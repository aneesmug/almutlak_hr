<?php
require_once __DIR__ . '/../init.php';
require_once __DIR__ . '/../session_check.php';
require_once __DIR__ . '/../dashboard_chart_helpers.php';

// Include shared functions to ensure __() translation helper is available (robust path resolution)
($functionPath = (function() {
    $candidates = [
        __DIR__ . '/../functions.php',
        dirname(__DIR__, 2) . '/includes/functions.php',
        dirname(__DIR__, 2) . '/functions.php',
    ];
    foreach ($candidates as $p) {
        if (file_exists($p)) return $p;
    }
    return null;
})()) && require_once $functionPath;

if (!function_exists('__')) {
    function __($key) { return $key; }
}

header('Content-Type: application/json');
ini_set('display_errors', '0');

// Regular employees don't get a dashboard - matches dashboard.php's own gate
if (($user_role ?? '') === 'Employee') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

// Same access-scope filters the dashboard page itself uses
$company_filter_alias = getCompanyFilterSQL('e.comp_no', true);
$department_filter_alias = getDepartmentFilterSQL('e.dept', true);
$employee_filter_alias = getEmployeeFilterSQL('e.emp_id', true);

$company_filter = getCompanyFilterSQL('comp_no', true);
$department_filter = getDepartmentFilterSQL('dept', true);
$employee_filter = getEmployeeFilterSQL('emp_id', true);

$has_explicit_scope_restrictions = function_exists('hasExplicitEmployeeScopeRestrictions')
    ? hasExplicitEmployeeScopeRestrictions(true)
    : (!empty($allowed_companies_array) || !empty($allowed_departments_array) || !empty($allowed_employees_array));

$can_see_all_employees = (
    function_exists('canSeeAllEmployeesByRole')
        ? canSeeAllEmployeesByRole(true)
        : (
            $is_system_admin ||
            $user_type == 'administrator' ||
            $user_dept == 5 ||
            $isHR ||
            $isDeptHr ||
            $user_dept == 1
        )
);

if (!$can_see_all_employees && !$has_explicit_scope_restrictions && !empty($user_dept)) {
    $escapedUserDept = mysqli_real_escape_string($conDB, $user_dept);
    $department_filter = " AND `dept`='" . $escapedUserDept . "'";
    $department_filter_alias = " AND `e`.`dept`='" . $escapedUserDept . "'";
}

$filters = [
    'department_ids' => isset($_POST['department_ids']) ? (array)$_POST['department_ids'] : [],
    'company_ids' => isset($_POST['company_ids']) ? (array)$_POST['company_ids'] : [],
    'sex' => isset($_POST['sex']) ? $_POST['sex'] : null,
    'country_id' => isset($_POST['country_id']) ? $_POST['country_id'] : null,
    'saudi' => isset($_POST['saudi']) ? $_POST['saudi'] : null,
];

$data = get_all_dashboard_chart_data(
    $conDB,
    $company_filter_alias . $department_filter_alias . $employee_filter_alias,
    $company_filter . $department_filter . $employee_filter,
    $filters,
    ($is_rtl ?? false)
);

echo json_encode(['success' => true, 'data' => $data], JSON_UNESCAPED_UNICODE);
