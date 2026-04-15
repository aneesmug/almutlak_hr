<?php
header('Content-Type: application/json');

require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/session_check.php';
require_once __DIR__ . '/../../includes/helper_functions.php';
require_once __DIR__ . '/../../includes/payroll_approval_helpers.php';

function getResolvedFeedbackEmployeeDetails(PDO $pdo, array $feedbackRow): array
{
    $employeeId = trim((string)($feedbackRow['emp_id'] ?? ''));
    $employeeName = '';

    if ($employeeId !== '') {
        $employeeStmt = $pdo->prepare("SELECT name, iqama FROM employees WHERE emp_id = :emp_id LIMIT 1");
        $employeeStmt->execute([':emp_id' => $employeeId]);
        $employeeRow = $employeeStmt->fetch(PDO::FETCH_ASSOC) ?: [];
        $employeeName = trim((string)($employeeRow['name'] ?? ''));
        if ($employeeName === '' && function_exists('getDisplayName')) {
            $employeeName = getDisplayName($employeeId);
        }
        if ($employeeName === '') {
            $employeeName = $employeeId;
        } elseif (function_exists('getDisplayName')) {
            $employeeName = getDisplayName($employeeName);
        }

        return [
            'emp_id' => $employeeId,
            'employee_name' => $employeeName,
            'iqama' => trim((string)($employeeRow['iqama'] ?? '')),
            'request_inv_no' => trim((string)($feedbackRow['request_inv_no'] ?? '')),
            'payroll_month' => trim((string)($feedbackRow['payroll_month'] ?? ''))
        ];
    }

    return [
        'emp_id' => '',
        'employee_name' => '',
        'iqama' => '',
        'request_inv_no' => trim((string)($feedbackRow['request_inv_no'] ?? '')),
        'payroll_month' => trim((string)($feedbackRow['payroll_month'] ?? ''))
    ];
}

