<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/session_check.php';
require_once __DIR__ . '/includes/helper_functions.php';
require_once __DIR__ . '/includes/init.php';
require_once __DIR__ . '/includes/payroll_approval_helpers.php';
require_once __DIR__ . '/includes/special_access_helper.php';

// Restrict access: Employees cannot view this page, unless explicitly
// granted via app_settings -> Special Access.
if (
    isset($isEmployee) && $isEmployee === true
    && !user_has_special_access($conDB, $empid ?? '', 'access_all_payroll_approvals', $user_role ?? '', $user_type ?? '', $is_system_admin ?? false)
) {
    header('Location: ./profile.php');
    exit();
}

$currentUserTypeForAccess = strtolower(trim((string)($user_type ?? '')));
$currentEmpTypeForAccess = strtolower(trim((string)($emp_type ?? '')));
$isFinanceOfficerUser = ($currentUserTypeForAccess === 'finance_officer')
    || ($currentUserTypeForAccess === 'finance' && $currentEmpTypeForAccess !== 'manager' && (int)($user_dept ?? 0) === 2);

$pdo = getDbConnection();
ensurePayrollApprovalTable($pdo);
ensurePayrollChecklistSupportTables($pdo);
$requestTypeId = ensurePayrollApprovalRequestType($pdo);

$allStatuses = [
    'my_pending' => __('my_pending_queue'),
    'my_dept' => __('my_department_requests'),
    'pending_approval' => __('all_pending'),
    'approved' => __('approved'),
    'rejected' => __('rejected'),
    'completed' => __('completed'),
    'all' => __('all_requests')
];

$searchTerm = $_GET['search'] ?? '';
$limitOptions = [9, 12, 15];
$perpage = 9;
$itemsPerPage = isset($_GET['limit']) && in_array((int)$_GET['limit'], $limitOptions, true) ? (int)$_GET['limit'] : $perpage;
$showAll = isset($_GET['limit']) && $_GET['limit'] === 'all';
if ($showAll) {
    $itemsPerPage = -1;
}

$currentPage = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
if ($currentPage < 1) {
    $currentPage = 1;
}

$currentFilter = $_GET['status'] ?? null;
if ($currentFilter === null) {
    if ($is_system_admin) {
        $currentFilter = 'all';
    } else {
        $currentFilter = 'my_pending';
    }
}

$canSeeAllDepts = ($is_system_admin ?? false) || ($isHR ?? false);

$where = [];
$params = [];
$types = '';
$joins = " LEFT JOIN payroll_approval_requests pr ON pr.payroll_month = p_months.month_year
           LEFT JOIN employees req_emp ON req_emp.emp_id = pr.requested_by
           LEFT JOIN (
               SELECT ra_pick.request_inv_no, ra_pick.approver_id, ra_pick.approval_level
               FROM request_approvers ra_pick
               INNER JOIN (
                   SELECT request_inv_no, MIN(approval_level) AS min_pending_level
                   FROM request_approvers
                   WHERE request_type_id = ? AND status = 'pending'
                   GROUP BY request_inv_no
               ) ra_min
                   ON ra_min.request_inv_no = ra_pick.request_inv_no
                  AND ra_min.min_pending_level = ra_pick.approval_level
               WHERE ra_pick.request_type_id = ? AND ra_pick.status = 'pending'
           ) ra_pending ON ra_pending.request_inv_no = pr.request_inv_no
           LEFT JOIN employees approver_emp ON approver_emp.emp_id = ra_pending.approver_id
           LEFT JOIN (
               SELECT
                   v.request_inv_no,
                   v.payroll_month,
                   CASE WHEN v.is_confirmed = 1 THEN 1 ELSE 0 END AS verification_confirmed,
                   CASE WHEN v.officer_approved = 1 THEN 1 ELSE 0 END AS officer_approved,
                   v.selected_company_ids,
                   v.confirmed_at,
                   v.finance_officer_emp_id
               FROM payroll_finance_verification v
               INNER JOIN (
                   SELECT request_inv_no, payroll_month, MAX(id) AS max_id
                   FROM payroll_finance_verification
                   GROUP BY request_inv_no, payroll_month
               ) fv_latest ON fv_latest.max_id = v.id
           ) fin_verify ON fin_verify.request_inv_no = pr.request_inv_no AND fin_verify.payroll_month = p_months.month_year
           LEFT JOIN (
               SELECT
                   pec.request_inv_no,
                   pec.payroll_month,
                   COUNT(DISTINCT CASE WHEN pec.is_checked = 1 THEN pec.emp_id END) AS checked_employees
               FROM payroll_checklist_employee_checks pec
               INNER JOIN admin_login al ON al.emp_id = pec.approver_id
               WHERE LOWER(TRIM(al.user_type)) = 'hr_payroll'
               GROUP BY pec.request_inv_no, pec.payroll_month
           ) hr_checks ON hr_checks.request_inv_no = pr.request_inv_no AND hr_checks.payroll_month = p_months.month_year ";
$params[] = $requestTypeId;
$params[] = $requestTypeId;
$types .= 'i';
$types .= 'i';

if ($currentFilter === 'my_pending') {
    // Match either the HR approval chain (ra_pending) or a finance verification
    // assignment - a manager can have several officers assigned concurrently to the
    // same request (one row each), so this checks all of them directly rather than
    // through the fin_verify join, which only surfaces a single representative row
    // per request for card display.
    $where[] = '(ra_pending.approver_id = ? OR EXISTS (
        SELECT 1 FROM payroll_finance_verification pfv_mine
        WHERE pfv_mine.request_inv_no = pr.request_inv_no
          AND pfv_mine.payroll_month = p_months.month_year
          AND pfv_mine.finance_officer_emp_id = ?
    ))';
    $params[] = (string)$empid;
    $params[] = (string)$empid;
    $types .= 'ss';
} elseif ($currentFilter === 'my_dept') {
    $where[] = 'req_emp.dept = ?';
    $params[] = (int)$user_dept;
    $types .= 'i';
} elseif (in_array($currentFilter, ['pending_approval', 'approved', 'rejected', 'completed'], true)) {
    $where[] = 'pr.status = ?';
    $params[] = $currentFilter;
    $types .= 's';
}

if ($searchTerm !== '') {
    $where[] = '(pr.request_inv_no LIKE ? OR pr.payroll_month LIKE ? OR req_emp.name LIKE ? OR req_emp.emp_id LIKE ?)';
    $search = '%' . $searchTerm . '%';
    array_push($params, $search, $search, $search, $search);
    $types .= 'ssss';
}

if (!$canSeeAllDepts && $currentFilter !== 'my_pending') {
    $where[] = '(req_emp.dept = ? OR EXISTS (
        SELECT 1 FROM request_approvers ra_any
        WHERE ra_any.request_inv_no = pr.request_inv_no
            AND ra_any.request_type_id = ?
            AND ra_any.approver_id = ?
    ))';
    $params[] = (int)$user_dept;
    $params[] = $requestTypeId;
    $params[] = (string)$empid;
    $types .= 'iss';
}

$whereSql = '';
if (!empty($where)) {
    $whereSql = ' WHERE ' . implode(' AND ', $where);
}

$countSql = "SELECT COUNT(DISTINCT p_months.month_year) AS total
    FROM (SELECT DISTINCT month_year FROM payrolls) p_months
    $joins
    $whereSql";
$countStmt = $conDB->prepare($countSql);
if (!$countStmt) {
    die('Count query prepare failed: ' . $conDB->error);
}
if (!empty($params)) {
    $countStmt->bind_param($types, ...$params);
}
$countStmt->execute();
$totalItems = (int)($countStmt->get_result()->fetch_assoc()['total'] ?? 0);
$countStmt->close();

$totalPages = $showAll ? 1 : (int)ceil($totalItems / max(1, $itemsPerPage));
if ($currentPage > $totalPages && $totalPages > 0) {
    $currentPage = $totalPages;
}

$requests = [];
if ($totalItems > 0) {
    $mainSql = "SELECT
            p_months.month_year AS payroll_month,
            p_months.employee_count,
            p_months.total_net_salary,
            p_months.bank_total_net_salary,
            p_months.checklist_employee_count,
            p_months.checklist_total_net_salary,
            pr.id AS approval_id,
            pr.request_inv_no,
            pr.status AS approval_status,
            pr.requested_by,
            pr.created_at AS approval_created_at,
            pr.approved_at,
            pr.processed_at,
            req_emp.name AS requester_name,
            req_emp.dept AS requester_dept,
            ra_pending.approver_id AS current_approver_id,
            ra_pending.approval_level AS current_approval_level,
            approver_emp.name AS current_approver_name,
            COALESCE(fin_verify.verification_confirmed, 0) AS finance_verification_confirmed,
            COALESCE(fin_verify.officer_approved, 0) AS finance_officer_approved,
            fin_verify.selected_company_ids AS finance_selected_company_ids,
            fin_verify.confirmed_at AS finance_verification_confirmed_at,
            fin_verify.finance_officer_emp_id AS finance_verification_officer,
            COALESCE(hr_checks.checked_employees, 0) AS hr_checked_count
        FROM (
            SELECT
                p.month_year,
                COUNT(p.emp_id) AS employee_count,
                SUM(p.net_salary) AS total_net_salary,
                SUM(CASE WHEN COALESCE(e.payment_type, 1) = 1 THEN p.net_salary ELSE 0 END) AS bank_total_net_salary,
                COUNT(CASE WHEN COALESCE(e.payment_type, 1) <> 3 THEN p.emp_id END) AS checklist_employee_count,
                SUM(CASE WHEN COALESCE(e.payment_type, 1) <> 3 THEN p.net_salary ELSE 0 END) AS checklist_total_net_salary
            FROM payrolls p
            INNER JOIN employees e ON e.emp_id = p.emp_id
            GROUP BY p.month_year
        ) p_months
        $joins
        $whereSql
        GROUP BY p_months.month_year
        ORDER BY p_months.month_year DESC";

    $mainParams = $params;
    $mainTypes = $types;

    if (!$showAll) {
        $offset = ($currentPage - 1) * $itemsPerPage;
        $mainSql .= ' LIMIT ?, ?';
        $mainParams[] = $offset;
        $mainParams[] = $itemsPerPage;
        $mainTypes .= 'ii';
    }

    $stmt = $conDB->prepare($mainSql);
    if (!$stmt) {
        die('Main query prepare failed: ' . $conDB->error);
    }
    if (!empty($mainParams)) {
        $stmt->bind_param($mainTypes, ...$mainParams);
    }

    if (!$stmt->execute()) {
        die('Main query execute failed: ' . $stmt->error);
    }

    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $requests[] = $row;
    }
    $stmt->close();
}

$unfilteredTotalItems = 0;
$unfilteredSql = 'SELECT COUNT(DISTINCT month_year) AS total FROM payrolls';
$unfilteredRes = mysqli_query($conDB, $unfilteredSql);
if ($unfilteredRes && ($tmp = mysqli_fetch_assoc($unfilteredRes))) {
    $unfilteredTotalItems = (int)$tmp['total'];
}
$isHrPayrollUser = strtolower(trim((string)($user_type ?? ''))) === 'hr_payroll';
$isHrSeniorBpUser = strtolower(trim((string)($user_type ?? ''))) === 'hr_senior_bp';
$isHeadOfficeFinanceManager = strtolower(trim((string)($user_type ?? ''))) === 'finance'
    && strtolower(trim((string)($emp_type ?? ''))) === 'manager'
    && (int)($user_dept ?? 0) === 2;

$normalizedUserType = str_replace([' ', '-'], '_', strtolower(trim((string)($user_type ?? ''))));
$isHrManagerUser = in_array($normalizedUserType, ['hr_manager', 'hrmanager', 'hr'], true);
$isGeneralManagerUser = in_array($normalizedUserType, ['general_manager', 'generalmanager', 'gm'], true);
$approvalModalUseWideLayout = $isHrManagerUser || $isGeneralManagerUser;

