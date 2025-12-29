<?php
/**
 * TEST CRON EMAIL FUNCTIONALITY
 * Run this file to test if emails are being sent correctly
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

date_default_timezone_set('Asia/Riyadh');

echo "<pre>";
echo "=== CRON EMAIL FUNCTIONALITY TEST ===\n\n";

try {
    // Include database
    require_once __DIR__ . '/includes/db.php';
    require_once __DIR__ . '/includes/helper_functions.php';
    
    echo "STEP 1: Checking email settings...\n";
    
    $admin_email = get_setting($conDB, 'admin_email');
    $from_email = get_setting($conDB, 'from_email');
    $from_name = get_setting($conDB, 'from_name', 'Al-Mutlak HR System');
    $email_enabled = get_setting($conDB, 'cron_email_notify_enabled', '1');
    
    echo "  Admin Email: " . ($admin_email ?: '❌ NOT SET') . "\n";
    echo "  From Email: " . ($from_email ?: '❌ NOT SET') . "\n";
    echo "  From Name: " . ($from_name ?: '❌ NOT SET') . "\n";
    echo "  Email Enabled: " . ($email_enabled === '0' ? '❌ DISABLED' : '✓ ENABLED') . "\n";
    
    if (!$admin_email) {
        echo "\n⚠️ Admin email not set. Checking admin_login table...\n";
        $admin_query = "SELECT email, user_type FROM admin_login WHERE user_type IN ('administrator', 'superadmin') AND email IS NOT NULL LIMIT 1";
        $result = mysqli_query($conDB, $admin_query);
        if ($result && $row = mysqli_fetch_assoc($result)) {
            echo "  Found: " . $row['email'] . " (Type: " . $row['user_type'] . ")\n";
            echo "  You can set this as admin_email in app_settings\n";
        }
    }
    
    echo "\nSTEP 2: Checking SMTP settings...\n";
    
    $smtp_host = get_setting($conDB, 'smtp_host');
    $smtp_port = get_setting($conDB, 'smtp_port', '587');
    $smtp_user = get_setting($conDB, 'smtp_user');
    $smtp_pass = get_setting($conDB, 'smtp_pass');
    
    if ($smtp_host) {
        echo "  SMTP Host: $smtp_host\n";
        echo "  SMTP Port: $smtp_port\n";
        echo "  SMTP User: " . ($smtp_user ? '✓ SET' : '❌ NOT SET') . "\n";
        echo "  SMTP Pass: " . ($smtp_pass ? '✓ SET' : '❌ NOT SET') . "\n";
        echo "  ✓ Will use PHPMailer + SMTP\n";
    } else {
        echo "  No SMTP configured\n";
        echo "  ✓ Will use PHP mail() function\n";
    }
    
    echo "\nSTEP 3: Testing last cron report...\n";
    
    $report_file = __DIR__ . '/cron_logs/last_vacation_update_report.json';
    if (file_exists($report_file)) {
        $report = json_decode(file_get_contents($report_file), true);
        echo "  ✓ Last report found\n";
        echo "    - Timestamp: " . $report['timestamp'] . "\n";
        echo "    - Total Employees: " . $report['total_employees'] . "\n";
        echo "    - Updated: " . $report['updated_count'] . "\n";
        echo "    - Changed: " . $report['changed_count'] . "\n";
        echo "    - Errors: " . $report['error_count'] . "\n";
    } else {
        echo "  ❌ No cron report found yet\n";
    }
    
    echo "\nSTEP 4: Email test options...\n";
    
    if (!$admin_email || !$from_email) {
        echo "  ❌ Cannot send test email - settings not configured\n";
        echo "\n  REQUIRED SETUP:\n";
        echo "  1. Go to Settings > Email Configuration\n";
        echo "  2. Set:\n";
        echo "     - Admin Email\n";
        echo "     - From Email\n";
        echo "     - From Name\n";
        echo "  3. Optionally configure SMTP for reliable delivery\n";
    } else {
        echo "  ✓ Settings configured\n";
        echo "\n  To send a test email, run:\n";
        echo "  D:\\xampp\\php\\php.exe send_cron_test_email.php\n";
    }
    
    echo "\n=== TEST COMPLETE ===\n";
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}

echo "</pre>";
?>
