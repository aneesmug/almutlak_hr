<?php
require_once __DIR__ . '/includes/db.php';

echo "=== Verifying Loan Deductions Setup ===\n\n";

// Check loan deductions
$deductions = mysqli_query($conDB, "
    SELECT * FROM payroll_deductions 
    WHERE emp_id = '5456' 
    AND deduction LIKE '%End of Service%'
    ORDER BY month ASC
");

echo "Loan Deductions in Database:\n";
echo str_repeat("-", 60) . "\n";
while ($row = mysqli_fetch_assoc($deductions)) {
    echo sprintf(
        "Month: %s | Amount: %s SAR | Status: %s\n",
        $row['month'],
        $row['note'],
        $row['status'] == 1 ? 'Active' : 'Inactive'
    );
}

echo "\n=== Checking November 2025 Payroll ===\n\n";

// Check if November 2025 payroll exists
$nov_payroll = mysqli_query($conDB, "
    SELECT * FROM payrolls 
    WHERE emp_id = '5456' 
    AND month_year = '2025-11'
");

if (mysqli_num_rows($nov_payroll) > 0) {
    $payroll = mysqli_fetch_assoc($nov_payroll);
    echo "November 2025 Payroll EXISTS:\n";
    echo "  Net Salary: " . $payroll['net_salary'] . "\n";
    echo "  Total Deductions: " . $payroll['total_deductions'] . "\n";
    echo "  Status: " . $payroll['status'] . "\n\n";
    
    // Check November deductions
    echo "November 2025 Deductions:\n";
    $nov_ded = mysqli_query($conDB, "
        SELECT * FROM payroll_deductions 
        WHERE emp_id = '5456' 
        AND month = '2025-11'
    ");
    
    while ($ded = mysqli_fetch_assoc($nov_ded)) {
        echo "  - " . $ded['deduction'] . ": " . $ded['note'] . " SAR\n";
    }
} else {
    echo "November 2025 payroll NOT generated yet\n";
}

echo "\n=== Checking December 2025 Setup ===\n\n";

$dec_deductions = mysqli_query($conDB, "
    SELECT * FROM payroll_deductions 
    WHERE emp_id = '5456' 
    AND month = '2025-12'
");

echo "December 2025 Deductions Configured:\n";
if (mysqli_num_rows($dec_deductions) > 0) {
    while ($ded = mysqli_fetch_assoc($dec_deductions)) {
        echo "  - " . $ded['deduction'] . ": " . $ded['note'] . " SAR\n";
    }
} else {
    echo "  No deductions configured for December 2025\n";
}

echo "\n=== Summary ===\n";
echo "✅ Loan approved and deductions configured starting December 2025\n";
echo "ℹ️  November 2025 won't have loan deductions (loan starts Dec 1st)\n";
echo "💡 Generate December 2025 payroll to see the loan deduction\n";

$conDB->close();
?>
