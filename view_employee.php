<?php

require_once __DIR__ . '/includes/session_check.php';
require_once __DIR__ . '/includes/special_access_helper.php';
require_once __DIR__ . '/includes/eos_estimate_helper.php';
require_once __DIR__ . '/includes/cost_centers.php';

$query = mysqli_query($conDB, "SELECT * FROM `admin_login` WHERE `id_iqama`='" . $username . "'");
if (mysqli_num_rows($query) == 1) {
	include("./includes/avatar_select.php");

	require("./includes/vacation_processor.php");

	include("./includes/Hijri_GregorianConvert.php");
	$DateConv = new Hijri_GregorianConvert;
	// $format="DD/MM/YYYY";
	$format = "YYYY-MM-DD";
	require("./includes/emp_query.php");

	if (!isset($_GET['emp_id']) || empty($_GET['emp_id'])) {
		header("Location: ./reg_employee.php");
		exit;
	}

	if (mysqli_num_rows($get_emp_data) !== 0) {
		$allRecords = mysqli_fetch_all($get_emp_data, MYSQLI_ASSOC);
		foreach ($allRecords as $rec) {
			$emprow = $rec;
		}

		$direct_reports_count = 0;
		$direct_reports_count_stmt = mysqli_prepare($conDB, "SELECT COUNT(*) AS total FROM `employees` WHERE `supervisor_id` = ? AND `status` = '1'");
		mysqli_stmt_bind_param($direct_reports_count_stmt, "s", $emprow['emp_id']);
		mysqli_stmt_execute($direct_reports_count_stmt);
		$direct_reports_count = (int)(mysqli_stmt_get_result($direct_reports_count_stmt)->fetch_assoc()['total'] ?? 0);
		mysqli_stmt_close($direct_reports_count_stmt);

		// UNIFIED ACCESS CONTROL - Use centralized function only
		// This function checks all permissions: admin, HR, IT, department manager, direct supervisor, self
		
		// DEBUG: Log accessible departments
		$accessible_depts = getAccessibleDepartments(true);
		
		$user_data = [
			'emp_id' => $_SESSION['auth_user']['emp_id'] ?? $empid ,
			'dept' => $_SESSION['auth_user']['dept'] ?? $user_dept,
			'comp_no' => $_SESSION['auth_user']['comp_no'] ?? 1,
			'user_type' => $user_type,
			'accessible_departments' => $accessible_depts
		];
		
		$employee_data = [
			'emp_id' => $emprow['emp_id'] ?? $emprow['empid'],
			'supervisor_id' => $emprow['supervisor_id'] ?? null,
			'dept' => $emprow['dept'] ?? null,
			'comp_no' => $emprow['comp_no'] ?? 1
		];
		
		if (!canEmployeeSupervisorAccess($employee_data, $user_data, $user_role)) {
			$_SESSION['error_msg'] = sprintf(
				'<div class="col-xl-12">
					<div class="alert alert-danger bg-danger text-white border-0" role="alert">
						<b>%s</b>
						<h4>%s</h4>
					</div>
				</div>',
				__('access_denied', 'Access Denied!'),
				__('access_denied_employee_view_message', "You don't have access to view this employee. Your access is limited to employees in your department and company.")
			);
			header("Location: ./dashboard.php");
			exit;
		}
		// If we get here, access is granted

		// --- START: Temporary Role Transfer (vacation replacement coverage) ---
		// Only HR Senior BP / HR Payroll (their own real role, not any borrowed temp role)
		// may grant/revoke coverage, matching includes/ajaxFile/tempRoleHandler.php.
		$can_manage_temp_roles = ($is_system_admin || in_array($actual_user_type ?? $user_type, ['hr_senior_bp', 'hr_payroll'], true));
		$tempRoleCandidate = $can_manage_temp_roles
			? getReplacementTempRoleCandidate($conDB, $emprow['emp_id'] ?? $emprow['empid'])
			: null;
		// --- END: Temporary Role Transfer ---

		// Salary info visible only to sys admin, HR, or an explicitly granted
		// 'view_employee_salary_value' special access (App Settings -> Special Access).
		$canViewSalary = (
			($is_system_admin ?? false) || ($isHR ?? false) || ($isDeptHr ?? false)
			|| user_has_special_access($conDB, $empid ?? '', 'view_employee_salary_value', $user_role ?? '', $user_type ?? '', $is_system_admin ?? false)
		);

		// EOS estimate badge visible only to sys admin, HR, or an explicitly granted
		// 'view_employee_eos_value' special access (App Settings -> Special Access).
		$canViewEosValue = (
			($is_system_admin ?? false) || ($isHR ?? false) || ($isDeptHr ?? false)
			|| user_has_special_access($conDB, $empid ?? '', 'view_employee_eos_value', $user_role ?? '', $user_type ?? '', $is_system_admin ?? false)
		);
		$eos_estimate = $canViewEosValue
			? calculate_current_eos_estimate($conDB, $emprow['empid'] ?? '', $emprow['joining_date'] ?? '')
			: null;

		// Attendance Record tab - hidden from everyone except sys admin unless an
		// explicitly granted 'view_employee_attendance_tab' special access (App Settings ->
		// Special Access). Unlike salary/EOS above, HR/Dept HR do NOT get this by default.
		$canViewAttendanceTab = attendance_enabled($conDB) && (
			($is_system_admin ?? false)
			|| user_has_special_access($conDB, $empid ?? '', 'view_employee_attendance_tab', $user_role ?? '', $user_type ?? '', $is_system_admin ?? false)
		);
		// Documents tab delete button - only with the 'delete_employee_documents' Special Access
		// key (admins always). Checked here because emp_top_info.php later overwrites $empid with
		// the viewed employee's ID. deleteAjax.php enforces the same key.
		$canDeleteEmpDocs = user_has_special_access($conDB, $empid ?? '', 'delete_employee_documents', $user_role ?? '', $user_type ?? '', $is_system_admin ?? false);
		// Memos tab (sent HR memos/letters) - same access as Employees > Send Memo
		// (employee_memos.php in App Settings > Page Access).
		require_once __DIR__ . '/includes/memo_helper.php';
		$canViewMemosTab = memo_user_can($conDB, 'send', $empid ?? '', $user_role ?? '', $user_type ?? '', $is_system_admin ?? false);
		// Manually add/edit a day's punch (e.g. employee forgot to clock in/out) - same
		// 'manage_attendance' key that already gates the standalone Attendance Record page.
		$canManageAttendanceRecord = attendance_enabled($conDB) && (
			($is_system_admin ?? false)
			|| user_has_special_access($conDB, $empid ?? '', 'manage_attendance', $user_role ?? '', $user_type ?? '', $is_system_admin ?? false)
		);
		// Add Record button (new manual record) needs the separate 'add_manual_attendance' key; editing stays under manage_attendance.
		$canAddAttendanceRecord = $canManageAttendanceRecord
			&& user_has_special_access($conDB, $empid ?? '', 'add_manual_attendance', $user_role ?? '', $user_type ?? '', $is_system_admin ?? false);
		// Row Edit / Delete on the Attendance tab: own Special Access keys; the Action column is hidden when neither is granted
		$canEditAttendanceRecord = $canManageAttendanceRecord
			&& user_has_special_access($conDB, $empid ?? '', 'edit_attendance_record', $user_role ?? '', $user_type ?? '', $is_system_admin ?? false);
		$canDeleteAttendanceRecord = $canManageAttendanceRecord
			&& user_has_special_access($conDB, $empid ?? '', 'delete_attendance_record', $user_role ?? '', $user_type ?? '', $is_system_admin ?? false);
		$canAttendanceRowActions = $canEditAttendanceRecord || $canDeleteAttendanceRecord;
		require_once __DIR__ . '/includes/attendance_ui.php'; // shared attendance status tiles + date filter (same as attendance.php)

		// --- START: Loan Summary Calculation ---
		$loan_summary = null;
		$sql_active_loan = "SELECT * FROM `emp_loan` WHERE `emp_id`='" . $emprow['empid'] . "' AND `status` = 'approved' ORDER BY `id` DESC LIMIT 1";
		$query_active_loan = mysqli_query($conDB, $sql_active_loan);

		if (mysqli_num_rows($query_active_loan) > 0) {
			$active_loan_data = mysqli_fetch_assoc($query_active_loan);
			$loan_id = $active_loan_data['id'];

			$sql_total_paid = "SELECT COALESCE(SUM(amount), 0) as total_paid FROM `emp_loan_payments` WHERE `loan_id` = '$loan_id'";
			$query_total_paid = mysqli_query($conDB, $sql_total_paid);
			$paid_data = mysqli_fetch_assoc($query_total_paid);

			$total_paid = $paid_data['total_paid'];
			$total_payable = $active_loan_data['total_payable'];
			$remaining_balance = $total_payable - $total_paid;

			$loan_summary = [
				'loan_id' => $loan_id,
				'inv_no' => $active_loan_data['inv_no'],
				'loan_type' => $active_loan_data['loan_type'],
				'loan_amount' => $active_loan_data['loan_amount'],
				'final_approved_amount' => $active_loan_data['final_approved_amount'] ?? $active_loan_data['loan_amount'],
				'installments' => $active_loan_data['installments'],
				'monthly_deduction' => $active_loan_data['monthly_deduction'],
				'deduction_mode' => $active_loan_data['deduction_mode'] ?? 'automatic',
				'start_date' => $active_loan_data['start_date'],
				'end_date' => $active_loan_data['end_date'],
				'total_payable' => $total_payable,
				'total_paid' => $total_paid,
				'remaining_balance' => $remaining_balance,
				'disbursement_receipt' => $active_loan_data['disbursement_receipt_id'],
				'disbursement_attachment' => $active_loan_data['disbursement_attachment']
			];
		}
		// --- END: Loan Summary Calculation ---

		// --- START: Last Approved Salary Increment (for the summary card) ---
		$last_salary_increment = null;
		$last_si_stmt = mysqli_prepare($conDB, "SELECT approved_amount, increment_amount, COALESCE(last_increment_date, DATE(last_modified)) AS effective_date FROM `emp_salary_increment` WHERE `emp_id` = ? AND `current_status` = 'approved' ORDER BY COALESCE(last_increment_date, DATE(last_modified)) DESC LIMIT 1");
		if ($last_si_stmt) {
			mysqli_stmt_bind_param($last_si_stmt, "s", $emprow['empid']);
			mysqli_stmt_execute($last_si_stmt);
			$last_salary_increment = mysqli_fetch_assoc(mysqli_stmt_get_result($last_si_stmt)) ?: null;
			mysqli_stmt_close($last_si_stmt);
		}
		// --- END: Last Approved Salary Increment ---

		// --- START: Local Vacation Count (for the summary card row) ---
		$local_vacation_count = 0;
		$local_vac_stmt = mysqli_prepare($conDB, "SELECT COUNT(*) AS cnt FROM `emp_vacation` WHERE `emp_id` = ? AND `vac_type` = 'Local Vacation' AND `current_status` IN ('approved','completed')");
		if ($local_vac_stmt) {
			mysqli_stmt_bind_param($local_vac_stmt, "s", $emprow['empid']);
			mysqli_stmt_execute($local_vac_stmt);
			$local_vac_row = mysqli_fetch_assoc(mysqli_stmt_get_result($local_vac_stmt));
			$local_vacation_count = (int)($local_vac_row['cnt'] ?? 0);
			mysqli_stmt_close($local_vac_stmt);
		}
		// --- END: Local Vacation Count ---

		// --- START: Payroll (Payslip) History ---
		$payroll_history = [];
		$payroll_benefits_by_month = [];
		if ($canViewSalary) {
			$payroll_history_stmt = mysqli_prepare($conDB, "SELECT month_year, basic_salary, total_gross_salary, total_benefits, total_deductions, net_salary FROM `payrolls` WHERE `emp_id` = ? AND `status` = 'paid' ORDER BY `month_year` DESC");
			mysqli_stmt_bind_param($payroll_history_stmt, "s", $emprow['empid']);
			mysqli_stmt_execute($payroll_history_stmt);
			$payroll_history = mysqli_fetch_all(mysqli_stmt_get_result($payroll_history_stmt), MYSQLI_ASSOC);
			mysqli_stmt_close($payroll_history_stmt);

			// Itemized benefit lines (name + amount) per paid month, for the "Additional Benefits" breakdown.
			$payroll_benefits_stmt = mysqli_prepare($conDB, "SELECT month, benefit, note FROM `payroll_benefits` WHERE `emp_id` = ? AND `status` = 1 ORDER BY `id` ASC");
			mysqli_stmt_bind_param($payroll_benefits_stmt, "s", $emprow['empid']);
			mysqli_stmt_execute($payroll_benefits_stmt);
			$payroll_benefits_rows = mysqli_fetch_all(mysqli_stmt_get_result($payroll_benefits_stmt), MYSQLI_ASSOC);
			mysqli_stmt_close($payroll_benefits_stmt);
			foreach ($payroll_benefits_rows as $benefit_row) {
				$payroll_benefits_by_month[$benefit_row['month']][] = $benefit_row;
			}
		}
		// D365 tab (system admins + 'view_employee_d365_tab' special access) - content loaded on first open from includes/ajaxFile/d365_employee_tab.php
		$show_d365_tab = d365_enabled($conDB) && user_has_special_access($conDB, $empid ?? '', 'view_employee_d365_tab', $user_role ?? '', $user_type ?? '', $is_system_admin ?? false);
		// "Sync to D365" (header widget + Payrolls tab): system admins + 'd365_sync_employee' special access
		$can_d365_sync = d365_enabled($conDB) && user_has_special_access($conDB, $empid ?? '', 'd365_sync_employee', $user_role ?? '', $user_type ?? '', $is_system_admin ?? false);
		if ($show_d365_tab && empty($_SESSION['d365_csrf'])) {
			$_SESSION['d365_csrf'] = bin2hex(random_bytes(16));
		}

		// D365 sync state per paid month (status shown to everyone, Sync button needs $can_d365_sync; local push log, no D365 call)
		$d365_pay_state = null;
		$d365_pay_env = '';
		if (d365_enabled($conDB) && !empty($payroll_history)) {
			try {
				require_once __DIR__ . '/includes/D365/D365Payroll.php';
				$d365_pay_client = new D365Client();
				$d365_pay_env = $d365_pay_client->getEnvironment();
				$d365_payroll = new D365Payroll($conDB, $d365_pay_client);
				// "Synced" months are re-checked against D365 (journal deleted there = Removed, can sync again); every 2 min per employee
				$d365_verify_file = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'd365_payverify_' . md5($d365_pay_env . '|' . $emprow['empid']);
				if (!is_file($d365_verify_file) || time() - filemtime($d365_verify_file) > 120) {
					try {
						$d365_payroll->verifyEmployeePushed($d365_pay_env, (string)$emprow['empid']);
						@touch($d365_verify_file);
					} catch (Throwable $ex) {
						error_log('D365 payroll verify: ' . $ex->getMessage()); // D365 unreachable - show the app's log as it is
					}
				}
				$d365_pay_state = $d365_payroll->getEmployeeSyncState($d365_pay_env, $emprow['empid']);
				if (empty($_SESSION['d365_csrf'])) {
					$_SESSION['d365_csrf'] = bin2hex(random_bytes(16));
				}
			} catch (Throwable $ex) {
				$d365_pay_state = null; // D365 not configured - no column
			}
		}
		// --- END: Payroll (Payslip) History ---

		// --- START: Additional Information (HR/Payroll reference fields) ---
		// Hidden from everyone except sys admin by default; grant via App Settings ->
		// Special Access ('view_employee_additional_info') to let specific HR/DeptHr users see it.
		$canViewAdditionalInfo = (
			($is_system_admin ?? false)
			|| user_has_special_access($conDB, $empid ?? '', 'view_employee_additional_info', $user_role ?? '', $user_type ?? '', $is_system_admin ?? false)
		);
		$canEditAdditionalInfo = $canViewAdditionalInfo && ($isHR || $is_system_admin || $isDeptHr);

		// Other Income (scheduled Bonus/extra income) has its own dedicated special access key
		// so it can be granted independently of the rest of Additional Information - e.g. a
		// payroll user who should only manage Other Income, not see salary grade/ticket/etc.
		// Edit Employee page has its own per-employee master switch ('other_income_enabled',
		// default on) - when off, the section is hidden for everyone on THIS employee's
		// profile regardless of the viewer's own special access grant.
		$otherIncomeEnabledForEmployee = ((string)($emprow['other_income_enabled'] ?? '1') === '1');
		$canViewOtherIncome = $otherIncomeEnabledForEmployee && (
			($is_system_admin ?? false)
			|| user_has_special_access($conDB, $empid ?? '', 'view_employee_other_income', $user_role ?? '', $user_type ?? '', $is_system_admin ?? false)
		);
		$canEditOtherIncome = $canViewOtherIncome && ($isHR || $is_system_admin || $isDeptHr);

		$employee_additional_info = null;
		if ($canViewSalary) {
			$additional_info_stmt = mysqli_prepare($conDB, "SELECT * FROM `employee_additional_info` WHERE `emp_id` = ? LIMIT 1");
			mysqli_stmt_bind_param($additional_info_stmt, "s", $emprow['empid']);
			mysqli_stmt_execute($additional_info_stmt);
			$employee_additional_info = mysqli_fetch_assoc(mysqli_stmt_get_result($additional_info_stmt)) ?: null;
			mysqli_stmt_close($additional_info_stmt);
		}

		// Monthly Leave Accrual is derived from the employee's contract, not manually
		// entered - contract_period.vac_period is the TOTAL days for the full contract term
		// (e.g. "2 Years - 30" -> 60 days = 30/year x 2 years), so divide by the year count
		// parsed from the period label first to get the annual rate, then by 12 for monthly.
		$monthly_leave_accrual_calculated = null;
		if (!empty($emprow['vac_period'])) {
			$contract_period_stmt = mysqli_prepare($conDB, "SELECT `period`, `vac_period` FROM `contract_period` WHERE `id` = ? LIMIT 1");
			mysqli_stmt_bind_param($contract_period_stmt, "i", $emprow['vac_period']);
			mysqli_stmt_execute($contract_period_stmt);
			$contract_period_row = mysqli_fetch_assoc(mysqli_stmt_get_result($contract_period_stmt));
			mysqli_stmt_close($contract_period_stmt);
			if ($contract_period_row && (float)$contract_period_row['vac_period'] > 0) {
				$years = 1;
				if (preg_match('/^\s*(\d+(?:\.\d+)?)/', (string)$contract_period_row['period'], $years_match)) {
					$years = max(1, (float)$years_match[1]);
				}
				$annual_days = (float)$contract_period_row['vac_period'] / $years;
				$monthly_leave_accrual_calculated = $annual_days / 12;
			}
		}

		// Medical Insurance / Insurance No / Medical Expiry are yearly-renewed, so they live
		// in their own history table instead of employee_additional_info - each renewal adds
		// a new row and the previous one flips to status='expired' (see
		// employeeMedicalInsuranceHandler.php). The active row is what's "current" here.
		$medical_insurance_records = [];
		$current_medical_insurance = null;
		if ($canViewSalary) {
			$medical_insurance_stmt = mysqli_prepare($conDB, "SELECT id, insurance_no, med_insurance, medical_expiry, medical_class, status, created_at FROM `employee_medical_insurance` WHERE `emp_id` = ? ORDER BY `created_at` DESC, `id` DESC");
			mysqli_stmt_bind_param($medical_insurance_stmt, "s", $emprow['empid']);
			mysqli_stmt_execute($medical_insurance_stmt);
			$medical_insurance_records = mysqli_fetch_all(mysqli_stmt_get_result($medical_insurance_stmt), MYSQLI_ASSOC);
			mysqli_stmt_close($medical_insurance_stmt);
			foreach ($medical_insurance_records as $mi_row) {
				if ($mi_row['status'] === 'active') {
					$current_medical_insurance = $mi_row;
					break;
				}
			}
		}

		// Scheduled "Other Income" (e.g. a 3-month Bonus) - HR schedules an amount across a
		// month range once, and payroll generation auto-adds it as a payroll_benefits row for
		// every covered month (see addOrUpdateScheduledOtherIncome in process_payroll.php),
		// then flips the schedule to status = 0 once its end_month has been processed.
		$other_income_records = [];
		if ($canViewOtherIncome) {
			$other_income_stmt = mysqli_prepare($conDB, "SELECT id, title, amount, start_month, end_month, status, created_at FROM `employee_other_income` WHERE `emp_id` = ? ORDER BY `created_at` DESC, `id` DESC");
			mysqli_stmt_bind_param($other_income_stmt, "s", $emprow['empid']);
			mysqli_stmt_execute($other_income_stmt);
			$other_income_records = mysqli_fetch_all(mysqli_stmt_get_result($other_income_stmt), MYSQLI_ASSOC);
			mysqli_stmt_close($other_income_stmt);
		}
		// --- END: Additional Information ---
		// debug($emprow);

		$salary_get = str_replace(',', '', ($emprow['basic'] + $emprow['housing'] + $emprow['transport'] + $emprow["food"] + $emprow["misc"] + $emprow["cashier"] + $emprow["fuel"] + $emprow["tel"] + $emprow["other"] + $emprow["guard"]));

		$hours_in_day   = 24;
		$minutes_in_hour = 60;
		$seconds_in_mins = 60;
		$birth_date     = new DateTime($emprow["dob"]);
		$current_date   = new DateTime();
		$diff           = $birth_date->diff($current_date);
		$years	   		= $diff->y . " " . __('years');
		// $vacyear_get = preg_replace("/[^0-9]/", "", $emprow["period"]);

		if ($emprow["status"] == 0 && $emprow["note"] == "expired") {
			$note_get = "Expired";
		} elseif ($emprow["status"] == 0 && $emprow["note"] == "terminat") {
			$note_get = "Terminated";
		}
	} else {
		header("Location: ./reg_employee.php");
	}

	$date = $DateConv->HijriToGregorian($emprow['iqama_exp'], $format);
	$exprydte = format_safe_date($date, 'm-'); //
	$today = date('m');

	$salaryItems = ['basic', 'housing', 'transport', 'food', 'misc', 'cashier', 'fuel', 'tel', 'other', 'guard'];
	// $salaryItems = ['أساسي'،'سكن'،'مواصلات'،'طعام'،'متنوع'،'صراف'،'وقود'،'هاتف'،'أخرى'،'حارس'];

	$shownItems = [];
	foreach ($salaryItems as $item) {
		if (!empty($emprow[$item]) && $emprow[$item] != "0") {
			$shownItems[] = $item;
		}
	}
	$countItems = count($shownItems); // Salary items only
	$totalBoxes = $countItems + 1; // +1 for Total Salary box
	$colsm = "col-sm-" . floor(12 / $totalBoxes); // Default column for all boxes
	// Special case: if 5 items, give Total Salary more space (col-sm-4)
	$totalColsm = ($countItems == 4) ? "col-sm-4" : $colsm;

	$join_date		= new DateTime($emprow['joining_date']);
	$curr_date  	= new DateTime();
	$joindiff		= $join_date->diff($curr_date);

	$probationStatus = ($emprow['probation'] !== NULL && $emprow['probation'] !== "")
		? (($joindiff->days > ((int)$emprow['probation'] * 30))
			? __('no_probation')
			: (int)$emprow['probation'] . " Months")
		: (($joindiff->days < 90)
			? __('under_probation')
			: __('no_probation'));

	$overtimeEligibilityText = ((string)($emprow['is_overtime_eligible'] ?? '0') === '1')
		? __('yes', 'Yes')
		: __('no', 'No');

	$allowEmergencyVacationText = ((string)($emprow['allow_emergency_vacation'] ?? '0') === '1')
		? __('yes', 'Yes')
		: __('no', 'No');

	$requestsBlockedText = ((string)($emprow['requests_blocked'] ?? '0') === '1')
		? __('yes', 'Yes')
		: __('no', 'No');
	// Effective per-type block status = globally_blocked XOR this employee's override checkbox,
	// matching the logic in is_employee_request_blocked() (includes/special_access_helper.php).
	$blockedRequestTypeLabels = get_blockable_request_type_labels();
	$globallyBlockedRequestTypes = get_global_blocked_request_types($conDB);
	$employeeOverrideTypes = decode_blocked_request_types((string)($emprow['blocked_request_types'] ?? ''));
	$effectiveBlockedTypeParts = [];
	foreach ($blockedRequestTypeLabels as $key => $label) {
		$isGlobal = in_array($key, $globallyBlockedRequestTypes, true);
		$isOverride = in_array($key, $employeeOverrideTypes, true);
		if ($isGlobal xor $isOverride) {
			$effectiveBlockedTypeParts[] = $label . ($isGlobal ? ' (' . __('global', 'Global') . ')' : ' (' . __('individual', 'Individual') . ')');
		}
	}
	$blockedRequestTypesText = empty($effectiveBlockedTypeParts)
		? __('none', 'None')
		: implode(', ', $effectiveBlockedTypeParts);

	$all_statuses = [
		'apply' => __('new_request'),
		'pending' => __('assistant_pending'),
		'hr_assistant_approved' => __('hr_assistant_approved'),
		'hr_manager_approved' => __('hr_manager_approved'),
		'gm_approved' => __('gm_approved'),
		'rejected' => __('rejected'),
	];

?>
	<!doctype html>
	<html lang="<?= $current_lang ?? 'en' ?>" <?= ($is_rtl ?? false) ? 'dir="rtl"' : '' ?>>

	<head>
		<meta charset="utf-8" />
		<title><?= $site_title ?> - <?= __('view_employee_title', 'View Employee') ?> <?= $emprow['name'] ?> <?= __('details') ?></title>
		<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
		<!--        <meta content="A fully featured admin theme which can be used to build CRM, CMS, etc." name="description" />-->
		<meta content="Anees Afzal" name="author" />
		<meta http-equiv="X-UA-Compatible" content="IE=edge" />
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />

		<!-- App favicon -->
		<link rel="shortcut icon" href="<?= get_setting($conDB, 'favicon') ?>">

		<!-- Modal -->
		<link href="./plugins/custombox/css/custombox.min.css" rel="stylesheet">

		<!-- Plugins css -->
		<link href="./plugins/bootstrap-timepicker/bootstrap-timepicker.min.css" rel="stylesheet">
		<link href="./plugins/bootstrap-datepicker/css/bootstrap-datepicker.min.css" rel="stylesheet">
		<link href="./plugins/clockpicker/css/bootstrap-clockpicker.min.css" rel="stylesheet">
		<link href="./plugins/bootstrap-daterangepicker/daterangepicker.css" rel="stylesheet">

		<link rel="stylesheet" href="./plugins/bootstrap-select/css/bootstrap-select.min.css">
		<link rel="stylesheet" href="./plugins/select2/css/select2.min.css">

		<!-- <link href="./plugins/bootstrap-daterangepicker/daterangepicker.css" rel="stylesheet"> -->
		<!-- <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/css/select2.min.css" /> -->

		<!-- DataTables -->
		<link href="./plugins/datatables/dataTables.bootstrap4.min.css" rel="stylesheet" type="text/css" />
		<link href="./plugins/datatables/buttons.bootstrap4.min.css" rel="stylesheet" type="text/css" />
		<!-- Responsive datatable examples -->
		<link href="./plugins/datatables/responsive.bootstrap4.min.css" rel="stylesheet" type="text/css" />

		<!-- Multi Item Selection examples -->
		<link href="./plugins/datatables/select.bootstrap4.min.css" rel="stylesheet" type="text/css" />

		<link href="./plugins/summernote/summernote.min.css" rel="stylesheet" />

		<!-- App css -->
		<link href="assets/css/bootstrap.min.css" rel="stylesheet" type="text/css" />
		<link href="assets/css/icons.css" rel="stylesheet" type="text/css" />
		<link href="assets/css/metismenu.min.css" rel="stylesheet" type="text/css" />
		<link href="assets/css/style.css" rel="stylesheet" type="text/css" />
		<link href="assets/css/style_dark.css" rel="stylesheet" type="text/css" />
    <link href="assets/css/smart_request.css?v=<?= @filemtime(__DIR__ . '/assets/css/smart_request.css') ?>" rel="stylesheet" type="text/css" />

		<!-- SweetAlert2 -->
		<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
		<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

		<!-- Dropzone CSS -->
		<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/dropzone/5.9.3/dropzone.min.css">

		<script src="assets/js/modernizr.min.js"></script>

		<link rel="stylesheet" href="./plugins/croppie/croppie.css">
		<style type="text/css">
			:root {
				--primary: #007bff;
				--secondary: #6c757d;
				--success: #28a745;
				--danger: #dc3545;
				--warning: #ffc107;
				--info: #17a2b8;
				--light: #f8f9fa;
				--dark: #343a40;
				--white: #ffffff;
				--shadow-sm: 0 1px 3px rgba(0, 0, 0, 0.12);
				--shadow-md: 0 2px 8px rgba(0, 0, 0, 0.15);
				--shadow-lg: 0 4px 16px rgba(0, 0, 0, 0.2);
			}
			.card-box.social {
				box-shadow: 0 1px 2px rgba(0, 0, 0, 0.15);
				transition: all 0.2s ease-in-out;
				border-radius: 10px !important;
			}
			.card-box.social:hover {
				box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
				transform: scale(1.005);
				cursor: pointer;
			}
			
			/* Tilebox styling with colored left border */
			.card-box.tilebox-one {
				position: relative;
				border-left: 4px solid #e9ecef;
				transition: all 0.3s ease-in-out;
			}
			
			.card-box.tilebox-one:hover {
				box-shadow: 0 4px 16px rgba(0, 0, 0, 0.15);
				transform: translateY(-2px);
			}
			
			/* Border colors matching icon colors */
			.card-box.tilebox-one:has(.duotone-success) {
				border-left-color: var(--success);
			}
			
			.card-box.tilebox-one:has(.duotone-info) {
				border-left-color: var(--primary);
			}
			
			.card-box.tilebox-one:has(.duotone-danger) {
				border-left-color: var(--danger);
			}
			
			.card-box.tilebox-one:has(.duotone-dark) {
				border-left-color: var(--dark);
			}
			
			.card-box.tilebox-one:has(.duotone-secondary) {
				border-left-color: var(--secondary);
			}

			.card-box.tilebox-one:has(.duotone-warning) {
				border-left-color: var(--warning);
			}

			.summary-cards-row .card-box.tilebox-one {
				height: 80%;
			}
			/* More Actions Modal - grouped tile grid (groups built in includes/emp_top_info.php) */
			.more-actions-modal {
				--ma-bg: #f5f6fa; --ma-card: #fff; --ma-border: #e8ebf1; --ma-text: #1f2937; --ma-muted: #8a93a3; --ma-accent: #5b73e8;
			}
			html.app-dark .more-actions-modal {
				--ma-bg: #1b1f27; --ma-card: #242a35; --ma-border: #323a48; --ma-text: #e5e7eb; --ma-muted: #9aa3b2; --ma-accent: #8b9cf4;
			}
			.more-actions-modal .swal2-popup {
				border-radius: 18px;
				box-shadow: 0 24px 64px rgba(15, 23, 42, 0.22);
				overflow: hidden;
				background: var(--ma-bg);
				padding: 0 !important;
			}
			.more-actions-modal .swal2-title {
				margin: 0;
				padding: 0;
				text-align: start;
				background: var(--ma-card);
				border-bottom: 1px solid var(--ma-border);
			}
			.more-actions-modal .ma-head { padding: 18px 56px 14px 20px; }
			.more-actions-modal .ma-head h2 { margin: 0; font-size: 18px; font-weight: 700; color: var(--ma-text); }
			.more-actions-modal .ma-head p { margin: 3px 0 0; font-size: 12.5px; font-weight: 500; color: var(--ma-muted); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
			.more-actions-modal .ma-search { position: relative; margin: 12px 0 0; }
			.more-actions-modal .ma-search i { position: absolute; inset-inline-start: 12px; top: 50%; transform: translateY(-50%); color: var(--ma-muted); font-size: 13px; }
			.more-actions-modal .ma-search input {
				width: 100%; height: 38px; padding: 0 12px 0 34px; border-radius: 10px; font-size: 13.5px; font-weight: 500;
				border: 1px solid var(--ma-border); background: var(--ma-bg); color: var(--ma-text); outline: 0;
			}
			[dir="rtl"] .more-actions-modal .ma-search input { padding: 0 34px 0 12px; }
			.more-actions-modal .ma-search input:focus { border-color: var(--ma-accent); box-shadow: 0 0 0 3px rgba(91, 115, 232, 0.15); }
			.more-actions-modal .swal2-html-container { margin: 0 !important; padding: 0 !important; overflow: visible; text-align: start; }

			.more-actions-modal .menu-items-container {
				padding: 14px 16px 18px;
				max-height: 62vh;
				overflow-y: auto;
				background: var(--ma-bg);
			}
			.more-actions-modal .ma-group + .ma-group { margin-top: 16px; }
			.more-actions-modal .ma-group-title {
				display: flex; align-items: center; gap: 7px; margin: 0 2px 8px;
				font-size: 11px; font-weight: 700; letter-spacing: .6px; text-transform: uppercase; color: var(--ma-muted);
			}
			.more-actions-modal .ma-group-title i { font-size: 11px; }
			.more-actions-modal .ma-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 8px; }
			@media (max-width: 520px) { .more-actions-modal .ma-grid { grid-template-columns: 1fr; } }

			.more-actions-modal .menu-item {
				display: flex !important;
				align-items: center;
				gap: 11px;
				min-height: 56px;
				padding: 10px 12px !important;
				margin: 0 !important;
				cursor: pointer !important;
				border: 1px solid var(--ma-border);
				border-radius: 12px;
				background: var(--ma-card);
				font-weight: 600;
				user-select: none;
				transition: border-color .15s ease, box-shadow .15s ease, transform .15s ease;
			}
			.more-actions-modal .menu-item:hover {
				border-color: currentColor;
				box-shadow: 0 6px 16px rgba(15, 23, 42, 0.08);
				transform: translateY(-1px);
			}
			.more-actions-modal .menu-item:active { transform: scale(0.98); }
			.more-actions-modal .menu-item:focus-visible { outline: 2px solid var(--ma-accent); outline-offset: 2px; }
			.more-actions-modal .menu-item i {
				flex-shrink: 0;
				width: 36px; height: 36px; border-radius: 10px;
				display: flex; align-items: center; justify-content: center;
				font-size: 15px;
				background-color: rgba(108, 117, 125, 0.14);
			}
			.more-actions-modal .menu-item span {
				flex: 1; min-width: 0;
				font-size: 13.5px; line-height: 1.3;
				color: var(--ma-text);
				display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;
			}
			.more-actions-modal .menu-item.ma-hidden, .more-actions-modal .ma-group.ma-hidden { display: none !important; }

			/* Icon badge tinted per action color, label stays neutral */
			.more-actions-modal .menu-item.text-primary { color: var(--primary) !important; }
			.more-actions-modal .menu-item.text-primary i { background-color: rgba(91, 115, 232, 0.14); }
			.more-actions-modal .menu-item.text-warning { color: var(--warning) !important; }
			.more-actions-modal .menu-item.text-warning i { background-color: rgba(241, 180, 76, 0.16); }
			.more-actions-modal .menu-item.text-info { color: var(--info) !important; }
			.more-actions-modal .menu-item.text-info i { background-color: rgba(80, 165, 241, 0.14); }
			.more-actions-modal .menu-item.text-success { color: var(--success) !important; }
			.more-actions-modal .menu-item.text-success i { background-color: rgba(40, 167, 69, 0.14); }
			.more-actions-modal .menu-item.text-danger { color: var(--danger) !important; }
			.more-actions-modal .menu-item.text-danger i { background-color: rgba(244, 106, 106, 0.14); }
			.more-actions-modal .menu-item.text-secondary { color: var(--secondary) !important; }
			.more-actions-modal .menu-item.text-secondary i { background-color: rgba(108, 117, 125, 0.14); }
			.more-actions-modal .menu-item.text-dark { color: var(--ma-text) !important; }
			.more-actions-modal .menu-item.text-dark i { background-color: rgba(108, 117, 125, 0.16); }
			.more-actions-modal .menu-item.text-teal { color: #0d9488 !important; }
			.more-actions-modal .menu-item.text-teal i { background-color: rgba(13, 148, 136, 0.14); }

			/* Exit & Separation: quiet red frame so destructive actions stand apart */
			.more-actions-modal .ma-group-exit .menu-item { border-style: dashed; border-color: rgba(244, 106, 106, 0.45); }
			.more-actions-modal .ma-group-exit .ma-group-title { color: #e05858; }

			.more-actions-modal .ma-empty, .more-actions-modal .ma-noresult { padding: 28px 12px; text-align: center; color: var(--ma-muted); }
			.more-actions-modal .ma-empty i, .more-actions-modal .ma-noresult i { font-size: 26px; margin-bottom: 8px; display: block; opacity: .7; }
			.more-actions-modal .ma-empty p, .more-actions-modal .ma-noresult p { margin: 0; font-size: 13.5px; }

			.more-actions-modal .swal2-close { font-size: 1.5rem; color: var(--ma-muted); width: 36px; height: 36px; top: 10px; }
			.more-actions-modal .swal2-close:hover { color: #f46a6a; }

			/* Resignation Wizard CSS */
			.resignation-wizard .resignation-popup {
				border-radius: 10px;
				box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15);
			}

			.resignation-wizard .swal2-title {
				font-size: 1.6rem;
				font-weight: 700;
				color: #2c3e50;
				padding: 1.5rem;
			}

			.resignation-step1 .form-control {
				border: 1px solid #bdc3c7;
				transition: all 0.3s ease;
			}

			.resignation-step1 .form-control:focus {
				border-color: #e74c3c;
				box-shadow: 0 0 0 0.2rem rgba(231, 76, 60, 0.25);
			}

			.resignation-step1 .form-label {
				font-weight: 600;
				color: #2c3e50;
				margin-bottom: 8px;
			}

			.resignation-step1 .alert {
				margin-top: 20px;
				border-radius: 5px;
			}

			/* Exit Interview Wizard CSS */
			.exit-interview-wizard .exit-interview-popup {
				border-radius: 10px;
				box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15);
			}

			.exit-interview-step2 {
				scrollbar-width: thin;
				scrollbar-color: #bdc3c7 #ecf0f1;
			}

			.exit-interview-step2::-webkit-scrollbar {
				width: 6px;
			}

			.exit-interview-step2::-webkit-scrollbar-track {
				background: #ecf0f1;
			}

			.exit-interview-step2::-webkit-scrollbar-thumb {
				background: #bdc3c7;
				border-radius: 3px;
			}

			.exit-interview-step2 .form-group {
				margin-bottom: 20px;
			}

			.exit-interview-step2 .form-label {
				font-weight: 600;
				color: #2c3e50;
				display: flex;
				align-items: center;
				margin-bottom: 10px;
			}

			.exit-interview-step2 .form-label span {
				display: inline-flex;
				align-items: center;
				justify-content: center;
				background: linear-gradient(135deg, #3498db 0%, #2980b9 100%);
				color: white;
				width: 28px;
				height: 28px;
				border-radius: 50%;
				margin-right: 8px;
				font-size: 12px;
				font-weight: bold;
				flex-shrink: 0;
			}

			.exit-interview-step2 .form-control {
				border: 1px solid #bdc3c7;
				border-radius: 5px;
				padding: 12px;
				font-size: 14px;
				transition: all 0.3s ease;
				resize: vertical;
			}

			.exit-interview-step2 .form-control:focus {
				border-color: #e74c3c;
				box-shadow: 0 0 0 0.2rem rgba(231, 76, 60, 0.25);
			}

			.exit-interview-step2 .form-control.is-invalid {
				border-color: #e74c3c;
				background-color: #fff5f5;
			}

			.exit-interview-step2 .form-text {
				color: #7f8c8d;
				font-size: 12px;
			}

			.exit-interview-step2 .alert {
				margin-top: 20px;
				padding: 15px;
				border-radius: 5px;
				border-left: 4px solid #f39c12;
			}

			/* Wizard step styling */
			.swal2-actions button {
				border-radius: 5px;
				font-weight: 600;
				padding: 10px 30px;
			}

			/* ===== PROFILE TAB (cards: white header + colored icon badge, label icons) ===== */
			.profile-content-wrapper {
				column-count: 2;
				column-gap: 18px;
				padding: 4px 0 0;
				background: transparent;
			}
			.profile-section {
				--ps: #2563eb;
				background: #fff;
				border-radius: 16px;
				overflow: hidden;
				border: 1px solid rgba(15, 23, 42, .07);
				box-shadow: 0 1px 2px rgba(15, 23, 42, .04), 0 8px 24px rgba(15, 23, 42, .06);
				position: relative;
				break-inside: avoid;
				margin: 0 0 18px;
				display: inline-block;
				width: 100%;
			}
			.profile-section::before { content: ""; position: absolute; inset: 0 0 auto 0; height: 4px; background: linear-gradient(90deg, var(--ps), color-mix(in srgb, var(--ps) 40%, white)); }
			.profile-section.ps-personal { --ps: #2563eb; }
			.profile-section.ps-employment { --ps: #7c3aed; }
			.profile-section.ps-contact { --ps: #16a34a; }
			.profile-section.ps-banking { --ps: #d97706; }
			.profile-section.ps-d365 { --ps: #0078d4; }
			.profile-section.ps-insurance { --ps: #dc2626; }
			.profile-section.ps-car { --ps: #475569; }
			.profile-section.ps-additional { --ps: #4f46e5; }
			.profile-section.ps-medical { --ps: #dc2626; }
			.profile-section.ps-income { --ps: #16a34a; }
			/* Card header extras (Additional Information tab) */
			.profile-section-header .psh-sub { font-size: 12px; font-weight: 600; color: #64748b; }
			.profile-section-header .psh-btn {
				margin-inline-start: auto; display: inline-flex; align-items: center; gap: 6px; height: 34px; padding: 0 14px;
				border-radius: 10px; border: 0; font-size: 13px; font-weight: 700; color: #fff; cursor: pointer;
				background: linear-gradient(135deg, var(--ps), color-mix(in srgb, var(--ps) 65%, #0f172a));
				box-shadow: 0 4px 10px color-mix(in srgb, var(--ps) 25%, transparent);
			}
			.profile-section-header .psh-btn:hover { filter: brightness(1.08); }
			/* Dynamics 365 header button - Microsoft Dynamics blue gradient */
			.profile-section-header .psh-btn-d365 { background: linear-gradient(135deg, #0078d4 0%, #0b53ce 55%, #002050 100%); box-shadow: 0 4px 12px rgba(0, 120, 212, .3); }
			.profile-section-header .psh-btn-d365 .psh-arrow { font-size: 11px; opacity: .8; transition: transform .15s; }
			.profile-section-header .psh-btn-d365:hover .psh-arrow { transform: translateX(3px); }
			[dir="rtl"] .profile-section-header .psh-btn-d365 .psh-arrow { transform: scaleX(-1); }
			[dir="rtl"] .profile-section-header .psh-btn-d365:hover .psh-arrow { transform: scaleX(-1) translateX(3px); }
			.profile-section .psh-subtitle { display: flex; align-items: center; gap: 8px; margin: 16px 4px 10px; font-size: 13.5px; font-weight: 800; color: #0f172a; }
			.profile-section .psh-subtitle i { color: var(--ps); }
			.profile-section .psh-empty { margin: 4px; padding: 22px; text-align: center; color: #64748b; border: 1px dashed #d8dde6; border-radius: 12px; }
			.profile-section .psh-empty i { margin-inline-end: 6px; color: var(--ps); }
			.profile-section .sr-table-wrap { border-radius: 12px; overflow: auto; border: 1px solid #eef1f5; }
			#additionalinfo1 .profile-section { margin-bottom: 18px; }
			/* More Info tab: Additional Information on the left; Medical Insurance with Other Income
			   right under it on the right (grid, so the order never depends on column balancing) */
			#additionalinfo1 .profile-content-wrapper:has(.ps-additional) {
				column-count: initial; display: grid; grid-template-columns: repeat(2, minmax(0, 1fr));
				grid-template-rows: auto 1fr; column-gap: 18px; align-items: start;
			}
			#additionalinfo1 .profile-content-wrapper:has(.ps-additional) .ps-additional { grid-column: 1; grid-row: 1 / span 2; }
			#additionalinfo1 .profile-content-wrapper:has(.ps-additional) .ps-medical { grid-column: 2; grid-row: 1; }
			#additionalinfo1 .profile-content-wrapper:has(.ps-additional) .ps-income { grid-column: 2; grid-row: 2; }
			@media (max-width: 1199px) {
				#additionalinfo1 .profile-content-wrapper:has(.ps-additional) { display: block; }
			}

			/* Vacation balance summary (Vacations tab) */
			.vac-sum { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 12px; margin-bottom: 12px; }
			.vac-sum-tile { --c: #2563eb; display: flex; align-items: center; gap: 14px; padding: 16px; border-radius: 14px; background: #fff; border: 1px solid #e8ebf1; box-shadow: 0 1px 2px rgba(15,23,42,.04), 0 6px 18px rgba(15,23,42,.05); position: relative; overflow: hidden; }
			.vac-sum-tile::before { content: ""; position: absolute; inset: 0 auto 0 0; width: 4px; background: var(--c); }
			[dir="rtl"] .vac-sum-tile::before { inset: 0 0 0 auto; }
			.vac-sum-tile > i { flex: none; width: 46px; height: 46px; border-radius: 13px; display: inline-flex; align-items: center; justify-content: center; font-size: 19px; color: var(--c); background: color-mix(in srgb, var(--c) 13%, white); }
			.vac-sum-tile small { display: block; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .5px; color: #64748b; }
			.vac-sum-tile b { display: flex; align-items: flex-end; margin-top: 2px; font-size: 24px; font-weight: 800; line-height: 1.2; color: #0f172a; font-variant-numeric: tabular-nums; }
			.vac-sum-tile b .vac-unit { font-size: 12px; font-weight: 600; color: #64748b; margin-inline-start: 5px; padding-bottom: 4px; line-height: 1; } /* class only - the number odometer (sr-odo) wraps digits in spans */
			.vac-sum-bar { margin-bottom: 20px; padding: 12px 16px; border-radius: 14px; background: #fff; border: 1px solid #e8ebf1; }
			.vac-sum-bar-head { display: flex; align-items: center; justify-content: space-between; gap: 12px; font-size: 12px; font-weight: 700; color: #64748b; margin-bottom: 8px; line-height: 1.2; }
			.vac-sum-bar-head > span { display: inline-flex; align-items: center; gap: 6px; }
			.vac-sum-used .vac-pct { display: inline-flex; align-items: center; padding: 2px 8px; border-radius: 999px; font-size: 12px; font-weight: 800; color: #c2410c; background: #fff1e6; }
			.vac-sum-bar-track { height: 10px; border-radius: 999px; background: #eef1f5; overflow: hidden; }
			.vac-sum-bar-track span { display: block; height: 100%; border-radius: 999px; background: linear-gradient(90deg, #f59e0b, #ea580c); }
			.vac-sum-empty { margin-bottom: 20px; padding: 20px; text-align: center; color: #64748b; border: 1px dashed #d8dde6; border-radius: 14px; }
			@media (max-width: 767px) { .vac-sum { grid-template-columns: 1fr; } }
			html.app-dark .vac-sum-tile, html.app-dark .vac-sum-bar { background: #242a35; border-color: #323a48; }
			html.app-dark .vac-sum-tile b, html.app-dark .profile-section .psh-subtitle { color: #e5e7eb; }
			html.app-dark .vac-sum-used .vac-pct { color: #fdba74; background: rgba(234,88,12,.15); }

			.profile-section-header {
				display: flex; align-items: center; gap: 12px;
				padding: 18px 20px 12px;
				background: transparent; color: #0f172a;
				font-size: 15.5px; font-weight: 800;
			}
			.profile-section-header .psh-icon {
				flex: none; width: 42px; height: 42px; border-radius: 12px;
				display: inline-flex; align-items: center; justify-content: center; font-size: 17px; color: #fff;
				background: linear-gradient(135deg, var(--ps), color-mix(in srgb, var(--ps) 65%, #0f172a));
				box-shadow: 0 6px 14px color-mix(in srgb, var(--ps) 30%, transparent);
			}
			.profile-section-header a { color: var(--ps) !important; }

			.profile-section-body { padding: 0 12px 12px; }
			.profile-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 8px; }
			.profile-field {
				display: flex; flex-direction: column; gap: 6px;
				padding: 11px 12px;
				border: 0; border-radius: 12px;
				background: color-mix(in srgb, var(--ps) 4%, white);
				transition: background .15s ease;
				min-width: 0;
			}
			.profile-field:hover { background: color-mix(in srgb, var(--ps) 9%, white); }
			.profile-field.full-width { grid-column: 1 / -1; }

			.profile-field-label {
				display: flex; align-items: center; gap: 8px;
				font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .5px; color: #64748b;
			}
			.profile-field-label > i {
				flex: none; width: 24px; height: 24px; border-radius: 7px; font-size: 11px;
				display: inline-flex; align-items: center; justify-content: center;
				color: var(--ps); background: color-mix(in srgb, var(--ps) 14%, white);
			}
			.profile-field-value { font-size: 14px; font-weight: 700; color: #0f172a; word-break: break-word; }
			.profile-field-label:has(> i) + .profile-field-value { padding-inline-start: 32px; }

			.profile-field-value .copyToClipboard { cursor: pointer; padding: 1px 4px; border-radius: 5px; transition: all .2s; }
			.profile-field-value .copyToClipboard:hover { background: color-mix(in srgb, var(--ps) 14%, white); color: var(--ps); }
			.profile-field-value .fa-clipboard { color: #94a3b8; font-size: 12px; margin-inline-start: 6px; cursor: pointer; }
			.profile-field-value .fa-clipboard:hover { color: var(--ps); }

			/* Date chips (Gregorian / Hijri) */
			.date-batch-g, .date-batch-h {
				display: inline-flex; align-items: center; gap: 5px;
				padding: 4px 9px; margin: 2px 6px 2px 0;
				background: #fff; border: 1px solid #e2e8f0; border-radius: 8px;
				font-size: 12.5px; font-weight: 700; transition: border-color .15s ease;
			}
			[dir="rtl"] .date-batch-g, [dir="rtl"] .date-batch-h { margin: 2px 0 2px 6px; }
			.date-batch-g:hover, .date-batch-h:hover { border-color: var(--ps); }
			.date-batch-g::before { content: "\f073"; font-family: "Font Awesome 7 Pro", "Font Awesome 6 Pro", "Font Awesome 5 Free", "FontAwesome"; font-weight: 900; font-size: 11px; color: var(--ps); margin: 0; }
			.date-batch-h::before { content: "\f186"; font-family: "Font Awesome 7 Pro", "Font Awesome 6 Pro", "Font Awesome 5 Free", "FontAwesome"; font-weight: 900; font-size: 11px; color: #a16207; margin: 0; }
			.date-batch-g::after, .date-batch-h::after {
				content: attr(data-prefix);
				font-size: 9.5px; font-weight: 700; text-transform: uppercase; letter-spacing: .4px;
				color: #64748b; background: #f1f5f9; padding: 1px 5px; border-radius: 4px; margin: 0;
			}

			html.app-dark .profile-section { background: #242a35; border-color: #323a48; }
			html.app-dark .profile-section-header, html.app-dark .profile-field-value { color: #e5e7eb; }
			html.app-dark .profile-field { background: color-mix(in srgb, var(--ps) 8%, #242a35); }
			html.app-dark .date-batch-g, html.app-dark .date-batch-h { background: #1b1f27; border-color: #323a48; }

			@media (max-width: 1199px) {
				.profile-content-wrapper { column-count: 1; }
			}
			@media (max-width: 575px) {
				.profile-grid { grid-template-columns: 1fr; }
				.profile-field-label:has(> i) + .profile-field-value { padding-inline-start: 0; }
			}

			/* Employee Detail Tabs - New GUI (sr-* tokens). Geometry unchanged: wrapping bar,
			   items flex 1 1 auto, 10px/16px links; only the look moved onto the design tokens. */
			.emp-tabs {
				display: flex;
				flex-wrap: wrap;
				gap: 4px;
				list-style: none;
				margin: 0;
				padding: 6px 6px 0 6px;
				background: var(--sr-surface-3);
				border: 1px solid var(--sr-border);
				border-bottom: none;
				border-radius: var(--sr-radius) var(--sr-radius) 0 0;
			}

			.emp-tabs .nav-item {
				flex: 1 1 auto;
			}

			.emp-tabs .nav-link {
				display: flex;
				align-items: center;
				justify-content: center;
				gap: 8px;
				white-space: nowrap;
				padding: 10px 16px;
				margin-bottom: 6px;
				border-radius: 10px;
				font-weight: 600;
				font-size: 0.875rem;
				color: var(--sr-muted);
				background: transparent;
				border: 1px solid transparent;
				transition: color .15s ease, background-color .15s ease, box-shadow .15s ease, border-color .15s ease;
			}

			.emp-tabs .nav-link i {
				font-size: 1rem;
				line-height: 1;
				margin: 0 !important;
				opacity: .85;
			}

			.emp-tabs .nav-link:hover {
				color: var(--sr-text);
				background: var(--sr-surface-2);
			}

			.emp-tabs .nav-link.active {
				color: var(--sr-accent-strong);
				background: var(--sr-surface);
				border-color: var(--sr-border);
				box-shadow: 0 1px 2px rgba(15, 23, 42, .06), inset 0 -2px 0 var(--sr-accent);
			}

			.emp-tabs .nav-link.active i {
				color: var(--sr-accent);
				opacity: 1;
			}

			.emp-tabs .nav-link .badge-count {
				display: inline-flex;
				align-items: center;
				justify-content: center;
				min-width: 20px;
				height: 20px;
				padding: 0 6px;
				border-radius: 999px;
				font-size: 0.7rem;
				font-weight: 700;
				background: var(--tone-amber-bg);
				color: var(--tone-amber-fg);
				border: 1px solid var(--tone-amber-bd);
			}

			.emp-tabs .nav-link.active .badge-count {
				background: var(--sr-accent);
				color: #fff;
				border-color: var(--sr-accent);
			}

			@media (max-width: 768px) {
				.emp-tabs {
					overflow-x: auto;
					flex-wrap: nowrap;
					-webkit-overflow-scrolling: touch;
				}

				.emp-tabs .nav-item {
					flex: 0 0 auto;
				}
			}

			.tab-content {
				margin: 0 0 1.5rem 0;
				border: 1px solid var(--sr-border);
				border-top: none;
				border-radius: 0 0 var(--sr-radius) var(--sr-radius);
				background: var(--sr-surface);
				box-shadow: var(--sr-shadow);
				animation: emp-tab-glow-in .2s ease;
			}

			.tab-content > .tab-pane {
				padding: 20px;
			}

			@keyframes emp-tab-glow-in {
				from { opacity: .7; }
				to { opacity: 1; }
			}

			/* ---------- Tab panes: old markup re-mapped onto the New GUI ---------- */
			.tab-content.sr-page .header-title { color: var(--sr-text); font-size: 15px; font-weight: 700; text-transform: none; letter-spacing: 0; }
			.tab-content.sr-page .card-box,
			.tab-content.sr-page .card {
				background: var(--sr-surface);
				border: 1px solid var(--sr-border) !important;
				border-radius: var(--sr-radius);
				box-shadow: none;
				overflow: hidden;
			}
			.tab-content.sr-page .card-box { padding: 18px; margin-bottom: 20px; }
			/* Attendance tab (#attendance) */
			.sr-page .att-tab-head { display: flex; flex-wrap: wrap; align-items: flex-start; justify-content: space-between; gap: 12px; margin-bottom: 14px; }
			.sr-page .att-tab-head .header-title { display: flex; align-items: center; gap: 8px; }
			.sr-page .att-tab-head .header-title i { color: var(--sr-accent); font-size: 18px; }
			.sr-page .att-tab-sub { margin: 0; font-size: 12.5px; color: var(--sr-muted); }
			.sr-page .att-tab-actions { display: flex; flex-wrap: wrap; align-items: center; gap: 10px; }
			.sr-page .att-toolbar-end { display: flex; flex-wrap: wrap; align-items: center; gap: 10px; }
			.sr-page .att-tab .att-tiles { margin-bottom: 14px; }

			.sr-page .att-toolbar { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 10px; margin-bottom: 10px; }
			.sr-page .att-export .dt-buttons { display: inline-flex; flex-wrap: wrap; gap: 6px; float: none; margin: 0; }
			.sr-page .att-export .dt-button, .sr-page .att-export .btn {
				display: inline-flex; align-items: center; gap: 6px; height: 36px; padding: 0 12px !important; margin: 0 !important;
				border-radius: 10px !important; border: 1px solid var(--sr-border-strong) !important; background: var(--sr-surface) !important;
				color: var(--sr-text-2) !important; font-size: 12.5px; font-weight: 600; box-shadow: none !important; background-image: none !important;
			}
			.sr-page .att-export .dt-button:hover, .sr-page .att-export .btn:hover { border-color: var(--sr-accent) !important; color: var(--sr-accent-strong) !important; background: var(--sr-accent-soft) !important; }
			.sr-page .att-export .buttons-excel i { color: #16a34a; }
			.sr-page .att-export .buttons-pdf i { color: #dc2626; }

			.sr-page #attendance_tbl td { vertical-align: middle; }
			.sr-page .att-weekend small { color: var(--tone-amber-fg); }
			.sr-page .att-time { display: inline-flex; align-items: center; gap: 6px; padding: 3px 9px; border-radius: 8px; font-weight: 600; font-variant-numeric: tabular-nums; color: var(--sr-text); background: var(--sr-surface-2); }
			.sr-page .att-time i { font-size: 13px; }
			.sr-page .att-time.in i { color: #16a34a; }
			.sr-page .att-time.out i { color: #dc2626; }
			.sr-page .att-hours { font-weight: 700; font-variant-numeric: tabular-nums; color: var(--sr-text); }
			.sr-page .att-mins { display: inline-block; padding: 2px 8px; border-radius: 999px; font-size: 12px; font-weight: 700; font-variant-numeric: tabular-nums; }
			.sr-page .att-mins.tone-amber { background: var(--tone-amber-bg); color: var(--tone-amber-fg); }
			.sr-page .att-mins.tone-sky { background: var(--tone-sky-bg); color: var(--tone-sky-fg); }
			.sr-page .att-muted { color: var(--sr-muted); }
			.sr-page #attendance_tbl .att-note { display: inline-block; max-width: 220px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; vertical-align: middle; }
			.sr-page .att-dt-bottom { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 10px; padding-top: 12px; }
			.sr-page .att-dt-bottom .dataTables_info { padding: 0; font-size: 12.5px; color: var(--sr-muted); }
			.sr-page .att-dt-bottom .dataTables_paginate { padding: 0; }
			.tab-content.sr-page .card > .card-header {
				display: flex; align-items: center; gap: 8px;
				background: var(--sr-surface-2) !important;
				color: var(--sr-text) !important;
				border-bottom: 1px solid var(--sr-border);
				font-size: 14px; font-weight: 700; padding: 12px 18px;
			}
			.tab-content.sr-page .card > .card-header h5 { font-size: 14px; font-weight: 700; color: var(--sr-text); }
			.tab-content.sr-page .card > .card-header i { color: var(--sr-accent); }
			.tab-content.sr-page .card > .card-body .text-muted.text-uppercase { font-size: 11px; font-weight: 700; letter-spacing: .5px; color: var(--sr-muted) !important; }
			.tab-content.sr-page .table-responsive { border: 1px solid var(--sr-border); border-radius: var(--sr-radius); padding: 6px; margin-bottom: 20px; }
			.tab-content.sr-page .card .table-responsive { border: 0; padding: 0; margin: 0; }
			.tab-content.sr-page .table-responsive > .mb-3:first-child { padding: 8px 8px 0; }
			.tab-content.sr-page .ve-head-tools { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; justify-content: flex-end; }
			.tab-content.sr-page .ve-head-tools .sr-search { flex: 0 1 260px; }

			/* tables */
			.tab-content.sr-page table.sr-table tbody tr { cursor: default; }
			.tab-content.sr-page table.sr-table tbody td { white-space: nowrap; }
			/* Loans tab - active loan summary */
			.tab-content.sr-page .ve-loan { margin-bottom: 20px; }
			.tab-content.sr-page .ve-loan .sr-card-head { display: flex; justify-content: space-between; align-items: center; gap: 12px; flex-wrap: wrap; }
			.tab-content.sr-page .ve-loan .sr-card-title i { color: var(--sr-accent); }
			.tab-content.sr-page .ve-loan .sr-card-sub { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; margin-top: 4px; }
			.ve-loan-stats { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 12px; }
			@media (max-width: 767px) { .ve-loan-stats { grid-template-columns: 1fr; } }
			.ve-loan-stat { padding: 14px 16px; border-radius: 12px; background: var(--sr-surface-2); border: 1px solid var(--sr-border); }
			.ve-loan-stat-label { display: flex; align-items: center; gap: 7px; font-size: 12px; font-weight: 600; color: var(--sr-muted); }
			.ve-loan-stat-label .sr-dot { width: 8px; height: 8px; border-radius: 50%; }
			.ve-loan-stat-value { display: block; margin-top: 6px; font-size: 22px; font-weight: 700; color: var(--sr-text); font-variant-numeric: tabular-nums; line-height: 1.15; }
			.ve-loan-stat.is-paid { background: var(--tone-green-bg); border-color: var(--tone-green-bd); }
			.ve-loan-stat.is-paid .sr-dot { background: var(--tone-green-fg); }
			.ve-loan-stat.is-paid .ve-loan-stat-value { color: var(--tone-green-fg); }
			.ve-loan-stat.is-remaining { background: var(--tone-red-bg); border-color: var(--tone-red-bd); }
			.ve-loan-stat.is-remaining .sr-dot { background: var(--tone-red-fg); }
			.ve-loan-stat.is-remaining .ve-loan-stat-value { color: var(--tone-red-fg); }
			.ve-loan-progress { height: 8px; border-radius: 4px; background: var(--sr-surface-3); overflow: hidden; margin: 12px 0 18px; }
			.ve-loan-progress > div { height: 100%; background: var(--tone-green-fg); border-radius: 4px; }
			.ve-loan-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 16px; }
			@media (max-width: 991px) { .ve-loan-grid { grid-template-columns: 1fr; } }
			.ve-loan-box { border: 1px solid var(--sr-border); border-radius: 12px; padding: 12px 16px; }
			.ve-loan-box-title { font-size: 11px; font-weight: 700; letter-spacing: .5px; text-transform: uppercase; color: var(--sr-muted); margin-bottom: 4px; }
			.ve-loan-box-title i { color: var(--sr-accent); }
			.ve-loan-box .sr-kv dd { display: flex; align-items: center; justify-content: flex-end; gap: 4px; }
			.ve-loan-amber { color: var(--tone-amber-fg) !important; }
			.ve-loan-select { width: auto; min-width: 170px; }
			.ve-loan-pay { width: 100%; justify-content: center; margin-top: 12px; }
			.tab-content.sr-page table.sr-table td .btn-sm,
			.tab-content.sr-page table.sr-table td .btn-xs {
				display: inline-flex; align-items: center; justify-content: center; gap: 4px;
				min-width: 30px; height: 30px; padding: 0 9px; border-radius: 8px; font-size: 12px; line-height: 1; box-shadow: none !important;
			}
			.tab-content.sr-page table.sr-table td .btn-group { gap: 4px; }
			.tab-content.sr-page table.sr-table td .btn-group > .btn { border-radius: 8px !important; }

			/* bootstrap badges -> sr pills */
			.tab-content.sr-page .sr-table .badge,
			.tab-content.sr-page .card-body .badge {
				display: inline-flex; align-items: center; gap: 5px;
				padding: 3px 9px; border-radius: 999px; border: 1px solid var(--tone-slate-bd);
				font-size: 11.5px; font-weight: 600; line-height: 1.4;
				background: var(--tone-slate-bg); color: var(--tone-slate-fg);
			}
			.tab-content.sr-page .badge-success { background: var(--tone-green-bg) !important; color: var(--tone-green-fg) !important; border-color: var(--tone-green-bd) !important; }
			.tab-content.sr-page .badge-danger { background: var(--tone-red-bg) !important; color: var(--tone-red-fg) !important; border-color: var(--tone-red-bd) !important; }
			.tab-content.sr-page .badge-warning { background: var(--tone-amber-bg) !important; color: var(--tone-amber-fg) !important; border-color: var(--tone-amber-bd) !important; }
			.tab-content.sr-page .badge-info { background: var(--tone-sky-bg) !important; color: var(--tone-sky-fg) !important; border-color: var(--tone-sky-bd) !important; }
			.tab-content.sr-page .badge-primary { background: var(--tone-indigo-bg) !important; color: var(--tone-indigo-fg) !important; border-color: var(--tone-indigo-bd) !important; }
			.tab-content.sr-page .badge-secondary,
			.tab-content.sr-page .badge-dark,
			.tab-content.sr-page .badge-light { background: var(--tone-slate-bg) !important; color: var(--tone-slate-fg) !important; border-color: var(--tone-slate-bd) !important; }

			/* DataTables controls */
			.tab-content.sr-page .dataTables_wrapper .dataTables_length,
			.tab-content.sr-page .dataTables_wrapper .dataTables_filter { padding: 6px 4px 10px; font-size: 12px; color: var(--sr-muted); }
			.tab-content.sr-page .dataTables_wrapper .dataTables_length select,
			.tab-content.sr-page .dataTables_wrapper .dataTables_filter input {
				height: 34px; border-radius: 10px; border: 1px solid var(--sr-border-strong);
				background: var(--sr-surface); color: var(--sr-text); font-size: 13px; box-shadow: none; outline: none;
			}
			.tab-content.sr-page .dataTables_wrapper .dataTables_filter input { min-width: 220px; padding: 0 12px; margin-inline-start: 6px; }
			.tab-content.sr-page .dataTables_wrapper .dataTables_filter input:focus,
			.tab-content.sr-page .dataTables_wrapper .dataTables_length select:focus { border-color: var(--sr-accent); box-shadow: 0 0 0 3px var(--sr-accent-soft); }
			/* buttons: centred content; 3+ buttons = one joined button group, 1-2 = separate buttons */
			.tab-content.sr-page .dataTables_wrapper .dt-buttons { display: inline-flex; flex-wrap: wrap; gap: 6px; margin: 0 0 10px; float: none; }
			.tab-content.sr-page .dataTables_wrapper .dt-buttons .btn {
				display: inline-flex; align-items: center; justify-content: center; gap: 6px;
				height: 34px; padding: 0 14px !important; margin: 0 !important; line-height: 1 !important;
				border-radius: 10px !important; background: var(--sr-surface) !important; border: 1px solid var(--sr-border-strong) !important;
				color: var(--sr-text-2) !important; font-size: 12.5px; font-weight: 600; box-shadow: none !important; white-space: nowrap;
			}
			.tab-content.sr-page .dataTables_wrapper .dt-buttons .btn > span { display: inline-flex; align-items: center; gap: 6px; }
			.tab-content.sr-page .dataTables_wrapper .dt-buttons .btn i { font-size: 15px; line-height: 1; color: var(--sr-muted); }
			.tab-content.sr-page .dataTables_wrapper .dt-buttons .btn:hover { background: var(--sr-surface-2) !important; color: var(--sr-accent-strong) !important; }
			.tab-content.sr-page .dataTables_wrapper .dt-buttons .btn:hover i { color: var(--sr-accent); }
			.tab-content.sr-page .dataTables_wrapper .dt-buttons:has(> .btn:nth-child(3)) { gap: 0; flex-wrap: nowrap; max-width: 100%; overflow-x: auto; }
			.tab-content.sr-page .dataTables_wrapper .dt-buttons:has(> .btn:nth-child(3)) > .btn { border-radius: 0 !important; }
			.tab-content.sr-page .dataTables_wrapper .dt-buttons:has(> .btn:nth-child(3)) > .btn + .btn { margin-inline-start: -1px !important; }
			.tab-content.sr-page .dataTables_wrapper .dt-buttons:has(> .btn:nth-child(3)) > .btn:first-child { border-start-start-radius: 10px !important; border-end-start-radius: 10px !important; }
			.tab-content.sr-page .dataTables_wrapper .dt-buttons:has(> .btn:nth-child(3)) > .btn:last-child { border-start-end-radius: 10px !important; border-end-end-radius: 10px !important; }
			.tab-content.sr-page .dataTables_wrapper .dt-buttons .btn:hover { position: relative; z-index: 1; border-color: var(--sr-accent) !important; }

			/* row action buttons -> soft New GUI buttons (solid bootstrap colours re-mapped onto tones) */
			.tab-content.sr-page table.sr-table td .btn { border: 1px solid transparent !important; font-weight: 600; transition: background .12s, color .12s, border-color .12s; }
			.tab-content.sr-page table.sr-table td .btn-primary { background: var(--tone-indigo-bg) !important; color: var(--tone-indigo-fg) !important; border-color: var(--tone-indigo-bd) !important; }
			.tab-content.sr-page table.sr-table td .btn-info { background: var(--tone-sky-bg) !important; color: var(--tone-sky-fg) !important; border-color: var(--tone-sky-bd) !important; }
			.tab-content.sr-page table.sr-table td .btn-success { background: var(--tone-green-bg) !important; color: var(--tone-green-fg) !important; border-color: var(--tone-green-bd) !important; }
			.tab-content.sr-page table.sr-table td .btn-warning { background: var(--tone-amber-bg) !important; color: var(--tone-amber-fg) !important; border-color: var(--tone-amber-bd) !important; }
			.tab-content.sr-page table.sr-table td .btn-danger { background: var(--tone-red-bg) !important; color: var(--tone-red-fg) !important; border-color: var(--tone-red-bd) !important; }
			.tab-content.sr-page table.sr-table td .btn-secondary,
			.tab-content.sr-page table.sr-table td .btn-light,
			.tab-content.sr-page table.sr-table td .btn-dark,
			.tab-content.sr-page table.sr-table td [class*="btn-outline-"] { background: var(--sr-surface) !important; color: var(--sr-text-2) !important; border-color: var(--sr-border-strong) !important; }
			.tab-content.sr-page table.sr-table td .btn-primary:hover { background: var(--sr-accent) !important; border-color: var(--sr-accent) !important; color: #fff !important; }
			.tab-content.sr-page table.sr-table td .btn-info:hover { background: #0284c7 !important; border-color: #0284c7 !important; color: #fff !important; }
			.tab-content.sr-page table.sr-table td .btn-success:hover { background: #16a34a !important; border-color: #16a34a !important; color: #fff !important; }
			.tab-content.sr-page table.sr-table td .btn-warning:hover { background: #f59e0b !important; border-color: #f59e0b !important; color: #fff !important; }
			.tab-content.sr-page table.sr-table td .btn-danger:hover { background: #dc2626 !important; border-color: #dc2626 !important; color: #fff !important; }
			.tab-content.sr-page table.sr-table td .btn-secondary:hover,
			.tab-content.sr-page table.sr-table td .btn-light:hover,
			.tab-content.sr-page table.sr-table td .btn-dark:hover,
			.tab-content.sr-page table.sr-table td [class*="btn-outline-"]:hover { border-color: var(--sr-accent) !important; color: var(--sr-accent-strong) !important; }
			.tab-content.sr-page table.sr-table td .btn i { margin: 0 !important; }

			/* pagination: compact chevrons */
			.tab-content.sr-page .dataTables_wrapper .pagination .page-link { display: inline-flex; align-items: center; justify-content: center; height: 32px; padding: 0 8px; }
			.tab-content.sr-page .dataTables_wrapper .pagination .page-link i { font-size: 16px; line-height: 1; }

			/* row action buttons: 2+ = joined group */
			.tab-content.sr-page table.sr-table td .btn-group:has(> .btn:nth-child(2)) { gap: 0; }
			.tab-content.sr-page table.sr-table td .btn-group:has(> .btn:nth-child(2)) > .btn { border-radius: 0 !important; }
			.tab-content.sr-page table.sr-table td .btn-group:has(> .btn:nth-child(2)) > .btn:first-child { border-start-start-radius: 8px !important; border-end-start-radius: 8px !important; }
			.tab-content.sr-page table.sr-table td .btn-group:has(> .btn:nth-child(2)) > .btn:last-child { border-start-end-radius: 8px !important; border-end-end-radius: 8px !important; }
			.tab-content.sr-page .sr-actions:has(> .sr-btn:nth-child(2)) { gap: 0; }
			.tab-content.sr-page .sr-actions:has(> .sr-btn:nth-child(2)) > .sr-btn { border-radius: 0; }
			.tab-content.sr-page .sr-actions:has(> .sr-btn:nth-child(2)) > .sr-btn + .sr-btn { margin-inline-start: -1px; }
			.tab-content.sr-page .sr-actions:has(> .sr-btn:nth-child(2)) > .sr-btn:first-child { border-start-start-radius: 8px; border-end-start-radius: 8px; }
			.tab-content.sr-page .sr-actions:has(> .sr-btn:nth-child(2)) > .sr-btn:last-child { border-start-end-radius: 8px; border-end-end-radius: 8px; }

			/* length / filter row under the buttons */
			.tab-content.sr-page .dataTables_wrapper .dataTables_length label,
			.tab-content.sr-page .dataTables_wrapper .dataTables_filter label { display: inline-flex; align-items: center; gap: 6px; margin: 0; }
			.tab-content.sr-page .dataTables_wrapper .dataTables_length select { width: auto; min-width: 70px; padding: 0 28px 0 10px; }

			.tab-content.sr-page table.dataTable td.dataTables_empty { padding: 28px 12px !important; color: var(--sr-muted); text-align: center; }

			/* Direct Reports - search toolbar */
			.direct-reports-search-group {
				display: flex;
				flex-direction: row;
				align-items: center;
				gap: 10px;
				min-width: 280px;
				padding: 10px 14px;
				background: #f4f6f9;
				border: 1px solid #e2e8f0;
				border-radius: 10px;
			}

			.direct-reports-search-label {
				margin: 0;
				font-size: 0.72rem;
				font-weight: 700;
				text-transform: uppercase;
				letter-spacing: .04em;
				color: #8a94a6;
				white-space: nowrap;
			}

			.direct-reports-search-input {
				position: relative;
				display: flex;
				align-items: center;
				flex: 1 1 auto;
			}

			.direct-reports-search-input i.mdi-magnify {
				position: absolute;
				left: 12px;
				color: #94a3b8;
				font-size: 1rem;
				pointer-events: none;
			}

			.direct-reports-search-input input {
				padding-left: 34px;
				padding-right: 30px;
				border-radius: 8px;
				border: 1px solid #e2e8f0;
				background: #fff;
				transition: border-color .15s ease, box-shadow .15s ease, background-color .15s ease;
			}

			.direct-reports-search-input input:focus {
				background: #fff;
				border-color: rgba(67, 97, 238, 0.5);
				box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.12);
			}

			.direct-reports-search-input .directReportsSearchClear {
				position: absolute;
				right: 10px;
				color: #cbd5e1;
				font-size: 1rem;
				cursor: pointer;
				display: none;
			}

			.direct-reports-search-input .directReportsSearchClear:hover {
				color: #64748b;
			}

			.direct-reports-search-input input:not(:placeholder-shown) ~ .directReportsSearchClear {
				display: block;
			}
		</style>
		<?php if ($is_rtl): ?>
			<link href="assets/css/style_rtl.css" rel="stylesheet" type="text/css" />
		<?php endif; ?>
		<script>
			window.lang = <?= json_encode($GLOBALS['translations'] ?? []) ?>;
			window.SALARY_INCREMENT_MAX_AMOUNT = <?= json_encode((float)get_setting_num($conDB, 'salary_increment_max_amount', 2000)) ?>;
		</script>
	</head>

	<body class="enlarged" data-keep-enlarged="true" data-page="view-employee">

		<!-- Begin page -->
		<div id="wrapper">

			<!-- ========== Left Sidebar Start ========== -->
			<div class="left side-menu">

				<div class="slimscroll-menu" id="remove-scroll">

					<!-- LOGO -->
					<div class="topbar-left">
						<a href="dashboard.php" class="logo">
							<span>
								<img src="<?= get_setting($conDB, 'logo') ?>" alt="" height="22">
							</span>
							<i>
								<img src="<?= get_setting($conDB, 'white_logo') ?>" alt="" height="28">
							</i>
						</a>
					</div>

					<!-- User box -->

					<!--- Sidemenu -->
					<?php include("./includes/main_menu.php"); ?>
					<!-- Sidebar -->

					<div class="clearfix"></div>

				</div>
				<!-- Sidebar -left -->

			</div>
			<!-- Left Sidebar End -->



			<!-- ============================================================== -->
			<!-- Start right Content here -->
			<!-- ============================================================== -->

			<div class="content-page">

				<!-- Top Bar Start -->
				<?php include("./includes/topbar.php"); ?>
				<!-- Top Bar End -->

				<!-- Start Page content -->
				<div class="content">
					<div class="container-fluid">
						<?php //echo $thismonthexp 
						?>
						<?php
						// --- Document Expiry Alerts ---
						// Prefer the stored Gregorian date (saved alongside the Hijri one); the server
						// Hijri conversion is arithmetic and can be 1-2 days off Umm al-Qura.
						$_doc_iqama_gregorian = (!empty($emprow['iqama_exp_g']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $emprow['iqama_exp_g']) && $emprow['iqama_exp_g'] !== '0000-00-00')
							? $emprow['iqama_exp_g']
							: $DateConv->HijriToGregorian($emprow['iqama_exp'] ?? '', $format);
						$_doc_contract_expiry = computeContractExpiry(
							$emprow['joining_date'] ?? null,
							isset($emprow['vac_period']) ? (int)$emprow['vac_period'] : null,
							'Y-m-d'
						);
						// "Update Expiry" button on each alert - same HR rule as the backends
						// (employeeDocExpiryHandler.php / employeeMedicalInsuranceHandler.php).
						$_doc_can_update = ($is_system_admin ?? false) || ($isHR ?? false) || ($isDeptHr ?? false);
						$_doc_alerts = [
							['key' => 'iqama', 'label' => getDisplayName('ID / Iqama'), 'expiry_date' => $_doc_iqama_gregorian,
								'hijri_date' => $emprow['iqama_exp'] ?? '',
								'editable' => $_doc_can_update, 'data' => ['hijri' => $emprow['iqama_exp'] ?? '']],
							['key' => 'passport', 'label' => getDisplayName('Passport'), 'expiry_date' => $emprow['passport_exp'] ?? null,
								'editable' => $_doc_can_update],
						];
						if ($current_medical_insurance) {
							$_doc_alerts[] = ['key' => 'insurance', 'label' => __('medical_insurance', 'Medical Insurance'),
								'expiry_date' => $current_medical_insurance['medical_expiry'] ?? null, 'editable' => $_doc_can_update,
								'data' => [
									'insurance-no' => $current_medical_insurance['insurance_no'] ?? '',
									'amount'       => $current_medical_insurance['med_insurance'] ?? '',
									'class'        => $current_medical_insurance['medical_class'] ?? '',
								]];
						}
						// Contract expiry is derived from joining date + contract term and rolls
						// forward on its own, so there is no stored date to edit.
						$_doc_alerts[] = ['key' => 'contract', 'label' => getDisplayName('Contract Renewal'), 'expiry_date' => $_doc_contract_expiry,
							'note' => __('contract_auto_renews', 'Renews automatically')];
						echo get_document_expiry_alerts($_doc_alerts);
						?>
						<?php include("./includes/emp_top_info.php"); ?>
						<div class="row">
							<div class="col-xl-12">

								<?php if (!$canViewSalary): ?>
								<div class="alert alert-warning"><i class="fa fa-lock"></i> <?= __('salary_hidden_no_access') ?></div>
								<?php else: ?>
								<?php
								// Salary information card: total + breakdown with share of total (styles: .sal-card below)
								$salTotal = (float)((!$salary_get) ? $emprow["salary"] : $salary_get);
								$salMeta = [
									'basic' => ['fa-money-bill-wave', '#16a34a'], 'housing' => ['fa-house', '#2563eb'], 'transport' => ['fa-car', '#dc2626'],
									'food' => ['fa-utensils', '#ea580c'], 'misc' => ['fa-shapes', '#64748b'], 'cashier' => ['fa-cash-register', '#0d9488'],
									'fuel' => ['fa-gas-pump', '#ca8a04'], 'tel' => ['fa-phone', '#7c3aed'], 'other' => ['fa-plus', '#475569'], 'guard' => ['fa-user-shield', '#0891b2'],
								];
								?>
								<div class="sal-card">
									<div class="sal-total">
										<span class="sal-total-icon"><i class="fa fa-sack-dollar"></i></span>
										<small><?= __('total_salary') ?></small>
										<b><?= number_format($salTotal, 2) ?> <i class="icon-saudi_riyal"></i></b>
										<span class="sal-total-sub"><?= __('monthly', 'Monthly') ?> &middot; <?= count($shownItems) ?> <?= __('components', 'components') ?></span>
										<?php if ($canViewSalary && !empty($last_salary_increment)): ?>
										<span class="sal-total-chip"><i class="fa fa-arrow-trend-up"></i> +<?= number_format((float)($last_salary_increment['approved_amount'] ?? $last_salary_increment['increment_amount']), 2) ?>
											<?php if (!empty($last_salary_increment['effective_date'])): ?><small><?= date('M Y', strtotime($last_salary_increment['effective_date'])) ?></small><?php endif; ?></span>
										<?php endif; ?>
									</div>
									<div class="sal-breakdown">
										<div class="sal-head"><i class="fa fa-chart-pie"></i> <?= __('salary_information', 'Salary Information') ?></div>
										<?php if ($salTotal > 0 && $shownItems): ?>
										<div class="sal-bar">
											<?php foreach ($shownItems as $item): $v = (float)$emprow[$item]; if ($v <= 0) continue; ?>
												<span style="width: <?= round($v / $salTotal * 100, 2) ?>%; background: <?= ($salMeta[$item] ?? ['', '#64748b'])[1] ?>;" title="<?= htmlspecialchars(__($item, ucfirst($item))) ?>"></span>
											<?php endforeach; ?>
										</div>
										<?php endif; ?>
										<div class="sal-items">
											<?php foreach ($shownItems as $item):
												$v = (float)$emprow[$item];
												[$ic, $col] = $salMeta[$item] ?? ['fa-money-bill', '#64748b'];
												$pct = $salTotal > 0 ? round($v / $salTotal * 100) : 0;
											?>
											<div class="sal-item" style="--c: <?= $col ?>;">
												<span class="sal-item-icon"><i class="fa <?= $ic ?>"></i></span>
												<div class="sal-item-text">
													<small><?= __($item, ucfirst($item)) ?></small>
													<b><?= number_format($v, 2) ?> <i class="icon-saudi_riyal"></i></b>
												</div>
												<span class="sal-item-pct"><?= $pct ?>%</span>
											</div>
											<?php endforeach; ?>
										</div>
									</div>
								</div>
								<?php endif; ?>

								<?php
								$showLocalVacCard = ($emprow["emp_sup_type"] <> "man_power") && ($local_vacation_count > 0);
								?>
								<?php if ($showLocalVacCard): ?>
									<div class="sal-extra">
										<?php if ($showLocalVacCard): ?>
										<div class="sal-item" style="--c: #475569;">
											<span class="sal-item-icon"><i class="fa fa-umbrella-beach"></i></span>
											<div class="sal-item-text"><small><?= __('local_vacation', 'Local Vacation') ?></small><b><?= (int)$local_vacation_count ?></b></div>
										</div>
										<?php endif; ?>
									</div>
								<?php endif; ?>
								<style>
									.sal-card { display: grid; grid-template-columns: minmax(240px, 300px) 1fr; gap: 0; margin-bottom: 20px; border-radius: 16px; overflow: hidden; background: #fff; border: 1px solid rgba(15,23,42,.07); box-shadow: 0 1px 2px rgba(15,23,42,.04), 0 8px 24px rgba(15,23,42,.06); }
									.sal-total { position: relative; overflow: hidden; display: flex; flex-direction: column; justify-content: center; gap: 4px; padding: 24px; color: #fff; background: linear-gradient(135deg, #16a34a, #0d9488); }
									.sal-total::after { content: ""; position: absolute; width: 180px; height: 180px; border-radius: 50%; background: rgba(255,255,255,.1); inset-inline-end: -60px; top: -70px; }
									.sal-total-icon { width: 44px; height: 44px; border-radius: 13px; display: inline-flex; align-items: center; justify-content: center; font-size: 18px; background: rgba(255,255,255,.2); margin-bottom: 8px; }
									.sal-total small { font-size: 11.5px; font-weight: 700; letter-spacing: .6px; text-transform: uppercase; opacity: .9; }
									.sal-total b { font-size: 30px; font-weight: 800; line-height: 1.15; font-variant-numeric: tabular-nums; }
									.sal-total-sub { font-size: 12px; opacity: .85; }
									.sal-total-chip { align-self: flex-start; margin-top: 10px; display: inline-flex; align-items: center; gap: 6px; padding: 4px 10px; border-radius: 999px; font-size: 12px; font-weight: 700; background: rgba(255,255,255,.2); }
									.sal-total-chip small { font-size: 11px; text-transform: none; letter-spacing: 0; }
									.sal-breakdown { padding: 20px 22px; min-width: 0; }
									.sal-head { display: flex; align-items: center; gap: 8px; font-size: 14.5px; font-weight: 800; color: #0f172a; margin-bottom: 12px; }
									.sal-head i { color: #16a34a; }
									.sal-bar { display: flex; height: 10px; border-radius: 999px; overflow: hidden; background: #eef1f5; margin-bottom: 16px; gap: 2px; }
									.sal-bar span { display: block; height: 100%; }
									.sal-items { display: grid; grid-template-columns: repeat(auto-fill, minmax(190px, 1fr)); gap: 10px; }
									.sal-item { display: flex; align-items: center; gap: 12px; padding: 12px; border-radius: 12px; border: 1px solid #e8ebf1; background: #fff; }
									.sal-item-icon { flex: none; width: 40px; height: 40px; border-radius: 11px; display: inline-flex; align-items: center; justify-content: center; font-size: 16px; color: var(--c); background: color-mix(in srgb, var(--c) 13%, white); }
									.sal-item-text { flex: 1; min-width: 0; }
									.sal-item-text small { display: block; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .4px; color: #64748b; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
									.sal-item-text b { font-size: 17px; font-weight: 800; color: #0f172a; font-variant-numeric: tabular-nums; }
									.sal-item-text b em { font-style: normal; font-size: 11px; font-weight: 600; color: #64748b; margin-inline-start: 4px; }
									.sal-item-pct { flex: none; padding: 2px 8px; border-radius: 999px; font-size: 11.5px; font-weight: 800; color: var(--c); background: color-mix(in srgb, var(--c) 11%, white); }
									.sal-extra { display: grid; grid-template-columns: repeat(auto-fill, minmax(260px, 1fr)); gap: 10px; margin-bottom: 20px; }
									.sal-extra .sal-item { box-shadow: 0 1px 2px rgba(15,23,42,.04), 0 6px 16px rgba(15,23,42,.05); }
									@media (max-width: 767px) { .sal-card { grid-template-columns: 1fr; } .sal-total b { font-size: 26px; } }
									html.app-dark .sal-card, html.app-dark .sal-item { background: #242a35; border-color: #323a48; }
									html.app-dark .sal-head, html.app-dark .sal-item-text b { color: #e5e7eb; }
									html.app-dark .sal-bar { background: #323a48; }
								</style>


							</div>
						</div>


						<div class="row">
							<div class="col-12">
								<div class="card-box">
									<div class="d-flex justify-content-between align-items-center mb-3">
										<h4 class="header-title m-t-0"><?= __('employee_information') ?></h4>
										<div class="btn-group" role="group">
											<?php if ($tempRoleCandidate) : ?>
												<?php if (($tempRoleCandidate['assignment_status'] ?? null) === 'active') : ?>
													<button type="button" class="btn btn-sm btn-outline-danger waves-effect"
														onclick="revokeTempRole(<?= (int)$tempRoleCandidate['vacation_id'] ?>)">
														<i class="mdi mdi-account-remove"></i>
														<?= __('revoke_temp_role', 'Revoke Temp Role') ?>
														(<?= htmlspecialchars(getRoleLabel($tempRoleCandidate['granted_role']), ENT_QUOTES) ?>)
													</button>
												<?php else : ?>
													<button type="button" class="btn btn-sm btn-outline-primary waves-effect"
														onclick="grantTempRole(<?= (int)$tempRoleCandidate['vacation_id'] ?>, '<?= htmlspecialchars($tempRoleCandidate['employee_name'] ?? $tempRoleCandidate['employee_emp_id'], ENT_QUOTES) ?>', '<?= htmlspecialchars($tempRoleCandidate['start_date'], ENT_QUOTES) ?>', '<?= htmlspecialchars($tempRoleCandidate['return_date'], ENT_QUOTES) ?>')">
														<i class="mdi mdi-account-switch"></i>
														<?= __('transfer_role_temp', 'Transfer Role (Temp)') ?>
													</button>
												<?php endif; ?>
											<?php endif; ?>
											<?php /*if (empty($emprow['has_active_regular_loan'])) : ?>
											    <button type="button" class="btn btn-success waves-effect waves-light applyLoan" data-emp_id="<?= $emprow['empid'] ?>">
												    <i class="mdi mdi-cash"></i> Apply for Loan<?=__('flys') ?>
											    </button>
                                            <?php endif; ?>
                                            <?php if (empty($emprow['has_active_emergency_loan'])) : ?>
                                                <button type="button" class="btn btn-warning waves-effect waves-light applyEmergencyLoan" data-emp_id="<?= $emprow['empid'] ?>">
												    <i class="mdi mdi-flash"></i> Emergency Loan<?=__('flys') ?>
											    </button>
                                            <?php endif; */ ?>
											<?php /* ?>
											<button type="button" class="btn btn-info waves-effect waves-light" onclick="assignAsset('<?= $emprow['empid'] ?>')">
												<i class="mdi mdi-plus-circle-outline"></i><?=__('assign_asset') ?>
											</button>
											<?php */ ?>
										</div>
									</div>
									<ul class="nav emp-tabs sr-page">

										<li class="nav-item">
											<a href="#profile1" data-toggle="tab" aria-expanded="true" class="nav-link active show">
												<i class="fi-head"></i><?= __('profile') ?>
											</a>
										</li>
										<?php /*if($user_type <> "dept_user"){*/ ?>
										<li class="nav-item">
											<a href="#home1" data-toggle="tab" aria-expanded="false" class="nav-link">
												<i class="mdi mdi-buffer"></i> <?= __('vacations') ?>
											</a>
										</li>
										<li class="nav-item">
											<a href="#loan1" data-toggle="tab" aria-expanded="false" class="nav-link">
												<i class="mdi mdi-cash-multiple"></i> <?= __('loans') ?>
											</a>
										</li>
										<?php if ($canViewSalary): ?>
										<li class="nav-item">
											<a href="#payroll1" data-toggle="tab" aria-expanded="false" class="nav-link">
												<i class="mdi mdi-file-document-box"></i> <?= __('payrolls', 'Payroll Details') ?>
											</a>
										</li>
										<?php endif; ?>
										<?php if ($canViewAdditionalInfo || $canViewOtherIncome): ?>
										<li class="nav-item">
											<a href="#additionalinfo1" data-toggle="tab" aria-expanded="false" class="nav-link">
												<i class="mdi mdi-information-outline"></i> <?= __('additional_information', 'Additional Information') ?>
											</a>
										</li>
										<?php endif; ?>
										<li class="nav-item">
											<a href="#assets" data-toggle="tab" aria-expanded="false" class="nav-link">
												<i class="mdi mdi-cash-multiple"></i> <?= __('assets') ?>
											</a>
										</li>
										<li class="nav-item">
											<a href="#documents" data-toggle="tab" aria-expanded="false" class="nav-link">
												<i class="mdi mdi-book-open-page-variant"></i> <?= __('documents') ?> <?= ($emprow['docs_count'] > 0) ? "<span class='badge-count'>" . $emprow['docs_count'] . "</span>" : "" ?>
											</a>
										</li>
										<!-- Memos tab also holds the employee Notes (below the memo table), so it is
											 shown to everyone; only the memo table itself is gated by $canViewMemosTab. -->
										<li class="nav-item">
											<a href="#memos" data-toggle="tab" aria-expanded="false" class="nav-link" id="memosTabLink">
												<i class="mdi mdi-email-outline"></i> <?= __('memos', 'Memos') ?> <?= ($emprow['empnote'] > 0) ? "<span class='badge-count'>" . $emprow['empnote'] . "</span>" : "" ?>
											</a>
										</li>
										<?php  ?>
										<li class="nav-item">
											<a href="#evaluations" data-toggle="tab" aria-expanded="false" class="nav-link">
												<i class="mdi mdi-chart-line"></i> <?= __('evaluations', 'Performance Evaluations') ?>
											</a>
										</li>
										<?php if ($direct_reports_count > 1): ?>
										<li class="nav-item">
											<a href="#directreports" data-toggle="tab" aria-expanded="false" class="nav-link" id="directReportsTabLink">
												<i class="mdi mdi-account-multiple"></i> <?= __('direct_reports', 'Direct Reports') ?> <span class="badge-count"><?= $direct_reports_count ?></span>
											</a>
										</li>
										<?php endif; ?>
										<?php  ?>
										<?php /*}*/ ?>

										<?php if ($canViewAttendanceTab): ?>
										<li class="nav-item">
											<a href="#attendance" data-toggle="tab" aria-expanded="false" class="nav-link">
												<i class="mdi mdi-fingerprint mr-2"></i> <?= __('attendance', 'Attendance Record') ?>
											</a>
										</li>
										<?php endif; ?>
										<?php if ($show_d365_tab): ?>
										<li class="nav-item">
											<a href="#d365tab" data-toggle="tab" aria-expanded="false" class="nav-link" id="d365TabLink">
												<i class="mdi mdi-microsoft"></i> D365
											</a>
										</li>
										<?php endif; ?>
									</ul>
									<div class="tab-content sr-page">
										<!-- Profile -->
										<div class="tab-pane active show" id="profile1">
											<div class="profile-content-wrapper">
												<!-- Personal Information Section -->
												<div class="profile-section ps-personal">
													<div class="profile-section-header personal">
														<span class="psh-icon"><i class="fa fa-user"></i></span>
														<span><?= __('personal_information', 'Personal Information') ?></span>
													</div>
													<div class="profile-section-body">
														<div class="profile-grid">
															<div class="profile-field">
																<div class="profile-field-label"><i class="fa fa-user"></i><?= __('name_of_employee') ?></div>
																<div class="profile-field-value">
																	<span class="copyToClipboard"><?= getDisplayName($emprow['name']); ?></span>
																	<i class="fa fa-clipboard"></i>
																</div>
															</div>
															<div class="profile-field">
																<div class="profile-field-label"><i class="fa fa-id-card"></i><?= __('iqama_id') ?></div>
																<div class="profile-field-value">
																	<span class="copyToClipboard"><?= $emprow['iqama']; ?></span>
																	<i class="fa fa-clipboard"></i>
																</div>
															</div>
															<div class="profile-field">
																<div class="profile-field-label"><i class="fa fa-calendar-xmark"></i><?= __('id_expiry') ?></div>
																<div class="profile-field-value">
																	<span class="date-batch-h" data-prefix="<?= __('hijri') ?>"><?= $emprow['iqama_exp']; ?></span>
																	<span class="date-batch-g" data-prefix="<?= __('gregorian') ?>"><?= $DateConv->HijriToGregorian($emprow['iqama_exp'], $format); ?></span>
																</div>
															</div>
															<div class="profile-field">
																<div class="profile-field-label"><i class="fa fa-passport"></i><?= __('passport_no') ?></div>
																<div class="profile-field-value">
																	<span class="copyToClipboard"><?= $emprow['passport_number']; ?></span>
																	<i class="fa fa-clipboard"></i>
																</div>
															</div>
															<div class="profile-field">
																<div class="profile-field-label"><i class="fa fa-calendar-xmark"></i><?= __('passport_expiry') ?></div>
																<div class="profile-field-value">
																	<?php if ($emprow['passport_exp']): ?>
																		<span class="date-batch-g" data-prefix="<?= __('gregorian') ?>"><?= $emprow['passport_exp']; ?></span>
																		<span class="date-batch-h" data-prefix="<?= __('hijri') ?>"><?= $DateConv->GregorianToHijri($emprow['passport_exp'], $format); ?></span>
																	<?php else: ?>
																		<span class="text-muted"><?= __('not_available', 'N/A') ?></span>
																	<?php endif ?>
																</div>
															</div>
															<div class="profile-field">
																<div class="profile-field-label"><i class="fa fa-cake-candles"></i><?= __('date_of_birth') ?></div>
																<div class="profile-field-value">
																	<span class="date-batch-g" data-prefix="<?= __('gregorian') ?>"><?= $emprow["dob"]; ?></span>
																	<span class="date-batch-h" data-prefix="<?= __('hijri') ?>"><?= $DateConv->GregorianToHijri($emprow["dob"], $format); ?></span>
																</div>
															</div>
															<div class="profile-field">
																<div class="profile-field-label"><i class="fa fa-hourglass-half"></i><?= __('age') ?></div>
																<div class="profile-field-value"><?= ($emprow["dob"] <> "") ? $years : "" ?></div>
															</div>
															<div class="profile-field">
																<div class="profile-field-label"><i class="fa fa-droplet"></i><?= __('gender_blood_group') ?></div>
																<div class="profile-field-value"><?= ucfirst(__($emprow["sex"])) . " | " . $emprow['blood_type']; ?></div>
															</div>
															<div class="profile-field">
																<div class="profile-field-label"><i class="fa fa-heart"></i><?= __('marital_status') ?></div>
																<div class="profile-field-value"><?= ucfirst(__($emprow["mar_status"])); ?></div>
															</div>
															<div class="profile-field">
																<div class="profile-field-label"><i class="fa fa-shirt"></i><?= __('tshirt_size') ?></div>
																<div class="profile-field-value"><?= ucfirst($emprow['t_shirt_size']); ?></div>
															</div>
														</div>
													</div>
												</div>

												<!-- Employment Information Section -->
												<div class="profile-section ps-employment">
													<div class="profile-section-header employment">
														<span class="psh-icon"><i class="fa fa-briefcase"></i></span>
														<span><?= __('employment', 'Employment Information') ?></span>
													</div>
													<div class="profile-section-body">
														<div class="profile-grid">
															<div class="profile-field">
																<div class="profile-field-label"><i class="fa fa-calendar-check"></i><?= __('joining_date') ?></div>
																<div class="profile-field-value">
																	<span class="date-batch-g" data-prefix="<?= __('gregorian') ?>"><?= $emprow["joining_date"]; ?></span>
																	<span class="date-batch-h" data-prefix="<?= __('hijri') ?>"><?= $DateConv->GregorianToHijri($emprow["joining_date"], $format); ?></span>
																</div>
															</div>
															<div class="profile-field">
																<div class="profile-field-label"><i class="fa fa-file-contract"></i><?= __('contract_expiry_label', 'Contract Expiry') ?></div>
																<div class="profile-field-value">
																	<?php
																	$expiryIso = computeContractExpiry(
																		$emprow['joining_date'] ?? null,
																		isset($emprow['vac_period']) ? (int)$emprow['vac_period'] : null,
																		'Y-m-d'
																	);
																	if ($expiryIso) {
																		$expiryDisplay = format_safe_date($expiryIso, 'd M Y');
																		?>
																		<span class="date-batch-g" data-prefix="<?= __('gregorian') ?>"><?= htmlspecialchars($expiryDisplay) ?></span>
																		<span class="date-batch-h" data-prefix="<?= __('hijri') ?>"><?= $DateConv->GregorianToHijri($expiryIso, $format); ?></span>
																		<?php
																	} else {
																		?>
																		<span class="text-muted"><?= __('not_available', 'N/A') ?></span>
																		<?php
																	}
																	?>
																</div>
															</div>
															<div class="profile-field">
																<div class="profile-field-label"><i class="fa fa-sitemap"></i><?= __('department') ?></div>
																<div class="profile-field-value"><?= ($is_rtl ?? false) ? $emprow["deptnme_ar"] : $emprow["deptnme"] ?></div>
															</div>
															<div class="profile-field">
																<div class="profile-field-label"><i class="fa fa-building"></i><?= __('company_label') ?></div>
																<div class="profile-field-value"><?= ($is_rtl ?? false) ? ($emprow["compnme_ar"] ?? $emprow["compnme"] ?? __('not_available', 'N/A')) : ($emprow["compnme"] ?? __('not_available', 'N/A')) ?></div>
															</div>
															<div class="profile-field">
																<div class="profile-field-label"><i class="fa fa-city"></i><?= __('city_label', 'City') ?></div>
																<div class="profile-field-value"><?= !empty($emprow["cityname"]) ? (($is_rtl ?? false) ? $emprow["cityname_ar"] : $emprow["cityname"]) : __('not_available', 'N/A') ?></div>
															</div>
															<div class="profile-field">
																<div class="profile-field-label"><i class="fa fa-map-pin"></i><?= __('location_label', 'Location') ?></div>
																<div class="profile-field-value"><?= !empty($emprow["locationname"]) ? (($is_rtl ?? false) ? $emprow["locationname_ar"] : $emprow["locationname"]) : __('not_available', 'N/A') ?></div>
															</div>
															<div class="profile-field">
																<div class="profile-field-label"><i class="fa fa-diagram-project"></i><?= __('sub_department_label', 'Sub-Department') ?></div>
																<div class="profile-field-value"><?= !empty($emprow["subdeptname"]) ? (($is_rtl ?? false) ? $emprow["subdeptname_ar"] : $emprow["subdeptname"]) : __('not_available', 'N/A') ?></div>
															</div>
															<div class="profile-field">
																<div class="profile-field-label"><i class="fa fa-id-badge"></i><?= __('employee_type') ?? "Employee Type" ?></div>
																<div class="profile-field-value"><?= __(strtolower($emprow['emptype'])) ?? __('not_available', 'N/A') ?></div>
															</div>
															<div class="profile-field">
																<div class="profile-field-label"><i class="fa fa-user-tie"></i><?= __('actual_job') ?></div>
																<div class="profile-field-value"><?= ($is_rtl ?? false ? $emprow["jobname_ar"] : $emprow["jobname"]) ?></div>
															</div>
															<div class="profile-field">
																<div class="profile-field-label"><i class="fa fa-user-shield"></i><?= __('direct_supervisor') ?? "Direct Supervisor" ?></div>
																<div class="profile-field-value">
																	<?php
																	if (!empty($emprow['supervisor_id'])) {
																		$supervisor_query = mysqli_query($conDB, "
																			SELECT `name`, `emp_id`, `emptype` 
																			FROM `employees` 
																			WHERE `emp_id` = '{$emprow['supervisor_id']}' 
																			LIMIT 1
																		");
																		$supervisor = mysqli_fetch_assoc($supervisor_query);
																		if ($supervisor) {
																			echo '<a href="view_employee.php?emp_id=' . $supervisor['emp_id'] . '" class="text-primary">' .
																				getDisplayName($supervisor['name']) . ' (' . $supervisor['emp_id'] . ')</a>';
																		} else {
																			echo '<span class="text-muted">' . (__('not_assigned') ?? 'Not Assigned') . '</span>';
																		}
																	} else {
																		echo '<span class="text-muted">' . (__('not_assigned') ?? 'Not Assigned') . '</span>';
																	}
																	?>
																</div>
															</div>
															<div class="profile-field">
																<div class="profile-field-label"><i class="fa fa-user-clock"></i><?= __('probation_period') ?></div>
																<div class="profile-field-value"><?= $probationStatus ?></div>
															</div>
															<div class="profile-field">
																<div class="profile-field-label"><i class="fa fa-clock"></i><?= __('eligible_for_overtime', 'Eligible For Overtime') ?></div>
																<div class="profile-field-value"><?= $overtimeEligibilityText ?></div>
															</div>
															<div class="profile-field">
																<div class="profile-field-label"><i class="fa fa-truck-medical"></i><?= __('allow_emergency_vacation', 'Allow Emergency Vacation') ?></div>
																<div class="profile-field-value"><?= $allowEmergencyVacationText ?></div>
															</div>
															<div class="profile-field">
																<div class="profile-field-label"><i class="fa fa-handshake"></i><?= __('sponsorship_label') ?></div>
																<div class="profile-field-value"><?= ($is_rtl ?? false) ? $emprow['sponsor_ar'] : $emprow['sponsor'] ?></div>
															</div>
															<div class="profile-field">
																<div class="profile-field-label"><i class="fa fa-calendar-week"></i><?= __('contract_period') ?></div>
																<div class="profile-field-value"><?= translateContractPeriod($emprow["period"]) ?></div>
															</div>
														</div>
													</div>
												</div>

												<!-- Contact Information Section -->
												<div class="profile-section ps-contact">
													<div class="profile-section-header contact">
														<span class="psh-icon"><i class="fa fa-address-book"></i></span>
														<span><?= __('contact', 'Contact Information') ?></span>
													</div>
													<div class="profile-section-body">
														<div class="profile-grid">
															<div class="profile-field full-width">
																<div class="profile-field-label"><i class="fa fa-envelope"></i><?= __('email') ?></div>
																<div class="profile-field-value">
																	<?= ($emprow['c_email']) ? "<b>" . __('personal') . ":</b> <span class='copyToClipboard'>" . $emprow['email'] . "</span> <i class='fa fa-clipboard'></i> | <b>" . __('company') . ":</b> <span class='copyToClipboard'>" . $emprow['c_email'] . "</span> <i class='fa fa-clipboard'></i>" : "<span class='copyToClipboard'>" . $emprow['email'] . "</span> <i class='fa fa-clipboard'></i>" ?>
																</div>
															</div>
															<div class="profile-field">
																<div class="profile-field-label"><i class="fa fa-mobile-screen"></i><?= __('mobile') ?></div>
																<div class="profile-field-value">
																	<span class="copyToClipboard"><?= $emprow['mobile']; ?></span>
																	<i class="fa fa-clipboard"></i>
																</div>
															</div>
															<div class="profile-field">
																<div class="profile-field-label"><i class="fa fa-earth-asia"></i><?= __('country') ?></div>
																<div class="profile-field-value"><?= ($is_rtl ?? false) ? $emprow["country_name_ar"] : $emprow["country_name"]; ?></div>
															</div>
															<div class="profile-field full-width">
																<div class="profile-field-label"><i class="fa fa-phone-volume"></i><?= __('emergency_contact') ?></div>
																<div class="profile-field-value"><?= $emprow['emg_name'] . " - " . $emprow["emg_mobile"] ?></div>
															</div>
															<div class="profile-field full-width">
																<div class="profile-field-label"><i class="fa fa-location-dot"></i><?= __('address') ?></div>
																<div class="profile-field-value"><?= getDisplayName(ucfirst($emprow['address'])) ?></div>
															</div>
														</div>
													</div>
												</div>

												<!-- Banking & Salary Information Section -->
												<div class="profile-section ps-banking">
													<div class="profile-section-header banking">
														<span class="psh-icon"><i class="fa fa-building-columns"></i></span>
														<span><?= __('bank_&_gosi_details', 'Banking & Salary') ?></span>
													</div>
													<div class="profile-section-body">
														<div class="profile-grid">
															<div class="profile-field">
																<div class="profile-field-label"><i class="fa fa-sack-dollar"></i><?= __('total_salary') ?></div>
																<div class="profile-field-value">
																	<?php if ($canViewSalary): ?>
																		<?= number_format($emprow['salary'], 2); ?> <i class="icon-saudi_riyal"></i> -
																		<?= ($emprow['payment_type'] == 1 ? __('bank_transfer') : ($emprow['payment_type'] == 2 ? __('cash_payment') : __('about_to_hold'))) ?>
																	<?php else: ?>
																		<span class="text-muted"><i class="fa fa-lock"></i> <?= __('salary_hidden_no_access') ?></span>
																	<?php endif; ?>
																</div>
															</div>
															<div class="profile-field">
																<div class="profile-field-label"><i class="fa fa-building-columns"></i><?= __('bank_name') ?></div>
																<div class="profile-field-value"><?= ($is_rtl ?? false) ? $emprow["b_name_ar"] : $emprow["b_name"] ?></div>
															</div>
															<div class="profile-field full-width">
																<div class="profile-field-label"><i class="fa fa-credit-card"></i><?= __('iban') ?></div>
																<div class="profile-field-value">
																	<span class="copyToClipboard"><?= $emprow["iban"] ?></span>
																	<i class="fa fa-clipboard"></i>
																</div>
															</div>
															<div class="profile-field">
																<div class="profile-field-label"><i class="fa fa-shield-halved"></i><?= __('gosi_gosi_no') ?></div>
																<div class="profile-field-value"><?= $emprow["gosi"] . " | " . $emprow["gosi_no"] ?></div>
															</div>
															<div class="profile-field">
																<div class="profile-field-label"><i class="fa fa-calendar-xmark"></i><?= __('gosi_expiry') ?></div>
																<div class="profile-field-value"><?= $emprow["date_hijri"] . " | " . $emprow["date_greg"] ?></div>
															</div>
														</div>
													</div>
												</div>

												<?php if (d365_enabled($conDB)): /* Microsoft Dynamics 365 switched off - no D365 section */ ?>
												<!-- Dynamics 365 Section -->
												<?php $d365Info = d365_profile_info($conDB, $emprow); $d365St = $d365Info['status']; $d365Companies = payroll_company_list(); ?>
												<div class="profile-section ps-d365">
													<div class="profile-section-header d365">
														<span class="psh-icon"><i class="fa fa-cloud"></i></span>
														<span><?= __('d365_details', 'Dynamics 365') ?><?= $d365Info['environment'] !== '' ? ' (' . htmlspecialchars($d365Info['environment']) . ')' : '' ?></span>
														<?php if (!empty($show_d365_tab)): ?>
														<button type="button" class="psh-btn psh-btn-d365" onclick="var l=document.getElementById('d365TabLink'); if(l){ l.click(); }"><i class="fa fa-cloud"></i><?= __('open_d365_tab', 'Open D365 tab') ?><i class="fa fa-arrow-right psh-arrow"></i></button>
														<?php endif; ?>
													</div>
													<div class="profile-section-body">
														<div class="profile-grid">
															<div class="profile-field">
																<div class="profile-field-label"><i class="fa fa-user-check"></i><?= __('d365_worker_status', 'D365 worker') ?></div>
																<div class="profile-field-value">
																	<?php if ($d365St && ($d365St['status'] ?? '') === 'registered'): ?>
																		<span class="text-success"><i class="mdi mdi-check-circle"></i> <?= __('d365_registered', 'Registered') ?></span>
																		<small class="text-muted">- <?= __('personnel_number', 'Personnel no.') ?> <?= htmlspecialchars($emprow['emp_id']) ?></small>
																	<?php elseif ($d365St): ?>
																		<span class="text-warning"><i class="mdi mdi-alert-circle"></i> <?= htmlspecialchars(ucfirst(str_replace('_', ' ', (string)$d365St['status']))) ?></span>
																	<?php else: ?>
																		<span class="text-muted"><?= __('d365_not_checked', 'Not checked yet') ?></span>
																	<?php endif; ?>
																</div>
															</div>
															<div class="profile-field">
																<div class="profile-field-label"><i class="fa fa-rotate"></i><?= __('d365_last_synced', 'Last synced') ?></div>
																<div class="profile-field-value"><?= !empty($d365St['synced_at']) ? htmlspecialchars($d365St['synced_at']) : '<span class="text-muted">' . __('not_available', 'N/A') . '</span>' ?></div>
															</div>
															<div class="profile-field">
																<div class="profile-field-label"><i class="fa fa-briefcase"></i><?= __('d365_employment_company', 'D365 employment company') ?></div>
																<div class="profile-field-value"><?php $c = $d365Info['employment_company'];
																	echo $c === '' ? '<span class="text-muted">' . __('not_available', 'N/A') . '</span>' : htmlspecialchars($c . (!empty($d365Companies[$c]['name']) ? ' - ' . $d365Companies[$c]['name'] : ''));
																	if (!empty($can_d365_sync) && $d365St && ($d365St['status'] ?? '') === 'registered'): ?>
																	<button type="button" class="btn btn-sm btn-outline-primary ml-2" style="padding:1px 8px;font-size:12px" onclick="document.dispatchEvent(new CustomEvent('d365:transfer-request'))"><i class="mdi mdi-swap-horizontal"></i> <?= __('d365_change_company', 'Change company') ?></button>
																	<?php endif; ?></div>
															</div>
															<div class="profile-field">
																<div class="profile-field-label"><i class="fa fa-money-check-dollar"></i><?= __('payroll_company_label', 'Payroll Company (D365)') ?></div>
																<div class="profile-field-value"><?php $c = $d365Info['payroll_company'];
																	echo $c === '' ? '<span class="text-muted">' . __('not_available', 'N/A') . '</span>' : htmlspecialchars($c . (!empty($d365Companies[$c]['name']) ? ' - ' . $d365Companies[$c]['name'] : ''));
																	if ($d365Info['payroll_auto']): ?> <small class="text-muted">(<?= __('payroll_company_auto_short', 'Auto') ?>)</small><?php endif; ?></div>
															</div>
															<div class="profile-field">
																<div class="profile-field-label"><i class="fa fa-bullseye"></i><?= __('cost_center_label', 'Cost Center') ?></div>
																<div class="profile-field-value"><?php if ($d365Info['uses_cost_center']): ?><?= htmlspecialchars(cost_center_label($conDB, $emprow['cost_center'] ?? '')) ?><?php else: ?><span class="text-muted"><?= htmlspecialchars(__('cost_center_not_used_short', 'Not used in') . ' ' . $d365Info['payroll_company']) ?></span><?php endif; ?></div>
															</div>
															<div class="profile-field">
																<div class="profile-field-label"><i class="fa fa-sitemap"></i><?= __('d365_department', 'D365 Department') ?></div>
																<div class="profile-field-value"><?= $d365Info['department'] !== '' ? htmlspecialchars($d365Info['department']) : '<span class="text-muted">' . __('not_available', 'N/A') . '</span>' ?>
																	<small class="text-muted">(<?= htmlspecialchars((($is_rtl ?? false) ? $emprow['deptnme_ar'] : $emprow['deptnme']) ?? '') ?>)</small></div>
															</div>
															<?php if (!empty($d365St['last_error'])): ?>
															<div class="profile-field full-width">
																<div class="profile-field-label"><i class="fa fa-message"></i><?= __('d365_last_message', 'Last D365 message') ?></div>
																<div class="profile-field-value text-warning"><?= htmlspecialchars($d365St['last_error']) ?></div>
															</div>
															<?php endif; ?>
														</div>
													</div>
												</div>
												<?php endif; ?>

												<!-- Insurance Information Section -->
												<div class="profile-section ps-insurance">
													<div class="profile-section-header insurance">
														<span class="psh-icon"><i class="fa fa-shield-heart"></i></span>
														<span><?= __('insurance', 'Insurance Information') ?></span>
													</div>
													<div class="profile-section-body">
														<div class="profile-grid">
															<div class="profile-field">
																<div class="profile-field-label"><i class="fa fa-notes-medical"></i><?= __('insurance_no_class') ?></div>
																<div class="profile-field-value"><?= display_or_na($current_medical_insurance['insurance_no'] ?? null) ?> | <?= display_or_na($current_medical_insurance['medical_class'] ?? null) ?></div>
															</div>
															<div class="profile-field">
																<div class="profile-field-label"><i class="fa fa-calendar-xmark"></i><?= __('insurance_expiry') ?></div>
																<div class="profile-field-value">
																	<?php if (!empty($current_medical_insurance['medical_expiry']) && $current_medical_insurance['medical_expiry'] !== '0000-00-00'): ?>
																		<span class="date-batch-g" data-prefix="<?= __('gregorian') ?>"><?= $current_medical_insurance['medical_expiry']; ?></span>
																		<span class="date-batch-h" data-prefix="<?= __('hijri') ?>"><?= $DateConv->GregorianToHijri($current_medical_insurance['medical_expiry'], $format); ?></span>
																	<?php else: ?>
																		<span class="text-muted"><?= __('not_available', 'N/A') ?></span>
																	<?php endif ?>
																</div>
															</div>
														</div>
													</div>
												</div>

												<?php if (car_get_info($emprow["car_id"])): ?>
												<!-- Car Information Section -->
												<div class="profile-section ps-car">
													<div class="profile-section-header car">
														<span class="psh-icon"><i class="fa fa-car"></i></span>
														<span><?= __('car_details', 'Car Information') ?></span>
													</div>
													<div class="profile-section-body">
														<div class="profile-grid">
															<div class="profile-field">
																<div class="profile-field-label"><i class="fa fa-car"></i><?= __('car_maker') ?></div>
																<div class="profile-field-value"><?= getDisplayName(car_get_info($emprow["car_id"])['maker_name']) . " (" . car_get_info($emprow["car_id"])['made_year'] . ")" ?></div>
															</div>
															<div class="profile-field">
																<div class="profile-field-label"><i class="fa fa-car-side"></i><?= __('car_model') ?></div>
																<div class="profile-field-value"><?= getDisplayName(car_get_info($emprow["car_id"])['model']) ?></div>
															</div>
														</div>
													</div>
												</div>
												<?php endif; ?>
											</div>

											<?php if ($emprow['status'] != 1): // Active employees print from the "More" menu; it is hidden for inactive ones ?>
											<div class="text-right mt-4">
												<div class="btn-group" role="group" aria-label="Edit Button">
													<button type="button" class="btn btn-sm waves-effect btn-primary" onclick="openPrintProfileOptions()"><i class="fi-printer "></i> <?= __('print_profile') ?></button>
												</div>
											</div>
											<?php endif; ?>
										</div>
										<!-- End Profile Tab -->

									<!-- Vacation Details Tab -->
									<div class="tab-pane" id="home1">
										<?php
											// Get vacation balance data
											$vac_total = $emprow['vacation_days'] ?? 0;
											$vac_used = $emprow['used_days'] ?? 0;
											$vac_available = $emprow['available_balance'] ?? 0;
											?>
											<?php $vac_used_pct = $vac_total > 0 ? min(100, max(0, round($vac_used / $vac_total * 100))) : 0; ?>
											<?php if ($vac_total > 0 || $vac_used > 0 || $vac_available > 0): ?>
											<div class="vac-sum" title="<?= __('vacation_balance_summary') ?>">
												<div class="vac-sum-tile" style="--c: #2563eb;"><i class="fa fa-calendar-days"></i><div><small><?= __('total_vacation_days') ?></small><b><?= number_format($vac_total, 1) ?><span class="vac-unit"><?= __('day_s', 'Days') ?></span></b></div></div>
												<div class="vac-sum-tile" style="--c: #d97706;"><i class="fa fa-plane-departure"></i><div><small><?= __('used_days') ?></small><b><?= number_format($vac_used, 1) ?><span class="vac-unit"><?= __('day_s', 'Days') ?></span></b></div></div>
												<div class="vac-sum-tile" style="--c: #16a34a;"><i class="fa fa-umbrella-beach"></i><div><small><?= __('available_balance') ?></small><b><?= number_format($vac_available, 2) ?><span class="vac-unit"><?= __('day_s', 'Days') ?></span></b></div></div>
											</div>
											<?php if ($vac_total > 0): ?>
											<div class="vac-sum-bar">
												<div class="vac-sum-bar-head"><span><i class="fa fa-chart-simple"></i> <?= __('vacation_balance_summary') ?></span><span class="vac-sum-used"><span class="vac-pct"><?= $vac_used_pct ?>%</span><span><?= __('used_days') ?></span></span></div>
												<div class="vac-sum-bar-track"><span style="width: <?= $vac_used_pct ?>%;"></span></div>
											</div>
											<?php endif; ?>
											<?php else: ?>
											<div class="vac-sum-empty"><i class="fa fa-circle-info"></i> <?= __('no_vacation_balance_record_found') ?></div>
											<?php endif; ?>
											<div class="table-responsive">
												<?php if ($isHR || $is_system_admin || $isDeptHr) { ?>
													<div class="mb-3">
														<button type="button" class="btn btn-primary btn-sm" onclick="addManualVacationHistory(<?= (int)$emprow['empid'] ?>, '<?= htmlspecialchars($emprow['name'] ?? '', ENT_QUOTES) ?>')">
															<i class="mdi mdi-plus-circle mr-1"></i><?= __('add_manual_vacation_history') ?>
														</button>
													</div>
												<?php } ?>
												<h4 class="m-t-0 header-title"></h4>
												<table id="employee_vac" class="table sr-table dt-responsive nowrap" style="border-collapse: collapse; border-spacing: 0; width: 100%;">
													<thead>
														<tr>
															<th><?= __('id') ?></th>
															<th><?= __('vacation_type') ?></th>
															<th><?= __('fly_type') ?></th>
															<th><?= __('start_date') ?></th>
															<th><?= __('return_date') ?></th>
															<th><?= __('days') ?></th>
															<th><?= __('permit_no') ?></th>
															<th><?= __('approval_status') ?></th>
															<th><?= __('arrived') ?></th>
															<th><?= __('remarks') ?></th>
															<th><?= __('created_at') ?></th>
															<?php if ($user_type <> "dept_user") { ?>
																<th><?= __('action') ?></th>
															<?php } ?>

														</tr>
													</thead>
													<tbody>
														<?php
														$sql_emp_vac = "SELECT * FROM `emp_vacation` WHERE `emp_id`='" . $emprow['empid'] . "' ORDER BY `id` DESC";
														$query_emp_vac = mysqli_query($conDB, $sql_emp_vac);

														while ($rec = mysqli_fetch_array($query_emp_vac)) {
															$id_emp_reg = $rec["id"];
															$vac_type = $rec["vac_type"] ?? $rec["note"]; // vacation type (Fly, Local Vacation, Encashed)
															$fly_type = $rec["fly_type"] ?? 'N/A'; // fly type (annual, emergency)
															$start_date = $rec["start_date"] ?? $rec["date"];
															$return_date_emp = $rec["return_date"];
															$vacdays_emp = $rec["vacdays"];
															$permit_no_emp = $rec["permit_no"];
															$current_status = $rec["current_status"] ?? 'pending';
															$remarks_get = $rec["remarks"];
															$arrived_date_get = $rec["arrived_date"];
															$can_cancel_vacation =
																(string)($emprow['empid'] ?? '') === (string)($empid ?? '') &&
																!in_array($current_status, ['completed', 'cancelled', 'rejected'], true);

															$date_reg_emp = $rec["created_at"];
															$timestamp_reg = strtotime("$date_reg_emp");
															$new_date_format = date('d, M Y H:i', $timestamp_reg);
															// Do not show legacy invoices in loan history
															if (isset($rec['request_inv_no']) && strpos($rec['request_inv_no'], 'LEGACY-') === 0) {
																continue;
															}
															?>
															<tr>
																<td><?= $id_emp_reg; ?></td>
																<td>
																	<?php
																	$vac_badge_color = ($vac_type == 'Fly') ? 'warning' : (($vac_type == 'Encashed') ? 'success' : 'info');
																	?>
																	<span class="badge badge-<?= $vac_badge_color ?>"><?= __(strtolower(str_replace(' ', '_', $vac_type))) ?></span>
																</td>
																<td>
																	<?php if ($fly_type && $fly_type != 'N/A'): ?>
																		<span class="badge badge-<?= ($fly_type == 'annual') ? 'primary' : 'danger' ?>"><?= __(strtolower($fly_type)) ?></span>
																	<?php else: ?>
																		<span class="text-muted">-</span>
																	<?php endif; ?>
																</td>
																<td><?= $start_date ? format_safe_date($start_date, 'd M, Y') : '-'; ?></td>
																<td><?= $return_date_emp ? format_safe_date($return_date_emp, 'd M, Y') : '-'; ?></td>
																<td><span class="badge badge-secondary"><?= $vacdays_emp ?? 0; ?></span></td>
																<td><?= $permit_no_emp ?: '-'; ?></td>
																<td>
																	<?php
																	$status_badge = ($current_status == 'approved') ? 'success' : (($current_status == 'rejected') ? 'danger' : 'warning');
																	?>
																	<span class="badge badge-<?= $status_badge ?>"><?= __(strtolower($current_status)) ?></span>
																</td>
																<td><?= ($arrived_date_get == "") ? "<span class='text-muted'>" . __('not_yet') . "</span>" : format_safe_date($arrived_date_get, 'd M, Y'); ?></td>
																<td><?= $remarks_get ?: '-'; ?></td>
																<td><?= $new_date_format; ?></td>
																<?php if ($user_type <> "dept_user") { ?>
																	<td>
																		<div class="btn-group" role="group">
																			<a href="javascript:void(0);" class="btn btn-sm btn-primary waves-effect">
																				<i class="fa fa-edit"></i>
																			</a>
																			<?php if ($can_cancel_vacation) { ?>
																				<button
																					type="button"
																					class="btn btn-sm btn-warning waves-effect"
																					onclick="cancelVacationRequest(<?= (int)$id_emp_reg ?>, '<?= htmlspecialchars(addslashes($vac_type), ENT_QUOTES) ?>', '<?= htmlspecialchars(addslashes(format_safe_date($start_date, 'M d, Y')), ENT_QUOTES) ?>')"
																					title="<?= __('cancel_vacation_request') ?>">
																					<i class="fa fa-times"></i>
																				</button>
																			<?php } ?>
																			<?php if ($is_system_admin) { ?>
																				<a href="javascript:void(0);" class="btn btn-sm btn-danger waves-effect deleteAjax" data-id="<?= $id_emp_reg ?>" data-tbl="emp_vacation" data-file='0' >
																					<i class="fa fa-solid fa-remove"></i>
																				</a>
																			<?php } ?>
																		</div>
																	</td>
																<?php } ?>

															</tr>
														<?php } ?>
													</tbody>
												</table>
											</div>

											<?php if ($isHR || $is_system_admin || $isDeptHr) { ?>
												<div class="card border-secondary border mb-4">
													<div class="card-header bg-secondary text-white font-weight-bold">
														<i class="mdi mdi-history mr-1"></i><?= __('vacation_activity_log', 'Vacation Activity Log') ?>
													</div>
													<div class="card-body table-responsive">
														<table id="employee_vac_activity_log" class="table sr-table dt-responsive nowrap" style="border-collapse: collapse; border-spacing: 0; width: 100%;">
															<thead>
																<tr>
																	<th><?= __('date') ?></th>
																	<th><?= __('event', 'Event') ?></th>
																	<th><?= __('available_balance') ?> (<?= __('before', 'Before') ?> &rarr; <?= __('after', 'After') ?>)</th>
																	<th><?= __('used_days') ?> (<?= __('before', 'Before') ?> &rarr; <?= __('after', 'After') ?>)</th>
																	<th><?= __('remaining_balance', 'Remaining') ?> (<?= __('before', 'Before') ?> &rarr; <?= __('after', 'After') ?>)</th>
																	<th><?= __('performed_by', 'Performed By') ?></th>
																	<th><?= __('notes') ?></th>
																</tr>
															</thead>
															<tbody>
																<?php
																$activity_log_reasons = [
																	'VACATION_APPLIED' => ['label' => __('applied', 'Applied'), 'badge' => 'info'],
																	'VACATION_APPROVED' => ['label' => __('approved', 'Approved'), 'badge' => 'success'],
																	'VACATION_REJECTED' => ['label' => __('rejected', 'Rejected'), 'badge' => 'danger'],
																	'VACATION_CANCELLED' => ['label' => __('cancelled', 'Cancelled'), 'badge' => 'warning'],
																	'VACATION_CANCELLED_ADMIN' => ['label' => __('cancelled', 'Cancelled') . ' (' . __('admin', 'Admin') . ')', 'badge' => 'warning'],
																	'VACATION_RETURN_EXTRA_DAYS' => ['label' => __('return_extra_days', 'Return - Extra Days'), 'badge' => 'dark'],
																	'VACATION_REJOIN_ADJUST_EXTRA_DAYS' => ['label' => __('rejoin_adjustment', 'Rejoin Adjustment'), 'badge' => 'dark'],
																	'MANUAL_VACATION_ENTRY' => ['label' => __('manual_entry', 'Manual Entry'), 'badge' => 'primary'],
																];

																// Dedicated table (sql/create_emp_vacation_activity_log.sql) - one row per
																// real vacation event (applied/approved/rejected/cancelled/returned/rejoin
																// adjustment/manual entry), tracked independently of the daily cron
																// balance snapshot (emp_vacation_balance_history), which is never queried here.
																$sql_activity_log = "SELECT l.*, pb.name AS performed_by_name
																                      FROM `emp_vacation_activity_log` l
																                      LEFT JOIN `employees` pb ON pb.emp_id = l.performed_by_emp_id
																                      WHERE l.emp_id = ?
																                      ORDER BY l.created_at DESC, l.id DESC LIMIT 200";
																$stmt_activity_log = mysqli_prepare($conDB, $sql_activity_log);
																if ($stmt_activity_log) {
																	mysqli_stmt_bind_param($stmt_activity_log, "s", $emprow['empid']);
																	mysqli_stmt_execute($stmt_activity_log);
																	$res_activity_log = mysqli_stmt_get_result($stmt_activity_log);
																	while ($log_row = mysqli_fetch_assoc($res_activity_log)) {
																		$reason_key = $log_row['action_type'] ?? '';
																		$reason_meta = $activity_log_reasons[$reason_key] ?? ['label' => $reason_key ?: '-', 'badge' => 'secondary'];
																		?>
																		<tr>
																			<td><?= $log_row['created_at'] ? date('d M Y H:i', strtotime($log_row['created_at'])) : '-'; ?></td>
																			<td><span class="badge badge-<?= $reason_meta['badge'] ?>"><?= htmlspecialchars($reason_meta['label']) ?></span></td>
																			<td><?= number_format((float)$log_row['old_available_balance'], 2) ?> &rarr; <?= number_format((float)$log_row['new_available_balance'], 2) ?></td>
																			<td><?= number_format((float)$log_row['old_used_days'], 2) ?> &rarr; <?= number_format((float)$log_row['new_used_days'], 2) ?></td>
																			<td><?= number_format((float)$log_row['old_remaining_balance'], 2) ?> &rarr; <?= number_format((float)$log_row['new_remaining_balance'], 2) ?></td>
																			<td><?= htmlspecialchars($log_row['performed_by_name'] ?? $log_row['performed_by_emp_id'] ?? '-') ?></td>
																			<td><?= htmlspecialchars($log_row['notes'] ?? '') ?></td>
																		</tr>
																		<?php
																	}
																	mysqli_free_result($res_activity_log);
																	mysqli_stmt_close($stmt_activity_log);
																	// No manual "no rows" placeholder row here on purpose - a colspan row
																	// has fewer <td> than the 7 <th> in the header, which breaks DataTables'
																	// column count check. DataTables shows its own emptyTable message
																	// (configured below in JS) automatically when tbody has zero rows.
																}
																?>
															</tbody>
														</table>
													</div>
												</div>

												<div class="card border-dark border mb-4">
													<div class="card-header bg-dark text-white font-weight-bold">
														<i class="mdi mdi-calendar-clock mr-1"></i><?= __('vacation_daily_balance_snapshot', 'Vacation Daily Balance Snapshot (Cron)') ?>
													</div>
													<div class="card-body table-responsive">
														<table id="employee_vac_cron_snapshot" class="table sr-table dt-responsive nowrap" style="border-collapse: collapse; border-spacing: 0; width: 100%;">
															<thead>
																<tr>
														<th><?= __('last_updated', 'Last Updated') ?></th>
																	<th><?= __('available_balance') ?> (<?= __('before', 'Before') ?> &rarr; <?= __('after', 'After') ?>)</th>
																	<th><?= __('used_days') ?></th>
																	<th><?= __('remaining_balance', 'Remaining') ?></th>
																	<th><?= __('status') ?></th>
																	<th><?= __('notes') ?></th>
																</tr>
															</thead>
															<tbody>
																<?php
																$sql_cron_snapshot = "SELECT * FROM `emp_vacation_balance_history`
																                       WHERE `emp_id` = ? AND `change_reason` IN ('CRON_DAILY_UPDATE', 'CRON_FORCE_UPDATE')
																                       ORDER BY `snapshot_time` DESC, `id` DESC LIMIT 200";
																$stmt_cron_snapshot = mysqli_prepare($conDB, $sql_cron_snapshot);
																if ($stmt_cron_snapshot) {
																	mysqli_stmt_bind_param($stmt_cron_snapshot, "s", $emprow['empid']);
																	mysqli_stmt_execute($stmt_cron_snapshot);
																	$res_cron_snapshot = mysqli_stmt_get_result($stmt_cron_snapshot);
																	while ($cron_row = mysqli_fetch_assoc($res_cron_snapshot)) {
																		$cron_changed = (int)($cron_row['balance_changed'] ?? 0) === 1;
																		$cron_last_updated = $cron_row['snapshot_time'] ?: $cron_row['snapshot_date'];
																		?>
																		<tr>
															<td><?= $cron_last_updated ? date('d M Y H:i', strtotime($cron_last_updated)) : '-'; ?></td>
																			<td><?= number_format((float)$cron_row['old_available_balance'], 2) ?> &rarr; <?= number_format((float)$cron_row['new_available_balance'], 2) ?></td>
																			<td><?= number_format((float)$cron_row['new_used_days'], 2) ?></td>
																			<td><?= number_format((float)$cron_row['new_remaining_balance'], 2) ?></td>
																			<td>
																				<?php if ((float)$cron_row['new_available_balance'] < 0): ?>
																					<span class="badge badge-danger"><?= __('negative', 'Negative') ?></span>
																				<?php elseif ($cron_changed): ?>
																					<span class="badge badge-warning"><?= __('changed', 'Changed') ?></span>
																				<?php else: ?>
																					<span class="badge badge-secondary"><?= __('refreshed', 'Refreshed') ?></span>
																				<?php endif; ?>
																			</td>
																			<td><?= htmlspecialchars($cron_row['notes'] ?? '') ?></td>
																		</tr>
																		<?php
																	}
																		mysqli_free_result($res_cron_snapshot);
																		mysqli_stmt_close($stmt_cron_snapshot);
																		// No manual "no rows" placeholder row - see comment on the activity log
																		// table above; DataTables' own emptyTable message covers this instead.
																	}
																	?>
															</tbody>
														</table>
													</div>
												</div>
											<?php } ?>
										</div>

										<div class="tab-pane" id="loan1">
											<?php if ($loan_summary):
												$loanTotal = (float)$loan_summary['total_payable'];
												$loanPaid = (float)$loan_summary['total_paid'];
												$loanPaidPct = $loanTotal > 0 ? max(0, min(100, round($loanPaid / $loanTotal * 100))) : 0;
												$loanFmtDate = function ($d) {
													return (!empty($d) && $d !== '0000-00-00') ? format_safe_date($d, 'd M, Y') : '-';
												};
											?>
												<!-- Active loan summary (new GUI) - IDs / classes are used by the edit-plan, deduction-mode and manual-payment scripts -->
												<div class="sr-card ve-loan">
													<div class="sr-card-head">
														<div>
															<div class="sr-card-title"><i class="mdi mdi-cash-multiple"></i> <?= __('active_loan_summary') ?></div>
															<div class="sr-card-sub">
																<span class="sr-mono"><?= htmlspecialchars($loan_summary['inv_no']) ?></span>
																<span class="sr-pill tone-sky"><span class="sr-dot"></span><?= htmlspecialchars(ucfirst(str_replace('_', ' ', __($loan_summary['loan_type'])))) ?></span>
															</div>
														</div>
														<span class="sr-pill <?= $loanPaidPct >= 100 ? 'tone-green' : 'tone-indigo' ?>"><span class="sr-dot"></span><?= $loanPaidPct ?>% <?= __('paid', 'paid') ?></span>
													</div>
													<div class="sr-card-body">
														<!-- Payment summary -->
														<div class="ve-loan-stats">
															<div class="ve-loan-stat">
																<span class="ve-loan-stat-label"><span class="sr-dot" style="background:var(--sr-accent)"></span><?= __('total_loan_amount') ?></span>
																<span class="ve-loan-stat-value"><?= number_format($loanTotal, 2) ?> <i class="icon-saudi_riyal"></i></span>
															</div>
															<div class="ve-loan-stat is-paid">
																<span class="ve-loan-stat-label"><span class="sr-dot"></span><?= __('total_paid') ?></span>
																<span class="ve-loan-stat-value"><?= number_format($loanPaid, 2) ?> <i class="icon-saudi_riyal"></i></span>
															</div>
															<div class="ve-loan-stat is-remaining">
																<span class="ve-loan-stat-label"><span class="sr-dot"></span><?= __('remaining_balance') ?></span>
																<span class="ve-loan-stat-value"><span id="remainingDisplay"><?= number_format($loan_summary['remaining_balance'], 2) ?></span> <i class="icon-saudi_riyal"></i></span>
															</div>
														</div>
														<div class="ve-loan-progress" title="<?= $loanPaidPct ?>%"><div style="width:<?= $loanPaidPct ?>%"></div></div>

														<div class="ve-loan-grid">
															<!-- Loan details -->
															<div class="ve-loan-box">
																<div class="ve-loan-box-title"><i class="mdi mdi-file-document"></i> <?= __('loan_details') ?></div>
																<dl class="sr-kv">
																	<div class="row-kv"><dt><?= __('invoice_number') ?></dt><dd class="sr-mono"><?= htmlspecialchars($loan_summary['inv_no']) ?></dd></div>
																	<div class="row-kv"><dt><?= __('approved_amount') ?></dt><dd><?= number_format($loan_summary['final_approved_amount'], 2) ?> <i class="icon-saudi_riyal"></i></dd></div>
																	<div class="row-kv"><dt><?= __('installments') ?></dt><dd><span id="installmentCount"><?= $loan_summary['installments'] ?></span> <?= __('months') ?></dd></div>
																	<div class="row-kv"><dt><?= __('monthly_deduction') ?></dt><dd class="ve-loan-amber"><span id="monthlyDeductionDisplay"><?= number_format($loan_summary['monthly_deduction'], 2) ?></span> <i class="icon-saudi_riyal"></i></dd></div>
																	<div class="row-kv"><dt><?= __('start_date') ?></dt><dd><?= $loanFmtDate($loan_summary['start_date'] ?? '') ?></dd></div>
																	<div class="row-kv"><dt><?= __('end_date') ?></dt><dd><?= $loanFmtDate($loan_summary['end_date'] ?? '') ?></dd></div>
																</dl>
															</div>

															<!-- Repayment settings + actions -->
															<div class="ve-loan-box">
																<div class="ve-loan-box-title"><i class="mdi mdi-tune"></i> <?= __('payment_summary') ?></div>
																<dl class="sr-kv">
																	<div class="row-kv">
																		<dt><?= __('deduction_mode') ?></dt>
																		<dd>
																			<select id="deductionModeSelect" class="form-control form-control-sm ve-loan-select" data-loan-id="<?= $loan_summary['loan_id'] ?>">
																				<option value="automatic" <?= ($loan_summary['deduction_mode'] ?? 'automatic') === 'automatic' ? 'selected' : '' ?>><?= __('automatic_monthly') ?></option>
																				<option value="manual" <?= ($loan_summary['deduction_mode'] ?? 'automatic') === 'manual' ? 'selected' : '' ?>><?= __('manual_addition') ?></option>
																			</select>
																		</dd>
																	</div>
																	<div class="row-kv">
																		<dt><?= __('installments_plan') ?></dt>
																		<dd>
																			<button type="button" class="sr-btn sr-btn-ghost sr-btn-sm editLoanInstallments"
																				data-loan-id="<?= $loan_summary['loan_id'] ?>"
																				data-installments="<?= $loan_summary['installments'] ?>"
																				data-monthly-deduction="<?= $loan_summary['monthly_deduction'] ?>"
																				data-remaining="<?= $loan_summary['remaining_balance'] ?>">
																				<i class="mdi mdi-pencil"></i> <?= __('edit_plan') ?>
																			</button>
																		</dd>
																	</div>
																	<?php if ($loan_summary['disbursement_receipt']): ?>
																		<div class="row-kv"><dt><?= __('disbursement_receipt_id') ?></dt><dd class="sr-mono"><?= htmlspecialchars($loan_summary['disbursement_receipt']); ?></dd></div>
																		<div class="row-kv">
																			<dt><?= __('disbursement_proof') ?></dt>
																			<dd><a href="./assets/loan_receipts/<?= htmlspecialchars($loan_summary['disbursement_attachment']); ?>" target="_blank" class="sr-btn sr-btn-ghost sr-btn-sm"><i class="mdi mdi-eye-outline"></i> <?= __('view_attachments', 'View Attachment') ?></a></dd>
																		</div>
																	<?php endif; ?>
																</dl>
																<?php if ($loan_summary['remaining_balance'] > 0): ?>
																	<button type="button" class="sr-btn sr-btn-success ve-loan-pay addManualPayment"
																		data-loan-id="<?= $loan_summary['loan_id'] ?>"
																		data-emp-id="<?= $emprow['empid'] ?>"
																		data-remaining="<?= $loan_summary['remaining_balance'] ?>">
																		<i class="mdi mdi-cash"></i> <?= __('add_manual_payment') ?>
																	</button>
																<?php endif; ?>
															</div>
														</div>
													</div>
												</div>
											<?php endif; ?>

											<?php
											$sql_emp_loan_active = "SELECT * FROM `emp_loan` WHERE `emp_id`='" . $emprow['empid'] . "' AND `status` = 'approved' ORDER BY `id` DESC LIMIT 1";
											$query_emp_loan_active = mysqli_query($conDB, $sql_emp_loan_active);
											$active_loan_rec = mysqli_fetch_array($query_emp_loan_active);
											?>
											<div class="d-flex justify-content-between align-items-center mb-3">
												<h4 class="header-title m-t-0"><?= __('loan_history') ?></h4>
												<?php if ($isHR || $is_system_admin || $isDeptHr || $isFinance): ?>
												<button type="button" class="btn btn-sm btn-primary addManualLoan" data-emp-id="<?= $emprow['empid'] ?>"
													<?php if ($loan_summary): ?>
													data-active-loan-id="<?= $loan_summary['loan_id'] ?>"
													data-active-loan-amount="<?= $loan_summary['loan_amount'] ?>"
													data-active-remaining="<?= $loan_summary['remaining_balance'] ?>"
													<?php endif; ?>>
													<i class="fa fa-plus"></i> <?= __('add_manual_loan', 'Add Manual Loan') ?>
												</button>
												<?php endif; ?>
											</div>
											<table id="loan_history_tbl" class="table sr-table dt-responsive nowrap" style="border-collapse: collapse; border-spacing: 0; width: 100%;">
												<thead>
													<tr>
														<th><?= __('loan_amount') ?></th>
														<th><?= __('monthly_deduction') ?></th>
														<th><?= __('remaining_balance') ?></th>
														<th><?= __('start_date') ?></th>
														<th><?= __('end_date') ?></th>
														<th><?= __('type') ?></th>
														<th><?= __('status') ?></th>
														<th><?= __('report') ?></th>
													</tr>
												</thead>
												<tbody>
													<?php
													// Build a single chronological list mixing loan records with manual
													// top-up events (emp_loan_topups), so a top-up shows as its own line
													// instead of silently vanishing into the updated loan row's numbers.
													$loan_history_rows = [];

													$sql_emp_loan = "SELECT * FROM `emp_loan` WHERE `emp_id`='" . $emprow['empid'] . "' ORDER BY `id` DESC";
													$query_emp_loan = mysqli_query($conDB, $sql_emp_loan);
													while ($loan_rec = mysqli_fetch_array($query_emp_loan)) {
														// Do not show legacy invoices in loan history
														if (isset($loan_rec['inv_no']) && strpos($loan_rec['inv_no'], 'LEGACY-') === 0) {
															continue;
														}
														$loan_history_rows[] = [
															'event' => 'loan',
															'sort_at' => $loan_rec['created_at'] ?? $loan_rec['start_date'],
															'rec' => $loan_rec,
														];
													}

													$sql_loan_topups = "SELECT t.*, l.inv_no, l.loan_type FROM `emp_loan_topups` t JOIN `emp_loan` l ON l.id = t.loan_id WHERE t.emp_id = '" . $emprow['empid'] . "' ORDER BY t.id DESC";
													$query_loan_topups = mysqli_query($conDB, $sql_loan_topups);
													if ($query_loan_topups) {
														while ($topup_rec = mysqli_fetch_assoc($query_loan_topups)) {
															$loan_history_rows[] = [
																'event' => 'topup',
																'sort_at' => $topup_rec['created_at'],
																'rec' => $topup_rec,
															];
														}
													}

													usort($loan_history_rows, function ($a, $b) {
														return strtotime($b['sort_at'] ?? '1970-01-01') <=> strtotime($a['sort_at'] ?? '1970-01-01');
													});

													foreach ($loan_history_rows as $history_row) {
														if ($history_row['event'] === 'topup') {
															$topup_rec = $history_row['rec'];
														?>
															<tr class="bg-light">
																<td class="font-weight-bold text-primary">+<?= number_format($topup_rec['additional_amount'], 2); ?> <i class="icon-saudi_riyal"></i></td>
																<td><?= number_format($topup_rec['new_monthly_deduction'], 2); ?> <i class="icon-saudi_riyal"></i></td>
																<td>-</td>
																<td><?= format_safe_date($topup_rec['created_at'], 'd, M Y H:i'); ?></td>
																<td>-</td>
																<td><span class="badge badge-info"><?= ucfirst(__($topup_rec['loan_type'])); ?></span></td>
																<td><span class="badge badge-primary"><i class="fa fa-arrow-up"></i> <?= __('manual_top_up', 'Manual Top-up') ?></span></td>
																<td><a href="./request_report.php?type=loan&id=<?= $topup_rec['loan_id'] ?>&emp_id=<?= $emprow['emp_id'] ?>" target="_blank" class="btn btn-sm btn-dark"><i class="fa fa-eye"></i> <?= __('view') ?></a></td>
															</tr>
														<?php
															continue;
														}

														$loan_rec = $history_row['rec'];
														// Calculate remaining balance for each loan
														$loan_id_hist = $loan_rec['id'];
														$total_payable_hist = $loan_rec['total_payable'];
														$sql_total_paid_hist = "SELECT COALESCE(SUM(amount), 0) as total_paid FROM `emp_loan_payments` WHERE `loan_id` = '$loan_id_hist'";
														$query_total_paid_hist = mysqli_query($conDB, $sql_total_paid_hist);
														$paid_rec_hist = mysqli_fetch_assoc($query_total_paid_hist);
														$total_paid_hist = $paid_rec_hist['total_paid'];
														$remaining_balance_hist = $total_payable_hist - $total_paid_hist;
													?>
														<tr>
															<td><?= number_format($loan_rec['loan_amount'], 2); ?> <i class="icon-saudi_riyal"></i></td>
															<td><?= number_format($loan_rec['monthly_deduction'], 2); ?> <i class="icon-saudi_riyal"></i></td>
															<td class="font-weight-bold <?= ($remaining_balance_hist > 0) ? 'text-danger' : 'text-success' ?>"><?= number_format($remaining_balance_hist, 2); ?> <i class="icon-saudi_riyal"></i></td>
															<td>
																<?php
																$loanRowStartDate = $loan_rec['start_date'] ?? '';
																echo (!empty($loanRowStartDate) && $loanRowStartDate !== '0000-00-00')
																	? format_safe_date($loanRowStartDate, 'd, M Y')
																	: '-';
																?>
															</td>
															<td>
																<?php
																$loanRowEndDate = $loan_rec['end_date'] ?? '';
																echo (!empty($loanRowEndDate) && $loanRowEndDate !== '0000-00-00')
																	? format_safe_date($loanRowEndDate, 'd, M Y')
																	: '-';
																?>
															</td>
															<td><span class="badge badge-<?= ($loan_rec['loan_type'] == 'emergency' ? 'warning' : 'info') ?>"><?= ucfirst(__($loan_rec['loan_type'])); ?></span></td>
															<td><span class="badge badge-<?= ($loan_rec['status'] == 'approved' ? 'success' : ($loan_rec['status'] == 'paid' ? 'primary' : ($loan_rec['status'] == 'rejected' ? 'danger' : 'warning'))) ?>"><?= ucfirst(__($loan_rec['status'])); ?></span></td>
															<td><a href="./request_report.php?type=loan&id=<?= $loan_id_hist ?>&emp_id=<?= $emprow['emp_id'] ?>" target="_blank" class="btn btn-sm btn-dark"><i class="fa fa-eye"></i> <?= __('view') ?></a></td>
														</tr>
													<?php } ?>
												</tbody>
											</table>

											<h4 class="header-title m-t-0 m-b-30 mt-4"><?= __('repayment_history') ?></h4>
											<table id="payment_history_tbl" class="table sr-table dt-responsive nowrap" style="border-collapse: collapse; border-spacing: 0; width: 100%;">
												<thead>
													<tr>
														<th><?= __('payment_date') ?></th>
														<th><?= __('amount') ?></th>
														<th><?= __('payment_method', 'Payment Method') ?></th>
														<th><?= __('receipt_id') ?></th>
														<th><?= __('attachment') ?></th>
														<th><?= __('note') ?></th>
														<th><?= __('loan_id') ?></th>
													</tr>
												</thead>
												<tbody>
													<?php
													$sql_loan_payments = "SELECT lp.* FROM `emp_loan_payments` lp JOIN `emp_loan` l ON lp.loan_id = l.id WHERE l.emp_id = '" . $emprow['empid'] . "' ORDER BY lp.payment_date DESC";
													$query_loan_payments = mysqli_query($conDB, $sql_loan_payments);
													while ($payment_rec = mysqli_fetch_array($query_loan_payments)) {
														$payment_method = $payment_rec['payment_method'] ?? 'auto';
														$payment_method_badge = '';
														switch ($payment_method) {
															case 'manual':
																$payment_method_badge = '<span class="badge badge-success"><i class="fa fa-hand-paper-o"></i> ' . __('manual') . '</span>';
																break;
															case 'payroll':
																$payment_method_badge = '<span class="badge badge-primary"><i class="fa fa-calendar"></i> ' . __('payroll') . '</span>';
																break;
															default:
																$payment_method_badge = '<span class="badge badge-info"><i class="fa fa-cog"></i> ' . __('auto', 'Auto') . '</span>';
														}
													?>
														<tr>
															<td><?= format_safe_date($payment_rec['payment_date'] ?? null, 'd, M Y'); ?></td>
															<td class="font-weight-bold text-success"><?= number_format($payment_rec['amount'], 2); ?> <i class="icon-saudi_riyal"></i></td>
															<td><?= $payment_method_badge; ?></td>
															<td><?= !empty($payment_rec['receipt_id']) ? htmlspecialchars($payment_rec['receipt_id']) : '<span class="text-muted">' . __('not_available', 'N/A') . '</span>'; ?></td>
															<td>
																<?php if (!empty($payment_rec['attachment'])):
																	// Determine file path based on payment method
																	if ($payment_method === 'manual') {
																		$file_path = './assets/loan_manual_payments/' . htmlspecialchars($payment_rec['attachment']);
																	} else {
																		$file_path = './assets/loan_receipts/' . htmlspecialchars($payment_rec['attachment']);
																	}
																?>
																	<a href="<?= $file_path; ?>" target="_blank" class="btn btn-sm btn-info"><i class="fa fa-eye"></i> <?= __('view') ?></a>
																<?php else: ?>
																	<span class="text-muted"><?= __('not_available', 'N/A') ?></span>
																<?php endif; ?>
															</td>
															<td>
																<?php if (!empty($payment_rec['note'])): ?>
																	<span class="text-muted" data-toggle="tooltip" title="<?= htmlspecialchars($payment_rec['note']); ?>">
																		<i class="fa fa-comment"></i> <?= substr(htmlspecialchars($payment_rec['note']), 0, 30); ?><?= strlen($payment_rec['note']) > 30 ? '...' : ''; ?>
																	</span>
																<?php else: ?>
																	<span class="text-muted">-</span>
																<?php endif; ?>
															</td>
															<td><?= $payment_rec['loan_id']; ?></td>
														</tr>
													<?php } ?>
												</tbody>
											</table>
										</div>

										<?php if ($canViewSalary): ?>
										<div class="tab-pane" id="payroll1">
											<h4 class="header-title m-t-0 m-b-30"><?= __('payroll_history', 'Payroll History') ?></h4>
											<table id="payroll_history_tbl" class="table sr-table dt-responsive nowrap" style="width: 100%;">
												<thead>
													<tr>
														<th><?= __('month', 'Month') ?></th>
														<th><?= __('total_gross_salary', 'Total Gross Salary') ?></th>
														<th><?= __('total_benefits', 'Additional Benefits') ?></th>
														<th><?= __('total_deductions', 'Total Deductions') ?></th>
														<th><?= __('net_salary', 'Net Salary') ?></th>
														<?php if ($d365_pay_state !== null): ?>
															<th title="Dynamics 365 (<?= htmlspecialchars($d365_pay_env) ?>)">D365</th>
														<?php endif; ?>
														<th><?= __('actions', 'Actions') ?></th>
													</tr>
												</thead>
												<tbody>
													<?php foreach ($payroll_history as $payroll_rec): ?>
														<?php $month_benefits = $payroll_benefits_by_month[$payroll_rec['month_year']] ?? []; ?>
														<tr>
															<td><?= date('F Y', strtotime($payroll_rec['month_year'] . '-01')); ?></td>
															<td><?= number_format((float)$payroll_rec['total_gross_salary'], 2); ?> <i class="icon-saudi_riyal"></i></td>
															<td>
																<?= number_format((float)$payroll_rec['total_benefits'], 2); ?> <i class="icon-saudi_riyal"></i>
																<?php if (!empty($month_benefits)): ?>
																	<br>
																	<?php foreach ($month_benefits as $benefit_item): ?>
																		<span class="badge badge-light border d-inline-block mt-1 mr-1" style="font-weight: 400;">
																			<?= htmlspecialchars($benefit_item['benefit']); ?>: <?= number_format((float)$benefit_item['note'], 2); ?> <i class="icon-saudi_riyal"></i>
																		</span>
																	<?php endforeach; ?>
																<?php endif; ?>
															</td>
															<td><?= number_format((float)$payroll_rec['total_deductions'], 2); ?> <i class="icon-saudi_riyal"></i></td>
															<td class="font-weight-bold text-success"><?= number_format((float)$payroll_rec['net_salary'], 2); ?> <i class="icon-saudi_riyal"></i></td>
															<?php if ($d365_pay_state !== null):
																$d365_row = $d365_pay_state[$payroll_rec['month_year']] ?? null;
																$d365_rs = $d365_row['status'] ?? '';
															?>
																<td class="d365-pay-cell" data-month="<?= htmlspecialchars($payroll_rec['month_year']) ?>">
																	<?php if ($d365_rs === 'ok'): ?>
																		<span class="badge badge-success" title="<?= htmlspecialchars('Journal ' . $d365_row['journal_batch'] . ' · ' . $d365_row['pushed_at']) ?>">
																			<i class="fa fa-check"></i> <?= htmlspecialchars($d365_row['legal_entity'] . ' · ' . $d365_row['journal_batch']) ?>
																		</span>
																	<?php else: ?>
																		<?php if ($d365_rs === 'error'): ?>
																			<span class="badge badge-danger" title="<?= htmlspecialchars((string)$d365_row['error']) ?>"><i class="fa fa-times"></i> <?= __('failed_status', 'Failed') ?></span>
																		<?php elseif ($d365_rs === 'removed'): ?>
																			<span class="badge badge-warning" title="<?= htmlspecialchars(__('d365_journal_deleted', 'The journal was deleted in D365')) ?>"><?= __('removed_status', 'Removed') ?></span>
																		<?php else: ?>
																			<span class="badge badge-secondary"><?= __('not_synced', 'Not synced') ?></span>
																		<?php endif; ?>
																		<?php if ($can_d365_sync): ?>
																		<button type="button" class="btn btn-sm btn-primary btn-d365-pay ml-1" data-month="<?= htmlspecialchars($payroll_rec['month_year']) ?>"><i class="fa fa-cloud-upload-alt"></i> <?= __('sync_to_d365', 'Sync to D365') ?></button>
																		<?php endif; ?>
																	<?php endif; ?>
																</td>
															<?php endif; ?>
															<td>
																<a href="./generate_bulk_payslips_pdf.php?month=<?= urlencode($payroll_rec['month_year']); ?>&emp_id=<?= urlencode($emprow['empid']); ?>" target="_blank" class="btn btn-sm btn-info">
																	<i class="fas fa-file-pdf"></i> <?= __('download_payslip', 'Download Payslip') ?>
																</a>
															</td>
														</tr>
													<?php endforeach; ?>
												</tbody>
											</table>
										</div>
										<?php endif; ?>
										<?php if ($canViewAdditionalInfo || $canViewOtherIncome): ?>

										<div class="tab-pane" id="additionalinfo1">
											<!-- Same two-column card layout as the Profile tab -->
											<div class="profile-content-wrapper">
											<?php if ($canViewAdditionalInfo): ?>
											<div class="profile-section ps-additional">
												<div class="profile-section-header">
													<span class="psh-icon"><i class="fa fa-circle-info"></i></span>
													<span><?= __('additional_information', 'Additional Information') ?></span>
													<?php if ($canEditAdditionalInfo): ?>
													<button type="button" class="psh-btn editAdditionalInfoBtn" data-emp-id="<?= htmlspecialchars($emprow['empid']); ?>">
														<i class="fa fa-pen"></i> <?= __('edit', 'Edit') ?>
													</button>
													<?php endif; ?>
												</div>
												<div class="profile-section-body">
													<div class="profile-grid" id="additionalInfoDisplay">
														<div class="profile-field">
															<div class="profile-field-label"><i class="fa fa-layer-group"></i><?= __('salary_grade', 'Salary Grade') ?></div>
															<div class="profile-field-value" data-field="salary_grade"><?= !empty($employee_additional_info['salary_grade']) ? __('grade', 'Grade') . ' ' . htmlspecialchars($employee_additional_info['salary_grade']) : __('not_available') ?></div>
														</div>
														<div class="profile-field">
															<div class="profile-field-label"><i class="fa fa-people-roof"></i><?= __('dependants_count', 'No. of Dependants') ?></div>
															<div class="profile-field-value" data-field="dependants_count"><?= display_or_na($employee_additional_info['dependants_count'] ?? null) ?></div>
														</div>
														<div class="profile-field">
															<div class="profile-field-label"><i class="fa fa-plane-departure"></i><?= __('ticket_fare', 'Ticket') ?></div>
															<div class="profile-field-value" data-field="ticket_fare"><?= isset($employee_additional_info['ticket_fare']) ? number_format((float)$employee_additional_info['ticket_fare'], 2) . ' <i class="icon-saudi_riyal"></i>' : __('not_available') ?></div>
														</div>
														<?php if ((int)($emprow['country'] ?? 0) !== 191): ?>
														<div class="profile-field">
															<div class="profile-field-label"><i class="fa fa-briefcase"></i><?= __('labour_office_expense', 'Labour Office Expenses') ?></div>
															<div class="profile-field-value" data-field="labour_office_expense"><?= isset($employee_additional_info['labour_office_expense']) ? number_format((float)$employee_additional_info['labour_office_expense'], 2) . ' <i class="icon-saudi_riyal"></i>' : __('not_available') ?></div>
														</div>
														<div class="profile-field">
															<div class="profile-field-label"><i class="fa fa-id-card"></i><?= __('iqama_renewal_fee', 'Iqama Renewal Fee') ?></div>
															<div class="profile-field-value" data-field="iqama_renewal_fee"><?= isset($employee_additional_info['iqama_renewal_fee']) ? number_format((float)$employee_additional_info['iqama_renewal_fee'], 2) . ' <i class="icon-saudi_riyal"></i>' : __('not_available') ?></div>
														</div>
														<div class="profile-field">
															<div class="profile-field-label"><i class="fa fa-people-arrows"></i><?= __('citizen_local_relation', 'Citizen (Local)') ?></div>
															<div class="profile-field-value" data-field="citizen_local_relation"><?= display_or_na($employee_additional_info['citizen_local_relation'] ?? null) ?></div>
														</div>
														<?php endif; ?>
														<div class="profile-field">
															<div class="profile-field-label"><i class="fa fa-calendar-plus"></i><?= __('monthly_leave_accrual', 'Monthly Leave Accrual') ?> <small class="text-muted">(<?= __('auto_calculated', 'auto-calculated') ?>)</small></div>
															<div class="profile-field-value"><?= $monthly_leave_accrual_calculated !== null ? number_format($monthly_leave_accrual_calculated, 2) . ' ' . __('day_s', 'Days') : __('not_available') ?></div>
														</div>
														<div class="profile-field">
															<div class="profile-field-label"><i class="fa fa-helmet-safety"></i><?= __('eng_council_fee', 'Saudi Engineering Council Fee') ?></div>
															<div class="profile-field-value" data-field="eng_council_fee"><?= isset($employee_additional_info['eng_council_fee']) ? number_format((float)$employee_additional_info['eng_council_fee'], 2) . ' <i class="icon-saudi_riyal"></i>' : __('not_available') ?></div>
														</div>
														<div class="profile-field">
															<div class="profile-field-label"><i class="fa fa-calendar-xmark"></i><?= __('eng_council_expiry', 'Saudi Engineering Council Expiry') ?></div>
															<div class="profile-field-value" data-field="eng_council_expiry"><?= (!empty($employee_additional_info['eng_council_expiry']) && $employee_additional_info['eng_council_expiry'] !== '0000-00-00') ? format_safe_date($employee_additional_info['eng_council_expiry'], 'd M, Y') : __('not_available') ?></div>
														</div>
													</div>
												</div>
											</div>

										<div class="profile-section ps-medical">
											<div class="profile-section-header">
												<span class="psh-icon"><i class="fa fa-shield-heart"></i></span>
												<span><?= __('medical_insurance', 'Medical Insurance') ?> <small class="psh-sub">(<?= __('renews_yearly', 'renews yearly') ?>)</small></span>
												<?php if ($canEditAdditionalInfo): ?>
												<button type="button" class="psh-btn addMedicalInsuranceBtn" data-emp-id="<?= htmlspecialchars($emprow['empid']); ?>">
													<i class="fa fa-plus"></i> <?= __('add_insurance', 'Add Insurance Details') ?>
												</button>
												<?php endif; ?>
											</div>
											<div class="profile-section-body">
												<div class="profile-grid">
													<div class="profile-field">
														<div class="profile-field-label"><i class="fa fa-notes-medical"></i><?= __('insurance_no', 'Insurance No') ?></div>
														<div class="profile-field-value"><?= display_or_na($current_medical_insurance['insurance_no'] ?? null) ?></div>
													</div>
													<div class="profile-field">
														<div class="profile-field-label"><i class="fa fa-sack-dollar"></i><?= __('med_insurance', 'Medical Insurance') ?></div>
														<div class="profile-field-value"><?= isset($current_medical_insurance['med_insurance']) ? number_format((float)$current_medical_insurance['med_insurance'], 2) . ' <i class="icon-saudi_riyal"></i>' : __('not_available') ?></div>
													</div>
													<div class="profile-field">
														<div class="profile-field-label"><i class="fa fa-calendar-xmark"></i><?= __('medical_expiry', 'Medical Expiry') ?></div>
														<div class="profile-field-value"><?= (!empty($current_medical_insurance['medical_expiry']) && $current_medical_insurance['medical_expiry'] !== '0000-00-00') ? format_safe_date($current_medical_insurance['medical_expiry'], 'd M, Y') : __('not_available') ?></div>
													</div>
													<div class="profile-field">
														<div class="profile-field-label"><i class="fa fa-star"></i><?= __('medical_class', 'Medical Class') ?></div>
														<div class="profile-field-value"><?= display_or_na($current_medical_insurance['medical_class'] ?? null) ?></div>
													</div>
												</div>
												<?php if (count($medical_insurance_records) > 1): ?>
										<h6 class="psh-subtitle"><i class="fa fa-clock-rotate-left"></i> <?= __('insurance_history', 'Insurance History') ?></h6>
										<div class="sr-table-wrap"><table class="table sr-table" style="width: 100%;">
											<thead>
												<tr>
													<th><?= __('insurance_no', 'Insurance No') ?></th>
													<th><?= __('med_insurance', 'Medical Insurance') ?></th>
													<th><?= __('medical_expiry', 'Medical Expiry') ?></th>
													<th><?= __('medical_class', 'Medical Class') ?></th>
													<th><?= __('status', 'Status') ?></th>
													<th><?= __('added_on', 'Added On') ?></th>
												</tr>
											</thead>
											<tbody>
												<?php foreach ($medical_insurance_records as $mi_row): ?>
												<tr>
													<td><?= display_or_na($mi_row['insurance_no']) ?></td>
													<td><?= isset($mi_row['med_insurance']) ? number_format((float)$mi_row['med_insurance'], 2) . ' <i class="icon-saudi_riyal"></i>' : __('not_available') ?></td>
													<td><?= (!empty($mi_row['medical_expiry']) && $mi_row['medical_expiry'] !== '0000-00-00') ? format_safe_date($mi_row['medical_expiry'], 'd M, Y') : __('not_available') ?></td>
													<td><?= display_or_na($mi_row['medical_class']) ?></td>
													<td>
														<?php if ($mi_row['status'] === 'active'): ?>
														<span class="badge badge-success"><?= __('active', 'Active') ?></span>
														<?php else: ?>
														<span class="badge badge-secondary"><?= __('expired', 'Expired') ?></span>
														<?php endif; ?>
													</td>
													<td><?= format_safe_date($mi_row['created_at'], 'd M, Y') ?></td>
												</tr>
												<?php endforeach; ?>
											</tbody>
										</table></div>
										<?php endif; ?>
											</div>
										</div>

										
										<?php endif; // $canViewAdditionalInfo ?>
										<?php if ($canViewOtherIncome): ?>
										<div class="profile-section ps-income">
											<div class="profile-section-header">
												<span class="psh-icon"><i class="fa fa-coins"></i></span>
												<span><?= __('other_income', 'Other Income') ?> <small class="psh-sub">(<?= __('scheduled_months', 'scheduled months') ?>)</small></span>
												<?php if ($canEditOtherIncome): ?>
												<button type="button" class="psh-btn addOtherIncomeBtn" data-emp-id="<?= htmlspecialchars($emprow['empid']); ?>">
													<i class="fa fa-plus"></i> <?= __('add_other_income', 'Add Other Income') ?>
												</button>
												<?php endif; ?>
											</div>
											<div class="profile-section-body">
										<?php if (!empty($other_income_records)): ?>
										<?php $has_inactive_other_income = false; foreach ($other_income_records as $oi_row) { if ((int)$oi_row['status'] !== 1) { $has_inactive_other_income = true; break; } } ?>
										<?php if ($has_inactive_other_income): ?>
										<div class="form-check mb-2">
											<input type="checkbox" class="form-check-input" id="oiShowInactive">
											<label class="form-check-label" for="oiShowInactive"><?= __('show_inactive', 'Show Inactive') ?></label>
										</div>
										<?php endif; ?>
										<div class="sr-table-wrap"><table class="table sr-table" style="width: 100%;">
											<thead>
												<tr>
													<th><?= __('other_income_title', 'Title') ?></th>
													<th><?= __('amount', 'Amount') ?></th>
													<th><?= __('start_month', 'Start Month') ?></th>
													<th><?= __('end_month', 'End Month') ?></th>
													<th><?= __('status', 'Status') ?></th>
													<?php if ($canEditOtherIncome): ?><th><?= __('action', 'Action') ?></th><?php endif; ?>
												</tr>
											</thead>
											<tbody>
												<?php foreach ($other_income_records as $oi_row): ?>
												<tr<?= (int)$oi_row['status'] !== 1 ? ' class="oi-inactive-row" style="display:none;"' : '' ?>>
													<td><?= htmlspecialchars($oi_row['title']) ?></td>
													<td><?= number_format((float)$oi_row['amount'], 2) ?> <i class="icon-saudi_riyal"></i></td>
													<td><?= htmlspecialchars($oi_row['start_month']) ?></td>
													<td><?= htmlspecialchars($oi_row['end_month']) ?></td>
													<td>
														<?php if ((int)$oi_row['status'] === 1): ?>
														<span class="badge badge-success"><?= __('active', 'Active') ?></span>
														<?php else: ?>
														<span class="badge badge-secondary"><?= __('inactive', 'Inactive') ?></span>
														<?php endif; ?>
													</td>
													<?php if ($canEditOtherIncome): ?>
													<td>
														<?php if ((int)$oi_row['status'] === 1): ?>
														<button type="button" class="btn btn-sm btn-outline-danger deactivateOtherIncomeBtn" data-id="<?= (int)$oi_row['id'] ?>" data-emp-id="<?= htmlspecialchars($emprow['empid']); ?>">
															<i class="mdi mdi-close"></i> <?= __('deactivate', 'Deactivate') ?>
														</button>
														<?php endif; ?>
													</td>
													<?php endif; ?>
												</tr>
												<?php endforeach; ?>
											</tbody>
										</table></div>
										<?php else: ?>
										<p class="psh-empty"><i class="fa fa-inbox"></i> <?= __('no_other_income_scheduled', 'No scheduled other income records.') ?></p>
										<?php endif; ?>
											</div>
										</div>
										<?php endif; // $canViewOtherIncome ?>
											</div><!-- /.profile-content-wrapper -->
										</div>
										<?php endif; // $canViewAdditionalInfo || $canViewOtherIncome ?>
										<div class="tab-pane" id="assets">
											<h4 class="header-title m-t-0 m-b-30 mt-4"><?= __('assigned_assets') ?></h4>
											<table id="assets_tbl" class="table sr-table dt-responsive nowrap" style="width: 100%;">
												<thead>
													<tr>
														<th><?= __('asset_type') ?></th>
														<th><?= __('serial_number') ?></th>
														<th><?= __('assigned_date') ?></th>
														<th><?= __('status') ?></th>
														<th><?= __('return_date') ?></th>
														<th><?= __('action') ?></th>
													</tr>
												</thead>
												<tbody>
													<?php
													$sql_assets = "SELECT ea.*, a.name as asset_name 
                                                                 FROM `employee_assets` ea 
                                                                 JOIN `assets` a ON ea.asset_id = a.id 
                                                                 WHERE ea.emp_id = '{$emprow['empid']}' 
                                                                 ORDER BY ea.assigned_date DESC";
													$query_assets = mysqli_query($conDB, $sql_assets);
													while ($asset_rec = mysqli_fetch_array($query_assets)) {
													?>
														<tr>
															<td><?= getDisplayName($asset_rec['asset_name']); ?></td>
															<td><?= htmlspecialchars($asset_rec['serial_number']); ?></td>
															<td><?= format_safe_date($asset_rec['assigned_date'] ?? null, 'd, M Y'); ?></td>
															<td>
																<span class="badge badge-<?= ($asset_rec['status'] == 'Assigned' ? 'success' : ($asset_rec['status'] == 'Lost' ? 'danger' : ($asset_rec['status'] == 'Damaged' ? 'warning' : 'secondary'))) ?>">
																	<?= __(strtolower($asset_rec['status'])); ?>
																</span>
															</td>
															<td><?= $asset_rec['return_date'] ? format_safe_date($asset_rec['return_date'], 'd, M Y') : __(strtolower('N/A')); ?></td>
															<td>
																<?php if ($asset_rec['status'] == 'Assigned'): ?>
																	<div class="btn-group">
																		<a href="asset_return_report.php?asset_id=<?= $asset_rec['id'] ?>" target="_blank" class="btn btn-sm btn-primary print-return-btn" data-asset-id="<?= $asset_rec['id'] ?>"><?= __('print_for_return') ?></a>
																		<button id="submit-return-btn-<?= $asset_rec['id'] ?>" class="btn btn-sm btn-danger waves-effect" onclick="unassignAsset(<?= $asset_rec['id'] ?>)" disabled>
																			<?= __('submit_return') ?>
																		</button>
																	</div>
																<?php else: ?>
																	<div class="btn-group">
																		<a href="asset_return_report.php?asset_id=<?= $asset_rec['id'] ?>" target="_blank" class="btn btn-sm btn-primary"><?= __('print_report') ?></a>
																		<?php if (!empty($asset_rec['return_attachment'])): ?>
																			<a href="<?= htmlspecialchars($asset_rec['return_attachment']) ?>" target="_blank" class="btn btn-sm btn-info"><?= __('view_proof') ?></a>
																		<?php endif; ?>
																	</div>
																<?php endif; ?>
															</td>
														</tr>
													<?php } ?>
												</tbody>
											</table>
										</div>


										<div class="tab-pane emp-docs" id="documents">
											<link href="assets/css/emp_docs.css?v=<?= @filemtime(__DIR__ . '/assets/css/emp_docs.css') ?>" rel="stylesheet" type="text/css">
											<?php
											require_once __DIR__ . '/includes/emp_doc_kind.php';
											$queryempdocu = mysqli_query($conDB, "SELECT * FROM `emp_docu` WHERE `emp_id`='" . $emprow['empid'] . "' ORDER BY `id` DESC ");
											$doc_count = mysqli_num_rows($queryempdocu);
											?>
											<div class="card-box documents-card">
												<div class="docs-head">
													<span class="docs-head-icon"><i class="fa fa-file-shield"></i></span>
													<div class="docs-head-text">
														<h5><?= __('employee_documents', 'Employee Documents') ?></h5>
														<small><?= __('click_document_to_preview', 'Click a document to preview it') ?></small>
													</div>
													<div class="docs-head-actions">
													<?php // Upload also here (not only in More Actions) so inactive / EOS employees can still get
													// documents like the signed EOS report. Same roles as More Actions in emp_top_info.php.
													if ($isDeptHr || $isHR || $is_system_admin || $user_type === 'archiving'): ?>
														<button type="button" class="docs-upload addEmpDocuAtter doc-tab-upload" data-id="<?= htmlspecialchars($emprow['eid']) ?>" data-emp_id="<?= htmlspecialchars($emprow['empid']) ?>"><i class="fa fa-solid fa-upload"></i> <?= __('add_documents') ?></button>
													<?php endif; ?>
														<span class="docs-count"><i class="fa fa-paperclip"></i> <?= (int)$doc_count ?></span>
													</div>
												</div>

												<?php if ($doc_count > 0): ?>
													<?php
													// Group documents by type for the filter chips; size read from disk for the meta line
													$vdDocs = [];
													$vdTypes = [];
													mysqli_data_seek($queryempdocu, 0);
													while ($recempdoc = mysqli_fetch_assoc($queryempdocu)) {
														$vdExt = strtolower((string)$recempdoc['docu_ext']);
														$vdType = trim((string)$recempdoc['docu_typ']) !== '' ? trim((string)$recempdoc['docu_typ']) : strtoupper($vdExt);
														$vdKey = strtolower($vdType);
														$vdTypes[$vdKey] = ['label' => getDisplayName($vdType), 'count' => ($vdTypes[$vdKey]['count'] ?? 0) + 1];
														$vdFile = __DIR__ . '/assets/emp_documents/' . $recempdoc['path'];
														$vdSize = is_file($vdFile) ? filesize($vdFile) : null;
														$recempdoc['_ext'] = $vdExt;
														$recempdoc['_type'] = $vdType;
														$recempdoc['_key'] = $vdKey;
														$recempdoc['_size'] = $vdSize === null ? '' : ($vdSize >= 1048576 ? number_format($vdSize / 1048576, 1) . ' MB' : max(1, round($vdSize / 1024)) . ' KB');
														$recempdoc['_missing'] = $vdSize === null;
														$vdDocs[] = $recempdoc;
													}
													$vdImageExt = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
													?>
													<div class="vd-toolbar">
														<div class="vd-chips" role="tablist">
															<button type="button" class="vd-chip active" data-vd-filter=""><?= __('all', 'All') ?> <span><?= (int)$doc_count ?></span></button>
															<?php foreach ($vdTypes as $vdKey => $vdT): ?>
																<button type="button" class="vd-chip" data-vd-filter="<?= htmlspecialchars($vdKey) ?>"><?= htmlspecialchars($vdT['label']) ?> <span><?= (int)$vdT['count'] ?></span></button>
															<?php endforeach; ?>
														</div>
														<div class="vd-search"><i class="fa fa-magnifying-glass"></i><input type="search" id="vdSearch" placeholder="<?= __('search', 'Search') ?>" autocomplete="off"></div>
													</div>
													<div class="vd-grid">
														<?php foreach ($vdDocs as $vd):
															$vdKind = emp_doc_kind($vd['docu_typ'], $vd['_ext']);
															$vdPath = './assets/emp_documents/' . $vd['path'];
															$vdName = getDisplayName($vd['_type']);
															$vdDate = date('d M Y', strtotime((string)$vd['created_at']));
															$vdIsImg = in_array($vd['_ext'], $vdImageExt, true) && !$vd['_missing'];
														?>
														<div class="vd-card<?= $vd['_missing'] ? ' is-missing' : '' ?>" style="--doc: <?= $vdKind[1] ?>;"
															data-doc-path="<?= htmlspecialchars($vdPath) ?>" data-doc-ext="<?= htmlspecialchars($vd['_ext']) ?>"
															data-doc-name="<?= htmlspecialchars($vdName) ?>" data-doc-type="<?= htmlspecialchars($vd['_key']) ?>"
															data-doc-search="<?= htmlspecialchars(mb_strtolower($vdName . ' ' . $vd['_type'] . ' ' . $vd['_ext'] . ' ' . $vdDate)) ?>">
															<button type="button" class="vd-thumb vd-open" title="<?= __('view', 'View') ?>">
																<?php if ($vdIsImg): ?>
																	<img src="<?= htmlspecialchars($vdPath) ?>" alt="" loading="lazy">
																<?php else: ?>
																	<span class="vd-thumb-icon"><i class="fa <?= $vdKind[0] ?>"></i></span>
																<?php endif; ?>
																<span class="vd-ext"><?= htmlspecialchars(strtoupper($vd['_ext'])) ?></span>
																<span class="vd-thumb-hover"><i class="fa fa-eye"></i> <?= __('preview', 'Preview') ?></span>
															</button>
															<div class="vd-body">
																<h6 class="vd-name" title="<?= htmlspecialchars($vdName) ?>"><?= htmlspecialchars($vdName) ?></h6>
																<div class="vd-meta">
																	<span><i class="fa fa-calendar"></i> <?= $vdDate ?></span>
																	<?php if ($vd['_missing']): ?>
																		<span class="vd-missing"><i class="fa fa-triangle-exclamation"></i> <?= __('file_not_found', 'File not found') ?></span>
																	<?php elseif ($vd['_size'] !== ''): ?>
																		<span><i class="fa fa-hard-drive"></i> <?= $vd['_size'] ?></span>
																	<?php endif; ?>
																</div>
															</div>
															<div class="vd-actions">
																<button type="button" class="vd-btn vd-btn-main vd-open"><i class="fa fa-eye"></i> <?= __('view', 'View') ?></button>
																<a class="vd-btn" href="./downloadFile.php?file=<?= htmlspecialchars(rawurlencode($vdPath)) ?>" title="<?= __('download', 'Download') ?>"><i class="fa fa-download"></i></a>
																<?php if ($canDeleteEmpDocs): ?>
																<button type="button" class="vd-btn vd-btn-del deleteAjax" data-id="<?= (int)$vd['id'] ?>" data-tbl="emp_docu" data-file="1" data-column="path" title="<?= __('delete') ?>"><i class="fa fa-trash"></i></button>
																<?php endif; ?>
															</div>
														</div>
														<?php endforeach; ?>
													</div>
													<div class="vd-noresult" hidden><i class="fa fa-magnifying-glass"></i> <?= __('no_matching_documents', 'No matching documents') ?></div>
												<?php else: ?>
													<div class="docs-empty"><i class="fa fa-folder-open"></i><b><?= __('no_documents_found') ?></b><?= __('no_documents_have_been_uploaded_yet') ?></div>
												<?php endif; ?>
											</div>
										</div>
										<div class="tab-pane" id="memos">
											<?php if ($canViewMemosTab): ?>
											<div class="card-box">
												<div class="d-flex justify-content-between align-items-center m-b-20">
													<h4 class="header-title mb-0"><?= __('memos', 'Memos') ?></h4>
													<div class="ve-head-tools">
														<div class="sr-search"><i class="mdi mdi-magnify"></i><input type="search" data-dt-search="#memos_tbl" placeholder="<?= __('search', 'Search') ?>" autocomplete="off"></div>
														<a href="employee_memos.php?emp_id=<?= urlencode($emprow['emp_id']) ?>" class="btn btn-sm btn-primary"><i class="mdi mdi-send"></i> <?= __('send_memo', 'Send Memo') ?></a>
													</div>
												</div>
												<table id="memos_tbl" class="table sr-table dt-responsive nowrap" style="width: 100%;">
													<thead>
														<tr>
															<th><?= __('date') ?></th>
															<th><?= __('memo_type', 'Memo Type') ?></th>
															<th><?= __('subject', 'Subject') ?></th>
															<th><?= __('reference_no', 'Reference No.') ?></th>
															<th><?= __('status') ?></th>
															<th><?= __('sent_by', 'Sent By') ?></th>
															<th><?= __('action') ?></th>
														</tr>
													</thead>
												</table>
											</div>
											<?php endif; ?>
											<div class="card-box">
												<h4 class="header-title m-b-30"><?= __('all_notes') ?></h4>
												<table id="notes_tbl" class="table sr-table dt-responsive nowrap" style="border-collapse: collapse; border-spacing: 0; width: 100%;"></table>
											</div>
										</div>


										<?php /*}*/ ?>
										<?php if ($canViewAttendanceTab): ?>
										<div class="tab-pane" id="attendance">
											<div class="card-box att-tab">

												<div class="att-tab-head">
													<div class="att-tab-title">
														<h4 class="header-title mb-1"><i class="mdi mdi-fingerprint"></i> <?= __('attendance_record', 'Attendance Record') ?></h4>
														<p class="att-tab-sub" id="attTabPeriod">&nbsp;</p>
													</div>
													<?php if ($canAddAttendanceRecord): ?>
													<div class="att-tab-actions">
														<button type="button" class="sr-btn sr-btn-primary" id="btn-add-attendance-record"><i class="mdi mdi-plus"></i> <?= __('add_record', 'Add Record') ?></button>
													</div>
													<?php endif; ?>
												</div>

												<!-- Same status tiles + date filter as attendance.php (includes/attendance_ui.php) -->
												<?= attendance_tiles_html('attStats', attendance_total_tile_html()) /* + Total hours of the rows shown */ ?>

												<div class="att-toolbar">
													<div class="sr-search">
														<i class="mdi mdi-magnify"></i>
														<input type="search" id="attTabSearch" placeholder="<?= __('search') ?>..." autocomplete="off" aria-label="<?= __('search') ?>">
													</div>
													<div class="att-toolbar-end">
														<?= attendance_datefilter_html(['range' => 'attTabRange', 'from' => 'attTabFrom', 'to' => 'attTabTo'], 'month') ?>
														<div class="att-export" id="attExport"></div>
													</div>
												</div>

												<div class="sr-table-wrap">
												<table id="attendance_tbl" class="table sr-table dt-responsive nowrap" style="width: 100%;">
													<thead>
														<tr>
															<th><?= __('date') ?></th>
															<th><?= __('check_in', 'Check In') ?></th>
															<th><?= __('check_out', 'Check Out') ?></th>
															<th><?= __('hours') ?></th>
															<th><?= __('state', 'State') ?></th>
															<th><?= __('late_minutes', 'Late (min)') ?></th>
															<th><?= __('early_leave_minutes', 'Early Leave (min)') ?></th>
															<th><?= __('note') ?></th>
															<?php if ($canAttendanceRowActions): ?>
															<th class="text-right"><?= __('action') ?></th>
															<?php endif; ?>
														</tr>
													</thead>
												</table>
												</div>

											</div>
										</div>
										<?php endif; ?>
										<?php  ?>
										<div class="tab-pane" id="evaluations">
											<div class="card-box">
												<h4 class="header-title m-b-30"><?= __('evaluations', 'Performance Evaluations') ?></h4>
												
												<table id="evaluations_tbl" class="table sr-table dt-responsive nowrap" style="border-collapse: collapse; border-spacing: 0; width: 100%;">
													<thead>
														<tr>
															<th><?= __('id') ?></th>
															<th><?= __('evaluation_date', 'Evaluation Date') ?></th>
															<th><?= __('evaluated_by', 'Evaluated By') ?></th>
															<th><?= __('department', 'Department') ?></th>
															<th><?= __('total_score', 'Total Score') ?></th>
															<th><?= __('observation', 'Observation') ?></th>
															<th><?= __('action') ?></th>
														</tr>
													</thead>
													<tbody>
														<?php
														// Fetch employee evaluations
														$eval_query = $pdo->prepare("
															SELECT 
																ev.id,
																ev.manager_emp_id,
																em.name AS manager_name,
																de.dep_nme,
																de.dep_nme_ar,
																ev.total_score,
																ev.observation,
																ev.manager_acknowledgment_status,
																ev.manager_objection_note,
																ev.manager_acknowledged_by,
																DATE_FORMAT(ev.created_at, '%Y-%m-%d %H:%i') AS eval_date,
																DATE_FORMAT(ev.manager_acknowledgment_date, '%Y-%m-%d %H:%i') AS acknowledgment_date,
																ack_em.name AS acknowledged_by_name
															FROM emp_evaluations ev
															LEFT JOIN employees em ON ev.manager_emp_id = em.emp_id
															LEFT JOIN employees ack_em ON ev.manager_acknowledged_by = ack_em.emp_id
															LEFT JOIN department de ON em.dept = de.id
															WHERE ev.employee_emp_id = ?
															ORDER BY ev.created_at DESC
														");
														$eval_query->execute([$emprow['empid']]);
														$evaluations = $eval_query->fetchAll(PDO::FETCH_ASSOC);
														
														if (count($evaluations) > 0):
															foreach ($evaluations as $eval):
																$score_class = 'success';
																if ($eval['total_score'] < 50) $score_class = 'danger';
																elseif ($eval['total_score'] < 70) $score_class = 'warning';
																elseif ($eval['total_score'] < 90) $score_class = 'info';
														?>
															<tr>
																<td><?= htmlspecialchars($eval['id']) ?></td>
																<td><?= htmlspecialchars($eval['eval_date']) ?></td>
																<td><?= getDisplayName($eval['manager_name']) ?></td>
																<td><?= ($is_rtl ?? false ? $eval['dep_nme_ar'] : $eval['dep_nme']) ?></td>
																<td>
																	<span class="badge badge-<?= $score_class ?>" style="font-size: 14px;">
																		<?= htmlspecialchars($eval['total_score']) ?>/100
																	</span>
																</td>
																<td><?= htmlspecialchars(substr($eval['observation'], 0, 100)) ?><?= strlen($eval['observation']) > 100 ? '...' : '' ?></td>
																<td>
																	<div class="btn-group" role="group">
																		<button class="btn btn-sm btn-primary view-eval-details" 
																			data-id="<?= $eval['id'] ?>">
																			<i class="mdi mdi-eye"></i> <?= __('view_details', 'View Details') ?>
																		</button>
																		<?php if ($eval['manager_acknowledgment_status'] === 'pending'): ?>
																			<button class="btn btn-sm btn-success acknowledge-eval" 
																				data-id="<?= $eval['id'] ?>"
																				data-manager-name="<?= htmlspecialchars($eval['manager_name']) ?>"
																				title="<?= __('acknowledge_evaluation', 'Acknowledge Evaluation') ?>">
																				<i class="mdi mdi-check-circle"></i> <?= __('acknowledge', 'Acknowledge') ?>
																			</button>
																			<button class="btn btn-sm btn-warning object-eval" 
																				data-id="<?= $eval['id'] ?>"
																				data-manager-name="<?= htmlspecialchars($eval['manager_name']) ?>"
																				title="<?= __('object_to_evaluation', 'Object to Evaluation') ?>">
																				<i class="mdi mdi-close-circle"></i> <?= __('object', 'Object') ?>
																			</button>
																		<?php else: ?>
																			<span class="badge badge-<?= $eval['manager_acknowledgment_status'] === 'acknowledged' ? 'success' : 'danger' ?>" style="padding: 8px 12px; font-size: 12px;">
																				<?php if ($eval['manager_acknowledgment_status'] === 'acknowledged'): ?>
																					<i class="mdi mdi-check-circle"></i> <?= __('acknowledged', 'Acknowledged') ?>
																				<?php else: ?>
																					<i class="mdi mdi-close-circle"></i> <?= __('objected', 'Objected') ?>
																				<?php endif; ?>
																			</span>
																			<?php if (!empty($eval['acknowledged_by_name'])): ?>
																				<small class="text-muted ml-2"><?= __('by', 'by') ?> <?= getDisplayName($eval['acknowledged_by_name']) ?></small>
																			<?php endif; ?>
																		<?php endif; ?>
																	</div>
																</td>
															</tr>
															<?php if ($eval['manager_acknowledgment_status'] === 'objected' && !empty($eval['manager_objection_note'])): ?>
															<tr class="bg-light-danger">
																<td colspan="7" class="border-top-0 pt-2 pb-2">
																	<div class="alert alert-danger mb-0" style="border-left: 4px solid #f46a6a;">
																		<strong><i class="mdi mdi-alert-circle"></i> <?= __('objection_note', 'Objection Note') ?>:</strong>
																		<p class="mb-0 mt-2"><?= nl2br(htmlspecialchars($eval['manager_objection_note'])) ?></p>
																		<?php if (!empty($eval['acknowledgment_date'])): ?>
																			<small class="text-muted">
																				<i class="mdi mdi-clock"></i> <?= __('objected_on', 'Objected on') ?>: <?= htmlspecialchars($eval['acknowledgment_date']) ?>
																				<?php if (!empty($eval['acknowledged_by_name'])): ?>
																					<?= __('by', 'by') ?> <?= getDisplayName($eval['acknowledged_by_name']) ?>
																				<?php endif; ?>
																			</small>
																		<?php endif; ?>
																	</div>
																</td>
															</tr>
															<?php endif; ?>
														<?php 
															endforeach;
														else:
														?>
															<tr>
																<td colspan="7" class="text-center">
																	<?= __('no_evaluations_found', 'No performance evaluations found for this employee.') ?>
																</td>
															</tr>
														<?php endif; ?>
													</tbody>
												</table>
											</div>
										</div>
										<?php  ?>

										<?php if ($show_d365_tab): ?>
										<div class="tab-pane" id="d365tab" data-emp="<?= htmlspecialchars($emprow['empid']) ?>" data-csrf="<?= htmlspecialchars($_SESSION['d365_csrf']) ?>" data-loaded="0">
											<div id="d365TabBody">
												<div class="text-center text-muted p-4"><i class="mdi mdi-loading mdi-spin"></i> <?= __('loading_d365_data', 'Loading Dynamics 365 data...') ?></div>
											</div>
										</div>
										<?php endif; ?>

										<?php if ($direct_reports_count > 1): ?>
										<div class="tab-pane" id="directreports" data-supervisor-emp-id="<?= htmlspecialchars($emprow['emp_id']) ?>" data-loaded="0">
											<div class="card-box">
												<div class="d-flex justify-content-between align-items-end flex-wrap mb-3 direct-reports-toolbar">
													<h4 class="header-title m-b-0"><?= __('direct_reports', 'Direct Reports') ?></h4>
													<div class="direct-reports-search-group mt-2 mt-md-0">
														<label for="directReportsSearch" class="direct-reports-search-label"><?= __('search', 'Search') ?></label>
														<div class="direct-reports-search-input">
															<i class="mdi mdi-magnify"></i>
															<input type="text" id="directReportsSearch" class="form-control" placeholder="<?= __('search_by_name_id_iqama_mobile', 'Name, employee ID, iqama or mobile') ?>" autocomplete="off">
															<i class="mdi mdi-close-circle directReportsSearchClear" id="directReportsSearchClear" title="<?= __('clear', 'Clear') ?>"></i>
														</div>
													</div>
												</div>
												<div id="directReportsCards">
													<div class="text-center text-muted p-4">
														<i class="mdi mdi-loading mdi-spin"></i> <?= __('loading', 'Loading...') ?>
													</div>
												</div>
												<div id="directReportsPagination" class="d-flex justify-content-between align-items-center mt-3"></div>
											</div>
										</div>
										<?php endif; ?>

									</div>
								</div>

							</div>
						</div>


					</div> <!-- container -->

				</div> <!-- content -->

				<footer class="footer">
					<?= $site_footer ?>
				</footer>

			</div>

			<!-- ============================================================== -->
			<!-- End Right content here -->
			<!-- ============================================================== -->
		</div>
		<!-- END wrapper -->
		<!-- jQuery  -->
		<script src="assets/js/jquery.min.js"></script>
		<!--        <script src="http://ajax.googleapis.com/ajax/libs/jquery/1.11.0/jquery.min.js"></script>-->
		<script src="assets/js/bootstrap.bundle.min.js"></script>
		<script src="assets/js/metisMenu.min.js"></script>
		<script src="assets/js/waves.js"></script>
		<script src="assets/js/jquery.slimscroll.js"></script>


		<!-- Modal-Effect -->
		<script type="text/javascript" src="./plugins/parsleyjs/parsley.min.js"></script>
		<script src="./plugins/bootstrap-inputmask/bootstrap-inputmask.min.js" type="text/javascript"></script>
		<script src="./plugins/autoNumeric/autoNumeric.js" type="text/javascript"></script>


		<!-- <script src="./plugins/moment/moment.js"></script> -->
		<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.18.1/moment.min.js"></script>

		<script src="./plugins/bootstrap-timepicker/bootstrap-timepicker.js"></script>
		<script src="./plugins/bootstrap-datepicker/js/bootstrap-datepicker.min.js"></script>

		<!-- <script src="./plugins/select2/js/select2.min.js" type="text/javascript"></script>
        <script src="./plugins/bootstrap-select/js/bootstrap-select.js" type="text/javascript"></script> -->

		<!-- <script src="./assets/pages/jquery.form-pickers.init.js"></script> -->
		<script src="./plugins/croppie/croppie.js" type="text/javascript"></script>
		<script src="./plugins/croppie/croppie.min.js" type="text/javascript"></script>
		<script src="./plugins/croppie/exif.js" type="text/javascript"></script>

		<!-- App js -->

		<!-- Required datatable js -->
		<script src="./plugins/datatables/jquery.dataTables.min.js"></script>
		<script src="./plugins/datatables/dataTables.bootstrap4.min.js"></script>
		<!-- Buttons examples -->
		<script src="./plugins/datatables/dataTables.buttons.min.js"></script>
		<script src="./plugins/datatables/buttons.bootstrap4.min.js"></script>
		<script src="./plugins/datatables/jszip.min.js"></script>
		<script src="./plugins/datatables/pdfmake.min.js"></script>
		<script src="./plugins/datatables/vfs_fonts.js"></script>
		<script src="./plugins/datatables/buttons.html5.min.js"></script>
		<script src="./plugins/datatables/buttons.print.min.js"></script>

		<!-- Key Tables -->
		<script src="./plugins/datatables/dataTables.keyTable.min.js"></script>

		<!-- Responsive examples -->
		<script src="./plugins/datatables/dataTables.responsive.min.js"></script>
		<script src="./plugins/datatables/responsive.bootstrap4.min.js"></script>


		<!-- Selection table -->
		<script src="./plugins/datatables/dataTables.select.min.js"></script>


		<!-- App js -->
		<script src="assets/js/jquery.app.js?t=<?= time() ?>"></script>
		<script src="assets/js/sr_forms.js?v=<?= @filemtime(__DIR__ . '/assets/js/sr_forms.js') ?>"></script>
		<script src="assets/js/attendance_ui.js?v=<?= @filemtime(__DIR__ . '/assets/js/attendance_ui.js') ?>"></script>
		<script src="assets/js/businessTrip.js?t=<?= time() ?>"></script>
		<script src="assets/js/employeeTransfer.js?t=<?= time() ?>"></script>
		<script src="assets/js/salaryIncrement.js?t=<?= time() ?>"></script>
		<script src="assets/js/empVacationHandle.js"></script>

		<!-- Dropzone JS -->
		<script src="https://cdnjs.cloudflare.com/ajax/libs/dropzone/5.9.3/dropzone.min.js"></script>

		<script src="./plugins/summernote/summernote.min.js"></script>
		<!-- <script src="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-bs4.min.js"></script> -->
		<script src="assets/js/loanHandling.js?t=<?= time() ?>"></script>
		<script src="assets/js/resignationWizard.js?v=<?= @filemtime(__DIR__ . '/assets/js/resignationWizard.js') ?>"></script>

		<script type="text/javascript">
			// ============================================================================
			// PRINT PROFILE OPTIONS
			// ============================================================================
			// Lets the user pick which blocks go on employee_profile.php. Unticked blocks are
			// sent as ?hide=a,b,c; the profile page still applies its own permission checks.
			// Sensitive blocks start unticked; the last choice is remembered per browser.
			// ============================================================================
			<?php
			$printProfileOptions = [
				['key' => 'photo', 'label' => __('photo', 'Photo'), 'sensitive' => false],
				['key' => 'ids', 'label' => __('iqama_passport_details', 'Iqama / Passport Details'), 'sensitive' => true],
				['key' => 'personal', 'label' => __('personal_information', 'Personal Information'), 'sensitive' => false],
				['key' => 'contact', 'label' => __('contact', 'Contact Information'), 'sensitive' => false],
				['key' => 'bank', 'label' => __('bank_&_gosi_details', 'Bank & GOSI Details'), 'sensitive' => true],
				['key' => 'employment', 'label' => __('employment', 'Employment Information'), 'sensitive' => false],
				['key' => 'vacation_balance', 'label' => __('vacation_balance_summary', 'Vacation Balance Summary'), 'sensitive' => false],
				['key' => 'vacations', 'label' => __('vacation_history_header', 'Vacation History'), 'sensitive' => false],
				['key' => 'assets', 'label' => __('assigned_assets', 'Assigned Assets'), 'sensitive' => false],
				['key' => 'notes', 'label' => __('notes_notices_header', 'Notes & Notices'), 'sensitive' => false],
				['key' => 'loans', 'label' => __('loan_history', 'Loan History'), 'sensitive' => true],
			];
			if ($canViewSalary) {
				$printProfileOptions[] = ['key' => 'salary', 'label' => __('salary_breakdown_header', 'Salary Breakdown'), 'sensitive' => true];
			}
			if ($canViewAdditionalInfo && $canViewSalary) {
				$printProfileOptions[] = ['key' => 'additional', 'label' => __('additional_information', 'Additional Information'), 'sensitive' => true];
			}
			if ($canViewEosValue) {
				$printProfileOptions[] = ['key' => 'eos', 'label' => __('end_of_service_header', 'End of Service'), 'sensitive' => true];
			}
			?>
			const PRINT_PROFILE_OPTIONS = <?= json_encode($printProfileOptions, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
			const PRINT_PROFILE_STORE = 'employeeProfilePrintHidden';

			// More Actions > Reset Login Password (button built in includes/emp_top_info.php).
			// Issues a one-time temporary password; the employee must change it at the next login.
			function resetEmployeePassword(iqama, name) {
				Swal.fire({
					icon: 'warning',
					title: <?= json_encode(__('reset_login_password', 'Reset Login Password')) ?>,
					html: <?= json_encode(__('reset_login_password_for', 'Reset the login password for')) ?> + ' <b>' + $('<div>').text(name).html() + '</b>?<br><br>'
						+ <?= json_encode(__('reset_login_password_note', 'A temporary password will be shown once. The employee must change it at the next login, and any "remember me" sign-ins will be ended.')) ?>,
					showCancelButton: true,
					cancelButtonText: <?= json_encode(__('cancel', 'Cancel')) ?>,
					confirmButtonText: <?= json_encode(__('reset_password_button', 'Reset Password')) ?>,
					confirmButtonColor: '#d33',
					showLoaderOnConfirm: true,
					allowOutsideClick: () => !Swal.isLoading(),
					preConfirm: () => {
						return $.ajax({
							url: 'includes/ajaxFile/ajaxEmployeePasswordReset.php',
							type: 'POST',
							dataType: 'json',
							data: { iqama: iqama }
						}).then(res => {
							if (!res || !res.ok) {
								Swal.showValidationMessage((res && res.message) || <?= json_encode(__('reset_failed', 'Reset failed.')) ?>);
								return false;
							}
							return res;
						}, xhr => {
							Swal.showValidationMessage((xhr.responseJSON && xhr.responseJSON.message) || <?= json_encode(__('reset_failed', 'Reset failed.')) ?>);
							return false;
						});
					}
				}).then(result => {
					if (!result.isConfirmed || !result.value) return;
					const temp = result.value.temp_password;
					Swal.fire({
						icon: 'success',
						title: <?= json_encode(__('temporary_password', 'Temporary Password')) ?>,
						html: <?= json_encode(__('give_password_to', 'Give this password to')) ?> + ' <b>' + $('<div>').text(name).html() + '</b>. ' + <?= json_encode(__('shown_only_once', 'It is shown only once.')) ?>
							+ '<div style="margin:16px auto 6px;padding:12px;border-radius:8px;background:#111827;color:#fff;font:700 22px/1.2 monospace;letter-spacing:2px;user-select:all;">'
							+ $('<div>').text(temp).html() + '</div>',
						confirmButtonText: <?= json_encode(__('copy_and_close', 'Copy & Close')) ?>,
						allowOutsideClick: false
					}).then(() => {
						if (navigator.clipboard) { navigator.clipboard.writeText(temp).catch(() => {}); }
					});
				});
			}

			function openPrintProfileOptions() {
				const empId = <?= json_encode((string)$emprow['empid']) ?>;
				let hidden = null;
				try {
					hidden = JSON.parse(localStorage.getItem(PRINT_PROFILE_STORE) || 'null');
				} catch (e) {}
				if (!Array.isArray(hidden)) {
					hidden = PRINT_PROFILE_OPTIONS.filter(o => o.sensitive).map(o => o.key);
				}

				const esc = s => $('<div>').text(s).html();
				const items = PRINT_PROFILE_OPTIONS.map(o => `
					<label class="pp-opt">
						<input type="checkbox" class="pp-check" value="${esc(o.key)}" ${hidden.includes(o.key) ? '' : 'checked'}>
						<span>${esc(o.label)}</span>
						${o.sensitive ? `<i class="fa fa-lock pp-lock" title="${esc(__('sensitive', 'Sensitive'))}"></i>` : ''}
					</label>`).join('');

				Swal.fire({
					title: __('print_profile', 'Print Profile'),
					html: `
						<style>
							.pp-head { display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px; font-size: 13px; color: #66717f; }
							.pp-head a { cursor: pointer; margin-inline-start: 10px; }
							.pp-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 6px; text-align: start; }
							.pp-opt { display: flex; align-items: center; gap: 8px; margin: 0; padding: 7px 10px; border: 1px solid #dfe4ea; border-radius: 6px; cursor: pointer; font-size: 13px; font-weight: 500; }
							.pp-opt:hover { background: #f3f6fa; }
							.pp-opt span { flex: 1; }
							.pp-lock { color: #b3261e; font-size: 12px; }
							@media (max-width: 520px) { .pp-grid { grid-template-columns: 1fr; } }
						</style>
						<div class="pp-head">
							<span>${esc(__('choose_sections_to_print', 'Choose the sections to include'))}</span>
							<span>
								<a id="ppAll">${esc(__('select_all', 'Select all'))}</a>
								<a id="ppNone">${esc(__('clear_all', 'Clear all'))}</a>
							</span>
						</div>
						<div class="pp-grid">${items}</div>`,
					width: 600,
					showCancelButton: true,
					confirmButtonText: '<i class="fi-printer"></i> ' + esc(__('print', 'Print')),
					cancelButtonText: __('cancel', 'Cancel'),
					didOpen: (popup) => {
						popup.querySelector('#ppAll').addEventListener('click', () => popup.querySelectorAll('.pp-check').forEach(c => c.checked = true));
						popup.querySelector('#ppNone').addEventListener('click', () => popup.querySelectorAll('.pp-check').forEach(c => c.checked = false));
					},
					preConfirm: () => Array.from(Swal.getPopup().querySelectorAll('.pp-check:not(:checked)')).map(c => c.value)
				}).then(result => {
					if (!result.isConfirmed) return;
					try {
						localStorage.setItem(PRINT_PROFILE_STORE, JSON.stringify(result.value));
					} catch (e) {}
					const params = new URLSearchParams({ emp_id: empId });
					if (result.value.length) params.set('hide', result.value.join(','));
					window.open('./employee_profile.php?' + params.toString(), '_blank');
				});
			}

			// ============================================================================
			// REJOIN APPROVAL SYSTEM
			// ============================================================================
			// Handles employee rejoin requests after vacation with supervisor approval
			// Allows 3-day adjustment window if employee selected wrong date
			// ============================================================================

			function rjEsc(v) {
				return $('<div>').text(v == null ? '' : String(v)).html();
			}

			function submitRejoinRequest(vacationId, returndate, empId, empName) {
				// First, check if there's an active rejoin request
				$.ajax({
					url: './includes/ajaxFile/leaveHandler.php',
					type: 'POST',
					dataType: 'JSON',
					data: {
						ajaxType: 'checkActiveRejoinRequest',
						vacation_id: vacationId,
						emp_id: empId
					},
					success: function(checkResponse) {
						// If there's an active request, show warning immediately
						if(checkResponse.type === 'warning' && checkResponse.active_request) {
							const ar = checkResponse.active_request;
							const arStatus = String(ar.status || '').toLowerCase();
							const arTone = arStatus === 'approved' ? 'green' : (arStatus === 'rejected' ? 'red' : 'amber');
							Swal.fire({
								title: '<i class="fa fa-plane-arrival"></i> ' + rjEsc(checkResponse.title || __('active_rejoin_request_exists', 'Active Request Exists')),
								html: `
									<div class="sr-form">
										<div class="sr-notice tone-amber" style="margin-bottom:12px;">
											<i class="mdi mdi-information-outline"></i>
											<div>${rjEsc(checkResponse.message || __('rejoin_request_already_active', 'A rejoin request for this vacation is already in progress.'))}</div>
										</div>
										<div class="sr-fsec mb-0">
											<div class="sr-fsec-head"><span><i class="mdi mdi-calendar-check"></i> ${__('rejoin_request', 'Rejoin Request')}</span></div>
											<dl class="sr-kv">
												<div class="row-kv"><dt>${__('request_number', 'Request Number')}</dt><dd class="sr-mono">${rjEsc(ar.request_inv_no)}</dd></div>
												<div class="row-kv"><dt>${__('status', 'Status')}</dt><dd><span class="sr-pill tone-${arTone}"><span class="sr-dot"></span>${rjEsc(arStatus.toUpperCase())}</span></dd></div>
												<div class="row-kv"><dt>${__('requested_rejoin_date', 'Requested Rejoin Date')}</dt><dd>${rjEsc(ar.requested_rejoin_date)}</dd></div>
												<div class="row-kv"><dt>${__('submitted_at', 'Submitted At')}</dt><dd>${rjEsc(ar.requested_at)}</dd></div>
												<div class="row-kv"><dt>${__('associated_vacation', 'Associated Vacation')}</dt><dd class="sr-mono">${rjEsc(ar.vacation_inv_no)}</dd></div>
												<div class="row-kv"><dt>${__('vacation_type', 'Vacation Type')}</dt><dd>${rjEsc(ar.vac_type)}</dd></div>
											</dl>
										</div>
									</div>
								`,
								customClass: { popup: 'sr-addline-popup sr-page' },
								width: '560px',
								allowOutsideClick: false,
								confirmButtonColor: APP_COLORS.primary,
								confirmButtonText: __('ok', 'OK')
							});
							return;
						}
						
						// No active request - show the submission form
						showRejoinRequestForm(vacationId, returndate, empId, empName);
					},
					error: function(jqXHR, textStatus) {
						Swal.fire({
							icon: 'error',
							title: __('error', 'Error'),
							text: __('request_failed_status', 'Request failed') + ' - ' + textStatus,
							confirmButtonColor: APP_COLORS.danger,
							confirmButtonText: __('ok', 'OK')
						});
					}
				});
			}
			
			function showRejoinRequestForm(vacationId, returndate, empId, empName) {
				// Parse the return date to ensure it's in the correct format (YYYY-MM-DD)
				const parsedDate = new Date(returndate);
				const formattedDate = parsedDate.toISOString().split('T')[0];
				const displayDate = parsedDate.toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' });
				// Allowed rejoin window: planned return date +/- 7 days
				const minRejoin = new Date(parsedDate);
				minRejoin.setDate(minRejoin.getDate() - 7);
				const maxRejoin = new Date(parsedDate);
				maxRejoin.setDate(maxRejoin.getDate() + 7);
				const minDisplay = minRejoin.toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' });
				const maxDisplay = maxRejoin.toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' });
				const initials = String(empName || '').trim().split(/\s+/).slice(0, 2).map(w => w.charAt(0)).join('').toUpperCase();

				Swal.fire({
					title: '<i class="fa fa-plane-arrival"></i> ' + __('rejoin_request_title', 'Submit Rejoin Request'),
					html: `
						<div class="sr-form">
							<div class="sr-fsec">
								<div class="sr-fsec-head"><span><i class="mdi mdi-airplane-landing"></i> ${__('vacation_details', 'Vacation Details')}</span></div>
								<div class="sr-person" style="margin-bottom:8px;">
									<span class="sr-avatar">${rjEsc(initials)}</span>
									<div>
										<div class="sr-cell-title">${rjEsc(empName)}</div>
										<div class="sr-cell-sub sr-mono">#${rjEsc(empId)}</div>
									</div>
								</div>
								<dl class="sr-kv">
									<div class="row-kv"><dt>${__('planned_return_text', 'Planned Return')}</dt><dd>${rjEsc(displayDate)}</dd></div>
									<div class="row-kv"><dt>${__('earliest_allowed_rejoin', 'Earliest Allowed Rejoin')}</dt><dd>${rjEsc(minDisplay)}</dd></div>
									<div class="row-kv"><dt>${__('latest_allowed_rejoin', 'Latest Allowed Rejoin')}</dt><dd>${rjEsc(maxDisplay)}</dd></div>
								</dl>
							</div>

							<div class="sr-notice tone-sky" style="margin-bottom:12px;">
								<i class="mdi mdi-information-outline"></i>
								<div>${__('rejoin_request_subtitle', 'Your rejoin request will be sent to your direct supervisor for approval')}</div>
							</div>

							<div class="sr-fsec mb-0">
								<div class="sr-fsec-head"><span><i class="mdi mdi-calendar-check"></i> ${__('rejoin_date_label', 'Actual Rejoin Date')}</span></div>
								<div class="sr-fgrid">
									<div class="sr-fcol c-12">
										<label for="rejoinDate">${__('rejoin_date_label', 'Actual Rejoin Date')} <span class="text-danger">*</span></label>
										<input type="text" id="rejoinDate" class="form-control" placeholder="${__('select_date', 'Select date...')}" value="${formattedDate}" autocomplete="off">
										<span class="sr-fhint">${__('rejoin_date_window_hint', 'Within 7 days before or after the planned return date')}</span>
									</div>
									<div class="sr-fcol c-12">
										<label for="rejoinReason">${__('rejoin_reason_label', 'Reason for Date Change (if applicable)')}</label>
										<textarea id="rejoinReason" class="form-control" rows="3" placeholder="${__('optional_text', 'Optional')}"></textarea>
									</div>
								</div>
							</div>
						</div>
					`,
					customClass: { popup: 'sr-addline-popup sr-page' },
					width: '600px',
					showCancelButton: true,
					confirmButtonText: '<i class="fa fa-paper-plane"></i> ' + __('submit_request_button', 'Submit Request'),
					cancelButtonText: '<i class="fa fa-times"></i> ' + __('cancel', 'Cancel'),
					confirmButtonColor: APP_COLORS.success,
					cancelButtonColor: APP_COLORS.secondary,
					allowOutsideClick: false,
					didOpen: () => {
						// Dates more than 7 days before or after the planned return are blocked
						// (same rule preConfirm enforces below).
						AppDate.single('#rejoinDate', {
							defaultDate: formattedDate,
							minDate: AppDate.format(minRejoin),
							maxDate: AppDate.format(maxRejoin)
						});
					},
					preConfirm: () => {
						const rejoinDate = document.getElementById('rejoinDate').value;
						const rejoinReason = document.getElementById('rejoinReason').value;

						if (!rejoinDate) {
							Swal.showValidationMessage(__('rejoin_date_required_validation', 'Please select a rejoin date'));
							return false;
						}

						// Verify date is within planned return +/- 7 days
						const rejoin = new Date(rejoinDate);

						if (rejoin < minRejoin || rejoin > maxRejoin) {
							Swal.showValidationMessage(
								__('rejoin_date_range_validation_pm7',
									'Rejoin date must be within 7 days before or after the planned return date. ' +
									'Please contact HR for special cases.')
							);
							return false;
						}

						return { rejoinDate, rejoinReason };
					}
				}).then((result) => {
					if (result.isConfirmed) {
						const { rejoinDate, rejoinReason } = result.value;
						submitRejoinAjax(vacationId, rejoinDate, rejoinReason, empId, empName);
					}
				});
			}

			function submitRejoinAjax(vacationId, rejoinDate, rejoinReason, empId, empName) {
				Swal.fire({
					title: __('processing_title', 'Processing...'),
					html: __('please_wait_text', 'Please wait while we process your request'),
					didOpen: () => Swal.showLoading(),
					customClass: { popup: 'sr-addline-popup sr-page' },
					allowOutsideClick: false,
					allowEscapeKey: false
				});

				$.ajax({
					url: './includes/ajaxFile/leaveHandler.php',
					type: 'POST',
					dataType: 'JSON',
					data: {
						ajaxType: 'submitRejoinRequest',
						vacation_id: vacationId,
						rejoin_date: rejoinDate,
						rejoin_reason: rejoinReason,
						emp_id: empId
					},
					success: function(response) {
						if (response.type === 'success') {
							Swal.fire({
								title: response.title || __('success_title', 'Success'),
								text: response.message || __('rejoin_request_submitted_text', 'Your rejoin request has been submitted for approval'),
								icon: 'success',
								customClass: { popup: 'sr-addline-popup sr-page' },
								allowOutsideClick: false,
								confirmButtonText: __('ok', 'OK')
							}).then(() => {
								location.reload();
							});
						} else {
							Swal.fire({
								title: response.title || __('error_title', 'Error'),
								text: response.message || __('unexpected_error_text', 'An unexpected error occurred'),
								icon: 'error',
								customClass: { popup: 'sr-addline-popup sr-page' },
								allowOutsideClick: false,
								confirmButtonText: __('ok', 'OK')
							});
						}
					},
					error: function(jqXHR, textStatus, errorThrown) {
						Swal.fire({
							title: __('error_title', 'Error'),
							text: __('request_failed_text', 'Request failed') + ': ' + textStatus,
							icon: 'error',
							customClass: { popup: 'sr-addline-popup sr-page' },
							allowOutsideClick: false,
							confirmButtonText: __('ok', 'OK')
						});
					}
				});
			}

			function grantTempRole(vacationId, employeeName, startDate, returnDate) {
				Swal.fire({
					title: __('transfer_role_temp', 'Transfer Role (Temp)'),
					html: `<p>${__('confirm_transfer_role_temp', 'Grant this employee') + ' ' + employeeName + '\'s role, valid ' + startDate + ' to ' + returnDate + '?'}</p>`,
					icon: 'question',
					showCancelButton: true,
					confirmButtonText: __('yes_transfer', 'Yes, transfer'),
					cancelButtonText: __('cancel', 'Cancel'),
					allowOutsideClick: false,
					allowEscapeKey: false
				}).then((result) => {
					if (!result.isConfirmed) {
						return;
					}
					$.ajax({
						url: './includes/ajaxFile/tempRoleHandler.php',
						type: 'POST',
						dataType: 'JSON',
						data: {
							ajaxType: 'grantTempRole',
							vacation_id: vacationId
						},
						success: function(response) {
							Swal.fire({
								title: response.title || (response.type === 'success' ? __('success') : __('error_title', 'Error')),
								text: response.message,
								icon: response.type || 'error',
								confirmButtonText: __('ok')
							}).then(() => {
								if (response.type === 'success') {
									location.reload();
								}
							});
						},
						error: function(jqXHR, textStatus) {
							Swal.fire({
								title: __('error_title', 'Error'),
								text: __('request_failed_text', 'Request failed') + ': ' + textStatus,
								icon: 'error',
								confirmButtonText: __('ok')
							});
						}
					});
				});
			}

			function revokeTempRole(vacationId) {
				Swal.fire({
					title: __('revoke_temp_role', 'Revoke Temp Role'),
					text: __('are_you_sure_revoke_temp_role', 'This will immediately remove the replacement\'s temporary access.'),
					icon: 'warning',
					showCancelButton: true,
					confirmButtonColor: '#dc3545',
					confirmButtonText: __('yes_revoke', 'Yes, revoke'),
					cancelButtonText: __('cancel', 'Cancel'),
					allowOutsideClick: false,
					allowEscapeKey: false
				}).then((result) => {
					if (!result.isConfirmed) {
						return;
					}
					$.ajax({
						url: './includes/ajaxFile/tempRoleHandler.php',
						type: 'POST',
						dataType: 'JSON',
						data: {
							ajaxType: 'revokeTempRole',
							vacation_id: vacationId
						},
						success: function(response) {
							Swal.fire({
								title: response.title || (response.type === 'success' ? __('success') : __('error_title', 'Error')),
								text: response.message,
								icon: response.type || 'error',
								confirmButtonText: __('ok')
							}).then(() => {
								if (response.type === 'success') {
									location.reload();
								}
							});
						},
						error: function(jqXHR, textStatus) {
							Swal.fire({
								title: __('error_title', 'Error'),
								text: __('request_failed_text', 'Request failed') + ': ' + textStatus,
								icon: 'error',
								confirmButtonText: __('ok')
							});
						}
					});
				});
			}

			function cancelVacationRequest(vacationId, vacType, startDate) {
				Swal.fire({
					title: __('cancel_vacation_request'),
					html: `<p>${__('are_you_sure_cancel_vacation', { vacType: vacType, startDate: startDate })}</p><p style="color: #dc3545; font-size: 12px; margin-top: 10px;">${__('this_action_cannot_be_undone')}</p>`,
					icon: 'warning',
					showCancelButton: true,
					confirmButtonColor: '#dc3545',
					cancelButtonColor: '#6c757d',
					confirmButtonText: __('yes_cancel_request'),
					cancelButtonText: __('keep_request'),
					allowOutsideClick: false,
					allowEscapeKey: false
				}).then((result) => {
					if (!result.isConfirmed) {
						return;
					}

					Swal.fire({
						title: __('cancelling_request'),
						html: __('please_wait_while_cancelling'),
						icon: 'info',
						allowOutsideClick: false,
						allowEscapeKey: false,
						didOpen: () => {
							Swal.showLoading();
						}
					});

					$.ajax({
						url: './includes/ajaxFile/leaveHandler.php',
						type: 'POST',
						dataType: 'JSON',
						data: {
							ajaxType: 'cancelVacationRequest',
							vacation_id: vacationId
						},
						success: function(response) {
							Swal.fire({
								title: response.title || __('success'),
								text: response.message || __('your_vacation_request_has_been_cancelled_successfully'),
								icon: response.type || 'success',
								confirmButtonText: __('ok'),
								allowOutsideClick: false
							}).then(() => {
								location.reload();
							});
						},
						error: function(xhr) {
							const response = xhr.responseJSON || {};
							Swal.fire({
								title: response.title || __('error'),
								text: response.message || __('failed_to_cancel_vacation_request'),
								icon: 'error',
								confirmButtonText: __('ok')
							});
						}
					});
				});
			}

			function approveRejoinRequest(rejoinRequestId, empId, rejoinDate, showAdjustmentOption = true) {
				let html = `
					<div class="text-left">
						<p class="mb-3"><strong>${__('rejoin_date_label', 'Rejoin Date')}:</strong><br>
						${new Date(rejoinDate).toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' })}</p>
						
						<div class="form-group">
							<label class="form-label font-weight-bold">${__('approval_action_label', 'Action')} <span class="text-danger">*</span></label>
							<div class="custom-control custom-radio mb-2">
								<input type="radio" class="custom-control-input" id="action_approve" name="action" value="approve" checked>
								<label class="custom-control-label" for="action_approve">
									${__('approve_button', 'Approve')} - ${__('approve_text', 'Accept the rejoin date as submitted')}
								</label>
							</div>
				`;

				if (showAdjustmentOption) {
					html += `
							<div class="custom-control custom-radio mb-2">
								<input type="radio" class="custom-control-input" id="action_adjust" name="action" value="adjust">
								<label class="custom-control-label" for="action_adjust">
									${__('adjust_button', 'Adjust')} - ${__('adjust_3days_text', 'Allow employee to adjust date (±3 days from submitted date)')}
								</label>
							</div>
							<div id="adjustmentDetails" style="display:none; background: #f0f0f0; padding: 10px; border-radius: 4px; margin-left: 30px; margin-top: 10px;">
								<small class="text-muted">
									<i class="fa fa-info-circle"></i> ${__('adjustment_explanation', 'Employee can modify the date within 3 days before or after the submitted date')}
								</small>
							</div>
					`;
				}

				html += `
						<div class="custom-control custom-radio mb-2">
							<input type="radio" class="custom-control-input" id="action_reject" name="action" value="reject">
							<label class="custom-control-label" for="action_reject">
								${__('reject_button', 'Reject')} - ${__('reject_text', 'Request changes or requires HR review')}
							</label>
						</div>
						<div id="rejectionReason" style="display:none; margin-left: 30px; margin-top: 10px;">
							<textarea id="rejectReason" class="form-control" rows="3" placeholder="${__('explain_rejection', 'Explain why you are rejecting this request')}"></textarea>
						</div>

						<div class="form-group mt-3">
							<label for="approvalNote" class="form-label font-weight-bold">${__('approval_note_label', 'Approval Note (Optional)')}</label>
							<textarea id="approvalNote" class="form-control" rows="2" placeholder="${__('optional_text', 'Optional')}"></textarea>
						</div>
					</div>
				`;

				Swal.fire({
					title: __('review_rejoin_request_title', 'Review Rejoin Request'),
					html: html,
					width: '550px',
					showCancelButton: true,
					confirmButtonText: __('submit_button', 'Submit'),
					cancelButtonText: __('cancel', 'Cancel'),
					confirmButtonColor: APP_COLORS.success,
					cancelButtonColor: APP_COLORS.danger,
					allowOutsideClick: false,
					didOpen: () => {
						// Toggle adjustment details and rejection reason visibility
						$('input[name="action"]').on('change', function() {
							const action = $(this).val();
							$('#adjustmentDetails').toggle(action === 'adjust');
							$('#rejectionReason').toggle(action === 'reject');
						});
					},
					preConfirm: () => {
						const action = document.querySelector('input[name="action"]:checked').value;
						const approvalNote = document.getElementById('approvalNote').value;

						let rejectReason = '';
						if (action === 'reject') {
							rejectReason = document.getElementById('rejectReason').value;
							if (!rejectReason.trim()) {
								Swal.showValidationMessage(__('rejection_reason_required', 'Please provide a reason for rejection'));
								return false;
							}
						}

						return { action, approvalNote, rejectReason };
					}
				}).then((result) => {
					if (result.isConfirmed) {
						const { action, approvalNote, rejectReason } = result.value;
						processRejoinApproval(rejoinRequestId, action, approvalNote, rejectReason);
					}
				});
			}

			function processRejoinApproval(rejoinRequestId, action, approvalNote, rejectReason) {
				Swal.fire({
					title: __('processing_title', 'Processing...'),
					html: __('please_wait_text', 'Please wait'),
					didOpen: () => Swal.showLoading(),
					allowOutsideClick: false,
					allowEscapeKey: false
				});

				$.ajax({
					url: './includes/ajaxFile/leaveHandler.php',
					type: 'POST',
					dataType: 'JSON',
					data: {
						ajaxType: 'processRejoinApproval',
						rejoin_request_id: rejoinRequestId,
						action: action,
						approval_note: approvalNote,
						rejection_reason: rejectReason
					},
					success: function(response) {
						if (response.status === 'success') {
							Swal.fire({
								title: __('success_title', 'Success'),
								text: response.message || __('request_processed_text', 'Request has been processed'),
								icon: 'success',
								confirmButtonText: __('ok', 'OK'),
								allowOutsideClick: false
							}).then(() => {
								location.reload();
							});
						} else {
							Swal.fire({
								title: __('error_title', 'Error'),
								text: response.message || __('processing_failed_text', 'Failed to process request'),
								icon: 'error',
								confirmButtonText: __('ok', 'OK')
							});
						}
					},
					error: function(jqXHR, textStatus, errorThrown) {
						Swal.fire({
							title: __('error_title', 'Error'),
							text: textStatus,
							icon: 'error',
							confirmButtonText: __('ok', 'OK')
						});
					}
				});
			}

			// Documents tab (redesign): type chips + search, preview in a SweetAlert2 viewer
			$(function() {
				var $docs = $('#documents');
				if (!$docs.length) return;
				function vdApply() {
					var f = $docs.find('.vd-chip.active').data('vd-filter') || '';
					var q = String($('#vdSearch').val() || '').trim().toLowerCase();
					var shown = 0;
					$docs.find('.vd-card').each(function() {
						var ok = (!f || this.getAttribute('data-doc-type') === f) && (!q || this.getAttribute('data-doc-search').indexOf(q) !== -1);
						this.hidden = !ok;
						if (ok) shown++;
					});
					$docs.find('.vd-noresult').prop('hidden', shown > 0);
				}
				$docs.on('click', '.vd-chip', function() {
					$docs.find('.vd-chip').removeClass('active');
					$(this).addClass('active');
					vdApply();
				});
				$docs.on('input', '#vdSearch', vdApply);

				function esc(v) { return $('<div>').text(v == null ? '' : String(v)).html(); }
				$docs.on('click', '.vd-open', function() {
					var $card = $(this).closest('.vd-card');
					var path = $card.data('doc-path'), ext = String($card.data('doc-ext') || ''), name = $card.data('doc-name');
					var dl = './downloadFile.php?file=' + encodeURIComponent(path);
					var body;
					if ($card.hasClass('is-missing')) {
						body = '<div class="vd-view-empty"><i class="fa fa-triangle-exclamation"></i><p><?= __('file_not_found', 'File not found') ?></p></div>';
					} else if (ext === 'pdf') {
						body = '<iframe src="' + esc(path) + '#view=FitH" title="' + esc(name) + '"></iframe>';
					} else if (['jpg', 'jpeg', 'png', 'gif', 'webp'].indexOf(ext) !== -1) {
						body = '<div class="vd-view-img"><img src="' + esc(path) + '" alt="' + esc(name) + '"></div>';
					} else if (['doc', 'docx', 'xls', 'xlsx'].indexOf(ext) !== -1) {
						body = '<iframe src="https://view.officeapps.live.com/op/embed.aspx?src=' + encodeURIComponent(window.location.origin + '/' + String(path).replace(/^\.\//, '')) + '"></iframe>';
					} else {
						body = '<div class="vd-view-empty"><i class="fa fa-file"></i><p><?= __('preview_not_available_for_file_type', 'Preview not available for this file type') ?></p>'
							+ '<a href="' + dl + '" class="vd-btn vd-btn-main"><i class="fa fa-download"></i> <?= __('download_file', 'Download File') ?></a></div>';
					}
					Swal.fire({
						html: '<div class="vd-view" style="--doc:' + esc($card[0].style.getPropertyValue('--doc')) + '">'
							+ '<div class="vd-view-head"><span class="vd-view-ic">' + $card.find('.vd-thumb-icon, .vd-thumb img').first().prop('outerHTML') + '</span>'
							+ '<div class="vd-view-title"><b>' + esc(name) + '</b><small>' + esc(ext.toUpperCase()) + ' · ' + esc($card.find('.vd-meta > span').map(function() { return $(this).text().trim(); }).get().join(' · ')) + '</small></div>'
							+ '<div class="vd-view-tools">'
							+ '<a href="' + esc(path) + '" target="_blank" rel="noopener" class="vd-btn" title="<?= __('open_in_new_tab', 'Open in new tab') ?>"><i class="fa fa-up-right-from-square"></i></a>'
							+ '<a href="' + dl + '" class="vd-btn" title="<?= __('download', 'Download') ?>"><i class="fa fa-download"></i></a>'
							+ '<button type="button" class="vd-btn vd-view-close" title="<?= __('close') ?>"><i class="fa fa-xmark"></i></button>'
							+ '</div></div><div class="vd-view-body">' + body + '</div></div>',
						showConfirmButton: false,
						allowOutsideClick: false,
						width: 'min(1100px, 96vw)',
						padding: 0,
						customClass: { popup: 'vd-view-swal srs-skip' },
						didOpen: function(popup) {
							$(popup).on('click', '.vd-view-close', function() { Swal.close(); });
						}
					});
				});
			});
		</script>

		<script type="text/javascript">
			$(document).ready(function() {

				var moreActionsHtml = <?= json_encode($moreActionsHtml); ?>;
				var moreActionsSub = <?= json_encode(trim(($emprow['name'] ?? '') . ' · ' . ($emprow['empid'] ?? ''), ' ·')) ?>;
				$('#moreActionsBtn').click(function() {
					var itemCount = ($('<div>').html(moreActionsHtml).find('.menu-item').length);
					var esc = function(s) { return $('<div>').text(s).html(); };
					Swal.fire({
						title: '<div class="ma-head"><div class="ma-title" style="font-size:18px;font-weight:700;color:var(--ma-text);">' + esc(<?= json_encode(__('more_actions')) ?>) + '</div>'
							+ (moreActionsSub ? '<p>' + esc(moreActionsSub) + '</p>' : '')
							+ (itemCount > 6 ? '<div class="ma-search"><i class="fa fa-search"></i><input type="search" id="maSearch" autocomplete="off" placeholder="' + esc(<?= json_encode(__('search_actions', 'Search actions...')) ?>) + '"></div>' : '')
							+ '</div>',
						html: '<div class="menu-items-container">' + moreActionsHtml
							+ '<div class="ma-noresult" style="display:none;"><i class="fa fa-magnifying-glass"></i><p>' + esc(<?= json_encode(__('no_matching_actions', 'No matching actions')) ?>) + '</p></div></div>',
						showConfirmButton: false,
						showCloseButton: true,
						customClass: {
							container: 'more-actions-modal',
							popup: 'swal2-popup',
							closeButton: 'swal2-close'
						},
						width: '620px',
						padding: '0',
						allowOutsideClick: false,
						didOpen: function() {
							// Get modal container and wrap it with jQuery
							var modalContainer = $(Swal.getHtmlContainer());

							// Keyboard: tiles focusable, Enter/Space activates
							modalContainer.find('.menu-item').attr('tabindex', '0').on('keydown', function(e) {
								if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); $(this).trigger('click'); }
							});

							// Search: hides non-matching tiles and empty groups; Enter opens the first match
							var $search = $('#maSearch');
							$search.on('input', function() {
								var q = $.trim(this.value).toLowerCase();
								modalContainer.find('.menu-item').each(function() {
									$(this).toggleClass('ma-hidden', q !== '' && $(this).text().toLowerCase().indexOf(q) === -1);
								});
								modalContainer.find('.ma-group').each(function() {
									$(this).toggleClass('ma-hidden', $(this).find('.menu-item:not(.ma-hidden)').length === 0);
								});
								modalContainer.find('.ma-noresult').toggle(modalContainer.find('.menu-item:not(.ma-hidden)').length === 0);
							}).on('keydown', function(e) {
								if (e.key === 'Enter') { e.preventDefault(); modalContainer.find('.menu-item:not(.ma-hidden)').first().trigger('click'); }
							});
							if ($search.length && window.matchMedia('(pointer: fine)').matches) $search.trigger('focus');

							// Sync / Add to D365: label follows the D365 widget's status; the widget runs the flow
							var $d365Item = modalContainer.find('.d365MenuAction');
							if ($d365Item.length && $('#d365Widget').attr('data-d365-state') === 'missing') {
								$d365Item.removeClass('text-teal').addClass('text-warning')
									.find('i').attr('class', 'fa fa-plus').end()
									.find('span').text(<?= json_encode(__('add_to_d365', 'Add to D365')) ?>);
							}
							$d365Item.on('click', function(e) {
								e.preventDefault();
								Swal.close();
								setTimeout(function() { document.dispatchEvent(new CustomEvent('d365:action-request')); }, 100);
							});

							// Add Documents
							modalContainer.find('.addEmpDocuAtter').on('click', function(e) {
								e.preventDefault();
								var eid = $(this).data('id');
								Swal.close();
								// Trigger on page element by finding it outside modal
								setTimeout(function() {
									$('.addEmpDocuAtter[data-id="' + eid + '"]').not('.swal2-html-container *, .doc-tab-upload').first().trigger('click');
								}, 100);
							});

							// Apply Loan
							modalContainer.find('.applyLoan').on('click', function(e) {
								e.preventDefault();
								var emp_id = $(this).data('emp_id');
								Swal.close();
								setTimeout(function() {
									$('.applyLoan[data-emp_id="' + emp_id + '"]').not('.swal2-html-container *').first().trigger('click');
								}, 100);
							});

							// Apply Vacation
							modalContainer.find('.applyvacationAtter').on('click', function(e) {
								e.preventDefault();
								var empid = $(this).data('empid');
								Swal.close();
								setTimeout(function() {
									$('.applyvacationAtter[data-empid="' + empid + '"]').not('.swal2-html-container *').first().trigger('click');
								}, 100);
							});

							// Apply Leave Request
							modalContainer.find('.applyLeaveRequest').on('click', function(e) {
								e.preventDefault();
								var empid = $(this).data('empid');
								Swal.close();
								setTimeout(function() {
									$('.applyLeaveRequest[data-empid="' + empid + '"]').not('.swal2-html-container *').first().trigger('click');
								}, 100);
							});

							// Create User Login
							modalContainer.find('.createUserDeptAjax').on('click', function(e) {
								e.preventDefault();
								var emp_id = $(this).data('emp_id');
								Swal.close();
								setTimeout(function() {
									$('.createUserDeptAjax[data-emp_id="' + emp_id + '"]').not('.swal2-html-container *').first().trigger('click');
								}, 100);
							});

							// Add Note
							modalContainer.find('.addnote').on('click', function(e) {
								e.preventDefault();
								var emp_id = $(this).data('emp_id');
								Swal.close();
								setTimeout(function() {
									$('a.addnote[data-emp_id="' + emp_id + '"]').not('.swal2-html-container *').first().trigger('click');
								}, 100);
							});

							// Apply Resignation
							modalContainer.find('.applyResignation').on('click', function(e) {
								e.preventDefault();
								var emp_id = $(this).data('emp_id');
								var emp_name = $(this).data('emp_name');
								Swal.close();
								setTimeout(function() {
									openResignationWizard(emp_id, emp_name);
								}, 100);
							});

							// Direct Rejoin (bypass approval chain) - special access only
							modalContainer.find('.directRejoinBypass').on('click', function(e) {
								e.preventDefault();
								var empid = $(this).data('empid');
								var vacid = $(this).data('vacid');
								var empname = $(this).data('empname');
								Swal.close();
								setTimeout(function() {
									openDirectRejoinModal(empid, vacid, empname);
								}, 100);
							});

						}
					});
				});

				// Check for SweetAlert message from session (after edit redirect)
				<?php if (isset($_SESSION['swal_alert'])): ?>
					Swal.fire({
						title: '<?= addslashes($_SESSION['swal_alert']['title']) ?>',
						text: '<?= addslashes($_SESSION['swal_alert']['message']) ?>',
						icon: '<?= $_SESSION['swal_alert']['type'] ?>',
						confirmButtonText: '<?= __("ok") ?>',
						allowOutsideClick: false,
						customClass: {
							confirmButton: 'btn btn-primary'
						},
						buttonsStyling: false
					});
					<?php unset($_SESSION['swal_alert']); ?>
				<?php endif; ?>

				$('#assets_tbl').DataTable({
					language: {
						search: `<span>${__('search')}:</span> _INPUT_`,
						searchPlaceholder: `${__('search')}...`,
						lengthMenu: `${__('show')} _MENU_ ${__('entries')}`,
						info: `${__('showing')} _START_ ${__('to')} _END_ ${__('of')} _TOTAL_ ${__('entries')}`,
						infoEmpty: `${__('showing')} 0 ${__('to')} 0 ${__('of')} 0 ${__('entries')}`,
						infoFiltered: `(${__('filtered_from')} _MAX_ ${__('total_entries')})`,
						paginate: {
							first: __('first'),
							last: __('last'),
							next: '<i class="mdi mdi-chevron-right"></i>',
							previous: '<i class="mdi mdi-chevron-left"></i>'
						},
						emptyTable: __('no_data_available_in_table'),
						zeroRecords: __('no_matching_records_found'),
						processing: `<div class="spinner-border text-primary" role="status"><span class="visually-hidden">${__('loading')}...</span></div>`
					}
				});

				// Event listener for the print button
				$('#assets_tbl').on('click', '.print-return-btn', function(event) {
					event.preventDefault(); // Prevent the link from opening immediately

					const assetRecordId = $(this).data('asset-id');
					const baseUrl = $(this).attr('href');

					Swal.fire({
						title: __('return_date'),
						html: '<input type="text" id="view-emp-return-date-input" class="form-control" value="' + new Date().toISOString().slice(0, 10) + '">',
						showCancelButton: true,
						confirmButtonText: __('print_for_return'),
						confirmButtonColor: APP_COLORS.info,
						cancelButtonText: __('cancel'),
						cancelButtonColor: APP_COLORS.danger,
						allowOutsideClick: false,
						didOpen: function() {
							AppDate.single('#view-emp-return-date-input');
						},
						preConfirm: () => {
							const selectedDate = $('#view-emp-return-date-input').val();
							if (!selectedDate) {
								Swal.showValidationMessage(__('please_select_return_date', 'Please select a return date'));
								return false;
							}
							return selectedDate;
						}
					}).then((result) => {
						if (result.isConfirmed && result.value) {
							const selectedDate = result.value;
							const joinChar = baseUrl.includes('?') ? '&' : '?';
							const urlWithDate = baseUrl + joinChar + 'print_return_date=' + encodeURIComponent(selectedDate);
							window.open(urlWithDate, '_blank');
							$('#submit-return-btn-' + assetRecordId).prop('disabled', false);
						}
					});
				});
			});

			$(document).ready(function() {

				var buttonConfig = [];
				var exportTitle = "<?= __('name') ?>: <?= $emprow['name'] ?>"
				buttonConfig.push({
					extend: 'excel',
					exportOptions: {
						columns: [0, 1, 2, 3, 4, 5, 6, 7]
					},
					title: exportTitle,
					className: 'btn-success'
				});
				buttonConfig.push({
					extend: 'pdf',
					exportOptions: {
						columns: [0, 1, 2, 3, 4, 5, 6, 7]
					},
					title: exportTitle,
					className: 'btn-danger'
				});
				buttonConfig.push({
					extend: 'print',
					exportOptions: {
						columns: [0, 1, 2, 3, 4, 5, 6, 7]
					},
					title: exportTitle,
					className: 'btn-dark'
				});
				// buttonConfig.push({text: '<i class="fa fa-plus"></i> Add Machine', action: function ( e, dt, button, config ) {window.location = './add_machine.php' } ,className: 'btn-info'});
				$('form').parsley();

				//Buttons examples
				var table = $('#employee_vac').DataTable({
					lengthChange: false,
					buttons: buttonConfig,
					order: [
						[0, "desc"]
					],
					"columnDefs": [{
						targets: [0],
						width: '50px'
					}, ],
					"responsive": true,
					language: {
						search: `<span>${__('search')}:</span> _INPUT_`,
						searchPlaceholder: `${__('search')}...`,
						lengthMenu: `${__('show')} _MENU_ ${__('entries')}`,
						info: `${__('showing')} _START_ ${__('to')} _END_ ${__('of')} _TOTAL_ ${__('entries')}`,
						infoEmpty: `${__('showing')} 0 ${__('to')} 0 ${__('of')} 0 ${__('entries')}`,
						infoFiltered: `(${__('filtered_from')} _MAX_ ${__('total_entries')})`,
						paginate: {
							first: __('first'),
							last: __('last'),
							next: '<i class="mdi mdi-chevron-right"></i>',
							previous: '<i class="mdi mdi-chevron-left"></i>'
						},
						emptyTable: __('no_data_available_in_table'),
						zeroRecords: __('no_matching_records_found'),
						processing: `<div class="spinner-border text-primary" role="status"><span class="visually-hidden">${__('loading')}...</span></div>`
					}
				});

				table.buttons().container()
					.appendTo('#employee_vac_wrapper .col-md-6:eq(0)');

				$('#loan_history_tbl').DataTable({
					language: {
						search: `<span>${__('search')}:</span> _INPUT_`,
						searchPlaceholder: `${__('search')}...`,
						lengthMenu: `${__('show')} _MENU_ ${__('entries')}`,
						info: `${__('showing')} _START_ ${__('to')} _END_ ${__('of')} _TOTAL_ ${__('entries')}`,
						infoEmpty: `${__('showing')} 0 ${__('to')} 0 ${__('of')} 0 ${__('entries')}`,
						infoFiltered: `(${__('filtered_from')} _MAX_ ${__('total_entries')})`,
						paginate: {
							first: __('first'),
							last: __('last'),
							next: '<i class="mdi mdi-chevron-right"></i>',
							previous: '<i class="mdi mdi-chevron-left"></i>'
						},
						emptyTable: __('no_data_available_in_table'),
						zeroRecords: __('no_matching_records_found'),
						processing: `<div class="spinner-border text-primary" role="status"><span class="visually-hidden">${__('loading')}...</span></div>`
					}
				});
				$('#payroll_history_tbl').DataTable({
					columnDefs: [
						{ orderable: false, searchable: false, targets: -1 }
					],
					order: [],
					language: {
						search: `<span>${__('search')}:</span> _INPUT_`,
						searchPlaceholder: `${__('search')}...`,
						lengthMenu: `${__('show')} _MENU_ ${__('entries')}`,
						info: `${__('showing')} _START_ ${__('to')} _END_ ${__('of')} _TOTAL_ ${__('entries')}`,
						infoEmpty: `${__('showing')} 0 ${__('to')} 0 ${__('of')} 0 ${__('entries')}`,
						infoFiltered: `(${__('filtered_from')} _MAX_ ${__('total_entries')})`,
						paginate: {
							first: __('first'),
							last: __('last'),
							next: '<i class="mdi mdi-chevron-right"></i>',
							previous: '<i class="mdi mdi-chevron-left"></i>'
						},
						emptyTable: __('no_data_available_in_table'),
						zeroRecords: __('no_matching_records_found'),
						processing: `<div class="spinner-border text-primary" role="status"><span class="visually-hidden">${__('loading')}...</span></div>`
					}
				});
				<?php if ($d365_pay_state !== null && $can_d365_sync): ?>
				// Payrolls tab: sync a missing / failed paid month to D365 (includes/ajaxFile/d365_employee.php)
				$(document).on('click', '.btn-d365-pay', function () {
					var month = String($(this).data('month'));
					var d365Pop = { popup: 'sr-addline-popup sr-page' };
					Swal.fire({
						title: <?= json_encode(__('sync_month_to_d365', 'Sync %s to D365?')) ?>.replace('%s', $('<i>').text(month).html()),
						html: '<div class="sr-form"><dl class="sr-kv">'
							+ '<div class="row-kv"><dt><?= htmlspecialchars(__('employee', 'Employee'), ENT_QUOTES) ?></dt><dd><?= htmlspecialchars($emprow['empid'], ENT_QUOTES) ?></dd></div>'
							+ '<div class="row-kv"><dt><?= htmlspecialchars(__('payroll_month', 'Payroll month'), ENT_QUOTES) ?></dt><dd>' + $('<i>').text(month).html() + '</dd></div>'
							+ '<div class="row-kv"><dt><?= htmlspecialchars(__('d365_environment', 'D365 environment'), ENT_QUOTES) ?></dt><dd><span class="sr-pill sr-pill-xs tone-amber"><?= strtoupper(htmlspecialchars($d365_pay_env, ENT_QUOTES)) ?></span></dd></div>'
							+ '<div class="row-kv"><dt><?= htmlspecialchars(__('journal', 'Journal'), ENT_QUOTES) ?></dt><dd><?= htmlspecialchars(__('d365_unposted_journal_line', 'Unposted line in the journal of the employee payroll company'), ENT_QUOTES) ?></dd></div>'
							+ '</dl></div>',
						showCancelButton: true,
						cancelButtonText: <?= json_encode(__('cancel', 'Cancel')) ?>,
						confirmButtonText: '<i class="mdi mdi-cloud-sync"></i> ' + <?= json_encode(__('sync_to_d365', 'Sync to D365')) ?>,
						confirmButtonColor: (window.APP_COLORS && APP_COLORS.primary) || undefined,
						cancelButtonColor: (window.APP_COLORS && APP_COLORS.danger_dark) || undefined,
						width: '560px',
						customClass: d365Pop,
						showLoaderOnConfirm: true,
						allowOutsideClick: false,
						preConfirm: function () {
							var fd = new FormData();
							fd.append('csrf', <?= json_encode($_SESSION['d365_csrf'] ?? '') ?>);
							fd.append('action', 'sync_payroll');
							fd.append('emp_id', <?= json_encode((string)$emprow['empid']) ?>);
							fd.append('month', month);
							return fetch('includes/ajaxFile/d365_employee.php', { method: 'POST', body: fd, credentials: 'same-origin' })
								.then(function (r) { return r.json().catch(function () { throw new Error('Server error (HTTP ' + r.status + ')'); }); })
								.catch(function (err) { return { ok: false, error: err.message }; });
						}
					}).then(function (res) {
						if (!res.isConfirmed) return;
						var r = res.value || {};
						var cells = $('.d365-pay-cell[data-month="' + month + '"]');
						if (r.ok) {
							cells.html($('<span class="badge badge-success"><i class="fa fa-check"></i> </span>').append(document.createTextNode((r.entity || '') + ' · ' + (r.journal || ''))));
							$('.btn-d365-pay[data-month="' + month + '"]').remove(); // copies in responsive child rows
							Swal.fire({ icon: 'success', title: <?= json_encode(__('synced_to_d365', 'Synced to D365')) ?>, customClass: d365Pop, html: '<div class="sr-form"><dl class="sr-kv"><div class="row-kv"><dt><?= htmlspecialchars(__('journal', 'Journal'), ENT_QUOTES) ?></dt><dd>' + $('<i>').text(r.journal || '').html() + '</dd></div><div class="row-kv"><dt><?= htmlspecialchars(__('company', 'Company'), ENT_QUOTES) ?></dt><dd>' + $('<i>').text(r.entity || '').html() + '</dd></div></dl><span class="sr-fhint mt-2"><?= htmlspecialchars(__('d365_unposted_finance_posts', 'Unposted - finance posts it in D365.'), ENT_QUOTES) ?></span></div>' });
						} else {
							cells.find('.badge').replaceWith($('<span class="badge badge-danger"><i class="fa fa-times"></i> ' + <?= json_encode(__('failed_status', 'Failed')) ?> + '</span>').attr('title', r.error || ''));
							Swal.fire({ icon: 'error', title: <?= json_encode(__('sync_failed', 'Sync failed')) ?>, text: r.error || <?= json_encode(__('unknown_error', 'Unknown error')) ?>, customClass: d365Pop });
						}
					});
				});
				<?php endif; ?>
				$('#payment_history_tbl').DataTable({
					language: {
						search: `<span>${__('search')}:</span> _INPUT_`,
						searchPlaceholder: `${__('search')}...`,
						lengthMenu: `${__('show')} _MENU_ ${__('entries')}`,
						info: `${__('showing')} _START_ ${__('to')} _END_ ${__('of')} _TOTAL_ ${__('entries')}`,
						infoEmpty: `${__('showing')} 0 ${__('to')} 0 ${__('of')} 0 ${__('entries')}`,
						infoFiltered: `(${__('filtered_from')} _MAX_ ${__('total_entries')})`,
						paginate: {
							first: __('first'),
							last: __('last'),
							next: '<i class="mdi mdi-chevron-right"></i>',
							previous: '<i class="mdi mdi-chevron-left"></i>'
						},
						emptyTable: __('no_data_available_in_table'),
						zeroRecords: __('no_matching_records_found'),
						processing: `<div class="spinner-border text-primary" role="status"><span class="visually-hidden">${__('loading')}...</span></div>`
					}
				});

				if ($('#employee_vac_activity_log').length) {
					$('#employee_vac_activity_log').DataTable({
						order: [
							[0, "desc"]
						],
						"responsive": true,
						language: {
							search: `<span>${__('search')}:</span> _INPUT_`,
							searchPlaceholder: `${__('search')}...`,
							lengthMenu: `${__('show')} _MENU_ ${__('entries')}`,
							info: `${__('showing')} _START_ ${__('to')} _END_ ${__('of')} _TOTAL_ ${__('entries')}`,
							infoEmpty: `${__('showing')} 0 ${__('to')} 0 ${__('of')} 0 ${__('entries')}`,
							infoFiltered: `(${__('filtered_from')} _MAX_ ${__('total_entries')})`,
							paginate: {
								first: __('first'),
								last: __('last'),
								next: '<i class="mdi mdi-chevron-right"></i>',
								previous: '<i class="mdi mdi-chevron-left"></i>'
							},
							emptyTable: __('no_data_available_in_table'),
							zeroRecords: __('no_matching_records_found'),
							processing: `<div class="spinner-border text-primary" role="status"><span class="visually-hidden">${__('loading')}...</span></div>`
						}
					});
				}

				if ($('#employee_vac_cron_snapshot').length) {
					$('#employee_vac_cron_snapshot').DataTable({
						order: [
							[0, "desc"]
						],
						"responsive": true,
						language: {
							search: `<span>${__('search')}:</span> _INPUT_`,
							searchPlaceholder: `${__('search')}...`,
							lengthMenu: `${__('show')} _MENU_ ${__('entries')}`,
							info: `${__('showing')} _START_ ${__('to')} _END_ ${__('of')} _TOTAL_ ${__('entries')}`,
							infoEmpty: `${__('showing')} 0 ${__('to')} 0 ${__('of')} 0 ${__('entries')}`,
							infoFiltered: `(${__('filtered_from')} _MAX_ ${__('total_entries')})`,
							paginate: {
								first: __('first'),
								last: __('last'),
								next: '<i class="mdi mdi-chevron-right"></i>',
								previous: '<i class="mdi mdi-chevron-left"></i>'
							},
							emptyTable: __('no_data_available_in_table'),
							zeroRecords: __('no_matching_records_found'),
							processing: `<div class="spinner-border text-primary" role="status"><span class="visually-hidden">${__('loading')}...</span></div>`
						}
					});
				}

			});
			jQuery(function($) {
				$('.autonumber').autoNumeric('init');
			});
			jQuery.browser = {};
			(function() {
				jQuery.browser.msie = false;
				jQuery.browser.version = 0;
				if (navigator.userAgent.match(/MSIE ([0-9]+)\./)) {
					jQuery.browser.msie = true;
					jQuery.browser.version = RegExp.$1;
				}
			})();

			$(document).ready(function() {


				$("input[name$='note']").click(function() {
					var value = $(this).val();
					if (value == 'Encashed') {
						$("#return_date").show();
						$("#note").hide();
						$("#return_date").removeAttr('required');
						$("#permit_no").removeAttr('required');
					} else if (value == 'Fly') {
						//document.getElementById("pet_id").required = true;
						$("#return_date").attr('required', '');
						$("#permit_no").attr('required', '');
						$("#note").show();
						//    $("#pet_id_box").hide();
					}
				});
				$("#return_date").removeAttr('required');
				//  	$("#pet_id_box").show();
				$("#note").hide();
			});
			/**
			 * This script should be placed within a PHP file where an employee's
			 * data (like $emprow) is available.
			 */
			// Global variable for the DataTable instance
			let noteTable;
			// Safely pass the PHP employee ID to a JavaScript variable.
			const employeeId = <?= json_encode($emprow['empid']); ?>;
			const canManageAttendanceRecord = <?= $canManageAttendanceRecord ? 'true' : 'false' ?>;
			const canAttendanceRowActions = <?= $canAttendanceRowActions ? 'true' : 'false' ?>; // Action column (edit/delete keys)
			const canAddAttendanceRecord = <?= $canAddAttendanceRecord ? 'true' : 'false' ?>;
			$(document).ready(function() {
				// 1. Initialize the DataTable with the correct columns for notes.
				initializeNotesTable();
				// 2. Fetch the notes data from the server and populate the table.
				fetchNotes();
			});
			/**
			 * Initializes the DataTable with column definitions that match the HTML <thead>.
			 * Your backend MUST return 'id', 'emp_id', 'name', 'created_at', and 'note' for each record.
			 */
			function initializeNotesTable() {
				noteTable = $('#notes_tbl').DataTable({
					// Define columns to match your HTML: <th>id</th>, <th>Emp ID.</th>, etc.
					autoWidth: false, // Add this line
					columns: [{
							data: 'id',
							title: __('id'),
						},
						{
							data: 'emp_id',
							title: __('emp_id')
						},
						{
							data: 'name',
							title: __('employee_name')
						},
						{
							data: 'note_type',
							title: __('note_type')
						},
						{
							data: 'note',
							title: __('notes')
						},
						{
							data: 'attachment',
							title: __('attachment')
						},
						{
							data: 'created_at',
							title: __('created_at')
						},
						{
							data: null,
							title: __('action')
						}
					],
					columnDefs: [{
							targets: 0,
							width: '20px',
							visible: false,
							searchable: false,
						},
						{
							targets: 1,
							width: '60px',
						},
						{
							targets: 3,
							width: '120px',
							render: function(data, type, row) {
								if (type === 'display') {
									if (!data) return '<span class="badge badge-secondary"><?= __('general', 'General') ?></span>';

									// Map note types to badge colors and labels
									const typeMap = {
										'warning': {
											color: 'warning',
											icon: 'fa-exclamation-triangle',
											label: '<?= __('warning', 'Warning') ?>'
										},
										'sick_leave': {
											color: 'info',
											icon: 'fa-notes-medical',
											label: '<?= __('sick_leave', 'Sick Leave') ?>'
										},
										'appreciation': {
											color: 'success',
											icon: 'fa-star',
											label: '<?= __('appreciation', 'Appreciation') ?>'
										},
										'violation': {
											color: 'danger',
											icon: 'fa-ban',
											label: '<?= __('violation', 'Violation') ?>'
										},
										'absence': {
											color: 'dark',
											icon: 'fa-user-slash',
											label: '<?= __('absence', 'Absence') ?>'
										},
										'late_arrival': {
											color: 'warning',
											icon: 'fa-clock',
											label: '<?= __('late_arrival', 'Late Arrival') ?>'
										},
										'performance_review': {
											color: 'primary',
											icon: 'fa-chart-line',
											label: '<?= __('performance_review_label', 'Performance') ?>'
										},
										'training': {
											color: 'info',
											icon: 'fa-graduation-cap',
											label: '<?= __('training', 'Training') ?>'
										},
										'promotion': {
											color: 'success',
											icon: 'fa-arrow-up',
											label: '<?= __('promotion', 'Promotion') ?>'
										},
										'salary_adjustment': {
											color: 'primary',
											icon: 'fa-money-bill',
											label: '<?= __('salary_adjustment_label', 'Salary') ?>'
										},
										'disciplinary_action': {
											color: 'danger',
											icon: 'fa-gavel',
											label: '<?= __('disciplinary_action_label', 'Disciplinary') ?>'
										},
										'medical_report': {
											color: 'info',
											icon: 'fa-file-medical',
											label: '<?= __('medical_report_label', 'Medical') ?>'
										},
										'general': {
											color: 'secondary',
											icon: 'fa-sticky-note',
											label: '<?= __('general', 'General') ?>'
										},
										'other': {
											color: 'secondary',
											icon: 'fa-ellipsis-h',
											label: '<?= __('other', 'Other') ?>'
										}
									};

									const typeInfo = typeMap[data] || {
										color: 'secondary',
										icon: 'fa-sticky-note',
										label: data
									};
									return `<span class="badge badge-${typeInfo.color}"><i class="fa ${typeInfo.icon}"></i> ${typeInfo.label}</span>`;
								}
								return data;
							}
						},
						{
							targets: 4,
							render: function(data, type, row) {
								if (type === 'display' && data) {
									// Truncate long notes and add tooltip
									if (data.length > 50) {
										return `<span title="${data.replace(/"/g, '&quot;')}">${data.substring(0, 50)}...</span>`;
									}
									return data;
								}
								return data;
							}
						},
						{
							targets: 5,
							width: '100px',
							orderable: false,
							render: function(data, type, row) {
								if (type === 'display') {
									if (data && data !== '') {
										// Extract file extension
										const fileExt = data.split('.').pop().toLowerCase();
										let iconClass = 'fa-file';
										let badgeColor = 'secondary';

										// Set icon and color based on file type
										if (fileExt === 'pdf') {
											iconClass = 'fa-file-pdf';
											badgeColor = 'danger';
										} else if (['doc', 'docx'].includes(fileExt)) {
											iconClass = 'fa-file-word';
											badgeColor = 'primary';
										} else if (['jpg', 'jpeg', 'png', 'gif'].includes(fileExt)) {
											iconClass = 'fa-file-image';
											badgeColor = 'success';
										}

										return `<a href="${data}" target="_blank" class="btn btn-sm btn-${badgeColor}" title="<?= __('view_attachments', 'View Attachment') ?>">
											<i class="fa ${iconClass}"></i> <?= __('view') ?>
										</a>`;
									}
									return '<span class="text-muted"><i class="fa fa-minus"></i> <?= __('no_file_text', 'No File') ?></span>';
								}
								return data;
							}
						},
						{
							targets: 6,
							width: '120px',
							render: function(data, type, row) {
								if (type === 'display' && data) {
									return new Date(data).toLocaleDateString('en-US', {
										year: 'numeric',
										month: 'short',
										day: 'numeric'
									});
								}
								return data;
							}
						},
						{
							targets: 7,
							width: '60px',
							orderable: false,
							render: function(data, type, row) {
								return `<button class="btn btn-danger btn-sm isDeleteAjax" data-tbl='emp_notice' data-id="${row.id}"><i class="fa fa-trash"></i></button>`;
							}
						},
						{
							targets: [0, 1, 5, 7],
							className: 'text-center'
						},
					],
					order: [
						[6, 'desc']
					], // Default sort by 'Created at' (now the 7th column) descending
					pageLength: 10,
					lengthMenu: [5, 10, 25, 50],
					language: {
						search: `<span>${__('search')}:</span> _INPUT_`,
						searchPlaceholder: `${__('search')}...`,
						lengthMenu: `${__('show')} _MENU_ ${__('entries')}`,
						info: `${__('showing')} _START_ ${__('to')} _END_ ${__('of')} _TOTAL_ ${__('entries')}`,
						infoEmpty: `${__('showing')} 0 ${__('to')} 0 ${__('of')} 0 ${__('entries')}`,
						infoFiltered: `(${__('filtered_from')} _MAX_ ${__('total_entries')})`,
						paginate: {
							first: __('first'),
							last: __('last'),
							next: '<i class="mdi mdi-chevron-right"></i>',
							previous: '<i class="mdi mdi-chevron-left"></i>'
						},
						emptyTable: __('no_data_available_in_table'),
						zeroRecords: __('no_matching_records_found'),
						processing: `<div class="spinner-border text-primary" role="status"><span class="visually-hidden">${__('loading')}...</span></div>`
					}
				});
			}

			/**
			 * Fetches notes for a specific employee using a POST request.
			 */
			async function fetchNotes() {
				const loadingIndicator = $('#loading-indicator');
				const noDataMessage = $('#noDataMessage');
				loadingIndicator.removeClass('hidden');
				noDataMessage.addClass('hidden');
				noteTable.clear().draw();
				const apiUrl = './includes/ajaxFile/hrHandler.php';
				// Prepare the data for the POST request
				const postData = new URLSearchParams();
				postData.append('ajaxType', 'view_notes');
				postData.append('emp_id', employeeId);
				try {
					const response = await fetch(apiUrl, {
						method: 'POST',
						headers: {
							// This header is crucial for the server to correctly interpret the POST data
							'Content-Type': 'application/x-www-form-urlencoded',
						},
						body: postData
					});
					if (!response.ok) {
						const errorText = await response.text();
						throw new Error(`Server responded with status ${response.status}: ${errorText}`);
					}
					const data = await response.json();
					// Check if the response is successful and contains a 'notes' array
					if (data.status === 'success' && data.notes && data.notes.length > 0) {
						noteTable.clear().rows.add(data.notes).draw();
					} else {
						noDataMessage.text(data.message || '<?= __('no_notes_found_for_employee', 'No notes found for this employee.') ?>').removeClass('hidden');
						noteTable.clear().draw();
					}
				} catch (error) {
					console.error('Error fetching notes:', error);
					noDataMessage.text(`<?= __('an_error_occurred', 'An error occurred') ?>: ${error.message}`).removeClass('hidden');
					noteTable.clear().draw();
				} finally {
					loadingIndicator.addClass('hidden');
				}
			}

			// Attendance tab - client-side DataTable (fetches this employee's full
			// filtered history in one call, no server-side pagination) so the Buttons
			// export below can cover everything matched, not just whatever page is on
			// screen. A single AppDate range field drives the From/To filter, defaulting
			// to the current month; "Show all records" disables it and bypasses the filter.
			let attendanceTable;
			let attendanceTableInitialized = false;
			$('a[href="#attendance"]').on('shown.bs.tab', function() {
				if (attendanceTableInitialized) {
					return;
				}
				attendanceTableInitialized = true;

				// Date filter (Today / This week / This month / All + From-To, no future days) and status
				// tiles: same component + rules as attendance.php (assets/js/attendance_ui.js). Default: this month.
				const attDates = AttUI.bindDateFilter({
					range: '#attTabRange', from: '#attTabFrom', to: '#attTabTo', active: 'month',
					onChange: function() { if (attendanceTable) attendanceTable.ajax.reload(); }
				});

				// Cell helpers. Renderers only change the 'display' output; sort/search/export
				// keep the raw values from attendanceEmpAjaxTbl.php.
				const aEsc = s => $('<div>').text(s == null ? '' : String(s)).html();
				const aDash = '<span class="att-muted">&ndash;</span>';
				const aEmpty = v => v == null || v === '' || v === '-';
				const aTone = s => {
					s = String(s || '');
					if (s === 'Present') return 'green';
					if (s.indexOf('Incomplete') > -1 || s === 'Absent') return 'red';
					if (s.indexOf('Late') === 0) return 'amber';
					if (s.indexOf('Early Leave') > -1 || s === 'Half Day') return 'sky';
					return 'slate';
				};
				const aHm = v => { const m = /^(\d{1,3}):(\d{2})/.exec(v || ''); return m ? (+m[1]) * 60 + (+m[2]) : null; };
				const aDur = mins => Math.floor(mins / 60) + __('hours_short', 'h') + ' ' + String(mins % 60).padStart(2, '0') + __('minutes_short', 'm');
				const aDisplay = fn => (v, type, row) => type === 'display' ? fn(v, row) : v;

				function updateAttendanceStats(rows) {
					AttUI.setCounts('#attStats', AttUI.count(rows));
					// Selected period under the title
					const [from, to] = attDates.values();
					const fmtD = d => { const x = new Date(d + 'T00:00:00'); return isNaN(x) ? d : x.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' }); };
					$('#attTabPeriod').text(!from && !to ? __('all_records', 'All records') : `${fmtD(from || to)} – ${fmtD(to || from)}`);
				}

				// "Total hours" tile: worked time of the rows currently shown (period + tile filter + search),
				// refreshed on every table draw. Days without both punches have no hours and are skipped.
				function updateAttendanceTotalHours() {
					let mins = 0, days = 0;
					attendanceTable.rows({ search: 'applied' }).data().each(r => { const m = aHm(r.hours); if (m !== null) { mins += m; days++; } });
					$('#attTotalHours').text(aDur(mins));
					$('#attTotalAvg').text(days ? `${days} ${__('days', 'days')} · ${__('avg', 'Avg')} ${aDur(Math.round(mins / days))}` : ' ');
				}

				attendanceTable = $('#attendance_tbl').DataTable({
					processing: true,
					serverSide: false,
					order: [[0, 'desc']],
					pageLength: 15,
					dom: 'rt<"att-dt-bottom"ip>',
					buttons: (function() {
						const exportColumns = function(idx, data, node) {
							const colCount = $('#attendance_tbl').DataTable().columns().header().length;
							return canAttendanceRowActions ? idx < colCount - 1 : true;
						};
						// modifier: page 'all' - export every filtered/sorted row across all
						// pages of the table (not just whatever page happens to be showing),
						// search/order 'applied' - respect the current search box + sort.
						const exportModifier = { page: 'all', search: 'applied', order: 'applied' };
						return [
							{ extend: 'copy', text: `<i class="mdi mdi-content-copy"></i> ${__('copy', 'Copy')}`, exportOptions: { columns: exportColumns, modifier: exportModifier } },
							{ extend: 'excel', text: `<i class="mdi mdi-file-excel"></i> ${__('excel', 'Excel')}`, filename: `attendance_${employeeId}`, exportOptions: { columns: exportColumns, modifier: exportModifier } },
							{ extend: 'pdf', text: `<i class="mdi mdi-file-pdf"></i> PDF`, filename: `attendance_${employeeId}`, exportOptions: { columns: exportColumns, modifier: exportModifier } },
							{ extend: 'print', text: `<i class="mdi mdi-printer"></i> ${__('print', 'Print')}`, exportOptions: { columns: exportColumns, modifier: exportModifier } }
						];
					})(),
					ajax: {
						url: './includes/ajaxFile/attendanceEmpAjaxTbl.php',
						type: 'POST',
						dataSrc: function(json) {
							const rows = (json && json.data) || [];
							updateAttendanceStats(rows);
							return rows;
						},
						data: function(d) {
							d.emp_id = employeeId;
							const [from, to] = attDates.values();
							d.fromdate = from;
							d.todate = to;
						}
					},
					columns: (function() {
						const cols = [
							{ data: 'date', render: aDisplay(d => {
								const x = new Date(d + 'T00:00:00');
								if (isNaN(x)) return aEsc(d);
								const wk = x.getDay(); // Fri/Sat = weekend
								return `<span class="sr-date${wk === 5 || wk === 6 ? ' att-weekend' : ''}">${x.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' })}<small>${x.toLocaleDateString('en-US', { weekday: 'long' })}</small></span>`;
							}) },
							{ data: 'check_in', render: aDisplay(v => aEmpty(v) ? aDash : `<span class="att-time in"><i class="mdi mdi-login"></i>${aEsc(v)}</span>`) },
							{ data: 'check_out', render: aDisplay(v => aEmpty(v) ? aDash : `<span class="att-time out"><i class="mdi mdi-logout"></i>${aEsc(v)}</span>`) },
							{ data: 'hours', render: aDisplay(v => { const m = aHm(v); return m === null ? aDash : `<span class="att-hours">${aDur(m)}</span>`; }) },
							{ data: 'state', render: aDisplay(s => `<span class="sr-pill sr-pill-xs tone-${aTone(s)}"><span class="sr-dot"></span>${aEsc(pAttStateLabel(s))}</span>`) },
							{ data: 'late_minutes', render: aDisplay(v => aEmpty(v) ? aDash : `<span class="att-mins tone-amber">${aEsc(v)} ${__('min', 'min')}</span>`) },
							{ data: 'early_minutes', render: aDisplay(v => aEmpty(v) ? aDash : `<span class="att-mins tone-sky">${aEsc(v)} ${__('min', 'min')}</span>`) },
							{ data: 'note', render: aDisplay(n => aEmpty(n) ? aDash : `<span class="att-note" title="${aEsc(n)}">${aEsc(n)}</span>`) }
						];
						if (canAttendanceRowActions) {
							cols.push({ data: 'action', orderable: false, searchable: false, className: 'text-right', render: aDisplay(html =>
								// Server sends a Bootstrap dropdown; restyle its toggle like the other sr tables
								String(html || '').replace('table-action-btn dropdown-toggle arrow-none btn btn-light btn-sm', 'sr-more-btn dropdown-toggle arrow-none')
									.replace('mdi-dots-horizontal', 'mdi-dots-vertical')) });
						}
						return cols;
					})(),
					language: {
						info: `${__('showing')} _START_ ${__('to')} _END_ ${__('of')} _TOTAL_ ${__('entries')}`,
						infoEmpty: `${__('showing')} 0 ${__('to')} 0 ${__('of')} 0 ${__('entries')}`,
						infoFiltered: '',
						paginate: { first: __('first'), last: __('last'), next: '<i class="mdi mdi-chevron-right"></i>', previous: '<i class="mdi mdi-chevron-left"></i>' },
						emptyTable: `<div class="sr-empty"><i class="mdi mdi-calendar-blank"></i>${__('no_data_available_in_table')}</div>`,
						zeroRecords: `<div class="sr-empty"><i class="mdi mdi-magnify"></i>${__('no_matching_records_found')}</div>`,
						processing: __('processing', 'Processing...')
					}
				});
				// Export buttons next to the search box instead of above the table
				attendanceTable.buttons().container().appendTo('#attExport');
				attendanceTable.on('draw', updateAttendanceTotalHours);
				$('#attStats').addClass('has-total'); // Total hours tile always shown here (one employee)
				let attSearchTimer;
				$('#attTabSearch').on('input', function() {
					clearTimeout(attSearchTimer);
					const v = this.value;
					attSearchTimer = setTimeout(() => attendanceTable.search(v).draw(), 250);
				});

				// Status tiles filter the loaded rows (AttUI rules = attendance.php server rules);
				// clicking the active tile again, or "All", shows every row again.
				const attTiles = AttUI.bindTiles('#attStats', function() { attendanceTable.draw(); });
				$.fn.dataTable.ext.search.push(function(settings, data, dataIndex, row) {
					if (settings.nTable.id !== 'attendance_tbl' || !attTiles.get()) return true;
					return AttUI.matches(attTiles.get(), (row || {}).state);
				});
			});

			// Manual add/edit for a forgotten punch - fixed to this employee, reuses the
			// same includes/ajaxFile/attendanceAjax.php backend and the same SRForm layout as the
			// standalone Attendance Record page (attendance.php), just without an employee picker.
			const P_ATT_STATES = ['Present', 'Late', 'Early Leave', 'Late & Early Leave', 'Incomplete', 'Late & Incomplete', 'Half Day', 'Leave', 'Day Off', 'Absent'];
			const P_ATT_EMP_LABEL = <?= json_encode(($emprow['empid'] ?? '') . ' - ' . ($emprow['name'] ?? '')) ?>;
			const pAttHm = v => String(v || '').slice(0, 5); // 'HH:MM:SS' -> 'HH:MM'
			const pAttMinutes = hm => { const m = /^(\d{1,2}):(\d{2})/.exec(hm || ''); return m ? (+m[1]) * 60 + (+m[2]) : null; };
			const pAttStateLabel = s => __(String(s || '').toLowerCase().replace(/ & /g, '_and_').replace(/ /g, '_'), s);

			function profileAttendanceFormHtml(v) {
				const F = window.SRForm, t = F.t, esc = F.esc;
				const isEdit = !!v.date;
				let punch = 'both';
				if (v.timeIn && !v.timeOut) punch = 'in';
				else if (!v.timeIn && v.timeOut) punch = 'out';
				const states = P_ATT_STATES.indexOf(v.state) > -1 || !v.state ? P_ATT_STATES : P_ATT_STATES.concat([v.state]);
				const seg = (val, icon, label) => `<label class="sr-seg-opt ${punch === val ? 'checked' : ''}"><input type="radio" name="punch" value="${val}" ${punch === val ? 'checked' : ''}><span><i class="mdi ${icon}"></i> ${esc(label)}</span></label>`;
				return '<form id="pAttForm" class="sr-page sr-form text-left" autocomplete="off" novalidate>' +
					F.section('mdi-account', t('employee', 'Employee'),
						F.field({ col: 8, name: '_emp', label: t('employee', 'Employee'),
							html: `<input type="text" class="form-control" value="${esc(P_ATT_EMP_LABEL)}" readonly>` }) +
						F.field({ col: 4, name: 'date', id: 'pAttDate', label: t('date', 'Date'), req: !isEdit, value: v.date || F.today(), ph: 'YYYY-MM-DD',
							attrs: isEdit ? ' readonly' : '', msg: t('select_date', 'Select the date') })
					) +
					F.section('mdi-clock', t('punches', 'Punches'),
						F.field({ col: 12, name: 'punch', label: t('punch_type', 'Punch type'),
							html: '<div class="sr-seg att-punch">' +
								seg('both', 'mdi-swap-horizontal', t('punch_both', 'Check in & out')) +
								seg('in', 'mdi-login', t('punch_in_only', 'Check in only')) +
								seg('out', 'mdi-logout', t('punch_out_only', 'Check out only')) +
							'</div>' }) +
						F.field({ col: 4, name: 'time_in', id: 'pAttTimeIn', label: t('check_in', 'Check in'), req: true, value: pAttHm(v.timeIn), ph: 'HH:MM',
							msg: t('enter_check_in', 'Enter the check-in time') }).replace('sr-fcol c-4', 'sr-fcol c-4 js-in') +
						F.field({ col: 4, name: 'time_out', id: 'pAttTimeOut', label: t('check_out', 'Check out'), req: true, value: pAttHm(v.timeOut), ph: 'HH:MM',
							msg: t('enter_check_out', 'Enter the check-out time') }).replace('sr-fcol c-4', 'sr-fcol c-4 js-out') +
						F.field({ col: 4, name: '_hours', label: t('hours', 'Hours'), html: '<div class="sr-fstats" style="grid-template-columns:1fr;"><div><span>' + esc(t('worked', 'Worked')) + '</span><b class="js-hours">&ndash;</b></div></div>' })
					) +
					F.section('mdi-information-outline', t('details', 'Details'),
						F.field({ col: 5, name: 'state', label: t('state', 'State'), req: true,
							html: F.select({ name: 'state', id: 'pAttState', req: true, options: states.map(s => ({ v: s, l: pAttStateLabel(s) })) }) }) +
						F.field({ col: 7, name: 'note', id: 'pAttNote', label: t('note', 'Note'), value: v.note || '', ph: t('optional', 'Optional') })
					) +
				'</form>';
			}

			function openProfileAttendanceForm(v) {
				const F = window.SRForm, t = F.t;
				const isEdit = !!v.date;
				F.open({
					title: isEdit ? t('edit_attendance', 'Edit record') : t('add_attendance', 'Add record'),
					html: profileAttendanceFormHtml(v),
					width: '760px',
					confirm: t('save', 'Save'),
					didOpen: function() {
						const $form = $('#pAttForm');
						F.liveClear($form);
						$('#pAttState').val(v.state || 'Present');
						// AppDate calendar for the date (no future days); clock-face pickers (same as Timetables) for the punches
						if (window.AppDate && !isEdit) AppDate.single('#pAttDate', { maxDate: 'today' });
						// Empty Check In / Check Out clocks open on this employee's scheduled times for that day
						F.clockDefault($('#pAttTimeIn'), '08:00');
						F.clockDefault($('#pAttTimeOut'), '17:00');
						F.clock($('#pAttTimeIn, #pAttTimeOut'));
						const loadScheduled = function() {
							const day = isEdit ? v.date : $('#pAttDate').val();
							if (!day) return;
							$.post('./includes/ajaxFile/attendanceAjax.php', { action: 'timetable_for', emp_id: employeeId, date: day }, null, 'json').done(function(r) {
								if (!r || r.status !== 'success') return;
								F.clockDefault($('#pAttTimeIn'), r.check_in);
								F.clockDefault($('#pAttTimeOut'), r.check_out);
							});
						};
						$form.on('change', '#pAttDate', loadScheduled);
						loadScheduled();
						function refresh() {
							const p = $form.find('input[name="punch"]:checked').val();
							$form.find('.js-in').toggle(p !== 'out').find('input').attr('data-required', p !== 'out' ? '1' : null);
							$form.find('.js-out').toggle(p !== 'in').find('input').attr('data-required', p !== 'in' ? '1' : null);
							const a = p === 'out' ? null : pAttMinutes($('#pAttTimeIn').val()), b = p === 'in' ? null : pAttMinutes($('#pAttTimeOut').val());
							let txt = '–';
							if (a !== null && b !== null) { let m = b - a; if (m < 0) m += 1440; txt = Math.floor(m / 60) + 'h ' + ('0' + (m % 60)).slice(-2) + 'm'; }
							$form.find('.js-hours').text(txt);
						}
						$form.on('change', 'input[name="punch"]', refresh);
						$form.on('input change', '#pAttTimeIn, #pAttTimeOut', refresh);
						refresh();
					},
					preConfirm: function() {
						const $form = $('#pAttForm');
						const msg = F.validate($form);
						if (msg) { Swal.showValidationMessage(msg); return false; }
						const p = $form.find('input[name="punch"]:checked').val();
						return $.ajax({
							url: './includes/ajaxFile/attendanceAjax.php', type: 'POST', dataType: 'json',
							data: {
								action: 'add_edit_attendance',
								emp_id: employeeId,
								date: isEdit ? v.date : $('#pAttDate').val(),
								time_in: p === 'out' ? '' : $('#pAttTimeIn').val(),
								time_out: p === 'in' ? '' : $('#pAttTimeOut').val(),
								state: $('#pAttState').val(),
								note: $('#pAttNote').val()
							}
						}).then(function(res) {
							if (!res || res.status !== 'success') throw new Error((res && res.message) || 'Error');
							return res;
						}).catch(function(err) {
							Swal.showValidationMessage((err && err.responseJSON && err.responseJSON.message) || (err && err.message) || t('unexpected_error', 'Unexpected error.'));
						});
					}
				}).then(function(result) {
					if (!(result.isConfirmed && result.value)) return;
					Swal.fire({ title: t('saved', 'Saved'), text: result.value.message, icon: 'success', timer: 1400, showConfirmButton: false, allowOutsideClick: false });
					if (attendanceTable) attendanceTable.ajax.reload(null, false);
				});
			}

			$(document).on('click', '#btn-add-attendance-record', function() {
				openProfileAttendanceForm({});
			});

			$(document).on('click', '.btn-edit-attendance-record', function() {
				const d = $(this).data();
				openProfileAttendanceForm({
					date: d.date, timeIn: d.timeIn || '', timeOut: d.timeOut || '', state: d.state, note: d.note || ''
				});
			});

			function returnVacationRequest(vacationId, returndate, empId, empName) {
				// Changed to use new rejoin approval system
				submitRejoinRequest(vacationId, returndate, empId, empName);
			}

			// Direct Rejoin: mark the employee's active vacation as completed
			// immediately, bypassing the rejoin_requests approval chain entirely.
			// Only shown to users granted the 'direct_rejoin_bypass_approval'
			// special access (or system admins) - see includes/emp_top_info.php.
			function openDirectRejoinModal(empId, vacationId, empName) {
				Swal.fire({
					title: '<?= __("direct_rejoin", "Direct Rejoin") ?>',
					html: `
						<p style="text-align:left;">
							<?= __("direct_rejoin_warning", "This immediately marks the employee's active vacation as completed and clears their away status - without going through the normal rejoin approval chain.") ?>
						</p>
						<p style="text-align:left;"><strong>${empName}</strong> (ID: ${empId})</p>
						<div class="form-group text-left" style="margin-top:15px;">
							<label for="directRejoinDate"><?= __("rejoin_date", "Rejoin Date") ?> <span class="text-danger">*</span></label>
							<input type="text" class="form-control" id="directRejoinDate" placeholder="<?= __("select_date", "Select date...") ?>" readonly>
						</div>
					`,
					icon: 'warning',
					showCancelButton: true,
					confirmButtonText: '<?= __("confirm_rejoin", "Yes, rejoin now") ?>',
					cancelButtonText: '<?= __("cancel", "Cancel") ?>',
					confirmButtonColor: APP_COLORS.warning,
					cancelButtonColor: APP_COLORS.secondary,
					allowOutsideClick: false,
					didOpen: () => {
						AppDate.single('#directRejoinDate', { defaultDate: 'today' });
					},
					preConfirm: () => {
						const rejoinDate = $('#directRejoinDate').val();
						if (!rejoinDate) {
							Swal.showValidationMessage('<?= __("rejoin_date_required", "Please select a rejoin date") ?>');
							return false;
						}
						return rejoinDate;
					}
				}).then((result) => {
					if (!result.isConfirmed) {
						return;
					}
					const rejoinDate = result.value;

					Swal.fire({
						title: '<?= __("processing", "Processing...") ?>',
						allowOutsideClick: false,
						allowEscapeKey: false,
						didOpen: () => {
							Swal.showLoading();
						}
					});

					$.ajax({
						url: './includes/ajaxFile/leaveHandler.php',
						type: 'POST',
						data: {
							ajaxType: 'directRejoinBypass',
							emp_id: empId,
							vacation_id: vacationId,
							rejoin_date: rejoinDate
						},
						dataType: 'JSON',
						success: function(response) {
							Swal.fire({
								icon: response.type === 'success' ? 'success' : 'error',
								title: response.title || response.type,
								text: response.message,
								confirmButtonText: '<?= __("ok", "OK") ?>'
							}).then(() => {
								if (response.type === 'success') {
									location.reload();
								}
							});
						},
						error: function() {
							Swal.fire('<?= __("error", "Error") ?>', '<?= __("request_failed", "Request failed") ?>', 'error');
						}
					});
				});
			}


		</script>


		<script>
			// Load evaluation details when view button is clicked using SweetAlert2
			$(document).on('click', '.view-eval-details', function() {
				var evalId = $(this).data('id');

				// Show loading
				Swal.fire({
					title: __('loading', 'Loading...'),
					text: __('fetching_evaluation_details', 'Fetching evaluation details'),
					allowOutsideClick: false,
					didOpen: () => {
						Swal.showLoading();
					}
				});

				// Fetch evaluation details
				$.ajax({
					url: 'includes/ajaxFile/ajaxEvaluation.php',
					method: 'POST',
					data: {
						action: 'get_evaluation_details',
						evaluation_id: evalId
					},
					dataType: 'json',
					success: function(response) {
						if (response.status === 'success') {
							var eval = response.data;

							// Determine badge color for total score based on rating
							let totalScoreBadge = 'success'; // Default Excellent
							if (eval.total_score < 60) {
								totalScoreBadge = 'danger'; // Needs Improvement
							} else if (eval.total_score < 70) {
								totalScoreBadge = 'warning'; // Satisfactory
							} else if (eval.total_score < 80) {
								totalScoreBadge = 'info'; // Good
							} else if (eval.total_score < 90) {
								totalScoreBadge = 'primary'; // Very Good
							}

							// Function to get badge color for individual scores (out of 10)
							function getScoreBadge(score) {
								if (score >= 9) return 'success'; // 90-100%
								if (score >= 8) return 'primary'; // 80-89%
								if (score >= 7) return 'info'; // 70-79%
								if (score >= 6) return 'warning'; // 60-69%
								return 'danger'; // Below 60%
							}

							var currentLang = getCurrentLanguage();

							// Build detailed HTML
							let detailsHtml = `
								<div class="evaluation-details-print" id="evaluationDetailsPrint" style="text-align: left; padding: 20px;">
									<div style="display: flex; justify-content: space-between; margin-bottom: 30px; padding-bottom: 20px; border-bottom: 2px solid #dee2e6;">
										<div style="flex: 1;">
											<p style="margin-bottom: 10px;"><strong><?= __('employee_name') ?>:</strong> <span class="emp-name">${eval.employee_name}</span></p>
											<p style="margin-bottom: 10px;"><strong><?= __('employee_id') ?>:</strong> ${eval.employee_emp_id}</p>
											<p style="margin-bottom: 10px;"><strong><?= __('department') ?>:</strong> <span class="dept-name">${eval.dept_name}</span></p>
											<p style="margin-bottom: 10px;"><strong><?= __('position') ?>:</strong> <span class="emp-position">${eval.employee_position}</span></p>
										</div>
										<div style="flex: 1; text-align: right;">
											<p style="margin-bottom: 10px;"><strong><?= __('evaluated_by') ?>:</strong> <span class="manager-name">${eval.manager_name}</span></p>
											<p style="margin-bottom: 10px;"><strong><?= __('evaluation_date') ?>:</strong> ${eval.created_at ? eval.created_at.substring(0, 16).replace('T', ' ') : __('not_available', 'N/A')}</p>
											<p style="margin-bottom: 10px;"><strong><?= __('total_score') ?>:</strong> <span class="badge badge-${totalScoreBadge}" style="font-size: 14px; padding: 5px 10px;">${eval.total_score || '0'}/100</span></p>
										</div>
									</div>
									
									<h5 style="margin-top: 20px; margin-bottom: 15px; color: #333;"><?= __('evaluation_criteria') ?></h5>
									<table class="table table-bordered" style="width: 100%; margin-bottom: 20px;">
										<thead style="background-color: #f8f9fa;">
											<tr>
												<th style="padding: 10px; width: 70%;"><?= __('criteria') ?></th>
												<th style="padding: 10px; text-align: center;"><?= __('score') ?></th>
											</tr>
										</thead>
										<tbody>
											<tr>
												<td style="padding: 10px;"><?= __('punctuality_attendance') ?></td>
												<td style="padding: 10px; text-align: center;"><span class="badge badge-${getScoreBadge(eval.punctuality || 0)}">${eval.punctuality || '0'}/10</span></td>
											</tr>
											<tr>
												<td style="padding: 10px;"><?= __('achieving_at_the_specified_time') ?></td>
												<td style="padding: 10px; text-align: center;"><span class="badge badge-${getScoreBadge(eval.achieving_time || 0)}">${eval.achieving_time || '0'}/10</span></td>
											</tr>
											<tr>
												<td style="padding: 10px;"><?= __('knowledge_of_job') ?></td>
												<td style="padding: 10px; text-align: center;"><span class="badge badge-${getScoreBadge(eval.job_knowledge || 0)}">${eval.job_knowledge || '0'}/10</span></td>
											</tr>
											<tr>
												<td style="padding: 10px;"><?= __('the_ability_to_solve_problems') ?></td>
												<td style="padding: 10px; text-align: center;"><span class="badge badge-${getScoreBadge(eval.problem_solving || 0)}">${eval.problem_solving || '0'}/10</span></td>
											</tr>
											<tr>
												<td style="padding: 10px;"><?= __('receptiveness_to_feedback_and_instructions') ?></td>
												<td style="padding: 10px; text-align: center;"><span class="badge badge-${getScoreBadge(eval.feedback_receptiveness || 0)}">${eval.feedback_receptiveness || '0'}/10</span></td>
											</tr>
											<tr>
												<td style="padding: 10px;"><?= __('self_professional_development') ?></td>
												<td style="padding: 10px; text-align: center;"><span class="badge badge-${getScoreBadge(eval.self_development || 0)}">${eval.self_development || '0'}/10</span></td>
											</tr>
											<tr>
												<td style="padding: 10px;"><?= __('work_under_pressure') ?></td>
												<td style="padding: 10px; text-align: center;"><span class="badge badge-${getScoreBadge(eval.work_under_pressure || 0)}">${eval.work_under_pressure || '0'}/10</span></td>
											</tr>
											<tr>
												<td style="padding: 10px;"><?= __('communication_skills_and_teamwork') ?></td>
												<td style="padding: 10px; text-align: center;"><span class="badge badge-${getScoreBadge(eval.communication_teamwork || 0)}">${eval.communication_teamwork || '0'}/10</span></td>
											</tr>
											<tr>
												<td style="padding: 10px;"><?= __('creativity_and_speed_of_response') ?></td>
												<td style="padding: 10px; text-align: center;"><span class="badge badge-${getScoreBadge(eval.creativity_response || 0)}">${eval.creativity_response || '0'}/10</span></td>
											</tr>
											<tr>
												<td style="padding: 10px;"><?= __('initiative_and_cooperation') ?></td>
												<td style="padding: 10px; text-align: center;"><span class="badge badge-${getScoreBadge(eval.initiative_cooperation || 0)}">${eval.initiative_cooperation || '0'}/10</span></td>
											</tr>
										</tbody>
									</table>
									
									${eval.observation 
										? `<h5 style="margin-top: 30px; margin-bottom: 15px; color: #333;"><?= __('observationremarks') ?></h5><p style="padding: 15px; background-color: #f8f9fa; border-radius: 5px; border-left: 4px solid #007bff;">${eval.observation}</p>` 
										: `<h5 style="margin-top: 30px; margin-bottom: 15px; color: #333;"><?= __('observationremarks') ?></h5><p style="padding: 15px; background-color: #f8f9fa; border-radius: 5px;"><?= __('no_observation_provided') ?></p>`}
									
									<div style="margin-top: 30px; padding-top: 20px; border-top: 2px solid #dee2e6;">
										<h5 style="margin-bottom: 15px; color: #333;">
											${eval.manager_acknowledgment_status === 'acknowledged' ? '<?= __('acknowledgment') ?>' : eval.manager_acknowledgment_status === 'objected' ? '<?= __('objection') ?>' : '<?= __('acknowledgment_status') ?>'}
										</h5>
										${eval.manager_acknowledgment_status === 'pending' 
											? `<div class="alert alert-warning" style="border-left: 4px solid #ffc107;"><i class="mdi mdi-clock"></i> <strong><?= __('status') ?>:</strong> <?= __('pending_acknowledgments') ?></div>`
											: eval.manager_acknowledgment_status === 'acknowledged'
												? `<div class="alert alert-success" style="border-left: 4px solid #28a745;">
													<p style="margin-bottom: 5px;"><i class="mdi mdi-check-circle"></i> <strong><?= __('status') ?>:</strong> <?= __('acknowledged') ?></p>
													${eval.acknowledged_by_name ? `<p style="margin-bottom: 5px;"><strong><?= __('acknowledged_by') ?>:</strong> <span class="acknow_by_name">${eval.acknowledged_by_name}</span></p>` : ''}
													${eval.acknowledgment_date ? `<p style="margin-bottom: 0;"><strong><?= __('date') ?>:</strong> ${eval.acknowledgment_date}</p>` : ''}
												</div>`
												: eval.manager_acknowledgment_status === 'objected'
													? `<div class="alert alert-danger" style="border-left: 4px solid #dc3545;">
														<p style="margin-bottom: 10px;"><i class="mdi mdi-close-circle"></i> <strong><?= __('status') ?>:</strong> <?= __('objected') ?></p>
														${eval.manager_objection_note ? `<p style="margin-bottom: 10px;"><strong><?= __('objection_note') ?>:</strong></p><p style="padding: 10px; background-color: #fff; border-radius: 4px; white-space: pre-wrap;">${eval.manager_objection_note}</p>` : ''}
														${eval.acknowledged_by_name ? `<p style="margin-bottom: 5px;"><strong><?= __('objected_by') ?>:</strong> <span class="acknow_by_name">${eval.acknowledged_by_name}</span></p>` : ''}
														${eval.acknowledgment_date ? `<p style="margin-bottom: 0;"><strong><?= __('date') ?>:</strong> ${eval.acknowledgment_date}</p>` : ''}
													</div>`
													: `<div class="alert alert-secondary"><i class="mdi mdi-information-outline"></i> <strong><?= __('status') ?>:</strong> <?= __('unknown') ?></div>`
										}
									</div>
								</div>
							`;

							Swal.fire({
								title: __('evaluation_details'),
								html: detailsHtml,
								width: '900px',
								showCloseButton: true,
								showCancelButton: true,
								confirmButtonText: '<i class="mdi mdi-printer"></i> ' + __('print'),
								confirmButtonColor: APP_COLORS.success,
								cancelButtonText: __('close'),
								customClass: {
									confirmButton: 'btn btn-success',
									cancelButton: 'btn btn-secondary'
								},
								allowOutsideClick: false,
								didOpen: () => {
									// Translate employee name
									if (eval.acknowledged_by_name && currentLang === 'ar') {
										translateName(eval.acknowledged_by_name, 'en', 'ar', function(translated) {
											const empNameEl = document.querySelector('.acknow_by_name');
											if (empNameEl) empNameEl.textContent = translated;
										});
									}
									// Translate employee name
									if (eval.employee_name && currentLang === 'ar') {
										translateName(eval.employee_name, 'en', 'ar', function(translated) {
											const empNameEl = document.querySelector('.emp-name');
											if (empNameEl) empNameEl.textContent = translated;
										});
									}
									// Translate department name
									if (eval.dept_name && currentLang === 'ar') {
										translateName(eval.dept_name, 'en', 'ar', function(translated) {
											const deptNameEl = document.querySelector('.dept-name');
											if (deptNameEl) deptNameEl.textContent = translated;
										});
									}
									// Translate position
									if (eval.employee_position && currentLang === 'ar') {
										translateName(eval.employee_position, 'en', 'ar', function(translated) {
											const empPosEl = document.querySelector('.emp-position');
											if (empPosEl) empPosEl.textContent = translated;
										});
									}
									// Translate manager name
									if (eval.manager_name && currentLang === 'ar') {
										translateName(eval.manager_name, 'en', 'ar', function(translated) {
											const managerNameEl = document.querySelector('.manager-name');
											if (managerNameEl) managerNameEl.textContent = translated;
										});
									}
								}
							}).then((result) => {
								if (result.isConfirmed) {
									// Print the evaluation details
									const printWindow = window.open('', '_blank');
									const printContent = document.getElementById('evaluationDetailsPrint').innerHTML;
									printWindow.document.write('<!DOCTYPE html>' +
										'<html>' +
										'<head>' +
										'<title><?= __('evaluation_details') ?> - ' + eval.employee_name + '</title>' +
										'<link rel="stylesheet" href="assets/css/bootstrap.min.css">' +
										'<style>' +
										'body { margin: 20px; font-family: Arial, sans-serif; }' +
										'.badge { display: inline-block; padding: 5px 10px; border-radius: 3px; font-weight: bold; }' +
										'.badge-primary { background-color: #007bff; color: white; }' +
										'.badge-success { background-color: #28a745; color: white; }' +
										'.badge-info { background-color: #17a2b8; color: white; }' +
										'.badge-warning { background-color: #ffc107; color: #212529; }' +
										'.badge-danger { background-color: #dc3545; color: white; }' +
										'table { width: 100%; border-collapse: collapse; }' +
										'table, th, td { border: 1px solid #dee2e6; }' +
										'th, td { padding: 10px; }' +
										'@media print { body { margin: 15px; } .no-print { display: none; } }' +
										'</style>' +
										'</head>' +
										'<body>' +
										printContent +
										'</body>' +
										'</html>');
									printWindow.document.close();
									setTimeout(function() {
										printWindow.print();
										setTimeout(function() {
											printWindow.close();
										}, 500);
									}, 250);
								}
							});
						} else {
							Swal.fire('<?= __('error') ?>', '<?= __('failed_to_load_evaluation_details', 'Failed to load evaluation details') ?>', 'error');
						}
					},
					error: function() {
						Swal.fire('<?= __('error') ?>', '<?= __('error_loading_evaluation_details', 'An error occurred while loading the evaluation details') ?>', 'error');
					}
				});
			});
		</script>

		<?php if ($show_d365_tab): ?>
		<!-- D365 Tab - loaded on first open (D365 is slow), refresh re-reads D365 -->
		<style>
			#d365tab .sr-card { margin-bottom: 16px; }
			#d365tab .sr-notice { margin-bottom: 12px; }
			.d365tab-bar { display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 8px; margin-bottom: 14px; }
			.d365tab-bar > div { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
			.d365tab-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 16px; }
			.d365tab-muted { color: var(--sr-muted, #64748b); font-size: 12px; }
			/* Refresh / Add buttons look like the active D365 tab */
			.d365tab-btn { display: inline-flex; align-items: center; gap: 8px; white-space: nowrap; padding: 8px 16px; border-radius: 10px; font-weight: 600; font-size: 0.875rem;
				color: var(--sr-accent-strong); background: var(--sr-surface); border: 1px solid var(--sr-border); cursor: pointer;
				box-shadow: 0 1px 2px rgba(15, 23, 42, .06), inset 0 -2px 0 var(--sr-accent); transition: background-color .15s ease, box-shadow .15s ease; }
			.d365tab-btn i { color: var(--sr-accent); font-size: 1rem; line-height: 1; }
			.d365tab-btn:hover { background: var(--sr-surface-2); }
			.d365tab-btn:disabled { opacity: .55; cursor: default; }
			.d365fin-filter .d365tab-btn { padding: 4px 10px; font-size: .78rem; }
			.d365tab-btn.is-active { background: var(--sr-accent); color: #fff; border-color: var(--sr-accent); }
			.d365fin-tiles { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 12px; }
			.d365fin-tile { border: 1px solid var(--sr-border, #e2e8f0); border-radius: 10px; padding: 12px 14px; display: flex; flex-direction: column; gap: 2px; }
			.d365fin-tile span { font-size: 12px; color: var(--sr-muted, #64748b); }
			.d365fin-tile b { font-size: 20px; }
			.d365fin-tile small { font-size: 11px; color: var(--sr-muted, #64748b); }
			.d365fin-tile.is-debit { border-left: 4px solid #dc2626; }
			.d365fin-tile.is-credit { border-left: 4px solid #16a34a; }
			.d365fin-companies { display: flex; flex-wrap: wrap; gap: 6px; margin-top: 12px; }
			.d365fin-debit { color: #dc2626; }
			.d365fin-credit { color: #16a34a; }
		</style>
		<script>
			(function () {
				var $pane = $('#d365tab');
				function loadD365Tab(refresh) {
					var $body = $('#d365TabBody');
					if (refresh) {
						$body.css({ opacity: 0.45, 'pointer-events': 'none' });
						$('#d365TabRefresh').prop('disabled', true).html('<i class="mdi mdi-loading mdi-spin"></i> Loading...');
					}
					$.post('includes/ajaxFile/d365_employee_tab.php', {
						csrf: $pane.data('csrf'),
						emp_id: String($pane.data('emp')),
						refresh: refresh ? 1 : 0
					}).done(function (html) {
						$body.html(html);
						$pane.attr('data-loaded', '1');
						if ($.fn.DataTable && $('#d365FinTable tbody tr').length) {
							var finTable = $('#d365FinTable').DataTable({ order: [[0, 'desc']], pageLength: 25, autoWidth: false });
							// All / Payroll / Other buttons filter the Type column
							$body.find('[data-fin-type]').on('click', function () {
								$body.find('[data-fin-type]').removeClass('is-active');
								$(this).addClass('is-active');
								var type = $(this).attr('data-fin-type');
								finTable.column(1).search(type ? '^' + type + '$' : '', true, false).draw();
							});
						}
					}).fail(function (xhr) {
						$body.html('<div class="sr-notice tone-red">' + <?= json_encode(__('could_not_load_d365_data', 'Could not load D365 data')) ?> + ' (HTTP ' + xhr.status + '). <a href="#" id="d365TabRetry">' + <?= json_encode(__('retry', 'Retry')) ?> + '</a></div>');
					}).always(function () {
						$body.css({ opacity: '', 'pointer-events': '' });
					});
				}
				$('#d365TabLink').on('shown.bs.tab click', function () {
					if ($pane.attr('data-loaded') === '0') {
						$pane.attr('data-loaded', 'loading');
						loadD365Tab(false);
					}
				});
				// view_employee.php?emp_id=X#d365 (links from the D365 pages) opens this tab directly
				if (window.location.hash === '#d365') {
					$(function () { $('#d365TabLink').tab('show'); });
				}
				$(document).on('click', '#d365TabRefresh', function () { loadD365Tab(true); });
				// Sync from the tab = the header widget's Sync flow; after any sync re-read D365 (skip the 10 min cache)
				$(document).on('click', '#d365TabSync', function () { document.dispatchEvent(new CustomEvent('d365:sync-request')); });
				$(document).on('click', '#d365TabRegister', function () { document.dispatchEvent(new CustomEvent('d365:register-request')); });
				document.addEventListener('d365:synced', function () {
					if ($pane.attr('data-loaded') !== '0') loadD365Tab(true);
				});
				$(document).on('click', '#d365TabRetry', function (e) { e.preventDefault(); loadD365Tab(true); });
			})();
		</script>
		<?php endif; ?>

		<!-- Direct Reports Tab - AJAX pagination (no URL reload) -->
		<script>
			function loadDirectReports(page, search) {
				const $pane = $('#directreports');
				const supervisorEmpId = $pane.data('supervisor-emp-id');
				const $cards = $('#directReportsCards');
				const $pagination = $('#directReportsPagination');
				if (typeof search === 'undefined') {
					search = $('#directReportsSearch').val() || '';
				}

				// Lock current height & dim instead of collapsing to a tiny "Loading..." block -
				// swapping to a short placeholder shrinks the page and makes the browser clamp
				// the scroll position, which reads as an unwanted "jump to top".
				const lockedHeight = $cards.outerHeight();
				if (lockedHeight) {
					$cards.css('min-height', lockedHeight + 'px');
				}
				$cards.css({
					opacity: 0.45,
					'pointer-events': 'none'
				});

				$.ajax({
					url: './includes/ajaxFile/get_direct_reports.php',
					type: 'POST',
					dataType: 'json',
					data: {
						supervisor_emp_id: supervisorEmpId,
						page: page,
						search: search
					}
				}).done(function(response) {
					if (!response || response.status !== 200) {
						$cards.html('<div class="alert alert-danger">' + (__('error_loading_data', 'Error loading data.')) + '</div>');
						return;
					}

					if (!response.total_items) {
						$cards.html('<div class="alert alert-info">' + (__('no_direct_reports_found', 'No employees report directly to this employee.')) + '</div>');
						$pagination.empty();
						return;
					}

					$cards.html('<div class="row">' + response.html + '</div>');
					renderDirectReportsPagination(response.current_page, response.total_pages);
				}).fail(function() {
					$cards.html('<div class="alert alert-danger">' + (__('error_loading_data', 'Error loading data.')) + '</div>');
				}).always(function() {
					$cards.css({
						opacity: 1,
						'pointer-events': 'auto',
						'min-height': ''
					});
				});
			}

			function renderDirectReportsPagination(currentPage, totalPages) {
				const $pagination = $('#directReportsPagination');
				$pagination.empty();

				if (totalPages <= 1) {
					return;
				}

				const $nav = $('<nav aria-label="Direct reports pagination"></nav>');
				const $ul = $('<ul class="pagination mb-0"></ul>');

				const $prev = $('<li class="page-item"></li>').toggleClass('disabled', currentPage <= 1);
				$prev.append($('<a class="page-link" href="javascript:void(0);"></a>').text(__('previous', 'Previous')).on('click', function(e) {
					e.preventDefault();
					if (currentPage > 1) loadDirectReports(currentPage - 1);
				}));
				$ul.append($prev);

				for (let i = 1; i <= totalPages; i++) {
					const $li = $('<li class="page-item"></li>').toggleClass('active', i === currentPage);
					$li.append($('<a class="page-link" href="javascript:void(0);"></a>').text(i).on('click', function(e) {
						e.preventDefault();
						if (i !== currentPage) loadDirectReports(i);
					}));
					$ul.append($li);
				}

				const $next = $('<li class="page-item"></li>').toggleClass('disabled', currentPage >= totalPages);
				$next.append($('<a class="page-link" href="javascript:void(0);"></a>').text(__('next', 'Next')).on('click', function(e) {
					e.preventDefault();
					if (currentPage < totalPages) loadDirectReports(currentPage + 1);
				}));
				$ul.append($next);

				$nav.append($ul);
				$pagination.append($nav);
			}

			let directReportsSearchTimer = null;
			$(document).ready(function() {
				$('#directReportsTabLink').on('shown.bs.tab', function() {
					const $pane = $('#directreports');
					if ($pane.data('loaded') != 1) {
						$pane.data('loaded', 1);
						loadDirectReports(1);
					}
				});

				$('#directReportsSearch').on('input', function() {
					const search = $(this).val();
					clearTimeout(directReportsSearchTimer);
					directReportsSearchTimer = setTimeout(function() {
						loadDirectReports(1, search);
					}, 350);
				});

				$('#directReportsSearchClear').on('click', function() {
					$('#directReportsSearch').val('').trigger('focus');
					clearTimeout(directReportsSearchTimer);
					loadDirectReports(1, '');
				});
			});
		</script>

		<!-- Manual Loan Payment Modal Script -->
		<script>
			$(document).on('click', '.addManualPayment', function() {
				const loanId = $(this).data('loan-id');
				const empId = $(this).data('emp-id');
				const remainingBalance = parseFloat($(this).data('remaining'));

				Swal.fire({
					title: '<i class="fa fa-money"></i> ' + __('add_manual_payment'),
					html: `
					<div class="text-left">
						<div class="form-group">
							<label for="payment_date" class="font-weight-bold">${__('payment_date')} <span class="text-danger">*</span></label>
							<input type="date" id="payment_date" class="form-control swal2-input" value="${new Date().toISOString().split('T')[0]}" required>
						</div>
						<div class="form-group">
							<label for="payment_amount" class="font-weight-bold">${__('payment_amount')} (${__('sar', 'SAR')}) <span class="text-danger">*</span></label>
							<input type="number" id="payment_amount" class="form-control swal2-input" step="0.01" min="0.01" max="${remainingBalance.toFixed(2)}" placeholder="${__('max')}: ${remainingBalance.toFixed(2)}" required>
							<small class="text-muted">${__('remaining_balance')}: <i class="icon-saudi_riyal"></i> ${remainingBalance.toFixed(2)}</small>
						</div>
						<div class="form-group">
							<label for="receipt_id" class="font-weight-bold">${__('receipt_number')}</label>
							<input type="text" id="receipt_id" class="form-control swal2-input" placeholder="${__('optional')}">
						</div>
						<div class="form-group">
							<label for="payment_proof" class="font-weight-bold">${__('payment_proof')} <span class="text-danger">*</span></label>
							<input type="file" id="payment_proof" class="form-control-file swal2-file" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx" required>
							<small class="text-muted">${__('allowed_formats')}: PDF, JPG, PNG, DOC, DOCX (Max 10MB)</small>
						</div>
						<div class="form-group">
							<label for="payment_note" class="font-weight-bold">${__('note')}</label>
							<textarea id="payment_note" class="form-control swal2-textarea" rows="2" placeholder="${__('optional')}"></textarea>
						</div>
					</div>
				`,
					width: '600px',
					showCancelButton: true,
					confirmButtonText: '<i class="fa fa-check"></i> ' + __('submit_payment'),
					cancelButtonText: '<i class="fa fa-times"></i> ' + __('cancel'),
					confirmButtonColor: APP_COLORS.success,
					cancelButtonColor: APP_COLORS.danger,
					showLoaderOnConfirm: true,
					preConfirm: () => {
						const payment_date = $('#payment_date').val();
						const payment_amount = parseFloat($('#payment_amount').val());
						const receipt_id = $('#receipt_id').val();
						const payment_proof = $('#payment_proof')[0].files[0];
						const payment_note = $('#payment_note').val();

						// Validation
						if (!payment_date) {
							Swal.showValidationMessage(__('payment_date_required'));
							return false;
						}
						if (!payment_amount || payment_amount <= 0) {
							Swal.showValidationMessage(__('invalid_payment_amount'));
							return false;
						}
						if (payment_amount > remainingBalance) {
							Swal.showValidationMessage(__('amount_exceeds_balance'));
							return false;
						}
						if (!payment_proof) {
							Swal.showValidationMessage(__('payment_proof_required'));
							return false;
						}

						// File validation
						const allowedTypes = ['application/pdf', 'image/jpeg', 'image/jpg', 'image/png',
							'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
						];
						if (!allowedTypes.includes(payment_proof.type)) {
							Swal.showValidationMessage(__('invalid_file_type'));
							return false;
						}
						if (payment_proof.size > 10 * 1024 * 1024) { // 10MB
							Swal.showValidationMessage(__('file_too_large'));
							return false;
						}

						// Prepare FormData
						const formData = new FormData();
						formData.append('ajaxType', 'add_manual_payment');
						formData.append('loan_id', loanId);
						formData.append('emp_id', empId);
						formData.append('payment_date', payment_date);
						formData.append('payment_amount', payment_amount);
						formData.append('receipt_id', receipt_id);
						formData.append('payment_proof', payment_proof);
						formData.append('payment_note', payment_note);

						// Debug logging
						// console.log('=== FormData Debug ===');
						// console.log('loanId:', loanId);
						// console.log('empId:', empId);
						// console.log('payment_proof file:', payment_proof);
						for (let pair of formData.entries()) {
							// console.log(pair[0] + ':', pair[1]);
						}

						// Submit via AJAX
						return $.ajax({
								url: './includes/ajaxFile/ajaxLoan.php',
								type: 'POST',
								data: formData,
								processData: false,
								contentType: false,
								dataType: 'json'
							})
							.then(response => {
								if (response.type !== 'success') {
									throw new Error(response.message || __('payment_failed'));
								}
								return response;
							})
							.catch(error => {
								Swal.showValidationMessage(`${__('request_failed')}: ${error.message || error}`);
							});
					},
					allowOutsideClick: () => !Swal.isLoading()
				}).then((result) => {
					if (result.isConfirmed && result.value) {
						Swal.fire({
							title: result.value.title || __('success'),
							text: result.value.message,
							icon: 'success',
							allowOutsideClick: false
						}).then(() => {
							location.reload();
						});
					}
				});
			});

			// Additional Information edit modal. Date field opens its own SweetAlert2 popup with an INLINE calendar (not a
			// dropdown) - a dropdown calendar gets visually clipped by the parent Swal's
			// own scroll container. Selecting a date closes the picker and reopens the
			// main form pre-filled with everything the user had already entered, plus the
			// newly picked date.
			function openAdditionalInfoModal(empId, prefill) {
				Swal.fire({
					title: '<?= __('edit_additional_information', 'Edit Additional Information') ?>',
					width: '750px',
					html: `
					<div class="row text-left">
						<div class="form-group col-md-6">
							<label><?= __('salary_grade', 'Salary Grade') ?></label>
							<select id="aiSalaryGrade" class="form-control">
								<option value=""><?= __('not_available') ?></option>
								${Array.from({length: 12}, (_, i) => i + 1).map(g => `<option value="${g}"><?= __('grade', 'Grade') ?> ${g}</option>`).join('')}
							</select>
						</div>
						<div class="form-group col-md-6">
							<label><?= __('dependants_count', 'No. of Dependants') ?></label>
							<input type="number" id="aiDependantsCount" class="form-control" min="0" step="1" placeholder="0">
						</div>
						<div class="form-group col-md-6">
							<label><?= __('ticket_fare', 'Ticket') ?> (<?= __('sar', 'SAR') ?>)</label>
							<input type="number" id="aiTicketFare" class="form-control" min="0" step="0.01" placeholder="0.00">
						</div>
						<?php if ((int)($emprow['country'] ?? 0) !== 191): ?>
						<div class="form-group col-md-6">
							<label><?= __('labour_office_expense', 'Labour Office Expenses') ?> (<?= __('sar', 'SAR') ?>)</label>
							<input type="number" id="aiLabourOfficeExpense" class="form-control" min="0" step="0.01" placeholder="0.00">
						</div>
						<div class="form-group col-md-6">
							<label><?= __('iqama_renewal_fee', 'Iqama Renewal Fee') ?> (<?= __('sar', 'SAR') ?>)</label>
							<input type="number" id="aiIqamaRenewalFee" class="form-control" min="0" step="0.01" placeholder="0.00">
						</div>
						<div class="form-group col-md-6">
							<label><?= __('citizen_local_relation', 'Citizen (Local)') ?></label>
							<select id="aiCitizenLocalRelation" class="form-control">
								<option value=""><?= __('not_available') ?></option>
								<option value="Son"><?= __('relation_son', 'Son') ?></option>
								<option value="Daughter"><?= __('relation_daughter', 'Daughter') ?></option>
								<option value="Wife"><?= __('relation_wife', 'Wife') ?></option>
								<option value="Husband"><?= __('relation_husband', 'Husband') ?></option>
							</select>
						</div>
						<?php endif; ?>
						<div class="form-group col-md-6">
							<label><?= __('eng_council_fee', 'Saudi Engineering Council Fee') ?> (<?= __('sar', 'SAR') ?>)</label>
							<input type="number" id="aiEngCouncilFee" class="form-control" min="0" step="0.01" placeholder="0.00">
						</div>
						<div class="form-group col-md-6">
							<label><?= __('eng_council_expiry', 'Saudi Engineering Council Expiry') ?></label>
							<input type="text" id="aiEngCouncilExpiry" class="form-control" placeholder="YYYY-MM-DD" autocomplete="off" readonly style="cursor: pointer; background-color: #fff;">
						</div>
					</div>
					`,
					showCancelButton: true,
					confirmButtonText: '<?= __('save', 'Save') ?>',
					cancelButtonText: '<?= __('cancel') ?>',
					allowOutsideClick: false,
					didOpen: () => {
						function fillForm(d) {
							$('#aiSalaryGrade').val(d.salary_grade || '');
							$('#aiDependantsCount').val(d.dependants_count ?? '');
							$('#aiTicketFare').val(d.ticket_fare ?? '');
							$('#aiLabourOfficeExpense').val(d.labour_office_expense ?? '');
							$('#aiIqamaRenewalFee').val(d.iqama_renewal_fee ?? '');
							$('#aiCitizenLocalRelation').val(d.citizen_local_relation || '');
							$('#aiEngCouncilFee').val(d.eng_council_fee ?? '');
							const expiryVal = (d.eng_council_expiry && d.eng_council_expiry !== '0000-00-00') ? d.eng_council_expiry : '';
							$('#aiEngCouncilExpiry').val(expiryVal);
						}

						if (prefill) {
							fillForm(prefill);
						} else {
							$.ajax({
								url: './includes/ajaxFile/employeeAdditionalInfoHandler.php',
								type: 'POST',
								dataType: 'json',
								data: { ajaxType: 'get_employee_additional_info', emp_id: empId }
							}).done(function(resp) {
								if (resp.status === 200 && resp.data) {
									fillForm(resp.data);
								}
							});
						}

						// Opens the date picker in its own SweetAlert2 popup (see openEngCouncilExpiryPicker
						// below) so the calendar isn't clipped by this modal's scroll container.
						$('#aiEngCouncilExpiry').on('click', function() {
							const snapshot = {
								salary_grade: $('#aiSalaryGrade').val(),
								dependants_count: $('#aiDependantsCount').val(),
								ticket_fare: $('#aiTicketFare').val(),
								labour_office_expense: $('#aiLabourOfficeExpense').val(),
								iqama_renewal_fee: $('#aiIqamaRenewalFee').val(),
								citizen_local_relation: $('#aiCitizenLocalRelation').val(),
								eng_council_fee: $('#aiEngCouncilFee').val(),
								eng_council_expiry: $('#aiEngCouncilExpiry').val()
							};
							openEngCouncilExpiryPicker(empId, snapshot);
						});
					},
					preConfirm: () => {
						return {
							salary_grade: $('#aiSalaryGrade').val(),
							dependants_count: $('#aiDependantsCount').val(),
							ticket_fare: $('#aiTicketFare').val(),
							labour_office_expense: $('#aiLabourOfficeExpense').val(),
							iqama_renewal_fee: $('#aiIqamaRenewalFee').val(),
							citizen_local_relation: $('#aiCitizenLocalRelation').val(),
							eng_council_fee: $('#aiEngCouncilFee').val(),
							eng_council_expiry: $('#aiEngCouncilExpiry').val()
						};
					}
				}).then((result) => {
					if (result.isConfirmed) {
						$.ajax({
							url: './includes/ajaxFile/employeeAdditionalInfoHandler.php',
							type: 'POST',
							dataType: 'json',
							data: Object.assign({ ajaxType: 'save_employee_additional_info', emp_id: empId }, result.value),
							success: function(resp) {
								if (resp.status === 200) {
									Swal.fire({
										icon: 'success',
										title: '<?= __('success') ?>',
										text: resp.message || '<?= __('update_successful', 'Updated successfully') ?>'
									}).then(() => {
										location.reload();
									});
								} else {
									Swal.fire({
										icon: 'error',
										title: '<?= __('error') ?>',
										text: resp.message || '<?= __('update_failed') ?>'
									});
								}
							},
							error: function() {
								Swal.fire({
									icon: 'error',
									title: '<?= __('error') ?>',
									text: '<?= __('an_error_occurred') ?>'
								});
							}
						});
					}
				});
			}

			// Standalone picker popup for Saudi Engineering Council Expiry - uses an INLINE
			// calendar (AppDate.inline) instead of a dropdown, since a dropdown calendar gets
			// clipped by the parent Swal's own scroll container.
			function openEngCouncilExpiryPicker(empId, snapshot) {
				Swal.fire({
					title: '<?= __('select_expiry_date_placeholder') ?>',
					html: `<input type="hidden" id="aiEngCouncilExpiryInline">`,
					width: '360px',
					showCancelButton: true,
					confirmButtonText: '<?= __('select', 'Select') ?>',
					cancelButtonText: '<?= __('cancel') ?>',
					allowOutsideClick: false,
					didOpen: () => {
						AppDate.inline('#aiEngCouncilExpiryInline', {
							defaultDate: snapshot.eng_council_expiry || null
						});
					},
					preConfirm: () => {
						return $('#aiEngCouncilExpiryInline').val() || '';
					}
				}).then((result) => {
					if (result.isConfirmed) {
						snapshot.eng_council_expiry = result.value;
					}
					// Reopen the main form either way, pre-filled with everything the user had
					// already entered (cancelling the picker must not lose unsaved edits).
					openAdditionalInfoModal(empId, snapshot);
				});
			}

			// Handle Edit Additional Information
			$(document).on('click', '.editAdditionalInfoBtn', function(e) {
				e.preventDefault();
				const empId = $(this).data('emp-id');
				openAdditionalInfoModal(empId, null);
			});

			// Document expiry alerts (get_document_expiry_alerts) - dismiss one row; the card
			// disappears with its last row.
			$(document).on('click', '.sr-doc-alert-close', function() {
				const $row = $(this).closest('.sr-doc-alert');
				const $box = $row.closest('.sr-doc-alerts');
				$row.css('opacity', 0);
				setTimeout(function() {
					$row.remove();
					const left = $box.find('.sr-doc-alert').length;
					if (!left) $box.remove();
					else $box.find('.sr-doc-alerts-head .sr-count').text(left);
				}, 200);
			});

			// "Update Expiry" on a document alert: pick the new date and save it right away.
			// Iqama/Passport -> employeeDocExpiryHandler.php; Medical Insurance -> adds the new
			// year through employeeMedicalInsuranceHandler.php, carrying over the current
			// insurance no / amount / class so only the expiry changes.
			// Hijri-based documents (button carries data-hijri, e.g. ID/Iqama) are picked on the
			// Hijri (Umm al-Qura) calendar first; the Gregorian date is converted automatically.
			$(document).on('click', '.js-doc-expiry-update', function() {
				const $btn = $(this);
				const doc = String($btn.data('doc'));
				const hasHijri = $btn.is('[data-hijri]');
				const empId = <?= json_encode((string)$emprow['empid']) ?>;
				const esc = (v) => $('<div>').text(v == null ? '' : String(v)).html();
				const current = String($btn.data('expiry') || '');
				const currentHijri = String($btn.data('hijri') || '');
				const quick = [1, 2, 5].map(y => `<button type="button" class="sr-btn sr-btn-sm js-doc-quick" data-years="${y}">+${y} ${y > 1 ? '<?= __('years', 'Years') ?>' : '<?= __('year', 'Year') ?>'}</button>`).join(' ');

				Swal.fire({
					title: '<?= __('update_expiry', 'Update Expiry') ?> - ' + esc($btn.data('label')),
					width: '520px',
					html: `
					<form class="sr-form" onsubmit="return false;">
						<div class="sr-notice tone-slate is-compact">
							<i class="mdi mdi-calendar-blank"></i>
							<div><?= __('current_expiry', 'Current expiry') ?>: <strong>${esc(current)}</strong>${hasHijri && currentHijri ? ` <span class="text-muted">(${esc(currentHijri)} <?= __('hijri', 'Hijri') ?>)</span>` : ''}</div>
						</div>
						<div class="sr-fsec">
							<div class="sr-fsec-head"><span><i class="mdi mdi-calendar-clock"></i> <?= __('new_expiry_date', 'New Expiry Date') ?></span></div>
							<div class="sr-fgrid">
								${hasHijri ? `
								<div class="sr-fcol c-6">
									<label for="docExpH"><?= __('hijri', 'Hijri') ?> <span class="text-danger">*</span></label>
									<input type="text" id="docExpH" class="form-control" placeholder="YYYY-MM-DD" autocomplete="off">
									<small class="sr-doc-cal-hint"><?= __('select_hijri_date', 'Select the Hijri date from the document') ?></small>
								</div>` : ''}
								<div class="sr-fcol c-${hasHijri ? 6 : 12}">
									<label for="docExpG"><?= __('gregorian', 'Gregorian') ?>${hasHijri ? '' : ' <span class="text-danger">*</span>'}</label>
									<input type="text" id="docExpG" class="form-control" placeholder="YYYY-MM-DD" autocomplete="off">
									${hasHijri ? `<small class="sr-doc-cal-hint"><?= __('converted_from_hijri', 'Converted automatically from Hijri') ?></small>` : ''}
								</div>
								<div class="sr-fcol c-12">${quick}</div>
							</div>
						</div>
					</form>`,
					showCancelButton: true,
					confirmButtonText: '<i class="mdi mdi-content-save"></i> <?= __('save', 'Save') ?>',
					cancelButtonText: '<?= __('cancel', 'Cancel') ?>',
					confirmButtonColor: window.APP_COLORS && APP_COLORS.primary,
					cancelButtonColor: window.APP_COLORS && APP_COLORS.danger_dark,
					showLoaderOnConfirm: true,
					allowOutsideClick: false,
					customClass: { popup: 'sr-addline-popup sr-page' },
					didOpen: () => {
						const pickers = hasHijri
							? AppDate.hijriPair('#docExpG', '#docExpH', { minDate: 'today' })
							: { gregorian: AppDate.single('#docExpG', { minDate: 'today' }) };
						// +N years from the current expiry (or from today when it is already past).
						// Hijri documents add Hijri years, so the renewal lands on the same Hijri day.
						$(Swal.getPopup()).on('click', '.js-doc-quick', function() {
							const years = Number($(this).data('years'));
							const today = new Date(); today.setHours(0, 0, 0, 0);
							const base = current ? new Date(current + 'T00:00:00') : new Date(NaN);
							const fromToday = isNaN(base) || base < today;
							if (hasHijri) {
								const h = (!fromToday && /^\d{4}-\d{2}-\d{2}$/.test(currentHijri)) ? currentHijri : AppDate.toHijri(today);
								const p = h.split('-').map(Number);
								const mm = String(p[1]).padStart(2, '0');
								// day 30 may not exist in the target Hijri month -> fall back to 29
								let g = AppDate.fromHijri((p[0] + years) + '-' + mm + '-' + String(p[2]).padStart(2, '0'));
								if (!g && p[2] > 29) g = AppDate.fromHijri((p[0] + years) + '-' + mm + '-29');
								if (g) pickers.gregorian.setDate(g, true);
								return;
							}
							const d = fromToday ? today : base;
							d.setFullYear(d.getFullYear() + years);
							pickers.gregorian.setDate(d, true);
						});
					},
					preConfirm: () => {
						const h = hasHijri ? ($('#docExpH').val() || '') : '';
						let g = $('#docExpG').val();
						// Hijri picked but Gregorian not filled yet -> convert it here
						if (hasHijri && h && !/^\d{4}-\d{2}-\d{2}$/.test(g)) g = AppDate.fromHijri(h);
						if (!/^\d{4}-\d{2}-\d{2}$/.test(g)) {
							$(hasHijri ? '#docExpH' : '#docExpG').addClass('is-invalid');
							Swal.showValidationMessage('<?= __('select_date', 'Please select a date') ?>');
							return false;
						}
						const req = doc === 'insurance'
							? {
								url: './includes/ajaxFile/employeeMedicalInsuranceHandler.php',
								data: {
									ajaxType: 'add_employee_medical_insurance', emp_id: empId,
									insurance_no: String($btn.data('insurance-no') || ''),
									med_insurance: String($btn.data('amount') || ''),
									medical_class: String($btn.data('class') || ''),
									medical_expiry: g
								}
							}
							: {
								url: './includes/ajaxFile/employeeDocExpiryHandler.php',
								data: { ajaxType: 'update_doc_expiry', emp_id: empId, doc: doc, expiry_g: g, expiry_h: h }
							};
						return $.ajax({ url: req.url, type: 'POST', dataType: 'json', data: req.data })
							.then(function(resp) {
								if (resp && (resp.type === 'success' || resp.status === 200)) return resp;
								throw new Error((resp && resp.message) || '<?= __('update_failed', 'Update failed') ?>');
							})
							.catch(function(err) {
								Swal.showValidationMessage((err && err.message) || '<?= __('an_error_occurred', 'An error occurred') ?>');
							});
					}
				}).then((result) => {
					if (!result.isConfirmed || !result.value) return;
					Swal.fire({
						icon: 'success',
						title: '<?= __('success', 'Success') ?>',
						text: result.value.message || '<?= __('update_successful', 'Updated successfully') ?>',
						allowOutsideClick: false
					}).then(() => location.reload());
				});
			});

			// Handle Add Medical Insurance (yearly renewal - always adds a new row,
			// never overwrites; the backend flips the previous active row to 'expired')
			$(document).on('click', '.addMedicalInsuranceBtn', function(e) {
				e.preventDefault();
				const empId = $(this).data('emp-id');

				Swal.fire({
					title: '<?= __('add_insurance', 'Add New Year') ?>',
					width: '450px',
					html: `
					<div class="text-left">
						<div class="form-group">
							<label><?= __('insurance_no', 'Insurance No') ?></label>
							<input type="text" id="miInsuranceNo" class="form-control" placeholder="<?= __('insurance_no', 'Insurance No') ?>">
						</div>
						<div class="form-group">
							<label><?= __('med_insurance', 'Medical Insurance') ?> (<?= __('sar', 'SAR') ?>)</label>
							<input type="number" id="miAmount" class="form-control" min="0" step="0.01" placeholder="0.00">
						</div>
						<div class="form-group">
							<label><?= __('medical_expiry', 'Medical Expiry') ?></label>
							<input type="text" id="miExpiry" class="form-control" placeholder="YYYY-MM-DD" autocomplete="off" readonly>
						</div>
						<div class="form-group">
							<label><?= __('medical_class', 'Medical Class') ?></label>
							<select id="miMedicalClass" class="form-control">
								<option value=""><?= __('not_available') ?></option>
								${['CLT','C','B','A','A+'].map(c => `<option value="${c}">${c}</option>`).join('')}
							</select>
						</div>
					</div>
					`,
					showCancelButton: true,
					confirmButtonText: '<?= __('save', 'Save') ?>',
					cancelButtonText: '<?= __('cancel') ?>',
					allowOutsideClick: false,
					didOpen: () => {
						AppDate.single('#miExpiry');
					},
					preConfirm: () => {
						const insuranceNo = $('#miInsuranceNo').val().trim();
						const amount = $('#miAmount').val();
						const expiry = $('#miExpiry').val();
						const medicalClass = $('#miMedicalClass').val();
						if (!insuranceNo && !amount && !expiry && !medicalClass) {
							Swal.showValidationMessage('<?= __('enter_at_least_one_field', 'Enter at least one field') ?>');
							return false;
						}
						return { insurance_no: insuranceNo, med_insurance: amount, medical_expiry: expiry, medical_class: medicalClass };
					}
				}).then((result) => {
					if (result.isConfirmed) {
						$.ajax({
							url: './includes/ajaxFile/employeeMedicalInsuranceHandler.php',
							type: 'POST',
							dataType: 'json',
							data: Object.assign({ ajaxType: 'add_employee_medical_insurance', emp_id: empId }, result.value),
							success: function(resp) {
								if (resp.status === 200) {
									Swal.fire({
										icon: 'success',
										title: '<?= __('success') ?>',
										text: resp.message || '<?= __('update_successful', 'Updated successfully') ?>'
									}).then(() => {
										location.reload();
									});
								} else {
									Swal.fire({
										icon: 'error',
										title: '<?= __('error') ?>',
										text: resp.message || '<?= __('update_failed') ?>'
									});
								}
							},
							error: function() {
								Swal.fire({
									icon: 'error',
									title: '<?= __('error') ?>',
									text: '<?= __('an_error_occurred') ?>'
								});
							}
						});
					}
				});
			});

			// Add Other Income (scheduled recurring income, e.g. a 3-month Bonus). Start/End
			// Month each reuse the shared SweetAlert2 single-date picker (newEmpDatePickerModal,
			// defined globally in jquery.app.js) instead of a plain <input type="month"> - pick
			// Start Month, its popup closes, then pick End Month separately. Only the YYYY-MM
			// part of each picked date is kept, and the picked range's month count is shown
			// live under the fields.
			function otherIncomeMonthCount(startMonth, endMonth) {
				if (!startMonth || !endMonth) return null;
				const parts1 = startMonth.split('-').map(Number);
				const parts2 = endMonth.split('-').map(Number);
				return (parts2[0] - parts1[0]) * 12 + (parts2[1] - parts1[1]) + 1;
			}

			function renderOtherIncomeDuration(startMonth, endMonth) {
				const count = otherIncomeMonthCount(startMonth, endMonth);
				if (count === null) {
					$('#oiDuration').text('');
				} else if (count < 1) {
					$('#oiDuration').text('<?= __('end_month', 'End Month') ?> < <?= __('start_month', 'Start Month') ?>');
				} else {
					$('#oiDuration').text('<?= __('duration', 'Duration') ?>: ' + count + ' ' + (count > 1 ? '<?= __('months') ?>' : '<?= __('month') ?>'));
				}
			}

			// Opens the shared SweetAlert2 single-date picker (newEmpDatePickerModal, global
			// in jquery.app.js) for one field at a time - pick Start Month, its popup closes,
			// then separately open it again for End Month. Reopens this modal pre-filled with
			// the result either way - same "picker returns to the parent modal" pattern as
			// openEngCouncilExpiryPicker above.
			function openOtherIncomeDatePicker(empId, snapshot, field) {
				const currentValue = snapshot[field] ? snapshot[field] + '-01' : null;
				newEmpDatePickerModal({ value: currentValue }).then(function(result) {
					if (result.isConfirmed) {
						snapshot[field] = result.value.gregorian.substring(0, 7);
					}
					openAddOtherIncomeModal(empId, snapshot);
				});
			}

			function openAddOtherIncomeModal(empId, prefill) {
				Swal.fire({
					title: '<?= __('add_other_income', 'Add Other Income') ?>',
					width: '450px',
					html: `
					<div class="text-left">
						<div class="form-group">
							<label><?= __('other_income_title', 'Title') ?></label>
							<input type="text" id="oiTitle" class="form-control" placeholder="<?= __('other_income_title_placeholder', 'e.g. Bonus') ?>">
						</div>
						<div class="form-group">
							<label><?= __('amount', 'Amount') ?> (<?= __('sar', 'SAR') ?>)</label>
							<input type="number" id="oiAmount" class="form-control" min="0" step="0.01" placeholder="0.00">
						</div>
						<div class="form-group">
							<label><?= __('start_month', 'Start Month') ?></label>
							<input type="text" id="oiStartMonth" class="form-control" placeholder="YYYY-MM" autocomplete="off" readonly style="cursor: pointer; background-color: #fff;">
						</div>
						<div class="form-group">
							<label><?= __('end_month', 'End Month') ?></label>
							<input type="text" id="oiEndMonth" class="form-control" placeholder="YYYY-MM" autocomplete="off" readonly style="cursor: pointer; background-color: #fff;">
						</div>
						<div id="oiDuration" class="text-muted small mb-2"></div>
					</div>
					`,
					showCancelButton: true,
					confirmButtonText: '<?= __('save', 'Save') ?>',
					cancelButtonText: '<?= __('cancel') ?>',
					allowOutsideClick: false,
					didOpen: () => {
						function fillForm(d) {
							$('#oiTitle').val(d.title || '');
							$('#oiAmount').val(d.amount || '');
							$('#oiStartMonth').val(d.start_month || '');
							$('#oiEndMonth').val(d.end_month || '');
							renderOtherIncomeDuration(d.start_month, d.end_month);
						}

						if (prefill) {
							fillForm(prefill);
						}

						$('#oiStartMonth').on('click', function() {
							const snapshot = {
								title: $('#oiTitle').val(),
								amount: $('#oiAmount').val(),
								start_month: $('#oiStartMonth').val(),
								end_month: $('#oiEndMonth').val()
							};
							openOtherIncomeDatePicker(empId, snapshot, 'start_month');
						});

						$('#oiEndMonth').on('click', function() {
							const snapshot = {
								title: $('#oiTitle').val(),
								amount: $('#oiAmount').val(),
								start_month: $('#oiStartMonth').val(),
								end_month: $('#oiEndMonth').val()
							};
							openOtherIncomeDatePicker(empId, snapshot, 'end_month');
						});
					},
					preConfirm: () => {
						const title = $('#oiTitle').val().trim();
						const amount = $('#oiAmount').val();
						const startMonth = $('#oiStartMonth').val();
						const endMonth = $('#oiEndMonth').val();
						if (!title || !amount || parseFloat(amount) <= 0) {
							Swal.showValidationMessage('<?= __('other_income_title', 'Title') ?> / <?= __('amount', 'Amount') ?>');
							return false;
						}
						if (!startMonth || !endMonth) {
							Swal.showValidationMessage('<?= __('start_month', 'Start Month') ?> / <?= __('end_month', 'End Month') ?>');
							return false;
						}
						if (endMonth < startMonth) {
							Swal.showValidationMessage('<?= __('end_month', 'End Month') ?>');
							return false;
						}
						return { title: title, amount: amount, start_month: startMonth, end_month: endMonth };
					}
				}).then((result) => {
					if (result.isConfirmed) {
						$.ajax({
							url: './includes/ajaxFile/employeeOtherIncomeHandler.php',
							type: 'POST',
							dataType: 'json',
							data: Object.assign({ ajaxType: 'add_employee_other_income', emp_id: empId }, result.value),
							success: function(resp) {
								if (resp.status === 200) {
									Swal.fire({
										icon: 'success',
										title: '<?= __('success') ?>',
										text: resp.message || '<?= __('update_successful', 'Updated successfully') ?>'
									}).then(() => {
										location.reload();
									});
								} else {
									Swal.fire({
										icon: 'error',
										title: '<?= __('error') ?>',
										text: resp.message || '<?= __('update_failed') ?>'
									});
								}
							},
							error: function() {
								Swal.fire({
									icon: 'error',
									title: '<?= __('error') ?>',
									text: '<?= __('an_error_occurred') ?>'
								});
							}
						});
					}
				});
			}

			$(document).on('click', '.addOtherIncomeBtn', function(e) {
				e.preventDefault();
				const empId = $(this).data('emp-id');
				openAddOtherIncomeModal(empId, null);
			});

			// Inactive Other Income rows are hidden by default (server-rendered display:none) -
			// this checkbox just toggles them, no reload needed.
			$(document).on('change', '#oiShowInactive', function() {
				$('.oi-inactive-row').toggle(this.checked);
			});

			// Handle Deactivate Other Income (stop it before its scheduled end month)
			$(document).on('click', '.deactivateOtherIncomeBtn', function(e) {
				e.preventDefault();
				const id = $(this).data('id');

				Swal.fire({
					icon: 'warning',
					title: '<?= __('deactivate', 'Deactivate') ?>',
					text: '<?= __('confirm_deactivate_other_income', 'Stop this scheduled income? Months not yet paid will no longer receive it.') ?>',
					showCancelButton: true,
					confirmButtonText: '<?= __('deactivate', 'Deactivate') ?>',
					cancelButtonText: '<?= __('cancel') ?>'
				}).then((result) => {
					if (result.isConfirmed) {
						$.ajax({
							url: './includes/ajaxFile/employeeOtherIncomeHandler.php',
							type: 'POST',
							dataType: 'json',
							data: { ajaxType: 'deactivate_employee_other_income', id: id },
							success: function(resp) {
								if (resp.status === 200) {
									Swal.fire({
										icon: 'success',
										title: '<?= __('success') ?>',
										text: resp.message || '<?= __('update_successful', 'Updated successfully') ?>'
									}).then(() => {
										location.reload();
									});
								} else {
									Swal.fire({
										icon: 'error',
										title: '<?= __('error') ?>',
										text: resp.message || '<?= __('update_failed') ?>'
									});
								}
							},
							error: function() {
								Swal.fire({
									icon: 'error',
									title: '<?= __('error') ?>',
									text: '<?= __('an_error_occurred') ?>'
								});
							}
						});
					}
				});
			});

			// Handle Edit Loan Installments
			$(document).on('click', '.editLoanInstallments', function(e) {
				e.preventDefault();
				const loanId = $(this).data('loan-id');
				const currentInstallments = $(this).data('installments');
				const currentMonthlyDeduction = parseFloat($(this).data('monthly-deduction'));
				const remaining = parseFloat($(this).data('remaining'));

				Swal.fire({
					title: '<?= __('edit_installments_plan') ?>',
					html: `
					<div class="form-group text-left">
						<label><?= __('number_of_installments') ?></label>
						<input type="number" id="newInstallments" class="form-control" value="${currentInstallments}" min="1" max="60" placeholder="<?= __('installments_placeholder_example', 'e.g. 12') ?>">
						<small class="text-muted d-block mt-1"><?= __('minimum') ?> 1, <?= __('maximum') ?> 60</small>
						<small id="installmentsFeedback" class="text-danger d-block mt-1"></small>
					</div>
					<div class="form-group text-left">
						<label><?= __('monthly_deduction') ?> (<?= __('sar', 'SAR') ?>)</label>
						<input type="number" id="newMonthlyDeduction" class="form-control" value="${currentMonthlyDeduction.toFixed(2)}" step="0.01" placeholder="0.00" readonly>
						<small class="text-muted d-block mt-1"><?= __('calculated_automatically') ?></small>
					</div>
					<div class="alert alert-info">
						<?= __('remaining_balance') ?>: <strong><i class="icon-saudi_riyal"></i> ${remaining.toFixed(2)}</strong>
					</div>
				`,
					showCancelButton: true,
					confirmButtonText: '<?= __('update') ?>',
					cancelButtonText: '<?= __('cancel') ?>',
					allowOutsideClick: false,
					preConfirm: () => {
						const installments = parseInt($('#newInstallments').val());
						if (!installments || installments < 1 || installments > 60) {
							Swal.showValidationMessage('<?= __('installments_must_be_between_1_and_60') ?>');
							return false;
						}
						const monthlyDeduction = remaining / installments;
						return {
							installments,
							monthlyDeduction
						};
					},
					didOpen: () => {
						const installmentsInput = $('#newInstallments');
						const feedback = $('#installmentsFeedback');
						const confirmButton = Swal.getConfirmButton();

						function validateInstallments() {
							const raw = installmentsInput.val();
							const installments = parseInt(raw);
							const isValid = raw !== '' && !isNaN(installments) && installments >= 1 && installments <= 60;

							if (!isValid) {
								feedback.text(
									!raw || isNaN(installments)
										? '<?= __('please_enter_valid_amount') ?>'
										: '<?= __('installments_must_be_between_1_and_60') ?>'
								);
								$('#newMonthlyDeduction').val('');
							} else {
								feedback.text('');
								const newDeduction = remaining / installments;
								$('#newMonthlyDeduction').val(newDeduction.toFixed(2));
							}

							confirmButton.disabled = !isValid;
						}

						installmentsInput.on('input', validateInstallments);
						validateInstallments();
					}
				}).then((result) => {
					if (result.isConfirmed) {
						const {
							installments,
							monthlyDeduction
						} = result.value;

						// AJAX to update
						$.ajax({
							url: './includes/ajaxFile/ajaxLoan.php',
							type: 'POST',
							dataType: 'json',
							data: {
								ajaxType: 'updateLoanInstallments',
								loan_id: loanId,
								installments: installments,
								monthly_deduction: monthlyDeduction.toFixed(2)
							},
							success: function(resp) {
								if (resp.status === 200) {
									// Update display
									$('#installmentCount').text(installments);
									$('#monthlyDeductionDisplay').text(monthlyDeduction.toFixed(2));

									Swal.fire({
										icon: 'success',
										title: '<?= __('success') ?>',
										text: resp.message || '<?= __('installments_updated_successfully') ?>'
									}).then(() => {
										location.reload();
									});
								} else {
									Swal.fire({
										icon: 'error',
										title: '<?= __('error') ?>',
										text: resp.message || '<?= __('update_failed') ?>'
									});
								}
							},
							error: function() {
								Swal.fire({
									icon: 'error',
									title: '<?= __('error') ?>',
									text: '<?= __('request_failed') ?>'
								});
							}
						});
					}
				});
			});

			// Handle deduction mode change
			$(document).on('change', '#deductionModeSelect', function() {
				const loanId = $(this).data('loan-id');
				const deductionMode = $(this).val();

				if (!loanId) {
					Swal.fire({
						icon: 'error',
						title: '<?= __('error') ?>',
						text: '<?= __('loan_id_missing') ?>'
					});
					return;
				}

				Swal.fire({
					icon: 'question',
					title: '<?= __('confirm_mode_change') ?>',
					html: deductionMode === 'automatic' ?
						'<?= __('switch_to_automatic_msg') ?>' : '<?= __('switch_to_manual_msg') ?>',
					showCancelButton: true,
					confirmButtonText: '<?= __('confirm') ?>',
					cancelButtonText: '<?= __('cancel') ?>'
				}).then((result) => {
					if (result.isConfirmed) {
						$.ajax({
							url: 'includes/ajaxFile/ajaxLoan.php',
							method: 'POST',
							dataType: 'json',
							data: {
								ajaxType: 'updateDeductionMode',
								loan_id: loanId,
								deduction_mode: deductionMode
							},
							success: function(resp) {
								if (resp.status === 200) {
									Swal.fire({
										icon: 'success',
										title: '<?= __('success') ?>',
										text: resp.message || '<?= __('deduction_mode_updated') ?>'
									}).then(() => {
										// If switching to automatic, offer to regenerate deductions
										if (deductionMode === 'automatic') {
											Swal.fire({
												icon: 'info',
												title: '<?= __('regenerate_deductions') ?>?',
												text: '<?= __('regenerate_deductions_msg') ?>',
												showCancelButton: true,
												confirmButtonText: '<?= __('regenerate') ?>',
												cancelButtonText: '<?= __('skip') ?>'
											}).then((regenerateResult) => {
												if (regenerateResult.isConfirmed) {
													regenerateLoanDeductions(loanId);
												}
											});
										}
									});
								} else {
									Swal.fire({
										icon: 'error',
										title: '<?= __('error') ?>',
										text: resp.message || '<?= __('update_failed') ?>'
									});
								}
							},
							error: function() {
								Swal.fire({
									icon: 'error',
									title: '<?= __('error') ?>',
									text: '<?= __('request_failed') ?>'
								});
							}
						});
					} else {
						// Revert the selection if user cancels
						location.reload();
					}
				});
			});

			// Regenerate loan deductions
			function regenerateLoanDeductions(loanId) {
				$.ajax({
					url: 'includes/ajaxFile/ajaxLoan.php',
					method: 'POST',
					dataType: 'json',
					data: {
						ajaxType: 'purgeAndRegenerateLoanDeductions',
						loan_id: loanId
					},
					success: function(resp) {
						Swal.fire({
							icon: resp.status === 200 ? 'success' : 'error',
							title: resp.status === 200 ? '<?= __('success') ?>' : '<?= __('error') ?>',
							text: resp.message || '<?= __('operation_failed') ?>'
						}).then(() => {
							if (resp.status === 200) {
								location.reload();
							}
						});
					},
					error: function() {
						Swal.fire({
							icon: 'error',
							title: '<?= __('error') ?>',
							text: '<?= __('request_failed') ?>'
						});
					}
				});
			}
			// Evaluation Acknowledgment/Objection handlers
			$(document).on('click', '.acknowledge-eval', function() {
				const evalId = $(this).data('id');
				const managerName = $(this).data('manager-name');
				const btn = this;

				Swal.fire({
					title: '<?= __('acknowledge_evaluation', 'Acknowledge Evaluation') ?>',
					html: '<p><?= __('manager_acknowledgment_confirm', 'Are you sure you want to acknowledge this evaluation from') ?> <strong class="manager-name">' + managerName + '</strong>?</p>',
					icon: 'question',
					showCancelButton: true,
					confirmButtonColor: APP_COLORS.success,
					cancelButtonColor: APP_COLORS.secondary,
					confirmButtonText: __('yes_acknowledge', 'Acknowledge'),
					cancelButtonText: __('cancel'),
					allowOutsideClick: false,
					didOpen: () => {
						var currentLang = getCurrentLanguage();
						// Translate manager name
						if (managerName && currentLang === 'ar') {
							translateName(managerName, 'en', 'ar', function(translated) {
								const managerNameEl = document.querySelector('.manager-name');
								if (managerNameEl) managerNameEl.textContent = translated;
							});
						}
					}
				}).then((result) => {
					if (result.isConfirmed) {
						// Submit acknowledgment
						submitManagerAcknowledgment(evalId, 'acknowledged', '', function() {
							location.reload();
						});
					}
				});
			});

			$(document).on('click', '.object-eval', function() {
				const evalId = $(this).data('id');
				const managerName = $(this).data('manager-name');

				Swal.fire({
					title: '<?= __('object_to_evaluation', 'Object to Evaluation') ?>',
					html: '<p><?= __('please_enter_your_objection_reasonnote', 'Please enter your objection reason/note:') ?></p><textarea id="objectionNote" class="form-control swal2-input" placeholder="<?= __('enter_your_objection_here', 'Enter your objection here...') ?>" rows="4" style="resize: vertical; margin-top: 10px;"></textarea>',
					icon: 'warning',
					showCancelButton: true,
					confirmButtonColor: APP_COLORS.warning,
					cancelButtonColor: APP_COLORS.secondary,
					confirmButtonText: __('submit_objection', 'Submit Objection'),
					cancelButtonText: __('cancel'),
					allowOutsideClick: false,
					didOpen: () => {
						$('#objectionNote').focus();
					},
					preConfirm: () => {
						const note = $('#objectionNote').val().trim();
						if (!note) {
							Swal.showValidationMessage('<?= __('please_provide_an_objection_reason', 'Objection note is required!') ?>');
							return false;
						}
						if (note.length < 10) {
							Swal.showValidationMessage('<?= __('objection_note_min_length', 'Please provide at least 10 characters.') ?>');
							return false;
						}
						return note;
					}
				}).then((result) => {
					if (result.isConfirmed) {
						// Submit objection
						submitManagerAcknowledgment(evalId, 'objected', result.value, function() {
							location.reload();
						});
					}
				});
			});

			// Function to submit manager acknowledgment
			function submitManagerAcknowledgment(evalId, status, objectionNote, onSuccess) {
				Swal.fire({
					title: '<?= __('processing') ?>',
					html: '<i class="fa fa-spinner fa-spin" style="font-size: 32px; color: #007bff;"></i><p style="margin-top: 15px;"><?= __('please_wait') ?></p>',
					allowOutsideClick: false,
					allowEscapeKey: false,
					showConfirmButton: false,
					didOpen: () => {
						$.ajax({
							url: './includes/ajaxFile/ajaxEvaluationAcknowledgment.php',
							method: 'POST',
							dataType: 'json',
							data: {
								ajaxType: 'submit_acknowledgment',
								eval_id: evalId,
								acknowledgment_status: status,
								objection_note: objectionNote || ''
							},
							success: function(resp) {
								Swal.close();
								if (resp.status === 200 || resp.status === 'success') {
									Swal.fire({
										icon: 'success',
										title: '<?= __('success') ?>',
										text: resp.message || '<?= __('evaluation_acknowledgment_submitted', 'Evaluation acknowledgment submitted successfully!') ?>',
										confirmButtonColor: APP_COLORS.primary
									}).then(() => {
										if (typeof onSuccess === 'function') {
											onSuccess();
										}
									});
								} else {
									Swal.fire({
										icon: 'error',
										title: '<?= __('error') ?>',
										text: resp.message || '<?= __('failed_to_submit') ?>'
									});
								}
							},
							error: function(xhr, status, error) {
								Swal.close();
								console.error('AJAX Error:', error, xhr.responseText);
								Swal.fire({
									icon: 'error',
									title: '<?= __('error') ?>',
									text: '<?= __('request_failed') ?>'
								});
							}
						});
					}
				});
			}
		</script>

		<?php if ($canViewMemosTab): ?>
		<script src="assets/js/employee_memos.js?v=<?= @filemtime(__DIR__ . '/assets/js/employee_memos.js') ?>"></script>
		<script>
			// Memos tab: load the history only when the tab is first opened.
			$('#memosTabLink').one('shown.bs.tab', function () {
				EmployeeMemos.initHistoryTable('#memos_tbl', <?= json_encode((string) $emprow['emp_id']) ?>);
			});
		</script>
		<?php endif; ?>

	</body>

	</html>
<?php } ?>
