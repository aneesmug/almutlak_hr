<?php
// Car detail. Same look as the Smart Request pages (assets/css/smart_request.css).
// Forms: assets/js/car_forms.js (.editCarAttr, .addMaintAttr, .addDrvrAtter, .addDocuAtter);
// return car (.addRtrnDrvrAtter) and delete (.deleteAjax) stay in assets/js/jquery.app.js.
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/session_check.php';
require_once __DIR__ . '/includes/special_access_helper.php';
$can_edit_car = !empty($is_system_admin) || user_has_special_access($conDB, $empid ?? '', 'cars_edit', $user_role ?? '', $user_type ?? '', $is_system_admin ?? false);
$query = mysqli_query($conDB, "SELECT * FROM `admin_login` WHERE `id_iqama`='" . $username . "'");
if (mysqli_num_rows($query) == 1) {
	include("./includes/avatar_select.php");

	$car_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
	$getquery = mysqli_query($conDB, "
SELECT `cars`.*, `car_maker`.`maker` AS `maker_name`, `car_maker`.`logo_pos`, `car_model`.`model`, `car_model`.`id` AS `mdid`, `car_maker`.`id` AS `mkid`
FROM `cars`
LEFT JOIN `car_maker` ON `car_maker`.`id` = `cars`.`maker_name`
LEFT JOIN `car_model` ON `car_model`.`id` = `cars`.`model`
WHERE `cars`.`id` = " . $car_id);

	if (!$getquery || mysqli_num_rows($getquery) === 0) {
		header("Location: ./all_cars.php");
		exit;
	}
	$car = mysqli_fetch_assoc($getquery);
	$id_car = (int)$car["id"];
	$maker_name = (string)$car["maker_name"];
	$model = (string)$car["model"];
	$made_year = $car["made_year"];
	$plate_no = (string)$car["plate_no"];
	$type = (string)$car["type"];
	$status = $car["status"];
	$remarks = (string)$car["remarks"];
	$logo_pos = $car["logo_pos"];
	$mdid = $car["mdid"];
	$mkid = $car["mkid"];
	$date_reg = $car["created_at"] ? date('d M Y', strtotime($car["created_at"])) : '';
	$is_active = ($status == 1);
	$plate = explode('-', $plate_no, 2);

	// Documents (newest first) + latest of each type
	$today = strtotime(date('Y-m-d'));
	$documents = [];
	$latest_doc = ['Licence' => null, 'Insurance' => null, 'MVPI' => null];
	$q = mysqli_query($conDB, "SELECT * FROM `cars_docu` WHERE `car_id`='" . $id_car . "' ORDER BY `id` DESC");
	while ($q && ($r = mysqli_fetch_assoc($q))) {
		$r['days'] = $r['exp_date'] ? (int)floor((strtotime($r['exp_date']) - $today) / 86400) : null;
		$documents[] = $r;
		if (array_key_exists($r['doc_type'], $latest_doc) && $latest_doc[$r['doc_type']] === null) {
			$latest_doc[$r['doc_type']] = $r;
		}
	}
	// Days left on the latest document of each type (missing = 0, same as before)
	$doc_days = [];
	foreach ($latest_doc as $k => $d) { $doc_days[$k] = $d ? $d['days'] : 0; }
	$tone_for = function ($days) { return $days < 7 ? 'red' : ($days <= 30 ? 'amber' : 'green'); };

	// Drivers
	$drivers = [];
	$current_driver = null;
	$q = mysqli_query($conDB, "SELECT `cars_drv`.*, `employees`.`name` FROM `cars_drv` LEFT JOIN `employees` ON `cars_drv`.`car_user` = `employees`.`emp_id` WHERE `cars_drv`.`car_id`='" . $id_car . "' ORDER BY `cars_drv`.`id` DESC");
	while ($q && ($r = mysqli_fetch_assoc($q))) {
		$drivers[] = $r;
		if ($r['status'] == 1 && $current_driver === null) { $current_driver = $r; }
	}

	// Maintenance
	$maintenance = [];
	$q = mysqli_query($conDB, "SELECT `cars_maint`.*, `employees`.`name` FROM `cars_maint` LEFT JOIN `employees` ON `cars_maint`.`car_user`=`employees`.`emp_id` WHERE `car_id`='" . $id_car . "' ORDER BY `cars_maint`.`id` DESC");
	while ($q && ($r = mysqli_fetch_assoc($q))) { $maintenance[] = $r; }
	$last_meter = $maintenance ? (int)$maintenance[0]['meter'] : null;

	$can_delete_children = ($user_type == $access1 or $user_type == $access2);
	$show_add_docs = $is_active && (45 > $doc_days['Licence'] or 45 > $doc_days['Insurance'] or 45 > $doc_days['MVPI']);
	$h = function ($v) { return htmlspecialchars((string)$v, ENT_QUOTES); };
	$fmt_date = function ($v) { return $v ? date('d M Y', strtotime($v)) : ''; };
	$doc_label = ['Licence' => __('licence_label', 'Licence'), 'Insurance' => __('insurance_label', 'Insurance'), 'MVPI' => __('mvpi_label', 'MVPI')];
	$doc_icon = ['Licence' => 'mdi-account-card-details', 'Insurance' => 'mdi-security', 'MVPI' => 'mdi-clipboard-check'];
?>
	<!doctype html>
	<html lang="<?= $current_lang ?? 'en' ?>" <?= ($is_rtl ?? false) ? 'dir="rtl"' : '' ?>>

	<head>
		<meta charset="utf-8" />
		<title><?= $site_title ?> - <?= $h(trim($maker_name . ' ' . $model) . ' ' . $plate_no) ?></title>
		<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
		<meta content="Anees Afzal" name="author" />
		<meta http-equiv="X-UA-Compatible" content="IE=edge" />

		<link rel="shortcut icon" href="<?=get_setting($conDB, 'favicon')?>">

		<!-- Plugins css -->
		<link href="./plugins/bootstrap-datepicker/css/bootstrap-datepicker.min.css" rel="stylesheet">
		<link href="./plugins/select2/css/select2.min.css" rel="stylesheet" type="text/css" />
		<!-- DataTables -->
		<link href="./plugins/datatables/dataTables.bootstrap4.min.css" rel="stylesheet" type="text/css" />
		<link href="./plugins/datatables/buttons.bootstrap4.min.css" rel="stylesheet" type="text/css" />
		<link href="./plugins/datatables/responsive.bootstrap4.min.css" rel="stylesheet" type="text/css" />

		<!-- App css -->
		<link href="assets/css/bootstrap.min.css" rel="stylesheet" type="text/css" />
		<link href="assets/css/icons.css" rel="stylesheet" type="text/css" />
		<link href="assets/css/metismenu.min.css" rel="stylesheet" type="text/css" />
		<link href="assets/css/style.css" rel="stylesheet" type="text/css" />
		<link href="assets/css/style_dark.css" rel="stylesheet" type="text/css" />
		<link href="assets/css/smart_request.css?v=<?= @filemtime(__DIR__ . '/assets/css/smart_request.css') ?>" rel="stylesheet" type="text/css" />
		<script src="assets/js/modernizr.min.js"></script>
		<style>
			.car-hero-plate { flex: 0 0 auto; transform: scale(.82); transform-origin: top left; margin: 0 -40px -22px 0; }
			[dir="rtl"] .car-hero-plate { transform-origin: top right; margin: 0 0 -22px -40px; }
			.car-hero-plate .plate-text-top, .car-hero-plate .plate-text-bottom { color: #000; }
			.car-hero-plate div.plateTb { border-color: var(--sr-border-strong) !important; box-shadow: var(--sr-shadow); }
			.car-brand {
				flex: 0 0 auto; width: 150px; padding: 10px; border-radius: 12px; text-align: center;
				border: 1px solid var(--sr-border); background: #fff; color: #334155; font-size: 12px; font-weight: 600;
			}
			.car-brand .make-logo {
				display: block; width: 130px; height: 60px; margin: 0 auto 4px;
				background: url("./assets/images/make_desktop_logos.png") no-repeat 0 60px;
			}
			.car-meter { font-variant-numeric: tabular-nums; }
		</style>
		<?php if ($is_rtl): ?>
			<link href="assets/css/style_rtl.css" rel="stylesheet" type="text/css" />
		<?php endif; ?>
		<script>
			window.lang = <?= json_encode($GLOBALS['translations'] ?? []) ?>;
		</script>
	</head>

	<body class="enlarged" data-keep-enlarged="true">
		<div id="wrapper">
			<div class="left side-menu">
				<div class="slimscroll-menu" id="remove-scroll">
					<div class="topbar-left">
						<a href="dashboard.php" class="logo">
							<span><img src="<?=get_setting($conDB, 'logo')?>" alt="" height="22"></span>
							<i><img src="<?=get_setting($conDB, 'white_logo')?>" alt="" height="28"></i>
						</a>
					</div>
					<?php include("./includes/main_menu.php"); ?>
					<div class="clearfix"></div>
				</div>
			</div>

			<div class="content-page">
				<?php include("./includes/topbar.php"); ?>

				<div class="content sr-page">
					<div class="container-fluid">

						<!-- ===== Header ===== -->
						<div class="sr-card sr-hero">
							<a href="./all_cars.php" class="sr-back"><i class="mdi mdi-arrow-left"></i> <?= __('all_cars', 'All cars') ?></a>
							<div class="sr-hero-top">
								<div class="d-flex align-items-center flex-wrap" style="gap: 20px; min-width: 0; flex: 1 1 520px;">
									<div class="car-hero-plate ad-keep">
										<div class="plateTb centerAlignObj">
											<div class="row containerTb">
												<div class="col-12 centerAlignObj">
													<div class="row plate-content-new">
														<div class="col-4 pltgrid plate-section-new">
															<div class="plate-text-top"><?= $h($plate[0] ?? '') ?></div>
															<div class="plate-divider-h"></div>
															<div class="plate-text-bottom"><?= $h($plate[1] ?? '') ?></div>
														</div>
														<div class="col-4 pltgrid plate-section-new plate-logo">
															<img src="./assets/cars_documents/logo.png" height="60" alt="" />
														</div>
														<div class="col-4 pltgrid plate-section-new">
															<div class="plate-text-top plateNumberValAr"><?= $h($plate[0] ?? '') ?></div>
															<div class="plate-divider-h"></div>
															<div class="plate-text-bottom plateNumberDigAr"><?= $h(strtolower($plate[1] ?? '')) ?></div>
														</div>
													</div>
												</div>
											</div>
										</div>
									</div>
									<div style="min-width: 0;">
										<div class="sr-hero-tags">
											<span class="sr-chip sr-mono"><i class="mdi mdi-pound"></i><?= $id_car ?></span>
											<span class="sr-pill sr-pill-lg <?= $is_active ? 'tone-green' : 'tone-slate' ?>"><span class="sr-dot"></span><?= $is_active ? __('active', 'Active') : __('inactive', 'Inactive') ?></span>
											<span class="sr-chip"><i class="mdi mdi-car"></i><?= $h(__(strtolower(str_replace(' ', '_', $type)), $type)) ?></span>
										</div>
										<h1 class="sr-hero-title"><?= $h(trim($maker_name . ' ' . $model) ?: '-') ?></h1>
										<div class="sr-card-sub">
											<i class="mdi mdi-calendar"></i> <?= __('made_year') ?>: <b><?= $h($made_year) ?></b>
											&nbsp;&middot;&nbsp; <i class="mdi mdi-calendar-check"></i> <?= __('date_registration_label', 'Registered') ?>: <?= $h($date_reg) ?>
											<?php if ($last_meter !== null): ?>&nbsp;&middot;&nbsp; <i class="mdi mdi-speedometer"></i> <span class="car-meter"><?= number_format($last_meter) ?> km</span><?php endif; ?>
										</div>
									</div>
								</div>
								<div class="car-brand ad-keep">
									<i class="make-logo" style="background-position: <?= $h($logo_pos) ?>;"></i>
									<?= $h($maker_name) ?>
								</div>
							</div>

							<?php if (trim($remarks) !== ''): ?>
								<div class="sr-remarks"><strong><?= __('remarks_label', 'Remarks') ?>:</strong><?= $h($remarks) ?></div>
							<?php endif; ?>

							<div class="sr-stats">
								<div class="sr-stat <?= $current_driver ? 'is-sky' : '' ?>">
									<div class="sr-stat-label"><?= __('driver_label', 'Driver') ?> <i class="mdi mdi-steering"></i></div>
									<?php if ($current_driver): ?>
										<div class="sr-stat-value" title="<?= $h($current_driver['name']) ?>"><?= $h($current_driver['name'] ?: $current_driver['car_user']) ?></div>
										<div class="sr-stat-sub"><?= __('receive_date_label', 'Received') ?>: <?= $h($fmt_date($current_driver['rcv_date'])) ?></div>
									<?php else: ?>
										<div class="sr-stat-value text-muted"><?= __('no_driver_text', 'No driver') ?></div>
										<div class="sr-stat-sub">&nbsp;</div>
									<?php endif; ?>
								</div>
								<?php foreach ($latest_doc as $k => $d): $days = $doc_days[$k]; ?>
									<div class="sr-stat is-<?= $tone_for($days) ?>">
										<div class="sr-stat-label"><?= $h($doc_label[$k]) ?> <i class="mdi <?= $doc_icon[$k] ?>"></i></div>
										<?php if (!$d): ?>
											<div class="sr-stat-value"><?= __('not_added', 'Not added') ?></div>
											<div class="sr-stat-sub">&nbsp;</div>
										<?php else: ?>
											<div class="sr-stat-value"><?= $days < 0 ? __('expired', 'Expired') : $days . ' ' . __('days_text', 'days') ?></div>
											<div class="sr-stat-sub"><?= __('expiry_date_label', 'Expiry') ?>: <?= $h($fmt_date($d['exp_date'])) ?></div>
										<?php endif; ?>
									</div>
								<?php endforeach; ?>
							</div>

							<div class="sr-hero-actions">
								<?php if ($is_active): ?>
									<?php if (!$current_driver): ?>
										<a href="javascript:void(0);" class="sr-btn sr-btn-sm sr-btn-primary addDrvrAtter" data-id="<?= $id_car ?>"><i class="mdi mdi-account-plus"></i> <?= __('add_driver_button', 'Assign driver') ?></a>
									<?php else: ?>
										<a href="javascript:void(0);" class="sr-btn sr-btn-sm addRtrnDrvrAtter" data-id="<?= (int)$current_driver['id'] ?>" data-cid="<?= $id_car ?>"><i class="mdi mdi-car-convertable"></i> <?= __('return_car_button', 'Return car') ?></a>
									<?php endif; ?>
									<a href="javascript:void(0);" class="sr-btn sr-btn-sm addMaintAttr" data-id="<?= $id_car ?>" data-caruser="<?= $h($current_driver['car_user'] ?? '') ?>"><i class="mdi mdi-wrench"></i> <?= __('add_maintenance_button', 'Add maintenance') ?></a>
									<?php if ($show_add_docs): ?>
										<a href="javascript:void(0);" class="sr-btn sr-btn-sm addDocuAtter" data-id="<?= $id_car ?>"><i class="mdi mdi-file-document"></i> <?= __('add_docs_button', 'Add document') ?></a>
									<?php endif; ?>
								<?php endif; ?>
								<span class="sr-spacer"></span>
								<?php if ($can_edit_car): ?>
									<a href="javascript:void(0);" class="sr-btn sr-btn-sm editCarAttr" data-id="<?= $id_car ?>" data-maker_name="<?= $h($mkid) ?>" data-model="<?= $h($mdid) ?>" data-made_year="<?= $h($made_year) ?>" data-plate_no="<?= $h($plate_no) ?>" data-type="<?= $h($type) ?>" data-remarks="<?= $h($remarks) ?>" data-status="<?= $h($status) ?>"><i class="mdi mdi-pencil"></i> <?= __('edit_button', 'Edit') ?></a>
								<?php endif; ?>
							</div>
						</div>

						<!-- ===== History ===== -->
						<div class="sr-card">
							<ul class="nav sr-tabs" id="carDetailTabs" role="tablist">
								<li class="nav-item"><a href="#docsTab" data-toggle="tab" class="nav-link active"><i class="mdi mdi-file-document"></i> <?= __('documents_details_header', 'Documents') ?> <span class="sr-count"><?= count($documents) ?></span></a></li>
								<li class="nav-item"><a href="#driversTab" data-toggle="tab" class="nav-link"><i class="mdi mdi-steering"></i> <?= __('drivers_details_header', 'Drivers') ?> <span class="sr-count"><?= count($drivers) ?></span></a></li>
								<li class="nav-item"><a href="#maintTab" data-toggle="tab" class="nav-link"><i class="mdi mdi-wrench"></i> <?= __('maintenance_details_header', 'Maintenance') ?> <span class="sr-count"><?= count($maintenance) ?></span></a></li>
							</ul>
							<div class="tab-content">
								<!-- Documents -->
								<div class="tab-pane active" id="docsTab">
									<div class="sr-tab-tools">
										<div id="docsButtons" class="sr-toolbar" style="padding: 0; border: 0;"></div>
										<?php if ($is_active): ?>
											<a href="javascript:void(0);" class="sr-btn sr-btn-sm sr-btn-ghost addDocuAtter" data-id="<?= $id_car ?>"><i class="mdi mdi-plus"></i> <?= __('add_docs_button', 'Add document') ?></a>
										<?php endif; ?>
									</div>
									<div class="sr-table-wrap">
										<table id="cars_docu" class="table sr-table dt-responsive nowrap" style="width: 100%;">
											<thead>
												<tr>
													<th><?= __('documents_type_header', 'Type') ?></th>
													<th><?= __('issue_date_header', 'Issue date') ?></th>
													<th><?= __('expiry_date_header', 'Expiry date') ?></th>
													<th><?= __('status_header', 'Status') ?></th>
													<th><?= __('attachment_header', 'Attachment') ?></th>
													<th><?= __('reg_date_header', 'Added') ?></th>
													<th class="text-right"><?= __('action') ?></th>
												</tr>
											</thead>
											<tbody>
												<?php foreach ($documents as $doc):
													$is_latest = isset($latest_doc[$doc['doc_type']]) && $latest_doc[$doc['doc_type']]['id'] == $doc['id'];
													$days = $doc['days'];
												?>
													<tr>
														<td><span class="sr-person-name"><i class="mdi <?= $doc_icon[$doc['doc_type']] ?? 'mdi-file' ?> mr-1" style="color: var(--sr-accent);"></i><?= $h($doc_label[$doc['doc_type']] ?? $doc['doc_type']) ?></span></td>
														<td><?= $h($fmt_date($doc['issue_date'])) ?></td>
														<td data-order="<?= $h($doc['exp_date']) ?>"><?= $h($fmt_date($doc['exp_date'])) ?></td>
														<td>
															<?php if ($days === null): ?>
																<span class="text-muted">&ndash;</span>
															<?php elseif (!$is_latest): ?>
																<span class="sr-pill sr-pill-xs tone-slate"><?= __('replaced', 'Replaced') ?></span>
															<?php elseif ($days < 0): ?>
																<span class="sr-pill sr-pill-xs tone-red"><span class="sr-dot"></span><?= __('expired', 'Expired') ?></span>
															<?php else: ?>
																<span class="sr-pill sr-pill-xs tone-<?= $tone_for($days) ?>"><span class="sr-dot"></span><?= $days ?> <?= __('days_left', 'days left') ?></span>
															<?php endif; ?>
														</td>
														<td>
															<?php if ($doc['file']): ?>
																<a href="javascript:void(0);" onclick="displayPopup(<?= $h(json_encode('./assets/cars_documents/' . $doc['file'])) ?>)" class="sr-open-btn"><i class="mdi mdi-paperclip"></i> <?= __('view_file_button', 'View file') ?></a>
															<?php else: ?>
																<span class="text-muted"><?= __('no_file_text', 'No file') ?></span>
															<?php endif; ?>
														</td>
														<td><?= $h($fmt_date($doc['created_at'])) ?></td>
														<td class="text-right">
															<?php if ($can_delete_children): ?>
																<a href="javascript:void(0);" class="sr-more-btn deleteAjax" title="<?= __('delete') ?>" data-id="<?= (int)$doc['id'] ?>" data-tbl="cars_docu" data-file="1" data-column="file"><i class="mdi mdi-delete text-danger"></i></a>
															<?php endif; ?>
														</td>
													</tr>
												<?php endforeach; ?>
											</tbody>
										</table>
									</div>
								</div>

								<!-- Drivers -->
								<div class="tab-pane" id="driversTab">
									<div class="sr-tab-tools">
										<div id="drvButtons" class="sr-toolbar" style="padding: 0; border: 0;"></div>
										<?php if ($is_active && !$current_driver): ?>
											<a href="javascript:void(0);" class="sr-btn sr-btn-sm sr-btn-ghost addDrvrAtter" data-id="<?= $id_car ?>"><i class="mdi mdi-plus"></i> <?= __('add_driver_button', 'Assign driver') ?></a>
										<?php endif; ?>
									</div>
									<div class="sr-table-wrap">
										<table id="cars_drvs" class="table sr-table dt-responsive nowrap" style="width: 100%;">
											<thead>
												<tr>
													<th><?= __('drivers_name_header', 'Driver') ?></th>
													<th><?= __('receiving_header', 'Received') ?></th>
													<th><?= __('return_date_header', 'Returned') ?></th>
													<th class="text-center"><?= __('days_text', 'Days') ?></th>
													<th><?= __('status_header', 'Status') ?></th>
													<th><?= __('created_at_header', 'Added') ?></th>
													<th class="text-right"><?= __('action') ?></th>
												</tr>
											</thead>
											<tbody>
												<?php foreach ($drivers as $drv):
													$on_job = ($drv['status'] == 1);
													$from = $drv['rcv_date'] ? strtotime($drv['rcv_date']) : null;
													$to = $drv['rtn_date'] ? strtotime($drv['rtn_date']) : time();
													$span = $from ? max(0, (int)floor(($to - $from) / 86400)) : null;
												?>
													<tr>
														<td>
															<span class="sr-person-name"><?= $h($drv['name'] ?: '-') ?></span>
															<span class="sr-cell-sub sr-mono"><?= $h($drv['car_user']) ?></span>
														</td>
														<td data-order="<?= $h($drv['rcv_date']) ?>"><?= $h($fmt_date($drv['rcv_date'])) ?></td>
														<td><?= $drv['rtn_date'] ? $h($fmt_date($drv['rtn_date'])) : '<span class="text-muted">' . __('on_job_text', 'On job') . '</span>' ?></td>
														<td class="text-center"><?= $span === null ? '&ndash;' : '<span class="sr-count">' . $span . '</span>' ?></td>
														<td><?= $on_job
															? '<span class="sr-pill sr-pill-xs tone-sky"><span class="sr-dot"></span>' . __('on_driving_text', 'Driving') . '</span>'
															: '<span class="sr-pill sr-pill-xs tone-slate"><span class="sr-dot"></span>' . __('returned_text', 'Returned') . '</span>' ?></td>
														<td><?= $h($fmt_date($drv['created_at'])) ?></td>
														<td class="text-right">
															<?php if ($can_delete_children): ?>
																<a href="javascript:void(0);" class="sr-more-btn deleteAjax" title="<?= __('delete') ?>" data-id="<?= (int)$drv['id'] ?>" data-tbl="cars_drv" data-file="0"><i class="mdi mdi-delete text-danger"></i></a>
															<?php endif; ?>
														</td>
													</tr>
												<?php endforeach; ?>
											</tbody>
										</table>
									</div>
								</div>

								<!-- Maintenance -->
								<div class="tab-pane" id="maintTab">
									<div class="sr-tab-tools">
										<div id="maintButtons" class="sr-toolbar" style="padding: 0; border: 0;"></div>
										<?php if ($is_active): ?>
											<a href="javascript:void(0);" class="sr-btn sr-btn-sm sr-btn-ghost addMaintAttr" data-id="<?= $id_car ?>" data-caruser="<?= $h($current_driver['car_user'] ?? '') ?>"><i class="mdi mdi-plus"></i> <?= __('add_maintenance_button', 'Add maintenance') ?></a>
										<?php endif; ?>
									</div>
									<div class="sr-table-wrap">
										<table id="cars_maint" class="table sr-table dt-responsive nowrap" style="width: 100%;">
											<thead>
												<tr>
													<th><?= __('date_header', 'Date') ?></th>
													<th><?= __('type_of_maint_header', 'Type') ?></th>
													<th><?= __('drivers_name_header', 'Driver') ?></th>
													<th class="text-right"><?= __('meter_reading_header', 'Meter') ?></th>
													<th class="text-right"><?= __('difference_reading_header', 'Difference') ?></th>
													<th><?= __('details_header', 'Details') ?></th>
													<th><?= __('remarks_header', 'Remarks') ?></th>
													<th><?= __('created_header', 'Added') ?></th>
													<th class="text-right"><?= __('action') ?></th>
												</tr>
											</thead>
											<tbody>
												<?php foreach ($maintenance as $m):
													$diff = (int)preg_replace('/[^\d-]/', '', (string)$m['diffmeter']);
												?>
													<tr>
														<td data-order="<?= $h($m['date']) ?>"><?= $h($fmt_date($m['date'])) ?></td>
														<td><span class="sr-chip"><?= $h($m['type']) ?></span></td>
														<td><?= $h($m['name'] ?: $m['car_user']) ?></td>
														<td class="text-right car-meter sr-mono" data-order="<?= (int)$m['meter'] ?>"><?= number_format((int)$m['meter']) ?></td>
														<td class="text-right car-meter" data-order="<?= $diff ?>"><?= $diff > 0 ? '+' . number_format($diff) . ' km' : '<span class="text-muted">' . number_format($diff) . ' km</span>' ?></td>
														<td style="white-space: normal; min-width: 180px;"><?= $h($m['details']) ?></td>
														<td style="white-space: normal;"><?= $h($m['remarks']) ?></td>
														<td><?= $h($fmt_date($m['created_at'])) ?></td>
														<td class="text-right">
															<?php if ($can_delete_children): ?>
																<a href="javascript:void(0);" class="sr-more-btn deleteAjax" title="<?= __('delete') ?>" data-id="<?= (int)$m['id'] ?>" data-tbl="cars_maint" data-file="0"><i class="mdi mdi-delete text-danger"></i></a>
															<?php endif; ?>
														</td>
													</tr>
												<?php endforeach; ?>
											</tbody>
										</table>
									</div>
								</div>
							</div>
						</div>

					</div>
				</div>

				<footer class="footer">
					<?= $site_footer ?>
				</footer>
			</div>
		</div>

		<!-- jQuery  -->
		<script src="assets/js/jquery.min.js"></script>
		<script src="assets/js/bootstrap.bundle.min.js"></script>
		<script src="assets/js/metisMenu.min.js"></script>
		<script src="assets/js/waves.js"></script>
		<script src="assets/js/jquery.slimscroll.js"></script>
		<script src="./plugins/moment/moment.js"></script>
		<script src="./plugins/bootstrap-datepicker/js/bootstrap-datepicker.min.js"></script>
		<script src="./plugins/select2/js/select2.min.js" type="text/javascript"></script>

		<!-- Required datatable js -->
		<script src="./plugins/datatables/jquery.dataTables.min.js"></script>
		<script src="./plugins/datatables/dataTables.bootstrap4.min.js"></script>
		<script src="./plugins/datatables/dataTables.buttons.min.js"></script>
		<script src="./plugins/datatables/buttons.bootstrap4.min.js"></script>
		<script src="./plugins/datatables/jszip.min.js"></script>
		<script src="./plugins/datatables/pdfmake.min.js"></script>
		<script src="./plugins/datatables/vfs_fonts.js"></script>
		<script src="./plugins/datatables/buttons.html5.min.js"></script>
		<script src="./plugins/datatables/buttons.print.min.js"></script>
		<script src="./plugins/datatables/dataTables.responsive.min.js"></script>
		<script src="./plugins/datatables/responsive.bootstrap4.min.js"></script>

		<!-- App js -->
		<script src="assets/js/jquery.app.js?t=<?= time() ?>"></script>
		<script src="assets/js/sr_forms.js?v=<?= @filemtime(__DIR__ . '/assets/js/sr_forms.js') ?>"></script>
		<script src="assets/js/car_forms.js?v=<?= @filemtime(__DIR__ . '/assets/js/car_forms.js') ?>"></script>

		<script type="text/javascript">
			$(document).ready(function() {
				const exportTitle = <?= json_encode(__('plate_no_label', 'Plate no') . ': ' . $plate_no) ?>;
				const language = {
					info: `${__('showing')} _START_ ${__('to')} _END_ ${__('of')} _TOTAL_ ${__('entries')}`,
					infoEmpty: `${__('showing')} 0 ${__('to')} 0 ${__('of')} 0 ${__('entries')}`,
					infoFiltered: '',
					paginate: { first: __('first'), last: __('last'), next: '<i class="mdi mdi-chevron-right"></i>', previous: '<i class="mdi mdi-chevron-left"></i>' },
					emptyTable: `<div class="sr-empty"><i class="mdi mdi-inbox"></i>${__('no_data_available_in_table')}</div>`,
					zeroRecords: `<div class="sr-empty"><i class="mdi mdi-magnify"></i>${__('no_matching_records_found')}</div>`
				};
				function makeTable(sel, holder, columns, actionCol) {
					const dt = $(sel).DataTable({
						dom: 'Brtip', pageLength: 10, responsive: true, order: [],
						columnDefs: [{ targets: [actionCol], orderable: false }],
						buttons: [
							{ extend: 'excel', text: '<i class="mdi mdi-file-excel"></i> Excel', exportOptions: { columns: columns }, title: exportTitle },
							{ extend: 'pdf', text: '<i class="mdi mdi-file-pdf"></i> PDF', exportOptions: { columns: columns }, title: exportTitle },
							{ extend: 'print', text: '<i class="mdi mdi-printer"></i> ' + <?= json_encode(__('print')) ?>, exportOptions: { columns: columns }, title: exportTitle }
						],
						language: language
					});
					dt.buttons().container().appendTo(holder);
					return dt;
				}
				makeTable('#cars_docu', '#docsButtons', [0, 1, 2, 3, 5], 6);
				makeTable('#cars_drvs', '#drvButtons', [0, 1, 2, 3, 4, 5], 6);
				makeTable('#cars_maint', '#maintButtons', [0, 1, 2, 3, 4, 5, 6, 7], 8);

				$('#carDetailTabs a[data-toggle="tab"]').on('shown.bs.tab', function() {
					$.fn.dataTable.tables({ visible: true, api: true }).columns.adjust().responsive.recalc();
				});
			});
		</script>
	</body>

	</html>
<?php } ?>
