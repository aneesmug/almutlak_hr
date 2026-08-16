<?php
require_once __DIR__ . '/includes/db.php';

require_once __DIR__ . '/includes/session_check.php';
$query = mysqli_query($conDB, "SELECT * FROM `admin_login` WHERE `id_iqama`='" . $username . "'");
if (mysqli_num_rows($query) == 1) {
    include("./includes/avatar_select.php");

$color = array("primary", "success", "info", "warning", "danger", "dark");

// Access scope labels for dashboard card
$allowed_company_names = 'All Companies';
if (!empty($allowed_companies_array)) {
    $company_ids = implode(',', array_map('intval', $allowed_companies_array));
    $comp_query = mysqli_query($conDB, "SELECT GROUP_CONCAT(DISTINCT `comp_name` SEPARATOR ', ') AS `names` FROM `companies` WHERE `id` IN ($company_ids) OR `comp_id` IN ($company_ids)");
    if ($comp_query && $comp_row = mysqli_fetch_assoc($comp_query)) {
        $allowed_company_names = $comp_row['names'] ?: 'All Companies';
    }
}

$allowed_department_names = 'All Departments';
if (!empty($allowed_departments_array)) {
    $department_ids = implode(',', array_map('intval', $allowed_departments_array));
    $dept_query = mysqli_query($conDB, "SELECT GROUP_CONCAT(DISTINCT `dep_nme` SEPARATOR ', ') AS `names` FROM `department` WHERE `id` IN ($department_ids)");
    if ($dept_query && $dept_row = mysqli_fetch_assoc($dept_query)) {
        $allowed_department_names = $dept_row['names'] ?: 'All Departments';
    }
}

$allowed_employee_names = null; // Only show if employees are specifically assigned
if (!empty($allowed_employees_array)) {
    $employee_ids = implode(',', array_map('intval', $allowed_employees_array));
    $emp_query = mysqli_query($conDB, "SELECT GROUP_CONCAT(DISTINCT CONCAT(`name`) SEPARATOR ', ') AS `names` FROM `employees` WHERE `emp_id` IN ($employee_ids)");
    if ($emp_query && $emp_row = mysqli_fetch_assoc($emp_query)) {
        $allowed_employee_names = $emp_row['names'];
    }
}

// Fallback department scope (legacy-safe):
// If user has no explicit allowed_departments/allowed_employees restrictions,
// non-HR/non-admin users should still be limited to their own department.
$can_see_all_employees = (
    function_exists('canSeeAllEmployeesByRole')
        ? canSeeAllEmployeesByRole(true)
        : (
            $is_system_admin ||
            $user_type == 'administrator' ||
            $user_dept == 5 ||
            $isHR ||
            $isDeptHr ||
            $user_dept == 1
        )
);
$has_explicit_scope_restrictions = function_exists('hasExplicitEmployeeScopeRestrictions')
    ? hasExplicitEmployeeScopeRestrictions(true)
    : (!empty($allowed_companies_array) || !empty($allowed_departments_array) || !empty($allowed_employees_array));
$fallback_dept_filter_employees = (!$can_see_all_employees && !$has_explicit_scope_restrictions && !empty($user_dept))
    ? " AND `employees`.`dept`='" . mysqli_real_escape_string($conDB, $user_dept) . "'"
    : "";
$fallback_dept_filter_emp = (!$can_see_all_employees && !$has_explicit_scope_restrictions && !empty($user_dept))
    ? " AND `emp`.`dept`='" . mysqli_real_escape_string($conDB, $user_dept) . "'"
    : "";
$fallback_dept_filter_plain = (!$can_see_all_employees && !$has_explicit_scope_restrictions && !empty($user_dept))
    ? " AND `dept`='" . mysqli_real_escape_string($conDB, $user_dept) . "'"
    : "";

?>
    <!doctype html>
    <html lang="<?= $current_lang ?? 'en' ?>" <?= ($is_rtl ?? false) ? 'dir="rtl"' : '' ?>>

    <head>
        <meta charset="utf-8" />
        <title><?= $site_title ?> - Dashboard</title>
        <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
        <!--        <meta content="A fully featured admin theme which can be used to build CRM, CMS, etc." name="description" />-->
        <meta content="Anees Afzal" name="author" />
        <meta http-equiv="X-UA-Compatible" content="IE=edge" />

        <!-- App favicon -->
        <link rel="shortcut icon" href="<?=get_setting($conDB, 'favicon')?>">

        <!-- App css -->
        <link href="assets/css/bootstrap.min.css" rel="stylesheet" type="text/css" />
        <link href="assets/css/icons.css" rel="stylesheet" type="text/css" />
        <link href="assets/css/metismenu.min.css" rel="stylesheet" type="text/css" />
        <link href="assets/css/style.css" rel="stylesheet" type="text/css" />
        <link href="assets/css/style_dark.css" rel="stylesheet" type="text/css" />

        <!-- DataTables -->
        <link href="./plugins/datatables/dataTables.bootstrap4.min.css" rel="stylesheet" type="text/css" />
        <link href="./plugins/datatables/buttons.bootstrap4.min.css" rel="stylesheet" type="text/css" />
        <!-- Responsive datatable examples -->
        <link href="./plugins/datatables/responsive.bootstrap4.min.css" rel="stylesheet" type="text/css" />
        <script src="assets/js/modernizr.min.js"></script>

        <style>
            /* Ensure table header and body align */
            .dataTables_wrapper .dataTables_scroll {
                overflow: auto;
                width: 100% !important;
            }
            .dataTables_scrollHeadInner {
                width: 100% !important;
            }
            .dataTables_scrollHeadInner table {
                width: 100% !important;
            }
            .dataTables_scrollBody {
                width: 100% !important;
                overflow: hidden !important;
            }

            /* Modern Stats Card Design */
            .stats-card {
                border-radius: 16px;
                box-shadow: 0 2px 12px rgba(0,0,0,0.08);
                padding: 32px 24px 24px 24px;
                margin-bottom: 28px;
                transition: box-shadow 0.2s, transform 0.2s;
                border: none;
                position: relative;
                min-height: 180px;
                display: flex;
                flex-direction: row;
                align-items: center;
                background: var(--card-gradient, linear-gradient(90deg,#2196f3 0%,#21cbf3 100%));
                color: #fff;
                overflow: hidden;
            }
            .stats-card:hover {
                box-shadow: 0 8px 32px rgba(0,0,0,0.18);
                transform: translateY(-4px) scale(1.02);
            }
            .stats-card-icon {
                background: rgba(255,255,255,0.18);
                border-radius: 50%;
                width: 72px;
                height: 72px;
                display: flex;
                align-items: center;
                justify-content: center;
                font-size: 40px;
                margin-right: 28px;
                box-shadow: 0 2px 16px rgba(0,0,0,0.12);
                position: relative;
                transition: transform 0.2s;
                flex-direction: column;
            }
            .stats-card-count-circle {
                background: #fff;
                color: #2196f3;
                border-radius: 50%;
                width: 38px;
                height: 38px;
                display: flex;
                align-items: center;
                justify-content: center;
                font-size: 20px;
                font-weight: 700;
                margin-bottom: 6px;
                box-shadow: 0 2px 8px rgba(0,0,0,0.10);
            }
            .stats-card-icon i {
                font-size: 28px;
                color: #2196f3;
            }
            .stats-card-icon:hover {
                transform: scale(1.10) rotate(-6deg);
            }
            .stats-card-icon .stats-card-tooltip {
                display: none;
                position: absolute;
                top: -32px;
                left: 50%;
                transform: translateX(-50%);
                background: #222;
                color: #fff;
                padding: 4px 10px;
                border-radius: 6px;
                font-size: 12px;
                white-space: nowrap;
                z-index: 10;
            }
            .stats-card-icon:hover .stats-card-tooltip {
                display: block;
            }
            .stats-card-content {
                flex: 1;
                display: flex;
                flex-direction: column;
                align-items: flex-start;
                position: relative;
            }
            .stats-card-badge {
                position: absolute;
                top: 0;
                right: 0;
                background: #50a5f1;
                color: #fff;
                font-size: 11px;
                font-weight: 600;
                padding: 2px 8px;
                border-radius: 8px;
                box-shadow: 0 2px 8px rgba(80,165,241,0.12);
                z-index: 2;
            }
            .stats-card-value {
                font-size: 36px;
                font-weight: 700;
                color: #222;
                margin-bottom: 4px;
            }
            .stats-card-label {
                font-size: 18px;
                font-weight: 700;
                color: #fff;
                margin-bottom: 12px;
                letter-spacing: 0.5px;
            }
            .stats-card-footer {
                display: flex;
                align-items: center;
                gap: 18px;
            }
            .stats-card-percentage {
                font-size: 15px;
                font-weight: 600;
                display: flex;
                align-items: center;
            }
            .stats-card-percentage.positive {
                color: #34c38f;
            }
            .stats-card-percentage.negative {
                color: #f46a6a;
            }
            .stats-card-percentage i {
                font-size: 18px;
                margin-right: 4px;
            }
            .stats-card-status {
                font-size: 13px;
                font-weight: 600;
                color: #34c38f;
                background: #eaf7ea;
                border-radius: 8px;
                padding: 2px 10px;
            }
            .stats-card-status.active {
                color: #34c38f;
                background: #eaf7ea;
            }
            @media (max-width: 767px) {
                .stats-card {
                    flex-direction: column;
                    align-items: flex-start;
                    padding: 16px 8px;
                    min-height: 160px;
                }
                .stats-card-icon {
                    margin-bottom: 10px;
                    margin-right: 0;
                }
                .stats-card-value {
                    font-size: 26px;
                }
            }
            /* Badge-based card styling for Allowed Employees */
            .allowed-employees-card {
                background: #f8f9fa;
                border: 1px solid #e0e0e0;
                border-radius: 8px;
                padding: 16px;
            }

            .allowed-employees-card-title {
                text-transform: uppercase;
                letter-spacing: 0.5px;
                font-size: 13px;
                color: #333;
                margin-bottom: 12px;
                font-weight: 600;
            }

            .allowed-employees-card-content {
                display: flex;
                flex-wrap: wrap;
                gap: 6px;
                max-height: 160px;
                overflow-y: auto;
                padding-right: 4px;
            }
            .allowed-employees-card-content::-webkit-scrollbar {
                width: 6px;
            }
            .allowed-employees-card-content::-webkit-scrollbar-thumb {
                background: rgba(0, 0, 0, 0.18);
                border-radius: 6px;
            }
            .allowed-employees-card-content::-webkit-scrollbar-track {
                background: transparent;
            }

            .allowed-employees-card .employee-badge {
                display: inline-flex;
                align-items: center;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                color: #fff;
                padding: 6px 12px;
                border-radius: 20px;
                font-size: 12px;
                box-shadow: 0 2px 8px rgba(102, 126, 234, 0.3);
                white-space: nowrap;
            }

            .allowed-employees-card .employee-badge.all-employees {
                background: linear-gradient(135deg, #a8edea 0%, #fed6e3 100%);
                color: #333;
                box-shadow: 0 2px 8px rgba(168, 237, 234, 0.3);
            }

            .allowed-employees-card .no-data-message {
                color: #999;
                font-size: 13px;
                font-style: italic;
            }

            /* Org drilldown - breadcrumb */
            .drilldown-breadcrumb {
                display: flex;
                flex-wrap: wrap;
                align-items: center;
                gap: 4px;
                padding: 12px 16px;
                background: #fff;
                border: 1px solid #e5e9f2;
                border-radius: 12px;
                box-shadow: 0 1px 3px rgba(15, 23, 42, 0.04);
            }
            .drilldown-crumb {
                display: inline-flex;
                align-items: center;
                gap: 6px;
                max-width: 220px;
                padding: 6px 14px;
                border-radius: 20px;
                background: #eef1fb;
                color: #556ee6;
                font-size: 0.82rem;
                font-weight: 600;
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
                cursor: pointer;
                border: 1px solid transparent;
                transition: background-color .15s ease, color .15s ease, box-shadow .15s ease, transform .1s ease;
            }
            .drilldown-crumb:hover {
                background: #556ee6;
                color: #fff;
                box-shadow: 0 3px 10px rgba(85, 110, 230, 0.3);
                transform: translateY(-1px);
            }
            .drilldown-crumb-root i {
                font-size: 0.95rem;
            }
            .drilldown-crumb-current {
                background: linear-gradient(135deg, #556ee6 0%, #6a7cf0 100%);
                color: #fff;
                cursor: default;
                font-weight: 700;
                box-shadow: 0 3px 10px rgba(85, 110, 230, 0.25);
            }
            .drilldown-crumb-current:hover {
                background: linear-gradient(135deg, #556ee6 0%, #6a7cf0 100%);
                color: #fff;
                transform: none;
                box-shadow: 0 3px 10px rgba(85, 110, 230, 0.25);
            }
            .drilldown-breadcrumb i.mdi-chevron-right {
                color: #c3c9d5;
                font-size: 1rem;
                flex-shrink: 0;
            }
            @media (max-width: 767px) {
                .drilldown-breadcrumb {
                    flex-wrap: nowrap;
                    overflow-x: auto;
                    -webkit-overflow-scrolling: touch;
                }
                .drilldown-crumb {
                    flex-shrink: 0;
                }
            }

            /* Org drilldown - tiles */
            .drilldown-tile-unassigned {
                background: linear-gradient(90deg,#6c757d 0%,#868e96 100%) !important;
            }

            /* Org drilldown - employees search toolbar (reused from filter_employee_by_comp.php) */
            .comp-search-group {
                display: flex;
                flex-direction: row;
                align-items: center;
                flex-wrap: wrap;
                gap: 20px;
                padding: 10px 14px;
                background: #f4f6f9;
                border: 1px solid #e2e8f0;
                border-radius: 10px;
            }
            .comp-search-field {
                display: flex;
                align-items: center;
                gap: 10px;
                flex: 1 1 auto;
            }
            .comp-search-field-status {
                flex: 0 0 auto;
            }
            .comp-search-field-status select {
                width: auto;
                min-width: 180px;
            }
            .comp-search-label {
                margin: 0;
                font-size: 0.72rem;
                font-weight: 700;
                text-transform: uppercase;
                letter-spacing: .04em;
                color: #8a94a6;
                white-space: nowrap;
            }
            .comp-search-input {
                position: relative;
                display: flex;
                align-items: center;
                flex: 1 1 auto;
                gap: 8px;
            }
            .comp-search-input i.mdi-magnify {
                position: absolute;
                left: 12px;
                color: #94a3b8;
                font-size: 1rem;
                pointer-events: none;
            }
            .comp-search-input input {
                padding-left: 34px;
                border-radius: 8px;
                border: 1px solid #e2e8f0;
                background: #fff;
                transition: border-color .15s ease, box-shadow .15s ease, background-color .15s ease;
            }
            .comp-search-input input:focus {
                border-color: rgba(67, 97, 238, 0.5);
                box-shadow: 0 0 0 3px rgba(67, 97, 238, 0.12);
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

        <!-- Begin page -->
        <div id="wrapper">

            <!-- ========== Left Sidebar Start ========== -->
            <div class="left side-menu">

                <div class="slimScrollDiv active" id="remove-scroll">

                    <!-- LOGO -->
                    <div class="topbar-left">
                        <a href="dashboard.php" class="logo">
                            <span>
                                <img src="<?=get_setting($conDB, 'logo')?>" alt="" height="22">
                            </span>
                            <i>
                                <img src="<?=get_setting($conDB, 'white_logo')?>" alt="" height="28">
                            </i>
                        </a>
                    </div>

                    <!-- User box -->


                    <!--- Sidemenu -->
                    <?php include("./includes/main_menu.php"); ?>
                    <!-- Sidebar -->

                    <div class="clearfix"></div>

                </div>
                <!-- Sidebar -left -->

            </div>
            <!-- Left Sidebar End -->

            <!-- ============================================================== -->
            <!-- Start right Content here -->
            <!-- ============================================================== -->

            <div class="content-page">

                <!-- Top Bar Start -->
                <?php include("./includes/topbar.php"); ?>
                <!-- Top Bar End -->
                <!-- Start Page content -->
                <div class="content">
                    <div class="container-fluid">

                        <div class="col-xl-12">
                            <div class="card-box">
                                <h4 class="header-title m-t-0 m-b-30"><?= __('all_employees_grouping') ?></h4>
                                <ul class="nav nav-tabs tabs-bordered">
                                    <li class="nav-item">
                                        <a href="#bycompany-b1" data-toggle="tab" aria-expanded="false" class="nav-link active show">
                                            <i class="fa fa-layer-group mr-2"></i> <?= __('companies') ?>
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a href="#bydepartment-b1" data-toggle="tab" aria-expanded="false" class="nav-link">
                                            <i class="fi-monitor mr-2"></i> <?= __('departments') ?>
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a href="#bylist-b1" data-toggle="tab" aria-expanded="true" class="nav-link">
                                            <i class="fi-head mr-2"></i> <?= __('employees_list') ?>
                                        </a>
                                    </li>
                                </ul>
                                <div class="tab-content">
                                    <div class="tab-pane" id="bydepartment-b1">
                                        <!-- <div class="tab-pane" id="bydepartment-b1"> -->
                                        <?php  ?><div class="row text-center">

                                            <?php
                                            // ================================================================
                                            // DEPARTMENT-BASED ACCESS CONTROL FOR DEPARTMENT GROUPING
                                            // ================================================================
                                            // HR Department (dept 5) and System Admins can see all departments
                                            // All other users can only see their own department
                                            $can_see_all_departments = (
                                                function_exists('canSeeAllEmployeesByRole')
                                                    ? canSeeAllEmployeesByRole(true)
                                                    : (
                                                        $is_system_admin ||
                                                        $user_type == 'administrator' ||
                                                        $user_dept == 5 ||
                                                        $isHR ||
                                                        $isDeptHr ||
                                                        $user_dept == 1
                                                    )
                                            );
                                            
                                            // Apply company and department filters
                                            $company_filter = getCompanyFilterSQL('employees.comp_no', true);
                                            $department_filter = getDepartmentFilterSQL('employees.dept', true);
                                            $employee_filter = getEmployeeFilterSQL('employees.emp_id', true);
                                            
                                            // Query to get department grouping (same query for all users, filters handle access)
                                            $querygrp = mysqli_query($conDB, "SELECT 
                                                count(`employees`.`dept`) AS `empcountgrp`,
                                                `employees`.`dept`, 
                                                `department`.`dep_nme`,
                                                `department`.`dep_nme_ar`,
                                                `department`.`dept_clr` AS `color`
                                                FROM `employees` 
                                                -- LEFT JOIN `dept_clr` ON `dept_clr`.`dept_name` = `employees`.`dept`
                                                LEFT JOIN `department` ON `department`.`id` = `employees`.`dept`
                                                WHERE `employees`.`status` = 1" . $company_filter . $department_filter . $employee_filter . $fallback_dept_filter_employees . "
                                                GROUP BY `employees`.`dept`
                                                ORDER BY `department`.`dep_nme` ASC");
                                            
                                            // $querygrp = mysqli_query($conDB, "SELECT count(`dept`) AS `empcountgrp`,`dept` FROM `employees` WHERE `emp_sup_type`='mocha' AND `status` = 1 GROUP BY `dept`");
                                            if ($querygrp) {
                                                $allowedCardColors = ["custom", "purple", "primary", "success"];
                                                // Total active employees (for percentage calculation) - must respect access filters
                                                $totalEmpRes = mysqli_query($conDB, "SELECT COUNT(*) AS total FROM employees WHERE status=1" . $company_filter . $department_filter . $employee_filter . $fallback_dept_filter_plain);
                                                $totalEmpRow = mysqli_fetch_assoc($totalEmpRes);
                                                $totalEmployees = $totalEmpRow && isset($totalEmpRow['total']) ? (int)$totalEmpRow['total'] : 1;
                                                while ($rec = mysqli_fetch_array($querygrp)) {
                                                    $rawCardColor = strtolower(trim((string)($rec["color"] ?? '')));
                                                    $cardColor = in_array($rawCardColor, $allowedCardColors, true) ? $rawCardColor : 'custom';
                                                    $deptCount = (int)$rec["empcountgrp"];
                                                    $percentage = round(($deptCount / $totalEmployees) * 100, 1);
                                            ?>
                                                <div class="col-sm-4 col-xl-3" onclick="window.location.href='filter_employee_by_dept.php?page=1&status=1&status=active&dept=<?= $rec["dept"] ?>'" style="cursor: pointer;">
                                                    <div class="stats-card professional-theme theme-<?= $cardColor ?>" data-color="<?= $cardColor ?>">
                                                        <div class="stats-card-icon professional-theme theme-<?= $cardColor ?>" data-color="<?= $cardColor ?>">
                                                            <div class="stats-card-count-circle"><?= $deptCount ?></div>
                                                            <span class="stats-card-tooltip">Department Info</span>
                                                            <i class="fa fa-building"></i>
                                                        </div>
                                                        <div class="stats-card-content">
                                                            <div class="stats-card-label" style="color:#fff;opacity:0.95;"><?= ($is_rtl ?? false) ? $rec["dep_nme_ar"] : $rec["dep_nme"] ?></div>
                                                            <div class="stats-card-footer">
                                                                <span class="stats-card-percentage positive">
                                                                    <i class="mdi mdi-trending-up"></i>
                                                                </span>
                                                            </div>
                                                            <!-- Progress from department share of total employees -->
                                                            <div style="width:100%;margin-top:18px;">
                                                                <div style="background:rgba(255,255,255,0.25);border-radius:8px;height:12px;overflow:hidden;">
                                                                    <div class="progress-bar-fill-animated" style="height:12px;border-radius:8px;width:<?= $percentage ?>%;background:rgba(255,255,255,0.9);box-shadow:0 0 8px rgba(255,255,255,0.6);transition:width 0.6s;"></div>
                                                                </div>
                                                                <div style="font-size:13px;color:#fff;opacity:0.85;margin-top:4px;">
                                                                    <?= $percentage ?>% <?=__('of_total_employees') ?>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            <?php 
                                                } // end while
                                            } // end if ($querygrp)
                                            ?>

                                        </div>
                                    </div>
                                    <div class="tab-pane active show" id="bycompany-b1">
                                        <div id="drilldownBreadcrumb" class="drilldown-breadcrumb mb-3">
                                            <span class="drilldown-crumb drilldown-crumb-root drilldown-crumb-current" data-level="companies"><i class="mdi mdi-home-variant-outline"></i> <?= __('companies') ?></span>
                                        </div>

                                        <div class="row text-center" id="drilldownTilesContainer">

                                            <?php
                                            // ================================================================
                                            // COMPANY-BASED ACCESS CONTROL FOR COMPANY GROUPING
                                            // ================================================================
                                            // Companies tab shows allowed companies only (no department filtering here)
                                            // Department filtering is applied further down the drilldown (city/location/dept/sub-dept)

                                            $company_filter = getCompanyFilterSQL('employees.comp_no', true);
                                            $employee_filter = getEmployeeFilterSQL('employees.emp_id', true);

                                            $querygrp = mysqli_query($conDB, "SELECT
                                                count(`employees`.`dept`) AS `empcountgrp`,
                                                `employees`.`comp_no`,
                                                `companies`.`comp_name`,
                                                `companies`.`comp_name_ar`,
                                                `companies`.`comp_id`
                                                FROM `employees`
                                                LEFT JOIN `companies` ON `companies`.`comp_id` = `employees`.`comp_no`
                                                WHERE `employees`.`status` = 1" . $company_filter . $employee_filter . $fallback_dept_filter_employees . "
                                                GROUP BY `employees`.`comp_no`");

                                            if ($querygrp) {
                                                $colorArr = ["primary","success","warning","danger","info","dark"];
                                                $colorCount = count($colorArr);
                                                $cardIndex = 0;
                                                $company_filter_total = getCompanyFilterSQL('comp_no', true);
                                                $employee_filter_total = getEmployeeFilterSQL('emp_id', true);
                                                $totalEmpRes = mysqli_query($conDB, "SELECT COUNT(*) AS total FROM employees WHERE status=1" . $company_filter_total . $employee_filter_total . $fallback_dept_filter_plain);
                                                $totalEmpRow = mysqli_fetch_assoc($totalEmpRes);
                                                $totalEmployees = $totalEmpRow && isset($totalEmpRow['total']) ? (int)$totalEmpRow['total'] : 1;
                                                while ($rec = mysqli_fetch_array($querygrp)) {
                                                    $cardColor = $colorArr[$cardIndex % $colorCount];
                                                    $compCount = (int)$rec["empcountgrp"];
                                                    $percentage = round(($compCount / $totalEmployees) * 100, 1);
                                                    $compLabel = ($is_rtl ?? false) ? ($rec["comp_name_ar"] ?: $rec["comp_name"]) : ($rec["comp_name"] ?: $rec["comp_name_ar"]);
                                                    echo generate_drilldown_tile(
                                                        $compCount,
                                                        $compLabel ?: ('Company #' . $rec["comp_no"]),
                                                        'fa fa-industry',
                                                        $cardColor,
                                                        $percentage,
                                                        ['next-level' => 'cities', 'company' => $rec["comp_no"], 'company-name' => $compLabel]
                                                    );
                                                    $cardIndex++;
                                                } // end while
                                            } // end if ($querygrp)
                                            ?>

                                        </div>

                                        <!-- Employees toolbar - shown only once the drilldown reaches a department/sub-department with no further tiles -->
                                        <div id="drilldownEmployeesToolbar" class="row filter-controls mx-auto mb-3 d-none" style="max-width: 800px;">
                                            <div class="col-12">
                                                <div class="comp-search-group">
                                                    <div class="comp-search-field">
                                                        <label for="drilldownSearchFilter" class="comp-search-label"><?= __('search') ?></label>
                                                        <div class="comp-search-input">
                                                            <i class="mdi mdi-magnify"></i>
                                                            <input type="search" class="form-control" id="drilldownSearchFilter" placeholder="..." autocomplete="off">
                                                        </div>
                                                    </div>
                                                    <div class="comp-search-field comp-search-field-status">
                                                        <label for="drilldownStatusFilter" class="comp-search-label"><?= __('filter_by_status') ?></label>
                                                        <select class="form-control" id="drilldownStatusFilter">
                                                            <option value="all"><?= __('all_option') ?></option>
                                                            <option value="active" selected><?= __('active') ?></option>
                                                            <option value="on_vacation"><?= __('on_vacations') ?></option>
                                                            <option value="inactive"><?= __('inactive') ?></option>
                                                        </select>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="row d-none" id="drilldownEmployeesCards"></div>
                                        <div class="row mt-4 d-none" id="drilldownEmployeesPaginationRow">
                                            <div class="col-12" id="drilldownEmployeesPagination"></div>
                                        </div>
                                    </div>
                                    <div class="tab-pane" id="bylist-b1">
                                        <table id="employee_vac" class="table table-striped table-bordered dt-responsive nowrap" style="border-collapse: collapse; border-spacing: 0; width: 100%;">
                                            <thead>
                                                <tr>
                                                    <th width="50"><?= __('emp_id') ?></th>
                                                    <th><?= __('employee_name') ?></th>
                                                    <th><?= __('department') ?></th>
                                                    <th><?= __('iqama_id') ?></th>
                                                    <th><?= __('mobile') ?></th>
                                                    <th><?= __('date_of_birth') ?></th>
                                                    <th><?= __('sponsorship') ?></th>
                                                    <th><?= __('blood_group') ?></th>
                                                    <th><?= __('gender') ?></th>
                                                    <th width="80"><?= __('country') ?></th>
                                                    <th><?= __('joining_date') ?></th>
                                                    <th width="80"><?= __('action') ?></th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php
                                                // ================================================================
                                                // DEPARTMENT-BASED ACCESS CONTROL
                                                // ================================================================
                                                // Apply company and department filters for all users
                                                $company_filter = getCompanyFilterSQL('emp.comp_no', true);
                                                $department_filter = getDepartmentFilterSQL('emp.dept', true);
                                                $employee_filter = getEmployeeFilterSQL('emp.emp_id', true);
                                                
                                                $sql = "SELECT 
                                                        `emp`.*, 
                                                        CASE 
                                                            WHEN `emp`.`sex` = 1 THEN 'male' 
                                                            WHEN `emp`.`sex` = 2 THEN 'female'
                                                            ELSE ''
                                                        END AS `sex`,  
                                                        `countries`.`name` AS `country_name`,
                                                        `countries`.`name_ar` AS `country_name_ar`,
                                                        `department`.`dep_nme`,
                                                        `department`.`dep_nme_ar`,
                                                        `sponsorship`.`sponsor`,
                                                        `sponsorship`.`sponsor_ar`
                                                        FROM `employees` `emp`
                                                        LEFT JOIN `department` ON `department`.`id` = `emp`.`dept` 
                                                        LEFT JOIN `countries` ON `countries`.`id` = `emp`.`country` 
                                                        LEFT JOIN `sponsorship` ON `sponsorship`.`id` = `emp`.`emp_sup_type` 
                                                        WHERE `emp`.`status`=1 AND `emp`.`fly`=0" . $company_filter . $department_filter . $employee_filter . $fallback_dept_filter_emp . " ";
                                                
                                                $query = mysqli_query($conDB, $sql);

                                                while ($rec = mysqli_fetch_array($query)) {
                                                    $id = $rec["id"];
                                                    $name = $rec["name"];
                                                    $emp_id = $rec["emp_id"];
                                                    $iqama = $rec["iqama"];
                                                    $mobile = $rec["mobile"];
                                                    $email_gt = $rec["email"];
                                                    $salary = $rec["salary"];
                                                    $vacation_days = $rec["vacation_days"];
                                                    $joining_date = $rec["joining_date"];
                                                    $emp_avatar = $rec["avatar"];
                                                    $emp_status = $rec["status"];
                                                    $emp_status_fly = $rec["fly"];
                                                    $emptype = $rec["emptype"];
                                                    $dept = $rec["dep_nme"];
                                                    $dept_ar = $rec["dep_nme_ar"];
                                                    $blood_type = $rec["blood_type"];
                                                    $emp_sup_type = $rec["sponsor"];
                                                    $emp_sup_type_ar = $rec["sponsor_ar"];
                                                    $date_dob_get = $rec["dob"];
                                                    $country_get = $rec["country_name"];
                                                    $country_get_ar = $rec["country_name_ar"];
                                                    $sex_get = $rec["sex"];
                                                    $mar_status_get = $rec["mar_status"];


                                                    $sql_count = mysqli_query($conDB, "SELECT COUNT(*) `emp_id` FROM `emp_vacation` WHERE `emp_id`='" . $emp_id . "' ");
                                                    $status_cont = mysqli_fetch_array($sql_count)[0];

                                                    $sql_count_fly = mysqli_query($conDB, "SELECT COUNT(*) `emp_id` FROM `emp_vacation` WHERE `emp_id`='" . $emp_id . "' && `note`='Fly' ");
                                                    $cont_fly = mysqli_fetch_array($sql_count_fly)[0];

                                                    $sql_count_encashed = mysqli_query($conDB, "SELECT COUNT(*) `emp_id` FROM `emp_vacation` WHERE `emp_id`='" . $emp_id . "' && `note`='Encashed' ");
                                                    $cont_fly = mysqli_fetch_array($sql_count_encashed)[0];

                                                    $checkGander = ($sex_get == 'male') ? './assets/emp_pics/defult.png' : './assets/emp_pics/defultFemale.jpg';
                                                    $emp_avatar = (file_exists("./assets/emp_pics/" . explode("/", $emp_avatar)[3])) ? $emp_avatar : $checkGander;
                                                ?>
                                                    <tr>
                                                        <td><?= $emp_id; ?></td>
                                                        <td>
                                                            <img src="<?= $emp_avatar; ?>" class="rounded-circle bx-shadow-lg" width="50">
                                                             <span class='copyToClipboard'><?= getDisplayName(parseName($rec["name"])); ?></span> <i class='fa fa-clipboard'></i>
                                                        </td>
                                                        <td><?= ($is_rtl ?? false) ? $dept_ar : $dept ?></td>
                                                        <td><span class='copyToClipboard'><?= $iqama; ?></span> <i class='fa fa-clipboard'></i></td>
                                                        <td><span class='copyToClipboard'><?= $mobile; ?></span> <i class='fa fa-clipboard'></i></td>
                                                        <td><?= $date_dob_get; ?></td>
                                                        <td><?= ($is_rtl ?? false) ? $emp_sup_type_ar : $emp_sup_type; ?></td>
                                                        <td><?= $blood_type; ?></td>
                                                        <td><?= __($sex_get); ?></td>
                                                        <td><?= ($is_rtl ?? false) ? $country_get_ar : $country_get; ?></td>
                                                        <td><?= $joining_date; ?></td>
                                                        <td>
                                                            <div class='btn-group dropdown'>
                                                                <a href='javascript: void(0);' class='table-action-btn dropdown-toggle arrow-none btn btn-light btn-sm' data-toggle='dropdown' aria-expanded='false'><i class='mdi mdi-dots-horizontal'></i></a>
                                                                <div class='dropdown-menu dropdown-menu-right' x-placement='bottom-end'>
                                                                    <a class='dropdown-item text-dark' href='view_employee.php?emp_id=<?= $emp_id ?>'><i class='mdi mdi-eye-outline mr-2 font-18 vertical-middle'></i><?= __('open') ?></a>
                                                                    <?php
                                                                    if ($emp_status == "1") {
                                                                        // Only system_admin, hr_operations, hr_recruitment can edit employees
                                                                        if ($is_system_admin || $user_type === 'hr_operations' || $user_type === 'hr_recruitment') {
                                                                    ?>
                                                                            <a href='edit_employee.php?emp_id=<?= $emp_id ?>' class='dropdown-item text-custom'><i class='fa fa-edit mr-2 font-18 vertical-middle'></i><?= __('edit') ?></a>
                                                                        <?php }
                                                                    }
                                                                    ?>
                                                                </div>
                                                            </div>

                                                        </td>
                                                    </tr>
                                                <?php } ?>
                                            </tbody>
                                            <tfoot>
                                                <tr>
                                                    <th width="50"><?= __('emp_id') ?></th>
                                                    <th><?= __('employee_name') ?></th>
                                                    <th><?= __('department') ?></th>
                                                    <th><?= __('iqama_id') ?></th>
                                                    <th><?= __('mobile') ?></th>
                                                    <th><?= __('date_of_birth') ?></th>
                                                    <th><?= __('sponsorship') ?></th>
                                                    <th><?= __('blood_group') ?></th>
                                                    <th><?= __('gender') ?></th>
                                                    <th width="80"><?= __('country') ?></th>
                                                    <th><?= __('joining_date') ?></th>
                                                    <th width="80"><?= __('action') ?></th>
                                                </tr>
                                            </tfoot>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="card-box">
                            <h4 class="m-t-0 header-title"><?= __('access_scope') ?: 'Access Scope' ?></h4>
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="card-box" style="border: 1px solid #e5e7eb;">
                                        <h5 class="m-t-0"><?= __('allowed_companies') ?: 'Allowed Companies' ?></h5>
                                        <div class="small text-muted"><?= htmlspecialchars($allowed_company_names) ?></div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="card-box" style="border: 1px solid #e5e7eb;">
                                        <h5 class="m-t-0"><?= __('allowed_departments') ?: 'Allowed Departments' ?></h5>
                                        <div class="small text-muted"><?= htmlspecialchars($allowed_department_names) ?></div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <?php if (!empty($allowed_employee_names)): ?>
                                    <div class="allowed-employees-card" id="allowed-employees-badge-card">
                                        <div class="allowed-employees-card-title"><?= __('allowed_employees') ?: 'Allowed Employees' ?></div>
                                        <div class="allowed-employees-card-content" id="allowed-employees-container">
                                            <!-- Badges will be populated by JavaScript -->
                                        </div>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                    </div> <!-- container -->

                </div> <!-- content -->

                <footer class="footer">
                    <?= $site_footer ?>
                </footer>

            </div>


            <!-- ============================================================== -->
            <!-- End Right content here -->
            <!-- ============================================================== -->


        </div>
        <!-- END wrapper -->

        <!-- jQuery  -->
        <script src="assets/js/jquery.min.js"></script>
        <script src="assets/js/bootstrap.bundle.min.js"></script>
        <script src="assets/js/metisMenu.min.js"></script>
        <script src="assets/js/waves.js"></script>
        <script src="assets/js/jquery.slimscroll.js"></script>


        <!-- Modal-Effect -->
        <script type="text/javascript" src="./plugins/parsleyjs/parsley.min.js"></script>
        <script src="./plugins/moment/moment.js"></script>

        <!-- Required datatable js -->
        <script src="./plugins/datatables/jquery.dataTables.min.js"></script>
        <script src="./plugins/datatables/dataTables.bootstrap4.min.js"></script>
        <!-- Buttons examples -->
        <script src="./plugins/datatables/dataTables.buttons.min.js"></script>
        <script src="./plugins/datatables/buttons.bootstrap4.min.js"></script>
        <script src="./plugins/datatables/jszip.min.js"></script>
        <script src="./plugins/datatables/pdfmake.min.js"></script>
        <script src="./plugins/datatables/vfs_fonts.js"></script>
        <script src="./plugins/datatables/buttons.html5.min.js"></script>
        <script src="./plugins/datatables/buttons.print.min.js"></script>

        <!-- Key Tables -->
        <script src="./plugins/datatables/dataTables.keyTable.min.js"></script>

        <!-- Responsive examples -->
        <script src="./plugins/datatables/dataTables.responsive.min.js"></script>
        <script src="./plugins/datatables/responsive.bootstrap4.min.js"></script>

        <!-- Selection table -->
        <script src="./plugins/datatables/dataTables.select.min.js"></script>

        <!-- Make sure this is on EVERY page -->
        <script src="assets/js/notifications.js"></script>

        <!-- App js -->
        <script src="assets/js/jquery.core.js"></script>
        <script src="assets/js/jquery.app.js?t=<?= time() ?>"></script>


        <script type="text/javascript">
            $(document).ready(function() {
                // --- 1. Your Button Configuration ---
                // This section defines the export buttons (Excel, PDF, Print) for the table.
                var buttonConfig = [];
                var exportTitle = "Employee List"; // A title for the exported files.
                buttonConfig.push({
                    extend: 'excel',
                    exportOptions: {
                        columns: [0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10] // All columns except 'Action'
                    },
                    title: exportTitle,
                    className: 'btn-success'
                });
                buttonConfig.push({
                    extend: 'pdf',
                    exportOptions: {
                        columns: [0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10]
                    },
                    title: exportTitle,
                    className: 'btn-danger'
                });
                buttonConfig.push({
                    extend: 'print',
                    exportOptions: {
                        columns: [0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10]
                    },
                    title: exportTitle,
                    className: 'btn-dark'
                });

                // --- 2. Initialize the DataTable ---
                var table = $('#employee_vac').DataTable({
                    // --- ALL OPTIONS ARE NOW CORRECTLY PLACED INSIDE THIS OBJECT ---

                    lengthChange: false, // Hides the "Show X entries" dropdown.
                    buttons: buttonConfig, // Assigns the button configuration from above.

                    // --- MERGED AND CORRECTED initComplete ---
                    // All initialization logic is now in a single, correct function.
                    initComplete: function() {
                        var api = this.api();

                        // A) Create Column Filtering Dropdowns
                        // Targets Department, Sponsorship, Blood G., and Gender columns.
                        api.columns([2, 6, 7, 8, 9]).every(function() {
                            var column = this;
                            // The text from the <tfoot> is used as a placeholder initially.
                            var title = $(column.footer()).text();
                            var select = $('<select class="form-control form-control-sm"><option value="">' + title + ' (' + __('all') + ')</option></select>')
                                .appendTo($(column.footer()).empty()) // Clears the footer cell and adds the select dropdown.
                                .on('change', function() {
                                    // Perform an exact-match search on column change.
                                    var val = $.fn.dataTable.util.escapeRegex($(this).val());
                                    column.search(val ? '^' + val + '$' : '', true, false).draw();
                                });

                            // B) Populate the Select Options from table data.
                            column.data().unique().sort().each(function(d, j) {
                                if (d) { // Make sure data is not empty
                                    select.append('<option value="' + d + '">' + d + '</option>');
                                }
                            });
                        });

                        // C) Adjust columns after initialization.
                        api.columns.adjust().draw();
                    },
                    language: {
                        search: `<span>${__('search')}:</span> _INPUT_`,
                        searchPlaceholder: `${__('search')}...`,
                        lengthMenu: `${__('show')} _MENU_ ${__('entries')}`,
                        info: `${__('showing')} _START_ ${__('to')} _END_ ${__('of')} _TOTAL_ ${__('entries')}`,
                        infoEmpty: `${__('showing')} 0 ${__('to')} 0 ${__('of')} 0 ${__('entries')}`,
                        infoFiltered: `(${__('filtered_from')} _MAX_ ${__('total_entries')})`,
                        paginate: {
                            first: __('first'),
                            last: __('last'),
                            next: __('next'),
                            previous: __('previous')
                        },
                        emptyTable: __('no_data_available_in_table'),
                        zeroRecords: __('no_matching_records_found'),
                        processing: `<div class="spinner-border text-primary" role="status"><span class="visually-hidden">${__('loading')}...</span></div>`
                    }
                });

                // --- 3. Place the Buttons in the DOM ---
                // Moves the generated buttons container to the top-left of the table wrapper.
                table.buttons().container()
                    .appendTo('#employee_vac_wrapper .col-md-6:eq(0)');

            });

            // Initialize Allowed Employees Badge Card
            $(document).ready(function() {
                var employeeNames = '<?= htmlspecialchars($allowed_employee_names ?? '', ENT_QUOTES, 'UTF-8') ?>';
                var container = $('#allowed-employees-container');
                if (container.length && typeof renderAllowedEmployeesCard === 'function') {
                    var badgeHTML = renderAllowedEmployeesCard(employeeNames);
                    container.html(badgeHTML);
                }
            });
        </script>

        <!-- Company -> City -> Location -> Department -> Sub-department drilldown (AJAX, no URL/reload) -->
        <script>
            $(document).ready(function() {
                var drilldownRootTilesHtml = $('#drilldownTilesContainer').html();
                var drilldownRootBreadcrumbHtml = $('#drilldownBreadcrumb').html();
                var drilldownState = { level: 'companies' };
                var drilldownSearchTimer = null;
                var DRILLDOWN_STORAGE_KEY = 'orgDrilldownState';

                function saveDrilldownState() {
                    try {
                        sessionStorage.setItem(DRILLDOWN_STORAGE_KEY, JSON.stringify(drilldownState));
                    } catch (e) {}
                }

                function drilldownShowTiles(tilesHtml, breadcrumbHtml) {
                    $('#drilldownTilesContainer').html(tilesHtml).removeClass('d-none');
                    $('#drilldownEmployeesToolbar').addClass('d-none');
                    $('#drilldownEmployeesCards').addClass('d-none').empty();
                    $('#drilldownEmployeesPaginationRow').addClass('d-none');
                    $('#drilldownBreadcrumb').html(breadcrumbHtml);
                }

                function drilldownShowEmployees(breadcrumbHtml) {
                    $('#drilldownTilesContainer').addClass('d-none');
                    $('#drilldownEmployeesToolbar').removeClass('d-none');
                    $('#drilldownEmployeesCards').removeClass('d-none');
                    $('#drilldownEmployeesPaginationRow').removeClass('d-none');
                    $('#drilldownBreadcrumb').html(breadcrumbHtml);
                }

                function drilldownFetchTiles(level, params) {
                    $.ajax({
                        url: './includes/ajaxFile/get_org_drilldown.php',
                        type: 'POST',
                        dataType: 'json',
                        data: $.extend({ level: level }, params)
                    }).done(function(response) {
                        if (!response || response.status !== 200) {
                            return;
                        }
                        drilldownState = $.extend({ level: level }, params);
                        saveDrilldownState();
                        drilldownShowTiles(response.tiles_html, response.breadcrumb_html);
                    });
                }

                function renderDrilldownPagination(currentPage, totalPages) {
                    var $pagination = $('#drilldownEmployeesPagination');
                    $pagination.empty();
                    if (totalPages <= 1) {
                        return;
                    }
                    var $nav = $('<nav aria-label="Employees pagination"></nav>');
                    var $ul = $('<ul class="pagination mb-0"></ul>');

                    var $prev = $('<li class="page-item"></li>').toggleClass('disabled', currentPage <= 1);
                    $prev.append($('<a class="page-link" href="javascript:void(0);"></a>').text(__('previous', 'Previous')).on('click', function(e) {
                        e.preventDefault();
                        if (currentPage > 1) drilldownFetchEmployees(currentPage - 1);
                    }));
                    $ul.append($prev);

                    for (var i = 1; i <= totalPages; i++) {
                        var $li = $('<li class="page-item"></li>').toggleClass('active', i === currentPage);
                        $li.append($('<a class="page-link" href="javascript:void(0);"></a>').text(i).on('click', { page: i }, function(e) {
                            e.preventDefault();
                            var page = e.data.page;
                            if (page !== currentPage) drilldownFetchEmployees(page);
                        }));
                        $ul.append($li);
                    }

                    var $next = $('<li class="page-item"></li>').toggleClass('disabled', currentPage >= totalPages);
                    $next.append($('<a class="page-link" href="javascript:void(0);"></a>').text(__('next', 'Next')).on('click', function(e) {
                        e.preventDefault();
                        if (currentPage < totalPages) drilldownFetchEmployees(currentPage + 1);
                    }));
                    $ul.append($next);

                    $nav.append($ul);
                    $pagination.append($nav);
                }

                function drilldownFetchEmployees(page, params) {
                    if (params) {
                        drilldownState = $.extend({}, drilldownState, params, { level: 'employees' });
                        if (params.search !== undefined) $('#drilldownSearchFilter').val(params.search);
                        if (params.status !== undefined) $('#drilldownStatusFilter').val(params.status);
                    }
                    var $cards = $('#drilldownEmployeesCards');
                    var search = $('#drilldownSearchFilter').val();
                    var status = $('#drilldownStatusFilter').val();

                    var lockedHeight = $cards.outerHeight();
                    if (lockedHeight) {
                        $cards.css('min-height', lockedHeight + 'px');
                    }
                    $cards.css({ opacity: 0.45, 'pointer-events': 'none' });

                    var requestData = $.extend({}, drilldownState, { level: 'employees', search: search, status: status, limit: 12, page: page });
                    drilldownState = $.extend({}, requestData);
                    saveDrilldownState();

                    $.ajax({
                        url: './includes/ajaxFile/get_org_drilldown.php',
                        type: 'POST',
                        dataType: 'json',
                        data: requestData
                    }).done(function(response) {
                        if (!response || response.status !== 200) {
                            return;
                        }
                        drilldownShowEmployees(response.breadcrumb_html);
                        $cards.html(response.cards_html);
                        renderDrilldownPagination(response.current_page, response.total_pages);
                    }).always(function() {
                        $cards.css({ opacity: 1, 'pointer-events': 'auto', 'min-height': '' });
                    });
                }

                // Tile clicks - drill one level deeper (or straight to the employee list
                // once a department with no sub-departments, or a sub-department, is reached).
                $(document).on('click', '.drilldown-tile', function() {
                    var $t = $(this);
                    var nextLevel = $t.data('next-level');
                    var params = {
                        company: $t.data('company') || 0,
                        company_name: $t.data('company-name') || '',
                        city: $t.data('city') || 0,
                        city_name: $t.data('city-name') || '',
                        location: $t.data('location') || 0,
                        location_name: $t.data('location-name') || '',
                        dept: $t.data('dept') || 0,
                        dept_name: $t.data('dept-name') || '',
                        subdept: $t.data('subdept') !== undefined ? $t.data('subdept') : '',
                        subdept_name: $t.data('subdept-name') || ''
                    };
                    if (nextLevel === 'employees') {
                        drilldownFetchEmployees(1, params);
                    } else {
                        drilldownFetchTiles(nextLevel, params);
                    }
                });

                // Breadcrumb clicks - jump back up to any earlier level.
                $(document).on('click', '.drilldown-crumb[data-level]', function() {
                    var $c = $(this);
                    var level = $c.data('level');
                    if (level === 'companies') {
                        drilldownState = { level: 'companies' };
                        saveDrilldownState();
                        drilldownShowTiles(drilldownRootTilesHtml, drilldownRootBreadcrumbHtml);
                        return;
                    }
                    var params = {
                        company: $c.data('company') || 0,
                        company_name: $c.data('company-name') || '',
                        city: $c.data('city') || 0,
                        city_name: $c.data('city-name') || '',
                        location: $c.data('location') || 0,
                        location_name: $c.data('location-name') || '',
                        dept: $c.data('dept') || 0,
                        dept_name: $c.data('dept-name') || ''
                    };
                    drilldownFetchTiles(level, params);
                });

                $('#drilldownSearchFilter').on('input', function() {
                    clearTimeout(drilldownSearchTimer);
                    drilldownSearchTimer = setTimeout(function() {
                        drilldownFetchEmployees(1);
                    }, 350);
                });
                $('#drilldownStatusFilter').on('change', function() {
                    drilldownFetchEmployees(1);
                });

                // Restore drill position only on an actual page refresh - arriving fresh
                // from the Dashboard (or any other link) always starts at Companies.
                try {
                    var navEntries = performance.getEntriesByType('navigation');
                    var isReload = navEntries.length ? navEntries[0].type === 'reload' : (performance.navigation && performance.navigation.type === 1);

                    if (!isReload) {
                        sessionStorage.removeItem(DRILLDOWN_STORAGE_KEY);
                    } else {
                        var savedState = JSON.parse(sessionStorage.getItem(DRILLDOWN_STORAGE_KEY) || 'null');
                        if (savedState && savedState.level && savedState.level !== 'companies') {
                            if (savedState.level === 'employees') {
                                drilldownFetchEmployees(savedState.page || 1, savedState);
                            } else {
                                drilldownFetchTiles(savedState.level, savedState);
                            }
                        }
                    }
                } catch (e) {}
            });
        </script>

    </body>

    </html>
<?php } ?>