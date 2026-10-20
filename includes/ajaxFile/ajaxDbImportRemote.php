<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../session_check.php';
require_once __DIR__ . '/../db_schema_sync.php';

if (!($is_system_admin ?? false)) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

// Nothing below writes to the session. Release the session file lock right away -
// PHP file sessions are locked for the whole request, and a run_import page can take
// minutes, which otherwise freezes every other page of the app in this browser.
session_write_close();

// Import checkpoint (app_settings 'db_import_remote_checkpoint'): the batch, the table
// and page cursor reached, and the latest rotated live export key. Saved after every
// page so a crash / closed tab / server restart can resume instead of starting over
// (and without losing the single-use key the live server already rotated).
function dbir_checkpoint_get(mysqli $db): ?array
{
    $res = mysqli_query($db, "SELECT setting_value FROM app_settings WHERE setting_name = 'db_import_remote_checkpoint'");
    $row = $res ? mysqli_fetch_row($res) : null;
    $data = $row ? json_decode((string) $row[0], true) : null;
    if (!is_array($data) || empty($data['table'])) {
        return null;
    }
    $key = dbir_setting_get($db, 'db_import_remote_key');
    if ($key !== '') {
        $data['export_key'] = $key;
    }
    return $data;
}

function dbir_setting_get(mysqli $db, string $name): string
{
    $stmt = mysqli_prepare($db, "SELECT setting_value FROM app_settings WHERE setting_name = ?");
    mysqli_stmt_bind_param($stmt, 's', $name);
    mysqli_stmt_execute($stmt);
    $row = mysqli_fetch_row(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);
    return $row ? (string) $row[0] : '';
}

function dbir_setting_set(mysqli $db, string $name, string $value): void
{
    $stmt = mysqli_prepare($db, "INSERT INTO app_settings (setting_name, setting_value) VALUES (?, ?)
        ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
    mysqli_stmt_bind_param($stmt, 'ss', $name, $value);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
}

// Latest rotated live export key - saved the moment a download completes
function dbir_key_save(mysqli $db, string $key): void
{
    dbir_setting_set($db, 'db_import_remote_key', $key);
}

function dbir_checkpoint_save(mysqli $db, array $data): void
{
    if (!empty($data['export_key'])) {
        dbir_key_save($db, $data['export_key']);
    }
    unset($data['export_key']);
    $data['updated_at'] = date('Y-m-d H:i:s');
    dbir_setting_set($db, 'db_import_remote_checkpoint', json_encode($data));
}

// Downloaded-but-not-yet-applied pages wait here (one .sql file per page)
function dbir_spool_dir(): string
{
    $dir = rtrim(sys_get_temp_dir(), '/\\') . DIRECTORY_SEPARATOR . 'dbir_spool_' . md5(DB_NAME);
    if (!is_dir($dir)) {
        @mkdir($dir, 0700, true);
    }
    return $dir;
}

function dbir_spool_path(string $id): ?string
{
    return preg_match('/^[a-f0-9]{32}$/', $id) ? dbir_spool_dir() . DIRECTORY_SEPARATOR . $id . '.sql' : null;
}

function dbir_spool_cleanup(int $olderThanSeconds): void
{
    foreach ((array) glob(dbir_spool_dir() . DIRECTORY_SEPARATOR . '*.sql') as $f) {
        if ($f && @filemtime($f) < time() - $olderThanSeconds) {
            @unlink($f);
        }
    }
}

// Reads up to $bytes from the start / end of a file (markers live there, not in the middle)
function dbir_file_head(string $path, int $bytes): string
{
    $h = @fopen($path, 'rb');
    if (!$h) {
        return '';
    }
    $data = (string) fread($h, $bytes);
    fclose($h);
    return $data;
}

function dbir_file_tail(string $path, int $bytes): string
{
    $size = (int) @filesize($path);
    $h = @fopen($path, 'rb');
    if (!$h) {
        return '';
    }
    fseek($h, max(0, $size - $bytes));
    $data = (string) fread($h, $bytes);
    fclose($h);
    return $data;
}

// Live progress of the page currently being imported (download bytes, rows applied),
// written to a small temp file while run_import works and polled by the browser every
// second - so a 20,000-row page visibly moves instead of jumping at the end, and a stall
// shows up as "no activity for Xs". Throttled to at most ~3 writes per second.
function dbir_progress_file(string $channel = 'ap'): string
{
    return rtrim(sys_get_temp_dir(), '/\\') . DIRECTORY_SEPARATOR . 'dbir_progress_' . md5(DB_NAME) . '_' . $channel . '.json';
}

function dbir_progress(array $data, bool $force = false, string $channel = 'ap'): void
{
    static $last = [], $state = [];
    $state[$channel] = array_merge($state[$channel] ?? [], $data);
    $now = microtime(true);
    if (!$force && $now - ($last[$channel] ?? 0) < 0.3) {
        return;
    }
    $last[$channel] = $now;
    $state[$channel]['ts'] = time();
    @file_put_contents(dbir_progress_file($channel), json_encode($state[$channel]), LOCK_EX);
}

function dbir_checkpoint_clear(mysqli $db): void
{
    mysqli_query($db, "UPDATE app_settings SET setting_value = '' WHERE setting_name = 'db_import_remote_checkpoint'");
    dbir_spool_cleanup(0);
}

// Returns the column name immediately before $target in $orderedNames (ordinal order),
// or null if $target is the first column - used to emit AFTER `col` / FIRST in generated ALTERs.
function db_import_remote_predecessor(array $orderedNames, string $target) {
    $prev = null;
    foreach ($orderedNames as $name) {
        if ($name === $target) {
            return $prev;
        }
        $prev = $name;
    }
    return null;
}

function db_import_remote_curl(string $url, array $postFields, int $timeoutSeconds, ?callable $onProgress = null, ?string $saveTo = null) {
    $ch = curl_init($url);
    $fh = null;
    if ($saveTo !== null) {
        $fh = fopen($saveTo, 'wb');
    }
    if ($onProgress) {
        curl_setopt($ch, CURLOPT_NOPROGRESS, false);
        curl_setopt($ch, CURLOPT_XFERINFOFUNCTION, function ($ch, $dlTotal, $dlNow) use ($onProgress) {
            $onProgress((int) $dlNow);
            return 0;
        });
    }
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => http_build_query($postFields),
        CURLOPT_TIMEOUT => $timeoutSeconds,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_MAXREDIRS => 3,
        // Shared-hosting WAFs (Imunify360, mod_security, etc.) commonly return 406 for
        // requests with no/curl-default User-Agent or Accept header.
        CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AlmutlakDbSync/1.0',
        CURLOPT_HTTPHEADER => ['Accept: */*', 'Expect:'],
    ]);
    // Output target LAST: setting CURLOPT_RETURNTRANSFER (even to false) after
    // CURLOPT_FILE silently switches the output to stdout, i.e. the HTTP response.
    if ($fh) {
        curl_setopt($ch, CURLOPT_FILE, $fh);
    } else {
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    }
    $body = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    if ($fh) {
        fclose($fh);
        $body = null;
    }

    return ['body' => $body, 'http_code' => $httpCode, 'error' => $error];
}

