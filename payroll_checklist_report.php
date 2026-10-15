<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/session_check.php';
require_once __DIR__ . '/includes/helper_functions.php';
require_once __DIR__ . '/includes/payroll_approval_helpers.php';
require_once __DIR__ . '/includes/special_access_helper.php';

if (file_exists(__DIR__ . '/includes/functions.php')) {
    require_once __DIR__ . '/includes/functions.php';
}

$current_lang = $_SESSION['lang'] ?? 'en';
if (function_exists('load_language')) {
    load_language($current_lang);
}

// Restrict access: Employees cannot view this page, unless explicitly
// granted via app_settings -> Special Access.
if (
    isset($isEmployee) && $isEmployee === true
    && !user_has_special_access($conDB, $empid ?? '', 'access_payroll_checklist_report', $user_role ?? '', $user_type ?? '', $is_system_admin ?? false)
) {
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
    // Default to Bank + Cash to match the payroll report (get_payroll_report.php),
    // which excludes Hold (payment_type = 3) employees but includes everything else.
    $selectedPaymentTypes = [1 => 1, 2 => 2];
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
    $pendingStmt = $pdo->prepare("SELECT COUNT(*) FROM request_approvers WHERE request_inv_no = :inv_no AND request_type_id = :type_id AND (approver_id = :approver_id OR " . approverInSql('approver_id') . ") AND status = 'pending'");
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
              AND (finance_officer_emp_id = :officer_id OR " . approverInSql('finance_officer_emp_id') . ")
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
                  AND (finance_officer_emp_id = :officer_id OR " . approverInSql('finance_officer_emp_id') . ")
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
    // Scoped to finance_officer_emp_id = finance_manager_emp_id too - a manager can now
    // have several officers' rows under their own manager id, and this branch is only
    // for the manager's OWN self-assigned scope, not whichever officer's row has the
    // highest id.
    if ($assignedScopeRaw === false || $assignedScopeRaw === null || trim((string)$assignedScopeRaw) === '') {
        // Check manager's own (self-assigned) scope first (confirmed only).
        $assignedScopeManagerStmt = $pdo->prepare("SELECT selected_company_ids
            FROM payroll_finance_verification
            WHERE request_inv_no = :inv_no
              AND payroll_month = :month_year
              AND finance_manager_emp_id = :finance_user_mgr
              AND (finance_officer_emp_id = :finance_user_officer OR " . approverInSql('finance_officer_emp_id') . ")
              AND is_confirmed = 1
            ORDER BY id DESC
            LIMIT 1");
        $assignedScopeManagerStmt->execute([
            ':inv_no' => $requestInvNo,
            ':month_year' => $monthYear,
            ':finance_user_mgr' => $currentApproverId,
            ':finance_user_officer' => $currentApproverId
        ]);
        $assignedScopeRaw = $assignedScopeManagerStmt->fetchColumn();
    }

    // If not found as manager (confirmed), try unconfirmed manager self-assigned scope.
    if ($assignedScopeRaw === false || $assignedScopeRaw === null || trim((string)$assignedScopeRaw) === '') {
        $assignedScopeManagerUnconfirmedStmt = $pdo->prepare("SELECT selected_company_ids
            FROM payroll_finance_verification
            WHERE request_inv_no = :inv_no
              AND payroll_month = :month_year
              AND finance_manager_emp_id = :finance_user_mgr
              AND (finance_officer_emp_id = :finance_user_officer OR " . approverInSql('finance_officer_emp_id') . ")
            ORDER BY id DESC
            LIMIT 1");
        $assignedScopeManagerUnconfirmedStmt->execute([
            ':inv_no' => $requestInvNo,
            ':month_year' => $monthYear,
            ':finance_user_mgr' => $currentApproverId,
            ':finance_user_officer' => $currentApproverId
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
              AND (finance_officer_emp_id = :finance_user OR " . approverInSql('finance_officer_emp_id') . ")
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
              AND (finance_officer_emp_id = :finance_user OR " . approverInSql('finance_officer_emp_id') . ")
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

// Allow Finance Managers to access even without company assignment.
// Also allow accounts whose user_type happens to be 'finance_officer' but who have an
// independent legitimate reason to be on this page - e.g. emp_type = 'Manager' (can upload
// the manager payroll excel for their own department) or an HR checklist approver. Without
// this, a manager who was also given a finance_officer login gets hard-blocked from every
// ability on this page just for lacking a finance company assignment he doesn't need.
if (
    $isFinanceOfficerChecklistUser &&
    !$isAssignedFinanceCompanyVerifier &&
    !$isCompanyManagerChecklistUser &&
    !$isHrChecklistApprover
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

// A finance officer (or a manager acting as their own officer) can check off employees
// in their assigned scope one by one, same as HR - required before they can approve.
$canFinanceOfficerCheckEmployees = $isAssignedFinanceCompanyVerifier
    && $requestInvNo !== ''
    && $requestStatus === 'pending_approval';

$canExportChecklistExcel = $isHrPayrollApprover
    || $isHrSeniorBpApprover
    || $isFinanceManagerChecklistUser
    || $isFinanceOfficerChecklistUser
    || ($is_system_admin ?? false)
    || (($user_type ?? '') === 'administrator')
    || user_has_special_access($conDB, $empid ?? '', 'payroll_checklist_export_excel', $user_role ?? '', $user_type ?? '', $is_system_admin ?? false);
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
$canUploadManagerPayrollExcel = $canUploadManagerPayrollExcel
    || ($requestInvNo !== '' && user_has_special_access($conDB, $empid ?? '', 'payroll_checklist_upload_excel', $user_role ?? '', $user_type ?? '', $is_system_admin ?? false));
$canReviewManagerUploadedPayrollExcel = $requestInvNo !== ''
    && ($isHrChecklistApprover
        || user_has_special_access($conDB, $empid ?? '', 'payroll_checklist_review_import', $user_role ?? '', $user_type ?? '', $is_system_admin ?? false));
// HR Payroll / HR Senior BP can mark employees checked both during normal approval
// (isPendingWithMe) and after final approval for follow-up feedback review.
$canManageChecklistReview = $requestInvNo !== ''
    && $isHrChecklistApprover
    && ($isPendingWithMe || $requestStatus === 'approved');
// Shared gate for the checklist checkbox/mark-checked UI - HR's own review flow
// (modified-employees-only) or a finance officer's own scope (checks everyone visible).
$canCheckPayrollChecklistEmployees = $canManageChecklistReview || $canFinanceOfficerCheckEmployees;
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
          AND (approver_id = :approver_id OR " . approverInSql('approver_id') . ")");
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

    // Finance Manager: only scoped when the toggle is ON (can otherwise see everyone).
    // Finance Officer: has no toggle and must always be scoped to their assigned companies.
    $applyFinanceCompanyFilterNow = $assignedScopeOnly || !$isFinanceManagerChecklistUser;
    if ($applyFinanceCompanyFilterNow && !empty($assignedFinanceCompanyIds)) {
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

// Mirrors the VACATION PAYROLL DROPOUT CHECK in get_employees.php / process_payroll.php /
// get_payroll_report.php so employees skipped from payroll generation because of a long
// vacation - or already paid out via a vacation settlement - don't show up on this
// checklist report either.
$monthStart = $monthYear !== '' ? $monthYear . '-01' : null;
$monthEnd = $monthStart ? date('Y-m-t', strtotime($monthStart)) : null;

$vacationDropoutDays = (int) get_setting_num($conDB, 'vacation_payroll_dropout_days', 30);
$dropoutFilter = '';
if ($vacationDropoutDays > 0 && !empty($monthStart) && !empty($monthEnd)) {
    $dropoutFilter = " AND NOT EXISTS (
            SELECT 1 FROM emp_vacation vd
            WHERE vd.emp_id = e.emp_id
                AND vd.review = 'A'
                AND vd.current_status IN ('approved', 'completed')
                AND vd.start_date <= :dropout_month_end
                AND COALESCE(vd.return_date, vd.start_date) >= :dropout_month_start
                AND COALESCE(vd.vacdays, 0) >= :dropout_days_param
                AND vd.request_inv_no LIKE 'VAC-%'
        )";
    $params[':dropout_month_end'] = $monthEnd;
    $params[':dropout_month_start'] = $monthStart;
    $params[':dropout_days_param'] = $vacationDropoutDays;
}

$settlementFilter = '';
if (!empty($monthStart) && !empty($monthEnd)) {
    $settlementFilter = " AND NOT EXISTS (
            SELECT 1 FROM settlement_records sr
            LEFT JOIN emp_vacation sv ON sv.request_inv_no = SUBSTRING(sr.request_inv_no, 6)
            WHERE sr.emp_id = e.emp_id
                AND sr.request_type = 'annual_vacation'
                AND sr.settlement_status IN ('completed', 'processed')
                AND sr.payment_date IS NOT NULL
                AND (
                        (
                            sv.id IS NOT NULL
                            AND sv.current_status IN ('approved', 'completed')
                            AND sv.start_date <= :settlement_month_end
                            AND COALESCE(sv.return_date, sv.start_date) >= :settlement_month_start
                            AND (
                                sv.return_date IS NULL
                                OR sv.return_date > :settlement_month_end3
                            )
                        )
                        OR
                        (
                            sv.id IS NULL
                            AND sr.payment_date BETWEEN :settlement_month_start2 AND :settlement_month_end2
                        )
                )
        )";
    $params[':settlement_month_end'] = $monthEnd;
    $params[':settlement_month_start'] = $monthStart;
    $params[':settlement_month_end2'] = $monthEnd;
    $params[':settlement_month_start2'] = $monthStart;
    $params[':settlement_month_end3'] = $monthEnd;
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
    WHERE gp.month_year = :month_year_param" . $deptFilter . $companyFilter . $departmentFilter . $sponsorFilter . $paymentTypeFilter . $dropoutFilter . $settlementFilter . "
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
          AND (finance_officer_emp_id = :finance_officer OR " . approverInSql('finance_officer_emp_id') . ")
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
if ($canCheckPayrollChecklistEmployees && $currentApproverId !== '') {
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

    $employees[$index]['benefits_list'] = array_map(function ($b) {
        $b['benefit'] = getDisplayName((string)($b['benefit'] ?? ''));
        return $b;
    }, $benefits);
    $employees[$index]['deductions_list'] = array_map(function ($d) {
        $d['deduction'] = getDisplayName((string)($d['deduction'] ?? ''));
        return $d;
    }, $deductions);
    $employees[$index]['expected_net_salary'] = $expectedNet;
    $employees[$index]['net_difference'] = $difference;
    $employees[$index]['is_balanced'] = $isBalanced;

    if ($canManageChecklistReview) {
        $requiresChecklistReview = !empty($employeesRequiringChecklistReviewByEmp[(string)$empId]);
    } elseif ($canFinanceOfficerCheckEmployees) {
        // Finance officer must check every employee visible in their assigned scope,
        // not just the ones modified since the request was created (that's HR's rule).
        $requiresChecklistReview = true;
    } else {
        $requiresChecklistReview = false;
    }
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
    // The assigned-scope toggle only exists for the Main Finance Manager (who can also
    // browse in "see everyone" mode); a plain Finance Officer has no toggle and only
    // ever has one view - their own assigned companies - so they don't need it ON.
    && (!$isFinanceManagerChecklistUser || $assignedScopeOnly);
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
        'employee_name' => getDisplayName((string)($employee['employee_name'] ?? '')),
        'department_name' => (string)((($is_rtl ?? false) ? ($employee['department_name_ar'] ?? $employee['department_name'] ?? 'N/A') : ($employee['department_name'] ?? $employee['department_name_ar'] ?? 'N/A'))),
        'company_name' => getDisplayName((string)($employee['comp_name'] ?? 'N/A')),
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
    <link href="assets/css/icons.css" rel="stylesheet" type="text/css" />
    <link href="assets/css/metismenu.min.css" rel="stylesheet" type="text/css" />
    <link href="assets/css/style.css" rel="stylesheet" type="text/css" />
    <link href="assets/css/style_dark.css" rel="stylesheet" type="text/css" />
    <link href="assets/css/smart_request.css?v=<?= @filemtime(__DIR__ . '/assets/css/smart_request.css') ?>" rel="stylesheet" type="text/css" />
    <?php if ($is_rtl ?? false): ?>
        <link href="assets/css/style_rtl.css" rel="stylesheet" type="text/css" />
    <?php endif; ?>
    <link href="plugins/datatables/dataTables.bootstrap4.min.css" rel="stylesheet" type="text/css" />
    <link href="./plugins/select2/css/select2.min.css" rel="stylesheet" type="text/css" />
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        /* ---- Page shell (sr-page design system, assets/css/smart_request.css) ---- */
        .pc-page .sr-head { align-items: flex-start; }
        .pc-page .sr-head h1 i { color: var(--sr-accent); margin-inline-end: 6px; }
        .pc-head-meta { display: flex; flex-wrap: wrap; gap: 6px; margin-top: 8px; }
        .pc-actions { display: flex; flex-wrap: wrap; justify-content: flex-end; gap: 8px; max-width: 720px; }
        .pc-actions .sr-btn { gap: 6px; }
        .pc-actions .sr-btn .sr-count { margin-inline-start: 2px; }
        .sr-btn-warn { background: #f59e0b; border-color: #f59e0b; color: #fff !important; }
        .sr-btn-warn:hover { background: #d97706; border-color: #d97706; color: #fff !important; }
        .sr-btn.is-on { background: var(--tone-amber-bg); border-color: var(--tone-amber-bd); color: var(--tone-amber-fg) !important; }
        .sr-btn:disabled { opacity: .55; cursor: not-allowed; }
        .pc-more-btn .sr-count { background: #fff; color: var(--sr-accent-strong); }

        /* ---- Employee detail popup ---- */
        .pc-detail-container .swal2-popup.pc-detail-popup { width: min(1240px, 96vw) !important; padding: 0 !important; border-radius: 18px; overflow: hidden; background: var(--sr-surface-2, #f8fafc); }
        /* Employee detail popup: banner header + scrolling body (all inside html-container) */
        .pc-detail-popup .swal2-title { display: none !important; }
        .pc-detail-popup .swal2-html-container { margin: 0 !important; padding: 0 !important; text-align: start !important; overflow: visible !important; max-height: none !important; }
        .pc-detail-popup .swal2-close { top: 14px; color: rgba(255, 255, 255, .85); z-index: 3; }
        .pc-detail-popup .swal2-close:hover { color: #fff; background: rgba(255, 255, 255, .15); }
        .pc-detail-popup .swal2-actions { margin: 0; padding: 12px 20px; gap: 8px; justify-content: flex-end; background: var(--sr-surface); border-top: 1px solid var(--sr-border); width: 100%; }
        .pc-detail-popup .swal2-footer { margin: 0; padding: 10px 20px; border-top: 1px solid var(--sr-border); background: var(--sr-surface); }
        .pc-dbody { padding: 16px 20px 6px; max-height: 64vh; overflow-y: auto; }
        /* Prev / Next: content is swapped in place; this overlay covers the popup while it loads */
        .pc-detail-container .swal2-popup.pc-detail-popup { position: relative; }
        .pc-dloader { position: absolute; inset: 0; z-index: 6; display: none; align-items: center; justify-content: center;
            background: rgba(255, 255, 255, .6); backdrop-filter: blur(2px); -webkit-backdrop-filter: blur(2px); }
        .pc-dloader.show { display: flex; }
        .pc-dloader-box { display: inline-flex; align-items: center; gap: 10px; padding: 12px 18px; border-radius: 12px; font-size: 13px; font-weight: 600;
            color: var(--sr-text-2); background: var(--sr-surface); border: 1px solid var(--sr-border); box-shadow: var(--sr-shadow); }
        .pc-spinner { width: 20px; height: 20px; border-radius: 50%; border: 3px solid var(--sr-accent-soft); border-top-color: var(--sr-accent); animation: pcSpin .7s linear infinite; }
        @keyframes pcSpin { to { transform: rotate(360deg); } }
        html.app-dark .pc-dloader { background: rgba(15, 23, 42, .55); }

        .pc-dhead { position: relative; display: flex; align-items: center; gap: 16px; padding: 18px 64px 18px 22px; color: #fff; line-height: 1.4;
            background: linear-gradient(120deg, var(--sr-accent-strong) 0%, #6d28d9 55%, #7c3aed 100%); overflow: hidden; }
        .pc-dhead::after { content: ''; position: absolute; inset-inline-end: -60px; top: -80px; width: 260px; height: 260px; border-radius: 50%;
            background: radial-gradient(circle, rgba(255, 255, 255, .16), rgba(255, 255, 255, 0) 70%); pointer-events: none; }
        [dir="rtl"] .pc-dhead { padding: 18px 22px 18px 64px; }
        .pc-dhead-avatar { flex: 0 0 auto; width: 54px; height: 54px; border-radius: 16px; display: inline-flex; align-items: center; justify-content: center;
            font-size: 18px; font-weight: 800; letter-spacing: .5px; color: #fff; background: rgba(255, 255, 255, .18); border: 1px solid rgba(255, 255, 255, .35); }
        .pc-dhead-main { flex: 1 1 auto; min-width: 0; position: relative; z-index: 1; }
        .pc-dhead-name { display: block; font-size: 18px; font-weight: 800; color: #fff; line-height: 1.25; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .pc-dhead-meta { display: flex; flex-wrap: wrap; gap: 6px; margin-top: 8px; }
        .pc-dhead-chip { display: inline-flex; align-items: center; gap: 5px; padding: 3px 10px; border-radius: 999px; font-size: 12px; font-weight: 600; line-height: 1.5;
            color: #fff; background: rgba(255, 255, 255, .16); border: 1px solid rgba(255, 255, 255, .22); white-space: nowrap; }
        .pc-dhead-chip i { font-size: 13px; opacity: .85; }
        .pc-dhead-side { flex: 0 0 auto; display: flex; flex-direction: column; align-items: flex-end; gap: 8px; position: relative; z-index: 1; }
        .pc-dhead-net { text-align: end; }
        .pc-dhead-net small { display: block; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .5px; opacity: .8; }
        .pc-dhead-net b { display: block; font-size: 22px; font-weight: 800; font-variant-numeric: tabular-nums; white-space: nowrap; }
        .pc-dhead-net b .icon-saudi_riyal { font-size: .7em !important; opacity: .85; }
        .pc-dhead-pills { display: flex; flex-wrap: wrap; justify-content: flex-end; gap: 6px; }
        .pc-dhead-pill { display: inline-flex; align-items: center; gap: 5px; padding: 3px 10px; border-radius: 999px; font-size: 11.5px; font-weight: 700; line-height: 1.5;
            background: #fff; white-space: nowrap; }
        .pc-dhead-pill.ok { color: #15803d; } .pc-dhead-pill.bad { color: #b91c1c; } .pc-dhead-pill.wait { color: #475569; }
        html.app-dark .pc-dhead { background: linear-gradient(120deg, #3730a3 0%, #4c1d95 60%, #5b21b6 100%); }
        html.app-dark .pc-dhead-pill { background: rgba(15, 23, 42, .75); }
        html.app-dark .pc-dhead-pill.ok { color: #4ade80; } html.app-dark .pc-dhead-pill.bad { color: #f87171; } html.app-dark .pc-dhead-pill.wait { color: #cbd5e1; }
        @media (max-width: 767px) { .pc-dhead { flex-wrap: wrap; } .pc-dhead-side { width: 100%; flex-direction: row; align-items: center; justify-content: space-between; } .pc-dhead-net { text-align: start; } }
        .pc-kpi { display: grid; grid-template-columns: repeat(6, minmax(0, 1fr)); gap: 10px; margin-bottom: 12px; }
        .pc-kpi-box { position: relative; padding: 11px 14px; border: 1px solid var(--sr-border); border-radius: 12px; background: var(--sr-surface); min-width: 0; }
        .pc-kpi-box::before { content: ''; position: absolute; inset-block: 10px; inset-inline-start: 0; width: 3px; border-radius: 3px; background: var(--k-fg, var(--sr-border-strong)); }
        .pc-kpi-box.k-green { --k-fg: var(--tone-green-fg); } .pc-kpi-box.k-red { --k-fg: var(--tone-red-fg); }
        .pc-kpi-box.k-sky { --k-fg: var(--tone-sky-fg); } .pc-kpi-box.k-indigo { --k-fg: var(--tone-indigo-fg); }
        .pc-kpi-label { display: block; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .4px; color: var(--sr-muted); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .pc-kpi-value { display: block; margin-top: 3px; font-size: 18px; font-weight: 800; color: var(--k-fg, var(--sr-text)); font-variant-numeric: tabular-nums; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .pc-kpi-value .icon-saudi_riyal { font-size: .8em; opacity: .75; }
        .pc-formula { display: flex; flex-wrap: wrap; align-items: center; gap: 6px 8px; padding: 10px 14px; margin-bottom: 14px; border-radius: 12px;
            border: 1px dashed var(--sr-border-strong); background: var(--sr-surface); font-size: 13px; color: var(--sr-text-2); font-variant-numeric: tabular-nums; }
        .pc-formula b { color: var(--sr-text); }
        .pc-formula .op { color: var(--sr-muted); font-weight: 700; }
        .pc-formula .ok { color: var(--tone-green-fg); } .pc-formula .bad { color: var(--tone-red-fg); }
        .pc-dgrid { display: grid; grid-template-columns: minmax(0, 1.35fr) minmax(0, 1fr); gap: 14px; margin-bottom: 14px; }
        .pc-dgrid.even { grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .pc-panel { border: 1px solid var(--sr-border); border-radius: 12px; background: var(--sr-surface); min-width: 0; overflow: hidden; }
        .pc-panel-head { display: flex; align-items: center; justify-content: space-between; gap: 8px; padding: 10px 14px; border-bottom: 1px solid var(--sr-border); font-size: 13.5px; font-weight: 700; color: var(--sr-text); }
        .pc-panel-head i { margin-inline-end: 6px; color: var(--sr-accent); }
        .pc-panel-head .pc-panel-total { font-variant-numeric: tabular-nums; }
        .pc-panel.p-green .pc-panel-head { background: var(--tone-green-bg); color: var(--tone-green-fg); border-color: var(--tone-green-bd); }
        .pc-panel.p-green .pc-panel-head i { color: var(--tone-green-fg); } .pc-panel.p-green { border-color: var(--tone-green-bd); }
        .pc-panel.p-red .pc-panel-head { background: var(--tone-red-bg); color: var(--tone-red-fg); border-color: var(--tone-red-bd); }
        .pc-panel.p-red .pc-panel-head i { color: var(--tone-red-fg); } .pc-panel.p-red { border-color: var(--tone-red-bd); }
        .pc-sal { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); }
        .pc-sal-row { display: flex; justify-content: space-between; align-items: center; gap: 10px; padding: 9px 14px; border-bottom: 1px solid var(--sr-border); font-size: 13px; }
        .pc-sal-row:nth-child(odd) { border-inline-end: 1px solid var(--sr-border); }
        .pc-sal-row span { color: var(--sr-muted); font-weight: 600; }
        .pc-sal-row b { color: var(--sr-text); font-variant-numeric: tabular-nums; white-space: nowrap; }
        .pc-sal-row.is-zero b { color: var(--sr-muted); font-weight: 500; opacity: .7; }
        .pc-kv { margin: 0; padding: 0; list-style: none; }
        .pc-kv li { display: flex; justify-content: space-between; gap: 12px; padding: 9px 14px; border-bottom: 1px solid var(--sr-border); font-size: 13px; }
        .pc-kv li:last-child { border-bottom: 0; }
        .pc-kv span { color: var(--sr-muted); font-weight: 600; }
        .pc-kv b { color: var(--sr-text); text-align: end; word-break: break-all; }
        .pc-items { margin: 0; padding: 6px 0; list-style: none; }
        .pc-items li { display: flex; align-items: center; justify-content: space-between; gap: 12px; padding: 9px 14px; }
        .pc-items li + li { border-top: 1px dashed var(--sr-border); }
        .pc-item-name { display: block; font-weight: 700; color: var(--sr-text); font-size: 13.5px; }
        .pc-item-meta { display: block; margin-top: 2px; font-size: 12px; color: var(--sr-muted); }
        .pc-item-amt { font-weight: 800; white-space: nowrap; font-variant-numeric: tabular-nums; }
        .p-green .pc-item-amt { color: var(--tone-green-fg); } .p-red .pc-item-amt { color: var(--tone-red-fg); }
        .pc-items-empty { padding: 18px 14px; text-align: center; color: var(--sr-muted); font-size: 13px; }
        .pc-items-empty i { display: block; font-size: 22px; opacity: .6; margin-bottom: 4px; }
        .pc-fb { border: 1px solid var(--tone-amber-bd); border-radius: 12px; background: var(--tone-amber-bg); margin-bottom: 14px; overflow: hidden; }
        .pc-fb-head { display: flex; justify-content: space-between; align-items: center; padding: 10px 14px; font-weight: 700; color: var(--tone-amber-fg); }
        .pc-fb-list { max-height: 190px; overflow-y: auto; padding: 0 10px 10px; }
        .pc-fb-item { padding: 10px 12px; border-radius: 10px; background: var(--sr-surface); border: 1px solid var(--sr-border); }
        .pc-fb-item + .pc-fb-item { margin-top: 8px; }
        .pc-fb-top { display: flex; justify-content: space-between; align-items: center; gap: 8px; font-size: 13px; font-weight: 700; color: var(--sr-text); }
        .pc-fb-when { font-size: 11.5px; color: var(--sr-muted); margin: 2px 0 6px; }
        .pc-fb-note { font-size: 13px; color: var(--sr-text-2); white-space: pre-wrap; }
        .pc-fb-resolved { margin-top: 6px; font-size: 11.5px; color: var(--tone-green-fg); }
        .pc-nav { display: flex; align-items: center; justify-content: space-between; gap: 12px; width: 100%; }
        .pc-nav-counter { font-weight: 700; color: var(--sr-muted); font-size: 12.5px; font-variant-numeric: tabular-nums; }
        .pc-nav-counter kbd { font-size: 10.5px; padding: 1px 5px; border-radius: 4px; background: var(--sr-surface-3); color: var(--sr-muted); border: 1px solid var(--sr-border); box-shadow: none; }
        .sr-btn.pc-btn-check { background: var(--sr-surface); border-color: #16a34a; color: #16a34a !important; }
        .sr-btn.pc-btn-check:hover { background: var(--tone-green-bg); }
        @media (max-width: 1199px) { .pc-kpi { grid-template-columns: repeat(3, minmax(0, 1fr)); } }
        @media (max-width: 991px) { .pc-dgrid, .pc-dgrid.even { grid-template-columns: 1fr; } }
        @media (max-width: 575px) { .pc-kpi { grid-template-columns: repeat(2, minmax(0, 1fr)); } .pc-sal { grid-template-columns: 1fr; } .pc-sal-row:nth-child(odd) { border-inline-end: 0; } }

        /* ---- More Actions popup (same look as view_employee.php / profile.php) ---- */
        .more-actions-modal { --ma-bg: #f5f6fa; --ma-card: #fff; --ma-border: #e8ebf1; --ma-text: #1f2937; --ma-muted: #8a93a3; --ma-accent: #5b73e8; }
        html.app-dark .more-actions-modal { --ma-bg: #1b1f27; --ma-card: #242a35; --ma-border: #323a48; --ma-text: #e5e7eb; --ma-muted: #9aa3b2; --ma-accent: #8b9cf4; }
        .more-actions-modal .swal2-popup { border-radius: 18px; box-shadow: 0 24px 64px rgba(15, 23, 42, .22); overflow: hidden; background: var(--ma-bg); padding: 0 !important; }
        .more-actions-modal .swal2-title { margin: 0; padding: 0; text-align: start; background: var(--ma-card); border-bottom: 1px solid var(--ma-border); }
        .more-actions-modal .ma-head { padding: 18px 56px 14px 20px; }
        [dir="rtl"] .more-actions-modal .ma-head { padding: 18px 20px 14px 56px; }
        .more-actions-modal .ma-head p { margin: 3px 0 0; font-size: 12.5px; font-weight: 500; color: var(--ma-muted); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .more-actions-modal .swal2-html-container { margin: 0 !important; padding: 0 !important; overflow: visible; text-align: start; }
        .more-actions-modal .menu-items-container { padding: 14px 16px 18px; max-height: 62vh; overflow-y: auto; background: var(--ma-bg); }
        .more-actions-modal .ma-group + .ma-group { margin-top: 16px; }
        .more-actions-modal .ma-group-title { display: flex; align-items: center; gap: 7px; margin: 0 2px 8px; font-size: 11px; font-weight: 700; letter-spacing: .6px; text-transform: uppercase; color: var(--ma-muted); }
        .more-actions-modal .ma-group-title i { font-size: 11px; }
        .more-actions-modal .ma-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 8px; }
        @media (max-width: 520px) { .more-actions-modal .ma-grid { grid-template-columns: 1fr; } }
        .more-actions-modal .menu-item { display: flex !important; align-items: center; gap: 11px; min-height: 56px; padding: 10px 12px !important; margin: 0 !important;
            cursor: pointer !important; border: 1px solid var(--ma-border); border-radius: 12px; background: var(--ma-card); font-weight: 600; user-select: none;
            transition: border-color .15s ease, box-shadow .15s ease, transform .15s ease; }
        .more-actions-modal .menu-item:hover { border-color: currentColor; box-shadow: 0 6px 16px rgba(15, 23, 42, .08); transform: translateY(-1px); }
        .more-actions-modal .menu-item:active { transform: scale(.98); }
        .more-actions-modal .menu-item:focus-visible { outline: 2px solid var(--ma-accent); outline-offset: 2px; }
        .more-actions-modal .menu-item i { flex-shrink: 0; width: 36px; height: 36px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 15px; background-color: rgba(108, 117, 125, .14); }
        .more-actions-modal .menu-item span { flex: 1; min-width: 0; font-size: 13.5px; line-height: 1.3; color: var(--ma-text); display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
        .more-actions-modal .menu-item .ma-count { flex: 0 0 auto; min-width: 22px; height: 22px; padding: 0 7px; border-radius: 999px; background: #ef4444; color: #fff; font-size: 11.5px; line-height: 22px; text-align: center; }
        .more-actions-modal .menu-item.text-primary { color: var(--primary) !important; } .more-actions-modal .menu-item.text-primary i { background-color: rgba(91, 115, 232, .14); }
        .more-actions-modal .menu-item.text-warning { color: var(--warning) !important; } .more-actions-modal .menu-item.text-warning i { background-color: rgba(241, 180, 76, .16); }
        .more-actions-modal .menu-item.text-info { color: var(--info) !important; } .more-actions-modal .menu-item.text-info i { background-color: rgba(80, 165, 241, .14); }
        .more-actions-modal .menu-item.text-success { color: var(--success) !important; } .more-actions-modal .menu-item.text-success i { background-color: rgba(40, 167, 69, .14); }
        .more-actions-modal .menu-item.text-secondary { color: var(--secondary) !important; } .more-actions-modal .menu-item.text-secondary i { background-color: rgba(108, 117, 125, .14); }
        .more-actions-modal .menu-item.text-dark { color: var(--ma-text) !important; } .more-actions-modal .menu-item.text-dark i { background-color: rgba(108, 117, 125, .16); }
        .more-actions-modal .swal2-close { font-size: 1.5rem; color: var(--ma-muted); width: 36px; height: 36px; top: 10px; }
        .more-actions-modal .swal2-close:hover { color: #f46a6a; }
        /* Filters */
        .pc-filters .sr-card-head h3 i, .pc-list .sr-card-head h3 i { color: var(--sr-accent); margin-inline-end: 6px; }
        .pc-filters .sr-filter-grid { grid-template-columns: repeat(auto-fit, minmax(170px, 1fr)); }
        .pc-filters .select2-container { width: 100% !important; }
        .pc-filters .select2-container--default .select2-selection--multiple {
            min-height: 38px; border: 1px solid var(--sr-border-strong); border-radius: 9px; padding: 2px 6px; background: var(--sr-surface-2);
        }
        .pc-filters .select2-container--default.select2-container--focus .select2-selection--multiple { border-color: var(--sr-accent); }
        .pc-filters .select2-container--default .select2-selection--multiple .select2-selection__choice { margin-top: 4px; }
        .pc-filters .pc-reset { width: 100%; justify-content: center; height: 38px; }
        /* KPI cards (static sr-scard) */
        .pc-kpis .sr-scard { cursor: default; }
        .pc-kpis .sr-scard:hover { transform: none; }
        .pc-kpis .pc-money { font-size: 19px; letter-spacing: -.3px; white-space: nowrap; }
        .pc-kpis .pc-money .icon-saudi_riyal { font-size: .8em; opacity: .7; margin-inline-end: 2px; }
        /* Table card */
        .pc-list .sr-card-head { display: flex; align-items: center; justify-content: space-between; gap: 10px; flex-wrap: wrap; }
        .pc-list .pc-table-wrap { padding: 12px 16px 16px; }
        .pc-list .dataTables_wrapper .dataTables_filter input, .pc-list .dataTables_wrapper .dataTables_length select {
            height: 34px; border-radius: 8px; border: 1px solid var(--sr-border-strong); background: var(--sr-surface-2); color: var(--sr-text); padding: 0 10px;
        }
        .pc-list #checklistTable thead th { white-space: nowrap; }
        .pc-list #checklistTable td { vertical-align: middle; }
        .pc-list .pc-emp { font-weight: 700; color: var(--sr-text); }
        .pc-list .pc-num { font-variant-numeric: tabular-nums; white-space: nowrap; text-align: end; }
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
        /* Used by popups built in JS (e.g. import "Skipped Rows") */
        .list-card { background: var(--sr-surface); border: 1px solid var(--sr-border); border-radius: 12px; overflow: hidden; }
        .list-card-header { padding: 12px 16px; border-bottom: 1px solid var(--sr-border); background: var(--sr-surface-2);
            display: flex; justify-content: space-between; align-items: center; gap: 10px; flex-wrap: wrap; }
        .list-card-title { margin: 0; font-size: 14px; font-weight: 700; color: var(--sr-text); }
        .pill-badge { display: inline-flex; align-items: center; gap: 6px; padding: 4px 10px; border-radius: 999px; font-weight: 700; font-size: 11.5px; border: 1px solid transparent; }
        .pill-success { background: var(--tone-green-bg); color: var(--tone-green-fg); border-color: var(--tone-green-bd); }
        .pill-danger { background: var(--tone-red-bg); color: var(--tone-red-fg); border-color: var(--tone-red-bd); }
        .pill-warning { background: var(--tone-amber-bg); color: var(--tone-amber-fg); border-color: var(--tone-amber-bd); }
        .pill-primary { background: var(--tone-indigo-bg); color: var(--tone-indigo-fg); border-color: var(--tone-indigo-bd); }
        .pill-secondary { background: var(--tone-slate-bg); color: var(--tone-slate-fg); border-color: var(--tone-slate-bd); }
        .table-compact td, .table-compact th { vertical-align: middle; }
        .checklist-row-checked td { background: var(--tone-green-bg) !important; }
        .checklist-row-checked:hover td { background: var(--tone-green-bg) !important; filter: brightness(.98); }
        .diff-positive { color: var(--tone-red-fg); font-weight: 700; }
        .diff-zero { color: var(--tone-green-fg); font-weight: 700; }
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
        .detail-panel-benefits { border-color: #bfe6c9; }
        .detail-panel-benefits h5 { background: #eaf8ee; color: #1f8b3c; border-bottom-color: #bfe6c9; }
        .detail-panel-benefits .line-items-two-col li { background: #f2fbf4; border-color: #d6f0dc; }
        .detail-panel-deductions { border-color: #f5c6c6; }
        .detail-panel-deductions h5 { background: #fdecec; color: #c43636; border-bottom-color: #f5c6c6; }
        .detail-panel-deductions .line-items-two-col li { background: #fdf3f3; border-color: #f6dcdc; }
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
        .modal-lg-custom { max-width: 1100px; }
        @media (max-width: 991px) {
            .details-grid { grid-template-columns: 1fr; }
            .line-items-two-col { grid-template-columns: 1fr; }
        }
        @media print {
            .left.side-menu, .topbar, .footer, .pc-actions, .pc-filters, #bulkCheckSelectedBtn,
            .dataTables_length, .dataTables_filter, .dataTables_paginate { display: none !important; }
            .content-page { margin: 0 !important; padding: 0 !important; }
            .content-page .content { padding: 0 !important; margin: 0 !important; }
            body, .sr-page { background: #fff !important; }
            .sr-card, .sr-scard { box-shadow: none !important; }
            .pc-kpis { grid-template-columns: repeat(4, minmax(0, 1fr)) !important; }
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
<body class="enlarged" data-keep-enlarged="true">
    <div id="wrapper">
        <div class="left side-menu">
            <div class="slimscroll-menu" id="remove-scroll">
                <div class="topbar-left">
                    <a href="dashboard.php" class="logo">
                        <span><img src="<?= get_setting($conDB, 'logo') ?>" alt="" height="22"></span>
                        <i><img src="<?= get_setting($conDB, 'white_logo') ?>" alt="" height="28"></i>
                    </a>
                </div>
                <?php include("./includes/main_menu.php"); ?>
            </div>
        </div>
        <div class="content-page">
            <?php include("./includes/topbar.php"); ?>

    <div class="content sr-page pc-page">
    <div class="container-fluid">
        <div class="sr-head">
            <div>
                <h1><i class="fas fa-clipboard-check"></i><?= __('payroll_checklist_report', 'Payroll Checklist Report') ?></h1>
                <p><?= __('payroll_checklist_subtitle', 'Detailed payroll calculation review for approvers') ?></p>
                <div class="pc-head-meta">
                    <span class="sr-chip"><i class="mdi mdi-calendar"></i><?= htmlspecialchars($monthTitle) ?></span>
                    <?php if ($requestInvNo !== ''): ?><span class="sr-chip sr-mono"><i class="mdi mdi-pound"></i><?= htmlspecialchars($requestInvNo) ?></span><?php endif; ?>
                    <?php if ($assignedScopeOnly): ?><span class="sr-chip"><i class="mdi mdi-filter"></i><?= __('my_assigned_companies', 'My assigned companies') ?></span><?php endif; ?>
                </div>
            </div>
            <?php
            // Secondary actions live in the "More Actions" popup (same look as the employee page);
            // only Back, the finance approval and the feedback follow-up stay on the header.
            $pcMa = ['file' => [], 'report' => [], 'view' => []];
            if ($canUploadManagerPayrollExcel) {
                $pcMa['file'][] = '<div class="menu-item text-info" role="button" data-pc-action="upload"><i class="fa fa-file-upload"></i><span>' . htmlspecialchars(__('upload_payroll_excel', 'Upload Payroll Excel')) . '</span></div>';
            }
            if ($canReviewManagerUploadedPayrollExcel) {
                $pcMa['file'][] = '<div class="menu-item text-primary" role="button" data-pc-action="review_import"><i class="fa fa-file-import"></i><span>' . htmlspecialchars(__('review_import_manager_file', 'Review Manager File & Import')) . '</span><b class="ma-count" data-pc-import-count style="display:none;">0</b></div>';
            }
            if ($canExportChecklistExcel) {
                $pcMa['report'][] = '<div class="menu-item text-success" role="button" data-pc-action="export"><i class="fa fa-file-excel"></i><span>' . htmlspecialchars(__('export_excel', 'Export Excel')) . '</span></div>';
            }
            $pcMa['report'][] = '<div class="menu-item text-dark" role="button" data-pc-action="print"><i class="fa fa-print"></i><span>' . htmlspecialchars(__('print', 'Print')) . '</span></div>';
            if ($requestInvNo !== '') {
                $pcMa['report'][] = '<div class="menu-item text-secondary" role="button" data-pc-href="payroll_status_history.php?inv_no=' . urlencode($requestInvNo) . '" data-pc-blank="1"><i class="fa fa-history"></i><span>' . htmlspecialchars(__('history', 'History')) . '</span></div>';
            }
            if ($assignedScopeToggleUrl !== '') {
                $pcMa['view'][] = '<div class="menu-item ' . ($assignedScopeOnly ? 'text-warning' : 'text-info') . '" role="button" data-pc-href="' . htmlspecialchars($assignedScopeToggleUrl) . '"><i class="fa fa-filter"></i><span>'
                    . htmlspecialchars($assignedScopeOnly ? __('show_all_employees', 'Show All Employees') : __('show_my_assigned_companies_only', 'Show My Assigned Companies Only')) . '</span></div>';
            }
            $pcMaTitles = [
                'file' => ['fa-file-excel', __('ma_group_payroll_file', 'Payroll File')],
                'report' => ['fa-chart-bar', __('ma_group_report', 'Report')],
                'view' => ['fa-eye', __('ma_group_view', 'View')],
            ];
            $pcMoreActionsHtml = '';
            foreach ($pcMa as $pcKey => $pcItems) {
                if ($pcItems) {
                    $pcMoreActionsHtml .= '<div class="ma-group"><div class="ma-group-title"><i class="fa ' . $pcMaTitles[$pcKey][0] . '"></i>' . htmlspecialchars($pcMaTitles[$pcKey][1]) . '</div><div class="ma-grid">' . implode('', $pcItems) . '</div></div>';
                }
            }
            ?>
            <div class="pc-actions">
                <?php if (!$isFinanceOfficerChecklistUser): ?>
                    <a href="all_payroll_approvals.php" class="sr-btn"><i class="fas fa-arrow-left"></i> <?= __('back_to_payroll_approvals', 'Back to Payroll Approvals') ?></a>
                <?php else: ?>
                    <a href="dashboard.php" class="sr-btn"><i class="fas fa-arrow-left"></i> <?= __('back', 'Back') ?></a>
                <?php endif; ?>
                <?php if ($canFinanceOfficerApproveHere): ?>
                    <?php if ($financeOfficerAlreadyApproved): ?>
                        <button type="button" class="sr-btn sr-btn-success" disabled>
                            <i class="fas fa-check-circle"></i> <?= __('finance_officer_approved', 'Finance Officer Approved') ?>
                        </button>
                    <?php else: ?>
                        <button type="button" id="financeOfficerApproveBtn" class="sr-btn sr-btn-primary" onclick="approveFinanceOfficerVerification()" <?= $financeOfficerCanApproveNow ? '' : 'disabled' ?>>
                            <i class="fas fa-user-check"></i> <?= __('approve_finance_verification', 'Approve Finance Verification') ?>
                        </button>
                    <?php endif; ?>
                <?php endif; ?>
                <?php if ($requestInvNo !== ''): ?>
                    <button type="button" id="feedbackFollowupBtn" class="sr-btn sr-btn-warn" onclick="notifyPayrollGeneratorFeedback()" style="display: <?= $showFeedbackFollowupButton ? 'inline-flex' : 'none' ?>; align-items:center; gap:6px;">
                        <i class="fas fa-paper-plane"></i> <?= __('send_feedback_followup', 'Send Feedback Follow-up') ?>
                        <span class="sr-count" id="feedbackFollowupCountBadge"><?= (int)$feedbackTotals['pending_followup_count'] ?></span>
                    </button>
                <?php endif; ?>
                <?php if ($pcMoreActionsHtml !== ''): ?>
                    <!-- id managerReviewImportBtn / managerPendingReviewCountBadge: refreshManagerReviewButtonCount() shows the pending-import count here -->
                    <button type="button" id="<?= $canReviewManagerUploadedPayrollExcel ? 'managerReviewImportBtn' : 'pcMoreActionsBtn' ?>" class="sr-btn sr-btn-primary pc-more-btn" onclick="openPcMoreActions()">
                        <i class="fas fa-th-large"></i> <?= __('more_actions', 'More Actions') ?>
                        <?php if ($canReviewManagerUploadedPayrollExcel): ?><span class="sr-count" id="managerPendingReviewCountBadge" style="display:none;" title="<?= htmlspecialchars(__('manager_files_pending_review', 'Manager files waiting for review'), ENT_QUOTES) ?>">0</span><?php endif; ?>
                    </button>
                <?php endif; ?>
            </div>
            <template id="pcMoreActionsTpl"><?= $pcMoreActionsHtml ?></template>
        </div>

        <div class="sr-card pc-filters mb-3">
            <div class="sr-card-head"><h3 class="sr-card-title"><i class="fas fa-filter"></i><?= __('filters', 'Filters') ?></h3></div>
            <div>
                <form method="GET" class="sr-filter-grid" id="checklistFilterForm">
                    <input type="hidden" name="month" value="<?= htmlspecialchars($monthYear) ?>">
                    <?php if ($requestInvNo !== ''): ?>
                        <input type="hidden" name="request_inv_no" value="<?= htmlspecialchars($requestInvNo) ?>">
                    <?php endif; ?>
                    <?php if ($assignedScopeOnly): ?>
                        <input type="hidden" name="assigned_scope" value="1">
                    <?php endif; ?>

                    <div>
                        <div class="form-group mb-0">
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

                    <div>
                        <div class="form-group mb-0">
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

                    <div>
                        <div class="form-group mb-0">
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

                    <div>
                        <div class="form-group mb-0">
                            <label for="filterFeedbackStatus"><?= __('feedback_status', 'Feedback Status') ?></label>
                            <select class="form-control" id="filterFeedbackStatus" name="feedback_status">
                                <option value=""><?= __('all', 'All') ?> <?= __('feedback', 'Feedback') ?></option>
                                <option value="submitted" <?= $selectedFeedbackStatus === 'submitted' ? 'selected' : '' ?>><?= __('feedback_submitted', 'Feedback Submitted') ?></option>
                                <option value="resolved" <?= $selectedFeedbackStatus === 'resolved' ? 'selected' : '' ?>><?= __('feedback_resolved', 'Feedback Resolved') ?></option>
                                <option value="needs_mark_checked" <?= $selectedFeedbackStatus === 'needs_mark_checked' ? 'selected' : '' ?>><?= __('needs_mark_checked', 'Need Mark Checked') ?></option>
                            </select>
                        </div>
                    </div>

                    <div>
                        <div class="form-group mb-0">
                            <label for="filterPaymentType"><?= __('salary_payment_type_label', 'Payment Type') ?></label>
                            <?php $selectedPaymentTypeMap = array_flip($selectedPaymentTypes); ?>
                            <select class="form-control" id="filterPaymentType" name="payment_type[]" multiple size="3">
                                <option value="1" <?= isset($selectedPaymentTypeMap[1]) ? 'selected' : '' ?>><?= __('bank_option', 'Bank') ?></option>
                                <option value="2" <?= isset($selectedPaymentTypeMap[2]) ? 'selected' : '' ?>><?= __('cash_option', 'Cash') ?></option>
                                <option value="3" <?= isset($selectedPaymentTypeMap[3]) ? 'selected' : '' ?>><?= __('hold_option', 'Hold') ?></option>
                            </select>
                        </div>
                    </div>

                    <div>
                        <div class="form-group mb-0">
                            <label>&nbsp;</label>
                            <a href="payroll_checklist_report.php?month=<?= urlencode($monthYear) ?><?= $requestInvNo !== '' ? '&request_inv_no=' . urlencode($requestInvNo) : '' ?><?= $assignedScopeOnly ? '&assigned_scope=1' : '' ?>" class="sr-btn pc-reset"><i class="fas fa-undo"></i> <?= __('reset_filters', 'Reset Filters') ?></a>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <?php
        require_once __DIR__ . '/includes/sr_list_helpers.php'; // sr_stat_cols(), sr_scard_foot()
        $pcTotalEmp = (int)$summary['employees'];
        $pcChecked = (int)$checkedEmployeesCount;
        $pcRemaining = max(0, $pcTotalEmp - $pcChecked);
        $pcCardCount = $canCheckPayrollChecklistEmployees ? 8 : 6;
        $pcCard = function ($tone, $icon, $label, $hint, $valueHtml, $foot = '') {
            return '<div class="sr-scard st-' . $tone . '">'
                . '<span class="sr-scard-ico"><i class="mdi ' . $icon . '"></i></span>'
                . '<span class="sr-scard-body"><span class="sr-scard-label">' . $label . '</span>'
                . ($hint !== '' ? '<span class="sr-scard-hint">' . $hint . '</span>' : '') . '</span>'
                . $valueHtml . $foot . '</div>';
        };
        $pcMoney = function ($id, $amount) {
            return '<span class="sr-scard-value pc-money"><i class="icon-saudi_riyal"></i><span id="' . $id . '">' . number_format((float)$amount, 2) . '</span></span>';
        };
        ?>
        <div class="sr-scards pc-kpis" style="--sr-scard-cols: <?= sr_stat_cols($pcCardCount) ?>;">
            <?= $pcCard('indigo', 'mdi-account-multiple', __('employees', 'Employees'), __('in_this_payroll', 'In this payroll'),
                '<span class="sr-scard-value" id="summaryEmployeesValue">' . $pcTotalEmp . '</span>') ?>
            <?= $pcCard('slate', 'mdi-cash', __('total_gross_salary_label', 'Gross Total'), __('before_benefits_deductions', 'Before benefits & deductions'),
                $pcMoney('summaryGrossValue', $summary['gross'])) ?>
            <?= $pcCard('green', 'mdi-plus-circle', __('benefits_total', 'Benefits Total'), __('added_to_gross', 'Added to gross'),
                $pcMoney('summaryBenefitsValue', $summary['benefits'])) ?>
            <?= $pcCard('red', 'mdi-minus-circle', __('deductions_total', 'Deductions Total'), __('taken_from_gross', 'Taken from gross'),
                $pcMoney('summaryDeductionsValue', $summary['deductions'])) ?>
            <?= $pcCard('sky', 'mdi-wallet', __('net_salary_label', 'Net Total'), __('to_be_paid', 'To be paid'),
                $pcMoney('summaryNetValue', $summary['net'])) ?>
            <?= $pcCard((int)$summary['mismatch_count'] > 0 ? 'red' : 'green', 'mdi-scale-balance', __('calculation_conflicts', 'Calculation Conflicts'),
                htmlspecialchars(__('calculation_conflicts_short', 'Net ≠ Gross + Benefits − Deductions')),
                '<span class="sr-scard-value" id="summaryMismatchValue" title="' . htmlspecialchars(__('calculation_conflicts_tooltip', 'Employees where Net Salary does not match (Gross + Benefits - Deductions).'), ENT_QUOTES) . '">' . (int)$summary['mismatch_count'] . '</span>') ?>
            <?php if ($canCheckPayrollChecklistEmployees): ?>
                <?= $pcCard('green', 'mdi-account-check', __('checked_by_me', 'Checked By Me'), __('reviewed_and_ok', 'Reviewed and OK'),
                    '<span class="sr-scard-value" id="checkedEmployeesValue">' . $pcChecked . ' / ' . $pcTotalEmp . '</span>',
                    str_replace(['<i style', '<span class="sr-scard-pct">'], ['<i id="pcCheckedBar" style', '<span class="sr-scard-pct" id="pcCheckedPct">'], sr_scard_foot($pcChecked, $pcTotalEmp))) ?>
                <?= $pcCard($pcRemaining > 0 ? 'amber' : 'green', 'mdi-timer-sand', __('remaining_to_check', 'Remaining To Check'), __('still_to_review', 'Still to review'),
                    '<span class="sr-scard-value" id="remainingEmployeesValue">' . $pcRemaining . '</span>') ?>
            <?php endif; ?>
        </div>

        <?php if (empty($employees)): ?>
            <div class="sr-card">
                <div class="sr-empty"><i class="mdi mdi-clipboard-text"></i>
                    <strong style="display: block; color: var(--sr-text); font-size: 15px;"><?= __('no_generated_payrolls_for_month_info', 'No generated payrolls found for this month.') ?></strong>
                    <?= __('please_generate_payroll_first', 'Generate payroll first, then review the checklist report.') ?>
                </div>
            </div>
        <?php else: ?>
            <div class="sr-card pc-list">
                <div class="sr-card-head">
                    <h3 class="sr-card-title"><i class="fas fa-users"></i><?= __('employee_payroll_list', 'Employee Payroll Check List') ?></h3>
                    <?php if ($canCheckPayrollChecklistEmployees): ?>
                        <button type="button" id="bulkCheckSelectedBtn" class="sr-btn sr-btn-sm sr-btn-success" disabled onclick="bulkCheckSelectedEmployees()">
                            <i class="fas fa-check-double"></i> <?= __('check_selected', 'Check Selected') ?> (<span id="bulkCheckSelectedCount">0</span>)
                        </button>
                    <?php endif; ?>
                </div>
                <div class="table-responsive sr-table-wrap pc-table-wrap">
                    <table class="table sr-table table-hover table-compact" id="checklistTable" style="width:100%;">
                        <thead>
                            <tr>
                                <?php if ($canCheckPayrollChecklistEmployees): ?>
                                    <th class="print-hide-status text-center"><input type="checkbox" id="selectAllUncheckedBox" title="<?= htmlspecialchars(__('select_all_unchecked', 'Select all unchecked'), ENT_QUOTES) ?>"></th>
                                <?php endif; ?>
                                <th><?= __('emp_id') ?></th>
                                <th><?= __('name') ?></th>
                                <th><?= __('department') ?></th>
                                <th><?= __('total_gross_salary_label', 'Total Gross Salary') ?></th>
                                <th><?= __('benefits_total', 'Total Benefits') ?></th>
                                <th><?= __('deductions_total', 'Total Deductions') ?></th>
                                <th><?= __('net_salary_label', 'Total Net Salary') ?></th>
                                <th><?= __('difference', 'Difference') ?></th>
                                <th class="print-hide-status"><?= __('status') ?></th>
                                <th class="print-hide-status text-center"><?= __('actions') ?></th>
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
                                    <?php if ($canCheckPayrollChecklistEmployees): ?>
                                        <td class="print-hide-status text-center">
                                            <?php if ($requiresChecklistReview && !$isEmployeeChecked && $empFeedback['open_count'] === 0): ?>
                                                <input type="checkbox" class="checklist-row-select" data-emp-id="<?= htmlspecialchars((string)$employee['emp_id'], ENT_QUOTES) ?>">
                                            <?php endif; ?>
                                        </td>
                                    <?php endif; ?>
                                    <td><?= htmlspecialchars((string)$employee['emp_id']) ?></td>
                                    <td class="pc-emp"><?= htmlspecialchars(getDisplayName((string)($employee['employee_name'] ?? ''))) ?></td>
                                    <td><?= htmlspecialchars(getDisplayName((string)(($is_rtl ?? false) ? ($employee['department_name_ar'] ?? $employee['department_name'] ?? 'N/A') : ($employee['department_name'] ?? $employee['department_name_ar'] ?? 'N/A')))) ?></td>
                                    <td class="pc-num" data-order="<?= (float)($employee['total_gross_salary'] ?? 0) ?>"><i class="icon-saudi_riyal"></i> <?= number_format((float)($employee['total_gross_salary'] ?? 0), 2) ?></td>
                                    <td class="pc-num" data-order="<?= (float)($employee['total_benefits'] ?? 0) ?>"><i class="icon-saudi_riyal"></i> <?= number_format((float)($employee['total_benefits'] ?? 0), 2) ?></td>
                                    <td class="pc-num" data-order="<?= (float)($employee['total_deductions'] ?? 0) ?>"><i class="icon-saudi_riyal"></i> <?= number_format((float)($employee['total_deductions'] ?? 0), 2) ?></td>
                                    <td class="pc-num" data-order="<?= (float)($employee['net_salary'] ?? 0) ?>"><i class="icon-saudi_riyal"></i> <?= number_format((float)($employee['net_salary'] ?? 0), 2) ?></td>
                                    <td class="pc-num <?= abs($difference) < 0.01 ? 'diff-zero' : 'diff-positive' ?>"><?= number_format($difference, 2) ?></td>
                                    <td class="print-hide-status"><span class="pill-badge <?= $statusBadge ?>"><i class="fas <?= $statusIcon ?>"></i> <?= htmlspecialchars(getDisplayName(ucfirst($statusValue))) ?></span></td>
                                    <td class="text-center print-hide-status">
                                        <div class="btn-group dropdown sr-actions">
                                            <a href="javascript:void(0);" class="table-action-btn dropdown-toggle arrow-none btn btn-light btn-sm" data-toggle="dropdown" aria-expanded="false">
                                                <i class="mdi mdi-dots-horizontal"></i>
                                            </a>
                                            <div class="dropdown-menu dropdown-menu-right">
                                                <a class="dropdown-item text-dark" href="javascript:void(0);" onclick="openEmployeeDetail('<?= htmlspecialchars((string)$employee['emp_id'], ENT_QUOTES) ?>')">
                                                    <i class="fa fa-eye mr-2 font-18 vertical-middle"></i><?= __('view', 'View') ?>
                                                </a>
                                                <?php if ($canCheckPayrollChecklistEmployees && $requiresChecklistReview && $empFeedback['open_count'] === 0): ?>
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
    </div>
            <footer class="footer"><?= $site_footer ?? '' ?></footer>
        </div>
    </div>

    <script src="assets/js/jquery.min.js"></script>
    <script src="assets/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/metisMenu.min.js"></script>
    <script src="assets/js/waves.js"></script>
    <script src="assets/js/jquery.slimscroll.js"></script>
    <script src="plugins/datatables/jquery.dataTables.min.js"></script>
    <script src="plugins/datatables/dataTables.bootstrap4.min.js"></script>
    <script src="./plugins/select2/js/select2.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js"></script>
    <script> window.lang = window.lang || <?= json_encode($GLOBALS['translations'] ?? []) ?>;</script>
    <script src="assets/js/jquery.core.js"></script>
    <script src="assets/js/jquery.app.js"></script>
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
        const checklistEmpIdColIndex = <?= $canCheckPayrollChecklistEmployees ? 1 : 0 ?>;
        const checklistReviewState = {
            canManage: <?= $canCheckPayrollChecklistEmployees ? 'true' : 'false' ?>,
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

        // "More Actions" popup: secondary header actions as tiles (template #pcMoreActionsTpl).
        // Each tile closes the popup first, then runs the original action (they open their own popups).
        function openPcMoreActions() {
            const tpl = document.getElementById('pcMoreActionsTpl');
            if (!tpl) return;
            const sub = [<?= json_encode($monthTitle, JSON_UNESCAPED_UNICODE) ?>, <?= json_encode($requestInvNo, JSON_UNESCAPED_UNICODE) ?>].filter(Boolean).join(' · ');
            Swal.fire({
                title: '<div class="ma-head"><div style="font-size:18px;font-weight:700;color:var(--ma-text);">' + esc(<?= json_encode(__('more_actions', 'More Actions')) ?>) + '</div>'
                    + (sub ? '<p>' + esc(sub) + '</p>' : '') + '</div>',
                html: '<div class="menu-items-container">' + tpl.innerHTML + '</div>',
                showConfirmButton: false,
                showCloseButton: true,
                customClass: { container: 'more-actions-modal', popup: 'sr-page' },
                width: '620px',
                padding: '0',
                didOpen: function() {
                    const box = Swal.getHtmlContainer();
                    const pending = Number(window.pcPendingImportCount || 0);
                    box.querySelectorAll('[data-pc-import-count]').forEach(function(el) {
                        el.textContent = String(pending);
                        el.style.display = pending > 0 ? 'inline-block' : 'none';
                    });
                    box.querySelectorAll('.menu-item').forEach(function(item) {
                        item.setAttribute('tabindex', '0');
                        const run = function() {
                            const href = item.getAttribute('data-pc-href');
                            if (href) {
                                if (item.getAttribute('data-pc-blank') === '1') { window.open(href, '_blank', 'noopener'); Swal.close(); }
                                else { window.location.href = href; }
                                return;
                            }
                            const action = item.getAttribute('data-pc-action');
                            const actions = {
                                upload: typeof openManagerPayrollUploadModal === 'function' ? openManagerPayrollUploadModal : null,
                                review_import: typeof reviewManagerUploadedPayrollExcel === 'function' ? reviewManagerUploadedPayrollExcel : null,
                                export: exportChecklistExcel,
                                print: printFullChecklistTable
                            };
                            Swal.close();
                            if (actions[action]) setTimeout(actions[action], 120);
                        };
                        item.addEventListener('click', run);
                        item.addEventListener('keydown', function(e) {
                            if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); run(); }
                        });
                    });
                }
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
            const checkedPct = totalEmployees > 0 ? Math.min(100, Math.round(checkedCount / totalEmployees * 100)) : 0;
            const checkedBar = document.getElementById('pcCheckedBar');
            const checkedPctEl = document.getElementById('pcCheckedPct');
            if (checkedBar) checkedBar.style.width = checkedPct + '%';
            if (checkedPctEl) checkedPctEl.textContent = checkedPct + '%';
            if (remainingValue) {
                remainingValue.textContent = String(remainingEmployees);
                const remainingCard = remainingValue.closest('.sr-scard');
                if (remainingCard) {
                    remainingCard.classList.toggle('st-amber', remainingEmployees > 0);
                    remainingCard.classList.toggle('st-green', remainingEmployees === 0);
                }
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
            // Checked = solid green, not yet = green outline (sr-btn look, same height as Close)
            button.removeAttribute('style');
            button.className = 'sr-btn ' + (isChecked ? 'sr-btn-success' : 'pc-btn-check');
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

            if (checklistEmpIdColIndex === 1) {
                const row = document.querySelector('[data-employee-row-id="' + selectorEmpId + '"]');
                const checkboxCell = row ? row.cells[0] : null;
                if (checkboxCell) {
                    const empData = employeeMap[empId] || {};
                    const requiresReview = !!empData.requires_checklist_review;
                    const hasOpenFeedback = Number(empData.open_feedback_count || 0) > 0;
                    const shouldShowCheckbox = requiresReview && !isChecked && !hasOpenFeedback;
                    const existingBox = checkboxCell.querySelector('.checklist-row-select');
                    if (shouldShowCheckbox && !existingBox) {
                        checkboxCell.innerHTML = '<input type="checkbox" class="checklist-row-select" data-emp-id="' + esc(empId) + '">';
                    } else if (!shouldShowCheckbox && existingBox) {
                        checkboxCell.innerHTML = '';
                    }
                }
                updateBulkCheckButtonState();
            }
        }

        function getSelectableChecklistCheckboxes() {
            if (checklistDataTable) {
                return checklistDataTable.rows({ search: 'applied' }).nodes().to$().find('.checklist-row-select');
            }
            return $('.checklist-row-select');
        }

        function updateBulkCheckButtonState() {
            const boxes = getSelectableChecklistCheckboxes();
            const checkedBoxes = boxes.filter(':checked');
            const btn = document.getElementById('bulkCheckSelectedBtn');
            const countEl = document.getElementById('bulkCheckSelectedCount');
            if (countEl) {
                countEl.textContent = String(checkedBoxes.length);
            }
            if (btn) {
                btn.disabled = checkedBoxes.length === 0;
            }

            const headerBox = document.getElementById('selectAllUncheckedBox');
            if (headerBox) {
                if (boxes.length === 0) {
                    headerBox.checked = false;
                    headerBox.indeterminate = false;
                    headerBox.disabled = true;
                } else {
                    headerBox.disabled = false;
                    headerBox.checked = checkedBoxes.length === boxes.length;
                    headerBox.indeterminate = checkedBoxes.length > 0 && checkedBoxes.length < boxes.length;
                }
            }
        }

        async function bulkCheckSelectedEmployees() {
            if (!checklistReviewState.canManage) {
                return;
            }

            const empIds = getSelectableChecklistCheckboxes().filter(':checked').map(function() {
                return $(this).attr('data-emp-id') || '';
            }).get().filter(Boolean);

            if (empIds.length === 0) {
                return;
            }

            const confirmResult = await Swal.fire({
                title: '<?= addslashes(__('confirm', 'Confirm')) ?>',
                text: '<?= addslashes(__('confirm_bulk_check_employees', 'Mark the selected employees as checked?')) ?>' + ' (' + empIds.length + ')',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: '<?= addslashes(__('confirm', 'Confirm')) ?>',
                cancelButtonText: '<?= addslashes(__('cancel', 'Cancel')) ?>'
            });

            if (!confirmResult.isConfirmed) {
                return;
            }

            const btn = document.getElementById('bulkCheckSelectedBtn');
            if (btn) {
                btn.disabled = true;
            }

            const total = empIds.length;
            let successCount = 0;
            let failCount = 0;

            const progressLabel = '<?= addslashes(__('checking_employees', 'Checking Employees')) ?>';
            const remainingLabel = '<?= addslashes(__('remaining', 'Remaining')) ?>';
            const doneLabel = '<?= addslashes(__('done', 'Done')) ?>';

            const buildProgressHtml = (doneCount) => `
                <div style="text-align:center;">
                    <div style="font-size:15px; color:#475569; margin-bottom:10px;">${progressLabel} ${doneCount} / ${total}</div>
                    <div style="font-size:42px; font-weight:700; color:#0f172a; line-height:1;">${total - doneCount}</div>
                    <div style="font-size:13px; color:#94a3b8; margin-top:4px;">${remainingLabel}</div>
                </div>`;

            Swal.fire({
                title: progressLabel,
                html: buildProgressHtml(0),
                allowOutsideClick: false,
                allowEscapeKey: false,
                showConfirmButton: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            for (const empId of empIds) {
                try {
                    const payload = new URLSearchParams();
                    payload.append('action', 'toggle_employee_check');
                    payload.append('request_inv_no', checklistReviewState.requestInvNo);
                    payload.append('month', checklistReviewState.month);
                    payload.append('emp_id', empId);
                    payload.append('checked', '1');

                    const response = await fetch('./includes/ajaxFile/payroll_approval_handler.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8' },
                        body: payload.toString()
                    });
                    const data = await response.json();
                    if (!response.ok || data.status !== 'success') {
                        throw new Error(data.message || 'Failed to update employee checklist status.');
                    }

                    checklistReviewState.checkedCount += data.is_checked ? 1 : 0;
                    updateEmployeeReviewButton(empId, !!data.is_checked, data.checked_at || '');
                    successCount++;
                } catch (error) {
                    failCount++;
                }

                Swal.update({ html: buildProgressHtml(successCount + failCount) });
            }

            syncChecklistReviewSummary();
            syncSummaryCardsFromVisibleRows();
            updateBulkCheckButtonState();

            if (btn) {
                btn.disabled = false;
            }

            Swal.fire({
                icon: failCount === 0 ? 'success' : 'warning',
                title: failCount === 0 ? '<?= addslashes(__('success', 'Success')) ?>' : '<?= addslashes(__('warning', 'Warning')) ?>',
                text: successCount + ' <?= addslashes(__('employees_checked_count_label', 'employee(s) checked')) ?>' + (failCount ? ', ' + failCount + ' <?= addslashes(__('failed', 'failed')) ?>' : '') + ` (${doneLabel})`
            });
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
                    return $(this).find('td').eq(checklistEmpIdColIndex).text().trim();
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
                const mismatchCard = mismatchEl.closest('.sr-scard');
                if (mismatchCard) {
                    mismatchCard.classList.toggle('st-red', totals.mismatch > 0);
                    mismatchCard.classList.toggle('st-green', totals.mismatch === 0);
                }
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

            // The open popup stays; only its content is reloaded (loadEmployeeDetailInline)
            openEmployeeDetail(targetEmployee.emp_id);
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
                return `<div class="pc-items-empty"><i class="mdi mdi-inbox"></i>${esc(emptyText)}</div>`;
            }
            return `<ul class="pc-items">${items.map(item => {
                const meta = [];
                if (Number(item.days || 0) > 0) meta.push(`${esc(item.days)} <?= addslashes(__('days', 'Days')) ?>`);
                if (Number(item.hours || 0) > 0) meta.push(`${esc(item.hours)} <?= addslashes(__('hours', 'Hours')) ?>`);
                if (item.calculation_type) meta.push(esc(String(item.calculation_type).replace(/_/g, ' ')));
                return `<li><div style="min-width:0;"><span class="pc-item-name">${esc(item[itemNameKey] || '')}</span>`
                    + (meta.length ? `<span class="pc-item-meta">${meta.join(' · ')}</span>` : '')
                    + `</div><span class="pc-item-amt"><i class="icon-saudi_riyal"></i> ${fmt(item.note || 0)}</span></li>`;
            }).join('')}</ul>`;
        }

        function buildSalaryAllowancesGrid(emp) {
            // Bank / IBAN moved to the employee details panel; zero amounts are dimmed
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
                { label: '<?= addslashes(__('guard_allowance_label', 'Guard')) ?>', value: emp.guard_allowance }
            ];
            return '<div class="pc-sal">' + items.map(item => {
                const zero = Math.abs(Number(item.value || 0)) < 0.005;
                return `<div class="pc-sal-row${zero ? ' is-zero' : ''}"><span>${esc(item.label)}</span><b><i class="icon-saudi_riyal"></i> ${fmt(item.value)}</b></div>`;
            }).join('') + '</div>';
        }

        function buildFeedbackHtml(feedbacks) {
            if (!Array.isArray(feedbacks) || feedbacks.length === 0) {
                return '';
            }

            const feedbackRowsHtml = feedbacks.map(item => {
                const createdAt = item.created_at ? new Date(item.created_at.replace(' ', 'T')).toLocaleString() : '-';
                const resolvedAt = item.resolved_at ? new Date(item.resolved_at.replace(' ', 'T')).toLocaleString() : '';
                const isResolved = String(item.status || '').toLowerCase() === 'resolved';
                return `
                    <div class="pc-fb-item">
                        <div class="pc-fb-top">
                            <span><i class="fas fa-user-check"></i> ${esc(item.approver_name || '-')}</span>
                            <span class="pill-badge ${isResolved ? 'pill-success' : 'pill-warning'}">${esc(item.status || 'open')}</span>
                        </div>
                        <div class="pc-fb-when"><i class="far fa-calendar-alt"></i> ${esc(item.payroll_month || '-')} &nbsp;·&nbsp; <i class="far fa-clock"></i> ${esc(createdAt)}</div>
                        <div class="pc-fb-note">${esc(item.feedback_note || '')}</div>
                        ${isResolved ? `<div class="pc-fb-resolved"><i class="fas fa-check-circle"></i> <?= addslashes(__('resolved_by', 'Resolved By')) ?>: ${esc(item.resolved_by_name || '-')} ${resolvedAt ? ('· ' + esc(resolvedAt)) : ''}</div>` : ''}
                    </div>`;
            }).join('');

            const hasActiveFeedback = feedbacks.some(item => {
                const status = String(item.status || '').trim().toLowerCase();
                return status === 'open' || status === 'submitted' || status === 'pending' || status === '';
            });
            // Resolved-only feedback starts collapsed behind a toggle
            const feedbackToggleHtml = !hasActiveFeedback
                ? `<div style="margin-bottom:10px;text-align:end;">
                        <button type="button" id="toggleFeedbackBlockBtn" class="sr-btn sr-btn-sm">
                            <i class="fas fa-eye"></i> <?= addslashes(__('show_approver_feedback', 'Show Approver Feedback')) ?> (${feedbacks.length})
                        </button>
                   </div>`
                : '';

            return `
                ${feedbackToggleHtml}
                <div id="approverFeedbackBlock" class="pc-fb${hasActiveFeedback ? '' : ' d-none'}">
                    <div class="pc-fb-head"><span><i class="fas fa-comment-dots"></i> <?= addslashes(__('approver_feedback', 'Approver Feedback')) ?></span><span class="sr-count">${feedbacks.length}</span></div>
                    <div class="pc-fb-list">${feedbackRowsHtml}</div>
                </div>`;
        }

        let checklistDetailLoadToken = 0;

        async function fetchEmployeeDetailFeedbacks(empId) {
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
                        return data.feedbacks;
                    }
                }
            } catch (err) {
                console.warn('Failed to fetch feedback:', err);
            }
            return [];
        }

        // Header + body + footer markup for one employee
        function buildEmployeeDetailView(empId, feedbacks) {
            const emp = employeeMap[empId];
            const navigationState = getChecklistEmployeeNavigationState(empId);
            const currentPosition = navigationState.currentIndex >= 0 ? navigationState.currentIndex + 1 : null;

            const isBalanced = Math.abs(Number(emp.net_difference || 0)) < 0.01;
            const feedbackHtml = buildFeedbackHtml(feedbacks);
            const isEmployeeChecked = !!emp.is_review_checked;
            const requiresChecklistReview = !!emp.requires_checklist_review;
            const ini = String(emp.employee_name || '?').trim().split(/\s+/).slice(0, 2).map(w => w[0]).join('').toUpperCase();
            const money = v => `<i class="icon-saudi_riyal"></i> ${fmt(v)}`;
            const sumOf = list => (Array.isArray(list) ? list : []).reduce((t, i) => t + Number(i.note || 0), 0);

            // Header: who + where + status
            const headerHtml = `
                <div class="pc-dhead">
                    <span class="pc-dhead-avatar">${esc(ini)}</span>
                    <div class="pc-dhead-main">
                        <span class="pc-dhead-name" title="${esc(emp.employee_name)}">${esc(emp.employee_name)}</span>
                        <div class="pc-dhead-meta">
                            <span class="pc-dhead-chip sr-mono"><i class="mdi mdi-pound"></i>${esc(emp.emp_id)}</span>
                            ${emp.department_name ? `<span class="pc-dhead-chip"><i class="mdi mdi-domain"></i>${esc(emp.department_name)}</span>` : ''}
                            ${emp.company_name ? `<span class="pc-dhead-chip"><i class="mdi mdi-city"></i>${esc(emp.company_name)}</span>` : ''}
                            <span class="pc-dhead-chip"><i class="mdi mdi-credit-card"></i>${esc(getPaymentTypeLabel(emp.payment_type))}</span>
                        </div>
                    </div>
                    <div class="pc-dhead-side">
                        <div class="pc-dhead-net">
                            <small><?= addslashes(__('net_salary_label', 'Net Salary')) ?></small>
                            <b><i class="icon-saudi_riyal"></i> ${fmt(emp.net_salary)}</b>
                        </div>
                        <div class="pc-dhead-pills">
                            <span class="pc-dhead-pill ${isBalanced ? 'ok' : 'bad'}"><i class="fas ${isBalanced ? 'fa-check-circle' : 'fa-triangle-exclamation'}"></i>
                                ${isBalanced ? '<?= addslashes(__('calculation_ok', 'Calculation OK')) ?>' : '<?= addslashes(__('calculation_conflict', 'Calculation Conflict')) ?>'}</span>
                            ${requiresChecklistReview ? `<span class="pc-dhead-pill ${isEmployeeChecked ? 'ok' : 'wait'}"><i class="fas ${isEmployeeChecked ? 'fa-user-check' : 'fa-hourglass-half'}"></i>
                                ${isEmployeeChecked ? '<?= addslashes(__('checked', 'Checked')) ?>' : '<?= addslashes(__('not_checked_yet', 'Not checked yet')) ?>'}</span>` : ''}
                        </div>
                    </div>
                </div>`;

            const kpi = (cls, label, value) => `<div class="pc-kpi-box ${cls}"><span class="pc-kpi-label">${label}</span><span class="pc-kpi-value">${value}</span></div>`;
            const detailHtml = `
                ${feedbackHtml}
                <div class="pc-kpi">
                    ${kpi('', '<?= addslashes(__('total_gross_salary_label', 'Gross Salary')) ?>', money(emp.total_gross_salary))}
                    ${kpi('k-green', '<?= addslashes(__('benefits_total', 'Benefits')) ?>', money(emp.total_benefits))}
                    ${kpi('k-red', '<?= addslashes(__('deductions_total', 'Deductions')) ?>', money(emp.total_deductions))}
                    ${kpi('k-indigo', '<?= addslashes(__('expected_net_salary', 'Expected Net')) ?>', money(emp.expected_net_salary))}
                    ${kpi('k-sky', '<?= addslashes(__('net_salary_label', 'Actual Net')) ?>', money(emp.net_salary))}
                    ${kpi(isBalanced ? 'k-green' : 'k-red', '<?= addslashes(__('difference', 'Difference')) ?>', money(emp.net_difference))}
                </div>
                <div class="pc-formula">
                    <i class="mdi mdi-calculator" style="color:var(--sr-accent);font-size:16px;"></i>
                    <span><?= addslashes(__('gross', 'Gross')) ?> <b>${fmt(emp.total_gross_salary)}</b></span>
                    <span class="op">+</span><span><?= addslashes(__('benefits', 'Benefits')) ?> <b>${fmt(emp.total_benefits)}</b></span>
                    <span class="op">−</span><span><?= addslashes(__('deductions', 'Deductions')) ?> <b>${fmt(emp.total_deductions)}</b></span>
                    <span class="op">=</span><span><?= addslashes(__('expected_net_salary', 'Expected Net')) ?> <b>${fmt(emp.expected_net_salary)}</b></span>
                    <span class="op">·</span><span><?= addslashes(__('net_salary_label', 'Actual Net')) ?> <b>${fmt(emp.net_salary)}</b></span>
                    <span class="${isBalanced ? 'ok' : 'bad'}"><i class="fas ${isBalanced ? 'fa-check-circle' : 'fa-triangle-exclamation'}"></i>
                        ${isBalanced ? '<?= addslashes(__('calculation_ok', 'Calculation OK')) ?>' : '<?= addslashes(__('difference', 'Difference')) ?> ' + fmt(emp.net_difference)}</span>
                </div>
                <div class="pc-dgrid">
                    <div class="pc-panel">
                        <div class="pc-panel-head"><span><i class="fas fa-wallet"></i><?= addslashes(__('salary_allowances_breakdown', 'Salary & Allowances Breakdown')) ?></span>
                            <span class="pc-panel-total">${money(emp.total_gross_salary)}</span></div>
                        ${buildSalaryAllowancesGrid(emp)}
                    </div>
                    <div class="pc-panel">
                        <div class="pc-panel-head"><span><i class="fas fa-id-badge"></i><?= addslashes(__('employee_details', 'Employee Details')) ?></span></div>
                        <ul class="pc-kv">
                            <li><span><?= addslashes(__('department')) ?></span><b>${esc(emp.department_name || '-')}</b></li>
                            <li><span><?= addslashes(__('company', 'Company')) ?></span><b>${esc(emp.company_name || '-')}</b></li>
                            <li><span><?= addslashes(__('iqama', 'Iqama')) ?></span><b class="sr-mono">${esc(emp.iqama || 'N/A')}</b></li>
                            <li><span><?= addslashes(__('salary_payment_type_label', 'Payment Type')) ?></span><b>${esc(getPaymentTypeLabel(emp.payment_type))}</b></li>
                            <li><span><?= addslashes(__('bank_name', 'Bank')) ?></span><b>${esc(emp.bank_name_s || 'N/A')}</b></li>
                            <li><span><?= addslashes(__('iban', 'IBAN')) ?></span><b class="sr-mono">${esc(emp.iban || 'N/A')}</b></li>
                        </ul>
                    </div>
                </div>
                <div class="pc-dgrid even">
                    <div class="pc-panel p-green">
                        <div class="pc-panel-head"><span><i class="fas fa-plus-circle"></i><?= addslashes(__('benefits_section', 'Benefits')) ?></span>
                            <span class="pc-panel-total">${money(sumOf(emp.benefits_list))}</span></div>
                        ${buildItemsList(emp.benefits_list, 'benefit', '<?= addslashes(__('no_benefits_added', 'No benefits added')) ?>')}
                    </div>
                    <div class="pc-panel p-red">
                        <div class="pc-panel-head"><span><i class="fas fa-minus-circle"></i><?= addslashes(__('deductions_section', 'Deductions')) ?></span>
                            <span class="pc-panel-total">${money(sumOf(emp.deductions_list))}</span></div>
                        ${buildItemsList(emp.deductions_list, 'deduction', '<?= addslashes(__('no_deductions_added', 'No deductions added')) ?>')}
                    </div>
                </div>
            `;

            const modalFooterHtml = `
                <div class="pc-nav">
                    <button type="button" id="prevChecklistEmployeeBtn" class="sr-btn sr-btn-sm checklist-nav-btn" ${navigationState.previousEmployee ? '' : 'disabled'}>
                        <i class="fas fa-arrow-left"></i><span><?= addslashes(__('previous', 'Previous')) ?></span>
                    </button>
                    ${currentPosition ? `<span class="pc-nav-counter">${currentPosition} / ${navigationState.employees.length} &nbsp;<kbd>←</kbd> <kbd>→</kbd></span>` : '<span></span>'}
                    <button type="button" id="nextChecklistEmployeeBtn" class="sr-btn sr-btn-sm checklist-nav-btn" ${navigationState.nextEmployee ? '' : 'disabled'}>
                        <span><?= addslashes(__('next', 'Next')) ?></span><i class="fas fa-arrow-right"></i>
                    </button>
                </div>
            `;

            return {
                emp,
                navigationState,
                isEmployeeChecked,
                requiresChecklistReview,
                html: headerHtml + '<div class="pc-dbody">' + detailHtml + '</div>',
                footer: modalFooterHtml
            };
        }

        function isEmployeeDetailOpen() {
            const popup = Swal.isVisible() ? Swal.getPopup() : null;
            return !!(popup && popup.classList.contains('pc-detail-popup'));
        }

        function setEmployeeDetailLoading(isLoading) {
            const popup = Swal.getPopup();
            if (!popup) {
                return;
            }
            let overlay = popup.querySelector('.pc-dloader');
            if (!overlay) {
                overlay = document.createElement('div');
                overlay.className = 'pc-dloader';
                overlay.innerHTML = '<div class="pc-dloader-box"><span class="pc-spinner"></span><span><?= addslashes(__('loading', 'Loading')) ?>...</span></div>';
                popup.appendChild(overlay);
            }
            overlay.classList.toggle('show', isLoading);
            if (isLoading) {
                popup.querySelectorAll('.checklist-nav-btn, #modalReviewToggleBtn').forEach(btn => { btn.disabled = true; });
            }
        }

        // Wires the buttons / keys of the content currently in the popup
        function bindEmployeeDetailView(empId, view) {
            const popup = Swal.getPopup();
            if (!popup) {
                return;
            }
            const navigationState = view.navigationState;
            attachChecklistModalKeyboardNavigation(navigationState);

            // Mark Checked button belongs to one employee: rebuild it for each one
            const oldReviewBtn = document.getElementById('modalReviewToggleBtn');
            if (oldReviewBtn) {
                oldReviewBtn.remove();
            }
            const swalActions = Swal.getActions();
            const empHasOpenFeedback = (Number(view.emp.open_feedback_count || 0) > 0);
            if (checklistReviewState.canManage && swalActions && !empHasOpenFeedback && view.requiresChecklistReview) {
                const modalReviewBtn = document.createElement('button');
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
                updateEmployeeReviewButton(empId, view.isEmployeeChecked, '');
            }

            const prevBtn = popup.querySelector('#prevChecklistEmployeeBtn');
            const nextBtn = popup.querySelector('#nextChecklistEmployeeBtn');
            const toggleFeedbackBtn = popup.querySelector('#toggleFeedbackBlockBtn');

            if (prevBtn) {
                prevBtn.addEventListener('click', function() {
                    navigateChecklistEmployeeFromModal(navigationState.previousEmployee);
                });
            }

            if (nextBtn) {
                nextBtn.addEventListener('click', function() {
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
        }

        // Popup already open (Previous / Next / arrow keys): show a loader and swap the content only
        async function loadEmployeeDetailInline(empId) {
            const token = ++checklistDetailLoadToken;
            checklistModalNavigationBusy = true;
            setEmployeeDetailLoading(true);

            const feedbacks = await fetchEmployeeDetailFeedbacks(empId);
            if (token !== checklistDetailLoadToken || !isEmployeeDetailOpen()) {
                return; // closed or superseded meanwhile
            }

            const view = buildEmployeeDetailView(empId, feedbacks);
            const container = Swal.getHtmlContainer();
            const footer = Swal.getFooter();
            if (container) {
                container.innerHTML = view.html;
            }
            if (footer) {
                footer.innerHTML = view.footer;
            }
            bindEmployeeDetailView(empId, view);
            setEmployeeDetailLoading(false);
            checklistModalNavigationBusy = false;
        }

        async function openEmployeeDetail(empId) {
            if (!employeeMap[empId]) return;

            if (isEmployeeDetailOpen()) {
                return loadEmployeeDetailInline(empId);
            }

            const token = ++checklistDetailLoadToken;
            const feedbacks = await fetchEmployeeDetailFeedbacks(empId);
            if (token !== checklistDetailLoadToken) {
                return;
            }
            const view = buildEmployeeDetailView(empId, feedbacks);

            Swal.fire({
                html: view.html,
                footer: view.footer,
                width: '1240px',
                padding: '0',
                allowOutsideClick: false,
                stopKeydownPropagation: false,
                keydownListenerCapture: true,
                customClass: {
                    popup: 'pc-detail-popup sr-page', // sr-page = design tokens (popups render outside the page)
                    container: 'pc-detail-container',
                    confirmButton: 'sr-btn'
                },
                buttonsStyling: false,
                showCloseButton: true,
                showConfirmButton: true,
                confirmButtonText: '<?= addslashes(__('close', 'Close')) ?>',
                focusConfirm: false,
                didOpen: () => {
                    bindEmployeeDetailView(empId, view);
                },
                willClose: () => {
                    checklistDetailLoadToken++;
                    checklistModalNavigationBusy = false;
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
                window.pcPendingImportCount = pendingCount; // also shown on the tile in the More Actions popup
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
            // deduction_type used to be hardcoded to 'hourly_deduction' here, no matter what
            // the row actually contained - so a pure deduction_days entry (no hours/minutes)
            // always got submitted as hourly, and the server then nulled out the days value.
            // Infer from which of days vs hours/minutes was actually filled in.
            const rawDeductionDays = Number((row && row.deduction_days) || 0);
            const rawDeductionHours = Number((row && row.deduction_hours) || 0);
            const rawDeductionMinutes = Number((row && row.deduction_minutes) || 0);
            const inferredDeductionType = (rawDeductionDays > 0 && rawDeductionHours <= 0 && rawDeductionMinutes <= 0)
                ? 'daily_deduction'
                : 'hourly_deduction';

            const normalizedRow = {
                checkpoint_code: String((row && row.checkpoint_code) || '').trim(),
                emp_id: String((row && row.emp_id) || '').trim(),
                month: String((row && row.month) || '').trim(),
                benefit_type: 'by_hours',
                overtime_value: String((row && row.overtime_value) || '').trim(),
                overtime_hours: String((row && row.overtime_hours) || (row && row.benefit_hours) || '').trim(),
                overtime_minutes: String((row && row.overtime_minutes) || (row && row.benefit_minutes) || '').trim(),
                overtime_reason: String((row && row.overtime_reason) || (row && row.benefit_reason) || '').trim(),
                deduction_type: inferredDeductionType,
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
            // These tables are paginated DataTables (pageLength: 10) - a plain
            // querySelectorAll only sees the CURRENTLY RENDERED page's <tr> elements,
            // since DataTables removes off-page rows from the DOM on each draw. That
            // silently limited every import to whatever page happened to be showing
            // (page 1 = the first 10 rows) instead of every reviewed row. Read through
            // the DataTable API instead, which holds every row regardless of page.
            const benefitRowElements = payrollImportBenefitsDataTable
                ? payrollImportBenefitsDataTable.rows().nodes().toArray()
                : document.querySelectorAll('#payrollImportBenefitsTable tbody tr[data-row-index]');
            const deductionRowElements = payrollImportDeductionsDataTable
                ? payrollImportDeductionsDataTable.rows().nodes().toArray()
                : document.querySelectorAll('#payrollImportDeductionsTable tbody tr[data-row-index]');

            // Rows not on the currently-shown page are detached from the live document by
            // DataTables (only the active page's <tr>s stay attached), so a fresh
            // document.querySelector() by row-index would come back null for them even
            // though the node itself still exists. Look them up from the node lists above
            // instead of re-querying the document.
            const benefitRowByIndex = new Map(Array.from(benefitRowElements).map((el) => [el.dataset.rowIndex, el]));
            const deductionRowByIndex = new Map(Array.from(deductionRowElements).map((el) => [el.dataset.rowIndex, el]));

            const rowIndexes = Array.from(new Set([
                ...benefitRowByIndex.keys(),
                ...deductionRowByIndex.keys()
            ]));

            const getFieldValue = (row, field) => {
                if (!row) return '';
                const input = row.querySelector(`.payroll-import-review-input[data-field="${field}"]`);
                return input ? String(input.value || '').trim() : '';
            };

            return rowIndexes.map((rowIndex) => {
                const benefitRow = benefitRowByIndex.get(rowIndex);
                const deductionRow = deductionRowByIndex.get(rowIndex);

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
                    const importError = new Error((result && result.message) || 'Failed to import reviewed manager file.');
                    importError.details = (result && (result.errors || result.skipped_details)) || [];
                    throw importError;
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

                const skippedDetails = Array.isArray(result.skipped_details) ? result.skipped_details : [];
                const skippedItems = Array.isArray(result.skipped_items) ? result.skipped_items : [];
                const skippedEmpIds = Array.isArray(result.skipped_emp_ids) ? result.skipped_emp_ids : [];
                const skippedCount = Number(result.skipped_rows || 0);
                const fmtAmount = (value) => Number(value || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });

                const skippedReasonRows = (skippedItems.length > 0 ? skippedItems : skippedDetails.map((detail) => ({ reason: detail })))
                    .map((item) => {
                        const empLabel = item.emp_id ? `<span class="pill-badge pill-secondary" style="margin-right:8px;">Emp ${escapePayrollImportHtml(String(item.emp_id))}</span>` : '';
                        return `<li style="margin-bottom:6px;">${empLabel}${escapePayrollImportHtml(String(item.reason || ''))}</li>`;
                    }).join('');

                const skippedSectionHtml = skippedCount > 0
                    ? `<div class="list-card" style="margin-top:16px;">
                           <div class="list-card-header">
                               <p class="list-card-title"><i class="fas fa-triangle-exclamation" style="color:#ad7b00;"></i> Skipped Rows</p>
                               <span class="pill-badge pill-warning">${skippedCount} skipped${skippedEmpIds.length > 0 ? ' &middot; ' + skippedEmpIds.length + ' employee(s)' : ''}</span>
                           </div>
                           <div style="max-height:240px; overflow:auto; padding:12px 16px;">
                               <ul style="margin:0; padding-left:0; list-style:none; color:#5d7286; font-size:13px;">${skippedReasonRows}</ul>
                           </div>
                       </div>`
                    : '';

                const successHtml = `
                    <div style="text-align:left;">
                        <p style="color:#5d7286; margin-bottom:16px;">${escapePayrollImportHtml(result.message || 'Payroll imported successfully from manager upload.')}</p>
                        <div class="calc-grid" style="margin-bottom:0;">
                            <div class="calc-box"><div class="calc-title"><i class="fas fa-list-check"></i> Records Imported</div><div class="calc-value">${Number(result.processed_rows || 0)}</div></div>
                            <div class="calc-box"><div class="calc-title"><i class="fas fa-users"></i> Employees Updated</div><div class="calc-value">${Number(result.updated_payrolls || 0)}</div></div>
                            <div class="calc-box"><div class="calc-title"><i class="fas fa-gift"></i> Benefits Imported</div><div class="calc-value" style="color:#1f8b3c;">${Number(result.benefits_records_count || 0)} <span style="font-size:13px; font-weight:600; color:#70859a;">records</span></div><div style="font-size:13px; color:#70859a; margin-top:2px;"><i class="icon-saudi_riyal"></i> ${fmtAmount(result.total_benefits_amount)} total</div></div>
                            <div class="calc-box"><div class="calc-title"><i class="fas fa-minus-circle"></i> Deductions Imported</div><div class="calc-value" style="color:#c43636;">${Number(result.deductions_records_count || 0)} <span style="font-size:13px; font-weight:600; color:#70859a;">records</span></div><div style="font-size:13px; color:#70859a; margin-top:2px;"><i class="icon-saudi_riyal"></i> ${fmtAmount(result.total_deductions_amount)} total</div></div>
                        </div>
                        ${skippedSectionHtml}
                    </div>`;

                await Swal.fire({
                    icon: 'success',
                    title: '<?= addslashes(__('success', 'Success')) ?>',
                    html: successHtml,
                    width: skippedCount > 0 ? '640px' : '540px',
                    confirmButtonColor: '#2563eb',
                    allowOutsideClick: false,
                });
                window.location.reload();
            } catch (error) {
                Swal.close();
                const details = Array.isArray(error.details) ? error.details : [];
                const errorHtml = details.length > 0
                    ? `<p>${escapePayrollImportHtml(error.message || 'Failed to import manager uploaded file.')}</p>
                       <div style="max-height:200px; overflow:auto; text-align:left; border:1px solid #e2e8f0; border-radius:8px; padding:8px;">
                           <ul style="margin:0; padding-left:18px;">${details.map((detail) => `<li>${escapePayrollImportHtml(String(detail))}</li>`).join('')}</ul>
                       </div>`
                    : (error.message || 'Failed to import manager uploaded file.');
                Swal.fire({
                    icon: 'error',
                    title: '<?= addslashes(__('error', 'Error')) ?>',
                    html: errorHtml
                });
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
                order: [[checklistEmpIdColIndex, 'asc']],
                columnDefs: checklistEmpIdColIndex === 1 ? [{ targets: 0, orderable: false, searchable: false }] : [],
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
                updateBulkCheckButtonState();
            });

            $('#checklistTable').on('change', '.checklist-row-select', function() {
                updateBulkCheckButtonState();
            });

            $(document).on('change', '#selectAllUncheckedBox', function() {
                getSelectableChecklistCheckboxes().prop('checked', this.checked);
                updateBulkCheckButtonState();
            });

            updateBulkCheckButtonState();

            syncFollowupButton();
            syncChecklistReviewSummary();
            syncSummaryCardsFromVisibleRows();
            window.setTimeout(syncSummaryCardsFromVisibleRows, 100);
        });
    </script>
</body>
</html>
<?php $conDB->close(); ?>