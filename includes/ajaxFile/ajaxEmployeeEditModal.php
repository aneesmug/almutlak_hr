<?php
// Backend for the "Edit Employee" SweetAlert2 modal (assets/js/editEmployeeModal.js).
// get_edit_data: the employee's stored values + every dropdown list + what this user may change.
// update_employee: saves through employee_apply_edit() (includes/employee_edit_helper.php), the
// same code the old edit_employee.php form posted to.
header('Content-Type: application/json');
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../session_check.php';
require_once __DIR__ . '/../employee_edit_helper.php';

$action = $_POST['action'] ?? '';
$perm = employee_edit_permissions();

if (!$perm['modify']) {
	echo json_encode(['status' => 'error', 'message' => __('no_permission_modify_employee', 'You do not have permission to modify employee information.')]);
	exit;
}

$editEmpId = (string)($_POST['emp_id'] ?? '');
if (!ctype_digit($editEmpId)) {
	echo json_encode(['status' => 'error', 'message' => __('employee_not_found', 'Employee not found')]);
	exit;
}

function eem_rows(mysqli $conDB, string $sql): array
{
	$rows = [];
	$result = mysqli_query($conDB, $sql);
	if ($result) {
		while ($row = mysqli_fetch_assoc($result)) {
			$rows[] = $row;
		}
	}
	return $rows;
}

