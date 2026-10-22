<?php
/**
 * Employee edit - shared by the Edit Employee SweetAlert2 modal (includes/ajaxFile/ajaxEmployeeEditModal.php)
 * and the legacy POST to edit_employee.php. Expects session_check.php (globals $pdo, $conDB, $empid,
 * $is_system_admin, $isDeptHr, $user_role, $user_type) and special_access_helper.php to be loaded.
 */

require_once __DIR__ . '/special_access_helper.php';
require_once __DIR__ . '/cost_centers.php';
require_once __DIR__ . '/eos_rehire.php';

/** Who may edit what. `modify` = may open/save the edit form at all. */
function employee_edit_permissions(): array
{
	global $conDB, $empid, $logged_in_empid, $user_role, $user_type, $is_system_admin, $isDeptHr;
	$me = (string)($logged_in_empid ?? $empid ?? ''); // $empid may be the viewed employee on profile pages
	$admin = !empty($is_system_admin);
	$has = function (string $key) use ($conDB, $me, $user_role, $user_type, $admin) {
		return $admin || user_has_special_access($conDB, $me, $key, $user_role ?? '', $user_type ?? '', $admin);
	};
	return [
		'modify' => $admin || !empty($isDeptHr) || $has('access_edit_employee'),
		'request_block' => $has('manage_employee_request_block'),
		'request_type_block' => $has('manage_employee_request_type_block'),
		'force_show_update_salary' => $has('manage_update_salary_button_visibility'),
		'loan_override' => $has('apply_loan_with_active_loan'),
	];
}

/**
 * Saves the posted edit form. Returns ['kind' => 'inactive'|'reactivated'|'updated'|'none', 'message' => ..., 'title' => ...].
 * Throws on access / validation / database errors.
 */
