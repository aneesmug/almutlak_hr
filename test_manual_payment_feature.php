<?php
/**
 * Manual Loan Payment Feature Test Script
 * 
 * This script tests the manual payment feature components:
 * 1. Directory structure
 * 2. Database schema
 * 3. Function availability
 * 4. File upload configuration
 */

require_once __DIR__ . '/includes/db.php';

echo "<!DOCTYPE html>
<html>
<head>
    <title>Manual Loan Payment Feature - Test Report</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { max-width: 1200px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        h1 { color: #333; border-bottom: 3px solid #007bff; padding-bottom: 10px; }
        h2 { color: #555; margin-top: 30px; border-bottom: 2px solid #28a745; padding-bottom: 8px; }
        .test-item { padding: 15px; margin: 10px 0; border-left: 4px solid #ddd; background: #fafafa; }
        .pass { border-left-color: #28a745; background: #d4edda; }
        .fail { border-left-color: #dc3545; background: #f8d7da; }
        .warning { border-left-color: #ffc107; background: #fff3cd; }
        .status { font-weight: bold; text-transform: uppercase; }
        .pass .status { color: #28a745; }
        .fail .status { color: #dc3545; }
        .warning .status { color: #ffc107; }
        .code { background: #2d2d2d; color: #f8f8f2; padding: 10px; border-radius: 4px; font-family: monospace; overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; margin: 15px 0; }
        th, td { padding: 10px; text-align: left; border-bottom: 1px solid #ddd; }
        th { background: #007bff; color: white; }
        tr:hover { background: #f1f1f1; }
        .summary { display: flex; justify-content: space-around; margin: 20px 0; }
        .summary-box { text-align: center; padding: 20px; border-radius: 8px; flex: 1; margin: 0 10px; }
        .summary-box h3 { margin: 0; font-size: 36px; }
        .summary-box p { margin: 5px 0 0 0; color: #666; }
        .pass-box { background: #d4edda; border: 2px solid #28a745; }
        .fail-box { background: #f8d7da; border: 2px solid #dc3545; }
        .warning-box { background: #fff3cd; border: 2px solid #ffc107; }
    </style>
</head>
<body>
    <div class='container'>
        <h1>📋 Manual Loan Payment Feature - Test Report</h1>
        <p style='color: #666;'>Generated: " . date('Y-m-d H:i:s') . "</p>
";

$pass_count = 0;
$fail_count = 0;
$warning_count = 0;

// Test 1: Check directory structure
echo "<h2>1️⃣ Directory Structure Tests</h2>";

$directories = [
    'assets/loan_manual_payments' => __DIR__ . '/assets/loan_manual_payments',
    'assets/loan_receipts' => __DIR__ . '/assets/loan_receipts',
    'assets/loan_payment_proofs' => __DIR__ . '/assets/loan_payment_proofs'
];

foreach ($directories as $name => $path) {
    $exists = is_dir($path);
    $writable = $exists ? is_writable($path) : false;
    
    if ($exists && $writable) {
        echo "<div class='test-item pass'><span class='status'>✓ PASS</span> - Directory <code>$name</code> exists and is writable<br><small>Path: $path</small></div>";
        $pass_count++;
    } elseif ($exists && !$writable) {
        echo "<div class='test-item warning'><span class='status'>⚠ WARNING</span> - Directory <code>$name</code> exists but is not writable<br><small>Path: $path</small></div>";
        $warning_count++;
    } else {
        echo "<div class='test-item fail'><span class='status'>✗ FAIL</span> - Directory <code>$name</code> does not exist<br><small>Path: $path</small></div>";
        $fail_count++;
    }
}

// Test 2: Check database schema
echo "<h2>2️⃣ Database Schema Tests</h2>";

// Check emp_loan_payments table
$tables_to_check = [
    'emp_loan' => ['id', 'emp_id', 'loan_amount', 'final_approved_amount', 'installments', 'monthly_deduction', 'start_date', 'end_date', 'total_payable', 'status', 'payment_proof_file', 'disbursement_receipt_id', 'disbursement_attachment'],
    'emp_loan_payments' => ['id', 'loan_id', 'payment_date', 'amount', 'receipt_id', 'attachment', 'payment_method', 'note']
];

foreach ($tables_to_check as $table => $columns) {
    $query = "SHOW COLUMNS FROM `$table`";
    $result = mysqli_query($conDB, $query);
    
    if ($result) {
        $existing_columns = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $existing_columns[] = $row['Field'];
        }
        
        $missing_columns = array_diff($columns, $existing_columns);
        
        if (empty($missing_columns)) {
            echo "<div class='test-item pass'><span class='status'>✓ PASS</span> - Table <code>$table</code> has all required columns<br>";
            echo "<small>Columns: " . implode(', ', $columns) . "</small></div>";
            $pass_count++;
        } else {
            echo "<div class='test-item fail'><span class='status'>✗ FAIL</span> - Table <code>$table</code> is missing columns<br>";
            echo "<small>Missing: " . implode(', ', $missing_columns) . "</small></div>";
            $fail_count++;
        }
    } else {
        echo "<div class='test-item fail'><span class='status'>✗ FAIL</span> - Table <code>$table</code> does not exist</div>";
        $fail_count++;
    }
}

// Test 3: Check AJAX handler
echo "<h2>3️⃣ Backend Code Tests</h2>";

$ajax_file = __DIR__ . '/includes/ajaxFile/ajaxLoan.php';
if (file_exists($ajax_file)) {
    $content = file_get_contents($ajax_file);
    
    // Check for add_manual_payment function
    if (strpos($content, "function add_manual_payment()") !== false) {
        echo "<div class='test-item pass'><span class='status'>✓ PASS</span> - Function <code>add_manual_payment()</code> exists in ajaxLoan.php</div>";
        $pass_count++;
    } else {
        echo "<div class='test-item fail'><span class='status'>✗ FAIL</span> - Function <code>add_manual_payment()</code> not found in ajaxLoan.php</div>";
        $fail_count++;
    }
    
    // Check for case statement
    if (strpos($content, "case 'add_manual_payment':") !== false || strpos($content, "case 'addManualPayment':") !== false) {
        echo "<div class='test-item pass'><span class='status'>✓ PASS</span> - AJAX handler case for manual payment exists</div>";
        $pass_count++;
    } else {
        echo "<div class='test-item fail'><span class='status'>✗ FAIL</span> - AJAX handler case for manual payment not found</div>";
        $fail_count++;
    }
    
    // Check for file upload handling
    if (strpos($content, "loan_manual_payments") !== false) {
        echo "<div class='test-item pass'><span class='status'>✓ PASS</span> - File upload to <code>loan_manual_payments</code> directory configured</div>";
        $pass_count++;
    } else {
        echo "<div class='test-item warning'><span class='status'>⚠ WARNING</span> - File upload path may not be configured correctly</div>";
        $warning_count++;
    }
} else {
    echo "<div class='test-item fail'><span class='status'>✗ FAIL</span> - File <code>includes/ajaxFile/ajaxLoan.php</code> not found</div>";
    $fail_count++;
}

// Test 4: Check frontend code
echo "<h2>4️⃣ Frontend Code Tests</h2>";

$view_employee_file = __DIR__ . '/view_employee.php';
if (file_exists($view_employee_file)) {
    $content = file_get_contents($view_employee_file);
    
    // Check for manual payment button
    if (strpos($content, "addManualPayment") !== false) {
        echo "<div class='test-item pass'><span class='status'>✓ PASS</span> - Manual payment button exists in view_employee.php</div>";
        $pass_count++;
    } else {
        echo "<div class='test-item fail'><span class='status'>✗ FAIL</span> - Manual payment button not found in view_employee.php</div>";
        $fail_count++;
    }
    
    // Check for modal JavaScript
    if (strpos($content, "ajaxType: 'addManualPayment'") !== false || strpos($content, "ajaxType', 'addManualPayment'") !== false) {
        echo "<div class='test-item pass'><span class='status'>✓ PASS</span> - Manual payment modal JavaScript exists</div>";
        $pass_count++;
    } else {
        echo "<div class='test-item fail'><span class='status'>✗ FAIL</span> - Manual payment modal JavaScript not found</div>";
        $fail_count++;
    }
    
    // Check for payment method column in history table
    if (strpos($content, "payment_method") !== false) {
        echo "<div class='test-item pass'><span class='status'>✓ PASS</span> - Payment history table includes payment_method column</div>";
        $pass_count++;
    } else {
        echo "<div class='test-item warning'><span class='status'>⚠ WARNING</span> - Payment method column may not be displayed in history table</div>";
        $warning_count++;
    }
    
    // Check for enhanced loan summary
    if (strpos($content, "loan_details") !== false || strpos($content, "payment_summary") !== false) {
        echo "<div class='test-item pass'><span class='status'>✓ PASS</span> - Enhanced loan summary display exists</div>";
        $pass_count++;
    } else {
        echo "<div class='test-item warning'><span class='status'>⚠ WARNING</span> - Enhanced loan summary may not be implemented</div>";
        $warning_count++;
    }
} else {
    echo "<div class='test-item fail'><span class='status'>✗ FAIL</span> - File <code>view_employee.php</code> not found</div>";
    $fail_count++;
}

// Test 5: PHP Configuration
echo "<h2>5️⃣ PHP Configuration Tests</h2>";

$upload_max = ini_get('upload_max_filesize');
$post_max = ini_get('post_max_size');
$file_uploads = ini_get('file_uploads');

if ($file_uploads) {
    echo "<div class='test-item pass'><span class='status'>✓ PASS</span> - File uploads are enabled</div>";
    $pass_count++;
} else {
    echo "<div class='test-item fail'><span class='status'>✗ FAIL</span> - File uploads are disabled in PHP configuration</div>";
    $fail_count++;
}

if (intval($upload_max) >= 10) {
    echo "<div class='test-item pass'><span class='status'>✓ PASS</span> - <code>upload_max_filesize</code> is $upload_max (sufficient for 10MB files)</div>";
    $pass_count++;
} else {
    echo "<div class='test-item warning'><span class='status'>⚠ WARNING</span> - <code>upload_max_filesize</code> is $upload_max (may be too small, recommend 10M or higher)</div>";
    $warning_count++;
}

if (intval($post_max) >= 10) {
    echo "<div class='test-item pass'><span class='status'>✓ PASS</span> - <code>post_max_size</code> is $post_max (sufficient)</div>";
    $pass_count++;
} else {
    echo "<div class='test-item warning'><span class='status'>⚠ WARNING</span> - <code>post_max_size</code> is $post_max (may be too small)</div>";
    $warning_count++;
}

// Test 6: Sample Data Check
echo "<h2>6️⃣ Sample Data Tests</h2>";

$sql_active_loans = "SELECT COUNT(*) as count FROM `emp_loan` WHERE `status` = 'approved'";
$result = mysqli_query($conDB, $sql_active_loans);
$active_loan_count = mysqli_fetch_assoc($result)['count'];

if ($active_loan_count > 0) {
    echo "<div class='test-item pass'><span class='status'>✓ PASS</span> - Found $active_loan_count active loan(s) in database (ready for manual payment testing)</div>";
    $pass_count++;
    
    // Show sample loans
    $sql_sample = "SELECT el.id, el.inv_no, el.emp_id, e.name as emp_name, el.total_payable, el.status 
                   FROM `emp_loan` el 
                   JOIN `employees` e ON el.emp_id = e.empid 
                   WHERE el.status = 'approved' 
                   LIMIT 5";
    $sample_result = mysqli_query($conDB, $sql_sample);
    
    echo "<table>";
    echo "<tr><th>Loan ID</th><th>Invoice</th><th>Employee</th><th>Total Payable</th><th>Status</th></tr>";
    while ($row = mysqli_fetch_assoc($sample_result)) {
        echo "<tr>";
        echo "<td>{$row['id']}</td>";
        echo "<td>{$row['inv_no']}</td>";
        echo "<td>{$row['emp_name']} (ID: {$row['emp_id']})</td>";
        echo "<td>" . number_format($row['total_payable'], 2) . " SAR</td>";
        echo "<td><span style='color: #28a745; font-weight: bold;'>{$row['status']}</span></td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<div class='test-item warning'><span class='status'>⚠ WARNING</span> - No active loans found (create test loans for testing)</div>";
    $warning_count++;
}

// Summary
$total_tests = $pass_count + $fail_count + $warning_count;

echo "<h2>📊 Test Summary</h2>";
echo "<div class='summary'>";
echo "<div class='summary-box pass-box'><h3>$pass_count</h3><p>Passed</p></div>";
echo "<div class='summary-box fail-box'><h3>$fail_count</h3><p>Failed</p></div>";
echo "<div class='summary-box warning-box'><h3>$warning_count</h3><p>Warnings</p></div>";
echo "</div>";

$pass_percentage = $total_tests > 0 ? round(($pass_count / $total_tests) * 100, 1) : 0;

if ($fail_count === 0 && $warning_count === 0) {
    echo "<div class='test-item pass' style='font-size: 18px; text-align: center; padding: 30px;'>";
    echo "<span class='status' style='font-size: 24px;'>✓ ALL TESTS PASSED</span><br>";
    echo "<p>The manual loan payment feature is fully configured and ready for use!</p>";
    echo "</div>";
} elseif ($fail_count === 0) {
    echo "<div class='test-item warning' style='font-size: 18px; text-align: center; padding: 30px;'>";
    echo "<span class='status' style='font-size: 24px;'>⚠ PASSED WITH WARNINGS</span><br>";
    echo "<p>The feature should work but has some configuration warnings. Review the warnings above.</p>";
    echo "</div>";
} else {
    echo "<div class='test-item fail' style='font-size: 18px; text-align: center; padding: 30px;'>";
    echo "<span class='status' style='font-size: 24px;'>✗ TESTS FAILED</span><br>";
    echo "<p>Critical issues found. Please fix the failed tests before using this feature.</p>";
    echo "</div>";
}

echo "<h2>🔧 Next Steps</h2>";
echo "<div class='test-item'>";
echo "<ol>";
echo "<li>Review all failed tests and warnings above</li>";
echo "<li>Fix any directory permission issues</li>";
echo "<li>Verify database schema changes were applied</li>";
echo "<li>Test manual payment on a sample loan</li>";
echo "<li>Verify file uploads work correctly</li>";
echo "<li>Check payment history table displays correctly</li>";
echo "</ol>";
echo "</div>";

echo "
    </div>
</body>
</html>
";
?>
