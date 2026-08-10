<?php
require_once __DIR__ . '/includes/session_check.php';
require_once __DIR__ . '/includes/special_access_helper.php';

// Restrict access: Employees cannot view this detailed report page,
// unless explicitly granted via app_settings -> Special Access.
if (
    isset($isEmployee) && $isEmployee === true
    && !user_has_special_access($conDB, $empid ?? '', 'access_all_applied_salary_increment', $user_role ?? '', $user_type ?? '', $is_system_admin ?? false)
) {
    header("Location: ./profile.php");
    exit();
}

$can_cancel_salary_increment_requests = (
    !empty($is_system_admin)
    || user_has_special_access($conDB, $empid ?? '', 'cancel_salary_increment_requests', $user_role ?? '', $user_type ?? '', $is_system_admin ?? false)
);
$cancellable_salary_increment_statuses = ['pending_approval'];

// --- Get Request Type ID for 'salary_increment' ---
$type_query = mysqli_query($conDB, "SELECT `id` FROM `approval_request_types` WHERE `type_name` = 'salary_increment' LIMIT 1");
if (!$type_query || mysqli_num_rows($type_query) == 0) {
    die("CRITICAL ERROR: 'salary_increment' type not found in `approval_request_types` table.");
}
$request_type_id = (int)mysqli_fetch_assoc($type_query)['id'];

$all_statuses = [
    'my_pending' => __('my_pending_queue'),
    'submitted_by_me' => (function_exists('__') ? __('submitted_by_me', 'Submitted By Me') : 'Submitted By Me'),
    'pending_approval' => __('all_pending'),
    'approved' => __('approved'),
    'rejected' => __('rejected'),
    'all' => __('all_requests')
];

$search_term = $_GET['search'] ?? '';
$limit_options = [9, 12, 15];
$perpage = 9;
$items_per_page = isset($_GET['limit']) && in_array((int)$_GET['limit'], $limit_options) ? (int)$_GET['limit'] : $perpage;
$show_all = isset($_GET['limit']) && $_GET['limit'] == 'all';
if ($show_all) {
    $items_per_page = -1;
}

$current_page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
if ($current_page < 1) {
    $current_page = 1;
}

$current_filter = $_GET['status'] ?? null;
if ($current_filter === null) {
    $current_filter = ($is_system_admin ?? false) ? 'all' : 'my_pending';
}

$where_clauses = [];
$params = [];
$types = "";
$join_sql = "";
$dept_filter_applied = false;
$isFinanceRole = (isset($user_type) && stripos($user_type, 'finance') !== false);
$isHRPayrollRole = (isset($user_type) && stripos($user_type, 'hr_payroll') !== false);
$isGMRole = (isset($user_type) && strtolower((string)$user_type) === 'gm');
$can_see_all_depts = ($is_system_admin ?? false) || ($isHR ?? false) || $isFinanceRole;

$page_title = $all_statuses[$current_filter] ?? __('all_requests');

if ($current_filter === 'my_pending') {
    $join_sql .= " JOIN `request_approvers` ra ON ra.request_inv_no = si.request_inv_no AND ra.request_type_id = ? ";
    $params[] = $request_type_id;
    $types .= "i";

    $where_clauses[] = "ra.approver_id = ?";
    $params[] = $empid;
    $types .= "i";

    $where_clauses[] = "ra.status = 'pending'";
    $where_clauses[] = "si.current_status = 'pending_approval'";
} elseif ($current_filter === 'submitted_by_me') {
    $where_clauses[] = "si.submitted_by = ?";
    $params[] = $empid;
    $types .= "s";
} elseif (in_array($current_filter, ['pending_approval', 'approved', 'rejected'], true)) {
    $where_clauses[] = "si.current_status = ?";
    $params[] = $current_filter;
    $types .= "s";
    if ($current_filter === 'approved' && empty($search_term)) {
        $where_clauses[] = "si.created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)";
    }
} elseif ($current_filter === 'all' && empty($search_term)) {
    $where_clauses[] = "(si.current_status != 'approved' OR si.created_at >= DATE_SUB(CURDATE(), INTERVAL 15 DAY))";
}

if (!empty($search_term)) {
    $where_clauses[] = "(e.name LIKE ? OR si.emp_id LIKE ? OR si.request_inv_no LIKE ? OR si.reason LIKE ?)";
    $search_param = "%{$search_term}%";
    array_push($params, $search_param, $search_param, $search_param, $search_param);
    $types .= "ssss";
}

if (!$can_see_all_depts && !$dept_filter_applied && $current_filter !== 'my_pending' && $current_filter !== 'submitted_by_me') {
    $where_clauses[] = "(e.dept = ? OR si.submitted_by = ? OR EXISTS (SELECT 1 FROM request_approvers ra_any WHERE ra_any.request_inv_no = si.request_inv_no AND ra_any.request_type_id = ? AND ra_any.approver_id = ?))";
    array_push($params, $user_dept, $empid, $request_type_id, $empid);
    $types .= "isii";
    $dept_filter_applied = true;
}

$where_sql = "";
if (!empty($where_clauses)) {
    $where_sql = " WHERE " . implode(" AND ", $where_clauses);
}

$company_filter = getCompanyFilterSQL('e.comp_no', true);
$department_filter = getDepartmentFilterSQL('e.dept', true);
$employee_filter = getEmployeeFilterSQL('e.emp_id', true);
if ($current_filter !== 'my_pending' && $current_filter !== 'submitted_by_me') {
    if (strpos($where_sql, 'WHERE') === false) {
        $where_sql = " WHERE 1=1" . $company_filter . $department_filter . $employee_filter;
    } else {
        $where_sql .= $company_filter . $department_filter . $employee_filter;
    }
}

$base_query = "FROM emp_salary_increment si
               JOIN employees e ON si.emp_id = e.emp_id
               $join_sql
               $where_sql";

$count_sql = "SELECT COUNT(DISTINCT si.id) as total " . $base_query;
$total_items = 0;

