<?php
// Lightweight status read for license_locked.php's background poll - reads the
// cached verdict only, no remote call, so multiple open tabs polling every few
// seconds doesn't hammer the license server.
header('Content-Type: application/json');
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../session_check.php';

echo json_encode([
    'valid' => get_setting($conDB, 'license_cache_valid') === '1',
]);
