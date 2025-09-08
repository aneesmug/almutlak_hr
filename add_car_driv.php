<?php
	require_once __DIR__ . '/includes/db.php';
	require_once __DIR__ . '/includes/session_check.php';
	$query = mysqli_query($conDB, "SELECT * FROM `admin_login` WHERE `id_iqama`='".$username."'");
	if(mysqli_num_rows($query) == 1){
	include("./includes/avatar_select.php");

$getquery = mysqli_query($conDB, "SELECT * FROM `cars` WHERE `id`='".$_GET['id']."' ");

	if(mysqli_num_rows($getquery) !== 0){
		while($rec = mysqli_fetch_assoc($getquery)){
			$id_car = $rec["id"];
			$maker_name = $rec["maker_name"];
			$model = $rec["model"];
			$made_year = $rec["made_year"];
			$plate_no = $rec["plate_no"];
			$type = $rec["type"];
			$status = $rec["status"];
			$remarks = $rec["remarks"];
			$datereg = $rec["date_reg"];

		//	$times_reg = strtotime("$date_emp");
		//	$datevac = date('d, M Y', $times_reg);

			$timestamp_reg = strtotime("$datereg");
			$date_reg = date('d, M Y', $timestamp_reg);
	}
		
	if(isset($_POST['submit'])){
		
		$drv_name_up = $_POST['drv_name'];
		$rcv_date_up = $_POST['rcv_date'];
		$rtn_date_up = $_POST['rtn_date'];
		$date_reg_up = date("c");
		$status_up = "yes";


	if($drv_name_up){
		$query = "INSERT INTO `cars_drv` (`car_id`, `drv_name`, `rcv_date`, `rtn_date`, `date_reg`, `status`) VALUES ('".$_GET['id']."', '".$drv_name_up."', '".$rcv_date_up."', '".$rtn_date_up."', '".$date_reg_up."', '".$status_up."')";
		mysqli_query($conDB, $query);
		/************log************/
		mysqli_query($conDB, "INSERT INTO `activity_log` (`user_editor`,`page`,`pg_id`,`reg_date`) VALUES ('".$_COOKIE['user']."','".$pgname."','".$_POST['drv_name']."','".date("c")."')") or die (mysqli_error($conDB));
		/************log************/
		$msg = "<div class=\"alert alert-success bg-success text-white border-0\" role=\"alert\">Add Seccssfully!</div>
		";		
		header( "refresh:1 ; url= ./view_car.php?id=".$_GET['id']." " );
	} else {
		$msg = "<div class=\"alert alert-danger bg-danger text-white border-0\" role=\"alert\">Please fill out the form!</div>";
	}

	
}

} else {
		//when the id not equals id show database
		header("Location: ./all_cars.php");
//		./view_car.php?id=".$_GET['id']."
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
		<link href="./plugins/bootstrap-tagsinput/css/bootstrap-tagsinput.css" rel="stylesheet" />
        <link href="./plugins/bootstrap-select/css/bootstrap-select.min.css" rel="stylesheet" />
        <link href="./plugins/select2/css/select2.min.css" rel="stylesheet" type="text/css" />
        <link rel="stylesheet" href="./plugins/switchery/switchery.min.css" />
		<!-- DataTables -->
        <link href="./plugins/datatables/dataTables.bootstrap4.min.css" rel="stylesheet" type="text/css" />
        <link href="./plugins/datatables/buttons.bootstrap4.min.css" rel="stylesheet" type="text/css" />
        <!-- Responsive datatable examples -->
        <link href="./plugins/datatables/responsive.bootstrap4.min.css" rel="stylesheet" type="text/css" />

        <!-- Multi Item Selection examples -->
        <link href="./plugins/datatables/select.bootstrap4.min.css" rel="stylesheet" type="text/css" />

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
                            <div class="col-xl-12">
                                <!-- meta -->
                                <div class="profile-user-box card-box bg-custom">
                                    <div class="row">
                                        <div class="col-sm-6">
                                            <div class="media-body text-white">
                                                <h4 class="mt-1 mb-1 font-18">Maker Name: <?php echo $maker_name ?></h4>
                                                <p class="font-13 text-light">Model: <?php echo $model ?></p>
                                                <p class="text-light mb-0">Made Year: <?php echo $made_year ?></p>
                                                <p class="font-13 text-light">Remarks: <?php echo $remarks ?></p>
                                            </div>
                                        </div>
                                        <div class="col-sm-6">
                                            <div class="text-left text-white">
												<h4 class="mt-1 mb-1 font-18">Plate No: <?php echo $plate_no ?></h4>
                                                <p class="font-13 text-light">Type: <?php echo $type ?></p>
                                                <p class="text-light mb-0">Date Reg.: <?php echo $date_reg ?></p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <!--/ meta -->

                            </div>
							
							
                        </div>

						
				<div class="row">
				<div class="col-md-12">
					<div class="card-box" style="height: 300px;">
						<h4 class="m-t-0 header-title">Register New Car Licence</h4>
						<form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post">
							<?php echo $msg ?>
							<div class="form-row">
								<div class="form-group col-md-6">
									<label for="drv_name" class="col-form-label">Enter Car Driver Name<span class="text-danger">*</span></label>
									<input type="text" name="drv_name" required placeholder="Driver name" class="form-control" id="drv_name">
								</div>								
								
							</div>
							<div class="form-row">							
								<div class="form-group col-md-6">
									<label for="rcv_date">Receive Date<span class="text-danger">*</span></label>
									<input type="text" name="rcv_date" parsley-trigger="change" required placeholder="Select issue date" class="form-control" id="rcv_date"  autocomplete="off">
								</div>
							</div>
							<div class="btn-group" role="group" aria-label="Edit Button">
							<a href="./view_car.php?id=<?php echo $_GET['id'] ?>" class="btn btn-dark"><i class="fa fa-angle-double-left"></i> Back</a>
							<button type="submit" name="submit" class="btn btn-primary"><i class="mdi mdi-car"></i> Register</button>
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
<!--		<script src="./plugins/bootstrap-inputmask/bootstrap-inputmask.min.js" type="text/javascript"></script>-->
		<script src="./plugins/bootstrap-inputmask/jquery.inputmask.min.js" type="text/javascript"></script>
<!--		<script src="https://cdn.jsdelivr.net/gh/RobinHerbots/jquery.inputmask@5.0.0-beta.87/dist/jquery.inputmask.min.js" type="text/javascript"></script>-->
		
        <script src="./plugins/select2/js/select2.min.js" type="text/javascript"></script>
        <script src="./plugins/bootstrap-select/js/bootstrap-select.js" type="text/javascript"></script>
		
		<script src="./plugins/moment/moment.js"></script>
        <script src="./plugins/bootstrap-timepicker/bootstrap-timepicker.js"></script>
        <script src="./plugins/bootstrap-colorpicker/js/bootstrap-colorpicker.min.js"></script>
        <script src="./plugins/clockpicker/js/bootstrap-clockpicker.min.js"></script>
        <script src="./plugins/bootstrap-daterangepicker/daterangepicker.js"></script>
        <script src="./plugins/bootstrap-datepicker/js/bootstrap-datepicker.min.js"></script>
		
        <script src="./plugins/bootstrap-filestyle/js/bootstrap-filestyle.min.js" type="text/javascript"></script>

        <!-- App js -->
		<script src="assets/pages/jquery.form-pickers.init.js"></script>
		
		
       	<script type="text/javascript" src="./plugins/autocomplete/jquery.mockjax.js"></script>
        <script type="text/javascript" src="./plugins/autocomplete/jquery.autocomplete.min.js"></script>
        <script type="text/javascript" src="./plugins/autocomplete/countries.js"></script>
        <!-- App js -->
		<script src="assets/pages/jquery.form-pickers.init.js"></script>
        <script type="text/javascript" src="assets/pages/jquery.form-advanced.init.js"></script>
		

        <!-- App js -->
        <script src="assets/js/jquery.core.js"></script>
        <script src="assets/js/jquery.app.js"></script>

<script type="text/javascript">
	$(document).ready(function() {
		$('form').parsley();
	});
$(function () {
    'use strict';

<?php
$query_emp = mysqli_query($conDB, "SELECT * FROM `employees` ORDER BY `name` REGEXP '^[a-z]' DESC, name");
?>
	var nhlTeams_mn = [
<?php
	while($rec = mysqli_fetch_assoc($query_emp)){
		$drv_name = $rec["name"];
		echo "'".$drv_name."',";
}
?>
	];
    var nhl_mn = $.map(nhlTeams_mn, function (team_mn) { return { value: team_mn}; });
    var teams_mn = nhl_mn.concat();
    // Initialize autocomplete with local lookup:

    $('#drv_name').devbridgeAutocomplete({
        lookup: teams_mn,
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
<?php } ?>