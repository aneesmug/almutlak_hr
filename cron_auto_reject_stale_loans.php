<?php
/**
 * Cron Job: Auto-Reject Stale Loan Requests
 * 
 * This script automatically rejects loan requests that have been pending
 * for more than 3 days without approval from the direct supervisor.
 * 
 * Schedule: Run daily (e.g., at 2:00 AM)
 * Windows Task Scheduler: php.exe "D:\xampp\htdocs\almutlak\system\cron_auto_reject_stale_loans.php"
 // * Linux Cron: 0 3 * * * /usr/local/bin/php /home/almutlak/public_html/hr/cron_auto_reject_stale_loans.php
 */


// Prevent direct browser access - only allow CLI or cron execution
if (php_sapi_name() !== 'cli' && !isset($_GET['cron_key']) || (isset($_GET['cron_key']) && $_GET['cron_key'] !== 'auto_reject_loans_2026')) {
    die('Access denied. This script can only be run via command line or with valid cron key.');
}

// Start output buffering for logging
ob_start();

// Include required files
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/helper_functions.php';

// Configuration
$DAYS_THRESHOLD = 3; // Number of days before auto-rejection
$SYSTEM_USER_ID = 1; // System user ID for automated actions
$report_file = __DIR__ . '/cron_logs/last_loan_rejection_report.json';

// Check for bypass flag:
// CLI: php script.php --force
// Browser: script.php?cron_key=...&force=1
$force_run = (isset($argv) && in_array('--force', $argv)) || (isset($_GET['force']) && $_GET['force'] == '1');

// Check if already executed today (to prevent duplicate runs)
$already_executed_today = false;
if (file_exists($report_file)) {
    $report_data = json_decode(file_get_contents($report_file), true);
    if ($report_data && isset($report_data['timestamp'])) {
        $report_date = substr($report_data['timestamp'], 0, 10);
        $today = date('Y-m-d');
        if ($report_date === $today) {
            $already_executed_today = true;
        }
    }
}

// If already executed today, don't run again (even with --force)
if ($already_executed_today) {
    $report_data = json_decode(file_get_contents($report_file), true);
    // Set content type for browser display
    if (php_sapi_name() !== 'cli') {
        header('Content-Type: text/plain; charset=utf-8');
    }
    echo "\n========== ALREADY UPDATED TODAY ==========\n";
    echo "Last update: " . $report_data['timestamp'] . "\n";
    echo "Total Stale Requests: " . ($report_data['total_stale'] ?? 0) . "\n";
    echo "Successfully Rejected: " . ($report_data['rejected_count'] ?? 0) . "\n";
    echo "Failed: " . ($report_data['failed_count'] ?? 0) . "\n";
    echo "Rejection Records: " . count($report_data['rejections_log'] ?? []) . "\n";
    echo "\nJSON file is protected from re-update today.\n";
    echo "File will be updated again tomorrow.\n";
    echo "===========================================\n\n";
    exit(0);
}

echo "=== Loan Auto-Rejection Cron Job Started ===\n";
echo "Execution Time: " . date('Y-m-d H:i:s') . "\n\n";

