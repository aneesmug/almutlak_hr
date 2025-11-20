<?php
/*
EMPLOYEE MENU - Simplified Menu for Employee-Related Links Only
- Shows only employee profile and related information pages
- Hides all administrative, approval, and system pages
- Perfect for employee self-service portals and employee information display
*/

// The $user_role, $user_type, and $is_system_admin variables are available globally from session_check.php

// =================================================================================
// EMPLOYEE MENU LINKS DEFINITIONS
// =================================================================================

$profileLink = 'profile.php';
$vacationHistoryLink = 'employee_vacation_history.php';
$loanHistoryLink = 'employee_loan_history.php';
$assignedAssetsLink = 'employee_assigned_assets.php';
$payrollSlipLink = 'employee_payroll_slip.php';
$warningsLink = 'employee_warnings.php';

$current_page_name = basename($_SERVER['PHP_SELF']);

// =================================================================================
// PAGE ACCESS CONTROL FOR EMPLOYEE PAGES
// =================================================================================

$employee_page_roles = [
    'profile.php' => ['Employee', 'Administrator', 'GM', 'HR_Senior_BP', 'HR_Operations', 'HR_Supervisor', 'HR_Recruitment', 'HR_Payroll', 'Finance_Officer', 'Auditor', 'GR_Officer', 'DPT_Manager', 'IT_Team', 'IT_Team_Manager', 'HR_Team', 'HR_Team_Manager', 'Finance_Team', 'Finance_Team_Manager', 'Executive_Team', 'Executive_Team_Manager', 'HR_Manager', 'Finance_Manager'],
    'employee_vacation_history.php' => ['Employee', 'Administrator', 'HR_Senior_BP', 'HR_Operations', 'HR_Supervisor', 'HR_Recruitment', 'HR_Payroll', 'HR_Team', 'HR_Team_Manager', 'HR_Manager'],
    'employee_loan_history.php' => ['Employee', 'Administrator', 'HR_Senior_BP', 'HR_Operations', 'HR_Supervisor', 'HR_Recruitment', 'HR_Payroll', 'Finance_Officer', 'HR_Team', 'HR_Team_Manager', 'Finance_Team', 'Finance_Team_Manager', 'HR_Manager', 'Finance_Manager'],
    'employee_assigned_assets.php' => ['Employee', 'Administrator', 'GR_Officer', 'HR_Senior_BP', 'HR_Team', 'HR_Team_Manager', 'HR_Manager'],
    'employee_payroll_slip.php' => ['Employee', 'Administrator', 'HR_Senior_BP', 'HR_Payroll', 'Finance_Officer', 'HR_Team', 'HR_Team_Manager', 'Finance_Team', 'Finance_Team_Manager', 'HR_Manager', 'Finance_Manager'],
    'employee_warnings.php' => ['Employee', 'Administrator', 'HR_Senior_BP', 'HR_Operations', 'HR_Supervisor', 'HR_Recruitment', 'HR_Team', 'HR_Team_Manager', 'HR_Manager'],
];

// Check if user has access to the page
if ($user_type != 'administrator') { 
    if (isset($employee_page_roles[$current_page_name])) {
        if (!in_array($user_role, $employee_page_roles[$current_page_name])) {
            header("Location: profile.php");
            exit();
        }
    }
}

// =================================================================================
// ROLE LISTS FOR EMPLOYEE MENU VISIBILITY
// =================================================================================

$can_see_employee_profile = [
    'Employee', 'Administrator', 'GM', 'HR_Senior_BP', 'HR_Operations', 'HR_Supervisor', 'HR_Recruitment', 'HR_Payroll',
    'Finance_Officer', 'Auditor', 'DPT_Manager', 'IT_Team', 'IT_Team_Manager',
    'HR_Team', 'HR_Team_Manager', 'Finance_Team', 'Finance_Team_Manager',
    'HR_Manager', 'Finance_Manager'
];

$can_see_vacation_history = [
    'Employee', 'Administrator', 'HR_Senior_BP', 'HR_Operations', 'HR_Supervisor', 'HR_Recruitment', 'HR_Payroll',
    'HR_Team', 'HR_Team_Manager', 'HR_Manager'
];

$can_see_loan_history = [
    'Employee', 'Administrator', 'HR_Senior_BP', 'HR_Operations', 'HR_Supervisor', 'HR_Recruitment', 'HR_Payroll',
    'Finance_Officer', 'HR_Team', 'HR_Team_Manager', 'Finance_Team', 'Finance_Team_Manager',
    'HR_Manager', 'Finance_Manager'
];

$can_see_assigned_assets = [
    'Employee', 'Administrator', 'GR_Officer', 'HR_Senior_BP', 'HR_Team', 'HR_Team_Manager', 'HR_Manager'
];

$can_see_payroll_slip = [
    'Employee', 'Administrator', 'HR_Senior_BP', 'HR_Payroll', 'Finance_Officer',
    'HR_Team', 'HR_Team_Manager', 'Finance_Team', 'Finance_Team_Manager',
    'HR_Manager', 'Finance_Manager'
];

$can_see_warnings = [
    'Employee', 'Administrator', 'HR_Senior_BP', 'HR_Operations', 'HR_Supervisor', 'HR_Recruitment',
    'HR_Team', 'HR_Team_Manager', 'HR_Manager'
];

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
        <li class="menu-title">Employee Information</li>

        <!-- Employee Profile -->
        <?php if (in_array($user_role, $can_see_employee_profile) || in_array($user_type, $can_see_employee_profile)): ?>
            <li><a href="<?= $profileLink ?>"><i class="fa fa-user-circle"></i><span><?= __('profile', 'My Profile') ?></span></a></li>
        <?php endif; ?>

        <!-- Vacation Information -->
        <?php if (in_array($user_role, $can_see_vacation_history) || in_array($user_type, $can_see_vacation_history)): ?>
            <li><a href="<?= $vacationHistoryLink ?>"><i class="fa fa-calendar-days"></i><span><?= __('vacation_history', 'Vacation History') ?></span></a></li>
        <?php endif; ?>

        <!-- Loan Information -->
        <?php if (in_array($user_role, $can_see_loan_history) || in_array($user_type, $can_see_loan_history)): ?>
            <li><a href="<?= $loanHistoryLink ?>"><i class="fa fa-money-bill-trend-up"></i><span><?= __('loan_history', 'Loan History') ?></span></a></li>
        <?php endif; ?>

        <!-- Assigned Assets -->
        <?php if (in_array($user_role, $can_see_assigned_assets) || in_array($user_type, $can_see_assigned_assets)): ?>
            <li><a href="<?= $assignedAssetsLink ?>"><i class="fa fa-object-group"></i><span><?= __('assigned_assets', 'Assigned Assets') ?></span></a></li>
        <?php endif; ?>

        <!-- Payroll Slip -->
        <?php if (in_array($user_role, $can_see_payroll_slip) || in_array($user_type, $can_see_payroll_slip)): ?>
            <li><a href="<?= $payrollSlipLink ?>"><i class="fa fa-receipt"></i><span><?= __('payroll_slip', 'Payroll Slip') ?></span></a></li>
        <?php endif; ?>

        <!-- Employee Warnings -->
        <?php if (in_array($user_role, $can_see_warnings) || in_array($user_type, $can_see_warnings)): ?>
            <li><a href="<?= $warningsLink ?>"><i class="fa fa-exclamation-triangle"></i><span><?= __('warnings', 'Disciplinary Records') ?></span></a></li>
        <?php endif; ?>

    </ul>
</div>
