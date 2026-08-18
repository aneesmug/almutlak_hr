<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../session_check.php';
require_once __DIR__ . '/../license_check.php';

if (!($is_system_admin ?? false)) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$serialKey = trim((string) ($_POST['serial_key'] ?? ''));
$token = trim((string) ($_POST['token'] ?? ''));
$apiUrl = trim((string) ($_POST['api_url'] ?? ''));

if ($serialKey === '') {
    echo json_encode(['success' => false, 'message' => 'Enter a serial key.']);
    exit;
}
if ($token === '') {
    echo json_encode(['success' => false, 'message' => 'Enter the verification URL token.']);
    exit;
}

license_ensure_settings_exist($conDB);

if ($apiUrl === '') {
    $apiUrl = trim((string) get_setting($conDB, 'license_api_url'));
    if ($apiUrl === '') {
        echo json_encode(['success' => false, 'message' => 'Enter the license server URL.']);
        exit;
    }
} elseif (!filter_var($apiUrl, FILTER_VALIDATE_URL) || !preg_match('#^https?://#i', $apiUrl)) {
    echo json_encode(['success' => false, 'message' => 'License server URL is not valid.']);
    exit;
}

license_update_setting($conDB, 'license_serial_key', $serialKey);
license_update_setting($conDB, 'license_token', $token);

$result = license_force_check($conDB, $apiUrl, $serialKey, $token);

echo json_encode([
    'success' => true,
    'valid' => (bool) ($result['valid'] ?? false),
    'status' => (string) ($result['status'] ?? 'unknown'),
    'message' => (string) ($result['message'] ?? ''),
    'expires_at' => $result['expires_at'] ?? null,
]);
