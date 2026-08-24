<?php
require_once __DIR__ . '/includes/connmon_gate.php';

header('Content-Type: application/json; charset=utf-8');

if (!connmon_has_valid_token()) {
    http_response_code(403);
    echo json_encode(['error' => 'Access denied.']);
    exit;
}

require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/user_activity_logger.php';
require_once __DIR__ . '/includes/connection_monitor_helper.php';

echo json_encode(connmon_snapshot($conDB));
