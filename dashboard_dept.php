    <?php
	require_once __DIR__ . '/includes/db.php';

	require_once __DIR__ . '/includes/session_check.php';
	$query = mysqli_query($conDB, "SELECT * FROM `admin_login` WHERE `id_iqama`='".$username."'");
	if(mysqli_num_rows($query) == 1){
	include("./includes/avatar_select.php");
		
	$sql_count_active = mysqli_query($conDB, "SELECT COUNT(*) `id` FROM `employees` WHERE `status`=1 AND `fly`=0 AND `dept`='".$user_dept."' ");
	$status_cont_active = mysqli_fetch_array($sql_count_active)[0];
		
	$sql_count_ter = mysqli_query($conDB, "SELECT COUNT(*) `id` FROM `employees` WHERE `status`=0 AND `dept`='".$user_dept."'");
	$status_cont_ter = mysqli_fetch_array($sql_count_ter)[0];
		
	$sql_count_fly = mysqli_query($conDB, "SELECT COUNT(*) `id` FROM `employees` WHERE `fly`=1 AND `dept`='".$user_dept."'");
	$status_cont_fly = mysqli_fetch_array($sql_count_fly)[0];
		
	$sql_count_tot = mysqli_query($conDB, "SELECT COUNT(*) `id` FROM `employees` WHERE `dept`='".$user_dept."'");
	$status_cont_tot = mysqli_fetch_array($sql_count_tot)[0];
?>
<!doctype html>
<html lang="en">
    <head>
        <meta charset="utf-8" />
        <title><?php echo $site_title ?> - Dashboard</title>
        <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
<!--        <meta content="A fully featured admin theme which can be used to build CRM, CMS, etc." name="description" />-->
        <meta content="Anees Afzal" name="author" />
        <meta http-equiv="X-UA-Compatible" content="IE=edge" />

        <!-- App favicon -->
        <link rel="shortcut icon" href="assets/images/favicon.ico">

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
                                <img src="assets/images/logo.png" alt="" height="22">
                            </span>
                            <i>
                                <img src="assets/images/logo_sm.png" alt="" height="28">
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
                                    <h3 class="m-b-10"><?php echo $status_cont_active ?></h3>
                                    <p class="text-uppercase m-b-5 font-13 font-600">ON Job Employees</p>
                                </div>
                            </div>
                            <div class="col-sm-6 col-xl-6" <?php if($status_cont_fly > 0){ ?> onclick="window.location.href='filter_employee.php?page=1&active=active&fly=yes'" style="cursor: pointer;" <?php } ?> >
                                <div class="card-box bg-primary widget-flat border-primary text-white">
                                    <i class="mdi mdi-airplane-takeoff"></i>
                                    <h3 class="m-b-10"><?php echo $status_cont_fly ?></h3>
                                    <p class="text-uppercase m-b-5 font-13 font-600">ON Vacations Employees</p>
                                </div>
                            </div>
                            <div class="col-sm-6 col-xl-6" onclick="window.location.href='filter_employee.php?page=1&active=no&fly=no'" style="cursor: pointer;">
                                <div class="card-box bg-danger widget-flat border-danger text-white">
                                    <i class="fi-delete"></i>
                                    <h3 class="m-b-10"><?php echo $status_cont_ter ?></h3>
                                    <p class="text-uppercase m-b-5 font-13 font-600">Terminated Employees</p>
                                </div>
                            </div>
							<div class="col-sm-6 col-xl-6" onclick="window.location.href='reg_employee.php'" style="cursor: pointer;">
                                <div class="card-box widget-flat border-success bg-success text-white">
                                    <i class="mdi mdi-account-multiple"></i>
                                    <h3 class="m-b-10"><?php echo $status_cont_tot ?></h3>
                                    <p class="text-uppercase m-b-5 font-13 font-600">Total Employees</p>
                                </div>
                            </div>						
                        </div>
                        
                    </div> <!-- container -->

                </div> <!-- content -->

                <footer class="footer">
                    <?php echo $site_footer ?>
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