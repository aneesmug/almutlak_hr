<?php
/**
 * Fix Missing Loan Deductions
 * 
 * This script automatically recreates missing payroll deduction entries for approved loans.
 * Useful if deductions weren't created during approval or were accidentally deleted.
 * 
 * Usage: Access via browser at http://sys.almutlak/testing/fix_loan_deductions.php
 */

require_once __DIR__ . '/../includes/session_check.php';

// Only admins can access
if (!($is_system_admin ?? false)) {
    echo "<h2>Access Denied</h2>";
    echo "<p>Only system administrators can access this tool.</p>";
    exit;
}

header('Content-Type: text/html; charset=UTF-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fix Missing Loan Deductions</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 20px;
            background: #f5f5f5;
        }
        .container {
            max-width: 1000px;
            margin: 0 auto;
        }
        h1 { color: #333; border-bottom: 3px solid #007bff; padding-bottom: 10px; }
        h2 { color: #007bff; margin-top: 30px; }
        
        .section {
            background: white;
            padding: 20px;
            margin: 20px 0;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .status-good { color: #28a745; font-weight: bold; }
        .status-bad { color: #dc3545; font-weight: bold; }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 10px 0;
        }
        
        table th, table td {
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        
        table th {
            background-color: #f8f9fa;
            font-weight: bold;
        }
        
        .success-box {
            background: #d4edda;
            border-left: 4px solid #28a745;
            padding: 15px;
            margin: 10px 0;
        }
        
        .error-box {
            background: #f8d7da;
            border-left: 4px solid #dc3545;
            padding: 15px;
            margin: 10px 0;
        }
        
        .info-box {
            background: #e7f3ff;
            border-left: 4px solid #007bff;
            padding: 15px;
            margin: 10px 0;
        }
        
        code {
            background: #f4f4f4;
            padding: 2px 6px;
            border-radius: 3px;
            font-family: 'Courier New', monospace;
        }
        
        button {
            background: #007bff;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
        }
        
        button:hover {
            background: #0056b3;
        }
        
        button.danger {
            background: #dc3545;
        }
        
        button.danger:hover {
            background: #c82333;
        }
    </style>
</head>
<body>
<div class="container">
    <h1>🔧 Fix Missing Loan Deductions</h1>
    <p>This tool scans for approved loans missing payroll deduction entries and recreates them automatically.</p>

<?php

try {
    // ============================================
    // 1. Identify Missing Deductions
    // ============================================
    echo '<div class="section">';
    echo '<h2>1️⃣ Scanning for Missing Deductions</h2>';
    
    $stmt = $conDB->prepare("
        SELECT 
            el.id,
            el.inv_no,
            el.emp_id,
            el.loan_type,
            el.monthly_deduction,
            el.installments,
            el.start_date,
            el.deduction_mode,
            e.name as emp_name,
            COUNT(pd.id) as existing_count
        FROM emp_loan el
        LEFT JOIN payroll_deductions pd ON el.emp_id = pd.emp_id 
            AND pd.deduction LIKE CONCAT('%', el.inv_no, '%')
        LEFT JOIN employees e ON el.emp_id = e.emp_id
        WHERE el.status = 'approved' AND el.deduction_mode = 'automatic'
        GROUP BY el.id
    ");
    
    $stmt->execute();
    $all_loans = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    
    // Find loans with missing deductions
    $loans_missing_deductions = [];
    foreach ($all_loans as $loan) {
        if (intval($loan['existing_count']) < intval($loan['installments'])) {
            $loans_missing_deductions[] = $loan;
        }
    }
    
    if (empty($loans_missing_deductions)) {
        echo '<div class="success-box">✅ All approved loans have complete deduction entries!</div>';
    } else {
        echo '<div class="error-box">';
        echo '<strong>⚠️ Found ' . count($loans_missing_deductions) . ' loan(s) with missing deductions</strong>';
        echo '</div>';
        
        echo '<table>';
        echo '<tr>';
        echo '<th>Invoice</th>';
        echo '<th>Employee</th>';
        echo '<th>Type</th>';
        echo '<th>Expected</th>';
        echo '<th>Existing</th>';
        echo '<th>Missing</th>';
        echo '</tr>';
        
        foreach ($loans_missing_deductions as $loan) {
            $missing = intval($loan['installments']) - intval($loan['existing_count']);
            echo '<tr>';
            echo '<td><code>' . htmlspecialchars($loan['inv_no']) . '</code></td>';
            echo '<td>' . htmlspecialchars($loan['emp_name']) . '</td>';
            echo '<td>' . htmlspecialchars($loan['loan_type']) . '</td>';
            echo '<td>' . $loan['installments'] . '</td>';
            echo '<td>' . $loan['existing_count'] . '</td>';
            echo '<td><span class="status-bad">' . $missing . '</span></td>';
            echo '</tr>';
        }
        
        echo '</table>';
    }
    
    echo '</div>';
    
    // ============================================
    // 2. Process Fixes
    // ============================================
    if (!empty($loans_missing_deductions)) {
        echo '<div class="section">';
        echo '<h2>2️⃣ Recreating Missing Deductions</h2>';
        
        $fixed_count = 0;
        $error_count = 0;
        $results = [];
        
        foreach ($loans_missing_deductions as $loan) {
            $emp_id = $loan['emp_id'];
            $inv_no = $loan['inv_no'];
            $monthly_amount = floatval($loan['monthly_deduction']);
            $installments = intval($loan['installments']);
            $start_date = new DateTime($loan['start_date']);
            
            // Determine deduction label
            $type_map = [
                'end_of_service' => 'End of Service Loan',
                'housing' => 'Housing Loan',
                'emergency' => 'Emergency Loan',
                'advance_salary' => 'Advance Salary Deduction',
                'regular' => 'Regular Loan'
            ];
            $deduction_label = $type_map[$loan['loan_type']] ?? ucfirst(str_replace('_', ' ', $loan['loan_type'])) . ' Loan';
            $deduction_name = $deduction_label . ' - ' . $inv_no;
            
            // Add missing deductions
            for ($i = 0; $i < $installments; $i++) {
                $month_date = clone $start_date;
                $month_date->modify("+{$i} months");
                $month_year = $month_date->format('Y-m');
                
                // Check if deduction already exists for this month
                $check_stmt = $conDB->prepare(
                    "SELECT id FROM payroll_deductions 
                     WHERE emp_id = ? AND month = ? AND deduction = ? LIMIT 1"
                );
                $check_stmt->bind_param("sss", $emp_id, $month_year, $deduction_name);
                $check_stmt->execute();
                $exists = $check_stmt->get_result()->fetch_assoc();
                $check_stmt->close();
                
                if ($exists) {
                    continue; // Already exists, skip
                }
                
                // Insert new deduction
                $note = number_format($monthly_amount, 2, '.', '');
                $stmt_insert = $conDB->prepare(
                    "INSERT INTO payroll_deductions (emp_id, deduction, note, month, status) 
                     VALUES (?, ?, ?, ?, 1)"
                );
                $stmt_insert->bind_param("ssss", $emp_id, $deduction_name, $note, $month_year);
                
                if ($stmt_insert->execute()) {
                    $fixed_count++;
                } else {
                    $error_count++;
                }
                $stmt_insert->close();
            }
            
            $missing = intval($loan['installments']) - intval($loan['existing_count']);
            $results[] = [
                'inv_no' => $inv_no,
                'emp_name' => $loan['emp_name'],
                'expected' => $missing,
                'success' => true
            ];
        }
        
        echo '<div class="success-box">';
        echo '<strong>✅ Fixed ' . $fixed_count . ' missing deductions</strong>';
        if ($error_count > 0) {
            echo ' (' . $error_count . ' errors encountered)';
        }
        echo '</div>';
        
        if (!empty($results)) {
            echo '<table>';
            echo '<tr><th>Invoice</th><th>Employee</th><th>Deductions Created</th></tr>';
            foreach ($results as $result) {
                echo '<tr>';
                echo '<td><code>' . htmlspecialchars($result['inv_no']) . '</code></td>';
                echo '<td>' . htmlspecialchars($result['emp_name']) . '</td>';
                echo '<td><span class="status-good">✅ ' . $result['expected'] . '</span></td>';
                echo '</tr>';
            }
            echo '</table>';
        }
        
        echo '</div>';
    }
    
    // ============================================
    // 3. Verification After Fix
    // ============================================
    echo '<div class="section">';
    echo '<h2>3️⃣ Verification Results</h2>';
    
    $stmt = $conDB->prepare("
        SELECT 
            el.id,
            el.inv_no,
            el.installments,
            COUNT(pd.id) as deduction_count
        FROM emp_loan el
        LEFT JOIN payroll_deductions pd ON el.emp_id = pd.emp_id 
            AND pd.deduction LIKE CONCAT('%', el.inv_no, '%')
        WHERE el.status = 'approved' AND el.deduction_mode = 'automatic'
        GROUP BY el.id
    ");
    
    $stmt->execute();
    $verification_results = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    
    $all_good = true;
    foreach ($verification_results as $result) {
        if (intval($result['deduction_count']) < intval($result['installments'])) {
            $all_good = false;
            break;
        }
    }
    
    if ($all_good && !empty($verification_results)) {
        echo '<div class="success-box">✅ All approved loans now have complete deduction entries!</div>';
    } elseif (empty($verification_results)) {
        echo '<div class="info-box">No approved loans to verify.</div>';
    } else {
        echo '<div class="error-box">⚠️ Some loans still have missing deductions. Check the results above.</div>';
    }
    
    echo '</div>';
    
    // ============================================
    // 4. Summary
    // ============================================
    echo '<div class="section">';
    echo '<h2>✅ Summary</h2>';
    
    echo '<p>';
    echo '<strong>Total Approved Loans (Automatic Mode):</strong> ' . count($all_loans) . '<br>';
    echo '<strong>Loans with Complete Deductions:</strong> ' . (count($all_loans) - count($loans_missing_deductions)) . '<br>';
    echo '<strong>Loans with Missing Deductions (Before Fix):</strong> ' . count($loans_missing_deductions) . '<br>';
    echo '</p>';
    
    echo '<p>The automatic loan deduction system is now ready for payroll processing.</p>';
    
    echo '<div class="info-box">';
    echo '<strong>Next Steps:</strong><br>';
    echo '1. Review the results above to ensure all deductions were created correctly<br>';
    echo '2. Go to <a href="verify_loan_deductions.php" target="_blank">Verification Tool</a> for detailed status<br>';
    echo '3. Generate payroll for the next month to apply these deductions<br>';
    echo '</div>';
    
    echo '</div>';

} catch (Exception $e) {
    echo '<div class="error-box">';
    echo '<strong>Error:</strong> ' . htmlspecialchars($e->getMessage());
    echo '<br><br>';
    echo '<strong>Stack Trace:</strong><br>';
    echo '<pre>' . htmlspecialchars($e->getTraceAsString()) . '</pre>';
    echo '</div>';
}

?>

</div>
</body>
</html>
