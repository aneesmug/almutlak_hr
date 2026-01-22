<?php

require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/session_check.php';
// $user_type, $empid, $user_dept, $is_system_admin, $isHR, $isDeptHr are available from session_check.php
// Restrict access: Employees cannot view this detailed report page
if (isset($isEmployee) && $isEmployee === true) {
    header("Location: ./profile.php");
    exit();
}

// --- Get Request Type IDs for 'vacation_request' AND 'excuse_leave' ---
// Both types use emp_vacation table, so we need to check both in approval queries
$type_query = mysqli_query($conDB, "SELECT `id`, `type_name` FROM `approval_request_types` WHERE `type_name` IN ('vacation_request', 'excuse_leave')");
if (!$type_query || mysqli_num_rows($type_query) == 0) {
    die("CRITICAL ERROR: 'vacation_request' or 'excuse_leave' types not found in `approval_request_types` table.");
}
$request_type_ids = [];
while ($row = mysqli_fetch_assoc($type_query)) {
    $request_type_ids[$row['type_name']] = (int)$row['id'];
}
mysqli_free_result($type_query);

// For backward compatibility, keep single variable pointing to vacation_request
$request_type_id = $request_type_ids['vacation_request'] ?? 3;
$excuse_leave_type_id = $request_type_ids['excuse_leave'] ?? 7;

// Create IN clause for SQL queries: (3, 7)
$request_type_ids_list = implode(',', array_values($request_type_ids));


// --- Search, Pagination & Filtering Logic ---

$all_statuses = [
    'my_pending' => __('my_pending_queue'),
    'my_team' => (function_exists('__') ? __('my_team_requests') : 'My Team'),
    'my_dept' => __('my_department_requests'),
    'pending_approval' => __('all_pending'),
    'pending_payment' => __('approved_pending_payment'),
    'pending_deduction' => __('approved_pending_deduction'),
    'approved' => __('approved'),
    'rejected' => __('rejected'),
    'all' => __('all_requests')
];

// 1. Set up variables
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
$statuses_to_query = [];

// 2. Determine the effective filter: either from URL or a default based on role
if ($current_filter === null) {
    if ($is_system_admin) {
        $current_filter = 'all';
    } else {
        // Default to 'my_pending' for any manager/approver
        $current_filter = 'my_pending';
    }
}

$where_clauses = [];
$params = [];
$types = "";
$join_sql = "";
// Track whether we've already applied a department filter in a specific branch
$dept_filter_applied = false;
// Determine if the user can see records for all departments
// Per requirement: Only HR and System Admin can see all departments
$can_see_all_depts = ($is_system_admin ?? false) || ($isHR ?? false);

// 3. Based on the effective filter, build the query
$page_title = $all_statuses[$current_filter] ?? __('all_requests');

if ($current_filter === 'my_pending') {
    // This is the most important filter. It finds requests *specifically assigned* to the current user.
    $join_sql .= " JOIN `request_approvers` ra ON ra.request_inv_no = v.request_inv_no AND ra.request_type_id IN ($request_type_ids_list) ";
    // No params needed - using IN clause with literal values

    $where_clauses[] = "ra.approver_id = ?";
    $params[] = $empid; // $empid is from session_check.php
    $types .= "i";

    $where_clauses[] = "ra.status = 'pending'";
    $where_clauses[] = "v.current_status != 'rejected'";
    $where_clauses[] = "v.current_status != 'completed'";
} elseif ($current_filter === 'my_dept') {
    // Show all requests from the user's department
    $where_clauses[] = "e.dept = ?";
    $params[] = $user_dept;
    $types .= "i";
    $dept_filter_applied = true;
} elseif ($current_filter === 'my_team') {
    // Show requests for the current user's direct reports (supervisor) or entire department (manager)
    $is_manager_role = (strpos($user_role ?? '', 'Manager') !== false) || ($user_role ?? '') === 'DPT_Manager';
    $is_supervisor_role = (strpos($user_role ?? '', 'Supervisor') !== false) || ($user_role ?? '') === 'HR_Supervisor';

    if ($is_manager_role) {
        // Managers: see all in their department
        $where_clauses[] = "e.dept = ?";
        $params[] = $user_dept;
        $types .= "i";
        $dept_filter_applied = true;
    } else {
        // Supervisors (or default): show only direct reports
        $where_clauses[] = "e.supervisor_id = ?";
        $params[] = $empid;
        $types .= "i";
    }
} elseif ($current_filter === 'pending_payment') {
    // Show approved annual vacation (fly) requests without payment details
    $where_clauses[] = "v.current_status = 'approved'";
    $where_clauses[] = "v.fly_type = 'annual'";
    $where_clauses[] = "(v.departure_date IS NULL OR v.arrival_date IS NULL OR v.ticket_pay IS NULL OR v.ticket_pay = 0 OR v.permit_fee IS NULL OR v.permit_fee = 0)";
} elseif ($current_filter === 'pending_deduction') {
    // Show approved annual vacation (fly) requests without overtime/deduction details
    $where_clauses[] = "v.current_status = 'approved'";
    $where_clauses[] = "v.fly_type = 'annual'";
    $where_clauses[] = "(v.overtime_hours IS NULL OR v.overtime_hours = 0) AND (v.deduction_hours IS NULL OR v.deduction_hours = 0) AND (v.deduction_days IS NULL OR v.deduction_days = 0)";
} elseif (in_array($current_filter, ['pending_approval', 'approved', 'rejected'])) {
    // Filter by the main status on the vacation table
    $where_clauses[] = "v.current_status = ?";
    $params[] = $current_filter;
    $types .= "s";

    // For 'approved' status: show only last 30 days if no search term is provided
    if ($current_filter === 'approved' && empty($search_term)) {
        $where_clauses[] = "v.created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)";
    }
}
// 'all' adds no WHERE clause, but also applies 30-day filter for approved records when no search
elseif ($current_filter === 'all' && empty($search_term)) {
    // When viewing 'all' without search, limit approved records to last 15 days
    $where_clauses[] = "(v.current_status != 'approved' OR v.created_at >= DATE_SUB(CURDATE(), INTERVAL 15 DAY))";
}

// Add search term if provided and exclude LEGACY records appropriately
if (!empty($search_term)) {
    $where_clauses[] = "(e.name LIKE ? OR v.emp_id LIKE ? OR v.request_inv_no LIKE ?)";
    $search_param = "%{$search_term}%";
    array_push($params, $search_param, $search_param, $search_param);
    $types .= "sss";
} else {
    // Exclude legacy-imported requests only when not searching
    $where_clauses[] = "v.request_inv_no NOT LIKE 'LEGACY-%'";
}

// Enforce department scoping: Only HR and System Admin can see all departments.
// Everyone else is restricted to their own department for history views.
if (!$can_see_all_depts && !$dept_filter_applied && $current_filter !== 'my_pending') {
    // Restrict to user's department OR any request where current user is in approval chain
    $where_clauses[] = "(e.dept = ? OR EXISTS (SELECT 1 FROM request_approvers ra_any WHERE ra_any.request_inv_no = v.request_inv_no AND ra_any.request_type_id IN ($request_type_ids_list) AND ra_any.approver_id = ?))";
    array_push($params, $user_dept, $empid);
    $types .= "ii";
    $dept_filter_applied = true;
}


$where_sql = "";
if (!empty($where_clauses)) {
    $where_sql = " WHERE " . implode(" AND ", $where_clauses);
}

// Add company filter to WHERE clause
$company_filter = getCompanyFilterSQL('e.comp_no', true);
if (strpos($where_sql, 'WHERE') === false) {
    $where_sql = " WHERE 1=1" . $company_filter;
} else {
    $where_sql .= $company_filter;
}

// Main query to select *which* vacations to show (for count and main data)
$base_query = "FROM emp_vacation v 
               JOIN employees e ON v.emp_id = e.emp_id 
               $join_sql 
               $where_sql";

$count_sql = "SELECT COUNT(DISTINCT v.id) as total " . $base_query;
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
    // This query fetches the full data *including* the current pending approver's name
    // AND the employee's supervisor assignment for determining approval logic
    $sql = "SELECT 
        v.*, 
        v.attachment_path,
        v.travel_email_sent,
        e.name as employee_name,
        e.dept,
        e.supervisor_id,
        b.remaining_balance,
        b.available_balance,
        CASE 
            WHEN `v`.`fly_type` = 'annual' THEN '" . __('annual_vacation') . "' 
            WHEN `v`.`fly_type` = 'emergency' THEN '" . __('emergency_vacation') . "'
            ELSE ''
        END AS `fly_type_translated`,
        ra_pending.approver_id as current_approver_id,
        approver_emp.name as current_approver_name,
        v.current_approval_level, -- Select the current level
        supervisor_emp.name as supervisor_name,
        supervisor_emp.emptype as supervisor_type,
        ra_payer.approver_id as payer_emp_id,
        ra_rejected.note as rejection_note
    FROM emp_vacation v 
    JOIN employees e ON v.emp_id = e.emp_id
    LEFT JOIN emp_vacation_balance b ON v.id = b.vac_id
    
    -- This JOIN finds the current pending approver (for both vacation and excuse leave)
    LEFT JOIN request_approvers ra_pending ON ra_pending.request_inv_no = v.request_inv_no 
         AND ra_pending.request_type_id IN ($request_type_ids_list) AND ra_pending.status = 'pending'
    LEFT JOIN employees approver_emp ON ra_pending.approver_id = approver_emp.emp_id
    
    -- This JOIN gets the supervisor information
    LEFT JOIN employees supervisor_emp ON e.supervisor_id = supervisor_emp.emp_id
    
    -- This JOIN gets the assigned payer information (approval_level >= 100 in request_approvers)
    LEFT JOIN request_approvers ra_payer ON ra_payer.request_inv_no = v.request_inv_no 
         AND ra_payer.request_type_id IN ($request_type_ids_list) AND ra_payer.approval_level >= 100

        -- This JOIN fetches rejection notes (if any)
        LEFT JOIN request_approvers ra_rejected ON ra_rejected.request_inv_no = v.request_inv_no 
            AND ra_rejected.request_type_id IN ($request_type_ids_list) AND ra_rejected.status = 'rejected'
    
    -- This JOIN is for the 'my_pending' filter
    $join_sql
    
    $where_sql";

    $sql .= " GROUP BY v.id ORDER BY v.created_at DESC"; // Group by v.id to avoid duplicates

    $main_params = $params;
    $main_types = $types;

    // No need to prepend request_type_id - using IN clause with literal values


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

