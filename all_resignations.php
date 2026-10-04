<?php

require_once __DIR__ . '/includes/session_check.php';
require_once __DIR__ . '/includes/special_access_helper.php';
// $user_type, $empid, $user_dept, $is_system_admin, $isHR, $isDeptManager are available from session_check.php

// Restrict access: Employees cannot view this detailed report page,
// unless explicitly granted via app_settings -> Special Access.
if (
    isset($isEmployee) && $isEmployee === true
    && !user_has_special_access($conDB, $empid ?? '', 'access_all_resignations', $user_role ?? '', $user_type ?? '', $is_system_admin ?? false)
) {
    header("Location: ./profile.php");
    exit();
}

// A temp-role replacement can act on the original employee's pending approvals too -
// request_approvers.approver_id still points at the original employee.
$delegatedFromEmpId = getDelegatedFromEmpId($conDB, $empid ?? '');

$can_cancel_resignation_requests = (
    !empty($is_system_admin)
    || user_has_special_access($conDB, $empid ?? '', 'cancel_resignation_requests', $user_role ?? '', $user_type ?? '', $is_system_admin ?? false)
);
$cancellable_resignation_statuses = ['pending', 'approved'];

// --- Get Request Type ID for 'resignation_request' ---
$type_query = mysqli_query($conDB, "SELECT `id` FROM `approval_request_types` WHERE `type_name` = 'resignation_request' LIMIT 1");
if (!$type_query || mysqli_num_rows($type_query) == 0) {
    die("CRITICAL ERROR: 'resignation_request' type not found in `approval_request_types` table.");
}
$request_type_id = (int)mysqli_fetch_assoc($type_query)['id'];

// --- Search, Pagination & Filtering Logic ---

$all_statuses = [
    'my_pending' => __('my_pending_queue'),
    'my_team' => (function_exists('__') ? __('my_team_requests') : 'My Team'),
    'my_dept' => __('my_department_requests'),
    'pending' => __('all_pending'),
    'approved' => __('approved'),
    'rejected' => __('rejected'),
    'all' => __('all_requests')
];

// 1. Set up variables
$search_term = $_GET['search'] ?? '';
$limit_options = [9, 12, 15];
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
    $join_sql .= " JOIN `request_approvers` ra ON ra.request_inv_no = r.request_inv_no AND ra.request_type_id = ? ";
    $params[] = $request_type_id;
    $types .= "i";
    
    $where_clauses[] = "ra.approver_id = ?";
    $params[] = $empid; // $empid is from session_check.php
    $types .= "i";

        // Treat both 'pending' (current level) and 'awaiting' (legacy/edge cases) as actionable
        $where_clauses[] = "ra.status IN ('pending','awaiting')";
    
} elseif ($current_filter === 'my_team') {
    // Assigned direct reports only (employees.supervisor_id) - not department-based.
    $where_clauses[] = "e.supervisor_id = ?";
    $params[] = $empid;
    $types .= "i";

} elseif ($current_filter === 'my_dept') {
    // Show all requests from the user's department
    $where_clauses[] = "e.dept = ?";
    $params[] = $user_dept;
    $types .= "i";
    $dept_filter_applied = true;

} elseif (in_array($current_filter, ['pending', 'approved', 'rejected'])) {
    // Filter by the main status on the resignation table
    $where_clauses[] = "r.status = ?";
    $params[] = $current_filter;
    $types .= "s";
    
    // For 'approved' status: show only last 30 days if no search term is provided
    if ($current_filter === 'approved' && empty($search_term)) {
        $where_clauses[] = "r.created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)";
    }
}
// 'all' adds no WHERE clause, but also applies 30-day filter for approved records when no search
elseif ($current_filter === 'all' && empty($search_term)) {
    // When viewing 'all' without search, limit approved records to last 15 days
    $where_clauses[] = "(r.status != 'approved' OR r.created_at >= DATE_SUB(CURDATE(), INTERVAL 15 DAY))";
}

// Add search term if provided
if (!empty($search_term)) {
    $where_clauses[] = "(e.name LIKE ? OR r.emp_id LIKE ? OR r.id LIKE ?)";
    $search_param = "%{$search_term}%";
    array_push($params, $search_param, $search_param, $search_param);
    $types .= "sss";
}

