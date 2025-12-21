<?php
/****************************************************************
 * MODIFICATION SUMMARY (001-all_applied_loan.php):
 * 1. ADDED MANUAL LOAN BUTTON: A new button "Add Manual Loan History" has been added to the top of the page, linking to the new `add_manual_loan.php` page. This provides an entry point for users to add historical loan records from the old system.
 ****************************************************************
 * MODIFICATION SUMMARY (015-all_applied_loan.php):
 * 1.  ROBUST VARIABLE ASSIGNMENT: Moved the explicit fetching of user data (`user_type`, `emp_type`, `user_dept`) to run BEFORE the `avatar_select.php` include. This prevents the include file from overwriting the correct user data and ensures the role detection logic works reliably.
 * 2.  REVISED WORKFLOW: The role definitions and status arrays have been updated to match the new approval chain:
 * Dept Manager -> HR Assistant -> HR Manager -> Finance Manager -> GM -> Finance Assistant
 * 3.  UPDATED STATUSES: The `$all_loan_statuses` array now includes `hr_assistant_pending` and `hr_manager_pending` in the correct order for the filter dropdown.
 * 4.  REVISED BUTTON VISIBILITY: The logic that determines which approval buttons are shown (`$can_take_action`) has been rewritten to accurately reflect each user's role in the new, longer approval process.
 * 5.  ADDED EMPLOYEE ID TO GM FUNCTION: The `emp_id` is now passed to the `modifyAndApproveLoan` function so it can fetch End of Service details for the GM's view.
 * 6.  PAGINATION IMPLEMENTED: Added full pagination controls, including an items-per-page selector, to handle large numbers of loan requests efficiently.
 ****************************************************************/
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/session_check.php';

// Restrict access: Employees cannot view this detailed report page
if (isset($isEmployee) && $isEmployee === true) {
    header("Location: ./profile.php");
    exit();
}

// --- Get Request Type ID for 'loan_request' ---
$type_query = mysqli_query($conDB, "SELECT `id` FROM `approval_request_types` WHERE `type_name` = 'loan_request' LIMIT 1");
if (!$type_query || mysqli_num_rows($type_query) == 0) {
    die("CRITICAL ERROR: 'loan_request' type not found in `approval_request_types` table.");
}
$request_type_id = (int)mysqli_fetch_assoc($type_query)['id'];

// --- Search, Pagination & Filtering Logic ---
$all_statuses = [
    'my_pending' => __('my_pending_queue'),
    'my_dept' => __('my_department_requests'),
    'pending_approval' => __('all_pending'),
    'approved' => __('approved'),
    'rejected' => __('rejected'),
    'all' => __('all_requests')
];

$search_term = $_GET['search'] ?? '';
$limit_options = [12, 16, 20];
$perpage = 12;
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
$statuses_to_query = [];

// 2. Determine the effective filter: either from URL or a default based on role
if ($current_filter === null) {
    if ($is_system_admin) {
        $current_filter = 'all'; 
    } else {
        $current_filter = 'my_pending';
    }
}

$where_clauses = [];
$params = [];
$types = "";
$join_sql = "";
// Track department scoping application
$dept_filter_applied = false;
// Only HR and System Admin can view all departments
$can_see_all_depts = ($is_system_admin ?? false) || ($isHR ?? false);

$page_title = $all_statuses[$current_filter] ?? __('all_requests');

if ($current_filter === 'my_pending') {
    // Backward compatibility: only join request_approvers if inv_no column exists in emp_loan
    $has_inv_no = false;
    $column_check = mysqli_query($conDB, "SHOW COLUMNS FROM emp_loan LIKE 'inv_no'");
    if ($column_check && mysqli_num_rows($column_check) === 1) { $has_inv_no = true; }
    if ($has_inv_no) {
        $join_sql .= " JOIN `request_approvers` ra ON ra.request_inv_no = l.inv_no AND ra.request_type_id = ? ";
        $params[] = $request_type_id;
        $types .= "i";
        $where_clauses[] = "ra.approver_id = ?";
        $params[] = $empid;
        $types .= "i";
        $where_clauses[] = "ra.status = 'pending'";
    }
} elseif ($current_filter === 'my_dept') {
    $where_clauses[] = "e.dept = ?";
    $params[] = $user_dept;
    $types .= "i";
    $dept_filter_applied = true;
} elseif (in_array($current_filter, ['pending_approval', 'approved', 'rejected'])) {
    $where_clauses[] = "l.status = ?";
    $params[] = $current_filter;
    $types .= "s";
    if ($current_filter === 'approved' && empty($search_term)) {
        $where_clauses[] = "l.created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)";
    }
} elseif ($current_filter === 'all' && empty($search_term)) {
    $where_clauses[] = "(l.status != 'approved' OR l.created_at >= DATE_SUB(CURDATE(), INTERVAL 15 DAY))";
}

