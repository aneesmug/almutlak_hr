<?php
/**
 * IP security (App Settings > Integrations > "IP Security").
 *
 * 1. Auto-block (default on): an IP with IP_GUARD fails / IDs within the window (failed sign-in, OTP,
 *    Google Authenticator, password reset, registration - see login_sec_record()) is blocked.
 *    Never auto-blocked: trusted IPs and IPs with a successful sign-in in the last 30 days
 *    (a shared office IP stays safe even if someone mistypes a password many times).
 * 2. Blocked IPs are refused on every page, the login page included, by ip_guard_enforce() which
 *    db.php runs before the database connects (reads the cache file cache/ip_blocklist.json).
 * 3. Trusted IPs: never auto-blocked. Optional "office only" mode (ip_access_enabled) lets only
 *    trusted IPs sign in (scope 'staff' = all accounts except user_type 'employee', or 'all').
 *
 * Rules live in the table ip_rules (rule 'block' | 'trust'); settings in app_settings (group ip_access).
 * Locked out? Delete the row from ip_rules and the file cache/ip_blocklist.json
 * (office-only mode: set ip_access_enabled to 0 in app_settings).
 */

require_once __DIR__ . '/cloudflare_ip.php'; // cf_ip_in_cidr() + the real visitor IP

if (!defined('IP_GUARD_CACHE_FILE')) {
    define('IP_GUARD_CACHE_FILE', dirname(__DIR__) . '/cache/ip_blocklist.json');
    define('IP_GUARD_WINDOW', 900);          // seconds counted for auto-block (15 minutes)
    define('IP_GUARD_KNOWN_DAYS', 30);       // a successful sign-in within this many days = known IP
    define('IP_ACCESS_BLOCKED_KEEP', 15);    // "office only" refusals kept for the tab
}

