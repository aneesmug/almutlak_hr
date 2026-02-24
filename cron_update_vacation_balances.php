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

    /**
     * Fetch latest vacation balance record for employee; create baseline record if missing.
     *
     * @param mysqli $conDB
     * @param string $emp_id
     * @return array|null
     */
    function get_or_create_balance_record($conDB, $emp_id) {
        $select_sql = "SELECT id, vac_id, contract_id, total_days, used_days, remaining_balance,
                              available_balance, carryover_days, period_start, period_end, last_updated
                       FROM emp_vacation_balance
                       WHERE emp_id = ?
                       ORDER BY id DESC
                       LIMIT 1";

        $select_stmt = mysqli_prepare($conDB, $select_sql);
        if (!$select_stmt) {
            log_message("  [emp_id: $emp_id] ERROR: Prepare failed while reading balance - " . mysqli_error($conDB), 'error');
            return null;
        }

        mysqli_stmt_bind_param($select_stmt, 's', $emp_id);
        if (!mysqli_stmt_execute($select_stmt)) {
            log_message("  [emp_id: $emp_id] ERROR: Execute failed while reading balance - " . mysqli_stmt_error($select_stmt), 'error');
            mysqli_stmt_close($select_stmt);
            return null;
        }

        $select_result = mysqli_stmt_get_result($select_stmt);
        $record = mysqli_fetch_assoc($select_result);
        mysqli_stmt_close($select_stmt);

        if ($record) {
            return $record;
        }

        log_message("  [emp_id: $emp_id] ℹ️ No vacation balance record found. Creating initial balance record.", 'info');

        if (!class_exists('VacationCalculator')) {
            log_message("  [emp_id: $emp_id] ERROR: VacationCalculator class not found", 'error');
            return null;
        }

        try {
            $calculator = new VacationCalculator($conDB);
            $calc_data = $calculator->getCalculatedBalance($emp_id);
        } catch (Throwable $e) {
            log_message("  [emp_id: $emp_id] ERROR: Failed calculating initial balance - " . $e->getMessage(), 'error');
            return null;
        }

        if (!$calc_data) {
            log_message("  [emp_id: $emp_id] ERROR: Could not calculate initial balance data", 'error');
            return null;
        }

        $contract_id = isset($calc_data['contract_id']) ? (int)$calc_data['contract_id'] : 0;
        if ($contract_id <= 0) {
            log_message("  [emp_id: $emp_id] ERROR: Invalid contract_id while creating initial balance", 'error');
            return null;
        }

        $period_start = ($calc_data['period_start'] instanceof DateTime)
            ? $calc_data['period_start']->format('Y-m-d')
            : (string)$calc_data['period_start'];
        $period_end = ($calc_data['period_end'] instanceof DateTime)
            ? $calc_data['period_end']->format('Y-m-d')
            : (string)$calc_data['period_end'];

        $total_days = (float)($calc_data['total_days'] ?? 0);
        $used_days = (float)($calc_data['used_days'] ?? 0);
        $remaining_balance = (float)($calc_data['remaining_balance'] ?? 0);
        $available_balance = (float)($calc_data['available_balance'] ?? 0);
        $carryover_days = (float)($calc_data['carryover_days'] ?? 0);

        $insert_sql = "INSERT INTO emp_vacation_balance
                          (emp_id, vac_id, contract_id, period_start, period_end,
                           total_days, used_days, remaining_balance, available_balance,
                           opening_balance, carryover_days, last_updated)
                       VALUES (?, 0, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";

        $insert_stmt = mysqli_prepare($conDB, $insert_sql);
        if (!$insert_stmt) {
            log_message("  [emp_id: $emp_id] ERROR: Prepare failed while creating balance - " . mysqli_error($conDB), 'error');
            return null;
        }

        mysqli_stmt_bind_param(
            $insert_stmt,
            'sisssddddd',
            $emp_id,
            $contract_id,
            $period_start,
            $period_end,
            $total_days,
            $used_days,
            $remaining_balance,
            $available_balance,
            $available_balance,
            $carryover_days
        );

        if (!mysqli_stmt_execute($insert_stmt)) {
            log_message("  [emp_id: $emp_id] ERROR: Insert failed while creating balance - " . mysqli_stmt_error($insert_stmt), 'error');
            mysqli_stmt_close($insert_stmt);
            return null;
        }
        mysqli_stmt_close($insert_stmt);

        log_message("  [emp_id: $emp_id] ✅ Initial vacation balance record created", 'info');

        $reselect_stmt = mysqli_prepare($conDB, $select_sql);
        if (!$reselect_stmt) {
            log_message("  [emp_id: $emp_id] ERROR: Prepare failed while re-reading balance - " . mysqli_error($conDB), 'error');
            return null;
        }

        mysqli_stmt_bind_param($reselect_stmt, 's', $emp_id);
        if (!mysqli_stmt_execute($reselect_stmt)) {
            log_message("  [emp_id: $emp_id] ERROR: Execute failed while re-reading balance - " . mysqli_stmt_error($reselect_stmt), 'error');
            mysqli_stmt_close($reselect_stmt);
            return null;
        }

        $reselect_result = mysqli_stmt_get_result($reselect_stmt);
        $new_record = mysqli_fetch_assoc($reselect_result);
        mysqli_stmt_close($reselect_stmt);

        return $new_record ?: null;
    }

    // =====================================================================
    // FUNCTION 1: Normal Mode (force=0) - Update once per day
    // =====================================================================
    function process_employees_normal($conDB, &$updated_count, &$changed_count, &$error_count) {
        global $updates_log, $conDB_global;
        
        $query = "SELECT e.emp_id
                  FROM employees e
                  WHERE e.status = 1
                  ORDER BY e.emp_id";
        
        $result = mysqli_query($conDB, $query);
        if (!$result) {
            log_message("ERROR: Query failed - " . mysqli_error($conDB), 'error');
            return 0;
        }
        
        $total = mysqli_num_rows($result);
        
        while ($row = mysqli_fetch_assoc($result)) {
            $emp_id = $row['emp_id'];
            $current_record = get_or_create_balance_record($conDB, $emp_id);
            
            if (!$current_record) {
                log_message("  [emp_id: $emp_id] ERROR: Could not fetch record", 'error');
                $error_count++;
                continue;
            }

            $balance_record_id = (int)$current_record['id'];
            $old_balance = (float)$current_record['available_balance'];
            
            $last_updated = $current_record['last_updated'];
            $today_str = date('Y-m-d');
            
            // Skip if already updated today
            if ($last_updated && substr($last_updated, 0, 10) === $today_str) {
                log_message("  [emp_id: $emp_id] ⏭️ SKIPPED: Already updated today ($last_updated)", 'warning');
                continue;
            }
            
            // Calculate live balance
            $live_balance = get_live_vacation_balance($conDB, $emp_id);
            if ($live_balance === null) {
                log_message("  [emp_id: $emp_id] WARNING: Could not calculate balance", 'warning');
                $error_count++;
                continue;
            }
            
            $live_balance = (float)$live_balance;
            $balance_changed = (abs($old_balance - $live_balance) > 0.001);
            
            // Set last_updated to NOW()
            $now = new DateTime();
            $new_last_updated = $now->format('Y-m-d H:i:s');
            
            // Update database
            $update_sql = "UPDATE `emp_vacation_balance` 
                          SET `available_balance` = ?, `opening_balance` = ?,
                              `remaining_balance` = ?, `last_updated` = ? WHERE `id` = ?";
            
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
                $changed_count++;
                
                // Insert history record
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
                    $change_amount = $live_balance - $old_balance;
                    $balance_changed_int = $balance_changed ? 1 : 0;
                    $change_reason = "CRON_DAILY_UPDATE";
                    $calc_status = "success";
                    $notes = "Daily cron auto-update";
                    $snapshot_date = date('Y-m-d');
                    $snapshot_time = date('Y-m-d H:i:s');
                    
                    mysqli_stmt_bind_param($history_stmt, 'siiiddddddddssidsssss',
                        $emp_id, $current_record['vac_id'], $current_record['contract_id'], $balance_record_id,
                        $old_balance, $current_record['used_days'], $current_record['remaining_balance'],
                        $live_balance, $current_record['used_days'], $current_record['remaining_balance'],
                        $current_record['carryover_days'], $current_record['total_days'],
                        $current_record['period_start'], $current_record['period_end'],
                        $balance_changed_int, $change_amount, $change_reason,
                        $calc_status, $notes, $snapshot_date, $snapshot_time
                    );
                    mysqli_stmt_execute($history_stmt);
                    mysqli_stmt_close($history_stmt);
                }
                
                if ($balance_changed) {
                    $change_msg = "Updated: $old_balance → $live_balance (VALUE CHANGED)";
                } else {
                    $change_msg = "Updated: $live_balance (synced daily for consistency)";
                }
                log_message("  [emp_id: $emp_id] ✅ $change_msg", 'update', $emp_id, $old_balance, $live_balance);
            }
        }
        mysqli_free_result($result);
        return $total;
    }

    // =====================================================================
    // FUNCTION 2: Check Missing Mode (force=1) - Refresh values only
    // =====================================================================
    function process_employees_check_missing($conDB, &$updated_count, &$changed_count, &$error_count) {
        global $updates_log, $conDB_global;
        
        $query = "SELECT e.emp_id
                  FROM employees e
                  WHERE e.status = 1
                  ORDER BY e.emp_id";
        
        $result = mysqli_query($conDB, $query);
        if (!$result) {
            log_message("ERROR: Query failed - " . mysqli_error($conDB), 'error');
            return 0;
        }
        
        $total = mysqli_num_rows($result);
        
        while ($row = mysqli_fetch_assoc($result)) {
            $emp_id = $row['emp_id'];
            $current_record = get_or_create_balance_record($conDB, $emp_id);
            if (!$current_record) {
                log_message("  [emp_id: $emp_id] ERROR: Could not fetch record in check-missing mode", 'error');
                $error_count++;
                continue;
            }

            $old_balance = (float)$current_record['available_balance'];
            $live_balance = get_live_vacation_balance($conDB, $emp_id);
            if ($live_balance === null) {
                log_message("  [emp_id: $emp_id] WARNING: Could not calculate balance in check-missing mode", 'warning');
                $error_count++;
                continue;
            }
            $live_balance = (float)$live_balance;
            
            // Only show refreshed values - NO database updates
            log_message("  [emp_id: $emp_id] 🔄 REFRESHED: $old_balance → $live_balance ❌", 'refresh', $emp_id, $old_balance, $live_balance);
        }
        mysqli_free_result($result);
        return $total;
    }

    // =====================================================================
    // FUNCTION 3: Force Update Mode (force=2) - Update all, set to yesterday
    // =====================================================================
    function process_employees_force_update($conDB, &$updated_count, &$changed_count, &$error_count) {
        global $updates_log, $conDB_global;
        
        $query = "SELECT e.emp_id
                  FROM employees e
                  WHERE e.status = 1
                  ORDER BY e.emp_id";
        
        $result = mysqli_query($conDB, $query);
        if (!$result) {
            log_message("ERROR: Query failed - " . mysqli_error($conDB), 'error');
            return 0;
        }
        
        $total = mysqli_num_rows($result);
        
        while ($row = mysqli_fetch_assoc($result)) {
            $emp_id = $row['emp_id'];
            $current_record = get_or_create_balance_record($conDB, $emp_id);
            
            if (!$current_record) {
                log_message("  [emp_id: $emp_id] ERROR: Could not fetch record", 'error');
                $error_count++;
                continue;
            }

            $balance_record_id = (int)$current_record['id'];
            $old_balance = (float)$current_record['available_balance'];
            
            // Calculate live balance
            $live_balance = get_live_vacation_balance($conDB, $emp_id);
            if ($live_balance === null) {
                log_message("  [emp_id: $emp_id] WARNING: Could not calculate balance", 'warning');
                $error_count++;
                continue;
            }
            
            $live_balance = (float)$live_balance;
            $balance_changed = (abs($old_balance - $live_balance) > 0.001);
            
            // Set last_updated to YESTERDAY at midnight
            $yesterday = new DateTime('yesterday');
            $new_last_updated = $yesterday->format('Y-m-d 00:00:00');
            
            // Update database
            $update_sql = "UPDATE `emp_vacation_balance` 
                          SET `available_balance` = ?, `opening_balance` = ?,
                              `remaining_balance` = ?, `last_updated` = ? WHERE `id` = ?";
            
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
                $changed_count++;
                
                // Insert history record
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
                    $change_amount = $live_balance - $old_balance;
                    $balance_changed_int = $balance_changed ? 1 : 0;
                    $change_reason = "CRON_FORCE_UPDATE";
                    $calc_status = "success";
                    $notes = "Force update - yesterday accrual";
                    $snapshot_date = date('Y-m-d');
                    $snapshot_time = date('Y-m-d H:i:s');
                    
                    mysqli_stmt_bind_param($history_stmt, 'siiiddddddddssidsssss',
                        $emp_id, $current_record['vac_id'], $current_record['contract_id'], $balance_record_id,
                        $old_balance, $current_record['used_days'], $current_record['remaining_balance'],
                        $live_balance, $current_record['used_days'], $current_record['remaining_balance'],
                        $current_record['carryover_days'], $current_record['total_days'],
                        $current_record['period_start'], $current_record['period_end'],
                        $balance_changed_int, $change_amount, $change_reason,
                        $calc_status, $notes, $snapshot_date, $snapshot_time
                    );
                    mysqli_stmt_execute($history_stmt);
                    mysqli_stmt_close($history_stmt);
                }
                
                if ($balance_changed) {
                    $change_msg = "Updated: $old_balance → $live_balance (VALUE CHANGED)";
                } else {
                    $change_msg = "Updated: $live_balance (synced daily for consistency)";
                }
                log_message("  [emp_id: $emp_id] ✅ $change_msg", 'update', $emp_id, $old_balance, $live_balance);
            }
        }
        mysqli_free_result($result);
        return $total;
    }

    // =====================================================================
    // CALL THE APPROPRIATE FUNCTION BASED ON FORCE LEVEL
    // =====================================================================
    $updated_count = 0;
    $changed_count = 0;
    $error_count = 0;
    $total_employees = 0;
    
    if ($force_level === 1) {
        log_message("Running FORCE LEVEL 1: Check missing mode", 'info');
        $total_employees = process_employees_check_missing($conDB, $updated_count, $changed_count, $error_count);
    } elseif ($force_level === 2) {
        log_message("Running FORCE LEVEL 2: Force update mode", 'info');
        $total_employees = process_employees_force_update($conDB, $updated_count, $changed_count, $error_count);
    } else {
        log_message("Running FORCE LEVEL 0: Normal mode", 'info');
        $total_employees = process_employees_normal($conDB, $updated_count, $changed_count, $error_count);
    }

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
                $status = '🔄 REFRESHED (❌ NOT UPDATE)';
            } else {
                $status = $is_changed ? '✅ UPDATED (💰 VALUE CHANGED)' : '🔄 UPDATED (SYNCED)';
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
