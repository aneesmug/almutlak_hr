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
    
    // Store for GUI display if this is an update or refresh record
    if (($type === 'update' || $type === 'refresh') && $emp_id !== null && $old_val !== null && $new_val !== null) {
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

// Check for force flag:
// CLI: php script.php --force=1  or --force=2
// Browser: script.php?force=1 or ?force=2
// 
// force=0: Normal - runs once per day, blocks duplicates
// force=1: Check missing records only, refresh values, DON'T update last_updated if ran today
// force=2: Force full update including last_updated, skip ALL checks
$force_level = 0;

// First check CLI arguments
if (isset($argv) && is_array($argv)) {
    foreach ($argv as $arg) {
        if (is_string($arg) && strpos($arg, '--force=') === 0) {
            $force_level = (int)substr($arg, 8);
            break;
        } elseif (is_string($arg) && $arg === '--force') {
            $force_level = 1; // Default to level 1 for backward compatibility
            break;
        }
    }
}

// Then check GET parameters (browser)
if ($force_level === 0 && isset($_GET['force'])) {
    $force_level = (int)$_GET['force'];
}

// Ensure force_level is valid (0, 1, or 2)
if (!in_array($force_level, [0, 1, 2])) {
    $force_level = 0;
}

log_message("Force level: $force_level (0=normal, 1=check missing, 2=full bypass)", 'info');
log_message("Source: " . (isset($_GET['force']) ? "Browser (?force={$_GET['force']})" : (isset($argv) ? "CLI (argv)" : "Default")), 'info');

try {
    // Include database connection
    require_once __DIR__ . '/includes/db.php';
    
    // Check if already updated today (UNLESS force_level=2)
    $already_updated_today = false;
    $should_update_last_updated = true;
    
    if ($force_level !== 2 && file_exists($report_file)) {
        $report_data = json_decode(file_get_contents($report_file), true);
        if ($report_data && isset($report_data['timestamp'])) {
            $report_date = substr($report_data['timestamp'], 0, 10);
            $today = date('Y-m-d');
            if ($report_date === $today) {
                $already_updated_today = true;
                
                if ($force_level === 0) {
                    // Normal run: skip if already updated today
                    if (php_sapi_name() !== 'cli') {
                        header('Content-Type: text/plain; charset=utf-8');
                    }
                    echo "\n========== ALREADY UPDATED TODAY ==========\n";
                    echo "Last update: " . $report_data['timestamp'] . "\n";
                    echo "Records Updated: " . ($report_data['updated_count'] ?? 0) . "\n";
                    echo "Balances Changed: " . ($report_data['changed_count'] ?? 0) . "\n";
                    echo "Errors: " . ($report_data['error_count'] ?? 0) . "\n";
                    echo "\nTo check for missing records and refresh values (without updating last_updated):\n";
                    echo "  CLI: php cron_update_vacation_balances.php --force=1\n";
                    echo "  Browser: /cron_update_vacation_balances.php?force=1\n";
                    echo "\nTo force full update with new last_updated timestamp:\n";
                    echo "  CLI: php cron_update_vacation_balances.php --force=2\n";
                    echo "  Browser: /cron_update_vacation_balances.php?force=2\n";
                    echo "===========================================\n\n";
                    exit(0);
                } elseif ($force_level === 1) {
                    // Force level 1: Refresh values but DON'T update last_updated
                    log_message("FORCE LEVEL 1: Checking for missing records and refreshing values (NOT updating last_updated)", 'info');
                    $should_update_last_updated = false;
                }
            }
        }
    }
    
    if ($force_level === 2) {
        log_message("FORCE LEVEL 2: Full bypass - updating all balances AND last_updated", 'info');
        $should_update_last_updated = true;
    } elseif ($force_level === 1) {
        log_message("FORCE LEVEL 1: Checking for missing records only", 'info');
        $should_update_last_updated = false;
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
                                available_balance, opening_balance, carryover_days,
                                period_start, period_end, last_updated
                            ) VALUES (?, 0, 0, ?, 0, ?, ?, ?, 0, CURDATE(), DATE_ADD(CURDATE(), INTERVAL 1 YEAR), NOW())";
            $insert_stmt = mysqli_prepare($conDB, $insert_sql);
            if ($insert_stmt) {
                mysqli_stmt_bind_param($insert_stmt, 'sdddd', $emp_id_missing, $initial_balance, $initial_balance, $initial_balance, $initial_balance);
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

    // Process all existing employees - but behavior depends on force_level:
    // Mode 0: Update with yesterday's timestamp, prevents daily duplicates
    // Mode 1: Show refreshed values only, don't update database or last_updated
    // Mode 2: Force update with 2-days-ago timestamp, always updates everything
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
            
            // For mode 1 (check missing), only show refreshed values - don't update database
            if ($force_level === 1) {
                log_message("  [emp_id: $emp_id] REFRESHED: $old_balance → $old_balance (not updated)", 'refresh', $emp_id, $old_balance, $old_balance);
                continue;
            }
            
            // For mode 0: Skip if already updated today (prevent duplicates)
            if ($force_level === 0 && $last_updated && substr($last_updated, 0, 10) === $today_str) {
                log_message("  [emp_id: $emp_id] ⏭️ SKIPPED: Already updated today ($last_updated)", 'warning');
                continue;
            }
            
            // Mode 0 (normal) and Mode 2 (force) both continue to update

            // Calculate live balance for this employee using VacationCalculator
            $live_balance = get_live_vacation_balance($conDB, $emp_id);

            if ($live_balance === null) {
                log_message("  [emp_id: $emp_id] WARNING: Could not calculate balance, skipping", 'warning');
                $error_count++;
                continue;
            }

            $live_balance = (float)$live_balance;
            $balance_changed = (abs($old_balance - $live_balance) > 0.001);
            
            // Determine last_updated timestamp based on force_level
            $now = new DateTime();
            $now_str = $now->format('Y-m-d H:i:s');
            
            if ($force_level === 2) {
                // Mode 2: Set to YESTERDAY at midnight so next calculation includes 1 day of accrual
                $yesterday = new DateTime('yesterday');
                $new_last_updated = $yesterday->format('Y-m-d 00:00:00');
            } else {
                // Mode 0: Set to NOW() to prevent duplicate same-day runs
                $new_last_updated = $now_str;
            }
            
            // Update the database (mode 1 already exited above)
            $update_sql = "UPDATE `emp_vacation_balance` 
                          SET `available_balance` = ?, 
                              `opening_balance` = ?,
                              `remaining_balance` = ?,
                              `last_updated` = ? 
                          WHERE `id` = ?";

            $stmt = mysqli_prepare($conDB, $update_sql);
            if (!$stmt) {
                log_message("  [emp_id: $emp_id] ERROR: Prepare failed - " . mysqli_error($conDB), 'error');
                $error_count++;
                continue;
            }
            
            mysqli_stmt_bind_param($stmt, 'dddsi', $live_balance, $live_balance, $live_balance, $new_last_updated, $balance_record_id);

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
                
                // Always count as changed since we update daily for synchronization
                $changed_count++;
                
                // Insert history record for audit trail
                $change_amount = $live_balance - $old_balance;
                $snapshot_date = date('Y-m-d');
                $snapshot_time = date('Y-m-d H:i:s');
                $calc_status = 'success';
                $notes = "Daily cron auto-update for balance synchronization";
                
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
                        $current_record['total_days'],
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
                
                // Always show as updated for daily sync
                if ($balance_changed) {
                    $change_msg = "Updated: $old_balance → $live_balance (VALUE CHANGED)";
                } else {
                    $change_msg = "Updated: $live_balance (synced daily for consistency)";
                }
                log_message("  [emp_id: $emp_id] ✓ $change_msg", 'update', $emp_id, $old_balance, $live_balance);
            }

        } catch (Exception $e) {
            log_message("  [emp_id: $emp_id] ERROR: " . $e->getMessage(), 'error');
            $error_count++;
        }
    }

    mysqli_free_result($result);

    // Save updates log to persistent JSON file for later viewing
    $report_file = __DIR__ . '/cron_logs/last_vacation_update_report.json';
    $force_mode_name = ['normal', 'check_missing', 'full_bypass'][$force_level] ?? 'unknown';
    $report_data = [
        'timestamp' => date('Y-m-d H:i:s'),
        'force_level' => $force_level,
        'force_mode' => $force_mode_name,
        'updated_last_updated' => $should_update_last_updated ? 'YES' : 'NO',
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
    echo "Mode: " . $force_mode_name . " (force_level=$force_level)\n";
    echo "Updated last_updated field: " . ($should_update_last_updated ? 'YES' : 'NO') . "\n";
    echo "Total Employees: " . $total_employees . "\n";
    echo "Records Updated: " . $updated_count . "\n";
    echo "Balances Changed: " . $changed_count . "\n";
    echo "Errors: " . $error_count . "\n";
    echo "\n--- UPDATE LIST ---\n";
    if (count($updates_log) > 0) {
        foreach ($updates_log as $log) {
            $is_changed = abs($log['old_value'] - $log['new_value']) > 0.001;
            if ($log['type'] === 'refresh') {
                $status = '🔄 REFRESHED ❌';
            } else {
                $status = $is_changed ? '✅ UPDATED (VALUE CHANGED)' : '🔄 UPDATED (SYNCED)';
            }
            printf("[%s] %s (%s) - Old: %.2f → New: %.2f [%s]\n", 
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
    echo "======================================================\n";
    echo "\nUSAGE MODES:\n";
    echo "  Mode 0 (Normal - Default):\n";
    echo "    - Runs ONCE per day automatically\n";
    echo "    - Updates all balances with daily accrual (last_updated = yesterday)\n";
    echo "    - Prevents duplicate runs on the same day\n";
    echo "    - CLI: php cron_update_vacation_balances.php\n";
    echo "    - Browser: /cron_update_vacation_balances.php\n";
    echo "\n  Mode 1 (Check Missing Only):\n";
    echo "    - Can run multiple times per day\n";
    echo "    - Checks for missing balance records and creates them\n";
    echo "    - Refreshes existing values but does NOT update last_updated\n";
    echo "    - Use to sync missing records without resetting the daily counter\n";
    echo "    - CLI: php cron_update_vacation_balances.php --force=1\n";
    echo "    - Browser: /cron_update_vacation_balances.php?force=1\n";
    echo "\n  Mode 2 (Full Bypass):\n";
    echo "    - Can run multiple times per day\n";
    echo "    - Forces full update including last_updated = yesterday\n";
    echo "    - Bypasses all once-per-day checks\n";
    echo "    - Use only for testing or emergency recalculations\n";
    echo "    - CLI: php cron_update_vacation_balances.php --force=2\n";
    echo "    - Browser: /cron_update_vacation_balances.php?force=2\n";
    echo "======================================================\n\n";

    // Recalculate earned days if requested
    // Usage: php cron_update_vacation_balances.php --recalc-earned
    // Or via browser: /cron_update_vacation_balances.php?recalc_earned=1
    $recalc_earned = (isset($argv) && in_array('--recalc-earned', $argv)) || (isset($_GET['recalc_earned']) && $_GET['recalc_earned'] == '1');
    
    if ($recalc_earned) {
        log_message("\n========== RECALCULATING EARNED DAYS ==========\n", 'info');
        echo "\n========== RECALCULATING EARNED DAYS ==========\n";
        
        $earned_update_count = 0;
        $earned_error_count = 0;
        
        // Get all active employees
        $earned_query = "SELECT DISTINCT evb.emp_id, evb.id as balance_record_id
                        FROM emp_vacation_balance evb
                        JOIN employees e ON evb.emp_id = e.emp_id
                        WHERE e.status = 1
                        ORDER BY evb.emp_id";
        
        $earned_result = mysqli_query($conDB, $earned_query);
        
        if ($earned_result) {
            while ($emp_row = mysqli_fetch_assoc($earned_result)) {
                $emp_id_earned = $emp_row['emp_id'];
                $balance_id = $emp_row['balance_record_id'];
                
                try {
                    // Recalculate earned days using the vacation calculator
                    $earned_days = get_live_vacation_balance($conDB, $emp_id_earned);
                    
                    if ($earned_days !== null) {
                        // Update with recalculated value
                        $earned_update_sql = "UPDATE emp_vacation_balance 
                                           SET available_balance = ?, 
                                               opening_balance = ?, 
                                               remaining_balance = ?,
                                               last_updated = NOW()
                                           WHERE id = ?";
                        
                        $earned_stmt = mysqli_prepare($conDB, $earned_update_sql);
                        if ($earned_stmt) {
                            mysqli_stmt_bind_param($earned_stmt, 'dddi', $earned_days, $earned_days, $earned_days, $balance_id);
                            
                            if (mysqli_stmt_execute($earned_stmt)) {
                                $earned_update_count++;
                                log_message("  [emp_id: $emp_id_earned] ✓ Earned days recalculated: $earned_days days", 'info');
                                echo "  [emp_id: $emp_id_earned] ✓ Earned days recalculated: $earned_days days\n";
                            } else {
                                log_message("  [emp_id: $emp_id_earned] ERROR: Failed to update earned days", 'error');
                                $earned_error_count++;
                            }
                            mysqli_stmt_close($earned_stmt);
                        } else {
                            log_message("  [emp_id: $emp_id_earned] ERROR: Prepare failed for earned days update", 'error');
                            $earned_error_count++;
                        }
                    } else {
                        log_message("  [emp_id: $emp_id_earned] WARNING: Could not calculate earned days", 'warning');
                        $earned_error_count++;
                    }
                } catch (Exception $e) {
                    log_message("  [emp_id: $emp_id_earned] ERROR: " . $e->getMessage(), 'error');
                    $earned_error_count++;
                }
            }
            mysqli_free_result($earned_result);
            
            echo "Earned Days Updated: " . $earned_update_count . "\n";
            echo "Errors: " . $earned_error_count . "\n";
            echo "==============================================\n\n";
            log_message("Earned days recalculation complete. Updated: $earned_update_count, Errors: $earned_error_count", 'info');
        }
    }

    // Finished update run
    exit(0);

} catch (Exception $e) {
    log_message("FATAL ERROR: " . $e->getMessage(), 'error');
    log_message("Stack trace: " . $e->getTraceAsString(), 'error');
    exit(1);
}

// Viewer functions removed from cron; see vacation_balance_report.php for GUI