$stmt_count = $conDB->prepare($count_sql);
if (!$stmt_count) {
    die("Count query prepare failed: " . $conDB->error);
}
if (!empty($params)) {
    $stmt_count->bind_param($types, ...$params);
}
$stmt_count->execute();
$total_items = $stmt_count->get_result()->fetch_assoc()['total'] ?? 0;
$stmt_count->close();

$total_pages = $show_all ? 1 : ceil($total_items / $items_per_page);
if ($current_page > $total_pages && $total_pages > 0) {
    $current_page = $total_pages;
}

$requests = [];
if ($total_items > 0) {
    $sql = "SELECT
        si.*,
        e.name as employee_name,
        e.dept,
        sup.name as submitted_by_name,
        ra_pending.approver_id as current_approver_id,
        ra_pending.approval_level as current_approval_level,
        approver_emp.name as current_approver_name,
        ra_rejected.note as rejection_note
    FROM emp_salary_increment si
    JOIN employees e ON si.emp_id = e.emp_id
    LEFT JOIN employees sup ON si.submitted_by = sup.emp_id
    LEFT JOIN request_approvers ra_pending ON ra_pending.request_inv_no = si.request_inv_no AND ra_pending.request_type_id = ? AND ra_pending.status = 'pending'
    LEFT JOIN employees approver_emp ON ra_pending.approver_id = approver_emp.emp_id
    LEFT JOIN request_approvers ra_rejected ON ra_rejected.request_inv_no = si.request_inv_no AND ra_rejected.request_type_id = ? AND ra_rejected.status = 'rejected'
    $join_sql
    $where_sql";
    $sql .= " GROUP BY si.id ORDER BY si.created_at DESC";

    $main_params = $params;
    $main_types = $types;
    array_unshift($main_params, $request_type_id);
    array_unshift($main_params, $request_type_id);
    $main_types = "ii" . $main_types;

    if (!$show_all) {
        $offset = ($current_page - 1) * $items_per_page;
        $sql .= " LIMIT ?, ?";
        array_push($main_params, $offset, $items_per_page);
        $main_types .= "ii";
    }

    $stmt = $conDB->prepare($sql);
    if (!$stmt) {
        die("Main query prepare failed: " . $conDB->error);
    }
    if (!empty($main_params)) {
        $stmt->bind_param($main_types, ...$main_params);
    }
    if (!$stmt->execute()) {
        die("Main query execute failed: " . $stmt->error);
    }

    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $requests[] = $row;
        }
    }
    $stmt->close();
}

