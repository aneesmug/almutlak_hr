<?php
// Read-only status check for a payroll month's approval chain. No side effects
// (no notification emails, no row inserts) so it's safe to poll on every page load.
header('Content-Type: application/json');

require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/session_check.php';
require_once __DIR__ . '/../../includes/ApprovalChainManager.php';
require_once __DIR__ . '/../../includes/payroll_approval_helpers.php';

$monthYear = trim((string)($_GET['month'] ?? ''));
if ($monthYear === '' || !preg_match('/^\d{4}-\d{2}$/', $monthYear)) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid month format. Expected YYYY-MM.']);
    exit();
}

$pdo = getDbConnection();

try {
    ensurePayrollApprovalTable($pdo);

    $requestStmt = $pdo->prepare("SELECT request_inv_no FROM payroll_approval_requests WHERE payroll_month = :payroll_month LIMIT 1");
    $requestStmt->execute([':payroll_month' => $monthYear]);
    $payrollApprovalRequest = $requestStmt->fetch(PDO::FETCH_ASSOC);

    if (!$payrollApprovalRequest) {
        echo json_encode(['status' => 'success', 'approval_status' => 'none']);
        exit();
    }

    $requestInvNo = $payrollApprovalRequest['request_inv_no'];
    $chainManager = new ApprovalChainManager($conDB, $pdo);
    $approvalStatus = $chainManager->getApprovalStatus($requestInvNo);
    $status = $approvalStatus['status'] ?? 'not_found';

    echo json_encode([
        'status' => 'success',
        'approval_status' => $status === 'not_found' ? 'none' : ($status === 'pending' ? 'pending_approval' : $status),
        'request_inv_no' => $requestInvNo,
    ]);
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
