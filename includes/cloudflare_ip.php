<?php
/**
 * Real visitor IP behind Cloudflare (App Settings > Integrations > "Cloudflare").
 *
 * With the domain proxied through Cloudflare every request reaches the server from a Cloudflare
 * edge IP, so REMOTE_ADDR would be the same few addresses for all users (per-IP request limit in
 * db.php, login lockouts, ZK sync allowed-IP, activity logs). While the switch is on and the request
 * really comes from a Cloudflare range, REMOTE_ADDR is replaced with the CF-Connecting-IP header.
 * Requests from any other address keep their REMOTE_ADDR, so the header cannot be faked by calling
 * the server directly.
 *
 * The switch is the app_settings row 'cloudflare_enabled' (default off), mirrored to the flag file
 * cache/cloudflare_proxy.on because this runs before the database connection (the per-IP limiter
 * in db.php comes first). settings_handler.php writes the file on save and re-syncs it from the row.
 *
 * Loaded first by includes/db.php and includes/zk_db_connect.php.
 * Ranges: https://www.cloudflare.com/ips/ (update if Cloudflare adds new ones).
 */

if (!defined('CF_PROXY_FLAG_FILE')) {
    define('CF_PROXY_FLAG_FILE', dirname(__DIR__) . '/cache/cloudflare_proxy.on');
}

if (!function_exists('cf_ip_in_cidr')) {
    function cf_ip_in_cidr(string $ip, string $cidr): bool
    {
        [$subnet, $bits] = explode('/', $cidr);
        $ipBin = @inet_pton($ip);
        $subBin = @inet_pton($subnet);
        if ($ipBin === false || $subBin === false || strlen($ipBin) !== strlen($subBin)) {
            return false;
        }
        $bits = (int) $bits;
        $bytes = intdiv($bits, 8);
        if (strncmp($ipBin, $subBin, $bytes) !== 0) {
            return false;
        }
        $rest = $bits % 8;
        if ($rest === 0) {
            return true;
        }
        $mask = (0xFF << (8 - $rest)) & 0xFF;
        return (ord($ipBin[$bytes]) & $mask) === (ord($subBin[$bytes]) & $mask);
    }

    function cf_is_cloudflare_ip(string $ip): bool
    {
        static $ranges = [
            // IPv4
            '173.245.48.0/20', '103.21.244.0/22', '103.22.200.0/22', '103.31.4.0/22', '141.101.64.0/18',
            '108.162.192.0/18', '190.93.240.0/20', '188.114.96.0/20', '197.234.240.0/22', '198.41.128.0/17',
            '162.158.0.0/15', '104.16.0.0/13', '104.24.0.0/14', '172.64.0.0/13', '131.0.72.0/22',
            // IPv6
            '2400:cb00::/32', '2606:4700::/32', '2803:f800::/32', '2405:b500::/32', '2405:8100::/32',
            '2a06:98c0::/29', '2c0f:f248::/32',
        ];
        foreach ($ranges as $cidr) {
            if (cf_ip_in_cidr($ip, $cidr)) {
                return true;
            }
        }
        return false;
    }
}

if (!function_exists('cloudflare_proxy_enabled')) {
    /** Integrations switch, read from the flag file (no database needed). */
    function cloudflare_proxy_enabled(): bool
    {
        return is_file(CF_PROXY_FLAG_FILE);
    }

    /** Write / remove the flag file. Returns false when the cache folder is not writable. */
    function cloudflare_set_proxy_enabled(bool $on): bool
    {
        if (!$on) {
            return !is_file(CF_PROXY_FLAG_FILE) || @unlink(CF_PROXY_FLAG_FILE);
        }
        $dir = dirname(CF_PROXY_FLAG_FILE);
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
        return is_file(CF_PROXY_FLAG_FILE) || @file_put_contents(CF_PROXY_FLAG_FILE, date('c')) !== false;
    }

    /**
     * How the current request reached the app (for the Integrations card):
     * mode 'app'    - came from a Cloudflare IP, the app swapped in the real visitor IP (switch on)
     *      'server' - Cloudflare headers present and the web server already gives the real IP (mod_remoteip)
     *      'edge'   - came from a Cloudflare IP, switch off (REMOTE_ADDR is still Cloudflare's)
     *      'header' - Cloudflare headers present but the sender is not in the Cloudflare ranges
     *      'none'   - no Cloudflare headers: opened directly
     * ip = the IP the app uses for this request, edge = the address that connected (when different).
     */
    function cloudflare_request_info(): array
    {
        $ip = (string) ($_SERVER['REMOTE_ADDR'] ?? '');
        $header = trim((string) ($_SERVER['HTTP_CF_CONNECTING_IP'] ?? ''));
        if (!empty($_SERVER['CF_EDGE_ADDR'])) {
            return ['mode' => 'app', 'ip' => $ip, 'edge' => (string) $_SERVER['CF_EDGE_ADDR']];
        }
        if ($header === '' && empty($_SERVER['HTTP_CF_RAY'])) {
            return ['mode' => 'none', 'ip' => $ip, 'edge' => ''];
        }
        if ($header !== '' && $header === $ip) {
            return ['mode' => 'server', 'ip' => $ip, 'edge' => ''];
        }
        return ['mode' => cf_is_cloudflare_ip($ip) ? 'edge' : 'header', 'ip' => $ip, 'edge' => $ip];
    }

    function cloudflare_request_detected(): bool
    {
        return cloudflare_request_info()['mode'] !== 'none';
    }
}

if (PHP_SAPI !== 'cli' && !defined('CF_REAL_IP_APPLIED') && cloudflare_proxy_enabled()) {
    define('CF_REAL_IP_APPLIED', true);
    $cfEdge = (string) ($_SERVER['REMOTE_ADDR'] ?? '');
    $cfVisitor = trim((string) ($_SERVER['HTTP_CF_CONNECTING_IP'] ?? ''));
    if ($cfEdge !== '' && $cfVisitor !== '' && filter_var($cfVisitor, FILTER_VALIDATE_IP) && cf_is_cloudflare_ip($cfEdge)) {
        $_SERVER['CF_EDGE_ADDR'] = $cfEdge;   // the Cloudflare server that forwarded the request
        $_SERVER['REMOTE_ADDR'] = $cfVisitor;
    }
    unset($cfEdge, $cfVisitor);
}
