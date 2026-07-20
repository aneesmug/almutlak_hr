<?php

// =================================================================================
// MENU LINKS DEFINITIONS
// =================================================================================

$dashboardLink = 'dashboard.php';
$dashboardGMLink = 'dashboardgm.php';
$addNewEmployeeLink = 'add_new_employee.php';
$allEmployeesLink = 'reg_employee.php';
$tempContractsLink = 'emp_temp_contant.php';
$yearlyEOSLink = 'employee_audit_gen.php';
$payrollLink = 'generate_payroll.php';
$appliedVacationsLink = 'all_applied_vac.php';
$appliedBusinessTripLink = 'all_applied_business_trip.php';
$appliedLoanLink = 'all_applied_loan.php';
$settlementsLink = 'all_settlements.php';
$payrollApprovalsLink = 'all_payroll_approvals.php';
$rejoinApprovalsLink = 'rejoin_approvals.php';
$allResignationsLink = 'all_resignations.php';
$carsLink = 'all_cars.php';
$locationsLink = 'all_locations.php';
$machinesLink = 'all_machines.php';
$itemsLink = 'all_menu_item.php';
$assetInventoryLink = 'asset_inventory.php';
$ordersLink = 'all_orders.php';
$customersLink = 'odr_customers.php';
$quotationsLink = 'all_quotations.php';
$allCustomersLink = 'all_customers.php';
$customerSurveyLink = 'customers_survey.php';
$smartRequestsLink = 'all_requests.php';
$generalRequestsLink = 'all_general_requests.php';
$announcementLink = 'send_announcement.php';
$vouchersLink = 'vouchers.php';
$invoicesLink = 'all_user_invoices.php';
$usersLink = 'all_users.php';
$fileManagerLink = 'file_manager.php';
$galleryLink = 'gallery.php';
$languageLink = 'language.php';
// $manualVacationLink = 'manual_vacation.php';
$manualVacationLink = 'import_vacation_balance.php';
$processIqamaImportLink = 'import_iqama_exp.php';
$addManualLoanLink = 'add_manual_loan.php';
$employeeSalaryReportLink = 'employee_salary_report.php';
$employeeEvaluationLink = 'employee_evaluation.php';
$allEmployeeEvaluationsLink = 'all_employee_evaluations.php';
$userActivityLink = 'user_activity.php';
$activityLoggerLink = 'view_activity_logs.php';
$manageEmployeeSupervisorsLink = 'manage_employee_supervisors.php';
$manageHolidaysLink = 'manage_holidays.php?status=1';
$vacationBalanceHistoryLink = 'vacation_balance_history.php';
$vacationDatesEditorLink = 'vacation_dates_by_inv.php';
$diagnoseDoubleDeductionLink = 'diagnose_double_deduction.php';
$fixDoubleDeductionLink = 'fix_double_deduction.php';
$appSettingsLink = 'app_settings.php';
$tableJsonApiLink = 'table_json_api.php';
$loanRejectionReport = 'loan_rejection_report.php';


// =================================================================================
// PAGE ACCESS CONTROL
// =================================================================================

$page_roles = [
    'dashboard.php' => ['Administrator', 'GM', 'HR_Senior_BP', 'HR_Operations', 'HR_Supervisor', 'HR_Recruitment', 'HR_Payroll', 'Finance_Officer', 'Auditor', 'GR_Officer', 'DPT_Manager', 'IT_Team', 'IT_Team_Manager', 'HR_Team', 'HR_Team_Manager', 'Finance_Team', 'Finance_Team_Manager', 'Executive_Team', 'Executive_Team_Manager', 'Employee', 'HR_Manager', 'Finance_Manager'],
    'dashboardgm.php' => ['GM'],
    'add_new_employee.php' => ['Administrator', 'HR_Operations', 'HR_Recruitment', 'HR_Payroll', 'HR_Senior_BP'],
    'reg_employee.php' => ['Administrator', 'HR_Senior_BP', 'HR_Operations', 'HR_Supervisor', 'HR_Recruitment', 'HR_Payroll', 'Finance_Officer', 'Auditor', 'DPT_Manager', 'IT_Team', 'IT_Team_Manager', 'HR_Team', 'HR_Team_Manager', 'Finance_Team', 'Finance_Team_Manager', 'HR_Manager', 'Finance_Manager'],
    'emp_temp_contant.php' => ['Administrator', 'HR_Senior_BP', 'HR_Operations', 'HR_Supervisor', 'HR_Recruitment', 'HR_Payroll', 'HR_Team', 'HR_Team_Manager', 'HR_Manager'],
    'employee_audit_gen.php' => ['Administrator'],
    'employee_salary_report.php' => ['Administrator'],
    'generate_payroll.php' => ['Administrator', 'HR_Senior_BP', 'HR_Payroll',  'HR_Manager'],
    'all_applied_vac.php' => ['Administrator', 'GM', 'HR_Senior_BP', 'HR_Operations', 'HR_Supervisor', 'HR_Recruitment', 'HR_Payroll', 'Finance_Officer', 'Auditor', 'GR_Officer', 'DPT_Manager', 'IT_Team', 'IT_Team_Manager', 'HR_Team', 'HR_Team_Manager', 'Finance_Team', 'Finance_Team_Manager', 'Employee', 'HR_Manager', 'Finance_Manager'],
    'all_applied_business_trip.php' => ['Administrator', 'GM', 'HR_Senior_BP', 'HR_Operations', 'HR_Supervisor', 'HR_Recruitment', 'HR_Payroll', 'Finance_Officer', 'Auditor', 'GR_Officer', 'DPT_Manager', 'IT_Team', 'IT_Team_Manager', 'HR_Team', 'HR_Team_Manager', 'Finance_Team', 'Finance_Team_Manager', 'HR_Manager', 'Finance_Manager'],
    'all_applied_loan.php' => ['Administrator', 'GM', 'HR_Senior_BP', 'HR_Operations', 'HR_Supervisor', 'HR_Recruitment', 'HR_Payroll', 'Finance_Officer', 'Auditor', 'DPT_Manager', 'HR_Team', 'HR_Team_Manager', 'Finance_Team', 'Finance_Team_Manager', 'Employee', 'HR_Manager', 'Finance_Manager','IT_Team_Manager'],
    'all_settlements.php' => ['Administrator', 'GM', 'HR_Senior_BP', 'HR_Operations', 'HR_Supervisor', 'HR_Recruitment', 'HR_Payroll', 'Finance_Officer', 'Auditor', 'DPT_Manager', 'HR_Team', 'HR_Team_Manager', 'Finance_Team', 'Finance_Team_Manager', 'HR_Manager', 'Finance_Manager'],
    'all_payroll_approvals.php' => ['Administrator', 'GM', 'HR_Senior_BP', 'HR_Operations', 'HR_Supervisor', 'HR_Recruitment', 'HR_Payroll', 'Finance_Officer', 'Auditor', 'DPT_Manager', 'HR_Team', 'HR_Team_Manager', 'Finance_Team', 'Finance_Team_Manager', 'HR_Manager', 'Finance_Manager'],
    'rejoin_approvals.php' => ['Administrator', 'GM', 'HR_Senior_BP', 'HR_Operations', 'HR_Supervisor', 'HR_Recruitment', 'HR_Payroll', 'Finance_Officer', 'Auditor', 'GR_Officer', 'DPT_Manager', 'IT_Team', 'IT_Team_Manager', 'HR_Team', 'HR_Team_Manager', 'Finance_Team', 'Finance_Team_Manager', 'HR_Manager', 'Finance_Manager'],
    'all_resignations.php' => ['Administrator', 'GM', 'HR_Senior_BP', 'HR_Operations', 'HR_Supervisor', 'HR_Recruitment', 'HR_Payroll', 'DPT_Manager', 'IT_Team_Manager', 'HR_Team', 'HR_Team_Manager', 'HR_Manager'],
    'add_manual_loan.php' => ['Administrator', 'HR_Senior_BP', 'HR_Payroll', 'Finance_Officer', 'Auditor', 'HR_Team', 'HR_Team_Manager', 'Finance_Team', 'Finance_Team_Manager', 'HR_Manager', 'Finance_Manager'],
    'all_cars.php' => ['Administrator', 'GR_Officer'],
    'all_locations.php' => ['Administrator', 'GR_Officer'],
    'all_machines.php' => ['Administrator', 'GR_Officer'],
    'asset_inventory.php' => ['Administrator', 'IT_Team', 'IT_Team_Manager', 'GR_Officer'],
    'all_menu_item.php' => ['Administrator'],
    'all_requests.php' => ['Administrator', 'GM', 'HR_Senior_BP', 'HR_Operations', 'HR_Supervisor', 'HR_Recruitment', 'HR_Payroll', 'Finance_Officer', 'Auditor', 'GR_Officer', 'DPT_Manager', 'IT_Team', 'IT_Team_Manager', 'HR_Team', 'HR_Team_Manager', 'Finance_Team', 'Finance_Team_Manager', 'HR_Manager', 'Finance_Manager'],
    // 'all_general_requests.php' => ['Administrator', 'GM', 'HR_Senior_BP', 'HR_Operations', 'HR_Supervisor', 'HR_Recruitment', 'HR_Payroll', 'Finance_Officer', 'Auditor', 'GR_Officer', 'DPT_Manager', 'IT_Team', 'IT_Team_Manager', 'HR_Team', 'HR_Team_Manager', 'Finance_Team', 'Finance_Team_Manager', 'HR_Manager', 'Finance_Manager'],
    'all_general_requests.php' => ['Administrator'],
    'send_announcement.php' => ['Administrator'],
    'vouchers.php' => ['Administrator', 'HR_Senior_BP', 'HR_Payroll', 'Finance_Officer', 'Auditor', 'HR_Team', 'HR_Team_Manager', 'Finance_Team', 'Finance_Team_Manager', 'HR_Manager', 'Finance_Manager'],
    'all_user_invoices.php' => ['Administrator', 'HR_Senior_BP', 'HR_Operations', 'HR_Supervisor', 'HR_Recruitment', 'HR_Payroll', 'Finance_Officer', 'Auditor', 'DPT_Manager', 'HR_Team', 'HR_Team_Manager', 'Finance_Team', 'Finance_Team_Manager', 'Employee', 'HR_Manager', 'Finance_Manager'],
    'all_users.php' => ['Administrator'],
    'file_manager.php' => ['Administrator'],
    'gallery.php' => ['Administrator'],
    'language.php' => ['Administrator'],
    'log_activity.php' => ['Administrator'],
    'view_activity_logs.php' => ['Administrator'],
    'manual_vacation.php' => ['Administrator', 'HR_Senior_BP', 'HR_Operations', 'HR_Supervisor', 'HR_Recruitment', 'HR_Payroll', 'IT_Team', 'IT_Team_Manager', 'HR_Team', 'HR_Team_Manager', 'HR_Manager'],
    'import_iqama_exp.php' => ['Administrator', 'HR_Operations', 'HR_Payroll'],
    'employee_evaluation.php' => ['Administrator', 'GM', 'HR_Senior_BP', 'HR_Operations', 'HR_Supervisor', 'HR_Recruitment', 'HR_Payroll', 'DPT_Manager', 'HR_Team', 'HR_Team_Manager', 'HR_Manager', 'IT_Team_Manager'],
    'all_employee_evaluations.php' => ['Administrator', 'GM', 'HR_Senior_BP', 'HR_Operations', 'HR_Supervisor', 'HR_Recruitment', 'HR_Payroll', 'DPT_Manager', 'HR_Team', 'HR_Team_Manager', 'HR_Manager'],
    'reports.php' => ['Administrator', 'GM', 'Auditor', 'HR_Senior_BP', 'HR_Payroll', 'HR_Operations', 'HR_Supervisor', 'Finance_Officer', 'DPT_Manager', 'HR_Manager', 'Finance_Manager','HR_Recruitment','IT_Team_Manager'],
    'manage_employee_supervisors.php' => ['Administrator', 'HR_Senior_BP'],
    'manage_holidays.php' => ['Administrator', 'HR_Senior_BP', 'HR_Team', 'HR_Team_Manager', 'HR_Manager'],
    'vacation_dates_by_inv.php' => ['Administrator', 'HR_Senior_BP'],
    'vacation_balance_history.php' => ['Administrator'],
    'diagnose_double_deduction.php' => ['Administrator'],
    'fix_double_deduction.php' => ['Administrator'],
    'app_settings.php' => ['Administrator'],
];

