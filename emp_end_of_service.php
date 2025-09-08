<?php
/****************************************************************
 * MODIFICATION SUMMARY (005-emp_end_of_service.php):
 * - Removed database sync for EOS reasons. The dropdown is now populated directly from the live API call every time.
 * - Updated the form submission logic to find the selected reason's English and Arabic text from the API response.
 * - The `leaving_reason` and a new `leaving_reason_ar` field in the `emp_eos` table are now correctly populated upon submission.
 * - The `eos_reason` field will continue to store the reason code from the API.
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
            $curt_month_salry = filter_input(INPUT_POST, 'curt_month_salry', FILTER_VALIDATE_FLOAT, ['options' => ['default' => 0]]);
            $curt_month_days = 0;
            if (!empty($endDateStr)) {
                $endDateTimeForDays = new DateTime($endDateStr);
                $curt_month_days = (int)$endDateTimeForDays->format('d');
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
                                                    <p><strong><?=__('Total Remaining Vacation Days');?>:</strong><br><?= htmlspecialchars(number_format($emprow['total_remaining_leave'], 2)); ?> <?=__('days');?></p>
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
                                                    <strong><?=__('Action Required:');?></strong> <?=__('This employee has ') . $assigned_assets_count . __(' outstanding asset(s). Please ensure all assets are returned before proceeding with termination.');?>
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
                                                                <select id="eos_reason" required class="form-control calc-trigger" name="eos_reason" <?php if(empty($filteredReasons) && empty($general_error_message)) echo 'disabled';?>>
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
                                                                <input type="text" name="end_date" class="form-control datepicker calc-trigger" id="end_date" value="<?=htmlspecialchars($endDateStr); ?>" required autocomplete="off">
                                                                <?php if (!empty($errors['end_date'])): ?><div class="text-danger"><small><?=htmlspecialchars($errors['end_date']); ?></small></div><?php endif; ?>
                                                            </div>
                                                            <div class="form-group col-lg-3">
                                                                <label for="anul_vac_days"><?=__('Annual vacation days');?></label>
                                                                <input type="number" class="form-control calc-trigger" value="<?= htmlspecialchars($_POST['anul_vac_days'] ?? $emprow['total_remaining_leave']); ?>" id="anul_vac_days" name="anul_vac_days" step="any">
                                                            </div>
                                                            <div class="form-group col-lg-2">
                                                                <label for="curt_month_days_display"><?=__('Working Days');?></label>
                                                                <input type="text" class="form-control" id="curt_month_days_display" readonly style="background-color: #e9ecef;">
                                                            </div>
                                                            <div class="form-group col-lg-3">
                                                                <label for="curt_month_salry"><?=__('Resignation Month Salary');?></label>
                                                                <input type="text" class="form-control calc-trigger" value="<?= htmlspecialchars($_POST['curt_month_salry'] ?? '0.00'); ?>" id="curt_month_salry" name="curt_month_salry" readonly>
                                                            </div>
                                                            <div class="form-group col-lg-4">
                                                                <label for="deduct" class="text-danger"><?=__('Deduct (Loan, etc.)');?></label>
                                                                <input type="number" class="form-control text-danger calc-trigger" value="<?= htmlspecialchars($_POST['deduct'] ?? $outstanding_loan); ?>" id="deduct" name="deduct" step="any">
                                                            </div>
                                                            <div class="col-12"><hr/></div>
                                                            <div class="form-group col-lg-4">
                                                                <label><?=__('EOS Amount (from API)');?></label>
                                                                <input type="text" class="form-control" id="eos_amount_display" readonly style="background-color: #e9ecef;">
                                                            </div>
                                                            <div class="form-group col-lg-4">
                                                                <label><?=__('Vacation Salary');?></label>
                                                                <input type="text" class="form-control" id="vacation_salary_display" readonly style="background-color: #e9ecef;">
                                                            </div>
                                                            <div class="form-group col-lg-4">
                                                                <label class="font-weight-bold"><?=__('Total Net Payment');?></label>
                                                                <input type="text" class="form-control font-weight-bold" id="net_payment_display" readonly style="background-color: #dff0d8;">
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
                                                        <input type="hidden" name="eos_amount" id="eos_amount_hidden">
                                                        <input type="hidden" name="anul_vac_salry" id="anul_vac_salry_hidden">
                                                        <input type="hidden" name="net_payment" id="net_payment_hidden">
                                                        <input type="hidden" id="salary" value="<?= htmlspecialchars($emprow['salary']); ?>">
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
		<script type="text/javascript">
            $(document).ready(function(){

                $("#eos_reason").select2();
                
                function calculateProratedSalary() {
                    const endDateStr = $('#end_date').val();
                    const basicSalary = parseFloat($('#salary').val()) || 0;
                    
                    if (!endDateStr || basicSalary <= 0) {
                        $('#curt_month_salry').val('0.00');
                        $('#curt_month_days_display').val('');
                        return;
                    };

                    const parts = endDateStr.split('-');
                    if (parts.length !== 3) return;
                    const year = parseInt(parts[0], 10);
                    const month = parseInt(parts[1], 10);
                    const day = parseInt(parts[2], 10);

                    if (isNaN(year) || isNaN(month) || isNaN(day)) return;

                    const daysInMonth = new Date(year, month, 0).getDate();
                    const workedDays = day;
                    $('#curt_month_days_display').val(workedDays);

                    if (daysInMonth > 0) {
                        const proratedSalary = (basicSalary / daysInMonth) * workedDays;
                        $('#curt_month_salry').val(proratedSalary.toFixed(2));
                    }
                }

                $('.datepicker').datepicker({
                    format: "yyyy-mm-dd",
                    todayHighlight: true,
                    autoclose: true
                }).on('changeDate', function(e) {
                    calculateProratedSalary();
                    performCalculation();
                });

                $('input[name="contract_type"]').on('change', function() {
                    $('#calculatorForm').submit();
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

                    $('#net_payment_display').val('Calculating...');

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
                                $('#net_payment_display').val('Error');
                            }
                        },
                        error: function() {
                            $('#net_payment_display').val('Error');
                        }
                    });
                }

                $('.calc-trigger').on('change keyup', function() {
                    performCalculation();
                });
                
                // Trigger calculation on page load if date is already set
                if($('#end_date').val()){
                    calculateProratedSalary();
                    performCalculation();
                }
            });
		</script>
	</body>
	</html>
<?php } ?>
