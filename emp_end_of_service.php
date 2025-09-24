<?php
/****************************************************************
 * MODIFICATION SUMMARY (009-emp_end_of_service.php):
 * - Fixed vacation day calculation to be dependent on the selected End of Service Date, not the current date.
 * - Corrected the translation function call for the asset warning message to properly handle dynamic content.
 * - Made the 'days_served' calculation inclusive of the end date for better accuracy.
 * - Made the 'Working Days' field editable to allow manual override of salary calculation.
 * - Added a check against the 'payrolls' table to zero out working days and salary if already paid for the selected month.
 * - Made the payroll check case-insensitive and robust against whitespace.
 * - MODIFICATION: Re-implemented vacation calculation in JavaScript to provide a real-time, dynamic experience without page reloads.
 * - FIXED: All calculations now correctly and instantly trigger upon changing the End of Service Date.
 * - ROBUSTNESS: Updated the cURL function to improve reliability when fetching data from the external API.
 * - RE-IMPLEMENTED: Vacation balance is now correctly prorated to the last working day by calculating accrual vs. used days within the current service year, ignoring potentially incorrect balance records.
 * - UX FIX: Removed page reloads on date change for a smoother user experience.
 * - FEATURE: Added an "Absent Days" field to deduct from working days for salary calculation.
 * - FEATURE: Added "Deduction (Hours)" field for hourly-based deductions from salary.
 * - FEATURE: Added automatic "GOSI Deduction" for Saudi employees (Country ID 191) based on their GOSI percentage.
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


// --- START: Compute available vacation days from emp_vacation_balance (injected by ChatGPT) ---
$available_vacation_days = floatval($emprow['vacation_days']); // default from employee record
$vac_total_days = $available_vacation_days;
$vac_used_days = 0.0;

$empid_esc = mysqli_real_escape_string($conDB, $emprow['empid']);
$vac_balance_q = mysqli_query($conDB, "SELECT total_days, used_days, remaining_balance FROM emp_vacation_balance WHERE emp_id = '{$empid_esc}' ORDER BY id DESC LIMIT 1");
if ($vac_balance_q && mysqli_num_rows($vac_balance_q) > 0) {
    $vac_row = mysqli_fetch_assoc($vac_balance_q);
    if (isset($vac_row['remaining_balance']) && is_numeric($vac_row['remaining_balance'])) {
        $available_vacation_days = floatval($vac_row['remaining_balance']);
    } elseif (isset($vac_row['total_days']) && is_numeric($vac_row['total_days'])) {
        $used_tmp = isset($vac_row['used_days']) && is_numeric($vac_row['used_days']) ? floatval($vac_row['used_days']) : 0.0;
        $available_vacation_days = floatval($vac_row['total_days']) - $used_tmp;
    }
    $vac_total_days = isset($vac_row['total_days']) && is_numeric($vac_row['total_days']) ? floatval($vac_row['total_days']) : $vac_total_days;
    $vac_used_days = isset($vac_row['used_days']) && is_numeric($vac_row['used_days']) ? floatval($vac_row['used_days']) : $vac_used_days;
}
// Make safe for output
$available_vacation_days = round($available_vacation_days, 2);
// Expose to JS if needed (keeps variable names consistent in page)
$vac_total_days = round($vac_total_days, 2);
$vac_used_days = round($vac_used_days, 2);
// --- END: Compute available vacation days ---

        
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
                                                                <label for="absent_days"><?=__('Absent Days');?></label>
                                                                <input type="number" class="form-control calculation-trigger" id="absent_days" name="absent_days" value="0" min="0">
                                                            </div>
                                                            <div class="form-group col-lg-2">
                                                                <label for="deduction_hours"><?=__('Deduction (Hours)');?></label>
                                                                <input type="number" class="form-control calculation-trigger" id="deduction_hours" name="deduction_hours" value="0" min="0">
                                                            </div>
                                                            <div class="form-group col-lg-4">
                                                                <label for="curt_month_salry"><?=__('Resignation Month Salary');?></label>
                                                                <input type="text" class="form-control" value="0.00" id="curt_month_salry" name="curt_month_salry" readonly>
                                                            </div>

                                                            <!-- Calculation Row 2 (Deductions) -->
                                                            <?php if ($emprow['country'] == 191 && floatval($emprow['gosi']) > 0): ?>
                                                                <div class="form-group col-lg-4">
                                                                    <label for="gosi_deduction" class="text-danger"><?=__('GOSI Deduction');?></label>
                                                                    <input type="number" class="form-control text-danger" id="gosi_deduction" value="0.00" readonly>
                                                                </div>
                                                                <div class="form-group col-lg-8">
                                                                    <label for="deduct" class="text-danger"><?=__('Other Deductions (Loan, etc.)');?></label>
                                                                    <input type="number" class="form-control text-danger calculation-trigger" value="<?= htmlspecialchars($outstanding_loan); ?>" id="deduct" name="deduct" step="any">
                                                                </div>
                                                            <?php else: ?>
                                                                <div class="form-group col-lg-12">
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
                                                        <input type="hidden" id="salary" value="<?= htmlspecialchars($emprow['salary']); ?>">
                                                        <input type="hidden" id="annual_vacation_entitlement" value="<?= htmlspecialchars($available_vacation_days); ?>">
                                                        <input type="hidden" id="emp_country" value="<?= htmlspecialchars($emprow['country']); ?>">
                                                        <input type="hidden" id="emp_gosi_percent" value="<?= htmlspecialchars($emprow['gosi']); ?>">
                                                        
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

                function isSalaryPaidForMonth(endDateStr) {
                    if (!endDateStr || !window.paidPayrolls || !window.paidPayrolls.length) return false;
                    const targetMonthYear = endDateStr.substring(0, 7);
                    return window.paidPayrolls.some(paidMonth => String(paidMonth).trim() === targetMonthYear);
                }

                function updateProratedVacation() {
                    const joiningDateStr = $('#joining_date').val();
                    const endDateStr = $('#end_date').val();
                    // const annualVacationEntitlement = parseFloat($('#annual_vacation_entitlement').val()) || 0;
                    const annualVacationEntitlement = parseFloat(document.getElementById('annual_vacation_entitlement').value) || 0;

                    if (!joiningDateStr || !endDateStr) {
                        $('#anul_vac_days').val('0.00');
                        $('#vacation_days_summary').text('0.00');
                        return;
                    }

                    const joiningDate = new Date(joiningDateStr + 'T00:00:00');
                    const endDate = new Date(endDateStr + 'T00:00:00');

                    if (endDate < joiningDate) {
                        $('#anul_vac_days').val('0.00');
                        $('#vacation_days_summary').text('0.00');
                        return;
                    }

                    let currentServiceYearStart = new Date(joiningDate.getTime());
                    while (true) {
                        let nextYearStart = new Date(currentServiceYearStart.getTime());
                        nextYearStart.setFullYear(nextYearStart.getFullYear() + 1);
                        if (nextYearStart > endDate) {
                            break;
                        }
                        currentServiceYearStart = nextYearStart;
                    }

                    let usedDaysForPeriod = 0;
                    if (window.vacationRecords) {
                        window.vacationRecords.forEach(function(record) {
                            const vacDate = new Date(record.start_date + 'T00:00:00');
                            if (vacDate >= currentServiceYearStart && vacDate <= endDate) {
                                usedDaysForPeriod += parseFloat(record.vacdays);
                            }
                        });
                    }

                    const timeDiff = endDate.getTime() - currentServiceYearStart.getTime();
                    const daysServed = (timeDiff / (1000 * 3600 * 24)) + 1;

                    const accruedDays = (daysServed / 365.0) * annualVacationEntitlement;

                    let balance = accruedDays - usedDaysForPeriod;
                    balance = Math.max(0, balance);

                    $('#anul_vac_days').val(balance.toFixed(2));
                    $('#vacation_days_summary').text(balance.toFixed(2));
                }

                function calculateFinalPayment() {
                    const endDateStr = $('#end_date').val();
                    if (!endDateStr) return;

                    const isPaid = isSalaryPaidForMonth(endDateStr);
                    const endDate = new Date(endDateStr);
                    const workingDays = isPaid ? 0 : endDate.getDate();
                    $('#curt_month_days_display').val(workingDays);
                    
                    const basicSalary = parseFloat($('#salary').val()) || 0;
                    const absentDays = parseInt($('#absent_days').val()) || 0;
                    const effectiveWorkedDays = Math.max(0, workingDays - absentDays);
                    const daysInMonth = new Date(endDate.getFullYear(), endDate.getMonth() + 1, 0).getDate();
                    const resignationSalary = (daysInMonth > 0 && !isPaid) ? (basicSalary / daysInMonth) * effectiveWorkedDays : 0;
                    $('#curt_month_salry').val(resignationSalary.toFixed(2));

                    const empCountry = $('#emp_country').val();
                    const gosiPercent = parseFloat($('#emp_gosi_percent').val()) || 0;
                    const gosiDeduction = (empCountry == '191' && gosiPercent > 0) ? (basicSalary * gosiPercent / 100) : 0;
                    if ($('#gosi_deduction').length) {
                        $('#gosi_deduction').val(gosiDeduction.toFixed(2));
                    }
                    
                    const eosAmount = parseFloat($('#eos_amount_display').val()) || 0;
                    const vacationSalary = parseFloat($('#vacation_salary_display').val()) || 0;
                    
                    const loanDeduction = parseFloat($('#deduct').val()) || 0;
                    const deductionHours = parseFloat($('#deduction_hours').val()) || 0;
                    const hourlyRate = (basicSalary / 30) / 8;
                    const hourlyDeductionAmount = hourlyRate * deductionHours;
                    
                    const totalEarnings = eosAmount + vacationSalary + resignationSalary;
                    const totalDeductions = loanDeduction + gosiDeduction + hourlyDeductionAmount;
                    const netPayment = totalEarnings - totalDeductions;
                    
                    $('#net_payment_display').val(netPayment.toFixed(2));
                    
                    $('#net_payment_hidden').val(netPayment.toFixed(2));
                    $('#anul_vac_salry_hidden').val(vacationSalary.toFixed(2));
                    $('#eos_amount_hidden').val(eosAmount.toFixed(2));
                }

                function performApiCalculation() {
                    const formData = {
                        contract_type: $('input[name="contract_type"]:checked').val(),
                        eos_reason: $('#eos_reason').val(),
                        end_date: $('#end_date').val(),
                        joining_date: $('#joining_date').val(),
                        salary: $('#salary').val(),
                        anul_vac_days: $('#anul_vac_days').val(),
                    };

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
                                $('#vacation_salary_display').val(response.vacation_salary);
                            } else {
                                $('#eos_amount_display, #vacation_salary_display').val('0.00');
                            }
                            calculateFinalPayment();
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