$current_page_name = basename($_SERVER['PHP_SELF']);

$is_employee_user_type = (strtolower((string)$user_type) === 'employee');

require_once __DIR__ . '/special_access_helper.php';

// Sidebar navigation entries hidden entirely when their request type is globally blocked
// (or individually blocked for the current logged-in user).
$isSmartRequestMenuBlocked = is_employee_request_blocked($conDB, $empid ?? '', 'smart_request')['blocked'];
$isGeneralRequestMenuBlocked = is_employee_request_blocked($conDB, $empid ?? '', 'general_request')['blocked'];

// Pages whose page_roles restriction can be bypassed by an explicit special-access grant.
// Value can be a single access key, or an array of keys where any one grants access.
$page_special_access_bypass = [
    'vacation_balance_history.php' => 'view_vacation_balance_history',
    'app_settings.php' => ['manage_department_settings', 'manage_job_title_settings', 'manage_global_request_blocks'],
];

$has_page_special_access_bypass = false;
if (isset($page_special_access_bypass[$current_page_name])) {
    $bypassKeys = (array)$page_special_access_bypass[$current_page_name];
    foreach ($bypassKeys as $bypassKey) {
        if (user_has_special_access($conDB, $empid ?? '', $bypassKey, $user_role ?? '', $user_type ?? '', $is_system_admin ?? false)) {
            $has_page_special_access_bypass = true;
            break;
        }
    }
}

if ($user_type != 'administrator') {
    // Allow reports page for all non-employee user types.
    if ($current_page_name === 'reports.php' && !$is_employee_user_type) {
        // intentionally bypass role-based restriction
    } elseif ($has_page_special_access_bypass) {
        // intentionally bypass role-based restriction: explicit special-access grant
    } elseif (isset($page_roles[$current_page_name])) {
        if (!in_array($user_role, $page_roles[$current_page_name])) {
            header("Location: dashboard.php");
            exit();
        }
    }
}

// --- Role lists for menu visibility ---
// Updated to include all new specific roles and department-based team roles
$can_see_employees_group_main = [
    'Administrator', 'HR_Senior_BP', 'HR_Operations', 'HR_Supervisor', 'HR_Recruitment', 'HR_Payroll',
    'Finance_Officer', 'Auditor', 'HR_Team', 'HR_Team_Manager', 'Finance_Team', 'Finance_Team_Manager',
    'HR_Manager', 'Finance_Manager'
];

$can_see_all_employees_page = [
    'Administrator', 'HR_Senior_BP', 'HR_Operations', 'HR_Supervisor', 'HR_Recruitment', 'HR_Payroll',
    'Finance_Officer', 'Auditor', 'DPT_Manager', 'IT_Team', 'IT_Team_Manager',
    'HR_Team', 'HR_Team_Manager', 'Finance_Team', 'Finance_Team_Manager',
    'HR_Manager', 'Finance_Manager'
];

$can_see_employees_bank_page = [
    'Administrator', 'HR_Senior_BP', 'HR_Operations', 'HR_Supervisor', 'HR_Recruitment', 'HR_Payroll',
    'Finance_Officer', 'Auditor',
    'HR_Team', 'HR_Team_Manager', 'Finance_Team', 'Finance_Team_Manager',
    'HR_Manager', 'Finance_Manager'
];

$can_see_new_employee_page = [
    'Administrator', 'HR_Senior_BP', 'HR_Operations', 'HR_Recruitment', 'HR_Payroll'
];

$can_see_employees_payroll_page = [
    'Administrator', 'HR_Senior_BP', 'HR_Payroll', 'HR_Manager'
];

$can_see_import_iqama_page = [
    'Administrator', 'HR_Operations', 'HR_Payroll'
];

$can_see_applied_vac_page = [
    // Core leadership & HR
    'Administrator', 'GM', 'HR_Senior_BP', 'HR_Operations', 'HR_Supervisor', 'HR_Recruitment', 'HR_Payroll', 'HR_Manager', 'HR_Team', 'HR_Team_Manager',
    // Department & functional approvers
    'DPT_Manager', 'IT_Team', 'IT_Team_Manager', 'Finance_Team', 'Finance_Team_Manager', 'Finance_Officer', 'Finance_Manager',
    // Asset / compliance related
    'GR_Officer', 'Auditor'
];

$can_see_business_trip_page = [
    'Administrator', 'GM', 'HR_Senior_BP', 'HR_Operations', 'HR_Supervisor', 'HR_Recruitment', 'HR_Payroll', 'HR_Manager', 'HR_Team', 'HR_Team_Manager',
    'DPT_Manager', 'IT_Team', 'IT_Team_Manager', 'Finance_Team', 'Finance_Team_Manager', 'Finance_Officer', 'Finance_Manager',
    'GR_Officer', 'Auditor'
];

$can_see_rejoin_approvals_page = [
    'Administrator', 'GM', 'HR_Senior_BP', 'HR_Operations', 'HR_Supervisor', 'HR_Recruitment', 'HR_Payroll',
    'Finance_Officer', 'Auditor', 'GR_Officer', 'DPT_Manager', 'IT_Team', 'IT_Team_Manager',
    'HR_Team', 'HR_Team_Manager', 'Finance_Team', 'Finance_Team_Manager', 'HR_Manager', 'Finance_Manager'
];

