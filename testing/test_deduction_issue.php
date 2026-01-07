<?php
/**
 * TEST SCRIPT: Verify Vacation Day Deduction Issues
 * This script checks:
 * 1. If used_days is being updated at wrong approval stages
 * 2. If total_days and available_balance are being zeroed
 * 3. If deductions are happening twice
 */

require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/session_check.php';

// Get all approved vacations in last 7 days
$sql = "SELECT 
    ev.id,
    ev.emp_id,
    ev.request_inv_no,
    ev.vacdays,
    ev.vac_type,
    ev.fly_type,
    ev.current_status,
    ev.created_at,
    evb.used_days,
    evb.total_days,
    evb.available_balance,
    evb.remaining_balance,
    e.name
FROM emp_vacation ev
LEFT JOIN emp_vacation_balance evb ON evb.vac_id = ev.id
LEFT JOIN employees e ON e.emp_id = ev.emp_id
WHERE ev.current_status IN ('approved', 'completed')
AND ev.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
ORDER BY ev.created_at DESC
LIMIT 20";

$result = mysqli_query($conDB, $sql);
if (!$result) {
    die("Query failed: " . mysqli_error($conDB));
}

echo "========== VACATION DEDUCTION ANALYSIS ==========\n";
echo "Analysis Period: Last 7 days\n\n";

$issues_found = [];

while ($row = mysqli_fetch_assoc($result)) {
    echo "─────────────────────────────────────────────\n";
    echo "Request: " . $row['request_inv_no'] . "\n";
    echo "Employee: " . ($row['name'] ?? 'Unknown') . " (ID: " . $row['emp_id'] . ")\n";
    echo "Vacation Type: " . $row['vac_type'] . " " . ($row['fly_type'] ? "(" . $row['fly_type'] . ")" : "") . "\n";
    echo "Status: " . $row['current_status'] . "\n";
    echo "Days Applied: " . $row['vacdays'] . "\n";
    
    if (!empty($row['used_days']) || !empty($row['total_days']) || !empty($row['available_balance'])) {
        echo "\nBalance Record Found:\n";
        echo "  Used Days: " . $row['used_days'] . "\n";
        echo "  Total Days: " . $row['total_days'] . "\n";
        echo "  Available Balance: " . $row['available_balance'] . "\n";
        echo "  Remaining Balance: " . $row['remaining_balance'] . "\n";
        
        // Check for issues
        if ((float)$row['total_days'] === 0 || (float)$row['available_balance'] === 0) {
            $issues_found[] = [
                'type' => 'ZERO_BALANCE',
                'request' => $row['request_inv_no'],
                'emp_id' => $row['emp_id'],
                'emp_name' => $row['name'],
                'total_days' => $row['total_days'],
                'available_balance' => $row['available_balance']
            ];
            echo "  ❌ ISSUE FOUND: Balance is ZERO! This indicates double deduction or calculation error\n";
        }
        
        if ((float)$row['used_days'] > (float)$row['vacdays']) {
            $issues_found[] = [
                'type' => 'DOUBLE_DEDUCTION',
                'request' => $row['request_inv_no'],
                'emp_id' => $row['emp_id'],
                'emp_name' => $row['name'],
                'used_days' => $row['used_days'],
                'vacdays' => $row['vacdays']
            ];
            echo "  ❌ ISSUE FOUND: Used days (" . $row['used_days'] . ") exceeds vacation days (" . $row['vacdays'] . ")\n";
        }
    } else {
        echo "  No balance record found (may not have been approved by HR_PAYROLL yet)\n";
    }
}

mysqli_free_result($result);

echo "\n\n========== SUMMARY ==========\n";
echo "Total Issues Found: " . count($issues_found) . "\n";

if (count($issues_found) > 0) {
    echo "\nDetailed Issues:\n";
    foreach ($issues_found as $issue) {
        echo "\n" . $issue['type'] . ":\n";
        echo "  Request: " . $issue['request'] . "\n";
        echo "  Employee: " . $issue['emp_name'] . " (" . $issue['emp_id'] . ")\n";
        if (isset($issue['total_days'])) {
            echo "  Total Days: " . $issue['total_days'] . " (Expected: > 0)\n";
            echo "  Available Balance: " . $issue['available_balance'] . " (Expected: > 0)\n";
        }
        if (isset($issue['used_days'])) {
            echo "  Used Days: " . $issue['used_days'] . " (Applied: " . $issue['vacdays'] . ")\n";
        }
    }
}
?>
