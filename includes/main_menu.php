<?php
/****************************************************************
 * MODIFICATION SUMMARY (024-main_menu.php):
 * 1. CREATED "APPROVALS" GROUP: A new dropdown menu group named "Approvals" has been created to centralize all approval-related tasks.
 * 2. MOVED MENU ITEMS: The following pages have been moved into this new group:
 * - "Applied Vacations" (`all_applied_vac.php`)
 * - "Loan Approvals" (`all_applied_loan.php`)
 * - "Content Approvals" (formerly "Contant List", `emp_temp_contant.php`)
 * 3. CONSOLIDATED PERMISSIONS: A new permission variable (`$show_approvals_menu`) has been created to control the visibility of the entire "Approvals" group, making the menu cleaner and more organized.
 ****************************************************************/
/****************************************************************
 * MODIFICATION SUMMARY (003-main_menu.php):
 * 1. ADDED EMPLOYEE ROLE: The 'Employee' role has been added to the list of roles allowed to access `all_applied_loan.php`. This resolves the permission conflict that was causing employees to be redirected away from the page.
 ****************************************************************/

// The $user_role, $user_type, and $is_system_admin variables are now available globally from session_check.php

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
$appliedLoanLink = 'all_applied_loan.php';
$carsLink = 'all_cars.php';
$locationsLink = 'all_locations.php';
$machinesLink = 'all_machines.php';
$itemsLink = 'all_menu_item.php';
$ordersLink = 'all_orders.php';
$customersLink = 'odr_customers.php';
$quotationsLink = 'all_quotations.php';
$allCustomersLink = 'all_customers.php';
$customerSurveyLink = 'customers_survey.php';
$smartRequestsLink = 'all_requests.php';
$vouchersLink = 'vouchers.php';
$invoicesLink = 'all_user_invoices.php';
$usersLink = 'all_users.php';
$fileManagerLink = 'file_manager.php';
$galleryLink = 'gallery.php';
$languageLink = 'language.php';
$logActivityLink = 'log_activity.php';


// =================================================================================
// PAGE ACCESS CONTROL
// =================================================================================

$page_roles = [
    'dashboard.php' => ['administrator', 'HR_Manager', 'HR_Assistant', 'Finance_Manager', 'Finance_Assistant', 'DPT_Manager', 'GM', 'Employee'],
    'dashboardgm.php' => ['GM'],
    'add_new_employee.php' => ['administrator', 'HR_Manager', 'HR_Assistant'],
    'reg_employee.php' => ['administrator', 'HR_Manager', 'HR_Assistant', 'Finance_Manager', 'Finance_Assistant', 'DPT_Manager'],
    'emp_temp_contant.php' => ['administrator', 'HR_Manager', 'HR_Assistant'],
    'employee_audit_gen.php' => ['administrator', 'HR_Manager', 'HR_Assistant', 'Finance_Manager', 'Finance_Assistant'],
    'generate_payroll.php' => ['administrator', 'HR_Manager', 'HR_Assistant'],
    'all_applied_vac.php' => ['administrator', 'HR_Manager', 'HR_Assistant', 'Finance_Manager', 'Finance_Assistant', 'DPT_Manager', 'GM','Employee'],
    'all_applied_loan.php' => ['administrator', 'GM', 'HR_Manager', 'HR_Assistant', 'Finance_Manager', 'Finance_Assistant', 'DPT_Manager', 'Employee'],
    'all_cars.php' => ['administrator'],
    'all_locations.php' => ['administrator'],
    'all_machines.php' => ['administrator'],
    'all_menu_item.php' => ['administrator'],
    'all_requests.php' => ['administrator', 'GM', 'HR_Manager', 'HR_Assistant', 'Finance_Manager', 'Finance_Assistant', 'DPT_Manager'],
    'vouchers.php' => ['administrator', 'HR_Manager', 'HR_Assistant', 'Finance_Manager', 'Finance_Assistant'],
    'all_user_invoices.php' => ['administrator', 'HR_Manager', 'HR_Assistant', 'Finance_Manager', 'Finance_Assistant', 'DPT_Manager', 'Employee'],
    'all_users.php' => ['administrator'],
    'file_manager.php' => ['administrator'],
    'gallery.php' => ['administrator'],
    'language.php' => ['administrator'],
    'log_activity.php' => ['administrator'],
];

$current_page_name = basename($_SERVER['PHP_SELF']);

if ($user_type != 'administrator') { 
    if (isset($page_roles[$current_page_name])) {
        if (!in_array($user_role, $page_roles[$current_page_name])) {
            header("Location: dashboard.php");
            exit();
        }
    }
}

