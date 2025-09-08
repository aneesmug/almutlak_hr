<?php
	require_once __DIR__ . '/includes/db.php';

	require_once __DIR__ . '/includes/session_check.php';

	include("./includes/Hijri_GregorianConvert.php");
	$DateConv=new Hijri_GregorianConvert;
	$format="DD/MM/YYYY";

	$query = mysqli_query($conDB, "SELECT * FROM `admin_login` WHERE `id_iqama`='".$username."'");
		if(mysqli_num_rows($query) == 1){
		include("./includes/avatar_select.php");
	}

	$stmt = $pdo->query("SELECT `emp_id` FROM `employees` ORDER BY `emp_id` DESC LIMIT 1");
	$empId = $stmt->fetch(PDO::FETCH_ASSOC)['emp_id'];
	$newEmpId = $empId + 1;
	// debug($newEmpId);
if(isset($_POST['submit'])){
	// Whitelist of allowed database columns
	$allowedColumns = [
		'name', 'emp_id', 'iqama', 'iqama_exp', 'passport_number',
		'passport_exp', 'mobile', 'emg_mobile', 'emg_name', 'country', 'dept',
		'sectin_nme', 'emptype', 'joining_date', 'dob', 'dob_h', 't_shirt_size',
		'sex', 'mar_status', 'blood_type', 'emp_sup_type', 'actual_Job', 'vac_period',
		'vacation_days', 'salary', 'bank_name', 'iban', 'email', 'address',
		'insurance_no', 'insurance_class', 'insurance_exp', 'iqama_exp_g', 'gosi',
		'probation', 'payment_type', 'created_at','fly','comp_no','avatar'
	];
	// Field-specific cleaning rules
	$cleanRules = [
		'emp_id' => fn($v) => str_replace(',', '', $v),
		'salary' => fn($v) => str_replace(',', '', $v),
		'iban' => fn($v) => str_replace(' ', '', $v),
		'mobile' => fn($v) => preg_replace('/[^0-9]/', '', explode(' ', $v)[0]),
		// 'emg_mobile' => fn($v) => preg_replace('/[^0-9]/', '', explode('-', $v)[0]),
		'email' => fn($v) => filter_var(trim($v), FILTER_SANITIZE_EMAIL),
		'created_at' => fn($v) => date('Y-m-d H:i:s')
	];
	// Process and validate data
	$data = $_POST;
	// debug($data);
	unset($data['submit']); // Remove submit button value
	// Add auto-generated fields
	$data['created_at'] = date('Y-m-d H:i:s');
	$data['fly'] = 'no';
	$data['dept'] = $data['department'] ?? null;
	unset($data['department']);
	$data['avatar'] = ($data['sex'] == 1)?"./assets/emp_pics/defult.png":"./assets/emp_pics/defultFemale.jpg";
	try {
		// Prepare data for insertion
		$columns = [];
		$placeholders = [];
		$values = [];
		foreach ($data as $column => $value) {
			if (!in_array($column, $allowedColumns)) continue;
			// Apply field-specific cleaning
			$cleanValue = isset($cleanRules[$column]) 
				? $cleanRules[$column]($value) 
				: trim($value);
			
			$columns[] = "`$column`";
			$placeholders[] = ":$column";
			$values[":$column"] = $cleanValue;
		}
		// Validate required fields
		$requiredFields = ['name', 'emp_id', 'iqama', 'mobile', 'salary'];
		foreach ($requiredFields as $field) {
			if (empty($values[":$field"])) {
				throw new Exception("$field is required");
			}
		}
		// Numeric validation
		if (!is_numeric($values[':emp_id'])) {
			throw new Exception(__('employee_id_must_be_numeric'));
		}
		if (!is_numeric($values[':salary'])) {
			throw new Exception(__('salary_must_be_numeric'));
		}
		// Build and execute query
		$sql = "INSERT INTO `employees` (" . implode(', ', $columns) . ") VALUES (" . implode(', ', $placeholders) . ")";
		$stmt = $pdo->prepare($sql);
		$stmt->execute($values);
		// Success response
		salert(__('success_title'), __('employee_created_successfully'), "success" ,"view_employee.php?emp_id=".$newEmpId, __('ok'));
	} catch (PDOException $e) {

		salert(__('error_title'), __('database_error').': ' . $e->getMessage(), "error" ,"new_comp_employee.php", __('ok') );
	} catch (Exception $e) {
		salert(__('error_title'), $e->getMessage(), "error" ,"new_comp_employee.php" , __('ok'));
	}

	
}

