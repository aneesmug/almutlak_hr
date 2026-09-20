<?php
/**
 * Cron Job: Temporary Role Transfer Expiry Reminder
 *
 * Emails the HR user who granted a temporary role assignment (vacation-based or
 * manual, via emp_temp_role_assignments) one day before it expires, so they know
 * to extend coverage or let it lapse. The access itself always reverts on its own
 * (session_check.php reads the date window fresh every request) - this is purely
 * a heads-up email, guarded by expiry_notified_at so it only sends once per row.
 *
 * Schedule: Run daily (e.g., at 7:00 AM)
 * Windows Task Scheduler: php.exe "D:\xampp\htdocs\almutlak\system\cron_temp_role_expiry_reminder.php"
 * Linux Cron: 0 7 * * * /usr/local/bin/php /home/almutlak/public_html/hr/cron_temp_role_expiry_reminder.php
 */

// Prevent direct browser access - only allow CLI or cron execution
if (php_sapi_name() !== 'cli' && !isset($_GET['cron_key']) || (isset($_GET['cron_key']) && $_GET['cron_key'] !== 'temp_role_expiry_reminder_2026')) {
    die('Access denied. This script can only be run via command line or with valid cron key.');
}

require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/helper_functions.php';

$report_file = __DIR__ . '/cron_logs/last_temp_role_expiry_reminder_report.json';

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
    echo "Notified: " . ($report_data['notified'] ?? 0) . "\n";
    echo "Skipped: " . ($report_data['skipped'] ?? 0) . "\n";
    echo "Errors: " . count($report_data['errors'] ?? []) . "\n";
    echo "Use --force (CLI) or &force=1 (browser) to run again today.\n";
    echo "=============================================\n\n";
    exit(0);
}

echo "=== Temporary Role Transfer Expiry Reminder Cron Job Started ===\n";
echo "Execution Time: " . date('Y-m-d H:i:s') . "\n\n";

$summary = sendTempRoleExpiryReminders($conDB);

echo "Notified: " . $summary['notified'] . "\n";
echo "Skipped (no valid email): " . $summary['skipped'] . "\n";
echo "Errors: " . count($summary['errors']) . "\n";
foreach ($summary['errors'] as $err) {
    echo "  - {$err}\n";
}

$report_data = [
    'timestamp' => date('Y-m-d H:i:s'),
    'notified' => $summary['notified'],
    'skipped' => $summary['skipped'],
    'errors' => $summary['errors']
];
file_put_contents($report_file, json_encode($report_data, JSON_PRETTY_PRINT));

echo "\n=== Cron Job Completed ===\n";
