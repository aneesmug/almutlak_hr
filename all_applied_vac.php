<?php
/****************************************************************
 * MODIFICATION SUMMARY:
 * 1. DYNAMIC 'PENDING WITH' STATUS: The status display logic has been enhanced to show not just the status, but also who the request is pending with. It now dynamically identifies the next approver in the chain (e.g., "Pending with HR Assistant," "Pending with GM") to provide clearer, more actionable information to users.
 * 2. ADDED HR MANAGER QUEUE: Created a special default filter, 'hr_manager_queue', for the HR Manager. This queue now correctly displays requests from their own department (dept 5) with an 'apply' status, in addition to requests from other departments that have been approved by the HR Assistant ('hr_assistant_approved').
 * 3. RESTRUCTURED FILTER LOGIC: The logic for determining the default filter has been refactored to be more explicit. It now first determines the correct filter based on the user's role (defaulting to 'all' for System Admins) and then applies that filter to build the query.
 * 4. FIXED ADMIN DEPARTMENT FILTER: Corrected the query logic to prevent the department filter from being applied to System Administrators who also have a 'DPT_Manager' role.
 * 5. ROBUST BUTTON VISIBILITY: Modified the button display logic to handle a parallel-like approval state. The IT department's "Clearance" button will now correctly appear for requests awaiting their action.
 * 6. SEQUENTIAL GM APPROVAL: The General Manager's and HR Manager's final "Approve" button is now hidden until the `it_approval_status` is no longer 'pending'.
 ****************************************************************/
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/session_check.php';

// The $user_role variable is now available globally from session_check.php

// --- Search, Pagination & Filtering Logic ---

$all_statuses = [
    'apply' => __('new_request'),
    'pending' => __('assistant_pending'),
    'hr_assistant_approved' => __('hr_assistant_approved'),
    'it_pending' => __('it_clearance_pending'),
    'hr_manager_approved' => __('hr_manager_approved'),
    'gm_approved' => __('gm_approved'),
    'rejected' => __('rejected')
];

// 1. Set up variables
$search_term = $_GET['search'] ?? '';
$limit_options = [8, 12, 16];
$perpage = 8;
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
    } elseif ($user_dept == 6) {
        $current_filter = 'it_pending';
    } else {
        switch ($user_role) {
            case 'DPT_Manager': $current_filter = 'apply'; break;
            case 'HR_Assistant': $current_filter = 'pending'; break;
            case 'HR_Manager': $current_filter = 'hr_manager_queue'; break;
            case 'GM': $current_filter = 'hr_manager_approved'; break;
            default: $current_filter = 'none'; break;
        }
    }
}

$where_clauses = [];
$params = [];
$types = "";

// 3. Based on the effective filter, build the query
if ($current_filter === 'hr_manager_queue') {
    $where_clauses[] = "((v.approval_status = ? AND e.dept = ?) OR v.approval_status = ?)";
    $params = ['apply', 5, 'hr_assistant_approved'];
    $types = "sis";
    $page_title = __('approval_queue');
} elseif ($current_filter !== 'all' && $current_filter !== 'none' && array_key_exists($current_filter, $all_statuses)) {
    $statuses_to_query = [$current_filter];
    $placeholders = implode(',', array_fill(0, count($statuses_to_query), '?'));
    $where_clauses[] = "v.approval_status IN ($placeholders)";
    foreach ($statuses_to_query as $status) {
        $params[] = $status;
        $types .= "s";
    }
    $page_title = $all_statuses[$current_filter];
} else {
    $page_title = __('all_requests');
}


if (!empty($search_term)) {
    $where_clauses[] = "(e.name LIKE ? OR v.emp_id LIKE ?)";
    $search_param = "%{$search_term}%";
    array_push($params, $search_param, $search_param);
    $types .= "ss";
}

// MODIFIED: Ensure the department filter does NOT apply to system admins.
if ($user_role === 'DPT_Manager' && !$is_system_admin) {
    $where_clauses[] = "e.dept = ?";
    $params[] = $user_dept;
    $types .= "i";
}


$where_sql = "";
if (!empty($where_clauses)) {
    $where_sql = " WHERE " . implode(" AND ", $where_clauses);
}

$count_sql = "SELECT COUNT(v.id) as total 
              FROM emp_vacation v 
              JOIN employees e ON v.emp_id = e.emp_id" . $where_sql;
