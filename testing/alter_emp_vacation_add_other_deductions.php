<?php
// Simple schema update script to add other_deductions column to emp_vacation
// Run once: http://your-host/system/alter_emp_vacation_add_other_deductions.php

require_once __DIR__ . '/includes/db.php';

header('Content-Type: application/json');

$response = [
    'status' => 'ok',
    'added' => false,
    'message' => '',
];

try {
    // Prefer PDO if available
    if (!isset($pdo) || !$pdo) {
        throw new Exception('Database PDO connection not available');
    }

    // Check if column exists
    $stmt = $pdo->prepare("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'emp_vacation' AND COLUMN_NAME = 'other_deductions' LIMIT 1");
    $stmt->execute();
    $exists = (bool)$stmt->fetchColumn();

    if ($exists) {
        $response['message'] = 'Column other_deductions already exists in emp_vacation';
    } else {
        // Add the column after other_earnings if it exists; if not, add at end
        // Try adding after other_earnings first
        $pdo->beginTransaction();
        try {
            $pdo->exec("ALTER TABLE `emp_vacation` ADD COLUMN `other_deductions` DECIMAL(10,2) NULL DEFAULT 0 AFTER `other_earnings`");
            $pdo->commit();
            $response['added'] = true;
            $response['message'] = 'Column other_deductions added after other_earnings';
        } catch (PDOException $e) {
            // Fallback: add at end
            $pdo->rollBack();
            $pdo->beginTransaction();
            $pdo->exec("ALTER TABLE `emp_vacation` ADD COLUMN `other_deductions` DECIMAL(10,2) NULL DEFAULT 0");
            $pdo->commit();
            $response['added'] = true;
            $response['message'] = 'Column other_deductions added at end of emp_vacation table';
        }
    }

    echo json_encode($response);
} catch (Exception $ex) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => $ex->getMessage(),
    ]);
}
