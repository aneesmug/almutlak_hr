<?php
	require_once __DIR__ . '/includes/db.php';

	require_once __DIR__ . '/includes/session_check.php';
	$query = mysqli_query($conDB, "SELECT * FROM `admin_login` WHERE `id_iqama`='".$username."'");
		if(mysqli_num_rows($query) == 1){
		include("./includes/avatar_select.php");
	}

$getquery = mysqli_query($conDB, "SELECT * FROM `employees` WHERE `id`='".$_GET['emp_id']."'  ");

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
			
			$salary_get = str_replace(',', '', $salary_get);
			
		$getquery_vac = mysqli_query($conDB, "SELECT * FROM `emp_vacation` WHERE `id`='".$_GET['id']."' ");
			while($rec = mysqli_fetch_assoc($getquery_vac)){
				$id_get_v = $rec["id"];
				$permit_no_get_v = $rec["permit_no"];
			}
	}
		
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

	$name_emp_up = mysqli_real_escape_string($conDB, $_POST['name']);
	$emp_id_up = htmlentities($_POST['emp_id']);
	$iqama_up = htmlentities($_POST['iqama']);
	$mobile_up = htmlentities($_POST['mobile']);
	$salary_up = mysqli_real_escape_string($conDB, $_POST['salary']);
	$vacation_days_up = htmlentities($_POST['vacation_days']);
	
	$salary_up = str_replace(',', '', $salary_up);

	