function sendHrPayrollResolvedFeedbackNotification(PDO $pdo, $conDB, array $feedbackRow, string $currentUserId): array
{
    $result = [
        'notification_sent' => false,
        'email_sent' => false,
        'recipient_count' => 0
    ];

    $requestInvNo = trim((string)($feedbackRow['request_inv_no'] ?? ''));
    $payrollMonth = trim((string)($feedbackRow['payroll_month'] ?? ''));
    $employeeId = trim((string)($feedbackRow['emp_id'] ?? ''));
    if ($requestInvNo === '' || $payrollMonth === '' || $employeeId === '') {
        return $result;
    }

    $resolvedEmployee = getResolvedFeedbackEmployeeDetails($pdo, $feedbackRow);
    $employeeName = trim((string)($resolvedEmployee['employee_name'] ?? ''));
    $employeeIqama = trim((string)($resolvedEmployee['iqama'] ?? ''));

    $resolverStmt = $pdo->prepare("SELECT name FROM employees WHERE emp_id = :emp_id LIMIT 1");
    $resolverStmt->execute([':emp_id' => $currentUserId]);
    $resolvedByName = trim((string)($resolverStmt->fetchColumn() ?: ''));
    if ($resolvedByName === '') {
        $resolvedByName = $currentUserId;
    } elseif (function_exists('getDisplayName')) {
        $resolvedByName = getDisplayName($resolvedByName);
    }

    $hrStmt = $pdo->prepare("SELECT al.emp_id, al.email, e.name
        FROM admin_login al
        INNER JOIN employees e ON e.emp_id = al.emp_id
        WHERE LOWER(TRIM(al.user_type)) = :user_type");
    $hrStmt->execute([':user_type' => 'hr_payroll']);
    $hrUsers = $hrStmt->fetchAll(PDO::FETCH_ASSOC);
    if (empty($hrUsers)) {
        return $result;
    }

    $requestUrl = 'payroll_checklist_report.php?month=' . urlencode($payrollMonth) . '&request_inv_no=' . urlencode($requestInvNo);
    $fullRequestUrl = (function_exists('get_base_url') ? get_base_url() : 'https://hr.almutlaksystem.com') . '/' . $requestUrl;
    $notificationTitle = 'Payroll Feedback Resolved';
    $notificationMessage = 'Payroll generator resolved feedback for employee ' . $employeeName . ' (' . $employeeId . ') in payroll ' . $payrollMonth . '. Please mark the record checked again to continue completion.';
    $emailSubject = 'Payroll Feedback Resolved - Recheck Required - ' . $payrollMonth . ' (' . $requestInvNo . ')';
    $emailMessage = 'Payroll generator ' . $resolvedByName . ' resolved feedback for employee ' . $employeeName . ' (' . $employeeId . ') in payroll ' . $payrollMonth . '. Please open the payroll checklist report and mark the employee record checked again.';
    $emailMessageHtml = '<div style="margin:0; color:#e0e0e0; line-height:1.7;">'
        . htmlspecialchars($emailMessage, ENT_QUOTES, 'UTF-8')
        . '</div>'
        . '<div style="margin-top:14px; text-align:left; background:#2a2a2a; border:1px solid #404040; border-radius:8px; padding:14px 16px;">'
        . '<div style="font-weight:700; color:#ffffff; margin-bottom:8px;">Resolved Employee Information</div>'
        . '<div style="color:#e0e0e0; line-height:1.8;">'
        . '<div><strong>Employee ID:</strong> ' . htmlspecialchars($employeeId, ENT_QUOTES, 'UTF-8') . '</div>'
        . '<div><strong>Employee Name:</strong> ' . htmlspecialchars($employeeName, ENT_QUOTES, 'UTF-8') . '</div>'
        . ($employeeIqama !== '' ? '<div><strong>Iqama:</strong> ' . htmlspecialchars($employeeIqama, ENT_QUOTES, 'UTF-8') . '</div>' : '')
        . '<div><strong>Payroll Month:</strong> ' . htmlspecialchars($payrollMonth, ENT_QUOTES, 'UTF-8') . '</div>'
        . '<div><strong>Request ID:</strong> ' . htmlspecialchars($requestInvNo, ENT_QUOTES, 'UTF-8') . '</div>'
        . '</div></div>';

    foreach ($hrUsers as $hrUser) {
        $hrEmpId = trim((string)($hrUser['emp_id'] ?? ''));
        if ($hrEmpId === '') {
            continue;
        }

        if (function_exists('create_and_show_notification')) {
            create_and_show_notification(
                $conDB,
                $hrEmpId,
                $notificationTitle,
                $notificationMessage,
                $requestUrl,
                'info'
            );
            $result['notification_sent'] = true;
        } elseif (function_exists('create_browser_notification')) {
            create_browser_notification(
                $conDB,
                (int)$hrEmpId,
                $notificationTitle,
                $notificationMessage,
                $requestUrl
            );
            $result['notification_sent'] = true;
        }

        if (!empty($hrUser['email']) && function_exists('send_approval_email')) {
            $templateData = [
                'APPROVER_NAME' => !empty($hrUser['name']) ? htmlspecialchars((string)$hrUser['name']) : 'HR Payroll',
                'REQUEST_ID' => $requestInvNo,
                'REQUEST_TYPE' => 'Payroll Feedback Resolved',
                'EMAIL_MESSAGE' => $emailMessage,
                'EMAIL_MESSAGE_HTML' => $emailMessageHtml,
                'PAYROLL_MONTH' => $payrollMonth,
                'EMPLOYEE_COUNT' => '1',
                'TOTAL_NET_SALARY' => number_format(0, 2),
                'PAYROLL_STATUS' => 'Resolved - Recheck Required',
                'REQUEST_URL' => $fullRequestUrl
            ];

            $sent = (bool)send_approval_email(
                $conDB,
                $hrUser['email'],
                $hrUser['name'] ?? 'HR Payroll',
                $emailSubject,
                'payroll_request',
                $templateData
            );
            $result['email_sent'] = $result['email_sent'] || $sent;
        }

        $result['recipient_count']++;
    }

    return $result;
}

if (empty($_SESSION['empid'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$feedbackId = (int)($input['feedback_id'] ?? 0);
$status = trim((string)($input['status'] ?? ''));
$currentUserId = (string)($_SESSION['empid'] ?? '');

if ($feedbackId <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid feedback id']);
    exit;
}

if (!in_array($status, ['resolved', 'open'], true)) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid status']);
    exit;
}

$pdo = getDbConnection();

try {
    ensurePayrollChecklistFeedbackTable($pdo);

    $feedbackStmt = $pdo->prepare("SELECT id, request_inv_no, payroll_month, emp_id, status
        FROM payroll_checklist_feedback
        WHERE id = :id
        LIMIT 1");
    $feedbackStmt->execute([':id' => $feedbackId]);
    $feedbackRow = $feedbackStmt->fetch(PDO::FETCH_ASSOC);

    if (!$feedbackRow) {
        echo json_encode(['status' => 'error', 'message' => 'Feedback not found']);
        exit;
    }

    $pdo->beginTransaction();

    if ($status === 'resolved') {
        $stmt = $pdo->prepare("UPDATE payroll_checklist_feedback
            SET status = 'resolved', resolved_at = NOW(), resolved_by = :resolved_by
            WHERE id = :id");
        $stmt->execute([
            ':resolved_by' => $currentUserId,
            ':id' => $feedbackId
        ]);
    } else {
        $stmt = $pdo->prepare("UPDATE payroll_checklist_feedback
            SET status = 'open', resolved_at = NULL, resolved_by = NULL
            WHERE id = :id");
        $stmt->execute([':id' => $feedbackId]);
    }

    if ($stmt->rowCount() < 1) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        echo json_encode(['status' => 'error', 'message' => 'Feedback not found or already updated']);
        exit;
    }

    $notificationResult = [
        'notification_sent' => false,
        'email_sent' => false,
        'recipient_count' => 0
    ];
    $resolvedEmployee = getResolvedFeedbackEmployeeDetails($pdo, $feedbackRow);

    if ($status === 'resolved') {
        $notificationResult = sendHrPayrollResolvedFeedbackNotification($pdo, $conDB, $feedbackRow, $currentUserId);

        $history = $pdo->prepare("INSERT INTO smt_request_status (inv_no, emp_id, emp_name, note, status)
            VALUES (:inv_no, :emp_id, :emp_name, :note, 'feedback')");
        $history->execute([
            ':inv_no' => (string)$feedbackRow['request_inv_no'],
            ':emp_id' => $currentUserId,
            ':emp_name' => 'System',
            ':note' => 'Payroll feedback resolved for employee ' . (string)$feedbackRow['emp_id'] . ' in payroll ' . (string)$feedbackRow['payroll_month'] . '. HR Payroll was notified to mark checked again.'
        ]);
    }

    if ($pdo->inTransaction()) {
        $pdo->commit();
    }

    echo json_encode([
        'status' => 'success',
        'message' => $status === 'resolved'
            ? (($notificationResult['notification_sent'] || $notificationResult['email_sent'])
                ? 'Feedback marked as resolved successfully. HR Payroll has been notified to mark checked again.'
                : 'Feedback marked as resolved successfully, but no HR Payroll notification could be sent.')
            : 'Feedback status updated successfully.',
        'resolved_employee' => $resolvedEmployee,
        'notification_sent' => (bool)$notificationResult['notification_sent'],
        'email_sent' => (bool)$notificationResult['email_sent'],
        'recipient_count' => (int)$notificationResult['recipient_count']
    ]);
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
