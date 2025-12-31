<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../includes/db.php';

try {
    // Read JSON body
    $raw = file_get_contents('php://input');
    $payload = json_decode($raw, true);

    $empId = isset($payload['emp_id']) ? trim($payload['emp_id']) : '';
    $paymentType = isset($payload['payment_type']) ? (int)$payload['payment_type'] : 0;

    if (empty($empId)) {
        echo json_encode(['status' => 'error', 'message' => 'Employee ID is required']);
        exit;
    }
    if (!in_array($paymentType, [1, 2, 3], true)) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid payment type. Allowed: 1=Bank, 2=Cash, 3=Hold']);
        exit;
    }

    $pdo = getDbConnection();
    $stmt = $pdo->prepare('UPDATE employees SET payment_type = :payment_type WHERE emp_id = :emp_id');
    $ok = $stmt->execute([':payment_type' => $paymentType, ':emp_id' => $empId]);

    if ($ok && $stmt->rowCount() >= 0) {
        echo json_encode(['status' => 'success', 'message' => 'Payment type updated successfully']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'No changes made or employee not found']);
    }
} catch (Throwable $e) {
    error_log('update_payment_type error: ' . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => 'Server error updating payment type']);
}
