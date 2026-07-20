<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/session_check.php';
require_once __DIR__ . '/includes/helper_functions.php';
require_once __DIR__ . '/includes/payroll_approval_helpers.php';

if (file_exists(__DIR__ . '/includes/functions.php')) {
    require_once __DIR__ . '/includes/functions.php';
}

$current_lang = $_SESSION['lang'] ?? 'en';
if (function_exists('load_language')) {
    load_language($current_lang);
}

if (isset($isEmployee) && $isEmployee === true) {
    header('Location: ./profile.php');
    exit();
}

$monthYear = trim((string)($_GET['month'] ?? ''));
$requestInvNo = trim((string)($_GET['request_inv_no'] ?? $_GET['inv_no'] ?? ''));
$selectedCompany = trim((string)($_GET['company'] ?? ''));
$selectedDepartment = trim((string)($_GET['department'] ?? ''));
$selectedSponsor = trim((string)($_GET['sponsor'] ?? ''));
$selectedFeedbackStatus = trim((string)($_GET['feedback_status'] ?? ''));
$selectedPaymentTypes = [];
$rawPaymentTypeFilter = $_GET['payment_type'] ?? [];
if (!is_array($rawPaymentTypeFilter)) {
    $rawPaymentTypeFilter = [$rawPaymentTypeFilter];
}
foreach ($rawPaymentTypeFilter as $rawPaymentTypeValue) {
    $candidates = explode(',', (string)$rawPaymentTypeValue);
    foreach ($candidates as $candidate) {
        $paymentType = (int)trim((string)$candidate);
        if (in_array($paymentType, [1, 2, 3], true)) {
            $selectedPaymentTypes[$paymentType] = $paymentType;
        }
    }
}
$hasPaymentTypeInRequest = array_key_exists('payment_type', $_GET);
if (!$hasPaymentTypeInRequest && empty($selectedPaymentTypes)) {
    // Default checklist view to Bank employees only unless user chooses other payment types.
    $selectedPaymentTypes[1] = 1;
}
$selectedPaymentTypes = array_values($selectedPaymentTypes);
$rawAssignedScopeParam = trim((string)($_GET['assigned_scope'] ?? '0'));
$assignedScopeOnly = ((int)$rawAssignedScopeParam === 1);

if ($monthYear === '' || !preg_match('/^\d{4}-\d{2}$/', $monthYear)) {
    die('<div style="padding:16px;margin:16px;border:1px solid #f5c2c7;background:#f8d7da;color:#842029;border-radius:8px;">ERROR: Valid payroll month not provided.</div>');
}

$pdo = getDbConnection();
ensurePayrollApprovalTable($pdo);
ensurePayrollChecklistSupportTables($pdo);
$requestTypeId = ensurePayrollApprovalRequestType($pdo);

function payrollTableHasColumn(PDO $pdo, string $tableName, string $columnName): bool
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

