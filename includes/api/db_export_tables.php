<?php
// Same server-to-server contract as download_db_export.php: key-only auth, no
// session dependency. Lists tables + exact row counts so a remote caller can build
// a "pick which tables to import" picker with a real local-vs-live diff.
header('Content-Type: application/json');
require_once __DIR__ . '/../db.php';

$configuredKey = trim((string) get_setting($conDB, 'db_export_secret_key'));
$providedKey = trim((string) ($_POST['export_key'] ?? $_GET['export_key'] ?? ''));

if ($configuredKey === '' || $providedKey === '' || !hash_equals($configuredKey, $providedKey)) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Invalid or missing export key.']);
    exit;
}

$pdo = getDbConnection();
$currentDbName = $pdo->query('SELECT DATABASE()')->fetchColumn();

$stmt = $pdo->prepare("SELECT TABLE_NAME FROM information_schema.TABLES
    WHERE TABLE_SCHEMA = :db AND TABLE_TYPE = 'BASE TABLE' ORDER BY TABLE_NAME");
$stmt->execute([':db' => $currentDbName]);
$tableNames = $stmt->fetchAll(PDO::FETCH_COLUMN);

function db_export_tables_quote_ident(string $name): string {
    return '`' . str_replace('`', '``', $name) . '`';
}

$tables = [];
foreach ($tableNames as $name) {
    $count = (int) $pdo->query('SELECT COUNT(*) FROM ' . db_export_tables_quote_ident($name))->fetchColumn();
    $tables[] = ['name' => $name, 'rows' => $count];
}

echo json_encode([
    'status' => 'success',
    'database' => $currentDbName,
    'tables' => $tables,
]);
