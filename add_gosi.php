<?php
	require_once __DIR__ . '/includes/db.php';

	require_once __DIR__ . '/includes/session_check.php';
	$query = mysqli_query($conDB, "SELECT * FROM `admin_login` WHERE `id_iqama`='".$username."'");
		if(mysqli_num_rows($query) == 1){
		include("./includes/avatar_select.php");
	}
	require("./includes/emp_query.php");
	if(mysqli_num_rows($get_emp_data) !== 0){
		$allRecords = mysqli_fetch_all($get_emp_data, MYSQLI_ASSOC);
		foreach ($allRecords as $rec) {
			$emprow = $rec;
		}
		if($emprow["status"] == "no" && $emprow["note"] == "expired"){
			$note_get = "Expired";
		} elseif($emprow["status"] == "no" && $emprow["note"] == "terminat"){
			$note_get = "Terminated";
		}	
	} else {
			//when the id not equals id show database
		header("Location: ./reg_employee.php");
	}


if(isset($_POST['submit'])){
	$gosi_no_po = $_POST['gosi_no'];
	$amount_po = $_POST['amount'];
	$date_greg_po = $_POST['date_greg'];
	$date_hijri_po = $_POST['date_hijri'];

	if($gosi_no_po){
		$query = "INSERT INTO `emp_gosi` (`emp_id`, `gosi_no`, `amount`, `date_greg`, `date_hijri`,`created_at`) VALUES ('".$emprow['empid']."', '".$gosi_no_po."', '$amount_po', '".$date_greg_po."', '".$date_hijri_po."', '".$date_reg."')";
		mysqli_query($conDB, $query);
		/************log************/
		mysqli_query($conDB, "INSERT INTO `activity_log` (`user_editor`,`page`,`pg_id`,`reg_date`) VALUES ('".$_COOKIE['user']."','".$pgname."','".$_POST['name']."','".date("Y-m-d H:i:s")."')") or die ();
		/************log************/
		$msg = "<div class=\"alert alert-success bg-success text-white border-0\" role=\"alert\">Add Seccssfully!</div>
		";		
		 header( "refresh:2 ; url=./view_employee.php?emp_id={$emprow['empid']}");
	} else {
		$msg = "<div class=\"alert alert-danger bg-danger text-white border-0\" role=\"alert\">Please fill out the form!</div>";
	}

	
}

?>

<!doctype html>
<html lang="en">

    <head>
        <meta charset="utf-8" />
        <title><?=$site_title ?> - All Employees</title>
        <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
