<?php
// Callable as a server-to-server API (another server pulling data), so it cannot
// depend on session_check.php - a remote caller has no browser session/cookie for
// this server. The export key is the sole gate here; db_export.php (which shows/
// generates the key) is the only piece of this feature that requires an admin login.
require_once __DIR__ . '/includes/db.php';

$configuredKey = trim((string) get_setting($conDB, 'db_export_secret_key'));
$providedKey = trim((string) ($_POST['export_key'] ?? $_GET['export_key'] ?? ''));

if ($configuredKey === '' || $providedKey === '' || !hash_equals($configuredKey, $providedKey)) {
    http_response_code(403);
    header('Content-Type: text/plain');
    echo 'Invalid or missing export key.';
    exit;
}

$onlyTablesRaw = trim((string) ($_POST['tables'] ?? $_GET['tables'] ?? ''));
$onlyTables = $onlyTablesRaw !== '' ? array_values(array_filter(array_map('trim', explode(',', $onlyTablesRaw)))) : [];

$sinceRaw = trim((string) ($_POST['since'] ?? $_GET['since'] ?? ''));
$since = '';
if ($sinceRaw !== '') {
    $sinceTs = strtotime($sinceRaw);
    if ($sinceTs !== false) {
        $since = date('Y-m-d H:i:s', $sinceTs);
    }
}

function db_export_quote_ident(string $name): string {
    return '`' . str_replace('`', '``', $name) . '`';
}

function db_export_table(PDO $pdo, string $table, string $since): void {
    $tIdent = db_export_quote_ident($table);

    $colsStmt = $pdo->prepare("SELECT COLUMN_NAME, DATA_TYPE FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :t ORDER BY ORDINAL_POSITION");
    $colsStmt->execute([':t' => $table]);
    $columnRows = $colsStmt->fetchAll(PDO::FETCH_ASSOC);
    if (empty($columnRows)) {
        return;
    }
    $columns = array_column($columnRows, 'COLUMN_NAME');
    $dataTypes = array_combine($columns, array_column($columnRows, 'DATA_TYPE'));

    $keysStmt = $pdo->query("SHOW KEYS FROM $tIdent");
    $keys = $keysStmt->fetchAll(PDO::FETCH_ASSOC);
    $primaryCols = [];
    $hasUniqueKey = false;
    foreach ($keys as $k) {
        if ((int) $k['Non_unique'] === 0) {
            $hasUniqueKey = true;
            if ($k['Key_name'] === 'PRIMARY') {
                $primaryCols[] = $k['Column_name'];
            }
        }
    }

    $whereSql = '';
    if ($since !== '') {
        if (in_array('updated_at', $columns, true)) {
            $whereSql = ' WHERE `updated_at` >= ' . $pdo->quote($since);
        } elseif (in_array('created_at', $columns, true)) {
            $whereSql = ' WHERE `created_at` >= ' . $pdo->quote($since);
        }
    }

    $countStmt = $pdo->query("SELECT COUNT(*) FROM $tIdent" . $whereSql);
    $totalRows = (int) $countStmt->fetchColumn();
    if ($totalRows === 0) {
        return;
    }

    echo "-- Table: $table ($totalRows rows)\n";

    $colIdentList = implode(', ', array_map('db_export_quote_ident', $columns));
    $updateCols = array_diff($columns, $primaryCols);
    $updateSql = '';
    if ($hasUniqueKey && !empty($updateCols)) {
        $updateSql = ' ON DUPLICATE KEY UPDATE ' . implode(', ', array_map(function ($c) {
            $ci = db_export_quote_ident($c);
            return "$ci = VALUES($ci)";
        }, $updateCols));
    }

    $insertVerb = $hasUniqueKey ? 'INSERT INTO' : 'INSERT IGNORE INTO';
    if (!$hasUniqueKey) {
        echo "-- WARNING: $table has no primary/unique key - using INSERT IGNORE (re-imports will not update changed rows)\n";
    }

    $integerTypes = ['int', 'bigint', 'mediumint', 'smallint', 'tinyint'];
    $singlePk = (count($primaryCols) === 1 && in_array($dataTypes[$primaryCols[0]] ?? '', $integerTypes, true))
        ? $primaryCols[0]
        : null;

    $chunkSize = 500;
    $offset = 0;
    $lastPkValue = 0;

    while (true) {
        if ($singlePk !== null) {
            $pkIdent = db_export_quote_ident($singlePk);
            $sql = "SELECT * FROM $tIdent" . $whereSql
                . ($whereSql === '' ? ' WHERE ' : ' AND ') . "$pkIdent > :lastPk"
                . " ORDER BY $pkIdent ASC LIMIT $chunkSize";
            $stmt = $pdo->prepare($sql);
            $stmt->bindValue(':lastPk', $lastPkValue, PDO::PARAM_INT);
            $stmt->execute();
        } else {
            $stmt = $pdo->query("SELECT * FROM $tIdent" . $whereSql . " LIMIT $chunkSize OFFSET $offset");
        }

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if (empty($rows)) {
            break;
        }

        $valueGroups = [];
        foreach ($rows as $row) {
            $values = [];
            foreach ($columns as $col) {
                $v = $row[$col];
                $values[] = $v === null ? 'NULL' : $pdo->quote((string) $v);
            }
            $valueGroups[] = '(' . implode(', ', $values) . ')';
            if ($singlePk !== null) {
                $lastPkValue = $row[$singlePk];
            }
        }

        echo "$insertVerb $tIdent ($colIdentList) VALUES\n" . implode(",\n", $valueGroups) . "$updateSql;\n";

        $offset += $chunkSize;
        unset($rows, $valueGroups);

        if (ob_get_level() > 0) {
            @ob_flush();
        }
        flush();
    }

    echo "\n";
}

