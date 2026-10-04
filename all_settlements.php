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
require_once __DIR__ . '/includes/special_access_helper.php';

// Restrict access: Employees cannot view this detailed report page,
// unless explicitly granted via app_settings -> Special Access.
if (
    isset($isEmployee) && $isEmployee === true
    && !user_has_special_access($conDB, $empid ?? '', 'access_all_settlements', $user_role ?? '', $user_type ?? '', $is_system_admin ?? false)
) {
    header("Location: ./profile.php");
    exit();
}

// A temp-role replacement can act on the original employee's pending approvals too -
// request_approvers.approver_id / current_approver_id still point at the original employee.
$delegatedFromEmpId = getDelegatedFromEmpId($conDB, $empid ?? '');

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
    'my_team' => (function_exists('__') ? __('my_team_requests') : 'My Team'),
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

$current_filter = $_GET['status'] ?? null;

// Determine effective filter: either from URL or a default based on role
if ($current_filter === null) {
    if ($is_system_admin) {
        $current_filter = 'all';
    } else {
        $current_filter = 'my_pending';
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

$pageTitle = $allStatuses[$current_filter] ?? __('all_requests');

// Build WHERE clause based on filter
if ($current_filter === 'my_pending') {
    // Settlements assigned to current user for approval
    // Use JOIN (not LEFT JOIN) to only get requests where user is the pending approver
    $joinSql = " JOIN `request_approvers` ra_pending ON ra_pending.request_inv_no = s.request_inv_no 
         AND ra_pending.request_type_id = $requestTypeId AND ra_pending.status = 'pending'";
    $whereClauses[] = "ra_pending.approver_id = ?";
    $params[] = $empid;
    $paramTypes .= "i";
    $whereClauses[] = "s.settlement_status != 'rejected'";
} elseif ($current_filter === 'my_team') {
    // Assigned direct reports only (employees.supervisor_id) - not department-based.
    $whereClauses[] = "e.supervisor_id = ?";
    $params[] = $empid;
    $paramTypes .= "i";
} elseif ($current_filter === 'my_dept') {
    // All settlements from user's department
    $whereClauses[] = "e.dept = ?";
    $params[] = $user_dept;
    $paramTypes .= "i";
    $deptFilterApplied = true;
} elseif (in_array($current_filter, ['pending_approval', 'approved', 'rejected', 'completed'])) {
    // Filter by settlement status
    // For 'pending_approval', also include 'pending' for backward compatibility
    if ($current_filter === 'pending_approval') {
        $whereClauses[] = "(s.settlement_status = 'pending_approval' OR s.settlement_status = 'pending')";
    } else {
        $whereClauses[] = "s.settlement_status = ?";
        $params[] = $current_filter;
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
if (!$canSeeAllDepts && !$deptFilterApplied && $current_filter !== 'my_pending' && $current_filter !== 'my_team') {
    $whereClauses[] = "(e.dept = ? OR EXISTS (SELECT 1 FROM request_approvers ra_any WHERE ra_any.request_inv_no = s.request_inv_no AND ra_any.request_type_id = ? AND ra_any.approver_id = ?))";
    array_push($params, $user_dept, $requestTypeId, $empid);
    $paramTypes .= "iii";
    $deptFilterApplied = true;
}

// Add company/department/employee scoping filters, except for 'my_pending'/'my_team':
// those are already scoped by explicit approver_id / supervisor_id assignment, so a
// supervisor overseeing someone outside their own department/company must still see them.
if ($current_filter === 'my_pending' || $current_filter === 'my_team') {
    $companyFilter = "";
    $departmentFilter = "";
    $employeeFilter = "";
} else {
    $companyFilter = getCompanyFilterSQL('e.comp_no', true);
    $departmentFilter = getDepartmentFilterSQL('e.dept', true);
    $employeeFilter = getEmployeeFilterSQL('e.emp_id', true);
}
$whereClauses[] = "1=1" . $companyFilter . $departmentFilter . $employeeFilter;


$whereSql = " WHERE " . implode(" AND ", $whereClauses);

// Count total items
// For 'my_pending' filter, use JOIN instead of LEFT JOIN since we only want assigned requests
$joinClause = ($current_filter === 'my_pending') ? 
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
    $mainJoinClause = ($current_filter === 'my_pending') ? 
        $joinSql . " LEFT JOIN employees approver_emp ON ra_pending.approver_id = approver_emp.emp_id" :
        "LEFT JOIN request_approvers ra_pending ON ra_pending.request_inv_no = s.request_inv_no 
             AND ra_pending.request_type_id = $requestTypeId AND ra_pending.status = 'pending'
        LEFT JOIN employees approver_emp ON ra_pending.approver_id = approver_emp.emp_id";

    ensureSettlementOverrideSnapshotColumn($conDB);
    $sql = "SELECT
        s.*,
        e.name as emp_name,
        e.dept,
        e.gosi,
        e.country as country_id,
        e.allow_vacation_salary_below_min_days,
        (SELECT eos.net_payment FROM emp_eos eos WHERE eos.emp_id = s.emp_id ORDER BY eos.id DESC LIMIT 1) as eos_net_payment,
        ra_pending.approver_id as current_approver_id,
        approver_emp.name as current_approver_name,
        ra_pending.approval_level as current_approval_level,
        v.vac_type,
        v.fly_type,
        v.vacdays,
        v.start_date,
        v.return_date,
        v.vacation_salary_type,
        v.overtime_hours,
        v.deduction_hours,
        v.deduction_days,
        v.other_earnings,
        v.other_deductions,
        v.ticket_pay,
        v.permit_fee,
        v.is_deductible,
        v.auto_gosi_deduction,
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
    <link href="assets/css/icons.css" rel="stylesheet" type="text/css" />
    <link href="assets/css/metismenu.min.css" rel="stylesheet" type="text/css" />
    <link href="assets/css/style.css" rel="stylesheet" type="text/css" />
    <link href="assets/css/style_dark.css" rel="stylesheet" type="text/css" />
    <link href="./plugins/bootstrap-datepicker/css/bootstrap-datepicker.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/dropzone/5.9.3/min/dropzone.min.css" />
    <link href="assets/css/smart_request.css?v=<?= @filemtime(__DIR__ . '/assets/css/smart_request.css') ?>" rel="stylesheet" type="text/css" />

    <script src="assets/js/modernizr.min.js"></script>
    <style>
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
            <?php require_once __DIR__ . '/includes/sr_list_helpers.php'; ?>
            <div class="content sr-page">
                <div class="container-fluid">

                    <div class="sr-head">
                        <div>
                            <h1><?= __('all_settlements') ?></h1>
                            <p><?= str_replace('{0}', (string)(int)$totalItems, __('showing_requests')) ?></p>
                        </div>
                    </div>

                    <div class="sr-card">
                        <?= sr_status_tabs($allStatuses, $current_filter, $totalItems) ?>
                        <?= sr_list_toolbar($searchTerm, (!empty($searchTerm) || $current_filter !== 'my_pending') ? 'resetFilters(' . (int)$perpage . ')' : '') ?>

                        <?php if (!empty($settlements)): ?>
                            <div class="sr-table-wrap sr-list-wrap">
                                <table class="table sr-table sr-list" style="width: 100%;">
                                    <thead>
                                        <tr>
                                            <th><?= __('employee', 'Employee') ?></th>
                                            <th><?= __('settlement_id') ?></th>
                                            <th><?= __('amount') ?></th>
                                            <th><?= __('status') ?></th>
                                            <th><?= __('created') ?></th>
                                            <th class="text-right"><?= __('actions') ?></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($settlements as $settlement): ?>
                                            <?php
                                            // Check if this settlement is pending approval with the current user
                                            // Support both 'pending' and 'pending_approval' status for backward compatibility
                                            $isPendingStatus = in_array($settlement['settlement_status'], ['pending', 'pending_approval']);
                                            $is_pending_with_me = ($isPendingStatus && !empty($settlement['current_approver_id']) && ((int)$settlement['current_approver_id'] === (int)$empid || ($delegatedFromEmpId !== null && (int)$settlement['current_approver_id'] === (int)$delegatedFromEmpId)));
                                            
                                            // Calculate payable amount from vacation data (same logic as vacation_report_details.php)
                                            $payableAmount = 0;
                                            
                                            // First try to calculate from vacation data
                                            if (!empty($settlement['vac_type']) && !empty($settlement['basic'])) {
                                                $vac_type = $settlement['vac_type'];
                                                $fly_type = $settlement['fly_type'];
                                                $vacation_salary_type = $settlement['vacation_salary_type'] ?? 'payroll';
                                                $approved_days = (float)($settlement['vacdays'] ?? 0);

                                                // Fallback for legacy/incomplete rows where vacdays is zero but date range exists.
                                                if (
                                                    $approved_days <= 0 &&
                                                    !empty($settlement['start_date']) &&
                                                    !empty($settlement['return_date']) &&
                                                    strtolower(trim((string)$vac_type)) !== 'encashed'
                                                ) {
                                                    try {
                                                        $start_date_obj = new DateTime($settlement['start_date']);
                                                        $return_date_obj = new DateTime($settlement['return_date']);
                                                        if ($return_date_obj >= $start_date_obj) {
                                                            $approved_days = (float)$start_date_obj->diff($return_date_obj)->days + 1;
                                                        }
                                                    } catch (Exception $e) {
                                                        // Keep original approved_days when parsing fails.
                                                    }
                                                }
                                                
                                                $is_fly_annual = ($vac_type === 'Fly' && $fly_type === 'annual');
                                                $is_encashment = (trim(strtolower($vac_type)) === 'encashed');
                                                $is_emergency = ($fly_type === 'emergency');
                                                // Frozen at settlement creation; the employee's current setting no longer applies.
                                                $allow_vacation_salary_below_min_days = isset($settlement['vac_salary_below_min_override'])
                                                    ? ((string)$settlement['vac_salary_below_min_override'] === '1')
                                                    : ((string)($settlement['allow_vacation_salary_below_min_days'] ?? '0') === '1');
                                                $vacation_salary_type = resolveVacationSalaryType($fly_type, $approved_days, $vacation_salary_type, $allow_vacation_salary_below_min_days);
                                                $is_local_annual_removed_from_payroll = isLocalAnnualRemovedFromPayroll(
                                                    $vac_type,
                                                    $fly_type,
                                                    $settlement['country_id'] ?? 0,
                                                    $approved_days,
                                                    $settlement['is_deductible'] ?? 0,
                                                    $vacation_salary_type,
                                                    $allow_vacation_salary_below_min_days
                                                );
                                                $is_settlement_payable_vacation = isSettlementPayableVacation(
                                                    $vac_type,
                                                    $fly_type,
                                                    $settlement['country_id'] ?? 0,
                                                    $approved_days,
                                                    $settlement['is_deductible'] ?? 0,
                                                    $vacation_salary_type,
                                                    $allow_vacation_salary_below_min_days
                                                );
                                                
                                                $non_payable_leave_types = ['Sick Leave', 'Casual Leave', 'Maternity Leave', 'Compassionate Leave', 'Business Trip', 'Compensatory Leave'];
                                                $is_non_payable_leave = in_array($vac_type, $non_payable_leave_types);
                                                
                                                // Emergency vacations allowed through - working-days-before-departure
                                                // payout only, never vacation_salary/GOSI (still gated on
                                                // $is_settlement_payable_vacation, unaffected and still false for Emergency).
                                                $calculate_payments = !$is_non_payable_leave && ($is_emergency || $is_settlement_payable_vacation);
                                                
                                                if ($calculate_payments) {
                                                    $basic_salary = (float)($settlement['basic'] ?? 0);
                                                    $total_monthly_salary = $basic_salary + ($settlement['housing'] ?? 0) + ($settlement['transport'] ?? 0) + ($settlement['food'] ?? 0) + ($settlement['misc'] ?? 0) + ($settlement['cashier'] ?? 0) + ($settlement['fuel'] ?? 0) + ($settlement['tel'] ?? 0) + ($settlement['other'] ?? 0) + ($settlement['guard'] ?? 0);
                                                    
                                                    if ($total_monthly_salary > 0) {
                                                        // Keep 30-day basis for all payroll calculations except Working Days Salary
                                                        $days_in_month = 30;
                                                        $working_days_month_days = 30;
                                                        if (!empty($settlement['start_date'])) {
                                                            $start_ts = strtotime($settlement['start_date']);
                                                            if ($start_ts !== false) {
                                                                $working_days_month_days = (int)date('t', $start_ts);
                                                            }
                                                        }
                                                        $daily_rate = round($total_monthly_salary / $days_in_month, 2);
                                                        $working_daily_rate = ($working_days_month_days > 0) ? round($total_monthly_salary / $working_days_month_days, 2) : 0;
                                                        
                                                        $dailyRateDeduction = round($total_monthly_salary / $days_in_month, 2);
                                                        $hourlyRateDeduction = round($dailyRateDeduction / 8, 2);
                                                        $overtimeHourlyRate = round((($basic_salary / 240) / 2) + ($total_monthly_salary / 240), 2);
                                                        
                                                        $working_days_salary = 0;
                                                        $vacation_salary = 0;
                                                        $gosi_deduction = 0;
                                                        $overtime_amount = 0;
                                                        $deduction_amount = 0;
                                                        
                                                        // Calculate working days salary for vacations removed from payroll,
                                                        // and for Emergency (days actually worked before departure).
                                                        if (($is_fly_annual || $is_local_annual_removed_from_payroll || $is_emergency) && !empty($settlement['start_date'])) {
                                                            try {
                                                                $start_date_obj = new DateTime($settlement['start_date']);
                                                                $start_day = (int)$start_date_obj->format('d');

                                                                // Business rule: exclude the start day from working-days salary (working days BEFORE departure).
                                                                // If vacation starts on day 1, there are zero working days in the same month.
                                                                if ($start_day === 1) {
                                                                    $working_days_salary = 0;
                                                                } else {
                                                                    // Example: start on March 11 => 10 working days (days 1-10 before departure on 11th)
                                                                    $working_days = $start_day - 1;
                                                                    if ($working_days_month_days > 0 && $working_days >= $working_days_month_days) {
                                                                        $working_days_salary = round($total_monthly_salary);
                                                                    } else {
                                                                        $working_days_salary = round(($total_monthly_salary / 30) * $working_days);
                                                                    }
                                                                }
                                                            } catch (Exception $e) {
                                                                $working_days_salary = 0;
                                                            }
                                                        }
                                                        
                                                        // Calculate vacation salary for deductible payable vacations with payroll type.
                                                        if ($is_settlement_payable_vacation && $vacation_salary_type === 'payroll') {
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
                                                        // Check auto_gosi_deduction flag: if 1 (enabled), apply GOSI; if 0 (disabled), skip
                                                        $auto_gosi_deduction = (int)($settlement['auto_gosi_deduction'] ?? 1);  // Default to 1 for backward compatibility
                                                        
                                                        if ($auto_gosi_deduction && $settlement['country_id'] == 191 && !empty($settlement['gosi']) && is_numeric($settlement['gosi'])) {
                                                            $gosi_percentage = (float)$settlement['gosi'];
                                                            if (($is_fly_annual || $is_local_annual_removed_from_payroll) && $vacation_salary_type === 'payroll') {
                                                                // Match payroll config: GOSI is based on basic + housing salary components.
                                                                $gosi_base = (float)$basic_salary + (float)($settlement['housing'] ?? 0);
                                                                $gosi_deduction = round(($gosi_base * $gosi_percentage) / 100, 2);
                                                            }
                                                        }
                                                        
                                                        // Calculate total payable - MUST MATCH vacation_report_details.php exactly.
                                                        // Emergency included (working-days-only payout, no vacation_salary/GOSI).
                                                        if ($is_encashment) {
                                                            $payableAmount = 0;
                                                        } elseif ($is_settlement_payable_vacation || $is_emergency) {
                                                            $working_component = ($is_fly_annual || $is_local_annual_removed_from_payroll || $is_emergency) ? $working_days_salary : 0;
                                                            $payableAmount = round(($working_component + $vacation_salary) + $overtime_amount + $other_earnings - $deduction_amount - $gosi_deduction);
                                                        }
                                                    }
                                                }
                                            }
                                            
                                            // Amount stored at settlement creation is final; calculation is only a fallback when it is 0.
                                            // Negative amounts are valid too (EOS where deductions exceed earnings).
                                            if ((float)($settlement['settlement_amount'] ?? 0) != 0) {
                                                $payableAmount = round($settlement['settlement_amount']);
                                            } elseif (($settlement['request_type'] ?? '') === 'resignation' && (float)($settlement['eos_net_payment'] ?? 0) != 0) {
                                                // EOS settlement stored as 0: show the Net Payment of the EOS record (same as EOS page/print).
                                                $payableAmount = round((float)$settlement['eos_net_payment']);
                                            }

                                            $status_tone = 'slate';
                                            switch ($settlement['settlement_status']) {
                                                case 'pending':
                                                case 'pending_approval':
                                                    $status_tone = 'amber';
                                                    $approver = $settlement['current_approver_name'] ? getDisplayName(parseName($settlement['current_approver_name'])) : __('next_approver');
                                                    $status_text = __('pending_with') . ' ' . $approver;
                                                    break;
                                                case 'approved':
                                                    $status_tone = 'green';
                                                    $status_text = __('approved');
                                                    break;
                                                case 'rejected':
                                                    $status_tone = 'red';
                                                    $status_text = __('rejected');
                                                    break;
                                                case 'completed':
                                                    $status_tone = 'indigo';
                                                    $status_text = __('completed');
                                                    break;
                                                default:
                                                    $status_text = __($settlement['settlement_status']);
                                                    break;
                                            }
                                            $setl_id = (int)$settlement['id'];
                                            $inv_js = htmlspecialchars($settlement['request_inv_no'], ENT_QUOTES);
                                            $request_type_label = trim((string)($settlement['request_type'] ?? ''));
                                            ?>
                                            <tr>
                                                <td><?= sr_person_cell(getDisplayName($settlement['emp_name']), $settlement['emp_id']) ?></td>
                                                <td>
                                                    <span class="sr-cell-title sr-mono"><?= sr_h($settlement['request_inv_no']) ?></span>
                                                    <?php if ($request_type_label !== ''): ?>
                                                        <span class="sr-cell-sub"><?= sr_h(__($request_type_label, ucwords(str_replace('_', ' ', $request_type_label)))) ?></span>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="sr-nowrap"><?= sr_pill($payableAmount < 0 ? 'red' : 'green', number_format(round($payableAmount), 2), false, 'mdi-cash') ?></td>
                                                <td><?= sr_pill($status_tone, $status_text) ?></td>
                                                <td><?= sr_time_cell($settlement['created_at'] ?? '') ?></td>
                                                <td class="text-right">
                                                    <div class="sr-actions">
                                                        <?php if ($is_pending_with_me): ?>
                                                            <a href="javascript:void(0);" class="sr-open-btn" onclick="approveSettlement(<?= $setl_id ?>, '<?= $inv_js ?>', <?= $settlement['emp_id'] ?>)"><i class="mdi mdi-check"></i> <?= __('approve') ?></a>
                                                        <?php else: ?>
                                                            <a href="javascript:void(0);" class="sr-open-btn" onclick="viewSettlementDetails(<?= $setl_id ?>, '<?= $inv_js ?>')"><i class="mdi mdi-eye"></i> <?= __('view') ?></a>
                                                        <?php endif; ?>
                                                        <div class="btn-group dropdown">
                                                            <a href="javascript:void(0);" class="sr-more-btn dropdown-toggle arrow-none" data-toggle="dropdown" data-boundary="viewport" aria-haspopup="true" aria-expanded="false"><i class="mdi mdi-dots-vertical"></i></a>
                                                            <div class="dropdown-menu dropdown-menu-right">
                                                                <?php if ($is_pending_with_me): ?>
                                                                    <a class="dropdown-item" href="javascript:void(0);" onclick="viewSettlementDetails(<?= $setl_id ?>, '<?= $inv_js ?>')"><i class="mdi mdi-eye"></i><?= __('view') ?></a>
                                                                <?php endif; ?>
                                                                <a class="dropdown-item" href="settlement_status_history.php?request_inv_no=<?= urlencode($settlement['request_inv_no']) ?>"><i class="mdi mdi-history"></i><?= __('history') ?></a>
                                                                <?php if ($settlement['settlement_status'] === 'approved'): ?>
                                                                    <div class="dropdown-divider"></div>
                                                                    <a class="dropdown-item" href="javascript:void(0);" onclick="processSettlementPayment(<?= $setl_id ?>, '<?= $inv_js ?>')"><i class="mdi mdi-check-circle text-success"></i><?= __('clear_settlement') ?></a>
                                                                <?php endif; ?>
                                                                <?php if ($is_pending_with_me): ?>
                                                                    <div class="dropdown-divider"></div>
                                                                    <a class="dropdown-item" href="javascript:void(0);" onclick="rejectSettlement(<?= $setl_id ?>, '<?= $inv_js ?>')"><i class="mdi mdi-close text-danger"></i><?= __('reject') ?></a>
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
                                <?= generate_pagination_controls($currentPage, $totalPages, $totalItems, $itemsPerPage, $limitOptions, $showAll, ['status' => $current_filter, 'search' => $searchTerm], $unfilteredTotalItems) ?>
                            </div>
                        <?php else: ?>
                            <?= sr_empty_state(__('no_records_found'), __('no_settlements_to_display')) ?>
                        <?php endif; ?>
                    </div>

                </div>
            </div>
            <footer class="footer"><?= $site_footer ?? '© 2025 Almutlak' ?></footer>
        </div>
    </div>

    <script src="assets/js/jquery.min.js"></script>
    <script src="assets/js/jquery.slimscroll.js"></script>
    <script src="assets/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/metisMenu.min.js"></script>
    <script src="assets/js/waves.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="./plugins/select2/js/select2.min.js"></script>
    <script src="assets/js/jquery.core.js"></script>
    <script src="assets/js/jquery.app.js?t=<?= time() ?>"></script>
        <?= sr_list_js() ?>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/dropzone/5.9.3/min/dropzone.min.js"></script>

    <script>

        // Configuration constants
        const MAX_FILE_SIZE_MB = 10;
        
        // Current user details - Make window-global for access in jquery.app.js?t=<?= time() ?>
        window.currentUserType = <?= json_encode($_SESSION['user_type'] ?? ''); ?>;
        window.currentUserId = <?= (int)$empid; ?>;
        window.currentUserTypeNormalized = String(window.currentUserType || '').trim().toLowerCase().replace(/[\s-]+/g, '_');
        
        // Also set as regular constants for backward compatibility
        const currentUserType = window.currentUserType;
        const currentUserId = window.currentUserId;
        const isHRPayroll = <?= !empty($isHR_Payroll) ? 'true' : 'false'; ?> ||
            window.currentUserTypeNormalized === 'hr_payroll' ||
            window.currentUserTypeNormalized === 'hrpayroll';

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
                    <p><strong><?= __("amount") ?>:</strong> <span class="badge badge-success"><i class="icon-saudi_riyal"></i> ${parseFloat(settlementAmount).toFixed(2)}</span></p>
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
