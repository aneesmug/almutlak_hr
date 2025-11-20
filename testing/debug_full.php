<?php
require_once './includes/db.php';

$emp_id = '5430';

echo "=== ALL VACATION RECORDS FOR EMP 5430 ===\n\n";

$sql = "SELECT id, emp_id, start_date, return_date, vacdays, current_status, vac_type FROM emp_vacation WHERE emp_id = ? ORDER BY start_date DESC";
$stmt = $conDB->prepare($sql);
$stmt->bind_param("s", $emp_id);
$stmt->execute();
$result = $stmt->get_result();

echo "Total records: " . $result->num_rows . "\n\n";

$totalUsed = 0;
while ($row = $result->fetch_assoc()) {
    echo "ID: {$row['id']}\n";
    echo "  Period: {$row['start_date']} to {$row['return_date']}\n";
    echo "  VacDays: {$row['vacdays']}\n";
    echo "  Status: {$row['current_status']}\n";
    echo "  Type: {$row['vac_type']}\n";
    
    if ($row['current_status'] == 'approved' || $row['current_status'] == 'gm_approved') {
        $totalUsed += (float)$row['vacdays'];
    }
    echo "\n";
}

echo "=== SUMMARY ===\n";
echo "Total Used Days (approved + gm_approved): $totalUsed\n";

echo "\n=== EMPLOYEE INFO ===\n";
$empSql = "SELECT emp_id, emp_name, joining_date FROM employees WHERE emp_id = ?";
$empStmt = $conDB->prepare($empSql);
$empStmt->bind_param("s", $emp_id);
$empStmt->execute();
$empResult = $empStmt->get_result();
$empRow = $empResult->fetch_assoc();

echo "Employee: {$empRow['emp_name']} (ID: {$empRow['emp_id']})\n";
echo "Joining: {$empRow['joining_date']}\n";

echo "\n=== CALCULATOR CHECK ===\n";
require_once './includes/vacation_calculator.php';

$calc = new VacationCalculator($conDB);
$balance = $calc->getCalculatedBalance($emp_id);

echo "Earned Days: {$balance['earned_days']}\n";
echo "Used Days: {$balance['used_days']}\n";
echo "Carried Over: {$balance['carried_over']}\n";
echo "Available Balance: {$balance['available_balance']}\n";
?>
