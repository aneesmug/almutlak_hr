<?php

if (defined('SPECIAL_ACCESS_HELPER_INCLUDED')) {
    return;
}
define('SPECIAL_ACCESS_HELPER_INCLUDED', true);

if (!function_exists('get_special_access_labels')) {
    function get_special_access_labels() {
        return [
            'cancel_vacation_requests' => 'Cancel Submitted Vacation Requests',
            'cancel_smart_requests' => 'Cancel Submitted Smart Requests',
            'cancel_general_requests' => 'Cancel Submitted General Requests',
            'cancel_loan_requests' => 'Cancel Submitted Loan Requests',
            'cancel_resignation_requests' => 'Cancel Submitted Resignation Requests',
            'cancel_rejoin_requests' => 'Cancel Submitted Rejoin Requests',
            'cancel_business_trip_requests' => 'Cancel Submitted Business Trip Requests',
            'cancel_salary_increment_requests' => 'Cancel Submitted Salary Increment Requests',
            'add_business_trip_manual_allowance' => 'Business Trip: Add Manual Allowance (Taxi, Parking, etc.)',
            'view_vacation_balance_history' => 'View Vacation Balance History',
            'view_remaining_balance_in_report' => 'Show Remaining Balance in Vacation Report',
            'manage_employee_request_block' => 'Block/Unblock Employee from All Requests',
            'manage_employee_request_type_block' => 'Block Employee by Specific Request Type',
            'manage_global_request_blocks' => 'Manage Request Type Blocks (Global, All Employees)',
            'manage_department_settings' => 'Access App Settings - Departments Tab',
            'manage_job_title_settings' => 'Access App Settings - Job Titles Tab',
            'manage_location_settings' => 'Access App Settings - Locations Tab',
            'manage_sub_department_settings' => 'Access App Settings - Sub-Departments Tab',
            'payroll_checklist_upload_excel' => 'Payroll Checklist Report: Upload Payroll Excel Button',
            'payroll_checklist_review_import' => 'Payroll Checklist Report: Review Manager File & Import Button',
            'payroll_checklist_export_excel' => 'Payroll Checklist Report: Export Excel Button',
            'direct_rejoin_bypass_approval' => 'Directly Rejoin Employee From Active Vacation (Bypass Approval Chain)',
            'ungenerate_payroll' => 'Payroll: Un-Generate Payroll Button',
            'assign_payroll_supervisor' => 'Payroll: Assign Direct Supervisor for Payroll Button',
            'manage_loan_settings' => 'Access App Settings - Loan Settings Tab',
            'manage_vacation_payroll_settings' => 'Access App Settings - Vacation Payroll Settings Tab',
            'manage_overtime_settings' => 'Access App Settings - Overtime Settings Tab',
            'manage_deduction_settings' => 'Access App Settings - Deduction Settings Tab',
            'manage_salary_increment_settings' => 'Access App Settings - Salary Increment Settings Tab',
            'manage_vacation_salary_below_min_days' => 'Employee Master: Allow Vacation Salary Payout Below Minimum Days',
            'view_employee_eos_value' => 'Employee Master: View End of Service (EOS) Estimated Value',
            'view_employee_salary_value' => 'Employee Master: View Salary',
            'view_employee_additional_info' => 'Employee Master: View Additional Information Tab',
            'view_employee_other_income' => 'Employee Master: View & Manage Other Income (Scheduled Bonus/Income)',
            'access_ctc_report' => 'Reports: CTC (Cost To Company) Report',
            'view_all_employees' => 'View All Employees (Cross-Department/Company Access)',
            'view_employee_banking_details' => 'Employee Master: View Banking/IBAN/GOSI Details',
            'view_employee_documents' => 'Employee Master: View Uploaded Documents (Passport/Iqama, etc.)',
            'request_employee_transfer' => 'Employee Master: Request Employee Transfer (Bypass Direct-Supervisor Requirement)',
        ] + get_special_access_page_labels();
    }
}

