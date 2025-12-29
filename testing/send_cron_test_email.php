<?php
/**
 * SEND TEST CRON EMAIL
 * This file sends a test email to verify cron email functionality is working
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

date_default_timezone_set('Asia/Riyadh');

echo "=== SENDING TEST CRON EMAIL ===\n\n";

try {
    require_once __DIR__ . '/includes/db.php';
    require_once __DIR__ . '/includes/helper_functions.php';
    require_once __DIR__ . '/cron_update_vacation_balances.php'; // Include the email functions
    
    // Get admin email
    $admin_email = get_setting($conDB, 'admin_email');
    $from_email = get_setting($conDB, 'from_email');
    $from_name = get_setting($conDB, 'from_name', 'Al-Mutlak HR System');
    
    if (!$admin_email) {
        echo "ERROR: Admin email not configured.\n";
        exit(1);
    }
    
    if (!$from_email) {
        echo "ERROR: From email not configured.\n";
        exit(1);
    }
    
    echo "Sending test email to: $admin_email\n";
    echo "From: $from_email\n";
    echo "From Name: $from_name\n\n";
    
    // Create test data
    $updated_count = 25;
    $changed_count = 10;
    $error_count = 0;
    $total_employees = 445;
    $updates_log = [
        [
            'emp_id' => '1061',
            'emp_name' => 'ABU AL FOTOOH A MAJD A FATTAH',
            'old_value' => 32.64,
            'new_value' => 32.85,
            'timestamp' => date('Y-m-d H:i:s'),
            'message' => 'Test update'
        ],
        [
            'emp_id' => '1496',
            'emp_name' => 'BASHIR AHMED GHLAM RASOOL',
            'old_value' => 15.81,
            'new_value' => 15.92,
            'timestamp' => date('Y-m-d H:i:s'),
            'message' => 'Test update'
        ],
        [
            'emp_id' => '5430',
            'emp_name' => 'ANEES AFZAL MUHAMMAD AFZAL',
            'old_value' => 17.89,
            'new_value' => 17.89,
            'timestamp' => date('Y-m-d H:i:s'),
            'message' => 'Test refresh'
        ]
    ];
    
    $protocol = !empty($_SERVER['HTTPS']) ? 'https' : 'http';
    $host = 'localhost';
    $report_url = "{$protocol}://{$host}/almutlak/system/cron_update_vacation_balances.php";
    
    // Get email template
    $subject = "TEST: Vacation Balance Update Report - " . date('Y-m-d H:i:s');
    $html_body = get_cron_email_template($updated_count, $changed_count, $error_count, $total_employees, $updates_log, $report_url, $from_name);
    
    // Send email
    $smtp_host = get_setting($conDB, 'smtp_host');
    
    if ($smtp_host) {
        echo "Sending via PHPMailer + SMTP...\n";
        send_cron_via_phpmailer($admin_email, $subject, $html_body, $conDB);
    } else {
        echo "Sending via PHP mail()...\n";
        send_cron_via_php_mail($admin_email, $subject, $html_body, $from_email, $from_name);
    }
    
    echo "\n✓ TEST EMAIL SENT SUCCESSFULLY!\n";
    echo "Check your email ($admin_email) for the test message.\n";
    echo "If you don't receive it within a few minutes:\n";
    echo "1. Check the spam/junk folder\n";
    echo "2. Review server error logs\n";
    echo "3. Verify SMTP settings if configured\n";
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "\nStack trace:\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}
?>