// Index signature: unique flag + ordered columns (with prefix length). Indexes are matched
// by shape, so the same key under a different name on each side is not a difference.
function dbir_index_sig(array $idx): string
{
    return (!empty($idx['unique']) ? 'U:' : 'K:') . implode(',', array_map(fn ($c) => strtolower($c['name']) . (isset($c['sub_part']) && $c['sub_part'] !== null && $c['sub_part'] !== '' ? '(' . (int) $c['sub_part'] . ')' : ''), $idx['columns'] ?? []));
}

function dbir_index_label(array $idx): string
{
    return (!empty($idx['unique']) ? 'UNIQUE ' : '') . $idx['name'] . ' (' . implode(', ', array_map(fn ($c) => $c['name'], $idx['columns'] ?? [])) . ')';
}

// Indexes on one side that have no same-shaped index on the other side
function dbir_index_missing(array $from, array $other): array
{
    $sigs = array_flip(array_map('dbir_index_sig', $other));
    return array_values(array_filter($from, fn ($i) => !isset($sigs[dbir_index_sig($i)])));
}

function db_import_remote_normalize_base_url(string $raw): ?string {
    $raw = trim($raw);
    if ($raw === '' || !preg_match('#^https?://#i', $raw)) {
        return null;
    }
    return rtrim($raw, '/');
}

$action = $_POST['action'] ?? '';

if ($action === 'get_checkpoint') {
    echo json_encode([
        'status' => 'success',
        'checkpoint' => dbir_checkpoint_get($conDB),
        'export_key' => dbir_setting_get($conDB, 'db_import_remote_key'),
    ]);
    exit;
}
if ($action === 'get_progress') {
    $out = [];
    foreach (['dl', 'ap'] as $ch) {
        $raw = @file_get_contents(dbir_progress_file($ch));
        $data = $raw ? json_decode($raw, true) : null;
        if (is_array($data)) {
            $data['age'] = max(0, time() - (int) ($data['ts'] ?? 0));
        }
        $out[$ch] = is_array($data) ? $data : null;
    }
    echo json_encode(['status' => 'success', 'progress' => $out]);
    exit;
}
if ($action === 'clear_checkpoint') {
    dbir_checkpoint_clear($conDB);
    echo json_encode(['status' => 'success']);
    exit;
}
$baseUrl = db_import_remote_normalize_base_url((string) ($_POST['base_url'] ?? ''));
$exportKey = trim((string) ($_POST['export_key'] ?? ''));

if ($baseUrl === null) {
    echo json_encode(['status' => 'error', 'message' => 'Live server URL must start with http:// or https://']);
    exit;
}
if ($exportKey === '') {
    echo json_encode(['status' => 'error', 'message' => 'Export key is required.']);
    exit;
}

if ($action === 'test_connection') {
    $result = db_import_remote_curl($baseUrl . '/includes/api/db_export_tables.php', ['export_key' => $exportKey], 30);

    if ($result['error']) {
        echo json_encode(['status' => 'error', 'message' => 'Connection failed: ' . $result['error']]);
        exit;
    }
    $data = json_decode((string) $result['body'], true);
    if ($result['http_code'] !== 200 || !is_array($data) || ($data['status'] ?? '') !== 'success') {
        echo json_encode(['status' => 'error', 'message' => ($data['message'] ?? null) ?: ('Live server returned HTTP ' . $result['http_code'] . '.')]);
        exit;
    }

    $localTableNames = [];
    $namesRes = mysqli_query($conDB, 'SHOW TABLES');
    while ($row = mysqli_fetch_array($namesRes)) {
        $localTableNames[$row[0]] = true;
    }

    $merged = [];
    foreach (($data['tables'] ?? []) as $t) {
        $name = $t['name'] ?? '';
        if ($name === '') {
            continue;
        }
        $liveRows = (int) ($t['rows'] ?? 0);
        $localRows = null;
        if (isset($localTableNames[$name])) {
            $ident = '`' . str_replace('`', '``', $name) . '`';
            $countRes = @mysqli_query($conDB, "SELECT COUNT(*) AS c FROM $ident");
            $localRows = $countRes ? (int) mysqli_fetch_assoc($countRes)['c'] : 0;
        }
        $merged[] = [
            'name' => $name,
            'live_rows' => $liveRows,
            'local_rows' => $localRows,
            'exists_locally' => $localRows !== null,
            'diff' => $localRows === null ? $liveRows : ($liveRows - $localRows),
        ];
    }

    echo json_encode(['status' => 'success', 'database' => $data['database'] ?? '', 'tables' => $merged]);
    exit;
}

if ($action === 'truncate_tables') {
    $tablesRaw = $_POST['tables'] ?? [];
    $tables = is_array($tablesRaw) ? array_values(array_filter(array_map('trim', $tablesRaw))) : [];
    if (empty($tables)) {
        echo json_encode(['status' => 'error', 'message' => 'Select at least one table.']);
        exit;
    }

    // The export key is single-use - download_db_export.php rotates it after every
    // successful pull, so a key that worked for an earlier import is dead by the time
    // this runs again. Without this check, a stale key would truncate the local tables
    // (this action never talks to the live server otherwise) and only THEN fail during
    // run_import's actual data pull, leaving those tables permanently empty. Validate
    // against db_export_tables.php first (it never rotates the key) so a bad/expired key
    // is caught before anything local is destroyed.
    $keyCheck = db_import_remote_curl($baseUrl . '/includes/api/db_export_tables.php', ['export_key' => $exportKey], 30);
    $keyCheckData = json_decode((string) $keyCheck['body'], true);
    if ($keyCheck['error'] || $keyCheck['http_code'] !== 200 || !is_array($keyCheckData) || ($keyCheckData['status'] ?? '') !== 'success') {
        echo json_encode([
            'status' => 'error',
            'message' => 'Live server rejected the export key - nothing was truncated. ' .
                ($keyCheckData['message'] ?? $keyCheck['error'] ?? ('HTTP ' . $keyCheck['http_code'])) .
                ' Get a fresh key from db_export.php on the live server and try again.',
        ]);
        exit;
    }

    $mysqli = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if (!$mysqli) {
        echo json_encode(['status' => 'error', 'message' => 'Could not connect to local database: ' . mysqli_connect_error()]);
        exit;
    }

    mysqli_query($mysqli, 'SET FOREIGN_KEY_CHECKS=0');

    $localTableNames = [];
    $namesRes = mysqli_query($mysqli, 'SHOW TABLES');
    while ($row = mysqli_fetch_array($namesRes)) {
        $localTableNames[$row[0]] = true;
    }

    $truncatedTables = [];
    $skippedTables = [];
    $errors = [];
    foreach ($tables as $table) {
        if (!isset($localTableNames[$table])) {
            $skippedTables[] = $table; // doesn't exist locally yet - nothing to truncate
            continue;
        }
        $ident = '`' . str_replace('`', '``', $table) . '`';
        if (mysqli_query($mysqli, "TRUNCATE TABLE $ident")) {
            $truncatedTables[] = $table;
        } else {
            $errors[] = "TRUNCATE $table: " . mysqli_error($mysqli);
        }
    }

    mysqli_query($mysqli, 'SET FOREIGN_KEY_CHECKS=1');
    mysqli_close($mysqli);

    if (!empty($errors)) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Truncate failed.',
            'errors' => array_slice($errors, 0, 20),
            'truncated_tables' => $truncatedTables,
        ]);
        exit;
    }

    echo json_encode([
        'status' => 'success',
        'message' => count($truncatedTables) . ' table(s) truncated.',
        'truncated_tables' => $truncatedTables,
        'skipped_tables' => $skippedTables,
    ]);
    exit;
}