$can_see_loan_approvals_page = [
    'Administrator', 'GM', 'HR_Senior_BP', 'HR_Operations', 'HR_Supervisor', 'HR_Recruitment', 'HR_Payroll',
    'Finance_Officer', 'Auditor', 'DPT_Manager',
    'HR_Team', 'HR_Team_Manager', 'Finance_Team', 'Finance_Team_Manager',
    'HR_Manager', 'Finance_Manager','IT_Team_Manager'
];

$can_see_settlements_page = [
    'Administrator', 'GM', 'HR_Senior_BP', 'HR_Operations', 'HR_Supervisor', 'HR_Recruitment', 'HR_Payroll',
    'Finance_Officer', 'Auditor', 'DPT_Manager',
    'HR_Team', 'HR_Team_Manager', 'Finance_Team', 'Finance_Team_Manager',
    'HR_Manager', 'Finance_Manager'
];

$can_see_payroll_approvals_page = [
    'Administrator', 'GM', 'HR_Senior_BP', 'HR_Operations', 'HR_Supervisor', 'HR_Recruitment', 'HR_Payroll',
    'Finance_Officer', 'Auditor', 'DPT_Manager',
    'HR_Team', 'HR_Team_Manager', 'Finance_Team', 'Finance_Team_Manager',
    'HR_Manager', 'Finance_Manager'
];

$can_see_resignations_page = [
    'Administrator', 'GM', 'HR_Senior_BP', 'HR_Operations', 'HR_Supervisor', 'HR_Payroll','HR_Recruitment',
    'DPT_Manager', 'IT_Team_Manager',
    'HR_Team', 'HR_Team_Manager',
    'HR_Manager'
];

$can_see_content_approvals_page = [
    'Administrator', 'HR_Senior_BP', 'HR_Operations', 'HR_Supervisor', 'HR_Recruitment', 'HR_Payroll',
    'HR_Team', 'HR_Team_Manager',
    'HR_Manager'
];

$can_see_smart_requests_page = [
    'Administrator', 'GM', 'HR_Senior_BP', 'HR_Operations', 'HR_Supervisor', 'HR_Recruitment', 'HR_Payroll',
    'Finance_Officer', 'Auditor', 'GR_Officer', 'DPT_Manager', 'IT_Team', 'IT_Team_Manager',
    'HR_Team', 'HR_Team_Manager', 'Finance_Team', 'Finance_Team_Manager',
    'HR_Manager', 'Finance_Manager'
];

$can_see_general_requests_page = ['Administrator'];
$can_see_announcement_page = ['Administrator'];
// $can_see_general_requests_page = [
//     'Administrator', 'GM', 'HR_Senior_BP', 'HR_Operations', 'HR_Supervisor', 'HR_Recruitment', 'HR_Payroll',
//     'Finance_Officer', 'Auditor', 'GR_Officer', 'DPT_Manager', 'IT_Team', 'IT_Team_Manager',
//     'HR_Team', 'HR_Team_Manager', 'Finance_Team', 'Finance_Team_Manager',
//     'HR_Manager', 'Finance_Manager'
// ];

$can_see_vouchers_page = [
    'Administrator', 'HR_Senior_BP', 'HR_Operations', 'HR_Supervisor', 'HR_Recruitment', 'HR_Payroll',
    'Finance_Officer', 'Auditor',
    'HR_Team', 'HR_Team_Manager', 'Finance_Team', 'Finance_Team_Manager',
    'HR_Manager', 'Finance_Manager'
];

$can_see_employee_evaluation_page = [
    'Administrator', 'GM', 'DPT_Manager', 'HR_Manager', 'Finance_Manager',
    // 'Administrator'
];

$can_see_evaluations_report_strict = [
    'Administrator', 'GM', 'HR_Recruitment'
    // 'Administrator'
];

$can_see_all_evaluations_report = [
    'Administrator', 'GM','HR_Manager', 'HR_Recruitment'
    // 'Administrator'
];

$can_see_reports_page = [ 
    'Administrator', 'HR_Senior_BP', 'HR_Operations', 'HR_Supervisor', 'HR_Recruitment', 'HR_Payroll',
    'Finance_Officer', 'Auditor',
    'HR_Team', 'HR_Team_Manager', 'Finance_Team', 'Finance_Team_Manager',
    'HR_Manager', 'Finance_Manager','GM','IT_Team_Manager'
];

$can_see_asstet_inventory_page = [
    'Administrator', 'IT_Team', 'IT_Team_Manager', 'GR_Officer'
];

$can_see_cars_management_page = [
    'Administrator', 'GR_Officer'
];

$is_admin = $is_system_admin; 
$is_gm = ($user_role == 'GM' || $isGM);

// GM should see queue counts assigned directly to GM, not department-aggregated counts.
$use_dept_scoped_pending_counts = (
    $isDeptManager
    || in_array($user_role, ['DPT_Manager', 'IT_Team_Manager', 'HR_Team_Manager', 'Finance_Team_Manager'])
    || (($user_type === 'hr') && $emp_type === 'Manager')
) && !$is_gm;

$show_employees_menu = !empty(array_intersect([$user_role, $user_type], $can_see_employees_group_main)) ||
                       !empty(array_intersect([$user_role, $user_type], $can_see_all_employees_page)) ||
                       !empty(array_intersect([$user_role, $user_type], $can_see_employees_bank_page));

$show_approvals_menu = !empty(array_intersect([$user_role, $user_type], $can_see_applied_vac_page)) ||
                       !empty(array_intersect([$user_role, $user_type], $can_see_business_trip_page)) ||
                       !empty(array_intersect([$user_role, $user_type], $can_see_loan_approvals_page)) ||
                       !empty(array_intersect([$user_role, $user_type], $can_see_settlements_page)) ||
                       !empty(array_intersect([$user_role, $user_type], $can_see_payroll_approvals_page)) ||
                       !empty(array_intersect([$user_role, $user_type], $can_see_resignations_page)) ||
                       !empty(array_intersect([$user_role, $user_type], $can_see_rejoin_approvals_page)) ||
                       !empty(array_intersect([$user_role, $user_type], $can_see_content_approvals_page));

$can_see_reports_menu = !$is_employee_user_type;


// =================================================================================
// DATA FETCHING FOR BADGES
// =================================================================================

// --- Fetch Loan Approval Counts (NEW SYSTEM) ---
$loan_pending_count = 0;
$loan_type_id = 0;
$loan_type_query = mysqli_query($conDB, "SELECT id FROM approval_request_types WHERE type_name = 'loan_request' LIMIT 1");
if ($row = mysqli_fetch_assoc($loan_type_query)) {
    $loan_type_id = (int)$row['id'];
}
if ($loan_type_id > 0) {
    if ($is_system_admin || $user_role == 'Administrator') {
    // Admin: count all distinct loan requests still pending anywhere
                $loan_pending_query_admin = "SELECT COUNT(DISTINCT ra.request_inv_no) AS count
                                                                         FROM request_approvers ra
                                                                         JOIN emp_loan l ON l.inv_no = ra.request_inv_no
                                                                         WHERE ra.status = 'pending' 
                                                                             AND ra.request_type_id = $loan_type_id
                                                                             AND l.status NOT IN ('approved','completed','rejected','paid')";
        $res_loan_admin = mysqli_query($conDB, $loan_pending_query_admin);
        if ($res_loan_admin && ($rla = mysqli_fetch_assoc($res_loan_admin))) {
            $loan_pending_count = (int)$rla['count'];
        }
    } elseif ($use_dept_scoped_pending_counts) {
        // Department Manager: count all loan requests from their department employees
        // Also applies to GM and HR Managers
                $loan_pending_query_dept = "SELECT COUNT(DISTINCT l.inv_no) AS count
                                                                     FROM emp_loan l
                                                                     JOIN employees e ON l.emp_id = e.emp_id
                                                                     WHERE e.dept = '" . mysqli_real_escape_string($conDB, $user_dept) . "'
                                                                         AND l.status NOT IN ('approved','completed','rejected','paid')";
        $res_loan_dept = mysqli_query($conDB, $loan_pending_query_dept);
        if ($res_loan_dept && ($rld = mysqli_fetch_assoc($res_loan_dept))) {
            $loan_pending_count = (int)$rld['count'];
        }
    } else {
        // Regular user: count requests awaiting THIS user's approval
                $loan_pending_query = "SELECT COUNT(DISTINCT ra.request_inv_no) AS count
                                                            FROM request_approvers ra
                                                            JOIN emp_loan l ON l.inv_no = ra.request_inv_no
                                                            WHERE ra.approver_id = " . (int)$empid . "
                                                                AND ra.status = 'pending'
                                                                AND ra.request_type_id = $loan_type_id
                                                                AND l.status NOT IN ('approved','completed','rejected','paid')";
        $res_loan = mysqli_query($conDB, $loan_pending_query);
        if ($res_loan && ($rl = mysqli_fetch_assoc($res_loan))) {
            $loan_pending_count = (int)$rl['count'];
        }
    }
}
// --- END NEW LOAN PENDING COUNT ---

