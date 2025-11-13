<?php
require_once __DIR__ . '/includes/db.php';

echo "=== Testing Payroll Generation for December 2025 ===\n\n";

// Get PDO connection
$pdo = getDbConnection();

$emp_id = '5456';
$month_year = '2025-12';

echo "Employee: {$emp_id}\n";
echo "Month: December 2025\n\n";

// Check existing deductions for December
echo "Step 1: Checking existing deductions for December 2025...\n";
$stmt = $pdo->prepare("SELECT * FROM payroll_deductions WHERE emp_id = :emp_id AND month = :month_year");
$stmt->execute([':emp_id' => $emp_id, ':month_year' => $month_year]);
$deductions = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (count($deductions) > 0) {
    echo "Found " . count($deductions) . " existing deduction(s):\n";
    foreach ($deductions as $ded) {
        echo "  - {$ded['deduction']}: {$ded['note']} SAR\n";
    }
} else {
    echo "No existing deductions found.\n";
}

echo "\n";

// Check loan status
echo "Step 2: Checking loan status...\n";
$payroll_month_end = date('Y-m-t', strtotime($month_year . '-01'));
$stmt_loan = $pdo->prepare("SELECT * FROM emp_loan WHERE emp_id = :emp_id AND status = 'approved' AND start_date <= :payroll_month_end");
$stmt_loan->execute([':emp_id' => $emp_id, ':payroll_month_end' => $payroll_month_end]);
$loan = $stmt_loan->fetch(PDO::FETCH_ASSOC);

if ($loan) {
    echo "Active loan found:\n";
    echo "  ID: {$loan['id']}\n";
    echo "  Type: {$loan['loan_type']}\n";
    echo "  Monthly Deduction: {$loan['monthly_deduction']} SAR\n";
    echo "  Start Date: {$loan['start_date']}\n";
} else {
    echo "No active loan found for December 2025\n";
}

echo "\n";

// Simulate the payroll deduction check
echo "Step 3: Simulating payroll generation check...\n";
$stmt_check = $pdo->prepare("SELECT id FROM payroll_deductions WHERE emp_id = :emp_id AND month = :month_year AND (deduction = 'Loan Installment' OR deduction LIKE '%Loan%')");
$stmt_check->execute([':emp_id' => $emp_id, ':month_year' => $month_year]);
$existing_loan_ded = $stmt_check->fetch();

if ($existing_loan_ded) {
    echo "✅ Loan deduction already exists - payroll system will skip creating duplicate\n";
    echo "   The existing loan deduction will be included in total deductions calculation\n";
} else {
    echo "❌ No loan deduction found - payroll system will try to create generic 'Loan Installment'\n";
}

echo "\n=== Summary ===\n";
echo "When you generate December 2025 payroll:\n";
if ($existing_loan_ded) {
    echo "✅ System will use existing 'End of Service Loan' deduction (1000.00 SAR)\n";
    echo "✅ Total deductions will include: GOSI + Loan Deduction\n";
} else {
    echo "⚠️  System will create new 'Loan Installment' deduction\n";
    echo "⚠️  This might create duplicate deductions\n";
}

?>