// Enforce department scoping: Only HR and System Admin can see all departments.
// Everyone else is restricted to their own department for history views.
if (!$can_see_all_depts && !$dept_filter_applied && $current_filter !== 'my_pending' && $current_filter !== 'my_team') {
    // Restrict to user's department OR any request where current user is in approval chain
    $where_clauses[] = "(e.dept = ? OR EXISTS (SELECT 1 FROM request_approvers ra_any WHERE ra_any.request_inv_no = r.request_inv_no AND ra_any.request_type_id = ? AND ra_any.approver_id = ?))";
    array_push($params, $user_dept, $request_type_id, $empid);
    $types .= "iii";
    $dept_filter_applied = true;
}

$where_sql = "";
if (!empty($where_clauses)) {
    $where_sql = " WHERE " . implode(" AND ", $where_clauses);
}

// Add company/department/employee scoping filters, except for 'my_pending'/'my_team':
// those are already scoped by explicit approver_id / supervisor_id assignment, so a
// supervisor overseeing someone outside their own department/company must still see them.
if ($current_filter === 'my_pending' || $current_filter === 'my_team') {
    $company_filter = "";
    $department_filter = "";
    $employee_filter = "";
} else {
    $company_filter = getCompanyFilterSQL('e.comp_no', true);
    $department_filter = getDepartmentFilterSQL('e.dept', true);
    $employee_filter = getEmployeeFilterSQL('e.emp_id', true);
}
if (strpos($where_sql, 'WHERE') === false) {
    $where_sql = " WHERE 1=1" . $company_filter . $department_filter . $employee_filter;
} else {
    $where_sql .= $company_filter . $department_filter . $employee_filter;
}

// Main query to select *which* resignations to show (for count and main data)
$base_query = "FROM emp_resignations r 
               JOIN employees e ON r.emp_id = e.emp_id 
               $join_sql 
               $where_sql";

