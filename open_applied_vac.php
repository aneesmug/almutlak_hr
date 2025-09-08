<?php
	require_once __DIR__ . '/includes/db.php';
	require_once __DIR__ . '/includes/session_check.php';
	$query = mysqli_query($conDB, "SELECT * FROM `admin_login` WHERE `id_iqama`='".$username."'");
	if(mysqli_num_rows($query) == 1){
	include("./includes/avatar_select.php");
	
	// $getquery = mysqli_query($conDB, "SELECT * FROM `employees` WHERE `emp_id`='".$_GET['emp_id']."' ");

	require("./includes/emp_query.php");

	if(mysqli_num_rows($get_emp_data) !== 0){
		$allRecords = mysqli_fetch_all($get_emp_data, MYSQLI_ASSOC);
		foreach ($allRecords as $rec) {
			$emprow = $rec;
		}
	// 	while($rec = mysqli_fetch_assoc($getquery)){
	// 		$id_get = $rec["id"];
	// 		$emprow['name'] = $rec["name"];
	// 		$emprow['empid'] = $rec["emp_id"];
	// 		$iqama_get = $rec["iqama"];
	// 		$mobile_get = $rec["mobile"];
	// 		$salary_get = (float)($rec["salary"]);
	// 		$vacation_days_get = $rec["vacation_days"];
	// 		$joining_date_get = $rec["joining_date"];
	// 		$date_reg_get = $rec["date_reg"];
	// 		$emp_avatar_get = $rec["avatar"];
	// 		$emp_status_get = $rec["status"];
	// 		$emp_ter_date_get = $rec["ter_date"];
	// 		$note_get = $rec["note"];
	// 		$fly_get = $rec["fly"];
	// 		$emprow['dept_name'] = $rec["dept"];
	// 		$sectin_nme_get = $rec["sectin_nme"];
	// 		$emprow['country_name'] = $rec["country"];
			
	// 		$salary_get = str_replace(',', '', $salary_get);
	// }	
	// $sql_count_fly = mysqli_query($conDB, "SELECT COUNT(*) `emp_id` FROM `emp_vacation` WHERE `emp_id`='".$emprow['empid']."' && `note`='Fly' ");
	// $cont_fly = mysqli_fetch_array($sql_count_fly)[0];

	// $sql_count_encashed = mysqli_query($conDB, "SELECT COUNT(*) `emp_id` FROM `emp_vacation` WHERE `emp_id`='".$emprow['empid']."' && `note`='Encashed' ");
	// $cont_encashed = mysqli_fetch_array($sql_count_encashed)[0];
		
	if($emprow["status"] == "no" && $emprow["note"] == "expired"){
		$note_get = "Expired";
	} elseif($emprow["status"] == "no" && $emprow["note"] == "terminat"){
		$note_get = "Terminated";
	}
		
		$getqueryaply = mysqli_query($conDB, "SELECT * FROM `apply_vac_dep` WHERE `emp_id`='".$emprow['empid']."' AND `id`='".$_GET['id']."' ORDER BY `id` DESC LIMIT 1 ");
			while($recaply = mysqli_fetch_assoc($getqueryaply)){
				$id_vac_get = $recaply["id"];
				$emp_id_vac_get = $recaply["emp_id"];
				$status_vac_get = $recaply["status"];
				$review_vac_get = $recaply["review"];
				$date_reg_vac_get = $recaply["date_reg"];
				$gm_note_vac_get = $recaply["gm_note"];
				$hr_note_vac_get = $recaply["hr_note"];
				$replacement_per_vac_get = $recaply["replacement_per"];
				$ticket_pay_vac_get = (float)($recaply["ticket_pay"]);
				$permit_fee_vac_get = (float)($recaply["permit_fee"]);
				$vac_strt_date_vac_get = $recaply["vac_strt_date"];
				$return_date_vac_get = $recaply["return_date"];
				$next_vac_date_vac_get = $recaply["next_vac_date"];
				$last_vac_date_vac_get = $recaply["last_vac_date"];
				$jion_date_vac_get = $recaply["jion_date"];
				$fly_type_get = $recaply["fly_type"];
				$vac_type_get = $recaply["vac_type"];
				$vacdaysget = (float)($recaply["vacdays"]);
				
				$timestamp_reg = strtotime($date_reg_vac_get);
				$date_reg_vac_get = date('d, M Y', $timestamp_reg);
				
				$flydatetime = strtotime(date('M d Y', strtotime(date('M d Y', strtotime(str_replace('/', '-', $vac_strt_date_vac_get))))));
				$returndatetime = strtotime(date('M d Y', strtotime(date('M d Y', strtotime(str_replace('/', '-', $return_date_vac_get))))));	
				$secs = $returndatetime - $flydatetime;// == <seconds between the two times>
				$vacdays = $secs / 86400;
			}
		$getquerylgn = mysqli_query($conDB, "SELECT * FROM `admin_login` WHERE `emp_id`='".$emprow['empid']."' ");
			while($recaply = mysqli_fetch_assoc($getquerylgn)){
				$emp_id_lgn_get = $recaply["emp_id"];
				$dept_lgn_get = $recaply["dept"];;
			}
		$getqueryslry = mysqli_query($conDB, "SELECT * FROM `emp_salary` WHERE `emp_id`='".$emprow['empid']."' ORDER BY id DESC LIMIT 1");
			while($recslry = mysqli_fetch_assoc($getqueryslry)){
				$basic_slry_get = (float)($recslry["basic"]);
				$housing_slry_get = (float)($recslry["housing"]);
				$transport_slry_get = (float)($recslry["transport"]);
				$food_slry_get = (float)($recslry["food"]);
				$misc_slry_get = (float)($recslry["misc"]);
				$cashier_slry_get = (float)($recslry["cashier"]);
				$fuel_slry_get = (float)($recslry["fuel"]);
				$tel_slry_get = (float)($recslry["tel"]);
				$other_slry_get = (float)($recslry["other"]);
				$guard_slry_get = (float)($recslry["guard"]);
			}
		
			if($fly_type_get == "annual" OR $vac_type_get == "Encashed" OR $vac_type_get == "Local Vacation"){
				$vaccslry_get = $basic_slry_get + $housing_slry_get + $transport_slry_get + $food_slry_get + $misc_slry_get + $cashier_slry_get + $fuel_slry_get + $tel_slry_get  + $other_slry_get  + $guard_slry_get;
				// $vaccslry_get = $basic_slry_get + $housing_slry_get + $transport_slry_get + $food_slry_get + $misc_slry_get + $cashier_slry_get + $fuel_slry_get + $tel_slry_get  + $other_slry_get  + $guard_slry_get;
				// if ($vac_strt_date_vac_get == date("Y")) {
				// }
				$vacslry_get = $vaccslry_get / 30 * $vacdaysget;
				/*************************************/
		$getqueryempvac_bal = mysqli_query($conDB, "SELECT * FROM `emp_vacation` WHERE `emp_id`='".$emprow['empid']."' ");
			while($recempvac_bal = mysqli_fetch_assoc($getqueryempvac_bal)){
				$emp_vacdays_get = $recempvac_bal["vacdays"];
				$emp_vacdate_get = date('Y', strtotime(str_replace('/', '-', $recempvac_bal["date"])));
			}
				if($emp_vacdate_get == date("Y")){
					if ($status_vac_get != "approve") {
						// if GM not approved
						$vac_days_get = $vacation_days_get - $emp_vacdays_get;
					} else {
						// if GM approved
						$vac_days_get = $emp_vacdays_get;
					}
					if($vac_days_get == "0"){
						$vac_days_get = $vacation_days_get;
					}
				}else{
					$vac_days_get = $vacation_days_get;
				}
			// $vacslryfinalget = $vacslry_get; // $vacation_days_get * $vac_days_get;
			$vacslryfinal_get = $vacslry_get + $other_pay_slry_get; // $vacation_days_get * $vac_days_get;
			$vacslrygosi_get = $vacslry_get / 100 * 9.75 /* old 10% */;
				/*************************************/
				$total_pay_vac = $vacslry_get + $ticket_pay_vac_get + $permit_fee_vac_get + $other_pay_slry_get ;
				if($emprow['country_name'] == "Saudi Arabia"){
					$total_pay_vac = $total_pay_vac - $vacslrygosi_get;
				}
			}else{
				$total_pay_vac="";
			}
//			$vac_days_get = $vacation_days_get - $emp_vacdays_get;
} else {
		//when the id not equals id show database
		header("Location: ./reg_employee.php");
	}

?>
<!doctype html> 
<html lang="en">

    <head>
        <meta charset="utf-8" />
        <title><?=$site_title ?> - All Employees</title>
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

                        <div class="row">
                            <div class="col-md-12">
                                <div class="card-box" id="nodeToRenderAsPDF">
                                    <div class="clearfix">
                                        <center> <img src="assets/images/logo.png" alt="" height="80"></center>
                                        <div class="float-right">
                                          <!-- <h4 class="m-0">Vacation for <?=$emprow['name'] ?></h4>   -->
                                        </div>
                                    </div>
                                    
                                    <div class="row">
                                       	<div class="col-6">
                                       	<div class="card-box">
											<img src="<?=$emprow['avatar'] ?>" alt="<?=$emprow['name'] ?>" class="img-thumbnail" style="height: 160px !important;" />
										</div>
										</div>
                                       
                                        <div class="col-4 offset-2">
                                            <div class="mt-3 float-right">

                                            	<!-- <h4 class="m-0">Vacation for <?=$emprow['name'] ?></h4><br />                                           -->

                                                <p class="m-b-13 font-18"><strong>Request Date: </strong> <?=$date_reg_vac_get." ". $vac_days_get ?></p>
												<?php
													if($status_vac_get == "app_hr"){
												?>
												<p class="m-b-13 font-18"><strong>Request Status: </strong>
												<?php if($user_type == "gm" OR $user_type == "administrator" OR $user_type == "hr"){?>
												<a href="javascript:void(0);" class="btn btn-sm btn-dark waves-effect" data-toggle="modal" data-target=".terminat" >
                                                    <i class="mdi mdi-account-off"></i> Not Yet Approved
                                                </a>												
												<?php }else{ ?>
												<a href="javascript:void(0);" class="btn btn-sm btn-dark waves-effect">
                                                    <i class="mdi mdi-account-off"></i> Not Yet Approved
                                                </a>
												<?php } ?>
												</p>
												<?php if($fly_type_get == "emergency"){?>
												<p class="m-b-13 font-18" style="color: #F1556C !important;">
                                                    <i class="mdi mdi-recycle"></i> Emergency Vacation
												</p>
												<?php }elseif($vac_type_get == "Encashed"){ ?>
                                              	<p class="m-b-13 font-18" style="color: #FF6700 !important;">
                                                    <i class="mdi mdi-cash-multiple"></i> Encashed
												</p>
                                               	<?php } ?>
                                                <!--<p class="m-b-13 font-18"><strong>Request Status: </strong> <span class="badge badge-dark">Not Yet Approved
												</span></p>-->
												<?php }elseif($status_vac_get == "approve"){ ?>
												<p class="m-b-13 font-18"><strong>Request Status: </strong> <span class="badge badge-success">Approved
												</span></p>
												<?php }elseif($status_vac_get == "not_approve"){ ?>
												<p class="m-b-13 font-18"><strong>Request Status: </strong> <span class="badge badge-danger">Vacation Not Approved
												</span></p>
												<?php } ?>
                                                <p class="m-b-10"><strong>Employee ID: </strong> #<?=$emprow['empid'] ?></p>
                                                <p class="m-b-10"><strong>Request ID: </strong> #<?=$_GET['id'] ?></p>
                                            </div>
                                        </div><!-- end col -->
                                    </div>
                                    <!-- end row -->
									<div class="row">
										<?php if($status_vac_get == "not_approve"){?>
										<div class="col-12">
										<center><h3 class="mt-0 m-b-20" style="color: #F1556C;">Rejection Note: <?=ucwords($gm_note_vac_get) ."<br>".ucwords($hr_note_vac_get); ?></h3></center>
										</div>
										<?php } ?>
										<div class="col-6">
											<div class="card-box">
                                   			
                                    
                                    <h3 class="mt-0 m-b-20">Personal Information </h3>
                                    <div class="panel-body">
                                        <hr>

                                        <div class="text-left">
                                            <p class="font-15"><strong>Full Name :</strong> <span class="m-l-15"><?=$emprow['name'] ?></span></p>
                                            <p class="font-15"><strong>Nationality :</strong> <span class="m-l-15"><?=$emprow['country_name'] ?></span></p>
                                            <p class="font-15"><strong>Department :</strong><span class="m-l-15"><?=$emprow['deptnme'] ?></span></p>
                                            <p class="font-15"><strong>Section :</strong> <span class="m-l-15"><?=$emprow['sectin_nme'] ?></span></p>
                                            <?php if($vac_type_get <> "Encashed"){?>
                                            <p class="font-15"><strong>Replacement Person :</strong> <span class="m-l-15"><?=$replacement_per_vac_get ?></span></p>
                                            <?php } ?>
                                            <?php if($vac_type_get == "Encashed" OR $vac_type_get == "Local Vacation"){?>
                                            <p class="font-15"><strong>Vacation Salary :</strong> <span class="m-l-15"><?=number_format($vacslryfinal_get) ?>/-SAR</span></p>
                                            <?php } ?>
                                            <?php if($fly_type_get == "annual"){ ?>
											<p class="font-15"><strong>Vacation Salary :</strong> <span class="m-l-15"><?=number_format($vacslryfinal_get) ?>/-SAR</span></p>
                                            <?php if($emprow['country_name'] <> "Saudi Arabia" OR $vac_type_get <> "Local Vacation"){?>
                                            <p class="font-15"><strong>Ticket Fares :</strong> <span class="m-l-15"><?=number_format($ticket_pay_vac_get) ?>/-SAR</span></p>
                                            <p class="font-15"><strong>Exit Re-Entry Permit Fee :</strong> <span class="m-l-15"><?=number_format($permit_fee_vac_get) ?>/-SAR</span></p>
                                            <?php } ?>
                                            <?php } ?>
											<?php if($emprow['country_name'] == "Saudi Arabia"){ ?>
											<p class="font-15"><strong>GOSI Deduction :</strong><span class="m-l-15 badge badge-danger">-<?=number_format($vacslrygosi_get) ?>/-SAR</span></p>
											<?php } ?>
                                        </div>
                                    </div>
                                </div>
										</div>
										<div class="col-6">
											<div class="card-box">
                                    <h3 class="mt-0 m-b-20">Vacation Information</h3>
                                    <div class="panel-body">
                                        <hr>

                                        <div class="text-left">
											<?php if($fly_type_get == "emergency"){ ?>
											<p class="font-15 badge badge-danger"><strong>Vacation Date :</strong> <span class="m-l-15"><?=$vac_strt_date_vac_get ?></span></p>
											<?php }else{ ?>
											<p class="font-15 badge badge-primary"><strong>Vacation Date :</strong> <span class="m-l-15"><?=$vac_strt_date_vac_get ?></span></p>
                                        	<?php }?>
                                           	<?php if($vac_type_get <> "Encashed"){ ?>
                                            <p class="font-15"><strong>Return Before :</strong><span class="m-l-15"><?=$return_date_vac_get ?></span></p>
                                            <p class="font-15"><strong>Days of Vacation :</strong> <span class="m-l-15"><?=$vacdays ?> Days</span></p>
                                            <?php } ?>
                                            <p class="font-15"><strong>Next Vacation :</strong> <span class="m-l-15"><?=$next_vac_date_vac_get ?></span></p>
                                            <p class="font-15"><strong>Last Vacation :</strong> <span class="m-l-15"><?=$last_vac_date_vac_get ?></span></p>
                                            <p class="font-15"><strong>Joining Date :</strong> <span class="m-l-15"><?=$emprow['joining_date'] ?></span></p>
                                            <?php if($gm_note_vac_get !== ""){ ?>
                                            <p class="font-15"><strong>General Manager Note :</strong> <span class="m-l-15"><?=$gm_note_vac_get ?></span></p>
                                            <?php } ?>
											<?php if($hr_note_vac_get !== ""){ ?>
                                            <p class="font-15"><strong>HR Note :</strong> <span class="m-l-15"><?=$hr_note_vac_get ?></span></p>
                                            <?php } ?>
                                        </div>

                                    </div>
                                </div>
										</div>
									</div>
                                    
                                    <div class="row">
                                        <div class="col-6"></div>
                                        <div class="col-6">
                                           <?php if($fly_type_get == "annual" OR $vac_type_get == "Encashed" OR $vac_type_get == "Local Vacation"){ ?>
                                            <div class="float-right">
                                                <p><b>Total Payment for Vacation:</p>
                                                <?php /*?><h3>SR <?=number_format((float)$total_pay_vac, 2, '.', '') ?> SAR</h3><?php */?>
                                                <h3>SR <?=number_format($total_pay_vac); ?> SAR</h3>
                                            </div>
                                            <?php } else { ?>
                                             <div class="float-right">
                                                <p><b>Total Payment for Vacation:</p>
                                                <h3>Nothing to Pay</h3>
                                            </div>
                                            <?php } ?>
                                            <div class="clearfix"></div>
                                        </div>
                                    </div>
								<?php if($status_vac_get == "approve" OR $status_vac_get == "app_hr"){?>
                                    <div class="hidden-print mt-4 mb-4">
                                        <div class="text-right">
                                            <a href="javascript:window.print()" class="btn btn-primary waves-effect waves-light"><i class="fa fa-print m-r-5"></i> Print</a>
<!--                                            <button type="button" class="btn btn-danger waves-effect waves-light" onclick="print();"><i class="mdi mdi-file-pdf"></i> Export PDF</button>-->
                                        </div>
                                    </div>
								<?php } ?>
                                </div>

                            </div>

                        </div>
                        <!-- end row -->

                    </div> <!-- container -->

                </div> <!-- content -->

                <footer class="footer">
                    <?=$site_footer ?>
                </footer>

            </div>

            <!-- ============================================================== -->
            <!-- End Right content here -->
            <!-- ============================================================== -->
        </div>
        <!-- END wrapper -->
<div class="modal fade terminat" tabindex="-1" role="dialog" aria-labelledby="mySmallModalLabel" aria-hidden="true" style="display: none;">
	<form action="./includes/vac_app_emp.php" method="get">
	<div class="modal-dialog modal-dialog-centered">
		<div class="modal-content">
			<div class="modal-header" style="background-color: #2D7BF4 !important; color: #fff !important;">
				<button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
				<h4 class="modal-title" id="mySmallModalLabel">
					<i class="dripicons-clockwise"></i> 
					Are you sure!
				</h4>
			</div>
			<div class="modal-body">
				<h3>You need to Approve!</h3>
				<h4><strong style="font-size: 30px; "><?=(explode(" ",$name)[0])." ".(explode(" ",$name)[1]) ?></strong></h4>
<div class="form-row" id="content" style="display:none;">

<!--	<a href="" class="btn btn-danger waves-effect waves-light" ><i class="mdi mdi-account-off"></i> Terminat</a>-->
<input type="hidden" name="id" value="<?=$_GET['id'] ?>" >
<input type="hidden" name="status" value="not_approve" >
<input type="hidden" name="appid" value="<?=$id_vac_get ?>" >
<input type="hidden" name="empidnoapp" value="<?=$emprow['empid'] ?>" >
<input type="hidden" name="reviewnoapp" value="<?=$review_vac_get ?>" >
	<div class="input-group">
		<input type="text" id="ter_note" name="note" class="form-control" aria-describedby="basic-addon2">
		<div class="input-group-append">
		<button type="submit" class="btn btn-danger waves-effect waves-light"><i class="dripicons-clockwise"></i> Not Approve</button>
		</div>
	</div>

</div>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-dark waves-effect" data-dismiss="modal">Close</button>
				<button type="button" id="terminat_emp" class="btn btn-light waves-effect waves-light"><i class="mdi mdi-account-off"></i> Not Approve</button>
				<a href="./includes/vac_app_emp.php?id=<?=$_GET['id'] ?>&status=approve&empidnoapp=<?=$emprow['empid'] ?>&reviewnoapp=<?=$review_vac_get ?>" class="btn btn-success waves-effect waves-light"><i class="mdi mdi-account-star"></i> Approved</a>			
				
			</div>
		</div><!-- /.modal-content -->
	</div><!-- /.modal-dialog -->
</form>
</div>

        <!-- jQuery  -->
        <script src="assets/js/jquery.min.js"></script>
<!--        <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/1.2.61/jspdf.min.js"></script>-->
		<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/1.3.4/jspdf.min.js" integrity="sha256-vIL0pZJsOKSz76KKVCyLxzkOT00vXs+Qz4fYRVMoDhw=" crossorigin="anonymous"></script>
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
					order: [[ 4, "desc" ]],
					"columnDefs": [
									{
									targets: [ 4 ],
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
	$("#return_date").removeAttr('required');
	$("#permit_no").removeAttr('required');
  }
  else if(value=='Fly') {
	//document.getElementById("pet_id").required = true;
	$("#return_date").attr('required', '');
	$("#permit_no").attr('required', '');
   	$("#note").show();
//    $("#pet_id_box").hide();
   }
  });
  	$("#return_date").removeAttr('required');
//  	$("#pet_id_box").show();
  	$("#note").hide();
});
/***************************/
	jQuery('#terminat_emp').on('click', function(event) {  
	   $("#ter_note").attr('required', '');
		jQuery('#content').toggle('show');
	});
</script>

    </body>
</html>
<?php } ?>