<?php
require_once './includes/db.php';

$emp_id = '5430';

// Get joining date and contract period for reference
$empSql = "SELECT emp_id, emp_name, joining_date FROM employees WHERE emp_id = ?";
$empStmt = $conDB->prepare($empSql);
$empStmt->bind_param("s", $emp_id);
$empStmt->execute();
$empResult = $empStmt->get_result();
$empRow = $empResult->fetch_assoc();

echo "=== EMPLOYEE INFO ===\n";
echo "EMP ID: {$empRow['emp_id']}\n";
echo "NAME: {$empRow['emp_name']}\n";
echo "JOINING: {$empRow['joining_date']}\n";
echo "\n=== ALL VACATION RECORDS ===\n";

$sql = "SELECT id, emp_id, start_date, return_date, vacdays, current_status, vac_type, fly_type FROM emp_vacation WHERE emp_id = ? ORDER BY start_date DESC";
$stmt = $conDB->prepare($sql);
$stmt->bind_param("s", $emp_id);
$stmt->execute();
$result = $stmt->get_result();

$totalUsed = 0;
if ($result->num_rows > 0) {
    echo "Found {$result->num_rows} vacation records:\n\n";
    while ($row = $result->fetch_assoc()) {
        echo "ID: {$row['id']}\n";
        echo "  Start: {$row['start_date']} | Return: {$row['return_date']}\n";
        echo "  VacDays: {$row['vacdays']} | Status: {$row['current_status']} | Type: {$row['vac_type']}\n";
        echo "  Fly Type: {$row['fly_type']}\n";
        
        if ($row['current_status'] == 'approved' || $row['current_status'] == 'gm_approved') {
            $totalUsed += (float)$row['vacdays'];
        }
        echo "\n";
    }
    echo "Total Used (approved only): $totalUsed\n";
} else {
    echo "No vacations found\n";
}

// Now check contract period
echo "\n=== CONTRACT PERIOD ===\n";
$contractSql = "SELECT id, contract_start_date, contract_end_date, contract_duration_days FROM contract_period WHERE emp_id = ? ORDER BY contract_start_date DESC LIMIT 1";
$contractStmt = $conDB->prepare($contractSql);
$contractStmt->bind_param("s", $emp_id);
$contractStmt->execute();
$contractResult = $contractStmt->get_result();
$contractRow = $contractResult->fetch_assoc();

if ($contractRow) {
    echo "Contract ID: {$contractRow['id']}\n";
    echo "Start: {$contractRow['contract_start_date']}\n";
    echo "End: {$contractRow['contract_end_date']}\n";
    echo "Duration: {$contractRow['contract_duration_days']} days\n";
}

// Check emp_vacation_balance table
echo "\n=== VACATION BALANCE RECORDS ===\n";
$balanceSql = "SELECT id, emp_id, period_start_date, period_end_date, earned_days, used_days, carried_days, available_balance FROM emp_vacation_balance WHERE emp_id = ? ORDER BY period_start_date DESC LIMIT 5";
$balanceStmt = $conDB->prepare($balanceSql);
$balanceStmt->bind_param("s", $emp_id);
$balanceStmt->execute();
$balanceResult = $balanceStmt->get_result();

if ($balanceResult->num_rows > 0) {
    echo "Found {$balanceResult->num_rows} balance records:\n\n";
    while ($row = $balanceResult->fetch_assoc()) {
        echo "Period: {$row['period_start_date']} to {$row['period_end_date']}\n";
        echo "  Earned: {$row['earned_days']} | Used: {$row['used_days']} | Carried: {$row['carried_days']} | Available: {$row['available_balance']}\n\n";
    }
} else {
    echo "No balance records found\n";
}
?>
