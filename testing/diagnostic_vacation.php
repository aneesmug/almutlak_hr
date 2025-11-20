<?php
/**
 * Diagnostic script to test VacationCalculator for emp_id 5430
 */
require_once './includes/db.php';
require_once './includes/vacation_calculator.php';

$emp_id = '5430'; // From your screenshot

echo "<pre>";
echo "=== DIAGNOSTIC VACATION CALCULATOR ===\n";
echo "Employee ID: $emp_id\n";
echo "Today: " . date('Y-m-d H:i:s') . "\n\n";

// 1. Get employee data directly from DB
echo "--- EMPLOYEE DATA FROM DB ---\n";
$sql_emp = "SELECT emp_id, name, joining_date, vac_period, status FROM employees WHERE emp_id = ? LIMIT 1";
$stmt_emp = $conDB->prepare($sql_emp);
if (!$stmt_emp) {
    die("Prepare failed: " . $conDB->error);
}
$stmt_emp->bind_param("s", $emp_id);
$stmt_emp->execute();
$emp_data = $stmt_emp->get_result()->fetch_assoc();
$stmt_emp->close();

if (!$emp_data) {
    die("Employee not found!");
}

echo "Name: " . $emp_data['name'] . "\n";
echo "Joining Date: " . $emp_data['joining_date'] . "\n";
echo "Vac Period (Contract ID): " . $emp_data['vac_period'] . "\n";
echo "Status: " . $emp_data['status'] . "\n\n";

// 2. Get contract details
echo "--- CONTRACT PERIOD DETAILS ---\n";
$sql_contract = "SELECT id, period, vac_period FROM contract_period WHERE id = ? LIMIT 1";
$stmt_contract = $conDB->prepare($sql_contract);
if (!$stmt_contract) {
    die("Contract prepare failed: " . $conDB->error);
}
$stmt_contract->bind_param("i", $emp_data['vac_period']);
$stmt_contract->execute();
$contract_data = $stmt_contract->get_result()->fetch_assoc();
$stmt_contract->close();

if (!$contract_data) {
    die("Contract not found!");
}

echo "Contract Period: " . $contract_data['period'] . "\n";
echo "Total Vacation Days: " . $contract_data['vac_period'] . "\n\n";

// 3. Call VacationCalculator
echo "--- VACATION CALCULATOR OUTPUT ---\n";
try {
    $vc = new VacationCalculator($conDB);
    $result = $vc->getCalculatedBalance($emp_id);
    
    if ($result) {
        echo "SUCCESS! Calculated Balance:\n";
        echo json_encode($result, JSON_PRETTY_PRINT) . "\n\n";
        
        echo "Key Values:\n";
        echo "  Available Balance: " . $result['available_balance'] . " (THIS SHOULD BE ~13.81)\n";
        echo "  Remaining Balance: " . $result['remaining_balance'] . "\n";
        echo "  Used Days: " . $result['used_days'] . "\n";
        echo "  Total Days: " . $result['total_days'] . "\n";
        echo "  Carryover Days: " . $result['carryover_days'] . "\n";
        echo "  Period: " . $result['period_start']->format('Y-m-d') . " to " . $result['period_end']->format('Y-m-d') . "\n";
    } else {
        echo "ERROR: getCalculatedBalance returned null\n";
    }
} catch (Exception $e) {
    echo "EXCEPTION: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
    echo "Trace:\n" . $e->getTraceAsString() . "\n";
}

echo "\n--- EXISTING BALANCE RECORDS IN DB ---\n";
$sql_balance = "SELECT * FROM emp_vacation_balance WHERE emp_id = ? ORDER BY id DESC LIMIT 3";
$stmt_balance = $conDB->prepare($sql_balance);
if ($stmt_balance) {
    $stmt_balance->bind_param("s", $emp_id);
    $stmt_balance->execute();
    $balances = $stmt_balance->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt_balance->close();
    
    if ($balances) {
        foreach ($balances as $b) {
            echo "ID: " . $b['id'] . " | Available: " . $b['available_balance'] . " | Used: " . $b['used_days'] . " | Period: " . $b['period_start'] . " to " . $b['period_end'] . "\n";
        }
    } else {
        echo "No balance records found\n";
    }
}

echo "\n--- APPROVED VACATIONS FOR THIS EMPLOYEE ---\n";
$sql_vac = "SELECT id, vacdays, vac_type, fly_type, start_date, approval_status FROM emp_vacation WHERE emp_id = ? AND approval_status = 'gm_approved' ORDER BY start_date DESC";
$stmt_vac = $conDB->prepare($sql_vac);
if ($stmt_vac) {
    $stmt_vac->bind_param("s", $emp_id);
    $stmt_vac->execute();
    $vacations = $stmt_vac->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt_vac->close();
    
    if ($vacations) {
        echo "Total GM-approved vacations: " . count($vacations) . "\n";
        $total_used = 0;
        foreach ($vacations as $v) {
            echo "  - " . $v['start_date'] . ": " . $v['vacdays'] . " days (" . $v['vac_type'] . " - " . $v['fly_type'] . ")\n";
            $total_used += $v['vacdays'];
        }
        echo "Total used days: $total_used\n";
    } else {
        echo "No GM-approved vacations found\n";
    }
}

echo "\n</pre>";
?>
