<?php
/****************************************************************
 * MODIFICATION SUMMARY (009-emp_end_of_service.php):
 * - Fixed vacation day calculation to be dependent on the selected End of Service Date, not the current date.
 * - Corrected the translation function call for the asset warning message to properly handle dynamic content.
 * - Made the 'days_served' calculation inclusive of the end date for better accuracy.
 * - Made the 'Working Days' field editable to allow manual override of salary calculation.
 * - Added a check against the 'payrolls' table to zero out working days and salary if already paid for the selected month.
 * - Made the payroll check case-insensitive and robust against whitespace.
 * - Replaced javascript .includes() with a more robust for-loop for checking paid months.
 ****************************************************************/

	require_once __DIR__ . '/includes/init.php';
    require_once __DIR__ . '/includes/session_check.php';
    

	$query = mysqli_query($conDB, "SELECT * FROM `admin_login` WHERE `id_iqama`='".$username."'");
	if(mysqli_num_rows($query) == 1){
	include("./includes/avatar_select.php");
	
	include("./includes/Hijri_GregorianConvert.php");
	$DateConv=new Hijri_GregorianConvert;
	$format="YYYY-MM-DD";
	
    // Use the emp_id from the URL, which is handled by emp_query.php
    $empidget = mysqli_real_escape_string($conDB, $_GET['emp_id']);
	require("./includes/emp_query.php");

	if(mysqli_num_rows($get_emp_data) !== 0){
		$emprow = mysqli_fetch_assoc($get_emp_data);
        
        // Second query to get EOS-specific details
        $eos_query = mysqli_query($conDB, "SELECT
            `emp_eos`.`created_at` AS `terminationDate`,
            `emp_eos`.`id` AS `eos_id`,
            `emp_eos`.`leaving_reason`
        FROM `emp_eos`
        WHERE `emp_eos`.`emp_id` = '{$emprow['empid']}'
        ORDER BY `emp_eos`.`id` DESC
        LIMIT 1");

        if(mysqli_num_rows($eos_query) > 0) {
            $eos_rec = mysqli_fetch_assoc($eos_query);
            $eos_id = $eos_rec['eos_id'];
            $eos_reason = $eos_rec['leaving_reason'];
            $terminationDate = $eos_rec['terminationDate'];
        } else {
            $eos_id = null;
            $eos_reason = null;
            $terminationDate = null;
        }

        // Query for outstanding loans
        $loan_query = mysqli_query($conDB, "
            SELECT 
                l.id, 
                l.total_payable, 
                COALESCE(SUM(p.amount), 0) as total_paid
            FROM emp_loan l
            LEFT JOIN emp_loan_payments p ON l.id = p.loan_id
            WHERE l.emp_id = '{$emprow['empid']}' AND l.status IN ('approved', 'dept_manager_pending', 'hr_manager_pending', 'finance_manager_pending', 'gm_pending', 'finance_assistant_pending')
            GROUP BY l.id
        ");
        $outstanding_loan = 0;
        if(mysqli_num_rows($loan_query) > 0){
            while($loan_row = mysqli_fetch_assoc($loan_query)){
                $outstanding_loan += $loan_row['total_payable'] - $loan_row['total_paid'];
            }
        }

        // Query for assigned assets
        $assets_query = mysqli_query($conDB, "SELECT COUNT(*) as assigned_assets_count FROM `employee_assets` WHERE `emp_id` = '{$emprow['empid']}' AND `status` = 'Assigned'");
        $assets_count_rec = mysqli_fetch_assoc($assets_query);
        $assigned_assets_count = $assets_count_rec['assigned_assets_count'];
		
    } else {
		header("Location: ./reg_employee.php");
        exit();
	}

    // --- START: New EOS Calculation Logic ---
    function makeCurlRequest($url, $method = 'POST', $payload = []) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        }
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_error = curl_error($ch);
        curl_close($ch);

        if ($curl_error) {
            error_log("cURL Error for $url: $curl_error");
            return ['error' => 'Curl error: ' . $curl_error, 'http_code' => 0, 'data' => null];
        }
        if ($http_code != 200) {
            error_log("HTTP Error $http_code for $url. Response: " . mb_substr($response, 0, 500));
        }

        return ['error' => null, 'http_code' => $http_code, 'data' => json_decode($response, true)];
    }

    function fetchEndOfServiceReasons() {
        $url = "https://knowledge-center-be.qiwa.sa/api/v1/end-of-service";
        $result = makeCurlRequest($url, 'POST', []);

        if ($result['error'] || $result['http_code'] !== 200 || empty($result['data'])) {
            return ['error' => __('Could not fetch initial data from the server.'), 'reasons' => []];
        }

        $api_reasons_data = $result['data']['EndOfServiceRewardLookUpRs']['Body']['EndOfServiceRewardLookUp']['ContractEndReason'] ?? [];
        
        return ['error' => null, 'reasons' => $api_reasons_data];
    }
    
    $contractType = $_POST['contract_type'] ?? '1';
    $selectedReasonCode = $_POST['eos_reason'] ?? '';
    // MODIFICATION: Do not default to the current date. Only use the date provided by the user.
    $endDateStr = $_POST['end_date'] ?? ''; 
    $allReasons = [];
    $errors = [];
    $general_error_message = '';

    $reasonsResult = fetchEndOfServiceReasons();
    if ($reasonsResult['error']) {
        $general_error_message = $reasonsResult['error'];
    } else {
        $allReasons = $reasonsResult['reasons'];
    }

    // Fetch all paid payroll months for the employee to use in JavaScript validation
    $paid_payrolls = [];
    $payroll_query = mysqli_prepare($conDB, "SELECT `month_year` FROM `payrolls` WHERE `emp_id` = ? AND LOWER(TRIM(`status`)) = 'paid'");
    if ($payroll_query) {
        mysqli_stmt_bind_param($payroll_query, "s", $emprow['empid']);
        mysqli_stmt_execute($payroll_query);
        $result = mysqli_stmt_get_result($payroll_query);
        while ($row = mysqli_fetch_assoc($result)) {
            $paid_payrolls[] = trim($row['month_year']);
        }
        mysqli_stmt_close($payroll_query);
    }

	// --- START: Prorated Vacation Calculation ---
    // This logic calculates the vacation days owed to an employee who is leaving before completing their full contract year.
    // It is based on the actual number of days served from the user-selected End of Service Date.
    $annual_vacation_entitlement = floatval($emprow['vacation_days']);
    $prorated_vacation_balance = 0;

    // This needs to be available for JS, so we calculate it regardless of whether an end date has been submitted yet.
    $used_days_query = mysqli_query($conDB, "SELECT SUM(`vacdays`) as `total_used` FROM `emp_vacation` WHERE `emp_id` = '{$emprow['empid']}' AND `is_deductible` = 1");
    $used_days_data = mysqli_fetch_assoc($used_days_query);
    $total_used_days = floatval($used_days_data['total_used'] ?? 0);

    // MODIFIED: Only perform the calculation if an End of Service Date has been provided.
    if (!empty($endDateStr)) {
        $joining_date_obj = new DateTime($emprow['joining_date']);
        $end_date_obj = new DateTime($endDateStr);

        // Ensure dates are valid and the employee has a vacation plan
        if ($end_date_obj >= $joining_date_obj && $annual_vacation_entitlement > 0) {
            // 1. Calculate the total number of days the employee has served.
            $interval = $end_date_obj->diff($joining_date_obj);
            // Add 1 to make the day count inclusive of the end date.
            $days_served = $interval->days + 1;

            // 2. Calculate the total vacation days accrued during the service period.
            // Formula: (Days Served / 365) * Annual Vacation Days
            $accrued_days = ($days_served / 365.0) * $annual_vacation_entitlement;

            // 3. The final balance is the accrued days minus any days already used.
            $prorated_vacation_balance = $accrued_days - $total_used_days;
        }
    }

    // Ensure the balance is not negative.
    $prorated_vacation_balance = max(0, $prorated_vacation_balance);
    // --- END: Prorated Vacation Calculation ---


    if(isset($_POST['submit'])){
        if($assigned_assets_count > 0){
            $errors['assets'] = "Cannot process termination. Employee has outstanding assets that must be returned first.";
        } else {
            $contractType = trim($_POST['contract_type'] ?? '');
            $selectedReasonCode = trim($_POST['eos_reason'] ?? '');
            $endDateStr = trim($_POST['end_date'] ?? '');
            $notes = mysqli_real_escape_string($conDB, $_POST['notes']);
            $anul_vac_days = filter_input(INPUT_POST, 'anul_vac_days', FILTER_VALIDATE_FLOAT, ['options' => ['default' => 0]]);
            $deduct = filter_input(INPUT_POST, 'deduct', FILTER_VALIDATE_FLOAT, ['options' => ['default' => 0]]);
            $eos_amount = filter_input(INPUT_POST, 'eos_amount', FILTER_VALIDATE_FLOAT, ['options' => ['default' => 0]]);
            $vacation_salary = filter_input(INPUT_POST, 'anul_vac_salry', FILTER_VALIDATE_FLOAT, ['options' => ['default' => 0]]);
            $net_payment = filter_input(INPUT_POST, 'net_payment', FILTER_VALIDATE_FLOAT, ['options' => ['default' => 0]]);

            // Check if salary is paid for the termination month
            $salaryPaidForTerminationMonth = false;
            if (!empty($endDateStr)) {
                $endDateTime = new DateTime($endDateStr);
                $month_year = $endDateTime->format('Y-m');
                $payroll_check_stmt = $conDB->prepare("SELECT id FROM `payrolls` WHERE `emp_id` = ? AND `month_year` = ? AND LOWER(TRIM(`status`)) = 'paid' LIMIT 1");
                $payroll_check_stmt->bind_param("ss", $emprow['empid'], $month_year);
                $payroll_check_stmt->execute();
                $payroll_check_stmt->store_result();
                if ($payroll_check_stmt->num_rows > 0) {
                    $salaryPaidForTerminationMonth = true;
                }
                $payroll_check_stmt->close();
            }

            if ($salaryPaidForTerminationMonth) {
                $curt_month_days = 0;
                $curt_month_salry = 0.00; // Force salary to 0 if already paid
            } else {
                // Use manually entered working days, fallback to date calculation
                $curt_month_days = filter_input(INPUT_POST, 'curt_month_days', FILTER_VALIDATE_INT, ['options' => ['default' => 0]]);
                $curt_month_salry = filter_input(INPUT_POST, 'curt_month_salry', FILTER_VALIDATE_FLOAT, ['options' => ['default' => 0]]);
            }
            
            if (empty($contractType)) $errors['contract_type'] = 'This field is required.';
            if (empty($selectedReasonCode)) $errors['eos_reason'] = 'This field is required.';
            if (empty($endDateStr)) $errors['end_date'] = 'This field is required.';
            if (empty($notes)) $errors['notes'] = 'This field is required.';
            
            if (!empty($emprow['joining_date']) && !empty($endDateStr)) {
                $startDateTime = new DateTime($emprow['joining_date']);
                $endDateTime = new DateTime($endDateStr);
                if ($startDateTime >= $endDateTime) {
                    $errors['end_date'] = "End date must be after start date.";
                }
            }
            
            $leaving_reason_en = '';
            $leaving_reason_ar = '';
            if (!empty($selectedReasonCode) && !empty($allReasons)) {
                foreach($allReasons as $reason) {
                    if ($reason['ContractEndReasonCode'] == $selectedReasonCode) {
                        $leaving_reason_en = $reason['EnDescription'] ?? 'N/A';
                        $leaving_reason_ar = $reason['ArDescription'] ?? 'N/A';
                        break;
                    }
                }
            }


            if (empty($errors)) {
                $serviceDuration = $startDateTime->diff($endDateTime);
                $t_years = $serviceDuration->y;
                $t_months = $serviceDuration->m;
                $t_days = $serviceDuration->d;

                $stmt = $conDB->prepare("INSERT INTO `emp_eos` (`emp_id`, `contract_type`, `eos_reason`, `leaving_reason`, `leaving_reason_ar`, `eos_amount`, `joining_date`, `end_date`, `t_years`, `t_months`, `t_days`, `anul_vac_days`, `anul_vac_salry`, `deduct`, `net_payment`, `notes`, `curt_month_days`, `curt_month_salry`) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("sisssdssiiiddddsid", $emprow['empid'], $contractType, $selectedReasonCode, $leaving_reason_en, $leaving_reason_ar, $eos_amount, $emprow['joining_date'], $endDateStr, $t_years, $t_months, $t_days, $anul_vac_days, $vacation_salary, $deduct, $net_payment, $notes, $curt_month_days, $curt_month_salry);
                $stmt->execute();

                $stmt_update = $conDB->prepare("UPDATE `employees` SET `status`='0', `ter_note`=?, `fly`='0', `ter_date`=NOW() WHERE `emp_id`=?");
                $stmt_update->bind_param("ss", $notes, $emprow['empid']);
                $stmt_update->execute();
                
                mysqli_query($conDB, "INSERT INTO `activity_log` (`user_editor`,`page`,`pg_id`,`reg_date`) VALUES ('".$username."','emp_end_of_service','".$_GET['emp_id']."','".date("c")."')");
                
                $error_1 = "<div class='alert alert-success'><strong>".__('Successfully!')."</strong> ".__('Employee End of Service has been registered.')."</div>";
                header("refresh:1; ./emp_end_of_service.php?emp_id=".$_GET['emp_id']."");
            }
        }
    }

    $filteredReasons = [];
    if (!empty($allReasons)) {
        foreach ($allReasons as $reason) {
            if (isset($reason['ContractTypeCode']) && $reason['ContractTypeCode'] == $contractType) {
                $filteredReasons[] = $reason;
            }
        }
        usort($filteredReasons, function($a, $b) {
            return intval($a['ContractEndReasonCode'] ?? 0) - intval($b['ContractEndReasonCode'] ?? 0);
        });
    }

	$checkGander = ($emprow['sex'] == 'Male')?'./assets/emp_pics/defult.png':'./assets/emp_pics/defultFemale.jpg';
	$emp_avatar_display = (!empty($emprow['avatar']) && file_exists(ltrim($emprow['avatar'], './'))) ? $emprow['avatar'] : $checkGander;

?>
	<!doctype html>
	<html lang="<?=$current_lang?>" dir="<?=($is_rtl) ? 'rtl' : 'ltr'?>">
	<head>
		<meta charset="utf-8" />
		<title><?= $site_title ?> - <?=__('End of Service Settlement');?></title>
		<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
		<meta content="Anees Afzal" name="author" />
		<meta http-equiv="X-UA-Compatible" content="IE=edge" />
		<link rel="shortcut icon" href="assets/images/favicon.ico">
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
		<script> window.lang = <?= json_encode($GLOBALS['translations'] ?? []) ?>;</script>
	</head>
	<body class="enlarged" data-keep-enlarged="true">
		<div id="wrapper">
			<div class="left side-menu">
				<div class="slimscroll-menu" id="remove-scroll">
					<div class="topbar-left">
						<a href="dashboard.php" class="logo">
							<span><img src="assets/images/logo.png" alt="" height="22"></span>
							<i><img src="assets/images/logo_sm.png" alt="" height="28"></i>
						</a>
					</div>
					<?php include("./includes/main_menu.php"); ?>
					<div class="clearfix"></div>
				</div>
			</div>
			<div class="content-page">
				<?php include("./includes/topbar.php"); ?>
				<div class="content">
					<div class="container-fluid">
                        <div class="row">
                            <div class="col-12">
                                <?=$error_1 ?? ''?>
                                <div class="card-box">
                                    <div class="text-center">
                                        <img src="assets/images/logo.png" alt="" height="60">
                                        <h3 class="mt-2"><?=__('final_settlement');?></h3>
                                        <h4><?=__('final_settlement_subheading');?></h4>
                                    </div>
                                    <hr>
                                    <?php if($eos_id){ ?>
                                        <div class="alert alert-danger text-center">
                                            <strong><?=__('terminated_date');?>:</strong> <?= date('d M, Y', strtotime($terminationDate)); ?> | 
                                            <strong><?=__('reason_notes');?>:</strong> <?= htmlspecialchars($eos_reason); ?>
                                        </div>
                                    <?php } ?>

                                    <div class="row mt-4">
                                        <div class="col-12">
                                            <div class="card">
                                                <div class="card-header bg-light">
                                                    <h4 class="m-0"><?=__('employee_information');?></h4>
                                                </div>
                                                <div class="card-body">
                                                    <div class="row">
                                                        <div class="col-md-2 text-center">
                                                            <img src="<?=$emp_avatar_display ?>" alt="<?=$emprow['name'] ?>" class="rounded-circle img-thumbnail" width="120" />
                                                        </div>
                                                        <div class="col-md-10">
                                                            <div class="row">
                                                                <div class="col-md-4"><p><strong><?=__('name');?>:</strong><br><?= htmlspecialchars($emprow['name']); ?></p></div>
                                                                <div class="col-md-4"><p><strong><?=__('employee_no');?>:</strong><br><?= htmlspecialchars($emprow['empid']); ?></p></div>
                                                                <div class="col-md-4"><p><strong><?=__('iqama_id');?>:</strong><br><?= htmlspecialchars($emprow['iqama']); ?></p></div>
                                                                <div class="col-md-4"><p><strong><?=__('department');?>:</strong><br><?= htmlspecialchars(($is_rtl ?? false ? $emprow['deptnme_ar']:$emprow['deptnme'])); ?></p></div>
                                                                <div class="col-md-4"><p><strong><?=__('actual_job');?>:</strong><br><?= htmlspecialchars(($is_rtl ?? false ? $emprow['jobname_ar']:$emprow['jobname'])); ?></p></div>
                                                                <div class="col-md-4"><p><strong><?=__('joining_date');?>:</strong><br><?= date('d M, Y', strtotime($emprow['joining_date'])); ?></p></div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Financial Summary Section -->
                                    <div class="card mt-4">
                                        <div class="card-header bg-light"><h5 class="m-0"><?=__('financial_summary');?></h5></div>
                                        <div class="card-body">
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <p><strong><?=__('total_remaining_vacation_days');?>:</strong><br><?= htmlspecialchars(number_format($prorated_vacation_balance, 2)); ?> <?=__('days');?></p>
                                                </div>
                                                <div class="col-md-6">
                                                    <p><strong><?=__('outstanding_loan_balance');?>:</strong><br><span class="text-danger"><?= htmlspecialchars(number_format($outstanding_loan, 2)); ?> <?=__('sar');?></span></p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Assigned Assets Section -->
                                    <div class="card mt-4">
                                        <div class="card-header bg-light"><h5 class="m-0"><?=__('assigned_assets_for_clearance');?></h5></div>
                                        <div class="card-body">
                                            <table class="table table-sm table-bordered">
                                                <thead>
                                                    <tr><th><?=__('asset_type');?></th><th><?=__('serial_number_identifier');?></th><th><?=__('description');?></th><th><?=__('Assigned Date');?></th></tr>
                                                </thead>
                                                <tbody>
                                                <?php
                                                    $assets_query_display = mysqli_query($conDB, "SELECT ea.*, a.name AS asset_name FROM `employee_assets` ea JOIN `assets` a ON ea.asset_id = a.id WHERE ea.emp_id = '{$emprow['empid']}' AND ea.status = 'Assigned'");
                                                    if(mysqli_num_rows($assets_query_display) > 0):
                                                        while($asset_row = mysqli_fetch_assoc($assets_query_display)):
                                                ?>
                                                    <tr>
                                                        <td><?= htmlspecialchars($asset_row['asset_name']); ?></td>
                                                        <td><?= htmlspecialchars($asset_row['serial_number']); ?></td>
                                                        <td><?= htmlspecialchars($asset_row['description']); ?></td>
                                                        <td><?= htmlspecialchars($asset_row['assigned_date']); ?></td>
                                                    </tr>
                                                <?php
                                                        endwhile;
                                                    else:
                                                ?>
                                                    <tr><td colspan="4" class="text-center"><?=__('no_assets_are_currently_assigned_to_this_employee');?></td></tr>
                                                <?php endif; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>

                                    <!-- EOS Calculation Section -->
                                    <div class="card mt-4">
                                        <div class="card-header bg-light"><h5 class="m-0"><?=__('end_of_service_calculation');?></h5></div>
                                        <div class="card-body">
                                            <?php if ($assigned_assets_count > 0): ?>
                                                <div class="alert alert-danger text-center">
                                                    <strong><?=__('Action Required:');?></strong> <?= str_replace('{count}', $assigned_assets_count, __('this_employee_has_outstanding_assets_please_ensure_all_assets_are_returned_before_proceeding_with_termination')) ?>
                                                </div>
                                            <?php endif; ?>

                                            <?php if ($eos_id == ""): ?>
                                                <form id="calculatorForm" action="emp_end_of_service.php?emp_id=<?=$_GET['emp_id']?>" method="post">
                                                    <fieldset <?= ($assigned_assets_count > 0) ? 'disabled' : '' ?>>
                                                        <?php if ($general_error_message): ?>
                                                            <div class="alert alert-danger"><?=htmlspecialchars($general_error_message); ?></div>
                                                        <?php endif; ?>
                                                        <?php if (!empty($errors['assets'])): ?><div class="alert alert-danger"><?=htmlspecialchars($errors['assets']); ?></div><?php endif; ?>
                                                        <div class="form-row align-items-end">
                                                            <div class="form-group col-lg-6">
                                                                <label><strong><?=__('type_of_contract');?>:</strong></label>
                                                                <div>
                                                                    <div class="custom-control custom-radio custom-control-inline">
                                                                        <input type="radio" id="contract-limited" name="contract_type" value="1" class="custom-control-input" <?=($contractType == "1") ? "checked" : ""; ?>>
                                                                        <label class="custom-control-label" for="contract-limited"><?=__('limited_period');?></label>
                                                                    </div>
                                                                    <div class="custom-control custom-radio custom-control-inline">
                                                                        <input type="radio" id="contract-unlimited" name="contract_type" value="2" class="custom-control-input" <?=($contractType == "2") ? "checked" : ""; ?>>
                                                                        <label class="custom-control-label" for="contract-unlimited"><?=__('unlimited_period');?></label>
                                                                    </div>
                                                                </div>
                                                                <?php if (!empty($errors['contract_type'])): ?><div class="text-danger"><small><?=htmlspecialchars($errors['contract_type']); ?></small></div><?php endif; ?>
                                                            </div>
                                                            <div class="form-group col-lg-6">
                                                                <label for="eos_reason"><strong><?=__('end_of_service_reason');?>:</strong><span class="text-danger">*</span></label>
                                                                <select id="eos_reason" required class="form-control calc-trigger" name="eos_reason" <?php if(empty($filteredReasons) && empty($general_error_message)) echo 'disabled';?>>
                                                                    <option value=""><?=__('select_reason');?></option>
                                                                    <?php if (!empty($filteredReasons)): ?>
                                                                        <?php foreach ($filteredReasons as $reason): ?>
                                                                            <option value="<?=htmlspecialchars($reason['ContractEndReasonCode']); ?>" <?=($selectedReasonCode == $reason['ContractEndReasonCode']) ? "selected" : ""; ?>>
                                                                                <?= ($current_lang === 'ar' && !empty($reason['ArDescription'])) ? htmlspecialchars($reason['ArDescription']) : htmlspecialchars($reason['EnDescription']); ?>
                                                                            </option>
                                                                        <?php endforeach; ?>
                                                                    <?php endif; ?>
                                                                </select>
                                                                <?php if (!empty($errors['eos_reason'])): ?><div class="text-danger"><small><?=htmlspecialchars($errors['eos_reason']); ?></small></div><?php endif; ?>
                                                            </div>
                                                            <div class="form-group col-lg-6">
                                                                <label for="joining_date"><?=__('joining_date');?>:</label>
                                                                <input type="text" name="joining_date" class="form-control" id="joining_date" value="<?=htmlspecialchars($emprow['joining_date']);?>" readonly>
                                                            </div>
                                                            <div class="form-group col-lg-6">
                                                                <label for="end_date"><?=__('end_of_service_date');?>:<span class="text-danger">*</span></label>
                                                                <input type="text" name="end_date" class="form-control datepicker calc-trigger" id="end_date" value="<?=htmlspecialchars($endDateStr); ?>" required autocomplete="off">
                                                                <?php if (!empty($errors['end_date'])): ?><div class="text-danger"><small><?=htmlspecialchars($errors['end_date']); ?></small></div><?php endif; ?>
                                                            </div>
                                                            <div class="form-group col-lg-3">
                                                                <label for="anul_vac_days"><?=__('annual_vacation_days');?></label>
                                                                <input type="number" class="form-control calc-trigger" value="<?= htmlspecialchars(number_format($prorated_vacation_balance, 2)); ?>" id="anul_vac_days" name="anul_vac_days" step="any" placeholder="calculated_from_end_date">
                                                            </div>
                                                            <div class="form-group col-lg-2">
                                                                <label for="curt_month_days_display"><?=__('working_days');?></label>
                                                                <input type="number" class="form-control" id="curt_month_days_display" name="curt_month_days">
                                                            </div>
                                                            <div class="form-group col-lg-3">
                                                                <label for="curt_month_salry"><?=__('resignation_month_salary');?></label>
                                                                <input type="text" class="form-control calc-trigger" value="<?= htmlspecialchars($_POST['curt_month_salry'] ?? '0.00'); ?>" id="curt_month_salry" name="curt_month_salry" readonly>
                                                            </div>
                                                            <div class="form-group col-lg-4">
                                                                <label for="deduct" class="text-danger"><?=__('deduct_loan_etc');?></label>
                                                                <input type="number" class="form-control text-danger calc-trigger" value="<?= htmlspecialchars($_POST['deduct'] ?? $outstanding_loan); ?>" id="deduct" name="deduct" step="any">
                                                            </div>
                                                            <div class="col-12"><hr/></div>
                                                            <div class="form-group col-lg-4">
                                                                <label><?=__('eos_amount_from_api');?></label>
                                                                <input type="text" class="form-control" id="eos_amount_display" readonly style="background-color: #e9ecef;">
                                                            </div>
                                                            <div class="form-group col-lg-4">
                                                                <label><?=__('vacation_salary');?></label>
                                                                <input type="text" class="form-control" id="vacation_salary_display" readonly style="background-color: #e9ecef;">
                                                            </div>
                                                            <div class="form-group col-lg-4">
                                                                <label class="font-weight-bold"><?=__('total_net_payment');?></label>
                                                                <input type="text" class="form-control font-weight-bold" id="net_payment_display" readonly style="background-color: #dff0d8;">
                                                            </div>
                                                            <div class="col-12"><hr/></div>
                                                            <div class="form-group col-lg-8">
                                                                <label for="notes"><?=__('notes');?>:<span class="text-danger">*</span></label>
                                                                <input type="text" class="form-control" id="notes" name="notes" value="<?= htmlspecialchars($_POST['notes'] ?? ''); ?>" required />
                                                                <?php if (!empty($errors['notes'])): ?><div class="text-danger"><small><?=htmlspecialchars($errors['notes']); ?></small></div><?php endif; ?>
                                                            </div>
                                                            <div class="form-group col-lg-4">
                                                                <button type="submit" name="submit" class="btn btn-danger btn-block"><i class="mdi mdi-settings"></i> <?=__('register_eos');?></button>
                                                            </div>
                                                        </div>
                                                        <input type="hidden" name="eos_amount" id="eos_amount_hidden">
                                                        <input type="hidden" name="anul_vac_salry" id="anul_vac_salry_hidden">
                                                        <input type="hidden" name="net_payment" id="net_payment_hidden">
                                                        <input type="hidden" id="salary" value="<?= htmlspecialchars($emprow['salary']); ?>">
                                                        <input type="hidden" id="annual_vacation_entitlement" value="<?= htmlspecialchars($annual_vacation_entitlement); ?>">
                                                        <input type="hidden" id="total_used_vacation_days" value="<?= htmlspecialchars($total_used_days); ?>">
                                                    </fieldset>
                                                </form>
                                            <?php else: ?>
                                                <div class="text-right">
                                                    <a href="./end_of_service_print.php?emp_id=<?=$_GET['emp_id'];?>" target="_blank" class="btn btn-primary"><i class="mdi mdi-printer"></i> <?=__('Print Report');?></a>
                                                </div>
                                            <?php endif ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
					</div>
				</div>
				<footer class="footer"><?= $site_footer ?></footer>
			</div>
		</div>
		<script src="assets/js/jquery.min.js"></script>
		<script src="assets/js/bootstrap.bundle.min.js"></script>
		<script src="assets/js/metisMenu.min.js"></script>
		<script src="assets/js/waves.js"></script>
		<script src="assets/js/jquery.slimscroll.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
		<script src="./plugins/bootstrap-datepicker/js/bootstrap-datepicker.min.js"></script>
		<script src="assets/js/jquery.app.js"></script>
        <script>
            const paidPayrolls = <?= json_encode($paid_payrolls); ?>;
        </script>
		<script type="text/javascript">
            $(document).ready(function(){
                
                $("#eos_reason").select2();
                
                function isSalaryPaidForMonth(endDateStr) {
                    if (!endDateStr || !window.paidPayrolls || !window.paidPayrolls.length) {
                        return false;
                    }
                    const targetMonthYear = endDateStr.substring(0, 7);
                    for (var i = 0; i < window.paidPayrolls.length; i++) {
                        if (String(window.paidPayrolls[i]).trim() === targetMonthYear) {
                            return true;
                        }
                    }
                    return false;
                }

                function calculateProratedVacation() {
                    const joiningDateStr = $('#joining_date').val();
                    const endDateStr = $('#end_date').val();
                    const annualVacationEntitlement = parseFloat($('#annual_vacation_entitlement').val()) || 0;
                    const totalUsedDays = parseFloat($('#total_used_vacation_days').val()) || 0;

                    if (!joiningDateStr || !endDateStr || annualVacationEntitlement <= 0) {
                        $('#anul_vac_days').val('0.00');
                        return;
                    }

                    const joiningDate = new Date(joiningDateStr);
                    const endDate = new Date(endDateStr);

                    if (endDate < joiningDate) {
                        $('#anul_vac_days').val('0.00');
                        return;
                    }

                    // Calculate the difference in time in milliseconds
                    const timeDiff = endDate.getTime() - joiningDate.getTime();
                    // Convert milliseconds to days and add 1 to make the count inclusive
                    const daysServed = (timeDiff / (1000 * 3600 * 24)) + 1;

                    const accruedDays = (daysServed / 365.0) * annualVacationEntitlement;
                    let proratedBalance = accruedDays - totalUsedDays;
                    proratedBalance = Math.max(0, proratedBalance);

                    $('#anul_vac_days').val(proratedBalance.toFixed(2));
                }

                function updateResignationSalary() {
                    const workedDays = parseInt($('#curt_month_days_display').val()) || 0;
                    const endDateStr = $('#end_date').val();
                    const basicSalary = parseFloat($('#salary').val()) || 0;
                    
                    if (isSalaryPaidForMonth(endDateStr)) {
                        $('#curt_month_salry').val('0.00');
                        performCalculation();
                        return;
                    }

                    if (!endDateStr || basicSalary <= 0) {
                        $('#curt_month_salry').val('0.00');
                    } else {
                        const endDate = new Date(endDateStr);
                        const year = endDate.getFullYear();
                        const month = endDate.getMonth() + 1; // JS months are 0-indexed

                        const daysInMonth = new Date(year, month, 0).getDate();
                        if (daysInMonth > 0) {
                            const proratedSalary = (basicSalary / daysInMonth) * workedDays;
                            $('#curt_month_salry').val(proratedSalary.toFixed(2));
                        } else {
                            $('#curt_month_salry').val('0.00');
                        }
                    }
                    performCalculation();
                }

                function updateWorkingDaysDisplay() {
                    const endDateStr = $('#end_date').val();
                    
                    if (isSalaryPaidForMonth(endDateStr)) {
                        $('#curt_month_days_display').val('0').prop('readonly', true);
                        return;
                    }
                    
                    $('#curt_month_days_display').prop('readonly', false);

                    if (!endDateStr) {
                        $('#curt_month_days_display').val('0');
                        return;
                    }

                    const endDate = new Date(endDateStr);
                    const today = new Date();
                    today.setHours(0, 0, 0, 0);

                    const year = endDate.getFullYear();
                    const day = endDate.getDate();
                    
                    let displayWorkedDays;
                    
                    if (year > today.getFullYear() || (year === today.getFullYear() && endDate.getMonth() > today.getMonth())) {
                        displayWorkedDays = 0;
                    } else {
                        displayWorkedDays = day;
                    }
                    
                    $('#curt_month_days_display').val(displayWorkedDays);
                }

                $('.datepicker').datepicker({
                    format: "yyyy-mm-dd",
                    todayHighlight: true,
                    autoclose: true
                }).on('changeDate', function(e) {
                    calculateProratedVacation();
                    updateWorkingDaysDisplay();
                    updateResignationSalary();
                });

                $('input[name="contract_type"]').on('change', function() {
                    $('#calculatorForm').submit();
                });

                $('#curt_month_days_display').on('change keyup', function() {
                    updateResignationSalary();
                });

                function performCalculation() {
                    const formData = {
                        contract_type: $('input[name="contract_type"]:checked').val(),
                        eos_reason: $('#eos_reason').val(),
                        end_date: $('#end_date').val(),
                        joining_date: $('#joining_date').val(),
                        salary: $('#salary').val(),
                        anul_vac_days: $('#anul_vac_days').val(),
                        deduct: $('#deduct').val()
                    };

                    if (!formData.eos_reason || !formData.end_date) {
                        $('#eos_amount_display, #vacation_salary_display, #net_payment_display').val('');
                        $('#eos_amount_hidden, #anul_vac_salry_hidden, #net_payment_hidden').val('');
                        return; 
                    }

                    $('#net_payment_display').val(__('calculating'));

                    $.ajax({
                        type: 'POST',
                        url: './includes/ajaxFile/ajax_eos_calculator.php',
                        data: formData,
                        dataType: 'json',
                        success: function(response) {
                            if (response.success) {
                                const resignation_salary = parseFloat($('#curt_month_salry').val()) || 0;
                                const net_payment_from_api = parseFloat(response.net_payment) || 0;
                                const final_net_payment = net_payment_from_api + resignation_salary;

                                $('#eos_amount_display').val(response.eos_amount);
                                $('#vacation_salary_display').val(response.vacation_salary);
                                $('#net_payment_display').val(final_net_payment.toFixed(2));
                                
                                $('#eos_amount_hidden').val(response.eos_amount);
                                $('#anul_vac_salry_hidden').val(response.vacation_salary);
                                $('#net_payment_hidden').val(final_net_payment.toFixed(2));
                            } else {
                                $('#net_payment_display').val(__('error_title'));
                            }
                        },
                        error: function() {
                            $('#net_payment_display').val(__('error_title'));
                        }
                    });
                }

                $('.calc-trigger').on('change keyup', function() {
                    performCalculation();
                });
                
                // Trigger calculation on page load if date is already set
                if($('#end_date').val()){
                    updateWorkingDaysDisplay();
                    updateResignationSalary();
                }
            });
		</script>
	</body>
	</html>
<?php } ?>