// ---------------------------------------------------------------------------------
// Pipelined import: fetch_page and apply_page run AT THE SAME TIME from the browser -
// while page N is being written into MySQL, page N+1 is already downloading. Downloads
// stay strictly sequential (each one returns the next single-use live key); applies stay
// sequential too (same table, in order). Pages travel through a spool file on disk.

if ($action === 'fetch_page') {
    $table = trim((string) ($_POST['table'] ?? ''));
    if ($table === '') {
        echo json_encode(['status' => 'error', 'message' => 'No table given.']);
        exit;
    }
    $sinceRaw = trim((string) ($_POST['since'] ?? ''));
    if (!empty($_POST['truncate']) && $_POST['truncate'] !== '0' && $sinceRaw !== '') {
        echo json_encode(['status' => 'error', 'message' => '"Only rows changed since" cannot be combined with Truncate.']);
        exit;
    }
    set_time_limit(0);
    ignore_user_abort(true);
    dbir_spool_cleanup(86400);

    $pageSize = max(1, min(100000, (int) ($_POST['page_size'] ?? 20000)));
    $cursor = trim((string) ($_POST['cursor'] ?? ''));
    $postFields = ['export_key' => $exportKey, 'tables' => $table, 'page_size' => $pageSize];
    if ($cursor !== '') {
        $postFields['cursor'] = $cursor;
    }
    if ($sinceRaw !== '') {
        $postFields['since'] = $sinceRaw;
    }

    $fileId = bin2hex(random_bytes(16));
    $path = dbir_spool_path($fileId);
    $token = (string) ($_POST['page_token'] ?? '');
    dbir_progress(['token' => $token, 'table' => $table, 'phase' => 'download', 'bytes' => 0], true, 'dl');
    $result = db_import_remote_curl($baseUrl . '/download_db_export.php', $postFields, 900, function (int $bytes) {
        dbir_progress(['bytes' => $bytes], false, 'dl');
    }, $path);
    $size = (int) @filesize($path);
    dbir_progress(['phase' => 'done', 'bytes' => $size], true, 'dl');

    if ($result['error'] || $result['http_code'] !== 200) {
        $snippet = trim(dbir_file_head($path, 300));
        @unlink($path);
        echo json_encode(['status' => 'error', 'message' => $result['error']
            ? 'Fetch failed: ' . $result['error']
            : 'Live server returned HTTP ' . $result['http_code'] . ': ' . $snippet]);
        exit;
    }

    // Completeness + key + paging markers sit at the very end of the dump
    $tail = dbir_file_tail($path, 16384);
    if (strpos($tail, 'SET FOREIGN_KEY_CHECKS=1;') === false || !preg_match('/^-- NEW_EXPORT_KEY: (\S+)\s*$/m', $tail, $km)) {
        @unlink($path);
        echo json_encode(['status' => 'error', 'message' => 'The download from the live server looks incomplete (cut off before finishing) - this page will be downloaded again.']);
        exit;
    }
    $nextKey = $km[1];
    dbir_key_save($conDB, $nextKey);

    $tableTotal = isset($_POST['table_total']) && $_POST['table_total'] !== '' ? (int) $_POST['table_total'] : null;
    if (preg_match('/^-- Table: \S+ \((\d+) rows\)/m', dbir_file_head($path, 16384), $tm)) {
        $tableTotal = (int) $tm[1];
    }
    $pageRows = 0;
    $hasMore = false;
    $nextCursor = '';
    if (preg_match('/^-- PAGE: rows=(\d+) next=(-?\d+) more=([01])\s*$/m', $tail, $pm)) {
        $pageRows = (int) $pm[1];
        $nextCursor = $pm[2];
        $hasMore = $pm[3] === '1';
    } else {
        // Live without paging support sends the whole table in one go
        $pageRows = (int) $tableTotal;
    }

    echo json_encode([
        'status' => 'success',
        'file_id' => $fileId,
        'bytes' => $size,
        'page_rows' => $pageRows,
        'has_more' => $hasMore,
        'next_cursor' => $nextCursor,
        'table_total' => $tableTotal,
        'next_export_key' => $nextKey,
    ]);
    exit;
}

