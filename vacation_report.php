<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/session_check.php';
$query = mysqli_query($conDB, "SELECT * FROM `admin_login` WHERE `id_iqama`='" . $username . "'");
if (mysqli_num_rows($query) == 1) {
    include("./includes/avatar_select.php");



    // --- Search, Pagination & Filtering Logic ---
    $all_statuses = [
        'pending' => 'Pending',
        'hr_assistant_approved' => 'HR Assistant Approved',
        'hr_manager_approved' => 'HR Manager Approved',
        'gm_approved' => 'GM Approved',
        'rejected' => 'Rejected'
    ];

    // 1. Set up variables
    $search_term = $_GET['search'] ?? '';
    $limit_options = [8, 12, 16]; 
    $perpage = 8;
    $items_per_page = isset($_GET['limit']) && in_array((int)$_GET['limit'], $limit_options) ? (int)$_GET['limit'] : $perpage; // Default
    $show_all = isset($_GET['limit']) && $_GET['limit'] == 'all';
    if ($show_all) {
        $items_per_page = -1;
    }

    $current_page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
    if ($current_page < 1) {
        $current_page = 1;
    }

    $current_filter = $_GET['status'] ?? 'all'; // Default to 'all' for the report page
    
    $page_title = isset($all_statuses[$current_filter]) ? $all_statuses[$current_filter] : 'All Requests';

    // 2. Build the dynamic WHERE clause
    $where_clauses = [];
    $params = [];
    $types = "";

    // Status filter
    if ($current_filter && $current_filter !== 'all') {
        if (array_key_exists($current_filter, $all_statuses)) {
            $where_clauses[] = "v.approval_status = ?";
            $params[] = $current_filter;
            $types .= "s";
        }
    }

    // Search filter
    if (!empty($search_term)) {
        $where_clauses[] = "(e.name LIKE ? OR v.emp_id LIKE ?)";
        $search_param = "%{$search_term}%";
        $params[] = $search_param;
        $params[] = $search_param;
        $types .= "ss";
    }

    $where_sql = "";
    if (!empty($where_clauses)) {
        $where_sql = " WHERE " . implode(" AND ", $where_clauses);
    }

    // 3. Get total item count
    $count_sql = "SELECT COUNT(v.id) as total FROM emp_vacation v JOIN employees e ON v.emp_id = e.emp_id" . $where_sql;
    $stmt_count = $conDB->prepare($count_sql);
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

    // 4. Fetch requests for the current page
    $requests = [];
    if ($total_items > 0) {
        $sql = "SELECT v.*, e.name as employee_name FROM emp_vacation v JOIN employees e ON v.emp_id = e.emp_id" . $where_sql;
        $sql .= " ORDER BY v.created_at DESC";

        if (!$show_all) {
            $offset = ($current_page - 1) * $items_per_page;
            $sql .= " LIMIT ?, ?";
            $params[] = $offset;
            $params[] = $items_per_page;
            $types .= "ii";
        }

        $stmt = $conDB->prepare($sql);
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $requests[] = $row;
            }
        }
        $stmt->close();
    }

