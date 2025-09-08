<?php
	require_once __DIR__ . '/includes/db.php';

	require_once __DIR__ . '/includes/session_check.php';
	$query = mysqli_query($conDB, "SELECT * FROM `admin_login` WHERE `id_iqama`='".$username."'");
		if(mysqli_num_rows($query) == 1){
		include("./includes/avatar_select.php");
	}
	
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
	$date_po = $_POST['date'];
	$return_date_po = $_POST['return_date'];
	$ticket_pay_po = $_POST['ticket_pay'];
	$permit_fee_po = $_POST['permit_fee'];
	$last_vac_date_po = $_POST['last_vac_date'];
	$next_vac_date_po = $_POST['next_vac_date'];
	$note_po = $_POST['hr_note'];
	
	
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

	if($date_po){
		
		
		$query = "UPDATE `apply_vac_dep` SET `vac_strt_date`='$date_po', `return_date`='$return_date_po', `last_vac_date`='$last_vac_date_po', `next_vac_date`='$next_vac_date_po', `ticket_pay`='$ticket_pay_po', `permit_fee`='$permit_fee_po', `status`='app_hr', `hr_note`='$note_po' WHERE `id`='".$_GET['id']."' ";
		mysqli_query($conDB, $query);
		
//		mysqli_query($conDB, "UPDATE `employees` SET `fly`='yes' WHERE `emp_id`='".$_GET['emp_id']."' ") or die (mysqli_error());
		/************log************/
		mysqli_query($conDB, "INSERT INTO `activity_log` (`user_editor`,`page`,`pg_id`,`reg_date`) VALUES ('".$_COOKIE['user']."','".$pgname."','".$_GET['id']."','".date("c")."')") or die (mysqli_error());
		/************log************/
		$msg = "<div class=\"alert alert-success bg-success text-white border-0\" role=\"alert\">Updated Seccssfully!</div>
		";		
		header( "refresh:3 ; url= ./view_employee.php?id=".$empgid." " );
		
		
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
            <!-- ============================================================== -->



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
						<?php include("./includes/emp_top_info.php"); ?>
                        <div class="row">
                            <div class="col-md-12">
                                <div class="card-box">
                                    <h4 class="m-t-0 header-title">Add New Vacation</h4>
                                    <form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post">
										<?php echo $msg; ?>
                                        <div class="form-row">
											<div class="form-group col-md-3">
                                                <label for="date_select" class="col-form-label">Vacation Date<span class="text-danger">*</span></label>
                                                <input type="text" name="date" parsley-trigger="change" required placeholder="dd/mm/yyyy" class="form-control" id="date_select" autocomplete="off" value="<?php echo $vac_strt_datevac ?>" />
                                            </div>
                                            <?php if($vac_type_vac_get <> "Encashed"){?>
                                            <div class="form-group col-md-3">
                                                <label for="return_date" class="col-form-label">Return Date<span class="text-danger">*</span></label>
                                                <input type="text" name="return_date" parsley-trigger="change" placeholder="dd/mm/yyyy" class="form-control" id="return_dated" autocomplete="off" required value="<?php echo $return_datevac ?>" />
                                            </div>
                                            
											<?php if($vac_typevac <> 'emergency' && $country_get <> "Myanmar" && $country_get <> "Saudi Arabia"){
												if($note_empvac_get <> "Local Vacation" && $note_empvac_get <> "Encashed"){
											?>
                                            <div class="form-group col-md-3">
                                                <label for="ticket_pay" class="col-form-label">Ticket Allowance<span class="text-danger">*</span></label>
                                                <input type="text" name="ticket_pay" class="form-control" id="ticket_pay" required value="<?php echo $ticket_payvac ?>" />
                                            </div>
                                            <div class="form-group col-md-3">
                                                <label for="permit_fee" class="col-form-label">Exit Re-Entry Permit Fee<span class="text-danger">*</span></label>
                                                <input type="text" name="permit_fee" class="form-control" id="permit_fee" required value="<?php echo $permit_feevac ?>" />
                                            </div>
											<?php } } } ?>
                                            <div class="form-group col-md-3">
                                                <label for="last_vac_date" class="col-form-label">Last Vacation Date </label>
                                                <input type="text" name="last_vac_date" parsley-trigger="change" placeholder="dd/mm/yyyy" class="form-control" id="last_vac_date" autocomplete="off" value="<?php echo $last_vac_datevac ?>" />
                                            </div>
                                            <div class="form-group col-md-3">
                                                <label for="next_vac_date" class="col-form-label">Next Vacation Date<span class="text-danger">*</span></label>
                                                <input type="text" name="next_vac_date" parsley-trigger="change"  placeholder="dd/mm/yyyy" class="form-control" id="next_vac_date" autocomplete="off" required value="<?php echo $next_vac_datevac ?>" />
                                            </div>
                                            <div class="form-group col-md-3">
                                                <label for="note" class="col-form-label">Notes</label>
                                                <input type="text" name="hr_note" parsley-trigger="change" class="form-control" id="note"  autocomplete="off" value="<?php echo $hr_notevac ?>" />
                                            </div>
                                        </div>
										<div class="btn-group" role="group" aria-label="Edit Button">
										<a href="view_employee.php?id=<?php echo $empgid ?>" class="btn btn-dark"><i class="fa fa-angle-double-left"></i> Back</a>
                                        <button type="submit" name="submit" class="btn btn-primary"><i class="mdi mdi-table-edit"></i> Edit Application</button>
										</div>
                                    </form>
                                    <?php
//										echo $flydime = date('M d Y', strtotime(str_replace('/', '-', "26/06/2017")));
//										echo "<br>";
//										echo date('d/m/Y', strtotime($flydime. ' + 3 days'));
									?>
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
		

        <!-- App js -->
        <script src="assets/js/jquery.core.js"></script>
        <script src="assets/js/jquery.app.js"></script>

		<script type="text/javascript">
            $(document).ready(function() {
                $('form').parsley();
				
				//Buttons examples
                var table = $('#employee_vac').DataTable({
                    lengthChange: false,
                    buttons: ['copy', 'excel', 'pdf', 'print'],
					order: [[ 7, "desc" ]],
					"columnDefs": [
									{
									targets: [ 7 ],
									visible: false,
									searchable: false
									},
								],
					});
				
					table.buttons().container()
					.appendTo('#employee_vac_wrapper .col-md-6:eq(0)');
				
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
	
//$(document).ready(function(){
//  $("input[name$='note']").click(function(){	  
//  var value = $(this).val();
//  if(value=='Encashed') {
//    $("#return_date").show();
//    $("#note").hide();
//	$("#note2").hide();
//	$("#return_date").removeAttr('required');
//	$("#permit_no").removeAttr('required');
//  }
//  else if(value=='Fly') {
//	//document.getElementById("pet_id").required = true;
//	$("#return_date").attr('required', '');
//	$("#permit_no").attr('required', '');
//   	$("#note").show();
//	$("#note2").hide();
////    $("#pet_id_box").hide();
//   }
//  else if(value=='Local Vacation') {
//	//document.getElementById("pet_id").required = true;
//	$("#return_date_v").attr('required', '');
//   	$("#note2").show();
//	$("#note").hide();
////    $("#pet_id_box").hide();
//   }
//  });
//  	$("#return_date").removeAttr('required');
//  	$("#return_date_v").removeAttr('required');
////  	$("#pet_id_box").show();
//  	$("#note").hide();
//	$("#note2").hide();
//});
        </script>

    </body>
</html>