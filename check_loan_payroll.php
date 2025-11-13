<?php
require_once __DIR__ . '/includes/db.php';

echo "=== Checking Loan and Payroll Status ===\n\n";

// Check loan details
$loan_query = mysqli_query($conDB, "SELECT * FROM emp_loan WHERE id = 11");
$loan = mysqli_fetch_assoc($loan_query);

if ($loan) {
    echo "Loan ID: " . $loan['id'] . "\n";
    echo "Employee ID: " . $loan['emp_id'] . "\n";
    echo "Invoice No: " . $loan['inv_no'] . "\n";
    echo "Loan Type: " . $loan['loan_type'] . "\n";
    echo "Status: " . $loan['status'] . "\n";
    echo "Loan Amount: " . $loan['loan_amount'] . "\n";
    echo "Installments: " . $loan['installments'] . "\n";
    echo "Monthly Deduction: " . $loan['monthly_deduction'] . "\n";
    echo "Start Date: " . $loan['start_date'] . "\n";
    echo "Payment Proof: " . ($loan['payment_proof_file'] ?? 'None') . "\n";
    echo "Final Approved Amount: " . ($loan['final_approved_amount'] ?? 'None') . "\n\n";
} else {
    echo "Loan not found!\n\n";
}

// Check payroll deductions
echo "=== Checking Payroll Deductions for Emp 5456 ===\n\n";
$deductions_query = mysqli_query($conDB, "SELECT * FROM payroll_deductions WHERE emp_id = '5456' ORDER BY created_at DESC");

if (mysqli_num_rows($deductions_query) > 0) {
    while ($deduction = mysqli_fetch_assoc($deductions_query)) {
        echo "ID: " . $deduction['id'] . "\n";
        echo "  Deduction: " . $deduction['deduction'] . "\n";
        echo "  Amount (note): " . $deduction['note'] . "\n";
        echo "  Month: " . $deduction['month'] . "\n";
        echo "  Status: " . $deduction['status'] . "\n";
        echo "  Created: " . $deduction['created_at'] . "\n\n";
    }
} else {
    echo "No payroll deductions found for employee 5456\n\n";
}

// Now manually trigger payroll integration for this loan
echo "=== Attempting to Create Payroll Deductions ===\n\n";

require_once __DIR__ . '/includes/ajaxFile/ajaxLoan.php';

if (function_exists('integrate_loan_to_payroll')) {
    $result = integrate_loan_to_payroll(11, $conDB);
    echo "Result: " . ($result['success'] ? 'SUCCESS' : 'FAILED') . "\n";
    echo "Message: " . $result['message'] . "\n\n";
    
    // Check again
    echo "=== Checking Payroll Deductions After Integration ===\n\n";
    $deductions_query2 = mysqli_query($conDB, "SELECT * FROM payroll_deductions WHERE emp_id = '5456' AND deduction LIKE '%End of Service%' ORDER BY month ASC");
    
    if (mysqli_num_rows($deductions_query2) > 0) {
        while ($deduction = mysqli_fetch_assoc($deductions_query2)) {
            echo "Month: " . $deduction['month'] . " - Amount: " . $deduction['note'] . " - " . $deduction['deduction'] . "\n";
        }
    } else {
        echo "No End of Service loan deductions found\n";
    }
} else {
    echo "ERROR: integrate_loan_to_payroll function not found!\n";
}

$conDB->close();
?>
