<?php
// ZKTeco ADMS command-poll endpoint. Devices call this every few seconds
// while online - used purely as a heartbeat here (no command queue yet).
require_once __DIR__ . '/../includes/zk_helpers.php';

header('Content-Type: text/plain; charset=UTF-8');

$serial = trim((string) ($_GET['SN'] ?? ''));
$conn = $serial !== '' ? zk_get_db_connection() : null;

if ($conn && zk_lookup_device($conn, $serial)) {
    zk_touch_device($conn, $serial, $_SERVER['REMOTE_ADDR'] ?? null);
}

echo 'OK';