if ($action === 'apply_page') {
    $table = trim((string) ($_POST['table'] ?? ''));
    $path = dbir_spool_path((string) ($_POST['file_id'] ?? ''));
    if ($table === '' || $path === null) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid page reference.']);
        exit;
    }
    set_time_limit(0);
    ignore_user_abort(true);

    $hasMore = !empty($_POST['has_more']) && $_POST['has_more'] !== '0';
    $pageRows = max(0, (int) ($_POST['page_rows'] ?? 0));
    $importedBefore = max(0, (int) ($_POST['imported'] ?? 0));
    $tableTotal = isset($_POST['table_total']) && $_POST['table_total'] !== '' ? (int) $_POST['table_total'] : null;
    $sinceRaw = trim((string) ($_POST['since'] ?? ''));
    $queueRaw = json_decode((string) ($_POST['queue'] ?? ''), true);
    $checkpoint = [
        'base_url' => $baseUrl,
        'queue' => is_array($queueRaw) ? array_values(array_map('strval', $queueRaw)) : [$table],
        'table' => $table,
        'cursor' => $hasMore ? trim((string) ($_POST['next_cursor'] ?? '')) : '',
        'table_total' => $tableTotal,
        'imported' => $importedBefore + $pageRows,
        'table_done' => !$hasMore,
        'truncate' => !empty($_POST['truncate']) && $_POST['truncate'] !== '0',
        'since' => $sinceRaw,
    ];

    // The file is deleted only after its transaction committed - a missing file means a
    // previous attempt already applied it (its response was lost), so this is a success.
    if (!is_file($path)) {
        echo json_encode(['status' => 'success', 'already_applied' => true, 'page_rows' => $pageRows]);
        exit;
    }

    $token = (string) ($_POST['page_token'] ?? '');
    dbir_progress(['token' => $token, 'table' => $table, 'phase' => 'apply', 'applied' => 0, 'page_rows' => $pageRows], true, 'ap');

    $sql = "START TRANSACTION;\n" . (string) file_get_contents($path) . "\nCOMMIT;\n";
    $mysqli = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if (!$mysqli) {
        echo json_encode(['status' => 'error', 'message' => 'Could not connect to local database: ' . mysqli_connect_error()]);
        exit;
    }

    $errors = [];
    $applied = 0;
    try {
        if (mysqli_multi_query($mysqli, $sql)) {
            do {
                if ($res = mysqli_store_result($mysqli)) {
                    mysqli_free_result($res);
                }
                if (mysqli_errno($mysqli)) {
                    $errors[] = mysqli_error($mysqli);
                } elseif (($info = mysqli_info($mysqli)) && preg_match('/Records:\s*(\d+)/', $info, $rm)) {
                    $applied += (int) $rm[1];
                    dbir_progress(['applied' => $applied], false, 'ap');
                }
            } while (mysqli_more_results($mysqli) && mysqli_next_result($mysqli));
        } else {
            $errors[] = mysqli_error($mysqli);
        }
    } catch (Throwable $e) {
        $errors[] = $e->getMessage();
    }
    unset($sql);
    dbir_progress(['phase' => $errors ? 'failed' : 'committed', 'applied' => $applied], true, 'ap');

    if ($errors) {
        mysqli_close($mysqli); // uncommitted transaction rolls back
        echo json_encode(['status' => 'error', 'message' => 'Local SQL error: ' . $errors[0], 'errors' => array_slice($errors, 0, 10)]);
        exit;
    }

    @unlink($path);
    dbir_checkpoint_save($conDB, $checkpoint);

    $status = 'success';
    $message = '';
    $localCount = null;
    if (!$hasMore) {
        // Last page of the table: compare the real local row count with live's
        $countRes = @mysqli_query($mysqli, 'SELECT COUNT(*) FROM `' . str_replace('`', '``', $table) . '`');
        $localCount = $countRes ? (int) mysqli_fetch_row($countRes)[0] : null;
        if ($localCount !== null && $tableTotal !== null && $sinceRaw === '' && $localCount < $tableTotal) {
            $status = 'warning';
            $message = number_format($localCount) . ' local rows vs ' . number_format($tableTotal) . ' on live - the live table probably has rows sharing a unique key that merge locally.';
        }
    }
    mysqli_close($mysqli);

    echo json_encode(['status' => $status, 'message' => $message, 'page_rows' => $pageRows, 'applied' => $applied, 'local_count' => $localCount]);
    exit;
}

