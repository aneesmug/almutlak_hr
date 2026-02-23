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
require_once __DIR__ . '/includes/settlement_attachments_helper.php';

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
$departmentFilter = getDepartmentFilterSQL('e.dept', true);
$employeeFilter = getEmployeeFilterSQL('e.emp_id', true);
if (empty($whereClauses)) {
    $whereClauses[] = "1=1" . $companyFilter . $departmentFilter . $employeeFilter;
} else {
    $whereClauses[] = "1=1" . $companyFilter . $departmentFilter . $employeeFilter;
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
        e.gosi,
        e.country as country_id,
        ra_pending.approver_id as current_approver_id,
        approver_emp.name as current_approver_name,
        ra_pending.approval_level as current_approval_level,
        v.vac_type,
        v.fly_type,
        v.vacdays,
        v.start_date,
        v.vacation_salary_type,
        v.overtime_hours,
        v.deduction_hours,
        v.deduction_days,
        v.other_earnings,
        v.other_deductions,
        v.ticket_pay,
        v.permit_fee,
        sal.basic,
        sal.housing,
        sal.transport,
        sal.food,
        sal.misc,
        sal.cashier,
        sal.fuel,
        sal.tel,
        sal.other,
        sal.guard
    FROM settlement_records s
    JOIN employees e ON s.emp_id = e.emp_id
    LEFT JOIN emp_vacation v ON v.request_inv_no = SUBSTR(s.request_inv_no, 6)
    LEFT JOIN emp_salary sal ON sal.emp_id = e.emp_id AND sal.status = 1 AND sal.id = (
        SELECT MAX(sal2.id) FROM emp_salary sal2 WHERE sal2.emp_id = e.emp_id AND sal2.status = 1
    )
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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/dropzone/5.9.3/min/dropzone.min.css" />

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
        #settlementDropzone.dropzone {
            border: 2px dotted #4e73df;
            border-radius: 8px;
            background: #f8f9fc;
            min-height: 180px;
        }
        #settlementDropzone .dz-message {
            margin: 2.5rem 0;
            text-align: center;
            color: #6c757d;
        }
        #settlementDropzone .dz-message i {
            display: block;
            font-size: 44px;
            color: #4e73df;
            margin-bottom: 10px;
        }
        #settlementDropzone .dz-message strong {
            color: #495057;
            display: block;
            margin-top: 6px;
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
                                            
                                            // Calculate payable amount from vacation data (same logic as vacation_report_details.php)
                                            $payableAmount = 0;
                                            
                                            // First try to calculate from vacation data
                                            if (!empty($settlement['vac_type']) && !empty($settlement['basic'])) {
                                                $vac_type = $settlement['vac_type'];
                                                $fly_type = $settlement['fly_type'];
                                                $vacation_salary_type = $settlement['vacation_salary_type'] ?? 'payroll';
                                                $approved_days = (float)($settlement['vacdays'] ?? 0);
                                                
                                                $is_fly_annual = ($vac_type === 'Fly' && $fly_type === 'annual');
                                                $is_local_annual = ($vac_type === 'Local Vacation' && $fly_type === 'annual');
                                                $is_encashment = (trim(strtolower($vac_type)) === 'encashed');
                                                $is_emergency = ($fly_type === 'emergency');
                                                
                                                $non_payable_leave_types = ['Sick Leave', 'Casual Leave', 'Maternity Leave', 'Compassionate Leave', 'Business Trip', 'Compensatory Leave'];
                                                $is_non_payable_leave = in_array($vac_type, $non_payable_leave_types);
                                                
                                                $calculate_payments = !$is_non_payable_leave && !$is_emergency && !$is_local_annual;
                                                
                                                if ($calculate_payments) {
                                                    $basic_salary = (float)($settlement['basic'] ?? 0);
                                                    $total_monthly_salary = $basic_salary + ($settlement['housing'] ?? 0) + ($settlement['transport'] ?? 0) + ($settlement['food'] ?? 0) + ($settlement['misc'] ?? 0) + ($settlement['cashier'] ?? 0) + ($settlement['fuel'] ?? 0) + ($settlement['tel'] ?? 0) + ($settlement['other'] ?? 0) + ($settlement['guard'] ?? 0);
                                                    
                                                    if ($total_monthly_salary > 0) {
                                                        $days_in_month = 30;
                                                        if (!empty($settlement['start_date'])) {
                                                            $start_ts = strtotime($settlement['start_date']);
                                                            if ($start_ts !== false) {
                                                                $days_in_month = (int)date('t', $start_ts);
                                                            }
                                                        }
                                                        $daily_rate = ($days_in_month > 0) ? round($total_monthly_salary / $days_in_month, 2) : 0;
                                                        
                                                        $dailyRateDeduction = ($days_in_month > 0) ? round($total_monthly_salary / $days_in_month, 2) : 0;
                                                        $hourlyRateDeduction = round($dailyRateDeduction / 8, 2);
                                                        $overtimeHourlyRate = round((($basic_salary / 240) / 2) + ($total_monthly_salary / 240), 2);
                                                        
                                                        $working_days_salary = 0;
                                                        $vacation_salary = 0;
                                                        $gosi_deduction = 0;
                                                        $overtime_amount = 0;
                                                        $deduction_amount = 0;
                                                        
                                                        // Calculate working days salary (Fly + Annual only)
                                                        if ($is_fly_annual && !empty($settlement['start_date'])) {
                                                            try {
                                                                $start_date_obj = new DateTime($settlement['start_date']);
                                                                $working_days = (int)$start_date_obj->format('d');
                                                                $working_days_salary = round($daily_rate * $working_days);
                                                            } catch (Exception $e) {
                                                                $working_days_salary = 0;
                                                            }
                                                        }
                                                        
                                                        // Calculate vacation salary (Fly + Annual with payroll type only)
                                                        if ($is_fly_annual && $vacation_salary_type === 'payroll') {
                                                            $vacation_salary = round($daily_rate * $approved_days);
                                                        }
                                                        
                                                        // Calculate overtime
                                                        if (!empty($settlement['overtime_hours']) && $settlement['overtime_hours'] > 0) {
                                                            $overtime_amount = round($overtimeHourlyRate * $settlement['overtime_hours']);
                                                        }

                                                        $other_earnings = !empty($settlement['other_earnings']) ? $settlement['other_earnings'] : 0;
                                                        
                                                        // Calculate deductions
                                                        $ded_hours = !empty($settlement['deduction_hours']) ? $settlement['deduction_hours'] : 0;
                                                        $ded_days = !empty($settlement['deduction_days']) ? $settlement['deduction_days'] : 0;
                                                        $other_ded = !empty($settlement['other_deductions']) ? $settlement['other_deductions'] : 0;
                                                        
                                                        if ($ded_hours > 0 || $ded_days > 0 || $other_ded > 0) {
                                                            $deduction_hours_amount = round($hourlyRateDeduction * $ded_hours);
                                                            $deduction_days_amount = round($dailyRateDeduction * $ded_days);
                                                            $deduction_amount = round($deduction_hours_amount + $deduction_days_amount + $other_ded);
                                                        }
                                                        
                                                        // Calculate GOSI
                                                        if ($settlement['country_id'] == 191 && !empty($settlement['gosi']) && is_numeric($settlement['gosi'])) {
                                                            $gosi_percentage = (float)$settlement['gosi'];
                                                            if ($is_fly_annual) {
                                                                $gosi_base = $working_days_salary + $vacation_salary;
                                                                $gosi_deduction = round(($gosi_base * $gosi_percentage) / 100);
                                                            }
                                                        }
                                                        
                                                        // Calculate total payable
                                                        if ($is_encashment) {
                                                            $payableAmount = 0;
                                                        } elseif ($is_fly_annual) {
                                                            $payableAmount = round(($working_days_salary + $vacation_salary) + $overtime_amount + $other_earnings - $deduction_amount - $gosi_deduction);
                                                        }
                                                    }
                                                }
                                            }
                                            
                                            // Fallback to stored settlement_amount if calculated amount is 0
                                            if ($payableAmount <= 0 && !empty($settlement['settlement_amount'])) {
                                                $payableAmount = round($settlement['settlement_amount']);
                                            }
                                            ?>
                                            <div class="col-lg-4 col-md-6 mb-4">
                                                <div class="card request-card h-100">
                                                    <div class="card-header">
                                                        <?= htmlspecialchars(getDisplayName($settlement['emp_name']), ENT_QUOTES); ?>
                                                        <span class="float-right"><?= __('emp_id') ?>: <?= htmlspecialchars($settlement['emp_id'], ENT_QUOTES); ?></span>
                                                    </div>
                                                    <div class="card-body">
                                                        <div class="detail-item"><i class="fad fa-file-invoice"></i><strong><?= __('settlement_id') ?>:</strong> <?= htmlspecialchars($settlement['request_inv_no'], ENT_QUOTES); ?></div>
                                                        <div class="detail-item"><i class="fad fa-coins"></i><strong><?= __('amount') ?>:</strong> <span class="badge badge-success" style="font-size: 0.95em; padding: 0.5rem 0.75rem;">SAR <?= number_format(round($payableAmount), 2); ?></span></div>
                                                        <div class="detail-item"><i class="fad fa-calendar-alt"></i><strong><?= __('created') ?>:</strong> <?= htmlspecialchars(date('d M Y', strtotime($settlement['created_at'])), ENT_QUOTES); ?></div>
                                                        <?php
                                                            $settlementAttachments = getSettlementAttachments(
                                                                $pdo,
                                                                (int)$settlement['id'],
                                                                $settlement['request_inv_no']
                                                            );
                                                            $attachmentLinks = [];
                                                            foreach ($settlementAttachments as $attachment) {
                                                                $attachmentLinks[] = 'download_settlement_attachment.php?id=' . (int)$attachment['id'];
                                                            }
                                                        ?>
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
    <script src="assets/js/jquery.slimscroll.js"></script>
    <script src="assets/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/metisMenu.min.js"></script>
    <script src="assets/js/waves.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="./plugins/select2/js/select2.min.js"></script>
    <script src="assets/js/jquery.core.js"></script>
    <script src="assets/js/jquery.app.js?t=<?= time() ?>"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/dropzone/5.9.3/min/dropzone.min.js"></script>

    <script>
        // Configuration constants
        const MAX_FILE_SIZE_MB = 10;
        
        // Current user details - Make window-global for access in jquery.app.js?t=<?= time() ?>
        window.currentUserType = '<?php echo $_SESSION['user_type'] ?? ""; ?>';
        window.currentUserId = <?= (int)$empid; ?>;
        
        // Also set as regular constants for backward compatibility
        const currentUserType = window.currentUserType;
        const currentUserId = window.currentUserId;
        const isHRPayroll = (currentUserType === 'hr_payroll');

        // Preserve legacy approval handler from jquery.app.js?t=<?= time() ?> for non-HR flows
        const approveSettlementLegacy = window.approveSettlement;
        
        // Global array to track uploaded file references (server-side filenames)
        window.uploadedSettlementFiles = [];

        function applyFilters() {
            const status = document.getElementById('statusFilter').value;
            const search = document.getElementById('searchFilter').value;
            const baseUrl = window.location.href.split('?')[0];
            window.location.href = `${baseUrl}?status=${status}&search=${encodeURIComponent(search)}&page=1`;
        }

        /**
         * Approve Settlement with Multiple Attachments
         * Shows approval modal with Dropzone for file uploads (HR Payroll only)
         */
        window.approveSettlement = function (settlementId, settlementInvNo, empId) {
            if (!isHRPayroll && typeof approveSettlementLegacy === 'function') {
                return approveSettlementLegacy(settlementId, settlementInvNo, empId);
            }

            // Get settlement details first
            $.ajax({
                url: './includes/ajaxFile/settlement_handler.php',
                type: 'POST',
                dataType: 'JSON',
                data: {
                    action: 'get_settlement_details',
                    settlement_id: settlementId
                },
                success: function(response) {

                    if (response.success && response.data && response.data.settlement) {
                        const settlement = response.data.settlement;
                        const employeeName = settlement.emp_name || 'Employee';
                        const settlementAmount = parseFloat(settlement.settlement_amount || 0);


                        // Show approval modal
                        showSettlementApprovalModal(settlementId, settlementInvNo, empId, employeeName, settlementAmount);
                    } else {
                        Swal.fire('Error', 'Failed to fetch settlement details', 'error');
                    }
                },
                error: function(xhr) {
                    Swal.fire('Error', 'Failed to fetch settlement details', 'error');
                }
            });
        };

        /**
         * Show Settlement Approval Modal with Multiple Attachments
         * Shows approval and multi-file upload (Dropzone) for HR Payroll
         */
        function showSettlementApprovalModal(settlementId, settlementInvNo, empId, employeeName, settlementAmount) {

            // Store settlement details in window for use in Dropzone
            window.currentSettlementId = settlementId;
            window.currentSettlementInvNo = settlementInvNo;
            // Build HTML based on user type
            let modalHTML = `
                <div class="text-left">
                    <p><strong><?= __("employee") ?>:</strong> ${employeeName}</p>
                    <p><strong><?= __("settlement_id") ?>:</strong> ${settlementInvNo}</p>
                    <p><strong><?= __("amount") ?>:</strong> <span class="badge badge-success">SAR ${parseFloat(settlementAmount).toFixed(2)}</span></p>
                    <hr>
                    <div class="form-group">
                        <label for="approvalComment"><strong><?= __("approval_comment") ?> (<?= __("optional") ?>)</strong></label>
                        <textarea id="approvalComment" class="form-control" rows="2" placeholder="<?= __("add_comments") ?>..."></textarea>
                    </div>
            `;
            
            // Add multiple attachments upload section (HR Payroll only)
            if (isHRPayroll) {
                modalHTML += `
                    <hr>
                    <h6 class="text-primary font-weight-bold">
                        <i class="fa fa-paperclip"></i> Attachments (<?= __("optional") ?>)
                    </h6>
                    <div class="form-group">
                        <label for="settlementDropzone"><strong><?= __("upload_supporting_documents") ?></strong></label>
                        <div id="settlementDropzone" class="dropzone"></div>
                        <small class="form-text text-muted mt-2" style="display: block;">
                            <i class="fa fa-star text-warning"></i> <strong><?= __("hr_payroll") ?>:</strong> <?= __("include_wps_payment_file_if_available", "Include WPS payment file if available") ?>
                        </small>
                    </div>
                `;
            }
            
            modalHTML += `</div>`;
            
            Swal.fire({
                title: '<?= __("approve") ?> Settlement',
                html: modalHTML,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: APP_COLORS.success,
                confirmButtonText: '<i class="fa fa-check"></i> <?= __("approve") ?>',
                cancelButtonColor: APP_COLORS.secondary,
                cancelButtonText: '<i class="fa fa-times"></i> <?= __("cancel") ?>',
                allowOutsideClick: false,
                showLoaderOnConfirm: true,
                width: '750px',
                padding: '2rem',
                scrollbarPadding: false,
                didOpen: async (modal) => {
                    // Ensure HTML container scrolls if needed
                    const htmlContainer = modal.querySelector('.swal2-html-container');
                    if (htmlContainer) {
                        htmlContainer.style.maxHeight = 'none';
                        htmlContainer.style.overflowY = 'visible';
                        htmlContainer.style.textAlign = 'left';
                        htmlContainer.style.paddingRight = '10px';
                    }
                    
                    // Initialize Dropzone with small delay to ensure DOM is ready
                    if (isHRPayroll) {
                        await new Promise(resolve => setTimeout(resolve, 100));
                        initializeSettlementDropzone();
                    }
                },
                preConfirm: () => {
                    const comment = document.getElementById('approvalComment').value.trim();
                    
                    // Use uploaded file references (collected during upload), not the files themselves
                    const uploadedFileReferences = window.uploadedSettlementFiles || [];
                    
                    if (uploadedFileReferences.length === 0) {
                    }
                    
                    return new Promise((resolve, reject) => {
                        // Prepare form data for approval with file references
                        const formData = new FormData();
                        formData.append('action', 'approve_settlement_with_attachments');
                        formData.append('settlement_id', settlementId);
                        formData.append('settlement_inv_no', settlementInvNo);
                        formData.append('emp_id', empId);
                        formData.append('approval_comment', comment);
                        formData.append('is_final_approval', 0);
                        formData.append('is_hr_payroll', isHRPayroll ? '1' : '0');
                        formData.append('attachment_count', uploadedFileReferences.length);
                        
                        // Add file references (server-side filenames) - NOT the files themselves
                        if (uploadedFileReferences && uploadedFileReferences.length > 0) {
                            uploadedFileReferences.forEach((fileRef, index) => {
                                formData.append(`attachment_file_${index}`, fileRef);
                            });
                        } else {
                        }
                        
                        // Use fetch API to send approval with file references
                        fetch('./includes/ajaxFile/settlement_handler.php', {
                            method: 'POST',
                            body: formData
                        })
                        .then(response => {
                            return response.json();
                        })
                        .then(data => {
                            if (data.success === true) {
                                resolve(data);
                            } else {
                                reject(data.message || '<?= __("error_approving_settlement") ?>');
                            }
                        })
                        .catch(error => {
                            reject(error.message || '<?= __("error_approving_settlement") ?>');
                        });
                    });

                }
            }).then((result) => {

                
                if (result.isConfirmed) {
                    // Settlement approved successfully
                    const message = result.value && result.value.message ? result.value.message : '<?= __("settlement_approved_successfully") ?>';
                    const attachmentCount = result.value && result.value.attachment_count ? result.value.attachment_count : 0;
                    
                    Swal.fire({
                        title: '<?= __("success") ?>!',
                        html: `
                            <p>${message}</p>
                            <p><strong><?= __("settlement_ref") ?>:</strong> ${settlementInvNo}</p>
                            ${attachmentCount > 0 ? `<p><i class="fa fa-check text-success"></i> <strong>${attachmentCount}</strong> attachment(s) uploaded</p>` : ''}
                        `,
                        icon: 'success',
                        confirmButtonColor: APP_COLORS.success,
                        confirmButtonText: '<?= __("ok") ?>',
                        allowOutsideClick: false
                    }).then(() => {
                        location.reload();
                    });
                }
            }).catch((error) => {
                Swal.fire({
                    title: '<?= __("error") ?>',
                    html: error,
                    icon: 'error',
                    confirmButtonColor: APP_COLORS.danger,
                    confirmButtonText: '<?= __("ok") ?>'
                });
            });
        }

        // Settlement functions defined in all_settlements.php:
        // - approveSettlement(settlementId, settlementInvNo, empId) - Main approval handler with attachments
        // - showSettlementApprovalModal(settlementId, settlementInvNo, empId, employeeName, settlementAmount) - Modal display
        // - initializeSettlementDropzone() - Dropzone initialization for file uploads
        // Settlement functions defined globally in assets/js/jquery.app.js?t=<?= time() ?>:
        // - viewSettlementDetails(settlementId, settlementInvNo)
        // - rejectSettlement(settlementId, settlementInvNo)
        // - processSettlementPayment(settlementId, settlementInvNo)
        // - htmlspecialcharsJs(str)
        
        /**
         * Initialize Dropzone for settlement attachments
         * Supports multiple file uploads with validation
         * Tracks uploaded file references for later linking to settlement
         */
        function initializeSettlementDropzone() {
            const dropzoneElement = document.getElementById('settlementDropzone');
            if (!dropzoneElement) {
                return;
            }
            
            // Reset uploaded files array for this modal session
            window.uploadedSettlementFiles = [];
            
            // Destroy previous instance if exists
            if (window.settlementDropzoneInstance) {
                window.settlementDropzoneInstance.destroy();
                window.settlementDropzoneInstance = null;
            }
            
            try {
                // Create new Dropzone instance
                window.settlementDropzoneInstance = new Dropzone('#settlementDropzone', {
                    url: './includes/ajaxFile/settlement_handler.php',
                    autoDiscover: false,
                    autoProcessQueue: true, // instant upload
                    maxFilesize: MAX_FILE_SIZE_MB,
                    maxFiles: 10,
                    acceptedFiles: '.pdf,.jpg,.jpeg',
                    addRemoveLinks: true,
                    clickable: true,
                    dictDefaultMessage: `
                        <i class="fa fa-cloud-upload-alt"></i>
                        <strong><?= __("drag_drop_files") ?></strong>
                        <span><?= __("or_click_to_browse") ?></span>
                    `,
                    dictFallbackMessage: `<?= __("or_click_to_browse") ?>`,
                    dictFileTooBig: 'File is too big ({{filesize}}). Max file size is {{maxFilesize}}.',
                    dictInvalidFileType: 'You cannot upload files of this type.',
                    dictMaxFilesExceeded: 'You can not upload any more files.',
                });

                // Add event handlers for file tracking and approval button state
                const dz = window.settlementDropzoneInstance;
                
                dz.on('sending', (file, xhr, formData) => {
                    // Append settlement details for database linking
                    formData.append('action', 'upload_settlement_attachment');
                    formData.append('settlement_id', window.currentSettlementId);
                    formData.append('settlement_inv_no', window.currentSettlementInvNo);
                });
                
                dz.on('addedfile', (file) => {
                    updateApprovalButtonState();
                });
                
                dz.on('removedfile', (file) => {
                    // Remove from uploaded files if it was there
                    window.uploadedSettlementFiles = window.uploadedSettlementFiles.filter(f => f !== file.name);
                    updateApprovalButtonState();
                });
                
                // Track successful uploads with server-side filename
                dz.on('success', (file, response) => {
                    // Response might be a string or object, handle both
                    let parsedResponse = response;
                    if (typeof response === 'string') {
                        try {
                            parsedResponse = JSON.parse(response);
                        } catch (e) {
                            return;
                        }
                    }
                    
                    // Extract server-side filename from response
                    if (parsedResponse && parsedResponse.uploaded_filename) {
                        window.uploadedSettlementFiles.push(parsedResponse.uploaded_filename);
                    } else {
                    }
                    
                    updateApprovalButtonState();
                });
                
                dz.on('uploadprogress', (file, progress, bytesSent) => {
                    updateApprovalButtonState();
                });
                
                dz.on('queuecomplete', () => {
                    updateApprovalButtonState();
                });
                
                dz.on('error', (file, message) => {
                    // Alert user about the error
                    Swal.fire({
                        icon: 'error',
                        title: 'Upload Failed',
                        text: 'Error uploading ' + file.name + ': ' + message,
                        showConfirmButton: true
                    });
                    
                    updateApprovalButtonState();
                });

                // Update button state immediately after initialization
                updateApprovalButtonState();
            } catch (e) {
            }
        }

        function updateApprovalButtonState() {
            const dz = window.settlementDropzoneInstance;
            const approveBtn = document.querySelector('.swal2-confirm');
            if (!dz || !approveBtn) {
                return;
            }
            
            const uploading = dz.getUploadingFiles().length > 0;
            const queued = dz.getQueuedFiles().length > 0;
            const shouldDisable = uploading || queued;
            const isCurrentlyDisabled = approveBtn.disabled;
            
            approveBtn.disabled = shouldDisable;
            approveBtn.classList.toggle('disabled', shouldDisable);
            
            if (shouldDisable !== isCurrentlyDisabled) {
            }
        }

        // ...existing code...
    </script>
</body>
</html>
