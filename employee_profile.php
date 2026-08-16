<?php
/*******************************************************************************************************************
 * MODIFICATION SUMMARY (006-employee_profile.php):
 * 1. MODERNIZED LAYOUT: The report has been redesigned with a clean, single-column, card-based layout to improve
 * visual organization and readability. Each major section is now in its own distinct card.
 * 2. PROFILE HEADER: A new profile header section has been added at the top, featuring the employee's photo, name,
 * and job title, followed by a two-column summary of key personal and employment details.
 * 3. ENHANCED FONT SIZE: The base font size has been increased again for better legibility on-screen and in print.
 * 4. LOGICAL GROUPING: Information is grouped into logical cards: "Personal & Employment", "Financial Details",
 * "Assigned Assets", "Loan History", "Vacation History", etc., making the report easier to navigate.
 * 5. PRINT OPTIMIZATION: The single-column layout is more robust for printing and will flow more naturally if it
 * needs to span more than one page.
 *******************************************************************************************************************/
	require_once __DIR__ . '/includes/db.php';
	require_once __DIR__ . '/includes/session_check.php';
    require_once __DIR__ . '/includes/helper_functions.php';
    require_once __DIR__ . '/includes/special_access_helper.php';
	$query = mysqli_query($conDB, "SELECT * FROM `admin_login` WHERE `id_iqama`='".$username."'");
	if(mysqli_num_rows($query) == 1){
	include("./includes/avatar_select.php");
	
	include("./includes/Hijri_GregorianConvert.php");
	$DateConv=new Hijri_GregorianConvert;
	$format="YYYY/MM/DD";
	
	require("./includes/emp_query.php");

	if(mysqli_num_rows($get_emp_data) !== 0){
		$allRecords = mysqli_fetch_all($get_emp_data, MYSQLI_ASSOC);
		foreach ($allRecords as $rec) {
			$emprow = $rec;
		}
		$salary_get = str_replace(',', '', ($emprow['basic'] + $emprow['housing'] + $emprow['transport'] + $emprow["food"] + $emprow["misc"] + $emprow["cashier"] + $emprow["fuel"] + $emprow["tel"] + $emprow["other"] + $emprow["guard"]));

		// Insurance No / Expiry / Class are yearly-renewed - fetch the current active record
		// from employee_medical_insurance (see view_employee.php's "Medical Insurance" section).
		$current_medical_insurance = null;
		$mi_stmt = mysqli_prepare($conDB, "SELECT insurance_no, medical_expiry, medical_class FROM `employee_medical_insurance` WHERE `emp_id` = ? AND `status` = 'active' LIMIT 1");
		mysqli_stmt_bind_param($mi_stmt, "s", $emprow['empid']);
		mysqli_stmt_execute($mi_stmt);
		$current_medical_insurance = mysqli_fetch_assoc(mysqli_stmt_get_result($mi_stmt)) ?: null;
		mysqli_stmt_close($mi_stmt);
		$current_medical_class = $current_medical_insurance['medical_class'] ?? null;

		$canViewSalary = (
			($is_system_admin ?? false) || ($isHR ?? false) || ($isDeptHr ?? false)
			|| user_has_special_access($conDB, $empid ?? '', 'view_employee_salary_value', $user_role ?? '', $user_type ?? '', $is_system_admin ?? false)
		);
		$hours_in_day   = 24;
		$minutes_in_hour= 60;
		$seconds_in_mins= 60;
		$birth_date     = new DateTime($emprow["dob"]);
		$current_date   = new DateTime();
		$diff           = $birth_date->diff($current_date);
		$years	   		= $diff->y . " " . __('years');
		$vacyear_get = preg_replace("/[^0-9]/","",$emprow["period"]);
	}	
	
    // New queries for the full report
    // Query for Assigned Car
    $car_info = null;
    if (!empty($emprow["car_id"])) {
        $car_info = car_get_info($emprow["car_id"]);
    }

    // Query for Assigned Assets
    $assets_query = mysqli_query($conDB, "SELECT ea.*, a.name as asset_name 
                                        FROM `employee_assets` ea 
                                        JOIN `assets` a ON ea.asset_id = a.id 
                                        WHERE ea.emp_id = '{$emprow['empid']}' AND ea.status = 'Assigned'
                                        ORDER BY ea.assigned_date DESC");
    $assigned_assets = mysqli_fetch_all($assets_query, MYSQLI_ASSOC);

    // Query for Loan History
    $loans_query = mysqli_query($conDB, "SELECT * FROM `emp_loan` WHERE `emp_id` = '{$emprow['empid']}' ORDER BY `id` DESC");
    $loan_history = mysqli_fetch_all($loans_query, MYSQLI_ASSOC);

    // Query for Vacation History
    $vacations_query = mysqli_query($conDB, "SELECT * FROM `emp_vacation` WHERE `emp_id`='" . $emprow['empid'] . "' ORDER BY `id` DESC");
    $vacation_history = mysqli_fetch_all($vacations_query, MYSQLI_ASSOC);

    // Query for Documents
    $documents_query = mysqli_query($conDB, "SELECT * FROM `emp_docu` WHERE `emp_id`='" . $emprow['empid'] . "' ORDER BY `id` DESC ");
    $employee_documents = mysqli_fetch_all($documents_query, MYSQLI_ASSOC);

    // Query for Notes
    $notes_query = mysqli_query($conDB, "SELECT n.* FROM `emp_notice` n WHERE n.emp_id = '{$emprow['empid']}' AND n.is_deleted = 0 ORDER BY n.id DESC");
    $employee_notes = mysqli_fetch_all($notes_query, MYSQLI_ASSOC);

    // Query for Supervisor Info
    $supervisor_query = mysqli_query($conDB, "SELECT `emp_id`, `name`, `actual_job` FROM `employees` WHERE `emp_id` = '{$emprow['supervisor_id']}' LIMIT 1");
    $supervisor_info = mysqli_fetch_assoc($supervisor_query);
    
    // Query for End of Service Info
    $eos_query = mysqli_query($conDB, "SELECT * FROM `emp_eos` WHERE `emp_id` = '{$emprow['empid']}' LIMIT 1");
    $end_of_service = mysqli_fetch_assoc($eos_query);

    // Query for Vacation Balance
    $balance_query = mysqli_query($conDB, "SELECT * FROM `emp_vacation_balance` WHERE `emp_id` = '{$emprow['empid']}' ORDER BY `last_updated` DESC LIMIT 1");
    $vacation_balance = mysqli_fetch_assoc($balance_query);

	} else {
		//when the id not equals id show database
		header("Location: ./reg_employee.php");
	}

?>
<!doctype html> 
<html lang="<?= $current_lang ?? 'en' ?>" <?= ($is_rtl ?? false) ? 'dir="rtl"' : '' ?>>

    <head>
        <meta charset="utf-8" />
        <title><?=$site_title ?> - <?=__('all_employees')?></title>
        <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
        <meta content="Anees Afzal" name="author" />
        <meta http-equiv="X-UA-Compatible" content="IE=edge" />

        <!-- App favicon -->
        <link rel="shortcut icon" href="<?=get_setting($conDB, 'favicon')?>">
        
        <!-- App css -->
        <link href="assets/css/bootstrap.min.css" rel="stylesheet" type="text/css" />
        <link href="assets/css/icons.css" rel="stylesheet" type="text/css" />
        <link href="assets/css/metismenu.min.css" rel="stylesheet" type="text/css" />
        <link href="assets/css/style.css" rel="stylesheet" type="text/css" />
		<link href="assets/css/style_dark.css" rel="stylesheet" type="text/css" />
        <script src="assets/js/modernizr.min.js"></script>
		<style>
            body {
                font-size: 12px;
                background-color: white;
                padding: 0;
                margin: 0;
            }
            .wrapper, .content-page, .content, .container-fluid {
                background-color: white !important;
                padding: 1rem !important;
            }
            .table-sm td, .table-sm th {
                padding: .35rem;
                font-size: 10px;
            }
            h4 {
                font-size: 1.25rem;
                font-weight: 600;
                color: #333;
            }
            h5 {
                font-size: 1.1rem;
                font-weight: 500;
                color: #555;
            }
            .card-box {
                page-break-inside: avoid;
                margin-bottom: 1rem;
                border: none;
            }
            .employee-header {
                text-align: center;
                margin-bottom: 1.5rem;
                padding-bottom: 1rem;
                border-bottom: 2px solid #333;
            }
            .employee-header img {
                width: 100px;
                height: 100px;
                border-radius: 50%;
                margin-bottom: 0.5rem;
                border: 2px solid #333;
            }
            .employee-header h2 {
                margin: 0.5rem 0;
                font-size: 1.5rem;
                font-weight: 700;
            }
            h4.section-title {
                font-size: 1.1rem;
                font-weight: 700;
                color: #000;
                margin-top: 1.5rem;
                margin-bottom: 0.5rem;
                border-bottom: 1px solid #333;
                padding-bottom: 0.3rem;
            }
            /* Layout helpers */
            .section { page-break-inside: avoid; }
            .two-col { display: grid; grid-template-columns: 1fr 1fr; column-gap: 16px; }
            .two-col > [class*="col-"] { width: 100%; padding-left: 0; padding-right: 0; }
            @page { size: A4; margin: 12mm; }
            /* Enhanced Table Styling */
            .table {
                margin-bottom: 1rem;
                border-collapse: collapse;
                border: 1px solid #999;
                width: 100%;
            }
            .table thead th {
                background-color: #333 !important;
                color: #fff !important;
                font-weight: 600;
                border: 1px solid #999;
                padding: 0.35rem 0.25rem !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            .table td, .table th { border: 1px solid #999; }
            .table-bordered td {
                border-color: #dee2e6;
            }
            .badge {
                font-weight: 600;
                padding: 0.4rem 0.8rem;
            }
            /* Status Badges */
            .badge-success { background-color: #27ae60 !important; }
            .badge-danger { background-color: #e74c3c !important; }
            .badge-warning { background-color: #f7b731 !important; color: #333 !important; }
            .badge-info { background-color: #17a2b8 !important; }
            .badge-primary { background-color: #007bff !important; }
            
            @media print {
                @page { size: A4 portrait; margin: 12mm; }
                html, body { height: auto; }
                body {
                    font-size: 11px;
                    background-color: white !important;
                    margin: 0 !important;
                }
                .content-page {
                    padding: 0 !important;
                    margin: 0 !important;
                }
                .content {
                    padding: 0 !important;
                    margin: 0 !important;
                }
                .container-fluid {
                    padding: 0 !important;
                    margin: 0 !important;
                }
                .row {
                    margin: 0 !important;
                }
                .col-md-12 {
                    padding: 0 !important;
                }
                .left.side-menu, .topbar, .navbar-custom, .page-title-box, .footer, .no-print { display: none !important; }
                .card-box {
                    box-shadow: none !important;
                    border: 1px solid #ccc !important;
                    page-break-inside: avoid;
                    margin-bottom: 0.75rem !important;
                }
                .table {
                    margin-bottom: 0.5rem !important;
                    font-size: 10px;
                    border-collapse: collapse !important;
                    border: 1px solid #999 !important;
                    width: 100% !important;
                }
                .table thead th {
                    background-color: #333 !important;
                    color: #fff !important;
                    -webkit-print-color-adjust: exact;
                    print-color-adjust: exact;
                    padding: 0.35rem 0.25rem !important;
                    border: 1px solid #999 !important;
                }
                .table td, .table th {
                    padding: 0.25rem !important;
                    border: 1px solid #999 !important;
                }
                .badge {
                    border: 1px solid #000 !important;
                    -webkit-print-color-adjust: exact;
                    print-color-adjust: exact;
                    padding: 0.25rem 0.5rem !important;
                }
                .badge-success { background-color: #27ae60 !important; color: white !important; }
                .badge-danger { background-color: #e74c3c !important; color: white !important; }
                .badge-warning { background-color: #f7b731 !important; }
                .badge-info { background-color: #17a2b8 !important; color: white !important; }
                .badge-primary { background-color: #007bff !important; color: white !important; }
                .no-print {
                    display: none !important;
                }
                .profile-section {
                    background-color: #f0f0f0 !important;
                    color: #000 !important;
                    border: 1px solid #999 !important;
                    -webkit-print-color-adjust: exact;
                    print-color-adjust: exact;
                }
                .stat-box {
                    background-color: #f9f9f9 !important;
                    color: #000 !important;
                    border: 1px solid #999 !important;
                    page-break-inside: avoid;
                    -webkit-print-color-adjust: exact;
                    print-color-adjust: exact;
                }
                .stat-box h3 {
                    color: #000 !important;
                }
                .header-title {
                    color: #000 !important;
                    border-bottom: 2px solid #333 !important;
                }
                img {
                    max-width: 100%;
                    height: auto;
                }
                .img-thumbnail {
                    border: 1px solid #999 !important;
                }
                .two-col { display: grid !important; grid-template-columns: 1fr 1fr !important; column-gap: 12px !important; }
                .two-col > [class*="col-"] { float: none !important; width: auto !important; padding-left: 0 !important; padding-right: 0 !important; }
                h4.section-title { break-after: avoid; }
                .table { page-break-inside: auto; }
                tr, td, th { page-break-inside: avoid; }
            }
        </style>
		<?php if ($is_rtl): ?>
            <link href="assets/css/style_rtl.css" rel="stylesheet" type="text/css" />
        <?php endif; ?>
		<script> window.lang = <?= json_encode($GLOBALS['translations'] ?? []) ?>;</script>
    </head>
    <body class="enlarged" data-keep-enlarged="true" onLoad="javascript:window.print()">

        <!-- Begin page -->
        <div id="wrapper">

            <!-- ========== Left Sidebar Start ========== -->
            <div class="left side-menu" style="display:none;">

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
                    <!--- Sidemenu -->
                    <?php include("./includes/main_menu.php"); ?>
                    <!-- Sidebar -->
                    <div class="clearfix"></div>
                </div>
                <!-- Sidebar -left -->

            </div>
            <!-- Left Sidebar End -->
            <div class="content-page">

                <!-- Top Bar Start -->
                <?php include("./includes/topbar.php"); ?>
                <!-- Top Bar End -->

                <!-- Start Page content -->
                <div class="content">
                    <div class="container-fluid">

                        <div class="row">
                            <div class="col-md-12">
                                <!-- Employee Header -->
                                <div class="employee-header">
                                    <img src="<?=$emprow['avatar'] ?>" alt="employee-image">
                                    <h2><?=$emprow['name']?></h2>
                                </div>

                                <!-- Personal & Employment Details -->
                                <h4 class="section-title"><?=__('personal_employment_details_header')?></h4>
                                    <div class="row two-col">
                                            <div class="col-md-6">
                                                <h5><?=__('personal_information_header')?></h5>
                                                <table class="table table-sm">
                                                    <tbody>
                                                        <tr><th style="width:150px;"><?=__('employee_id_label')?>:</th><td><strong>#<?=$emprow['empid']; ?></strong></td></tr>
                                                        <tr><th><?=__('iqama_id_label')?>:</th><td><?=$emprow['iqama']; ?></td></tr>
                                                        <tr><th><?=__('iqama_expiry')?>:</th><td><?=$emprow['iqama_exp']; ?></td></tr>
                                                        <tr><th><?=__('passport_label')?>:</th><td><?=$emprow['passport_number']; ?></td></tr>
                                                        <tr><th><?=__('passport_expiry')?>:</th><td><?php if (!empty($emprow['passport_exp'])) echo $emprow['passport_exp']; else echo 'N/A'; ?></td></tr>
                                                        <tr><th><?=__('dob_label')?>:</th><td><?=$emprow['dob']; ?> (<?=$years?> <?=__('years_text')?>)</td></tr>
                                                        <tr><th><?=__('nationality_label')?>:</th><td><?=($is_rtl ?? false ? $emprow['country_name_ar']:$emprow['country_name']); ?></td></tr>
                                                    </tbody>
                                                </table>
                                            </div>
                                            <div class="col-md-6">
                                                <h5><?=__('employment_information_header')?></h5>
                                                <table class="table table-sm">
                                                    <tbody>
                                                        <tr><th style="width:150px;"><?=__('department_label')?>:</th><td><?=$emprow['deptnme']; ?></td></tr>
                                                        <tr><th><?=__('company_label')?>:</th><td><?=($is_rtl ?? false ? ($emprow['compnme_ar'] ?? $emprow['compnme']) : $emprow['compnme']); ?></td></tr>
                                                        <tr><th><?=__('current_position')?>:</th><td><?=($is_rtl ?? false ? $emprow['jobname_ar']:$emprow['jobname']); ?></td></tr>
                                                        <tr><th><?=__('date_hired_label')?>:</th><td><?=$emprow['joining_date']; ?></td></tr>
                                                        <tr>
                                                            <th><?= __('contract_expiry_label', 'Contract Expiry') ?>:</th>
                                                            <td><?=computeContractExpiry($emprow['joining_date'],isset($emprow['vac_period']) ? (int)$emprow['vac_period'] : null,'Y-m-d') ?></td>
                                                        </tr>
                                                        <tr><th><?=__('working_period')?>:</th><td><?=ageDOB($emprow['joining_date']) ?></td></tr>
                                                        <tr><th><?=__('contract_period_label')?>:</th><td><?=formatPeriod($emprow["period"])?></td></tr>
                                                        <tr><th><?=__('contact_label')?>:</th><td><?=$emprow['mobile']; ?></td></tr>
                                                        <tr><th><?=__('email')?>:</th><td><?=$emprow['c_email']; ?></td></tr>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>

                                    <!-- Financial Details -->
                                    <h4 class="section-title"><?=__('financial_details_header')?></h4>
                                        <div class="row two-col">
                                            <div class="col-md-6">
                                                <h5><?=__('salary_breakdown_header')?></h5>
                                                <?php if ($canViewSalary): ?>
                                                <table class="table table-sm">
                                                    <tbody>
                                                    <?php
                                                    $salaryItems = ['basic','housing','transport','food','misc','cashier','fuel','tel','other','guard'];
                                                    foreach ($salaryItems as $item) {
                                                        if (!empty($emprow[$item]) && $emprow[$item] != "0") {
                                                            echo "<tr><th style='width:150px;'>" . __($item) . ":</th><td>" . number_format($emprow[$item], 2) . " " . __('sar_currency') . "</td></tr>";
                                                        }
                                                    }
                                                    ?>
                                                    <tr class="bg-light"><th class="font-weight-bold"><?=__('total_salary_label')?>:</th><td class="font-weight-bold"><?=number_format($salary_get, 2); ?> <?=__('sar_currency')?></td></tr>
                                                    </tbody>
                                                </table>
                                                <?php else: ?>
                                                <p class="text-muted"><i class="fa fa-lock"></i> <?=__('salary_hidden_no_access')?></p>
                                                <?php endif; ?>
                                            </div>
                                            <div class="col-md-6">
                                                <h5 ><?=__('bank_insurance_header')?></h5>
                                                <table class="table table-sm">
                                                    <tbody>
                                                        <tr><th style='width:150px;'><?=__('bank_name_label')?>:</th><td><?=($is_rtl ?? false ? $emprow['b_name_ar'] : $emprow['b_name'])?></td></tr>
                                                        <tr><th><?=__('iban_label')?>:</th><td><?=$emprow['iban']; ?></td></tr>
                                                        <tr><th><?=__('gosi_no_label')?>:</th><td><?=$emprow['gosi_no']; ?></td></tr>
                                                        <tr><th><?=__('gosi_payment_label')?>:</th><td><?=$emprow['amount']; ?></td></tr>
                                                        <tr><th><?=__('insurance_no_label')?>:</th><td><?=display_or_na($current_medical_insurance['insurance_no'] ?? null) ?></td></tr>
                                                        <tr><th><?=__('insurance_class_label')?>:</th><td><?=display_or_na($current_medical_class) ?></td></tr>
                                                        <tr><th><?=__('insurance_expiry_label')?>:</th><td><?=(!empty($current_medical_insurance['medical_expiry']) && $current_medical_insurance['medical_expiry'] !== '0000-00-00') ? format_safe_date($current_medical_insurance['medical_expiry'], 'd M, Y') : __('not_available') ?></td></tr>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>

                                    <!-- Assets -->
                                    <?php if ($car_info || !empty($assigned_assets)): ?>
                                    <h4 class="section-title"><?=__('assigned_assets_header')?></h4>
                                        <div class="row two-col">
                                            <?php if ($car_info): ?>
                                            <div class="col-md-6">
                                                <h5><?=__('assigned_car_header')?></h5>
                                                <table class="table table-sm">
                                                    <tbody>
                                                        <tr><th style="width:150px;"><?=__('maker_model_label')?>:</th><td><?= $car_info['maker_name'] ?> - <?= $car_info['model'] ?> (<?= $car_info['made_year'] ?>)</td></tr>
                                                        <tr><th><?=__('plate_no_label')?>:</th><td><?= $car_info['plate_no'] ?></td></tr>
                                                        <tr><th><?=__('receive_date_label')?>:</th><td><?= format_safe_date($emprow['rcv_date'] ?? null, 'd, M Y') ?></td></tr>
                                                    </tbody>
                                                </table>
                                            </div>
                                            <?php endif; ?>
                                            <?php if (!empty($assigned_assets)): ?>
                                            <div class="col-md-6">
                                                <h5><?=__('other_assets_header')?></h5>
                                                <table class="table table-sm">
                                                    <?php foreach ($assigned_assets as $asset): ?>
                                                    <tr>
                                                        <td style="width:150px;"><?= htmlspecialchars($asset['asset_name']); ?></td>
                                                        <td><?= htmlspecialchars($asset['serial_number']); ?></td>
                                                        <td><?= format_safe_date($asset['assigned_date'] ?? null, 'd M Y'); ?></td>
                                                    </tr>
                                                    <?php endforeach; ?>
                                                </table>
                                            </div>
                                            <?php endif; ?>
                                        </div>
                                    <?php endif; ?>

                                    <!-- Loan History -->
                                    <?php if (!empty($loan_history)): ?>
                                    <h4 class="section-title"><?=__('loan_history_header')?></h4>
                                        <table class="table table-sm table-bordered">
                                            <thead class="thead-light">
                                                <tr><th><?=__('amount_header')?></th><th><?=__('deduction_header')?></th><th><?=__('balance_header')?></th><th><?=__('start_header')?></th><th><?=__('end_header')?></th><th><?=__('type_header')?></th><th><?=__('status_header')?></th></tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($loan_history as $loan): 
                                                    $loan_id_hist = $loan['id'];
                                                    $total_payable_hist = $loan['total_payable'];
                                                    $sql_total_paid_hist = "SELECT COALESCE(SUM(amount), 0) as total_paid FROM `emp_loan_payments` WHERE `loan_id` = '$loan_id_hist'";
                                                    $query_total_paid_hist = mysqli_query($conDB, $sql_total_paid_hist);
                                                    $paid_rec_hist = mysqli_fetch_assoc($query_total_paid_hist);
                                                    $total_paid_hist = $paid_rec_hist['total_paid'];
                                                    $remaining_balance_hist = $total_payable_hist - $total_paid_hist;
                                                ?>
                                                <tr>
                                                    <td><?= number_format($loan['loan_amount'], 2); ?></td>
                                                    <td><?= number_format($loan['monthly_deduction'], 2); ?></td>
                                                    <td class="font-weight-bold <?= ($remaining_balance_hist > 0) ? 'text-danger' : 'text-success' ?>"><?= number_format($remaining_balance_hist, 2); ?></td>
                                                    <td><?= format_safe_date($loan['start_date'] ?? null, 'd M Y'); ?></td>
                                                    <td><?= format_safe_date($loan['end_date'] ?? null, 'd M Y'); ?></td>
                                                    <td><span class="badge badge-<?= ($loan['loan_type'] == 'emergency' ? 'warning' : 'info') ?>"><?= __($loan['loan_type']); ?></span></td>
                                                    <td><span class="badge badge-<?= ($loan['status'] == 'approved' ? 'success' : ($loan['status'] == 'paid' ? 'primary' : ($loan['status'] == 'rejected' ? 'danger' : 'warning'))) ?>"><?= __($loan['status']); ?></span></td>
                                                </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    <?php endif; ?>

                                    <!-- Vacation History -->
                                    <?php if (!empty($vacation_history)): ?>
                                    <h4 class="section-title"><?=__('vacation_history_header')?></h4>
                                        <table class="table table-sm table-bordered">
                                            <thead class="thead-light">
                                                <tr><th><?=__('type_header')?></th><th><?=__('start_date_header')?></th><th><?=__('return_date_header')?></th><th><?=__('days_header')?></th><th><?=__('permit_no_header')?></th><th><?=__('status_header')?></th><th><?=__('arrived_header')?></th></tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($vacation_history as $vac): ?>
                                                <tr>
                                                    <td><?= htmlspecialchars($vac['note'] ?? 'N/A'); ?></td>
                                                    <td><?= format_safe_date($vac['start_date'] ?? null, 'd M Y'); ?></td>
                                                    <td><?= format_safe_date($vac['return_date'] ?? null, 'd M Y'); ?></td>
                                                    <td><?= htmlspecialchars($vac['vacdays']); ?></td>
                                                    <td><?= htmlspecialchars($vac['permit_no']); ?></td>
                                                    <td><?= ($vac["review"] == 'A') ? __('approved') : (($vac["review"] == 'C') ? __('completed') : __('pending')); ?></td>
                                                    <td><?= ($vac["arrived_date"] == "") ? __('not_yet_text') : format_safe_date($vac['arrived_date'], 'd M Y'); ?></td>
                                                </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    <?php endif; ?>
                                    
                                    <!-- End of Service Info -->
                                    <?php if ($end_of_service): ?>
                                    <h4 class="section-title text-danger"><?=__('end_of_service_header')?></h4>
                                        <div class="row">
                                            <div class="col-md-6">
                                                <table class="table table-sm">
                                                    <tbody>
                                                        <tr><th style="width:150px;"><?=__('resignation_date_label')?>:</th><td><?= format_safe_date($end_of_service['resignation_date'] ?? null, 'd M Y'); ?></td></tr>
                                                        <tr><th><?=__('last_working_day_label')?>:</th><td><?= format_safe_date($end_of_service['last_working_day'] ?? null, 'd M Y'); ?></td></tr>
                                                        <tr><th><?=__('reason_label')?>:</th><td><?= htmlspecialchars($end_of_service['reason'] ?? 'N/A'); ?></td></tr>
                                                    </tbody>
                                                </table>
                                            </div>
                                            <div class="col-md-6">
                                                <table class="table table-sm">
                                                    <tbody>
                                                        <tr><th style="width:150px;"><?=__('eos_amount_label')?>:</th><td><?= number_format($end_of_service['eos_amount'] ?? 0, 2); ?> <?=__('sar_currency')?></td></tr>
                                                        <tr><th><?=__('final_settlement_label')?>:</th><td><?= number_format($end_of_service['final_settlement'] ?? 0, 2); ?> <?=__('sar_currency')?></td></tr>
                                                        <tr><th><?=__('status_label')?>:</th><td><span class="badge badge-<?= ($end_of_service['status'] == 'settled' ? 'success' : 'warning') ?>"><?= __($end_of_service['status'] ?? 'pending'); ?></span></td></tr>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    <?php endif; ?>

                                    <!-- Notes -->
                                     <?php if (!empty($employee_notes)): ?>
                                    <h4 class="section-title"><?=__('notes_notices_header')?></h4>
                                        <table class="table table-sm table-bordered">
                                            <thead class="thead-light">
                                                <tr>
                                                    <th style="width:150px;"><?=__('date_header')?></th>
                                                    <th><?=__('note_header')?></th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($employee_notes as $note): ?>
                                                <tr>
                                                    <td><?= format_safe_date($note['created_at'] ?? null, 'd, M Y'); ?></td>
                                                    <td><?= htmlspecialchars($note['note']); ?></td>
                                                </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    <?php endif; ?>

                            </div>
                        </div>
                        <!-- end row -->

                    </div> <!-- container -->
                </div> <!-- content -->

                <footer class="footer no-print">
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

        <!-- App js -->
        <script src="assets/js/jquery.core.js"></script>
        <script src="assets/js/jquery.app.js?t=<?= time() ?>"></script>

		<script type="text/javascript">
            //	window.print();
            // 	setTimeout(window.close, 0);
        </script>

    </body>
</html>