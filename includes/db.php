<?php
/**
 * MODIFICATION SUMMARY
 *
 * - Removed hardcoded database credentials (DB_HOST, DB_USER, DB_PASS, DB_NAME).
 * - Added functionality to parse database settings from an external `config.ini` file.
 * - This approach improves security and makes configuration management easier.
 * - The rest of the script will continue to function as before by using the constants
 * defined from the .ini file.
 * - Added error handling in case the configuration file is unreadable.
 */

ob_start();

if (!function_exists('app_is_json_request')) {
    function app_is_json_request() {
        if (PHP_SAPI === 'cli') {
            return false;
        }

        $requestedWith = $_SERVER['HTTP_X_REQUESTED_WITH'] ?? '';
        $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
        $requestUri = $_SERVER['REQUEST_URI'] ?? '';

        return strtolower($requestedWith) === 'xmlhttprequest'
            || stripos($accept, 'application/json') !== false
            || preg_match('/(check_user_type|reset_password|table_json_api|ajax)/i', $requestUri);
    }
}

if (!function_exists('app_should_show_detailed_errors')) {
    function app_should_show_detailed_errors() {
        $remoteAddr = $_SERVER['REMOTE_ADDR'] ?? '';
        $serverName = $_SERVER['SERVER_NAME'] ?? '';
        $envOverride = getenv('APP_SHOW_ERRORS');

        if ($envOverride === '1') {
            return true;
        }

        return in_array($remoteAddr, ['127.0.0.1', '::1'], true) || stripos($serverName, '.local') !== false;
    }
}

