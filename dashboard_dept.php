    <?php
	require_once __DIR__ . '/includes/db.php';

	require_once __DIR__ . '/includes/session_check.php';
	$query = mysqli_query($conDB, "SELECT * FROM `admin_login` WHERE `id_iqama`='".$username."'");
	if(mysqli_num_rows($query) == 1){
	include("./includes/avatar_select.php");
		
    $company_filter = getCompanyFilterSQL('comp_no', true);
    $department_filter = getDepartmentFilterSQL('dept', true);
    $employee_filter = getEmployeeFilterSQL('emp_id', true);
	
    $sql_count_active = mysqli_query($conDB, "SELECT COUNT(*) `id` FROM `employees` WHERE `status`=1 AND `fly`=0".$company_filter.$department_filter.$employee_filter);
	$status_cont_active = mysqli_fetch_array($sql_count_active)[0];
		
    $sql_count_ter = mysqli_query($conDB, "SELECT COUNT(*) `id` FROM `employees` WHERE `status`=0".$company_filter.$department_filter.$employee_filter);
	$status_cont_ter = mysqli_fetch_array($sql_count_ter)[0];
		
    $sql_count_fly = mysqli_query($conDB, "SELECT COUNT(*) `id` FROM `employees` WHERE `fly`=1".$company_filter.$department_filter.$employee_filter);
	$status_cont_fly = mysqli_fetch_array($sql_count_fly)[0];
		
    $sql_count_tot = mysqli_query($conDB, "SELECT COUNT(*) `id` FROM `employees` WHERE 1=1".$company_filter.$department_filter.$employee_filter);
	$status_cont_tot = mysqli_fetch_array($sql_count_tot)[0];
	
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

    $allowed_employee_names = 'All Employees';
    if (!empty($allowed_employees_array)) {
        $employee_ids = implode(',', array_map('intval', $allowed_employees_array));
        $emp_query = mysqli_query($conDB, "SELECT GROUP_CONCAT(DISTINCT CONCAT(`emp_id`, ' - ', `name`) SEPARATOR ', ') AS `names` FROM `employees` WHERE `emp_id` IN ($employee_ids)");
        if ($emp_query && $emp_row = mysqli_fetch_assoc($emp_query)) {
            $allowed_employee_names = $emp_row['names'] ?: 'All Employees';
        }
    }
?>
<!doctype html>
<html lang="en">
    <head>
        <meta charset="utf-8" />
        <title><?=$site_title ?> - Dashboard</title>
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
        <script src="assets/js/modernizr.min.js"></script>
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
                <div class="content">
                    <div class="container-fluid">

                        <div class="row text-center">
                            <div class="col-sm-6 col-xl-6" onclick="window.location.href='filter_employee.php?page=1&active=active&fly=no'" style="cursor: pointer;">
                                <div class="card-box widget-flat border-custom bg-custom text-white">
                                    <i class="mdi mdi-account-convert"></i>
                                    <h3 class="m-b-10"><?=$status_cont_active ?></h3>
                                    <p class="text-uppercase m-b-5 font-13 font-600">ON Job Employees</p>
                                </div>
                            </div>
                            <div class="col-sm-6 col-xl-6" <?php if($status_cont_fly > 0){ ?> onclick="window.location.href='filter_employee.php?page=1&active=active&fly=yes'" style="cursor: pointer;" <?php } ?> >
                                <div class="card-box bg-primary widget-flat border-primary text-white">
                                    <i class="mdi mdi-airplane-takeoff"></i>
                                    <h3 class="m-b-10"><?=$status_cont_fly ?></h3>
                                    <p class="text-uppercase m-b-5 font-13 font-600">ON Vacations Employees</p>
                                </div>
                            </div>
                            <div class="col-sm-6 col-xl-6" onclick="window.location.href='filter_employee.php?page=1&active=no&fly=no'" style="cursor: pointer;">
                                <div class="card-box bg-danger widget-flat border-danger text-white">
                                    <i class="fi-delete"></i>
                                    <h3 class="m-b-10"><?=$status_cont_ter ?></h3>
                                    <p class="text-uppercase m-b-5 font-13 font-600">Terminated Employees</p>
                                </div>
                            </div>
							<div class="col-sm-6 col-xl-6" onclick="window.location.href='reg_employee.php'" style="cursor: pointer;">
                                <div class="card-box widget-flat border-success bg-success text-white">
                                    <i class="mdi mdi-account-multiple"></i>
                                    <h3 class="m-b-10"><?=$status_cont_tot ?></h3>
                                    <p class="text-uppercase m-b-5 font-13 font-600">Total Employees</p>
                                </div>
                            </div>						
                        </div>
dash
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
                                    <div class="card-box" style="border: 1px solid #e5e7eb;">
                                        <h5 class="m-t-0"><?= __('allowed_employees') ?: 'Allowed Employees' ?></h5>
                                        <div class="small text-muted"><?= htmlspecialchars($allowed_employee_names) ?></div>
                                    </div>
                                </div>
                            </div>
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
        <script src="assets/js/jquery.app.js"></script>

    </body>
</html>
<?php } ?>