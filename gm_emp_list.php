<?php
	require_once __DIR__ . '/includes/db.php';

	require_once __DIR__ . '/includes/session_check.php';
	$query = mysqli_query($conDB, "SELECT * FROM `admin_login` WHERE `id_iqama`='".$username."'");
		if(mysqli_num_rows($query) == 1){
		include("./includes/avatar_select.php");
//	if($user_type == "dept_user"){
//		include_once("includes/pagi_function_filter_dept_user.php");
//	}else{
		include_once("includes/pagi_function_filter.php");
//	}
	

	if(isset($_GET["page"]))
	$page = (int)$_GET["page"];
	else
	$page = 1;

	$setLimit = 9;
	$pageLimit = ($page * $setLimit) - $setLimit;
	
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

        <!-- App css -->
        <link href="assets/css/bootstrap.min.css" rel="stylesheet" type="text/css" />
        <link href="assets/css/icons.css" rel="stylesheet" type="text/css" />
        <link href="assets/css/metismenu.min.css" rel="stylesheet" type="text/css" />
        <link href="assets/css/style.css" rel="stylesheet" type="text/css" />
		<link href="assets/css/style_dark.css" rel="stylesheet" type="text/css" />
        <script src="assets/js/modernizr.min.js"></script>
        <style type="text/css">
        	.card-box.bg-light, .card-box.bg-warning, .card-box.bg-danger{
        		box-shadow: 0 1px 2px rgba(0,0,0,0.15);
  				transition: all 0.3s ease-in-out;
  				border-radius: 10px !important;
        	}
        	.card-box.bg-light:hover, .card-box.bg-warning:hover, .card-box.bg-danger:hover{
        		box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        		transform: scale(1.005);
        		cursor: pointer;
        	}
        </style>
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
<?php

$query_emp_vac = mysqli_query($conDB, "SELECT * FROM `apply_vac_dep` WHERE `status`='".$_GET['status']."' ORDER BY `id` DESC");

while ($rec = mysqli_fetch_array($query_emp_vac)) {
	$id_user_usr = $rec["id"];
	$emp_id_usr = $rec["emp_id"];
	$emp_name_usr = $rec["emp_name"];
	$dept_usr = $rec["dept"];
	$status_usr = $rec["status"];
	$date_reg_apl_usr = $rec["date_reg"];
	$empgid_apl_usr = $rec["empgid"];
	$review_apl_usr = $rec["review"];
	$vac_type_apl_usr = $rec["vac_type"];
	
	$times_reg = strtotime("$date_reg_apl_usr");
	$datevac_apl = date('d, M Y', $times_reg);

	
	$sql = "SELECT * FROM `employees` WHERE `status`=1 AND `emp_id`='".$emp_id_usr."' LIMIT ".$pageLimit." , ".$setLimit;
	$query = mysqli_query($conDB, $sql);
	while ($rec = mysqli_fetch_array($query)) {
		$id = $rec["id"];
		$iqama = $rec["iqama"];
		$mobile = $rec["mobile"];
		$salary = $rec["salary"];
		$vacation_days = $rec["vacation_days"];
		$joining_date = $rec["joining_date"];
		$date_reg = $rec["date_reg"];
		$emp_avatar = $rec["avatar"];
		$emp_status = $rec["status"];
		$emp_status_fly = $rec["fly"];
		$emptype = $rec["emptype"];
		
//		$sql_count = mysqli_query($conDB, "SELECT COUNT(*) `emp_id` FROM `emp_vacation` WHERE `emp_id`='".$emp_id."' ");
//		$status_cont = ceil(mysqli_result($sql_count,0));
//		
//		$sql_count_fly = mysqli_query($conDB, "SELECT COUNT(*) `emp_id` FROM `emp_vacation` WHERE `emp_id`='".$emp_id."' && `note`='Fly' ");
//		$cont_fly = ceil(mysqli_result($sql_count_fly,0));
//		
//		$sql_count_encashed = mysqli_query($conDB, "SELECT COUNT(*) `emp_id` FROM `emp_vacation` WHERE `emp_id`='".$emp_id."' && `note`='Encashed' ");
//		$cont_encashed = ceil(mysqli_result($sql_count_encashed,0));	
	}
	
?>
		<div class="col-lg-3" onclick="window.location.href='open_applied_vac.php?id=<?= $id_user_usr."&emp_id=".$emp_id_usr ?>'" style="cursor: pointer;">
			<?php if($status_usr == "app_hr"){
						$color_stu = "warning";
					}elseif($status_usr == "approve"){
						$color_stu = "success";
					}elseif($status_usr == "not_approve"){
						$color_stu = "danger";
					}
			?>
			<div class="text-center card-box bg-<?= $color_stu ?>">

				<div class="member-card pt-2 pb-2">
					<div class="thumb-lg member-thumb m-b-10 mx-auto">
						<img src="<?= $emp_avatar ?>" class="emp_avat_img empfil" alt="profile-image">
					</div>
					<div class=""><br>
						<h4 class="m-b-5"><?=(explode(" ",$emp_name_usr)[0])." ".(explode(" ",$emp_name_usr)[1]) ?></h4>
					</div>
					<div class="btn-group" role="group" aria-label="Edit Button">
					<a href="open_applied_vac.php?id=<?= $id_user_usr."&emp_id=".$emp_id_usr ?>" class="btn btn-primary m-t-20 btn-rounded waves-effect w-md waves-light btn-sm"><i class="mdi mdi-account-search"></i> View Details</a>
					</div><br>
					
					<div class="mt-4">
						<div class="row">
							<div class="col-4 text-left">
								<div class="mt-3">
									<h4 class="m-b-5"><?= $emp_id_usr ?></h4>
									<p class="mb-0">Emp. ID</p>
								</div>
							</div>
							
							<div class="col-4">
								<div class="mt-3">
									<?php if($emptype == "Manager"){?>
										<h5 class="m-b-5"><?= $emptype ?></h5>
<!--									<p class="mb-0 text-muted">Vac. No.</p>-->
									<?php } ?>
								</div>
							</div>
							
							<div class="col-4 text-right">
								<div class="mt-3">
									<h5 class="m-b-5"><?= $iqama ?></h5>
									<p class="mb-0">Iqama / ID</p>
								</div>
							</div>
						</div>
					</div>

				</div>

			</div>

		</div> <!-- end col -->

 <?php } ?>
						</div>
                      

                        <div class="row">
                            <div class="col-12"> 
                                <div class="text-center">
<!--
                                    <ul class="pagination pagination-split mt-0 float-right">
                                        <li class="page-item">
                                            <a class="page-link" href="#" aria-label="Previous">
                                                <span aria-hidden="true">«</span>
                                                <span class="sr-only">Previous</span>
                                            </a>
                                        </li>
                                        <li class="page-item active"><a class="page-link" href="#">1</a></li>
                                        <li class="page-item"><a class="page-link" href="#">2</a></li>
                                        <li class="page-item"><a class="page-link" href="#">3</a></li>
                                        <li class="page-item"><a class="page-link" href="#">4</a></li>
                                        <li class="page-item"><a class="page-link" href="#">5</a></li>
                                        <li class="page-item">
                                            <a class="page-link" href="#" aria-label="Next">
                                                <span aria-hidden="true">»</span>
                                                <span class="sr-only">Next</span>
                                            </a>
                                        </li>
                                    </ul>
-->
									<?php  echo displayPaginationBelow($setLimit,$page); ?>
                                </div>
                            </div>
                        </div>
                        <!-- end row -->

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
        <script src="./plugins/custombox/js/custombox.min.js"></script>
        <script src="./plugins/custombox/js/legacy.min.js"></script>

        <!-- App js -->
        <script src="assets/js/jquery.core.js"></script>
        <script src="assets/js/jquery.app.js"></script>

    </body>
</html>
<?php } ?>