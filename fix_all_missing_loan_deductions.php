<?php
require_once __DIR__ . '/includes/db.php';

echo "=== Creating Missing Loan Deductions ===\n\n";

// Get all approved loans that don't have payroll deductions
$loans = mysqli_query($conDB, "
    SELECT l.* 
    FROM emp_loan l
    WHERE l.status = 'approved'
    AND NOT EXISTS (
        SELECT 1 FROM payroll_deductions pd 
        WHERE pd.emp_id = l.emp_id 
        AND pd.deduction LIKE CONCAT('%', l.inv_no, '%')
    )
    ORDER BY l.id DESC
");

if (mysqli_num_rows($loans) == 0) {
    echo "No loans found that need deductions.\n";
    exit;
}

echo "Found " . mysqli_num_rows($loans) . " loan(s) without deductions:\n\n";

while ($loan = mysqli_fetch_assoc($loans)) {
    echo "Processing Loan ID: {$loan['id']}\n";
    echo "  Invoice: {$loan['inv_no']}\n";
    echo "  Employee: {$loan['emp_id']}\n";
    echo "  Type: {$loan['loan_type']}\n";
    echo "  Start Date: {$loan['start_date']}\n";
    echo "  Installments: {$loan['installments']}\n";
    echo "  Monthly Deduction: {$loan['monthly_deduction']}\n\n";
    
    $emp_id = $loan['emp_id'];
    $loan_type = $loan['loan_type'];
    $monthly_deduction = $loan['monthly_deduction'];
    $installments = $loan['installments'];
    $start_date = new DateTime($loan['start_date']);
    $inv_no = $loan['inv_no'];
    
    // Determine deduction label based on loan type
    $deduction_label = '';
    switch ($loan_type) {
        case 'end_of_service':
            $deduction_label = 'End of Service Loan';
            break;
        case 'housing':
            $deduction_label = 'Housing Loan';
            break;
        case 'advance_salary':
            $deduction_label = 'Advance Salary';
            break;
        default:
            $deduction_label = 'Loan';
    }
    
    // For advance_salary, create one deduction for current month
    if ($loan_type === 'advance_salary') {
        $current_month = $start_date->format('Y-m');
        $deduction_name = $deduction_label . ' - ' . $inv_no;
        $note = number_format($loan['total_payable'], 2, '.', '');
        
        $stmt = $conDB->prepare("INSERT INTO payroll_deductions (emp_id, deduction, note, month, status) VALUES (?, ?, ?, ?, 1)");
        $stmt->bind_param("ssss", $emp_id, $deduction_name, $note, $current_month);
        
        if ($stmt->execute()) {
            echo "  ✅ Created one-time deduction for {$current_month}: {$note} SAR\n\n";
        } else {
            echo "  ❌ Failed: " . $stmt->error . "\n\n";
        }
        $stmt->close();
        
    } else {
        // For other loan types, create monthly installments
        $deduction_name = $deduction_label . ' - ' . $inv_no;
        $added_count = 0;
        
        for ($i = 0; $i < $installments; $i++) {
            $month_date = clone $start_date;
            $month_date->modify("+{$i} months");
            $month_year = $month_date->format('Y-m');
            
            $note = number_format($monthly_deduction, 2, '.', '');
            
            $stmt = $conDB->prepare("INSERT INTO payroll_deductions (emp_id, deduction, note, month, status) VALUES (?, ?, ?, ?, 1)");
            $stmt->bind_param("ssss", $emp_id, $deduction_name, $note, $month_year);
            
            if ($stmt->execute()) {
                echo "  ✅ Created deduction for {$month_year}: {$note} SAR\n";
                $added_count++;
            } else {
                echo "  ❌ Failed for {$month_year}: " . $stmt->error . "\n";
            }
            $stmt->close();
        }
        
        echo "  Total created: {$added_count}/{$installments}\n\n";
    }
}

echo "\n=== Summary ===\n";
echo "All missing loan deductions have been created.\n";
echo "Employees can now see loan deductions when generating payroll.\n";

$conDB->close();
?>
