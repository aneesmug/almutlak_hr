<?php
require_once __DIR__ . '/includes/session_check.php';
require_once __DIR__ . '/includes/special_access_helper.php';

// Restrict access: Employees cannot view this page, unless explicitly granted
// via app_settings -> Special Access. (Same pattern as all_applied_business_trip.php -
// the broader per-role restriction, and the error403.php page for anyone else denied,
// is handled generically by includes/main_menu.php via App Settings > Page Access.)
if (
    isset($isEmployee) && $isEmployee === true
    && !user_has_special_access($conDB, $empid ?? '', 'access_all_applied_employee_transfers', $user_role ?? '', $user_type ?? '', $is_system_admin ?? false)
) {
    header("Location: ./profile.php");
    exit();
}

// A temp-role replacement can act on the original employee's pending approvals too -
// request_approvers.approver_id / current_approver_id still point at the original employee.
$delegatedFromEmpId = getDelegatedFromEmpId($conDB, $empid ?? '');

// --- Get Request Type ID for 'employee_transfer_request' ---
$type_query = mysqli_query($conDB, "SELECT `id` FROM `approval_request_types` WHERE `type_name` = 'employee_transfer_request' LIMIT 1");
if (!$type_query || mysqli_num_rows($type_query) == 0) {
    die("CRITICAL ERROR: 'employee_transfer_request' type not found in `approval_request_types` table.");
}
$request_type_id = (int)mysqli_fetch_assoc($type_query)['id'];

