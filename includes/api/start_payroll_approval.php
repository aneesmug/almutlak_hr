<?php
header('Content-Type: application/json');

require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/session_check.php';
require_once __DIR__ . '/../../includes/ApprovalChainManager.php';
require_once __DIR__ . '/../../includes/payroll_approval_helpers.php';

$input = json_decode(file_get_contents('php://input'), true);
$monthYear = trim((string)($input['month'] ?? ''));

if ($monthYear === '' || !preg_match('/^\d{4}-\d{2}$/', $monthYear)) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Invalid month format. Expected YYYY-MM.'
    ]);
    exit();
}

$requestedBy = $_SESSION['empid'] ?? ($empid ?? null);
if (empty($requestedBy)) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Unauthorized payroll approval request.'
    ]);
    exit();
}

$pdo = getDbConnection();

try {
    $generatedCheck = $pdo->prepare("SELECT COUNT(*) FROM payrolls WHERE month_year = :month_year AND status IN ('generated', 'paid')");
    $generatedCheck->execute([':month_year' => $monthYear]);
    $generatedCount = (int)$generatedCheck->fetchColumn();

    if ($generatedCount <= 0) {
        echo json_encode([
            'status' => 'error',
            'message' => __('no_generated_payroll_for_selected_month', 'Selected month does not contain generated payroll.')
        ]);
        exit();
    }

    ensurePayrollApprovalTable($pdo);
    ensurePayrollApprovalRequestType($pdo);

    $requestStmt = $pdo->prepare("SELECT * FROM payroll_approval_requests WHERE payroll_month = :payroll_month LIMIT 1");
    $requestStmt->execute([':payroll_month' => $monthYear]);
    $payrollApprovalRequest = $requestStmt->fetch(PDO::FETCH_ASSOC);

    $chainManager = new ApprovalChainManager($conDB, $pdo);

    if (!$payrollApprovalRequest) {
        $requestInvNo = generatePayrollApprovalInvNo($pdo, $monthYear);
        $departmentId = getEmployeeDepartmentId($pdo, $requestedBy);

        $insertRequest = $pdo->prepare("INSERT INTO payroll_approval_requests
            (request_inv_no, payroll_month, requested_by, status)
            VALUES (:request_inv_no, :payroll_month, :requested_by, 'pending_approval')");
        $insertRequest->execute([
            ':request_inv_no' => $requestInvNo,
            ':payroll_month' => $monthYear,
            ':requested_by' => (string)$requestedBy
        ]);

        $chainManager->createApprovalChain('payroll_request', $requestInvNo, (string)$requestedBy, $departmentId);
        $notificationSent = notifyPayrollApprovalApprover($conDB, $pdo, $requestInvNo, $monthYear);

        $historyStmt = $pdo->prepare("INSERT INTO smt_request_status (inv_no, emp_id, emp_name, note, status)
            VALUES (:inv_no, :emp_id, :emp_name, :note, :status)");
        $historyStmt->execute([
            ':inv_no' => $requestInvNo,
            ':emp_id' => (string)$requestedBy,
            ':emp_name' => 'System',
            ':note' => 'Payroll approval started for month ' . $monthYear,
            ':status' => 'pending_approval'
        ]);

        echo json_encode([
            'status' => 'success',
            'message' => __('payroll_approval_started', 'Payroll approval started successfully.') . ($notificationSent ? ' Notification email sent to approver.' : ''),
            'request_inv_no' => $requestInvNo,
            'payroll_month' => $monthYear,
            'notification_sent' => (bool)$notificationSent
        ]);
        exit();
    }

    $requestInvNo = $payrollApprovalRequest['request_inv_no'];
    $approvalStatus = $chainManager->getApprovalStatus($requestInvNo);

    if (($approvalStatus['status'] ?? '') === 'not_found') {
        $departmentId = getEmployeeDepartmentId($pdo, $payrollApprovalRequest['requested_by'] ?? $requestedBy);
        $chainManager->createApprovalChain('payroll_request', $requestInvNo, (string)($payrollApprovalRequest['requested_by'] ?? $requestedBy), $departmentId);
        $notificationSent = notifyPayrollApprovalApprover($conDB, $pdo, $requestInvNo, $monthYear);

        echo json_encode([
            'status' => 'success',
            'message' => __('payroll_approval_restarted', 'Payroll approval chain started for existing request.') . ($notificationSent ? ' Notification email sent to approver.' : ''),
            'request_inv_no' => $requestInvNo,
            'payroll_month' => $monthYear,
            'notification_sent' => (bool)$notificationSent
        ]);
        exit();
    }

    if (($approvalStatus['status'] ?? '') === 'approved') {
        echo json_encode([
            'status' => 'info',
            'message' => __('payroll_approval_already_approved', 'Payroll approval is already completed for this month.'),
            'request_inv_no' => $requestInvNo,
            'payroll_month' => $monthYear,
            'approval_status' => 'approved'
        ]);
        exit();
    }

    if (($approvalStatus['status'] ?? '') === 'rejected') {
        echo json_encode([
            'status' => 'info',
            'message' => __('payroll_approval_rejected', 'Payroll approval request is rejected. Please update approvers or open payroll approvals.'),
            'request_inv_no' => $requestInvNo,
            'payroll_month' => $monthYear,
            'approval_status' => 'rejected'
        ]);
        exit();
    }

    $notificationSent = notifyPayrollApprovalApprover($conDB, $pdo, $requestInvNo, $monthYear);

    echo json_encode([
        'status' => 'info',
        'message' => __('payroll_approval_already_started', 'Payroll approval is already started for this month.') . ($notificationSent ? ' Reminder email sent to current approver.' : ''),
        'request_inv_no' => $requestInvNo,
        'payroll_month' => $monthYear,
        'approval_status' => $approvalStatus['status'] ?? 'pending_approval',
        'notification_sent' => (bool)$notificationSent
    ]);
} catch (Exception $e) {
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
