<?php

if (defined('REPORT_PERMISSIONS_HELPER_INCLUDED')) {
    return;
}
define('REPORT_PERMISSIONS_HELPER_INCLUDED', true);

if (!function_exists('get_report_type_labels')) {
    function get_report_type_labels() {
        return [
            'employee' => 'Employee Report',
            'vacation' => 'Vacation Report',
            'rejoin' => 'Employee Rejoin Report',
            'loan' => 'Loan Report',
            'salary_increment' => 'Salary Increment Report',
            'salary' => 'Salary Report',
            'payroll' => 'Payroll Report',
            'attendance' => 'Attendance Report',
            'document' => 'Document Report',
            'assets' => 'Assets Report',
            'assets_list' => 'Assets List',
            'evaluation' => 'Evaluation Report',
            'resignation' => 'Resignation Report',
            'terminated_employees' => 'Terminated Employees',
            'eos' => 'End of Service',
            'dept_comparison' => 'Department Comparison Report',
            'country_company_comparison' => 'Country & Company Comparison Report',
            'custom' => 'Custom Report',
        ];
    }
}

if (!function_exists('decode_report_visibility_map')) {
    function decode_report_visibility_map($rawValue) {
        if (!is_string($rawValue) || trim($rawValue) === '') {
            return [];
        }

        $decoded = json_decode($rawValue, true);
        if (!is_array($decoded)) {
            return [];
        }

        $normalized = [];
        foreach ($decoded as $empId => $types) {
            $key = trim((string)$empId);
            if ($key === '') {
                continue;
            }
            $normalized[$key] = normalize_report_type_list($types);
        }

        return $normalized;
    }
}

if (!function_exists('normalize_report_type_list')) {
    function normalize_report_type_list($types) {
        $allowed = array_keys(get_report_type_labels());
        $allowedMap = array_fill_keys($allowed, true);

        if (!is_array($types)) {
            return [];
        }

        $normalized = [];
        foreach ($types as $type) {
            $reportType = trim((string)$type);
            if ($reportType === '' || !isset($allowedMap[$reportType])) {
                continue;
            }
            $normalized[$reportType] = true;
        }

        return array_keys($normalized);
    }
}

if (!function_exists('get_report_visibility_map')) {
    function get_report_visibility_map($conDB) {
        $raw = '';
        if (function_exists('get_setting')) {
            $raw = (string)get_setting($conDB, 'report_visibility_by_user', '{}');
        }
        return decode_report_visibility_map($raw);
    }
}

if (!function_exists('get_allowed_report_types_for_user')) {
    function get_allowed_report_types_for_user($conDB, $empId, $userRole = '', $userType = '', $isSystemAdmin = false) {
        $allReportTypes = array_keys(get_report_type_labels());

        $role = strtolower(trim((string)$userRole));
        $type = strtolower(trim((string)$userType));
        if ($isSystemAdmin || $role === 'administrator' || $type === 'administrator') {
            return $allReportTypes;
        }

        $empKey = trim((string)$empId);
        if ($empKey === '') {
            return $allReportTypes;
        }

        $map = get_report_visibility_map($conDB);

        if (array_key_exists($empKey, $map)) {
            return normalize_report_type_list($map[$empKey]);
        }

        // Backward-compatible default: no explicit mapping means full list.
        return $allReportTypes;
    }
}
