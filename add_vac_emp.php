<?php
	require_once __DIR__ . '/includes/db.php';

	require_once __DIR__ . '/includes/session_check.php';
	$query = mysqli_query($conDB, "SELECT * FROM `admin_login` WHERE `id_iqama`='".$username."'");
		if(mysqli_num_rows($query) == 1){
		include("./includes/avatar_select.php");
	}
	
        require("./includes/emp_query.php");

        $allRecords = mysqli_fetch_all($get_emp_data, MYSQLI_ASSOC);
		foreach ($allRecords as $rec) {
			$emprow = $rec;
		}

// $sql = "SELECT * FROM `employees` WHERE `id`='".$emprow['empid']."' ";
// 	$getquery = mysqli_query($conDB, $sql);

// while($rec = mysqli_fetch_assoc($getquery)){
// 			$id_get = $rec["id"];
// 			$name_get = $rec["name"];
// 			$emprow['empid'] = $rec["emp_id"];
// 			$iqama_get = $rec["iqama"];
// 			$mobile_get = $rec["mobile"];
// 			$salary_get = $rec["salary"];
// 			$vacation_days_get = $rec["vacation_days"];
// 			$joining_date_get = $rec["joining_date"];
// 			$emp_avatar_get = $rec["avatar"];
// 			$emp_status_get = $rec["status"];
// 			$emp_ter_date_get = $rec["ter_date"];
// 			$note_get = $rec["note"];
// 			$dept_get = $rec["dept"];
// 			$fly_get = $rec["fly"];
// 			$emptype_get = $rec["emptype"];
// 			$sectin_nme_get = $rec["sectin_nme"];
// 			$emprow['country_name'] = $rec["country"];
			
// 			$salary_get = str_replace(',', '', $salary_get);
// 	}

// $getqueryaply = mysqli_query($conDB, "SELECT * FROM `apply_vac_dep` WHERE `emp_id`='".$emprow['empid']."' ORDER BY `id` DESC LIMIT 1  ");
// 	while($recaply = mysqli_fetch_assoc($getqueryaply)){
// 		$emp_id_vac_get = $recaply["emp_id"];
// 		$status_vac_get = $recaply["status"];
// 		$review_vac_get = $recaply["review"];
// 		$date_reg_vac_get = $recaply["date_reg"];
// 		$note_vac_get = $recaply["hr_note"];
// 		$replacement_per_vac_get = $recaply["replacement_per"];
// 		$ticket_pay_vac_get = $recaply["ticket_pay"];
// 		$permit_fee_vac_get = $recaply["permit_fee"];
// 		$emprow['av_vac_strt_date'] = $recaply["vac_strt_date"];
// 		$return_date_vac_get = $recaply["return_date"];
// 		$next_vac_date_vac_get = $recaply["next_vac_date"];
// 		$last_vac_date_vac_get = $recaply["last_vac_date"];
// 		$jion_date_vac_get = $recaply["jion_date"];
// 		$emprow['av_vac_type'] = $recaply["vac_type"];
// 		$emprow['av_fly_type'] = $recaply["fly_type"];

// 		$timestamp_reg = strtotime($date_reg_vac_get);
// 		$date_reg_vac_get = date('d, M Y', $timestamp_reg);

//         //if fly in case emergency days will not count
//         if ($emprow['av_fly_type'] !== 'emergency') {
//     		$flydatetime = strtotime(date('M d Y', strtotime(date('M d Y', strtotime($emprow['av_vac_strt_date'])))));
//     		$returndatetime = strtotime(date('M d Y', strtotime(date('M d Y', strtotime($return_date_vac_get)))));
//     		$secs = $returndatetime - $flydatetime;// == <seconds between the two times>
//     		$vacdays = $secs / 86400;
//         }
    
// 	}

	$getqueryempvac = mysqli_query($conDB, "SELECT * FROM `emp_vacation` WHERE `emp_id`='".$emprow['empid']."' AND `review`='A' ORDER BY `id` DESC ");
		while($recempvac = mysqli_fetch_assoc($getqueryempvac)){
			$id_empvac_get = $recempvac["id"];
			$emp_id_empvac_get = $recempvac["emp_id"];
			$note_empvac_get = $recempvac["note"];
			$review_empvac_get = $recempvac["review"];
		}

