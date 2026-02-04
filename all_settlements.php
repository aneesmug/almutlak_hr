<?php
/**
 * All Settlements Management Page
 * Displays all settlements with approval workflow
 * Uses same layout and design as all_applied_vac.php and all_applied_loan.php
 * Integrated with ApprovalChainManager for app_settings approval chain
 */

require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/session_check.php';
require_once __DIR__ . '/includes/helper_functions.php';

// Restrict access: Employees cannot view this detailed report page
if (isset($isEmployee) && $isEmployee === true) {
    header("Location: ./profile.php");
    exit();
}

// Get Request Type ID for 'settlement'
$typeQuery = mysqli_query($conDB, "SELECT `id` FROM `approval_request_types` WHERE `type_name` = 'settlement' LIMIT 1");
if (!$typeQuery || mysqli_num_rows($typeQuery) == 0) {
    die("CRITICAL ERROR: 'settlement' type not found in `approval_request_types` table.");
}
$requestTypeId = (int)mysqli_fetch_assoc($typeQuery)['id'];
mysqli_free_result($typeQuery);

// Search, Pagination & Filtering Logic
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
$itemsPerPage = isset($_GET['limit']) && in_array((int)$_GET['limit'], $limitOptions) ? (int)$_GET['limit'] : $perpage;
$showAll = isset($_GET['limit']) && $_GET['limit'] == 'all';
if ($showAll) {
    $itemsPerPage = -1;
}

$currentPage = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
if ($currentPage < 1) {
    $currentPage = 1;
}

$currentFilter = $_GET['status'] ?? null;

// Determine effective filter: either from URL or a default based on role
if ($currentFilter === null) {
    if ($is_system_admin) {
        $currentFilter = 'all';
    } else {
        $currentFilter = 'my_pending';
    }
}

$whereClauses = [];
$params = [];
$paramTypes = "";
$joinSql = "";
$deptFilterApplied = false;

// Only HR and System Admin can see all departments
$canSeeAllDepts = ($is_system_admin ?? false) || ($isHR ?? false);
$isFinanceRole = (isset($user_type) && stripos($user_type, 'finance') !== false);
$canSeeAllDepts = $canSeeAllDepts || $isFinanceRole;

$pageTitle = $allStatuses[$currentFilter] ?? __('all_requests');

// Build WHERE clause based on filter
if ($currentFilter === 'my_pending') {
    // Settlements assigned to current user for approval
    // Use JOIN (not LEFT JOIN) to only get requests where user is the pending approver
    $joinSql = " JOIN `request_approvers` ra_pending ON ra_pending.request_inv_no = s.request_inv_no 
         AND ra_pending.request_type_id = $requestTypeId AND ra_pending.status = 'pending'";
    $whereClauses[] = "ra_pending.approver_id = ?";
    $params[] = $empid;
    $paramTypes .= "i";
    $whereClauses[] = "s.settlement_status != 'rejected'";
} elseif ($currentFilter === 'my_dept') {
    // All settlements from user's department
    $whereClauses[] = "e.dept = ?";
    $params[] = $user_dept;
    $paramTypes .= "i";
    $deptFilterApplied = true;
} elseif (in_array($currentFilter, ['pending_approval', 'approved', 'rejected', 'completed'])) {
    // Filter by settlement status
    // For 'pending_approval', also include 'pending' for backward compatibility
    if ($currentFilter === 'pending_approval') {
        $whereClauses[] = "(s.settlement_status = 'pending_approval' OR s.settlement_status = 'pending')";
    } else {
        $whereClauses[] = "s.settlement_status = ?";
        $params[] = $currentFilter;
        $paramTypes .= "s";
    }
}

// Add search term
if (!empty($searchTerm)) {
    $whereClauses[] = "(e.name LIKE ? OR s.emp_id LIKE ? OR s.request_inv_no LIKE ?)";
    $searchParam = "%{$searchTerm}%";
    array_push($params, $searchParam, $searchParam, $searchParam);
    $paramTypes .= "sss";
}

// Department scoping for non-admin users
if (!$canSeeAllDepts && !$deptFilterApplied && $currentFilter !== 'my_pending') {
    $whereClauses[] = "(e.dept = ? OR EXISTS (SELECT 1 FROM request_approvers ra_any WHERE ra_any.request_inv_no = s.request_inv_no AND ra_any.request_type_id = ? AND ra_any.approver_id = ?))";
    array_push($params, $user_dept, $requestTypeId, $empid);
    $paramTypes .= "iii";
    $deptFilterApplied = true;
}

