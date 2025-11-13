<?php
require_once __DIR__ . '/includes/db.php';

echo "=== Manual Payroll Integration for Loan ID 11 ===\n\n";

// Get loan details
$loan_id = 11;
$stmt = $conDB->prepare("SELECT * FROM emp_loan WHERE id = ? LIMIT 1");
$stmt->bind_param("i", $loan_id);
$stmt->execute();
$loan = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$loan) {
    die("Loan not found!\n");
}

echo "Loan Details:\n";
echo "  Employee ID: " . $loan['emp_id'] . "\n";
echo "  Loan Type: " . $loan['loan_type'] . "\n";
echo "  Invoice No: " . $loan['inv_no'] . "\n";
echo "  Status: " . $loan['status'] . "\n";
echo "  Monthly Deduction: " . $loan['monthly_deduction'] . "\n";
echo "  Installments: " . $loan['installments'] . "\n";
echo "  Start Date: " . $loan['start_date'] . "\n\n";

$emp_id = $loan['emp_id'];
$loan_type = $loan['loan_type'];
$monthly_deduction = $loan['monthly_deduction'];
$installments = $loan['installments'];
$start_date = new DateTime($loan['start_date']);
$inv_no = $loan['inv_no'];

if ($loan_type === 'end_of_service') {
    echo "Creating monthly installment deductions for End of Service loan...\n\n";
    
    $deduction_name = 'End of Service Loan - ' . $inv_no;
    $added_count = 0;
    
    for ($i = 0; $i < $installments; $i++) {
        $month_date = clone $start_date;
        $month_date->modify("+{$i} months");
        $month_year = $month_date->format('Y-m');
        
        // Check if deduction already exists
        $check_stmt = $conDB->prepare(
            "SELECT id FROM payroll_deductions 
             WHERE emp_id = ? AND month = ? AND deduction = ? LIMIT 1"
        );
        $check_stmt->bind_param("sss", $emp_id, $month_year, $deduction_name);
        $check_stmt->execute();
        $exists = $check_stmt->get_result()->fetch_assoc();
        $check_stmt->close();
        
        if ($exists) {
            echo "  Month {$month_year}: Already exists (ID: {$exists['id']})\n";
            continue;
        }
        
        // Insert new deduction
        $note = number_format($monthly_deduction, 2, '.', '');
        $stmt_insert = $conDB->prepare(
            "INSERT INTO payroll_deductions (emp_id, deduction, note, month, status) 
             VALUES (?, ?, ?, ?, 1)"
        );
        $stmt_insert->bind_param("ssss", $emp_id, $deduction_name, $note, $month_year);
        
        if ($stmt_insert->execute()) {
            $new_id = $conDB->insert_id;
            echo "  Month {$month_year}: Created (ID: {$new_id}, Amount: {$note})\n";
            $added_count++;
        } else {
            echo "  Month {$month_year}: FAILED - " . $stmt_insert->error . "\n";
        }
        $stmt_insert->close();
    }
    
    echo "\n✅ Successfully added {$added_count} monthly deductions to payroll\n\n";
    
    // Verify
    echo "=== Verification - Payroll Deductions Created ===\n\n";
    $verify_query = mysqli_query($conDB, 
        "SELECT * FROM payroll_deductions 
         WHERE emp_id = '{$emp_id}' AND deduction LIKE '%End of Service%' 
         ORDER BY month ASC"
    );
    
    while ($row = mysqli_fetch_assoc($verify_query)) {
        echo "  " . $row['month'] . " → " . $row['note'] . " SAR\n";
    }
    
} else {
    echo "Loan type '{$loan_type}' - manual integration needed for this type\n";
}

echo "\nDone!\n";
?>
