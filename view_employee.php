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


	// DEPARTMENT-BASED ACCESS CONTROL
	// Get the employee's department
	$target_dept = $emprow["dept"] ?? 0;
	
	// Check if user has permission to view this employee
	$can_see_all_employees = (
		$is_system_admin || 
		$user_type == 'administrator' ||
		$user_dept == 5 || // HR Department
		$isHR || 
		$isDeptHr
	);
	
	$hasAccess = $can_see_all_employees || ($user_dept == $target_dept);
	
	if (!$hasAccess) {
		$_SESSION['error_msg'] = sprintf(
			'<div class="col-xl-12">
				<div class="alert alert-danger bg-danger text-white border-0" role="alert">
					<b>Access Denied!</b> 
					<h4>You don\'t have access for ( %s ) Department.</h4>
				</div>
			</div>',
			$emprow["deptnme"]
		);
		header("Location: ./dashboard.php");
		exit;
	}
	// If we get here, access is granted

	if (!isset($_GET['emp_id']) || empty($_GET['emp_id'])) {
		header("Location: ./reg_employee.php");
		exit;
	}

	if (mysqli_num_rows($get_emp_data) !== 0) {
		$allRecords = mysqli_fetch_all($get_emp_data, MYSQLI_ASSOC);
		foreach ($allRecords as $rec) {
			$emprow = $rec;
		}

		// --- START: Loan Summary Calculation ---
		$loan_summary = null;
		$sql_active_loan = "SELECT * FROM `emp_loan` WHERE `emp_id`='" . $emprow['empid'] . "' AND `status` = 'approved' ORDER BY `id` DESC LIMIT 1";
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
				'loan_id' => $loan_id,
				'inv_no' => $active_loan_data['inv_no'],
				'loan_type' => $active_loan_data['loan_type'],
				'loan_amount' => $active_loan_data['loan_amount'],
				'final_approved_amount' => $active_loan_data['final_approved_amount'] ?? $active_loan_data['loan_amount'],
				'installments' => $active_loan_data['installments'],
				'monthly_deduction' => $active_loan_data['monthly_deduction'],
				'start_date' => $active_loan_data['start_date'],
				'end_date' => $active_loan_data['end_date'],
				'total_payable' => $total_payable,
				'total_paid' => $total_paid,
				'remaining_balance' => $remaining_balance,
				'disbursement_receipt' => $active_loan_data['disbursement_receipt_id'],
				'disbursement_attachment' => $active_loan_data['disbursement_attachment']
			];
		}
		// --- END: Loan Summary Calculation ---
		// debug($emprow);


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
		<link rel="shortcut icon" href="<?= get_setting($conDB, 'favicon') ?>">

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
		
		<!-- SweetAlert2 -->
		<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
		<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
		
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
								<img src="<?= get_setting($conDB, 'logo') ?>" alt="" height="22">
							</span>
							<i>
								<img src="<?= get_setting($conDB, 'white_logo') ?>" alt="" height="28">
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
										<li class="nav-item">
											<a href="#evaluations" data-toggle="tab" aria-expanded="false" class="nav-link">
												<i class="mdi mdi-chart-line mr-2"></i> <?= __('evaluations', 'Performance Evaluations') ?>
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
															<span class="date-batch-h" data-prefix="<?= __('hijri') ?>"><?= $emprow['iqama_exp']; ?></span>
															<span class="date-batch-g float-right" data-prefix="<?= __('gregorian') ?>"><?= $DateConv->HijriToGregorian($emprow['iqama_exp'], $format); ?></span>
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
																<span class="date-batch-g" data-prefix="<?= __('gregorian') ?>"><?= $emprow['passport_exp']; ?></span>
															<?php endif ?>
															<!-- <span class="date-batch-h float-right"><? //=$DateConv->GregorianToHijri($emprow['passport_exp'], $format); 
																										?></span> -->
															<?php if ($emprow['passport_exp']): ?>
																<span class="date-batch-h float-right" data-prefix="<?= __('hijri') ?>"><?= $DateConv->GregorianToHijri($emprow['passport_exp'], $format); ?></span>
															<?php endif ?>
														</td>
													</tr>
													<tr>
														<th><?= __('date_of_birth') ?>:</th>
														<td>
															<span class="date-batch-g" data-prefix="<?= __('gregorian') ?>"><?= $emprow["dob"]; ?></span>
															<span class="date-batch-h float-right" data-prefix="<?= __('hijri') ?>"><?= $DateConv->GregorianToHijri($emprow["dob"], $format); ?></span>
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
															<span class="date-batch-g" data-prefix="<?= __('gregorian') ?>"><?= $emprow["joining_date"]; ?></span>
															<span class="date-batch-h float-right" data-prefix="<?= __('hijri') ?>"><?= $DateConv->GregorianToHijri($emprow["joining_date"], $format); ?></span>
														</td>
														<th><?= __('department') ?>:</th>
														<td><?= ($is_rtl ?? false) ? $emprow["deptnme_ar"] : $emprow["deptnme"] ?></td>
													</tr>
													<tr>
														<th><?= __('employee_type') ?? "Employee Type" ?>:</th>
														<td><?= $emprow['emptype'] ?? 'N/A' ?></td>
														<th><?= __('direct_supervisor') ?? "Direct Supervisor" ?>:</th>
														<td>
															<?php 
															if (!empty($emprow['supervisor_id'])) {
																$supervisor_query = mysqli_query($conDB, "
																	SELECT `name`, `emp_id`, `emptype` 
																	FROM `employees` 
																	WHERE `emp_id` = '{$emprow['supervisor_id']}' 
																	LIMIT 1
																");
																$supervisor = mysqli_fetch_assoc($supervisor_query);
																if ($supervisor) {
																	echo '<a href="view_employee.php?emp_id=' . $supervisor['emp_id'] . '" class="text-primary">' . 
																		 htmlspecialchars($supervisor['name']) . ' (' . $supervisor['emp_id'] . ')</a>' .
																		 ' <span class="badge badge-soft-info">' . $supervisor['emptype'] . '</span>';
																} else {
																	echo '<span class="text-muted">' . (__('not_assigned') ?? 'Not Assigned') . '</span>';
																}
															} else {
																echo '<span class="text-muted">' . (__('not_assigned') ?? 'Not Assigned') . '</span>';
															}
															?>
														</td>
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
																<span class="date-batch-g" data-prefix="<?= __('gregorian') ?>"><?= $emprow['insurance_exp']; ?></span>
															<?php endif ?>
															<?php if ($emprow['insurance_exp']): ?>
																<span class="date-batch-h float-right" data-prefix="<?= __('hijri') ?>"><?= $DateConv->GregorianToHijri($emprow['insurance_exp'], $format); ?></span>
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
											<div class="card border-primary border mb-4">
												<div class="card-header bg-primary text-white font-weight-bold"><?= __('vacation_balance_summary') ?></div>
												<div class="card-body">
													<div class="row text-center">
														<div class="col-md-4">
															<h6 class="text-muted text-uppercase"><?= __('total_vacation_days') ?></h6>
															<h4><?= $emprow['total_vacation_days'] ?? 0 ?></h4>
														</div>
														<div class="col-md-4">
															<h6 class="text-muted text-uppercase"><?= __('vacations_taken') ?></h6>
															<h4><?= $emprow['vacations_taken'] ?? 0 ?></h4>
														</div>
														<div class="col-md-4">
															<h6 class="text-muted text-uppercase"><?= __('remaining_balance') ?></h6>
															<h4 class="text-success font-weight-bold"><?= ($emprow['total_vacation_days'] ?? 0) - ($emprow['vacations_taken'] ?? 0) ?></h4>
														</div>
													</div>
												</div>
											</div>
											<div class="table-responsive">

												<h4 class="m-t-0 header-title"></h4>
												<table id="employee_vac" class="table table-striped table-bordered dt-responsive nowrap" style="border-collapse: collapse; border-spacing: 0; width: 100%;">
													<thead>
														<tr>
															<th><?= __('remarks') ?></th>
															<th><?= __('fly_date') ?></th>
															<th><?= __('return_date') ?></th>
															<th><?= __('permit_no') ?></th>
															<th><?= __('notes') ?></th>
															<th><?= __('days') ?></th>
															<th><?= __('arrived') ?></th>
															<th><?= __('created_at') ?></th>
															<th><?= __('id') ?></th>
															<?php if ($user_type <> "dept_user") { ?>
																<th><?= __('action') ?></th>
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
															$return_date_emp = $rec["return_date"];
															$vacdays_emp = $rec["vacdays"];
															$permit_no_emp = $rec["permit_no"];
															$emp_id_emp = $rec["emp_id"];
															$remarks_get = $rec["remarks"];
															$arrived_date_get = $rec["arrived_date"];

															$date_reg_emp = $rec["created_at"];
															$timestamp_reg = strtotime("$date_reg_emp");
															$new_date_format = date('d, M Y H:i', $timestamp_reg);

														?>
															<tr>
																<td><?= $note_emp; ?></td>
																<td><?= $date_emp; ?></td>
																<td><?= $return_date_emp; ?></td>
																<td><?= $permit_no_emp; ?></td>
																<td><?= $remarks_get; ?></td>
																<td><?= $vacdays_emp; ?></td>
																<td><?= ($arrived_date_get == "") ? "Not Yet" : $arrived_date_get; ?>
																</td>
																<td><?= $new_date_format; ?></td>
																<td><?= $id_emp_reg; ?></td>
																<?php if ($user_type <> "dept_user") { ?>
																	<td>
																		<div class="btn-group" role="group">
																			<a href="javascript:void(0);" class="btn btn-sm btn-primary waves-effect">
																				<i class="fa fa-edit"></i>
																			</a>
																			<?php if ($user_type == $access1) { ?>
																				<a href="javascript:void(0);" class="btn btn-sm btn-danger waves-effect">
																					<i class="fa fa-solid fa-remove"></i>
																				</a>
																			<?php } ?>
																		</div>
																	</td>
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
													<div class="card-header bg-primary text-white">
														<h5 class="mb-0"><i class="fa fa-info-circle"></i> <?= __('active_loan_summary') ?></h5>
													</div>
													<div class="card-body">
														<div class="row">
															<!-- Loan Details Column -->
															<div class="col-md-6">
																<div class="info-block mb-3">
																	<h6 class="text-muted text-uppercase mb-3"><i class="fa fa-file-text"></i> <?= __('loan_details') ?></h6>
																	<table class="table table-sm table-borderless">
																		<tr>
																			<td class="font-weight-bold" width="50%"><?= __('invoice_number') ?>:</td>
																			<td><?= htmlspecialchars($loan_summary['inv_no']) ?></td>
																		</tr>
																		<tr>
																			<td class="font-weight-bold"><?= __('loan_type') ?>:</td>
																			<td><span class="badge badge-info"><?= ucfirst(str_replace('_', ' ', __($loan_summary['loan_type']))) ?></span></td>
																		</tr>
																		<tr>
																			<td class="font-weight-bold"><?= __('approved_amount') ?>:</td>
																			<td class="text-primary font-weight-bold"><?= number_format($loan_summary['final_approved_amount'], 2) ?> SAR</td>
																		</tr>
																		<tr>
																			<td class="font-weight-bold"><?= __('installments') ?>:</td>
																			<td><?= $loan_summary['installments'] ?> <?= __('months') ?></td>
																		</tr>
																		<tr>
																			<td class="font-weight-bold"><?= __('monthly_deduction') ?>:</td>
																			<td class="text-warning font-weight-bold"><?= number_format($loan_summary['monthly_deduction'], 2) ?> SAR</td>
																		</tr>
																		<tr>
																			<td class="font-weight-bold"><?= __('start_date') ?>:</td>
																			<td><?= date('d M, Y', strtotime($loan_summary['start_date'])) ?></td>
																		</tr>
																		<tr>
																			<td class="font-weight-bold"><?= __('end_date') ?>:</td>
																			<td><?= date('d M, Y', strtotime($loan_summary['end_date'])) ?></td>
																		</tr>
																	</table>
																</div>
															</div>
															
															<!-- Payment Summary Column -->
															<div class="col-md-6">
																<div class="info-block mb-3">
																	<h6 class="text-muted text-uppercase mb-3"><i class="fa fa-money"></i> <?= __('payment_summary') ?></h6>
																	<div class="payment-stat mb-3 p-3 bg-light rounded">
																		<small class="text-muted d-block"><?= __('total_loan_amount') ?></small>
																		<h4 class="mb-0 text-primary"><?= number_format($loan_summary['total_payable'], 2) ?> <i class="icon-saudi_riyal"></i></h4>
																	</div>
																	<div class="payment-stat mb-3 p-3 bg-light rounded">
																		<small class="text-muted d-block"><?= __('total_paid') ?></small>
																		<h4 class="mb-0 text-success"><?= number_format($loan_summary['total_paid'], 2) ?> <i class="icon-saudi_riyal"></i></h4>
																	</div>
																	<div class="payment-stat mb-3 p-3 bg-light rounded">
																		<small class="text-muted d-block"><?= __('remaining_balance') ?></small>
																		<h4 class="mb-0 text-danger font-weight-bold"><?= number_format($loan_summary['remaining_balance'], 2) ?> <i class="icon-saudi_riyal"></i></h4>
																	</div>
																	<?php if ($loan_summary['remaining_balance'] > 0): ?>
																	<div class="mt-3">
																		<button type="button" class="btn btn-success btn-block waves-effect waves-light addManualPayment" 
																		        data-loan-id="<?= $loan_summary['loan_id'] ?>" 
																		        data-emp-id="<?= $emprow['empid'] ?>"
																		        data-remaining="<?= $loan_summary['remaining_balance'] ?>">
																			<i class="mdi mdi-cash-multiple"></i> <?= __('add_manual_payment') ?>
																		</button>
																	</div>
																	<?php endif; ?>
																</div>
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
														<th><?= __('payment_method') ?></th>
														<th><?= __('receipt_id') ?></th>
														<th><?= __('attachment') ?></th>
														<th><?= __('note') ?></th>
														<th><?= __('loan_id') ?></th>
													</tr>
												</thead>
												<tbody>
													<?php
													$sql_loan_payments = "SELECT lp.* FROM `emp_loan_payments` lp JOIN `emp_loan` l ON lp.loan_id = l.id WHERE l.emp_id = '" . $emprow['empid'] . "' ORDER BY lp.payment_date DESC";
													$query_loan_payments = mysqli_query($conDB, $sql_loan_payments);
													while ($payment_rec = mysqli_fetch_array($query_loan_payments)) {
														$payment_method = $payment_rec['payment_method'] ?? 'auto';
														$payment_method_badge = '';
														switch($payment_method) {
															case 'manual':
																$payment_method_badge = '<span class="badge badge-success"><i class="fa fa-hand-paper-o"></i> Manual</span>';
																break;
															case 'payroll':
																$payment_method_badge = '<span class="badge badge-primary"><i class="fa fa-calendar"></i> Payroll</span>';
																break;
															default:
																$payment_method_badge = '<span class="badge badge-info"><i class="fa fa-cog"></i> Auto</span>';
														}
													?>
														<tr>
															<td><?= date('d, M Y', strtotime($payment_rec['payment_date'])); ?></td>
															<td class="font-weight-bold text-success"><?= number_format($payment_rec['amount'], 2); ?> SAR</td>
															<td><?= $payment_method_badge; ?></td>
															<td><?= !empty($payment_rec['receipt_id']) ? htmlspecialchars($payment_rec['receipt_id']) : '<span class="text-muted">N/A</span>'; ?></td>
															<td>
																<?php if (!empty($payment_rec['attachment'])): 
																	// Determine file path based on payment method
																	if ($payment_method === 'manual') {
																		$file_path = './assets/loan_manual_payments/' . htmlspecialchars($payment_rec['attachment']);
																	} else {
																		$file_path = './assets/loan_receipts/' . htmlspecialchars($payment_rec['attachment']);
																	}
																?>
																	<a href="<?= $file_path; ?>" target="_blank" class="btn btn-sm btn-info"><i class="fa fa-eye"></i> <?= __('view') ?></a>
																<?php else: ?>
																	<span class="text-muted">N/A</span>
																<?php endif; ?>
															</td>
															<td>
																<?php if (!empty($payment_rec['note'])): ?>
																	<span class="text-muted" data-toggle="tooltip" title="<?= htmlspecialchars($payment_rec['note']); ?>">
																		<i class="fa fa-comment"></i> <?= substr(htmlspecialchars($payment_rec['note']), 0, 30); ?><?= strlen($payment_rec['note']) > 30 ? '...' : ''; ?>
																	</span>
																<?php else: ?>
																	<span class="text-muted">-</span>
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

										<div class="tab-pane" id="evaluations">
											<div class="card-box">
												<h4 class="header-title m-b-30"><?= __('performance_evaluations', 'Performance Evaluations') ?></h4>
												
												<table id="evaluations_tbl" class="table table-striped table-bordered dt-responsive nowrap" style="border-collapse: collapse; border-spacing: 0; width: 100%;">
													<thead>
														<tr>
															<th><?= __('id') ?></th>
															<th><?= __('evaluation_date', 'Evaluation Date') ?></th>
															<th><?= __('evaluated_by', 'Evaluated By') ?></th>
															<th><?= __('department', 'Department') ?></th>
															<th><?= __('total_score', 'Total Score') ?></th>
															<th><?= __('observation', 'Observation') ?></th>
															<th><?= __('action') ?></th>
														</tr>
													</thead>
													<tbody>
														<?php
														// Fetch employee evaluations
														$eval_query = $pdo->prepare("
															SELECT 
																ev.id,
																ev.manager_emp_id,
																em.name AS manager_name,
																ev.dept_name,
																ev.total_score,
																ev.observation,
																DATE_FORMAT(ev.created_at, '%Y-%m-%d %H:%i') AS eval_date
															FROM emp_evaluations ev
															LEFT JOIN employees em ON ev.manager_emp_id = em.emp_id
															WHERE ev.employee_emp_id = ?
															ORDER BY ev.created_at DESC
														");
														$eval_query->execute([$emprow['empid']]);
														$evaluations = $eval_query->fetchAll(PDO::FETCH_ASSOC);
														
														if (count($evaluations) > 0):
															foreach ($evaluations as $eval):
																$score_class = 'success';
																if ($eval['total_score'] < 50) $score_class = 'danger';
																elseif ($eval['total_score'] < 70) $score_class = 'warning';
																elseif ($eval['total_score'] < 90) $score_class = 'info';
														?>
															<tr>
																<td><?= htmlspecialchars($eval['id']) ?></td>
																<td><?= htmlspecialchars($eval['eval_date']) ?></td>
																<td><?= htmlspecialchars($eval['manager_name'] ?? 'N/A') ?></td>
																<td><?= htmlspecialchars($eval['dept_name']) ?></td>
																<td>
																	<span class="badge badge-<?= $score_class ?>" style="font-size: 14px;">
																		<?= htmlspecialchars($eval['total_score']) ?>/100
																	</span>
																</td>
																<td><?= htmlspecialchars(substr($eval['observation'], 0, 100)) ?><?= strlen($eval['observation']) > 100 ? '...' : '' ?></td>
																<td>
																	<button class="btn btn-sm btn-primary view-eval-details" 
																		data-id="<?= $eval['id'] ?>" 
																		data-toggle="modal" 
																		data-target="#evaluationModal">
																		<i class="mdi mdi-eye"></i> <?= __('view_details', 'View Details') ?>
																	</button>
																</td>
															</tr>
														<?php 
															endforeach;
														else:
														?>
															<tr>
																<td colspan="7" class="text-center">
																	<em><?= __('no_evaluations_found', 'No performance evaluations found for this employee.') ?></em>
																</td>
															</tr>
														<?php endif; ?>
													</tbody>
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
		<?php /* ?>
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
		<?php */ ?>

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
		<script src="assets/js/empVacationHandle.js"></script>

		<script src="./plugins/summernote/summernote.min.js"></script>
		<!-- <script src="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-bs4.min.js"></script> -->
		<script src="assets/js/loanHandling.js"></script>

		<script type="text/javascript">
			$(document).ready(function() {
				// Check for SweetAlert message from session (after edit redirect)
				<?php if (isset($_SESSION['swal_alert'])): ?>
					Swal.fire({
						title: '<?= addslashes($_SESSION['swal_alert']['title']) ?>',
						text: '<?= addslashes($_SESSION['swal_alert']['message']) ?>',
						icon: '<?= $_SESSION['swal_alert']['type'] ?>',
						confirmButtonText: '<?= __("ok") ?>',
						customClass: {
							confirmButton: 'btn btn-primary'
						},
						buttonsStyling: false
					});
					<?php unset($_SESSION['swal_alert']); ?>
				<?php endif; ?>
				
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
			const employeeId = <?= json_encode($emprow['empid']); ?>;
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
							data: 'note_type',
							title: __('note_type')
						},
						{
							data: 'note',
							title: __('notes')
						},
						{
							data: 'attachment',
							title: __('attachment')
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
							targets: 3,
							width: '120px',
							render: function(data, type, row) {
								if (type === 'display') {
									if (!data) return '<span class="badge badge-secondary">General</span>';
									
									// Map note types to badge colors and labels
									const typeMap = {
										'warning': { color: 'warning', icon: 'fa-exclamation-triangle', label: 'Warning' },
										'sick_leave': { color: 'info', icon: 'fa-notes-medical', label: 'Sick Leave' },
										'appreciation': { color: 'success', icon: 'fa-star', label: 'Appreciation' },
										'violation': { color: 'danger', icon: 'fa-ban', label: 'Violation' },
										'absence': { color: 'dark', icon: 'fa-user-slash', label: 'Absence' },
										'late_arrival': { color: 'warning', icon: 'fa-clock', label: 'Late Arrival' },
										'performance_review': { color: 'primary', icon: 'fa-chart-line', label: 'Performance' },
										'training': { color: 'info', icon: 'fa-graduation-cap', label: 'Training' },
										'promotion': { color: 'success', icon: 'fa-arrow-up', label: 'Promotion' },
										'salary_adjustment': { color: 'primary', icon: 'fa-money-bill', label: 'Salary' },
										'disciplinary_action': { color: 'danger', icon: 'fa-gavel', label: 'Disciplinary' },
										'medical_report': { color: 'info', icon: 'fa-file-medical', label: 'Medical' },
										'general': { color: 'secondary', icon: 'fa-sticky-note', label: 'General' },
										'other': { color: 'secondary', icon: 'fa-ellipsis-h', label: 'Other' }
									};
									
									const typeInfo = typeMap[data] || { color: 'secondary', icon: 'fa-sticky-note', label: data };
									return `<span class="badge badge-${typeInfo.color}"><i class="fa ${typeInfo.icon}"></i> ${typeInfo.label}</span>`;
								}
								return data;
							}
						},
						{
							targets: 4,
							render: function(data, type, row) {
								if (type === 'display' && data) {
									// Truncate long notes and add tooltip
									if (data.length > 50) {
										return `<span title="${data.replace(/"/g, '&quot;')}">${data.substring(0, 50)}...</span>`;
									}
									return data;
								}
								return data;
							}
						},
						{
							targets: 5,
							width: '100px',
							orderable: false,
							render: function(data, type, row) {
								if (type === 'display') {
									if (data && data !== '') {
										// Extract file extension
										const fileExt = data.split('.').pop().toLowerCase();
										let iconClass = 'fa-file';
										let badgeColor = 'secondary';
										
										// Set icon and color based on file type
										if (fileExt === 'pdf') {
											iconClass = 'fa-file-pdf';
											badgeColor = 'danger';
										} else if (['doc', 'docx'].includes(fileExt)) {
											iconClass = 'fa-file-word';
											badgeColor = 'primary';
										} else if (['jpg', 'jpeg', 'png', 'gif'].includes(fileExt)) {
											iconClass = 'fa-file-image';
											badgeColor = 'success';
										}
										
										return `<a href="${data}" target="_blank" class="btn btn-sm btn-${badgeColor}" title="View Attachment">
											<i class="fa ${iconClass}"></i> View
										</a>`;
									}
									return '<span class="text-muted"><i class="fa fa-minus"></i> No File</span>';
								}
								return data;
							}
						},
						{
							targets: 6,
							width: '120px',
							render: function(data, type, row) {
								if (type === 'display' && data) {
									return new Date(data).toLocaleDateString('en-US', {
										year: 'numeric',
										month: 'short',
										day: 'numeric'
									});
								}
								return data;
							}
						},
						{
							targets: 7,
							width: '60px',
							orderable: false,
							render: function(data, type, row) {
								return `<button class="btn btn-danger btn-sm isDeleteAjax" data-tbl='emp_notice' data-id="${row.id}"><i class="fa fa-trash"></i></button>`;
							}
						},
						{
							targets: [0, 1, 5, 7],
							className: 'text-center'
						},
					],
					order: [
						[6, 'desc']
					], // Default sort by 'Created at' (now the 7th column) descending
					pageLength: 10,
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

		<!-- Evaluation Details Modal -->
		<div class="modal fade" id="evaluationModal" tabindex="-1" role="dialog" aria-labelledby="evaluationModalLabel" aria-hidden="true">
			<div class="modal-dialog modal-lg" role="document">
				<div class="modal-content">
					<div class="modal-header">
						<h5 class="modal-title" id="evaluationModalLabel"><?= __('evaluation_details', 'Evaluation Details') ?></h5>
						<button type="button" class="close" data-dismiss="modal" aria-label="Close">
							<span aria-hidden="true">&times;</span>
						</button>
					</div>
					<div class="modal-body" id="evaluationModalBody">
						<div class="text-center">
							<div class="spinner-border text-primary" role="status">
								<span class="sr-only"><?= __('loading') ?>...</span>
							</div>
						</div>
					</div>
					<div class="modal-footer">
						<button type="button" class="btn btn-secondary" data-dismiss="modal"><?= __('close') ?></button>
					</div>
				</div>
			</div>
		</div>

		<script>
		// Load evaluation details when view button is clicked
		$(document).on('click', '.view-eval-details', function() {
			var evalId = $(this).data('id');
			
			$('#evaluationModalBody').html('<div class="text-center"><div class="spinner-border text-primary" role="status"><span class="sr-only">Loading...</span></div></div>');
			
			$.ajax({
				url: 'includes/ajaxFile/ajaxEvaluation.php',
				method: 'POST',
				data: { 
					action: 'get_evaluation_details', 
					evaluation_id: evalId 
				},
				dataType: 'json',
				success: function(response) {
					if (response.status === 'success') {
						var data = response.data;
						var html = `
							<div class="row">
								<div class="col-md-6">
									<p><strong><?= __('employee_name', 'Employee Name') ?>:</strong> ${data.employee_name}</p>
									<p><strong><?= __('department', 'Department') ?>:</strong> ${data.dept_name}</p>
									<p><strong><?= __('position', 'Position') ?>:</strong> ${data.employee_position}</p>
								</div>
								<div class="col-md-6">
									<p><strong><?= __('evaluated_by', 'Evaluated By') ?>:</strong> ${data.manager_name || 'N/A'}</p>
									<p><strong><?= __('evaluation_date', 'Evaluation Date') ?>:</strong> ${data.created_at}</p>
									<p><strong><?= __('total_score', 'Total Score') ?>:</strong> <span class="badge badge-success" style="font-size: 16px;">${data.total_score}/100</span></p>
								</div>
							</div>
							<hr>
							<h5><?= __('evaluation_criteria', 'Evaluation Criteria') ?></h5>
							<table class="table table-bordered table-sm">
								<thead>
									<tr>
										<th><?= __('criteria', 'Criteria') ?></th>
										<th width="100"><?= __('score', 'Score') ?></th>
									</tr>
								</thead>
								<tbody>
									<tr><td>Punctuality Attendance</td><td class="text-center"><span class="badge badge-primary">${data.punctuality}/10</span></td></tr>
									<tr><td>Achieving at the specified time</td><td class="text-center"><span class="badge badge-primary">${data.achieving_time}/10</span></td></tr>
									<tr><td>Knowledge of job</td><td class="text-center"><span class="badge badge-primary">${data.job_knowledge}/10</span></td></tr>
									<tr><td>The Ability to solve problems</td><td class="text-center"><span class="badge badge-primary">${data.problem_solving}/10</span></td></tr>
									<tr><td>Receptiveness to Feedback and Instructions</td><td class="text-center"><span class="badge badge-primary">${data.feedback_receptiveness}/10</span></td></tr>
									<tr><td>Self & Professional Development</td><td class="text-center"><span class="badge badge-primary">${data.self_development}/10</span></td></tr>
									<tr><td>Work under pressure</td><td class="text-center"><span class="badge badge-primary">${data.work_under_pressure}/10</span></td></tr>
									<tr><td>Communication skills and Teamwork</td><td class="text-center"><span class="badge badge-primary">${data.communication_teamwork}/10</span></td></tr>
									<tr><td>Creativity and speed of response</td><td class="text-center"><span class="badge badge-primary">${data.creativity_response}/10</span></td></tr>
									<tr><td>Initiative and cooperation</td><td class="text-center"><span class="badge badge-primary">${data.initiative_cooperation}/10</span></td></tr>
								</tbody>
							</table>
							<hr>
							<h5><?= __('observation', 'Observation/Remarks') ?></h5>
							<p>${data.observation || '<?= __('no_observation', 'No observation provided.') ?>'}</p>
						`;
						$('#evaluationModalBody').html(html);
					} else {
						$('#evaluationModalBody').html('<div class="alert alert-danger">Failed to load evaluation details.</div>');
					}
				},
				error: function() {
					$('#evaluationModalBody').html('<div class="alert alert-danger">An error occurred while loading the evaluation details.</div>');
				}
			});
		});
		</script>

		<!-- Manual Loan Payment Modal Script -->
		<script>
		$(document).on('click', '.addManualPayment', function() {
			const loanId = $(this).data('loan-id');
			const empId = $(this).data('emp-id');
			const remainingBalance = parseFloat($(this).data('remaining'));
			
			Swal.fire({
				title: '<i class="fa fa-money"></i> ' + __('add_manual_payment'),
				html: `
					<div class="text-left">
						<div class="form-group">
							<label for="payment_date" class="font-weight-bold">${__('payment_date')} <span class="text-danger">*</span></label>
							<input type="date" id="payment_date" class="form-control swal2-input" value="${new Date().toISOString().split('T')[0]}" required>
						</div>
						<div class="form-group">
							<label for="payment_amount" class="font-weight-bold">${__('payment_amount')} (SAR) <span class="text-danger">*</span></label>
							<input type="number" id="payment_amount" class="form-control swal2-input" step="0.01" min="0.01" max="${remainingBalance.toFixed(2)}" placeholder="${__('max')}: ${remainingBalance.toFixed(2)}" required>
							<small class="text-muted">${__('remaining_balance')}: ${remainingBalance.toFixed(2)} SAR</small>
						</div>
						<div class="form-group">
							<label for="receipt_id" class="font-weight-bold">${__('receipt_number')}</label>
							<input type="text" id="receipt_id" class="form-control swal2-input" placeholder="${__('optional')}">
						</div>
						<div class="form-group">
							<label for="payment_proof" class="font-weight-bold">${__('payment_proof')} <span class="text-danger">*</span></label>
							<input type="file" id="payment_proof" class="form-control-file swal2-file" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx" required>
							<small class="text-muted">${__('allowed_formats')}: PDF, JPG, PNG, DOC, DOCX (Max 10MB)</small>
						</div>
						<div class="form-group">
							<label for="payment_note" class="font-weight-bold">${__('note')}</label>
							<textarea id="payment_note" class="form-control swal2-textarea" rows="2" placeholder="${__('optional')}"></textarea>
						</div>
					</div>
				`,
				width: '600px',
				showCancelButton: true,
				confirmButtonText: '<i class="fa fa-check"></i> ' + __('submit_payment'),
				cancelButtonText: '<i class="fa fa-times"></i> ' + __('cancel'),
				confirmButtonColor: '#28a745',
				cancelButtonColor: '#dc3545',
				showLoaderOnConfirm: true,
				preConfirm: () => {
					const payment_date = $('#payment_date').val();
					const payment_amount = parseFloat($('#payment_amount').val());
					const receipt_id = $('#receipt_id').val();
					const payment_proof = $('#payment_proof')[0].files[0];
					const payment_note = $('#payment_note').val();
					
					// Validation
					if (!payment_date) {
						Swal.showValidationMessage(__('payment_date_required'));
						return false;
					}
					if (!payment_amount || payment_amount <= 0) {
						Swal.showValidationMessage(__('invalid_payment_amount'));
						return false;
					}
					if (payment_amount > remainingBalance) {
						Swal.showValidationMessage(__('amount_exceeds_balance'));
						return false;
					}
					if (!payment_proof) {
						Swal.showValidationMessage(__('payment_proof_required'));
						return false;
					}
					
					// File validation
					const allowedTypes = ['application/pdf', 'image/jpeg', 'image/jpg', 'image/png', 
					                       'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
					if (!allowedTypes.includes(payment_proof.type)) {
						Swal.showValidationMessage(__('invalid_file_type'));
						return false;
					}
					if (payment_proof.size > 10 * 1024 * 1024) { // 10MB
						Swal.showValidationMessage(__('file_too_large'));
						return false;
					}
					
					// Prepare FormData
					const formData = new FormData();
					formData.append('ajaxType', 'add_manual_payment');
					formData.append('loan_id', loanId);
					formData.append('emp_id', empId);
					formData.append('payment_date', payment_date);
					formData.append('payment_amount', payment_amount);
					formData.append('receipt_id', receipt_id);
					formData.append('payment_proof', payment_proof);
					formData.append('payment_note', payment_note);
					
					// Debug logging
					console.log('=== FormData Debug ===');
					console.log('loanId:', loanId);
					console.log('empId:', empId);
					console.log('payment_proof file:', payment_proof);
					for (let pair of formData.entries()) {
						console.log(pair[0] + ':', pair[1]);
					}
					
					// Submit via AJAX
					return $.ajax({
						url: './includes/ajaxFile/ajaxLoan.php',
						type: 'POST',
						data: formData,
						processData: false,
						contentType: false,
						dataType: 'json'
					})
					.then(response => {
						if (response.type !== 'success') {
							throw new Error(response.message || __('payment_failed'));
						}
						return response;
					})
					.catch(error => {
						Swal.showValidationMessage(`${__('request_failed')}: ${error.message || error}`);
					});
				},
				allowOutsideClick: () => !Swal.isLoading()
			}).then((result) => {
				if (result.isConfirmed && result.value) {
					Swal.fire({
						title: result.value.title || __('success'),
						text: result.value.message,
						icon: 'success',
						allowOutsideClick: false
					}).then(() => {
						location.reload();
					});
				}
			});
		});
		</script>

	</body>

	</html>
<?php } ?>