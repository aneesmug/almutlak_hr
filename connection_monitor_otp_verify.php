<?php
require_once __DIR__ . '/includes/connmon_gate.php';

header('Content-Type: application/json; charset=utf-8');

$otp = $_POST['otp'] ?? '';
if (connmon_verify_otp($otp)) {
    connmon_issue_token();
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Incorrect code.']);
}