if (!function_exists('get_special_access_page_labels')) {
    /**
     * Pages normally blocked for plain employees (emp_type/user_type = 'employee').
     * Granting one of these keys via Special Access lets that page's own
     * `$isEmployee` redirect-guard be bypassed for that specific employee only,
     * without changing their role or giving them any other management ability.
     */
    function get_special_access_page_labels() {
        return [
            'access_all_applied_vac' => 'Access Page: All Applied Vacations',
            'access_all_applied_loan' => 'Access Page: All Applied Loans',
            'access_all_applied_business_trip' => 'Access Page: All Applied Business Trips',
            'access_all_resignations' => 'Access Page: All Resignations',
            'access_all_settlements' => 'Access Page: All Settlements',
            'access_all_payroll_approvals' => 'Access Page: All Payroll Approvals',
            'access_payroll_checklist_report' => 'Access Page: Payroll Checklist Report',
            'access_payroll_status_history' => 'Access Page: Payroll Status History',
            'access_loan_report_details' => 'Access Page: Loan Report Details',
            'access_settlement_status_history' => 'Access Page: Settlement Status History',
            'access_vacation_status_history' => 'Access Page: Vacation Status History',
            'access_business_trip_status_history' => 'Access Page: Business Trip Status History',
            'access_all_applied_salary_increment' => 'Access Page: All Applied Salary Increments',
            'access_salary_increment_status_history' => 'Access Page: Salary Increment Status History',
            'access_edit_employee' => 'Access Page: Edit Employee',
            'access_all_applied_employee_transfers' => 'Access Page: All Employee Transfer Requests',
            'access_import_medical_insurance' => 'Access Page: Import Medical Insurance',
            'access_import_loan_opening_balance' => 'Access Page: Import Loan Opening Balance',
            'access_import_iqama_exp' => 'Access Page: Import Iqama Expiry',
        ];
    }
}

if (!function_exists('get_special_access_categories')) {
    /**
     * Groups every Special Access key into a display category for the App Settings UI.
     * Purely presentational (grouping/icon/order) - has no effect on what a key
     * actually grants. New keys that aren't listed here fall back to 'Other' so
     * nothing breaks if this list drifts out of sync with get_special_access_labels().
     */
    function get_special_access_categories() {
        return [
            'Cancel Submitted Requests' => [
                'icon' => 'fa-ban',
                'keys' => [
                    'cancel_vacation_requests',
                    'cancel_smart_requests',
                    'cancel_general_requests',
                    'cancel_loan_requests',
                    'cancel_resignation_requests',
                    'cancel_rejoin_requests',
                    'cancel_business_trip_requests',
                    'cancel_salary_increment_requests',
                ],
            ],
            'Page Access' => [
                'icon' => 'fa-door-open',
                'keys' => array_keys(get_special_access_page_labels()),
            ],
            'Employee Master' => [
                'icon' => 'fa-id-badge',
                'keys' => [
                    'view_all_employees',
                    'view_employee_eos_value',
                    'view_employee_salary_value',
                    'view_employee_additional_info',
                    'view_employee_other_income',
                    'access_ctc_report',
                    'view_employee_banking_details',
                    'view_employee_documents',
                    'manage_vacation_salary_below_min_days',
                    'manage_employee_request_block',
                    'manage_employee_request_type_block',
                    'request_employee_transfer',
                ],
            ],
            'Vacation Visibility' => [
                'icon' => 'fa-umbrella-beach',
                'keys' => [
                    'view_vacation_balance_history',
                    'view_remaining_balance_in_report',
                ],
            ],
            'Payroll Checklist Report' => [
                'icon' => 'fa-clipboard-check',
                'keys' => [
                    'payroll_checklist_upload_excel',
                    'payroll_checklist_review_import',
                    'payroll_checklist_export_excel',
                ],
            ],
            'App Settings Tabs' => [
                'icon' => 'fa-cogs',
                'keys' => [
                    'manage_department_settings',
                    'manage_job_title_settings',
                    'manage_location_settings',
                    'manage_sub_department_settings',
                    'manage_global_request_blocks',
                    'manage_loan_settings',
                    'manage_vacation_payroll_settings',
                    'manage_overtime_settings',
                    'manage_deduction_settings',
                    'manage_salary_increment_settings',
                ],
            ],
            'Business Trip' => [
                'icon' => 'fa-plane',
                'keys' => [
                    'add_business_trip_manual_allowance',
                ],
            ],
            'Other Special Actions' => [
                'icon' => 'fa-star',
                'keys' => [
                    'direct_rejoin_bypass_approval',
                    'ungenerate_payroll',
                    'assign_payroll_supervisor',
                ],
            ],
        ];
    }
}