function getPayrollModifiedEmployeesForChecklist(PDO $pdo, string $monthValue, string $requestCreatedAt): array
{
    if ($monthValue === '' || $requestCreatedAt === '') {
        return [];
    }

    $modifiedEmployees = [];

    $benefitHasUpdatedAt = payrollTableHasColumn($pdo, 'payroll_benefits', 'updated_at');
    $benefitHasCreatedAt = payrollTableHasColumn($pdo, 'payroll_benefits', 'created_at');
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

    $deductionHasUpdatedAt = payrollTableHasColumn($pdo, 'payroll_deductions', 'updated_at');
    $deductionHasCreatedAt = payrollTableHasColumn($pdo, 'payroll_deductions', 'created_at');
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

if ($requestInvNo === '') {
    $lookupStmt = $pdo->prepare("SELECT request_inv_no FROM payroll_approval_requests WHERE payroll_month = :month_year ORDER BY id DESC LIMIT 1");
    $lookupStmt->execute([':month_year' => $monthYear]);
    $requestInvNo = (string)($lookupStmt->fetchColumn() ?: '');
}

$currentApproverId = (string)($empid ?? $_SESSION['empid'] ?? '');
$requestStatus = '';
$requestRequestedBy = '';
$requestGeneratorName = '';
$requestCreatedAt = '';
if ($requestInvNo !== '') {
    $requestMetaStmt = $pdo->prepare("SELECT status, requested_by, created_at FROM payroll_approval_requests WHERE request_inv_no = :inv_no LIMIT 1");
    $requestMetaStmt->execute([':inv_no' => $requestInvNo]);
    $requestMetaRow = $requestMetaStmt->fetch(PDO::FETCH_ASSOC) ?: [];
    $requestStatus = strtolower(trim((string)($requestMetaRow['status'] ?? '')));
    $requestRequestedBy = (string)($requestMetaRow['requested_by'] ?? '');
    $requestCreatedAt = trim((string)($requestMetaRow['created_at'] ?? ''));

    if ($requestRequestedBy !== '') {
        $requestGeneratorStmt = $pdo->prepare("SELECT name FROM employees WHERE id = :requester_id OR emp_id = :requester_emp_id LIMIT 1");
        $requestGeneratorStmt->execute([
            ':requester_id' => (int)$requestRequestedBy,
            ':requester_emp_id' => $requestRequestedBy
        ]);
        $requestGeneratorName = (string)($requestGeneratorStmt->fetchColumn() ?: '');
    }
}
$isPendingWithMe = false;
if ($requestInvNo !== '') {
    $pendingStmt = $pdo->prepare("SELECT COUNT(*) FROM request_approvers WHERE request_inv_no = :inv_no AND request_type_id = :type_id AND approver_id = :approver_id AND status = 'pending'");
    $pendingStmt->execute([
        ':inv_no' => $requestInvNo,
        ':type_id' => $requestTypeId,
        ':approver_id' => $currentApproverId
    ]);
    $isPendingWithMe = ((int)$pendingStmt->fetchColumn() > 0);
}

$currentUserType = strtolower(trim((string)($user_type ?? '')));
$currentEmpType = trim((string)($emp_type ?? ''));
$isCompanyManagerChecklistUser = strtolower($currentEmpType) === 'manager' && $currentUserType !== 'employee';
$isHrPayrollApprover = ($currentUserType === 'hr_payroll');
$isHrSeniorBpApprover = ($currentUserType === 'hr_senior_bp');
$isHrChecklistApprover = $isHrPayrollApprover || $isHrSeniorBpApprover;
$isFinanceOfficerChecklistUser = ($currentUserType === 'finance_officer')
    || ($currentUserType === 'finance' && strcasecmp($currentEmpType, 'Manager') !== 0 && (int)($user_dept ?? 0) === 2);
$isFinanceManagerChecklistUser = ($currentUserType === 'finance'
    && strcasecmp($currentEmpType, 'Manager') === 0
    && (int)($user_dept ?? 0) === 2);

// Finance Manager with assigned scope: user_type=finance, emp_type=Manager, dept=2
// The toggle visibility is controlled by whether they have assigned companies to manage.
// Will be finalized after checking assigned scope.

$assignedFinanceCompanyIds = [];
$assignedScopeRaw = null;
if ($requestInvNo !== '' && $currentApproverId !== '') {
    // For Finance Manager in assigned_scope mode, prefer manager's own direct assignment only.
    if ($isFinanceManagerChecklistUser && $assignedScopeOnly) {
        $managerOwnScopeStmt = $pdo->prepare("SELECT selected_company_ids
            FROM payroll_finance_officer_company_defaults
            WHERE finance_manager_emp_id = :manager_id
              AND finance_officer_emp_id = :officer_id
            ORDER BY id DESC
            LIMIT 1");
        $managerOwnScopeStmt->execute([
            ':manager_id' => $currentApproverId,
            ':officer_id' => $currentApproverId
        ]);
        $assignedScopeRaw = $managerOwnScopeStmt->fetchColumn();

        // Fallback: check latest history row where manager assigned to himself.
        if ($assignedScopeRaw === false || $assignedScopeRaw === null || trim((string)$assignedScopeRaw) === '') {
            $managerOwnHistoryStmt = $pdo->prepare("SELECT selected_company_ids
                FROM payroll_finance_verification_history
                WHERE request_inv_no = :inv_no
                  AND payroll_month = :month_year
                  AND finance_manager_emp_id = :manager_id
                  AND finance_officer_emp_id = :officer_id
                  AND action_type IN ('setup_assigned', 'setup_updated')
                ORDER BY id DESC
                LIMIT 1");
            $managerOwnHistoryStmt->execute([
                ':inv_no' => $requestInvNo,
                ':month_year' => $monthYear,
                ':manager_id' => $currentApproverId,
                ':officer_id' => $currentApproverId
            ]);
            $assignedScopeRaw = $managerOwnHistoryStmt->fetchColumn();
        }
    }

    // Always use latest assignment from verification table (most authoritative source).
    if ($assignedScopeRaw === false || $assignedScopeRaw === null || trim((string)$assignedScopeRaw) === '') {
        // Check manager assignment first (confirmed only).
        $assignedScopeManagerStmt = $pdo->prepare("SELECT selected_company_ids
            FROM payroll_finance_verification
            WHERE request_inv_no = :inv_no
              AND payroll_month = :month_year
              AND finance_manager_emp_id = :finance_user
              AND is_confirmed = 1
            ORDER BY id DESC
            LIMIT 1");
        $assignedScopeManagerStmt->execute([
            ':inv_no' => $requestInvNo,
            ':month_year' => $monthYear,
            ':finance_user' => $currentApproverId
        ]);
        $assignedScopeRaw = $assignedScopeManagerStmt->fetchColumn();
    }

    // If not found as manager (confirmed), try unconfirmed manager assignment.
    if ($assignedScopeRaw === false || $assignedScopeRaw === null || trim((string)$assignedScopeRaw) === '') {
        $assignedScopeManagerUnconfirmedStmt = $pdo->prepare("SELECT selected_company_ids
            FROM payroll_finance_verification
            WHERE request_inv_no = :inv_no
              AND payroll_month = :month_year
              AND finance_manager_emp_id = :finance_user
            ORDER BY id DESC
            LIMIT 1");
        $assignedScopeManagerUnconfirmedStmt->execute([
            ':inv_no' => $requestInvNo,
            ':month_year' => $monthYear,
            ':finance_user' => $currentApproverId
        ]);
        $unconfirmedResult = $assignedScopeManagerUnconfirmedStmt->fetchColumn();
        if ($unconfirmedResult !== false && $unconfirmedResult !== null && trim((string)$unconfirmedResult) !== '') {
            $assignedScopeRaw = $unconfirmedResult;
        }
    }

    // If not found as manager, check officer assignment (confirmed).
    if ($assignedScopeRaw === false || $assignedScopeRaw === null || trim((string)$assignedScopeRaw) === '') {
        $assignedScopeOfficerStmt = $pdo->prepare("SELECT selected_company_ids
            FROM payroll_finance_verification
            WHERE request_inv_no = :inv_no
              AND payroll_month = :month_year
              AND finance_officer_emp_id = :finance_user
              AND is_confirmed = 1
            ORDER BY id DESC
            LIMIT 1");
        $assignedScopeOfficerStmt->execute([
            ':inv_no' => $requestInvNo,
            ':month_year' => $monthYear,
            ':finance_user' => $currentApproverId
        ]);
        $assignedScopeRaw = $assignedScopeOfficerStmt->fetchColumn();
    }

    // If not found as officer (confirmed), try unconfirmed officer assignment.
    if ($assignedScopeRaw === false || $assignedScopeRaw === null || trim((string)$assignedScopeRaw) === '') {
        $assignedScopeOfficerUnconfirmedStmt = $pdo->prepare("SELECT selected_company_ids
            FROM payroll_finance_verification
            WHERE request_inv_no = :inv_no
              AND payroll_month = :month_year
              AND finance_officer_emp_id = :finance_user
            ORDER BY id DESC
            LIMIT 1");
        $assignedScopeOfficerUnconfirmedStmt->execute([
            ':inv_no' => $requestInvNo,
            ':month_year' => $monthYear,
            ':finance_user' => $currentApproverId
        ]);
        $unconfirmedResult = $assignedScopeOfficerUnconfirmedStmt->fetchColumn();
        if ($unconfirmedResult !== false && $unconfirmedResult !== null && trim((string)$unconfirmedResult) !== '') {
            $assignedScopeRaw = $unconfirmedResult;
        }
    }

    $assignedScopeDecoded = json_decode((string)($assignedScopeRaw ?: '[]'), true);
    if (is_array($assignedScopeDecoded)) {
        $assignedFinanceCompanyIds = array_values(array_unique(array_filter(array_map(static function ($value) {
            return trim((string)$value);
        }, $assignedScopeDecoded), static function ($value) {
            return $value !== '';
        })));
    }
}

$isAssignedFinanceCompanyVerifier = !empty($assignedFinanceCompanyIds);

// Finance Manager gets toggle button if they have assigned companies to manage
if ($isFinanceManagerChecklistUser && $isAssignedFinanceCompanyVerifier) {
    $isMainFinanceManager = true;
} else {
    $isMainFinanceManager = false;
}

// assigned_scope toggle/filter is only allowed for Main Finance Manager.
if (!$isMainFinanceManager && $assignedScopeOnly) {
    $assignedScopeOnly = false;
}

// Allow Finance Managers to access even without company assignment
if (
    $isFinanceOfficerChecklistUser &&
    !$isAssignedFinanceCompanyVerifier
) {
    die('<div style="padding:16px;margin:16px;border:1px solid #f5c2c7;background:#f8d7da;color:#842029;border-radius:8px;">
        ERROR: You are not assigned to verify this payroll checklist request.
    </div>');
}

// Finance assignment review should default to full company scope (all departments/sponsors).
if (($isFinanceOfficerChecklistUser || $isFinanceManagerChecklistUser) && $isAssignedFinanceCompanyVerifier) {
    $selectedDepartment = '';
    $selectedSponsor = '';
}

// Finance users ALWAYS apply company filter (regardless of toggle state).
$applyAssignedScopeFilter = $isAssignedFinanceCompanyVerifier;

$canExportChecklistExcel = $isHrPayrollApprover
    || $isHrSeniorBpApprover
    || ($is_system_admin ?? false)
    || (($user_type ?? '') === 'administrator');
$canManageFeedbackActions = $isHrPayrollApprover;
// Upload button shows for: a manager-flagged account, a Direct Supervisor who
// actually received the "Send Payroll Report by Direct Supervisor" email for this
// request (covers supervisors whose admin account isn't flagged emp_type = 'Manager'),
// or HR Payroll / HR Senior BP themselves - who can upload on behalf of any manager
// (e.g. the manager sent the filled file back some other way).
$canUploadManagerPayrollExcel = $requestInvNo !== ''
    && $currentUserType !== 'employee'
    && ($isCompanyManagerChecklistUser
        || $isHrChecklistApprover
        || payrollSupervisorHasReportAccess($pdo, $requestInvNo, $monthYear, $currentApproverId));
$canReviewManagerUploadedPayrollExcel = $requestInvNo !== '' && $isHrChecklistApprover;
// HR Payroll / HR Senior BP can mark employees checked both during normal approval
// (isPendingWithMe) and after final approval for follow-up feedback review.
$canManageChecklistReview = $requestInvNo !== ''
    && $isHrChecklistApprover
    && ($isPendingWithMe || $requestStatus === 'approved');
$canSendFeedbackFollowup = $requestInvNo !== '' && $canManageFeedbackActions;
$employeesRequiringChecklistReviewByEmp = ($canManageChecklistReview && $requestCreatedAt !== '')
    ? getPayrollModifiedEmployeesForChecklist($pdo, $monthYear, $requestCreatedAt)
    : [];

$isFinanceChecklistUser = ($isFinanceOfficerChecklistUser || $isFinanceManagerChecklistUser) && $isAssignedFinanceCompanyVerifier;

$isApproverInApprovalChain = false;
if ($requestInvNo !== '' && $currentApproverId !== '') {
    $chainApproverStmt = $pdo->prepare("SELECT COUNT(*)
        FROM request_approvers
        WHERE request_inv_no = :inv_no
          AND request_type_id = :type_id
          AND approver_id = :approver_id");
    $chainApproverStmt->execute([
        ':inv_no' => $requestInvNo,
        ':type_id' => $requestTypeId,
        ':approver_id' => $currentApproverId
    ]);
    $isApproverInApprovalChain = ((int)$chainApproverStmt->fetchColumn() > 0);
}

// Finance users ONLY follow their assigned company scope, not user permission restrictions.
if ($isFinanceChecklistUser) {
    $canSeeAllEmployees = true;
    $deptFilter = '';
} else {
    // Non-finance approvers in the approval chain should always see all payroll employees.
    if ($isApproverInApprovalChain) {
        $canSeeAllEmployees = true;
    } elseif ($isHrChecklistApprover) {
        // HR Payroll / HR Senior BP always see the full employee list on this page.
        $canSeeAllEmployees = true;
    } elseif ($isFinanceManagerChecklistUser) {
        // Finance Manager (dept 2, emp_type Manager) always sees the full employee list here,
        // even without an active company assignment for this request.
        $canSeeAllEmployees = true;
    } else {
        // Intentionally narrow: full visibility on this page is limited to System Admin,
        // Finance Manager, HR Payroll and HR Senior BP (handled above). The broader
        // canSeeAllEmployeesByRole()/canSeeAllPayrollEmployees() helpers also grant full
        // access to dept 1 (Administration), dept 5 (any HR sub-role), GM, etc., which is
        // too wide for a Direct Supervisor opening this report from the emailed link.
        $canSeeAllEmployees = ($is_system_admin ?? false)
            || (($user_type ?? '') === 'administrator');
    }

    // Anyone left without full access (e.g. a Direct Supervisor opening the report from
    // the emailed link) may only see the employees who report directly to them - not the
    // whole department.
    $deptFilter = '';
    if (!$canSeeAllEmployees) {
        $deptFilter = ' AND e.supervisor_id = :user_supervisor_id';
    }
}

$params = [':month_year_param' => $monthYear];
if ($deptFilter !== '') {
    $params[':user_supervisor_id'] = $currentApproverId;
}

$companyFilter = '';
$departmentFilter = '';
$sponsorFilter = '';
$paymentTypeFilter = '';

// Finance Manager: 
// - Toggle OFF (assignedScopeOnly=false): Show ALL generated employees (no filter)
// - Toggle ON (assignedScopeOnly=true): Show ONLY assigned companies
if ($isFinanceChecklistUser) {
    $selectedDepartment = '';
    $selectedSponsor = '';

    // Apply assigned company filter ONLY when toggle is ON
    if ($assignedScopeOnly && !empty($assignedFinanceCompanyIds)) {
        $companyPlaceholders = [];
        foreach ($assignedFinanceCompanyIds as $index => $companyId) {
            $paramKey = ':assigned_company_' . $index;
            $companyPlaceholders[] = $paramKey;
            $params[$paramKey] = $companyId;
        }

        if (!empty($companyPlaceholders)) {
            $companyFilter = ' AND e.comp_no IN (' . implode(', ', $companyPlaceholders) . ')';
        }

        if ($selectedCompany !== '' && in_array($selectedCompany, $assignedFinanceCompanyIds, true)) {
            $companyFilter .= ' AND e.comp_no = :filter_company';
            $params[':filter_company'] = $selectedCompany;
        }
    }
    // When toggle OFF: show all, no company filter
} else {
    if ($selectedCompany !== '') {
        $companyFilter = ' AND e.comp_no = :filter_company';
        $params[':filter_company'] = $selectedCompany;
    }
    
    if ($selectedDepartment !== '') {
        $departmentFilter = ' AND e.dept = :filter_department';
        $params[':filter_department'] = $selectedDepartment;
    }
    
    if ($selectedSponsor !== '') {
        $sponsorFilter = ' AND e.emp_sup_type = :filter_sponsor';
        $params[':filter_sponsor'] = $selectedSponsor;
    }
}

if (!empty($selectedPaymentTypes)) {
    $paymentTypePlaceholders = [];
    foreach ($selectedPaymentTypes as $index => $paymentType) {
        $paramKey = ':filter_payment_type_' . $index;
        $paymentTypePlaceholders[] = $paramKey;
        $params[$paramKey] = (int)$paymentType;
    }

    if (!empty($paymentTypePlaceholders)) {
        $paymentTypeFilter = ' AND COALESCE(e.payment_type, 1) IN (' . implode(', ', $paymentTypePlaceholders) . ')';
    }
}

$companies = [];
if ($applyAssignedScopeFilter && !empty($assignedFinanceCompanyIds)) {
    $companyListPlaceholders = [];
    $companyListParams = [];
    foreach ($assignedFinanceCompanyIds as $index => $companyId) {
        $paramKey = ':scope_company_' . $index;
        $companyListPlaceholders[] = $paramKey;
        $companyListParams[$paramKey] = $companyId;
    }
    $companyListStmt = $pdo->prepare('SELECT comp_id, comp_name FROM companies WHERE comp_id IN (' . implode(', ', $companyListPlaceholders) . ') ORDER BY comp_name ASC');
    $companyListStmt->execute($companyListParams);
    $companies = $companyListStmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    $companies = $pdo->query("SELECT comp_id, comp_name FROM companies ORDER BY comp_name ASC")->fetchAll(PDO::FETCH_ASSOC);
}
$departments = $pdo->query("SELECT id, dep_nme, dep_nme_ar FROM department ORDER BY dep_nme ASC")->fetchAll(PDO::FETCH_ASSOC);
$sponsors = $pdo->query("SELECT id, sponsor FROM sponsorship ORDER BY sponsor ASC")->fetchAll(PDO::FETCH_ASSOC);

$assignedScopeToggleUrl = '';
if ($isMainFinanceManager) {
    $toggleParams = [
        'month' => $monthYear
    ];
    if ($requestInvNo !== '') {
        $toggleParams['request_inv_no'] = $requestInvNo;
    }
    if ($selectedCompany !== '') {
        $toggleParams['company'] = $selectedCompany;
    }
    if ($selectedDepartment !== '') {
        $toggleParams['department'] = $selectedDepartment;
    }
    if ($selectedSponsor !== '') {
        $toggleParams['sponsor'] = $selectedSponsor;
    }
    if ($selectedFeedbackStatus !== '') {
        $toggleParams['feedback_status'] = $selectedFeedbackStatus;
    }
    if (!empty($selectedPaymentTypes)) {
        $toggleParams['payment_type'] = $selectedPaymentTypes;
    }
    $toggleParams['assigned_scope'] = $assignedScopeOnly ? '0' : '1';
    $assignedScopeToggleUrl = 'payroll_checklist_report.php?' . http_build_query($toggleParams);
}

// Always use payroll-only query (JOIN, not LEFT JOIN) to exclude not_generated employees.
$sql = "SELECT
        gp.id AS payroll_id,
        gp.emp_id,
        e.iqama,
        e.name AS employee_name,
        e.iban,
        e.payment_type,
        e.dept,
        d.dep_nme AS department_name,
        d.dep_nme_ar AS department_name_ar,
        c.comp_name,
        bl.bank_name_s,
        s.sponsor,
        gp.month_year,
        gp.basic_salary,
        gp.housing_allowance,
        gp.transport_allowance,
        gp.food_allowance,
        gp.miscellaneous_allowance,
        gp.cashier_allowance,
        gp.fuel_allowance,
        gp.telephone_allowance,
        gp.other_allowance,
        gp.guard_allowance,
        gp.total_gross_salary,
        gp.total_benefits,
        gp.total_deductions,
        gp.net_salary,
        gp.status
    FROM payrolls gp
    JOIN employees e ON gp.emp_id = e.emp_id
    LEFT JOIN department d ON e.dept = d.id
    LEFT JOIN companies c ON e.comp_no = c.comp_id
    LEFT JOIN bank_list bl ON bl.bnk_id = e.bank_name
    LEFT JOIN sponsorship s ON e.emp_sup_type = s.id
    WHERE gp.month_year = :month_year_param" . $deptFilter . $companyFilter . $departmentFilter . $sponsorFilter . $paymentTypeFilter . "
    ORDER BY c.comp_name ASC, d.dep_nme ASC, e.name ASC";

$stmt = $pdo->prepare($sql);
foreach ($params as $key => $value) {
    $stmt->bindValue($key, $value);
}
$stmt->execute();
$employees = $stmt->fetchAll(PDO::FETCH_ASSOC);

$feedbackStatusByEmp = [];
$feedbackTotals = [
    'total_count' => 0,
    'open_count' => 0,
    'resolved_count' => 0,
    'employee_count' => 0,
    'pending_followup_count' => 0
];
if ($monthYear !== '') {
    $feedbackSummarySql = "SELECT
            emp_id,
            COUNT(*) AS total_count,
            SUM(CASE WHEN status = 'open' THEN 1 ELSE 0 END) AS open_count,
            SUM(CASE WHEN status = 'resolved' THEN 1 ELSE 0 END) AS resolved_count,
            SUM(CASE WHEN status = 'open' AND followup_sent_at IS NULL THEN 1 ELSE 0 END) AS pending_followup_count
        FROM payroll_checklist_feedback
        WHERE payroll_month = :month_year";
    $feedbackSummaryParams = [':month_year' => $monthYear];

    if ($requestInvNo !== '') {
        $feedbackSummarySql .= " AND request_inv_no = :request_inv_no";
        $feedbackSummaryParams[':request_inv_no'] = $requestInvNo;
    }

    $feedbackSummarySql .= " GROUP BY emp_id";
    $feedbackSummaryStmt = $pdo->prepare($feedbackSummarySql);
    $feedbackSummaryStmt->execute($feedbackSummaryParams);
    $feedbackRows = $feedbackSummaryStmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($feedbackRows as $feedbackRow) {
        $totalCount = (int)($feedbackRow['total_count'] ?? 0);
        $openCount = (int)($feedbackRow['open_count'] ?? 0);
        $resolvedCount = (int)($feedbackRow['resolved_count'] ?? 0);
        $pendingFollowupCount = (int)($feedbackRow['pending_followup_count'] ?? 0);

        $feedbackStatusByEmp[(string)$feedbackRow['emp_id']] = [
            'total_count' => $totalCount,
            'open_count' => $openCount,
            'resolved_count' => $resolvedCount,
            'pending_followup_count' => $pendingFollowupCount
        ];

        $feedbackTotals['total_count'] += $totalCount;
        $feedbackTotals['open_count'] += $openCount;
        $feedbackTotals['resolved_count'] += $resolvedCount;
        $feedbackTotals['pending_followup_count'] += $pendingFollowupCount;
        $feedbackTotals['employee_count']++;
    }
}

$showFeedbackFollowupButton = $canSendFeedbackFollowup && $feedbackTotals['pending_followup_count'] > 0;

$financeOfficerVerificationRow = [];
if ($isAssignedFinanceCompanyVerifier && $requestInvNo !== '') {
    $financeOfficerVerificationStmt = $pdo->prepare("SELECT id, officer_approved, officer_approved_at
        FROM payroll_finance_verification
        WHERE request_inv_no = :inv_no
          AND payroll_month = :month_year
          AND finance_officer_emp_id = :finance_officer
          AND is_confirmed = 1
        ORDER BY id DESC
        LIMIT 1");
    $financeOfficerVerificationStmt->execute([
        ':inv_no' => $requestInvNo,
        ':month_year' => $monthYear,
        ':finance_officer' => $currentApproverId
    ]);
    $financeOfficerVerificationRow = $financeOfficerVerificationStmt->fetch(PDO::FETCH_ASSOC) ?: [];
}

$employeeReviewStatusByEmp = [];
if ($canManageChecklistReview && $currentApproverId !== '') {
    $reviewStmt = $pdo->prepare("SELECT emp_id, is_checked, checked_at
        FROM payroll_checklist_employee_checks
        WHERE request_inv_no = :request_inv_no
          AND payroll_month = :month_year
          AND approver_id = :approver_id");
    $reviewStmt->execute([
        ':request_inv_no' => $requestInvNo,
        ':month_year' => $monthYear,
        ':approver_id' => $currentApproverId
    ]);

    foreach ($reviewStmt->fetchAll(PDO::FETCH_ASSOC) as $reviewRow) {
        $employeeReviewStatusByEmp[(string)($reviewRow['emp_id'] ?? '')] = [
            'is_checked' => !empty($reviewRow['is_checked']),
            'checked_at' => (string)($reviewRow['checked_at'] ?? '')
        ];
    }
}

if ($selectedFeedbackStatus !== '') {
    $employees = array_values(array_filter($employees, static function ($employee) use (
        $feedbackStatusByEmp,
        $selectedFeedbackStatus,
        $canManageChecklistReview,
        $employeesRequiringChecklistReviewByEmp,
        $employeeReviewStatusByEmp
    ) {
        $empId = (string)($employee['emp_id'] ?? '');
        $feedbackInfo = $feedbackStatusByEmp[$empId] ?? [
            'total_count' => 0,
            'open_count' => 0,
            'resolved_count' => 0,
            'pending_followup_count' => 0
        ];

        $openCount = (int)($feedbackInfo['open_count'] ?? 0);
        $resolvedCount = (int)($feedbackInfo['resolved_count'] ?? 0);
        $totalCount = (int)($feedbackInfo['total_count'] ?? 0);

        if ($selectedFeedbackStatus === 'submitted') {
            return $openCount > 0;
        }

        if ($selectedFeedbackStatus === 'resolved') {
            return $totalCount > 0 && $openCount === 0 && $resolvedCount > 0;
        }

        if ($selectedFeedbackStatus === 'needs_mark_checked') {
            if (!$canManageChecklistReview) {
                return false;
            }

            $requiresChecklistReview = !empty($employeesRequiringChecklistReviewByEmp[$empId]);
            $isExplicitlyChecked = !empty($employeeReviewStatusByEmp[$empId]['is_checked']);

            return $requiresChecklistReview && !$isExplicitlyChecked;
        }

        return true;
    }));
}

$stmtBenefits = $pdo->prepare("SELECT
        CASE
            WHEN pb.type_id IS NOT NULL AND bt.name IS NOT NULL THEN bt.name
            ELSE pb.benefit
        END AS benefit,
        pb.note,
        pb.hours,
        pb.days,
        pb.calculation_type
    FROM payroll_benefits pb
    LEFT JOIN benefit_types bt ON pb.type_id = bt.id
    WHERE pb.emp_id = :emp_id AND pb.month = :month_year AND pb.status = 1");

$stmtDeductions = $pdo->prepare("SELECT deduction, note, hours, days, calculation_type
    FROM payroll_deductions
    WHERE emp_id = :emp_id AND month = :month_year AND status = 1");

$summary = [
    'employees' => 0,
    'gross' => 0.0,
    'benefits' => 0.0,
    'deductions' => 0.0,
    'net' => 0.0,
    'mismatch_count' => 0,
    'paid_count' => 0,
    'generated_count' => 0
];
$checkedEmployeesCount = 0;
$checklistReviewRequiredCount = 0;

foreach ($employees as $index => $employee) {
    $empId = $employee['emp_id'];

    $stmtBenefits->execute([':emp_id' => $empId, ':month_year' => $monthYear]);
    $benefits = $stmtBenefits->fetchAll(PDO::FETCH_ASSOC);

    $stmtDeductions->execute([':emp_id' => $empId, ':month_year' => $monthYear]);
    $deductions = $stmtDeductions->fetchAll(PDO::FETCH_ASSOC);

    $gross = (float)($employee['total_gross_salary'] ?? 0);
    $benefitTotal = (float)($employee['total_benefits'] ?? 0);
    $deductionTotal = (float)($employee['total_deductions'] ?? 0);
    $actualNet = (float)($employee['net_salary'] ?? 0);
    $expectedNet = round($gross + $benefitTotal - $deductionTotal, 2);
    $difference = round($actualNet - $expectedNet, 2);
    $isBalanced = abs($difference) < 0.01;

    $employees[$index]['benefits_list'] = $benefits;
    $employees[$index]['deductions_list'] = $deductions;
    $employees[$index]['expected_net_salary'] = $expectedNet;
    $employees[$index]['net_difference'] = $difference;
    $employees[$index]['is_balanced'] = $isBalanced;

    $requiresChecklistReview = $canManageChecklistReview && !empty($employeesRequiringChecklistReviewByEmp[(string)$empId]);
    $isExplicitlyChecked = !empty($employeeReviewStatusByEmp[(string)$empId]['is_checked']);
    $isChecklistChecked = !$requiresChecklistReview || $isExplicitlyChecked;

    $employees[$index]['requires_checklist_review'] = $requiresChecklistReview;
    $employees[$index]['is_review_checked'] = $isChecklistChecked;

    if ($requiresChecklistReview) {
        $checklistReviewRequiredCount++;
    }

    $summary['employees']++;
    $summary['gross'] += $gross;
    $summary['benefits'] += $benefitTotal;
    $summary['deductions'] += $deductionTotal;
    $summary['net'] += $actualNet;
    if (!$isBalanced) {
        $summary['mismatch_count']++;
    }
    if (($employee['status'] ?? '') === 'paid') {
        $summary['paid_count']++;
    }
    if (($employee['status'] ?? '') === 'generated') {
        $summary['generated_count']++;
    }
    if ($isChecklistChecked) {
        $checkedEmployeesCount++;
    }
}

$canFinanceOfficerApproveHere = $isAssignedFinanceCompanyVerifier
    && $requestInvNo !== ''
    && $requestStatus === 'pending_approval'
    && $assignedScopeOnly; // Only allow approval when toggle is ON (assigned scope mode)
$financeOfficerAlreadyApproved = !empty($financeOfficerVerificationRow['officer_approved']);
$financeOfficerCanApproveNow = $canFinanceOfficerApproveHere
    && !$financeOfficerAlreadyApproved
    && (int)$summary['employees'] > 0
    && (int)$checkedEmployeesCount >= (int)$summary['employees'];

$monthTitle = date('F Y', strtotime($monthYear . '-01'));

$employeesForJs = [];
foreach ($employees as $employee) {
    $employeesForJs[] = [
        'emp_id' => (string)($employee['emp_id'] ?? ''),
        'employee_name' => (string)($employee['employee_name'] ?? ''),
        'department_name' => (string)((($is_rtl ?? false) ? ($employee['department_name_ar'] ?? $employee['department_name'] ?? 'N/A') : ($employee['department_name'] ?? $employee['department_name_ar'] ?? 'N/A'))),
        'company_name' => (string)($employee['comp_name'] ?? 'N/A'),
        'status' => (string)($employee['status'] ?? 'generated'),
        'payment_type' => (int)($employee['payment_type'] ?? 1),
        'iqama' => (string)($employee['iqama'] ?? ''),
        'iban' => (string)($employee['iban'] ?? ''),
        'bank_name_s' => (string)($employee['bank_name_s'] ?? ''),
        'basic_salary' => (float)($employee['basic_salary'] ?? 0),
        'housing_allowance' => (float)($employee['housing_allowance'] ?? 0),
        'transport_allowance' => (float)($employee['transport_allowance'] ?? 0),
        'food_allowance' => (float)($employee['food_allowance'] ?? 0),
        'miscellaneous_allowance' => (float)($employee['miscellaneous_allowance'] ?? 0),
        'cashier_allowance' => (float)($employee['cashier_allowance'] ?? 0),
        'fuel_allowance' => (float)($employee['fuel_allowance'] ?? 0),
        'telephone_allowance' => (float)($employee['telephone_allowance'] ?? 0),
        'other_allowance' => (float)($employee['other_allowance'] ?? 0),
        'guard_allowance' => (float)($employee['guard_allowance'] ?? 0),
        'total_gross_salary' => (float)($employee['total_gross_salary'] ?? 0),
        'total_benefits' => (float)($employee['total_benefits'] ?? 0),
        'total_deductions' => (float)($employee['total_deductions'] ?? 0),
        'expected_net_salary' => (float)($employee['expected_net_salary'] ?? 0),
        'net_salary' => (float)($employee['net_salary'] ?? 0),
        'net_difference' => (float)($employee['net_difference'] ?? 0),
        'is_balanced' => !empty($employee['is_balanced']),
        'benefits_list' => $employee['benefits_list'] ?? [],
        'deductions_list' => $employee['deductions_list'] ?? [],
        'open_feedback_count' => (int)(($feedbackStatusByEmp[(string)($employee['emp_id'] ?? '')] ?? [])['open_count'] ?? 0),
        'requires_checklist_review' => !empty($employee['requires_checklist_review']),
        'is_review_checked' => !empty($employee['is_review_checked'])
    ];
}
?>
<!doctype html>
<html lang="<?= htmlspecialchars($current_lang) ?>" <?= ($is_rtl ?? false) ? 'dir="rtl"' : '' ?>>
<head>
    <meta charset="utf-8" />
    <title><?= htmlspecialchars($site_title ?? 'Al-Mutlak') ?> - <?= __('payroll_checklist_report', 'Payroll Checklist Report') ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <link rel="shortcut icon" href="<?= htmlspecialchars((string)(function_exists('get_setting') ? (get_setting($conDB, 'favicon') ?? 'assets/images/favicon.ico') : 'assets/images/favicon.ico')) ?>">
    <link href="assets/css/bootstrap.min.css" rel="stylesheet" type="text/css" />
    <link href="assets/css/style.css" rel="stylesheet" type="text/css" />
    <link href="assets/css/icons.css" rel="stylesheet" type="text/css" />
    <link href="plugins/datatables/dataTables.bootstrap4.min.css" rel="stylesheet" type="text/css" />
    <link href="./plugins/select2/css/select2.min.css" rel="stylesheet" type="text/css" />
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body { background: #f4f7fb; }
        .page-header {
            background: linear-gradient(135deg, #0f5b78 0%, #138a72 100%);
            color: #fff;
            padding: 30px 0;
            margin-bottom: 28px;
            box-shadow: 0 8px 24px rgba(15, 91, 120, 0.18);
            text-align: <?= ($is_rtl ?? false) ? 'right' : 'left' ?>;
        }
        .page-header h3 { margin: 0; font-weight: 700; }
        .toolbar {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 16px;
        }
        .toolbar .btn { border-radius: 999px; padding: 10px 18px; font-weight: 600; }
        .filter-card .form-group label {
            font-size: 12px;
            font-weight: 700;
            color: #5d7286;
            margin-bottom: 6px;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }
        .filter-card .form-control {
            border-radius: 10px;
            min-height: 42px;
        }
        .filter-card .filter-action-wrap {
            height: 100%;
        }
        .filter-card .filter-action-spacer {
            display: block;
            visibility: hidden;
            font-size: 12px;
            font-weight: 700;
            margin-bottom: 6px;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }
        .filter-card .filter-reset-btn {
            min-height: 42px;
            border-radius: 10px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 100%;
            font-weight: 600;
        }
        .filter-card .select2-container {
            width: 100% !important;
        }
        .filter-card .select2-container--default .select2-selection--multiple {
            min-height: 42px;
            border: 1px solid #ced4da;
            border-radius: 10px;
            padding: 3px 6px;
        }
        .filter-card .select2-container--default.select2-container--focus .select2-selection--multiple {
            border-color: #80bdff;
        }
        .filter-card .select2-container--default .select2-selection--multiple .select2-selection__choice {
            margin-top: 4px;
        }
        .checklist-nav-footer {
            text-align: center;
        }
        .checklist-nav-actions {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            width: 100%;
        }
        .checklist-nav-btn {
            min-width: 110px;
            border-radius: 999px;
            padding: 8px 14px;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            justify-content: center;
        }
        .checklist-nav-btn:disabled {
            opacity: 0.55;
            cursor: not-allowed;
        }
        .checklist-nav-counter {
            font-weight: 700;
            color: #5d7286;
            min-width: 80px;
        }
        .summary-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 14px;
            margin-bottom: 22px;
        }
        .summary-card {
            background: #fff;
            border: 1px solid #e5edf4;
            border-radius: 16px;
            padding: 18px;
            box-shadow: 0 8px 24px rgba(24, 39, 75, 0.05);
        }
        .summary-label {
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #789;
            margin-bottom: 8px;
        }
        .summary-value {
            font-size: 24px;
            font-weight: 700;
            color: #183247;
        }
        .list-card {
            background: #fff;
            border: 1px solid #e2eaf1;
            border-radius: 18px;
            box-shadow: 0 10px 30px rgba(24, 39, 75, 0.05);
            overflow: hidden;
        }
        .list-card-header {
            background: linear-gradient(135deg, #eef7fb 0%, #f7fbf8 100%);
            padding: 14px 16px;
            border-bottom: 1px solid #e2eaf1;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }
        .list-card-title {
            color: #173247;
            font-size: 16px;
            font-weight: 700;
            margin: 0;
        }
        .pill-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 12px;
            border-radius: 999px;
            font-weight: 700;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }
        .pill-success { background: #eaf8ee; color: #1f8b3c; }
        .pill-danger { background: #fdecec; color: #c43636; }
        .pill-warning { background: #fff7df; color: #ad7b00; }
        .pill-primary { background: #e9f2ff; color: #1d62d1; }
        .pill-secondary { background: #eef2f6; color: #607080; }
        .table-compact td, .table-compact th { vertical-align: middle; }
        .checklist-row-checked td {
            background: #edf9f0 !important;
        }
        .checklist-row-checked:hover td {
            background: #e3f5e7 !important;
        }
        .diff-positive { color: #c43636; font-weight: 700; }
        .diff-zero { color: #1f8b3c; font-weight: 700; }
        .calc-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 12px;
            margin-bottom: 18px;
        }
        .calc-box {
            background: #f8fbfd;
            border: 1px solid #e5edf4;
            border-radius: 12px;
            padding: 14px;
        }
        .calc-title {
            font-size: 12px;
            text-transform: uppercase;
            font-weight: 700;
            color: #70859a;
            margin-bottom: 6px;
        }
        .calc-value {
            font-size: 20px;
            font-weight: 700;
            color: #173247;
        }
        .details-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 18px;
        }
        .detail-panel {
            border: 1px solid #e5edf4;
            border-radius: 14px;
            overflow: visible;
            background: #fff;
            min-width: 0;
        }
        .detail-panel h5 {
            margin: 0;
            padding: 14px 16px;
            background: #f7fafc;
            border-bottom: 1px solid #e5edf4;
            color: #16344a;
            font-size: 15px;
            font-weight: 700;
        }
        .detail-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        .detail-list li {
            display: flex;
            justify-content: space-between;
            gap: 12px;
            padding: 12px 16px;
            border-bottom: 1px solid #eef3f7;
            font-size: 14px;
        }
        .detail-list li:last-child { border-bottom: none; }
        .detail-list .label { color: #637b90; font-weight: 600; }
        .detail-list .value { color: #173247; font-weight: 700; text-align: right; }
        .line-items {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        .line-items-two-col {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 10px;
            list-style: none;
            padding: 14px;
            margin: 0;
        }
        .line-items li {
            padding: 12px 16px;
            border-bottom: 1px solid #eef3f7;
        }
        .line-items li:last-child { border-bottom: none; }
        .line-items-two-col li {
            padding: 10px 12px;
            border: 1px solid #e5edf4;
            border-radius: 10px;
            background: #fafcfe;
        }
        .item-title { font-weight: 700; color: #173247; }
        .item-meta { color: #6b8296; font-size: 13px; margin-top: 4px; }
        .salary-allowances-grid-flex {
            width: 100%;
            border: 1px solid #eef3f7;
            border-radius: 0 0 4px 4px;
            padding: 0;
            margin: 0;
        }
        .salary-row-flex {
            display: flex;
            flex-direction: row;
            border-bottom: 1px solid #eef3f7;
        }
        .salary-row-flex:last-child {
            border-bottom: none;
        }
        .salary-col {
            flex: 1 1 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 8px;
            padding: 12px 12px;
            border-right: 1px solid #eef3f7;
            font-size: 13px;
            min-width: 0;
        }
        .salary-col:last-child {
            border-right: none;
        }
        .salary-label {
            color: #637b90;
            font-weight: 600;
            flex-shrink: 1;
            min-width: 80px;
            word-wrap: break-word;
        }
        .salary-value {
            color: #173247;
            font-weight: 700;
            text-align: right;
            min-width: 80px;
            flex-shrink: 0;
        }
        .swal-landscape-popup {
            width: 94vw !important;
            max-width: 1500px !important;
        }
        .swal-landscape-container .swal2-html-container {
            text-align: left !important;
            margin: 0.75rem 0.8rem 0.2rem 0.8rem !important;
        }
        .empty-state {
            background: #fff;
            border-radius: 18px;
            padding: 40px 24px;
            text-align: center;
            border: 1px solid #e2eaf1;
            box-shadow: 0 10px 30px rgba(24, 39, 75, 0.05);
        }
        .modal-lg-custom { max-width: 1100px; }
        @media (max-width: 991px) {
            .details-grid { grid-template-columns: 1fr; }
            .line-items-two-col { grid-template-columns: 1fr; }
        }
        @media print {
            .toolbar { display: none !important; }
            .page-header { box-shadow: none; }
            body { background: #fff; }
            .employee-card, .summary-card { box-shadow: none; }
            .print-hide-status { display: none !important; }
        }
        #payrollImportReviewTabs .nav-link#payrollImportBenefitsTab {
            color: #28a745;
        }
        #payrollImportReviewTabs .nav-link#payrollImportBenefitsTab.active {
            color: #fff;
            background-color: #28a745;
            border-color: #28a745 #28a745 #28a745;
        }
        #payrollImportReviewTabs .nav-link#payrollImportDeductionsTab {
            color: #dc3545;
        }
        #payrollImportReviewTabs .nav-link#payrollImportDeductionsTab.active {
            color: #fff;
            background-color: #dc3545;
            border-color: #dc3545 #dc3545 #dc3545;
        }
    </style>
</head>
<body>
    <div class="page-header">
        <div class="container-fluid">
            <h3><i class="fas fa-clipboard-check"></i> <?= __('payroll_checklist_report', 'Payroll Checklist Report') ?></h3>
            <p class="mb-0"><?= __('payroll_checklist_subtitle', 'Detailed payroll calculation review for approvers') ?>: <?= htmlspecialchars($monthTitle) ?></p>
            <div class="toolbar">
                <?php if (!$isFinanceOfficerChecklistUser): ?>
                    <a href="all_payroll_approvals.php" class="btn btn-light"><i class="fas fa-arrow-left"></i> <?= __('back_to_payroll_approvals', 'Back to Payroll Approvals') ?></a>
                <?php else: ?>
                    <a href="dashboard.php" class="btn btn-light"><i class="fas fa-arrow-left"></i> <?= __('back', 'Back') ?></a>
                <?php endif; ?>
                <?php if ($requestInvNo !== ''): ?>
                    <a href="payroll_status_history.php?inv_no=<?= urlencode($requestInvNo) ?>" target="_blank" class="btn btn-outline-light"><i class="fas fa-history"></i> <?= __('history') ?></a>
                <?php endif; ?>
                <?php if ($assignedScopeToggleUrl !== ''): ?>
                    <a href="<?= htmlspecialchars($assignedScopeToggleUrl) ?>" class="btn <?= $assignedScopeOnly ? 'btn-warning' : 'btn-info' ?>">
                        <i class="fas fa-filter"></i>
                        <?= $assignedScopeOnly
                            ? __('show_all_employees', 'Show All Employees')
                            : __('show_my_assigned_companies_only', 'Show My Assigned Companies Only') ?>
                    </a>
                <?php endif; ?>
                <?php if ($canFinanceOfficerApproveHere): ?>
                    <?php if ($financeOfficerAlreadyApproved): ?>
                        <button type="button" class="btn btn-success" disabled>
                            <i class="fas fa-check-circle"></i> <?= __('finance_officer_approved', 'Finance Officer Approved') ?>
                        </button>
                    <?php else: ?>
                        <button type="button" id="financeOfficerApproveBtn" class="btn btn-primary" onclick="approveFinanceOfficerVerification()" <?= $financeOfficerCanApproveNow ? '' : 'disabled' ?>>
                            <i class="fas fa-user-check"></i> <?= __('approve_finance_verification', 'Approve Finance Verification') ?>
                        </button>
                    <?php endif; ?>
                <?php endif; ?>
                <?php if ($requestInvNo !== ''): ?>
                    <button type="button" id="feedbackFollowupBtn" class="btn btn-warning" onclick="notifyPayrollGeneratorFeedback()" style="display: <?= $showFeedbackFollowupButton ? 'inline-flex' : 'none' ?>; align-items:center; gap:6px;">
                        <i class="fas fa-paper-plane"></i> <?= __('send_feedback_followup', 'Send Feedback Follow-up') ?>
                        <span class="badge badge-light ml-1" id="feedbackFollowupCountBadge"><?= (int)$feedbackTotals['pending_followup_count'] ?></span>
                    </button>
                <?php endif; ?>
                <?php if ($canUploadManagerPayrollExcel): ?>
                    <button type="button" class="btn btn-info" onclick="openManagerPayrollUploadModal()">
                        <i class="fas fa-file-upload"></i> <?= __('upload_payroll_excel', 'Upload Payroll Excel') ?>
                    </button>
                <?php endif; ?>
                <?php if ($canReviewManagerUploadedPayrollExcel): ?>
                    <button type="button" id="managerReviewImportBtn" class="btn btn-primary" onclick="reviewManagerUploadedPayrollExcel()" style="display:inline-flex; align-items:center; gap:6px;">
                        <i class="fas fa-file-import"></i> <?= __('review_import_manager_file', 'Review Manager File & Import') ?>
                        <span class="badge badge-light ml-1" id="managerPendingReviewCountBadge" style="display:none;">0</span>
                    </button>
                <?php endif; ?>
                <?php if ($canExportChecklistExcel): ?>
                    <button type="button" class="btn btn-success" onclick="exportChecklistExcel()"><i class="fas fa-file-excel"></i> <?= __('export_excel', 'Export Excel') ?></button>
                <?php endif; ?>
                <button type="button" class="btn btn-outline-light" onclick="printFullChecklistTable()"><i class="fas fa-print"></i> <?= __('print', 'Print') ?></button>
            </div>
        </div>
    </div>

    <div class="container-fluid" style="margin-bottom: 32px;">
        <div class="list-card filter-card mb-3">
            <div class="list-card-header">
                <h5 class="list-card-title"><i class="fas fa-filter"></i> <?= __('filters', 'Filters') ?></h5>
            </div>
            <div class="p-3">
                <form method="GET" class="row" id="checklistFilterForm">
                    <input type="hidden" name="month" value="<?= htmlspecialchars($monthYear) ?>">
                    <?php if ($requestInvNo !== ''): ?>
                        <input type="hidden" name="request_inv_no" value="<?= htmlspecialchars($requestInvNo) ?>">
                    <?php endif; ?>
                    <?php if ($assignedScopeOnly): ?>
                        <input type="hidden" name="assigned_scope" value="1">
                    <?php endif; ?>

                    <div class="col-md-2 col-sm-6">
                        <div class="form-group">
                            <label for="filterCompany"><?= __('company', 'Company') ?></label>
                            <select class="form-control" id="filterCompany" name="company">
                                <option value=""><?= __('all', 'All') ?> <?= __('company', 'Company') ?></option>
                                <?php foreach ($companies as $company): ?>
                                    <option value="<?= htmlspecialchars((string)($company['comp_id'] ?? '')) ?>" <?= ((string)($company['comp_id'] ?? '') === $selectedCompany) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars(getDisplayName((string)($company['comp_name'] ?? ''))) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="col-md-2 col-sm-6">
                        <div class="form-group">
                            <label for="filterDepartment"><?= __('department', 'Department') ?></label>
                            <select class="form-control" id="filterDepartment" name="department">
                                <option value=""><?= __('all', 'All') ?> <?= __('department', 'Department') ?></option>
                                <?php foreach ($departments as $department): ?>
                                    <?php $departmentLabel = ($is_rtl ?? false) ? ($department['dep_nme_ar'] ?? $department['dep_nme'] ?? '') : ($department['dep_nme'] ?? $department['dep_nme_ar'] ?? ''); ?>
                                    <option value="<?= htmlspecialchars((string)($department['id'] ?? '')) ?>" <?= ((string)($department['id'] ?? '') === $selectedDepartment) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars(getDisplayName((string)$departmentLabel)) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="col-md-2 col-sm-6">
                        <div class="form-group">
                            <label for="filterSponsor"><?= __('sponsor', 'Sponsor') ?></label>
                            <select class="form-control" id="filterSponsor" name="sponsor">
                                <option value=""><?= __('all', 'All') ?> <?= __('sponsor', 'Sponsor') ?></option>
                                <?php foreach ($sponsors as $sponsor): ?>
                                    <option value="<?= htmlspecialchars((string)($sponsor['id'] ?? '')) ?>" <?= ((string)($sponsor['id'] ?? '') === $selectedSponsor) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars(getDisplayName((string)($sponsor['sponsor'] ?? ''))) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="col-md-2 col-sm-6">
                        <div class="form-group">
                            <label for="filterFeedbackStatus"><?= __('feedback_status', 'Feedback Status') ?></label>
                            <select class="form-control" id="filterFeedbackStatus" name="feedback_status">
                                <option value=""><?= __('all', 'All') ?> <?= __('feedback', 'Feedback') ?></option>
                                <option value="submitted" <?= $selectedFeedbackStatus === 'submitted' ? 'selected' : '' ?>><?= __('feedback_submitted', 'Feedback Submitted') ?></option>
                                <option value="resolved" <?= $selectedFeedbackStatus === 'resolved' ? 'selected' : '' ?>><?= __('feedback_resolved', 'Feedback Resolved') ?></option>
                                <option value="needs_mark_checked" <?= $selectedFeedbackStatus === 'needs_mark_checked' ? 'selected' : '' ?>><?= __('needs_mark_checked', 'Need Mark Checked') ?></option>
                            </select>
                        </div>
                    </div>

                    <div class="col-md-2 col-sm-6">
                        <div class="form-group">
                            <label for="filterPaymentType"><?= __('salary_payment_type_label', 'Payment Type') ?></label>
                            <?php $selectedPaymentTypeMap = array_flip($selectedPaymentTypes); ?>
                            <select class="form-control" id="filterPaymentType" name="payment_type[]" multiple size="3">
                                <option value="1" <?= isset($selectedPaymentTypeMap[1]) ? 'selected' : '' ?>><?= __('bank_option', 'Bank') ?></option>
                                <option value="2" <?= isset($selectedPaymentTypeMap[2]) ? 'selected' : '' ?>><?= __('cash_option', 'Cash') ?></option>
                                <option value="3" <?= isset($selectedPaymentTypeMap[3]) ? 'selected' : '' ?>><?= __('hold_option', 'Hold') ?></option>
                            </select>
                        </div>
                    </div>

                    <div class="col-md-2 col-sm-6">
                        <div class="form-group filter-action-wrap">
                            <span class="filter-action-spacer"><?= __('feedback_status', 'Feedback Status') ?></span>
                            <a href="payroll_checklist_report.php?month=<?= urlencode($monthYear) ?><?= $requestInvNo !== '' ? '&request_inv_no=' . urlencode($requestInvNo) : '' ?><?= $assignedScopeOnly ? '&assigned_scope=1' : '' ?>" class="btn btn-outline-secondary filter-reset-btn"><i class="fas fa-undo mr-2"></i> <?= __('reset_filters', 'Reset Filters') ?></a>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <div class="summary-grid">
            <div class="summary-card"><div class="summary-label"><?= __('employees', 'Employees') ?></div><div class="summary-value" id="summaryEmployeesValue"><?= (int)$summary['employees'] ?></div></div>
            <div class="summary-card"><div class="summary-label"><?= __('total_gross_salary_label', 'Gross Total') ?></div><div class="summary-value" id="summaryGrossValue"><?= number_format($summary['gross'], 2) ?></div></div>
            <div class="summary-card"><div class="summary-label"><?= __('benefits_total', 'Benefits Total') ?></div><div class="summary-value" id="summaryBenefitsValue"><?= number_format($summary['benefits'], 2) ?></div></div>
            <div class="summary-card"><div class="summary-label"><?= __('deductions_total', 'Deductions Total') ?></div><div class="summary-value" id="summaryDeductionsValue"><?= number_format($summary['deductions'], 2) ?></div></div>
            <div class="summary-card"><div class="summary-label"><?= __('net_salary_label', 'Net Total') ?></div><div class="summary-value" id="summaryNetValue"><?= number_format($summary['net'], 2) ?></div></div>
            <div class="summary-card"><div class="summary-label"><?= __('calculation_conflicts', 'Calculation Conflicts') ?><i class="fas fa-info-circle text-muted ml-1" data-toggle="tooltip" data-placement="top" title="<?= htmlspecialchars(__('calculation_conflicts_tooltip', 'Employees where Net Salary does not match (Gross + Benefits - Deductions).'), ENT_QUOTES) ?>" style="cursor: help;"></i></div><div class="summary-value" id="summaryMismatchValue" style="color: <?= $summary['mismatch_count'] > 0 ? '#c43636' : '#1f8b3c' ?>;"><?= (int)$summary['mismatch_count'] ?></div></div>
            <?php if ($canManageChecklistReview): ?>
                <div class="summary-card"><div class="summary-label"><?= __('checked_by_me', 'Checked By Me') ?></div><div class="summary-value" id="checkedEmployeesValue"><?= (int)$checkedEmployeesCount ?> / <?= (int)$summary['employees'] ?></div></div>
                <div class="summary-card"><div class="summary-label"><?= __('remaining_to_check', 'Remaining To Check') ?></div><div class="summary-value" id="remainingEmployeesValue" style="color: <?= ((int)$summary['employees'] - (int)$checkedEmployeesCount) > 0 ? '#ad7b00' : '#1f8b3c' ?>;"><?= max(0, (int)$summary['employees'] - (int)$checkedEmployeesCount) ?></div></div>
            <?php endif; ?>
        </div>

        <?php if (empty($employees)): ?>
            <div class="empty-state">
                <h4><?= __('no_generated_payrolls_for_month_info', 'No generated payrolls found for this month.') ?></h4>
                <p class="text-muted mb-0"><?= __('please_generate_payroll_first', 'Generate payroll first, then review the checklist report.') ?></p>
            </div>
        <?php else: ?>
            <div class="list-card">
                <div class="list-card-header">
                    <h5 class="list-card-title"><i class="fas fa-users"></i> <?= __('employee_payroll_list', 'Employee Payroll Check List') ?></h5>
                </div>
                <div class="table-responsive p-3">
                    <table class="table table-bordered table-hover table-compact" id="checklistTable" style="width:100%;">
                        <thead>
                            <tr>
                                <th><?= __('emp_id') ?></th>
                                <th><?= __('name') ?></th>
                                <th><?= __('department') ?></th>
                                <th><?= __('total_gross_salary_label', 'Total Gross Salary') ?></th>
                                <th><?= __('benefits_total', 'Total Benefits') ?></th>
                                <th><?= __('deductions_total', 'Total Deductions') ?></th>
                                <th><?= __('net_salary_label', 'Total Net Salary') ?></th>
                                <th><?= __('difference', 'Difference') ?></th>
                                <th class="print-hide-status"><?= __('status') ?></th>
                                <th class="print-hide-status\"><?= __('actions') ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($employees as $employee): ?>
                                <?php
                                $statusValue = (string)($employee['status'] ?? 'generated');
                                $statusBadge = 'pill-warning';
                                $statusIcon = 'fa-file-invoice-dollar';
                                if ($statusValue === 'paid') {
                                    $statusBadge = 'pill-primary';
                                    $statusIcon = 'fa-check-circle';
                                }
                                $difference = (float)($employee['net_difference'] ?? 0);
                                $empFeedback = $feedbackStatusByEmp[(string)($employee['emp_id'] ?? '')] ?? ['total_count' => 0, 'open_count' => 0, 'resolved_count' => 0];
                                $feedbackBtnClass = 'btn-outline-danger';
                                $feedbackBtnIcon = 'fa-comment-dots';
                                $feedbackBtnText = __('feedback', 'Feedback');
                                $requiresChecklistReview = !empty($employee['requires_checklist_review']);
                                $empReviewStatus = $employeeReviewStatusByEmp[(string)($employee['emp_id'] ?? '')] ?? ['is_checked' => false, 'checked_at' => ''];
                                $isEmployeeChecked = !empty($employee['is_review_checked']);
                                $checkBtnClass = $isEmployeeChecked ? 'btn-success' : 'btn-outline-success';
                                $checkBtnIcon = $isEmployeeChecked ? 'fa-user-check' : 'fa-check';
                                $checkBtnText = $isEmployeeChecked ? __('checked', 'Checked') : __('mark_checked', 'Mark Checked');
                                $checkBtnTitle = ($isEmployeeChecked && !empty($empReviewStatus['checked_at']))
                                    ? __('checked_on', 'Checked on') . ': ' . date('d M Y H:i', strtotime((string)$empReviewStatus['checked_at']))
                                    : __('mark_employee_checked_hint', 'Mark this employee as reviewed and OK');

                                if ($empFeedback['total_count'] > 0 && $empFeedback['open_count'] > 0) {
                                    $feedbackBtnClass = 'btn-warning';
                                    $feedbackBtnIcon = 'fa-comments';
                                    $feedbackBtnText = __('feedback_submitted', 'Feedback Submitted');
                                } elseif ($empFeedback['total_count'] > 0 && $empFeedback['open_count'] === 0) {
                                    $feedbackBtnClass = 'btn-success';
                                    $feedbackBtnIcon = 'fa-check-circle';
                                    $feedbackBtnText = __('feedback_resolved', 'Feedback Resolved');
                                }
                                ?>
                                <tr class="<?= $isEmployeeChecked ? 'checklist-row-checked' : '' ?>" data-employee-row-id="<?= htmlspecialchars((string)$employee['emp_id'], ENT_QUOTES) ?>" data-requires-review="<?= $requiresChecklistReview ? '1' : '0' ?>">
                                    <td><?= htmlspecialchars((string)$employee['emp_id']) ?></td>
                                    <td><?= htmlspecialchars(getDisplayName((string)($employee['employee_name'] ?? ''))) ?></td>
                                    <td><?= htmlspecialchars(getDisplayName((string)(($is_rtl ?? false) ? ($employee['department_name_ar'] ?? $employee['department_name'] ?? 'N/A') : ($employee['department_name'] ?? $employee['department_name_ar'] ?? 'N/A')))) ?></td>
                                    <td><?= number_format((float)($employee['total_gross_salary'] ?? 0), 2) ?></td>
                                    <td><?= number_format((float)($employee['total_benefits'] ?? 0), 2) ?></td>
                                    <td><?= number_format((float)($employee['total_deductions'] ?? 0), 2) ?></td>
                                    <td><?= number_format((float)($employee['net_salary'] ?? 0), 2) ?></td>
                                    <td class="<?= abs($difference) < 0.01 ? 'diff-zero' : 'diff-positive' ?>"><?= number_format($difference, 2) ?></td>
                                    <td class="print-hide-status"><span class="pill-badge <?= $statusBadge ?>"><i class="fas <?= $statusIcon ?>"></i> <?= htmlspecialchars(getDisplayName(ucfirst($statusValue))) ?></span></td>
                                    <td class="text-center print-hide-status">
                                        <div class="btn-group dropdown">
                                            <a href="javascript:void(0);" class="table-action-btn dropdown-toggle arrow-none btn btn-light btn-sm" data-toggle="dropdown" aria-expanded="false">
                                                <i class="mdi mdi-dots-horizontal"></i>
                                            </a>
                                            <div class="dropdown-menu dropdown-menu-right">
                                                <a class="dropdown-item text-dark" href="javascript:void(0);" onclick="openEmployeeDetail('<?= htmlspecialchars((string)$employee['emp_id'], ENT_QUOTES) ?>')">
                                                    <i class="fa fa-eye mr-2 font-18 vertical-middle"></i><?= __('view', 'View') ?>
                                                </a>
                                                <?php if ($canManageChecklistReview && $requiresChecklistReview && $empFeedback['open_count'] === 0): ?>
                                                    <a class="dropdown-item text-dark" href="javascript:void(0);"
                                                        data-review-emp-id="<?= htmlspecialchars((string)$employee['emp_id'], ENT_QUOTES) ?>"
                                                        data-requires-check="1"
                                                        data-checked="<?= $isEmployeeChecked ? '1' : '0' ?>"
                                                        title="<?= htmlspecialchars($checkBtnTitle, ENT_QUOTES) ?>"
                                                        onclick="toggleEmployeeReviewCheck('<?= htmlspecialchars((string)$employee['emp_id'], ENT_QUOTES) ?>', this)">
                                                        <i class="fas <?= $checkBtnIcon ?> mr-2 font-18 vertical-middle <?= $isEmployeeChecked ? 'text-success' : '' ?>"></i><?= $checkBtnText ?>
                                                    </a>
                                                <?php endif; ?>
                                                <?php if ($requestInvNo !== '' && $canManageFeedbackActions): ?>
                                                    <a class="dropdown-item text-dark" href="javascript:void(0);" data-feedback-emp-id="<?= htmlspecialchars((string)$employee['emp_id'], ENT_QUOTES) ?>" onclick="openFeedbackDialog('<?= htmlspecialchars((string)$employee['emp_id'], ENT_QUOTES) ?>', '<?= htmlspecialchars((string)($employee['employee_name'] ?? ''), ENT_QUOTES) ?>')">
                                                        <i class="fas <?= $feedbackBtnIcon ?> mr-2 font-18 vertical-middle <?= $empFeedback['total_count'] > 0 && $empFeedback['open_count'] > 0 ? 'text-warning' : ($empFeedback['total_count'] > 0 && $empFeedback['open_count'] === 0 ? 'text-success' : 'text-danger') ?>"></i><?= $feedbackBtnText ?>
                                                    </a>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <script src="assets/js/jquery.min.js"></script>
    <script src="assets/js/bootstrap.bundle.min.js"></script>
    <script src="plugins/datatables/jquery.dataTables.min.js"></script>
    <script src="plugins/datatables/dataTables.bootstrap4.min.js"></script>
    <script src="./plugins/select2/js/select2.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js"></script>
    <script>
        const employeesData = <?= json_encode($employeesForJs, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
        const employeeMap = {};
        let checklistDataTable = null;
        let checklistModalKeyHandler = null;
        let checklistModalNavigationBusy = false;
        let checklistPrintRestoreState = null;
        const feedbackReminderState = {
            canManageFeedback: <?= $canSendFeedbackFollowup ? 'true' : 'false' ?>,
            canNotify: <?= $showFeedbackFollowupButton ? 'true' : 'false' ?>,
            requestInvNo: <?= json_encode($requestInvNo, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
            month: <?= json_encode($monthYear, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
            totalCount: <?= (int)$feedbackTotals['total_count'] ?>,
            openCount: <?= (int)$feedbackTotals['open_count'] ?>,
            pendingCount: <?= (int)$feedbackTotals['pending_followup_count'] ?>
        };
        const checklistReviewState = {
            canManage: <?= $canManageChecklistReview ? 'true' : 'false' ?>,
            requestInvNo: <?= json_encode($requestInvNo, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
            month: <?= json_encode($monthYear, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
            checkedCount: <?= (int)$checkedEmployeesCount ?>,
            totalEmployees: <?= (int)$summary['employees'] ?>
        };
        const financeOfficerApprovalState = {
            canApproveHere: <?= $canFinanceOfficerApproveHere ? 'true' : 'false' ?>,
            alreadyApproved: <?= $financeOfficerAlreadyApproved ? 'true' : 'false' ?>,
            requestInvNo: <?= json_encode($requestInvNo, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
            month: <?= json_encode($monthYear, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>
        };
        const checklistCompletionNoticeState = {
            lastRemainingEmployees: null,
            alerted: false
        };
        const payrollGeneratorName = <?= json_encode($requestGeneratorName, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
        const payrollGeneratorId = <?= json_encode($requestRequestedBy, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
        const checklistScopeState = {
            requestInvNo: <?= json_encode($requestInvNo, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
            assignedScopeOnly: <?= $assignedScopeOnly ? 'true' : 'false' ?>
        };
        const managerPayrollExcelState = {
            canUpload: <?= $canUploadManagerPayrollExcel ? 'true' : 'false' ?>,
            canReview: <?= $canReviewManagerUploadedPayrollExcel ? 'true' : 'false' ?>,
            requestInvNo: <?= json_encode($requestInvNo, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
            month: <?= json_encode($monthYear, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>
        };
        employeesData.forEach(item => { employeeMap[item.emp_id] = item; });

        function bindAutoFilterSubmit() {
            const form = document.getElementById('checklistFilterForm');
            if (!form) {
                return;
            }

            const submitFilterForm = function() {
                const submitButton = form.querySelector('button[type="submit"]');
                if (submitButton) {
                    submitButton.disabled = true;
                    submitButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> <?= addslashes(__('loading', 'Loading')) ?>';
                }
                form.submit();
            };

            const filterSelectors = ['filterCompany', 'filterDepartment', 'filterSponsor', 'filterFeedbackStatus'];
            filterSelectors.forEach(id => {
                const field = document.getElementById(id);
                if (!field) {
                    return;
                }

                field.addEventListener('change', function() {
                    submitFilterForm();
                });
            });

            const paymentTypeField = document.getElementById('filterPaymentType');
            if (paymentTypeField) {
                paymentTypeField.addEventListener('change', function() {
                    // For native multiple select fallback, submit immediately.
                    if (!(window.jQuery && typeof jQuery.fn.select2 === 'function')) {
                        submitFilterForm();
                    }
                });

                if (window.jQuery && typeof jQuery.fn.select2 === 'function') {
                    let hasSelectionChange = false;
                    $('#filterPaymentType').on('select2:select select2:unselect', function() {
                        hasSelectionChange = true;
                    });
                    $('#filterPaymentType').on('select2:close', function() {
                        if (hasSelectionChange) {
                            hasSelectionChange = false;
                            submitFilterForm();
                        }
                    });
                }
            }
        }

        function initPaymentTypeSelect2() {
            if (!(window.jQuery && typeof jQuery.fn.select2 === 'function')) {
                return;
            }

            const paymentTypeField = document.getElementById('filterPaymentType');
            if (!paymentTypeField) {
                return;
            }

            $('#filterPaymentType').select2({
                width: '100%',
                closeOnSelect: false,
                allowClear: true,
                placeholder: '<?= addslashes(__('select_payment_types', 'Select payment type(s)')) ?>'
            });
        }

        function exportChecklistExcel() {
            if (typeof XLSX === 'undefined') {
                Swal.fire('<?= addslashes(__('error', 'Error')) ?>', 'Excel export library failed to load.', 'error');
                return;
            }

            const visibleEmployees = getChecklistVisibleEmployees();
            if (!Array.isArray(visibleEmployees) || visibleEmployees.length === 0) {
                Swal.fire('<?= addslashes(__('info', 'Info')) ?>', '<?= addslashes(__('no_data_available_in_table', 'No data available in table')) ?>', 'info');
                return;
            }

            const exportRows = visibleEmployees.map((emp, index) => ({
                '#': index + 1,
                'Employee ID': String(emp.emp_id || ''),
                'Name': String(emp.employee_name || ''),
                'Department': String(emp.department_name || ''),
                'Company': String(emp.company_name || ''),
                'Payment Type': String(getPaymentTypeLabel(emp.payment_type) || ''),
                'Gross Salary': Number(emp.total_gross_salary || 0),
                'Benefits': Number(emp.total_benefits || 0),
                'Deductions': Number(emp.total_deductions || 0),
                'Expected Net': Number(emp.expected_net_salary || 0),
                'Actual Net': Number(emp.net_salary || 0),
                'Difference': Number(emp.net_difference || 0),
                'Status': String(emp.status || '')
            }));

            const worksheet = XLSX.utils.json_to_sheet(exportRows);
            worksheet['!cols'] = [
                { wch: 6 }, { wch: 14 }, { wch: 28 }, { wch: 20 }, { wch: 22 }, { wch: 16 },
                { wch: 14 }, { wch: 12 }, { wch: 14 }, { wch: 14 }, { wch: 14 }, { wch: 12 }, { wch: 14 }
            ];

            const workbook = XLSX.utils.book_new();
            XLSX.utils.book_append_sheet(workbook, worksheet, 'Payroll Checklist');

            const safeMonth = String(<?= json_encode($monthYear, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?> || 'payroll').replace(/[^0-9A-Za-z_-]+/g, '_');
            XLSX.writeFile(workbook, `payroll_checklist_${safeMonth}.xlsx`);
        }

        function restoreChecklistTableAfterPrint() {
            if (!checklistDataTable || !checklistPrintRestoreState) {
                return;
            }

            const restoreState = checklistPrintRestoreState;
            checklistPrintRestoreState = null;

            checklistDataTable.one('draw', function() {
                if (restoreState.pageLength !== -1) {
                    checklistDataTable.page(restoreState.pageIndex).draw('page');
                }
            });

            checklistDataTable.page.len(restoreState.pageLength).draw(false);
        }

        function printFullChecklistTable() {
            if (!checklistDataTable) {
                window.print();
                return;
            }

            if (checklistPrintRestoreState) {
                return;
            }

            const pageInfo = checklistDataTable.page.info();
            checklistPrintRestoreState = {
                pageLength: checklistDataTable.page.len(),
                pageIndex: Number(pageInfo && typeof pageInfo.page !== 'undefined' ? pageInfo.page : 0)
            };

            if (checklistPrintRestoreState.pageLength === -1) {
                window.print();
                return;
            }

            checklistDataTable.one('draw', function() {
                window.setTimeout(function() {
                    window.print();
                }, 100);
            });

            checklistDataTable.page.len(-1).draw(false);
        }

        window.addEventListener('afterprint', function() {
            window.setTimeout(restoreChecklistTableAfterPrint, 50);
        });

        function syncFollowupButton() {
            const followupBtn = document.getElementById('feedbackFollowupBtn');
            const followupBadge = document.getElementById('feedbackFollowupCountBadge');
            if (!followupBtn) {
                return;
            }

            feedbackReminderState.canNotify = !!feedbackReminderState.canManageFeedback && Number(feedbackReminderState.pendingCount || 0) > 0;
            followupBtn.style.display = feedbackReminderState.canNotify ? 'inline-flex' : 'none';
            if (followupBadge) {
                followupBadge.textContent = String(Number(feedbackReminderState.pendingCount || 0));
            }
        }

        function refreshFeedbackButtons(employeeIds) {
            if (!Array.isArray(employeeIds)) {
                return;
            }

            employeeIds.forEach(empId => {
                const selectorEmpId = String(empId).replace(/\\/g, '\\\\').replace(/"/g, '\\"');
                const btn = document.querySelector('[data-feedback-emp-id="' + selectorEmpId + '"]');
                if (!btn) {
                    return;
                }

                btn.classList.remove('btn-outline-danger', 'btn-success');
                btn.classList.add('btn-warning');
                btn.innerHTML = '<i class="fas fa-comments"></i> <?= addslashes(__('feedback_submitted', 'Feedback Submitted')) ?>';
            });

            if (checklistDataTable) {
                checklistDataTable.rows().invalidate('dom').draw(false);
            }
        }

        function syncChecklistReviewSummary() {
            const checkedValue = document.getElementById('checkedEmployeesValue');
            const remainingValue = document.getElementById('remainingEmployeesValue');
            const checkedCount = Number(checklistReviewState.checkedCount || 0);
            const totalEmployees = Number(checklistReviewState.totalEmployees || 0);
            const remainingEmployees = Math.max(0, totalEmployees - checkedCount);
            const previousRemainingEmployees = checklistCompletionNoticeState.lastRemainingEmployees;

            if (checkedValue) {
                checkedValue.textContent = `${checkedCount} / ${totalEmployees}`;
            }
            if (remainingValue) {
                remainingValue.textContent = String(remainingEmployees);
                remainingValue.style.color = remainingEmployees > 0 ? '#ad7b00' : '#1f8b3c';
            }

            if (remainingEmployees > 0) {
                checklistCompletionNoticeState.alerted = false;
            } else if (
                checklistReviewState.canManage &&
                totalEmployees > 0 &&
                previousRemainingEmployees !== null &&
                previousRemainingEmployees > 0 &&
                !checklistCompletionNoticeState.alerted
            ) {
                checklistCompletionNoticeState.alerted = true;
                Swal.fire({
                    icon: 'success',
                    title: '<?= addslashes(__('success', 'Success')) ?>',
                    text: '<?= addslashes(__('all_employees_checked_notice', 'All employees have been marked checked. There are no remaining payroll records to review.')) ?>',
                    confirmButtonText: '<?= addslashes(__('ok', 'OK')) ?>'
                });
            }

            checklistCompletionNoticeState.lastRemainingEmployees = remainingEmployees;

            const approveBtn = document.getElementById('financeOfficerApproveBtn');
            if (approveBtn && financeOfficerApprovalState.canApproveHere && !financeOfficerApprovalState.alreadyApproved) {
                approveBtn.disabled = !(remainingEmployees === 0 && totalEmployees > 0);
            }

        }

        async function approveFinanceOfficerVerification() {
            if (!financeOfficerApprovalState.canApproveHere || financeOfficerApprovalState.alreadyApproved) {
                return;
            }

            if (Number(checklistReviewState.totalEmployees || 0) <= 0 || Number(checklistReviewState.checkedCount || 0) < Number(checklistReviewState.totalEmployees || 0)) {
                Swal.fire('<?= addslashes(__('warning', 'Warning')) ?>', '<?= addslashes(__('please_complete_all_checks_before_approve', 'Please complete all employee checks before finance approval.')) ?>', 'warning');
                return;
            }

            const result = await Swal.fire({
                title: '<?= addslashes(__('confirm', 'Confirm')) ?>',
                text: '<?= addslashes(__('confirm_finance_officer_approval', 'Submit finance officer approval for this payroll verification?')) ?>',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: '<?= addslashes(__('approve', 'Approve')) ?>',
                cancelButtonText: '<?= addslashes(__('cancel', 'Cancel')) ?>',
                confirmButtonColor: '#007bff',
                allowOutsideClick: false,
            });

            if (!result.isConfirmed) {
                return;
            }

            try {
                const payload = new URLSearchParams();
                payload.append('action', 'confirm_finance_officer_verification');
                payload.append('request_inv_no', financeOfficerApprovalState.requestInvNo);
                payload.append('month', financeOfficerApprovalState.month);

                const response = await fetch('./includes/ajaxFile/payroll_approval_handler.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8' },
                    body: payload.toString()
                });
                const data = await response.json();

                if (!response.ok || data.status !== 'success') {
                    throw new Error(data.message || 'Failed to submit finance officer approval.');
                }

                await Swal.fire('<?= addslashes(__('success', 'Success')) ?>', data.message || 'Finance officer approval submitted successfully.', 'success');
                location.reload();
            } catch (error) {
                Swal.fire('<?= addslashes(__('error', 'Error')) ?>', error.message || 'Failed to submit finance officer approval.', 'error');
            }
        }

        function applyModalReviewButtonStyle(button, isChecked) {
            if (!button) {
                return;
            }

            const closeBtn = Swal.getConfirmButton();
            button.className = 'swal2-confirm swal2-styled';
            button.style.display = 'inline-flex';
            button.style.alignItems = 'center';
            button.style.justifyContent = 'center';
            button.style.gap = '6px';
            button.style.boxShadow = 'none';
            button.style.border = '1px solid #28a745';
            button.style.backgroundColor = isChecked ? '#28a745' : '#fff';
            button.style.color = isChecked ? '#fff' : '#28a745';

            if (closeBtn) {
                const closeBtnStyles = window.getComputedStyle(closeBtn);
                button.style.padding = closeBtnStyles.padding;
                button.style.fontSize = closeBtnStyles.fontSize;
                button.style.borderRadius = closeBtnStyles.borderRadius;
                button.style.margin = closeBtnStyles.margin;
                button.style.height = `${closeBtn.offsetHeight}px`;
                button.style.minWidth = `${closeBtn.offsetWidth}px`;
            }
        }

        function updateEmployeeReviewButton(empId, isChecked, checkedAt) {
            if (employeeMap[empId]) {
                employeeMap[empId].is_review_checked = !!isChecked;
            }

            const selectorEmpId = String(empId).replace(/\\/g, '\\\\').replace(/"/g, '\\"');
            const btn = document.querySelector('[data-review-emp-id="' + selectorEmpId + '"]');
            const modalBtn = document.getElementById('modalReviewToggleBtn');
            if (!btn && !modalBtn) {
                return;
            }

            const titleText = isChecked && checkedAt
                ? `<?= addslashes(__('checked_on', 'Checked on')) ?>: ${checkedAt}`
                : `<?= addslashes(__('mark_employee_checked_hint', 'Mark this employee as reviewed and OK')) ?>`;
            const buttonHtml = isChecked
                ? '<i class="fas fa-user-check mr-2"></i> <?= addslashes(__('checked', 'Checked')) ?>'
                : '<i class="fas fa-check mr-2"></i> <?= addslashes(__('mark_checked', 'Mark Checked')) ?>';

            if (btn) {
                btn.dataset.checked = isChecked ? '1' : '0';
                btn.classList.remove('btn-outline-success', 'btn-success', 'text-success', 'font-weight-bold');
                if (isChecked) {
                    btn.classList.add('text-success', 'font-weight-bold');
                }
                btn.title = titleText;
                btn.innerHTML = buttonHtml;

                const row = btn.closest('tr') || document.querySelector('[data-employee-row-id="' + selectorEmpId + '"]');
                if (row) {
                    row.classList.toggle('checklist-row-checked', !!isChecked);
                }
            }

            if (modalBtn && String(modalBtn.getAttribute('data-emp-id') || '') === String(empId)) {
                modalBtn.dataset.checked = isChecked ? '1' : '0';
                modalBtn.title = titleText;
                modalBtn.innerHTML = buttonHtml;
                applyModalReviewButtonStyle(modalBtn, !!isChecked);
            }
        }

        async function toggleEmployeeReviewCheck(empId, triggerButton = null) {
            if (!checklistReviewState.canManage) {
                return;
            }

            if (employeeMap[empId] && !employeeMap[empId].requires_checklist_review) {
                return;
            }

            const selectorEmpId = String(empId).replace(/\\/g, '\\\\').replace(/"/g, '\\"');
            const rowBtn = document.querySelector('[data-review-emp-id="' + selectorEmpId + '"]');
            const modalBtn = document.getElementById('modalReviewToggleBtn');
            const btn = triggerButton || rowBtn || (modalBtn && String(modalBtn.getAttribute('data-emp-id') || '') === String(empId) ? modalBtn : null);
            if (!btn) {
                return;
            }

            const wasChecked = btn.dataset.checked === '1';
            const originalHtml = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> <?= addslashes(__('loading', 'Loading')) ?>';

            try {
                const payload = new URLSearchParams();
                payload.append('action', 'toggle_employee_check');
                payload.append('request_inv_no', checklistReviewState.requestInvNo);
                payload.append('month', checklistReviewState.month);
                payload.append('emp_id', empId);
                payload.append('checked', wasChecked ? '0' : '1');

                const response = await fetch('./includes/ajaxFile/payroll_approval_handler.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8' },
                    body: payload.toString()
                });

                const data = await response.json();
                if (!response.ok || data.status !== 'success') {
                    throw new Error(data.message || 'Failed to update employee checklist status.');
                }

                checklistReviewState.checkedCount += data.is_checked ? (wasChecked ? 0 : 1) : (wasChecked ? -1 : 0);
                syncChecklistReviewSummary();
                updateEmployeeReviewButton(empId, !!data.is_checked, data.checked_at || '');
            } catch (error) {
                btn.innerHTML = originalHtml;
                Swal.fire('<?= addslashes(__('error', 'Error')) ?>', error.message || 'Failed to update employee checklist status.', 'error');
            } finally {
                btn.disabled = false;
            }
        }

        function fmt(v) {
            return Number(v || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        }

        function esc(v) {
            return String(v == null ? '' : v)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#39;');
        }

        function getPaymentTypeLabel(type) {
            const t = parseInt(type || 1, 10);
            if (t === 2) return '<?= addslashes(__('cash_option', 'Cash')) ?>';
            if (t === 3) return '<?= addslashes(__('hold_option', 'Hold')) ?>';
            return '<?= addslashes(__('bank_option', 'Bank')) ?>';
        }

        function getChecklistVisibleEmployees() {
            if (checklistDataTable) {
                const orderedIds = checklistDataTable.rows({ search: 'applied', order: 'applied' }).nodes().to$().map(function() {
                    return $(this).find('td').eq(0).text().trim();
                }).get();

                return orderedIds.map(empId => employeeMap[String(empId)]).filter(Boolean);
            }

            return Array.isArray(employeesData) ? employeesData.slice() : [];
        }

        function syncSummaryCardsFromVisibleRows() {
            const visibleEmployees = getChecklistVisibleEmployees();
            const totals = visibleEmployees.reduce((acc, emp) => {
                acc.employees += 1;
                acc.gross += Number(emp.total_gross_salary || 0);
                acc.benefits += Number(emp.total_benefits || 0);
                acc.deductions += Number(emp.total_deductions || 0);
                acc.net += Number(emp.net_salary || 0);
                if (!emp.is_balanced) {
                    acc.mismatch += 1;
                }
                return acc;
            }, {
                employees: 0,
                gross: 0,
                benefits: 0,
                deductions: 0,
                net: 0,
                mismatch: 0
            });

            const employeesEl = document.getElementById('summaryEmployeesValue');
            const grossEl = document.getElementById('summaryGrossValue');
            const benefitsEl = document.getElementById('summaryBenefitsValue');
            const deductionsEl = document.getElementById('summaryDeductionsValue');
            const netEl = document.getElementById('summaryNetValue');
            const mismatchEl = document.getElementById('summaryMismatchValue');

            if (employeesEl) employeesEl.textContent = String(totals.employees);
            if (grossEl) grossEl.textContent = fmt(totals.gross);
            if (benefitsEl) benefitsEl.textContent = fmt(totals.benefits);
            if (deductionsEl) deductionsEl.textContent = fmt(totals.deductions);
            if (netEl) netEl.textContent = fmt(totals.net);
            if (mismatchEl) {
                mismatchEl.textContent = String(totals.mismatch);
                mismatchEl.style.color = totals.mismatch > 0 ? '#c43636' : '#1f8b3c';
            }
        }

        function getChecklistEmployeeNavigationState(empId) {
            const employees = getChecklistVisibleEmployees();
            const currentIndex = employees.findIndex(emp => String(emp.emp_id) === String(empId));

            return {
                employees,
                currentIndex,
                previousEmployee: currentIndex > 0 ? employees[currentIndex - 1] : null,
                nextEmployee: currentIndex >= 0 && currentIndex < employees.length - 1 ? employees[currentIndex + 1] : null
            };
        }

        function detachChecklistModalKeyboardNavigation() {
            if (typeof checklistModalKeyHandler === 'function') {
                window.removeEventListener('keydown', checklistModalKeyHandler, true);
                checklistModalKeyHandler = null;
            }
        }

        function navigateChecklistEmployeeFromModal(targetEmployee) {
            if (!targetEmployee || !targetEmployee.emp_id || checklistModalNavigationBusy) {
                return;
            }

            checklistModalNavigationBusy = true;
            detachChecklistModalKeyboardNavigation();
            Swal.close();

            window.setTimeout(function() {
                checklistModalNavigationBusy = false;
                openEmployeeDetail(targetEmployee.emp_id);
            }, 60);
        }

        function attachChecklistModalKeyboardNavigation(navigationState) {
            detachChecklistModalKeyboardNavigation();

            checklistModalKeyHandler = function(event) {
                if (!Swal.isVisible() || checklistModalNavigationBusy) {
                    return;
                }

                if (event.altKey || event.ctrlKey || event.metaKey) {
                    return;
                }

                const activeElement = document.activeElement;
                const activeTag = activeElement && activeElement.tagName ? activeElement.tagName.toLowerCase() : '';
                if (activeElement && (activeElement.isContentEditable || activeTag === 'input' || activeTag === 'textarea' || activeTag === 'select')) {
                    return;
                }

                const key = String(event.key || event.code || '').toLowerCase();
                const keyCode = Number(event.keyCode || event.which || 0);
                const isPreviousKey = key === 'arrowleft' || key === 'left' || keyCode === 37;
                const isNextKey = key === 'arrowright' || key === 'right' || keyCode === 39;

                if (isPreviousKey && navigationState.previousEmployee) {
                    event.preventDefault();
                    event.stopPropagation();
                    navigateChecklistEmployeeFromModal(navigationState.previousEmployee);
                    return;
                }

                if (isNextKey && navigationState.nextEmployee) {
                    event.preventDefault();
                    event.stopPropagation();
                    navigateChecklistEmployeeFromModal(navigationState.nextEmployee);
                }
            };

            window.addEventListener('keydown', checklistModalKeyHandler, true);
        }

        async function notifyPayrollGeneratorFeedback() {
            if (!feedbackReminderState.canNotify) {
                return;
            }

            const openCountText = feedbackReminderState.openCount === 1
                ? '1 <?= addslashes(__('feedback_item', 'feedback item')) ?>'
                : `${feedbackReminderState.openCount} <?= addslashes(__('feedback_items', 'feedback items')) ?>`;

            const result = await Swal.fire({
                title: '<?= addslashes(__('send_feedback_followup', 'Send Feedback Follow-up')) ?>',
                text: `<?= addslashes(__('notify_generator_follow_feedback_text', 'Send email and notification to the payroll generator so they can follow the submitted feedback changes?')) ?> (${openCountText})`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#f0ad4e',
                confirmButtonText: '<?= addslashes(__('send_notification', 'Send Notification')) ?>',
                cancelButtonText: '<?= addslashes(__('cancel', 'Cancel')) ?>',
                showLoaderOnConfirm: true,
                allowOutsideClick: () => !Swal.isLoading(),
                allowEscapeKey: () => !Swal.isLoading(),
                preConfirm: async () => {
                    try {
                        const payload = new URLSearchParams();
                        payload.append('action', 'notify_feedback_followup');
                        payload.append('request_inv_no', feedbackReminderState.requestInvNo);
                        payload.append('month', feedbackReminderState.month);

                        const response = await fetch('./includes/ajaxFile/payroll_approval_handler.php', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8' },
                            body: payload.toString()
                        });

                        const data = await response.json();
                        if (!response.ok || data.status !== 'success') {
                            throw new Error(data.message || 'Failed to send the feedback follow-up reminder.');
                        }

                        return data;
                    } catch (error) {
                        Swal.showValidationMessage(error.message || 'Failed to send the feedback follow-up reminder.');
                        return false;
                    }
                }
            });

            if (!result.isConfirmed || !result.value) {
                return;
            }

            if (result.value.feedback_summary) {
                feedbackReminderState.totalCount = Number(result.value.feedback_summary.total_feedback || 0);
                feedbackReminderState.openCount = Number(result.value.feedback_summary.open_feedback || 0);
                feedbackReminderState.pendingCount = Number(result.value.feedback_summary.pending_followup_count || 0);
                syncFollowupButton();
            } else {
                feedbackReminderState.pendingCount = 0;
                syncFollowupButton();
            }

            await Swal.fire({
                icon: 'success',
                title: '<?= addslashes(__('success', 'Success')) ?>',
                text: result.value.message || 'Success',
                confirmButtonText: '<?= addslashes(__('ok', 'OK')) ?>'
            });
        }

        function buildItemsList(items, itemNameKey, emptyText) {
            if (!Array.isArray(items) || items.length === 0) {
                return `<div style="padding:16px;color:#6b8296;">${esc(emptyText)}</div>`;
            }

            return `<ul class="line-items-two-col">${items.map(item => {
                const title = `${esc(item[itemNameKey] || '')}: ${fmt(item.note || 0)}`;
                const meta = [];
                if (Number(item.days || 0) > 0) meta.push(`${esc(item.days)} <?= addslashes(__('days', 'Days')) ?>`);
                if (Number(item.hours || 0) > 0) meta.push(`${esc(item.hours)} <?= addslashes(__('hours', 'Hours')) ?>`);
                if (item.calculation_type) meta.push(esc(item.calculation_type));
                return `<li><div class="item-title">${title}</div><div class="item-meta">${meta.join(' | ')}</div></li>`;
            }).join('')}</ul>`;
        }

        function buildSalaryAllowancesGrid(emp) {
            const items = [
                { label: '<?= addslashes(__('basic_salary_label', 'Basic Salary')) ?>', value: emp.basic_salary },
                { label: '<?= addslashes(__('housing_allowance_label', 'Housing')) ?>', value: emp.housing_allowance },
                { label: '<?= addslashes(__('transport_allowance_label', 'Transport')) ?>', value: emp.transport_allowance },
                { label: '<?= addslashes(__('food_allowance_label', 'Food')) ?>', value: emp.food_allowance },
                { label: '<?= addslashes(__('miscellaneous_allowance_label', 'Miscellaneous')) ?>', value: emp.miscellaneous_allowance },
                { label: '<?= addslashes(__('cashier_allowance_label', 'Cashier')) ?>', value: emp.cashier_allowance },
                { label: '<?= addslashes(__('fuel_allowance_label', 'Fuel')) ?>', value: emp.fuel_allowance },
                { label: '<?= addslashes(__('telephone_allowance_label', 'Telephone')) ?>', value: emp.telephone_allowance },
                { label: '<?= addslashes(__('other_allowance_label', 'Other')) ?>', value: emp.other_allowance },
                { label: '<?= addslashes(__('guard_allowance_label', 'Guard')) ?>', value: emp.guard_allowance },
                { label: '<?= addslashes(__('bank_name', 'Bank')) ?>', value: emp.bank_name_s || 'N/A', isText: true },
                { label: '<?= addslashes(__('iban', 'IBAN')) ?>', value: emp.iban || 'N/A', isText: true }
            ];
            let html = '<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0;">';
            for (let i = 0; i < items.length; i++) {
                const item = items[i];
                html += `<div style="display: flex; justify-content: space-between; align-items: center; padding: 10px 12px; border-bottom: 1px solid #eef3f7; border-right: ${(i + 1) % 2 === 0 ? 'none' : '1px solid #eef3f7'};">
                    <span style="color: #637b90; font-weight: 600; min-width: 80px; font-size: 13px;">${esc(item.label)}</span>
                    <span style="color: #173247; font-weight: 700; text-align: right; min-width: 80px; font-size: 13px;">${item.isText ? esc(String(item.value)) : fmt(item.value)}</span>
                </div>`;
            }
            html += '</div>';
            return html;
        }

        function buildFeedbackHtml(feedbacks) {
            if (!Array.isArray(feedbacks) || feedbacks.length === 0) {
                return '';
            }

            const feedbackRowsHtml = feedbacks.map(item => {
                const createdAt = item.created_at
                    ? new Date(item.created_at.replace(' ', 'T')).toLocaleString()
                    : '-';
                const resolvedAt = item.resolved_at
                    ? new Date(item.resolved_at.replace(' ', 'T')).toLocaleString()
                    : '';
                const statusClass = String(item.status || '').toLowerCase() === 'resolved' ? 'success' : 'warning';
                const isResolved = String(item.status || '').toLowerCase() === 'resolved';
                return `
                    <div class="border rounded p-2 mb-2 bg-light">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <strong><i class="fas fa-user-check"></i> ${esc(item.approver_name || '-')}</strong>
                            <span class="badge badge-${statusClass}">${esc(item.status || 'open')}</span>
                        </div>
                        <div class="small text-muted mb-1">
                            <i class="far fa-calendar-alt"></i> ${esc(item.payroll_month || '-')}
                            &nbsp; | &nbsp;
                            <i class="far fa-clock"></i> ${esc(createdAt)}
                        </div>
                        <div>${esc(item.feedback_note || '')}</div>
                        ${isResolved ? `<div class="mt-2 small text-muted"><i class="fas fa-check-circle text-success"></i> <?= addslashes(__('resolved_by', 'Resolved By')) ?>: ${esc(item.resolved_by_name || '-')} ${resolvedAt ? ('| ' + esc(resolvedAt)) : ''}</div>` : ''}
                    </div>
                `;
            }).join('');

            const hasActiveFeedback = feedbacks.some(item => {
                const status = String(item.status || '').trim().toLowerCase();
                return status === 'open' || status === 'submitted' || status === 'pending' || status === '';
            });
            const feedbackInitiallyHiddenClass = hasActiveFeedback ? '' : ' d-none';
            const feedbackToggleHtml = feedbacks.length > 0 && !hasActiveFeedback
                ? `
                    <div class="mb-2 text-end">
                        <button type="button" id="toggleFeedbackBlockBtn" class="btn btn-sm btn-outline-warning">
                            <i class="fas fa-eye"></i> <?= addslashes(__('show_approver_feedback', 'Show Approver Feedback')) ?>
                        </button>
                    </div>
                `
                : '';

            return `
                ${feedbackToggleHtml}
                <div id="approverFeedbackBlock" class="card border-warning shadow-sm mb-3${feedbackInitiallyHiddenClass}">
                    <div class="card-header d-flex justify-content-between align-items-center" style="background:#fff8e1;">
                        <h6 class="mb-0 text-warning"><i class="fas fa-comment-dots"></i> <?= addslashes(__('approver_feedback', 'Approver Feedback')) ?></h6>
                        <span class="badge badge-warning">${feedbacks.length}</span>
                    </div>
                    <div class="card-body py-2" style="max-height: 180px; overflow-y: auto;">
                        ${feedbackRowsHtml}
                    </div>
                </div>
            `;
        }

        async function openEmployeeDetail(empId) {
            const emp = employeeMap[empId];
            if (!emp) return;

            const navigationState = getChecklistEmployeeNavigationState(empId);
            const currentPosition = navigationState.currentIndex >= 0 ? navigationState.currentIndex + 1 : null;

            let feedbacks = [];
            try {
                const detailsParams = new URLSearchParams();
                detailsParams.set('emp_id', String(empId));
                detailsParams.set('month', '<?= addslashes($monthYear) ?>');
                if (checklistScopeState.requestInvNo) {
                    detailsParams.set('request_inv_no', String(checklistScopeState.requestInvNo));
                }
                detailsParams.set('assigned_scope', checklistScopeState.assignedScopeOnly ? '1' : '0');
                detailsParams.set('_', String(Date.now()));
                const response = await fetch('./includes/api/get_payroll_details.php?' + detailsParams.toString());
                if (response.ok) {
                    const data = await response.json();
                    if (data.feedbacks && Array.isArray(data.feedbacks)) {
                        feedbacks = data.feedbacks;
                    }
                }
            } catch (err) {
                console.warn('Failed to fetch feedback:', err);
            }

            const diffClass = Math.abs(Number(emp.net_difference || 0)) < 0.01 ? 'diff-zero' : 'diff-positive';
            const conflictText = emp.is_balanced
                ? '<?= addslashes(__('calculation_ok', 'Calculation OK')) ?>'
                : '<?= addslashes(__('calculation_conflict', 'Calculation Conflict')) ?>';

            const feedbackHtml = buildFeedbackHtml(feedbacks);
            const isEmployeeChecked = !!emp.is_review_checked;
            const requiresChecklistReview = !!emp.requires_checklist_review;

            const detailHtml = `
                ${feedbackHtml}
                <div class="calc-grid">
                    <div class="calc-box"><div class="calc-title"><?= addslashes(__('salary_payment_type_label', 'Payment Type')) ?></div><div class="calc-value">${esc(getPaymentTypeLabel(emp.payment_type))}</div></div>
                    <div class="calc-box"><div class="calc-title"><?= addslashes(__('total_gross_salary_label', 'Gross Salary')) ?></div><div class="calc-value">${fmt(emp.total_gross_salary)}</div></div>
                    <div class="calc-box"><div class="calc-title"><?= addslashes(__('benefits_total', 'Benefits')) ?></div><div class="calc-value">${fmt(emp.total_benefits)}</div></div>
                    <div class="calc-box"><div class="calc-title"><?= addslashes(__('deductions_total', 'Deductions')) ?></div><div class="calc-value">${fmt(emp.total_deductions)}</div></div>
                    <div class="calc-box"><div class="calc-title"><?= addslashes(__('expected_net_salary', 'Expected Net')) ?></div><div class="calc-value">${fmt(emp.expected_net_salary)}</div></div>
                    <div class="calc-box"><div class="calc-title"><?= addslashes(__('net_salary_label', 'Actual Net')) ?></div><div class="calc-value">${fmt(emp.net_salary)}</div></div>
                    <div class="calc-box"><div class="calc-title"><?= addslashes(__('difference', 'Difference')) ?></div><div class="calc-value ${diffClass}">${fmt(emp.net_difference)}</div></div>
                </div>
                <div class="details-grid">
                    <div class="detail-panel">
                        <h5><i class="fas fa-wallet"></i> <?= addslashes(__('salary_allowances_breakdown', 'Salary & Allowances Breakdown')) ?></h5>
                        ${buildSalaryAllowancesGrid(emp)}
                    </div>
                    <div class="detail-panel">
                        <h5><i class="fas fa-id-badge"></i> <?= addslashes(__('employee_details', 'Employee Details')) ?></h5>
                        <ul class="detail-list">
                            <li><span class="label"><?= addslashes(__('name')) ?></span><span class="value">${esc(emp.employee_name)}</span></li>
                            <li><span class="label"><?= addslashes(__('department')) ?></span><span class="value">${esc(emp.department_name)}</span></li>
                            <li><span class="label"><?= addslashes(__('company', 'Company')) ?></span><span class="value">${esc(emp.company_name)}</span></li>
                            <li><span class="label"><?= addslashes(__('iqama', 'Iqama')) ?></span><span class="value">${esc(emp.iqama || 'N/A')}</span></li>
                        </ul>
                    </div>
                    <div class="detail-panel">
                        <h5><i class="fas fa-plus-circle"></i> <?= addslashes(__('benefits_section', 'Benefits')) ?></h5>
                        ${buildItemsList(emp.benefits_list, 'benefit', '<?= addslashes(__('no_benefits_added', 'No benefits added')) ?>')}
                    </div>
                    <div class="detail-panel">
                        <h5><i class="fas fa-minus-circle"></i> <?= addslashes(__('deductions_section', 'Deductions')) ?></h5>
                        ${buildItemsList(emp.deductions_list, 'deduction', '<?= addslashes(__('no_deductions_added', 'No deductions added')) ?>')}
                    </div>
                </div>
            `;

            const modalFooterHtml = `
                <div class="checklist-nav-footer">
                    <div class="checklist-nav-actions">
                        <button type="button" id="prevChecklistEmployeeBtn" class="btn btn-sm btn-outline-primary checklist-nav-btn" ${navigationState.previousEmployee ? '' : 'disabled'}>
                            <i class="fas fa-arrow-left"></i>
                            <span><?= addslashes(__('previous', 'Previous')) ?></span>
                        </button>
                        ${currentPosition ? `<small class="checklist-nav-counter mb-0">${currentPosition} / ${navigationState.employees.length}</small>` : '<span></span>'}
                        <button type="button" id="nextChecklistEmployeeBtn" class="btn btn-sm btn-outline-primary checklist-nav-btn" ${navigationState.nextEmployee ? '' : 'disabled'}>
                            <span><?= addslashes(__('next', 'Next')) ?></span>
                            <i class="fas fa-arrow-right"></i>
                        </button>
                    </div>
                </div>
            `;

            Swal.fire({
                title: `<i class="fas fa-user"></i> ${esc(emp.employee_name)} (${esc(emp.emp_id)})`,
                html: detailHtml,
                footer: modalFooterHtml,
                width: '94vw',
                allowOutsideClick: false,
                stopKeydownPropagation: false,
                keydownListenerCapture: true,
                customClass: {
                    popup: 'swal-landscape-popup',
                    container: 'swal-landscape-container'
                },
                showCloseButton: true,
                showConfirmButton: true,
                confirmButtonText: '<?= addslashes(__('close', 'Close')) ?>',
                focusConfirm: false,
                didOpen: () => {
                    const prevBtn = document.getElementById('prevChecklistEmployeeBtn');
                    const nextBtn = document.getElementById('nextChecklistEmployeeBtn');
                    const toggleFeedbackBtn = document.getElementById('toggleFeedbackBlockBtn');
                    const swalActions = Swal.getActions();

                    attachChecklistModalKeyboardNavigation(navigationState);

                    const empHasOpenFeedback = (Number(emp.open_feedback_count || 0) > 0);
                    if (checklistReviewState.canManage && swalActions && !empHasOpenFeedback && requiresChecklistReview) {
                        let modalReviewBtn = document.getElementById('modalReviewToggleBtn');
                        if (!modalReviewBtn) {
                            modalReviewBtn = document.createElement('button');
                            modalReviewBtn.type = 'button';
                            modalReviewBtn.id = 'modalReviewToggleBtn';
                            modalReviewBtn.setAttribute('data-emp-id', String(empId));
                            modalReviewBtn.addEventListener('click', function() {
                                toggleEmployeeReviewCheck(empId, this);
                            });

                            const closeBtn = Swal.getConfirmButton();
                            if (closeBtn && closeBtn.parentNode === swalActions) {
                                swalActions.insertBefore(modalReviewBtn, closeBtn);
                            } else {
                                swalActions.appendChild(modalReviewBtn);
                            }
                        }

                        updateEmployeeReviewButton(empId, isEmployeeChecked, '');
                    }

                    if (prevBtn) {
                        prevBtn.addEventListener('click', function() {
                            if (!navigationState.previousEmployee) {
                                return;
                            }
                            navigateChecklistEmployeeFromModal(navigationState.previousEmployee);
                        });
                    }

                    if (nextBtn) {
                        nextBtn.addEventListener('click', function() {
                            if (!navigationState.nextEmployee) {
                                return;
                            }
                            navigateChecklistEmployeeFromModal(navigationState.nextEmployee);
                        });
                    }

                    if (toggleFeedbackBtn) {
                        toggleFeedbackBtn.addEventListener('click', function() {
                            const block = document.getElementById('approverFeedbackBlock');
                            if (!block) {
                                return;
                            }

                            const isHidden = block.classList.contains('d-none');
                            if (isHidden) {
                                block.classList.remove('d-none');
                                this.innerHTML = `<i class="fas fa-eye-slash"></i> <?= addslashes(__('hide_approver_feedback', 'Hide Approver Feedback')) ?>`;
                            } else {
                                block.classList.add('d-none');
                                this.innerHTML = `<i class="fas fa-eye"></i> <?= addslashes(__('show_approver_feedback', 'Show Approver Feedback')) ?>`;
                            }
                        });
                    }
                },
                willClose: () => {
                    detachChecklistModalKeyboardNavigation();
                }
            });
        }

        async function openFeedbackDialog(empId, empName) {
            const generatorDisplay = String(payrollGeneratorName || '').trim() !== ''
                ? `${esc(payrollGeneratorName)}${String(payrollGeneratorId || '').trim() !== '' ? ' (' + esc(payrollGeneratorId) + ')' : ''}`
                : (String(payrollGeneratorId || '').trim() !== '' ? esc(payrollGeneratorId) : '<?= addslashes(__('not_available', 'N/A')) ?>');
            const generatorInfoHtml = `<div class="text-left mb-2"><strong><?= addslashes(__('payroll_generator', 'Waiting for Resolve from')) ?>:</strong> ${generatorDisplay}</div>`;

            // Fetch current feedbacks for this employee from the API
            let feedbacks = [];
            try {
                const detailsParams = new URLSearchParams();
                detailsParams.set('emp_id', String(empId));
                detailsParams.set('month', '<?= addslashes($monthYear) ?>');
                if (checklistScopeState.requestInvNo) {
                    detailsParams.set('request_inv_no', String(checklistScopeState.requestInvNo));
                }
                detailsParams.set('assigned_scope', checklistScopeState.assignedScopeOnly ? '1' : '0');
                detailsParams.set('_', String(Date.now()));
                const apiResp = await fetch('./includes/api/get_payroll_details.php?' + detailsParams.toString());
                const apiData = await apiResp.json();
                if (Array.isArray(apiData.feedbacks)) {
                    feedbacks = apiData.feedbacks;
                }
            } catch (e) { /* proceed with empty feedbacks */ }

            const openFeedbacks = feedbacks.filter(f => String(f.status || '').toLowerCase() === 'open');
            const resolvedFeedbacks = feedbacks.filter(f => String(f.status || '').toLowerCase() === 'resolved');

            // If there are open (unresolved) feedbacks — show read-only view, no submission form
            if (openFeedbacks.length > 0) {
                await Swal.fire({
                    title: '<i class="fas fa-comment-dots text-warning"></i> <?= addslashes(__('submitted_feedback', 'Submitted Feedback')) ?>',
                    html: `
                        <div class="text-left mb-2"><strong><?= addslashes(__('employee', 'Employee')) ?>:</strong> ${esc(empName)} (${esc(empId)})</div>
                        ${generatorInfoHtml}
                        <div class="text-left mb-3 small text-muted"><i class="fas fa-info-circle text-warning"></i> <?= addslashes(__('feedback_open_info', 'This employee has open feedback. It must be resolved before new feedback can be sent.')) ?></div>
                        ${buildFeedbackHtml(openFeedbacks)}
                    `,
                    showCancelButton: false,
                    confirmButtonColor: '#6c757d',
                    confirmButtonText: '<?= addslashes(__('close', 'Close')) ?>',
                    allowOutsideClick: false
                });
                return;
            }

            // No open feedbacks — show resolved history at top (if any) + new feedback form
            const resolvedSection = buildFeedbackHtml(resolvedFeedbacks);

            const result = await Swal.fire({
                title: '<?= addslashes(__('send_back_for_fixing', 'Send Back For Fixing')) ?>',
                html: `
                    <div class="text-left mb-2"><strong><?= addslashes(__('employee', 'Employee')) ?>:</strong> ${esc(empName)} (${esc(empId)})</div>
                    ${resolvedSection}
                    <textarea id="singleIssueNote" class="form-control" rows="4" placeholder="<?= addslashes(__('describe_issue_and_required_fix', 'Describe the issue and required fixes')) ?>"></textarea>
                `,
                showCancelButton: true,
                allowOutsideClick: false,
                confirmButtonColor: '#dc3545',
                confirmButtonText: '<?= addslashes(__('send_feedback', 'Send Feedback')) ?>',
                cancelButtonText: '<?= addslashes(__('cancel', 'Cancel')) ?>',
                didOpen: () => {
                    const toggleFeedbackBtn = document.getElementById('toggleFeedbackBlockBtn');
                    if (toggleFeedbackBtn) {
                        toggleFeedbackBtn.addEventListener('click', function() {
                            const block = document.getElementById('approverFeedbackBlock');
                            if (!block) return;
                            const isHidden = block.classList.contains('d-none');
                            if (isHidden) {
                                block.classList.remove('d-none');
                                this.innerHTML = `<i class="fas fa-eye-slash"></i> <?= addslashes(__('hide_approver_feedback', 'Hide Approver Feedback')) ?>`;
                            } else {
                                block.classList.add('d-none');
                                this.innerHTML = `<i class="fas fa-eye"></i> <?= addslashes(__('show_approver_feedback', 'Show Approver Feedback')) ?>`;
                            }
                        });
                    }
                },
                preConfirm: () => {
                    const note = (document.getElementById('singleIssueNote') || {}).value || '';
                    if (!note.trim()) {
                        Swal.showValidationMessage('<?= addslashes(__('issue_note_required', 'Issue note is required.')) ?>');
                        return false;
                    }
                    return { note: note.trim() };
                }
            });

            if (!result.isConfirmed) return;

            const selectedIds = [empId];

            const payload = new URLSearchParams();
            payload.append('action', 'send_back_issues');
            payload.append('request_inv_no', '<?= addslashes($requestInvNo) ?>');
            payload.append('month', '<?= addslashes($monthYear) ?>');
            payload.append('employee_ids', selectedIds.join(','));
            payload.append('note', result.value.note);

            const response = await fetch('./includes/ajaxFile/payroll_approval_handler.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8' },
                body: payload.toString()
            });

            const data = await response.json();
            if (data.status === 'success') {
                if (data.feedback_summary) {
                    feedbackReminderState.totalCount = Number(data.feedback_summary.total_feedback || 0);
                    feedbackReminderState.openCount = Number(data.feedback_summary.open_feedback || 0);
                    feedbackReminderState.pendingCount = Number(data.feedback_summary.pending_followup_count || 0);
                } else {
                    feedbackReminderState.totalCount = Number(feedbackReminderState.totalCount || 0) + selectedIds.length;
                    feedbackReminderState.openCount = Number(feedbackReminderState.openCount || 0) + selectedIds.length;
                    feedbackReminderState.pendingCount = Number(feedbackReminderState.pendingCount || 0) + selectedIds.length;
                }

                syncFollowupButton();
                refreshFeedbackButtons(selectedIds);

                await Swal.fire({
                    icon: 'success',
                    title: '<?= addslashes(__('success', 'Success')) ?>',
                    text: data.message || '<?= addslashes(__('feedback_added_successfully', 'Feedback added successfully.')) ?>',
                    confirmButtonText: '<?= addslashes(__('ok', 'OK')) ?>',
                    allowOutsideClick: false
                });
                return;
            }

            Swal.fire('<?= addslashes(__('error', 'Error')) ?>', data.message || 'Failed to send payroll feedback.', 'error');
        }

        async function openManagerPayrollUploadModal() {
            if (!managerPayrollExcelState.canUpload) {
                Swal.fire('<?= addslashes(__('error', 'Error')) ?>', 'Only manager users can upload this file.', 'error');
                return;
            }

            const result = await Swal.fire({
                title: '<?= addslashes(__('upload_payroll_excel', 'Upload Payroll Excel')) ?>',
                html: `
                    <div style="text-align:left;">
                        <p class="mb-2">Upload one Excel file to return reviewed payroll benefits and deductions for this request.</p>
                        <ol class="pl-3 mb-3 text-danger" style="font-size:13px;">
                            <li>Use the same attached file format with Benefits Import and Deductions Import sheets.</li>
                            <li>Please make sure entered values match BioTime machine records.</li>
                        </ol>
                        <div class="custom-file mb-2">
                            <input type="file" class="custom-file-input" id="managerPayrollExcelFile" accept=".xlsx,.xls">
                            <label class="custom-file-label" for="managerPayrollExcelFile">Choose Excel file</label>
                        </div>
                        <small class="text-muted">Current month: ${managerPayrollExcelState.month}</small>
                    </div>
                `,
                showCancelButton: true,
                confirmButtonText: '<?= addslashes(__('yes_upload_it', 'Yes, Upload it!')) ?>',
                cancelButtonText: '<?= addslashes(__('cancel', 'Cancel')) ?>',
                confirmButtonColor: '#2563eb',
                allowOutsideClick: false,
                didOpen: () => {
                    const fileInput = document.getElementById('managerPayrollExcelFile');
                    const fileLabel = document.querySelector('label[for="managerPayrollExcelFile"]');
                    if (fileInput && fileLabel) {
                        fileInput.addEventListener('change', function() {
                            const file = this.files && this.files[0] ? this.files[0] : null;
                            fileLabel.textContent = file ? file.name : 'Choose Excel file';
                        });
                    }
                },
                preConfirm: () => {
                    const fileInput = document.getElementById('managerPayrollExcelFile');
                    const file = fileInput && fileInput.files && fileInput.files[0] ? fileInput.files[0] : null;
                    if (!file) {
                        Swal.showValidationMessage('Please choose an Excel file first.');
                        return false;
                    }

                    return file;
                }
            });

            if (!result.isConfirmed || !result.value) {
                return;
            }

            const file = result.value;

            // Upload + HR notification/email happen server-side in this same request,
            // which can take a few seconds - show the same processing loader used
            // elsewhere (e.g. Send Payroll Report by Direct Supervisor) instead of just
            // relying on SweetAlert2's small inline confirm-button spinner.
            Swal.fire({
                title: '<?= addslashes(__('processing', 'Processing')) ?>',
                html: '<?= addslashes(__('please_wait_processing', 'Please wait while processing...')) ?>',
                allowOutsideClick: false,
                allowEscapeKey: false,
                showConfirmButton: false,
                didOpen: () => Swal.showLoading()
            });

            try {
                const formData = new FormData();
                formData.append('action', 'upload_manager_payroll_excel');
                formData.append('request_inv_no', managerPayrollExcelState.requestInvNo);
                formData.append('month', managerPayrollExcelState.month);
                formData.append('payroll_excel', file);

                const response = await fetch('./includes/ajaxFile/payroll_approval_handler.php', {
                    method: 'POST',
                    body: formData
                });
                const data = await response.json().catch(() => null);
                Swal.close();

                if (!response.ok || !data || data.status !== 'success') {
                    throw new Error((data && data.message) || 'Failed to upload manager payroll file.');
                }

                await Swal.fire('<?= addslashes(__('success', 'Success')) ?>', data.message || 'Payroll Excel uploaded successfully.', 'success');
                refreshManagerReviewButtonCount();
            } catch (error) {
                Swal.close();
                Swal.fire('<?= addslashes(__('error', 'Error')) ?>', error.message || 'Failed to upload manager payroll file.', 'error');
            }
        }

        async function refreshManagerReviewButtonCount() {
            const reviewBtn = document.getElementById('managerReviewImportBtn');
            const badge = document.getElementById('managerPendingReviewCountBadge');
            if (!reviewBtn || !badge || !managerPayrollExcelState.canReview) {
                return;
            }

            if (!managerPayrollExcelState.requestInvNo || !managerPayrollExcelState.month) {
                badge.style.display = 'none';
                return;
            }

            try {
                const payload = new URLSearchParams();
                payload.append('action', 'list_manager_uploaded_payroll_excel_files');
                payload.append('request_inv_no', managerPayrollExcelState.requestInvNo);
                payload.append('month', managerPayrollExcelState.month);

                const response = await fetch('./includes/ajaxFile/payroll_approval_handler.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8'
                    },
                    body: payload.toString()
                });
                const data = await response.json().catch(() => null);

                if (!response.ok || !data || data.status !== 'success' || !Array.isArray(data.files)) {
                    badge.style.display = 'none';
                    return;
                }

                const pendingCount = data.files.reduce((count, file) => {
                    const reviewedAt = String((file && file.reviewed_at) || '').trim();
                    return count + (reviewedAt === '' ? 1 : 0);
                }, 0);

                badge.textContent = String(pendingCount);
                badge.style.display = pendingCount > 0 ? 'inline-block' : 'none';
            } catch (error) {
                badge.style.display = 'none';
            }
        }

        let payrollImportBenefitsDataTable = null;
        let payrollImportDeductionsDataTable = null;
        let payrollImportCompensationOverrides = {};

        async function loadPayrollImportCompensationOverrides(empIds, monthYear) {
            const uniqueEmpIds = Array.from(new Set((empIds || []).map((empId) => String(empId || '').trim()).filter(Boolean)));
            if (uniqueEmpIds.length === 0) {
                return;
            }

            try {
                const payload = new URLSearchParams();
                payload.append('action', 'get_payroll_compensation_for_employees');
                payload.append('month', String(monthYear || '').trim());
                payload.append('emp_ids', uniqueEmpIds.join(','));

                const response = await fetch('./includes/ajaxFile/payroll_approval_handler.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: payload.toString()
                });
                const data = await response.json().catch(() => null);
                if (response.ok && data && data.status === 'success' && data.compensation) {
                    payrollImportCompensationOverrides = data.compensation;
                }
            } catch (error) {
                // Compensation preview is best-effort; final amounts are always computed server-side on save.
            }
        }

        function escapePayrollImportHtml(value) {
            return String(value == null ? '' : value)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }

        function normalizePayrollImportGosiText(value) {
            return String(value || '')
                .toLowerCase()
                .replace(/[^a-z]/g, '')
                .replace(/(.)\1+/g, '$1');
        }

        function isPayrollImportGosiReason(value) {
            return normalizePayrollImportGosiText(value).includes('gosi');
        }

        function normalizePayrollImportBenefitType(value) {
            const normalized = String(value || '').trim().toLowerCase();
            if (normalized === 'overtime_basic' || normalized === 'overtime_total' || normalized === 'by_hours' || normalized === 'fixed' || normalized === 'by_hours_and_minutes') {
                return normalized;
            }

            if (normalized === 'hourly' || normalized === 'calculated' || normalized === 'hours' || normalized === 'by_hours_and_minutes') {
                return 'by_hours';
            }

            return 'fixed';
        }

        function normalizePayrollImportDeductionType(value) {
            const normalized = String(value || '').trim().toLowerCase();
            if (normalized === 'fixed' || normalized === 'hourly_deduction' || normalized === 'daily_deduction' || normalized === 'by_hours_and_minutes') {
                return normalized;
            }

            if (normalized === 'hourly' || normalized === 'hours' || normalized === 'by_hours_and_minutes') {
                return 'hourly_deduction';
            }

            if (normalized === 'daily' || normalized === 'days') {
                return 'daily_deduction';
            }

            return 'fixed';
        }

        function getPayrollImportEmployeeCompensation(empId) {
            const normalizedEmpId = String(empId || '').trim();
            if (!normalizedEmpId) {
                return {
                    basic: 0,
                    housing: 0,
                    food: 0,
                    totalGross: 0
                };
            }

            const override = payrollImportCompensationOverrides[normalizedEmpId];
            if (override) {
                const basic = parseFloat(override.basic_salary) || 0;
                const housing = parseFloat(override.housing_allowance) || 0;
                const food = parseFloat(override.food_allowance) || 0;
                const transport = parseFloat(override.transport_allowance) || 0;
                const totalFromPayroll = parseFloat(override.total_gross_salary) || 0;
                return {
                    basic,
                    housing,
                    food,
                    totalGross: totalFromPayroll > 0 ? totalFromPayroll : (basic + housing + food + transport)
                };
            }

            if (!Array.isArray(employeesData)) {
                return {
                    basic: 0,
                    housing: 0,
                    food: 0,
                    totalGross: 0
                };
            }

            const employee = employeesData.find((item) => String((item && item.emp_id) || '').trim() === normalizedEmpId) || {};
            const toNumber = (value) => {
                const parsed = parseFloat(value);
                return Number.isFinite(parsed) ? parsed : 0;
            };

            const basic = toNumber(employee.basic_salary || employee.basic);
            const housing = toNumber(employee.housing_allowance || employee.housing);
            const food = toNumber(employee.food_allowance || employee.food);

            const totalFromPayroll = toNumber(employee.total_gross_salary);
            const totalFromComponents = basic
                + housing
                + toNumber(employee.transport_allowance || employee.transport)
                + food
                + toNumber(employee.miscellaneous_allowance || employee.misc)
                + toNumber(employee.cashier_allowance || employee.cashier)
                + toNumber(employee.fuel_allowance || employee.fuel)
                + toNumber(employee.telephone_allowance || employee.tel)
                + toNumber(employee.other_allowance || employee.other)
                + toNumber(employee.guard_allowance || employee.guard);

            return {
                basic,
                housing,
                food,
                totalGross: totalFromPayroll > 0 ? totalFromPayroll : totalFromComponents
            };
        }

        function formatPayrollImportComputedValue(value) {
            const numeric = Number(value);
            if (!Number.isFinite(numeric) || numeric <= 0) {
                return '';
            }
            return numeric.toFixed(2);
        }

        function normalizePayrollImportDurationHours(hours, minutes) {
            const parsedHours = Number(hours);
            const parsedMinutes = Number(minutes);
            let totalHours = 0;

            if (Number.isFinite(parsedHours)) {
                totalHours += parsedHours;
            }

            if (Number.isFinite(parsedMinutes)) {
                totalHours += Math.max(parsedMinutes, 0) / 60;
            }

            return totalHours;
        }

        function computePayrollImportBenefitValue(empId, hours, minutes) {
            const totalHours = normalizePayrollImportDurationHours(hours, minutes);
            if (!Number.isFinite(totalHours) || totalHours <= 0) {
                return '';
            }

            const comp = getPayrollImportEmployeeCompensation(empId);
            if (comp.totalGross <= 0) {
                return '';
            }

            const amount = (((comp.basic / 240) / 2) + (comp.totalGross / 240)) * totalHours;
            return formatPayrollImportComputedValue(amount);
        }

        function computePayrollImportDeductionValue(empId, hours, minutes, days) {
            const parsedDays = Number(days);
            const dayHours = Number.isFinite(parsedDays) && parsedDays > 0 ? parsedDays * 8 : 0;
            const totalHours = normalizePayrollImportDurationHours(hours, minutes) + dayHours;
            if (!Number.isFinite(totalHours) || totalHours <= 0) {
                return '';
            }

            const comp = getPayrollImportEmployeeCompensation(empId);
            const deductibleSalary = Math.max(comp.totalGross - comp.food, 0);

            if (deductibleSalary <= 0) {
                return '';
            }

            const hourlyRate = deductibleSalary / 240;
            return formatPayrollImportComputedValue(hourlyRate * totalHours);
        }

        function normalizePayrollImportDeductionDurationHours(hours, minutes, days) {
            const parsedDays = Number(days);
            const dayHours = Number.isFinite(parsedDays) && parsedDays > 0 ? parsedDays * 8 : 0;
            return normalizePayrollImportDurationHours(hours, minutes) + dayHours;
        }

        function normalizeReviewedPayrollImportRow(row) {
            const normalizedRow = {
                checkpoint_code: String((row && row.checkpoint_code) || '').trim(),
                emp_id: String((row && row.emp_id) || '').trim(),
                month: String((row && row.month) || '').trim(),
                benefit_type: 'by_hours',
                overtime_value: String((row && row.overtime_value) || '').trim(),
                overtime_hours: String((row && row.overtime_hours) || (row && row.benefit_hours) || '').trim(),
                overtime_minutes: String((row && row.overtime_minutes) || (row && row.benefit_minutes) || '').trim(),
                overtime_reason: String((row && row.overtime_reason) || (row && row.benefit_reason) || '').trim(),
                deduction_type: 'hourly_deduction',
                deduction_value: String((row && row.deduction_value) || '').trim(),
                deduction_days: String((row && row.deduction_days) || '').trim(),
                deduction_hours: String((row && row.deduction_hours) || '').trim(),
                deduction_minutes: String((row && row.deduction_minutes) || '').trim(),
                deduction_reason: String((row && row.deduction_reason) || '').trim(),
            };

            if (isPayrollImportGosiReason(normalizedRow.deduction_reason)) {
                normalizedRow.deduction_type = 'hourly_deduction';
                normalizedRow.deduction_value = '';
                normalizedRow.deduction_days = '';
                normalizedRow.deduction_hours = '';
                normalizedRow.deduction_minutes = '';
                normalizedRow.deduction_reason = '';
            }

            const benefitDurationHours = normalizePayrollImportDurationHours(normalizedRow.overtime_hours, normalizedRow.overtime_minutes);
            if (benefitDurationHours <= 0 || normalizedRow.overtime_reason === '') {
                normalizedRow.overtime_value = '';
                normalizedRow.overtime_hours = '';
                normalizedRow.overtime_minutes = '';
                normalizedRow.overtime_reason = '';
            } else {
                normalizedRow.overtime_value = Number(normalizedRow.overtime_value) > 0 ? normalizedRow.overtime_value : '';
            }

            const deductionDurationHours = normalizePayrollImportDeductionDurationHours(normalizedRow.deduction_hours, normalizedRow.deduction_minutes, normalizedRow.deduction_days);
            if (deductionDurationHours <= 0 || normalizedRow.deduction_reason === '') {
                normalizedRow.deduction_value = '';
                normalizedRow.deduction_days = '';
                normalizedRow.deduction_hours = '';
                normalizedRow.deduction_minutes = '';
                normalizedRow.deduction_reason = '';
            } else {
                normalizedRow.deduction_value = Number(normalizedRow.deduction_value) > 0 ? normalizedRow.deduction_value : '';
            }

            return normalizedRow;
        }

        function rowHasReviewedPayrollImportEntry(row) {
            const benefitDurationHours = normalizePayrollImportDurationHours(row.overtime_hours, row.overtime_minutes);
            const deductionDurationHours = normalizePayrollImportDeductionDurationHours(row.deduction_hours, row.deduction_minutes, row.deduction_days);
            const hasBenefitEntry = benefitDurationHours > 0 || (Number(row.overtime_value) > 0);
            const hasDeductionEntry = deductionDurationHours > 0 || (Number(row.deduction_value) > 0);
            return hasBenefitEntry || hasDeductionEntry;
        }

        function buildPayrollImportReviewTable(rows) {
            const monthValue = rows.length > 0 ? String(rows[0].month || '').trim() : '';

            const employeeCell = (row) => `
                <td>
                    <input type="hidden" class="payroll-import-review-input" data-field="checkpoint_code" value="${escapePayrollImportHtml(row.checkpoint_code || '')}">
                    <div class="payroll-import-review-employee-cell">
                        <span class="payroll-import-review-employee-id">${escapePayrollImportHtml(row.emp_id || '')}</span>
                    </div>
                    <input type="hidden" class="payroll-import-review-input" data-field="emp_id" value="${escapePayrollImportHtml(row.emp_id || '')}">
                    <input type="hidden" class="payroll-import-review-input" data-field="month" value="${escapePayrollImportHtml(row.month || '')}">
                </td>
            `;

            const rowHasBenefitEntry = (row) => normalizePayrollImportDurationHours(row.overtime_hours, row.overtime_minutes) > 0 || Number(row.overtime_value || 0) > 0;
            const rowHasDeductionEntry = (row) => normalizePayrollImportDeductionDurationHours(row.deduction_hours, row.deduction_minutes, row.deduction_days) > 0 || Number(row.deduction_value || 0) > 0;

            const benefitRows = rows
                .map((row, index) => ({ row, index }))
                .filter(({ row }) => rowHasBenefitEntry(row))
                .map(({ row, index }) => `
                <tr data-row-index="${index}">
                    <td class="text-center">${index + 1}</td>
                    ${employeeCell(row)}
                    <td><input type="hidden" class="payroll-import-review-input" data-field="benefit_type" value="${escapePayrollImportHtml(normalizePayrollImportBenefitType(row.benefit_type || ''))}"><input type="text" class="form-control form-control-sm payroll-import-review-input" data-field="overtime_hours" value="${escapePayrollImportHtml(row.overtime_hours || '')}"></td>
                    <td><input type="text" class="form-control form-control-sm payroll-import-review-input" data-field="overtime_minutes" value="${escapePayrollImportHtml(row.overtime_minutes || '')}"></td>
                    <td><input type="text" class="form-control form-control-sm payroll-import-review-input" data-field="overtime_value" value="${escapePayrollImportHtml(row.overtime_value || '')}" readonly></td>
                    <td>
                        <div class="input-group input-group-sm">
                            <input type="text" class="form-control form-control-sm payroll-import-review-input" data-field="overtime_reason" value="${escapePayrollImportHtml(row.overtime_reason || '')}">
                            <div class="input-group-append">
                                <button type="button" class="btn btn-outline-danger payroll-import-clear-entry-btn" data-entry-type="overtime"><i class="fa fa-times"></i></button>
                            </div>
                        </div>
                    </td>
                </tr>
            `).join('');

            const deductionRows = rows
                .map((row, index) => ({ row, index }))
                .filter(({ row }) => rowHasDeductionEntry(row))
                .map(({ row, index }) => `
                <tr data-row-index="${index}">
                    <td class="text-center">${index + 1}</td>
                    ${employeeCell(row)}
                    <td><input type="hidden" class="payroll-import-review-input" data-field="deduction_type" value="${escapePayrollImportHtml(normalizePayrollImportDeductionType(row.deduction_type || ''))}"><input type="text" class="form-control form-control-sm payroll-import-review-input" data-field="deduction_days" value="${escapePayrollImportHtml(row.deduction_days || '')}"></td>
                    <td><input type="text" class="form-control form-control-sm payroll-import-review-input" data-field="deduction_hours" value="${escapePayrollImportHtml(row.deduction_hours || '')}"></td>
                    <td><input type="text" class="form-control form-control-sm payroll-import-review-input" data-field="deduction_minutes" value="${escapePayrollImportHtml(row.deduction_minutes || '')}"></td>
                    <td><input type="text" class="form-control form-control-sm payroll-import-review-input" data-field="deduction_value" value="${escapePayrollImportHtml(row.deduction_value || '')}" readonly></td>
                    <td>
                        <div class="input-group input-group-sm">
                            <input type="text" class="form-control form-control-sm payroll-import-review-input" data-field="deduction_reason" value="${escapePayrollImportHtml(row.deduction_reason || '')}">
                            <div class="input-group-append">
                                <button type="button" class="btn btn-outline-danger payroll-import-clear-entry-btn" data-entry-type="deduction"><i class="fa fa-times"></i></button>
                            </div>
                        </div>
                    </td>
                </tr>
            `).join('');

            return `
                <div style="text-align:left;">
                    <div class="payroll-import-review-summary">
                        <div><strong>Review Imported Rows</strong></div>
                        <div>Processed Rows: <strong>${rows.length}</strong></div>
                        <div>Month: <strong>${escapePayrollImportHtml(monthValue || '--')}</strong></div>
                        <div>Review and adjust benefit and deduction values before the final save.</div>
                    </div>
                    <br />
                    <ul class="nav nav-tabs" id="payrollImportReviewTabs" role="tablist">
                        <li class="nav-item">
                            <a class="nav-link active" id="payrollImportBenefitsTab" data-toggle="tab" href="#payrollImportBenefitsPane" role="tab">Benefits</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="payrollImportDeductionsTab" data-toggle="tab" href="#payrollImportDeductionsPane" role="tab">Deductions</a>
                        </li>
                    </ul>
                    <div class="tab-content" style="margin-top:12px;">
                        <div class="tab-pane fade show active" id="payrollImportBenefitsPane" role="tabpanel">
                            <div class="payroll-import-review-table-wrap">
                                <table id="payrollImportBenefitsTable" class="table table-bordered table-sm payroll-import-review-table">
                                    <thead>
                                        <tr>
                                            <th style="width:60px;">#</th>
                                            <th style="width:190px;">Employee</th>
                                            <th style="width:95px;">Hours</th>
                                            <th style="width:95px;">Minutes</th>
                                            <th style="width:150px;">Benefits</th>
                                            <th style="width:320px;">Overtime Reason</th>
                                        </tr>
                                    </thead>
                                    <tbody>${benefitRows}</tbody>
                                </table>
                            </div>
                        </div>
                        <div class="tab-pane fade" id="payrollImportDeductionsPane" role="tabpanel">
                            <div class="payroll-import-review-table-wrap">
                                <table id="payrollImportDeductionsTable" class="table table-bordered table-sm payroll-import-review-table">
                                    <thead>
                                        <tr>
                                            <th style="width:60px;">#</th>
                                            <th style="width:190px;">Employee</th>
                                            <th style="width:80px;">Days</th>
                                            <th style="width:95px;">Hours</th>
                                            <th style="width:95px;">Minutes</th>
                                            <th style="width:150px;">Deduction</th>
                                            <th style="width:320px;">Deduction Reason</th>
                                        </tr>
                                    </thead>
                                    <tbody>${deductionRows}</tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        }

        function collectReviewedPayrollImportRows() {
            const benefitRowElements = document.querySelectorAll('#payrollImportBenefitsTable tbody tr[data-row-index]');
            const deductionRowElements = document.querySelectorAll('#payrollImportDeductionsTable tbody tr[data-row-index]');

            const rowIndexes = Array.from(new Set([
                ...Array.from(benefitRowElements).map((el) => el.dataset.rowIndex),
                ...Array.from(deductionRowElements).map((el) => el.dataset.rowIndex)
            ]));

            const getFieldValue = (row, field) => {
                if (!row) return '';
                const input = row.querySelector(`.payroll-import-review-input[data-field="${field}"]`);
                return input ? String(input.value || '').trim() : '';
            };

            return rowIndexes.map((rowIndex) => {
                const benefitRow = document.querySelector(`#payrollImportBenefitsTable tbody tr[data-row-index="${rowIndex}"]`);
                const deductionRow = document.querySelector(`#payrollImportDeductionsTable tbody tr[data-row-index="${rowIndex}"]`);

                return normalizeReviewedPayrollImportRow({
                    checkpoint_code: getFieldValue(benefitRow, 'checkpoint_code') || getFieldValue(deductionRow, 'checkpoint_code'),
                    emp_id: getFieldValue(benefitRow, 'emp_id') || getFieldValue(deductionRow, 'emp_id'),
                    month: getFieldValue(benefitRow, 'month') || getFieldValue(deductionRow, 'month'),
                    benefit_type: getFieldValue(benefitRow, 'benefit_type'),
                    overtime_value: getFieldValue(benefitRow, 'overtime_value'),
                    overtime_hours: getFieldValue(benefitRow, 'overtime_hours'),
                    overtime_minutes: getFieldValue(benefitRow, 'overtime_minutes'),
                    overtime_reason: getFieldValue(benefitRow, 'overtime_reason'),
                    deduction_type: getFieldValue(deductionRow, 'deduction_type'),
                    deduction_value: getFieldValue(deductionRow, 'deduction_value'),
                    deduction_days: getFieldValue(deductionRow, 'deduction_days'),
                    deduction_hours: getFieldValue(deductionRow, 'deduction_hours'),
                    deduction_minutes: getFieldValue(deductionRow, 'deduction_minutes'),
                    deduction_reason: getFieldValue(deductionRow, 'deduction_reason'),
                });
            });
        }

        function applyBenefitRowRules(row) {
            if (!row) {
                return;
            }

            const getInput = (field) => row.querySelector(`.payroll-import-review-input[data-field="${field}"]`);
            const empIdInput = getInput('emp_id');
            const empId = empIdInput ? String(empIdInput.value || '').trim() : '';

            const overtimeHoursInput = getInput('overtime_hours');
            const overtimeMinutesInput = getInput('overtime_minutes');
            const overtimeValueInput = getInput('overtime_value');
            const overtimeReasonInput = getInput('overtime_reason');
            const benefitDurationHours = normalizePayrollImportDurationHours(
                overtimeHoursInput ? overtimeHoursInput.value : '',
                overtimeMinutesInput ? overtimeMinutesInput.value : ''
            );

            if (overtimeHoursInput) {
                overtimeHoursInput.readOnly = false;
                overtimeHoursInput.classList.remove('bg-light');
            }
            if (overtimeMinutesInput) {
                overtimeMinutesInput.readOnly = false;
                overtimeMinutesInput.classList.remove('bg-light');
            }
            if (overtimeValueInput) {
                overtimeValueInput.readOnly = true;
                overtimeValueInput.classList.add('bg-light');
            }

            if (overtimeReasonInput) {
                overtimeReasonInput.readOnly = false;
                overtimeReasonInput.classList.remove('bg-light');
            }

            if (Number.isFinite(benefitDurationHours) && benefitDurationHours > 0) {
                if (overtimeValueInput) {
                    overtimeValueInput.value = computePayrollImportBenefitValue(empId, overtimeHoursInput ? overtimeHoursInput.value : '', overtimeMinutesInput ? overtimeMinutesInput.value : '');
                }
                if (overtimeReasonInput && overtimeReasonInput.value === '') {
                    overtimeReasonInput.value = 'Hourly benefit';
                }
            } else {
                if (overtimeValueInput) {
                    overtimeValueInput.value = '';
                }
                if (overtimeReasonInput) {
                    overtimeReasonInput.value = '';
                }
            }
        }

        function applyDeductionRowRules(row) {
            if (!row) {
                return;
            }

            const getInput = (field) => row.querySelector(`.payroll-import-review-input[data-field="${field}"]`);
            const empIdInput = getInput('emp_id');
            const empId = empIdInput ? String(empIdInput.value || '').trim() : '';

            const deductionDaysInput = getInput('deduction_days');
            const deductionHoursInput = getInput('deduction_hours');
            const deductionMinutesInput = getInput('deduction_minutes');
            const deductionValueInput = getInput('deduction_value');
            const deductionReasonInput = getInput('deduction_reason');
            const deductionDurationHours = normalizePayrollImportDeductionDurationHours(
                deductionHoursInput ? deductionHoursInput.value : '',
                deductionMinutesInput ? deductionMinutesInput.value : '',
                deductionDaysInput ? deductionDaysInput.value : ''
            );

            if (deductionDaysInput) {
                deductionDaysInput.readOnly = false;
                deductionDaysInput.classList.remove('bg-light');
            }
            if (deductionHoursInput) {
                deductionHoursInput.readOnly = false;
                deductionHoursInput.classList.remove('bg-light');
            }
            if (deductionMinutesInput) {
                deductionMinutesInput.readOnly = false;
                deductionMinutesInput.classList.remove('bg-light');
            }
            if (deductionValueInput) {
                deductionValueInput.readOnly = true;
                deductionValueInput.classList.add('bg-light');
            }
            if (deductionReasonInput) {
                deductionReasonInput.readOnly = false;
                deductionReasonInput.classList.remove('bg-light');
            }

            if (Number.isFinite(deductionDurationHours) && deductionDurationHours > 0) {
                if (deductionValueInput) {
                    deductionValueInput.value = computePayrollImportDeductionValue(empId, deductionHoursInput ? deductionHoursInput.value : '', deductionMinutesInput ? deductionMinutesInput.value : '', deductionDaysInput ? deductionDaysInput.value : '');
                }
                if (deductionReasonInput && deductionReasonInput.value === '') {
                    deductionReasonInput.value = 'Hourly deduction';
                }
            } else {
                if (deductionValueInput) {
                    deductionValueInput.value = '';
                }
                if (deductionReasonInput) {
                    deductionReasonInput.value = '';
                }
            }
        }

        async function openPayrollImportReviewModal(rows, defaultMonth) {
            const reviewResult = await Swal.fire({
                title: 'Review Imported Rows',
                html: buildPayrollImportReviewTable(rows),
                width: '92%',
                showCancelButton: true,
                confirmButtonText: 'Save After Review',
                cancelButtonText: '<?= addslashes(__('cancel', 'Cancel')) ?>',
                confirmButtonColor: '#2563eb',
                allowOutsideClick: false,
                focusConfirm: false,
                didOpen: () => {
                    const benefitsTableElement = $('#payrollImportBenefitsTable');
                    const deductionsTableElement = $('#payrollImportDeductionsTable');

                    const bindClearButton = (tableElement, applyRules) => {
                        tableElement.off('click', '.payroll-import-clear-entry-btn').on('click', '.payroll-import-clear-entry-btn', function() {
                            const entryType = String(this.dataset.entryType || '').trim();
                            const row = this.closest('tr');
                            if (!row || !entryType) {
                                return;
                            }

                            const valueInput = row.querySelector(`.payroll-import-review-input[data-field="${entryType}_value"]`);
                            const daysInput = row.querySelector(`.payroll-import-review-input[data-field="${entryType}_days"]`);
                            const hoursInput = row.querySelector(`.payroll-import-review-input[data-field="${entryType}_hours"]`);
                            const minutesInput = row.querySelector(`.payroll-import-review-input[data-field="${entryType}_minutes"]`);
                            const reasonInput = row.querySelector(`.payroll-import-review-input[data-field="${entryType}_reason"]`);

                            if (valueInput) valueInput.value = '';
                            if (daysInput) daysInput.value = '';
                            if (hoursInput) hoursInput.value = '';
                            if (minutesInput) minutesInput.value = '';
                            if (reasonInput) reasonInput.value = '';

                            applyRules(row);
                        });
                    };

                    const bindInputListener = (tableElement, applyRules) => {
                        tableElement
                            .off('input change', '.payroll-import-review-input')
                            .on('input change', '.payroll-import-review-input', function() {
                                applyRules(this.closest('tr'));
                            });
                    };

                    bindClearButton(benefitsTableElement, applyBenefitRowRules);
                    bindClearButton(deductionsTableElement, applyDeductionRowRules);
                    bindInputListener(benefitsTableElement, applyBenefitRowRules);
                    bindInputListener(deductionsTableElement, applyDeductionRowRules);

                    benefitsTableElement.find('tbody tr').each(function() {
                        applyBenefitRowRules(this);
                    });
                    deductionsTableElement.find('tbody tr').each(function() {
                        applyDeductionRowRules(this);
                    });

                    if ($.fn.DataTable) {
                        [benefitsTableElement, deductionsTableElement].forEach((tableElement) => {
                            if (tableElement.length && $.fn.DataTable.isDataTable(tableElement)) {
                                tableElement.DataTable().destroy();
                            }
                        });

                        const baseDataTableOptions = {
                            pageLength: 10,
                            lengthMenu: [[-1, 10, 25, 50, 100], ['All', 10, 25, 50, 100]],
                            order: [[1, 'asc']],
                            autoWidth: false,
                            responsive: false,
                            scrollX: true,
                            destroy: true
                        };

                        payrollImportBenefitsDataTable = benefitsTableElement.DataTable(Object.assign({}, baseDataTableOptions, {
                            language: { emptyTable: 'No benefit records available' }
                        }));
                        payrollImportDeductionsDataTable = deductionsTableElement.DataTable(Object.assign({}, baseDataTableOptions, {
                            language: { emptyTable: 'No deduction records available' }
                        }));

                        payrollImportBenefitsDataTable.columns.adjust().draw(false);
                    }

                    $('#payrollImportReviewTabs a[data-toggle="tab"]').off('shown.bs.tab').on('shown.bs.tab', function(event) {
                        const targetId = $(event.target).attr('href');
                        if (targetId === '#payrollImportDeductionsPane' && payrollImportDeductionsDataTable) {
                            payrollImportDeductionsDataTable.columns.adjust().draw(false);
                        } else if (targetId === '#payrollImportBenefitsPane' && payrollImportBenefitsDataTable) {
                            payrollImportBenefitsDataTable.columns.adjust().draw(false);
                        }
                    });
                },
                willClose: () => {
                    $('#payrollImportBenefitsTable').off('click', '.payroll-import-clear-entry-btn').off('input change', '.payroll-import-review-input');
                    $('#payrollImportDeductionsTable').off('click', '.payroll-import-clear-entry-btn').off('input change', '.payroll-import-review-input');
                    $('#payrollImportReviewTabs a[data-toggle="tab"]').off('shown.bs.tab');
                    payrollImportBenefitsDataTable = null;
                    payrollImportDeductionsDataTable = null;
                },
                preConfirm: () => {
                    const reviewedRows = collectReviewedPayrollImportRows();
                    const rowsToSave = reviewedRows.filter(rowHasReviewedPayrollImportEntry);

                    if (!Array.isArray(rowsToSave) || rowsToSave.length === 0) {
                        Swal.showValidationMessage('No valid rows were found to save.');
                        return false;
                    }

                    const checkpointCodes = Array.from(new Set(rowsToSave.map(row => String(row.checkpoint_code || '').trim()).filter(Boolean)));
                    if (checkpointCodes.length !== 1) {
                        Swal.showValidationMessage('The selected file is not valid. Please upload a valid payroll import file.');
                        return false;
                    }

                    const invalidRowIndex = rowsToSave.findIndex((row) => {
                        return row.checkpoint_code === '' || row.emp_id === '' || row.month === '';
                    });

                    if (invalidRowIndex !== -1) {
                        Swal.showValidationMessage('Each row must have Employee ID, Month, and at least one overtime or deduction entry.');
                        return false;
                    }

                    return {
                        rows: rowsToSave,
                        defaultMonth
                    };
                }
            });

            if (!reviewResult.isConfirmed || !reviewResult.value || !Array.isArray(reviewResult.value.rows)) {
                return null;
            }

            return reviewResult.value;
        }

        async function reviewManagerUploadedPayrollExcel() {
            if (!managerPayrollExcelState.canReview) {
                Swal.fire('<?= addslashes(__('error', 'Error')) ?>', 'Only HR Payroll and HR Senior BP can review and import manager files.', 'error');
                return;
            }

            Swal.fire({
                title: 'Loading manager files',
                html: 'Please wait while uploaded files are prepared for review...',
                didOpen: () => Swal.showLoading(),
                allowOutsideClick: false,
                allowEscapeKey: false
            });

            let filesResponse;
            try {
                const payload = new URLSearchParams();
                payload.append('action', 'list_manager_uploaded_payroll_excel_files');
                payload.append('request_inv_no', managerPayrollExcelState.requestInvNo);
                payload.append('month', managerPayrollExcelState.month);

                const response = await fetch('./includes/ajaxFile/payroll_approval_handler.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: payload.toString()
                });
                filesResponse = await response.json().catch(() => null);
                Swal.close();

                if (!response.ok || !filesResponse || filesResponse.status !== 'success' || !Array.isArray(filesResponse.files) || filesResponse.files.length === 0) {
                    throw new Error((filesResponse && filesResponse.message) || 'No manager uploaded file found for review.');
                }
            } catch (error) {
                Swal.close();
                Swal.fire('<?= addslashes(__('error', 'Error')) ?>', error.message || 'Failed to load manager uploaded files.', 'error');
                return;
            }

            const availableFiles = filesResponse.files.filter((file) => !!file && file.is_available !== false);
            if (availableFiles.length === 0) {
                Swal.fire('<?= addslashes(__('error', 'Error')) ?>', 'All uploaded files are unavailable on server. Please ask manager to upload again.', 'error');
                return;
            }

            let firstSelectableMarked = false;
            const fileTableRows = availableFiles.map((file) => {
                const isReviewed = String(file.reviewed_at || '').trim() !== '';
                const statusBadge = isReviewed
                    ? '<span class="badge badge-success">Reviewed Completed</span>'
                    : '<span class="badge badge-warning">Pending Review</span>';
                const canSelect = !isReviewed;
                const shouldCheck = canSelect && !firstSelectableMarked;
                if (shouldCheck) {
                    firstSelectableMarked = true;
                }

                return `
                    <tr ${isReviewed ? 'style="opacity:0.75;"' : ''} data-file-id="${escapePayrollImportHtml(String(file.file_id || ''))}">
                        <td class="text-center">
                            <input type="radio" name="managerPayrollReviewFileRow" value="${escapePayrollImportHtml(String(file.file_id || ''))}" ${shouldCheck ? 'checked' : ''} ${canSelect ? '' : 'disabled'}>
                        </td>
                        <td>${escapePayrollImportHtml(String(file.company_name || '-'))}</td>
                        <td>${escapePayrollImportHtml(String(file.original_name || file.stored_name || 'Uploaded file'))}</td>
                        <td>${escapePayrollImportHtml(String(file.uploaded_by_name || file.uploaded_by || 'N/A'))}</td>
                        <td>${escapePayrollImportHtml(String(file.uploaded_at || 'N/A'))}</td>
                        <td>${statusBadge}</td>
                        <td>${isReviewed ? escapePayrollImportHtml(String(file.reviewed_at || '')) : '-'}</td>
                        <td class="text-center">${canSelect ? `<button type="button" class="btn btn-sm btn-outline-danger payroll-delete-uploaded-file-btn" data-file-id="${escapePayrollImportHtml(String(file.file_id || ''))}" title="Delete uploaded file"><i class="fa fa-trash"></i></button>` : ''}</td>
                    </tr>
                `;
            }).join('');

            const pickResult = await Swal.fire({
                title: 'Select Manager Uploaded File',
                width: '85%',
                html: `
                    <div style="text-align:left;">
                        <p class="mb-2">Only files uploaded for <strong>${escapePayrollImportHtml(String(managerPayrollExcelState.month || ''))}</strong> are shown below.</p>
                        <div style="max-height:340px; overflow:auto; border:1px solid #e2e8f0; border-radius:8px;">
                            <table class="table table-sm table-bordered mb-0" id="managerPayrollFilePickerTable">
                                <thead class="thead-light">
                                    <tr>
                                        <th style="width:90px;" class="text-center">Select</th>
                                        <th>Company Name</th>
                                        <th>File Name</th>
                                        <th>Uploaded By</th>
                                        <th>Uploaded At</th>
                                        <th>Status</th>
                                        <th>Reviewed At</th>
                                        <th style="width:70px;" class="text-center">Delete</th>
                                    </tr>
                                </thead>
                                <tbody>${fileTableRows}</tbody>
                            </table>
                        </div>
                    </div>
                `,
                showCancelButton: true,
                confirmButtonText: 'Review Selected File',
                cancelButtonText: '<?= addslashes(__('cancel', 'Cancel')) ?>',
                confirmButtonColor: '#2563eb',
                allowOutsideClick: false,
                didOpen: () => {
                    document.querySelectorAll('#managerPayrollFilePickerTable .payroll-delete-uploaded-file-btn').forEach((btn) => {
                        btn.addEventListener('click', async function() {
                            const fileId = String(this.dataset.fileId || '').trim();
                            if (!fileId || !window.confirm('Delete this uploaded file? This cannot be undone.')) {
                                return;
                            }

                            this.disabled = true;
                            try {
                                const deletePayload = new URLSearchParams();
                                deletePayload.append('action', 'delete_manager_uploaded_payroll_excel_file');
                                deletePayload.append('request_inv_no', managerPayrollExcelState.requestInvNo);
                                deletePayload.append('month', managerPayrollExcelState.month);
                                deletePayload.append('file_id', fileId);

                                const deleteResponse = await fetch('./includes/ajaxFile/payroll_approval_handler.php', {
                                    method: 'POST',
                                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                                    body: deletePayload.toString()
                                });
                                const deleteData = await deleteResponse.json().catch(() => null);
                                if (!deleteResponse.ok || !deleteData || deleteData.status !== 'success') {
                                    throw new Error((deleteData && deleteData.message) || 'Failed to delete uploaded file.');
                                }

                                const row = this.closest('tr');
                                if (row) {
                                    row.remove();
                                }
                                refreshManagerReviewButtonCount();
                            } catch (error) {
                                this.disabled = false;
                                window.alert(error.message || 'Failed to delete uploaded file.');
                            }
                        });
                    });
                },
                preConfirm: () => {
                    const selectedRadio = document.querySelector('input[name="managerPayrollReviewFileRow"]:checked');
                    const selectedFileId = selectedRadio ? String(selectedRadio.value || '').trim() : '';
                    if (!selectedFileId) {
                        Swal.showValidationMessage('Please select an uploaded file.');
                        return false;
                    }
                    return selectedFileId;
                }
            });

            if (!pickResult.isConfirmed || !pickResult.value) {
                return;
            }

            const selectedFileId = String(pickResult.value);
            const selectedFileMeta = availableFiles.find((file) => String(file.file_id || '') === selectedFileId) || null;

            Swal.fire({
                title: 'Loading selected file',
                html: 'Please wait while selected file is prepared for review...',
                didOpen: () => Swal.showLoading(),
                allowOutsideClick: false,
                allowEscapeKey: false
            });

            let fetched;
            try {
                const payload = new URLSearchParams();
                payload.append('action', 'get_manager_uploaded_payroll_excel_rows');
                payload.append('request_inv_no', managerPayrollExcelState.requestInvNo);
                payload.append('month', managerPayrollExcelState.month);
                payload.append('file_id', selectedFileId);

                const response = await fetch('./includes/ajaxFile/payroll_approval_handler.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: payload.toString()
                });
                fetched = await response.json().catch(() => null);
                Swal.close();

                if (!response.ok || !fetched || fetched.status !== 'success' || !Array.isArray(fetched.rows)) {
                    throw new Error((fetched && fetched.message) || 'No manager uploaded file found for review.');
                }
            } catch (error) {
                Swal.close();
                Swal.fire('<?= addslashes(__('error', 'Error')) ?>', error.message || 'Failed to load manager uploaded file.', 'error');
                return;
            }

            const reviewRows = fetched.rows.map((row) => {
                const overtimeValue = String((row && row.benefit_value) || (row && row.overtime_value) || '').trim();
                const overtimeHours = String((row && row.benefit_hours) || (row && row.overtime_hours) || '').trim();
                const overtimeMinutes = String((row && row.benefit_minutes) || (row && row.overtime_minutes) || '').trim();
                const overtimeReason = String((row && row.benefit_reason) || (row && row.overtime_reason) || '').trim();
                const deductionValue = String((row && row.deduction_value) || '').trim();
                const deductionDays = String((row && row.deduction_days) || '').trim();
                const deductionHours = String((row && row.deduction_hours) || '').trim();
                const deductionMinutes = String((row && row.deduction_minutes) || '').trim();
                const deductionReason = String((row && row.deduction_reason) || '').trim();

                const hasBenefitData = Number(overtimeValue || 0) > 0 || Number(overtimeHours || 0) > 0 || Number(overtimeMinutes || 0) > 0 || overtimeReason !== '';
                const hasDeductionData = Number(deductionValue || 0) > 0 || Number(deductionDays || 0) > 0 || Number(deductionHours || 0) > 0 || Number(deductionMinutes || 0) > 0 || deductionReason !== '';

                return {
                    checkpoint_code: String((row && row.checkpoint_code) || '').trim(),
                    emp_id: String((row && row.emp_id) || '').trim(),
                    month: String((row && row.month) || managerPayrollExcelState.month).trim(),
                    benefit_type: hasBenefitData ? normalizePayrollImportBenefitType((row && row.benefit_type) || '') : '',
                    overtime_value: overtimeValue,
                    overtime_hours: overtimeHours,
                    overtime_minutes: overtimeMinutes,
                    overtime_reason: overtimeReason,
                    deduction_type: hasDeductionData ? normalizePayrollImportDeductionType((row && row.deduction_type) || '') : '',
                    deduction_value: deductionValue,
                    deduction_days: deductionDays,
                    deduction_hours: deductionHours,
                    deduction_minutes: deductionMinutes,
                    deduction_reason: deductionReason
                };
            }).filter(rowHasReviewedPayrollImportEntry);

            payrollImportCompensationOverrides = {};
            await loadPayrollImportCompensationOverrides(reviewRows.map((row) => row.emp_id), managerPayrollExcelState.month);

            const reviewResult = await openPayrollImportReviewModal(reviewRows, managerPayrollExcelState.month);

            if (!reviewResult || !Array.isArray(reviewResult.rows)) {
                return;
            }

            Swal.fire({
                title: 'Importing payroll data',
                html: 'Please wait while payroll data is updated...',
                didOpen: () => Swal.showLoading(),
                allowOutsideClick: false,
                allowEscapeKey: false
            });

            try {
                const uploadedCheckpointCode = String(fetched.checkpoint_code || '').trim();
                if (uploadedCheckpointCode === '') {
                    throw new Error('The uploaded file is not valid.');
                }

                const rowsWithOriginalCheckpoint = reviewResult.rows.map((row) => ({
                    checkpoint_code: String(row.checkpoint_code || uploadedCheckpointCode).trim(),
                    emp_id: String(row.emp_id || '').trim(),
                    month: String(row.month || managerPayrollExcelState.month).trim(),
                    benefit_type: normalizePayrollImportBenefitType(row.benefit_type || ''),
                    benefit_value: String(row.overtime_value || '').trim(),
                    benefit_hours: String(row.overtime_hours || '').trim(),
                    benefit_minutes: String(row.overtime_minutes || '').trim(),
                    benefit_reason: String(row.overtime_reason || '').trim(),
                    deduction_type: normalizePayrollImportDeductionType(row.deduction_type || ''),
                    deduction_value: String(row.deduction_value || '').trim(),
                    deduction_days: String(row.deduction_days || '').trim(),
                    deduction_hours: String(row.deduction_hours || '').trim(),
                    deduction_minutes: String(row.deduction_minutes || '').trim(),
                    deduction_reason: String(row.deduction_reason || '').trim()
                }));

                const response = await fetch('./includes/api/import_payroll_excel.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        rows: rowsWithOriginalCheckpoint,
                        default_month: managerPayrollExcelState.month
                    })
                });
                const result = await response.json().catch(() => null);
                Swal.close();

                if (!response.ok || !result || (result.status !== 'success' && result.status !== 'warning')) {
                    throw new Error((result && result.message) || 'Failed to import reviewed manager file.');
                }

                const markPayload = new URLSearchParams();
                markPayload.append('action', 'mark_manager_uploaded_payroll_excel_reviewed');
                markPayload.append('request_inv_no', managerPayrollExcelState.requestInvNo);
                markPayload.append('month', managerPayrollExcelState.month);
                markPayload.append('file_id', selectedFileId);
                markPayload.append('review_note', 'Reviewed by HR Payroll and imported to payroll.');

                const markResponse = await fetch('./includes/ajaxFile/payroll_approval_handler.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8'
                    },
                    body: markPayload.toString()
                });
                const markResult = await markResponse.json().catch(() => null);
                if (!markResponse.ok || !markResult || markResult.status !== 'success') {
                    throw new Error((markResult && markResult.message) || 'Payroll imported, but failed to update review record.');
                }

                await Swal.fire('<?= addslashes(__('success', 'Success')) ?>', result.message || 'Payroll imported successfully from manager upload.', 'success');
                window.location.reload();
            } catch (error) {
                Swal.close();
                Swal.fire('<?= addslashes(__('error', 'Error')) ?>', error.message || 'Failed to import manager uploaded file.', 'error');
            }
        }

        $(function() {
            initPaymentTypeSelect2();
            bindAutoFilterSubmit();
            refreshManagerReviewButtonCount();
            $('[data-toggle="tooltip"]').tooltip();

            checklistDataTable = $('#checklistTable').DataTable({
                pageLength: 25,
                lengthMenu: [[-1, 5, 10, 25, 50, 100], ['All', 5, 10, 25, 50, 100]],
                order: [[0, 'asc']],
                language: {
                    search: '<?= addslashes(__('search', 'Search')) ?>:',
                    lengthMenu: '<?= addslashes(__('show', 'Show')) ?> _MENU_ <?= addslashes(__('entries', 'entries')) ?>',
                    info: '<?= addslashes(__('showing', 'Showing')) ?> _START_ <?= addslashes(__('to', 'to')) ?> _END_ <?= addslashes(__('of', 'of')) ?> _TOTAL_ <?= addslashes(__('entries', 'entries')) ?>',
                    zeroRecords: '<?= addslashes(__('no_matching_records_found', 'No matching records found')) ?>',
                    emptyTable: '<?= addslashes(__('no_data_available_in_table', 'No data available in table')) ?>'
                }
            });

            $('#checklistTable').on('draw.dt search.dt order.dt page.dt', function() {
                syncSummaryCardsFromVisibleRows();
            });

            syncFollowupButton();
            syncChecklistReviewSummary();
            syncSummaryCardsFromVisibleRows();
            window.setTimeout(syncSummaryCardsFromVisibleRows, 100);
        });
    </script>
</body>
</html>
<?php $conDB->close(); ?>