<!--        <meta content="A fully featured admin theme which can be used to build CRM, CMS, etc." name="description" />-->
        <meta content="Anees Afzal" name="author" />
        <meta http-equiv="X-UA-Compatible" content="IE=edge" />

        <!-- App favicon -->
        <link rel="shortcut icon" href="assets/images/favicon.ico">

        <!-- Modal -->
        <link href="./plugins/custombox/css/custombox.min.css" rel="stylesheet">

		<!-- Plugins css -->
		<link href="./plugins/bootstrap-timepicker/hijri/bootstrap-datetimepicker.css" rel="stylesheet">


		<!--<link href="./plugins/bootstrap-timepicker/bootstrap-timepicker.min.css" rel="stylesheet">-->
		<!--<link href="./plugins/bootstrap-colorpicker/css/bootstrap-colorpicker.min.css" rel="stylesheet">-->
		<link href="./plugins/bootstrap-datepicker/css/bootstrap-datepicker.min.css" rel="stylesheet">
		<!--<link href="./plugins/clockpicker/css/bootstrap-clockpicker.min.css" rel="stylesheet">-->
		<!--<link href="./plugins/bootstrap-daterangepicker/daterangepicker.css" rel="stylesheet">-->
		
		<link href="./plugins/bootstrap-timepicker/hijri_css/bootstrap-datetimepicker.css" rel="stylesheet">
        <link href="./plugins/bootstrap-timepicker/hijri_css/bootstrap-datetimepicker.min.css" rel="stylesheet">


        <!-- App css -->
        <link href="assets/css/bootstrap.min.css" rel="stylesheet" type="text/css" />
        <link href="assets/css/icons.css" rel="stylesheet" type="text/css" />
        <link href="assets/css/metismenu.min.css" rel="stylesheet" type="text/css" />
        <link href="assets/css/style.css" rel="stylesheet" type="text/css" />
		<link href="assets/css/style_dark.css" rel="stylesheet" type="text/css" />
        <script src="assets/js/modernizr.min.js"></script>
    </head>
    <body class="enlarged" data-keep-enlarged="true">
    <body>

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
                    
						<?php include("./includes/emp_top_info.php"); ?>

                        <div class="row">
                            <div class="col-md-12">
                                <div class="card-box">
                                    <h4 class="m-t-0 header-title">Register Gosi Information</h4>
                                    <form action="<?=$_SERVER['PHP_SELF']; ?>" method="post" enctype="multipart/form-data">
										<?=$msg ?>
                                        <div class="form-row">
											<div class="form-group col-md-6">
												<label for="gosi_no">GOSI Number: <span class="text-danger">*</span></label>
												<input type="text" name="gosi_no" placeholder="Enter Gosi Number" class="form-control" id="gosi_no" autocomplete="off" required>
                                        	</div>
											<div class="form-group col-md-6">
												<label for="amount">GOSI Amount<span class="text-danger">*</span></label>
												<input type="text" name="amount" placeholder="Enter GOSI Amount" class="form-control" id="amount" autocomplete="off" required>
                                        	</div>
											<div class="form-group col-md-6">
												<label for="iqama_exp_hijri" class="pull-right">تاريخ الهجري<span class="text-danger">*</span></label>
												<input type="text" name="date_hijri" id='iqama_exp_hijri' placeholder="dd/mm/yyyy" class="form-control" autocomplete="off" required>
											</div>
                                            <div class="form-group col-md-6">
                                                <label for="last_vac_date" >Gregorian Date </label>
                                                <input type="text" name="date_greg" id="last_vac_date" placeholder="dd/mm/yyyy" class="form-control" autocomplete="off">
                                            </div>
                                        </div>
										<div class="btn-group" role="group" aria-label="Edit Button">
											<a href="view_employee.php?emp_id=<?=$emprow['empid']; ?>" class="btn btn-dark"><i class="fa fa-angle-double-left"></i> Back</a>
                                        	<button type="submit" name="submit" class="btn btn-primary"><i class="mdi mdi-account-plus"></i> Register</button>
										</div>
                                    </form>
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

		<script src="./plugins/bootstrap-timepicker/hijri/momentjs.js"></script>
		<script src="./plugins/bootstrap-timepicker/hijri/moment-with-locales.js"></script>
		<script src="./plugins/bootstrap-timepicker/hijri/moment-hijri.js"></script>
		<script src="./plugins/bootstrap-timepicker/hijri/bootstrap-hijri-datetimepicker.js"></script>


		<script src="./plugins/bootstrap-timepicker/bootstrap-timepicker.js"></script>
		<script src="./plugins/bootstrap-colorpicker/js/bootstrap-colorpicker.min.js"></script>
		<script src="./plugins/bootstrap-datepicker/js/bootstrap-datepicker.min.js"></script>
		
		
		<script src="./plugins/bootstrap-timepicker/hijri/bootstrap-hijri-datetimepicker.js"></script>
        <script src="./plugins/bootstrap-timepicker/hijri/bootstrap-hijri-datetimepicker.min.js"></script>
        <script src="./plugins/bootstrap-timepicker/hijri/bootstrap-hijri-datetimepickermin.js"></script>


        <!-- Modal-Effect -->
		<script type="text/javascript" src="./plugins/parsleyjs/parsley.min.js"></script>
		<script src="./plugins/bootstrap-inputmask/bootstrap-inputmask.min.js" type="text/javascript"></script>
        <script src="./plugins/autoNumeric/autoNumeric.js" type="text/javascript"></script>
		
		<script src="assets/pages/jquery.form-pickers.init.js"></script>

	
        <!-- App js -->
<!--		<script src="assets/pages/jquery.form-pickers.init.js"></script>-->
<script type="text/javascript">

        $(function () {

            initHijrDatePicker();
			/********************/

    jQuery('#last_vac_date').datepicker({
		format: "dd/mm/yyyy",
        autoclose: true,
        todayHighlight: true,
//		endDate: '+0d',
//		ignoreReadonly: true,
    });
			

        });

        function initHijrDatePicker() {

            $("#hijri-date-input").datetimepicker({
                locale: "ar-sa",
                format: "iDD/iMM/iYYYY",
                dayViewHeaderFormat: "iMMMM iYYYY",
                allowInputToggle: true,
                showTodayButton: false,
                useCurrent: false,
                showClear: false,
                isRTL: false,
                keepOpen: false,
                hijri: true,
                debug: false
            });
			
        }


    </script>
        <script src="assets/js/jquery.core.js"></script>
        <script src="assets/js/jquery.app.js"></script>
		<script type="text/javascript">
//            $(document).ready(function() {
//                $('form').parsley();
//            });
//			jQuery(function($) {
//                $('.autonumber').autoNumeric('init');
//            });
//            jQuery.browser = {};
//            (function () {
//                jQuery.browser.msie = false;
//                jQuery.browser.version = 0;
//                if (navigator.userAgent.match(/MSIE ([0-9]+)\./)) {
//                    jQuery.browser.msie = true;
//                    jQuery.browser.version = RegExp.$1;
//                }
//            })();
        </script>

    </body>
</html>