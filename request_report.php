<?php
/**
 * One report page for every request type:
 *   request_report.php?type=vacation&id=..&emp_id=..
 *   request_report.php?type=loan&id=..&emp_id=..
 *   request_report.php?type=business_trip&id=..&emp_id=..
 *   request_report.php?type=resignation&id=..&emp_id=..
 *   request_report.php?type=salary_increment&inv=<request_inv_no>
 *   request_report.php?type=settlement&id=<settlement id>
 *
 * The shared layout (includes/report_layout.php: header, employee banner, approval chain, signatures,
 * footer, print) wraps the body; each type's data, access check and body live in includes/reports/<type>.php.
 */

require_once __DIR__ . '/includes/session_check.php';
require_once __DIR__ . '/includes/report_layout.php';

require_once __DIR__ . '/includes/page_access_helper.php';

// type => [body part, the page it replaces]. The page name keeps that page's access rules
// (employee allow-list, App Settings page-role map, 'access_<page>' special-access grant).
$report_types = [
    'vacation'      => ['vacation.php', 'vacation_report_details.php'],
    'loan'          => ['loan.php', 'loan_report_details.php'],
    'business_trip' => ['business_trip.php', 'business_trip_report_details.php'],
    'resignation'   => ['resignation.php', 'resignation_report_details.php'],
    'salary_increment' => ['salary_increment.php', 'all_applied_salary_increment.php'],
    'settlement'    => ['settlement.php', 'all_settlements.php'],
];

$report_type = strtolower(trim((string)($_GET['type'] ?? '')));
if (!isset($report_types[$report_type]) || !is_file(__DIR__ . '/includes/reports/' . $report_types[$report_type][0])) {
    http_response_code(404);
    die('Unknown report type.');
}
[$report_part, $report_legacy_page] = $report_types[$report_type];

// ---- same page access as the old <type>_report_details.php ----
$report_special_grant = user_has_special_access($conDB, $empid ?? '', 'access_' . pathinfo($report_legacy_page, PATHINFO_FILENAME), $user_role ?? '', $user_type ?? '', $is_system_admin ?? false);
if (($user_type ?? null) === 'employee' && !in_array($report_legacy_page, EMPLOYEE_ALLOWED_PAGES, true) && !$report_special_grant) {
    header('Location: ./profile.php');
    exit();
}
if (($user_type ?? '') !== 'administrator' && !$report_special_grant
    && !page_role_allowed($conDB, $report_legacy_page, $user_role ?? '', $user_type ?? '', $is_system_admin ?? false)) {
    header('Location: error403.php?page=' . urlencode($report_legacy_page));
    exit();
}

include __DIR__ . '/includes/reports/' . $report_part;
