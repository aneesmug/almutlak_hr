<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once './includes/db.php';

$emp_id = '5430';

echo "=== VACATION RECORDS ===\n";
$sql = "SELECT id, emp_id, start_date, return_date, vacdays, current_status FROM emp_vacation WHERE emp_id = ?";
$stmt = $conDB->prepare($sql);
$stmt->bind_param("s", $emp_id);
$stmt->execute();
$result = $stmt->get_result();

echo "Total: " . $result->num_rows . "\n";
$totalUsed = 0;

while ($row = $result->fetch_assoc()) {
    $vacdays = floatval($row['vacdays']);
    $totalUsed += $vacdays;
    echo "Vacation ID {$row['id']}: {$vacdays} days ({$row['current_status']}), {$row['start_date']} to {$row['return_date']}\n";
}

echo "\nTotal Used: $totalUsed\n";

echo "\n=== EMPLOYEE INFO ===\n";
$empSql = "SELECT emp_id, emp_name, joining_date FROM employees WHERE emp_id = ?";
$empStmt = $conDB->prepare($empSql);
$empStmt->bind_param("s", $emp_id);
$empStmt->execute();
$empResult = $empStmt->get_result();
$empRow = $empResult->fetch_assoc();

echo "Employee: {$empRow['emp_name']}\n";
echo "Joining: {$empRow['joining_date']}\n";
?>
