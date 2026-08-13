<?php
/**
 * Cron Job: Revert Expired Temporary Employee Transfers
 *
 * A temporary employee transfer moves employees.supervisor_id to the new
 * supervisor as soon as the transfer request is fully approved. This script
 * reverts that change back to the original supervisor once the transfer's
 * end_date has passed, for any transfer that hasn't already been reverted.
 *
 * Schedule: Run daily (e.g., at 1:00 AM)
 * Windows Task Scheduler: php.exe "D:\xampp\htdocs\almutlak\system\cron_revert_temp_transfers.php"
 * Linux Cron: 0 1 * * * /usr/local/bin/php /home/almutlak/public_html/hr/cron_revert_temp_transfers.php
 */

// Prevent direct browser access - only allow CLI or cron execution
if (php_sapi_name() !== 'cli' && !isset($_GET['cron_key']) || (isset($_GET['cron_key']) && $_GET['cron_key'] !== 'revert_temp_transfers_2026')) {
    die('Access denied. This script can only be run via command line or with valid cron key.');
}

require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/helper_functions.php';

$report_file = __DIR__ . '/cron_logs/last_temp_transfer_revert_report.json';

// Check for bypass flag: CLI: php script.php --force | Browser: script.php?cron_key=...&force=1
$force_run = (isset($argv) && in_array('--force', $argv)) || (isset($_GET['force']) && $_GET['force'] == '1');

$already_executed_today = false;
if (file_exists($report_file)) {
    $report_data = json_decode(file_get_contents($report_file), true);
    if ($report_data && isset($report_data['timestamp'])) {
        $already_executed_today = (substr($report_data['timestamp'], 0, 10) === date('Y-m-d'));
    }
}

if ($already_executed_today && !$force_run) {
    $report_data = json_decode(file_get_contents($report_file), true);
    if (php_sapi_name() !== 'cli') {
        header('Content-Type: text/plain; charset=utf-8');
    }
    echo "\n========== ALREADY UPDATED TODAY ==========\n";
    echo "Last run: " . $report_data['timestamp'] . "\n";
    echo "Reverted: " . ($report_data['reverted'] ?? 0) . "\n";
    echo "Skipped: " . ($report_data['skipped'] ?? 0) . "\n";
    echo "Errors: " . count($report_data['errors'] ?? []) . "\n";
    echo "Use --force (CLI) or &force=1 (browser) to run again today.\n";
    echo "=============================================\n\n";
    exit(0);
}

echo "=== Temporary Transfer Auto-Revert Cron Job Started ===\n";
echo "Execution Time: " . date('Y-m-d H:i:s') . "\n\n";

$summary = revert_expired_temporary_transfers($conDB);

echo "Reverted: " . $summary['reverted'] . "\n";
echo "Skipped (already changed since): " . $summary['skipped'] . "\n";
echo "Errors: " . count($summary['errors']) . "\n";
foreach ($summary['errors'] as $err) {
    echo "  - {$err}\n";
}

$report_data = [
    'timestamp' => date('Y-m-d H:i:s'),
    'reverted' => $summary['reverted'],
    'skipped' => $summary['skipped'],
    'errors' => $summary['errors']
];
file_put_contents($report_file, json_encode($report_data, JSON_PRETTY_PRINT));

echo "\n=== Cron Job Completed ===\n";
