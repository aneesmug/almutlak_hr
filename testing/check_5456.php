<?php
require_once './includes/db.php';

$emp_id = '5456';

// Check for snapshot
$q = "SELECT * FROM emp_vacation_balance WHERE emp_id = ? ORDER BY id DESC LIMIT 1";
$stmt = $conDB->prepare($q);
$stmt->bind_param("s", $emp_id);
$stmt->execute();
$snap = $stmt->get_result()->fetch_assoc();

if ($snap) {
    echo "Snapshot found:\n";
    echo json_encode($snap, JSON_PRETTY_PRINT);
} else {
    echo "No snapshot found\n";
}

// Check employee data
$q2 = "SELECT e.joining_date, e.vac_period, cp.period, cp.vac_period FROM employees e JOIN contract_period cp ON e.vac_period = cp.id WHERE e.emp_id = ?";
$stmt2 = $conDB->prepare($q2);
$stmt2->bind_param("s", $emp_id);
$stmt2->execute();
$emp = $stmt2->get_result()->fetch_assoc();

if ($emp) {
    echo "\nEmployee data:\n";
    echo json_encode($emp, JSON_PRETTY_PRINT);
}

// Calculate days from join to today
$join = new DateTime('2025-10-15');
$today = new DateTime();
$days = $join->diff($today)->days;
echo "\nDays from join to today: $days\n";
echo "Accrual: $days × (30/365.25) = " . ($days * 30 / 365.25) . "\n";
?>
