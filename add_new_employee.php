<?php
	require_once __DIR__ . '/includes/db.php';

	require_once __DIR__ . '/includes/session_check.php';
	$query = mysqli_query($conDB, "SELECT * FROM `admin_login` WHERE `id_iqama`='".$username."'");
	if(mysqli_num_rows($query) == 1){
	include("./includes/avatar_select.php");
if($user_type == "dept_user"){
	// Add company filter for dept user counts
	$company_filter = getCompanyFilterSQL('comp_no', true);
	$department_filter = getDepartmentFilterSQL('dept', true);
    $employee_filter = getEmployeeFilterSQL('emp_id', true);
	$dept_safe = (int)$user_dept;
    $sql_count_active = mysqli_query($conDB, "SELECT COUNT(*) `id` FROM `employees` WHERE `status`=1 AND `fly`='no' AND `dept`='" . $dept_safe . "'" . $company_filter . $department_filter . $employee_filter);
	$status_cont_active = mysqli_fetch_array($sql_count_active)[0];
		
    $sql_count_ter = mysqli_query($conDB, "SELECT COUNT(*) `id` FROM `employees` WHERE `status`='no' AND `dept`='" . $dept_safe . "'" . $company_filter . $department_filter . $employee_filter);
	$status_cont_ter = mysqli_fetch_array($sql_count_ter)[0];
		
    $sql_count_fly = mysqli_query($conDB, "SELECT COUNT(*) `id` FROM `employees` WHERE `fly`='yes' AND `dept`='" . $dept_safe . "'" . $company_filter . $department_filter . $employee_filter);
	$status_cont_fly = mysqli_fetch_array($sql_count_fly)[0];
		
    $sql_count_tot = mysqli_query($conDB, "SELECT COUNT(*) `id` FROM `employees` WHERE `dept`='" . $dept_safe . "'" . $company_filter . $department_filter . $employee_filter);
	$status_cont_tot = mysqli_fetch_array($sql_count_tot)[0];
	
	$sql_count_man_power = mysqli_query($conDB, "SELECT COUNT(*) `id` FROM `employees` WHERE `emp_sup_type`='man_power'");
	$status_cont_man_power = mysqli_fetch_array($sql_count_man_power)[0];
}else{
        $employee_filter = getEmployeeFilterSQL('emp_id', true);
        $sql_count_active = mysqli_query($conDB, "SELECT COUNT(*) `id` FROM `employees` WHERE `status`=1 AND `fly`='no' " . $employee_filter);
	$status_cont_active = mysqli_fetch_array($sql_count_active)[0];
		
    $sql_count_ter = mysqli_query($conDB, "SELECT COUNT(*) `id` FROM `employees` WHERE `status`='no' " . $employee_filter);
	$status_cont_ter = mysqli_fetch_array($sql_count_ter)[0];
		
    $sql_count_fly = mysqli_query($conDB, "SELECT COUNT(*) `id` FROM `employees` WHERE `fly`='yes' " . $employee_filter);
	$status_cont_fly = mysqli_fetch_array($sql_count_fly)[0];
		
    $sql_count_tot = mysqli_query($conDB, "SELECT COUNT(*) `id` FROM `employees` WHERE 1=1 " . $employee_filter);
	$status_cont_tot = mysqli_fetch_array($sql_count_tot)[0];
}
?>
<!doctype html>
<html lang="<?= $current_lang ?? 'en' ?>" <?= ($is_rtl ?? false) ? 'dir="rtl"' : '' ?>>
    <head>
        <meta charset="utf-8" />
        <title><?=$site_title ?> - <?= __('add_new_employee', 'Add new employee') ?></title>
        <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
<!--        <meta content="A fully featured admin theme which can be used to build CRM, CMS, etc." name="description" />-->
        <meta content="Anees Afzal" name="author" />
        <meta http-equiv="X-UA-Compatible" content="IE=edge" />

        <!-- App favicon -->
        <link rel="shortcut icon" href="<?=get_setting($conDB, 'favicon')?>">

        <!-- App css -->
        <link href="assets/css/bootstrap.min.css" rel="stylesheet" type="text/css" />
        <!-- <link href="assets/css/icons.css" rel="stylesheet" type="text/css" /> -->
        <link href="assets/css/metismenu.min.css" rel="stylesheet" type="text/css" />
        <link href="assets/css/style.css" rel="stylesheet" type="text/css" />
		<link href="assets/css/style_dark.css" rel="stylesheet" type="text/css" />
        <link href="assets/css/smart_request.css?v=<?= @filemtime(__DIR__ . '/assets/css/smart_request.css') ?>" rel="stylesheet" type="text/css" />
        <style>
            .ne-choices { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 20px; }
            @media (max-width: 767px) { .ne-choices { grid-template-columns: 1fr; } }
            .ne-choice {
                --ne: #3b82f6; --ne-soft: rgba(59, 130, 246, .12);
                position: relative; display: flex; align-items: center; gap: 18px; padding: 26px 24px; overflow: hidden;
                border-radius: var(--sr-radius); background: var(--sr-surface); border: 1px solid var(--sr-border); box-shadow: var(--sr-shadow);
                color: var(--sr-text-2) !important; text-decoration: none !important; transition: transform .15s, border-color .15s, box-shadow .15s;
            }
            .ne-choice::before { content: ''; position: absolute; top: 0; bottom: 0; inset-inline-start: 0; width: 4px; background: var(--ne); }
            .ne-choice.is-violet { --ne: #8b5cf6; --ne-soft: rgba(139, 92, 246, .12); }
            .ne-choice:hover { transform: translateY(-3px); border-color: var(--ne); box-shadow: 0 10px 28px rgba(15, 23, 42, .10); }
            .ne-icon { flex: 0 0 auto; width: 64px; height: 64px; border-radius: 16px; display: inline-flex; align-items: center; justify-content: center; font-size: 26px; color: var(--ne); background: var(--ne-soft); }
            .ne-text { flex: 1 1 auto; min-width: 0; }
            .ne-text b { display: block; font-size: 17px; color: var(--sr-text); }
            .ne-text small { display: block; margin-top: 4px; font-size: 13px; color: var(--sr-muted); line-height: 1.5; }
            .ne-go { flex: 0 0 auto; color: var(--sr-muted); transition: transform .15s, color .15s; }
            .ne-choice:hover .ne-go { color: var(--ne); transform: translateX(4px); }
            [dir="rtl"] .ne-go { transform: scaleX(-1); }
            [dir="rtl"] .ne-choice:hover .ne-go { transform: scaleX(-1) translateX(4px); }
        </style>
        <script src="assets/js/modernizr.min.js"></script>
        <?php if ($is_rtl): ?>
            <link href="assets/css/style_rtl.css" rel="stylesheet" type="text/css" />
        <?php endif; ?>
		<script> window.lang = <?= json_encode($GLOBALS['translations'] ?? []) ?>;</script>
    </head>
    <body class="enlarged" data-keep-enlarged="true">

        <!-- Begin page -->
        <div id="wrapper">

            <!-- ========== Left Sidebar Start ========== -->
            <div class="left side-menu">

                <div class="slimscroll-menu" id="remove-scroll">

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
                <div class="content sr-page">
                    <div class="container-fluid">
                        <div class="sr-head">
                            <div>
                                <h1><?= __('add_new_employee', 'Add new employee') ?></h1>
                                <p><?= __('choose_employee_type', 'Choose the type of employee you want to register') ?></p>
                            </div>
                            <div class="sr-head-actions">
                                <span class="sr-chip"><i class="fa fa-users"></i> <?= __('total', 'Total') ?>: <?= (int)$status_cont_tot ?></span>
                                <span class="sr-chip"><i class="fa fa-user-check"></i> <?= __('active', 'Active') ?>: <?= (int)$status_cont_active ?></span>
                            </div>
                        </div>

                        <div class="ne-choices">
                            <a href="new_comp_employee.php" class="ne-choice is-blue">
                                <span class="ne-icon"><i class="fa fa-house-chimney-user"></i></span>
                                <span class="ne-text">
                                    <b><?= __('almutlak_co_employee') ?></b>
                                    <small><?= __('company_employee_desc', 'Direct company employee with full contract, payroll and vacation details') ?></small>
                                </span>
                                <i class="fa fa-arrow-right ne-go"></i>
                            </a>
                            <a href="new_mnpow_employee.php" class="ne-choice is-violet">
                                <span class="ne-icon"><i class="fa fa-users-rays"></i></span>
                                <span class="ne-text">
                                    <b><?= __('manpower_employee') ?></b>
                                    <small><?= __('manpower_employee_desc', 'Employee supplied by a manpower agency') ?></small>
                                </span>
                                <i class="fa fa-arrow-right ne-go"></i>
                            </a>
                        </div>
                    </div> <!-- container -->
                </div> <!-- content -->
                <footer class="footer">
                    <?=$site_footer ?>
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

        <!-- Flot chart -->
        <script src="./plugins/flot-chart/jquery.flot.min.js"></script>
        <script src="./plugins/flot-chart/jquery.flot.time.js"></script>
        <script src="./plugins/flot-chart/jquery.flot.tooltip.min.js"></script>
        <script src="./plugins/flot-chart/jquery.flot.resize.js"></script>
        <script src="./plugins/flot-chart/jquery.flot.pie.js"></script>
        <script src="./plugins/flot-chart/jquery.flot.crosshair.js"></script>
        <script src="./plugins/flot-chart/curvedLines.js"></script>
        <script src="./plugins/flot-chart/jquery.flot.axislabels.js"></script>

        <!-- KNOB JS -->
        <!--[if IE]>
        <script type="text/javascript" src="../plugins/jquery-knob/excanvas.js"></script>
        <![endif]-->
        <script src="./plugins/jquery-knob/jquery.knob.js"></script>

        <!-- Dashboard Init -->
        <script src="assets/pages/jquery.dashboard.init.js"></script>

        <!-- App js -->
        <script src="assets/js/jquery.core.js"></script>
        <script src="assets/js/jquery.app.js?t=<?= time() ?>"></script>

    </body>
</html>
<?php } ?>