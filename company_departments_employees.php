<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/session_check.php';

$query = mysqli_query($conDB, "SELECT * FROM `admin_login` WHERE `id_iqama`='" . $username . "'");
if (mysqli_num_rows($query) == 1) {
    include("./includes/avatar_select.php");
}

$can_see_all_employees = function_exists('canSeeAllEmployeesByRole')
    ? canSeeAllEmployeesByRole(true)
    : ($is_system_admin || $user_type == 'administrator' || $user_dept == 5 || $isHR || $isDeptHr || $user_dept == 1);

$has_explicit_scope_restrictions = function_exists('hasExplicitEmployeeScopeRestrictions')
    ? hasExplicitEmployeeScopeRestrictions(true)
    : (!empty($allowed_companies_array) || !empty($allowed_departments_array) || !empty($allowed_employees_array));

$currentUserCompany = (int)($user_company ?? ($_SESSION['auth_user']['comp_no'] ?? 0));

$fallback_dept_filter_emp = (!$can_see_all_employees && !$has_explicit_scope_restrictions && !empty($user_dept))
    ? " AND `emp`.`dept`='" . mysqli_real_escape_string($conDB, $user_dept) . "'"
    : "";
$fallback_company_filter_emp = (!$can_see_all_employees && !$has_explicit_scope_restrictions && $currentUserCompany > 0)
    ? " AND `emp`.`comp_no`='" . mysqli_real_escape_string($conDB, $currentUserCompany) . "'"
    : "";

$company_filter_emp = getCompanyFilterSQL('emp.comp_no', true);
$department_filter_emp = getDepartmentFilterSQL('emp.dept', true);
$employee_filter_emp = getEmployeeFilterSQL('emp.emp_id', true);

$companies = [];
$companies_sql = "SELECT DISTINCT
    `emp`.`comp_no`,
    `companies`.`comp_name`,
    `companies`.`comp_name_ar`
    FROM `employees` `emp`
    LEFT JOIN `companies` ON `companies`.`comp_id` = `emp`.`comp_no`
    WHERE `emp`.`status` = 1" . $company_filter_emp . $department_filter_emp . $employee_filter_emp . $fallback_dept_filter_emp . $fallback_company_filter_emp . "
    ORDER BY `companies`.`comp_name` ASC";
$companies_query = mysqli_query($conDB, $companies_sql);
if ($companies_query) {
    while ($row = mysqli_fetch_assoc($companies_query)) {
        $companies[] = $row;
    }
}

$selected_company = isset($_GET['company']) ? (int)$_GET['company'] : 0;
$selected_dept = isset($_GET['dept']) ? (int)$_GET['dept'] : 0;

$available_company_ids = array_map(static function ($companyRow) {
    return (int)$companyRow['comp_no'];
}, $companies);

if ($selected_company > 0 && !in_array($selected_company, $available_company_ids, true)) {
    $selected_company = 0;
    $selected_dept = 0;
}

$departments = [];
$total_company_employees = 0;
if ($selected_company > 0) {
    $company_clause = " AND `emp`.`comp_no`=" . (int)$selected_company;

    $total_company_sql = "SELECT COUNT(*) AS `total`
        FROM `employees` `emp`
        WHERE `emp`.`status` = 1" . $company_filter_emp . $department_filter_emp . $employee_filter_emp . $fallback_dept_filter_emp . $fallback_company_filter_emp . $company_clause;
    $total_company_res = mysqli_query($conDB, $total_company_sql);
    $total_company_row = $total_company_res ? mysqli_fetch_assoc($total_company_res) : null;
    $total_company_employees = $total_company_row && isset($total_company_row['total']) ? (int)$total_company_row['total'] : 0;

    $departments_sql = "SELECT
        `emp`.`dept`,
        COUNT(`emp`.`dept`) AS `empcountgrp`,
        `department`.`dep_nme`,
        `department`.`dep_nme_ar`
        FROM `employees` `emp`
        LEFT JOIN `department` ON `department`.`id` = `emp`.`dept`
        WHERE `emp`.`status` = 1" . $company_filter_emp . $department_filter_emp . $employee_filter_emp . $fallback_dept_filter_emp . $fallback_company_filter_emp . $company_clause . "
        GROUP BY `emp`.`dept`, `department`.`dep_nme`, `department`.`dep_nme_ar`
        ORDER BY `department`.`dep_nme` ASC";
    $departments_query = mysqli_query($conDB, $departments_sql);
    if ($departments_query) {
        while ($row = mysqli_fetch_assoc($departments_query)) {
            $departments[] = $row;
        }
    }

    $available_dept_ids = array_map(static function ($deptRow) {
        return (int)$deptRow['dept'];
    }, $departments);

    if ($selected_dept > 0 && !in_array($selected_dept, $available_dept_ids, true)) {
        $selected_dept = 0;
    }
}

