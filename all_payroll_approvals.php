<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/session_check.php';
require_once __DIR__ . '/includes/helper_functions.php';
require_once __DIR__ . '/includes/payroll_approval_helpers.php';

if (isset($isEmployee) && $isEmployee === true) {
    header('Location: ./profile.php');
    exit();
}

$pdo = getDbConnection();
ensurePayrollApprovalTable($pdo);
ensurePayrollChecklistReviewTable($pdo);
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

$isFinanceOfficerRole = function_exists('isHeadOfficeFinanceOfficer')
    ? isHeadOfficeFinanceOfficer(true)
    : (isset($user_type) && strtolower(trim((string)$user_type)) === 'finance_officer' && (int)($user_dept ?? 0) === 2);
$currentFilter = $_GET['status'] ?? null;
if ($currentFilter === null) {
    if ($is_system_admin) {
        $currentFilter = 'all';
    } elseif ($isFinanceOfficerRole) {
        $currentFilter = 'all';
    } else {
        $currentFilter = 'my_pending';
    }
}

$canSeeAllDepts = ($is_system_admin ?? false) || ($isHR ?? false);
$resolvedUserType = strtolower(trim((string)($user_type ?? '')));
$isFinanceRole = ($resolvedUserType !== 'finance_officer' && stripos($resolvedUserType, 'finance') !== false) || $isFinanceOfficerRole;
$canSeeAllDepts = $canSeeAllDepts || $isFinanceRole;

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
                        CASE WHEN EXISTS (
                                SELECT 1
                                FROM smt_request_status srs
                                WHERE srs.inv_no = pr.request_inv_no
                                    AND srs.status = 'finance_review_complete'
                        ) THEN 1 ELSE 0 END AS finance_review_completed,
            req_emp.name AS requester_name,
            req_emp.dept AS requester_dept,
            ra_pending.approver_id AS current_approver_id,
            ra_pending.approval_level AS current_approval_level,
            approver_emp.name AS current_approver_name,
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
                                        $isFinanceReviewCompleted = !empty($request['finance_review_completed']);
                                        $isPendingWithMe = ($approvalStatus === 'pending_approval' && !empty($request['current_approver_id']) && (string)$request['current_approver_id'] === (string)$empid);
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
                                            $statusClass = $isFinanceReviewCompleted ? 'info' : 'success';
                                            $statusText = $isFinanceOfficerRole
                                                ? ($isFinanceReviewCompleted
                                                    ? __('finance_review_already', 'Finance Review Already')
                                                    : __('ready_for_finance_review', 'Ready for Finance Review'))
                                                : __('approved');
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
                                            $checklistBtnClass = ($isFinanceOfficerRole && $isFinanceReviewCompleted) ? 'btn-secondary' : 'btn-success';
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
                                                        <i class="fa fa-clipboard-check"></i> <?= ($isFinanceOfficerRole && $approvalStatus === 'approved') ? ($isFinanceReviewCompleted ? __('finance_review_completed', 'Finance Officer Review Completed') : __('start_finance_review', 'Start Finance Review')) : __('payroll_checklist_report', 'Payroll Check List') ?>
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
                                                            <?php if ($isPendingWithMe): ?>
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
