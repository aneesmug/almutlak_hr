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

// A temp-role replacement can act on the original employee's pending approvals too -
// request_approvers.approver_id / current_approver_id still point at the original employee.
$delegatedFromEmpId = getDelegatedFromEmpId($conDB, $empid ?? '');

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

// Filter + count block, wrapped in a closure so the status cards can count every status
// with exactly the same rules (sr_status_counts); the current status runs it once as before.
require_once __DIR__ . '/includes/sr_list_helpers.php';
$sr_scope = sr_scope_vars(get_defined_vars());
$sr_build_filter = function ($currentFilter) use ($sr_scope) {
    extract($sr_scope, EXTR_SKIP);
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
    $where[] = '(ra_pending.approver_id IN (?, ' . (int)$approver_alt_id . ') OR EXISTS (
        SELECT 1 FROM payroll_finance_verification pfv_mine
        WHERE pfv_mine.request_inv_no = pr.request_inv_no
          AND pfv_mine.payroll_month = p_months.month_year
          AND pfv_mine.finance_officer_emp_id IN (?, ' . (int)$approver_alt_id . ')
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
            AND ra_any.approver_id IN (?, ' . (int)$approver_alt_id . ')
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
    return sr_scope_vars(get_defined_vars());
};
extract($sr_build_filter($currentFilter));
$sr_status_counts = sr_status_counts($allStatuses, $currentFilter, $totalItems, $sr_build_filter, 'totalItems');

$totalPages = $showAll ? 1 : (int)ceil($totalItems / max(1, $itemsPerPage));
if ($currentPage > $totalPages && $totalPages > 0) {
    $currentPage = $totalPages;
}

