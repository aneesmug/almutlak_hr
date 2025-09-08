<?php
	require_once __DIR__ . '/includes/db.php';

	require_once __DIR__ . '/includes/session_check.php';
	$query = mysqli_query($conDB, "SELECT * FROM `admin_login` WHERE `id_iqama`='".$username."'");
		if(mysqli_num_rows($query) == 1){
		include("./includes/avatar_select.php");
	}

$getquery = mysqli_query($conDB, "SELECT * FROM `employees` WHERE `id`='".$_GET['id']."' ");

	if(mysqli_num_rows($getquery) == 0){
		while($rec = mysqli_fetch_assoc($getquery)){
			$id_get = $rec["id"];
			$name_get = $rec["name"];
			$emp_id_get = $rec["emp_id"];
			$iqama_get = $rec["iqama"];
			$mobile_get = $rec["mobile"];
			$salary_get = $rec["salary"];
			$vacation_days_get = $rec["vacation_days"];
			$joining_date_get = $rec["joining_date"];
			$date_reg_get = $rec["date_reg"];
			$emp_avatar_get = $rec["avatar"];
			$emp_status_get = $rec["status"];
			$emp_ter_date_get = $rec["ter_date"];
			$note_get = $rec["note"];
			$fly_get = $rec["fly"];
			$dept_get = $rec["dept"];
			
			$salary_get = str_replace(',', '', $salary_get);
	}	
	$sql_count_fly = mysqli_query($conDB, "SELECT COUNT(*) `emp_id` FROM `emp_vacation` WHERE `emp_id`='".$emp_id_get."' && `note`='Fly' ");
    $cont_fly = mysqli_fetch_array($sql_count_fly)[0];

	$sql_count_encashed = mysqli_query($conDB, "SELECT COUNT(*) `emp_id` FROM `emp_vacation` WHERE `emp_id`='".$emp_id_get."' && `note`='Encashed' ");
	$cont_encashed = mysqli_fetch_array($sql_count_encashed)[0];
		
	if($emp_status_get == "no" && $note_get == "expired"){
		$note_get = "Expired";
	} elseif($emp_status_get == "no" && $note_get == "terminat"){
		$note_get = "Terminated";
	}

} else {
		//when the id not equals id show database
		header("Location: ./reg_employee.php");
	}


if(isset($_POST['submit'])){
	$name_emp = $_POST['name'];
	$username_po = $_POST['username'];
	$user_type_po = $_POST['user_type'];
	$mobile = $_POST['mobile'];
	$dept_po = $_POST['dept'];
	$email_po = $_POST['email'];
	$password_po = sha1(md5($_POST['password']));

	$date_reg = date("c");

	if($name_emp){
		$query = "INSERT INTO `admin_login` (`firstname`, `username`, `user_type`, `mobile`, `dept`, `email`, `password`, `avatar`,`date_reg`,`emp_id`) VALUES ('".$name_emp."', '".$username_po."', 'dept_user', '".$mobile."', '".$dept_po."', '".$email_po."', '".$password_po."', '".$emp_avatar_get."', '".$date_reg."', '".$emp_id_get."')";
		mysqli_query($conDB, $query);
		/************log************/
		mysqli_query($conDB, "INSERT INTO `activity_log` (`user_editor`,`page`,`pg_id`,`reg_date`) VALUES ('".$_COOKIE['user']."','".$pgname."','".$_POST['name']."','".date("c")."')") or die ();
		/************log************/
		$msg = "<div class=\"alert alert-success bg-success text-white border-0\" role=\"alert\">Add Seccssfully!</div>
		";		
		 header( "refresh:2 ; url=./view_employee.php?id=$_GET[id]");
	} else {
		$msg = "<div class=\"alert alert-danger bg-danger text-white border-0\" role=\"alert\">Please fill out the form!</div>";
	}

	
}

?>

<!doctype html>
<html lang="en">

    <head>
        <meta charset="utf-8" />
        <title><?php echo $site_title ?> - All Employees</title>
        <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
<!--        <meta content="A fully featured admin theme which can be used to build CRM, CMS, etc." name="description" />-->
        <meta content="Anees Afzal" name="author" />
        <meta http-equiv="X-UA-Compatible" content="IE=edge" />

        <!-- App favicon -->
        <link rel="shortcut icon" href="assets/images/favicon.ico">

        <!-- Modal -->
        <link href="./plugins/custombox/css/custombox.min.css" rel="stylesheet">