?>

<!doctype html>
<html lang="<?= $current_lang ?? 'en' ?>" <?= ($is_rtl ?? false) ? 'dir="rtl"' : '' ?>>

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
		
		<link href="./plugins/bootstrap-timepicker/hijri_css/bootstrap-datetimepicker.css" rel="stylesheet">
        <link href="./plugins/bootstrap-timepicker/hijri_css/bootstrap-datetimepicker.min.css" rel="stylesheet">

		<link rel="stylesheet" href="./plugins/bootstrap-select/css/bootstrap-select.min.css">
		<link rel="stylesheet" href="./plugins/select2/css/select2.min.css">

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
    <body class="enlarged" data-keep-enlarged="true" data-page="new-employee">

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
								<div class="col-sm-12 col-xl-12">
									<div class="card-box widget-flat border-custom bg-custom text-white">
										<i class="fa fa-house-chimney-user"></i>
										<br><h3 class="m-b-10"><?=__('new_almutlak_co_employee') ?></h3><br>
									</div>
                            	</div>
                            <div class="col-md-12">
                                <div class="card-box">
                                    <h4 class="m-t-0 header-title"><?=__('register_new_employee') ?></h4>
                                    <form action="<?= $_SERVER['PHP_SELF']; ?>" method="post" enctype="multipart/form-data" id="registration" class="registration">
										<div class="form-row">
											<div class="form-group col-md-2">
												<label for="name" class="col-form-label"><?=__('employee_name') ?><span class="text-danger">*</span></label>
												<input type="text" name="name" parsley-trigger="change" class="form-control" id="name" autofocus required />
												</div>
                                            <div class="form-group col-md-1">
												<label for="emp_id" class="col-form-label"><?=__('employee_id') ?>.<span class="text-danger">*</span></label>
												<input type="text" name="emp_id" parsley-trigger="change" class="form-control autonumber" id="emp_id" value="<?=$newEmpId?>" data-v-max="9999" data-v-min="0" required />
                                        	</div>
                                        	<div class="form-group col-md-1">
												<label for="iqama" class="col-form-label"><?=__('iqama_id') ?><span class="text-danger">*</span></label>
												<input type="text" name="iqama" parsley-trigger="change" data-mask="9999999999" class="form-control" id="iqama" required />
                                        	</div>
											<div class="form-group col-2">
												<label for="iqama_exp_g" class="col-form-label"><?=__('iqama_id_expiry') ?><span class="text-danger"><?=__('in_gregorian') ?> *</span></label>
												<input class="form-control" id="iqama_exp_g" name="iqama_exp_g" type="text" required />
											</div>
											<div class="form-group col-2">
												<label for="iqama_exp" class="col-form-label"><?=__('iqama_id_expiry') ?><span class="text-danger"> <?=__('in_hijri') ?> *</span></label>
												<input class="form-control" id="iqama_exp" name="iqama_exp" type="text" required />
											</div>

											<div class="form-group col-md-2">
												<label for="passport_number" class="col-form-label"><?=__('passport_no') ?></label>
												<input type="text" name="passport_number" parsley-trigger="change" class="form-control" id="passport_number" >
                                        	</div>
											<div class="form-group col-md-2">
												<label for="passport_exp" class="col-form-label"><?=__('passport_expiry') ?></label>
												<input type="text" name="passport_exp" parsley-trigger="change" class="form-control" id="passport_exp">
                                        	</div>
											<div class="form-group col-md-2">
                                                <label for="mobile" class="col-form-label"><?=__('mobile') ?>.<span class="text-danger">*</span></label>
                                                <input type="text" name="mobile" parsley-trigger="change" data-mask="0599999999" class="form-control" id="mobile" required />
                                            </div>
											<div class="form-group col-md-2">
                                                <label for="emg_mobile" class="col-form-label"><?=__('emergency_mobile_no_label') ?>.<span class="text-danger">*</span></label>
                                                <input type="text" name="emg_mobile" parsley-trigger="change" class="form-control" required />
                                            </div>
											<div class="form-group col-md-2">
                                                <label for="emg_name" class="col-form-label"><?=__('emergency_contact_name_label') ?><span class="text-danger">*</span></label>
                                                <input type="text" name="emg_name" parsley-trigger="change" class="form-control" required />
                                            </div>
                                        	<div class="form-group col-md-2">
											<label for="country" class="col-form-label"><?=__('nationality') ?><span class="text-danger">*</span></label>
											<select class="form-control" name="country" id="country" required >
												<option value=""><?=__('select_option')?></option>
											<?php
												$query_country = mysqli_query($conDB, "SELECT * FROM `countries` ORDER BY `name` REGEXP '^[^A-Za-z]' ASC, name");
												while($rec_con = mysqli_fetch_assoc($query_country)){
											?>
												<option value="<?= $rec_con["id"]; ?>"><?= ($is_rtl ?? false ? $rec_con["name_ar"] : $rec_con["name"]); ?></option>
											<?php } ?>
											</select>
											</div>
                                            <div class="form-group col-md-2">
											<label for="department" class="col-form-label"><?=__('department') ?><span class="text-danger">*</span></label>
											<select class="form-control" name="department" id="department" required >
												<option value=""><?=__('select_option')?></option>
											<?php
												$query_dep_nme = mysqli_query($conDB, "SELECT * FROM `department` ORDER BY `dep_nme` REGEXP '^[^A-Za-z]' ASC, dep_nme");
												while($rec = mysqli_fetch_assoc($query_dep_nme)){
											?>
												<option value="<?= $rec["id"] ?>"><?=($is_rtl ?? false ? $rec["dep_nme_ar"] : $rec["dep_nme"]) ?></option>
											<?php } ?>
											</select>
											</div>
											<div class="form-group col-md-2">
                                                <label for="sectin_nme" class="col-form-label"><?=__('section_label') ?><span class="text-danger">*</span></label>
												<select class="form-control sectin_nme" name="sectin_nme" id="sectin_nme" required >
													<option value=""><?=__('select_option')?></option>
													<?php
														$query_dep_nme = mysqli_query($conDB, "SELECT * FROM `section` ORDER BY `section_name` REGEXP '^[^A-Za-z]' ASC, section_name");
														while($rec = mysqli_fetch_assoc($query_dep_nme)){ 
													?>
														<option value="<?=$rec["id"]?>"><?= $rec["section_name"]?></option>
													<?php } ?>
												</select>
                                            </div>
											<div class="form-group col-md-1">
                                                <label for="emptype" class="col-form-label"><?=__('employee_type_label') ?><span class="text-danger">*</span></label>
												<select class="form-control" name="emptype" required >
													<option value=""><?=__('select_option')?></option>
													<option value="Manager"><?=__('manager') ?></option>
													<option value="Supervisor"><?=__('supervisor_option') ?></option>
													<option value="Supporter"><?=__('supporter_option') ?></option>
												</select>
                                            </div>
											<div class="form-group col-md-2">
                                                <label for="joining_date" class="col-form-label"><?=__('joining_date') ?><span class="text-danger">*</span></label>
                                                <input type="text" name="joining_date" parsley-trigger="change" class="form-control" id="joining_date"  required />
                                            </div>
											<div class="form-group col-md-2">
                                                <label for="dob" class="col-form-label"><?=__('date_of_birth') ?><span class="text-danger"> <?=__('in_gregorian') ?> *</span></label>
                                                <input type="text" name="dob" parsley-trigger="change" class="form-control" id="dob"  required />
                                            </div>
											<div class="form-group col-md-2">
                                                <label for="dateofbirthHijri" class="col-form-label"><?=__('date_of_birth') ?><span class="text-danger"> <?=__('in_hijri') ?> *</span></label>
                                                <input type="text" name="dob_h" parsley-trigger="change" class="form-control" id="dateofbirthHijri"  required />
                                            </div>
											<div class="form-group col-md-1">
                                                <label for="t_shirt_size" class="col-form-label"><?=__('t_shirt_size') ?></label>
                                                <input type="text" name="t_shirt_size" parsley-trigger="change" class="form-control" id="t_shirt_size">
                                            </div>
											<div class="form-group col-md-2">
                                                <label class="font-14 mt-3 mb-2"><?=__('gender') ?><span class="text-danger">*</span></label><br>
												<div class="radio radio-info form-check-inline">
                                                    <input type="radio" id="inlineRadio3" value="1" checked name="sex">
                                                    <label for="inlineRadio3" class="atch"><i class="mdi mdi-human-male"></i> <?=__('male') ?></label>
                                                </div>
                                                <div class="radio radio-info form-check-inline">
                                                    <input type="radio" id="inlineRadio1" value="2" name="sex">
                                                    <label for="inlineRadio1" class="atch"><i class="mdi mdi-human-female"></i> <?=__('female') ?> </label>
                                                </div>
                                            </div>
											<div class="form-group col-md-2">
                                                <label class="font-14 mt-3 mb-2 radioalign"><?=__('marital_status') ?></label>
												<div class="radio radio-info form-check-inline">
                                                    <input type="radio" id="married" value="married" name="mar_status">
                                                    <label for="married" class="atch"><i class="mdi mdi-ring"></i> <?=__('married') ?></label>
                                                </div>
                                                <div class="radio radio-info form-check-inline">
                                                    <input type="radio" id="single" value="single" checked name="mar_status">
                                                    <label for="single" class="atch"><i class="mdi mdi-account-convert"></i> <?=__('single') ?></label>
                                                </div>
                                            </div>
											<div class="form-group col-md-2">
                                                <label for="blood_type" class="col-form-label"><?=__('blood_group') ?></label>
												<select class="form-control" name="blood_type">
													<option value=""><?=__('select_option')?></option>
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
											<!--  -->
											<div class="form-group col-md-2">
												<label for="emp_sup_type" class="col-form-label"><?=__('sponsorship') ?><span class="text-danger">*</span></label>
												<select class="form-control" name="emp_sup_type" id="emp_sup_type" required >
													<option value=""><?=__('select_option')?></option>
													<?php
													$query_cp = mysqli_query($conDB, "SELECT * FROM `sponsorship` ORDER BY `sponsor` REGEXP '^[^A-Za-z]' ASC, `sponsor`");
														while($rec_con = mysqli_fetch_assoc($query_cp)){
													?>
													<option value="<?=$rec_con["id"];?>"><?=$rec_con["sponsor"];?></option>
													<?php } ?>
												</select>
                                            </div>
											<div class="form-group col-md-2">
												<label for="comp_no" class="col-form-label"><?=__('company') ?><span class="text-danger">*</span></label>
												<select class="form-control" name="comp_no" id="comp_no" required />
													<option value=""><?=__('select_option')?></option>
													<?php
													$query_cp = mysqli_query($conDB, "SELECT * FROM `companies` ORDER BY `comp_name` REGEXP '^[^A-Za-z]' ASC, `comp_name`");
														while($rec_con = mysqli_fetch_assoc($query_cp)){
													?>
													<option value="<?=$rec_con["comp_id"];?>"><?=$rec_con["comp_name"];?></option>
													<?php } ?>
												</select>
                                            </div>
											<div class="form-group col-md-2">
												<label for="actual_Job" class="col-form-label"><?=__('actual_job') ?><span class="text-danger">*</span></label>
												<select class="form-control" name="actual_Job" id="actual_Job" required >
													<option value=""><?=__('select_option')?></option>
													<?php
													$query_cp = mysqli_query($conDB, "SELECT * FROM `ac_jobs` ORDER BY `job` REGEXP '^[^A-Za-z]' ASC, `job`");
														while($rec_con = mysqli_fetch_assoc($query_cp)){
													?>
													<option value="<?=$rec_con["id"];?>"><?=($is_rtl ?? false ? $rec_con["job_ar"] : $rec_con["job"]);?></option>
													<?php } ?>
												</select>
                                            </div>
											<!--  -->
											<div class="form-group col-md-2">
												<label for="vac_period" class="col-form-label"><?=__('contract_period') ?><span class="text-danger">*</span></label>
												<select class="form-control" name="vac_period" id="vac_period" required >
													<option value=""><?=__('select_option')?></option>
													<?php
													$query_cp = mysqli_query($conDB, "SELECT * FROM `contract_period` ORDER BY `period` REGEXP '^[^A-Za-z]' ASC, period");
														while($rec_con = mysqli_fetch_assoc($query_cp)){
													?>
													<option value="<?=$rec_con["id"];?>"><?=$rec_con["period"];?></option>
													<?php } ?>
												</select>
                                            </div>
											<div class="form-group col-md-2">
                                                <label for="vacation_days" class="col-form-label"><?=__('vacation_days') ?><span class="text-danger">*</span></label>
                                                <input type="text" name="vacation_days" class="form-control" id="vacation_days" readonly required />
                                            </div>
											<div class="form-group col-md-2">
                                                <label for="salary" class="col-form-label"><?=__('salary') ?><span class="text-danger">*</span></label>
                                                <input type="text" name="salary" class="form-control autonumber" data-v-max="2500000" data-v-min="0" id="salary" required />
                                            </div>
                                            <div class="form-group col-md-2">
                                               	 <label for="bank_name" class="col-form-label"><?=__('bank_name') ?><span class="text-danger">*</span></label>
													<select class="form-control" name="bank_name" id="bank_name" required >
													<?php if(empty($bank_name_get)){ ?>
														<option value=""><?=__('select_option')?></option>
													<?php } else { ?>
														<option value="<?= $bank_name_get ?>"><?= $bank_name_get ?></option>
													<?php } ?>
													<?php
														$query_bank = mysqli_query($conDB, "SELECT * FROM `bank_list` ORDER BY `name` REGEXP '^[^A-Za-z]' ASC, name");
														while($rec_con = mysqli_fetch_assoc($query_bank)){
															$banknme = $rec_con["name"];
													?>
														<option value="<?= $rec_con["bnk_id"] ?>" ><?= ($is_rtl ?? false ? $rec_con["bank_name_ar"]:$rec_con["name"]) ?></option>
													<?php } ?>
													</select>
                                            </div>
                                            <div class="form-group col-md-2">
                                                <label for="iban" class="col-form-label"><?=__('iban') ?><span class="text-danger">*</span></label>
                                                <!-- <input type="text" name="iban" class="form-control"  id="iban" data-mask="SA99 9999 9999 9999 9999 9999"> -->
                                                <input type="text" name="iban" class="form-control" required />
                                            </div>

											<div class="form-group col-md-2">
                                                <label for="email" class="col-form-label"><?=__('email') ?></label>
                                                <input type="email" name="email" parsley-trigger="change" class="form-control" id="email">
                                            </div>
											<div class="form-group col-md-4">
                                                <label for="address" class="col-form-label"><?=__('address') ?><span class="text-danger">*</span></label>
                                                <input type="text" name="address" parsley-trigger="change" class="form-control" id="address" required />
                                            </div>
											<div class="form-group col-md-2">
                                                <label for="insurance_no" class="col-form-label"><?=__('insurance_no_label') ?></label>
                                                <input type="text" name="insurance_no" parsley-trigger="change"  class="form-control" id="insurance_no">
                                            </div>
											
											<div class="form-group col-md-2">
                                                <label for="insurance_class" class="col-form-label"><?=__('insurance_class_label') ?></label>
												<select class="form-control" name="insurance_class" >
													<option value=""><?=__('select_option')?></option>
													<option value="A">A</option>
													<option value="B">B</option>
													<option value="C">C</option>
													<option value="CLT">CLT</option>
													<option value="VIP">VIP</option>
												</select>
                                            </div>
											<div class="form-group col-md-2">
                                                <label for="insurance_exp" class="col-form-label"><?=__('insurance_expire_label') ?></label>
                                                <input type="text" name="insurance_exp" parsley-trigger="change" class="form-control" id="insurance_exp">
                                        	</div>

											<div class="form-group col-md-2 noneDIV" id="gosiDiv">
                                                <label for="gosi" class="col-form-label"><?=__('gosi') ?><span class="text-danger">*</span></label>
                                                <div class="input-group">
													<div class="input-group-prepend">
														<div class="input-group-text">%</div>
													</div>
													<input type="text" class="form-control" id="gosi" />
												</div>
                                            </div>

											<div class="form-group col-md-2">
                                                <label for="probation" class="col-form-label"> 
													<?=__('probation_period_label') ?><span class="text-danger">*</span>
												</label>
												<select class="form-control" name="probation" required>
													<option value=""><?=__('select_option')?></option>
													<option value="3 Months">3 <?=__('months') ?></option>
													<option value="6 Months">6 <?=__('months') ?></option>
												</select>
                                            </div>
											
											<div class="form-group col-md-2">
                                                <label for="payment_type" class="col-form-label">
                                                	<?=__('salary_payment_type_label') ?><span class="text-danger">*</span>
												</label>
												<select class="form-control" name="payment_type" required>
													<option value=""><?=__('select_option')?></option>
													<option value="1"><?=__('bank_option')?></option>
													<option value="2"><?=__('cash_option')?></option>
												</select>
                                            </div>

											<div class="form-group col-md-12">
                                                <div class="btn-group" role="group" aria-label="Edit Button">
													<a href="./add_new_employee.php" class="btn btn-dark"><i class="fa fa-angles-left"></i> <?=__('back_button') ?></a>
													<button type="submit" name="submit" class="btn btn-primary"><i class="fa fa-user-plus"></i> <?=__('yes_register') ?></button>
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
		<script src="assets/pages/jquery.form-hijri-pickers.init.js"></script>
		
        <script src="assets/js/jquery.core.js"></script>
        <script src="assets/js/jquery.app.js"></script>
		
		<script defer src="./plugins/imask.js"></script>
		
		<!-- <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-validate/1.19.0/jquery.validate.js"></script> -->
		<!-- <script src="./assets/js/jquery.custom.validation.js"></script> -->