$u = "UPDATE `emp_vacation` SET `name`='".$name_emp_up."', `emp_id`='".$emp_id_up."', `iqama`='".$iqama_up."', `mobile`='".$mobile_up."', `salary`='".$salary_up."', `vacation_days`='".$vacation_days_up."' WHERE `id`='".$_GET['id']."' ";
		mysqli_query($conDB, $u) or die (mysqli_error());
		
		/************log************/
		mysqli_query($conDB, "INSERT INTO `activity_log` (`user_editor`,`page`,`pg_id`,`reg_date`) VALUES ('".$_COOKIE['user']."','".$pgname."','".$_GET['id']."','".date("c")."')") or die (mysqli_error());
		/************log************/
		$error_1 = "<div class='alert alert-success'><strong>Successfully!</strong> Your employee details will be successfully edit!</div>";
		header("refresh:1; ./edit_employee.php?id=".$_GET['id']."");
	
	/*****************************************/
	
	if(isset($_FILES['avatar']['name'])){
		$nameava = $_FILES['avatar']['name'];
		$tmp_name = $_FILES['avatar']['tmp_name'];
		if($nameava){
			$image = "./assets/emp_pics/".$iqama_up.".".$nameava." ";
			move_uploaded_file($tmp_name, $image);
			$query = mysqli_query($conDB, "UPDATE `employees` SET `avatar`='".$image."' WHERE `id`='".$_GET['id']."' ");
				if($image){
					$error_1 = "<div class='alert alert-success'>Images has been changed Please wait 5 seconds</div>";
					mysqli_query($conDB, $query);
					header( "Refresh:5; url= ./edit_employee.php?id=".$_GET['id']." ", true, 303);
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
                            <div class="col-sm-12">
                                <!-- meta -->
								<div class="profile-user-box card-box <?php if($emp_status_get == "active"){echo "bg-custom";}else{echo "bg-danger";}?>">
                                    <div class="row">
                                        <div class="col-sm-6">
                                            <span class="float-left mr-3"><img src="<?php echo $emp_avatar_get ?>" alt="<?php echo $name_get ?>" class="thumb-lg rounded-circle"></span>
                                            <div class="media-body text-white">
                                                <h4 class="mt-1 mb-1 font-18">Name: <?php echo $name_get ?></h4>
                                                <p class="font-13 text-light">Joing Date: <?php echo date('M d Y', strtotime(str_replace('/', '-', $joining_date_get))) ?></p>
                                                <p class="text-light mb-0">Mobile: <?php echo $mobile_get ?></p>
                                            </div>
                                        </div>
                                        <div class="col-sm-6">
                                            <div class="text-left">
												<p class="text-light mb-0">Iqama No.: <?php echo $iqama_get ?></p>
												<p class="text-light mb-0">Employee No.: <?php echo $emp_id_get ?></p>
												<?php if($emp_status_get == "no" ){?>
												<p class="text-light mb-0">
													<?php echo $note_get." Date: ".date('d M Y', strtotime($emp_ter_date_get)); ?>
												</p>
												<?php } ?>
                                            </div>
											<?php if($emp_status_get == "active" ){?>
											<div class="text-right">
												<a href="javascript:void(0);" class="btn btn-sm btn-danger waves-effect" data-toggle="modal" data-target=".terminat" >
                                                    <i class="mdi mdi-account-off"></i> Terminat
                                                </a>
                                            </div>
											<?php } ?>
                                        </div>
                                    </div>
                                </div>
                                <!--/ meta -->
                            </div>
                        </div>
                        <!-- end row -->

                        <div class="row">
                            <div class="col-md-12">
                                <div class="card-box">

                                    <h4 class="m-t-0 header-title">Edit Vac</h4>
                                    <form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post" enctype="multipart/form-data">
										<?php echo $error_1 ?>
                                        <div class="form-row">
											<div class="form-group col-md-6">
												<label for="name">Employee Name<span class="text-danger">*</span></label>
												<input type="text" name="name" value="<?php echo $permit_no_get_v ?>" class="form-control" id="name" autofocus>
												</div>
                                            <div class="form-group col-md-6">
												<label for="emp_id">Employee ID.<span class="text-danger">*</span></label>
												<input type="text" name="emp_id" value="<?php echo $emp_id_get ?>" class="form-control" id="emp_id">
                                        	</div>
                                        </div>
										<div class="form-row">
                                            <div class="form-group col-md-6">
                                                <label for="iqama" class="col-form-label">Iqama</label>
                                                <input type="text" name="iqama" value="<?php echo $iqama_get ?>" data-mask="9999999999" class="form-control" id="iqama">
                                            </div>
                                            <div class="form-group col-md-6">
                                                <label for="mobile" class="col-form-label">Mobile No.</label>
                                                <input type="text" name="mobile" value="<?php echo $mobile_get ?>" data-mask="0599999999" class="form-control" id="mobile">
                                            </div>
                                        </div>
										<div class="form-row">
											<div class="form-group col-md-6">
												<label for="avatar" class="col-form-label"><?php echo $image_txt ?></label>
												<input type="file" class="filestyle" name="avatar" data-btnClass="btn-primary">
                                            </div>
											<div class="form-group col-md-3">
                                                <label for="salary" class="col-form-label">Salary</label>
                                                <input type="text" name="salary" value="<?php echo $salary_get ?>" class="form-control autonumber" data-v-max="20000" data-v-min="0" id="salary">
                                            </div>
                                            <div class="form-group col-md-3">
                                                <label for="vacation_days" class="col-form-label">Vacation Days</label>
                                                <input type="text" name="vacation_days" 
                                                   class="form-control autonumber" data-v-max="180" data-v-min="0" id="vacation_days" value="<?php echo $vacation_days_get ?>">
                                            </div>
                                        </div>

										<div class="btn-group" role="group" aria-label="Edit Button">
                                        
										<a href="view_employee.php?id=<?php echo $_GET['id']; ?>" class="btn btn-dark"><i class="fa fa-angle-double-left"></i> Back</a>
                                        <button type="submit" name="submit" class="btn btn-primary"><i class="mdi mdi-account-edit"></i> Save Edit</button>
											
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
<div class="modal fade terminat" tabindex="-1" role="dialog" aria-labelledby="mySmallModalLabel" aria-hidden="true" style="display: none;">
	<div class="modal-dialog modal-dialog-centered">
		<div class="modal-content">
			<div class="modal-header" style="background-color: brown !important; color: #fff !important;">
				<button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
				<h4 class="modal-title" id="mySmallModalLabel">
					<i class="mdi mdi-delete-circle"></i> 
					Are you sure!
				</h4>
			</div>
			<div class="modal-body">
				<h3>You need to Terminat!</h3>
				<h4><strong style="font-size: 30px; "><?php echo $name_get ?></strong></h4>
<div class="form-row" id="content" style="display:none;">
<form action="./includes/terminat_emp.php" method="get">
<!--	<a href="" class="btn btn-danger waves-effect waves-light" ><i class="mdi mdi-account-off"></i> Terminat</a>-->
<input type="hidden" name="id" value="<?php echo $id_get ?>" >
<input type="hidden" name="note" value="terminat" >
	<div class="input-group">
		<input type="text" id="ter_note" name="ter_note" class="form-control" aria-describedby="basic-addon2">
		<div class="input-group-append">
		<button type="submit" class="btn btn-danger waves-effect waves-light"><i class="mdi mdi-account-off"></i> Terminat Submit</button>
		</div>
	</div>
</form>
</div>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-dark waves-effect" data-dismiss="modal">Close</button>
				<a href="./includes/terminat_emp.php?id=<?php echo $id_get ?>&note=expired" class="btn btn-light waves-effect waves-light"><i class="mdi mdi-account-star"></i> Expired</a>
				<button type="button" id="terminat_emp" class="btn btn-danger waves-effect waves-light"><i class="mdi mdi-account-off"></i> Terminat</button>			
				
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
	/***************************/

	jQuery('#terminat_emp').on('click', function(event) {  
	   $("#ter_note").attr('required', '');
		jQuery('#content').toggle('show');
	});

</script>

    </body>
</html>