function getPayrollModifiedEmployeesForApprovalGate(PDO $pdo, string $requestInvNo, string $monthValue): array
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

    $columnCheckStmt = $pdo->prepare("SELECT COUNT(*)
        FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = :table_name
          AND COLUMN_NAME = :column_name");

    $tableHasColumn = static function (string $tableName, string $columnName) use ($columnCheckStmt): bool {
        $columnCheckStmt->execute([
            ':table_name' => $tableName,
            ':column_name' => $columnName
        ]);
        return ((int)$columnCheckStmt->fetchColumn() > 0);
    };

    $benefitHasUpdatedAt = $tableHasColumn('payroll_benefits', 'updated_at');
    $benefitHasCreatedAt = $tableHasColumn('payroll_benefits', 'created_at');
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

    $deductionHasUpdatedAt = $tableHasColumn('payroll_deductions', 'updated_at');
    $deductionHasCreatedAt = $tableHasColumn('payroll_deductions', 'created_at');
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

function getFinanceVerifierChecklistCompletion(PDO $pdo, string $requestInvNo, string $monthValue, string $verifierEmpId, bool $isManagerVerifier = false): array
{
    if ($requestInvNo === '' || $monthValue === '' || $verifierEmpId === '') {
        return [
            'total_employees' => 0,
            'checked_employees' => 0,
            'remaining_employees' => 0,
            'is_completed' => false
        ];
    }

    // A manager can have several officers' rows under them - "manager verifier" must mean
    // the manager's OWN self-assigned row (finance_officer_emp_id = the manager themself),
    // not just any row where they happen to be the manager on record.
    $scopeStmt = $pdo->prepare("SELECT selected_company_ids
        FROM payroll_finance_verification
        WHERE request_inv_no = :inv_no
          AND payroll_month = :month_year
          AND finance_officer_emp_id = :verifier_id"
          . ($isManagerVerifier ? ' AND finance_manager_emp_id = :verifier_id_mgr' : '') . "
          AND is_confirmed = 1
        ORDER BY id DESC
        LIMIT 1");
    $scopeExecParams = [
        ':inv_no' => $requestInvNo,
        ':month_year' => $monthValue,
        ':verifier_id' => $verifierEmpId
    ];
    if ($isManagerVerifier) {
        $scopeExecParams[':verifier_id_mgr'] = $verifierEmpId;
    }
    $scopeStmt->execute($scopeExecParams);
    $scopeRaw = $scopeStmt->fetchColumn();
    $scopeCompanies = json_decode((string)($scopeRaw ?: '[]'), true);
    if (!is_array($scopeCompanies)) {
        $scopeCompanies = [];
    }

    $scopeCompanies = array_values(array_unique(array_filter(array_map(static function ($value) {
        return trim((string)$value);
    }, $scopeCompanies), static function ($value) {
        return $value !== '';
    })));

    if (empty($scopeCompanies)) {
        // A manager who fully delegated (never self-assigned any companies) has nothing
        // personally to verify - that's not "incomplete", it's not applicable to them.
        return [
            'total_employees' => 0,
            'checked_employees' => 0,
            'remaining_employees' => 0,
            'is_completed' => $isManagerVerifier
        ];
    }

    $scopeParams = [':month_year' => $monthValue];
    $scopePlaceholders = [];
    foreach ($scopeCompanies as $index => $companyId) {
        $paramKey = ':scope_company_' . $index;
        $scopePlaceholders[] = $paramKey;
        $scopeParams[$paramKey] = $companyId;
    }

    $employeesStmt = $pdo->prepare("SELECT DISTINCT p.emp_id
        FROM payrolls p
        INNER JOIN employees e ON e.emp_id = p.emp_id
        WHERE p.month_year = :month_year
          AND CAST(e.comp_no AS CHAR) IN (" . implode(', ', $scopePlaceholders) . ")");
    $employeesStmt->execute($scopeParams);
    $scopeEmpIds = array_values(array_filter(array_map('trim', array_map('strval', $employeesStmt->fetchAll(PDO::FETCH_COLUMN))), static function ($empId) {
        return $empId !== '';
    }));

    $totalEmployees = count($scopeEmpIds);
    if ($totalEmployees <= 0) {
        return [
            'total_employees' => 0,
            'checked_employees' => 0,
            'remaining_employees' => 0,
            'is_completed' => true
        ];
    }

    $modifiedByEmp = getPayrollModifiedEmployeesForApprovalGate($pdo, $requestInvNo, $monthValue);
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
            'remaining_employees' => 0,
            'is_completed' => true
        ];
    }

    $checkedParams = [
        ':request_inv_no' => $requestInvNo,
        ':payroll_month' => $monthValue,
        ':approver_id' => $verifierEmpId,
    ];
    $requiredPlaceholders = [];
    foreach ($requiredEmpIds as $idx => $empId) {
        $key = ':req_emp_' . $idx;
        $requiredPlaceholders[] = $key;
        $checkedParams[$key] = $empId;
    }

    $checkedStmt = $pdo->prepare("SELECT COUNT(DISTINCT pec.emp_id)
        FROM payroll_checklist_employee_checks pec
        WHERE pec.request_inv_no = :request_inv_no
          AND pec.payroll_month = :payroll_month
          AND pec.approver_id = :approver_id
          AND pec.is_checked = 1
          AND pec.emp_id IN (" . implode(', ', $requiredPlaceholders) . ")");
    $checkedStmt->execute($checkedParams);
    $checkedRequiredEmployees = (int)$checkedStmt->fetchColumn();

    $checkedEmployees = max(0, ($totalEmployees - $requiredCount) + $checkedRequiredEmployees);
    $remainingEmployees = max(0, $totalEmployees - $checkedEmployees);

    return [
        'total_employees' => $totalEmployees,
        'checked_employees' => $checkedEmployees,
        'remaining_employees' => $remainingEmployees,
        'is_completed' => $remainingEmployees === 0
    ];
}

// Sums checklist completion across EVERY officer assigned to this request (a manager can
// have several concurrent officers, each with their own row/scope) - not just one of them.
function getFinanceVerificationAggregateCompletion(PDO $pdo, string $requestInvNo, string $monthValue): array
{
    if ($requestInvNo === '' || $monthValue === '') {
        return ['total_employees' => 0, 'checked_employees' => 0, 'remaining_employees' => 0, 'is_completed' => false];
    }

    $officersStmt = $pdo->prepare("SELECT DISTINCT finance_officer_emp_id
        FROM payroll_finance_verification
        WHERE request_inv_no = :inv_no AND payroll_month = :month_year AND is_confirmed = 1");
    $officersStmt->execute([':inv_no' => $requestInvNo, ':month_year' => $monthValue]);
    $officerIds = array_values(array_filter(array_map('trim', array_map('strval', $officersStmt->fetchAll(PDO::FETCH_COLUMN))), static function ($value) {
        return $value !== '';
    }));

    if (empty($officerIds)) {
        return ['total_employees' => 0, 'checked_employees' => 0, 'remaining_employees' => 0, 'is_completed' => false];
    }

    $totalEmployees = 0;
    $checkedEmployees = 0;
    foreach ($officerIds as $officerId) {
        $completion = getFinanceVerifierChecklistCompletion($pdo, $requestInvNo, $monthValue, $officerId, false);
        $totalEmployees += (int)$completion['total_employees'];
        $checkedEmployees += (int)$completion['checked_employees'];
    }

    $remaining = max(0, $totalEmployees - $checkedEmployees);
    return [
        'total_employees' => $totalEmployees,
        'checked_employees' => $checkedEmployees,
        'remaining_employees' => $remaining,
        'is_completed' => $totalEmployees > 0 && $remaining === 0
    ];
}

// Union of companies assigned to ANY officer on this request - not just one representative
// row - used for accurate "Assigned Companies: X / Y" display and setup-modal state.
function getAllAssignedFinanceCompanyIdsForRequest(PDO $pdo, string $requestInvNo, string $monthValue): array
{
    if ($requestInvNo === '' || $monthValue === '') {
        return [];
    }

    $stmt = $pdo->prepare("SELECT selected_company_ids
        FROM payroll_finance_verification
        WHERE request_inv_no = :inv_no AND payroll_month = :month_year AND is_confirmed = 1");
    $stmt->execute([':inv_no' => $requestInvNo, ':month_year' => $monthValue]);

    $companyIds = [];
    foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $rowCompanyIdsJson) {
        $decoded = json_decode((string)$rowCompanyIdsJson, true);
        if (is_array($decoded)) {
            $companyIds = array_merge($companyIds, $decoded);
        }
    }

    return array_values(array_unique(array_filter(array_map(static function ($value) {
        return trim((string)$value);
    }, $companyIds), static function ($value) {
        return $value !== '';
    })));
}

function getPayrollMonthCompanyIds(PDO $pdo, string $monthValue): array
{
    static $cache = [];

    if ($monthValue === '') {
        return [];
    }

    if (isset($cache[$monthValue])) {
        return $cache[$monthValue];
    }

    $stmt = $pdo->prepare("SELECT DISTINCT CAST(e.comp_no AS CHAR)
        FROM payrolls p
        INNER JOIN employees e ON e.emp_id = p.emp_id
        WHERE p.month_year = :month_year");
    $stmt->execute([':month_year' => $monthValue]);

    $companyIds = array_values(array_unique(array_filter(array_map(static function ($value) {
        return trim((string)$value);
    }, $stmt->fetchAll(PDO::FETCH_COLUMN)), static function ($value) {
        return $value !== '';
    })));

    $cache[$monthValue] = $companyIds;
    return $companyIds;
}

function mapPayrollBankBucketFromName(string $name): string
{
    if ($name === '') {
        return '';
    }

    // SNB / NCB / Al Ahli / Saudi National Bank
    if (
        strpos($name, 'national commercial') !== false ||
        strpos($name, 'ncb') !== false ||
        strpos($name, 'alahli') !== false ||
        strpos($name, 'al ahli') !== false ||
        strpos($name, 'al-ahli') !== false ||
        strpos($name, 'alahly') !== false ||
        strpos($name, 'snb') !== false ||
        strpos($name, 'saudi national') !== false ||
        strpos($name, 'ahli bank') !== false
    ) {
        return 'ncb';
    }

    // Riyadh Bank (official English is "Riyad Bank" without h)
    if (
        strpos($name, 'riyadh') !== false ||
        strpos($name, 'riyad') !== false ||
        $name === 'rb' ||
        $name === 'riyad bank' ||
        $name === 'bank riyad'
    ) {
        return 'riyadh_bank';
    }

    // Saudi Investment Bank / SAIB
    if (
        strpos($name, 'saudi investment') !== false ||
        strpos($name, 'saib') !== false ||
        $name === 'sib'
    ) {
        return 'saudi_investment_bank';
    }

    // Al Rajhi Bank
    if (
        strpos($name, 'alrajhi') !== false ||
        strpos($name, 'al rajhi') !== false ||
        strpos($name, 'al-rajhi') !== false ||
        strpos($name, 'rajhi') !== false ||
        $name === 'arb'
    ) {
        return 'al_rajhi_bank';
    }

    // Alinma Bank
    if (
        strpos($name, 'alinma') !== false ||
        strpos($name, 'al inma') !== false ||
        strpos($name, 'al-inma') !== false ||
        strpos($name, 'inma') !== false
    ) {
        return 'alinma_bank';
    }

    // Arab National Bank / ANB
    if (
        preg_match('/\banb\b/', $name) ||
        strpos($name, 'arab national') !== false ||
        strpos($name, 'arab nat') !== false
    ) {
        return 'anb_bank';
    }

    // Bank AlJazira / BAJ
    if (
        strpos($name, 'jazira') !== false ||
        strpos($name, 'al jazira') !== false ||
        strpos($name, 'aljazira') !== false ||
        $name === 'baj'
    ) {
        return 'al_jazira';
    }

    return '';
}

function mapPayrollBankBucket(string $bankNameShort, string $bankNameFull = ''): string
{
    // Try short name first, then full name
    $shortNorm = strtolower(trim($bankNameShort));
    $fullNorm  = strtolower(trim($bankNameFull));

    $bucket = mapPayrollBankBucketFromName($shortNorm);
    if ($bucket !== '') {
        return $bucket;
    }

    if ($fullNorm !== '') {
        $bucket = mapPayrollBankBucketFromName($fullNorm);
        if ($bucket !== '') {
            return $bucket;
        }
    }

    // Not recognizable as a specific bank — do NOT classify as cash
    return 'other_bank';
}

function getPayrollBankBucketLabels(): array
{
    return [
        'ncb' => 'NATIONAL COMMERCIAL BANK',
        'riyadh_bank' => 'RIYADH BANK',
        'saudi_investment_bank' => 'SAUDI INVESTMENT BANK',
        'al_rajhi_bank' => 'AL RAJHI BANK',
        'alinma_bank' => 'ALINMA BANK',
        'anb_bank' => 'ANB BANK',
        'al_jazira' => 'AL JAZIRA',
        'other_bank' => 'OTHER BANKS',
    ];
}

function getPayrollBankWiseCompanySummary(PDO $pdo, string $monthValue): array
{
    $summary = [
        'month' => $monthValue,
        'bank_columns' => [],
        'rows' => []
    ];

    if ($monthValue === '') {
        return $summary;
    }

    $stmt = $pdo->prepare("SELECT
            p.emp_id,
            p.net_salary,
            COALESCE(e.payment_type, 1) AS payment_type,
            COALESCE(c.comp_name, 'N/A') AS company_name,
            COALESCE(bl.bank_name_s, '') AS bank_name_short,
            COALESCE(bl.name, '') AS bank_name_full
        FROM payrolls p
        INNER JOIN employees e ON e.emp_id = p.emp_id
        LEFT JOIN companies c ON c.comp_id = e.comp_no
        LEFT JOIN bank_list bl ON bl.bnk_id = e.bank_name
        WHERE p.month_year = :month_year
        ORDER BY c.comp_name ASC, p.emp_id ASC");
    $stmt->execute([':month_year' => $monthValue]);

    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if (empty($rows)) {
        return $summary;
    }

    $companies = [];
    $companyEmployeeMap = [];
    $activeBankColumns = [];
    $allBankColumns = array_keys(getPayrollBankBucketLabels());

    foreach ($rows as $row) {
        $companyName = trim((string)($row['company_name'] ?? ''));
        if ($companyName === '') {
            $companyName = 'N/A';
        }

        if (!isset($companies[$companyName])) {
            $companies[$companyName] = [
                'branch' => $companyName,
                'no_of_emp' => 0,
                'salary_register' => 0.0,
                'cash_salary' => 0.0,
            ];
            foreach ($allBankColumns as $bankColumn) {
                $companies[$companyName][$bankColumn] = 0.0;
            }
            $companyEmployeeMap[$companyName] = [];
        }

        $empId = trim((string)($row['emp_id'] ?? ''));
        if ($empId !== '') {
            $companyEmployeeMap[$companyName][$empId] = true;
        }

        $netSalary = (float)($row['net_salary'] ?? 0);
        $paymentType = (int)($row['payment_type'] ?? 1);

        $companies[$companyName]['salary_register'] += $netSalary;

        if ($paymentType === 1) {
            $bucket = mapPayrollBankBucket(
                (string)($row['bank_name_short'] ?? ''),
                (string)($row['bank_name_full'] ?? '')
            );
            $companies[$companyName][$bucket] += $netSalary;
            $activeBankColumns[$bucket] = true;
        } else {
            $companies[$companyName]['cash_salary'] += $netSalary;
        }
    }

    $summary['bank_columns'] = array_values(array_filter($allBankColumns, static function ($bankColumn) use ($activeBankColumns) {
        return !empty($activeBankColumns[$bankColumn]);
    }));

    $totals = [
        'branch' => 'TOTAL',
        'no_of_emp' => 0,
        'salary_register' => 0.0,
        'cash_salary' => 0.0,
    ];
    foreach ($allBankColumns as $bankColumn) {
        $totals[$bankColumn] = 0.0;
    }

    foreach ($companies as $companyName => &$companyRow) {
        $companyRow['no_of_emp'] = count($companyEmployeeMap[$companyName]);

        $totals['no_of_emp'] += (int)$companyRow['no_of_emp'];
        $totals['salary_register'] += (float)$companyRow['salary_register'];
        foreach ($summary['bank_columns'] as $bankColumn) {
            $totals[$bankColumn] += (float)$companyRow[$bankColumn];
        }
        $totals['cash_salary'] += (float)$companyRow['cash_salary'];
    }
    unset($companyRow);

    $summary['rows'] = array_values($companies);
    $summary['rows'][] = $totals;

    return $summary;
}

$approvalBankReportByRequest = [];
$approvalBankReportByMonth = [];
if (!empty($requests)) {
    foreach ($requests as $request) {
        $requestInvNo = trim((string)($request['request_inv_no'] ?? ''));
        $payrollMonth = trim((string)($request['payroll_month'] ?? ''));
        if ($requestInvNo === '' || $payrollMonth === '') {
            continue;
        }

        if (!isset($approvalBankReportByMonth[$payrollMonth])) {
            $approvalBankReportByMonth[$payrollMonth] = getPayrollBankWiseCompanySummary($pdo, $payrollMonth);
        }

        $approvalBankReportByRequest[$requestInvNo] = $approvalBankReportByMonth[$payrollMonth];
    }
}
?>
<!doctype html>
<html lang="<?= $current_lang ?? 'en' ?>" <?= ($is_rtl ?? false) ? 'dir="rtl"' : '' ?>>
<head>
    <meta charset="utf-8" />
    <title><?= $site_title ?? 'Payroll Approvals' ?> - <?= __('payroll_approvals', 'Payroll Approvals') ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta content="Al-Mutlak" name="author" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <link rel="shortcut icon" href="<?= get_setting($conDB, 'favicon') ?>">
    <link href="./plugins/custombox/css/custombox.min.css" rel="stylesheet">
    <link href="./plugins/select2/css/select2.min.css" rel="stylesheet" type="text/css" />
    <link href="./plugins/datatables/dataTables.bootstrap4.min.css" rel="stylesheet" type="text/css" />
    <link href="assets/css/bootstrap.min.css" rel="stylesheet" type="text/css" />
    <link href="assets/css/metismenu.min.css" rel="stylesheet" type="text/css" />
    <link href="assets/css/style.css" rel="stylesheet" type="text/css" />
    <link href="assets/css/style_dark.css" rel="stylesheet" type="text/css" />
    <script src="assets/js/modernizr.min.js"></script>
    <style>
        .filter-controls { max-width: 800px; }
        .request-card {
            border-radius: 15px;
            border: none;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.07);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        .request-card:hover { transform: translateY(-5px); box-shadow: 0 15px 40px rgba(0, 0, 0, 0.1); }
        .request-card .card-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: #fff;
            border-bottom: none;
            font-weight: 600;
            border-top-left-radius: 15px;
            border-top-right-radius: 15px;
        }
        .request-card .card-header .btn, .request-card .card-header .dropdown-toggle { color: #212529 !important; }
        .request-card .card-body { padding: 1.5rem; }
        .detail-item { display: flex; align-items: center; margin-bottom: 1rem; font-size: 1.03em; }
        .detail-item i { color: #4a90e2; margin-right: 15px; width: 20px; text-align: center; }
        .detail-item strong { color: #8a94a6; min-width: 140px; display: inline-block; }
        .detail-item { flex-direction: <?= ($is_rtl ?? false) ? 'row-reverse !important' : 'row !important' ?>; text-align: <?= ($is_rtl ?? false) ? 'right !important' : 'left !important' ?>; }
        .request-card .card-footer { background: linear-gradient(135deg, #eef1fc 0%, #f6f1fb 100%); border-top: 2px solid #a5b0e8; border-bottom-left-radius: 15px; border-bottom-right-radius: 15px; }
        .request-time-footer {
            display: flex; justify-content: space-between; align-items: center; gap: 8px;
            font-size: 0.78em; color: #6c757d; padding-bottom: 8px; margin-bottom: 10px;
            border-bottom: 1px dashed #e3e6f5;
        }
        .request-time-footer .rtf-ago, .request-time-footer .rtf-exact { display: flex; align-items: center; gap: 5px; white-space: nowrap; }
        .request-time-footer .rtf-ago { font-weight: 600; color: #495057; }
        .request-time-footer .rtf-exact { font-variant-numeric: tabular-nums; opacity: 0.85; }
        .request-time-footer i { color: #a0a8c0; }
        .no-requests { padding: 3rem; background: #fff; border-radius: 15px; box-shadow: 0 10px 30px rgba(0, 0, 0, 0.07); }
        .swal-payroll-details {
            text-align: left;
            border: 1px solid #e9ecef;
            border-radius: 12px;
            background: #f8fafc;
            padding: 14px;
            margin-bottom: 14px;
        }
        .swal-details-header {
            font-weight: 700;
            color: #334155;
            margin-bottom: 10px;
        }
        .swal-details-body {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 8px 16px;
        }
        .swal-detail-item {
            display: flex;
            justify-content: space-between;
            gap: 10px;
            padding: 8px 10px;
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            font-size: 14px;
        }
        .swal-detail-label {
            font-weight: 700;
            color: #64748b;
        }
        .swal-detail-value {
            font-weight: 600;
            color: #0f172a;
            text-align: right;
        }
        @media (max-width: 768px) {
            .swal-details-body {
                grid-template-columns: 1fr;
            }
        }
    </style>
    <?php if ($is_rtl): ?>
        <link href="assets/css/style_rtl.css" rel="stylesheet" type="text/css" />
    <?php endif; ?>
    <script>window.lang = <?= json_encode($GLOBALS['translations'] ?? []) ?>;</script>
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
            <?php include './includes/main_menu.php'; ?>
            <div class="clearfix"></div>
        </div>
    </div>

    <div class="content-page">
        <?php include './includes/topbar.php'; ?>
        <div class="content">
            <div class="container-fluid">
                <div class="row">
                    <div class="col-xl-12">
                        <div class="card-box">
                            <h4 class="header-title m-t-0 m-b-30"><?= __('payroll_approvals', 'Payroll Approvals') ?></h4>

                            <div class="row filter-controls mx-auto mb-5">
                                <div class="col-md-6 mb-3 mb-md-0">
                                    <div class="form-group">
                                        <label for="statusFilter" class="font-weight-bold"><?= __('filter_by_status') ?></label>
                                        <select class="form-control" id="statusFilter" onchange="applyFilters()">
                                            <?php foreach ($allStatuses as $statusKey => $statusValue): ?>
                                                <option value="<?= $statusKey ?>" <?= ($currentFilter === $statusKey) ? 'selected' : '' ?>>
                                                    <?= htmlspecialchars($statusValue) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="searchFilter" class="font-weight-bold"><?= __('search_by_name_id') ?></label>
                                        <div class="input-group">
                                            <input type="search" class="form-control" id="searchFilter" placeholder="<?= __('enter_search_term') ?>" value="<?= htmlspecialchars($searchTerm) ?>">
                                            <div class="input-group-append">
                                                <button class="btn btn-primary" type="button" onclick="applyFilters()"><i class="fas fa-search"></i></button>
                                            </div>
                                            <?php if (!empty($searchTerm) || $currentFilter !== 'my_pending'): ?>
                                                <div class="input-group-append">
                                                    <button class="btn btn-danger" type="button" onclick="resetFilters(<?= $perpage ?>)"><i class="fas fa-times"></i></button>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="d-flex justify-content-between align-items-center mb-4">
                                <h4 class="mb-0 text-muted"><?= str_replace('{0}', (string)$totalItems, __('showing_requests')) ?></h4>
                                <span class="badge badge-light p-2"><?= __('total_found') ?>: <?= $totalItems ?></span>
                            </div>

                            <?php if (!empty($requests)): ?>
                                <div class="row">
                                    <?php foreach ($requests as $request): ?>
                                        <?php
                                        $approvalStatus = $request['approval_status'] ?? null;
                                        $isPendingWithMe = ($approvalStatus === 'pending_approval' && !empty($request['current_approver_id']) && (string)$request['current_approver_id'] === (string)$empid);
                                        // Union across EVERY officer assigned to this request, not just the one
                                        // representative row picked up by the fin_verify JOIN used for the main list query.
                                        $assignedCompanyIds = !empty($request['request_inv_no'])
                                            ? getAllAssignedFinanceCompanyIdsForRequest($pdo, (string)$request['request_inv_no'], (string)$request['payroll_month'])
                                            : [];
                                        $financeVerificationConfirmed = !empty($assignedCompanyIds);
                                        $financeOfficerApproved = !empty($request['finance_officer_approved']);
                                        $monthCompanyIds = getPayrollMonthCompanyIds($pdo, (string)($request['payroll_month'] ?? ''));
                                        $unassignedCompanyIds = array_values(array_diff($monthCompanyIds, $assignedCompanyIds));
                                        $allCompaniesAssigned = !empty($monthCompanyIds) && empty($unassignedCompanyIds);
                                        $assignedCompaniesCount = count($assignedCompanyIds);
                                        $monthCompaniesCount = count($monthCompanyIds);
                                        $financeOfficerChecklistCompletion = [
                                            'total_employees' => 0,
                                            'checked_employees' => 0,
                                            'remaining_employees' => 0,
                                            'is_completed' => false
                                        ];
                                        if ($financeVerificationConfirmed && !empty($request['request_inv_no'])) {
                                            $financeOfficerChecklistCompletion = getFinanceVerificationAggregateCompletion(
                                                $pdo,
                                                (string)$request['request_inv_no'],
                                                (string)$request['payroll_month']
                                            );
                                        }
                                        $financeOfficerChecklistCompleted = $financeVerificationConfirmed
                                            && !empty($financeOfficerChecklistCompletion['is_completed']);
                                        $isHeadOfficeFinancePending = $isHeadOfficeFinanceManager && $isPendingWithMe;
                                        $financeManagerChecklistCompletion = [
                                            'total_employees' => 0,
                                            'checked_employees' => 0,
                                            'remaining_employees' => 0,
                                            'is_completed' => false
                                        ];
                                        if ($isHeadOfficeFinancePending && $financeVerificationConfirmed && !empty($request['request_inv_no'])) {
                                            $financeManagerChecklistCompletion = getFinanceVerifierChecklistCompletion(
                                                $pdo,
                                                (string)$request['request_inv_no'],
                                                (string)$request['payroll_month'],
                                                (string)$empid,
                                                true
                                            );
                                        }
                                        $financeManagerChecklistCompleted = $isHeadOfficeFinancePending
                                            ? !empty($financeManagerChecklistCompletion['is_completed'])
                                            : true;
                                        
                                        // Check if ALL companies in this payroll month have been approved by their respective officers
                                        $allAssignedCompaniesApproved = true;
                                        if ($isHeadOfficeFinancePending && !empty($monthCompanyIds) && !empty($request['request_inv_no'])) {
                                            $companyApprovalsStmt = $pdo->prepare("SELECT DISTINCT selected_company_ids
                                                FROM payroll_finance_verification
                                                WHERE request_inv_no = :inv_no
                                                  AND payroll_month = :month_year
                                                  AND officer_approved = 1
                                                  AND is_confirmed = 1");
                                            $companyApprovalsStmt->execute([
                                                ':inv_no' => $request['request_inv_no'],
                                                ':month_year' => $request['payroll_month']
                                            ]);
                                            
                                            $approvedCompanyRecords = $companyApprovalsStmt->fetchAll(PDO::FETCH_COLUMN);
                                            $approvedCompanies = [];
                                            foreach ($approvedCompanyRecords as $companiesJson) {
                                                $decoded = json_decode((string)$companiesJson, true);
                                                if (is_array($decoded)) {
                                                    $approvedCompanies = array_merge($approvedCompanies, $decoded);
                                                }
                                            }
                                            $approvedCompanies = array_unique(array_filter(array_map(static function ($v) {
                                                return trim((string)$v);
                                            }, $approvedCompanies)));
                                            
                                            // Check if ALL companies in the month are approved
                                            foreach ($monthCompanyIds as $companyId) {
                                                if (!in_array($companyId, $approvedCompanies, true)) {
                                                    $allAssignedCompaniesApproved = false;
                                                    break;
                                                }
                                            }
                                        }
                                        
                                        // Gate purely on officer_approved (allAssignedCompaniesApproved already requires
                                        // EVERY assigned officer's row to have it, not just one) - officer_approved can only
                                        // be granted after that officer checked every employee in their scope (enforced
                                        // server-side in confirmFinanceOfficerVerification), so it's already sufficient proof.
                                        // $financeOfficerChecklistCompleted / $financeManagerChecklistCompleted stay as
                                        // display-only stats - re-deriving completion independently here would incorrectly
                                        // re-block approvals granted before that server-side check existed.
                                        $financeOfficerFinalApproved = $allCompaniesAssigned && $allAssignedCompaniesApproved;
                                        $canApproveNow = $isPendingWithMe && (!$isHeadOfficeFinancePending || $financeOfficerFinalApproved);
                                        $showFinanceVerificationSetupAction = $isHeadOfficeFinancePending && !$allCompaniesAssigned;
                                        $hrCheckedCount = (int)($request['hr_checked_count'] ?? 0);
                                        $monthEmployeeCount = (int)($request['checklist_employee_count'] ?? $request['employee_count'] ?? 0);
                                        $canSendCompanyPayrollReport = ($isHrPayrollUser || $isHrSeniorBpUser)
                                            && !empty($request['request_inv_no'])
                                            && $approvalStatus === 'pending_approval'
                                            && $monthEmployeeCount > 0
                                            && $hrCheckedCount < $monthEmployeeCount;
                                        $statusClass = 'secondary';
                                        $statusText = $approvalStatus ? __($approvalStatus) : __('no_approval_request', 'No Approval Request');
                                        $statusIcon = '';
                                        if ($approvalStatus === null) {
                                            $statusClass = 'dark';
                                            $statusText = __('no_approval_request', 'No Approval Request');
                                            $statusIcon = "<i class='fa fa-exclamation text-white'></i>";
                                        } elseif ($approvalStatus === 'pending_approval') {
                                            $statusClass = 'warning';
                                            $nextApprover = !empty($request['current_approver_name']) ? getDisplayName(parseName($request['current_approver_name'])) : __('next_approver');
                                            $statusText = __('pending_with') . ' ' . htmlspecialchars($nextApprover);
                                            $statusIcon = "<i class='fa fa-hourglass-half text-white'></i>";
                                        } elseif ($approvalStatus === 'approved') {
                                            $statusClass = 'success';
                                            $statusText = __('approved');
                                            $statusIcon = "<i class='fa fa-check text-white'></i>";
                                        } elseif ($approvalStatus === 'rejected') {
                                            $statusClass = 'danger';
                                            $statusText = __('rejected');
                                            $statusIcon = "<i class='fa fa-times text-white'></i>";
                                        } elseif ($approvalStatus === 'completed') {
                                            $statusClass = 'primary';
                                            $statusText = __('completed');
                                            $statusIcon = "<i class='fa fa-badge-check text-white'></i>";
                                        }
                                        ?>
                                        <div class="col-lg-4 col-md-6 mb-4">
                                            <div class="card request-card h-100">
                                                <div class="card-header d-flex justify-content-between align-items-center">
                                                    <span><?= __('payroll_month') ?>: <?= htmlspecialchars($request['payroll_month']) ?></span>
                                                    <div class="d-flex align-items-center card-header-actions" style="gap: 8px;">
                                                        <?php if (!empty($request['request_inv_no'])): ?>
                                                        <span><?= __('request_id') ?>: <?= htmlspecialchars($request['request_inv_no']) ?></span>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                                <div class="card-body">
                                                        <div class="detail-item"><i class="fad fa-calendar"></i><strong><?= __('month') ?>:</strong> <?= htmlspecialchars($request['payroll_month']) ?></div>
                                                        <div class="detail-item"><i class="fad fa-users"></i><strong><?= __('employees', 'Employees') ?>:</strong> <?= (int)($request['checklist_employee_count'] ?? $request['employee_count'] ?? 0) ?></div>
                                                        <div class="detail-item"><i class="fad fa-money-bill"></i><strong><?= __('total_net', 'Total Net') ?>:</strong> <?= number_format((float)($request['checklist_total_net_salary'] ?? $request['total_net_salary'] ?? 0), 2) ?> SAR</div>
                                                        <?php if ($isHrPayrollUser): ?>
                                                        <div class="detail-item"><i class="fad fa-user-check"></i><strong><?= __('checked_by_me', 'Checked By Me') ?>:</strong> <?= $hrCheckedCount ?> / <?= $monthEmployeeCount ?></div>
                                                        <?php endif; ?>
                                                        <?php if ($isHeadOfficeFinancePending && $financeVerificationConfirmed && !$financeOfficerChecklistCompleted): ?>
                                                        <div class="detail-item"><i class="fad fa-user-clock"></i><strong><?= __('finance_verification_status', 'Finance Verification') ?>:</strong> <?= (int)($financeOfficerChecklistCompletion['checked_employees'] ?? 0) ?> / <?= (int)($financeOfficerChecklistCompletion['total_employees'] ?? 0) ?></div>
                                                        <?php endif; ?>
                                                        <?php if ($isHeadOfficeFinancePending && $monthCompaniesCount > 0): ?>
                                                        <div class="detail-item"><i class="fad fa-building"></i><strong><?= __('assigned_companies', 'Assigned Companies') ?>:</strong> <?= $assignedCompaniesCount ?> / <?= $monthCompaniesCount ?></div>
                                                        <?php endif; ?>
                                                        <?php if ($isHeadOfficeFinancePending && $financeOfficerChecklistCompleted && !$financeOfficerApproved): ?>
                                                        <div class="detail-item"><i class="fad fa-hourglass-half"></i><strong><?= __('finance_officer_approval', 'Finance Officer Approval') ?>:</strong> <?= __('pending', 'Pending') ?></div>
                                                        <?php endif; ?>
                                                        <?php if ($isHeadOfficeFinancePending && $financeVerificationConfirmed && !$financeManagerChecklistCompleted): ?>
                                                        <div class="detail-item"><i class="fad fa-list-check"></i><strong><?= __('manager_verification_status', 'Manager Verification') ?>:</strong> <?= (int)($financeManagerChecklistCompletion['checked_employees'] ?? 0) ?> / <?= (int)($financeManagerChecklistCompletion['total_employees'] ?? 0) ?></div>
                                                        <?php endif; ?>
                                                        <?php if (!empty($request['requester_name'])): ?>
                                                        <div class="detail-item"><i class="fad fa-user"></i><strong><?= __('requested_by', 'Requested By') ?>:</strong> <?= htmlspecialchars(getDisplayName($request['requester_name'])) ?></div>
                                                        <?php endif; ?>
                                                        <?php if (!empty($request['approval_created_at'])): ?>
                                                        <div class="detail-item"><i class="fad fa-clock"></i><strong><?= __('submitted', 'Submitted') ?>:</strong> <?= htmlspecialchars(format_safe_date($request['approval_created_at'] ?? null, 'd M Y')) ?></div>
                                                        <?php endif; ?>
                                                        <div class="detail-item">
                                                            <i class="fad fa-tasks"></i>
                                                            <strong><?= __('status') ?>:</strong>
                                                            <span class="badge badge-<?= $statusClass ?> p-2"><?= $statusIcon . ' ' . $statusText ?></span>
                                                        </div>
                                                </div>
                                                <div class="card-footer">
                                                    <?php if (!empty($request['approval_created_at'])): ?>
                                                    <div class="request-time-footer">
                                                        <span class="rtf-ago"><i class="fa fa-history"></i> <?= htmlspecialchars(($current_lang ?? 'en') === 'ar' ? timeAgoAr($request['approval_created_at']) : timeAgo($request['approval_created_at'])) ?></span>
                                                        <span class="rtf-exact"><i class="fa fa-calendar-alt"></i> <?= htmlspecialchars(format_safe_date($request['approval_created_at'], 'Y-m-d H:i:s')) ?></span>
                                                    </div>
                                                    <?php endif; ?>
                                                    <div class="btn-group flex-fill" style="display:flex;">
                                                        <button type="button" class="btn btn-secondary dropdown-toggle btn-block waves-effect" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                                            <?= __('actions') ?> <span class="caret"></span>
                                                        </button>
                                                        <div class="dropdown-menu dropdown-menu-right">
                                                            <a class="dropdown-item" href="payroll_checklist_report.php?month=<?= urlencode($request['payroll_month']) ?><?php if (!empty($request['request_inv_no'])): ?>&request_inv_no=<?= urlencode($request['request_inv_no']) ?><?php endif; ?>" target="_blank">
                                                                <i class="fa fa-clipboard-check"></i> <?= __('payroll_checklist_report', 'Payroll Check List') ?>
                                                            </a>
                                                            <div class="dropdown-divider"></div>
                                                            <?php if (!empty($request['request_inv_no'])): ?>
                                                                <a class="dropdown-item" href="payroll_status_history.php?inv_no=<?= urlencode($request['request_inv_no']) ?>" target="_blank">
                                                                    <i class="fa fa-history"></i> <?= __('history') ?>
                                                                </a>
                                                            <?php else: ?>
                                                                <a class="dropdown-item" href="generate_payroll.php">
                                                                    <i class="fa fa-paper-plane text-warning"></i> <?= __('start_approval', 'Start Approval') ?>
                                                                </a>
                                                            <?php endif; ?>
                                                            <?php if ($showFinanceVerificationSetupAction): ?>
                                                                <div class="dropdown-divider"></div>
                                                                <a class="dropdown-item" href="javascript:void(0);" onclick="openFinanceVerificationSetupModal('<?= htmlspecialchars($request['request_inv_no'], ENT_QUOTES) ?>', '<?= htmlspecialchars($request['payroll_month'], ENT_QUOTES) ?>')">
                                                                    <i class="fa fa-tasks text-primary"></i> <?= __('finance_verification_setup', 'Finance Verification Setup') ?> (<?= $assignedCompaniesCount ?> / <?= $monthCompaniesCount ?>)
                                                                </a>
                                                            <?php endif; ?>
                                                            <?php if ($canApproveNow): ?>
                                                                <div class="dropdown-divider"></div>
                                                                <a class="dropdown-item" href="javascript:void(0);" onclick="approvePayrollRequest('<?= htmlspecialchars($request['request_inv_no'], ENT_QUOTES) ?>', '<?= htmlspecialchars($request['payroll_month'], ENT_QUOTES) ?>', <?= (int)($request['checklist_employee_count'] ?? $request['employee_count'] ?? 0) ?>, <?= (float)($request['checklist_total_net_salary'] ?? $request['total_net_salary'] ?? 0) ?>, '<?= htmlspecialchars(getDisplayName(parseName($request['requester_name'] ?? '')), ENT_QUOTES) ?>', <?= (float)($request['bank_total_net_salary'] ?? 0) ?>)">
                                                                    <i class="fa fa-check text-success"></i> <?= __('approve') ?>
                                                                </a>
                                                                <a class="dropdown-item" href="javascript:void(0);" onclick="rejectPayrollRequest('<?= htmlspecialchars($request['request_inv_no'], ENT_QUOTES) ?>', '<?= htmlspecialchars($request['payroll_month'], ENT_QUOTES) ?>', <?= (int)($request['checklist_employee_count'] ?? $request['employee_count'] ?? 0) ?>, <?= (float)($request['checklist_total_net_salary'] ?? $request['total_net_salary'] ?? 0) ?>, '<?= htmlspecialchars(getDisplayName($request['requester_name'] ?? ''), ENT_QUOTES) ?>', <?= (float)($request['bank_total_net_salary'] ?? 0) ?>)">
                                                                    <i class="fa fa-times text-danger"></i> <?= __('reject') ?>
                                                                </a>
                                                            <?php endif; ?>
                                                            <?php if ($canSendCompanyPayrollReport): ?>
                                                                <div class="dropdown-divider"></div>
                                                                <a class="dropdown-item" href="javascript:void(0);" onclick="openCompanyPayrollReportModal('<?= htmlspecialchars($request['request_inv_no'], ENT_QUOTES) ?>', '<?= htmlspecialchars($request['payroll_month'], ENT_QUOTES) ?>')">
                                                                    <i class="fa fa-envelope-open-text text-primary"></i> <?= __('send_supervisor_payroll_report', 'Send Payroll Report by Direct Supervisor') ?>
                                                                </a>
                                                            <?php endif; ?>
                                                            
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <div class="no-requests">
                                    <h5><?= __('no_records_found') ?></h5>
                                    <p><?= __('no_data_available_in_table') ?></p>
                                </div>
                            <?php endif; ?>

                            <div class="row mt-4">
                                <div class="col-xl-12">
                                    <?= generate_pagination_controls($currentPage, $totalPages, $totalItems, $itemsPerPage, $limitOptions, $showAll, ['status' => $currentFilter, 'search' => $searchTerm], $unfilteredTotalItems) ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <footer class="footer"><?= $site_footer ?? '© 2026 Almutlak' ?></footer>
    </div>
</div>

<script src="assets/js/jquery.min.js"></script>
<script src="assets/js/jquery.slimscroll.js"></script>
<script src="assets/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/metisMenu.min.js"></script>
<script src="assets/js/waves.js"></script>
<script src="./plugins/select2/js/select2.min.js" type="text/javascript"></script>
<script src="./plugins/datatables/jquery.dataTables.min.js"></script>
<script src="./plugins/datatables/dataTables.bootstrap4.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="assets/js/jquery.core.js"></script>
<script src="assets/js/jquery.app.js?t=<?= time() ?>"></script>
<script>
// Move each card's "Actions" dropdown from the footer into the header so the
// menu has room to open downward instead of getting clipped at the bottom of
// the page (was especially bad for the last row of cards on a page).
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.request-card').forEach(function(card) {
        const footer = card.querySelector('.card-footer');
        const actionsGroup = footer ? footer.querySelector('.btn-group') : null;
        const headerSlot = card.querySelector('.card-header-actions');
        if (!actionsGroup || !headerSlot) {
            return;
        }
        actionsGroup.classList.remove('flex-fill');
        const toggleBtn = actionsGroup.querySelector('.dropdown-toggle');
        if (toggleBtn) {
            toggleBtn.classList.remove('btn-block', 'btn-secondary');
            toggleBtn.classList.add('btn-sm', 'btn-light');
        }
        headerSlot.appendChild(actionsGroup);
    });
});

const payrollApprovalBankReports = <?= json_encode($approvalBankReportByRequest, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
const payrollApprovalUseWideModal = <?= $approvalModalUseWideLayout ? 'true' : 'false' ?>;
const payrollApprovalCanViewBankReport = payrollApprovalUseWideModal;

function buildPayrollRequestDetailsHtml(requestInvNo, payrollMonth, employeeCount, totalNet, requesterName, bankTotalNet) {
    const safeRequesterName = requesterName || '<?= __('not_available', 'N/A') ?>';
    const bankWpsPaymentValue = Number(bankTotalNet || 0);
    const formattedTotalNet = Number(totalNet || 0).toLocaleString('en-US', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    });
    const formattedBankWpsPayment = bankWpsPaymentValue.toLocaleString('en-US', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    });

    return `
        <div class="swal-payroll-details">
            <div class="swal-details-header"><i class="fas fa-info-circle"></i> ${__('request_details') || 'Request Details'}</div>
            <div class="swal-details-body">
                <div class="swal-detail-item"><span class="swal-detail-label">${__('request_id') || 'Request ID'}</span><span class="swal-detail-value">${requestInvNo || 'N/A'}</span></div>
                <div class="swal-detail-item"><span class="swal-detail-label">${__('payroll_month') || 'Payroll Month'}</span><span class="swal-detail-value">${payrollMonth || 'N/A'}</span></div>
                <div class="swal-detail-item"><span class="swal-detail-label">${__('employees', 'Employees')}</span><span class="swal-detail-value">${employeeCount || 0}</span></div>
                <div class="swal-detail-item"><span class="swal-detail-label">${__('total_net', 'Total Net')}</span><span class="swal-detail-value">SAR ${formattedTotalNet}</span></div>
                <div class="swal-detail-item"><span class="swal-detail-label">${__('requested_by', 'Requested By')}</span><span class="swal-detail-value">${safeRequesterName}</span></div>
                <div class="swal-detail-item"><span class="swal-detail-label">${__('bank_wps_payment', 'Bank WPS Payment')}</span><span class="swal-detail-value">SAR ${formattedBankWpsPayment}</span></div>
            </div>
        </div>
    `;
}

function buildPayrollApprovalBankReportHtml(requestInvNo, payrollMonth) {
    const report = payrollApprovalBankReports[String(requestInvNo || '').trim()] || null;
    const rows = Array.isArray(report && report.rows) ? report.rows : [];
    const bankColumns = Array.isArray(report && report.bank_columns) ? report.bank_columns : [];
    const bankColumnLabels = {
        ncb: 'NATIONAL COMMERCIAL BANK',
        riyadh_bank: 'RIYADH BANK',
        saudi_investment_bank: 'SAUDI INVESTMENT BANK',
        al_rajhi_bank: 'AL RAJHI BANK',
        alinma_bank: 'ALINMA BANK',
        anb_bank: 'ANB BANK',
        al_jazira: 'AL JAZIRA',
        other_bank: 'OTHER BANKS'
    };

    if (rows.length === 0) {
        return `
            <div class="swal-payroll-details">
                <div class="swal-details-header"><i class="fas fa-table"></i> Payroll Summary Report</div>
                <div class="text-muted">No payroll summary data available for ${payrollMonth || 'selected month'}.</div>
            </div>
        `;
    }

    const monthTitle = String(payrollMonth || report.month || '').trim();
    const formatAmount = (value) => Number(value || 0).toLocaleString('en-US', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    });
    const bankHeaderCells = bankColumns.map((bankColumn) => `
        <th style="padding:6px 8px;border:1px solid #dbe3ef;">${bankColumnLabels[bankColumn] || String(bankColumn || '').replace(/_/g, ' ').toUpperCase()}</th>
    `).join('');

    const bodyRows = rows.map((row, index) => {
        const isTotal = String(row.branch || '').toUpperCase() === 'TOTAL';
        const bankValueCells = bankColumns.map((bankColumn) => `
            <td style="padding:6px 8px;border:1px solid #dbe3ef;text-align:right;">${formatAmount(row[bankColumn])}</td>
        `).join('');
        return `
            <tr ${isTotal ? 'style="font-weight:700;background:#eef5ff;"' : ''}>
                <td style="padding:6px 8px;border:1px solid #dbe3ef;text-align:center;">${isTotal ? '' : (index + 1)}</td>
                <td style="padding:6px 8px;border:1px solid #dbe3ef;text-align:center;">${Number(row.no_of_emp || 0)}</td>
                <td style="padding:6px 8px;border:1px solid #dbe3ef;white-space:nowrap;">${String(row.branch || '-')}</td>
                <td style="padding:6px 8px;border:1px solid #dbe3ef;text-align:right;">${formatAmount(row.salary_register)}</td>
                ${bankValueCells}
                <td style="padding:6px 8px;border:1px solid #dbe3ef;text-align:right;">${formatAmount(row.cash_salary)}</td>
            </tr>
        `;
    }).join('');

    return `
        <div class="swal-payroll-details" style="max-height:360px;overflow:auto;">
            <table style="width:100%;border-collapse:collapse;font-size:12px;background:#fff;">
                <thead>
                    <tr style="background:#f1f5f9;">
                        <th style="padding:6px 8px;border:1px solid #dbe3ef;">S/N</th>
                        <th style="padding:6px 8px;border:1px solid #dbe3ef;">NO OF EMP.</th>
                        <th style="padding:6px 8px;border:1px solid #dbe3ef;">BRANCH</th>
                        <th style="padding:6px 8px;border:1px solid #dbe3ef;">SALARY REGISTER</th>
                        ${bankHeaderCells}
                        <th style="padding:6px 8px;border:1px solid #dbe3ef;">CASH SALARY</th>
                    </tr>
                </thead>
                <tbody>${bodyRows}</tbody>
            </table>
        </div>
    `;
}

function buildPayrollApprovalBankReportToggleHtml(requestInvNo, payrollMonth) {
    if (!payrollApprovalCanViewBankReport) {
        return '';
    }

    return `
        <div class="text-left mb-2">
            <button type="button" id="togglePayrollBankReportBtn" class="btn btn-sm btn-outline-primary">
                ${__('hide_payroll_report', 'Hide Payroll Report')}
            </button>
        </div>
        <div id="payrollBankReportContainer">
            ${buildPayrollApprovalBankReportHtml(requestInvNo, payrollMonth)}
        </div>
    `;
}

function applyFilters() {
    const status = document.getElementById('statusFilter').value;
    const search = document.getElementById('searchFilter').value;
    const baseUrl = window.location.href.split('?')[0];
    window.location.href = `${baseUrl}?status=${encodeURIComponent(status)}&search=${encodeURIComponent(search)}&page=1`;
}

function resetFilters(defaultLimit) {
    const baseUrl = window.location.href.split('?')[0];
    window.location.href = `${baseUrl}?status=my_pending&limit=${defaultLimit}&page=1`;
}

async function approvePayrollRequest(requestInvNo, payrollMonth, employeeCount, totalNet, requesterName, bankTotalNet) {
    const result = await Swal.fire({
        title: '<?= __('approve') ?> Payroll',
        ...(payrollApprovalUseWideModal ? { width: '95%' } : {width: '40%' }),
        html: `
            ${buildPayrollRequestDetailsHtml(requestInvNo, payrollMonth, employeeCount, totalNet, requesterName, bankTotalNet)}
            ${buildPayrollApprovalBankReportToggleHtml(requestInvNo, payrollMonth)}
            <div class="text-left">
                <label for="payrollApprovalComment" class="font-weight-bold"><?= __('approval_comment', 'Approval Comment') ?> <span class="text-muted"><?= __('optional', 'Optional') ?></span></label>
                <textarea id="payrollApprovalComment" class="form-control" rows="3" placeholder="<?= __('add_comments', 'Add comments') ?>"></textarea>
            </div>
        `,
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: '<i class="fa fa-check"></i> <?= __('approve') ?>',
        cancelButtonText: '<?= __('cancel') ?>',
        confirmButtonColor: '#28a745',
        allowOutsideClick: false,
        didOpen: () => {
            const toggleBtn = document.getElementById('togglePayrollBankReportBtn');
            const reportContainer = document.getElementById('payrollBankReportContainer');

            if (!toggleBtn || !reportContainer) {
                return;
            }

            toggleBtn.addEventListener('click', () => {
                const willShow = reportContainer.classList.contains('d-none');
                reportContainer.classList.toggle('d-none', !willShow);
                toggleBtn.textContent = willShow
                    ? __('hide_payroll_report', 'Hide Payroll Report')
                    : __('show_payroll_report', 'Show Payroll Report');
            });
        },
        preConfirm: () => ({
            note: (document.getElementById('payrollApprovalComment') || {}).value || ''
        })
    });

    if (!result.isConfirmed) {
        return;
    }

    // Show processing modal and keep it visible until approval email handling completes
    Swal.fire({
        title: '<?= __('processing') ?>',
        html: '<?= __('please_wait_processing') ?><br><small>Sending email notification...</small>',
        allowOutsideClick: false,
        allowEscapeKey: false,
        showConfirmButton: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });

    try {
        const payload = new URLSearchParams();
        payload.append('action', 'approve_request');
        payload.append('request_inv_no', requestInvNo);
        payload.append('note', (result.value && result.value.note) ? result.value.note : '');

        const response = await fetch('./includes/ajaxFile/payroll_approval_handler.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8' },
            body: payload.toString()
        });

        const data = await response.json();
        Swal.close();

        if (data.status === 'success') {
            const resultIcon = data.email_sent === false ? 'warning' : 'success';
            const resultTitle = data.email_sent === false ? '<?= __('warning', 'Warning') ?>' : '<?= __('success') ?>';
            await Swal.fire(resultTitle, data.message || 'Success', resultIcon);
            location.reload();
            return;
        }

        Swal.fire('<?= __('error') ?>', data.message || 'Failed to approve request', 'error');
    } catch (error) {
        Swal.close();
        Swal.fire('<?= __('error') ?>', error.message || 'Failed to approve request', 'error');
    }
}

async function rejectPayrollRequest(requestInvNo, payrollMonth, employeeCount, totalNet, requesterName, bankTotalNet) {
    const result = await Swal.fire({
        title: '<?= __('confirm_rejection', 'Confirm Rejection') ?>',
        html: buildPayrollRequestDetailsHtml(requestInvNo, payrollMonth, employeeCount, totalNet, requesterName, bankTotalNet),
        input: 'textarea',
        inputLabel: '<?= __('provide_rejection_reason', 'Provide Rejection Reason') ?>',
        inputPlaceholder: '<?= __('enter_reason_here', 'Enter reason here') ?>',
        inputValidator: (value) => {
            if (!value || !value.trim()) {
                return '<?= __('rejection_reason_required', 'Rejection reason is required') ?>';
            }
            return null;
        },
        showCancelButton: true,
        confirmButtonText: '<?= __('submit_rejection', 'Submit Rejection') ?>',
        cancelButtonText: '<?= __('cancel') ?>',
        confirmButtonColor: '#dc3545',
        allowOutsideClick: false
    });

    if (!result.isConfirmed) {
        return;
    }

    // Show processing modal and keep it visible until rejection email handling completes
    Swal.fire({
        title: '<?= __('processing') ?>',
        html: '<?= __('please_wait_processing') ?><br><small>Sending email notification...</small>',
        allowOutsideClick: false,
        allowEscapeKey: false,
        showConfirmButton: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });

    try {
        const payload = new URLSearchParams();
        payload.append('action', 'reject_request');
        payload.append('request_inv_no', requestInvNo);
        payload.append('note', result.value || 'Rejected');

        const response = await fetch('./includes/ajaxFile/payroll_approval_handler.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8' },
            body: payload.toString()
        });

        const data = await response.json();
        Swal.close();

        if (data.status === 'success') {
            const resultIcon = data.email_sent === false ? 'warning' : 'success';
            const resultTitle = data.email_sent === false ? '<?= __('warning', 'Warning') ?>' : '<?= __('success') ?>';
            await Swal.fire(resultTitle, data.message || 'Success', resultIcon);
            location.reload();
            return;
        }

        Swal.fire('<?= __('error') ?>', data.message || 'Failed to reject request', 'error');
    } catch (error) {
        Swal.close();
        Swal.fire('<?= __('error') ?>', error.message || 'Failed to reject request', 'error');
    }
}

async function openFinanceVerificationSetupModal(requestInvNo, payrollMonth) {
    try {
        const loadPayload = new URLSearchParams();
        loadPayload.append('action', 'get_finance_verification_setup');
        loadPayload.append('request_inv_no', requestInvNo);
        loadPayload.append('month', payrollMonth);

        Swal.fire({
            title: '<?= __('loading', 'Loading') ?>',
            html: '<?= __('please_wait_fetching_data', 'Please wait while fetching data...') ?>',
            allowOutsideClick: false,
            allowEscapeKey: false,
            showConfirmButton: false,
            didOpen: () => Swal.showLoading()
        });

        const loadResponse = await fetch('./includes/ajaxFile/payroll_approval_handler.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8' },
            body: loadPayload.toString()
        });
        const loadData = await loadResponse.json();
        Swal.close();

        if (!loadResponse.ok || loadData.status !== 'success') {
            throw new Error(loadData.message || 'Failed to load finance verification setup data.');
        }

        const officers = Array.isArray(loadData.officers) ? loadData.officers : [];
        const companies = Array.isArray(loadData.companies) ? loadData.companies : [];
        const existing = loadData.existing || {};
        const historyItems = Array.isArray(loadData.history) ? loadData.history : [];
        const assignedOfficerEmpIds = new Set(Array.isArray(loadData.assigned_officer_emp_ids)
            ? loadData.assigned_officer_emp_ids.map((value) => String(value || '').trim()).filter(Boolean)
            : []);
        const companyNameById = companies.reduce((acc, company) => {
            const id = String(company.comp_id || '').trim();
            if (id) {
                acc[id] = String(company.comp_name || id);
            }
            return acc;
        }, {});

        if (officers.length === 0) {
            throw new Error('No finance officer available for assignment.');
        }
        if (companies.length === 0) {
            throw new Error('No payroll company data available for this month.');
        }

        const assignedOfficerEmpId = String(existing.finance_officer_emp_id || '').trim();
        const officerApproved = !!existing.officer_approved;
        const officerOptions = officers.map((officer) => {
            const officerEmpId = String(officer.emp_id || '').trim();
            const isAlreadyAssignedOfficer = assignedOfficerEmpIds.has(officerEmpId)
                || (assignedOfficerEmpId !== '' && officerEmpId === assignedOfficerEmpId);
            const disabledAttr = isAlreadyAssignedOfficer ? ' disabled' : '';
            const statusSuffix = isAlreadyAssignedOfficer
                ? (officerApproved
                    ? ' - <?= __('already_assigned_approved', 'Already Assigned (Approved)') ?>'
                    : ' - <?= __('already_assigned', 'Already Assigned') ?>')
                : '';
            return `<option value="${officerEmpId.replace(/"/g, '&quot;')}"${disabledAttr}>${String(officer.name || officer.emp_id || 'N/A')}${statusSuffix}</option>`;
        }).join('');

        const existingCompanyIds = new Set(Array.isArray(existing.selected_company_ids) ? existing.selected_company_ids.map(v => String(v || '')) : []);
        const assignedCount = existingCompanyIds.size;
        const totalCompanyCount = companies.length;
        const sentOfficerName = String(existing.finance_officer_name || existing.finance_officer_emp_id || '').trim();
        const sentAtRaw = String(existing.confirmed_at || '').trim();
        const sentAtText = sentAtRaw ? sentAtRaw : '<?= __('not_available', 'N/A') ?>';
        const officerApprovedAtRaw = String(existing.officer_approved_at || '').trim();
        const officerApprovedAtText = officerApprovedAtRaw ? officerApprovedAtRaw : '<?= __('not_available', 'N/A') ?>';
        const currentStatusHtml = assignedCount > 0
            ? `
                <div class="alert alert-light border mb-3" style="border-color:#dbe6ef !important;">
                    <div><strong><?= __('sent_to_finance_officer', 'Already Sent To Finance Officer') ?>:</strong> ${sentOfficerName || '<?= __('not_available', 'N/A') ?>'}</div>
                    <div><strong><?= __('sent_at', 'Sent At') ?>:</strong> ${sentAtText}</div>
                    <div><strong><?= __('finance_officer_approval', 'Finance Officer Approval') ?>:</strong> ${officerApproved ? '<?= __('approved', 'Approved') ?>' : '<?= __('pending', 'Pending') ?>'}${officerApproved ? ` (${officerApprovedAtText})` : ''}</div>
                    ${officerApproved ? `<div><strong><?= __('completed_by_finance_officer', 'Completed By Finance Officer') ?>:</strong> ${sentOfficerName || '<?= __('not_available', 'N/A') ?>'}</div>` : ''}
                    <div><strong><?= __('assigned_companies', 'Assigned Companies') ?>:</strong> ${assignedCount} / ${totalCompanyCount}</div>
                </div>
            `
            : '';
        const statusToggleHtml = currentStatusHtml
            ? `
                <div class="mb-2">
                    <button type="button" class="btn btn-sm btn-outline-secondary" id="financeStatusToggleBtn">
                        <?= __('show_current_status', 'Show Current Status') ?>
                    </button>
                </div>
                <div id="financeStatusTogglePanel" class="d-none">
                    ${currentStatusHtml}
                </div>
            `
            : '';
        const officerCompletionHtml = officerApproved
            ? `
                <div class="mb-3" style="border:1px solid #bde5c8;border-radius:8px;padding:10px 12px;background:#f4fff7;color:#1b5e20;">
                    <div class="font-weight-bold"><?= __('finance_officer_completed', 'Finance Officer Completed') ?></div>
                    <div style="font-size:13px;"><strong><?= __('finance_officer', 'Finance Officer') ?>:</strong> ${sentOfficerName || '<?= __('not_available', 'N/A') ?>'}</div>
                    <div style="font-size:13px;"><strong><?= __('approved_at', 'Approved At') ?>:</strong> ${officerApprovedAtText}</div>
                </div>
            `
            : '';
        const assignedCompanyDetails = Array.from(existingCompanyIds)
            .map((id) => ({
                id,
                name: companyNameById[id] || id
            }))
            .sort((a, b) => a.name.localeCompare(b.name));
        const actionTypeLabelMap = {
            setup_assigned: '<?= __('setup_assigned', 'Setup Assigned') ?>',
            setup_updated: '<?= __('setup_updated', 'Setup Updated') ?>',
            officer_approved: '<?= __('officer_approved', 'Officer Approved') ?>'
        };
        const actionTypeClassMap = {
            setup_assigned: 'badge-primary',
            setup_updated: 'badge-warning',
            officer_approved: 'badge-success'
        };
        const renderedHistoryItems = historyItems.length > 0
            ? historyItems
            : (assignedCompanyDetails.length > 0
                ? [{
                    action_type: 'setup_assigned',
                    action_note: '<?= __('initial_assignment_available', 'Initial assignment is available from current setup.') ?>',
                    created_by_name: sentOfficerName ? '<?= __('finance_manager', 'Finance Manager') ?>' : '<?= __('system', 'System') ?>',
                    created_at: sentAtText,
                    finance_officer_name: sentOfficerName,
                    selected_company_ids: assignedCompanyDetails.map((item) => item.id)
                }]
                : []);
        const getItemCompanyIds = (item) => {
            const sourceIds = Array.isArray(item.display_company_ids)
                ? item.display_company_ids
                : (Array.isArray(item.selected_company_ids) ? item.selected_company_ids : []);
            return sourceIds.map((value) => String(value || '').trim()).filter(Boolean);
        };
        const splitAssignedCompaniesByAssigner = (items) => {
            if (!Array.isArray(items) || items.length === 0) {
                return [];
            }

            const withIndex = items.map((item, index) => ({ item, index }));
            const ordered = [...withIndex].sort((a, b) => {
                const aTime = Date.parse(String(a.item.created_at || ''));
                const bTime = Date.parse(String(b.item.created_at || ''));
                const safeATime = Number.isNaN(aTime) ? 0 : aTime;
                const safeBTime = Number.isNaN(bTime) ? 0 : bTime;

                if (safeATime === safeBTime) {
                    return a.index - b.index;
                }
                return safeATime - safeBTime;
            });

            const seenCompanyIds = new Set();
            const displayIdsByOriginalIndex = new Map();

            ordered.forEach(({ item, index }) => {
                const rawIds = Array.isArray(item.selected_company_ids)
                    ? item.selected_company_ids.map((value) => String(value || '').trim()).filter(Boolean)
                    : [];
                const uniqueRawIds = Array.from(new Set(rawIds));
                const newCompanyIds = uniqueRawIds.filter((companyId) => !seenCompanyIds.has(companyId));

                newCompanyIds.forEach((companyId) => seenCompanyIds.add(companyId));
                displayIdsByOriginalIndex.set(index, newCompanyIds);
            });

            return withIndex.map(({ item, index }) => {
                const displayCompanyIds = displayIdsByOriginalIndex.get(index) || [];
                return {
                    ...item,
                    display_company_ids: displayCompanyIds,
                    display_company_count: displayCompanyIds.length
                };
            });
        };
        const buildHistoryCards = (items, emptyText) => {
            if (!Array.isArray(items) || items.length === 0) {
                return `<div style="border:1px dashed #cbd5e1;background:#fff;border-radius:6px;padding:10px;color:#64748b;font-size:13px;">${emptyText}</div>`;
            }

            return items.map((item) => {
                const actionType = String(item.action_type || '').trim();
                const actionLabel = actionTypeLabelMap[actionType] || actionType || '<?= __('update', 'Update') ?>';
                const actionClass = actionTypeClassMap[actionType] || 'badge-secondary';
                const createdBy = String(item.created_by_name || item.created_by || '<?= __('system', 'System') ?>');
                const createdAt = String(item.created_at || '');
                const officerName = String(item.finance_officer_name || '').trim();
                const itemCompanyIds = getItemCompanyIds(item);
                const companyCount = Number(item.display_company_count || item.company_count || itemCompanyIds.length || 0);
                const itemCompanyNames = itemCompanyIds.map((id) => companyNameById[id] || id);
                const note = String(item.action_note || '').trim();
                const companyBadgeHtml = itemCompanyNames.length > 0
                    ? `
                        <div style="margin-top:6px;display:flex;flex-wrap:wrap;gap:6px;">
                            ${itemCompanyNames.map((companyName) => `
                                <span style="display:inline-block;border:1px solid #d7e4f2;background:#f8fbff;color:#2f4156;border-radius:999px;padding:2px 9px;font-size:12px;font-weight:600;">
                                    ${companyName}
                                </span>
                            `).join('')}
                        </div>
                    `
                    : '';

                return `
                    <div style="border:1px solid #e1ebf3;background:#fff;border-radius:6px;padding:8px 10px;margin-bottom:8px;">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <span class="badge ${actionClass}">${actionLabel}</span>
                            <small class="text-muted">${createdAt || '-'}</small>
                        </div>
                        <div style="font-size:13px;color:#2f4156;"><strong><?= __('by', 'By') ?>:</strong> ${createdBy}</div>
                        ${officerName ? `<div style="font-size:13px;color:#2f4156;"><strong><?= __('finance_officer', 'Finance Officer') ?>:</strong> ${officerName}</div>` : ''}
                        ${companyCount > 0 ? `<div style="font-size:13px;color:#2f4156;"><strong><?= __('companies', 'Companies') ?>:</strong> ${companyCount}</div>` : ''}
                        ${itemCompanyNames.length > 0 ? `<div style="margin-top:4px;font-size:12px;color:#607080;"><strong><?= __('assigned_companies', 'Assigned Companies') ?>:</strong></div>` : ''}
                        ${companyBadgeHtml}
                        ${note ? `<div style="font-size:12px;color:#607080;margin-top:2px;">${note}</div>` : ''}
                    </div>
                `;
            }).join('');
        };

        const assignedHistoryItems = renderedHistoryItems.filter((item) => {
            const actionType = String(item.action_type || '').trim();
            return actionType === 'setup_assigned' || actionType === 'setup_updated' || actionType === '';
        });
        const assignedHistoryItemsUnique = splitAssignedCompaniesByAssigner(assignedHistoryItems);
        const approvedHistoryItemsRaw = renderedHistoryItems.filter((item) => String(item.action_type || '').trim() === 'officer_approved');
        const approvedHistoryItems = approvedHistoryItemsRaw.length > 0
            ? approvedHistoryItemsRaw
            : (officerApproved
                ? [{
                    action_type: 'officer_approved',
                    action_note: '<?= __('finance_officer_approved_assignment', 'Finance officer approved assignment.') ?>',
                    created_by_name: sentOfficerName || '<?= __('not_available', 'N/A') ?>',
                    created_at: officerApprovedAtText,
                    finance_officer_name: sentOfficerName,
                    selected_company_ids: assignedCompanyDetails.map((item) => item.id),
                    company_count: assignedCompanyDetails.length
                }]
                : []);

        const financeHistoryTabsHtml = `
            <div class="mb-3" style="border:1px solid #dbe6ef;border-radius:8px;background:#f8fbff;overflow:hidden;">
                <div style="display:flex;gap:8px;padding:10px 12px;border-bottom:1px solid #dbe6ef;background:#eef5fb;">
                    <button type="button" class="btn btn-sm btn-outline-secondary finance-history-tab-btn" data-target="assigned"><?= __('assigned', 'Assigned') ?> (${Math.max(assignedHistoryItems.length, assignedCompanyDetails.length)})</button>
                    <button type="button" class="btn btn-sm btn-outline-secondary finance-history-tab-btn" data-target="approved"><?= __('approved', 'Approved') ?> (${approvedHistoryItems.length})</button>
                </div>
                <div id="financeHistoryTabHint" style="padding:10px 12px;color:#607080;font-size:13px;">
                    <?= __('click_tab_to_view_data', 'Click Assigned or Approved tab to view data.') ?>
                </div>
                <div id="financeHistoryTabAssigned" class="finance-history-tab-panel d-none" style="padding:10px 12px;max-height:260px;overflow-y:auto;">
                    ${buildHistoryCards(assignedHistoryItemsUnique, '<?= __('no_assignment_history_found', 'No assignment history found yet.') ?>')}
                </div>
                <div id="financeHistoryTabApproved" class="finance-history-tab-panel d-none" style="padding:10px 12px;max-height:260px;overflow-y:auto;">
                    ${officerCompletionHtml}
                    ${buildHistoryCards(approvedHistoryItems, '<?= __('no_approval_history_found', 'No approval history found yet.') ?>')}
                </div>
            </div>
        `;
        const companyOptions = companies.map((company) => {
            const companyId = String(company.comp_id || '').trim();
            const isAssigned = existingCompanyIds.has(companyId);
            const disabledAttr = isAssigned ? ' disabled' : '';
            const styleAttr = isAssigned ? ' style="color:#dc3545;font-weight:700;"' : '';
            const suffix = isAssigned ? ' - <?= __('assigned', 'Assigned') ?>' : '';
            return `<option value="${companyId.replace(/"/g, '&quot;')}"${disabledAttr}${styleAttr}>${String(company.comp_name || 'N/A')} (${Number(company.employee_count || 0)})${suffix}</option>`;
        }).join('');

        const hasSelectableOfficer = officers.some((officer) => {
            const officerEmpId = String(officer.emp_id || '').trim();
            return officerEmpId !== '' && !assignedOfficerEmpIds.has(officerEmpId) && officerEmpId !== assignedOfficerEmpId;
        });
        if (!hasSelectableOfficer) {
            throw new Error('<?= __('no_available_finance_officer_to_assign', 'No available finance officer to assign. Already assigned officer is locked.') ?>');
        }

        const hasSelectableCompany = companies.some((company) => {
            const companyId = String(company.comp_id || '').trim();
            return companyId !== '' && !existingCompanyIds.has(companyId);
        });
        if (!hasSelectableCompany) {
            throw new Error('<?= __('all_companies_assigned', 'All companies are already assigned for this payroll month.') ?>');
        }

        const modalResult = await Swal.fire({
            title: '<?= __('finance_verification_setup', 'Finance Verification Setup') ?>',
            width: '900px',
            html: `
                <div class="text-left">
                    ${statusToggleHtml}
                    ${financeHistoryTabsHtml}
                    <label for="verificationFinanceOfficer" class="font-weight-bold"><?= __('finance_officer', 'Finance Officer') ?></label>
                    <select id="verificationFinanceOfficer" class="form-control mb-3">
                        <option value=""><?= __('select', 'Select') ?></option>
                        ${officerOptions}
                    </select>

                    <label for="verificationCompanies" class="font-weight-bold"><?= __('company', 'Company') ?></label>
                    <select id="verificationCompanies" class="form-control mb-3" multiple>
                        ${companyOptions}
                    </select>

                    <div class="form-check mt-2">
                        <input class="form-check-input" type="checkbox" id="verificationConfirmCheck">
                        <label class="form-check-label" for="verificationConfirmCheck">
                            <?= __('confirm_selected_companies_for_verification', 'I confirm selected companies for finance verification.') ?>
                        </label>
                    </div>
                </div>
            `,
            showCancelButton: true,
            confirmButtonColor: '#28a745',
            confirmButtonText: '<?= __('confirm', 'Confirm') ?>',
            cancelButtonText: '<?= __('cancel', 'Cancel') ?>',
            allowOutsideClick: false,
            didOpen: () => {
                const financeOfficerSelect = document.getElementById('verificationFinanceOfficer');
                const companiesSelect = document.getElementById('verificationCompanies');
                const tabButtons = document.querySelectorAll('.finance-history-tab-btn');
                const statusToggleBtn = document.getElementById('financeStatusToggleBtn');
                const statusTogglePanel = document.getElementById('financeStatusTogglePanel');

                if (statusToggleBtn && statusTogglePanel) {
                    statusToggleBtn.addEventListener('click', () => {
                        const willShow = statusTogglePanel.classList.contains('d-none');
                        statusTogglePanel.classList.toggle('d-none', !willShow);
                        statusToggleBtn.textContent = willShow
                            ? '<?= __('hide_current_status', 'Hide Current Status') ?>'
                            : '<?= __('show_current_status', 'Show Current Status') ?>';
                    });
                }

                tabButtons.forEach((button) => {
                    button.addEventListener('click', () => {
                        const target = String(button.getAttribute('data-target') || '').trim();
                        const assignedPanel = document.getElementById('financeHistoryTabAssigned');
                        const approvedPanel = document.getElementById('financeHistoryTabApproved');
                        const hintPanel = document.getElementById('financeHistoryTabHint');
                        const isActive = button.classList.contains('btn-primary');

                        tabButtons.forEach((btn) => {
                            btn.classList.remove('btn-primary');
                            btn.classList.add('btn-outline-secondary');
                        });

                        if (assignedPanel && approvedPanel) {
                            if (isActive) {
                                assignedPanel.classList.add('d-none');
                                approvedPanel.classList.add('d-none');
                                if (hintPanel) {
                                    hintPanel.classList.remove('d-none');
                                }
                                return;
                            }

                            button.classList.remove('btn-outline-secondary');
                            button.classList.add('btn-primary');

                            const showApproved = target === 'approved';
                            assignedPanel.classList.toggle('d-none', showApproved);
                            approvedPanel.classList.toggle('d-none', !showApproved);
                            if (hintPanel) {
                                hintPanel.classList.add('d-none');
                            }
                        }
                    });
                });

                if (window.jQuery && typeof jQuery.fn.select2 === 'function') {
                    const $popup = jQuery('.swal2-popup');
                    jQuery('#verificationFinanceOfficer').select2({ width: '100%', dropdownParent: $popup });
                    jQuery('#verificationCompanies').select2({ width: '100%', dropdownParent: $popup, closeOnSelect: false });
                }

                if (financeOfficerSelect) {
                    const firstEnabledOfficerOption = Array.from(financeOfficerSelect.options || []).find((option) => {
                        return !!String(option.value || '').trim() && !option.disabled;
                    });
                    if (firstEnabledOfficerOption && String(financeOfficerSelect.value || '').trim() === '') {
                        financeOfficerSelect.value = String(firstEnabledOfficerOption.value || '').trim();
                    }

                    if (window.jQuery && typeof jQuery.fn.select2 === 'function') {
                        jQuery('#verificationFinanceOfficer').trigger('change.select2');
                    }
                }
            },
            preConfirm: () => {
                const financeOfficerEmpId = (document.getElementById('verificationFinanceOfficer') || {}).value || '';
                const companiesSelect = document.getElementById('verificationCompanies');
                const confirmCheck = document.getElementById('verificationConfirmCheck');

                const companyIds = companiesSelect
                    ? Array.from(companiesSelect.selectedOptions || [])
                        .map(option => String(option.value || '').trim())
                        .filter(Boolean)
                    : [];
                const uniqueCompanyIds = Array.from(new Set(companyIds));

                if (!financeOfficerEmpId) {
                    Swal.showValidationMessage('<?= __('finance_officer_required', 'Finance officer selection is required.') ?>');
                    return false;
                }
                if (uniqueCompanyIds.length === 0) {
                    Swal.showValidationMessage('<?= __('company_required', 'Company is required.') ?>');
                    return false;
                }
                if (!confirmCheck || !confirmCheck.checked) {
                    Swal.showValidationMessage('<?= __('confirmation_required', 'Please confirm selected companies to continue.') ?>');
                    return false;
                }

                return { financeOfficerEmpId, companyIds: uniqueCompanyIds };
            }
        });

        if (!modalResult.isConfirmed) {
            return;
        }

        const submitPayload = new URLSearchParams();
        submitPayload.append('action', 'submit_finance_verification_setup');
        submitPayload.append('request_inv_no', requestInvNo);
        submitPayload.append('month', payrollMonth);
        submitPayload.append('finance_officer_emp_id', modalResult.value.financeOfficerEmpId);
        submitPayload.append('company_ids', JSON.stringify(modalResult.value.companyIds || []));

        Swal.fire({
            title: '<?= __('processing', 'Processing') ?>',
            html: '<?= __('please_wait_processing', 'Please wait while processing...') ?>',
            allowOutsideClick: false,
            allowEscapeKey: false,
            showConfirmButton: false,
            didOpen: () => Swal.showLoading()
        });

        const submitResponse = await fetch('./includes/ajaxFile/payroll_approval_handler.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8' },
            body: submitPayload.toString()
        });
        const submitData = await submitResponse.json();
        Swal.close();

        if (!submitResponse.ok || submitData.status !== 'success') {
            throw new Error(submitData.message || 'Failed to save finance verification setup.');
        }

        await Swal.fire('<?= __('success', 'Success') ?>', submitData.message || 'Finance verification setup confirmed successfully.', 'success');
        location.reload();
    } catch (error) {
        Swal.close();
        Swal.fire('<?= __('error', 'Error') ?>', error.message || 'Failed to complete finance verification setup.', 'error');
    }
}

