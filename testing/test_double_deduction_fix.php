<?php
/**
 * TEST SCRIPT: Verify Double Deduction Fix
 * 
 * This script tests that vacation days are NOT deducted twice
 * Checks that update_vacation_balance_on_approval is only called once per vacation
 */

require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/helper_functions.php';

echo "=== DOUBLE DEDUCTION TEST ===\n\n";

// Get recent vacations that were approved
$sql = "SELECT 
    v.id,
    v.request_inv_no,
    v.emp_id,
    v.vacdays,
    v.current_status,
    b.used_days,
    b.total_days,
    b.available_balance,
    COUNT(b.id) as balance_record_count
FROM emp_vacation v
LEFT JOIN emp_vacation_balance b ON v.id = b.vac_id
WHERE v.current_status IN ('approved', 'completed')
AND v.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
GROUP BY v.id
ORDER BY v.created_at DESC
LIMIT 20";

$result = mysqli_query($conDB, $sql);

if (!$result) {
    echo "❌ Database error: " . mysqli_error($conDB) . "\n";
    exit(1);
}

$issues = [];
$processed = 0;

while ($row = mysqli_fetch_assoc($result)) {
    $processed++;
    $vacation_id = $row['id'];
    $inv_no = $row['request_inv_no'];
    $emp_id = $row['emp_id'];
    $requested_days = (float)$row['vacdays'];
    $used_days = (float)($row['used_days'] ?? 0);
    $total_days = (float)($row['total_days'] ?? 0);
    $available_balance = (float)($row['available_balance'] ?? 0);
    $balance_record_count = (int)$row['balance_record_count'];
    
    echo "Vacation: $inv_no | Employee: $emp_id | Days: $requested_days\n";
    echo "  Status: {$row['current_status']}\n";
    echo "  Balance Records: $balance_record_count\n";
    echo "  Used Days in Balance: $used_days | Total Days: $total_days | Available: $available_balance\n";
    
    // Check for issues
    $problem = false;
    
    // Issue 1: Multiple balance records for same vacation (shouldn't happen)
    if ($balance_record_count > 1) {
        echo "  ⚠️  ISSUE: Multiple balance records for this vacation!\n";
        $problem = true;
        $issues[] = "Multiple balance records: $inv_no";
    }
    
    // Issue 2: Double deduction (used_days = 2x requested_days)
    if ($balance_record_count > 0 && $used_days > 0) {
        $deduction_ratio = $used_days / $requested_days;
        if ($deduction_ratio > 1.9) { // Allow some tolerance for float comparison
            echo "  ❌ ISSUE: DOUBLE DEDUCTION! Used: $used_days vs Requested: $requested_days (ratio: " . round($deduction_ratio, 2) . "x)\n";
            $problem = true;
            $issues[] = "Double deduction: $inv_no (used=$used_days, requested=$requested_days)";
        } elseif ($deduction_ratio > 1.1) {
            echo "  ⚠️  WARNING: Possible over-deduction (ratio: " . round($deduction_ratio, 2) . "x)\n";
        } elseif (abs($used_days - $requested_days) < 0.1) {
            echo "  ✅ Correct deduction (1:1 ratio)\n";
        }
    }
    
    if (!$problem && $balance_record_count > 0) {
        echo "  ✅ No issues detected\n";
    }
    
    echo "\n";
}

if (empty($processed)) {
    echo "⚠️  No vacations found in last 7 days for testing\n";
} else {
    echo "\n=== SUMMARY ===\n";
    echo "Total vacations checked: $processed\n";
    
    if (empty($issues)) {
        echo "✅ NO ISSUES FOUND - Double deduction fix appears to be working!\n";
    } else {
        echo "❌ ISSUES FOUND:\n";
        foreach ($issues as $issue) {
            echo "  - $issue\n";
        }
    }
}

mysqli_close($conDB);
?>
