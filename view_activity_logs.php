<?php
require_once(__DIR__ . "/includes/init.php");
require_once(__DIR__ . "/includes/session_check.php");

// Check admin access
if (!($is_system_admin ?? false )) {
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

    <style>
        /* Match dashboard stats cards */
        .stats-card {
            border-radius: 16px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.08);
            padding: 24px 20px;
            margin-bottom: 20px;
            transition: box-shadow 0.2s, transform 0.2s;
            border: none;
            position: relative;
            min-height: 160px;
            display: flex;
            flex-direction: row;
            align-items: center;
            background: var(--card-gradient, linear-gradient(90deg,#556ee6 0%,#50a5f1 100%));
            color: #fff;
            overflow: hidden;
        }
        .stats-card[data-color="primary"] { --card-gradient: linear-gradient(90deg,#556ee6 0%,#50a5f1 100%); }
        .stats-card[data-color="success"] { --card-gradient: linear-gradient(90deg,#34c38f 0%,#43e97b 100%); }
        .stats-card[data-color="info"]    { --card-gradient: linear-gradient(90deg,#50a5f1 0%,#2196f3 100%); }
        .stats-card[data-color="danger"]  { --card-gradient: linear-gradient(90deg,#f46a6a 0%,#ff6a88 100%); }
        .stats-card[data-color="warning"] { --card-gradient: linear-gradient(90deg,#f1b44c 0%,#ffde7d 100%); }
        .stats-card[data-color="secondary"] { --card-gradient: linear-gradient(90deg,#6c757d 0%,#343a40 100%); }
        .stats-card:hover { box-shadow: 0 8px 32px rgba(0,0,0,0.18); transform: translateY(-4px) scale(1.01); }
        .stats-card-icon {
            background: rgba(255,255,255,0.18);
            border-radius: 50%;
            width: 72px;
            height: 72px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 32px;
            margin-right: 18px;
            box-shadow: 0 2px 16px rgba(0,0,0,0.12);
            position: relative;
            flex-direction: column;
        }
        .stats-card-count-circle {
            background: #fff;
            color: #2196f3;
            border-radius: 50%;
            width: 38px;
            height: 38px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            font-weight: 700;
            margin-bottom: 6px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.10);
        }
        .stats-card-content { flex: 1; display: flex; flex-direction: column; }
        .stats-card-label { font-size: 16px; font-weight: 700; margin-bottom: 10px; letter-spacing: 0.4px; }
        .stats-card-value { font-size: 26px; font-weight: 700; margin: 0; }
        .action-badge {
            padding: 5px 10px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
        }
        .badge-create { background: #28a745; color: #fff; }
        .badge-update { background: #17a2b8; color: #fff; }
        .badge-delete { background: #dc3545; color: #fff; }
        .badge-login { background: #6c757d; color: #fff; }
        .badge-logout { background: #6c757d; color: #fff; }
        .badge-view { background: #ffc107; color: #000; }
        .badge-approve { background: #28a745; color: #fff; }
        .badge-reject { background: #dc3545; color: #fff; }
        .badge-export { background: #6f42c1; color: #fff; }
        .badge-import { background: #fd7e14; color: #fff; }
        .filters-card label { font-weight: 600; }
        .table td { vertical-align: middle; }
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

            <div class="content">
                <div class="container-fluid">
                    <div class="card-box">

                        <div class="row align-items-center mb-3">
                            <div class="col">
                                <h4 class="header-title mb-1">📊 Activity Logs</h4>
                                <p class="text-muted mb-0">Audit trail across all modules</p>
                            </div>
                            <div class="col-auto">
                                <a href="dashboard.php" class="btn btn-secondary">← Back to Dashboard</a>
                                <a href="?export=csv" class="btn btn-success">📥 Export CSV</a>
                            </div>
                        </div>

                        <!-- Statistics Cards -->
                        <div class="row m-b-20">
                            <div class="col-md-3 m-b-10">
                                <div class="stats-card" data-color="primary">
                                    <div class="stats-card-icon" data-color="primary">
                                        <div class="stats-card-count-circle"><i class="fa fa-list"></i></div>
                                    </div>
                                    <div class="stats-card-content">
                                        <div class="stats-card-label">Total Logs</div>
                                        <p class="stats-card-value mb-0"><?= number_format($stats['total_logs'] ?? 0) ?></p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3 m-b-10">
                                <div class="stats-card" data-color="success">
                                    <div class="stats-card-icon" data-color="success">
                                        <div class="stats-card-count-circle"><i class="fa fa-plus"></i></div>
                                    </div>
                                    <div class="stats-card-content">
                                        <div class="stats-card-label">Creates</div>
                                        <p class="stats-card-value mb-0"><?= number_format($stats['creates'] ?? 0) ?></p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3 m-b-10">
                                <div class="stats-card" data-color="info">
                                    <div class="stats-card-icon" data-color="info">
                                        <div class="stats-card-count-circle"><i class="fa fa-pen"></i></div>
                                    </div>
                                    <div class="stats-card-content">
                                        <div class="stats-card-label">Updates</div>
                                        <p class="stats-card-value mb-0"><?= number_format($stats['updates'] ?? 0) ?></p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3 m-b-10">
                                <div class="stats-card" data-color="danger">
                                    <div class="stats-card-icon" data-color="danger">
                                        <div class="stats-card-count-circle"><i class="fa fa-trash"></i></div>
                                    </div>
                                    <div class="stats-card-content">
                                        <div class="stats-card-label">Deletes</div>
                                        <p class="stats-card-value mb-0"><?= number_format($stats['deletes'] ?? 0) ?></p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row m-b-20">
                            <div class="col-md-4 m-b-10">
                                <div class="stats-card" data-color="secondary">
                                    <div class="stats-card-icon" data-color="secondary">
                                        <div class="stats-card-count-circle"><i class="fa fa-bolt"></i></div>
                                    </div>
                                    <div class="stats-card-content">
                                        <div class="stats-card-label">Today's Actions</div>
                                        <p class="stats-card-value mb-0"><?= number_format($stats['today_actions'] ?? 0) ?></p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4 m-b-10">
                                <div class="stats-card" data-color="primary">
                                    <div class="stats-card-icon" data-color="primary">
                                        <div class="stats-card-count-circle"><i class="fa fa-users"></i></div>
                                    </div>
                                    <div class="stats-card-content">
                                        <div class="stats-card-label">Active Users</div>
                                        <p class="stats-card-value mb-0"><?= number_format($stats['unique_users'] ?? 0) ?></p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4 m-b-10">
                                <div class="stats-card" data-color="success">
                                    <div class="stats-card-icon" data-color="success">
                                        <div class="stats-card-count-circle"><i class="fa fa-cubes"></i></div>
                                    </div>
                                    <div class="stats-card-content">
                                        <div class="stats-card-label">Modules Tracked</div>
                                        <p class="stats-card-value mb-0"><?= number_format($stats['unique_modules'] ?? 0) ?></p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Filters -->
                        <div class="card m-b-20">
                            <div class="card-body filters-card">
                                <h5 class="card-title mb-3">🔍 Filters</h5>
                                <form method="GET" class="row g-3">
                                    <div class="col-md-2 col-sm-6">
                                        <label class="form-label">User</label>
                                        <select name="user" class="form-control">
                                            <option value="">All Users</option>
                                            <?php foreach ($unique_users as $user): ?>
                                                <option value="<?= htmlspecialchars($user['user_id']) ?>" <?= $filter_user == $user['user_id'] ? 'selected' : '' ?>>
                                                    <?= htmlspecialchars($user['user_id']) ?>
                                                    <?php if (!empty($user['user_name'])): ?>
                                                        - <?= htmlspecialchars($user['user_name']) ?>
                                                    <?php endif; ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-2 col-sm-6">
                                        <label class="form-label">Module</label>
                                        <select name="module" class="form-control">
                                            <option value="">All Modules</option>
                                            <?php foreach ($unique_modules as $mod): ?>
                                                <option value="<?= htmlspecialchars($mod['module']) ?>" <?= $filter_module == $mod['module'] ? 'selected' : '' ?>>
                                                    <?= htmlspecialchars($mod['module']) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-2 col-sm-6">
                                        <label class="form-label">Page</label>
                                        <select name="page_name" class="form-control">
                                            <option value="">All Pages</option>
                                            <?php foreach ($unique_pages as $page): ?>
                                                <option value="<?= htmlspecialchars($page['page']) ?>" <?= $filter_page == $page['page'] ? 'selected' : '' ?>>
                                                    <?= htmlspecialchars($page['page']) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-2 col-sm-6">
                                        <label class="form-label">Action</label>
                                        <select name="action_type" class="form-control">
                                            <option value="">All Actions</option>
                                            <?php foreach ($action_types as $action): ?>
                                                <option value="<?= $action ?>" <?= $filter_action == $action ? 'selected' : '' ?>><?= $action ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-2 col-sm-6">
                                        <label class="form-label">From Date</label>
                                        <input type="date" name="date_from" class="form-control" value="<?= $filter_date_from ?>">
                                    </div>
                                    <div class="col-md-2 col-sm-6">
                                        <label class="form-label">To Date</label>
                                        <input type="date" name="date_to" class="form-control" value="<?= $filter_date_to ?>">
                                    </div>
                                    <div class="col-md-2 col-sm-6">
                                        <label class="form-label">Limit</label>
                                        <select name="limit" class="form-control">
                                            <option value="50" <?= $limit == 50 ? 'selected' : '' ?>>50</option>
                                            <option value="100" <?= $limit == 100 ? 'selected' : '' ?>>100</option>
                                            <option value="500" <?= $limit == 500 ? 'selected' : '' ?>>500</option>
                                            <option value="1000" <?= $limit == 1000 ? 'selected' : '' ?>>1000</option>
                                        </select>
                                    </div>
                                    <div class="col-12">
                                        <button type="submit" class="btn btn-primary">Apply Filters</button>
                                        <a href="view_activity_logs.php" class="btn btn-light">Clear Filters</a>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <!-- Activity Logs Table -->
                        <div class="card">
                            <div class="card-body">
                                <div class="d-flex align-items-center justify-content-between mb-3">
                                    <h5 class="card-title mb-0">Activity Logs <span class="badge badge-primary"><?= count($logs) ?> records</span></h5>
                                </div>

                                <div class="table-responsive">
                                    <table id="logsTable" class="table table-striped table-bordered">
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
                                            <?php foreach ($logs as $log): ?>
                                                <tr>
                                                    <td><?= $log['id'] ?></td>
                                                    <td><small><?= date('Y-m-d H:i:s', strtotime($log['created_at'])) ?></small></td>
                                                    <td>
                                                        <strong><?= htmlspecialchars($log['user_id']) ?></strong>
                                                        <?php if (!empty($log['user_name'])): ?>
                                                            <br><small class="text-muted"><?= htmlspecialchars($log['user_name']) ?></small>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td><span class="badge badge-secondary"><?= htmlspecialchars($log['module']) ?></span></td>
                                                    <td>
                                                        <span class="action-badge badge-<?= strtolower($log['action_type']) ?>"><?= $log['action_type'] ?></span>
                                                    </td>
                                                    <td><code><?= htmlspecialchars($log['page']) ?></code></td>
                                                    <td><?= htmlspecialchars($log['description'] ?? '-') ?></td>
                                                    <td>
                                                        <?php if ($log['old_values'] || $log['new_values']): ?>
                                                            <button class="btn btn-sm btn-info" onclick="showDetails(<?= $log['id'] ?>, '<?= htmlspecialchars($log['old_values'] ?? '', ENT_QUOTES) ?>', '<?= htmlspecialchars($log['new_values'] ?? '', ENT_QUOTES) ?>')">View</button>
                                                        <?php else: ?>
                                                            <small class="text-muted">-</small>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td><small><?= htmlspecialchars($log['ip_address'] ?? '-') ?></small></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                    </div> <!-- card-box -->
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
    <script src="assets/js/jquery.app.js"></script>

    <script>
        $(document).ready(function() {
            if ($.fn.DataTable.isDataTable('#logsTable')) {
                $('#logsTable').DataTable().destroy();
            }
            $('#logsTable').DataTable({
                order: [[0, 'desc']],
                pageLength: 25,
                lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
                responsive: true,
                language: {
                    search: "Search logs:",
                    lengthMenu: "Show _MENU_ logs per page",
                    info: "Showing _START_ to _END_ of _TOTAL_ logs",
                    infoEmpty: "No logs found",
                    infoFiltered: "(filtered from _MAX_ total logs)"
                }
            });
        });

        function showDetails(id, oldVal, newVal) {
            Swal.fire({
                title: 'Change Details - Log #' + id,
                html: `
                    <div style="text-align: left;">
                        <h6 style="color: #dc3545;">Old Value:</h6>
                        <pre style="background: #f8d7da; padding: 10px; border-radius: 4px; font-size: 12px;">${oldVal || '(empty)'}</pre>
                        <h6 style="color: #28a745; margin-top: 15px;">New Value:</h6>
                        <pre style="background: #d4edda; padding: 10px; border-radius: 4px; font-size: 12px;">${newVal || '(empty)'}</pre>
                    </div>
                `,
                width: 600,
                showCloseButton: true,
                showConfirmButton: false
            });
        }
    </script>

</body>

</html>
