<?php
/**
 * Direct-socket online/offline check for ZK devices that stay pointed at
 * BioTime (ADMS push, unchanged). For each device with a pull_port
 * configured (the router-forwarded external port for its local 4370 port),
 * attempts a real ZK protocol handshake - success/failure IS the live
 * online/offline state, no heartbeat-staleness guessing needed.
 *
 * Does NOT touch attendance/punch data - see includes/ZkSocketClient.php
 * for why (binary log format needs real-hardware validation first).
 *
 * Run every 2 minutes via crontab (avoid the "*" + "/" step syntax literally
 * inside this comment block - it would prematurely close the doc comment):
 *   0-58/2 * * * * /usr/bin/php /full/path/to/system/zk_socket_sync.php >> /full/path/to/system/cron_logs/zk_socket_sync.log 2>&1
 */
require_once __DIR__ . '/includes/zk_db_connect.php';
require_once __DIR__ . '/includes/ZkSocketClient.php';

$conn = zk_get_db_connection();
if (!$conn) {
    fwrite(STDERR, "[ZK] Could not connect to database.\n");
    exit(1);
}

$result = mysqli_query($conn, "SELECT id, serial_number, device_name, pull_host, pull_port FROM zk_devices WHERE pull_port IS NOT NULL AND pull_port > 0");
if (!$result) {
    fwrite(STDERR, "[ZK] Could not query zk_devices: " . mysqli_error($conn) . "\n");
    exit(1);
}

$devices = [];
while ($row = mysqli_fetch_assoc($result)) {
    $devices[] = $row;
}

if (empty($devices)) {
    echo date('Y-m-d H:i:s') . " - No devices have a pull_port configured. Nothing to check.\n";
    exit(0);
}

$onlineCount = 0;
$offlineCount = 0;

$updateStmt = mysqli_prepare($conn, "UPDATE zk_devices SET state = ?, last_activity = IF(? = 'online', NOW(), last_activity) WHERE id = ?");

foreach ($devices as $device) {
    $host = $device['pull_host'] ?: '212.118.124.212';
    $port = (int) $device['pull_port'];

    $client = new ZkSocketClient($host, $port, 5);
    $isOnline = $client->connect();
    if ($isOnline) {
        $client->disconnect();
    }

    $state = $isOnline ? 'online' : 'offline';
    if ($isOnline) {
        $onlineCount++;
    } else {
        $offlineCount++;
    }

    $id = (int) $device['id'];
    mysqli_stmt_bind_param($updateStmt, 'ssi', $state, $state, $id);
    mysqli_stmt_execute($updateStmt);

    echo sprintf(
        "%s - %s (%s) @ %s:%d - %s\n",
        date('Y-m-d H:i:s'),
        $device['device_name'],
        $device['serial_number'],
        $host,
        $port,
        strtoupper($state)
    );
}

mysqli_stmt_close($updateStmt);

echo date('Y-m-d H:i:s') . " - Checked " . count($devices) . " device(s): {$onlineCount} online, {$offlineCount} offline.\n";
