<?php
// Live side of "Check Schema -> Apply to Live" in db_import_remote.php.
// Same key-only, server-to-server contract as db_export_schema.php. The caller sends
// STRUCTURED operations (add / modify / drop column) - never SQL text; every statement
// is built and validated by includes/db_schema_sync.php. The export key is single-use
// here as well: it is rotated after every authenticated call and the new one is
// returned so the importer can keep chaining requests.
header('Content-Type: application/json');
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../db_schema_sync.php';

$configuredKey = trim((string) get_setting($conDB, 'db_export_secret_key'));
$providedKey = trim((string) ($_POST['export_key'] ?? ''));

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || $configuredKey === '' || $providedKey === '' || !hash_equals($configuredKey, $providedKey)) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Invalid or missing export key.']);
    exit;
}

$ops = json_decode((string) ($_POST['ops'] ?? ''), true);
if (!is_array($ops) || !$ops || count($ops) > 500) {
    echo json_encode(['status' => 'error', 'message' => 'No schema operations received.']);
    exit;
}
foreach ($ops as $op) {
    if (!is_array($op) || !in_array($op['op'] ?? '', ['add', 'modify', 'drop'], true)) {
        echo json_encode(['status' => 'error', 'message' => 'Only add / modify / drop column operations are allowed.']);
        exit;
    }
}

$newKey = bin2hex(random_bytes(32));
$rotate = mysqli_prepare($conDB, "UPDATE app_settings SET setting_value = ? WHERE setting_name = 'db_export_secret_key'");
mysqli_stmt_bind_param($rotate, 's', $newKey);
mysqli_stmt_execute($rotate);

$results = dbss_apply($conDB, $ops);
$failed = count(array_filter($results, fn ($r) => !$r['ok']));

echo json_encode([
    'status' => $failed ? ($failed === count($results) ? 'error' : 'warning') : 'success',
    'message' => $failed ? "$failed of " . count($results) . ' statement(s) failed on live.' : 'Schema updated on live.',
    'results' => $results,
    'next_export_key' => $newKey,
]);
