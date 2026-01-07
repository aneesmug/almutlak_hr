<?php
require_once 'includes/db.php';
require_once 'includes/helper_functions.php';

echo "=== TRACING THE ACTUAL CALCULATION ===\n\n";

$vac_id = 706;

// Get the vacation details
$sql = "SELECT id, emp_id, vacdays, start_date, return_date, vac_type, fly_type, remarks FROM emp_vacation WHERE id = $vac_id LIMIT 1";
$result = mysqli_query($conDB, $sql);
$vac = mysqli_fetch_assoc($result);
mysqli_free_result($result);

echo "1. Vacation Details:\n";
echo "   - ID: " . $vac['id'] . "\n";
echo "   - Emp ID: " . $vac['emp_id'] . "\n";
echo "   - Vacation days: " . $vac['vacdays'] . "\n";
echo "   - Period: " . $vac['start_date'] . " to " . $vac['return_date'] . "\n";
echo "   - Type: " . $vac['vac_type'] . " / " . $vac['fly_type'] . "\n\n";

// Get holidays in range
$active_holidays = get_active_holidays_in_range($conDB, $vac['start_date'], $vac['return_date']);
echo "2. Active Holidays Found:\n";
echo "   - Count: " . count($active_holidays) . "\n";
foreach ($active_holidays as $h) {
    echo "   - " . $h['holiday_name'] . " (" . $h['start_date'] . " to " . $h['end_date'] . ")\n";
}

// Calculate holiday days
$holiday_days = calculate_holiday_days_in_vacation($active_holidays, $vac['start_date'], $vac['return_date']);
echo "\n3. Holiday Day Calculation:\n";
echo "   - Holiday days found: $holiday_days\n";
echo "   - Days to deduct: " . $vac['vacdays'] . "\n";
echo "   - Expected deduction: " . ($vac['vacdays'] - $holiday_days) . " days\n\n";

// Get the balance record
$sql = "SELECT * FROM emp_vacation_balance WHERE vac_id = $vac_id LIMIT 1";
$result = mysqli_query($conDB, $sql);
$balance = mysqli_fetch_assoc($result);
mysqli_free_result($result);

echo "4. Stored Balance Record:\n";
if ($balance) {
    echo "   - Used days: " . $balance['used_days'] . "\n";
    echo "   - Available: " . $balance['available_balance'] . "\n";
    echo "   - Remaining: " . $balance['remaining_balance'] . "\n\n";
} else {
    echo "   - No balance record found!\n\n";
}

// Get employee's contract info
$sql = "SELECT e.vac_period, cp.vac_period as contract_days FROM employees e
        JOIN contract_period cp ON e.vac_period = cp.id
        WHERE e.emp_id = " . $vac['emp_id'];
$result = mysqli_query($conDB, $sql);
$emp = mysqli_fetch_assoc($result);
mysqli_free_result($result);

echo "5. Employee Contract Info:\n";
echo "   - Vac period ID: " . $emp['vac_period'] . "\n";
echo "   - Contract days: " . $emp['contract_days'] . "\n\n";

// Check if there's a latest balance before this vacation
$sql = "SELECT * FROM emp_vacation_balance WHERE emp_id = " . $vac['emp_id'] . " AND vac_id != $vac_id ORDER BY id DESC LIMIT 1";
$result = mysqli_query($conDB, $sql);
$prev_balance = mysqli_fetch_assoc($result);
mysqli_free_result($result);

if ($prev_balance) {
    echo "6. Previous Balance Record:\n";
    echo "   - Used days: " . $prev_balance['used_days'] . "\n";
    echo "   - Available: " . $prev_balance['available_balance'] . "\n";
    echo "   - Remaining: " . $prev_balance['remaining_balance'] . "\n\n";
} else {
    echo "6. Previous Balance Record:\n";
    echo "   - None found (this is first deduction)\n\n";
}

echo "=== ANALYSIS ===\n";
if ($balance) {
    $expected_used = ($prev_balance ? $prev_balance['used_days'] : 0) + ($vac['vacdays'] - $holiday_days);
    echo "Expected used days: " . $expected_used . "\n";
    echo "Actual used days:   " . $balance['used_days'] . "\n";
    echo "Discrepancy:        " . ($balance['used_days'] - $expected_used) . " days\n";
}
