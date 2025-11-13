<?php
/****************************************************************
 * MODIFICATION SUMMARY:
 * 1. ENTIRELY REBUILT for Chain Approval System.
 * 2. REMOVED old status logic (hr_assistant_approved, it_pending, etc.).
 * 3. ADDED new filters: 'my_pending', 'approved', 'rejected', 'my_dept', 'all'.
 * 4. 'my_pending' is the new default for managers, querying `request_approvers`.
 * 5. SQL QUERY now joins `request_approvers` and `employees` (as `approver_emp`) to find the current pending approver.
 * 6. STATUS DISPLAY is now dynamic: "Pending with [Approver Name]", "Approved", or "Rejected".
 * 7. BUTTON VISIBILITY is simplified: Approve/Reject buttons only show if the request is pending with the logged-in user.
 * 8. JAVASCRIPT `approveRequest()` modal now has conditional logic:
 * - Simple Leave with Supervisor: Level 1 (Supervisor/Manager) → HR Senior BP
 * - Simple Leave without Supervisor: Level 1 (Dept Manager) → HR Senior BP
 * - Annual Vacation: Level 1 (Manager) → HR Assistant → Full Chain
 * 9. JAVASCRIPT `sendApproval()` now sends all conditional data (chain, payments) to `ajaxVacation.php`.
 * 10. APPLIED `parseName()` to all employee names.
 * 11. FIXED all bugs (numbering, file paths) and set modal width.
 * 12. [FIXED] Updated "Payments" button logic to be visible to HR Assistants ('assistant')
 * *after* a request is fully approved, matching the requested workflow.
 * 13. [FIXED] Corrected JS variable for HR Assistant role from 'HR_Assistant' to 'assistant'.
 * 14. [FIXED] Updated JS variable pass to use `$user_type` from session_check.php.
 * 15. [FIXED] Updated PHP "Payments" button logic to use `$isHR`, `$is_system_admin`, 
 * and `$isDeptHr` from session_check.php for correct role checking.
 * 16. [NEW] Payment button is now hidden if `ticket_pay` or `permit_fee` have been entered.
 * 17. [NEW] Added 30-day filter for approved records: By default, only shows approved 
 * records from the last 30 days. When searching by name/emp_id, shows ALL records 
 * for that employee regardless of date. This improves page performance and focuses 
 * on recent approvals.
 * 18. [NEW] SUPERVISOR-BASED APPROVAL: Updated to support supervisor assignments.
 * - Query now includes `supervisor_id` and supervisor details from employees table
 * - Approval modal differentiates between Simple Leave and Annual Vacation flows
 * - Simple Leave: Supervisor/Manager → HR Senior BP (2-level)
 * - Annual Vacation: Manager → HR Assistant → Custom Chain (multi-level)
 * - Added `get_hr_senior_bp` AJAX handler for loading HR Senior BP users
 * - Pass `hasSupervisor` and `isSimpleLeave` flags to JavaScript for dynamic logic
 ****************************************************************/
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/session_check.php';
// $user_type, $empid, $user_dept, $is_system_admin, $isHR, $isDeptHr are available from session_check.php

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

} elseif ($current_filter === 'my_team') {
    // Show requests for the current user's direct reports (supervisor) or entire department (manager)
    $is_manager_role = (strpos($user_role ?? '', 'Manager') !== false) || ($user_role ?? '') === 'DPT_Manager';
    $is_supervisor_role = (strpos($user_role ?? '', 'Supervisor') !== false) || ($user_role ?? '') === 'HR_Supervisor';

    if ($is_manager_role) {
        // Managers: see all in their department
        $where_clauses[] = "e.dept = ?";
        $params[] = $user_dept;
        $types .= "i";
    } else {
        // Supervisors (or default): show only direct reports
        $where_clauses[] = "e.supervisor_id = ?";
        $params[] = $empid;
        $types .= "i";
    }
    
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
    // When viewing 'all' without search, limit approved records to last 30 days
    $where_clauses[] = "(v.current_status != 'approved' OR v.created_at >= DATE_SUB(CURDATE(), INTERVAL 15 DAY))";
}

// Add search term if provided
if (!empty($search_term)) {
    $where_clauses[] = "(e.name LIKE ? OR v.emp_id LIKE ? OR v.request_inv_no LIKE ?)";
    $search_param = "%{$search_term}%";
    array_push($params, $search_param, $search_param, $search_param);
    $types .= "sss";
}