if (!function_exists('get_special_access_key_category')) {
    function get_special_access_key_category($key) {
        static $lookup = null;
        if ($lookup === null) {
            $lookup = [];
            foreach (get_special_access_categories() as $categoryName => $meta) {
                foreach ($meta['keys'] as $k) {
                    $lookup[$k] = $categoryName;
                }
            }
        }
        return $lookup[$key] ?? 'Other';
    }
}

if (!function_exists('decode_special_access_map')) {
    function decode_special_access_map($rawValue) {
        if (!is_string($rawValue) || trim($rawValue) === '') {
            return [];
        }

        $decoded = json_decode($rawValue, true);
        if (!is_array($decoded)) {
            return [];
        }

        $normalized = [];
        foreach ($decoded as $empId => $keys) {
            $key = trim((string)$empId);
            if ($key === '') {
                continue;
            }
            $normalized[$key] = normalize_special_access_list($keys);
        }

        return $normalized;
    }
}

if (!function_exists('normalize_special_access_list')) {
    function normalize_special_access_list($keys) {
        $allowed = array_keys(get_special_access_labels());
        $allowedMap = array_fill_keys($allowed, true);

        if (!is_array($keys)) {
            return [];
        }

        $normalized = [];
        foreach ($keys as $accessKey) {
            $key = trim((string)$accessKey);
            if ($key === '' || !isset($allowedMap[$key])) {
                continue;
            }
            $normalized[$key] = true;
        }

        return array_keys($normalized);
    }
}

if (!function_exists('get_special_access_map')) {
    function get_special_access_map($conDB) {
        $raw = '';
        if (function_exists('get_setting')) {
            $raw = (string)get_setting($conDB, 'special_access_by_user', '{}');
        }
        return decode_special_access_map($raw);
    }
}

if (!function_exists('user_has_special_access')) {
    function user_has_special_access($conDB, $empId, $accessKey, $userRole = '', $userType = '', $isSystemAdmin = false) {
        $role = strtolower(trim((string)$userRole));
        $type = strtolower(trim((string)$userType));
        if ($isSystemAdmin || $role === 'administrator' || $type === 'administrator') {
            return true;
        }

        $empKey = trim((string)$empId);
        if ($empKey === '') {
            return false;
        }

        $map = get_special_access_map($conDB);
        if (!array_key_exists($empKey, $map)) {
            return false;
        }

        return in_array($accessKey, $map[$empKey], true);
    }
}

if (!function_exists('get_blockable_request_type_labels')) {
    function get_blockable_request_type_labels() {
        return [
            'smart_request' => __('smart_request', 'Smart Request'),
            'loan_request' => __('loan_request', 'Loan Request'),
            'vacation_annual' => __('vacation_annual_request_type', 'Vacation - Fly (Annual)'),
            'vacation_emergency' => __('vacation_emergency_request_type', 'Vacation - Fly (Emergency)'),
            'vacation_local' => __('vacation_local_request_type', 'Vacation - Local Vacation'),
            'vacation_encashed' => __('vacation_encashed_request_type', 'Vacation - Encashed'),
            'excuse_leave' => __('excuse_leave_request_type', 'Leave / Excuse (Sick, Marriage, Hajj, etc.)'),
            'resignation_request' => __('resignation_request', 'Resignation Request'),
            'rejoin_request' => __('rejoin_request', 'Rejoin Request'),
            'general_request' => __('general_request', 'General Request'),
            'business_trip' => __('business_trip', 'Business Trip'),
            'salary_increment' => __('salary_increment', 'Salary Increment'),
        ];
    }
}