// --- Fetch Smart Request Counts (NEW GENERAL SYSTEM) ---
$smart_request_count = 0;
if ($is_system_admin || $user_role == 'Administrator') {
    // Admin sees a count of ALL pending requests (excluding drafts)
    $smart_request_query_admin = "SELECT COUNT(*) as count FROM smart_request WHERE current_status NOT IN ('approved', 'rejected', 'paid', 'draft')";
    $result_admin = mysqli_query($conDB, $smart_request_query_admin);
    if ($row_admin = mysqli_fetch_assoc($result_admin)) {
       $smart_request_count = $row_admin['count'];
   }
} elseif ($use_dept_scoped_pending_counts) {
    // Department Manager: count requests from their department employees
    $smart_request_query_dept = "SELECT COUNT(*) as count 
                                                          FROM smart_request sr
                                                          JOIN employees e ON sr.emp_id = e.emp_id
                                                          WHERE e.dept = '" . mysqli_real_escape_string($conDB, $user_dept) . "'
                                                              AND sr.current_status NOT IN ('approved','rejected','paid','draft')";
    $result_dept = mysqli_query($conDB, $smart_request_query_dept);
    if ($row_dept = mysqli_fetch_assoc($result_dept)) {
        $smart_request_count = $row_dept['count'];
    }
} else {
    // All other users see a count of requests pending *their* approval
    $smart_request_type_id = 0;
    $smt_type_query = mysqli_query($conDB, "SELECT id FROM approval_request_types WHERE type_name = 'smart_request' LIMIT 1");
    if ($row = mysqli_fetch_assoc($smt_type_query)) {
        $smart_request_type_id = (int)$row['id'];
    }

    if ($smart_request_type_id > 0) {
                $smart_request_query = "SELECT COUNT(DISTINCT ra.request_inv_no) as count 
                                                                FROM request_approvers ra
                                                                JOIN smart_request sr ON sr.inv_no = ra.request_inv_no
                                                                WHERE ra.approver_id = " . (int)$empid . " 
                                                                    AND ra.status = 'pending' 
                                                                    AND ra.request_type_id = $smart_request_type_id
                                                                    AND sr.current_status NOT IN ('approved','rejected','paid','draft')";
        
        $result = mysqli_query($conDB, $smart_request_query);
        if ($row = mysqli_fetch_assoc($result)) {
            $smart_request_count = $row['count'];
        }
    }
}
// --- END NEW SMART REQUEST COUNT ---

// --- Fetch General Request Counts ---
$general_request_count = 0;
if ($is_system_admin || $user_role == 'Administrator') {
    // Admin sees a count of ALL pending general requests (excluding completed)
    $general_request_query_admin = "SELECT COUNT(*) as count FROM general_requests WHERE current_status NOT IN ('approved', 'rejected', 'draft', 'completed')";
    $result_admin = mysqli_query($conDB, $general_request_query_admin);
    if ($row_admin = mysqli_fetch_assoc($result_admin)) {
        $general_request_count = $row_admin['count'];
    }
} elseif ($use_dept_scoped_pending_counts) {
    // Department Manager: count requests from their department employees
    $general_request_query_dept = "SELECT COUNT(*) as count 
                                                            FROM general_requests gr
                                                            JOIN employees e ON gr.emp_id = e.emp_id
                                                            WHERE e.dept = '" . mysqli_real_escape_string($conDB, $user_dept) . "'
                                                                AND gr.current_status NOT IN ('approved','rejected','draft','completed')";
    $result_dept = mysqli_query($conDB, $general_request_query_dept);
    if ($row_dept = mysqli_fetch_assoc($result_dept)) {
        $general_request_count = $row_dept['count'];
    }
} else {
    // All other users see a count of general requests pending *their* approval
    $general_request_type_id = 0;
    $gr_type_query = mysqli_query($conDB, "SELECT id FROM approval_request_types WHERE main_table_name = 'general_requests' LIMIT 1");
    if ($row = mysqli_fetch_assoc($gr_type_query)) {
        $general_request_type_id = (int)$row['id'];
    }

    if ($general_request_type_id > 0) {
                $general_request_query = "SELECT COUNT(DISTINCT ra.request_inv_no) as count 
                                                                FROM request_approvers ra
                                                                JOIN general_requests gr ON gr.inv_no = ra.request_inv_no
                                                                WHERE ra.approver_id = " . (int)$empid . " 
                                                                    AND ra.status = 'pending' 
                                                                    AND ra.request_type_id = $general_request_type_id
                                                                    AND gr.current_status NOT IN ('approved','rejected','draft','completed')";
        
        $result = mysqli_query($conDB, $general_request_query);
        if ($row = mysqli_fetch_assoc($result)) {
            $general_request_count = $row['count'];
        }
    }
}
// --- END NEW GENERAL REQUEST COUNT ---

// --- Fetch Vacation Pending Approval Count (NEW) ---
$vacation_pending_count = 0;
$vacation_type_id = 0;
$vac_type_query = mysqli_query($conDB, "SELECT id FROM approval_request_types WHERE type_name = 'vacation_request' LIMIT 1");
if ($row = mysqli_fetch_assoc($vac_type_query)) {
    $vacation_type_id = (int)$row['id'];
}
if ($vacation_type_id > 0) {
    if ($is_system_admin || $user_role == 'Administrator') {
    // Admin: count all distinct vacation requests still pending anywhere
                $vacation_pending_query_admin = "SELECT COUNT(DISTINCT ra.request_inv_no) AS count
                                                                                 FROM request_approvers ra
                                                                                 JOIN emp_vacation v ON v.request_inv_no = ra.request_inv_no
                                                                                 WHERE ra.status = 'pending' 
                                                                                     AND ra.request_type_id = $vacation_type_id
                                                                                     AND v.current_status NOT IN ('approved','completed','rejected')";
        $res_vac_admin = mysqli_query($conDB, $vacation_pending_query_admin);
        if ($res_vac_admin && ($rva = mysqli_fetch_assoc($res_vac_admin))) {
            $vacation_pending_count = (int)$rva['count'];
        }
    } elseif ($use_dept_scoped_pending_counts) {
        // Department Manager: count all vacation requests from their department employees
        // Also applies to GM and HR Managers
                $vacation_pending_query_dept = "SELECT COUNT(DISTINCT v.request_inv_no) AS count
                                                                             FROM emp_vacation v
                                                                             JOIN employees e ON v.emp_id = e.emp_id
                                                                             WHERE e.dept = '" . mysqli_real_escape_string($conDB, $user_dept) . "'
                                                                                 AND v.current_status NOT IN ('approved','completed','rejected')";
        $res_vac_dept = mysqli_query($conDB, $vacation_pending_query_dept);
        if ($res_vac_dept && ($rvd = mysqli_fetch_assoc($res_vac_dept))) {
            $vacation_pending_count = (int)$rvd['count'];
        }
    } else {
        // Regular user: count requests awaiting THIS user's approval
                $vacation_pending_query = "SELECT COUNT(DISTINCT ra.request_inv_no) AS count
                                                                     FROM request_approvers ra
                                                                     JOIN emp_vacation v ON v.request_inv_no = ra.request_inv_no
                                                                     WHERE ra.approver_id = " . (int)$empid . "
                                                                         AND ra.status = 'pending'
                                                                         AND ra.request_type_id = $vacation_type_id
                                                                         AND v.current_status NOT IN ('approved','completed','rejected')";
        $res_vac = mysqli_query($conDB, $vacation_pending_query);
        if ($res_vac && ($rv = mysqli_fetch_assoc($res_vac))) {
            $vacation_pending_count = (int)$rv['count'];
        }
    }
}
// --- END NEW VACATION PENDING COUNT ---

