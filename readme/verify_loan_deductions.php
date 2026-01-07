<?php
/**
 * Loan Payroll Deduction Verification Script
 * 
 * This script verifies that loan deductions are properly created and will be applied during payroll generation.
 * Provides diagnostic information and troubleshooting guidance.
 * 
 * Usage: Access via browser at http://localhost/almutlak/system/testing/verify_loan_deductions.php
 */

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/session_check.php';

// Only admins can access
if (!($is_system_admin ?? false)) {
    echo "<h2>Access Denied</h2>";
    echo "<p>Only system administrators can access this verification script.</p>";
    exit;
}

header('Content-Type: text/html; charset=UTF-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Loan Payroll Deduction Verification</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 20px;
            background: #f5f5f5;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }
        h1 { color: #333; border-bottom: 3px solid #007bff; padding-bottom: 10px; }
        h2 { color: #007bff; margin-top: 30px; }
        h3 { color: #666; margin-top: 15px; }
        
        .section {
            background: white;
            padding: 20px;
            margin: 20px 0;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .status-good { color: #28a745; font-weight: bold; }
        .status-warning { color: #ffc107; font-weight: bold; }
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
        
        table tr:hover {
            background-color: #f9f9f9;
        }
        
        .badge {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 3px;
            font-size: 12px;
            font-weight: bold;
            margin-right: 5px;
        }
        
        .badge-approved { background: #d4edda; color: #155724; }
        .badge-pending { background: #fff3cd; color: #856404; }
        .badge-automatic { background: #d1ecf1; color: #0c5460; }
        .badge-manual { background: #f8d7da; color: #721c24; }
        
        .info-box {
            background: #e7f3ff;
            border-left: 4px solid #007bff;
            padding: 15px;
            margin: 10px 0;
        }
        
        .error-box {
            background: #f8d7da;
            border-left: 4px solid #dc3545;
            padding: 15px;
            margin: 10px 0;
        }
        
        .success-box {
            background: #d4edda;
            border-left: 4px solid #28a745;
            padding: 15px;
            margin: 10px 0;
        }
        
        code {
            background: #f4f4f4;
            padding: 2px 6px;
            border-radius: 3px;
            font-family: 'Courier New', monospace;
        }
        
        pre {
            background: #f4f4f4;
            padding: 10px;
            border-radius: 3px;
            overflow-x: auto;
        }
    </style>
</head>
<body>
<div class="container">
    <h1>🔍 Loan Payroll Deduction Verification</h1>
    <p>This tool verifies that loan deductions are properly set up and will be applied during payroll generation.</p>

<?php

try {
    // ============================================
    // 1. Check for Approved Loans
    // ============================================
    echo '<div class="section">';
    echo '<h2>1️⃣ Approved Loans with Deduction Setup</h2>';
    
    $stmt = $conDB->prepare("
        SELECT 
            el.id,
            el.inv_no,
            el.emp_id,
            el.loan_type,
            el.loan_amount,
            el.monthly_deduction,
            el.installments,
            el.start_date,
            el.deduction_mode,
            el.status,
            e.name as emp_name
        FROM emp_loan el
        LEFT JOIN employees e ON el.emp_id = e.emp_id
        WHERE el.status = 'approved'
        ORDER BY el.created_at DESC
        LIMIT 50
    ");
    
    $stmt->execute();
    $approved_loans = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    
    if (empty($approved_loans)) {
        echo '<div class="info-box">No approved loans found in the system.</div>';
    } else {
        echo '<table>';
        echo '<tr>';
        echo '<th>Invoice No</th>';
        echo '<th>Employee</th>';
        echo '<th>Type</th>';
        echo '<th>Amount</th>';
        echo '<th>Monthly</th>';
        echo '<th>Installments</th>';
        echo '<th>Start Date</th>';
        echo '<th>Mode</th>';
        echo '<th>Deductions Created</th>';
        echo '</tr>';
        
        foreach ($approved_loans as $loan) {
            // Check how many deductions exist
            $deduction_stmt = $conDB->prepare("
                SELECT COUNT(*) as count FROM payroll_deductions 
                WHERE emp_id = ? AND deduction LIKE ?
            ");
            $pattern = '%' . $loan['inv_no'] . '%';
            $deduction_stmt->bind_param("ss", $loan['emp_id'], $pattern);
            $deduction_stmt->execute();
            $deduction_count = $deduction_stmt->get_result()->fetch_assoc()['count'];
            $deduction_stmt->close();
            
            $expected_count = intval($loan['installments']);
            $status_badge = '<span class="badge badge-' . ($loan['deduction_mode'] ?? 'automatic') . '">' . 
                            ucfirst($loan['deduction_mode'] ?? 'automatic') . '</span>';
            
            $deductions_status = $deduction_count >= $expected_count ? 
                '<span class="status-good">✅ ' . $deduction_count . '/' . $expected_count . '</span>' :
                '<span class="status-bad">❌ ' . $deduction_count . '/' . $expected_count . '</span>';
            
            echo '<tr>';
            echo '<td><strong>' . htmlspecialchars($loan['inv_no']) . '</strong></td>';
            echo '<td>' . htmlspecialchars($loan['emp_name']) . '</td>';
            echo '<td>' . htmlspecialchars($loan['loan_type']) . '</td>';
            echo '<td>SAR ' . number_format($loan['loan_amount'], 2) . '</td>';
            echo '<td>SAR ' . number_format($loan['monthly_deduction'], 2) . '</td>';
            echo '<td>' . $loan['installments'] . '</td>';
            echo '<td>' . $loan['start_date'] . '</td>';
            echo '<td>' . $status_badge . '</td>';
            echo '<td>' . $deductions_status . '</td>';
            echo '</tr>';
        }
        
        echo '</table>';
    }
    
    echo '</div>';
    
    // ============================================
    // 2. Check Payroll Deductions for Approved Loans
    // ============================================
    echo '<div class="section">';
    echo '<h2>2️⃣ Payroll Deduction Entries</h2>';
    
    $stmt = $conDB->prepare("
        SELECT 
            pd.id,
            pd.emp_id,
            pd.deduction,
            pd.note,
            pd.month,
            pd.status,
            e.name as emp_name
        FROM payroll_deductions pd
        LEFT JOIN employees e ON pd.emp_id = e.emp_id
        WHERE pd.deduction LIKE '%Loan%' OR pd.deduction LIKE '%LN-%'
        ORDER BY pd.month DESC, pd.emp_id
        LIMIT 100
    ");
    
    $stmt->execute();
    $deductions = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    
    if (empty($deductions)) {
        echo '<div class="info-box">No loan-related payroll deductions found. They will be created when loans are approved.</div>';
    } else {
        echo '<p>Found <strong>' . count($deductions) . '</strong> deduction entries.</p>';
        echo '<table>';
        echo '<tr>';
        echo '<th>Employee</th>';
        echo '<th>Deduction Name</th>';
        echo '<th>Amount</th>';
        echo '<th>Month</th>';
        echo '<th>Status</th>';
        echo '<th>Created</th>';
        echo '</tr>';
        
        $grouped = [];
        foreach ($deductions as $ded) {
            $month = $ded['month'];
            if (!isset($grouped[$month])) {
                $grouped[$month] = [];
            }
            $grouped[$month][] = $ded;
        }
        
        foreach ($grouped as $month => $month_deductions) {
            foreach ($month_deductions as $ded) {
                $status_text = $ded['status'] == 1 ? '✅ Active' : '❌ Inactive';
                echo '<tr>';
                echo '<td>' . htmlspecialchars($ded['emp_name']) . '</td>';
                echo '<td><code>' . htmlspecialchars($ded['deduction']) . '</code></td>';
                echo '<td>SAR ' . number_format(floatval($ded['note']), 2) . '</td>';
                echo '<td><strong>' . $month . '</strong></td>';
                echo '<td>' . $status_text . '</td>';
                echo '<td>' . date('Y-m-d', strtotime($ded['month'] . '-01')) . '</td>';
                echo '</tr>';
            }
        }
        
        echo '</table>';
    }
    
    echo '</div>';
    
    // ============================================
    // 3. Employee-Month Verification
    // ============================================
    echo '<div class="section">';
    echo '<h2>3️⃣ Employees with Active Loans in Current Month</h2>';
    
    $current_month = date('Y-m');
    
    $stmt = $conDB->prepare("
        SELECT 
            pd.emp_id,
            e.name as emp_name,
            COUNT(*) as deduction_count,
            SUM(CAST(pd.note AS DECIMAL(10,2))) as total_deduction,
            GROUP_CONCAT(DISTINCT pd.deduction SEPARATOR ', ') as deductions
        FROM payroll_deductions pd
        LEFT JOIN employees e ON pd.emp_id = e.emp_id
        WHERE pd.month = ? AND (pd.deduction LIKE '%Loan%' OR pd.deduction LIKE '%LN-%')
        GROUP BY pd.emp_id, e.name
        ORDER BY total_deduction DESC
    ");
    
    $stmt->bind_param("s", $current_month);
    $stmt->execute();
    $current_month_deductions = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    
    if (empty($current_month_deductions)) {
        echo '<div class="info-box">No loan deductions scheduled for ' . $current_month . '.</div>';
    } else {
        echo '<p>For month: <strong>' . $current_month . '</strong></p>';
        echo '<table>';
        echo '<tr>';
        echo '<th>Employee</th>';
        echo '<th>Deduction Count</th>';
        echo '<th>Total Amount</th>';
        echo '<th>Deductions</th>';
        echo '</tr>';
        
        foreach ($current_month_deductions as $emp_ded) {
            echo '<tr>';
            echo '<td><strong>' . htmlspecialchars($emp_ded['emp_name']) . '</strong></td>';
            echo '<td>' . $emp_ded['deduction_count'] . '</td>';
            echo '<td><strong>SAR ' . number_format(floatval($emp_ded['total_deduction']), 2) . '</strong></td>';
            echo '<td><small>' . htmlspecialchars($emp_ded['deductions']) . '</small></td>';
            echo '</tr>';
        }
        
        echo '</table>';
    }
    
    echo '</div>';
    
    // ============================================
    // 4. Data Consistency Check
    // ============================================
    echo '<div class="section">';
    echo '<h2>4️⃣ Data Consistency Checks</h2>';
    
    $checks = [];
    
    // Check 1: Loans with missing deductions
    $stmt = $conDB->prepare("
        SELECT el.id, el.inv_no, el.emp_id, el.installments,
               COUNT(pd.id) as deduction_count
        FROM emp_loan el
        LEFT JOIN payroll_deductions pd ON el.emp_id = pd.emp_id 
            AND pd.deduction LIKE CONCAT('%', el.inv_no, '%')
        WHERE el.status = 'approved' AND el.deduction_mode = 'automatic'
        GROUP BY el.id, el.inv_no, el.emp_id, el.installments
        HAVING deduction_count < el.installments
        LIMIT 10
    ");
    $stmt->execute();
    $missing_deductions = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    
    if (!empty($missing_deductions)) {
        echo '<div class="error-box">';
        echo '<strong>⚠️ WARNING: ' . count($missing_deductions) . ' approved loans missing deduction entries</strong>';
        echo '<table style="margin-top: 10px;">';
        echo '<tr><th>Invoice</th><th>Employee ID</th><th>Expected</th><th>Found</th></tr>';
        foreach ($missing_deductions as $loan) {
            echo '<tr>';
            echo '<td><code>' . htmlspecialchars($loan['inv_no']) . '</code></td>';
            echo '<td>' . htmlspecialchars($loan['emp_id']) . '</td>';
            echo '<td>' . $loan['installments'] . '</td>';
            echo '<td><span class="status-bad">' . $loan['deduction_count'] . '</span></td>';
            echo '</tr>';
        }
        echo '</table>';
        echo '<p><strong>Action Required:</strong> Run <code>fix_loan_deductions.php</code> to recreate missing deductions.</p>';
        echo '</div>';
    } else {
        echo '<div class="success-box">✅ All approved loans have complete deduction entries.</div>';
    }
    
    // Check 2: Deductions with wrong amounts
    $stmt = $conDB->prepare("
        SELECT 
            pd.id,
            pd.emp_id,
            pd.deduction,
            pd.note as stored_amount,
            el.monthly_deduction as expected_amount,
            ABS(CAST(pd.note AS DECIMAL(10,2)) - el.monthly_deduction) as difference
        FROM payroll_deductions pd
        LEFT JOIN emp_loan el ON pd.emp_id = el.emp_id 
            AND pd.deduction LIKE CONCAT('%', el.inv_no, '%')
        WHERE el.id IS NOT NULL 
            AND ABS(CAST(pd.note AS DECIMAL(10,2)) - el.monthly_deduction) > 0.01
        LIMIT 10
    ");
    $stmt->execute();
    $wrong_amounts = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    
    if (!empty($wrong_amounts)) {
        echo '<div class="error-box">';
        echo '<strong>⚠️ WARNING: ' . count($wrong_amounts) . ' deductions with wrong amounts</strong>';
        echo '<table style="margin-top: 10px;">';
        echo '<tr><th>Deduction</th><th>Stored</th><th>Expected</th><th>Diff</th></tr>';
        foreach ($wrong_amounts as $ded) {
            echo '<tr>';
            echo '<td><small>' . htmlspecialchars(substr($ded['deduction'], 0, 50)) . '</small></td>';
            echo '<td>SAR ' . number_format(floatval($ded['stored_amount']), 2) . '</td>';
            echo '<td>SAR ' . number_format($ded['expected_amount'], 2) . '</td>';
            echo '<td><span class="status-bad">SAR ' . number_format($ded['difference'], 2) . '</span></td>';
            echo '</tr>';
        }
        echo '</table>';
        echo '</div>';
    } else {
        echo '<div class="success-box">✅ All deduction amounts match expected values.</div>';
    }
    
    echo '</div>';
    
    // ============================================
    // 5. Payroll Processing Simulation
    // ============================================
    echo '<div class="section">';
    echo '<h2>5️⃣ Next Payroll Processing Preview</h2>';
    
    $next_month = date('Y-m', strtotime('first day of next month'));
    
    $stmt = $conDB->prepare("
        SELECT 
            pd.emp_id,
            e.name,
            COUNT(*) as loan_deduction_count,
            SUM(CAST(pd.note AS DECIMAL(10,2))) as total_loan_deduction
        FROM payroll_deductions pd
        LEFT JOIN employees e ON pd.emp_id = e.emp_id
        WHERE pd.month = ? AND (pd.deduction LIKE '%Loan%' OR pd.deduction LIKE '%LN-%')
        GROUP BY pd.emp_id, e.name
        ORDER BY total_loan_deduction DESC
    ");
    
    $stmt->bind_param("s", $next_month);
    $stmt->execute();
    $next_month_preview = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    
    echo '<p>When payroll is generated for <strong>' . $next_month . '</strong>:</p>';
    
    if (empty($next_month_preview)) {
        echo '<div class="info-box">No loan deductions scheduled for next month (' . $next_month . ').</div>';
    } else {
        $total_all = 0;
        echo '<table>';
        echo '<tr><th>Employee</th><th>Loans Deducting</th><th>Total Deduction</th></tr>';
        
        foreach ($next_month_preview as $preview) {
            $total_all += floatval($preview['total_loan_deduction']);
            echo '<tr>';
            echo '<td>' . htmlspecialchars($preview['name']) . '</td>';
            echo '<td>' . $preview['loan_deduction_count'] . ' loan(s)</td>';
            echo '<td><strong>SAR ' . number_format(floatval($preview['total_loan_deduction']), 2) . '</strong></td>';
            echo '</tr>';
        }
        
        echo '<tr style="font-weight: bold; background: #f0f0f0;">';
        echo '<td colspan="2">TOTAL DEDUCTIONS FOR ALL EMPLOYEES</td>';
        echo '<td>SAR ' . number_format($total_all, 2) . '</td>';
        echo '</tr>';
        echo '</table>';
    }
    
    echo '</div>';
    
    // ============================================
    // 6. Manual Deduction Mode Check
    // ============================================
    echo '<div class="section">';
    echo '<h2>6️⃣ Manual Deduction Mode Loans</h2>';
    
    $stmt = $conDB->prepare("
        SELECT id, inv_no, emp_id, loan_type, loan_amount, deduction_mode 
        FROM emp_loan 
        WHERE deduction_mode = 'manual' AND status = 'approved'
    ");
    $stmt->execute();
    $manual_loans = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    
    if (empty($manual_loans)) {
        echo '<div class="info-box">No loans set to manual deduction mode.</div>';
    } else {
        echo '<p>These approved loans use manual deduction mode and will NOT auto-deduct during payroll:</p>';
        echo '<table>';
        echo '<tr><th>Invoice</th><th>Employee ID</th><th>Type</th><th>Amount</th></tr>';
        foreach ($manual_loans as $loan) {
            echo '<tr>';
            echo '<td><code>' . htmlspecialchars($loan['inv_no']) . '</code></td>';
            echo '<td>' . htmlspecialchars($loan['emp_id']) . '</td>';
            echo '<td>' . htmlspecialchars($loan['loan_type']) . '</td>';
            echo '<td>SAR ' . number_format($loan['loan_amount'], 2) . '</td>';
            echo '</tr>';
        }
        echo '</table>';
    }
    
    echo '</div>';
    
    // ============================================
    // 7. Summary and Recommendations
    // ============================================
    echo '<div class="section">';
    echo '<h2>✅ System Status Summary</h2>';
    
    $total_approved = count($approved_loans);
    $total_with_deductions = 0;
    foreach ($approved_loans as $loan) {
        $deduction_stmt = $conDB->prepare("SELECT COUNT(*) as count FROM payroll_deductions WHERE emp_id = ? AND deduction LIKE ?");
        $pattern = '%' . $loan['inv_no'] . '%';
        $deduction_stmt->bind_param("ss", $loan['emp_id'], $pattern);
        $deduction_stmt->execute();
        $count = $deduction_stmt->get_result()->fetch_assoc()['count'];
        $deduction_stmt->close();
        if ($count > 0) $total_with_deductions++;
    }
    
    echo '<p>';
    echo '<strong>Total Approved Loans:</strong> ' . $total_approved . '<br>';
    echo '<strong>With Deductions Created:</strong> ' . $total_with_deductions . '<br>';
    echo '<strong>Completion Rate:</strong> ' . ($total_approved > 0 ? round(($total_with_deductions / $total_approved) * 100, 1) : 0) . '%<br>';
    echo '</p>';
    
    if ($total_with_deductions == $total_approved && $total_approved > 0) {
        echo '<div class="success-box">';
        echo '<strong>✅ System is working correctly!</strong><br>';
        echo 'All approved loans have payroll deductions set up and will be auto-deducted during payroll generation.';
        echo '</div>';
    } elseif ($total_with_deductions == 0 && $total_approved == 0) {
        echo '<div class="info-box">';
        echo '<strong>ℹ️ No approved loans yet</strong><br>';
        echo 'Create and approve a loan through the system to test automatic deduction setup.';
        echo '</div>';
    } else {
        echo '<div class="error-box">';
        echo '<strong>❌ Some loans missing deductions</strong><br>';
        echo 'Run the fix script or manually recreate missing deductions.';
        echo '</div>';
    }
    
    echo '</div>';
    
    // ============================================
    // 8. Related Links
    // ============================================
    echo '<div class="section">';
    echo '<h2>📚 Related Documents & Tools</h2>';
    echo '<ul>';
    echo '<li><a href="LOAN_PAYROLL_DEDUCTION_FLOW.md" target="_blank">📖 Complete Implementation Guide</a></li>';
    echo '<li><a href="fix_double_deduction.php" target="_blank">🔧 Double Deduction Fixer</a></li>';
    echo '<li><a href="diagnose_double_deduction.php" target="_blank">🔍 Double Deduction Diagnostic</a></li>';
    echo '<li><a href="../all_applied_loan.php" target="_blank">💼 View All Loans</a></li>';
    echo '</ul>';
    echo '</div>';

} catch (Exception $e) {
    echo '<div class="error-box">';
    echo '<strong>Error:</strong> ' . htmlspecialchars($e->getMessage());
    echo '</div>';
}

?>

</div>
</body>
</html>
