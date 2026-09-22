<?php
require_once __DIR__ . '/zk_db_connect.php';

// Shared helpers for the ZKTeco ADMS endpoints (iclock/*.php) and the
// device-offline cron - kept dependency-free of includes/db.php on purpose,
// see zk_db_connect.php for why.

if (!function_exists('zk_lookup_device')) {
    /** Whitelist check: only pre-registered serials (added via the Devices admin page) are accepted. */
    function zk_lookup_device($conn, $serial) {
        $stmt = mysqli_prepare($conn, "SELECT id, state FROM zk_devices WHERE serial_number = ? LIMIT 1");
        mysqli_stmt_bind_param($stmt, 's', $serial);
        mysqli_stmt_execute($stmt);
        $row = mysqli_stmt_get_result($stmt)->fetch_assoc();
        mysqli_stmt_close($stmt);
        return $row ?: null;
    }
}

if (!function_exists('zk_touch_device')) {
    /** Marks a device online + records its last-contact time; called on every accepted request (heartbeat). */
    function zk_touch_device($conn, $serial, $deviceIp = null) {
        $stmt = mysqli_prepare($conn, "UPDATE zk_devices SET state = 'online', last_activity = NOW(), device_ip = COALESCE(?, device_ip) WHERE serial_number = ?");
        mysqli_stmt_bind_param($stmt, 'ss', $deviceIp, $serial);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }
}

if (!function_exists('zk_bump_device_transaction_count')) {
    function zk_bump_device_transaction_count($conn, $serial, $by) {
        if ($by <= 0) {
            return;
        }
        $stmt = mysqli_prepare($conn, "UPDATE zk_devices SET transaction_qty = transaction_qty + ? WHERE serial_number = ?");
        mysqli_stmt_bind_param($stmt, 'is', $by, $serial);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }
}

if (!function_exists('zk_insert_live_attendance')) {
    /**
     * Resolves one deduplicated punch against `employees` and writes it into
     * the dedicated `zk_attendance` reporting table. Deliberately NOT the old
     * `attendance` table - that table's real schema (id, uid, emp_id, state,
     * date, time_in, time_out, type, note, created_at) has no
     * emp_name/punch_time/punch_state columns; the code that assumed those
     * (import_csv/uploadCsv.php, attendanceEmpAjaxfile.php) is legacy/unwired.
     * PIN = employees.emp_id (confirmed 1:1 with device enrollment numbers).
     * Punches from an unknown/inactive employee are silently skipped (deliberately not
     * written to error_log - it flooded the log), not fatal -
     * callers should keep re-attempting this same punch on later syncs (as long as
     * it's still within whatever window gets re-sent) rather than treating one
     * failed attempt as final: an employee added to HR *after* a device flushes
     * weeks of offline-cached punches would otherwise lose that history forever.
     * INSERT IGNORE + zk_attendance's uniq_emp_punch(emp_id, punch_datetime) key
     * (db_updates/add_zk_attendance_unique_key.sql) makes repeated attempts for an
     * already-recorded punch safe no-ops instead of duplicate rows.
     */
    function zk_insert_live_attendance($conn, $serial, $pin, $punchTime, $status) {
        $empStmt = mysqli_prepare($conn, "SELECT emp_id, name FROM employees WHERE emp_id = ? AND status = 1 LIMIT 1");
        mysqli_stmt_bind_param($empStmt, 's', $pin);
        mysqli_stmt_execute($empStmt);
        $emp = mysqli_stmt_get_result($empStmt)->fetch_assoc();
        mysqli_stmt_close($empStmt);

        if (!$emp) {
            return false;
        }

        $date = date('Y-m-d', strtotime($punchTime));
        $time = date('H:i:s', strtotime($punchTime));

        $insert = mysqli_prepare(
            $conn,
            "INSERT IGNORE INTO zk_attendance (emp_id, emp_name, serial_number, punch_datetime, punch_date, punch_time, punch_state) VALUES (?, ?, ?, ?, ?, ?, ?)"
        );
        mysqli_stmt_bind_param($insert, 'sssssss', $emp['emp_id'], $emp['name'], $serial, $punchTime, $date, $time, $status);
        mysqli_stmt_execute($insert);
        $inserted = mysqli_stmt_affected_rows($insert) > 0;
        mysqli_stmt_close($insert);
        return $inserted;
    }
}

