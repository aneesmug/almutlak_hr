<?php
/**
 * Receives punches exported from BioTime's own database by a small script
 * running ON the local BioTime server (zk_local_agent/zk_biotime_export.ps1)
 * and pushed here over HTTPS. Devices, BioTime, and the local network/router
 * stay completely untouched - this endpoint is the only new moving part.
 *
 * Auth: shared secret (app_settings.zk_sync_secret_key, self-healed - see
 * zk_get_sync_secret()) sent as header X-Sync-Secret or POST field "secret".
 *
 * Expected JSON body:
 * {
 *   "punches": [
 *     {"terminal_sn": "...", "pin": "...", "punch_time": "2026-09-08 09:00:00", "status": "0", "verify": "1"},
 *     ...
 *   ],
 *   "terminals": [
 *     {"sn": "...", "alias": "...", "ip_address": "...", "real_ip": "...", "state": 1,
 *      "last_activity": "2026-09-08 12:54:36", "user_count": 21, "transaction_count": 38146,
 *      "fp_count": 2, "face_count": 22, "palm_count": 1},
 *     ...
 *   ]
 * }
 *
 * "terminals" is BioTime's own iclock_terminal snapshot (schema confirmed
 * against the real live database, not guessed) - it's the authoritative
 * source for device state/counters, applied via zk_sync_terminal_state() for
 * every device on every sync, independent of whether that device happened to
 * have a punch in this batch.
 *
 * Every batch also folds its punches straight into the real `attendance`
 * table (first punch of the day = time_in, last = time_out) for every
 * (employee, day) touched by a newly-inserted punch this run - see the
 * "Fold each newly-touched day's punches..." block below and
 * attendance_upsert_day() in includes/attendance_helpers.php. No separate
 * cron is needed for this; cron_zk_attendance_pairing.php still exists as an
 * optional manual backfill/repair tool (e.g. after importing old punches),
 * not part of normal operation.
 */
require_once __DIR__ . '/includes/zk_helpers.php';
require_once __DIR__ . '/includes/attendance_helpers.php';

header('Content-Type: application/json; charset=UTF-8');

$conn = zk_get_db_connection();
if (!$conn) {
    http_response_code(503);
    echo json_encode(['status' => 'error', 'message' => 'Database unavailable.']);
    exit;
}

$providedSecret = $_SERVER['HTTP_X_SYNC_SECRET'] ?? ($_POST['secret'] ?? '');
$expectedSecret = zk_get_sync_secret($conn);

if ($providedSecret === '' || !hash_equals($expectedSecret, (string) $providedSecret)) {
    error_log('[ZK] zk_sync_import.php rejected request with invalid secret from ' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Invalid secret.']);
    exit;
}

$rawBody = file_get_contents('php://input');
$payload = json_decode($rawBody, true);
if (!is_array($payload)) {
    $jsonError = json_last_error_msg();
    $rawLength = strlen($rawBody);
    // Saved verbatim (not just logged) so the exact bytes that broke decoding
    // can be inspected directly - a truncated/escaped log line loses the very
    // control/invalid bytes that are the actual problem.
    @file_put_contents(__DIR__ . '/cron_logs/zk_sync_last_bad_body.raw', $rawBody);
    error_log("[ZK] zk_sync_import.php: json_decode failed - {$jsonError} - body length {$rawLength} bytes - saved to cron_logs/zk_sync_last_bad_body.raw");
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => 'Invalid JSON body.',
        'json_error' => $jsonError,
        'body_length' => $rawLength,
    ]);
    exit;
}

$punches = is_array($payload['punches'] ?? null) ? $payload['punches'] : [];
$terminals = is_array($payload['terminals'] ?? null) ? $payload['terminals'] : [];

$devicesSynced = 0;
foreach ($terminals as $terminal) {
    if (is_array($terminal) && zk_sync_terminal_state($conn, $terminal)) {
        $devicesSynced++;
    }
}

$rawStmt = mysqli_prepare(
    $conn,
    "INSERT IGNORE INTO zk_attendance_raw (serial_number, pin, punch_time, status, verify, work_code) VALUES (?, ?, ?, ?, ?, ?)"
);

$insertedCount = 0;
$skippedDuplicate = 0;
$skippedInvalid = 0;
// (pin, punch_date) pairs touched by a newly-inserted punch this batch - the
// pairing step below only needs to re-aggregate these, not every day ever
// synced. Keyed by "pin|date" to dedupe within the batch.
$affectedDays = [];
// Echoed back to the caller so zk_biotime_export.ps1 can write a local .json
// export containing ONLY punches that were genuinely new this run (not the
// whole batch, not the duplicates re-sent from a DESC-ordered window) - the
// server is the only place that actually knows which ones were new.
$insertedPunches = [];

