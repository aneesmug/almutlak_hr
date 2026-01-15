<?php
/**
 * CRON JOB: Update Employee Vacation Balances
 * 
 * This script should be scheduled to run ONCE PER DAY (recommended: 00:00 or 01:00)
 * It updates the emp_vacation_balance table with current calculated balances for all active employees.
 * 
 * Features:
 * - Automatically calculates and updates vacation balances for active employees (status=1)
 * - Records all changes to emp_vacation_balance_history table for audit trail
 * - Prevents duplicate updates on the same day (use --force to override)
 * - Logs all operations to daily log files for troubleshooting
 * 
 * Crontab Entry Example:
 * 0 1 * * * /usr/bin/php /path/to/almutlak/system/cron_update_vacation_balances.php >> /var/log/almutlak_cron.log 2>&1
 * 
 * Or on Windows Task Scheduler:
 * Task: Run at 01:00 AM daily
 * Action: C:\xampp\php\php.exe D:\xampp\htdocs\almutlak\system\cron_update_vacation_balances.php
 */

error_reporting(E_ALL);
ini_set('display_errors', 1); // temporary: surface errors instead of blank page
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

// If accessed via browser, redirect to the viewer page

// if (php_sapi_name() !== 'cli') {
//     header('Location: /almutlak/system/vacation_balance_report.php');
//     exit(0);
// }

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

// Check for bypass flag:
// CLI: php script.php --force
// Browser: script.php?force=1
$force_run = (isset($argv) && in_array('--force', $argv)) || (isset($_GET['force']) && $_GET['force'] == '1');