// Add company filter to WHERE clause (same as vacation and loan pages)
$companyFilter = getCompanyFilterSQL('e.comp_no', true);
if (empty($whereClauses)) {
    $whereClauses[] = "1=1" . $companyFilter;
} else {
    $whereClauses[] = "1=1" . $companyFilter;
}


$whereSql = " WHERE " . implode(" AND ", $whereClauses);

// Count total items
// For 'my_pending' filter, use JOIN instead of LEFT JOIN since we only want assigned requests
$joinClause = ($currentFilter === 'my_pending') ? 
    $joinSql . " LEFT JOIN employees approver_emp ON ra_pending.approver_id = approver_emp.emp_id" :
    "LEFT JOIN request_approvers ra_pending ON ra_pending.request_inv_no = s.request_inv_no 
         AND ra_pending.request_type_id = $requestTypeId AND ra_pending.status = 'pending'
    LEFT JOIN employees approver_emp ON ra_pending.approver_id = approver_emp.emp_id";

$countSql = "SELECT COUNT(DISTINCT s.id) as total FROM settlement_records s 
    JOIN employees e ON s.emp_id = e.emp_id 
    " . $joinClause . " " . $whereSql;
$countStmt = $conDB->prepare($countSql);
if (!$countStmt) {
    die("Count query prepare failed: " . $conDB->error);
}
if (!empty($params)) {
    $countStmt->bind_param($paramTypes, ...$params);
}
$countStmt->execute();
$totalItems = $countStmt->get_result()->fetch_assoc()['total'] ?? 0;
$countStmt->close();

$totalPages = $showAll ? 1 : ceil($totalItems / $itemsPerPage);
if ($currentPage > $totalPages && $totalPages > 0) {
    $currentPage = $totalPages;
}

$settlements = [];
if ($totalItems > 0) {
    // Main query to fetch settlement details
    // For 'my_pending' filter, use JOIN instead of LEFT JOIN since we only want assigned requests
    $mainJoinClause = ($currentFilter === 'my_pending') ? 
        $joinSql . " LEFT JOIN employees approver_emp ON ra_pending.approver_id = approver_emp.emp_id" :
        "LEFT JOIN request_approvers ra_pending ON ra_pending.request_inv_no = s.request_inv_no 
             AND ra_pending.request_type_id = $requestTypeId AND ra_pending.status = 'pending'
        LEFT JOIN employees approver_emp ON ra_pending.approver_id = approver_emp.emp_id";
    
    $sql = "SELECT 
        s.*, 
        e.name as emp_name,
        e.dept,
        ra_pending.approver_id as current_approver_id,
        approver_emp.name as current_approver_name,
        ra_pending.approval_level as current_approval_level
    FROM settlement_records s
    JOIN employees e ON s.emp_id = e.emp_id
    " . $mainJoinClause . "
    $whereSql
    GROUP BY s.id
    ORDER BY s.created_at DESC";

    if (!$showAll) {
        $offset = ($currentPage - 1) * $itemsPerPage;
        $sql .= " LIMIT ?, ?";
        array_push($params, $offset, $itemsPerPage);
        $paramTypes .= "ii";
    }

    $stmt = $conDB->prepare($sql);
    if (!$stmt) {
        die("Main query prepare failed: " . $conDB->error);
    }
    if (!empty($params)) {
        $stmt->bind_param($paramTypes, ...$params);
    }

    if (!$stmt->execute()) {
        die("Main query execute failed: " . $stmt->error);
    }
    
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $settlements[] = $row;
        }
    }
    $stmt->close();
}

// Get unfiltered total
if ($canSeeAllDepts) {
    if (empty($searchTerm)) {
        $unfilteredSql = "SELECT COUNT(id) as total FROM settlement_records";
    } else {
        $unfilteredSql = "SELECT COUNT(id) as total FROM settlement_records";
    }
    $unfilteredResult = mysqli_query($conDB, $unfilteredSql);
    $unfilteredTotalItems = ($unfilteredResult && ($rowUnf = mysqli_fetch_assoc($unfilteredResult))) ? ($rowUnf['total'] ?? 0) : 0;
} else {
    $unfilteredSql = "SELECT COUNT(s.id) as total FROM settlement_records s JOIN employees e ON s.emp_id = e.emp_id WHERE (e.dept = ? OR EXISTS (SELECT 1 FROM request_approvers ra_any WHERE ra_any.request_inv_no = s.request_inv_no AND ra_any.request_type_id = ? AND ra_any.approver_id = ?))";
    $stmtUnf = $conDB->prepare($unfilteredSql);
    if ($stmtUnf) {
        $stmtUnf->bind_param('iii', $user_dept, $requestTypeId, $empid);
        $stmtUnf->execute();
        $resUnf = $stmtUnf->get_result();
        $unfilteredTotalItems = ($resUnf && ($rowUnf = $resUnf->fetch_assoc())) ? ($rowUnf['total'] ?? 0) : 0;
        $stmtUnf->close();
    } else {
        $unfilteredTotalItems = 0;
    }
}
?>
<!doctype html>
<html lang="<?= $current_lang ?? 'en' ?>" <?= ($is_rtl ?? false) ? 'dir="rtl"' : '' ?>>

