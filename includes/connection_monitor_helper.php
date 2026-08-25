<?php
if (!function_exists('connmon_fmt_duration')) {
    function connmon_fmt_duration($seconds) {
        $seconds = (int) $seconds;
        $h = intdiv($seconds, 3600);
        $m = intdiv($seconds % 3600, 60);
        if ($h > 0) {
            return "{$h}h {$m}m";
        }
        return "{$m}m";
    }
}

if (!function_exists('connmon_status')) {
    function connmon_status($current, $max) {
        $max = (int) $max;
        $current = (int) $current;
        $pct = $max > 0 ? min(100, round($current / $max * 100)) : 0;

        if ($max <= 0) {
            return ['level' => 'unknown', 'label' => 'Unknown', 'color' => '#94a3b8', 'bg' => 'rgba(148,163,184,.15)', 'pct' => 0];
        }
        if ($pct < 50) {
            return ['level' => 'ok', 'label' => 'Healthy', 'color' => '#22c55e', 'bg' => 'rgba(34,197,94,.15)', 'pct' => $pct];
        }
        if ($pct < 80) {
            return ['level' => 'warn', 'label' => 'Elevated', 'color' => '#f59e0b', 'bg' => 'rgba(245,158,11,.15)', 'pct' => $pct];
        }
        return ['level' => 'critical', 'label' => 'Critical', 'color' => '#f87171', 'bg' => 'rgba(248,113,113,.15)', 'pct' => $pct];
    }
}

// SHOW FULL PROCESSLIST is the ground truth for what MySQL itself has open -
// our own file-based tracker only sees requests that passed through this
// app's db.php, not sleeping/idle connections or anything else on the account.
if (!function_exists('connmon_processlist')) {
    function connmon_processlist($conDB) {
        $rows = [];
        $res = @mysqli_query($conDB, "SHOW FULL PROCESSLIST");
        if ($res) {
            while ($r = mysqli_fetch_assoc($res)) {
                $info = $r['Info'] ?? '';
                if ($info !== null && mb_strlen($info) > 140) {
                    $info = mb_substr($info, 0, 140) . '…';
                }
                $rows[] = [
                    'id'      => $r['Id'] ?? '',
                    'user'    => $r['User'] ?? '',
                    'host'    => $r['Host'] ?? '',
                    'db'      => $r['db'] ?? '',
                    'command' => $r['Command'] ?? '',
                    'time'    => (int) ($r['Time'] ?? 0),
                    'state'   => $r['State'] ?? '',
                    'info'    => (string) $info,
                ];
            }
            usort($rows, fn($a, $b) => $b['time'] <=> $a['time']);
        }
        return $rows;
    }
}

// Real "who is logged in right now" list, from the app's own activity log
// (user_activity_logger.php) rather than the instant-lived request slots -
// a request finishes in well under a second, so the slot table is nearly
// always empty even while dozens of people sit on open pages.
if (!function_exists('connmon_active_users')) {
    function connmon_active_users($conDB, $limit = 200) {
        // Sweep first so the list reflects reality even if no other request
        // has triggered sweepStaleUserActivity() recently. Same settings
        // used in session_check.php.
        if (function_exists('sweepStaleUserActivity')) {
            $autoSignoutHours = function_exists('get_setting') ? (float) get_setting($conDB, 'auto_signout_hours') : 0;
            $autoSignoutSeconds = $autoSignoutHours > 0 ? (int) round($autoSignoutHours * 3600) : 28800;
            $autoSignoutEmployeeHours = function_exists('get_setting') ? (float) get_setting($conDB, 'auto_signout_hours_employee') : 0;
            $autoSignoutEmployeeSeconds = $autoSignoutEmployeeHours > 0 ? (int) round($autoSignoutEmployeeHours * 3600) : $autoSignoutSeconds;
            sweepStaleUserActivity($conDB, $autoSignoutSeconds, $autoSignoutEmployeeSeconds);
        }

        $sql = "SELECT ua.id AS activity_id, ua.emp_id, ua.username AS login_id, al.fullname, e.name AS employee_name,
                       ua.ip_address, ua.login_time, ua.current_page, ua.browser, ua.browser_version, ua.os, ua.device_type,
                       TIMESTAMPDIFF(SECOND, ua.login_time, NOW()) AS session_seconds
                FROM user_activity_log ua
                LEFT JOIN admin_login al ON al.emp_id = ua.emp_id
                LEFT JOIN employees e ON e.emp_id = ua.emp_id
                WHERE ua.status = 'active'
                ORDER BY ua.login_time DESC
                LIMIT " . (int) $limit;

        $rows = [];
        $res = @mysqli_query($conDB, $sql);
        if ($res) {
            while ($r = mysqli_fetch_assoc($res)) {
                $rows[] = [
                    'activity_id' => $r['activity_id'] ?? '',
                    'emp_id'      => $r['emp_id'] ?? '',
                    'name'        => $r['employee_name'] ?: ($r['fullname'] ?: ($r['login_id'] ?? '')),
                    'ip'          => $r['ip_address'] ?? '',
                    'page'        => $r['current_page'] ?: '-',
                    'device'      => trim(($r['os'] ?? '') . ' · ' . ($r['browser'] ?? '')),
                    'since'       => $r['login_time'] ?? '',
                    'duration'    => (int) ($r['session_seconds'] ?? 0),
                ];
            }
        }
        return $rows;
    }
}

