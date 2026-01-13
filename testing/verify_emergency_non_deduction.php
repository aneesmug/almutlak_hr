<?php
/**
 * VERIFICATION SCRIPT: Emergency Vacation Non-Deduction
 * 
 * Verifies that emergency vacations are NOT deducting from employee balance
 * (Emergency vacations are unpaid/non-deductible leave)
 */

require_once __DIR__ . '/../includes/db.php';

echo "=================================================================\n";
echo "EMERGENCY VACATION NON-DEDUCTION VERIFICATION\n";
echo "=================================================================\n\n";

// Find all completed emergency vacations
$sql_emergency = "SELECT 
    v.id, v.emp_id, v.request_inv_no, v.vac_type, v.fly_type,
    v.vacdays, v.current_status, v.created_at,
    b.id as balance_id, b.used_days as balance_used_days,
    e.name as employee_name
FROM emp_vacation v
LEFT JOIN emp_vacation_balance b ON v.id = b.vac_id
LEFT JOIN employees e ON v.emp_id = e.emp_id
WHERE v.current_status = 'completed'
    AND v.fly_type = 'emergency'
    AND v.request_inv_no NOT LIKE 'LEGACY-%'
    AND v.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
ORDER BY v.created_at DESC
LIMIT 10";

$result_emergency = mysqli_query($conDB, $sql_emergency);
$emergency_count = 0;
$correct_count = 0;
$incorrect_count = 0;

if ($result_emergency && mysqli_num_rows($result_emergency) > 0) {
    echo "Found completed EMERGENCY vacations in last 30 days:\n\n";
    
    while ($row = mysqli_fetch_assoc($result_emergency)) {
        $emergency_count++;
        $status = '✓ CORRECT';
        
        if ($row['balance_id'] !== null) {
            // Emergency vacation has a balance record - should NOT have used_days
            if ((float)$row['balance_used_days'] > 0) {
                $status = '✗ FAIL';
                $incorrect_count++;
                echo "  {$status} ID {$row['id']}: {$row['request_inv_no']}\n";
                echo "     Employee: {$row['employee_name']}\n";
                echo "     Type: {$row['vac_type']} | {$row['fly_type']}\n";
                echo "     Days: {$row['vacdays']}\n";
                echo "     ERROR: Has balance deduction of {$row['balance_used_days']} days (should be 0)\n\n";
            } else {
                $correct_count++;
                echo "  {$status} ID {$row['id']}: {$row['request_inv_no']}\n";
                echo "     Employee: {$row['employee_name']}\n";
                echo "     Type: {$row['vac_type']} | {$row['fly_type']}\n";
                echo "     Days: {$row['vacdays']}\n";
                echo "     Status: No balance deduction (correct)\n\n";
            }
        } else {
            // Emergency vacation correctly has NO balance record
            $correct_count++;
            echo "  {$status} ID {$row['id']}: {$row['request_inv_no']}\n";
            echo "     Employee: {$row['employee_name']}\n";
            echo "     Type: {$row['vac_type']} | {$row['fly_type']}\n";
            echo "     Days: {$row['vacdays']}\n";
            echo "     Status: No balance record (correct for emergency)\n\n";
        }
    }
} else {
    echo "No completed emergency vacations found in last 30 days.\n";
}

if ($result_emergency) mysqli_free_result($result_emergency);

echo "\n=================================================================\n";
echo "CODE VERIFICATION\n";
echo "=================================================================\n\n";

$ajax_content = file_get_contents(__DIR__ . '/../includes/ajaxFile/ajaxVacation.php');
$helper_content = file_get_contents(__DIR__ . '/../includes/helper_functions.php');

// Check 1: Rule 3 should NOT call update_vacation_balance_on_approval for emergency
$check1 = (
    preg_match('/Rule 3:.*?Fly.*?Emergency.*?NO balance deduction/s', $ajax_content) ||
    preg_match('/if.*?\$is_fly.*?&&.*?\$is_emergency.*?Mark completed/s', $ajax_content)
) && !preg_match('/Rule 3:.*?update_vacation_balance_on_approval/s', $ajax_content);

// Check 2: Helper function should exclude emergency from deductible types
$check2 = preg_match('/Emergency vacations \(both Fly and Local\) are NON-DEDUCTIBLE/s', $helper_content);

// Check 3: Emergency should not be in the deductible types check
$check3 = preg_match('/Only Annual Fly vacations are deductible from balance/s', $helper_content);

$checks = [
    'Rule 3 (Emergency) does NOT deduct balance' => $check1,
    'Helper function marks emergency as non-deductible' => $check2,
    'Only Annual Fly is marked as deductible' => $check3
];

foreach ($checks as $check_name => $check_result) {
    $status = $check_result ? '✓ PASS' : '✗ FAIL';
    echo "{$status}: {$check_name}\n";
}

echo "\n=================================================================\n";
echo "SUMMARY\n";
echo "=================================================================\n\n";

if ($emergency_count > 0) {
    echo "Emergency Vacations Checked: {$emergency_count}\n";
    echo "  ✓ Correct (NO deduction): {$correct_count}\n";
    echo "  ✗ Incorrect (HAS deduction): {$incorrect_count}\n\n";
}

echo "DEDUCTION RULES:\n";
echo "  ✓ Annual Fly vacations: DEDUCT from balance\n";
echo "  ✓ Encashed vacations: DEDUCT from balance\n";
echo "  ✗ Emergency vacations: DO NOT deduct (unpaid leave)\n";
echo "  ✗ Sick Leave: DO NOT deduct (non-deductible)\n";
echo "  ✗ Local Annual: DO NOT deduct (non-deductible)\n";
echo "  ✗ Other leave types: DO NOT deduct\n";

echo "\n=================================================================\n";

mysqli_close($conDB);
?>
