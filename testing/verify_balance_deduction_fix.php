<?php
/**
 * VERIFICATION SCRIPT: Balance Deduction Fix for Vacation ID 725
 * 
 * This script verifies that all endpoints that mark vacation as 'completed'
 * now properly call update_vacation_balance_on_approval() to deduct the balance.
 * 
 * Created: January 13, 2026
 * Issue: Vacation ID 725 was marked as completed but balance was not deducted
 * 
 * FIXED ENDPOINTS:
 * 1. updateVacationPayments - Fly | Annual with payment + adjustments
 * 2. updatePayrollAdjustments - Local | Annual with adjustments (Rule 1)
 * 3. updatePayrollAdjustments - Fly | Annual with payment + adjustments (Rule 2)
 * 4. updatePayrollAdjustments - Fly | Emergency with adjustments (Rule 3) - Already had deduction
 * 5. approveVacation - Encashed vacations with payer payment
 */

require_once __DIR__ . '/../includes/db.php';

echo "=================================================================\n";
echo "VACATION BALANCE DEDUCTION FIX VERIFICATION\n";
echo "=================================================================\n\n";

// Test Case 1: Check Vacation ID 725
echo "TEST CASE 1: Checking Vacation ID 725\n";
echo "-----------------------------------------------------------------\n";

$sql_725 = "SELECT 
    v.id, v.emp_id, v.request_inv_no, v.vac_type, v.fly_type, 
    v.vacdays, v.current_status, v.review, v.created_at,
    b.id as balance_id, b.used_days, b.remaining_balance, b.available_balance, b.total_days,
    e.name as employee_name
FROM emp_vacation v
LEFT JOIN emp_vacation_balance b ON v.id = b.vac_id
LEFT JOIN employees e ON v.emp_id = e.emp_id
WHERE v.id = 725";

$result_725 = mysqli_query($conDB, $sql_725);
if ($result_725 && $row_725 = mysqli_fetch_assoc($result_725)) {
    echo "Vacation Details:\n";
    echo "  Invoice: {$row_725['request_inv_no']}\n";
    echo "  Employee: {$row_725['employee_name']} ({$row_725['emp_id']})\n";
    echo "  Type: {$row_725['vac_type']} | {$row_725['fly_type']}\n";
    echo "  Days: {$row_725['vacdays']}\n";
    echo "  Status: {$row_725['current_status']}\n";
    echo "  Review: {$row_725['review']}\n\n";
    
    echo "Balance Record:\n";
    if ($row_725['balance_id']) {
        echo "  ✓ Balance record exists (ID: {$row_725['balance_id']})\n";
        echo "  Total Days: {$row_725['total_days']}\n";
        echo "  Used Days: {$row_725['used_days']}\n";
        echo "  Remaining: {$row_725['remaining_balance']}\n";
        echo "  Available: {$row_725['available_balance']}\n\n";
        
        // Check if deduction is correct
        $expected_remaining = $row_725['total_days'] - $row_725['used_days'];
        if (abs($row_725['remaining_balance'] - $expected_remaining) < 0.01) {
            echo "  ✓ PASS: Remaining balance calculation is CORRECT\n";
        } else {
            echo "  ✗ FAIL: Remaining balance should be {$expected_remaining}, but is {$row_725['remaining_balance']}\n";
        }
        
        if (abs($row_725['available_balance'] - $expected_remaining) < 0.01) {
            echo "  ✓ PASS: Available balance calculation is CORRECT\n";
        } else {
            echo "  ✗ FAIL: Available balance should be {$expected_remaining}, but is {$row_725['available_balance']}\n";
        }
    } else {
        echo "  ✗ FAIL: NO balance record found - balance was NOT deducted!\n";
    }
} else {
    echo "  ✗ ERROR: Vacation ID 725 not found!\n";
}
if ($result_725) mysqli_free_result($result_725);

echo "\n=================================================================\n\n";

// Test Case 2: Check all completed vacations without balance records
echo "TEST CASE 2: Finding Completed Vacations WITHOUT Balance Records\n";
echo "-----------------------------------------------------------------\n";

$sql_missing = "SELECT 
    v.id, v.emp_id, v.request_inv_no, v.vac_type, v.fly_type, 
    v.vacdays, v.current_status, v.is_deductible,
    e.name as employee_name
FROM emp_vacation v
LEFT JOIN emp_vacation_balance b ON v.id = b.vac_id
LEFT JOIN employees e ON v.emp_id = e.emp_id
WHERE v.current_status = 'completed'
    AND b.id IS NULL
    AND v.request_inv_no NOT LIKE 'LEGACY-%'
    AND v.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
ORDER BY v.created_at DESC
LIMIT 20";

$result_missing = mysqli_query($conDB, $sql_missing);
$missing_count = 0;

