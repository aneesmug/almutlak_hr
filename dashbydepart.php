<?php
require_once __DIR__ . '/includes/db.php';

require_once __DIR__ . '/includes/session_check.php';
$query = mysqli_query($conDB, "SELECT * FROM `admin_login` WHERE `id_iqama`='" . $username . "'");
if (mysqli_num_rows($query) == 1) {
    include("./includes/avatar_select.php");

$color = array("primary", "success", "info", "warning", "danger", "dark");

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
            /* Gradient backgrounds for each color */
            .stats-card[data-color="primary"] {
                --card-gradient: linear-gradient(90deg,#556ee6 0%,#50a5f1 100%);
            }
            .stats-card[data-color="success"] {
                --card-gradient: linear-gradient(90deg,#34c38f 0%,#43e97b 100%);
            }
            .stats-card[data-color="info"] {
                --card-gradient: linear-gradient(90deg,#50a5f1 0%,#2196f3 100%);
            }
            .stats-card[data-color="danger"] {
                --card-gradient: linear-gradient(90deg,#f46a6a 0%,#ff6a88 100%);
            }
            .stats-card[data-color="warning"] {
                --card-gradient: linear-gradient(90deg,#f1b44c 0%,#ffde7d 100%);
            }
            .stats-card[data-color="secondary"] {
                --card-gradient: linear-gradient(90deg,#6c757d 0%,#343a40 100%);
            }
            .stats-card[data-color="dark"] {
                --card-gradient: linear-gradient(90deg,#343a40 0%,#232526 100%);
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
            .stats-card-icon[data-color="primary"] i { color: #556ee6; }
            .stats-card-icon[data-color="success"] i { color: #34c38f; }
            .stats-card-icon[data-color="info"] i { color: #50a5f1; }
            .stats-card-icon[data-color="danger"] i { color: #f46a6a; }
            .stats-card-icon[data-color="warning"] i { color: #f1b44c; }
            .stats-card-icon[data-color="secondary"] i { color: #6c757d; }
            .stats-card-icon[data-color="dark"] i { color: #343a40; }
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
            /* Dynamic department color for icon */
            <?php foreach (["primary","success","warning","danger","info","dark"] as $clr) { ?>
                :root {
                    --dept-color-<?= $clr ?>: <?php
                        if ($clr == "primary") {
                            echo "#556ee6";
                        } elseif ($clr == "success") {
                            echo "#34c38f";
                        } elseif ($clr == "info") {
                            echo "#50a5f1";
                        } elseif ($clr == "warning") {
                            echo "#f1b44c";
                        } elseif ($clr == "danger") {
                            echo "#f46a6a";
                        } elseif ($clr == "secondary") {
                            echo "#6c757d";
                        } else {
                            echo "#343a40";
                        }
                    ?>;
                }
            <?php } ?>
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
                                        <a href="#bydepartment-b1" data-toggle="tab" aria-expanded="false" class="nav-link active show">
                                            <i class="fi-monitor mr-2"></i> <?= __('departments') ?>
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a href="#bycompany-b1" data-toggle="tab" aria-expanded="false" class="nav-link">
                                            <i class="fa fa-layer-group mr-2"></i> <?= __('companies') ?>
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a href="#bylist-b1" data-toggle="tab" aria-expanded="true" class="nav-link">
                                            <i class="fi-head mr-2"></i> <?= __('employees_list') ?>
                                        </a>
                                    </li>
                                </ul>
                                <div class="tab-content">
                                    <div class="tab-pane active show" id="bydepartment-b1">
                                        <!-- <div class="tab-pane" id="bydepartment-b1"> -->
                                        <?php  ?><div class="row text-center">

                                            <?php
                                            // ================================================================
                                            // DEPARTMENT-BASED ACCESS CONTROL FOR DEPARTMENT GROUPING
                                            // ================================================================
                                            // HR Department (dept 5) and System Admins can see all departments
                                            // All other users can only see their own department
                                            $can_see_all_departments = (
                                                $is_system_admin || 
                                                $user_type == 'administrator' ||
                                                $user_dept == 5 || // HR Department
                                                $isHR || 
                                                $isDeptHr ||
                                                $user_dept == 1 // Administration Department
                                            );
                                            
                                            if (!$can_see_all_departments) {
                                                // Department managers: show only their department
                                                $company_filter = getCompanyFilterSQL('employees.comp_no', true);
                                                $querygrp = mysqli_query($conDB, "SELECT 
                                                    count(`employees`.`dept`) AS `empcountgrp`,
                                                    `employees`.`dept`, 
                                                    `department`.`dep_nme`,
                                                    `department`.`dep_nme_ar`,
                                                    `dept_clr`.`color`
                                                    FROM `employees` 
                                                    LEFT JOIN `dept_clr` ON `dept_clr`.`dept_name` = `employees`.`dept`
                                                    LEFT JOIN `department` ON `department`.`id` = `dept_clr`.`dept_name`
                                                    WHERE `employees`.`status` = 1 
                                                    AND `employees`.`dept` = '" . mysqli_real_escape_string($conDB, $user_dept) . "'" . $company_filter . " 
                                                    GROUP BY `employees`.`dept`");
                                            } else {
                                                // HR and System Admins: show all departments
                                                $company_filter = getCompanyFilterSQL('employees.comp_no', true);
                                                $querygrp = mysqli_query($conDB, "SELECT 
                                                    count(`employees`.`dept`) AS `empcountgrp`,
                                                    `employees`.`dept`, 
                                                    `department`.`dep_nme`, 
                                                    `department`.`dep_nme_ar`,
                                                    `dept_clr`.`color`
                                                    FROM `employees` 
                                                    LEFT JOIN `dept_clr` ON `dept_clr`.`dept_name` = `employees`.`dept`
                                                    LEFT JOIN `department` ON `department`.`id` = `dept_clr`.`dept_name`
                                                    WHERE `employees`.`status` = 1" . $company_filter . "
                                                    GROUP BY `employees`.`dept`");
                                            }
                                            // $querygrp = mysqli_query($conDB, "SELECT count(`dept`) AS `empcountgrp`,`dept` FROM `employees` WHERE `emp_sup_type`='mocha' AND `status` = 1 GROUP BY `dept`");
                                            if ($querygrp) {
                                                $colorArr = ["primary","success","warning","danger","info","dark"];
                                                $colorCount = count($colorArr);
                                                $cardIndex = 0;
                                                // Total active employees (for percentage calculation)
                                                $totalEmpRes = mysqli_query($conDB, "SELECT COUNT(*) AS total FROM employees WHERE status=1");
                                                $totalEmpRow = mysqli_fetch_assoc($totalEmpRes);
                                                $totalEmployees = $totalEmpRow && isset($totalEmpRow['total']) ? (int)$totalEmpRow['total'] : 1;
                                                while ($rec = mysqli_fetch_array($querygrp)) {
                                                    $cardColor = $colorArr[$cardIndex % $colorCount];
                                                    $deptCount = (int)$rec["empcountgrp"];
                                                    $percentage = round(($deptCount / $totalEmployees) * 100, 1);
                                            ?>
                                                <div class="col-sm-4 col-xl-3" onclick="window.location.href='filter_employee_by_dept.php?page=1&status=1&status=active&dept=<?= $rec["dept"] ?>'" style="cursor: pointer;">
                                                    <div class="stats-card" data-color="<?= $cardColor ?>">
                                                        <div class="stats-card-icon" data-color="<?= $cardColor ?>">
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
                                                                    <div style="height:12px;border-radius:8px;width:<?= $percentage ?>%;background:rgba(255,255,255,0.9);box-shadow:0 0 8px rgba(255,255,255,0.6);transition:width 0.6s;"></div>
                                                                </div>
                                                                <div style="font-size:13px;color:#fff;opacity:0.85;margin-top:4px;">
                                                                    <?= $percentage ?>% <?=__('of_total_employees') ?>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            <?php 
                                                    $cardIndex++;
                                                } // end while
                                            } // end if ($querygrp)
                                            ?>

                                        </div>
                                    </div>
                                    <div class="tab-pane" id="bycompany-b1">
                                        <!-- <div class="tab-pane" id="bydepartment-b1"> -->
                                        <?php  ?><div class="row text-center">

                                            <?php
                                            // ================================================================
                                            // DEPARTMENT-BASED ACCESS CONTROL FOR COMPANY GROUPING
                                            // ================================================================
                                            // HR Department (dept 5) and System Admins can see all companies
                                            // All other users can only see companies in their department
                                            $can_see_all_companies = (
                                                $is_system_admin || 
                                                $user_type == 'administrator' ||
                                                $user_dept == 5 || // HR Department
                                                $isHR || 
                                                $isDeptHr ||
                                                $user_dept == 1 // Administration Department
                                            );
                                            
                                            if (!$can_see_all_companies) {
                                                // Department managers: show only companies in their department
                                                $company_filter = getCompanyFilterSQL('employees.comp_no', true);
                                                $querygrp = mysqli_query($conDB, "SELECT 
                                                    count(`employees`.`dept`) AS `empcountgrp`,
                                                    `employees`.`comp_no`, 
                                                    `companies`.`comp_name`, 
                                                    `companies`.`comp_name_ar`, 
                                                    `companies`.`comp_id`
                                                    FROM `employees` 
                                                    LEFT JOIN `companies` ON `companies`.`comp_id` = `employees`.`comp_no`
                                                    WHERE `employees`.`status` = 1
                                                    AND `employees`.`dept` = '" . mysqli_real_escape_string($conDB, $user_dept) . "'" . $company_filter . "
                                                    GROUP BY `employees`.`comp_no`");
                                            } else {
                                                // HR and System Admins: show all companies
                                                $company_filter = getCompanyFilterSQL('employees.comp_no', true);
                                                $querygrp = mysqli_query($conDB, "SELECT 
                                                    count(`employees`.`dept`) AS `empcountgrp`,
                                                    `employees`.`comp_no`, 
                                                    `companies`.`comp_name`, 
                                                    `companies`.`comp_name_ar`, 
                                                    `companies`.`comp_id`
                                                    FROM `employees` 
                                                    LEFT JOIN `companies` ON `companies`.`comp_id` = `employees`.`comp_no`
                                                    WHERE `employees`.`status` = 1" . $company_filter . "
                                                    GROUP BY `employees`.`comp_no`");
                                            }
                                            // $querygrp = mysqli_query($conDB, "SELECT count(`dept`) AS `empcountgrp`,`dept` FROM `employees` WHERE `emp_sup_type`='mocha' AND `status` = 1 GROUP BY `dept`");
                                            if ($querygrp) {
                                                $colorArr = ["primary","success","warning","danger","info","dark"];
                                                $colorCount = count($colorArr);
                                                $cardIndex = 0;
                                                // Total active employees (for percentage calculation)
                                                $company_filter = getCompanyFilterSQL('comp_no', true);
                                                $totalEmpRes = mysqli_query($conDB, "SELECT COUNT(*) AS total FROM employees WHERE status=1" . $company_filter);
                                                $totalEmpRow = mysqli_fetch_assoc($totalEmpRes);
                                                $totalEmployees = $totalEmpRow && isset($totalEmpRow['total']) ? (int)$totalEmpRow['total'] : 1;
                                                while ($rec = mysqli_fetch_array($querygrp)) {
                                                    $cardColor = $colorArr[$cardIndex % $colorCount];
                                                    $compCount = (int)$rec["empcountgrp"];
                                                    $percentage = round(($compCount / $totalEmployees) * 100, 1);
                                            ?>
                                                <div class="col-sm-4 col-xl-3" onclick="window.location.href='filter_employee_by_comp.php?page=1&status=1&comp=<?= $rec["comp_no"] ?>'" style="cursor: pointer;">
                                                    <div class="stats-card" data-color="<?= $cardColor ?>">
                                                        <div class="stats-card-icon" data-color="<?= $cardColor ?>">
                                                            <div class="stats-card-count-circle"><?= $compCount ?></div>
                                                            <span class="stats-card-tooltip">Company Info</span>
                                                            <i class="fa fa-industry"></i>
                                                        </div>
                                                        <div class="stats-card-content">
                                                            <div class="stats-card-label" style="color:#fff;opacity:0.95;"><?= ($is_rtl ?? false) ? $rec["comp_name_ar"] : $rec["comp_name"] ?></div>
                                                            <div class="stats-card-footer">
                                                                <span class="stats-card-percentage positive">
                                                                    <i class="mdi mdi-trending-up"></i>
                                                                </span>
                                                            </div>
                                                            <!-- Progress from company share of total employees -->
                                                            <div style="width:100%;margin-top:18px;">
                                                                <div style="background:rgba(255,255,255,0.25);border-radius:8px;height:12px;overflow:hidden;">
                                                                    <div style="height:12px;border-radius:8px;width:<?=$percentage ?>%;background:rgba(255,255,255,0.9);box-shadow:0 0 8px rgba(255,255,255,0.6);transition:width 0.6s;"></div>
                                                                </div>
                                                                <div style="font-size:13px;color:#fff;opacity:0.85;margin-top:4px;">
                                                                    <?= $percentage ?>% of total employees
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            <?php 
                                                    $cardIndex++;
                                                } // end while
                                            } // end if ($querygrp)
                                            ?>

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
                                                // HR Department (dept 5) and System Admins can see all employees
                                                // All other users can only see employees from their own department
                                                $can_see_all_employees = (
                                                    $is_system_admin || 
                                                    $user_type == 'administrator' ||
                                                    $user_dept == 5 || // HR Department
                                                    $isHR || 
                                                    $isDeptHr
                                                );

                                                if (!$can_see_all_employees) {
                                                    // Department users: show only their department employees
                                                    $company_filter = getCompanyFilterSQL('emp.comp_no', true);
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
                                                            WHERE `emp`.`status`=1 AND `emp`.`fly`=0 
                                                            AND `emp`.`dept`='" . mysqli_real_escape_string($conDB, $user_dept) . "'" . $company_filter . " ";
                                                } else {
                                                    // HR and System Admins: show all employees
                                                    $company_filter = getCompanyFilterSQL('emp.comp_no', true);
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
                                                            WHERE `emp`.`status`=1 AND `emp`.`fly`=0" . $company_filter . " ";
                                                }
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

        <!-- App js -->
        <script src="assets/js/jquery.core.js"></script>
        <script src="assets/js/jquery.app.js"></script>


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
        </script>

    </body>

    </html>
<?php } ?>