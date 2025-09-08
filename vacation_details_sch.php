<?php
	require_once __DIR__ . '/includes/db.php';
	
$sql = "SELECT * FROM `employees` WHERE `emp_id`='".$_GET['emp_id']."' ";
	$getquery = mysqli_query($conDB, $sql);
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
			$dept_get = $rec["dept"];
			$fly_get = $rec["fly"];
			$emptype_get = $rec["emptype"];
			$sectin_nme_get = $rec["sectin_nme"];
			$bank_name_get = $rec["bank_name"];
			$iban_get = $rec["iban"];
			$country_get = $rec["country"];
			$dob_get = $rec["dob"];
			$vac_period_get = $rec["vac_period"];
			$sex_get = $rec["sex"];
			$mar_status_get = $rec["mar_status"];
			
			$salary_get = str_replace(',', '', $salary_get);
	}
		
	if($emp_status_get == "no" && $note_get == "expired"){
		$note_get = "Expired";
	} elseif($emp_status_get == "no" && $note_get == "terminat"){
		$note_get = "Terminated";
	}
		
	$getqueryaply = mysqli_query($conDB, "SELECT * FROM `apply_vac_dep` WHERE `emp_id`='".$emp_id_get."' AND `review`='A' ORDER BY `id` DESC ");
		while($recaply = mysqli_fetch_assoc($getqueryaply)){
			$id_vac_get = $recaply["id"];
			$emp_id_vac_get = $recaply["emp_id"];
			$status_vac_get = $recaply["status"];
			$review_vac_get = $recaply["review"];
			$vac_type_vac_get = $recaply["vac_type"];
			
		}
	$getquerylgn = mysqli_query($conDB, "SELECT * FROM `admin_login` WHERE `emp_id`='".$emp_id_get."' ");
		while($recaply = mysqli_fetch_assoc($getquerylgn)){
			$emp_id_lgn_get = $recaply["emp_id"];
			$dept_lgn_get = $recaply["dept"];;
		}
	$getqueryempvac = mysqli_query($conDB, "SELECT * FROM `emp_vacation` WHERE `emp_id`='".$emp_id_get."' AND `review`='A' ORDER BY `id` DESC ");
		while($recempvac = mysqli_fetch_assoc($getqueryempvac)){
			$id_empvac_get = $recempvac["id"];
			$emp_id_empvac_get = $recempvac["emp_id"];
			$note_empvac_get = $recempvac["note"];
			$review_empvac_get = $recempvac["review"];
		}
	$getqueryslry = mysqli_query($conDB, "SELECT * FROM `salary_emp` WHERE `emp_id`='".$emp_id_get."' ORDER BY `id` DESC LIMIT 1 ");
		while($recslry = mysqli_fetch_assoc($getqueryslry)){
			$emp_id_slry_get = $recslry["emp_id"];
			$basic_slry_get = $recslry["basic"];;
			$housing_slry_get = $recslry["housing"];;
			$transport_slry_get = $recslry["transport"];;
			$other_slry_get = $recslry["other_pay"];;
		}
	$getquerygosi = mysqli_query($conDB, "SELECT * FROM `gosi_emp` WHERE `emp_id`='".$emp_id_get."' ORDER BY `id` DESC LIMIT 1 ");
		while($recgosi = mysqli_fetch_assoc($getquerygosi)){
			$gosi_no_get = $recgosi["gosi_no"];
			$amount_gosi_get = $recgosi["amount"];;
			$date_greg_get = $recgosi["date_greg"];;
			$date_hijri_get = $recgosi["date_hijri"];;
		}

$sqlvacget = mysqli_query($conDB, "SELECT * FROM `apply_vac_dep` WHERE `emp_id`='".$emp_id_get."' ");

while ($recvac = mysqli_fetch_array($sqlvacget)) {
	$id = $recvac["id"];
	$vac_strt_datevac = $recvac["vac_strt_date"];
	$return_datevac = $recvac["return_date"];
	$ticket_payvac = $recvac["ticket_pay"];
	$permit_feevac = $recvac["permit_fee"];
	$last_vac_datevac = $recvac["last_vac_date"];
	$next_vac_datevac = $recvac["next_vac_date"];
	$hr_notevac = $recvac["hr_note"];
	$empgid = $recvac["empgid"];

}