<!-- Plugins css -->
<link href="./plugins/bootstrap-timepicker/hijri/ummalqura.calendars.picker.css" rel="stylesheet">


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


                        <div class="row">
                            <div class="col-md-12">
                                <div class="card-box">
                                    <h4 class="m-t-0 header-title">Register New User</h4>
                                    <form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post" enctype="multipart/form-data">
										<?php echo $msg ?>
                                        <div class="form-row">
											<div class="form-group col-md-4">
												<label for="name">ميلادي<span class="text-danger">*</span></label>
												<input type="text" name="name" id="pickCalGer" readonly class="form-control"  >
											</div>
											<div class="form-group col-md-4">
												<label for="dept" >هجري<span class="text-danger">*</span></label>
												<input type="text" name="dept" id='pickCalHj' class="form-control" id="dept" value="<?php echo $dept_get ?>" readonly>
											</div>
                                            <div class="form-group col-md-4">
                                                <label for="mobile" >Mobile No.<span class="text-danger">*</span></label>
                                                <input type="text" name="mobile" parsley-trigger="change" 
                                                   value="<?php echo $mobile_get ?>" readonly class="form-control" id="mobile">
                                            </div>
                                            <div class="form-group col-md-4">
												<label for="username">Username<span class="text-danger">*</span></label>
												<input type="text" name="username" parsley-trigger="change" required
                                                   placeholder="Enter username" class="form-control" id="username">
                                        	</div>
											<div class="form-group col-md-4">
												<label for="Password">New Password<span class="text-danger">*</span></label>
												<input type="text" name="password" parsley-trigger="change" required
                                                   placeholder="Enter password" class="form-control" id="password">
                                        	</div>
                                            <div class="form-group col-md-4">
                                                <label for="email">Email<span class="text-danger">*</span></label>
                                                <input type="text" name="email" parsley-trigger="change" required class="form-control" id="email">
                                            </div>
                                        </div>
										<div class="btn-group" role="group" aria-label="Edit Button">
										<a href="view_employee.php?id=<?php echo $_GET['id']; ?>" class="btn btn-dark"><i class="fa fa-angle-double-left"></i> Back</a>
                                        <button type="submit" name="submit" class="btn btn-primary"><i class="mdi mdi-account-plus"></i> Register</button>
										</div>
                                    </form>
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

		
<script type="text/javascript" src="./plugins/bootstrap-timepicker/hijri_/jquery.calendars.js"></script>
<script type="text/javascript" src="./plugins/bootstrap-timepicker/hijri_/jquery.plugin.js"></script> 
<script type="text/javascript" src="./plugins/bootstrap-timepicker/hijri_/jquery.calendars.plus.js"></script>
<script type="text/javascript" src="./plugins/bootstrap-timepicker/hijri_/jquery.calendars.picker.js"></script>
<script type="text/javascript" src="./plugins/bootstrap-timepicker/hijri_/jquery.calendars.picker-ar.js"></script>
<script type="text/javascript" src="./plugins/bootstrap-timepicker/hijri_/jquery.calendars.ummalqura.js"></script>
<script type="text/javascript" src="./plugins/bootstrap-timepicker/hijri_/jquery.calendars.ummalqura-ar.js"></script>
<script type="text/javascript" src="./plugins/bootstrap-timepicker/hijri_/calendar-convert.js"></script>

        <!-- Modal-Effect -->
		<script type="text/javascript" src="./plugins/parsleyjs/parsley.min.js"></script>
		<script src="./plugins/bootstrap-inputmask/bootstrap-inputmask.min.js" type="text/javascript"></script>
        <script src="./plugins/autoNumeric/autoNumeric.js" type="text/javascript"></script>


		<script src="./plugins/moment/moment.js"></script>
        <script src="./plugins/bootstrap-timepicker/bootstrap-timepicker.js"></script>
        <script src="./plugins/bootstrap-colorpicker/js/bootstrap-colorpicker.min.js"></script>
        <script src="./plugins/clockpicker/js/bootstrap-clockpicker.min.js"></script>
        <script src="./plugins/bootstrap-daterangepicker/daterangepicker.js"></script>
        <script src="./plugins/bootstrap-datepicker/js/bootstrap-datepicker.min.js"></script>
		
        <script src="./plugins/bootstrap-filestyle/js/bootstrap-filestyle.min.js" type="text/javascript"></script>

        <!-- App js -->
<!--		<script src="assets/pages/jquery.form-pickers.init.js"></script>-->
<script type="text/javascript">

	var calGer = $.calendars.instance();
    var calHj = $.calendars.instance('ummalqura', 'ar');
					
	$(function() {
	
		$('#pickCalGer').calendarsPicker($.extend({
			calendar: calGer,
			onSelect: function (date) {
				convertDtFromGerToHijri(date, 'pickCalHj');
			},
			showTrigger: '#calImg',
			dateFormat: 'dd/mm/yyyy',
		}));

		$('#pickCalHj').calendarsPicker($.extend({
			calendar: calHj,
			onSelect: function (date) {
				convertDtFromHijriToGer(date, 'pickCalGer');
			},
			showTrigger: '#calImg',
			dateFormat: 'dd/mm/yyyy',
		},
			$.calendarsPicker.regionalOptions['ar'])
		);		
		
	});
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