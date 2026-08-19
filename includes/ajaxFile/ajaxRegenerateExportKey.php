<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../session_check.php';

if (!($is_system_admin ?? false)) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

$exportKey = bin2hex(random_bytes(32));
$stmt = $conDB->prepare("UPDATE app_settings SET setting_value = ? WHERE setting_name = 'db_export_secret_key'");
$stmt->bind_param('s', $exportKey);
$stmt->execute();

echo json_encode(['status' => 'success', 'export_key' => $exportKey]);