if ($can_see_all_depts) {
    $unfiltered_sql = "SELECT COUNT(id) as total FROM emp_salary_increment";
    $unfiltered_result = mysqli_query($conDB, $unfiltered_sql);
    $unfiltered_total_items = ($unfiltered_result && ($row_unf = mysqli_fetch_assoc($unfiltered_result))) ? ($row_unf['total'] ?? 0) : 0;
} else {
    $unfiltered_sql = "SELECT COUNT(si.id) as total FROM emp_salary_increment si JOIN employees e ON si.emp_id = e.emp_id WHERE (e.dept = ? OR si.submitted_by = ? OR EXISTS (SELECT 1 FROM request_approvers ra_any WHERE ra_any.request_inv_no = si.request_inv_no AND ra_any.request_type_id = ? AND ra_any.approver_id = ?))";
    if ($stmt_unf = $conDB->prepare($unfiltered_sql)) {
        $stmt_unf->bind_param('isii', $user_dept, $empid, $request_type_id, $empid);
        $stmt_unf->execute();
        $res_unf = $stmt_unf->get_result();
        $unfiltered_total_items = ($res_unf && ($row_unf = $res_unf->fetch_assoc())) ? ($row_unf['total'] ?? 0) : 0;
        $stmt_unf->close();
    } else {
        $unfiltered_total_items = 0;
    }
}
?>
<!doctype html>
<html lang="<?= $current_lang ?? 'en' ?>" <?= ($is_rtl ?? false) ? 'dir="rtl"' : '' ?>>
<head>
    <meta charset="utf-8" />
    <title><?= $site_title ?? 'System' ?> - <?= __('salary_increment_requests', 'Salary Increment Requests') ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta content="Anees Afzal" name="author" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <link rel="shortcut icon" href="<?= get_setting($conDB, 'favicon') ?>">
    <link href="assets/css/bootstrap.min.css" rel="stylesheet" type="text/css" />
    <link href="plugins/bootstrap-datepicker/css/bootstrap-datepicker.min.css" rel="stylesheet" type="text/css" />
    <link href="assets/css/metismenu.min.css" rel="stylesheet" type="text/css" />
    <link href="assets/css/style.css" rel="stylesheet" type="text/css" />
    <link href="assets/css/style_dark.css" rel="stylesheet" type="text/css" />
    <script src="assets/js/modernizr.min.js"></script>
    <style>
        .filter-controls { max-width: 800px; }
        .request-card { border-radius: 15px; border: none; box-shadow: 0 10px 30px rgba(0, 0, 0, 0.07); transition: transform 0.3s ease, box-shadow 0.3s ease; }
        .request-card:hover { transform: translateY(-5px); box-shadow: 0 15px 40px rgba(0, 0, 0, 0.1); }
        .request-card .card-header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border-bottom: none; font-weight: 600; font-size: 1.1em; border-top-left-radius: 15px; border-top-right-radius: 15px; }
        .request-card .card-header .float-right { font-size: 0.85em; opacity: 0.9; }
        .request-card .card-header .btn, .request-card .card-header .dropdown-toggle { color: #212529 !important; }
        .request-card .card-body { padding: 1.5rem; }
        .detail-item { display: flex; align-items: center; margin-bottom: 1rem; font-size: 1.03em; }
        .detail-item i { color: #4a90e2; margin-right: 15px; width: 20px; text-align: center; }
        .detail-item strong { color: #8a94a6; min-width: 140px; display: inline-block; }
        .request-card .card-footer { background-color: #fafbff; border-top: 1px solid #eef; }
        .no-requests { padding: 3rem; background: #fff; border-radius: 15px; box-shadow: 0 10px 30px rgba(0, 0, 0, 0.07); }
        .btn-block + .btn-block { margin-top: 0rem !important; }
        .detail-item { flex-direction: <?= ($is_rtl) ? 'row-reverse !important' : 'row !important' ?>; text-align: <?= ($is_rtl) ? 'right !important' : 'left !important' ?>; }
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
                <?php include("./includes/main_menu.php"); ?>
                <div class="clearfix"></div>
            </div>
        </div>

        <div class="content-page">
            <?php include("./includes/topbar.php"); ?>
            <div class="content">
                <div class="container-fluid">
                    <div class="row">
                        <div class="col-xl-12">
                            <div class="card-box">
                                <h4 class="header-title m-t-0 m-b-30"><?= __('salary_increment_approval_center', 'Salary Increment Approval Center') ?></h4>

                                <div class="row filter-controls mx-auto mb-5">
                                    <div class="col-md-6 mb-3 mb-md-0">
                                        <div class="form-group">
                                            <label for="statusFilter" class="font-weight-bold"><?= __('filter_by_status') ?></label>
                                            <select class="form-control" id="statusFilter" onchange="applyFilters()">
                                                <?php foreach ($all_statuses as $status_key => $status_value): ?>
                                                    <option value="<?= $status_key; ?>" <?php if ($current_filter == $status_key) echo 'selected'; ?>>
                                                        <?= htmlspecialchars($status_value); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label for="searchFilter" class="font-weight-bold"><?= __('search_by_name_id') ?></label>
                                            <div class="input-group">
                                                <input type="search" class="form-control" id="searchFilter" placeholder="<?= __('enter_search_term') ?>" value="<?= htmlspecialchars($search_term); ?>">
                                                <div class="input-group-append">
                                                    <button class="btn btn-primary" type="button" onclick="applyFilters()"><i class="fas fa-search"></i></button>
                                                </div>
                                                <?php if (!empty($search_term) || $current_filter !== 'my_pending'): ?>
                                                <div class="input-group-append">
                                                    <button class="btn btn-danger" type="reset" onclick="resetFilters(<?= $perpage ?>)"><i class="fas fa-times"></i></button>
                                                </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="d-flex justify-content-between align-items-center mb-4">
                                    <h4 class="mb-0 text-muted"><?= __('showing') ?>: <?= htmlspecialchars($page_title); ?></h4>
                                    <span class="badge badge-light p-2"><?= __('total_found') ?>: <?= $total_items; ?></span>
                                </div>

                                <?php if (!empty($requests)): ?>
                                    <div class="row">
                                        <?php foreach ($requests as $req): ?>
                                            <?php
                                            $status_badge_class = 'secondary';
                                            $status_text = '';
                                            $current_level_display = '';

                                            if (!empty($req['current_approver_name']) && $req['current_status'] === 'pending_approval') {
                                                $status_text = __('pending_with') . ' ' . getDisplayName(parseName($req['current_approver_name']));
                                                $status_badge_class = 'warning';
                                                if (!empty($req['current_approval_level'])) {
                                                    $current_level_display = ' (Level ' . (int)$req['current_approval_level'] . ')';
                                                }
                                            } elseif ($req['current_status'] === 'approved') {
                                                $status_text = __('approved');
                                                $status_badge_class = 'success';
                                            } elseif ($req['current_status'] === 'rejected') {
                                                $status_text = __('rejected');
                                                $status_badge_class = 'danger';
                                            } elseif ($req['current_status'] === 'cancelled') {
                                                $status_text = __('cancelled', 'Cancelled');
                                                $status_badge_class = 'secondary';
                                            } else {
                                                $status_text = __('pending_approval');
                                                $status_badge_class = 'warning';
                                            }

                                            $can_take_action = ((int)($req['current_approver_id'] ?? 0) === (int)$empid) && (($req['current_status'] ?? '') === 'pending_approval');
                                            $can_cancel_self = ((string)($req['submitted_by'] ?? '') === (string)$empid) && (($req['current_status'] ?? '') === 'pending_approval');
                                            ?>
                                            <div class="col-lg-4 col-md-6 mb-4">
                                                <div class="card request-card h-100">
                                                    <div class="card-header d-flex justify-content-between align-items-center">
                                                        <span><?= getDisplayName(parseName($req['employee_name'])); ?></span>
                                                        <div class="d-flex align-items-center card-header-actions" style="gap: 8px;">
                                                            <span><?= __('emp_id') ?>: <?= htmlspecialchars((string)$req['emp_id']); ?></span>
                                                        </div>
                                                    </div>
                                                    <div class="card-body">
                                                        <div class="detail-item"><i class="fa fa-hashtag"></i><strong><?= __('request_id') ?>:</strong> <?= htmlspecialchars((string)$req['request_inv_no']); ?></div>
                                                        <div class="detail-item"><i class="fa fa-arrow-trend-up"></i><strong><?= __('increment_amount', 'Increment Amount') ?>:</strong> <?= number_format((float)$req['increment_amount'], 2); ?></div>
                                                        <?php if ($req['approved_amount'] !== null): ?>
                                                            <div class="detail-item"><i class="fa fa-check-circle"></i><strong><?= __('approved_amount', 'Approved Amount') ?>:</strong> <?= number_format((float)$req['approved_amount'], 2); ?></div>
                                                        <?php endif; ?>
                                                        <div class="detail-item"><i class="fa fa-clipboard-check"></i><strong><?= __('evaluation_score', 'Evaluation Score') ?>:</strong> <?= $req['evaluation_score'] !== null ? number_format((float)$req['evaluation_score'], 2) : '-'; ?></div>
                                                        <div class="detail-item"><i class="fa fa-user-tie"></i><strong><?= __('submitted_by', 'Submitted By') ?>:</strong> <?= htmlspecialchars((string)($req['submitted_by_name'] ?? $req['submitted_by'])); ?></div>
                                                        <div class="detail-item"><i class="fa fa-info-circle"></i><strong><?= __('reason', 'Reason') ?>:</strong> <?= htmlspecialchars((string)($req['reason'] ?? '')); ?></div>
                                                        <div class="detail-item"><i class="fa fa-stream"></i><strong><?= __('status') ?>:</strong> <span class="badge badge-<?= $status_badge_class; ?> p-2"><?= htmlspecialchars($status_text . $current_level_display); ?></span></div>

                                                        <?php if (($req['current_status'] ?? '') === 'rejected' && !empty($req['rejection_note'])): ?>
                                                            <div class="detail-item" style="margin-top: 12px; padding: 10px; background-color: #f8d7da; border-left: 3px solid #dc3545; border-radius: 4px;">
                                                                <i class="fas fa-ban" style="color:#dc3545; margin-right:8px;"></i>
                                                                <strong><?= __('rejection_reason') ?>:</strong>
                                                                <?= nl2br(htmlspecialchars(getDisplayName((string)$req['rejection_note']))); ?>
                                                            </div>
                                                        <?php endif; ?>
                                                    </div>
                                                    <div class="card-footer d-flex justify-content-between align-items-center" style="gap: 0.5rem;">
                                                        <button type="button" class="btn btn-info btn-block waves-effect" onclick="viewSalaryIncrementReport('<?= htmlspecialchars((string)$req['request_inv_no'], ENT_QUOTES); ?>')">
                                                            <i class="fa fa-eye"></i> <?= __('view_report') ?>
                                                        </button>
                                                        <div class="btn-group flex-fill" style="position: relative; z-index: 1000;">
                                                            <button type="button" class="btn btn-secondary dropdown-toggle btn-block waves-effect" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                                                <?= __('actions') ?> <span class="caret"></span>
                                                            </button>
                                                            <div class="dropdown-menu dropdown-menu-right" style="z-index: 1050; position: absolute;">
                                                                <a class="dropdown-item" href="salary_increment_status_history.php?request_inv_no=<?= urlencode($req['request_inv_no']); ?>" target="_blank">
                                                                    <i class="fa fa-history"></i> <?= __('history') ?>
                                                                </a>
                                                                <?php if ($can_take_action): ?>
                                                                    <div class="dropdown-divider"></div>
                                                                    <button type="button" class="dropdown-item" style="cursor: pointer; background: none; border: none; width: 100%; text-align: left;" onclick="approveSalaryIncrementRequest('<?= htmlspecialchars((string)$req['request_inv_no'], ENT_QUOTES); ?>', '<?= htmlspecialchars((string)$req['employee_name'], ENT_QUOTES); ?>', '<?= number_format((float)$req['increment_amount'], 2); ?>')">
                                                                        <i class="fa fa-check text-success"></i> <?= $isHRPayrollRole ? __('submit_salary_increment_request', 'Submit') : __('approve') ?>
                                                                    </button>
                                                                    <button type="button" class="dropdown-item" style="cursor: pointer; background: none; border: none; width: 100%; text-align: left;" onclick="rejectSalaryIncrementRequest('<?= htmlspecialchars((string)$req['request_inv_no'], ENT_QUOTES); ?>', '<?= htmlspecialchars((string)$req['employee_name'], ENT_QUOTES); ?>', '<?= number_format((float)$req['increment_amount'], 2); ?>')">
                                                                        <i class="fa fa-times text-danger"></i> <?= __('reject') ?>
                                                                    </button>
                                                                <?php endif; ?>
                                                                <?php if ($can_cancel_self && in_array($req['current_status'] ?? '', $cancellable_salary_increment_statuses, true)): ?>
                                                                    <div class="dropdown-divider"></div>
                                                                    <button type="button" class="dropdown-item" style="cursor: pointer; background: none; border: none; width: 100%; text-align: left;" onclick="cancelSalaryIncrementSelf('<?= htmlspecialchars((string)$req['request_inv_no'], ENT_QUOTES); ?>', '<?= htmlspecialchars((string)$req['employee_name'], ENT_QUOTES); ?>')">
                                                                        <i class="fa fa-ban text-danger"></i> <?= __('cancel', 'Cancel') ?>
                                                                    </button>
                                                                <?php endif; ?>
                                                                <?php if ($can_cancel_salary_increment_requests && !$can_cancel_self && in_array($req['current_status'] ?? '', $cancellable_salary_increment_statuses, true)): ?>
                                                                    <div class="dropdown-divider"></div>
                                                                    <button type="button" class="dropdown-item" style="cursor: pointer; background: none; border: none; width: 100%; text-align: left;" onclick="cancelSalaryIncrementAdmin('<?= htmlspecialchars((string)$req['request_inv_no'], ENT_QUOTES); ?>', '<?= htmlspecialchars((string)$req['employee_name'], ENT_QUOTES); ?>')">
                                                                        <i class="fa fa-ban text-danger"></i> <?= __('cancel', 'Cancel') ?>
                                                                    </button>
                                                                <?php endif; ?>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>

                                    <?php
                                    $pagination_params = [];
                                    if (!empty($search_term)) $pagination_params['search'] = $search_term;
                                    if (!empty($current_filter)) $pagination_params['status'] = $current_filter;
                                    echo generate_pagination_controls($current_page, $total_pages, $total_items, $items_per_page, $limit_options, $show_all, $pagination_params, $unfiltered_total_items);
                                    ?>
                                <?php else: ?>
                                    <div class="row justify-content-center">
                                        <div class="col-md-8">
                                            <div class="text-center no-requests">
                                                <i class="fas fa-arrow-trend-up fa-3x text-muted mb-3"></i>
                                                <h2><?= __('no_salary_increment_requests_found', 'No salary increment requests found') ?></h2>
                                                <p class="text-muted"><?= __('no_requests_matching_filters') ?></p>
                                            </div>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
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
    <script src="plugins/bootstrap-datepicker/js/bootstrap-datepicker.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="assets/js/jquery.core.js"></script>
    <script src="assets/js/jquery.app.js?t=<?= time() ?>"></script>
    <script>
        const IS_HR_PAYROLL_APPROVER = <?= $isHRPayrollRole ? 'true' : 'false' ?>;
        const IS_GM_APPROVER = <?= $isGMRole ? 'true' : 'false' ?>;
        const SALARY_INCREMENT_MAX_AMOUNT = <?= json_encode((float)get_setting_num($conDB, 'salary_increment_max_amount', 2000)) ?>;

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

        function applyFilters() {
            const status = document.getElementById('statusFilter').value;
            const limitElement = document.getElementById('limitFilter');
            const limit = limitElement ? limitElement.value : <?= $perpage ?>;
            const search = document.getElementById('searchFilter').value;
            const baseUrl = window.location.href.split('?')[0];
            window.location.href = `${baseUrl}?status=${status}&limit=${limit}&search=${encodeURIComponent(search)}&page=1`;
        }

        document.getElementById('searchFilter').addEventListener('keypress', function (e) {
            if (e.key === 'Enter') {
                applyFilters();
            }
        });

        function approveSalaryIncrementRequest(requestInvNo, employeeName, amount) {
            const showLastIncrementDate = IS_HR_PAYROLL_APPROVER && !IS_GM_APPROVER;
            const showGMFields = IS_GM_APPROVER;
            const rawAmount = parseFloat(String(amount).replace(/,/g, '')) || 0;
            Swal.fire({
                title: __('confirm_approval') || 'Confirm Approval',
                html: `<div class="text-left">
                        <p><strong>${__('employee') || 'Employee'}:</strong> ${employeeName}</p>
                        <p><strong>${__('increment_amount', 'Increment Amount') || 'Increment Amount'}:</strong> ${amount}</p>
                        ${showGMFields ? `<div class="form-group">
                            <label>${__('approved_amount', 'Approved Amount')} <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" min="0.01" max="${SALARY_INCREMENT_MAX_AMOUNT}" id="si_gm_approved_amount" class="form-control" value="${rawAmount}">
                        </div>
                        <div class="form-group">
                            <label>${__('increment_effective_date') || 'Increment Effective Date'} <span class="text-danger">*</span></label>
                            <input type="text" id="si_gm_effective_date" class="form-control" placeholder="YYYY-MM-DD" autocomplete="off">
                        </div>` : ''}
                        ${showLastIncrementDate ? `<div class="form-group">
                            <label>${__('last_increment_date') || 'Date of Last Increment (Optional)'}</label>
                            <input type="text" id="si_last_increment_date" class="form-control" placeholder="YYYY-MM-DD" autocomplete="off">
                        </div>` : ''}
                        <div class="form-group">
                            <label>${__('add_approval_comment') || 'Approval Comment (Optional)'}</label>
                            <textarea id="si_approval_comment" class="form-control" rows="3" placeholder="${__('enter_comment_here') || 'Enter comment...'}"></textarea>
                        </div>
                    </div>`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: APP_COLORS.success,
                cancelButtonColor: APP_COLORS.danger,
                confirmButtonText: showLastIncrementDate ? (__('submit_salary_increment_request') || 'Submit') : (__('approve') || 'Approve'),
                cancelButtonText: __('cancel') || 'Cancel',
                showLoaderOnConfirm: true,
                allowOutsideClick: false,
                didOpen: () => {
                    const $lastIncrementDate = $('#si_last_increment_date');
                    if ($lastIncrementDate.length && typeof $lastIncrementDate.datepicker === 'function') {
                        $lastIncrementDate.datepicker({
                            format: 'yyyy-mm-dd',
                            autoclose: true,
                            todayHighlight: true,
                            endDate: '0d'
                        });
                    }
                    const $gmEffectiveDate = $('#si_gm_effective_date');
                    if ($gmEffectiveDate.length && typeof $gmEffectiveDate.datepicker === 'function') {
                        $gmEffectiveDate.datepicker({
                            format: 'yyyy-mm-dd',
                            autoclose: true,
                            todayHighlight: true,
                            startDate: '0d'
                        });
                    }
                },
                preConfirm: () => {
                    const approvalComment = (document.getElementById('si_approval_comment') || {}).value || '';
                    const lastIncrementDate = showLastIncrementDate ? ((document.getElementById('si_last_increment_date') || {}).value || '') : '';

                    let approvedAmount = '';
                    let gmEffectiveDate = '';
                    if (showGMFields) {
                        approvedAmount = (document.getElementById('si_gm_approved_amount') || {}).value || '';
                        gmEffectiveDate = (document.getElementById('si_gm_effective_date') || {}).value || '';

                        if (approvedAmount === '' || parseFloat(approvedAmount) <= 0 || parseFloat(approvedAmount) > SALARY_INCREMENT_MAX_AMOUNT) {
                            Swal.showValidationMessage((__('approved_amount_required') || 'Approved amount is required and must be between 0 and {max}.').replace('{max}', SALARY_INCREMENT_MAX_AMOUNT));
                            return false;
                        }
                        if (gmEffectiveDate === '') {
                            Swal.showValidationMessage(__('increment_effective_date_required') || 'Increment effective date is required.');
                            return false;
                        }
                        const todayStr = new Date().toISOString().slice(0, 10);
                        if (gmEffectiveDate < todayStr) {
                            Swal.showValidationMessage(__('increment_effective_date_must_be_future') || 'Increment effective date cannot be a past date.');
                            return false;
                        }
                    }

                    return new Promise((resolve, reject) => {
                        $.ajax({
                            url: './includes/ajaxFile/ajaxSalaryIncrement.php',
                            type: 'POST',
                            dataType: 'JSON',
                            data: {
                                ajaxType: 'approveSalaryIncrement',
                                request_inv_no: requestInvNo,
                                approval_comment: approvalComment,
                                last_increment_date: lastIncrementDate,
                                approved_amount: approvedAmount,
                                increment_effective_date: gmEffectiveDate
                            },
                            success: function (response) {
                                if (response.status === 'success') {
                                    resolve(response);
                                } else {
                                    reject(response.message || 'Approval failed');
                                }
                            },
                            error: function () {
                                reject('Failed to process approval request.');
                            }
                        });
                    }).catch(error => {
                        Swal.showValidationMessage(error);
                    });
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    Swal.fire({
                        title: result.value.title || 'Approved',
                        text: result.value.message || 'Salary increment request approved successfully.',
                        icon: 'success',
                        confirmButtonColor: APP_COLORS.success,
                        allowOutsideClick: false
                    }).then(() => location.reload());
                }
            });
        }

        function rejectSalaryIncrementRequest(requestInvNo, employeeName, amount) {
            Swal.fire({
                title: __('confirm_rejection') || 'Confirm Rejection',
                html: `<div class="text-left">
                        <p><strong>${__('employee') || 'Employee'}:</strong> ${employeeName}</p>
                        <p><strong>${__('increment_amount', 'Increment Amount') || 'Increment Amount'}:</strong> ${amount}</p>
                    </div>`,
                input: 'textarea',
                inputLabel: __('provide_rejection_reason') || 'Provide rejection reason',
                inputPlaceholder: __('enter_reason_here') || 'Enter rejection reason...',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: APP_COLORS.danger,
                cancelButtonColor: APP_COLORS.secondary,
                confirmButtonText: __('reject') || 'Reject',
                cancelButtonText: __('cancel') || 'Cancel',
                showLoaderOnConfirm: true,
                allowOutsideClick: false,
                preConfirm: (rejectionReason) => {
                    if (!rejectionReason || rejectionReason.trim() === '') {
                        Swal.showValidationMessage(__('provide_rejection_reason') || 'Rejection reason is required');
                        return false;
                    }

                    return new Promise((resolve, reject) => {
                        $.ajax({
                            url: './includes/ajaxFile/ajaxSalaryIncrement.php',
                            type: 'POST',
                            dataType: 'JSON',
                            data: {
                                ajaxType: 'rejectSalaryIncrement',
                                request_inv_no: requestInvNo,
                                rejection_reason: rejectionReason.trim()
                            },
                            success: function (response) {
                                if (response.status === 'success') {
                                    resolve(response);
                                } else {
                                    reject(response.message || 'Rejection failed');
                                }
                            },
                            error: function () {
                                reject('Failed to process rejection request.');
                            }
                        });
                    }).catch(error => {
                        Swal.showValidationMessage(error);
                    });
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    Swal.fire({
                        title: result.value.title || 'Rejected',
                        text: result.value.message || 'Salary increment request rejected successfully.',
                        icon: 'success',
                        confirmButtonColor: APP_COLORS.success
                    }).then(() => location.reload());
                }
            });
        }

        function cancelSalaryIncrementSelf(requestInvNo, employeeName) {
            Swal.fire({
                title: __('cancel_salary_increment_request', 'Cancel Salary Increment Request'),
                html: `<p>${__('confirm_cancel_salary_increment_for', 'Are you sure you want to cancel the salary increment request for')} <strong>${employeeName}</strong>?</p>`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: APP_COLORS.danger,
                cancelButtonColor: APP_COLORS.secondary,
                confirmButtonText: __('yes_cancel') || 'Yes, Cancel',
                cancelButtonText: __('cancel') || 'Cancel',
                showLoaderOnConfirm: true,
                allowOutsideClick: false,
                preConfirm: () => {
                    return new Promise((resolve, reject) => {
                        $.ajax({
                            url: './includes/ajaxFile/ajaxSalaryIncrement.php',
                            type: 'POST',
                            dataType: 'JSON',
                            data: {
                                ajaxType: 'cancelSalaryIncrementSelf',
                                request_inv_no: requestInvNo
                            },
                            success: function (response) {
                                if (response.status === 'success') {
                                    resolve(response);
                                } else {
                                    reject(response.message || 'Cancellation failed');
                                }
                            },
                            error: function () {
                                reject('Failed to process cancellation request.');
                            }
                        });
                    }).catch(error => {
                        Swal.showValidationMessage(error);
                    });
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    Swal.fire({
                        title: result.value.title || 'Cancelled',
                        text: result.value.message || 'Salary increment request cancelled successfully.',
                        icon: 'success',
                        confirmButtonColor: APP_COLORS.success
                    }).then(() => location.reload());
                }
            });
        }

        function cancelSalaryIncrementAdmin(requestInvNo, employeeName) {
            Swal.fire({
                title: __('cancel_salary_increment_request', 'Cancel Salary Increment Request'),
                html: `<p>${__('confirm_cancel_salary_increment_for', 'Are you sure you want to cancel the salary increment request for')} <strong>${employeeName}</strong>?</p>`,
                input: 'textarea',
                inputLabel: __('cancellation_reason') || 'Cancellation reason',
                inputPlaceholder: __('enter_cancellation_reason_placeholder') || 'Enter reason for cancelling this request',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: APP_COLORS.danger,
                cancelButtonColor: APP_COLORS.secondary,
                confirmButtonText: __('yes_cancel') || 'Yes, Cancel',
                cancelButtonText: __('cancel') || 'Cancel',
                showLoaderOnConfirm: true,
                allowOutsideClick: false,
                preConfirm: (cancellationNote) => {
                    if (!cancellationNote || cancellationNote.trim() === '') {
                        Swal.showValidationMessage(__('cancellation_reason_required_validation') || 'Cancellation reason is required');
                        return false;
                    }

                    return new Promise((resolve, reject) => {
                        $.ajax({
                            url: './includes/ajaxFile/ajaxSalaryIncrement.php',
                            type: 'POST',
                            dataType: 'JSON',
                            data: {
                                ajaxType: 'cancelSalaryIncrementAdmin',
                                request_inv_no: requestInvNo,
                                cancellation_note: cancellationNote.trim()
                            },
                            success: function (response) {
                                if (response.status === 'success') {
                                    resolve(response);
                                } else {
                                    reject(response.message || 'Cancellation failed');
                                }
                            },
                            error: function () {
                                reject('Failed to process cancellation request.');
                            }
                        });
                    }).catch(error => {
                        Swal.showValidationMessage(error);
                    });
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    Swal.fire({
                        title: result.value.title || 'Cancelled',
                        text: result.value.message || 'Salary increment request cancelled successfully.',
                        icon: 'success',
                        confirmButtonColor: APP_COLORS.success
                    }).then(() => location.reload());
                }
            });
        }

        function viewSalaryIncrementReport(requestInvNo) {
            const escapeHtml = (value) => String(value == null ? '' : value)
                .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;').replace(/'/g, '&#39;');

            const getStatusMeta = (statusValue) => {
                const normalized = String(statusValue || '').toLowerCase().replace(/_/g, ' ').trim();
                if (normalized.includes('approved')) return { icon: 'fa-check-circle', cls: 'text-success', bg: '#e9f9ee', label: __('approved', 'Approved') };
                if (normalized.includes('rejected')) return { icon: 'fa-times-circle', cls: 'text-danger', bg: '#fdeceb', label: __('rejected', 'Rejected') };
                if (normalized.includes('cancelled')) return { icon: 'fa-ban', cls: 'text-secondary', bg: '#f1f2f4', label: __('cancelled', 'Cancelled') };
                if (normalized.includes('pending')) return { icon: 'fa-hourglass-half', cls: 'text-warning', bg: '#fff8e6', label: __('pending', 'Pending') };
                if (normalized.includes('awaiting')) return { icon: 'fa-pause-circle', cls: 'text-info', bg: '#e8f4fd', label: __('awaiting', 'Awaiting') };
                return { icon: 'fa-circle', cls: 'text-secondary', bg: '#f1f2f4', label: normalized.replace(/\b\w/g, c => c.toUpperCase()) || __('unknown', 'Unknown') };
            };

            Swal.fire({
                title: __('loading', 'Loading...'),
                html: '<div style="padding:20px;"><i class="fa fa-spinner fa-spin fa-2x"></i></div>',
                showConfirmButton: false,
                allowOutsideClick: false,
                didOpen: () => Swal.showLoading()
            });

            $.ajax({
                url: './includes/ajaxFile/ajaxSalaryIncrement.php',
                dataType: 'JSON',
                type: 'POST',
                data: { ajaxType: 'getSalaryIncrementReport', request_inv_no: requestInvNo },
                success: function (res) {
                    if (!res || res.status !== 'success' || !res.request) {
                        Swal.fire('Error', (res && res.message) || 'Failed to load report.', 'error');
                        return;
                    }

                    const req = res.request;
                    const chain = Array.isArray(res.approval_chain) ? res.approval_chain : [];
                    const statusMeta = getStatusMeta(req.current_status);
                    const submittedDate = req.created_at ? new Date(req.created_at.replace(' ', 'T')) : null;
                    const submittedLabel = submittedDate && !isNaN(submittedDate.getTime()) ? submittedDate.toLocaleDateString(undefined, { year: 'numeric', month: 'short', day: 'numeric' }) : (req.created_at || '-');

                    let chainHtml = '<div style="padding:6px 2px;color:#8a94a6;font-size:13px;">' + (__('no_approvers', 'No approvers found')) + '</div>';
                    if (chain.length > 0) {
                        chainHtml = '<div style="display:flex;flex-direction:column;gap:8px;">' + chain.map(c => {
                            const cMeta = getStatusMeta(c.status);
                            const approverName = escapeHtml((c.approver_name && String(c.approver_name).trim() !== '') ? c.approver_name : ('Emp#' + c.approver_id));
                            const actionDate = c.action_date ? new Date(String(c.action_date).replace(' ', 'T')) : null;
                            const actionLabel = actionDate && !isNaN(actionDate.getTime()) ? actionDate.toLocaleDateString(undefined, { year: 'numeric', month: 'short', day: 'numeric' }) : '';
                            return '<div style="padding:8px 12px;background:' + cMeta.bg + ';border-radius:8px;">'
                                + '<div style="display:flex;align-items:center;justify-content:space-between;">'
                                + '<span style="font-size:13px;color:#333;"><strong style="color:#667eea;">' + (__('level', 'Level')) + ' ' + c.level + '</strong> &nbsp;' + approverName + '</span>'
                                + '<span class="' + cMeta.cls + '" style="font-size:12px;font-weight:600;white-space:nowrap;"><i class="fa ' + cMeta.icon + '"></i> ' + cMeta.label + '</span>'
                                + '</div>'
                                + (actionLabel ? '<div style="font-size:11px;color:#8a94a6;margin-top:4px;">' + actionLabel + '</div>' : '')
                                + (c.note ? '<div style="font-size:12px;color:#555;margin-top:4px;">' + escapeHtml(c.note) + '</div>' : '')
                                + '</div>';
                        }).join('') + '</div>';
                    }

                    const salary = res.salary_info || null;
                    const row = (label, value, opts) => {
                        opts = opts || {};
                        return '<div style="display:flex;justify-content:space-between;align-items:center;padding:7px 0;border-bottom:1px solid #eef0f4;">'
                            + '<span style="color:#8a94a6;font-size:13px;">' + label + '</span>'
                            + '<span style="font-size:13px;' + (opts.strong ? 'font-weight:700;' : '') + (opts.color ? 'color:' + opts.color + ';' : '') + '">' + value + '</span>'
                            + '</div>';
                    };

                    let salaryHtml = '<div style="padding:6px 2px;color:#8a94a6;font-size:13px;">' + (__('no_data_found', 'No data found')) + '</div>';
                    if (salary) {
                        const salaryRows = [
                            ['basic_salary', 'Basic Salary', salary.basic],
                            ['housing_allowance', 'Housing Allowance', salary.housing],
                            ['transport_allowance', 'Transport Allowance', salary.transport],
                            ['food_allowance', 'Food Allowance', salary.food],
                            ['miscellaneous_allowance', 'Miscellaneous Allowance', salary.misc],
                            ['cashier_allowance', 'Cashier Allowance', salary.cashier],
                            ['fuel_allowance', 'Fuel Allowance', salary.fuel],
                            ['telephone_allowance', 'Telephone Allowance', salary.tel],
                            ['other_allowance', 'Others', salary.other],
                            ['guard_allowance', 'Guard Allowance', salary.guard]
                        ];
                        salaryHtml = '<div style="display:flex;flex-direction:column;gap:2px;">'
                            + salaryRows.filter(([, , val]) => Number(val) > 0)
                                .map(([key, fallback, val]) => row(__(key, fallback), Number(val).toFixed(2))).join('')
                            + row(__('total_salary', 'Total salary'), Number(salary.total_salary).toFixed(2), { strong: true, color: '#667eea' })
                            + '</div>';
                    }

                    const html = `
                        <div class="vacation-form-container" style="text-align:left;">
                            <div class="row" style="margin:0 -8px;">
                                <div class="col-md-6" style="padding:0 8px;">
                                    <div class="vacation-card" style="height:100%;">
                                        <div class="vacation-card-header"><i class="fa fa-file-alt"></i> ${__('applied_information', 'Applied Information')}</div>
                                        <div style="display:flex;flex-direction:column;gap:2px;">
                                            ${row(__('request_id', 'Request ID'), '<code style="font-size:13px;">' + escapeHtml(req.request_inv_no) + '</code>')}
                                            ${row(__('employee', 'Employee'), escapeHtml(req.employee_name) + ' (' + escapeHtml(req.emp_id) + ')')}
                                            ${row(__('department', 'Department'), escapeHtml(req.department_name || '-'))}
                                            ${row(__('status', 'Status'), '<span class="' + statusMeta.cls + '" style="font-weight:600;font-size:13px;background:' + statusMeta.bg + ';padding:4px 10px;border-radius:20px;"><i class="fa ' + statusMeta.icon + '"></i> ' + statusMeta.label + '</span>')}
                                            ${row(__('increment_amount', 'Increment Amount'), Number(req.increment_amount).toFixed(2), { strong: true })}
                                            ${(req.approved_amount !== null && req.approved_amount !== undefined) ? row(__('approved_amount', 'Approved Amount'), Number(req.approved_amount).toFixed(2), { strong: true, color: '#28a745' }) : ''}
                                            ${row(__('evaluation_score', 'Evaluation Score'), req.evaluation_score !== null ? Number(req.evaluation_score).toFixed(2) : '-')}
                                            ${req.last_increment_date ? row(__('last_increment_date', 'Date of Last Increment (Optional)'), escapeHtml(req.last_increment_date)) : ''}
                                            ${row(__('submitted_by', 'Submitted By'), escapeHtml(req.submitted_by_name || req.submitted_by))}
                                            ${row(__('submitted_date', 'Submitted Date'), submittedLabel)}
                                        </div>
                                        ${req.reason ? `<div style="padding:8px 0 0;">
                                            <div style="color:#8a94a6;font-size:13px;margin-bottom:4px;">${__('reason', 'Reason')}</div>
                                            <div style="font-size:13px;color:#333;background:#f8f9fb;padding:8px 10px;border-radius:6px;">${escapeHtml(req.reason)}</div>
                                        </div>` : ''}
                                    </div>
                                </div>
                                <div class="col-md-6" style="padding:0 8px;">
                                    <div class="vacation-card" style="height:100%;">
                                        <div class="vacation-card-header"><i class="fa fa-money-bill-wave"></i> ${__('salary_information', 'Salary Information')}</div>
                                        ${salaryHtml}
                                    </div>
                                </div>
                            </div>

                            <div class="vacation-card">
                                <div class="vacation-card-header" style="cursor:pointer;display:flex;justify-content:space-between;align-items:center;" onclick="toggleSalaryIncrementReportChain()">
                                    <span><i class="fa fa-sitemap"></i> ${__('approval_chain', 'Approval Chain')}</span>
                                    <span id="siReportChainToggleIcon"><i class="fa fa-chevron-down"></i></span>
                                </div>
                                <div id="siReportChainBody" style="display:none;">
                                    ${chainHtml}
                                </div>
                            </div>
                        </div>
                    `;

                    Swal.fire({
                        title: '<i class="fa fa-arrow-trend-up" style="margin-right: 8px;"></i> ' + __('salary_increment_approval_history', 'Salary Increment Approval History'),
                        html: html,
                        showConfirmButton: false,
                        showCancelButton: true,
                        cancelButtonColor: APP_COLORS.danger_dark,
                        cancelButtonText: '<i class="fa fa-times"></i> ' + (__('close', 'Close')),
                        allowOutsideClick: false,
                        width: (window.innerWidth && window.innerWidth < 768) ? '95%' : '75%',
                        padding: '20px',
                        scrollbarPadding: false,
                        customClass: {
                            popup: 'vacation-modal-popup',
                            title: 'vacation-modal-title',
                            cancelButton: 'btn-modern-cancel'
                        }
                    });
                },
                error: function () {
                    Swal.fire('Error', 'Failed to load report.', 'error');
                }
            });
        }

        function toggleSalaryIncrementReportChain() {
            const body = document.getElementById('siReportChainBody');
            const icon = document.getElementById('siReportChainToggleIcon');
            if (!body || !icon) return;
            const isHidden = body.style.display === 'none';
            body.style.display = isHidden ? 'block' : 'none';
            icon.innerHTML = isHidden ? '<i class="fa fa-chevron-up"></i>' : '<i class="fa fa-chevron-down"></i>';
        }
    </script>
</body>
</html>
<?php
$conDB->close();
?>
