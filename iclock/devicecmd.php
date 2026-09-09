<?php
// ZKTeco ADMS command-result endpoint. Devices POST here to report the
// outcome of a queued command. No commands are ever queued yet (no admin
// UI to issue one), so results are discarded - this only serves as a heartbeat.
require_once __DIR__ . '/../includes/zk_helpers.php';

header('Content-Type: text/plain; charset=UTF-8');

$serial = trim((string) ($_GET['SN'] ?? ''));
$conn = $serial !== '' ? zk_get_db_connection() : null;

if ($conn && zk_lookup_device($conn, $serial)) {
    zk_touch_device($conn, $serial, $_SERVER['REMOTE_ADDR'] ?? null);
}

echo 'OK';