?>
<!doctype html>
<html lang="<?= $current_lang ?? 'en' ?>" <?= ($is_rtl ?? false) ? 'dir="rtl"' : '' ?>>
<head>
    <meta charset="utf-8" />
    <title><?= $site_title ?> - Company Departments</title>
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <link rel="shortcut icon" href="<?= get_setting($conDB, 'favicon') ?>">

    <link href="assets/css/bootstrap.min.css" rel="stylesheet" type="text/css" />
    <link href="assets/css/icons.css" rel="stylesheet" type="text/css" />
    <link href="assets/css/metismenu.min.css" rel="stylesheet" type="text/css" />
    <link href="assets/css/style.css" rel="stylesheet" type="text/css" />
    <link href="assets/css/style_dark.css" rel="stylesheet" type="text/css" />

    <link href="./plugins/datatables/dataTables.bootstrap4.min.css" rel="stylesheet" type="text/css" />
    <link href="./plugins/datatables/responsive.bootstrap4.min.css" rel="stylesheet" type="text/css" />

    <script src="assets/js/modernizr.min.js"></script>

    <style>
        .stats-card {
            border-radius: 16px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.08);
            padding: 24px 18px 18px 18px;
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
        .stats-card[data-color="primary"] { --card-gradient: linear-gradient(90deg,#556ee6 0%,#50a5f1 100%); }
        .stats-card[data-color="success"] { --card-gradient: linear-gradient(90deg,#34c38f 0%,#43e97b 100%); }
        .stats-card[data-color="info"] { --card-gradient: linear-gradient(90deg,#50a5f1 0%,#2196f3 100%); }
        .stats-card[data-color="danger"] { --card-gradient: linear-gradient(90deg,#f46a6a 0%,#ff6a88 100%); }
        .stats-card[data-color="warning"] { --card-gradient: linear-gradient(90deg,#f1b44c 0%,#ffde7d 100%); }
        .stats-card[data-color="dark"] { --card-gradient: linear-gradient(90deg,#343a40 0%,#232526 100%); }
        .stats-card:hover {
            box-shadow: 0 8px 32px rgba(0,0,0,0.18);
            transform: translateY(-4px) scale(1.02);
        }
        .stats-card.active {
            outline: 2px solid rgba(255,255,255,0.7);
            outline-offset: -2px;
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
            margin-right: 18px;
            box-shadow: 0 2px 16px rgba(0,0,0,0.12);
            position: relative;
            transition: transform 0.2s;
            flex-direction: column;
        }
        .stats-card-count-circle {
            background: #fff;
            color: #2196f3;
            border-radius: 50%;
            width: 34px;
            height: 34px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 16px;
            font-weight: 700;
            margin-bottom: 4px;
        }
        .stats-card-icon i {
            font-size: 24px;
            color: #2196f3;
        }
        .stats-card-icon[data-color="primary"] i { color: #556ee6; }
        .stats-card-icon[data-color="success"] i { color: #34c38f; }
        .stats-card-icon[data-color="info"] i { color: #50a5f1; }
        .stats-card-icon[data-color="danger"] i { color: #f46a6a; }
        .stats-card-icon[data-color="warning"] i { color: #f1b44c; }
        .stats-card-icon[data-color="dark"] i { color: #343a40; }
        .stats-card-content { flex: 1; }
        .stats-card-label {
            font-size: 16px;
            font-weight: 700;
            color: #fff;
            margin-bottom: 8px;
            line-height: 1.3;
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
                    <span><img src="<?= get_setting($conDB, 'logo') ?>" alt="" height="22"></span>
                    <i><img src="<?= get_setting($conDB, 'white_logo') ?>" alt="" height="28"></i>
                </a>
            </div>
            <?php include('./includes/main_menu.php'); ?>
            <div class="clearfix"></div>
        </div>
    </div>

    <div class="content-page">
        <?php include('./includes/topbar.php'); ?>
        <div class="content">
            <div class="container-fluid">
                <div class="card-box">
                    <h4 class="header-title m-t-0 m-b-20"><?= __('companies') ?> → <?= __('departments') ?> → <?= __('employees_list') ?></h4>

                    <form method="get" action="company_departments_employees.php" class="mb-3">
                        <div class="row align-items-end">
                            <div class="col-md-6">
                                <label class="font-weight-bold" for="company"><?= __('companies') ?></label>
                                <select name="company" id="company" class="form-control" onchange="this.form.submit()">
                                    <option value=""><?= __('select_option') ?: 'Select Company' ?></option>
                                    <?php foreach ($companies as $company): ?>
                                        <option value="<?= (int)$company['comp_no'] ?>" <?= $selected_company === (int)$company['comp_no'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars(($is_rtl ?? false) ? ($company['comp_name_ar'] ?: $company['comp_name']) : ($company['comp_name'] ?: $company['comp_name_ar'])) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6 mt-3 mt-md-0 text-md-right">
                                <?php if ($selected_company > 0): ?>
                                    <a href="company_departments_employees.php" class="btn btn-light"><i class="mdi mdi-refresh"></i> <?= __('clear') ?: 'Clear' ?></a>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php if ($selected_dept > 0): ?>
                            <input type="hidden" name="dept" value="<?= (int)$selected_dept ?>">
                        <?php endif; ?>
                    </form>

                    <?php if ($selected_company > 0): ?>
                        <div class="row mb-3">
                            <?php
                            $colorArr = ["primary", "success", "warning", "danger", "info", "dark"];
                            $colorCount = count($colorArr);
                            ?>
                            <?php foreach ($departments as $index => $department): ?>
                                <?php
                                $deptId = (int)$department['dept'];
                                $deptCount = (int)$department['empcountgrp'];
                                $deptLabel = ($is_rtl ?? false)
                                    ? ($department['dep_nme_ar'] ?: $department['dep_nme'])
                                    : ($department['dep_nme'] ?: $department['dep_nme_ar']);
                                $percentage = $total_company_employees > 0 ? round(($deptCount / $total_company_employees) * 100, 1) : 0;
                                $cardColor = $colorArr[$index % $colorCount];
                                $dept_link_params = ['comp' => $selected_company, 'dept' => $deptId, 'page' => 1];
                                ?>
                                <div class="col-sm-6 col-lg-4 mb-3">
                                    <a href="filter_employee_by_comp.php?<?= htmlspecialchars(http_build_query($dept_link_params)) ?>" class="text-white" style="text-decoration:none;">
                                        <div class="stats-card <?= $selected_dept === $deptId ? 'active' : '' ?>" data-color="<?= $cardColor ?>">
                                            <div class="stats-card-icon" data-color="<?= $cardColor ?>">
                                                <div class="stats-card-count-circle"><?= $deptCount ?></div>
                                                <i class="fa fa-building"></i>
                                            </div>
                                            <div class="stats-card-content">
                                                <div class="stats-card-label"><?= htmlspecialchars($deptLabel ?: ('Department #' . $deptId)) ?></div>
                                                <div style="width:100%;margin-top:10px;">
                                                    <div style="background:rgba(255,255,255,0.25);border-radius:8px;height:10px;overflow:hidden;">
                                                        <div style="height:10px;border-radius:8px;width:<?= $percentage ?>%;background:rgba(255,255,255,0.9);box-shadow:0 0 8px rgba(255,255,255,0.6);"></div>
                                                    </div>
                                                    <div style="font-size:12px;color:#fff;opacity:0.9;margin-top:4px;">
                                                        <?= $percentage ?>% <?= __('of_total_employees') ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </a>
                                </div>
                            <?php endforeach; ?>

                            <?php if (empty($departments)): ?>
                                <div class="col-12">
                                    <div class="alert alert-warning mb-0"><?= __('no_data_available_in_table') ?></div>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <footer class="footer"><?= $site_footer ?></footer>
    </div>
</div>

<script src="assets/js/jquery.min.js"></script>
<script src="assets/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/metisMenu.min.js"></script>
<script src="assets/js/waves.js"></script>
<script src="assets/js/jquery.slimscroll.js"></script>

<script src="assets/js/notifications.js"></script>
<script src="assets/js/jquery.core.js"></script>
<script src="assets/js/jquery.app.js?t=<?= time() ?>"></script>
</body>
</html>
