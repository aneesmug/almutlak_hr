<?php

if (defined('PAGE_ACCESS_HELPER_INCLUDED')) {
    return;
}
define('PAGE_ACCESS_HELPER_INCLUDED', true);

if (!function_exists('get_all_assignable_roles')) {
    /**
     * Canonical role taxonomy, mirrors $role_mapping / $dept_role_mapping in
     * includes/role_check.php. Kept as one static list so every page-access
     * checkbox grid (and any future one) enumerates the exact same roles.
     */
    function get_all_assignable_roles() {
        return [
            'Administrator', 'GM', 'HR_Senior_BP', 'HR_Operations', 'HR_Supervisor',
            'HR_Recruitment', 'HR_Payroll', 'Finance_Officer', 'Auditor', 'GR_Officer',
            'DPT_Manager', 'IT_Team', 'IT_Team_Manager', 'HR_Team', 'HR_Team_Manager',
            'Finance_Team', 'Finance_Team_Manager', 'Executive_Team', 'Executive_Team_Manager',
            'Employee', 'HR_Manager', 'Finance_Manager',
        ];
    }
}

if (!function_exists('get_default_page_access_roles')) {
    /**
     * Default per-page role list, seeded from the values that used to be hardcoded
     * directly in includes/main_menu.php ($page_roles). Used to pre-populate the
     * 'page_role_access' app_settings row the first time it's created, so behavior
     * is unchanged until an admin edits it from App Settings > Page Access.
     */
    function get_default_page_access_roles() {
        return [
            'dashboard.php' => ['Administrator', 'GM', 'HR_Senior_BP', 'HR_Operations', 'HR_Supervisor', 'HR_Recruitment', 'HR_Payroll', 'Finance_Officer', 'Auditor', 'GR_Officer', 'DPT_Manager', 'IT_Team', 'IT_Team_Manager', 'HR_Team', 'HR_Team_Manager', 'Finance_Team', 'Finance_Team_Manager', 'Executive_Team', 'Executive_Team_Manager', 'Employee', 'HR_Manager', 'Finance_Manager'],
            'dashboardgm.php' => ['GM'],
            'add_new_employee.php' => ['Administrator', 'HR_Operations', 'HR_Recruitment', 'HR_Payroll', 'HR_Senior_BP'],
            'reg_employee.php' => ['Administrator', 'HR_Senior_BP', 'HR_Operations', 'HR_Supervisor', 'HR_Recruitment', 'HR_Payroll', 'Finance_Officer', 'Auditor', 'DPT_Manager', 'IT_Team', 'IT_Team_Manager', 'HR_Team', 'HR_Team_Manager', 'Finance_Team', 'Finance_Team_Manager', 'HR_Manager', 'Finance_Manager'],
            'emp_temp_contant.php' => ['Administrator', 'HR_Senior_BP', 'HR_Operations', 'HR_Supervisor', 'HR_Recruitment', 'HR_Payroll', 'HR_Team', 'HR_Team_Manager', 'HR_Manager'],
            'employee_audit_gen.php' => ['Administrator'],
            'employee_salary_report.php' => ['Administrator'],
            'generate_payroll.php' => ['Administrator', 'HR_Senior_BP', 'HR_Payroll', 'HR_Manager'],
            'all_applied_vac.php' => ['Administrator', 'GM', 'HR_Senior_BP', 'HR_Operations', 'HR_Supervisor', 'HR_Recruitment', 'HR_Payroll', 'Finance_Officer', 'Auditor', 'GR_Officer', 'DPT_Manager', 'IT_Team', 'IT_Team_Manager', 'HR_Team', 'HR_Team_Manager', 'Finance_Team', 'Finance_Team_Manager', 'Employee', 'HR_Manager', 'Finance_Manager'],
            'all_applied_business_trip.php' => ['Administrator'],
            'all_applied_loan.php' => ['Administrator', 'GM', 'HR_Senior_BP', 'HR_Operations', 'HR_Supervisor', 'HR_Recruitment', 'HR_Payroll', 'Finance_Officer', 'Auditor', 'DPT_Manager', 'HR_Team', 'HR_Team_Manager', 'Finance_Team', 'Finance_Team_Manager', 'Employee', 'HR_Manager', 'Finance_Manager', 'IT_Team_Manager'],
            'all_applied_salary_increment.php' => ['Administrator'],
            'all_settlements.php' => ['Administrator', 'GM', 'HR_Senior_BP', 'HR_Operations', 'HR_Supervisor', 'HR_Recruitment', 'HR_Payroll', 'Finance_Officer', 'Auditor', 'DPT_Manager', 'HR_Team', 'HR_Team_Manager', 'Finance_Team', 'Finance_Team_Manager', 'HR_Manager', 'Finance_Manager'],
            'all_payroll_approvals.php' => ['Administrator', 'GM', 'HR_Senior_BP', 'HR_Operations', 'HR_Supervisor', 'HR_Recruitment', 'HR_Payroll', 'Finance_Officer', 'Auditor', 'DPT_Manager', 'HR_Team', 'HR_Team_Manager', 'Finance_Team', 'Finance_Team_Manager', 'HR_Manager', 'Finance_Manager'],
            'rejoin_approvals.php' => ['Administrator', 'GM', 'HR_Senior_BP', 'HR_Operations', 'HR_Supervisor', 'HR_Recruitment', 'HR_Payroll', 'Finance_Officer', 'Auditor', 'GR_Officer', 'DPT_Manager', 'IT_Team', 'IT_Team_Manager', 'HR_Team', 'HR_Team_Manager', 'Finance_Team', 'Finance_Team_Manager', 'HR_Manager', 'Finance_Manager'],
            'all_resignations.php' => ['Administrator', 'GM', 'HR_Senior_BP', 'HR_Operations', 'HR_Supervisor', 'HR_Recruitment', 'HR_Payroll', 'DPT_Manager', 'IT_Team_Manager', 'HR_Team', 'HR_Team_Manager', 'HR_Manager'],
            'add_manual_loan.php' => ['Administrator', 'HR_Senior_BP', 'HR_Payroll', 'Finance_Officer', 'Auditor', 'HR_Team', 'HR_Team_Manager', 'Finance_Team', 'Finance_Team_Manager', 'HR_Manager', 'Finance_Manager'],
            'import_loan_opening_balance.php' => ['Administrator'],
            'all_cars.php' => ['Administrator', 'GR_Officer'],
            'all_locations.php' => ['Administrator', 'GR_Officer'],
            'all_machines.php' => ['Administrator', 'GR_Officer'],
            'asset_inventory.php' => ['Administrator', 'IT_Team', 'IT_Team_Manager', 'GR_Officer'],
            'all_menu_item.php' => ['Administrator'],
            'all_requests.php' => ['Administrator', 'GM', 'HR_Senior_BP', 'HR_Operations', 'HR_Supervisor', 'HR_Recruitment', 'HR_Payroll', 'Finance_Officer', 'Auditor', 'GR_Officer', 'DPT_Manager', 'IT_Team', 'IT_Team_Manager', 'HR_Team', 'HR_Team_Manager', 'Finance_Team', 'Finance_Team_Manager', 'HR_Manager', 'Finance_Manager'],
            'all_general_requests.php' => ['Administrator', 'GM', 'HR_Senior_BP', 'HR_Operations', 'HR_Supervisor', 'HR_Recruitment', 'HR_Payroll', 'Finance_Officer', 'Auditor', 'GR_Officer', 'DPT_Manager', 'IT_Team', 'IT_Team_Manager', 'HR_Team', 'HR_Team_Manager', 'Finance_Team', 'Finance_Team_Manager', 'HR_Manager', 'Finance_Manager'],
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
            'reports.php' => ['Administrator', 'GM', 'Auditor', 'HR_Senior_BP', 'HR_Payroll', 'HR_Operations', 'HR_Supervisor', 'Finance_Officer', 'DPT_Manager', 'HR_Manager', 'Finance_Manager', 'HR_Recruitment', 'IT_Team_Manager'],
            'manage_employee_supervisors.php' => ['Administrator', 'HR_Senior_BP'],
            'manage_holidays.php' => ['Administrator', 'HR_Senior_BP', 'HR_Team', 'HR_Team_Manager', 'HR_Manager'],
            'vacation_dates_by_inv.php' => ['Administrator', 'HR_Senior_BP'],
            'vacation_balance_history.php' => ['Administrator'],
            'diagnose_double_deduction.php' => ['Administrator'],
            'fix_double_deduction.php' => ['Administrator'],
            'app_settings.php' => ['Administrator'],
        ];
    }
}

