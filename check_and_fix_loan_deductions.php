<?php
require_once __DIR__ . '/includes/db.php';

echo "=== Checking Loan Approval and Deductions ===\n\n";

$inv_no = 'LN-20251112-6638-zrxo';
$emp_id = '5455';

// Check loan details
$loan = mysqli_fetch_assoc(mysqli_query($conDB, "SELECT * FROM emp_loan WHERE inv_no = '{$inv_no}'"));

if ($loan) {
    echo "Loan Details:\n";
    echo "  Invoice: {$loan['inv_no']}\n";
    echo "  Employee: {$loan['emp_id']}\n";
    echo "  Type: {$loan['loan_type']}\n";
    echo "  Status: {$loan['status']}\n";
    echo "  Amount: {$loan['loan_amount']}\n";
    echo "  Installments: {$loan['installments']}\n";
    echo "  Monthly Deduction: {$loan['monthly_deduction']}\n";
    echo "  Start Date: {$loan['start_date']}\n";
    echo "  Payment Proof: {$loan['payment_proof_file']}\n\n";
} else {
    die("Loan not found!\n");
}

// Check if payroll deductions were created
echo "Checking Payroll Deductions for Employee {$emp_id}:\n";
$deductions = mysqli_query($conDB, "SELECT * FROM payroll_deductions WHERE emp_id = '{$emp_id}' AND deduction LIKE '%Loan%' ORDER BY month ASC");

if (mysqli_num_rows($deductions) > 0) {
    echo "Found loan deductions:\n";
    while ($ded = mysqli_fetch_assoc($deductions)) {
        echo "  - Month: {$ded['month']}, Amount: {$ded['note']}, Deduction: {$ded['deduction']}\n";
    }
} else {
    echo "❌ NO loan deductions found!\n";
    echo "This means integrate_loan_to_payroll() was not called or failed silently.\n\n";
    
    // Manually create the deductions now
    echo "Creating deductions manually...\n\n";
    
    require_once __DIR__ . '/includes/ajaxFile/ajaxLoan.php';
    
    if (function_exists('integrate_loan_to_payroll')) {
        $result = integrate_loan_to_payroll($loan['id'], $conDB);
        echo "Integration Result:\n";
        echo "  Success: " . ($result['success'] ? 'YES' : 'NO') . "\n";
        echo "  Message: {$result['message']}\n\n";
        
        // Verify deductions created
        echo "Verifying created deductions:\n";
        $verify = mysqli_query($conDB, "SELECT * FROM payroll_deductions WHERE emp_id = '{$emp_id}' AND deduction LIKE '%End of Service%' ORDER BY month ASC");
        while ($ded = mysqli_fetch_assoc($verify)) {
            echo "  ✅ {$ded['month']}: {$ded['note']} SAR\n";
        }
    } else {
        echo "ERROR: integrate_loan_to_payroll() function not found!\n";
    }
}

$conDB->close();
?>
