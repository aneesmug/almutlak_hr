<?php
/**
 * Hourly MySQL backup tool.
 *
 * Dumps the app database to a gzip-compressed .sql.gz file, stored ONE
 * LEVEL ABOVE the "system" folder (outside the web docroot on the live
 * server, since the live docroot points at "system" itself), grouped into
 * per-day folders: db_backups/YYYY-MM-DD/almutlak_db_HH-ii-ss.sql.gz
 * Keeps the last 7 days worth of folders; any day folder older than that
 * (whole folder, not individual files inside it) is deleted after each run.
 *
 * The same secret key is required on every run - CLI/cron included, not just
 * HTTP. It lives in the app_settings table (setting_name 'db_backup_secret_key',
 * visible/editable on the General settings page). Self-healed on first run: if
 * the row doesn't exist yet, it's created with a fresh random value. Unlike
 * db_export_secret_key this key does NOT rotate after use - it stays stable
 * so cron can keep using it.
 *
 * Run modes:
 *  - CLI (cron):  php tools/db_backup.php key=YOUR_SECRET
 *  - HTTP:        https://yourdomain.com/tools/db_backup.php?key=YOUR_SECRET
 *
 * Hourly cron (top of every hour):
 * 0 * * * * php /full/path/to/system/tools/db_backup.php key=572d4ba9e872518e3ea3f8961ab3d3a3cc9b8c05262acec5e9422d515fbbde5c >> /full/path/to/system/tools/cron_output.log 2>&1
 */

error_reporting(E_ALL);
ini_set('display_errors', PHP_SAPI === 'cli' ? '1' : '0');

define('BACKUP_RETENTION_DAYS', 7);

$configPath = __DIR__ . '/../includes/config.ini';
if (!file_exists($configPath) || !is_readable($configPath)) {
    backup_fail('Config file missing or unreadable: ' . $configPath);
}

$config = parse_ini_file($configPath, true);
if ($config === false || !isset($config['database'])) {
    backup_fail('Unable to parse config.ini or [database] section missing.');
}

$db = $config['database'];
define('DB_HOST', $db['DB_HOST'] ?? '');
define('DB_PORT', $db['DB_PORT'] ?? '3306');
define('DB_USER', $db['DB_USER'] ?? '');
define('DB_PASS', $db['DB_PASS'] ?? '');
define('DB_NAME', $db['DB_NAME'] ?? '');

if (DB_HOST === '' || DB_USER === '' || DB_NAME === '') {
    backup_fail('Database credentials are incomplete in config.ini.');
}

$backupCfg = $config['backup'] ?? [];

// Guard: require the same secret key on every run - CLI/cron included - not
// just HTTP. Anyone with shell/cron access still needs the key from app_settings.
$expectedKey = backup_get_or_create_web_key();
$providedKey = PHP_SAPI === 'cli' ? backup_get_cli_key($argv ?? []) : (string) ($_GET['key'] ?? '');

if ($expectedKey === '' || !hash_equals($expectedKey, $providedKey)) {
    if (PHP_SAPI === 'cli') {
        backup_fail('Missing or invalid backup key. Usage: php tools/db_backup.php key=YOUR_SECRET');
    }
    http_response_code(403);
    exit('Forbidden');
}

if (PHP_SAPI !== 'cli') {
    header('Content-Type: text/plain; charset=utf-8');
}

$backupDir = realpath(__DIR__ . '/..') . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'db_backups';
$backupDir = str_replace('\\', '/', $backupDir);
if (!is_dir($backupDir) && !@mkdir($backupDir, 0700, true)) {
    backup_fail('Could not create backup directory: ' . $backupDir);
}

// Defense in depth in case this folder ever ends up reachable over HTTP.
$htaccess = $backupDir . '/.htaccess';
if (!file_exists($htaccess)) {
    @file_put_contents($htaccess, "Require all denied\nDeny from all\n");
}

$logFile = $backupDir . '/backup.log';
$lockFile = $backupDir . '/.backup.lock';

$lockHandle = fopen($lockFile, 'c');
if (!$lockHandle || !flock($lockHandle, LOCK_EX | LOCK_NB)) {
    backup_log($logFile, 'SKIP: another backup run is already in progress.');
    exit;
}

$dayFolder = date('Y-m-d');
$dayDir = $backupDir . '/' . $dayFolder;
if (!is_dir($dayDir) && !@mkdir($dayDir, 0700, true)) {
    flock($lockHandle, LOCK_UN);
    fclose($lockHandle);
    backup_fail('Could not create day folder: ' . $dayDir);
}

$filename = DB_NAME . '_' . date('H-i-s') . '.sql.gz';
$filepath = $dayDir . '/' . $filename;