// System admins see all departments. Other users (if not using 'my_pending' or 'all') are restricted to their dept.
if (!$is_system_admin && !in_array($current_filter, ['my_pending', 'all', 'my_dept'])) {
    $where_clauses[] = "e.dept = ?";
    $params[] = $user_dept;
    $types .= "i";
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

// Get the total unfiltered count
$unfiltered_sql = "SELECT COUNT(id) as total FROM emp_vacation";
$unfiltered_result = mysqli_query($conDB, $unfiltered_sql);
$unfiltered_total_items = mysqli_fetch_assoc($unfiltered_result)['total'] ?? 0;

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
            .request-card .card-header { background-color: #fff; border-bottom: 1px solid #eef; font-weight: 600; font-size: 1.1em; }
            .request-card .card-header span { font-size: 0.9em; color: #8a94a6; }
            .request-card .card-body { padding: 1.5rem; }
            .detail-item { display: flex; align-items: center; margin-bottom: 1rem; font-size: 1.09em; }
            .detail-item i { color: #4a90e2; margin-right: 15px; width: 20px; text-align: center; }
            .detail-item strong { color: #8a94a6; min-width: 100px; display: inline-block; }
            .request-card .card-footer { background-color: #fafbff; border-top: 1px solid #eef; }
            .no-requests { padding: 3rem; background: #fff; border-radius: 15px; box-shadow: 0 10px 30px rgba(0, 0, 0, 0.07); }
			.btn-block + .btn-block{ margin-top: 0rem !important; }
            
            /* --- NEW STYLES FOR APPROVER LIST --- */
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
															<?=parseName($req['employee_name']); ?>
															<span class="float-right"><?=__('emp_id')?>: <?=htmlspecialchars($req['emp_id']); ?></span>
														</div>
														<div class="card-body">
															<div class="detail-item"><i class="fad fa-paper-plane duotone-info"></i><strong><?=__('applied')?>:</strong> <?=htmlspecialchars(date('d M Y', strtotime($req['created_at']))); ?></div>
															<div class="detail-item"><i class="fad fa-suitcase-rolling duotone-info"></i><strong><?=__('type')?>:</strong> <?=htmlspecialchars(parseName($req['vac_type'], 'FIRST')." | ".$req['fly_type_translated']); ?></div>
															<div class="detail-item"><i class="fad fa-calendar-alt duotone-info"></i><strong><?=__('start')?>:</strong> <?=htmlspecialchars($req['start_date'] ?? 'N/A'); ?></div>
															<div class="detail-item"><i class="fad fa-calendar-check duotone-info"></i><strong><?=__('return')?>:</strong> <?=htmlspecialchars($req['return_date'] ?? 'N/A'); ?></div>
															<div class="detail-item"><i class="fad fa-sun duotone-info"></i><strong><?=__('days')?>:</strong> <?=htmlspecialchars($req['vacdays']); ?></div>
                                                            
                                                            <?php if (!empty($req['attachment_path'])): ?>
                                                                <div class="detail-item">
                                                                    <i class="fad fa-paperclip duotone-info"></i>
                                                                    <strong><?=__('attachment')?>:</strong> 
                                                                    <a href="<?=htmlspecialchars($req['attachment_path']); ?>" target="_blank" class="ml-2 font-weight-bold text-info"><?=__('view_file')?></a>
                                                                </div>
                                                            <?php endif; ?>

															<div class="detail-item">
                                                                <?php 
                                                                    // --- NEW DYNAMIC STATUS LOGIC ---
                                                                    $badge_class = 'secondary';
                                                                    $status_text = '';

                                                                    switch ($req['current_status']) {
                                                                        case 'pending_approval':
                                                                            $badge_class = 'warning';
                                                                            $approver_name = $req['current_approver_name'] ? parseName($req['current_approver_name']) : 'next approver';
                                                                            $status_text = __('pending_with') . ' ' . htmlspecialchars($approver_name);
                                                                            break;
                                                                        case 'approved':
                                                                            $badge_class = 'success';
                                                                            $status_text = __('approved');
                                                                            break;
                                                                        case 'rejected':
                                                                            $badge_class = 'danger';
                                                                            $status_text = __('rejected');
                                                                            break;
                                                                        default:
                                                                            $status_text = htmlspecialchars($req['current_status']);
                                                                            break;
                                                                    }
																?>
																<i class="fad fa-info-circle duotone-info"></i>
																<strong><?=__('status')?>:</strong> <span class="badge badge-<?=$badge_class; ?> p-2"><?=htmlspecialchars($status_text); ?></span>
                                                            </div>
                                                            
                                                            <?php if ($req['current_status'] == 'approved' && isset($req['remaining_balance'])): ?>
                                                                <hr>
                                                                <div class="detail-item"><i class="fad fa-wallet duotone-success"></i><strong><?=__('remaining')?>:</strong> <?=htmlspecialchars(number_format($req['remaining_balance'], 2)); ?> <?=__('days')?></div>
                                                            <?php endif; ?>
														</div>
														<div class="card-footer d-flex justify-content-between align-items-center btn-group">
															<button class="btn btn-info btn-block waves-effect" onclick="window.open('vacation_report_details.php?id=<?=$req['id']; ?>&emp_id=<?=$req['emp_id']; ?>')"><i class="fa fa-eye"></i> <?=__('view')?></button>
												<button class="btn btn-secondary btn-block waves-effect" onclick="window.open('vacation_status_history.php?request_inv_no=<?= urlencode($req['request_inv_no']); ?>')"><i class="fas fa-history"></i> <?=__('history')?></button>
												
                                                            <?php
                                                            // --- NEW SIMPLIFIED BUTTON LOGIC ---
                                                            // Show buttons if the request is pending approval AND the current logged-in user is the one it's pending with.
                                                            if ($req['current_status'] == 'pending_approval' && $req['current_approver_id'] == $empid):
                                                                $employee_name_js = htmlspecialchars(addslashes(parseName($req['employee_name'])), ENT_QUOTES);
                                                                $employee_id_js = htmlspecialchars($req['emp_id'], ENT_QUOTES); // Pass emp_id for asset loading
                                                                $vac_type_js = htmlspecialchars($req['vac_type']); // Send the raw type 'Fly'
                                                                $start_date_js = htmlspecialchars($req['start_date'] ?? 'N/A');
                                                                $end_date_js = htmlspecialchars($req['return_date'] ?? 'N/A');
                                                                $days_js = htmlspecialchars($req['vacdays']);
                                                                $current_level_js = (int)$req['current_approval_level']; 
                                                                
                                                                // Pass user role and supervisor info
                                                                $user_role_js = htmlspecialchars($user_type, ENT_QUOTES);
                                                                $has_supervisor_js = !empty($req['supervisor_id']) ? 'true' : 'false';
                                                                $is_simple_leave_js = ($req['vac_type'] != 'Fly') ? 'true' : 'false';
                                                            ?>
                                                                <button class="btn btn-danger btn-block waves-effect" onclick="rejectVacationRequest(<?=$req['id']; ?>, '<?=$employee_name_js; ?>', '<?=$vac_type_js; ?>', '<?=$start_date_js; ?>', '<?=$end_date_js; ?>', '<?=$days_js; ?>')"><i class="fa fa-times"></i> <?=__('reject')?></button>
                                                                <button class="btn btn-success btn-block waves-effect" onclick="approveRequest(<?=$req['id']; ?>, '<?=$employee_id_js; ?>', '<?=$employee_name_js; ?>', '<?=$vac_type_js; ?>', '<?=$start_date_js; ?>', '<?=$end_date_js; ?>', '<?=$days_js; ?>', <?=$current_level_js; ?>, '<?=$user_role_js; ?>', <?=$has_supervisor_js; ?>, <?=$is_simple_leave_js; ?>)"><i class="fa fa-check"></i> <?=__('approve')?></button>
                                                            <?php endif; // End approval button check ?>

                                                            <?php
                                                            // --- [FIXED] PAYMENT BUTTON LOGIC ---
                                                            // Show this button if:
                                                            // 1. It's a 'Fly' vacation AND
                                                            // 2. (User is HR Manager/Admin AND status is pending/approved) OR
                                                            // 3. (User is HR Assistant (isDeptHr) AND status is *only* approved) OR
                                                            // 4. (User is GR Officer AND status is *only* approved)
                                                            
                                                            $show_payment_button = false; // Default
                                                            // Show ONLY after final approval (last level), and ONLY for Fly requests
                                                            if (
                                                                $req['vac_type'] == 'Fly' &&
                                                                $req['current_status'] == 'approved' &&
                                                                ($isHR || $is_system_admin || $isDeptHr || $isGR_Officer)
                                                            ) {
                                                                $show_payment_button = true;
                                                            }

                                                            // [NEW] Check if payments are entered
                                                            $payments_entered = (!empty($req['ticket_pay']) && (float)$req['ticket_pay'] > 0) || (!empty($req['permit_fee']) && (float)$req['permit_fee'] > 0);

                                                            // Only show the button if it's meant to be shown AND payments have NOT been entered
                                                            if ($show_payment_button && !$payments_entered):
                                                            ?>
                                                                <button class="btn btn-warning btn-block waves-effect" 
                                                                        onclick="addVacationPayments(
                                                                            <?=$req['id']; ?>, 
                                                                            '<?=htmlspecialchars(addslashes(parseName($req['employee_name'])), ENT_QUOTES); ?>',
                                                                            '<?= $req['ticket_pay'] ?? '0.00'; ?>',
                                                                            '<?= $req['permit_fee'] ?? '0.00'; ?>'
                                                                        )">
                                                                    <i class="fa fa-credit-card"></i> <?=__('payments')?>
                                                                </button>
                                                            <?php endif; // End payment button check ?>
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
            // Remove request details panel from the approval modal per requirement
            let infoHtml = '';
            
            // --- Define approval flow conditions ---
            const isLevel1 = (currentLevel == 1);
            const isHR_Assistant = (userRole === 'assistant');
            const isHR_SeniorBP = (userRole === 'hr_senior_bp');
            const isAnnualFly = (vacType === 'Fly');
            
            // Check if user is asset clearance role (IT, Admin, Transport manager)
            const isAssetClearanceRole = userRole && (
                userRole.toLowerCase().includes('it') || 
                userRole.toLowerCase().includes('admin') || 
                userRole.toLowerCase().includes('transport')
            );
            
            // Determine approval flow:
            // 1. Simple Leave with Supervisor: Level 1 = Supervisor → Level 2 = HR Senior BP
            // 2. Simple Leave without Supervisor: Level 1 = Dept Manager → Level 2 = HR Senior BP
            // 3. Annual Vacation: Level 1 = Manager → Level 2 = HR Assistant → Level 3+ = Chain
            
            let paymentHtml = '';
            let chainHtml = '';
            let hrTeamCCHtml = '';
            let assetHtml = '';
            let confirmButtonText = __('yes_approve_it');
            let showDenyButton = false;
            
            // --- Condition 1: Show Payment Fields? ---
            // Only for HR Assistant approving annual vacation (Fly)
            if (isHR_Assistant && isAnnualFly) {
                paymentHtml = `
                    <div class="swal-payment-details text-left mt-3">
                        <hr>
                        <h6 class="text-primary mb-3"><i class="fa fa-money-bill-wave"></i> ${__('payment_information')}</h6>
                        <div class="form-group">
                            <label for="swal_ticket_fares" class="font-weight-bold">
                                <i class="fa fa-plane"></i> ${__('ticket_fares')} 
                                <span class="text-muted">(${__('optional')})</span>
                            </label>
                            <input type="number" id="swal_ticket_fares" class="form-control" placeholder="0.00" step="0.01" style="width: 100%; padding: .375rem .75rem; border: 1px solid #ced4da; border-radius: .25rem;">
                        </div>
                        <div class="form-group mt-2">
                            <label for="swal_permit_fee" class="font-weight-bold">
                                <i class="fa fa-passport"></i> ${__('exit_re-entry_fee')} 
                                <span class="text-muted">(${__('optional')})</span>
                            </label>
                            <input type="number" id="swal_permit_fee" class="form-control" placeholder="0.00" step="0.01" style="width: 100%; padding: .375rem .75rem; border: 1px solid #ced4da; border-radius: .25rem;">
                        </div>
                    </div>
                `;
            }

            // --- Condition 1.5: Show HR Team CC Selection? ---
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
                    // Level 1: Supervisor/Manager approving → select HR Senior BP
                    chainHtml = `
                        <div class="swal-approval-chain text-left mt-3">
                            <hr>
                            <label for="hr_bp_select" class="mt-2">${__('select_hr_senior_bp')} (Required)</label>
                            <div class="swal-approval-chain-builder">
                                <select id="hr_bp_select" class="form-control swal-select2-dynamic" style="width: 100%;">
                                    <option value="">${__('loading_hr_staff')}</option>
                                </select>
                            </div>
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

            Swal.fire({
                title: isAssetClearanceRole ? (__('asset_clearance_confirmation') || 'Asset Clearance Confirmation') : __('confirm_approval'),
                html: infoHtml + assetHtml + paymentHtml + hrTeamCCHtml + chainHtml, // Combine all HTML parts (assets first)
                icon: isAssetClearanceRole ? 'question' : 'warning',
                width: '40%', // Set modal width
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

                    // --- SIMPLE LEAVE LOGIC ---
                    if (isSimpleLeave && isLevel1) {
                        // Level 1 approving simple leave: Load HR Senior BP
                        initSelect2('#hr_bp_select', __('select_hr_senior_bp'));
                        let $hrBPSelect = $('#hr_bp_select');
                        
                        $.ajax({
                            url: './includes/ajaxFile/ajaxEmployee.php',
                            dataType: 'JSON',
                            type: 'POST',
                            data: {
                                ajaxType: "get_hr_senior_bp" // Get HR Senior BP users
                            },
                            success: function(res) {
                                if (res.status == 200 && res.data.length > 0) {
                                    let hrBPOptions = `<option value="">${__('select_hr_senior_bp')}</option>`;
                                    for (let i in res.data) {
                                        if (res.data[i].emp_id != <?=$empid?>) { 
                                            hrBPOptions += `<option value="${res.data[i].emp_id}">${res.data[i].name} (${res.data[i].user_type})</option>`;
                                        }
                                    }
                                    $hrBPSelect.html(hrBPOptions);
                                } else {
                                    $hrBPSelect.html(`<option value="">${__('no_hr_senior_bp_found')}</option>`);
                                }
                            },
                            error: () => {
                                $hrBPSelect.html(`<option value="">${__('error_loading_approvers')}</option>`);
                            }
                        });
                    }

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
                                emp_id: employeeId
                            },
                            success: function(res) {
                                const $container = $(swalModal).find('#assets-loading-container');
                                if (res.status === 200 && Array.isArray(res.assets) && res.assets.length > 0) {
                                    let assetsListHtml = '<ul class="list-group mb-0">';
                                    res.assets.forEach(function(asset) {
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
                },
                preConfirm: () => {
                    const swalModal = Swal.getHtmlContainer();
                    let approver_chain = [];
                    
                    // A) Get payment details (if they exist)
                    let ticket_pay = $(swalModal).find('#swal_ticket_fares').val() || null;
                    let permit_fee = $(swalModal).find('#swal_permit_fee').val() || null;
                    
                    // A.5) Get HR Team CC members (if HR Senior BP is approving)
                    let hr_team_cc = [];
                    if (isHR_SeniorBP) {
                        let selectedCC = $(swalModal).find('#hr_team_cc_select').val();
                        if (selectedCC && Array.isArray(selectedCC)) {
                            hr_team_cc = selectedCC;
                        }
                    }

                    // B) Get approver chain (based on leave type and role)
                    if (isSimpleLeave && isLevel1) {
                        // Simple leave: Level 1 must select HR Senior BP
                        let hrBPId = $(swalModal).find('#hr_bp_select').val();
                        if (!hrBPId) {
                            Swal.showValidationMessage(__('must_select_hr_senior_bp'));
                            return false;
                        }
                        approver_chain.push(hrBPId);
                        
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
                                // Chain might be empty if no more approvers needed
                                resolve({
                                    approver_chain: res.chain,
                                    ticket_pay: $(swalModal).find('#swal_ticket_fares').val() || null,
                                    permit_fee: $(swalModal).find('#swal_permit_fee').val() || null
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
                    return {
                        approver_chain: approver_chain,
                        ticket_pay: ticket_pay,
                        permit_fee: permit_fee,
                        hr_team_cc: hr_team_cc
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
			$.ajax({
				url: './includes/ajaxFile/ajaxVacation.php',
				type: 'POST',
				dataType: 'JSON',
				data: {
					ajaxType: 'approveVacation',
					vacation_id: vacationId,
                    approver_chain: approveData.approver_chain || [], // Send the dynamic chain
                    ticket_pay: approveData.ticket_pay || null,       // Send ticket pay
                    permit_fee: approveData.permit_fee || null,       // Send permit fee
                    hr_team_cc: approveData.hr_team_cc || []          // Send HR team CC
				},
			})
			.done(function(response){
				Swal.fire({
					title:response.title,text:response.message,icon:response.type,allowOutsideClick:false
				}).then(function(isConfirm){(isConfirm)?location.reload():""});
			})
			.fail(function(jqXHR, textStatus, errorThrown) {
                // Use SweetAlert to show the failure
				Swal.fire('Error', 'An error occurred: ' + textStatus, 'error');
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
            Swal.fire({
                title: __('add_edit_payments_for').replace('{0}', employeeName),
                html: `
                    <div class="text-left" style="padding: 10px 20px;">
                        <p class="mt-3 mb-4"><strong>${__('enter_update_payment_details')}</strong></p>
                        
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
                preConfirm: () => {
                    return {
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
        }

        </script>
    </body>
</html>