if (!function_exists('get_page_access_labels')) {
    /** Friendly names for the Page Access admin UI, same keys as get_default_page_access_roles(). */
    function get_page_access_labels() {
        return [
            'dashboard.php' => 'Dashboard',
            'dashboardgm.php' => 'GM Dashboard',
            'add_new_employee.php' => 'Add New Employee',
            'reg_employee.php' => 'All Employees',
            'emp_temp_contant.php' => 'Temporary Contracts / Content Updates',
            'employee_audit_gen.php' => 'Yearly EOS Audit',
            'employee_salary_report.php' => 'Employee Salary Report',
            'generate_payroll.php' => 'Generate Payroll',
            'all_applied_vac.php' => 'All Applied Vacations',
            'all_applied_business_trip.php' => 'All Applied Business Trips',
            'all_applied_loan.php' => 'All Applied Loans',
            'all_applied_salary_increment.php' => 'All Applied Salary Increments',
            'all_settlements.php' => 'All Settlements',
            'all_payroll_approvals.php' => 'All Payroll Approvals',
            'rejoin_approvals.php' => 'Rejoin Approvals',
            'all_resignations.php' => 'All Resignations',
            'add_manual_loan.php' => 'Add Manual Loan',
            'import_loan_opening_balance.php' => 'Import Loan Opening Balance',
            'all_cars.php' => 'Cars Management',
            'all_locations.php' => 'Locations Management',
            'all_machines.php' => 'Machines Management',
            'asset_inventory.php' => 'Asset Inventory',
            'all_menu_item.php' => 'Menu Items',
            'all_requests.php' => 'Smart Requests',
            'all_general_requests.php' => 'General Requests',
            'send_announcement.php' => 'Send Announcement',
            'vouchers.php' => 'Vouchers',
            'all_user_invoices.php' => 'User Invoices',
            'all_users.php' => 'System Users',
            'file_manager.php' => 'File Manager',
            'gallery.php' => 'Gallery',
            'language.php' => 'Language Manager',
            'log_activity.php' => 'Activity Log (legacy)',
            'view_activity_logs.php' => 'Activity Logs',
            'manual_vacation.php' => 'Import Vacation Balance',
            'import_iqama_exp.php' => 'Import Iqama Expiry',
            'employee_evaluation.php' => 'Employee Evaluation',
            'all_employee_evaluations.php' => 'All Employee Evaluations',
            'reports.php' => 'Reports',
            'manage_employee_supervisors.php' => 'Manage Employee Supervisors',
            'manage_holidays.php' => 'Manage Holidays',
            'vacation_dates_by_inv.php' => 'Vacation Dates Editor',
            'vacation_balance_history.php' => 'Vacation Balance History',
            'diagnose_double_deduction.php' => 'Diagnose Double Deduction',
            'fix_double_deduction.php' => 'Fix Double Deduction',
            'app_settings.php' => 'App Settings',
        ];
    }
}