try {
    $mysqldumpPath = backup_resolve_mysqldump_path($backupCfg);

    if ($mysqldumpPath !== null && function_exists('proc_open')) {
        backup_dump_via_mysqldump($mysqldumpPath, $filepath);
    } else {
        backup_dump_via_php($filepath);
    }

    $size = filesize($filepath);
    if ($size === false || $size < 100) {
        throw new RuntimeException('Backup file looks empty/too small (' . ($size === false ? 'unknown' : $size) . ' bytes).');
    }

    backup_log($logFile, "OK: $dayFolder/$filename created (" . round($size / 1024, 1) . " KB).");

    $deleted = backup_rotate($backupDir, BACKUP_RETENTION_DAYS);
    foreach ($deleted as $old) {
        backup_log($logFile, "ROTATE: deleted old day folder $old");
    }

    if (PHP_SAPI !== 'cli') {
        echo "OK: $filename (" . round($size / 1024, 1) . " KB)\n";
    }
} catch (Throwable $e) {
    @unlink($filepath);
    backup_log($logFile, 'FAIL: ' . $e->getMessage());
    flock($lockHandle, LOCK_UN);
    fclose($lockHandle);
    backup_fail($e->getMessage());
}

flock($lockHandle, LOCK_UN);
fclose($lockHandle);

// --- helpers -----------------------------------------------------------

function backup_fail(string $message): void {
    if (PHP_SAPI === 'cli') {
        fwrite(STDERR, $message . "\n");
        exit(1);
    }
    http_response_code(500);
    echo "FAIL: $message\n";
    exit(1);
}

function backup_log(string $logFile, string $message): void {
    $line = '[' . date('Y-m-d H:i:s') . '] ' . $message . "\n";
    @file_put_contents($logFile, $line, FILE_APPEND | LOCK_EX);
}

function backup_get_cli_key(array $argv): string {
    foreach (array_slice($argv, 1) as $arg) {
        if (str_starts_with($arg, 'key=')) {
            return substr($arg, 4);
        }
        if (str_starts_with($arg, '--key=')) {
            return substr($arg, 6);
        }
    }
    return '';
}

function backup_get_or_create_web_key(): string {
    $conn = mysqli_init();
    mysqli_options($conn, MYSQLI_OPT_CONNECT_TIMEOUT, 10);
    if (!@mysqli_real_connect($conn, DB_HOST, DB_USER, DB_PASS, DB_NAME, (int) DB_PORT)) {
        return '';
    }

    $result = mysqli_query($conn, "SELECT setting_value FROM app_settings WHERE setting_name = 'db_backup_secret_key'");
    $row = $result ? mysqli_fetch_assoc($result) : null;
    $key = trim((string) ($row['setting_value'] ?? ''));

    if ($key === '') {
        $key = bin2hex(random_bytes(32));
        $description = 'Database Backup Secret Key <br /> <small>(used in the daily backup cron URL - see tools/db_backup.php)</small>';
        $stmt = mysqli_prepare($conn, "INSERT INTO app_settings (setting_name, setting_value, description) VALUES ('db_backup_secret_key', ?, ?)
            ON DUPLICATE KEY UPDATE
                setting_value = IF(setting_value = '' OR setting_value IS NULL, VALUES(setting_value), setting_value),
                description = IF(description = '' OR description IS NULL, VALUES(description), description)");
        mysqli_stmt_bind_param($stmt, 'ss', $key, $description);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        $result = mysqli_query($conn, "SELECT setting_value FROM app_settings WHERE setting_name = 'db_backup_secret_key'");
        $row = $result ? mysqli_fetch_assoc($result) : null;
        $key = trim((string) ($row['setting_value'] ?? $key));
    }

    mysqli_close($conn);
    return $key;
}

function backup_resolve_mysqldump_path(array $backupCfg): ?string {
    if (!empty($backupCfg['MYSQLDUMP_PATH']) && is_file($backupCfg['MYSQLDUMP_PATH'])) {
        return $backupCfg['MYSQLDUMP_PATH'];
    }

    if (stripos(PHP_OS, 'WIN') === 0) {
        $guess = str_replace('\\', '/', dirname(dirname(PHP_BINARY))) . '/mysql/bin/mysqldump.exe';
        if (is_file($guess)) {
            return $guess;
        }
        return null; // don't rely on PATH lookup for a bare command on Windows
    }

    return 'mysqldump'; // resolved via PATH on Linux/macOS hosts
}

function backup_dump_via_mysqldump(string $mysqldumpPath, string $filepath): void {
    $cmd = escapeshellarg($mysqldumpPath)
        . ' --host=' . escapeshellarg(DB_HOST)
        . ' --port=' . escapeshellarg((string) DB_PORT)
        . ' --user=' . escapeshellarg(DB_USER)
        . ' --single-transaction --quick --routines --triggers --events'
        . ' --default-character-set=utf8mb4 '
        . escapeshellarg(DB_NAME);

    putenv('MYSQL_PWD=' . DB_PASS);
    $descriptorSpec = [
        0 => ['pipe', 'r'],
        1 => ['pipe', 'w'],
        2 => ['pipe', 'w'],
    ];
    $process = proc_open($cmd, $descriptorSpec, $pipes);
    if (!is_resource($process)) {
        putenv('MYSQL_PWD');
        throw new RuntimeException('Could not start mysqldump process.');
    }
    fclose($pipes[0]);

    $gz = gzopen($filepath, 'wb9');
    if (!$gz) {
        proc_terminate($process);
        putenv('MYSQL_PWD');
        throw new RuntimeException('Could not open backup file for writing: ' . $filepath);
    }

    while (!feof($pipes[1])) {
        $chunk = fread($pipes[1], 1048576);
        if ($chunk === false || $chunk === '') {
            continue;
        }
        gzwrite($gz, $chunk);
    }
    gzclose($gz);

    $stderr = stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);
    $exitCode = proc_close($process);
    putenv('MYSQL_PWD');

    if ($exitCode !== 0) {
        throw new RuntimeException('mysqldump exited with code ' . $exitCode . ': ' . trim($stderr));
    }
}

