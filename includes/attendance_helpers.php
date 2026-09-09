<?php

if (defined('ATTENDANCE_HELPERS_INCLUDED')) {
    return;
}
define('ATTENDANCE_HELPERS_INCLUDED', true);

if (!function_exists('attendance_next_uid')) {
    /**
     * `attendance.uid` has a UNIQUE NOT NULL constraint left over from its
     * original meaning (a device's internal log id), which no longer applies
     * now that rows are aggregated per employee per day. Callers (the pairing
     * cron and the manual-entry ajax handler) just need a value guaranteed
     * distinct from every existing row. Cron runs are sequential and manual
     * adds are infrequent single admin actions, so a simple MAX+1 is safe
     * without extra locking.
     */
    function attendance_next_uid($conn) {
        $result = mysqli_query($conn, "SELECT COALESCE(MAX(uid), 0) + 1 AS next_uid FROM attendance");
        $row = $result ? mysqli_fetch_assoc($result) : null;
        return (int) ($row['next_uid'] ?? 1);
    }
}

if (!function_exists('attendance_upsert_day')) {
    /**
     * One row per (emp_id, date) - enforced by the uq_emp_date unique key.
     * Device-sourced upserts must not clobber a row an admin already
     * hand-edited, so time_in/time_out/state/source only get overwritten
     * when the existing row isn't already 'manual'. A manual upsert
     * ($source = 'manual') always wins - an admin's own edit takes priority
     * regardless of what's there.
     *
     * A manual "punch type" add sends only one of time_in/time_out (the
     * other arrives as '') - e.g. someone already checked in and an admin
     * is only filling in the forgotten check-out. An empty incoming value
     * must NOT blank out an existing punch, so time_in/time_out only
     * overwrite when the incoming value is non-empty; the same-type case
     * (re-adding a check-in) still replaces it since the incoming value
     * is then non-empty too.
     */
    function attendance_upsert_day($conn, $empId, $date, $timeIn, $timeOut, $state, $note, $source) {
        $empId = (int) $empId;
        if ($empId <= 0 || $date === '') {
            return false;
        }

        $uid = attendance_next_uid($conn);

        if ($source === 'manual') {
            $stmt = mysqli_prepare(
                $conn,
                "INSERT INTO attendance (uid, emp_id, state, date, time_in, time_out, type, note, source)
                 VALUES (?, ?, ?, ?, ?, ?, '', ?, 'manual')
                 ON DUPLICATE KEY UPDATE
                    state = VALUES(state),
                    time_in = IF(VALUES(time_in) = '', time_in, VALUES(time_in)),
                    time_out = IF(VALUES(time_out) = '', time_out, VALUES(time_out)),
                    note = VALUES(note),
                    source = 'manual'"
            );
            mysqli_stmt_bind_param($stmt, 'iisssss', $uid, $empId, $state, $date, $timeIn, $timeOut, $note);
        } else {
            $stmt = mysqli_prepare(
                $conn,
                "INSERT INTO attendance (uid, emp_id, state, date, time_in, time_out, type, note, source)
                 VALUES (?, ?, ?, ?, ?, ?, '', ?, 'device')
                 ON DUPLICATE KEY UPDATE
                    state = IF(source = 'manual', state, VALUES(state)),
                    time_in = IF(source = 'manual', time_in, VALUES(time_in)),
                    time_out = IF(source = 'manual', time_out, VALUES(time_out)),
                    note = IF(source = 'manual', note, VALUES(note))"
            );
            mysqli_stmt_bind_param($stmt, 'iisssss', $uid, $empId, $state, $date, $timeIn, $timeOut, $note);
        }

        $ok = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        return $ok;
    }
}

