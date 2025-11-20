<?php
require_once './includes/db.php';

$emp_id = isset($_GET['emp_id']) ? trim($_GET['emp_id']) : '5430';

echo "=== emp_vacation_balance FOR EMP {$emp_id} ===\n";
$sql = "SELECT id, emp_id, period_start, period_end, used_days, available_balance, carryover_days FROM emp_vacation_balance WHERE emp_id = ? ORDER BY period_start DESC LIMIT 10";
$stmt = $conDB->prepare($sql);
$stmt->bind_param("s", $emp_id);
$stmt->execute();
$result = $stmt->get_result();

echo "Total records: " . $result->num_rows . "\n\n";

while ($row = $result->fetch_assoc()) {
    echo "ID {$row['id']}: {$row['period_start']} to {$row['period_end']}\n";
    echo "  Used: {$row['used_days']}, Carried: {$row['carryover_days']}, Available: {$row['available_balance']}\n\n";
}
?>
