<?php
header('Content-Type: application/json');

require_once("./../../includes/db.php");
require_once("./../../includes/session_check.php");

function normalizeImportCheckpointCode($value): string
{
    return strtoupper(trim((string)$value));
}

function ensurePayrollImportCheckpointTable(PDO $pdo): void
{
    $pdo->exec("CREATE TABLE IF NOT EXISTS payroll_import_checkpoint_registry (
        id INT AUTO_INCREMENT PRIMARY KEY,
        checkpoint_code VARCHAR(255) NOT NULL UNIQUE,
        default_month VARCHAR(7) DEFAULT NULL,
        created_by VARCHAR(100) DEFAULT NULL,
        issued_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        used_at TIMESTAMP NULL DEFAULT NULL,
        INDEX idx_default_month (default_month),
        INDEX idx_used_at (used_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed.']);
    exit();
}

$payload = json_decode(file_get_contents('php://input'), true);
$checkpointCode = normalizeImportCheckpointCode($payload['checkpoint_code'] ?? '');

if ($checkpointCode === '') {
    http_response_code(422);
    echo json_encode([
        'status' => 'error',
        'message' => 'The selected file is not valid. Please upload a valid payroll import file.',
        'exists' => false,
    ]);
    exit();
}

try {
    $pdo = getDbConnection();
    ensurePayrollImportCheckpointTable($pdo);

    $stmt = $pdo->prepare("SELECT used_at FROM payroll_import_checkpoint_registry WHERE checkpoint_code = :checkpoint_code LIMIT 1");
    $stmt->execute([':checkpoint_code' => $checkpointCode]);

    $record = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    $exists = $record !== null;
    $isUsed = $exists && !empty($record['used_at']);

    echo json_encode([
        'status' => 'success',
        'exists' => $exists,
        'is_used' => $isUsed,
        'checkpoint_code' => $checkpointCode,
        'message' => !$exists
            ? 'The selected file is not valid. Please upload a valid payroll import file.'
            : ($isUsed
                ? 'This file was already used. Please upload a different file.'
                : 'File validation completed successfully.'),
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Unable to validate the selected file right now. Please try again.',
    ]);
}