// Exclude LEGACY records by default, but include them when searching by employee name
if (!empty($search_term)) {
    $where_clauses[] = "(e.name LIKE ? OR l.emp_id LIKE ? OR l.inv_no LIKE ?)";
    $search_param = "%{$search_term}%";
    array_push($params, $search_param, $search_param, $search_param);
    $types .= "sss";
} else {
    // Hide LEGACY records when not searching
    $where_clauses[] = "l.inv_no NOT LIKE 'LEGACY-%'";
}

// Enforce department scoping for non-privileged users
if (!$can_see_all_depts && !$dept_filter_applied && $current_filter !== 'my_pending') {
    // Restrict to user's department OR any request where current user is in approval chain
    // Resolve request type id is already available as $request_type_id
    $where_clauses[] = "(e.dept = ? OR EXISTS (SELECT 1 FROM request_approvers ra_any WHERE ra_any.request_inv_no = l.inv_no AND ra_any.request_type_id = ? AND ra_any.approver_id = ?))";
    array_push($params, $user_dept, $request_type_id, $empid);
    $types .= "iii";
    $dept_filter_applied = true;
}

$where_sql = "";
if (!empty($where_clauses)) {
    $where_sql = " WHERE " . implode(" AND ", $where_clauses);
}

$base_query = "FROM emp_loan l 
               JOIN employees e ON l.emp_id = e.emp_id 
               $join_sql 
               $where_sql";

$count_sql = "SELECT COUNT(DISTINCT l.id) as total " . $base_query;
$total_items = 0;

$stmt_count = $conDB->prepare($count_sql);
if (!$stmt_count) { die("Count query prepare failed: " . $conDB->error); }
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
        l.*, 
        l.inv_no AS request_inv_no,
        e.name as employee_name,
        e.dept,
        ra_pending.approver_id as current_approver_id, 
        ra_pending.approval_level as current_approval_level, 
    COALESCE(approver_emp.name, approver_admin.fullname, approver_admin.username) as current_approver_name,
    approver_admin.user_type as current_approver_user_type,
        l.loan_amount,
        l.monthly_deduction,
        l.start_date,
        l.end_date
    FROM emp_loan l 
    JOIN employees e ON l.emp_id = e.emp_id
    LEFT JOIN request_approvers ra_pending ON ra_pending.request_inv_no = l.inv_no AND ra_pending.request_type_id = ? AND ra_pending.status = 'pending' 
    LEFT JOIN employees approver_emp ON ra_pending.approver_id = approver_emp.emp_id 
    LEFT JOIN admin_login approver_admin ON ra_pending.approver_id = approver_admin.emp_id
    $join_sql
    $where_sql";
    $sql .= " GROUP BY l.id ORDER BY l.created_at DESC";

    $main_params = $params;
    $main_types = $types;
    // Prepend the request_type_id for the LEFT JOIN on ra_pending
    array_unshift($main_params, $request_type_id);
    $main_types = "i" . $main_types;

    if (!$show_all) {
        $offset = ($current_page - 1) * $items_per_page;
        $sql .= " LIMIT ?, ?";
        array_push($main_params, $offset, $items_per_page);
        $main_types .= "ii";
    }

    $stmt = $conDB->prepare($sql);
    if (!$stmt) { die("Main query prepare failed: " . $conDB->error); }
    if (!empty($main_params)) {
        $stmt->bind_param($main_types, ...$main_params);
    }
    if(!$stmt->execute()) { die("Main query execute failed: " . $stmt->error); }
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $requests[] = $row;
        }
    }
    $stmt->close();
}

