<?php
	require_once __DIR__ . '/includes/db.php';

	require_once __DIR__ . '/includes/session_check.php';
	$query = mysqli_query($conDB, "SELECT * FROM `admin_login` WHERE `id_iqama`='".$username."'");
		if(mysqli_num_rows($query) == 1){
		include("./includes/avatar_select.php");
	}
    
if(isset($_POST['submit'])){
	$owner_name = mysqli_real_escape_string($conDB, $_POST['owner_name']);
    $owner_number = mysqli_real_escape_string($conDB, $_POST['owner_number']);
    $owner_email = mysqli_real_escape_string($conDB, $_POST['owner_email']);
    $contract_no = mysqli_real_escape_string($conDB, $_POST['contract_no']);
    $start_cont_date = mysqli_real_escape_string($conDB, $_POST['start_cont_date']);
    $end_cont_date = mysqli_real_escape_string($conDB, $_POST['end_cont_date']);
    $rent = str_replace(',', '', $_POST['rent']);
    $service = str_replace(',', '', $_POST['service']);
    $elect_prc = str_replace(',', '', $_POST['elect_prc']);
    $water_prc = str_replace(',', '', $_POST['water_prc']);
    $incuranse_prc = str_replace(',', '', $_POST['incuranse_prc']);
    $others = str_replace(',', '', $_POST['others']);
	$reg_date = date("c");
	
	
	if($owner_name){
        
		$query = "INSERT INTO `location_contract` (`location_id`,`owner_name`, `owner_number`, `owner_email`, `contract_no`, `start_cont_date`, `end_cont_date`, `rent`, `service`, `elect_prc`, `water_prc`, `incuranse_prc`, `others`, `reg_date`) VALUES ('".$_GET['location_id']."','".$owner_name."', '".$owner_number."', '".$owner_email."','".$contract_no."','".$start_cont_date."','".$end_cont_date."','".$rent."','".$service."','".$elect_prc."','".$water_prc."','".$incuranse_prc."','".$others."', '".$reg_date."')";
		mysqli_query($conDB, $query);

        mysqli_query($conDB, "UPDATE `section` SET `location_owner`='".$owner_name."' WHERE `id`='".$_GET['location_id']."' ");

		/************log************/
		//mysqli_query($conDB, "INSERT INTO `activity_log` (`user_editor`,`page`,`pg_id`,`reg_date`) VALUES ('".$_COOKIE['user']."','".$pgname."','".$_POST['maker_name']."','".date("c")."')") or die (mysqli_error());
		/************log************/
		$msg = "<div class=\"alert alert-success bg-success text-white border-0\" role=\"alert\">Contract Added Seccssfully!</div>
		";		
		 header( "refresh:2 ; url=view_location.php?id=$_GET[location_id]" );
	} else {
		$msg = "<div class=\"alert alert-danger bg-danger text-white border-0\" role=\"alert\">Please fill out the form!</div>";
	}

}

?>

<!doctype html>
<html lang="en">

    <head>
        <meta charset="utf-8" />
        <title><?php echo $site_title ?> - Add Location</title>
        <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
