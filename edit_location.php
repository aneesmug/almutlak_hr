<?php
	require_once __DIR__ . '/includes/db.php';

	require_once __DIR__ . '/includes/session_check.php';
	$query = mysqli_query($conDB, "SELECT * FROM `admin_login` WHERE `id_iqama`='".$username."'");
		if(mysqli_num_rows($query) == 1){
		include("./includes/avatar_select.php");
	}

$getquery = mysqli_query($conDB, "SELECT * FROM `section` WHERE `id`='".$_GET['id']."' ");

	if(mysqli_num_rows($getquery) !== 0){
		while($rec = mysqli_fetch_assoc($getquery)){
			$id_get = $rec["id"];
			$section_name_get = $rec["section_name"];
            $dept_get = $rec["dept"];
            $location_owner_get = $rec["location_owner"];
            $camera_in_get = $rec["camera_in"];
            $camera_out_get = $rec["camera_out"];
            $b_license_exp_get = $rec["b_license_exp"];
            $b_license_no_get = $rec["b_license_no"];
            $location_dist_get = $rec["location_dist"];
            $bulding_base_get = $rec["bulding_base"];
            $bulding_size_get = $rec["bulding_size"];
            $t_bulding_siz_get = $rec["t_bulding_size"];
            $latitude_get = $rec["latitude"];
            $longitude_get = $rec["longitude"];
            $location_name_get = $rec["location_name"];
            $municipality_get = $rec["municipality"];
            $sub_municipality_get = $rec["sub_municipality"];
            // $status = $rec["status"];
			
//			$salary_get = str_replace(',', '', $salary_get);

		    $dept_get = (isset($dept_get)) ? $dept_get : false ;
		    $bulding_base_get = (isset($bulding_base_get)) ? $bulding_base_get : false ;
		    $bulding_size = (isset($bulding_size)) ? $bulding_size : false ;


		    $query_loc_img = mysqli_query($conDB, "SELECT * FROM `location_img` WHERE `location_id`='".$id_get."' ");
                while ($rec_img = mysqli_fetch_array($query_loc_img)) {
                $id_img_get = $rec_img["location_id"];
                $in_img_get = $rec_img["in_img"];
                $out_img_get = $rec_img["out_img"];
            }

	}	
} else {
		//when the id not equals id show database
		header( "refresh:1 ; url= ./all_locations.php" );
	}

if ($id_img_get !== $id_get) {
	$defult_img = "./assets/location_content/default_in.jpg";
	mysqli_query($conDB, "INSERT INTO `location_img` (`location_id`,`in_img`,`out_img`,`reg_date`) VALUES ('".$id_get."','".$defult_img."','".$defult_img."','".date("c")."')") or die (mysqli_error());
}


