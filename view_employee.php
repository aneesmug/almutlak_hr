<?php

require_once __DIR__ . '/includes/session_check.php';
require_once __DIR__ . '/includes/special_access_helper.php';

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
			: $emprow['probation'] . " Months")
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
		<!-- <link href="assets/css/icons.css" rel="stylesheet" type="text/css" /> -->
		<link href="assets/css/metismenu.min.css" rel="stylesheet" type="text/css" />
		<link href="assets/css/style.css" rel="stylesheet" type="text/css" />
		<link href="assets/css/style_dark.css" rel="stylesheet" type="text/css" />

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
			/* More Actions Modal - Professional Action-Sheet Design */
			.more-actions-modal .swal2-popup {
				border-radius: 16px;
				box-shadow: 0 20px 60px rgba(15, 23, 42, 0.18);
				overflow: hidden;
				background: #f7f8fa;
			}

			.more-actions-modal .swal2-title {
				font-size: 1.25rem;
				font-weight: 700;
				color: #1f2937;
				padding: 1.25rem 1.5rem 1rem;
				margin: 0;
				text-align: left;
				background: #fff;
				border-bottom: 1px solid #eef0f4;
			}

			.more-actions-modal .swal2-html-container {
				margin: 0 !important;
				padding: 0 !important;
				overflow: visible;
			}

			/* Menu Items Container */
			.more-actions-modal .menu-items-container {
				display: flex;
				flex-direction: column;
				gap: 6px;
				margin: 0;
				padding: 10px 10px 14px;
				width: 100%;
				background: #f7f8fa;
				max-height: 60vh;
				overflow-y: auto;
			}

			.more-actions-modal .menu-item {
				display: flex !important;
				align-items: center;
				gap: 12px;
				padding: 10px 12px !important;
				margin: 0 !important;
				cursor: pointer !important;
				transition: all 0.2s ease;
				border: 1px solid transparent;
				border-radius: 12px;
				font-weight: 600;
				font-size: 14.5px;
				user-select: none;
				box-sizing: border-box;
				background-color: #fff;
				position: relative;
				box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04);
			}

			.more-actions-modal .menu-item:hover {
				transform: translateX(2px);
				box-shadow: 0 4px 12px rgba(15, 23, 42, 0.08);
				border-color: currentColor;
			}

			.more-actions-modal .menu-item:active {
				transform: translateX(2px) scale(0.99);
			}

			.more-actions-modal .menu-item i {
				font-size: 15px;
				width: 34px;
				height: 34px;
				border-radius: 10px;
				text-align: center;
				flex-shrink: 0;
				display: flex;
				align-items: center;
				justify-content: center;
				background-color: rgba(108, 117, 125, 0.14);
			}

			.more-actions-modal .menu-item span {
				font-size: 14.5px;
				white-space: nowrap;
				flex: 1;
				overflow: hidden;
				text-overflow: ellipsis;
				color: #374151;
			}

			.more-actions-modal .menu-item::after {
				content: '\f105';
				font-family: 'Font Awesome 5 Free', 'FontAwesome';
				font-weight: 900;
				font-size: 13px;
				color: #c7cbd1;
				flex-shrink: 0;
				transition: transform 0.2s ease, color 0.2s ease;
			}

			.more-actions-modal .menu-item:hover::after {
				transform: translateX(3px);
				color: currentColor;
			}

			/* Color Schemes - icon badge tinted to its own action color, label stays neutral */
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

			.more-actions-modal .menu-item.text-dark { color: var(--dark) !important; }
			.more-actions-modal .menu-item.text-dark i { background-color: rgba(52, 58, 64, 0.14); }

			/* Close Button */
			.more-actions-modal .swal2-close {
				font-size: 1.5rem;
				color: #9aa1ac;
				width: 36px;
				height: 36px;
			}

			.more-actions-modal .swal2-close:hover {
				color: #f46a6a;
			}

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

			/* ===== MODERN PROFILE LAYOUT ===== */
			.profile-content-wrapper {
				background: #f5f7fa;
				padding: 25px;
				border-radius: 8px;
			}

			.profile-section {
				background: white;
				border-radius: 8px;
				margin-bottom: 20px;
				overflow: hidden;
				box-shadow: 0 1px 3px rgba(0,0,0,0.06);
			}

			.profile-section-header {
				background: linear-gradient(135deg, #4facfe 0%, #00a8ff 100%);
				color: white;
				padding: 15px 20px;
				font-size: 15px;
				font-weight: 600;
				display: flex;
				align-items: center;
				gap: 10px;
			}

			.profile-section-header i {
				font-size: 18px;
				opacity: 0.9;
			}

			.profile-section-body {
				padding: 0;
			}

			.profile-grid {
				display: grid;
				grid-template-columns: repeat(2, 1fr);
				gap: 0;
			}

			.profile-field {
				padding: 15px 20px;
				border-bottom: 1px solid #eef2f7;
				border-right: 1px solid #eef2f7;
				display: flex;
				flex-direction: column;
				gap: 5px;
				transition: background 0.2s;
			}

			.profile-field:nth-child(2n) {
				border-right: none;
			}

			.profile-field:hover {
				background: #f8fafc;
			}

			.profile-field-label {
				font-size: 11px;
				font-weight: 600;
				text-transform: uppercase;
				color: #94a3b8;
				letter-spacing: 0.5px;
			}

			.profile-field-value {
				font-size: 14px;
				color: #1e293b;
				font-weight: 500;
				word-break: break-word;
			}

			.profile-field-value .copyToClipboard {
				cursor: pointer;
				padding: 2px 6px;
				border-radius: 4px;
				transition: all 0.2s;
			}

			.profile-field-value .copyToClipboard:hover {
				background: #e0e7ff;
				color: #4f46e5;
			}

			.profile-field-value .fa-clipboard {
				color: #94a3b8;
				font-size: 12px;
				margin-left: 6px;
				cursor: pointer;
				transition: color 0.2s;
			}

			.profile-field-value .fa-clipboard:hover {
				color: #4f46e5;
			}

			/* Full width fields */
			.profile-field.full-width {
				grid-column: 1 / -1;
				border-right: none;
			}

			/* Date badges */
			.date-batch-g, .date-batch-h {
				display: inline-flex;
				align-items: center;
				gap: 4px;
				padding: 6px 12px;
				background: white;
				border: 1px solid #e2e8f0;
				border-radius: 6px;
				font-size: 13px;
				margin-right: 8px;
				margin-top: 4px;
				/* color: #334155; */
				font-weight: 500;
				transition: all 0.2s ease;
			}

			.date-batch-g:hover, .date-batch-h:hover {
				border-color: #4facfe;
				background: #f0f9ff;
				transform: translateY(-1px);
				box-shadow: 0 2px 4px rgba(79, 172, 254, 0.1);
			}

			.date-batch-g::before {
				content: "📅";
				font-size: 14px;
				margin-right: 2px;
			}

			.date-batch-h::before {
				content: "🌙";
				font-size: 14px;
				margin-right: 2px;
			}

			.date-batch-g::after {
				content: attr(data-prefix);
				font-size: 10px;
				font-weight: 600;
				text-transform: uppercase;
				color: #64748b;
				background: #f1f5f9;
				padding: 2px 6px;
				border-radius: 3px;
				letter-spacing: 0.5px;
				margin-left: 4px;
			}

			.date-batch-h::after {
				content: attr(data-prefix);
				font-size: 10px;
				font-weight: 600;
				text-transform: uppercase;
				color: #64748b;
				background: #f1f5f9;
				padding: 2px 6px;
				border-radius: 3px;
				letter-spacing: 0.5px;
				margin-left: 4px;
			}

			@media (max-width: 768px) {
				.profile-grid {
					grid-template-columns: 1fr;
				}

				.profile-field {
					border-right: none;
				}

				.profile-content-wrapper {
					padding: 15px;
				}
			}

			/* Employee Detail Tabs - redesign (tab bar fused to content panel) */
			.emp-tabs {
				display: flex;
				flex-wrap: wrap;
				gap: 4px;
				list-style: none;
				margin: 0;
				padding: 6px 6px 0 6px;
				background: #f4f6f9;
				border: 1px solid rgba(67, 97, 238, 0.18);
				border-bottom: none;
				border-radius: 12px 12px 0 0;
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
				border-radius: 8px;
				font-weight: 600;
				font-size: 0.875rem;
				color: #64748b;
				background: transparent;
				border: none;
				transition: color .15s ease, background-color .15s ease, box-shadow .15s ease;
			}

			.emp-tabs .nav-link i {
				font-size: 1rem;
				line-height: 1;
			}

			.emp-tabs .nav-link:hover {
				color: #1f2937;
				background: rgba(255, 255, 255, 0.7);
			}

			.emp-tabs .nav-link.active {
				color: #fff;
				background: var(--primary, #4361ee);
				box-shadow: 0 4px 10px rgba(67, 97, 238, 0.25);
			}

			.emp-tabs .nav-link .badge-count {
				display: inline-flex;
				align-items: center;
				justify-content: center;
				min-width: 20px;
				height: 20px;
				padding: 0 6px;
				border-radius: 10px;
				font-size: 0.7rem;
				font-weight: 700;
				background: #ffedd5;
				color: #c2410c;
			}

			.emp-tabs .nav-link.active .badge-count {
				background: #ffedd5;
				color: #c2410c;
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
				border: 1px solid rgba(67, 97, 238, 0.35);
				border-top: none;
				border-radius: 0 0 12px 12px;
				background: #fff;
				box-shadow: 0 0 0 1px rgba(67, 97, 238, 0.08), 0 10px 28px rgba(67, 97, 238, 0.14);
				animation: emp-tab-glow-in .2s ease;
			}

			.tab-content > .tab-pane {
				padding: 20px;
			}

			@keyframes emp-tab-glow-in {
				from {
					box-shadow: 0 0 0 0 rgba(67, 97, 238, 0);
					opacity: .7;
				}
				to {
					box-shadow: 0 0 0 1px rgba(67, 97, 238, 0.08), 0 10px 28px rgba(67, 97, 238, 0.14);
					opacity: 1;
				}
			}

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
						$_doc_iqama_gregorian = $DateConv->HijriToGregorian($emprow['iqama_exp'] ?? '', $format);
						$_doc_contract_expiry = computeContractExpiry(
							$emprow['joining_date'] ?? null,
							isset($emprow['vac_period']) ? (int)$emprow['vac_period'] : null,
							'Y-m-d'
						);
						echo get_document_expiry_alerts([
							['label' => getDisplayName('ID / Iqama'),          'expiry_date' => $_doc_iqama_gregorian],
							['label' => getDisplayName('Passport'),            'expiry_date' => $emprow['passport_exp'] ?? null],
							['label' => getDisplayName('Contract Renewal'),    'expiry_date' => $_doc_contract_expiry],
						]);
						?>
						<?php include("./includes/emp_top_info.php"); ?>
						<div class="row">
							<div class="col-xl-12">

								<?php if (!$canViewSalary): ?>
								<div class="alert alert-warning"><i class="fa fa-lock"></i> <?= __('salary_hidden_no_access') ?></div>
								<?php else: ?>
								<div class="row">
									<?php foreach ($shownItems as $item): ?>
										<div class="<?= $colsm ?>">
											<div class="card-box tilebox-one">
												<?php
												$icons = [
													'basic' => 'fa-money-bill-alt duotone-success',
													'housing' => 'fa-home duotone-info',
													'transport' => 'fa-car duotone-danger',
													'food' => 'fa-money-bill-wheat duotone-info',
													'misc' => 'fa-diamond-half duotone-dark',
													'cashier' => 'fa-cash-register duotone-success',
													'fuel' => 'fa-car-wash duotone-info',
													'tel' => 'fa-user-headset duotone-info',
													'other' => 'fa-person-carry duotone-dark',
													'guard' => 'fa-hands-holding-diamond duotone-success',
												];
												$icon = $icons[$item] ?? 'fa-money-bill duotone-secondary';
												?>
												<i class="fad <?= $icon ?> float-right"></i>
												<h6 class="text-muted text-uppercase mt-0"><?= __($item, ucfirst($item)) ?></h6>
												<h2 class="m-b-20" data-plugin="counterup"><?= $emprow[$item] ?> <i class="icon-saudi_riyal"></i></h2>
											</div>
										</div>
									<?php endforeach; ?>
									<div class="<?= $totalColsm ?>">
										<div class="card-box tilebox-one">
											<i class="fad fa-money-bill-trend-up float-right duotone-success"></i>
											<h6 class="text-muted text-uppercase mt-0"><?= __('total_salary') ?></h6>
											<h2 class="m-b-20" data-plugin="counterup"><?= (!$salary_get) ? $emprow["salary"] : $salary_get ?> <i class="icon-saudi_riyal"></i></h2>
										</div>
									</div>
								</div><!-- end row -->
								<?php endif; ?>

								<?php
								$showFlyCard = ($emprow["emp_sup_type"] <> "man_power") && ((int)$emprow['flystus'] > 0);
								$showEncashedCard = ($emprow["emp_sup_type"] <> "man_power") && ((int)$emprow['encashstus'] > 0);
								$showLocalVacCard = ($emprow["emp_sup_type"] <> "man_power") && ($local_vacation_count > 0);
								$showLoanCard = ($canViewSalary && $loan_summary);
								$showIncrementCard = ($canViewSalary && $last_salary_increment);
								?>
								<?php if ($showFlyCard || $showEncashedCard || $showLocalVacCard || $showLoanCard || $showIncrementCard): ?>
									<div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-5 summary-cards-row">
										<?php if ($showFlyCard): ?>
										<div class="col mb-3 d-flex">
											<div class="card-box tilebox-one flex-fill">
												<i class="fad fa-truck-plane float-right duotone-info"></i>
												<h6 class="text-uppercase mt-0 text-muted"><?= __('flys') ?></h6>
												<h2 class="m-b-20" data-plugin="counterup"><?= $emprow['flystus'] ?></h2>
											</div>
										</div><!-- end col -->
										<?php endif; ?>

										<?php if ($showEncashedCard): ?>
										<div class="col mb-3 d-flex">
											<div class="card-box tilebox-one flex-fill">
												<i class="fad fa-money-from-bracket float-right duotone-danger"></i>
												<h6 class="text-uppercase mt-0 text-muted"><?= __('encashed') ?></h6>
												<h2 class="m-b-20"><span data-plugin="counterup"><?= $emprow['encashstus'] ?></span></h2>
											</div>
										</div><!-- end col -->
										<?php endif; ?>

										<?php if ($showLocalVacCard): ?>
										<div class="col mb-3 d-flex">
											<div class="card-box tilebox-one flex-fill">
												<i class="fad fa-umbrella-beach float-right duotone-dark"></i>
												<h6 class="text-uppercase mt-0 text-muted"><?= __('local_vacation', 'Local Vacation') ?></h6>
												<h2 class="m-b-20" data-plugin="counterup"><?= $local_vacation_count ?></h2>
											</div>
										</div><!-- end col -->
										<?php endif; ?>

										<?php if ($showLoanCard): ?>
										<div class="col mb-3 d-flex">
											<div class="card-box tilebox-one flex-fill">
												<i class="fad fa-hand-holding-dollar float-right duotone-warning"></i>
												<h6 class="text-uppercase mt-0 text-muted"><?= __('active_loan_summary', 'Active Loan') ?></h6>
												<h2 class="m-b-20 text-warning"><?= number_format($loan_summary['remaining_balance'], 2) ?> <i class="icon-saudi_riyal"></i></h2>
												<small class="text-muted"><?= ucfirst(str_replace('_', ' ', __($loan_summary['loan_type']))) ?> &middot; <?= __('remaining_balance', 'Remaining Balance') ?></small>
											</div>
										</div><!-- end col -->
										<?php endif; ?>

										<?php if ($showIncrementCard): ?>
										<div class="col mb-3 d-flex">
											<div class="card-box tilebox-one flex-fill">
												<i class="fad fa-arrow-trend-up float-right duotone-success"></i>
												<h6 class="text-uppercase mt-0 text-muted"><?= __('last_salary_increment', 'Last Salary Increment') ?></h6>
												<h2 class="m-b-20 text-success"><?= number_format((float)($last_salary_increment['approved_amount'] ?? $last_salary_increment['increment_amount']), 2) ?> <i class="icon-saudi_riyal"></i></h2>
												<small class="text-muted"><?= !empty($last_salary_increment['effective_date']) ? date('d M Y', strtotime($last_salary_increment['effective_date'])) : '' ?></small>
											</div>
										</div><!-- end col -->
										<?php endif; ?>
									</div><!-- end row -->
								<?php endif; ?>


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
												    <i class="mdi mdi-cash-plus"></i> Apply for Loan<?=__('flys') ?>
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
									<ul class="nav emp-tabs">

										<li class="nav-item">
											<a href="#profile1" data-toggle="tab" aria-expanded="true" class="nav-link active show">
												<i class="fi-head"></i><?= __('profile') ?>
											</a>
										</li>
										<?php /*if($user_type <> "dept_user"){*/ ?>
										<li class="nav-item">
											<a href="#home1" data-toggle="tab" aria-expanded="false" class="nav-link">
												<i class="mdi mdi-buffer"></i> <?= __('vacation_details') ?>
											</a>
										</li>
										<li class="nav-item">
											<a href="#loan1" data-toggle="tab" aria-expanded="false" class="nav-link">
												<i class="mdi mdi-cash-multiple"></i> <?= __('loan_details') ?>
											</a>
										</li>
										<?php if ($canViewSalary): ?>
										<li class="nav-item">
											<a href="#payroll1" data-toggle="tab" aria-expanded="false" class="nav-link">
												<i class="mdi mdi-file-document-box"></i> <?= __('payroll_details', 'Payroll Details') ?>
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
												<i class="mdi mdi-cash-multiple"></i> <?= __('assets_details') ?>
											</a>
										</li>
										<li class="nav-item">
											<a href="#documents" data-toggle="tab" aria-expanded="false" class="nav-link">
												<i class="mdi mdi-book-open-page-variant"></i> <?= __('documents') ?> <?= ($emprow['docs_count'] > 0) ? "<span class='badge-count'>" . $emprow['docs_count'] . "</span>" : "" ?>
											</a>
										</li>
										<li class="nav-item">
											<a href="#noties" data-toggle="tab" aria-expanded="false" class="nav-link">
												<i class="mdi mdi-book-open-page-variant"></i> <?= __('notes') ?> <?= ($emprow['empnote'] > 0) ? "<span class='badge-count'>" . $emprow['empnote'] . "</span>" : "" ?>
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
												<i class="mdi mdi-account-group"></i> <?= __('direct_reports', 'Direct Reports') ?> <span class="badge-count"><?= $direct_reports_count ?></span>
											</a>
										</li>
										<?php endif; ?>
										<?php  ?>
										<?php /*}*/ ?>

										<?php /* ?>	
	<li class="nav-item">
		<a href="#attendance" data-toggle="tab" aria-expanded="false" class="nav-link">
			<i class="mdi mdi-fingerprint mr-2"></i> Attendance
		</a>
	</li>
	<?php */ ?>
									</ul>
									<div class="tab-content">
										<!-- Profile -->
										<div class="tab-pane active show" id="profile1">
											<div class="profile-content-wrapper">
												<!-- Personal Information Section -->
												<div class="profile-section">
													<div class="profile-section-header personal">
														<i class="mdi mdi-account-circle"></i>
														<span><?= __('personal_information', 'Personal Information') ?></span>
													</div>
													<div class="profile-section-body">
														<div class="profile-grid">
															<div class="profile-field">
																<div class="profile-field-label"><?= __('name_of_employee') ?></div>
																<div class="profile-field-value">
																	<span class="copyToClipboard"><?= getDisplayName($emprow['name']); ?></span>
																	<i class="fa fa-clipboard"></i>
																</div>
															</div>
															<div class="profile-field">
																<div class="profile-field-label"><?= __('iqama_id') ?></div>
																<div class="profile-field-value">
																	<span class="copyToClipboard"><?= $emprow['iqama']; ?></span>
																	<i class="fa fa-clipboard"></i>
																</div>
															</div>
															<div class="profile-field">
																<div class="profile-field-label"><?= __('id_expiry') ?></div>
																<div class="profile-field-value">
																	<span class="date-batch-h" data-prefix="<?= __('hijri') ?>"><?= $emprow['iqama_exp']; ?></span>
																	<span class="date-batch-g" data-prefix="<?= __('gregorian') ?>"><?= $DateConv->HijriToGregorian($emprow['iqama_exp'], $format); ?></span>
																</div>
															</div>
															<div class="profile-field">
																<div class="profile-field-label"><?= __('passport_no') ?></div>
																<div class="profile-field-value">
																	<span class="copyToClipboard"><?= $emprow['passport_number']; ?></span>
																	<i class="fa fa-clipboard"></i>
																</div>
															</div>
															<div class="profile-field">
																<div class="profile-field-label"><?= __('passport_expiry') ?></div>
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
																<div class="profile-field-label"><?= __('date_of_birth') ?></div>
																<div class="profile-field-value">
																	<span class="date-batch-g" data-prefix="<?= __('gregorian') ?>"><?= $emprow["dob"]; ?></span>
																	<span class="date-batch-h" data-prefix="<?= __('hijri') ?>"><?= $DateConv->GregorianToHijri($emprow["dob"], $format); ?></span>
																</div>
															</div>
															<div class="profile-field">
																<div class="profile-field-label"><?= __('age') ?></div>
																<div class="profile-field-value"><?= ($emprow["dob"] <> "") ? $years : "" ?></div>
															</div>
															<div class="profile-field">
																<div class="profile-field-label"><?= __('gender_blood_group') ?></div>
																<div class="profile-field-value"><?= ucfirst(__($emprow["sex"])) . " | " . $emprow['blood_type']; ?></div>
															</div>
															<div class="profile-field">
																<div class="profile-field-label"><?= __('marital_status') ?></div>
																<div class="profile-field-value"><?= ucfirst(__($emprow["mar_status"])); ?></div>
															</div>
															<div class="profile-field">
																<div class="profile-field-label"><?= __('tshirt_size') ?></div>
																<div class="profile-field-value"><?= ucfirst($emprow['t_shirt_size']); ?></div>
															</div>
														</div>
													</div>
												</div>

												<!-- Employment Information Section -->
												<div class="profile-section">
													<div class="profile-section-header employment">
														<i class="mdi mdi-briefcase"></i>
														<span><?= __('employment', 'Employment Information') ?></span>
													</div>
													<div class="profile-section-body">
														<div class="profile-grid">
															<div class="profile-field">
																<div class="profile-field-label"><?= __('joining_date') ?></div>
																<div class="profile-field-value">
																	<span class="date-batch-g" data-prefix="<?= __('gregorian') ?>"><?= $emprow["joining_date"]; ?></span>
																	<span class="date-batch-h" data-prefix="<?= __('hijri') ?>"><?= $DateConv->GregorianToHijri($emprow["joining_date"], $format); ?></span>
																</div>
															</div>
															<div class="profile-field">
																<div class="profile-field-label"><?= __('contract_expiry_label', 'Contract Expiry') ?></div>
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
																<div class="profile-field-label"><?= __('department') ?></div>
																<div class="profile-field-value"><?= ($is_rtl ?? false) ? $emprow["deptnme_ar"] : $emprow["deptnme"] ?></div>
															</div>
															<div class="profile-field">
																<div class="profile-field-label"><?= __('company_label') ?></div>
																<div class="profile-field-value"><?= ($is_rtl ?? false) ? ($emprow["compnme_ar"] ?? $emprow["compnme"] ?? __('not_available', 'N/A')) : ($emprow["compnme"] ?? __('not_available', 'N/A')) ?></div>
															</div>
															<div class="profile-field">
																<div class="profile-field-label"><?= __('city_label', 'City') ?></div>
																<div class="profile-field-value"><?= !empty($emprow["cityname"]) ? (($is_rtl ?? false) ? $emprow["cityname_ar"] : $emprow["cityname"]) : __('not_available', 'N/A') ?></div>
															</div>
															<div class="profile-field">
																<div class="profile-field-label"><?= __('location_label', 'Location') ?></div>
																<div class="profile-field-value"><?= !empty($emprow["locationname"]) ? (($is_rtl ?? false) ? $emprow["locationname_ar"] : $emprow["locationname"]) : __('not_available', 'N/A') ?></div>
															</div>
															<div class="profile-field">
																<div class="profile-field-label"><?= __('sub_department_label', 'Sub-Department') ?></div>
																<div class="profile-field-value"><?= !empty($emprow["subdeptname"]) ? (($is_rtl ?? false) ? $emprow["subdeptname_ar"] : $emprow["subdeptname"]) : __('not_available', 'N/A') ?></div>
															</div>
															<div class="profile-field">
																<div class="profile-field-label"><?= __('employee_type') ?? "Employee Type" ?></div>
																<div class="profile-field-value"><?= __(strtolower($emprow['emptype'])) ?? __('not_available', 'N/A') ?></div>
															</div>
															<div class="profile-field">
																<div class="profile-field-label"><?= __('actual_job') ?></div>
																<div class="profile-field-value"><?= ($is_rtl ?? false ? $emprow["jobname_ar"] : $emprow["jobname"]) ?></div>
															</div>
															<div class="profile-field">
																<div class="profile-field-label"><?= __('direct_supervisor') ?? "Direct Supervisor" ?></div>
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
																<div class="profile-field-label"><?= __('probation_period') ?></div>
																<div class="profile-field-value"><?= $probationStatus ?></div>
															</div>
															<div class="profile-field">
																<div class="profile-field-label"><?= __('eligible_for_overtime', 'Eligible For Overtime') ?></div>
																<div class="profile-field-value"><?= $overtimeEligibilityText ?></div>
															</div>
															<div class="profile-field">
																<div class="profile-field-label"><?= __('allow_emergency_vacation', 'Allow Emergency Vacation') ?></div>
																<div class="profile-field-value"><?= $allowEmergencyVacationText ?></div>
															</div>
															<div class="profile-field">
																<div class="profile-field-label"><?= __('sponsorship_label') ?></div>
																<div class="profile-field-value"><?= ($is_rtl ?? false) ? $emprow['sponsor_ar'] : $emprow['sponsor'] ?></div>
															</div>
															<div class="profile-field">
																<div class="profile-field-label"><?= __('contract_period') ?></div>
																<div class="profile-field-value"><?= translateContractPeriod($emprow["period"]) ?></div>
															</div>
														</div>
													</div>
												</div>

												<!-- Contact Information Section -->
												<div class="profile-section">
													<div class="profile-section-header contact">
														<i class="mdi mdi-phone"></i>
														<span><?= __('contact', 'Contact Information') ?></span>
													</div>
													<div class="profile-section-body">
														<div class="profile-grid">
															<div class="profile-field full-width">
																<div class="profile-field-label"><?= __('email') ?></div>
																<div class="profile-field-value">
																	<?= ($emprow['c_email']) ? "<b>" . __('personal') . ":</b> <span class='copyToClipboard'>" . $emprow['email'] . "</span> <i class='fa fa-clipboard'></i> | <b>" . __('company') . ":</b> <span class='copyToClipboard'>" . $emprow['c_email'] . "</span> <i class='fa fa-clipboard'></i>" : "<span class='copyToClipboard'>" . $emprow['email'] . "</span> <i class='fa fa-clipboard'></i>" ?>
																</div>
															</div>
															<div class="profile-field">
																<div class="profile-field-label"><?= __('mobile') ?></div>
																<div class="profile-field-value">
																	<span class="copyToClipboard"><?= $emprow['mobile']; ?></span>
																	<i class="fa fa-clipboard"></i>
																</div>
															</div>
															<div class="profile-field">
																<div class="profile-field-label"><?= __('country') ?></div>
																<div class="profile-field-value"><?= ($is_rtl ?? false) ? $emprow["country_name_ar"] : $emprow["country_name"]; ?></div>
															</div>
															<div class="profile-field full-width">
																<div class="profile-field-label"><?= __('emergency_contact') ?></div>
																<div class="profile-field-value"><?= $emprow['emg_name'] . " - " . $emprow["emg_mobile"] ?></div>
															</div>
															<div class="profile-field full-width">
																<div class="profile-field-label"><?= __('address') ?></div>
																<div class="profile-field-value"><?= getDisplayName(ucfirst($emprow['address'])) ?></div>
															</div>
														</div>
													</div>
												</div>

												<!-- Banking & Salary Information Section -->
												<div class="profile-section">
													<div class="profile-section-header banking">
														<i class="mdi mdi-bank"></i>
														<span><?= __('bank_&_gosi_details', 'Banking & Salary') ?></span>
													</div>
													<div class="profile-section-body">
														<div class="profile-grid">
															<div class="profile-field">
																<div class="profile-field-label"><?= __('total_salary') ?></div>
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
																<div class="profile-field-label"><?= __('bank_name') ?></div>
																<div class="profile-field-value"><?= ($is_rtl ?? false) ? $emprow["b_name_ar"] : $emprow["b_name"] ?></div>
															</div>
															<div class="profile-field full-width">
																<div class="profile-field-label"><?= __('iban') ?></div>
																<div class="profile-field-value">
																	<span class="copyToClipboard"><?= $emprow["iban"] ?></span>
																	<i class="fa fa-clipboard"></i>
																</div>
															</div>
															<div class="profile-field">
																<div class="profile-field-label"><?= __('gosi_gosi_no') ?></div>
																<div class="profile-field-value"><?= $emprow["gosi"] . " | " . $emprow["gosi_no"] ?></div>
															</div>
															<div class="profile-field">
																<div class="profile-field-label"><?= __('gosi_expiry') ?></div>
																<div class="profile-field-value"><?= $emprow["date_hijri"] . " | " . $emprow["date_greg"] ?></div>
															</div>
														</div>
													</div>
												</div>

												<!-- Insurance Information Section -->
												<div class="profile-section">
													<div class="profile-section-header insurance">
														<i class="mdi mdi-shield-check"></i>
														<span><?= __('insurance', 'Insurance Information') ?></span>
													</div>
													<div class="profile-section-body">
														<div class="profile-grid">
															<div class="profile-field">
																<div class="profile-field-label"><?= __('insurance_no_class') ?></div>
																<div class="profile-field-value"><?= display_or_na($current_medical_insurance['insurance_no'] ?? null) ?> | <?= display_or_na($current_medical_insurance['medical_class'] ?? null) ?></div>
															</div>
															<div class="profile-field">
																<div class="profile-field-label"><?= __('insurance_expiry') ?></div>
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
												<div class="profile-section">
													<div class="profile-section-header car">
														<i class="mdi mdi-car"></i>
														<span><?= __('car_details', 'Car Information') ?></span>
													</div>
													<div class="profile-section-body">
														<div class="profile-grid">
															<div class="profile-field">
																<div class="profile-field-label"><?= __('car_maker') ?></div>
																<div class="profile-field-value"><?= getDisplayName(car_get_info($emprow["car_id"])['maker_name']) . " (" . car_get_info($emprow["car_id"])['made_year'] . ")" ?></div>
															</div>
															<div class="profile-field">
																<div class="profile-field-label"><?= __('car_model') ?></div>
																<div class="profile-field-value"><?= getDisplayName(car_get_info($emprow["car_id"])['model']) ?></div>
															</div>
														</div>
													</div>
												</div>
												<?php endif; ?>
											</div>

											<div class="text-right mt-4">
												<div class="btn-group" role="group" aria-label="Edit Button">
													<a href="./employee_profile.php?emp_id=<?= $emprow['empid']; ?>" class="btn btn-sm waves-effect btn-primary" target="_blank"><i class="fi-printer "></i> <?= __('print_profile') ?></a>
												</div>
											</div>
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
											<div class="card border-primary border mb-4">
												<div class="card-header bg-primary text-white font-weight-bold"><?= __('vacation_balance_summary') ?></div>
												<div class="card-body">
													<?php if ($vac_total > 0 || $vac_used > 0 || $vac_available > 0): ?>
														<div class="row text-center">
															<div class="col-md-4">
																<h6 class="text-muted text-uppercase"><?= __('total_vacation_days') ?></h6>
																<h4><?= number_format($vac_total, 1) ?></h4>
															</div>
															<div class="col-md-4">
																<h6 class="text-muted text-uppercase"><?= __('used_days') ?></h6>
																<h4 class="text-warning"><?= number_format($vac_used, 1) ?></h4>
															</div>
															<div class="col-md-4">
																<h6 class="text-muted text-uppercase"><?= __('available_balance') ?></h6>
																<h4 class="text-success font-weight-bold"><?= number_format($vac_available, 2) ?></h4>
															</div>
														</div>
													<?php else: ?>
														<div class="alert alert-info mb-0" role="alert">
															<i class="mdi mdi-information-outline mr-2"></i>
															<?= __('no_vacation_balance_record_found') ?>
														</div>
													<?php endif; ?>
												</div>
											</div>
											<div class="table-responsive">
												<?php if ($isHR || $is_system_admin || $isDeptHr) { ?>
													<div class="mb-3">
														<button type="button" class="btn btn-primary btn-sm" onclick="addManualVacationHistory(<?= (int)$emprow['empid'] ?>, '<?= htmlspecialchars($emprow['name'] ?? '', ENT_QUOTES) ?>')">
															<i class="mdi mdi-plus-circle mr-1"></i><?= __('add_manual_vacation_history') ?>
														</button>
													</div>
												<?php } ?>
												<h4 class="m-t-0 header-title"></h4>
												<table id="employee_vac" class="table table-striped table-bordered dt-responsive nowrap" style="border-collapse: collapse; border-spacing: 0; width: 100%;">
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
														<table id="employee_vac_activity_log" class="table table-striped table-bordered dt-responsive nowrap" style="border-collapse: collapse; border-spacing: 0; width: 100%;">
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
														<table id="employee_vac_cron_snapshot" class="table table-striped table-bordered dt-responsive nowrap" style="border-collapse: collapse; border-spacing: 0; width: 100%;">
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
											<?php if ($loan_summary): ?>
												<div class="card border-primary border mb-4">
													<div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
														<h5 class="mb-0"><i class="fa fa-info-circle"></i> <?= __('active_loan_summary') ?></h5>
													</div>
													<div class="card-body">
														<div class="row">
															<!-- Loan Details Column -->
															<div class="col-md-6">
																<div class="info-block mb-3">
																	<h6 class="text-muted text-uppercase mb-3"><i class="fa fa-file-text"></i> <?= __('loan_details') ?></h6>
																	<table class="table table-sm table-borderless">
																		<tr>
																			<td class="font-weight-bold" width="50%"><?= __('invoice_number') ?>:</td>
																			<td><?= htmlspecialchars($loan_summary['inv_no']) ?></td>
																		</tr>
																		<tr>
																			<td class="font-weight-bold"><?= __('loan_type') ?>:</td>
																			<td><span class="badge badge-info"><?= ucfirst(str_replace('_', ' ', __($loan_summary['loan_type']))) ?></span></td>
																		</tr>
																		<tr>
																			<td class="font-weight-bold"><?= __('approved_amount') ?>:</td>
																			<td class="text-primary font-weight-bold"><?= number_format($loan_summary['final_approved_amount'], 2) ?> <i class="icon-saudi_riyal"></i></td>
																		</tr>
																		<tr>
																			<td class="font-weight-bold"><?= __('installments') ?>:</td>
																			<td><span id="installmentCount"><?= $loan_summary['installments'] ?></span> <?= __('months') ?></td>
																		</tr>
																		<tr>
																			<td class="font-weight-bold"><?= __('monthly_deduction') ?>:</td>
																			<td class="text-warning font-weight-bold"><span id="monthlyDeductionDisplay"><?= number_format($loan_summary['monthly_deduction'], 2) ?></span> <i class="icon-saudi_riyal"></i></td>
																		</tr>
																		<tr>
																			<td class="font-weight-bold"><?= __('deduction_mode') ?>:</td>
																			<td>
																				<select id="deductionModeSelect" class="form-control form-control-sm w-auto" data-loan-id="<?= $loan_summary['loan_id'] ?>">
																					<option value="automatic" <?= ($loan_summary['deduction_mode'] ?? 'automatic') === 'automatic' ? 'selected' : '' ?>>
																						<?= __('automatic_monthly') ?>
																					</option>
																					<option value="manual" <?= ($loan_summary['deduction_mode'] ?? 'automatic') === 'manual' ? 'selected' : '' ?>>
																						<?= __('manual_addition') ?>
																					</option>
																				</select>
																			</td>
																		</tr>
																		<tr>
																			<td class="font-weight-bold"><?= __('installments_plan') ?>:</td>
																			<td>
																				<button type="button" class="btn btn-sm btn-light editLoanInstallments"
																					data-loan-id="<?= $loan_summary['loan_id'] ?>"
																					data-installments="<?= $loan_summary['installments'] ?>"
																					data-monthly-deduction="<?= $loan_summary['monthly_deduction'] ?>"
																					data-remaining="<?= $loan_summary['remaining_balance'] ?>">
																					<i class="fa fa-edit"></i> <?= __('edit_plan') ?>
																				</button>
																			</td>
																		</tr>
																		<tr>
																			<td class="font-weight-bold"><?= __('start_date') ?>:</td>
																			<td>
																				<?php
																				$loanStartDate = $loan_summary['start_date'] ?? '';
																				echo (!empty($loanStartDate) && $loanStartDate !== '0000-00-00')
																					? format_safe_date($loanStartDate, 'd M, Y')
																					: '-';
																				?>
																			</td>
																		</tr>
																		<tr>
																			<td class="font-weight-bold"><?= __('end_date') ?>:</td>
																			<td>
																				<?php
																				$loanEndDate = $loan_summary['end_date'] ?? '';
																				echo (!empty($loanEndDate) && $loanEndDate !== '0000-00-00')
																					? format_safe_date($loanEndDate, 'd M, Y')
																					: '-';
																				?>
																			</td>
																		</tr>
																	</table>
																</div>
															</div>

															<!-- Payment Summary Column -->
															<div class="col-md-6">
																<div class="info-block mb-3">
																	<h6 class="text-muted text-uppercase mb-3"><i class="fa fa-money"></i> <?= __('payment_summary') ?></h6>
																	<div class="payment-stat mb-3 p-3 bg-light rounded">
																		<small class="text-muted d-block"><?= __('total_loan_amount') ?></small>
																		<h4 class="mb-0 text-primary"><?= number_format($loan_summary['total_payable'], 2) ?> <i class="icon-saudi_riyal"></i></h4>
																	</div>
																	<div class="payment-stat mb-3 p-3 bg-light rounded">
																		<small class="text-muted d-block"><?= __('total_paid') ?></small>
																		<h4 class="mb-0 text-success"><?= number_format($loan_summary['total_paid'], 2) ?> <i class="icon-saudi_riyal"></i></h4>
																	</div>
																	<div class="payment-stat mb-3 p-3 bg-light rounded">
																		<small class="text-muted d-block"><?= __('remaining_balance') ?></small>
																		<h4 class="mb-0 text-danger font-weight-bold"><span id="remainingDisplay"><?= number_format($loan_summary['remaining_balance'], 2) ?></span> <i class="icon-saudi_riyal"></i></h4>
																	</div>
																	<?php if ($loan_summary['remaining_balance'] > 0): ?>
																		<div class="mt-3">
																			<button type="button" class="btn btn-success btn-block waves-effect waves-light addManualPayment"
																				data-loan-id="<?= $loan_summary['loan_id'] ?>"
																				data-emp-id="<?= $emprow['empid'] ?>"
																				data-remaining="<?= $loan_summary['remaining_balance'] ?>">
																				<i class="mdi mdi-cash-multiple"></i> <?= __('add_manual_payment') ?>
																			</button>
																		</div>
																	<?php endif; ?>
																</div>
															</div>
														</div>
														<?php if ($loan_summary['disbursement_receipt']): ?>
															<hr>
															<div class="row">
																<div class="col-md-6">
																	<strong><?= __('disbursement_receipt_id') ?>:</strong>
																	<p><?= htmlspecialchars($loan_summary['disbursement_receipt']); ?></p>
																</div>
																<div class="col-md-6">
																	<strong><?= __('disbursement_proof') ?>:</strong>
																	<p><a href="./assets/loan_receipts/<?= htmlspecialchars($loan_summary['disbursement_attachment']); ?>" target="_blank" class="btn btn-sm btn-info"><i class="fa fa-eye"></i> <?= __('view_attachments', 'View Attachment') ?></a></p>
																</div>
															</div>
														<?php endif; ?>
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
											<table id="loan_history_tbl" class="table table-striped table-bordered dt-responsive nowrap" style="border-collapse: collapse; border-spacing: 0; width: 100%;">
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
																<td><a href="./loan_report_details.php?id=<?= $topup_rec['loan_id'] ?>&emp_id=<?= $emprow['emp_id'] ?>" target="_blank" class="btn btn-sm btn-dark"><i class="fa fa-eye"></i> <?= __('view') ?></a></td>
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
															<td><a href="./loan_report_details.php?id=<?= $loan_id_hist ?>&emp_id=<?= $emprow['emp_id'] ?>" target="_blank" class="btn btn-sm btn-dark"><i class="fa fa-eye"></i> <?= __('view') ?></a></td>
														</tr>
													<?php } ?>
												</tbody>
											</table>

											<h4 class="header-title m-t-0 m-b-30 mt-4"><?= __('repayment_history') ?></h4>
											<table id="payment_history_tbl" class="table table-striped table-bordered dt-responsive nowrap" style="border-collapse: collapse; border-spacing: 0; width: 100%;">
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
											<table id="payroll_history_tbl" class="table table-striped table-bordered dt-responsive nowrap" style="width: 100%;">
												<thead>
													<tr>
														<th><?= __('month', 'Month') ?></th>
														<th><?= __('total_gross_salary', 'Total Gross Salary') ?></th>
														<th><?= __('total_benefits', 'Additional Benefits') ?></th>
														<th><?= __('total_deductions', 'Total Deductions') ?></th>
														<th><?= __('net_salary', 'Net Salary') ?></th>
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
											<?php if ($canViewAdditionalInfo): ?>
											<div class="d-flex justify-content-between align-items-center mb-3">
												<h4 class="header-title m-t-0"><?= __('additional_information', 'Additional Information') ?></h4>
												<?php if ($canEditAdditionalInfo): ?>
												<button type="button" class="btn btn-sm btn-primary editAdditionalInfoBtn" data-emp-id="<?= htmlspecialchars($emprow['empid']); ?>">
													<i class="mdi mdi-pencil"></i> <?= __('edit', 'Edit') ?>
												</button>
												<?php endif; ?>
											</div>
											<div class="profile-section">
												<div class="profile-section-body">
													<div class="profile-grid" id="additionalInfoDisplay">
														<div class="profile-field">
															<div class="profile-field-label"><?= __('salary_grade', 'Salary Grade') ?></div>
															<div class="profile-field-value" data-field="salary_grade"><?= !empty($employee_additional_info['salary_grade']) ? __('grade', 'Grade') . ' ' . htmlspecialchars($employee_additional_info['salary_grade']) : __('not_available') ?></div>
														</div>
														<div class="profile-field">
															<div class="profile-field-label"><?= __('dependants_count', 'No. of Dependants') ?></div>
															<div class="profile-field-value" data-field="dependants_count"><?= display_or_na($employee_additional_info['dependants_count'] ?? null) ?></div>
														</div>
														<div class="profile-field">
															<div class="profile-field-label"><?= __('ticket_fare', 'Ticket') ?></div>
															<div class="profile-field-value" data-field="ticket_fare"><?= isset($employee_additional_info['ticket_fare']) ? number_format((float)$employee_additional_info['ticket_fare'], 2) . ' <i class="icon-saudi_riyal"></i>' : __('not_available') ?></div>
														</div>
														<?php if ((int)($emprow['country'] ?? 0) !== 191): ?>
														<div class="profile-field">
															<div class="profile-field-label"><?= __('labour_office_expense', 'Labour Office Expenses') ?></div>
															<div class="profile-field-value" data-field="labour_office_expense"><?= isset($employee_additional_info['labour_office_expense']) ? number_format((float)$employee_additional_info['labour_office_expense'], 2) . ' <i class="icon-saudi_riyal"></i>' : __('not_available') ?></div>
														</div>
														<div class="profile-field">
															<div class="profile-field-label"><?= __('iqama_renewal_fee', 'Iqama Renewal Fee') ?></div>
															<div class="profile-field-value" data-field="iqama_renewal_fee"><?= isset($employee_additional_info['iqama_renewal_fee']) ? number_format((float)$employee_additional_info['iqama_renewal_fee'], 2) . ' <i class="icon-saudi_riyal"></i>' : __('not_available') ?></div>
														</div>
														<div class="profile-field">
															<div class="profile-field-label"><?= __('citizen_local_relation', 'Citizen (Local)') ?></div>
															<div class="profile-field-value" data-field="citizen_local_relation"><?= display_or_na($employee_additional_info['citizen_local_relation'] ?? null) ?></div>
														</div>
														<?php endif; ?>
														<div class="profile-field">
															<div class="profile-field-label"><?= __('monthly_leave_accrual', 'Monthly Leave Accrual') ?> <small class="text-muted">(<?= __('auto_calculated', 'auto-calculated') ?>)</small></div>
															<div class="profile-field-value"><?= $monthly_leave_accrual_calculated !== null ? number_format($monthly_leave_accrual_calculated, 2) . ' ' . __('day_s', 'Days') : __('not_available') ?></div>
														</div>
														<div class="profile-field">
															<div class="profile-field-label"><?= __('eng_council_fee', 'Saudi Engineering Council Fee') ?></div>
															<div class="profile-field-value" data-field="eng_council_fee"><?= isset($employee_additional_info['eng_council_fee']) ? number_format((float)$employee_additional_info['eng_council_fee'], 2) . ' <i class="icon-saudi_riyal"></i>' : __('not_available') ?></div>
														</div>
														<div class="profile-field">
															<div class="profile-field-label"><?= __('eng_council_expiry', 'Saudi Engineering Council Expiry') ?></div>
															<div class="profile-field-value" data-field="eng_council_expiry"><?= (!empty($employee_additional_info['eng_council_expiry']) && $employee_additional_info['eng_council_expiry'] !== '0000-00-00') ? format_safe_date($employee_additional_info['eng_council_expiry'], 'd M, Y') : __('not_available') ?></div>
														</div>
													</div>
												</div>
											</div>

										<div class="d-flex justify-content-between align-items-center mb-3 mt-4">
											<h4 class="header-title m-t-0"><?= __('medical_insurance', 'Medical Insurance') ?> <small class="text-muted">(<?= __('renews_yearly', 'renews yearly') ?>)</small></h4>
											<?php if ($canEditAdditionalInfo): ?>
											<button type="button" class="btn btn-sm btn-primary addMedicalInsuranceBtn" data-emp-id="<?= htmlspecialchars($emprow['empid']); ?>">
												<i class="mdi mdi-plus"></i> <?= __('add_insurance', 'Add Insurance Details') ?>
											</button>
											<?php endif; ?>
										</div>
										<div class="profile-section">
											<div class="profile-section-body">
												<div class="profile-grid">
													<div class="profile-field">
														<div class="profile-field-label"><?= __('insurance_no', 'Insurance No') ?></div>
														<div class="profile-field-value"><?= display_or_na($current_medical_insurance['insurance_no'] ?? null) ?></div>
													</div>
													<div class="profile-field">
														<div class="profile-field-label"><?= __('med_insurance', 'Medical Insurance') ?></div>
														<div class="profile-field-value"><?= isset($current_medical_insurance['med_insurance']) ? number_format((float)$current_medical_insurance['med_insurance'], 2) . ' <i class="icon-saudi_riyal"></i>' : __('not_available') ?></div>
													</div>
													<div class="profile-field">
														<div class="profile-field-label"><?= __('medical_expiry', 'Medical Expiry') ?></div>
														<div class="profile-field-value"><?= (!empty($current_medical_insurance['medical_expiry']) && $current_medical_insurance['medical_expiry'] !== '0000-00-00') ? format_safe_date($current_medical_insurance['medical_expiry'], 'd M, Y') : __('not_available') ?></div>
													</div>
													<div class="profile-field">
														<div class="profile-field-label"><?= __('medical_class', 'Medical Class') ?></div>
														<div class="profile-field-value"><?= display_or_na($current_medical_insurance['medical_class'] ?? null) ?></div>
													</div>
												</div>
											</div>
										</div>

										<?php if (count($medical_insurance_records) > 1): ?>
										<h5 class="header-title mt-4"><?= __('insurance_history', 'Insurance History') ?></h5>
										<table class="table table-striped table-bordered" style="width: 100%;">
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
										</table>
										<?php endif; ?>
										<?php endif; // $canViewAdditionalInfo ?>
										<?php if ($canViewOtherIncome): ?>
										<div class="d-flex justify-content-between align-items-center mb-3 mt-4">
											<h4 class="header-title m-t-0"><?= __('other_income', 'Other Income') ?> <small class="text-muted">(<?= __('scheduled_months', 'scheduled months') ?>)</small></h4>
											<?php if ($canEditOtherIncome): ?>
											<button type="button" class="btn btn-sm btn-primary addOtherIncomeBtn" data-emp-id="<?= htmlspecialchars($emprow['empid']); ?>">
												<i class="mdi mdi-plus"></i> <?= __('add_other_income', 'Add Other Income') ?>
											</button>
											<?php endif; ?>
										</div>
										<?php if (!empty($other_income_records)): ?>
										<?php $has_inactive_other_income = false; foreach ($other_income_records as $oi_row) { if ((int)$oi_row['status'] !== 1) { $has_inactive_other_income = true; break; } } ?>
										<?php if ($has_inactive_other_income): ?>
										<div class="form-check mb-2">
											<input type="checkbox" class="form-check-input" id="oiShowInactive">
											<label class="form-check-label" for="oiShowInactive"><?= __('show_inactive', 'Show Inactive') ?></label>
										</div>
										<?php endif; ?>
										<table class="table table-striped table-bordered" style="width: 100%;">
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
										</table>
										<?php else: ?>
										<p class="text-muted"><?= __('no_other_income_scheduled', 'No scheduled other income records.') ?></p>
										<?php endif; ?>
										<?php endif; // $canViewOtherIncome ?>
										<?php endif; // $canViewAdditionalInfo || $canViewOtherIncome ?>

										</div>
										<div class="tab-pane" id="assets">
											<h4 class="header-title m-t-0 m-b-30 mt-4"><?= __('assigned_assets') ?></h4>
											<table id="assets_tbl" class="table table-striped table-bordered dt-responsive nowrap" style="width: 100%;">
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


										<div class="tab-pane" id="documents">
											<div class="card-box">
												<div class="d-flex justify-content-between align-items-center mb-3">
													<h4 class="header-title m-t-0"><i class="mdi mdi-file-document-multiple"></i> <?= __('my_files') ?></h4>
													<span class="badge badge-primary badge-pill"><?php echo mysqli_num_rows(mysqli_query($conDB, "SELECT * FROM `emp_docu` WHERE `emp_id`='" . $emprow['empid'] . "'")); ?></span>
												</div>

												<?php
												$queryempdocu = mysqli_query($conDB, "SELECT * FROM `emp_docu` WHERE `emp_id`='" . $emprow['empid'] . "' ORDER BY `id` DESC ");
												$doc_count = mysqli_num_rows($queryempdocu);

												if ($doc_count > 0):
												?>
													<div class="row">
														<!-- Documents List Column -->
														<div class="col-12">
															<div class="documents-list-container">
																<?php
																mysqli_data_seek($queryempdocu, 0);
																while ($recempdoc = mysqli_fetch_assoc($queryempdocu)) {
																	$id_empdoc_get = $recempdoc["id"];
																	$docu_typ_get = $recempdoc["docu_typ"];
																	$attachment_get = $recempdoc["path"];
																	$docu_ext_get = strtolower($recempdoc["docu_ext"]);
																	$doc_date_reg_get = $recempdoc["created_at"];
																	$times_reg = strtotime("$doc_date_reg_get");
																	$doc_date_formatted = date('d M Y', $times_reg);

																	// Determine file type and icon
																	$file_type_map = [
																		'pdf' => ['icon' => 'fa-file-pdf', 'color' => 'danger', 'label' => 'PDF'],
																		'xls' => ['icon' => 'fa-file-excel', 'color' => 'success', 'label' => 'Excel'],
																		'xlsx' => ['icon' => 'fa-file-excel', 'color' => 'success', 'label' => 'Excel'],
																		'doc' => ['icon' => 'fa-file-word', 'color' => 'primary', 'label' => 'Word'],
																		'docx' => ['icon' => 'fa-file-word', 'color' => 'primary', 'label' => 'Word'],
																		'jpg' => ['icon' => 'fa-file-image', 'color' => 'info', 'label' => 'Image'],
																		'jpeg' => ['icon' => 'fa-file-image', 'color' => 'info', 'label' => 'Image'],
																		'png' => ['icon' => 'fa-file-image', 'color' => 'info', 'label' => 'Image'],
																		'gif' => ['icon' => 'fa-file-image', 'color' => 'info', 'label' => 'Image'],
																		'zip' => ['icon' => 'fa-file-archive', 'color' => 'warning', 'label' => 'Archive'],
																		'rar' => ['icon' => 'fa-file-archive', 'color' => 'warning', 'label' => 'Archive'],
																		'txt' => ['icon' => 'fa-file-text', 'color' => 'secondary', 'label' => 'Text'],
																	];
																	$file_info = $file_type_map[$docu_ext_get] ?? ['icon' => 'fa-file', 'color' => 'secondary', 'label' => 'File'];
																?>
																	<div class="doc-list-item" data-doc-id="<?= $id_empdoc_get ?>"
																		data-doc-path="./assets/emp_documents/<?= $attachment_get ?>"
																		data-doc-ext="<?= $docu_ext_get ?>"
																		data-doc-name="<?= htmlspecialchars($docu_typ_get ?: $file_info['label']) ?>">
																		<div class="doc-item-icon">
																			<i class="fa <?= $file_info['icon'] ?> text-<?= $file_info['color'] ?>"></i>
																		</div>
																		<div class="doc-item-info">
																			<h6 class="doc-item-name"><?= getDisplayName($file_info['label']) ?></h6>
																			<small class="doc-item-meta">
																				<span class="badge badge-<?= $file_info['color'] ?> badge-sm"><?= strtoupper($docu_ext_get) ?></span>
																				<span class="text-muted ml-2"><i class="fa fa-calendar"></i> <?= $doc_date_formatted ?></span>
																			</small>
																		</div>
																		<div class="doc-item-actions">
																			<button class="btn btn-sm btn-icon btn-download-doc" title="<?= __('download', 'Download') ?>" onclick="window.location.href='./downloadFile.php?file=./assets/emp_documents/<?= $attachment_get ?>'">
																				<i class="fa fa-download"></i>
																			</button>
																			<button class="btn btn-sm btn-icon btn-delete-item deleteAjax" data-id='<?= $recempdoc['id'] ?>' data-tbl='emp_docu' data-file='1' data-column='path' title="<?= __('delete') ?>">
																				<i class="fa fa-trash"></i>
																			</button>
																		</div>
																	</div>
																<?php } ?>
															</div>
														</div>

														<!-- Document Viewer Column -->
														<div class="col-md-7">
															<div class="document-viewer-container" style="display:none;">
																<div class="viewer-header">
																	<h5 class="viewer-title"><i class="fa fa-file"></i> <span id="viewer-doc-name"><?= __('select_document') ?></span></h5>
																	<div class="viewer-actions">
																		<button class="btn btn-sm btn-light" id="viewer-fullscreen" title="<?= __('fullscreen') ?>">
																			<i class="fa fa-expand"></i>
																		</button>
																		<button class="btn btn-sm btn-light" id="viewer-download" title="<?= __('download', 'Download') ?>" style="display:none;">
																			<i class="fa fa-download"></i>
																		</button>
																		<button class="btn btn-sm btn-light" id="viewer-clear" title="<?= __('close') ?>">
																			<i class="fa fa-times"></i>
																		</button>
																	</div>
																</div>
																<div class="viewer-body" id="document-viewer">
																	<div class="viewer-placeholder">
																		<i class="fa fa-file-text" style="font-size: 64px; color: #ddd;"></i>
																		<p class="text-muted mt-3"><?= __('select_document_to_view') ?></p>
																	</div>
																</div>
															</div>
														</div>
													</div>
												<?php else: ?>
													<div class="alert alert-info" role="alert">
														<div class="d-flex align-items-center">
															<i class="fa fa-info-circle mr-3" style="font-size: 24px;"></i>
															<div>
																<h5 class="mb-1"><?= __('no_documents_found') ?></h5>
																<p class="mb-0"><?= __('no_documents_have_been_uploaded_yet') ?></p>
															</div>
														</div>
													</div>
												<?php endif; ?>
											</div>
										</div>
										<div class="tab-pane" id="noties">
											<div class="card-box">
												<h4 class="header-title m-b-30"><?= __('all_notes') ?></h4>
												<table id="notes_tbl" class="table table-striped table-bordered dt-responsive nowrap" style="border-collapse: collapse; border-spacing: 0; width: 100%;"></table>
											</div>
										</div>


										<?php /*}*/ ?>
										<div class="tab-pane" id="attendance">
											<div class="card-box">

												<h4 class="header-title m-b-30"><?= __('attendance_record', 'Attendance Record') ?></h4>

												<div class="col-4 pull-right">
													<div class="input-group input-daterange">
														<input type="text" id="FromDate" class="form-control date-range-filter" data-date-format="yyyy-mm-dd" placeholder="<?= __('from', 'From') ?>:">
														<div class="input-group-addon"><?= __('to', 'to') ?></div>
														<input type="text" id="Todate" class="form-control date-range-filter" data-date-format="yyyy-mm-dd" placeholder="<?= __('to', 'To') ?>:">
													</div>
												</div>


												<table id="attendance_tbl" class="table table-striped table-bordered dt-responsive nowrap" style="border-collapse: collapse; border-spacing: 0; width: 100%;">
													<thead>
														<tr>
															<th><?= __('id') ?></th>
															<th><?= __('emp_id', 'Emp ID') ?>.</th>
															<th><?= __('employee_name', 'Employee Name') ?></th>
															<th><?= __('date') ?></th>
															<th><?= __('check_in', 'Check In') ?></th>
															<th><?= __('check_out', 'Check Out') ?></th>
															<th><?= __('hours') ?></th>
															<th><?= __('punch_type', 'Punch Type') ?></th>
															<th><?= __('note') ?></th>
															<th><?= __('action') ?></th>
														</tr>
													</thead>
												</table>

											</div>
										</div>
										<?php  ?>
										<div class="tab-pane" id="evaluations">
											<div class="card-box">
												<h4 class="header-title m-b-30"><?= __('evaluations', 'Performance Evaluations') ?></h4>
												
												<table id="evaluations_tbl" class="table table-striped table-bordered dt-responsive nowrap" style="border-collapse: collapse; border-spacing: 0; width: 100%;">
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
																				<i class="mdi mdi-clock-outline"></i> <?= __('objected_on', 'Objected on') ?>: <?= htmlspecialchars($eval['acknowledgment_date']) ?>
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
		<script src="assets/js/businessTrip.js?t=<?= time() ?>"></script>
		<script src="assets/js/employeeTransfer.js?t=<?= time() ?>"></script>
		<script src="assets/js/salaryIncrement.js?t=<?= time() ?>"></script>
		<script src="assets/js/empVacationHandle.js"></script>

		<!-- Dropzone JS -->
		<script src="https://cdnjs.cloudflare.com/ajax/libs/dropzone/5.9.3/dropzone.min.js"></script>

		<script src="./plugins/summernote/summernote.min.js"></script>
		<!-- <script src="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-bs4.min.js"></script> -->
		<script src="assets/js/loanHandling.js?t=<?= time() ?>"></script>
		<script src="assets/js/resignationWizard.js"></script>

		<script type="text/javascript">
			// ============================================================================
			// REJOIN APPROVAL SYSTEM
			// ============================================================================
			// Handles employee rejoin requests after vacation with supervisor approval
			// Allows 3-day adjustment window if employee selected wrong date
			// ============================================================================

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
							Swal.fire({
								icon: 'warning',
								title: checkResponse.title || __('active_rejoin_request_exists', 'Active Request Exists'),
								html: `
									<div style="background-color: #e3f2fd; border-left: 4px solid #2196F3; padding: 15px; text-align: left; margin-top: 15px; border-radius: 4px;">
										<div style="margin-bottom: 10px;">
											<strong>${__('request_number', 'Request Number')}:</strong> 
											<span style="color: #d32f2f;">${checkResponse.active_request.request_inv_no}</span>
										</div>
										<div style="margin-bottom: 10px;">
											<strong>${__('status', 'Status')}:</strong> 
											<span style="color: #d32f2f; font-weight: bold;">${checkResponse.active_request.status.toUpperCase()}</span>
										</div>
										<div style="margin-bottom: 10px;">
											<strong>${__('requested_rejoin_date', 'Requested Rejoin Date')}:</strong> 
											<span>${checkResponse.active_request.requested_rejoin_date}</span>
										</div>
										<div style="margin-bottom: 10px;">
											<strong>${__('submitted_at', 'Submitted At')}:</strong> 
											<span>${checkResponse.active_request.requested_at}</span>
										</div>
										<hr style="margin: 10px 0;">
										<div style="margin-bottom: 10px;">
											<strong>${__('associated_vacation', 'Associated Vacation')}:</strong> 
											<span>${checkResponse.active_request.vacation_inv_no}</span>
										</div>
										<div>
											<strong>${__('vacation_type', 'Vacation Type')}:</strong> 
											<span>${checkResponse.active_request.vac_type}</span>
										</div>
									</div>
								`,
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
				
				Swal.fire({
					title: __('rejoin_request_title', 'Submit Rejoin Request'),
					html: `
						<div class="text-left">
							<p class="text-muted mb-3">${__('rejoin_request_subtitle', 'Your rejoin request will be sent to your direct supervisor for approval')}</p>
							<div class="form-group">
								<label for="rejoinDate" class="form-label font-weight-bold">${__('rejoin_date_label', 'Actual Rejoin Date')} <span class="text-danger">*</span></label>
								<input type="text" id="rejoinDate" class="form-control" placeholder="${__('select_date', 'Select date...')}" value="${formattedDate}">
								<small class="text-muted d-block mt-2">
									<i class="fa fa-info-circle"></i> ${__('planned_return_text', 'Planned Return')}:<br>
									<strong>${displayDate}</strong>
								</small>
							</div>
							<div class="form-group">
								<label for="rejoinReason" class="form-label font-weight-bold">${__('rejoin_reason_label', 'Reason for Date Change (if applicable)')}</label>
								<textarea id="rejoinReason" class="form-control" rows="3" placeholder="${__('optional_text', 'Optional')}"></textarea>
							</div>
						</div>
					`,
					width: '500px',
					showCancelButton: true,
					confirmButtonText: __('submit_request_button', 'Submit Request'),
					cancelButtonText: __('cancel', 'Cancel'),
					confirmButtonColor: APP_COLORS.primary,
					cancelButtonColor: APP_COLORS.danger,
					allowOutsideClick: false,
					willOpen: () => {
						var isRTL = $('html').attr('dir') === 'rtl' || $('body').attr('dir') === 'rtl';
						jQuery('#rejoinDate').datepicker({
							format: "yyyy-mm-dd",
							todayHighlight: true,
							autoclose: true,
							startDate: parsedDate, // Can't select before planned date
							orientation: isRTL ? 'bottom auto' : 'bottom auto'
						}).datepicker('setDate', parsedDate);
						
						if (isRTL) {
							$('#rejoinDate').on('show', function(e) {
								var input = $(e.target);
								var datepicker = input.data('datepicker').picker;
								var offset = input.offset();
								var inputWidth = input.outerWidth();
								var pickerWidth = datepicker.outerWidth();
								
								setTimeout(function() {
									datepicker.css({
										'left': (offset.left + inputWidth - pickerWidth) + 'px',
										'top': (offset.top + input.outerHeight()) + 'px'
									});
								}, 0);
							});
						}
					},
					preConfirm: () => {
						const rejoinDate = document.getElementById('rejoinDate').value;
						const rejoinReason = document.getElementById('rejoinReason').value;

						if (!rejoinDate) {
							Swal.showValidationMessage(__('rejoin_date_required_validation', 'Please select a rejoin date'));
							return false;
						}

						// Verify date is within valid range (not more than 3 days after planned return)
						const planned = new Date(returndate);
						const rejoin = new Date(rejoinDate);
						const maxDate = new Date(planned);
						maxDate.setDate(maxDate.getDate() + 7);

						if (rejoin > maxDate) {
							Swal.showValidationMessage(
								__('rejoin_date_range_validation', 
									'Rejoin date cannot be more than 7 days after the planned return date. ' +
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
								confirmButtonText: __('ok', 'OK'),
								allowOutsideClick: false
							}).then(() => {
								location.reload();
							});
						} else {
							Swal.fire({
								title: response.title || __('error_title', 'Error'),
								text: response.message || __('unexpected_error_text', 'An unexpected error occurred'),
								icon: 'error',
								confirmButtonText: __('ok', 'OK')
							});
						}
					},
					error: function(jqXHR, textStatus, errorThrown) {
						Swal.fire({
							title: __('error_title', 'Error'),
							text: __('request_failed_text', 'Request failed') + ': ' + textStatus,
							icon: 'error',
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

			$(function() {
				var currentDocPath = '';

				// Delegated click for dynamic items
				$(document).on('click', '.doc-list-item', function() {
					$('.doc-list-item').removeClass('active');
					$(this).addClass('active');

					var docPath = $(this).data('doc-path');
					var docExt = $(this).data('doc-ext');
					var docName = $(this).data('doc-name');
					currentDocPath = docPath;

					// Show viewer
					$('.document-viewer-container').show();

					// Adjust columns: list -> col-md-5, viewer -> col-md-7
					var $listCol = $('.documents-list-container').closest('.col-12, .col-md-5');
					$listCol.removeClass('col-12').addClass('col-md-5');
					var $viewerCol = $('.document-viewer-container').closest('.col-md-7, .col-12');
					$viewerCol.removeClass('col-12').addClass('col-md-7');

					// Update title and download
					$('#viewer-doc-name').text(docName);
					$('#viewer-download').show().off('click').on('click', function() {
						window.location.href = './downloadFile.php?file=' + docPath;
					});

					var viewer = $('#document-viewer');
					if (docExt === 'pdf') {
						viewer.html('<embed src="' + docPath + '" type="application/pdf" width="100%" height="100%">');
					} else if (['jpg', 'jpeg', 'png', 'gif'].includes(docExt)) {
						viewer.html('<img src="' + docPath + '" alt="' + docName + '" style="max-width: 100%; height: auto; display: block; margin: 0 auto;">');
					} else if (['doc', 'docx', 'xls', 'xlsx'].includes(docExt)) {
						viewer.html('<iframe src="https://view.officeapps.live.com/op/embed.aspx?src=' + encodeURIComponent(window.location.origin + '/' + docPath) + '" width="100%" height="100%" frameborder="0"></iframe>');
					} else if (docExt === 'txt') {
						$.get(docPath, function(data) {
							viewer.html('<pre style="padding: 20px; white-space: pre-wrap; word-wrap: break-word;">' + $('<div>').text(data).html() + '</pre>');
						});
					} else {
						viewer.html('<div class="viewer-placeholder"><i class="fa fa-file" style="font-size: 64px; color: #ddd;"></i><p class="text-muted mt-3"><?= __('preview_not_available_for_file_type', 'Preview not available for this file type') ?></p><a href="' + docPath + '" download class="btn btn-primary mt-3"><i class="fa fa-download"></i> <?= __('download_file', 'Download File') ?></a></div>');
					}
				});

				// Fullscreen toggle
				$('#viewer-fullscreen').on('click', function() {
					var container = $('.document-viewer-container')[0];
					if (container.requestFullscreen) container.requestFullscreen();
					else if (container.webkitRequestFullscreen) container.webkitRequestFullscreen();
					else if (container.msRequestFullscreen) container.msRequestFullscreen();
				});

				// Clear selection and hide viewer
				$('#viewer-clear').on('click', function() {
					$('.doc-list-item').removeClass('active');
					$('#viewer-download').hide();
					$('#viewer-doc-name').text('<?= __('select_document') ?>');
					$('#document-viewer').html(`
						<div class="viewer-placeholder">
							<i class="fa fa-file-text" style="font-size: 64px; color: #ddd;"></i>
							<p class="text-muted mt-3"><?= __('select_document_to_view') ?></p>
						</div>
					`);
					$('.document-viewer-container').hide();
					var $listCol = $('.documents-list-container').closest('.col-md-5, .col-12');
					$listCol.removeClass('col-md-5').addClass('col-12');
					var $viewerCol = $('.document-viewer-container').closest('.col-md-7, .col-12');
					$viewerCol.removeClass('col-md-7').addClass('col-12');
				});
			});
		</script>

		<script type="text/javascript">
			$(document).ready(function() {

				var moreActionsHtml = <?= json_encode($moreActionsHtml); ?>;
				$('#moreActionsBtn').click(function() {
					Swal.fire({
						title: '<?= __('more_actions') ?>',
						html: '<div class="menu-items-container">' + moreActionsHtml + '</div>',
						showConfirmButton: false,
						showCloseButton: true,
						customClass: {
							container: 'more-actions-modal',
							popup: 'swal2-popup',
							closeButton: 'swal2-close'
						},
						width: '450px',
						padding: '0',
						allowOutsideClick: false,
						didOpen: function() {
							// Get modal container and wrap it with jQuery
							var modalContainer = $(Swal.getHtmlContainer());

							// Add Documents
							modalContainer.find('.addEmpDocuAtter').on('click', function(e) {
								e.preventDefault();
								var eid = $(this).data('id');
								Swal.close();
								// Trigger on page element by finding it outside modal
								setTimeout(function() {
									$('.addEmpDocuAtter[data-id="' + eid + '"]').not('.swal2-html-container *').first().trigger('click');
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
							next: __('next'),
							previous: __('previous')
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
						willOpen: function() {
							var isRTL = $('html').attr('dir') === 'rtl' || $('body').attr('dir') === 'rtl';
							$('#view-emp-return-date-input').datepicker({
								format: "yyyy-mm-dd",
								todayHighlight: true,
								autoclose: true,
								orientation: isRTL ? 'bottom auto' : 'bottom auto'
							});
							
							if (isRTL) {
								$('#view-emp-return-date-input').on('show', function(e) {
									var input = $(e.target);
									var datepicker = input.data('datepicker').picker;
									var offset = input.offset();
									var inputWidth = input.outerWidth();
									var pickerWidth = datepicker.outerWidth();
									
									setTimeout(function() {
										datepicker.css({
											'left': (offset.left + inputWidth - pickerWidth) + 'px',
											'top': (offset.top + input.outerHeight()) + 'px'
										});
									}, 0);
								});
							}
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
							next: __('next'),
							previous: __('previous')
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
							next: __('next'),
							previous: __('previous')
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
							next: __('next'),
							previous: __('previous')
						},
						emptyTable: __('no_data_available_in_table'),
						zeroRecords: __('no_matching_records_found'),
						processing: `<div class="spinner-border text-primary" role="status"><span class="visually-hidden">${__('loading')}...</span></div>`
					}
				});
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
							next: __('next'),
							previous: __('previous')
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
								next: __('next'),
								previous: __('previous')
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
								next: __('next'),
								previous: __('previous')
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
							next: __('next'),
							previous: __('previous')
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
						$('#directRejoinDate').datepicker({
							format: "yyyy-mm-dd",
							todayHighlight: true,
							autoclose: true
						}).datepicker('setDate', new Date());
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

		<!-- Document Viewer JavaScript -->
		<script>
			$(document).ready(function() {
				let currentDocPath = '';

				// Handle document item click
				$('.doc-list-item').on('click', function() {
					$('.doc-list-item').removeClass('active');
					$(this).addClass('active');

					const docPath = $(this).data('doc-path');
					const docExt = $(this).data('doc-ext');
					const docName = $(this).data('doc-name');

					currentDocPath = docPath;

					$('#viewer-doc-name').text(docName);
					$('#viewer-download').attr('onclick', "window.location.href='./downloadFile.php?file=" + docPath + "'").show();

					loadDocument(docPath, docExt);
				});

				// Load document into viewer
				function loadDocument(path, ext) {
					const viewer = $('#document-viewer');

					if (ext === 'pdf') {
						viewer.html('<embed src="' + path + '" type="application/pdf" />');
					} else if (['jpg', 'jpeg', 'png', 'gif'].includes(ext)) {
						viewer.html('<img src="' + path + '" alt="<?= __('document', 'Document') ?>" />');
					} else if (['doc', 'docx', 'xls', 'xlsx'].includes(ext)) {
						viewer.html('<iframe src="https://view.officeapps.live.com/op/embed.aspx?src=' + encodeURIComponent(window.location.origin + '/' + path) + '"></iframe>');
					} else if (ext === 'txt') {
						$.get(path, function(data) {
							viewer.html('<div style="padding: 20px; background: #fff; height: 100%; overflow: auto;"><pre style="white-space: pre-wrap; font-family: monospace;">' + data + '</pre></div>');
						});
					} else {
						viewer.html('<div class="viewer-placeholder"><i class="fa fa-file" style="font-size: 64px; color: #ddd;"></i><p class="text-muted mt-3"><?= __('preview_not_available_for_file_type', 'Preview not available for this file type') ?></p><button class="btn btn-primary mt-2" onclick="window.location.href=\'./downloadFile.php?file=' + path + '\'"><i class="fa fa-download"></i> <?= __('download_file', 'Download File') ?></button></div>');
					}
				}

				// Fullscreen toggle
				$('#viewer-fullscreen').on('click', function() {
					const container = $('.document-viewer-container')[0];
					const icon = $(this).find('i');

					if (!document.fullscreenElement) {
						container.requestFullscreen().then(() => {
							icon.removeClass('fa-expand').addClass('fa-compress');
						});
					} else {
						document.exitFullscreen().then(() => {
							icon.removeClass('fa-compress').addClass('fa-expand');
						});
					}
				});

				// Exit fullscreen handler
				document.addEventListener('fullscreenchange', function() {
					const icon = $('#viewer-fullscreen i');
					if (!document.fullscreenElement) {
						icon.removeClass('fa-compress').addClass('fa-expand');
					}
				});

				// Do not auto-select any document; show viewer only after click
			});
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
											? `<div class="alert alert-warning" style="border-left: 4px solid #ffc107;"><i class="mdi mdi-clock-outline"></i> <strong><?= __('status') ?>:</strong> <?= __('pending_acknowledgments') ?></div>`
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
			// calendar (bootstrap-datepicker's `inline: true`) instead of a dropdown, since a
			// dropdown calendar gets clipped by the parent Swal's own scroll container.
			function openEngCouncilExpiryPicker(empId, snapshot) {
				Swal.fire({
					title: '<?= __('select_expiry_date_placeholder') ?>',
					html: `<input type="text" id="aiEngCouncilExpiryInline" class="form-control text-center" placeholder="YYYY-MM-DD" autocomplete="off" readonly>`,
					width: '25%',
					showCancelButton: true,
					confirmButtonText: '<?= __('select', 'Select') ?>',
					cancelButtonText: '<?= __('cancel') ?>',
					allowOutsideClick: false,
					didOpen: () => {
						const $inline = $('#aiEngCouncilExpiryInline');
						$inline.datepicker({
							format: 'yyyy-mm-dd',
							todayHighlight: true,
							autoclose: true,
							orientation: 'bottom auto'
						});
						if (snapshot.eng_council_expiry) {
							$inline.datepicker('setDate', snapshot.eng_council_expiry);
						}
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
						$('#miExpiry').datepicker({
							format: 'yyyy-mm-dd',
							todayHighlight: true,
							autoclose: true,
							orientation: 'bottom auto'
						});
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

	</body>

	</html>
<?php } ?>
