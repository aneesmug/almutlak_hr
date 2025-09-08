<?php
	require_once __DIR__ . '/includes/db.php';

	require_once __DIR__ . '/includes/session_check.php';
	$query = mysqli_query($conDB, "SELECT * FROM `admin_login` WHERE `id_iqama`='".$username."'");
		if(mysqli_num_rows($query) == 1){
		include("./includes/avatar_select.php");
	}

if(isset($_POST['submit'])){
	$name_emp = $_POST['name'];
	$emp_id = $_POST['emp_id'];
	$iqama = $_POST['iqama'];
	$mobile = $_POST['mobile'];
	$salary = $_POST['salary'];
	$vacation_days = $_POST['vacation_days'];
	$joining_date = $_POST['joining_date'];
	$department = $_POST['department'];
	$sectin_nme = $_POST['sectin_nme'];
	$emptype = $_POST['emptype'];
	$bank_name = $_POST['bank_name'];
	$iban = $_POST['iban'];
	$country = $_POST['country'];
	$dob = $_POST['dob'];
	$vac_period = $_POST['vac_period'];
	$sex = $_POST['sex'];
	$mar_status = $_POST['mar_status'];
	$date_reg = date("c");
	
	/*****************/
	
	if(!empty($_FILES['avatar']['name'])){
		$nameava = $_FILES['avatar']['name'];
		$tmp_name = $_FILES['avatar']['tmp_name'];
		$image = "./assets/emp_pics/".$iqama.".".$nameava." ";
	} else {
		$image = "./assets/emp_pics/defult.png";
	}
	
//	$image_move = "./assets/emp_pics/".$iqama.".".$nameava." ";
	move_uploaded_file($tmp_name, $image);
	
//	header( "Refresh:5; url= profile", true, 303);
	
	/*****************/
	

	if($name_emp){
		$query = "INSERT INTO `employees` (`name`, `emp_id`, `iqama`, `mobile`, `salary`, `vacation_days`, `joining_date`, `date_reg`, `status`, `avatar`,`fly`,`dept`,`emptype`,`sectin_nme`,`country`,`bank_name`,`iban`) VALUES ('".$name_emp."', '".$emp_id."', '".$iqama."', '".$mobile."', '".$salary."', '".$vacation_days."', '".$joining_date."', '".$date_reg."', 'active', '$image','no','".$department."','".$emptype."','".$sectin_nme."','".$country."','".$bank_name."','".$iban."','".$dob."','".$vac_period."','".$sex."','".$mar_status."')";
		mysqli_query($conDB, $query);
		/************log************/
		mysqli_query($conDB, "INSERT INTO `activity_log` (`user_editor`,`page`,`pg_id`,`reg_date`) VALUES ('".$_COOKIE['user']."','".$pgname."','".$_POST['name']."','".date("c")."')") or die ();
		/************log************/
		$msg = "<div class=\"alert alert-success bg-success text-white border-0\" role=\"alert\">Add Seccssfully!</div>
		";		
		 header( "refresh:2 ; url=new_employee.php" );
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
                        <a href="index.html" class="logo">
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
                                    <h4 class="m-t-0 header-title">Register New Employee</h4>
                                    <form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post" enctype="multipart/form-data">
										<?php echo $msg ?>
                                        <div class="form-row">
											<div class="form-group col-md-3">
												<label for="name">Employee Name<span class="text-danger">*</span></label>
												<input type="text" name="name" parsley-trigger="change" required
													   placeholder="Enter employee name" class="form-control" id="name" autofocus>
												</div>
                                            <div class="form-group col-md-3">
												<label for="emp_id">Employee ID.<span class="text-danger">*</span></label>
												<input type="text" name="emp_id" parsley-trigger="change" required
                                                   placeholder="Enter employee id" class="form-control" id="emp_id">
                                        	</div>
                                        	<div class="form-group col-md-3">
												<label for="iqama">Iqama<span class="text-danger">*</span></label>
												<input type="text" name="iqama" parsley-trigger="change" 
                                                   placeholder="Enter employee iqama" data-mask="9999999999" class="form-control" id="iqama" required>
                                        	</div>
                                        	<div class="form-group col-md-3">
											<label for="country">Nationality<span class="text-danger">*</span></label>
											<select class="form-control" name="country" required>
												<option value="">Select</option>
											<?php
												$query_country = mysqli_query($conDB, "SELECT * FROM `country` ORDER BY `name` REGEXP '^[^A-Za-z]' ASC, name");
												while($rec_con = mysqli_fetch_assoc($query_country)){
													$countrynme = $rec_con["name"];
											?>
												<option value="<?php echo $countrynme ?>"><?php echo $countrynme ?></option>
											<?php } ?>
											</select>
											</div>
                                            <div class="form-group col-md-3">
											<label for="department" class="col-form-label">Department<span class="text-danger">*</span></label>
											<select class="form-control" name="department" required>
												<option value="">Select</option>
											<?php
												$query_dep_nme = mysqli_query($conDB, "SELECT * FROM `department` ORDER BY `dep_nme` REGEXP '^[^A-Za-z]' ASC, dep_nme");
												while($rec = mysqli_fetch_assoc($query_dep_nme)){
													$dep_nme = $rec["dep_nme"];
											?>
												<option value="<?php echo $dep_nme ?>"><?php echo $dep_nme ?></option>
											<?php } ?>
											</select>
											</div>
											<div class="form-group col-md-2">
                                                <label for="section_name" class="col-form-label">Section<span class="text-danger">*</span></label>
											<select class="form-control" name="section_name" required>
												<option value="">Select</option>
											<?php
												$query_sectin_nme = mysqli_query($conDB, "SELECT * FROM `section` ORDER BY `section_name` REGEXP '^[^A-Za-z]' ASC, section_name");
												while($rec = mysqli_fetch_assoc($query_sectin_nme)){
													$sectin_nme = $rec["section_name"];
											?>
												<option value="<?php echo $sectin_nme ?>"><?php echo $sectin_nme ?></option>
											<?php } ?>
											</select>
                                            </div>
											<div class="form-group col-md-2">
                                                <label for="emptype" class="col-form-label">Employee Type<span class="text-danger">*</span></label>
												<select class="form-control" name="emptype" required>
													<option value="">Select</option>
													<option value="Manager">Manager</option>
													<option value="Supporter">Supporter</option>
												</select>
                                            </div>
                                            <div class="form-group col-md-2">
                                                <label for="mobile" class="col-form-label">Mobile No.<span class="text-danger">*</span></label>
                                                <input type="text" name="mobile" parsley-trigger="change" placeholder="Enter employee mobile" data-mask="0599999999" class="form-control" id="mobile" required>
                                            </div>
											<div class="form-group col-md-3">
                                                <label for="joining_date" class="col-form-label">Joining Date<span class="text-danger">*</span></label>
                                                <input type="text" name="joining_date" parsley-trigger="change" required placeholder="dd/mm/yyyy" class="form-control" id="joining_date">
                                            </div>
											<div class="form-group col-md-3">
                                                <label for="dob" class="col-form-label">Date of Birth</label>
                                                <input type="text" name="dob" parsley-trigger="change" required placeholder="dd/mm/yyyy" class="form-control" id="dob">
                                            </div>
											<div class="form-group col-md-3">
                                                <p class="font-14 mt-3 mb-2">Gender <span class="text-danger">*</span></p>
												<div class="radio radio-info form-check-inline">
                                                    <input type="radio" id="inlineRadio3" value="male" name="sex" required>
                                                    <label for="inlineRadio3"><i class="mdi mdi-human-male"></i> Male</label>
                                                </div>
                                                <div class="radio radio-info form-check-inline">
                                                    <input type="radio" id="inlineRadio1" value="female" name="sex">
                                                    <label for="inlineRadio1"><i class="mdi mdi-human-female"></i> Female </label>
                                                </div>
                                            </div>
											<div class="form-group col-md-3">
                                                <p class="font-14 mt-3 mb-2">Marital Status</p>
												<div class="radio radio-info form-check-inline">
                                                    <input type="radio" id="married" value="married" name="mar_status">
                                                    <label for="married"><i class="mdi mdi-ring"></i> Married</label>
                                                </div>
                                                <div class="radio radio-info form-check-inline">
                                                    <input type="radio" id="single" value="single" name="mar_status">
                                                    <label for="single"><i class="mdi mdi-account-convert"></i> Single </label>
                                                </div>
                                            </div>
											<div class="form-group col-md-3">
												<label for="vac_period" class="col-form-label">Contract Period<span class="text-danger">*</span></label>
												<select class="form-control" name="vac_period" required>
													<option value="">Select</option>
													<option value="1 Year">1 Year</option>
													<option value="2 Years">2 Years</option>
												</select>
                                            </div>
											<div class="form-group col-md-3">
												<label for="avatar" class="col-form-label">Add Employee Picture</label>
												<input type="file" class="filestyle" name="avatar" data-btnClass="btn-primary">
                                            </div>
											<div class="form-group col-md-2">
                                                <label for="salary" class="col-form-label">Salary</label>
                                                <input type="text" name="salary" placeholder="Employee salary" class="form-control autonumber" data-v-max="25000" data-v-min="0" id="salary">
                                            </div>
                                            <div class="form-group col-md-2">
                                                <label for="vacation_days" class="col-form-label">Vacation Days</label>
                                                <input type="text" name="vacation_days" class="form-control autonumber" data-v-max="180" data-v-min="0" placeholder="Max 180 days" id="vacation_days">
                                            </div>
                                            <div class="form-group col-md-2">
                                               	 <label for="bank_name" class="col-form-label">Bank Name</label>
													<select class="form-control" name="bank_name" id="bank_name" required>
													<?php if(empty($bank_name_get)){ ?>
														<option value="">Select</option>
													<?php } else { ?>
														<option value="<?php echo $bank_name_get ?>"><?php echo $bank_name_get ?></option>
													<?php } ?>
													<?php
														$query_bank = mysqli_query($conDB, "SELECT * FROM `bank_list` ORDER BY `name` REGEXP '^[^A-Za-z]' ASC, name");
														while($rec_con = mysqli_fetch_assoc($query_bank)){
															$banknme = $rec_con["name"];
													?>
														<option value="<?php echo $banknme ?>"><?php echo $banknme ?></option>
													<?php } ?>
													</select>
                                            </div>
                                            <div class="form-group col-md-3">
                                                <label for="iban" class="col-form-label">IBAN</label>
                                                <input type="text" name="iban" class="form-control"  id="iban" data-mask="SA99 9999 9999 9999 9999 9999" value="<?php echo $iban_get ?>">
                                               
                                            </div>
                                        </div>
										<div class="btn-group" role="group" aria-label="Edit Button">
										<a href="dashboard.php" class="btn btn-dark"><i class="fa fa-angle-double-left"></i> Back</a>
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
		<script src="assets/pages/jquery.form-hijri-pickers.init.js"></script>
		
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