try {
    // Include database connection
    require_once __DIR__ . '/includes/db.php';
    
    // Check if already updated today (to prevent JSON overwrite)
    $already_updated_today = false;
    if (!$force_run && file_exists($report_file)) {
        $report_data = json_decode(file_get_contents($report_file), true);
        if ($report_data && isset($report_data['timestamp'])) {
            $report_date = substr($report_data['timestamp'], 0, 10);
            $today = date('Y-m-d');
            if ($report_date === $today) {
                $already_updated_today = true;
                // Set content type for browser display
                if (php_sapi_name() !== 'cli') {
                    header('Content-Type: text/plain; charset=utf-8');
                }
                echo "\n========== ALREADY UPDATED TODAY ==========\n";
                echo "Last update: " . $report_data['timestamp'] . "\n";
                echo "Records Updated: " . ($report_data['updated_count'] ?? 0) . "\n";
                echo "Balances Changed: " . ($report_data['changed_count'] ?? 0) . "\n";
                echo "Errors: " . ($report_data['error_count'] ?? 0) . "\n";
                echo "To force re-run, use: php cron_update_vacation_balances.php --force\n";
                echo "To force re-run, use: /cron_update_vacation_balances.php?force=1\n";
                echo "JSON file preserved from first run.\n";
                echo "===========================================\n\n";
                exit(0);
            }
        }
    }
    
    if ($force_run) {
        log_message("FORCE RUN enabled: Bypassing once-per-day restriction", 'info');
    }
    
    // Make connection available globally
    global $conDB_global;
    $conDB_global = $conDB;
    
    // Live calculation logic moved here (balance_calculator.php now only reads from DB)
    require_once __DIR__ . '/includes/helper_functions.php';
    require_once __DIR__ . '/includes/vacation_calculator.php';
    
    log_message("Database connection established", 'info');

    // Ensure all active employees have a vacation balance record
    $new_records = 0;
    $missing_sql = "SELECT e.emp_id
                    FROM employees e
                    WHERE e.status = 1
                      AND NOT EXISTS (SELECT 1 FROM emp_vacation_balance evb WHERE evb.emp_id = e.emp_id)";
    $missing_result = mysqli_query($conDB, $missing_sql);
    if ($missing_result) {
        while ($emp = mysqli_fetch_assoc($missing_result)) {
            $emp_id_missing = $emp['emp_id'];
            $initial_balance = get_live_vacation_balance($conDB, $emp_id_missing);
            if ($initial_balance === null) {
                $initial_balance = 0; // fallback to zero if calculation fails
            }
            $insert_sql = "INSERT INTO emp_vacation_balance (
                                emp_id, vac_id, contract_id,
                                total_days, used_days, remaining_balance,
                                available_balance, carryover_days,
                                period_start, period_end, last_updated
                            ) VALUES (?, 0, 0, ?, 0, ?, ?, 0, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 1 YEAR), NOW())";
            $insert_stmt = mysqli_prepare($conDB, $insert_sql);
            if ($insert_stmt) {
                mysqli_stmt_bind_param($insert_stmt, 'sddd', $emp_id_missing, $initial_balance, $initial_balance, $initial_balance);
                if (mysqli_stmt_execute($insert_stmt)) {
                    $new_records++;
                    log_message("[emp_id: $emp_id_missing] Created new emp_vacation_balance with starting balance {$initial_balance}", 'info');
                } else {
                    log_message("[emp_id: $emp_id_missing] ERROR creating emp_vacation_balance: " . mysqli_stmt_error($insert_stmt), 'error');
                }
                mysqli_stmt_close($insert_stmt);
            } else {
                log_message("[emp_id: $emp_id_missing] ERROR preparing insert for emp_vacation_balance: " . mysqli_error($conDB), 'error');
            }
        }
        mysqli_free_result($missing_result);
    } else {
        log_message("ERROR: Failed to fetch missing vacation balance records - " . mysqli_error($conDB), 'error');
    }
    if ($new_records > 0) {
        log_message("Created $new_records new emp_vacation_balance record(s) for newly registered employees", 'info');
    }

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
            // Get current record details for history tracking
            $check_sql = "SELECT vac_id, contract_id, total_days, used_days, remaining_balance, 
                                 available_balance, carryover_days, period_start, period_end, last_updated 
                          FROM emp_vacation_balance WHERE id = ? LIMIT 1";
            $check_stmt = mysqli_prepare($conDB, $check_sql);
            if (!$check_stmt) {
                log_message("  [emp_id: $emp_id] ERROR: Prepare failed for record check - " . mysqli_error($conDB), 'error');
                $error_count++;
                continue;
            }
            mysqli_stmt_bind_param($check_stmt, 'i', $balance_record_id);
            if (!mysqli_stmt_execute($check_stmt)) {
                log_message("  [emp_id: $emp_id] ERROR: Execute failed for record check - " . mysqli_stmt_error($check_stmt), 'error');
                mysqli_stmt_close($check_stmt);
                $error_count++;
                continue;
            }
            $result_last = mysqli_stmt_get_result($check_stmt);
            $current_record = mysqli_fetch_assoc($result_last);
            mysqli_stmt_close($check_stmt);

            if (!$current_record) {
                log_message("  [emp_id: $emp_id] ERROR: Could not fetch current balance record", 'error');
                $error_count++;
                continue;
            }

            $last_updated = $current_record['last_updated'];
            $today_str = date('Y-m-d');
            if (!$force_run && $last_updated && substr($last_updated, 0, 10) === $today_str) {
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
            
            // DEBUG: Log calculation details
            if ($emp_id === '1061') {
                error_log("DEBUG emp_1061: old_balance={$old_balance}, live_balance={$live_balance}, diff=" . abs($old_balance - $live_balance) . ", threshold=0.001, changed={$balance_changed}");
            }

            // Update the record with new balance and track when it was last updated
            // CRITICAL FIX: Also update total_days and remaining_balance to keep synchronized with available_balance
            // total_days represents the opening balance, so when available_balance changes,
            // total_days must also be updated to reflect the new opening balance for vacation deductions
            $update_sql = "UPDATE `emp_vacation_balance` 
                          SET `available_balance` = ?, 
                              `total_days` = ?,
                              `remaining_balance` = ?,
                              `last_updated` = NOW() 
                          WHERE `id` = ?";

            $stmt = mysqli_prepare($conDB, $update_sql);
            if (!$stmt) {
                log_message("  [emp_id: $emp_id] ERROR: Prepare failed - " . mysqli_error($conDB), 'error');
                $error_count++;
                continue;
            }

            mysqli_stmt_bind_param($stmt, 'dddi', $live_balance, $live_balance, $live_balance, $balance_record_id);

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
                
                // Insert history record for audit trail
                $change_amount = $live_balance - $old_balance;
                $snapshot_date = date('Y-m-d');
                $snapshot_time = date('Y-m-d H:i:s');
                $calc_status = 'success';
                $notes = $balance_changed ? "Cron auto-update: Balance changed" : "Cron auto-update: Timestamp refreshed";
                
                $history_sql = "INSERT INTO emp_vacation_balance_history 
                               (emp_id, vac_id, contract_id, balance_record_id, 
                                old_available_balance, old_used_days, old_remaining_balance,
                                new_available_balance, new_used_days, new_remaining_balance,
                                carryover_days, total_days, period_start, period_end,
                                balance_changed, change_amount, change_reason, 
                                calculation_status, notes, snapshot_date, snapshot_time)
                               VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                
                $history_stmt = mysqli_prepare($conDB, $history_sql);
                if ($history_stmt) {
                    $change_reason = "CRON_DAILY_UPDATE";
                    $balance_changed_int = $balance_changed ? 1 : 0;
                    
                    mysqli_stmt_bind_param($history_stmt, 'siiiddddddddssidsssss',
                        $emp_id,
                        $current_record['vac_id'],
                        $current_record['contract_id'],
                        $balance_record_id,
                        $old_balance,
                        $current_record['used_days'],
                        $current_record['remaining_balance'],
                        $live_balance,
                        $current_record['used_days'],
                        $current_record['remaining_balance'],
                        $current_record['carryover_days'],
                        $live_balance,
                        $current_record['period_start'],
                        $current_record['period_end'],
                        $balance_changed_int,
                        $change_amount,
                        $change_reason,
                        $calc_status,
                        $notes,
                        $snapshot_date,
                        $snapshot_time
                    );
                    
                    if (!mysqli_stmt_execute($history_stmt)) {
                        log_message("  [emp_id: $emp_id] WARNING: Failed to insert history record - " . mysqli_stmt_error($history_stmt), 'warning');
                    }
                    mysqli_stmt_close($history_stmt);
                } else {
                    log_message("  [emp_id: $emp_id] WARNING: Failed to prepare history insert - " . mysqli_error($conDB), 'warning');
                }
                
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

    // Debug: Log updates_log content to file
    error_log("DEBUG: updates_log count = " . count($updates_log));
    if (count($updates_log) > 0) {
        error_log("DEBUG: First entry = " . json_encode($updates_log[0]));
    }

    // Set content type for browser display
    if (php_sapi_name() !== 'cli') {
        header('Content-Type: text/plain; charset=utf-8');
    }

    // Output results as text
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

    // Finished update run
    exit(0);

} catch (Exception $e) {
    log_message("FATAL ERROR: " . $e->getMessage(), 'error');
    log_message("Stack trace: " . $e->getTraceAsString(), 'error');
    exit(1);
}

// Viewer functions removed from cron; see vacation_balance_report.php for GUI
