<?php
// Check the calculation step by step
require_once(__DIR__ . '/includes/db.php');

$emp_id = 5430;
$vac_id = 706;

// Method 1: Check what getUsedVacationDays would return
echo "=== SIMULATING VacationCalculator::getUsedVacationDays() ===\n\n";

// Get the approved vacation
$query = "SELECT id, vacdays, start_date, return_date FROM emp_vacation WHERE id = ? AND emp_id = ?";
$stmt = $conDB->prepare($query);
$stmt->bind_param("ii", $vac_id, $emp_id);
$stmt->execute();
$result = $stmt->get_result();
$vacation = $result->fetch_assoc();

if (!$vacation) {
    echo "Vacation not found\n";
    exit;
}

echo "Vacation: " . $vacation['vacdays'] . " days\n";
echo "Period: " . $vacation['start_date'] . " to " . $vacation['return_date'] . "\n\n";

// Get holidays within this vacation period
$query = "SELECT total_days FROM emp_holidays WHERE is_active = 1 AND start_date <= ? AND end_date >= ?";
$stmt = $conDB->prepare($query);
$stmt->bind_param("ss", $vacation['return_date'], $vacation['start_date']);
$stmt->execute();
$result = $stmt->get_result();

$total_holiday_days = 0;
while ($row = $result->fetch_assoc()) {
    $total_holiday_days += (float)$row['total_days'];
}

echo "Holidays in period: " . $total_holiday_days . " days\n";

// Calculate deductible
$deductible_days = max(0, (float)$vacation['vacdays'] - $total_holiday_days);
echo "Deductible days (9 - 5): " . $deductible_days . " days\n\n";

echo "=== CHECKING BALANCE UPDATE LOGIC ===\n\n";

// Check if there's an issue with how calculate_holiday_days_in_vacation is being called
$query = "SELECT * FROM emp_vacation WHERE id = ?";
$stmt = $conDB->prepare($query);
$stmt->bind_param("i", $vac_id);
$stmt->execute();
$result = $stmt->get_result();
$vac_details = $result->fetch_assoc();

$vac_start = $vac_details['start_date'];
$vac_end = $vac_details['return_date'];

// Get active holidays in range
$query = "SELECT id, holiday_name, total_days, start_date, end_date FROM emp_holidays WHERE is_active = 1 AND start_date <= ? AND end_date >= ?";
$stmt = $conDB->prepare($query);
$stmt->bind_param("ss", $vac_end, $vac_start);
$stmt->execute();
$result = $stmt->get_result();

$active_holidays = [];
while ($row = $result->fetch_assoc()) {
    $active_holidays[] = $row;
}

echo "Active holidays array:\n";
print_r($active_holidays);

echo "\nCalculating holiday_days using calculate_holiday_days_in_vacation logic:\n";
$holiday_days = 0;
foreach ($active_holidays as $holiday) {
    $holiday_total = (float)($holiday['total_days'] ?? 0);
    if ($holiday_total > 0) {
        echo "  Adding: " . $holiday_total . "\n";
        $holiday_days += $holiday_total;
    }
}
echo "Total holiday_days: " . $holiday_days . "\n";

$days_to_deduct = max(0, (float)$vac_details['vacdays'] - $holiday_days);
echo "Days to deduct (vacdays - holiday_days): " . $days_to_deduct . "\n";

$conDB->close();
?>