if(isset($_POST['submit'])){

	$section_name_po = mysqli_real_escape_string($conDB, $_POST['section_name']);
	$dept_po = mysqli_real_escape_string($conDB, $_POST['dept']);
	// $location_owner_po = mysqli_real_escape_string($conDB, $_POST['location_owner']);
	$camera_in_po = mysqli_real_escape_string($conDB, $_POST['camera_in']);
	$camera_out_po = mysqli_real_escape_string($conDB, $_POST['camera_out']);
	$b_license_exp_po = mysqli_real_escape_string($conDB, $_POST['b_license_exp']);
	$b_license_no_po = mysqli_real_escape_string($conDB, $_POST['b_license_no']);
	$location_dist_po = mysqli_real_escape_string($conDB, $_POST['location_dist']);
	$bulding_base_po = mysqli_real_escape_string($conDB, $_POST['bulding_base']);
	$bulding_size_po = mysqli_real_escape_string($conDB, $_POST['bulding_size']);
	$t_bulding_size_po = mysqli_real_escape_string($conDB, $_POST['t_bulding_size']);
	$latitude_po = mysqli_real_escape_string($conDB, $_POST['latitude']);
	$longitude_po = mysqli_real_escape_string($conDB, $_POST['longitude']);
	$loc_address_po = mysqli_real_escape_string($conDB, $_POST['loc_address']);
	$municipality_po = mysqli_real_escape_string($conDB, $_POST['municipality']);
	$sub_municipality_po = mysqli_real_escape_string($conDB, $_POST['sub_municipality']);


	$status_po = "A";


	if ($id_img_get !== $id_get) {
		$defult_img = "./assets/location_content/default_in.jpg";
		mysqli_query($conDB, "INSERT INTO `location_img` (`location_id`,`in_img`,`out_img`,`reg_date`) VALUES ('".$id_get."','".$defult_img."','".$defult_img."','".date("c")."')") or die (mysqli_error());
	}
	
	
$u = "UPDATE `section` SET `section_name`='".$section_name_po."', `dept`='".$dept_po."',`camera_in`='".$camera_in_po."',`camera_out`='".$camera_out_po."',`b_license_exp`='".$b_license_exp_po."',`b_license_no`='".$b_license_no_po."',`location_dist`='".$location_dist_po."', `bulding_base`='".$bulding_base_po."', `bulding_size`='".$bulding_size_po."', `t_bulding_size`='".$t_bulding_size_po."', `latitude`='".$latitude_po."', `longitude`='".$longitude_po."', `location_name`='".$loc_address_po."', `municipality`='".$municipality_po."', `sub_municipality`='".$sub_municipality_po."', `status`='".$status_po."' WHERE `id`='".$_GET['id']."' ";
		mysqli_query($conDB, $u) or die (mysqli_error());
		
		/************log************/
		// mysqli_query($conDB, "INSERT INTO `activity_log` (`user_editor`,`page`,`pg_id`,`reg_date`) VALUES ('".$_COOKIE['user']."','".$pgname."','".$_GET['id']."','".date("c")."')") or die (mysqli_error());
		/************log************/
		$error_1 = "<div class='alert alert-success bg-success text-white border-0'><strong>Successfully!</strong> Your location details will be updated!</div>";
		header( "refresh:1 ; url= ./view_location.php?id=".$_GET['id']." " );

	/*****************************************/
	
	if(isset($_FILES['avatar']['name'])){
		$nameava = $_FILES['avatar']['name'];
		$tmp_name = $_FILES['avatar']['tmp_name'];
		if($nameava){
			$image = "./assets/emp_pics/".$iqama_up.".".$nameava." ";
			move_uploaded_file($tmp_name, $image);
			$query = mysqli_query($conDB, "UPDATE `employees` SET `avatar`='".$image."' WHERE `id`='".$_GET['id']."' ");
				if($image){
					$error_1 = "<div class='alert alert-success'>Images has been changed Please wait 2 seconds</div>";
					mysqli_query($conDB, $query);
					header( "Refresh:2; url= ./view_employee.php?id=".$_GET['id']." ", true, 303);
				} 
		 }
	}
	
	/******************************************/

}