// Unfiltered total respects department visibility and excludes LEGACY records when not searching
if ($can_see_all_depts) {
    if (empty($search_term)) {
        $unfiltered_sql = "SELECT COUNT(id) as total FROM emp_loan WHERE inv_no NOT LIKE 'LEGACY-%'";
    } else {
        $unfiltered_sql = "SELECT COUNT(id) as total FROM emp_loan";
    }
    $unfiltered_result = mysqli_query($conDB, $unfiltered_sql);
    $unfiltered_total_items = ($unfiltered_result && ($row_unf = mysqli_fetch_assoc($unfiltered_result))) ? ($row_unf['total'] ?? 0) : 0;
} else {
    if (empty($search_term)) {
        $unfiltered_sql = "SELECT COUNT(l.id) as total FROM emp_loan l JOIN employees e ON l.emp_id = e.emp_id WHERE (e.dept = ? OR EXISTS (SELECT 1 FROM request_approvers ra_any WHERE ra_any.request_inv_no = l.inv_no AND ra_any.request_type_id = ? AND ra_any.approver_id = ?)) AND l.inv_no NOT LIKE 'LEGACY-%'";
    } else {
        $unfiltered_sql = "SELECT COUNT(l.id) as total FROM emp_loan l JOIN employees e ON l.emp_id = e.emp_id WHERE (e.dept = ? OR EXISTS (SELECT 1 FROM request_approvers ra_any WHERE ra_any.request_inv_no = l.inv_no AND ra_any.request_type_id = ? AND ra_any.approver_id = ?))";
    }
    if ($stmt_unf = $conDB->prepare($unfiltered_sql)) {
        $stmt_unf->bind_param('iii', $user_dept, $request_type_id, $empid);
        $stmt_unf->execute();
        $res_unf = $stmt_unf->get_result();
        $unfiltered_total_items = ($res_unf && ($row_unf = $res_unf->fetch_assoc())) ? ($row_unf['total'] ?? 0) : 0;
        $stmt_unf->close();
    } else {
        $unfiltered_total_items = 0;
    }
}