<head>
    <meta charset="utf-8" />
    <title><?= $site_title ?? 'Settlements' ?> - <?= __('settlements') ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta content="Al-Mutlak" name="author" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <link rel="shortcut icon" href="<?= get_setting($conDB, 'favicon') ?>">
    <link href="./plugins/custombox/css/custombox.min.css" rel="stylesheet">
    <!-- Select2 -->
    <link href="./plugins/select2/css/select2.min.css" rel="stylesheet" type="text/css" />
    <link href="assets/css/bootstrap.min.css" rel="stylesheet" type="text/css" />
    <link href="assets/css/metismenu.min.css" rel="stylesheet" type="text/css" />
    <link href="assets/css/style.css" rel="stylesheet" type="text/css" />
    <link href="assets/css/style_dark.css" rel="stylesheet" type="text/css" />
    <link href="./plugins/bootstrap-datepicker/css/bootstrap-datepicker.min.css" rel="stylesheet">

    <script src="assets/js/modernizr.min.js"></script>
    <style>
        .filter-controls {
            max-width: 800px;
        }

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
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-bottom: none;
            font-weight: 600;
            font-size: 1.1em;
            border-top-left-radius: 15px;
            border-top-right-radius: 15px;
        }

        .request-card .card-header .float-right {
            font-size: 0.85em;
            opacity: 0.9;
        }

        .request-card .card-body {
            padding: 1.5rem;
        }

        .detail-item {
            display: flex;
            align-items: center;
            font-size: 1.09em;
            margin-bottom: 0.8rem;
        }

        .detail-item i.fad {
            color: #4a90e2;
            margin-right: 15px;
            width: 20px;
            text-align: center;
            flex-shrink: 0;
        }

        .detail-item strong {
            color: #8a94a6;
            min-width: 130px;
            display: inline-block;
            flex-shrink: 0;
            margin-right: 10px;
        }

        .request-card .card-footer {
            background-color: #fafbff;
            border-top: 1px solid #eef;
            overflow: visible;
        }

        /* Footer actions: responsive grid to avoid overflow and keep symmetry */
        .vac-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
            gap: .5rem;
        }

        .vac-actions .btn {
            white-space: normal;
            line-height: 1.2;
        }

        .vac-actions .btn i {
            margin-inline-end: .35rem;
        }

        /* Keep block buttons filling their grid cell */
        .vac-actions .btn.btn-block {
            display: inline-flex;
            width: 100%;
            justify-content: center;
            align-items: center;
        }

        .no-requests {
            padding: 3rem;
            background: #fff;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.07);
        }

        .btn-block+.btn-block {
            margin-top: 0rem !important;
        }

        /* --- NEW STYLES FOR APPROVER LIST --- */
        /* Ensure dropdowns are not hidden behind adjacent cards */
        .request-card {
            position: relative;
        }

        .request-card:hover,
        .request-card:focus-within {
            z-index: 50;
        }

        .request-card .dropdown-menu {
            z-index: 2000;
        }

        .detail-item {
            flex-direction: <?= ($is_rtl) ? 'row-reverse !important' : 'row !important' ?>;
        }
        .datepicker table tr td.disabled, .datepicker table tr td.disabled:hover {
            background: 0 0;
            color: var(--danger);
            background-color: #ffe6e9;
            cursor: default;
        }
    </style>
    <?php if ($is_rtl): ?>
        <link href="assets/css/style_rtl.css" rel="stylesheet" type="text/css" />
    <?php endif; ?>
    <script>
        window.lang = <?= json_encode($GLOBALS['translations'] ?? []) ?>;
    </script>
</head>

