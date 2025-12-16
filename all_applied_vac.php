<?php

require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/session_check.php';
// $user_type, $empid, $user_dept, $is_system_admin, $isHR, $isDeptHr are available from session_check.php

// Restrict access: Employees cannot view this detailed report page
if (isset($isEmployee) && $isEmployee === true) {
    header("Location: ./profile.php");
    exit();
}

// --- Get Request Type ID for 'vacation_request' ---
$type_query = mysqli_query($conDB, "SELECT `id` FROM `approval_request_types` WHERE `type_name` = 'vacation_request' LIMIT 1");
if (!$type_query || mysqli_num_rows($type_query) == 0) {
    die("CRITICAL ERROR: 'vacation_request' type not found in `approval_request_types` table.");
}
$request_type_id = (int)mysqli_fetch_assoc($type_query)['id'];


// --- Search, Pagination & Filtering Logic ---

$all_statuses = [
    'my_pending' => __('my_pending_queue'),
    'my_team' => (function_exists('__') ? __('my_team_requests') : 'My Team') ,
    'my_dept' => __('my_department_requests'),
    'pending_approval' => __('all_pending'),
    'pending_payment' => __('approved_pending_payment'),
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
    $join_sql .= " JOIN `request_approvers` ra ON ra.request_inv_no = v.request_inv_no AND ra.request_type_id = ? ";
    $params[] = $request_type_id;
    $types .= "i";
    
    $where_clauses[] = "ra.approver_id = ?";
    $params[] = $empid; // $empid is from session_check.php
    $types .= "i";

    $where_clauses[] = "ra.status = 'pending'";
    
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
    $where_clauses[] = "(e.dept = ? OR EXISTS (SELECT 1 FROM request_approvers ra_any WHERE ra_any.request_inv_no = v.request_inv_no AND ra_any.request_type_id = ? AND ra_any.approver_id = ?))";
    array_push($params, $user_dept, $request_type_id, $empid);
    $types .= "iii";
    $dept_filter_applied = true;
}


$where_sql = "";
if (!empty($where_clauses)) {
    $where_sql = " WHERE " . implode(" AND ", $where_clauses);
}

// Main query to select *which* vacations to show (for count and main data)
$base_query = "FROM emp_vacation v 
               JOIN employees e ON v.emp_id = e.emp_id 
               $join_sql 
               $where_sql";

