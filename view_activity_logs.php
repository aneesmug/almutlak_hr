<?php
require_once(__DIR__ . "/includes/init.php");
require_once(__DIR__ . "/includes/session_check.php");

// Check admin access
// (or an explicit 'Access Page: Activity Logs' Special Access grant)
if (!($is_system_admin ?? false) && !user_has_special_access($conDB, $empid ?? '', 'access_view_activity_logs', $user_role ?? '', $user_type ?? '', $is_system_admin ?? false)) {
    $_SESSION['error_msg'] = '<div class="alert alert-danger">Access Denied! Only administrators can view activity logs.</div>';
    header('Location: dashboard.php');
    exit;
}

// Log this view
ActivityLogger::log('VIEW', 'Activity Logs', 'Viewed activity logs dashboard', [
    'page' => 'view_activity_logs.php'
]);

// Get filter parameters
$filter_user = $_GET['user'] ?? '';
$filter_module = $_GET['module'] ?? '';
$filter_page = $_GET['page_name'] ?? '';
$filter_action = $_GET['action_type'] ?? '';
$filter_date_from = $_GET['date_from'] ?? '';
$filter_date_to = $_GET['date_to'] ?? '';
$limit = (int)($_GET['limit'] ?? 50);

// Build filters
$where_clauses = [];
$params = [];
$types = '';

if ($filter_user !== '') {
    $where_clauses[] = "user_id LIKE ?";
    $params[] = "%$filter_user%";
    $types .= 's';
}

if ($filter_module !== '') {
    $where_clauses[] = "module LIKE ?";
    $params[] = "%$filter_module%";
    $types .= 's';
}

if ($filter_page !== '') {
    $where_clauses[] = "page LIKE ?";
    $params[] = "%$filter_page%";
    $types .= 's';
}

if ($filter_action !== '') {
    $where_clauses[] = "action_type = ?";
    $params[] = $filter_action;
    $types .= 's';
}

if ($filter_date_from !== '') {
    $where_clauses[] = "DATE(created_at) >= ?";
    $params[] = $filter_date_from;
    $types .= 's';
}

if ($filter_date_to !== '') {
    $where_clauses[] = "DATE(created_at) <= ?";
    $params[] = $filter_date_to;
    $types .= 's';
}

$where_sql = !empty($where_clauses) ? 'WHERE ' . implode(' AND ', $where_clauses) : '';

// Fetch logs
$logs = [];
$sql = "SELECT * FROM activity_log $where_sql ORDER BY created_at DESC LIMIT ?";
$params_with_limit = $params;
$types_with_limit = $types . 'i';
$params_with_limit[] = $limit;

$stmt = mysqli_prepare($conDB, $sql);
if ($stmt) {
    if (!empty($params_with_limit)) {
        mysqli_stmt_bind_param($stmt, $types_with_limit, ...$params_with_limit);
    }
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $logs = mysqli_fetch_all($result, MYSQLI_ASSOC);
    mysqli_stmt_close($stmt);
}

// Stats
$stats_sql = "SELECT 
    COUNT(*) as total_logs,
    COUNT(DISTINCT user_id) as unique_users,
    COUNT(DISTINCT module) as unique_modules,
    COUNT(DISTINCT page) as unique_pages,
    COUNT(CASE WHEN action_type = 'CREATE' THEN 1 END) as creates,
    COUNT(CASE WHEN action_type = 'UPDATE' THEN 1 END) as updates,
    COUNT(CASE WHEN action_type = 'DELETE' THEN 1 END) as deletes,
    COUNT(CASE WHEN DATE(created_at) = CURDATE() THEN 1 END) as today_actions
FROM activity_log $where_sql";

