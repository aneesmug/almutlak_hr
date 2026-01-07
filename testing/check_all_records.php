<?php
// Check all balance records for employee 5430
require_once(__DIR__ . '/includes/db.php');

$emp_id = 5430;

$query = "SELECT id, vac_id, used_days, remaining_balance, total_days FROM emp_vacation_balance WHERE emp_id = ? ORDER BY id DESC";
$stmt = $conDB->prepare($query);
$stmt->bind_param("i", $emp_id);
$stmt->execute();
$result = $stmt->get_result();

echo "=== ALL BALANCE RECORDS FOR EMP 5430 ===\n";
while ($row = $result->fetch_assoc()) {
    echo "ID: " . $row['id'] . ", VAC_ID: " . $row['vac_id'] . ", USED: " . $row['used_days'] . ", REMAINING: " . $row['remaining_balance'] . ", TOTAL: " . $row['total_days'] . "\n";
}

echo "\n=== ALL VACATIONS FOR EMP 5430 ===\n";
$query = "SELECT id, vacdays, start_date, return_date, current_status FROM emp_vacation WHERE emp_id = ? ORDER BY id DESC LIMIT 10";
$stmt = $conDB->prepare($query);
$stmt->bind_param("i", $emp_id);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    echo "VAC_ID: " . $row['id'] . ", DAYS: " . $row['vacdays'] . ", DATES: " . $row['start_date'] . " to " . $row['return_date'] . ", STATUS: " . $row['current_status'] . "\n";
}

$conDB->close();
?>