$count_sql = "SELECT COUNT(DISTINCT r.id) as total " . $base_query;
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
    // This query fetches the full data *including* approval chain details
    $sql = "SELECT 
        r.*, 
        r.request_inv_no,
        e.name as employee_name,
        e.emp_id as employee_id,
        e.iqama,
        e.dept,
        e.supervisor_id,
        d.dep_nme as department,
        j.job as designation,
        ra_pending.approver_id as current_approver_id,
        approver_emp.name as current_approver_name,
        ra_pending.approval_level as current_approval_level,
        ra_pending.status as current_approval_status,
        supervisor_emp.name as supervisor_name
    FROM emp_resignations r 
    JOIN employees e ON r.emp_id = e.emp_id
    LEFT JOIN department d ON d.id = e.dept
    LEFT JOIN ac_jobs j ON j.id = e.actual_job
    
    -- This JOIN finds the current pending/awaiting approver
    LEFT JOIN request_approvers ra_pending ON ra_pending.request_inv_no = r.request_inv_no 
         AND ra_pending.request_type_id = ? AND (ra_pending.status = 'pending' OR ra_pending.status = 'awaiting')
    LEFT JOIN employees approver_emp ON ra_pending.approver_id = approver_emp.emp_id
    
    -- This JOIN gets the supervisor information
    LEFT JOIN employees supervisor_emp ON e.supervisor_id = supervisor_emp.emp_id
    
    -- This JOIN is for the 'my_pending' filter
    $join_sql
    
    $where_sql";
    
    $sql .= " GROUP BY r.id ORDER BY r.created_at DESC"; // Group by r.id to avoid duplicates

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
    $unfiltered_sql = "SELECT COUNT(id) as total FROM emp_resignations";
    $unfiltered_result = mysqli_query($conDB, $unfiltered_sql);
    $unfiltered_total_items = ($unfiltered_result && ($row_unf = mysqli_fetch_assoc($unfiltered_result))) ? ($row_unf['total'] ?? 0) : 0;
} else {
    // Respect the same scoping (dept OR in approval chain)
    $unfiltered_sql = "SELECT COUNT(r.id) as total FROM emp_resignations r JOIN employees e ON r.emp_id = e.emp_id WHERE (e.dept = ? OR EXISTS (SELECT 1 FROM request_approvers ra_any WHERE ra_any.request_inv_no = r.request_inv_no AND ra_any.request_type_id = ? AND ra_any.approver_id = ?))";
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
        <title><?= $site_title ?? 'Al-Mutlak WMS' ?> - <?=__('all_resignation_requests')?></title>
        <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
        <meta content="Anees Afzal" name="author" />
        <meta http-equiv="X-UA-Compatible" content="IE=edge" />
        <link rel="shortcut icon" href="<?=get_setting($conDB, 'favicon')?>">
        <link href="./plugins/custombox/css/custombox.min.css" rel="stylesheet">
        <!-- Select2 -->
        <link href="./plugins/select2/css/select2.min.css" rel="stylesheet" type="text/css" />
        <link href="assets/css/bootstrap.min.css" rel="stylesheet" type="text/css" />
        <link href="assets/css/icons.css" rel="stylesheet" type="text/css" />
        <link href="assets/css/metismenu.min.css" rel="stylesheet" type="text/css" />
        <link href="assets/css/style.css" rel="stylesheet" type="text/css" />
        <link href="assets/css/style_dark.css" rel="stylesheet" type="text/css" />
        <link href="assets/css/smart_request.css?v=<?= @filemtime(__DIR__ . '/assets/css/smart_request.css') ?>" rel="stylesheet" type="text/css" />
        <script src="assets/js/modernizr.min.js"></script>
        <style>
            .detail-item { display: flex; align-items: center; margin-bottom: 1rem; font-size: 1.09em; }
            .detail-item i.fad { margin-right: 15px; width: 20px; text-align: center; font-size: 1.2em; }
            .detail-item i.duotone-info { --fa-primary-color: #4a90e2; --fa-secondary-color: #a8d0ff; --fa-secondary-opacity: 0.4; }
            .detail-item strong { color: #8a94a6; min-width: 130px; display: inline-block; }

            /* --- Resignation: "View" details modal --- */
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

            /* Resignation Approval Wizard Custom Styles */
            .resignation-wizard-popup .swal2-popup { font-family: inherit; }
            .resignation-approval-wizard { text-align: left; padding: 10px; }
            .resignation-approval-wizard .wizard-section { background: #f8f9fa; border-radius: 8px; padding: 20px; margin-bottom: 15px; }
            .resignation-approval-wizard .section-title { color: #333; font-size: 16px; font-weight: 600; margin-bottom: 15px; padding-bottom: 10px; border-bottom: 2px solid #007bff; }
            .resignation-approval-wizard .section-title i { color: #007bff; margin-right: 8px; }
            .resignation-approval-wizard .info-table { width: 100%; border-collapse: collapse; }
            .resignation-approval-wizard .info-table tr { border-bottom: 1px solid #dee2e6; }
            .resignation-approval-wizard .info-table tr:last-child { border-bottom: none; }
            .resignation-approval-wizard .info-table td { padding: 10px 5px; }
            .resignation-approval-wizard .info-table td.label { font-weight: 600; color: #495057; width: 40%; }
            .resignation-approval-wizard .info-table td.value { color: #212529; width: 60%; }
            .resignation-details-popup .resignation-details .details-section { margin-bottom: 20px; }
            .resignation-details-popup .resignation-details h5 { font-size: 16px; margin-bottom: 12px; }
            .custom-control-label { font-size: 15px; padding-left: 5px; }
            .custom-radio .custom-control-label i { margin-right: 5px; }
            .resignation-approval-wizard .form-control:focus { border-color: #007bff; box-shadow: 0 0 0 0.2rem rgba(0,123,255,.25); }
            .resignation-approval-wizard .datepicker { cursor: pointer; }
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
                <?php require_once __DIR__ . '/includes/sr_list_helpers.php'; ?>
                <div class="content sr-page">
                    <div class="container-fluid">

                        <div class="sr-head">
                            <div>
                                <h1><?=__('resignation_approval_center')?></h1>
                                <p><?= str_replace('{0}', (string)(int)$total_items, __('showing_requests')) ?></p>
                            </div>
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
                                                <th><?=__('department')?></th>
                                                <th><?=__('last_working_day')?></th>
                                                <th><?=__('status')?></th>
                                                <th><?=__('submitted')?></th>
                                                <th class="text-right"><?=__('actions')?></th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($requests as $resignation):
                                                // Get approval chain details
                                                $approval_chain = [];
                                                $approver_stmt = $conDB->prepare("
                                                    SELECT ra.approval_level, ra.status, ra.approver_id, e.name as approver_name
                                                    FROM request_approvers ra
                                                    LEFT JOIN employees e ON ra.approver_id = e.emp_id
                                                    WHERE ra.request_inv_no = ? AND ra.request_type_id = ?
                                                    ORDER BY ra.approval_level ASC
                                                ");
                                                if ($approver_stmt) {
                                                    $approver_stmt->bind_param('si', $resignation['request_inv_no'], $request_type_id);
                                                    $approver_stmt->execute();
                                                    $approver_result = $approver_stmt->get_result();
                                                    while ($approver_row = $approver_result->fetch_assoc()) {
                                                        $approval_chain[] = $approver_row;
                                                    }
                                                    $approver_stmt->close();
                                                }

                                                // Determine if current user has pending approval
                                                $user_has_pending_approval = false;
                                                foreach ($approval_chain as $approval) {
                                                    // Check for both 'pending' (first level) and 'awaiting' (subsequent levels) statuses
                                                    if (in_array($approval['status'], ['pending', 'awaiting'])) {
                                                        $awaiting_approver_id = $approval['approver_id'] ?? null;
                                                        if ($awaiting_approver_id == $empid || ($delegatedFromEmpId !== null && $awaiting_approver_id == $delegatedFromEmpId)) {
                                                            $user_has_pending_approval = true;
                                                            break;
                                                        }
                                                    }
                                                }

                                                // Only show action buttons if current user has pending approval
                                                $can_take_action = $user_has_pending_approval;
                                                $can_cancel_this_resignation = $can_cancel_resignation_requests && in_array($resignation['status'], $cancellable_resignation_statuses, true);

                                                // Prepare JS-safe variables - remove line breaks and escape quotes
                                                $employee_name_js = htmlspecialchars(str_replace(["\r", "\n"], ' ', addslashes($resignation['employee_name'])), ENT_QUOTES);
                                                $employee_id_js = htmlspecialchars(str_replace(["\r", "\n"], ' ', $resignation['employee_id']), ENT_QUOTES);
                                                $iqama_js = htmlspecialchars(str_replace(["\r", "\n"], ' ', addslashes($resignation['iqama'])), ENT_QUOTES);
                                                $designation_js = htmlspecialchars(str_replace(["\r", "\n"], ' ', addslashes($resignation['designation'] ?? 'N/A')), ENT_QUOTES);
                                                $department_js = htmlspecialchars(str_replace(["\r", "\n"], ' ', addslashes($resignation['department'] ?? 'N/A')), ENT_QUOTES);

                                                // Status + current/next approver
                                                $status_tone = 'slate';
                                                switch ($resignation['status']) {
                                                    case 'pending':
                                                        $status_tone = 'amber';
                                                        $status_text = __('pending');
                                                        foreach ($approval_chain as $approval) {
                                                            if ($approval['status'] === 'pending' || $approval['status'] === 'awaiting') {
                                                                $status_text .= " - Level " . $approval['approval_level'] . ": " . ($approval['approver_name'] ?? 'N/A');
                                                                break;
                                                            }
                                                        }
                                                        break;
                                                    case 'approved':
                                                        $status_tone = 'green';
                                                        $status_text = __('approved');
                                                        break;
                                                    case 'rejected':
                                                        $status_tone = 'red';
                                                        $status_text = __('rejected');
                                                        break;
                                                    case 'cancelled':
                                                        $status_tone = 'slate';
                                                        $status_text = __('cancelled');
                                                        break;
                                                    default:
                                                        $status_text = __($resignation['status']);
                                                        break;
                                                }
                                                $report_url = 'resignation_report_details.php?id=' . (int)$resignation['id'] . '&emp_id=' . urlencode($resignation['employee_id']);
                                                $view_attrs = 'data-id="' . (int)$resignation['id'] . '"'
                                                    . ' data-emp-id="' . sr_h($resignation['employee_id']) . '"'
                                                    . ' data-iqama="' . sr_h($resignation['iqama']) . '"'
                                                    . ' data-name="' . sr_h($resignation['employee_name']) . '"'
                                                    . ' data-designation="' . sr_h($resignation['designation'] ?? 'N/A') . '"'
                                                    . ' data-department="' . sr_h($resignation['department'] ?? 'N/A') . '"'
                                                    . ' data-last-day="' . sr_h($resignation['last_working_day']) . '"'
                                                    . ' data-status="' . sr_h($resignation['status']) . '"';
                                            ?>
                                                <tr>
                                                    <td><?= sr_person_cell($resignation['employee_name'], $resignation['employee_id']) ?></td>
                                                    <td>
                                                        <span class="sr-cell-title"><?= sr_h($resignation['department'] ?? 'N/A') ?></span>
                                                        <span class="sr-cell-sub"><i class="mdi mdi-briefcase"></i> <?= sr_h($resignation['designation'] ?? 'N/A') ?></span>
                                                    </td>
                                                    <td class="sr-nowrap"><?= sr_pill('red', format_safe_date($resignation['last_working_day'] ?? null, 'd M Y'), true, 'mdi-calendar-remove') ?></td>
                                                    <td><?= sr_pill($status_tone, $status_text) ?></td>
                                                    <td><?= sr_time_cell($resignation['created_at'] ?? '') ?></td>
                                                    <td class="text-right">
                                                        <div class="sr-actions">
                                                            <?php if ($can_take_action): ?>
                                                                <a href="javascript:void(0);" class="sr-open-btn" onclick="approveResignation(<?=$resignation['id']; ?>, '<?=$employee_id_js; ?>', '<?=$employee_name_js; ?>', '<?=$iqama_js; ?>', '<?=$designation_js; ?>', '<?=$department_js; ?>', '<?=$resignation['last_working_day']; ?>')"><i class="mdi mdi-check"></i> <?=__('approve')?></a>
                                                            <?php else: ?>
                                                                <a href="javascript:void(0);" class="sr-open-btn viewResignation" <?= $view_attrs ?>><i class="mdi mdi-eye"></i> <?=__('view')?></a>
                                                            <?php endif; ?>
                                                            <div class="btn-group dropdown">
                                                                <a href="javascript:void(0);" class="sr-more-btn dropdown-toggle arrow-none" data-toggle="dropdown" data-boundary="viewport" aria-haspopup="true" aria-expanded="false"><i class="mdi mdi-dots-vertical"></i></a>
                                                                <div class="dropdown-menu dropdown-menu-right">
                                                                    <?php if ($can_take_action): ?>
                                                                        <a class="dropdown-item viewResignation" href="javascript:void(0);" <?= $view_attrs ?>><i class="mdi mdi-eye"></i><?=__('view')?></a>
                                                                    <?php endif; ?>
                                                                    <a class="dropdown-item" href="<?= sr_h($report_url) ?>" target="_blank"><i class="mdi mdi-file-pdf"></i><?=__('report')?></a>
                                                                    <?php if ($can_take_action): ?>
                                                                        <div class="dropdown-divider"></div>
                                                                        <a class="dropdown-item" href="javascript:void(0);" onclick="rejectResignation(<?=$resignation['id']; ?>, '<?=$employee_name_js; ?>')"><i class="mdi mdi-close text-danger"></i><?=__('reject')?></a>
                                                                    <?php endif; ?>
                                                                    <?php if ($can_cancel_this_resignation): ?>
                                                                        <div class="dropdown-divider"></div>
                                                                        <a class="dropdown-item text-danger" href="javascript:void(0);" onclick="cancelResignationAdmin(<?=$resignation['id']; ?>, '<?=$employee_name_js; ?>')"><i class="mdi mdi-cancel"></i><?=__('cancel', 'Cancel')?></a>
                                                                    <?php endif; ?>
                                                                </div>
                                                            </div>
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
                                        echo generate_pagination_controls($current_page,$total_pages,$total_items,$items_per_page,$limit_options,$show_all,$pagination_params,$unfiltered_total_items);
                                    ?>
                                </div>
                            <?php else: ?>
                                <?= sr_empty_state(__('no_resignations_found'), __('try_changing_filters')) ?>
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
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
        <!-- Select2 -->
        <script src="./plugins/select2/js/select2.min.js"></script>
        <script src="assets/js/jquery.core.js"></script>
        <script src="assets/js/jquery.app.js?t=<?= time() ?>"></script>
        <?= sr_list_js() ?>
        <script src="assets/js/resignationApprovalWizard.js"></script>
        <script src="assets/js/resignationApproval.js"></script>
        <script>
            function applyFilters() {
                const status = document.getElementById('statusFilter').value;
                const limitElement = document.getElementById('limitFilter');
                const limit = limitElement ? limitElement.value : <?= $perpage ?>;
                const search = document.getElementById('searchFilter').value;
                const baseUrl = window.location.href.split('?')[0];
                window.location.href = `${baseUrl}?status=${status}&limit=${limit}&search=${encodeURIComponent(search)}&page=1`;
            }
        </script>
    </body>
    </html>
<?php
    $conDB->close();
?>