// --- Helper: Fallback next-approver name by status level when chain table isn't available ---
function get_next_approver_name_fallback(mysqli $conDB, array $loanRow) {
    $status = $loanRow['status'] ?? '';
    $empDept = (int)($loanRow['dept'] ?? 0);
    $map = [
        'pending_level_1' => ['where' => "a.emp_type='Manager' AND e.dept=?", 'title' => __('pending_department_manager')],
        'pending_level_2' => ['where' => "a.user_type='assistant' AND e.dept=5", 'title' => __('pending_hr_assistant')],
        'pending_level_3' => ['where' => "a.user_type='hr' AND e.dept=5", 'title' => __('pending_hr_manager')],
        'pending_level_4' => ['where' => "a.emp_type='Manager' AND e.dept=2", 'title' => __('pending_finance_manager')],
        'pending_level_5' => ['where' => "a.user_type='gm'", 'title' => __('pending_gm')],
        'pending_level_6' => ['where' => "a.user_type='assistant' AND e.dept=2", 'title' => __('pending_final_processing')],
    ];
    if (!isset($map[$status])) return null;
    $rule = $map[$status];
    $sql = "SELECT e.name FROM admin_login a JOIN employees e ON e.emp_id = a.id_iqama WHERE " . $rule['where'] . " LIMIT 1";
    $name = null;
    if (strpos($rule['where'], '?') !== false) {
        $stmt = $conDB->prepare($sql);
        if ($stmt) {
            $stmt->bind_param('i', $empDept);
            if ($stmt->execute()) {
                $res = $stmt->get_result()->fetch_assoc();
                $name = $res['name'] ?? null;
            }
            $stmt->close();
        }
    } else {
        $res = mysqli_query($conDB, $sql);
        if ($res) {
            $row = mysqli_fetch_assoc($res);
            $name = $row['name'] ?? null;
        }
    }
    return $name ?: null;
}
?>
    <!doctype html>
    <html lang="<?= $current_lang ?? 'en' ?>" <?= ($is_rtl ?? false) ? 'dir="rtl"' : '' ?>>
    <head>
        <meta charset="utf-8" />
        <title><?= $site_title ?> - <?=__('all_loan_requests')?></title>
        <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
        <meta content="Anees Afzal" name="author" />
        <meta http-equiv="X-UA-Compatible" content="IE=edge" />
        <link rel="shortcut icon" href="<?=get_setting($conDB, 'favicon')?>">
        <link href="assets/css/bootstrap.min.css" rel="stylesheet" type="text/css" />
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
            .request-card .card-body { padding: 1.5rem; }
            .detail-item { display: flex; align-items: center; margin-bottom: 1rem; font-size: 1.09em; }
            .detail-item i { color: #4a90e2; margin-right: 15px; width: 20px; text-align: center; }
            .detail-item strong { color: #8a94a6; min-width: 100px; display: inline-block; }
            .request-card .card-footer { background-color: #fafbff; border-top: 1px solid #eef; }
            .no-requests { padding: 3rem; background: #fff; border-radius: 15px; box-shadow: 0 10px 30px rgba(0, 0, 0, 0.07); }
			.btn-block + .btn-block{ margin-top: 0rem !important; }
            .detail-item{
                flex-direction: <?= ($is_rtl) ? 'row-reverse !important' : 'row !important' ?>;
            }
        </style>
        <?php if ($is_rtl): ?>
            <link href="assets/css/style_rtl.css" rel="stylesheet" type="text/css" />
        <?php endif; ?>
		<script> window.lang = <?= json_encode($GLOBALS['translations'] ?? []) ?>;</script>
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
                                    <h4 class="header-title m-t-0 m-b-30"><?=__('loan_approval_center')?></h4>
                                    <div class="row filter-controls mx-auto mb-5">
                                        <div class="col-md-6 mb-3 mb-md-0">
                                            <div class="form-group">
                                                <label for="statusFilter" class="font-weight-bold"><?=__('filter_by_status')?></label>
                                                <select class="form-control" id="statusFilter" onchange="applyFilters()">
                                                    <?php foreach ($all_statuses as $status_key => $status_value): ?>
                                                        <option value="<?=$status_key; ?>" <?php if ($current_filter == $status_key) echo 'selected'; ?>>
                                                            <?=htmlspecialchars($status_value); ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="searchFilter" class="font-weight-bold"><?=__('search_by_name_id')?></label>
                                                <div class="input-group">
                                                    <input type="search" class="form-control" id="searchFilter" placeholder="<?=__('enter_search_term')?>" value="<?=htmlspecialchars($search_term); ?>">
                                                    <div class="input-group-append">
                                                        <button class="btn btn-primary" type="button" onclick="applyFilters()"><i class="fas fa-search"></i></button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="d-flex justify-content-between align-items-center mb-4">
                                        <h4 class="mb-0 text-muted"><?=__('showing')?>: <?=htmlspecialchars($page_title); ?></h4>
                                        <span class="badge badge-light p-2"><?=__('total_found')?>: <?=$total_items; ?></span>
                                    </div>

                                    <?php if (!empty($requests)): ?>
                                        <div class="row">
                                            <?php foreach ($requests as $loan): ?>
                                                <div class="col-lg-4 col-md-6 mb-4">
                                                    <div class="card request-card h-100">
                                                        <div class="card-header">
                                                            <?=parseName($loan['employee_name']); ?>
                                                            <span class="float-right"><?=__('emp_id')?>: <?=htmlspecialchars($loan['emp_id']); ?></span>
                                                        </div>
                                                        <div class="card-body">
                                                            <div class="detail-item"><i class="fad fa-hand-holding-usd duotone-info"></i><strong><?=__('amount')?>:</strong> <?=htmlspecialchars($loan['loan_amount']); ?></div>
                                                            <div class="detail-item"><i class="fad fa-calendar-alt duotone-info"></i><strong><?=__('start_date')?>:</strong> <?=htmlspecialchars(date('d M Y', strtotime($loan['start_date']))); ?></div>
                                                            <div class="detail-item"><i class="fad fa-calendar-check duotone-info"></i><strong><?=__('end_date')?>:</strong> <?=htmlspecialchars(date('d M Y', strtotime($loan['end_date']))); ?></div>
                                                            <div class="detail-item"><i class="fad fa-wallet duotone-info"></i><strong><?=__('monthly')?>:</strong> <?=htmlspecialchars($loan['monthly_deduction']); ?></div>
                                                            <div class="detail-item">
                                                                <?php
                                                                    $loan_badge_class = 'secondary';
                                                                    $loan_status_text = '';
                                                                    $current_level_display = '';
                                                                    
                                                                    // Check if we have current approver from chain (ALWAYS show if available)
                                                                    if (!empty($loan['current_approver_name'])) {
                                                                        $loan_status_text = __('pending_with') . ' ' . htmlspecialchars($loan['current_approver_name']);
                                                                        $loan_badge_class = 'warning';
                                                                        if (!empty($loan['current_approval_level'])) {
                                                                            $current_level_display = ' (Level ' . $loan['current_approval_level'] . ')';
                                                                        }
                                                                    } elseif (in_array($loan['status'], ['pending', 'pending_approval'])) {
                                                                        // Generic pending status when no approver is found
                                                                        $loan_status_text = __('pending_approval');
                                                                        $loan_badge_class = 'warning';
                                                                    } elseif ($loan['status'] === 'approved') {
                                                                        $loan_status_text = __('approved');
                                                                        $loan_badge_class = 'success';
                                                                    } elseif ($loan['status'] === 'rejected') {
                                                                        $loan_status_text = __('rejected');
                                                                        $loan_badge_class = 'danger';
                                                                    } elseif ($loan['status'] === 'paid') {
                                                                        $loan_status_text = __('paid');
                                                                        $loan_badge_class = 'primary';
                                                                    } else {
                                                                        $loan_status_text = __('pending');
                                                                        $loan_badge_class = 'warning';
                                                                    }
                                                                ?>
                                                                <i class="fad fa-info-circle duotone-info"></i>
                                                                <strong><?=__('status')?>:</strong> <span class="badge badge-<?=$loan_badge_class; ?> p-2"><?=htmlspecialchars($loan_status_text . $current_level_display); ?></span>
                                                            </div>
                                                        </div>
                                                        <?php
                                                            // Button visibility: Only show if pending with logged-in user
                                                            $can_take_action = false;
                                                            if ($loan['current_approver_id'] == $empid) {
                                                                $can_take_action = true;
                                                            }
                                                        ?>
                                                        <div class="card-footer d-flex justify-content-between align-items-center" style="gap: 0.5rem;">
                                                            <a href="loan_report_details.php?id=<?=$loan['id']; ?>&emp_id=<?=$loan['emp_id']; ?>" target="_blank" class="btn btn-info btn-block waves-effect">
                                                                <i class="fa fa-eye"></i> <?=__('view')?>
                                                            </a>
                                                            <div class="btn-group flex-fill" style="position: relative; z-index: 1000;">
                                                                <button type="button" class="btn btn-secondary dropdown-toggle btn-block waves-effect" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                                                    <?=__('actions')?> <span class="caret"></span>
                                                                </button>
                                                                <div class="dropdown-menu dropdown-menu-right" style="z-index: 1050; position: absolute;">
                                                                    <a class="dropdown-item" href="loan_status_history.php?inv_no=<?=urlencode($loan['inv_no']); ?>" target="_blank" style="cursor: pointer;">
                                                                        <i class="fa fa-history"></i> <?=__('history')?>
                                                                    </a>
                                                                    <?php if($can_take_action): ?>
                                                                        <div class="dropdown-divider"></div>
                                                                        <button type="button" class="dropdown-item" style="cursor: pointer; background: none; border: none; width: 100%; text-align: left;" onclick="approveLoanRequest(<?=$loan['id']; ?>, '<?=htmlspecialchars($user_type, ENT_QUOTES)?>', <?=$loan['loan_amount']; ?>, '<?=$loan['current_approver_user_type'] ?? ''?>', <?=$loan['current_approval_level'] ?? 0?>)">
                                                                            <i class="fa fa-check text-success"></i> <?=__('approve')?>
                                                                        </button>
                                                                        <button type="button" class="dropdown-item" style="cursor: pointer; background: none; border: none; width: 100%; text-align: left;" onclick="rejectLoanRequest(<?=$loan['id']; ?>, '<?=htmlspecialchars($user_type, ENT_QUOTES)?>')">
                                                                            <i class="fa fa-times text-danger"></i> <?=__('reject')?>
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
                                            echo generate_pagination_controls($current_page,$total_pages,$total_items,$items_per_page,$limit_options,$show_all,$pagination_params,$unfiltered_total_items);
                                        ?>
                                    <?php else: ?>
                                        <div class="row justify-content-center">
                                            <div class="col-md-8">
                                                <div class="text-center no-requests">
                                                    <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                                                    <h2><?=__('no_loan_requests_found')?></h2>
                                                    <p class="text-muted"><?=__('no_requests_matching_filters')?></p>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <footer class="footer"><?= $site_footer ?></footer>
            </div>
        </div>

        <script src="assets/js/jquery.min.js"></script>
        <script src="assets/js/bootstrap.bundle.min.js"></script>
        <script src="assets/js/metisMenu.min.js"></script>
        <script src="assets/js/waves.js"></script>
        <script src="assets/js/jquery.slimscroll.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
        <script src="assets/js/jquery.core.js"></script>
        <script src="assets/js/jquery.app.js"></script>
        <script src="assets/js/loan_approval.js"></script>
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
                if (e.key === 'Enter') { applyFilters(); }
            });
        </script>
    </body>
    </html>
<?php
    $conDB->close();
?>