$stats = [];
if (!empty($where_clauses)) {
    $stats_stmt = mysqli_prepare($conDB, $stats_sql);
    if ($stats_stmt) {
        // remove limit param
        $stats_types = $types;
        $stats_params = $params;
        if (!empty($stats_params)) {
            mysqli_stmt_bind_param($stats_stmt, $stats_types, ...$stats_params);
        }
        mysqli_stmt_execute($stats_stmt);
        $stats_result = mysqli_stmt_get_result($stats_stmt);
        $stats = mysqli_fetch_assoc($stats_result);
        mysqli_stmt_close($stats_stmt);
    }
} else {
    $stats_res = mysqli_query($conDB, $stats_sql);
    if ($stats_res) {
        $stats = mysqli_fetch_assoc($stats_res);
    }
}

// Dropdown data
$unique_users = mysqli_fetch_all(mysqli_query($conDB, "SELECT DISTINCT user_id, user_name FROM activity_log ORDER BY user_id"), MYSQLI_ASSOC);
$unique_modules = mysqli_fetch_all(mysqli_query($conDB, "SELECT DISTINCT module FROM activity_log ORDER BY module"), MYSQLI_ASSOC);
$unique_pages = mysqli_fetch_all(mysqli_query($conDB, "SELECT DISTINCT page FROM activity_log ORDER BY page"), MYSQLI_ASSOC);

$action_types = ['CREATE', 'UPDATE', 'DELETE', 'LOGIN', 'LOGOUT', 'VIEW', 'DOWNLOAD', 'UPLOAD', 'APPROVE', 'REJECT', 'SUBMIT', 'EXPORT', 'IMPORT', 'OTHER'];
$val_action_tones = [
    'CREATE' => 'tone-green', 'APPROVE' => 'tone-green', 'UPDATE' => 'tone-sky', 'SUBMIT' => 'tone-sky',
    'DELETE' => 'tone-red', 'REJECT' => 'tone-red', 'VIEW' => 'tone-amber', 'EXPORT' => 'tone-indigo',
    'IMPORT' => 'tone-indigo', 'UPLOAD' => 'tone-indigo', 'DOWNLOAD' => 'tone-indigo',
];
?>
<!doctype html>
<html lang="<?= $current_lang ?? 'en' ?>" <?= ($is_rtl ?? false) ? 'dir="rtl"' : '' ?>>