try {
    // Get request type ID for loan_request
    $type_query = mysqli_query($conDB, "SELECT `id` FROM `approval_request_types` WHERE `type_name` = 'loan_request' LIMIT 1");
    if (!$type_query || mysqli_num_rows($type_query) == 0) {
        throw new Exception("CRITICAL ERROR: 'loan_request' type not found in approval_request_types table.");
    }
    $request_type_id = (int)mysqli_fetch_assoc($type_query)['id'];
    
    echo "Request Type ID: {$request_type_id}\n";
    
    // Find all loan requests pending at level 1 (department manager) for more than 3 days
    $cutoff_date = date('Y-m-d H:i:s', strtotime("-{$DAYS_THRESHOLD} days"));
    echo "Cutoff Date: {$cutoff_date}\n\n";
    
    $sql = "SELECT 
                l.id,
                l.inv_no,
                l.emp_id,
                l.loan_amount,
                l.loan_type,
                l.created_at,
                e.name as employee_name,
                ra.approver_id,
                ra.approval_level,
                approver.name as supervisor_name,
                TIMESTAMPDIFF(HOUR, l.created_at, NOW()) as hours_pending
            FROM emp_loan l
            JOIN employees e ON l.emp_id = e.emp_id
            JOIN request_approvers ra ON ra.request_inv_no = l.inv_no 
                AND ra.request_type_id = ?
                AND ra.approval_level = 1
                AND ra.status = 'pending'
            LEFT JOIN employees approver ON ra.approver_id = approver.emp_id
            WHERE l.status = 'pending'
                AND l.created_at <= ?
                AND l.inv_no NOT LIKE 'LEGACY-%'
            ORDER BY l.created_at ASC";
    
    $stmt = $conDB->prepare($sql);
    if (!$stmt) {
        throw new Exception("Failed to prepare query: " . $conDB->error);
    }
    
    $stmt->bind_param("is", $request_type_id, $cutoff_date);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $stale_requests = [];
    while ($row = $result->fetch_assoc()) {
        $stale_requests[] = $row;
    }
    $stmt->close();
    
    $total_stale = count($stale_requests);
    echo "Found {$total_stale} stale loan request(s) pending supervisor approval for more than {$DAYS_THRESHOLD} days.\n\n";
    
    if ($total_stale === 0) {
        echo "=== Execution Summary ===\n";
        echo "Total Stale Requests: {$total_stale}\n";
        echo "Successfully Rejected: 0\n";
        echo "Failed: 0\n";
        echo "Completion Time: " . date('Y-m-d H:i:s') . "\n";
        echo "=== Cron Job Completed ===\n";
        echo "\nNo action needed. All loan requests are within the approval timeframe.\n\n";
        
        // Preserve old rejections and append empty log for today
        $old_rejections = [];
        if (file_exists($report_file)) {
            $old_data = json_decode(file_get_contents($report_file), true);
            if ($old_data && isset($old_data['rejections_log'])) {
                $old_rejections = $old_data['rejections_log'];
            }
        }
        
        $report_data = [
            'timestamp' => date('Y-m-d H:i:s'),
            'total_stale' => $total_stale,
            'rejected_count' => 0,
            'failed_count' => 0,
            'days_threshold' => $DAYS_THRESHOLD,
            'rejections_log' => $old_rejections
        ];
        file_put_contents($report_file, json_encode($report_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        
        log_cron_execution(ob_get_contents());
        
        // Set content type for browser display
        if (php_sapi_name() !== 'cli') {
            header('Content-Type: text/plain; charset=utf-8');
        }
        
        ob_end_flush();
        exit(0);
    }
    
    // Process each stale request
    $rejected_count = 0;
    $failed_count = 0;
    $rejections_log = []; // Store detailed rejection info for JSON report
    
    foreach ($stale_requests as $request) {
        echo "-------------------------------------------\n";
        echo "Processing Loan #{$request['id']} (INV: {$request['inv_no']})\n";
        echo "Employee: {$request['employee_name']} (ID: {$request['emp_id']})\n";
        echo "Loan Amount: " . number_format($request['loan_amount'], 2) . " SAR\n";
        echo "Loan Type: {$request['loan_type']}\n";
        echo "Created: {$request['created_at']}\n";
        echo "Hours Pending: {$request['hours_pending']} hours\n";
        echo "Pending with: {$request['supervisor_name']} (ID: {$request['approver_id']})\n";
        
        // Begin transaction for this rejection
        $conDB->begin_transaction();
        
        try {
            // Update loan status to rejected (rejection_reason field may not exist in all setups)
            // Check if rejection_reason and rejection_date columns exist
            $check_column = $conDB->query("SHOW COLUMNS FROM emp_loan LIKE 'rejection_reason'");
            $has_rejection_reason = ($check_column && $check_column->num_rows > 0);
            
            $check_date_column = $conDB->query("SHOW COLUMNS FROM emp_loan LIKE 'rejection_date'");
            $has_rejection_date = ($check_date_column && $check_date_column->num_rows > 0);
            
            $rejection_reason = "Automatically rejected due to no approval action from direct supervisor within {$DAYS_THRESHOLD} days. Request submitted on " . date('Y-m-d', strtotime($request['created_at'])) . ".";
            
            if ($has_rejection_reason && $has_rejection_date) {
                $update_loan = $conDB->prepare("UPDATE emp_loan SET 
                    status = 'rejected',
                    rejection_reason = ?,
                    rejection_date = NOW(),
                    rejected_by = ?
                    WHERE id = ?");
                $update_loan->bind_param("ssi", $rejection_reason, $SYSTEM_USER_ID, $request['id']);
            } elseif ($has_rejection_reason) {
                $update_loan = $conDB->prepare("UPDATE emp_loan SET 
                    status = 'rejected',
                    rejection_reason = ?,
                    rejected_by = ?
                    WHERE id = ?");
                $update_loan->bind_param("ssi", $rejection_reason, $SYSTEM_USER_ID, $request['id']);
            } elseif ($has_rejection_date) {
                $update_loan = $conDB->prepare("UPDATE emp_loan SET 
                    status = 'rejected',
                    rejection_date = NOW(),
                    rejected_by = ?
                    WHERE id = ?");
                $update_loan->bind_param("ii", $SYSTEM_USER_ID, $request['id']);
            } else {
                $update_loan = $conDB->prepare("UPDATE emp_loan SET 
                    status = 'rejected',
                    rejected_by = ?
                    WHERE id = ?");
                $update_loan->bind_param("ii", $SYSTEM_USER_ID, $request['id']);
            }
            
            if (!$update_loan->execute()) {
                throw new Exception("Failed to update loan status: " . $update_loan->error);
            }
            $update_loan->close();
            
            // Update approval chain - mark all pending approvers as 'rejected'
            // Using 'note' column (not 'comments') and 'action_date' (not 'approved_at')
            // Status ENUM values: 'pending','approved','rejected','awaiting'
            $update_chain = $conDB->prepare("UPDATE request_approvers SET 
                status = 'rejected',
                action_date = NOW(),
                note = ?
                WHERE request_inv_no = ? AND request_type_id = ? AND status = 'pending'");
            
            $auto_reject_comment = "Auto-rejected by system after {$DAYS_THRESHOLD} days of inactivity";
            $update_chain->bind_param("ssi", $auto_reject_comment, $request['inv_no'], $request_type_id);
            
            if (!$update_chain->execute()) {
                throw new Exception("Failed to update approval chain: " . $update_chain->error);
            }
            $update_chain->close();
            
            // Save rejection comment
            if (function_exists('save_approval_comment_db')) {
                save_approval_comment_db(
                    $conDB,
                    $request['inv_no'],
                    $request_type_id,
                    $SYSTEM_USER_ID,
                    'reject',
                    $rejection_reason,
                    1 // approval level
                );
            }
            
            // Create browser notification for employee
            if (function_exists('create_browser_notification')) {
                create_browser_notification(
                    $conDB,
                    $request['emp_id'],
                    'loan_rejected',
                    'Loan Request Auto-Rejected',
                    "Your loan request for " . number_format($request['loan_amount'], 2) . " SAR has been automatically rejected due to no supervisor approval within {$DAYS_THRESHOLD} days.",
                    "./all_applied_loan.php?search=" . urlencode($request['inv_no'])
                );
            }
            
            // Commit transaction
            $conDB->commit();
            
            echo "✓ Successfully auto-rejected\n";
            echo "Rejection Reason: {$rejection_reason}\n";
            $rejected_count++;
            
            // Store rejection details for JSON report
            $rejections_log[] = [
                'loan_id' => $request['id'],
                'inv_no' => $request['inv_no'],
                'emp_id' => $request['emp_id'],
                'emp_name' => $request['employee_name'],
                'loan_type' => $request['loan_type'],
                'loan_amount' => (float)$request['loan_amount'],
                'created_at' => $request['created_at'],
                'hours_pending' => (int)$request['hours_pending'],
                'days_pending' => ceil($request['hours_pending'] / 24),
                'supervisor_id' => $request['approver_id'],
                'supervisor_name' => $request['supervisor_name'],
                'rejection_reason' => $rejection_reason,
                'rejected_at' => date('Y-m-d H:i:s'),
                'status' => 'successfully_rejected'
            ];
            
        } catch (Exception $e) {
            $conDB->rollback();
            echo "✗ Failed to reject: " . $e->getMessage() . "\n";
            $failed_count++;
            
            // Store failed rejection info for JSON report
            $rejections_log[] = [
                'loan_id' => $request['id'],
                'inv_no' => $request['inv_no'],
                'emp_id' => $request['emp_id'],
                'emp_name' => $request['employee_name'],
                'loan_type' => $request['loan_type'],
                'loan_amount' => (float)$request['loan_amount'],
                'created_at' => $request['created_at'],
                'hours_pending' => (int)$request['hours_pending'],
                'days_pending' => ceil($request['hours_pending'] / 24),
                'supervisor_id' => $request['approver_id'],
                'supervisor_name' => $request['supervisor_name'],
                'rejection_reason' => $e->getMessage(),
                'rejected_at' => date('Y-m-d H:i:s'),
                'status' => 'failed',
                'error' => $e->getMessage()
            ];
        }
        
        echo "\n";
    }
    
    // Summary
    echo "=== Execution Summary ===\n";
    echo "Total Stale Requests: {$total_stale}\n";
    echo "Successfully Rejected: {$rejected_count}\n";
    echo "Failed: {$failed_count}\n";
    echo "Completion Time: " . date('Y-m-d H:i:s') . "\n";
    echo "=== Cron Job Completed ===\n";
    
    // Preserve old rejections and append new ones to history
    $old_rejections = [];
    if (file_exists($report_file)) {
        $old_data = json_decode(file_get_contents($report_file), true);
        if ($old_data && isset($old_data['rejections_log'])) {
            $old_rejections = $old_data['rejections_log'];
        }
    }
    
    // Combine old and new rejections
    $all_rejections = array_merge($old_rejections, $rejections_log);
    
    // Save report to JSON file for later viewing (with history)
    $report_data = [
        'timestamp' => date('Y-m-d H:i:s'),
        'total_stale' => $total_stale,
        'rejected_count' => $rejected_count,
        'failed_count' => $failed_count,
        'days_threshold' => $DAYS_THRESHOLD,
        'rejections_log' => $all_rejections
    ];
    file_put_contents($report_file, json_encode($report_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    
} catch (Exception $e) {
    echo "FATAL ERROR: " . $e->getMessage() . "\n";
    echo "Stack Trace:\n" . $e->getTraceAsString() . "\n";
}

// Log the execution
log_cron_execution(ob_get_contents());

// Set content type for browser display
if (php_sapi_name() !== 'cli') {
    header('Content-Type: text/plain; charset=utf-8');
}

ob_end_flush();

// Close database connection
if (isset($conDB)) {
    mysqli_close($conDB);
}

/**
 * Log cron execution to file
 */
function log_cron_execution($output) {
    $log_dir = __DIR__ . '/cron_logs';
    if (!is_dir($log_dir)) {
        mkdir($log_dir, 0755, true);
    }
    
    $log_file = $log_dir . '/auto_reject_loans_' . date('Y-m-d') . '.log';
    $timestamp = date('Y-m-d H:i:s');
    
    $log_entry = "\n" . str_repeat('=', 80) . "\n";
    $log_entry .= "EXECUTION: {$timestamp}\n";
    $log_entry .= str_repeat('=', 80) . "\n";
    $log_entry .= $output;
    $log_entry .= "\n";
    
    file_put_contents($log_file, $log_entry, FILE_APPEND);
}
