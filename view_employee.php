<?php

/****************************************************************
 * MODIFICATION SUMMARY (001-view_employee.php):
 * 1. FIXED ADMINISTRATOR ACCESS: Modified the department access check to grant access to system administrators (`user_type` = 'administrator').
 * 2. ADDED `$is_system_admin` CHECK: The access logic now includes the `$is_system_admin` variable (defined in `session_check.php`). This ensures that an administrator can view any employee's profile, regardless of their department, fixing the "You don't have access" error.
 ****************************************************************/

/*******************************************************************************************************************
 * MODIFICATION SUMMARY (004-view_employee.php):
 *
 * 1. ADDED EMERGENCY LOAN BUTTON: A new "Emergency Loan" button is now present next to the regular loan button.
 * - It has the class `applyEmergencyLoan` to trigger the new JavaScript function in `loanHandling.js`.
 * 2. UPDATED PAYMENT HISTORY TABLE: The "Payment History" table now includes columns for "Receipt ID" and "Attachment".
 * - It will display the receipt ID if available.
 * - It will show a "View" button to open the attachment in a new tab if one exists.
 * 3. SIMPLIFIED LOAN BUTTON: The "Apply for Loan" button's data attributes have been simplified. It now only
 * requires `data-emp_id`. The salary and other details are now securely fetched on the server-side by `ajaxLoan.php`
 * when the loan process begins, preventing data manipulation on the client-side.
 * 4. ADDED LOAN TYPE DISPLAY: The "Loan History" table now has a "Type" column to show whether a loan is 'Regular' or 'Emergency'.
 * 5. CONDITIONAL BUTTON DISPLAY: The "Apply for Loan" and "Emergency Loan" buttons are now conditionally displayed.
 * - The regular loan button is hidden if the employee has an active regular loan.
 * - The emergency loan button is hidden if the employee has an active emergency loan.
 *******************************************************************************************************************/

/*******************************************************************************************************************
 * MODIFICATION SUMMARY (005-view_employee.php):
 *
 * 1. ADDED LOAN SUMMARY CALCULATION: Added PHP logic to fetch the employee's active approved loan details from the `emp_loan` table.
 * 2. CALCULATED PAID AND REMAINING AMOUNTS: The script queries the `emp_loan_payments` table to calculate the total amount paid against the loan and determines the remaining balance.
 * 3. POPULATED `$loan_summary` VARIABLE: A new `$loan_summary` array is created containing the total payable, total paid, remaining balance, and disbursement details. This fixes the issue where the loan summary section was not displaying because the variable was missing.
 *******************************************************************************************************************/

// require_once __DIR__ . '/includes/db.php';
// require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/session_check.php';