if (!function_exists('decode_page_access_map')) {
    function decode_page_access_map($rawValue) {
        if (!is_string($rawValue) || trim($rawValue) === '') {
            return [];
        }

        $decoded = json_decode($rawValue, true);
        if (!is_array($decoded)) {
            return [];
        }

        $allowedPages = array_fill_keys(array_keys(get_default_page_access_roles()), true);
        $allowedRoles = array_fill_keys(get_all_assignable_roles(), true);

        $normalized = [];
        foreach ($decoded as $page => $roles) {
            $pageKey = trim((string)$page);
            if ($pageKey === '' || !isset($allowedPages[$pageKey]) || !is_array($roles)) {
                continue;
            }
            $cleanRoles = [];
            foreach ($roles as $role) {
                $roleKey = trim((string)$role);
                if ($roleKey !== '' && isset($allowedRoles[$roleKey])) {
                    $cleanRoles[$roleKey] = true;
                }
            }
            $normalized[$pageKey] = array_keys($cleanRoles);
        }

        return $normalized;
    }
}

if (!function_exists('get_page_access_map')) {
    /**
     * Effective per-page allowed-role map: DB overrides layered on top of the
     * hardcoded defaults, so a page missing from the saved JSON (new page added
     * after the last save) still has a sane default instead of blocking everyone.
     */
    function get_page_access_map($conDB) {
        $defaults = get_default_page_access_roles();

        $raw = '';
        if (function_exists('get_setting')) {
            $raw = (string)get_setting($conDB, 'page_role_access', '');
        }
        $saved = decode_page_access_map($raw);

        return array_merge($defaults, $saved);
    }
}

if (!function_exists('page_role_allowed')) {
    function page_role_allowed($conDB, $pageKey, $userRole = '', $userType = '', $isSystemAdmin = false) {
        $role = strtolower(trim((string)$userRole));
        $type = strtolower(trim((string)$userType));
        if ($isSystemAdmin || $role === 'administrator' || $type === 'administrator') {
            return true;
        }

        $map = get_page_access_map($conDB);
        if (!isset($map[$pageKey])) {
            return true;
        }

        return in_array($userRole, $map[$pageKey], true) || in_array($userType, $map[$pageKey], true);
    }
}
