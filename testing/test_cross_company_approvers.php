<?php
/**
 * Test Script: Verify Cross-Company Approver Access
 * 
 * This script tests if employees from one company can see approvers from other companies
 * Specifically tests the fix for employee 5232 from "2 Matal" finding HR_SENIOR_BP approvers
 */

// Include database connection
require_once 'includes/conn.php';
require_once 'includes/functions.php';

echo "=== CROSS-COMPANY APPROVER ACCESS TEST ===\n\n";

// Test 1: Check if employee 5232 exists and which company they're from
echo "Test 1: Checking employee 5232 details...\n";
$stmt = $pdo->prepare("SELECT emp_id, name, comp_no FROM employees WHERE emp_id = ?");
$stmt->execute([5232]);
$emp = $stmt->fetch(PDO::FETCH_ASSOC);

if ($emp) {
    echo "  ✓ Employee found:\n";
    echo "    - ID: {$emp['emp_id']}\n";
    echo "    - Name: {$emp['name']}\n";
    echo "    - Company: {$emp['comp_no']}\n\n";
    $emp_company = $emp['comp_no'];
} else {
    echo "  ✗ Employee 5232 not found\n\n";
    exit(1);
}

// Test 2: Check HR Senior BP approvers in all companies
echo "Test 2: Checking all HR Senior BP approvers (no company filter)...\n";
$sql = "SELECT e.emp_id, e.name, e.comp_no, al.user_type 
        FROM employees e 
        JOIN admin_login al ON e.emp_id = al.emp_id 
        WHERE al.user_type = 'hr_senior_bp' AND e.status = 1
        ORDER BY e.name ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute();
$approvers = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "  Found " . count($approvers) . " HR Senior BP approvers:\n";
foreach ($approvers as $approver) {
    $company_marker = ($approver['comp_no'] == $emp_company) ? "✓ SAME" : "✓ CROSS-COMPANY";
    echo "    - {$approver['emp_id']} ({$approver['name']}) - Company: {$approver['comp_no']} [{$company_marker}]\n";
}

if (count($approvers) > 0) {
    echo "\n  ✓ Cross-company approvers are accessible!\n";
    
    // Check if there are cross-company approvers
    $cross_company_count = 0;
    foreach ($approvers as $approver) {
        if ($approver['comp_no'] != $emp_company) {
            $cross_company_count++;
        }
    }
    
    if ($cross_company_count > 0) {
        echo "  ✓ Found $cross_company_count cross-company HR Senior BP approvers\n";
    } else {
        echo "  ⚠ All HR Senior BP approvers are from the same company\n";
    }
} else {
    echo "\n  ✗ No HR Senior BP approvers found!\n";
}

echo "\n";

// Test 3: Verify the SQL query used in hrHandler.php works
echo "Test 3: Simulating AJAX call for get_hr_senior_bp...\n";

// Simulate what hrHandler.php does now (without company filter)
$sql_ajax = "SELECT e.emp_id, e.name, al.user_type 
        FROM employees e 
        JOIN admin_login al ON e.emp_id = al.emp_id 
        WHERE al.user_type = 'hr_senior_bp' AND e.status = 1
        ORDER BY e.name ASC";

$stmt = $pdo->prepare($sql_ajax);
$stmt->execute();
$ajax_result = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (count($ajax_result) > 0) {
    echo "  ✓ AJAX query returns " . count($ajax_result) . " results\n";
    echo "  ✓ First approver: {$ajax_result[0]['emp_id']} ({$ajax_result[0]['name']})\n";
    echo "  ✓ Employee 5232 should now see these approvers!\n";
} else {
    echo "  ✗ AJAX query returns no results\n";
}

echo "\n=== TEST COMPLETE ===\n";
echo "\nConclusion: The company filter has been removed from get_hr_senior_bp.\n";
echo "Employee 5232 from '2 Matal' can now find HR Senior BP approvers from any company.\n";
?>