// --- Fetch Rejoin Pending Approval Count (NEW) ---
$rejoin_pending_count = 0;
$rejoin_type_id = 0;
$rejoin_type_query = mysqli_query($conDB, "SELECT id FROM approval_request_types WHERE type_name = 'rejoin_request' LIMIT 1");
if ($row = mysqli_fetch_assoc($rejoin_type_query)) {
    $rejoin_type_id = (int)$row['id'];
}
if ($rejoin_type_id > 0) {
    if ($is_system_admin || $user_role == 'Administrator') {
    // Admin: count all distinct rejoin requests still pending anywhere
                $rejoin_pending_query_admin = "SELECT COUNT(DISTINCT ra.request_inv_no) AS count
                                                                                 FROM request_approvers ra
                                                                                 JOIN rejoin_requests rr ON rr.id = ra.request_inv_no
                                                                                 WHERE ra.status = 'pending'
                                                                                     AND rr.status = 'pending'
                                                                                     AND ra.request_type_id = $rejoin_type_id";
        $res_rejoin_admin = mysqli_query($conDB, $rejoin_pending_query_admin);
        if ($res_rejoin_admin && ($rra = mysqli_fetch_assoc($res_rejoin_admin))) {
            $rejoin_pending_count = (int)$rra['count'];
        }
    } elseif ($use_dept_scoped_pending_counts) {
        // Department Manager: count all rejoin requests from their department employees
        // Also applies to GM and HR Managers
                $rejoin_pending_query_dept = "SELECT COUNT(DISTINCT rr.id) AS count
                                                                           FROM rejoin_requests rr
                                                                           JOIN employees e ON rr.emp_id = e.emp_id
                                                                           WHERE e.dept = '" . mysqli_real_escape_string($conDB, $user_dept) . "'
                                                                               AND rr.status = 'pending'";
        // error_log("REJOIN DEPT QUERY: " . $rejoin_pending_query_dept . " | User Role: " . $user_role . " | Dept: " . $user_dept);
        $res_rejoin_dept = mysqli_query($conDB, $rejoin_pending_query_dept);
        if ($res_rejoin_dept && ($rrd = mysqli_fetch_assoc($res_rejoin_dept))) {
            $rejoin_pending_count = (int)$rrd['count'];
            // error_log("REJOIN DEPT COUNT: " . $rejoin_pending_count);
        }
    } else {
        // Regular user: count requests awaiting THIS user's approval
                $rejoin_pending_query = "SELECT COUNT(DISTINCT ra.request_inv_no) AS count
                                                                     FROM request_approvers ra
                                                                     JOIN rejoin_requests rr ON rr.id = ra.request_inv_no
                                                                     WHERE ra.approver_id = " . (int)$empid . "
                                                                         AND ra.status = 'pending'
                                                                         AND rr.status = 'pending'
                                                                         AND ra.request_type_id = $rejoin_type_id";
        $res_rejoin = mysqli_query($conDB, $rejoin_pending_query);
        if ($res_rejoin && ($rr = mysqli_fetch_assoc($res_rejoin))) {
            $rejoin_pending_count = (int)$rr['count'];
        }
    }
}
// --- END NEW REJOIN PENDING COUNT ---

// --- Fetch Resignation Pending Approval Count (NEW) ---
$resignation_pending_count = 0;
$resignation_type_id = 0;
$res_type_query = mysqli_query($conDB, "SELECT id FROM approval_request_types WHERE type_name = 'resignation_request' LIMIT 1");
if ($row = mysqli_fetch_assoc($res_type_query)) {
    $resignation_type_id = (int)$row['id'];
}
if ($resignation_type_id > 0) {
    if ($is_system_admin || $user_role == 'Administrator') {
        // Admin: count all distinct resignation requests still pending anywhere
                $resignation_pending_query_admin = "SELECT COUNT(DISTINCT ra.request_inv_no) AS count
                                                                                        FROM request_approvers ra
                                                                                        JOIN emp_resignations r ON r.request_inv_no = ra.request_inv_no
                                                                                        WHERE ra.status IN ('pending','awaiting')
                                                                                            AND ra.request_type_id = $resignation_type_id
                                                                                            AND r.status = 'pending'";
        $res_resig_admin = mysqli_query($conDB, $resignation_pending_query_admin);
        if ($res_resig_admin && ($rra = mysqli_fetch_assoc($res_resig_admin))) {
            $resignation_pending_count = (int)$rra['count'];
        }
    } elseif ($use_dept_scoped_pending_counts) {
        // Department Manager: count all resignation requests from their department employees
        // Also applies to GM and HR Managers
                $resignation_pending_query_dept = "SELECT COUNT(DISTINCT r.request_inv_no) AS count
                                                                                    FROM emp_resignations r
                                                                                    JOIN employees e ON r.emp_id = e.emp_id
                                                                                    WHERE e.dept = '" . mysqli_real_escape_string($conDB, $user_dept) . "'
                                                                                        AND r.status = 'pending'";
        $res_resig_dept = mysqli_query($conDB, $resignation_pending_query_dept);
        if ($res_resig_dept && ($rrd = mysqli_fetch_assoc($res_resig_dept))) {
            $resignation_pending_count = (int)$rrd['count'];
        }
    } else {
        // Regular user: count requests awaiting THIS user's approval
                $resignation_pending_query = "SELECT COUNT(DISTINCT ra.request_inv_no) AS count
                                                                            FROM request_approvers ra
                                                                            JOIN emp_resignations r ON r.request_inv_no = ra.request_inv_no
                                                                            WHERE ra.approver_id = " . (int)$empid . "
                                                                                AND ra.status IN ('pending','awaiting')
                                                                                AND ra.request_type_id = $resignation_type_id
                                                                                AND r.status = 'pending'";
        $res_resig = mysqli_query($conDB, $resignation_pending_query);
        if ($res_resig && ($rr = mysqli_fetch_assoc($res_resig))) {
            $resignation_pending_count = (int)$rr['count'];
        }
    }
}
// --- END NEW RESIGNATION PENDING COUNT ---

// --- Fetch Settlement Pending Approval Count (NEW) ---
$settlement_pending_count = 0;
$settlement_type_id = 0;
$settlement_type_query = mysqli_query($conDB, "SELECT id FROM approval_request_types WHERE type_name = 'settlement' LIMIT 1");
if ($row = mysqli_fetch_assoc($settlement_type_query)) {
    $settlement_type_id = (int)$row['id'];
}
if ($settlement_type_id > 0) {
    if ($is_system_admin || $user_role == 'Administrator') {
        // Admin: count all distinct settlement requests still pending anywhere
        $settlement_pending_query_admin = "SELECT COUNT(DISTINCT ra.request_inv_no) AS count FROM request_approvers ra WHERE ra.request_type_id = $settlement_type_id AND ra.status = 'pending'";
        $res_settlement_admin = mysqli_query($conDB, $settlement_pending_query_admin);
        if ($res_settlement_admin && ($rsa = mysqli_fetch_assoc($res_settlement_admin))) {
            $settlement_pending_count = (int)$rsa['count'];
        }
    } elseif ($use_dept_scoped_pending_counts) {
        // Department Manager: count all settlement requests from their department employees
        $settlement_pending_query_dept = "SELECT COUNT(DISTINCT ra.request_inv_no) AS count FROM request_approvers ra 
                                         JOIN settlement_records s ON s.request_inv_no = ra.request_inv_no 
                                         JOIN employees e ON e.emp_id = s.emp_id 
                                         WHERE e.dept = " . (int)$user_dept . " AND ra.request_type_id = $settlement_type_id AND ra.status = 'pending'";
        $res_settlement_dept = mysqli_query($conDB, $settlement_pending_query_dept);
        if ($res_settlement_dept && ($rsd = mysqli_fetch_assoc($res_settlement_dept))) {
            $settlement_pending_count = (int)$rsd['count'];
        }
    } else {
        // Regular user: count requests awaiting THIS user's approval
        $settlement_pending_query = "SELECT COUNT(DISTINCT ra.request_inv_no) AS count FROM request_approvers ra 
                                     WHERE ra.request_type_id = $settlement_type_id AND ra.approver_id = " . (int)$empid . " AND ra.status = 'pending'";
        $res_settlement = mysqli_query($conDB, $settlement_pending_query);
        if ($res_settlement && ($rs = mysqli_fetch_assoc($res_settlement))) {
            $settlement_pending_count = (int)$rs['count'];
        }
    }
}
// --- END NEW SETTLEMENT PENDING COUNT ---

// --- Fetch Payroll Pending Approval Count (NEW) ---
$payroll_pending_count = 0;
$payroll_type_id = 0;
$payroll_table_exists = false;

$payroll_tbl_res = mysqli_query($conDB, "SHOW TABLES LIKE 'payroll_approval_requests'");
if ($payroll_tbl_res && mysqli_num_rows($payroll_tbl_res) > 0) {
    $payroll_table_exists = true;
}

$payroll_type_query = mysqli_query($conDB, "SELECT id FROM approval_request_types WHERE type_name = 'payroll_request' LIMIT 1");
if ($row = mysqli_fetch_assoc($payroll_type_query)) {
    $payroll_type_id = (int)$row['id'];
}

if ($payroll_table_exists && $payroll_type_id > 0) {
    if ($is_system_admin || $user_role == 'Administrator') {
        $payroll_pending_query_admin = "SELECT COUNT(DISTINCT ra.request_inv_no) AS count
            FROM request_approvers ra
            JOIN payroll_approval_requests pr ON pr.request_inv_no = ra.request_inv_no
            WHERE ra.request_type_id = $payroll_type_id
              AND ra.status = 'pending'
              AND pr.status = 'pending_approval'";
        $res_payroll_admin = mysqli_query($conDB, $payroll_pending_query_admin);
        if ($res_payroll_admin && ($rpa = mysqli_fetch_assoc($res_payroll_admin))) {
            $payroll_pending_count = (int)$rpa['count'];
        }
    } elseif ($use_dept_scoped_pending_counts) {
        $payroll_pending_query_dept = "SELECT COUNT(DISTINCT pr.request_inv_no) AS count
            FROM payroll_approval_requests pr
            LEFT JOIN employees e ON e.emp_id = pr.requested_by
            WHERE e.dept = '" . mysqli_real_escape_string($conDB, $user_dept) . "'
              AND pr.status = 'pending_approval'";
        $res_payroll_dept = mysqli_query($conDB, $payroll_pending_query_dept);
        if ($res_payroll_dept && ($rpd = mysqli_fetch_assoc($res_payroll_dept))) {
            $payroll_pending_count = (int)$rpd['count'];
        }
    } else {
        $payroll_pending_query = "SELECT COUNT(DISTINCT ra.request_inv_no) AS count
            FROM request_approvers ra
            JOIN payroll_approval_requests pr ON pr.request_inv_no = ra.request_inv_no
            WHERE ra.request_type_id = $payroll_type_id
              AND ra.approver_id = '" . mysqli_real_escape_string($conDB, (string)$empid) . "'
              AND ra.status = 'pending'
              AND pr.status = 'pending_approval'";
        $res_payroll = mysqli_query($conDB, $payroll_pending_query);
        if ($res_payroll && ($rp = mysqli_fetch_assoc($res_payroll))) {
            $payroll_pending_count = (int)$rp['count'];
        }
    }
}
// --- END NEW PAYROLL PENDING COUNT ---

