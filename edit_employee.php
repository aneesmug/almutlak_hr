<?php
// require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/session_check.php';
require_once __DIR__ . '/includes/special_access_helper.php';

// Request-blocking fields are only writable/visible by admins or employees with the matching special access grant.
$canManageRequestBlock = ($is_system_admin || user_has_special_access($conDB, $empid, 'manage_employee_request_block', $user_role ?? '', $user_type ?? '', $is_system_admin));
$canManageRequestTypeBlock = ($is_system_admin || user_has_special_access($conDB, $empid, 'manage_employee_request_type_block', $user_role ?? '', $user_type ?? '', $is_system_admin));
$globallyBlockedRequestTypes = get_global_blocked_request_types($conDB);

// ============================================================
// HANDLE POST REQUEST FIRST (BEFORE ANY OUTPUT)
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit'])) {
	// Process the form submission before any HTML output
	try {
		$formData = $_POST;
		// Whitelist of allowed database columns
		$allowedColumns = [
			'name',
			'iqama',
			'iqama_exp_g',
			'iqama_exp',
			'passport_number',
			'passport_exp',
			'mobile',
			'emg_mobile',
			'emg_name',
			'country',
			'dept',
			'city_id',
			'location_id',
			'sub_dept_id',
			'emptype',
			'supervisor_id',
			'joining_date',
			'dob',
			'dob_h',
			't_shirt_size',
			'sex',
			'mar_status',
			'blood_type',
			'emp_sup_type',
			'actual_job',
			'vac_period',
			'vacation_days',
			'salary',
			'bank_name',
			'iban',
			'email',
			'c_email',
			'address',
			'insurance_no',
			'comp_no',
			'insurance_exp',
			'gosi',
			'insurance_class',
			'probation',
			'payment_type',
			'is_overtime_eligible',
			'allow_emergency_vacation',
			'requests_blocked',
			'blocked_request_types'
		];

		if (!$canManageRequestBlock) {
			unset($formData['requests_blocked']);
		}
		if (!$canManageRequestTypeBlock) {
			unset($formData['blocked_request_types']);
		} elseif (isset($formData['blocked_request_types'])) {
			// Submitted via a hidden input as a JSON array string (see the checkbox group JS on this page).
			$formData['blocked_request_types'] = json_encode(decode_blocked_request_types((string)$formData['blocked_request_types']));
		}

		// FETCH OLD VALUES BEFORE UPDATE
		$employee_id_from_form = $formData['emp_id'];
		$old_stmt = $pdo->prepare("SELECT * FROM employees WHERE emp_id = :emp_id");
		$old_stmt->execute([':emp_id' => $employee_id_from_form]);
		$old_employee = $old_stmt->fetch(PDO::FETCH_ASSOC);
		$old_values = [];
		
		// Prepare the update data
		$setParts = [];
		$formData['dept'] = $formData['department'];
		
		$params = [':emp_id' => $employee_id_from_form];

		foreach ($formData as $field => $value) {
			// Skip non-database fields and the primary key
			if ($field === 'emp_id' || $field === 'submit' || !in_array($field, $allowedColumns)) {
				continue;
			}
			if ($field === 'salary') {
				$value = str_replace(',', '', $value);
			} elseif ($field === 'iban') {
				$value = str_replace(' ', '', $value);
			}
			$setParts[] = "`$field` = :$field";
			$params[":$field"] = $value;
			
			// Collect old values for logging
			if ($old_employee && isset($old_employee[$field])) {
				$old_values[$field] = $old_employee[$field];
			}
		}
		
		// Build and execute the query
		$sql = "UPDATE `employees` SET " . implode(', ', $setParts) . " WHERE `emp_id` = :emp_id";
		$stmt = $pdo->prepare($sql);
		$stmt->execute($params);

		// Keep admin_login.dept in sync with employees.dept (all_users.php reads admin_login's own copy).
		if (isset($params[':dept'])) {
			$syncDeptStmt = $pdo->prepare("UPDATE `admin_login` SET `dept` = :dept WHERE `emp_id` = :emp_id");
			$syncDeptStmt->execute([':dept' => $params[':dept'], ':emp_id' => $employee_id_from_form]);
		}

		// LOG EMPLOYEE UPDATE ACTION
		if ($stmt->rowCount() > 0) {
			$new_values = [];
			foreach ($params as $key => $value) {
				if ($key !== ':emp_id') {
					$field = ltrim($key, ':');
					$new_values[$field] = $value;
				}
			}
			ActivityLogger::logUpdate(
				'Employee',
				'edit_employee.php',
				$employee_id_from_form,
				$old_values,
				$new_values,
				"Updated employee: " . ($old_employee['name'] ?? 'Unknown'),
				'employees'
			);
		}
		
		// Store alert data in session for display on the view page
		if ($stmt->rowCount() > 0) {
			$_SESSION['swal_alert'] = [
				'title' => __("success"),
				'message' => __("employee_details_edited_successfully"),
				'type' => 'success'
			];
		} else {
			$_SESSION['swal_alert'] = [
				'title' => __("info"),
				'message' => __("no_changes_made"),
				'type' => 'info'
			];
		}
		
		header("Location: view_employee.php?emp_id={$employee_id_from_form}");
		exit();
		
	} catch (PDOException $e) {
		die(__("database_error") . " " . $e->getMessage());
	}
}
// ============================================================
// END POST HANDLING
// ============================================================

$query = mysqli_query($conDB, "SELECT * FROM `admin_login` WHERE `id_iqama`='" . $username . "'");
if (mysqli_num_rows($query) == 1) {
	include("./includes/avatar_select.php");

	include("./includes/Hijri_GregorianConvert.php");
	$DateConv = new Hijri_GregorianConvert;
	// $format="DD/MM/YYYY";
	$format = "YYYY-MM-DD";

	// --- FIX 1: Check for emp_id BEFORE running the query ---
	if(!isset($_GET['emp_id']) || empty($_GET['emp_id']) ){
		// On POST, the emp_id might be in $_POST, not $_GET
		// But the page load logic depends on GET.
		// If it's a POST request, we'll let the POST handler deal with it,
		// but we must ensure $emprow is set correctly.
		// The best fix is to ensure the form action preserves the GET param.
		// For now, if it's not a POST, we redirect.
		if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
			header("Location: ./reg_employee.php");
			exit;
		}
		// If it IS a post, we need to get the emp_id from the form
		// But the query logic below relies on $_GET.
		// This structure is problematic.
	}
	
	// Let's ensure $_GET['emp_id'] is set even on POST, by fixing the form action.
	// We will still run the original query logic.
	require("./includes/emp_query.php");


	if (mysqli_num_rows($get_emp_data) !== 0) {
		$allRecords = mysqli_fetch_all($get_emp_data, MYSQLI_ASSOC);
		foreach ($allRecords as $rec) {
			$emprow = $rec;
		}

		// EMPLOYEE MODIFICATION ACCESS CONTROL
		// Only system admin and HR department members can modify employees
		$can_modify_employee = (
			$is_system_admin || 
			$isDeptHr
		);
		
		if (!$can_modify_employee) {
			$_SESSION['error_msg'] = '<div class="col-xl-12">
					<div class="alert alert-danger bg-danger text-white border-0" role="alert">
						<b>Access Denied!</b> 
						<h4>You do not have permission to modify employee information.</h4>
					</div>
				</div>';
			header("Location: ./view_employee.php?emp_id=" . $_GET['emp_id']);
			exit;
		}
		// If we get here, access is granted
		if ($emprow["status"] == "0" && $emprow["note"] == "expired") {
			$note_get = __("expired");
		} elseif ($emprow["status"] == "0" && $emprow["note"] == "terminat") {
			$note_get = __("terminated");
		}
	} else {
		//when the id not equals id show database
		// Avoid redirect loops on POST if emp_query.php failed
		if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
			header("Location: ./reg_employee.php");
			exit; // Added exit
		} else {
			// Handle POST error differently, maybe?
			// For now, we'll assume $emprow is needed for the form,
			// but the POST handler will be the primary logic.
		}
	}

