<?php
	require_once __DIR__ . '/includes/db.php';

	require_once __DIR__ . '/includes/session_check.php';
	$query = mysqli_query($conDB, "SELECT * FROM `admin_login` WHERE `id_iqama`='".$username."'");
		if(mysqli_num_rows($query) == 1){
		include("./includes/avatar_select.php");
	}
    
if(isset($_POST['submit'])){
	$section_name = mysqli_real_escape_string($conDB, $_POST['section_name']);
    $dept = mysqli_real_escape_string($conDB, $_POST['dept']);
    $camera_in = mysqli_real_escape_string($conDB, $_POST['camera_in']);
    $camera_out = mysqli_real_escape_string($conDB, $_POST['camera_out']);
    $b_license_exp = mysqli_real_escape_string($conDB, $_POST['b_license_exp']);
    $b_license_no = mysqli_real_escape_string($conDB, $_POST['b_license_no']);
    $location_dist = mysqli_real_escape_string($conDB, $_POST['location_dist']);
    $bulding_base = mysqli_real_escape_string($conDB, $_POST['bulding_base']);
    $bulding_size = mysqli_real_escape_string($conDB, $_POST['bulding_size']);
    $t_bulding_size = mysqli_real_escape_string($conDB, $_POST['t_bulding_size']);
    $latitude = mysqli_real_escape_string($conDB, $_POST['latitude']);
    $longitude = mysqli_real_escape_string($conDB, $_POST['longitude']);
    $loc_address = mysqli_real_escape_string($conDB, $_POST['loc_address']);
    $municipality = mysqli_real_escape_string($conDB, $_POST['municipality']);
    $sub_municipality = mysqli_real_escape_string($conDB, $_POST['sub_municipality']);
	$status = "A";
	$date_reg = date("c");
	
	
	if($section_name){
		$query = "INSERT INTO `section` (`section_name`, `dept`, `camera_in`, `camera_out`, `b_license_exp`, `b_license_no`, `location_dist`, `bulding_base`, `bulding_size`, `t_bulding_size`, `latitude`, `longitude`, `location_name`, `municipality`, `sub_municipality`, `status`) VALUES ('".$section_name."', '".$dept."', '".$camera_in."','".$camera_out."','".$b_license_exp."','".$b_license_no."','".$location_dist."','".$bulding_base."','".$bulding_size."','".$t_bulding_size."','".$latitude."','".$longitude."','".$loc_address."','".$municipality."','".$sub_municipality."', '".$status."')";
		mysqli_query($conDB, $query);
		/************log************/
		//mysqli_query($conDB, "INSERT INTO `activity_log` (`user_editor`,`page`,`pg_id`,`reg_date`) VALUES ('".$_COOKIE['user']."','".$pgname."','".$_POST['maker_name']."','".date("c")."')") or die (mysqli_error());
		/************log************/
		$msg = "<div class=\"alert alert-success bg-success text-white border-0\" role=\"alert\">Add Seccssfully!</div>
		";		
		 header( "refresh:2 ; url=all_locations.php" );
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
						<form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post">
							<?php echo $msg ?>
							<div class="form-row">
                                <div class="form-group col-md-3">
                                    <label for="section_name" class="col-form-label">Location Name<span class="text-danger">*</span></label>
                                    <input type="text" name="section_name" required placeholder="Enter section name" class="form-control" id="section_name" >
                                </div>
                                <div class="form-group col-md-2">
                                    <label for="latitude" class="col-form-label">Latitude<span class="text-danger">*</span></label>
                                    <input type="text" name="latitude" placeholder="Enter google latitude" class="form-control" id="latitude" required >
                                </div>
                                <div class="form-group col-md-2">
                                    <label for="longitude" class="col-form-label">Longitude<span class="text-danger">*</span></label>
                                    <input type="text" name="longitude" placeholder="Enter google longitude" class="form-control" id="longitude" required autocomplete="off" >
                                </div>
                                <div class="form-group col-md-2">
                                    <label for="t_bulding_size" class="col-form-label">Total Bulding Size (M)</label>
                                    <input type="text" name="t_bulding_size" placeholder="Enter total bulding base in metters" class="form-control" id="t_bulding_size" autocomplete="off">
                                </div>
                                <div class="form-group col-md-3">
                                    <label for="dept" class="col-form-label">Select Department Name<span class="text-danger">*</span></label>
                                    <select class="form-control selectpicker" data-live-search="true" data-style="btn-custom" name="dept" required>
                                        <option value="">Select</option>
                                        <?php
                                            $query_sectin_nme = mysqli_query($conDB, "SELECT * FROM `department` ORDER BY `dep_nme` REGEXP '^[^A-Za-z]' ASC, dep_nme");
                                            while($rec = mysqli_fetch_assoc($query_sectin_nme)){
                                                $brand_name = $rec["dep_nme"];
                                        ?>
                                            <option value="<?php echo $brand_name ?>"><?php echo $brand_name ?></option>
                                        <?php } ?>
                                    </select>
                                </div>
                                <div class="form-group col-md-1">
                                    <label for="camera_in" class="col-form-label">Camera (IN)</label>
                                    <input type="text" name="camera_in" placeholder="Enter camera Inside" class="form-control" id="camera_in"  autocomplete="off">
                                </div>
                                <div class="form-group col-md-1">
                                    <label for="camera_out" class="col-form-label">Camera (OUT)</label>
                                    <input type="text" name="camera_out" placeholder="Enter camera outside" class="form-control" id="camera_out"  autocomplete="off" >
                                </div>
                                <div class="form-group col-md-2">
                                    <label for="bulding_base" class="col-form-label">Bulding Base</label>
                                    <input type="text" name="bulding_base" placeholder="Enter bulding base" class="form-control" id="bulding_base" autocomplete="off">
                                </div>
                                <div class="form-group col-md-2">
                                    <label for="bulding_size" class="col-form-label">Bulding Size (L * W)</label>
                                    <input type="text" name="bulding_size" placeholder="Enter Bulding Size (L * W)" class="form-control" id="bulding_size" autocomplete="off">
                                </div>                    
                                <div class="form-group col-md-3">
                                    <label for="location_dist" class="col-form-label">District<span class="text-danger">*</span></label>
                                    <input type="text" name="location_dist" placeholder="Enter District" class="form-control" id="location_dist" required autocomplete="off" >
                                </div>                      
                                <div class="form-group col-md-3">
                                    <label for="b_license_no" class="col-form-label">Balady License No.<span class="text-danger">*</span></label>
                                    <input type="text" name="b_license_no" placeholder="Enter Balady License No." class="form-control" id="b_license_no" required autocomplete="off">
                                </div>                      
                                <div class="form-group col-md-2">
                                    <label for="b_license_exp_hijri" class="col-form-label">Balady License Exp.<span class="text-danger">*</span></label>
                                    <input type="text" name="b_license_exp" placeholder="Enter Balady License Exp." class="form-control" id="b_license_exp_hijri" required autocomplete="off">
                                </div>                      
                                <div class="form-group col-md-3">
                                    <label for="municipality" class="col-form-label">Municipality</label>
                                    <input type="text" name="municipality" placeholder="Enter Municipality name" class="form-control" id="municipality" autocomplete="off" >
                                </div>                      
                                <div class="form-group col-md-3">
                                    <label for="sub_municipality" class="col-form-label">Sub-municipality</label>
                                    <input type="text" name="sub_municipality" placeholder="Enter sub municipality name" class="form-control" id="sub_municipality"  autocomplete="off" >
                                </div>
                                <div class="form-group col-md-4">
                                    <label for="loc_address" class="col-form-label">Location Address</label>
                                    <input type="text" name="loc_address" placeholder="Enter location address" class="form-control" id="loc_address"  autocomplete="off" >
                                </div>                                          

                            </div>
							<div class="btn-group" role="group" aria-label="Edit Button">
							<a href="all_locations.php" class="btn btn-dark"><i class="fa fa-angle-double-left"></i> Back</a>
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
<!--        <script src="./plugins/bootstrap-inputmask/bootstrap-inputmask.min.js" type="text/javascript"></script>-->
        <script src="./plugins/bootstrap-inputmask/jquery.inputmask.min.js" type="text/javascript"></script>
<!--        <script src="https://cdn.jsdelivr.net/gh/RobinHerbots/jquery.inputmask@5.0.0-beta.87/dist/jquery.inputmask.min.js" type="text/javascript"></script>-->
        

        <script src="./plugins/autoNumeric/autoNumeric.js" type="text/javascript"></script>

        <script src="./plugins/switchery/switchery.min.js"></script>
        <script src="./plugins/bootstrap-tagsinput/js/bootstrap-tagsinput.min.js"></script>
        <script src="./plugins/select2/js/select2.min.js" type="text/javascript"></script>
        <script src="./plugins/bootstrap-select/js/bootstrap-select.js" type="text/javascript"></script>
        <script src="./plugins/bootstrap-filestyle/js/bootstrap-filestyle.min.js" type="text/javascript"></script>
        <script src="./plugins/bootstrap-maxlength/bootstrap-maxlength.js" type="text/javascript"></script>
        
        
        <script type="text/javascript" src="./plugins/autocomplete/jquery.mockjax.js"></script>
        <script type="text/javascript" src="./plugins/autocomplete/jquery.autocomplete.min.js"></script>
        <script type="text/javascript" src="./plugins/autocomplete/countries.js"></script>
<!--        <script type="text/javascript" src="assets/pages/jquery.autocomplete.init.js"></script>-->

        <!-- App js -->
        <script src="assets/pages/jquery.form-pickers.init.js"></script>
        <script type="text/javascript" src="assets/pages/jquery.form-advanced.init.js"></script>

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
        <script src="assets/pages/jquery.form-hijri-pickers.init.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-validate/1.19.0/jquery.validate.js"></script>
        <script src="./assets/js/jquery.custom.validation.js"></script>

        <script src="./plugins/croppie/croppie.js" type="text/javascript"></script>
        <script src="./plugins/croppie/croppie.min.js" type="text/javascript"></script>
        <script src="./plugins/croppie/exif.js" type="text/javascript"></script>

        <script src="assets/js/jquery.core.js"></script>
        <script src="assets/js/jquery.app.js"></script>
    </body>
</html>