$all_statuses = [
    'my_pending' => __('my_pending_queue'),
    'my_requests' => __('my_requests', 'My Requests'),
    'pending_approval' => __('all_pending'),
    'approved' => __('approved'),
    'rejected' => __('rejected'),
    'completed' => __('completed'),
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
$can_see_all = ($is_system_admin ?? false) || ($isHR ?? false);

$page_title = $all_statuses[$current_filter] ?? __('all_requests');

if ($current_filter === 'my_pending') {
    $join_sql .= " JOIN `request_approvers` ra ON ra.request_inv_no = et.request_inv_no AND ra.request_type_id = ? ";
    $params[] = $request_type_id;
    $types .= "i";

    $where_clauses[] = "ra.approver_id = ?";
    $params[] = $empid;
    $types .= "s";

    $where_clauses[] = "ra.status = 'pending'";
    $where_clauses[] = "et.current_status = 'pending_approval'";
} elseif ($current_filter === 'my_requests') {
    // Requests I created (as the requesting/new supervisor)
    $where_clauses[] = "et.emp_id = ?";
    $params[] = $empid;
    $types .= "s";
} elseif (in_array($current_filter, ['pending_approval', 'approved', 'rejected', 'completed'], true)) {
    $where_clauses[] = "et.current_status = ?";
    $params[] = $current_filter;
    $types .= "s";
} elseif ($current_filter === 'all' && empty($search_term) && !$can_see_all) {
    // Non-privileged users viewing "all": only requests they created, approved on, or are pending on
    $where_clauses[] = "(et.emp_id = ? OR EXISTS (SELECT 1 FROM request_approvers ra_any WHERE ra_any.request_inv_no = et.request_inv_no AND ra_any.request_type_id = ? AND ra_any.approver_id = ?))";
    array_push($params, $empid, $request_type_id, $empid);
    $types .= "sis";
}

if (!empty($search_term)) {
    $where_clauses[] = "(tgt.name LIKE ? OR et.target_emp_id LIKE ? OR et.request_inv_no LIKE ?)";
    $search_param = "%{$search_term}%";
    array_push($params, $search_param, $search_param, $search_param);
    $types .= "sss";
}

$where_sql = "";
if (!empty($where_clauses)) {
    $where_sql = " WHERE " . implode(" AND ", $where_clauses);
}

$base_query = "FROM emp_transfers et
               JOIN employees tgt ON et.target_emp_id = tgt.emp_id
               LEFT JOIN employees req ON et.emp_id = req.emp_id
               LEFT JOIN employees fs ON et.from_supervisor_id = fs.emp_id
               LEFT JOIN employees ts ON et.to_supervisor_id = ts.emp_id
               LEFT JOIN department d ON tgt.dept = d.id
               LEFT JOIN companies c ON tgt.comp_no = c.comp_id
               LEFT JOIN ac_jobs j ON tgt.actual_job = j.id
               $join_sql
               $where_sql";

$count_sql = "SELECT COUNT(DISTINCT et.id) as total " . $base_query;
$total_items = 0;
$stmt_count = mysqli_prepare($conDB, $count_sql);
if (!$stmt_count) {
    die("Count query prepare failed: " . mysqli_error($conDB));
}
if (!empty($params)) {
    mysqli_stmt_bind_param($stmt_count, $types, ...$params);
}
mysqli_stmt_execute($stmt_count);
$total_items = (int)(mysqli_stmt_get_result($stmt_count)->fetch_assoc()['total'] ?? 0);
mysqli_stmt_close($stmt_count);

$total_pages = $show_all ? 1 : ceil($total_items / $items_per_page);
if ($current_page > $total_pages && $total_pages > 0) {
    $current_page = $total_pages;
}

$requests = [];
if ($total_items > 0) {
    $sql = "SELECT
        et.*,
        tgt.name AS target_name,
        d.dep_nme AS deptnme,
        c.comp_name AS compnme,
        j.job AS jobname,
        fs.name AS from_supervisor_name,
        ts.name AS to_supervisor_name,
        req.name AS requester_name,
        ra_pending.approver_id AS current_approver_id,
        ra_pending.approval_level AS current_approval_level,
        approver_emp.name AS current_approver_name,
        ra_rejected.note AS rejection_note
    FROM emp_transfers et
    JOIN employees tgt ON et.target_emp_id = tgt.emp_id
    LEFT JOIN employees req ON et.emp_id = req.emp_id
    LEFT JOIN employees fs ON et.from_supervisor_id = fs.emp_id
    LEFT JOIN employees ts ON et.to_supervisor_id = ts.emp_id
    LEFT JOIN department d ON tgt.dept = d.id
    LEFT JOIN companies c ON tgt.comp_no = c.comp_id
    LEFT JOIN ac_jobs j ON tgt.actual_job = j.id
    LEFT JOIN request_approvers ra_pending ON ra_pending.request_inv_no = et.request_inv_no AND ra_pending.request_type_id = ? AND ra_pending.status = 'pending'
    LEFT JOIN employees approver_emp ON ra_pending.approver_id = approver_emp.emp_id
    LEFT JOIN request_approvers ra_rejected ON ra_rejected.request_inv_no = et.request_inv_no AND ra_rejected.request_type_id = ? AND ra_rejected.status = 'rejected'
    $join_sql
    $where_sql
    GROUP BY et.id ORDER BY et.created_at DESC";

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

    $stmt = mysqli_prepare($conDB, $sql);
    if (!$stmt) {
        die("Main query prepare failed: " . mysqli_error($conDB));
    }
    if (!empty($main_params)) {
        mysqli_stmt_bind_param($stmt, $main_types, ...$main_params);
    }
    if (!mysqli_stmt_execute($stmt)) {
        die("Main query execute failed: " . mysqli_stmt_error($stmt));
    }
    $result = mysqli_stmt_get_result($stmt);
    while ($row = mysqli_fetch_assoc($result)) {
        $requests[] = $row;
    }
    mysqli_stmt_close($stmt);
}
?>
<!doctype html>
<html lang="<?= $current_lang ?? 'en' ?>" <?= ($is_rtl ?? false) ? 'dir="rtl"' : '' ?>>
<head>
    <meta charset="utf-8" />
    <title><?= $site_title ?? 'System' ?> - <?= __('employee_transfer_requests', 'Employee Transfer Requests') ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta content="Anees Afzal" name="author" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <link rel="shortcut icon" href="<?= get_setting($conDB, 'favicon') ?>">
    <link href="./plugins/select2/css/select2.min.css" rel="stylesheet" type="text/css" />
    <link href="assets/css/bootstrap.min.css" rel="stylesheet" type="text/css" />
    <link href="plugins/bootstrap-daterangepicker/daterangepicker.css" rel="stylesheet" type="text/css" />
    <link href="assets/css/icons.css" rel="stylesheet" type="text/css" />
    <link href="assets/css/metismenu.min.css" rel="stylesheet" type="text/css" />
    <link href="assets/css/style.css" rel="stylesheet" type="text/css" />
    <link href="assets/css/style_dark.css" rel="stylesheet" type="text/css" />
    <link href="assets/css/smart_request.css?v=<?= @filemtime(__DIR__ . '/assets/css/smart_request.css') ?>" rel="stylesheet" type="text/css" />
    <script src="assets/js/modernizr.min.js"></script>
    <style>
        /* --- Employee Transfer: "View Report" modal --- */
        .et-report { text-align: left; }
        .et-report-header { display: flex; align-items: center; gap: 15px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: #fff; border-radius: 10px; padding: 18px 20px; margin-bottom: 18px; }
        .et-report-avatar { width: 48px; height: 48px; min-width: 48px; border-radius: 50%; background: rgba(255,255,255,0.18); display: flex; align-items: center; justify-content: center; font-size: 20px; }
        .et-report-heading { flex: 1; min-width: 0; }
        .et-report-name { font-size: 17px; font-weight: 700; line-height: 1.3; }
        .et-report-subid { font-size: 12px; opacity: 0.85; margin-top: 2px; }
        .et-report-status-pill { padding: 6px 14px; border-radius: 20px; font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.4px; white-space: nowrap; }
        .et-status-warning { background: #fff3cd; color: #856404; }
        .et-status-success { background: #d4edda; color: #155724; }
        .et-status-danger { background: #f8d7da; color: #721c24; }
        .et-status-primary { background: #d1ecf1; color: #0c5460; }
        .et-status-secondary { background: #e2e3e5; color: #383d41; }

        .et-report-row { display: flex; justify-content: space-between; align-items: center; gap: 10px; padding: 8px 0; border-bottom: 1px solid #eef0f5; font-size: 13.5px; }
        .et-report-row:last-child { border-bottom: none; }
        .et-report-row-label { color: #858796; font-weight: 600; }
        .et-report-row-label i { width: 16px; margin-right: 6px; color: #4e73df; }
        .et-report-row-value { color: #3a3b45; font-weight: 600; text-align: right; }

        .et-handover { display: flex; align-items: center; justify-content: space-between; gap: 15px; }
        .et-handover-side { flex: 1; text-align: center; background: #fff; border: 1px solid #e3e6f0; border-radius: 8px; padding: 14px 10px; }
        .et-handover-side i { font-size: 20px; color: #4e73df; margin-bottom: 6px; display: block; }
        .et-handover-label { font-size: 11px; text-transform: uppercase; color: #858796; font-weight: 600; letter-spacing: 0.4px; }
        .et-handover-name { font-size: 14px; font-weight: 700; color: #3a3b45; margin-top: 3px; }
        .et-handover-arrow { color: #4e73df; font-size: 20px; }

        .et-notes-text { font-size: 13.5px; color: #3a3b45; background: #fff; border: 1px solid #e3e6f0; border-radius: 6px; padding: 10px 12px; }

        .et-timeline { display: flex; flex-direction: column; gap: 0; }
        .et-timeline-item { display: flex; gap: 14px; position: relative; padding-bottom: 20px; }
        .et-timeline-item:last-child { padding-bottom: 0; }
        .et-timeline-item:not(:last-child)::before { content: ''; position: absolute; left: 15px; top: 32px; bottom: 0; width: 2px; background: #e3e6f0; }
        .et-timeline-dot { width: 32px; height: 32px; min-width: 32px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 13px; color: #fff; z-index: 1; }
        .et-step-success .et-timeline-dot { background: #1cc88a; }
        .et-step-warning .et-timeline-dot { background: #f6c23e; }
        .et-step-danger .et-timeline-dot { background: #e74a3b; }
        .et-step-secondary .et-timeline-dot { background: #b7b9cc; }
        .et-timeline-content { flex: 1; background: #fff; border: 1px solid #e3e6f0; border-radius: 8px; padding: 10px 14px; }
        .et-timeline-top { display: flex; justify-content: space-between; align-items: center; }
        .et-timeline-level { font-weight: 700; font-size: 13px; color: #3a3b45; }
        .et-timeline-badge { font-size: 11px; font-weight: 700; text-transform: uppercase; color: #858796; }
        .et-timeline-approver { font-size: 13px; color: #5a5c69; margin-top: 2px; }
        .et-timeline-note { margin-top: 6px; font-size: 12.5px; color: #e74a3b; background: #fdeeed; border-radius: 5px; padding: 6px 8px; }
        .et-timeline-toggle { cursor: pointer; user-select: none; }
        .et-timeline-chevron { margin-left: auto; margin-right: 0; transition: transform 0.2s ease; font-size: 13px; }
        .et-timeline-toggle.et-open .et-timeline-chevron { transform: rotate(180deg); }
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
                            <h1><?= __('employee_transfer_approval_center', 'Employee Transfer Approval Center') ?></h1>
                            <p><?= sr_h($page_title) ?> &middot; <?= __('total_found') ?>: <?= (int)$total_items ?></p>
                        </div>
                    </div>

                    <div class="sr-notice tone-sky">
                        <i class="mdi mdi-information-outline"></i>
                        <div><?= __('request_employee_transfer_hint', 'To request an employee transfer, open that employee\'s profile and use "More Actions" > "Request Employee Transfer".') ?></div>
                    </div>

                    <div class="sr-card">
                        <?= sr_status_tabs($all_statuses, $current_filter, $total_items) ?>
                        <?= sr_list_toolbar($search_term) ?>

                        <?php if (!empty($requests)): ?>
                            <div class="sr-table-wrap sr-list-wrap">
                                <table class="table sr-table sr-list" style="width: 100%;">
                                    <thead>
                                        <tr>
                                            <th><?= __('employee', 'Employee') ?></th>
                                            <th><?= __('transfer_type') ?></th>
                                            <th><?= __('new_supervisor', 'New supervisor') ?></th>
                                            <th><?= __('transfer_dates') ?></th>
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
                                            } elseif ($req['current_status'] === 'completed') {
                                                $status_text = __('completed');
                                                $status_tone = 'indigo';
                                            } elseif ($req['current_status'] === 'rejected') {
                                                $status_text = __('rejected');
                                                $status_tone = 'red';
                                            } else {
                                                $status_text = __('pending_approval');
                                                $status_tone = 'amber';
                                            }

                                            $can_take_action = ((string)($req['current_approver_id'] ?? '') === (string)$empid || ($delegatedFromEmpId !== null && (string)($req['current_approver_id'] ?? '') === (string)$delegatedFromEmpId)) && (($req['current_status'] ?? '') === 'pending_approval');
                                            $is_temp = ($req['transfer_type'] === 'temporary');
                                            $type_label = $is_temp ? __('temporary', 'Temporary') : __('permanent', 'Permanent');
                                            $inv_js = htmlspecialchars((string)$req['request_inv_no'], ENT_QUOTES);
                                            $name_js = htmlspecialchars(getDisplayName(parseName($req['target_name'])), ENT_QUOTES);
                                            ?>
                                            <tr>
                                                <td><?= sr_person_cell(getDisplayName(parseName($req['target_name'])), $req['target_emp_id']) ?></td>
                                                <td>
                                                    <?= sr_pill($is_temp ? 'sky' : 'indigo', $type_label, true) ?>
                                                    <span class="sr-cell-sub sr-mono"><?= sr_h($req['request_inv_no']) ?></span>
                                                </td>
                                                <td>
                                                    <?php if (!empty($req['to_supervisor_name'])): ?>
                                                        <span class="sr-cell-title"><?= sr_h(getDisplayName(parseName($req['to_supervisor_name']))) ?></span>
                                                    <?php endif; ?>
                                                    <?php if (!empty($req['from_supervisor_name'])): ?>
                                                        <span class="sr-cell-sub"><?= __('from', 'From') ?>: <?= sr_h(getDisplayName(parseName($req['from_supervisor_name']))) ?></span>
                                                    <?php endif; ?>
                                                    <?php if (empty($req['to_supervisor_name']) && empty($req['from_supervisor_name'])): ?>
                                                        <span class="text-muted">&ndash;</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="sr-period">
                                                    <span class="sr-cell-title"><?= sr_h(format_safe_date($req['start_date'] ?? null, 'd M Y', 'N/A')) ?><?php if (!empty($req['end_date'])): ?><i class="mdi mdi-arrow-right"></i><?= sr_h(format_safe_date($req['end_date'], 'd M Y', 'N/A')) ?><?php endif; ?></span>
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
                                                            <a href="javascript:void(0);" class="sr-open-btn" onclick="approveEmployeeTransferRequest('<?= $inv_js ?>', '<?= $name_js ?>')"><i class="mdi mdi-check"></i> <?= __('approve') ?></a>
                                                        <?php else: ?>
                                                            <a href="javascript:void(0);" class="sr-open-btn" onclick="viewEmployeeTransferDetails('<?= $inv_js ?>')"><i class="mdi mdi-file-document"></i> <?= __('report') ?></a>
                                                        <?php endif; ?>
                                                        <?php if ($can_take_action): ?>
                                                            <div class="btn-group dropdown">
                                                                <a href="javascript:void(0);" class="sr-more-btn dropdown-toggle arrow-none" data-toggle="dropdown" data-boundary="viewport" aria-haspopup="true" aria-expanded="false"><i class="mdi mdi-dots-vertical"></i></a>
                                                                <div class="dropdown-menu dropdown-menu-right">
                                                                    <a class="dropdown-item" href="javascript:void(0);" onclick="viewEmployeeTransferDetails('<?= $inv_js ?>')"><i class="mdi mdi-file-document"></i><?= __('report') ?></a>
                                                                    <div class="dropdown-divider"></div>
                                                                    <a class="dropdown-item" href="javascript:void(0);" onclick="rejectEmployeeTransferRequest('<?= $inv_js ?>', '<?= $name_js ?>')"><i class="mdi mdi-close text-danger"></i><?= __('reject') ?></a>
                                                                </div>
                                                            </div>
                                                        <?php endif; ?>
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
                                echo generate_pagination_controls($current_page, $total_pages, $total_items, $items_per_page, $limit_options, $show_all, $pagination_params, $total_items);
                                ?>
                            </div>
                        <?php else: ?>
                            <?= sr_empty_state(__('no_employee_transfer_requests_found', 'No employee transfer requests found'), __('no_requests_matching_filters')) ?>
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
    <script src="plugins/moment/moment.min.js"></script>
    <script src="plugins/bootstrap-daterangepicker/daterangepicker.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="./plugins/select2/js/select2.min.js"></script>
    <script src="assets/js/jquery.core.js"></script>
    <script src="assets/js/jquery.app.js?t=<?= time() ?>"></script>
        <?= sr_list_js() ?>
    <script>
        function applyFilters() {
            const status = document.getElementById('statusFilter').value;
            const search = document.getElementById('searchFilter').value;
            const baseUrl = window.location.href.split('?')[0];
            window.location.href = `${baseUrl}?status=${status}&search=${encodeURIComponent(search)}&page=1`;
        }

        function viewEmployeeTransferDetails(requestInvNo) {
            Swal.fire({
                title: __('loading', 'Loading...'),
                allowOutsideClick: false,
                allowEscapeKey: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            $.ajax({
                url: './includes/ajaxFile/ajaxEmployeeTransfer.php',
                type: 'POST',
                dataType: 'JSON',
                data: { ajaxType: 'getEmployeeTransferDetails', request_inv_no: requestInvNo },
                success: function(response) {
                    if (response.status !== 'success') {
                        Swal.fire({
                            title: __('error', 'Error'),
                            text: response.message || __('request_not_found', 'Employee transfer request not found.'),
                            icon: 'error',
                            confirmButtonColor: APP_COLORS.danger
                        });
                        return;
                    }

                    const t = response.data.transfer;
                    const chain = response.data.approval_chain || [];
                    const typeLabel = t.transfer_type === 'temporary' ? __('temporary', 'Temporary') : __('permanent', 'Permanent');
                    const typeIcon = t.transfer_type === 'temporary' ? 'fa-clock' : 'fa-infinity';
                    const datesText = t.start_date + (t.end_date ? ' &rarr; ' + t.end_date : '');

                    const statusMeta = {
                        pending_approval: { label: __('pending_approval', 'Pending Approval'), cls: 'et-status-warning' },
                        approved:          { label: __('approved', 'Approved'), cls: 'et-status-success' },
                        rejected:          { label: __('rejected', 'Rejected'), cls: 'et-status-danger' },
                        completed:         { label: __('completed', 'Completed'), cls: 'et-status-primary' },
                        cancelled:         { label: __('cancelled', 'Cancelled'), cls: 'et-status-secondary' }
                    };
                    const sMeta = statusMeta[t.current_status] || { label: t.current_status, cls: 'et-status-secondary' };

                    const infoRow = (icon, label, value) => `
                        <div class="et-report-row">
                            <div class="et-report-row-label"><i class="fa ${icon}"></i> ${label}</div>
                            <div class="et-report-row-value">${value || 'N/A'}</div>
                        </div>`;

                    let html = '<div class="et-report">';

                    // --- Header banner ---
                    html += `
                        <div class="et-report-header">
                            <div class="et-report-avatar"><i class="fa fa-user"></i></div>
                            <div class="et-report-heading">
                                <div class="et-report-name">${t.target_name || ''}</div>
                                <div class="et-report-subid">${__('emp_id', 'Employee ID')}: ${t.target_emp_id}  &bull;  ${t.request_inv_no}</div>
                            </div>
                            <div class="et-report-status-pill ${sMeta.cls}">${sMeta.label}</div>
                        </div>`;

                    // --- Two-column detail cards ---
                    html += '<div class="row">';
                    html += `
                        <div class="col-md-6">
                            <div class="vacation-card">
                                <div class="vacation-card-header"><i class="fa fa-id-card"></i> ${__('employee_information', 'Employee Information')}</div>
                                ${infoRow('fa-sitemap', __('department', 'Department'), t.deptnme)}
                                ${infoRow('fa-building', __('company', 'Company'), t.compnme)}
                                ${infoRow('fa-user-tag', __('job_title', 'Job Title'), t.jobname)}
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="vacation-card">
                                <div class="vacation-card-header"><i class="fa fa-exchange-alt"></i> ${__('transfer_details', 'Transfer Details')}</div>
                                ${infoRow(typeIcon, __('transfer_type', 'Transfer Type'), typeLabel)}
                                ${infoRow('fa-calendar-alt', __('transfer_dates', 'Transfer Dates'), datesText)}
                                ${infoRow('fa-user-edit', __('requested_by', 'Requested By'), t.requester_name)}
                            </div>
                        </div>`;
                    html += '</div>';

                    // --- Supervisor handover ---
                    html += `
                        <div class="vacation-card">
                            <div class="vacation-card-header"><i class="fa fa-people-arrows"></i> ${__('supervisor_handover', 'Supervisor Handover')}</div>
                            <div class="et-handover">
                                <div class="et-handover-side">
                                    <i class="fa fa-user-minus"></i>
                                    <div class="et-handover-label">${__('from_supervisor', 'From Supervisor')}</div>
                                    <div class="et-handover-name">${t.from_supervisor_name || 'N/A'}</div>
                                </div>
                                <div class="et-handover-arrow"><i class="fa fa-long-arrow-alt-right"></i></div>
                                <div class="et-handover-side">
                                    <i class="fa fa-user-plus"></i>
                                    <div class="et-handover-label">${__('to_supervisor', 'To Supervisor')}</div>
                                    <div class="et-handover-name">${t.to_supervisor_name || 'N/A'}</div>
                                </div>
                            </div>
                        </div>`;

                    // --- Notes ---
                    if (t.request_notes) {
                        html += `
                            <div class="vacation-card">
                                <div class="vacation-card-header"><i class="fa fa-sticky-note"></i> ${__('additional_notes', 'Additional Notes')}</div>
                                <div class="et-notes-text">${t.request_notes.replace(/\n/g, '<br>')}</div>
                            </div>`;
                    }

                    // --- Approval chain timeline (collapsed by default) ---
                    html += `
                        <div class="vacation-card">
                            <div class="vacation-card-header et-timeline-toggle" onclick="toggleEtApprovalChain(this)" role="button">
                                <i class="fa fa-sitemap"></i> ${__('approval_chain', 'Approval Chain')}
                                <i class="fa fa-chevron-down et-timeline-chevron"></i>
                            </div>
                            <div class="et-timeline d-none">`;
                    chain.forEach(level => {
                        const stepMeta = {
                            approved: { icon: 'fa-check', cls: 'et-step-success', label: __('approved', 'Approved') },
                            pending:  { icon: 'fa-clock', cls: 'et-step-warning', label: __('pending', 'Pending') },
                            rejected: { icon: 'fa-times', cls: 'et-step-danger', label: __('rejected', 'Rejected') },
                            awaiting: { icon: 'fa-hourglass-half', cls: 'et-step-secondary', label: __('awaiting', 'Awaiting') }
                        };
                        const step = stepMeta[level.status] || stepMeta.awaiting;
                        html += `
                            <div class="et-timeline-item ${step.cls}">
                                <div class="et-timeline-dot"><i class="fa ${step.icon}"></i></div>
                                <div class="et-timeline-content">
                                    <div class="et-timeline-top">
                                        <span class="et-timeline-level">${__('level', 'Level')} ${level.approval_level}</span>
                                        <span class="et-timeline-badge">${step.label}</span>
                                    </div>
                                    <div class="et-timeline-approver">${level.approver_name || __('not_assigned', 'Not Assigned')}</div>
                                    ${(level.status === 'rejected' && level.note) ? `<div class="et-timeline-note"><i class="fas fa-ban"></i> ${level.note}</div>` : ''}
                                </div>
                            </div>`;
                    });
                    html += '</div></div>';

                    html += '</div>';

                    Swal.fire({
                        title: __('employee_transfer_report', 'Employee Transfer Report'),
                        html: html,
                        width: '60%',
                        padding: '20px',
                        allowOutsideClick: false,
                        confirmButtonText: __('close', 'Close'),
                        confirmButtonColor: APP_COLORS.primary,
                        customClass: {
                            popup: 'vacation-modal-popup',
                            title: 'vacation-modal-title',
                            confirmButton: 'btn-modern-confirm'
                        }
                    });
                },
                error: function() {
                    Swal.fire({
                        title: __('error', 'Error'),
                        text: __('failed_to_load_details', 'Failed to load request details.'),
                        icon: 'error',
                        confirmButtonColor: APP_COLORS.danger
                    });
                }
            });
        }

        function approveEmployeeTransferRequest(requestInvNo, employeeName) {
            Swal.fire({
                title: __('confirm_approval', 'Confirm Approval'),
                html: `<div class="text-left"><p><strong>${__('employee', 'Employee')}:</strong> ${employeeName}</p></div>`,
                input: 'textarea',
                inputLabel: __('add_approval_comment', 'Approval Comment (Optional)'),
                inputPlaceholder: __('enter_comment_here', 'Enter comment...'),
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: APP_COLORS.success,
                cancelButtonColor: APP_COLORS.danger,
                confirmButtonText: __('approve', 'Approve'),
                cancelButtonText: __('cancel', 'Cancel'),
                showLoaderOnConfirm: true,
                allowOutsideClick: false,
                preConfirm: (comment) => {
                    return new Promise((resolve, reject) => {
                        $.ajax({
                            url: './includes/ajaxFile/ajaxEmployeeTransfer.php',
                            type: 'POST',
                            dataType: 'JSON',
                            data: { ajaxType: 'approveEmployeeTransfer', request_inv_no: requestInvNo, comment: comment || '' },
                            success: function(response) {
                                if (response.status === 'success') {
                                    resolve(response);
                                } else {
                                    reject(response.message || 'Approval failed');
                                }
                            },
                            error: function() {
                                reject('Failed to process approval request.');
                            }
                        });
                    }).catch(error => {
                        Swal.showValidationMessage(error);
                    });
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    const applied = result.value.employee_transfer_applied;
                    if (applied) {
                        Swal.fire({
                            title: result.value.title || __('approved', 'Approved'),
                            html: `
                                <div class="text-left">
                                    <p>${result.value.message}</p>
                                    <div class="et-handover" style="margin-top: 15px;">
                                        <div class="et-handover-side">
                                            <i class="fa fa-user-minus"></i>
                                            <div class="et-handover-label">${__('old_direct_supervisor', 'Old Direct Supervisor')}</div>
                                            <div class="et-handover-name">${applied.old_supervisor_name || applied.old_supervisor_id || 'N/A'}</div>
                                        </div>
                                        <div class="et-handover-arrow"><i class="fa fa-long-arrow-alt-right"></i></div>
                                        <div class="et-handover-side">
                                            <i class="fa fa-user-plus"></i>
                                            <div class="et-handover-label">${__('new_direct_supervisor', 'New Direct Supervisor')}</div>
                                            <div class="et-handover-name">${applied.new_supervisor_name || applied.new_supervisor_id || 'N/A'}</div>
                                        </div>
                                    </div>
                                </div>`,
                            icon: 'success',
                            width: '40%',
                            confirmButtonColor: APP_COLORS.success,
                            allowOutsideClick: false,
                            customClass: { popup: 'vacation-modal-popup', title: 'vacation-modal-title' }
                        }).then(() => location.reload());
                    } else {
                        Swal.fire({
                            title: result.value.title || 'Approved',
                            text: result.value.message,
                            icon: 'success',
                            confirmButtonColor: APP_COLORS.success,
                            allowOutsideClick: false
                        }).then(() => location.reload());
                    }
                }
            });
        }

        function rejectEmployeeTransferRequest(requestInvNo, employeeName) {
            Swal.fire({
                title: __('confirm_rejection', 'Confirm Rejection'),
                html: `<div class="text-left"><p><strong>${__('employee', 'Employee')}:</strong> ${employeeName}</p></div>`,
                input: 'textarea',
                inputLabel: __('provide_rejection_reason', 'Provide rejection reason'),
                inputPlaceholder: __('enter_reason_here', 'Enter rejection reason...'),
                inputValidator: (value) => {
                    if (!value) return __('rejection_reason_required', 'Please provide a rejection reason');
                },
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: APP_COLORS.danger,
                cancelButtonColor: APP_COLORS.secondary || '#6c757d',
                confirmButtonText: __('reject', 'Reject'),
                cancelButtonText: __('cancel', 'Cancel'),
                showLoaderOnConfirm: true,
                allowOutsideClick: false,
                preConfirm: (comment) => {
                    return new Promise((resolve, reject) => {
                        $.ajax({
                            url: './includes/ajaxFile/ajaxEmployeeTransfer.php',
                            type: 'POST',
                            dataType: 'JSON',
                            data: { ajaxType: 'rejectEmployeeTransfer', request_inv_no: requestInvNo, comment: comment },
                            success: function(response) {
                                if (response.status === 'success') {
                                    resolve(response);
                                } else {
                                    reject(response.message || 'Rejection failed');
                                }
                            },
                            error: function() {
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
                        text: result.value.message,
                        icon: 'success',
                        confirmButtonColor: APP_COLORS.danger,
                        allowOutsideClick: false
                    }).then(() => location.reload());
                }
            });
        }
    </script>
</body>
</html>
