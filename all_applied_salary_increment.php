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

// A temp-role replacement can act on the original employee's pending approvals too -
// request_approvers.approver_id / current_approver_id still point at the original employee.
$delegatedFromEmpId = getDelegatedFromEmpId($conDB, $empid ?? '');

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
        ra_rejected.note as rejection_note,
        (SELECT ge.name FROM request_approvers rg
            JOIN admin_login gl ON gl.emp_id = rg.approver_id AND gl.user_type = 'gm'
            JOIN employees ge ON ge.emp_id = rg.approver_id
            WHERE rg.request_inv_no = si.request_inv_no AND rg.request_type_id = " . (int)$request_type_id . " AND rg.status = 'approved'
            ORDER BY rg.action_date DESC LIMIT 1) as gm_approver_name
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
    <link href="assets/css/icons.css" rel="stylesheet" type="text/css" />
    <link href="assets/css/metismenu.min.css" rel="stylesheet" type="text/css" />
    <link href="assets/css/style.css" rel="stylesheet" type="text/css" />
    <link href="assets/css/style_dark.css" rel="stylesheet" type="text/css" />
    <link href="./plugins/select2/css/select2.min.css" rel="stylesheet" type="text/css" />
    <link href="assets/css/smart_request.css?v=<?= @filemtime(__DIR__ . '/assets/css/smart_request.css') ?>" rel="stylesheet" type="text/css" />
    <script src="assets/js/modernizr.min.js"></script>
    <style>
        .detail-item { display: flex; align-items: center; margin-bottom: 1rem; font-size: 1.03em; }
        .detail-item i { color: #4a90e2; margin-right: 15px; width: 20px; text-align: center; }
        .detail-item strong { color: #8a94a6; min-width: 140px; display: inline-block; }
        .detail-item { flex-direction: <?= ($is_rtl) ? 'row-reverse !important' : 'row !important' ?>; text-align: <?= ($is_rtl) ? 'right !important' : 'left !important' ?>; }
        /* Approval popup: two columns side by side, stacked on narrow screens */
        .si-approve-grid { display: grid; grid-template-columns: minmax(0, 1fr) minmax(0, 1fr); gap: 14px; align-items: start; }
        .si-approve-col { min-width: 0; }
        .si-approve-col:empty { display: none; }
        .si-approve-col .sr-fsec:last-child { margin-bottom: 0; }
        .si-approve-grid .sr-table th, .si-approve-grid .sr-table td { padding: 7px 10px; }
        @media (max-width: 991px) { .si-approve-grid { grid-template-columns: minmax(0, 1fr); } }
        /* Request Summary block */
        .si-sum-head { display: flex; align-items: center; gap: 12px; }
        .si-sum-who { flex: 1 1 auto; min-width: 0; display: flex; flex-direction: column; gap: 2px; }
        .si-sum-who .sr-cell-title { font-size: 15px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .si-sum-eval { flex: 0 0 auto; text-align: center; padding: 6px 12px; border-radius: 10px; background: var(--sr-accent-soft); }
        .si-sum-eval span { display: block; font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: .4px; color: var(--sr-muted); }
        .si-sum-eval b { font-size: 16px; color: var(--sr-accent-strong); font-variant-numeric: tabular-nums; }
        .si-sum-tiles { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); border: 1px solid var(--sr-border); border-radius: 12px; overflow: hidden; background: var(--sr-surface-2); }
        .si-sum-tiles.is-single { grid-template-columns: minmax(0, 1fr); }
        .si-sum-tiles > div { padding: 10px 14px; min-width: 0; }
        .si-sum-tiles > div + div { border-inline-start: 1px solid var(--sr-border); }
        .si-sum-tiles > div > span { display: block; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .4px; color: var(--sr-muted); }
        .si-sum-tiles > div > b { display: block; margin-top: 2px; font-size: 18px; color: var(--sr-text); font-variant-numeric: tabular-nums; }
        .si-sum-tiles small { display: block; margin-top: 2px; font-size: 12px; color: var(--sr-muted); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .si-sum-tiles .is-approved { background: var(--tone-green-bg); }
        .si-sum-tiles .is-approved > span, .si-sum-tiles .is-approved > b { color: var(--tone-green-fg); }
        .si-sum-tiles .is-date { background: var(--tone-indigo-bg); }
        .si-sum-tiles .is-date > span, .si-sum-tiles .is-date > b { color: var(--tone-indigo-fg); }
        .si-gm-badge { display: inline-block; padding: 0 6px; border-radius: 6px; font-size: 10px; font-weight: 700; line-height: 16px; background: var(--tone-green-fg); color: #fff; vertical-align: 1px; }
        /* Report popup */
        .si-report-hero { display: flex; align-items: center; gap: 12px; padding: 12px 14px; margin-bottom: 12px; border: 1px solid var(--sr-border); border-radius: 12px; background: var(--sr-surface); }
        .si-report-hero .sr-avatar { width: 44px; height: 44px; font-size: 15px; }
        .si-report-tiles { grid-template-columns: repeat(4, minmax(0, 1fr)); margin-bottom: 12px; }
        .si-report .sr-activity { max-height: none; }
        .si-report .sr-activity-title { display: flex; flex-wrap: wrap; align-items: center; gap: 6px; }
        @media (max-width: 767px) { .si-report-tiles { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
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
            <?php require_once __DIR__ . '/includes/sr_list_helpers.php'; ?>
            <div class="content sr-page">
                <div class="container-fluid">

                    <div class="sr-head">
                        <div>
                            <h1><?= __('salary_increment_approval_center', 'Salary Increment Approval Center') ?></h1>
                            <p><?= sr_h($page_title) ?> &middot; <?= __('total_found') ?>: <?= (int)$total_items ?></p>
                        </div>
                    </div>

                    <div class="sr-card">
                        <?= sr_status_tabs($all_statuses, $current_filter, $total_items) ?>
                        <?= sr_list_toolbar($search_term, (!empty($search_term) || $current_filter !== 'my_pending') ? 'resetFilters(' . (int)$perpage . ')' : '') ?>

                        <?php if (!empty($requests)): ?>
                            <div class="sr-table-wrap sr-list-wrap">
                                <table class="table sr-table sr-list" style="width: 100%;">
                                    <thead>
                                        <tr>
                                            <th><?= __('employee', 'Employee') ?></th>
                                            <th><?= __('increment_amount', 'Increment Amount') ?></th>
                                            <th><?= __('evaluation_score', 'Evaluation Score') ?></th>
                                            <th><?= __('reason', 'Reason') ?></th>
                                            <th><?= __('status') ?></th>
                                            <th><?= __('applied') ?></th>
                                            <th class="text-right"><?= __('actions') ?></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($requests as $req): ?>
                                            <?php
                                            $status_tone = 'slate';
                                            $status_text = '';
                                            $current_level_display = '';

                                            if (!empty($req['current_approver_name']) && $req['current_status'] === 'pending_approval') {
                                                $status_text = __('pending_with') . ' ' . getDisplayName(parseName($req['current_approver_name']));
                                                $status_tone = 'amber';
                                                if (!empty($req['current_approval_level'])) {
                                                    $current_level_display = ' (Level ' . (int)$req['current_approval_level'] . ')';
                                                }
                                            } elseif ($req['current_status'] === 'approved') {
                                                $status_text = __('approved');
                                                $status_tone = 'green';
                                            } elseif ($req['current_status'] === 'rejected') {
                                                $status_text = __('rejected');
                                                $status_tone = 'red';
                                            } elseif ($req['current_status'] === 'cancelled') {
                                                $status_text = __('cancelled', 'Cancelled');
                                                $status_tone = 'slate';
                                            } else {
                                                $status_text = __('pending_approval');
                                                $status_tone = 'amber';
                                            }

                                            $can_take_action = ((int)($req['current_approver_id'] ?? 0) === (int)$empid || ($delegatedFromEmpId !== null && (int)($req['current_approver_id'] ?? 0) === (int)$delegatedFromEmpId)) && (($req['current_status'] ?? '') === 'pending_approval');
                                            $can_cancel_self = ((string)($req['submitted_by'] ?? '') === (string)$empid) && (($req['current_status'] ?? '') === 'pending_approval');
                                            $inv_js = htmlspecialchars((string)$req['request_inv_no'], ENT_QUOTES);
                                            $name_js = htmlspecialchars((string)$req['employee_name'], ENT_QUOTES);
                                            $amount_js = number_format((float)$req['increment_amount'], 2);
                                            $meta_js = htmlspecialchars(json_encode([
                                                'emp_id' => (string)$req['emp_id'],
                                                'evaluation_score' => $req['evaluation_score'] !== null ? (float)$req['evaluation_score'] : null,
                                                'approved_amount' => $req['approved_amount'] !== null ? (float)$req['approved_amount'] : null,
                                                'approved_by' => (string)($req['gm_approver_name'] ?? ''),
                                                'reason' => (string)($req['reason'] ?? ''),
                                                'submitted_by' => (string)($req['submitted_by_name'] ?? $req['submitted_by'] ?? ''),
                                            ], JSON_UNESCAPED_UNICODE), ENT_QUOTES);
                                            ?>
                                            <tr>
                                                <td><?= sr_person_cell(getDisplayName(parseName($req['employee_name'])), $req['emp_id']) ?></td>
                                                <td class="sr-nowrap">
                                                    <span class="sr-money"><i class="icon-saudi_riyal"></i> <?= number_format((float)$req['increment_amount'], 2) ?></span>
                                                    <?php if ($req['approved_amount'] !== null): ?>
                                                        <span class="sr-cell-sub" style="color: var(--tone-green-fg);"><i class="mdi mdi-check-circle"></i> <?= __('approved_amount', 'Approved Amount') ?>: <?= number_format((float)$req['approved_amount'], 2) ?><?php if (!empty($req['gm_approver_name'])): ?> · <?= __('gm', 'GM') ?>: <?= sr_h($req['gm_approver_name']) ?><?php endif; ?></span>
                                                    <?php endif; ?>
                                                    <span class="sr-cell-sub sr-mono"><?= sr_h($req['request_inv_no']) ?></span>
                                                </td>
                                                <td><span class="sr-chip"><?= $req['evaluation_score'] !== null ? number_format((float)$req['evaluation_score'], 2) : '-' ?></span></td>
                                                <td>
                                                    <div class="sr-wrap-text"><?= sr_h($req['reason'] ?? '') ?></div>
                                                    <span class="sr-cell-sub"><i class="mdi mdi-account"></i> <?= sr_h($req['submitted_by_name'] ?? $req['submitted_by']) ?></span>
                                                </td>
                                                <td>
                                                    <?= sr_pill($status_tone, $status_text . $current_level_display) ?>
                                                    <?php if (($req['current_status'] ?? '') === 'rejected' && !empty($req['rejection_note'])): ?>
                                                        <div class="sr-reject-note"><strong><?= __('rejection_reason') ?>:</strong> <?= nl2br(sr_h(getDisplayName((string)$req['rejection_note']))) ?></div>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?= sr_time_cell($req['created_at'] ?? '') ?></td>
                                                <td class="text-right">
                                                    <div class="sr-actions">
                                                        <?php if ($can_take_action): ?>
                                                            <a href="javascript:void(0);" class="sr-open-btn" onclick="approveSalaryIncrementRequest('<?= $inv_js ?>', '<?= $name_js ?>', '<?= $amount_js ?>', <?= $meta_js ?>)"><i class="mdi mdi-check"></i> <?= $isHRPayrollRole ? __('submit_salary_increment_request', 'Submit') : __('approve') ?></a>
                                                        <?php else: ?>
                                                            <a href="javascript:void(0);" class="sr-open-btn" onclick="viewSalaryIncrementReport('<?= $inv_js ?>')"><i class="mdi mdi-file-document"></i> <?= __('report') ?></a>
                                                        <?php endif; ?>
                                                        <div class="btn-group dropdown">
                                                            <a href="javascript:void(0);" class="sr-more-btn dropdown-toggle arrow-none" data-toggle="dropdown" data-boundary="viewport" aria-haspopup="true" aria-expanded="false"><i class="mdi mdi-dots-vertical"></i></a>
                                                            <div class="dropdown-menu dropdown-menu-right">
                                                                <?php if ($can_take_action): ?>
                                                                    <a class="dropdown-item" href="javascript:void(0);" onclick="viewSalaryIncrementReport('<?= $inv_js ?>')"><i class="mdi mdi-file-document"></i><?= __('report') ?></a>
                                                                <?php endif; ?>
                                                                <a class="dropdown-item" href="salary_increment_status_history.php?request_inv_no=<?= urlencode($req['request_inv_no']); ?>" target="_blank"><i class="mdi mdi-history"></i><?= __('history') ?></a>
                                                                <?php if ($can_take_action): ?>
                                                                    <div class="dropdown-divider"></div>
                                                                    <a class="dropdown-item" href="javascript:void(0);" onclick="rejectSalaryIncrementRequest('<?= $inv_js ?>', '<?= $name_js ?>', '<?= $amount_js ?>')"><i class="mdi mdi-close text-danger"></i><?= __('reject') ?></a>
                                                                <?php endif; ?>
                                                                <?php if ($can_cancel_self && in_array($req['current_status'] ?? '', $cancellable_salary_increment_statuses, true)): ?>
                                                                    <div class="dropdown-divider"></div>
                                                                    <a class="dropdown-item text-danger" href="javascript:void(0);" onclick="cancelSalaryIncrementSelf('<?= $inv_js ?>', '<?= $name_js ?>')"><i class="mdi mdi-cancel"></i><?= __('cancel', 'Cancel') ?></a>
                                                                <?php endif; ?>
                                                                <?php if ($can_cancel_salary_increment_requests && !$can_cancel_self && in_array($req['current_status'] ?? '', $cancellable_salary_increment_statuses, true)): ?>
                                                                    <div class="dropdown-divider"></div>
                                                                    <a class="dropdown-item text-danger" href="javascript:void(0);" onclick="cancelSalaryIncrementAdmin('<?= $inv_js ?>', '<?= $name_js ?>')"><i class="mdi mdi-cancel"></i><?= __('cancel', 'Cancel') ?></a>
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

                            <div class="sr-pager">
                                <?php
                                $pagination_params = [];
                                if (!empty($search_term)) $pagination_params['search'] = $search_term;
                                if (!empty($current_filter)) $pagination_params['status'] = $current_filter;
                                echo generate_pagination_controls($current_page, $total_pages, $total_items, $items_per_page, $limit_options, $show_all, $pagination_params, $unfiltered_total_items);
                                ?>
                            </div>
                        <?php else: ?>
                            <?= sr_empty_state(__('no_salary_increment_requests_found', 'No salary increment requests found'), __('no_requests_matching_filters')) ?>
                        <?php endif; ?>
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
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="assets/js/jquery.core.js"></script>
    <script src="assets/js/jquery.app.js?t=<?= time() ?>"></script>
    <script src="./plugins/select2/js/select2.min.js" type="text/javascript"></script>
    <script src="assets/js/sr_forms.js?v=<?= @filemtime(__DIR__ . '/assets/js/sr_forms.js') ?>"></script>
        <?= sr_list_js() ?>
    <script>
        const IS_HR_PAYROLL_APPROVER = <?= $isHRPayrollRole ? 'true' : 'false' ?>;
        const IS_GM_APPROVER = <?= $isGMRole ? 'true' : 'false' ?>;
        const SALARY_INCREMENT_MAX_AMOUNT = <?= json_encode((float)get_setting_num($conDB, 'salary_increment_max_amount', 2000)) ?>;


        function applyFilters() {
            const status = document.getElementById('statusFilter').value;
            const limitElement = document.getElementById('limitFilter');
            const limit = limitElement ? limitElement.value : <?= $perpage ?>;
            const search = document.getElementById('searchFilter').value;
            const baseUrl = window.location.href.split('?')[0];
            window.location.href = `${baseUrl}?status=${status}&limit=${limit}&search=${encodeURIComponent(search)}&page=1`;
        }


        const SALARY_COMPONENTS = [
            { v: 'basic', l: __('basic', 'Basic') }, { v: 'housing', l: __('housing', 'Housing') },
            { v: 'transport', l: __('transport', 'Transport') }, { v: 'food', l: __('food', 'Food') },
            { v: 'misc', l: __('misc', 'Misc') }, { v: 'cashier', l: __('cashier', 'Cashier') },
            { v: 'fuel', l: __('fuel', 'Fuel') }, { v: 'tel', l: __('tel', 'Telephone') },
            { v: 'other', l: __('others', 'Other') }, { v: 'guard', l: __('guard', 'Guard') }
        ];

        // The last approver also picks the salary component + CC list, so ask the server
        // whether this is the final step before building the popup.
        function approveSalaryIncrementRequest(requestInvNo, employeeName, amount, meta) {
            Swal.fire({ title: __('loading', 'Loading...'), allowOutsideClick: false, didOpen: () => Swal.showLoading() });
            $.post('./includes/ajaxFile/ajaxSalaryIncrement.php', { ajaxType: 'getSalaryIncrementFinalContext', request_inv_no: requestInvNo }, null, 'json')
                .done((ctx) => {
                    if (!ctx || ctx.status !== 'success') {
                        Swal.fire({ icon: 'error', title: __('error', 'Error'), text: (ctx && ctx.message) || 'Failed to load request details.' });
                        return;
                    }
                    openSalaryIncrementApproval(requestInvNo, employeeName, amount, meta, ctx.is_final ? ctx : null);
                })
                .fail(() => Swal.fire({ icon: 'error', title: __('error', 'Error'), text: __('failed_to_load_details', 'Failed to load request details.') }));
        }

        function openSalaryIncrementApproval(requestInvNo, employeeName, amount, meta, finalCtx) {
            meta = meta || {};
            const showLastIncrementDate = IS_HR_PAYROLL_APPROVER && !IS_GM_APPROVER;
            const showGMFields = IS_GM_APPROVER;
            const rawAmount = SRForm.num(amount);
            const esc = SRForm.esc;
            const money = (v) => '<i class="icon-saudi_riyal"></i> ' + Number(v || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            const kv = (label, value) => `<div class="row-kv"><dt>${esc(label)}</dt><dd>${value}</dd></div>`;
            const evalScore = (meta.evaluation_score !== null && meta.evaluation_score !== undefined) ? Number(meta.evaluation_score).toFixed(2) : '-';

            const hasApproved = meta.approved_amount !== null && meta.approved_amount !== undefined;
            const initials = String(employeeName || '').trim().split(/\s+/).slice(0, 2).map(w => w.charAt(0)).join('').toUpperCase();
            const summaryHtml = `
                <div class="sr-fcol c-12">
                    <div class="si-sum-head">
                        <span class="sr-avatar">${esc(initials)}</span>
                        <div class="si-sum-who">
                            <span class="sr-cell-title">${esc(employeeName)}</span>
                            <span class="sr-cell-sub">${esc(__('employee_id', 'Emp ID'))}: <b>${esc(meta.emp_id || '-')}</b></span>
                        </div>
                        <div class="si-sum-eval">
                            <span>${esc(__('evaluation_score', 'Evaluation Score'))}</span>
                            <b>${esc(evalScore)}</b>
                        </div>
                    </div>
                </div>
                <div class="sr-fcol c-12">
                    <div class="si-sum-tiles${hasApproved ? '' : ' is-single'}">
                        <div>
                            <span>${esc(__('increment_amount', 'Increment Amount'))}</span>
                            <b>${money(rawAmount)}</b>
                            <small>${esc(__('requested_by_supervisor', 'Requested by supervisor'))}</small>
                        </div>
                        ${hasApproved ? `<div class="is-approved">
                            <span><i class="mdi mdi-check-circle"></i> ${esc(__('approved_amount', 'Approved Amount'))}</span>
                            <b>${money(meta.approved_amount)}</b>
                            ${meta.approved_by ? `<small><span class="si-gm-badge">${esc(__('gm', 'GM'))}</span> ${esc(meta.approved_by)}</small>` : ''}
                        </div>` : ''}
                    </div>
                </div>
                <div class="sr-fcol c-12"><dl class="sr-kv">
                    ${kv(__('request_no', 'Request No'), '<span class="sr-mono">' + esc(requestInvNo) + '</span>')}
                    ${kv(__('submitted_by', 'Submitted By'), esc(meta.submitted_by || '-'))}
                </dl></div>
                ${meta.reason ? `<div class="sr-fcol c-12"><label>${esc(__('reason', 'Reason'))}</label><div class="sr-notice tone-slate" style="white-space: pre-line;">${esc(meta.reason)}</div></div>` : ''}`;

            const gmHtml = showGMFields ? SRForm.section('mdi-gavel', __('gm_decision', 'GM Decision'),
                SRForm.field({ name: 'approved_amount', id: 'si_gm_approved_amount', type: 'number', col: 6, req: true,
                    label: __('approved_amount', 'Approved Amount'), value: rawAmount,
                    attrs: ` step="0.01" min="0.01" max="${SALARY_INCREMENT_MAX_AMOUNT}"`,
                    msg: (__('approved_amount_required') || 'Approved amount is required and must be between 0 and {max}.').replace('{max}', SALARY_INCREMENT_MAX_AMOUNT),
                    hint: `${esc(__('max', 'Max'))} ${SALARY_INCREMENT_MAX_AMOUNT} <i class="icon-saudi_riyal"></i>` }) +
                SRForm.field({ name: 'effective_date', id: 'si_gm_effective_date', col: 6, req: true,
                    label: __('increment_effective_date', 'Increment Effective Date'), ph: 'YYYY-MM-DD',
                    msg: __('increment_effective_date_required') || 'Increment effective date is required.' }) +
                `<div class="sr-fcol c-12"><div class="sr-addline-summary" style="grid-template-columns: repeat(3, minmax(0, 1fr)); margin-top: 0;">
                    <div><span>${esc(__('requested', 'Requested'))}</span><b>${money(rawAmount)}</b></div>
                    <div class="accent"><span>${esc(__('approved_amount', 'Approved Amount'))}</span><b id="si_gm_sum_approved">${money(rawAmount)}</b></div>
                    <div><span>${esc(__('difference', 'Difference'))}</span><b id="si_gm_sum_diff">${money(0)}</b></div>
                </div></div>`) : '';

            const hrHtml = showLastIncrementDate ? SRForm.section('mdi-history', __('last_increment', 'Last Increment'),
                SRForm.field({ name: 'last_increment_date', id: 'si_last_increment_date', col: 6,
                    label: __('last_increment_date') || 'Date of Last Increment (Optional)', ph: 'YYYY-MM-DD' })) : '';

            let salaryHtml = '';
            let ccHtml = '';
            if (finalCtx) {
                const breakdownTotal = SALARY_COMPONENTS.reduce((t, c) => t + Number(finalCtx.components[c.v] || 0), 0);
                const masterMismatch = finalCtx.master_salary !== null && Math.abs(Number(finalCtx.master_salary) - breakdownTotal) > 0.01;
                // Effective date as "30 Oct 2026" + "in 22 days" / "today" / "8 days ago".
                const effDate = (() => {
                    const raw = finalCtx.effective_date || '';
                    const m = /^(\d{4})-(\d{2})-(\d{2})$/.exec(raw);
                    if (!m) return { text: raw || __('not_set', 'Not set'), hint: '' };
                    const d = new Date(+m[1], +m[2] - 1, +m[3]);
                    const t = new Date(); t.setHours(0, 0, 0, 0);
                    const days = Math.round((d - t) / 86400000);
                    const text = d.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
                    const hint = days === 0 ? __('today', 'Today')
                        : days > 0 ? (__('in_n_days', 'In {n} days')).replace('{n}', days)
                        : (__('n_days_ago', '{n} days ago')).replace('{n}', -days);
                    return { text, hint };
                })();
                salaryHtml = SRForm.section('mdi-cash-multiple', __('salary_update', 'Salary Update'),
                    (!finalCtx.has_salary_row
                        ? `<div class="sr-fcol c-12"><div class="sr-notice tone-amber"><i class="mdi mdi-alert-outline"></i><div>${esc(__('no_salary_breakdown_hint', 'This employee has no active salary breakdown. Please set it from the employee profile before approving.'))}</div></div></div>`
                        : '') +
                    `<div class="sr-fcol c-12"><div class="si-sum-tiles">
                        <div class="is-approved">
                            <span><i class="mdi mdi-check-circle"></i> ${esc(__('approved_amount', 'Approved Amount'))}</span>
                            <b id="si_final_amount">${money(finalCtx.amount)}</b>
                        </div>
                        <div class="is-date">
                            <span><i class="mdi mdi-calendar-check"></i> ${esc(__('increment_effective_date', 'Increment Effective Date'))}</span>
                            <b>${esc(effDate.text)}</b>
                            ${effDate.hint ? `<small>${esc(effDate.hint)}</small>` : ''}
                        </div>
                    </div></div>` +
                    SRForm.field({ name: 'salary_component', id: 'si_salary_component', col: 12, req: true,
                        label: __('add_increment_to_component', 'Add Increment To'),
                        html: SRForm.select({ name: 'salary_component', id: 'si_salary_component', req: true,
                            msg: __('select_salary_component', 'Please select the salary component for this increment.'),
                            placeholder: __('select_salary_component_ph', 'Select salary component'), options: SALARY_COMPONENTS }) }) +
                    `
                    <div class="sr-fcol c-12"><div class="sr-table-wrap"><table class="sr-table" style="margin: 0;">
                        <thead><tr>
                            <th>${esc(__('salary_component', 'Salary Component'))}</th>
                            <th class="text-right">${esc(__('current', 'Current'))}</th>
                            <th class="text-right">${esc(__('increment', 'Increment'))}</th>
                            <th class="text-right">${esc(__('new', 'New'))}</th>
                        </tr></thead>
                        <tbody id="si_breakdown_body"></tbody>
                    </table></div></div>` +
                    (masterMismatch
                        ? `<div class="sr-fcol c-12"><div class="sr-notice tone-amber"><i class="mdi mdi-alert-outline"></i><div>${esc(__('master_salary_mismatch_hint', 'Master salary differs from the component total; the new total salary will be based on the components.'))} (${money(finalCtx.master_salary)})</div></div></div>`
                        : ''));

                ccHtml = SRForm.section('mdi-email-outline', __('notify_by_email_cc', 'Notify by Email (CC)'),
                    SRForm.field({ name: 'cc_emp_ids', id: 'si_cc_emp_ids', col: 12,
                        label: __('cc_employees', 'CC Employees (HR, Finance, ...)'),
                        hint: esc(__('cc_employees_hint', 'The submitting supervisor receives the approval email; selected employees are added in CC.')),
                        html: `<select id="si_cc_emp_ids" multiple class="form-control">${(finalCtx.cc_people || []).map(p =>
                            `<option value="${esc(p.id)}" data-emp-id="${esc(p.id)}">${esc(p.name)}</option>`).join('')}</select>` }));
            }

            // Salary breakdown preview (final step): current / increment / new per component.
            // emp_salary columns are whole numbers, so the increment is rounded like the server does.
            function renderBreakdown() {
                if (!finalCtx) return;
                const comp = $('#si_salary_component').val() || '';
                const inc = Math.round(showGMFields ? SRForm.num($('#si_gm_approved_amount').val()) : Number(finalCtx.amount || 0));
                $('#si_final_amount').html(money(inc));
                let curTotal = 0, newTotal = 0, rows = '';
                SALARY_COMPONENTS.forEach((c) => {
                    const cur = Number(finalCtx.components[c.v] || 0);
                    const add = c.v === comp ? inc : 0;
                    curTotal += cur; newTotal += cur + add;
                    if (cur === 0 && add === 0) return;
                    rows += `<tr${add ? ' style="background: var(--tone-green-bg);"' : ''}>
                        <td>${esc(c.l)}${add ? ' <i class="mdi mdi-arrow-up-bold" style="color: var(--tone-green-fg);"></i>' : ''}</td>
                        <td class="text-right sr-mono">${money(cur)}</td>
                        <td class="text-right sr-mono" style="${add ? 'color: var(--tone-green-fg); font-weight: 700;' : ''}">${add ? '+' + money(add) : '-'}</td>
                        <td class="text-right sr-mono" style="font-weight: 600;">${money(cur + add)}</td>
                    </tr>`;
                });
                rows += `<tr style="border-top: 2px solid var(--sr-border-strong);">
                    <td style="font-weight: 700;">${esc(__('total_salary', 'Total Salary'))}</td>
                    <td class="text-right sr-mono" style="font-weight: 700;">${money(curTotal)}</td>
                    <td class="text-right sr-mono" style="color: var(--tone-green-fg); font-weight: 700;">${comp ? '+' + money(inc) : '-'}</td>
                    <td class="text-right sr-mono" style="font-weight: 800; color: var(--sr-accent-strong);">${money(newTotal)}</td>
                </tr>`;
                $('#si_breakdown_body').html(rows);
            }

            const commentHtml = SRForm.section('mdi-comment-text-outline', __('add_approval_comment') || 'Approval Comment (Optional)',
                SRForm.field({ name: 'approval_comment', id: 'si_approval_comment', type: 'textarea', col: 12, rows: 2,
                    ph: __('enter_comment_here') || 'Enter comment...' }));

            SRForm.open({
                title: __('confirm_approval') || 'Confirm Approval',
                // Landscape: request info + approver inputs on the left, salary/CC/comment on the right.
                html: `<div class="sr-page"><form class="sr-form si-approve-grid" id="si_approve_form" onsubmit="return false;">
                        <div class="si-approve-col">
                            ${SRForm.section('mdi-account-card-details', __('request_summary', 'Request Summary'), summaryHtml)}
                            ${gmHtml}${hrHtml}
                        </div>
                        <div class="si-approve-col">
                            ${salaryHtml}${ccHtml}${commentHtml}
                        </div>
                    </form></div>`,
                width: finalCtx ? '1180px' : '960px',
                icon: 'mdi-check',
                confirm: showLastIncrementDate ? (__('submit_salary_increment_request') || 'Submit') : (__('approve') || 'Approve'),
                confirmColor: APP_COLORS.success,
                extra: { customClass: { popup: 'sr-addline-popup sr-page' } },
                didOpen: () => {
                    const $form = $('#si_approve_form');
                    SRForm.liveClear($form);
                    // Last increment: future days blocked. GM effective date: past days blocked.
                    SRForm.datepicker($('#si_last_increment_date'), { maxDate: 'today' });
                    SRForm.datepicker($('#si_gm_effective_date'), { minDate: 'today' });
                    $('#si_gm_approved_amount').on('input', function () {
                        const v = SRForm.num(this.value);
                        const diff = v - rawAmount;
                        $('#si_gm_sum_approved').html(money(v));
                        $('#si_gm_sum_diff').html((diff > 0 ? '+' : diff < 0 ? '−' : '') + money(Math.abs(diff)))
                            .css('color', diff < 0 ? 'var(--tone-red-fg)' : diff > 0 ? 'var(--tone-green-fg)' : '');
                        renderBreakdown();
                    });

                    if (!finalCtx) return;
                    SRForm.select2($('#si_salary_component'), { placeholder: __('select_salary_component_ph', 'Select salary component'), minimumResultsForSearch: Infinity });
                    // Shows names only; search matches name or emp_id.
                    SRForm.select2($('#si_cc_emp_ids'), { placeholder: __('search_by_name_or_id', 'Search by name or Emp ID...'), closeOnSelect: false,
                        matcher: (params, data) => {
                            const term = $.trim(params.term || '').toLowerCase();
                            if (term === '') return data;
                            const id = String($(data.element).data('emp-id') || '').toLowerCase();
                            return (String(data.text || '').toLowerCase().indexOf(term) > -1 || id.indexOf(term) > -1) ? data : null;
                        } });
                    $('#si_salary_component').on('change', renderBreakdown);
                    renderBreakdown();
                },
                preConfirm: () => {
                    const $form = $('#si_approve_form');
                    const approvalComment = $('#si_approval_comment').val() || '';
                    const lastIncrementDate = showLastIncrementDate ? ($('#si_last_increment_date').val() || '') : '';

                    let approvedAmount = '';
                    let gmEffectiveDate = '';
                    if (showGMFields) {
                        approvedAmount = $('#si_gm_approved_amount').val() || '';
                        gmEffectiveDate = $('#si_gm_effective_date').val() || '';

                        const err = SRForm.validate($form, () => {
                            const amt = parseFloat(approvedAmount);
                            if (!(amt > 0) || amt > SALARY_INCREMENT_MAX_AMOUNT) {
                                return { el: document.getElementById('si_gm_approved_amount'), msg: $('#si_gm_approved_amount').data('msg') };
                            }
                            if (gmEffectiveDate < SRForm.today()) {
                                return { el: document.getElementById('si_gm_effective_date'), msg: __('increment_effective_date_must_be_future') || 'Increment effective date cannot be a past date.' };
                            }
                            return null;
                        });
                        if (err) {
                            Swal.showValidationMessage(err);
                            return false;
                        }
                    }

                    let salaryComponent = '';
                    let ccEmpIds = [];
                    if (finalCtx) {
                        if (!finalCtx.has_salary_row) {
                            Swal.showValidationMessage(__('no_salary_breakdown_hint', 'This employee has no active salary breakdown. Please set it from the employee profile before approving.'));
                            return false;
                        }
                        const err = SRForm.validate($form);
                        if (err) {
                            Swal.showValidationMessage(err);
                            return false;
                        }
                        salaryComponent = $('#si_salary_component').val() || '';
                        ccEmpIds = $('#si_cc_emp_ids').val() || [];
                    }

                    return new Promise((resolve, reject) => {
                        $.ajax({
                            url: './includes/ajaxFile/ajaxSalaryIncrement.php',
                            type: 'POST',
                            dataType: 'JSON',
                            data: {
                                ajaxType: 'approveSalaryIncrement',
                                salary_component: salaryComponent,
                                cc_emp_ids: ccEmpIds,
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

        const SI_COMPANY_LOGO = <?= json_encode((string)get_setting($conDB, 'logo', 'assets/images/logo.png')) ?>;

        // Report popup (new GUI view) + compact A4 print of the same data.
        function viewSalaryIncrementReport(requestInvNo) {
            const esc = SRForm.esc;
            const num = (v) => Number(v || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            const money = (v) => '<i class="icon-saudi_riyal"></i> ' + num(v);
            const fmtDate = (v) => {
                if (!v) return '';
                const d = new Date(String(v).replace(' ', 'T'));
                return isNaN(d.getTime()) ? String(v) : d.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
            };
            const fmtDateTime = (v) => {
                if (!v) return '';
                const d = new Date(String(v).replace(' ', 'T'));
                return isNaN(d.getTime()) ? String(v) : d.toLocaleString('en-GB', { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' });
            };
            const initials = (name) => String(name || '').trim().split(/\s+/).slice(0, 2).map(w => w.charAt(0)).join('').toUpperCase();
            const roleLabel = (r) => {
                const map = { gm: 'GM', hr: 'HR', hr_payroll: 'HR Payroll', administrator: 'Administrator', finance_officer: 'Finance' };
                r = String(r || '').toLowerCase();
                return map[r] || r.replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase());
            };
            const statusInfo = (s) => {
                s = String(s || '').toLowerCase();
                if (s.includes('approved')) return { tone: 'green', icon: 'mdi-check-circle', label: __('approved', 'Approved') };
                if (s.includes('rejected')) return { tone: 'red', icon: 'mdi-close-circle', label: __('rejected', 'Rejected') };
                if (s.includes('cancel')) return { tone: 'slate', icon: 'mdi-cancel', label: __('cancelled', 'Cancelled') };
                if (s.includes('pending')) return { tone: 'amber', icon: 'mdi-clock', label: __('pending', 'Pending') };
                if (s.includes('awaiting')) return { tone: 'sky', icon: 'mdi-timer-sand', label: __('awaiting', 'Awaiting') };
                return { tone: 'slate', icon: 'mdi-help-circle', label: s || '-' };
            };
            const SALARY_ROWS = [
                ['basic', __('basic', 'Basic')], ['housing', __('housing', 'Housing')], ['transport', __('transport', 'Transport')],
                ['food', __('food', 'Food')], ['misc', __('misc', 'Misc')], ['cashier', __('cashier', 'Cashier')],
                ['fuel', __('fuel', 'Fuel')], ['tel', __('tel', 'Telephone')], ['other', __('others', 'Other')], ['guard', __('guard', 'Guard')]
            ];

            Swal.fire({ title: __('loading', 'Loading...'), allowOutsideClick: false, didOpen: () => Swal.showLoading() });

            $.post('./includes/ajaxFile/ajaxSalaryIncrement.php', { ajaxType: 'getSalaryIncrementReport', request_inv_no: requestInvNo }, null, 'json')
                .done((res) => {
                    if (!res || res.status !== 'success' || !res.request) {
                        Swal.fire({ icon: 'error', title: __('error', 'Error'), text: (res && res.message) || 'Failed to load report.' });
                        return;
                    }

                    const req = res.request;
                    const chain = Array.isArray(res.approval_chain) ? res.approval_chain : [];
                    const salary = res.salary_info || null;
                    const st = statusInfo(req.current_status);
                    const hasApproved = req.approved_amount !== null && req.approved_amount !== undefined;
                    const gmStep = chain.find(c => String(c.approver_role).toLowerCase() === 'gm' && c.status === 'approved');
                    const service = req.service_years !== null && req.service_years !== undefined
                        ? `${req.service_years} ${__('years', 'years')} ${req.service_months || 0} ${__('months', 'months')}` : '-';
                    const salaryLines = salary ? SALARY_ROWS.filter(([k]) => Number(salary[k]) > 0).map(([k, l]) => ({ label: l, value: Number(salary[k]) })) : [];
                    const salaryTotal = salary ? Number(salary.total_salary) : null;

                    const empInfo = [
                        [__('iqama', 'Iqama'), req.iqama],
                        [__('iqama_expiry', 'Iqama Expiry'), fmtDate(req.iqama_exp_g)],
                        [__('nationality', 'Nationality'), req.nationality],
                        [__('job_title', 'Job Title'), req.job_title],
                        [__('department', 'Department'), req.department_name],
                        [__('company', 'Company'), req.company_name],
                        [__('location', 'Location'), req.location_name],
                        [__('employee_type', 'Employee Type'), req.emptype],
                        [__('cost_center', 'Cost Center'), req.cost_center],
                        [__('joining_date', 'Joining Date'), fmtDate(req.joining_date)],
                        [__('years_of_service', 'Years of Service'), service],
                        [__('direct_supervisor', 'Direct Supervisor'), req.direct_supervisor_name],
                        [__('mobile', 'Mobile'), req.mobile],
                        [__('company_email', 'Company Email'), req.c_email]
                    ];
                    const reqInfo = [
                        [__('request_no', 'Request No'), req.request_inv_no],
                        [__('submitted_by', 'Submitted By'), req.submitted_by_name || req.submitted_by],
                        [__('submitted_date', 'Submitted Date'), fmtDateTime(req.created_at)]
                    ];

                    /* ---------- popup ---------- */
                    const kv = (rows) => '<dl class="sr-kv">' + rows.map(([l, v]) =>
                        `<div class="row-kv"><dt>${esc(l)}</dt><dd>${esc(v || '-')}</dd></div>`).join('') + '</dl>';

                    const salaryTable = salary
                        ? `<div class="sr-table-wrap"><table class="sr-table" style="margin: 0;">
                            <tbody>${salaryLines.map(r => `<tr><td>${esc(r.label)}</td><td class="text-right sr-mono">${money(r.value)}</td></tr>`).join('')}
                            <tr style="border-top: 2px solid var(--sr-border-strong);"><td style="font-weight: 700;">${esc(__('total_salary', 'Total Salary'))}</td>
                                <td class="text-right sr-mono" style="font-weight: 800; color: var(--sr-accent-strong);">${money(salaryTotal)}</td></tr></tbody>
                        </table></div>`
                        : `<div class="sr-notice tone-slate"><i class="mdi mdi-information-outline"></i><div>${esc(__('no_salary_breakdown', 'No active salary breakdown on record.'))}</div></div>`;

                    const chainHtml = chain.length ? '<ul class="sr-activity">' + chain.map(c => {
                        const s = statusInfo(c.status);
                        return `<li>
                            <span class="sr-activity-icon tone-${s.tone}"><i class="mdi ${s.icon}"></i></span>
                            <div class="sr-activity-body">
                                <div class="sr-activity-title">${esc(c.approver_name || ('Emp#' + c.approver_id))}
                                    ${c.approver_role ? `<span class="sr-chip">${esc(roleLabel(c.approver_role))}</span>` : ''}
                                    ${'<span class="sr-pill tone-' + s.tone + '" style="margin-inline-start: 6px;"><span class="sr-dot"></span>' + esc(s.label) + '</span>'}</div>
                                ${c.note ? `<div class="sr-activity-note"><i class="mdi mdi-comment-text-outline"></i> ${esc(c.note)}</div>` : ''}
                                <div class="sr-activity-meta"><span>${esc(__('level', 'Level'))} ${esc(c.level)}</span>${c.approver_job ? `<span>${esc(c.approver_job)}</span>` : ''}${c.action_date ? `<span><i class="mdi mdi-clock"></i> ${esc(fmtDateTime(c.action_date))}</span>` : ''}</div>
                            </div>
                        </li>`;
                    }).join('') + '</ul>' : `<div class="sr-empty">${esc(__('no_data_found', 'No data found'))}</div>`;

                    const html = `<div class="sr-page"><div class="sr-form si-report">
                        <div class="si-report-hero">
                            <span class="sr-avatar">${esc(initials(req.employee_name))}</span>
                            <div class="si-sum-who">
                                <span class="sr-cell-title">${esc(req.employee_name)}</span>
                                <span class="sr-cell-sub">${esc(__('emp_id', 'Emp ID'))}: <b>${esc(req.emp_id)}</b>${req.job_title ? ' · ' + esc(req.job_title) : ''}${req.department_name ? ' · ' + esc(req.department_name) : ''}</span>
                            </div>
                            <span class="sr-pill tone-${st.tone}"><span class="sr-dot"></span>${esc(st.label)}</span>
                        </div>

                        <div class="si-sum-tiles si-report-tiles">
                            <div><span>${esc(__('increment_amount', 'Increment Amount'))}</span><b>${money(req.increment_amount)}</b><small>${esc(__('requested_by_supervisor', 'Requested by supervisor'))}</small></div>
                            <div class="${hasApproved ? 'is-approved' : ''}"><span>${esc(__('approved_amount', 'Approved Amount'))}</span><b>${hasApproved ? money(req.approved_amount) : '-'}</b>${gmStep ? `<small><span class="si-gm-badge">${esc(__('gm', 'GM'))}</span> ${esc(gmStep.approver_name)}</small>` : ''}</div>
                            <div class="is-date"><span>${esc(__('increment_effective_date', 'Increment Effective Date'))}</span><b>${esc(fmtDate(req.last_increment_date) || '-')}</b></div>
                            <div><span>${esc(__('evaluation_score', 'Evaluation Score'))}</span><b>${req.evaluation_score !== null ? num(req.evaluation_score) : '-'}</b></div>
                        </div>

                        <div class="si-approve-grid">
                            <div class="si-approve-col">
                                ${SRForm.section('mdi-account-card-details', __('employee_information', 'Employee Information'), '<div class="sr-fcol c-12">' + kv(empInfo) + '</div>')}
                            </div>
                            <div class="si-approve-col">
                                ${SRForm.section('mdi-cash-multiple', __('current_salary', 'Current Salary'), '<div class="sr-fcol c-12">' + salaryTable + '</div>')}
                                ${SRForm.section('mdi-file-document', __('request_information', 'Request Information'), '<div class="sr-fcol c-12">' + kv(reqInfo) + '</div>')}
                            </div>
                        </div>
                        ${req.reason ? SRForm.section('mdi-note-text', __('reason', 'Reason'), `<div class="sr-fcol c-12"><div class="sr-notice tone-slate" style="white-space: pre-line;">${esc(req.reason)}</div></div>`) : ''}
                        ${SRForm.section('mdi-sitemap', __('approval_chain', 'Approval Chain'), '<div class="sr-fcol c-12">' + chainHtml + '</div>')}
                    </div></div>`;

                    /* ---------- print (compact A4) ---------- */
                    const printReport = () => {
                        const pe = (v) => esc(v == null || v === '' ? '-' : v);
                        const grid = (rows) => rows.map(([l, v]) => `<div class="f"><span>${pe(l)}</span><b>${pe(v)}</b></div>`).join('');
                        const logo = new URL(SI_COMPANY_LOGO || 'assets/images/logo.png', window.location.href).href;
                        const doc = `<!doctype html><html><head><meta charset="utf-8"><title>${pe(req.request_inv_no)}</title>
<style>
@page { size: A4; margin: 10mm; }
* { box-sizing: border-box; }
body { font-family: Arial, Helvetica, sans-serif; font-size: 10.5px; color: #1f2937; margin: 0; }
.hd { display: flex; align-items: center; justify-content: space-between; border-bottom: 2px solid #4f46e5; padding-bottom: 6px; margin-bottom: 8px; }
.hd img { height: 42px; }
.hd h1 { margin: 0; font-size: 16px; color: #111827; text-align: center; flex: 1; }
.hd .meta { text-align: right; font-size: 9.5px; color: #6b7280; line-height: 1.5; }
.pill { display: inline-block; padding: 1px 8px; border-radius: 10px; font-weight: 700; font-size: 9.5px; border: 1px solid; }
.t-green { color: #15803d; border-color: #86efac; background: #f0fdf4; } .t-red { color: #b91c1c; border-color: #fca5a5; background: #fef2f2; }
.t-amber { color: #b45309; border-color: #fcd34d; background: #fffbeb; } .t-slate, .t-sky { color: #475569; border-color: #cbd5e1; background: #f8fafc; }
h2 { font-size: 11px; text-transform: uppercase; letter-spacing: .4px; color: #4f46e5; margin: 9px 0 4px; padding-bottom: 2px; border-bottom: 1px solid #e5e7eb; }
.emp { display: flex; justify-content: space-between; align-items: baseline; margin-bottom: 2px; }
.emp .n { font-size: 14px; font-weight: 700; }
.grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 3px 12px; }
.f { display: flex; flex-direction: column; padding: 2px 0; border-bottom: 1px dotted #e5e7eb; }
.f span { font-size: 8.5px; color: #6b7280; text-transform: uppercase; }
.f b { font-size: 10.5px; }
.tiles { display: grid; grid-template-columns: repeat(4, 1fr); border: 1px solid #d1d5db; border-radius: 6px; overflow: hidden; }
.tiles div { padding: 5px 8px; border-right: 1px solid #d1d5db; } .tiles div:last-child { border-right: 0; }
.tiles span { display: block; font-size: 8.5px; color: #6b7280; text-transform: uppercase; } .tiles b { font-size: 13px; } .tiles small { display: block; color: #6b7280; font-size: 9px; }
.tiles .ok { background: #f0fdf4; } .tiles .ok b { color: #15803d; }
.cols { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
table { width: 100%; border-collapse: collapse; }
th, td { padding: 3px 6px; border: 1px solid #e5e7eb; text-align: left; font-size: 10px; }
th { background: #f3f4f6; font-size: 9px; text-transform: uppercase; color: #4b5563; }
td.r, th.r { text-align: right; } tr.tot td { font-weight: 700; background: #eef2ff; }
.reason { border: 1px solid #e5e7eb; border-radius: 4px; padding: 5px 7px; white-space: pre-line; }
.sign { display: grid; grid-template-columns: repeat(3, 1fr); gap: 18px; margin-top: 26px; }
.sign div { border-top: 1px solid #9ca3af; padding-top: 3px; text-align: center; font-size: 9.5px; color: #4b5563; }
.ft { margin-top: 10px; font-size: 8.5px; color: #9ca3af; text-align: center; }
</style></head><body>
<div class="hd"><img src="${esc(logo)}" alt=""><h1>${pe(__('salary_increment_report', 'Salary Increment Report'))}</h1>
<div class="meta">${pe(req.request_inv_no)}<br>${pe(__('printed_on', 'Printed on'))}: ${pe(fmtDateTime(new Date().toISOString()))}<br><span class="pill t-${st.tone}">${pe(st.label)}</span></div></div>

<div class="emp"><span class="n">${pe(req.employee_name)}</span><span>${pe(__('emp_id', 'Emp ID'))}: <b>${pe(req.emp_id)}</b></span></div>
<h2>${pe(__('employee_information', 'Employee Information'))}</h2>
<div class="grid">${grid(empInfo)}</div>

<h2>${pe(__('increment_details', 'Increment Details'))}</h2>
<div class="tiles">
<div><span>${pe(__('increment_amount', 'Increment Amount'))}</span><b>${num(req.increment_amount)}</b><small>${pe(__('requested_by_supervisor', 'Requested by supervisor'))}</small></div>
<div class="${hasApproved ? 'ok' : ''}"><span>${pe(__('approved_amount', 'Approved Amount'))}</span><b>${hasApproved ? num(req.approved_amount) : '-'}</b>${gmStep ? `<small>GM: ${pe(gmStep.approver_name)}</small>` : ''}</div>
<div><span>${pe(__('increment_effective_date', 'Increment Effective Date'))}</span><b>${pe(fmtDate(req.last_increment_date))}</b></div>
<div><span>${pe(__('evaluation_score', 'Evaluation Score'))}</span><b>${req.evaluation_score !== null ? num(req.evaluation_score) : '-'}</b></div>
</div>

<div class="cols">
<div><h2>${pe(__('current_salary', 'Current Salary'))}</h2>
${salary ? `<table><thead><tr><th>${pe(__('salary_component', 'Salary Component'))}</th><th class="r">${pe(__('amount', 'Amount'))} (SAR)</th></tr></thead><tbody>
${salaryLines.map(r => `<tr><td>${pe(r.label)}</td><td class="r">${num(r.value)}</td></tr>`).join('')}
<tr class="tot"><td>${pe(__('total_salary', 'Total Salary'))}</td><td class="r">${num(salaryTotal)}</td></tr></tbody></table>` : `<div class="reason">${pe(__('no_salary_breakdown', 'No active salary breakdown on record.'))}</div>`}
</div>
<div><h2>${pe(__('request_information', 'Request Information'))}</h2>
<table><tbody>${reqInfo.map(([l, v]) => `<tr><th style="width: 40%;">${pe(l)}</th><td>${pe(v)}</td></tr>`).join('')}</tbody></table>
${req.reason ? `<h2>${pe(__('reason', 'Reason'))}</h2><div class="reason">${pe(req.reason)}</div>` : ''}
</div>
</div>

<h2>${pe(__('approval_chain', 'Approval Chain'))}</h2>
<table><thead><tr><th>${pe(__('level', 'Level'))}</th><th>${pe(__('approver', 'Approver'))}</th><th>${pe(__('role', 'Role'))}</th><th>${pe(__('status', 'Status'))}</th><th>${pe(__('date', 'Date'))}</th><th>${pe(__('comment', 'Comment'))}</th></tr></thead><tbody>
${chain.map(c => `<tr><td>${pe(c.level)}</td><td>${pe(c.approver_name || ('Emp#' + c.approver_id))}</td><td>${pe(roleLabel(c.approver_role))}</td><td>${pe(statusInfo(c.status).label)}</td><td>${pe(fmtDateTime(c.action_date))}</td><td>${pe(c.note)}</td></tr>`).join('')}
</tbody></table>

<div class="sign"><div>${pe(__('direct_supervisor', 'Direct Supervisor'))}</div><div>${pe(__('hr_department', 'HR Department'))}</div><div>${pe(__('general_manager', 'General Manager'))}</div></div>
<div class="ft">${pe(__('system_generated_document', 'This is a system generated document.'))}</div>
<script>
/* The print tab prints itself once loaded (logo included) and closes when the dialog is done. */
(function () {
    var closed = false;
    function done() { if (closed) return; closed = true; setTimeout(function () { window.close(); }, 100); }
    window.addEventListener('afterprint', done);
    window.addEventListener('load', function () {
        setTimeout(function () {
            window.focus();
            var t = Date.now();
            window.print();
            // Chrome/Edge block in print() until the dialog closes - close right away there.
            // Where print() returns at once, wait for afterprint instead.
            if (Date.now() - t > 300) done();
        }, 200);
    });
})();
<\/script>
</body></html>`;
                        const w = window.open('', '_blank');
                        if (!w) {
                            Swal.showValidationMessage(__('allow_popups_to_print', 'Please allow pop-ups to print.'));
                            return;
                        }
                        w.document.open();
                        w.document.write(doc);
                        w.document.close();
                    };

                    Swal.fire({
                        title: __('salary_increment_report', 'Salary Increment Report'),
                        html: html,
                        width: '1100px',
                        showConfirmButton: true,
                        showCancelButton: true,
                        confirmButtonColor: APP_COLORS.primary,
                        cancelButtonColor: APP_COLORS.danger_dark,
                        confirmButtonText: '<i class="mdi mdi-printer"></i> ' + esc(__('print', 'Print')),
                        cancelButtonText: '<i class="mdi mdi-close"></i> ' + esc(__('close', 'Close')),
                        allowOutsideClick: false,
                        customClass: { popup: 'sr-addline-popup sr-page' },
                        // Print keeps the popup open.
                        preConfirm: () => { printReport(); return false; }
                    });
                })
                .fail(() => Swal.fire({ icon: 'error', title: __('error', 'Error'), text: 'Failed to load report.' }));
        }
    </script>
</body>
</html>
<?php
$conDB->close();
?>
