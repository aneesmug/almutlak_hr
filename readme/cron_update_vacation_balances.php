<?php
/**
 * CRON JOB: Update Employee Vacation Balances
 * 
 * This script should be scheduled to run ONCE PER DAY (recommended: 00:00 or 01:00)
 * It updates the emp_vacation_balance table with current calculated balances for all active employees.
 * 
 * Crontab Entry Example:
 * 0 1 * * * /usr/bin/php /path/to/almutlak/system/cron_update_vacation_balances.php >> /var/log/almutlak_cron.log 2>&1
 * 
 * Or on Windows Task Scheduler:
 * Task: Run at 01:00 AM daily
 * Action: C:\xampp\php\php.exe D:\xampp\htdocs\almutlak\system\cron_update_vacation_balances.php
 */

error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

// Set timezone
date_default_timezone_set('Asia/Riyadh');

$log_file = __DIR__ . '/cron_logs/vacation_balance_update_' . date('Y-m-d') . '.log';
$report_file = __DIR__ . '/cron_logs/last_vacation_update_report.json';

// Create log directory if it doesn't exist
$log_dir = dirname($log_file);
if (!is_dir($log_dir)) {
    mkdir($log_dir, 0755, true);
}

// CHECK IF ACCESSED VIA BROWSER (NOT CLI) - Load saved report instead of re-running
if (php_sapi_name() !== 'cli') {
    if (file_exists($report_file)) {
        $saved_report = json_decode(file_get_contents($report_file), true);
        if ($saved_report) {
            display_gui_report(
                $saved_report['updated_count'],
                $saved_report['changed_count'],
                $saved_report['error_count'],
                $saved_report['total_employees'],
                $saved_report['updates_log'],
                $saved_report['timestamp']
            );
            exit(0);
        }
    }
    // If no saved report exists, show a message
    show_no_report_available();
    exit(0);
}

$updates_log = []; // Store updates for GUI display
$conDB_global = null; // Make database connection global for access in functions

function get_employee_name($conDB, $emp_id) {
    if (!$conDB || !$emp_id) {
        return "Unknown";
    }
    $stmt = mysqli_prepare($conDB, "SELECT name FROM employees WHERE emp_id = ? LIMIT 1");
    if (!$stmt) {
        return "Unknown";
    }
    mysqli_stmt_bind_param($stmt, "s", $emp_id);
    if (!mysqli_stmt_execute($stmt)) {
        mysqli_stmt_close($stmt);
        return "Unknown";
    }
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
    return $row ? htmlspecialchars($row['name']) : "Unknown";
}

function log_message($message, $type = 'info', $emp_id = null, $old_val = null, $new_val = null) {
    global $log_file, $updates_log, $conDB_global;
    $timestamp = date('Y-m-d H:i:s');
    $full_message = "[$timestamp] $message\n";
    
    // Ensure log directory exists
    $log_dir = dirname($log_file);
    if (!is_dir($log_dir)) {
        mkdir($log_dir, 0755, true);
    }
    // Ensure log file exists (create if missing)
    if (!file_exists($log_file)) {
        touch($log_file);
    }
    file_put_contents($log_file, $full_message, FILE_APPEND);
    
    // Store for GUI display if this is an update record
    if ($type === 'update' && $emp_id !== null && $old_val !== null && $new_val !== null) {
        $emp_name = get_employee_name($conDB_global, $emp_id);
        $updates_log[] = [
            'type' => $type,
            'emp_id' => $emp_id,
            'emp_name' => $emp_name,
            'old_value' => $old_val,
            'new_value' => $new_val,
            'timestamp' => $timestamp,
            'message' => $message
        ];
    }
}

log_message("========== CRON: Vacation Balance Update Started ==========", 'info');

