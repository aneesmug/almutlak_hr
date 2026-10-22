<?php
// Vacation salary base: which salary components are paid for vacation days.
// Configured in App Settings > Payroll Settings > Vacation Payroll ("vacation_salary_components",
// JSON array of the component keys below, same keys as deduction_base_components).
// Used everywhere vacation days are turned into money: encashment amount, settlement vacation
// salary (leaveHandler / all_settlements / vacation_report_details), payroll "Vacation Salary
// Benefit", EOS annual vacation salary and the employee header "Vacation Due" tile.
// While the setting has never been saved, vacation_salary_monthly_base() returns the caller's
// own (previous) base, so nothing changes until HR picks the components.

if (defined('VACATION_SALARY_HELPER_INCLUDED')) {
    return;
}
define('VACATION_SALARY_HELPER_INCLUDED', true);

// component key => emp_salary column
const VACATION_SALARY_COMPONENT_COLUMNS = [
    'basic_salary' => 'basic',
    'housing_allowance' => 'housing',
    'transport_allowance' => 'transport',
    'food_allowance' => 'food',
    'miscellaneous_allowance' => 'misc',
    'cashier_allowance' => 'cashier',
    'fuel_allowance' => 'fuel',
    'telephone_allowance' => 'tel',
    'other_allowance' => 'other',
    'guard_allowance' => 'guard',
];

// Selected component keys, or null when the setting was never saved (= keep old behaviour).
function vacation_salary_components($conDB = null) {
    static $cache = false;
    if ($cache !== false) return $cache;
    if ($conDB === null) {
        $conDB = $GLOBALS['conDB'] ?? null;
    }
    $cache = null;
    $res = $conDB ? @mysqli_query($conDB, "SELECT `setting_value` FROM `app_settings` WHERE `setting_name` = 'vacation_salary_components' LIMIT 1") : false;
    $row = $res ? mysqli_fetch_assoc($res) : null;
    $decoded = $row ? json_decode((string)$row['setting_value'], true) : null;
    if (is_array($decoded)) {
        $keys = array_values(array_intersect($decoded, array_keys(VACATION_SALARY_COMPONENT_COLUMNS)));
        // Basic Salary is always part of vacation salary (locked in App Settings)
        $cache = array_values(array_unique(array_merge(['basic_salary'], $keys)));
    }
    return $cache;
}

// Monthly base for vacation salary from one salary row. $row may use emp_salary column names
// (basic, housing, ...) or the component keys (basic_salary, housing_allowance, ...).
// $fallback = the base the caller used before this setting existed.
function vacation_salary_monthly_base(array $row, $fallback, $conDB = null) {
    $keys = vacation_salary_components($conDB);
    if ($keys === null) {
        return (float)$fallback;
    }
    $sum = 0.0;
    foreach ($keys as $key) {
        $col = VACATION_SALARY_COMPONENT_COLUMNS[$key];
        $sum += (float)($row[$key] ?? $row[$col] ?? 0);
    }
    return $sum;
}
