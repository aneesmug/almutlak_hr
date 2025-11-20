<?php
require_once __DIR__ . '/includes/db.php';

echo "<h2>Database Verification</h2>";
echo "<pre>";

$query = "SELECT id, emp_id, contract_id, period_start, period_end, total_days, used_days, remaining_balance, available_balance, carryover_days FROM `emp_vacation_balance` WHERE emp_id='5152' AND contract_id=5";
$result = mysqli_query($conDB, $query);

if ($result && mysqli_num_rows($result) > 0) {
    $row = mysqli_fetch_assoc($result);
    echo "RECORD FOUND IN DATABASE:\n";
    echo "=====================================\n";
    echo "ID:                  " . $row['id'] . "\n";
    echo "emp_id:              " . $row['emp_id'] . "\n";
    echo "contract_id:         " . $row['contract_id'] . "\n";
    echo "period_start:        " . $row['period_start'] . "\n";
    echo "period_end:          " . $row['period_end'] . "\n";
    echo "total_days:          " . $row['total_days'] . "\n";
    echo "used_days:           " . $row['used_days'] . "\n";
    echo "remaining_balance:   " . $row['remaining_balance'] . "\n";
    echo "available_balance:   " . $row['available_balance'] . " ✓ (UPDATED)\n";
    echo "carryover_days:      " . $row['carryover_days'] . "\n";
} else {
    echo "ERROR: No record found\n";
}

echo "</pre>";
?>