if (!function_exists('app_render_runtime_error')) {
    function app_render_runtime_error($publicMessage, $technicalDetails = '', $statusCode = 500) {
        static $isRendering = false;

        if ($isRendering) {
            exit;
        }
        $isRendering = true;

        $reference = 'ERR-' . date('YmdHis') . '-' . substr(md5($publicMessage . microtime(true)), 0, 6);
        $requestUri = $_SERVER['REQUEST_URI'] ?? 'CLI';
        $logMessage = sprintf('[ALMUTLAK][%s] %s | URI: %s', $reference, $publicMessage, $requestUri);

        if (!empty($technicalDetails)) {
            $logMessage .= ' | Details: ' . $technicalDetails;
        }

        error_log($logMessage);

        while (ob_get_level() > 0) {
            @ob_end_clean();
        }

        if (!headers_sent()) {
            http_response_code($statusCode);
        }

        $showDetails = app_should_show_detailed_errors();

        if (app_is_json_request()) {
            if (!headers_sent()) {
                header('Content-Type: application/json; charset=utf-8');
            }

            echo json_encode([
                'status' => 'error',
                'message' => $publicMessage,
                'error_reference' => $reference,
                'details' => $showDetails && !empty($technicalDetails) ? $technicalDetails : null,
            ]);
            exit;
        }

        if (!headers_sent()) {
            header('Content-Type: text/html; charset=utf-8');
        }

        $safeMessage = htmlspecialchars($publicMessage, ENT_QUOTES, 'UTF-8');
        $safeReference = htmlspecialchars($reference, ENT_QUOTES, 'UTF-8');
        $safeDetails = $showDetails && !empty($technicalDetails)
            ? '<pre style="margin-top:16px;padding:12px;background:#0f172a;color:#e2e8f0;border-radius:8px;white-space:pre-wrap;word-break:break-word;">' . htmlspecialchars($technicalDetails, ENT_QUOTES, 'UTF-8') . '</pre>'
            : '';

        echo '<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Server Error</title>
    <style>
        body{font-family:Arial,sans-serif;background:#f8fafc;color:#0f172a;margin:0;padding:24px;display:flex;align-items:center;justify-content:center;min-height:100vh}
        .error-card{max-width:720px;width:100%;background:#fff;border-radius:14px;padding:28px;box-shadow:0 10px 30px rgba(15,23,42,.12)}
        .badge{display:inline-block;background:#fee2e2;color:#b91c1c;border-radius:999px;padding:6px 10px;font-size:12px;font-weight:700;margin-bottom:12px}
        h1{margin:0 0 10px;font-size:28px}
        p{line-height:1.6;margin:0 0 10px}
        .ref{margin-top:14px;color:#475569;font-size:13px}
    </style>
</head>
<body>
    <div class="error-card">
        <span class="badge">Server-side issue detected</span>
        <h1>Request could not be completed</h1>
        <p>' . $safeMessage . '</p>
        <p>Please try again in a few minutes. If the issue continues, share the reference below with the administrator.</p>
        <div class="ref">Reference: ' . $safeReference . '</div>
        ' . $safeDetails . '
    </div>
</body>
</html>';
        exit;
    }
}

if (!function_exists('app_bootstrap_error_handlers')) {
    function app_bootstrap_error_handlers() {
        static $registered = false;

        if ($registered) {
            return;
        }
        $registered = true;

        error_reporting(E_ALL & ~E_NOTICE);
        ini_set('log_errors', '1');
        ini_set('html_errors', '0');
        ini_set('default_socket_timeout', '15');
        ini_set('max_execution_time', '25');
        ini_set('max_input_time', '25');
        ini_set('mysql.connect_timeout', '10');
        @set_time_limit(25);

        set_exception_handler(function (Throwable $e) {
            app_render_runtime_error(
                'A server-side error occurred while processing your request.',
                $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine(),
                500
            );
        });

        register_shutdown_function(function () {
            $error = error_get_last();
            if (!$error) {
                return;
            }

            $fatalTypes = [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR];
            if (!in_array($error['type'], $fatalTypes, true)) {
                return;
            }

            $publicMessage = stripos($error['message'], 'Maximum execution time') !== false
                ? 'The server took too long to respond and stopped the request.'
                : 'A fatal server-side error stopped this request.';

            app_render_runtime_error(
                $publicMessage,
                $error['message'] . ' in ' . $error['file'] . ':' . $error['line'],
                500
            );
        });
    }
}

app_bootstrap_error_handlers();

// Caps concurrent DB-connecting requests, per client IP AND app-wide, so the
// shared-hosting MySQL pool (fixed by the host, can't be raised - see
// max_connections) never gets exhausted from our side. IP is the closest
// reliable stand-in for "one device" - true device fingerprints are JS-based
// and spoofable. Uses flock'd files (no APCu/Redis dependency) so it works on
// plain shared hosting.
if (!defined('ALMUTLAK_CONNLIMIT_DIR')) {
    define('ALMUTLAK_CONNLIMIT_DIR', sys_get_temp_dir() . '/almutlak_connlimit');
}

// Each active slot is stored as one JSON line so connection_monitor.php can
// show WHO/WHAT is holding connections (ip, page, age) instead of just a
// count - needed to tell real traffic apart from a leak.
if (!function_exists('app_acquire_concurrency_slot')) {
    function app_acquire_concurrency_slot($key, $limit, $publicMessage, $staleSeconds = 30) {
        if (PHP_SAPI === 'cli') {
            return; // cron/CLI scripts not subject to this
        }

        if (!is_dir(ALMUTLAK_CONNLIMIT_DIR)) {
            @mkdir(ALMUTLAK_CONNLIMIT_DIR, 0700, true);
        }

        $file = ALMUTLAK_CONNLIMIT_DIR . '/' . md5($key) . '.json';
        $fp = @fopen($file, 'c+');
        if (!$fp) {
            return; // fail open: don't block traffic if temp dir unwritable
        }

        flock($fp, LOCK_EX);
        $raw = stream_get_contents($fp);
        $now = time();
        $active = [];
        foreach (preg_split('/\r?\n/', (string) $raw, -1, PREG_SPLIT_NO_EMPTY) as $line) {
            $entry = json_decode($line, true);
            if (is_array($entry) && ($entry['ts'] ?? 0) > $now - $staleSeconds) {
                $active[] = $entry;
            }
        }

        if (count($active) >= $limit) {
            flock($fp, LOCK_UN);
            fclose($fp);
            app_render_runtime_error(
                $publicMessage,
                "Key '$key' exceeded $limit concurrent slots",
                429
            );
        }

        // Logged-in user is already known here: session_check.php starts the
        // session and sets $_SESSION['auth_user'] BEFORE it requires this file,
        // so this is free - no extra query needed to know who's holding the slot.
        $sessionLoginId = '';
        $sessionName = '';
        if (session_status() === PHP_SESSION_ACTIVE && !empty($_SESSION['auth_user']) && is_array($_SESSION['auth_user'])) {
            $sessionLoginId = (string) ($_SESSION['auth_user']['user_id'] ?? '');
            $sessionName = (string) ($_SESSION['auth_user']['fullname'] ?? '');
        }

        $myToken = uniqid('', true);
        $active[] = [
            'token'    => $myToken,
            'ip'       => $_SERVER['REMOTE_ADDR'] ?? 'cli',
            'uri'      => $_SERVER['REQUEST_URI'] ?? '',
            'ts'       => $now,
            'pid'      => getmypid(),
            'login_id' => $sessionLoginId,
            'user_name'=> $sessionName,
        ];

        ftruncate($fp, 0);
        rewind($fp);
        foreach ($active as $entry) {
            fwrite($fp, json_encode($entry) . "\n");
        }
        fflush($fp);
        flock($fp, LOCK_UN);
        fclose($fp);

        register_shutdown_function(function () use ($file, $myToken) {
            $fp = @fopen($file, 'c+');
            if (!$fp) {
                return;
            }
            flock($fp, LOCK_EX);
            $raw = stream_get_contents($fp);
            $kept = [];
            foreach (preg_split('/\r?\n/', (string) $raw, -1, PREG_SPLIT_NO_EMPTY) as $line) {
                $entry = json_decode($line, true);
                if (is_array($entry) && ($entry['token'] ?? null) !== $myToken) {
                    $kept[] = $line;
                }
            }
            ftruncate($fp, 0);
            rewind($fp);
            fwrite($fp, $kept ? implode("\n", $kept) . "\n" : '');
            fflush($fp);
            flock($fp, LOCK_UN);
            fclose($fp);
        });
    }
}

// Per-IP: stop one browser/bot/script alone eating the pool.
app_acquire_concurrency_slot(
    $_SERVER['REMOTE_ADDR'] ?? 'cli',
    3,
    'Too many concurrent requests from your connection. Please wait a moment and try again.'
);

// App-wide: host's max_connections is fixed at 151 and shared with other
// processes on the account (other cron jobs, other DB clients). Stay well
// under it so our own app never triggers the fatal "1040 Too many
// connections" - reject gracefully instead once we're the bottleneck.
app_acquire_concurrency_slot(
    'global',
    100,
    'The system is under heavy load right now. Please try again in a moment.'
);

// Load database configuration from an external .ini file
$configPath = __DIR__ . '/config.ini';
if (!file_exists($configPath) || !is_readable($configPath)) {
    app_render_runtime_error('The server configuration file is missing or unreadable.', 'Config path: ' . $configPath, 500);
}

$config = parse_ini_file($configPath, true);
if ($config === false || !isset($config['database'])) {
    app_render_runtime_error('Database configuration is missing from the server settings.', 'Unable to parse config.ini or [database] section missing.', 500);
}

$dbConfig = $config['database'];

// Define constants from the configuration file
define('DB_HOST', $dbConfig['DB_HOST'] ?? '');
define('DB_USER', $dbConfig['DB_USER'] ?? '');
define('DB_PASS', $dbConfig['DB_PASS'] ?? '');
define('DB_NAME', $dbConfig['DB_NAME'] ?? '');

if (DB_HOST === '' || DB_USER === '' || DB_NAME === '') {
    app_render_runtime_error('Database credentials are incomplete on the live server.', 'One or more DB_* values are missing in config.ini.', 500);
}

mysqli_report(MYSQLI_REPORT_OFF);

// MySQL error 1040 = "Too many connections". This is almost always transient
// (a burst of parallel requests briefly exhausts the connection quota), so a
// couple of short backoff retries clear it without needing any server-side change.
$dbConnectMaxAttempts = 3;
$conDB = null;
$connected = false;
for ($dbConnectAttempt = 1; $dbConnectAttempt <= $dbConnectMaxAttempts; $dbConnectAttempt++) {
    $conDB = mysqli_init();
    if (!$conDB) {
        app_render_runtime_error('The system could not initialize a database connection.', 'mysqli_init returned false.', 503);
    }

    mysqli_options($conDB, MYSQLI_OPT_CONNECT_TIMEOUT, 10);
    if (@mysqli_real_connect($conDB, DB_HOST, DB_USER, DB_PASS, DB_NAME)) {
        $connected = true;
        break;
    }

    $isTooManyConnections = mysqli_connect_errno() === 1040;
    if (!$isTooManyConnections || $dbConnectAttempt === $dbConnectMaxAttempts) {
        break;
    }
    usleep(300000 * $dbConnectAttempt); // 300ms, then 600ms
}

if (!$connected) {
    app_render_runtime_error(
        'The live server cannot connect to the database right now.',
        mysqli_connect_error(),
        503
    );
}

$conDB->set_charset("utf8mb4");

// Host won't raise max_connections (fixed shared-hosting limit). Shrinking
// this connection's own idle timeout means if a request dies weird (client
// abort, hung script) and never explicitly closes, MySQL reclaims the slot
// in seconds instead of sitting on it until the server-wide wait_timeout
// (often hours on shared hosts) finally kicks it out.
mysqli_query($conDB, "SET SESSION wait_timeout = 15, SESSION interactive_timeout = 15");

// Release the slot back to the pool the moment this script ends rather than
// waiting on PHP's own connection teardown timing.
register_shutdown_function(function () use ($conDB) {
    global $pdo;
    if ($conDB instanceof mysqli) {
        @mysqli_close($conDB);
    }
    $pdo = null;
});

// Set timezone to Saudi Arabia (GMT+3)
$defaultTimezone = 'Asia/Riyadh';
date_default_timezone_set($defaultTimezone);
mysqli_query($conDB, "SET time_zone = '+03:00'");

// License enforcement lives here, not only in session_check.php: dozens of
// ajax/api endpoints connect to the database directly without ever including
// session_check.php, which left them fully reachable regardless of license
// state. db.php is the one file every entry point requires, so gating here
// closes that gap in a single place instead of chasing individual pages.
// Must run AFTER date_default_timezone_set() above - license_enforce() compares
// strtotime() on stored timestamps against time(), and doing that before the
// timezone is set to Asia/Riyadh throws the elapsed-time math off by the UTC
// offset, silently breaking the periodic recheck.
require_once __DIR__ . '/license_check.php';
license_enforce($conDB);

// Lazy PDO connection: most pages only ever use the mysqli $conDB above and never
// touch $pdo/db(). Connecting PDO eagerly on every request doubled DB connection
// usage app-wide and was a direct contributor to "Too many connections". This
// subclass IS a real PDO (passes `instanceof PDO` and `PDO $pdo` type hints), it
// just defers the actual connection until the first method call that needs it.
if (!class_exists('LazyPdo')) {
    class LazyPdo extends PDO {
        private $lazyDsn;
        private $lazyUser;
        private $lazyPass;
        private $lazyOptions;
        private $lazyConnected = false;

        public function __construct($dsn, $user, $pass, $options) {
            $this->lazyDsn = $dsn;
            $this->lazyUser = $user;
            $this->lazyPass = $pass;
            $this->lazyOptions = $options;
        }

        private function ensureConnected() {
            if ($this->lazyConnected) {
                return;
            }

            $maxAttempts = 3;
            for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
                try {
                    parent::__construct($this->lazyDsn, $this->lazyUser, $this->lazyPass, $this->lazyOptions);
                    $this->lazyConnected = true;
                    return;
                } catch (PDOException $e) {
                    $isTooManyConnections = (int)$e->getCode() === 1040 || stripos($e->getMessage(), 'too many connections') !== false;
                    if (!$isTooManyConnections || $attempt === $maxAttempts) {
                        app_render_runtime_error('The live server cannot connect to the database right now.', $e->getMessage(), 503);
                        throw $e;
                    }
                    usleep(300000 * $attempt); // 300ms, then 600ms
                }
            }
        }

        public function prepare(string $query, array $options = []): PDOStatement|false {
            $this->ensureConnected();
            return parent::prepare($query, $options);
        }

        public function query(string $query, ?int $fetchMode = null, mixed ...$fetchModeArgs): PDOStatement|false {
            $this->ensureConnected();
            return parent::query(...func_get_args());
        }

        public function exec(string $statement): int|false {
            $this->ensureConnected();
            return parent::exec($statement);
        }

        public function beginTransaction(): bool {
            $this->ensureConnected();
            return parent::beginTransaction();
        }

        public function commit(): bool {
            $this->ensureConnected();
            return parent::commit();
        }

        public function rollBack(): bool {
            $this->ensureConnected();
            return parent::rollBack();
        }

        public function inTransaction(): bool {
            $this->ensureConnected();
            return parent::inTransaction();
        }

        public function lastInsertId(?string $name = null): string|false {
            $this->ensureConnected();
            return parent::lastInsertId($name);
        }

        public function quote(string $string, int $type = PDO::PARAM_STR): string|false {
            $this->ensureConnected();
            return parent::quote($string, $type);
        }

        public function errorInfo(): array {
            $this->ensureConnected();
            return parent::errorInfo();
        }
    }
}

/** @var PDO|null $pdo */
$pdo = null;

function getDbConnection(): PDO {
    global $pdo;

    if (!($pdo instanceof PDO)) {
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
        $options = [
            PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8mb4 COLLATE utf8mb4_general_ci',
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::ATTR_TIMEOUT            => 10,
        ];
        $pdo = new LazyPdo($dsn, DB_USER, DB_PASS, $options);
    }

    return $pdo;
}

if (!function_exists('db')) {
    function db(): PDO {
        return getDbConnection();
    }
}

/** @var PDO $pdo */
$pdo = getDbConnection();

// --- Function to Get Settings ---
function get_setting($conn, $setting_name) {
    $value = null; // Default value
    $sql = "SELECT setting_value FROM app_settings WHERE setting_name = ?";
    $stmt = $conn->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("s", $setting_name);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            $value = $row['setting_value'];
        }
        $stmt->close();
    }
    return $value;
}

/****time_zone****/
$applicationTimezone = trim((string) (get_setting($conDB, 'timezone') ?? ''));
if ($applicationTimezone === '' || !in_array($applicationTimezone, timezone_identifiers_list(), true)) {
    $applicationTimezone = $defaultTimezone;
}
date_default_timezone_set($applicationTimezone);
mysqli_query($conDB,"SET NAMES utf8mb4 COLLATE utf8mb4_general_ci;");
header('Content-Type: text/html; charset=utf-8');

$developerMode = (string) get_setting($conDB, 'developer_mode') === '1';
if ($developerMode) {
    error_reporting(E_ALL ^ E_NOTICE);
    ini_set('display_errors', '1');
    ini_set('display_startup_errors', '1');
    ini_set('log_errors', '1');
} else {
    ini_set('display_errors', '0');
    ini_set('display_startup_errors', '0');
    ini_set('log_errors', '1');
}


$requestPath = parse_url($_SERVER['REQUEST_URI'] ?? ($_SERVER['SCRIPT_NAME'] ?? ''), PHP_URL_PATH);
$pgname = $requestPath ? basename($requestPath) : '';

/*$url = "http://".$_SERVER['HTTP_HOST']."";
$parsed = parse_url($url);
$domain = explode('.', $parsed['host']);
$maindomain = '';
$subdomain = '';
if ($domain[0] == 'www'){
    $subdomain  = $domain[1];
    $maindomain = (isset($domain[2]))?$domain[2]:"";
} else {
    $subdomain  = $domain[0];
    $maindomain = (isset($domain[1]))?$domain[1]:"";
}

$apiKey = "f4ebae-c62cdf-920748-1ba956-583c33";
$usrid = "mochachino_db";
$url = "https://hekayajazeera.com/restapi";
$ch = curl_init();
curl_setopt($ch, CURLOPT_USERPWD, $usrid . ":" . $apiKey);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_URL,$url);
$result=curl_exec($ch);
$apitbl=json_decode($result, true);
function tbl($index, $array){
    if (array_key_exists($index, $array)) {
        return $array[$index];
    }
}

if (!isset($apitbl['0'])) {
    echo $apitbl['data'];
    // exit();
} else {
    $apitbl = $apitbl;
}*/

require_once __DIR__ . '/init.php'; // Initialize the application and load translations

?>