// --- Fetch Business Trip Pending Approval Count (NEW) ---
$business_trip_pending_count = 0;
$business_trip_type_id = 0;
$business_trip_type_query = mysqli_query($conDB, "SELECT id FROM approval_request_types WHERE type_name = 'business_trip' LIMIT 1");
if ($row = mysqli_fetch_assoc($business_trip_type_query)) {
    $business_trip_type_id = (int)$row['id'];
}
if ($business_trip_type_id > 0) {
    if ($is_system_admin || $user_role == 'Administrator') {
        // Admin: count all distinct business trip requests still pending anywhere
        $business_trip_pending_query_admin = "SELECT COUNT(DISTINCT ra.request_inv_no) AS count
                                                                             FROM request_approvers ra
                                                                             JOIN emp_business_trip bt ON bt.request_inv_no = ra.request_inv_no
                                                                             WHERE ra.status = 'pending' 
                                                                                 AND ra.request_type_id = $business_trip_type_id
                                                                                 AND bt.current_status = 'pending_approval'";
        $res_bt_admin = mysqli_query($conDB, $business_trip_pending_query_admin);
        if ($res_bt_admin && ($rbta = mysqli_fetch_assoc($res_bt_admin))) {
            $business_trip_pending_count = (int)$rbta['count'];
        }
    } elseif ($use_dept_scoped_pending_counts) {
        // Department Manager: count all business trip requests from their department employees
        // Also applies to GM and HR Managers
        $business_trip_pending_query_dept = "SELECT COUNT(DISTINCT bt.request_inv_no) AS count
                                                                           FROM emp_business_trip bt
                                                                           JOIN employees e ON bt.emp_id = e.emp_id
                                                                           WHERE e.dept = '" . mysqli_real_escape_string($conDB, $user_dept) . "'
                                                                               AND bt.current_status = 'pending_approval'";
        $res_bt_dept = mysqli_query($conDB, $business_trip_pending_query_dept);
        if ($res_bt_dept && ($rbtd = mysqli_fetch_assoc($res_bt_dept))) {
            $business_trip_pending_count = (int)$rbtd['count'];
        }
    } else {
        // Regular user: count requests awaiting THIS user's approval
        $business_trip_pending_query = "SELECT COUNT(DISTINCT ra.request_inv_no) AS count
                                                                     FROM request_approvers ra
                                                                     JOIN emp_business_trip bt ON bt.request_inv_no = ra.request_inv_no
                                                                     WHERE ra.approver_id = " . (int)$empid . "
                                                                         AND ra.status = 'pending'
                                                                         AND ra.request_type_id = $business_trip_type_id
                                                                         AND bt.current_status = 'pending_approval'";
        $res_bt = mysqli_query($conDB, $business_trip_pending_query);
        if ($res_bt && ($rbt = mysqli_fetch_assoc($res_bt))) {
            $business_trip_pending_count = (int)$rbt['count'];
        }
    }
}
// --- END NEW BUSINESS TRIP PENDING COUNT ---

// Initialize counts to 0
$status_cont_vacapl = 0;
$status_cont_vacaphr = 0;
$status_cont_vacapv = 0;
$status_cont_contaprl = 0;

// Get count for 'apply' status
$sql_count_vacapl = mysqli_query($conDB, "SELECT COUNT(*) AS `statusaply` FROM `apply_vac_dep` WHERE `status`='apply'");
if ($rec = mysqli_fetch_assoc($sql_count_vacapl)) {
    $status_cont_vacapl = $rec["statusaply"];
}

// Get count for 'app_hr' status
$sql_count_vacaphr = mysqli_query($conDB, "SELECT COUNT(*) AS `apphr` FROM `apply_vac_dep` WHERE `status`='app_hr'");
if ($rec = mysqli_fetch_assoc($sql_count_vacaphr)) {
    $status_cont_vacaphr = $rec["apphr"];
}

// Get count for 'approve' status for the specific department
$sql_count_vacapv = mysqli_query($conDB, "SELECT COUNT(*) AS `statusaprv` FROM `apply_vac_dep` WHERE `status`='approve' AND `review`='A' AND `dept`='" . mysqli_real_escape_string($conDB, $user_dept) . "'");
if ($rec = mysqli_fetch_assoc($sql_count_vacapv)) {
    $status_cont_vacapv = $rec["statusaprv"];
}

// Get count for temporary contracts with 'Pending' status
$sql_count_aprl = mysqli_query($conDB, "SELECT COUNT(*) AS `contaprl` FROM `employee_temp_contants` WHERE `status`='Pending'");
if ($rec = mysqli_fetch_assoc($sql_count_aprl)) {
    $status_cont_contaprl = $rec["contaprl"];
}

// --- CALCULATE TOTAL COUNTS FOR PARENT MENUS ---
// Total count for Approvals menu
$approvals_total_count = $vacation_pending_count + $rejoin_pending_count + $loan_pending_count + $resignation_pending_count + $settlement_pending_count + $business_trip_pending_count + $payroll_pending_count + $status_cont_contaprl;

// Total count for Requests menu
$requests_total_count = $smart_request_count + $general_request_count;
// --- END PARENT MENU TOTALS ---

// Generate unique numbers for SR and QUO
$newinvnr = "SMT" . ($empid ?? '') . date('ymdis');
$newinvgr = "GR" . ($empid ?? '') . date('ymdis');
$newquonr = "QUO" . ($empid ?? '') . date('ymdis');
?>

<div class="user-box">
    <div class="user-img">
        <img src="<?= htmlspecialchars($avatar ?? '', ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars($fname ?? '', ENT_QUOTES, 'UTF-8') ?>" title="<?= htmlspecialchars($fname ?? '', ENT_QUOTES, 'UTF-8') ?>" class="rounded-circle img-fluid">
    </div>
    <h5><a href="javascript:void(0);"><?= htmlspecialchars($userwel ?? '', ENT_QUOTES, 'UTF-8') ?></a> </h5>
    <p class="text-muted"><?= htmlspecialchars($usracc ?? '', ENT_QUOTES, 'UTF-8') ?></p>
</div>

