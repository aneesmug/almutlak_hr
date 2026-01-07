<?php
require_once 'includes/db.php';
require_once 'includes/helper_functions.php';

echo "=== TESTING THE FIX ===\n\n";

// Test case: Employee 5430
$vac_id = 706;
$emp_id = 5430;

// Get vacation details
$sql = "SELECT id, vacdays, start_date, return_date FROM emp_vacation WHERE id = $vac_id LIMIT 1";
$result = mysqli_query($conDB, $sql);
$vac = mysqli_fetch_assoc($result);
mysqli_free_result($result);

echo "Vacation Details:\n";
echo "  - Vacation days: " . $vac['vacdays'] . "\n";
echo "  - Period: " . $vac['start_date'] . " to " . $vac['return_date'] . "\n\n";

// Get holidays using the fixed function
$active_holidays = get_active_holidays_in_range($conDB, $vac['start_date'], $vac['return_date']);

echo "Holidays Found:\n";
$total_holiday_days = 0;
foreach ($active_holidays as $h) {
    echo "  - " . $h['holiday_name'] . ": " . $h['total_days'] . " days\n";
    $total_holiday_days += $h['total_days'];
}
echo "  Total holiday days: $total_holiday_days\n\n";

// Calculate deductible days using the fixed function
$holiday_days = calculate_holiday_days_in_vacation($active_holidays, $vac['start_date'], $vac['return_date']);

echo "Calculation (Using Fixed Function):\n";
echo "  - Vacation days: " . $vac['vacdays'] . "\n";
echo "  - Holiday days: $holiday_days\n";
echo "  - Deductible days: " . ($vac['vacdays'] - $holiday_days) . "\n\n";

echo "Expected vs Actual:\n";
echo "  - Expected deduction: 4 days (9 - 5 = 4)\n";
echo "  - Calculated deduction: " . ($vac['vacdays'] - $holiday_days) . " days\n";
echo "  - Match: " . (($vac['vacdays'] - $holiday_days) == 4 ? "✓ YES" : "✗ NO") . "\n\n";

echo "Balance Calculation:\n";
echo "  - Starting balance: 17.53\n";
echo "  - Deduction: " . ($vac['vacdays'] - $holiday_days) . " days\n";
echo "  - Expected ending: " . (17.53 - ($vac['vacdays'] - $holiday_days)) . "\n";
