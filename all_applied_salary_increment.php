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
                                            ?>
                                            <tr>
                                                <td><?= sr_person_cell(getDisplayName(parseName($req['employee_name'])), $req['emp_id']) ?></td>
                                                <td class="sr-nowrap">
                                                    <span class="sr-money"><i class="icon-saudi_riyal"></i> <?= number_format((float)$req['increment_amount'], 2) ?></span>
                                                    <?php if ($req['approved_amount'] !== null): ?>
                                                        <span class="sr-cell-sub" style="color: var(--tone-green-fg);"><i class="mdi mdi-check-circle"></i> <?= __('approved_amount', 'Approved Amount') ?>: <?= number_format((float)$req['approved_amount'], 2) ?></span>
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
                                                            <a href="javascript:void(0);" class="sr-open-btn" onclick="approveSalaryIncrementRequest('<?= $inv_js ?>', '<?= $name_js ?>', '<?= $amount_js ?>')"><i class="mdi mdi-check"></i> <?= $isHRPayrollRole ? __('submit_salary_increment_request', 'Submit') : __('approve') ?></a>
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
    <script src="plugins/bootstrap-datepicker/js/bootstrap-datepicker.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="assets/js/jquery.core.js"></script>
    <script src="assets/js/jquery.app.js?t=<?= time() ?>"></script>
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