if(isset($_POST['submit'])){
	$vac_strt_date_po = $_POST['vac_strt_date'];
	$return_date_po = $_POST['return_date'];
	$next_vac_d = $_POST['next_vac_date'];
//	
	$text = str_replace('/', '-', $vac_strt_date_po);
	$nextvacdatepo = date('d/m/Y', strtotime($text.' + ' .$vac_period_get));
	$next_vac_date_po = str_replace('-', '/', $nextvacdatepo);
//	
	$note_po = $_POST['hr_note'];
	$replacement_per_po = $_POST['replacement_per'];
	
	
	/*********************************/
//	if(empty($_POST['return_date']) ){
//		$vacdays = "N/A";
//		$return_date = "N/A";
//		$permit_no = "N/A";
//	} elseif(empty($_POST['permit_no'])){
//		$return_date = $_POST['return_date'];
//		$permit_no = "N/A";
//		$flydatetime = strtotime(date('M d Y', strtotime(date('M d Y', strtotime(str_replace('/', '-', $date))))));
//		$returndatetime = strtotime(date('M d Y', strtotime(date('M d Y', strtotime(str_replace('/', '-', $return_date))))));	
//		$secs = $returndatetime - $flydatetime;// == <seconds between the two times>
//		$vacdays = $secs / 86400;
//	} else {
//		$return_date = $_POST['return_date'];
//		$permit_no = $_POST['permit_no'];
////		$flydatetime = "01/03/2019";
////		$returndatetime = "30/04/2019";
//		$flydatetime = strtotime(date('M d Y', strtotime(date('M d Y', strtotime(str_replace('/', '-', $date))))));
//		$returndatetime = strtotime(date('M d Y', strtotime(date('M d Y', strtotime(str_replace('/', '-', $return_date))))));	
//		$secs = $returndatetime - $flydatetime;// == <seconds between the two times>
//		$vacdays = $secs / 86400;
//	}
	/*********************************/
	
	if($vac_strt_date_po && $replacement_per_po){
		
		mysqli_query($conDB, "INSERT INTO `vac_sch` (`emp_id`,`name`,`dept`,`replacement_per`,`vac_strt_date`,`last_vac_date`,`next_vac_date`,`note`,`vacation_days`,`date_reg`) VALUES ('".$emp_id_get."','".$name_get."','".$dept_get."','".$replacement_per_po."','".$vac_strt_date_po."','".$last_vac_datevac."','".$next_vac_date_po."','".$note_po."','".$vacation_days_get."','".date("c")."' )") or die (mysqli_error());
		
//		mysqli_query($conDB, "UPDATE `employees` SET `fly`='yes' WHERE `emp_id`='".$_GET['emp_id']."' ") or die (mysqli_error());
		/************log************/
		mysqli_query($conDB, "INSERT INTO `activity_log` (`user_editor`,`page`,`pg_id`,`reg_date`) VALUES ('".$_COOKIE['user']."','".$pgname."','".$_GET['id']."','".date("c")."')") or die (mysqli_error());
		/************log************/
		$msg = "<div class=\"alert alert-success bg-success text-white border-0\" role=\"alert\">Added Seccssfully!</div>
		";		
		header( "Location: vacation_sch_completed.php " );
//		header( "refresh:3 ; url= ./view_employee.php?id=".$empgid." " );
		
//		header( "refresh:1 ; url=./search.php?search=$_GET[emp_id]" );
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
		
		<link href="./plugins/select2/css/select2.min.css" rel="stylesheet" type="text/css" />
		
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
            
            <div class="content-page" style="margin-right: 240px !important;">
				
                <!-- Top Bar Start -->
                <?php 
					$queryExecuted = mysqli_query($conDB, "SELECT * FROM `employees` WHERE `emp_id`='".$_GET['emp_id']."'");
					if(mysqli_num_rows($queryExecuted) > 0){
						
					$queryCHK = mysqli_query($conDB, "SELECT * FROM `vac_sch` WHERE `emp_id`='".$_GET['emp_id']."' ");
					if(mysqli_num_rows($queryCHK) == 0){
				?>
				 <div class="topbar">

	<nav class="navbar-custom">
		<ul class="list-inline menu-left mb-0">
			<li class="float-left">
				<button class="button-menu-mobile open-left disable-btn">
					<i class="dripicons-menu"></i>
				</button>
			</li>
			<li>
				<div class="page-title-box">
					<h4 class="page-title">Human Resource</h4>
					<ol class="breadcrumb">
						<li class="breadcrumb-item active">Welcome to Mochachino Admin Panel !</li>
					</ol>
				</div>
			</li>

		</ul>

	</nav>

</div>
<!-- /.modal -->
                <!-- Top Bar End -->


                <!-- Start Page content -->
                <div class="content">
                    <div class="container-fluid">
						<?php
	$current_page_name = basename($_SERVER['PHP_SELF']);
?>
<div class="row">
<div class="col-xl-12">
	<!-- meta -->
	<div class="profile-user-box card-box <?php if($emp_status_get == "active" AND $fly_get == "no"){echo "bg-dark";}elseif($fly_get == "yes"){echo "bg-warning";}else{echo "bg-danger";}?>">
		<div class="row">
			<div class="col-sm-1">
				<span class="float-left mr-3"><img src="<?php echo $emp_avatar_get ?>" alt="<?php echo $name_get ?>" class="thumb-lg rounded-circle"></span>
			</div>
			<div class="col-sm-5">

				<div class="media-body text-white">
					<h4 class="mt-1 mb-1 font-18">Name: <?php echo $name_get ?></h4>
					<p class="text-light mb-0">Joing Date: <?php echo date('M d Y', strtotime(str_replace('/', '-', $joining_date_get))) ?></p>
					<p class="text-light mb-0">Mobile: <?php echo $mobile_get ?></p>
					<p class="text-light mb-0">Vacation Days: <?php echo $vacation_days_get ?></p>
				</div>
			</div>
			<div class="col-sm-6">
				<div class="text-left">
					<p class="text-light mb-0">
						<?php if($country_get == "Saudi Arabia"){echo "ID. No.: ".$iqama_get;}else{echo "Iqama No.: ".$iqama_get;}?>
					</p>
					<p class="text-light mb-0">Employee No.: <?php echo $emp_id_get ?></p>
					<p class="text-light mb-0">Department: <?php echo $dept_get ?></p>
					<p class="text-light mb-0">Nationality: <?php echo $country_get ?></p>
					<p class="text-light mb-0">Balance Vacations: <?php echo $vacation_days_get - $emp_vacdays_get ?> Days</p>
					<?php if($emp_status_get == "no" ){?>
					<p class="text-light mb-0">
						<?php echo $note_get." Date: ".date('d M Y', strtotime($emp_ter_date_get)); ?>
					</p>
					<?php } ?>
				</div>

			</div>
		</div>
		
	</div>
	<!--/ meta -->

<br>
</div>
</div>
                        <div class="row">
                            <div class="col-md-12">
                                <div class="card-box">
                                    <h4 class="m-t-0 header-title">Add Vacation</h4>
                                    <form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post">
										<?php echo $msg; ?>
                                        <div class="form-row">
											<div class="form-group col-md-3">
                                                <label for="date_select" class="col-form-label">Vacation Date<span class="text-danger">*</span></label>
                                                <input type="text" name="vac_strt_date" parsley-trigger="change" required placeholder="dd/mm/yyyy" class="form-control" id="return_date_v" autocomplete="off" />
                                            </div>
                                            
                                            <div class="form-group col-md-3">
                                                <label for="last_vac_date" class="col-form-label">Last Vacation Date </label>
                                                <input type="text" name="last_vac_date" class="form-control" value="<?php echo $last_vac_datevac ?>" readonly />
                                            </div>
											
											<div class="form-group col-md-3">
                                                <label for="last_vac_date" class="col-form-label">Replacement Person<span class="text-danger">*</span></label>
                                            <?php
												$sqlcodes = "SELECT * FROM `employees` WHERE `emp_sup_type`='mocha' AND `note`!='terminat' AND `dept`<>'' ORDER BY `dept` ASC";
    											$resultcodes = mysqli_query($conDB, $sqlcodes);
												echo "<select class='form-control select2' name='replacement_per' required>";
												echo "<option value=''>Select a Person...</option>";
												if ($resultcodes->num_rows > 0) {
												while($row = $resultcodes->fetch_assoc()) {
													 $group[$row['dept']][] = $row;
												}
												 foreach ($group as $key => $values){
													 echo '<optgroup label="'.$key.'">';
													 foreach ($values as $value) 
													 {
														 echo '<option value="'.$value['emp_id'].'">'.$value['emp_id'].' - '.$value['name'].'</option>';
													 }
													 echo '</optgroup>';
												 }
												} else {}
												echo "</select>";
											?>
												
                                            </div>
											
                                            <div class="form-group col-md-3">
                                                <label for="note" class="col-form-label">Notes</label>
                                                <input type="text" name="hr_note" parsley-trigger="change" class="form-control" id="note"  autocomplete="off" />
                                            </div>
											
                                        </div>
										<div class="btn-group" role="group" aria-label="Edit Button">
										<a href="vacation_sch.php" class="btn btn-dark"><i class="fa fa-angle-double-left"></i> Back</a>
                                        <button type="submit" name="submit" class="btn btn-primary"><i class="mdi mdi-table-edit"></i> Apply Application</button>
										</div>
                                    </form>

                                </div>
                            </div>
                        </div>						

                    </div> <!-- container -->

                </div> <!-- content -->
				<?php } else { ?>
				<div class="content">
                    <div class="container-fluid">
                        <div class="row">
                            <div class="col-md-12">
                                <div class="card-box">
									
										<div class="col-sm-12 col-xl-12">
											<div class="card-box bg-danger widget-flat border-danger text-white">
												<i class="fi-delete"></i>
												<h1 class="m-b-10">Your <strong>("<?php echo $name_get ?>")</strong> Vacation information registered already!</h1>
											</div>
											<a href="vacation_sch.php" class="btn btn-primary"><i class="fa fa-angle-double-left"></i> Back to main Page</a>
											
										</div>
									
                                </div>
                            </div>
                        </div>						

                    </div> <!-- container -->

                </div>
					<?php } ?>
				<?php } else { ?>
				
				<div class="content">
                    <div class="container-fluid">
                        <div class="row">
                            <div class="col-md-12">
                                <div class="card-box">
									
										<div class="col-sm-12 col-xl-12">
											<div class="card-box bg-danger widget-flat border-danger text-white">
												<i class="fi-delete"></i>
												<h1 class="m-b-10"><?php echo $msg ?></h1>
												<h1 class="m-b-10">Please Enter Correct Mochachino Employee ID. or Contact with Human Resource (HR) Department.</h1>
											</div>
											<a href="vacation_sch.php" class="btn btn-primary"><i class="fa fa-angle-double-left"></i> Back to main Page</a>
											
										</div>
									
                                </div>
                            </div>
                        </div>						

                    </div> <!-- container -->

                </div>
				<?php } ?>
				
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

        <!-- App js -->
		<script src="assets/pages/jquery.form-pickers.init.js"></script>
		
		
		<!-- Required datatable js -->
        <script src="./plugins/datatables/jquery.dataTables.min.js"></script>
        <script src="./plugins/datatables/dataTables.bootstrap4.min.js"></script>
        <!-- Buttons examples -->
        <script src="./plugins/datatables/dataTables.buttons.min.js"></script>
        <script src="./plugins/datatables/buttons.bootstrap4.min.js"></script>
        <script src="./plugins/datatables/jszip.min.js"></script>
        <script src="./plugins/datatables/pdfmake.min.js"></script>
        <script src="./plugins/datatables/vfs_fonts.js"></script>
        <script src="./plugins/datatables/buttons.html5.min.js"></script>
        <script src="./plugins/datatables/buttons.print.min.js"></script>
		
		

        <!-- Key Tables -->
        <script src="./plugins/datatables/dataTables.keyTable.min.js"></script>

        <!-- Responsive examples -->
        <script src="./plugins/datatables/dataTables.responsive.min.js"></script>
        <script src="./plugins/datatables/responsive.bootstrap4.min.js"></script>

        <!-- Selection table -->
        <script src="./plugins/datatables/dataTables.select.min.js"></script>
		
		<!-- Init Js file -->
		<script src="./plugins/switchery/switchery.min.js"></script>
        <script src="./plugins/bootstrap-tagsinput/js/bootstrap-tagsinput.min.js"></script>
        <script src="./plugins/select2/js/select2.min.js" type="text/javascript"></script>
        <script src="./plugins/bootstrap-select/js/bootstrap-select.js" type="text/javascript"></script>
        <script src="./plugins/bootstrap-filestyle/js/bootstrap-filestyle.min.js" type="text/javascript"></script>
        <script src="./plugins/bootstrap-maxlength/bootstrap-maxlength.js" type="text/javascript"></script>

        <script type="text/javascript" src="./plugins/autocomplete/jquery.mockjax.js"></script>
        <script type="text/javascript" src="./plugins/autocomplete/jquery.autocomplete.min.js"></script>
        <script type="text/javascript" src="./plugins/autocomplete/countries.js"></script>
        <script type="text/javascript" src="assets/pages/jquery.autocomplete.init.js"></script>

        <!-- Init Js file -->
        <script type="text/javascript" src="assets/pages/jquery.form-advanced.init.js"></script>
		

        <!-- App js -->
        <script src="assets/js/jquery.core.js"></script>
        <script src="assets/js/jquery.app.js"></script>

    </body>
</html>