<body class="enlarged" data-keep-enlarged="true">
    <div id="wrapper">
        <div class="left side-menu">
            <div class="slimscroll-menu" id="remove-scroll">
                <div class="topbar-left">
                    <a href="dashboard.php" class="logo">
                        <span><img src="<?=get_setting($conDB, 'logo')?>" alt="" height="22"></span>
                        <i><img src="<?=get_setting($conDB, 'white_logo')?>" alt="" height="28"></i>
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
                                <h4 class="header-title m-t-0 m-b-30"><?= __('all_settlements') ?></h4>

                                <div class="row filter-controls mx-auto mb-5">
                                    <div class="col-md-6 mb-3 mb-md-0">
                                        <div class="form-group">
                                            <label for="statusFilter" class="font-weight-bold"><?= __('filter_by_status') ?></label>
                                            <select class="form-control" id="statusFilter" onchange="applyFilters()">
                                                <?php foreach ($allStatuses as $status_key => $status_value): ?>
                                                    <option value="<?= $status_key; ?>" <?php if ($currentFilter == $status_key) echo 'selected'; ?>>
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
                                                <input type="search" class="form-control" id="searchFilter" placeholder="<?= __('enter_search_term') ?>" value="<?= htmlspecialchars($searchTerm); ?>">
                                                <div class="input-group-append">
                                                    <button class="btn btn-primary" type="button" onclick="applyFilters()"><i class="fas fa-search"></i></button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="d-flex justify-content-between align-items-center mb-4">
                                    <?php
                                    $showing_text = str_replace('{0}', (string)(int)$totalItems, __('showing_requests'));
                                    ?>
                                    <h4 class="mb-0 text-muted"><?= $showing_text ?></h4>
                                    <span class="badge badge-light p-2"><?= __('total_found') ?>: <?= $totalItems; ?></span>
                                </div>

                                <?php if (!empty($settlements)): ?>
                                    <div class="row">
                                        <?php foreach ($settlements as $settlement): ?>
                                            <?php
                                            // Check if this settlement is pending approval with the current user
                                            // Support both 'pending' and 'pending_approval' status for backward compatibility
                                            $isPendingStatus = in_array($settlement['settlement_status'], ['pending', 'pending_approval']);
                                            $is_pending_with_me = ($isPendingStatus && !empty($settlement['current_approver_id']) && (int)$settlement['current_approver_id'] === (int)$empid);
                                            ?>
                                            <div class="col-lg-4 col-md-6 mb-4">
                                                <div class="card request-card h-100">
                                                    <div class="card-header">
                                                        <?= htmlspecialchars(getDisplayName($settlement['emp_name']), ENT_QUOTES); ?>
                                                        <span class="float-right"><?= __('emp_id') ?>: <?= htmlspecialchars($settlement['emp_id'], ENT_QUOTES); ?></span>
                                                    </div>
                                                    <div class="card-body">
                                                        <div class="detail-item"><i class="fad fa-file-invoice"></i><strong><?= __('settlement_id') ?>:</strong> <?= htmlspecialchars($settlement['request_inv_no'], ENT_QUOTES); ?></div>
                                                        <div class="detail-item"><i class="fad fa-coins"></i><strong><?= __('amount') ?>:</strong> <?= number_format($settlement['settlement_amount'], 2); ?> SAR</div>
                                                        <div class="detail-item"><i class="fad fa-calendar-alt"></i><strong><?= __('created') ?>:</strong> <?= htmlspecialchars(date('d M Y', strtotime($settlement['created_at'])), ENT_QUOTES); ?></div>
                                                        <div class="detail-item">
                                                            <i class="fad fa-tasks"></i>
                                                            <strong><?= __('status') ?>:</strong>
                                                            <div style="display: flex; flex-direction: column; gap: 6px;">
                                                                <?php
                                                                $badge_class = 'secondary';
                                                                $status_text = '';
                                                                $status_icon = '';
                                                                
                                                                switch ($settlement['settlement_status']) {
                                                                    case 'pending':
                                                                    case 'pending_approval':
                                                                        $badge_class = 'warning';
                                                                        $approver = $settlement['current_approver_name'] ? getDisplayName(parseName($settlement['current_approver_name'])) : __('next_approver');
                                                                        $status_text = __('pending_with') . ' ' . htmlspecialchars($approver);
                                                                        $status_icon = "<i class='fa fa-solid fa-hourglass-half text-white'></i>";
                                                                        break;
                                                                    case 'approved':
                                                                        $badge_class = 'success';
                                                                        $status_text = __('approved');
                                                                        $status_icon = "<i class='fa fa-solid fa-check text-white'></i>";
                                                                        break;
                                                                    case 'rejected':
                                                                        $badge_class = 'danger';
                                                                        $status_text = __('rejected');
                                                                        $status_icon = "<i class='fa fa-solid fa-times text-white'></i>";
                                                                        break;
                                                                    case 'completed':
                                                                        $badge_class = 'primary';
                                                                        $status_text = __('completed');
                                                                        $status_icon = "<i class='fa fa-solid fa-badge-check text-white'></i>";
                                                                        break;
                                                                    default:
                                                                        $status_text = __($settlement['settlement_status']);
                                                                        $status_icon = "";
                                                                        break;
                                                                }
                                                                ?>
                                                                <span class="badge badge-<?= $badge_class; ?> p-2" style="display: inline-block; width: fit-content;">
                                                                    <?= $status_icon . " " . htmlspecialchars($status_text); ?>
                                                                </span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="card-footer d-flex justify-content-between align-items-center" style="gap: 0.5rem;">
                                                        <a href="javascript:void(0);" class="btn btn-info btn-block waves-effect" onclick="viewSettlementDetails(<?= $settlement['id'] ?>, '<?= htmlspecialchars($settlement['request_inv_no'], ENT_QUOTES) ?>')">
                                                            <i class="fa fa-eye"></i> <?= __('view') ?>
                                                        </a>
                                                        <div class="btn-group flex-fill">
                                                            <button type="button" class="btn btn-secondary dropdown-toggle btn-block waves-effect" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                                                <?= __('actions') ?> <span class="caret"></span>
                                                            </button>
                                                            <div class="dropdown-menu dropdown-menu-right">
                                                                <a class="dropdown-item" href="settlement_status_history.php?request_inv_no=<?= htmlspecialchars($settlement['request_inv_no'], ENT_QUOTES) ?>">
                                                                    <i class="fa fa-history"></i> <?= __('history') ?>
                                                                </a>
                                                                <?php if ($settlement['settlement_status'] === 'approved'): ?>
                                                                    <div class="dropdown-divider"></div>
                                                                    <a class="dropdown-item" href="javascript:void(0);" onclick="processSettlementPayment(<?= $settlement['id'] ?>, '<?= htmlspecialchars($settlement['request_inv_no'], ENT_QUOTES) ?>')">
                                                                        <i class="fa fa-check-circle text-success"></i> <?= __('clear_settlement') ?>
                                                                    </a>
                                                                <?php endif; ?>
                                                                <?php if ($is_pending_with_me): ?>
                                                                    <div class="dropdown-divider"></div>
                                                                    <a class="dropdown-item" href="javascript:void(0);" onclick="approveSettlement(<?= $settlement['id'] ?>, '<?= htmlspecialchars($settlement['request_inv_no'], ENT_QUOTES) ?>', <?= $settlement['emp_id'] ?>)">
                                                                        <i class="fa fa-check text-success"></i> <?= __('approve') ?>
                                                                    </a>
                                                                    <a class="dropdown-item" href="javascript:void(0);" onclick="rejectSettlement(<?= $settlement['id'] ?>, '<?= htmlspecialchars($settlement['request_inv_no'], ENT_QUOTES) ?>')">
                                                                        <i class="fa fa-times text-danger"></i> <?= __('reject') ?>
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
                                        <p><?= __('no_settlements_to_display') ?></p>
                                    </div>
                                <?php endif; ?>

                                <!-- Pagination -->
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
            <footer class="footer"><?= $site_footer ?? '© 2025 Almutlak' ?></footer>
        </div>
    </div>

    <!-- Scripts -->
    <script src="assets/js/jquery.min.js"></script>
    <script src="assets/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/metisMenu.min.js"></script>
    <script src="assets/js/waves.js"></script>
    <script src="assets/js/jquery.slimscroll.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="./plugins/select2/js/select2.min.js"></script>
    <script src="assets/js/jquery.core.js"></script>
    <script src="assets/js/jquery.app.js"></script>

    <script>
        function applyFilters() {
            const status = document.getElementById('statusFilter').value;
            const search = document.getElementById('searchFilter').value;
            const baseUrl = window.location.href.split('?')[0];
            window.location.href = `${baseUrl}?status=${status}&search=${encodeURIComponent(search)}&page=1`;
        }

        // Settlement functions are now defined globally in assets/js/jquery.app.js:
        // - viewSettlementDetails(settlementId, settlementInvNo)
        // - approveSettlement(settlementId, settlementInvNo, empId)
        // - rejectSettlement(settlementId, settlementInvNo)
        // - processSettlementPayment(settlementId, settlementInvNo)
        // - htmlspecialcharsJs(str)
    </script>
</body>
</html>
    </script>
</body>
</html>