if (!function_exists('attendance_resolve_timetable')) {
    /**
     * Resolves the specific weekday's schedule (a timetable_days row) that
     * governs an employee on a given date - schedules are per-weekday now
     * (db_updates/add_timetable_per_weekday_schedule.sql), since some
     * companies run different hours on different days (e.g. a short
     * Thursday, Friday off, normal Sat-Wed) rather than one fixed
     * check-in/check-out for every working day.
     * Checks a per-employee override first (timetable_employees +
     * timetables.is_temporary) - lets specific employees be pulled out of
     * their company's schedule onto their own, either permanently
     * (start_date/end_date both NULL) or for a limited window (both set,
     * active only while $date falls inside them). Falls back to the
     * employee's company's timetable otherwise.
     * `employees.comp_no` joins to `companies.comp_id` (NOT `companies.id` -
     * an established convention elsewhere in this codebase, e.g.
     * includes/helper_functions.php's company-filter joins). Every company
     * has a `timetable_id` (defaults to 1 "Default") - falls back to a
     * hardcoded array (Fri+Sat off) if even that day's row is somehow
     * missing, so this never fatals. Both the employee override and the
     * company timetable are only honored while `timetables.is_active = 1`
     * (see db_updates/add_timetable_is_active.sql) - an inactive/draft
     * timetable is skipped as if the employee/company weren't assigned to
     * it, falling through to Default. The Default timetable (id 1) is
     * always active and can't be deactivated.
     */
    function attendance_resolve_timetable($conn, $empId, $compNo, $date) {
        $empId = (int) $empId;
        $date = (string) $date;
        $weekday = $date !== '' ? (int) date('N', strtotime($date)) : 1;

        $timetableId = null;
        if ($empId > 0 && $date !== '') {
            $stmt = mysqli_prepare(
                $conn,
                "SELECT t.id FROM timetable_employees te
                 JOIN timetables t ON t.id = te.timetable_id
                 WHERE te.emp_id = ? AND t.is_temporary = 1 AND t.is_active = 1
                   AND (
                     (t.start_date IS NULL AND t.end_date IS NULL)
                     OR (t.start_date IS NOT NULL AND t.end_date IS NOT NULL AND ? BETWEEN t.start_date AND t.end_date)
                   )
                 ORDER BY (t.start_date IS NULL) ASC, t.start_date DESC LIMIT 1"
            );
            mysqli_stmt_bind_param($stmt, 'is', $empId, $date);
            mysqli_stmt_execute($stmt);
            $override = mysqli_stmt_get_result($stmt)->fetch_assoc();
            mysqli_stmt_close($stmt);
            if ($override) {
                $timetableId = (int) $override['id'];
            }
        }

        if ($timetableId === null) {
            $timetableId = 1;
            $compNo = trim((string) $compNo);
            if ($compNo !== '') {
                // Only an *active* company timetable takes effect - a draft
                // timetable a company is assigned to but hasn't been
                // activated yet must not silently change what's expected of
                // its employees, so it falls back to Default until switched on.
                $stmt = mysqli_prepare(
                    $conn,
                    "SELECT c.timetable_id FROM companies c
                     JOIN timetables t ON t.id = c.timetable_id
                     WHERE c.comp_id = ? AND t.is_active = 1 LIMIT 1"
                );
                mysqli_stmt_bind_param($stmt, 's', $compNo);
                mysqli_stmt_execute($stmt);
                $row = mysqli_stmt_get_result($stmt)->fetch_assoc();
                mysqli_stmt_close($stmt);
                if ($row && (int) $row['timetable_id'] > 0) {
                    $timetableId = (int) $row['timetable_id'];
                }
            }
        }

        $stmt2 = mysqli_prepare($conn, "SELECT * FROM timetable_days WHERE timetable_id = ? AND weekday = ? LIMIT 1");
        mysqli_stmt_bind_param($stmt2, 'ii', $timetableId, $weekday);
        mysqli_stmt_execute($stmt2);
        $day = mysqli_stmt_get_result($stmt2)->fetch_assoc();
        mysqli_stmt_close($stmt2);

        if ($day) {
            return $day;
        }

        return [
            'is_off' => in_array($weekday, [5, 6], true) ? 1 : 0,
            'check_in' => '08:00', 'check_in_start' => '07:45', 'check_in_end' => '08:15',
            'check_out' => '17:00', 'check_out_start' => '16:45', 'check_out_end' => '17:15',
            'standard_hours' => 8,
        ];
    }
}