<div id="sidebar-menu">
    <ul class="metismenu" id="side-menu">
        <li class="menu-title">Navigation</li>

        <!-- Dashboard -->
        <?php if ($is_gm): ?>
            <li><a href="<?= $dashboardGMLink ?>" class="<?= dashboard($current_page) ?>"><i class="fa fa-airplay"></i><span><?=__('dashboard') ?></span></a></li>
        <?php else: ?>
            <li><a href="<?= $dashboardLink ?>" class="<?= dashboard($current_page) ?>"><i class="fa fa-airplay"></i><span><?=__('dashboard') ?></span></a></li>
        <?php endif; ?>

        <!-- Employee's Group -->
        <?php if ($show_employees_menu): ?>
        <li class="<?=(strpos($current_page_name, 'employee') !== false ? 'mm-active' : '')?>">
            <a href="javascript:void(0);"><i class="fa fa-users-gear"></i><span><?=__("employee's") ?></span><span class="float-right fa fa-arrow-right"></span></a>
            <ul class="nav-second-level" aria-expanded="<?= (strpos($current_page_name, 'employee') !== false ? 'true' : 'false') ?>">
                <?php if (in_array($user_role, $can_see_new_employee_page) || in_array($user_type, $can_see_new_employee_page)): ?>
                    <li><a href="<?= $addNewEmployeeLink ?>"><i class="fa fa-user-plus"></i><span><?=__('new_employee') ?></span></a></li>
                <?php endif; ?>
                <?php if (in_array($user_role, $can_see_all_employees_page) || in_array($user_type, $can_see_all_employees_page)): ?>
                    <li><a href="<?= $allEmployeesLink ?>"><i class="fa fa-users"></i><span><?=__('all_employees') ?></span></a></li>
                <?php endif; /* ?>
                <?php if (in_array($user_role, $can_see_employees_bank_page) || in_array($user_type, $can_see_employees_bank_page)): ?>
                    <li><a href="<?= $yearlyEOSLink ?>"><i class="fa fa-calendar-time"></i><span><?=__('employees_bank') ?></span></a></li>
                <?php endif; ?>
                <?php if ($isHR || $is_system_admin || $isDeptHr): ?>
                    <li><a href="<?= $employeeSalaryReportLink ?>"><i class="fa fa-money-bill"></i><span>Salary Report</span></a></li>
                <?php endif; */?>
                <?php if (in_array($user_role, $can_see_employees_payroll_page) || in_array($user_type, $can_see_employees_payroll_page)): ?>
                    <li><a href="<?= $payrollLink ?>"><i class="fa fa-money-bill-transfer"></i><span><?=__('payroll') ?></span></a></li>
                <?php endif; ?>
                <?php /* ?>
                <li>
                    <a href="javascript:void(0);"><i class="fa fa-users-gear"></i><span><?=__("history") ?></span><span class="float-right fa fa-arrow-right"></span></a>
                    <ul class="nav-second-level" aria-expanded="false">
                        <?php if (in_array($user_role, $can_see_employees_group_main) || in_array($user_type, $can_see_employees_group_main)): ?>
                            <li><a href="<?= $addManualLoanLink ?>"><i class="fa fa-solid fa-history"></i><span><?=__('add_loan_history') ?></span></a></li>
                        <?php endif; ?>
                        <?php if (in_array($user_role, $can_see_employees_group_main) || in_array($user_type, $can_see_employees_group_main)): ?>
                            <li><a href="<?= $manualVacationLink ?>"><i class="fa fa-solid fa-history"></i><span><?=__('add_vacation_history') ?></span></a></li>
                        <?php endif; ?>
                    </ul>
                </li>
                <?php */ ?>
                <?php if (in_array($user_role, $can_see_import_iqama_page) || in_array($user_type, $can_see_import_iqama_page)): ?>
                    <li><a href="<?= $processIqamaImportLink ?>"><i class="fa fa-plus-circle"></i><span><?=__('import_iqama_exp') ?></span></a></li>
                <?php endif; ?>

                <?php if (in_array($user_role, $can_see_employee_evaluation_page) || in_array($user_type, $can_see_employee_evaluation_page) || $isDeptManager): ?>
                    <li><a href="<?= $employeeEvaluationLink ?>"><i class="fa fa-chart-line"></i><span><?=__('employee_evaluation', 'Employee Evaluation') ?></span></a></li>
                <?php endif; ?>

                <?php if ($is_system_admin): ?>
                    <li><a href="<?= $manageEmployeeSupervisorsLink ?>"><i class="fa fa-users-gear"></i><span><?=__('manage_supervisors', 'Manage Supervisors') ?></span></a></li>
                <?php endif; ?>

                <?php if ($isHR || $is_system_admin): ?>
                    <li><a href="<?= $manageHolidaysLink ?>"><i class="fa fa-calendar-days"></i><span><?=__('holiday_management', 'Holiday Management') ?></span></a></li>
                <?php endif; ?>

                <?php /* 
                if (
                    in_array($user_role, $can_see_evaluations_report_strict) ||
                    in_array($user_type, $can_see_evaluations_report_strict) ||
                    $is_gm || $is_admin
                ): ?>
                    <li><a href="<?= $allEmployeeEvaluationsLink ?>"><i class="fa fa-file-chart-line"></i><span><?=__('evaluation_reports', 'Evaluation Reports') ?></span></a></li>
                <?php endif; */ ?>

            </ul>
        </li>
        <?php endif; ?>

        <!-- Approvals Group -->
        <?php if ($show_approvals_menu): ?>
        <li class="<?=(strpos($current_page_name, 'applied') !== false || strpos($current_page_name, 'approvals') !== false || strpos($current_page_name, 'resignations') !== false ? 'mm-active' : '')?>">
            <a href="javascript:void(0);"><i class="fa fa-check-to-slot"></i><span><?=__('approvals')?></span><?= ($approvals_total_count > 0) ? "<span class='badgez badge-danger'>$approvals_total_count</span>" : "" ?><span class="float-right fa fa-arrow-right"></span></a>
            <ul class="nav-second-level" aria-expanded="<?= (strpos($current_page_name, 'applied') !== false || strpos($current_page_name, 'approvals') !== false || strpos($current_page_name, 'resignations') !== false ? 'true' : 'false') ?>">
                <?php if (in_array($user_role, $can_see_applied_vac_page) || in_array($user_type, $can_see_applied_vac_page)): ?>
                    <li><a href="<?= $appliedVacationsLink ?>" class="<?= all_applied_vac($current_page) ?>"><i class="fa fa-calendar-circle-user"></i><span><?=__('vacations') ?> <?= ($vacation_pending_count > 0) ? "<span class='badgez badge-danger'>$vacation_pending_count</span>" : "" ?></span></a></li>
                <?php endif; ?>
                <?php /*if (in_array($user_role, $can_see_business_trip_page) || in_array($user_type, $can_see_business_trip_page)): ?>
                    <li><a href="<?= $appliedBusinessTripLink ?>"><i class="fa fa-plane"></i><span><?=__('business_trip', 'Business Trip') ?></span><?= ($business_trip_pending_count > 0) ? "<span class='badgez badge-danger'>$business_trip_pending_count</span>" : "" ?></a></li>
                <?php endif;*/ ?>
                <?php if (in_array($user_role, $can_see_rejoin_approvals_page) || in_array($user_type, $can_see_rejoin_approvals_page)): ?>
                    <li><a href="<?= $rejoinApprovalsLink ?>"><i class="fa fa-plane-arrival"></i><span><?=__('rejoin_approvals', 'Rejoin Approvals') ?> <?= ($rejoin_pending_count > 0) ? "<span class='badgez badge-danger'>$rejoin_pending_count</span>" : "" ?></span></a></li>
                <?php endif; ?>
                <?php if (in_array($user_role, $can_see_loan_approvals_page) || in_array($user_type, $can_see_loan_approvals_page)): ?>
                    <li><a href="<?= $appliedLoanLink ?>"><i class="fa fa-money-bill-trend-up"></i><span><?=__('loans') ?></span><?= ($loan_pending_count > 0) ? "<span class='badgez badge-danger'>$loan_pending_count</span>" : "" ?></a></li>
                <?php endif; ?>
                <?php if (in_array($user_role, $can_see_settlements_page) || in_array($user_type, $can_see_settlements_page)): ?>
                    <li><a href="<?= $settlementsLink ?>"><i class="fa fa-file-invoice-dollar"></i><span><?=__('settlements', 'Settlements') ?></span><?= ($settlement_pending_count > 0) ? "<span class='badgez badge-danger'>$settlement_pending_count</span>" : "" ?></a></li>
                <?php endif; ?>
                <?php if (in_array($user_role, $can_see_payroll_approvals_page) || in_array($user_type, $can_see_payroll_approvals_page)): ?>
                    <li><a href="<?= $payrollApprovalsLink ?>"><i class="fa fa-money-check-dollar"></i><span><?=__('payroll_approvals', 'Payroll Approvals') ?></span><?= ($payroll_pending_count > 0) ? "<span class='badgez badge-danger'>$payroll_pending_count</span>" : "" ?></a></li>
                <?php endif; ?>
                <?php if (in_array($user_role, $can_see_resignations_page) || in_array($user_type, $can_see_resignations_page)): ?>
                    <li><a href="<?= $allResignationsLink ?>"><i class="fa fa-user-times"></i><span><?=__('resignations') ?></span><?= ($resignation_pending_count > 0) ? "<span class='badgez badge-danger'>$resignation_pending_count</span>" : "" ?></a></li>
                <?php endif; ?>
                 <?php if (in_array($user_role, $can_see_content_approvals_page) || in_array($user_type, $can_see_content_approvals_page)): ?>
                    <li><a href="<?= $tempContractsLink ?>"><i class="fa fa-arrows-spin"></i><span><?=__('content_updates') ?> <?= ($status_cont_contaprl > 0) ? "<span class='badgez badge-danger'>$status_cont_contaprl</span>" : "" ?></span></a></li>
                <?php endif; ?>
            </ul>
        </li>
        <?php endif; ?>

        <!-- Requests Menu (Smart Request + General Request) -->
        <?php if ((!$isSmartRequestMenuBlocked && (in_array($user_role, $can_see_smart_requests_page) || in_array($user_type, $can_see_smart_requests_page))) || (!$isGeneralRequestMenuBlocked && (in_array($user_role, $can_see_general_requests_page) || in_array($user_type, $can_see_general_requests_page))) || (in_array($user_role, $can_see_vouchers_page) || in_array($user_type, $can_see_vouchers_page))): ?>
        <li class="<?=((strpos($current_page_name, 'request') !== false || $current_page_name === 'vouchers.php') ? 'mm-active' : '')?>">
            <a href="javascript:void(0);"><i class="fa fa-ticket"></i><span><?=__('requests', 'Requests')?></span><?= ($requests_total_count > 0) ? "<span class='badgez badge-danger'>$requests_total_count</span>" : "" ?><span class="float-right fa fa-arrow-right"></span></a>
            <ul class="nav-second-level" aria-expanded="<?= ((strpos($current_page_name, 'request') !== false || $current_page_name === 'vouchers.php') ? 'true' : 'false') ?>">
                <?php if (!$isSmartRequestMenuBlocked && (in_array($user_role, $can_see_smart_requests_page) || in_array($user_type, $can_see_smart_requests_page))): ?>
                <li><a href="<?= $smartRequestsLink ?>"><i class="fa fa-layer-group"></i> <span> <?=__('smart_requests', 'Smart Request') ?> </span> <?= ($smart_request_count > 0) ? "<span class='badgez badge-danger'>$smart_request_count</span>" : "" ?></a></li>
                <?php endif; ?>
                <?php if (!$isGeneralRequestMenuBlocked && (in_array($user_role, $can_see_general_requests_page) || in_array($user_type, $can_see_general_requests_page))): ?>
                <li><a href="<?= $generalRequestsLink ?>"><i class="fa fa-file-alt"></i> <span><?=__('general_request', 'General Request')?></span> <?= ($general_request_count > 0) ? "<span class='badgez badge-danger'>$general_request_count</span>" : "" ?></a></li>
                <?php endif; ?>
                <?php if (in_array($user_role, $can_see_vouchers_page) || in_array($user_type, $can_see_vouchers_page)): ?>
                <li><a href="<?= $vouchersLink ?>"><i class="fa fa-box-archive"></i> <span> <?=__('vouchers') ?> </span></a></li>
                <?php endif; ?>
            </ul>
        </li>
        <?php endif; ?>

        <!-- Admin Section -->
        <?php if ($is_admin || $is_system_admin  || in_array($user_role, $can_see_cars_management_page) ): ?>
            <li><a href="<?= $carsLink ?>" class="<?= all_cars($current_page) ?>"><i class="fa fa-cars"></i><span><?=__('cars') ?></span></a></li>
            <li><a href="<?= $locationsLink ?>" class="<?= all_locations($current_page) ?>"><i class="fa fa-sitemap"></i><span><?=__('locations') ?></span></a></li>
        <?php endif; ?>
        <?php if ($is_admin || $is_system_admin || in_array($user_role, $can_see_asstet_inventory_page)): ?>
            <li><a href="<?= $assetInventoryLink ?>"><i class="fa fa-box"></i><span><?=__('asset_inventory', 'Asset Inventory') ?></span></a></li>
        <?php endif; ?>

        <?php
        $can_view_vac_balance_history = $is_system_admin || user_has_special_access($conDB, $empid ?? '', 'view_vacation_balance_history', $user_role ?? '', $user_type ?? '', $is_system_admin ?? false);
        $can_access_app_settings = $is_system_admin
            || user_has_special_access($conDB, $empid ?? '', 'manage_department_settings', $user_role ?? '', $user_type ?? '', $is_system_admin ?? false)
            || user_has_special_access($conDB, $empid ?? '', 'manage_job_title_settings', $user_role ?? '', $user_type ?? '', $is_system_admin ?? false)
            || user_has_special_access($conDB, $empid ?? '', 'manage_global_request_blocks', $user_role ?? '', $user_type ?? '', $is_system_admin ?? false);
        ?>
        <?php if ($is_system_admin || $user_role === 'HR_Senior_BP' || $can_view_vac_balance_history || $can_access_app_settings): ?>
        <li class="<?= (($current_page_name === 'vacation_dates_by_inv.php' || $current_page_name === 'send_announcement.php') ? 'mm-active' : '') ?>">
            <a href="javascript:void(0);"><i class="fa fa-calendar-check"></i><span><?=__('tools', 'Tools') ?></span><span class="float-right fa fa-arrow-right"></span></a>
            <ul class="nav-second-level" aria-expanded="<?= (($current_page_name === 'vacation_dates_by_inv.php' || $current_page_name === 'send_announcement.php') ? 'true' : 'false') ?>">
                <?php if ($is_system_admin): ?>
                <li><a href="<?= $announcementLink ?>"><i class="fa fa-bullhorn"></i><span><?=__('announcement', 'Announcement')?></span></a></li>
                <?php endif; ?>
                <?php if ($is_system_admin || $user_role === 'HR_Senior_BP'): ?>
                <li><a href="<?= $vacationDatesEditorLink ?>"><i class="fa fa-calendar-days"></i><span><?=__('vacation_date_editor', 'Vacation Date Editor') ?></span></a></li>
                <?php endif; ?>
                <?php if ($is_system_admin || $user_role === 'HR_Senior_BP'): ?>
                <li><a href="<?= $manageEmployeeSupervisorsLink ?>"><i class="fa fa-users-gear"></i><span><?=__('manage_supervisors', 'Manage Supervisors') ?></span></a></li>
                <?php endif; ?>
                <?php if ($can_view_vac_balance_history): ?>
                <li><a href="<?= $vacationBalanceHistoryLink ?>" target="_blank"><i class="fa fa-clock-rotate-left"></i><span><?=__('vacation_balance_history', 'Vacation Balance History') ?></span></a></li>
                <?php endif; ?>
                <?php if ($can_access_app_settings && !$is_system_admin): ?>
                <li><a href="<?= $appSettingsLink ?>" target="_blank"><i class="fa fa-gear"></i><span><?=__('app_settings', 'App Settings') ?></span></a></li>
                <?php endif; ?>
                <?php if ($is_system_admin): ?>
                <li><a href="<?= $loanRejectionReport ?>" target="_blank"><i class="fa fa-solid fa-square-shekel"></i><span><?=__('loan_rejection_report', 'Loan Rejection Report') ?></span></a></li>
                <li><a href="<?= $tableJsonApiLink ?>" target="_blank"><i class="fa fa-database"></i><span><?=__('table_json_api', 'Table JSON API') ?></span></a></li>
                <li><a href="<?= $diagnoseDoubleDeductionLink ?>" target="_blank"><i class="fa fa-stethoscope"></i><span><?=__('diagnose_double_deduction', 'Diagnose Double Deduction') ?></span></a></li>
                <li><a href="<?= $fixDoubleDeductionLink ?>" target="_blank"><i class="fa fa-screwdriver-wrench"></i><span><?=__('fix_double_deduction', 'Fix Double Deduction') ?></span></a></li>
                <?php endif; ?>
            </ul>
        </li>
        <?php endif; ?>

        <?php if ($is_system_admin): ?>
        <li>
            <a href="javascript:void(0);"><i class="fa fa-gear-complex"></i><span><?=__('settings') ?></span><span class="float-right fa fa-arrow-right"></span></a>
            <ul class="nav-second-level" aria-expanded="false">
                <!-- User Management -->
                <li>
                    <a href="javascript:void(0);"><i class="fa fa-users"></i><span><?=__('user_management', 'Users') ?></span><span class="float-right fa fa-arrow-right"></span></a>
                    <ul class="nav-third-level" aria-expanded="false">
                        <li><a href="<?= $usersLink ?>"><i class="fa fa-users-gear"></i><span><?=__('users') ?></span></a></li>
                        <li><a href="<?= $userActivityLink ?>"><i class="fa fa-history"></i><span><?=__('user_activity') ?></span></a></li>
                    </ul>
                </li>
                <!-- System Tools -->
                <li>
                    <a href="javascript:void(0);"><i class="fa fa-screwdriver-wrench"></i><span><?=__('system_tools', 'System Tools') ?></span><span class="float-right fa fa-arrow-right"></span></a>
                    <ul class="nav-third-level" aria-expanded="false">
                        <li><a href="<?= $appSettingsLink ?>" target="_blank"><i class="fa fa-gear"></i><span><?=__('app_settings', 'App Settings') ?></span></a></li>
                        
                    </ul>
                </li>
                <!-- System Logs -->
                <li>
                    <a href="javascript:void(0);"><i class="fa fa-list-check"></i><span><?=__('system_logs', 'System Logs') ?></span><span class="float-right fa fa-arrow-right"></span></a>
                    <ul class="nav-third-level" aria-expanded="false">
                        <li><a href="<?= $activityLoggerLink ?>"><i class="fa fa-list-check"></i><span><?=__('activity_logger') ?></span></a></li>
                    </ul>
                </li>
                
                <!-- Language Settings -->
                <li><a href="<?= $languageLink ?>"><i class="fa fa-language"></i><span><?=__('language') ?></span></a></li>
            </ul>
        </li>
        <?php endif; ?>
        <!-- Reports -->
        <?php if ($can_see_reports_menu): ?>
            <li><a href="reports.php"><i class="fa fa-chart-simple"></i> <span> <?=__('reports') ?> </span></a></li>
        <?php endif; ?>
        <li><a href="./system_guide.php" target="_blank"><i class="fa fa-book-open-lines"></i><span><?=__('system_guide') ?></span></a></li>
    </ul>
</div>