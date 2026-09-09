<?php
// ZKTeco ADMS push endpoint - devices in "Cloud Server Mode" hit this exact
// path (no extension, firmware-hardcoded) for handshake (GET) and to upload
// attendance logs (POST). See includes/zk_db_connect.php for why this does
// not go through includes/db.php.
require_once __DIR__ . '/../includes/zk_helpers.php';

header('Content-Type: text/plain; charset=UTF-8');

$serial = trim((string) ($_GET['SN'] ?? ''));
if ($serial === '') {
    echo 'OK';
    exit;
}

$conn = zk_get_db_connection();
if (!$conn) {
    echo 'OK';
    exit;
}

// Whitelist only: unregistered serials are logged and dropped, never written.
// This endpoint is reachable from the open internet - anyone could claim any SN.
if (!zk_lookup_device($conn, $serial)) {
    error_log('[ZK] Rejected unregistered device SN=' . $serial . ' from ' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
    echo 'OK';
    exit;
}

zk_touch_device($conn, $serial, $_SERVER['REMOTE_ADDR'] ?? null);

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if ($method === 'GET') {
    // Handshake/ping - standard ADMS option response so the device proceeds to push data.
    echo "GET OPTION FROM: {$serial}\n";
    echo "Stamp=9999\n";
    echo "OpStamp=9999\n";
    echo "ErrorDelay=30\n";
    echo "Delay=10\n";
    echo "TransTimes=00:00;14:05\n";
    echo "TransInterval=1\n";
    echo "TransFlag=1111000000\n";
    echo "TimeZone=3\n";
    echo "Realtime=1\n";
    echo "Encrypt=0\n";
    exit;
}

// POST: device is uploading a data table (we only act on ATTLOG - punches).
$table = strtoupper(trim((string) ($_GET['table'] ?? '')));
$body = file_get_contents('php://input');

if ($table !== 'ATTLOG' || $body === '' || $body === false) {
    echo 'OK';
    exit;
}

$lines = preg_split('/\r\n|\r|\n/', trim($body));
$rawStmt = mysqli_prepare(
    $conn,
    "INSERT IGNORE INTO zk_attendance_raw (serial_number, pin, punch_time, status, verify, work_code) VALUES (?, ?, ?, ?, ?, ?)"
);

$insertedCount = 0;
foreach ($lines as $line) {
    if (trim($line) === '') {
        continue;
    }

    // ATTLOG line format: PIN\tTime\tStatus\tVerify\tWorkCode\t...(ignored trailing fields)
    $fields = explode("\t", $line);
    $pin = trim($fields[0] ?? '');
    $rawTime = trim($fields[1] ?? '');
    $status = trim($fields[2] ?? '');
    $verify = trim($fields[3] ?? '');
    $workCode = trim($fields[4] ?? '');

    $timestamp = $rawTime !== '' ? strtotime($rawTime) : false;
    if ($pin === '' || $timestamp === false) {
        continue;
    }
    $punchTime = date('Y-m-d H:i:s', $timestamp);

    mysqli_stmt_bind_param($rawStmt, 'ssssss', $serial, $pin, $punchTime, $status, $verify, $workCode);
    mysqli_stmt_execute($rawStmt);

    if (mysqli_stmt_affected_rows($rawStmt) > 0) {
        $insertedCount++;
    }

    // Attempted every time this punch is re-sent (device retries the whole
    // ATTLOG batch until it gets a clean ack), not just when its raw row was
    // new - see zk_helpers.php's zk_insert_live_attendance() for why: an
    // employee not yet active the first time a punch arrived must still be
    // picked up once they are, instead of that punch being lost forever.
    zk_insert_live_attendance($conn, $serial, $pin, $punchTime, $status);
}
mysqli_stmt_close($rawStmt);

zk_bump_device_transaction_count($conn, $serial, $insertedCount);

echo "OK: {$insertedCount}";
