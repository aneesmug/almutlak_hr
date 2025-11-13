<?php
// Test script to check payroll integration for loans
require_once __DIR__ . '/includes/db.php';

echo "<h2>Loan Payroll Integration Test</h2>";

// Check if integrate_loan_to_payroll function exists
if (!function_exists('integrate_loan_to_payroll')) {
    require_once __DIR__ . '/includes/ajaxFile/ajaxLoan.php';
}

// Get a test loan
$test_loan_query = "SELECT * FROM emp_loan WHERE status = 'approved' ORDER BY id DESC LIMIT 1";
$result = mysqli_query($conDB, $test_loan_query);

if ($result && mysqli_num_rows($result) > 0) {
    $loan = mysqli_fetch_assoc($result);
    echo "<h3>Testing with Loan ID: {$loan['id']}</h3>";
    echo "<p>Invoice Number: {$loan['inv_no']}</p>";
    echo "<p>Loan Type: {$loan['loan_type']}</p>";
    echo "<p>Employee ID: {$loan['emp_id']}</p>";
    echo "<p>Total Payable: {$loan['total_payable']}</p>";
    echo "<p>Monthly Deduction: {$loan['monthly_deduction']}</p>";
    echo "<p>Installments: " . ($loan['installments'] ?? '1') . "</p>";
    
    // Test the integration
    try {
        if (function_exists('integrate_loan_to_payroll')) {
            echo "<h4>Calling integrate_loan_to_payroll...</h4>";
            $result = integrate_loan_to_payroll($loan['id'], $conDB);
            echo "<pre>";
            print_r($result);
            echo "</pre>";
        } else {
            echo "<p style='color:red;'>ERROR: integrate_loan_to_payroll function not found!</p>";
        }
    } catch (Exception $e) {
        echo "<p style='color:red;'>Exception: " . $e->getMessage() . "</p>";
        echo "<pre>" . $e->getTraceAsString() . "</pre>";
    }
} else {
    echo "<p>No approved loans found to test with.</p>";
    echo "<p>Create a test loan or use an existing one.</p>";
}

// Check payroll_deductions table structure
echo "<h3>Payroll Deductions Table Structure:</h3>";
$structure_query = "SHOW COLUMNS FROM payroll_deductions";
$result = mysqli_query($conDB, $structure_query);
echo "<table border='1' cellpadding='5'><tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
while ($row = mysqli_fetch_assoc($result)) {
    echo "<tr>";
    echo "<td>{$row['Field']}</td>";
    echo "<td>{$row['Type']}</td>";
    echo "<td>{$row['Null']}</td>";
    echo "<td>{$row['Key']}</td>";
    echo "<td>{$row['Default']}</td>";
    echo "</tr>";
}
echo "</table>";

$conDB->close();
?>
