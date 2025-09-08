<?php
	require_once __DIR__ . '/includes/db.php';

	require_once __DIR__ . '/includes/session_check.php';

	include("./includes/Hijri_GregorianConvert.php");
	$DateConv=new Hijri_GregorianConvert;
	$format="DD/MM/YYYY";

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
	$emp_sup_type = "man_power";
	$date_reg = date("c");
	
	$iqama_exp_g = $_POST['iqama_exp_g'];
	
	/*****************/
	
	if(!empty($_FILES['avatar']['name'])){
		$nameava = $_FILES['avatar']['name'];
		$tmp_name = $_FILES['avatar']['tmp_name'];
		$image = "./assets/emp_pics/".$iqama.".".$nameava." ";
	} else {
		if ($sex == "male") {
			$image = "./assets/emp_pics/defult.png";
		} else {
			$image = "./assets/emp_pics/defultFemale.jpg";
		}
	}
	
//	$image_move = "./assets/emp_pics/".$iqama.".".$nameava." ";
	move_uploaded_file($tmp_name, $image);
	
//	header( "Refresh:5; url= profile", true, 303);
	
	/*****************/
	

	if($name_emp){
$queryckh = mysqli_query($conDB, "SELECT * FROM `employees` WHERE `emp_id`='".$emp_id."' ");
if(mysqli_num_rows($queryckh) > 0 ) { //check if there is already an entry for that appointment
		$msg = "<div class=\"alert alert-danger bg-danger text-white border-0\" role=\"alert\">This employee no. (\"$emp_id\") registerd already!</div>";
	
	}else{
		$query = "INSERT INTO `employees` (`name`, `emp_id`, `iqama`, `mobile`, `salary`, `joining_date`, `date_reg`, `status`, `avatar`,`fly`,`dept`,`sectin_nme`,`country`,`dob`,`sex`,`emp_sup_type`,`iqama_exp_g`) VALUES ('".$name_emp."', '".$emp_id."', '".$iqama."', '".$mobile."', '".$salary."', '".$joining_date."', '".$date_reg."', 'active', '$image','no','".$department."','".$sectin_nme."','".$country."','".$dob."','".$sex."','".$emp_sup_type."','".$iqama_exp_g."')";
		mysqli_query($conDB, $query);
		/************log************/
		mysqli_query($conDB, "INSERT INTO `activity_log` (`user_editor`,`page`,`pg_id`,`reg_date`) VALUES ('".$_COOKIE['user']."','".$pgname."','".$_POST['name']."','".date("c")."')") or die ();
		/************log************/
		$msg = "<div class=\"alert alert-success bg-success text-white border-0\" role=\"alert\">Add Seccssfully!</div>
		";		
//		 header( "refresh:2 ; url=dashboard.php" );
	}
		} else {
			$msg = "<div class=\"alert alert-danger bg-danger text-white border-0\" role=\"alert\">Please fill out all required fields in this form!</div>";
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
								<div class="col-sm-12 col-xl-12">
									<div class="card-box widget-flat border-purple bg-purple text-white">
										<i class="mdi mdi-account-network"></i>
										<br><h3 class="m-b-10">New Man-Power Employee</h3><br>
									</div>
                            	</div>
                            <div class="col-md-12">
                                <div class="card-box">
                                    <h4 class="m-t-0 header-title">Register New Employee</h4>
                                    <form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post" enctype="multipart/form-data" id="registration">
										<?php echo $msg ?>
                                        <div class="form-row">
											<div class="form-group col-md-3">
												<label for="name">Employee Name<span class="text-danger">*</span></label>
												<input type="text" name="name" parsley-trigger="change" class="form-control" id="name" autofocus>
												</div>
                                            <div class="form-group col-md-2">
												<label for="emp_id">Employee ID.<span class="text-danger">*</span></label>
												<input type="text" name="emp_id" parsley-trigger="change" class="form-control" id="emp_id">
                                        	</div>
                                        	<div class="form-group col-md-2">
												<label for="iqama">Iqama<span class="text-danger">*</span></label>
												<input type="text" name="iqama" parsley-trigger="change" data-mask="9999999999" class="form-control" id="iqama">
                                        	</div>
                                        	<div class="form-group col-md-2">
												<label for="iqama_exp_hijri">Iqama Expire<span class="text-danger"> in Hijri *</span></label>
                                                <input type="text" name="iqama_exp" parsley-trigger="change" class="form-control" id="iqama_exp_hijri">
                                        	</div>
                                        	<div class="form-group col-md-3">
											<label for="country">Nationality</label>
											<select class="form-control" name="country" required />
												<option value="">Select</option>
											<?php
												$query_country = mysqli_query($conDB, "SELECT * FROM `countries` ORDER BY `name` REGEXP '^[^A-Za-z]' ASC, name");
												while($rec_con = mysqli_fetch_assoc($query_country)){
											?>
												<option value="<?= $rec_con["id"]; ?>"><?= $rec_con["name"]; ?></option>
											<?php } ?>
											</select>
											</div>
                                            <div class="form-group col-md-3">
											<label for="department" class="col-form-label">Department<span class="text-danger">*</span></label>
											<select class="form-control" name="department" id="department" required />
												<option value="">Select</option>
											<?php
												$query_dep_nme = mysqli_query($conDB, "SELECT * FROM `department` ORDER BY `dep_nme` REGEXP '^[^A-Za-z]' ASC, dep_nme");
												while($rec = mysqli_fetch_assoc($query_dep_nme)){
											?>
												<option value="<?= $rec["id"] ?>"><?=$rec["dep_nme"] ?></option>
											<?php } ?>
											</select>
											</div>
											<div class="form-group col-md-3">
                                                <label for="sectin_nme" class="col-form-label">Section<span class="text-danger">*</span></label>
												<select class="form-control" name="sectin_nme" id="sectin_nme" required /></select>
                                            </div>
                                            <div class="form-group col-md-3">
                                                <label for="mobile" class="col-form-label">Mobile No.</label>
                                                <input type="text" name="mobile" parsley-trigger="change"data-mask="0599999999" class="form-control" id="" >
                                            </div>
											<div class="form-group col-md-3">
                                                <label for="joining_date" class="col-form-label">Joining Date</label>
                                                <input type="text" name="joining_date" parsley-trigger="change" class="form-control" id="joining_date" autocomplete="off">
                                            </div>
											<div class="form-group col-md-3">
                                                <label for="salary" class="col-form-label">Salary<span class="text-danger">*</span></label>
                                                <input type="text" name="salary" class="form-control autonumber" data-v-max="25000" data-v-min="0" id="salary">
                                            </div>
											<div class="form-group col-md-3">
                                                <label for="dob" class="col-form-label">Date of Birth</label>
                                                <input type="text" name="dob" parsley-trigger="change" class="form-control" id="">
                                            </div>
											<div class="form-group col-md-3">
                                                <label class="font-14 mt-3 mb-2 radioalign">Gender <span class="text-danger">*</span></label>
												<div class="radio radio-info form-check-inline">
                                                    <input type="radio" id="inlineRadio3" value="male" name="sex" checked>
                                                    <b for="inlineRadio3"><i class="mdi mdi-human-male"></i> Male</b>
                                                </div>
                                                <div class="radio radio-info form-check-inline">
                                                    <input type="radio" id="inlineRadio1" value="female" name="sex">
                                                    <b for="inlineRadio1"><i class="mdi mdi-human-female"></i> Female </b>
                                                </div>
                                            </div>
											<div class="form-group col-md-3">
												<label for="avatar" class="col-form-label">Add Employee Picture</label>
												<input type="file" class="filestyle" name="avatar" data-btnClass="btn-primary">
                                            </div>
                                        </div>
										
										<input type="text" hidden="true" name="iqama_exp_g" id="iqama_exp_g" >
										
										<div class="btn-group" role="group" aria-label="Edit Button">
										<a href="./add_new_employee.php" class="btn btn-dark"><i class="fa fa-angle-double-left"></i> Back</a>
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
		
		<script src="./plugins/bootstrap-timepicker/hijri/bootstrap-hijri-datetimepicker.js"></script>
        <script src="./plugins/bootstrap-timepicker/hijri/bootstrap-hijri-datetimepicker.min.js"></script>
        <script src="./plugins/bootstrap-timepicker/hijri/bootstrap-hijri-datetimepickermin.js"></script>
		
        <script src="./plugins/bootstrap-filestyle/js/bootstrap-filestyle.min.js" type="text/javascript"></script>

        <!-- App js -->
		<script src="assets/pages/jquery.form-pickers.init.js"></script>
		<script src="assets/pages/jquery.form-hijri-pickers.init.js"></script>
		
		<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-validate/1.19.0/jquery.validate.js"></script>
		<script src="./assets/js/jquery.custom.validation.manpower.js"></script>
		
        <script src="assets/js/jquery.core.js"></script>
        <script src="assets/js/jquery.app.js"></script>
		<script type="text/javascript">
$(document).ready(function() {

    $("#department").bind("change", function() {
        const deptData = $("#department").val();
        $.ajax({
                url: './includes/DepartmentSelect.php',
                type: 'POST',
                data: {department:deptData},
                dataType: 'json',
                success: (res) => {
                    let options = '<option value="">Select Section</option>';
                    res.data.forEach(item => {
                        options += `<option value="${item.id}" >${item.section_name}</option>`;
                    });
                    $('#sectin_nme').html(options);
                },
                error: (xhr, status, error) => {
                    $('#sectin_nme').html('<option value="">Error loading sections</option>');
                    console.error('AJAX Error:', status, error);
                }
            });
    });

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