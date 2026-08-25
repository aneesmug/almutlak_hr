<?php
/**
 * Refreshes the Connection Monitor access cookie (CONNMON_EXTEND_SECONDS,
 * see includes/connmon_gate.php) without requiring the OTP again - only
 * works while the current token is still valid, so it can't be used to
 * regain access after it's actually expired.
 */
require_once __DIR__ . '/includes/connmon_gate.php';

header('Content-Type: application/json; charset=utf-8');

if (!connmon_has_valid_token()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Session already expired.']);
    exit;
}

$expiry = connmon_issue_token(CONNMON_EXTEND_SECONDS);
echo json_encode(['success' => true, 'expiry' => $expiry]);
