<?php
require_once __DIR__ . '/includes/session_check.php';
require_once __DIR__ . '/includes/special_access_helper.php';
require_once __DIR__ . '/includes/business_trip_settlement_helper.php';

// Fallback for servers still on an older business_trip_settlement_helper.php.
if (!function_exists('cleanBusinessTripText')) {
    function cleanBusinessTripText($value)
    {
        return trim(str_replace(['\\r\\n', '\\n', '\\r', "\\'", '\\"'], ["\n", "\n", "\n", "'", '"'], (string)$value));
    }
}

// Restrict access: Employees cannot view this detailed report page,
// unless explicitly granted via app_settings -> Special Access.
if (
    isset($isEmployee) && $isEmployee === true
    && !user_has_special_access($conDB, $empid ?? '', 'access_all_applied_business_trip', $user_role ?? '', $user_type ?? '', $is_system_admin ?? false)
) {
    header("Location: ./profile.php");
    exit();
}

// A temp-role replacement can act on the original employee's pending approvals too -
// request_approvers.approver_id / current_approver_id still point at the original employee.
$delegatedFromEmpId = getDelegatedFromEmpId($conDB, $empid ?? '');

$can_cancel_business_trip_requests = (
    !empty($is_system_admin)
    || user_has_special_access($conDB, $empid ?? '', 'cancel_business_trip_requests', $user_role ?? '', $user_type ?? '', $is_system_admin ?? false)
);
$cancellable_business_trip_statuses = ['pending_approval', 'approved'];

// --- Get Request Type ID for 'business_trip' ---
$type_query = mysqli_query($conDB, "SELECT `id` FROM `approval_request_types` WHERE `type_name` = 'business_trip' LIMIT 1");
if (!$type_query || mysqli_num_rows($type_query) == 0) {
    die("CRITICAL ERROR: 'business_trip' type not found in `approval_request_types` table.");
}
$request_type_id = (int)mysqli_fetch_assoc($type_query)['id'];

$all_statuses = [
    'my_pending' => __('my_pending_queue'),
    'my_team' => (function_exists('__') ? __('my_team_requests') : 'My Team'),
    'my_dept' => __('my_department_requests'),
    'pending_approval' => __('all_pending'),
    'approved' => __('approved'),
    'rejected' => __('rejected'),
    'completed' => __('completed'),
    'all' => __('all_requests')
];

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
if ($current_filter === null) {
    $current_filter = ($is_system_admin ?? false) ? 'all' : 'my_pending';
}

// Filter + count block, wrapped in a closure so the status cards can count every status
// with exactly the same rules (sr_status_counts); the current status runs it once as before.
require_once __DIR__ . '/includes/sr_list_helpers.php';
$sr_scope = sr_scope_vars(get_defined_vars());
$sr_build_filter = function ($current_filter) use ($sr_scope) {
    extract($sr_scope, EXTR_SKIP);
$where_clauses = [];
$params = [];
$types = "";
$join_sql = "";
$dept_filter_applied = false;
$isFinanceRole = (isset($user_type) && stripos($user_type, 'finance') !== false);
$isHRPayrollRole = (isset($user_type) && stripos($user_type, 'hr_payroll') !== false);
$can_see_all_depts = ($is_system_admin ?? false) || ($isHR ?? false) || $isFinanceRole;

$page_title = $all_statuses[$current_filter] ?? __('all_requests');

if ($current_filter === 'my_pending') {
    $join_sql .= " JOIN `request_approvers` ra ON ra.request_inv_no = bt.request_inv_no AND ra.request_type_id = ? ";
    $params[] = $request_type_id;
    $types .= "i";

    $where_clauses[] = "ra.approver_id IN (?, {$approver_alt_id})";
    $params[] = $empid;
    $types .= "i";

    $where_clauses[] = "ra.status = 'pending'";
    $where_clauses[] = "bt.current_status = 'pending_approval'";
} elseif ($current_filter === 'my_team') {
    // Assigned direct reports only (employees.supervisor_id) - not department-based.
    $where_clauses[] = "e.supervisor_id = ?";
    $params[] = $empid;
    $types .= "i";
} elseif ($current_filter === 'my_dept') {
    $where_clauses[] = "e.dept = ?";
    $params[] = $user_dept;
    $types .= "i";
    $dept_filter_applied = true;
} elseif (in_array($current_filter, ['pending_approval', 'approved', 'rejected', 'completed'], true)) {
    $where_clauses[] = "bt.current_status = ?";
    $params[] = $current_filter;
    $types .= "s";
    if ($current_filter === 'approved' && empty($search_term)) {
        $where_clauses[] = "bt.created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)";
    }
} elseif ($current_filter === 'all' && empty($search_term)) {
    $where_clauses[] = "(bt.current_status != 'approved' OR bt.created_at >= DATE_SUB(CURDATE(), INTERVAL 15 DAY))";
}

if (!empty($search_term)) {
    $where_clauses[] = "(e.name LIKE ? OR bt.emp_id LIKE ? OR bt.request_inv_no LIKE ? OR bt.trip_purpose LIKE ?)";
    $search_param = "%{$search_term}%";
    array_push($params, $search_param, $search_param, $search_param, $search_param);
    $types .= "ssss";
}

if (!$can_see_all_depts && !$dept_filter_applied && $current_filter !== 'my_pending' && $current_filter !== 'my_team') {
    $where_clauses[] = "(e.dept = ? OR EXISTS (SELECT 1 FROM request_approvers ra_any WHERE ra_any.request_inv_no = bt.request_inv_no AND ra_any.request_type_id = ? AND ra_any.approver_id IN (?, {$approver_alt_id})))";
    array_push($params, $user_dept, $request_type_id, $empid);
    $types .= "iii";
    $dept_filter_applied = true;
}

$where_sql = "";
if (!empty($where_clauses)) {
    $where_sql = " WHERE " . implode(" AND ", $where_clauses);
}

$company_filter = getCompanyFilterSQL('e.comp_no', true);
$department_filter = getDepartmentFilterSQL('e.dept', true);
$employee_filter = getEmployeeFilterSQL('e.emp_id', true);
if ($current_filter !== 'my_pending' && $current_filter !== 'my_team') {
    if (strpos($where_sql, 'WHERE') === false) {
        $where_sql = " WHERE 1=1" . $company_filter . $department_filter . $employee_filter;
    } else {
        $where_sql .= $company_filter . $department_filter . $employee_filter;
    }
}

$base_query = "FROM emp_business_trip bt
               JOIN employees e ON bt.emp_id = e.emp_id
               $join_sql
               $where_sql";

$count_sql = "SELECT COUNT(DISTINCT bt.id) as total " . $base_query;
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
    return sr_scope_vars(get_defined_vars());
};
extract($sr_build_filter($current_filter));
$sr_status_counts = sr_status_counts($all_statuses, $current_filter, $total_items, $sr_build_filter, 'total_items');

