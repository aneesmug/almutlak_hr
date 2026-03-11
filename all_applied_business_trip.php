<?php
require_once __DIR__ . '/includes/session_check.php';

// Restrict access: Employees cannot view this detailed report page
if (isset($isEmployee) && $isEmployee === true) {
    header("Location: ./profile.php");
    exit();
}

// --- Get Request Type ID for 'business_trip' ---
$type_query = mysqli_query($conDB, "SELECT `id` FROM `approval_request_types` WHERE `type_name` = 'business_trip' LIMIT 1");
if (!$type_query || mysqli_num_rows($type_query) == 0) {
    die("CRITICAL ERROR: 'business_trip' type not found in `approval_request_types` table.");
}
$request_type_id = (int)mysqli_fetch_assoc($type_query)['id'];

$all_statuses = [
    'my_pending' => __('my_pending_queue'),
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

    $where_clauses[] = "ra.approver_id = ?";
    $params[] = $empid;
    $types .= "i";

    $where_clauses[] = "ra.status = 'pending'";
    $where_clauses[] = "bt.current_status = 'pending_approval'";
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

if (!$can_see_all_depts && !$dept_filter_applied && $current_filter !== 'my_pending') {
    $where_clauses[] = "(e.dept = ? OR EXISTS (SELECT 1 FROM request_approvers ra_any WHERE ra_any.request_inv_no = bt.request_inv_no AND ra_any.request_type_id = ? AND ra_any.approver_id = ?))";
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
if ($current_filter !== 'my_pending') {
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
        (CASE WHEN ba.id IS NOT NULL THEN 1 ELSE 0 END) as has_allowance_record
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
    $unfiltered_sql = "SELECT COUNT(bt.id) as total FROM emp_business_trip bt JOIN employees e ON bt.emp_id = e.emp_id WHERE (e.dept = ? OR EXISTS (SELECT 1 FROM request_approvers ra_any WHERE ra_any.request_inv_no = bt.request_inv_no AND ra_any.request_type_id = ? AND ra_any.approver_id = ?))";
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
        .detail-item { display: flex; align-items: center; margin-bottom: 1rem; font-size: 1.03em; }
        .detail-item i { color: #4a90e2; margin-right: 15px; width: 20px; text-align: center; }
        .detail-item strong { color: #8a94a6; min-width: 140px; display: inline-block; }
        .request-card .card-footer { background-color: #fafbff; border-top: 1px solid #eef; }
        .no-requests { padding: 3rem; background: #fff; border-radius: 15px; box-shadow: 0 10px 30px rgba(0, 0, 0, 0.07); }
        .btn-block + .btn-block { margin-top: 0rem !important; }
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
            <div class="content">
                <div class="container-fluid">
                    <div class="row">
                        <div class="col-xl-12">
                            <div class="card-box">
                                <h4 class="header-title m-t-0 m-b-30"><?= __('business_trip_approval_center') ?></h4>

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
                                                <?php if (!empty($search_term) || $current_filter !== 'my_pending'): ?>
                                                <div class="input-group-append">
                                                    <button class="btn btn-danger" type="reset" onclick="resetFilters(<?= $perpage ?>)"><i class="fas fa-times"></i></button>
                                                </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="d-flex justify-content-between align-items-center mb-4">
                                    <h4 class="mb-0 text-muted"><?= __('showing') ?>: <?= htmlspecialchars($page_title); ?></h4>
                                    <span class="badge badge-light p-2"><?= __('total_found') ?>: <?= $total_items; ?></span>
                                </div>

                                <?php if (!empty($requests)): ?>
                                    <div class="row">
                                        <?php foreach ($requests as $trip): ?>
                                            <?php
                                            $trip_type_text = ($trip['trip_type'] === 'international') ? __('international') : __('domestic');
                                            $route_text = '';
                                            if ($trip['trip_type'] === 'international') {
                                                $route_text = ($trip['destination_country'] ?? 'N/A');
                                            } else {
                                                $from_name = ($is_rtl ?? false) ? ($trip['from_city_name_ar'] ?? '') : ($trip['from_city_name_en'] ?? '');
                                                $to_name = ($is_rtl ?? false) ? ($trip['to_city_name_ar'] ?? '') : ($trip['to_city_name_en'] ?? '');
                                                $route_text = trim((string)$from_name) . ' → ' . trim((string)$to_name);
                                            }

                                            $status_badge_class = 'secondary';
                                            $status_text = '';
                                            $current_level_display = '';

                                            if (!empty($trip['current_approver_name']) && $trip['current_status'] === 'pending_approval') {
                                                $status_text = __('pending_with') . ' ' . getDisplayName(parseName($trip['current_approver_name']));
                                                $status_badge_class = 'warning';
                                                if (!empty($trip['current_approval_level'])) {
                                                    $current_level_display = ' (Level ' . (int)$trip['current_approval_level'] . ')';
                                                }
                                            } elseif ($trip['current_status'] === 'approved') {
                                                $status_text = __('approved');
                                                $status_badge_class = 'success';
                                            } elseif ($trip['current_status'] === 'rejected') {
                                                $status_text = __('rejected');
                                                $status_badge_class = 'danger';
                                            } elseif ($trip['current_status'] === 'completed') {
                                                $status_text = __('completed');
                                                $status_badge_class = 'primary';
                                            } else {
                                                $status_text = __('pending_approval');
                                                $status_badge_class = 'warning';
                                            }

                                            $can_take_action = ((int)($trip['current_approver_id'] ?? 0) === (int)$empid) && (($trip['current_status'] ?? '') === 'pending_approval');
                                            ?>
                                            <div class="col-lg-4 col-md-6 mb-4">
                                                <div class="card request-card h-100">
                                                    <div class="card-header">
                                                        <?= getDisplayName(parseName($trip['employee_name'])); ?>
                                                        <span class="float-right"><?= __('emp_id') ?>: <?= htmlspecialchars((string)$trip['emp_id']); ?></span>
                                                    </div>
                                                    <div class="card-body">
                                                        <div class="detail-item"><i class="fa fa-hashtag"></i><strong><?= __('request_id') ?>:</strong> <?= htmlspecialchars((string)$trip['request_inv_no']); ?></div>
                                                        <div class="detail-item"><i class="fa fa-plane"></i><strong><?= __('trip_type') ?>:</strong> <?= htmlspecialchars((string)$trip_type_text); ?></div>
                                                        <div class="detail-item"><i class="fa fa-map-marker"></i><strong><?= __('destination') ?>:</strong> <?= htmlspecialchars((string)$route_text); ?></div>
                                                        <div class="detail-item"><i class="fa fa-calendar-alt"></i><strong><?= __('trip_dates') ?>:</strong> <?= htmlspecialchars((string)$trip['trip_start_date']); ?> → <?= htmlspecialchars((string)$trip['trip_end_date']); ?></div>
                                                        <div class="detail-item"><i class="fa fa-info-circle"></i><strong><?= __('purpose') ?>:</strong> <?= htmlspecialchars((string)($trip['trip_purpose'] ?? '')); ?></div>
                                                        <div class="detail-item"><i class="fa fa-stream"></i><strong><?= __('status') ?>:</strong> <span class="badge badge-<?= $status_badge_class; ?> p-2"><?= htmlspecialchars($status_text . $current_level_display); ?></span></div>

                                                        <?php if (($trip['current_status'] ?? '') === 'rejected' && !empty($trip['rejection_note'])): ?>
                                                            <div class="detail-item" style="margin-top: 12px; padding: 10px; background-color: #f8d7da; border-left: 3px solid #dc3545; border-radius: 4px;">
                                                                <i class="fas fa-ban" style="color:#dc3545; margin-right:8px;"></i>
                                                                <strong><?= __('rejection_reason') ?>:</strong>
                                                                <?= nl2br(htmlspecialchars(getDisplayName((string)$trip['rejection_note']))); ?>
                                                            </div>
                                                        <?php endif; ?>
                                                    </div>
                                                    <div class="card-footer d-flex justify-content-between align-items-center" style="gap: 0.5rem;">
                                                        <a href="business_trip_report.php?id=<?= (int)$trip['id']; ?>&emp_id=<?= urlencode((string)$trip['emp_id']); ?>" target="_blank" class="btn btn-info btn-block waves-effect">
                                                            <i class="fa fa-eye"></i> <?= __('view_report') ?>
                                                        </a>
                                                        <div class="btn-group flex-fill" style="position: relative; z-index: 1000;">
                                                            <button type="button" class="btn btn-secondary dropdown-toggle btn-block waves-effect" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                                                <?= __('actions') ?> <span class="caret"></span>
                                                            </button>
                                                            <div class="dropdown-menu dropdown-menu-right" style="z-index: 1050; position: absolute;">
                                                                <a class="dropdown-item" href="business_trip_status_history.php?request_inv_no=<?= urlencode($trip['request_inv_no']); ?>" target="_blank">
                                                                    <i class="fa fa-history"></i> <?= __('history') ?>
                                                                </a>
                                                                <?php if (($trip['current_status'] ?? '') === 'approved' && ($trip['transportation_type'] ?? '') === 'by_air' && empty($trip['travel_email_sent'])): ?>
                                                                    <div class="dropdown-divider"></div>
                                                                    <button type="button" class="dropdown-item" style="cursor: pointer; background: none; border: none; width: 100%; text-align: left;" onclick="sendTravelEmailBusinessTrip(<?= (int)$trip['id']; ?>, '<?= htmlspecialchars((string)$trip['employee_name'], ENT_QUOTES); ?>')">
                                                                        <i class="fa fa-plane text-info"></i> <?= __('send_travel_email')?>
                                                                    </button>
                                                                <?php endif; ?>
                                                                <?php if (($trip['current_status'] ?? '') === 'approved' && ($trip['transportation_type'] ?? '') === 'by_air' && !empty($trip['travel_email_sent']) && !$isHRPayrollRole && empty($trip['has_allowance_record'])): ?>
                                                                    <div class="dropdown-divider"></div>
                                                                    <button type="button" class="dropdown-item" style="cursor: pointer; background: none; border: none; width: 100%; text-align: left;" onclick="openBusinessTripAllowanceModal(<?= (int)$trip['id']; ?>, '<?= htmlspecialchars((string)$trip['employee_name'], ENT_QUOTES); ?>')">
                                                                        <i class="fa fa-money-bill-wave text-success"></i> <?= __('add_other_amount') ?: 'Add Other Amount' ?>
                                                                    </button>
                                                                <?php endif; ?>
                                                                <?php if (($trip['current_status'] ?? '') === 'approved' && ($trip['transportation_type'] ?? '') === 'by_air' && !empty($trip['travel_email_sent']) && $isHRPayrollRole && empty($trip['has_allowance_record'])): ?>
                                                                    <div class="dropdown-divider"></div>
                                                                    <button type="button" class="dropdown-item" style="cursor: pointer; background: none; border: none; width: 100%; text-align: left;" onclick="openHRPayrollTicketFareModal(<?= (int)$trip['id']; ?>, '<?= htmlspecialchars((string)$trip['employee_name'], ENT_QUOTES); ?>')">
                                                                        <i class="fa fa-ticket-alt text-primary"></i> <?= __('add_ticket_fare_only') ?: 'HR Payroll Ticket Fare' ?>
                                                                    </button>
                                                                <?php endif; ?>
                                                                <?php if ($can_take_action): ?>
                                                                    <div class="dropdown-divider"></div>
                                                                    <button type="button" class="dropdown-item" style="cursor: pointer; background: none; border: none; width: 100%; text-align: left;" onclick="approveBusinessTripRequest('<?= htmlspecialchars((string)$trip['request_inv_no'], ENT_QUOTES); ?>', '<?= htmlspecialchars((string)$trip['employee_name'], ENT_QUOTES); ?>', '<?= htmlspecialchars((string)$trip_type_text, ENT_QUOTES); ?>', '<?= htmlspecialchars((string)$trip['trip_start_date'], ENT_QUOTES); ?>', '<?= htmlspecialchars((string)$trip['trip_end_date'], ENT_QUOTES); ?>')">
                                                                        <i class="fa fa-check text-success"></i> <?= __('approve') ?>
                                                                    </button>
                                                                    <button type="button" class="dropdown-item" style="cursor: pointer; background: none; border: none; width: 100%; text-align: left;" onclick="rejectBusinessTripRequest('<?= htmlspecialchars((string)$trip['request_inv_no'], ENT_QUOTES); ?>', '<?= htmlspecialchars((string)$trip['employee_name'], ENT_QUOTES); ?>', '<?= htmlspecialchars((string)$trip_type_text, ENT_QUOTES); ?>', '<?= htmlspecialchars((string)$trip['trip_start_date'], ENT_QUOTES); ?>', '<?= htmlspecialchars((string)$trip['trip_end_date'], ENT_QUOTES); ?>')">
                                                                        <i class="fa fa-times text-danger"></i> <?= __('reject') ?>
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
                                    echo generate_pagination_controls($current_page, $total_pages, $total_items, $items_per_page, $limit_options, $show_all, $pagination_params, $unfiltered_total_items);
                                    ?>
                                <?php else: ?>
                                    <div class="row justify-content-center">
                                        <div class="col-md-8">
                                            <div class="text-center no-requests">
                                                <i class="fas fa-plane-departure fa-3x text-muted mb-3"></i>
                                                <h2><?= __('no_business_trip_requests_found') ?></h2>
                                                <p class="text-muted"><?= __('no_requests_matching_filters') ?></p>
                                            </div>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
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

        document.getElementById('searchFilter').addEventListener('keypress', function (e) {
            if (e.key === 'Enter') {
                applyFilters();
            }
        });

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

                    // Build existing passport doc HTML
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
                        </div>
                    `;
                    }

                    // Build passport section
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
                                    <i class="fa fa-info-circle"></i> ${__('passport_file_help') || 'Please upload a clear copy of the employee passport. Accepted formats: PDF, JPG, PNG (Max 5MB)'}
                                </small>
                            </div>
                        ` : ''}
                        ${existingPassportHtml}
                    </div>
                `;

                    // Show confirmation modal
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
                                        <td style="padding: 8px; font-weight: 600;">Name:</td>
                                        <td style="padding: 8px;">${data.employee_name || '-'}</td>
                                    </tr>
                                    <tr style="border-bottom: 1px solid #dee2e6;">
                                        <td style="padding: 8px; font-weight: 600;">Employee ID:</td>
                                        <td style="padding: 8px;">${data.emp_id || '-'}</td>
                                    </tr>
                                    <tr style="border-bottom: 1px solid #dee2e6;">
                                        <td style="padding: 8px; font-weight: 600;">Department:</td>
                                        <td style="padding: 8px;">${data.department_name || '-'}</td>
                                    </tr>
                                </table>
                            </div>

                            <div style="background: #fff9e6; padding: 15px; border-radius: 8px; margin-bottom: 15px; border-left: 4px solid #ffc107;">
                                <h5 style="color: #856404; margin-top: 0; border-bottom: 2px solid #ffc107; padding-bottom: 8px;">
                                    <i class="fa fa-plane"></i> ${__('trip_details') || 'Trip Details'}
                                </h5>
                                <table style="width: 100%; font-size: 14px;">
                                    <tr style="border-bottom: 1px solid #dee2e6;">
                                        <td style="padding: 8px; font-weight: 600;">Destination:</td>
                                        <td style="padding: 8px;">${data.destination || '-'}</td>
                                    </tr>
                                    <tr style="border-bottom: 1px solid #dee2e6;">
                                        <td style="padding: 8px; font-weight: 600;">Trip Dates:</td>
                                        <td style="padding: 8px;">${data.trip_start_date} to ${data.trip_end_date} (${data.trip_days} days)</td>
                                    </tr>
                                    <tr style="border-bottom: 1px solid #dee2e6;">
                                        <td style="padding: 8px; font-weight: 600;">Transportation:</td>
                                        <td style="padding: 8px;"><i class="fa fa-plane"></i> ${__(data.transportation_type)}</td>
                                    </tr>
                                    <tr>
                                        <td style="padding: 8px; font-weight: 600;">Reference Number:</td>
                                        <td style="padding: 8px;">${data.request_inv_no || '-'}</td>
                                    </tr>
                                </table>
                            </div>

                            <div style="background: #fff3cd; padding: 12px; border-radius: 6px; margin-bottom: 15px; border: 1px solid #ffc107;">
                                <p style="margin: 0; font-size: 13px; color: #856404;">
                                    <i class="fa fa-exclamation-triangle" style="margin-right: 5px;"></i>
                                    <strong>${__('important') || 'Important'}:</strong> ${__('verify_information_notice') || 'Please verify all information is correct before sending the email.'}
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