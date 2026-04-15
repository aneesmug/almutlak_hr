<?php
header('Content-Type: application/json');

require_once("./../../includes/db.php");
require_once("./../../includes/session_check.php");

function normalizeImportMonth($value): ?string
{
    $stringValue = trim((string)$value);
    if ($stringValue === '') {
        return null;
    }

    if (preg_match('/^(\d{4})-(\d{2})$/', $stringValue, $matches)) {
        return $matches[1] . '-' . $matches[2];
    }

    return null;
}

function ensurePayrollImportCheckpointRegistryTable(PDO $pdo): void
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

function generatePayrollImportCheckpointCode(?string $defaultMonth): string
{
    $prefix = 'PAYIMP';
    $monthValue = preg_replace('/[^0-9]/', '', (string)$defaultMonth);
    if ($monthValue === '') {
        $monthValue = date('Ym');
    }

    try {
        $randomCode = strtoupper(bin2hex(random_bytes(6)));
    } catch (Throwable $e) {
        $randomCode = strtoupper(dechex((int)(microtime(true) * 1000000))) . strtoupper(substr(md5(uniqid('', true)), 0, 6));
    }

    return $prefix . '-' . $monthValue . '-' . $randomCode;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed.']);
    exit();
}

$payload = json_decode(file_get_contents('php://input'), true);
$defaultMonth = normalizeImportMonth($payload['default_month'] ?? '');

try {
    $pdo = getDbConnection();
    ensurePayrollImportCheckpointRegistryTable($pdo);

    $checkpointCode = generatePayrollImportCheckpointCode($defaultMonth);
    $stmt = $pdo->prepare("INSERT INTO payroll_import_checkpoint_registry (checkpoint_code, default_month, created_by) VALUES (:checkpoint_code, :default_month, :created_by)");
    $stmt->execute([
        ':checkpoint_code' => $checkpointCode,
        ':default_month' => $defaultMonth,
        ':created_by' => (string)($_SESSION['empid'] ?? $empid ?? ''),
    ]);

    echo json_encode([
        'status' => 'success',
        'checkpoint_code' => $checkpointCode,
        'default_month' => $defaultMonth,
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Unable to generate a payroll import template checkpoint right now. Please try again.',
    ]);
}