<?php
/**
 * VERIFICATION SCRIPT: Test the Fixed update_vacation_balance_on_approval Function
 * This script verifies that vacation deductions now work correctly:
 * - total_days remains at original contract value (30)
 * - available_balance decreases with deductions
 * - No zero balances after HR_PAYROLL approval
 */

$conDB = new mysqli('localhost', 'root', 'admin123', 'almutlak_db');
if ($conDB->connect_error) {
    die("Connection failed: " . $conDB->connect_error);
}

require_once __DIR__ . '/includes/helper_functions.php';

echo "========== VACATION DEDUCTION FIX VERIFICATION ==========\n\n";

// Find a test vacation that's been approved but not yet processed by HR_PAYROLL
$sql = "SELECT 
    ev.id,
    ev.emp_id,
    ev.request_inv_no,
    ev.vacdays,
    ev.vac_type,
    ev.fly_type,
    ev.current_status,
    e.name,
    e.vac_period,
    cp.vac_period as contract_days
FROM emp_vacation ev
LEFT JOIN employees e ON e.emp_id = ev.emp_id
LEFT JOIN contract_period cp ON e.vac_period = cp.id
WHERE ev.vac_type IN ('Fly', 'Local Vacation')
AND ev.current_status IN ('approved')
AND NOT EXISTS (SELECT 1 FROM emp_vacation_balance WHERE vac_id = ev.id)
ORDER BY ev.created_at DESC
LIMIT 1";

$result = $conDB->query($sql);
if (!$result || $result->num_rows === 0) {
    echo "No test vacation found. Looking for any approved vacation...\n";
    
    // Find any approved vacation
    $sql2 = "SELECT 
        ev.id,
        ev.emp_id,
        ev.request_inv_no,
        ev.vacdays,
        ev.vac_type,
        ev.current_status,
        e.name,
        cp.vac_period as contract_days
    FROM emp_vacation ev
    LEFT JOIN employees e ON e.emp_id = ev.emp_id
    LEFT JOIN contract_period cp ON e.vac_period = cp.id
    WHERE ev.current_status = 'approved'
    ORDER BY ev.created_at DESC
    LIMIT 1";
    
    $result = $conDB->query($sql2);
}

if ($result && $result->num_rows > 0) {
    $vac = $result->fetch_assoc();
    echo "TEST VACATION:\n";
    echo "  Request: " . $vac['request_inv_no'] . "\n";
    echo "  Employee: " . $vac['name'] . " (ID: " . $vac['emp_id'] . ")\n";
    echo "  Type: " . $vac['vac_type'] . "\n";
    echo "  Days: " . $vac['vacdays'] . "\n";
    echo "  Contract Days: " . $vac['contract_days'] . "\n\n";
    
    echo "BEFORE DEDUCTION:\n";
    $bal_sql = "SELECT total_days, available_balance, used_days FROM emp_vacation_balance WHERE vac_id = " . (int)$vac['id'];
    $bal_result = $conDB->query($bal_sql);
    if ($bal_result && $bal_result->num_rows > 0) {
        $bal = $bal_result->fetch_assoc();
        echo "  Total Days: " . $bal['total_days'] . "\n";
        echo "  Available Balance: " . $bal['available_balance'] . "\n";
        echo "  Used Days: " . $bal['used_days'] . "\n";
    } else {
        echo "  No balance record found yet (will be created on deduction)\n";
    }
    
    echo "\nSIMULATING HR_PAYROLL APPROVAL (calling update_vacation_balance_on_approval)...\n";
    
    // Call the fixed function
    if (function_exists('update_vacation_balance_on_approval')) {
        $result = update_vacation_balance_on_approval($conDB, $vac['id']);
        
        if ($result) {
            echo "✅ Function executed successfully\n\n";
            
            echo "AFTER DEDUCTION:\n";
            $bal_sql2 = "SELECT total_days, available_balance, used_days, remaining_balance FROM emp_vacation_balance WHERE vac_id = " . (int)$vac['id'];
            $bal_result2 = $conDB->query($bal_sql2);
            if ($bal_result2 && $bal_result2->num_rows > 0) {
                $bal2 = $bal_result2->fetch_assoc();
                $total_ok = ((float)$bal2['total_days'] === (float)$vac['contract_days']);
                $balance_ok = ((float)$bal2['available_balance'] > 0);
                $used_ok = ((float)$bal2['used_days'] === (float)$vac['vacdays']);
                
                echo "  Total Days: " . $bal2['total_days'] . ($total_ok ? " ✅ CORRECT" : " ❌ WRONG") . "\n";
                echo "  Available Balance: " . $bal2['available_balance'] . ($balance_ok ? " ✅ NOT ZERO" : " ❌ IS ZERO!") . "\n";
                echo "  Used Days: " . $bal2['used_days'] . ($used_ok ? " ✅ CORRECT" : " ❌ WRONG") . "\n";
                echo "  Remaining Balance: " . $bal2['remaining_balance'] . "\n";
                
                echo "\nVERIFICATION:\n";
                echo ($total_ok && $balance_ok && $used_ok ? "✅ ALL CHECKS PASSED" : "❌ SOME CHECKS FAILED") . "\n";
            } else {
                echo "  Error retrieving balance record\n";
            }
        } else {
            echo "❌ Function failed\n";
        }
    } else {
        echo "❌ Function not found\n";
    }
} else {
    echo "No approved vacations found to test\n";
}

$conDB->close();
?>