if (!function_exists('zk_attendance_retention_days')) {
    /**
     * Attendance data older than this many calendar days (counting today as day 1) is
     * purged - see zk_purge_old_attendance(). Editable in App Settings > Attendance
     * Config > Data Retention (app_settings.attendance_retention_days). Falls back to
     * 60 if unset/invalid, and is clamped to a 7-day floor so a typo (0, blank) can
     * never wipe the attendance tables.
     */
    function zk_attendance_retention_days($conn = null) {
        $days = 60;
        if ($conn) {
            $result = mysqli_query($conn, "SELECT setting_value FROM app_settings WHERE setting_name = 'attendance_retention_days' LIMIT 1");
            if ($result) {
                $row = mysqli_fetch_assoc($result);
                mysqli_free_result($result);
                if ($row && ctype_digit(trim((string) $row['setting_value']))) {
                    $days = (int) trim((string) $row['setting_value']);
                }
            }
        }
        return max(7, $days);
    }
}

if (!function_exists('zk_attendance_cutoff_date')) {
    /** Oldest date (Y-m-d) still kept: today is day 1, so N days = today minus (N-1). */
    function zk_attendance_cutoff_date($conn = null) {
        return date('Y-m-d', strtotime('-' . (zk_attendance_retention_days($conn) - 1) . ' days'));
    }
}

if (!function_exists('zk_purge_old_attendance')) {
    /**
     * Deletes attendance data older than the retention window from the punch tables
     * (zk_attendance_raw, zk_attendance) and the daily `attendance` table, in
     * batches so a big first-time purge doesn't hold long locks. Runs at most once
     * per calendar day (guarded by cron_logs/last_attendance_retention_report.json,
     * same pattern as the cron_*.php scripts) unless $force is true.
     */
    function zk_purge_old_attendance($conn, $force = false) {
        $reportFile = __DIR__ . '/../cron_logs/last_attendance_retention_report.json';
        if (!$force && file_exists($reportFile)) {
            $last = json_decode((string) @file_get_contents($reportFile), true);
            if (is_array($last) && isset($last['timestamp']) && substr($last['timestamp'], 0, 10) === date('Y-m-d')) {
                return null;
            }
        }

        $cutoff = zk_attendance_cutoff_date($conn);
        $targets = [
            'zk_attendance_raw' => 'punch_time',
            'zk_attendance' => 'punch_date',
            'attendance' => 'date',
        ];

        $deleted = [];
        foreach ($targets as $table => $column) {
            $deleted[$table] = 0;
            do {
                $ok = mysqli_query($conn, "DELETE FROM `{$table}` WHERE `{$column}` < '{$cutoff}' LIMIT 5000");
                $affected = $ok ? mysqli_affected_rows($conn) : 0;
                $deleted[$table] += $affected;
            } while ($affected > 0);
        }

        @file_put_contents($reportFile, json_encode([
            'timestamp' => date('Y-m-d H:i:s'),
            'cutoff_date' => $cutoff,
            'deleted' => $deleted,
        ], JSON_PRETTY_PRINT));

        return $deleted;
    }
}

if (!function_exists('zk_ensure_offline_threshold_setting')) {
    function zk_ensure_offline_threshold_setting($conn) {
        $stmt = mysqli_prepare(
            $conn,
            "INSERT IGNORE INTO app_settings (setting_name, setting_value, setting_group, description, input_type) VALUES ('device_offline_threshold_minutes', '3', 'device_monitor', 'Minutes of silence before a ZK device is marked offline', 'text')"
        );
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }
}

if (!function_exists('zk_ensure_sync_allowed_ip_setting')) {
    function zk_ensure_sync_allowed_ip_setting($conn) {
        $stmt = mysqli_prepare(
            $conn,
            "INSERT IGNORE INTO app_settings (setting_name, setting_value, setting_group, description, input_type) VALUES ('zk_sync_allowed_ip', '', 'sync_settings', 'Local BioTime-server IP address allowed to push attendance via zk_sync_import.php. Leave blank to allow any IP (the shared secret is still required either way).', 'text')"
        );
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }
}

if (!function_exists('zk_get_sync_allowed_ip')) {
    /**
     * Optional IP allowlist for zk_sync_import.php, set from App Settings ->
     * Attendance Config -> Sync Settings. Blank (the default) means no IP
     * restriction - only the shared secret (zk_get_sync_secret()) gates the
     * endpoint, same as before this setting existed.
     */
    function zk_get_sync_allowed_ip($conn) {
        zk_ensure_sync_allowed_ip_setting($conn);
        $result = mysqli_query($conn, "SELECT setting_value FROM app_settings WHERE setting_name = 'zk_sync_allowed_ip' LIMIT 1");
        $row = $result ? mysqli_fetch_assoc($result) : null;
        return trim((string) ($row['setting_value'] ?? ''));
    }
}

