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

// Create log directory if it doesn't exist
$log_dir = dirname($log_file);
if (!is_dir($log_dir)) {
    mkdir($log_dir, 0755, true);
}

function log_message($message) {
    global $log_file;
    $timestamp = date('Y-m-d H:i:s');
    $full_message = "[$timestamp] $message\n";
    file_put_contents($log_file, $full_message, FILE_APPEND);
    echo $full_message;
}

log_message("========== CRON: Vacation Balance Update Started ==========");

try {
    // Include database connection
    require_once __DIR__ . '/includes/db.php';
    // Live calculation logic moved here (balance_calculator.php now only reads from DB)
    require_once __DIR__ . '/includes/helper_functions.php';
    require_once __DIR__ . '/includes/vacation_calculator.php';
    
    log_message("Database connection established");

    // Get all active employees (status = 1) that have vacation balance records
    $query = "SELECT DISTINCT evb.emp_id, evb.id as balance_record_id, evb.available_balance as old_balance
              FROM emp_vacation_balance evb
              JOIN employees e ON evb.emp_id = e.emp_id
              WHERE e.status = 1
              ORDER BY evb.emp_id";
    
    $result = mysqli_query($conDB, $query);
    
    if (!$result) {
        log_message("ERROR: Query failed - " . mysqli_error($conDB));
        exit(1);
    }
    
    $total_employees = mysqli_num_rows($result);
    log_message("Found $total_employees active employees with balance records to update");
    log_message("(Only employees with status=1 will be processed)");

    $updated_count = 0;
    $changed_count = 0;
    $error_count = 0;

    while ($row = mysqli_fetch_assoc($result)) {
        $emp_id = $row['emp_id'];
        $balance_record_id = $row['balance_record_id'];
        $old_balance = (float)$row['old_balance'];

        try {

            // Calculate live balance for this employee using VacationCalculator
            $live_balance = get_live_vacation_balance($conDB, $emp_id);

            if ($live_balance === null) {
                log_message("  [emp_id: $emp_id] WARNING: Could not calculate balance, skipping");
                $error_count++;
                continue;
            }

            $live_balance = (float)$live_balance;
            $balance_changed = (abs($old_balance - $live_balance) > 0.001);

            // Update the record with new balance
            $update_sql = "UPDATE `emp_vacation_balance` 
                          SET `available_balance` = ? 
                          WHERE `id` = ?";

            $stmt = mysqli_prepare($conDB, $update_sql);
            if (!$stmt) {
                log_message("  [emp_id: $emp_id] ERROR: Prepare failed - " . mysqli_error($conDB));
                $error_count++;
                continue;
            }

            mysqli_stmt_bind_param($stmt, 'di', $live_balance, $balance_record_id);

            if (!mysqli_stmt_execute($stmt)) {
                log_message("  [emp_id: $emp_id] ERROR: Execute failed - " . mysqli_stmt_error($stmt));
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
                    log_message("  [emp_id: $emp_id] ✓ Updated: $old_balance → $live_balance (CHANGED)");
                } else {
                    log_message("  [emp_id: $emp_id] ✓ Refreshed: $live_balance (unchanged value, timestamp updated)");
                }
            }

        } catch (Exception $e) {
            log_message("  [emp_id: $emp_id] ERROR: " . $e->getMessage());
            $error_count++;
        }
    }

    mysqli_free_result($result);

    // Summary
    log_message("");
    log_message("========== SUMMARY ==========");
    log_message("Total employees processed: $total_employees");
    log_message("Records updated: $updated_count");
    log_message("Balances changed: $changed_count");
    log_message("Errors: $error_count");
    log_message("========== CRON: Vacation Balance Update Completed ==========");
    log_message("");

    exit(0);

} catch (Exception $e) {
    log_message("FATAL ERROR: " . $e->getMessage());
    log_message("Stack trace: " . $e->getTraceAsString());
    exit(1);
}
?>