if ($action === 'run_import') {
    $tablesRaw = $_POST['tables'] ?? [];
    $tables = is_array($tablesRaw) ? array_values(array_filter(array_map('trim', $tablesRaw))) : [];
    if (empty($tables)) {
        echo json_encode(['status' => 'error', 'message' => 'Select at least one table.']);
        exit;
    }

    $truncateFirst = !empty($_POST['truncate']) && $_POST['truncate'] !== '0';
    $sinceRaw = trim((string) ($_POST['since'] ?? ''));
    if ($truncateFirst && $sinceRaw !== '') {
        echo json_encode(['status' => 'error', 'message' => '"Only rows changed since" cannot be combined with Truncate - a delta pull after wiping the table would delete the rest of the data.']);
        exit;
    }

    set_time_limit(0);
    // Finish the page (and save the checkpoint) even if the browser goes away mid-request
    ignore_user_abort(true);

    // Batch info for the checkpoint - the client sends the whole queue with every page
    $queueRaw = json_decode((string) ($_POST['queue'] ?? ''), true);
    $queue = is_array($queueRaw) ? array_values(array_filter(array_map('strval', $queueRaw))) : $tables;
    $importedBefore = max(0, (int) ($_POST['imported'] ?? 0));
    $checkpoint = [
        'base_url' => $baseUrl,
        'queue' => $queue,
        'table' => $tables[0],
        'cursor' => trim((string) ($_POST['cursor'] ?? '')),
        'table_total' => isset($_POST['table_total']) ? (int) $_POST['table_total'] : null,
        'imported' => $importedBefore,
        'table_done' => false,
        'truncate' => $truncateFirst,
        'since' => $sinceRaw,
        'export_key' => $exportKey,
    ];

    $postFields = ['export_key' => $exportKey, 'tables' => implode(',', $tables)];
    if ($sinceRaw !== '') {
        $postFields['since'] = $sinceRaw;
    }

    // Paged pull (one table, page_size rows per request) - keeps each live response small
    // enough to finish inside the live host's time limit on million-row tables.
    $pageSize = max(0, min(100000, (int) ($_POST['page_size'] ?? 0)));
    $pageCursor = trim((string) ($_POST['cursor'] ?? ''));
    $knownTotal = isset($_POST['table_total']) ? (int) $_POST['table_total'] : null;
    if ($pageSize > 0 && count($tables) === 1) {
        $postFields['page_size'] = $pageSize;
        if ($pageCursor !== '') {
            $postFields['cursor'] = $pageCursor;
        }
    } else {
        $pageSize = 0;
    }

    // 15 min cap so a hung live request can't block this worker forever
    $pageToken = (string) ($_POST['page_token'] ?? '');
    dbir_progress(['token' => $pageToken, 'table' => $tables[0], 'phase' => 'download', 'bytes' => 0, 'applied' => 0], true);
    $result = db_import_remote_curl($baseUrl . '/download_db_export.php', $postFields, 900, function (int $bytes) {
        dbir_progress(['bytes' => $bytes]);
    });
    dbir_progress(['phase' => 'received', 'bytes' => strlen((string) $result['body'])], true);

    if ($result['error']) {
        echo json_encode(['status' => 'error', 'message' => 'Fetch failed: ' . $result['error']]);
        exit;
    }
    if ($result['http_code'] !== 200) {
        echo json_encode(['status' => 'error', 'message' => 'Live server returned HTTP ' . $result['http_code'] . ': ' . substr((string) $result['body'], 0, 300)]);
        exit;
    }

    $sql = (string) $result['body'];
    if (trim($sql) === '') {
        echo json_encode(['status' => 'error', 'message' => 'Live server returned an empty export.']);
        exit;
    }

    // download_db_export.php always ends its output with a "-- NEW_EXPORT_KEY: ..." line
    // (the rotated one-time key, see below) once every selected table has been fully
    // written out. If it's missing, the download was cut short (host execution/time
    // limit, WAF, dropped connection) partway through a table's INSERT block - applying
    // it as-is would silently import a partial table while still reporting "success",
    // which is worse than truncate mode ever running: the truncated table would be left
    // with less data than before.
    if (!preg_match('/SET FOREIGN_KEY_CHECKS=1;/', $sql) || !preg_match('/^-- NEW_EXPORT_KEY: (\S+)\s*$/m', $sql, $keyMatch)) {
        echo json_encode([
            'status' => 'error',
            'message' => 'The download from the live server looks incomplete (it was cut off before finishing) - nothing was imported. This is usually a live-server timeout on large tables; try selecting fewer tables per run.' .
                ($truncateFirst ? ' Any tables you already truncated are now EMPTY - re-run the import for them before using the app.' : ''),
        ]);
        exit;
    }
    // The importer pulls one table per request and this key is single-use (rotated
    // server-side on every successful export), so the NEXT request in the batch must use
    // this rotated value - hand it back to the client, which threads it through.
    $rotatedExportKey = $keyMatch[1];
    // Persist the rotated key BEFORE applying anything: if this request dies while
    // applying, a resume re-pulls the same page (upserts are idempotent) with this key.
    $checkpoint['export_key'] = $rotatedExportKey;
    dbir_checkpoint_save($conDB, $checkpoint);

    // download_db_export.php prints "-- Table: name (N rows)" per table it dumps -
    // reuse that instead of re-deriving counts, so the reported numbers always match
    // exactly what was pulled from live.
    preg_match_all('/^-- Table: (\S+) \((\d+) rows\)/m', $sql, $tableCountMatches, PREG_SET_ORDER);
    $tableCounts = [];
    foreach ($tableCountMatches as $m) {
        $tableCounts[$m[1]] = (int) $m[2];
    }
    $totalRecords = array_sum($tableCounts);

    // "-- PAGE: rows=N next=X more=0|1" - only present when the live server supports paging.
    // An older live download_db_export.php ignores page_size and sends the whole table, so
    // no marker = treat it as a complete, single-shot export (previous behavior).
    $paging = null;
    if ($pageSize > 0 && preg_match('/^-- PAGE: rows=(\d+) next=(-?\d+) more=([01])\s*$/m', $sql, $pm)) {
        $paging = ['page_rows' => (int) $pm[1], 'next_cursor' => $pm[2], 'has_more' => $pm[3] === '1'];
        $totalRecords = $paging['page_rows'];
    }
    $isLastPage = $paging === null || !$paging['has_more'];

    $mysqli = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if (!$mysqli) {
        echo json_encode(['status' => 'error', 'message' => 'Could not connect to local database: ' . mysqli_connect_error()]);
        exit;
    }

    $statementsRun = 0;
    $errors = [];
    // mysqli_info() returns a string like "Records: 482  Duplicates: 480  Warnings: 0" for
    // INSERT/UPDATE statements specifically - captured per statement so a shortfall (fewer
    // local rows than the dump claimed, despite zero SQL errors) can be explained: a high
    // Duplicates count means most rows collapsed into each other via ON DUPLICATE KEY UPDATE,
    // which happens when the source data has many rows sharing the same unique key value.
    $insertDiagnostics = [];

    // One transaction per page: a page lands completely or not at all, so the saved
    // cursor always matches what is really in the table. Also far fewer log flushes.
    $sql = "START TRANSACTION;\n" . $sql . "\nCOMMIT;\n";
    $appliedRows = 0;
    dbir_progress(['phase' => 'apply', 'applied' => 0], true);

    if (mysqli_multi_query($mysqli, $sql)) {
        do {
            if ($res = mysqli_store_result($mysqli)) {
                mysqli_free_result($res);
            }
            if (mysqli_errno($mysqli)) {
                $errors[] = mysqli_error($mysqli);
            } else {
                $statementsRun++;
                $info = mysqli_info($mysqli);
                if ($info) {
                    $insertDiagnostics[] = $info;
                    if (preg_match('/Records:\s*(\d+)/', $info, $rm)) {
                        $appliedRows += (int) $rm[1];
                        dbir_progress(['applied' => $appliedRows]);
                    }
                }
            }
        } while (mysqli_more_results($mysqli) && mysqli_next_result($mysqli));
    } else {
        $errors[] = mysqli_error($mysqli);
    }

    dbir_progress(['phase' => empty($errors) ? 'committed' : 'failed', 'applied' => $appliedRows], true);

    if (!empty($errors)) {
        mysqli_close($mysqli);
        echo json_encode([
            'status' => 'error',
            'message' => 'Import completed with errors.',
            'statements_run' => $statementsRun,
            'errors' => array_slice($errors, 0, 20),
            'insert_diagnostics' => $insertDiagnostics,
            // The live server already rotated its key once the download completed, before
            // these local SQL errors were hit - the caller must move to this value for the
            // next table's request, or every table after this one will fail auth too.
            'next_export_key' => $rotatedExportKey,
        ]);
        exit;
    }

    $checkpoint['imported'] = $importedBefore + (int) $totalRecords;
    if ($tableCounts && isset($tableCounts[$tables[0]])) {
        $checkpoint['table_total'] = $tableCounts[$tables[0]];
    }
    if (!$isLastPage) {
        $checkpoint['cursor'] = (string) $paging['next_cursor'];
    } else {
        $checkpoint['cursor'] = '';
        $checkpoint['table_done'] = true;
    }
    dbir_checkpoint_save($conDB, $checkpoint);

    // Middle page of a paged pull - nothing to verify yet, hand the cursor back.
    if (!$isLastPage) {
        mysqli_close($mysqli);
        echo json_encode([
            'status' => 'success',
            'paged' => true,
            'has_more' => true,
            'next_cursor' => $paging['next_cursor'],
            'page_rows' => $paging['page_rows'],
            'table_counts' => $tableCounts,
            'next_export_key' => $rotatedExportKey,
        ]);
        exit;
    }
    // Last page: the live total came from page one, the client passes it back
    if ($paging !== null && $knownTotal !== null && count($tables) === 1 && !isset($tableCounts[$tables[0]])) {
        $tableCounts[$tables[0]] = $knownTotal;
    }

    // Verify against the actual local table, not just the SQL that was sent - if a
    // table came back short of what the dump claimed, surface it instead of trusting
    // a blanket "success".
    $localCounts = [];
    $shortfallTables = [];
    foreach ($tables as $table) {
        $ident = '`' . str_replace('`', '``', $table) . '`';
        $countRes = @mysqli_query($mysqli, "SELECT COUNT(*) AS c FROM $ident");
        if ($countRes) {
            $localCounts[$table] = (int) mysqli_fetch_assoc($countRes)['c'];
            if (isset($tableCounts[$table]) && $sinceRaw === '' && $localCounts[$table] < $tableCounts[$table]) {
                $shortfallTables[] = $table;
            }
        }
    }

    mysqli_close($mysqli);

    $message = 'Imported ' . number_format($totalRecords) . ' record(s) across ' . count($tables) . ' table(s) into local database.';
    if ($truncateFirst) {
        $message .= ' Tables were truncated first - local data now mirrors live exactly.';
    }
    if (!empty($shortfallTables)) {
        $message .= ' WARNING: ' . implode(', ', $shortfallTables) . ' ended up with fewer local rows than the live export reported.';
        if (!empty($insertDiagnostics)) {
            // e.g. "Records: 482  Duplicates: 480  Warnings: 0" - a high Duplicates count
            // means most of the exported rows share a unique key value with an earlier row
            // in the same batch and collapsed into it via ON DUPLICATE KEY UPDATE, instead of
            // landing as separate rows - almost always a data issue on the live table itself
            // (duplicate values in whatever column the unique key covers), not an import bug.
            // Sum the per-statement "Records: N Duplicates: D" lines into one figure
            $recSum = 0;
            $dupSum = 0;
            foreach ($insertDiagnostics as $info) {
                if (preg_match('/Records:\s*(\d+)\s+Duplicates:\s*(\d+)/', $info, $dm)) {
                    $recSum += (int) $dm[1];
                    $dupSum += (int) $dm[2];
                }
            }
            $message .= $dupSum > 0
                ? ' Last batch: ' . number_format($dupSum) . ' of ' . number_format($recSum) . ' rows were duplicates of an existing unique key - the LIVE table has duplicate rows that the local unique key merges.'
                : ' (' . implode('; ', $insertDiagnostics) . ')';
        } else {
            $message .= ' Check the import for that table.';
        }
    }

    echo json_encode([
        'status' => empty($shortfallTables) ? 'success' : 'warning',
        'message' => $message,
        'tables' => $tables,
        'table_counts' => $tableCounts,
        'local_counts' => $localCounts,
        'total_records' => $totalRecords,
        'statements_run' => $statementsRun,
        'shortfall_tables' => $shortfallTables,
        'insert_diagnostics' => $insertDiagnostics,
        'next_export_key' => $rotatedExportKey,
        'paged' => $paging !== null,
        'has_more' => false,
        'page_rows' => $paging !== null ? $paging['page_rows'] : null,
    ]);
    exit;
}

