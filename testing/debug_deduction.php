<?php
// Debug script to check deductions for employee 5430
require_once('includes/db.php');

$emp_id = 5430;

echo "=== EMPLOYEE 5430 VACATION DEDUCTION DEBUG ===\n\n";

// Get latest vacation
$query = "SELECT id, emp_id, vacdays, start_date, return_date, current_status FROM emp_vacation WHERE emp_id = ? ORDER BY id DESC LIMIT 1";
$stmt = $conDB->prepare($query);
$stmt->bind_param("i", $emp_id);
$stmt->execute();
$result = $stmt->get_result();
$vacation = $result->fetch_assoc();

if (!$vacation) {
    echo "No vacation found for employee 5430\n";
    exit;
}

echo "Vacation ID: " . $vacation['id'] . "\n";
echo "Vacation Days: " . $vacation['vacdays'] . "\n";
echo "Start Date: " . $vacation['start_date'] . "\n";
echo "Return Date: " . $vacation['return_date'] . "\n";
echo "Status: " . $vacation['current_status'] . "\n\n";

// Get all holidays that fall within vacation period
$query = "SELECT id, holiday_name, total_days, start_date, end_date, is_active 
          FROM emp_holidays 
          WHERE is_active = 1 
          AND start_date <= ? 
          AND end_date >= ?";
$stmt = $conDB->prepare($query);
$stmt->bind_param("ss", $vacation['return_date'], $vacation['start_date']);
$stmt->execute();
$result = $stmt->get_result();

echo "=== ACTIVE HOLIDAYS IN VACATION PERIOD ===\n";
$total_holiday_days = 0;
while ($holiday = $result->fetch_assoc()) {
    echo "Holiday: " . $holiday['holiday_name'] . "\n";
    echo "  Total Days: " . $holiday['total_days'] . "\n";
    echo "  Date Range: " . $holiday['start_date'] . " to " . $holiday['end_date'] . "\n";
    $total_holiday_days += (float)$holiday['total_days'];
}

echo "\nTotal Holiday Days Summed: " . $total_holiday_days . "\n";

// Calculate deduction
$days_to_deduct = max(0, (float)$vacation['vacdays'] - $total_holiday_days);
echo "\nDeduction Calculation:\n";
echo "  Vacation Days: " . $vacation['vacdays'] . "\n";
echo "  Holiday Days: " . $total_holiday_days . "\n";
echo "  Days to Deduct: " . $days_to_deduct . " (should be 4)\n";

// Check all employee balance related tables
echo "\n=== CHECKING BALANCE TABLES ===\n";

// Try emp_vacation_balance
$query = "SHOW TABLES LIKE '%balance%'";
$result = mysqli_query($conDB, $query);
echo "\nTables with 'balance' in name:\n";
while ($row = mysqli_fetch_row($result)) {
    echo "  - " . $row[0] . "\n";
}

// Check vacation_balance table
$query = "SELECT emp_id, used_days, remaining_balance FROM vacation_balance WHERE emp_id = ? ORDER BY created_at DESC LIMIT 1";
$stmt = $conDB->prepare($query);
$stmt->bind_param("i", $emp_id);
$stmt->execute();
$result = $stmt->get_result();
$balance = $result->fetch_assoc();

if ($balance) {
    echo "\n=== VACATION_BALANCE RECORD ===\n";
    echo "Used Days: " . $balance['used_days'] . "\n";
    echo "Remaining Balance: " . $balance['remaining_balance'] . "\n";
} else {
    echo "\nNo record in vacation_balance table\n";
}

// Check emp_vacation_balance table - get ALL columns
$query = "SELECT * FROM emp_vacation_balance WHERE emp_id = ? ORDER BY created_at DESC LIMIT 1";
$stmt = $conDB->prepare($query);
$stmt->bind_param("i", $emp_id);
$stmt->execute();
$result = $stmt->get_result();
$balance = $result->fetch_assoc();

if ($balance) {
    echo "\n=== EMP_VACATION_BALANCE RECORD ===\n";
    foreach ($balance as $key => $value) {
        echo $key . ": " . $value . "\n";
    }
} else {
    echo "\nNo record in emp_vacation_balance\n";
}

$conDB->close();
?>