<head>
    <meta charset="utf-8" />
    <title><?= $site_title ?> - Activity Logs</title>
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta content="Anees Afzal" name="author" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />

    <link rel="shortcut icon" href="<?=get_setting($conDB, 'favicon')?>">

    <!-- DataTables -->
    <link href="./plugins/datatables/dataTables.bootstrap4.min.css" rel="stylesheet" type="text/css" />
    <link href="./plugins/datatables/buttons.bootstrap4.min.css" rel="stylesheet" type="text/css" />
    <link href="./plugins/datatables/responsive.bootstrap4.min.css" rel="stylesheet" type="text/css" />

    <!-- App css -->
    <link href="assets/css/bootstrap.min.css" rel="stylesheet" type="text/css" />
    <link href="assets/css/icons.css" rel="stylesheet" type="text/css" />
    <link href="assets/css/metismenu.min.css" rel="stylesheet" type="text/css" />
    <link href="assets/css/style.css" rel="stylesheet" type="text/css" />
    <link href="assets/css/style_dark.css" rel="stylesheet" type="text/css" />
    <?php if ($is_rtl): ?>
        <link href="assets/css/style_rtl.css" rel="stylesheet" type="text/css" />
    <?php endif; ?>

    <script src="assets/js/modernizr.min.js"></script>

    <link href="assets/css/smart_request.css?v=<?= @filemtime(__DIR__ . '/assets/css/smart_request.css') ?>" rel="stylesheet" type="text/css" />
    <style>
        .val-stats { display: grid; grid-template-columns: repeat(7, minmax(0, 1fr)); gap: 12px; margin-bottom: 20px; }
        @media (max-width: 1399px) { .val-stats { grid-template-columns: repeat(4, minmax(0, 1fr)); } }
        @media (max-width: 767px) { .val-stats { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
        .val-stats .sr-stat { background: var(--sr-surface); }
        .sr-page .val-desc { max-width: 280px; font-weight: 500; }
        .val-pre { margin: 6px 0 0; padding: 10px 12px; border-radius: 10px; font-size: 12px; text-align: start; white-space: pre-wrap; word-break: break-word; max-height: 260px; overflow: auto; border: 1px solid; }
        .val-h { margin: 0; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .4px; text-align: start; }
    </style>
</head>

<body class="enlarged" data-keep-enlarged="true">

    <div id="wrapper">

        <!-- ========== Left Sidebar Start ========== -->
        <div class="left side-menu">
            <div class="slimscroll-menu" id="remove-scroll">
                <div class="topbar-left">
                    <a href="dashboard.php" class="logo">
                        <span>
                            <img src="<?=get_setting($conDB, 'logo')?>" alt="" height="22">
                        </span>
                        <i>
                            <img src="<?=get_setting($conDB, 'white_logo')?>" alt="" height="28">
                        </i>
                    </a>
                </div>

                <?php include("./includes/main_menu.php"); ?>

                <div class="clearfix"></div>
            </div>
        </div>
        <!-- Left Sidebar End -->

        <!-- ============================================================== -->
        <!-- Start right Content here -->
        <!-- ============================================================== -->
        <div class="content-page">

            <!-- Top Bar Start -->
            <?php include("./includes/topbar.php"); ?>
            <!-- Top Bar End -->

            <div class="content sr-page">
                <div class="container-fluid">
                    <div class="sr-head">
                        <div>
                            <h1>Activity Logs</h1>
                            <p>Audit trail across all modules</p>
                        </div>
                        <div class="sr-head-actions">
                            <a href="dashboard.php" class="sr-btn"><i class="mdi mdi-arrow-left"></i> Dashboard</a>
                            <a href="?export=csv" class="sr-btn sr-btn-success"><i class="mdi mdi-file-excel"></i> Export CSV</a>
                        </div>
                    </div>

                    <!-- Statistics -->
                    <div class="val-stats">
                        <?php foreach ([
                            ['Total Logs', $stats['total_logs'] ?? 0, 'mdi-format-list-bulleted', 'is-sky'],
                            ['Creates', $stats['creates'] ?? 0, 'mdi-plus-circle-outline', 'is-green'],
                            ['Updates', $stats['updates'] ?? 0, 'mdi-pencil', 'is-sky'],
                            ['Deletes', $stats['deletes'] ?? 0, 'mdi-delete', 'is-red'],
                            ["Today's Actions", $stats['today_actions'] ?? 0, 'mdi-flash', 'is-amber'],
                            ['Active Users', $stats['unique_users'] ?? 0, 'mdi-account-multiple', 'is-sky'],
                            ['Modules Tracked', $stats['unique_modules'] ?? 0, 'mdi-view-module', 'is-green'],
                        ] as $st): ?>
                            <div class="sr-stat <?= $st[3] ?>">
                                <div class="sr-stat-label"><?= htmlspecialchars($st[0]) ?> <i class="mdi <?= $st[2] ?>"></i></div>
                                <div class="sr-stat-value"><?= number_format((float)$st[1]) ?></div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <div class="sr-card">
                        <!-- Filters -->
                        <form method="GET" class="sr-filter-grid" style="border-bottom: 1px solid var(--sr-border);">
                            <div>
                                <label>User</label>
                                <select name="user" class="form-control">
                                    <option value="">All Users</option>
                                    <?php foreach ($unique_users as $user): ?>
                                        <option value="<?= htmlspecialchars($user['user_id']) ?>" <?= $filter_user == $user['user_id'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($user['user_id']) ?><?php if (!empty($user['user_name'])): ?> - <?= htmlspecialchars($user['user_name']) ?><?php endif; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div>
                                <label>Module</label>
                                <select name="module" class="form-control">
                                    <option value="">All Modules</option>
                                    <?php foreach ($unique_modules as $mod): ?>
                                        <option value="<?= htmlspecialchars($mod['module']) ?>" <?= $filter_module == $mod['module'] ? 'selected' : '' ?>><?= htmlspecialchars($mod['module']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div>
                                <label>Page</label>
                                <select name="page_name" class="form-control">
                                    <option value="">All Pages</option>
                                    <?php foreach ($unique_pages as $page): ?>
                                        <option value="<?= htmlspecialchars($page['page']) ?>" <?= $filter_page == $page['page'] ? 'selected' : '' ?>><?= htmlspecialchars($page['page']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div>
                                <label>Action</label>
                                <select name="action_type" class="form-control">
                                    <option value="">All Actions</option>
                                    <?php foreach ($action_types as $action): ?>
                                        <option value="<?= $action ?>" <?= $filter_action == $action ? 'selected' : '' ?>><?= $action ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div>
                                <label>From Date</label>
                                <input type="date" name="date_from" class="form-control" value="<?= htmlspecialchars($filter_date_from) ?>">
                            </div>
                            <div>
                                <label>To Date</label>
                                <input type="date" name="date_to" class="form-control" value="<?= htmlspecialchars($filter_date_to) ?>">
                            </div>
                            <div>
                                <label>Limit</label>
                                <select name="limit" class="form-control">
                                    <?php foreach ([50, 100, 500, 1000] as $lim): ?>
                                        <option value="<?= $lim ?>" <?= $limit == $lim ? 'selected' : '' ?>><?= $lim ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="sr-filter-actions">
                                <button type="submit" class="sr-btn sr-btn-primary"><i class="mdi mdi-filter-variant"></i> Apply</button>
                                <a href="view_activity_logs.php" class="sr-btn"><i class="mdi mdi-refresh"></i> Clear</a>
                            </div>
                        </form>

                        <div class="sr-toolbar">
                            <h2 class="sr-card-title" style="margin-inline-end: auto;"><i class="mdi mdi-history"></i> Activity Logs <span class="sr-count"><?= count($logs) ?></span></h2>
                            <div class="sr-search">
                                <i class="mdi mdi-magnify"></i>
                                <input type="search" id="logsSearch" placeholder="Search logs..." autocomplete="off">
                            </div>
                        </div>

                        <div class="sr-table-wrap">
                            <table id="logsTable" class="table sr-table dt-responsive nowrap" style="width:100%">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Date/Time</th>
                                        <th>User</th>
                                        <th>Module</th>
                                        <th>Action</th>
                                        <th>Page</th>
                                        <th>Description</th>
                                        <th>Details</th>
                                        <th>IP Address</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($logs as $log):
                                        $act = strtoupper((string)$log['action_type']);
                                        $actTone = $val_action_tones[$act] ?? 'tone-slate';
                                    ?>
                                        <tr>
                                            <td><span class="sr-chip sr-mono"><?= (int)$log['id'] ?></span></td>
                                            <td data-order="<?= htmlspecialchars($log['created_at']) ?>">
                                                <div class="sr-date"><?= date('Y-m-d', strtotime($log['created_at'])) ?><small><?= date('H:i:s', strtotime($log['created_at'])) ?></small></div>
                                            </td>
                                            <td>
                                                <span class="sr-cell-title"><?= htmlspecialchars($log['user_id']) ?></span>
                                                <?php if (!empty($log['user_name'])): ?>
                                                    <span class="sr-cell-sub"><?= htmlspecialchars($log['user_name']) ?></span>
                                                <?php endif; ?>
                                            </td>
                                            <td><span class="sr-chip"><?= htmlspecialchars($log['module']) ?></span></td>
                                            <td><span class="sr-pill sr-pill-xs <?= $actTone ?>"><?= htmlspecialchars($act) ?></span></td>
                                            <td><code><?= htmlspecialchars($log['page']) ?></code></td>
                                            <td><span class="sr-cell-title val-desc" title="<?= htmlspecialchars($log['description'] ?? '') ?>"><?= htmlspecialchars($log['description'] ?? '-') ?></span></td>
                                            <td>
                                                <?php if ($log['old_values'] || $log['new_values']): ?>
                                                    <button type="button" class="sr-open-btn border-0 val-details"
                                                        data-id="<?= (int)$log['id'] ?>"
                                                        data-old="<?= htmlspecialchars($log['old_values'] ?? '', ENT_QUOTES) ?>"
                                                        data-new="<?= htmlspecialchars($log['new_values'] ?? '', ENT_QUOTES) ?>"><i class="mdi mdi-eye-outline"></i> View</button>
                                                <?php else: ?>
                                                    <span class="text-muted">-</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><span class="sr-mono"><?= htmlspecialchars($log['ip_address'] ?? '-') ?></span></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div> <!-- container-fluid -->
            </div> <!-- content -->

            <footer class="footer">
                <?= $site_footer ?>
            </footer>

        </div> <!-- content-page -->
    </div> <!-- wrapper -->

    <!-- jQuery  -->
    <script src="assets/js/jquery.min.js"></script>
    <script src="assets/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/metisMenu.min.js"></script>
    <script src="assets/js/waves.js"></script>
    <script src="assets/js/jquery.slimscroll.js"></script>

    <!-- DataTables -->
    <script src="./plugins/datatables/jquery.dataTables.min.js"></script>
    <script src="./plugins/datatables/dataTables.bootstrap4.min.js"></script>
    <script src="./plugins/datatables/dataTables.responsive.min.js"></script>
    <script src="./plugins/datatables/responsive.bootstrap4.min.js"></script>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <!-- App js -->
    <script src="assets/js/jquery.core.js"></script>
    <script src="assets/js/jquery.app.js?t=<?= time() ?>"></script>

    <script>
        $(document).ready(function() {
            if ($.fn.DataTable.isDataTable('#logsTable')) {
                $('#logsTable').DataTable().destroy();
            }
            var logsTable = $('#logsTable').DataTable({
                dom: 'rtip',
                order: [[0, 'desc']],
                pageLength: 25,
                responsive: true,
                language: {
                    info: "Showing _START_ to _END_ of _TOTAL_ logs",
                    infoEmpty: "No logs found",
                    infoFiltered: "",
                    emptyTable: '<div class="sr-empty"><i class="mdi mdi-history"></i>No logs found</div>',
                    zeroRecords: '<div class="sr-empty"><i class="mdi mdi-magnify"></i>No matching logs</div>',
                    paginate: { next: '<i class="mdi mdi-chevron-right"></i>', previous: '<i class="mdi mdi-chevron-left"></i>' }
                }
            });
            $('#logsSearch').on('input', function() { logsTable.search(this.value).draw(); });

            $('#logsTable').on('click', '.val-details', function() {
                showDetails($(this).data('id'), $(this).attr('data-old'), $(this).attr('data-new'));
            });
        });

        function showDetails(id, oldVal, newVal) {
            var box = function(label, val, tone) {
                return $('<div class="mt-2">')
                    .append($('<h6 class="val-h">').css('color', 'var(--tone-' + tone + '-fg)').text(label))
                    .append($('<pre class="val-pre">').css({ background: 'var(--tone-' + tone + '-bg)', borderColor: 'var(--tone-' + tone + '-bd)', color: 'var(--sr-text)' }).text(val || '(empty)'));
            };
            var $html = $('<div class="sr-page">').append(box('Old Value', oldVal, 'red'), box('New Value', newVal, 'green'));
            Swal.fire({
                title: 'Change Details - Log #' + id,
                html: $html[0],
                width: 640,
                showCloseButton: true,
                showConfirmButton: false,
                allowOutsideClick: false,
                customClass: { popup: 'sr-addline-popup' }
            });
        }
    </script>

</body>

</html>
