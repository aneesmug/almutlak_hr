<?php
	require_once __DIR__ . '/includes/db.php';
	require_once __DIR__ . '/includes/session_check.php';

	// This was included but never used. It can be removed if not needed for other logic.
	// include("./includes/Hijri_GregorianConvert.php");
	// $DateConv=new Hijri_GregorianConvert;
	// $format="DD/MM/YYYY";

	// This block is not relevant to adding a new employee, it's for the logged-in user.
	// $query = mysqli_query($conDB, "SELECT * FROM `admin_login` WHERE `id_iqama`='".$username."');
	// if(mysqli_num_rows($query) == 1){
	// 	include("./includes/avatar_select.php");
	// }

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit'])) {
    // --- Input Validation and Sanitization ---
    $name_emp = trim($_POST['name'] ?? '');
    $emp_id = trim($_POST['emp_id'] ?? '');
    $iqama = trim($_POST['iqama'] ?? '');
    $mobile = trim($_POST['mobile'] ?? '');
    $salary = filter_var($_POST['salary'] ?? 0, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
    $joining_date = trim($_POST['joining_date'] ?? '');
    $department = trim($_POST['department'] ?? '');
    $comp_no = trim($_POST['comp_no'] ?? '');
    $city_id = !empty($_POST['city_id']) ? (int)$_POST['city_id'] : null;
    $location_id = !empty($_POST['location_id']) ? (int)$_POST['location_id'] : null;
    $sub_dept_id = !empty($_POST['sub_dept_id']) ? (int)$_POST['sub_dept_id'] : null;
    $country = trim($_POST['country'] ?? '');
    $dob = trim($_POST['dob'] ?? '');
    $sex = trim($_POST['sex'] ?? 'male');
    $iqama_exp_g = trim($_POST['iqama_exp_g'] ?? '');
    $emp_sup_type = "man_power";
    $date_reg = date("c");
    $image_path = '';

    // --- File Upload Handling ---
    if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['avatar'];
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        $file_type = mime_content_type($file['tmp_name']);
        
        if (in_array($file_type, $allowed_types)) {
            $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
            // Sanitize iqama to be used as a filename component
            $safe_iqama = preg_replace('/[^a-zA-Z0-9_-]/', '', $iqama);
            $image_path = "./assets/emp_pics/" . $safe_iqama . "_" . time() . "." . $extension;
            
            if (!move_uploaded_file($file['tmp_name'], $image_path)) {
                $image_path = ''; // Reset path if move fails
            }
        }
    }

    // Set default avatar if no file was uploaded or if the upload failed
    if (empty($image_path)) {
        $image_path = ($sex === "male") ? "./assets/emp_pics/defult.png" : "./assets/emp_pics/defultFemale.jpg";
    }

    // --- Form Validation ---
    if (empty($name_emp) || empty($emp_id) || empty($iqama) || empty($department) || empty($comp_no) || empty($salary)) {
        $msg = "<div class=\"alert alert-danger bg-danger text-white border-0\" role=\"alert\">Please fill out all required fields in this form!</div>";
    } else {
        // --- Database Operations with Prepared Statements ---
        
        // Check if employee ID already exists
        $stmt_check = $conDB->prepare("SELECT `emp_id` FROM `employees` WHERE `emp_id` = ?");
        $stmt_check->bind_param("s", $emp_id);
        $stmt_check->execute();
        $stmt_check->store_result();

        if ($stmt_check->num_rows > 0) {
            $msg = "<div class=\"alert alert-danger bg-danger text-white border-0\" role=\"alert\">This employee no. (\"$emp_id\") is already registered!</div>";
        } else {
            // Insert new employee record
            $sql = "INSERT INTO `employees` (`name`, `emp_id`, `iqama`, `mobile`, `salary`, `joining_date`, `date_reg`, `status`, `avatar`, `fly`, `dept`, `comp_no`, `city_id`, `location_id`, `sub_dept_id`, `country`, `dob`, `sex`, `emp_sup_type`, `iqama_exp_g`)
                    VALUES (?, ?, ?, ?, ?, ?, ?, 'active', ?, 'no', ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

            $stmt_insert = $conDB->prepare($sql);
            $stmt_insert->bind_param(
                "ssssdsssssiiissss",
                $name_emp,
                $emp_id,
                $iqama,
                $mobile,
                $salary,
                $joining_date,
                $date_reg,
                $image_path,
                $department,
                $comp_no,
                $city_id,
                $location_id,
                $sub_dept_id,
                $country,
                $dob,
                $sex,
                $emp_sup_type,
                $iqama_exp_g
            );

            if ($stmt_insert->execute()) {
                // LOG EMPLOYEE CREATE ACTION (Enhanced Activity Logger)
                ActivityLogger::logCreate(
                    'Employee',
                    'new_mnpow_employee.php',
                    $emp_id,
                    [
                        'name' => $name_emp,
                        'emp_id' => $emp_id,
                        'iqama' => $iqama,
                        'mobile' => $mobile,
                        'salary' => $salary,
                        'joining_date' => $joining_date,
                        'department' => $department,
                        'emp_sup_type' => $emp_sup_type,
                        'country' => $country,
                        'dob' => $dob
                    ],
                    "Created new manpower employee: $name_emp",
                    'employees'
                );

                $msg = "<div class=\"alert alert-success bg-success text-white border-0\" role=\"alert\">Added Successfully!</div>";
            } else {
                $msg = "<div class=\"alert alert-danger bg-danger text-white border-0\" role=\"alert\">Error: Could not add employee.</div>";
            }
            $stmt_insert->close();
        }
        $stmt_check->close();
    }
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
        <link rel="shortcut icon" href="<?=get_setting($conDB, 'favicon')?>">

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


                        <div class="row">
								<div class="col-sm-12 col-xl-12">
									<div class="card-box widget-flat border-purple bg-purple text-white">
										<i class="mdi mdi-account-network"></i>
										<br><h3 class="m-b-10">New Man-Power Employee</h3><br>
									</div>
                            	</div>
                            <div class="col-md-12">
                                <div class="card-box">
                                    <h4 class="m-t-0 header-title">Register New Employee</h4>
                                    <form action="<?=$_SERVER['PHP_SELF']; ?>" method="post" enctype="multipart/form-data" id="registration">
										<?=(isset($msg) ? $msg : '') ?>
                                        <div class="form-row">
											<div class="form-group col-md-3">
												<label for="name">Employee Name<span class="text-danger">*</span></label>
												<input type="text" name="name" parsley-trigger="change" class="form-control" id="name" autofocus>
												</div>
                                            <div class="form-group col-md-2">
												<label for="emp_id">Employee ID.<span class="text-danger">*</span></label>
												<input type="text" name="emp_id" parsley-trigger="change" class="form-control" id="emp_id">
                                        	</div>
                                        	<div class="form-group col-md-2">
												<label for="iqama">Iqama<span class="text-danger">*</span></label>
												<input type="text" name="iqama" parsley-trigger="change" data-mask="9999999999" class="form-control" id="iqama">
                                        	</div>
                                        	<div class="form-group col-md-2">
												<label for="iqama_exp_hijri">Iqama Expire<span class="text-danger"> in Hijri *</span></label>
                                                <input type="text" name="iqama_exp" parsley-trigger="change" class="form-control" id="iqama_exp_hijri">
                                        	</div>
                                        	<div class="form-group col-md-3">
											<label for="country">Nationality</label>
											<select class="form-control" name="country" required />
												<option value="">Select</option>
											<?php
												$query_country = mysqli_query($conDB, "SELECT * FROM `countries` ORDER BY `name` REGEXP '^[^A-Za-z]' ASC, name");
												while($rec_con = mysqli_fetch_assoc($query_country)){
											?>
												<option value="<?= $rec_con["id"]; ?>"><?= $rec_con["name"]; ?></option>
											<?php } ?>
											</select>
											</div>
                                            <div class="form-group col-md-3">
											<label for="department" class="col-form-label">Department<span class="text-danger">*</span></label>
											<select class="form-control" name="department" id="department" required />
												<option value="">Select</option>
											<?php
												$query_dep_nme = mysqli_query($conDB, "SELECT * FROM `department` ORDER BY `dep_nme` REGEXP '^[^A-Za-z]' ASC, dep_nme");
												while($rec = mysqli_fetch_assoc($query_dep_nme)){
											?>
												<option value="<?= $rec["id"] ?>"><?=$rec["dep_nme"] ?></option>
											<?php } ?>
											</select>
											</div>
											<div class="form-group col-md-3">
                                                <label for="comp_no" class="col-form-label"><?= __("company_label") ?><span class="text-danger">*</span></label>
												<select class="form-control select2" name="comp_no" id="comp_no" required>
													<option value=""><?= __("select_option") ?></option>
													<?php
														$query_companies = mysqli_query($conDB, "SELECT * FROM `companies` ORDER BY `comp_name` REGEXP '^[^A-Za-z]' ASC, `comp_name`");
														while ($rec_con = mysqli_fetch_assoc($query_companies)) {
													?>
														<option value="<?= $rec_con["comp_id"] ?>"><?= ($is_rtl ?? false ? $rec_con["comp_name_ar"] : $rec_con["comp_name"]) ?></option>
													<?php } ?>
												</select>
                                            </div>
                                            <div class="form-group col-md-3">
                                                <label for="city_id" class="col-form-label"><?= __("city_label", "City") ?></label>
                                                <select class="form-control select2" name="city_id" id="city_id">
                                                <option value=""><?= __("select_option") ?></option>
                                                <?php
                                                    $query_cities = mysqli_query($conDB, "SELECT `id`, `name_en`, `name_ar` FROM `saudi_cities` ORDER BY `name_en` ASC");
                                                    while ($rec = mysqli_fetch_assoc($query_cities)) {
                                                ?>
                                                    <option value="<?= $rec["id"] ?>"><?= ($is_rtl ?? false ? $rec["name_ar"] : $rec["name_en"]) ?></option>
                                                <?php } ?>
                                                </select>
                                            </div>
                                            <div class="form-group col-md-3">
                                                <label for="location_id" class="col-form-label"><?= __("location_label", "Location") ?></label>
                                                <select class="form-control select2" name="location_id" id="location_id">
                                                <option value=""><?= __("select_a_city_first", "Select a City First") ?></option>
                                                </select>
                                            </div>
                                            <div class="form-group col-md-3">
                                                <label for="sub_dept_id" class="col-form-label"><?= __("sub_department_label", "Sub-Department") ?></label>
                                                <select class="form-control select2" name="sub_dept_id" id="sub_dept_id">
                                                <option value=""><?= __("select_a_department_first", "Select a Department First") ?></option>
                                                </select>
                                            </div>
                                            <div class="form-group col-md-3">
                                                <label for="mobile" class="col-form-label">Mobile No.</label>
                                                <input type="text" name="mobile" parsley-trigger="change"data-mask="0599999999" class="form-control" id="" >
                                            </div>
											<div class="form-group col-md-3">
                                                <label for="joining_date" class="col-form-label">Joining Date</label>
                                                <input type="text" name="joining_date" parsley-trigger="change" class="form-control" id="joining_date" autocomplete="off">
                                            </div>
											<div class="form-group col-md-3">
                                                <label for="salary" class="col-form-label">Salary<span class="text-danger">*</span></label>
                                                <input type="text" name="salary" class="form-control autonumber" data-v-max="25000" data-v-min="0" id="salary" required>
                                            </div>
											<div class="form-group col-md-3">
                                                <label for="dob" class="col-form-label">Date of Birth</label>
                                                <input type="text" name="dob" parsley-trigger="change" class="form-control" id="">
                                            </div>
											<div class="form-group col-md-3">
                                                <label class="font-14 mt-3 mb-2 radioalign">Gender <span class="text-danger">*</span></label>
												<div class="radio radio-info form-check-inline">
                                                    <input type="radio" id="inlineRadio3" value="male" name="sex" checked>
                                                    <b for="inlineRadio3"><i class="mdi mdi-human-male"></i> Male</b>
                                                </div>
                                                <div class="radio radio-info form-check-inline">
                                                    <input type="radio" id="inlineRadio1" value="female" name="sex">
                                                    <b for="inlineRadio1"><i class="mdi mdi-human-female"></i> Female </b>
                                                </div>
                                            </div>
											<div class="form-group col-md-3">
												<label for="avatar" class="col-form-label">Add Employee Picture</label>
												<input type="file" class="filestyle" name="avatar" data-btnClass="btn-primary">
                                            </div>
                                        </div>
										
										<input type="hidden" name="iqama_exp_g" id="iqama_exp_g" >
										
										<div class="btn-group" role="group" aria-label="Edit Button">
										<a href="./add_new_employee.php" class="btn btn-dark"><i class="fa fa-angle-double-left"></i> Back</a>
                                        <button type="submit" name="submit" class="btn btn-primary"><i class="mdi mdi-account-plus"></i> Register</button>
										</div>
                                    </form>
                                </div>
                            </div>
                        </div>
						

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
		
		<script src="./plugins/bootstrap-timepicker/hijri/bootstrap-hijri-datetimepicker.js"></script>
        <script src="./plugins/bootstrap-timepicker/hijri/bootstrap-hijri-datetimepicker.min.js"></script>
        <script src="./plugins/bootstrap-timepicker/hijri/bootstrap-hijri-datetimepickermin.js"></script>
		
        <script src="./plugins/bootstrap-filestyle/js/bootstrap-filestyle.min.js" type="text/javascript"></script>

        <!-- App js -->
		<script src="assets/pages/jquery.form-pickers.init.js"></script>
		<script src="assets/pages/jquery.form-hijri-pickers.init.js"></script>
		
		<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-validate/1.19.0/jquery.validate.js"></script>
		<script src="./assets/js/jquery.custom.validation.manpower.js"></script>
		
        <script src="assets/js/jquery.core.js"></script>
        <script src="assets/js/jquery.app.js?t=<?= time() ?>"></script>
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

$(document).ready(function() {

    $('#city_id').on('change', function() {
        populateLocationOptions($(this).val(), '');
    });
    $('#department').on('change', function() {
        populateSubDeptOptions($(this).val(), '');
    });

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
        </script>

    </body>
</html>