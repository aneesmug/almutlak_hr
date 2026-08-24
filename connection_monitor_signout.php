<?php
require_once __DIR__ . '/includes/connmon_gate.php';

header('Content-Type: application/json; charset=utf-8');

if (!connmon_has_valid_token()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Access denied.']);
    exit;
}

require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/user_activity_logger.php';

$activityId = (int) ($_POST['activity_id'] ?? 0);
if ($activityId <= 0) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing activity_id.']);
    exit;
}

$ok = forceSignOutActivity($conDB, $activityId);
echo json_encode(['success' => $ok, 'message' => $ok ? 'Signed out.' : 'Already signed out or not found.']);