if(isset($_POST['submit'])){
	$date = $_POST['date'];
//	$note = $_POST['note'];
	$remarks = $_POST['remarks'];
	$date_reg = date("c");
	/*********************************/
	if(empty($_POST['return_date']) ){
//		$vacdays = "N/A";
		$return_date = "N/A";
		$permit_no = "N/A";
	} elseif(empty($_POST['permit_no'])){
		$return_date = $_POST['return_date'];
		$permit_no = "N/A";
        //if fly in case emergency days will not count
        if ($emprow['av_fly_type'] !== 'emergency') {
    		/*$flydatetime = strtotime(date('M d Y', strtotime(date('M d Y', strtotime(str_replace('/', '-', $date))))));
    		$returndatetime = strtotime(date('M d Y', strtotime(date('M d Y', strtotime(str_replace('/', '-', $return_date))))));*/
    		$flydatetime = strtotime(date('M d Y', strtotime(date('M d Y', strtotime($date)))));
    		$returndatetime = strtotime(date('M d Y', strtotime(date('M d Y', strtotime($return_date)))));
    		$secs = $returndatetime - $flydatetime;// == <seconds between the two times>
    		$vacdays = $secs / 86400;
        }
	} else {
		$return_date = $_POST['return_date'];
		$permit_no = $_POST['permit_no'];
//		$flydatetime = "01/03/2019";
//		$returndatetime = "30/04/2019";
        //if fly in case emergency days will not count
        if ($emprow['av_fly_type'] !== 'emergency') {

    		/*$flydatetime = strtotime(date('M d Y', strtotime(date('M d Y', strtotime(str_replace('/', '-', $date))))));
    		$returndatetime = strtotime(date('M d Y', strtotime(date('M d Y', strtotime(str_replace('/', '-', $return_date))))));*/

    		$flydatetime = strtotime(date('M d Y', strtotime(date('M d Y', strtotime(str_replace('/', '-', $date))))));
			$returndatetime = strtotime(date('M d Y', strtotime(date('M d Y', strtotime(str_replace('/', '-', $return_date))))));	
			$secs = $returndatetime - $flydatetime;// == <seconds between the two times>
			$vacdays = $secs / 86400;
        }
	}
	/*********************************/
	if($date){
		if($emprow['av_vac_type'] == "Encashed"){
        $vacdays = ($emprow['av_vac_type'] == "Encashed" OR $emprow['av_vac_type'] == "Local Vacation")?$emprow['vacation_days']:"";
		$query = "INSERT INTO `emp_vacation` (`emp_id`, `date`, `return_date`, `user_update`, `note`, `date_reg`,`permit_no`,`vacdays`,`remarks`,`review`) VALUES ('".$emprow['empid']."', '".$date."', '".$return_date."', '".$userwel."', '".$emprow['av_vac_type']."', '".$date_reg."', '".$permit_no."', '".$vacdays."', '".$remarks."', 'C')";
			mysqli_query($conDB, "UPDATE `apply_vac_dep` SET `review`='C' WHERE `empgid`='".$emprow['empid']."' AND `emp_id`='".$emprow['empid']."' AND `review`='A' ") or die ();
		}else{
			$query = "INSERT INTO `emp_vacation` (`emp_id`, `date`, `return_date`, `user_update`, `note`, `date_reg`,`permit_no`,`vacdays`,`remarks`,`review`) VALUES ('".$emprow['empid']."', '".$date."', '".$return_date."', '".$userwel."', '".$emprow['av_vac_type']."', '".$date_reg."', '".$permit_no."', '".$vacdays."', '".$remarks."', 'A')";
		}
		mysqli_query($conDB, $query);
		if($emprow['av_vac_type'] <> "Encashed"){
			mysqli_query($conDB, "UPDATE `employees` SET `fly`='yes' WHERE `emp_id`='".$emprow['empid']."' ") or die ();
		}
		/************log************/
		mysqli_query($conDB, "INSERT INTO `activity_log` (`user_editor`,`page`,`pg_id`,`reg_date`) VALUES ('".$_COOKIE['user']."','".$pgname."','".$emprow['empid']."','".date("c")."')") or die ();
		/************log************/
		$msg = "<div class=\"alert alert-success bg-success text-white border-0\" role=\"alert\">Add Seccssfully!</div>";

		header( "refresh:1 ; url= view_employee.php?emp_id=".$emprow['empid']." " );
	} else {
		$msg = "<div class=\"alert alert-danger bg-danger text-white border-0\" role=\"alert\">Please fill out the form!</div>";
	}
}