try {
    // Include database connection
    require_once __DIR__ . '/includes/db.php';
    
    // Make connection available globally
    global $conDB_global;
    $conDB_global = $conDB;
    
    // Live calculation logic moved here (balance_calculator.php now only reads from DB)
    require_once __DIR__ . '/includes/helper_functions.php';
    require_once __DIR__ . '/includes/vacation_calculator.php';
    
    log_message("Database connection established", 'info');

    // Get all active employees (status = 1) that have vacation balance records
    $query = "SELECT DISTINCT evb.emp_id, evb.id as balance_record_id, evb.available_balance as old_balance
              FROM emp_vacation_balance evb
              JOIN employees e ON evb.emp_id = e.emp_id
              WHERE e.status = 1
              ORDER BY evb.emp_id";
    
    $result = mysqli_query($conDB, $query);
    
    if (!$result) {
        log_message("ERROR: Query failed - " . mysqli_error($conDB), 'error');
        exit(1);
    }
    
    $total_employees = mysqli_num_rows($result);
    log_message("Found $total_employees active employees with balance records to update", 'info');
    log_message("(Only employees with status=1 will be processed)", 'info');

    $updated_count = 0;
    $changed_count = 0;
    $error_count = 0;

    while ($row = mysqli_fetch_assoc($result)) {
        $emp_id = $row['emp_id'];
        $balance_record_id = $row['balance_record_id'];
        $old_balance = (float)$row['old_balance'];

        try {
            // Check last_updated for this record
            $check_sql = "SELECT last_updated FROM emp_vacation_balance WHERE id = ? LIMIT 1";
            $check_stmt = mysqli_prepare($conDB, $check_sql);
            if (!$check_stmt) {
                log_message("  [emp_id: $emp_id] ERROR: Prepare failed for last_updated check - " . mysqli_error($conDB), 'error');
                $error_count++;
                continue;
            }
            mysqli_stmt_bind_param($check_stmt, 'i', $balance_record_id);
            if (!mysqli_stmt_execute($check_stmt)) {
                log_message("  [emp_id: $emp_id] ERROR: Execute failed for last_updated check - " . mysqli_stmt_error($check_stmt), 'error');
                mysqli_stmt_close($check_stmt);
                $error_count++;
                continue;
            }
            $result_last = mysqli_stmt_get_result($check_stmt);
            $last_row = mysqli_fetch_assoc($result_last);
            mysqli_stmt_close($check_stmt);

            $last_updated = $last_row ? $last_row['last_updated'] : null;
            $today_str = date('Y-m-d');
            if ($last_updated && substr($last_updated, 0, 10) === $today_str) {
                log_message("  [emp_id: $emp_id] SKIPPED: Already updated today ($last_updated)", 'warning');
                continue;
            }

            // Calculate live balance for this employee using VacationCalculator
            $live_balance = get_live_vacation_balance($conDB, $emp_id);

            if ($live_balance === null) {
                log_message("  [emp_id: $emp_id] WARNING: Could not calculate balance, skipping", 'warning');
                $error_count++;
                continue;
            }

            $live_balance = (float)$live_balance;
            $balance_changed = (abs($old_balance - $live_balance) > 0.001);

            // Update the record with new balance and track when it was last updated
            // CRITICAL FIX: Also update total_days to keep it synchronized with available_balance
            // total_days represents the opening balance, so when available_balance changes,
            // total_days must also be updated to reflect the new opening balance for vacation deductions
            $update_sql = "UPDATE `emp_vacation_balance` 
                          SET `available_balance` = ?, 
                              `total_days` = ?,
                              `last_updated` = NOW() 
                          WHERE `id` = ?";

            $stmt = mysqli_prepare($conDB, $update_sql);
            if (!$stmt) {
                log_message("  [emp_id: $emp_id] ERROR: Prepare failed - " . mysqli_error($conDB), 'error');
                $error_count++;
                continue;
            }

            mysqli_stmt_bind_param($stmt, 'ddi', $live_balance, $live_balance, $balance_record_id);

            if (!mysqli_stmt_execute($stmt)) {
                log_message("  [emp_id: $emp_id] ERROR: Execute failed - " . mysqli_stmt_error($stmt), 'error');
                mysqli_stmt_close($stmt);
                $error_count++;
                continue;
            }

            $affected = mysqli_stmt_affected_rows($stmt);
            mysqli_stmt_close($stmt);

            if ($affected > 0) {
                $updated_count++;
                if ($balance_changed) {
                    $changed_count++;
                    $change_msg = "Updated: $old_balance → $live_balance";
                    log_message("  [emp_id: $emp_id] ✓ $change_msg (CHANGED)", 'update', $emp_id, $old_balance, $live_balance);
                } else {
                    $refresh_msg = "Refreshed: $live_balance (unchanged value, timestamp updated)";
                    log_message("  [emp_id: $emp_id] ✓ $refresh_msg", 'update', $emp_id, $old_balance, $live_balance);
                }
            }

        } catch (Exception $e) {
            log_message("  [emp_id: $emp_id] ERROR: " . $e->getMessage(), 'error');
            $error_count++;
        }
    }

    mysqli_free_result($result);

    // Save updates log to persistent JSON file for later viewing
    $report_file = __DIR__ . '/cron_logs/last_vacation_update_report.json';
    $report_data = [
        'timestamp' => date('Y-m-d H:i:s'),
        'total_employees' => $total_employees,
        'updated_count' => $updated_count,
        'changed_count' => $changed_count,
        'error_count' => $error_count,
        'updates_log' => $updates_log
    ];
    file_put_contents($report_file, json_encode($report_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    log_message("Report saved to: $report_file", 'info');

    // Send email notification with cron results
    send_cron_email_notification($conDB, $updated_count, $changed_count, $error_count, $total_employees, $updates_log);

    // Debug: Log updates_log content to file
    error_log("DEBUG: updates_log count = " . count($updates_log));
    if (count($updates_log) > 0) {
        error_log("DEBUG: First entry = " . json_encode($updates_log[0]));
    }

    // Always output a text list to console when run from CLI
    if (php_sapi_name() === 'cli') {
        echo "\n========== VACATION BALANCE UPDATE RESULTS ==========\n";
        echo "Total Employees: " . $total_employees . "\n";
        echo "Records Updated: " . $updated_count . "\n";
        echo "Balances Changed: " . $changed_count . "\n";
        echo "Errors: " . $error_count . "\n";
        echo "\n--- UPDATE LIST ---\n";
        if (count($updates_log) > 0) {
            foreach ($updates_log as $log) {
                $is_changed = abs($log['old_value'] - $log['new_value']) > 0.001;
                $status = $is_changed ? 'CHANGED' : 'REFRESHED';
                printf("[%s] %s (%s) - Old: %.2f → New: %.2f (%s)\n", 
                    $log['timestamp'],
                    $log['emp_id'],
                    $log['emp_name'],
                    $log['old_value'],
                    $log['new_value'],
                    $status
                );
            }
        } else {
            echo "No updates recorded.\n";
        }
        echo "======================================================\n\n";
    }

    // Display HTML GUI with results
    display_gui_report($updated_count, $changed_count, $error_count, $total_employees, $updates_log, date('Y-m-d H:i:s'));

    exit(0);

} catch (Exception $e) {
    log_message("FATAL ERROR: " . $e->getMessage(), 'error');
    log_message("Stack trace: " . $e->getTraceAsString(), 'error');
    exit(1);
}

/**
 * Send email notification with cron results
 */
function send_cron_email_notification($conDB, $updated_count, $changed_count, $error_count, $total_employees, $updates_log) {
    global $log_file;
    
    try {
        // Check if email notifications are enabled
        $email_enabled = get_setting($conDB, 'cron_email_notify_enabled', '1');
        if (!$email_enabled || $email_enabled === '0') {
            return; // Email notifications disabled
        }

        // Get admin email from settings
        $admin_email = get_setting($conDB, 'admin_email');
        $from_email = get_setting($conDB, 'from_email');
        $from_name = get_setting($conDB, 'from_name', 'Al-Mutlak HR System');
        
        // If no admin email configured, try to get from system admin_login
        if (!$admin_email) {
            $admin_query = "SELECT email FROM admin_login WHERE (user_type = 'administrator' OR user_type = 'superadmin') AND email IS NOT NULL LIMIT 1";
            $admin_result = mysqli_query($conDB, $admin_query);
            if ($admin_result && $admin_row = mysqli_fetch_assoc($admin_result)) {
                $admin_email = $admin_row['email'];
            }
        }

        // Don't send if no admin email found
        if (!$admin_email) {
            error_log("CRON EMAIL: No admin email configured");
            return;
        }

        // Get base URL for the report link
        $protocol = !empty($_SERVER['HTTPS']) ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $report_url = "{$protocol}://{$host}/almutlak/system/cron_update_vacation_balances.php";

        // Build email subject
        $subject = "Vacation Balance Update Report - " . date('Y-m-d H:i:s');

        // Build email body
        $html_body = get_cron_email_template($updated_count, $changed_count, $error_count, $total_employees, $updates_log, $report_url, $from_name);

        // Send email using PHPMailer if available, otherwise use PHP mail()
        if (function_exists('send_approval_email')) {
            // Use the existing send_approval_email function from helper_functions
            $template_data = [
                'updated_count' => $updated_count,
                'changed_count' => $changed_count,
                'error_count' => $error_count,
                'total_employees' => $total_employees,
                'updates_log' => $updates_log,
                'report_url' => $report_url,
                'timestamp' => date('Y-m-d H:i:s')
            ];
            
            // Try using the existing email function (may need adjustment)
            try {
                $smtp_host = get_setting($conDB, 'smtp_host');
                if ($smtp_host) {
                    send_cron_via_phpmailer($admin_email, $subject, $html_body, $conDB);
                } else {
                    // Fallback to PHP mail()
                    send_cron_via_php_mail($admin_email, $subject, $html_body, $from_email, $from_name);
                }
            } catch (Exception $e) {
                error_log("CRON EMAIL ERROR: " . $e->getMessage());
            }
        } else {
            // Fallback to PHP mail()
            send_cron_via_php_mail($admin_email, $subject, $html_body, $from_email, $from_name);
        }

        error_log("CRON EMAIL: Notification sent to $admin_email");

    } catch (Exception $e) {
        error_log("CRON EMAIL EXCEPTION: " . $e->getMessage());
    }
}

/**
 * Send cron email using PHPMailer
 */
function send_cron_via_phpmailer($to_email, $subject, $html_body, $conDB) {
    try {
        $smtp_host = get_setting($conDB, 'smtp_host');
        $smtp_port = (int)get_setting($conDB, 'smtp_port', 587);
        $smtp_user = get_setting($conDB, 'smtp_user');
        $smtp_pass = get_setting($conDB, 'smtp_pass');
        $smtp_from_email = get_setting($conDB, 'from_email');
        $smtp_from_name = get_setting($conDB, 'from_name', 'Al-Mutlak HR System');
        $smtp_secure = get_setting($conDB, 'smtp_encryption', 'tls');

        // Create a new PHPMailer instance
        $mail = new PHPMailer\PHPMailer\PHPMailer();
        $mail->isSMTP();
        $mail->Host = $smtp_host;
        $mail->SMTPAuth = true;
        $mail->Username = $smtp_user;
        $mail->Password = $smtp_pass;
        $mail->SMTPSecure = $smtp_secure;
        $mail->Port = $smtp_port;

        $mail->setFrom($smtp_from_email, $smtp_from_name);
        $mail->addAddress($to_email);
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $html_body;
        $mail->AltBody = strip_tags($html_body);

        return $mail->send();

    } catch (Exception $e) {
        throw new Exception("PHPMailer Error: " . $e->getMessage());
    }
}

/**
 * Send cron email using PHP mail()
 */
function send_cron_via_php_mail($to_email, $subject, $html_body, $from_email = null, $from_name = null) {
    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type: text/html; charset=UTF-8" . "\r\n";
    
    if ($from_email) {
        $headers .= "From: " . ($from_name ? $from_name . " <" . $from_email . ">" : $from_email) . "\r\n";
    }
    
    return mail($to_email, $subject, $html_body, $headers);
}

/**
 * Generate HTML email template for cron report
 */
function get_cron_email_template($updated_count, $changed_count, $error_count, $total_employees, $updates_log, $report_url, $from_name) {
    $timestamp = date('Y-m-d H:i:s');
    $update_rows = '';
    
    if (count($updates_log) > 0) {
        foreach ($updates_log as $log) {
            $is_changed = abs($log['old_value'] - $log['new_value']) > 0.001;
            $status = $is_changed ? '<span style="color: #ff6b6b;">CHANGED</span>' : '<span style="color: #51cf66;">REFRESHED</span>';
            $update_rows .= "
            <tr>
                <td style='padding: 10px; border-bottom: 1px solid #eee;'>{$log['emp_id']}</td>
                <td style='padding: 10px; border-bottom: 1px solid #eee;'>{$log['emp_name']}</td>
                <td style='padding: 10px; border-bottom: 1px solid #eee;'>{$log['old_value']}</td>
                <td style='padding: 10px; border-bottom: 1px solid #eee;'>{$log['new_value']}</td>
                <td style='padding: 10px; border-bottom: 1px solid #eee; text-align: center;'>$status</td>
            </tr>";
        }
    } else {
        $update_rows = '<tr><td colspan="5" style="padding: 20px; text-align: center; color: #999;">No updates recorded</td></tr>';
    }

    $html = "
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset='UTF-8'>
        <style>
            body { font-family: 'Segoe UI', Arial, sans-serif; line-height: 1.6; color: #333; }
            .container { max-width: 900px; margin: 0 auto; padding: 20px; }
            .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; border-radius: 5px 5px 0 0; }
            .header h1 { margin: 0; font-size: 24px; }
            .header p { margin: 5px 0 0 0; opacity: 0.9; }
            .summary { background: #f5f5f5; padding: 20px; border-collapse: collapse; width: 100%; margin: 0; }
            .summary-card { display: inline-block; width: 23%; margin: 1%; padding: 15px; background: white; border-radius: 5px; text-align: center; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
            .summary-card .number { font-size: 28px; font-weight: bold; color: #667eea; }
            .summary-card .label { font-size: 12px; color: #999; margin-top: 5px; }
            .details { padding: 20px; background: white; }
            .details h2 { margin-top: 0; color: #333; border-bottom: 2px solid #667eea; padding-bottom: 10px; }
            table { width: 100%; border-collapse: collapse; margin: 20px 0; }
            table th { background: #f5f5f5; padding: 12px; text-align: left; font-weight: 600; border-bottom: 2px solid #ddd; }
            table td { padding: 10px; border-bottom: 1px solid #eee; }
            .link-button { display: inline-block; background: #667eea; color: white; padding: 12px 30px; text-decoration: none; border-radius: 5px; margin: 20px 0; }
            .link-button:hover { background: #764ba2; }
            .footer { background: #f5f5f5; padding: 15px 20px; border-radius: 0 0 5px 5px; text-align: center; color: #999; font-size: 12px; }
        </style>
    </head>
    <body>
        <div class='container'>
            <!-- Header -->
            <div class='header'>
                <h1>✓ Vacation Balance Update Report</h1>
                <p>Automated Cron Job Execution - $timestamp</p>
            </div>

            <!-- Summary Cards -->
            <div style='background: white; padding: 20px;'>
                <div style='display: inline-block; width: 23%; margin: 1%; padding: 15px; background: #f5f5f5; border-radius: 5px; text-align: center;'>
                    <div style='font-size: 28px; font-weight: bold; color: #667eea;'>$total_employees</div>
                    <div style='font-size: 12px; color: #999; margin-top: 5px;'>Total Employees</div>
                </div>
                <div style='display: inline-block; width: 23%; margin: 1%; padding: 15px; background: #f5f5f5; border-radius: 5px; text-align: center;'>
                    <div style='font-size: 28px; font-weight: bold; color: #51cf66;'>$updated_count</div>
                    <div style='font-size: 12px; color: #999; margin-top: 5px;'>Records Updated</div>
                </div>
                <div style='display: inline-block; width: 23%; margin: 1%; padding: 15px; background: #f5f5f5; border-radius: 5px; text-align: center;'>
                    <div style='font-size: 28px; font-weight: bold; color: #ffa94d;'>$changed_count</div>
                    <div style='font-size: 12px; color: #999; margin-top: 5px;'>Balances Changed</div>
                </div>
                <div style='display: inline-block; width: 23%; margin: 1%; padding: 15px; background: #f5f5f5; border-radius: 5px; text-align: center;'>
                    <div style='font-size: 28px; font-weight: bold; color: #ff6b6b;'>$error_count</div>
                    <div style='font-size: 12px; color: #999; margin-top: 5px;'>Errors</div>
                </div>
            </div>

            <!-- Details -->
            <div class='details'>
                <h2>Update Details</h2>
                <table>
                    <thead>
                        <tr>
                            <th>Employee ID</th>
                            <th>Employee Name</th>
                            <th>Old Balance</th>
                            <th>New Balance</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        $update_rows
                    </tbody>
                </table>

                <div style='text-align: center;'>
                    <a href='$report_url' class='link-button'>View Full Report</a>
                </div>
            </div>

            <!-- Footer -->
            <div class='footer'>
                <p>This is an automated report from the Al-Mutlak HR System. Please do not reply to this email.</p>
                <p>Report Time: $timestamp | System: $from_name</p>
            </div>
        </div>
    </body>
    </html>";

    return $html;
}


/**
 * Display message when no saved report is available
 */
function show_no_report_available() {
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Vacation Balance Update - No Report</title>
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
        <style>
            body {
                font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                min-height: 100vh;
                display: flex;
                align-items: center;
                justify-content: center;
                padding: 20px;
            }
            .container {
                background: white;
                border-radius: 10px;
                box-shadow: 0 10px 40px rgba(0,0,0,0.2);
                padding: 60px 40px;
                text-align: center;
                max-width: 500px;
            }
            .icon {
                font-size: 64px;
                color: #ffc107;
                margin-bottom: 20px;
            }
            h1 {
                color: #333;
                margin-bottom: 10px;
                font-size: 24px;
            }
            p {
                color: #666;
                line-height: 1.6;
                margin-bottom: 20px;
            }
            .button {
                display: inline-block;
                background: #667eea;
                color: white;
                padding: 12px 30px;
                border-radius: 5px;
                text-decoration: none;
                margin-top: 20px;
            }
            .button:hover {
                background: #764ba2;
            }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="icon">
                <i class="fas fa-inbox"></i>
            </div>
            <h1>No Report Available</h1>
            <p>The vacation balance update cron job has not been run yet, or no saved report exists.</p>
            <p>Please wait for the scheduled cron job to run, or run it manually from the command line.</p>
            <p style="font-size: 12px; color: #999; margin-top: 30px;">Cron Report: <?php echo date('Y-m-d H:i:s'); ?></p>
        </div>
    </body>
    </html>
    <?php
}

/**
 * Display HTML GUI Report with Icons and Formatted Data
 */
function display_gui_report($updated_count, $changed_count, $error_count, $total_employees, $updates_log, $report_timestamp = null) {
    if ($report_timestamp === null) {
        $report_timestamp = date('Y-m-d H:i:s');
    }
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Vacation Balance Update - Cron Report</title>
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
        <style>
            * {
                margin: 0;
                padding: 0;
                box-sizing: border-box;
            }
            body {
                font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                min-height: 100vh;
                padding: 20px;
            }
            .container {
                max-width: 1200px;
                margin: 0 auto;
            }
            .header {
                background: white;
                padding: 30px;
                border-radius: 10px 10px 0 0;
                box-shadow: 0 2px 10px rgba(0,0,0,0.1);
                display: flex;
                align-items: center;
                gap: 20px;
            }
            .header h1 {
                color: #333;
                font-size: 28px;
            }
            .header .icon {
                font-size: 40px;
                color: #667eea;
            }
            .summary {
                background: white;
                padding: 30px;
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
                gap: 20px;
                box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            }
            .summary-card {
                padding: 20px;
                border-radius: 8px;
                text-align: center;
                display: flex;
                flex-direction: column;
                align-items: center;
                gap: 10px;
            }
            .summary-card.total {
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                color: white;
            }
            .summary-card.updated {
                background: linear-gradient(135deg, #84fab0 0%, #8fd3f4 100%);
                color: white;
            }
            .summary-card.changed {
                background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
                color: white;
            }
            .summary-card.errors {
                background: linear-gradient(135deg, #ff6b6b 0%, #ff8787 100%);
                color: white;
            }
            .summary-card .icon {
                font-size: 32px;
            }
            .summary-card .number {
                font-size: 32px;
                font-weight: bold;
            }
            .summary-card .label {
                font-size: 14px;
                opacity: 0.9;
            }
            .details-section {
                background: white;
                padding: 30px;
                margin-top: 20px;
                border-radius: 8px;
                box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            }
            .details-section h2 {
                color: #333;
                margin-bottom: 20px;
                font-size: 20px;
                display: flex;
                align-items: center;
                gap: 10px;
            }
            .table-wrapper {
                overflow-x: auto;
            }
            table {
                width: 100%;
                border-collapse: collapse;
            }
            table thead {
                background: #f5f5f5;
            }
            table th {
                padding: 12px;
                text-align: left;
                font-weight: 600;
                color: #333;
                border-bottom: 2px solid #ddd;
            }
            table td {
                padding: 12px;
                border-bottom: 1px solid #eee;
            }
            table tbody tr:hover {
                background: #f9f9f9;
            }
            .status-badge {
                display: inline-flex;
                align-items: center;
                gap: 6px;
                padding: 6px 12px;
                border-radius: 20px;
                font-size: 12px;
                font-weight: 600;
            }
            .status-badge.success {
                background: #d4edda;
                color: #155724;
            }
            .status-badge.changed {
                background: #fff3cd;
                color: #856404;
            }
            .status-badge.error {
                background: #f8d7da;
                color: #721c24;
            }
            .emp-id {
                font-weight: 600;
                color: #667eea;
            }
            .emp-name {
                font-weight: 500;
                color: #333;
                display: block;
                max-width: 200px;
                word-break: break-word;
            }
            .value-container {
                display: flex;
                align-items: center;
                gap: 10px;
                justify-content: center;
            }
            .old-value {
                padding: 6px 12px;
                background: #f0f0f0;
                border-radius: 4px;
                font-family: 'Courier New', monospace;
            }
            .arrow {
                color: #999;
                font-size: 16px;
            }
            .new-value {
                padding: 6px 12px;
                background: #e8f5e9;
                border-radius: 4px;
                font-family: 'Courier New', monospace;
                font-weight: 600;
                color: #2e7d32;
            }
            .timestamp {
                color: #999;
                font-size: 12px;
            }
            .empty-state {
                text-align: center;
                padding: 40px;
                color: #999;
            }
            .empty-state i {
                font-size: 48px;
                margin-bottom: 20px;
                opacity: 0.5;
            }
            .footer {
                background: white;
                padding: 20px 30px;
                border-radius: 0 0 10px 10px;
                box-shadow: 0 2px 10px rgba(0,0,0,0.1);
                text-align: center;
                color: #666;
                font-size: 12px;
            }
        </style>
    </head>
    <body>
        <div class="container">
            <!-- Header -->
            <div class="header">
                <div class="icon">
                    <i class="fas fa-calendar-check"></i>
                </div>
                <div>
                    <h1>Vacation Balance Update Report</h1>
                    <p style="color: #999; margin-top: 5px;">Cron Job Execution Report</p>
                </div>
            </div>

            <!-- Summary Cards -->
            <div class="summary">
                <div class="summary-card total">
                    <div class="icon"><i class="fas fa-users"></i></div>
                    <div class="number"><?php echo $total_employees; ?></div>
                    <div class="label">Total Employees</div>
                </div>
                <div class="summary-card updated">
                    <div class="icon"><i class="fas fa-check-circle"></i></div>
                    <div class="number"><?php echo $updated_count; ?></div>
                    <div class="label">Records Updated</div>
                </div>
                <div class="summary-card changed">
                    <div class="icon"><i class="fas fa-exchange-alt"></i></div>
                    <div class="number"><?php echo $changed_count; ?></div>
                    <div class="label">Balances Changed</div>
                </div>
                <div class="summary-card errors">
                    <div class="icon"><i class="fas fa-exclamation-circle"></i></div>
                    <div class="number"><?php echo $error_count; ?></div>
                    <div class="label">Errors</div>
                </div>
            </div>

            <!-- Details Table -->
            <div class="details-section">
                <h2>
                    <i class="fas fa-table"></i>
                    Update Details (<?php echo count($updates_log); ?> records)
                </h2>
                <?php if (count($updates_log) > 0): ?>
                    <div class="table-wrapper">
                        <table>
                            <thead>
                                <tr>
                                    <th style="width: 12%;">Employee ID</th>
                                    <th style="width: 20%;">Employee Name</th>
                                    <th style="width: 18%;">Old Balance</th>
                                    <th style="width: 18%;">New Balance</th>
                                    <th style="width: 16%;">Status</th>
                                    <th style="width: 16%;">Timestamp</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($updates_log as $log): ?>
                                    <?php 
                                        $is_changed = abs($log['old_value'] - $log['new_value']) > 0.001;
                                        $status_class = $is_changed ? 'changed' : 'success';
                                        $status_text = $is_changed ? 'Changed' : 'Refreshed';
                                        $status_icon = $is_changed ? 'fas fa-exchange-alt' : 'fas fa-sync-alt';
                                    ?>
                                    <tr>
                                        <td>
                                            <span class="emp-id"><?php echo htmlspecialchars($log['emp_id']); ?></span>
                                        </td>
                                        <td>
                                            <span class="emp-name"><?php echo htmlspecialchars($log['emp_name']); ?></span>
                                        </td>
                                        <td>
                                            <div class="value-container">
                                                <span class="old-value"><?php echo number_format((float)$log['old_value'], 2); ?></span>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="value-container">
                                                <span class="arrow"><i class="fas fa-arrow-right"></i></span>
                                                <span class="new-value"><?php echo number_format((float)$log['new_value'], 2); ?></span>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="status-badge <?php echo $status_class; ?>">
                                                <i class="<?php echo $status_icon; ?>"></i>
                                                <?php echo $status_text; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="timestamp"><?php echo htmlspecialchars($log['timestamp']); ?></span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-inbox"></i>
                        <p>No updates to display</p>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Footer -->
            <div class="footer">
                <p>Report Generated: <?php echo htmlspecialchars($report_timestamp); ?> | Cron Job Execution Report</p>
            </div>
        </div>
    </body>
    </html>
    <?php
}