?>
    <!doctype html>
    <html lang="en">

    <head>
        <meta charset="utf-8" />
        <title><?= $site_title ?> - Vacation Reports</title>
        <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
        <meta content="Anees Afzal" name="author" />
        <meta http-equiv="X-UA-Compatible" content="IE=edge" />
        <link rel="shortcut icon" href="assets/images/favicon.ico">
        <link href="assets/css/bootstrap.min.css" rel="stylesheet" type="text/css" />
        <link href="assets/css/icons.css" rel="stylesheet" type="text/css" />
        <link href="assets/css/metismenu.min.css" rel="stylesheet" type="text/css" />
        <link href="assets/css/style.css" rel="stylesheet" type="text/css" />
        <link href="assets/css/style_dark.css" rel="stylesheet" type="text/css" />
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
        <script src="assets/js/modernizr.min.js"></script>
        <style>
            .request-card {
                border-radius: 15px;
                border: none;
                box-shadow: 0 10px 30px rgba(0, 0, 0, 0.07);
                transition: transform 0.3s ease, box-shadow 0.3s ease;
            }
            .request-card:hover {
                transform: translateY(-5px);
                box-shadow: 0 15px 40px rgba(0, 0, 0, 0.1);
            }
            .request-card .card-header {
                background-color: #fff;
                border-bottom: 1px solid #eef;
                font-weight: 600;
                font-size: 1.1em;
            }
            .request-card .card-header span {
                font-size: 0.9em;
                color: #8a94a6;
            }
            .detail-item {
                display: flex;
                align-items: center;
                margin-bottom: 1rem;
                font-size: 1.09em;
            }
            .detail-item i {
                color: #4a90e2;
                margin-right: 15px;
                width: 20px;
                text-align: center;
            }
            .detail-item strong {
                color: #8a94a6;
                min-width: 100px;
                display: inline-block;
            }
            .status-badge {
                font-size: 0.9em;
                font-weight: 600;
            }
        </style>
    </head>

    <body class="enlarged" data-keep-enlarged="true">
        <div id="wrapper">
            <div class="left side-menu">
                <div class="slimscroll-menu" id="remove-scroll">
                    <div class="topbar-left">
                        <a href="dashboard.php" class="logo">
                            <span><img src="assets/images/logo.png" alt="" height="22"></span>
                            <i><img src="assets/images/logo_sm.png" alt="" height="28"></i>
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
                                    <h4 class="header-title m-t-0 m-b-30">All Vacation Reports</h4>
                                    <a href="view_vacation_requests.php" class="btn btn-info mb-3"><i class="fas fa-clipboard-check mr-1"></i> Go to Approval Center</a>

                                    <div class="row filter-controls mx-auto mb-5">
                                        <div class="col-md-6 mb-3 mb-md-0">
                                            <div class="form-group">
                                                <label for="statusFilter" class="font-weight-bold">Filter by Status</label>
                                                <select class="form-control" id="statusFilter" onchange="applyFilters()">
                                                    <option value="all" <?php if ($current_filter == 'all') echo 'selected'; ?>>All Requests</option>
                                                    <?php foreach ($all_statuses as $status_key => $status_value): ?>
                                                        <option value="<?php echo $status_key; ?>" <?php if ($current_filter == $status_key) echo 'selected'; ?>>
                                                            <?php echo htmlspecialchars($status_value); ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="searchFilter" class="font-weight-bold">Search by Name / ID</label>
                                                <div class="input-group">
                                                    <input type="search" class="form-control" id="searchFilter" placeholder="Enter search term..." value="<?php echo htmlspecialchars($search_term); ?>">
                                                    <div class="input-group-append">
                                                        <button class="btn btn-primary" type="button" onclick="applyFilters()"><i class="fas fa-search"></i></button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="d-flex justify-content-between align-items-center mb-4">
                                        <h4 class="mb-0 text-muted">Showing: <?php echo htmlspecialchars($page_title); ?> Requests</h4>
                                        <span class="badge badge-light p-2">Total Found: <?php echo $total_items; ?></span>
                                    </div>

                                    <?php if (!empty($requests)): ?>
                                        <div class="row">
                                            <?php foreach ($requests as $req): ?>
                                                <div class="col-lg-4 col-md-6 mb-3">
                                                    <div class="card request-card h-100">
                                                        <div class="card-header">
                                                            <?php echo parseName($req['employee_name']); ?>
                                                            <span class="float-right">ID: <?php echo htmlspecialchars($req['emp_id']); ?></span>
                                                        </div>
                                                        <div class="card-body">
                                                            <div class="detail-item">
                                                                <i class="fad fa-paper-plane"></i>
                                                                <strong>Applied:</strong> <?php echo htmlspecialchars(date('d M Y', strtotime($req['created_at']))); ?>
                                                            </div>
                                                            <div class="detail-item">
                                                                <i class="fad fa-suitcase-rolling"></i>
                                                                <strong>Type:</strong> <?php echo htmlspecialchars($req['vac_type']); ?>
                                                            </div>
                                                             <div class="detail-item">
                                                                <i class="fas fa-info-circle"></i>
                                                                <strong>Status:</strong> 
                                                                <span class="badge status-badge badge-<?php 
                                                                    switch($req['approval_status']){
                                                                        case 'pending': echo 'warning'; break;
                                                                        case 'gm_approved': echo 'success'; break;
                                                                        case 'rejected': echo 'danger'; break;
                                                                        default: echo 'primary';
                                                                    }
                                                                ?>">
                                                                    <?php echo htmlspecialchars($all_statuses[$req['approval_status']]); ?>
                                                                </span>
                                                            </div>
                                                        </div>
                                                        <div class="card-footer text-right">
                                                            <a href="open_applied_vac.php?id=<?php echo $req['id']; ?>&emp_id=<?php echo $req['emp_id']; ?>" class="btn btn-primary" target="_blank">
                                                                <i class="fas fa-eye"></i> View Report
                                                            </a>
                                                        </div>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                        <?php
                                        $pagination_params = ['status' => $current_filter, 'limit' => $show_all ? 'all' : $items_per_page, 'search' => $search_term];
                                        echo generate_pagination_controls($current_page, $total_pages, $items_per_page, $limit_options, $show_all, $pagination_params);
                                        ?>
                                    <?php else: ?>
                                        <div class="row justify-content-center">
                                            <div class="col-md-8 text-center no-requests">
                                                <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                                                <h2>No Reports Found</h2>
                                                <p class="text-muted">There are no vacation reports matching your current filters.</p>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <footer class="footer">
                    <?= $site_footer ?>
                </footer>
            </div>
        </div>
        <script src="assets/js/jquery.min.js"></script>
        <script src="assets/js/bootstrap.bundle.min.js"></script>
        <script src="assets/js/metisMenu.min.js"></script>
        <script src="assets/js/waves.js"></script>
        <script src="assets/js/jquery.slimscroll.js"></script>
        <script src="assets/js/jquery.core.js"></script>
        <script src="assets/js/jquery.app.js"></script>
        <script>
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
        </script>
    </body>
    </html>
<?php
    $conDB->close();
}
?>
