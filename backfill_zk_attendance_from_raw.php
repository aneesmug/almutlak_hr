<?php
/**
 * One-time repair tool for a real data-loss bug: zk_sync_import.php and
 * iclock/cdata.php used to only ever attempt zk_insert_live_attendance() once
 * per punch - the exact moment its row was first inserted into
 * zk_attendance_raw. A punch for an employee not yet active/added in
 * `employees` at that moment was logged "unknown/inactive employee ... skipped"
 * and permanently lost, since the raw row's dedup key meant it could never be
 * retried (fixed going forward - see zk_helpers.php's zk_insert_live_attendance()
 * and the two callers in zk_sync_import.php / iclock/cdata.php).
 *
 * This scans every row already sitting in zk_attendance_raw and retries the
 * insert into zk_attendance against the CURRENT employees table - recovering
 * punches for anyone who was inactive/missing back when their device backlog
 * first synced but is active now. Safe to run repeatedly (INSERT IGNORE
 * against zk_attendance's uniq_emp_punch key).
 *
 * After this, run `php cron_zk_attendance_pairing.php` to fold any
 * newly-recovered zk_attendance rows into the real `attendance` table.
 *
 * Manual run:
 *   php backfill_zk_attendance_from_raw.php
 */
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/zk_helpers.php';

@set_time_limit(0);

if (!$conDB) {
    fwrite(STDERR, "[ZK] Could not connect to database.\n");
    exit(1);
}

$result = mysqli_query(
    $conDB,
    "SELECT serial_number, pin, punch_time, status FROM zk_attendance_raw ORDER BY punch_time ASC"
);
if (!$result) {
    fwrite(STDERR, "[ZK] Failed to read zk_attendance_raw: " . mysqli_error($conDB) . "\n");
    exit(1);
}

$total = 0;
$recovered = 0;
$stillUnmatched = 0;

while ($row = mysqli_fetch_assoc($result)) {
    $total++;
    $punchTime = date('Y-m-d H:i:s', strtotime($row['punch_time']));
    if (zk_insert_live_attendance($conDB, $row['serial_number'], $row['pin'], $punchTime, $row['status'])) {
        $recovered++;
    }
}

// zk_insert_live_attendance() logs its own "unknown/inactive employee" line
// per still-unmatched punch (same as before) - not duplicated here.

echo date('Y-m-d H:i:s') . " - Scanned: {$total} - Newly recovered into zk_attendance: {$recovered}\n";
echo "Now run: php cron_zk_attendance_pairing.php\n";
