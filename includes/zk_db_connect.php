<?php
// Minimal DB bootstrap for ZKTeco device endpoints (iclock/*.php) and related
// CLI scripts. Deliberately does NOT include includes/db.php: that file's
// per-IP/global connection-slot limiter, license_enforce() redirect, and
// HTML/JSON error pages are all wrong here - device firmware expects a bare
// "OK"/text response on every request and will retry-storm on anything else
// (a redirect or an HTML error page looks like "no data" to it, forever).
if (!function_exists('zk_get_db_connection')) {
    function zk_get_db_connection() {
        static $conn = null;
        if ($conn instanceof mysqli) {
            return $conn;
        }

        $configPath = __DIR__ . '/config.ini';
        $config = @parse_ini_file($configPath, true);
        if ($config === false || !isset($config['database'])) {
            error_log('[ZK] Unable to read database config at ' . $configPath);
            return null;
        }

        $db = $config['database'];
        $conn = @mysqli_connect($db['DB_HOST'] ?? '', $db['DB_USER'] ?? '', $db['DB_PASS'] ?? '', $db['DB_NAME'] ?? '');
        if (!$conn) {
            error_log('[ZK] Database connection failed: ' . mysqli_connect_error());
            $conn = null;
            return null;
        }

        mysqli_set_charset($conn, 'utf8mb4');
        mysqli_query($conn, "SET time_zone = '+03:00'");
        return $conn;
    }
}