?>

	<!doctype html>
	<html lang="<?= $current_lang ?? 'en' ?>" <?= ($is_rtl ?? false) ? 'dir="rtl"' : '' ?>

	<head>
		<meta charset="utf-8" />
		<title><?= $site_title ?> - <?= __("all_employees") ?></title>
		<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
		<!--        <meta content="A fully featured admin theme which can be used to build CRM, CMS, etc." name="description" />-->
		<meta content="Anees Afzal" name="author" />
		<meta http-equiv="X-UA-Compatible" content="IE=edge" />
		<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />

		<!-- App favicon -->
		<link rel="shortcut icon" href="<?=get_setting($conDB, 'favicon')?>">

		<!-- Modal -->
		<link href="./plugins/custombox/css/custombox.min.css" rel="stylesheet">

		<!-- Plugins css -->
		<link href="./plugins/bootstrap-timepicker/bootstrap-timepicker.min.css" rel="stylesheet">
		<link href="./plugins/bootstrap-colorpicker/css/bootstrap-colorpicker.min.css" rel="stylesheet">
		<link href="./plugins/bootstrap-datepicker/css/bootstrap-datepicker.min.css" rel="stylesheet">
		<link href="./plugins/clockpicker/css/bootstrap-clockpicker.min.css" rel="stylesheet">
		<link href="./plugins/bootstrap-daterangepicker/daterangepicker.css" rel="stylesheet">

		<link rel="stylesheet" href="./plugins/croppie/croppie.css">

		<link rel="stylesheet" href="./plugins/bootstrap-select/css/bootstrap-select.min.css">
		<link rel="stylesheet" href="./plugins/select2/css/select2.min.css">

		<link href="./plugins/bootstrap-timepicker/hijri_css/bootstrap-datetimepicker.css" rel="stylesheet">
		<link href="./plugins/bootstrap-timepicker/hijri_css/bootstrap-datetimepicker.min.css" rel="stylesheet">

		<!-- App css -->
		<link href="assets/css/bootstrap.min.css" rel="stylesheet" type="text/css" />
		<link href="assets/css/icons.css" rel="stylesheet" type="text/css" />
		<link href="assets/css/metismenu.min.css" rel="stylesheet" type="text/css" />
		<link href="assets/css/style.css" rel="stylesheet" type="text/css" />
		<link href="assets/css/style_dark.css" rel="stylesheet" type="text/css" />

		<script src="assets/js/modernizr.min.js"></script>
		<?php if ($is_rtl): ?>
			<link href="assets/css/style_rtl.css" rel="stylesheet" type="text/css" />
		<?php endif; ?>
		<script>
			window.lang = <?= json_encode($GLOBALS['translations'] ?? []) ?>;
		</script>


	</head>

	<body class="enlarged" data-keep-enlarged="true" data-page="edit-employee">

		<!-- Begin page -->
		<div id="wrapper">

			<!-- ========== Left Sidebar Start ========== -->
			<div class="left side-menu">

				<div class="slimscroll-menu" id="remove-scroll">

					<!-- LOGO -->
					<div class="topbar-left">
						<a href="dashboard.php" class="logo">
							<span>
								<img src="<?=get_setting($conDB, 'logo')?>" alt="" height="22">
							</span>
							<i>
								<img src="<?=get_setting($conDB, 'white_logo')?>" alt="" height="28">
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
						<!-- end row -->

						<div class="row">
							<div class="col-md-12">
								<div class="card-box editEmployeeAttr">
									<h4 class="m-t-0 header-title"><?= __("edit_employee_title") ?></h4>
									<!-- --- FIX 4: Ensure the form action retains the emp_id query string --- -->
									<form action="<?= $_SERVER['PHP_SELF']; ?>?emp_id=<?= htmlspecialchars($emprow['empid'] ?? $_GET['emp_id']); ?>" method="post" enctype="multipart/form-data" id="registration" class="registration">
										<div class="form-row">
											<div class="form-group col-md-2">
												<label for="name" class="col-form-label"><?= __("employee_name_label") ?><span class="text-danger">*</span></label>
												<input type="text" name="name" value="<?= ucwords($emprow['name']); ?>" parsley-trigger="change" class="form-control" id="name" autofocus required />
											</div>
											<div class="form-group col-md-1">
												<label for="emp_id" class="col-form-label"><?= __("employee_id_label") ?><span class="text-danger">*</span></label>
												<input type="text" name="emp_id" value="<?= $emprow['empid'] ?>" parsley-trigger="change" class="form-control" id="emp_id" readonly />
											</div>
											<div class="form-group col-md-1">
												<label for="iqama" class="col-form-label"><?= __("id_iqama_label") ?><span class="text-danger">*</span></label>
												<input type="text" name="iqama" value="<?= $emprow['iqama'] ?>" parsley-trigger="change" data-mask="9999999999" class="form-control" id="iqama" required />
											</div>
											<div class="form-group col-md-2">
												<label for="iqama_exp" class="col-form-label"><?= __("id_iqama_expire_gregorian_label") ?><span class="text-danger">*</span></label>
												<input type="text" name="iqama_exp_g" value="<?= $emprow['iqama_exp_g'] ?>" parsley-trigger="change" class="form-control" id="iqama_exp" />
											</div>
											<div class="form-group col-md-2">
												<label for="dateofidHijri" class="col-form-label"><?= __("id_iqama_expire_hijri_label") ?><span class="text-danger">*</span></label>
												<input type="text" name="iqama_exp" value="<?= $emprow['iqama_exp'] ?>" parsley-trigger="change" class="form-control" id="dateofidHijri" />
											</div>
											<div class="form-group col-md-2">
												<label for="passport_number" class="col-form-label"><?= __("passport_no_label") ?></label>
												<input type="text" name="passport_number" value="<?= $emprow['passport_number'] ?>" parsley-trigger="change" class="form-control" id="passport_number">
											</div>
											<div class="form-group col-md-2">
												<label for="passport_exp" class="col-form-label"><?= __("passport_expire_label") ?></label>
												<input type="text" name="passport_exp" value="<?= $emprow['passport_exp'] ?>" parsley-trigger="change" class="form-control" id="passport_exp">
											</div>
											<div class="form-group col-md-2">
												<label for="mobile" class="col-form-label"><?= __("mobile_no_label") ?>
													<?php if ($emprow['emp_sup_type'] !== "man_power"): ?>
														<span class="text-danger">*</span>
													<?php endif ?>
												</label>
												<input type="text" name="mobile" value="<?= $emprow['mobile'] ?>" parsley-trigger="change" class="form-control" id="mobile" required />
											</div>
											<div class="form-group col-md-2">
												<label for="emg_mobile" class="col-form-label"><?= __("emergency_mobile_no_label") ?>
													<?php if ($emprow['emp_sup_type'] !== "man_power"): ?>
														<span class="text-danger">*</span>
													<?php endif ?>
												</label>
												<input type="text" name="emg_mobile" value="<?= $emprow['emg_mobile'] ?>" parsley-trigger="change" class="form-control" />
											</div>
											<div class="form-group col-md-2">
												<label for="emg_name" class="col-form-label"><?= __("emergency_contact_name_label") ?>
													<?php if ($emprow['emp_sup_type'] !== "man_power"): ?>
														<span class="text-danger">*</span>
													<?php endif ?>
												</label>

												<input type="text" name="emg_name" value="<?= $emprow['emg_name'] ?>" parsley-trigger="change" class="form-control" />
											</div>
											<div class="form-group col-md-2">
												<label for="country" class="col-form-label"><?= __("nationality_label") ?><span class="text-danger">*</span></label>
												<select class="form-control" name="country" required />
												<option value=""><?= __("select_option") ?></option>
												<?php
												$query_country = mysqli_query($conDB, "SELECT * FROM `countries` ORDER BY `name` REGEXP '^[^A-Za-z]' ASC, `name`");
												while ($rec_con = mysqli_fetch_assoc($query_country)) {
												?>
													<option value="<?= $rec_con["id"] ?>" <?= ($emprow['country_id'] == $rec_con["id"]) ? "selected=selected" : "" ?>><?= ($is_rtl ?? false ? $rec_con["name_ar"] : $rec_con["name"]) ?></option>
												<?php } ?>
												</select>
											</div>
											<div class="form-group col-md-2">
												<label for="department" class="col-form-label"><?= __("department_label") ?><span class="text-danger">*</span></label>
												<select class="form-control department" name="department" id="department" required />
												<option value=""><?= __("select_option") ?></option>
												<?php
												$query_dep_nme = mysqli_query($conDB, "SELECT * FROM `department` ORDER BY `dep_nme` REGEXP '^[^A-Za-z]' ASC, dep_nme");
												while ($rec = mysqli_fetch_assoc($query_dep_nme)) {
												?>
													<option value="<?= $rec["id"] ?>" <?= ($rec["id"] == $emprow['dept']) ? "selected=selected" : "" ?>><?= ($is_rtl ?? false ? $rec["dep_nme_ar"] : $rec["dep_nme"]) ?></option>
												<?php } ?>
												</select>
											</div>
											<div class="form-group col-md-2">
												<label for="city_id" class="col-form-label"><?= __("city_label", "City") ?><span class="text-danger">*</span></label>
												<select class="form-control select2" name="city_id" id="city_id" required>
												<option value=""><?= __("select_option") ?></option>
												<?php
												$query_cities = mysqli_query($conDB, "SELECT `id`, `name_en`, `name_ar` FROM `saudi_cities` ORDER BY `name_en` ASC");
												while ($rec = mysqli_fetch_assoc($query_cities)) {
												?>
													<option value="<?= $rec["id"] ?>" <?= ($rec["id"] == $emprow['city_id']) ? "selected=selected" : "" ?>><?= ($is_rtl ?? false ? $rec["name_ar"] : $rec["name_en"]) ?></option>
												<?php } ?>
												</select>
											</div>
											<div class="form-group col-md-2">
												<label for="location_id" class="col-form-label"><?= __("location_label", "Location") ?><span class="text-danger">*</span></label>
												<select class="form-control select2" name="location_id" id="location_id" required>
												<option value=""><?= __("select_a_city_first", "Select a City First") ?></option>
												</select>
											</div>
											<div class="form-group col-md-2">
												<label for="sub_dept_id" class="col-form-label"><?= __("sub_department_label", "Sub-Department") ?></label>
												<select class="form-control select2" name="sub_dept_id" id="sub_dept_id">
												<option value=""><?= __("select_a_department_first", "Select a Department First") ?></option>
												</select>
											</div>
											<div class="form-group col-md-2">
												<label for="emptype" class="col-form-label"><?= __("employee_type_label") ?><span class="text-danger">*</span></label>
												<select class="form-control" name="emptype" required />
												<option value=""><?= __("select_option") ?></option>
												<option value="Manager" <?= ($emprow['emptype'] == 'Manager' ? 'selected' : '') ?>><?= __("manager_option") ?></option>
												<option value="Supervisor" <?= ($emprow['emptype'] == 'Supervisor' ? 'selected' : '') ?>><?= __("supervisor_option") ?></option>
												<option value="Supporter" <?= ($emprow['emptype'] == 'Supporter' ? 'selected' : '') ?>><?= __("supporter_option") ?></option>
												</select>
											</div>
											<div class="form-group col-md-2">
												<label for="supervisor_id" class="col-form-label"><?= __("direct_supervisor_label") ?? "Direct Supervisor" ?></label>
												<select class="form-control select2" name="supervisor_id" id="supervisor_id">
													<option value=""><?= __("no_supervisor") ?? "No Direct Supervisor" ?></option>
													<?php
													// Get list of potential supervisors (Managers and Supervisors)
													$supervisor_query = mysqli_query($conDB, "
														SELECT `emp_id`, `name`, `emptype`, `dept` 
														FROM `employees` 
														WHERE `status` = 1 
														AND `emptype` IN ('Manager', 'Supervisor')
														AND `emp_id` != '{$emprow['emp_id']}'
														ORDER BY `dept`, `emptype` = 'Manager' DESC, `name` ASC
													");
													while ($supervisor = mysqli_fetch_assoc($supervisor_query)) {
														$selected = (isset($emprow['supervisor_id']) && $emprow['supervisor_id'] == $supervisor['emp_id']) ? 'selected' : '';
														$dept_name = '';
														$dept_q = mysqli_query($conDB, "SELECT `dep_nme` FROM `department` WHERE `id` = '{$supervisor['dept']}' LIMIT 1");
														if ($dept_r = mysqli_fetch_assoc($dept_q)) {
															$dept_name = ' - ' . $dept_r['dep_nme'];
														}
														echo "<option value='{$supervisor['emp_id']}' {$selected}>" . 
															 getDisplayName($supervisor['name']) . " ({$supervisor['emp_id']}) - " . getDisplayName($supervisor['emptype']) . " - " . ($is_rtl ?? false ? $emprow['deptnme_ar'] : $emprow['deptnme']) . "</option>";
													}
													?>
												</select>
												<small class="form-text text-muted"><?= __("supervisor_help_text") ?? "Assign direct supervisor for leave approvals" ?></small>
											</div>
											<div class="form-group col-md-2">
												<label for="joining_date" class="col-form-label"><?= __("joining_date_label") ?>
													<?php if ($emprow['emp_sup_type'] !== "man_power"): ?>
														<span class="text-danger">*</span>
													<?php endif ?>
												</label>
												<input type="text" name="joining_date" value="<?= $emprow['joining_date'] ?>" parsley-trigger="change" class="form-control" id="joining_date" required />
											</div>
											<div class="form-group col-md-2">
												<label for="dob" class="col-form-label"><?= __("dob_gregorian_label") ?>
													<?php if ($emprow['emp_sup_type'] !== "man_power"): ?>
														<span class="text-danger">*</span>
													<?php endif ?>
												</label>
												<input type="text" name="dob" value="<?= $emprow['dob'] ?>" parsley-trigger="change" class="form-control" id="dob" required />
											</div>
											<div class="form-group col-md-2">
												<label for="dateofbirthHijri" class="col-form-label"><?= __("dob_hijri_label") ?>
													<?php if ($emprow['emp_sup_type'] !== "man_power"): ?>
														<span class="text-danger">*</span>
													<?php endif ?>
												</label>
												<input type="text" name="dob_h" value="<?= ($emprow['dob_h'] == "") ? $DateConv->GregorianToHijri($emprow['dob'], $format) : $emprow['dob_h'] ?>" parsley-trigger="change" class="form-control" id="dateofbirthHijri" required />
											</div>

											<div class="form-group col-md-2">
												<label for="t_shirt_size" class="col-form-label"><?= __("t_shirt_size_label") ?></label>
												<input type="text" name="t_shirt_size" value="<?= $emprow['t_shirt_size'] ?>" parsley-trigger="change" class="form-control" id="t_shirt_size">
											</div>
											<div class="form-group col-md-2">
												<label class="font-14 mt-3 mb-2 radioalign"><?= __("gender_label") ?><span class="text-danger">*</span></label>
												<div class="radio radio-info form-check-inline">
													<input type="radio" id="inlineRadio3" value="1" name="sex" <?= ($emprow['sex'] == "male") ? 'checked' : ''; ?>>
													<label for="inlineRadio3" class="atch"><i class="mdi mdi-human-male"></i> <?= __("male_option") ?></label>
												</div>
												<div class="radio radio-info form-check-inline">
													<input type="radio" id="inlineRadio1" value="2" name="sex" <?= ($emprow['sex'] == "female") ? 'checked' : '' ?>>
													<label for="inlineRadio1" class="atch"><i class="mdi mdi-human-female"></i> <?= __("female_option") ?> </label>
												</div>
											</div>
											<div class="form-group col-md-2">
												<label class="font-14 mt-3 mb-2 radioalign"><?= __("marital_status_label") ?><span class="text-danger">*</span></label>
												<div class="radio radio-info form-check-inline">
													<input type="radio" id="married" value="married" name="mar_status" <?= ($emprow['mar_status'] == "married") ? 'checked' : '' ?>>
													<label for="married" class="atch"><i class="mdi mdi-ring"></i> <?= __("married_option") ?></label>
												</div>
												<div class="radio radio-info form-check-inline">
													<input type="radio" id="single" value="single" name="mar_status" <?= ($emprow['mar_status'] == "single") ? 'checked' : '' ?>>
													<label for="single" class="atch"><i class="mdi mdi-account-convert"></i> <?= __("single_option") ?></label>
												</div>
											</div>
											<div class="form-group col-md-2">
												<label for="blood_type" class="col-form-label"><?= __("blood_type_label") ?>
													<?php if ($emprow['emp_sup_type'] !== "man_power"): ?>
														<span class="text-danger">*</span>
													<?php endif ?>
												</label>
												<select class="form-control" name="blood_type" />
												<?php if (empty($emprow['blood_type'])) { ?>
													<option value=""><?= __("select_option") ?></option>
												<?php } else { ?>
													<option value="<?= $emprow['blood_type'] ?>"><?= $emprow['blood_type'] ?></option>
												<?php } ?>
												<option value="A+">A+</option>
												<option value="B+">B+</option>
												<option value="O+">O+</option>
												<option value="AB+">AB+</option>
												<option value="A-">A-</option>
												<option value="B-">B-</option>
												<option value="O-">O-</option>
												<option value="AB-">AB-</option>
												</select>
											</div>
											<div class="form-group col-md-2">
												<label for="emp_sup_type" class="col-form-label"><?= __("sponsorship_label") ?><span class="text-danger">*</span></label>
												<select class="form-control" name="emp_sup_type" id="emp_sup_type" required />
												<option value=""><?= __("select_option") ?></option>
												<?php
												$query_cp = mysqli_query($conDB, "SELECT * FROM `sponsorship` ORDER BY `sponsor` REGEXP '^[^A-Za-z]' ASC, `sponsor`");
												while ($rec_con = mysqli_fetch_assoc($query_cp)) {
												?>
													<option value="<?= $rec_con["id"]; ?>" <?= ($rec_con["id"] == $emprow['emp_sup_type']) ? "selected=selected" : "" ?>><?= getDisplayName($rec_con["sponsor"]); ?></option>
												<?php } ?>
												</select>
											</div>
											<div class="form-group col-md-2">
												<label for="comp_no" class="col-form-label"><?= __("company_label") ?><span class="text-danger">*</span></label>
												<select class="form-control" name="comp_no" id="comp_no" required />
												<option value=""><?= __("select_option") ?></option>
												<?php
												$query_cp = mysqli_query($conDB, "SELECT * FROM `companies` ORDER BY `comp_name` REGEXP '^[^A-Za-z]' ASC, `comp_name`");
												while ($rec_con = mysqli_fetch_assoc($query_cp)) {
												?>
													<option value="<?= $rec_con["comp_id"]; ?>" <?= ($rec_con["comp_id"] == $emprow['comp_no']) ? "selected=selected" : "" ?>><?= getDisplayName($rec_con["comp_name"]); ?></option>
												<?php } ?>
												</select>
											</div>
											<div class="form-group col-md-2">
												<label for="actual_job" class="col-form-label"><?= __("actual_job_label") ?><span class="text-danger">*</span></label>
												<select class="form-control" name="actual_job" id="actual_job" required />
												<option value=""><?= __("select_option") ?></option>
												<?php
												$query_cp = mysqli_query($conDB, "SELECT * FROM `ac_jobs` ORDER BY `job` REGEXP '^[^A-Za-z]' ASC, `job`");
												while ($rec_con = mysqli_fetch_assoc($query_cp)) {
												?>
													<option value="<?= $rec_con["id"]; ?>" <?= ($rec_con["id"] == $emprow['actual_job']) ? "selected=selected" : "" ?>><?= ($is_rtl ?? false ? $rec_con["job_ar"] : $rec_con["job"]); ?></option>
												<?php } ?>
												</select>
											</div>
											<div class="form-group col-md-2">
												<label for="vac_period" class="col-form-label"><?= __("contract_period_label") ?>
													<?php if ($emprow['emp_sup_type'] !== "man_power"): ?>
														<span class="text-danger">*</span></label>
											<?php endif ?>
											<select class="form-control vac_period" name="vac_period" id="vac_period" required />
											<?php
											$query_cp = mysqli_query($conDB, "SELECT * FROM `contract_period` ORDER BY `period` REGEXP '^[^A-Za-z]' ASC, period");
											while ($rec_con = mysqli_fetch_assoc($query_cp)) {
											?>
												<option value="<?= $rec_con["id"]; ?>" <?= ($rec_con["id"] == $emprow['vac_period']) ? "selected=selected" : "" ?>><?= getDisplayName($rec_con["period"]); ?></option>
											<?php } ?>
											</select>
											</div>
											<div class="form-group col-md-2">
												<label for="vacation_days" class="col-form-label"><?= __("vacation_days_label") ?>
													<?php if ($emprow['emp_sup_type'] !== "man_power"): ?>
														<span class="text-danger">*</span>
													<?php endif ?>
												</label>
												<input type="text" name="vacation_days" class="form-control" id="vacation_days" readonly value="<?= ($emprow['vacation_days']) ? $emprow['vacation_days'] : "" ?>" required />
											</div>
											<div class="form-group col-md-2">
												<label for="salary" class="col-form-label"><?= __("salary_label") ?><span class="text-danger">*</span></label>
												<input type="text" name="salary" value="<?= $emprow['salary'] ?>" class="form-control autonumber" data-v-max="90000" data-v-min="0" id="salary" required />
											</div>
											<div class="form-group col-md-2">
												<label for="bank_name" class="col-form-label"><?= __("bank_name_label") ?>
													<?php if ($emprow['emp_sup_type'] !== "man_power"): ?>
														<span class="text-danger">*</span></label>
											<?php endif ?>
											<select class="form-control" name="bank_name" id="bank_name" required />
											<option value=""><?= __("select_option") ?></option>
											<?php
											$query_bank = mysqli_query($conDB, "SELECT * FROM `bank_list` ORDER BY `name` REGEXP '^[^A-Za-z]' ASC, name");
											while ($rec_con = mysqli_fetch_assoc($query_bank)) {
											?>
												<option value="<?= $rec_con["bnk_id"] ?>" <?= ($emprow['bnk_id'] == $rec_con["bnk_id"]) ? "selected=selected" : "" ?>><?= ($is_rtl ?? false ? $rec_con["bank_name_ar"] : $rec_con["name"]) ?></option>
											<?php } ?>
											</select>
											</div>
											<div class="form-group col-md-2">
												<label for="iban" class="col-form-label"><?= __("iban_label") ?>
													<?php if ($emprow['emp_sup_type'] !== "man_power"): ?>
														<span class="text-danger">*</span>
													<?php endif ?>
												</label>
												<input type="text" name="iban" class="form-control" id="iban" data-mask="SA99 9999 9999 9999 9999 9999" value="<?= $emprow['iban'] ?>">
											</div>

											<div class="form-group col-md-2">
												<label for="email" class="col-form-label"><?= __("employee_email_label") ?></label>
												<input type="email" name="email" value="<?= $emprow['email'] ?>" parsley-trigger="change" class="form-control" id="email">
											</div>
											<div class="form-group col-md-2">
												<label for="c_email" class="col-form-label"><?= __("company_email_label") ?></label>
												<input type="email" name="c_email" value="<?= $emprow['c_email'] ?>" parsley-trigger="change" class="form-control" id="c_email">
											</div>
											<div class="form-group col-md-2">
												<label for="address" class="col-form-label"><?= __("employee_address_label") ?>
													<?php if ($emprow['emp_sup_type'] !== "man_power"): ?>
														<span class="text-danger">*</span>
													<?php endif ?>
												</label>
												<input type="address" name="address" value="<?= $emprow['address'] ?>" parsley-trigger="change" class="form-control" id="address">
											</div>
											<div class="form-group col-md-2">
												<label for="insurance_no" class="col-form-label"><?= __("insurance_no_label") ?></label>
												<input type="text" name="insurance_no" value="<?= $emprow['insurance_no'] ?>" parsley-trigger="change" class="form-control" id="insurance_no">
											</div>
											<div class="form-group col-md-2">
												<label for="insurance_class" class="col-form-label"><?= __("insurance_class_label") ?>
													<?php if ($emprow['emp_sup_type'] !== "man_power"): ?>
														<span class="text-danger">*</span>
													<?php endif ?>
												</label>
												<select class="form-control" name="insurance_class">
													<option value=""><?= __("select_option") ?></option>
													<option value="A" <?= ($emprow['insurance_class'] == 'A' ? 'selected' : '') ?>>A</option>
													<option value="B" <?= ($emprow['insurance_class'] == 'B' ? 'selected' : '') ?>>B</option>
													<option value="C" <?= ($emprow['insurance_class'] == 'C' ? 'selected' : '') ?>>C</option>
													<option value="CLT" <?= ($emprow['insurance_class'] == 'CLT' ? 'selected' : '') ?>>CLT</option>
													<option value="VIP" <?= ($emprow['insurance_class'] == 'VIP' ? 'selected' : '') ?>>VIP</option>
												</select>
											</div>
											<div class="form-group col-md-2">
												<label for="insurance_exp" class="col-form-label"><?= __("insurance_expire_label") ?></label>
												<input type="text" name="insurance_exp" value="<?= $emprow['insurance_exp'] ?>" parsley-trigger="change" class="form-control" id="insurance_exp">
											</div>

											<?php if ($emprow['country'] == 191) { ?>
												<div class="form-group col-md-2">
													<label for="gosi" class="col-form-label"><?= __("gosi_label") ?><span class="text-danger">*</span></label>
													<div class="input-group">
														<div class="input-group-prepend">
															<div class="input-group-text">%</div>
														</div>
														<input type="text" name="gosi" value="<?= $emprow['gosi'] ?>" class="form-control" id="gosi" required />
													</div>
												</div>
											<?php } ?>

											<div class="form-group col-md-2">
												<label for="probation" class="col-form-label"><?= __("probation_period_label") ?></label>
												<select class="form-control" name="probation">
													<option value=""><?= __("select_option") ?></option>
													<option value="" <?= ($emprow['probation'] == '' ? 'selected' : '') ?>><?= __("no_probation_period_option") ?></option>
													<option value="3" <?= ($emprow['probation'] == '3' ? 'selected' : '') ?>><?= __("3_months_option") ?></option>
													<option value="6" <?= ($emprow['probation'] == '6' ? 'selected' : '') ?>><?= __("6_months_option") ?></option>
												</select>
											</div>

											<div class="form-group col-md-2">
												<label for="payment_type" class="col-form-label"><?= __("salary_payment_type_label") ?>
													<?php if ($emprow['emp_sup_type'] !== "man_power"): ?>
														<span class="text-danger">*</span>
													<?php endif ?>
												</label>
												<select class="form-control" name="payment_type" required>
													<option value=""><?= __("select_option") ?></option>
													<option value="1" <?= ($emprow['payment_type'] == '1' ? 'selected' : '') ?>><?= __("bank_option") ?></option>
													<option value="2" <?= ($emprow['payment_type'] == '2' ? 'selected' : '') ?>><?= __("cash_option") ?></option>
													<option value="3" <?= ($emprow['payment_type'] == '3' ? 'selected' : '') ?>><?= __("hold_option") ?></option>
												</select>
											</div>

											<div class="form-group col-md-4">
												<label class="col-form-label d-block"><?= __('eligible_for_overtime', 'Eligible For Overtime') ?></label>
												<div class="radio radio-info form-check-inline">
													<input type="radio" id="overtimeEligibleYes" name="is_overtime_eligible" value="1" <?= ((string)($emprow['is_overtime_eligible'] ?? '0') === '1') ? 'checked' : '' ?> required>
													<label for="overtimeEligibleYes" class="atch"><?= __('yes', 'Yes') ?></label>
												</div>
												<div class="radio radio-info form-check-inline">
													<input type="radio" id="overtimeEligibleNo" name="is_overtime_eligible" value="0" <?= ((string)($emprow['is_overtime_eligible'] ?? '0') !== '1') ? 'checked' : '' ?>>
													<label for="overtimeEligibleNo" class="atch"><?= __('no', 'No') ?></label>
												</div>
											</div>

											<div class="form-group col-md-4">
												<label class="col-form-label d-block"><?= __('allow_emergency_vacation', 'Allow Emergency Vacation') ?></label>
												<div class="radio radio-info form-check-inline">
													<input type="radio" id="allowEmergencyVacationYes" name="allow_emergency_vacation" value="1" <?= ((string)($emprow['allow_emergency_vacation'] ?? '0') === '1') ? 'checked' : '' ?>>
													<label for="allowEmergencyVacationYes" class="atch"><?= __('yes', 'Yes') ?></label>
												</div>
												<div class="radio radio-info form-check-inline">
													<input type="radio" id="allowEmergencyVacationNo" name="allow_emergency_vacation" value="0" <?= ((string)($emprow['allow_emergency_vacation'] ?? '0') !== '1') ? 'checked' : '' ?>>
													<label for="allowEmergencyVacationNo" class="atch"><?= __('no', 'No') ?></label>
												</div>
												<small class="form-text text-muted"><?= __('allow_emergency_vacation_hint', 'When Yes, this employee can apply for Emergency Vacation even with a healthy balance (>1 day)') ?></small>
											</div>

											<?php if ($canManageRequestBlock || $canManageRequestTypeBlock): ?>
											<div class="form-group col-md-12">
												<hr>
												<label class="col-form-label d-block"><strong><?= __('request_restrictions', 'Request Restrictions') ?></strong></label>
											</div>

											<?php if ($canManageRequestBlock): ?>
											<div class="form-group col-md-4">
												<label class="col-form-label d-block"><?= __('block_from_all_requests', 'Block From All Requests') ?></label>
												<div class="radio radio-danger form-check-inline">
													<input type="radio" id="requestsBlockedYes" name="requests_blocked" value="1" <?= ((string)($emprow['requests_blocked'] ?? '0') === '1') ? 'checked' : '' ?>>
													<label for="requestsBlockedYes" class="atch"><?= __('yes', 'Yes') ?></label>
												</div>
												<div class="radio radio-info form-check-inline">
													<input type="radio" id="requestsBlockedNo" name="requests_blocked" value="0" <?= ((string)($emprow['requests_blocked'] ?? '0') !== '1') ? 'checked' : '' ?>>
													<label for="requestsBlockedNo" class="atch"><?= __('no', 'No') ?></label>
												</div>
											</div>
											<?php endif; ?>

											<?php if ($canManageRequestTypeBlock): ?>
											<div class="form-group col-md-8">
												<label class="col-form-label d-block"><?= __('block_specific_request_types', 'Block Specific Request Types') ?></label>
												<small class="form-text text-muted mb-2"><?= __('block_specific_request_types_hint', 'Some request types may already be blocked for all employees (see Manage Request Type Blocks). Checking one of those here exempts this employee; checking a type that is not globally blocked blocks it for this employee only.') ?></small>
												<div id="blockedRequestTypesContainer">
													<?php
													$blockedTypesRaw = (string)($emprow['blocked_request_types'] ?? '');
													$blockedTypesSelected = decode_blocked_request_types($blockedTypesRaw);
													foreach (get_blockable_request_type_labels() as $typeKey => $typeLabel):
														$isGloballyBlocked = in_array($typeKey, $globallyBlockedRequestTypes, true);
														$checkboxLabel = $isGloballyBlocked
															? '🔒 ' . $typeLabel . ' — ' . __('globally_blocked_check_to_allow', 'globally blocked; check to allow this employee anyway')
															: $typeLabel;
													?>
													<div class="checkbox checkbox-danger form-check-inline">
														<input type="checkbox" id="blockType_<?= htmlspecialchars($typeKey) ?>" class="blocked-request-type-checkbox" value="<?= htmlspecialchars($typeKey) ?>" <?= in_array($typeKey, $blockedTypesSelected, true) ? 'checked' : '' ?>>
														<label for="blockType_<?= htmlspecialchars($typeKey) ?>" class="atch"><?= htmlspecialchars($checkboxLabel) ?></label>
													</div>
													<?php endforeach; ?>
												</div>
												<input type="hidden" name="blocked_request_types" id="blockedRequestTypesHidden" value="<?= htmlspecialchars(json_encode($blockedTypesSelected)) ?>">
											</div>
											<script>
												document.addEventListener('DOMContentLoaded', function() {
													var hidden = document.getElementById('blockedRequestTypesHidden');
													var boxes = document.querySelectorAll('.blocked-request-type-checkbox');
													function syncHidden() {
														var values = Array.prototype.filter.call(boxes, function(b) { return b.checked; })
															.map(function(b) { return b.value; });
														hidden.value = JSON.stringify(values);
													}
													boxes.forEach(function(b) { b.addEventListener('change', syncHidden); });
												});
											</script>
											<?php endif; ?>
											<?php endif; ?>

											<div class="form-group col-md-12">
												<div class="btn-group" role="group" aria-label="Edit Button">
													<a href="view_employee.php?emp_id=<?= $_GET['emp_id']; ?>" class="btn btn-dark"><i class="fa fa-angle-double-left"></i> <?= __("back_button") ?></a>
													<button type="submit" name="submit" class="btn btn-primary"><i class="mdi mdi-account-plus"></i> <?= __("save_edit_button") ?></button>
												</div>
											</div>

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

		<div class="modal fade terminat" tabindex="-1" role="dialog" aria-labelledby="mySmallModalLabel" aria-hidden="true" style="display: none;">
			<div class="modal-dialog modal-dialog-centered">
				<div class="modal-content">
					<div class="modal-header" style="background-color: brown !important; color: #fff !important;">
						<button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
						<h4 class="modal-title" id="mySmallModalLabel">
							<i class="mdi mdi-delete-circle"></i>
							<?= __("are_you_sure") ?>
						</h4>
					</div>
					<div class="modal-body">
						<h3><?= __("you_need_to_terminate_text") ?></h3>
						<h4><strong style="font-size: 30px; "><?= $emprow['name'] ?></strong></h4>
						<div class="form-row" id="content" style="display:none;">
							<form action="./includes/terminat_emp.php" method="get">
								<!--	<a href="" class="btn btn-danger waves-effect waves-light" ><i class="mdi mdi-account-off"></i> Terminat</a>-->
								<input type="hidden" name="emp_id" value="<?= $emprow['empid'] ?>">
								<input type="hidden" name="note" value="terminat">
								<div class="input-group">
									<input type="text" id="ter_note" name="ter_note" class="form-control" aria-describedby="basic-addon2">
									<div class="input-group-append">
										<button type="submit" class="btn btn-danger waves-effect waves-light"><i class="mdi mdi-account-off"></i> <?= __("terminate_submit_button") ?></button>
									</div>
								</div>
							</form>
						</div>
					</div>
					<div class="modal-footer">
						<button type="button" class="btn btn-dark waves-effect" data-dismiss="modal"><?= __("close") ?></button>
						<?php /*?><a href="./includes/terminat_emp.php?id=<?= $id_get ?>&note=expired" class="btn btn-light waves-effect waves-light"><i class="mdi mdi-account-star"></i> Expired</a><?php */ ?>
						<button type="button" id="terminat_emp" class="btn btn-danger waves-effect waves-light"><i class="mdi mdi-account-off"></i> <?= __("terminate_button") ?></button>
					</div>
				</div><!-- /.modal-content -->
			</div><!-- /.modal-dialog -->
		</div><!-- /.modal -->


		<!-- jQuery  -->
		<script src="assets/js/jquery.min.js"></script>
		<script src="assets/js/bootstrap.bundle.min.js"></script>
		<script src="assets/js/metisMenu.min.js"></script>
		<script src="assets/js/waves.js"></script>
		<script src="assets/js/jquery.slimscroll.js"></script>

		<!-- Modal-Effect -->
		<script type="text/javascript" src="./plugins/parsleyjs/parsley.min.js"></script>
		<script src="./plugins/bootstrap-inputmask/jquery.inputmask.min.js" type="text/javascript"></script>
		<script src="./plugins/autoNumeric/autoNumeric.js" type="text/javascript"></script>

		<script src="./plugins/moment/moment.js"></script>
		<script src="./plugins/bootstrap-timepicker/bootstrap-timepicker.js"></script>
		<script src="./plugins/bootstrap-timepicker/hijri/bootstrap-hijri-datetimepicker.js"></script>
		<script src="./plugins/bootstrap-timepicker/hijri/bootstrap-hijri-datetimepicker.min.js"></script>
		<script src="./plugins/bootstrap-timepicker/hijri/bootstrap-hijri-datetimepickermin.js"></script>
		<script src="./plugins/bootstrap-datepicker/js/bootstrap-datepicker.min.js"></script>
		<script src="./plugins/select2/js/select2.min.js" type="text/javascript"></script>
		<script src="./plugins/croppie/croppie.js" type="text/javascript"></script>
		<script src="./plugins/croppie/croppie.min.js" type="text/javascript"></script>
		<script src="./plugins/croppie/exif.js" type="text/javascript"></script>
		<script src="./plugins/bootstrap-filestyle/js/bootstrap-filestyle.min.js" type="text/javascript"></script>

		<!-- App js -->
		<script src="assets/pages/jquery.form-pickers.init.js"></script>
		<script src="assets/pages/jquery.form-hijri-pickers.init.js"></script>

		<script src="assets/js/jquery.core.js"></script>
		<script src="assets/js/jquery.app.js?t=<?= time() ?>"></script>


		<!-- <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-validate/1.19.0/jquery.validate.js"></script> -->

		<script type="text/javascript">
			$uploadCrop = $('#upload_emp_img').croppie({
				url: "<?= substr($emprow['avatar'], 2); ?>",
				enableExif: true,
				viewport: {
					width: 200,
					height: 200,
					type: 'circle',
				},
				boundary: {
					width: 300,
					height: 300,
				}
			});
			//$uploadCrop = $('#upload_emp_img').croppie({
			//	url: "<?php //echo substr($emprow['avatar'], 2);
						?>", // although you can use the base64 format of an image in the place of the URL
			//    enableExif: true,
			//    viewport: {
			//        width: 200,
			//        height: 200,
			//        type: 'circle',
			////        type: 'square'
			//    },
			//    boundary: {
			//        width: 300,
			//        height: 300
			//    }
			//});
			$('#upload').on('change', function() {
				var reader = new FileReader();
				reader.onload = function(e) {
					$uploadCrop.croppie('bind', {
						url: e.target.result
					}).then(function() {
						console.log('jQuery bind complete');
					});

				}
				reader.readAsDataURL(this.files[0]);
			});

			$('.upload_result').on('click', function(ev) {
				$uploadCrop.croppie('result', {
					type: 'canvas',
					size: 'viewport'
				}).then(function(resp) {
					$.ajax({
						url: "./includes/ajaxpro.php",
						type: "POST",
						data: {
							"image": resp,
							"id": "<?= $emprow['id'] ?>",
							"emp_id": "<?= $emprow['empid']; ?>",
							"emp_name": "<?= $emprow['name']; ?>"
						},
						success: function(data) {
							if (data == 'Image Uploaded Successfully') {
								html = '<img src="' + resp + '" />';
								$("#upload_emp_img_i").html(html);
								location.reload(); //refresh page after uploading
							} else {
								$("body").append("<div class='upload-error'>" + data + "</div>");

							}
						}
					});
				});
			});
		</script>


		<script type="text/javascript">
			<?php
				$all_locations_json = [];
				$query_all_locations = mysqli_query($conDB, "SELECT `id`, `city_id`, `name_en`, `name_ar` FROM `locations` ORDER BY `name_en` ASC");
				while ($rec = mysqli_fetch_assoc($query_all_locations)) { $all_locations_json[] = $rec; }

				$all_sub_depts_json = [];
				$query_all_sub_depts = mysqli_query($conDB, "SELECT `id`, `department_id`, `name_en`, `name_ar` FROM `sub_departments` ORDER BY `name_en` ASC");
				while ($rec = mysqli_fetch_assoc($query_all_sub_depts)) { $all_sub_depts_json[] = $rec; }
			?>
			const ALL_LOCATIONS = <?= json_encode($all_locations_json) ?>;
			const ALL_SUB_DEPARTMENTS = <?= json_encode($all_sub_depts_json) ?>;
			const EMP_LOCATION_ID = "<?= $emprow['location_id'] ?? '' ?>";
			const EMP_SUB_DEPT_ID = "<?= $emprow['sub_dept_id'] ?? '' ?>";
			const IS_RTL_LOCALE = <?= ($is_rtl ?? false) ? 'true' : 'false' ?>;

			function populateLocationOptions(cityId, selectedLocationId) {
				const $select = $('#location_id');
				const isSelect2 = $select.hasClass('select2-hidden-accessible');
				if (!cityId) {
					$select.empty().append('<option value=""><?= __("select_a_city_first", "Select a City First") ?></option>');
					if (isSelect2) $select.trigger('change');
					return;
				}
				let options = '<option value=""><?= __("select_option") ?></option>';
				ALL_LOCATIONS.filter(loc => String(loc.city_id) === String(cityId)).forEach(loc => {
					const selected = (String(loc.id) === String(selectedLocationId)) ? 'selected' : '';
					const label = IS_RTL_LOCALE ? loc.name_ar : loc.name_en;
					options += `<option value="${loc.id}" ${selected}>${label}</option>`;
				});
				$select.html(options);
				if (isSelect2) $select.trigger('change');
			}

			function populateSubDeptOptions(departmentId, selectedSubDeptId) {
				const $select = $('#sub_dept_id');
				const isSelect2 = $select.hasClass('select2-hidden-accessible');
				if (!departmentId) {
					$select.empty().append('<option value=""><?= __("select_a_department_first", "Select a Department First") ?></option>');
					if (isSelect2) $select.trigger('change');
					return;
				}
				let options = '<option value=""><?= __("select_option") ?></option>';
				ALL_SUB_DEPARTMENTS.filter(sd => String(sd.department_id) === String(departmentId)).forEach(sd => {
					const selected = (String(sd.id) === String(selectedSubDeptId)) ? 'selected' : '';
					const label = IS_RTL_LOCALE ? sd.name_ar : sd.name_en;
					options += `<option value="${sd.id}" ${selected}>${label}</option>`;
				});
				$select.html(options);
				if (isSelect2) $select.trigger('change');
			}

			$(function() {

				// Initial population based on the employee's currently saved City/Department
				populateLocationOptions($('#city_id').val(), EMP_LOCATION_ID);
				populateSubDeptOptions($('#department').val(), EMP_SUB_DEPT_ID);

				$('#city_id').on('change', function() {
					populateLocationOptions($(this).val(), '');
				});
				$('#department').on('change', function() {
					populateSubDeptOptions($(this).val(), '');
				});

				/***************************/

				jQuery('#terminat_emp').on('click', function(event) {
					$("#ter_note").attr('required', '');
					jQuery('#content').toggle('show');
				});


				$("#vac_period").bind("change", function() {
					$.ajax({
						type: "GET",
						url: "./includes/ContractPeriodSelect.php",
						data: "vac_period=" + $("#vac_period").val(),
						success: function(val) {
							console.log(val);
							$("#vacation_days").val(val);
						}
					});
				});


				/*$("#registration").on("change", function() {
					if ($(this).data("is-updating")) return; // Prevent recursion
					$(this).data("is-updating", true);
					$.ajax({
						url: './includes/DepartmentSelect.php',
						dataType: 'JSON',
						type: 'POST',
						data: { department: $("#department").val() },
						success: function(res) {
							if (res.status == 200) {
								const $section = $('#sectin_nme').empty();
								$.each(res.data, function(i, item) {
									$section.append(`<option value="${item.id}">${item.section_name}</option>`);
								});
							}
						},
						error: function(j, e) {
							errorHandling(j, e);
						},
						complete: function() {
							$("#registration").data("is-updating", false);
						}
					});
				});*/
				<?php /* ?>
    var dbDeptId = "<?= isset($emprow['dept']) ? $emprow['dept'] : ''; ?>";
    var dbSectionId = "<?= isset($emprow['sectin_id']) ? $emprow['sectin_id'] : ''; ?>";
    const loadSections = (deptId, selectId = '') => {
        if (!deptId) {
            $('#sectin_nme').html('<option value="">Select Department First</option>').prop('disabled', true);
            return;
        }
        $('#sectin_nme').html('<option value="">Loading...</option>').prop('disabled', false);
        $.ajax({
            url: './includes/DepartmentSelect.php',
            type: 'POST',
            data: { department: deptId },
            dataType: 'json',
            success: (res) => {
                let options = '<option value="">Select Section</option>';
                res.data.forEach(item => {
                    const selected = (item.id == selectId) ? 'selected' : '';
                    options += `<option value="${item.id}" ${selected}>${item.section_name}</option>`;
                });
                $('#sectin_nme').html(options);
            },
            error: (xhr, status, error) => {
                $('#sectin_nme').html('<option value="">Error loading sections</option>');
                console.error('AJAX Error:', status, error);
            }
        });
    };
    $('#department').change(function() {
        loadSections($(this).val());
    });
    if (dbDeptId) {
        loadSections(dbDeptId, dbSectionId);
    }
	<?php */ ?>

				/***********Date of ID**********/
				const initDateOfIDPickers = () => {
					$('#iqama_exp').datepicker({
						format: 'yyyy-mm-dd',
						autoclose: true,
						todayHighlight: true
					}).on('changeDate', function(e) {
						const hijriDate = moment(e.date).format('iYYYY-iMM-iDD');
						$('#dateofidHijri').val(hijriDate).hijriDatePicker('setDate', hijriDate);
					});
					$('#dateofidHijri').hijriDatePicker({
						format: 'iYYYY-iMM-iDD',
						hijri: true,
						showSwitcher: false
					}).on('dp.change', function(e) {
						if (e.date) {
							const gregorianDate = moment(e.date.format('iYYYY-iMM-iDD'), 'iYYYY-iMM-iDD').format('YYYY-MM-DD');
							$('#iqama_exp').val(gregorianDate).datepicker('update', gregorianDate);
						}
					});
				};
				initDateOfIDPickers();
				/***********Date of ID**********/
				/***********Date of Birth**********/
				const initDateOfBirthPickers = () => {
					$('#dob').datepicker({
						format: 'yyyy-mm-dd',
						autoclose: true,
						todayHighlight: true
					}).on('changeDate', function(e) {
						const hijriDate = moment(e.date).format('iYYYY-iMM-iDD');
						$('#dateofbirthHijri').val(hijriDate).hijriDatePicker('setDate', hijriDate);
					});
					$('#dateofbirthHijri').hijriDatePicker({
						format: 'iYYYY-iMM-iDD',
						hijri: true,
						showSwitcher: false,
					}).on('dp.change', function(e) {
						if (e.date) {
							const gregorianDate = moment(e.date.format('iYYYY-iMM-iDD'), 'iYYYY-iMM-iDD').format('YYYY-MM-DD');
							$('#dob').val(gregorianDate).datepicker('update', gregorianDate);
						}
					});
				};
				/***********Date of Birth**********/
				initDateOfBirthPickers();


			});
		</script>


		<script type="text/javascript">
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
		</script>

	</body>

	</html>
<?php } ?>
