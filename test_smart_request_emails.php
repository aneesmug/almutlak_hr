<?php
/**
 * Test Email Configuration for Smart Requests
 * 
 * This script verifies:
 * 1. Employees have emails in admin_login table
 * 2. SMTP settings are configured in app_settings table
 * 3. Helper functions are working correctly
 */

require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/helper_functions.php';

echo "<h1>Smart Request Email Configuration Test</h1>";
echo "<style>
    body { font-family: Arial, sans-serif; margin: 20px; }
    .success { color: green; font-weight: bold; }
    .error { color: red; font-weight: bold; }
    .warning { color: orange; font-weight: bold; }
    table { border-collapse: collapse; width: 100%; margin: 20px 0; }
    th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
    th { background-color: #4CAF50; color: white; }
    tr:nth-child(even) { background-color: #f2f2f2; }
</style>";

// Test 1: Check if helper functions exist
echo "<h2>1. Function Availability Check</h2>";
$functions = [
    'getEmployeeDetailsForApproval',
    'getEmployeeDetails',
    'send_approval_email',
    'create_browser_notification',
    'get_setting'
];

echo "<table>";
echo "<tr><th>Function Name</th><th>Status</th></tr>";
foreach ($functions as $func) {
    $exists = function_exists($func);
    $status = $exists ? '<span class="success">✓ Available</span>' : '<span class="error">✗ NOT FOUND</span>';
    echo "<tr><td>$func()</td><td>$status</td></tr>";
}
echo "</table>";

// Test 2: Check SMTP Settings
echo "<h2>2. SMTP Configuration (from app_settings table)</h2>";
$smtp_settings = [
    'smtp_host',
    'smtp_port',
    'smtp_user',
    'smtp_pass',
    'smtp_encryption',
    'from_email',
    'from_name'
];

echo "<table>";
echo "<tr><th>Setting Name</th><th>Value</th><th>Status</th></tr>";
foreach ($smtp_settings as $setting) {
    $value = get_setting($conDB, $setting);
    if ($setting === 'smtp_pass') {
        $display_value = $value ? str_repeat('*', strlen($value)) : '<span class="error">NOT SET</span>';
    } else {
        $display_value = $value ?: '<span class="error">NOT SET</span>';
    }
    $status = $value ? '<span class="success">✓ Set</span>' : '<span class="error">✗ Missing</span>';
    echo "<tr><td>$setting</td><td>$display_value</td><td>$status</td></tr>";
}
echo "</table>";

// Test 3: Check Employee Emails from admin_login table
echo "<h2>3. Employee Email Addresses (from admin_login table)</h2>";
$email_query = mysqli_query($conDB, "
    SELECT e.emp_id, e.name, al.email, al.user_type, e.status
    FROM employees e
    LEFT JOIN admin_login al ON e.emp_id = al.emp_id
    WHERE e.status = 1
    ORDER BY e.emp_id
    LIMIT 50
");

if ($email_query) {
    $total_active = mysqli_num_rows($email_query);
    $with_email = 0;
    $without_email = 0;
    
    echo "<p><strong>Total Active Employees:</strong> $total_active</p>";
    echo "<table>";
    echo "<tr><th>Emp ID</th><th>Name</th><th>Email (from admin_login)</th><th>User Type</th><th>Status</th></tr>";
    
    while ($row = mysqli_fetch_assoc($email_query)) {
        $email = $row['email'];
        $has_email = !empty($email) && filter_var($email, FILTER_VALIDATE_EMAIL);
        
        if ($has_email) {
            $with_email++;
            $email_display = htmlspecialchars($email);
            $status = '<span class="success">✓ Valid</span>';
        } else {
            $without_email++;
            $email_display = '<span class="error">NO EMAIL</span>';
            $status = '<span class="error">✗ Missing</span>';
        }
        
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['emp_id']) . "</td>";
        echo "<td>" . htmlspecialchars($row['name']) . "</td>";
        echo "<td>$email_display</td>";
        echo "<td>" . htmlspecialchars($row['user_type'] ?? 'Not set') . "</td>";
        echo "<td>$status</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<p><strong>Summary:</strong></p>";
    echo "<ul>";
    echo "<li><span class='success'>With Valid Email: $with_email</span></li>";
    echo "<li><span class='error'>Without Email: $without_email</span></li>";
    echo "</ul>";
    
    if ($without_email > 0) {
        echo "<div style='background: #fff3cd; padding: 15px; border-left: 4px solid #ffc107;'>";
        echo "<strong>⚠ Warning:</strong> $without_email employee(s) do not have email addresses in the admin_login table. ";
        echo "They will NOT receive email notifications. Update using:<br>";
        echo "<code>UPDATE admin_login SET email = 'address@company.com' WHERE emp_id = [ID];</code>";
        echo "</div>";
    }
} else {
    echo "<p class='error'>Error fetching employee emails: " . mysqli_error($conDB) . "</p>";
}

// Test 4: Test getEmployeeDetailsForApproval function
echo "<h2>4. Test getEmployeeDetailsForApproval() Function</h2>";
echo "<p>This function is used to fetch approver details (name + email from admin_login table)</p>";

// Get a random active employee with email
$test_emp_query = mysqli_query($conDB, "
    SELECT e.emp_id 
    FROM employees e
    INNER JOIN admin_login al ON e.emp_id = al.emp_id
    WHERE e.status = 1 AND al.email IS NOT NULL AND al.email != ''
    LIMIT 1
");

if ($test_emp_query && mysqli_num_rows($test_emp_query) > 0) {
    $test_emp = mysqli_fetch_assoc($test_emp_query);
    $test_emp_id = $test_emp['emp_id'];
    
    echo "<p>Testing with emp_id: <strong>$test_emp_id</strong></p>";
    
    $details = getEmployeeDetailsForApproval($conDB, $test_emp_id);
    
    if ($details) {
        echo "<table>";
        echo "<tr><th>Field</th><th>Value</th></tr>";
        echo "<tr><td>Name</td><td>" . htmlspecialchars($details['name']) . "</td></tr>";
        echo "<tr><td>Email</td><td>" . htmlspecialchars($details['email'] ?? 'NULL') . "</td></tr>";
        echo "</table>";
        
        if (!empty($details['email'])) {
            echo "<p class='success'>✓ Function returned employee with email successfully!</p>";
        } else {
            echo "<p class='warning'>⚠ Function returned employee but email is NULL</p>";
        }
    } else {
        echo "<p class='error'>✗ Function returned NULL</p>";
    }
} else {
    echo "<p class='warning'>⚠ No active employees with email found for testing</p>";
}

// Test 5: Check user_notifications table
echo "<h2>5. Browser Notifications Table Check</h2>";
$notif_table_check = mysqli_query($conDB, "SHOW TABLES LIKE 'user_notifications'");
if ($notif_table_check && mysqli_num_rows($notif_table_check) > 0) {
    echo "<p class='success'>✓ user_notifications table exists</p>";
    
    // Check structure
    $structure = mysqli_query($conDB, "DESCRIBE user_notifications");
    if ($structure) {
        echo "<p>Table structure:</p>";
        echo "<table>";
        echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th></tr>";
        while ($field = mysqli_fetch_assoc($structure)) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($field['Field']) . "</td>";
            echo "<td>" . htmlspecialchars($field['Type']) . "</td>";
            echo "<td>" . htmlspecialchars($field['Null']) . "</td>";
            echo "<td>" . htmlspecialchars($field['Key']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    // Check recent notifications
    $recent = mysqli_query($conDB, "SELECT COUNT(*) as count FROM user_notifications");
    if ($recent) {
        $count = mysqli_fetch_assoc($recent)['count'];
        echo "<p>Total notifications in database: <strong>$count</strong></p>";
    }
} else {
    echo "<p class='error'>✗ user_notifications table does NOT exist</p>";
    echo "<p>Browser notifications will not work. Create the table or check the schema.</p>";
}

// Test 6: PHP Error Log Location
echo "<h2>6. PHP Error Log Location</h2>";
$error_log = ini_get('error_log');
echo "<p>PHP errors are logged to: <strong>" . ($error_log ?: 'Default location (check php.ini)') . "</strong></p>";
echo "<p>Common locations:</p>";
echo "<ul>";
echo "<li>Windows (XAMPP): <code>C:\\xampp\\php\\logs\\php_error_log</code></li>";
echo "<li>Windows (XAMPP Apache): <code>C:\\xampp\\apache\\logs\\error.log</code></li>";
echo "</ul>";
echo "<p>After submitting a Smart Request, check this file for lines starting with <code>open_request:</code></p>";

echo "<hr>";
echo "<h2>Summary</h2>";
echo "<p><strong>Your Smart Request email system is configured to:</strong></p>";
echo "<ol>";
echo "<li>Fetch employee emails from the <code>admin_login</code> table ✓</li>";
echo "<li>Use SMTP settings from the <code>app_settings</code> table ✓</li>";
echo "<li>Send emails via <code>send_approval_email()</code> function ✓</li>";
echo "<li>Create browser notifications via <code>create_browser_notification()</code> function ✓</li>";
echo "<li>Log all operations to PHP error log for debugging ✓</li>";
echo "</ol>";

echo "<div style='background: #d1ecf1; padding: 15px; border-left: 4px solid #0c5460; margin-top: 20px;'>";
echo "<strong>Next Steps:</strong><br>";
echo "1. Ensure all employees have email addresses in admin_login table<br>";
echo "2. Verify SMTP credentials are correct<br>";
echo "3. Submit a test Smart Request<br>";
echo "4. Check PHP error log for notification/email results<br>";
echo "5. Check recipient's email inbox (and spam folder)";
echo "</div>";
?>
