<?php
/**
 * Test script to verify loan approval chain creation
 * Run this to see what approvers will be assigned for a new loan
 */

require_once __DIR__ . '/includes/db.php';

// Test for employee 5127 (Makaran)
$test_emp_id = '5127';

echo "<h2>Testing Loan Approval Chain for Employee: {$test_emp_id}</h2>";

// Copy of get_loan_approvers function for testing
function get_loan_approvers_test($emp_id, $conDB) {
    $approvers = [];
    $level = 1;
    
    echo "<h3>Building approval chain...</h3>";
    
    // Level 1: Get employee's direct supervisor or department manager
    $stmt = $conDB->prepare("SELECT supervisor_id, dept FROM employees WHERE emp_id = ? LIMIT 1");
    $stmt->bind_param("s", $emp_id);
    $stmt->execute();
    $emp_data = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    if (!$emp_data) {
        echo "<p style='color:red;'>❌ Employee not found!</p>";
        return [];
    }
    
    echo "<p>Employee Dept: {$emp_data['dept']}, Supervisor: " . ($emp_data['supervisor_id'] ?: 'None') . "</p>";
    
    $supervisor_id = $emp_data['supervisor_id'];
    $dept = $emp_data['dept'];
    
    // If employee has a supervisor, add them as level 1
    if (!empty($supervisor_id)) {
        $approvers[$level] = $supervisor_id;
        echo "<p>✅ Level {$level}: Direct Supervisor (emp_id: {$supervisor_id})</p>";
        $level++;
    } else {
        // No supervisor: get department manager
        $stmt = $conDB->prepare("SELECT emp_id, name FROM employees WHERE dept = ? AND emptype = 'Manager' AND status = 1 LIMIT 1");
        $stmt->bind_param("s", $dept);
        $stmt->execute();
        $manager = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        if ($manager) {
            $approvers[$level] = $manager['emp_id'];
            echo "<p>✅ Level {$level}: Dept Manager - {$manager['name']} (emp_id: {$manager['emp_id']})</p>";
            $level++;
        } else {
            echo "<p style='color:orange;'>⚠️ No department manager found for dept {$dept}</p>";
        }
    }
    
    // Level 2: HR Payroll
    $stmt = $conDB->prepare("SELECT emp_id, fullname FROM admin_login WHERE user_type = 'hr_payroll' AND emp_id IS NOT NULL AND status = 1 LIMIT 1");
    $stmt->execute();
    $hr_payroll = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if ($hr_payroll && !empty($hr_payroll['emp_id'])) {
        $approvers[$level] = $hr_payroll['emp_id'];
        echo "<p>✅ Level {$level}: HR Payroll - {$hr_payroll['fullname']} (emp_id: {$hr_payroll['emp_id']})</p>";
        $level++;
    } else {
        echo "<p style='color:red;'>❌ Level {$level}: HR Payroll NOT FOUND</p>";
    }
    
    // Level 3: HR Manager
    $stmt = $conDB->prepare("SELECT emp_id, fullname FROM admin_login WHERE user_type = 'hr_supervisor' AND emp_id IS NOT NULL AND status = 1 LIMIT 1");
    $stmt->execute();
    $hr_manager = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if ($hr_manager && !empty($hr_manager['emp_id'])) {
        $approvers[$level] = $hr_manager['emp_id'];
        echo "<p>✅ Level {$level}: HR Manager - {$hr_manager['fullname']} (emp_id: {$hr_manager['emp_id']})</p>";
        $level++;
    } else {
        echo "<p style='color:red;'>❌ Level {$level}: HR Manager NOT FOUND</p>";
    }
    
    // Level 4: Audit
    $stmt = $conDB->prepare("SELECT emp_id, fullname FROM admin_login WHERE user_type = 'auditor' AND emp_id IS NOT NULL AND status = 1 LIMIT 1");
    $stmt->execute();
    $audit = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if ($audit && !empty($audit['emp_id'])) {
        $approvers[$level] = $audit['emp_id'];
        echo "<p>✅ Level {$level}: Auditor - {$audit['fullname']} (emp_id: {$audit['emp_id']})</p>";
        $level++;
    } else {
        echo "<p style='color:orange;'>⚠️ Level {$level}: Auditor NOT FOUND (This is OK if you don't have an auditor yet)</p>";
    }
    
    // Level 5: GM
    $stmt = $conDB->prepare("SELECT emp_id, fullname FROM admin_login WHERE user_type = 'gm' AND emp_id IS NOT NULL AND status = 1 LIMIT 1");
    $stmt->execute();
    $gm = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if ($gm && !empty($gm['emp_id'])) {
        $approvers[$level] = $gm['emp_id'];
        echo "<p>✅ Level {$level}: GM - {$gm['fullname']} (emp_id: {$gm['emp_id']})</p>";
        $level++;
    } else {
        echo "<p style='color:red;'>❌ Level {$level}: GM NOT FOUND</p>";
    }
    
    // Level 6: Finance Manager
    $stmt = $conDB->prepare("SELECT emp_id, fullname FROM admin_login WHERE user_type = 'finance' AND emp_id IS NOT NULL AND status = 1 LIMIT 1");
    $stmt->execute();
    $finance_mgr = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if ($finance_mgr && !empty($finance_mgr['emp_id'])) {
        $approvers[$level] = $finance_mgr['emp_id'];
        echo "<p>✅ Level {$level}: Finance Manager - {$finance_mgr['fullname']} (emp_id: {$finance_mgr['emp_id']})</p>";
        $level++;
    } else {
        echo "<p style='color:red;'>❌ Level {$level}: Finance Manager NOT FOUND</p>";
    }
    
    // Level 7: Finance Officer (Payer)
    $stmt = $conDB->prepare("SELECT emp_id, fullname FROM admin_login WHERE user_type = 'finance_officer' AND emp_id IS NOT NULL AND status = 1 LIMIT 1");
    $stmt->execute();
    $payer = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if ($payer && !empty($payer['emp_id'])) {
        $approvers[$level] = $payer['emp_id'];
        echo "<p>✅ Level {$level}: Finance Officer (Payer) - {$payer['fullname']} (emp_id: {$payer['emp_id']})</p>";
        $level++;
    } else {
        echo "<p style='color:red;'>❌ Level {$level}: Finance Officer NOT FOUND</p>";
    }
    
    return $approvers;
}

$approvers = get_loan_approvers_test($test_emp_id, $conDB);

echo "<hr>";
echo "<h3>Summary</h3>";
echo "<p><strong>Total Approval Levels Created:</strong> " . count($approvers) . "</p>";
echo "<p><strong>Expected:</strong> 7 levels (or 6 if no auditor)</p>";

if (count($approvers) >= 6) {
    echo "<p style='color:green; font-weight:bold;'>✅ SUCCESS! Approval chain looks good!</p>";
} else {
    echo "<p style='color:red; font-weight:bold;'>❌ PROBLEM! Missing approvers in the chain.</p>";
    echo "<p>Please check the errors above to see which approvers are missing.</p>";
}

echo "<hr>";
echo "<h3>Approval Chain Array</h3>";
echo "<pre>";
print_r($approvers);
echo "</pre>";

$conDB->close();
?>