$query = mysqli_query($conDB, "SELECT * FROM `admin_login` WHERE `id_iqama`='" . $username . "'");
if (mysqli_num_rows($query) == 1) {
	include("./includes/avatar_select.php");

	require("./includes/vacation_processor.php");

	include("./includes/Hijri_GregorianConvert.php");
	$DateConv = new Hijri_GregorianConvert;
	// $format="DD/MM/YYYY";
	$format = "YYYY-MM-DD";
	require("./includes/emp_query.php");

	if (mysqli_num_rows($get_emp_data) !== 0) {
		$allRecords = mysqli_fetch_all($get_emp_data, MYSQLI_ASSOC);
		foreach ($allRecords as $rec) {
			$emprow = $rec;
		}

		// --- START: Loan Summary Calculation ---
		$loan_summary = null;
		$sql_active_loan = "SELECT id, total_payable, disbursement_receipt_id, disbursement_attachment FROM `emp_loan` WHERE `emp_id`='" . $emprow['empid'] . "' AND `status` = 'approved' ORDER BY `id` DESC LIMIT 1";
		$query_active_loan = mysqli_query($conDB, $sql_active_loan);

		if (mysqli_num_rows($query_active_loan) > 0) {
			$active_loan_data = mysqli_fetch_assoc($query_active_loan);
			$loan_id = $active_loan_data['id'];

			$sql_total_paid = "SELECT COALESCE(SUM(amount), 0) as total_paid FROM `emp_loan_payments` WHERE `loan_id` = '$loan_id'";
			$query_total_paid = mysqli_query($conDB, $sql_total_paid);
			$paid_data = mysqli_fetch_assoc($query_total_paid);

			$total_paid = $paid_data['total_paid'];
			$total_payable = $active_loan_data['total_payable'];
			$remaining_balance = $total_payable - $total_paid;

			$loan_summary = [
				'total_payable' => $total_payable,
				'total_paid' => $total_paid,
				'remaining_balance' => $remaining_balance,
				'disbursement_receipt' => $active_loan_data['disbursement_receipt_id'],
				'disbursement_attachment' => $active_loan_data['disbursement_attachment']
			];
		}
		// --- END: Loan Summary Calculation ---

		// debug($emprow);

		// Get the current user's department and type from session  
		$target_dept = $emprow["dept"] ?? 0;
		// The main fix is adding $is_system_admin to this check.
		$hasAccess = ($user_dept == $target_dept) || $isHR || $isDeptHr || $is_system_admin;
		if (!$hasAccess) {
			$_SESSION['error_msg'] = sprintf(
				'<div class="col-xl-12">
					<div class="alert alert-danger bg-danger text-white border-0" role="alert">
						<b>Error ooooh!</b> 
						<h4>You don\'t have access for ( %s ) Department.</h4>
					</div>
				</div>',
				$emprow["deptnme"]
			);
			header("Location: ./dashboard.php");
			exit;
		}
		// If we get here, access is granted

		$salary_get = str_replace(',', '', ($emprow['basic'] + $emprow['housing'] + $emprow['transport'] + $emprow["food"] + $emprow["misc"] + $emprow["cashier"] + $emprow["fuel"] + $emprow["tel"] + $emprow["other"] + $emprow["guard"]));

		$hours_in_day   = 24;
		$minutes_in_hour = 60;
		$seconds_in_mins = 60;
		$birth_date     = new DateTime($emprow["dob"]);
		$current_date   = new DateTime();
		$diff           = $birth_date->diff($current_date);
		$years	   		= $diff->y . " " . __('years');
		// $vacyear_get = preg_replace("/[^0-9]/", "", $emprow["period"]);

		if ($emprow["status"] == 0 && $emprow["note"] == "expired") {
			$note_get = "Expired";
		} elseif ($emprow["status"] == 0 && $emprow["note"] == "terminat") {
			$note_get = "Terminated";
		}
	} else {
		header("Location: ./reg_employee.php");
	}

	$date = $DateConv->HijriToGregorian($emprow['iqama_exp'], $format);
	$exprydte = date('m-', strtotime($date)); //
	$today = date('m');

	$salaryItems = ['basic', 'housing', 'transport', 'food', 'misc', 'cashier', 'fuel', 'tel', 'other', 'guard'];
	// $salaryItems = ['أساسي'،'سكن'،'مواصلات'،'طعام'،'متنوع'،'صراف'،'وقود'،'هاتف'،'أخرى'،'حارس'];

	$shownItems = [];
	foreach ($salaryItems as $item) {
		if (!empty($emprow[$item]) && $emprow[$item] != "0") {
			$shownItems[] = $item;
		}
	}
	$countItems = count($shownItems); // Salary items only
	$totalBoxes = $countItems + 1; // +1 for Total Salary box
	$colsm = "col-sm-" . floor(12 / $totalBoxes); // Default column for all boxes
	// Special case: if 5 items, give Total Salary more space (col-sm-4)
	$totalColsm = ($countItems == 4) ? "col-sm-4" : $colsm;

	$join_date		= new DateTime($emprow['joining_date']);
	$curr_date  	= new DateTime();
	$joindiff		= $join_date->diff($curr_date);

	$probationStatus = ($emprow['probation'] !== NULL && $emprow['probation'] !== "")
		? (($joindiff->days > ((int)$emprow['probation'] * 30))
			? __('no_probation')
			: $emprow['probation'] . " Months")
		: (($joindiff->days < 90)
			? __('under_probation')
			: __('no_probation'));

	$all_statuses = [
		'apply' => __('new_request'),
		'pending' => __('assistant_pending'),
		'hr_assistant_approved' => __('hr_assistant_approved'),
		'hr_manager_approved' => __('hr_manager_approved'),
		'gm_approved' => __('gm_approved'),
		'rejected' => __('rejected'),
	];

?>
	<!doctype html>
	<html lang="<?= $current_lang ?? 'en' ?>" <?= ($is_rtl ?? false) ? 'dir="rtl"' : '' ?>>

	<head>
		<meta charset="utf-8" />
		<title><?= $site_title ?> - View Employee <?= $emprow['name'] ?> Details</title>
		<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
		<!--        <meta content="A fully featured admin theme which can be used to build CRM, CMS, etc." name="description" />-->
		<meta content="Anees Afzal" name="author" />
		<meta http-equiv="X-UA-Compatible" content="IE=edge" />
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />

		<!-- App favicon -->
		<link rel="shortcut icon" href="assets/images/favicon.ico">

		<!-- Modal -->
		<link href="./plugins/custombox/css/custombox.min.css" rel="stylesheet">

		<!-- Plugins css -->
		<link href="./plugins/bootstrap-timepicker/bootstrap-timepicker.min.css" rel="stylesheet">
		<link href="./plugins/bootstrap-datepicker/css/bootstrap-datepicker.min.css" rel="stylesheet">
		<link href="./plugins/clockpicker/css/bootstrap-clockpicker.min.css" rel="stylesheet">
		<link href="./plugins/bootstrap-daterangepicker/daterangepicker.css" rel="stylesheet">

		<link rel="stylesheet" href="./plugins/bootstrap-select/css/bootstrap-select.min.css">
		<link rel="stylesheet" href="./plugins/select2/css/select2.min.css">

		<!-- <link href="./plugins/bootstrap-daterangepicker/daterangepicker.css" rel="stylesheet"> -->
		<!-- <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.0.13/dist/css/select2.min.css" /> -->

		<!-- DataTables -->
		<link href="./plugins/datatables/dataTables.bootstrap4.min.css" rel="stylesheet" type="text/css" />
		<link href="./plugins/datatables/buttons.bootstrap4.min.css" rel="stylesheet" type="text/css" />
		<!-- Responsive datatable examples -->
		<link href="./plugins/datatables/responsive.bootstrap4.min.css" rel="stylesheet" type="text/css" />

		<!-- Multi Item Selection examples -->
		<link href="./plugins/datatables/select.bootstrap4.min.css" rel="stylesheet" type="text/css" />

		<link href="./plugins/summernote/summernote.min.css" rel="stylesheet" />

		<!-- App css -->
		<link href="assets/css/bootstrap.min.css" rel="stylesheet" type="text/css" />
		<!-- <link href="assets/css/icons.css" rel="stylesheet" type="text/css" /> -->
		<link href="assets/css/metismenu.min.css" rel="stylesheet" type="text/css" />
		<link href="assets/css/style.css" rel="stylesheet" type="text/css" />
		<link href="assets/css/style_dark.css" rel="stylesheet" type="text/css" />
		<script src="assets/js/modernizr.min.js"></script>

		<link rel="stylesheet" href="./plugins/croppie/croppie.css">
		<style type="text/css">
			.card-box.social {
				box-shadow: 0 1px 2px rgba(0, 0, 0, 0.15);
				transition: all 0.2s ease-in-out;
				border-radius: 10px !important;
			}

			.card-box.social:hover {
				box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
				transform: scale(1.005);
				cursor: pointer;
			}
		</style>
		<?php if ($is_rtl): ?>
			<link href="assets/css/style_rtl.css" rel="stylesheet" type="text/css" />
		<?php endif; ?>
		<script>
			window.lang = <?= json_encode($GLOBALS['translations'] ?? []) ?>;
		</script>
	</head>

	<body class="enlarged" data-keep-enlarged="true" data-page="view-employee">

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
						<?php //echo $thismonthexp 
						?>
						<?php include("./includes/emp_top_info.php"); ?>
						<div class="row">
							<div class="col-xl-12">

								<div class="row">
									<?php foreach ($shownItems as $item): ?>
										<div class="<?= $colsm ?>">
											<div class="card-box tilebox-one">
												<?php
												$icons = [
													'basic' => 'fa-money-bill-alt duotone-success',
													'housing' => 'fa-home duotone-info',
													'transport' => 'fa-car duotone-danger',
													'food' => 'fa-money-bill-wheat duotone-info',
													'misc' => 'fa-diamond-half duotone-dark',
													'cashier' => 'fa-cash-register duotone-success',
													'fuel' => 'fa-car-wash duotone-info',
													'tel' => 'fa-user-headset duotone-info',
													'other' => 'fa-person-carry duotone-dark',
													'guard' => 'fa-hands-holding-diamond duotone-success',
												];
												$icon = $icons[$item] ?? 'fa-money-bill duotone-secondary';
												?>
												<i class="fad <?= $icon ?> float-right"></i>
												<h6 class="text-muted text-uppercase mt-0"><?= __($item, ucfirst($item)) ?></h6>
												<h2 class="m-b-20" data-plugin="counterup"><?= $emprow[$item] ?> <i class="icon-saudi_riyal"></i></h2>
											</div>
										</div>
									<?php endforeach; ?>
									<div class="<?= $totalColsm ?>">
										<div class="card-box tilebox-one">
											<i class="fad fa-money-bill-trend-up float-right duotone-success"></i>
											<h6 class="text-muted text-uppercase mt-0"><?= __('total_salary') ?></h6>
											<h2 class="m-b-20" data-plugin="counterup"><?= (!$salary_get) ? $emprow["salary"] : $salary_get ?> <i class="icon-saudi_riyal"></i></h2>
										</div>
									</div>
								</div><!-- end row -->

								<?php if ($emprow["emp_sup_type"] <> "man_power") { ?>

									<div class="row">
										<div class="col-sm-6">
											<div class="card-box tilebox-one">
												<i class="fad fa-truck-plane float-right duotone-info"></i>
												<h6 class="text-uppercase mt-0 text-muted">
													<?php 
														if ($emprow["country"] == 191 or $emprow["country"] == 150) {
															echo __('encashed');
														} else {
															echo __('flys');
														} 
													?>
												</h6>
												<h2 class="m-b-20" data-plugin="counterup"><?= $emprow['flystus'] ?></h2>
											</div>
										</div><!-- end col -->
										<div class="col-sm-6">
											<div class="card-box tilebox-one">
												<i class="fad fa-money-from-bracket float-right duotone-info"></i>
												<h6 class="text-uppercase mt-0 text-muted"><?= __('encashed') ?></h6>
												<h2 class="m-b-20"><span data-plugin="counterup"><?= $emprow['encashstus'] ?></span></h2>
											</div>
										</div><!-- end col -->
									</div><!-- end col -->

								<?php } ?>


							</div>
						</div>


						<div class="row">
							<div class="col-12">
								<div class="card-box">
									<div class="d-flex justify-content-between align-items-center mb-3">
										<h4 class="header-title m-t-0"><?= __('employee_information') ?></h4>
										<div class="btn-group" role="group">
											<?php /*if (empty($emprow['has_active_regular_loan'])) : ?>
											    <button type="button" class="btn btn-success waves-effect waves-light applyLoan" data-emp_id="<?= $emprow['empid'] ?>">
												    <i class="mdi mdi-cash-plus"></i> Apply for Loan<?=__('flys') ?>
											    </button>
                                            <?php endif; ?>
                                            <?php if (empty($emprow['has_active_emergency_loan'])) : ?>
                                                <button type="button" class="btn btn-warning waves-effect waves-light applyEmergencyLoan" data-emp_id="<?= $emprow['empid'] ?>">
												    <i class="mdi mdi-flash"></i> Emergency Loan<?=__('flys') ?>
											    </button>
                                            <?php endif; */ ?>
											<?php /* ?>
											<button type="button" class="btn btn-info waves-effect waves-light" onclick="assignAsset('<?= $emprow['empid'] ?>')">
												<i class="mdi mdi-plus-circle-outline"></i><?=__('assign_asset') ?>
											</button>
											<?php */ ?>
										</div>
									</div>
									<ul class="nav nav-pills navtab-bg nav-justified pull-in ">

										<li class="nav-item">
											<a href="#profile1" data-toggle="tab" aria-expanded="true" class="nav-link active show">
												<i class="fi-head mr-2"></i><?= __('profile') ?>
											</a>
										</li>
										<?php /*if($user_type <> "dept_user"){*/ ?>
										<li class="nav-item">
											<a href="#messages1" data-toggle="tab" aria-expanded="false" class="nav-link">
												<i class="mdi mdi-bank mr-2"></i> <?= __('bank_&_gosi_details') ?>
											</a>
										</li>
										<li class="nav-item">
											<a href="#home1" data-toggle="tab" aria-expanded="false" class="nav-link">
												<i class="mdi mdi-buffer mr-2"></i> <?= __('vacation_details') ?>
											</a>
										</li>
										<li class="nav-item">
											<a href="#loan1" data-toggle="tab" aria-expanded="false" class="nav-link">
												<i class="mdi mdi-cash-multiple mr-2"></i> <?= __('loan_details') ?>
											</a>
										</li>
										<li class="nav-item">
											<a href="#assets" data-toggle="tab" aria-expanded="false" class="nav-link">
												<i class="mdi mdi-cash-multiple mr-2"></i> <?= __('assets_details') ?>
											</a>
										</li>
										<li class="nav-item">
											<a href="#documents" data-toggle="tab" aria-expanded="false" class="nav-link">
												<i class="mdi mdi-book-open-page-variant mr-2"></i> <?= __('documents') ?> <?= ($emprow['docs_count'] > 0) ? "(" . $emprow['docs_count'] . ")" : "" ?>
											</a>
										</li>
										<li class="nav-item">
											<a href="#noties" data-toggle="tab" aria-expanded="false" class="nav-link">
												<i class="mdi mdi-book-open-page-variant mr-2"></i> <?= __('notes') ?> <?= ($emprow['empnote'] > 0) ? "(" . $emprow['empnote'] . ")" : "" ?>
											</a>
										</li>
										<?php /*}*/ ?>

										<?php /* ?>	
	<li class="nav-item">
		<a href="#attendance" data-toggle="tab" aria-expanded="false" class="nav-link">
			<i class="mdi mdi-fingerprint mr-2"></i> Attendance
		</a>
	</li>
	<?php */ ?>
									</ul>
									<div class="tab-content">
										<!-- Profile -->
										<div class="tab-pane active show" id="profile1">
											<table class="table table-hover mb-0">
												<tbody>
													<tr>
														<th><?= __('name_of_employee') ?>:</th>
														<td><span class="copyToClipboard"><?= $emprow['name']; ?></span> <i class="fa fa-clipboard"></i></td>
														<th><?= __('email') ?>:</th>
														<td><?= ($emprow['c_email']) ? "<b>" . __('personal') . "</b> : <span class='copyToClipboard'>" . $emprow['email'] . "</span> <i class='fa fa-clipboard'></i> | <b>" . __('company') . "</b> : <span class='copyToClipboard'>" . $emprow['c_email'] . "</span> <i class='fa fa-clipboard'></i>" : "<span class='copyToClipboard'>" . $emprow['email'] . "</span> <i class='fa fa-clipboard'></i>" ?></td>
													</tr>
													<tr>
														<th><?= __('iqama_id') ?>:</th>
														<td><span class="copyToClipboard"><?= $emprow['iqama']; ?></span> <i class="fa fa-clipboard"></i></td>
														<th><?= __('id_expiry') ?>:</th>
														<td>
															<span class="date-batch-h" data-prefix="<?=__('hijri') ?>"><?= $emprow['iqama_exp']; ?></span>
															<span class="date-batch-g float-right" data-prefix="<?=__('gregorian') ?>"><?= $DateConv->HijriToGregorian($emprow['iqama_exp'], $format); ?></span>
														</td>
													</tr>
													<tr>
														<th><?= __('passport_no') ?>:</th>
														<td>
															<span class="copyToClipboard"><?= $emprow['passport_number']; ?></span> <i class="fa fa-clipboard"></i>
														</td>
														<th><?= __('passport_expiry') ?>:</th>
														<td>
															<?php if ($emprow['passport_exp']): ?>
																<span class="date-batch-g" data-prefix="<?=__('gregorian') ?>"><?= $emprow['passport_exp']; ?></span>
															<?php endif ?>
															<!-- <span class="date-batch-h float-right"><? //=$DateConv->GregorianToHijri($emprow['passport_exp'], $format); 
																										?></span> -->
															<?php if ($emprow['passport_exp']): ?>
																<span class="date-batch-h float-right" data-prefix="<?=__('hijri') ?>"><?= $DateConv->GregorianToHijri($emprow['passport_exp'], $format); ?></span>
															<?php endif ?>
														</td>
													</tr>
													<tr>
														<th><?= __('date_of_birth') ?>:</th>
														<td>
															<span class="date-batch-g" data-prefix="<?=__('gregorian') ?>"><?= $emprow["dob"]; ?></span>
															<span class="date-batch-h float-right" data-prefix="<?=__('hijri') ?>"><?= $DateConv->GregorianToHijri($emprow["dob"], $format); ?></span>
														</td>
														<th><?= __('age') ?>:</th>
														<td><?= ($emprow["dob"] <> "") ? $years : "" ?></td>
													</tr>
													<tr>
														<th><?= __('gender_blood_group') ?>:</th>
														<td><?= ucfirst(__($emprow["sex"])) . " | " . $emprow['blood_type']; ?></td>
														<th><?= __('marital_status') ?>:</th>
														<td><?= ucfirst(__($emprow["mar_status"])); ?></td>
													</tr>
													<tr>
														<th><?= __('tshirt_size') ?>:</th>
														<td><?= ucfirst($emprow['t_shirt_size']); ?></td>
														<th><?= __('contract_period') ?>:</th>
														<td><?= formatPeriod($emprow["period"]) ?></td>
													</tr>
													<tr>
														<th><?= __('mobile') ?>:</th>
														<td><span class="copyToClipboard"><?= $emprow['mobile']; ?></span> <i class="fa fa-clipboard"></i></td>
														<th><?= __('country') ?>:</th>
														<td><?= ($is_rtl ?? false) ? $emprow["country_name_ar"] : $emprow["country_name"]; ?></td>
													</tr>
													<tr>
														<th><?= __('joining_date') ?>:</th>
														<td>
															<span class="date-batch-g" data-prefix="<?=__('gregorian') ?>"><?= $emprow["joining_date"]; ?></span>
															<span class="date-batch-h float-right" data-prefix="<?=__('hijri') ?>"><?= $DateConv->GregorianToHijri($emprow["joining_date"], $format); ?></span>
														</td>
														<th><?= __('department') ?>:</th>
														<td><?= ($is_rtl ?? false) ? $emprow["deptnme_ar"] : $emprow["deptnme"] ?></td>
													</tr>

													<?php if (car_get_info($emprow["car_id"])) { ?>
														<tr class="table-info">
															<th><?= __('car_maker') ?>:</th>
															<td><?= car_get_info($emprow["car_id"])['maker_name'] . " | " . car_get_info($emprow["car_id"])['made_year'] ?></td>
															<th><?= __('car_model') ?>:</th>
															<td><?= car_get_info($emprow["car_id"])['model'] ?></td>
														</tr>
													<?php } ?>

													<tr>
														<th><?= __('section_area_sponsorship') ?>:</th>
														<td><?= $emprow["sectin_nme"] . " | " . $emprow['sponsor'] ?></td>
														<th><?= __('total_salary') ?>:</th>
														<td><?= $emprow['salary']; ?><i class="icon-saudi_riyal" style="font-size: 14px !important;"></i> -
															<?= ($emprow['payment_type'] == 1 ? __('bank_transfer') : ($emprow['payment_type'] == 2 ? __('cash_payment') : __('about_to_hold'))) ?>
														</td>
													</tr>

													<tr>
														<th><?= __('bank_name') ?>:</th>
														<td><?= ($is_rtl ?? false) ? $emprow["b_name_ar"] : $emprow["b_name"] ?></td>
														<th><?= __('iban') ?>:</th>
														<td><?= $emprow["iban"] ?></td>
													</tr>
													<?php //if($emprow["country"] == 191){ 
													?>
													<tr>
														<th><?= __('gosi_gosi_no') ?>:</th>
														<td><?= $emprow["gosi"] . " | " . $emprow["gosi_no"] ?></td>
														<th><?= __('gosi_expiry') ?>:</th>
														<td><?= $emprow["date_hijri"] . " | " . $emprow["date_greg"] ?></td>
													</tr>
													<?php //} 
													?>
													<tr>
														<th><?= __('actual_job') ?>:</th>
														<td><?= ($is_rtl ?? false ? $emprow["jobname_ar"] : $emprow["jobname"]) ?></td>
														<th><?= __('probation_period') ?>:</th>
														<td><?= $probationStatus ?></td>
													</tr>

													<tr>
														<th><?= __('insurance_no_class') ?>:</th>
														<td><?= $emprow['insurance_no'] . " | " . $emprow['insurance_class'] ?></td>
														<th><?= __('insurance_expiry') ?>:</th>
														<td>
															<?php if ($emprow['insurance_exp']): ?>
																<span class="date-batch-g" data-prefix="<?=__('gregorian') ?>"><?= $emprow['insurance_exp']; ?></span>
															<?php endif ?>
															<?php if ($emprow['insurance_exp']): ?>
																<span class="date-batch-h float-right" data-prefix="<?=__('hijri') ?>"><?= $DateConv->GregorianToHijri($emprow['insurance_exp'], $format); ?></span>
															<?php endif ?>
														</td>
													</tr>
													<tr>
														<th><?= __('emergency_contact') ?>:</th>
														<td><?= $emprow["emg_mobile"] . " | " . $emprow['emg_name'] ?></td>
														<th><?= __('address') ?>:</th>
														<td><?= ucfirst($emprow['address']) ?></td>
													</tr>
												</tbody>
											</table>


											<div class="text-right">
												<div class="btn-group" role="group" aria-label="Edit Button">
													<a href="./employee_profile.php?emp_id=<?= $emprow['empid']; ?>" class="btn btn-sm waves-effect btn-primary" target="_blank"><i class="fi-printer "></i> <?= __('print_profile') ?></a>
												</div>
											</div>

										</div>

										<?php /*if($user_type <> "dept_user"){*/ ?>
										<div class="tab-pane" id="home1">
											<div class="table-responsive">

												<h4 class="m-t-0 header-title"></h4>
												<table id="employee_vac" class="table table-striped table-bordered dt-responsive nowrap" style="border-collapse: collapse; border-spacing: 0; width: 100%;">
													<thead>
														<tr>
															<th><?= __('remarks') ?>
															<th><?= __('fly_date') ?>
															<th><?= __('return_date') ?>
															<th><?= __('permit_no') ?>
															<th><?= __('notes') ?>
															<th><?= __('days') ?>
															<th><?= __('arrived') ?>
															<th><?= __('created_at') ?>
															<th><?= __('id') ?>
																<?php if ($user_type <> "dept_user") { ?>
															<th><?= __('action') ?>
															<?php } ?>

														</tr>
													</thead>
													<tbody>
														<?php
														$sql_emp_vac = "SELECT * FROM `emp_vacation` WHERE `emp_id`='" . $emprow['empid'] . "' ";
														$query_emp_vac = mysqli_query($conDB, $sql_emp_vac);

														while ($rec = mysqli_fetch_array($query_emp_vac)) {
															$id_emp_reg = $rec["id"];
															$date_emp = $rec["date"];
															$note_emp = $rec["note"];
															$user_update = $rec["user_update"];
															$date_reg_emp = $rec["date_reg"];
															$return_date_emp = $rec["return_date"];
															$vacdays_emp = $rec["vacdays"];
															$permit_no_emp = $rec["permit_no"];
															$emp_id_emp = $rec["emp_id"];
															$remarks_get = $rec["remarks"];
															$arrived_date_get = $rec["arrived_date"];

															$timestamp_reg = strtotime("$date_reg_emp");
															$new_date_format = date('d, M Y', $timestamp_reg);

														?>
															<tr>
																<th><?= $note_emp; ?></th>
																<th><?= $date_emp; ?></th>
																<th><?= $return_date_emp; ?></th>
																<th><?= $permit_no_emp; ?></th>
																<th><?= $remarks_get; ?></th>
																<th><?= $vacdays_emp; ?></th>
																<th><?= ($arrived_date_get == "") ? "Not Yet" : $arrived_date_get; ?>
																</th>
																<th><?= $new_date_format; ?></th>
																<th><?= $id_emp_reg; ?></th>
																<?php if ($user_type <> "dept_user") { ?>
																	<th>
																		<a href="./edit_vac.php?id=<?= $id_emp_reg ?>&emp_id=<?= "" . $_GET['id'] . "" ?>" class="btn btn-sm btn-primary waves-effect">
																			<i class="fa fa-edit"></i>
																		</a>
																		<?php if ($user_type == $access1) { ?>
																			<a href="./includes/delete_vac.php?id=<?= $id_emp_reg ?>" class="btn btn-sm btn-danger waves-effect">
																				<i class="dripicons-tag-delete"></i>
																			</a>
																		<?php } ?>
																	</th>
																<?php } ?>

															</tr>
														<?php } ?>
													</tbody>
												</table>
											</div>
										</div>
										<div class="tab-pane" id="messages1">
											<table class="table table-hover mb-0">
												<tbody>
													<thead class="thead-dark">
														<tr>
															<th colspan="4">
																<center><?= __('bank_account_information') ?></center>
															</th>
														</tr>
													</thead>
													<tr>
														<th><?= __('bank_name') ?>:</th>
														<td><?= $emprow["b_name"]; ?></td>
														<th><?= __('iban') ?>:</th>
														<td>
															<span class="copyToClipboard"><?= implode(" ", str_split($emprow["iban"], 4)); ?></span> <i class="fa fa-clipboard"></i>
														</td>
													</tr>
													<?php
													if ($emprow['gosi_no'] <> "") {
													?>
														<thead class="thead-dark">
															<tr>
																<th colspan="4">
																	<center><?= __('gosi_information') ?></center>
																</th>
															</tr>
														</thead>
														<tr>
															<th><?= __('gosi_no') ?>:</th>
															<td><?= $emprow['gosi_no']; ?></td>
															<th><?= __('gosi_payment') ?>:</th>
															<td><?= $emprow["amount"]; ?></td>
														</tr>
														<tr>
															<th><?= __('gregorian_date') ?>:</th>
															<td><?= $emprow["date_greg"]; ?></td>
															<th><?= __('hijri_date') ?>:</th>
															<td><?= $emprow["date_hijri"]; ?></td>
														</tr>
													<?php } else { ?>
														<tr>
															<td colspan="4">
																<a href="./add_gosi.php?emp_id=<?= "" . $emprow['emp_id'] . "" ?>" class="btn btn-sm btn-primary waves-effect">
																	<i class="mdi mdi-database-plus"></i><?= __('add_gosi_details') ?>
																</a>
															</td>
														</tr>
													<?php } ?>
												</tbody>
											</table>
										</div>

										<div class="tab-pane" id="loan1">
											<?php if ($loan_summary): ?>
												<div class="card border-primary border mb-4">
													<div class="card-header bg-primary text-white font-weight-bold"><?= __('active_loan_summary') ?></div>
													<div class="card-body">
														<div class="row text-center">
															<div class="col-md-4">
																<h6 class="text-muted text-uppercase"><?= __('total_payable_amount') ?></h6>
																<h4><?= number_format($loan_summary['total_payable'], 2) ?> <i class="icon-saudi_riyal"></i></h4>
															</div>
															<div class="col-md-4">
																<h6 class="text-muted text-uppercase"><?= __('total_paid') ?></h6>
																<h4><?= number_format($loan_summary['total_paid'], 2) ?> <i class="icon-saudi_riyal"></i></h4>
															</div>
															<div class="col-md-4">
																<h6 class="text-muted text-uppercase"><?= __('remaining_balance') ?></h6>
																<h4 class="text-danger font-weight-bold"><?= number_format($loan_summary['remaining_balance'], 2) ?> <i class="icon-saudi_riyal"></i></h4>
															</div>
														</div>
														<?php if ($loan_summary['disbursement_receipt']): ?>
															<hr>
															<div class="row">
																<div class="col-md-6">
																	<strong><?= __('disbursement_receipt_id') ?>:</strong>
																	<p><?= htmlspecialchars($loan_summary['disbursement_receipt']); ?></p>
																</div>
																<div class="col-md-6">
																	<strong><?= __('disbursement_proof') ?>:</strong>
																	<p><a href="./assets/loan_receipts/<?= htmlspecialchars($loan_summary['disbursement_attachment']); ?>" target="_blank" class="btn btn-sm btn-info"><i class="fa fa-eye"></i> View Attachment</a></p>
																</div>
															</div>
														<?php endif; ?>
													</div>
												</div>
											<?php endif; ?>

											<?php
											$sql_emp_loan_active = "SELECT * FROM `emp_loan` WHERE `emp_id`='" . $emprow['empid'] . "' AND `status` = 'approved' ORDER BY `id` DESC LIMIT 1";
											$query_emp_loan_active = mysqli_query($conDB, $sql_emp_loan_active);
											$active_loan_rec = mysqli_fetch_array($query_emp_loan_active);
											?>
											<div class="d-flex justify-content-between align-items-center mb-3">
												<h4 class="header-title m-t-0"><?= __('loan_history') ?></h4>
												<?php if ($active_loan_rec) : ?>
													<button type="button" class="btn btn-info waves-effect waves-light addManualPayment" data-loan-id="<?= $active_loan_rec['id'] ?>" data-emp-id="<?= $emprow['empid'] ?>">
														<i class="mdi mdi-plus-circle-outline"></i> <?= __('add_manual_payment') ?>
													</button>
												<?php endif; ?>
											</div>
											<table id="loan_history_tbl" class="table table-striped table-bordered dt-responsive nowrap" style="border-collapse: collapse; border-spacing: 0; width: 100%;">
												<thead>
													<tr>
														<th><?= __('loan_amount') ?></th>
														<th><?= __('monthly_deduction') ?></th>
														<th><?= __('remaining_balance') ?></th>
														<th><?= __('start_date') ?></th>
														<th><?= __('end_date') ?></th>
														<th><?= __('type') ?></th>
														<th><?= __('status') ?></th>
														<th><?= __('report') ?></th>
													</tr>
												</thead>
												<tbody>
													<?php
													$sql_emp_loan = "SELECT * FROM `emp_loan` WHERE `emp_id`='" . $emprow['empid'] . "' ORDER BY `id` DESC";
													$query_emp_loan = mysqli_query($conDB, $sql_emp_loan);
													while ($loan_rec = mysqli_fetch_array($query_emp_loan)) {
														// Calculate remaining balance for each loan
														$loan_id_hist = $loan_rec['id'];
														$total_payable_hist = $loan_rec['total_payable'];
														$sql_total_paid_hist = "SELECT COALESCE(SUM(amount), 0) as total_paid FROM `emp_loan_payments` WHERE `loan_id` = '$loan_id_hist'";
														$query_total_paid_hist = mysqli_query($conDB, $sql_total_paid_hist);
														$paid_rec_hist = mysqli_fetch_assoc($query_total_paid_hist);
														$total_paid_hist = $paid_rec_hist['total_paid'];
														$remaining_balance_hist = $total_payable_hist - $total_paid_hist;
													?>
														<tr>
															<td><?= number_format($loan_rec['loan_amount'], 2); ?></td>
															<td><?= number_format($loan_rec['monthly_deduction'], 2); ?></td>
															<td class="font-weight-bold <?= ($remaining_balance_hist > 0) ? 'text-danger' : 'text-success' ?>"><?= number_format($remaining_balance_hist, 2); ?></td>
															<td><?= date('d, M Y', strtotime($loan_rec['start_date'])); ?></td>
															<td><?= date('d, M Y', strtotime($loan_rec['end_date'])); ?></td>
															<td><span class="badge badge-<?= ($loan_rec['loan_type'] == 'emergency' ? 'warning' : 'info') ?>"><?= ucfirst(__($loan_rec['loan_type'])); ?></span></td>
															<td><span class="badge badge-<?= ($loan_rec['status'] == 'approved' ? 'success' : ($loan_rec['status'] == 'paid' ? 'primary' : ($loan_rec['status'] == 'rejected' ? 'danger' : 'warning'))) ?>"><?= ucfirst(__($loan_rec['status'])); ?></span></td>
															<td><a href="./loan_report_details.php?id=<?= $loan_id_hist ?>&emp_id=<?= $emprow['emp_id'] ?>" target="_blank" class="btn btn-sm btn-dark"><i class="fa fa-eye"></i> <?= __('view') ?></a></td>
														</tr>
													<?php } ?>
												</tbody>
											</table>

											<h4 class="header-title m-t-0 m-b-30 mt-4"><?= __('repayment_history') ?></h4>
											<table id="payment_history_tbl" class="table table-striped table-bordered dt-responsive nowrap" style="border-collapse: collapse; border-spacing: 0; width: 100%;">
												<thead>
													<tr>
														<th><?= __('payment_date') ?></th>
														<th><?= __('amount') ?></th>
														<th><?= __('receipt_id') ?></th>
														<th><?= __('attachment') ?></th>
														<th><?= __('loan_id') ?></th>
													</tr>
												</thead>
												<tbody>
													<?php
													$sql_loan_payments = "SELECT lp.* FROM `emp_loan_payments` lp JOIN `emp_loan` l ON lp.loan_id = l.id WHERE l.emp_id = '" . $emprow['empid'] . "' ORDER BY lp.payment_date DESC";
													$query_loan_payments = mysqli_query($conDB, $sql_loan_payments);
													while ($payment_rec = mysqli_fetch_array($query_loan_payments)) {
													?>
														<tr>
															<td><?= date('d, M Y', strtotime($payment_rec['payment_date'])); ?></td>
															<td><?= $payment_rec['amount']; ?></td>
															<td><?= htmlspecialchars($payment_rec['receipt_id'] ?? 'N/A'); ?></td>
															<td>
																<?php if (!empty($payment_rec['attachment'])): ?>
																	<a href="./assets/loan_receipts/<?= htmlspecialchars($payment_rec['attachment']); ?>" target="_blank" class="btn btn-sm btn-info"><i class="fa fa-eye"></i> <?= __('view') ?></a>
																<?php else: ?>
																	N/A
																<?php endif; ?>
															</td>
															<td><?= $payment_rec['loan_id']; ?></td>
														</tr>
													<?php } ?>
												</tbody>
											</table>
										</div>

										<div class="tab-pane" id="assets">
											<h4 class="header-title m-t-0 m-b-30 mt-4"><?= __('assigned_assets') ?></h4>
											<table id="assets_tbl" class="table table-striped table-bordered dt-responsive nowrap" style="width: 100%;">
												<thead>
													<tr>
														<th><?= __('asset_type') ?></th>
														<th><?= __('serial_number') ?></th>
														<th><?= __('assigned_date') ?></th>
														<th><?= __('status') ?></th>
														<th><?= __('return_date') ?></th>
														<th><?= __('action') ?></th>
													</tr>
												</thead>
												<tbody>
													<?php
													$sql_assets = "SELECT ea.*, a.name as asset_name 
                                                                 FROM `employee_assets` ea 
                                                                 JOIN `assets` a ON ea.asset_id = a.id 
                                                                 WHERE ea.emp_id = '{$emprow['empid']}' 
                                                                 ORDER BY ea.assigned_date DESC";
													$query_assets = mysqli_query($conDB, $sql_assets);
													while ($asset_rec = mysqli_fetch_array($query_assets)) {
													?>
														<tr>
															<td><?= htmlspecialchars($asset_rec['asset_name']); ?></td>
															<td><?= htmlspecialchars($asset_rec['serial_number']); ?></td>
															<td><?= date('d, M Y', strtotime($asset_rec['assigned_date'])); ?></td>
															<td>
																<span class="badge badge-<?= ($asset_rec['status'] == 'Assigned' ? 'success' : ($asset_rec['status'] == 'Lost' ? 'danger' : ($asset_rec['status'] == 'Damaged' ? 'warning' : 'secondary'))) ?>">
																	<?= __(strtolower($asset_rec['status'])); ?>
																</span>
															</td>
															<td><?= $asset_rec['return_date'] ? date('d, M Y', strtotime($asset_rec['return_date'])) : 'N/A'; ?></td>
															<td>
																<?php if ($asset_rec['status'] == 'Assigned'): ?>
																	<div class="btn-group">
																		<a href="asset_return_report.php?asset_id=<?= $asset_rec['id'] ?>" target="_blank" class="btn btn-sm btn-primary print-return-btn" data-asset-id="<?= $asset_rec['id'] ?>"><?= __('print_for_return') ?></a>
																		<button id="submit-return-btn-<?= $asset_rec['id'] ?>" class="btn btn-sm btn-danger waves-effect" onclick="unassignAsset(<?= $asset_rec['id'] ?>)" disabled>
																			<?= __('submit_return') ?>
																		</button>
																	</div>
																<?php else: ?>
																	<div class="btn-group">
																		<a href="asset_return_report.php?asset_id=<?= $asset_rec['id'] ?>" target="_blank" class="btn btn-sm btn-primary"><?= __('print_report') ?></a>
																		<?php if (!empty($asset_rec['return_attachment'])): ?>
																			<a href="<?= htmlspecialchars($asset_rec['return_attachment']) ?>" target="_blank" class="btn btn-sm btn-info"><?= __('view_proof') ?></a>
																		<?php endif; ?>
																	</div>
																<?php endif; ?>
															</td>
														</tr>
													<?php } ?>
												</tbody>
											</table>
										</div>


										<div class="tab-pane" id="documents">
											<div class="card-box">
												<h4 class="header-title m-b-30"><?= __('my_files') ?></h4>
												<div class="row">
													<?php
													$queryempdocu = mysqli_query($conDB, "SELECT * FROM `emp_docu` WHERE `emp_id`='" . $emprow['empid'] . "' ORDER BY `id` DESC ");
													while ($recempdoc = mysqli_fetch_assoc($queryempdocu)) {
														$id_empdoc_get = $recempdoc["id"];
														$docu_typ_get = $recempdoc["docu_typ"];
														$attachment_get = $recempdoc["path"];
														$docu_ext_get = $recempdoc["docu_ext"];
														$doc_date_reg_get = $recempdoc["created_at"];
														$times_reg = strtotime("$doc_date_reg_get");
														$doc_date_reg_get = date('d, M Y h:ia', $times_reg);
														$fileIcon = ($docu_ext_get == "pdf" ? "pdf" : ($docu_ext_get == "xls" ? "excel" : ($docu_ext_get == "tif" ? "tif" : "")));
													?>

														<div class="col-lg-2 col-xl-2">
															<div class="file-man-box">
																<a href="javascript:void(0);" class="file-close deleteAjax" data-id='<?= $recempdoc['id'] ?>' data-tbl='emp_docu' data-file='1' data-column='path'>
																	<i class="fa fa-xmark"></i>
																</a>
																<div class="file-img-box">
																	<?php if ($docu_ext_get == "pdf" or $docu_ext_get == "xls" or $docu_ext_get == "tif"): ?>
																		<img src="assets/images/file_icons/<?= $fileIcon ?>.svg" onclick="javascript:displayPopup('./assets/emp_documents/<?= $attachment_get ?>')" style="cursor:pointer;" />
																	<?php else: ?>
																		<img src="./assets/emp_documents/<?= $attachment_get ?>" onclick="javascript:displayPopup('./assets/emp_documents/<?= $attachment_get ?>')" style="cursor:pointer;" />
																	<?php endif ?>
																</div>

																<a href="./downloadFile.php?file=./assets/emp_documents/<?= $attachment_get ?>" class="file-download"><i class="mdi mdi-download"></i></a>
																<div class="file-man-title">
																	<p class="mb-0"><small><?= $doc_date_reg_get ?></small></p>
																</div>
															</div>
														</div>
													<?php } ?>

												</div>

											</div>
										</div>

										<div class="tab-pane" id="noties">
											<div class="card-box">
												<h4 class="header-title m-b-30"><?= __('all_notes') ?></h4>
												<table id="notes_tbl" class="table table-striped table-bordered dt-responsive nowrap" style="border-collapse: collapse; border-spacing: 0; width: 100%;"></table>
											</div>
										</div>


										<?php /*}*/ ?>
										<div class="tab-pane" id="attendance">
											<div class="card-box">

												<h4 class="header-title m-b-30">Attendance Record</h4>

												<div class="col-4 pull-right">
													<div class="input-group input-daterange">
														<input type="text" id="FromDate" class="form-control date-range-filter" data-date-format="yyyy-mm-dd" placeholder="From:">
														<div class="input-group-addon">to</div>
														<input type="text" id="Todate" class="form-control date-range-filter" data-date-format="yyyy-mm-dd" placeholder="To:">
													</div>
												</div>


												<table id="attendance_tbl" class="table table-striped table-bordered dt-responsive nowrap" style="border-collapse: collapse; border-spacing: 0; width: 100%;">
													<thead>
														<tr>
															<th>id</th>
															<th>Emp ID.</th>
															<th>Employee Name</th>
															<th>Date</th>
															<th>Check In</th>
															<th>Check Out</th>
															<th>Hours</th>
															<th>Punch Type</th>
															<th>Note</th>
															<th>Action</th>
														</tr>
													</thead>
												</table>

											</div>
										</div>


									</div>
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
		<div class="modal fade terminat" tabindex="-1" role="dialog" aria-labelledby="mySmallModalLabel" aria-hidden="true" style="display: none;">
			<form action="./includes/apply_vac_emp.php" method="get">
				<div class="modal-dialog modal-dialog-centered">
					<div class="modal-content">
						<div class="modal-header" style="background-color: #02C0CE !important; color: #fff !important;">
							<button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
							<h4 class="modal-title" id="mySmallModalLabel">
								<i class="mdi mdi-format-rotate-90"></i>
								Are you sure!
							</h4>
						</div>
						<div class="modal-body">
							<h5>Please select Replacement Person!</h5>
							<div class="form-row">
								<div class="form-group col-md-6">
									<!--	<a href="" class="btn btn-da nger waves-effect waves-light" ><i class="mdi mdi-account-off"></i> Terminat</a>-->
									<input type="hidden" name="id" value="<?= $_GET['id'] ?>">
									<input type="hidden" name="emp_id" value="<?= $emprow['empid'] ?>">
									<input type="hidden" name="emp_name" value="<?= $emprow['name'] ?>">
									<input type="hidden" name="dept" value="<?= $emprow["dept"] ?>">

									<div class="input-group">
										<select class="form-control" name="replacement_per" required>
											<option value="">Select</option>
											<?php
											$query_emp_apl_nme = mysqli_query($conDB, "SELECT * FROM `employees` WHERE `dept`='" . $emprow["dept"] . "' AND `dept`<>'' AND `status`=1 ORDER BY `name` REGEXP '^[^A-Za-z]' ASC, name");
											while ($rec = mysqli_fetch_assoc($query_emp_apl_nme)) {
												$emp_apl_nme = $rec["name"];
											?>
												<option value="<?= $emp_apl_nme ?>"><?= $emp_apl_nme ?></option>
											<?php } ?>
										</select>
									</div>
								</div>
								<div class="form-group col-md-6">
									<div class="custom-control custom-radio">
										<input type="radio" id="customRadio1" name="vac_type" value="annual" class="custom-control-input" required>
										<label class="custom-control-label" for="customRadio1">Annual Vacation</label>
									</div>
									<div class="custom-control custom-radio">
										<input type="radio" id="customRadio2" name="vac_type" value="emergency" class="custom-control-input" required>
										<label class="custom-control-label" for="customRadio2">Emergency Vacation</label>
									</div>
								</div>


								<div class="form-group col-md-6">
									<label for="date_select" class="col-form-label">Vacation Date<span class="text-danger">*</span></label>
									<input type="text" name="date" parsley-trigger="change" required
										placeholder="YYYY-MM-DD" class="form-control" id="date_select" autocomplete="off">
								</div>
								<div class="form-group col-md-6">
									<label for="return_dated" class="col-form-label">Return Date<span class="text-danger">*</span></label>
									<input type="text" name="return_date" parsley-trigger="change"
										placeholder="YYYY-MM-DD" class="form-control" id="return_dated" autocomplete="off" required>
								</div>

								<div class="input-group-append">
									<button type="submit" class="btn btn-success waves-effect waves-light"><i class="mdi mdi-format-rotate-90"></i> Apply Now</button>
								</div>
							</div>
						</div>
						<div class="modal-footer">
							<button type="button" class="btn btn-dark waves-effect" data-dismiss="modal">Close</button>


						</div>
					</div><!-- /.modal-content -->
				</div><!-- /.modal-dialog -->
			</form>
		</div>

		<!-- jQuery  -->
		<script src="assets/js/jquery.min.js"></script>
		<!--        <script src="http://ajax.googleapis.com/ajax/libs/jquery/1.11.0/jquery.min.js"></script>-->
		<script src="assets/js/bootstrap.bundle.min.js"></script>
		<script src="assets/js/metisMenu.min.js"></script>
		<script src="assets/js/waves.js"></script>
		<script src="assets/js/jquery.slimscroll.js"></script>


		<!-- Modal-Effect -->
		<script type="text/javascript" src="./plugins/parsleyjs/parsley.min.js"></script>
		<script src="./plugins/bootstrap-inputmask/bootstrap-inputmask.min.js" type="text/javascript"></script>
		<script src="./plugins/autoNumeric/autoNumeric.js" type="text/javascript"></script>


		<!-- <script src="./plugins/moment/moment.js"></script> -->
		<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.18.1/moment.min.js"></script>

		<script src="./plugins/bootstrap-timepicker/bootstrap-timepicker.js"></script>
		<script src="./plugins/bootstrap-datepicker/js/bootstrap-datepicker.min.js"></script>

		<!-- <script src="./plugins/select2/js/select2.min.js" type="text/javascript"></script>
        <script src="./plugins/bootstrap-select/js/bootstrap-select.js" type="text/javascript"></script> -->

		<!-- <script src="./assets/pages/jquery.form-pickers.init.js"></script> -->
		<script src="./plugins/croppie/croppie.js" type="text/javascript"></script>
		<script src="./plugins/croppie/croppie.min.js" type="text/javascript"></script>
		<script src="./plugins/croppie/exif.js" type="text/javascript"></script>

		<!-- App js -->

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
		<script src="assets/js/jquery.app.js"></script>

		<script src="./plugins/summernote/summernote.min.js"></script>
		<!-- <script src="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-bs4.min.js"></script> -->
		<script src="assets/js/loanHandling.js"></script>

		<script type="text/javascript">
			$(document).ready(function() {
				$('#assets_tbl').DataTable({
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

				// Event listener for the print button
				$('#assets_tbl').on('click', '.print-return-btn', function(event) {
					event.preventDefault(); // Prevent the link from opening immediately

					const assetRecordId = $(this).data('asset-id');
					const url = $(this).attr('href');

					// Enable the corresponding submit button
					$('#submit-return-btn-' + assetRecordId).prop('disabled', false);

					// Open the report in a new tab
					window.open(url, '_blank');
				});
			});

			$(document).ready(function() {

				var buttonConfig = [];
				var exportTitle = "Name: <?= $emprow['name'] ?>"
				buttonConfig.push({
					extend: 'excel',
					exportOptions: {
						columns: [0, 1, 2, 3, 4, 5, 6, 7]
					},
					title: exportTitle,
					className: 'btn-success'
				});
				buttonConfig.push({
					extend: 'pdf',
					exportOptions: {
						columns: [0, 1, 2, 3, 4, 5, 6, 7]
					},
					title: exportTitle,
					className: 'btn-danger'
				});
				buttonConfig.push({
					extend: 'print',
					exportOptions: {
						columns: [0, 1, 2, 3, 4, 5, 6, 7]
					},
					title: exportTitle,
					className: 'btn-dark'
				});
				// buttonConfig.push({text: '<i class="fa fa-plus"></i> Add Machine', action: function ( e, dt, button, config ) {window.location = './add_machine.php' } ,className: 'btn-info'});
				$('form').parsley();

				//Buttons examples
				var table = $('#employee_vac').DataTable({
					lengthChange: false,
					buttons: buttonConfig,
					order: [
						[8, "desc"]
					],
					"columnDefs": [{
						targets: [8],
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
					.appendTo('#employee_vac_wrapper .col-md-6:eq(0)');

				$('#loan_history_tbl').DataTable({
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
				$('#payment_history_tbl').DataTable({
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

			});
			jQuery(function($) {
				$('.autonumber').autoNumeric('init');
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
			/**
			 * This script should be placed within a PHP file where an employee's
			 * data (like $emprow) is available.
			 */
			// Global variable for the DataTable instance
			let noteTable;
			// Safely pass the PHP employee ID to a JavaScript variable.
			const employeeId = <?php echo json_encode($emprow['empid']); ?>;
			$(document).ready(function() {
				// 1. Initialize the DataTable with the correct columns for notes.
				initializeNotesTable();
				// 2. Fetch the notes data from the server and populate the table.
				fetchNotes();
			});
			/**
			 * Initializes the DataTable with column definitions that match the HTML <thead>.
			 * Your backend MUST return 'id', 'emp_id', 'name', 'created_at', and 'note' for each record.
			 */
			function initializeNotesTable() {
				noteTable = $('#notes_tbl').DataTable({
					// Define columns to match your HTML: <th>id</th>, <th>Emp ID.</th>, etc.
					autoWidth: false, // Add this line
					columns: [{
							data: 'id',
							title: 'id',
						},
						{
							data: 'emp_id',
							title: __('emp_id')
						},
						{
							data: 'name',
							title: __('employee_name')
						},
						{
							data: 'note',
							title: __('notes')
						},
						{
							data: 'created_at',
							title: __('created_at')
						},
						{
							data: null,
							title: __('action')
						}
					],
					columnDefs: [{
							targets: 0,
							width: '20px',
							visible: false,
							searchable: false,
						},
						{
							targets: 1,
							width: '60px',
						},
						{
							targets: 4,
							width: '100px',
							render: function(data, type, row) {
								if (type === 'display' && data) {
									return new Date(data).toLocaleDateString('en-US', {
										year: 'numeric',
										month: 'long',
										day: 'numeric'
									});
								}
								return data;
							}
						},
						{
							targets: 5,
							width: '40px',
							orderable: false,
							render: function(data, type, row) {
								return `<button class="btn btn-danger btn-sm isDeleteAjax" data-tbl='emp_notice' data-id="${row.id}"><i class="fa fa-xmark-large"></i></button>`;
							}
						},
						{
							targets: [0, 1, 5],
							className: 'text-center'
						},
					],
					order: [
						[3, 'desc']
					], // Default sort by 'Created at' (the 4th column) descending
					pageLength: 5,
					lengthMenu: [5, 10, 25, 50],
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
			}

			/**
			 * Fetches notes for a specific employee using a POST request.
			 */
			async function fetchNotes() {
				const loadingIndicator = $('#loading-indicator');
				const noDataMessage = $('#noDataMessage');
				loadingIndicator.removeClass('hidden');
				noDataMessage.addClass('hidden');
				noteTable.clear().draw();
				const apiUrl = './includes/ajaxFile/ajaxEmployee.php';
				// Prepare the data for the POST request
				const postData = new URLSearchParams();
				postData.append('ajaxType', 'view_notes');
				postData.append('emp_id', employeeId);
				try {
					const response = await fetch(apiUrl, {
						method: 'POST',
						headers: {
							// This header is crucial for the server to correctly interpret the POST data
							'Content-Type': 'application/x-www-form-urlencoded',
						},
						body: postData
					});
					if (!response.ok) {
						const errorText = await response.text();
						throw new Error(`Server responded with status ${response.status}: ${errorText}`);
					}
					const data = await response.json();
					// Check if the response is successful and contains a 'notes' array
					if (data.status === 'success' && data.notes && data.notes.length > 0) {
						noteTable.clear().rows.add(data.notes).draw();
					} else {
						noDataMessage.text(data.message || 'No notes found for this employee.').removeClass('hidden');
						noteTable.clear().draw();
					}
				} catch (error) {
					console.error('Error fetching notes:', error);
					noDataMessage.text(`An error occurred: ${error.message}`).removeClass('hidden');
					noteTable.clear().draw();
				} finally {
					loadingIndicator.addClass('hidden');
				}
			}

			function returnVacationRequest(vacationId, returndate) {
				Swal.fire({
					title: 'Confirm Employee Return',
					html: '<p>Please select the actual date the employee returned to work:</p>' +
						'<input type="text" id="returndate" class="form-control">',
					showCancelButton: true,
					confirmButtonColor: '#3085d6',
					cancelButtonColor: '#d33',
					confirmButtonText: 'Yes, Update!',
					showLoaderOnConfirm: true,
					allowOutsideClick: false,
					willOpen: () => {
						jQuery('#returndate').datepicker({
							format: "yyyy-mm-dd",
							todayHighlight: true,
							autoclose: true,
							startDate: returndate // Set startDate to your database date
						}).datepicker('setDate', returndate);
					},
					preConfirm: () => {
						const returnDate = document.getElementById('returndate').value;
						if (!returnDate) {
							Swal.showValidationMessage('You must select a return date!');
							return false;
						}
						// Return the AJAX promise
						return $.ajax({
								url: './includes/ajaxFile/ajaxVacation.php',
								type: 'POST',
								dataType: 'JSON',
								data: {
									ajaxType: 'returnVacation',
									vacation_id: vacationId,
									returnDate: returnDate
								}
							})
							.done(function(response) {
								Swal.fire({
									title: response.title,
									text: response.message,
									icon: response.type,
									allowOutsideClick: false
								}).then(function(isConfirm) {
									(isConfirm) ? location.reload(): ""
								});
							})
							.fail(function(jqXHR, textStatus, errorThrown) {
								Swal.showValidationMessage('Request failed: ' + textStatus);
								return false;
							});
					}
				})
			}
		</script>

	</body>

	</html>
<?php } ?>