<?php
/**
 * App access by IP (App Settings > Integrations > "Allowed IPs").
 *
 * When switched on, only the listed IPs / ranges may sign in and use the app. Checked in login.php
 * (refused before any OTP is sent) and on every signed-in page in session_check.php (a session from
 * another address is signed out). Device / sync endpoints (iclock, zk_sync_*) are not affected.
 *
 * Settings (app_settings):
 *   ip_access_enabled  '0' | '1'                 (default off)
 *   ip_access_scope    'staff' | 'all'           staff = every account except user_type 'employee'
 *   ip_access_list     JSON [{ip, label}, ...]   ip = single IPv4 / IPv6 or CIDR range (e.g. 5.41.90.0/24)
 *   ip_access_blocked  JSON [{ip, user, at}, ...] last refused attempts (newest first), for the tab
 *
 * Behind Cloudflare the visitor IP comes from includes/cloudflare_ip.php (switch on the Add-ons tab).
 * Locked out? Set ip_access_enabled to 0 in app_settings (phpMyAdmin) and sign in again.
 */

require_once __DIR__ . '/cloudflare_ip.php'; // cf_ip_in_cidr() + the real visitor IP

if (!defined('IP_ACCESS_BLOCKED_KEEP')) {
    define('IP_ACCESS_BLOCKED_KEEP', 15);
}

if (!function_exists('ip_access_settings')) {
    /** Creates the rows once, returns ['enabled' => bool, 'scope' => 'staff'|'all', 'list' => [...], 'blocked' => [...]]. */
    function ip_access_settings($conDB, bool $fresh = false): array
    {
        static $cache = null;
        if ($cache !== null && !$fresh) {
            return $cache;
        }
        $defaults = [
            'ip_access_enabled' => ['0', 'Allow app access only from the listed IPs'],
            'ip_access_scope'   => ['staff', 'Who the allowed IP list applies to: staff (all except employees) or all'],
            'ip_access_list'    => ['[]', 'Allowed IPs / ranges (JSON list of {ip, label})'],
            'ip_access_blocked' => ['[]', 'Last sign-ins refused by the allowed IP list (auto-updated, read-only)'],
        ];
        $values = [];
        $res = @$conDB->query("SELECT setting_name, setting_value FROM app_settings WHERE setting_name LIKE 'ip\\_access\\_%'");
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
        $list = json_decode($values['ip_access_list'], true);
        $blocked = json_decode($values['ip_access_blocked'], true);
        return $cache = [
            'enabled' => trim($values['ip_access_enabled']) === '1',
            'scope'   => $values['ip_access_scope'] === 'all' ? 'all' : 'staff',
            'list'    => is_array($list) ? array_values($list) : [],
            'blocked' => is_array($blocked) ? array_values($blocked) : [],
        ];
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

    function ip_access_ip_in_list(string $ip, array $list): bool
    {
        foreach ($list as $row) {
            $entry = (string) ($row['ip'] ?? '');
            if ($entry === '') {
                continue;
            }
            if (strpos($entry, '/') !== false ? cf_ip_in_cidr($ip, $entry) : @inet_pton($entry) === @inet_pton($ip)) {
                return true;
            }
        }
        return false;
    }

    /** Does the list apply to this account type? */
    function ip_access_applies_to(string $scope, string $userType): bool
    {
        return $scope === 'all' || $userType !== 'employee';
    }

    /** May this user type use the app from $ip (default: current request)? */
    function ip_access_allowed($conDB, string $userType, ?string $ip = null): bool
    {
        if (PHP_SAPI === 'cli') {
            return true;
        }
        $s = ip_access_settings($conDB);
        if (!$s['enabled'] || !ip_access_applies_to($s['scope'], $userType)) {
            return true;
        }
        return ip_access_ip_in_list($ip ?? (string) ($_SERVER['REMOTE_ADDR'] ?? ''), $s['list']);
    }

    /** Remember a refused attempt (newest first, last IP_ACCESS_BLOCKED_KEEP) so the tab can offer "Allow". */
    function ip_access_record_block($conDB, string $who): void
    {
        $ip = (string) ($_SERVER['REMOTE_ADDR'] ?? '');
        $blocked = ip_access_settings($conDB)['blocked'];
        array_unshift($blocked, ['ip' => $ip, 'user' => $who, 'at' => date('Y-m-d H:i:s')]);
        $json = json_encode(array_slice($blocked, 0, IP_ACCESS_BLOCKED_KEEP), JSON_UNESCAPED_UNICODE);
        $stmt = $conDB->prepare("UPDATE app_settings SET setting_value = ? WHERE setting_name = 'ip_access_blocked'");
        if ($stmt) {
            $stmt->bind_param('s', $json);
            $stmt->execute();
            $stmt->close();
        }
        error_log('[IP ACCESS] refused ' . $who . ' from ' . $ip);
    }

    function ip_access_denied_message(): string
    {
        $ip = (string) ($_SERVER['REMOTE_ADDR'] ?? '');
        $text = function_exists('__') ? __('ip_access_denied', 'Access from your network ({ip}) is not allowed. Please connect from the office network or contact IT support.') : 'Access from your network ({ip}) is not allowed. Please connect from the office network or contact IT support.';
        return str_replace('{ip}', $ip, $text);
    }

    /**
     * Save from the Allowed IPs tab. $list: [{ip, label}]. Refuses a change that would lock out the
     * admin saving it (their own IP must stay allowed while the switch is on).
     * Returns [ok, message].
     */
    function ip_access_save($conDB, bool $enabled, string $scope, array $list, string $adminUserType): array
    {
        $scope = $scope === 'all' ? 'all' : 'staff';
        $clean = [];
        $seen = [];
        foreach ($list as $row) {
            $raw = (string) ($row['ip'] ?? '');
            if (trim($raw) === '') {
                continue;
            }
            $entry = ip_access_normalise_entry($raw);
            if ($entry === null) {
                return [false, str_replace('{ip}', $raw, __('ip_access_invalid_entry', '"{ip}" is not a valid IP address or range (e.g. 5.41.90.162 or 5.41.90.0/24).'))];
            }
            if (isset($seen[$entry])) {
                continue;
            }
            $seen[$entry] = true;
            $clean[] = ['ip' => $entry, 'label' => mb_substr(trim((string) ($row['label'] ?? '')), 0, 80)];
        }
        if ($enabled && !$clean) {
            return [false, __('ip_access_empty_list', 'Add at least one IP before switching it on.')];
        }
        $myIp = (string) ($_SERVER['REMOTE_ADDR'] ?? '');
        if ($enabled && ip_access_applies_to($scope, $adminUserType) && !ip_access_ip_in_list($myIp, $clean)) {
            return [false, str_replace('{ip}', $myIp, __('ip_access_self_lockout', 'Your current IP ({ip}) is not in the list - saving would sign you out. Add it first.'))];
        }
        $values = [
            'ip_access_enabled' => $enabled ? '1' : '0',
            'ip_access_scope'   => $scope,
            'ip_access_list'    => json_encode($clean, JSON_UNESCAPED_UNICODE),
        ];
        ip_access_settings($conDB); // rows exist
        $stmt = $conDB->prepare("UPDATE app_settings SET setting_value = ? WHERE setting_name = ?");
        foreach ($values as $name => $value) {
            $stmt->bind_param('ss', $value, $name);
            $stmt->execute();
        }
        $stmt->close();
        ip_access_settings($conDB, true);
        return [true, __('saved_successfully', 'Saved successfully.')];
    }
}