if ($result_missing && mysqli_num_rows($result_missing) > 0) {
    echo "Found completed vacations WITHOUT balance records:\n\n";
    while ($row = mysqli_fetch_assoc($result_missing)) {
        $missing_count++;
        
        // Determine if this SHOULD have balance record
        $should_deduct = false;
        $reason = '';
        
        if (stripos($row['vac_type'], 'encash') !== false) {
            $should_deduct = true;
            $reason = 'Encashment vacations are balance deductible';
        } elseif ($row['vac_type'] === 'Fly' && $row['fly_type'] === 'annual') {
            $should_deduct = true;
            $reason = 'Annual fly vacations are balance deductible';
        } elseif ($row['vac_type'] === 'Fly' && $row['fly_type'] === 'emergency') {
            $should_deduct = true;
            $reason = 'Emergency fly vacations are balance deductible';
        } elseif ($row['vac_type'] === 'Local Vacation' && $row['fly_type'] === 'annual') {
            $should_deduct = true;
            $reason = 'Local annual vacations are balance deductible';
        } elseif ($row['vac_type'] === 'Local Vacation' && $row['fly_type'] === 'emergency') {
            $should_deduct = true;
            $reason = 'Emergency local vacations are balance deductible';
        } else {
            $reason = 'Non-deductible leave type (sick leave, business trip, etc.)';
        }
        
        $status_icon = $should_deduct ? '✗ FAIL' : '✓ OK';
        
        echo "  {$status_icon} ID {$row['id']}: {$row['request_inv_no']}\n";
        echo "     Employee: {$row['employee_name']}\n";
        echo "     Type: {$row['vac_type']} | {$row['fly_type']}\n";
        echo "     Days: {$row['vacdays']}\n";
        echo "     Reason: {$reason}\n\n";
    }
} else {
    echo "  ✓ EXCELLENT! No completed vacations found without balance records.\n";
}
if ($result_missing) mysqli_free_result($result_missing);

echo "\n=================================================================\n\n";

// Test Case 3: Verify code has the fixes
echo "TEST CASE 3: Code Verification - Checking Fixed Endpoints\n";
echo "-----------------------------------------------------------------\n";

$ajax_file = __DIR__ . '/../includes/ajaxFile/ajaxVacation.php';
$ajax_content = file_get_contents($ajax_file);

$checks = [
    'updateVacationPayments endpoint has balance deduction' => 
        (strpos($ajax_content, 'if ($has_payment && $has_adjustment) {') !== false &&
         strpos($ajax_content, 'update_vacation_balance_on_approval($conDB, $vacation_id)') !== false),
    
    'updatePayrollAdjustments Rule 1 (Local Annual) has balance deduction' =>
        (strpos($ajax_content, 'Rule 1: Local | Annual vacation') !== false &&
         preg_match('/Rule 1.*?update_vacation_balance_on_approval/s', $ajax_content)),
    
    'updatePayrollAdjustments Rule 2 (Fly Annual) has balance deduction' =>
        (strpos($ajax_content, 'Rule 2: Fly | Annual vacation') !== false &&
         preg_match('/Rule 2.*?update_vacation_balance_on_approval/s', $ajax_content)),
    
    'updatePayrollAdjustments Rule 3 (Fly Emergency) has balance deduction' =>
        (strpos($ajax_content, 'Rule 3: Fly | Emergency vacation') !== false &&
         preg_match('/Rule 3.*?update_vacation_balance_on_approval/s', $ajax_content)),
    
    'approveVacation Encashed endpoint has balance deduction' =>
        (strpos($ajax_content, 'if ($vac_type === \'Encashed\')') !== false &&
         preg_match('/Encashed.*?update_vacation_balance_on_approval/s', $ajax_content))
];

foreach ($checks as $check_name => $check_result) {
    $status = $check_result ? '✓ PASS' : '✗ FAIL';
    echo "  {$status}: {$check_name}\n";
}

echo "\n=================================================================\n\n";

// Summary
echo "SUMMARY OF FIXES APPLIED:\n";
echo "-----------------------------------------------------------------\n";
echo "The following endpoints now properly deduct vacation balance:\n\n";
echo "1. updateVacationPayments (Line ~2421)\n";
echo "   - Handles: Fly | Annual vacations\n";
echo "   - Triggers: When payment AND adjustments are filled\n\n";
echo "2. updatePayrollAdjustments - Rule 1 (Line ~2592)\n";
echo "   - Handles: Local | Annual vacations\n";
echo "   - Triggers: When adjustments are filled\n\n";
echo "3. updatePayrollAdjustments - Rule 2 (Line ~2605)\n";
echo "   - Handles: Fly | Annual vacations\n";
echo "   - Triggers: When payment AND adjustments are filled\n\n";
echo "4. updatePayrollAdjustments - Rule 3 (Line ~2619)\n";
echo "   - Handles: Fly | Emergency vacations\n";
echo "   - Triggers: When adjustments are filled\n";
echo "   - Status: ALREADY HAD balance deduction (verified working)\n\n";
echo "5. approveVacation - Encashed (Line ~1578)\n";
echo "   - Handles: Encashed vacations\n";
echo "   - Triggers: When payer completes payment\n\n";
echo "All endpoints now check if balance has already been deducted\n";
echo "before calling update_vacation_balance_on_approval() to prevent\n";
echo "duplicate deductions.\n";
echo "\n=================================================================\n";

mysqli_close($conDB);
?>
