<?php
	require_once __DIR__ . '/includes/db.php';

	require_once __DIR__ . '/includes/session_check.php';
	$query = mysqli_query($conDB, "SELECT * FROM `admin_login` WHERE `id_iqama`='".$username."'");
		if(mysqli_num_rows($query) == 1){
		include("./includes/avatar_select.php");
	}

$getquery = mysqli_query($conDB, "SELECT * FROM `machines` WHERE `id`='".$_GET['id']."' ");

	if(mysqli_num_rows($getquery) !== 0){
		while($rec = mysqli_fetch_assoc($getquery)){
			$id_get = $rec["id"];
            $m_id_get = $rec["m_id"];
			$name_mach_get = $rec["name_mach"];
			$maker_name_get = $rec["maker_name"];
			$location_get = $rec["location"];
			$made_year_get = $rec["made_year"];
			$remarks_get = $rec["remarks"];
			$status_get = $rec["status"];
			$serial_get = $rec["serial"];
			$datereg_get = $rec["date_reg"];

		//	$times_reg = strtotime("$date_emp");
		//	$datevac = date('d, M Y', $times_reg);

			$timestamp_reg_get = strtotime("$datereg_get");
			$date_reg_get = date('d, M Y', $timestamp_reg_get);

		$query_mactrny = mysqli_query($conDB, "SELECT * FROM `machine_trans` WHERE `mid`='".$_GET['id']."' ORDER BY `id` DESC LIMIT 1");
            while ($rec = mysqli_fetch_array($query_mactrny)) {
                $new_location_trn = $rec["location"];
                $old_location_trn = $rec["old_location"];
            }

           $old_location = ($new_location_trn == "") ? $location_get : $new_location_trn;

	}

if(isset($_POST['submit'])){
	$name_mach_po = ucwords($_POST['name_mach']);
	$maker_name_po = ucwords($_POST['maker_name']);
	$location_po = $_POST['location'];
	$made_year_po = $_POST['made_year'];
	$remarks_po = $_POST['remarks'];
	$m_id_po = $_POST['m_id'];
	$serial_po = strtoupper($_POST['serial']);
	$date_reg_po = date("c");

	// $querys = mysqli_query($conDB, "SELECT * FROM `machines` WHERE `serial` = '".$serial_po."' ");
	// 		while($row = mysqli_fetch_array($querys)){
	// 			$serialgt=$row['serial'];
	// 			$name_machgt=$row['name_mach'];
	// 			$idgt=$row['id'];
	// 		}
	// 	if($serialgt == $serial_po) { //check if there is already an entry for that appointment
	// 			$msg = "
	// 			<div class=\"alert alert-danger bg-danger text-white border-0\" role=\"alert\"><b>Error ooooh!</b> This serial no. ($serial_po) already registerd. For view record click here. <a href='$idgt' class='btn btn-sm btn-warning waves-effect btn-rounded'>$name_machgt</a></div>
	// 			";
	// 		} else {

$u = "UPDATE `machines` SET `name_mach`='".$name_mach_po."', `maker_name`='".$maker_name_po."', `location`='".$location_po."', `remarks`='".$remarks_po."', `m_id`='".$m_id_po."', `serial`='".$serial_po."', `made_year`='".$made_year_po."' WHERE `id`='".$_GET['id']."' ";


		// $query = "INSERT INTO `machines` (`name_mach`,`maker_name`, `location`, `remarks`,`serial`, `date_reg`, `m_id`, `made_year`) VALUES ('".$name_mach_po."','".$maker_name_po."', '".$location_po."', '".$remarks_po."', '".$serial_po."', '".$date_reg_po."', '".$m_id_get."', '".$made_year_po."')";
		mysqli_query($conDB, $u);
		/************log************/
		// mysqli_query($conDB, "INSERT INTO `activity_log` (`user_editor`,`page`,`pg_id`,`reg_date`) VALUES ('".$_COOKIE['user']."','".$pgname."','".$_POST['maker_name']."','".date("c")."')") or die (mysqli_error());
		/************log************/
		$msg = "<div class=\"alert alert-success bg-success text-white border-0\" role=\"alert\">Add Seccssfully!</div>
		";		
		 header( "refresh:2 ; url=view_machine.php?id=".$_GET['id']." " );
		 // header( "refresh:2" );
//}
}

} else {
		//when the id not equals id show database
		header("Location: ./all_machines.php");
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
						<h4 class="m-t-0 header-title">Register New Machine</h4>
            			<a href="add_brand.php" class="btn btn-primary waves-effect"><i class="mdi mdi-settings"></i> Add Brand</a>	
						<form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post">
							<?php echo $msg ?>
							<div class="form-row">
								<div class="form-group col-md-4">
									<label for="name_mach" class="col-form-label">Machine Name<span class="text-danger">*</span></label>
									<input type="text" name="name_mach" required placeholder="Enter Machine name" class="form-control" id="name_mach" value="<?php echo $name_mach_get ?>">
								</div>
								<div class="form-group col-md-2">
									<label for="m_id" class="col-form-label">Machine ID<span class="text-danger">*</span></label>
									<input type="text" name="m_id" required placeholder="Enter m id" class="form-control" id="m_id" value="<?php echo $m_id_get ?>">
								</div>
								<div class="form-group col-md-3">
									<label for="serial" class="col-form-label">Serial No.<span class="text-danger">*</span></label>
									<input type="text" name="serial" required placeholder="Enter serial no." class="form-control" id="serial" value="<?php echo $serial_get ?>">
								</div>
								<div class="form-group col-md-3">
									<label for="made_year" class="col-form-label">Made Year<span class="text-danger">*</span></label>
									<input type="text" name="made_year" required placeholder="Enter made year" class="form-control" id="made_year" value="<?php echo $made_year_get ?>">
								</div>
								<div class="form-group col-md-4">
									<label for="remarks" class="col-form-label">Remarks</label>
									<input type="text" name="remarks" placeholder="Enter remarks" class="form-control" id="remarks" value="<?php echo $remarks_get ?>">
								</div>
								<div class="form-group col-md-4">
									<label for="maker_name" class="col-form-label">Select Model Name<span class="text-danger">*</span></label>
									<select class="form-control selectpicker" data-live-search="true" data-style="btn-custom" name="maker_name" required>
										<option value="<?php echo $maker_name_get ?>"><?php echo $maker_name_get ?></option>
										<?php

											// echo ($maker_name_get='') ? "<option value=''>Select</option>" : $maker_name_get ;

											$query_brand_nme = mysqli_query($conDB, "SELECT * FROM `brand_name` ORDER BY `name` REGEXP '^[^A-Za-z]' ASC, name");
											while($rec = mysqli_fetch_assoc($query_brand_nme)){
												$brand_name = $rec["name"];
										?>
											<option value="<?php echo $brand_name ?>"><?php echo $brand_name ?></option>
										<?php } ?>
									</select>
								</div>
								<div class="form-group col-md-4">
									<label for="location" class="col-form-label">Select Location<span class="text-danger">*</span></label>
									<select class="form-control selectpicker" data-live-search="true" data-style="btn-custom" name="location" required>
										<option value="<?php echo $old_location ?>"><?php echo $old_location ?></option>
										<?php /* ?>
										<option value="<?php echo ($_SESSION['lastlocation'] != "") ? $_SESSION['lastlocation'] : "" ; ?>">
											<?php echo ($_SESSION['lastlocation'] != "") ? $_SESSION['lastlocation'] : "Select" ; ?>
										</option>
										<?php */ ?>

										<?php

										// echo ($old_location='') ? "<option value=''>Select</option>" : "<option value='$old_location'>$old_location</option>" ;

											$query_sectin_nme = mysqli_query($conDB, "SELECT DISTINCT(section_name) AS `name` FROM `section` ORDER BY `section_name` REGEXP '^[^A-Za-z]' ASC, section_name");
											while($rec = mysqli_fetch_assoc($query_sectin_nme)){
												$sectin_nme = $rec["name"];
										?>
											<option value="<?php echo $sectin_nme ?>"><?php echo $sectin_nme ?></option>
										<?php } ?>
									</select>
								</div>
								
							</div>
							<div class="btn-group" role="group" aria-label="Edit Button">
							<a href="view_machine.php?id=<?php echo $_GET['id']; ?>" class="btn btn-dark"><i class="fa fa-angle-double-left"></i> Back</a>
							<button type="submit" name="submit" class="btn btn-primary"><i class="mdi mdi-settings"></i> Update</button>
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
	$sql_name_mach = "SELECT * FROM `machines` GROUP BY `name_mach`";
	$query_name_mach = mysqli_query($conDB, $sql_name_mach);
?>
	var nhlTeams_mn = [
<?php
	while($rec = mysqli_fetch_assoc($query_name_mach)){
		$namemach = $rec["name_mach"];
		echo "'".$namemach."',";
}
?>
	];
    var nhl_mn = $.map(nhlTeams_mn, function (team_mn) { return { value: team_mn}; });
    var teams_mn = nhl_mn.concat();
    // Initialize autocomplete with local lookup:

    $('#name_mach').devbridgeAutocomplete({
        lookup: teams_mn,
        minChars: 1,
        onSelect: function (suggestion) {
            $('#selection').html('You selected: ' + suggestion.value + ', ' + suggestion.data.category);
        },
        showNoSuggestionNotice: true,
        noSuggestionNotice: 'Sorry, no matching results',
//        groupBy: 'category'
    });
// <?php
// 	$sql_model = "SELECT * FROM `cars` GROUP BY `model`";
// 	$query_model = mysqli_query($conDB, $sql_model);
// ?>
// 	var nhlTeams_md = [
// <?php
// 	while($rec = mysqli_fetch_assoc($query_model)){
// 		$modelcr = $rec["model"];
// 		echo "'".$modelcr."',";
// }
// ?>
// 	];
//     var nhl_md = $.map(nhlTeams_md, function (team_md) { return { value: team_md}; });
//     var teams_md = nhl_md.concat();
//     // Initialize autocomplete with local lookup:
//     $('#model').devbridgeAutocomplete({
//         lookup: teams_md,
//         minChars: 1,
//         onSelect: function (suggestion) {
//             $('#selection').html('You selected: ' + suggestion.value + ', ' + suggestion.data.category);
//         },
//         showNoSuggestionNotice: true,
//         noSuggestionNotice: 'Sorry, no matching results',
// //        groupBy: 'category'
//     });
	
// <?php
// 	$sql_made_year = "SELECT * FROM `cars` GROUP BY `made_year`";
// 	$query_made_year = mysqli_query($conDB, $sql_made_year);
// ?>
// 	var nhlTeams_mdy = [
// <?php
// 	while($rec = mysqli_fetch_assoc($query_made_year)){
// 		$madeyear = $rec["made_year"];
// 		echo "'".$madeyear."',";
// }
// ?>
// 	];
//     var nhl_mdy = $.map(nhlTeams_mdy, function (team_mdy) { return { value: team_mdy}; });
//     var teams_mdy = nhl_mdy.concat();
//     // Initialize autocomplete with local lookup:
//     $('#made_year').devbridgeAutocomplete({
//         lookup: teams_mdy,
//         minChars: 1,
//         onSelect: function (suggestion) {
//             $('#selection').html('You selected: ' + suggestion.value + ', ' + suggestion.data.category);
//         },
//         showNoSuggestionNotice: true,
//         noSuggestionNotice: 'Sorry, no matching results',
// //        groupBy: 'category'
//     });  
	

});

</script>

    </body>
</html>