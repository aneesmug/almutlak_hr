<?php
require_once 'includes/db.php';
require_once 'includes/helper_functions.php';
require_once 'includes/vacation_calculator.php';

date_default_timezone_set('Asia/Riyadh');

echo "=== DEBUGGING BALANCE CALCULATION ===\n\n";

$emp_id = '1061';

// Get current database state
$query = "SELECT emp_id, available_balance, last_updated FROM emp_vacation_balance WHERE emp_id = ? LIMIT 1";
$stmt = mysqli_prepare($conDB, $query);
mysqli_stmt_bind_param($stmt, 's', $emp_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$current = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt);

echo "Current DB State:\n";
echo "  emp_id: {$current['emp_id']}\n";
echo "  available_balance: {$current['available_balance']}\n";
echo "  last_updated: {$current['last_updated']}\n";
echo "  Today's date: " . date('Y-m-d H:i:s') . "\n\n";

// Now calculate the live balance
echo "Calling get_live_vacation_balance()...\n";
$live_balance = get_live_vacation_balance($conDB, $emp_id);

echo "Live Balance Result: $live_balance\n";
echo "Should have added accrual: " . (date('Y-m-d') !== substr($current['last_updated'], 0, 10) ? "YES" : "NO") . "\n";
echo "Days elapsed: " . (date('Y-m-d') !== substr($current['last_updated'], 0, 10) ? "1+ days" : "0 days (same calendar day)") . "\n";

echo "\nExpected accrual: ~0.08 days\n";
echo "Expected new balance: ~" . ($current['available_balance'] + 0.08) . "\n";
echo "Actual live balance: $live_balance\n";

if (abs($live_balance - $current['available_balance']) > 0.001) {
    echo "\n✅ ACCRUAL WORKING - Balance increased by " . ($live_balance - $current['available_balance']) . "\n";
} else {
    echo "\n❌ NO ACCRUAL - Balance unchanged\n";
}
?>