async function openCompanyPayrollReportModal(requestInvNo, payrollMonth) {
    try {
        const optionsPayload = new URLSearchParams();
        optionsPayload.append('action', 'get_direct_supervisor_options');
        optionsPayload.append('request_inv_no', requestInvNo);
        optionsPayload.append('month', payrollMonth);

        Swal.fire({
            title: '<?= __('loading', 'Loading') ?>',
            html: '<?= __('please_wait_fetching_data', 'Please wait while fetching data...') ?>',
            allowOutsideClick: false,
            allowEscapeKey: false,
            showConfirmButton: false,
            didOpen: () => Swal.showLoading()
        });

        const optionsResponse = await fetch('./includes/ajaxFile/payroll_approval_handler.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8' },
            body: optionsPayload.toString()
        });
        const optionsData = await optionsResponse.json();
        Swal.close();

        if (!optionsResponse.ok || optionsData.status !== 'success') {
            throw new Error(optionsData.message || 'Failed to load direct supervisor options.');
        }

        const supervisors = Array.isArray(optionsData.supervisors) ? optionsData.supervisors : [];

        if (supervisors.length === 0) {
            throw new Error('<?= __('no_supervisor_with_email_found', 'No direct supervisor with registered email found.') ?>');
        }

        // A supervisor is only unselectable as primary when their team was merged into
        // someone else's report (merged_into set) - they never got their own email.
        // A supervisor who was themselves already sent a report stays selectable so
        // HR Payroll / HR Senior BP can resend (e.g. figures changed after the first send).
        const supervisorOptionsList = supervisors.map(s => {
            const isSent = Number(s.is_sent || 0) === 1 || s.is_sent === true;
            const isMergedElsewhere = String(s.merged_into || '').trim() !== '';
            const disabledAttr = isMergedElsewhere ? ' disabled="disabled"' : '';
            const sentAttr = isSent ? '1' : '0';
            const mergedAttr = isMergedElsewhere ? '1' : '0';
            const empId = String(s.supervisor_emp_id || '');
            return `<option value="${empId.replace(/"/g, '&quot;')}" data-is-sent="${sentAttr}" data-is-merged="${mergedAttr}" data-email="${String(s.supervisor_email || '').replace(/"/g, '&quot;')}"${disabledAttr}>${String(s.supervisor_name || 'N/A')} (${empId}) - ${Number(s.employee_count || 0)} <?= __('employees', 'Employees') ?></option>`;
        }).join('');
        const hasAvailableSupervisor = supervisors.some(s => String(s.merged_into || '').trim() === '');
        if (!hasAvailableSupervisor) {
            throw new Error('<?= __('all_supervisor_batch_emails_sent', 'Batch email already sent for all direct supervisors.') ?>');
        }

        async function showEmployeesPreviewModal(supervisorId) {
            const previewPayload = new URLSearchParams();
            previewPayload.append('action', 'get_supervisor_employees_preview');
            previewPayload.append('request_inv_no', requestInvNo);
            previewPayload.append('month', payrollMonth);
            previewPayload.append('supervisor_emp_id', supervisorId);

            try {
                Swal.fire({
                    title: '<?= __('loading', 'Loading') ?>',
                    allowOutsideClick: false,
                    allowEscapeKey: false,
                    showConfirmButton: false,
                    didOpen: () => Swal.showLoading()
                });

                const previewResponse = await fetch('./includes/ajaxFile/payroll_approval_handler.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8' },
                    body: previewPayload.toString()
                });
                const previewData = await previewResponse.json();

                if (!previewResponse.ok || previewData.status !== 'success') {
                    throw new Error(previewData.message || 'Failed to load employees preview.');
                }

                const employees = Array.isArray(previewData.employees) ? previewData.employees : [];
                const escapeHtml = (value) => jQuery ? jQuery('<div>').text(value || '').html() : String(value || '');
                const rowsHtml = employees.map((emp, idx) => `<tr><td>${idx + 1}</td><td>${escapeHtml(emp.emp_id)}</td><td>${escapeHtml(emp.name)}</td><td>${escapeHtml(emp.department)}</td><td>${escapeHtml(emp.company)}</td></tr>`).join('');

                await Swal.fire({
                    title: `<?= __('preview_employees', 'Preview Employees') ?> - ${escapeHtml(previewData.supervisor_name)} (${Number(previewData.employee_count || 0)})`,
                    html: `
                        <div class="table-responsive" style="text-align:left;">
                            <table id="supervisorEmployeesPreviewTable" class="table table-sm table-bordered table-hover mb-0" style="width:100%;">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th><?= __('emp_id', 'Emp ID') ?></th>
                                        <th><?= __('name', 'Name') ?></th>
                                        <th><?= __('department', 'Department') ?></th>
                                        <th><?= __('company', 'Company') ?></th>
                                    </tr>
                                </thead>
                                <tbody>${rowsHtml}</tbody>
                            </table>
                        </div>
                    `,
                    width: '60%',
                    allowOutsideClick: false,
                    confirmButtonText: '<?= __('back', 'Back') ?>',
                    didOpen: () => {
                        if (window.jQuery && typeof jQuery.fn.DataTable === 'function') {
                            jQuery('#supervisorEmployeesPreviewTable').DataTable({
                                pageLength: 10,
                                lengthChange: false,
                                order: [[2, 'asc']],
                                language: {
                                    search: '<?= __('search', 'Search') ?>:',
                                    emptyTable: '<?= __('no_data_available_in_table', 'No data available in table') ?>',
                                    zeroRecords: '<?= __('no_matching_records_found', 'No matching records found') ?>'
                                }
                            });
                        }
                    }
                });
            } catch (previewError) {
                await Swal.fire('<?= __('error', 'Error') ?>', previewError.message || 'Failed to load employees preview.', 'error');
            }
        }

        // The select-supervisor modal can be reopened with the previous selection preserved
        // after "Preview Employees" is used, instead of forcing the user to start over.
        let preselectedSupervisorId = '';
        let modalResult;
        while (true) {
            let cameFromPreview = false;

            modalResult = await Swal.fire({
                title: '<?= __('send_supervisor_payroll_report', 'Send Payroll Report by Direct Supervisor') ?>',
                html: `
                    <div class="text-left">
                        <label for="supervisorReportSelect" class="font-weight-bold"><?= __('direct_supervisor', 'Direct Supervisor') ?></label>
                        <select id="supervisorReportSelect" class="form-control mb-2"><option value="" selected><?= __('select_direct_supervisor', 'Select Direct Supervisor') ?></option>${supervisorOptionsList}</select>
                        <button type="button" id="previewSupervisorEmployeesBtn" class="btn btn-outline-primary btn-sm mb-2" style="display:none;">
                            <i class="fa fa-users"></i> <?= __('preview_employees', 'Preview Employees') ?>
                        </button>
                        <div class="small text-info mb-2"><?= __('batch_email_sent_hint_supervisor', 'Supervisors already sent a report can be selected again to resend (e.g. figures changed). Supervisors marked Assigned Elsewhere already had their employees merged into another supervisor\'s report and can\'t be selected here.') ?></div>
                    </div>
                `,
                showCancelButton: true,
                confirmButtonColor: '#28a745',
                confirmButtonText: '<?= __('send_email', 'Send Email') ?>',
                cancelButtonText: '<?= __('cancel', 'Cancel') ?>',
                allowOutsideClick: false,
                width: '45%',
                didOpen: () => {
                    const supervisorSelect = document.getElementById('supervisorReportSelect');
                    if (window.jQuery && typeof jQuery.fn.select2 === 'function' && supervisorSelect) {
                        const $popup = jQuery('.swal2-popup');
                        jQuery(supervisorSelect).select2({
                            width: '100%',
                            dropdownParent: $popup,
                            placeholder: '<?= __('select_direct_supervisor', 'Select Direct Supervisor') ?>',
                            escapeMarkup: function(markup) {
                                return markup;
                            },
                            templateResult: function(state) {
                                if (!state.id) {
                                    return state.text;
                                }

                                const optionEl = state.element || null;
                                const isSent = optionEl && optionEl.getAttribute('data-is-sent') === '1';
                                const isMerged = optionEl && optionEl.getAttribute('data-is-merged') === '1';
                                const safeText = jQuery('<div>').text(state.text || '').html();
                                const badgeHtml = isMerged
                                    ? '<span class="badge badge-secondary float-right"><?= __('assigned_to_other_supervisor', 'Assigned Elsewhere') ?></span>'
                                    : (isSent
                                        ? '<span class="badge badge-warning float-right"><?= __('batch_email_sent_resend', 'Sent - Click to Resend') ?></span>'
                                        : '');

                                return '<span class="d-flex justify-content-between align-items-center w-100"><span>' + safeText + '</span>' + badgeHtml + '</span>';
                            },
                            templateSelection: function(state) {
                                if (!state.id) {
                                    return state.text;
                                }

                                const optionEl = state.element || null;
                                const isSent = optionEl && optionEl.getAttribute('data-is-sent') === '1';
                                const isMerged = optionEl && optionEl.getAttribute('data-is-merged') === '1';
                                const safeText = jQuery('<div>').text(state.text || '').html();
                                const badgeHtml = isMerged
                                    ? ' <span class="badge badge-secondary"><?= __('assigned_to_other_supervisor', 'Assigned Elsewhere') ?></span>'
                                    : (isSent
                                        ? ' <span class="badge badge-warning"><?= __('batch_email_sent_resend', 'Will Resend') ?></span>'
                                        : '');

                                return '<span>' + safeText + badgeHtml + '</span>';
                            }
                        });
                    }

                    if (supervisorSelect && preselectedSupervisorId) {
                        supervisorSelect.value = preselectedSupervisorId;
                        if (window.jQuery && typeof jQuery.fn.select2 === 'function') {
                            jQuery(supervisorSelect).trigger('change');
                        }
                    }

                    const previewBtn = document.getElementById('previewSupervisorEmployeesBtn');
                    const togglePreviewButton = () => {
                        if (!previewBtn || !supervisorSelect) {
                            return;
                        }
                        previewBtn.style.display = String(supervisorSelect.value || '').trim() !== '' ? 'inline-block' : 'none';
                    };
                    togglePreviewButton();
                    if (supervisorSelect) {
                        supervisorSelect.addEventListener('change', togglePreviewButton);
                        if (window.jQuery && typeof jQuery.fn.select2 === 'function') {
                            jQuery(supervisorSelect).on('change.previewToggle', togglePreviewButton);
                        }
                    }

                    if (previewBtn) {
                        previewBtn.addEventListener('click', () => {
                            const supervisorId = supervisorSelect ? String(supervisorSelect.value || '').trim() : '';
                            if (supervisorId === '') {
                                return;
                            }

                            // Close the select modal first (sequential, not nested) so the preview
                            // modal shows cleanly, then the loop below reopens the select modal
                            // afterwards with the same selection restored.
                            preselectedSupervisorId = supervisorId;
                            cameFromPreview = true;
                            Swal.close();
                        });
                    }
                },
                preConfirm: () => {
                    const supervisorSelect = document.getElementById('supervisorReportSelect');
                    const supervisorId = supervisorSelect ? String(supervisorSelect.value || '').trim() : '';
                    if (supervisorId === '') {
                        Swal.showValidationMessage('<?= __('direct_supervisor_required', 'Direct supervisor is required.') ?>');
                        return false;
                    }
                    const selectedOption = supervisorSelect.options[supervisorSelect.selectedIndex];
                    if (selectedOption && selectedOption.disabled) {
                        Swal.showValidationMessage('<?= addslashes(__('supervisor_assigned_elsewhere', 'This supervisor employees were already assigned to another supervisor report. Please select another supervisor.')) ?>');
                        return false;
                    }
                    return { supervisorIds: [supervisorId] };
                }
            });

            if (cameFromPreview) {
                await showEmployeesPreviewModal(preselectedSupervisorId);
                continue;
            }
            break;
        }

        if (!modalResult.isConfirmed) {
            return;
        }

        Swal.fire({
            title: '<?= __('processing', 'Processing') ?>',
            html: '<?= __('please_wait_processing', 'Please wait while processing...') ?>',
            allowOutsideClick: false,
            allowEscapeKey: false,
            showConfirmButton: false,
            didOpen: () => Swal.showLoading()
        });

        const sendPayload = new URLSearchParams();
        sendPayload.append('action', 'send_supervisor_payroll_report');
        sendPayload.append('request_inv_no', requestInvNo);
        sendPayload.append('month', payrollMonth);
        sendPayload.append('supervisor_ids', JSON.stringify(modalResult.value.supervisorIds || []));

        const sendResponse = await fetch('./includes/ajaxFile/payroll_approval_handler.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8' },
            body: sendPayload.toString()
        });
        const sendData = await sendResponse.json();
        Swal.close();

        if (!sendResponse.ok || sendData.status !== 'success') {
            throw new Error(sendData.message || 'Failed to send supervisor payroll report email.');
        }

        await Swal.fire({
            icon: 'success',
            title: '<?= __('success', 'Success') ?>',
            text: sendData.message || 'Supervisor payroll report email sent successfully.',
            allowOutsideClick: false
        });
    } catch (error) {
        Swal.close();
        Swal.fire('<?= __('error', 'Error') ?>', error.message || 'Failed to send supervisor payroll report email.', 'error');
    }
}
</script>
</body>
</html>