if (!function_exists('connmon_snapshot')) {
    function connmon_snapshot($conDB) {
        $statusRes = mysqli_query($conDB, "SHOW STATUS LIKE 'Threads_connected'");
        $threadsConnected = $statusRes ? (int) (mysqli_fetch_assoc($statusRes)['Value'] ?? 0) : 0;

        $maxRes = mysqli_query($conDB, "SHOW VARIABLES LIKE 'max_connections'");
        $maxConnections = $maxRes ? (int) (mysqli_fetch_assoc($maxRes)['Value'] ?? 0) : 0;

        $globalFile = ALMUTLAK_CONNLIMIT_DIR . '/' . md5('global') . '.json';
        $entries = [];
        if (is_file($globalFile)) {
            $raw = @file_get_contents($globalFile);
            $now = time();
            foreach (preg_split('/\r?\n/', (string) $raw, -1, PREG_SPLIT_NO_EMPTY) as $line) {
                $entry = json_decode($line, true);
                if (is_array($entry) && ($entry['ts'] ?? 0) > $now - 30) {
                    $entry['age'] = $now - $entry['ts'];
                    $entries[] = $entry;
                }
            }
            usort($entries, fn($a, $b) => $b['age'] <=> $a['age']);
        }

        // Entries only carry the login id + name captured from the requesting
        // page's own session. Resolve the real emp_id here, once per monitor
        // refresh, instead of querying on every request in db.php's hot path.
        $loginIds = array_values(array_unique(array_filter(array_column($entries, 'login_id'))));
        if ($loginIds) {
            $escaped = array_map(fn($id) => "'" . mysqli_real_escape_string($conDB, $id) . "'", $loginIds);
            $empRes = mysqli_query($conDB, "SELECT id_iqama, emp_id, fullname FROM admin_login WHERE id_iqama IN (" . implode(',', $escaped) . ")");
            $empMap = [];
            if ($empRes) {
                while ($row = mysqli_fetch_assoc($empRes)) {
                    $empMap[$row['id_iqama']] = ['emp_id' => $row['emp_id'], 'fullname' => $row['fullname']];
                }
            }
            foreach ($entries as &$entry) {
                $match = $empMap[$entry['login_id'] ?? ''] ?? null;
                $entry['emp_id'] = $match['emp_id'] ?? '';
                if ($match) {
                    $entry['user_name'] = $match['fullname'];
                }
            }
            unset($entry);
        }

        $processlist = connmon_processlist($conDB);
        $activeUsers = connmon_active_users($conDB);
        $status = connmon_status($threadsConnected, $maxConnections);

        return [
            'threads_connected' => $threadsConnected,
            'max_connections'   => $maxConnections,
            'status'            => $status,
            'app_tracked'       => count($entries),
            'entries'           => $entries,
            'processlist'       => $processlist,
            'active_users'      => $activeUsers,
            'active_users_count'=> count($activeUsers),
            'server_time'       => date('H:i:s'),
        ];
    }
}