$count_sql = "SELECT COUNT(DISTINCT v.id) as total " . $base_query;
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
        supervisor_emp.emptype as supervisor_type
    FROM emp_vacation v 
    JOIN employees e ON v.emp_id = e.emp_id
    LEFT JOIN emp_vacation_balance b ON v.id = b.vac_id
    
    -- This JOIN finds the current pending approver
    LEFT JOIN request_approvers ra_pending ON ra_pending.request_inv_no = v.request_inv_no 
         AND ra_pending.request_type_id = ? AND ra_pending.status = 'pending'
    LEFT JOIN employees approver_emp ON ra_pending.approver_id = approver_emp.emp_id
    
    -- This JOIN gets the supervisor information
    LEFT JOIN employees supervisor_emp ON e.supervisor_id = supervisor_emp.emp_id
    
    -- This JOIN is for the 'my_pending' filter
    $join_sql
    
    $where_sql";
    
    $sql .= " GROUP BY v.id ORDER BY v.created_at DESC"; // Group by v.id to avoid duplicates

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
        $unfiltered_sql = "SELECT COUNT(v.id) as total FROM emp_vacation v JOIN employees e ON v.emp_id = e.emp_id WHERE (e.dept = ? OR EXISTS (SELECT 1 FROM request_approvers ra_any WHERE ra_any.request_inv_no = v.request_inv_no AND ra_any.request_type_id = ? AND ra_any.approver_id = ?)) AND v.request_inv_no NOT LIKE 'LEGACY-%'";
    } else {
        $unfiltered_sql = "SELECT COUNT(v.id) as total FROM emp_vacation v JOIN employees e ON v.emp_id = e.emp_id WHERE (e.dept = ? OR EXISTS (SELECT 1 FROM request_approvers ra_any WHERE ra_any.request_inv_no = v.request_inv_no AND ra_any.request_type_id = ? AND ra_any.approver_id = ?))";
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

?>
    <!doctype html>
    <html lang="<?= $current_lang ?? 'en' ?>" <?= ($is_rtl ?? false) ? 'dir="rtl"' : '' ?>>

    <head>
        <meta charset="utf-8" />
        <title><?= $site_title ?? 'Vacation System' ?> - <?=__('all_vacation_requests')?></title>
        <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
        <meta content="Anees Afzal" name="author" />
        <meta http-equiv="X-UA-Compatible" content="IE=edge" />
        <link rel="shortcut icon" href="<?=get_setting($conDB, 'favicon')?>">
        <link href="./plugins/custombox/css/custombox.min.css" rel="stylesheet">
        <!-- Select2 -->
        <link href="./plugins/select2/css/select2.min.css" rel="stylesheet" type="text/css" />
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
            .detail-item { display: flex; align-items: center; /*margin-bottom: 1rem;*/ font-size: 1.09em; }
            .detail-item i.fad { color: #4a90e2; margin-right: 15px; width: 20px; text-align: center; }
            .detail-item strong { color: #8a94a6; min-width: 130px; display: inline-block; }
            .request-card .card-footer { background-color: #fafbff; border-top: 1px solid #eef; overflow: visible; }
            /* Footer actions: responsive grid to avoid overflow and keep symmetry */
            .vac-actions {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
                gap: .5rem;
            }
            .vac-actions .btn { white-space: normal; line-height: 1.2; }
            .vac-actions .btn i { margin-inline-end: .35rem; }
            /* Keep block buttons filling their grid cell */
            .vac-actions .btn.btn-block { display: inline-flex; width: 100%; justify-content: center; align-items: center; }
            .no-requests { padding: 3rem; background: #fff; border-radius: 15px; box-shadow: 0 10px 30px rgba(0, 0, 0, 0.07); }
			.btn-block + .btn-block{ margin-top: 0rem !important; }
            
            /* --- NEW STYLES FOR APPROVER LIST --- */
            /* Ensure dropdowns are not hidden behind adjacent cards */
            .request-card { position: relative; }
            .request-card:hover, .request-card:focus-within { z-index: 50; }
            .request-card .dropdown-menu { z-index: 2000; }
            .swal-approval-chain .select2-container { width: 100% !important; }
            .swal-approval-chain label, .swal-payment-details label { font-weight: 600; margin-top: 10px; }
            .swal-approval-chain-builder { display: flex; align-items: center; margin-bottom: 10px; }
            .swal-approval-chain-builder .select2-container { flex-grow: 1; }
            .swal-approval-chain-builder #add-approver-btn-new {
                margin-left: 10px;
                flex-shrink: 0;
                height: 38px; /* Match Select2 height */
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
            #approver-chain-list li:last-child { border-bottom: none; }
            #approver-chain-list .remove-approver-btn {
                background: none;
                border: none;
                color: #dc3545;
                font-weight: bold;
                font-size: 1.2em;
                cursor: pointer;
                padding: 0 5px;
            }
            #approver-chain-list .remove-approver-btn:hover { color: #a71d2a; }
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
                                    <h4 class="header-title m-t-0 m-b-30"><?=__('vacation_approval_center')?></h4>

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
                                        <?php 
                                            // Replace placeholder {0} in translation with the total count
                                            $showing_text = str_replace('{0}', (string)(int)$total_items, __('showing_requests'));
                                        ?>
                                        <h4 class="mb-0 text-muted"><?=$showing_text?></h4>
                                        <span class="badge badge-light p-2"><?=__('total_found')?>: <?=$total_items; ?></span>
                                    </div>

                                    <?php if (!empty($requests)): ?>
                                        <div class="row">
                                            <?php foreach ($requests as $req): ?>
												<div class="col-lg-4 col-md-6 mb-4">
													<div class="card request-card h-100">
														<div class="card-header">
															<?=translate_name($req['employee_name'], $current_lang ?? 'en'); ?>
															<span class="float-right"><?=__('emp_id')?>: <?=htmlspecialchars($req['emp_id']); ?></span>
														</div>
														<div class="card-body">
															<div class="detail-item"><i class="fad fa-paper-plane duotone-info"></i><strong><?=__('applied')?>:</strong> <?=htmlspecialchars(date('d M Y', strtotime($req['created_at']))); ?></div>
															<div class="detail-item"><i class="fad fa-suitcase-rolling duotone-info"></i><strong><?=__('type')?>:</strong> <?=htmlspecialchars(parseName($req['vac_type'], 'FIRST')." | ".$req['fly_type_translated']); ?></div>
															<div class="detail-item"><i class="fad fa-calendar-alt duotone-info"></i><strong><?=__('start')?>:</strong> <?=htmlspecialchars($req['start_date'] ?? 'N/A'); ?></div>
															<div class="detail-item"><i class="fad fa-calendar-check duotone-info"></i><strong><?=__('return')?>:</strong> <?=htmlspecialchars($req['return_date'] ?? 'N/A'); ?></div>
                                                            <?php if (!empty($req['departure_date']) && $req['vac_type'] === 'Fly' && $req['fly_type'] === 'annual'): ?>
															<div class="detail-item"><i class="fad fa-plane-departure duotone-info"></i><strong><?=__('departure_date')?>:</strong> <?=htmlspecialchars($req['departure_date']); ?></div>
                                                            <?php endif; ?>
                                                            <?php if (!empty($req['arrival_date']) && $req['vac_type'] === 'Fly' && $req['fly_type'] === 'annual'): ?>
															<div class="detail-item"><i class="fad fa-plane-arrival duotone-info"></i><strong><?=__('arrival_date')?>:</strong> <?=htmlspecialchars($req['arrival_date']); ?></div>
                                                            <?php endif; ?>
															<div class="detail-item"><i class="fad fa-sun duotone-info"></i><strong><?=__('days')?>:</strong> <?=htmlspecialchars($req['vacdays']); ?></div>
                                                            
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
                                                                    <strong><?=__('attachments')?> (<?= count($attachments) ?>):</strong> 
                                                                    <div style="margin-inline-start: 20px; margin-top: 5px; direction: inherit;">
                                                                        <?php foreach ($attachments as $index => $attachment): ?>
                                                                            <a href="<?=htmlspecialchars($attachment); ?>" target="_blank" class="font-weight-bold text-info" style="display: block; margin-bottom: 5px; direction: ltr; text-align: start;">
                                                                                <i class="fa fa-file-<?= pathinfo($attachment, PATHINFO_EXTENSION) === 'pdf' ? 'pdf' : 'image' ?>"></i>
                                                                                <?=__('document')?> <?= $index + 1 ?>
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
                                                                    if ($req['current_status'] == 'approved' && 
                                                                        $req['vac_type'] == 'Fly' && 
                                                                        $req['fly_type'] == 'annual') {
                                                                        // Check all payment fields are properly filled
                                                                        $has_departure = !empty($req['departure_date']);
                                                                        $has_arrival = !empty($req['arrival_date']);
                                                                        $has_ticket_pay = !empty($req['ticket_pay']) && (float)$req['ticket_pay'] > 0;
                                                                        $has_permit_fee = !empty($req['permit_fee']) && (float)$req['permit_fee'] > 0;
                                                                        
                                                                        // Payment is pending if ANY field is missing or zero
                                                                        if (!$has_departure || !$has_arrival || !$has_ticket_pay || !$has_permit_fee) {
                                                                            $is_payment_pending = true;
                                                                        }
                                                                    }
																?>
																<i class="fad fa-info-circle duotone-info"></i>
																<strong><?=__('status')?>:</strong> <span class="badge badge-<?=$badge_class; ?> p-2"><?=$status_icon." ".htmlspecialchars($status_text); ?></span>
                                                                <?php if ($is_payment_pending): ?>
                                                                    <span class="badge badge-warning p-2 ml-1">
                                                                        <i class="fa fa-credit-card"></i> <?=__('pending_payment')?>
                                                                    </span>
                                                                <?php endif; ?>
                                                            </div>
                                                            
                                                            <?php if ($req['current_status'] == 'approved' && isset($req['remaining_balance'])): ?>
                                                                <hr>
                                                                <div class="detail-item"><i class="fad fa-wallet duotone-success"></i><strong><?=__('remaining')?>:</strong> <?=htmlspecialchars(number_format($req['remaining_balance'], 2)); ?> <?=__('days')?></div>
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
                                                            $payments_entered = (!empty($req['ticket_pay']) && (float)$req['ticket_pay'] > 0) || (!empty($req['permit_fee']) && (float)$req['permit_fee'] > 0);
                                                            if (
                                                                $req['vac_type'] == 'Fly' &&
                                                                $req['fly_type'] == 'annual' &&
                                                                $req['current_status'] == 'approved' &&
                                                                !$payments_entered &&
                                                                // ($isHR || $is_system_admin || $isDeptHr || $isGR_Officer)
                                                                ($isHR || $is_system_admin || $isDeptHr || $isHR_Payroll)
                                                            ) { $show_payment_button = true; }

                                                            $show_travel_email_button = false;
                                                            if (
                                                                $req['vac_type'] == 'Fly' &&
                                                                $req['fly_type'] == 'annual' &&
                                                                $req['current_status'] == 'approved' &&
                                                                !empty($req['departure_date']) &&
                                                                !empty($req['arrival_date']) &&
                                                                ($req['travel_email_sent'] == 0 || empty($req['travel_email_sent'])) &&
                                                                // ($isHR || $is_system_admin || $isGR_Officer)
                                                                ($isHR || $is_system_admin)
                                                            ) { $show_travel_email_button = true; }
                                                        ?>
                                                        <div class="card-footer d-flex justify-content-between align-items-center" style="gap: 0.5rem;">
                                                            <a href="vacation_report_details.php?id=<?=$req['id']; ?>&emp_id=<?=$req['emp_id']; ?>" target="_blank" class="btn btn-info btn-block waves-effect">
                                                                <i class="fa fa-eye"></i> <?=__('view')?>
                                                            </a>
                                                            <div class="btn-group flex-fill">
                                                                <button type="button" class="btn btn-secondary dropdown-toggle btn-block waves-effect" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                                                    <?=__('actions')?> <span class="caret"></span>
                                                                </button>
                                                                <div class="dropdown-menu dropdown-menu-right">
                                                                    <a class="dropdown-item" href="vacation_status_history.php?request_inv_no=<?= urlencode($req['request_inv_no']); ?>" target="_blank">
                                                                        <i class="fa fa-history"></i> <?=__('history')?>
                                                                    </a>
                                                                    <?php if ($is_pending_with_me): ?>
                                                                        <div class="dropdown-divider"></div>
                                                                        <a class="dropdown-item" href="javascript:void(0);" onclick="approveRequest(<?=$req['id']; ?>, '<?=$employee_id_js; ?>', '<?=$employee_name_js; ?>', '<?=$vac_type_js; ?>', '<?=$start_date_js; ?>', '<?=$end_date_js; ?>', '<?=$days_js; ?>', <?=$current_level_js; ?>, '<?=$user_role_js; ?>', <?=$has_supervisor_js; ?>, <?=$is_simple_leave_js; ?>)">
                                                                            <i class="fa fa-check text-success"></i> <?=__('approve')?>
                                                                        </a>
                                                                        <a class="dropdown-item" href="javascript:void(0);" onclick="rejectVacationRequest(<?=$req['id']; ?>, '<?=$employee_name_js; ?>', '<?=$vac_type_js; ?>', '<?=$start_date_js; ?>', '<?=$end_date_js; ?>', '<?=$days_js; ?>')">
                                                                            <i class="fa fa-times text-danger"></i> <?=__('reject')?>
                                                                        </a>
                                                                    <?php endif; ?>
                                                                    <?php if ($show_payment_button): ?>
                                                                        <div class="dropdown-divider"></div>
                                                                        <a class="dropdown-item" href="javascript:void(0);" onclick="addVacationPayments(<?=$req['id']; ?>, '<?=htmlspecialchars(addslashes(parseName($req['employee_name'])), ENT_QUOTES); ?>','<?= $req['ticket_pay'] ?? '0.00'; ?>','<?= $req['permit_fee'] ?? '0.00'; ?>')">
                                                                            <i class="fa fa-credit-card text-warning"></i> <?=__('payments')?>
                                                                        </a>
                                                                    <?php endif; ?>
                                                                    <?php if ($show_travel_email_button): ?>
                                                                        <a class="dropdown-item" id="travel-email-btn-<?=$req['id']; ?>" href="javascript:void(0);" onclick="sendTravelEmail(<?=$req['id']; ?>, '<?=htmlspecialchars(addslashes(parseName($req['employee_name'])), ENT_QUOTES); ?>')">
                                                                            <i class="fa fa-paper-plane text-primary"></i> <?=__('send_travel_email')?>
                                                                        </a>
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
                                                    <h2><?=__('no_requests_found')?></h2>
                                                    <?php 
                                                    if (($current_filter && $current_filter !== 'all' && $current_filter !== 'none') || !empty($search_term)): ?>
                                                        <p class="text-muted"><?=__('no_requests_matching_filters_vac')?></p>
                                                    <?php else: ?>
                                                        <p class="text-muted"><?=__('no_requests_to_display')?></p>
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
            document.getElementById('searchFilter').addEventListener('keypress', function (e) {
                if (e.key === 'Enter') { applyFilters(); }
            });

        /**
         * =================================================================
         * == APPROVE REQUEST FUNCTION (Updated for Supervisor Chain)
         * =================================================================
         * This function now handles BOTH:
         * 1. Simple Leave Requests (with supervisor → HR BP chain)
         * 2. Annual Vacation (with HR Assistant chain)
         * 
         * @param {boolean} hasSupervisor - Does the employee have a supervisor assigned?
         * @param {boolean} isSimpleLeave - Is this a simple leave (not annual vacation)?
         */
        function approveRequest(vacationId, employeeId, employeeName, vacType, startDate, endDate, totalDays, currentLevel, userRole, hasSupervisor, isSimpleLeave) {
            // First, check if this approver should see asset clearance modal
            // This is only relevant for IT/Admin/Transport managers
            const potentialAssetClearanceRole = userRole && (
                userRole.toLowerCase().includes('it') || 
                userRole.toLowerCase().includes('admin') || 
                userRole.toLowerCase().includes('transport')
            );
            
            if (potentialAssetClearanceRole) {
                // Check if employee has assets relevant to this approver's department
                $.ajax({
                    url: './includes/ajaxFile/ajaxEmployee.php',
                    type: 'POST',
                    dataType: 'json',
                    async: false, // Wait for response before proceeding
                    data: {
                        ajaxType: 'get_assigned_assets',
                        emp_id: employeeId
                    },
                    success: function(res) {
                        if (res.status === 200 && Array.isArray(res.assets) && res.assets.length > 0) {
                            // Check if any assets belong to approver's department
                            const hasRelevantAssets = res.assets.some(function(asset) {
                                const assetName = (asset.asset_name || '').toLowerCase();
                                const userRoleLower = (userRole || '').toLowerCase();
                                
                                // IT Manager: check for laptop/computer
                                if (userRoleLower.includes('it') && (assetName.includes('laptop') || assetName.includes('computer'))) {
                                    return true;
                                }
                                // Admin Manager: check for mobile/phone/sim
                                if (userRoleLower.includes('admin') && (assetName.includes('mobile') || assetName.includes('phone') || assetName.includes('sim'))) {
                                    return true;
                                }
                                // Transport Manager: check for car/vehicle
                                if (userRoleLower.includes('transport') && (assetName.includes('car') || assetName.includes('vehicle'))) {
                                    return true;
                                }
                                return false;
                            });
                            
                            if (!hasRelevantAssets) {
                                // No relevant assets - proceed with normal approval (no asset clearance modal)
                                proceedWithApproval(vacationId, employeeId, employeeName, vacType, startDate, endDate, totalDays, currentLevel, userRole, hasSupervisor, isSimpleLeave, false);
                                return;
                            } else {
                                // Has relevant assets - show asset clearance modal
                                proceedWithApproval(vacationId, employeeId, employeeName, vacType, startDate, endDate, totalDays, currentLevel, userRole, hasSupervisor, isSimpleLeave, true);
                                return;
                            }
                        } else {
                            // No assets assigned - proceed with normal approval
                            proceedWithApproval(vacationId, employeeId, employeeName, vacType, startDate, endDate, totalDays, currentLevel, userRole, hasSupervisor, isSimpleLeave, false);
                            return;
                        }
                    },
                    error: function() {
                        // On error, default to normal approval
                        proceedWithApproval(vacationId, employeeId, employeeName, vacType, startDate, endDate, totalDays, currentLevel, userRole, hasSupervisor, isSimpleLeave, false);
                    }
                });
            } else {
                // Not an asset clearance role - proceed with normal approval
                proceedWithApproval(vacationId, employeeId, employeeName, vacType, startDate, endDate, totalDays, currentLevel, userRole, hasSupervisor, isSimpleLeave, false);
            }
        }
        
        /**
         * Internal function to show the approval modal
         * Separated from approveRequest to allow asset check first
         */
        function proceedWithApproval(vacationId, employeeId, employeeName, vacType, startDate, endDate, totalDays, currentLevel, userRole, hasSupervisor, isSimpleLeave, isAssetClearanceRole) {
            // Remove request details panel from the approval modal per requirement
            let infoHtml = '';
            
            // Parse vacation date range for date picker validation
            const vacStartDate = startDate; // Format: YYYY-MM-DD
            const vacEndDate = endDate;     // Format: YYYY-MM-DD
            
            // --- Define approval flow conditions ---
            const isLevel1 = (currentLevel == 1);
            const isHR_Assistant = (userRole === 'assistant');
            const isHR_SeniorBP = (userRole === 'hr_senior_bp');
            const isHR_Payroll = (userRole === 'hr_payroll');
            // const isGR_Officer = (userRole === 'gr_officer');
            const isAnnualFly = (vacType === 'Fly');
            
            // isAssetClearanceRole is now passed as a parameter (determined by pre-check)
            
            // Determine approval flow:
            // 1. Simple Leave with Supervisor: Level 1 = Supervisor → Level 2 = HR Senior BP
            // 2. Simple Leave without Supervisor: Level 1 = Dept Manager → Level 2 = HR Senior BP
            // 3. Annual Vacation: Level 1 = Manager → Level 2 = HR Senior BP → Level 3+ = Chain
            
            let paymentHtml = '';
            let chainHtml = '';
            let hrTeamCCHtml = '';
            let assetHtml = '';
            let hrPayrollHtml = '';
            let commentHtml = ''; // Comment textarea
            let confirmButtonText = __('yes_approve_it');
            let showDenyButton = false;
            
            // --- Condition 1: Show Payment Fields? ---
            // For HR Assistant OR GR Officer approving annual vacation (Fly)
            // HR_Payroll does not enter payment during approval - can add later
            // if ((isHR_Assistant || isGR_Officer) && isAnnualFly) {
            if ((isHR_Assistant) && isAnnualFly) {
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

            // --- Condition 2: Show HR Payroll Fields? ---
            // For HR Payroll: Add overtime and deduction fields
            if (isHR_Payroll) {
                hrPayrollHtml = `
                    <div class="swal-payroll-details text-left mt-3">
                        <hr>
                        <h6 class="text-primary mb-3"><i class="fa fa-calculator"></i> ${__('payroll_adjustments') || 'Payroll Adjustments'}</h6>
                        <input type="hidden" id="employee_salary" value="0">
                        <input type="hidden" id="employee_basic_salary" value="0">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="swal_overtime_hours" class="font-weight-bold">
                                        <i class="fa fa-clock"></i> ${__('overtime_hours') || 'Overtime (Hours)'} 
                                        <span class="text-muted">(${__('optional')})</span>
                                    </label>
                                    <input type="number" id="swal_overtime_hours" class="form-control payroll-calc-input" placeholder="0" step="0.5" min="0" style="width: 100%; padding: .375rem .75rem; border: 1px solid #ced4da; border-radius: .25rem;">
                                    <small class="text-success font-weight-bold" id="overtime_calc" style="display:none;">
                                        <i class="fa fa-plus-circle"></i> <span id="overtime_amount">0.00</span> SAR
                                    </small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="swal_deduction_hours" class="font-weight-bold">
                                        <i class="fa fa-minus-circle"></i> ${__('deduction_hours') || 'Deduction (Hours)'} 
                                        <span class="text-muted">(${__('optional')})</span>
                                    </label>
                                    <input type="number" id="swal_deduction_hours" class="form-control payroll-calc-input" placeholder="0" step="0.5" min="0" style="width: 100%; padding: .375rem .75rem; border: 1px solid #ced4da; border-radius: .25rem;">
                                    <small class="text-danger font-weight-bold" id="deduction_hours_calc" style="display:none;">
                                        <i class="fa fa-minus-circle"></i> <span id="deduction_hours_amount">0.00</span> SAR
                                    </small>
                                </div>
                            </div>
                        </div>
                        <div class="form-group mt-2">
                            <label for="swal_deduction_days" class="font-weight-bold">
                                <i class="fa fa-calendar-minus"></i> ${__('deduction_days') || 'Deduction (Days)'} 
                                <span class="text-muted">(${__('optional')})</span>
                            </label>
                            <input type="number" id="swal_deduction_days" class="form-control payroll-calc-input" placeholder="0" step="0.5" min="0" style="width: 100%; padding: .375rem .75rem; border: 1px solid #ced4da; border-radius: .25rem;">
                            <small class="text-danger font-weight-bold" id="deduction_days_calc" style="display:none;">
                                <i class="fa fa-minus-circle"></i> <span id="deduction_days_amount">0.00</span> SAR
                            </small>
                        </div>
                        <div class="form-group mt-3" id="payroll_summary" style="display:none; background: #f8f9fa; padding: 15px; border-radius: 8px; border-left: 4px solid #007bff;">
                            <h6 class="mb-2"><i class="fa fa-calculator"></i> ${__('payroll_summary') || 'Payroll Summary'}</h6>
                            <div style="display: flex; justify-content: space-between; margin-bottom: 5px;">
                                <span>${__('total_overtime') || 'Total Overtime'}:</span>
                                <span class="text-success font-weight-bold">+<span id="total_overtime">0.00</span> SAR</span>
                            </div>
                            <div style="display: flex; justify-content: space-between; margin-bottom: 5px;">
                                <span>${__('total_deductions') || 'Total Deductions'}:</span>
                                <span class="text-danger font-weight-bold">-<span id="total_deductions">0.00</span> SAR</span>
                            </div>
                            <hr style="margin: 10px 0;">
                            <div style="display: flex; justify-content: space-between;">
                                <span class="font-weight-bold">${__('net_adjustment') || 'Net Adjustment'}:</span>
                                <span class="font-weight-bold" id="net_adjustment_display">0.00 SAR</span>
                            </div>
                        </div>
                        <div class="form-group mt-2">
                            <label for="swal_payroll_note" class="font-weight-bold">
                                <i class="fa fa-sticky-note"></i> ${__('payroll_note') || 'Note'} 
                                <span class="text-muted">(${__('optional')})</span>
                            </label>
                            <textarea id="swal_payroll_note" class="form-control" rows="2" placeholder="${__('payroll_note_placeholder') || 'Add any notes about overtime/deductions...'}" style="width: 100%; padding: .375rem .75rem; border: 1px solid #ced4da; border-radius: .25rem;"></textarea>
                        </div>
                    </div>
                `;
            }

            // --- Condition 3: Show HR Team CC Selection? ---
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
                    chainHtml = `
                        <div class="swal-approval-chain text-left mt-3">
                            <hr>
                            <p class="text-info">
                                <i class="fa fa-info-circle"></i> 
                                ${__('approval_chain_auto_built') || 'Approval chain will be automatically determined based on assigned assets (HR Senior BP + Asset Teams).'}
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
            
            // --- Condition 3: Asset Clearance Role? ---
            // For IT/Admin/Transport managers, change button text and show deny option
            if (isAssetClearanceRole) {
                confirmButtonText = __('assets_received') || 'Assets Received';
                showDenyButton = true;
                
                // Load assigned assets for this employee
                assetHtml = `
                    <div class="swal-assigned-assets text-left mt-3">
                        <hr>
                        <h6 class="text-warning mb-3">
                            <i class="fa fa-exclamation-triangle"></i> ${__('assigned_assets') || 'Assigned Assets'}
                        </h6>
                        <div id="assets-loading-container">
                            <i class="fa fa-spinner fa-spin"></i> ${__('loading_assets') || 'Loading assigned assets...'}
                        </div>
                    </div>
                `;
            }
            
            // --- Comment/Review Textarea ---
            // Add comment field for all approvals
            commentHtml = `
                <div class="swal-comment-section text-left mt-3">
                    <hr>
                    <h6 class="text-primary mb-3">
                        <i class="fa fa-comment"></i> ${__('approval_comment') || 'Approval Comment'}
                        <span class="text-muted">(${__('optional')})</span>
                    </h6>
                    <div class="form-group">
                        <textarea id="swal_approval_comment" class="form-control" rows="4" placeholder="${__('write_comment') || 'Write your comment or review for this approval (optional)...'}" style="width: 100%; padding: .375rem .75rem; border: 1px solid #ced4da; border-radius: .25rem; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto; font-size: 14px;"></textarea>
                        <small class="form-text text-muted">
                            <span id="char-count">0</span>/5000 ${__('characters')}
                        </small>
                    </div>
                </div>
            `;

            Swal.fire({
                title: isAssetClearanceRole ? (__('asset_clearance_confirmation') || 'Asset Clearance Confirmation') : __('confirm_approval'),
                html: infoHtml + assetHtml + paymentHtml + hrPayrollHtml + hrTeamCCHtml + commentHtml + chainHtml, // Combine all HTML parts
                icon: isAssetClearanceRole ? 'question' : 'warning',
                // width: '40%', // Set modal width
                showCancelButton: true,
                showDenyButton: showDenyButton,
                confirmButtonColor: '#28a745',
                denyButtonColor: '#ffc107',
                cancelButtonColor: '#dc3535',
                confirmButtonText: confirmButtonText,
                denyButtonText: __('employee_keeps_assets') || 'Employee Keeps Assets',
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
                    
                    // --- Initialize Date Pickers for Payment Fields ---
                    // if ((isHR_Assistant || isGR_Officer) && isAnnualFly) {
                    if ((isHR_Assistant || isHR_Payroll) && isAnnualFly) {
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
                                    }).on('changeDate', function (e) {
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
                                    }).on('changeDate', function (e) {
                                        var arrivalDate = e.date;
                                        $('#swal_departure_date').datepicker('setEndDate', arrivalDate);
                                    });
                                    
                                    // Set initial values if they exist
                                    if (res.departure_date) {
                                        $('#swal_departure_date').datepicker('setDate', res.departure_date);
                                    }
                                    if (res.arrival_date) {
                                        $('#swal_arrival_date').datepicker('setDate', res.arrival_date);
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
                                }).on('changeDate', function (e) {
                                    var departureDate = e.date;
                                    $('#swal_arrival_date').datepicker('setStartDate', departureDate);
                                });
                                
                                $('#swal_arrival_date').datepicker({
                                    format: "yyyy-mm-dd",
                                    startDate: vacStartDate,
                                    endDate: vacEndDate,
                                    todayHighlight: true,
                                    autoclose: true
                                }).on('changeDate', function (e) {
                                    var arrivalDate = e.date;
                                    $('#swal_departure_date').datepicker('setEndDate', arrivalDate);
                                });
                            }
                        });
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
                                        if (res.data[i].emp_id != <?=$empid?>) { 
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
                    
                    // --- Load assigned assets for asset clearance approvers ---
                    // Load assets if the modal contains the assets loading container
                    if ($(swalModal).find('#assets-loading-container').length > 0) {
                        // Fetch assigned assets for the employee
                        $.ajax({
                            url: './includes/ajaxFile/ajaxEmployee.php',
                            type: 'POST',
                            dataType: 'json',
                            data: {
                                ajaxType: 'get_assigned_assets',
                                emp_id: employeeId,
                                check_department: userRole // Pass user role to filter relevant assets
                            },
                            success: function(res) {
                                const $container = $(swalModal).find('#assets-loading-container');
                                if (res.status === 200 && Array.isArray(res.assets) && res.assets.length > 0) {
                                    // Filter assets based on approver's department
                                    let relevantAssets = res.assets.filter(function(asset) {
                                        const assetName = (asset.asset_name || '').toLowerCase();
                                        const userRoleLower = (userRole || '').toLowerCase();
                                        
                                        // IT Manager: only laptop/computer assets
                                        if (userRoleLower.includes('it')) {
                                            return assetName.includes('laptop') || assetName.includes('computer');
                                        }
                                        // Admin Manager: only mobile/phone/sim assets
                                        if (userRoleLower.includes('admin')) {
                                            return assetName.includes('mobile') || assetName.includes('phone') || assetName.includes('sim');
                                        }
                                        // Transport Manager: only car/vehicle assets
                                        if (userRoleLower.includes('transport')) {
                                            return assetName.includes('car') || assetName.includes('vehicle');
                                        }
                                        return false; // Not a clearance role
                                    });
                                    
                                    if (relevantAssets.length > 0) {
                                        let assetsListHtml = '<ul class="list-group mb-0">';
                                        relevantAssets.forEach(function(asset) {
                                            assetsListHtml += `
                                                <li class="list-group-item d-flex justify-content-between align-items-start">
                                                    <div>
                                                        <strong>${asset.asset_name}</strong><br>
                                                        <small class="text-muted">S/N: ${asset.serial_number || 'N/A'}</small><br>
                                                        <small class="text-muted">Assigned: ${asset.assigned_date || 'N/A'}</small>
                                                    </div>
                                                    <span class="badge bg-warning text-dark">${__('pending_clearance') || 'Pending'}</span>
                                                </li>
                                            `;
                                        });
                                        assetsListHtml += '</ul>';
                                        $container.html(assetsListHtml);
                                    } else {
                                        // No relevant assets for this approver's department
                                        $container.html(`<p class="text-info mb-0"><i class="fa fa-info-circle"></i> ${__('no_assets_in_your_department') || 'No assets from your department assigned to this employee.'}</p>`);
                                    }
                                } else {
                                    $container.html(`<p class="text-muted mb-0"><i class="fa fa-info-circle"></i> ${__('no_assets_assigned') || 'No assets assigned to this employee.'}</p>`);
                                }
                            },
                            error: function() {
                                const $container = $(swalModal).find('#assets-loading-container');
                                $container.html(`<p class="text-danger mb-0"><i class="fa fa-exclamation-triangle"></i> ${__('error_loading_assets') || 'Failed to load assets.'}</p>`);
                                console.log('Failed to load assets for employee ' + employeeId);
                            }
                        });
                    }
                    
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
                                        if (res.data[i].emp_id != <?=$empid?>) { 
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
                                    // Trigger calculation after salary is loaded
                                    calculatePayrollAdjustments();
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
                            
                            console.log('Values:', {overtimeHours, deductionHours, deductionDays});
                            
                            // --- CALCULATION LOGIC MATCHING EOS FILE (emp_end_of_service.php) ---
                            
                            // DEDUCTION BASE RULE: Use contract base for deductions
                            const DEDUCTION_BASE = salary; // contractSalaryBase
                            const dailyRateDeduction = DEDUCTION_BASE / 30;
                            const hourlyRateDeduction = dailyRateDeduction / 8;
                            
                            // OVERTIME CALCULATION (per EOS file lines 630-635):
                            // per-hour overtime rate = (basic/240)/2 + (full/240)
                            // hours amount = overtimeHourlyRate * overtime_hours
                            // days amount  = overtimeHourlyRate * 8 * overtime_days
                            const overtimeHourlyRate = ((basic / 240) / 2) + (salary / 240);
                            
                            // Calculate amounts
                            const overtimeAmount = overtimeHourlyRate * overtimeHours;
                            const deductionHoursAmount = hourlyRateDeduction * deductionHours;
                            const deductionDaysAmount = dailyRateDeduction * deductionDays;
                            
                            console.log('Rates:', {
                                dailyRateDeduction: dailyRateDeduction.toFixed(4),
                                hourlyRateDeduction: hourlyRateDeduction.toFixed(4),
                                overtimeHourlyRate: overtimeHourlyRate.toFixed(4)
                            });
                            console.log('Calculated amounts:', {overtimeAmount, deductionHoursAmount, deductionDaysAmount});
                            
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
                        
                        // Initial calculation
                        setTimeout(function() {
                            console.log('Running initial calculation');
                            calculatePayrollAdjustments();
                        }, 800);
                    } // --- End if (isHR_Payroll) ---
                },
                preConfirm: () => {
                    const swalModal = Swal.getHtmlContainer();
                    let approver_chain = [];
                    
                    // Get approval comment (if provided)
                    let approval_comment = $(swalModal).find('#swal_approval_comment').val() || '';
                    approval_comment = approval_comment.trim().substring(0, 5000); // Limit to 5000 chars
                    
                    // A) Get payment details (if they exist)
                    let ticket_pay = $(swalModal).find('#swal_ticket_fares').val() || null;
                    let permit_fee = $(swalModal).find('#swal_permit_fee').val() || null;
                    
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

                    // B) Get approver chain (based on leave type and role)
                    if (isSimpleLeave && isLevel1) {
                        // Simple leave: Level 1 - HR Senior BP will be auto-selected by backend
                        // Return a Promise to get HR Senior BP from backend
                        return new Promise(function(resolve, reject){
                            $.ajax({
                                url: './includes/ajaxFile/ajaxEmployee.php',
                                type: 'POST',
                                dataType: 'json',
                                data: { 
                                    ajaxType: 'get_hr_senior_bp'
                                },
                            }).done(function(res){
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
                            }).fail(function(){
                                Swal.showValidationMessage(__('error_loading_hr_senior_bp') || 'Error loading HR Senior BP');
                                reject('Error loading HR Senior BP');
                            });
                        });
                        
                    } else if (!isSimpleLeave && isLevel1) {
                        // Vacation (annual vacation) flow: After Manager, auto-route to HR Senior BP then asset teams
                        // Build chain by calling backend helper; return a Promise to resolve approver_chain
                        return new Promise(function(resolve, reject){
                            $.ajax({
                                url: './includes/ajaxFile/ajaxEmployee.php',
                                type: 'POST',
                                dataType: 'json',
                                data: { 
                                    ajaxType: 'get_asset_clearance_chain', 
                                    vacation_id: vacationId,
                                    exclude_level1: true  // Exclude first approver (they're approving now)
                                },
                            }).done(function(res){
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
                            }).fail(function(){
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
                    
                    // Log for debugging
                    console.log('sendApproval preConfirm - departure_date:', departureDateVal);
                    console.log('sendApproval preConfirm - arrival_date:', arrivalDateVal);
                    console.log('sendApproval preConfirm - ticket_pay:', ticket_pay);
                    console.log('sendApproval preConfirm - permit_fee:', permit_fee);
                    
                    return {
                        approver_chain: approver_chain,
                        departure_date: departureDateVal,
                        arrival_date: arrivalDateVal,
                        ticket_pay: ticket_pay,
                        permit_fee: permit_fee,
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
                } else if (result.isDenied) {
                    // User selected "Employee Keeps Assets" - still approve but log this decision
                    const approvalData = result.value || {};
                    approvalData.asset_decision = 'employee_keeps';
                    
                    // Show confirmation that employee will keep assets
                    Swal.fire({
                        title: __('confirm_employee_keeps_assets') || 'Confirm: Employee Keeps Assets',
                        html: __('employee_keeps_assets_note') || 'You are approving this vacation with the understanding that the employee will keep the assigned assets during their vacation period.',
                        icon: 'info',
                        showCancelButton: true,
                        confirmButtonColor: '#ffc107',
                        confirmButtonText: __('yes_proceed') || 'Yes, Proceed',
                        cancelButtonText: __('cancel')
                    }).then(function(confirmResult) {
                        if (confirmResult.isConfirmed) {
                            sendApproval(vacationId, approvalData);
                        }
                    });
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
                didOpen: () => { Swal.showLoading(); }
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
                    departure_date: approveData.departure_date || null, // Send departure date
                    arrival_date: approveData.arrival_date || null,     // Send arrival date
                    ticket_pay: approveData.ticket_pay || null,       // Send ticket pay
                    permit_fee: approveData.permit_fee || null,       // Send permit fee
                    hr_team_cc: approveData.hr_team_cc || [],         // Send HR team CC
                    overtime_hours: approveData.overtime_hours || null, // Send overtime hours
                    deduction_hours: approveData.deduction_hours || null, // Send deduction hours
                    deduction_days: approveData.deduction_days || null, // Send deduction days
                    payroll_note: approveData.payroll_note || null,    // Send payroll note
                    approval_comment: approveData.approval_comment || ''  // Send approval comment
				},
			})
			.done(function(response){
                console.log('sendApproval - Backend response:', response);
                console.log('sendApproval - Response success:', response.success);
                console.log('sendApproval - Response message:', response.message);
                
                Swal.fire({
                    title: response.title || __('success') || 'Success',
                    text: response.message || '',
                    icon: response.type || 'success',
                    allowOutsideClick: false
                }).then(function(isConfirm){ if(isConfirm){ location.reload(); } });
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
					.done(function(response){
						Swal.fire({
							title:response.title,text:response.message,icon:response.type,allowOutsideClick:false
						}).then(function(isConfirm){(isConfirm)?location.reload():""});
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
        
        function showPaymentModal(vacationId, employeeName, currentTicketPay, currentPermitFee, currentDepartureDate, currentArrivalDate) {
            Swal.fire({
                title: __('add_edit_payments_for').replace('{0}', employeeName),
                html: `
                    <div class="text-left" style="padding: 10px 20px;">
                        <p class="mt-3 mb-4"><strong>${__('enter_update_payment_details')}</strong></p>
                        
                        <div class="form-group mb-3">
                            <label for="departure_date_update" class="d-block text-left font-weight-bold mb-2" style="color: #333;">
                                <i class="fa fa-plane-departure"></i> ${__('departure_date')}
                            </label>
                            <input type="text" id="departure_date_update" class="form-control" placeholder="Select departure date" readonly style="width: 100%; padding: .75rem; border: 1px solid #ced4da; border-radius: .25rem; background-color: white; cursor: pointer;">
                        </div>
                        
                        <div class="form-group mb-3">
                            <label for="arrival_date_update" class="d-block text-left font-weight-bold mb-2" style="color: #333;">
                                <i class="fa fa-plane-arrival"></i> ${__('arrival_date')}
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
                            <input type="number" id="permit_fee_update" class="form-control" placeholder="${__('permit_fee')}" value="${currentPermitFee}" step="0.01" style="width: 100%; padding: .75rem; border: 1px solid #ced4da; border-radius: .25rem;">
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
                    }).on('changeDate', function (e) {
                        var departureDate = e.date;
                        $('#arrival_date_update').datepicker('setStartDate', departureDate);
                    });
                    
                    // Initialize arrival date picker
                    $('#arrival_date_update').datepicker({
                        format: "yyyy-mm-dd",
                        todayHighlight: true,
                        autoclose: true
                    }).on('changeDate', function (e) {
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
                    .done(function(response){
                        Swal.fire({
                            title:response.title,text:response.message,icon:response.type,allowOutsideClick:false
                        }).then(function(isConfirm){(isConfirm)?location.reload():""}); 
                    })
                    .fail(function(jqXHR, textStatus, errorThrown) {
                        Swal.fire('Error', __('error_updating_payments'), 'error');
                    });
                }
            });
        }        /**
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
                                <h6 style="margin:0 0 10px; color:#0b5eb7;"><i class="fa fa-file"></i> ${__('current_passport_copy') || 'Current Passport Copy'}</h6>
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
                                            success: function(r){
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
                                                    Swal.fire(__('error')||'Error', r.message || 'Failed replacing passport copy.', 'error');
                                                }
                                            },
                                            error: function(){
                                                Swal.hideLoading();
                                                Swal.fire(__('error')||'Error', __('error_replacing_passport')||'Could not replace passport copy.', 'error');
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
                            .done(function(response){
                                Swal.fire({
                                    title: response.title || (__('success') || 'Success'),
                                    text: response.message,
                                    icon: response.type || 'success',
                                    allowOutsideClick: false
                                }).then(function(isConfirm){
                                    if(isConfirm) {
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

        </script>
    </body>
</html>