// Get the total unfiltered count (respect department visibility rules and search context)
if ($can_see_all_depts) {
    if (empty($search_term)) {
        $unfiltered_sql = "SELECT COUNT(id) as total FROM emp_vacation WHERE request_inv_no NOT LIKE 'LEGACY-%'";
    } else {
        $unfiltered_sql = "SELECT COUNT(id) as total FROM emp_vacation";
    }
    $unfiltered_result = mysqli_query($conDB, $unfiltered_sql);
    $unfiltered_total_items = ($unfiltered_result && ($row_unf = mysqli_fetch_assoc($unfiltered_result))) ? ($row_unf['total'] ?? 0) : 0;
} else {
    // Respect the same scoping (dept OR in approval chain) and exclude LEGACY invoices when not searching
    if (empty($search_term)) {
        $unfiltered_sql = "SELECT COUNT(v.id) as total FROM emp_vacation v JOIN employees e ON v.emp_id = e.emp_id WHERE (e.dept = ? OR EXISTS (SELECT 1 FROM request_approvers ra_any WHERE ra_any.request_inv_no = v.request_inv_no AND ra_any.request_type_id IN ($request_type_ids_list) AND ra_any.approver_id = ?)) AND v.request_inv_no NOT LIKE 'LEGACY-%'";
    } else {
        $unfiltered_sql = "SELECT COUNT(v.id) as total FROM emp_vacation v JOIN employees e ON v.emp_id = e.emp_id WHERE (e.dept = ? OR EXISTS (SELECT 1 FROM request_approvers ra_any WHERE ra_any.request_inv_no = v.request_inv_no AND ra_any.request_type_id IN ($request_type_ids_list) AND ra_any.approver_id = ?))";
    }
    if ($stmt_unf = $conDB->prepare($unfiltered_sql)) {
        $stmt_unf->bind_param('ii', $user_dept, $empid);
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
    <title><?= $site_title ?? 'Vacation System' ?> - <?= __('all_vacation_requests') ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta content="Anees Afzal" name="author" />
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
            /*margin-bottom: 1rem;*/
            font-size: 1.09em;
        }

        .detail-item i.fad {
            color: #4a90e2;
            margin-right: 15px;
            width: 20px;
            text-align: center;
        }

        .detail-item strong {
            color: #8a94a6;
            min-width: 130px;
            display: inline-block;
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

        .swal-approval-chain .select2-container {
            width: 100% !important;
        }

        .swal-approval-chain label,
        .swal-payment-details label {
            font-weight: 600;
            margin-top: 10px;
        }

        .swal-approval-chain-builder {
            display: flex;
            align-items: center;
            margin-bottom: 10px;
        }

        .swal-approval-chain-builder .select2-container {
            flex-grow: 1;
        }

        .swal-approval-chain-builder #add-approver-btn-new {
            margin-left: 10px;
            flex-shrink: 0;
            height: 38px;
            /* Match Select2 height */
            background-color: #28a745;
            border-color: #28a745;
        }

        #approver-chain-list {
            list-style-type: none;
            padding-left: 0;
            margin-top: 15px;
            max-height: 150px;
            overflow-y: auto;
            background: #f9f9f9;
            border-radius: 4px;
            border: 1px solid #ddd;
        }

        #approver-chain-list li {
            padding: 8px 12px;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 0.95em;
        }

        #approver-chain-list li:last-child {
            border-bottom: none;
        }

        #approver-chain-list .remove-approver-btn {
            background: none;
            border: none;
            color: #dc3545;
            font-weight: bold;
            font-size: 1.2em;
            cursor: pointer;
            padding: 0 5px;
        }

        #approver-chain-list .remove-approver-btn:hover {
            color: #a71d2a;
        }

        #approver-chain-list-empty {
            padding: 15px;
            text-align: center;
            color: #777;
        }

        .swal-payment-details input {
            width: 100%;
            padding: .375rem .75rem;
            font-size: 1rem;
            line-height: 1.5;
            color: #495057;
            background-color: #fff;
            background-clip: padding-box;
            border: 1px solid #ced4da;
            border-radius: .25rem;
            margin-top: 5px;
        }

        /* --- FIX SELECT2 DROPDOWN HEIGHT IN SWAL --- */
        .swal2-container .select2-container {
            z-index: 1060 !important;
        }

        .swal2-container .select2-dropdown {
            z-index: 1061 !important;
            max-height: 200px !important;
        }

        .swal2-container .select2-results {
            max-height: 150px !important;
            overflow-y: auto;
        }

        /* Keep SweetAlert2 buttons always visible and on top */
        .swal2-actions {
            z-index: 9999 !important;
            position: relative;
            margin-top: 1.5rem !important;
            padding-top: 1rem !important;
            background: white;
        }

        /* --- END NEW STYLES --- */
        .detail-item {
            flex-direction: <?= ($is_rtl) ? 'row-reverse !important' : 'row !important' ?>;
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
                                <h4 class="header-title m-t-0 m-b-30"><?= __('vacation_approval_center') ?></h4>

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
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="d-flex justify-content-between align-items-center mb-4">
                                    <?php
                                    // Replace placeholder {0} in translation with the total count
                                    $showing_text = str_replace('{0}', (string)(int)$total_items, __('showing_requests'));
                                    ?>
                                    <h4 class="mb-0 text-muted"><?= $showing_text ?></h4>
                                    <span class="badge badge-light p-2"><?= __('total_found') ?>: <?= $total_items; ?></span>
                                </div>

                                <?php if (!empty($requests)): ?>
                                    <div class="row">
                                        <?php foreach ($requests as $req): ?>
                                            <div class="col-lg-4 col-md-6 mb-4">
                                                <div class="card request-card h-100">
                                                    <div class="card-header">
                                                        <?= getDisplayName($req['employee_name']); ?>
                                                        <span class="float-right"><?= __('emp_id') ?>: <?= htmlspecialchars($req['emp_id']); ?></span>
                                                    </div>
                                                    <div class="card-body">
                                                        <div class="detail-item"><i class="fad fa-paper-plane duotone-info"></i><strong><?= __('applied') ?>:</strong> <?= htmlspecialchars(date('d M Y', strtotime($req['created_at']))); ?></div>
                                                        <div class="detail-item"><i class="fad fa-suitcase-rolling duotone-info"></i><strong><?= __('type') ?>:</strong> <?= getDisplayName($req['vac_type']) . " | " . $req['fly_type_translated']; ?></div>
                                                        <div class="detail-item"><i class="fad fa-calendar-alt duotone-info"></i><strong><?= __('start') ?>:</strong> <?= htmlspecialchars($req['start_date'] ?? 'N/A'); ?></div>
                                                        <div class="detail-item"><i class="fad fa-calendar-check duotone-info"></i><strong><?= __('return') ?>:</strong> <?= htmlspecialchars($req['return_date'] ?? 'N/A'); ?></div>
                                                        <?php if (!empty($req['departure_date']) && $req['vac_type'] === 'Fly' && $req['fly_type'] === 'annual'): ?>
                                                            <div class="detail-item"><i class="fad fa-plane-departure duotone-info"></i><strong><?= __('departure_date') ?>:</strong> <?= htmlspecialchars($req['departure_date']); ?></div>
                                                        <?php endif; ?>
                                                        <?php if (!empty($req['arrival_date']) && $req['vac_type'] === 'Fly' && $req['fly_type'] === 'annual'): ?>
                                                            <div class="detail-item"><i class="fad fa-plane-arrival duotone-info"></i><strong><?= __('arrival_date') ?>:</strong> <?= htmlspecialchars($req['arrival_date']); ?></div>
                                                        <?php endif; ?>
                                                        <div class="detail-item"><i class="fad fa-sun duotone-info"></i><strong><?= __('days') ?>:</strong> <?= htmlspecialchars($req['vacdays']); ?></div>

                                                        <?php if (!empty($req['attachment_path'])):
                                                            // Decode JSON array of attachments
                                                            $attachments = json_decode($req['attachment_path'], true);
                                                            if (!is_array($attachments)) {
                                                                // Fallback for old single file format
                                                                $attachments = [$req['attachment_path']];
                                                            }
                                                        ?>
                                                            <div class="detail-item">
                                                                <i class="fad fa-paperclip duotone-info"></i>
                                                                <strong><?= __('attachments') ?> (<?= count($attachments) ?>):</strong>
                                                                <div style="margin-inline-start: 20px; margin-top: 5px; direction: inherit;">
                                                                    <?php foreach ($attachments as $index => $attachment): ?>
                                                                        <a href="<?= htmlspecialchars($attachment); ?>" target="_blank" class="font-weight-bold text-info" style="display: block; margin-bottom: 5px; direction: ltr; text-align: start;">
                                                                            <i class="fa fa-file-<?= pathinfo($attachment, PATHINFO_EXTENSION) === 'pdf' ? 'pdf' : 'image' ?>"></i>
                                                                            <?= __('document') ?> <?= $index + 1 ?>
                                                                        </a>
                                                                    <?php endforeach; ?>
                                                                </div>
                                                            </div>
                                                        <?php endif; ?>

                                                        <div class="detail-item">
                                                            <?php
                                                            // --- NEW DYNAMIC STATUS LOGIC ---
                                                            $badge_class = 'secondary';
                                                            $status_text = '';
                                                            $status_icon = '';

                                                            switch ($req['current_status']) {
                                                                case 'pending_approval':
                                                                    $badge_class = 'warning';
                                                                    $approver_name = $req['current_approver_name'] ? parseName($req['current_approver_name']) : 'next approver';
                                                                    $status_text = __('pending_with') . ' ' . htmlspecialchars($approver_name);
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
                                                                    $status_text = __($req['current_status']);
                                                                    $status_icon = "";
                                                                    break;
                                                            }

                                                            // Check if payment is pending for annual fly vacation
                                                            // Payment is pending if ANY of these is missing/zero: departure_date, arrival_date, ticket_pay, permit_fee
                                                            $is_payment_pending = false;
                                                            $is_deduction_pending = false;
                                                            $is_travel_email_pending = false;
                                                            if (
                                                                $req['current_status'] == 'approved' &&
                                                                $req['vac_type'] == 'Fly' &&
                                                                $req['fly_type'] == 'annual'
                                                            ) {
                                                                // Check if travel email is pending (not sent yet)
                                                                if (empty($req['departure_date']) || empty($req['arrival_date']) || $req['travel_email_sent'] == 0 || empty($req['travel_email_sent'])) {
                                                                    $is_travel_email_pending = true;
                                                                }

                                                                // Check all payment fields are properly filled
                                                                $has_departure = !empty($req['departure_date']);
                                                                $has_arrival = !empty($req['arrival_date']);
                                                                $has_ticket_pay = !empty($req['ticket_pay']) && (float)$req['ticket_pay'] > 0;
                                                                $has_permit_fee = !empty($req['permit_fee']) && (float)$req['permit_fee'] > 0;

                                                                // Payment is pending if ANY field is missing or zero
                                                                if (!$has_departure || !$has_arrival || !$has_ticket_pay || !$has_permit_fee) {
                                                                    $is_payment_pending = true;
                                                                }

                                                                // Check if deduction/overtime is pending
                                                                // All three fields are NULL or 0 means no adjustments have been entered
                                                                $has_overtime = !empty($req['overtime_hours']) && (float)$req['overtime_hours'] > 0;
                                                                $has_deduction_hours = !empty($req['deduction_hours']) && (float)$req['deduction_hours'] > 0;
                                                                $has_deduction_days = !empty($req['deduction_days']) && (float)$req['deduction_days'] > 0;

                                                                // Deduction is pending if ALL adjustment fields are missing or zero
                                                                if (!$has_overtime && !$has_deduction_hours && !$has_deduction_days) {
                                                                    $is_deduction_pending = true;
                                                                }
                                                            }
                                                            ?>
                                                            <i class="fad fa-info-circle duotone-info"></i>
                                                            <strong><?= __('status') ?>:</strong>
                                                            <div style="display: flex; flex-direction: column; gap: 6px;">
                                                                <span class="badge badge-<?= $badge_class; ?> p-2" style="display: inline-block; width: fit-content;">
                                                                    <?= $status_icon . " " . htmlspecialchars($status_text); ?>
                                                                </span>
                                                                <?php if ($is_travel_email_pending): ?>
                                                                    <span class="badge badge-danger p-2" style="display: inline-block; width: fit-content;">
                                                                        <i class="fa fa-paper-plane"></i> <?= __('pending_travel_email') ?: 'Pending Travel Email' ?>
                                                                    </span>
                                                                <?php endif; ?>
                                                                <?php if ($is_payment_pending): ?>
                                                                    <span class="badge badge-warning p-2" style="display: inline-block; width: fit-content;">
                                                                        <i class="fa fa-credit-card"></i> <?= __('pending_booking_payment') ?>
                                                                    </span>
                                                                <?php endif; ?>
                                                                <?php if ($is_deduction_pending): ?>
                                                                    <span class="badge badge-info p-2" style="display: inline-block; width: fit-content;">
                                                                        <i class="fa fa-calculator"></i> <?= __('pending_deduction_overtime') ?: 'Pending Deduction/Overtime' ?>
                                                                    </span>
                                                                <?php endif; ?>
                                                            </div>

                                                        </div>

                                                        <?php if ($req['current_status'] === 'rejected' && !empty($req['rejection_note'])): 
                                                            $rejection_note_clean = stripslashes($req['rejection_note']);
                                                        ?>
                                                            <div class="detail-item" style="margin-top: 12px; padding: 10px; background-color: #f8d7da; border-left: 3px solid #dc3545; border-radius: 4px;">
                                                                <i class="fas fa-ban" style="color:#dc3545; margin-right:8px;"></i>
                                                                <strong><?= __('rejection_reason') ?>:</strong>&nbsp;<?= nl2br(htmlspecialchars(getDisplayName($rejection_note_clean))); ?>
                                                            </div>
                                                        <?php endif; ?>

                                                        <?php if ($req['current_status'] == 'approved' && isset($req['remaining_balance'])): ?>
                                                            <hr>
                                                            <div class="detail-item"><i class="fad fa-wallet duotone-success"></i><strong><?= __('remaining') ?>:</strong> <?= htmlspecialchars(number_format($req['remaining_balance'], 2)); ?> <?= __('days') ?></div>
                                                        <?php endif; ?>
                                                    </div>
                                                    <?php
                                                    // Pre-compute action parameters
                                                    $employee_name_js = htmlspecialchars(addslashes(parseName($req['employee_name'])), ENT_QUOTES);
                                                    $employee_id_js = htmlspecialchars($req['emp_id'], ENT_QUOTES);
                                                    $vac_type_js = htmlspecialchars($req['vac_type']);
                                                    $start_date_js = htmlspecialchars($req['start_date'] ?? 'N/A');
                                                    $end_date_js = htmlspecialchars($req['return_date'] ?? 'N/A');
                                                    $days_js = htmlspecialchars($req['vacdays']);
                                                    $current_level_js = (int)$req['current_approval_level'];
                                                    $user_role_js = htmlspecialchars($user_type, ENT_QUOTES);
                                                    $has_supervisor_js = !empty($req['supervisor_id']) ? 'true' : 'false';
                                                    $is_simple_leave_js = ($req['vac_type'] != 'Fly') ? 'true' : 'false';
                                                    $is_pending_with_me = ($req['current_status'] == 'pending_approval' && $req['current_approver_id'] == $empid);

                                                    // Determine other conditional actions
                                                    $show_payment_button = false;
                                                    $show_adjustments_button = false;
                                                    $show_travel_email_button = false;

                                                    // === WORKFLOW FOR FLY + ANNUAL VACATION ===
                                                    // 1. STEP 1: Show Travel Email button FIRST (when departure/arrival dates are set and email NOT sent)
                                                    if (
                                                        isset($req['vac_type']) && $req['vac_type'] === 'Fly' &&
                                                        isset($req['fly_type']) && $req['fly_type'] === 'annual' &&
                                                        $req['current_status'] == 'approved' &&
                                                        !empty($req['departure_date']) &&
                                                        !empty($req['arrival_date']) &&
                                                        ($req['travel_email_sent'] == 0 || empty($req['travel_email_sent'])) &&
                                                        ($isHR || $is_system_admin)
                                                    ) {
                                                        $show_travel_email_button = true;
                                                    }

                                                    // 2. STEP 2: Show Payment button AFTER travel email is sent (and payments missing)
                                                    if (
                                                        isset($req['vac_type']) && $req['vac_type'] === 'Fly' &&
                                                        isset($req['fly_type']) && $req['fly_type'] === 'annual' &&
                                                        $req['current_status'] == 'approved' &&
                                                        !empty($req['departure_date']) &&
                                                        !empty($req['arrival_date']) &&
                                                        ($req['travel_email_sent'] == 1) &&
                                                        (empty($req['ticket_pay']) || (float)$req['ticket_pay'] <= 0 || empty($req['permit_fee']) || (float)$req['permit_fee'] <= 0) &&
                                                        ($is_system_admin || $isHR_Payroll) &&
                                                        $user_type !== 'gr_officer'
                                                    ) {
                                                        $show_payment_button = true;
                                                    }

                                                    // 3. STEP 3: Show Adjustments button when adjustments are missing/pending
                                                    // Adjustments are missing if ALL three fields are empty/zero
                                                    $adjustments_missing = (empty($req['overtime_hours']) || (float)$req['overtime_hours'] <= 0) &&
                                                        (empty($req['deduction_hours']) || (float)$req['deduction_hours'] <= 0) &&
                                                        (empty($req['deduction_days']) || (float)$req['deduction_days'] <= 0);

                                                    // Fly | Annual: show adjustments button
                                                    if (
                                                        isset($req['vac_type']) && $req['vac_type'] === 'Fly' &&
                                                        isset($req['fly_type']) && $req['fly_type'] === 'annual' &&
                                                        $req['current_status'] == 'approved' &&
                                                        $adjustments_missing &&
                                                        ($is_system_admin || $isHR_Payroll)
                                                    ) {
                                                        $show_adjustments_button = true;
                                                    }

                                                    // Local | Annual: show ONLY adjustments button (no booking)
                                                    if (
                                                        isset($req['vac_type']) && $req['vac_type'] !== 'Fly' &&
                                                        isset($req['fly_type']) && $req['fly_type'] === 'annual' &&
                                                        $req['current_status'] == 'approved' &&
                                                        $adjustments_missing &&
                                                        ($is_system_admin || $isHR_Payroll)
                                                    ) {
                                                        $show_adjustments_button = true;
                                                        // Explicitly ensure booking buttons are hidden
                                                        $show_travel_email_button = false;
                                                        $show_payment_button = false;
                                                    }

                                                    // Fly | Emergency: show ONLY adjustments button (no booking)
                                                    if (
                                                        isset($req['vac_type']) && $req['vac_type'] === 'Fly' &&
                                                        isset($req['fly_type']) && strtolower($req['fly_type']) === 'emergency' &&
                                                        $req['current_status'] == 'approved' &&
                                                        $adjustments_missing &&
                                                        ($is_system_admin || $isHR_Payroll)
                                                    ) {
                                                        $show_adjustments_button = true;
                                                        $show_travel_email_button = false;
                                                        $show_payment_button = false;
                                                    }
                                                    ?>
                                                    <div class="card-footer d-flex justify-content-between align-items-center" style="gap: 0.5rem;">
                                                        <a href="vacation_report_details.php?id=<?= $req['id']; ?>&emp_id=<?= $req['emp_id']; ?>" target="_blank" class="btn btn-info btn-block waves-effect">
                                                            <i class="fa fa-eye"></i> <?= __('view') ?>
                                                        </a>
                                                        <div class="btn-group flex-fill">
                                                            <button type="button" class="btn btn-secondary dropdown-toggle btn-block waves-effect" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                                                <?= __('actions') ?> <span class="caret"></span>
                                                            </button>
                                                            <div class="dropdown-menu dropdown-menu-right">
                                                                <a class="dropdown-item" href="vacation_status_history.php?request_inv_no=<?= urlencode($req['request_inv_no']); ?>" target="_blank">
                                                                    <i class="fa fa-history"></i> <?= __('history') ?>
                                                                </a>
                                                                <?php if ($is_pending_with_me): ?>
                                                                    <div class="dropdown-divider"></div>
                                                                    <a class="dropdown-item" href="javascript:void(0);" onclick="approveRequest(<?= $req['id']; ?>, '<?= $employee_id_js; ?>', '<?= $employee_name_js; ?>', '<?= $vac_type_js; ?>', '<?= $start_date_js; ?>', '<?= $end_date_js; ?>', '<?= $days_js; ?>', <?= $current_level_js; ?>, '<?= $user_role_js; ?>', <?= $has_supervisor_js; ?>, <?= $is_simple_leave_js; ?>, <?= (int)($req['payer_emp_id'] ?? 0); ?>, <?= (int)$empid; ?>)">
                                                                        <i class="fa fa-check text-success"></i> <?= __('approve') ?>
                                                                    </a>
                                                                    <a class="dropdown-item" href="javascript:void(0);" onclick="rejectVacationRequest(<?= $req['id']; ?>, '<?= $employee_name_js; ?>', '<?= $vac_type_js; ?>', '<?= $start_date_js; ?>', '<?= $end_date_js; ?>', '<?= $days_js; ?>')">
                                                                        <i class="fa fa-times text-danger"></i> <?= __('reject') ?>
                                                                    </a>
                                                                <?php endif; ?>

                                                                <?php if ($show_travel_email_button || $show_payment_button || $show_adjustments_button): ?>
                                                                    <div class="dropdown-divider"></div>
                                                                    <!-- STEP 1: Show Travel Email Button First -->
                                                                    <?php if ($show_travel_email_button): ?>
                                                                        <a class="dropdown-item" id="travel-email-btn-<?= $req['id']; ?>" href="javascript:void(0);" onclick="sendTravelEmail(<?= $req['id']; ?>, '<?= htmlspecialchars(addslashes(parseName($req['employee_name'])), ENT_QUOTES); ?>')">
                                                                            <i class="fa fa-paper-plane text-primary"></i> <?= __('send_travel_email') ?>
                                                                        </a>
                                                                    <?php endif; ?>

                                                                    <!-- STEP 2: Show Payment Button After Travel Email is Sent -->
                                                                    <?php if ($show_payment_button): ?>
                                                                        <a class="dropdown-item" href="javascript:void(0);" onclick="addVacationPayments(<?= $req['id']; ?>, '<?= htmlspecialchars(addslashes(parseName($req['employee_name'])), ENT_QUOTES); ?>','<?= $req['ticket_pay'] ?? '0.00'; ?>','<?= $req['permit_fee'] ?? '0.00'; ?>')">
                                                                            <i class="fa fa-credit-card text-warning"></i> <?= __('booking_exit_reentry') ?>
                                                                        </a>
                                                                    <?php endif; ?>

                                                                    <!-- STEP 3: Show Adjustments Button After Payments are Complete -->
                                                                    <?php if ($show_adjustments_button): ?>
                                                                        <a class="dropdown-item" href="javascript:void(0);" onclick="addVacationAdjustments(<?= $req['id']; ?>, '<?= htmlspecialchars(addslashes(parseName($req['employee_name'])), ENT_QUOTES); ?>', '<?= $req['overtime_hours'] ?? '0'; ?>', '<?= $req['deduction_hours'] ?? '0'; ?>', '<?= $req['deduction_days'] ?? '0'; ?>', '<?= $req['other_earnings'] ?? '0'; ?>', `<?= htmlspecialchars($req['payroll_note'] ?? '', ENT_QUOTES); ?>`)">
                                                                            <i class="fa fa-calculator text-info"></i> <?= __('add_deduction_overtime') ?: 'Add deduction/overtime' ?>
                                                                        </a>
                                                                    <?php endif; ?>
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
                                                <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                                                <h2><?= __('no_requests_found') ?></h2>
                                                <?php
                                                if (($current_filter && $current_filter !== 'all' && $current_filter !== 'none') || !empty($search_term)): ?>
                                                    <p class="text-muted"><?= __('no_requests_matching_filters_vac') ?></p>
                                                <?php else: ?>
                                                    <p class="text-muted"><?= __('no_requests_to_display') ?></p>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <footer class="footer"><?= $site_footer ?? '© 2025 Almutlak' ?></footer>
        </div>
    </div>

    <!-- ***** FILE PATHS FIXED ***** -->
    <script src="assets/js/jquery.min.js"></script>
    <script src="assets/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/metisMenu.min.js"></script>
    <script src="assets/js/waves.js"></script>
    <script src="assets/js/jquery.slimscroll.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <!-- Select2 -->
    <script src="./plugins/select2/js/select2.min.js"></script>
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
        document.getElementById('searchFilter').addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                applyFilters();
            }
        });

        
        /**
         * =================================================================
         * == ASSET CLEARANCE MODAL FUNCTIONS
         * =================================================================
         */
        function showAssetClearanceModal(vacationId, employeeId, employeeName) {
            // First fetch vacation details and employee assets
            $.ajax({
                url: './includes/ajaxFile/ajaxVacation.php',
                type: 'POST',
                dataType: 'json',
                data: {
                    ajaxType: 'getVacationDetails',
                    vacation_id: vacationId
                },
                success: function(vacationDetails) {
                    // Fetch employee assets
                    $.ajax({
                        url: './includes/ajaxFile/ajaxVacation.php',
                        type: 'POST',
                        dataType: 'json',
                        data: {
                            ajaxType: 'getEmployeeAssignedAssets',
                            emp_id: employeeId
                        },
                        success: function(assetsResponse) {
                            // CHECK IF EMPLOYEE HAS ANY ASSIGNED ASSETS
                            if (!assetsResponse.assets || assetsResponse.assets.length === 0) {
                                // No assets - skip clearance and proceed directly with approval
                                processAssetClearance(vacationId, 'no_assets_required', 'Employee has no assigned assets - automatic clearance');
                                return;
                            }

                            // Build assigned assets section - only if there ARE assets
                            let assignedAssetsHtml = `
                                <div class="alert alert-warning mb-3" style="padding: 15px; border-radius: 8px; background: #fff3cd; border: 1px solid #ffc107;">
                                    <h6 class="mb-2"><i class="fa fa-laptop"></i> <strong>${__('assigned_assets_to_verify') || 'Assigned Assets to Verify'}</strong></h6>
                                    <div style="padding-left: 25px; max-height: 250px; overflow-y: auto;">
                                        <ul style="list-style-type: none; padding: 0;">
                            `;
                            assetsResponse.assets.forEach(function(asset, index) {
                                assignedAssetsHtml += `
                                    <li style="padding: 10px; margin-bottom: 8px; background: white; border-left: 4px solid #ffc107; border-radius: 4px; font-size: 13px;">
                                        <strong>#${index + 1}. ${asset.description || asset.asset_id || __("asset")}</strong>
                                        ${asset.serial_number ? `<br><i class="fa fa-barcode"></i> ${__("serial_header")}: <code>${asset.serial_number}</code>` : ''}
                                        <br><i class="fa fa-calendar"></i> ${__("assigned")}: ${asset.assigned_date}
                                        <br><i class="fa fa-check-circle"></i> ${__("status")}: <span class="badge badge-sm badge-info">${asset.status}</span>
                                    </li>
                                `;
                            });
                            assignedAssetsHtml += `
                                        </ul>
                                    </div>
                                </div>
                            `;

                            Swal.fire({
                                title: __('asset_clearance') || 'Asset Clearance',
                                html: `
                                    <form class="text-left">
                                        ${assignedAssetsHtml}
                                        <p class="alert alert-info mb-3">
                                            <i class="fa fa-info-circle"></i> 
                                            ${__('asset_clearance_required') || 'Please confirm the status of company assets for this employee\'s vacation.'}
                                        </p>
                                        <div class="form-group">
                                            <label style="display: block; margin-bottom: 10px;"><strong>${__('asset_status') || 'Asset Status'}</strong></label>
                                            <div class="custom-control custom-radio mb-2">
                                                <input type="radio" id="assets_received" name="asset_decision" class="custom-control-input" value="assets_received" required>
                                                <label class="custom-control-label" for="assets_received" style="cursor: pointer;">
                                                    <i class="fa fa-check-circle text-success"></i> ${__('assets_received_employee_returned_all_company_assets')} 
                                                </label>
                                            </div>
                                            <div class="custom-control custom-radio mb-3">
                                                <input type="radio" id="employee_keeps_assets" name="asset_decision" class="custom-control-input" value="employee_keeps_assets" required>
                                                <label class="custom-control-label" for="employee_keeps_assets" style="cursor: pointer;">
                                                    <i class="fa fa-exclamation-circle text-warning"></i> ${__('employee_keeps_assets_employee_will_keep_assets_during_vacation')}
                                                </label>
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <label style="text-align: left; display: block;">${__('clearance_notes') || 'Notes'} <span class="text-muted">(${__('optional')})</span></label>
                                            <textarea id="clearance_comment" class="form-control" rows="3" placeholder="${__('add_notes_about_asset_decision') || ''}" maxlength="500"></textarea>
                                            <small class="text-muted"><span id="clearance-char-count">0</span>/500 ${__('characters') || 'characters'}</small>
                                        </div>
                                    </form>
                                `,
                                width: '55%',
                                showCancelButton: false,
                                confirmButtonText: __('confirm_clearance') || 'Confirm Clearance',
                                confirmButtonColor: '#28a745',
                                allowOutsideClick: false,
                                allowEscapeKey: false,
                                showLoaderOnConfirm: true,
                                preConfirm: () => {
                                    const decision = document.querySelector('input[name="asset_decision"]:checked')?.value;
                                    const comment = document.getElementById('clearance_comment').value;

                                    if (!decision) {
                                        Swal.showValidationMessage(__('please_select_an_asset_status') || 'Please select an asset status');
                                        return false;
                                    }

                                    return { decision, comment };
                                },
                                didOpen: () => {
                                    const commentEl = document.getElementById('clearance_comment');
                                    commentEl.addEventListener('input', function() {
                                        document.getElementById('clearance-char-count').textContent = this.value.length;
                                    });
                                }
                            }).then((result) => {
                                if (result.isConfirmed) {
                                    processAssetClearance(vacationId, result.value.decision, result.value.comment);
                                }
                            });
                        },
                        error: function() {
                            // Continue without assets if fetch fails
                            showAssetClearanceModalSimple(vacationId, employeeName, employeeId);
                        }
                    });
                },
                error: function() {
                    // Fallback if we can't fetch vacation details
                    showAssetClearanceModalSimple(vacationId, employeeName, employeeId);
                }
            });
        }

        function showAssetClearanceModalSimple(vacationId, employeeName, employeeId) {
            Swal.fire({
                title: __('asset_clearance') || 'Asset Clearance',
                html: `
                    <form class="text-left">
                        <p class="alert alert-info mb-3">
                            <i class="fa fa-info-circle"></i> 
                            ${__('asset_clearance_required') || 'Please confirm the status of company assets for this employee\'s vacation.'}
                        </p>
                        <div class="form-group">
                            <label style="display: block; margin-bottom: 10px;"><strong>${__('asset_status') || 'Asset Status'}</strong></label>
                            <div class="custom-control custom-radio mb-2">
                                <input type="radio" id="assets_received" name="asset_decision" class="custom-control-input" value="assets_received" required>
                                <label class="custom-control-label" for="assets_received" style="cursor: pointer;">
                                    <i class="fa fa-check-circle text-success"></i> ${__('assets_received_employee_returned_all_company_assets')} 
                                </label>
                            </div>
                            <div class="custom-control custom-radio mb-3">
                                <input type="radio" id="employee_keeps_assets" name="asset_decision" class="custom-control-input" value="employee_keeps_assets" required>
                                <label class="custom-control-label" for="employee_keeps_assets" style="cursor: pointer;">
                                    <i class="fa fa-exclamation-circle text-warning"></i> ${__('employee_keeps_assets_employee_will_keep_assets_during_vacation')}
                                </label>
                            </div>
                        </div>
                        <div class="form-group">
                            <label style="text-align: left; display: block;">${__('clearance_notes') || 'Notes'} <span class="text-muted">(${__('optional')})</span></label>
                            <textarea id="clearance_comment" class="form-control" rows="3" placeholder="${__('add_notes_about_asset_decision') || ''}" maxlength="500"></textarea>
                            <small class="text-muted"><span id="clearance-char-count">0</span>/500 ${__('characters') || 'characters'}</small>
                        </div>
                    </form>
                `,
                width: '45%',
                showCancelButton: false,
                confirmButtonText: __('confirm_clearance') || 'Confirm Clearance',
                confirmButtonColor: '#28a745',
                allowOutsideClick: false,
                allowEscapeKey: false,
                showLoaderOnConfirm: true,
                preConfirm: () => {
                    const decision = document.querySelector('input[name="asset_decision"]:checked')?.value;
                    const comment = document.getElementById('clearance_comment').value;

                    if (!decision) {
                        Swal.showValidationMessage(__('please_select_an_asset_status') || 'Please select an asset status');
                        return false;
                    }

                    return { decision, comment };
                },
                didOpen: () => {
                    const commentEl = document.getElementById('clearance_comment');
                    commentEl.addEventListener('input', function() {
                        document.getElementById('clearance-char-count').textContent = this.value.length;
                    });
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    processAssetClearance(vacationId, result.value.decision, result.value.comment);
                }
            });
        }

        function processAssetClearance(vacationId, assetDecision, clearanceComment) {
            // Show loading state
            Swal.fire({
                title: __('processing') || 'Processing',
                html: __('please_wait_processing_asset_clearance') || 'Processing asset clearance...',
                allowOutsideClick: false,
                allowEscapeKey: false,
                showConfirmButton: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            $.ajax({
                url: './includes/ajaxFile/ajaxVacation.php',
                type: 'POST',
                dataType: 'json',
                data: {
                    ajaxType: 'processAssetClearance',
                    vacation_id: vacationId,
                    asset_decision: assetDecision,
                    clearance_comment: clearanceComment
                },
                success: function(response) {
                    Swal.fire({
                        title: response.title,
                        text: response.message,
                        icon: response.type,
                        allowOutsideClick: false,
                        confirmButtonColor: '#28a745',
                        confirmButtonText: __('ok') || 'OK'
                    }).then(() => {
                        location.reload();
                    });
                },
                error: function(xhr) {
                    const response = xhr.responseJSON || {};
                    Swal.fire({
                        title: response.title || __('error') || 'Error',
                        text: response.message || __('error_processing_request') || 'An error occurred',
                        icon: 'error',
                        confirmButtonColor: '#dc3545'
                    });
                }
            });
        }

        /**
         * =================================================================
         * == APPROVE REQUEST FUNCTION (Updated for Supervisor Chain)
         * =================================================================
         * This function now handles BOTH:
         * 1. Simple Leave Requests (with supervisor → HR BP chain)
         * 2. Annual Vacation (with HR Assistant chain)
         * 3. Finance Manager payer selection for final approvals
         * 
         * @param {boolean} hasSupervisor - Does the employee have a supervisor assigned?
         * @param {boolean} isSimpleLeave - Is this a simple leave (not annual vacation)?
         */
        function approveRequest(vacationId, employeeId, employeeName, vacType, startDate, endDate, totalDays, currentLevel, userRole, hasSupervisor, isSimpleLeave, payerEmpId, currentUserId) {
            // Debug logging
            console.log('approveRequest called with:', {
                vacationId, employeeId, employeeName, vacType, startDate, endDate, totalDays, 
                currentLevel, userRole, hasSupervisor, isSimpleLeave, payerEmpId, currentUserId
            });

            // Check if current user is Finance Manager
            const currentUserType = document.body.getAttribute('data-user-type') || '<?php echo $_SESSION['user_type'] ?? ""; ?>';
            const isFinanceManager = (currentUserType === 'finance');

            // Check if current user is the assigned payer (finance_officer)
            // Either payerEmpId matches currentUserId OR user has finance_officer role (which indicates they're a payer)
            const isPayer = ((payerEmpId > 0 && payerEmpId === currentUserId) || userRole === 'finance_officer');

            // If user is assigned payer, show payment modal
            if (isPayer) {
                // First, fetch vacation details to get the approved payment amount
                $.ajax({
                    url: './includes/ajaxFile/ajaxVacation.php',
                    type: 'POST',
                    dataType: 'json',
                    data: {
                        ajaxType: 'getVacationDetails',
                        vacation_id: vacationId
                    },
                    success: function(vacationDetails) {
                        // For encashed, use encashment_amount only
                        let approvedTotalAmount = 0;
                        let approvedTicketPay = 0;
                        let approvedPermitFee = 0;
                        let infoHtml = '';
                        if (vacType === 'Encashed') {
                            approvedTotalAmount = parseFloat(vacationDetails.encashment_amount || 0);
                            infoHtml = approvedTotalAmount > 0 ? `<div class="alert alert-info text-center">
                                <strong><i class="fa fa-info-circle"></i> ${__('approved_amount') || 'Approved Amount'}:</strong> ${approvedTotalAmount.toFixed(2)} SAR
                                <br><small>${__('encashment_amount') || 'Encashment Amount'}: ${approvedTotalAmount.toFixed(2)} SAR</small>
                            </div>` : '';
                        } else {
                            approvedTicketPay = parseFloat(vacationDetails.ticket_pay || 0);
                            approvedPermitFee = parseFloat(vacationDetails.permit_fee || 0);
                            approvedTotalAmount = approvedTicketPay + approvedPermitFee;
                            infoHtml = approvedTotalAmount > 0 ? `<div class="alert alert-info text-center">
                                <strong><i class="fa fa-info-circle"></i> ${__('approved_amount') || 'Approved Amount'}:</strong> ${approvedTotalAmount.toFixed(2)} SAR
                                <br><small>${__('ticket_fare') || 'Ticket'}: ${approvedTicketPay.toFixed(2)} SAR + ${__('permit_fee') || 'Permit'}: ${approvedPermitFee.toFixed(2)} SAR</small>
                            </div>` : '';
                        }

                        Swal.fire({
                            title: __('process_payment_upload_proof') || 'Process Payment & Upload Proof',
                            html: `
                                <form id="payerApprovalForm" class="text-left" enctype="multipart/form-data">
                                    <p class="alert alert-warning text-center"><i class="fa fa-exclamation-triangle"></i> ${ __('payer_notice') || 'You have been assigned to process this payment. Please enter the final amount and upload payment proof.'}</p>
                                    ${infoHtml}
                                    <div class="form-group">
                                        <label for="payment_amount">${__('final_approved_amount_sar') || 'Final Approved Amount (SAR)'} <span class="text-danger">*</span></label>
                                        <input type="number" step="0.01" id="payment_amount" name="payment_amount" class="form-control" placeholder="${__('enter_amount_actually_paid') || 'Enter amount actually paid'}" value="${approvedTotalAmount > 0 ? approvedTotalAmount.toFixed(2) : ''}" required>
                                        ${approvedTotalAmount > 0 ? `<small class="form-text text-muted">${__('expected_amount') || 'Expected amount'}: ${approvedTotalAmount.toFixed(2)} SAR</small>` : ''}
                                    </div>
                                    <div class="form-group">
                                        <label for="payment_proof">${__('payment_proof_document') || 'Payment Proof Document'} <span class="text-danger">*</span></label>
                                        <input type="file" id="payment_proof" name="payment_proof" class="form-control-file" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx" required>
                                        <small class="form-text text-muted">${__('accepted_formats') || 'Accepted: PDF, JPG, PNG, DOC, DOCX'}</small>
                                    </div>
                                    <div class="form-group">
                                        <label for="approval_comment">${__('payment_notes') || 'Payment Notes'} <span class="text-muted">(${__('optional')})</span></label>
                                        <textarea id="approval_comment" name="approval_comment" class="form-control" rows="3" placeholder="${__('write_payment_notes') || 'Write any notes about the payment...'}" maxlength="5000"></textarea>
                                        <small class="form-text text-muted"><span id="char-count">0</span>/5000 ${__('characters')}</small>
                                    </div>
                                </form>
                    `,
                            width: '40%',
                            showCancelButton: true,
                            confirmButtonText: __('confirm_payment_upload_proof') || 'Confirm Payment & Upload Proof',
                            confirmButtonColor: '#28a745',
                            cancelButtonColor: '#dc3535',
                            cancelButtonText: __('cancel'),
                            showLoaderOnConfirm: true,
                            allowOutsideClick: false,
                            didOpen: () => {
                                // Character counter
                                $('#approval_comment').on('input', function() {
                                    $('#char-count').text($(this).val().length);
                                });

                                // Function to validate amount and enable/disable confirm button
                                const validateAmountAndToggleButton = function() {
                                    const $input = $('#payment_amount');
                                    const $group = $input.closest('.form-group');
                                    const rawVal = ($input.val() || '').toString().trim();
                                    const $confirmBtn = $('.swal2-confirm');

                                    // Remove any previous warning
                                    $input.removeClass('is-invalid is-valid');
                                    $group.find('.invalid-feedback').remove();

                                    // Non-numeric validation
                                    if (rawVal === '' || isNaN(rawVal)) {
                                        $input.addClass('is-invalid');
                                        const nonNumericMsg = `<div class="invalid-feedback d-block"><i class="fa fa-exclamation-triangle"></i> ${__('invalid_amount_not_numeric') || 'Please enter a numeric amount'}.</div>`;
                                        $input.after(nonNumericMsg);
                                        $confirmBtn.prop('disabled', true);
                                        return;
                                    }

                                    const enteredAmount = parseFloat(rawVal);
                                    if (approvedTotalAmount > 0 && Math.abs(enteredAmount - approvedTotalAmount) > 0.0001) {
                                        $input.addClass('is-invalid');
                                        const warningMsg = enteredAmount < approvedTotalAmount ?
                                            `<div class="invalid-feedback d-block"><i class="fa fa-exclamation-triangle"></i> ${__('amount_less_than_approved') || 'Amount is less than approved amount'} (${approvedTotalAmount.toFixed(2)} SAR). ${__('please_verify') || 'Please verify.'}</div>` :
                                            `<div class="invalid-feedback d-block"><i class="fa fa-exclamation-triangle"></i> ${__('amount_greater_than_approved') || 'Amount is greater than approved amount'} (${approvedTotalAmount.toFixed(2)} SAR). ${__('please_verify') || 'Please verify.'}</div>`;
                                        $input.after(warningMsg);
                                        $confirmBtn.prop('disabled', true);
                                    } else {
                                        $input.addClass('is-valid');
                                        $confirmBtn.prop('disabled', false);
                                    }
                                };

                                // Add blur and input validation for payment amount
                                if (approvedTotalAmount > 0) {
                                    $('#payment_amount').on('blur input', validateAmountAndToggleButton);
                                }
                            },
                            preConfirm: () => {
                                const form = document.getElementById('payerApprovalForm');
                                const formData = new FormData(form);
                                formData.append('ajaxType', 'approveVacation');
                                formData.append('vacation_id', vacationId);
                                formData.append('user_role', userRole);

                                const paymentAmount = parseFloat(formData.get('payment_amount'));
                                const paymentProof = document.getElementById('payment_proof').files[0];
                                const $paymentInput = $('#payment_amount');

                                if (!paymentAmount || paymentAmount <= 0) {
                                    Swal.showValidationMessage(__('payment_amount_required') || 'Payment amount is required and must be greater than zero');
                                    return false;
                                }

                                // Validate against approved amount if it exists - MUST BE EXACT MATCH
                                if (approvedTotalAmount > 0 && Math.abs(paymentAmount - approvedTotalAmount) > 0.0001) {
                                    const message = paymentAmount < approvedTotalAmount ?
                                        (__('error_amount_less_than_approved') || 'You entered {entered} SAR which is less than the approved amount of {approved} SAR. Please enter the correct approved amount.')
                                        .replace('{entered}', paymentAmount.toFixed(2))
                                        .replace('{approved}', approvedTotalAmount.toFixed(2)) :
                                        (__('error_amount_greater_than_approved') || 'You entered {entered} SAR which is greater than the approved amount of {approved} SAR. Please enter the correct approved amount.')
                                        .replace('{entered}', paymentAmount.toFixed(2))
                                        .replace('{approved}', approvedTotalAmount.toFixed(2));

                                    Swal.showValidationMessage(message);
                                    // Mark input as invalid visually
                                    $paymentInput.addClass('is-invalid').removeClass('is-valid');
                                    return false;
                                }

                                if (!paymentProof) {
                                    Swal.showValidationMessage(__('payment_proof_required') || 'Payment proof document is required');
                                    return false;
                                }

                                return $.ajax({
                                        url: './includes/ajaxFile/ajaxVacation.php',
                                        type: 'POST',
                                        data: formData,
                                        processData: false,
                                        contentType: false,
                                        dataType: 'JSON',
                                    })
                                    .fail(function(jqXHR, textStatus) {
                                        Swal.showValidationMessage(`${__('request_failed')} ${textStatus}`);
                                    });
                            }
                        }).then((result) => {
                            if (result.isConfirmed) {
                                const response = result.value;
                                Swal.fire({
                                    title: response.title,
                                    text: response.message,
                                    icon: response.type,
                                    allowOutsideClick: false
                                }).then(() => {
                                    location.reload();
                                });
                            }
                        });
                    },
                    error: function() {
                        // If we can't fetch vacation details, show an error instead of a fallback modal
                        Swal.fire({
                            title: __('error') || 'Error',
                            text: __('error_loading_vacation_details') || 'Could not load vacation details. Please try again.',
                            icon: 'error',
                            confirmButtonColor: '#dc3535'
                        });
                    }
                });
                return;
            }

            // If Finance Manager, show payer selection modal instead
            if (isFinanceManager) {
                $.ajax({
                    url: './includes/ajaxFile/ajaxLoan.php',
                    type: 'POST',
                    data: {
                        ajaxType: 'get_finance_staff'
                    },
                    dataType: 'JSON',
                }).done(function(staffResponse) {
                    let payerOptions = '<option value="">-- Select Finance Payer --</option>';
                    if (staffResponse.status === 'success' && staffResponse.staff) {
                        staffResponse.staff.forEach(function(staff) {
                            payerOptions += `<option value="${staff.emp_id}">${staff.name} (${staff.emp_id})</option>`;
                        });
                    }

                    Swal.fire({
                        title: 'Finance Manager - Vacation Approval',
                        html: `
                            <form class="text-left">
                                <p class="alert alert-info" style="margin-bottom: 15px;">
                                    <i class="fa fa-info-circle"></i> Select the finance staff member who will process the payment for this vacation.
                                </p>
                                <div class="form-group">
                                    <label style="text-align: left; display: block;">Finance Payer <span class="text-danger">*</span></label>
                                    <select id="payer_emp_id" class="form-control" required>
                                        ${payerOptions}
                                    </select>
                                    <small class="text-muted">The selected person will handle payment processing</small>
                                </div>
                                <div class="form-group">
                                    <label style="text-align: left; display: block;">Approval Note <span class="text-muted">(Optional)</span></label>
                                    <textarea id="approval_comment" class="form-control" rows="3" placeholder="Write your comment..." maxlength="5000" style="height: 80px;"></textarea>
                                    <small class="text-muted"><span id="char-count">0</span>/5000 characters</span>
                                </div>
                            </form>
                        `,
                        width: '40%',
                        showCancelButton: true,
                        confirmButtonText: 'Approve & Assign Payer',
                        confirmButtonColor: '#28a745',
                        cancelButtonColor: '#dc3545',
                        showLoaderOnConfirm: true,
                        preConfirm: () => {
                            const payerId = document.getElementById('payer_emp_id').value;
                            const comment = document.getElementById('approval_comment').value;

                            if (!payerId) {
                                Swal.showValidationMessage('Please select a finance payer');
                                return false;
                            }

                            return {
                                payer_emp_id: payerId,
                                approval_comment: comment
                            }
                        },
                        didOpen: () => {
                            // Character counter
                            document.getElementById('approval_comment').addEventListener('input', function() {
                                document.getElementById('char-count').textContent = this.value.length;
                            });
                        }
                    }).then((result) => {
                        if (result.isConfirmed) {
                            sendApprovalWithPayer(vacationId, userRole, result.value.payer_emp_id, result.value.approval_comment);
                        }
                    });
                }).fail(function() {
                    // Fallback to simple approval if staff fetch fails
                    Swal.fire({
                        title: 'Are you sure?',
                        text: "Do you want to approve this vacation request?",
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#50e3c2',
                        cancelButtonColor: '#e35050',
                        confirmButtonText: 'Yes, approve it!'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            sendApproval(vacationId, userRole);
                        }
                    });
                });
                return;
            }

            const assetDeptMap = { it: 6, admin: 1, transport: 17 };
            const getAssetDeptId = (role) => {
                if (!role) return null;
                const lower = role.toLowerCase();
                if (lower.includes('it')) return assetDeptMap.it;
                if (lower.includes('admin')) return assetDeptMap.admin;
                if (lower.includes('transport')) return assetDeptMap.transport;
                return null;
            };

            const assetDeptId = getAssetDeptId(userRole);

            console.log('Approval Check - User Role:', userRole, 'Asset Dept ID:', assetDeptId);

            // For GR Officer, ALWAYS show the approval modal with comment and permit fee
            if (userRole === 'gr_officer') {
                console.log('✓ GR Officer detected - showing approval modal with comment and permit fee');
                proceedWithApproval(vacationId, employeeId, employeeName, vacType, startDate, endDate, totalDays, currentLevel, userRole, hasSupervisor, isSimpleLeave);
                return;
            }

            // Check if current user is the assigned asset checker for this vacation
            // If yes, show clearance modal; if no but they're an asset manager, show assignment modal
            if (vacType !== 'Encashed') {
                $.ajax({
                    url: './includes/ajaxFile/ajaxVacation.php',
                    type: 'POST',
                    dataType: 'json',
                    data: {
                        ajaxType: 'getVacationDetails',
                        vacation_id: vacationId
                    },
                    success: function(vacationDetails) {
                        const assignedAssetChecker = vacationDetails.asset_checker_emp_id ? parseInt(vacationDetails.asset_checker_emp_id) : null;
                        const currentUserIdInt = parseInt(currentUserId);
                        const currentUserEmpType = vacationDetails.current_user_emp_type || null; // Get emp_type from backend

                        // Check if current user is a manager by emp_type
                        const isManagerRole = (currentUserEmpType === 'Manager');

                        console.log('Asset Clearance Check:', {
                            vacationDetails,
                            assignedAssetChecker,
                            currentUserId,
                            currentUserIdInt,
                            isMatch: assignedAssetChecker === currentUserIdInt,
                            isAssetDeptUser: !!assetDeptId,
                            isManagerRole: isManagerRole,
                            currentUserEmpType: currentUserEmpType,
                            userRole: userRole
                        });

                        // If current user IS the assigned asset checker AND they're NOT a manager, check if employee has assets
                        if (assignedAssetChecker && assignedAssetChecker === currentUserIdInt && !isManagerRole) {
                            // FIRST CHECK: Does the employee have any assigned assets?
                            $.ajax({
                                url: './includes/ajaxFile/ajaxVacation.php',
                                type: 'POST',
                                dataType: 'json',
                                data: {
                                    ajaxType: 'getEmployeeAssignedAssets',
                                    emp_id: employeeId
                                },
                                success: function(assetsResponse) {
                                    // If employee has NO assets, skip the clearance modal
                                    if (!assetsResponse.assets || assetsResponse.assets.length === 0) {
                                        console.log('✓ Employee has no assigned assets - skipping clearance modal');
                                        processAssetClearance(vacationId, 'no_assets_required', 'Employee has no assigned assets - automatic clearance');
                                        return;
                                    }
                                    
                                    // Employee HAS assets, show the clearance modal
                                    console.log('✓ Showing asset clearance modal for assigned checker (non-manager):', assignedAssetChecker);
                                    showAssetClearanceModal(vacationId, employeeId, employeeName);
                                },
                                error: function() {
                                    // On error, show the modal anyway as a fallback
                                    console.log('✓ Showing asset clearance modal for assigned checker (non-manager):', assignedAssetChecker);
                                    showAssetClearanceModal(vacationId, employeeId, employeeName);
                                }
                            });
                            return;
                        }

                        // Otherwise, if they're an asset manager (from asset dept) AND NOT a Manager by emp_type, show assignment modal
                        // Managers from asset departments should go through normal approval flow
                        if (assetDeptId && !isManagerRole) {
                            console.log('✓ Showing asset checker assignment modal for asset department staff (non-manager)');

                            $.ajax({
                                url: './includes/ajaxFile/ajaxVacation.php',
                                type: 'POST',
                                dataType: 'json',
                                data: {
                                    ajaxType: 'get_asset_department_employees',
                                    dept_id: assetDeptId
                                }
                            }).done(function(res) {
                                const selectAssetCheckerLabel = __('select_asset_checker') || 'Select Asset Checker';
                                let optionsHtml = '<option value="">' + selectAssetCheckerLabel + '</option>';
                                const employees = Array.isArray(res.employees) ? res.employees : (Array.isArray(res.data) ? res.data : []);

                                employees.forEach(function(emp) {
                                    optionsHtml += `<option value="${emp.emp_id}">${emp.name} (${emp.emp_id})</option>`;
                                });

                                Swal.fire({
                                    title: __('assign_asset_checker') || 'Assign Asset Checker',
                                    html: `
                                        <form class="text-left">
                                            <p class="alert alert-info" style="margin-bottom: 15px;">
                                                <i class="fa fa-info-circle"></i> ${__('asset_checker_select_note') || 'Select a colleague from IT, Administration, or Transportation to perform the asset check.'}
                                            </p>
                                            <div class="form-group">
                                                <label style="text-align: left; display: block;">${__('asset_checker') || 'Asset Checker'} <span class="text-danger">*</span></label>
                                                <select id="asset_checker_emp_id" class="form-control" required>
                                                    ${optionsHtml}
                                                </select>
                                                <small class="text-muted">${__('asset_checker_same_dept') || 'Only active staff from IT, Administration, and Transportation are listed.'}</small>
                                            </div>
                                            <div class="form-group">
                                                <label style="text-align: left; display: block;">${__('approval_comment') || 'Approval Comment'} <span class="text-muted">(${__('optional')})</span></label>
                                                <textarea id="asset_checker_comment" class="form-control" rows="3" placeholder="${__('write_comment') || 'Write your comment...'}" maxlength="5000" style="height: 80px;"></textarea>
                                                <small class="text-muted"><span id="asset-checker-char-count">0</span>/5000 ${__('characters')}</small>
                                            </div>
                                        </form>
                                    `,
                                    width: '40%',
                                    showCancelButton: true,
                                    confirmButtonText: __('approve_and_assign_checker') || 'Approve & Assign Checker',
                                    confirmButtonColor: '#28a745',
                                    cancelButtonColor: '#dc3545',
                                    showLoaderOnConfirm: true,
                                    preConfirm: () => {
                                        const checkerId = document.getElementById('asset_checker_emp_id').value;
                                        const comment = document.getElementById('asset_checker_comment').value;

                                        if (!checkerId) {
                                            Swal.showValidationMessage(__('please_select_an_asset_checker') || 'Please select an asset checker');
                                            return false;
                                        }

                                        return {
                                            asset_checker_emp_id: checkerId,
                                            approval_comment: comment
                                        };
                                    },
                                    didOpen: () => {
                                        const commentEl = document.getElementById('asset_checker_comment');
                                        commentEl.addEventListener('input', function() {
                                            document.getElementById('asset-checker-char-count').textContent = this.value.length;
                                        });
                                    }
                                }).then((result) => {
                                    if (result.isConfirmed) {
                                        const payload = result.value || {};
                                        const approvalData = {
                                            asset_checker_emp_id: parseInt(payload.asset_checker_emp_id, 10),
                                            approval_comment: payload.approval_comment || ''
                                        };
                                        sendApproval(vacationId, approvalData);
                                    }
                                });
                            }).fail(function() {
                                Swal.fire({
                                    title: __('error') || 'Error',
                                    text: __('error_loading_department_staff') || 'Could not load department staff. Please try again.',
                                    icon: 'error',
                                    confirmButtonColor: '#dc3535'
                                });
                            });
                            return;
                        }

                        // Not an asset manager and not assigned checker - but first check if employee has assets
                        console.log('✓ Department manager - checking if employee has any assigned assets before approval');
                        
                        // Check if employee has any assigned assets
                        $.ajax({
                            url: './includes/ajaxFile/ajaxVacation.php',
                            type: 'POST',
                            dataType: 'json',
                            data: {
                                ajaxType: 'getEmployeeAssignedAssets',
                                emp_id: employeeId
                            },
                            success: function(assetsResponse) {
                                // If employee has assets, show asset clearance modal
                                if (assetsResponse.assets && assetsResponse.assets.length > 0) {
                                    console.log('✓ Employee has assigned assets - showing asset clearance modal');
                                    showAssetClearanceModal(vacationId, employeeId, employeeName);
                                } else {
                                    // No assets - proceed with normal approval
                                    console.log('✓ No assets assigned - proceeding with normal approval');
                                    proceedWithApproval(vacationId, employeeId, employeeName, vacType, startDate, endDate, totalDays, currentLevel, userRole, hasSupervisor, isSimpleLeave);
                                }
                            },
                            error: function() {
                                // On error, proceed with normal approval as fallback
                                console.log('✓ Error checking assets - proceeding with normal approval as fallback');
                                proceedWithApproval(vacationId, employeeId, employeeName, vacType, startDate, endDate, totalDays, currentLevel, userRole, hasSupervisor, isSimpleLeave);
                            }
                        });
                    },
                    error: function() {
                        // If we can't fetch vacation details, check if they're an asset manager
                        if (assetDeptId) {
                            // Asset manager but can't verify if they're assigned checker - show assignment modal
                            $.ajax({
                                url: './includes/ajaxFile/ajaxVacation.php',
                                type: 'POST',
                                dataType: 'json',
                                data: {
                                    ajaxType: 'get_asset_department_employees',
                                    dept_id: assetDeptId
                                }
                            }).done(function(res) {
                                const selectAssetCheckerLabel = __('select_asset_checker') || 'Select Asset Checker';
                                let optionsHtml = '<option value="">' + selectAssetCheckerLabel + '</option>';
                                const employees = Array.isArray(res.employees) ? res.employees : (Array.isArray(res.data) ? res.data : []);

                                employees.forEach(function(emp) {
                                    optionsHtml += `<option value="${emp.emp_id}">${emp.name} (${emp.emp_id})</option>`;
                                });

                                Swal.fire({
                                    title: __('assign_asset_checker') || 'Assign Asset Checker',
                                    html: `
                                        <form class="text-left">
                                            <p class="alert alert-info" style="margin-bottom: 15px;">
                                                <i class="fa fa-info-circle"></i> ${__('asset_checker_select_note') || 'Select a colleague from IT, Administration, or Transportation to perform the asset check.'}
                                            </p>
                                            <div class="form-group">
                                                <label style="text-align: left; display: block;">${__('asset_checker') || 'Asset Checker'} <span class="text-danger">*</span></label>
                                                <select id="asset_checker_emp_id" class="form-control" required>
                                                    ${optionsHtml}
                                                </select>
                                                <small class="text-muted">${__('asset_checker_same_dept') || 'Only active staff from IT, Administration, and Transportation are listed.'}</small>
                                            </div>
                                            <div class="form-group">
                                                <label style="text-align: left; display: block;">${__('approval_comment') || 'Approval Comment'} <span class="text-muted">(${__('optional')})</span></label>
                                                <textarea id="asset_checker_comment" class="form-control" rows="3" placeholder="${__('write_comment') || 'Write your comment...'}" maxlength="5000" style="height: 80px;"></textarea>
                                                <small class="text-muted"><span id="asset-checker-char-count">0</span>/5000 ${__('characters')}</small>
                                            </div>
                                        </form>
                                    `,
                                    width: '40%',
                                    showCancelButton: true,
                                    confirmButtonText: __('approve_and_assign_checker') || 'Approve & Assign Checker',
                                    confirmButtonColor: '#28a745',
                                    cancelButtonColor: '#dc3545',
                                    showLoaderOnConfirm: true,
                                    allowOutsideClick: false,
                                    preConfirm: () => {
                                        const checkerId = document.getElementById('asset_checker_emp_id').value;
                                        const comment = document.getElementById('asset_checker_comment').value;

                                        if (!checkerId) {
                                            Swal.showValidationMessage(__('please_select_an_asset_checker') || 'Please select an asset checker');
                                            return false;
                                        }

                                        return {
                                            asset_checker_emp_id: checkerId,
                                            approval_comment: comment
                                        };
                                    },
                                    didOpen: () => {
                                        const commentEl = document.getElementById('asset_checker_comment');
                                        commentEl.addEventListener('input', function() {
                                            document.getElementById('asset-checker-char-count').textContent = this.value.length;
                                        });
                                    }
                                }).then((result) => {
                                    if (result.isConfirmed) {
                                        const payload = result.value || {};
                                        const approvalData = {
                                            asset_checker_emp_id: parseInt(payload.asset_checker_emp_id, 10),
                                            approval_comment: payload.approval_comment || ''
                                        };
                                        sendApproval(vacationId, approvalData);
                                    }
                                });
                            }).fail(function() {
                                Swal.fire({
                                    title: __('error') || 'Error',
                                    text: __('error_loading_department_staff') || 'Could not load department staff. Please try again.',
                                    icon: 'error',
                                    confirmButtonColor: '#dc3535'
                                });
                            });
                            return;
                        }

                        // If we can't fetch vacation details and not an asset manager, proceed with normal approval
                        proceedWithApproval(vacationId, employeeId, employeeName, vacType, startDate, endDate, totalDays, currentLevel, userRole, hasSupervisor, isSimpleLeave);
                    }
                });
                return;
            }


        }

        /**
         * Internal function to show the approval modal
         * Separated from approveRequest to allow asset check first
         */
        function proceedWithApproval(vacationId, employeeId, employeeName, vacType, startDate, endDate, totalDays, currentLevel, userRole, hasSupervisor, isSimpleLeave) {
            // Remove request details panel from the approval modal per requirement
            let infoHtml = '';

            // Parse vacation date range for date picker validation
            const vacStartDate = startDate; // Format: YYYY-MM-DD
            const vacEndDate = endDate; // Format: YYYY-MM-DD

            // --- Define approval flow conditions ---
            const isLevel1 = (currentLevel == 1);
            const isHR_Assistant = (userRole === 'assistant');
            const isHR_SeniorBP = (userRole === 'hr_senior_bp');
            const isHR_Payroll = (userRole === 'hr_payroll');
            const isGR_Officer = (userRole === 'gr_officer'); // [NEW] Enable GR Officer role
            const isAnnualFly = (vacType === 'Fly');

            // Determine if current user is from asset clearance roles (IT, Admin, Transportation)
            const assetDeptMap = { it: 6, admin: 1, transport: 17 };
            const getAssetDeptId = (role) => {
                if (!role) return null;
                const lower = role.toLowerCase();
                if (lower.includes('it')) return assetDeptMap.it;
                if (lower.includes('admin')) return assetDeptMap.admin;
                if (lower.includes('transport')) return assetDeptMap.transport;
                return null;
            };
            const isAssetClearanceRole = !!getAssetDeptId(userRole);

            // Determine approval flow:
            // 1. Simple Leave with Supervisor: Level 1 = Supervisor → Level 2 = HR Senior BP
            // 2. Simple Leave without Supervisor: Level 1 = Dept Manager → Level 2 = HR Senior BP
            // 3. Annual Vacation: Level 1 = Manager → Level 2 = HR Senior BP → Level 3+ = Chain

            let paymentHtml = '';
            let chainHtml = '';
            let hrTeamCCHtml = '';
            let hrPayrollHtml = '';
            let commentHtml = ''; // Comment textarea
            let confirmButtonText = __('yes_approve_it');

            // --- Condition 1: Show Payment Fields? ---
            // Payment fields should NOT be shown during approval modal
            // Payment entry happens AFTER approval through separate action button
            if (false) {  // Disabled - payment entry happens after approval, not during
                paymentHtml = `
                    <div class="swal-payment-details text-left mt-3">
                        <hr>
                        <h6 class="text-primary mb-3"><i class="fa fa-money-bill-wave"></i> ${__('payment_information')}</h6>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="swal_departure_date" class="font-weight-bold">
                                        <i class="fa fa-plane-departure"></i> ${__('departure_date')}
                                        <span class="text-danger">*</span>
                                    </label>
                                    <input type="text" id="swal_departure_date" class="form-control" placeholder="Select departure date" readonly required style="width: 100%; padding: .375rem .75rem; border: 1px solid #ced4da; border-radius: .25rem; background-color: white; cursor: pointer;">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="swal_arrival_date" class="font-weight-bold">
                                        <i class="fa fa-plane-arrival"></i> ${__('arrival_date')}
                                        <span class="text-danger">*</span>
                                    </label>
                                    <input type="text" id="swal_arrival_date" class="form-control" placeholder="Select arrival date" readonly required style="width: 100%; padding: .375rem .75rem; border: 1px solid #ced4da; border-radius: .25rem; background-color: white; cursor: pointer;">
                                </div>
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="swal_ticket_fares" class="font-weight-bold">
                                <i class="fa fa-plane"></i> ${__('ticket_fares')} 
                                <span class="text-danger">*</span>
                            </label>
                            <input type="number" id="swal_ticket_fares" class="form-control" placeholder="0.00" step="0.01" required min="0" style="width: 100%; padding: .375rem .75rem; border: 1px solid #ced4da; border-radius: .25rem;">
                        </div>
                        <div class="form-group mt-2">
                            <label for="swal_permit_fee" class="font-weight-bold">
                                <i class="fa fa-passport"></i> ${__('exit_re-entry_fee')} 
                                <span class="text-danger">*</span>
                            </label>
                            <input type="number" id="swal_permit_fee" class="form-control" placeholder="0.00" step="0.01" required min="0" style="width: 100%; padding: .375rem .75rem; border: 1px solid #ced4da; border-radius: .25rem;">
                        </div>
                    </div>
                `;
            }

            // Payroll adjustments moved to post-approval action only

            // Helper: constrain return date to ±2 days around base return date
            const setReturnDateBounds = (dateStr) => {
                const base = dateStr ? new Date(dateStr) : (vacEndDate ? new Date(vacEndDate) : null);
                if (!base || isNaN(base.getTime())) return;
                const min = new Date(base); min.setDate(min.getDate() - 2);
                const max = new Date(base); max.setDate(max.getDate() + 2);
                $('#swal_return_date').datepicker('setStartDate', min);
                $('#swal_return_date').datepicker('setEndDate', max);
                $('#swal_return_date').datepicker('setDate', base);
            };
            const setStartDateBounds = (dateStr) => {
                const base = dateStr ? new Date(dateStr) : (vacStartDate ? new Date(vacStartDate) : null);
                if (!base || isNaN(base.getTime())) return;
                const min = new Date(base); min.setDate(min.getDate() - 2);
                const max = new Date(base); max.setDate(max.getDate() + 2);
                $('#swal_start_date').datepicker('setStartDate', min);
                $('#swal_start_date').datepicker('setEndDate', max);
                $('#swal_start_date').datepicker('setDate', base);
            };

            // [NEW] HR Payroll: Return Date (Last Working Day)
            if (isHR_Payroll) {
                hrPayrollHtml += `
                    <div class="swal-hr-payroll-fields text-left mt-3">
                        <hr>
                        <h6 class="text-primary mb-3"><i class="fa fa-calendar"></i> ${__('start_date') || 'Start Date'}</h6>
                        <div class="form-group">
                            <label for="swal_start_date" class="font-weight-bold">
                                <i class="fa fa-calendar-day"></i> ${__('start_date') || 'Start Date'}
                                <span class="text-danger">*</span>
                            </label>
                            <input type="text" id="swal_start_date" class="form-control" placeholder="${__('select_return_date') || 'Select return date'}" readonly required style="width: 100%; padding: .375rem .75rem; border: 1px solid #ced4da; border-radius: .25rem; background-color: white; cursor: pointer;">
                            <small class="form-text text-muted">${__('hr_payroll_start_date_note') || 'HR Payroll can adjust the employee\'s last working day (return date) before final approval. Limit: ±2 days from current return date.'}</small>
                        </div>
                    </div>
                    <div class="swal-hr-payroll-fields text-left mt-3">
                        <hr>
                        <h6 class="text-primary mb-3"><i class="fa fa-calendar"></i> ${__('return_date') || 'Return Date (Last Working Day)'}</h6>
                        <div class="form-group">
                            <label for="swal_return_date" class="font-weight-bold">
                                <i class="fa fa-calendar-day"></i> ${__('return_date') || 'Return Date / Last Working Day'}
                                <span class="text-danger">*</span>
                            </label>
                            <input type="text" id="swal_return_date" class="form-control" placeholder="${__('select_return_date') || 'Select return date'}" readonly required style="width: 100%; padding: .375rem .75rem; border: 1px solid #ced4da; border-radius: .25rem; background-color: white; cursor: pointer;">
                            <small class="form-text text-muted">${__('hr_payroll_return_date_note') || 'HR Payroll can adjust the employee\'s last working day (return date) before final approval. Limit: ±2 days from current return date.'}</small>
                        </div>
                    </div>
                `;
            }

            // [NEW] --- GR Officer Visa/Re-Entry Fee Section ---
            if (isGR_Officer && isAnnualFly) {
                hrPayrollHtml = `
                    <div class="swal-gr-officer-fields text-left mt-3">
                        <hr>
                        <h6 class="text-primary mb-3"><i class="fa fa-passport"></i> ${__('visa_re_entry_fee_information') || 'Visa & Re-Entry Fee Information'}</h6>
                        <div class="form-group">
                            <label for="swal_permit_fee" class="font-weight-bold">
                                <i class="fa fa-coins"></i> ${__('permit_fee') || 'Permit & Visa Fees (Exit & Re-Entry)'} 
                                <span class="text-danger">*</span>
                            </label>
                            <input type="number" id="swal_permit_fee" class="form-control" placeholder="0.00" step="0.01" required min="0" style="width: 100%; padding: .375rem .75rem; border: 1px solid #ced4da; border-radius: .25rem;">
                            <small class="form-text text-muted">
                                <i class="fa fa-info-circle"></i> ${__('permit_fee_description') || 'Enter the total amount for exit and re-entry visa permit fees (in SAR)'}
                            </small>
                        </div>
                    </div>
                `;
            }

            // Payroll adjustments moved to post-approval action only
            // Only for HR Senior BP approving (to notify HR team members)
            if (isHR_SeniorBP) {
                hrTeamCCHtml = `
                    <div class="swal-hr-team-cc text-left mt-3">
                        <hr>
                        <h6 class="text-primary mb-3"><i class="fa fa-users"></i> ${__('notify_hr_team')}</h6>
                        <div class="form-group">
                            <label for="hr_team_cc_select" class="font-weight-bold">
                                <i class="fa fa-envelope"></i> ${__('select_hr_team_members_cc')} 
                                <span class="text-muted">(${__('optional')})</span>
                            </label>
                            <select id="hr_team_cc_select" class="form-control swal-select2-dynamic" multiple="multiple" style="width: 100%;">
                                <option value="">${__('loading_hr_team')}</option>
                            </select>
                            <small class="form-text text-muted">
                                <i class="fa fa-info-circle"></i> ${__('hr_team_cc_note') || 'Selected HR team members will receive email notifications (CC only, not approvers)'}
                            </small>
                        </div>
                    </div>
                `;
            }

            // --- Condition 2: Show Chain Builder? ---
            if (isSimpleLeave) {
                // SIMPLE LEAVE APPROVAL LOGIC
                if (isLevel1) {
                    // Level 1: Supervisor/Manager approving → HR Senior BP will be auto-selected by backend
                    chainHtml = `
                        <div class="swal-approval-chain text-left mt-3">
                            <hr>
                            <p class="text-info">
                                <i class="fa fa-info-circle"></i> 
                                ${__('approval_chain_auto_built') || 'Approval will be automatically forwarded to HR Senior BP.'}
                            </p>
                        </div>
                    `;
                }
                // Level 2 (HR Senior BP): No chain needed, final approval

            } else {
                // ANNUAL VACATION APPROVAL LOGIC (Fly)
                if (isLevel1) {
                    // Level 1 Manager: Chain will be auto-built from assets (no UI needed)
                    // Show additional note for Fly + Annual vacations about GR Officer
                    const grOfficerNote = isAnnualFly ? `<br><i class="fa fa-passport"></i> ${__('gr_officer_auto_added', 'GR Officer will be automatically added for exit & re-entry visa processing.')}` : '';
                    chainHtml = `
                        <div class="swal-approval-chain text-left mt-3">
                            <hr>
                            <p class="text-info">
                                <i class="fa fa-info-circle"></i> 
                                ${__('approval_chain_auto_built') || 'Approval chain will be automatically determined based on assigned assets (HR Senior BP + Asset Teams).'}
                                ${grOfficerNote}
                            </p>
                        </div>
                    `;
                } else if (isHR_Assistant) {
                    // HR Assistant: Build full chain
                    chainHtml = `
                        <div class="swal-approval-chain text-left mt-3">
                            <hr>
                            
                            <label for="approver_select" class="mt-2">${__('select_next_approver')}</label>
                            <div class="swal-approval-chain-builder">
                                <select id="approver_select" class="form-control swal-select2-dynamic">
                                    <option value="">${__('select_an_approver')}</option>
                                </select>
                                <button type="button" id="add-approver-btn-new" class="btn btn-success"><i class="fa fa-plus"></i> ${__('add')}</button>
                            </div>

                            <ol id="approver-chain-list" class="mt-3">
                                <li id="approver-chain-list-empty">${__('no_approvers_added_yet')}</li>
                            </ol>
                        </div>
                    `;
                }
            }

            // --- Comment/Review Textarea ---
            // Add comment field for all approvals
            // Make it REQUIRED for GR Officer
            const isCommentRequired = isGR_Officer;
            commentHtml = `
                <div class="swal-comment-section text-left mt-3">
                    <hr>
                    <h6 class="text-primary mb-3">
                        <i class="fa fa-comment"></i> ${__('approval_comment') || 'Approval Comment'}
                        ${isCommentRequired ? '<span class="text-danger">*</span>' : '<span class="text-muted">(Optional)</span>'}
                    </h6>
                    <div class="form-group">
                        <textarea id="swal_approval_comment" class="form-control" rows="4" placeholder="${__('write_comment') || 'Write your comment or review for this approval (optional)...'}" style="width: 100%; padding: .375rem .75rem; border: 1px solid #ced4da; border-radius: .25rem; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto; font-size: 14px;" ${isCommentRequired ? 'required' : ''}></textarea>
                        <small class="form-text text-muted">
                            <span id="char-count">0</span>/5000 ${__('characters')}
                        </small>
                    </div>
                </div>
            `;

            Swal.fire({
                title: isAssetClearanceRole ? (__('asset_clearance_confirmation') || 'Asset Clearance Confirmation') : __('confirm_approval'),
                html: infoHtml + paymentHtml + hrPayrollHtml + hrTeamCCHtml + commentHtml + chainHtml, // Combine all HTML parts
                icon: 'warning',
                // width: '40%', // Set modal width
                showCancelButton: true,
                confirmButtonColor: '#28a745',
                cancelButtonColor: '#dc3535',
                confirmButtonText: confirmButtonText,
                allowOutsideClick: false,
                willOpen: () => {
                    const swalModal = Swal.getHtmlContainer();

                    // Helper function to initialize a Select2 dropdown
                    const initSelect2 = (selector, placeholder) => {
                        $(selector).select2({
                            placeholder: placeholder,
                            allowClear: true,
                            dropdownParent: $(swalModal), // Attach to the modal
                            closeOnSelect: true
                        });
                    };

                    // --- Initialize Date Pickers for Payment / Travel / Return Fields ---
                    if ((isHR_Assistant || isHR_Payroll || isGR_Officer) && isAnnualFly) {
                        // Fetch existing departure and arrival dates from database
                        $.ajax({
                            url: './includes/ajaxFile/ajaxVacation.php',
                            type: 'POST',
                            dataType: 'json',
                            data: {
                                ajaxType: 'getVacationDetails',
                                vacation_id: vacationId
                            },
                            success: function(res) {
                                if (res.status === 200) {
                                    // Initialize departure date picker (Bootstrap Datepicker)
                                    $('#swal_departure_date').datepicker({
                                        format: "yyyy-mm-dd",
                                        startDate: vacStartDate,
                                        endDate: vacEndDate,
                                        todayHighlight: true,
                                        autoclose: true
                                    }).on('changeDate', function(e) {
                                        var departureDate = e.date;
                                        $('#swal_arrival_date').datepicker('setStartDate', departureDate);
                                    });

                                    // Initialize arrival date picker (Bootstrap Datepicker)
                                    $('#swal_arrival_date').datepicker({
                                        format: "yyyy-mm-dd",
                                        startDate: vacStartDate,
                                        endDate: vacEndDate,
                                        todayHighlight: true,
                                        autoclose: true
                                    }).on('changeDate', function(e) {
                                        var arrivalDate = e.date;
                                        $('#swal_departure_date').datepicker('setEndDate', arrivalDate);
                                    });

                                    // Initialize start_date and return_date datepickers for HR Payroll
                                    if (isHR_Payroll) {
                                        // Initialize start_date datepicker
                                        $('#swal_start_date').datepicker({
                                            format: "yyyy-mm-dd",
                                            todayHighlight: true,
                                            autoclose: true
                                        });
                                        
                                        // Initialize return_date datepicker
                                        $('#swal_return_date').datepicker({
                                            format: "yyyy-mm-dd",
                                            todayHighlight: true,
                                            autoclose: true
                                        });
                                    }

                                    // Set initial values if they exist
                                    if (res.departure_date) {
                                        $('#swal_departure_date').datepicker('setDate', res.departure_date);
                                    }
                                    if (res.arrival_date) {
                                        $('#swal_arrival_date').datepicker('setDate', res.arrival_date);
                                    }
                                    
                                    // Set start_date and return_date for HR Payroll
                                    if (isHR_Payroll) {
                                        if (res.start_date) {
                                            $('#swal_start_date').datepicker('setDate', res.start_date);
                                            setStartDateBounds(res.start_date);
                                        } else if (vacStartDate) {
                                            $('#swal_start_date').datepicker('setDate', vacStartDate);
                                            setStartDateBounds(vacStartDate);
                                        }
                                        
                                        if (res.return_date) {
                                            $('#swal_return_date').datepicker('setDate', res.return_date);
                                            setReturnDateBounds(res.return_date);
                                        } else if (vacEndDate) {
                                            $('#swal_return_date').datepicker('setDate', vacEndDate);
                                            setReturnDateBounds(vacEndDate);
                                        }
                                    }
                                }
                            },
                            error: function() {
                                // Initialize with default vacation range even if fetch fails
                                $('#swal_departure_date').datepicker({
                                    format: "yyyy-mm-dd",
                                    startDate: vacStartDate,
                                    endDate: vacEndDate,
                                    todayHighlight: true,
                                    autoclose: true
                                }).on('changeDate', function(e) {
                                    var departureDate = e.date;
                                    $('#swal_arrival_date').datepicker('setStartDate', departureDate);
                                });

                                $('#swal_arrival_date').datepicker({
                                    format: "yyyy-mm-dd",
                                    startDate: vacStartDate,
                                    endDate: vacEndDate,
                                    todayHighlight: true,
                                    autoclose: true
                                }).on('changeDate', function(e) {
                                    var arrivalDate = e.date;
                                    $('#swal_departure_date').datepicker('setEndDate', arrivalDate);
                                });

                                // Initialize start_date and return_date for HR Payroll even if fetch fails
                                if (isHR_Payroll) {
                                    // Initialize start_date datepicker
                                    $('#swal_start_date').datepicker({
                                        format: "yyyy-mm-dd",
                                        todayHighlight: true,
                                        autoclose: true
                                    }).datepicker('setDate', vacStartDate);
                                    setStartDateBounds(vacStartDate);
                                    
                                    // Initialize return_date datepicker
                                    $('#swal_return_date').datepicker({
                                        format: "yyyy-mm-dd",
                                        todayHighlight: true,
                                        autoclose: true
                                    }).datepicker('setDate', vacEndDate);
                                    setReturnDateBounds(vacEndDate);
                                }
                            }
                        });
                    }

                    // Initialize return date picker for HR Payroll even when not Fly/annual
                    if (isHR_Payroll) {
                        // Initialize start_date datepicker
                        $('#swal_start_date').datepicker({
                            format: "yyyy-mm-dd",
                            todayHighlight: true,
                            autoclose: true
                        }).datepicker('setDate', vacStartDate);
                        setStartDateBounds(vacStartDate);
                        
                        // Initialize return_date datepicker
                        $('#swal_return_date').datepicker({
                            format: "yyyy-mm-dd",
                            todayHighlight: true,
                            autoclose: true
                        }).datepicker('setDate', vacEndDate);
                        setReturnDateBounds(vacEndDate);
                    }

                    // --- SIMPLE LEAVE LOGIC ---
                    // (HR Senior BP will be auto-selected by backend, no UI needed)

                    // --- HR TEAM CC LOADING (for HR Senior BP) ---
                    if (isHR_SeniorBP) {
                        console.log('HR Senior BP detected - loading HR team members for CC');
                        // Load HR team members for CC notification
                        initSelect2('#hr_team_cc_select', __('select_hr_team_members'));
                        let $hrTeamCCSelect = $('#hr_team_cc_select');

                        $.ajax({
                            url: './includes/ajaxFile/ajaxEmployee.php',
                            dataType: 'JSON',
                            type: 'POST',
                            data: {
                                ajaxType: "get_hr_team_members" // Get HR team members for CC
                            },
                            success: function(res) {
                                console.log('HR Team Members Response:', res);
                                if (res.status == 200 && res.data.length > 0) {
                                    let hrTeamOptions = ``;
                                    for (let i in res.data) {
                                        // Exclude current user from CC list
                                        if (res.data[i].emp_id != <?= $empid ?>) {
                                            hrTeamOptions += `<option value="${res.data[i].emp_id}">${res.data[i].name} (${res.data[i].dept_name || 'HR'})</option>`;
                                        }
                                    }
                                    console.log('HR Team Options HTML:', hrTeamOptions);
                                    $hrTeamCCSelect.html(hrTeamOptions);
                                } else {
                                    console.log('No HR team members found or invalid response');
                                    $hrTeamCCSelect.html(`<option value="">${__('no_hr_team_found')}</option>`);
                                }
                            },
                            error: (xhr, status, error) => {
                                console.error('Error loading HR team:', status, error);
                                $hrTeamCCSelect.html(`<option value="">${__('error_loading_hr_team')}</option>`);
                            }
                        });
                    }

                    // --- ANNUAL VACATION LOGIC (Level 1 Manager) ---
                    // No UI needed - chain is auto-built in preConfirm

                    // --- Logic for HR Assistant (Loading full chain builder) ---
                    if (isHR_Assistant) {
                        let approverOptionsHtml = '<option value="">' + __('select_an_approver') + '</option>';

                        // Initialize the main "Add" dropdown
                        initSelect2('#approver_select', __('select_an_approver'));

                        // Helper function to re-number the list
                        const reorderList = () => {
                            const list = $(swalModal).find('#approver-chain-list');
                            const items = list.find('li:not(#approver-chain-list-empty)');
                            if (items.length === 0) {
                                list.find('#approver-chain-list-empty').show();
                            } else {
                                list.find('#approver-chain-list-empty').hide();
                                items.each(function(index) {
                                    // Find and remove the old number span/strong
                                    $(this).find('.approver-name strong').remove();
                                    // Prepend the new, correct number
                                    $(this).find('.approver-name').prepend(`<strong>${index + 1}. </strong>`);
                                });
                            }
                        };

                        // Fetch *all* potential approvers for the chain
                        $.ajax({
                            url: './includes/ajaxFile/ajaxEmployee.php',
                            dataType: 'JSON',
                            type: 'POST',
                            data: {
                                ajaxType: "get_potential_approvers" // Get ALL potential approvers
                            },
                            success: function(res) {
                                if (res.status == 200) {
                                    for (let i in res.data) {
                                        // Exclude the current user from the next approver list
                                        if (res.data[i].emp_id != <?= $empid ?>) {
                                            approverOptionsHtml += `<option value="${res.data[i].emp_id}">${res.data[i].name} (${res.data[i].user_type})</option>`;
                                        }
                                    }
                                    // Now that we have the options, set the dropdown HTML
                                    $(swalModal).find('#approver_select').html(approverOptionsHtml);
                                }
                            }
                        });

                        // Click listener for the "Add" button
                        $(swalModal).on('click', '#add-approver-btn-new', function() {
                            const selector = $(swalModal).find('#approver_select');
                            const selectedId = selector.val();
                            const selectedText = selector.find('option:selected').text();

                            if (selectedId && selectedText) {
                                let alreadyAdded = false;
                                $(swalModal).find('#approver-chain-list li').each(function() {
                                    if ($(this).data('id') == selectedId) {
                                        alreadyAdded = true;
                                    }
                                });

                                if (alreadyAdded) {
                                    Swal.showValidationMessage(__('approver_already_added'));
                                    return;
                                }

                                const newItemHtml = `
                                    <li data-id="${selectedId}">
                                        <span class="approver-name">${selectedText}</span>
                                        <button type="button" class="remove-approver-btn">&times;</button>
                                    </li>
                                `;
                                $(swalModal).find('#approver-chain-list').append(newItemHtml);
                                reorderList();
                                selector.val(null).trigger('change');
                            }
                        });

                        // Click listener for "Remove" button
                        $(swalModal).on('click', '.remove-approver-btn', function() {
                            $(this).closest('li').remove();
                            reorderList();
                        });
                    } // --- End if (isHR_Assistant) ---

                    // --- APPROVAL COMMENT CHARACTER COUNTER ---
                    $(swalModal).on('input', '#swal_approval_comment', function() {
                        const currentLength = $(this).val().length;
                        const maxLength = 5000;

                        $('#char-count').text(currentLength);

                        // Change color if approaching limit
                        if (currentLength > maxLength * 0.9) {
                            $('#char-count').css('color', '#dc3545'); // Red warning
                        } else if (currentLength > maxLength * 0.7) {
                            $('#char-count').css('color', '#ffc107'); // Yellow warning
                        } else {
                            $('#char-count').css('color', '#6c757d'); // Default gray
                        }
                    });

                    // --- HR PAYROLL CALCULATIONS ---
                    if (isHR_Payroll) {
                        console.log('HR Payroll calculations initialized');

                        let basicSalary = 0;
                        let contractSalaryBase = 0;

                        // Fetch employee salary from backend
                        $.ajax({
                            url: './includes/ajaxFile/ajaxEmployee.php',
                            type: 'POST',
                            dataType: 'json',
                            data: {
                                ajaxType: 'get_employee_salary',
                                emp_id: employeeId
                            },
                            success: function(res) {
                                console.log('Salary response:', res);
                                if (res.status === 200 && res.salary) {
                                    contractSalaryBase = parseFloat(res.salary) || 0;
                                    basicSalary = parseFloat(res.basic_salary) || 0;
                                    $('#employee_salary').val(contractSalaryBase);
                                    $('#employee_basic_salary').val(basicSalary);
                                    console.log('Employee salary loaded - Contract Base:', contractSalaryBase, 'Basic:', basicSalary);
                                    // Trigger calculation after salary is loaded with small delay to ensure DOM is ready
                                    setTimeout(function() {
                                        console.log('Triggering calculation after salary load');
                                        calculatePayrollAdjustments();
                                    }, 100);
                                } else {
                                    console.warn('Failed to load employee salary:', res.message);
                                }
                            },
                            error: function(xhr, status, error) {
                                console.error('Error loading employee salary:', status, error);
                                console.error('Response:', xhr.responseText);
                            }
                        });

                        // Function to calculate and display payroll adjustments
                        // Uses EOS file calculation logic (emp_end_of_service.php lines 627-638)
                        function calculatePayrollAdjustments() {
                            const salary = parseFloat($('#employee_salary').val()) || 0;
                            const basic = parseFloat($('#employee_basic_salary').val()) || 0;

                            console.log('Calculating with salary:', salary, 'basic:', basic);

                            // Get input values
                            const overtimeHours = parseFloat($('#swal_overtime_hours').val()) || 0;
                            const deductionHours = parseFloat($('#swal_deduction_hours').val()) || 0;
                            const deductionDays = parseFloat($('#swal_deduction_days').val()) || 0;

                            console.log('Values:', {
                                overtimeHours,
                                deductionHours,
                                deductionDays
                            });

                            // --- CALCULATION LOGIC MATCHING PAYROLL SYSTEM ---
                            // (Same as update_payroll.php and process_payroll.php)
                            
                            // OVERTIME CALCULATION (overtime_basic type from benefit_types):
                            // Formula: (basic_salary / 240 / 2) + (total_gross_salary / 240)
                            // This matches the EOS labor law calculation for overtime premium
                            const overtimeHourlyRate = (basic / 240 / 2) + (salary / 240);
                            
                            // DEDUCTION CALCULATION (standard hourly/daily rates):
                            // Daily rate: total_salary / 30 days
                            // Hourly rate: daily_rate / 8 hours
                            const dailyRateDeduction = salary / 30;
                            const hourlyRateDeduction = dailyRateDeduction / 8;

                            // Calculate amounts
                            const overtimeAmount = overtimeHourlyRate * overtimeHours;
                            const deductionHoursAmount = hourlyRateDeduction * deductionHours;
                            const deductionDaysAmount = dailyRateDeduction * deductionDays;

                            console.log('Rates:', {
                                dailyRateDeduction: dailyRateDeduction.toFixed(4),
                                hourlyRateDeduction: hourlyRateDeduction.toFixed(4),
                                overtimeHourlyRate: overtimeHourlyRate.toFixed(4)
                            });
                            console.log('Calculated amounts:', {
                                overtimeAmount,
                                deductionHoursAmount,
                                deductionDaysAmount
                            });

                            // Update individual field calculations
                            if (overtimeHours > 0 && salary > 0) {
                                $('#overtime_amount').text(overtimeAmount.toFixed(2));
                                $('#overtime_calc').show();
                            } else {
                                $('#overtime_calc').hide();
                            }

                            if (deductionHours > 0 && salary > 0) {
                                $('#deduction_hours_amount').text(deductionHoursAmount.toFixed(2));
                                $('#deduction_hours_calc').show();
                            } else {
                                $('#deduction_hours_calc').hide();
                            }

                            if (deductionDays > 0 && salary > 0) {
                                $('#deduction_days_amount').text(deductionDaysAmount.toFixed(2));
                                $('#deduction_days_calc').show();
                            } else {
                                $('#deduction_days_calc').hide();
                            }

                            // Calculate and update summary
                            const totalOvertime = overtimeAmount;
                            const totalDeductions = deductionHoursAmount + deductionDaysAmount;
                            const netAdjustment = totalOvertime - totalDeductions;

                            if ((overtimeHours > 0 || deductionHours > 0 || deductionDays > 0) && salary > 0) {
                                $('#total_overtime').text(totalOvertime.toFixed(2));
                                $('#total_deductions').text(totalDeductions.toFixed(2));

                                // Format net adjustment with +/- sign and color
                                const netText = (netAdjustment >= 0 ? '+' : '') + netAdjustment.toFixed(2) + ' SAR';
                                const netColor = netAdjustment >= 0 ? 'text-success' : 'text-danger';
                                $('#net_adjustment_display')
                                    .text(netText)
                                    .removeClass('text-success text-danger')
                                    .addClass(netColor);

                                $('#payroll_summary').show();
                            } else {
                                $('#payroll_summary').hide();
                            }
                        }

                        // Attach input event listeners to payroll calculation inputs directly
                        $(document).on('input', '#swal_overtime_hours, #swal_deduction_hours, #swal_deduction_days', function() {
                            console.log('Input detected on:', this.id, 'Value:', $(this).val());
                            calculatePayrollAdjustments();
                        });

                        // Initial calculation - backup in case AJAX is slow
                        // Primary calculation happens in AJAX success callback
                        setTimeout(function() {
                            console.log('Running backup initial calculation');
                            // Only run if salary has been loaded
                            if (contractSalaryBase > 0) {
                                calculatePayrollAdjustments();
                            }
                        }, 1200);
                    } // --- End if (isHR_Payroll) ---
                },
                preConfirm: () => {
                    const swalModal = Swal.getHtmlContainer();
                    let approver_chain = [];

                    // Get approval comment (if provided)
                    let approval_comment = $(swalModal).find('#swal_approval_comment').val() || '';
                    approval_comment = approval_comment.trim().substring(0, 5000); // Limit to 5000 chars

                    // [REQUIRED] GR Officer MUST provide a comment
                    if (isGR_Officer && !approval_comment) {
                        Swal.showValidationMessage(__('approval_comment_required_for_gr_officer') || 'GR Officer must provide an approval comment');
                        return false;
                    }

                    // A) Get payment details (if they exist)
                    let ticket_pay = $(swalModal).find('#swal_ticket_fares').val() || null;
                    let permit_fee = $(swalModal).find('#swal_permit_fee').val() || null;

                    // A.0) HR Payroll return date (last working day)
                    let return_date = $(swalModal).find('#swal_return_date').val() || null;

                    // A.1) Get HR Payroll details (if they exist)
                    let overtime_hours = $(swalModal).find('#swal_overtime_hours').val() || null;
                    let deduction_hours = $(swalModal).find('#swal_deduction_hours').val() || null;
                    let deduction_days = $(swalModal).find('#swal_deduction_days').val() || null;
                    let payroll_note = $(swalModal).find('#swal_payroll_note').val() || null;

                    // A.5) Get HR Team CC members (if HR Senior BP is approving)
                    let hr_team_cc = [];
                    if (isHR_SeniorBP) {
                        let selectedCC = $(swalModal).find('#hr_team_cc_select').val();
                        if (selectedCC && Array.isArray(selectedCC)) {
                            hr_team_cc = selectedCC;
                        }
                    }

                    // A.2) Validate payment fields if HR Assistant (not HR_Payroll - they can add payment later)
                    // if ((isHR_Assistant || isGR_Officer) && isAnnualFly) {
                    if ((isHR_Assistant) && isAnnualFly) {
                        const departure = $(swalModal).find('#swal_departure_date').val();
                        const arrival = $(swalModal).find('#swal_arrival_date').val();
                        const ticket = $(swalModal).find('#swal_ticket_fares').val();
                        const permit = $(swalModal).find('#swal_permit_fee').val();

                        if (!departure || departure.trim() === '') {
                            Swal.showValidationMessage(__('departure_date_required') || 'Departure date is required');
                            return false;
                        }
                        if (!arrival || arrival.trim() === '') {
                            Swal.showValidationMessage(__('arrival_date_required') || 'Arrival date is required');
                            return false;
                        }
                        if (!ticket || parseFloat(ticket) <= 0) {
                            Swal.showValidationMessage(__('ticket_pay_required') || 'Ticket payment amount is required');
                            return false;
                        }
                        if (!permit || parseFloat(permit) <= 0) {
                            Swal.showValidationMessage(__('permit_fee_required') || 'Exit re-entry fee is required');
                            return false;
                        }
                    }
                    
                    // HR Payroll must set start_date and return_date
                    if (isHR_Payroll) {
                        const start_date = $(swalModal).find('#swal_start_date').val();
                        if (!start_date || start_date.trim() === '') {
                            Swal.showValidationMessage(__('start_date_required') || 'Start date is required for HR Payroll approval');
                            return false;
                        }
                        if (!return_date || return_date.trim() === '') {
                            Swal.showValidationMessage(__('return_date_required') || 'Return date is required for HR Payroll approval');
                            return false;
                        }
                    }

                    // [UPDATED] Validate GR Officer required fields if GR Officer is approving Fly | Annual
                    if ((isGR_Officer && isAnnualFly)) {
                        const permitFee = $(swalModal).find('#swal_permit_fee').val();
                        if (!permitFee || parseFloat(permitFee) <= 0) {
                            Swal.showValidationMessage(__('permit_fee_required') || 'Permit & Visa Fees are required');
                            return false;
                        }
                    }

                    // B) Get approver chain (based on leave type and role)
                    if (isSimpleLeave && isLevel1) {
                        // Simple leave: Level 1 - HR Senior BP will be auto-selected by backend
                        // Return a Promise to get HR Senior BP from backend
                        return new Promise(function(resolve, reject) {
                            $.ajax({
                                url: './includes/ajaxFile/ajaxEmployee.php',
                                type: 'POST',
                                dataType: 'json',
                                data: {
                                    ajaxType: 'get_hr_senior_bp'
                                },
                            }).done(function(res) {
                                if (!res || res.status !== 200 || !res.data || res.data.length === 0) {
                                    Swal.showValidationMessage(__('no_hr_senior_bp_found') || 'No HR Senior BP found');
                                    return reject('No HR Senior BP found');
                                }
                                // Automatically use the first HR Senior BP
                                let hrBPId = res.data[0].emp_id;
                                console.log('Simple leave - Auto-selected HR Senior BP:', hrBPId);
                                resolve({
                                    approver_chain: [hrBPId]
                                });
                            }).fail(function() {
                                Swal.showValidationMessage(__('error_loading_hr_senior_bp') || 'Error loading HR Senior BP');
                                reject('Error loading HR Senior BP');
                            });
                        });

                    } else if (!isSimpleLeave && isLevel1) {
                        // Vacation (annual vacation) flow: After Manager, auto-route to HR Senior BP then asset teams
                        // Build chain by calling backend helper; return a Promise to resolve approver_chain
                        return new Promise(function(resolve, reject) {
                            $.ajax({
                                url: './includes/ajaxFile/ajaxEmployee.php',
                                type: 'POST',
                                dataType: 'json',
                                data: {
                                    ajaxType: 'get_asset_clearance_chain',
                                    vacation_id: vacationId,
                                    exclude_level1: true // Exclude first approver (they're approving now)
                                },
                            }).done(function(res) {
                                if (!res || res.status !== 200 || !Array.isArray(res.chain)) {
                                    Swal.showValidationMessage('Unable to build approval chain');
                                    return reject('Unable to build approval chain');
                                }
                                // Use the chain returned from the backend (HR Senior BP, GR Officer, etc.)
                                console.log('Level 1 vacation approval - Retrieved chain:', res.chain);
                                resolve({
                                    approver_chain: res.chain, // Use the actual chain from backend!
                                    departure_date: $(swalModal).find('#swal_departure_date').val() || null,
                                    arrival_date: $(swalModal).find('#swal_arrival_date').val() || null,
                                    ticket_pay: $(swalModal).find('#swal_ticket_fares').val() || null,
                                    permit_fee: $(swalModal).find('#swal_permit_fee').val() || null,
                                    overtime_hours: $(swalModal).find('#swal_overtime_hours').val() || null,
                                    deduction_hours: $(swalModal).find('#swal_deduction_hours').val() || null,
                                    deduction_days: $(swalModal).find('#swal_deduction_days').val() || null,
                                    payroll_note: $(swalModal).find('#swal_payroll_note').val() || null,
                                    hr_team_cc: []
                                });
                            }).fail(function() {
                                Swal.showValidationMessage('Unable to build approval chain');
                                reject('Unable to build approval chain');
                            });
                        });

                    } else if (isHR_Assistant) {
                        // HR Assistant building chain for annual vacation
                        $(swalModal).find('#approver-chain-list li').each(function() {
                            if ($(this).data('id')) {
                                approver_chain.push($(this).data('id'));
                            }
                        });
                        if (approver_chain.length === 0) {
                            Swal.showValidationMessage(__('select_next_approver'));
                            return false; // enforce required next approver
                        }
                    }
                    // For L2+, approver_chain remains empty, which is correct

                    // Return all gathered data
                    const departureDateVal = $(swalModal).find('#swal_departure_date').val() || '';
                    const arrivalDateVal = $(swalModal).find('#swal_arrival_date').val() || '';
                    const returnDateVal = $(swalModal).find('#swal_return_date').val() || '';
                    const startDateVal = $(swalModal).find('#swal_start_date').val() || '';

                    // Log for debugging
                    console.log('sendApproval preConfirm - departure_date:', departureDateVal);
                    console.log('sendApproval preConfirm - arrival_date:', arrivalDateVal);
                    console.log('sendApproval preConfirm - ticket_pay:', ticket_pay);
                    console.log('sendApproval preConfirm - permit_fee:', permit_fee);
                    console.log('sendApproval preConfirm - return_date:', returnDateVal);
                    console.log('sendApproval preConfirm - start_date:', startDateVal);

                    return {
                        approver_chain: approver_chain,
                        departure_date: departureDateVal,
                        arrival_date: arrivalDateVal,
                        return_date: returnDateVal,
                        start_date: startDateVal,
                        ticket_pay: ticket_pay,
                        permit_fee: permit_fee, // [UPDATED] Include permit_fee for GR Officer
                        hr_team_cc: hr_team_cc,
                        overtime_hours: overtime_hours,
                        deduction_hours: deduction_hours,
                        deduction_days: deduction_days,
                        payroll_note: payroll_note,
                        approval_comment: approval_comment
                    }
                }
            }).then(function(result) {
                if (result.isConfirmed) {
                    // User confirmed assets received (or regular approval)
                    sendApproval(vacationId, result.value);
                }
            });
        }

        /**
         * =================================================================
         * == NEW sendApproval FUNCTION
         * =================================================================
         * Now sends the dynamic `approver_chain` array.
         * Also sends `hr_team_cc` array if HR Senior BP selected team members for CC.
         * Removed ticketPay/permitFee as that's separate logic.
         */
        function sendApproval(vacationId, approveData) {
            // Show processing loader immediately after approval
            Swal.fire({
                title: __('processing_approval') || 'Processing approval...',
                html: __('please_wait_processing') || 'Please wait while we process this approval.',
                allowOutsideClick: false,
                allowEscapeKey: false,
                showConfirmButton: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            // Log all data being sent
            console.log('sendApproval - Complete approveData:', approveData);
            console.log('sendApproval - departure_date to send:', approveData.departure_date);
            console.log('sendApproval - arrival_date to send:', approveData.arrival_date);

            $.ajax({
                    url: './includes/ajaxFile/ajaxVacation.php',
                    type: 'POST',
                    dataType: 'JSON',
                    data: {
                        ajaxType: 'approveVacation',
                        vacation_id: vacationId,
                        approver_chain: approveData.approver_chain || [], // Send the dynamic chain
                        asset_checker_emp_id: approveData.asset_checker_emp_id || null, // Asset checker assigned by asset managers
                        departure_date: approveData.departure_date || null, // Send departure date
                        arrival_date: approveData.arrival_date || null, // Send arrival date
                        return_date: approveData.return_date || null, // HR Payroll adjusted return/last working day
                        ticket_pay: approveData.ticket_pay || null, // Send ticket pay
                        permit_fee: approveData.permit_fee || null, // Send permit fee
                        permit_fee: approveData.permit_fee || null, // [UPDATED] Send permit_fee for GR Officer
                        hr_team_cc: approveData.hr_team_cc || [], // Send HR team CC
                        overtime_hours: approveData.overtime_hours || null, // Send overtime hours
                        deduction_hours: approveData.deduction_hours || null, // Send deduction hours
                        deduction_days: approveData.deduction_days || null, // Send deduction days
                        payroll_note: approveData.payroll_note || null, // Send payroll note
                        approval_comment: approveData.approval_comment || '' // Send approval comment
                    },
                })
                .done(function(response) {
                    console.log('sendApproval - Backend response:', response);
                    console.log('sendApproval - Response success:', response.success);
                    console.log('sendApproval - Response message:', response.message);

                    Swal.fire({
                        title: response.title || __('success') || 'Success',
                        text: response.message || '',
                        icon: response.type || 'success',
                        allowOutsideClick: false
                    }).then(function(isConfirm) {
                        if (isConfirm) {
                            location.reload();
                        }
                    });
                })
                .fail(function(jqXHR, textStatus, errorThrown) {
                    console.log('sendApproval - AJAX failed');
                    console.log('sendApproval - Text Status:', textStatus);
                    console.log('sendApproval - Error Thrown:', errorThrown);
                    console.log('sendApproval - Full Response:', jqXHR);

                    // Use SweetAlert to show the failure
                    Swal.fire({
                        title: __('error') || 'Error',
                        text: (jqXHR.responseJSON && jqXHR.responseJSON.message) ? jqXHR.responseJSON.message : ('An error occurred: ' + textStatus),
                        icon: 'error',
                        allowOutsideClick: false
                    });
                });
        }

        // AJAX call for sending approval with payer selection (Finance Manager)
        function sendApprovalWithPayer(vacationId, approverRole, payerId, approvalComment = '') {
            // Show loading state
            Swal.fire({
                title: 'Processing...',
                html: 'Assigning payer and sending notification emails...',
                icon: 'info',
                allowOutsideClick: false,
                allowEscapeKey: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            $.ajax({
                url: './includes/ajaxFile/ajaxVacation.php',
                type: 'POST',
                dataType: 'JSON',
                data: {
                    ajaxType: 'approveVacation',
                    vacation_id: vacationId,
                    approver_role: approverRole,
                    payer_emp_id: payerId,
                    approval_comment: approvalComment
                },
                success: function(response) {
                    Swal.fire({
                        title: response.title || 'Approved!',
                        text: response.message || 'The vacation request has been approved and payer has been assigned.',
                        icon: response.type || 'success',
                        allowOutsideClick: false
                    }).then(() => location.reload());
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    Swal.fire({
                        title: 'Error!',
                        text: (jqXHR.responseJSON && jqXHR.responseJSON.message) ? jqXHR.responseJSON.message : 'Something went wrong with the approval.',
                        icon: 'error',
                        allowOutsideClick: false
                    });
                }
            });
        }

        /**
         * =Slightly modified rejectVacationRequest
         * Removed 'role' as it's not needed by the new backend
         */
        function rejectVacationRequest(vacationId, employeeName, vacType, startDate, endDate, totalDays) {
            let infoHtml = `
				<div class="swal-vacation-details">
					<div class="swal-details-header"><i class="fas fa-info-circle"></i> ${__('request_details')}</div>
					<div class="swal-details-body">
						<div class="swal-detail-item"><span class="swal-detail-label">${__('employee')}</span> <span class="swal-detail-value"><i class="fas fa-user"></i> ${employeeName}</span></div>
						<div class="swal-detail-item"><span class="swal-detail-label">${__('type')}</span> <span class="swal-detail-value"><i class="fas fa-suitcase-rolling"></i> ${vacType}</span></div>
						<div class="swal-detail-item"><span class="swal-detail-label">${__('start_date')}</span> <span class="swal-detail-value"><i class="fas fa-calendar-alt"></i> ${startDate}</span></div>
						<div class="swal-detail-item"><span class="swal-detail-label">${__('return_date')}</span> <span class="swal-detail-value"><i class="fas fa-calendar-check"></i> ${endDate}</span></div>
						<div class="swal-detail-item"><span class="swal-detail-label">${__('total_days')}</span> <span class="swal-detail-value"><i class="fas fa-sun"></i> ${totalDays}</span></div>
					</div>
				</div>
			`;
            Swal.fire({
                title: __('confirm_rejection'),
                html: infoHtml,
                input: 'textarea',
                inputLabel: __('provide_rejection_reason'),
                inputPlaceholder: __('enter_reason_here'),
                showCancelButton: true,
                confirmButtonText: __('submit_rejection'),
                confirmButtonColor: '#dc3545',
                showLoaderOnConfirm: true,
                allowOutsideClick: false,
                inputValidator: (value) => {
                    if (!value) {
                        return __('must_provide_rejection_reason')
                    }
                },
                preConfirm: (reason) => {
                    $.ajax({
                            url: './includes/ajaxFile/ajaxVacation.php',
                            type: 'POST',
                            dataType: 'JSON',
                            data: {
                                ajaxType: 'rejectVacation',
                                vacation_id: vacationId,
                                rejection_note: reason
                                // approver_role is no longer needed
                            }
                        })
                        .done(function(response) {
                            Swal.fire({
                                title: response.title,
                                text: response.message,
                                icon: response.type,
                                allowOutsideClick: false
                            }).then(function(isConfirm) {
                                (isConfirm) ? location.reload(): ""
                            });
                        })
                        .fail(function(jqXHR, textStatus, errorThrown) {
                            Swal.fire('Error', 'An error occurred: ' + textStatus, 'error');
                        });
                }
            })
        }

        /**
         * addVacationPayments - This is for editing/adding payments *outside* the approval flow
         */
        function addVacationPayments(vacationId, employeeName, currentTicketPay, currentPermitFee) {
            // First fetch current departure and arrival dates
            $.ajax({
                url: './includes/ajaxFile/ajaxVacation.php',
                type: 'POST',
                dataType: 'JSON',
                data: {
                    ajaxType: 'getVacationDetails',
                    vacation_id: vacationId
                },
                success: function(data) {
                    if (data.status === 200) {
                        showPaymentModal(vacationId, employeeName, currentTicketPay, currentPermitFee, data.departure_date, data.arrival_date);
                    } else {
                        showPaymentModal(vacationId, employeeName, currentTicketPay, currentPermitFee, '', '');
                    }
                },
                error: function() {
                    showPaymentModal(vacationId, employeeName, currentTicketPay, currentPermitFee, '', '');
                }
            });
        }

        // addVacationAdjustments function moved to jquery.app.js as global function
        // It now supports: vacationId, employeeName, overtimeHours, deductionHours, deductionDays, otherEarnings, payrollNote
        // With calculation display and backward compatibility

        function showPaymentModal(vacationId, employeeName, currentTicketPay, currentPermitFee, currentDepartureDate, currentArrivalDate) {
            Swal.fire({
                title: __('add_edit_payments_for').replace('{0}', employeeName),
                html: `
                    <div class="text-left" style="padding: 10px 20px;">
                        <p class="mt-3 mb-4"><strong>${__('enter_update_payment_details')}</strong></p>
                        
                        <div class="form-group mb-3">
                            <label for="departure_date_update" class="d-block text-left font-weight-bold mb-2" style="color: #333;">
                                <i class="fa fa-plane-departure"></i> ${__('departure_date')}
                                <span class="text-danger">*</span>
                            </label>
                            <input type="text" id="departure_date_update" class="form-control" placeholder="Select departure date" readonly style="width: 100%; padding: .75rem; border: 1px solid #ced4da; border-radius: .25rem; background-color: white; cursor: pointer;">
                        </div>
                        
                        <div class="form-group mb-3">
                            <label for="arrival_date_update" class="d-block text-left font-weight-bold mb-2" style="color: #333;">
                                <i class="fa fa-plane-arrival"></i> ${__('arrival_date')}
                                <span class="text-danger">*</span>
                            </label>
                            <input type="text" id="arrival_date_update" class="form-control" placeholder="Select arrival date" readonly style="width: 100%; padding: .75rem; border: 1px solid #ced4da; border-radius: .25rem; background-color: white; cursor: pointer;">
                        </div>
                        
                        <div class="form-group mb-3">
                            <label for="ticket_pay_update" class="d-block text-left font-weight-bold mb-2" style="color: #333;">
                                <i class="fa fa-plane"></i> ${__('ticket_payment')}
                            </label>
                            <input type="number" id="ticket_pay_update" class="form-control" placeholder="${__('ticket_payment')}" value="${currentTicketPay}" step="0.01" style="width: 100%; padding: .75rem; border: 1px solid #ced4da; border-radius: .25rem;">
                        </div>
                        
                        <div class="form-group mb-3">
                            <label for="permit_fee_update" class="d-block text-left font-weight-bold mb-2" style="color: #333;">
                                <i class="fa fa-passport"></i> ${__('permit_fee')}
                            </label>
                            <input type="number" id="permit_fee_update" class="form-control" readonly placeholder="${__('permit_fee')}" value="${currentPermitFee}" step="0.01" style="width: 100%; padding: .75rem; border: 1px solid #ced4da; border-radius: .25rem;">
                        </div>
                    </div>
                `,
                confirmButtonText: __('update_payments'),
                showCancelButton: true,
                allowOutsideClick: false,
                width: '500px',
                didOpen: () => {
                    // Initialize departure date picker
                    $('#departure_date_update').datepicker({
                        format: "yyyy-mm-dd",
                        todayHighlight: true,
                        autoclose: true
                    }).on('changeDate', function(e) {
                        var departureDate = e.date;
                        $('#arrival_date_update').datepicker('setStartDate', departureDate);
                    });

                    // Initialize arrival date picker
                    $('#arrival_date_update').datepicker({
                        format: "yyyy-mm-dd",
                        todayHighlight: true,
                        autoclose: true
                    }).on('changeDate', function(e) {
                        var arrivalDate = e.date;
                        $('#departure_date_update').datepicker('setEndDate', arrivalDate);
                    });

                    // Set initial values if they exist
                    if (currentDepartureDate) {
                        $('#departure_date_update').datepicker('setDate', currentDepartureDate);
                    }
                    if (currentArrivalDate) {
                        $('#arrival_date_update').datepicker('setDate', currentArrivalDate);
                    }
                },
                preConfirm: () => {
                    return {
                        departure_date: document.getElementById('departure_date_update').value,
                        arrival_date: document.getElementById('arrival_date_update').value,
                        ticket_pay: document.getElementById('ticket_pay_update').value,
                        permit_fee: document.getElementById('permit_fee_update').value
                    }
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                            url: './includes/ajaxFile/ajaxVacation.php',
                            type: 'POST',
                            dataType: 'JSON',
                            data: {
                                ajaxType: 'updateVacationPayments',
                                vacation_id: vacationId,
                                departure_date: result.value.departure_date,
                                arrival_date: result.value.arrival_date,
                                ticket_pay: result.value.ticket_pay,
                                permit_fee: result.value.permit_fee
                            },
                        })
                        .done(function(response) {
                            Swal.fire({
                                title: response.title,
                                text: response.message,
                                icon: response.type,
                                allowOutsideClick: false
                            }).then(function(isConfirm) {
                                (isConfirm) ? location.reload(): ""
                            });
                        })
                        .fail(function(jqXHR, textStatus, errorThrown) {
                            Swal.fire('Error', __('error_updating_payments'), 'error');
                        });
                }
            });
        }
        /**
         * =================================================================
         * == SEND TRAVEL EMAIL FUNCTION
         * =================================================================
         * Sends employee travel information to the traveling company
         */
        function sendTravelEmail(vacationId, employeeName) {
            // First, fetch full traveler details
            Swal.fire({
                title: __('loading') || 'Loading...',
                html: __('fetching_traveler_details') || 'Fetching traveler details...',
                allowOutsideClick: false,
                allowEscapeKey: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            $.ajax({
                    url: './includes/ajaxFile/ajaxVacation.php',
                    type: 'POST',
                    dataType: 'JSON',
                    data: {
                        ajaxType: 'getTravelerDetails',
                        vacation_id: vacationId
                    },
                })
                .done(function(response) {
                    if (response.type === 'success' && response.data) {
                        const data = response.data;

                        // Compute passport validation: missing number or expiry within 3 months from departure (or today if no departure date)
                        const missingPassport = !data.passport_number_raw || data.passport_number === 'Not Provided' || data.passport_number === 'N/A';
                        const depBase = data.departure_date_raw ? new Date(data.departure_date_raw) : new Date();
                        let expSoon = false;
                        if (data.passport_exp_raw) {
                            const expDate = new Date(data.passport_exp_raw);
                            const diffMs = expDate.getTime() - depBase.getTime();
                            const diffDays = diffMs / (1000 * 60 * 60 * 24);
                            const diffMonths = diffDays / 30.44; // approx. months
                            expSoon = diffMonths < 3; // less than 3 months validity
                        } else {
                            // No expiry provided -> treat as invalid
                            expSoon = true;
                        }
                        const invalidPassport = missingPassport || expSoon;

                        const passportWarningHtml = invalidPassport ? `
                        <div style="background: #f8d7da; padding: 12px; border-radius: 6px; margin-bottom: 12px; border: 1px solid #f5c2c7;">
                            <p style="margin: 0; font-size: 13px; color: #842029;">
                                <i class="fa fa-exclamation-circle" style="margin-right: 6px;"></i>
                                <strong>${__('passport_validation_issue') || 'Passport Issue:'}</strong>
                                ${missingPassport ? (__('passport_missing_message') || 'Passport number is missing.') : ''}
                                ${missingPassport && expSoon ? ' ' : ''}
                                ${!missingPassport && expSoon ? (__('passport_expiring_soon_message') || 'Passport expires within 3 months of travel.') : ''}
                            </p>
                        </div>
                    ` : '';

                        // Build existing passport doc HTML if present
                        let existingPassportHtml = '';
                        if (data.passport_doc_url) {
                            const isImg = data.passport_doc_is_image;
                            const ext = data.passport_doc_ext || '';
                            const viewContent = isImg ? `<img src="${data.passport_doc_url}" alt="Passport Copy" style="max-width:100%; max-height:180px; border:1px solid #ddd; border-radius:6px;"/>` : `<a href="${data.passport_doc_url}" target="_blank" class="btn btn-sm btn-outline-primary"><i class="fa fa-file"></i> View Passport (${ext.toUpperCase()})</a>`;
                            existingPassportHtml = `
                            <div id="existing-passport-doc" style="background:#eef7ff; padding:12px; border-radius:6px; margin-top:15px; border:1px solid #c5e0ff;">
                                <h6 style="margin:0 0 10px; color:#0b5eb7;"><i class='fa fa-file'></i> ${__('current_passport_copy') || 'Current Passport Copy'}</h6>
                                <div style="margin-bottom:10px;">${viewContent}</div>
                                <button type="button" id="open-passport-btn" class="btn btn-sm btn-primary" style="margin-right:8px;"><i class="fa fa-external-link-alt"></i> ${__('open_passport_file') || 'Open Passport'}</button>
                                <button type="button" id="replace-passport-btn" class="btn btn-sm btn-warning"><i class="fa fa-sync-alt"></i> ${__('replace_passport_copy') || 'Replace Passport Copy'}</button>
                                <input type="file" id="replace_passport_input" accept=".pdf,.jpg,.jpeg,.png" style="display:none;" />
                                <small class="form-text text-muted" id="replace-passport-hint" style="display:none;">
                                    <i class="fa fa-info-circle"></i> ${__('upload_new_passport_hint') || 'Select a new file to replace the stored passport copy.'}
                                </small>
                            </div>
                        `;
                        }
                        // Build passport section: show upload only if no existing doc
                        const requireUpload = !data.passport_doc_url;
                        const passportSectionHtml = `
                        <div style="background: #e7f3ff; padding: 15px; border-radius: 8px; margin-top: 15px; border-left: 4px solid #3085d6;">
                            <h5 style="color: #004085; margin-top: 0; border-bottom: 2px solid #3085d6; padding-bottom: 8px;">
                                <i class="fa fa-file-upload"></i> ${__('passport_copy') || 'Passport Copy'} ${requireUpload ? '<span class="text-danger">*</span>' : ''}
                            </h5>
                            ${requireUpload ? `
                                <div class="form-group" style="margin-bottom: 0;">
                                    <label for="passport_file" style="font-weight: 600; color: #666; margin-bottom: 8px; display: block;">
                                        <i class="fa fa-passport" style="margin-right: 5px; color: #3085d6;"></i> ${__('select_passport_file') || 'Select passport copy (PDF, JPG, PNG)'}
                                    </label>
                                    <input type="file" id="passport_file" accept=".pdf,.jpg,.jpeg,.png" class="form-control-file" style="padding: 10px; border: 2px dashed #3085d6; border-radius: 6px; background: #f8f9fa; width: 100%;">
                                    <small class="form-text text-muted" style="margin-top: 8px; display: block;">
                                        <i class="fa fa-info-circle"></i> ${__('passport_file_help') || 'Please upload a clear copy of the employee\'s passport. Accepted formats: PDF, JPG, PNG (Max 5MB)'}
                                    </small>
                                </div>
                            ` : ''}
                            ${existingPassportHtml}
                        </div>
                    `;
                        // Show confirmation with all traveler details
                        Swal.fire({
                            title: '<i class="fa fa-passport"></i> ' + (__('verify_traveler_information') || 'Verify Traveler Information'),
                            html: `
                            <div style="text-align: left; max-height: 500px; overflow-y: auto;">
                                ${passportWarningHtml}
                                <div style="background: #f8f9fa; padding: 15px; border-radius: 8px; margin-bottom: 15px;">
                                    <h5 style="color: #667eea; margin-top: 0; border-bottom: 2px solid #667eea; padding-bottom: 8px;">
                                        <i class="fa fa-user"></i> ${__('employee_information') || 'Employee Information'}
                                    </h5>
                                    <table style="width: 100%; font-size: 14px;">
                                        <tr style="border-bottom: 1px solid #dee2e6;">
                                            <td style="padding: 8px; font-weight: 600; color: #666; width: 45%;"><i class="fa fa-id-card" style="margin-right: 5px; color: #667eea;"></i> ${__('employee_name') || 'Employee Name'}:</td>
                                            <td style="padding: 8px;"><strong>${data.employee_name || 'N/A'}</strong></td>
                                        </tr>
                                        <tr style="border-bottom: 1px solid #dee2e6;">
                                            <td style="padding: 8px; font-weight: 600; color: #666;"><i class="fa fa-hashtag" style="margin-right: 5px; color: #667eea;"></i> ${__('emp_id') || 'Employee ID'}:</td>
                                            <td style="padding: 8px;">${data.emp_id || 'N/A'}</td>
                                        </tr>
                                        <tr style="border-bottom: 1px solid #dee2e6;">
                                            <td style="padding: 8px; font-weight: 600; color: #666;"><i class="fa fa-passport" style="margin-right: 5px; color: #667eea;"></i> ${__('passport_no') || 'Passport No'}:</td>
                                            <td style="padding: 8px;"><strong>${data.passport_number || 'N/A'}</strong></td>
                                        </tr>
                                        <tr style="border-bottom: 1px solid #dee2e6;">
                                            <td style="padding: 8px; font-weight: 600; color: #666;"><i class="fa fa-calendar-times" style="margin-right: 5px; color: #667eea;"></i> ${__('passport_expiry') || 'Passport Expiry'}:</td>
                                            <td style="padding: 8px;">${data.passport_exp || 'N/A'}</td>
                                        </tr>
                                    </table>
                                </div>

                                <div style="background: #fff9e6; padding: 15px; border-radius: 8px; margin-bottom: 15px; border-left: 4px solid #ffc107;">
                                    <h5 style="color: #856404; margin-top: 0; border-bottom: 2px solid #ffc107; padding-bottom: 8px;">
                                        <i class="fa fa-plane"></i> ${__('travel_details') || 'Travel Details'}
                                    </h5>
                                    <table style="width: 100%; font-size: 14px;">
                                        <tr style="border-bottom: 1px solid #dee2e6;">
                                            <td style="padding: 8px; font-weight: 600; color: #666; width: 45%;"><i class="fa fa-map-marker-alt" style="margin-right: 5px; color: #ffc107;"></i> ${__('departure_to') || 'Departure To'}:</td>
                                            <td style="padding: 8px;"><strong>${data.country_name || 'N/A'}</strong></td>
                                        </tr>
                                        <tr style="border-bottom: 1px solid #dee2e6;">
                                            <td style="padding: 8px; font-weight: 600; color: #666;"><i class="fa fa-plane-departure" style="margin-right: 5px; color: #ffc107;"></i> ${__('departure_date') || 'Departure Date'}:</td>
                                            <td style="padding: 8px;"><strong>${data.departure_date || 'N/A'}</strong></td>
                                        </tr>
                                        <tr style="border-bottom: 1px solid #dee2e6;">
                                            <td style="padding: 8px; font-weight: 600; color: #666;"><i class="fa fa-plane-arrival" style="margin-right: 5px; color: #ffc107;"></i> ${__('arrival_date') || 'Arrival Date'}:</td>
                                            <td style="padding: 8px;"><strong>${data.arrival_date || 'N/A'}</strong></td>
                                        </tr>
                                        <tr style="border-bottom: 1px solid #dee2e6;">
                                            <td style="padding: 8px; font-weight: 600; color: #666;"><i class="fa fa-calendar-alt" style="margin-right: 5px; color: #ffc107;"></i> ${__('vacation_start') || 'Vacation Start'}:</td>
                                            <td style="padding: 8px;">${data.start_date || 'N/A'}</td>
                                        </tr>
                                        <tr>
                                            <td style="padding: 8px; font-weight: 600; color: #666;"><i class="fa fa-calendar-check" style="margin-right: 5px; color: #ffc107;"></i> ${__('vacation_return') || 'Vacation Return'}:</td>
                                            <td style="padding: 8px;">${data.return_date || 'N/A'}</td>
                                        </tr>
                                    </table>
                                </div>

                                <div style="background: #e7f3ff; padding: 15px; border-radius: 8px; border-left: 4px solid #3085d6;">
                                    <p style="margin: 0; font-size: 13px; color: #004085;">
                                        <i class="fa fa-info-circle" style="margin-right: 5px;"></i>
                                        <strong>${__('reference_number') || 'Reference Number'}:</strong> ${data.request_inv_no || 'N/A'}
                                    </p>
                                </div>

                                <div style="background: #fff3cd; padding: 12px; border-radius: 6px; margin-top: 15px; border: 1px solid #ffc107;">
                                    <p style="margin: 0; font-size: 13px; color: #856404;">
                                        <i class="fa fa-exclamation-triangle" style="margin-right: 5px;"></i>
                                        <strong>${__('important') || 'Important'}:</strong> ${__('verify_information_notice') || 'Please verify all information is correct. If any information is incorrect, please contact HR for corrections before sending this email.'}
                                    </p>
                                </div>

                                <div style="background: #f8d7da; padding: 12px; border-radius: 6px; margin-top: 10px; border: 1px solid #f5c2c7;">
                                    <p style="margin: 0; font-size: 13px; color: #842029;">
                                        <i class="fa fa-ban" style="margin-right: 5px;"></i>
                                        <strong>${__('note') || 'Note'}:</strong> ${__('email_sent_once_warning') || 'This email can only be sent once. After sending, the button will be hidden.'}
                                    </p>
                                </div>

                                ${passportSectionHtml}
                            </div>
                        `,
                            icon: 'question',
                            showCancelButton: true,
                            confirmButtonText: '<i class="fa fa-check-circle"></i> ' + (__('confirm_and_send') || 'Confirm & Send Email'),
                            cancelButtonText: '<i class="fa fa-times"></i> ' + (__('cancel') || 'Cancel'),
                            confirmButtonColor: '#28a745',
                            cancelButtonColor: '#dc3545',
                            allowOutsideClick: false,
                            width: '650px',
                            customClass: {
                                confirmButton: 'btn btn-success btn-lg',
                                cancelButton: 'btn btn-danger btn-lg'
                            },
                            didOpen: () => {
                                const confirmBtn = Swal.getConfirmButton();
                                const passportFileInput = document.getElementById('passport_file');
                                const replaceBtn = document.getElementById('replace-passport-btn');
                                const openBtn = document.getElementById('open-passport-btn');
                                const replaceInput = document.getElementById('replace_passport_input');
                                const replaceHint = document.getElementById('replace-passport-hint');
                                const hasExistingPassportDoc = !!data.passport_doc_url;
                                if (openBtn) {
                                    openBtn.addEventListener('click', function() {
                                        window.open(data.passport_doc_url, '_blank');
                                    });
                                }

                                // Function to update confirm button state
                                const updateConfirmButton = () => {
                                    if (confirmBtn) {
                                        if (invalidPassport) {
                                            confirmBtn.disabled = true;
                                            confirmBtn.setAttribute('title', __('passport_fix_required') || 'Fix passport number/expiry before sending');
                                        } else if (!hasExistingPassportDoc && (!passportFileInput || !passportFileInput.files || passportFileInput.files.length === 0)) {
                                            confirmBtn.disabled = true;
                                            confirmBtn.setAttribute('title', __('passport_file_required') || 'Please attach passport copy before sending');
                                        } else {
                                            confirmBtn.disabled = false;
                                            confirmBtn.removeAttribute('title');
                                        }
                                    }
                                };

                                // Initial state - disable button
                                updateConfirmButton();

                                // Add event listener to enable button when file is selected
                                if (passportFileInput) {
                                    passportFileInput.addEventListener('change', function() {
                                        updateConfirmButton();
                                    });
                                }
                                if (replaceBtn && replaceInput) {
                                    replaceBtn.addEventListener('click', () => {
                                        replaceInput.click();
                                    });
                                    replaceInput.addEventListener('change', () => {
                                        if (replaceInput.files && replaceInput.files.length > 0) {
                                            if (replaceHint) replaceHint.style.display = 'block';
                                            // Upload replacement immediately
                                            const f = replaceInput.files[0];
                                            const fd = new FormData();
                                            fd.append('ajaxType', 'replacePassportDoc');
                                            fd.append('emp_id', data.emp_id);
                                            fd.append('passport_file', f);
                                            Swal.showLoading();
                                            $.ajax({
                                                url: './includes/ajaxFile/ajaxVacation.php',
                                                type: 'POST',
                                                dataType: 'JSON',
                                                data: fd,
                                                processData: false,
                                                contentType: false,
                                                success: function(r) {
                                                    Swal.hideLoading();
                                                    if (r.type === 'success') {
                                                        // Update preview
                                                        const container = document.getElementById('existing-passport-doc');
                                                        if (container) {
                                                            const isImage = r.passport_doc_is_image;
                                                            container.innerHTML = `
                                                            <h6 style="margin:0 0 10px; color:#0b5eb7;"><i class='fa fa-file'></i> ${__('current_passport_copy') || 'Current Passport Copy'}</h6>
                                                            <div style="margin-bottom:10px;">${isImage ? `<img src='${r.passport_doc_url}' style='max-width:100%; max-height:180px; border:1px solid #ddd; border-radius:6px;'/>` : `<a href='${r.passport_doc_url}' target='_blank' class='btn btn-sm btn-outline-primary'><i class='fa fa-file'></i> View Passport (${(r.passport_doc_ext||'').toUpperCase()})</a>`}</div>
                                                            <button type='button' id='replace-passport-btn' class='btn btn-sm btn-warning'><i class='fa fa-sync-alt'></i> ${__('replace_passport_copy') || 'Replace Passport Copy'}</button>
                                                            <input type='file' id='replace_passport_input' accept='.pdf,.jpg,.jpeg,.png' style='display:none;' />
                                                            <small class='form-text text-muted'><i class='fa fa-info-circle'></i> ${__('upload_new_passport_hint') || 'Select a new file to replace again if needed.'}</small>
                                                        `;
                                                        }
                                                        updateConfirmButton();
                                                    } else {
                                                        Swal.fire(__('error') || 'Error', r.message || 'Failed replacing passport copy.', 'error');
                                                    }
                                                },
                                                error: function() {
                                                    Swal.hideLoading();
                                                    Swal.fire(__('error') || 'Error', __('error_replacing_passport') || 'Could not replace passport copy.', 'error');
                                                }
                                            });
                                        }
                                    });
                                }
                            }
                        }).then((result) => {
                            if (result.isConfirmed) {
                                // Validate passport file is selected
                                const passportFile = document.getElementById('passport_file');
                                const hasExistingPassportDoc = !!data.passport_doc_url;
                                if (!hasExistingPassportDoc && (!passportFile || !passportFile.files || passportFile.files.length === 0)) {
                                    Swal.fire({
                                        title: __('validation_error') || 'Validation Error',
                                        text: __('passport_file_required') || 'Please attach a passport copy before sending the email.',
                                        icon: 'error'
                                    });
                                    return;
                                }

                                // Validate file size (max 5MB)
                                const maxSize = 5 * 1024 * 1024; // 5MB in bytes
                                if (passportFile && passportFile.files && passportFile.files.length > 0 && passportFile.files[0].size > maxSize) {
                                    Swal.fire({
                                        title: __('file_too_large') || 'File Too Large',
                                        text: __('passport_file_size_limit') || 'Passport file must be less than 5MB. Please compress or select a smaller file.',
                                        icon: 'error'
                                    });
                                    return;
                                }

                                // Show loading
                                Swal.fire({
                                    title: __('sending') || 'Sending...',
                                    html: __('please_wait_sending_email') || 'Please wait while we send the email to the traveling company.',
                                    allowOutsideClick: false,
                                    allowEscapeKey: false,
                                    didOpen: () => {
                                        Swal.showLoading();
                                    }
                                });

                                // Create FormData to include file upload
                                const formData = new FormData();
                                formData.append('ajaxType', 'sendTravelEmail');
                                formData.append('vacation_id', vacationId);
                                if (passportFile && passportFile.files && passportFile.files.length > 0) {
                                    formData.append('passport_file', passportFile.files[0]);
                                }

                                $.ajax({
                                        url: './includes/ajaxFile/ajaxVacation.php',
                                        type: 'POST',
                                        dataType: 'JSON',
                                        data: formData,
                                        processData: false,
                                        contentType: false,
                                    })
                                    .done(function(response) {
                                        Swal.fire({
                                            title: response.title || (__('success') || 'Success'),
                                            text: response.message,
                                            icon: response.type || 'success',
                                            allowOutsideClick: false
                                        }).then(function(isConfirm) {
                                            if (isConfirm) {
                                                // Hide the button after successful send
                                                $('#travel-email-btn-' + vacationId).fadeOut();
                                                location.reload();
                                            }
                                        });
                                    })
                                    .fail(function(jqXHR, textStatus, errorThrown) {
                                        console.error('AJAX Error:', textStatus, errorThrown);
                                        Swal.fire(
                                            __('error') || 'Error',
                                            __('error_sending_travel_email') || 'An error occurred while sending the travel email. Please try again.',
                                            'error'
                                        );
                                    });
                            }
                        });
                    } else {
                        Swal.fire(
                            __('error') || 'Error',
                            response.message || (__('error_fetching_details') || 'Could not fetch traveler details.'),
                            'error'
                        );
                    }
                })
                .fail(function(jqXHR, textStatus, errorThrown) {
                    console.error('AJAX Error:', textStatus, errorThrown);
                    Swal.fire(
                        __('error') || 'Error',
                        __('error_loading_traveler_info') || 'An error occurred while loading traveler information.',
                        'error'
                    );
                });
        }

        /**
         * =====================================================================
         * == PROCESS PAYMENT FUNCTION (HR PAYROLL ONLY)
         * =====================================================================
         * Marks a vacation payment as "paid" in the database
         * After payment is processed, HR Payroll can then approve the vacation
         */
        function processPayment(vacationId, requestInvNo, employeeName) {
            const confirmText = `${__('process_payment_confirm') || 'Are you ready to process payment for'} ${employeeName}?`;

            Swal.fire({
                title: __('process_payment') || 'Process Payment',
                html: `
                    <div style="text-align: left; padding: 20px; background: #f8f9fa; border-radius: 8px;">
                        <p><strong>Request:</strong> ${requestInvNo}</p>
                        <p><strong>Employee:</strong> ${employeeName}</p>
                        <div style="margin-top: 15px; padding: 12px; background: white; border-radius: 6px; border-left: 4px solid #ffc107;">
                            <i class="fa fa-info-circle text-warning"></i> 
                            <strong>${__('note')}:</strong> 
                            ${__('payment_process_note') || 'Processing payment will mark this vacation request as "paid". You will then be able to approve the vacation request.'}
                        </div>
                    </div>
                `,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#28a745',
                confirmButtonText: '<i class="fa fa-check"></i> ' + (__('process_payment') || 'Process Payment'),
                cancelButtonColor: '#dc3545',
                cancelButtonText: '<i class="fa fa-times"></i> ' + (__('cancel') || 'Cancel'),
                allowOutsideClick: false
            }).then((result) => {
                if (result.isConfirmed) {
                    // Show loading
                    Swal.fire({
                        title: __('processing') || 'Processing...',
                        html: __('please_wait_processing') || 'Please wait...',
                        allowOutsideClick: false,
                        allowEscapeKey: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });

                    // Send AJAX request
                    $.ajax({
                        url: './includes/ajaxFile/ajaxVacation.php',
                        type: 'POST',
                        dataType: 'JSON',
                        data: {
                            ajaxType: 'processPayment',
                            vacation_id: vacationId,
                            request_inv_no: requestInvNo
                        },
                        success: function(response) {
                            Swal.fire({
                                title: response.title || __('success') || 'Success',
                                text: response.message,
                                icon: response.type || 'success',
                                allowOutsideClick: false
                            }).then(function() {
                                location.reload();
                            });
                        },
                        error: function(jqXHR, textStatus, errorThrown) {
                            console.error('Payment processing error:', errorThrown);
                            let errorMsg = __('error_processing_payment') || 'An error occurred';
                            if (jqXHR.responseJSON && jqXHR.responseJSON.message) {
                                errorMsg = jqXHR.responseJSON.message;
                            }
                            Swal.fire(
                                __('error') || 'Error',
                                errorMsg,
                                'error'
                            );
                        }
                    });
                }
            });
        }

        /**
         * =====================================================================
         * == APPROVE VACATION AFTER PAYMENT (HR PAYROLL ONLY)
         * =====================================================================
         * Opens the approval modal for HR Payroll to approve the vacation
         * Only accessible AFTER payment has been processed
         */
        function approveVacationPayment(vacationId, requestInvNo, employeeName) {
            // Get vacation details to determine if it's annual and get dates
            $.ajax({
                url: './includes/ajaxFile/ajaxVacation.php',
                type: 'POST',
                dataType: 'JSON',
                data: {
                    ajaxType: 'getVacationDetails',
                    vacation_id: vacationId
                },
                success: function(vacRes) {
                    if (vacRes.status === 200) {
                        // Call the regular approveRequest function with HR Payroll as the user
                        approveRequest(
                            vacationId,
                            vacRes.emp_id,
                            employeeName,
                            vacRes.vac_type,
                            vacRes.start_date,
                            vacRes.return_date,
                            vacRes.vacdays,
                            vacRes.current_approval_level || 3, // Final level
                            'hr_payroll', // User role
                            vacRes.supervisor_id ? true : false, // Has supervisor
                            false // Not simple leave (is annual vacation)
                        );
                    } else {
                        Swal.fire(__('error') || 'Error', 'Could not load vacation details', 'error');
                    }
                },
                error: function() {
                    Swal.fire(__('error') || 'Error', __('error_loading_vacation_details') || 'Could not load vacation details', 'error');
                }
            });
        }
    </script>
</body>

</html>