if ($action === 'get_edit_data') {
	cost_center_ensure_column($conDB);
	ensure_allow_loan_with_active_loan_column($conDB);

	$stmt = $pdo->prepare("SELECT * FROM `employees` WHERE `emp_id` = ? LIMIT 1");
	$stmt->execute([$editEmpId]);
	$emp = $stmt->fetch(PDO::FETCH_ASSOC);
	if (!$emp) {
		echo json_encode(['status' => 'error', 'message' => __('employee_not_found', 'Employee not found')]);
		exit;
	}

	// Status box: Active/Draft <-> Inactive; locked while on vacation. An inactive employee is always
	// re-hired as a new employee record (next free emp_id, new joining date) - and only once (locked
	// after that, pointing to the new record).
	$status = (string)$emp['status'];
	$eos = $status === '0' ? eos_latest_record($conDB, $editEmpId) : null;
	$rehiredAs = $status === '0' ? employee_rehired_as($conDB, $editEmpId) : '';
	$statusLocked = ($status !== '0' && (string)($emp['fly'] ?? '0') === '1') || $rehiredAs !== '';
	$lockReason = $rehiredAs !== ''
		? str_replace('{emp_id}', $rehiredAs, __('already_rehired_as', 'This employee was already re-hired as #{emp_id}.'))
		: __('status_locked_on_vacation', 'Cannot change status while employee is on vacation');

	// Hijri DOB was not always stored - derive it from the Gregorian one like the old page did
	if (($emp['dob_h'] ?? '') === '' && !empty($emp['dob'])) {
		include_once __DIR__ . '/../Hijri_GregorianConvert.php';
		$emp['dob_h'] = (new Hijri_GregorianConvert)->GregorianToHijri($emp['dob'], 'YYYY-MM-DD');
	}

	$d365On = d365_enabled($conDB);
	$blockLabels = [];
	foreach (get_blockable_request_type_labels() as $key => $label) {
		$blockLabels[] = ['key' => $key, 'label' => $label];
	}

	echo json_encode([
		'status' => 'success',
		'employee' => $emp,
		'perm' => $perm,
		'status_info' => [
			'value' => $status,
			'locked' => $statusLocked,
			'lock_reason' => $lockReason,
			'rehired_as' => $rehiredAs,
			// Set when reactivating means a re-hire: the End of Service settlement + the number the new record gets
			'rehire' => ($status === '0' && $rehiredAs === '') ? [
				'eos_id' => $eos ? (int)$eos['id'] : 0,
				'end_date' => $eos ? (string)$eos['end_date'] : '',
				'net_payment' => $eos ? (string)$eos['net_payment'] : '',
				// left without End of Service: when / why it was set inactive
				'ter_date' => substr((string)($emp['ter_date'] ?? ''), 0, 10),
				'ter_note' => (string)($emp['ter_note'] ?? ''),
				'next_emp_id' => eos_next_emp_id($pdo),
			] : null,
		],
		'countries' => eem_rows($conDB, "SELECT `id`, `name`, `name_ar` FROM `countries` ORDER BY `name` REGEXP '^[^A-Za-z]' ASC, `name`"),
		'departments' => eem_rows($conDB, "SELECT `id`, `dep_nme`, `dep_nme_ar` FROM `department` ORDER BY `dep_nme` REGEXP '^[^A-Za-z]' ASC, `dep_nme`"),
		'companies' => eem_rows($conDB, "SELECT `comp_id`, `comp_name`, `comp_name_ar` FROM `companies` ORDER BY `comp_name` REGEXP '^[^A-Za-z]' ASC, `comp_name`"),
		'cities' => eem_rows($conDB, "SELECT `id`, `name_en`, `name_ar` FROM `saudi_cities` ORDER BY `name_en` ASC"),
		// employees.bank_name stores bank_list.bnk_id
		'banks' => eem_rows($conDB, "SELECT `bnk_id`, `name`, `bank_name_ar` FROM `bank_list` ORDER BY `name` REGEXP '^[^A-Za-z]' ASC, `name`"),
		'jobs' => eem_rows($conDB, "SELECT `id`, `job`, `job_ar` FROM `ac_jobs` ORDER BY `job` REGEXP '^[^A-Za-z]' ASC, `job`"),
		'contract_periods' => eem_rows($conDB, "SELECT `id`, `period` FROM `contract_period` ORDER BY `period` REGEXP '^[^A-Za-z]' ASC, `period`"),
		'sponsorships' => eem_rows($conDB, "SELECT `id`, `sponsor`, `sponsor_ar` FROM `sponsorship` ORDER BY `sponsor` REGEXP '^[^A-Za-z]' ASC, `sponsor`"),
		'supervisors' => array_map(function ($row) {
			$row['name'] = parseName($row['name']);
			return $row;
		}, eem_rows($conDB, "SELECT e.`emp_id`, e.`name`, e.`emptype`, d.`dep_nme`, d.`dep_nme_ar`
			FROM `employees` e LEFT JOIN `department` d ON d.`id` = e.`dept`
			WHERE e.`status` = 1 AND e.`emptype` IN ('Manager', 'Supervisor') AND e.`emp_id` <> '" . mysqli_real_escape_string($conDB, $editEmpId) . "'
			ORDER BY e.`dept`, e.`emptype` = 'Manager' DESC, e.`name` ASC")),
		'all_locations' => eem_rows($conDB, "SELECT `id`, `city_id`, `name_en`, `name_ar` FROM `locations` ORDER BY `name_en` ASC"),
		'all_sub_departments' => eem_rows($conDB, "SELECT `id`, `department_id`, `name_en`, `name_ar` FROM `sub_departments` ORDER BY `name_en` ASC"),
		// Payroll company select is shown only while D365 is on (the stored value is kept otherwise)
		'payroll_company_html' => $d365On ? payroll_company_options_html($emp['payroll_company'] ?? '', payroll_employment_company($conDB, $editEmpId)) : '',
		'vac_salary_min_days' => (int)(getLocalAnnualPayrollRemovalRuleConfig()['minimum_days_exclusive'] ?? 20),
		'request_types' => $blockLabels,
		'global_blocked_types' => get_global_blocked_request_types($conDB),
		'blocked_types' => decode_blocked_request_types((string)($emp['blocked_request_types'] ?? '')),
	]);
	exit;
}

if ($action === 'update_employee') {
	$form = json_decode($_POST['form_data'] ?? '', true);
	if (!is_array($form)) {
		echo json_encode(['status' => 'error', 'message' => 'Invalid form data.']);
		exit;
	}
	$form = array_map(function ($v) { return is_scalar($v) || $v === null ? (string)$v : json_encode($v); }, $form);
	$form['emp_id'] = $editEmpId;
	try {
		$result = employee_apply_edit($form, $perm);
		echo json_encode(['status' => 'success'] + $result);
	} catch (Throwable $e) {
		echo json_encode(['status' => 'error', 'message' => ($e instanceof PDOException ? __('database_error') . ' ' : '') . $e->getMessage()]);
	}
	exit;
}

echo json_encode(['status' => 'error', 'message' => 'Unknown action.']);
