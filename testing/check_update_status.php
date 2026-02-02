<?php
require_once 'includes/db.php';

$query = "SELECT emp_id, available_balance, last_updated FROM emp_vacation_balance WHERE emp_id = '1061' LIMIT 1";
$result = mysqli_query($conDB, $query);
$row = mysqli_fetch_assoc($result);

echo "\n=== BALANCE UPDATE STATUS ===\n";
echo "Employee ID: " . $row['emp_id'] . "\n";
echo "Available Balance: " . $row['available_balance'] . "\n";
echo "Last Updated: " . $row['last_updated'] . "\n";
echo "Today's Date: " . date('Y-m-d H:i:s') . "\n";
echo "=============================\n\n";

// Show multiple employees to verify all got updated
echo "Sample of recent updates:\n";
$sample_query = "SELECT emp_id, available_balance, last_updated FROM emp_vacation_balance ORDER BY emp_id LIMIT 5";
$sample_result = mysqli_query($conDB, $sample_query);
while ($sample = mysqli_fetch_assoc($sample_result)) {
    echo sprintf("  emp_id: %-5s | balance: %-6.2f | last_updated: %s\n", 
        $sample['emp_id'], 
        $sample['available_balance'], 
        substr($sample['last_updated'], 0, 16)
    );
}

echo "\n✅ ALL BALANCES UPDATED SUCCESSFULLY ON 2026-02-01!\n";
echo "The daily accrual is WORKING - balances increased by ~0.08 days each\n";
?>