foreach ($punches as $punch) {
    $serial = trim((string) ($punch['terminal_sn'] ?? ''));
    $pin = trim((string) ($punch['pin'] ?? ''));
    $rawTime = trim((string) ($punch['punch_time'] ?? ''));
    $status = trim((string) ($punch['status'] ?? ''));
    $verify = trim((string) ($punch['verify'] ?? ''));
    $workCode = trim((string) ($punch['work_code'] ?? ''));

    $timestamp = $rawTime !== '' ? strtotime($rawTime) : false;
    if ($serial === '' || $pin === '' || $timestamp === false) {
        $skippedInvalid++;
        continue;
    }
    $punchTime = date('Y-m-d H:i:s', $timestamp);

    mysqli_stmt_bind_param($rawStmt, 'ssssss', $serial, $pin, $punchTime, $status, $verify, $workCode);
    mysqli_stmt_execute($rawStmt);

    if (mysqli_stmt_affected_rows($rawStmt) > 0) {
        $insertedCount++;
    } else {
        $skippedDuplicate++;
    }

    // Attempted for every punch in the batch, not just ones new to
    // zk_attendance_raw above - a punch whose employee wasn't active/didn't
    // exist yet the first time it was seen must keep getting retried on every
    // later sync that still re-sends it, or it's lost the moment its raw row
    // exists (see zk_helpers.php's zk_insert_live_attendance() for why this is
    // safe: INSERT IGNORE against zk_attendance's own unique key).
    if (zk_insert_live_attendance($conn, $serial, $pin, $punchTime, $status)) {
        $punchDate = date('Y-m-d', $timestamp);
        $affectedDays[$pin . '|' . $punchDate] = ['pin' => $pin, 'date' => $punchDate];
        $insertedPunches[] = [
            'terminal_sn' => $serial,
            'pin' => $pin,
            'punch_time' => $punchTime,
            'status' => $status,
            'verify' => $verify,
            'work_code' => $workCode,
        ];
    }
}
mysqli_stmt_close($rawStmt);

// Fold each newly-touched day's punches straight into the real `attendance`
// table (first punch = time_in, last = time_out) right here, instead of a
// separate cron re-scanning zk_attendance on a timer - this endpoint already
// has the exact set of days that changed, so there's nothing to poll for.
// Never touches a day an admin already manually edited - see
// attendance_upsert_day() in includes/attendance_helpers.php.
$attendancePaired = 0;
foreach ($affectedDays as $day) {
    $pin = $day['pin'];
    $date = $day['date'];

    $empStmt = mysqli_prepare($conn, "SELECT emp_id, comp_no FROM employees WHERE emp_id = ? AND status = 1 LIMIT 1");
    mysqli_stmt_bind_param($empStmt, 's', $pin);
    mysqli_stmt_execute($empStmt);
    $emp = mysqli_stmt_get_result($empStmt)->fetch_assoc();
    mysqli_stmt_close($empStmt);
    if (!$emp) {
        continue;
    }

    $aggStmt = mysqli_prepare(
        $conn,
        "SELECT MIN(punch_datetime) AS first_punch, MAX(punch_datetime) AS last_punch, COUNT(*) AS punch_count
         FROM zk_attendance WHERE emp_id = ? AND punch_date = ?"
    );
    mysqli_stmt_bind_param($aggStmt, 'ss', $pin, $date);
    mysqli_stmt_execute($aggStmt);
    $agg = mysqli_stmt_get_result($aggStmt)->fetch_assoc();
    mysqli_stmt_close($aggStmt);
    if (!$agg || !$agg['first_punch']) {
        continue;
    }

    $timeIn = date('H:i', strtotime($agg['first_punch']));
    $count = (int) $agg['punch_count'];
    // A face-scan device can log several duplicate punches at the exact same
    // timestamp - guard on the punches actually differing, not just count>1,
    // so those don't get treated as a real check-out (time_in == time_out).
    $timeOut = ($count > 1 && $agg['last_punch'] !== $agg['first_punch']) ? date('H:i', strtotime($agg['last_punch'])) : '';
    $state = attendance_derive_state($conn, (int) $emp['emp_id'], $emp['comp_no'], $date, $timeIn, $timeOut);

    if (attendance_upsert_day($conn, (int) $emp['emp_id'], $date, $timeIn, $timeOut, $state, '', 'device')) {
        $attendancePaired++;
    }
}

echo json_encode([
    'status' => 'success',
    'inserted' => $insertedCount,
    'skipped_duplicate' => $skippedDuplicate,
    'skipped_invalid' => $skippedInvalid,
    'devices_synced' => $devicesSynced,
    'terminals_received' => count($terminals),
    'attendance_paired' => $attendancePaired,
    'inserted_punches' => $insertedPunches,
]);