$requests = [];
if ($totalItems > 0) {
    // Vacation dropout rule (includes/payroll_vacation_rules.php): which joined vacation
    // rows drop their employee from the checklist totals. '1=0' when the rule is disabled.
    $vacdaysGuardSql = 'AND ' . payrollVacationDropoutQualifierSql($conDB, 'vd');
    // Paid vacation settlement rule (same file), as join pieces for the per-month pre-compute.
    $paidSettlementWindowsSql = payrollPaidSettlementWindowsSql();
    $paidSettlementPeriodSql = payrollPaidSettlementWindowPeriodSql("CONCAT(m.month_year, '-01')", "LAST_DAY(CONCAT(m.month_year, '-01'))", 'w');
    $mainSql = "SELECT
            p_months.month_year AS payroll_month,
            p_months.employee_count,
            p_months.total_net_salary,
            p_months.bank_total_net_salary,
            p_months.checklist_employee_count,
            p_months.checklist_total_net_salary,
            p_months.checklist_bank_total_net_salary,
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
                COUNT(CASE WHEN COALESCE(e.payment_type, 1) <> 3 AND vac_excl.emp_id IS NULL AND settle_excl.emp_id IS NULL THEN p.emp_id END) AS checklist_employee_count,
                SUM(CASE WHEN COALESCE(e.payment_type, 1) <> 3 AND vac_excl.emp_id IS NULL AND settle_excl.emp_id IS NULL THEN p.net_salary ELSE 0 END) AS checklist_total_net_salary,
                SUM(CASE WHEN COALESCE(e.payment_type, 1) = 1 AND vac_excl.emp_id IS NULL AND settle_excl.emp_id IS NULL THEN p.net_salary ELSE 0 END) AS checklist_bank_total_net_salary
            FROM payrolls p
            INNER JOIN employees e ON e.emp_id = p.emp_id
            -- Same two exclusion rules as payroll_checklist_report.php (long-vacation dropout
            -- + already-settled vacation payout), but precomputed once per distinct month
            -- instead of a correlated NOT EXISTS re-evaluated for every payroll row - this
            -- query aggregates every month in payroll history at once, and the per-row version
            -- timed out (25s cap) once the settlement check was added alongside it.
            LEFT JOIN (
                SELECT DISTINCT m.month_year, vd.emp_id
                FROM (SELECT DISTINCT month_year FROM payrolls) m
                INNER JOIN emp_vacation vd
                    ON vd.review = 'A'
                    AND vd.current_status IN ('approved', 'completed')
                    AND vd.request_inv_no LIKE 'VAC-%'
                    AND vd.start_date <= LAST_DAY(CONCAT(m.month_year, '-01'))
                    AND COALESCE(vd.return_date, vd.start_date) >= CONCAT(m.month_year, '-01')
                    {$vacdaysGuardSql}
            ) vac_excl ON vac_excl.month_year = p.month_year AND vac_excl.emp_id = p.emp_id
            LEFT JOIN (
                SELECT DISTINCT m.month_year, w.emp_id
                FROM (SELECT DISTINCT month_year FROM payrolls) m
                INNER JOIN ({$paidSettlementWindowsSql}) w
                    ON {$paidSettlementPeriodSql}
            ) settle_excl ON settle_excl.month_year = p.month_year AND settle_excl.emp_id COLLATE utf8mb4_general_ci = p.emp_id
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
    global $conDB;

    $summary = [
        'month' => $monthValue,
        'bank_columns' => [],
        'rows' => []
    ];

    if ($monthValue === '') {
        return $summary;
    }

    $monthStart = $monthValue . '-01';
    $monthEnd = date('Y-m-t', strtotime($monthStart));

    // Must mirror the exclusions used by the Payroll Summary Report PDF
    // (get_payroll_report.php / actionPayrollSummaryReportBtn): Hold employees
    // (payment_type = 3) and employees dropped via a long vacation or already
    // settled - otherwise this breakdown table disagrees with the real report.
    $params = [
        ':month_year' => $monthValue,
    ];

    // Vacation dropout rule lives in includes/payroll_vacation_rules.php.
    $dropoutFilter = '';
    $dropoutCond = payrollVacationDropoutCondition($conDB, 'e.emp_id', ':dropout_month_start', ':dropout_month_end');
    if ($dropoutCond !== '') {
        $dropoutFilter = " AND NOT {$dropoutCond}";
        $params[':dropout_month_end'] = $monthEnd;
        $params[':dropout_month_start'] = $monthStart;
    }

    // Paid vacation settlement rule lives in includes/payroll_vacation_rules.php.
    [$settlementCond, $settlementParams] = payrollPaidSettlementCondition('e.emp_id', $monthStart, $monthEnd);
    $settlementFilter = " AND NOT {$settlementCond}";
    $params += $settlementParams;

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
        WHERE p.month_year = :month_year AND e.payment_type <> 3
        {$dropoutFilter}
        {$settlementFilter}
        ORDER BY c.comp_name ASC, p.emp_id ASC");
    $stmt->execute($params);

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
    <link href="assets/css/icons.css" rel="stylesheet" type="text/css" />
    <link href="assets/css/metismenu.min.css" rel="stylesheet" type="text/css" />
    <link href="assets/css/style.css" rel="stylesheet" type="text/css" />
    <link href="assets/css/style_dark.css" rel="stylesheet" type="text/css" />
    <link href="assets/css/smart_request.css?v=<?= @filemtime(__DIR__ . '/assets/css/smart_request.css') ?>" rel="stylesheet" type="text/css" />
    <script src="assets/js/modernizr.min.js"></script>
    <style>
        .detail-item { display: flex; align-items: center; margin-bottom: 1rem; font-size: 1.03em; }
        .detail-item i { color: #4a90e2; margin-right: 15px; width: 20px; text-align: center; }
        .detail-item strong { color: #8a94a6; min-width: 140px; display: inline-block; }
        .detail-item { flex-direction: <?= ($is_rtl ?? false) ? 'row-reverse !important' : 'row !important' ?>; text-align: <?= ($is_rtl ?? false) ? 'right !important' : 'left !important' ?>; }
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

        /* Approve / reject payroll modals (Swal popup sits outside .sr-page - literal colors + app-dark overrides) */
        .swal2-popup.pv-popup { padding: 22px 22px 18px; border-radius: 16px; }
        .swal2-popup.pv-popup .swal2-title { padding: 0; margin: 0 0 14px; text-align: start; }
        .swal2-popup.pv-popup .swal2-html-container { margin: 0; padding: 0; text-align: start; overflow: visible; }
        .swal2-popup.pv-popup .swal2-actions { margin-top: 18px; }
        .swal2-popup.pv-popup .swal2-validation-message { margin: 10px 0 0; border-radius: 8px; }
        .swal2-popup.pv-popup-sm { width: 380px !important; }
        .pv-title { display: flex; align-items: center; gap: 12px; }
        .pv-title-icon { width: 42px; height: 42px; border-radius: 12px; display: inline-flex; align-items: center; justify-content: center; font-size: 22px; flex: 0 0 auto; }
        .pv-title-icon.tone-green { background: #dcfce7; color: #16a34a; }
        .pv-title-icon.tone-red { background: #fee2e2; color: #dc2626; }
        .pv-title-icon.tone-indigo { background: #eef2ff; color: #4f46e5; }

        /* Send payroll report by direct supervisor */
        .ps-stats { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 8px; margin-bottom: 12px; }
        .ps-stat { padding: 8px 12px; border: 1px solid #e5e7eb; border-radius: 10px; background: #fff; }
        .ps-stat span { display: block; font-size: 10.5px; font-weight: 700; text-transform: uppercase; letter-spacing: .04em; color: #64748b; }
        .ps-stat strong { display: block; font-size: 18px; color: #0f172a; font-variant-numeric: tabular-nums; }
        .ps-stat.tone-sky strong { color: #0369a1; }
        .ps-stat.tone-amber strong { color: #b45309; }
        .ps-tools { margin-bottom: 8px; }
        .ps-search { position: relative; }
        .ps-search i { position: absolute; inset-inline-start: 10px; top: 50%; transform: translateY(-50%); color: #94a3b8; font-size: 16px; }
        .ps-search input { width: 100%; height: 38px; padding-inline: 34px 10px; border: 1px solid #e5e7eb; border-radius: 10px; background: #f8fafc; font-size: 13px; color: #0f172a; outline: 0; }
        .ps-search input:focus { background: #fff; border-color: #6366f1; box-shadow: 0 0 0 3px rgba(99, 102, 241, .15); }
        .ps-list { max-height: 320px; overflow-y: auto; display: flex; flex-direction: column; gap: 6px; padding: 2px; }
        .ps-row { position: relative; display: flex; align-items: center; gap: 10px; margin: 0; padding: 9px 10px 9px 12px; border: 1px solid #e5e7eb; border-radius: 10px; background: #fff; cursor: pointer; transition: border-color .15s, background .15s; flex: 0 0 auto; }
        .ps-row:hover { border-color: #c7d2fe; }
        .ps-row.is-selected { border-color: #6366f1; background: #eef2ff; box-shadow: 0 0 0 1px #6366f1 inset; }
        .ps-row.is-disabled { cursor: not-allowed; opacity: .6; background: #f8fafc; }
        .ps-row input { position: absolute; opacity: 0; pointer-events: none; }
        .ps-avatar { width: 34px; height: 34px; border-radius: 50%; flex: 0 0 auto; display: inline-flex; align-items: center; justify-content: center; font-size: 11.5px; font-weight: 700; background: #eef2ff; color: #4f46e5; }
        .ps-row.is-selected .ps-avatar { background: #6366f1; color: #fff; }
        .ps-info { flex: 1; min-width: 0; display: flex; flex-direction: column; }
        .ps-name { font-size: 13.5px; font-weight: 600; color: #0f172a; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .ps-sub { font-size: 12px; color: #64748b; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .ps-count { flex: 0 0 auto; font-size: 12px; font-weight: 700; color: #475569; background: #f1f5f9; padding: 2px 8px; border-radius: 999px; white-space: nowrap; }
        .ps-badge { flex: 0 0 auto; display: inline-flex; align-items: center; gap: 4px; font-size: 11.5px; font-weight: 700; padding: 2px 9px; border-radius: 999px; white-space: nowrap; }
        .ps-badge.tone-sky { background: #f0f9ff; color: #0369a1; }
        .ps-badge.tone-amber { background: #fffbeb; color: #b45309; }
        .ps-badge.tone-slate { background: #f1f5f9; color: #64748b; }
        .ps-preview, .ps-preview-spacer { flex: 0 0 auto; width: 30px; height: 30px; }
        .ps-preview { border: 1px solid #e5e7eb; border-radius: 8px; background: #fff; color: #6366f1; font-size: 16px; display: inline-flex; align-items: center; justify-content: center; cursor: pointer; }
        .ps-preview:hover { background: #6366f1; border-color: #6366f1; color: #fff; }
        .ps-empty { padding: 20px; text-align: center; color: #64748b; font-size: 13px; }
        .ps-hint { display: flex; gap: 6px; align-items: flex-start; margin-top: 10px; padding: 8px 10px; border-radius: 8px; background: #f8fafc; font-size: 12px; color: #64748b; line-height: 1.5; }
        @media (max-width: 575px) { .ps-stats { grid-template-columns: repeat(2, minmax(0, 1fr)); } .ps-count, .ps-sub { display: none; } }
        html.app-dark .ps-stat, html.app-dark .ps-row, html.app-dark .ps-preview { background: #1d2436; border-color: #2a3348; }
        html.app-dark .ps-stat strong, html.app-dark .ps-name { color: #f1f5f9; }
        html.app-dark .ps-row.is-selected { background: #252b4a; border-color: #818cf8; box-shadow: 0 0 0 1px #818cf8 inset; }
        html.app-dark .ps-row.is-disabled, html.app-dark .ps-hint, html.app-dark .ps-search input { background: #161c2a; border-color: #2a3348; color: #d8dee9; }
        html.app-dark .ps-avatar { background: #252b4a; color: #c7d2fe; }
        html.app-dark .ps-count, html.app-dark .ps-badge.tone-slate { background: #252e44; color: #d8dee9; }
        html.app-dark .ps-badge.tone-sky { background: #102c3d; color: #7dd3fc; }
        html.app-dark .ps-badge.tone-amber { background: #3a2e12; color: #fcd34d; }
        html.app-dark .pv-title-icon.tone-indigo { background: #252b4a; color: #c7d2fe; }
        .pv-title-icon.tone-amber { background: #fef3c7; color: #d97706; }
        .swal2-popup.pv-popup-md { width: 460px !important; }
        .pv-result-msg { font-size: 14px; color: #334155; line-height: 1.6; }
        .ps-badge.tone-green { background: #f0fdf4; color: #15803d; }
        .ps-badge.tone-indigo { background: #eef2ff; color: #4338ca; }
        .ps-stat.tone-green strong { color: #15803d; }

        /* Finance verification setup */
        .fv-status { border: 1px solid #e5e7eb; border-radius: 12px; padding: 12px 14px; background: #f8fafc; margin-bottom: 12px; }
        .fv-status-row { display: flex; align-items: center; gap: 12px; flex-wrap: wrap; }
        .fv-status-avatar { width: 38px; height: 38px; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; background: #eef2ff; color: #4f46e5; font-size: 20px; flex: 0 0 auto; }
        .fv-status-main { flex: 1; min-width: 0; display: flex; flex-direction: column; }
        .fv-status-label { font-size: 10.5px; font-weight: 700; text-transform: uppercase; letter-spacing: .04em; color: #64748b; }
        .fv-status-name { font-size: 14px; font-weight: 700; color: #0f172a; }
        .fv-status-sub { font-size: 12px; color: #64748b; }
        .fv-progress-head { display: flex; justify-content: space-between; font-size: 12px; color: #64748b; margin: 10px 0 5px; }
        .fv-progress-head strong { color: #0f172a; }
        .fv-progress { height: 7px; border-radius: 999px; background: #e2e8f0; overflow: hidden; }
        .fv-progress > span { display: block; height: 100%; border-radius: inherit; background: #6366f1; }
        .fv-section { border: 1px solid #e5e7eb; border-radius: 12px; margin-bottom: 12px; background: #fff; }
        .fv-section > summary { display: flex; align-items: center; gap: 8px; padding: 9px 12px; cursor: pointer; font-size: 13px; font-weight: 700; color: #334155; list-style: none; }
        .fv-section > summary::-webkit-details-marker { display: none; }
        .fv-section > summary > i { color: #6366f1; font-size: 16px; }
        .fv-seg { margin-inline-start: auto; display: inline-flex; border: 1px solid #e5e7eb; border-radius: 8px; overflow: hidden; }
        .fv-seg-btn { border: 0; background: #fff; padding: 4px 10px; font-size: 12px; font-weight: 600; color: #64748b; cursor: pointer; }
        .fv-seg-btn + .fv-seg-btn { border-inline-start: 1px solid #e5e7eb; }
        .fv-seg-btn span { margin-inline-start: 3px; padding: 0 6px; border-radius: 999px; background: #f1f5f9; font-size: 11px; }
        .fv-seg-btn.active { background: #6366f1; color: #fff; }
        .fv-seg-btn.active span { background: rgba(255, 255, 255, .25); }
        .fv-hist-panel { max-height: 220px; overflow-y: auto; padding: 4px 14px 10px; border-top: 1px solid #f1f5f9; }
        .fv-hist { display: flex; gap: 10px; padding: 8px 0; border-bottom: 1px dashed #e2e8f0; }
        .fv-hist:last-child { border-bottom: 0; }
        .fv-hist-dot { width: 10px; height: 10px; border-radius: 50%; margin-top: 6px; flex: 0 0 auto; background: #94a3b8; }
        .fv-hist-dot.tone-indigo { background: #6366f1; }
        .fv-hist-dot.tone-amber { background: #f59e0b; }
        .fv-hist-dot.tone-green { background: #22c55e; }
        .fv-hist-body { flex: 1; min-width: 0; }
        .fv-hist-head { display: flex; justify-content: space-between; align-items: center; gap: 8px; }
        .fv-hist-time { font-size: 11.5px; color: #94a3b8; }
        .fv-hist-meta { font-size: 12.5px; color: #475569; margin-top: 3px; }
        .fv-hist-chips { display: flex; flex-wrap: wrap; gap: 4px; margin-top: 5px; }
        .fv-hist-chips span { font-size: 11.5px; font-weight: 600; padding: 2px 8px; border-radius: 999px; background: #f1f5f9; color: #334155; }
        .fv-hist-note { font-size: 12px; color: #64748b; margin-top: 4px; }
        .fv-empty { font-size: 12.5px; color: #64748b; padding: 10px 0; }
        .fv-grid { display: grid; grid-template-columns: minmax(0, 1fr) minmax(0, 1.3fr); gap: 14px; }
        .fv-label { display: flex; align-items: center; gap: 8px; font-size: 12.5px; font-weight: 700; color: #334155; margin-bottom: 8px; }
        .fv-step { width: 20px; height: 20px; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; background: #6366f1; color: #fff; font-size: 11px; }
        .fv-label-tools { margin-inline-start: auto; display: flex; align-items: center; gap: 8px; font-weight: 600; }
        .fv-picked { font-size: 11.5px; padding: 1px 8px; border-radius: 999px; background: #f1f5f9; color: #64748b; }
        .fv-picked.has-picked { background: #eef2ff; color: #4338ca; }
        .fv-link { border: 0; background: none; padding: 0; font-size: 12px; font-weight: 600; color: #6366f1; cursor: pointer; }
        .fv-link:hover { text-decoration: underline; }
        .fv-officers { max-height: 280px; }
        .fv-comp-search { margin-bottom: 8px; }
        .fv-comps { display: grid; grid-template-columns: repeat(auto-fill, minmax(190px, 1fr)); gap: 6px; max-height: 280px; overflow-y: auto; padding: 2px; }
        .fv-comp { position: relative; display: flex; align-items: center; gap: 8px; margin: 0; padding: 8px 10px; border: 1px solid #e5e7eb; border-radius: 9px; background: #fff; cursor: pointer; transition: border-color .15s, background .15s; }
        .fv-comp:hover { border-color: #c7d2fe; }
        .fv-comp input { position: absolute; opacity: 0; pointer-events: none; }
        .fv-comp-check { width: 18px; height: 18px; border-radius: 5px; border: 1.5px solid #cbd5e1; display: inline-flex; align-items: center; justify-content: center; color: transparent; font-size: 13px; flex: 0 0 auto; background: #fff; }
        .fv-comp input:checked ~ .fv-comp-check { background: #6366f1; border-color: #6366f1; color: #fff; }
        .fv-comp:has(input:checked) { border-color: #6366f1; background: #eef2ff; }
        .fv-comp-name { flex: 1; min-width: 0; font-size: 12.5px; font-weight: 600; color: #0f172a; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .fv-comp-count { font-size: 11px; font-weight: 700; color: #64748b; white-space: nowrap; }
        .fv-comp.is-assigned { cursor: not-allowed; background: #fef2f2; border-color: #fecaca; }
        .fv-comp.is-assigned .fv-comp-name, .fv-comp.is-assigned .fv-comp-count { color: #b91c1c; }
        .fv-comp.is-assigned .fv-comp-check { border-color: #fecaca; background: #fee2e2; }
        .fv-confirm { display: flex; align-items: flex-start; gap: 8px; margin: 14px 0 0; padding: 10px 12px; border-radius: 10px; background: #fffbeb; border: 1px solid #fde68a; font-size: 13px; color: #92400e; cursor: pointer; }
        .fv-confirm input { margin-top: 3px; width: 16px; height: 16px; accent-color: #16a34a; flex: 0 0 auto; }
        @media (max-width: 767px) { .fv-grid { grid-template-columns: 1fr; } }

        /* Employees preview */
        .pe-chips { display: flex; flex-wrap: wrap; gap: 6px; margin-bottom: 8px; }
        .pe-chips span { font-size: 12px; padding: 3px 10px; border-radius: 999px; background: #f1f5f9; color: #475569; }
        .pe-chips strong { color: #0f172a; margin-inline-start: 3px; }
        .pe-list { max-height: 380px; overflow-y: auto; border: 1px solid #e5e7eb; border-radius: 12px; background: #fff; }
        .pe-row { display: flex; align-items: center; gap: 10px; padding: 9px 12px; border-bottom: 1px solid #f1f5f9; }
        .pe-row:last-of-type { border-bottom: 0; }
        .pe-row:hover { background: #f8fafc; }
        .pe-company { flex: 0 0 auto; font-size: 12px; font-weight: 600; color: #475569; background: #f1f5f9; padding: 3px 9px; border-radius: 999px; white-space: nowrap; }

        /* Payroll summary report (inside Approve) */
        .pv-report { border: 1px solid #e5e7eb; border-radius: 12px; padding: 12px 14px; margin-bottom: 12px; background: #fff; }
        .pv-report-title { font-size: 13px; font-weight: 700; color: #334155; margin-bottom: 10px; display: flex; align-items: center; gap: 6px; }
        .pv-report-title i { color: #6366f1; font-size: 16px; }
        .pv-report-table { max-height: 340px; overflow: auto; border: 1px solid #e5e7eb; border-radius: 10px; }
        .pv-report-table table { width: 100%; border-collapse: collapse; font-size: 12.5px; }
        .pv-report-table th { position: sticky; top: 0; background: #f8fafc; color: #64748b; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .03em; padding: 8px 10px; border-bottom: 1px solid #e5e7eb; text-align: center; }
        .pv-report-table td { padding: 8px 10px; border-bottom: 1px solid #f1f5f9; color: #334155; text-align: center; }
        .pv-report-table .is-left { text-align: start; }
        .pv-report-table .is-num { text-align: end; font-variant-numeric: tabular-nums; }
        .pv-report-table .is-muted { color: #94a3b8; }
        .pv-report-table .is-strong { font-weight: 600; color: #0f172a; }
        .pv-report-table tbody tr:hover td { background: #f8fafc; }
        .pv-report-table tfoot td { position: sticky; bottom: 0; background: #f0fdf4; color: #15803d; font-weight: 700; border-top: 1px solid #bbf7d0; }

        html.app-dark .pv-result-msg, html.app-dark .fv-hist-meta, html.app-dark .fv-label, html.app-dark .fv-section > summary, html.app-dark .pv-report-title { color: #d8dee9; }
        html.app-dark .fv-status, html.app-dark .fv-section, html.app-dark .fv-comp, html.app-dark .pe-list, html.app-dark .pv-report, html.app-dark .fv-seg-btn { background: #1d2436; border-color: #2a3348; }
        html.app-dark .fv-status-name, html.app-dark .fv-comp-name, html.app-dark .fv-progress-head strong, html.app-dark .pe-chips strong { color: #f1f5f9; }
        html.app-dark .fv-comp:has(input:checked) { background: #252b4a; border-color: #818cf8; }
        html.app-dark .fv-comp.is-assigned { background: #3a1717; border-color: #7a2a2a; }
        html.app-dark .fv-confirm { background: #3a2e12; border-color: #6b5417; color: #fcd34d; }
        html.app-dark .fv-hist-chips span, html.app-dark .pe-chips span, html.app-dark .pe-company, html.app-dark .fv-progress, html.app-dark .fv-picked { background: #252e44; color: #d8dee9; }
        html.app-dark .pe-row, html.app-dark .fv-hist-panel, html.app-dark .fv-hist { border-color: #252e44; }
        html.app-dark .pe-row:hover, html.app-dark .pv-report-table tbody tr:hover td { background: #252e44; }
        html.app-dark .pv-report-table, html.app-dark .pv-report-table th, html.app-dark .pv-report-table td { border-color: #2a3348; }
        html.app-dark .pv-report-table th { background: #161c2a; color: #8b98ad; }
        html.app-dark .pv-report-table td { color: #d8dee9; }
        html.app-dark .pv-report-table tfoot td { background: #12301f; color: #86efac; }
        html.app-dark .ps-badge.tone-green { background: #12301f; color: #86efac; }
        .pv-title-text { display: block; font-size: 18px; font-weight: 700; color: #0f172a; line-height: 1.25; }
        .pv-title-sub { display: block; font-size: 12.5px; font-weight: 500; color: #64748b; margin-top: 2px; }
        .pv-hero { padding: 16px 18px; border-radius: 14px; background: linear-gradient(135deg, #eef2ff, #f0fdf4); border: 1px solid #e0e7ff; margin-bottom: 10px; }
        .pv-hero-label { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .05em; color: #64748b; }
        .pv-hero-amount { font-size: 28px; font-weight: 800; color: #0f172a; font-variant-numeric: tabular-nums; line-height: 1.2; margin: 2px 0 10px; }
        .pv-hero-amount .icon-saudi_riyal { font-size: .7em !important; opacity: .7; }
        .pv-split-bar { height: 7px; border-radius: 999px; background: #fcd34d; overflow: hidden; }
        .pv-split-bar > span { display: block; height: 100%; background: #6366f1; border-radius: inherit; }
        .pv-split-legend { display: flex; flex-wrap: wrap; gap: 6px 18px; margin-top: 8px; font-size: 12.5px; color: #475569; }
        .pv-split-legend strong { color: #0f172a; font-variant-numeric: tabular-nums; margin-inline-start: 4px; }
        .pv-split-legend .icon-saudi_riyal { font-size: .85em !important; opacity: .7; }
        .pv-dot { display: inline-block; width: 8px; height: 8px; border-radius: 50%; margin-inline-end: 4px; }
        .pv-dot.is-bank { background: #6366f1; }
        .pv-dot.is-other { background: #f59e0b; }
        .pv-facts { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 8px; margin-bottom: 14px; }
        .pv-fact { display: flex; align-items: center; gap: 10px; padding: 9px 12px; border: 1px solid #e5e7eb; border-radius: 10px; background: #fff; min-width: 0; }
        .pv-fact > div { min-width: 0; }
        .pv-fact-icon, .pv-fact-avatar { width: 32px; height: 32px; border-radius: 9px; display: inline-flex; align-items: center; justify-content: center; flex: 0 0 auto; background: #f1f5f9; color: #6366f1; font-size: 16px; }
        .pv-fact-avatar { border-radius: 50%; background: #eef2ff; color: #4f46e5; font-size: 11.5px; font-weight: 700; }
        .pv-fact-label { display: block; font-size: 11px; font-weight: 600; color: #64748b; }
        .pv-fact-value { display: block; font-size: 13.5px; font-weight: 700; color: #0f172a; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
        .pv-mono { font-family: "SFMono-Regular", Consolas, monospace; }
        .pv-report-toggle { margin-bottom: 10px; }
        .pv-field label { display: block; font-size: 12.5px; font-weight: 700; color: #334155; margin-bottom: 6px; }
        .pv-field label span { font-weight: 500; color: #94a3b8; }
        .pv-field label span.is-req { color: #dc2626; font-weight: 700; }
        .pv-field textarea { width: 100%; padding: 10px 12px; border: 1px solid #e5e7eb; border-radius: 10px; background: #f8fafc; color: #0f172a; font-size: 13.5px; resize: vertical; outline: 0; transition: border-color .15s, box-shadow .15s; }
        .pv-field textarea:focus { background: #fff; border-color: #6366f1; box-shadow: 0 0 0 3px rgba(99, 102, 241, .15); }
        .pv-field.is-danger textarea:focus { border-color: #dc2626; box-shadow: 0 0 0 3px rgba(220, 38, 38, .12); }
        .pv-loading { display: flex; align-items: center; gap: 14px; padding: 4px 2px; text-align: start; }
        .pv-loading strong { display: block; font-size: 15px; color: #0f172a; }
        .pv-loading span:not(.pv-spinner) { display: block; font-size: 12.5px; color: #64748b; }
        .pv-spinner { width: 32px; height: 32px; border-radius: 50%; border: 3px solid #e0e7ff; border-top-color: #6366f1; animation: pvSpin .8s linear infinite; flex: 0 0 auto; }
        @keyframes pvSpin { to { transform: rotate(360deg); } }
        @media (max-width: 575px) { .pv-facts { grid-template-columns: 1fr; } .pv-hero-amount { font-size: 23px; } }
        html.app-dark .pv-title-text, html.app-dark .pv-hero-amount, html.app-dark .pv-fact-value, html.app-dark .pv-split-legend strong, html.app-dark .pv-loading strong { color: #f1f5f9; }
        html.app-dark .pv-hero { background: linear-gradient(135deg, #252b4a, #12301f); border-color: #2a3348; }
        html.app-dark .pv-split-legend, html.app-dark .pv-field label { color: #d8dee9; }
        html.app-dark .pv-fact { background: #1d2436; border-color: #2a3348; }
        html.app-dark .pv-fact-icon { background: #252e44; color: #a5b4fc; }
        html.app-dark .pv-fact-avatar { background: #252b4a; color: #c7d2fe; }
        html.app-dark .pv-field textarea { background: #161c2a; border-color: #2a3348; color: #f1f5f9; }
        html.app-dark .pv-title-icon.tone-green { background: #12301f; color: #86efac; }
        html.app-dark .pv-title-icon.tone-red { background: #3a1717; color: #fca5a5; }
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
        <?php require_once __DIR__ . '/includes/sr_list_helpers.php'; ?>
        <div class="content sr-page">
            <div class="container-fluid">

                <div class="sr-head">
                    <div>
                        <h1><?= __('payroll_approvals', 'Payroll Approvals') ?></h1>
                        <p><?= str_replace('{0}', (string)$totalItems, __('showing_requests')) ?></p>
                    </div>
                </div>

                <?= sr_status_tabs($allStatuses, $currentFilter, $totalItems, $sr_status_counts) ?>
                <div class="sr-card">
                    <?= sr_list_toolbar($searchTerm, (!empty($searchTerm) || $currentFilter !== 'my_pending') ? 'resetFilters(' . (int)$perpage . ')' : '') ?>

                    <?php if (!empty($requests)): ?>
                        <div class="sr-table-wrap sr-list-wrap">
                            <table class="table sr-table sr-list" style="width: 100%;">
                                <thead>
                                    <tr>
                                        <th><?= __('payroll_month') ?></th>
                                        <th><?= __('employees', 'Employees') ?></th>
                                        <th><?= __('total_net', 'Total Net') ?></th>
                                        <th><?= __('progress', 'Progress') ?></th>
                                        <th><?= __('status') ?></th>
                                        <th><?= __('submitted', 'Submitted') ?></th>
                                        <th class="text-right"><?= __('actions') ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($requests as $request): ?>
                                        <?php
                                        $approvalStatus = $request['approval_status'] ?? null;
                                        $isPendingWithMe = ($approvalStatus === 'pending_approval' && !empty($request['current_approver_id']) && ((string)$request['current_approver_id'] === (string)$empid || ($delegatedFromEmpId !== null && (string)$request['current_approver_id'] === (string)$delegatedFromEmpId)));
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

                                        $statusTone = ['warning' => 'amber', 'success' => 'green', 'danger' => 'red', 'primary' => 'indigo', 'dark' => 'slate', 'secondary' => 'slate'][$statusClass] ?? 'slate';
                                        $invJs = htmlspecialchars((string)($request['request_inv_no'] ?? ''), ENT_QUOTES);
                                        $monthJs = htmlspecialchars((string)$request['payroll_month'], ENT_QUOTES);
                                        $empCount = (int)($request['checklist_employee_count'] ?? $request['employee_count'] ?? 0);
                                        $totalNet = (float)($request['checklist_total_net_salary'] ?? $request['total_net_salary'] ?? 0);
                                        $bankNet = (float)($request['checklist_bank_total_net_salary'] ?? $request['bank_total_net_salary'] ?? 0);
                                        $checklistUrl = 'payroll_checklist_report.php?month=' . urlencode($request['payroll_month']) . (!empty($request['request_inv_no']) ? '&request_inv_no=' . urlencode($request['request_inv_no']) : '');
                                        $approveJs = "approvePayrollRequest('" . $invJs . "', '" . $monthJs . "', " . $empCount . ", " . $totalNet . ", '" . htmlspecialchars(getDisplayName(parseName($request['requester_name'] ?? '')), ENT_QUOTES) . "', " . $bankNet . ")";
                                        $rejectJs = "rejectPayrollRequest('" . $invJs . "', '" . $monthJs . "', " . $empCount . ", " . $totalNet . ", '" . htmlspecialchars(getDisplayName($request['requester_name'] ?? ''), ENT_QUOTES) . "', " . $bankNet . ")";
                                        $monthTs = strtotime((string)$request['payroll_month'] . (strlen((string)$request['payroll_month']) === 7 ? '-01' : ''));
                                        ?>
                                        <tr>
                                            <td>
                                                <div class="sr-person">
                                                    <span class="sr-avatar" style="border-radius: 10px;"><i class="mdi mdi-calendar-check" style="font-size: 17px;"></i></span>
                                                    <div style="min-width: 0;">
                                                        <span class="sr-cell-title"><?= $monthTs ? date('F Y', $monthTs) : sr_h($request['payroll_month']) ?></span>
                                                        <span class="sr-cell-sub sr-mono"><?= !empty($request['request_inv_no']) ? sr_h($request['request_inv_no']) : sr_h($request['payroll_month']) ?></span>
                                                    </div>
                                                </div>
                                            </td>
                                            <td><span class="sr-chip"><i class="mdi mdi-account-multiple"></i><?= $empCount ?></span></td>
                                            <td class="sr-nowrap"><span class="sr-money"><i class="icon-saudi_riyal"></i> <?= number_format($totalNet, 2) ?></span></td>
                                            <td>
                                                <div class="sr-flags">
                                                    <?php if ($isHrPayrollUser): ?>
                                                        <span class="sr-pill sr-pill-xs tone-<?= ($monthEmployeeCount > 0 && $hrCheckedCount >= $monthEmployeeCount) ? 'green' : 'sky' ?>" title="<?= __('checked_by_me', 'Checked By Me') ?>"><i class="mdi mdi-account-check"></i><?= __('checked_by_me', 'Checked By Me') ?>: <?= $hrCheckedCount ?> / <?= $monthEmployeeCount ?></span>
                                                    <?php endif; ?>
                                                    <?php if ($isHeadOfficeFinancePending && $financeVerificationConfirmed && !$financeOfficerChecklistCompleted): ?>
                                                        <span class="sr-pill sr-pill-xs tone-amber"><i class="mdi mdi-timer-sand"></i><?= __('finance_verification_status', 'Finance Verification') ?>: <?= (int)($financeOfficerChecklistCompletion['checked_employees'] ?? 0) ?> / <?= (int)($financeOfficerChecklistCompletion['total_employees'] ?? 0) ?></span>
                                                    <?php endif; ?>
                                                    <?php if ($isHeadOfficeFinancePending && $monthCompaniesCount > 0): ?>
                                                        <span class="sr-pill sr-pill-xs tone-<?= $allCompaniesAssigned ? 'green' : 'amber' ?>"><i class="mdi mdi-domain"></i><?= __('assigned_companies', 'Assigned Companies') ?>: <?= $assignedCompaniesCount ?> / <?= $monthCompaniesCount ?></span>
                                                    <?php endif; ?>
                                                    <?php if ($isHeadOfficeFinancePending && $financeOfficerChecklistCompleted && !$financeOfficerApproved): ?>
                                                        <span class="sr-pill sr-pill-xs tone-amber"><i class="mdi mdi-timer-sand"></i><?= __('finance_officer_approval', 'Finance Officer Approval') ?>: <?= __('pending', 'Pending') ?></span>
                                                    <?php endif; ?>
                                                    <?php if ($isHeadOfficeFinancePending && $financeVerificationConfirmed && !$financeManagerChecklistCompleted): ?>
                                                        <span class="sr-pill sr-pill-xs tone-sky"><i class="mdi mdi-format-list-checks"></i><?= __('manager_verification_status', 'Manager Verification') ?>: <?= (int)($financeManagerChecklistCompletion['checked_employees'] ?? 0) ?> / <?= (int)($financeManagerChecklistCompletion['total_employees'] ?? 0) ?></span>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                            <td>
                                                <?= sr_pill($statusTone, strip_tags(html_entity_decode($statusText, ENT_QUOTES))) ?>
                                                <?php if (!empty($request['requester_name'])): ?>
                                                    <span class="sr-cell-sub"><i class="mdi mdi-account"></i> <?= sr_h(getDisplayName($request['requester_name'])) ?></span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?= sr_time_cell($request['approval_created_at'] ?? '') ?></td>
                                            <td class="text-right">
                                                <div class="sr-actions">
                                                    <?php if ($canApproveNow): ?>
                                                        <a href="javascript:void(0);" class="sr-open-btn" onclick="<?= $approveJs ?>"><i class="mdi mdi-check"></i> <?= __('approve') ?></a>
                                                    <?php elseif ($showFinanceVerificationSetupAction): ?>
                                                        <a href="javascript:void(0);" class="sr-open-btn" onclick="openFinanceVerificationSetupModal('<?= $invJs ?>', '<?= $monthJs ?>')"><i class="mdi mdi-format-list-checks"></i> <?= __('finance_verification_setup', 'Finance Verification Setup') ?></a>
                                                    <?php else: ?>
                                                        <a href="<?= sr_h($checklistUrl) ?>" target="_blank" class="sr-open-btn"><i class="mdi mdi-clipboard-check"></i> <?= __('payroll_check_list', 'Payroll Check List') ?></a>
                                                    <?php endif; ?>
                                                    <div class="btn-group dropdown">
                                                        <a href="javascript:void(0);" class="sr-more-btn dropdown-toggle arrow-none" data-toggle="dropdown" data-boundary="viewport" aria-haspopup="true" aria-expanded="false"><i class="mdi mdi-dots-vertical"></i></a>
                                                        <div class="dropdown-menu dropdown-menu-right">
                                                            <?php if ($canApproveNow || $showFinanceVerificationSetupAction): ?>
                                                                <a class="dropdown-item" href="<?= sr_h($checklistUrl) ?>" target="_blank"><i class="mdi mdi-clipboard-check"></i><?= __('payroll_check_list', 'Payroll Check List') ?></a>
                                                            <?php endif; ?>
                                                            <?php if (!empty($request['request_inv_no'])): ?>
                                                                <a class="dropdown-item" href="payroll_status_history.php?inv_no=<?= urlencode($request['request_inv_no']) ?>" target="_blank"><i class="mdi mdi-history"></i><?= __('history') ?></a>
                                                            <?php else: ?>
                                                                <a class="dropdown-item" href="generate_payroll.php"><i class="mdi mdi-send text-warning"></i><?= __('start_approval', 'Start Approval') ?></a>
                                                            <?php endif; ?>
                                                            <?php if ($showFinanceVerificationSetupAction): ?>
                                                                <div class="dropdown-divider"></div>
                                                                <a class="dropdown-item" href="javascript:void(0);" onclick="openFinanceVerificationSetupModal('<?= $invJs ?>', '<?= $monthJs ?>')"><i class="mdi mdi-format-list-checks text-primary"></i><?= __('finance_verification_setup', 'Finance Verification Setup') ?> (<?= $assignedCompaniesCount ?> / <?= $monthCompaniesCount ?>)</a>
                                                            <?php endif; ?>
                                                            <?php if ($canApproveNow): ?>
                                                                <div class="dropdown-divider"></div>
                                                                <a class="dropdown-item" href="javascript:void(0);" onclick="<?= $rejectJs ?>"><i class="mdi mdi-close text-danger"></i><?= __('reject') ?></a>
                                                            <?php endif; ?>
                                                            <?php if ($canSendCompanyPayrollReport): ?>
                                                                <div class="dropdown-divider"></div>
                                                                <a class="dropdown-item" href="javascript:void(0);" onclick="openCompanyPayrollReportModal('<?= $invJs ?>', '<?= $monthJs ?>')"><i class="mdi mdi-email text-primary"></i><?= __('send_supervisor_payroll_report', 'Send Payroll Report by Direct Supervisor') ?></a>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <?= sr_empty_state(__('no_records_found'), __('no_data_available_in_table')) ?>
                    <?php endif; ?>

                    <div class="sr-pager">
                        <?= generate_pagination_controls($currentPage, $totalPages, $totalItems, $itemsPerPage, $limitOptions, $showAll, ['status' => $currentFilter, 'search' => $searchTerm], $unfilteredTotalItems) ?>
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
        <?= sr_list_js() ?>
<script>

const payrollApprovalBankReports = <?= json_encode($approvalBankReportByRequest, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
const payrollApprovalUseWideModal = <?= $approvalModalUseWideLayout ? 'true' : 'false' ?>;
const payrollApprovalCanViewBankReport = payrollApprovalUseWideModal;

function escPv(v) {
    return String(v == null ? '' : v).replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
}

function pvMoney(v) {
    return Number(v || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

// "2026-10" -> "October 2026"
function pvMonthLabel(ym) {
    const m = /^(\d{4})-(\d{2})$/.exec(String(ym || ''));
    if (!m) return ym || '';
    return new Date(Number(m[1]), Number(m[2]) - 1, 1).toLocaleString('en-US', { month: 'long', year: 'numeric' });
}

function pvTitleHtml(icon, tone, title, sub) {
    return `<span class="pv-title"><span class="pv-title-icon tone-${tone}"><i class="${icon}"></i></span><span><span class="pv-title-text">${title}</span>${sub ? `<span class="pv-title-sub">${sub}</span>` : ''}</span></span>`;
}

function pvLoading(text, title) {
    Swal.fire({
        title: '',
        html: `<div class="pv-loading"><span class="pv-spinner"></span><div><strong>${title || '<?= __('processing') ?>'}</strong><span>${text}</span></div></div>`,
        customClass: { popup: 'pv-popup pv-popup-sm' },
        allowOutsideClick: false,
        allowEscapeKey: false,
        showConfirmButton: false
    });
}

// Result popup in the same style: type = success | warning | error
function pvResult(type, title, message) {
    const meta = {
        success: { icon: 'mdi mdi-check-circle', tone: 'green', color: '#16a34a' },
        warning: { icon: 'mdi mdi-alert', tone: 'amber', color: '#d97706' },
        error: { icon: 'mdi mdi-close-circle', tone: 'red', color: '#dc2626' }
    }[type] || { icon: 'mdi mdi-information', tone: 'indigo', color: '#6366f1' };
    return Swal.fire({
        title: pvTitleHtml(meta.icon, meta.tone, title, ''),
        html: `<div class="pv-result-msg">${escPv(message || '').replace(/\n/g, '<br>')}</div>`,
        customClass: { popup: 'pv-popup pv-popup-md' },
        confirmButtonText: '<?= __('ok', 'OK') ?>',
        confirmButtonColor: meta.color,
        allowOutsideClick: false
    });
}

function buildPayrollRequestDetailsHtml(requestInvNo, payrollMonth, employeeCount, totalNet, requesterName, bankTotalNet) {
    const net = Number(totalNet || 0);
    const bank = Number(bankTotalNet || 0);
    const other = Math.max(0, net - bank);
    const bankPct = net > 0 ? Math.min(100, Math.round(bank / net * 100)) : 0;
    const name = String(requesterName || '').trim();
    const initials = name ? name.split(/\s+/).slice(0, 2).map(w => w.charAt(0)).join('').toUpperCase() : '?';

    return `
        <div class="pv-hero">
            <div class="pv-hero-label"><?= __('total_net', 'Total Net') ?></div>
            <div class="pv-hero-amount"><i class="icon-saudi_riyal"></i> ${pvMoney(net)}</div>
            <div class="pv-split">
                <div class="pv-split-bar"><span style="width:${bankPct}%"></span></div>
                <div class="pv-split-legend">
                    <span><i class="pv-dot is-bank"></i> ${__('bank_wps_payment', 'Bank WPS Payment')} <strong><i class="icon-saudi_riyal"></i> ${pvMoney(bank)}</strong></span>
                    ${other > 0 ? `<span><i class="pv-dot is-other"></i> ${__('cash_other', 'Cash / other')} <strong><i class="icon-saudi_riyal"></i> ${pvMoney(other)}</strong></span>` : ''}
                </div>
            </div>
        </div>
        <div class="pv-facts">
            <div class="pv-fact"><span class="pv-fact-icon"><i class="mdi mdi-pound"></i></span><div><span class="pv-fact-label">${__('request_id') || 'Request ID'}</span><span class="pv-fact-value pv-mono">${escPv(requestInvNo || 'N/A')}</span></div></div>
            <div class="pv-fact"><span class="pv-fact-icon"><i class="mdi mdi-calendar"></i></span><div><span class="pv-fact-label">${__('payroll_month') || 'Payroll Month'}</span><span class="pv-fact-value">${escPv(pvMonthLabel(payrollMonth) || 'N/A')}</span></div></div>
            <div class="pv-fact"><span class="pv-fact-icon"><i class="mdi mdi-account-multiple"></i></span><div><span class="pv-fact-label">${__('employees', 'Employees')}</span><span class="pv-fact-value">${Number(employeeCount || 0).toLocaleString('en-US')}</span></div></div>
            <div class="pv-fact"><span class="pv-fact-avatar">${escPv(initials)}</span><div><span class="pv-fact-label">${__('requested_by', 'Requested By')}</span><span class="pv-fact-value">${escPv(name || '<?= __('not_available', 'N/A') ?>')}</span></div></div>
        </div>
    `;
}

function buildPayrollApprovalBankReportHtml(requestInvNo, payrollMonth) {
    const report = payrollApprovalBankReports[String(requestInvNo || '').trim()] || null;
    const rows = Array.isArray(report && report.rows) ? report.rows : [];
    const bankColumns = Array.isArray(report && report.bank_columns) ? report.bank_columns : [];

    if (rows.length === 0) {
        return `<div class="pv-report"><div class="pv-report-title"><i class="mdi mdi-table-large"></i> ${__('payroll_summary_report', 'Payroll Summary Report')}</div><div class="ps-empty">No payroll summary data available for ${escPv(pvMonthLabel(payrollMonth) || 'selected month')}.</div></div>`;
    }

    const formatAmount = (value) => Number(value || 0).toLocaleString('en-US', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    });
    const formatCount = (value) => Number(value || 0).toLocaleString('en-US');

    const totalRow = rows.find((row) => String(row.branch || '').toUpperCase() === 'TOTAL') || null;
    const companyRows = rows.filter((row) => String(row.branch || '').toUpperCase() !== 'TOTAL');

    const rowTotalBank = (row) => bankColumns.reduce((sum, col) => sum + Number(row[col] || 0), 0);

    const totalCompanies = companyRows.length;
    const grandEmployees = totalRow ? Number(totalRow.no_of_emp || 0) : companyRows.reduce((sum, r) => sum + Number(r.no_of_emp || 0), 0);
    const grandNet = totalRow ? Number(totalRow.salary_register || 0) : companyRows.reduce((sum, r) => sum + Number(r.salary_register || 0), 0);
    const grandBank = totalRow ? rowTotalBank(totalRow) : companyRows.reduce((sum, r) => sum + rowTotalBank(r), 0);
    const grandCash = totalRow ? Number(totalRow.cash_salary || 0) : companyRows.reduce((sum, r) => sum + Number(r.cash_salary || 0), 0);

    const cardsHtml = `
        <div class="ps-stats pv-report-stats">
            <div class="ps-stat"><span>${__('total_companies', 'Total Companies')}</span><strong>${formatCount(totalCompanies)}</strong></div>
            <div class="ps-stat"><span>${__('employees', 'Employees')}</span><strong>${formatCount(grandEmployees)}</strong></div>
            <div class="ps-stat tone-sky"><span>${__('total_bank', 'Total Bank')}</span><strong>${formatAmount(grandBank)}</strong></div>
            <div class="ps-stat tone-green"><span>${__('grand_total_label', 'Grand Total')} (SAR)</span><strong>${formatAmount(grandNet)}</strong></div>
        </div>
    `;

    const bodyRows = companyRows.map((row, index) => `
        <tr>
            <td class="is-muted">${index + 1}</td>
            <td class="is-strong is-left">${escPv(String(row.branch || '-'))}</td>
            <td class="is-num">${formatCount(row.no_of_emp)}</td>
            <td class="is-num">${formatAmount(rowTotalBank(row))}</td>
            <td class="is-num">${formatAmount(row.cash_salary)}</td>
            <td class="is-num is-strong">${formatAmount(row.salary_register)}</td>
        </tr>
    `).join('');

    return `
        <div class="pv-report">
            <div class="pv-report-title"><i class="mdi mdi-table-large"></i> ${__('payroll_summary_report', 'Payroll Summary Report')}</div>
            ${cardsHtml}
            <div class="pv-report-table">
                <table>
                    <thead>
                        <tr>
                            <th>#</th>
                            <th class="is-left">${__('all_companies_option', 'All Companies')}</th>
                            <th class="is-num">${__('employees', 'Employees')}</th>
                            <th class="is-num">${__('total_bank', 'Total Bank')}</th>
                            <th class="is-num">${__('total_cash', 'Total Cash')}</th>
                            <th class="is-num">${__('total_net', 'Total Net')}</th>
                        </tr>
                    </thead>
                    <tbody>${bodyRows}</tbody>
                    <tfoot>
                        <tr>
                            <td></td>
                            <td class="is-left">${__('total', 'Total')}</td>
                            <td class="is-num">${formatCount(grandEmployees)}</td>
                            <td class="is-num">${formatAmount(grandBank)}</td>
                            <td class="is-num">${formatAmount(grandCash)}</td>
                            <td class="is-num">${formatAmount(grandNet)}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    `;
}

function buildPayrollApprovalBankReportToggleHtml(requestInvNo, payrollMonth) {
    if (!payrollApprovalCanViewBankReport) {
        return '';
    }

    return `
        <div class="pv-report-toggle">
            <button type="button" id="togglePayrollBankReportBtn" class="sr-btn sr-btn-sm">
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
        title: pvTitleHtml('mdi mdi-check-circle', 'green', '<?= __('approve') ?> Payroll', escPv(pvMonthLabel(payrollMonth))),
        ...(payrollApprovalUseWideModal ? { width: '95%' } : { width: '620px' }),
        customClass: { popup: 'pv-popup' },
        html: `
            ${buildPayrollRequestDetailsHtml(requestInvNo, payrollMonth, employeeCount, totalNet, requesterName, bankTotalNet)}
            ${buildPayrollApprovalBankReportToggleHtml(requestInvNo, payrollMonth)}
            <div class="pv-field">
                <label for="payrollApprovalComment"><?= __('approval_comment', 'Approval Comment') ?> <span><?= __('optional', 'Optional') ?></span></label>
                <textarea id="payrollApprovalComment" rows="3" placeholder="<?= __('add_comments', 'Add comments') ?>"></textarea>
            </div>
        `,
        showCancelButton: true,
        confirmButtonText: '<i class="fa fa-check"></i> <?= __('approve') ?>',
        cancelButtonText: '<?= __('cancel') ?>',
        confirmButtonColor: '#16a34a',
        cancelButtonColor: '#6c757d',
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
    pvLoading('<?= __('approving_and_notifying', 'Approving and sending email notification...') ?>');

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
            await pvResult(resultIcon, resultTitle, data.message || 'Success');
            location.reload();
            return;
        }

        pvResult('error', '<?= __('error') ?>', data.message || 'Failed to approve request');
    } catch (error) {
        Swal.close();
        pvResult('error', '<?= __('error') ?>', error.message || 'Failed to approve request');
    }
}

async function rejectPayrollRequest(requestInvNo, payrollMonth, employeeCount, totalNet, requesterName, bankTotalNet) {
    const result = await Swal.fire({
        title: pvTitleHtml('mdi mdi-close-octagon', 'red', '<?= __('confirm_rejection', 'Confirm Rejection') ?>', escPv(pvMonthLabel(payrollMonth))),
        width: '620px',
        customClass: { popup: 'pv-popup' },
        html: `
            ${buildPayrollRequestDetailsHtml(requestInvNo, payrollMonth, employeeCount, totalNet, requesterName, bankTotalNet)}
            <div class="pv-field is-danger">
                <label for="payrollRejectReason"><?= __('provide_rejection_reason', 'Provide Rejection Reason') ?> <span class="is-req">*</span></label>
                <textarea id="payrollRejectReason" rows="3" placeholder="<?= __('enter_reason_here', 'Enter reason here') ?>"></textarea>
            </div>
        `,
        showCancelButton: true,
        confirmButtonText: '<i class="fa fa-times"></i> <?= __('submit_rejection', 'Submit Rejection') ?>',
        cancelButtonText: '<?= __('cancel') ?>',
        confirmButtonColor: '#dc2626',
        cancelButtonColor: '#6c757d',
        allowOutsideClick: false,
        preConfirm: () => {
            const value = ((document.getElementById('payrollRejectReason') || {}).value || '').trim();
            if (!value) {
                Swal.showValidationMessage('<?= __('rejection_reason_required', 'Rejection reason is required') ?>');
                return false;
            }
            return value;
        }
    });

    if (!result.isConfirmed) {
        return;
    }

    // Show processing modal and keep it visible until rejection email handling completes
    pvLoading('<?= __('rejecting_and_notifying', 'Rejecting and sending email notification...') ?>');

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
            await pvResult(resultIcon, resultTitle, data.message || 'Success');
            location.reload();
            return;
        }

        pvResult('error', '<?= __('error') ?>', data.message || 'Failed to reject request');
    } catch (error) {
        Swal.close();
        pvResult('error', '<?= __('error') ?>', error.message || 'Failed to reject request');
    }
}

async function openFinanceVerificationSetupModal(requestInvNo, payrollMonth) {
    try {
        const loadPayload = new URLSearchParams();
        loadPayload.append('action', 'get_finance_verification_setup');
        loadPayload.append('request_inv_no', requestInvNo);
        loadPayload.append('month', payrollMonth);

        pvLoading('<?= __('loading_finance_setup', 'Loading finance officers and companies...') ?>', '<?= __('loading', 'Loading') ?>');

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
        const assignedPct = totalCompanyCount ? Math.round(assignedCount / totalCompanyCount * 100) : 0;
        const currentStatusHtml = assignedCount > 0
            ? `
                <div class="fv-status">
                    <div class="fv-status-row">
                        <span class="fv-status-avatar"><i class="mdi mdi-account-circle"></i></span>
                        <div class="fv-status-main">
                            <span class="fv-status-label"><?= __('sent_to_finance_officer', 'Already Sent To Finance Officer') ?></span>
                            <span class="fv-status-name">${escPv(sentOfficerName || '<?= __('not_available', 'N/A') ?>')}</span>
                            <span class="fv-status-sub"><i class="mdi mdi-clock"></i> <?= __('sent_at', 'Sent At') ?> ${escPv(sentAtText)}</span>
                        </div>
                        ${officerApproved
                            ? `<span class="ps-badge tone-green"><i class="mdi mdi-check-circle"></i> <?= __('approved', 'Approved') ?> · ${escPv(officerApprovedAtText)}</span>`
                            : `<span class="ps-badge tone-amber"><i class="mdi mdi-timer-sand"></i> <?= __('pending', 'Pending') ?></span>`}
                    </div>
                    <div class="fv-progress-head"><span><?= __('assigned_companies', 'Assigned Companies') ?></span><strong>${assignedCount} / ${totalCompanyCount}</strong></div>
                    <div class="fv-progress"><span style="width:${assignedPct}%"></span></div>
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
        const actionTypeToneMap = {
            setup_assigned: 'indigo',
            setup_updated: 'amber',
            officer_approved: 'green'
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
                return `<div class="fv-empty">${emptyText}</div>`;
            }

            return items.map((item) => {
                const actionType = String(item.action_type || '').trim();
                const actionLabel = actionTypeLabelMap[actionType] || actionType || '<?= __('update', 'Update') ?>';
                const actionTone = actionTypeToneMap[actionType] || 'slate';
                const createdBy = String(item.created_by_name || item.created_by || '<?= __('system', 'System') ?>');
                const createdAt = String(item.created_at || '');
                const officerName = String(item.finance_officer_name || '').trim();
                const itemCompanyIds = getItemCompanyIds(item);
                const itemCompanyNames = itemCompanyIds.map((id) => companyNameById[id] || id);
                const note = String(item.action_note || '').trim();

                return `
                    <div class="fv-hist">
                        <span class="fv-hist-dot tone-${actionTone}"></span>
                        <div class="fv-hist-body">
                            <div class="fv-hist-head">
                                <span class="ps-badge tone-${actionTone}">${escPv(actionLabel)}</span>
                                <span class="fv-hist-time">${escPv(createdAt || '-')}</span>
                            </div>
                            <div class="fv-hist-meta"><i class="mdi mdi-account"></i> ${escPv(createdBy)}${officerName ? ` <i class="mdi mdi-arrow-right"></i> <strong>${escPv(officerName)}</strong>` : ''}</div>
                            ${itemCompanyNames.length ? `<div class="fv-hist-chips">${itemCompanyNames.map((n) => `<span>${escPv(n)}</span>`).join('')}</div>` : ''}
                            ${note ? `<div class="fv-hist-note">${escPv(note)}</div>` : ''}
                        </div>
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

        const hasHistory = assignedHistoryItemsUnique.length > 0 || approvedHistoryItems.length > 0;
        const financeHistoryTabsHtml = hasHistory ? `
            <details class="fv-section">
                <summary><i class="mdi mdi-history"></i> <?= __('history', 'History') ?>
                    <span class="fv-seg" role="tablist">
                        <button type="button" class="fv-seg-btn active" data-target="assigned"><?= __('assigned', 'Assigned') ?> <span>${Math.max(assignedHistoryItems.length, assignedCompanyDetails.length)}</span></button>
                        <button type="button" class="fv-seg-btn" data-target="approved"><?= __('approved', 'Approved') ?> <span>${approvedHistoryItems.length}</span></button>
                    </span>
                </summary>
                <div class="fv-hist-panel" data-panel="assigned">${buildHistoryCards(assignedHistoryItemsUnique, '<?= __('no_assignment_history_found', 'No assignment history found yet.') ?>')}</div>
                <div class="fv-hist-panel" data-panel="approved" style="display:none;">${buildHistoryCards(approvedHistoryItems, '<?= __('no_approval_history_found', 'No approval history found yet.') ?>')}</div>
            </details>
        ` : '';

        // Officer picker: radio cards; an officer already assigned for this month is locked
        let firstOfficerPicked = false;
        const officerCardsHtml = officers.map((officer) => {
            const officerEmpId = String(officer.emp_id || '').trim();
            const isLocked = assignedOfficerEmpIds.has(officerEmpId)
                || (assignedOfficerEmpId !== '' && officerEmpId === assignedOfficerEmpId);
            const checked = !isLocked && officerEmpId !== '' && !firstOfficerPicked;
            if (checked) firstOfficerPicked = true;
            const name = String(officer.name || officer.emp_id || 'N/A');
            const initials = name.split(/\s+/).slice(0, 2).map(w => w.charAt(0)).join('').toUpperCase();
            const badge = isLocked
                ? `<span class="ps-badge tone-slate"><i class="mdi mdi-lock"></i> ${officerApproved ? '<?= __('already_assigned_approved', 'Already Assigned (Approved)') ?>' : '<?= __('already_assigned', 'Already Assigned') ?>'}</span>`
                : '';
            return `<label class="ps-row${isLocked ? ' is-disabled' : ''}${checked ? ' is-selected' : ''}">
                    <input type="radio" name="fvOfficer" value="${escPv(officerEmpId)}"${isLocked ? ' disabled' : ''}${checked ? ' checked' : ''}>
                    <span class="ps-avatar">${escPv(initials)}</span>
                    <span class="ps-info"><span class="ps-name">${escPv(name)}</span><span class="ps-sub"><span class="pv-mono">#${escPv(officerEmpId)}</span></span></span>
                    ${badge}
                </label>`;
        }).join('');

        // Company picker: checkbox chips; companies already assigned are locked
        const companyChipsHtml = companies.map((company) => {
            const companyId = String(company.comp_id || '').trim();
            const isAssigned = existingCompanyIds.has(companyId);
            return `<label class="fv-comp${isAssigned ? ' is-assigned' : ''}" data-search="${escPv(String(company.comp_name || '').toLowerCase())}">
                    <input type="checkbox" name="fvCompany" value="${escPv(companyId)}"${isAssigned ? ' disabled' : ''}>
                    <span class="fv-comp-check"><i class="mdi mdi-check"></i></span>
                    <span class="fv-comp-name">${escPv(company.comp_name || 'N/A')}</span>
                    <span class="fv-comp-count">${isAssigned ? '<i class="mdi mdi-lock"></i> <?= __('assigned', 'Assigned') ?>' : `<i class="mdi mdi-account-multiple"></i> ${Number(company.employee_count || 0)}`}</span>
                </label>`;
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
        const availableCompanyCount = companies.length - assignedCount;

        const modalResult = await Swal.fire({
            title: pvTitleHtml('mdi mdi-shield-outline', 'indigo', '<?= __('finance_verification_setup', 'Finance Verification Setup') ?>', escPv(pvMonthLabel(payrollMonth)) + ' · <span class="pv-mono">' + escPv(requestInvNo) + '</span>'),
            width: '860px',
            customClass: { popup: 'pv-popup' },
            html: `
                ${currentStatusHtml}
                ${financeHistoryTabsHtml}
                <div class="fv-grid">
                    <div>
                        <div class="fv-label"><span class="fv-step">1</span> <?= __('finance_officer', 'Finance Officer') ?></div>
                        <div class="ps-list fv-officers">${officerCardsHtml}</div>
                    </div>
                    <div>
                        <div class="fv-label"><span class="fv-step">2</span> <?= __('company', 'Company') ?>
                            <span class="fv-label-tools">
                                <span class="fv-picked" id="fvPicked">0 / ${availableCompanyCount}</span>
                                <button type="button" class="fv-link" id="fvSelectAll"><?= __('select_all', 'Select all') ?></button>
                                <button type="button" class="fv-link" id="fvClearAll"><?= __('clear', 'Clear') ?></button>
                            </span>
                        </div>
                        ${companies.length > 8 ? `<div class="ps-search fv-comp-search"><i class="mdi mdi-magnify"></i><input type="search" id="fvCompSearch" placeholder="<?= __('search', 'Search') ?>..." autocomplete="off"></div>` : ''}
                        <div class="fv-comps">${companyChipsHtml}</div>
                    </div>
                </div>
                <label class="fv-confirm">
                    <input type="checkbox" id="verificationConfirmCheck">
                    <span><?= __('confirm_selected_companies_for_verification', 'I confirm selected companies for finance verification.') ?></span>
                </label>
            `,
            showCancelButton: true,
            confirmButtonColor: '#16a34a',
            cancelButtonColor: '#6c757d',
            confirmButtonText: '<i class="mdi mdi-check"></i> <?= __('confirm', 'Confirm') ?>',
            cancelButtonText: '<?= __('cancel', 'Cancel') ?>',
            allowOutsideClick: false,
            didOpen: (popup) => {
                popup.querySelectorAll('input[name="fvOfficer"]').forEach((radio) => {
                    radio.addEventListener('change', () => {
                        popup.querySelectorAll('.fv-officers .ps-row').forEach((row) => row.classList.toggle('is-selected', row.contains(radio) && radio.checked));
                    });
                });

                const pickedEl = popup.querySelector('#fvPicked');
                const syncPicked = () => {
                    const n = popup.querySelectorAll('input[name="fvCompany"]:checked').length;
                    if (pickedEl) {
                        pickedEl.textContent = `${n} / ${availableCompanyCount}`;
                        pickedEl.classList.toggle('has-picked', n > 0);
                    }
                };
                popup.querySelectorAll('input[name="fvCompany"]').forEach((cb) => cb.addEventListener('change', syncPicked));
                const setAllVisible = (on) => {
                    popup.querySelectorAll('.fv-comp').forEach((chip) => {
                        const cb = chip.querySelector('input');
                        if (!cb.disabled && chip.style.display !== 'none') cb.checked = on;
                    });
                    syncPicked();
                };
                const selAll = popup.querySelector('#fvSelectAll');
                const clrAll = popup.querySelector('#fvClearAll');
                if (selAll) selAll.addEventListener('click', () => setAllVisible(true));
                if (clrAll) clrAll.addEventListener('click', () => setAllVisible(false));

                const compSearch = popup.querySelector('#fvCompSearch');
                if (compSearch) {
                    compSearch.addEventListener('input', () => {
                        const q = compSearch.value.trim().toLowerCase();
                        popup.querySelectorAll('.fv-comp').forEach((chip) => {
                            chip.style.display = !q || chip.dataset.search.indexOf(q) !== -1 ? '' : 'none';
                        });
                    });
                }

                // History segmented toggle (inside <summary> - don't let it collapse the section)
                popup.querySelectorAll('.fv-seg-btn').forEach((btn) => {
                    btn.addEventListener('click', (e) => {
                        e.preventDefault();
                        const details = btn.closest('details');
                        if (details) details.open = true;
                        popup.querySelectorAll('.fv-seg-btn').forEach((b) => b.classList.toggle('active', b === btn));
                        popup.querySelectorAll('.fv-hist-panel').forEach((p) => {
                            p.style.display = p.dataset.panel === btn.dataset.target ? '' : 'none';
                        });
                    });
                });
            },
            preConfirm: () => {
                const officerRadio = document.querySelector('input[name="fvOfficer"]:checked');
                const financeOfficerEmpId = officerRadio ? String(officerRadio.value || '').trim() : '';
                const confirmCheck = document.getElementById('verificationConfirmCheck');

                const companyIds = Array.from(document.querySelectorAll('input[name="fvCompany"]:checked'))
                    .map(cb => String(cb.value || '').trim())
                    .filter(Boolean);
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

        pvLoading('<?= __('saving_finance_setup', 'Saving the setup and notifying the finance officer...') ?>');

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

        await pvResult('success', '<?= __('success', 'Success') ?>', submitData.message || 'Finance verification setup confirmed successfully.');
        location.reload();
    } catch (error) {
        Swal.close();
        pvResult('error', '<?= __('error', 'Error') ?>', error.message || 'Failed to complete finance verification setup.');
    }
}

async function openCompanyPayrollReportModal(requestInvNo, payrollMonth) {
    try {
        const optionsPayload = new URLSearchParams();
        optionsPayload.append('action', 'get_direct_supervisor_options');
        optionsPayload.append('request_inv_no', requestInvNo);
        optionsPayload.append('month', payrollMonth);

        pvLoading('<?= __('loading_direct_supervisors', 'Loading direct supervisors and report status...') ?>', '<?= __('loading', 'Loading') ?>');

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
        let sentCount = 0, mergedCount = 0, totalEmployees = 0;
        const supervisorCardsList = supervisors.map(s => {
            const isSent = Number(s.is_sent || 0) === 1 || s.is_sent === true;
            const isMergedElsewhere = String(s.merged_into || '').trim() !== '';
            if (isMergedElsewhere) mergedCount++; else if (isSent) sentCount++;
            totalEmployees += Number(s.employee_count || 0);
            const empId = String(s.supervisor_emp_id || '');
            const name = String(s.supervisor_name || 'N/A');
            const initials = name.split(/\s+/).slice(0, 2).map(w => w.charAt(0)).join('').toUpperCase();
            const badge = isMergedElsewhere
                ? `<span class="ps-badge tone-slate"><i class="mdi mdi-call-merge"></i> <?= __('assigned_to_other_supervisor', 'Assigned Elsewhere') ?></span>`
                : (isSent
                    ? `<span class="ps-badge tone-amber"><i class="mdi mdi-email-open"></i> <?= __('sent_will_resend', 'Sent · will resend') ?></span>`
                    : `<span class="ps-badge tone-sky"><i class="mdi mdi-email-outline"></i> <?= __('not_sent_yet', 'Not sent') ?></span>`);
            const search = `${name} ${empId} ${s.supervisor_email || ''}`.toLowerCase();
            return `<label class="ps-row${isMergedElsewhere ? ' is-disabled' : ''}" data-id="${escPv(empId)}" data-search="${escPv(search)}">
                    <input type="radio" name="psSupervisor" value="${escPv(empId)}"${isMergedElsewhere ? ' disabled' : ''}>
                    <span class="ps-avatar">${escPv(initials)}</span>
                    <span class="ps-info">
                        <span class="ps-name">${escPv(name)}</span>
                        <span class="ps-sub"><span class="pv-mono">#${escPv(empId)}</span>${s.supervisor_email ? ` · ${escPv(s.supervisor_email)}` : ''}</span>
                    </span>
                    <span class="ps-count" title="<?= __('employees', 'Employees') ?>"><i class="mdi mdi-account-multiple"></i> ${Number(s.employee_count || 0)}</span>
                    ${badge}
                    ${isMergedElsewhere ? '<span class="ps-preview-spacer"></span>' : `<button type="button" class="ps-preview" data-id="${escPv(empId)}" title="<?= __('preview_employees', 'Preview Employees') ?>"><i class="mdi mdi-eye-outline"></i></button>`}
                </label>`;
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
                pvLoading('<?= __('loading_employees', 'Loading employees...') ?>', '<?= __('preview_employees', 'Preview Employees') ?>');

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
                // Group-by-company counts for the chips row
                const byCompany = {};
                employees.forEach(emp => { const k = String(emp.company || '-'); byCompany[k] = (byCompany[k] || 0) + 1; });
                const sortedEmployees = [...employees].sort((a, b) => String(a.name || '').localeCompare(String(b.name || '')));
                const rowsHtml = sortedEmployees.map((emp) => {
                    const name = String(emp.name || '');
                    const initials = name.split(/\s+/).slice(0, 2).map(w => w.charAt(0)).join('').toUpperCase();
                    const search = `${emp.emp_id} ${name} ${emp.department || ''} ${emp.company || ''}`.toLowerCase();
                    return `<div class="pe-row" data-search="${escPv(search)}">
                            <span class="ps-avatar">${escPv(initials)}</span>
                            <span class="ps-info"><span class="ps-name">${escPv(name)}</span><span class="ps-sub"><span class="pv-mono">#${escPv(emp.emp_id)}</span> · ${escPv(emp.department || '-')}</span></span>
                            <span class="pe-company"><i class="mdi mdi-domain"></i> ${escPv(emp.company || '-')}</span>
                        </div>`;
                }).join('');

                await Swal.fire({
                    title: pvTitleHtml('mdi mdi-account-multiple', 'indigo', '<?= __('preview_employees', 'Preview Employees') ?>', escPv(previewData.supervisor_name) + ' · ' + Number(previewData.employee_count || employees.length) + ' <?= __('employees', 'Employees') ?>'),
                    html: `
                        <div class="ps-tools"><div class="ps-search"><i class="mdi mdi-magnify"></i><input type="search" id="peSearch" placeholder="<?= __('search', 'Search') ?>..." autocomplete="off"></div></div>
                        ${Object.keys(byCompany).length > 1 ? `<div class="pe-chips">${Object.keys(byCompany).sort().map(k => `<span>${escPv(k)} <strong>${byCompany[k]}</strong></span>`).join('')}</div>` : ''}
                        <div class="pe-list" id="peList">
                            ${rowsHtml || `<div class="ps-empty"><?= __('no_data_available_in_table', 'No data available in table') ?></div>`}
                            <div class="ps-empty" id="peNoMatch" style="display:none;"><?= __('no_matching_records_found', 'No matching records found') ?></div>
                        </div>
                    `,
                    width: '680px',
                    customClass: { popup: 'pv-popup' },
                    allowOutsideClick: false,
                    confirmButtonColor: '#6366f1',
                    confirmButtonText: '<i class="mdi mdi-arrow-left"></i> <?= __('back', 'Back') ?>',
                    didOpen: (popup) => {
                        const input = popup.querySelector('#peSearch');
                        input.addEventListener('input', () => {
                            const q = input.value.trim().toLowerCase();
                            let shown = 0;
                            popup.querySelectorAll('.pe-row').forEach(row => {
                                const ok = !q || row.dataset.search.indexOf(q) !== -1;
                                row.style.display = ok ? '' : 'none';
                                if (ok) shown++;
                            });
                            popup.querySelector('#peNoMatch').style.display = shown || !employees.length ? 'none' : '';
                        });
                    }
                });            } catch (previewError) {
                await pvResult('error', '<?= __('error', 'Error') ?>', previewError.message || 'Failed to load employees preview.');
            }
        }

        // The select-supervisor modal can be reopened with the previous selection preserved
        // after "Preview Employees" is used, instead of forcing the user to start over.
        let preselectedSupervisorId = '';
        let previewSupervisorId = '';
        let modalResult;
        while (true) {
            let cameFromPreview = false;

            modalResult = await Swal.fire({
                title: pvTitleHtml('mdi mdi-send', 'indigo', '<?= __('send_supervisor_payroll_report', 'Send Payroll Report by Direct Supervisor') ?>', escPv(pvMonthLabel(payrollMonth)) + ' · <span class="pv-mono">' + escPv(requestInvNo) + '</span>'),
                html: `
                    <div class="ps-stats">
                        <div class="ps-stat"><span><?= __('supervisors', 'Supervisors') ?></span><strong>${supervisors.length}</strong></div>
                        <div class="ps-stat tone-sky"><span><?= __('not_sent_yet', 'Not sent') ?></span><strong>${supervisors.length - sentCount - mergedCount}</strong></div>
                        <div class="ps-stat tone-amber"><span><?= __('already_sent', 'Already sent') ?></span><strong>${sentCount}</strong></div>
                        <div class="ps-stat"><span><?= __('employees', 'Employees') ?></span><strong>${totalEmployees.toLocaleString('en-US')}</strong></div>
                    </div>
                    <div class="ps-tools">
                        <div class="ps-search"><i class="mdi mdi-magnify"></i><input type="search" id="psSearch" placeholder="<?= __('search_supervisor', 'Search supervisor, ID, email...') ?>" autocomplete="off"></div>
                    </div>
                    <div class="ps-list" id="psList">
                        ${supervisorCardsList}
                        <div class="ps-empty" id="psNoMatch" style="display:none;"><?= __('no_matching_records_found', 'No matching records found') ?></div>
                    </div>
                    <div class="ps-hint"><i class="mdi mdi-information-outline"></i><span><?= __('batch_email_sent_hint_supervisor', 'Supervisors already sent a report can be selected again to resend (e.g. figures changed). Supervisors marked Assigned Elsewhere already had their employees merged into another supervisor\'s report and can\'t be selected here.') ?></span></div>
                `,
                customClass: { popup: 'pv-popup' },
                showCancelButton: true,
                confirmButtonColor: '#16a34a',
                cancelButtonColor: '#6c757d',
                confirmButtonText: '<i class="mdi mdi-send"></i> <?= __('send_email', 'Send Email') ?>',
                cancelButtonText: '<?= __('cancel', 'Cancel') ?>',
                allowOutsideClick: false,
                width: '720px',
                didOpen: (popup) => {
                    const confirmBtn = Swal.getConfirmButton();
                    const sync = () => {
                        const checked = popup.querySelector('input[name="psSupervisor"]:checked');
                        popup.querySelectorAll('.ps-row').forEach(row => row.classList.toggle('is-selected', !!checked && row.dataset.id === checked.value));
                        const isResend = checked && !!checked.closest('.ps-row').querySelector('.ps-badge.tone-amber');
                        confirmBtn.innerHTML = isResend
                            ? '<i class="mdi mdi-sync"></i> <?= __('resend_email', 'Resend Email') ?>'
                            : '<i class="mdi mdi-send"></i> <?= __('send_email', 'Send Email') ?>';
                    };
                    popup.querySelectorAll('input[name="psSupervisor"]').forEach(r => r.addEventListener('change', sync));

                    if (preselectedSupervisorId) {
                        const pre = popup.querySelector(`input[name="psSupervisor"][value="${CSS.escape(preselectedSupervisorId)}"]`);
                        if (pre && !pre.disabled) {
                            pre.checked = true;
                            pre.closest('.ps-row').scrollIntoView({ block: 'nearest' });
                        }
                    }
                    sync();

                    const searchEl = popup.querySelector('#psSearch');
                    searchEl.addEventListener('input', () => {
                        const q = searchEl.value.trim().toLowerCase();
                        let shown = 0;
                        popup.querySelectorAll('.ps-row').forEach(row => {
                            const ok = !q || row.dataset.search.indexOf(q) !== -1;
                            row.style.display = ok ? '' : 'none';
                            if (ok) shown++;
                        });
                        popup.querySelector('#psNoMatch').style.display = shown ? 'none' : '';
                    });

                    popup.querySelectorAll('.ps-preview').forEach(btn => {
                        btn.addEventListener('click', (e) => {
                            e.preventDefault();
                            e.stopPropagation();
                            // Close the select modal first (sequential, not nested) so the preview
                            // modal shows cleanly, then the loop below reopens the select modal
                            // afterwards with the same selection restored.
                            const checked = popup.querySelector('input[name="psSupervisor"]:checked');
                            previewSupervisorId = btn.dataset.id;
                            preselectedSupervisorId = checked ? checked.value : btn.dataset.id;
                            cameFromPreview = true;
                            Swal.close();
                        });
                    });
                },
                preConfirm: () => {
                    const checked = document.querySelector('input[name="psSupervisor"]:checked');
                    const supervisorId = checked ? String(checked.value || '').trim() : '';
                    if (supervisorId === '') {
                        Swal.showValidationMessage('<?= __('direct_supervisor_required', 'Direct supervisor is required.') ?>');
                        return false;
                    }
                    if (checked.disabled) {
                        Swal.showValidationMessage('<?= addslashes(__('supervisor_assigned_elsewhere', 'This supervisor employees were already assigned to another supervisor report. Please select another supervisor.')) ?>');
                        return false;
                    }
                    return { supervisorIds: [supervisorId] };
                }
            });

            if (cameFromPreview) {
                await showEmployeesPreviewModal(previewSupervisorId);
                continue;
            }
            break;
        }

        if (!modalResult.isConfirmed) {
            return;
        }

        pvLoading('<?= __('sending_supervisor_report', 'Building the report and sending the email...') ?>');

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

        await pvResult('success', '<?= __('success', 'Success') ?>', sendData.message || 'Supervisor payroll report email sent successfully.');
    } catch (error) {
        Swal.close();
        pvResult('error', '<?= __('error', 'Error') ?>', error.message || 'Failed to send supervisor payroll report email.');
    }
}
</script>
</body>
</html>