function backup_dump_via_php(string $filepath): void {
    $mysqli = mysqli_init();
    mysqli_options($mysqli, MYSQLI_OPT_CONNECT_TIMEOUT, 15);
    if (!@mysqli_real_connect($mysqli, DB_HOST, DB_USER, DB_PASS, DB_NAME, (int) DB_PORT)) {
        throw new RuntimeException('Could not connect to database: ' . mysqli_connect_error());
    }
    $mysqli->set_charset('utf8mb4');

    $gz = gzopen($filepath, 'wb9');
    if (!$gz) {
        throw new RuntimeException('Could not open backup file for writing: ' . $filepath);
    }

    gzwrite($gz, "-- PHP fallback dump (mysqldump unavailable) of " . DB_NAME . " on " . date('c') . "\n");
    gzwrite($gz, "SET NAMES utf8mb4;\nSET FOREIGN_KEY_CHECKS=0;\n\n");

    $tables = [];
    $res = $mysqli->query('SHOW TABLES');
    while ($row = $res->fetch_row()) {
        $tables[] = $row[0];
    }
    $res->free();

    foreach ($tables as $table) {
        $quoted = '`' . str_replace('`', '``', $table) . '`';

        $createRow = $mysqli->query("SHOW CREATE TABLE $quoted")->fetch_assoc();
        gzwrite($gz, "DROP TABLE IF EXISTS $quoted;\n" . $createRow['Create Table'] . ";\n\n");

        $result = $mysqli->query("SELECT * FROM $quoted", MYSQLI_USE_RESULT);
        $fields = $result->fetch_fields();
        $columnNames = array_map(fn($f) => '`' . str_replace('`', '``', $f->name) . '`', $fields);
        $rowsBuffer = [];
        $bufferCount = 0;

        while ($row = $result->fetch_row()) {
            $values = array_map(function ($value) use ($mysqli) {
                if ($value === null) {
                    return 'NULL';
                }
                return "'" . $mysqli->real_escape_string($value) . "'";
            }, $row);
            $rowsBuffer[] = '(' . implode(',', $values) . ')';
            $bufferCount++;

            if ($bufferCount >= 500) {
                gzwrite($gz, "INSERT INTO $quoted (" . implode(',', $columnNames) . ") VALUES\n" . implode(",\n", $rowsBuffer) . ";\n");
                $rowsBuffer = [];
                $bufferCount = 0;
            }
        }
        if ($rowsBuffer) {
            gzwrite($gz, "INSERT INTO $quoted (" . implode(',', $columnNames) . ") VALUES\n" . implode(",\n", $rowsBuffer) . ";\n");
        }
        gzwrite($gz, "\n");
        $result->free();
    }

    gzwrite($gz, "SET FOREIGN_KEY_CHECKS=1;\n");
    gzclose($gz);
    $mysqli->close();
}

function backup_rotate(string $backupDir, int $retentionDays): array {
    $deleted = [];
    $cutoffFolder = date('Y-m-d', strtotime('-' . ($retentionDays - 1) . ' days'));

    foreach (glob($backupDir . '/*', GLOB_ONLYDIR) as $dir) {
        $folderName = basename($dir);
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $folderName)) {
            continue; // not one of our day folders
        }
        if ($folderName < $cutoffFolder) {
            backup_rrmdir($dir);
            $deleted[] = $folderName;
        }
    }

    return $deleted;
}

function backup_rrmdir(string $dir): void {
    foreach (scandir($dir) ?: [] as $entry) {
        if ($entry === '.' || $entry === '..') {
            continue;
        }
        $path = $dir . '/' . $entry;
        is_dir($path) ? backup_rrmdir($path) : @unlink($path);
    }
    @rmdir($dir);
}
