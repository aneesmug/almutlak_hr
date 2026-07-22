<?php

header('Content-Type: application/json');

require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/session_check.php';
require_once __DIR__ . '/../../includes/helper_functions.php';
require_once __DIR__ . '/../../includes/ApprovalChainManager.php';
require_once __DIR__ . '/../../includes/payroll_approval_helpers.php';

if (empty($_SESSION['empid'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

$action = $_POST['action'] ?? '';
$currentUserId = (string)$_SESSION['empid'];

$pdo = getDbConnection();

try {
    ensurePayrollApprovalTable($pdo);
    ensurePayrollChecklistSupportTables($pdo);
    $requestTypeId = ensurePayrollApprovalRequestType($pdo);
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => 'Payroll approval setup failed: ' . $e->getMessage()]);
    exit;
}

switch ($action) {
    case 'approve_request':
        approvePayrollRequest($pdo, $conDB, $currentUserId, $requestTypeId);
        break;

    case 'reject_request':
        rejectPayrollRequest($pdo, $conDB, $currentUserId, $requestTypeId);
        break;

    case 'send_back_issues':
        sendBackPayrollIssues($pdo, $conDB, $currentUserId, $requestTypeId);
        break;

    case 'notify_feedback_followup':
        notifyPayrollFeedbackFollowup($pdo, $conDB, $currentUserId, $requestTypeId);
        break;

    case 'get_direct_supervisor_options':
        getDirectSupervisorOptionsForPayroll($pdo, $currentUserId);
        break;

    case 'get_supervisor_employees_preview':
        getSupervisorEmployeesPreviewForPayroll($pdo, $currentUserId);
        break;

    case 'send_supervisor_payroll_report':
        sendSupervisorPayrollReport($pdo, $conDB, $currentUserId);
        break;

    case 'upload_manager_payroll_excel':
        uploadManagerPayrollExcel($pdo, $conDB, $currentUserId);
        break;

    case 'get_manager_uploaded_payroll_excel_rows':
        getManagerUploadedPayrollExcelRows($pdo, $currentUserId);
        break;

    case 'get_payroll_compensation_for_employees':
        getPayrollCompensationForEmployees($pdo, $currentUserId);
        break;

    case 'list_manager_uploaded_payroll_excel_files':
        listManagerUploadedPayrollExcelFiles($pdo, $currentUserId);
        break;

    case 'mark_manager_uploaded_payroll_excel_reviewed':
        markManagerUploadedPayrollExcelReviewed($pdo, $currentUserId);
        break;

    case 'delete_manager_uploaded_payroll_excel_file':
        deleteManagerUploadedPayrollExcelFile($pdo, $currentUserId);
        break;

    case 'get_finance_verification_setup':
        getFinanceVerificationSetup($pdo, $currentUserId, $requestTypeId);
        break;

    case 'submit_finance_verification_setup':
        submitFinanceVerificationSetup($pdo, $conDB, $currentUserId, $requestTypeId);
        break;

    case 'toggle_employee_check':
        togglePayrollEmployeeCheck($pdo, $currentUserId, $requestTypeId);
        break;

    case 'confirm_finance_officer_verification':
        confirmFinanceOfficerVerification($pdo, $currentUserId);
        break;

    default:
        echo json_encode(['status' => 'error', 'message' => 'Invalid action']);
        break;
}

function sendJsonResponseAndContinue(array $payload): void
{
    $json = json_encode($payload);
    if ($json === false) {
        $json = json_encode(['status' => 'error', 'message' => 'Response encoding failed']);
    }

    if (session_status() === PHP_SESSION_ACTIVE) {
        @session_write_close();
    }

    ignore_user_abort(true);

    if (!headers_sent()) {
        header('Connection: close');
        header('Content-Length: ' . strlen((string)$json));
    }

    echo $json;

    while (ob_get_level() > 0) {
        @ob_end_flush();
    }

    @flush();

    if (function_exists('fastcgi_finish_request')) {
        fastcgi_finish_request();
    }
}

function payrollApprovalTableHasColumn(PDO $pdo, string $tableName, string $columnName): bool
{
    $stmt = $pdo->prepare("SELECT COUNT(*)
        FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = :table_name
          AND COLUMN_NAME = :column_name");
    $stmt->execute([
        ':table_name' => $tableName,
        ':column_name' => $columnName
    ]);
    return ((int)$stmt->fetchColumn() > 0);
}

function getPayrollChecklistModifiedEmployeeMap(PDO $pdo, string $requestInvNo, string $monthValue): array
{
    if ($requestInvNo === '' || $monthValue === '') {
        return [];
    }

    $requestStmt = $pdo->prepare("SELECT created_at FROM payroll_approval_requests WHERE request_inv_no = :inv_no LIMIT 1");
    $requestStmt->execute([':inv_no' => $requestInvNo]);
    $requestCreatedAt = trim((string)$requestStmt->fetchColumn());
    if ($requestCreatedAt === '') {
        return [];
    }

    $modifiedEmployees = [];

    $benefitHasUpdatedAt = payrollApprovalTableHasColumn($pdo, 'payroll_benefits', 'updated_at');
    $benefitHasCreatedAt = payrollApprovalTableHasColumn($pdo, 'payroll_benefits', 'created_at');
    if ($benefitHasUpdatedAt || $benefitHasCreatedAt) {
        $benefitTimeExpr = $benefitHasUpdatedAt && $benefitHasCreatedAt
            ? 'GREATEST(COALESCE(pb.updated_at, pb.created_at), COALESCE(pb.created_at, pb.updated_at))'
            : ($benefitHasUpdatedAt ? 'pb.updated_at' : 'pb.created_at');

        $benefitStmt = $pdo->prepare("SELECT DISTINCT pb.emp_id
            FROM payroll_benefits pb
            WHERE pb.month = :month_year
              AND pb.status = 1
              AND {$benefitTimeExpr} > :request_created_at");
        $benefitStmt->execute([
            ':month_year' => $monthValue,
            ':request_created_at' => $requestCreatedAt,
        ]);

        foreach ($benefitStmt->fetchAll(PDO::FETCH_COLUMN) as $empId) {
            $empId = trim((string)$empId);
            if ($empId !== '') {
                $modifiedEmployees[$empId] = true;
            }
        }
    }

    $deductionHasUpdatedAt = payrollApprovalTableHasColumn($pdo, 'payroll_deductions', 'updated_at');
    $deductionHasCreatedAt = payrollApprovalTableHasColumn($pdo, 'payroll_deductions', 'created_at');
    if ($deductionHasUpdatedAt || $deductionHasCreatedAt) {
        $deductionTimeExpr = $deductionHasUpdatedAt && $deductionHasCreatedAt
            ? 'GREATEST(COALESCE(pd.updated_at, pd.created_at), COALESCE(pd.created_at, pd.updated_at))'
            : ($deductionHasUpdatedAt ? 'pd.updated_at' : 'pd.created_at');

        $deductionStmt = $pdo->prepare("SELECT DISTINCT pd.emp_id
            FROM payroll_deductions pd
            WHERE pd.month = :month_year
              AND pd.status = 1
              AND {$deductionTimeExpr} > :request_created_at");
        $deductionStmt->execute([
            ':month_year' => $monthValue,
            ':request_created_at' => $requestCreatedAt,
        ]);

        foreach ($deductionStmt->fetchAll(PDO::FETCH_COLUMN) as $empId) {
            $empId = trim((string)$empId);
            if ($empId !== '') {
                $modifiedEmployees[$empId] = true;
            }
        }
    }

    return $modifiedEmployees;
}

function isHeadOfficeFinanceManagerRole(): bool
{
    $userType = strtolower(trim((string)($GLOBALS['user_type'] ?? '')));
    $empType = strtolower(trim((string)($GLOBALS['emp_type'] ?? '')));
    $userDept = (int)($GLOBALS['user_dept'] ?? 0);

    return $userType === 'finance' && $empType === 'manager' && $userDept === 2;
}

function getFinanceOfficerOptions(PDO $pdo): array
{
    $stmt = $pdo->prepare("SELECT al.emp_id, e.name
        FROM admin_login al
        INNER JOIN employees e ON e.emp_id = al.emp_id
        WHERE (LOWER(TRIM(COALESCE(al.user_type, ''))) = 'finance_officer'
               OR (LOWER(TRIM(COALESCE(al.user_type, ''))) = 'finance' AND LOWER(TRIM(COALESCE(al.emp_type, ''))) = 'manager'))
          AND (COALESCE(al.dept, 0) = 2)
        ORDER BY e.name ASC");
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getFinanceOfficerCompanyDefaults(PDO $pdo, string $financeManagerEmpId, array $officerEmpIds): array
{
    if ($financeManagerEmpId === '' || empty($officerEmpIds)) {
        return [];
    }

    $officerEmpIds = array_values(array_unique(array_filter(array_map(static function ($value) {
        return trim((string)$value);
    }, $officerEmpIds), static function ($value) {
        return $value !== '';
    })));

    if (empty($officerEmpIds)) {
        return [];
    }

    $params = [':finance_manager' => $financeManagerEmpId];
    $placeholders = [];
    foreach ($officerEmpIds as $index => $officerEmpId) {
        $key = ':officer_' . $index;
        $placeholders[] = $key;
        $params[$key] = $officerEmpId;
    }

    $stmt = $pdo->prepare("SELECT finance_officer_emp_id, selected_company_ids
        FROM payroll_finance_officer_company_defaults
        WHERE finance_manager_emp_id = :finance_manager
          AND finance_officer_emp_id IN (" . implode(', ', $placeholders) . ")");
    $stmt->execute($params);

    $defaults = [];
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $officerEmpId = trim((string)($row['finance_officer_emp_id'] ?? ''));
        if ($officerEmpId === '') {
            continue;
        }

        $companyIds = json_decode((string)($row['selected_company_ids'] ?? '[]'), true);
        if (!is_array($companyIds)) {
            $companyIds = [];
        }

        $companyIds = array_values(array_unique(array_filter(array_map(static function ($value) {
            return trim((string)$value);
        }, $companyIds), static function ($value) {
            return $value !== '';
        })));

        $defaults[$officerEmpId] = $companyIds;
    }

    return $defaults;
}

function getAssignedFinanceVerificationCompanyIds(PDO $pdo, string $requestInvNo, string $monthValue, string $financeOfficerEmpId): array
{
    if ($requestInvNo === '' || $monthValue === '' || $financeOfficerEmpId === '') {
        return [];
    }

    $stmt = $pdo->prepare("SELECT selected_company_ids
        FROM payroll_finance_verification
        WHERE request_inv_no = :inv_no
          AND payroll_month = :month_year
          AND finance_officer_emp_id = :finance_officer
          AND is_confirmed = 1
        ORDER BY id DESC
        LIMIT 1");
    $stmt->execute([
        ':inv_no' => $requestInvNo,
        ':month_year' => $monthValue,
        ':finance_officer' => $financeOfficerEmpId
    ]);

    $raw = $stmt->fetchColumn();
    $companyIds = json_decode((string)($raw ?: '[]'), true);
    if (!is_array($companyIds)) {
        return [];
    }

    return array_values(array_unique(array_filter(array_map(static function ($value) {
        return trim((string)$value);
    }, $companyIds), static function ($value) {
        return $value !== '';
    })));
}

function getAssignedFinanceVerificationCompanyIdsForVerifier(PDO $pdo, string $requestInvNo, string $monthValue, string $verifierEmpId): array
{
    if ($requestInvNo === '' || $monthValue === '' || $verifierEmpId === '') {
        return [];
    }

    $scope = getAssignedFinanceVerificationCompanyIds($pdo, $requestInvNo, $monthValue, $verifierEmpId);
    if (!empty($scope)) {
        return $scope;
    }

    $stmt = $pdo->prepare("SELECT selected_company_ids
        FROM payroll_finance_verification
        WHERE request_inv_no = :inv_no
          AND payroll_month = :month_year
          AND finance_manager_emp_id = :finance_manager
          AND is_confirmed = 1
        ORDER BY id DESC
        LIMIT 1");
    $stmt->execute([
        ':inv_no' => $requestInvNo,
        ':month_year' => $monthValue,
        ':finance_manager' => $verifierEmpId
    ]);

    $raw = $stmt->fetchColumn();
    $companyIds = json_decode((string)($raw ?: '[]'), true);
    if (!is_array($companyIds)) {
        return [];
    }

    return array_values(array_unique(array_filter(array_map(static function ($value) {
        return trim((string)$value);
    }, $companyIds), static function ($value) {
        return $value !== '';
    })));
}

function getLatestFinanceVerificationRow(PDO $pdo, string $requestInvNo, string $monthValue, string $financeManagerEmpId): array
{
    if ($requestInvNo === '' || $monthValue === '' || $financeManagerEmpId === '') {
        return [];
    }

        $stmt = $pdo->prepare("SELECT finance_officer_emp_id, is_confirmed, officer_approved, officer_approved_at
        FROM payroll_finance_verification
        WHERE request_inv_no = :inv_no
          AND payroll_month = :month_year
          AND finance_manager_emp_id = :manager_id
        ORDER BY id DESC
        LIMIT 1");
    $stmt->execute([
        ':inv_no' => $requestInvNo,
        ':month_year' => $monthValue,
        ':manager_id' => $financeManagerEmpId
    ]);

    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) {
        return [];
    }

    return [
        'finance_officer_emp_id' => trim((string)($row['finance_officer_emp_id'] ?? '')),
        'is_confirmed' => !empty($row['is_confirmed']),
        'officer_approved' => !empty($row['officer_approved']),
        'officer_approved_at' => trim((string)($row['officer_approved_at'] ?? ''))
    ];
}

function confirmFinanceOfficerVerification(PDO $pdo, string $currentUserId): void
{
    $requestInvNo = trim((string)($_POST['request_inv_no'] ?? ''));
    $monthYear = trim((string)($_POST['month'] ?? ''));

    try {
        ensurePayrollFinanceVerificationTable($pdo);

        if ($requestInvNo === '' || $monthYear === '') {
            throw new Exception('Missing request number or month.');
        }

        $verificationStmt = $pdo->prepare("SELECT id, selected_company_ids
            FROM payroll_finance_verification
            WHERE request_inv_no = :inv_no
              AND payroll_month = :month_year
              AND finance_officer_emp_id = :finance_officer
              AND is_confirmed = 1
            ORDER BY id DESC
            LIMIT 1");
        $verificationStmt->execute([
            ':inv_no' => $requestInvNo,
            ':month_year' => $monthYear,
            ':finance_officer' => $currentUserId
        ]);
        $verificationRow = $verificationStmt->fetch(PDO::FETCH_ASSOC);
        if (!$verificationRow) {
            throw new Exception('You are not assigned to approve this finance verification.');
        }

        $companyIds = json_decode((string)($verificationRow['selected_company_ids'] ?? '[]'), true);
        if (!is_array($companyIds)) {
            $companyIds = [];
        }
        $companyIds = array_values(array_unique(array_filter(array_map(static function ($value) {
            return trim((string)$value);
        }, $companyIds), static function ($value) {
            return $value !== '';
        })));

        if (empty($companyIds)) {
            throw new Exception('No assigned company scope found for this verification request.');
        }

        $scopeParams = [':payroll_month' => $monthYear];
        $companyPlaceholders = [];
        foreach ($companyIds as $index => $companyId) {
            $paramKey = ':assigned_company_' . $index;
            $companyPlaceholders[] = $paramKey;
            $scopeParams[$paramKey] = $companyId;
        }

        // Finance verifier approval is no longer tied to per-employee Mark Checked actions.
        // Only HR Payroll / HR Senior BP maintain checklist marks.

        $approveStmt = $pdo->prepare("UPDATE payroll_finance_verification
            SET officer_approved = 1,
                officer_approved_at = NOW()
            WHERE id = :id
            LIMIT 1");
        $approveStmt->execute([':id' => (int)$verificationRow['id']]);

        $historyStmt = $pdo->prepare("INSERT INTO payroll_finance_verification_history
            (request_inv_no, payroll_month, finance_manager_emp_id, finance_officer_emp_id, action_type, action_note, selected_company_ids, created_by)
            SELECT request_inv_no, payroll_month, finance_manager_emp_id, finance_officer_emp_id,
                   'officer_approved',
                   :action_note,
                   selected_company_ids,
                   :created_by
            FROM payroll_finance_verification
            WHERE id = :verification_id
            LIMIT 1");
        $historyStmt->execute([
            ':action_note' => 'Finance officer approved assigned verification scope.',
            ':created_by' => $currentUserId,
            ':verification_id' => (int)$verificationRow['id']
        ]);

        echo json_encode([
            'status' => 'success',
            'message' => 'Finance officer approval submitted successfully.'
        ]);
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
}

function getPayrollChecklistReviewSummary(PDO $pdo, string $requestInvNo, string $monthValue, string $approverId): array
{
    $assignedCompanyIds = getAssignedFinanceVerificationCompanyIdsForVerifier($pdo, $requestInvNo, $monthValue, $approverId);

    $canSeeAllEmployees = function_exists('canSeeAllPayrollEmployees')
        ? canSeeAllPayrollEmployees(true)
        : (
            ($GLOBALS['is_system_admin'] ?? false) ||
            (($GLOBALS['user_type'] ?? '') === 'administrator') ||
            (($GLOBALS['user_dept'] ?? null) == 5) ||
            ($GLOBALS['isHR'] ?? false) ||
            ($GLOBALS['isDeptHr'] ?? false)
        );

    $deptSql = '';
    $scopeParams = [':payroll_month_main' => $monthValue];

    if (!$canSeeAllEmployees && isset($GLOBALS['user_dept'])) {
        $deptSql = ' AND e.dept = :user_dept';
        $scopeParams[':user_dept'] = $GLOBALS['user_dept'];
    }

    if (!empty($assignedCompanyIds)) {
        $companyPlaceholders = [];
        foreach ($assignedCompanyIds as $index => $companyId) {
            $paramKey = ':assigned_company_' . $index;
            $companyPlaceholders[] = $paramKey;
            $scopeParams[$paramKey] = $companyId;
        }
        $deptSql .= " AND CAST(e.comp_no AS CHAR) IN (" . implode(', ', $companyPlaceholders) . ")";
    }

    $scopeStmt = $pdo->prepare("SELECT DISTINCT p.emp_id
        FROM payrolls p
        INNER JOIN employees e ON e.emp_id = p.emp_id
        WHERE p.month_year = :payroll_month_main" . $deptSql);
    $scopeStmt->execute($scopeParams);

    $scopeEmpIds = array_values(array_filter(array_map('trim', array_map('strval', $scopeStmt->fetchAll(PDO::FETCH_COLUMN))), static function ($empId) {
        return $empId !== '';
    }));

    $totalEmployees = count($scopeEmpIds);
    if ($totalEmployees <= 0) {
        return [
            'total_employees' => 0,
            'checked_employees' => 0,
            'remaining_employees' => 0
        ];
    }

    $modifiedByEmp = getPayrollChecklistModifiedEmployeeMap($pdo, $requestInvNo, $monthValue);
    $requiredEmpIds = [];
    foreach ($scopeEmpIds as $scopeEmpId) {
        if (!empty($modifiedByEmp[$scopeEmpId])) {
            $requiredEmpIds[] = $scopeEmpId;
        }
    }

    $requiredCount = count($requiredEmpIds);
    if ($requiredCount <= 0) {
        return [
            'total_employees' => $totalEmployees,
            'checked_employees' => $totalEmployees,
            'remaining_employees' => 0
        ];
    }

    $inPlaceholders = [];
    $checkedParams = [
        ':request_inv_no_check' => $requestInvNo,
        ':payroll_month_check' => $monthValue,
        ':approver_id_check' => $approverId,
    ];
    foreach ($requiredEmpIds as $idx => $requiredEmpId) {
        $key = ':req_emp_' . $idx;
        $inPlaceholders[] = $key;
        $checkedParams[$key] = $requiredEmpId;
    }

    $checkedStmt = $pdo->prepare("SELECT COUNT(DISTINCT pec.emp_id)
        FROM payroll_checklist_employee_checks pec
        WHERE pec.request_inv_no = :request_inv_no_check
          AND pec.payroll_month = :payroll_month_check
          AND pec.approver_id = :approver_id_check
          AND pec.is_checked = 1
          AND pec.emp_id IN (" . implode(', ', $inPlaceholders) . ")");
    $checkedStmt->execute($checkedParams);

    $checkedRequiredEmployees = (int)$checkedStmt->fetchColumn();
    $checkedEmployees = max(0, ($totalEmployees - $requiredCount) + $checkedRequiredEmployees);

    return [
        'total_employees' => $totalEmployees,
        'checked_employees' => $checkedEmployees,
        'remaining_employees' => max(0, $totalEmployees - $checkedEmployees)
    ];
}

function getHrPayrollChecklistReviewSummary(PDO $pdo, string $requestInvNo, string $monthValue): array
{
    $totalStmt = $pdo->prepare("SELECT COUNT(DISTINCT emp_id) FROM payrolls WHERE month_year = :month_year");
    $totalStmt->execute([':month_year' => $monthValue]);
    $totalEmployees = (int)$totalStmt->fetchColumn();

    if ($totalEmployees <= 0) {
        return [
            'total_employees' => 0,
            'checked_employees' => 0,
            'remaining_employees' => 0,
            'approver_id' => ''
        ];
    }

    $summaryStmt = $pdo->prepare("SELECT
            pec.approver_id,
            COUNT(DISTINCT CASE WHEN pec.is_checked = 1 THEN pec.emp_id END) AS checked_employees
        FROM payroll_checklist_employee_checks pec
        INNER JOIN admin_login al ON al.emp_id = pec.approver_id
        WHERE pec.request_inv_no = :request_inv_no
          AND pec.payroll_month = :month_year
          AND LOWER(TRIM(al.user_type)) = 'hr_payroll'
        GROUP BY pec.approver_id
        ORDER BY checked_employees DESC, pec.approver_id ASC
        LIMIT 1");
    $summaryStmt->execute([
        ':request_inv_no' => $requestInvNo,
        ':month_year' => $monthValue
    ]);
    $summary = $summaryStmt->fetch(PDO::FETCH_ASSOC) ?: [];

    $checkedEmployees = (int)($summary['checked_employees'] ?? 0);

    return [
        'total_employees' => $totalEmployees,
        'checked_employees' => $checkedEmployees,
        'remaining_employees' => max(0, $totalEmployees - $checkedEmployees),
        'approver_id' => (string)($summary['approver_id'] ?? '')
    ];
}

function getPayrollFeedbackSummary(PDO $pdo, string $requestInvNo, string $monthValue): array
{
    $summaryStmt = $pdo->prepare("SELECT
            COUNT(*) AS total_feedback,
            SUM(CASE WHEN status = 'open' THEN 1 ELSE 0 END) AS open_feedback,
            SUM(CASE WHEN status = 'resolved' THEN 1 ELSE 0 END) AS resolved_feedback,
            SUM(CASE WHEN status = 'open' AND followup_sent_at IS NULL THEN 1 ELSE 0 END) AS pending_followup_count,
            COUNT(DISTINCT emp_id) AS affected_employees
        FROM payroll_checklist_feedback
        WHERE request_inv_no = :request_inv_no
          AND payroll_month = :payroll_month");
    $summaryStmt->execute([
        ':request_inv_no' => $requestInvNo,
        ':payroll_month' => $monthValue
    ]);
    $summary = $summaryStmt->fetch(PDO::FETCH_ASSOC) ?: [];

    return [
        'total_feedback' => (int)($summary['total_feedback'] ?? 0),
        'open_feedback' => (int)($summary['open_feedback'] ?? 0),
        'resolved_feedback' => (int)($summary['resolved_feedback'] ?? 0),
        'pending_followup_count' => (int)($summary['pending_followup_count'] ?? 0),
        'affected_employees' => (int)($summary['affected_employees'] ?? 0)
    ];
}

function getPayrollFeedbackItems(PDO $pdo, string $requestInvNo, string $monthValue): array
{
    $detailsStmt = $pdo->prepare("SELECT
            f.emp_id,
            COALESCE(e.name, f.emp_id) AS employee_name,
            f.feedback_note,
            f.created_at
        FROM payroll_checklist_feedback f
        LEFT JOIN employees e ON e.emp_id = f.emp_id
        WHERE f.request_inv_no = :request_inv_no
          AND f.payroll_month = :payroll_month
          AND f.status = 'open'
          AND f.followup_sent_at IS NULL
        ORDER BY f.emp_id ASC, f.created_at DESC, f.id DESC");
    $detailsStmt->execute([
        ':request_inv_no' => $requestInvNo,
        ':payroll_month' => $monthValue
    ]);

    $rows = $detailsStmt->fetchAll(PDO::FETCH_ASSOC);
    $grouped = [];

    foreach ($rows as $row) {
        $empId = trim((string)($row['emp_id'] ?? ''));
        if ($empId === '') {
            continue;
        }

        if (!isset($grouped[$empId])) {
            $employeeName = trim((string)($row['employee_name'] ?? ''));
            if (function_exists('getDisplayName')) {
                $employeeName = getDisplayName($employeeName);
            }

            $grouped[$empId] = [
                'emp_id' => $empId,
                'employee_name' => $employeeName !== '' ? $employeeName : $empId,
                'reasons' => []
            ];
        }

        $reason = trim((string)($row['feedback_note'] ?? ''));
        if ($reason !== '' && !in_array($reason, $grouped[$empId]['reasons'], true)) {
            $grouped[$empId]['reasons'][] = $reason;
        }
    }

    return array_values($grouped);
}

function sendPayrollFeedbackReminderNotification(PDO $pdo, $conDB, string $requestInvNo, string $monthValue, string $requesterId, array $feedbackSummary): array
{
    $result = [
        'notification_sent' => false,
        'email_sent' => false
    ];

    if ($requesterId === '') {
        return $result;
    }

    $openFeedback = max(0, (int)($feedbackSummary['open_feedback'] ?? 0));
    $totalFeedback = max(0, (int)($feedbackSummary['total_feedback'] ?? 0));
    $affectedEmployees = max(0, (int)($feedbackSummary['affected_employees'] ?? 0));

    if ($totalFeedback <= 0 || $affectedEmployees <= 0) {
        return $result;
    }

    $feedbackCountText = $openFeedback === 1 ? '1 open feedback item' : $openFeedback . ' open feedback items';
    $employeeCountText = $affectedEmployees === 1 ? '1 employee record' : $affectedEmployees . ' employee records';
    $feedbackItems = getPayrollFeedbackItems($pdo, $requestInvNo, $monthValue);
    $notificationTitle = 'Payroll Feedback Follow-up Required';
    $notificationMessage = 'Payroll ' . $monthValue . ' has ' . $feedbackCountText . ' across ' . $employeeCountText . '. Please review the approver notes and update the payroll.';
    $requestUrl = 'generate_payroll.php';

    if (function_exists('create_and_show_notification')) {
        create_and_show_notification($conDB, $requesterId, $notificationTitle, $notificationMessage, $requestUrl, 'warning');
        $result['notification_sent'] = true;
    } elseif (function_exists('create_browser_notification')) {
        create_browser_notification($conDB, (int)$requesterId, $notificationTitle, $notificationMessage, $requestUrl);
        $result['notification_sent'] = true;
    }

    if (!function_exists('send_approval_email')) {
        return $result;
    }

    $requesterQuery = "SELECT e.name, e.emp_id, al.email FROM employees e
                      LEFT JOIN admin_login al ON al.emp_id = e.emp_id
                      WHERE e.emp_id = ? LIMIT 1";
    $requesterStmt = $conDB->prepare($requesterQuery);
    if (!$requesterStmt) {
        return $result;
    }

    $requesterIdInt = (int)$requesterId;
    $requesterStmt->bind_param('i', $requesterIdInt);
    $requesterStmt->execute();
    $requesterResult = $requesterStmt->get_result();
    $requester = $requesterResult ? $requesterResult->fetch_assoc() : null;
    if ($requesterResult) {
        $requesterResult->free();
    }
    $requesterStmt->close();

    if (empty($requester) || empty($requester['email'])) {
        return $result;
    }

    $selectedEmployeeIds = array_values(array_unique(array_filter(array_map(static function ($item) {
        return trim((string)($item['emp_id'] ?? ''));
    }, $feedbackItems))));

    $selectedEmployeeCount = count($selectedEmployeeIds);
    $selectedTotalNetSalary = 0.0;

    if (!empty($selectedEmployeeIds)) {
        $salaryPlaceholders = [];
        $salaryParams = [':month' => $monthValue];

        foreach ($selectedEmployeeIds as $index => $employeeId) {
            $placeholder = ':emp_id_' . $index;
            $salaryPlaceholders[] = $placeholder;
            $salaryParams[$placeholder] = $employeeId;
        }

        $payrollSummaryStmt = $pdo->prepare("SELECT COUNT(DISTINCT emp_id) AS employee_count, COALESCE(SUM(net_salary), 0) AS total_net_salary
            FROM payrolls
            WHERE month_year = :month
              AND emp_id IN (" . implode(', ', $salaryPlaceholders) . ")");
        $payrollSummaryStmt->execute($salaryParams);
        $payrollSummary = $payrollSummaryStmt->fetch(PDO::FETCH_ASSOC) ?: [];

        $selectedEmployeeCount = (int)($payrollSummary['employee_count'] ?? $selectedEmployeeCount);
        $selectedTotalNetSalary = (float)($payrollSummary['total_net_salary'] ?? 0);
    }

    $feedbackDetailsHtml = '';
    if (!empty($feedbackItems)) {
        $rowsHtml = [];
        foreach ($feedbackItems as $item) {
            $reasonLines = [];
            foreach (($item['reasons'] ?? []) as $reason) {
                $reasonLines[] = '<li style="margin:4px 0; line-height:1.6;">' . nl2br(htmlspecialchars((string)$reason, ENT_QUOTES, 'UTF-8')) . '</li>';
            }

            $rowsHtml[] = '<li style="margin-bottom:12px;">'
                . '<strong>Employee ID:</strong> ' . htmlspecialchars((string)($item['emp_id'] ?? ''), ENT_QUOTES, 'UTF-8')
                . ' &nbsp;|&nbsp; <strong>Name:</strong> ' . htmlspecialchars((string)($item['employee_name'] ?? ''), ENT_QUOTES, 'UTF-8')
                . (!empty($reasonLines)
                    ? '<div style="margin-top:6px;"><strong>Reason(s):</strong><ul style="margin:6px 0 0 18px; padding:0;">' . implode('', $reasonLines) . '</ul></div>'
                    : '')
                . '</li>';
        }

        if (!empty($rowsHtml)) {
            $feedbackDetailsHtml = '<div style="margin-top:16px; text-align:left; background:#2a2a2a; border:1px solid #404040; border-radius:8px; padding:14px 16px;">'
                . '<div style="font-weight:700; color:#ffffff; margin-bottom:8px;">Employees to review:</div>'
                . '<ul style="margin:0 0 0 18px; padding:0; color:#e0e0e0;">' . implode('', $rowsHtml) . '</ul>'
                . '</div>';
        }
    }

    $emailIntroText = 'Payroll ' . $monthValue . ' currently has ' . $feedbackCountText . ' across ' . $employeeCountText . '. Please review the approver feedback, apply the required changes, and resubmit the payroll.';

    $emailSubject = 'Payroll Feedback Follow-up Required - ' . $monthValue . ' (' . $requestInvNo . ')';
    $templateData = [
        'APPROVER_NAME' => !empty($requester['name']) ? htmlspecialchars($requester['name']) : 'Requester',
        'REQUEST_ID' => $requestInvNo,
        'REQUEST_TYPE' => 'Payroll Feedback Follow-up',
        'PAYROLL_MONTH' => $monthValue,
        'EMPLOYEE_COUNT' => (string)$selectedEmployeeCount,
        'TOTAL_NET_SALARY' => number_format($selectedTotalNetSalary, 2),
        'PAYROLL_STATUS' => 'Feedback Pending Review',
        'EMAIL_MESSAGE' => $emailIntroText,
        'EMAIL_MESSAGE_HTML' => '<div style="margin:0; color:#e0e0e0; line-height:1.7;">' . htmlspecialchars($emailIntroText, ENT_QUOTES, 'UTF-8') . '</div>' . $feedbackDetailsHtml,
        'REQUEST_URL' => (function_exists('get_base_url') ? get_base_url() : 'https://hr.almutlaksystem.com') . '/generate_payroll.php'
    ];

    $result['email_sent'] = (bool)send_approval_email(
        $conDB,
        $requester['email'],
        $requester['name'] ?? 'Requester',
        $emailSubject,
        'payroll_request',
        $templateData
    );

    return $result;
}

function notifyPayrollFeedbackFollowup(PDO $pdo, $conDB, string $currentUserId, int $requestTypeId): void
{
    $requestInvNo = trim((string)($_POST['request_inv_no'] ?? ''));
    $monthYear = trim((string)($_POST['month'] ?? ''));

    if ($requestInvNo === '') {
        echo json_encode(['status' => 'error', 'message' => 'Missing request number']);
        return;
    }

    try {
        ensurePayrollChecklistFeedbackTable($pdo);

        $currentUserRole = strtolower(trim((string)($GLOBALS['user_type'] ?? '')));
        if ($currentUserRole !== 'hr_payroll') {
            throw new Exception('You are not allowed to send this payroll feedback follow-up.');
        }

        $requestStmt = $pdo->prepare("SELECT payroll_month, requested_by, status FROM payroll_approval_requests WHERE request_inv_no = :inv_no LIMIT 1");
        $requestStmt->execute([':inv_no' => $requestInvNo]);
        $requestRow = $requestStmt->fetch(PDO::FETCH_ASSOC);
        if (!$requestRow) {
            throw new Exception('Payroll request not found');
        }

        if (function_exists('removePayrollFinanceOfficerStep')) {
            removePayrollFinanceOfficerStep($pdo, $requestInvNo);
        }

        $monthValue = $monthYear !== '' ? $monthYear : (string)($requestRow['payroll_month'] ?? '');
        $feedbackSummary = getPayrollFeedbackSummary($pdo, $requestInvNo, $monthValue);
        if ($feedbackSummary['pending_followup_count'] <= 0) {
            throw new Exception('There is no new feedback pending follow-up sending.');
        }

        $notificationResult = sendPayrollFeedbackReminderNotification(
            $pdo,
            $conDB,
            $requestInvNo,
            $monthValue,
            (string)($requestRow['requested_by'] ?? ''),
            $feedbackSummary
        );

        if (!$notificationResult['notification_sent'] && !$notificationResult['email_sent']) {
            throw new Exception('Unable to send the feedback follow-up notification.');
        }

        $markSentStmt = $pdo->prepare("UPDATE payroll_checklist_feedback
            SET followup_sent_at = NOW(), followup_sent_by = :sent_by
            WHERE request_inv_no = :inv_no
              AND payroll_month = :month_value
              AND status = 'open'
              AND followup_sent_at IS NULL");
        $markSentStmt->execute([
            ':sent_by' => $currentUserId,
            ':inv_no' => $requestInvNo,
            ':month_value' => $monthValue
        ]);

        $feedbackSummary = getPayrollFeedbackSummary($pdo, $requestInvNo, $monthValue);

        $history = $pdo->prepare("INSERT INTO smt_request_status (inv_no, emp_id, emp_name, note, status)
            VALUES (:inv_no, :emp_id, :emp_name, :note, 'feedback')");
        $history->execute([
            ':inv_no' => $requestInvNo,
            ':emp_id' => $currentUserId,
            ':emp_name' => 'System',
            ':note' => 'Feedback follow-up reminder sent for payroll ' . $monthValue . '. Open feedback items: ' . $feedbackSummary['open_feedback'] . '. Affected employees: ' . $feedbackSummary['affected_employees'] . '.'
        ]);

        echo json_encode([
            'status' => 'success',
            'message' => 'Follow-up email and notification were sent to the payroll generator successfully.',
            'notification_sent' => (bool)$notificationResult['notification_sent'],
            'email_sent' => (bool)$notificationResult['email_sent'],
            'feedback_summary' => $feedbackSummary
        ]);
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
}

function togglePayrollEmployeeCheck(PDO $pdo, string $currentUserId, int $requestTypeId): void
{
    $requestInvNo = trim((string)($_POST['request_inv_no'] ?? ''));
    $monthYear = trim((string)($_POST['month'] ?? ''));
    $empId = trim((string)($_POST['emp_id'] ?? ''));
    $isChecked = (string)($_POST['checked'] ?? '1') === '1';

    if ($requestInvNo === '' || $empId === '') {
        echo json_encode(['status' => 'error', 'message' => 'Missing employee or request information']);
        return;
    }

    try {
        $currentUserRole = strtolower(trim((string)($GLOBALS['user_type'] ?? '')));
        $isHrPayrollApprover = $currentUserRole === 'hr_payroll';
        $isHrSeniorBpApprover = $currentUserRole === 'hr_senior_bp';
        $isHrChecklistApprover = $isHrPayrollApprover || $isHrSeniorBpApprover;

        ensurePayrollChecklistReviewTable($pdo);

        $requestStmt = $pdo->prepare("SELECT payroll_month, status FROM payroll_approval_requests WHERE request_inv_no = :inv_no LIMIT 1");
        $requestStmt->execute([':inv_no' => $requestInvNo]);
        $requestRow = $requestStmt->fetch(PDO::FETCH_ASSOC);
        if (!$requestRow) {
            throw new Exception('Payroll request not found');
        }

        $monthValue = $monthYear !== '' ? $monthYear : (string)($requestRow['payroll_month'] ?? '');
        if ($monthValue === '') {
            throw new Exception('Payroll month is missing for this request.');
        }

        if (!$isHrChecklistApprover) {
            throw new Exception('You are not allowed to update employee checklist checks for this payroll request.');
        }

        $assignedCompanyIds = [];

        $requestIsApproved = strtolower(trim((string)($requestRow['status'] ?? ''))) === 'approved';

        if (!$requestIsApproved) {
            // During approval phase: verify the HR checklist user has an active pending slot.
            $permissionStmt = $pdo->prepare("SELECT COUNT(*) FROM request_approvers
                WHERE request_inv_no = :inv_no AND request_type_id = :type_id AND approver_id = :approver_id AND status = 'pending'");
            $permissionStmt->execute([
                ':inv_no' => $requestInvNo,
                ':type_id' => $requestTypeId,
                ':approver_id' => $currentUserId
            ]);

            if ((int)$permissionStmt->fetchColumn() <= 0) {
                throw new Exception('You are not allowed to update employee checklist checks for this payroll request.');
            }
        }
        // If request is already approved, authorized users may re-mark checks after feedback.

        $canSeeAllEmployees = function_exists('canSeeAllPayrollEmployees')
            ? canSeeAllPayrollEmployees(true)
            : (
                ($GLOBALS['is_system_admin'] ?? false) ||
                (($GLOBALS['user_type'] ?? '') === 'administrator') ||
                (($GLOBALS['user_dept'] ?? null) == 5) ||
                ($GLOBALS['isHR'] ?? false) ||
                ($GLOBALS['isDeptHr'] ?? false)
            );

        $employeeSql = "SELECT COUNT(*)
            FROM payrolls p
            INNER JOIN employees e ON e.emp_id = p.emp_id
            WHERE p.month_year = :month_year
              AND p.emp_id = :emp_id";
        $employeeParams = [
            ':month_year' => $monthValue,
            ':emp_id' => $empId
        ];

        if (!$canSeeAllEmployees && isset($GLOBALS['user_dept'])) {
            $employeeSql .= " AND e.dept = :user_dept";
            $employeeParams[':user_dept'] = $GLOBALS['user_dept'];
        }

        if (!empty($assignedCompanyIds)) {
            $companyPlaceholders = [];
            foreach ($assignedCompanyIds as $index => $companyId) {
                $paramKey = ':assigned_company_' . $index;
                $companyPlaceholders[] = $paramKey;
                $employeeParams[$paramKey] = $companyId;
            }
            $employeeSql .= " AND CAST(e.comp_no AS CHAR) IN (" . implode(', ', $companyPlaceholders) . ")";
        }

        $employeeStmt = $pdo->prepare($employeeSql);
        $employeeStmt->execute($employeeParams);
        if ((int)$employeeStmt->fetchColumn() <= 0) {
            throw new Exception('This employee is not available in your payroll checklist scope.');
        }

        $modifiedByEmp = getPayrollChecklistModifiedEmployeeMap($pdo, $requestInvNo, $monthValue);
        if (empty($modifiedByEmp[$empId])) {
            throw new Exception('This employee is auto-checked by default because payroll values were not modified.');
        }

        $checkedAt = $isChecked ? date('Y-m-d H:i:s') : null;
        $saveStmt = $pdo->prepare("INSERT INTO payroll_checklist_employee_checks
                (request_inv_no, payroll_month, emp_id, approver_id, is_checked, checked_at)
            VALUES
                (:request_inv_no_insert, :payroll_month_insert, :emp_id_insert, :approver_id_insert, :is_checked_insert, :checked_at_insert)
            ON DUPLICATE KEY UPDATE
                is_checked = :is_checked_update,
                checked_at = :checked_at_update");
        $saveStmt->execute([
            ':request_inv_no_insert' => $requestInvNo,
            ':payroll_month_insert' => $monthValue,
            ':emp_id_insert' => $empId,
            ':approver_id_insert' => $currentUserId,
            ':is_checked_insert' => $isChecked ? 1 : 0,
            ':checked_at_insert' => $checkedAt,
            ':is_checked_update' => $isChecked ? 1 : 0,
            ':checked_at_update' => $checkedAt
        ]);

        echo json_encode([
            'status' => 'success',
            'message' => $isChecked ? 'Employee marked as checked successfully.' : 'Employee checklist mark removed successfully.',
            'is_checked' => $isChecked,
            'checked_at' => $checkedAt,
            'review_summary' => getPayrollChecklistReviewSummary($pdo, $requestInvNo, $monthValue, $currentUserId)
        ]);
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
}

function getFinanceVerificationSetup(PDO $pdo, string $currentUserId, int $requestTypeId): void
{
    $requestInvNo = trim((string)($_POST['request_inv_no'] ?? ''));
    $monthYear = trim((string)($_POST['month'] ?? ''));

    try {
        ensurePayrollFinanceVerificationTable($pdo);

        if (!isHeadOfficeFinanceManagerRole()) {
            throw new Exception('Only Head Office Finance Manager can access this action.');
        }

        if ($requestInvNo === '' || $monthYear === '') {
            throw new Exception('Missing request number or month.');
        }

        $requestStmt = $pdo->prepare("SELECT payroll_month, status FROM payroll_approval_requests WHERE request_inv_no = :inv_no LIMIT 1");
        $requestStmt->execute([':inv_no' => $requestInvNo]);
        $requestRow = $requestStmt->fetch(PDO::FETCH_ASSOC);
        if (!$requestRow) {
            throw new Exception('Payroll request not found.');
        }

        $monthValue = (string)($requestRow['payroll_month'] ?? $monthYear);
        $requestStatus = strtolower(trim((string)($requestRow['status'] ?? '')));
        if ($requestStatus !== 'pending_approval') {
            throw new Exception('This setup can be done only while request is pending approval.');
        }

        $pendingStmt = $pdo->prepare("SELECT COUNT(*) FROM request_approvers
            WHERE request_inv_no = :inv_no
              AND request_type_id = :type_id
              AND approver_id = :approver_id
              AND status = 'pending'");
        $pendingStmt->execute([
            ':inv_no' => $requestInvNo,
            ':type_id' => $requestTypeId,
            ':approver_id' => $currentUserId
        ]);
        if ((int)$pendingStmt->fetchColumn() <= 0) {
            throw new Exception('This payroll request is not pending with you.');
        }

        ensurePayrollFinanceOfficerCompanyDefaultsTable($pdo);

        $officers = getFinanceOfficerOptions($pdo);
        if (empty($officers)) {
            throw new Exception('No finance officer found for department 2.');
        }

        $officerEmpIds = array_values(array_unique(array_filter(array_map(static function ($row) {
            return trim((string)($row['emp_id'] ?? ''));
        }, $officers), static function ($value) {
            return $value !== '';
        })));
        $officerCompanyDefaults = getFinanceOfficerCompanyDefaults($pdo, $currentUserId, $officerEmpIds);

        $companiesStmt = $pdo->prepare("SELECT
                c.comp_id AS comp_id,
                c.comp_name AS comp_name,
                COUNT(DISTINCT p.emp_id) AS employee_count,
                COALESCE(SUM(p.net_salary), 0) AS total_net_salary
            FROM payrolls p
            INNER JOIN employees e ON e.emp_id = p.emp_id
            INNER JOIN companies c ON c.comp_id = e.comp_no
            WHERE p.month_year = :month_year
            GROUP BY c.comp_id, c.comp_name
            ORDER BY c.comp_name ASC");
        $companiesStmt->execute([':month_year' => $monthValue]);
        $companies = $companiesStmt->fetchAll(PDO::FETCH_ASSOC);

                $existingStmt = $pdo->prepare("SELECT
                                v.finance_officer_emp_id,
                                v.selected_company_ids,
                                v.selected_employee_ids,
                                v.is_confirmed,
                                v.confirmed_at,
                                v.officer_approved,
                                v.officer_approved_at,
                                e.name AS finance_officer_name
                        FROM payroll_finance_verification v
                        LEFT JOIN employees e ON e.emp_id = v.finance_officer_emp_id
                        WHERE v.request_inv_no = :inv_no
                            AND v.payroll_month = :month_year
                            AND v.finance_manager_emp_id = :manager_id
                    ORDER BY v.id DESC
                        LIMIT 1");
        $existingStmt->execute([
            ':inv_no' => $requestInvNo,
            ':month_year' => $monthValue,
            ':manager_id' => $currentUserId
        ]);
        $existing = $existingStmt->fetch(PDO::FETCH_ASSOC) ?: [];

        $existingCompanyIds = json_decode((string)($existing['selected_company_ids'] ?? '[]'), true);

        $historyStmt = $pdo->prepare("SELECT
                h.id,
                h.action_type,
                h.action_note,
                h.selected_company_ids,
            h.finance_officer_emp_id,
                h.created_by,
                h.created_at,
                COALESCE(e.name, h.created_by) AS created_by_name,
                COALESCE(fo.name, h.finance_officer_emp_id) AS finance_officer_name
            FROM payroll_finance_verification_history h
            LEFT JOIN employees e ON e.emp_id = h.created_by
            LEFT JOIN employees fo ON fo.emp_id = h.finance_officer_emp_id
            WHERE h.request_inv_no = :inv_no
              AND h.payroll_month = :month_year
            ORDER BY h.id DESC
            LIMIT 25");
        $historyStmt->execute([
            ':inv_no' => $requestInvNo,
            ':month_year' => $monthValue
        ]);
        $historyRows = $historyStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        $assignedOfficerEmpIds = array_values(array_unique(array_filter(array_map(static function ($row) {
            return trim((string)($row['finance_officer_emp_id'] ?? ''));
        }, $historyRows), static function ($value) {
            return $value !== '';
        })));

        echo json_encode([
            'status' => 'success',
            'officers' => array_map(static function ($row) {
                return [
                    'emp_id' => (string)($row['emp_id'] ?? ''),
                    'name' => (string)($row['name'] ?? '')
                ];
            }, $officers),
            'officer_company_defaults' => $officerCompanyDefaults,
            'companies' => array_map(static function ($row) {
                return [
                    'comp_id' => (string)($row['comp_id'] ?? ''),
                    'comp_name' => (string)($row['comp_name'] ?? 'N/A'),
                    'employee_count' => (int)($row['employee_count'] ?? 0),
                    'total_net_salary' => (float)($row['total_net_salary'] ?? 0)
                ];
            }, $companies),
            'existing' => [
                'finance_officer_emp_id' => (string)($existing['finance_officer_emp_id'] ?? ''),
                'finance_officer_name' => (string)($existing['finance_officer_name'] ?? ''),
                'selected_company_ids' => is_array($existingCompanyIds) ? $existingCompanyIds : [],
                'is_confirmed' => !empty($existing['is_confirmed']),
                'confirmed_at' => (string)($existing['confirmed_at'] ?? ''),
                'officer_approved' => !empty($existing['officer_approved']),
                'officer_approved_at' => (string)($existing['officer_approved_at'] ?? '')
            ],
            'assigned_officer_emp_ids' => $assignedOfficerEmpIds,
            'history' => array_map(static function ($row) {
                $companyIds = json_decode((string)($row['selected_company_ids'] ?? '[]'), true);
                if (!is_array($companyIds)) {
                    $companyIds = [];
                }

                $companyIds = array_values(array_filter(array_map(static function ($value) {
                    return trim((string)$value);
                }, $companyIds), static function ($value) {
                    return $value !== '';
                }));

                return [
                    'id' => (int)($row['id'] ?? 0),
                    'action_type' => (string)($row['action_type'] ?? ''),
                    'action_note' => (string)($row['action_note'] ?? ''),
                    'company_count' => count($companyIds),
                    'selected_company_ids' => $companyIds,
                    'finance_officer_emp_id' => (string)($row['finance_officer_emp_id'] ?? ''),
                    'created_by' => (string)($row['created_by'] ?? ''),
                    'created_by_name' => (string)($row['created_by_name'] ?? ''),
                    'finance_officer_name' => (string)($row['finance_officer_name'] ?? ''),
                    'created_at' => (string)($row['created_at'] ?? '')
                ];
            }, $historyRows)
        ]);
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
}

function submitFinanceVerificationSetup(PDO $pdo, $conDB, string $currentUserId, int $requestTypeId): void
{
    $requestInvNo = trim((string)($_POST['request_inv_no'] ?? ''));
    $monthYear = trim((string)($_POST['month'] ?? ''));
    $financeOfficerEmpId = trim((string)($_POST['finance_officer_emp_id'] ?? ''));
    $companyIdsRaw = $_POST['company_ids'] ?? '[]';

    try {
        ensurePayrollFinanceVerificationTable($pdo);
        ensurePayrollFinanceOfficerCompanyDefaultsTable($pdo);

        if (!isHeadOfficeFinanceManagerRole()) {
            throw new Exception('Only Head Office Finance Manager can submit this setup.');
        }

        if ($requestInvNo === '' || $monthYear === '' || $financeOfficerEmpId === '') {
            throw new Exception('Missing required verification setup data.');
        }

        $companyIds = json_decode((string)$companyIdsRaw, true);
        if (!is_array($companyIds)) {
            $companyIds = [];
        }

        $companyIds = array_values(array_unique(array_filter(array_map(static function ($value) {
            return trim((string)$value);
        }, $companyIds), static function ($value) {
            return $value !== '';
        })));

        if (empty($companyIds)) {
            throw new Exception('Please select finance officer and companies to confirm verification setup.');
        }

        $requestStmt = $pdo->prepare("SELECT payroll_month, status FROM payroll_approval_requests WHERE request_inv_no = :inv_no LIMIT 1");
        $requestStmt->execute([':inv_no' => $requestInvNo]);
        $requestRow = $requestStmt->fetch(PDO::FETCH_ASSOC);
        if (!$requestRow) {
            throw new Exception('Payroll request not found.');
        }

        $monthValue = (string)($requestRow['payroll_month'] ?? $monthYear);
        $requestStatus = strtolower(trim((string)($requestRow['status'] ?? '')));
        if ($requestStatus !== 'pending_approval') {
            throw new Exception('This setup can be done only while request is pending approval.');
        }

        $pendingStmt = $pdo->prepare("SELECT COUNT(*) FROM request_approvers
            WHERE request_inv_no = :inv_no
              AND request_type_id = :type_id
              AND approver_id = :approver_id
              AND status = 'pending'");
        $pendingStmt->execute([
            ':inv_no' => $requestInvNo,
            ':type_id' => $requestTypeId,
            ':approver_id' => $currentUserId
        ]);
        if ((int)$pendingStmt->fetchColumn() <= 0) {
            throw new Exception('This payroll request is not pending with you.');
        }

        $officerStmt = $pdo->prepare("SELECT al.emp_id, e.name, al.email
            FROM admin_login al
            INNER JOIN employees e ON e.emp_id = al.emp_id
            WHERE al.emp_id = :emp_id
              AND (LOWER(TRIM(COALESCE(al.user_type, ''))) = 'finance_officer'
                                     OR (LOWER(TRIM(COALESCE(al.user_type, ''))) = 'finance' AND LOWER(TRIM(COALESCE(al.emp_type, ''))) = 'manager'))
              AND COALESCE(al.dept, 0) = 2
            LIMIT 1");
        $officerStmt->execute([':emp_id' => $financeOfficerEmpId]);
        $officerRow = $officerStmt->fetch(PDO::FETCH_ASSOC);
        if (!$officerRow) {
            throw new Exception('Selected finance officer is invalid.');
        }

        $validCompanyStmt = $pdo->prepare("SELECT DISTINCT CAST(e.comp_no AS CHAR)
            FROM payrolls p
            INNER JOIN employees e ON e.emp_id = p.emp_id
            WHERE p.month_year = :month_year");
        $validCompanyStmt->execute([':month_year' => $monthValue]);
        $validCompanyIds = array_values(array_filter(array_map('strval', $validCompanyStmt->fetchAll(PDO::FETCH_COLUMN))));
        $validCompanyMap = array_fill_keys($validCompanyIds, true);
        foreach ($companyIds as $companyId) {
            if (!isset($validCompanyMap[$companyId])) {
                throw new Exception('One selected company is invalid for this payroll month.');
            }
        }

        // Update existing assignment row and append newly selected companies.
        $existingVerificationStmt = $pdo->prepare("SELECT id, selected_company_ids, selected_employee_ids
            FROM payroll_finance_verification
            WHERE request_inv_no = :inv_no
              AND payroll_month = :month_year
              AND finance_manager_emp_id = :manager_id
            ORDER BY id DESC
            LIMIT 1");
        $existingVerificationStmt->execute([
            ':inv_no' => $requestInvNo,
            ':month_year' => $monthValue,
            ':manager_id' => $currentUserId
        ]);
        $existingVerificationRow = $existingVerificationStmt->fetch(PDO::FETCH_ASSOC) ?: [];

        $existingCompanyIds = json_decode((string)($existingVerificationRow['selected_company_ids'] ?? '[]'), true);
        if (!is_array($existingCompanyIds)) {
            $existingCompanyIds = [];
        }
        $existingCompanyIds = array_values(array_unique(array_filter(array_map(static function ($value) {
            return trim((string)$value);
        }, $existingCompanyIds), static function ($value) {
            return $value !== '';
        })));

        $existingEmployeeIds = json_decode((string)($existingVerificationRow['selected_employee_ids'] ?? '[]'), true);
        if (!is_array($existingEmployeeIds)) {
            $existingEmployeeIds = [];
        }
        $existingEmployeeIds = array_values(array_unique(array_filter(array_map(static function ($value) {
            return trim((string)$value);
        }, $existingEmployeeIds), static function ($value) {
            return $value !== '';
        })));

        // Cumulative scope for main verification table.
        $assignedCompanyIds = array_values(array_unique(array_merge($existingCompanyIds, $companyIds)));
        $assignedCompanyIdsJson = json_encode($assignedCompanyIds, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        // Backfill assigned officers from history so selected_employee_ids remains complete.
        $historyOfficerStmt = $pdo->prepare("SELECT DISTINCT finance_officer_emp_id
            FROM payroll_finance_verification_history
            WHERE request_inv_no = :inv_no
              AND payroll_month = :month_year
              AND finance_manager_emp_id = :manager_id
              AND finance_officer_emp_id IS NOT NULL
              AND TRIM(finance_officer_emp_id) <> ''");
        $historyOfficerStmt->execute([
            ':inv_no' => $requestInvNo,
            ':month_year' => $monthValue,
            ':manager_id' => $currentUserId
        ]);
        $historyOfficerIds = array_values(array_unique(array_filter(array_map(static function ($value) {
            return trim((string)$value);
        }, $historyOfficerStmt->fetchAll(PDO::FETCH_COLUMN)), static function ($value) {
            return $value !== '';
        })));

        // Keep cumulative assigned officers in selected_employee_ids on main verification table.
        $assignedVerifierIds = array_values(array_unique(array_merge($existingEmployeeIds, $historyOfficerIds, [$financeOfficerEmpId])));
        $selectedEmployeeIdsJson = json_encode($assignedVerifierIds, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        // Per-officer scope for defaults/history tables (do NOT merge with previous officers).
        $currentOfficerCompanyIdsJson = json_encode($companyIds, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        $existingVerificationId = (int)($existingVerificationRow['id'] ?? 0);
        if ($existingVerificationId > 0) {
            $updateAssignmentStmt = $pdo->prepare("UPDATE payroll_finance_verification
                SET finance_officer_emp_id = :finance_officer_emp_id,
                    selected_company_ids = :selected_company_ids,
                    selected_employee_ids = :selected_employee_ids,
                    is_confirmed = 1,
                    confirmed_at = NOW(),
                    officer_approved = 0,
                    officer_approved_at = NULL
                WHERE id = :id");
            $updateAssignmentStmt->execute([
                ':finance_officer_emp_id' => $financeOfficerEmpId,
                ':selected_company_ids' => $assignedCompanyIdsJson,
                ':selected_employee_ids' => $selectedEmployeeIdsJson,
                ':id' => $existingVerificationId
            ]);
        } else {
            $insertAssignmentStmt = $pdo->prepare("INSERT INTO payroll_finance_verification
                (request_inv_no, payroll_month, finance_manager_emp_id, finance_officer_emp_id, selected_company_ids, selected_employee_ids, is_confirmed, confirmed_at, officer_approved, officer_approved_at)
                VALUES
                (:request_inv_no, :payroll_month, :finance_manager_emp_id, :finance_officer_emp_id, :selected_company_ids, :selected_employee_ids, 1, NOW(), 0, NULL)");
            $insertAssignmentStmt->execute([
                ':request_inv_no' => $requestInvNo,
                ':payroll_month' => $monthValue,
                ':finance_manager_emp_id' => $currentUserId,
                ':finance_officer_emp_id' => $financeOfficerEmpId,
                ':selected_company_ids' => $assignedCompanyIdsJson,
                ':selected_employee_ids' => $selectedEmployeeIdsJson
            ]);
        }

        $defaultsUpsertStmt = $pdo->prepare("INSERT INTO payroll_finance_officer_company_defaults
            (finance_manager_emp_id, finance_officer_emp_id, selected_company_ids)
            VALUES
            (:finance_manager_emp_id, :finance_officer_emp_id, :selected_company_ids)
            ON DUPLICATE KEY UPDATE
                selected_company_ids = VALUES(selected_company_ids),
                updated_at = CURRENT_TIMESTAMP");
        $defaultsUpsertStmt->execute([
            ':finance_manager_emp_id' => $currentUserId,
            ':finance_officer_emp_id' => $financeOfficerEmpId,
            ':selected_company_ids' => $currentOfficerCompanyIdsJson
        ]);

        // Determine action type: check if this officer already has any history in this request.
        $historyCheckStmt = $pdo->prepare("SELECT COUNT(*) FROM payroll_finance_verification_history
            WHERE request_inv_no = :inv_no
              AND payroll_month = :month_year
              AND finance_manager_emp_id = :manager_id
              AND finance_officer_emp_id = :officer_id");
        $historyCheckStmt->execute([
            ':inv_no' => $requestInvNo,
            ':month_year' => $monthValue,
            ':manager_id' => $currentUserId,
            ':officer_id' => $financeOfficerEmpId
        ]);
        $hasExistingHistory = (int)$historyCheckStmt->fetchColumn() > 0;
        $historyActionType = $hasExistingHistory ? 'setup_updated' : 'setup_assigned';
        $historyNote = $hasExistingHistory
            ? 'Finance verification assignment updated.'
            : 'Finance verification assignment created and sent to finance officer.';

        $historyInsertStmt = $pdo->prepare("INSERT INTO payroll_finance_verification_history
            (request_inv_no, payroll_month, finance_manager_emp_id, finance_officer_emp_id, action_type, action_note, selected_company_ids, created_by)
            VALUES
            (:request_inv_no, :payroll_month, :finance_manager_emp_id, :finance_officer_emp_id, :action_type, :action_note, :selected_company_ids, :created_by)");
        $historyInsertStmt->execute([
            ':request_inv_no' => $requestInvNo,
            ':payroll_month' => $monthValue,
            ':finance_manager_emp_id' => $currentUserId,
            ':finance_officer_emp_id' => $financeOfficerEmpId,
            ':action_type' => $historyActionType,
            ':action_note' => $historyNote,
            ':selected_company_ids' => $currentOfficerCompanyIdsJson,
            ':created_by' => $currentUserId
        ]);

        if (function_exists('create_and_show_notification')) {
            create_and_show_notification(
                $conDB,
                (string)$financeOfficerEmpId,
                'Payroll Verification Assignment',
                'You have been assigned payroll verification for selected companies for ' . $monthValue . '.',
                'payroll_checklist_report.php?month=' . urlencode($monthValue) . '&request_inv_no=' . urlencode($requestInvNo),
                'info'
            );
        }

        if (!empty($officerRow['email']) && function_exists('send_approval_email')) {
            $emailSubject = 'Payroll Verification Assignment - ' . $monthValue . ' (' . $requestInvNo . ')';
            $templateData = [
                'APPROVER_NAME' => !empty($officerRow['name']) ? htmlspecialchars((string)$officerRow['name']) : 'Finance Officer',
                'REQUEST_ID' => $requestInvNo,
                'REQUEST_TYPE' => 'Payroll Verification Assignment',
                'EMAIL_MESSAGE' => 'You have been assigned to verify payroll for selected companies. Open Payroll Checklist Report to verify only your assigned companies employees.',
                'PAYROLL_MONTH' => $monthValue,
                'REQUEST_URL' => (function_exists('get_base_url') ? get_base_url() : 'https://hr.almutlaksystem.com') . '/payroll_checklist_report.php?month=' . urlencode($monthValue) . '&request_inv_no=' . urlencode($requestInvNo)
            ];
            send_approval_email(
                $conDB,
                (string)$officerRow['email'],
                (string)($officerRow['name'] ?? 'Finance Officer'),
                $emailSubject,
                'payroll_request',
                $templateData
            );
        }

        echo json_encode([
            'status' => 'success',
            'message' => 'Finance verification setup confirmed successfully. Head Office Finance Manager approval will be enabled after assigned finance officer completes checklist verification.'
        ]);
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
}

function getDirectSupervisorOptionsForPayroll(PDO $pdo, string $currentUserId): void
{
    $requestInvNo = trim((string)($_POST['request_inv_no'] ?? ''));
    $monthYear = trim((string)($_POST['month'] ?? ''));

    try {
        ensurePayrollSupervisorReportDispatchTable($pdo);

        $currentUserRole = strtolower(trim((string)($GLOBALS['user_type'] ?? '')));
        if (!in_array($currentUserRole, ['hr_payroll', 'hr_senior_bp'], true) && $currentUserRole !== 'administrator') {
            throw new Exception('Only HR Payroll and HR Senior BP can access this action.');
        }

        if ($requestInvNo === '' || $monthYear === '') {
            throw new Exception('Missing request number or month.');
        }

        $requestStmt = $pdo->prepare("SELECT payroll_month FROM payroll_approval_requests WHERE request_inv_no = :inv_no LIMIT 1");
        $requestStmt->execute([':inv_no' => $requestInvNo]);
        $requestRow = $requestStmt->fetch(PDO::FETCH_ASSOC);
        if (!$requestRow) {
            throw new Exception('Payroll request not found.');
        }

        $monthValue = (string)($requestRow['payroll_month'] ?? $monthYear);

        $supervisorsStmt = $pdo->prepare("SELECT
                e.supervisor_id AS supervisor_emp_id,
                sup.name AS supervisor_name,
                al.email AS supervisor_email,
                COUNT(DISTINCT p.emp_id) AS employee_count,
                COALESCE(SUM(p.net_salary), 0) AS total_net_salary,
                MAX(dispatch.sent_at) AS sent_at,
                MAX(dispatch.merged_into_supervisor_emp_id) AS merged_into_supervisor_emp_id
            FROM payrolls p
            INNER JOIN employees e ON e.emp_id = p.emp_id
            INNER JOIN employees sup ON sup.emp_id = e.supervisor_id
            INNER JOIN admin_login al ON al.emp_id = e.supervisor_id
            LEFT JOIN payroll_supervisor_report_dispatch dispatch
                ON dispatch.request_inv_no = :request_inv_no
               AND dispatch.payroll_month = :dispatch_month
               AND dispatch.supervisor_emp_id = e.supervisor_id
            WHERE p.month_year = :month_year
              AND e.supervisor_id IS NOT NULL
              AND TRIM(e.supervisor_id) <> ''
              AND al.email IS NOT NULL
              AND TRIM(al.email) <> ''
              AND sup.status = 1
            GROUP BY e.supervisor_id, sup.name, al.email
            ORDER BY sup.name ASC");
        $supervisorsStmt->execute([
            ':request_inv_no' => $requestInvNo,
            ':dispatch_month' => $monthValue,
            ':month_year' => $monthValue
        ]);
        $supervisors = $supervisorsStmt->fetchAll(PDO::FETCH_ASSOC);

        // Any admin_login account on the company domain (@almutlak.com) should always
        // be selectable as a Direct Supervisor / "also include" target - not only
        // employees who already happen to be someone's supervisor_id this month (e.g.
        // HR Payroll/HR Senior BP staff who directly supervise their own team, or any
        // manager with zero direct reports this particular month). Add whoever the
        // main query above missed, using their own direct-report count (may be 0).
        $knownSupervisorIds = array_map(static function ($row) {
            return (string)($row['supervisor_emp_id'] ?? '');
        }, $supervisors);

        $companyEmailCandidatesStmt = $pdo->prepare("SELECT al.emp_id AS supervisor_emp_id, e.name AS supervisor_name, al.email AS supervisor_email
            FROM admin_login al
            INNER JOIN employees e ON e.emp_id = al.emp_id
            WHERE al.email IS NOT NULL
              AND TRIM(al.email) <> ''
              AND LOWER(TRIM(al.email)) LIKE '%@almutlak.com'
              AND e.status = 1");
        $companyEmailCandidatesStmt->execute();
        $companyEmailCandidates = $companyEmailCandidatesStmt->fetchAll(PDO::FETCH_ASSOC);

        $teamStmt = $pdo->prepare("SELECT COUNT(DISTINCT p.emp_id) AS employee_count, COALESCE(SUM(p.net_salary), 0) AS total_net_salary
            FROM payrolls p
            INNER JOIN employees e ON e.emp_id = p.emp_id
            WHERE p.month_year = :month_year
              AND e.supervisor_id = :supervisor_emp_id");
        $candidateDispatchStmt = $pdo->prepare("SELECT sent_at, merged_into_supervisor_emp_id
            FROM payroll_supervisor_report_dispatch
            WHERE request_inv_no = :request_inv_no
              AND payroll_month = :payroll_month
              AND supervisor_emp_id = :supervisor_emp_id
            LIMIT 1");

        foreach ($companyEmailCandidates as $candidate) {
            $candidateEmpId = (string)($candidate['supervisor_emp_id'] ?? '');
            if ($candidateEmpId === '' || in_array($candidateEmpId, $knownSupervisorIds, true)) {
                continue;
            }

            $teamStmt->execute([':month_year' => $monthValue, ':supervisor_emp_id' => $candidateEmpId]);
            $teamRow = $teamStmt->fetch(PDO::FETCH_ASSOC) ?: [];

            $candidateDispatchStmt->execute([
                ':request_inv_no' => $requestInvNo,
                ':payroll_month' => $monthValue,
                ':supervisor_emp_id' => $candidateEmpId
            ]);
            $candidateDispatchRow = $candidateDispatchStmt->fetch(PDO::FETCH_ASSOC) ?: [];

            $supervisors[] = [
                'supervisor_emp_id' => $candidateEmpId,
                'supervisor_name' => (string)($candidate['supervisor_name'] ?? 'N/A'),
                'supervisor_email' => (string)($candidate['supervisor_email'] ?? ''),
                'employee_count' => (int)($teamRow['employee_count'] ?? 0),
                'total_net_salary' => (float)($teamRow['total_net_salary'] ?? 0),
                'sent_at' => (string)($candidateDispatchRow['sent_at'] ?? ''),
                'merged_into_supervisor_emp_id' => (string)($candidateDispatchRow['merged_into_supervisor_emp_id'] ?? '')
            ];
            $knownSupervisorIds[] = $candidateEmpId;
        }

        usort($supervisors, static function ($a, $b) {
            return strcasecmp((string)($a['supervisor_name'] ?? ''), (string)($b['supervisor_name'] ?? ''));
        });

        echo json_encode([
            'status' => 'success',
            'supervisors' => array_map(static function ($row) {
                $sentAt = (string)($row['sent_at'] ?? '');
                // A supervisor whose team was merged into someone else's report never
                // received their own email, so they can't be (re)selected as primary -
                // resend that other supervisor's report instead. A supervisor who WAS
                // themselves a primary recipient can be resent to (e.g. figures changed).
                $mergedInto = trim((string)($row['merged_into_supervisor_emp_id'] ?? ''));
                return [
                    'supervisor_emp_id' => (string)($row['supervisor_emp_id'] ?? ''),
                    'supervisor_name' => (string)($row['supervisor_name'] ?? 'N/A'),
                    'supervisor_email' => (string)($row['supervisor_email'] ?? ''),
                    'employee_count' => (int)($row['employee_count'] ?? 0),
                    'total_net_salary' => (float)($row['total_net_salary'] ?? 0),
                    'is_sent' => $sentAt !== '',
                    'sent_at' => $sentAt,
                    'merged_into' => $mergedInto
                ];
            }, $supervisors)
        ]);
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
}

function getSupervisorEmployeesPreviewForPayroll(PDO $pdo, string $currentUserId): void
{
    $requestInvNo = trim((string)($_POST['request_inv_no'] ?? ''));
    $monthYear = trim((string)($_POST['month'] ?? ''));
    $supervisorEmpId = trim((string)($_POST['supervisor_emp_id'] ?? ''));

    try {
        $currentUserRole = strtolower(trim((string)($GLOBALS['user_type'] ?? '')));
        if (!in_array($currentUserRole, ['hr_payroll', 'hr_senior_bp'], true) && $currentUserRole !== 'administrator') {
            throw new Exception('Only HR Payroll and HR Senior BP can access this action.');
        }

        if ($requestInvNo === '' || $monthYear === '' || $supervisorEmpId === '') {
            throw new Exception('Missing request number, month or supervisor.');
        }

        $requestStmt = $pdo->prepare("SELECT payroll_month FROM payroll_approval_requests WHERE request_inv_no = :inv_no LIMIT 1");
        $requestStmt->execute([':inv_no' => $requestInvNo]);
        $requestRow = $requestStmt->fetch(PDO::FETCH_ASSOC);
        if (!$requestRow) {
            throw new Exception('Payroll request not found.');
        }

        $monthValue = (string)($requestRow['payroll_month'] ?? $monthYear);

        $supervisorStmt = $pdo->prepare("SELECT al.emp_id, al.email, e.name
            FROM admin_login al
            INNER JOIN employees e ON e.emp_id = al.emp_id
            WHERE al.emp_id = :emp_id
              AND al.email IS NOT NULL
              AND TRIM(al.email) <> ''
            LIMIT 1");
        $supervisorStmt->execute([':emp_id' => $supervisorEmpId]);
        $supervisor = $supervisorStmt->fetch(PDO::FETCH_ASSOC);
        if (!$supervisor) {
            throw new Exception('Selected supervisor has no registered email.');
        }

        $employeesStmt = $pdo->prepare("SELECT
                p.emp_id,
                e.name AS employee_name,
                COALESCE(d.dep_nme, '') AS department_name,
                COALESCE(c.comp_name, '') AS company_name
            FROM payrolls p
            INNER JOIN employees e ON e.emp_id = p.emp_id
            LEFT JOIN department d ON d.id = e.dept
            LEFT JOIN companies c ON c.comp_id = e.comp_no
            WHERE p.month_year = :month_year
              AND e.supervisor_id = :supervisor_emp_id
            ORDER BY e.name ASC");
        $employeesStmt->execute([
            ':month_year' => $monthValue,
            ':supervisor_emp_id' => $supervisorEmpId
        ]);
        $employees = $employeesStmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode([
            'status' => 'success',
            'supervisor_name' => (string)($supervisor['name'] ?? ''),
            'employee_count' => count($employees),
            'employees' => array_map(static function ($row) {
                return [
                    'emp_id' => (string)($row['emp_id'] ?? ''),
                    'name' => (string)($row['employee_name'] ?? ''),
                    'department' => (string)($row['department_name'] ?? ''),
                    'company' => (string)($row['company_name'] ?? '')
                ];
            }, $employees)
        ]);
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
}

function getManagerPayrollUploadStorageDir(): string
{
    return __DIR__ . '/../../assets/payroll_manager_uploads';
}

function ensureManagerPayrollImportCheckpointRegistryTable(PDO $pdo): void
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

function generateManagerPayrollImportCheckpointCode(string $defaultMonth): string
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

function issueManagerPayrollImportCheckpoint(PDO $pdo, string $defaultMonth, string $createdBy): string
{
    ensureManagerPayrollImportCheckpointRegistryTable($pdo);
    $checkpointCode = generateManagerPayrollImportCheckpointCode($defaultMonth);

    $insertStmt = $pdo->prepare("INSERT INTO payroll_import_checkpoint_registry (checkpoint_code, default_month, created_by) VALUES (:checkpoint_code, :default_month, :created_by)");
    $insertStmt->execute([
        ':checkpoint_code' => $checkpointCode,
        ':default_month' => $defaultMonth,
        ':created_by' => $createdBy
    ]);

    return $checkpointCode;
}

function normalizeManagerPayrollUploadKey(string $requestInvNo, string $monthYear): string
{
    $requestPart = preg_replace('/[^A-Za-z0-9_-]+/', '_', trim($requestInvNo));
    $monthPart = preg_replace('/[^0-9-]+/', '', trim($monthYear));
    return $requestPart . '__' . $monthPart;
}

function getManagerPayrollUploadMetaPath(string $requestInvNo, string $monthYear): string
{
    return getManagerPayrollUploadStorageDir() . DIRECTORY_SEPARATOR
        . normalizeManagerPayrollUploadKey($requestInvNo, $monthYear)
        . '.json';
}

function loadManagerPayrollUploadMeta(string $requestInvNo, string $monthYear): array
{
    $metaPath = getManagerPayrollUploadMetaPath($requestInvNo, $monthYear);
    if (!file_exists($metaPath)) {
        return [];
    }

    $raw = @file_get_contents($metaPath);
    if ($raw === false || trim($raw) === '') {
        return [];
    }

    $decoded = json_decode($raw, true);
    return is_array($decoded) ? $decoded : [];
}

function saveManagerPayrollUploadMeta(string $requestInvNo, string $monthYear, array $meta): void
{
    $storageDir = getManagerPayrollUploadStorageDir();
    if (!is_dir($storageDir) && !@mkdir($storageDir, 0775, true) && !is_dir($storageDir)) {
        throw new Exception('Unable to create manager upload storage directory.');
    }

    $metaPath = getManagerPayrollUploadMetaPath($requestInvNo, $monthYear);
    $encoded = json_encode($meta, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    if ($encoded === false || @file_put_contents($metaPath, $encoded, LOCK_EX) === false) {
        throw new Exception('Unable to save manager upload metadata.');
    }
}

/**
 * Acquire an exclusive lock spanning the whole load-modify-save cycle for a
 * request/month's upload metadata. Without this, two near-simultaneous
 * requests (double-click upload, or an upload racing a review/delete) can
 * each load the same starting JSON, and the last one to save silently wipes
 * out the other's change - including uploads whose .xlsx already landed on
 * disk, leaving them orphaned and invisible to HR Payroll / HR Senior BP.
 */
function acquireManagerPayrollUploadLock(string $requestInvNo, string $monthYear)
{
    $storageDir = getManagerPayrollUploadStorageDir();
    if (!is_dir($storageDir) && !@mkdir($storageDir, 0775, true) && !is_dir($storageDir)) {
        throw new Exception('Unable to prepare upload storage directory.');
    }

    $lockPath = $storageDir . DIRECTORY_SEPARATOR . normalizeManagerPayrollUploadKey($requestInvNo, $monthYear) . '.lock';
    $handle = @fopen($lockPath, 'c');
    if ($handle === false || !flock($handle, LOCK_EX)) {
        if (is_resource($handle)) {
            fclose($handle);
        }
        throw new Exception('Unable to lock manager upload metadata. Please try again.');
    }

    return $handle;
}

function releaseManagerPayrollUploadLock($lockHandle): void
{
    if (is_resource($lockHandle)) {
        flock($lockHandle, LOCK_UN);
        fclose($lockHandle);
    }
}

function buildManagerPayrollUploadFileId(string $storedName, string $uploadedAt): string
{
    return substr(sha1($storedName . '|' . $uploadedAt), 0, 20);
}

function extractManagerPayrollCompanyNameFromFileName(string $fileName): string
{
    $baseName = pathinfo(trim($fileName), PATHINFO_FILENAME);
    if ($baseName === '') {
        return '';
    }

    // Expected pattern is typically: payroll_YYYY-MM_COMPANY_NAME
    if (preg_match('/^payroll_\d{4}-\d{2}_(.+)$/i', $baseName, $matches)) {
        $baseName = (string)($matches[1] ?? '');
    } elseif (preg_match('/^payroll_(.+)$/i', $baseName, $matches)) {
        $baseName = (string)($matches[1] ?? '');
    }

    $baseName = trim(str_replace('_', ' ', $baseName));
    $baseName = preg_replace('/\s+/', ' ', (string)$baseName);

    return trim((string)$baseName);
}

function normalizeManagerPayrollUploadMeta(array $meta, string $requestInvNo, string $monthYear): array
{
    $normalized = [
        'request_inv_no' => $requestInvNo,
        'month' => $monthYear,
        'uploads' => []
    ];

    $uploads = isset($meta['uploads']) && is_array($meta['uploads']) ? $meta['uploads'] : [];

    // Backward compatibility: migrate legacy single-file metadata to uploads[] format.
    if (empty($uploads) && !empty($meta['stored_name'])) {
        $legacyUploadedAt = trim((string)($meta['uploaded_at'] ?? ''));
        $legacyStoredName = trim((string)$meta['stored_name']);
        $uploads[] = [
            'file_id' => buildManagerPayrollUploadFileId($legacyStoredName, $legacyUploadedAt !== '' ? $legacyUploadedAt : date('Y-m-d H:i:s')),
            'checkpoint_code' => (string)($meta['checkpoint_code'] ?? ''),
            'original_name' => (string)($meta['original_name'] ?? $legacyStoredName),
            'stored_name' => $legacyStoredName,
            'uploaded_by' => (string)($meta['uploaded_by'] ?? ''),
            'uploaded_by_name' => (string)($meta['uploaded_by_name'] ?? ''),
            'uploaded_at' => $legacyUploadedAt,
            'reviewed_by' => (string)($meta['reviewed_by'] ?? ''),
            'reviewed_by_name' => (string)($meta['reviewed_by_name'] ?? ''),
            'reviewed_at' => (string)($meta['reviewed_at'] ?? ''),
            'review_note' => (string)($meta['review_note'] ?? '')
        ];
    }

    foreach ($uploads as $upload) {
        if (!is_array($upload)) {
            continue;
        }

        $storedName = trim((string)($upload['stored_name'] ?? ''));
        if ($storedName === '') {
            continue;
        }

        $uploadedAt = trim((string)($upload['uploaded_at'] ?? ''));
        $fileId = trim((string)($upload['file_id'] ?? ''));
        if ($fileId === '') {
            $fileId = buildManagerPayrollUploadFileId($storedName, $uploadedAt !== '' ? $uploadedAt : date('Y-m-d H:i:s'));
        }

        $normalized['uploads'][] = [
            'file_id' => $fileId,
            'checkpoint_code' => strtoupper(trim((string)($upload['checkpoint_code'] ?? ''))),
            'original_name' => trim((string)($upload['original_name'] ?? $storedName)),
            'stored_name' => $storedName,
            'uploaded_by' => trim((string)($upload['uploaded_by'] ?? '')),
            'uploaded_by_name' => trim((string)($upload['uploaded_by_name'] ?? '')),
            'uploaded_at' => $uploadedAt,
            'reviewed_by' => trim((string)($upload['reviewed_by'] ?? '')),
            'reviewed_by_name' => trim((string)($upload['reviewed_by_name'] ?? '')),
            'reviewed_at' => trim((string)($upload['reviewed_at'] ?? '')),
            'review_note' => trim((string)($upload['review_note'] ?? ''))
        ];
    }

    usort($normalized['uploads'], static function (array $a, array $b): int {
        $at = strtotime((string)($a['uploaded_at'] ?? '')) ?: 0;
        $bt = strtotime((string)($b['uploaded_at'] ?? '')) ?: 0;
        return $bt <=> $at;
    });

    return $normalized;
}

function findManagerPayrollUploadByFileId(array $meta, string $fileId): array
{
    $uploads = isset($meta['uploads']) && is_array($meta['uploads']) ? $meta['uploads'] : [];
    foreach ($uploads as $index => $upload) {
        if (!is_array($upload)) {
            continue;
        }
        if (trim((string)($upload['file_id'] ?? '')) === $fileId) {
            return ['index' => (int)$index, 'upload' => $upload];
        }
    }
    return ['index' => -1, 'upload' => []];
}

function parseManagerPayrollSheetRows($sheet, string $monthYear, string $checkpointCode, bool $isBenefitsSheet): array
{
    if (!$sheet) {
        return [];
    }

    $rows = $sheet->toArray('', true, true, true);
    if (empty($rows)) {
        return [];
    }

    $headerRow = array_shift($rows);
    $headerMap = [];
    foreach ($headerRow as $column => $headerValue) {
        $normalized = strtolower(trim((string)$headerValue));
        if ($normalized !== '') {
            $headerMap[$normalized] = $column;
        }
    }

    $getCell = static function (array $row, array $map, array $keys): string {
        foreach ($keys as $key) {
            if (isset($map[$key])) {
                $column = $map[$key];
                return trim((string)($row[$column] ?? ''));
            }
        }
        return '';
    };

    $normalizedRows = [];
    foreach ($rows as $row) {
        $empId = $getCell($row, $headerMap, ['emp_id', 'employee_id']);
        if ($empId === '') {
            continue;
        }

        if ($isBenefitsSheet) {
            $benefitHours = $getCell($row, $headerMap, ['benefit_hours', 'overtime_hours']);
            $benefitMinutes = $getCell($row, $headerMap, ['benefit_minutes', 'overtime_minutes']);
            $benefitReason = $getCell($row, $headerMap, ['benefit_reason', 'overtime_reason']);

            if ($benefitHours === '' && $benefitMinutes === '' && $benefitReason === '') {
                continue;
            }

            $normalizedRows[] = [
                'checkpoint_code' => $checkpointCode,
                'emp_id' => $empId,
                'month' => $monthYear,
                'benefit_type' => 'by_hours',
                'benefit_value' => '',
                'benefit_hours' => $benefitHours,
                'benefit_minutes' => $benefitMinutes,
                'benefit_reason' => $benefitReason,
                'deduction_type' => 'fixed',
                'deduction_value' => '',
                'deduction_hours' => '',
                'deduction_minutes' => '',
                'deduction_days' => '',
                'deduction_reason' => ''
            ];
        } else {
            $deductionDays = $getCell($row, $headerMap, ['deduction_day', 'deduction_days']);
            $deductionHours = $getCell($row, $headerMap, ['deduction_hours']);
            $deductionMinutes = $getCell($row, $headerMap, ['deduction_minutes']);
            $deductionReason = $getCell($row, $headerMap, ['deduction_reason']);

            if ($deductionDays === '' && $deductionHours === '' && $deductionMinutes === '' && $deductionReason === '') {
                continue;
            }

            $normalizedRows[] = [
                'checkpoint_code' => $checkpointCode,
                'emp_id' => $empId,
                'month' => $monthYear,
                'benefit_type' => 'fixed',
                'benefit_value' => '',
                'benefit_hours' => '',
                'benefit_minutes' => '',
                'benefit_reason' => '',
                'deduction_type' => 'hourly_deduction',
                'deduction_value' => '',
                'deduction_hours' => $deductionHours,
                'deduction_minutes' => $deductionMinutes,
                'deduction_days' => $deductionDays,
                'deduction_reason' => $deductionReason
            ];
        }
    }

    return $normalizedRows;
}

function parseManagerUploadedPayrollFileToRows(string $filePath, string $monthYear): array
{
    if (!class_exists('PhpOffice\\PhpSpreadsheet\\Spreadsheet')) {
        $autoloadPaths = [
            __DIR__ . '/../../vendor/autoload.php',
            __DIR__ . '/../../../vendor/autoload.php',
            __DIR__ . '/../../includes/vendor/autoload.php'
        ];
        foreach ($autoloadPaths as $path) {
            if (file_exists($path)) {
                require_once $path;
                break;
            }
        }
    }

    if (!class_exists('PhpOffice\\PhpSpreadsheet\\Spreadsheet')) {
        throw new Exception('Spreadsheet library is not available on server.');
    }

    $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($filePath);
    $benefitsSheet = $spreadsheet->getSheetByName('Benefits Import');
    $deductionsSheet = $spreadsheet->getSheetByName('Deductions Import');
    $metadataSheet = $spreadsheet->getSheetByName('__PAYROLL_IMPORT_META');

    $checkpointCode = '';
    if ($metadataSheet) {
        $checkpointCode = strtoupper(trim((string)$metadataSheet->getCell('A1')->getValue()));
    }

    if ($checkpointCode === '' || !preg_match('/^PAYIMP-\d{6}-[A-Z0-9]+$/', $checkpointCode)) {
        throw new Exception('The uploaded file is not valid.');
    }

    $benefitRows = parseManagerPayrollSheetRows($benefitsSheet, $monthYear, $checkpointCode, true);
    $deductionRows = parseManagerPayrollSheetRows($deductionsSheet, $monthYear, $checkpointCode, false);

    return [
        'checkpoint_code' => $checkpointCode,
        'rows' => array_values(array_merge($benefitRows, $deductionRows))
    ];
}

function uploadManagerPayrollExcel(PDO $pdo, $conDB, string $currentUserId): void
{
    $requestInvNo = trim((string)($_POST['request_inv_no'] ?? ''));
    $monthYear = trim((string)($_POST['month'] ?? ''));

    try {
        $currentUserType = strtolower(trim((string)($GLOBALS['user_type'] ?? '')));
        $currentEmpType = strtolower(trim((string)($GLOBALS['emp_type'] ?? '')));

        if ($requestInvNo === '' || $monthYear === '' || !preg_match('/^\d{4}-\d{2}$/', $monthYear)) {
            throw new Exception('Missing request number or valid month.');
        }

        // Allow: a manager-flagged account, a Direct Supervisor who actually received
        // this specific "Send Payroll Report by Direct Supervisor" email (covers
        // supervisors whose admin account isn't flagged emp_type = 'Manager'), or
        // HR Payroll / HR Senior BP uploading on behalf of any manager.
        $isManagerAccount = ($currentEmpType === 'manager' && $currentUserType !== 'employee');
        $isReportRecipient = $currentUserType !== 'employee'
            && payrollSupervisorHasReportAccess($pdo, $requestInvNo, $monthYear, $currentUserId);
        $isHrChecklistApproverUploader = in_array($currentUserType, ['hr_payroll', 'hr_senior_bp'], true);
        if (!$isManagerAccount && !$isReportRecipient && !$isHrChecklistApproverUploader) {
            throw new Exception('Only manager users can upload this payroll Excel file.');
        }

        if (!isset($_FILES['payroll_excel']) || !is_array($_FILES['payroll_excel'])) {
            throw new Exception('Please choose an Excel file to upload.');
        }

        $upload = $_FILES['payroll_excel'];
        if ((int)($upload['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            throw new Exception('Unable to upload file. Please try again.');
        }

        $originalName = trim((string)($upload['name'] ?? ''));
        $tmpPath = (string)($upload['tmp_name'] ?? '');
        $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        if (!in_array($extension, ['xlsx', 'xls'], true)) {
            throw new Exception('Only .xlsx or .xls files are allowed.');
        }

        $requestStmt = $pdo->prepare('SELECT payroll_month FROM payroll_approval_requests WHERE request_inv_no = :inv_no LIMIT 1');
        $requestStmt->execute([':inv_no' => $requestInvNo]);
        $requestRow = $requestStmt->fetch(PDO::FETCH_ASSOC);
        if (!$requestRow) {
            throw new Exception('Payroll request not found.');
        }

        $requestMonth = trim((string)($requestRow['payroll_month'] ?? ''));
        if ($requestMonth !== '' && $requestMonth !== $monthYear) {
            throw new Exception('Upload month must match the payroll request month.');
        }

        // Validate hidden checkpoint code before accepting the upload.
        $parsedUpload = parseManagerUploadedPayrollFileToRows($tmpPath, $monthYear);
        $checkpointCode = strtoupper(trim((string)($parsedUpload['checkpoint_code'] ?? '')));
        if ($checkpointCode === '') {
            throw new Exception('The uploaded file is not valid.');
        }

        ensureManagerPayrollImportCheckpointRegistryTable($pdo);
        $checkpointStmt = $pdo->prepare("SELECT used_at FROM payroll_import_checkpoint_registry WHERE checkpoint_code = :checkpoint_code LIMIT 1");
        $checkpointStmt->execute([':checkpoint_code' => $checkpointCode]);
        $checkpointRecord = $checkpointStmt->fetch(PDO::FETCH_ASSOC) ?: null;
        if (!$checkpointRecord) {
            throw new Exception('The uploaded file is not valid.');
        }
        if (!empty($checkpointRecord['used_at'])) {
            throw new Exception("This file is already uploaded, so you can't upload it again.");
        }

        $storageDir = getManagerPayrollUploadStorageDir();
        if (!is_dir($storageDir) && !@mkdir($storageDir, 0775, true) && !is_dir($storageDir)) {
            throw new Exception('Unable to prepare upload storage directory.');
        }

        $lockHandle = acquireManagerPayrollUploadLock($requestInvNo, $monthYear);
        try {
            $existingMetaForCheckpoint = normalizeManagerPayrollUploadMeta(loadManagerPayrollUploadMeta($requestInvNo, $monthYear), $requestInvNo, $monthYear);
            $existingUploadsForCheckpoint = isset($existingMetaForCheckpoint['uploads']) && is_array($existingMetaForCheckpoint['uploads'])
                ? $existingMetaForCheckpoint['uploads']
                : [];
            foreach ($existingUploadsForCheckpoint as $existingUpload) {
                if (!is_array($existingUpload)) {
                    continue;
                }

                $existingCheckpoint = strtoupper(trim((string)($existingUpload['checkpoint_code'] ?? '')));
                if ($existingCheckpoint !== '' && $existingCheckpoint === $checkpointCode) {
                    throw new Exception("This file is already uploaded, so you can't upload it again.");
                }
            }

            $safeKey = normalizeManagerPayrollUploadKey($requestInvNo, $monthYear);
            $storedName = $safeKey . '__' . date('Ymd_His') . '__' . bin2hex(random_bytes(4)) . '.' . $extension;
            $storedPath = $storageDir . DIRECTORY_SEPARATOR . $storedName;

            if (!@move_uploaded_file($tmpPath, $storedPath)) {
                throw new Exception('Failed to save uploaded file on server.');
            }

            $uploaderNameStmt = $pdo->prepare('SELECT name FROM employees WHERE emp_id = :emp_id LIMIT 1');
            $uploaderNameStmt->execute([':emp_id' => $currentUserId]);
            $uploaderName = (string)($uploaderNameStmt->fetchColumn() ?: $currentUserId);
            if ($uploaderName !== '' && function_exists('parseName')) {
                $parsedUploaderName = trim((string)parseName($uploaderName, 'FIRST_LAST'));
                if ($parsedUploaderName !== '') {
                    $uploaderName = $parsedUploaderName;
                }
            }

            $existingMeta = normalizeManagerPayrollUploadMeta(loadManagerPayrollUploadMeta($requestInvNo, $monthYear), $requestInvNo, $monthYear);
            $fileId = buildManagerPayrollUploadFileId($storedName, date('Y-m-d H:i:s'));
            $newUpload = [
                'file_id' => $fileId,
                'request_inv_no' => $requestInvNo,
                'month' => $monthYear,
                'checkpoint_code' => $checkpointCode,
                'original_name' => $originalName,
                'stored_name' => $storedName,
                'uploaded_by' => $currentUserId,
                'uploaded_by_name' => $uploaderName,
                'uploaded_at' => date('Y-m-d H:i:s'),
                'reviewed_by' => '',
                'reviewed_by_name' => '',
                'reviewed_at' => '',
                'review_note' => ''
            ];

            $existingMeta['uploads'][] = $newUpload;
            $meta = normalizeManagerPayrollUploadMeta($existingMeta, $requestInvNo, $monthYear);
            saveManagerPayrollUploadMeta($requestInvNo, $monthYear, $meta);
        } finally {
            releaseManagerPayrollUploadLock($lockHandle);
        }

        // Summary figures for the HR notification email (matches the level of detail
        // the "Send Payroll Report by Direct Supervisor" email already shows).
        $uploadedEmpIdsForSummary = array_values(array_unique(array_filter(array_map(static function ($row) {
            return trim((string)($row['emp_id'] ?? ''));
        }, isset($parsedUpload['rows']) && is_array($parsedUpload['rows']) ? $parsedUpload['rows'] : []))));
        $uploadedEmployeeCount = count($uploadedEmpIdsForSummary);
        $uploadedTotalNetSalary = 0.0;
        if (!empty($uploadedEmpIdsForSummary)) {
            $summaryPlaceholders = implode(',', array_fill(0, count($uploadedEmpIdsForSummary), '?'));
            $summaryStmt = $pdo->prepare("SELECT COALESCE(SUM(net_salary), 0) FROM payrolls WHERE month_year = ? AND emp_id IN ($summaryPlaceholders)");
            $summaryStmt->execute(array_merge([$monthYear], $uploadedEmpIdsForSummary));
            $uploadedTotalNetSalary = (float)$summaryStmt->fetchColumn();
        }

        $hrNotifiedCount = 0;
        $hrEmailedCount = 0;
        $hrRecipientCount = 0;

        // Notify both HR Payroll and HR Senior BP user groups. Every step below is
        // logged with a "PAYROLL_UPLOAD_NOTIFY" prefix (greppable in the PHP error
        // log) and wrapped defensively so one bad recipient (missing email, DB hiccup,
        // etc.) can never silently swallow the rest or abort the whole loop.
        $notifyStmt = $pdo->prepare("SELECT DISTINCT emp_id FROM admin_login WHERE LOWER(TRIM(COALESCE(user_type,''))) IN ('hr_payroll','hr_senior_bp')");
        $notifyStmt->execute();
        $rows = $notifyStmt->fetchAll(PDO::FETCH_ASSOC);
        $hrRecipientCount = count($rows);
        error_log('PAYROLL_UPLOAD_NOTIFY: request=' . $requestInvNo . ' month=' . $monthYear . ' hr_recipients_found=' . $hrRecipientCount);

        if ($hrRecipientCount === 0) {
            error_log('PAYROLL_UPLOAD_NOTIFY: no admin_login accounts with user_type hr_payroll/hr_senior_bp were found - nobody to notify.');
        }

        $subject = 'Manager Payroll Excel Uploaded: ' . $monthYear . ' (' . $requestInvNo . ')';
        $requestUrl = 'payroll_checklist_report.php?month=' . urlencode($monthYear) . '&request_inv_no=' . urlencode($requestInvNo);

        foreach ($rows as $r) {
            $hrEmpId = trim((string)($r['emp_id'] ?? ''));
            if ($hrEmpId === '') {
                continue;
            }

            if (function_exists('create_and_show_notification')) {
                try {
                    $notifyResult = create_and_show_notification(
                        $conDB,
                        $hrEmpId,
                        'Manager Payroll Excel Uploaded',
                        'Manager uploaded payroll Excel for ' . $monthYear . ' (' . $requestInvNo . '). Please review and import.',
                        $requestUrl,
                        'info'
                    );
                    $dbSaved = is_array($notifyResult) ? ($notifyResult['db_saved'] ?? false) : false;
                    if ($dbSaved) {
                        $hrNotifiedCount++;
                    } else {
                        error_log('PAYROLL_UPLOAD_NOTIFY: in-app notification NOT saved for emp_id=' . $hrEmpId . ' (create_browser_notification returned false - check that admin_login.emp_id is a valid positive numeric value and user_notifications table exists).');
                    }
                } catch (Throwable $notifyError) {
                    error_log('PAYROLL_UPLOAD_NOTIFY: exception creating notification for emp_id=' . $hrEmpId . ': ' . $notifyError->getMessage());
                }
            } else {
                error_log('PAYROLL_UPLOAD_NOTIFY: create_and_show_notification() function not available.');
            }

            // Attempt to send an email if address exists in admin_login or employees
            try {
                $emailStmt = $pdo->prepare("SELECT COALESCE(NULLIF(TRIM(al.email),''), NULLIF(TRIM(e.email),'')) AS email, COALESCE(NULLIF(TRIM(e.name),''), NULLIF(TRIM(al.fullname),''), '') AS name FROM admin_login al LEFT JOIN employees e ON e.emp_id = al.emp_id WHERE al.emp_id = :emp_id LIMIT 1");
                $emailStmt->execute([':emp_id' => $hrEmpId]);
                $emailRow = $emailStmt->fetch(PDO::FETCH_ASSOC) ?: [];
                $toEmail = trim((string)($emailRow['email'] ?? ''));
                $toName = trim((string)($emailRow['name'] ?? '')) ?: $hrEmpId;

                if ($toEmail === '') {
                    error_log('PAYROLL_UPLOAD_NOTIFY: no email address on file for emp_id=' . $hrEmpId . ' - skipping email.');
                } elseif (!function_exists('send_approval_email')) {
                    error_log('PAYROLL_UPLOAD_NOTIFY: send_approval_email() function not available.');
                } else {
                    $templateData = [
                        'APPROVER_NAME' => $toName,
                        'REQUEST_ID' => $requestInvNo,
                        'REQUEST_TYPE' => 'Manager Payroll Excel Upload',
                        'PAYROLL_MONTH' => $monthYear,
                        'EMPLOYEE_COUNT' => (string)$uploadedEmployeeCount,
                        'TOTAL_NET_SALARY' => number_format($uploadedTotalNetSalary, 2),
                        'PAYROLL_STATUS' => 'Manager Reviewed - Pending HR Import',
                        'EMAIL_MESSAGE' => 'Manager ' . $uploaderName . ' uploaded payroll Excel for ' . $monthYear
                            . ' (' . $uploadedEmployeeCount . ' employees). Please review and import.',
                        'REQUEST_URL' => (function_exists('get_base_url') ? get_base_url() : '') . '/' . $requestUrl,
                        'ACTION_BUTTON_TEXT' => 'Review Uploaded Payroll Excel'
                    ];
                    // send_approval_email expects mysqli connection as first param
                    $emailSentOk = send_approval_email($conDB, $toEmail, $toName, $subject, 'payroll_request', $templateData);
                    if ($emailSentOk) {
                        $hrEmailedCount++;
                    } else {
                        error_log('PAYROLL_UPLOAD_NOTIFY: send_approval_email() returned false for emp_id=' . $hrEmpId . ' <' . $toEmail . '> - check SMTP settings in app_settings and the SEND_EMAIL_DEBUG log lines above this one.');
                    }
                }
            } catch (Throwable $emailError) {
                error_log('PAYROLL_UPLOAD_NOTIFY: exception sending email for emp_id=' . $hrEmpId . ': ' . $emailError->getMessage());
            }
        }

        error_log('PAYROLL_UPLOAD_NOTIFY: done - hr_recipients_found=' . $hrRecipientCount . ' notified=' . $hrNotifiedCount . ' emailed=' . $hrEmailedCount);

        echo json_encode([
            'status' => 'success',
            'message' => 'Payroll Excel uploaded successfully and sent to HR Payroll for review.',
            'meta' => $meta,
            'hr_recipients_found' => $hrRecipientCount,
            'hr_notified_count' => $hrNotifiedCount,
            'hr_emailed_count' => $hrEmailedCount
        ]);
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
}

function getManagerUploadedPayrollExcelRows(PDO $pdo, string $currentUserId): void
{
    $requestInvNo = trim((string)($_POST['request_inv_no'] ?? ''));
    $monthYear = trim((string)($_POST['month'] ?? ''));
    $fileId = trim((string)($_POST['file_id'] ?? ''));

    try {
        $currentUserType = strtolower(trim((string)($GLOBALS['user_type'] ?? '')));
        if (!in_array($currentUserType, ['hr_payroll', 'hr_senior_bp'], true) && $currentUserType !== 'administrator') {
            throw new Exception('Only HR Payroll and HR Senior BP can review manager uploaded payroll Excel.');
        }

        if ($requestInvNo === '' || $monthYear === '' || !preg_match('/^\d{4}-\d{2}$/', $monthYear)) {
            throw new Exception('Missing request number or valid month.');
        }

        $meta = normalizeManagerPayrollUploadMeta(loadManagerPayrollUploadMeta($requestInvNo, $monthYear), $requestInvNo, $monthYear);
        $uploads = isset($meta['uploads']) && is_array($meta['uploads']) ? $meta['uploads'] : [];
        if (empty($uploads)) {
            throw new Exception('No manager uploaded payroll Excel found for this request and month.');
        }

        $selectedUpload = [];
        if ($fileId !== '') {
            $found = findManagerPayrollUploadByFileId($meta, $fileId);
            $selectedUpload = $found['upload'];
            if (empty($selectedUpload)) {
                throw new Exception('Selected manager uploaded file was not found.');
            }
        } else {
            $selectedUpload = $uploads[0];
        }

        $filePath = getManagerPayrollUploadStorageDir() . DIRECTORY_SEPARATOR . basename((string)($selectedUpload['stored_name'] ?? ''));
        if (!is_file($filePath)) {
            throw new Exception('Uploaded file no longer exists on server.');
        }

        $parsed = parseManagerUploadedPayrollFileToRows($filePath, $monthYear);
        $rows = isset($parsed['rows']) && is_array($parsed['rows']) ? $parsed['rows'] : [];
        if (empty($rows)) {
            throw new Exception('Uploaded file contains no importable rows in Benefits Import or Deductions Import sheets.');
        }

        $uploadedEmpIds = array_values(array_unique(array_filter(array_map(static function ($row) {
            return trim((string)($row['emp_id'] ?? ''));
        }, $rows))));

        if (!empty($uploadedEmpIds)) {
            $empPlaceholders = implode(',', array_fill(0, count($uploadedEmpIds), '?'));
            $empStmt = $pdo->prepare("SELECT emp_id FROM employees WHERE emp_id IN ($empPlaceholders)");
            $empStmt->execute($uploadedEmpIds);
            $validEmpIds = array_flip(array_map('strval', $empStmt->fetchAll(PDO::FETCH_COLUMN)));

            $rows = array_values(array_filter($rows, static function ($row) use ($validEmpIds) {
                return isset($validEmpIds[trim((string)($row['emp_id'] ?? ''))]);
            }));
        }

        // Do not block here when none of the uploaded rows match an existing employee -
        // let the review modal open and show "no records available" instead of a hard error.
        $checkpointCode = strtoupper(trim((string)($parsed['checkpoint_code'] ?? '')));
        if ($checkpointCode === '') {
            throw new Exception('The uploaded file is not valid.');
        }

        ensureManagerPayrollImportCheckpointRegistryTable($pdo);
        $checkpointStmt = $pdo->prepare("SELECT used_at FROM payroll_import_checkpoint_registry WHERE checkpoint_code = :checkpoint_code LIMIT 1");
        $checkpointStmt->execute([':checkpoint_code' => $checkpointCode]);
        $checkpointRecord = $checkpointStmt->fetch(PDO::FETCH_ASSOC) ?: null;
        if (!$checkpointRecord) {
            throw new Exception('The uploaded file is not valid.');
        }
        if (!empty($checkpointRecord['used_at'])) {
            throw new Exception("This file is already uploaded, so you can't upload it again.");
        }

        echo json_encode([
            'status' => 'success',
            'message' => 'Manager uploaded file is ready for review.',
            'meta' => $selectedUpload,
            'checkpoint_code' => $checkpointCode,
            'rows' => $rows
        ]);
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
}

function getPayrollCompensationForEmployees(PDO $pdo, string $currentUserId): void
{
    $monthYear = trim((string)($_POST['month'] ?? ''));
    $empIdsRaw = $_POST['emp_ids'] ?? '';

    try {
        $currentUserType = strtolower(trim((string)($GLOBALS['user_type'] ?? '')));
        if (!in_array($currentUserType, ['hr_payroll', 'hr_senior_bp'], true) && $currentUserType !== 'administrator') {
            throw new Exception('Only HR Payroll and HR Senior BP can look up payroll compensation.');
        }

        if ($monthYear === '' || !preg_match('/^\d{4}-\d{2}$/', $monthYear)) {
            throw new Exception('Missing or invalid month.');
        }

        if (is_array($empIdsRaw)) {
            $empIds = $empIdsRaw;
        } else {
            $empIds = explode(',', (string)$empIdsRaw);
        }

        $empIds = array_values(array_unique(array_filter(array_map(static function ($empId) {
            return trim((string)$empId);
        }, $empIds), static function ($empId) {
            return $empId !== '';
        })));

        if (empty($empIds)) {
            echo json_encode(['status' => 'success', 'compensation' => []]);
            return;
        }

        $placeholders = implode(',', array_fill(0, count($empIds), '?'));
        $stmt = $pdo->prepare("SELECT emp_id, basic_salary, housing_allowance, food_allowance, transport_allowance, total_gross_salary
            FROM payrolls
            WHERE month_year = ? AND emp_id IN ($placeholders)");
        $stmt->execute(array_merge([$monthYear], $empIds));

        $compensation = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $compensation[(string)$row['emp_id']] = [
                'basic_salary' => (float)($row['basic_salary'] ?? 0),
                'housing_allowance' => (float)($row['housing_allowance'] ?? 0),
                'food_allowance' => (float)($row['food_allowance'] ?? 0),
                'transport_allowance' => (float)($row['transport_allowance'] ?? 0),
                'total_gross_salary' => (float)($row['total_gross_salary'] ?? 0),
            ];
        }

        echo json_encode(['status' => 'success', 'compensation' => $compensation]);
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
}

function listManagerUploadedPayrollExcelFiles(PDO $pdo, string $currentUserId): void
{
    $requestInvNo = trim((string)($_POST['request_inv_no'] ?? ''));
    $monthYear = trim((string)($_POST['month'] ?? ''));

    try {
        $currentUserType = strtolower(trim((string)($GLOBALS['user_type'] ?? '')));
        if (!in_array($currentUserType, ['hr_payroll', 'hr_senior_bp'], true) && $currentUserType !== 'administrator') {
            throw new Exception('Only HR Payroll and HR Senior BP can review manager uploaded payroll Excel.');
        }

        if ($requestInvNo === '' || $monthYear === '' || !preg_match('/^\d{4}-\d{2}$/', $monthYear)) {
            throw new Exception('Missing request number or valid month.');
        }

        $meta = normalizeManagerPayrollUploadMeta(loadManagerPayrollUploadMeta($requestInvNo, $monthYear), $requestInvNo, $monthYear);
        $uploads = isset($meta['uploads']) && is_array($meta['uploads']) ? $meta['uploads'] : [];
        if (empty($uploads)) {
            throw new Exception('No manager uploaded payroll Excel found for this request and month.');
        }

        $storageDir = getManagerPayrollUploadStorageDir();
        $files = [];
        foreach ($uploads as $upload) {
            if (!is_array($upload)) {
                continue;
            }

            $storedName = trim((string)($upload['stored_name'] ?? ''));
            if ($storedName === '') {
                continue;
            }

            $originalName = trim((string)($upload['original_name'] ?? $storedName));
            $uploadedByNameRaw = trim((string)($upload['uploaded_by_name'] ?? ''));
            $uploadedByNameDisplay = $uploadedByNameRaw;
            if ($uploadedByNameDisplay !== '' && function_exists('parseName')) {
                $parsedName = trim((string)parseName($uploadedByNameDisplay, 'FIRST_LAST'));
                if ($parsedName !== '') {
                    $uploadedByNameDisplay = $parsedName;
                }
            }

            $files[] = [
                'file_id' => trim((string)($upload['file_id'] ?? '')),
                'original_name' => $originalName,
                'company_name' => extractManagerPayrollCompanyNameFromFileName($originalName),
                'stored_name' => $storedName,
                'uploaded_by' => trim((string)($upload['uploaded_by'] ?? '')),
                'uploaded_by_name' => $uploadedByNameDisplay,
                'uploaded_at' => trim((string)($upload['uploaded_at'] ?? '')),
                'reviewed_by' => trim((string)($upload['reviewed_by'] ?? '')),
                'reviewed_by_name' => trim((string)($upload['reviewed_by_name'] ?? '')),
                'reviewed_at' => trim((string)($upload['reviewed_at'] ?? '')),
                'review_note' => trim((string)($upload['review_note'] ?? '')),
                'is_available' => is_file($storageDir . DIRECTORY_SEPARATOR . basename($storedName))
            ];
        }

        echo json_encode([
            'status' => 'success',
            'files' => $files
        ]);
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
}

function markManagerUploadedPayrollExcelReviewed(PDO $pdo, string $currentUserId): void
{
    $requestInvNo = trim((string)($_POST['request_inv_no'] ?? ''));
    $monthYear = trim((string)($_POST['month'] ?? ''));
    $fileId = trim((string)($_POST['file_id'] ?? ''));
    $reviewNote = trim((string)($_POST['review_note'] ?? ''));

    try {
        $currentUserType = strtolower(trim((string)($GLOBALS['user_type'] ?? '')));
        if (!in_array($currentUserType, ['hr_payroll', 'hr_senior_bp'], true) && $currentUserType !== 'administrator') {
            throw new Exception('Only HR Payroll and HR Senior BP can update manager upload review records.');
        }

        if ($requestInvNo === '' || $monthYear === '' || !preg_match('/^\d{4}-\d{2}$/', $monthYear) || $fileId === '') {
            throw new Exception('Missing file selection, request number, or valid month.');
        }

        $lockHandle = acquireManagerPayrollUploadLock($requestInvNo, $monthYear);
        try {
            $meta = normalizeManagerPayrollUploadMeta(loadManagerPayrollUploadMeta($requestInvNo, $monthYear), $requestInvNo, $monthYear);
            $found = findManagerPayrollUploadByFileId($meta, $fileId);
            if ($found['index'] < 0 || empty($found['upload'])) {
                throw new Exception('Selected manager uploaded file was not found.');
            }

            $reviewerNameStmt = $pdo->prepare('SELECT name FROM employees WHERE emp_id = :emp_id LIMIT 1');
            $reviewerNameStmt->execute([':emp_id' => $currentUserId]);
            $reviewerName = (string)($reviewerNameStmt->fetchColumn() ?: $currentUserId);

            $meta['uploads'][$found['index']]['reviewed_by'] = $currentUserId;
            $meta['uploads'][$found['index']]['reviewed_by_name'] = $reviewerName;
            $meta['uploads'][$found['index']]['reviewed_at'] = date('Y-m-d H:i:s');
            $meta['uploads'][$found['index']]['review_note'] = $reviewNote !== '' ? $reviewNote : 'Reviewed by HR Payroll and imported into payroll.';

            saveManagerPayrollUploadMeta($requestInvNo, $monthYear, normalizeManagerPayrollUploadMeta($meta, $requestInvNo, $monthYear));
        } finally {
            releaseManagerPayrollUploadLock($lockHandle);
        }

        echo json_encode([
            'status' => 'success',
            'message' => 'Review record updated successfully.',
            'meta' => $meta['uploads'][$found['index']]
        ]);
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
}

function deleteManagerUploadedPayrollExcelFile(PDO $pdo, string $currentUserId): void
{
    $requestInvNo = trim((string)($_POST['request_inv_no'] ?? ''));
    $monthYear = trim((string)($_POST['month'] ?? ''));
    $fileId = trim((string)($_POST['file_id'] ?? ''));

    try {
        $currentUserType = strtolower(trim((string)($GLOBALS['user_type'] ?? '')));
        if (!in_array($currentUserType, ['hr_payroll', 'hr_senior_bp'], true) && $currentUserType !== 'administrator') {
            throw new Exception('Only HR Payroll and HR Senior BP can delete manager uploaded payroll Excel.');
        }

        if ($requestInvNo === '' || $monthYear === '' || !preg_match('/^\d{4}-\d{2}$/', $monthYear) || $fileId === '') {
            throw new Exception('Missing file selection, request number, or valid month.');
        }

        $lockHandle = acquireManagerPayrollUploadLock($requestInvNo, $monthYear);
        try {
            $meta = normalizeManagerPayrollUploadMeta(loadManagerPayrollUploadMeta($requestInvNo, $monthYear), $requestInvNo, $monthYear);
            $found = findManagerPayrollUploadByFileId($meta, $fileId);
            if ($found['index'] < 0 || empty($found['upload'])) {
                throw new Exception('Selected manager uploaded file was not found.');
            }

            if (!empty($found['upload']['reviewed_at'])) {
                throw new Exception('A reviewed file cannot be deleted.');
            }

            $storedName = trim((string)($found['upload']['stored_name'] ?? ''));
            if ($storedName !== '') {
                $filePath = getManagerPayrollUploadStorageDir() . DIRECTORY_SEPARATOR . basename($storedName);
                if (is_file($filePath)) {
                    @unlink($filePath);
                }
            }

            array_splice($meta['uploads'], $found['index'], 1);
            saveManagerPayrollUploadMeta($requestInvNo, $monthYear, normalizeManagerPayrollUploadMeta($meta, $requestInvNo, $monthYear));
        } finally {
            releaseManagerPayrollUploadLock($lockHandle);
        }

        echo json_encode([
            'status' => 'success',
            'message' => 'Uploaded file deleted successfully.'
        ]);
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
}

function sendSupervisorPayrollReport(PDO $pdo, $conDB, string $currentUserId): void
{
    $requestInvNo = trim((string)($_POST['request_inv_no'] ?? ''));
    $monthYear = trim((string)($_POST['month'] ?? ''));
    $supervisorIdsInput = $_POST['supervisor_ids'] ?? ($_POST['supervisor_id'] ?? '');

    $supervisorIds = [];
    if (is_array($supervisorIdsInput)) {
        $supervisorIds = $supervisorIdsInput;
    } elseif (is_string($supervisorIdsInput)) {
        $raw = trim($supervisorIdsInput);
        if ($raw !== '') {
            if ($raw[0] === '[') {
                $decoded = json_decode($raw, true);
                if (is_array($decoded)) {
                    $supervisorIds = $decoded;
                }
            }
            if (empty($supervisorIds)) {
                $supervisorIds = explode(',', $raw);
            }
        }
    }
    $supervisorIds = array_values(array_unique(array_filter(array_map(static function ($value) {
        return trim((string)$value);
    }, $supervisorIds), static function ($value) {
        return $value !== '';
    })));

    // Optional: other Direct Supervisors whose employees should be merged into the
    // single primary supervisor's report/excel (they still review as one team, but
    // only the primary supervisor receives the email and upload rights).
    $includedSupervisorIdsInput = $_POST['included_supervisor_ids'] ?? '';
    $includedSupervisorIds = [];
    if (is_array($includedSupervisorIdsInput)) {
        $includedSupervisorIds = $includedSupervisorIdsInput;
    } elseif (is_string($includedSupervisorIdsInput)) {
        $raw = trim($includedSupervisorIdsInput);
        if ($raw !== '') {
            if ($raw[0] === '[') {
                $decoded = json_decode($raw, true);
                if (is_array($decoded)) {
                    $includedSupervisorIds = $decoded;
                }
            }
            if (empty($includedSupervisorIds)) {
                $includedSupervisorIds = explode(',', $raw);
            }
        }
    }
    $includedSupervisorIds = array_values(array_unique(array_filter(array_map(static function ($value) {
        return trim((string)$value);
    }, $includedSupervisorIds), static function ($value) {
        return $value !== '';
    })));

    $tempFilePath = '';

    try {
        ensurePayrollSupervisorReportDispatchTable($pdo);

        $currentUserRole = strtolower(trim((string)($GLOBALS['user_type'] ?? '')));
        if (!in_array($currentUserRole, ['hr_payroll', 'hr_senior_bp'], true) && $currentUserRole !== 'administrator') {
            throw new Exception('Only HR Payroll and HR Senior BP can send this report.');
        }

        if ($requestInvNo === '' || $monthYear === '' || empty($supervisorIds)) {
            throw new Exception('Missing required data to send payroll report.');
        }

        // Merging other supervisors' employees into one report only makes sense for a
        // single primary recipient; drop the primary itself out of the included list.
        if (count($supervisorIds) !== 1) {
            $includedSupervisorIds = [];
        } else {
            $includedSupervisorIds = array_values(array_diff($includedSupervisorIds, $supervisorIds));
        }

        $requestStmt = $pdo->prepare("SELECT payroll_month, status FROM payroll_approval_requests WHERE request_inv_no = :inv_no LIMIT 1");
        $requestStmt->execute([':inv_no' => $requestInvNo]);
        $requestRow = $requestStmt->fetch(PDO::FETCH_ASSOC);
        if (!$requestRow) {
            throw new Exception('Payroll request not found.');
        }

        $requestStatus = strtolower(trim((string)($requestRow['status'] ?? '')));
        if ($requestStatus !== 'pending_approval') {
            throw new Exception('Supervisor reports can be sent only when payroll is pending with HR Payroll.');
        }

        $monthValue = (string)($requestRow['payroll_month'] ?? $monthYear);

        $totalStmt = $pdo->prepare("SELECT COUNT(DISTINCT emp_id) FROM payrolls WHERE month_year = :month_year");
        $totalStmt->execute([':month_year' => $monthValue]);
        $totalEmployees = (int)$totalStmt->fetchColumn();

        $checkedStmt = $pdo->prepare("SELECT COUNT(DISTINCT pec.emp_id)
            FROM payroll_checklist_employee_checks pec
            INNER JOIN admin_login al ON al.emp_id = pec.approver_id
            WHERE pec.request_inv_no = :request_inv_no
              AND pec.payroll_month = :month_year
              AND pec.is_checked = 1
              AND LOWER(TRIM(al.user_type)) = 'hr_payroll'");
        $checkedStmt->execute([
            ':request_inv_no' => $requestInvNo,
            ':month_year' => $monthValue
        ]);
        $checkedEmployees = (int)$checkedStmt->fetchColumn();

        if ($totalEmployees <= 0) {
            throw new Exception('No payroll employees found for this month.');
        }

        if ($checkedEmployees >= $totalEmployees) {
            throw new Exception('All employees are already marked checked. Send the supervisor report before completing Mark Checked.');
        }

        if (!class_exists('PhpOffice\\PhpSpreadsheet\\Spreadsheet')) {
            $autoloadPaths = [
                __DIR__ . '/../../vendor/autoload.php',
                __DIR__ . '/../../../vendor/autoload.php',
                __DIR__ . '/../../includes/vendor/autoload.php'
            ];
            foreach ($autoloadPaths as $path) {
                if (file_exists($path)) {
                    require_once $path;
                    break;
                }
            }
        }

        if (!class_exists('PhpOffice\\PhpSpreadsheet\\Spreadsheet')) {
            throw new Exception('Spreadsheet library is not available on server.');
        }

        $alreadySentStmt = $pdo->prepare("SELECT id, merged_into_supervisor_emp_id
            FROM payroll_supervisor_report_dispatch
            WHERE request_inv_no = :request_inv_no
              AND payroll_month = :payroll_month
              AND supervisor_emp_id = :supervisor_emp_id
            LIMIT 1");
        $supervisorStmt = $pdo->prepare("SELECT al.emp_id, al.email, e.name
            FROM admin_login al
            INNER JOIN employees e ON e.emp_id = al.emp_id
            WHERE al.emp_id = :emp_id
              AND al.email IS NOT NULL
              AND TRIM(al.email) <> ''
            LIMIT 1");
        $buildPayrollStmtForSupervisorIds = static function (PDO $pdo, array $supervisorIdsForQuery) {
            $placeholders = implode(',', array_fill(0, count($supervisorIdsForQuery), '?'));
            return $pdo->prepare("SELECT
                    p.emp_id,
                    e.name AS employee_name,
                    COALESCE(d.dep_nme, '') AS department_name,
                    COALESCE(c.comp_name, '') AS company_name,
                    p.basic_salary,
                    p.total_benefits,
                    p.total_deductions,
                    p.net_salary,
                    p.status
                FROM payrolls p
                INNER JOIN employees e ON e.emp_id = p.emp_id
                LEFT JOIN department d ON d.id = e.dept
                LEFT JOIN companies c ON c.comp_id = e.comp_no
                WHERE p.month_year = ?
                  AND e.supervisor_id IN ($placeholders)
                ORDER BY e.name ASC");
        };
                $benefitsStmt = $pdo->prepare("SELECT
                                CASE
                                        WHEN pb.type_id IS NOT NULL AND bt.name IS NOT NULL THEN bt.name
                                        ELSE pb.benefit
                                END AS benefit_name,
                        pb.note,
                                pb.hours,
                                pb.days,
                                pb.calculation_type
                        FROM payroll_benefits pb
                        LEFT JOIN benefit_types bt ON pb.type_id = bt.id
                        WHERE pb.emp_id = :emp_id
                            AND pb.month = :month_year
                            AND pb.status = 1");
                $deductionsStmt = $pdo->prepare("SELECT pd.deduction, pd.note, pd.hours, pd.days, pd.calculation_type
                    FROM payroll_deductions pd
                    WHERE pd.emp_id = :emp_id
                        AND pd.month = :month_year
                        AND pd.status = 1");
        // ON DUPLICATE KEY UPDATE lets HR resend to a supervisor who already received a
        // report - the row is refreshed in place (new sent_at/checkpoint-bearing file)
        // instead of being blocked by the unique key.
        $dispatchInsert = $pdo->prepare("INSERT INTO payroll_supervisor_report_dispatch
            (request_inv_no, payroll_month, supervisor_emp_id, supervisor_email, sent_by, included_supervisor_ids, merged_into_supervisor_emp_id)
            VALUES
            (:request_inv_no, :payroll_month, :supervisor_emp_id, :supervisor_email, :sent_by, :included_supervisor_ids, :merged_into_supervisor_emp_id)
            ON DUPLICATE KEY UPDATE
                supervisor_email = VALUES(supervisor_email),
                sent_by = VALUES(sent_by),
                sent_at = CURRENT_TIMESTAMP,
                included_supervisor_ids = VALUES(included_supervisor_ids),
                merged_into_supervisor_emp_id = VALUES(merged_into_supervisor_emp_id)");

        $sentSupervisorNames = [];

        // A supervisor already covered elsewhere (their own prior report, or merged
        // into a different primary) can still be reassigned as "also include" here -
        // HR may need to move their team to a different reviewer. The dispatch upsert
        // below simply repoints their row's merged_into_supervisor_emp_id to the new
        // primary, which also revokes their own direct upload access for that team.

        foreach ($supervisorIds as $supervisorEmpId) {
            $alreadySentStmt->execute([
                ':request_inv_no' => $requestInvNo,
                ':payroll_month' => $monthValue,
                ':supervisor_emp_id' => $supervisorEmpId
            ]);
            $existingDispatchRow = $alreadySentStmt->fetch(PDO::FETCH_ASSOC);
            // A supervisor whose team was merged into someone else's report can't be
            // picked as primary - resend to that other primary supervisor instead. A
            // supervisor who was themselves already a primary recipient CAN be resent
            // to (e.g. figures changed); the dispatch row/checkpoint is simply refreshed.
            if ($existingDispatchRow && trim((string)($existingDispatchRow['merged_into_supervisor_emp_id'] ?? '')) !== '') {
                throw new Exception('One of the selected supervisors\' employees were already merged into another supervisor\'s payroll report. Resend to that supervisor instead.');
            }

            $supervisorStmt->execute([':emp_id' => $supervisorEmpId]);
            $supervisor = $supervisorStmt->fetch(PDO::FETCH_ASSOC);
            if (!$supervisor) {
                throw new Exception('One selected supervisor has no registered email.');
            }

            // Merge in employees of the additional supervisors (if any) selected for
            // this primary recipient, so a single email/excel covers every team.
            $effectiveSupervisorIdsForRows = array_values(array_unique(array_merge([$supervisorEmpId], $includedSupervisorIds)));

            $payrollStmt = $buildPayrollStmtForSupervisorIds($pdo, $effectiveSupervisorIdsForRows);
            $payrollStmt->execute(array_merge([$monthValue], $effectiveSupervisorIdsForRows));
            $rows = $payrollStmt->fetchAll(PDO::FETCH_ASSOC);

            if (empty($rows)) {
                throw new Exception('No payroll records found for one selected supervisor in this month.');
            }

            $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
            $mainSheet = $spreadsheet->getActiveSheet();
            $mainSheet->setTitle('Payroll Report');

            $checkpointCode = issueManagerPayrollImportCheckpoint($pdo, $monthValue, $currentUserId);

            $benefitsSheet = $spreadsheet->createSheet();
            $benefitsSheet->setTitle('Benefits Import');

            $deductionsSheet = $spreadsheet->createSheet();
            $deductionsSheet->setTitle('Deductions Import');

            $metaSheet = $spreadsheet->createSheet();
            $metaSheet->setTitle('__PAYROLL_IMPORT_META');

            $mainHeaders = ['#', 'Emp ID', 'Employee Name', 'Department', 'Company', 'Basic Salary', 'Benefits', 'Benefits Reason', 'Deductions', 'Deduction Reason', 'Net Salary', 'Status'];
            $mainSheet->fromArray($mainHeaders, null, 'A1');

            $benefitsSheet->fromArray(
                ['emp_id', 'benefit_hours', 'benefit_minutes', 'benefit_reason'],
                null,
                'A1'
            );
            $benefitsSheet->fromArray(
                ['1001', '6', '0', 'Project Support Benefit'],
                null,
                'A2'
            );

            $deductionsSheet->fromArray(
                ['emp_id', 'deduction_days', 'deduction_hours', 'deduction_minutes', 'deduction_reason'],
                null,
                'A1'
            );
            $deductionsSheet->fromArray(
                ['1001', '0', '7', '0', 'Late Arrival Deduction'],
                null,
                'A2'
            );

            $metaSheet->fromArray([$checkpointCode, $monthValue], null, 'A1');

            $metaSheet->setSheetState(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet::SHEETSTATE_VERYHIDDEN);

            $mainRowIndex = 2;
            $benefitRowIndex = 3;
            $deductionRowIndex = 3;
            $totalNetSalary = 0.0;
            foreach ($rows as $index => $row) {
                $basicSalary = (float)($row['basic_salary'] ?? 0);
                $totalBenefits = (float)($row['total_benefits'] ?? 0);
                $totalDeductions = (float)($row['total_deductions'] ?? 0);
                $netSalary = (float)($row['net_salary'] ?? 0);
                $totalNetSalary += $netSalary;

                $benefitsStmt->execute([
                    ':emp_id' => (string)($row['emp_id'] ?? ''),
                    ':month_year' => $monthValue
                ]);
                $benefitRows = $benefitsStmt->fetchAll(PDO::FETCH_ASSOC);

                $deductionsStmt->execute([
                    ':emp_id' => (string)($row['emp_id'] ?? ''),
                    ':month_year' => $monthValue
                ]);
                $deductionRows = $deductionsStmt->fetchAll(PDO::FETCH_ASSOC);

                $benefitReasons = [];
                foreach ($benefitRows as $benefitRow) {
                    $benefitName = trim((string)($benefitRow['benefit_name'] ?? ''));
                    if ($benefitName === '') {
                        continue;
                    }

                    $hoursValue = (float)($benefitRow['hours'] ?? 0);

                    $reasonText = $benefitName;
                    if ($hoursValue > 0) {
                        $reasonText .= ' (' . rtrim(rtrim(number_format($hoursValue, 2, '.', ''), '0'), '.') . ' hrs)';
                    }

                    if (!in_array($reasonText, $benefitReasons, true)) {
                        $benefitReasons[] = $reasonText;
                    }

                    $benefitsSheet->fromArray([
                        (string)($row['emp_id'] ?? ''),
                        $hoursValue,
                        0,
                        $benefitName
                    ], null, 'A' . $benefitRowIndex);
                    $benefitRowIndex++;
                }

                $deductionReasons = [];
                foreach ($deductionRows as $deductionRow) {
                    $deductionName = trim((string)($deductionRow['deduction'] ?? ''));
                    $hoursValue = (float)($deductionRow['hours'] ?? 0);
                    $daysValue = (float)($deductionRow['days'] ?? 0);
                    $amountValue = (float)($deductionRow['note'] ?? 0);
                    $rawCalcType = strtolower(trim((string)($deductionRow['calculation_type'] ?? 'fixed')));
                    $deductionType = $rawCalcType;
                    if ($deductionType === 'hourly' || $deductionType === 'hours') {
                        $deductionType = 'hourly_deduction';
                    } elseif ($deductionType === 'daily' || $deductionType === 'days') {
                        $deductionType = 'daily_deduction';
                    }
                    if (!in_array($deductionType, ['fixed', 'hourly_deduction', 'daily_deduction'], true)) {
                        $deductionType = 'fixed';
                    }

                    // GOSI is system-managed in payroll and should not be exported in import sheet.
                    if (strtoupper($deductionName) === 'GOSI') {
                        continue;
                    }

                    $hasGeneratedDeduction = ($amountValue > 0 || $hoursValue > 0 || $daysValue > 0 || $deductionName !== '');
                    if (!$hasGeneratedDeduction) {
                        continue;
                    }

                    if ($deductionName === '') {
                        if ($deductionType === 'hourly_deduction') {
                            $deductionName = 'Hourly Deduction';
                        } elseif ($deductionType === 'daily_deduction') {
                            $deductionName = 'Daily Deduction';
                        } else {
                            $deductionName = 'Deduction';
                        }
                    }

                    $reasonText = $deductionName;
                    if ($hoursValue > 0) {
                        $reasonText .= ' (' . rtrim(rtrim(number_format($hoursValue, 2, '.', ''), '0'), '.') . ' hrs)';
                    } elseif ($daysValue > 0) {
                        $reasonText .= ' (' . rtrim(rtrim(number_format($daysValue, 2, '.', ''), '0'), '.') . ' days)';
                    }

                    if (!in_array($reasonText, $deductionReasons, true)) {
                        $deductionReasons[] = $reasonText;
                    }

                    $deductionsSheet->fromArray([
                        (string)($row['emp_id'] ?? ''),
                        $daysValue,
                        $hoursValue,
                        0,
                        $deductionName
                    ], null, 'A' . $deductionRowIndex);
                    $deductionRowIndex++;
                }

                $mainSheet->fromArray([
                    $index + 1,
                    (string)($row['emp_id'] ?? ''),
                    (string)($row['employee_name'] ?? ''),
                    (string)($row['department_name'] ?? ''),
                    (string)($row['company_name'] ?? ''),
                    $basicSalary,
                    $totalBenefits,
                    implode('; ', $benefitReasons),
                    $totalDeductions,
                    implode('; ', $deductionReasons),
                    $netSalary,
                    (string)($row['status'] ?? '')
                ], null, 'A' . $mainRowIndex);
                $mainRowIndex++;
            }

            if ($mainRowIndex > 2) {
                foreach (['F', 'G', 'I', 'K'] as $column) {
                    $mainSheet->getStyle($column . '2:' . $column . ($mainRowIndex - 1))
                        ->getNumberFormat()
                        ->setFormatCode('#,##0.00');
                }
            }

            foreach (range('A', 'L') as $column) {
                $mainSheet->getColumnDimension($column)->setAutoSize(true);
            }

            if ($benefitRowIndex > 2) {
                foreach (['B', 'C'] as $column) {
                    $benefitsSheet->getStyle($column . '2:' . $column . ($benefitRowIndex - 1))
                        ->getNumberFormat()
                        ->setFormatCode('#,##0.00');
                }
            }

            if ($deductionRowIndex > 2) {
                foreach (['B', 'C', 'D'] as $column) {
                    $deductionsSheet->getStyle($column . '2:' . $column . ($deductionRowIndex - 1))
                        ->getNumberFormat()
                        ->setFormatCode('#,##0.00');
                }
            }

            foreach (range('A', 'D') as $column) {
                $benefitsSheet->getColumnDimension($column)->setAutoSize(true);
            }

            foreach (range('A', 'E') as $column) {
                $deductionsSheet->getColumnDimension($column)->setAutoSize(true);
            }

            $safeMonth = preg_replace('/[^0-9A-Za-z_-]+/', '_', $monthValue);
            $safeSupervisor = preg_replace('/[^0-9A-Za-z_-]+/', '_', (string)($supervisor['name'] ?? 'supervisor'));
            $attachmentName = 'payroll_' . $safeMonth . '_' . $safeSupervisor . '.xlsx';
            $tempFilePath = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . uniqid('payroll_report_', true) . '.xlsx';

            $spreadsheet->setActiveSheetIndex(0);
            $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
            $writer->save($tempFilePath);

            $emailSubject = 'Team Payroll Verification - ' . $monthValue . ' (' . (string)$supervisor['name'] . ')';
            $requestUrl = (function_exists('get_base_url') ? get_base_url() : 'https://hr.almutlaksystem.com')
                . '/payroll_checklist_report.php?month=' . urlencode($monthValue)
                . '&request_inv_no=' . urlencode($requestInvNo);
            $importantNoteTitle = 'Important Message';
            $importantNoteText = 'For your team and month ' . $monthValue . ', when entering Benefits and Deductions in the attached file, all values must strictly follow BioTime machine records. This file will be rechecked again to make sure there is no discrepancy, so please ensure every entered value is accurate.';
            $importantNoteArabicText = 'بالنسبة لفريقك ولشهر ' . $monthValue . '، عند إدخال قيم البدلات والاستقطاعات في الملف المرفق، يجب أن تتوافق جميع القيم بشكل صارم مع سجلات جهاز BioTime. سيتم إعادة التحقق من هذا الملف مرة أخرى للتأكد من عدم وجود أي اختلافات، لذا يرجى التأكد من دقة كل قيمة يتم إدخالها.';
            $mergedSupervisorNames = [];
            if (!empty($includedSupervisorIds)) {
                $mergedNamePlaceholders = implode(',', array_fill(0, count($includedSupervisorIds), '?'));
                $mergedNameStmt = $pdo->prepare("SELECT name FROM employees WHERE emp_id IN ($mergedNamePlaceholders) ORDER BY name ASC");
                $mergedNameStmt->execute($includedSupervisorIds);
                $mergedSupervisorNames = array_map('strval', $mergedNameStmt->fetchAll(PDO::FETCH_COLUMN));
            }

            $emailMessage = 'Please review the attached payroll report for your team'
                . ' (' . count($rows) . ' employees) for month ' . $monthValue . '.'
                . (!empty($mergedSupervisorNames)
                    ? ' This report also includes the team(s) of: ' . implode(', ', $mergedSupervisorNames) . '.'
                    : '')
                . ' You can also open the Payroll Check List page below to review your team and upload the completed Excel file directly.';
            $emailMessageHtml = '<div style="margin:0; color:#e0e0e0; line-height:1.7;">'
                . htmlspecialchars($emailMessage, ENT_QUOTES, 'UTF-8')
                . '<br><br><strong>Request ID:</strong> ' . htmlspecialchars($requestInvNo, ENT_QUOTES, 'UTF-8')
                . '<br><strong>Total Net Salary:</strong> SAR ' . number_format($totalNetSalary, 2)
                . '<div style="margin-top:14px; padding:10px 12px; border:1px solid #ff4d4f; border-radius:6px; color:#ff4d4f; font-weight:700; background:#fff5f5;">'
                . htmlspecialchars($importantNoteTitle, ENT_QUOTES, 'UTF-8')
                . ': '
                . htmlspecialchars($importantNoteText, ENT_QUOTES, 'UTF-8')
                . '<br><span dir="rtl" style="display:inline-block; margin-top:6px;">'
                . htmlspecialchars('رسالة مهمة: ' . $importantNoteArabicText, ENT_QUOTES, 'UTF-8')
                . '</span>'
                . '</div>'
                . '</div>';

            $emailSent = sendPayrollEmailWithAttachment(
                $conDB,
                (string)$supervisor['email'],
                (string)($supervisor['name'] ?? 'Supervisor'),
                $emailSubject,
                [
                    'APPROVER_NAME' => (string)($supervisor['name'] ?? 'Supervisor'),
                    'REQUEST_ID' => $requestInvNo,
                    'REQUEST_TYPE' => 'Team Payroll Verification',
                    'PAYROLL_MONTH' => $monthValue,
                    'EMPLOYEE_COUNT' => (string)count($rows),
                    'TOTAL_NET_SALARY' => number_format($totalNetSalary, 2),
                    'PAYROLL_STATUS' => 'Ready for Verification',
                    'EMAIL_MESSAGE' => $emailMessage,
                    'EMAIL_MESSAGE_HTML' => $emailMessageHtml,
                    'REQUEST_URL' => $requestUrl,
                    'ACTION_BUTTON_TEXT' => 'Open Payroll Check List'
                ],
                $tempFilePath,
                $attachmentName
            );

            if (!$emailSent) {
                $mailError = trim((string)($GLOBALS['payroll_company_report_mail_error'] ?? ''));
                if ($mailError === '') {
                    $mailError = 'Failed to send email. Please verify SMTP settings.';
                }

                throw new Exception($mailError);
            }

            $dispatchInsert->execute([
                ':request_inv_no' => $requestInvNo,
                ':payroll_month' => $monthValue,
                ':supervisor_emp_id' => $supervisorEmpId,
                ':supervisor_email' => (string)$supervisor['email'],
                ':sent_by' => $currentUserId,
                ':included_supervisor_ids' => !empty($includedSupervisorIds) ? json_encode(array_values($includedSupervisorIds)) : null,
                ':merged_into_supervisor_emp_id' => null
            ]);

            // Mark each merged-in supervisor as covered by this dispatch too, so they
            // can't be independently selected/re-sent later and their own login (if
            // any) is not treated as having received this email.
            foreach ($includedSupervisorIds as $includedSupervisorEmpId) {
                $supervisorStmt->execute([':emp_id' => $includedSupervisorEmpId]);
                $includedSupervisorInfo = $supervisorStmt->fetch(PDO::FETCH_ASSOC);
                $dispatchInsert->execute([
                    ':request_inv_no' => $requestInvNo,
                    ':payroll_month' => $monthValue,
                    ':supervisor_emp_id' => $includedSupervisorEmpId,
                    ':supervisor_email' => (string)($includedSupervisorInfo['email'] ?? ''),
                    ':sent_by' => $currentUserId,
                    ':included_supervisor_ids' => null,
                    ':merged_into_supervisor_emp_id' => $supervisorEmpId
                ]);
            }

            $sentSupervisorNames[] = (string)($supervisor['name'] ?? $supervisorEmpId);

            if (!empty($tempFilePath) && file_exists($tempFilePath)) {
                @unlink($tempFilePath);
                $tempFilePath = '';
            }
        }

        echo json_encode([
            'status' => 'success',
            'message' => 'Payroll report email sent successfully to ' . count($sentSupervisorNames) . ' supervisor' . (count($sentSupervisorNames) === 1 ? '' : 's') . '.',
            'sent_supervisors' => $sentSupervisorNames
        ]);
    } catch (Exception $e) {
        if (!empty($tempFilePath) && file_exists($tempFilePath)) {
            @unlink($tempFilePath);
        }
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
}

function sendPayrollEmailWithAttachment($conDB, string $toEmail, string $toName, string $subject, array $templateData, string $attachmentPath, string $attachmentName): bool
{
    $GLOBALS['payroll_company_report_mail_error'] = '';

    if (!class_exists('PHPMailer\\PHPMailer\\PHPMailer')) {
        $GLOBALS['payroll_company_report_mail_error'] = 'Email library is not available on the server.';
        return false;
    }

    if (!filter_var($toEmail, FILTER_VALIDATE_EMAIL)) {
        $GLOBALS['payroll_company_report_mail_error'] = 'Selected manager email address is invalid.';
        return false;
    }

    if (!file_exists($attachmentPath)) {
        $GLOBALS['payroll_company_report_mail_error'] = 'Payroll attachment file was not generated.';
        return false;
    }

    $smtpHost = get_setting($conDB, 'smtp_host');
    $smtpPort = (int)get_setting($conDB, 'smtp_port');
    $smtpUser = get_setting($conDB, 'smtp_user');
    $smtpPass = get_setting($conDB, 'smtp_pass');
    $smtpFromEmail = get_setting($conDB, 'from_email');
    $smtpFromName = get_setting($conDB, 'from_name', 'Al Mutlak HR System');
    $smtpSecure = strtolower((string)get_setting($conDB, 'smtp_encryption'));

    if (empty($smtpHost) || empty($smtpPort) || empty($smtpUser) || empty($smtpPass) || empty($smtpFromEmail)) {
        $GLOBALS['payroll_company_report_mail_error'] = 'SMTP settings are incomplete. Please verify smtp_host, smtp_port, smtp_user, smtp_pass, and from_email.';
        return false;
    }

    $bodyHtml = load_email_template('payroll_request', $templateData);
    if ($bodyHtml === false) {
        $safeMessage = htmlspecialchars((string)($templateData['EMAIL_MESSAGE'] ?? 'Payroll report attached for review.'), ENT_QUOTES, 'UTF-8');
        $bodyHtml = '<div style="font-family:Segoe UI,Arial,sans-serif;color:#222;">'
            . '<h2 style="margin:0 0 10px;">' . htmlspecialchars((string)($templateData['REQUEST_TYPE'] ?? 'Payroll Verification'), ENT_QUOTES, 'UTF-8') . '</h2>'
            . '<p style="margin:0 0 12px;">' . $safeMessage . '</p>'
            . '</div>';
    }

    try {
        $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
        $mail->isSMTP();
        $mail->Host = $smtpHost;
        $mail->SMTPAuth = true;
        $mail->Username = $smtpUser;
        $mail->Password = $smtpPass;
        if ($smtpSecure === 'tls') {
            $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        } elseif ($smtpSecure === 'ssl') {
            $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
        } else {
            $mail->SMTPSecure = false;
        }
        $mail->Port = $smtpPort;
        $mail->CharSet = 'UTF-8';
        $mail->Timeout = 12;

        $mail->setFrom($smtpFromEmail, $smtpFromName ?: 'Al Mutlak HR System');
        $mail->addAddress($toEmail, $toName);
        $mail->addReplyTo($smtpFromEmail, $smtpFromName ?: 'Al Mutlak HR System');

        $mail->addAttachment($attachmentPath, $attachmentName);

        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $bodyHtml;
        $mail->AltBody = strip_tags($bodyHtml);

        $mail->send();
        return true;
    } catch (Exception $e) {
        $mailInfo = '';
        if (isset($mail) && is_object($mail) && !empty($mail->ErrorInfo)) {
            $mailInfo = ' | PHPMailer: ' . $mail->ErrorInfo;
        }

        $GLOBALS['payroll_company_report_mail_error'] = trim('Failed to send email. ' . $e->getMessage() . $mailInfo);
        error_log('PAYROLL_COMPANY_REPORT_EMAIL_ERROR: ' . $GLOBALS['payroll_company_report_mail_error']);
        return false;
    }
}

function sendBackPayrollIssues(PDO $pdo, $conDB, string $currentUserId, int $requestTypeId): void
{
    $requestInvNo = trim((string)($_POST['request_inv_no'] ?? ''));
    $monthYear = trim((string)($_POST['month'] ?? ''));
    $note = trim((string)($_POST['note'] ?? ''));
    $employeeIdsRaw = trim((string)($_POST['employee_ids'] ?? ''));

    if ($requestInvNo === '') {
        echo json_encode(['status' => 'error', 'message' => 'Missing request number']);
        return;
    }
    if ($note === '') {
        echo json_encode(['status' => 'error', 'message' => 'Issue note is required']);
        return;
    }

    $employeeIds = array_values(array_filter(array_map('trim', explode(',', $employeeIdsRaw)), static function ($value) {
        return $value !== '';
    }));

    if (empty($employeeIds)) {
        echo json_encode(['status' => 'error', 'message' => 'Select at least one employee with issue']);
        return;
    }

    try {
        $currentUserRole = strtolower(trim((string)($GLOBALS['user_type'] ?? '')));
        if ($currentUserRole !== 'hr_payroll') {
            throw new Exception('You are not allowed to submit payroll feedback for this request.');
        }

        // Run DDL outside transaction to avoid implicit commit side effects in MySQL.
        ensurePayrollChecklistFeedbackTable($pdo);

        $pdo->beginTransaction();

        $requestStmt = $pdo->prepare("SELECT request_inv_no, payroll_month, requested_by FROM payroll_approval_requests WHERE request_inv_no = :inv_no LIMIT 1");
        $requestStmt->execute([':inv_no' => $requestInvNo]);
        $requestRow = $requestStmt->fetch(PDO::FETCH_ASSOC);
        if (!$requestRow) {
            throw new Exception('Payroll request not found');
        }

        $monthValue = $monthYear !== '' ? $monthYear : (string)$requestRow['payroll_month'];

        // Clear HR checklist checks ONLY for the affected employees so they must re-verify them.
        // The approval chain is NOT restarted — the payroll stays approved.
        if (!empty($employeeIds)) {
            $empPlaceholders = implode(',', array_fill(0, count($employeeIds), '?'));
            $clearAffectedChecksStmt = $pdo->prepare("DELETE FROM payroll_checklist_employee_checks
                WHERE request_inv_no = ?
                  AND payroll_month = ?
                  AND emp_id IN ($empPlaceholders)");
            $clearAffectedChecksStmt->execute(
                array_merge([$requestInvNo, $monthValue], $employeeIds)
            );
        }

        $insertFeedback = $pdo->prepare("INSERT INTO payroll_checklist_feedback (request_inv_no, payroll_month, emp_id, approver_id, feedback_note, status)
            VALUES (:request_inv_no, :payroll_month, :emp_id, :approver_id, :feedback_note, 'open')");

        foreach ($employeeIds as $employeeId) {
            $insertFeedback->execute([
                ':request_inv_no' => $requestInvNo,
                ':payroll_month' => $monthValue,
                ':emp_id' => (string)$employeeId,
                ':approver_id' => $currentUserId,
                ':feedback_note' => $note
            ]);
        }

        $employeeListForNote = implode(', ', $employeeIds);
        $fullNote = 'Payroll checklist feedback recorded for employees: ' . $employeeListForNote . '. ' . $note;

        $history = $pdo->prepare("INSERT INTO smt_request_status (inv_no, emp_id, emp_name, note, status)
            VALUES (:inv_no, :emp_id, :emp_name, :note, 'feedback')");
        $history->execute([
            ':inv_no' => $requestInvNo,
            ':emp_id' => $currentUserId,
            ':emp_name' => 'System',
            ':note' => 'Payroll ' . $monthValue . ' feedback submitted. ' . $fullNote
        ]);

        $feedbackSummary = getPayrollFeedbackSummary($pdo, $requestInvNo, $monthValue);

        $pdo->commit();

        echo json_encode([
            'status' => 'success',
            'message' => 'Payroll checklist feedback has been saved successfully.',
            'notification_sent' => false,
            'email_sent' => false,
            'feedback_summary' => $feedbackSummary
        ]);
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
}

function approvePayrollRequest(PDO $pdo, $conDB, string $currentUserId, int $requestTypeId): void
{
    $requestInvNo = trim((string)($_POST['request_inv_no'] ?? ''));
    $note = trim((string)($_POST['note'] ?? ''));

    if ($requestInvNo === '') {
        echo json_encode(['status' => 'error', 'message' => 'Missing request number']);
        return;
    }

    try {
        $chainManager = new ApprovalChainManager($conDB, $pdo);
        $currentChecklistRole = strtolower(trim((string)($GLOBALS['user_type'] ?? '')));
        $requiresChecklistReview = in_array($currentChecklistRole, ['hr_payroll', 'hr_senior_bp'], true);
        $requiresFinanceVerification = isHeadOfficeFinanceManagerRole();

        // Run DDL before opening the transaction to avoid implicit commits in MySQL.
        if ($requiresChecklistReview) {
            ensurePayrollChecklistReviewTable($pdo);
        }
        if ($requiresFinanceVerification) {
            ensurePayrollFinanceVerificationTable($pdo);
        }

        $pdo->beginTransaction();

        $verify = $chainManager->verifyApprover($requestInvNo, $currentUserId);
        if (empty($verify['authorized'])) {
            throw new Exception($verify['message'] ?? 'You are not authorized to approve this request');
        }

        $requestStmt = $pdo->prepare("SELECT payroll_month, requested_by FROM payroll_approval_requests WHERE request_inv_no = :inv_no LIMIT 1");
        $requestStmt->execute([':inv_no' => $requestInvNo]);
        $requestRow = $requestStmt->fetch(PDO::FETCH_ASSOC);

        if (!$requestRow) {
            throw new Exception('Payroll request not found');
        }

        if ($requiresChecklistReview) {
            $reviewSummary = getPayrollChecklistReviewSummary($pdo, $requestInvNo, (string)$requestRow['payroll_month'], $currentUserId);
            if ($reviewSummary['total_employees'] > 0 && $reviewSummary['checked_employees'] < $reviewSummary['total_employees']) {
                throw new Exception(
                    'Please mark all employee records in the Payroll Checklist Report as checked before approving. Remaining: '
                    . $reviewSummary['remaining_employees']
                    . ' of '
                    . $reviewSummary['total_employees']
                    . '.'
                );
            }
        }

        if ($requiresFinanceVerification) {
            $monthValue = (string)($requestRow['payroll_month'] ?? '');
            $verificationRow = getLatestFinanceVerificationRow($pdo, $requestInvNo, $monthValue, $currentUserId);
            if (empty($verificationRow) || empty($verificationRow['is_confirmed'])) {
                throw new Exception('Please complete Finance Verification setup (finance officer and companies confirmation) before approving.');
            }

            $assignedFinanceOfficerId = trim((string)($verificationRow['finance_officer_emp_id'] ?? ''));
            if ($assignedFinanceOfficerId === '') {
                throw new Exception('Assigned finance officer was not found for this verification setup.');
            }

            if (empty($verificationRow['officer_approved'])) {
                throw new Exception('Finance officer approval is still pending. Please ask finance officer to approve from Payroll Checklist page.');
            }
        }

        $result = $chainManager->processApproval($requestInvNo, $currentUserId, 'approve', $note);

        // Fetch payroll data for employee count and net salary
        $payrollStmt = $pdo->prepare("SELECT COUNT(emp_id) as employee_count, SUM(net_salary) as total_net_salary FROM payrolls WHERE month_year = :month");
        $payrollStmt->execute([':month' => $requestRow['payroll_month']]);
        $payrollData = $payrollStmt->fetch(PDO::FETCH_ASSOC);
        $employeeCount = (int)($payrollData['employee_count'] ?? 0);
        $totalNetSalary = (float)($payrollData['total_net_salary'] ?? 0);

        $newStatus = !empty($result['is_final']) ? 'approved' : 'pending_approval';
        
        if (!empty($result['is_final'])) {
            // Final approval - update status and mark as approved
            $update = $pdo->prepare("UPDATE payroll_approval_requests
                SET status = :status,
                    approved_by = :approver,
                    approved_at = NOW()
                WHERE request_inv_no = :inv_no");
            $update->execute([
                ':status' => $newStatus,
                ':approver' => $currentUserId,
                ':inv_no' => $requestInvNo
            ]);
        } else {
            // Not final approval - just update status
            $update = $pdo->prepare("UPDATE payroll_approval_requests
                SET status = :status
                WHERE request_inv_no = :inv_no");
            $update->execute([
                ':status' => $newStatus,
                ':inv_no' => $requestInvNo
            ]);
        }

        $statusLabel = !empty($result['is_final']) ? 'approved' : ('approved_level_' . (int)$verify['level']);
        $history = $pdo->prepare("INSERT INTO smt_request_status (inv_no, emp_id, emp_name, note, status)
            VALUES (:inv_no, :emp_id, :emp_name, :note, :status)");
        $history->execute([
            ':inv_no' => $requestInvNo,
            ':emp_id' => $currentUserId,
            ':emp_name' => 'System',
            ':note' => 'Payroll ' . $requestRow['payroll_month'] . ' approved. ' . ($note !== '' ? ('Comment: ' . $note) : ''),
            ':status' => $statusLabel
        ]);

        if ($pdo->inTransaction()) {
            $pdo->commit();
        }

        $emailSent = false;
        $notificationSent = false;

        // --- SEND NOTIFICATIONS TO NEXT APPROVER ---
        if (empty($result['is_final']) && !empty($result['next_approver'])) {
            $nextApprover = $result['next_approver'];
            
            // Send browser notification to next approver via chainManager
            if (function_exists('create_and_show_notification')) {
                create_and_show_notification(
                    $conDB,
                    $nextApprover['approver_id'],
                    'Payroll Requires Your Approval',
                    'Payroll ' . htmlspecialchars($requestRow['payroll_month']) . ' is awaiting your approval at level ' . (int)$nextApprover['approval_level'] . '.',
                    'all_payroll_approvals.php',
                    'info'
                );
                $notificationSent = true;
            }
            
            // Send email notification to next approver
            if (!empty($nextApprover['email']) && function_exists('send_approval_email')) {
                $emailSubject = 'Payroll Approval Required - ' . $requestRow['payroll_month'] . ' (' . $requestInvNo . ')';
                $templateData = [
                    'APPROVER_NAME' => !empty($nextApprover['name']) ? htmlspecialchars($nextApprover['name']) : 'Approver',
                    'REQUEST_ID' => $requestInvNo,
                    'REQUEST_TYPE' => 'Payroll Approval',
                    'EMAIL_MESSAGE' => 'A payroll request is awaiting your approval at level ' . (int)$nextApprover['approval_level'] . '. Please review and take appropriate action.',
                    'PAYROLL_MONTH' => $requestRow['payroll_month'],
                    'EMPLOYEE_COUNT' => (string)$employeeCount,
                    'TOTAL_NET_SALARY' => number_format($totalNetSalary, 2),
                    'REQUEST_URL' => (function_exists('get_base_url') ? get_base_url() : 'https://hr.almutlaksystem.com') . '/all_payroll_approvals.php?status=my_pending'
                ];
                
                $emailSent = (bool)send_approval_email($conDB, $nextApprover['email'], $nextApprover['name'], $emailSubject, 'payroll_request', $templateData);
            }
        }

        if (!empty($result['is_final'])) {
            // --- NOTIFY REQUESTER (PAYROLL GENERATOR) ON FINAL APPROVAL ---
            $requesterId = (string)($requestRow['requested_by'] ?? '');
            if ($requesterId !== '') {
                $requesterQuery = "SELECT e.name, e.emp_id, al.email FROM employees e 
                                  LEFT JOIN admin_login al ON al.emp_id = e.emp_id
                                  WHERE e.emp_id = ? LIMIT 1";
                $requesterStmt = $conDB->prepare($requesterQuery);
                if ($requesterStmt) {
                    $requesterStmt->bind_param('i', $requesterId);
                    $requesterStmt->execute();
                    $requesterResult = $requesterStmt->get_result();
                    $requester = $requesterResult ? $requesterResult->fetch_assoc() : null;
                    if ($requesterResult) { $requesterResult->free(); }
                    $requesterStmt->close();

                    if (!empty($requester)) {
                        if (function_exists('create_browser_notification')) {
                            create_browser_notification(
                                $conDB,
                                (int)$requesterId,
                                'Payroll Fully Approved',
                                'Payroll ' . htmlspecialchars($requestRow['payroll_month']) . ' has been fully approved. Bank file is now ready to download and upload for transfer.',
                                'all_payroll_approvals.php'
                            );
                            $notificationSent = true;
                        }

                        if (!empty($requester['email']) && function_exists('send_approval_email')) {
                            $emailSubject = 'Payroll Fully Approved - ' . $requestRow['payroll_month'] . ' (' . $requestInvNo . ')';
                            $templateData = [
                                'APPROVER_NAME' => !empty($requester['name']) ? htmlspecialchars($requester['name']) : 'Requester',
                                'REQUEST_ID' => $requestInvNo,
                                'REQUEST_TYPE' => 'Payroll Approval',
                                'EMAIL_MESSAGE' => 'Your payroll request has been fully approved by all approvers. You can now download the bank file and upload it to the bank for final transfer.',
                                'PAYROLL_MONTH' => $requestRow['payroll_month'],
                                'EMPLOYEE_COUNT' => (string)$employeeCount,
                                'TOTAL_NET_SALARY' => number_format($totalNetSalary, 2),
                                'REQUEST_URL' => (function_exists('get_base_url') ? get_base_url() : 'https://hr.almutlaksystem.com') . '/all_payroll_approvals.php'
                            ];
                            $sent = (bool)send_approval_email($conDB, $requester['email'], $requester['name'], $emailSubject, 'payroll_request', $templateData);
                            $emailSent = $emailSent || $sent;
                        }
                    }
                }
            }
        }

        $responseMessage = !empty($result['is_final'])
            ? 'Payroll approval is fully completed. Payroll generator has been notified and bank file is now available for download.'
            : 'Payroll approval recorded and forwarded to next approver.';

        if ($emailSent) {
            $responseMessage .= ' Email notification sent successfully.';
        } elseif ($notificationSent) {
            $responseMessage .= ' Browser notification sent successfully.';
        } else {
            $responseMessage .= ' No email notification was sent.';
        }

        echo json_encode([
            'status' => 'success',
            'message' => $responseMessage,
            'is_final' => !empty($result['is_final']),
            'next_approver' => $result['next_approver'] ?? null,
            'email_sent' => $emailSent,
            'notification_sent' => $notificationSent
        ]);
        return;

    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
}

function rejectPayrollRequest(PDO $pdo, $conDB, string $currentUserId, int $requestTypeId): void
{
    $requestInvNo = trim((string)($_POST['request_inv_no'] ?? ''));
    $note = trim((string)($_POST['note'] ?? ''));

    if ($requestInvNo === '') {
        echo json_encode(['status' => 'error', 'message' => 'Missing request number']);
        return;
    }

    if ($note === '') {
        $note = 'Rejected';
    }

    try {
        $chainManager = new ApprovalChainManager($conDB, $pdo);

        $pdo->beginTransaction();

        $verify = $chainManager->verifyApprover($requestInvNo, $currentUserId);
        if (empty($verify['authorized'])) {
            throw new Exception($verify['message'] ?? 'You are not authorized to reject this request');
        }

        $chainManager->processApproval($requestInvNo, $currentUserId, 'reject', $note);

        $requestStmt = $pdo->prepare("SELECT payroll_month, requested_by FROM payroll_approval_requests WHERE request_inv_no = :inv_no LIMIT 1");
        $requestStmt->execute([':inv_no' => $requestInvNo]);
        $requestRow = $requestStmt->fetch(PDO::FETCH_ASSOC);

        if (!$requestRow) {
            throw new Exception('Payroll request not found');
        }

        // Fetch payroll data for employee count and net salary
        $payrollStmt = $pdo->prepare("SELECT COUNT(emp_id) as employee_count, SUM(net_salary) as total_net_salary FROM payrolls WHERE month_year = :month");
        $payrollStmt->execute([':month' => $requestRow['payroll_month']]);
        $payrollData = $payrollStmt->fetch(PDO::FETCH_ASSOC);
        $employeeCount = (int)($payrollData['employee_count'] ?? 0);
        $totalNetSalary = (float)($payrollData['total_net_salary'] ?? 0);

        $update = $pdo->prepare("UPDATE payroll_approval_requests
            SET status = 'rejected', approved_by = :approver
            WHERE request_inv_no = :inv_no");
        $update->execute([
            ':approver' => $currentUserId,
            ':inv_no' => $requestInvNo
        ]);

        $history = $pdo->prepare("INSERT INTO smt_request_status (inv_no, emp_id, emp_name, note, status)
            VALUES (:inv_no, :emp_id, :emp_name, :note, 'rejected')");
        $history->execute([
            ':inv_no' => $requestInvNo,
            ':emp_id' => $currentUserId,
            ':emp_name' => 'System',
            ':note' => 'Payroll ' . $requestRow['payroll_month'] . ' rejected. Reason: ' . $note
        ]);

        if ($pdo->inTransaction()) {
            $pdo->commit();
        }

        // --- SEND NOTIFICATIONS TO REQUESTER ---
        $requesterId = (string)($requestRow['requested_by'] ?? '');
        $emailSent = false;
        $notificationSent = false;
        
        if ($requesterId !== '' && !$emailSent) {
            // Fetch requester details for notifications
            $requesterQuery = "SELECT e.name, e.emp_id, al.email FROM employees e 
                              LEFT JOIN admin_login al ON al.emp_id = e.emp_id
                              WHERE e.emp_id = ? LIMIT 1";
            $requesterStmt = $conDB->prepare($requesterQuery);
            if ($requesterStmt) {
                $requesterStmt->bind_param('i', $requesterId);
                $requesterStmt->execute();
                $requesterResult = $requesterStmt->get_result();
                $requester = $requesterResult ? $requesterResult->fetch_assoc() : null;
                if ($requesterResult) {
                    $requesterResult->free();
                }
                $requesterStmt->close();
                
                if (!empty($requester)) {
                    // Send browser notification to requester
                    if (function_exists('create_browser_notification')) {
                        $notifTitle = 'Payroll Approval Rejected';
                        $notifMessage = 'Payroll ' . htmlspecialchars($requestRow['payroll_month']) . ' has been rejected. Please review the reason and resubmit if necessary.';
                        $notifUrl = 'all_payroll_approvals.php';
                        create_browser_notification($conDB, (int)$requesterId, $notifTitle, $notifMessage, $notifUrl);
                        $notificationSent = true;
                    }
                    
                    // Send email notification to requester with rejection reason ONLY ONCE
                    if (!empty($requester['email']) && function_exists('send_approval_email')) {
                        $emailSubject = 'Payroll Rejected - ' . $requestRow['payroll_month'] . ' (' . $requestInvNo . ')';
                        
                        $templateData = [
                            'APPROVER_NAME' => !empty($requester['name']) ? htmlspecialchars($requester['name']) : 'Requester',
                            'REQUEST_ID' => $requestInvNo,
                            'REQUEST_TYPE' => 'Payroll Approval',
                            'EMAIL_MESSAGE' => 'Unfortunately, your payroll request has been rejected.',
                            'PAYROLL_MONTH' => $requestRow['payroll_month'],
                            'EMPLOYEE_COUNT' => (string)$employeeCount,
                            'TOTAL_NET_SALARY' => number_format($totalNetSalary, 2),
                            'REJECTION_REASON' => $note,
                            'REJECTED_BY' => 'Approver',
                            'REQUEST_URL' => (function_exists('get_base_url') ? get_base_url() : 'https://hr.almutlaksystem.com') . '/all_payroll_approvals.php'
                        ];
                        
                        $emailSent = (bool)send_approval_email($conDB, $requester['email'], $requester['name'], $emailSubject, 'payroll_request', $templateData);
                    }
                }
            }
        }

        $responseMessage = 'Payroll approval request rejected successfully.';
        if ($emailSent) {
            $responseMessage .= ' Email notification sent successfully.';
        } elseif ($notificationSent) {
            $responseMessage .= ' Browser notification sent successfully.';
        } else {
            $responseMessage .= ' No email notification was sent.';
        }

        echo json_encode([
            'status' => 'success',
            'message' => $responseMessage,
            'email_sent' => $emailSent,
            'notification_sent' => $notificationSent
        ]);
        return;

    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
}