// --- Role lists for menu visibility ---
$can_see_employees_group_main = ['administrator', 'HR_Manager', 'HR_Assistant'];
$can_see_all_employees_page = ['administrator', 'HR_Manager', 'HR_Assistant', 'Finance_Manager', 'Finance_Assistant', 'DPT_Manager'];
$can_see_employees_bank_page = ['administrator', 'HR_Manager', 'HR_Assistant', 'Finance_Manager', 'Finance_Assistant'];
$can_see_applied_vac_page = ['administrator', 'GM', 'HR_Manager', 'HR_Assistant', 'DPT_Manager'];
$can_see_loan_approvals_page = ['administrator', 'GM', 'HR_Manager', 'HR_Assistant', 'Finance_Manager', 'Finance_Assistant', 'DPT_Manager'];
$can_see_content_approvals_page = ['administrator', 'HR_Manager', 'HR_Assistant'];
$can_see_smart_requests_page = ['administrator', 'GM', 'HR_Manager', 'HR_Assistant', 'Finance_Manager', 'Finance_Assistant', 'DPT_Manager'];
$can_see_vouchers_page = ['administrator', 'HR_Manager', 'HR_Assistant', 'Finance_Manager', 'Finance_Assistant'];

$is_admin = $is_system_admin; 
$is_gm = $user_role == 'GM';

$show_employees_menu = !empty(array_intersect([$user_role, $user_type], $can_see_employees_group_main)) ||
                       !empty(array_intersect([$user_role, $user_type], $can_see_all_employees_page)) ||
                       !empty(array_intersect([$user_role, $user_type], $can_see_employees_bank_page));

$show_approvals_menu = !empty(array_intersect([$user_role, $user_type], $can_see_applied_vac_page)) ||
                       !empty(array_intersect([$user_role, $user_type], $can_see_loan_approvals_page)) ||
                       !empty(array_intersect([$user_role, $user_type], $can_see_content_approvals_page));


// =================================================================================
// DATA FETCHING FOR BADGES
// =================================================================================

// --- Fetch Loan Approval Counts ---
$loan_pending_count = 0;
$loan_count_query = "";

switch ($user_role) {
    case 'DPT_Manager':
        $loan_count_query = "SELECT COUNT(*) as count FROM emp_loan l JOIN employees e ON l.emp_id = e.emp_id WHERE l.status = 'dept_manager_pending' AND e.dept = '" . mysqli_real_escape_string($conDB, $user_dept) . "'";
        break;
    case 'HR_Manager':
        $loan_count_query = "SELECT COUNT(*) as count FROM emp_loan WHERE status = 'hr_manager_pending'";
        break;
    case 'Finance_Manager':
        $loan_count_query = "SELECT COUNT(*) as count FROM emp_loan WHERE status = 'finance_manager_pending'";
        break;
    case 'GM':
        $loan_count_query = "SELECT COUNT(*) as count FROM emp_loan WHERE status = 'gm_pending'";
        break;
    case 'Finance_Assistant':
        $loan_count_query = "SELECT COUNT(*) as count FROM emp_loan WHERE status = 'finance_assistant_pending'";
        break;
    case 'administrator':
        $loan_count_query = "SELECT COUNT(*) as count FROM emp_loan WHERE status NOT IN ('approved', 'paid', 'rejected')";
        break;
}

if (!empty($loan_count_query)) {
    $result = mysqli_query($conDB, $loan_count_query);
    if ($row = mysqli_fetch_assoc($result)) {
        $loan_pending_count = $row['count'];
    }
}

// --- Fetch Smart Request Counts ---
$smart_request_count = 0;
$smart_request_query = "";
switch ($user_role) {
    case 'DPT_Manager':
        $smart_request_query = "SELECT COUNT(*) as count FROM smart_request WHERE dept_manager_status = 'pending' AND department = '" . mysqli_real_escape_string($conDB, $user_dept) . "'";
        break;
    case 'Finance_Manager':
        $smart_request_query = "SELECT COUNT(*) as count FROM smart_request WHERE finance_manager_status = 'pending'";
        break;
    case 'GM':
        $smart_request_query = "SELECT COUNT(*) as count FROM smart_request WHERE gm_status = 'pending'";
        break;
    case 'administrator':
        $smart_request_query = "SELECT COUNT(*) as count FROM smart_request WHERE current_status != 'approved' AND current_status != 'rejected' AND current_status != 'paid'";
        break;
    case 'HR_Manager':
    case 'HR_Assistant':
         $smart_request_query = "SELECT COUNT(*) as count FROM smart_request WHERE dept_manager_status = 'approved' AND (finance_manager_status = 'pending' OR gm_status = 'pending')";
        break;
}
if (!empty($smart_request_query)) {
    $result = mysqli_query($conDB, $smart_request_query);
    if ($row = mysqli_fetch_assoc($result)) {
        $smart_request_count = $row['count'];
    }
}
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

