<?php
// Simple check of emp_vacation_balance
require_once(__DIR__ . '/includes/db.php');

$emp_id = 5430;

$query = "SELECT * FROM emp_vacation_balance WHERE emp_id = ? ORDER BY created_at DESC LIMIT 1";
$stmt = $conDB->prepare($query);
$stmt->bind_param("i", $emp_id);
$stmt->execute();
$result = $stmt->get_result();
$balance = $result->fetch_assoc();

if ($balance) {
    echo "=== EMP_VACATION_BALANCE FOR EMPLOYEE 5430 ===\n";
    foreach ($balance as $key => $value) {
        echo $key . ": " . $value . "\n";
    }
} else {
    echo "No record found\n";
}

$conDB->close();
?>
