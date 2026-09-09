<?php
/**
 * Marks ZK devices offline once they've gone quiet longer than the
 * configured threshold (app_settings.device_offline_threshold_minutes).
 * ZKTeco devices in ADMS push mode call cdata/getrequest every few seconds
 * while online, so a few quiet minutes reliably means the device dropped off.
 *
 * Run every minute via crontab:
 *   * * * * * /usr/bin/php /full/path/to/system/cron_zk_device_offline_check.php >> /full/path/to/system/cron_logs/zk_device_offline.log 2>&1
 */
require_once __DIR__ . '/includes/zk_helpers.php';

$conn = zk_get_db_connection();
if (!$conn) {
    fwrite(STDERR, "[ZK] Could not connect to database.\n");
    exit(1);
}

$thresholdMinutes = zk_get_offline_threshold_minutes($conn);

$stmt = mysqli_prepare(
    $conn,
    "UPDATE zk_devices SET state = 'offline' WHERE state = 'online' AND (last_activity IS NULL OR last_activity < (NOW() - INTERVAL ? MINUTE))"
);
mysqli_stmt_bind_param($stmt, 'i', $thresholdMinutes);
mysqli_stmt_execute($stmt);
$affected = mysqli_stmt_affected_rows($stmt);
mysqli_stmt_close($stmt);

echo date('Y-m-d H:i:s') . " - Threshold: {$thresholdMinutes}m - Marked offline: {$affected}\n";
