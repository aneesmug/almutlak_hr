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

        function viewSalaryIncrementReport(requestInvNo) {
            const escapeHtml = (value) => String(value == null ? '' : value)
                .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;').replace(/'/g, '&#39;');

            const statusMetaMap = {
                approved:  { icon: 'fa-check', cls: 'et-status-success', label: __('approved', 'Approved') },
                rejected:  { icon: 'fa-times', cls: 'et-status-danger', label: __('rejected', 'Rejected') },
                cancelled: { icon: 'fa-ban', cls: 'et-status-secondary', label: __('cancelled', 'Cancelled') },
                pending:   { icon: 'fa-hourglass-half', cls: 'et-status-warning', label: __('pending', 'Pending') },
                awaiting:  { icon: 'fa-pause-circle', cls: 'et-status-primary', label: __('awaiting', 'Awaiting') }
            };
            const getStatusMeta = (statusValue) => {
                const normalized = String(statusValue || '').toLowerCase().replace(/_/g, ' ').trim();
                const key = Object.keys(statusMetaMap).find(k => normalized.includes(k));
                return statusMetaMap[key] || { icon: 'fa-circle', cls: 'et-status-secondary', label: normalized.replace(/\b\w/g, c => c.toUpperCase()) || __('unknown', 'Unknown') };
            };
            // Same per-step map used by the Employee Transfer / Settlement approval-chain timelines.
            const stepMeta = {
                approved: { icon: 'fa-check', cls: 'et-step-success', label: __('approved', 'Approved') },
                pending:  { icon: 'fa-clock', cls: 'et-step-warning', label: __('pending', 'Pending') },
                rejected: { icon: 'fa-times', cls: 'et-step-danger', label: __('rejected', 'Rejected') },
                awaiting: { icon: 'fa-hourglass-half', cls: 'et-step-secondary', label: __('awaiting', 'Awaiting') }
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

                    const infoRow = (icon, label, value) => `
                        <div class="et-report-row">
                            <div class="et-report-row-label"><i class="fa ${icon}"></i> ${label}</div>
                            <div class="et-report-row-value">${value || 'N/A'}</div>
                        </div>`;

                    let timelineHtml = '';
                    chain.forEach(c => {
                        const step = stepMeta[c.status] || stepMeta.awaiting;
                        const approverName = escapeHtml((c.approver_name && String(c.approver_name).trim() !== '') ? c.approver_name : ('Emp#' + c.approver_id));
                        const actionDate = c.action_date ? new Date(String(c.action_date).replace(' ', 'T')) : null;
                        const actionLabel = actionDate && !isNaN(actionDate.getTime()) ? actionDate.toLocaleDateString(undefined, { year: 'numeric', month: 'short', day: 'numeric' }) : '';
                        timelineHtml += `
                            <div class="et-timeline-item ${step.cls}">
                                <div class="et-timeline-dot"><i class="fa ${step.icon}"></i></div>
                                <div class="et-timeline-content">
                                    <div class="et-timeline-top">
                                        <span class="et-timeline-level">${__('level', 'Level')} ${c.level}</span>
                                        <span class="et-timeline-badge">${step.label}</span>
                                    </div>
                                    <div class="et-timeline-approver">${approverName}</div>
                                    ${actionLabel ? `<div class="et-timeline-approver">${actionLabel}</div>` : ''}
                                    ${c.note ? `<div class="et-timeline-note"><i class="fas fa-comment"></i> ${escapeHtml(c.note)}</div>` : ''}
                                </div>
                            </div>`;
                    });

                    const salary = res.salary_info || null;
                    let salaryHtml = '<div style="padding:6px 2px;color:#8a94a6;font-size:13px;">' + (__('no_data_found', 'No data found')) + '</div>';
                    if (salary) {
                        const salaryIcons = {
                            basic_salary: 'fa-money-bill-alt', housing_allowance: 'fa-home', transport_allowance: 'fa-car',
                            food_allowance: 'fa-utensils', miscellaneous_allowance: 'fa-shapes', cashier_allowance: 'fa-cash-register',
                            fuel_allowance: 'fa-gas-pump', telephone_allowance: 'fa-phone', other_allowance: 'fa-ellipsis-h', guard_allowance: 'fa-shield-alt'
                        };
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
                        salaryHtml = salaryRows.filter(([, , val]) => Number(val) > 0)
                            .map(([key, fallback, val]) => infoRow(salaryIcons[key] || 'fa-money-bill', __(key, fallback), Number(val).toFixed(2))).join('')
                            + infoRow('fa-calculator', __('total_salary', 'Total salary'), '<strong style="color:#667eea;">' + Number(salary.total_salary).toFixed(2) + '</strong>');
                    }

                    let html = '<div class="et-report">';
                    html += `
                        <div class="et-report-header">
                            <div class="et-report-avatar"><i class="fa fa-arrow-trend-up"></i></div>
                            <div class="et-report-heading">
                                <div class="et-report-name">${escapeHtml(req.employee_name)}</div>
                                <div class="et-report-subid">${__('emp_id', 'Employee ID')}: ${escapeHtml(req.emp_id)} &bull; ${escapeHtml(req.request_inv_no)}</div>
                            </div>
                            <div class="et-report-status-pill ${statusMeta.cls}"><i class="fa ${statusMeta.icon}"></i> ${statusMeta.label}</div>
                        </div>`;

                    html += '<div class="row">';
                    html += `
                        <div class="col-md-6">
                            <div class="vacation-card">
                                <div class="vacation-card-header"><i class="fa fa-file-alt"></i> ${__('applied_information', 'Applied Information')}</div>
                                ${infoRow('fa-sitemap', __('department', 'Department'), escapeHtml(req.department_name || '-'))}
                                ${infoRow('fa-arrow-trend-up', __('increment_amount', 'Increment Amount'), Number(req.increment_amount).toFixed(2))}
                                ${(req.approved_amount !== null && req.approved_amount !== undefined) ? infoRow('fa-check-circle', __('approved_amount', 'Approved Amount'), '<strong style="color:#28a745;">' + Number(req.approved_amount).toFixed(2) + '</strong>') : ''}
                                ${infoRow('fa-star', __('evaluation_score', 'Evaluation Score'), req.evaluation_score !== null ? Number(req.evaluation_score).toFixed(2) : '-')}
                                ${req.last_increment_date ? infoRow('fa-calendar-day', __('last_increment_date', 'Date of Last Increment (Optional)'), escapeHtml(req.last_increment_date)) : ''}
                                ${infoRow('fa-user-edit', __('submitted_by', 'Submitted By'), escapeHtml(req.submitted_by_name || req.submitted_by))}
                                ${infoRow('fa-calendar-alt', __('submitted_date', 'Submitted Date'), submittedLabel)}
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="vacation-card">
                                <div class="vacation-card-header"><i class="fa fa-money-bill-wave"></i> ${__('salary_information', 'Salary Information')}</div>
                                ${salaryHtml}
                            </div>
                        </div>`;
                    html += '</div>';

                    if (req.reason) {
                        html += `
                            <div class="vacation-card">
                                <div class="vacation-card-header"><i class="fa fa-sticky-note"></i> ${__('reason', 'Reason')}</div>
                                <div class="et-notes-text">${escapeHtml(req.reason)}</div>
                            </div>`;
                    }

                    if (timelineHtml) {
                        html += `
                            <div class="vacation-card">
                                <div class="vacation-card-header et-timeline-toggle" onclick="toggleEtApprovalChain(this)" role="button">
                                    <i class="fa fa-sitemap"></i> ${__('approval_chain', 'Approval Chain')}
                                    <i class="fa fa-chevron-down et-timeline-chevron"></i>
                                </div>
                                <div class="et-timeline d-none">${timelineHtml}</div>
                            </div>`;
                    }
                    html += '</div>';

                    Swal.fire({
                        title: __('salary_increment_approval_history', 'Salary Increment Approval History'),
                        html: html,
                        showConfirmButton: false,
                        showCancelButton: true,
                        cancelButtonColor: APP_COLORS.danger_dark,
                        cancelButtonText: '<i class="fa fa-times"></i> ' + (__('close', 'Close')),
                        allowOutsideClick: false,
                        width: '60%',
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
    </script>
</body>
</html>
<?php
$conDB->close();
?>
