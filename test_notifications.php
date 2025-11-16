<?php
/**
 * Test Notification System
 * This file tests if notifications and emails are working correctly
 */

require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/helper_functions.php';

echo "<h1>Notification System Test</h1>";
echo "<hr>";

// Test 1: Check if functions exist
echo "<h2>1. Function Availability Check</h2>";
echo "create_browser_notification: " . (function_exists('create_browser_notification') ? '<span style="color:green">✓ EXISTS</span>' : '<span style="color:red">✗ NOT FOUND</span>') . "<br>";
echo "send_approval_email: " . (function_exists('send_approval_email') ? '<span style="color:green">✓ EXISTS</span>' : '<span style="color:red">✗ NOT FOUND</span>') . "<br>";
echo "getEmployeeDetailsForApproval: " . (function_exists('getEmployeeDetailsForApproval') ? '<span style="color:green">✓ EXISTS</span>' : '<span style="color:red">✗ NOT FOUND</span>') . "<br>";
echo "<hr>";

// Test 2: Check database table
echo "<h2>2. Database Table Check</h2>";
$table_check = mysqli_query($conDB, "SHOW TABLES LIKE 'user_notifications'");
if ($table_check && mysqli_num_rows($table_check) > 0) {
    echo "<span style='color:green'>✓ user_notifications table EXISTS</span><br>";
    
    // Get table structure
    $structure = mysqli_query($conDB, "DESCRIBE user_notifications");
    echo "<h3>Table Structure:</h3>";
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    while ($row = mysqli_fetch_assoc($structure)) {
        echo "<tr>";
        echo "<td>{$row['Field']}</td>";
        echo "<td>{$row['Type']}</td>";
        echo "<td>{$row['Null']}</td>";
        echo "<td>{$row['Key']}</td>";
        echo "<td>{$row['Default']}</td>";
        echo "<td>{$row['Extra']}</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<span style='color:red'>✗ user_notifications table NOT FOUND</span><br>";
}
echo "<hr>";

// Test 3: Get a test employee with email
echo "<h2>3. Test Employee Data</h2>";
$test_emp_query = mysqli_query($conDB, "SELECT emp_id, name, email FROM employees WHERE email IS NOT NULL AND email != '' LIMIT 5");
if ($test_emp_query && mysqli_num_rows($test_emp_query) > 0) {
    echo "<h3>Employees with Email Addresses:</h3>";
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Employee ID</th><th>Name</th><th>Email</th></tr>";
    while ($emp = mysqli_fetch_assoc($test_emp_query)) {
        echo "<tr>";
        echo "<td>{$emp['emp_id']}</td>";
        echo "<td>{$emp['name']}</td>";
        echo "<td>{$emp['email']}</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<span style='color:red'>✗ No employees with email addresses found!</span><br>";
}
echo "<hr>";

// Test 4: Check SMTP settings
echo "<h2>4. Email Configuration Check</h2>";
$smtp_check = mysqli_query($conDB, "SELECT * FROM app_settings WHERE setting_name IN ('smtp_host', 'smtp_port', 'smtp_username', 'smtp_secure', 'smtp_from_email', 'smtp_from_name')");
if ($smtp_check && mysqli_num_rows($smtp_check) > 0) {
    echo "<h3>SMTP Settings:</h3>";
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Setting</th><th>Value</th></tr>";
    while ($setting = mysqli_fetch_assoc($smtp_check)) {
        $value = $setting['setting_value'];
        // Hide password
        if ($setting['setting_name'] == 'smtp_password') {
            $value = '********';
        }
        echo "<tr><td>{$setting['setting_name']}</td><td>{$value}</td></tr>";
    }
    echo "</table>";
} else {
    echo "<span style='color:red'>✗ SMTP settings not found in app_settings table!</span><br>";
}
echo "<hr>";

// Test 5: Try to create a test notification
echo "<h2>5. Test Notification Creation</h2>";
// Get first employee with email
$test_emp_result = mysqli_query($conDB, "SELECT emp_id, name, email FROM employees WHERE email IS NOT NULL AND email != '' LIMIT 1");
if ($test_emp_result && mysqli_num_rows($test_emp_result) > 0) {
    $test_emp = mysqli_fetch_assoc($test_emp_result);
    $test_emp_id = $test_emp['emp_id'];
    
    echo "Testing notification for Employee ID: {$test_emp_id} ({$test_emp['name']})<br>";
    
    if (function_exists('create_browser_notification')) {
        $result = create_browser_notification(
            $conDB,
            $test_emp_id,
            "Test Notification",
            "This is a test notification created at " . date('Y-m-d H:i:s'),
            "test_notifications.php"
        );
        
        if ($result) {
            echo "<span style='color:green'>✓ Notification created successfully!</span><br>";
            
            // Verify it was inserted
            $verify = mysqli_query($conDB, "SELECT * FROM user_notifications WHERE emp_id = $test_emp_id ORDER BY id DESC LIMIT 1");
            if ($verify && mysqli_num_rows($verify) > 0) {
                $notif = mysqli_fetch_assoc($verify);
                echo "<h4>Last Notification:</h4>";
                echo "<pre>" . print_r($notif, true) . "</pre>";
            }
        } else {
            echo "<span style='color:red'>✗ Notification creation FAILED!</span><br>";
        }
    } else {
        echo "<span style='color:red'>✗ create_browser_notification function not available</span><br>";
    }
} else {
    echo "<span style='color:red'>✗ No test employee available</span><br>";
}
echo "<hr>";

// Test 6: Check PHP error log location
echo "<h2>6. PHP Error Log Location</h2>";
echo "Error log file: " . ini_get('error_log') . "<br>";
echo "Display errors: " . (ini_get('display_errors') ? 'ON' : 'OFF') . "<br>";
echo "Log errors: " . (ini_get('log_errors') ? 'ON' : 'OFF') . "<br>";
echo "<hr>";

echo "<h2>Instructions:</h2>";
echo "<ol>";
echo "<li>If all functions exist and table exists, try creating a request (vacation/leave/loan)</li>";
echo "<li>Check the PHP error log file mentioned above for detailed error messages</li>";
echo "<li>Check if the test employee received the test notification in the database</li>";
echo "<li>Verify SMTP settings are correct if emails are not being sent</li>";
echo "</ol>";

echo "<p><strong>Note:</strong> After testing, you can check the actual error logs when you submit a request to see detailed debugging information.</p>";
?>