if ($action === 'check_schema') {
    $tablesRaw = $_POST['tables'] ?? [];
    $tables = is_array($tablesRaw) ? array_values(array_filter(array_map('trim', $tablesRaw))) : [];
    if (empty($tables)) {
        echo json_encode(['status' => 'error', 'message' => 'Select at least one table.']);
        exit;
    }

    $result = db_import_remote_curl($baseUrl . '/includes/api/db_export_schema.php', [
        'export_key' => $exportKey,
        'tables' => implode(',', $tables),
    ], 30);

    if ($result['error']) {
        echo json_encode(['status' => 'error', 'message' => 'Connection failed: ' . $result['error']]);
        exit;
    }
    $data = json_decode((string) $result['body'], true);
    if ($result['http_code'] !== 200 || !is_array($data) || ($data['status'] ?? '') !== 'success') {
        echo json_encode(['status' => 'error', 'message' => ($data['message'] ?? null) ?: ('Live server returned HTTP ' . $result['http_code'] . '. If this is a 404, upload the new includes/api/db_export_schema.php file to the live server first.')]);
        exit;
    }
    $liveSchema = $data['schema'] ?? [];
    $liveIndexes = $data['indexes'] ?? null; // null = live has an older db_export_schema.php

    $localTableNames = [];
    $namesRes = mysqli_query($conDB, 'SHOW TABLES');
    while ($row = mysqli_fetch_array($namesRes)) {
        $localTableNames[$row[0]] = true;
    }

    $localColsStmt = mysqli_prepare($conDB, "SELECT COLUMN_NAME, COLUMN_TYPE, IS_NULLABLE, COLUMN_KEY, COLUMN_DEFAULT, EXTRA
        FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?
        ORDER BY ORDINAL_POSITION");

    $report = [];
    $anyMismatch = false;
    foreach ($tables as $table) {
        $liveCols = $liveSchema[$table] ?? [];
        $liveByName = [];
        foreach ($liveCols as $c) {
            $liveByName[$c['name']] = $c;
        }

        if (!isset($localTableNames[$table])) {
            $liveOrderAll = array_keys($liveByName);
            $missingInLocalAll = array_values(array_map(function ($name) use ($liveByName, $liveOrderAll) {
                $col = $liveByName[$name];
                $col['after'] = db_import_remote_predecessor($liveOrderAll, $name);
                return $col;
            }, $liveOrderAll));
            $report[$table] = [
                'exists_locally' => false,
                'missing_in_local' => $missingInLocalAll,
                'missing_in_live' => [],
                'type_mismatches' => [],
            ];
            if (!empty($liveByName)) {
                $anyMismatch = true;
            }
            continue;
        }

        mysqli_stmt_bind_param($localColsStmt, 's', $table);
        mysqli_stmt_execute($localColsStmt);
        $localRes = mysqli_stmt_get_result($localColsStmt);
        $localByName = [];
        while ($row = mysqli_fetch_assoc($localRes)) {
            $localByName[$row['COLUMN_NAME']] = $row;
        }

        $missingInLocalNames = array_diff(array_keys($liveByName), array_keys($localByName));
        $missingInLiveNames = array_diff(array_keys($localByName), array_keys($liveByName));

        $liveOrder = array_keys($liveByName);
        $localOrder = array_keys($localByName);

        $missingInLocal = array_values(array_map(function ($name) use ($liveByName, $liveOrder) {
            $col = $liveByName[$name];
            $col['after'] = db_import_remote_predecessor($liveOrder, $name);
            return $col;
        }, $missingInLocalNames));
        $missingInLive = array_values(array_map(function ($name) use ($localByName, $localOrder) {
            $c = $localByName[$name];
            return [
                'name' => $c['COLUMN_NAME'],
                'type' => $c['COLUMN_TYPE'],
                'nullable' => $c['IS_NULLABLE'] === 'YES',
                'default' => $c['COLUMN_DEFAULT'],
                'extra' => $c['EXTRA'],
                'after' => db_import_remote_predecessor($localOrder, $name),
            ];
        }, $missingInLiveNames));

        $typeMismatches = [];
        foreach ($liveByName as $name => $liveCol) {
            if (!isset($localByName[$name])) {
                continue;
            }
            $localCol = $localByName[$name];
            if ($localCol['COLUMN_TYPE'] !== $liveCol['type'] || ($localCol['IS_NULLABLE'] === 'YES') !== $liveCol['nullable']) {
                $typeMismatches[] = [
                    'column' => $name,
                    'local_type' => $localCol['COLUMN_TYPE'] . ($localCol['IS_NULLABLE'] === 'YES' ? ' NULL' : ' NOT NULL'),
                    'live_type' => $liveCol['type'] . ($liveCol['nullable'] ? ' NULL' : ' NOT NULL'),
                ];
            }
        }

        $idxMissingLive = [];
        $idxMissingLocal = [];
        if (is_array($liveIndexes)) {
            $localIdx = array_values(dbss_table_indexes($conDB, $table));
            $liveIdx = $liveIndexes[$table] ?? [];
            $toItem = fn ($i) => ['name' => $i['name'], 'unique' => !empty($i['unique']), 'label' => dbir_index_label($i)];
            $idxMissingLive = array_map($toItem, dbir_index_missing($localIdx, $liveIdx));
            $idxMissingLocal = array_map($toItem, dbir_index_missing($liveIdx, $localIdx));
        }

        if (!empty($missingInLocal) || !empty($missingInLive) || !empty($typeMismatches) || $idxMissingLive || $idxMissingLocal) {
            $anyMismatch = true;
        }

        $report[$table] = [
            'exists_locally' => true,
            'missing_in_local' => $missingInLocal,
            'missing_in_live' => $missingInLive,
            'type_mismatches' => $typeMismatches,
            'index_missing_in_live' => $idxMissingLive,
            'index_missing_in_local' => $idxMissingLocal,
        ];
    }

    echo json_encode([
        'status' => 'success',
        'indexes_checked' => is_array($liveIndexes),
        'any_mismatch' => $anyMismatch,
        'report' => $report,
    ]);
    exit;
}

// Turns the per-column choices ticked in the schema modal into concrete operations
// for each side. Column definitions are re-read from information_schema (local) and
// db_export_schema.php (live) - the browser only says WHICH change, never the SQL.
// Returns ['local' => [items], 'live' => [items]], item = [table, column, choice, op, sql, error].
function db_import_remote_schema_plan(mysqli $conDB, string $baseUrl, string $exportKey, array $changes): array
{
    $byTable = [];
    foreach ($changes as $c) {
        $table = (string) ($c['table'] ?? '');
        $choice = (string) ($c['choice'] ?? '');
        if (!dbss_valid_ident($table) || !in_array($choice, ['create_local', 'add_local', 'drop_local', 'modify_local', 'add_live', 'remove_live', 'modify_live', 'add_index_live', 'add_index_local'], true)) {
            continue;
        }
        $byTable[$table][] = ['column' => (string) ($c['column'] ?? ''), 'choice' => $choice];
    }
    if (!$byTable) {
        return ['error' => 'Select at least one change.'];
    }

    $result = db_import_remote_curl($baseUrl . '/includes/api/db_export_schema.php', [
        'export_key' => $exportKey,
        'tables' => implode(',', array_keys($byTable)),
        'include_create' => 1,
    ], 30);
    $data = json_decode((string) $result['body'], true);
    if ($result['error'] || $result['http_code'] !== 200 || !is_array($data) || ($data['status'] ?? '') !== 'success') {
        return ['error' => $result['error'] ? 'Connection failed: ' . $result['error'] : (($data['message'] ?? null) ?: 'Live server returned HTTP ' . $result['http_code'] . '.')];
    }
    $liveSchema = $data['schema'] ?? [];
    $liveCreate = $data['create_sql'] ?? [];
    $liveIndexes = $data['indexes'] ?? [];

    $localStmt = mysqli_prepare($conDB, "SELECT COLUMN_NAME, COLUMN_TYPE, IS_NULLABLE, COLUMN_DEFAULT, EXTRA
        FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? ORDER BY ORDINAL_POSITION");

    $plan = ['local' => [], 'live' => []];
    $rank = ['create_local' => 0, 'add_local' => 1, 'add_live' => 1, 'modify_local' => 2, 'modify_live' => 2, 'drop_local' => 3, 'remove_live' => 3, 'add_index_local' => 4, 'add_index_live' => 4];

    foreach ($byTable as $table => $items) {
        $live = [];
        foreach (($liveSchema[$table] ?? []) as $c) {
            $live[$c['name']] = ['name' => $c['name'], 'type' => $c['type'], 'nullable' => (bool) $c['nullable'], 'default' => $c['default'], 'extra' => $c['extra']];
        }
        mysqli_stmt_bind_param($localStmt, 's', $table);
        mysqli_stmt_execute($localStmt);
        $res = mysqli_stmt_get_result($localStmt);
        $local = [];
        while ($r = mysqli_fetch_assoc($res)) {
            $local[$r['COLUMN_NAME']] = ['name' => $r['COLUMN_NAME'], 'type' => $r['COLUMN_TYPE'], 'nullable' => $r['IS_NULLABLE'] === 'YES', 'default' => $r['COLUMN_DEFAULT'], 'extra' => $r['EXTRA']];
        }
        $liveOrder = array_keys($live);
        $localOrder = array_keys($local);

        // Adds run in source-column order so AFTER can point at a column added just before
        usort($items, function ($a, $b) use ($rank, $liveOrder, $localOrder) {
            if ($rank[$a['choice']] !== $rank[$b['choice']]) {
                return $rank[$a['choice']] <=> $rank[$b['choice']];
            }
            $order = $a['choice'] === 'add_live' ? $localOrder : $liveOrder;
            return (int) array_search($a['column'], $order, true) <=> (int) array_search($b['column'], $order, true);
        });

        $localHas = array_fill_keys($localOrder, true);
        $liveHas = array_fill_keys($liveOrder, true);

        // Nearest column before $name in $order that already exists on the target side
        $after = function (array $order, string $name, array $targetHas) {
            $prev = null;
            foreach ($order as $n) {
                if ($n === $name) {
                    return $prev;
                }
                if (isset($targetHas[$n])) {
                    $prev = $n;
                }
            }
            return $prev;
        };

        foreach ($items as $it) {
            $col = $it['column'];
            $choice = $it['choice'];
            $side = substr($choice, -5) === '_live' ? 'live' : 'local';
            $op = null;
            $err = null;

            switch ($choice) {
                case 'create_local':
                    if ($localOrder) {
                        $err = 'Table already exists locally.';
                    } elseif (empty($liveCreate[$table])) {
                        $err = 'Live server did not send CREATE TABLE - upload the latest includes/api/db_export_schema.php to live.';
                    } else {
                        $op = ['op' => 'create', 'table' => $table, 'create_sql' => $liveCreate[$table]];
                    }
                    break;
                case 'add_local':
                    if (!isset($live[$col]) || isset($local[$col])) {
                        $err = 'Column is no longer missing locally.';
                    } elseif (!$localOrder) {
                        $err = 'Table does not exist locally - create it first.';
                    } else {
                        $op = ['op' => 'add', 'table' => $table, 'column' => $live[$col], 'after' => $after($liveOrder, $col, $localHas)];
                        $localHas[$col] = true;
                    }
                    break;
                case 'add_live':
                    if (!isset($local[$col]) || isset($live[$col])) {
                        $err = 'Column is no longer missing on live.';
                    } else {
                        $op = ['op' => 'add', 'table' => $table, 'column' => $local[$col], 'after' => $after($localOrder, $col, $liveHas)];
                        $liveHas[$col] = true;
                    }
                    break;
                case 'modify_local':
                case 'modify_live':
                    if (!isset($local[$col], $live[$col])) {
                        $err = 'Column must exist on both sides.';
                    } else {
                        $op = ['op' => 'modify', 'table' => $table, 'column' => $choice === 'modify_local' ? $live[$col] : $local[$col]];
                    }
                    break;
                case 'drop_local':
                    if (!isset($local[$col])) {
                        $err = 'Column does not exist locally.';
                    } else {
                        $op = ['op' => 'drop', 'table' => $table, 'column' => ['name' => $col]];
                    }
                    break;
                case 'remove_live':
                    if (!isset($live[$col])) {
                        $err = 'Column does not exist on live.';
                    } else {
                        $op = ['op' => 'drop', 'table' => $table, 'column' => ['name' => $col]];
                    }
                    break;
                case 'add_index_live':
                case 'add_index_local':
                    // $col carries the index name; its definition is read from the side that has it
                    $source = $choice === 'add_index_live'
                        ? dbss_table_indexes($conDB, $table)
                        : array_column($liveIndexes[$table] ?? [], null, 'name');
                    if (!isset($source[$col])) {
                        $err = 'Index no longer exists on the ' . ($choice === 'add_index_live' ? 'local' : 'live') . ' side.';
                    } else {
                        $op = ['op' => 'add_index', 'table' => $table, 'index' => $source[$col]];
                    }
                    break;
            }

            $sql = null;
            if ($op && $op['op'] === 'add_index') {
                [$sql, $err] = dbss_add_index_preview($op);
                if ($err) {
                    $op = null;
                }
            } elseif ($op) {
                [$sql, $err] = dbss_build_sql($op);
                if ($err) {
                    $op = null;
                }
            }
            $plan[$side][] = ['table' => $table, 'column' => $col, 'choice' => $choice, 'op' => $op, 'sql' => $sql, 'error' => $err];
        }
    }
    mysqli_stmt_close($localStmt);
    return $plan;
}

function db_import_remote_plan_public(array $items): array
{
    return array_map(fn ($i) => ['table' => $i['table'], 'column' => $i['column'], 'choice' => $i['choice'], 'sql' => $i['sql'], 'error' => $i['error']], $items);
}

if ($action === 'schema_plan' || $action === 'apply_schema') {
    $changes = json_decode((string) ($_POST['changes'] ?? ''), true);
    if (!is_array($changes) || !$changes) {
        echo json_encode(['status' => 'error', 'message' => 'Select at least one change.']);
        exit;
    }
    $plan = db_import_remote_schema_plan($conDB, $baseUrl, $exportKey, $changes);
    if (isset($plan['error'])) {
        echo json_encode(['status' => 'error', 'message' => $plan['error']]);
        exit;
    }

    if ($action === 'schema_plan') {
        echo json_encode(['status' => 'success', 'local' => db_import_remote_plan_public($plan['local']), 'live' => db_import_remote_plan_public($plan['live'])]);
        exit;
    }

    $target = ($_POST['target'] ?? '') === 'live' ? 'live' : 'local';
    $items = $plan[$target];
    $ops = array_values(array_filter(array_map(fn ($i) => $i['op'], $items)));
    $skipped = array_values(array_filter($items, fn ($i) => $i['op'] === null));
    $skippedResults = array_map(fn ($i) => ['ok' => false, 'sql' => $i['sql'], 'error' => $i['error'], 'table' => $i['table']], $skipped);

    if (!$ops) {
        echo json_encode(['status' => 'error', 'message' => 'Nothing to apply on ' . $target . '.', 'results' => $skippedResults]);
        exit;
    }

    // Index rebuilds (copy-swap) can take minutes on big tables
    set_time_limit(0);
    ignore_user_abort(true);

    $nextKey = null;
    if ($target === 'local') {
        $results = dbss_apply($conDB, $ops);
    } else {
        $result = db_import_remote_curl($baseUrl . '/includes/api/db_apply_schema.php', [
            'export_key' => $exportKey,
            'ops' => json_encode($ops),
        ], 3600); // a unique key on a big table with duplicates is a table rebuild
        $data = json_decode((string) $result['body'], true);
        if ($result['error'] || !is_array($data) || !isset($data['results'])) {
            $msg = $result['error'] ? 'Connection failed: ' . $result['error'] : (($data['message'] ?? null) ?: 'Live server returned HTTP ' . $result['http_code'] . '.');
            if ($result['http_code'] === 404) {
                $msg .= ' Upload includes/api/db_apply_schema.php and includes/db_schema_sync.php to the live server first.';
            }
            echo json_encode(['status' => 'error', 'message' => $msg, 'next_export_key' => $data['next_export_key'] ?? null]);
            exit;
        }
        $results = $data['results'];
        $nextKey = $data['next_export_key'] ?? null;
    }

    $results = array_merge($results, $skippedResults);
    $failed = count(array_filter($results, fn ($r) => empty($r['ok'])));
    echo json_encode([
        'status' => $failed ? ($failed === count($results) ? 'error' : 'warning') : 'success',
        'message' => $failed ? "$failed of " . count($results) . " change(s) failed on $target." : count($results) . " change(s) applied on $target.",
        'results' => $results,
        'next_export_key' => $nextKey,
    ]);
    exit;
}

echo json_encode(['status' => 'error', 'message' => 'Unknown action.']);
