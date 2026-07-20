<?php
header('Content-Type: application/json');

require_once __DIR__ . '/../session_check.php';
require_once __DIR__ . '/../special_access_helper.php';

// Wide blast-radius control (can block a request type for every employee) - admin only,
// or anyone explicitly granted the 'manage_global_request_blocks' special access.
$can_manage_global_request_blocks = (
    $is_system_admin
    || user_has_special_access($conDB, $empid ?? '', 'manage_global_request_blocks', $user_role ?? '', $user_type ?? '', $is_system_admin ?? false)
);
if (!$can_manage_global_request_blocks) {
    echo json_encode(['success' => false, 'message' => 'Access denied']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

$action = $_POST['action'] ?? '';

switch ($action) {
    case 'get_global_blocked_types':
        get_global_blocked_types_action($conDB);
        break;
    case 'update_global_blocked_types':
        update_global_blocked_types_action($conDB);
        break;
    default:
        echo json_encode(['success' => false, 'message' => 'Invalid action specified.']);
        break;
}

function ensure_global_request_type_blocks_setting($conDB) {
    $settingName = 'globally_blocked_request_types';
    $defaultValue = '[]';

    $checkStmt = mysqli_prepare($conDB, "SELECT id FROM app_settings WHERE setting_name = ? LIMIT 1");
    mysqli_stmt_bind_param($checkStmt, 's', $settingName);
    mysqli_stmt_execute($checkStmt);
    $result = mysqli_stmt_get_result($checkStmt);
    $exists = $result && mysqli_num_rows($result) > 0;
    mysqli_stmt_close($checkStmt);

    if ($exists) {
        return;
    }

    $insertStmt = mysqli_prepare($conDB, "INSERT INTO app_settings (setting_name, setting_value, setting_group, description, input_type, options) VALUES (?, ?, 'special_access', 'globally_blocked_request_types_json_array', 'text', NULL)");
    mysqli_stmt_bind_param($insertStmt, 'ss', $settingName, $defaultValue);
    mysqli_stmt_execute($insertStmt);
    mysqli_stmt_close($insertStmt);
}

function get_global_blocked_types_action($conDB) {
    ensure_global_request_type_blocks_setting($conDB);
    $blockedTypes = get_global_blocked_request_types($conDB);
    echo json_encode(['success' => true, 'blocked_types' => $blockedTypes]);
}

function update_global_blocked_types_action($conDB) {
    ensure_global_request_type_blocks_setting($conDB);

    $submitted = $_POST['blocked_types'] ?? '[]';
    if (is_string($submitted)) {
        $decoded = json_decode($submitted, true);
        $submitted = is_array($decoded) ? $decoded : [];
    }
    if (!is_array($submitted)) {
        $submitted = [];
    }

    $validKeys = array_keys(get_blockable_request_type_labels());
    $normalized = array_values(array_intersect($submitted, $validKeys));
    $jsonValue = json_encode($normalized);

    $settingName = 'globally_blocked_request_types';
    $updateStmt = mysqli_prepare($conDB, "UPDATE app_settings SET setting_value = ? WHERE setting_name = ?");
    mysqli_stmt_bind_param($updateStmt, 'ss', $jsonValue, $settingName);
    if (!mysqli_stmt_execute($updateStmt)) {
        echo json_encode(['success' => false, 'message' => 'Failed to update setting: ' . mysqli_error($conDB)]);
        mysqli_stmt_close($updateStmt);
        return;
    }
    mysqli_stmt_close($updateStmt);

    echo json_encode(['success' => true, 'blocked_types' => $normalized]);
}
