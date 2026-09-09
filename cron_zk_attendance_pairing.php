<?php
/**
 * NOT required for normal operation - zk_sync_import.php now folds every
 * batch's punches into `attendance` directly as they arrive (see the "Fold
 * each newly-touched day's punches..." block there), so this script isn't
 * on a schedule. Kept as a manual backfill/repair tool: run it by hand after
 * importing old punches directly into zk_attendance, or if `attendance` ever
 * needs to be re-derived from scratch for a rolling 2-day window.
 *
 * Turns raw ZK device punches (zk_attendance, one row per punch) into daily
 * check-in/check-out rows in the real `attendance` table that reports.php
 * and the employee profile page read. First punch of the day = time_in,
 * last punch = time_out (if there's more than one *distinct* punch time -
 * a face-scan device retrying at the same timestamp doesn't count); a
 * single punch (or several duplicates of the same one) that day is marked
 * 'Incomplete' with no time_out (missing checkout).
 * Never touches a day an admin already manually edited - see
 * attendance_upsert_day() in includes/attendance_helpers.php.
 *
 * Manual run:
 *   php cron_zk_attendance_pairing.php
 */
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/attendance_helpers.php';

// db.php sets max_execution_time = 25 (meant to bound web requests) - this
// backfill deliberately runs long over large datasets when re-deriving
// `attendance` from scratch, so lift the cap for this manual CLI tool only.
@set_time_limit(0);

if (!$conDB) {
    fwrite(STDERR, "[ZK] Could not connect to database.\n");
    exit(1);
}

// No date restriction here on purpose - as a manual backfill tool this should
// re-derive `attendance` from every punch on record, not just a recent
// window (that windowing only made sense back when this ran as a frequent
// scheduled cron). Pass a day count via argv to limit it, e.g.
// `php cron_zk_attendance_pairing.php 2` for just the last 2 days.
$windowDays = isset($argv[1]) ? (int) $argv[1] : null;
$dateFilter = $windowDays ? "WHERE punch_date >= (CURDATE() - INTERVAL {$windowDays} DAY)" : '';

$groups = [];
$result = mysqli_query(
    $conDB,
    "SELECT emp_id, punch_date, MIN(punch_datetime) AS first_punch, MAX(punch_datetime) AS last_punch, COUNT(*) AS punch_count
     FROM zk_attendance
     {$dateFilter}
     GROUP BY emp_id, punch_date"
);
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $groups[] = $row;
    }
}

$paired = 0;
$skippedUnknown = 0;

foreach ($groups as $group) {
    $pin = trim((string) $group['emp_id']);

    $empStmt = mysqli_prepare($conDB, "SELECT emp_id, comp_no FROM employees WHERE emp_id = ? AND status = 1 LIMIT 1");
    mysqli_stmt_bind_param($empStmt, 's', $pin);
    mysqli_stmt_execute($empStmt);
    $emp = mysqli_stmt_get_result($empStmt)->fetch_assoc();
    mysqli_stmt_close($empStmt);

    if (!$emp) {
        $skippedUnknown++;
        continue;
    }

    $date = date('Y-m-d', strtotime($group['punch_date']));
    $timeIn = date('H:i', strtotime($group['first_punch']));
    $count = (int) $group['punch_count'];
    // A face-scan device can log several duplicate punches at the exact same
    // timestamp - guard on the punches actually differing, not just count>1,
    // so those don't get treated as a real check-out (time_in == time_out).
    $timeOut = ($count > 1 && $group['last_punch'] !== $group['first_punch']) ? date('H:i', strtotime($group['last_punch'])) : '';
    $state = attendance_derive_state($conDB, (int) $emp['emp_id'], $emp['comp_no'], $date, $timeIn, $timeOut);

    if (attendance_upsert_day($conDB, (int) $emp['emp_id'], $date, $timeIn, $timeOut, $state, '', 'device')) {
        $paired++;
    }
}

echo date('Y-m-d H:i:s') . " - Groups: " . count($groups) . " - Paired: {$paired} - Skipped (unknown/inactive emp): {$skippedUnknown}\n";