set_time_limit(0);
ignore_user_abort(true);
@ini_set('memory_limit', '512M');

while (ob_get_level() > 0) {
    ob_end_clean();
}

$pdo = getDbConnection();
$currentDbName = $pdo->query('SELECT DATABASE()')->fetchColumn();

$filename = 'almutlak_export_' . date('Ymd_His') . '.sql';
header('Content-Type: application/sql; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: no-store');
header('X-Accel-Buffering: no');

echo "-- Almutlak HR DB diff-export\n";
echo "-- Generated: " . date('Y-m-d H:i:s') . " by emp_id " . ($empid ?? '') . "\n";
echo "-- Import with: mysql -u USER -p DBNAME < thisfile.sql\n";
echo "-- Rows are upserted (INSERT ... ON DUPLICATE KEY UPDATE): existing local rows sharing a\n";
echo "-- primary/unique key are updated to the live values, new rows are inserted, and any rows\n";
echo "-- that only exist locally are left untouched.\n\n";
echo "SET FOREIGN_KEY_CHECKS=0;\n";
echo "SET NAMES utf8mb4;\n\n";

$tablesStmt = $pdo->prepare("SELECT TABLE_NAME FROM information_schema.TABLES
    WHERE TABLE_SCHEMA = :db AND TABLE_TYPE = 'BASE TABLE' ORDER BY TABLE_NAME");
$tablesStmt->execute([':db' => $currentDbName]);
$allTables = $tablesStmt->fetchAll(PDO::FETCH_COLUMN);

$tables = $onlyTables ? array_values(array_intersect($allTables, $onlyTables)) : $allTables;

foreach ($tables as $table) {
    db_export_table($pdo, $table, $since);
}

echo "\nSET FOREIGN_KEY_CHECKS=1;\n";

// One-time key: rotate after every successful export so the same key can't be
// reused for a later pull - the admin has to go back to db_export.php for a
// fresh one each time. The importer pulls one table per request (see
// ajaxDbImportRemote.php), so the rotated key is also echoed back as a trailing
// comment - the importer picks it up and uses it for the next table's request,
// chaining through the whole selected batch without a human re-copying keys.
$newKey = bin2hex(random_bytes(32));
$rotateStmt = $pdo->prepare("UPDATE app_settings SET setting_value = ? WHERE setting_name = 'db_export_secret_key'");
$rotateStmt->execute([$newKey]);
echo "-- NEW_EXPORT_KEY: $newKey\n";

exit;