if (!function_exists('ip_rule_matches')) {
    /** __() when translations are loaded (not yet during the early db.php check). */
    function ipg_t(string $key, string $default): string
    {
        return function_exists('__') ? (string) __($key, $default) : $default;
    }

    /** $entry: single IP or CIDR range. */
    function ip_rule_matches(string $ip, string $entry): bool
    {
        if ($ip === '' || $entry === '') {
            return false;
        }
        return strpos($entry, '/') !== false ? cf_ip_in_cidr($ip, $entry) : @inet_pton($entry) === @inet_pton($ip);
    }

    /** Normalised entry ('1.2.3.4', '1.2.3.0/24', '2001:db8::/32') or null when it is not a valid IP / range. */
    function ip_access_normalise_entry(string $entry): ?string
    {
        $entry = trim($entry);
        if (strpos($entry, '/') === false) {
            return filter_var($entry, FILTER_VALIDATE_IP) ? $entry : null;
        }
        [$ip, $bits] = explode('/', $entry, 2);
        if (!filter_var($ip, FILTER_VALIDATE_IP) || !ctype_digit($bits)) {
            return null;
        }
        $max = filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) ? 32 : 128;
        return ((int) $bits >= 1 && (int) $bits <= $max) ? $ip . '/' . (int) $bits : null;
    }

    // ---------------------------------------------------------------- blocked IPs: early check (no DB)

    /** Runs from db.php before anything else: a blocked IP gets a 403 and the request stops. */
    function ip_guard_enforce(): void
    {
        if (PHP_SAPI === 'cli' || !is_file(IP_GUARD_CACHE_FILE)) {
            return;
        }
        $blocked = json_decode((string) @file_get_contents(IP_GUARD_CACHE_FILE), true);
        $ip = (string) ($_SERVER['REMOTE_ADDR'] ?? '');
        if (!is_array($blocked) || $ip === '') {
            return;
        }
        foreach ($blocked as $entry) {
            if (ip_rule_matches($ip, (string) $entry)) {
                ip_guard_block_response($ip);
            }
        }
    }

    function ip_guard_block_response(string $ip): void
    {
        http_response_code(403);
        $isJson = stripos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') !== false
            || strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'xmlhttprequest'
            || strpos($_SERVER['PHP_SELF'] ?? '', '/ajaxFile/') !== false;
        if ($isJson) {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'status' => 'error', 'message' => 'Access from your network (' . $ip . ') has been blocked. Please contact IT support.']);
            exit();
        }
        $ipHtml = htmlspecialchars($ip, ENT_QUOTES, 'UTF-8');
        header('Content-Type: text/html; charset=UTF-8');
        echo '<!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Access blocked</title>'
            . '<style>body{margin:0;min-height:100vh;display:flex;align-items:center;justify-content:center;font-family:system-ui,Segoe UI,Arial,sans-serif;background:#f1f5f9;color:#0f172a}'
            . '.b{max-width:460px;margin:16px;padding:28px;background:#fff;border:1px solid #e2e8f0;border-top:4px solid #dc2626;border-radius:12px;text-align:center}'
            . 'h1{font-size:20px;margin:0 0 10px}p{margin:6px 0;color:#475569;line-height:1.5}code{background:#f1f5f9;padding:2px 6px;border-radius:4px}</style></head><body><div class="b">'
            . '<h1>Access blocked</h1><p>Access from your network <code>' . $ipHtml . '</code> has been blocked because of suspicious activity.</p><p>If this is a mistake, please contact IT support.</p>'
            . '<p dir="rtl" style="margin-top:14px">تم حظر الدخول من شبكتك <code>' . $ipHtml . '</code> بسبب نشاط مشبوه. إذا كان ذلك خطأً يرجى التواصل مع الدعم الفني.</p>'
            . '</div></body></html>';
        exit();
    }

    // ---------------------------------------------------------------- rules table + cache file

    function ip_rules_ensure_table($conDB): void
    {
        static $done = false;
        if ($done) {
            return;
        }
        $done = true;
        $conDB->query("CREATE TABLE IF NOT EXISTS `ip_rules` (
            `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `ip` VARCHAR(64) NOT NULL,
            `rule` VARCHAR(10) NOT NULL,
            `label` VARCHAR(255) NOT NULL DEFAULT '',
            `source` VARCHAR(10) NOT NULL DEFAULT 'manual',
            `created_by` VARCHAR(64) NOT NULL DEFAULT '',
            `created_at` DATETIME NOT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `uniq_ip` (`ip`),
            KEY `idx_rule` (`rule`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
    }

    function ip_rules_list($conDB, ?string $rule = null): array
    {
        ip_rules_ensure_table($conDB);
        $sql = "SELECT id, ip, rule, label, source, created_by, created_at FROM ip_rules" . ($rule ? " WHERE rule = '" . ($rule === 'trust' ? 'trust' : 'block') . "'" : '') . " ORDER BY created_at DESC, id DESC";
        $res = $conDB->query($sql);
        $rows = [];
        while ($res && ($row = $res->fetch_assoc())) {
            $rows[] = $row;
        }
        return $rows;
    }

    /** Rewrites cache/ip_blocklist.json from the table (read by ip_guard_enforce()). */
    function ip_guard_rebuild_cache($conDB): bool
    {
        $ips = array_column(ip_rules_list($conDB, 'block'), 'ip');
        $dir = dirname(IP_GUARD_CACHE_FILE);
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
        if (!$ips) {
            return !is_file(IP_GUARD_CACHE_FILE) || @unlink(IP_GUARD_CACHE_FILE);
        }
        return @file_put_contents(IP_GUARD_CACHE_FILE, json_encode(array_values($ips)), LOCK_EX) !== false;
    }

    function ip_rule_find($conDB, string $ip): ?array
    {
        foreach (ip_rules_list($conDB) as $row) {
            if (ip_rule_matches($ip, $row['ip'])) {
                return $row;
            }
        }
        return null;
    }

    function ip_is_trusted($conDB, string $ip): bool
    {
        foreach (ip_rules_list($conDB, 'trust') as $row) {
            if (ip_rule_matches($ip, $row['ip'])) {
                return true;
            }
        }
        return false;
    }

    /** Adds / replaces a rule. Returns [ok, message]. */
    function ip_rule_save($conDB, string $entry, string $rule, string $label, string $source, string $by): array
    {
        ip_rules_ensure_table($conDB);
        $rule = $rule === 'trust' ? 'trust' : 'block';
        $ip = ip_access_normalise_entry($entry);
        if ($ip === null) {
            return [false, str_replace('{ip}', $entry, ipg_t('ip_access_invalid_entry', '"{ip}" is not a valid IP address or range (e.g. 5.41.90.162 or 5.41.90.0/24).'))];
        }
        if ($rule === 'block' && $source === 'manual' && ip_rule_matches((string) ($_SERVER['REMOTE_ADDR'] ?? ''), $ip)) {
            return [false, ipg_t('ip_block_self', 'This is your own current IP - blocking it would lock you out.')];
        }
        $label = mb_substr(trim($label), 0, 255);
        $stmt = $conDB->prepare("INSERT INTO ip_rules (ip, rule, label, source, created_by, created_at) VALUES (?, ?, ?, ?, ?, NOW())
            ON DUPLICATE KEY UPDATE rule = VALUES(rule), label = VALUES(label), source = VALUES(source), created_by = VALUES(created_by), created_at = NOW()");
        $stmt->bind_param('sssss', $ip, $rule, $label, $source, $by);
        $stmt->execute();
        $stmt->close();
        ip_guard_rebuild_cache($conDB);
        return [true, ''];
    }

    function ip_rule_delete($conDB, int $id): void
    {
        ip_rules_ensure_table($conDB);
        $stmt = $conDB->prepare("DELETE FROM ip_rules WHERE id = ?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $stmt->close();
        ip_guard_rebuild_cache($conDB);
    }

    // ---------------------------------------------------------------- settings

    /** Creates the rows once, returns the IP security settings. */
    function ip_access_settings($conDB, bool $fresh = false): array
    {
        static $cache = null;
        if ($cache !== null && !$fresh) {
            return $cache;
        }
        $defaults = [
            'ip_autoblock_enabled' => ['1', 'Block IPs with repeated failed sign-ins automatically'],
            'ip_autoblock_fails'   => ['10', 'Auto-block after this many failed sign-ins from one IP within 15 minutes'],
            'ip_autoblock_ids'     => ['5', 'Auto-block after this many different IDs failed from one IP within 15 minutes'],
            'ip_access_enabled'    => ['0', 'Office only: allow sign-in only from trusted IPs'],
            'ip_access_scope'      => ['staff', 'Who office-only mode applies to: staff (all except employees) or all'],
            'ip_access_blocked'    => ['[]', 'Last sign-ins refused by office-only mode (auto-updated, read-only)'],
        ];
        $values = [];
        $res = @$conDB->query("SELECT setting_name, setting_value FROM app_settings WHERE setting_group = 'ip_access'");
        while ($res && ($row = $res->fetch_assoc())) {
            $values[$row['setting_name']] = (string) $row['setting_value'];
        }
        foreach ($defaults as $name => [$value, $description]) {
            if (array_key_exists($name, $values)) {
                continue;
            }
            $stmt = $conDB->prepare("INSERT IGNORE INTO app_settings (setting_name, setting_value, setting_group, description, input_type) VALUES (?, ?, 'ip_access', ?, 'text')");
            if ($stmt) {
                $stmt->bind_param('sss', $name, $value, $description);
                $stmt->execute();
                $stmt->close();
            }
            $values[$name] = $value;
        }
        // First version kept the office list as JSON in ip_access_list: move it to trusted rules
        if (!empty($values['ip_access_list']) && $values['ip_access_list'] !== '[]') {
            foreach ((array) json_decode($values['ip_access_list'], true) as $row) {
                if (!empty($row['ip'])) {
                    ip_rule_save($conDB, (string) $row['ip'], 'trust', (string) ($row['label'] ?? ''), 'manual', 'migrated');
                }
            }
            $conDB->query("UPDATE app_settings SET setting_value = '[]' WHERE setting_name = 'ip_access_list'");
        }
        $blocked = json_decode($values['ip_access_blocked'], true);
        return $cache = [
            'autoblock' => trim($values['ip_autoblock_enabled']) === '1',
            'fails'     => max(3, (int) $values['ip_autoblock_fails']),
            'ids'       => max(2, (int) $values['ip_autoblock_ids']),
            'enabled'   => trim($values['ip_access_enabled']) === '1',
            'scope'     => $values['ip_access_scope'] === 'all' ? 'all' : 'staff',
            'blocked'   => is_array($blocked) ? array_values($blocked) : [],
        ];
    }

    function ip_access_update_settings($conDB, array $values): void
    {
        ip_access_settings($conDB);
        $stmt = $conDB->prepare("UPDATE app_settings SET setting_value = ? WHERE setting_name = ?");
        foreach ($values as $name => $value) {
            $value = (string) $value;
            $stmt->bind_param('ss', $value, $name);
            $stmt->execute();
        }
        $stmt->close();
        ip_access_settings($conDB, true);
    }

    // ---------------------------------------------------------------- auto-block

    /** Called by login_sec_record() after every failed attempt. */
    function ip_guard_after_failure($conDB, string $ip): void
    {
        if ($ip === '') {
            return;
        }
        $s = ip_access_settings($conDB);
        if (!$s['autoblock'] || ip_rule_find($conDB, $ip) !== null) {
            return; // off, or already trusted / blocked
        }
        $window = (int) IP_GUARD_WINDOW;
        $stmt = $conDB->prepare("SELECT COUNT(*) AS fails, COUNT(DISTINCT identifier) AS ids, GROUP_CONCAT(DISTINCT identifier ORDER BY id DESC SEPARATOR ', ') AS who
            FROM login_attempts WHERE ip_address = ? AND success = 0 AND created_at > DATE_SUB(NOW(), INTERVAL {$window} SECOND)");
        $stmt->bind_param('s', $ip);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        $fails = (int) ($row['fails'] ?? 0);
        $ids = (int) ($row['ids'] ?? 0);
        if ($fails < $s['fails'] && $ids < $s['ids']) {
            return;
        }
        if (ip_guard_is_known($conDB, $ip)) {
            return; // someone signed in successfully from here recently - a shared office IP, never blocked automatically
        }
        $who = implode(', ', array_slice(explode(', ', (string) ($row['who'] ?? '')), 0, 8));
        ip_rule_save($conDB, $ip, 'block', $fails . ' failed sign-ins / ' . $ids . ' IDs in 15 min (' . $who . ')', 'auto', 'auto');
        error_log('[IP GUARD] auto-blocked ' . $ip . ' (' . $fails . ' failures, ' . $ids . ' IDs)');
    }

    /** A successful sign-in (not a rate-limit marker) from this IP in the last IP_GUARD_KNOWN_DAYS days. */
    function ip_guard_is_known($conDB, string $ip): bool
    {
        $days = (int) IP_GUARD_KNOWN_DAYS;
        $stmt = $conDB->prepare("SELECT 1 FROM login_attempts WHERE ip_address = ? AND success = 1 AND kind IN ('login', 'otp', 'gauth', 'register', 'microsoft')
            AND created_at > DATE_SUB(NOW(), INTERVAL {$days} DAY) LIMIT 1");
        $stmt->bind_param('s', $ip);
        $stmt->execute();
        $known = $stmt->get_result()->num_rows > 0;
        $stmt->close();
        return $known;
    }

    /**
     * Sign-in activity per IP for the tab (last $days days), newest first.
     * users = every ID tried from that IP: [{id, name, ok, failed, last_seen}], newest first.
     */
    function ip_guard_activity($conDB, int $days = 7, int $limit = 60): array
    {
        $since = "created_at > DATE_SUB(NOW(), INTERVAL " . (int) $days . " DAY) AND identifier NOT LIKE '%:%'";
        $res = @$conDB->query("SELECT ip_address AS ip, SUM(success = 1) AS ok, SUM(success = 0) AS failed, COUNT(DISTINCT identifier) AS ids, MAX(created_at) AS last_seen
            FROM login_attempts WHERE $since GROUP BY ip_address ORDER BY last_seen DESC LIMIT " . (int) $limit);
        $rows = [];
        while ($res && ($row = $res->fetch_assoc())) {
            $row['users'] = [];
            $rows[$row['ip']] = $row;
        }
        if (!$rows) {
            return [];
        }
        // Per IP + ID
        $ips = array_map('strval', array_keys($rows));
        $in = implode(',', array_fill(0, count($ips), '?'));
        $stmt = $conDB->prepare("SELECT ip_address AS ip, identifier AS id, SUM(success = 1) AS ok, SUM(success = 0) AS failed, MAX(created_at) AS last_seen
            FROM login_attempts WHERE $since AND ip_address IN ($in) GROUP BY ip_address, identifier ORDER BY last_seen DESC");
        $stmt->bind_param(str_repeat('s', count($ips)), ...$ips);
        $stmt->execute();
        $r = $stmt->get_result();
        $allIds = [];
        while ($u = $r->fetch_assoc()) {
            $rows[$u['ip']]['users'][] = ['id' => (string) $u['id'], 'name' => '', 'ok' => (int) $u['ok'], 'failed' => (int) $u['failed'], 'last_seen' => $u['last_seen']];
            $allIds[(string) $u['id']] = true;
        }
        $stmt->close();
        // ID -> name
        $names = [];
        $allIds = array_map('strval', array_keys($allIds));
        if ($allIds) {
            $in = implode(',', array_fill(0, count($allIds), '?'));
            $stmt = $conDB->prepare("SELECT a.id_iqama, COALESCE(e.name, a.fullname) AS name FROM admin_login a LEFT JOIN employees e ON e.emp_id = a.emp_id WHERE a.id_iqama IN ($in)");
            $stmt->bind_param(str_repeat('s', count($allIds)), ...$allIds);
            $stmt->execute();
            $r = $stmt->get_result();
            while ($n = $r->fetch_assoc()) {
                $names[(string) $n['id_iqama']] = function_exists('getDisplayName') ? getDisplayName((string) $n['name']) : (string) $n['name'];
            }
            $stmt->close();
        }
        $rules = ip_rules_list($conDB);
        foreach ($rows as &$row) {
            foreach ($row['users'] as &$u) {
                $u['name'] = $names[$u['id']] ?? '';
            }
            unset($u);
            $row['rule'] = '';
            foreach ($rules as $rule) {
                if (ip_rule_matches((string) $row['ip'], $rule['ip'])) {
                    $row['rule'] = $rule['rule'];
                    break;
                }
            }
        }
        unset($row);
        return array_values($rows);
    }

    // ---------------------------------------------------------------- office-only mode

    function ip_access_applies_to(string $scope, string $userType): bool
    {
        return $scope === 'all' || $userType !== 'employee';
    }

    /** Office-only mode: may this user type sign in from $ip (default: current request)? */
    function ip_access_allowed($conDB, string $userType, ?string $ip = null): bool
    {
        if (PHP_SAPI === 'cli') {
            return true;
        }
        $s = ip_access_settings($conDB);
        if (!$s['enabled'] || !ip_access_applies_to($s['scope'], $userType)) {
            return true;
        }
        return ip_is_trusted($conDB, $ip ?? (string) ($_SERVER['REMOTE_ADDR'] ?? ''));
    }

    /** Remember an office-only refusal (newest first, last IP_ACCESS_BLOCKED_KEEP). */
    function ip_access_record_block($conDB, string $who): void
    {
        $ip = (string) ($_SERVER['REMOTE_ADDR'] ?? '');
        $blocked = ip_access_settings($conDB)['blocked'];
        array_unshift($blocked, ['ip' => $ip, 'user' => $who, 'at' => date('Y-m-d H:i:s')]);
        ip_access_update_settings($conDB, ['ip_access_blocked' => json_encode(array_slice($blocked, 0, IP_ACCESS_BLOCKED_KEEP), JSON_UNESCAPED_UNICODE)]);
        error_log('[IP ACCESS] refused ' . $who . ' from ' . $ip);
    }

    function ip_access_denied_message(): string
    {
        $ip = (string) ($_SERVER['REMOTE_ADDR'] ?? '');
        $text = ipg_t('ip_access_denied', 'Access from your network ({ip}) is not allowed. Please connect from the office network or contact IT support.');
        return str_replace('{ip}', $ip, $text);
    }

    /** Save office-only mode. Refuses a change that would lock out the admin saving it. Returns [ok, message]. */
    function ip_access_save_mode($conDB, bool $enabled, string $scope, string $adminUserType): array
    {
        $scope = $scope === 'all' ? 'all' : 'staff';
        if ($enabled && !ip_rules_list($conDB, 'trust')) {
            return [false, ipg_t('ip_access_empty_list', 'Add at least one trusted IP before switching it on.')];
        }
        $myIp = (string) ($_SERVER['REMOTE_ADDR'] ?? '');
        if ($enabled && ip_access_applies_to($scope, $adminUserType) && !ip_is_trusted($conDB, $myIp)) {
            return [false, str_replace('{ip}', $myIp, ipg_t('ip_access_self_lockout', 'Your current IP ({ip}) is not trusted - switching on would sign you out. Trust it first.'))];
        }
        ip_access_update_settings($conDB, ['ip_access_enabled' => $enabled ? '1' : '0', 'ip_access_scope' => $scope]);
        return [true, ipg_t('saved_successfully', 'Saved successfully.')];
    }

    /** Everything the IP Security tab shows. */
    function ip_security_state($conDB): array
    {
        $settings = ip_access_settings($conDB, true);
        ip_guard_rebuild_cache($conDB); // self-heal the cache file (e.g. lost in a deploy)
        return [
            'settings' => $settings,
            'blocked'  => ip_rules_list($conDB, 'block'),
            'trusted'  => ip_rules_list($conDB, 'trust'),
            'activity' => ip_guard_activity($conDB),
            'my_ip'    => (string) ($_SERVER['REMOTE_ADDR'] ?? ''),
        ];
    }
}
