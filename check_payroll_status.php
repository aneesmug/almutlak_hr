<?php
require_once __DIR__ . '/includes/db.php';

echo "=== Checking Payroll Deductions for Employee 5456 ===\n\n";

// Check all deductions
$result = mysqli_query($conDB, "SELECT * FROM payroll_deductions WHERE emp_id = '5456' ORDER BY month ASC, created_at DESC");

if (mysqli_num_rows($result) > 0) {
    echo "ID | Deduction | Amount | Month | Status | Created\n";
    echo str_repeat("-", 80) . "\n";
    while ($row = mysqli_fetch_assoc($result)) {
        printf(
            "%d | %s | %s | %s | %d | %s\n",
            $row['id'],
            $row['deduction'],
            $row['note'],
            $row['month'],
            $row['status'],
            $row['created_at']
        );
    }
} else {
    echo "No deductions found for employee 5456\n";
}

echo "\n=== Checking Loan Status ===\n\n";
$loan = mysqli_fetch_assoc(mysqli_query($conDB, "SELECT * FROM emp_loan WHERE emp_id = '5456' AND id = 11"));
if ($loan) {
    echo "Loan ID: " . $loan['id'] . "\n";
    echo "Status: " . $loan['status'] . "\n";
    echo "Invoice: " . $loan['inv_no'] . "\n";
    echo "Type: " . $loan['loan_type'] . "\n";
    echo "Start Date: " . $loan['start_date'] . "\n";
    echo "Monthly Deduction: " . $loan['monthly_deduction'] . "\n";
    echo "Installments: " . $loan['installments'] . "\n";
}

$conDB->close();
?>
