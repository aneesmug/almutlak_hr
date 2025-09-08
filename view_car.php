<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/session_check.php';
$query = mysqli_query($conDB, "SELECT * FROM `admin_login` WHERE `id_iqama`='" . $username . "'");
if (mysqli_num_rows($query) == 1) {
	include("./includes/avatar_select.php");

	$getquery = mysqli_query($conDB, "
SELECT `cars`.*, `car_maker`.`maker` AS `maker_name`, `car_maker`.`logo_pos`, `car_model`.`model`, `car_model`.`id` AS `mdid`, `car_maker`.`id` AS `mkid`
FROM `cars`
LEFT JOIN `car_maker` ON `car_maker`.`id` = `cars`.`maker_name`
LEFT JOIN `car_model` ON `car_model`.`id` = `cars`.`model` 
WHERE `cars`.`id` = '" . $_GET['id'] . "'

");

	if (mysqli_num_rows($getquery) !== 0) {
		while ($rec = mysqli_fetch_assoc($getquery)) {
			$id_car = $rec["id"];
			$maker_name = $rec["maker_name"];
			$model = $rec["model"];
			$made_year = $rec["made_year"];
			$plate_no = $rec["plate_no"];
			$type = $rec["type"];
			$status = $rec["status"];
			$remarks = $rec["remarks"];
			$logo_pos = $rec["logo_pos"];
			$datereg = $rec["created_at"];

			$mdid = $rec["mdid"];
			$mkid = $rec["mkid"];

			$timestamp_reg = strtotime("$datereg");
			$date_reg = date('d, M Y', $timestamp_reg);
		}
	} else {
		//when the id not equals id show database
		header("Location: ./all_cars.php");
	}

	/********************Start***********************/
	$today = strtotime(date('M d Y', strtotime(date('M d Y', strtotime(date("c"))))));
	/********************Start Licence***********************/
	$licqry = mysqli_query($conDB, "SELECT * FROM `cars_docu` WHERE `doc_type` = 'Licence' AND `car_id`='" . $_GET['id'] . "' ORDER BY `id` DESC LIMIT 1 ");
	while ($rec_lic = mysqli_fetch_assoc($licqry)) {
		$exp_date_lic = $rec_lic["exp_date"];
		$exp_lic = date('d, M Y', strtotime($exp_date_lic));
	}
	$licdate = strtotime(date('M d Y', strtotime(date('M d Y', strtotime($exp_date_lic)))));
	$secs_lic = $licdate - $today; // == <seconds between the two times>
	$licdays = $secs_lic / 86400;
	/********************End Licence***********************/
	/********************Start Insurance***********************/
	$incqry = mysqli_query($conDB, "SELECT * FROM `cars_docu` WHERE `doc_type` = 'Insurance' AND `car_id`='" . $_GET['id'] . "' ORDER BY `id` DESC LIMIT 1 ");
	while ($rec_inc = mysqli_fetch_assoc($incqry)) {
		$exp_date_inc = $rec_inc["exp_date"];
		$exp_inc = date('d, M Y', strtotime($exp_date_inc));
	}
	$incdate = strtotime(date('M d Y', strtotime(date('M d Y', strtotime($exp_date_inc)))));
	$secs_inc = $incdate - $today; // == <seconds between the two times>
	$incdays = $secs_inc / 86400;
	/********************End Insurance***********************/
	/********************Start MVPI***********************/
	$mvpiqry = mysqli_query($conDB, "SELECT * FROM `cars_docu` WHERE `doc_type` = 'MVPI' AND `car_id`='" . $_GET['id'] . "' ORDER BY `id` DESC LIMIT 1 ");
	while ($rec_mvpi = mysqli_fetch_assoc($mvpiqry)) {
		$exp_date_mvpi = $rec_mvpi["exp_date"];
		$exp_mvpi = date('d, M Y', strtotime($exp_date_mvpi));
	}
	$mvpidate = strtotime(date('M d Y', strtotime(date('M d Y', strtotime($exp_date_mvpi)))));
	$secs_mvpi = $mvpidate - $today; // == <seconds between the two times>
	$mvpidays = $secs_mvpi / 86400;
	/********************End MVPI***********************/
	/********************End***********************/
	$sql_yes_drv = mysqli_query($conDB, "SELECT COUNT(*) `car_id` FROM `cars_drv` WHERE `car_id`='" . $_GET['id'] . "' && `status`='1' ");
	$cont_drv = mysqli_fetch_array($sql_yes_drv)[0];

	$sql_drv = mysqli_query($conDB, "SELECT `cars_drv`.*, `employees`.`name` FROM `cars_drv` LEFT JOIN `employees` ON `cars_drv`.`car_user` = `employees`.`emp_id` WHERE `cars_drv`.`car_id`='" . $_GET['id'] . "' && `cars_drv`.`status`='1' ");
	while ($rec = mysqli_fetch_assoc($sql_drv)) {
		$car_drv_id = $rec["id"];
		$car_drv_car_id = $rec["car_id"];
		$car_undrv_name = $rec["name"];
		$car_udrv_id = $rec["car_user"];
		//		$car_rcv_date = $rec["rcv_date"];
		$car_rcv_date = date('d, M Y', strtotime($rec["rcv_date"]));
	}

?>
	<!doctype html>
	<html lang="<?= $current_lang ?? 'en' ?>" <?= ($is_rtl ?? false) ? 'dir="rtl"' : '' ?>>

	<head>
		<meta charset="utf-8" />
		<title><?= $site_title ?> - <?= __('car_view_title') ?></title>
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
		<link href="./plugins/bootstrap-select/css/bootstrap-select.min.css" rel="stylesheet" />
		<link href="./plugins/select2/css/select2.min.css" rel="stylesheet" type="text/css" />
		<!-- DataTables -->
		<link href="./plugins/datatables/dataTables.bootstrap4.min.css" rel="stylesheet" type="text/css" />
		<link href="./plugins/datatables/buttons.bootstrap4.min.css" rel="stylesheet" type="text/css" />
		<!-- Responsive datatable examples -->
		<link href="./plugins/datatables/responsive.bootstrap4.min.css" rel="stylesheet" type="text/css" />

		<!-- Multi Item Selection examples -->
		<link href="./plugins/datatables/select.bootstrap4.min.css" rel="stylesheet" type="text/css" />

		<link href="./plugins/bootstrap-timepicker/hijri_css/bootstrap-datetimepicker.css" rel="stylesheet">
		<link href="./plugins/bootstrap-timepicker/hijri_css/bootstrap-datetimepicker.min.css" rel="stylesheet">

		<!-- App css -->
		<link href="assets/css/bootstrap.min.css" rel="stylesheet" type="text/css" />
		<link href="assets/css/icons.css" rel="stylesheet" type="text/css" />
		<link href="assets/css/metismenu.min.css" rel="stylesheet" type="text/css" />
		<link href="assets/css/style.css" rel="stylesheet" type="text/css" />
		<link href="assets/css/style_dark.css" rel="stylesheet" type="text/css" />
		<script src="assets/js/modernizr.min.js"></script>

		<style type="text/css">
			.make-logo {
				background-image: url("./assets/images/make_desktop_logos.png");
				display: block;
				background-repeat: no-repeat;
				height: 60px;
				margin: 0 auto;
				vertical-align: middle;
				width: 130px !important;
				background-position: 0px 60px;
			}

			.brand {
				width: 152px;
				height: 100%;
				border-radius: 4px;
				background: #fff;
				border: 0.5px solid #f0f0f0;
				box-shadow: 0px 3px 4px rgba(0, 0, 0, 0.08);
				padding: 16px;
				text-align: center;
				line-height: normal;
				font-size: 14px;
			}
		</style>
		<?php if ($is_rtl): ?>
			<link href="assets/css/style_rtl.css" rel="stylesheet" type="text/css" />
		<?php endif; ?>
		<script>
			window.lang = <?= json_encode($GLOBALS['translations'] ?? []) ?>;
		</script>

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
								<div class="profile-user-box card-box <?= ($status == 1) ? "bg-custom-mocha" : "bg-danger"; ?>">
									<div class="row">
										<div class="col-sm-4">


											<div class="plateTb centerAlignObj">
												<div class="row containerTb">
													<div class="col-12 centerAlignObj">
														<div class="row plate-content-new">
															<!-- English Section -->
															<div class="col-4 pltgrid plate-section-new">
																<div class="plate-text-top"><?= explode("-", $plate_no)[0] ?></div>
																<div class="plate-divider-h"></div>
																<div class="plate-text-bottom"><?= explode("-", $plate_no)[1] ?></div>
															</div>

															<!-- Logo Section -->
															<div class="col-4 pltgrid plate-section-new plate-logo">
																<img src="./assets/cars_documents/logo.png" height="60" />
															</div>

															<!-- Arabic Section -->
															<div class="col-4 pltgrid plate-section-new">
																<div class="plate-text-top plateNumberValAr"><?= explode("-", $plate_no)[0] ?></div>
																<div class="plate-divider-h"></div>
																<div class="plate-text-bottom plateNumberDigAr"><?= strtolower(explode("-", $plate_no)[1]) ?></div>
															</div>
														</div>
													</div>
												</div>
											</div>


										</div>
										<div class="col-sm-4">
											<div class="media-body text-black">

												<div class="brand">
													<i class="make-logo" style="background-position: <?= $logo_pos ?>;"></i>
													<div class="p4t color-grayblack"><?= $maker_name ?> - <?= $model ?></div>
													<div class="p4t color-grayblack"><?= $made_year ?></div>
													<div class="p4t color-grayblack"></div>
												</div>

												<?php /* ?>
                                                <h4 class="mt-1 mb-1 font-18 carinfo"><?=__('maker_name_label')?>: <span><?=$maker_name ?></span></h4>
                                                <h5 class="mt-1 mb-1 font-16 carinfo"><?=__('model_label')?>: <span><?=$model?></span></h5>
                                                <h5 class="mt-1 mb-1 font-16 carinfo"><?=__('made_year_label')?>: <span><?=$made_year?></span></h5>
                                                <?php */ ?>
												<?php if ($remarks !== ""): ?>
													<p class="font-13 text-light"><?= __('remarks_label') ?>: <?= $remarks ?></p>
												<?php endif ?>
											</div>
										</div>
										<div class="col-sm-4">
											<div class="text-left text-white">
												<h4 class="mt-1 mb-1 font-18 carinfo"><?= __('plate_no_label') ?>: <span><?= $plate_no ?></span></h4>
												<h5 class="mt-1 mb-1 font-16 carinfo"><?= __('type_label') ?>: <span><?= $type ?></span></h5>
												<p class="font-14 text-light"><?= __('date_registration_label') ?>: <?= $date_reg ?></p>
												<div class="plateNumberValAr plateNumberDigAr"><? //=str_replace("-", " ", strtolower($plate_no))
																								?></div>
											</div>
											<div class="text-right">
												<?php if ($status == 1) { ?>
													<div class="btn-group" role="group" aria-label="Edit Button">
														<?php if (45 > $licdays or 45 > $incdays or 45 > $mvpidays) { ?>
															<a href="javascript:void(0);" class="btn btn-sm btn-primary waves-effect addDocuAtter" data-id="<?= $id_car ?>">
																<i class="mdi mdi-library-plus"></i> <?= __('add_docs_button') ?>
															</a>
														<?php } ?>
														<?php if ($cont_drv < 1) { ?>
															<a href="javascript:void(0);" class="btn btn-sm btn-success waves-effect addDrvrAtter" data-id="<?= $id_car ?>">
																<i class="mdi mdi-human-greeting"></i> <?= __('add_driver_button') ?>
															</a>
														<?php } else { ?>
															<a href="javascript:void(0);" class="btn btn-sm btn-danger waves-effect addRtrnDrvrAtter" data-id="<?= $car_drv_id ?>" data-cid="<?= $id_car ?>">
																<i class="mdi mdi-car-convertable"></i> <?= __('return_car_button') ?>
															</a>
														<?php } ?>
														<a href="javascript:void(0);" class="btn btn-sm btn-dark waves-effect addMaintAttr" data-id="<?= $id_car ?>" data-caruser="<?= $car_udrv_id ?>">
															<i class="fa fa-solid fa-screwdriver-wrench"></i> <?= __('add_maintenance_button') ?>
														</a>
													<?php } ?>
													<a href="javascript:void:(0);" class="btn btn-sm btn-light waves-effect editCarAttr" data-id="<?= $id_car ?>" data-maker_name="<?= $mkid ?>" data-model="<?= $mdid ?>" data-made_year="<?= $made_year ?>" data-plate_no="<?= $plate_no ?>" data-type="<?= $type ?>" data-remarks="<?= $remarks ?>" data-status="<?= $status ?>">
														<i class="fa fa-edit"></i> <?= __('edit_button') ?>
													</a>
													</div>
													<!-- <a href="javascript:void(0);" onclick="addCustomerAtter()">Open</a> -->
											</div>
										</div>
									</div>
								</div>
								<!--/ meta -->

							</div>


						</div>

						<div class="row">
							<div class="col-xl-12">
								<div class="row">

									<div class="col-sm-3">
										<div class="card-box tilebox-one <?= ($cont_drv < 1) ? "bg-danger" : "bg-success"; ?>" style="height:130px !important;">
											<i class="mdi mdi-car-sports float-right"></i>
											<h4 class="text-uppercase mt-0"><?= __('driver_label') ?></h4>
											<h4 class="m-b-20" data-plugin="counterup"><?= ($cont_drv < 1) ? "<h2>" . __('no_driver_text') . "</h2>" : (explode(" ", $car_undrv_name)[0]) . " " . (explode(" ", $car_undrv_name)[1]); ?></h4>
											<?= __('receive_date_label') ?>: <div class="float-right"><?= $car_rcv_date ?></div>
										</div>
									</div><!-- end col -->

									<div class="col-sm-3">
										<div class="card-box tilebox-one <?= (7 > $licdays ? "bg-danger" : (30 >= $licdays ? "bg-warning" : "bg-success")) ?>" style="height:130px !important;">
											<i class="dripicons-wallet float-right"></i>
											<h4 class="text-uppercase mt-0"><?= __('licence_label') ?></h4>
											<h2 class="m-b-20" data-plugin="counterup"><?= ($exp_date_lic == "") ? "0" : $licdays ?> <?= __('days_text') ?></h2>
											<?= __('expiry_date_label') ?>: <div class="float-right"><?= $exp_lic ?></div>
										</div>
									</div><!-- end col -->

									<div class="col-sm-3">
										<div class="card-box tilebox-one <?= (7 > $incdays ? "bg-danger" : (30 >= $incdays ? "bg-warning" : "bg-success")) ?>" style="height:130px !important;">
											<i class="mdi mdi-clipboard-text float-right"></i>
											<h4 class="text-uppercase mt-0"><?= __('insurance_label') ?></h4>
											<h2 class="m-b-20"><span data-plugin="counterup"><?= ($exp_date_inc == "") ? "0" : $incdays ?> <?= __('days_text') ?></span></h2>
											<?= __('expiry_date_label') ?>: <div class="float-right"><?= $exp_inc ?></div>
										</div>
									</div><!-- end col -->

									<div class="col-sm-3">
										<div class="card-box tilebox-one <?= (7 > $mvpidays ? "bg-danger" : (30 >= $mvpidays ? "bg-warning" : "bg-success")) ?>" style="height:130px !important;">
											<i class="mdi mdi-autorenew float-right"></i>
											<h4 class="text-uppercase mt-0"><?= __('mvpi_label') ?></h4>
											<h2 class="m-b-20" data-plugin="counterup"><?= ($exp_date_mvpi == "") ? "0" : $mvpidays ?> <?= __('days_text') ?></h2>
											<?= __('expiry_date_label') ?>: <div class="float-right"><?= $exp_mvpi ?></div>
										</div>
									</div><!-- end col -->

								</div>
							</div>
						</div>

						<div class="row">
							<div class="col-12">
								<div class="card-box table-responsive">
									<h4 class="m-t-0 header-title"><?= __('documents_details_header') ?></h4>
									<table id="cars_docu" class="table table-striped table-bordered dt-responsive nowrap" style="border-collapse: collapse; border-spacing: 0; width: 100%;">
										<thead>
											<tr>
												<th><?= __('id') ?></th>
												<th><?= __('documents_type_header') ?></th>
												<th><?= __('issue_date_header') ?></th>
												<th><?= __('expiry_date_header') ?></th>
												<th><?= __('attachment_header') ?></th>
												<th><?= __('reg_date_header') ?></th>
												<?php if ($user_type == $access1 or $user_type == $access2) { ?>
													<th width="60"><?= __('action') ?></th>
												<?php } ?>
											</tr>
										</thead>
										<tbody>
											<?php

											$query_cardoc = mysqli_query($conDB, "SELECT * FROM `cars_docu` WHERE `car_id`='" . $_GET['id'] . "' ");
											while ($rec = mysqli_fetch_array($query_cardoc)) {
												$id_car_doc = $rec["id"];
												$car_id_doc = $rec["car_id"];
												$doc_type_doc = $rec["doc_type"];
												$issue_date_doc = $rec["issue_date"];
												$exp_date_doc = $rec["exp_date"];
												$file_doc = $rec["file"];
												$dateregdoc = $rec["created_at"];

												$date_reg_doc = date('d, M Y', strtotime($dateregdoc));

											?>
												<tr>
													<th><?= $id_car_doc; ?></th>
													<th><?= $doc_type_doc; ?></th>
													<th><?= $issue_date_doc; ?></th>
													<th><?= $exp_date_doc; ?></th>
													<th><?= ($file_doc) ? "<a href=\"javascript:displayPopup('./assets/cars_documents/" . "$file_doc')\" class='btn btn-primary btn-sm'><i class='fa fa-paperclip'></i> " . __('view_file_button') . "</a>" : "<a href='javascript:void(0)' class='btn btn-dark btn-sm'><i class='fa fa-link-slash'></i> " . __('no_file_text') . "</a>" ?>
													</th>
													<th><?= $date_reg_doc; ?></th>
													<?php if ($user_type == $access1 or $user_type == $access2) { ?>
														<th>
															<div class='btn-group dropdown'>
																<a href='javascript: void(0);' class='table-action-btn dropdown-toggle arrow-none btn btn-light btn-sm' data-toggle='dropdown' aria-expanded='false'><i class='mdi mdi-dots-horizontal'></i></a>
																<div class='dropdown-menu dropdown-menu-right' x-placement='bottom-end'>
																	<a href='javascript:void(0);' class='dropdown-item text-danger deleteAjax' data-id='<?= $rec['id'] ?>' data-tbl='cars_docu' data-file='1' data-column='file'><i class='fa fa-trash mr-2 font-18 vertical-middle'></i><?= __('delete') ?></a>
																</div>
															</div>
														</th>
													<?php } ?>

												</tr>
											<?php } ?>
										</tbody>
									</table>
								</div>
							</div>
						</div>
						<div class="row">
							<div class="col-12">
								<div class="card-box table-responsive">
									<h4 class="m-t-0 header-title"><?= __('drivers_details_header') ?></h4>
									<table id="cars_drvs" class="table table-striped table-bordered dt-responsive nowrap" style="border-collapse: collapse; border-spacing: 0; width: 100%;">
										<thead>
											<tr>
												<th><?= __('id') ?></th>
												<th><?= __('drivers_name_header') ?></th>
												<th><?= __('receiving_header') ?></th>
												<th><?= __('return_date_header') ?></th>
												<th><?= __('status_header') ?></th>
												<th><?= __('created_at_header') ?></th>
												<?php if ($user_type == $access1 or $user_type == $access2) { ?>
													<th width="60"><?= __('action') ?></th>
												<?php } ?>

											</tr>
										</thead>
										<tbody>
											<?php

											$query_cardrv = mysqli_query($conDB, "SELECT `cars_drv`.*,`cars_drv`.`id` AS `cdrid`, `employees`.`name` FROM `cars_drv` LEFT JOIN `employees` ON `cars_drv`.`car_user` = `employees`.`emp_id` WHERE `car_id`='" . $_GET['id'] . "' ");
											while ($rec = mysqli_fetch_array($query_cardrv)) {
												$id_car_drv = $rec["id"];
												$cdrid = $rec['cdrid'];
												$car_id_drv = $rec["car_id"];
												$drv_name_drv = $rec["name"];
												$rcv_date_drv = $rec["rcv_date"];
												$rtndatedrv = $rec["rtn_date"];
												$status_drv = $rec["status"];
												$date_reg_drv = date('d, M Y', strtotime($rec["created_at"]));

												if ($rtndatedrv == "") {
													$rtn_date_drv = __('on_job_text');
												} else {
													$rtn_date_drv =  date('d, M Y', strtotime($rtndatedrv));
												}

											?>
												<tr>
													<th><?= $id_car_drv; ?></th>
													<th><?= $drv_name_drv; ?></th>
													<th><?= $rcv_date_drv; ?></th>
													<th><?= $rtn_date_drv; ?></th>
													<th><?= ($status_drv == 1) ? "<span class='badge badge-success'>" . __('on_driving_text') . "</span>" : "<span class='badge badge-danger'>" . __('returned_text') . "</span>" ?></th>
													<th><?= $date_reg_drv; ?></th>
													<?php if ($user_type == $access1 or $user_type == $access2) { ?>
														<th>
															<div class='btn-group dropdown'>
																<a href='javascript: void(0);' class='table-action-btn dropdown-toggle arrow-none btn btn-light btn-sm' data-toggle='dropdown' aria-expanded='false'><i class='mdi mdi-dots-horizontal'></i></a>
																<div class='dropdown-menu dropdown-menu-right' x-placement='bottom-end'>
																	<a href='javascript:void(0);' class='dropdown-item text-danger deleteAjax' data-id='<?= $cdrid ?>' data-tbl='cars_drv' data-file='0'><i class='fa fa-trash mr-2 font-18 vertical-middle'></i><?= __('delete') ?></a>
																</div>
															</div>
														</th>
													<?php } ?>

												</tr>
											<?php } ?>
										</tbody>
									</table>
								</div>
							</div>
						</div>


					</div> <!-- container -->

					<div class="row">
						<div class="col-12">
							<div class="card-box table-responsive">
								<h4 class="m-t-0 header-title"><?= __('maintenance_details_header') ?></h4>
								<table id="cars_maint" class="table table-striped table-bordered dt-responsive nowrap" style="border-collapse: collapse; border-spacing: 0; width: 100%;">
									<thead>
										<tr>
											<th><?= __('id') ?></th>
											<th><?= __('drivers_name_header') ?></th>
											<th><?= __('meter_reading_header') ?></th>
											<th><?= __('difference_reading_header') ?></th>
											<th><?= __('date_header') ?></th>
											<th><?= __('type_of_maint_header') ?></th>
											<th><?= __('details_header') ?></th>
											<th><?= __('remarks_header') ?></th>
											<th><?= __('created_header') ?></th>
											<?php if ($user_type == $access1 or $user_type == $access2) { ?>
												<th width="60"><?= __('action') ?></th>
											<?php } ?>

										</tr>
									</thead>
									<tbody>
										<?php

										$query_cardrv = mysqli_query($conDB, "SELECT `cars_maint`.*, `employees`.`name` FROM `cars_maint` LEFT JOIN `employees` ON `cars_maint`.`car_user`=`employees`.`emp_id` WHERE `car_id`='" . $_GET['id'] . "' ");
										while ($rec = mysqli_fetch_array($query_cardrv)) {
											$id_car_maint 	= $rec["id"];
											$drv_name_maint 	= $rec["name"];
											$meter_maint 	= $rec["meter"];
											$diffmeter_maint 	= $rec["diffmeter"];
											$date_maint 	= $rec["date"];
											$type_maint 	= $rec["type"];
											$details_maint 	= $rec["details"];
											$remarks_maint 	= $rec["remarks"];
											$created_at_maint 	= $rec["created_at"];
											$created_at_maint =  date('d, M Y', strtotime($created_at_maint));

										?>
											<tr>
												<th><?= $id_car_maint; ?></th>
												<th><?= $drv_name_maint; ?></th>
												<th><?= $meter_maint; ?></th>
												<th><?= $diffmeter_maint; ?></th>
												<th><?= $date_maint; ?></th>
												<th><?= $type_maint; ?></th>
												<th><?= $details_maint; ?></th>
												<th><?= $remarks_maint; ?></th>
												<th><?= $created_at_maint; ?></th>
												<?php if ($user_type == $access1 or $user_type == $access2) { ?>
													<th>
														<div class='btn-group dropdown'>
															<a href='javascript: void(0);' class='table-action-btn dropdown-toggle arrow-none btn btn-light btn-sm' data-toggle='dropdown' aria-expanded='false'><i class='mdi mdi-dots-horizontal'></i></a>
															<div class='dropdown-menu dropdown-menu-right' x-placement='bottom-end'>
																<a href='javascript:void(0);' class='dropdown-item text-danger deleteAjax' data-id='<?= $rec['id'] ?>' data-tbl='cars_maint' data-file='0'><i class='fa fa-trash mr-2 font-18 vertical-middle'></i><?= __('delete') ?></a>
															</div>
														</div>
													</th>
												<?php } ?>
											</tr>
										<?php } ?>
									</tbody>
								</table>
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
		<!-- <script src="./plugins/bootstrap-inputmask/bootstrap-inputmask.min.js" type="text/javascript"></script> -->
		<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.inputmask/3.3.4/jquery.inputmask.bundle.min.js" type="text/javascript"></script>
		<script src="./plugins/autoNumeric/autoNumeric.js" type="text/javascript"></script>


		<script src="./plugins/moment/moment.js"></script>
		<script src="./plugins/bootstrap-timepicker/bootstrap-timepicker.js"></script>
		<script src="./plugins/bootstrap-colorpicker/js/bootstrap-colorpicker.min.js"></script>
		<script src="./plugins/clockpicker/js/bootstrap-clockpicker.min.js"></script>
		<script src="./plugins/bootstrap-daterangepicker/daterangepicker.js"></script>
		<script src="./plugins/bootstrap-datepicker/js/bootstrap-datepicker.min.js"></script>

		<!-- App js -->
		<script src="assets/pages/jquery.form-pickers.init.js"></script>
		<script src="assets/pages/jquery.form-hijri-pickers.init.js"></script>
		<script src="./plugins/bootstrap-timepicker/hijri/bootstrap-hijri-datetimepicker.js"></script>
		<script src="./plugins/bootstrap-timepicker/hijri/bootstrap-hijri-datetimepicker.min.js"></script>
		<script src="./plugins/bootstrap-timepicker/hijri/bootstrap-hijri-datetimepickermin.js"></script>

		<script src="./plugins/select2/js/select2.min.js" type="text/javascript"></script>
		<script src="./plugins/bootstrap-select/js/bootstrap-select.js" type="text/javascript"></script>

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
		<!-- <script src="assets/js/jquery.core.js"></script> -->
		<script src="assets/js/jquery.app.js"></script>

		<script type="text/javascript">
			jQuery(function($) {
				$('.autonumber').autoNumeric('init');
			});
			$(document).ready(function() {
				$('form').parsley();

				//Buttons examples
				var table = $('#cars_docu').DataTable({
					lengthChange: false,
					buttons: ['copy', 'excel', 'pdf', 'print'],
					order: [
						[0, "desc"]
					],
					"columnDefs": [{
						targets: [0],
						visible: false,
						searchable: false
					}, ],
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

				table.buttons().container()
					.appendTo('#cars_docu_wrapper .col-md-6:eq(0)');

			});
			$(document).ready(function() {
				$('form').parsley();

				//Buttons examples
				var table = $('#cars_drvs').DataTable({
					lengthChange: false,
					buttons: ['copy', 'excel', 'pdf', 'print'],
					order: [
						[0, "desc"]
					],
					"columnDefs": [{
						targets: [0],
						visible: false,
						searchable: false
					}, ],
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

				table.buttons().container()
					.appendTo('#cars_drvs_wrapper .col-md-6:eq(0)');

			});

			$(document).ready(function() {
				$('form').parsley();
				//Buttons examples
				var table = $('#cars_maint').DataTable({
					lengthChange: false,
					buttons: ['copy', 'excel', 'pdf', 'print'],
					order: [
						[0, "desc"]
					],
					"columnDefs": [{
						targets: [0],
						visible: false,
						searchable: false
					}, ],
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
				table.buttons().container()
					.appendTo('#cars_maint_wrapper .col-md-6:eq(0)');

			});

			jQuery.browser = {};
			(function() {
				jQuery.browser.msie = false;
				jQuery.browser.version = 0;
				if (navigator.userAgent.match(/MSIE ([0-9]+)\./)) {
					jQuery.browser.msie = true;
					jQuery.browser.version = RegExp.$1;
				}
			})();

			$(document).ready(function() {
				$("input[name$='note']").click(function() {
					var value = $(this).val();
					if (value == 'Encashed') {
						$("#return_date").show();
						$("#note").hide();
						$("#return_date").removeAttr('required');
						$("#permit_no").removeAttr('required');
					} else if (value == 'Fly') {
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
		</script>

	</body>

	</html>
<?php } ?>