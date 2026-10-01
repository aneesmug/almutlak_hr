<?php
// File: includes/ajaxFile/checkLanguageKey.php
// Purpose: Live check if a translation language key already exists

require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/session_check.php';
require_once __DIR__ . '/../../includes/page_access_helper.php';
// Translation management is limited to people allowed on language.php.
if (!page_role_allowed($conDB, 'language.php', $user_role ?? '', $user_type ?? '', $is_system_admin ?? false)) {
    http_response_code(403);
    header('Content-Type: application/json');
    echo json_encode(['type' => 'error', 'status' => 'error', 'title' => 'Error', 'message' => 'Access denied.']);
    exit;
}

header('Content-Type: application/json');

// Support both GET and POST
$raw_key = isset($_REQUEST['key']) ? trim($_REQUEST['key']) : '';

if ($raw_key === '') {
    echo json_encode(['success' => false, 'message' => 'Missing key']);
    exit;
}

// Normalize the key same way as addLanguageAjax.php
$lang_key = strtolower(str_replace(' ', '_', $raw_key));
$lang_key = preg_replace('/[^a-z0-9_]/i', '', $lang_key);
$lang_key = preg_replace('/_+/', '_', $lang_key);
$lang_key = mysqli_real_escape_string($conDB, $lang_key);

$exists = false;
$languages = [];
$translations = [];

$sql = "SELECT lang_code, translation FROM translations WHERE lang_key = '{$lang_key}'";
$res = mysqli_query($conDB, $sql);
if ($res) {
    while ($row = mysqli_fetch_assoc($res)) {
        $exists = true;
        $languages[] = $row['lang_code'];
        $translations[$row['lang_code']] = $row['translation'];
    }
}

echo json_encode([
    'success' => true,
    'exists' => $exists,
    'key' => $lang_key,
    'languages' => $languages,
    'translations' => $translations
]);
?>
