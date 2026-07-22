<?php

if (defined('SPECIAL_ACCESS_HELPER_INCLUDED')) {
    return;
}
define('SPECIAL_ACCESS_HELPER_INCLUDED', true);

if (!function_exists('get_special_access_labels')) {
    function get_special_access_labels() {
        return [
            'cancel_vacation_requests' => 'Cancel Submitted Vacation Requests',
            'view_vacation_balance_history' => 'View Vacation Balance History',
            'view_remaining_balance_in_report' => 'Show Remaining Balance in Vacation Report',
            'manage_employee_request_block' => 'Block/Unblock Employee from All Requests',
            'manage_employee_request_type_block' => 'Block Employee by Specific Request Type',
            'manage_global_request_blocks' => 'Manage Request Type Blocks (Global, All Employees)',
            'manage_department_settings' => 'Access App Settings - Departments Tab',
            'manage_job_title_settings' => 'Access App Settings - Job Titles Tab',
            'payroll_checklist_upload_excel' => 'Payroll Checklist Report: Upload Payroll Excel Button',
            'payroll_checklist_review_import' => 'Payroll Checklist Report: Review Manager File & Import Button',
            'payroll_checklist_export_excel' => 'Payroll Checklist Report: Export Excel Button',
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
        ];
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
            'smart_request' => 'Smart Request',
            'loan_request' => 'Loan Request',
            'vacation_annual' => 'Vacation - Fly (Annual)',
            'vacation_emergency' => 'Vacation - Fly (Emergency)',
            'vacation_local' => 'Vacation - Local Vacation',
            'vacation_encashed' => 'Vacation - Encashed',
            'excuse_leave' => 'Leave / Excuse (Sick, Marriage, Hajj, etc.)',
            'resignation_request' => 'Resignation Request',
            'rejoin_request' => 'Rejoin Request',
            'general_request' => 'General Request',
            'business_trip' => 'Business Trip',
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