?>
<!doctype html>
<html lang="en">

    <head>
        <meta charset="utf-8" />
        <title><?= $site_title ?> - All Employees</title>
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
                                    <form action="<?= $_SERVER['PHP_SELF']; ?>" method="post">
										<?= $msg; ?>
                                        <div class="form-row">
											<div class="form-group col-md-6">
                                                <label for="joining_date" class="col-form-label">Vacation Date<span class="text-danger">*</span></label>
                                                <input type="text" name="date" parsley-trigger="change" readonly
                                                   placeholder="dd/mm/yyyy" class="form-control" value="<?= $emprow['av_vac_strt_date'] ?>"  autocomplete="off">
                                            </div>
                                            <div class="form-group col-md-6">
												
												<label for="inlineRadio3" class="col-form-label radioalign">Remarks<span class="text-danger">*</span></label>
												<div class="radio radio-info form-check-inline">
                                                    <input type="radio" id="inlineRadio3" value="Local Vacation" name="note" <?php if($emprow['av_vac_type'] == "Local Vacation"){echo "checked";} ?> />
                                                    <b for="inlineRadio3"><i class="fa fa-odnoklassniki"></i> Local Vacation</b>
                                                </div>
                                                <?php if($emprow['country_name'] <> "Saudi Arabia" && $emprow['country_name'] <> "Myanmar" ){?>
                                                <div class="radio radio-info form-check-inline">
                                                    <input type="radio" id="inlineRadio1" value="Fly" name="note" <?php if($emprow['av_fly_type'] == "annual" OR $emprow['av_fly_type'] == "emergency"){echo "checked";} ?> />
                                                    <b for="inlineRadio1"><i class="fa fa-plane"></i> Fly </b>
                                                </div>
                                                <?php } ?>
                                                <div class="radio radio-info form-check-inline">
                                                    <input type="radio" id="inlineRadio2" value="Encashed" name="note" <?php if($emprow['av_vac_type'] == "Encashed"){echo "checked";} ?> />
                                                    <b for="inlineRadio2"><i class="mdi mdi-square-inc-cash"></i> Encashed</b>
                                                </div>
												<?php //echo $emprow['av_vac_type'] ?>
                                            </div>											
											
                                        </div>
                                        <?php if($emprow['av_fly_type'] == "annual" OR $emprow['av_fly_type'] == "emergency"){?>
										<div class="form-row">
											<div class="form-group col-md-4">
                                                <label for="return_date" class="col-form-label">Return Date<span class="text-danger">*</span></label>
                                                <input type="text" name="return_date" parsley-trigger="change" readonly
                                                   placeholder="dd/mm/yyyy" class="form-control" value="<?= $emprow['av_return_date'] ?>"  autocomplete="off">
                                            </div>
                                            <div class="form-group col-md-4">
                                                <label for="permit_no" class="col-form-label">Permit No.<span class="text-danger">*</span></label>
                                                <input type="text" name="permit_no" parsley-trigger="change" class="form-control" id="permit_no"  autocomplete="off" required>
                                            </div>
                                            <div class="form-group col-md-4">
                                                <label for="remarks" class="col-form-label">Notes</label>
                                                <input type="text" name="remarks" parsley-trigger="change" class="form-control" id="remarks"  autocomplete="off">
                                            </div>
                                        </div>
                                        <?php } ?>
                                        <?php if($emprow['av_vac_type'] == "Local Vacation"){
										?>
										<div class="form-row">
											<div class="form-group col-md-4">
                                                <label for="return_date" class="col-form-label">Return Date<span class="text-danger">*</span></label>
                                                <input type="text" name="return_date" parsley-trigger="change" readonly
                                                   placeholder="dd/mm/yyyy" class="form-control" value="<?= $emprow['av_return_date'] ?>" autocomplete="off">
                                            </div>
                                            <div class="form-group col-md-4">
                                                <label for="remarks" class="col-form-label">Notes<span class="text-danger">*</span></label>
                                                <input type="text" name="remarks" parsley-trigger="change" class="form-control" id="remarks"  autocomplete="off">
                                            </div>
                                        </div>
                                        <?php } ?>
										<div class="btn-group" role="group" aria-label="Edit Button">
										<a href="view_employee.php?id=<?= $emprow['empid']; ?>" class="btn btn-dark"><i class="fa fa-angle-double-left"></i> Back</a>
                                        <button type="submit" name="submit" class="btn btn-primary"><i class="mdi mdi-airplane-takeoff"></i> Add Details</button>
										</div>
                                    </form>
                                </div>
                            </div>
                        </div>						

                    </div> <!-- container -->

                </div> <!-- content -->

                <footer class="footer">
                    <?= $site_footer ?>
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
	
$(document).ready(function(){
  $("input[name$='note']").click(function(){	  
  var value = $(this).val();
  if(value=='Encashed') {
    $("#return_date").show();
    $("#note").hide();
	$("#note2").hide();
	$("#return_date").removeAttr('required');
	$("#permit_no").removeAttr('required');
  }
  else if(value=='Fly') {
	//document.getElementById("pet_id").required = true;
	$("#return_date").attr('required', '');
	$("#permit_no").attr('required', '');
   	$("#note").show();
	$("#note2").hide();
//    $("#pet_id_box").hide();
   }
  else if(value=='Local Vacation') {
	//document.getElementById("pet_id").required = true;
	$("#return_date_v").attr('required', '');
   	$("#note2").show();
	$("#note").hide();
//    $("#pet_id_box").hide();
   }
  });
  	$("#return_date").removeAttr('required');
  	$("#return_date_v").removeAttr('required');
//  	$("#pet_id_box").show();
  	$("#note").hide();
	$("#note2").hide();
});
        </script>

    </body>
</html>