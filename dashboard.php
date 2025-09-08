<?php
	// require_once __DIR__ . '/includes/db.php';
	// Initialize the application and load translations
	require_once __DIR__ . '/includes/session_check.php';	
	/*require_once('./includes/auth.php');
	include('./includes/MainClass.php');*/
	$query = mysqli_query($conDB, "SELECT * FROM `admin_login` WHERE `id_iqama`='".$username."'");
	if(mysqli_num_rows($query) == 1){
	
	include("./includes/avatar_select.php");
	include("./includes/Hijri_GregorianConvert.php");
	$DateConv = new Hijri_GregorianConvert;
	$format="DD/MM/YYYY";

	/****************Employee Allow Page*****************/
    if ($_SESSION['user_type'] == 'employee') {
        header( "refresh:1 ; url= ./profile.php" );
    }
    /****************Employee Allow Page*****************/

if($user_type == "dept_user"){
	$sql_count_active = mysqli_query($conDB, "SELECT COUNT(*) AS `activeusr`, `id` FROM `employees` WHERE `status`=1 AND `fly`=0 AND `dept`='".$user_dept."' ");
	while($rec = mysqli_fetch_assoc($sql_count_active)){$status_cont_active = $rec["activeusr"];}
//	$status_cont_active = ceil(mysqli_result($sql_count_active,0));
		
	$sql_count_ter = mysqli_query($conDB, "SELECT COUNT(*) AS `ter`, `id` FROM `employees` WHERE `status`=0 AND `dept`='".$user_dept."'");
	while($rec = mysqli_fetch_assoc($sql_count_ter)){$status_cont_ter = $rec["ter"];}
//	$status_cont_ter = ceil(mysqli_result($sql_count_ter,0));
		
	$sql_count_fly = mysqli_query($conDB, "SELECT COUNT(*) AS `flying`, `id` FROM `employees` WHERE `fly`=1 AND `dept`='".$user_dept."'");
	while($rec = mysqli_fetch_assoc($sql_count_fly)){$status_cont_fly = $rec["flying"];}
//	$status_cont_fly = ceil(mysqli_result($sql_count_fly,0));
		
	$sql_count_tot = mysqli_query($conDB, "SELECT COUNT(*) AS `tot`, `id` FROM `employees` WHERE `dept`='".$user_dept."'");
	while($rec = mysqli_fetch_assoc($sql_count_tot)){$status_cont_tot = $rec["tot"];}
//	$status_cont_tot = ceil(mysqli_result($sql_count_tot,0));
	
	$sql_count_man_power = mysqli_query($conDB, "SELECT COUNT(*) AS `manpwr`, `id` FROM `employees` WHERE `emp_sup_type`='man_power' AND `dept`='".$user_dept."' AND `status`=1 ");
	while($rec = mysqli_fetch_assoc($sql_count_man_power)){$status_cont_man_power = $rec["manpwr"];}
//	$status_cont_man_power = ceil(mysqli_result($sql_count_man_power,0));
	
}else{
	$sql_count_active = mysqli_query($conDB, "SELECT COUNT(*) AS `activeusr`, `id` FROM `employees` WHERE `status`=1 AND `fly`=0 ");
	while($rec = mysqli_fetch_assoc($sql_count_active)){$status_cont_active = $rec["activeusr"];}
		
	$sql_count_ter = mysqli_query($conDB, "SELECT COUNT(*) AS `ter`, `id` FROM `employees` WHERE `status`=0 ");
	while($rec = mysqli_fetch_assoc($sql_count_ter)){$status_cont_ter = $rec["ter"];}
		
	$sql_count_fly = mysqli_query($conDB, "SELECT COUNT(*) AS `flying`, `id` FROM `employees` WHERE `fly`=1");
	while($rec = mysqli_fetch_assoc($sql_count_fly)){$status_cont_fly = $rec["flying"];}
		
	$sql_count_tot = mysqli_query($conDB, "SELECT COUNT(*) AS `tot`, `id` FROM `employees`");
	while($rec = mysqli_fetch_assoc($sql_count_tot)){$status_cont_tot = $rec["tot"];}
	
	$sql_count_man_power = mysqli_query($conDB, "SELECT COUNT(*) AS `manpwr`, `id` FROM `employees` WHERE `emp_sup_type`='man_power' AND `status`=1 ");
	while($rec = mysqli_fetch_assoc($sql_count_man_power)){$status_cont_man_power = $rec["manpwr"];}
}

if(isset($_POST['submit'])){
	if($_POST['iqama_exp']){
		$iqama_exp_gup = $DateConv->HijriToGregorian($_POST['iqama_exp'], $format);
		$iqama_exp_g_up = date("Y/m/d", strtotime($iqama_exp_gup));
		/*$u = "UPDATE `employees` SET `iqama_exp`='".$_POST['iqama_exp']."', `iqama_exp_g`='".$iqama_exp_g_up."' WHERE `id`='".$_POST['iid']."' ";
		print_r($u);
		exit();*/
		mysqli_query($conDB, "UPDATE `employees` SET `iqama_exp`='".$_POST['iqama_exp']."', `iqama_exp_g`='".$iqama_exp_g_up."' WHERE `id`='".$_POST['iid']."' ") or die();
		$message = "<div class='alert alert-success bg-success text-white border-0'><strong>Successfully!</strong> Your car details will be successfully edit!</div>";
		header( "refresh:1 ; url= ./dashboard.php" );

	} else {
		$message = "<div class=\"alert alert-danger bg-danger text-white border-0\" role=\"alert\">Please select expiry date!</div>";
	}
}


?>
<!doctype html>
<html lang="<?= $current_lang ?? 'en' ?>" <?= ($is_rtl ?? false) ? 'dir="rtl"' : '' ?>>
    <head>
        <meta charset="utf-8" />
        <title><?=$site_title ?> - <?=__('dashboard') ?></title>
        <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
<!--        <meta content="A fully featured admin theme which can be used to build CRM, CMS, etc." name="description" />-->
        <meta content="Anees Afzal" name="author" />
        <meta http-equiv="X-UA-Compatible" content="IE=edge" />

        <!-- App favicon -->
        <link rel="shortcut icon" href="assets/images/favicon.ico">
		
		<!-- DataTables -->
        <link href="./plugins/datatables/dataTables.bootstrap4.min.css" rel="stylesheet" type="text/css" />
        <link href="./plugins/datatables/buttons.bootstrap4.min.css" rel="stylesheet" type="text/css" />
        <!-- Responsive datatable examples -->
        <link href="./plugins/datatables/responsive.bootstrap4.min.css" rel="stylesheet" type="text/css" />

        <!-- Multi Item Selection examples -->
        <link href="./plugins/datatables/select.bootstrap4.min.css" rel="stylesheet" type="text/css" />

        <link href="./plugins/bootstrap-timepicker/bootstrap-timepicker.min.css" rel="stylesheet">
        <link href="./plugins/bootstrap-colorpicker/css/bootstrap-colorpicker.min.css" rel="stylesheet">
        <link href="./plugins/bootstrap-datepicker/css/bootstrap-datepicker.min.css" rel="stylesheet">
        <link href="./plugins/clockpicker/css/bootstrap-clockpicker.min.css" rel="stylesheet">
        <link href="./plugins/bootstrap-daterangepicker/daterangepicker.css" rel="stylesheet">

		<link href="./plugins/bootstrap-timepicker/hijri_css/bootstrap-datetimepicker.css" rel="stylesheet">
        <link href="./plugins/bootstrap-timepicker/hijri_css/bootstrap-datetimepicker.min.css" rel="stylesheet">

        <!-- App css -->
        <link href="assets/css/bootstrap.min.css" rel="stylesheet" type="text/css" />
        <!-- <link href="assets/css/icons.css" rel="stylesheet" type="text/css" /> -->
        <link href="assets/css/metismenu.min.css" rel="stylesheet" type="text/css" />
        <link href="assets/css/style.css" rel="stylesheet" type="text/css" />
		<link href="assets/css/style_dark.css" rel="stylesheet" type="text/css" />

        <script src="assets/js/modernizr.min.js"></script>

		<?php if ($is_rtl): ?>
            <link href="assets/css/style_rtl.css" rel="stylesheet" type="text/css" />
        <?php endif; ?>
		<script> window.lang = <?= json_encode($GLOBALS['translations'] ?? []) ?>;</script>

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
						<div class="card-box">

						<?php 
							$message;
							if (isset($_SESSION['error_msg'])) {
								echo $_SESSION['error_msg'];
								unset($_SESSION['error_msg']); // Clear after displaying
								header("refresh:3 ; ./dashboard.php");
							}
						?>
                        <div class="row text-center">
                            <div class="col-sm-4 col-xl-4" onclick="window.location.href='dashbydepart.php'" style="cursor: pointer;">
                                <div class="card-box widget-flat border-custom bg-custom text-white">
                                    <i class="fa fa-house-chimney-user"></i>
                                    <h3 class="m-b-10"><?=$status_cont_active ?></h3>
                                    <p class="text-uppercase m-b-5 font-13 font-600"><?=__('almutlak_employees') ?></p>
                                </div>
                            </div>
							<div class="col-sm-4 col-xl-4" onclick="window.location.href='filter_employee.php?page=1&status=1&fly=no&emp_sup_type=man_power'" style="cursor: pointer;">
                                <div class="card-box widget-flat border-purple bg-purple text-white">
                                    <i class="fa fa-users-rays"></i>
                                    <h3 class="m-b-10"><?php if($status_cont_man_power > 0){ echo $status_cont_man_power;}else{echo "0";} ?></h3>
                                    <p class="text-uppercase m-b-5 font-13 font-600"><?=__('man_power_employees') ?></p>
                                </div>
                            </div>
                            <div class="col-sm-4 col-xl-4" <?php if($status_cont_fly > 0){ ?> onclick="window.location.href='dashbydepart.php'" style="cursor: pointer;" <?php } ?> >
                                <div class="card-box bg-primary widget-flat border-primary text-white">
                                    <i class="fa fa-plane-departure"></i>
                                    <h3 class="m-b-10"><?=$status_cont_man_power + $status_cont_active ?></h3>
                                    <p class="text-uppercase m-b-5 font-13 font-600"><?=__('total_on_job_employees') ?></p>
                                </div>
                            </div>
                            <div class="col-sm-4 col-xl-4" <?php if($status_cont_fly > 0){ ?> onclick="window.location.href='filter_employee.php?page=1&status=1&fly=yes'" style="cursor: pointer;" <?php } ?> >
                                <div class="card-box bg-warning widget-flat border-primary text-white">
                                    <i class="fa fa-planet-moon"></i>
                                    <h3 class="m-b-10"><?=$status_cont_fly ?></h3>
                                    <p class="text-uppercase m-b-5 font-13 font-600"><?=__('on_vacations_employees') ?></p>
                                </div>
                            </div>
                            <div class="col-sm-4 col-xl-4" onclick="window.location.href='filter_employee.php?page=1&status=0&fly=0'" style="cursor: pointer;">
                                <div class="card-box bg-danger widget-flat border-danger text-white">
                                    <i class="fa fa-users-slash"></i>
                                    <h3 class="m-b-10"><?=$status_cont_ter ?></h3>
                                    <p class="text-uppercase m-b-5 font-13 font-600"><?=__('terminated_employees') ?></p>
                                </div>
                            </div>
							<div class="col-sm-4 col-xl-4" onclick="window.location.href='reg_employee.php'" style="cursor: pointer;">
                                <div class="card-box widget-flat border-success bg-success text-white">
                                    <i class="fa fa-users-viewfinder"></i>
                                    <h3 class="m-b-10"><?=$status_cont_tot ?></h3>
                                    <p class="text-uppercase m-b-5 font-13 font-600"><?=__('total_employees') ?></p>
                                </div>
                            </div>
                        </div>                        
                        </div>
                        <?php /* if($user_type == $access1 OR $user_type == $access2){ ?>
                        <div class="card-box">
                        	<h4 class="m-t-0 header-title">Search Birthday by Month</h4>
                        	<form action="find_birthday.php" method="get">
							<?=$msg ?>
							<div class="form-row">
								<div class="form-group col-md-6">
									<label for="datepickerdob" class="col-form-label">Section Name<span class="text-danger">*</span></label>
									<input type="text" name="find_birthday" parsley-trigger="change" required placeholder="Select any date of month" class="form-control" id="datepickerdob">
								</div>
							</div>							
							<button type="submit" name="submit" class="btn btn-primary"><i class="mdi mdi-update"></i> Search Birthday</button>
							</form>
                        </div>
                        <?php } */?>
							<?php

							if ($user_type == "dept_user" || $user_type == "hr" || $user_type == "administrator"):

								if($user_type == "dept_user"){
									$result=mysqli_query($conDB, "SELECT 
									`e`.*, 
									`d`.`dep_nme`,
									`d`.`dep_nme_ar`
									FROM `employees` as `e`
									LEFT JOIN `department` `d` ON `d`.`id` = `e`.`dept`
									WHERE `e`.`status`=1 AND `e`.`iqama_exp_g` BETWEEN CURDATE() and DATE_ADD(CURDATE(), INTERVAL 30 DAY) AND `e`.`dept`='".$user_dept."' ");
								}else{
									$result=mysqli_query($conDB, "SELECT 
									`e`.*, 
									`d`.`dep_nme`,
									`d`.`dep_nme_ar`
									FROM `employees` as `e`
									LEFT JOIN `department` `d` ON `d`.`id` = `e`.`dept`
									WHERE `e`.`status`=1 AND `e`.`iqama_exp_g` BETWEEN CURDATE() and DATE_ADD(CURDATE(), INTERVAL 30 DAY) ");
								}

								if( mysqli_num_rows($result) != 0 ){
							?>
						<div class="card-box">
							<h4 class="m-t-0 header-title"><?=__('coming_soon_expiry_with_30_days') ?></h4>
							<table id="datatable" class="table table-striped dt-responsive nowrap" style="border-collapse: collapse; border-spacing: 0; width: 100%;">
								<thead class="thead-dark">
								<tr>
									<th><?=__('employee_id') ?></th>
									<th><?=__('name') ?></th>
									<th><?=__('iqama_id') ?></th>
									<th><?=__('department') ?></th>
									<th><?=__('country') ?></th>
									<th><?=__('iqama_id_expiry') ?></th>
									<th><?=__('days_of_expiry') ?></th>
									<th width="200"><?=__('action') ?></th>
								</tr>
								</thead>

								<tbody>
								<?php
									while($row = mysqli_fetch_assoc($result)){	
										if(strtotime(date("Y/m/d")) < strtotime($row['iqama_exp_g'])){

											$from = strtotime(date("d-m-Y", strtotime($row['iqama_exp_g'])));
											$today = time();
											$difference = $from - $today;
											$daystoexp = floor($difference / 86400);  // (60 * 60 * 24)
											
											if($daystoexp <= "10"){
												$color = "table-danger";
											}elseif($daystoexp <= "20"){
												$color = "table-warning";
											}elseif($daystoexp <= "30"){
												$color = "table-info";
											}
											
//													 echo "<div style='background-color: {$color};'>Expires in {$when} day(s)</div>";
								?>
									
								<tr class="<?=$color; ?>">
									<td><?=$row['emp_id']; ?></td>
									<td><?=$row['name']; ?></td>
									<td><span class='copyToClipboard'><?=$row['iqama']?></span> <i class='fa fa-clipboard'></i></td>
									<td><?=($is_rtl ?? false ? $row['dep_nme_ar']:$row['dep_nme'])?></td>
									<td><?=$row['country']; ?></td>
									<td><?=$row['iqama_exp']; ?></td>
									<td align="center"><?=$daystoexp; ?> <?=__('days')?></td>
									<td>
										<div class="btn-group" role="group" aria-label="Edit Button">
											<a href="view_employee.php?emp_id=<?=$row['emp_id'];?>" class="btn btn-primary m-t-20 btn-rounded waves-effect w-md waves-light btn-sm iqama_exp_hijri" data-id="<?=$row['id']?>" >
												<i class="mdi mdi-account-card-details"></i> <?=__('update_expiry')?>
											</a>
										</div>
									</td>
								</tr>
								<?php } } ?>
								</tbody>
							</table>
						</div>
						<?php } ?>
						<?php
							if($user_type == "dept_user"){
									$result=mysqli_query($conDB, "SELECT 
										`e`.*, 
										`d`.`dep_nme`,
										`d`.`dep_nme_ar`
										FROM `employees` as `e`
										LEFT JOIN `department` `d` ON `d`.`id` = `e`.`dept`
										WHERE `e`.`status`=1 AND DATEDIFF(`e`.`iqama_exp_g`, NOW()) <= 1 AND `e`.`dept`='".$user_dept."' ");
								}else{
									$result=mysqli_query($conDB, "SELECT 
										`e`.*, 
										`d`.`dep_nme`,
										`d`.`dep_nme_ar`
										FROM `employees` as `e`
										LEFT JOIN `department` `d` ON `d`.`id` = `e`.`dept`
										WHERE `e`.`status`=1 AND DATEDIFF(`e`.`iqama_exp_g`, NOW()) <= 1
									");
								}
								if( mysqli_num_rows($result) != 0 ){

						?>
						<div class="card-box">
							<h4 class="m-t-0 header-title"><?=__('expired') ?></h4>

							<table id="datatable_exp" class="table table-striped dt-responsive nowrap" style="border-collapse: collapse; border-spacing: 0; width: 100%;">
								<thead class="thead-dark">
								<tr>
									<th><?=__('employee_id') ?></th>
									<th><?=__('name') ?></th>
									<th><?=__('iqama_id') ?></th>
									<th><?=__('department') ?></th>
									<th><?=__('country') ?></th>
									<th><?=__('iqama_id_expiry') ?></th>
									<th><?=__('days_of_expiry') ?></th>
									<th width="200"><?=__('action') ?></th>
								</tr>
								</thead>

								<tbody>
								<?php
									while($row = mysqli_fetch_assoc($result)){
										if(strtotime(date("Y/m/d")) > strtotime($row['iqama_exp_g'])){
											$from = strtotime(date("d-m-Y", strtotime($row['iqama_exp_g'])));
											$today = time();
											$difference = $from - $today;
											$daystoexp = floor($difference / 86400);  // (60 * 60 * 24)
									?>
								<tr>
									<td><?=$row['emp_id']; ?></td>
									<td><?=$row['name']; ?></td>
									<td><span class='copyToClipboard'><?=$row['iqama']?></span> <i class='fa fa-clipboard'></i></td>
									<td><?=($is_rtl ?? false ? $row['dep_nme_ar']:$row['dep_nme'])?></td>
									<td><?=$row['country']; ?></td>
									<td><?=date("d/m/Y", strtotime($row['iqama_exp_g'])); ?></td>
									<td align="center"><?=$daystoexp; ?> <?=__('days') ?></td>
									<td>
										<div class="btn-group" role="group" aria-label="Edit Button">
											<a href="view_employee.php?emp_id=<?=$row['emp_id'];?>" class="btn btn-danger m-t-20 btn-rounded waves-effect w-md waves-light btn-sm iqama_exp_hijri" data-id="<?=$row['id']?>" >
												<i class="fa fa-images-user"></i> <?=__('update_expiry') ?>
											</a>
										</div>
									</td>
								</tr>
								<?php } } ?>
								</tbody>
							</table>
						</div>
						<?php } ?>

					<?php endif; ?>
							
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


        <!-- jQuery  -->
        <script src="assets/js/jquery.min.js"></script>
        <script src="assets/js/bootstrap.bundle.min.js"></script>
        <script src="assets/js/metisMenu.min.js"></script>
        <script src="assets/js/waves.js"></script>
        <script src="assets/js/jquery.slimscroll.js"></script>

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

		<script src="./plugins/moment/moment.js"></script>
        <script src="./plugins/bootstrap-timepicker/bootstrap-timepicker.js"></script>

        <script src="./plugins/bootstrap-timepicker/hijri/bootstrap-hijri-datetimepicker.js"></script>
        <script src="./plugins/bootstrap-timepicker/hijri/bootstrap-hijri-datetimepicker.min.js"></script>
        <script src="./plugins/bootstrap-timepicker/hijri/bootstrap-hijri-datetimepickermin.js"></script>

        <script src="./plugins/bootstrap-colorpicker/js/bootstrap-colorpicker.min.js"></script>
        <script src="./plugins/clockpicker/js/bootstrap-clockpicker.min.js"></script>
        <script src="./plugins/bootstrap-daterangepicker/daterangepicker.js"></script>
        <script src="./plugins/bootstrap-datepicker/js/bootstrap-datepicker.min.js"></script>
		
        <script src="./plugins/bootstrap-filestyle/js/bootstrap-filestyle.min.js" type="text/javascript"></script>

        <!-- App js -->
		<script src="assets/pages/jquery.form-pickers.init.js"></script>
		
        <!-- App js -->
        <script src="assets/js/jquery.core.js"></script>
        <script src="assets/js/jquery.app.js"></script>

        <!-- Dashboard Init -->
        <script src="assets/pages/jquery.dashboard.init.js"></script>
		
		<script type="text/javascript">

			$('.updateExpID').click(function() {
	            $('#iid')      .val($(this).data('iid'));
	        });

            $(document).ready(function() {

                // Default Datatable
                $('#datatable').DataTable({
						lengthChange: false,
						order: [[ 5, "asc" ]],
						language: {
							search: `<span>${__('search')}:</span> _INPUT_`,
							searchPlaceholder: `${__('search')}...`,
							lengthMenu: `${__('show')} _MENU_ ${__('entries')}`,
							info: `${__('showing')} _START_ ${__('to')} _END_ ${__('of')} _TOTAL_ ${__('entries')}`,
							infoEmpty: `${__('showing')} 0 ${__('to')} 0 ${__('of')} 0 ${__('entries')}`,
							infoFiltered: `(${__('filtered_from')} _MAX_ ${__('total_entries')})`,
							paginate: {
								first: __('first'),
								last: __('last'),
								next: __('next'),
								previous: __('previous')
							},
							emptyTable: __('no_data_available_in_table'),
							zeroRecords: __('no_matching_records_found'),
							processing: `<div class="spinner-border text-primary" role="status"><span class="visually-hidden">${__('loading')}...</span></div>`
						}
					}
				);
                $('#datatable_exp').DataTable({
					language: {
						search: `<span>${__('search')}:</span> _INPUT_`,
						searchPlaceholder: `${__('search')}...`,
						lengthMenu: `${__('show')} _MENU_ ${__('entries')}`,
						info: `${__('showing')} _START_ ${__('to')} _END_ ${__('of')} _TOTAL_ ${__('entries')}`,
						infoEmpty: `${__('showing')} 0 ${__('to')} 0 ${__('of')} 0 ${__('entries')}`,
						infoFiltered: `(${__('filtered_from')} _MAX_ ${__('total_entries')})`,
						paginate: {
							first: __('first'),
							last: __('last'),
							next: __('next'),
							previous: __('previous')
						},
						emptyTable: __('no_data_available_in_table'),
						zeroRecords: __('no_matching_records_found'),
						processing: `<div class="spinner-border text-primary" role="status"><span class="visually-hidden">${__('loading')}...</span></div>`
					}
				});

                //Buttons examples
                var table = $('#datatable-buttons').DataTable({
                    lengthChange: false,
                    buttons: ['copy', 'excel', 'pdf']
                });

                // Key Tables
                $('#key-table').DataTable({
                    keys: true
                });

                // Responsive Datatable
                $('#responsive-datatable').DataTable();

                // Multi Selection Datatable
                $('#selection-datatable').DataTable({
                    select: {
                        style: 'multi'
                    }
                });
                table.buttons().container()
                        .appendTo('#datatable-buttons_wrapper .col-md-6:eq(0)');
            } );

        </script>

    </body>
</html>
<?php } ?>