$total_pages = $show_all ? 1 : ceil($total_items / $items_per_page);
if ($current_page > $total_pages && $total_pages > 0) {
    $current_page = $total_pages;
}

$requests = [];
if ($total_items > 0) {
    $sql = "SELECT
        bt.*,
        e.name as employee_name,
        e.dept,
        ra_pending.approver_id as current_approver_id,
        ra_pending.approval_level as current_approval_level,
        approver_emp.name as current_approver_name,
        ra_rejected.note as rejection_note,
        fc.name_en as from_city_name_en,
        fc.name_ar as from_city_name_ar,
        tc.name_en as to_city_name_en,
        tc.name_ar as to_city_name_ar,
        (CASE WHEN ba.id IS NOT NULL THEN 1 ELSE 0 END) as has_allowance_record,
        (SELECT sr.id FROM settlement_records sr WHERE sr.request_inv_no = CONCAT('SETL-', bt.request_inv_no) LIMIT 1) as settlement_id
    FROM emp_business_trip bt
    JOIN employees e ON bt.emp_id = e.emp_id
    LEFT JOIN request_approvers ra_pending ON ra_pending.request_inv_no = bt.request_inv_no AND ra_pending.request_type_id = ? AND ra_pending.status = 'pending'
    LEFT JOIN employees approver_emp ON ra_pending.approver_id = approver_emp.emp_id
    LEFT JOIN request_approvers ra_rejected ON ra_rejected.request_inv_no = bt.request_inv_no AND ra_rejected.request_type_id = ? AND ra_rejected.status = 'rejected'
    LEFT JOIN saudi_cities fc ON bt.from_city_id = fc.id
    LEFT JOIN saudi_cities tc ON bt.to_city_id = tc.id
    LEFT JOIN emp_business_trip_allowances ba ON ba.trip_id = bt.id
    $join_sql
    $where_sql";
    $sql .= " GROUP BY bt.id ORDER BY bt.created_at DESC";

    $main_params = $params;
    $main_types = $types;
    array_unshift($main_params, $request_type_id);
    array_unshift($main_params, $request_type_id);
    $main_types = "ii" . $main_types;

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

if ($can_see_all_depts) {
    $unfiltered_sql = "SELECT COUNT(id) as total FROM emp_business_trip";
    $unfiltered_result = mysqli_query($conDB, $unfiltered_sql);
    $unfiltered_total_items = ($unfiltered_result && ($row_unf = mysqli_fetch_assoc($unfiltered_result))) ? ($row_unf['total'] ?? 0) : 0;
} else {
    $unfiltered_sql = "SELECT COUNT(bt.id) as total FROM emp_business_trip bt JOIN employees e ON bt.emp_id = e.emp_id WHERE (e.dept = ? OR EXISTS (SELECT 1 FROM request_approvers ra_any WHERE ra_any.request_inv_no = bt.request_inv_no AND ra_any.request_type_id = ? AND ra_any.approver_id IN (?, {$approver_alt_id})))";
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
    <title><?= $site_title ?? 'System' ?> - <?= __('business_trip_requests') ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta content="Anees Afzal" name="author" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge" />
    <link rel="shortcut icon" href="<?= get_setting($conDB, 'favicon') ?>">
    <link href="assets/css/bootstrap.min.css" rel="stylesheet" type="text/css" />
    <link href="plugins/bootstrap-datepicker/css/bootstrap-datepicker.min.css" rel="stylesheet" type="text/css" />
    <link href="assets/css/icons.css" rel="stylesheet" type="text/css" />
    <link href="assets/css/metismenu.min.css" rel="stylesheet" type="text/css" />
    <link href="assets/css/style.css" rel="stylesheet" type="text/css" />
    <link href="assets/css/style_dark.css" rel="stylesheet" type="text/css" />
    <link href="assets/css/smart_request.css?v=<?= @filemtime(__DIR__ . '/assets/css/smart_request.css') ?>" rel="stylesheet" type="text/css" />
    <script src="assets/js/modernizr.min.js"></script>
    <style>
        .detail-item { display: flex; align-items: center; margin-bottom: 1rem; font-size: 1.03em; }
        .detail-item i { color: #4a90e2; margin-right: 15px; width: 20px; text-align: center; }
        .detail-item strong { color: #8a94a6; min-width: 140px; display: inline-block; }
        .detail-item { flex-direction: <?= ($is_rtl) ? 'row-reverse !important' : 'row !important' ?>; text-align: <?= ($is_rtl) ? 'right !important' : 'left !important' ?>; }
    </style>
    <?php if ($is_rtl): ?>
        <link href="assets/css/style_rtl.css" rel="stylesheet" type="text/css" />
    <?php endif; ?>
    <script>window.lang = <?= json_encode($GLOBALS['translations'] ?? []) ?>;</script>
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
            <?php require_once __DIR__ . '/includes/sr_list_helpers.php'; ?>
            <div class="content sr-page">
                <div class="container-fluid">

                    <div class="sr-head">
                        <div>
                            <h1><?= __('business_trip_approval_center') ?></h1>
                            <p><?= sr_h($page_title) ?> &middot; <?= __('total_found') ?>: <?= (int)$total_items ?></p>
                        </div>
                    </div>

                    <?= sr_status_tabs($all_statuses, $current_filter, $total_items, $sr_status_counts) ?>
                    <div class="sr-card">
                        <?= sr_list_toolbar($search_term, (!empty($search_term) || $current_filter !== 'my_pending') ? 'resetFilters(' . (int)$perpage . ')' : '') ?>

                        <?php if (!empty($requests)): ?>
                            <div class="sr-table-wrap sr-list-wrap">
                                <table class="table sr-table sr-list" style="width: 100%;">
                                    <thead>
                                        <tr>
                                            <th><?= __('employee', 'Employee') ?></th>
                                            <th><?= __('destination') ?></th>
                                            <th><?= __('trip_dates') ?></th>
                                            <th><?= __('purpose') ?></th>
                                            <th><?= __('status') ?></th>
                                            <th><?= __('applied') ?></th>
                                            <th class="text-right"><?= __('actions') ?></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($requests as $trip): ?>
                                            <?php
                                            $is_intl = ($trip['trip_type'] === 'international');
                                            $trip_type_text = $is_intl ? __('international') : __('domestic');
                                            $route_text = '';
                                            if ($is_intl) {
                                                $route_text = ($trip['destination_country'] ?? 'N/A');
                                            } else {
                                                $from_name = ($is_rtl ?? false) ? ($trip['from_city_name_ar'] ?? '') : ($trip['from_city_name_en'] ?? '');
                                                $to_name = ($is_rtl ?? false) ? ($trip['to_city_name_ar'] ?? '') : ($trip['to_city_name_en'] ?? '');
                                                $route_text = trim((string)$from_name) . ' → ' . trim((string)$to_name);
                                            }

                                            $status_tone = 'slate';
                                            $status_text = '';
                                            $current_level_display = '';

                                            if (!empty($trip['current_approver_name']) && $trip['current_status'] === 'pending_approval') {
                                                $status_text = __('pending_with') . ' ' . getDisplayName(parseName($trip['current_approver_name']));
                                                $status_tone = 'amber';
                                                if (!empty($trip['current_approval_level'])) {
                                                    $current_level_display = ' (Level ' . (int)$trip['current_approval_level'] . ')';
                                                }
                                            } elseif ($trip['current_status'] === 'approved') {
                                                $status_text = __('approved');
                                                $status_tone = 'green';
                                            } elseif ($trip['current_status'] === 'rejected') {
                                                $status_text = __('rejected');
                                                $status_tone = 'red';
                                            } elseif ($trip['current_status'] === 'completed') {
                                                $status_text = __('completed');
                                                $status_tone = 'indigo';
                                            } else {
                                                $status_text = __('pending_approval');
                                                $status_tone = 'amber';
                                            }

                                            $can_take_action = ((int)($trip['current_approver_id'] ?? 0) === (int)$empid || ($delegatedFromEmpId !== null && (int)($trip['current_approver_id'] ?? 0) === (int)$delegatedFromEmpId)) && (($trip['current_status'] ?? '') === 'pending_approval');

                                            $is_approved = (($trip['current_status'] ?? '') === 'approved');
                                            $is_by_air = (($trip['transportation_type'] ?? '') === 'by_air');
                                            $show_travel_email = $is_approved && $is_by_air && empty($trip['travel_email_sent']);
                                            $show_ticket_fare = $is_approved && $is_by_air && !empty($trip['travel_email_sent']) && $isHRPayrollRole && empty($trip['has_allowance_record']);
                                            // Settlement: HR Payroll, once approved. Creating it moves the trip to completed.
                                            $has_settlement = !empty($trip['settlement_id']);
                                            $show_create_settlement = $is_approved && !$has_settlement && ($isHRPayrollRole || !empty($is_system_admin));

                                            $id = (int)$trip['id'];
                                            $name_js = htmlspecialchars((string)$trip['employee_name'], ENT_QUOTES);
                                            $inv_js = htmlspecialchars((string)$trip['request_inv_no'], ENT_QUOTES);
                                            $type_js = htmlspecialchars((string)$trip_type_text, ENT_QUOTES);
                                            $start_js = htmlspecialchars((string)$trip['trip_start_date'], ENT_QUOTES);
                                            $end_js = htmlspecialchars((string)$trip['trip_end_date'], ENT_QUOTES);
                                            $report_url = 'business_trip_report_details.php?id=' . $id . '&emp_id=' . urlencode((string)$trip['emp_id']);
                                            ?>
                                            <tr>
                                                <td><?= sr_person_cell(getDisplayName(parseName($trip['employee_name'])), $trip['emp_id']) ?></td>
                                                <td>
                                                    <span class="sr-cell-title"><?= sr_h($route_text) ?></span>
                                                    <span class="sr-cell-sub"><i class="mdi <?= $is_intl ? 'mdi-earth' : 'mdi-map-marker' ?>"></i> <?= sr_h($trip_type_text) ?><?php if ($is_by_air): ?> &middot; <i class="mdi mdi-airplane"></i><?php endif; ?></span>
                                                    <span class="sr-cell-sub sr-mono"><?= sr_h($trip['request_inv_no']) ?></span>
                                                </td>
                                                <td class="sr-period">
                                                    <span class="sr-cell-title"><?= sr_h(format_safe_date($trip['trip_start_date'] ?? null, 'd M Y', 'N/A')) ?><i class="mdi mdi-arrow-right"></i><?= sr_h(format_safe_date($trip['trip_end_date'] ?? null, 'd M Y', 'N/A')) ?></span>
                                                </td>
                                                <td><div class="sr-wrap-text"><?= nl2br(sr_h(cleanBusinessTripText($trip['trip_purpose'] ?? '')), false) ?></div></td>
                                                <td>
                                                    <div class="sr-flags">
                                                        <?= sr_pill($status_tone, $status_text . $current_level_display) ?>
                                                        <?php if ($show_travel_email): ?>
                                                            <?= sr_pill('red', __('pending_travel_email') ?: 'Pending Travel Email', true, 'mdi-send') ?>
                                                        <?php endif; ?>
                                                    </div>
                                                    <?php if (($trip['current_status'] ?? '') === 'rejected' && !empty($trip['rejection_note'])): ?>
                                                        <div class="sr-reject-note"><strong><?= __('rejection_reason') ?>:</strong> <?= nl2br(sr_h(getDisplayName((string)$trip['rejection_note']))) ?></div>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?= sr_time_cell($trip['created_at'] ?? '') ?></td>
                                                <td class="text-right">
                                                    <div class="sr-actions">
                                                        <?php if ($can_take_action): ?>
                                                            <a href="javascript:void(0);" class="sr-open-btn" onclick="approveBusinessTripRequest('<?= $inv_js ?>', '<?= $name_js ?>', '<?= $type_js ?>', '<?= $start_js ?>', '<?= $end_js ?>')"><i class="mdi mdi-check"></i> <?= __('approve') ?></a>
                                                        <?php else: ?>
                                                            <a href="<?= sr_h($report_url) ?>" target="_blank" class="sr-open-btn"><i class="mdi mdi-file-pdf"></i> <?= __('report') ?></a>
                                                        <?php endif; ?>
                                                        <div class="btn-group dropdown">
                                                            <a href="javascript:void(0);" class="sr-more-btn dropdown-toggle arrow-none" data-toggle="dropdown" data-boundary="viewport" aria-haspopup="true" aria-expanded="false"><i class="mdi mdi-dots-vertical"></i></a>
                                                            <div class="dropdown-menu dropdown-menu-right">
                                                                <?php if ($can_take_action): ?>
                                                                    <a class="dropdown-item" href="<?= sr_h($report_url) ?>" target="_blank"><i class="mdi mdi-file-pdf"></i><?= __('report') ?></a>
                                                                <?php endif; ?>
                                                                <a class="dropdown-item" href="business_trip_status_history.php?request_inv_no=<?= urlencode($trip['request_inv_no']); ?>" target="_blank"><i class="mdi mdi-history"></i><?= __('history') ?></a>
                                                                <?php if ($can_take_action): ?>
                                                                    <div class="dropdown-divider"></div>
                                                                    <a class="dropdown-item" href="javascript:void(0);" onclick="rejectBusinessTripRequest('<?= $inv_js ?>', '<?= $name_js ?>', '<?= $type_js ?>', '<?= $start_js ?>', '<?= $end_js ?>')"><i class="mdi mdi-close text-danger"></i><?= __('reject') ?></a>
                                                                <?php endif; ?>
                                                                <?php if ($show_travel_email || $show_ticket_fare): ?>
                                                                    <div class="dropdown-divider"></div>
                                                                <?php endif; ?>
                                                                <?php if ($show_travel_email): ?>
                                                                    <a class="dropdown-item" href="javascript:void(0);" onclick="sendTravelEmailBusinessTrip(<?= $id ?>, '<?= $name_js ?>')"><i class="mdi mdi-send text-info"></i><?= __('send_travel_email') ?></a>
                                                                <?php endif; ?>
                                                                <?php if ($show_ticket_fare): ?>
                                                                    <a class="dropdown-item" href="javascript:void(0);" onclick="openHRPayrollTicketFareModal(<?= $id ?>, '<?= $name_js ?>')"><i class="mdi mdi-airplane text-primary"></i><?= __('add_ticket_fare_only') ?: 'HR Payroll Ticket Fare' ?></a>
                                                                <?php endif; ?>
                                                                <?php if ($show_create_settlement): ?>
                                                                    <div class="dropdown-divider"></div>
                                                                    <a class="dropdown-item" href="javascript:void(0);" onclick="createBusinessTripSettlement(<?= $id ?>, '<?= htmlspecialchars(addslashes(getDisplayName(parseName((string)$trip['employee_name']))), ENT_QUOTES) ?>')"><i class="mdi mdi-cash text-success"></i><?= __('create_settlement') ?: 'Create Settlement' ?></a>
                                                                <?php elseif ($has_settlement): ?>
                                                                    <div class="dropdown-divider"></div>
                                                                    <a class="dropdown-item" href="javascript:void(0);" onclick="viewSettlementDetails(<?= (int)$trip['settlement_id'] ?>, 'SETL-<?= $inv_js ?>')"><i class="mdi mdi-file-document text-info"></i><?= __('settlement_report') ?: 'Settlement Report' ?></a>
                                                                <?php endif; ?>
                                                                <?php if ($can_cancel_business_trip_requests && in_array($trip['current_status'] ?? '', $cancellable_business_trip_statuses, true)): ?>
                                                                    <div class="dropdown-divider"></div>
                                                                    <a class="dropdown-item text-danger" href="javascript:void(0);" onclick="cancelBusinessTripAdmin('<?= $inv_js ?>', '<?= $name_js ?>')"><i class="mdi mdi-cancel"></i><?= __('cancel', 'Cancel') ?></a>
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
                                echo generate_pagination_controls($current_page, $total_pages, $total_items, $items_per_page, $limit_options, $show_all, $pagination_params, $unfiltered_total_items);
                                ?>
                            </div>
                        <?php else: ?>
                            <?= sr_empty_state(__('no_business_trip_requests_found'), __('no_requests_matching_filters')) ?>
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
    <script src="plugins/bootstrap-datepicker/js/bootstrap-datepicker.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="assets/js/jquery.core.js"></script>
    <script src="assets/js/jquery.app.js?t=<?= time() ?>"></script>
        <?= sr_list_js() ?>
    <script src="assets/js/businessTrip.js?t=<?= time() ?>"></script>
    <script>

        function applyFilters() {
            const status = document.getElementById('statusFilter').value;
            const limitElement = document.getElementById('limitFilter');
            const limit = limitElement ? limitElement.value : <?= $perpage ?>;
            const search = document.getElementById('searchFilter').value;
            const baseUrl = window.location.href.split('?')[0];
            window.location.href = `${baseUrl}?status=${status}&limit=${limit}&search=${encodeURIComponent(search)}&page=1`;
        }


        function approveBusinessTripRequest(tripId, employeeName, tripType, startDate, endDate) {
            Swal.fire({
                title: __('confirm_approval') || 'Confirm Approval',
                html: `<div class="text-left">
                        <p><strong>${__('employee') || 'Employee'}:</strong> ${employeeName}</p>
                        <p><strong>${__('trip_type') || 'Trip Type'}:</strong> ${tripType}</p>
                        <p><strong>${__('trip_dates') || 'Trip Dates'}:</strong> ${startDate} → ${endDate}</p>
                    </div>`,
                input: 'textarea',
                inputLabel: __('add_approval_comment') || 'Approval Comment (Optional)',
                inputPlaceholder: __('enter_comment_here') || 'Enter comment...',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: APP_COLORS.success,
                cancelButtonColor: APP_COLORS.danger,
                confirmButtonText: __('approve') || 'Approve',
                cancelButtonText: __('cancel') || 'Cancel',
                showLoaderOnConfirm: true,
                allowOutsideClick: false,
                preConfirm: (approvalComment) => {
                    return new Promise((resolve, reject) => {
                        $.ajax({
                            url: './includes/ajaxFile/ajaxBusinessTrip.php',
                            type: 'POST',
                            dataType: 'JSON',
                            data: {
                                ajaxType: 'approveBusinessTrip',
                                trip_id: tripId,
                                approval_comment: approvalComment || ''
                            },
                            success: function (response) {
                                if (response.status === 'success') {
                                    resolve(response);
                                } else {
                                    reject(response.message || 'Approval failed');
                                }
                            },
                            error: function () {
                                reject('Failed to process approval request.');
                            }
                        });
                    }).catch(error => {
                        Swal.showValidationMessage(error);
                    });
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    Swal.fire({
                        title: result.value.title || 'Approved',
                        text: result.value.message || 'Business trip request approved successfully.',
                        icon: 'success',
                        confirmButtonColor: APP_COLORS.success,
                        allowOutsideClick: false
                    }).then(() => location.reload());
                }
            });
        }

        function rejectBusinessTripRequest(tripId, employeeName, tripType, startDate, endDate) {
            Swal.fire({
                title: __('confirm_rejection') || 'Confirm Rejection',
                html: `<div class="text-left">
                        <p><strong>${__('employee') || 'Employee'}:</strong> ${employeeName}</p>
                        <p><strong>${__('trip_type') || 'Trip Type'}:</strong> ${tripType}</p>
                        <p><strong>${__('trip_dates') || 'Trip Dates'}:</strong> ${startDate} → ${endDate}</p>
                    </div>`,
                input: 'textarea',
                inputLabel: __('provide_rejection_reason') || 'Provide rejection reason',
                inputPlaceholder: __('enter_reason_here') || 'Enter rejection reason...',
                inputAttributes: {
                    'aria-label': 'Rejection reason'
                },
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: APP_COLORS.danger,
                cancelButtonColor: APP_COLORS.secondary,
                confirmButtonText: __('reject') || 'Reject',
                cancelButtonText: __('cancel') || 'Cancel',
                showLoaderOnConfirm: true,
                allowOutsideClick: false,
                preConfirm: (rejectionReason) => {
                    if (!rejectionReason || rejectionReason.trim() === '') {
                        Swal.showValidationMessage(__('provide_rejection_reason') || 'Rejection reason is required');
                        return false;
                    }

                    return new Promise((resolve, reject) => {
                        $.ajax({
                            url: './includes/ajaxFile/ajaxBusinessTrip.php',
                            type: 'POST',
                            dataType: 'JSON',
                            data: {
                                ajaxType: 'rejectBusinessTrip',
                                trip_id: tripId,
                                rejection_reason: rejectionReason.trim()
                            },
                            success: function (response) {
                                if (response.status === 'success') {
                                    resolve(response);
                                } else {
                                    reject(response.message || 'Rejection failed');
                                }
                            },
                            error: function () {
                                reject('Failed to process rejection request.');
                            }
                        });
                    }).catch(error => {
                        Swal.showValidationMessage(error);
                    });
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    Swal.fire({
                        title: result.value.title || 'Rejected',
                        text: result.value.message || 'Business trip request rejected successfully.',
                        icon: 'success',
                        confirmButtonColor: APP_COLORS.success
                    }).then(() => location.reload());
                }
            });
        }

        function cancelBusinessTripAdmin(tripId, employeeName) {
            Swal.fire({
                title: __('cancel_business_trip_request') || 'Cancel Business Trip Request',
                html: `<p>${__('confirm_cancel_business_trip_for') || 'Are you sure you want to cancel the business trip request for'} <strong>${employeeName}</strong>?</p>`,
                input: 'textarea',
                inputLabel: __('cancellation_reason') || 'Cancellation reason',
                inputPlaceholder: __('enter_cancellation_reason_placeholder') || 'Enter reason for cancelling this request',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: APP_COLORS.danger,
                cancelButtonColor: APP_COLORS.secondary,
                confirmButtonText: __('yes_cancel') || 'Yes, Cancel',
                cancelButtonText: __('cancel') || 'Cancel',
                showLoaderOnConfirm: true,
                allowOutsideClick: false,
                preConfirm: (cancellationNote) => {
                    if (!cancellationNote || cancellationNote.trim() === '') {
                        Swal.showValidationMessage(__('cancellation_reason_required_validation') || 'Cancellation reason is required');
                        return false;
                    }

                    return new Promise((resolve, reject) => {
                        $.ajax({
                            url: './includes/ajaxFile/ajaxBusinessTrip.php',
                            type: 'POST',
                            dataType: 'JSON',
                            data: {
                                ajaxType: 'cancelBusinessTripAdmin',
                                trip_id: tripId,
                                cancellation_note: cancellationNote.trim()
                            },
                            success: function (response) {
                                if (response.status === 'success') {
                                    resolve(response);
                                } else {
                                    reject(response.message || 'Cancellation failed');
                                }
                            },
                            error: function () {
                                reject('Failed to process cancellation request.');
                            }
                        });
                    }).catch(error => {
                        Swal.showValidationMessage(error);
                    });
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    Swal.fire({
                        title: result.value.title || 'Cancelled',
                        text: result.value.message || 'Business trip request cancelled successfully.',
                        icon: 'success',
                        confirmButtonColor: APP_COLORS.success
                    }).then(() => location.reload());
                }
            });
        }

        function sendTravelEmailBusinessTrip(tripId, employeeName) {
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
                url: './includes/ajaxFile/ajaxBusinessTrip.php',
                type: 'POST',
                dataType: 'JSON',
                data: {
                    ajaxType: 'getTravelerDetailsBusinessTrip',
                    trip_id: tripId
                },
            })
            .done(function(response) {
                if (response.status === 'success' && response.data) {
                    const data = response.data;

                    // Compute passport validation
                    const missingPassport = !data.passport_number_raw || data.passport_number === 'Not Provided' || data.passport_number === 'N/A';
                    const depBase = data.trip_start_date_raw ? new Date(data.trip_start_date_raw) : new Date();
                    let expSoon = false;
                    if (data.passport_exp_raw) {
                        const formats = [/^(\d{4})-(\d{2})-(\d{2})$/, /^(\d{2})-(\d{2})-(\d{4})$/, /^(\d{2})\/(\d{2})\/(\d{4})$/];
                        let expDate = null;
                        for (let fmt of formats) {
                            if (fmt.test(data.passport_exp_raw)) {
                                expDate = new Date(data.passport_exp_raw);
                                break;
                            }
                        }
                        if (expDate) {
                            const diffMs = expDate.getTime() - depBase.getTime();
                            const diffDays = diffMs / (1000 * 60 * 60 * 24);
                            const diffMonths = diffDays / 30.44;
                            expSoon = diffMonths < 3;
                        } else {
                            expSoon = true;
                        }
                    } else {
                        expSoon = true;
                    }
                    const invalidPassport = missingPassport || expSoon;

                    // --- Traveler verification markup (styles: .tv-* in sr_swal_theme.css) ---
                    const tvEsc = (v) => $('<div>').text(v == null || v === '' ? '-' : String(v)).html();
                    const tvItem = (icon, label, value, strong) => `
                        <div class="tv-item">
                            <span class="tv-label"><i class="fa fa-${icon}"></i>${label}</span>
                            <span class="tv-value${strong ? ' is-strong' : ''}">${value}</span>
                        </div>`;
                    const passportWarningHtml = invalidPassport ? `
                        <div class="tv-alert is-err"><i class="fa fa-exclamation-circle"></i><span>
                            <strong>${__('passport_validation_issue') || 'Passport Issue:'}</strong>
                            ${missingPassport ? (__('passport_missing_message') || 'Passport number is missing.') : ''}
                            ${!missingPassport && expSoon ? (__('passport_expiring_soon_message') || 'Passport expires within 3 months of travel.') : ''}
                        </span></div>` : '';

                    // Existing passport copy (ids are used by didOpen: open / replace)
                    let existingPassportHtml = '';
                    if (data.passport_doc_url) {
                        const isImg = data.passport_doc_is_image;
                        const ext = data.passport_doc_ext || '';
                        const viewContent = isImg
                            ? `<img src="${data.passport_doc_url}" alt="Passport Copy" class="tv-doc-img"/>`
                            : `<a href="${data.passport_doc_url}" target="_blank" class="tv-doc-file"><i class="fa fa-file-pdf"></i> ${__('view_passport') || 'View Passport'} (${ext.toUpperCase()})</a>`;
                        existingPassportHtml = `
                        <div id="existing-passport-doc" class="tv-doc">
                            <div class="tv-doc-view">${viewContent}</div>
                            <div class="tv-doc-actions">
                                <button type="button" id="open-passport-btn" class="srs-tool"><i class="fa fa-external-link-alt"></i> ${__('open_passport_file') || 'Open Passport'}</button>
                                <button type="button" id="replace-passport-btn" class="srs-tool"><i class="fa fa-sync-alt"></i> ${__('replace_passport_copy') || 'Replace Passport Copy'}</button>
                            </div>
                            <input type="file" id="replace_passport_input" accept=".pdf,.jpg,.jpeg,.png" style="display:none;" />
                            <small class="tv-hint" id="replace-passport-hint" style="display:none;"><i class="fa fa-info-circle"></i> ${__('upload_new_passport_hint') || 'Select a new file to replace the stored passport copy.'}</small>
                        </div>`;
                    }
                    const requireUpload = !data.passport_doc_url;
                    const passportSectionHtml = `
                        <div class="tv-card">
                            <div class="tv-card-head"><span class="tv-ico tone-sky"><i class="fa fa-file-upload"></i></span>${__('passport_copy') || 'Passport Copy'}
                                ${requireUpload ? `<span class="tv-tag is-req">${__('required') || 'Required'}</span>` : `<span class="tv-tag is-ok"><i class="fa fa-check"></i> ${__('on_file') || 'On file'}</span>`}</div>
                            ${requireUpload ? `
                                <label for="passport_file" class="tv-drop">
                                    <i class="fa fa-cloud-upload-alt"></i>
                                    <span>${__('select_passport_file') || 'Select passport copy (PDF, JPG, PNG)'}</span>
                                    <small>${__('passport_file_help_short', 'PDF, JPG or PNG · max 5MB')}</small>
                                    <input type="file" id="passport_file" accept=".pdf,.jpg,.jpeg,.png" class="tv-drop-input" onchange="var f=this.files[0];this.parentNode.classList.toggle('has-file',!!f);if(f)this.parentNode.querySelector('span').textContent=f.name;">
                                </label>` : ''}
                            ${existingPassportHtml}
                        </div>`;

                    Swal.fire({
                        title: `<span class="srs-head"><span class="srs-icon tone-sky"><i class="fa fa-passport"></i></span><span class="srs-head-text">${__('verify_traveler_information') || 'Verify Traveler Information'}<small class="srs-head-sub">${tvEsc(data.employee_name)} · ${tvEsc(data.request_inv_no)}</small></span></span>`,
                        html: `
                            <div class="tv-wrap">
                                ${passportWarningHtml}
                                <div class="tv-card">
                                    <div class="tv-card-head"><span class="tv-ico tone-indigo"><i class="fa fa-user"></i></span>${__('employee_information') || 'Employee Information'}</div>
                                    <div class="tv-grid">
                                        ${tvItem('id-card', __('employee_name') || 'Employee Name', tvEsc(data.employee_name), true)}
                                        ${tvItem('hashtag', __('emp_id') || 'Employee ID', tvEsc(data.emp_id))}
                                        ${tvItem('building', __('department') || 'Department', tvEsc(data.department_name))}
                                        ${tvItem('passport', __('passport_no') || 'Passport No', `<span class="srs-mono">${tvEsc(data.passport_number)}</span>`, true)}
                                    </div>
                                </div>
                                <div class="tv-card">
                                    <div class="tv-card-head"><span class="tv-ico tone-amber"><i class="fa fa-plane"></i></span>${__('trip_details') || 'Trip Details'}</div>
                                    <div class="tv-route">
                                        <div><small>${__('start_date') || 'Start'}</small><b>${tvEsc(data.trip_start_date)}</b></div>
                                        <div class="tv-route-mid"><i class="fa fa-plane"></i><span>${tvEsc(data.destination)}</span></div>
                                        <div><small>${__('end_date') || 'End'}</small><b>${tvEsc(data.trip_end_date)}</b></div>
                                    </div>
                                    <div class="tv-grid">
                                        ${tvItem('clock', __('duration') || 'Duration', `${tvEsc(data.trip_days)} ${__('days') || 'days'}`)}
                                        ${tvItem('bus', __('transportation') || 'Transportation', tvEsc(__(data.transportation_type) || data.transportation_type))}
                                    </div>
                                </div>
                                ${passportSectionHtml}
                                <div class="tv-alert is-warn"><i class="fa fa-exclamation-triangle"></i><span><strong>${__('important') || 'Important'}:</strong> ${__('verify_information_notice') || 'Please verify all information is correct before sending the email.'}</span></div>
                                <div class="tv-alert is-err"><i class="fa fa-ban"></i><span><strong>${__('note') || 'Note'}:</strong> ${__('email_sent_once_warning') || 'This email can only be sent once. After sending, the button will be hidden.'}</span></div>
                            </div>
                        `,
                        showCancelButton: true,
                        confirmButtonText: '<i class="fa fa-paper-plane"></i> ' + (__('confirm_and_send') || 'Confirm & Send Email'),
                        cancelButtonText: __('cancel') || 'Cancel',
                        confirmButtonColor: '#16a34a',
                        cancelButtonColor: '#64748b',
                        allowOutsideClick: false,
                        width: '640px',
                        didOpen: () => {
                            const confirmBtn = Swal.getConfirmButton();
                            const passportFileInput = document.getElementById('passport_file');
                            const openBtn = document.getElementById('open-passport-btn');
                            const replaceBtn = document.getElementById('replace-passport-btn');
                            const replaceInput = document.getElementById('replace_passport_input');
                            let selectedReplacementUrl = null;

                            if (openBtn) {
                                openBtn.addEventListener('click', function() {
                                    if (selectedReplacementUrl) {
                                        window.open(selectedReplacementUrl, '_blank');
                                    } else {
                                        window.open(data.passport_doc_url, '_blank');
                                    }
                                });
                            }

                            // Update confirm button state
                            const updateConfirmButton = () => {
                                if (confirmBtn) {
                                    if (invalidPassport && !data.passport_doc_url && !passportFileInput.value) {
                                        confirmBtn.disabled = true;
                                        confirmBtn.style.opacity = '0.6';
                                        confirmBtn.style.cursor = 'not-allowed';
                                        confirmBtn.setAttribute('title', __('passport_fix_required') || 'Fix passport number/expiry before sending');
                                    } else if (!data.passport_doc_url && !passportFileInput.value) {
                                        confirmBtn.disabled = true;
                                        confirmBtn.style.opacity = '0.6';
                                        confirmBtn.style.cursor = 'not-allowed';
                                        confirmBtn.setAttribute('title', __('passport_attachment_required') || 'Passport attachment is required');
                                    } else {
                                        confirmBtn.disabled = false;
                                        confirmBtn.style.opacity = '1';
                                        confirmBtn.style.cursor = 'pointer';
                                        confirmBtn.removeAttribute('title');
                                    }
                                }
                            };

                            updateConfirmButton();

                            if (passportFileInput) {
                                passportFileInput.addEventListener('change', updateConfirmButton);
                            }
                            if (replaceBtn && replaceInput) {
                                replaceBtn.addEventListener('click', () => {
                                    replaceInput.click();
                                });

                                replaceInput.addEventListener('change', () => {
                                    if (!replaceInput.files || replaceInput.files.length === 0) {
                                        return;
                                    }

                                    const file = replaceInput.files[0];
                                    const maxSize = 5 * 1024 * 1024;
                                    if (file.size > maxSize) {
                                        Swal.fire({
                                            title: 'Error',
                                            text: 'Passport file is too large. Maximum size is 5MB.',
                                            icon: 'error'
                                        });
                                        replaceInput.value = '';
                                        return;
                                    }

                                    if (selectedReplacementUrl) {
                                        URL.revokeObjectURL(selectedReplacementUrl);
                                    }
                                    selectedReplacementUrl = URL.createObjectURL(file);
                                    replaceBtn.innerHTML = '<i class="fa fa-check"></i> ' + (__('replacement_selected') || 'Replacement selected');
                                    replaceBtn.classList.remove('btn-warning');
                                    replaceBtn.classList.add('btn-success');
                                });
                            }
                        }
                    }).then((result) => {
                        if (result.isConfirmed) {
                            const passportFile = document.getElementById('passport_file');
                            const replacePassportFile = document.getElementById('replace_passport_input');
                            const maxSize = 5 * 1024 * 1024;

                            let selectedPassportFile = null;
                            if (passportFile && passportFile.files && passportFile.files.length > 0) {
                                selectedPassportFile = passportFile.files[0];
                            } else if (replacePassportFile && replacePassportFile.files && replacePassportFile.files.length > 0) {
                                selectedPassportFile = replacePassportFile.files[0];
                            }

                            if (selectedPassportFile && selectedPassportFile.size > maxSize) {
                                Swal.fire({
                                    title: 'Error',
                                    text: 'Passport file is too large. Maximum size is 5MB.',
                                    icon: 'error'
                                });
                                return;
                            }

                            // Show loading
                            Swal.fire({
                                title: __('sending') || 'Sending...',
                                html: __('please_wait_sending_email') || 'Please wait while we send the email to the travel company.',
                                allowOutsideClick: false,
                                allowEscapeKey: false,
                                didOpen: () => {
                                    Swal.showLoading();
                                }
                            });

                            // Create FormData
                            const formData = new FormData();
                            formData.append('ajaxType', 'sendTravelEmailBusinessTrip');
                            formData.append('trip_id', tripId);
                            if (selectedPassportFile) {
                                formData.append('passport_file', selectedPassportFile);
                            }

                            $.ajax({
                                url: './includes/ajaxFile/ajaxBusinessTrip.php',
                                type: 'POST',
                                dataType: 'JSON',
                                cache: false,
                                contentType: false,
                                processData: false,
                                data: formData,
                                success: function(response) {
                                    if (response.status === 'success') {
                                        Swal.fire({
                                            title: response.title || 'Success',
                                            text: response.message || 'Email sent successfully',
                                            icon: 'success',
                                            confirmButtonColor: APP_COLORS.success,
                                            allowOutsideClick: false
                                        }).then(() => location.reload());
                                    } else {
                                        Swal.fire({
                                            title: 'Error',
                                            text: response.message || 'Failed to send email',
                                            icon: 'error',
                                            confirmButtonColor: APP_COLORS.danger
                                        });
                                    }
                                },
                                error: function() {
                                    Swal.fire({
                                        title: 'Error',
                                        text: 'An error occurred while sending the email',
                                        icon: 'error',
                                        confirmButtonColor: APP_COLORS.danger
                                    });
                                }
                            });
                        }
                    });
                } else {
                    Swal.fire(
                        __('error') || 'Error',
                        response.message || (__('error_fetching_details') || 'Could not fetch trip details.'),
                        'error'
                    );
                }
            })
            .fail(function(jqXHR, textStatus, errorThrown) {
                console.error('AJAX Error:', textStatus, errorThrown);
                Swal.fire(
                    __('error') || 'Error',
                    __('error_loading_traveler_info') || 'An error occurred while loading trip information.',
                    'error'
                );
            });
        }
    </script>
</body>
</html>
<?php
$conDB->close();
?>