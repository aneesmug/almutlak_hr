<?php
// Check all holidays
require_once(__DIR__ . '/includes/db.php');

$query = "SELECT id, holiday_name, total_days, start_date, end_date, is_active FROM emp_holidays ORDER BY start_date";
$result = mysqli_query($conDB, $query);

echo "=== ALL HOLIDAYS ===\n";
while ($row = mysqli_fetch_assoc($result)) {
    echo "ID: " . $row['id'] . ", Name: " . $row['holiday_name'] . ", Total: " . $row['total_days'] . " days, " . $row['start_date'] . " to " . $row['end_date'] . ", Active: " . $row['is_active'] . "\n";
}

echo "\n=== HOLIDAYS OVERLAPPING WITH VACATION 2026-01-02 to 2026-01-10 ===\n";
$vac_start = "2026-01-02";
$vac_end = "2026-01-10";

$query = "SELECT id, holiday_name, total_days, start_date, end_date FROM emp_holidays WHERE is_active = 1 AND start_date <= ? AND end_date >= ?";
$stmt = $conDB->prepare($query);
$stmt->bind_param("ss", $vac_end, $vac_start);
$stmt->execute();
$result = $stmt->get_result();

$total_holiday_days = 0;
while ($row = $result->fetch_assoc()) {
    echo "  " . $row['holiday_name'] . ": " . $row['total_days'] . " days (" . $row['start_date'] . " to " . $row['end_date'] . ")\n";
    $total_holiday_days += (float)$row['total_days'];
}

echo "\nTotal Holiday Days (summed): " . $total_holiday_days . "\n";

$conDB->close();
?>