if (!function_exists('attendance_derive_state')) {
    /**
     * Flags a day's pairing against the employee's resolved weekday schedule
     * (temporary override if active, else their company's timetable - see
     * attendance_resolve_timetable()). $timeIn/$timeOut are 'H:i' strings
     * ($timeOut may be '' - no checkout yet). Plain string comparison is
     * correct here since both the schedule and the computed times are always
     * zero-padded 24-hour 'H:i'. If the resolved day is flagged is_off,
     * returns 'Day Off' immediately - no Late/Early-Leave check on a day the
     * employee isn't expected in. Otherwise flags combine (e.g. 'Late &
     * Early Leave', 'Late & Incomplete') since attendance.state is free text
     * with no enum constraint.
     */
    function attendance_derive_state($conn, $empId, $compNo, $date, $timeIn, $timeOut) {
        $day = attendance_resolve_timetable($conn, $empId, $compNo, $date);

        if (!empty($day['is_off'])) {
            return 'Day Off';
        }

        $flags = [];
        if ($timeIn !== '' && $timeIn > $day['check_in_end']) {
            $flags[] = 'Late';
        }
        if ($timeOut === '') {
            $flags[] = 'Incomplete';
        } elseif ($timeOut < $day['check_out_start']) {
            $flags[] = 'Early Leave';
        }

        return $flags ? implode(' & ', $flags) : 'Present';
    }
}

if (!function_exists('attendance_recalculate_states')) {
    /**
     * Re-derives and updates `attendance.state` for every device-sourced row
     * belonging to the given employees. Needed because state is computed
     * once and stored at pairing time (zk_sync_import.php / the manual
     * backfill cron) - editing a timetable afterward doesn't retroactively
     * touch rows already written, so without this a timetable edit looks
     * like it "did nothing" on the attendance pages. Never touches
     * source='manual' rows (an admin's hand-edit stays as-is). Returns the
     * number of rows whose state actually changed.
     */
    function attendance_recalculate_states($conn, array $empIds) {
        $empIds = array_values(array_unique(array_filter(array_map('intval', $empIds), function ($id) {
            return $id > 0;
        })));
        if (empty($empIds)) {
            return 0;
        }

        $placeholders = implode(',', array_fill(0, count($empIds), '?'));
        $types = str_repeat('i', count($empIds));
        $stmt = mysqli_prepare(
            $conn,
            "SELECT a.id, a.emp_id, a.state, DATE(a.date) AS date, a.time_in, a.time_out, e.comp_no
             FROM attendance a
             JOIN employees e ON e.emp_id = a.emp_id
             WHERE a.source = 'device' AND a.emp_id IN ({$placeholders})"
        );
        mysqli_stmt_bind_param($stmt, $types, ...$empIds);
        mysqli_stmt_execute($stmt);
        $rows = mysqli_stmt_get_result($stmt)->fetch_all(MYSQLI_ASSOC);
        mysqli_stmt_close($stmt);

        $updateStmt = mysqli_prepare($conn, "UPDATE attendance SET state = ? WHERE id = ?");
        $updated = 0;
        foreach ($rows as $row) {
            $newState = attendance_derive_state(
                $conn, (int) $row['emp_id'], $row['comp_no'], $row['date'],
                (string) $row['time_in'], (string) $row['time_out']
            );
            if ($newState !== $row['state']) {
                $rowId = (int) $row['id'];
                mysqli_stmt_bind_param($updateStmt, 'si', $newState, $rowId);
                mysqli_stmt_execute($updateStmt);
                $updated++;
            }
        }
        mysqli_stmt_close($updateStmt);

        return $updated;
    }
}
