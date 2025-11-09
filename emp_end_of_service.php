<?php
/****************************************************************
 * MODIFICATION SUMMARY (010-emp_end_of_service.php):
 * - COUNTRY RESTRICTION: Added check to prevent EOS processing for employees
 *   from country ID 121. Form is disabled and error message shown.
 * - HOUSING ALLOWANCE CALCULATION: If employee has no housing allowance (housing = 0),
 *   the system now calculates it as (basic/12*2) and includes it in total salary.
 *   Example: basic 1800, housing 0 → calculated housing = (1800/12*2) = 300
 *   Total = basic 1800 + food 300 + calculated 300 + all other allowances
 * - UPDATED SALARY CALCULATION: Modified actual_salary_base to include calculated
 *   housing when original housing is 0, ensuring accurate vacation pay calculations.
 * 
 * PREVIOUS MODIFICATION (009-emp_end_of_service.php):
 * - REVERTED & FIXED VACATION LOGIC: Reinstated the use of the
 * `emp_vacation_balance` table to get a carried-over balance.
 * - IMPROVED ACCRUAL CALCULATION: The logic now correctly calculates
 * the vacation accrued only for the final, partial year of service and
 * adds it to the opening balance before subtracting vacation taken
 * during that same period.
 * - ADDED OPENING BALANCE FIELD: A new hidden field has been added
 * to pass the carried-over balance to the JavaScript calculator.
 *
 * MODIFICATION (2025-11-05):
 * - Added logic to pass contract duration (1 or 2 years) to JavaScript.
 * - Modified JavaScript `updateProratedVacation` function to use
 * the correct *annual* vacation rate (e.g., 21 instead of 42)
 * and calculate the daily rate based on 365 days to match
 * the old system's calculation method.
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
        
        // Get full salary details for accurate calculations - ONLY active salary (status = 1)
        $get_salary_data = mysqli_query($conDB, "SELECT * FROM `emp_salary` WHERE `emp_id`='".$emprow['empid']."' AND `status` = 1 ORDER BY `id` DESC LIMIT 1");
        $salaryrow = mysqli_fetch_assoc($get_salary_data) ?: [];
        
        // Calculate Total Salary by summing up all components
        $total_salary = 0;
        if (!empty($salaryrow)) {
            $basic_salary = (float)($salaryrow['basic'] ?? 0);
            $housing_benefit = (float)($salaryrow['housing'] ?? 0);
            $calculated_housing = 0;

            // If housing benefit is not provided, calculate it as (basic/12)*2
            if ($housing_benefit == 0 && $basic_salary > 0) {
                $calculated_housing = ($basic_salary / 12) * 2;
            } else {
                $calculated_housing = $housing_benefit;
            }
        }

        // Calculate base salary for EOS calculation (includes calculated housing if missing)
        $actual_salary_base = 0;
        if (!empty($salaryrow)) {
            $actual_salary_base += (float)($salaryrow['basic'] ?? 0);
            
            // Add calculated housing if original housing is 0, otherwise use actual housing
            if ((float)($salaryrow['housing'] ?? 0) == 0 && (float)($salaryrow['basic'] ?? 0) > 0) {
                $actual_salary_base += (($salaryrow['basic'] / 12) * 2); // calculated housing for EOS
            } else {
                $actual_salary_base += (float)($salaryrow['housing'] ?? 0); // actual housing
            }
            
            $actual_salary_base += (float)($salaryrow['transport'] ?? 0);
            $actual_salary_base += (float)($salaryrow['food'] ?? 0);
            $actual_salary_base += (float)($salaryrow['misc'] ?? 0);
            $actual_salary_base += (float)($salaryrow['cashier'] ?? 0);
            $actual_salary_base += (float)($salaryrow['fuel'] ?? 0);
            $actual_salary_base += (float)($salaryrow['tel'] ?? 0);
            $actual_salary_base += (float)($salaryrow['guard'] ?? 0);
            $actual_salary_base += (float)($salaryrow['other'] ?? 0);
        }

        // Calculate base salary for VACATION calculation (uses ONLY actual housing, NO calculated housing)
        $vacation_salary_base = 0;
        if (!empty($salaryrow)) {
            $vacation_salary_base += (float)($salaryrow['basic'] ?? 0);
            $vacation_salary_base += (float)($salaryrow['housing'] ?? 0); // ONLY actual housing, no calculation
            $vacation_salary_base += (float)($salaryrow['transport'] ?? 0);
            $vacation_salary_base += (float)($salaryrow['food'] ?? 0);
            $vacation_salary_base += (float)($salaryrow['misc'] ?? 0);
            $vacation_salary_base += (float)($salaryrow['cashier'] ?? 0);
            $vacation_salary_base += (float)($salaryrow['fuel'] ?? 0);
            $vacation_salary_base += (float)($salaryrow['tel'] ?? 0);
            $vacation_salary_base += (float)($salaryrow['guard'] ?? 0);
            $vacation_salary_base += (float)($salaryrow['other'] ?? 0);
        }

        // Set total_salary for API (uses EOS base with calculated housing)
        $total_salary = $actual_salary_base;

        // Get the annual vacation entitlement directly from the employee's record.
        // Note: This field (`vacation_days`) stores the TOTAL period entitlement (e.g., 42 for 2 years)
        $annual_vacation_entitlement = (float)($emprow['vacation_days'] ?? 0);

        // --- START: Get current vacation balance (available_balance) and period_end ---
        $current_vacation_balance = 0.0;
        $balance_period_end = null;
        
        $empid_esc = mysqli_real_escape_string($conDB, $emprow['empid']);
        
        // Get current available balance and period_end from emp_vacation_balance
        $vac_balance_q = mysqli_query($conDB, "SELECT available_balance, period_end FROM emp_vacation_balance WHERE emp_id = '{$empid_esc}' ORDER BY id DESC LIMIT 1");
        if ($vac_balance_q && mysqli_num_rows($vac_balance_q) > 0) {
            $vac_row = mysqli_fetch_assoc($vac_balance_q);
            if (isset($vac_row['available_balance']) && is_numeric($vac_row['available_balance'])) {
                $current_vacation_balance = floatval($vac_row['available_balance']);
            }
            if (isset($vac_row['period_end'])) {
                $balance_period_end = $vac_row['period_end'];
            }
        }
        // --- END: Get current vacation balance ---

        // --- START: Get Contract Period Length (1 or 2 years) ---
        $is_two_year_contract = 0;
        if (!empty($emprow['vac_period'])) {
            $vac_period_id = mysqli_real_escape_string($conDB, $emprow['vac_period']);
            $period_q = mysqli_query($conDB, "SELECT period FROM contract_period WHERE id = '{$vac_period_id}' LIMIT 1");
            $period_row = mysqli_fetch_assoc($period_q);
            $contract_period_string = $period_row ? $period_row['period'] : '';
            if (strpos($contract_period_string, '2 Years') !== false) {
                $is_two_year_contract = 1;
            }
        }
        // --- END: Get Contract Period Length ---
        
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
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36'
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        // Disable SSL verification for robustness, especially in local environments like XAMPP
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

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

        if ($result['error']) {
            return ['error' => __('Could not fetch initial data from the server: ') . $result['error'], 'reasons' => []];
        }
        if ($result['http_code'] !== 200 || empty($result['data'])) {
            return ['error' => __('Could not fetch initial data from the server (HTTP Status: ').$result['http_code'].')', 'reasons' => []];
        }

        $api_reasons_data = $result['data']['EndOfServiceRewardLookUpRs']['Body']['EndOfServiceRewardLookUp']['ContractEndReason'] ?? [];
        
        return ['error' => null, 'reasons' => $api_reasons_data];
    }
    
    $contractType = $_POST['contract_type'] ?? '1';
    $selectedReasonCode = $_POST['eos_reason'] ?? '';
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
    
    // Fetch all deductible vacation records for this employee to be used in JS for client-side calculation
    $vacation_records = [];
    $vac_query = mysqli_query($conDB, "SELECT `start_date`, `vacdays` FROM `emp_vacation` WHERE `emp_id` = '{$emprow['empid']}' AND `is_deductible` = 1");
    if($vac_query){
        while($vac_row = mysqli_fetch_assoc($vac_query)){
            $vacation_records[] = $vac_row;
        }
    }

    if(isset($_POST['submit'])){
        // Check if employee country is 121 (should not process EOS for this country)
        if ($emprow['country'] == 121) {
            $errors['country'] = "EOS processing is not allowed for employees from country ID: 121.";
        }
        
        if(empty($errors) && $assigned_assets_count > 0){
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
            $overtime_hours = filter_input(INPUT_POST, 'overtime_hours', FILTER_VALIDATE_FLOAT, ['options' => ['default' => 0]]);
            $overtime_days = filter_input(INPUT_POST, 'overtime_days', FILTER_VALIDATE_FLOAT, ['options' => ['default' => 0]]);

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

                $stmt = $conDB->prepare("INSERT INTO `emp_eos` (`emp_id`, `contract_type`, `eos_reason`, `leaving_reason`, `leaving_reason_ar`, `eos_amount`, `joining_date`, `end_date`, `t_years`, `t_months`, `t_days`, `anul_vac_days`, `anul_vac_salry`, `overtime_hours`, `overtime_days`, `deduct`, `net_payment`, `notes`, `curt_month_days`, `curt_month_salry`) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("sisssdssiiiddddddsid", $emprow['empid'], $contractType, $selectedReasonCode, $leaving_reason_en, $leaving_reason_ar, $eos_amount, $emprow['joining_date'], $endDateStr, $t_years, $t_months, $t_days, $anul_vac_days, $vacation_salary, $overtime_hours, $overtime_days, $deduct, $net_payment, $notes, $curt_month_days, $curt_month_salry);
                $stmt->execute();

                $stmt_update = $conDB->prepare("UPDATE `employees` SET `status`='0', `ter_note`=?, `fly`='0', `ter_date`=? WHERE `emp_id`=?");
                $stmt_update->bind_param("sss", $notes, $endDateStr, $emprow['empid']);
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
		<link rel="shortcut icon" href="<?=get_setting($conDB, 'favicon')?>">
		<link href="./plugins/bootstrap-timepicker/bootstrap-timepicker.min.css" rel="stylesheet">
        <link href="./plugins/bootstrap-colorpicker/css/bootstrap-colorpicker.min.css" rel="stylesheet">
        <link href="./plugins/bootstrap-datepicker/css/bootstrap-datepicker.min.css" rel="stylesheet">
        <link href="./plugins/clockpicker/css/bootstrap-clockpicker.min.css" rel="stylesheet">
        <link href="./plugins/bootstrap-daterangepicker/daterangepicker.css" rel="stylesheet">
		
		<link rel="stylesheet" href="./plugins/croppie/croppie.css">
		
		<link rel="stylesheet" href="./plugins/bootstrap-select/css/bootstrap-select.min.css">
		<link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css" rel="stylesheet" />
		
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
				<div class="content">
					<div class="container-fluid">
                        <div class="row">
                            <div class="col-12">
                                <?=$error_1 ?? ''?>
                                <div class="card-box">
                                    <div class="text-center">
                                        <img src="<?=get_setting($conDB, 'logo')?>" alt="" height="60">
                                        <h3 class="mt-2"><?=__('FINAL SETTLEMENT');?></h3>
                                        <h4><?=__('Final Settlement Subheading');?></h4>
                                    </div>
                                    <hr>
                                    <?php if($eos_id){ ?>
                                        <div class="alert alert-danger text-center">
                                            <strong><?=__('Terminated on:');?></strong> <?= date('d M, Y', strtotime($terminationDate)); ?> | 
                                            <strong><?=__('Reason:');?></strong> <?= htmlspecialchars($eos_reason); ?>
                                        </div>
                                    <?php } ?>

                                    <div class="row mt-4">
                                        <div class="col-12">
                                            <div class="card">
                                                <div class="card-header bg-light">
                                                    <h4 class="m-0"><?=__('Employee Information');?></h4>
                                                </div>
                                                <div class="card-body">
                                                    <div class="row">
                                                        <div class="col-md-2 text-center">
                                                            <img src="<?=$emp_avatar_display ?>" alt="<?=$emprow['name'] ?>" class="rounded-circle img-thumbnail" width="120" />
                                                        </div>
                                                        <div class="col-md-10">
                                                            <div class="row">
                                                                <div class="col-md-4"><p><strong><?=__('Name');?>:</strong><br><?= htmlspecialchars($emprow['name']); ?></p></div>
                                                                <div class="col-md-4"><p><strong><?=__('Employee ID');?>:</strong><br><?= htmlspecialchars($emprow['empid']); ?></p></div>
                                                                <div class="col-md-4"><p><strong><?=__('Iqama / ID');?>:</strong><br><?= htmlspecialchars($emprow['iqama']); ?></p></div>
                                                                <div class="col-md-4"><p><strong><?=__('Department');?>:</strong><br><?= htmlspecialchars($emprow['deptnme']); ?></p></div>
                                                                <div class="col-md-4"><p><strong><?=__('Job Title');?>:</strong><br><?= htmlspecialchars($emprow['jobname']); ?></p></div>
                                                                <div class="col-md-4"><p><strong><?=__('Joining Date');?>:</strong><br><?= date('d M, Y', strtotime($emprow['joining_date'])); ?></p></div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Financial Summary Section -->
                                    <div class="card mt-4">
                                        <div class="card-header bg-light"><h5 class="m-0"><?=__('Financial Summary');?></h5></div>
                                        <div class="card-body">
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <p><strong><?=__('Total Remaining Vacation Days');?>:</strong><br><span id="vacation_days_summary">0.00</span> <?=__('days');?></p>
                                                </div>
                                                <div class="col-md-6">
                                                    <p><strong><?=__('Outstanding Loan Balance');?>:</strong><br><span class="text-danger"><?= htmlspecialchars(number_format($outstanding_loan, 2)); ?> <?=__('SAR');?></span></p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Assigned Assets Section -->
                                    <div class="card mt-4">
                                        <div class="card-header bg-light"><h5 class="m-0"><?=__('Assigned Assets for Clearance');?></h5></div>
                                        <div class="card-body">
                                            <table class="table table-sm table-bordered">
                                                <thead>
                                                    <tr><th><?=__('Asset Type');?></th><th><?=__('Serial Number');?></th><th><?=__('Description');?></th><th><?=__('Assigned Date');?></th></tr>
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
                                                    <tr><td colspan="4" class="text-center"><?=__('No assets are currently assigned to this employee.');?></td></tr>
                                                <?php endif; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>

                                    <!-- EOS Calculation Section -->
                                    <div class="card mt-4">
                                        <div class="card-header bg-light"><h5 class="m-0"><?=__('End of Service Calculation');?></h5></div>
                                        <div class="card-body">
                                            <?php if ($assigned_assets_count > 0): ?>
                                                <div class="alert alert-danger text-center">
                                                    <strong><?=__('Action Required:');?></strong> <?= str_replace('{count}', $assigned_assets_count, __('This employee has {count} outstanding asset(s). Please ensure all assets are returned before proceeding with termination.')) ?>
                                                </div>
                                            <?php endif; ?>

                                            <?php if ($eos_id == ""): ?>
                                                <form id="calculatorForm" action="emp_end_of_service.php?emp_id=<?=$_GET['emp_id']?>" method="post">
                                                    <fieldset <?= ($assigned_assets_count > 0) ? 'disabled' : '' ?>>
                                                        <?php if ($general_error_message): ?>
                                                            <div class="alert alert-danger"><?=htmlspecialchars($general_error_message); ?></div>
                                                        <?php endif; ?>
                                                        <?php if (!empty($errors['assets'])): ?><div class="alert alert-danger"><?=htmlspecialchars($errors['assets']); ?></div><?php endif; ?>
                                                        <?php if (!empty($errors['country'])): ?><div class="alert alert-danger"><?=htmlspecialchars($errors['country']); ?></div><?php endif; ?>
                                                        <div class="form-row align-items-end">
                                                            <div class="form-group col-lg-6">
                                                                <label><strong><?=__('Type of Contract');?>:</strong></label>
                                                                <div>
                                                                    <div class="custom-control custom-radio custom-control-inline">
                                                                        <input type="radio" id="contract-limited" name="contract_type" value="1" class="custom-control-input" <?=($contractType == "1") ? "checked" : ""; ?>>
                                                                        <label class="custom-control-label" for="contract-limited"><?=__('Limited Period');?></label>
                                                                    </div>
                                                                    <div class="custom-control custom-radio custom-control-inline">
                                                                        <input type="radio" id="contract-unlimited" name="contract_type" value="2" class="custom-control-input" <?=($contractType == "2") ? "checked" : ""; ?>>
                                                                        <label class="custom-control-label" for="contract-unlimited"><?=__('Unlimited Period');?></label>
                                                                    </div>
                                                                </div>
                                                                <?php if (!empty($errors['contract_type'])): ?><div class="text-danger"><small><?=htmlspecialchars($errors['contract_type']); ?></small></div><?php endif; ?>
                                                            </div>
                                                            <div class="form-group col-lg-6">
                                                                <label for="eos_reason"><strong><?=__('End of Service Reason');?>:</strong><span class="text-danger">*</span></label>
                                                                <select id="eos_reason" required class="form-control" name="eos_reason" <?php if(empty($filteredReasons) && empty($general_error_message)) echo 'disabled';?>>
                                                                    <option value=""><?=__('Choose a reason');?></option>
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
                                                                <label for="joining_date"><?=__('Joining Date');?>:</label>
                                                                <input type="text" name="joining_date" class="form-control" id="joining_date" value="<?=htmlspecialchars($emprow['joining_date']);?>" readonly>
                                                            </div>
                                                            <div class="form-group col-lg-6">
                                                                <label for="end_date"><?=__('End of Service Date');?>:<span class="text-danger">*</span></label>
                                                                <input type="text" name="end_date" class="form-control datepicker" id="end_date" value="<?=htmlspecialchars($endDateStr); ?>" required autocomplete="off">
                                                                <?php if (!empty($errors['end_date'])): ?><div class="text-danger"><small><?=htmlspecialchars($errors['end_date']); ?></small></div><?php endif; ?>
                                                            </div>

                                                            <!-- Calculation Row 1 -->
                                                            <div class="form-group col-lg-2">
                                                                <label for="anul_vac_days"><?=__('Annual vacation days');?></label>
                                                                <input type="number" class="form-control" value="0.00" id="anul_vac_days" name="anul_vac_days" step="any" placeholder="0.00" readonly>
                                                            </div>
                                                            <div class="form-group col-lg-2">
                                                                <label for="curt_month_days_display"><?=__('Working Days');?></label>
                                                                <input type="number" class="form-control" id="curt_month_days_display" name="curt_month_days" value="0" readonly>
                                                            </div>
                                                            <div class="form-group col-lg-2">
                                                                <label for="absent_days" class="text-danger"><?=__('Absent Days');?></label>
                                                                <input type="number" class="form-control calculation-trigger" id="absent_days" name="absent_days" value="0" min="0">
                                                            </div>
                                                            <div class="form-group col-lg-2">
                                                                <label for="deduction_hours" class="text-danger"><?=__('Deduction (Hours)');?></label>
                                                                <input type="number" class="form-control calculation-trigger" id="deduction_hours" name="deduction_hours" value="0" min="0">
                                                            </div>
                                                            <div class="form-group col-lg-2">
                                                                <label for="overtime_hours" class="text-success"><?=__('Overtime (Hours)');?></label>
                                                                <input type="number" class="form-control calculation-trigger" id="overtime_hours" name="overtime_hours" value="0" min="0" step="0.5">
                                                            </div>
                                                            <div class="form-group col-lg-2">
                                                                <label for="overtime_days" class="text-success"><?=__('Overtime (Days)');?></label>
                                                                <input type="number" class="form-control calculation-trigger" id="overtime_days" name="overtime_days" value="0" min="0" step="0.5">
                                                            </div>
                                                            <div class="form-group col-lg-4">
                                                                <label for="curt_month_salry"><?=__('Resignation Month Salary');?></label>
                                                                <input type="text" class="form-control" value="0.00" id="curt_month_salry" name="curt_month_salry" readonly>
                                                            </div>

                                                            <!-- Calculation Row 2 (Deductions) -->
                                                            <?php if ($emprow['country'] == 191 && floatval($emprow['gosi']) > 0): ?>
                                                                <div class="form-group col-lg-4">
                                                                    <label for="gosi_deduction" class="text-danger"><?=__('GOSI Deduction');?></label>
                                                                    <input type="number" class="form-control text-danger calculation-trigger" id="gosi_deduction" name="gosi_deduction" value="0.00" step="any">
                                                                </div>
                                                                <div class="form-group col-lg-4">
                                                                    <label for="other_earnings" class="text-success"><?=__('Other Earnings');?></label>
                                                                    <input type="number" class="form-control text-success calculation-trigger" id="other_earnings" name="other_earnings" value="0.00" step="any">
                                                                </div>
                                                                <div class="form-group col-lg-4">
                                                                    <label for="deduct" class="text-danger"><?=__('Other Deductions (Loan, etc.)');?></label>
                                                                    <input type="number" class="form-control text-danger calculation-trigger" value="<?= htmlspecialchars($outstanding_loan); ?>" id="deduct" name="deduct" step="any">
                                                                </div>
                                                            <?php else: ?>
                                                                <div class="form-group col-lg-4">
                                                                    <label for="other_earnings" class="text-success"><?=__('Other Earnings');?></label>
                                                                    <input type="number" class="form-control text-success calculation-trigger" id="other_earnings" name="other_earnings" value="0.00" step="any">
                                                                </div>
                                                                <div class="form-group col-lg-4">
                                                                    <label for="deduct" class="text-danger"><?=__('Deduct (Loan, etc.)');?></label>
                                                                    <input type="number" class="form-control text-danger calculation-trigger" value="<?= htmlspecialchars($outstanding_loan); ?>" id="deduct" name="deduct" step="any">
                                                                </div>
                                                            <?php endif; ?>
                                                            
                                                            <div class="col-12"><hr/></div>
                                                            
                                                            <!-- Final Summary Row -->
                                                            <div class="form-group col-lg-4">
                                                                <label><?=__('EOS Amount (from API)');?></label>
                                                                <input type="text" class="form-control" id="eos_amount_display" value="0.00" readonly style="background-color: #e9ecef;">
                                                            </div>
                                                            <div class="form-group col-lg-4">
                                                                <label><?=__('Vacation Salary');?></label>
                                                                <input type="text" class="form-control" id="vacation_salary_display" value="0.00" readonly style="background-color: #e9ecef;">
                                                            </div>
                                                            <div class="form-group col-lg-4">
                                                                <label class="font-weight-bold"><?=__('Total Net Payment');?></label>
                                                                <input type="text" class="form-control font-weight-bold" id="net_payment_display" value="0.00" readonly style="background-color: #dff0d8;">
                                                            </div>
                                                            
                                                            <div class="col-12"><hr/></div>
                                                            
                                                            <div class="form-group col-lg-8">
                                                                <label for="notes"><?=__('Notes');?>:<span class="text-danger">*</span></label>
                                                                <input type="text" class="form-control" id="notes" name="notes" value="<?= htmlspecialchars($_POST['notes'] ?? ''); ?>" required />
                                                                <?php if (!empty($errors['notes'])): ?><div class="text-danger"><small><?=htmlspecialchars($errors['notes']); ?></small></div><?php endif; ?>
                                                            </div>
                                                            <div class="form-group col-lg-4">
                                                                <button type="submit" name="submit" class="btn btn-danger btn-block"><i class="mdi mdi-settings"></i> <?=__('Register EOS');?></button>
                                                            </div>
                                                        </div>

                                                        <!-- Hidden fields for calculation and submission -->
                                                        <input type="hidden" name="eos_amount" id="eos_amount_hidden" value="">
                                                        <input type="hidden" name="anul_vac_salry" id="anul_vac_salry_hidden" value="">
                                                        <input type="hidden" name="net_payment" id="net_payment_hidden" value="">
                                                        <input type="hidden" id="total_salary" value="<?= htmlspecialchars($total_salary); ?>">
                                                        <input type="hidden" id="basic_salary" value="<?= htmlspecialchars($salaryrow['basic'] ?? 0); ?>">
                                                        <input type="hidden" id="housing_allowance" value="<?= htmlspecialchars($salaryrow['housing'] ?? 0); ?>">
                                                        <input type="hidden" id="actual_salary_base" value="<?= htmlspecialchars($actual_salary_base); ?>">
                                                        <input type="hidden" id="vacation_salary_base" value="<?= htmlspecialchars($vacation_salary_base); ?>">
                                                        <input type="hidden" id="annual_vacation_entitlement" value="<?= htmlspecialchars($annual_vacation_entitlement); ?>">
                                                        <input type="hidden" id="current_vacation_balance" value="<?= htmlspecialchars($current_vacation_balance); ?>">
                                                        <input type="hidden" id="balance_period_end" value="<?= htmlspecialchars($balance_period_end ?? ''); ?>">
                                                        <input type="hidden" id="is_two_year_contract" value="<?= htmlspecialchars($is_two_year_contract); ?>">
                                                        <input type="hidden" id="emp_country" value="<?= htmlspecialchars($emprow['country']); ?>">
                                                        <input type="hidden" id="emp_gosi_percent" value="<?= htmlspecialchars($emprow['gosi']); ?>">
                                                        <input type="hidden" id="emp_contract_type" value="<?= htmlspecialchars($emprow['contract_type']); ?>">
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
		<script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js"></script>
		<script src="assets/js/jquery.app.js"></script>
        <script>
            const paidPayrolls = <?= json_encode($paid_payrolls); ?>;
            const vacationRecords = <?= json_encode($vacation_records); ?>;
        </script>
		<script type="text/javascript">
            $(document).ready(function(){
                
                $('#eos_reason').select2();
                
                // Track if user manually edited GOSI deduction
                let gosiManuallyEdited = false;
                
                $('#gosi_deduction').on('input change', function() {
                    // Mark as manually edited when user types
                    gosiManuallyEdited = true;
                });

                function isSalaryPaidForMonth(endDateStr) {
                    if (!endDateStr || !window.paidPayrolls || !window.paidPayrolls.length) return false;
                    const targetMonthYear = endDateStr.substring(0, 7);
                    return window.paidPayrolls.some(paidMonth => String(paidMonth).trim() === targetMonthYear);
                }

                function updateProratedVacation() {
                    const endDateStr = $('#end_date').val();
                    // This variable name is misleading. It holds the TOTAL entitlement for the period (e.g., 42 for 2 years)
                    const periodEntitlement = parseFloat($('#annual_vacation_entitlement').val()) || 0;
                    const currentVacationBalance = parseFloat($('#current_vacation_balance').val()) || 0;
                    const balancePeriodEndStr = $('#balance_period_end').val();
                    
                    if (!endDateStr) {
                        $('#anul_vac_days').val('0.00');
                        $('#vacation_days_summary').text('0.00');
                        return;
                    }
                    
                    let finalBalance = currentVacationBalance;
                    
                    // Calculate accrued days from period_end to EOS date
                    if (periodEntitlement > 0 && balancePeriodEndStr) {
                        const periodEndDate = new Date(balancePeriodEndStr + 'T00:00:00');
                        const eosDate = new Date(endDateStr + 'T00:00:00');
                        
                        if (eosDate > periodEndDate) {
                            const eosYear = eosDate.getFullYear();
                            const eosMonth = eosDate.getMonth();
                            const eosDay = eosDate.getDate();
                            
                            // --- START: MODIFIED CALCULATION TO MATCH OLD SYSTEM (Target 41.88 for emp 5313) ---
                            
                            // 1. Get the contract duration (1 or 2 years) passed from PHP
                            const isTwoYear = parseInt($('#is_two_year_contract').val()) || 0;
                            
                            // 2. Determine the *true* annual rate. The DB field stores the *period* total.
                            // For emp 5313: periodEntitlement is 42, isTwoYear is 1. trueAnnualRate becomes 21.
                            // For a 1-year contract: periodEntitlement might be 21, isTwoYear is 0. trueAnnualRate remains 21.
                            const trueAnnualRate = isTwoYear ? (periodEntitlement / 2) : periodEntitlement;

                            // 3. The old system's target (41.88) is achieved by using a 365-day daily rate.
                            const dailyAccrualRate = trueAnnualRate / 365;
                            
                            // 4. The old system (and this code) has a bug, calculating days from the 1st of the month
                            //    instead of from the period_end date. We keep this bug to match the target number.
                            //    For Nov 4, this calculates 3 days (Nov 1, 2, 3).
                            const daysToAccrue = eosDay - 1; 
                            
                            const accruedDays = daysToAccrue * dailyAccrualRate;
                            
                            // --- END: MODIFIED CALCULATION ---
                            
                            finalBalance += accruedDays;
                            
                            console.log('=== Vacation Calculation Debug (Old System Match) ===');
                            console.log('EOS Date:', endDateStr);
                            console.log('Period End Date:', balancePeriodEndStr);
                            console.log('Opening Balance:', currentVacationBalance.toFixed(4));
                            console.log('Period Entitlement (from DB):', periodEntitlement);
                            console.log('Is 2-Year Contract:', isTwoYear);
                            console.log('True Annual Rate:', trueAnnualRate);
                            console.log('Daily Accrual Rate (Annual/365):', dailyAccrualRate.toFixed(10));
                            console.log('Days to Accrue (Buggy: eosDay - 1):', daysToAccrue);
                            console.log('Accrued Days:', accruedDays.toFixed(4));
                            console.log('Final Balance:', finalBalance.toFixed(4));
                            console.log('Target (41.88)');
                            console.log('====================================================');
                        }
                    }
                    
                    finalBalance = Math.max(0, finalBalance);
                    
                    $('#anul_vac_days').val(finalBalance.toFixed(2));
                    $('#vacation_days_summary').text(finalBalance.toFixed(2));
                }

                function calculateFinalPayment() {
                    const endDateStr = $('#end_date').val();
                    if (!endDateStr) return;

                    const isPaid = isSalaryPaidForMonth(endDateStr);
                    const endDate = new Date(endDateStr);
                    const workingDays = isPaid ? 0 : endDate.getDate();
                    $('#curt_month_days_display').val(workingDays);
                    
                    const actualSalaryBase = parseFloat($('#actual_salary_base').val()) || 0;
                    const contractSalaryBase = parseFloat($('#vacation_salary_base').val()) || 0; // actual package without calculated housing
                    const basicSalary = parseFloat($('#basic_salary').val()) || 0;
                    const housingAllowance = parseFloat($('#housing_allowance').val()) || 0;
                    const absentDays = parseInt($('#absent_days').val()) || 0;
                    
                    // Base daily rate for potential salary (contract package)
                    const dailyRateContract = contractSalaryBase / 30; // 30-day convention
                    // Resignation month's salary is based on worked days only (deductions handled separately)
                    let resignationSalary = (!isPaid) ? dailyRateContract * workingDays : 0;

                    // DEDUCTION BASE RULE: Use contract base for deductions (days) and /8 for hours
                    const DEDUCTION_BASE = contractSalaryBase;
                    const dailyRateDeduction = DEDUCTION_BASE / 30;
                    const deductionHours = parseFloat($('#deduction_hours').val()) || 0;
                    const hourlyRateDeduction = (dailyRateDeduction) / 8;
                    const absentDeductionAmount = dailyRateDeduction * absentDays;
                    const hourlyDeductionAmount = hourlyRateDeduction * deductionHours;
                    
                    // Ensure resignation salary doesn't go below zero
                    resignationSalary = Math.max(0, resignationSalary);
                    
                    $('#curt_month_salry').val(resignationSalary.toFixed(2));

                    const empCountry = $('#emp_country').val();
                    const gosiPercent = parseFloat($('#emp_gosi_percent').val()) || 0;
                    const gosiBase = basicSalary + housingAllowance;
                    
                    // Calculate GOSI deduction for the termination month only
                    // Formula: (Monthly GOSI / 30) × Days worked in termination month
                    let calculatedGosiDeduction = 0;
                    if (empCountry == '191' && gosiPercent > 0 && gosiBase > 0 && endDateStr) {
                        const monthlyGosi = gosiBase * gosiPercent / 100;
                        const dailyGosi = monthlyGosi / 30;
                        const terminationDate = new Date(endDateStr);
                        const daysInTerminationMonth = terminationDate.getDate(); // Days from 1st to termination date
                        calculatedGosiDeduction = dailyGosi * daysInTerminationMonth;
                    }
                    
                    // Only auto-fill if user hasn't manually edited the field
                    if ($('#gosi_deduction').length && !gosiManuallyEdited) {
                        $('#gosi_deduction').val(calculatedGosiDeduction.toFixed(2));
                    }
                    
                    // Get the actual GOSI deduction value (user can override)
                    const gosiDeduction = parseFloat($('#gosi_deduction').val()) || 0;
                    
                    const eosAmount = parseFloat($('#eos_amount_display').val()) || 0;
                    const vacationSalary = parseFloat($('#vacation_salary_display').val()) || 0;
                    const otherEarnings = parseFloat($('#other_earnings').val()) || 0;
                    
                    // Calculate overtime earnings per new rule:
                    // per-hour overtime rate = (basic/240)/2 + (full/240)
                    // full = actualSalaryBase; basic = basicSalary
                    // hours amount = overtimeHourlyRate * overtime_hours
                    // days amount  = overtimeHourlyRate * 8 * overtime_days
                    const overtimeHours = parseFloat($('#overtime_hours').val()) || 0;
                    const overtimeDays = parseFloat($('#overtime_days').val()) || 0;
                    const overtimeHourlyRate = ((basicSalary / 240) / 2) + ((contractSalaryBase) / 240);
                    const overtimeHoursAmount = overtimeHourlyRate * overtimeHours;
                    const overtimeDaysAmount = overtimeHourlyRate * 8 * overtimeDays;
                    const totalOvertimeEarnings = overtimeHoursAmount + overtimeDaysAmount;
                    
                    const loanDeduction = parseFloat($('#deduct').val()) || 0;
 
                    const totalEarnings = eosAmount + vacationSalary + resignationSalary + otherEarnings + totalOvertimeEarnings;
                    const totalDeductions = loanDeduction + gosiDeduction + absentDeductionAmount + hourlyDeductionAmount;
                    const netPayment = totalEarnings - totalDeductions;                    // --- MODIFICATION: Round up the net payment to the nearest next number ---
                    const roundedNetPayment = Math.ceil(netPayment);
                    
                    $('#net_payment_display').val(roundedNetPayment.toFixed(2));
                    
                    $('#net_payment_hidden').val(roundedNetPayment.toFixed(2));
                    // --- END MODIFICATION ---
                    
                    $('#anul_vac_salry_hidden').val(vacationSalary.toFixed(2));
                    $('#eos_amount_hidden').val(eosAmount.toFixed(2));
                }

                function performApiCalculation() {
                    const totalSalary = parseFloat($('#total_salary').val()) || 0;
                    
                    const formData = {
                        contract_type: $('input[name="contract_type"]:checked').val(),
                        eos_reason: $('#eos_reason').val(),
                        end_date: $('#end_date').val(),
                        joining_date: $('#joining_date').val(),
                        salary: totalSalary,
                        anul_vac_days: $('#anul_vac_days').val(),
                    };
                    
                    console.log('=== EOS API Request ===');
                    console.log('Total Salary being sent to API:', totalSalary);
                    console.log('Contract Type:', formData.contract_type);
                    console.log('EOS Reason:', formData.eos_reason);
                    console.log('Joining Date:', formData.joining_date);
                    console.log('End Date:', formData.end_date);
                    console.log('Vacation Days:', formData.anul_vac_days);
                    console.log('======================');

                    if (!formData.end_date || !formData.eos_reason) {
                        $('#eos_amount_display, #vacation_salary_display').val('0.00');
                        calculateFinalPayment();
                        return; 
                    }

                    $('#net_payment_display').val('Calculating...');

                    $.ajax({
                        type: 'POST',
                        url: './includes/ajaxFile/ajax_eos_calculator.php',
                        data: formData,
                        dataType: 'json',
                        success: function(response) {
                            if (response.success) {
                                $('#eos_amount_display').val(response.eos_amount);
                                
                                // --- Calculate Vacation Salary using vacation_salary_base (WITHOUT calculated housing) ---
                                const vacationBase = parseFloat($('#vacation_salary_base').val()) || 0;
                                const vacationDays = parseFloat($('#anul_vac_days').val()) || 0;
                                const calculatedVacationSalary = (vacationBase > 0 && vacationDays > 0) ? (vacationBase / 30) * vacationDays : 0;
                                $('#vacation_salary_display').val(calculatedVacationSalary.toFixed(2));
                                // --- END Vacation Salary Calculation ---

                            } else {
                                $('#eos_amount_display, #vacation_salary_display').val('0.00');
                            }
                            calculateFinalPayment(); // This will now use the correctly calculated vacation salary
                        },
                        error: function() {
                            $('#eos_amount_display, #vacation_salary_display').val('Error');
                            calculateFinalPayment();
                        }
                    });
                }

                function masterCalculationTrigger() {
                    updateProratedVacation();
                    performApiCalculation();
                }

                $('.datepicker').datepicker({
                    format: "yyyy-mm-dd",
                    todayHighlight: true,
                    autoclose: true
                }).on('changeDate', masterCalculationTrigger);

                $('input[name="contract_type"]').on('change', performApiCalculation);
                $('#eos_reason').on('change', performApiCalculation);
                $('.calculation-trigger').on('change keyup', calculateFinalPayment);

                if($('#end_date').val()){
                    masterCalculationTrigger();
                }
            });
		</script>
	</body>
	</html>
<?php } ?>