if (!function_exists('zk_sync_terminal_state')) {
    /**
     * Updates one zk_devices row from BioTime's own iclock_terminal snapshot
     * (schema confirmed directly against the real live database - see
     * zk_local_agent/zk_biotime_export.ps1's terminal query).
     *
     * iclock_terminal.state is NOT trusted for online/offline - confirmed
     * stale in practice (stays 'online' after a device silently drops off
     * the network; only flips on a graceful disconnect). BioTime's own UI
     * computes Online/Offline from last_activity recency, not that raw
     * column, so we do the same here: online only if last_activity is within
     * the configured offline threshold (device_offline_threshold_minutes),
     * same threshold cron_zk_device_offline_check.php uses. Only updates
     * devices already registered in zk_devices (whitelist) - unknown serials
     * are ignored.
     */
    function zk_sync_terminal_state($conn, array $terminal)
    {
        $serial = trim((string) ($terminal['sn'] ?? ''));
        if ($serial === '') {
            return false;
        }

        $deviceIp = trim((string) ($terminal['ip_address'] ?? '')) ?: null;
        $lastActivityRaw = trim((string) ($terminal['last_activity'] ?? ''));
        $lastActivityTs = $lastActivityRaw !== '' ? strtotime($lastActivityRaw) : false;
        $lastActivity = $lastActivityTs !== false ? date('Y-m-d H:i:s', $lastActivityTs) : null;

        $thresholdMinutes = zk_get_offline_threshold_minutes($conn);
        $state = ($lastActivityTs !== false && $lastActivityTs >= (time() - $thresholdMinutes * 60))
            ? 'online'
            : 'offline';
        $userQty = (int) ($terminal['user_count'] ?? 0);
        $transactionQty = (int) ($terminal['transaction_count'] ?? 0);
        $fpQty = (int) ($terminal['fp_count'] ?? 0);
        $faceQty = (int) ($terminal['face_count'] ?? 0);
        $palmQty = (int) ($terminal['palm_count'] ?? 0);

        $stmt = mysqli_prepare(
            $conn,
            "UPDATE zk_devices SET
                state = ?,
                last_activity = COALESCE(?, last_activity),
                device_ip = COALESCE(?, device_ip),
                user_qty = ?,
                transaction_qty = ?,
                fp_qty = ?,
                face_qty = ?,
                palm_qty = ?
             WHERE serial_number = ?"
        );
        mysqli_stmt_bind_param(
            $stmt,
            'sssiiiiis',
            $state,
            $lastActivity,
            $deviceIp,
            $userQty,
            $transactionQty,
            $fpQty,
            $faceQty,
            $palmQty,
            $serial
        );
        mysqli_stmt_execute($stmt);
        $matched = mysqli_stmt_affected_rows($stmt) > 0;
        mysqli_stmt_close($stmt);

        return $matched;
    }
}

if (!function_exists('zk_get_sync_secret')) {
    /**
     * Shared secret for zk_sync_import.php, checked by the local BioTime-server
     * export script when it pushes punches over HTTPS. Self-healed on first use
     * (same pattern as tools/db_backup.php's db_backup_secret_key) - stable
     * across runs so the local script's saved key keeps working.
     */
    function zk_get_sync_secret($conn) {
        $result = mysqli_query($conn, "SELECT setting_value FROM app_settings WHERE setting_name = 'zk_sync_secret_key'");
        $row = $result ? mysqli_fetch_assoc($result) : null;
        $key = trim((string) ($row['setting_value'] ?? ''));

        if ($key === '') {
            $key = bin2hex(random_bytes(32));
            $stmt = mysqli_prepare(
                $conn,
                "INSERT INTO app_settings (setting_name, setting_value, setting_group, description, input_type) VALUES ('zk_sync_secret_key', ?, 'device_monitor', 'Shared secret the local BioTime-server export script sends to zk_sync_import.php', 'text')
                 ON DUPLICATE KEY UPDATE setting_value = IF(setting_value = '' OR setting_value IS NULL, VALUES(setting_value), setting_value)"
            );
            mysqli_stmt_bind_param($stmt, 's', $key);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
        }

        return $key;
    }
}

if (!function_exists('zk_get_offline_threshold_minutes')) {
    function zk_get_offline_threshold_minutes($conn) {
        zk_ensure_offline_threshold_setting($conn);
        $result = mysqli_query($conn, "SELECT setting_value FROM app_settings WHERE setting_name = 'device_offline_threshold_minutes' LIMIT 1");
        $row = $result ? mysqli_fetch_assoc($result) : null;
        $minutes = (int) ($row['setting_value'] ?? 3);
        return $minutes > 0 ? $minutes : 3;
    }
}