$total_items = 0;
if ($current_filter !== 'none' || !empty($search_term)) {
    $stmt_count = $conDB->prepare($count_sql);
    if (!empty($params)) {
        $stmt_count->bind_param($types, ...$params);
    }
    $stmt_count->execute();
    $total_items = $stmt_count->get_result()->fetch_assoc()['total'] ?? 0;
    $stmt_count->close();
}
$total_pages = $show_all ? 1 : ceil($total_items / $items_per_page);
if ($current_page > $total_pages && $total_pages > 0) {
    $current_page = $total_pages;
}

$requests = [];
if (($current_filter !== 'none' || !empty($search_term)) && $total_items > 0) {
    $sql = "SELECT 
        v.*, 
        v.attachment_path,
        e.name as employee_name,
        e.dept,
        b.remaining_balance,
        b.available_balance,
        CASE 
            WHEN `v`.`fly_type` = 'annual' THEN '" . __('annual_vacation') . "' 
            WHEN `v`.`fly_type` = 'emergency' THEN '" . __('emergency_vacation') . "'
            ELSE ''
        END AS `fly_type`
        FROM emp_vacation v 
        JOIN employees e ON v.emp_id = e.emp_id
        LEFT JOIN emp_vacation_balance b ON v.id = b.vac_id" . $where_sql;
    $sql .= " ORDER BY v.created_at DESC";

    $main_params = $params;
    $main_types = $types;

    if (!$show_all) {
        $offset = ($current_page - 1) * $items_per_page;
        $sql .= " LIMIT ?, ?";
        array_push($main_params, $offset, $items_per_page);
        $main_types .= "ii";
    }

    $stmt = $conDB->prepare($sql);
    if (!empty($main_params)) {
        $stmt->bind_param($main_types, ...$main_params);
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

// ** NEW ** Get the total unfiltered count of all emp_vacation.
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
        <link rel="shortcut icon" href="assets/images/favicon.ico">
        <link href="./plugins/custombox/css/custombox.min.css" rel="stylesheet">
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
                                    <h4 class="header-title m-t-0 m-b-30"><?=__('vacation_approval_center')?></h4>

                                    <div class="row filter-controls mx-auto mb-5">
                                        <div class="col-md-6 mb-3 mb-md-0">
                                            <div class="form-group">
                                                <label for="statusFilter" class="font-weight-bold"><?=__('filter_by_status')?></label>
                                                <select class="form-control" id="statusFilter" onchange="applyFilters()">
                                                    <option value="all" <?php if ($current_filter == 'all') echo 'selected'; ?>><?=__('all_requests')?></option>
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
                                        <h4 class="mb-0 text-muted"><?=__('showing_requests')?></h4>
                                        <span class="badge badge-light p-2"><?=__('total_found')?>: <?=$total_items; ?></span>
                                    </div>

                                    <?php if (!empty($requests)): ?>
                                        <div class="row">
                                            <?php foreach ($requests as $req): ?>
												<div class="col-lg-3 col-md-6 mb-3">
													<div class="card request-card h-100">
														<div class="card-header">
															<?=parseName($req['employee_name']); ?>
															<span class="float-right"><?=__('emp_id')?>: <?=htmlspecialchars($req['emp_id']); ?></span>
														</div>
														<div class="card-body">
															<div class="detail-item"><i class="fad fa-paper-plane duotone-info"></i><strong><?=__('applied')?>:</strong> <?=htmlspecialchars(date('d M Y', strtotime($req['created_at']))); ?></div>
															<div class="detail-item"><i class="fad fa-suitcase-rolling duotone-info"></i><strong><?=__('type')?>:</strong> <?=htmlspecialchars(parseName($req['vac_type'], 'FIRST')." | ".$req['fly_type']); ?></div>
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
                                                                    $badge_class = 'secondary';
                                                                    $status_text = '';

                                                                    switch ($req['approval_status']) {
                                                                        case 'apply':
                                                                            $badge_class = 'info';
                                                                            $status_text = $req['dept'] == 5 ? __('pending_with_hr_manager') : __('pending_with_dpt_manager');
                                                                            break;
                                                                        case 'pending':
                                                                            $badge_class = 'warning';
                                                                            $status_text = __('pending_with_hr_assistant');
                                                                            break;
                                                                        case 'hr_assistant_approved':
                                                                            $badge_class = 'primary';
                                                                            $status_text = __('pending_with_hr_manager');
                                                                            break;
                                                                        case 'it_pending':
                                                                            $badge_class = 'primary';
                                                                            $status_text = __('pending_with_it');
                                                                            break;
                                                                        case 'hr_manager_approved':
                                                                            $badge_class = 'primary';
                                                                            // This is the key logic: if the main status is hr_manager_approved, check the sub-status for IT.
                                                                            if ($req['it_approval_status'] == 'pending') {
                                                                                $status_text = __('pending_with_it');
                                                                            } else {
                                                                                $status_text = __('pending_with_gm');
                                                                            }
                                                                            break;
                                                                        case 'gm_approved':
                                                                            $badge_class = 'success';
                                                                            $status_text = __('approved');
                                                                            break;
                                                                        case 'rejected':
                                                                            $badge_class = 'danger';
                                                                            $status_text = __('rejected');
                                                                            break;
                                                                        default:
                                                                            $status_text = __('unknown');
                                                                            break;
                                                                    }
																?>
																<i class="fad fa-info-circle duotone-info"></i>
																<strong><?=__('status')?>:</strong> <span class="badge badge-<?=$badge_class; ?> p-2"><?=htmlspecialchars($status_text); ?></span>
                                                            </div>
                                                            
                                                            <?php if ($req['approval_status'] == 'gm_approved' && isset($req['remaining_balance'])): ?>
                                                                <hr>
                                                                <div class="detail-item"><i class="fad fa-wallet duotone-success"></i><strong><?=__('remaining')?>:</strong> <?=htmlspecialchars(number_format($req['remaining_balance'], 2)); ?> <?=__('days')?></div>
                                                            <?php endif; ?>
														</div>
														<div class="card-footer d-flex justify-content-between align-items-center btn-group">
															<button class="btn btn-info btn-block waves-effect" onclick="window.open('vacation_report_details.php?id=<?=$req['id']; ?>&emp_id=<?=$req['emp_id']; ?>')"><i class="fa fa-eye"></i> <?=__('view')?></button>
															
                                                            <?php
                                                            if ($req['approval_status'] != 'gm_approved' && $req['approval_status'] != 'rejected'):

                                                                $show_standard_buttons = false;
                                                                
                                                                if (
                                                                    // DPT Manager or HR can approve 'apply' status
                                                                    ($req['approval_status'] == 'apply' && ($user_role == 'DPT_Manager' || ($user_role == 'HR_Manager' && $req['dept'] == 5))) ||
                                                                    // HR Assistant approves 'pending' status
                                                                    ($req['approval_status'] == 'pending' && $user_role == 'HR_Assistant') ||
                                                                    // HR Manager approves requests from other depts after HR Assistant
                                                                    ($req['approval_status'] == 'hr_assistant_approved' && $user_role == 'HR_Manager')
                                                                ) {
                                                                    $show_standard_buttons = true;
                                                                }
                                                                
                                                                // GM and HR Manager final approval button with IT check
                                                                if ($req['approval_status'] == 'hr_manager_approved' && $req['it_approval_status'] != 'pending' && ($user_role == 'GM' || $user_role == 'HR_Manager')) {
                                                                    $show_standard_buttons = true;
                                                                }

                                                                if ($show_standard_buttons):
                                                                    $employee_name_js = htmlspecialchars(addslashes($req['employee_name']), ENT_QUOTES);
                                                                    $vac_type_js = htmlspecialchars($req['vac_type']);
                                                                    $start_date_js = htmlspecialchars($req['start_date'] ?? 'N/A');
                                                                    $end_date_js = htmlspecialchars($req['return_date'] ?? 'N/A');
                                                                    $days_js = htmlspecialchars($req['vacdays']);
                                                                ?>
                                                                    <button class="btn btn-danger btn-block waves-effect" onclick="rejectVacationRequest(<?=$req['id']; ?>, '<?=$user_role; ?>', '<?=$employee_name_js; ?>', '<?=$vac_type_js; ?>', '<?=$start_date_js; ?>', '<?=$end_date_js; ?>', '<?=$days_js; ?>')"><i class="fa fa-times"></i> <?=__('reject')?></button>
                                                                    <button class="btn btn-success btn-block waves-effect" onclick="approveRequest(<?=$req['id']; ?>, '<?=$user_role; ?>', '<?=$employee_name_js; ?>', '<?=$vac_type_js; ?>', '<?=$start_date_js; ?>', '<?=$end_date_js; ?>', '<?=$days_js; ?>')"><i class="fa fa-check"></i> <?=__('approve')?></button>
                                                                <?php endif; ?>
                                                                
                                                                <?php // IT Clearance Buttons: Show if status is IT pending OR if status is HR Manager approved AND IT status is pending
                                                                if (($req['approval_status'] == 'it_pending' || ($req['approval_status'] == 'hr_manager_approved' && $req['it_approval_status'] == 'pending')) && ($user_dept == 6 || $is_system_admin)):
                                                                    $employee_name_js_it = htmlspecialchars(addslashes($req['employee_name']), ENT_QUOTES);
                                                                    $vac_type_js_it = htmlspecialchars($req['vac_type']);
                                                                    $start_date_js_it = htmlspecialchars($req['start_date'] ?? 'N/A');
                                                                    $end_date_js_it = htmlspecialchars($req['return_date'] ?? 'N/A');
                                                                    $days_js_it = htmlspecialchars($req['vacdays']);
                                                                ?>
                                                                    <button class="btn btn-danger btn-block waves-effect" onclick="rejectVacationRequest(<?=$req['id']; ?>, 'IT_Technician', '<?=$employee_name_js_it; ?>', '<?=$vac_type_js_it; ?>', '<?=$start_date_js_it; ?>', '<?=$end_date_js_it; ?>', '<?=$days_js_it; ?>')"><i class="fa fa-times"></i> <?=__('reject')?></button>
                                                                    <button class="btn btn-primary btn-block waves-effect" onclick="approveITClearance(<?=$req['id'];?>, '<?=$employee_name_js_it;?>')">
                                                                        <i class="fa fa-laptop"></i> <?=__('clearance')?>
                                                                    </button>
                                                                <?php endif; ?>

                                                            <?php endif; // End check for final statuses ?>

                                                            <?php
                                                            // Condition to show "Add/Edit Payments" button
                                                            if (
                                                                ($user_role == 'HR_Assistant' || $user_role == 'HR_Manager') &&
                                                                in_array($req['approval_status'], ['hr_assistant_approved', 'hr_manager_approved', 'gm_approved', 'it_pending']) &&
                                                                $req['vac_type'] == 'Fly' &&
                                                                ($req['ticket_pay'] == 00.0 || $req['permit_fee'] == 00.0)
                                                            ):
                                                            ?>
                                                                <button class="btn btn-warning btn-block waves-effect" 
                                                                        onclick="addVacationPayments(
                                                                            <?=$req['id']; ?>, 
                                                                            '<?=htmlspecialchars(addslashes($req['employee_name']), ENT_QUOTES); ?>',
                                                                            '<?= $req['ticket_pay'] ?? '00.0'; ?>',
                                                                            '<?= $req['permit_fee'] ?? '00.0'; ?>'
                                                                        )">
                                                                    <i class="fa fa-credit-card"></i> <?=__('add_edit_payments')?>
                                                                </button>
                                                            <?php endif; ?>
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

        <script src="assets/js/jquery.min.js"></script>
        <script src="assets/js/bootstrap.bundle.min.js"></script>
        <script src="assets/js/metisMenu.min.js"></script>
        <script src="assets/js/waves.js"></script>
        <script src="assets/js/jquery.slimscroll.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
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

		function approveRequest(vacationId, role, employeeName, vacType, startDate, endDate, totalDays) {
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

			if (role === 'HR_Assistant') {
				Swal.fire({
					title: __('confirm_approval'),
					html: `
						${infoHtml}
						<p class="mt-3"><strong>${__('enter_approval_details')}</strong></p>
						<input type="number" id="ticket_pay" class="swal2-input" placeholder="${__('ticket_payment_optional')}">
						<input type="number" id="permit_fee" class="swal2-input" placeholder="${__('permit_fee_optional')}">
					`,
					confirmButtonText: __('submit_approval'),
					showCancelButton: true,
                    allowOutsideClick: false,
					preConfirm: () => {
						return {
							ticket_pay: document.getElementById('ticket_pay').value,
							permit_fee: document.getElementById('permit_fee').value
						}
					}
				}).then((result) => {
					if (result.isConfirmed) {
						sendApproval(vacationId, role, result.value.ticket_pay, result.value.permit_fee);
					}
				});
			} else {
				Swal.fire({
					title: __('confirm_approval'),
					html: infoHtml,
					icon: 'warning',
					showCancelButton: true,
					confirmButtonColor: '#28a745',
					cancelButtonColor: '#dc3545',
					confirmButtonText: __('yes_approve_it'),
                    allowOutsideClick: false,
				}).then((result) => {
					if (result.isConfirmed) {
						sendApproval(vacationId, role);
					}
				})
			}
		}

		function sendApproval(vacationId, role, ticketPay = null, permitFee = null) {
			$.ajax({
				url: './includes/ajaxFile/ajaxVacation.php',
				type: 'POST',
				dataType: 'JSON',
				data: {
					ajaxType: 'approveVacation',
					vacation_id: vacationId,
					approver_role: role,
					ticket_pay: ticketPay,
					permit_fee: permitFee
				},
			})
			.done(function(response){
				Swal.fire({
					title:response.title,text:response.message,icon:response.type,allowOutsideClick:false
				}).then(function(isConfirm){(isConfirm)?location.reload():""});
			})
			.fail(function(jqXHR, textStatus, errorThrown) {
				reject(handleAjaxFailure(jqXHR, textStatus).message);
			});
		}

		function rejectVacationRequest(vacationId, role, employeeName, vacType, startDate, endDate, totalDays) {
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
							rejection_note: reason,
							approver_role: role
						}
					})
					.done(function(response){
						Swal.fire({
							title:response.title,text:response.message,icon:response.type,allowOutsideClick:false
						}).then(function(isConfirm){(isConfirm)?location.reload():""});
					})
					.fail(function(jqXHR, textStatus, errorThrown) {
						reject(handleAjaxFailure(jqXHR, textStatus).message);
					});
				}
			})
		}

        function approveITClearance(vacationId, employeeName) {
            Swal.fire({
                title: __('it_asset_clearance_for').replace('{0}', employeeName),
                html: `
                    <p class="mt-3"><strong>${__('provide_clearance_notes')}</strong></p>
                    <select id="it_notes" class="swal2-input" required>
                        <option value="">${__('select_an_option')}</option>
                        <option value="cleared">${__('cleared_all_assets')}</option>
                        <option value="not_received">${__('not_received')}</option>
                        <option value="received">${__('received')}</option>
                    </select>
                `,
                confirmButtonText: __('submit_clearance'),
                showCancelButton: true,
                allowOutsideClick: false,
                preConfirm: () => {
                    const itNotes = document.getElementById('it_notes').value;
                    // const errorElement = document.getElementById('it_notes_error');
                    
                    // Validate selection
                    if (!itNotes) {
                        // errorElement.style.display = 'block';
                        Swal.showValidationMessage(__('please_select_an_option'));
                        return false;
                    }
                    // errorElement.style.display = 'none';
                    return {
                        it_notes: itNotes
                    }
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: './includes/ajaxFile/ajaxVacation.php',
                        type: 'POST',
                        dataType: 'JSON',
                        data: {
                            ajaxType: 'approveITClearance',
                            vacation_id: vacationId,
                            it_notes: result.value.it_notes
                        },
                    })
                    .done(function(response){
                        Swal.fire({
                            title:response.title, 
                            text:response.message, 
                            icon:response.type, 
                            allowOutsideClick:false
                        }).then(function(isConfirm){ 
                            if(isConfirm.value) { 
                                location.reload(); 
                            } 
                        });
                    })
                    .fail(function() {
                        Swal.fire('Error', __('error_processing_clearance'), 'error');
                    });
                }
            });
        }

        function addVacationPayments(vacationId, employeeName, currentTicketPay, currentPermitFee) {
            Swal.fire({
                title: __('add_edit_payments_for').replace('{0}', employeeName),
                html: `
                    <p class="mt-3"><strong>${__('enter_update_payment_details')}</strong></p>
                    <input type="number" id="ticket_pay_update" class="swal2-input" placeholder="${__('ticket_payment')}" value="${currentTicketPay}">
                    <input type="number" id="permit_fee_update" class="swal2-input" placeholder="${__('permit_fee')}" value="${currentPermitFee}">
                `,
                confirmButtonText: __('update_payments'),
                showCancelButton: true,
                allowOutsideClick: false,
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