if (!function_exists('decode_blocked_request_types')) {
    function decode_blocked_request_types($rawValue) {
        if (!is_string($rawValue) || trim($rawValue) === '') {
            return [];
        }

        $decoded = json_decode($rawValue, true);
        if (!is_array($decoded)) {
            return [];
        }

        $allowedMap = array_fill_keys(array_keys(get_blockable_request_type_labels()), true);
        $normalized = [];
        foreach ($decoded as $type) {
            $key = trim((string)$type);
            if ($key === '' || !isset($allowedMap[$key])) {
                continue;
            }
            $normalized[$key] = true;
        }

        return array_keys($normalized);
    }
}

if (!function_exists('resolve_vacation_block_type_key')) {
    function resolve_vacation_block_type_key($vacType, $flyType = '') {
        $vacType = trim((string)$vacType);
        $flyType = strtolower(trim((string)$flyType));

        if ($vacType === 'Local Vacation') {
            return 'vacation_local';
        }
        if ($vacType === 'Encashed') {
            return 'vacation_encashed';
        }
        if ($flyType === 'emergency') {
            return 'vacation_emergency';
        }
        // Default: 'Fly' with no/annual fly_type, or any other unrecognized vac_type.
        return 'vacation_annual';
    }
}

if (!function_exists('get_global_blocked_request_types')) {
    function get_global_blocked_request_types($conDB) {
        static $cache = null;
        if ($cache !== null) {
            return $cache;
        }

        $raw = '';
        if (function_exists('get_setting')) {
            $raw = (string)get_setting($conDB, 'globally_blocked_request_types', '[]');
        }
        $cache = decode_blocked_request_types($raw);
        return $cache;
    }
}

if (!function_exists('is_employee_request_blocked')) {
    function is_employee_request_blocked($conDB, $empId, $typeKey) {
        $empId = trim((string)$empId);
        if ($empId === '') {
            return ['blocked' => false, 'reason' => ''];
        }

        $stmt = mysqli_prepare($conDB, "SELECT requests_blocked, blocked_request_types FROM employees WHERE emp_id = ? LIMIT 1");
        mysqli_stmt_bind_param($stmt, 's', $empId);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $row = $result ? mysqli_fetch_assoc($result) : null;
        mysqli_stmt_close($stmt);

        if (!$row) {
            return ['blocked' => false, 'reason' => ''];
        }

        if ((string)($row['requests_blocked'] ?? '0') === '1') {
            return ['blocked' => true, 'reason' => 'You are currently blocked from submitting any requests. Please contact HR.'];
        }

        $labels = get_blockable_request_type_labels();
        $label = $labels[$typeKey] ?? $typeKey;

        $globallyBlocked = in_array($typeKey, get_global_blocked_request_types($conDB), true);
        $employeeOverride = in_array($typeKey, decode_blocked_request_types($row['blocked_request_types'] ?? ''), true);
        // NOTE: `xor` has lower precedence than `=` in PHP, so `$x = $a xor $b` would silently
        // assign only $a. Use !== (boolean XOR) instead, evaluated fully before assignment.
        $effectiveBlocked = ($globallyBlocked !== $employeeOverride);

        if ($effectiveBlocked) {
            if ($globallyBlocked) {
                return ['blocked' => true, 'reason' => "{$label} requests are currently disabled for all employees. Please contact HR."];
            }
            return ['blocked' => true, 'reason' => "You are currently blocked from submitting {$label} requests. Please contact HR."];
        }

        return ['blocked' => false, 'reason' => ''];
    }
}
