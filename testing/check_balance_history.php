<?php
require_once './includes/db.php';

$emp_id = '5430';

echo "=== VACATION BALANCE RECORDS FOR 5430 ===\n";
$sql = "SELECT id, period_start_date, period_end_date, earned_days, used_days, carried_days, available_balance FROM emp_vacation_balance WHERE emp_id = ? ORDER BY period_start_date DESC LIMIT 10";
$stmt = $conDB->prepare($sql);
$stmt->bind_param("s", $emp_id);
$stmt->execute();
$result = $stmt->get_result();

echo "Total records: " . $result->num_rows . "\n\n";

while ($row = $result->fetch_assoc()) {
    echo "Period: {$row['period_start_date']} to {$row['period_end_date']}\n";
    echo "  Earned: {$row['earned_days']}, Used: {$row['used_days']}, Carried: {$row['carried_days']}, Available: {$row['available_balance']}\n\n";
}

echo "\n=== OTHER EMPLOYEES WITH APPROVED VACATIONS ===\n";
$sql2 = "SELECT emp_id, COUNT(*) as vacation_count, SUM(vacdays) as total_vacdays FROM emp_vacation WHERE current_status IN ('approved', 'gm_approved') GROUP BY emp_id LIMIT 10";
$result2 = $conDB->query($sql2);

while ($row2 = $result2->fetch_assoc()) {
    echo "EMP {$row2['emp_id']}: {$row2['vacation_count']} vacations, {$row2['total_vacdays']} total days\n";
}
?>
