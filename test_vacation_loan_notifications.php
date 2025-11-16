<?php
/**
 * Quick Diagnostic Test for Vacation and Loan Notifications
 * 
 * This script tests if notification functions are available in the AJAX files
 */

echo "<h1>Vacation & Loan Notification Diagnostic</h1>";
echo "<style>
    body { font-family: Arial, sans-serif; margin: 20px; }
    .success { color: green; font-weight: bold; }
    .error { color: red; font-weight: bold; }
    .warning { color: orange; font-weight: bold; }
    table { border-collapse: collapse; width: 100%; margin: 20px 0; }
    th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
    th { background-color: #4CAF50; color: white; }
    code { background: #f4f4f4; padding: 2px 6px; border-radius: 3px; }
</style>";

echo "<h2>Test 1: Simulate ajaxVacation.php Environment</h2>";
require_once __DIR__ . '/includes/db.php';
include("./includes/helper_functions.php");

$functions_to_check = [
    'getEmployeeDetailsForApproval',
    'create_browser_notification',
    'send_approval_email',
    'get_setting'
];

echo "<table>";
echo "<tr><th>Function Name</th><th>Status in ajaxVacation Context</th></tr>";
foreach ($functions_to_check as $func) {
    $exists = function_exists($func);
    $status = $exists ? '<span class="success">✓ Available</span>' : '<span class="error">✗ NOT FOUND</span>';
    echo "<tr><td>$func()</td><td>$status</td></tr>";
}
echo "</table>";

echo "<h2>Test 2: Simulate ajaxLoan.php Environment (BEFORE Fix)</h2>";
echo "<p><strong>Testing if helper_functions.php is included in ajaxLoan.php...</strong></p>";

// Read first 50 lines of ajaxLoan.php
$loan_file = __DIR__ . '/includes/ajaxFile/ajaxLoan.php';
$loan_content = file_get_contents($loan_file);
$first_lines = implode("\n", array_slice(explode("\n", $loan_content), 0, 30));

if (strpos($first_lines, 'helper_functions.php') !== false) {
    echo "<p class='success'>✓ helper_functions.php IS included in ajaxLoan.php</p>";
    echo "<p>The fix has been applied! Notifications should now work for Loan Requests.</p>";
} else {
    echo "<p class='error'>✗ helper_functions.php is NOT included in ajaxLoan.php</p>";
    echo "<p class='warning'>⚠ This is the problem! The notification functions are not available in the loan request handler.</p>";
    echo "<p><strong>Solution:</strong> Add this line after line 24 in <code>includes/ajaxFile/ajaxLoan.php</code>:</p>";
    echo "<code>include(\"./../../includes/helper_functions.php\");</code>";
}

echo "<h2>Test 3: Check Recent Error Logs</h2>";
echo "<p>Looking for notification-related errors in PHP error log...</p>";

$error_log_locations = [
    'C:/xampp/php/logs/php_error_log',
    'C:/xampp/apache/logs/error.log',
    ini_get('error_log')
];

$found_logs = false;
foreach ($error_log_locations as $log_path) {
    if ($log_path && file_exists($log_path)) {
        $found_logs = true;
        echo "<h3>Log: " . htmlspecialchars($log_path) . "</h3>";
        
        // Read last 100 lines
        $lines = file($log_path);
        $recent_lines = array_slice($lines, -100);
        
        // Filter for notification-related errors
        $notification_errors = array_filter($recent_lines, function($line) {
            return (
                stripos($line, 'applyVacation') !== false ||
                stripos($line, 'applyLeave') !== false ||
                stripos($line, 'apply_for_loan') !== false ||
                stripos($line, 'notification') !== false ||
                stripos($line, 'send_approval_email') !== false
            );
        });
        
        if (!empty($notification_errors)) {
            echo "<p><strong>Found " . count($notification_errors) . " notification-related log entries:</strong></p>";
            echo "<pre style='background: #f4f4f4; padding: 10px; border-radius: 5px; max-height: 400px; overflow-y: scroll;'>";
            foreach ($notification_errors as $error) {
                echo htmlspecialchars($error);
            }
            echo "</pre>";
        } else {
            echo "<p>No notification-related errors found in recent logs.</p>";
        }
        break; // Only show first found log
    }
}

if (!$found_logs) {
    echo "<p class='warning'>⚠ Could not find PHP error log file. Common locations:</p>";
    echo "<ul>";
    foreach ($error_log_locations as $path) {
        if ($path) echo "<li>" . htmlspecialchars($path) . "</li>";
    }
    echo "</ul>";
}

echo "<h2>Test 4: Test Actual Notification Function</h2>";

// Get a test employee with email
$test_query = mysqli_query($conDB, "
    SELECT e.emp_id, e.name, al.email
    FROM employees e
    INNER JOIN admin_login al ON e.emp_id = al.emp_id
    WHERE e.status = 1 AND al.email IS NOT NULL AND al.email != ''
    LIMIT 1
");

if ($test_query && mysqli_num_rows($test_query) > 0) {
    $test_emp = mysqli_fetch_assoc($test_query);
    
    echo "<p>Testing with Employee ID: <strong>" . $test_emp['emp_id'] . "</strong></p>";
    echo "<p>Employee Name: <strong>" . htmlspecialchars($test_emp['name']) . "</strong></p>";
    echo "<p>Employee Email: <strong>" . htmlspecialchars($test_emp['email']) . "</strong></p>";
    
    // Test browser notification
    echo "<h3>Creating Test Browser Notification...</h3>";
    if (function_exists('create_browser_notification')) {
        $notif_result = create_browser_notification(
            $conDB,
            $test_emp['emp_id'],
            "Test Notification",
            "This is a test notification from the diagnostic script.",
            "dashboard.php"
        );
        
        if ($notif_result) {
            echo "<p class='success'>✓ Browser notification created successfully!</p>";
            echo "<p>Check the notifications icon in the user's account to verify.</p>";
        } else {
            echo "<p class='error'>✗ Browser notification creation FAILED</p>";
            echo "<p>Check PHP error log for details.</p>";
        }
    } else {
        echo "<p class='error'>✗ create_browser_notification function NOT available</p>";
    }
    
    // Test email notification
    echo "<h3>Testing Email Function (will NOT actually send)...</h3>";
    if (function_exists('send_approval_email')) {
        echo "<p class='success'>✓ send_approval_email function is available</p>";
        echo "<p>Note: Actual email sending is disabled in this test to avoid spam.</p>";
        echo "<p>The function would send to: <code>" . htmlspecialchars($test_emp['email']) . "</code></p>";
    } else {
        echo "<p class='error'>✗ send_approval_email function NOT available</p>";
    }
    
} else {
    echo "<p class='warning'>⚠ No employees with email addresses found for testing</p>";
}

echo "<hr>";
echo "<h2>Summary & Next Steps</h2>";
echo "<div style='background: #d1ecf1; padding: 15px; border-left: 4px solid #0c5460; margin-top: 20px;'>";
echo "<strong>What to do next:</strong><br><br>";
echo "1. <strong>If ajaxLoan.php missing helper_functions.php:</strong> The fix has been applied. Test submitting a loan request.<br>";
echo "2. <strong>Submit test requests:</strong><br>";
echo "   - Annual Vacation Request<br>";
echo "   - Leave Request<br>";
echo "   - Loan Request<br>";
echo "3. <strong>Check PHP error log</strong> for lines containing:<br>";
echo "   - <code>applyVacation: Attempting to send notification</code><br>";
echo "   - <code>applyLeave: Attempting to send notification</code><br>";
echo "   - <code>apply_for_loan: Attempting to send notification</code><br>";
echo "4. <strong>Verify results:</strong> Look for SUCCESS or FAILED messages in error log<br>";
echo "5. <strong>Check recipient accounts:</strong> Login as the approver to see if notifications appear<br>";
echo "</div>";

echo "<div style='background: #fff3cd; padding: 15px; border-left: 4px solid #ffc107; margin-top: 20px;'>";
echo "<strong>⚠ Important:</strong><br>";
echo "Smart Request works because <code>open_request.php</code> includes helper_functions via <code>session_check.php</code><br>";
echo "AJAX files must include helper_functions.php directly:<br>";
echo "<code>include(\"./../../includes/helper_functions.php\");</code>";
echo "</div>";
?>
