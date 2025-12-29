<?php
/**
 * CRON EMAIL SETTINGS GUIDE
 * 
 * This file explains what email settings need to be configured for the cron job to send emails.
 * 
 * REQUIRED SETTINGS (in app_settings table):
 * 
 * 1. cron_email_notify_enabled
 *    - Value: 1 (to enable) or 0 (to disable)
 *    - Default: 1 (enabled)
 *    - Description: Enable/disable email notifications from cron jobs
 * 
 * 2. admin_email
 *    - Value: your-email@company.com
 *    - Description: Admin email that will receive the cron reports
 * 
 * 3. from_email
 *    - Value: noreply@company.com
 *    - Description: Sender email address for outgoing emails
 * 
 * 4. from_name
 *    - Value: Al-Mutlak HR System
 *    - Description: Sender name that appears in emails
 * 
 * SMTP SETTINGS (Optional - if you want to use SMTP instead of PHP mail()):
 * 
 * 5. smtp_host
 *    - Value: smtp.gmail.com or your-smtp-server.com
 *    - Default: (empty - will use PHP mail() function)
 * 
 * 6. smtp_port
 *    - Value: 587 (TLS) or 465 (SSL)
 *    - Default: 587
 * 
 * 7. smtp_user
 *    - Value: your-email@gmail.com
 *    - Description: SMTP authentication username
 * 
 * 8. smtp_pass
 *    - Value: your-app-password
 *    - Description: SMTP authentication password
 * 
 * 9. smtp_encryption
 *    - Value: tls or ssl
 *    - Default: tls
 * 
 * ============================================================================
 * SQL SCRIPT TO ADD EMAIL SETTINGS:
 * ============================================================================
 * 
 * -- Enable cron email notifications
 * INSERT INTO app_settings (setting_name, setting_value, created_at)
 * VALUES ('cron_email_notify_enabled', '1', NOW())
 * ON DUPLICATE KEY UPDATE setting_value = '1';
 * 
 * -- Set admin email
 * INSERT INTO app_settings (setting_name, setting_value, created_at)
 * VALUES ('admin_email', 'admin@almutlak.com', NOW())
 * ON DUPLICATE KEY UPDATE setting_value = 'admin@almutlak.com';
 * 
 * -- Set sender email
 * INSERT INTO app_settings (setting_name, setting_value, created_at)
 * VALUES ('from_email', 'noreply@almutlak.com', NOW())
 * ON DUPLICATE KEY UPDATE setting_value = 'noreply@almutlak.com';
 * 
 * -- Set sender name
 * INSERT INTO app_settings (setting_name, setting_value, created_at)
 * VALUES ('from_name', 'Al-Mutlak HR System', NOW())
 * ON DUPLICATE KEY UPDATE setting_value = 'Al-Mutlak HR System';
 * 
 * ============================================================================
 * WHAT THE EMAIL CONTAINS:
 * ============================================================================
 * 
 * 1. SUBJECT: Vacation Balance Update Report - YYYY-MM-DD HH:MM:SS
 * 
 * 2. SUMMARY CARDS:
 *    - Total Employees: Number of employees processed
 *    - Records Updated: Number of records that were updated
 *    - Balances Changed: Number with actual balance changes
 *    - Errors: Number of errors encountered
 * 
 * 3. DETAILED TABLE:
 *    - Employee ID
 *    - Employee Name
 *    - Old Balance (value before update)
 *    - New Balance (value after update)
 *    - Status (CHANGED or REFRESHED)
 * 
 * 4. ACTION BUTTON:
 *    - "View Full Report" link that opens the cron job page
 * 
 * ============================================================================
 * EMAIL SENDING METHODS:
 * ============================================================================
 * 
 * The system will automatically try to use the best available method:
 * 
 * 1. PHPMailer + SMTP (if SMTP settings are configured)
 *    - Most reliable for production environments
 *    - Supports authentication
 *    - Better handling of large emails
 * 
 * 2. PHP mail() function (if SMTP is not configured)
 *    - Uses server's local mail function
 *    - Works on most shared hosting
 *    - No SMTP configuration needed
 * 
 * ============================================================================
 * TROUBLESHOOTING:
 * ============================================================================
 * 
 * EMAIL NOT RECEIVED:
 * 1. Check if admin_email setting is configured
 * 2. Check if from_email setting is configured
 * 3. Verify cron_email_notify_enabled is set to 1
 * 4. Check server error logs for PHPMailer errors
 * 5. Check if SMTP settings are correct (if using SMTP)
 * 6. Verify admin email is not being filtered to spam folder
 * 
 * CHECK ERROR LOG:
 * - Look for "CRON EMAIL:" messages in PHP error_log
 * - Check D:\xampp\htdocs\almutlak\system\cron_logs\vacation_balance_update_YYYY-MM-DD.log
 * 
 * MANUALLY TEST EMAIL:
 * Run: D:\xampp\php\php.exe test_cron_email.php
 * 
 * ============================================================================
 * DISABLING EMAIL NOTIFICATIONS:
 * ============================================================================
 * 
 * If you want to disable email notifications without removing settings:
 * 
 * UPDATE app_settings SET setting_value = '0' WHERE setting_name = 'cron_email_notify_enabled';
 * 
 */
?>
