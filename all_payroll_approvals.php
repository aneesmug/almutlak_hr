<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/session_check.php';
require_once __DIR__ . '/includes/helper_functions.php';
require_once __DIR__ . '/includes/init.php';
require_once __DIR__ . '/includes/payroll_approval_helpers.php';

if (isset($isEmployee) && $isEmployee === true) {
    header('Location: ./profile.php');
    exit();
}

$currentUserTypeForAccess = strtolower(trim((string)($user_type ?? '')));
$currentEmpTypeForAccess = strtolower(trim((string)($emp_type ?? '')));
$isFinanceOfficerUser = ($currentUserTypeForAccess === 'finance_officer')
    || ($currentUserTypeForAccess === 'finance' && $currentEmpTypeForAccess !== 'manager' && (int)($user_dept ?? 0) === 2);
if ($isFinanceOfficerUser) {
    header('Location: ./dashboard.php');
    exit();
}

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
    $where[] = 'ra_pending.approver_id = ?';
    $params[] = (string)$empid;
    $types .= 's';
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
            SELECT month_year, COUNT(emp_id) AS employee_count, SUM(net_salary) AS total_net_salary
            FROM payrolls GROUP BY month_year
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
$isHeadOfficeFinanceManager = strtolower(trim((string)($user_type ?? ''))) === 'finance'
    && strtolower(trim((string)($emp_type ?? ''))) === 'manager'
    && (int)($user_dept ?? 0) === 2;

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

    $scopeWhere = $isManagerVerifier ? 'finance_manager_emp_id = :verifier_id' : 'finance_officer_emp_id = :verifier_id';
    $scopeStmt = $pdo->prepare("SELECT selected_company_ids
        FROM payroll_finance_verification
        WHERE request_inv_no = :inv_no
          AND payroll_month = :month_year
          AND " . $scopeWhere . "
          AND is_confirmed = 1
        ORDER BY id DESC
        LIMIT 1");
    $scopeStmt->execute([
        ':inv_no' => $requestInvNo,
        ':month_year' => $monthValue,
        ':verifier_id' => $verifierEmpId
    ]);
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
        return [
            'total_employees' => 0,
            'checked_employees' => 0,
            'remaining_employees' => 0,
            'is_completed' => false
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
        .request-card .card-body { padding: 1.5rem; }
        .detail-item { display: flex; align-items: center; font-size: 1.02em; margin-bottom: .75rem; }
        .detail-item i.fad { color: #4a90e2; margin-right: 12px; width: 20px; text-align: center; flex-shrink: 0; }
        .detail-item strong { color: #8a94a6; min-width: 135px; display: inline-block; margin-right: 8px; }
        .request-card .card-footer { background-color: #fafbff; border-top: 1px solid #eef; }
        .no-requests { padding: 3rem; background: #fff; border-radius: 15px; box-shadow: 0 10px 30px rgba(0, 0, 0, 0.07); }
        .request-details-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: .35rem 1.25rem;
        }
        .request-details-grid .detail-item {
            margin-bottom: .35rem;
            min-width: 0;
        }
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
            .request-details-grid,
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
                                        $financeVerificationConfirmed = !empty($request['finance_verification_confirmed']);
                                        $financeOfficerApproved = !empty($request['finance_officer_approved']);
                                        $assignedCompanyIdsRaw = json_decode((string)($request['finance_selected_company_ids'] ?? '[]'), true);
                                        $assignedCompanyIds = is_array($assignedCompanyIdsRaw)
                                            ? array_values(array_unique(array_filter(array_map(static function ($value) {
                                                return trim((string)$value);
                                            }, $assignedCompanyIdsRaw), static function ($value) {
                                                return $value !== '';
                                            })))
                                            : [];
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
                                        if ($financeVerificationConfirmed && !empty($request['finance_verification_officer']) && !empty($request['request_inv_no'])) {
                                            $financeOfficerChecklistCompletion = getFinanceVerifierChecklistCompletion(
                                                $pdo,
                                                (string)$request['request_inv_no'],
                                                (string)$request['payroll_month'],
                                                (string)$request['finance_verification_officer'],
                                                false
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
                                        
                                        $financeOfficerFinalApproved = $financeOfficerChecklistCompleted && $financeOfficerApproved && $financeManagerChecklistCompleted && $allAssignedCompaniesApproved;
                                        $canApproveNow = $isPendingWithMe && (!$isHeadOfficeFinancePending || $financeOfficerFinalApproved);
                                        $showFinanceVerificationSetupAction = $isHeadOfficeFinancePending && !$allCompaniesAssigned;
                                        $hrCheckedCount = (int)($request['hr_checked_count'] ?? 0);
                                        $monthEmployeeCount = (int)($request['employee_count'] ?? 0);
                                        $canSendCompanyPayrollReport = $isHrPayrollUser
                                            && !empty($request['request_inv_no'])
                                            && $approvalStatus === 'pending_approval'
                                            && $isPendingWithMe
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

                                        $checklistBtnClass = 'btn-info';
                                        if ($approvalStatus === 'pending_approval') {
                                            $checklistBtnClass = 'btn-warning';
                                        } elseif ($approvalStatus === 'approved') {
                                            $checklistBtnClass = 'btn-success';
                                        } elseif ($approvalStatus === 'rejected') {
                                            $checklistBtnClass = 'btn-danger';
                                        } elseif ($approvalStatus === 'completed') {
                                            $checklistBtnClass = 'btn-primary';
                                        } elseif ($approvalStatus === null) {
                                            $checklistBtnClass = 'btn-dark';
                                        }
                                        ?>
                                        <div class="col-lg-4 col-md-6 mb-4">
                                            <div class="card request-card h-100">
                                                <div class="card-header">
                                                    <?= __('payroll_month') ?>: <?= htmlspecialchars($request['payroll_month']) ?>
                                                    <?php if (!empty($request['request_inv_no'])): ?>
                                                    <span class="float-right"><?= __('request_id') ?>: <?= htmlspecialchars($request['request_inv_no']) ?></span>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="card-body">
                                                    <div class="request-details-grid">
                                                        <div class="detail-item"><i class="fad fa-calendar"></i><strong><?= __('month') ?>:</strong> <?= htmlspecialchars($request['payroll_month']) ?></div>
                                                        <div class="detail-item"><i class="fad fa-users"></i><strong><?= __('employees', 'Employees') ?>:</strong> <?= (int)($request['employee_count'] ?? 0) ?></div>
                                                        <div class="detail-item"><i class="fad fa-money-bill"></i><strong><?= __('total_net', 'Total Net') ?>:</strong> <?= number_format((float)($request['total_net_salary'] ?? 0), 2) ?> SAR</div>
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
                                                        <div class="detail-item"><i class="fad fa-clock"></i><strong><?= __('submitted', 'Submitted') ?>:</strong> <?= htmlspecialchars(date('d M Y', strtotime($request['approval_created_at']))) ?></div>
                                                        <?php endif; ?>
                                                        <div class="detail-item">
                                                            <i class="fad fa-tasks"></i>
                                                            <strong><?= __('status') ?>:</strong>
                                                            <span class="badge badge-<?= $statusClass ?> p-2"><?= $statusIcon . ' ' . $statusText ?></span>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="card-footer d-flex justify-content-between align-items-center" style="gap:0.5rem;">
                                                    <a class="btn <?= $checklistBtnClass ?> btn-block waves-effect" href="payroll_checklist_report.php?month=<?= urlencode($request['payroll_month']) ?><?php if (!empty($request['request_inv_no'])): ?>&request_inv_no=<?= urlencode($request['request_inv_no']) ?><?php endif; ?>" target="_blank">
                                                        <i class="fa fa-clipboard-check"></i> <?= __('payroll_checklist_report', 'Payroll Check List') ?>
                                                    </a>
                                                    <div class="btn-group flex-fill">
                                                        <button type="button" class="btn btn-secondary dropdown-toggle btn-block waves-effect" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                                            <?= __('actions') ?> <span class="caret"></span>
                                                        </button>
                                                        <div class="dropdown-menu dropdown-menu-right">
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
                                                                <a class="dropdown-item" href="javascript:void(0);" onclick="approvePayrollRequest('<?= htmlspecialchars($request['request_inv_no'], ENT_QUOTES) ?>', '<?= htmlspecialchars($request['payroll_month'], ENT_QUOTES) ?>', <?= (int)($request['employee_count'] ?? 0) ?>, <?= (float)($request['total_net_salary'] ?? 0) ?>, '<?= htmlspecialchars(getDisplayName($request['requester_name'] ?? ''), ENT_QUOTES) ?>')">
                                                                    <i class="fa fa-check text-success"></i> <?= __('approve') ?>
                                                                </a>
                                                                <a class="dropdown-item" href="javascript:void(0);" onclick="rejectPayrollRequest('<?= htmlspecialchars($request['request_inv_no'], ENT_QUOTES) ?>', '<?= htmlspecialchars($request['payroll_month'], ENT_QUOTES) ?>', <?= (int)($request['employee_count'] ?? 0) ?>, <?= (float)($request['total_net_salary'] ?? 0) ?>, '<?= htmlspecialchars(getDisplayName($request['requester_name'] ?? ''), ENT_QUOTES) ?>')">
                                                                    <i class="fa fa-times text-danger"></i> <?= __('reject') ?>
                                                                </a>
                                                            <?php endif; ?>
                                                            <?php if ($canSendCompanyPayrollReport): ?>
                                                                <div class="dropdown-divider"></div>
                                                                <a class="dropdown-item" href="javascript:void(0);" onclick="openCompanyPayrollReportModal('<?= htmlspecialchars($request['request_inv_no'], ENT_QUOTES) ?>', '<?= htmlspecialchars($request['payroll_month'], ENT_QUOTES) ?>')">
                                                                    <i class="fa fa-envelope-open-text text-primary"></i> <?= __('send_company_payroll_report', 'Send Company Payroll Report') ?>
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
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="assets/js/jquery.core.js"></script>
<script src="assets/js/jquery.app.js?t=<?= time() ?>"></script>
<script>
function buildPayrollRequestDetailsHtml(requestInvNo, payrollMonth, employeeCount, totalNet, requesterName) {
    const safeRequesterName = requesterName || '<?= __('not_available', 'N/A') ?>';
    const formattedTotalNet = Number(totalNet || 0).toLocaleString('en-US', {
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
            </div>
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

async function approvePayrollRequest(requestInvNo, payrollMonth, employeeCount, totalNet, requesterName) {
    const result = await Swal.fire({
        title: '<?= __('approve') ?> Payroll',
        // ${buildPayrollRequestDetailsHtml(requestInvNo, payrollMonth, employeeCount, totalNet, requesterName)}
        html: `
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

async function rejectPayrollRequest(requestInvNo, payrollMonth, employeeCount, totalNet, requesterName) {
    const result = await Swal.fire({
        title: '<?= __('confirm_rejection', 'Confirm Rejection') ?>',
        html: buildPayrollRequestDetailsHtml(requestInvNo, payrollMonth, employeeCount, totalNet, requesterName),
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
        optionsPayload.append('action', 'get_company_manager_options');
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
            throw new Error(optionsData.message || 'Failed to load company/manager options.');
        }

        const companies = Array.isArray(optionsData.companies) ? optionsData.companies : [];
        const managers = Array.isArray(optionsData.managers) ? optionsData.managers : [];

        if (companies.length === 0) {
            throw new Error('<?= __('no_data_available_in_table', 'No data available in table') ?>');
        }

        if (managers.length === 0) {
            throw new Error('<?= __('no_manager_with_email_found', 'No manager with registered email found.') ?>');
        }

        const companyOptionsList = companies.map(c => {
            const isSent = Number(c.is_sent || 0) === 1 || c.is_sent === true;
            const disabledAttr = isSent ? ' disabled="disabled"' : '';
            const sentAttr = isSent ? '1' : '0';
            return `<option value="${String(c.comp_id || '').replace(/"/g, '&quot;')}" data-is-sent="${sentAttr}"${disabledAttr}>${String(c.comp_name || 'N/A')} (${Number(c.employee_count || 0)} <?= __('employees', 'Employees') ?>)</option>`;
        }).join('');
        const companyOptions = `<option value="" selected><?= __('select_company', 'Select Company') ?></option>${companyOptionsList}`;
        const hasAvailableCompany = companies.some(c => !(Number(c.is_sent || 0) === 1 || c.is_sent === true));
        if (!hasAvailableCompany) {
            throw new Error('<?= __('all_company_batch_emails_sent', 'Batch email already sent for all companies.') ?>');
        }

        const modalResult = await Swal.fire({
            title: '<?= __('send_company_payroll_report', 'Send Company Payroll Report') ?>',
            html: `
                <div class="text-left">
                    <label for="companyReportSelect" class="font-weight-bold"><?= __('company', 'Company') ?></label>
                    <select id="companyReportSelect" class="form-control mb-3" multiple>${companyOptionsList}</select>
                    <div class="small text-info mb-2"><?= __('batch_email_sent_hint', 'Companies marked as Batch Email Sent are already processed and cannot be selected again.') ?></div>

                    <label for="managerReportSelect" class="font-weight-bold"><?= __('manager', 'Manager') ?></label>
                    <select id="managerReportSelect" class="form-control mb-2" disabled>
                        <option value="" selected><?= __('select_company_first', 'Select company first') ?></option>
                    </select>

                    <div class="small text-success mb-1" id="managerSuggestionText"></div>

                    <div class="small text-muted" id="selectedManagerEmail"></div>
                </div>
            `,
            showCancelButton: true,
            confirmButtonColor: '#28a745',
            confirmButtonText: '<?= __('send_email', 'Send Email') ?>',
            cancelButtonText: '<?= __('cancel', 'Cancel') ?>',
            allowOutsideClick: false,
            didOpen: () => {
                const managerSelect = document.getElementById('managerReportSelect');
                const companySelect = document.getElementById('companyReportSelect');
                const emailText = document.getElementById('selectedManagerEmail');
                const suggestionText = document.getElementById('managerSuggestionText');
                const normalizeCompanyId = (value) => String(value || '').trim().toLowerCase();
                const getSelectedCompanyIds = () => {
                    if (!companySelect) {
                        return [];
                    }
                    return Array.from(companySelect.selectedOptions || [])
                        .map(option => normalizeCompanyId(option.value || ''))
                        .filter(value => value !== '');
                };
                const populateManagerOptions = () => {
                    if (!companySelect || !managerSelect) {
                        return;
                    }

                    const selectedCompanyIds = getSelectedCompanyIds();

                    managerSelect.innerHTML = '';
                    managerSelect.disabled = true;
                    emailText.textContent = '';

                    if (selectedCompanyIds.length === 0) {
                        managerSelect.innerHTML = `<option value="" selected><?= __('select_company_first', 'Select company first') ?></option>`;
                        if (window.jQuery && typeof jQuery.fn.select2 === 'function') {
                            jQuery(managerSelect).trigger('change');
                        }
                        if (suggestionText) {
                            suggestionText.textContent = '';
                        }
                        return;
                    }

                    const matchingManagers = managers.filter(m => selectedCompanyIds.includes(normalizeCompanyId(m.company_id || '')));
                    const optionsHtml = managers.map(m => `<option value="${String(m.emp_id || '').replace(/"/g, '&quot;')}" data-email="${String(m.email || '').replace(/"/g, '&quot;')}" data-company-id="${String(m.company_id || '').replace(/"/g, '&quot;')}">${String(m.name || m.emp_id || 'N/A')}</option>`).join('');

                    managerSelect.innerHTML = optionsHtml;
                    managerSelect.disabled = managers.length === 0;

                    if (matchingManagers.length > 0 && suggestionText) {
                        suggestionText.textContent = `<?= __('suggested_manager_by_company', 'Suggested manager selected for this company. You can change it manually.') ?>`;
                    } else if (suggestionText) {
                        suggestionText.textContent = `<?= __('no_company_manager_suggestion', 'No exact manager match for this company. Please select manually.') ?>`;
                    }

                    if (window.jQuery && typeof jQuery.fn.select2 === 'function') {
                        jQuery(managerSelect).trigger('change');
                    }
                    refreshEmail();
                };
                const refreshEmail = () => {
                    const selectedOption = managerSelect && managerSelect.options[managerSelect.selectedIndex];
                    emailText.textContent = selectedOption
                        ? `<?= __('email', 'Email') ?>: ${selectedOption.getAttribute('data-email') || ''}`
                        : '';
                };
                const suggestManagerForCompany = () => {
                    if (!companySelect || !managerSelect) {
                        return;
                    }

                    const selectedCompanyIds = getSelectedCompanyIds();
                    if (selectedCompanyIds.length === 0) {
                        populateManagerOptions();
                        return;
                    }

                    const suggestedOption = Array.from(managerSelect.options).find(option => {
                        const optionCompanyId = normalizeCompanyId(option.getAttribute('data-company-id') || '');
                        return optionCompanyId !== '' && selectedCompanyIds.includes(optionCompanyId);
                    });

                    if (suggestedOption) {
                        managerSelect.value = suggestedOption.value;
                        if (window.jQuery && typeof jQuery.fn.select2 === 'function') {
                            jQuery(managerSelect).trigger('change');
                        }
                        refreshEmail();
                        if (suggestionText) {
                            suggestionText.textContent = `<?= __('suggested_manager_by_company', 'Suggested manager selected for this company. You can change it manually.') ?>`;
                        }
                    } else if (suggestionText) {
                        suggestionText.textContent = `<?= __('no_company_manager_suggestion', 'No exact manager match for this company. Please select manually.') ?>`;
                    }
                };
                if (window.jQuery && typeof jQuery.fn.select2 === 'function') {
                    const $popup = jQuery('.swal2-popup');
                    if (companySelect) {
                        jQuery(companySelect).select2({
                            width: '100%',
                            dropdownParent: $popup,
                            placeholder: '<?= __('select_company', 'Select Company') ?>',
                            closeOnSelect: false,
                            escapeMarkup: function(markup) {
                                return markup;
                            },
                            templateResult: function(state) {
                                if (!state.id) {
                                    return state.text;
                                }

                                const optionEl = state.element || null;
                                const isSent = optionEl && optionEl.getAttribute('data-is-sent') === '1';
                                const safeText = jQuery('<div>').text(state.text || '').html();
                                const badgeHtml = isSent
                                    ? '<span class="badge badge-primary float-right"><?= __('batch_email_sent', 'Email Sent') ?></span>'
                                    : '';

                                return '<span class="d-flex justify-content-between align-items-center w-100"><span>' + safeText + '</span>' + badgeHtml + '</span>';
                            },
                            templateSelection: function(state) {
                                if (!state.id) {
                                    return state.text;
                                }

                                const optionEl = state.element || null;
                                const isSent = optionEl && optionEl.getAttribute('data-is-sent') === '1';
                                const safeText = jQuery('<div>').text(state.text || '').html();
                                const badgeHtml = isSent
                                    ? ' <span class="badge badge-info"><?= __('batch_email_sent', 'Batch Email Sent') ?></span>'
                                    : '';

                                return '<span>' + safeText + badgeHtml + '</span>';
                            }
                        });
                    }
                    if (managerSelect) {
                        jQuery(managerSelect).select2({
                            width: '100%',
                            dropdownParent: $popup,
                            placeholder: '<?= __('select_manager', 'Select Manager') ?>'
                        });
                    }
                }
                if (companySelect) {
                    companySelect.addEventListener('change', () => {
                        populateManagerOptions();
                        suggestManagerForCompany();
                    });
                    if (window.jQuery && typeof jQuery.fn.select2 === 'function') {
                        jQuery(companySelect).on('change.selectCompanySuggest', () => {
                            populateManagerOptions();
                            suggestManagerForCompany();
                        });
                    }
                }
                if (managerSelect) {
                    managerSelect.addEventListener('change', refreshEmail);
                    refreshEmail();
                }
            },
            preConfirm: () => {
                const companySelect = document.getElementById('companyReportSelect');
                const companyIds = companySelect
                    ? Array.from(companySelect.selectedOptions || []).map(option => String(option.value || '').trim()).filter(value => value !== '')
                    : [];
                const managerEmpId = (document.getElementById('managerReportSelect') || {}).value || '';
                if (companyIds.length === 0) {
                    Swal.showValidationMessage('<?= __('company_required', 'Company is required.') ?>');
                    return false;
                }
                const hasDisabledSelectedCompany = companySelect
                    ? Array.from(companySelect.selectedOptions || []).some(option => option.disabled)
                    : false;
                if (hasDisabledSelectedCompany) {
                    Swal.showValidationMessage('<?= __('company_already_sent', 'Batch email already sent for this company. Please select another company.') ?>');
                    return false;
                }
                if (!managerEmpId) {
                    Swal.showValidationMessage('<?= __('manager_required', 'Manager selection is required.') ?>');
                    return false;
                }
                return { companyIds, managerEmpId };
            }
        });

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
        sendPayload.append('action', 'send_company_manager_payroll_report');
        sendPayload.append('request_inv_no', requestInvNo);
        sendPayload.append('month', payrollMonth);
        sendPayload.append('company_ids', JSON.stringify(modalResult.value.companyIds || []));
        sendPayload.append('manager_emp_id', modalResult.value.managerEmpId);

        const sendResponse = await fetch('./includes/ajaxFile/payroll_approval_handler.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8' },
            body: sendPayload.toString()
        });
        const sendData = await sendResponse.json();
        Swal.close();

        if (!sendResponse.ok || sendData.status !== 'success') {
            throw new Error(sendData.message || 'Failed to send company payroll report email.');
        }

        await Swal.fire({
            icon: 'success',
            title: '<?= __('success', 'Success') ?>',
            text: sendData.message || 'Company payroll report email sent successfully.',
            allowOutsideClick: false
        });
    } catch (error) {
        Swal.close();
        Swal.fire('<?= __('error', 'Error') ?>', error.message || 'Failed to send company payroll report email.', 'error');
    }
}
</script>
</body>
</html>