// Generate unique numbers for SR and QUO
$newinvnr = "SMT" . ($empid ?? '') . date('ymdis');
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
        <li>
            <a href="javascript:void(0);"><i class="fa fa-users-gear"></i><span><?=__("employee's") ?></span><span class="float-right fa fa-arrow-right"></span></a>
            <ul class="nav-second-level" aria-expanded="false">
                <?php if (in_array($user_role, $can_see_employees_group_main) || in_array($user_type, $can_see_employees_group_main)): ?>
                    <li><a href="<?= $addNewEmployeeLink ?>"><i class="fa fa-user-plus"></i><span><?=__('new_employee') ?></span></a></li>
                <?php endif; ?>
                <?php if (in_array($user_role, $can_see_all_employees_page) || in_array($user_type, $can_see_all_employees_page)): ?>
                    <li><a href="<?= $allEmployeesLink ?>"><i class="fa fa-users"></i><span><?=__('all_employees') ?></span></a></li>
                <?php endif; ?>
                <?php if (in_array($user_role, $can_see_employees_bank_page) || in_array($user_type, $can_see_employees_bank_page)): ?>
                    <li><a href="<?= $yearlyEOSLink ?>"><i class="fa fa-calendar-time"></i><span><?=__('employees_bank') ?></span></a></li>
                <?php endif; ?>
                <?php if (in_array($user_role, $can_see_employees_group_main) || in_array($user_type, $can_see_employees_group_main)): ?>
                    <li><a href="<?= $payrollLink ?>"><i class="fa fa-money-bill-transfer"></i><span><?=__('payroll') ?></span></a></li>
                <?php endif; ?>
            </ul>
        </li>
        <?php endif; ?>

        <!-- Approvals Group -->
        <?php if ($show_approvals_menu): ?>
        <li>
            <a href="javascript:void(0);"><i class="fa fa-check-to-slot"></i><span><?=__('approvals')?></span><span class="float-right fa fa-arrow-right"></span></a>
            <ul class="nav-second-level" aria-expanded="false">
                <?php if (in_array($user_role, $can_see_applied_vac_page) || in_array($user_type, $can_see_applied_vac_page)): ?>
                    <li><a href="<?= $appliedVacationsLink ?>" class="<?= all_applied_vac($current_page) ?>"><i class="fa fa-calendar-circle-user"></i><span><?=__('vacations') ?> <?= ($status_cont_vacapl > 0) ? "<span class='badgez badge-danger'>$status_cont_vacapl</span>" : "" ?></span></a></li>
                <?php endif; ?>
                <?php if (in_array($user_role, $can_see_loan_approvals_page) || in_array($user_type, $can_see_loan_approvals_page)): ?>
                    <li><a href="<?= $appliedLoanLink ?>"><i class="fa fa-money-bill-trend-up"></i><span><?=__('loans') ?></span><?= ($loan_pending_count > 0) ? "<span class='badgez badge-danger'>$loan_pending_count</span>" : "" ?></a></li>
                <?php endif; ?>
                 <?php if (in_array($user_role, $can_see_content_approvals_page) || in_array($user_type, $can_see_content_approvals_page)): ?>
                    <li><a href="<?= $tempContractsLink ?>"><i class="fa fa-arrows-spin"></i><span><?=__('content_updates') ?> <?= ($status_cont_contaprl > 0) ? "<span class='badgez badge-danger'>$status_cont_contaprl</span>" : "" ?></span></a></li>
                <?php endif; ?>
            </ul>
        </li>
        <?php endif; ?>

        <!-- Smart Requests -->
        <?php if (in_array($user_role, $can_see_smart_requests_page) || in_array($user_type, $can_see_smart_requests_page)): ?>
            <li><a href="<?= $smartRequestsLink ?>"><i class="fa fa-layer-group"></i> <span> <?=__('smart_requests') ?> </span> <?= ($smart_request_count > 0) ? "<span class='badgez badge-danger'>$smart_request_count</span>" : "" ?></a></li>
        <?php endif; ?>

        <!-- Vouchers -->
        <?php if (in_array($user_role, $can_see_vouchers_page) || in_array($user_type, $can_see_vouchers_page)): ?>
            <li><a href="<?= $vouchersLink ?>"><i class="fa fa-box-archive"></i> <span> <?=__('vouchers') ?> </span></a></li>
        <?php endif; ?>

        <!-- Admin Section -->
        <?php if ($is_admin): ?>
            <li><a href="<?= $carsLink ?>" class="<?= all_cars($current_page) ?>"><i class="fa fa-cars"></i><span><?=__('cars') ?></span></a></li>
            <li><a href="<?= $locationsLink ?>" class="<?= all_locations($current_page) ?>"><i class="fa fa-sitemap"></i><span><?=__('locations') ?></span></a></li>
            <li>
                <a href="javascript:void(0);"><i class="fa fa-gear-complex"></i><span><?=__('settings') ?></span><span class="float-right fa fa-arrow-right"></span></a>
                <ul class="nav-second-level" aria-expanded="false">
                    <li><a href="<?= $usersLink ?>"><i class="fa fa-users-gear"></i><span><?=__('users') ?></span></a></li>
                    <li><a href="<?= $languageLink ?>"><i class="fa fa-language"></i><span><?=__('language') ?></span></a></li>
                </ul>
            </li>
        <?php endif; ?>
    </ul>
</div>