if($emp_avatar_get == "./assets/emp_pics/defult.png"){
		$image_txt = "Upload Employee Picture";
	} else {
		$image_txt = "Change Picture";
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

        <link href="./plugins/bootstrap-tagsinput/css/bootstrap-tagsinput.css" rel="stylesheet" />
        <link href="./plugins/bootstrap-select/css/bootstrap-select.min.css" rel="stylesheet" />
        <link href="./plugins/select2/css/select2.min.css" rel="stylesheet" type="text/css" />
        <link rel="stylesheet" href="./plugins/switchery/switchery.min.css" />

        <link href="./plugins/bootstrap-timepicker/bootstrap-timepicker.min.css" rel="stylesheet">
        <link href="./plugins/bootstrap-colorpicker/css/bootstrap-colorpicker.min.css" rel="stylesheet">
        <link href="./plugins/bootstrap-datepicker/css/bootstrap-datepicker.min.css" rel="stylesheet">
        <link href="./plugins/clockpicker/css/bootstrap-clockpicker.min.css" rel="stylesheet">
        <link href="./plugins/bootstrap-daterangepicker/daterangepicker.css" rel="stylesheet">
		
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
						<h4 class="m-t-0 header-title">Edit Location</h4>
						<form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post">
							<?php echo $error_1 ?>
							<div class="form-row">
								<div class="form-group col-md-3">
									<label for="section_name" class="col-form-label">Location Name<span class="text-danger">*</span></label>
									<input type="text" name="section_name" required placeholder="Enter section name" class="form-control" id="section_name" value="<?php echo $section_name_get ?>" >
								</div>
								<div class="form-group col-md-2">
									<label for="latitude" class="col-form-label">Latitude<span class="text-danger">*</span></label>
									<input type="text" name="latitude" placeholder="Enter google latitude" class="form-control" id="latitude" required value="<?php echo $latitude_get ?>" >
								</div>
								<div class="form-group col-md-2">
									<label for="longitude" class="col-form-label">Longitude<span class="text-danger">*</span></label>
									<input type="text" name="longitude" placeholder="Enter google longitude" class="form-control" id="longitude" required autocomplete="off" value="<?php echo $longitude_get ?>" >
								</div>
								<div class="form-group col-md-2">
									<label for="t_bulding_size" class="col-form-label">Total Bulding Size (M)</label>
									<input type="text" name="t_bulding_size" placeholder="Enter total bulding base in metters" class="form-control" id="t_bulding_size" autocomplete="off" value="<?php echo $t_bulding_siz_get ?>" >
								</div>
								<div class="form-group col-md-3">
									<label for="dept" class="col-form-label">Select Department Name<span class="text-danger">*</span></label>
									<select class="form-control selectpicker" data-live-search="true" data-style="btn-custom" name="dept" required>
										<option value="<?php echo $dept_get ?>"><?php echo $dept_get ?></option>
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
								<div class="form-group col-md-2">
									<label for="camera_in" class="col-form-label">Camera (IN)</label>
									<input type="text" name="camera_in" placeholder="Enter camera Inside" class="form-control" id="camera_in"  autocomplete="off" value="<?php echo $camera_in_get ?>" >
								</div>
								<div class="form-group col-md-2">
									<label for="camera_out" class="col-form-label">Camera (OUT)</label>
									<input type="text" name="camera_out" placeholder="Enter camera outside" class="form-control" id="camera_out"  autocomplete="off" value="<?php echo $camera_out_get ?>" >
								</div>
								<div class="form-group col-md-2">
									<label for="bulding_base" class="col-form-label">Bulding Base</label>
									<input type="text" name="bulding_base" placeholder="Enter bulding base" class="form-control" id="bulding_base" autocomplete="off" value="<?php echo $bulding_base_get ?>" >
								</div>
								<!-- <div class="form-group col-md-4">
									<label for="bulding_base" class="col-form-label">Bulding Base<span class="text-danger">*</span></label>
									<select class="form-control selectpicker" data-live-search="true" data-style="btn-custom" name="bulding_base" required>
										<option value="<?php //echo $bulding_base_get ?>" ><?php //echo $bulding_base_get ?></option>
										<option value="">Select</option>
										<option value="Cement">Cement</option>
										<option value="IBSF Cabnit">IBSF Cabnit</option>
										<option value="Matel">Matel</option>
										<option value="Matel U Shape">Matel U Shape</option>
										<option value="SARIAH Cabnit">SARIAH Cabnit</option>
										<option value="Wooden Walls">Wooden Walls</option>
									</select>
								</div> -->
								<div class="form-group col-md-3">
									<label for="bulding_size" class="col-form-label">Bulding Size (L * W)</label>
									<input type="text" name="bulding_size" placeholder="Enter Bulding Size (L * W)" class="form-control" id="bulding_size" autocomplete="off" value="<?php echo $bulding_size_get ?>" >
								</div>
								<!-- <div class="form-group col-md-4">
									<label for="bulding_size" class="col-form-label">Bulding Size (L * W)<span class="text-danger">*</span></label>
									<select class="form-control selectpicker" data-live-search="true" data-style="btn-custom" name="bulding_size" required>
										<option value="<?php //echo $bulding_size_get ?>" ><?php //echo $bulding_size_get ?></option>
										<option value="">Select</option>
										<option value="0">0</option>
										<option value="3*2">3*2</option>
										<option value="4*3">4*3</option>
										<option value="5*2">5*2</option>
										<option value="6*4">6*4</option>
										<option value="8*6">8*6</option>
									</select>
								</div> -->						
								<div class="form-group col-md-3">
									<label for="location_dist" class="col-form-label">District<span class="text-danger">*</span></label>
									<input type="text" name="location_dist" placeholder="Enter District" class="form-control" id="location_dist" required autocomplete="off" value="<?php echo $location_dist_get ?>" >
								</div>						
								<div class="form-group col-md-3">
									<label for="b_license_no" class="col-form-label">Balady License No.<span class="text-danger">*</span></label>
									<input type="text" name="b_license_no" placeholder="Enter Balady License No." class="form-control" id="b_license_no" required autocomplete="off" value="<?php echo $b_license_no_get ?>" >
								</div>						
								<div class="form-group col-md-3">
									<label for="b_license_exp_hijri" class="col-form-label">Balady License Exp.<span class="text-danger">*</span></label>
									<input type="text" name="b_license_exp" placeholder="Enter Balady License Exp." class="form-control" id="b_license_exp_hijri" required autocomplete="off" value="<?php echo $b_license_exp_get ?>" >
								</div>						
								<div class="form-group col-md-3">
									<label for="municipality" class="col-form-label">Municipality</label>
									<input type="text" name="municipality" placeholder="Enter Municipality name" class="form-control" id="municipality" autocomplete="off" value="<?php echo $municipality_get ?>" >
								</div>						
								<div class="form-group col-md-3">
									<label for="sub_municipality" class="col-form-label">Sub-municipality</label>
									<input type="text" name="sub_municipality" placeholder="Enter sub municipality name" class="form-control" id="sub_municipality"  autocomplete="off" value="<?php echo $sub_municipality_get ?>" >
								</div>
								<div class="form-group col-md-12">
									<label for="loc_address" class="col-form-label">Location Address</label>
									<textarea name="loc_address" placeholder="Enter location address" class="form-control" id="loc_address" rows="5"><?php echo $location_name_get ?></textarea>
								</div>
								

							</div>
							<div class="btn-group" role="group" aria-label="Edit Button">
							<a href="./view_location.php?id=<?php echo $_GET['id']; ?>" class="btn btn-dark"><i class="fa fa-angle-double-left"></i> Back</a>
							<button type="submit" name="submit" class="btn btn-primary"><i class="mdi mdi-source-commit-next-local"></i> Edit Save</button>
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

<div class="modal fade loc_inside_img" tabindex="-1" role="dialog" aria-labelledby="myLargeModalLabel" aria-hidden="true" style="display: none;">
<!--	<div class="modal-dialog modal-lg" style="max-width: 1450px !important">-->
	<div class="modal-dialog modal-lg">
		<div class="modal-content">
			<div class="modal-header" style="background-color: #2D7BF4 !important; color: #fff !important;">
				<button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
				<h4 class="modal-title" id="myLargeModalLabel">
					<i class="mdi mdi-image-filter-tilt-shift "></i> 
					Upload new Image for <?php echo $name_get ?>
				</h4>
			</div>
			<div class="modal-body">
<!---->
				
	  	<div class="row">
	  		<div class="col-md-6 text-center">
				<div id="upload_loc_in_img" style="width:350px"></div>
	  		</div>
  		  	<div class="col-md-6">
			  <div >
				  <img src="<?php echo $in_img_get ?>" style="width:300px;padding:30px;height:300px;margin-top:30px" />
			  </div>
	  		</div>
			<div class="col-md-6" style="padding-top:30px;">
				<strong>Select Image:</strong>
				<input type="file" class="filestyle" id="upload_inside_img" data-btnClass="btn-primary" accept="image/*">
	  		</div>
			
	  	</div>
				
<!---->
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-danger waves-effect" data-dismiss="modal">Close</button>
				<button type="button" class="btn btn-success upload_result_in_img waves-effect waves-light"><i class="mdi mdi-backup-restore"></i> Upload Image</button>
			</div>
		</div><!-- /.modal-content -->
	</div><!-- /.modal-dialog -->
</div><!-- /.modal -->

<div class="modal fade loc_outside_img" tabindex="-1" role="dialog" aria-labelledby="myLargeModalLabel" aria-hidden="true" style="display: none;">
<!--	<div class="modal-dialog modal-lg" style="max-width: 1450px !important">-->
	<div class="modal-dialog modal-lg">
		<div class="modal-content">
			<div class="modal-header" style="background-color: #2D7BF4 !important; color: #fff !important;">
				<button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
				<h4 class="modal-title" id="myLargeModalLabel">
					<i class="mdi mdi-image-filter-tilt-shift "></i> 
					Upload new Image for <?php echo $name_get ?>
				</h4>
			</div>
			<div class="modal-body">
<!---->
				
	  	<div class="row">
	  		<div class="col-md-6 text-center">
				<div id="upload_loc_out_img" style="width:350px"></div>
	  		</div>
  		  	<div class="col-md-6">
			  <div >
				  <img src="<?php echo $out_img_get ?>" style="width:300px;padding:30px;height:300px;margin-top:30px" />
			  </div>
	  		</div>
			<div class="col-md-6" style="padding-top:30px;">
				<strong>Select Image:</strong>
				<input type="file" class="filestyle" id="upload_outside_img" data-btnClass="btn-primary" accept="image/*">
	  		</div>
			
	  	</div>
				
<!---->
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-danger waves-effect" data-dismiss="modal">Close</button>
				<button type="button" class="btn btn-success upload_result_out_img waves-effect waves-light"><i class="mdi mdi-backup-restore"></i> Upload Image</button>
			</div>
		</div><!-- /.modal-content -->
	</div><!-- /.modal-dialog -->
</div><!-- /.modal -->



        <!-- jQuery  -->
        <script src="assets/js/jquery.min.js"></script>
        <script src="assets/js/bootstrap.bundle.min.js"></script>
        <script src="assets/js/metisMenu.min.js"></script>
        <script src="assets/js/waves.js"></script>
        <script src="assets/js/jquery.slimscroll.js"></script>


        <!-- Modal-Effect -->
		<script type="text/javascript" src="./plugins/parsleyjs/parsley.min.js"></script>
<!--		<script src="./plugins/bootstrap-inputmask/bootstrap-inputmask.min.js" type="text/javascript"></script>-->
		<script src="./plugins/bootstrap-inputmask/jquery.inputmask.min.js" type="text/javascript"></script>
<!--		<script src="https://cdn.jsdelivr.net/gh/RobinHerbots/jquery.inputmask@5.0.0-beta.87/dist/jquery.inputmask.min.js" type="text/javascript"></script>-->
		

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

        <script src="./plugins/moment/moment.js"></script>
        <script src="./plugins/bootstrap-timepicker/bootstrap-timepicker.js"></script>
		
        <script src="./plugins/bootstrap-timepicker/hijri/bootstrap-hijri-datetimepicker.js"></script>
        <script src="./plugins/bootstrap-timepicker/hijri/bootstrap-hijri-datetimepicker.min.js"></script>
        <script src="./plugins/bootstrap-timepicker/hijri/bootstrap-hijri-datetimepickermin.js"></script>
		
        <script src="./plugins/bootstrap-colorpicker/js/bootstrap-colorpicker.min.js"></script>
        <script src="./plugins/clockpicker/js/bootstrap-clockpicker.min.js"></script>
        <script src="./plugins/bootstrap-daterangepicker/daterangepicker.js"></script>
        <script src="./plugins/bootstrap-datepicker/js/bootstrap-datepicker.min.js"></script>

        <!-- App js -->
		<script src="assets/pages/jquery.form-pickers.init.js"></script>
		<script src="assets/pages/jquery.form-hijri-pickers.init.js"></script>
        <script type="text/javascript" src="assets/pages/jquery.form-advanced.init.js"></script>

		
        <script src="./plugins/bootstrap-filestyle/js/bootstrap-filestyle.min.js" type="text/javascript"></script>

        <!-- App js -->
		<script src="assets/pages/jquery.form-pickers.init.js"></script>
		<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-validate/1.19.0/jquery.validate.js"></script>
		<script src="./assets/js/jquery.custom.validation.js"></script>

		<script src="./plugins/croppie/croppie.js" type="text/javascript"></script>
		<script src="./plugins/croppie/croppie.min.js" type="text/javascript"></script>
		<script src="./plugins/croppie/exif.js" type="text/javascript"></script>

        <script src="assets/js/jquery.core.js"></script>
        <script src="assets/js/jquery.app.js"></script>


<script type="text/javascript">

$uploadCrop_in_img = $('#upload_loc_in_img').croppie({
	url: "<?php echo substr($in_img_get, 2);?>",
	enableExif: true,
    viewport: {
        width: 400,
        height: 400,
        type: 'square', /*type: 'circle',*/
    },
    boundary: {
        width: 500,
        height: 500,
    }
});
$('#upload_inside_img').on('change', function () {
	var reader = new FileReader();
    reader.onload = function (e) {
    	$uploadCrop_in_img.croppie('bind', {
    		url: e.target.result
    	}).then(function(){
    		console.log('jQuery bind complete');
    	});
    	
    }
    reader.readAsDataURL(this.files[0]);
});

$('.upload_result_in_img').on('click', function (ev) {
    $uploadCrop_in_img.croppie('result', {
        type: 'canvas',
        size: 'viewport'
    }).then(function (resp) {

        $.ajax({
            url: "./includes/ajaxpro_locatio_in_img.php",
            type: "POST",
            data: {"image": resp, "id": "<?php echo $id_get; ?>", "section_name_get": "<?php echo $section_name_get; ?>"},
            success: function (data) {
                if (data == 'Image Uploaded Successfully') {
                    html = '<img src="' + resp + '" />';
                    $("#upload_loc_in_img_i").html(html);
					
						swal({
	                        title:"Uploaded!",
	                        text:"Your files bas been uploaded successfully.",
	                        type:'success',allowOutsideClick:false
	                    }).then(function(isConfirm){(isConfirm)?location.reload():""});

					/*alert("Image uploaded Successfully!");
					location.reload(); //refresh page after uploading*/
					 
					
                } else {
                    $("body").append("<div class='upload-error'>" + data + "</div>");
                     // alert("Error");

                }
            }
        });
    });
});



$uploadCrop_out_img = $('#upload_loc_out_img').croppie({
	url: "<?php echo substr($out_img_get, 2);?>",
	enableExif: true,
    viewport: {
        width: 400,
        height: 400,
        type: 'square',
        // type: 'circle',
    },
    boundary: {
        width: 500,
        height: 500,
    }
});
$('#upload_outside_img').on('change', function () {
	var reader = new FileReader();
    reader.onload = function (e) {
    	$uploadCrop_out_img.croppie('bind', {
    		url: e.target.result
    	}).then(function(){
    		console.log('jQuery bind complete');
    	});
    	
    }
    reader.readAsDataURL(this.files[0]);
});

$('.upload_result_out_img').on('click', function (ev) {
    $uploadCrop_out_img.croppie('result', {
        type: 'canvas',
        size: 'viewport'
    }).then(function (resp) {

        $.ajax({
            url: "./includes/ajaxpro_locatio_out_img.php",
            type: "POST",
            data: {"image": resp, "id": "<?php echo $id_get; ?>", "section_name_get": "<?php echo $section_name_get; ?>"},
            success: function (data) {
                if (data == 'Image Uploaded Successfully') {
                    html = '<img src="' + resp + '" />';
                    $("#upload_loc_out_img_i").html(html);
						swal({
	                        title:"Uploaded!",
	                        text:"Your files bas been uploaded successfully.",
	                        type:'success',allowOutsideClick:false
	                    }).then(function(isConfirm){(isConfirm)?location.reload():""});
                } else {
                    $("body").append("<div class='upload-error'>" + data + "</div>");

                }
            }
        });
    });
});
	



</script>



<script type="text/javascript">
	$(document).ready(function() {
		$('form').parsley();
	});
	$(document).ready(function(){
//		$('#plate_no').inputmask({mask:'9999'});  //static mask
		$('#plate_no').inputmask({mask:'9999-aaa'});  //static mask
	});
/**
* Theme: Highdmin - Responsive Bootstrap 4 Admin Dashboard
* Author: Coderthemes
* Auto Complete
*/


/*jslint  browser: true, white: true, plusplus: true */
/*global $, countries */

$(function () {
    'use strict';

<?php
	$sql_bulding_base = "SELECT * FROM `section` GROUP BY `bulding_base`";
	$query_bulding_base = mysqli_query($conDB, $sql_bulding_base);
?>
	var nhlTeams_mn = [
<?php
	while($rec = mysqli_fetch_assoc($query_bulding_base)){
		$buldingbase = $rec["bulding_base"];
		echo "'".$buldingbase."',";
}
?>
	];
    var nhl_mn = $.map(nhlTeams_mn, function (team_mn) { return { value: team_mn}; });
    var teams_mn = nhl_mn.concat();
    // Initialize autocomplete with local lookup:

    $('#bulding_base').devbridgeAutocomplete({
        lookup: teams_mn,
        minChars: 1,
        onSelect: function (suggestion) {
            $('#selection').html('You selected: ' + suggestion.value + ', ' + suggestion.data.category);
        },
        showNoSuggestionNotice: true,
        noSuggestionNotice: 'Sorry, no matching results',
//        groupBy: 'category'
    });
<?php
	$sql_bulding_size = "SELECT * FROM `section` GROUP BY `bulding_size`";
	$query_bulding_size = mysqli_query($conDB, $sql_bulding_size);
?>
	var nhlTeams_md = [
<?php
	while($rec = mysqli_fetch_assoc($query_bulding_size)){
		$buldingsize = $rec["bulding_size"];
		echo "'".$buldingsize."',";
}
?>
	];
    var nhl_md = $.map(nhlTeams_md, function (team_md) { return { value: team_md}; });
    var teams_md = nhl_md.concat();
    // Initialize autocomplete with local lookup:
    $('#bulding_size').devbridgeAutocomplete({
        lookup: teams_md,
        minChars: 1,
        onSelect: function (suggestion) {
            $('#selection').html('You selected: ' + suggestion.value + ', ' + suggestion.data.category);
        },
        showNoSuggestionNotice: true,
        noSuggestionNotice: 'Sorry, no matching results',
//        groupBy: 'category'
    });
	
<?php
	$sql_made_year = "SELECT * FROM `cars` GROUP BY `made_year`";
	$query_made_year = mysqli_query($conDB, $sql_made_year);
?>
	var nhlTeams_mdy = [
<?php
	while($rec = mysqli_fetch_assoc($query_made_year)){
		$madeyear = $rec["made_year"];
		echo "'".$madeyear."',";
}
?>
	];
    var nhl_mdy = $.map(nhlTeams_mdy, function (team_mdy) { return { value: team_mdy}; });
    var teams_mdy = nhl_mdy.concat();
    // Initialize autocomplete with local lookup:
    $('#made_year').devbridgeAutocomplete({
        lookup: teams_mdy,
        minChars: 1,
        onSelect: function (suggestion) {
            $('#selection').html('You selected: ' + suggestion.value + ', ' + suggestion.data.category);
        },
        showNoSuggestionNotice: true,
        noSuggestionNotice: 'Sorry, no matching results',
//        groupBy: 'category'
    });  
	

});

</script>

    </body>
</html>