<!--        <meta content="A fully featured admin theme which can be used to build CRM, CMS, etc." name="description" />-->
        <meta content="Anees Afzal" name="author" />
        <meta http-equiv="X-UA-Compatible" content="IE=edge" />

        <!-- App favicon -->
        <link rel="shortcut icon" href="assets/images/favicon.ico">

        <!-- Modal -->
        <link href="./plugins/custombox/css/custombox.min.css" rel="stylesheet">

        <link href="./plugins/bootstrap-tagsinput/css/bootstrap-tagsinput.css" rel="stylesheet" />
        <link href="./plugins/bootstrap-select/css/bootstrap-select.min.css" rel="stylesheet" />
        <link href="./plugins/select2/css/select2.min.css" rel="stylesheet" type="text/css" />
        <link rel="stylesheet" href="./plugins/switchery/switchery.min.css" />
        
        <link rel="stylesheet" href="./plugins/croppie/croppie.css">
        
        
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
						<h4 class="m-t-0 header-title">Register New Location</h4>
						<form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post" id='registration'>
							<?php echo $msg ?>
							<div class="form-row">
                                <div class="form-group col-md-3">
                                    <label for="owner_name" class="col-form-label">Location Owner Name<span class="text-danger">*</span></label>
                                    <input type="text" name="owner_name" required placeholder="Enter owner name" class="form-control" id="owner_name" >
                                </div>
                                <div class="form-group col-md-3">
                                    <label for="owner_number" class="col-form-label">Owner Number<span class="text-danger">*</span></label>
                                    <input type="text" name="owner_number" placeholder="Enter Owner number" class="form-control" id="owner_number" parsley-trigger="change" data-mask="0599999999" required >
                                </div>
                                <div class="form-group col-md-3">
                                    <label for="owner_email" class="col-form-label">Owner Email<span class="text-danger">*</span></label>
                                    <input type="text" name="owner_email" placeholder="Enter owner email" class="form-control" id="owner_email" required autocomplete="off" >
                                </div>
                                <div class="form-group col-md-3">
                                    <label for="contract_no" class="col-form-label">Contract No.<span class="text-danger">*</span></label>
                                    <input type="text" name="contract_no" placeholder="Enter contract no" class="form-control" id="contract_no" required autocomplete="off">
                                </div>
                                <div class="form-group col-md-3">
                                    <label for="start_cont_date" class="col-form-label">Contract Starting Date<span class="text-danger">*</span></label>
                                    <input type="text" name="start_cont_date" placeholder="Enter Contract Start Date" class="form-control" id="start_cont_date"  autocomplete="off" required>
                                </div>
                                <div class="form-group col-md-3">
                                    <label for="end_cont_date" class="col-form-label">Contract Ending Date<span class="text-danger">*</span></label>
                                    <input type="text" name="end_cont_date" placeholder="Enter Contract Ending Date" class="form-control" id="end_cont_date"  autocomplete="off" required>
                                </div>
                                <div class="form-group col-md-3">
                                    <label for="rent" class="col-form-label">Amount of Rent<span class="text-danger">*</span></label>
                                    <input type="text" name="rent" placeholder="Enter Amount of Rent" class="form-control autonumber" id="rent" autocomplete="off" required>
                                </div>
                                <div class="form-group col-md-3">
                                    <label for="service" class="col-form-label">Amount of Services</label>
                                    <input type="text" name="service" placeholder="Enter Amount of Services" class="form-control autonumber" id="service" autocomplete="off">
                                </div>                    
                                <div class="form-group col-md-3">
                                    <label for="elect_prc" class="col-form-label">Amount of Electric City</label>
                                    <input type="text" name="elect_prc" placeholder="Enter Amount of Electric City" class="form-control autonumber" id="elect_prc" autocomplete="off" >
                                </div>                      
                                <div class="form-group col-md-3">
                                    <label for="water_prc" class="col-form-label">Amount of Water</label>
                                    <input type="text" name="water_prc" placeholder="Enter Balady License No." class="form-control autonumber" id="water_prc" autocomplete="off">
                                </div>                      
                                <div class="form-group col-md-3">
                                    <label for="incuranse_prc" class="col-form-label">Amount of Incuranse<span class="text-danger">*</span></label>
                                    <input type="text" name="incuranse_prc" placeholder="Enter Amount of Incuranse" class="form-control autonumber" id="incuranse_prc" required autocomplete="off">
                                </div>                      
                                <div class="form-group col-md-3">
                                    <label for="others" class="col-form-label">Others</label>
                                    <input type="text" name="others" placeholder="Enter others" class="form-control autonumber" id="others" autocomplete="off" >
                                </div>                                      

                            </div>
							<div class="btn-group" role="group" aria-label="Edit Button">
						<a href="view_location.php?id=<?php echo $_GET['location_id'] ?>" class="btn btn-dark"><i class="fa fa-angle-double-left"></i> Back</a>
							<button type="submit" name="submit" class="btn btn-primary"><i class="mdi mdi-source-commit-next-local"></i> Register</button>
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
        
        <script src="./plugins/bootstrap-timepicker/hijri/bootstrap-hijri-datetimepicker.js"></script>
        <script src="./plugins/bootstrap-timepicker/hijri/bootstrap-hijri-datetimepicker.min.js"></script>
        <script src="./plugins/bootstrap-timepicker/hijri/bootstrap-hijri-datetimepickermin.js"></script>
        
        <script src="./plugins/bootstrap-colorpicker/js/bootstrap-colorpicker.min.js"></script>
        <script src="./plugins/clockpicker/js/bootstrap-clockpicker.min.js"></script>
        <script src="./plugins/bootstrap-daterangepicker/daterangepicker.js"></script>
        <script src="./plugins/bootstrap-datepicker/js/bootstrap-datepicker.min.js"></script>
        
        <script src="./plugins/bootstrap-filestyle/js/bootstrap-filestyle.min.js" type="text/javascript"></script>

        <!-- App js -->
        <script src="assets/pages/jquery.form-pickers.init.js"></script>
        
        <script src="assets/js/jquery.core.js"></script>
        <script src="assets/js/jquery.app.js"></script>
        
        <script defer src="./plugins/imask.js"></script>
        
        <script src="./plugins/croppie/croppie.js" type="text/javascript"></script>
        <script src="./plugins/croppie/croppie.min.js" type="text/javascript"></script>
        <script src="./plugins/croppie/exif.js" type="text/javascript"></script>
        
        <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-validate/1.19.0/jquery.validate.js"></script>
        <script src="./assets/js/jquery.custom.validation.js"></script>

        <script type="text/javascript">
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