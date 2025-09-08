<?php
	require_once __DIR__ . '/includes/db.php';

	require_once __DIR__ . '/includes/session_check.php';
	$query = mysqli_query($conDB, "SELECT * FROM `admin_login` WHERE `id_iqama`='".$username."'");
		if(mysqli_num_rows($query) == 1){
		include("./includes/avatar_select.php");
	}

$getquery = mysqli_query($conDB, "SELECT * FROM `employees` WHERE `emp_id`='".$_GET['emp_id']."' ");

	if(mysqli_num_rows($getquery) !== 0){
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
	$id_iqama_po = $_POST['id_iqama'];
	$dept_po = $_POST['dept'];
	$email_po = $_POST['email'];
	$email_pass_po = $_POST['email_pass'];
	$password_po = sha1(md5($_POST['password']));
    
    // $emp_type_po = ($user_type_po == "dept_user") ? "Manager" : "Supporter" ;

	$date_reg = date("c");

    // debug($_POST);

	if($name_emp){
		$query = "INSERT INTO `admin_login` (`fullname`, `username`, `user_type`, `emp_type`, `mobile`, `dept`, `email`, `password`, `avatar`,`emp_id`,`email_pass`,`id_iqama`) VALUES ('".$name_emp."', '".$username_po."', '".$user_type_po."', '".$emp_type_po."', '".$mobile."', '".$dept_po."', '".$email_po."', '".$password_po."', '".$emp_avatar_get."', '".$emp_id_get."', '".$email_pass_po."', '".$id_iqama_po."')";
		mysqli_query($conDB, $query);
		/************log************/
		mysqli_query($conDB, "INSERT INTO `activity_log` (`user_editor`,`page`,`pg_id`,`reg_date`) VALUES ('".$_COOKIE['user']."','".$pgname."','".$_POST['name']."','".date("c")."')") or die ();
		/************log************/
		$msg = "<div class=\"alert alert-success bg-success text-white border-0\" role=\"alert\">Add Seccssfully!</div>
		";		
		 header( "refresh:2 ; url=./view_employee.php?emp_id=$_GET[emp_id]");
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
        <link href="./plugins/bootstrap-timepicker/bootstrap-timepicker.min.css" rel="stylesheet">
        <link href="./plugins/bootstrap-colorpicker/css/bootstrap-colorpicker.min.css" rel="stylesheet">
        <link href="./plugins/bootstrap-datepicker/css/bootstrap-datepicker.min.css" rel="stylesheet">
        <link href="./plugins/clockpicker/css/bootstrap-clockpicker.min.css" rel="stylesheet">
        <link href="./plugins/bootstrap-daterangepicker/daterangepicker.css" rel="stylesheet">

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
                                    <form action="<?=$_SERVER['PHP_SELF']; ?>" method="post" enctype="multipart/form-data">
										<?=$msg ?>
                                        <div class="form-row">
											<div class="form-group col-md-3">
												<label for="name">Employee Name<span class="text-danger">*</span></label>
												<input type="text" name="name" parsley-trigger="change" value="<?=$name_get ?>" readonly class="form-control"  >
											</div>
											<div class="form-group col-md-2">
												<label for="dept" >Department<span class="text-danger">*</span></label>
												<input type="text" name="dept" parsley-trigger="change" class="form-control" id="dept" value="<?=$dept_get ?>" readonly>
											</div>
											<div class="form-group col-md-2">
												<label for="id_iqama" >ID/Iqama<span class="text-danger">*</span></label>
												<input type="text" name="id_iqama" parsley-trigger="change" class="form-control" id="id_iqama" value="<?=$iqama_get ?>" readonly>
											</div>
                                            <div class="form-group col-md-2">
                                                <label for="mobile" >Mobile No.<span class="text-danger">*</span></label>
                                                <input type="text" name="mobile" parsley-trigger="change" 
                                                   value="<?=$mobile_get ?>" readonly class="form-control" id="mobile">
                                            </div>
                                            <div class="form-group col-md-3">
                                                <label for="user_type" >User Type<span class="text-danger">*</span></label>
                                                <select class="form-control" name="user_type" required>
                                                    <option value="">Select</option>
                                                    <option value="dept_user">Manager</option>
                                                    <option value="employee">Supporter</option>
                                                </select>
                                            </div>
                                            <div class="form-group col-md-3">
												<label for="username">Username<span class="text-danger">*</span></label>
												<input type="text" name="username" parsley-trigger="change" required
                                                   placeholder="Enter username" class="form-control" id="username">
                                        	</div>
											<div class="form-group col-md-3">
												<label for="Password">New Password<span class="text-danger">*</span></label>
												<input type="text" name="password" parsley-trigger="change" required
                                                   placeholder="Enter password" class="form-control" id="password">
                                        	</div>
                                            <div class="form-group col-md-3">
                                                <label for="email">Email<span class="text-danger">*</span></label>
                                                <input type="text" name="email" parsley-trigger="change" required class="form-control" id="email">
                                            </div>
                                            <div class="form-group col-md-3">
                                                <label for="email_pass">Email Password<span class="text-danger">*</span></label>
                                                <input type="text" name="email_pass" parsley-trigger="change" required class="form-control" id="email_pass">
                                            </div>
                                        </div>
										<div class="btn-group" role="group" aria-label="Edit Button">
										<a href="view_employee.php?id=<?=$_GET['id']; ?>" class="btn btn-dark"><i class="fa fa-angle-double-left"></i> Back</a>
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
		<script src="assets/pages/jquery.form-pickers.init.js"></script>
		
        <script src="assets/js/jquery.core.js"></script>
        <script src="assets/js/jquery.app.js"></script>
		<script type="text/javascript">
            $(document).ready(function() {
                $('form').parsley();
            });
			jQuery(function($) {
                $('.autonumber').autoNumeric('init');
            });
            jQuery.browser = {};
            (function () {
                jQuery.browser.msie = false;
                jQuery.browser.version = 0;
                if (navigator.userAgent.match(/MSIE ([0-9]+)\./)) {
                    jQuery.browser.msie = true;
                    jQuery.browser.version = RegExp.$1;
                }
            })();
        </script>

    </body>
</html>