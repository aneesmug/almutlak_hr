<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../session_check.php';

if (!($is_system_admin ?? false)) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

function db_import_remote_curl(string $url, array $postFields, int $timeoutSeconds) {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => http_build_query($postFields),
        CURLOPT_RETURNTRANSFER => true,
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
    $body = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    return ['body' => $body, 'http_code' => $httpCode, 'error' => $error];
}

function db_import_remote_normalize_base_url(string $raw): ?string {
    $raw = trim($raw);
    if ($raw === '' || !preg_match('#^https?://#i', $raw)) {
        return null;
    }
    return rtrim($raw, '/');
}

$action = $_POST['action'] ?? '';
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
    $postFields = ['export_key' => $exportKey, 'tables' => implode(',', $tables)];
    if ($sinceRaw !== '') {
        $postFields['since'] = $sinceRaw;
    }

    $result = db_import_remote_curl($baseUrl . '/download_db_export.php', $postFields, 0);

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

    // download_db_export.php always ends its output with this line once every
    // selected table has been fully written out. If it's missing, the download was
    // cut short (host execution/time limit, WAF, dropped connection) partway through
    // a table's INSERT block - applying it as-is would silently import a partial
    // table while still reporting "success", which is worse than truncate mode ever
    // running: the truncated table would be left with less data than before.
    if (!preg_match('/SET FOREIGN_KEY_CHECKS=1;\s*$/', $sql)) {
        echo json_encode([
            'status' => 'error',
            'message' => 'The download from the live server looks incomplete (it was cut off before finishing) - nothing was imported. This is usually a live-server timeout on large tables; try selecting fewer tables per run.' .
                ($truncateFirst ? ' Any tables you already truncated are now EMPTY - re-run the import for them before using the app.' : ''),
        ]);
        exit;
    }

    // download_db_export.php prints "-- Table: name (N rows)" per table it dumps -
    // reuse that instead of re-deriving counts, so the reported numbers always match
    // exactly what was pulled from live.
    preg_match_all('/^-- Table: (\S+) \((\d+) rows\)/m', $sql, $tableCountMatches, PREG_SET_ORDER);
    $tableCounts = [];
    foreach ($tableCountMatches as $m) {
        $tableCounts[$m[1]] = (int) $m[2];
    }
    $totalRecords = array_sum($tableCounts);

    $mysqli = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    if (!$mysqli) {
        echo json_encode(['status' => 'error', 'message' => 'Could not connect to local database: ' . mysqli_connect_error()]);
        exit;
    }

    $statementsRun = 0;
    $errors = [];

    if (mysqli_multi_query($mysqli, $sql)) {
        do {
            if ($res = mysqli_store_result($mysqli)) {
                mysqli_free_result($res);
            }
            if (mysqli_errno($mysqli)) {
                $errors[] = mysqli_error($mysqli);
            } else {
                $statementsRun++;
            }
        } while (mysqli_more_results($mysqli) && mysqli_next_result($mysqli));
    } else {
        $errors[] = mysqli_error($mysqli);
    }

    if (!empty($errors)) {
        mysqli_close($mysqli);
        echo json_encode([
            'status' => 'error',
            'message' => 'Import completed with errors.',
            'statements_run' => $statementsRun,
            'errors' => array_slice($errors, 0, 20),
        ]);
        exit;
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
        $message .= ' WARNING: ' . implode(', ', $shortfallTables) . ' ended up with fewer local rows than the live export reported - check the import for that table.';
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
    ]);
    exit;
}

echo json_encode(['status' => 'error', 'message' => 'Unknown action.']);