<script type="text/javascript">
$(function() {
 
	$("#vac_period").bind("change", function() {
		$.ajax({
			type: "GET", 
			url: "./includes/ContractPeriodSelect.php",
			data: "vac_period="+$("#vac_period").val(),
			success: function(val) {
				console.log(val);
				$("#vacation_days").val(val);
			}
		});
	});

	$("#country").bind("change", function() {
		const conttData = $("#country").val();
		console.log(conttData);
		if(conttData == 191){
			$("#gosiDiv").removeClass("noneDIV");
			$("input#gosi:text").attr('required','required');
			$("input#gosi:text").attr('name','gosi');	
		} else {
			$("#gosiDiv").addClass("noneDIV");
			$("input#gosi:text").removeAttr('name');
			$("input#gosi:text").removeAttr('required');

		}
	});


	// $("#department").bind("change", function() {
	// 	const deptData = $("#department").val();
	// 	$.ajax({
	// 		url: './includes/DepartmentSelect.php',
	// 		type: 'POST',
	// 		data: {department:deptData},
	// 		dataType: 'json',
	// 		success: (res) => {
	// 			let options = '<option value="">Select Section</option>';
	// 			res.data.forEach(item => {
	// 				options += `<option value="${item.id}" >${item.section_name}</option>`;
	// 			});
	// 			$('#sectin_nme').html(options);
	// 		},
	// 		error: (xhr, status, error) => {
	// 			$('#sectin_nme').html('<option value="">Error loading sections</option>');
	// 			console.error('AJAX Error:', status, error);
	// 		}
	// 	});
	// });

	/***********Date of ID**********/
	const initDateOfIDPickers = () => {
		$('#iqama_exp_g').datepicker({
			format: 'yyyy-mm-dd',
			autoclose: true,
			todayHighlight: true
		}).on('changeDate', function(e) {
			const hijriDate = moment(e.date).format('iYYYY-iMM-iDD');
			$('#iqama_exp').val(hijriDate).hijriDatePicker('setDate', hijriDate);
		});
		$('#iqama_exp').hijriDatePicker({
			format: 'iYYYY-iMM-iDD',
			hijri: true,
			showSwitcher: false
		}).on('dp.change', function(e) {
			if (e.date) {
				const gregorianDate = moment(e.date.format('iYYYY-iMM-iDD'), 'iYYYY-iMM-iDD').format('YYYY-MM-DD');
				$('#iqama_exp_g').val(gregorianDate).datepicker('update', gregorianDate);
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
            $(document).ready(function() {
                $('form').parsley();
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
	

//window.onload = function() {
//    var src = document.getElementById("iqama_exp_hijri"),
//        dst = document.getElementById("iqama_exp_g");
//    src.addEventListener('input', function() {
//        dst.value = src.value;
//    });
//};
			
/*
$(document).ready(function(){
$('#iqama_exp_hijri').blur( function(){
     $('#iqama_exp_g').val($(this).val());
});
});		
*/
			
//$(function () {
//    var $src = $('#iqama_exp_hijri'),
//        $dst = $('#iqama_exp_g');
//    $src.on('input', function () {
//        $dst.val($src.val());
//    });
//});

</script>

    </body>
</html>