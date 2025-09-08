<?php
	require_once __DIR__ . '/includes/db.php';

	require_once __DIR__ . '/includes/session_check.php';
	$query = mysqli_query($conDB, "SELECT * FROM `admin_login` WHERE `id_iqama`='".$username."'");
		if(mysqli_num_rows($query) == 1){
		include("./includes/avatar_select.php");
	}

$getquery = mysqli_query($conDB, "SELECT * FROM `cars` WHERE `id`='".$_GET['id']."' ");

	if(mysqli_num_rows($getquery) !== 0){
		while($rec = mysqli_fetch_assoc($getquery)){
			$id_get = $rec["id"];
			$maker_name_get = $rec["maker_name"];
			$model_get = $rec["model"];
			$made_year_get = $rec["made_year"];
			$plate_no_get = $rec["plate_no"];
			$type_get = $rec["type"];
			$status_get = $rec["status"];
			$remarks_get = $rec["remarks"];
			$date_reg_get = $rec["date_reg"];
			
//			$salary_get = str_replace(',', '', $salary_get);
	}	
} else {
		//when the id not equals id show database
		header( "refresh:1 ; url= ./all_cars.php" );
	}

if(isset($_POST['submit'])){

	$maker_name_up = mysqli_real_escape_string($conDB, $_POST['maker_name']);
	$model_up = htmlentities($_POST['model']);
	$made_year_up = htmlentities($_POST['made_year']);
	$plate_no_up = htmlentities($_POST['plate_no']);
	$type_up = mysqli_real_escape_string($conDB, $_POST['type']);
	$remarks_up = htmlentities($_POST['remarks']);
	
	
$u = "UPDATE `cars` SET `maker_name`='".$maker_name_up."', `model`='".$model_up."', `made_year`='".$made_year_up."', `plate_no`='".$plate_no_up."', `type`='".$type_up."', `remarks`='".$remarks_up."' WHERE `id`='".$_GET['id']."' ";
		mysqli_query($conDB, $u) or die (mysqli_error());
		
		/************log************/
		mysqli_query($conDB, "INSERT INTO `activity_log` (`user_editor`,`page`,`pg_id`,`reg_date`) VALUES ('".$_COOKIE['user']."','".$pgname."','".$_GET['id']."','".date("c")."')") or die (mysqli_error());
		/************log************/
		$error_1 = "<div class='alert alert-success bg-success text-white border-0'><strong>Successfully!</strong> Your car details will be successfully edit!</div>";
		header( "refresh:1 ; url= ./view_car.php?id=".$_GET['id']." " );
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
						<h4 class="m-t-0 header-title">Register New Car</h4>
						<form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post">
							<?php echo $error_1 ?>
							<div class="form-row">
								<div class="form-group col-md-6">
									<label for="maker_name">Maker Name<span class="text-danger">*</span></label>
									<input type="text" name="maker_name" required placeholder="Enter maker name" class="form-control" id="maker_name" value="<?php echo $maker_name_get ?>" >
								</div>
								<div class="form-group col-md-6">
									<label for="model">Model<span class="text-danger">*</span></label>
									<input type="text" name="model" required placeholder="Enter model" class="form-control" id="model" value="<?php echo $model_get ?>" >
								</div>
							</div>
							<div class="form-row">
								<div class="form-group col-md-3">
									<label for="made_year" class="col-form-label">Made Year<span class="text-danger">*</span></label>
									<input type="text" name="made_year" placeholder="Enter made year" class="form-control" id="made_year" required value="<?php echo $made_year_get ?>" >
								</div>
								<div class="form-group col-md-3">
									<label for="plate_no" class="col-form-label">Plate No.<span class="text-danger">*</span></label>
									<input type="text" name="plate_no" placeholder="1234-ABC" class="form-control" id="plate_no" required autocomplete="off" style="text-transform: uppercase !important;" value="<?php echo $plate_no_get ?>" >
								</div>
								<div class="form-group col-md-3">
									<label for="type" class="col-form-label">Type of car<span class="text-danger">*</span></label>
									<select class="form-control selectpicker" data-live-search="true" data-style="btn-custom" name="type" required>
										<option value="<?php echo $type_get ?>" ><?php echo $type_get ?></option>
										<option value="">Select</option>
										<option value="Bus">Bus</option>
										<option value="Car">Car</option>
										<option value="Dyna">Dyna</option>
										<option value="Fork Lift">Fork Lift</option>
										<option value="Jeep">Jeep</option>
										<option value="Pick Up">Pick Up</option>
										<option value="Truck">Truck</option>
										<option value="Van">Van</option>
									</select>
								</div>
								<div class="form-group col-md-3">
									<label for="remarks" class="col-form-label">Remarks</label>
									<input type="text" name="remarks" placeholder="Enter remarks" class="form-control" id="remarks" value="<?php echo $remarks_get ?>">
								</div>
							</div>
							<div class="btn-group" role="group" aria-label="Edit Button">
							<a href="./view_car.php?id=<?php echo $_GET['id']; ?>" class="btn btn-dark"><i class="fa fa-angle-double-left"></i> Back</a>
							<button type="submit" name="submit" class="btn btn-success"><i class="mdi mdi-car"></i> Edit Save</button>
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
		
        <script src="assets/js/jquery.core.js"></script>
        <script src="assets/js/jquery.app.js"></script>
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
	$sql_maker_name = "SELECT * FROM `cars` GROUP BY `maker_name`";
	$query_maker_name = mysqli_query($conDB, $sql_maker_name);
?>
	var nhlTeams_mn = [
<?php
	while($rec = mysqli_fetch_assoc($query_maker_name)){
		$makername = $rec["maker_name"];
		echo "'".$makername."',";
}
?>
	];
    var nhl_mn = $.map(nhlTeams_mn, function (team_mn) { return { value: team_mn}; });
    var teams_mn = nhl_mn.concat();
    // Initialize autocomplete with local lookup:

    $('#maker_name').devbridgeAutocomplete({
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
	$sql_model = "SELECT * FROM `cars` GROUP BY `model`";
	$query_model = mysqli_query($conDB, $sql_model);
?>
	var nhlTeams_md = [
<?php
	while($rec = mysqli_fetch_assoc($query_model)){
		$modelcr = $rec["model"];
		echo "'".$modelcr."',";
}
?>
	];
    var nhl_md = $.map(nhlTeams_md, function (team_md) { return { value: team_md}; });
    var teams_md = nhl_md.concat();
    // Initialize autocomplete with local lookup:
    $('#model').devbridgeAutocomplete({
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