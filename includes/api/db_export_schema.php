<?php
// Same server-to-server contract as download_db_export.php / db_export_tables.php:
// key-only auth, no session dependency. Returns column definitions per table so a
// remote importer can diff its own schema against live and flag missing/extra
// columns before attempting to import data.
header('Content-Type: application/json');
require_once __DIR__ . '/../db.php';

$configuredKey = trim((string) get_setting($conDB, 'db_export_secret_key'));
$providedKey = trim((string) ($_POST['export_key'] ?? $_GET['export_key'] ?? ''));

if ($configuredKey === '' || $providedKey === '' || !hash_equals($configuredKey, $providedKey)) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Invalid or missing export key.']);
    exit;
}

$tablesRaw = trim((string) ($_POST['tables'] ?? $_GET['tables'] ?? ''));
$onlyTables = $tablesRaw !== '' ? array_values(array_filter(array_map('trim', explode(',', $tablesRaw)))) : [];

$pdo = getDbConnection();
$currentDbName = $pdo->query('SELECT DATABASE()')->fetchColumn();

$tableNamesStmt = $pdo->prepare("SELECT TABLE_NAME FROM information_schema.TABLES
    WHERE TABLE_SCHEMA = :db AND TABLE_TYPE = 'BASE TABLE' ORDER BY TABLE_NAME");
$tableNamesStmt->execute([':db' => $currentDbName]);
$allTables = $tableNamesStmt->fetchAll(PDO::FETCH_COLUMN);

$tables = $onlyTables ? array_values(array_intersect($allTables, $onlyTables)) : $allTables;

$colsStmt = $pdo->prepare("SELECT TABLE_NAME, COLUMN_NAME, COLUMN_TYPE, IS_NULLABLE, COLUMN_KEY, COLUMN_DEFAULT, EXTRA
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = :db AND TABLE_NAME = :t
    ORDER BY ORDINAL_POSITION");

$schema = [];
foreach ($tables as $table) {
    $colsStmt->execute([':db' => $currentDbName, ':t' => $table]);
    $cols = $colsStmt->fetchAll(PDO::FETCH_ASSOC);
    $schema[$table] = array_map(function ($c) {
        return [
            'name' => $c['COLUMN_NAME'],
            'type' => $c['COLUMN_TYPE'],
            'nullable' => $c['IS_NULLABLE'] === 'YES',
            'key' => $c['COLUMN_KEY'],
            'default' => $c['COLUMN_DEFAULT'],
            'extra' => $c['EXTRA'],
        ];
    }, $cols);
}

// Indexes per table (PRIMARY excluded) so the importer can spot missing (unique) keys
$indexes = [];
foreach ($tables as $table) {
    $rows = $pdo->query('SHOW INDEX FROM `' . str_replace('`', '``', $table) . '`')->fetchAll(PDO::FETCH_ASSOC);
    $byName = [];
    foreach ($rows as $r) {
        if ($r['Key_name'] === 'PRIMARY') {
            continue;
        }
        $n = $r['Key_name'];
        $byName[$n] = $byName[$n] ?? ['name' => $n, 'unique' => (int) $r['Non_unique'] === 0, 'columns' => []];
        $byName[$n]['columns'][(int) $r['Seq_in_index']] = ['name' => $r['Column_name'], 'sub_part' => $r['Sub_part']];
    }
    foreach ($byName as &$i) {
        ksort($i['columns']);
        $i['columns'] = array_values($i['columns']);
    }
    unset($i);
    $indexes[$table] = array_values($byName);
}

// Optional CREATE TABLE statements so the importer can create a table that is
// missing on its side. Only for explicitly requested tables.
$createSql = [];
if (!empty($_POST['include_create']) && $onlyTables) {
    foreach ($tables as $table) {
        $row = $pdo->query('SHOW CREATE TABLE `' . str_replace('`', '``', $table) . '`')->fetch(PDO::FETCH_NUM);
        if ($row && isset($row[1])) {
            $createSql[$table] = $row[1];
        }
    }
}

echo json_encode([
    'status' => 'success',
    'database' => $currentDbName,
    'schema' => $schema,
    'create_sql' => $createSql,
    'indexes' => $indexes,
]);