function employee_apply_edit(array $formData, array $perm): array
{
	global $pdo;

	if (empty($perm['modify'])) {
		throw new RuntimeException(__('no_permission_modify_employee', 'You do not have permission to modify employee information.'));
	}
	ensure_allow_loan_with_active_loan_column($GLOBALS['conDB']);

	// Cost center is no longer on this form - it is set per employee in App Settings > D365 Config > Employee Dimensions
	unset($formData['cost_center']);
	if (isset($formData['payroll_company'])) {
		$formData['payroll_company'] = payroll_company_clean($formData['payroll_company']); // '' = D365 employment company
	}
	// Whitelist of allowed database columns
	$allowedColumns = [
		'name', 'iqama', 'iqama_exp_g', 'iqama_exp', 'passport_number', 'passport_exp', 'mobile', 'emg_mobile', 'emg_name',
		'country', 'dept', 'city_id', 'location_id', 'sub_dept_id', 'cost_center', 'payroll_company', 'emptype', 'supervisor_id',
		'joining_date', 'dob', 'dob_h', 't_shirt_size', 'sex', 'mar_status', 'blood_type', 'emp_sup_type', 'actual_job',
		'vac_period', 'vacation_days', 'salary', 'bank_name', 'iban', 'email', 'c_email', 'address', 'comp_no', 'gosi',
		'probation', 'payment_type', 'is_overtime_eligible', 'allow_emergency_vacation', 'allow_vacation_salary_below_min_days',
		'other_income_enabled', 'requests_blocked', 'blocked_request_types', 'force_show_update_salary_btn', 'allow_loan_with_active_loan'
	];

	if (empty($perm['request_block'])) {
		unset($formData['requests_blocked']);
	}
	if (empty($perm['force_show_update_salary'])) {
		unset($formData['force_show_update_salary_btn']);
	}
	if (empty($perm['loan_override'])) {
		unset($formData['allow_loan_with_active_loan']);
	} elseif (isset($formData['allow_loan_with_active_loan'])) {
		$formData['allow_loan_with_active_loan'] = $formData['allow_loan_with_active_loan'] === '1' ? '1' : '0';
	}
	if (empty($perm['request_type_block'])) {
		unset($formData['blocked_request_types']);
	} elseif (isset($formData['blocked_request_types'])) {
		// Submitted as a JSON array string
		$formData['blocked_request_types'] = json_encode(decode_blocked_request_types((string)$formData['blocked_request_types']));
	}

	// FETCH OLD VALUES BEFORE UPDATE
	$employee_id_from_form = (string)($formData['emp_id'] ?? '');
	if (!ctype_digit($employee_id_from_form)) {
		throw new RuntimeException(__('employee_not_found', 'Employee not found'));
	}
	$old_stmt = $pdo->prepare("SELECT * FROM employees WHERE emp_id = :emp_id");
	$old_stmt->execute([':emp_id' => $employee_id_from_form]);
	$old_employee = $old_stmt->fetch(PDO::FETCH_ASSOC);
	if (!$old_employee) {
		throw new RuntimeException(__('employee_not_found', 'Employee not found'));
	}
	$old_values = [];

	// Prepare the update data
	$setParts = [];
	if (isset($formData['department'])) {
		$formData['dept'] = $formData['department'];
	}

	$params = [':emp_id' => $employee_id_from_form];

	// Nullable number/date columns: an empty field stores NULL (MySQL would turn '' into 0)
	$nullableTyped = [];
	foreach ($pdo->query("SHOW COLUMNS FROM `employees`") as $col) {
		if ($col['Null'] === 'YES' && preg_match('/^(tinyint|smallint|mediumint|int|bigint|decimal|float|double|date|datetime|timestamp)/i', $col['Type'])) {
			$nullableTyped[$col['Field']] = true;
		}
	}

	foreach ($formData as $field => $value) {
		// Skip non-database fields and the primary key
		if ($field === 'emp_id' || $field === 'submit' || !in_array($field, $allowedColumns, true)) {
			continue;
		}
		if ($field === 'salary') {
			$value = str_replace(',', '', $value);
		} elseif ($field === 'iban') {
			$value = str_replace(' ', '', $value);
		}
		if ($value === '' && isset($nullableTyped[$field])) {
			$value = null;
		}
		$setParts[] = "`$field` = :$field";
		$params[":$field"] = $value;

		// Collect old values for logging
		if (isset($old_employee[$field])) {
			$old_values[$field] = $old_employee[$field];
		}
	}

	// Employee status: only Active (1) <-> Draft (2) can be switched here, and only when the
	// employee is currently Active/Draft and not away on vacation. Inactive (0) stays with
	// the terminate / end-of-service flow. Draft employees drop out of every `status = 1`
	// list (payroll, active employees, dropdowns).
	if (isset($formData['emp_status'])
		&& in_array((string)$old_employee['status'], ['1', '2'], true)
		&& (string)($old_employee['fly'] ?? '0') !== '1'
		&& in_array((string)$formData['emp_status'], ['1', '2'], true)) {
		$setParts[] = "`status` = :status";
		$params[':status'] = (string)$formData['emp_status'];
		$old_values['status'] = $old_employee['status'];
	}

	// Employee status -> Inactive (0) (no end-of-service settlement). Same preconditions as above,
	// plus a reason note. The login account is deactivated too.
	$setInactive = false;
	if (isset($formData['emp_status']) && (string)$formData['emp_status'] === '0'
		&& in_array((string)$old_employee['status'], ['1', '2'], true)
		&& (string)($old_employee['fly'] ?? '0') !== '1') {
		$inactiveReason = trim((string)($formData['inactive_reason'] ?? ''));
		$inactiveType = in_array(($formData['inactive_type'] ?? ''), ['terminat', 'expired'], true) ? $formData['inactive_type'] : 'terminat';
		$inactiveDate = trim((string)($formData['inactive_date'] ?? ''));
		$inactiveDateObj = DateTime::createFromFormat('Y-m-d', $inactiveDate);
		if (!$inactiveDateObj || $inactiveDateObj->format('Y-m-d') !== $inactiveDate) {
			$inactiveDate = date('Y-m-d');
		}
		if ($inactiveReason !== '') {
			$setParts[] = "`status` = :status";
			$setParts[] = "`note` = :inactive_note";
			$setParts[] = "`ter_note` = :ter_note";
			$setParts[] = "`ter_date` = :ter_date";
			$setParts[] = "`fly` = '0'";
			$params[':status'] = '0';
			$params[':inactive_note'] = $inactiveType;
			$params[':ter_note'] = $inactiveReason;
			$params[':ter_date'] = $inactiveDate;
			$old_values['status'] = $old_employee['status'];
			$old_values['note'] = $old_employee['note'] ?? '';
			$old_values['ter_note'] = $old_employee['ter_note'] ?? '';
			$old_values['ter_date'] = $old_employee['ter_date'] ?? '';
			$setInactive = true;
		}
	}

	// Inactive (0) -> Active (1) / Draft (2): undo what the Inactive path above wrote (clear note,
	// ter_note, ter_date) and re-enable the login. An employee who left through End of Service is
	// re-hired instead: the old record stays as it is (inactive, with its settlement) and a NEW
	// employee record is registered under the next free emp_id from the new joining date
	// (includes/eos_rehire.php). The edits made in the form are applied to the new record.
	$setReactivated = false;
	$rehireEos = null;
	$rehireDate = null;
	if (isset($formData['emp_status'])
		&& (string)$old_employee['status'] === '0'
		&& in_array((string)$formData['emp_status'], ['1', '2'], true)) {
		$eos = eos_latest_record($GLOBALS['conDB'], $employee_id_from_form);
		if ($eos && !empty($eos['rehired_emp_id'])) {
			throw new RuntimeException(str_replace('{emp_id}', $eos['rehired_emp_id'],
				__('already_rehired_as', 'This employee was already re-hired as #{emp_id}.')));
		}
		if ($eos) {
			$rehireEos = $eos;
			$rehireDate = trim((string)($formData['rehire_joining_date'] ?? ''));
			$rehireObj = DateTime::createFromFormat('!Y-m-d', $rehireDate);
			if (!$rehireObj || $rehireObj->format('Y-m-d') !== $rehireDate) {
				throw new RuntimeException(__('rehire_joining_date_required', 'Enter the new joining date for the re-hire.'));
			}
			$eosEnd = DateTime::createFromFormat('!Y-m-d', (string)($eos['end_date'] ?? ''));
			if ($eosEnd && $rehireObj <= $eosEnd) {
				throw new RuntimeException(str_replace('{date}', $eosEnd->format('Y-m-d'),
					__('rehire_date_after_eos', 'The new joining date must be after the End of Service date ({date}).')));
			}
			// The new record gets the re-hire date - drop the old joining date the Employment tab posts
			$setParts = array_values(array_filter($setParts, function ($p) { return strpos($p, '`joining_date`') !== 0; }));
			unset($params[':joining_date']);
		} else {
			$setParts[] = "`status` = :status";
			$setParts[] = "`note` = ''";
			$setParts[] = "`ter_note` = ''";
			$setParts[] = "`ter_date` = ''";
			$params[':status'] = (string)$formData['emp_status'];
			$old_values['status'] = $old_employee['status'];
			$old_values['note'] = $old_employee['note'] ?? '';
			$old_values['ter_note'] = $old_employee['ter_note'] ?? '';
			$old_values['ter_date'] = $old_employee['ter_date'] ?? '';
		}
		$setReactivated = true;
	}

	if (!$setParts && !$rehireEos) {
		return ['kind' => 'none', 'title' => __('info'), 'message' => __('no_changes_made')];
	}

	// Build and execute the query. A re-hire first registers the new record, the form's edits then
	// go to that record, and the settlement is linked to it - all as one change.
	$targetEmpId = $employee_id_from_form;
	$newStatus = (string)($formData['emp_status'] ?? '');
	$pdo->beginTransaction();
	try {
		if ($rehireEos) {
			$targetEmpId = eos_create_rehire_record($pdo, $employee_id_from_form, $rehireDate, $newStatus);
			$params[':emp_id'] = $targetEmpId;
		}
		$stmt = null;
		if ($setParts) {
			$sql = "UPDATE `employees` SET " . implode(', ', $setParts) . " WHERE `emp_id` = :emp_id";
			$stmt = $pdo->prepare($sql);
			$stmt->execute($params);
		}
		if ($rehireEos) {
			$pdo->prepare("UPDATE `emp_eos` SET `rehired_at` = NOW(), `rehired_by` = :by, `rehired_emp_id` = :new_emp, `rehire_joining_date` = :new_date WHERE `id` = :id")
				->execute([
					':by' => (string)($GLOBALS['logged_in_empid'] ?? $GLOBALS['empid'] ?? ''),
					':new_emp' => $targetEmpId,
					':new_date' => $rehireDate,
					':id' => $rehireEos['id'],
				]);
			// The person's login (username = Iqama) moves to the new employment
			$pdo->prepare("UPDATE `admin_login` SET `emp_id` = :new_emp, `status` = :st WHERE `emp_id` = :old_emp")
				->execute([':new_emp' => $targetEmpId, ':st' => $newStatus === '1' ? 1 : 0, ':old_emp' => $employee_id_from_form]);
		}
		$pdo->commit();
	} catch (Throwable $e) {
		$pdo->rollBack();
		throw $e;
	}

	// Inactive employee: deactivate the matching login account as well (same as terminate / EOS).
	if ($setInactive) {
		$pdo->prepare("UPDATE `admin_login` SET `status` = 0 WHERE `emp_id` = :emp_id")->execute([':emp_id' => $employee_id_from_form]);
	}
	// Reactivated employee: switch the login back on (only when back to Active, Draft stays locked out).
	if ($setReactivated && !$rehireEos && $newStatus === '1') {
		$pdo->prepare("UPDATE `admin_login` SET `status` = 1 WHERE `emp_id` = :emp_id")->execute([':emp_id' => $employee_id_from_form]);
	}

	// Keep admin_login.dept in sync with employees.dept (all_users.php reads admin_login's own copy).
	if (isset($params[':dept'])) {
		$pdo->prepare("UPDATE `admin_login` SET `dept` = :dept WHERE `emp_id` = :emp_id")->execute([':dept' => $params[':dept'], ':emp_id' => $targetEmpId]);
	}

	if ($rehireEos) {
		ActivityLogger::logCreate(
			'Employee',
			'edit_employee.php',
			$targetEmpId,
			['emp_id' => $targetEmpId, 'rehired_from_emp_id' => $employee_id_from_form, 'joining_date' => $rehireDate, 'status' => $newStatus],
			"Re-hired employee " . ($old_employee['name'] ?? '') . " (#{$employee_id_from_form}) as #{$targetEmpId} after End of Service",
			'employees'
		);
		return [
			'kind' => 'rehired',
			'new_emp_id' => $targetEmpId,
			'title' => __('success'),
			'message' => str_replace(['{emp_id}', '{date}'], [$targetEmpId, $rehireDate],
				__('employee_rehired_success', 'Employee has been re-hired as #{emp_id} with joining date {date}. The previous record and its End of Service are kept.')),
		];
	}

	$changed = $stmt && $stmt->rowCount() > 0;
	// LOG EMPLOYEE UPDATE ACTION
	if ($changed) {
		$new_values = [];
		foreach ($params as $key => $value) {
			if ($key !== ':emp_id') {
				$new_values[ltrim($key, ':')] = $value;
			}
		}
		ActivityLogger::logUpdate(
			'Employee',
			'edit_employee.php',
			$employee_id_from_form,
			$old_values,
			$new_values,
			"Updated employee: " . ($old_employee['name'] ?? 'Unknown'),
			'employees'
		);
	}

	if ($setInactive && $changed) {
		return ['kind' => 'inactive', 'title' => __('success'), 'message' => __('employee_set_inactive_success', 'Employee has been set to Inactive and the login account was disabled.')];
	}
	if ($setReactivated && $changed) {
		return ['kind' => 'reactivated', 'title' => __('success'), 'message' => __('employee_reactivated_success', 'Employee has been reactivated and the inactive details were cleared.')];
	}
	if ($changed) {
		return ['kind' => 'updated', 'title' => __('success'), 'message' => __('employee_details_edited_successfully')];
	}
	return ['kind' => 'none', 'title' => __('info'), 'message' => __('no_changes_made')];
}
