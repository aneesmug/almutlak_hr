<?php
/*********************************************************************************
 * MODIFICATION SUMMARY (007-end_of_service_print.php):
 *
 * RECENT CHANGES (007):
 * - OVERTIME FIELDS ADDED: Added support for overtime hours and overtime days
 *   in the EOS print report. Overtime hours calculated at 1.5x hourly rate,
 *   overtime days at regular daily rate. Total overtime earnings included in
 *   final settlement calculation and displayed in Financial Settlement section.
 *
 * PREVIOUS CHANGES (006):
 * 1. STANDARDIZED DAILY RATE: The daily rate used for calculating the last
 * month's salary and any absence-related deductions is now consistently based
 * on a 30-day month to match the calculation form.
 * 2. CORRECTED DEDUCTION CALCULATION: The logic for deriving the potential
 * last month's salary and the subsequent absent deduction has been updated
 * to use this new standardized daily rate, ensuring consistency.
 *********************************************************************************/

	require_once __DIR__ . '/includes/db.php';
	require_once __DIR__ . '/includes/session_check.php';
	
    // Include the main employee query from the correct path
	require_once __DIR__ . '/includes/emp_query.php';

	$query = mysqli_query($conDB, "SELECT * FROM `admin_login` WHERE `id_iqama`='".$username."'");
	if(mysqli_num_rows($query) == 1){
	include("./includes/avatar_select.php");
	
	include("./includes/Hijri_GregorianConvert.php");
	$DateConv=new Hijri_GregorianConvert;
	$format="DD/MM/YYYY";
	
    $emprow = []; // Initialize emprow
	if (mysqli_num_rows($get_emp_data) > 0) {
		$emprow = mysqli_fetch_assoc($get_emp_data);
	} else {
		//when the id not equals id show database
		header("Location: ./reg_employee.php");
        exit();
	}

    // Get EOS details
    $get_eos_data = mysqli_query($conDB, "SELECT 
        `emp_eos`.*, `eos_calc`.`details`
        FROM `emp_eos`
        LEFT JOIN `eos_calc` ON `eos_calc`.`cid` = `emp_eos`.`eos_reason`
        WHERE `emp_eos`.`emp_id`='".$_GET['emp_id']."'
    ");
    $eosrow = mysqli_fetch_assoc($get_eos_data) ?: [];

    // Reasons longer than the old VARCHAR(100) were cut on save - restore the full text
    require_once __DIR__ . '/includes/eos_reason_fix.php';
    eos_repair_reason($conDB, $eosrow);

    // Get Salary benefits
    $get_salary_data = mysqli_query($conDB, "SELECT * FROM `emp_salary` WHERE `status` = 1 AND `emp_id`='".$_GET['emp_id']."'");
    $salaryrow = mysqli_fetch_assoc($get_salary_data) ?: [];

    // Get Assigned Assets for Clearance
    $get_assets_data = mysqli_query($conDB, "SELECT 
        ea.serial_number, ea.description, ea.assigned_date, a.name as asset_name
        FROM `employee_assets` ea
        LEFT JOIN `assets` a ON ea.asset_id = a.id
        WHERE ea.emp_id = '".$_GET['emp_id']."' AND ea.status = 'Assigned'
    ");
    $assigned_assets = mysqli_fetch_all($get_assets_data, MYSQLI_ASSOC);

    // Get Outstanding Loans
    $get_loans_data = mysqli_query($conDB, "SELECT 
            l.loan_type,
            l.loan_amount,
            l.total_payable,
            l.status,
            (l.total_payable - COALESCE((SELECT SUM(amount) FROM emp_loan_payments WHERE loan_id = l.id), 0)) as remaining_balance
        FROM `emp_loan` l
        WHERE l.emp_id = '".$_GET['emp_id']."' 
        AND l.status NOT IN ('processed', 'rejected') 
        HAVING remaining_balance > 0
    ");
    $outstanding_loans = mysqli_fetch_all($get_loans_data, MYSQLI_ASSOC);


    // Age Calculation
    $years = '';
    if (!empty($emprow['dob'])) {
        $birth_date = new DateTime(date('Y-m-d', strtotime(str_replace('/', '-', $emprow['dob']))));
        $current_date = new DateTime();
        $diff = $birth_date->diff($current_date);
        $years = $diff->y . " Years";
    }

    // Initialize basic_salary outside the block for later use
    $basic_salary = 0;
    
    // Calculate Total Salary for EOS Amount (including provisional housing)
    $total_salary_for_eos = 0;
    if (!empty($salaryrow)) {
        $basic_salary = (float)($salaryrow['basic'] ?? 0);
        $housing_benefit = (float)($salaryrow['housing'] ?? 0);
        $calculated_housing = 0;

        if ($housing_benefit == 0 && $basic_salary > 0) {
            $calculated_housing = ($basic_salary / 12) * 2;
        } else {
            $calculated_housing = $housing_benefit;
        }

        $total_salary_for_eos += $basic_salary;
        $total_salary_for_eos += $calculated_housing;
        $total_salary_for_eos += (float)($salaryrow['transport'] ?? 0);
        $total_salary_for_eos += (float)($salaryrow['food'] ?? 0);
        $total_salary_for_eos += (float)($salaryrow['misc'] ?? 0);
        $total_salary_for_eos += (float)($salaryrow['cashier'] ?? 0);
        $total_salary_for_eos += (float)($salaryrow['fuel'] ?? 0);
        $total_salary_for_eos += (float)($salaryrow['tel'] ?? 0);
        $total_salary_for_eos += (float)($salaryrow['guard'] ?? 0);
        $total_salary_for_eos += (float)($salaryrow['other'] ?? 0);
    }

    // Calculate Actual Salary Base for Deductions (sum of actual benefits only)
    $actual_salary_base = 0;
    if (!empty($salaryrow)) {
        $actual_salary_base += (float)($salaryrow['basic'] ?? 0);
        $actual_salary_base += (float)($salaryrow['housing'] ?? 0); // actual housing
        $actual_salary_base += (float)($salaryrow['transport'] ?? 0);
        $actual_salary_base += (float)($salaryrow['food'] ?? 0);
        $actual_salary_base += (float)($salaryrow['misc'] ?? 0);
        $actual_salary_base += (float)($salaryrow['cashier'] ?? 0);
        $actual_salary_base += (float)($salaryrow['fuel'] ?? 0);
        $actual_salary_base += (float)($salaryrow['tel'] ?? 0);
        $actual_salary_base += (float)($salaryrow['guard'] ?? 0);
        $actual_salary_base += (float)($salaryrow['other'] ?? 0);
    }

    // *** Get deduction components from database (already calculated in EOS form) ***
    // Fetch GOSI deduction from database instead of calculating
    $gosi_deduction = (float)($eosrow['gosi_deduction'] ?? 0);

    // Use ACTUAL salary paid (which already accounts for absences)
    // Display the actual amount paid, not potential - absences are already reflected
    $last_month_salary_paid = (float)($eosrow['curt_month_salry'] ?? 0);


    // Fetch deduction fields from database
    $absent_days = (int)($eosrow['absent_days'] ?? 0);
    $deduction_hours = (float)($eosrow['deduction_hours'] ?? 0);
    
    // Recalculate total earnings and deductions for clarity
    $overtime_hours = (float)($eosrow['overtime_hours'] ?? 0);
    $overtime_days = (float)($eosrow['overtime_days'] ?? 0);
    $other_earnings = (float)($eosrow['other_earnings'] ?? 0);
    
    // Calculate overtime earnings per new rule:
    // per-hour overtime rate = (basic/240)/2 + (contractBase/240)
    // contractBase = $actual_salary_base (actual benefits without calculated housing)
    // hours amount = overtimeHourlyRate * overtime_hours
    // days amount  = overtimeHourlyRate * 8 * overtime_days
    $overtime_earnings = 0;
    if ($actual_salary_base > 0 && ($overtime_hours > 0 || $overtime_days > 0)) {
        $overtime_hourly_rate = (($basic_salary / 240) / 2) + ($actual_salary_base / 240);
        $overtime_hours_amount = $overtime_hourly_rate * $overtime_hours;
        $overtime_days_amount = $overtime_hourly_rate * 8 * $overtime_days;
        $overtime_earnings = $overtime_hours_amount + $overtime_days_amount;
    }
    
    $total_earnings = (float)($eosrow['eos_amount'] ?? 0)
        + (float)($eosrow['anul_vac_salry'] ?? 0)
        + (float)($eosrow['curt_month_salry'] ?? 0)
        + $overtime_earnings
        + $other_earnings;
    $loan_deduction = (float)($eosrow['deduct'] ?? 0);
    $net_payment = (float)($eosrow['net_payment'] ?? 0);

    // Calculate deductions based on database values
    $dailyRateDeduction = $actual_salary_base / 30;
    $hourlyRateDeduction = $dailyRateDeduction / 8;
    $absent_deduction_amount = $dailyRateDeduction * $absent_days;
    $hourly_deduction_amount = $hourlyRateDeduction * $deduction_hours;
    $total_absence_deductions = $absent_deduction_amount + $hourly_deduction_amount;

?>
<!doctype html> 
<html lang="en">

    <head>
        <meta charset="utf-8" />
        <title><?=$site_title ?> - End of Service Print</title>
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
        <!-- Final settlement sheet: one fixed A4 page. Used for the on-screen preview and copied into the print iframe. -->
        <style id="eospStyles">
            .eosp-preview { display: flex; justify-content: center; padding: 10px 0 24px; overflow-x: auto; }
            .eosp, .eosp * { box-sizing: border-box; }
            .eosp {
                --ep-ink: #1f2937; --ep-muted: #6b7280; --ep-line: #e5e7eb; --ep-soft: #f8fafc;
                --ep-navy: #1e3a5f; --ep-navy-2: #2c5282; --ep-green: #166534; --ep-green-bg: #f0fdf4; --ep-red: #b91c1c; --ep-red-bg: #fef2f2;
                -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important;
                font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; color: var(--ep-ink); line-height: 1.4; font-size: 11.5px;
            }
            .eosp.eosp-sheet {
                position: relative; width: 210mm; max-height: 296mm; padding: 7mm 10mm 4mm; margin: 0 auto; background: #fff; overflow: hidden;
                display: flex; flex-direction: column; box-shadow: 0 6px 28px rgba(15, 23, 42, .16);
            }
            .eosp.eosp-sheet::before { content: ''; position: absolute; top: 0; left: 0; right: 0; height: 5px; background: linear-gradient(90deg, var(--ep-navy), var(--ep-navy-2)); }
            .eosp .eosp-body { flex: 0 0 auto; }
            .eosp p, .eosp h2, .eosp h3, .eosp h4 { margin: 0; }
            .eosp .ar { direction: rtl; text-align: right; font-family: Tahoma, 'Segoe UI', sans-serif; }
            .eosp i { font-style: normal; }
            .eosp small { font-size: .85em; color: var(--ep-muted); font-weight: 400; }

            /* Header */
            .eosp .ep-head { display: grid; grid-template-columns: 1fr auto 1fr; align-items: center; gap: 12px; padding-bottom: 10px; border-bottom: 1.5px solid var(--ep-navy); }
            .eosp .ep-logo { height: 54px; width: auto; max-width: 170px; object-fit: contain; }
            .eosp .ep-title { text-align: center; }
            .eosp .ep-title h2 { font-size: 21px; font-weight: 800; letter-spacing: 2.5px; color: var(--ep-navy); }
            .eosp .ep-title h3 { font-size: 17px; font-weight: 700; color: var(--ep-navy); margin-top: 1px; direction: rtl; }
            .eosp .ep-title p { margin-top: 3px; font-size: 10px; color: var(--ep-muted); letter-spacing: .3px; }
            .eosp .ep-ref { justify-self: end; border: 1px solid var(--ep-line); border-radius: 6px; overflow: hidden; min-width: 170px; }
            .eosp .ep-ref > div { display: flex; flex-direction: column; padding: 4px 10px; }
            .eosp .ep-ref > div + div { border-top: 1px solid var(--ep-line); }
            .eosp .ep-ref span { font-size: 9px; text-transform: uppercase; letter-spacing: .5px; color: var(--ep-muted); display: flex; justify-content: space-between; gap: 8px; }
            .eosp .ep-ref b { font-size: 12px; color: var(--ep-ink); font-variant-numeric: tabular-nums; }

            /* Sections */
            .eosp .ep-sec { margin-top: 7px; }
            .eosp .ep-sec-title {
                display: flex; justify-content: space-between; align-items: center; padding: 4px 10px; margin-bottom: 4px;
                background: var(--ep-navy) !important; color: #fff !important; border-radius: 4px; font-size: 11.5px; font-weight: 700; letter-spacing: .4px;
            }
            .eosp .ep-sec-title span:first-child { text-transform: uppercase; }

            /* Employee fields */
            .eosp .ep-fields { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); border: 1px solid var(--ep-line); border-radius: 6px; overflow: hidden; }
            .eosp .ep-field { padding: 3px 10px 4px; border-bottom: 1px solid var(--ep-line); border-inline-end: 1px solid var(--ep-line); min-width: 0; }
            .eosp .ep-field.span-2 { grid-column: span 2; }
            .eosp .ep-field:nth-last-child(-n+3) { border-bottom: 0; }
            .eosp .ep-field:nth-child(3n+2) { border-inline-end: 0; } /* last column: Emp ID row + every 3rd after */
            .eosp .ep-lbl { display: flex; justify-content: space-between; gap: 6px; font-size: 9px; font-weight: 600; text-transform: uppercase; letter-spacing: .4px; color: var(--ep-muted); }
            .eosp .ep-lbl .ar { text-transform: none; letter-spacing: 0; font-size: 10px; }
            .eosp .ep-val { margin-top: 1px; font-size: 12px; font-weight: 600; color: var(--ep-ink); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
            .eosp .ep-val.is-strong { font-size: 13px; font-weight: 700; color: var(--ep-navy); }
            /* English left, Arabic right; when both don't fit on one line the Arabic drops to its own line (never cut off) */
            .eosp .ep-val.is-dual { display: flex; flex-wrap: wrap; justify-content: space-between; align-items: baseline; column-gap: 8px; row-gap: 0; white-space: normal; overflow: visible; }
            .eosp .ep-val.is-dual > span { min-width: 0; white-space: nowrap; }
            .eosp .ep-val.is-dual > .ar { direction: rtl; text-align: right; margin-left: auto; font-family: Tahoma, 'Segoe UI', Arial, sans-serif; }
            .eosp .is-mono { font-family: Consolas, 'Courier New', monospace; letter-spacing: .3px; }

            /* Service period */
            .eosp .ep-service { display: grid; grid-template-columns: repeat(3, 70px) 1fr; gap: 8px; align-items: stretch; }
            .eosp .ep-stat { border: 1px solid var(--ep-line); border-radius: 6px; background: var(--ep-soft) !important; text-align: center; padding: 4px; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 3px; }
            .eosp .ep-stat b { display: block; font-size: 20px; font-weight: 800; color: var(--ep-navy); line-height: 1.1; }
            .eosp .ep-stat span { display: flex; flex-direction: column; align-items: center; font-size: 9px; line-height: 1.25; color: var(--ep-muted); text-transform: uppercase; letter-spacing: .4px; }
            .eosp .ep-stat span i { font-style: normal; text-transform: none; letter-spacing: 0; }
            .eosp .ep-reason { border: 1px solid var(--ep-line); border-radius: 6px; padding: 4px 10px; display: flex; flex-direction: column; justify-content: center; }
            .eosp .ep-reason-txt { display: flex; justify-content: space-between; gap: 12px; margin-top: 3px; font-size: 12px; font-weight: 600; }

            /* Generic tables (assets / loans) */
            .eosp .ep-table { width: 100%; border-collapse: collapse; border: 1px solid var(--ep-line); }
            .eosp .ep-table th { background: var(--ep-soft) !important; text-align: left; font-size: 9.5px; font-weight: 700; text-transform: uppercase; letter-spacing: .3px; color: var(--ep-muted); padding: 5px 8px; border-bottom: 1px solid var(--ep-line); }
            .eosp .ep-table th i { text-transform: none; margin-inline-start: 4px; font-weight: 600; }
            .eosp .ep-table td { padding: 3px 8px; border-bottom: 1px solid var(--ep-line); font-size: 11px; }
            .eosp .num { text-align: right !important; white-space: nowrap; font-variant-numeric: tabular-nums; }

            /* Financial statement */
            .eosp .ep-fin { width: 100%; border-collapse: collapse; border: 1px solid var(--ep-line); }
            .eosp .ep-fin th { background: var(--ep-soft) !important; font-size: 9.5px; font-weight: 700; text-transform: uppercase; letter-spacing: .4px; color: var(--ep-muted); padding: 5px 10px; text-align: left; border-bottom: 1px solid var(--ep-line); }
            .eosp .ep-fin td { padding: 3px 10px; font-size: 11.5px; border-bottom: 1px solid #f1f5f9; }
            .eosp .ep-fin td.num { width: 26%; font-weight: 600; }
            .eosp .ep-fin td.neg { color: var(--ep-red); }
            .eosp .ep-fin tr.grp td { padding-top: 6px; font-size: 10px; font-weight: 800; text-transform: uppercase; letter-spacing: .6px; border-bottom: 1px solid var(--ep-line); }
            .eosp .ep-fin tr.grp td.ar { font-size: 11px; text-transform: none; letter-spacing: 0; }
            .eosp .ep-fin tr.grp.is-earn td { color: var(--ep-green); }
            .eosp .ep-fin tr.grp.is-deduct td { color: var(--ep-red); }
            .eosp .ep-fin tr.sub td { font-weight: 700; border-top: 1px solid var(--ep-line); border-bottom: 1px solid var(--ep-line); }
            .eosp .ep-fin tr.sub.is-earn td { background: var(--ep-green-bg) !important; color: var(--ep-green); }
            .eosp .ep-fin tr.sub.is-deduct td { background: var(--ep-red-bg) !important; color: var(--ep-red); }
            .eosp .ep-fin tr.is-none td { color: var(--ep-muted); font-style: italic; }
            .eosp .ep-net {
                display: flex; justify-content: space-between; align-items: center; gap: 16px; margin-top: 6px; padding: 6px 14px;
                background: var(--ep-navy) !important; color: #fff !important; border-radius: 6px;
            }
            .eosp .ep-net-lbl { display: flex; flex-direction: column; gap: 1px; font-size: 12px; font-weight: 800; letter-spacing: 1px; }
            .eosp .ep-net-lbl .ar { text-align: left; font-size: 12px; letter-spacing: 0; opacity: .9; }
            .eosp .ep-net-amt { font-size: 22px; font-weight: 800; font-variant-numeric: tabular-nums; white-space: nowrap; }
            .eosp .ep-net-amt small { color: #cbd5e1; font-size: 12px; font-weight: 600; margin-inline-end: 3px; }

            /* Acknowledgment */
            .eosp .ep-ack { margin-top: 7px; padding: 6px 12px; border: 1px solid var(--ep-line); border-inline-start: 3px solid var(--ep-navy); border-radius: 6px; background: var(--ep-soft) !important; font-size: 10.5px; line-height: 1.55; color: #374151; }
            .eosp .ep-ack p + p { margin-top: 4px; padding-top: 4px; border-top: 1px dashed var(--ep-line); }
            .eosp .ep-ack b { color: var(--ep-navy); }

            /* Signatures */
            .eosp .ep-foot { display: grid; grid-template-columns: 1fr 1fr 110px; gap: 12px; padding-top: 8px; flex-shrink: 0; page-break-inside: avoid; }
            .eosp .ep-sign { border: 1px solid var(--ep-line); border-radius: 6px; overflow: hidden; }
            .eosp .ep-sign-head { display: flex; justify-content: space-between; padding: 5px 10px; background: var(--ep-soft) !important; border-bottom: 1px solid var(--ep-line); font-size: 10.5px; font-weight: 700; color: var(--ep-navy); text-transform: uppercase; letter-spacing: .4px; }
            .eosp .ep-sign-head .ar { text-transform: none; }
            .eosp .ep-sign-row { display: flex; align-items: flex-end; gap: 8px; padding: 0 10px; height: 22px; }
            .eosp .ep-sign-row:nth-child(3) { height: 30px; }
            .eosp .ep-sign-row span { flex: 0 0 84px; display: flex; justify-content: space-between; font-size: 9.5px; color: var(--ep-muted); padding-bottom: 3px; }
            .eosp .ep-sign-row b { flex: 1 1 auto; border-bottom: 1px solid #9ca3af; min-height: 16px; padding-bottom: 2px; font-size: 11px; font-weight: 600; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
            .eosp .ep-stamp { border: 1.5px dashed #cbd5e1; border-radius: 50%; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 2px; font-size: 9px; color: #94a3b8; text-transform: uppercase; letter-spacing: .5px; aspect-ratio: 1; align-self: center; }
            .eosp .ep-stamp .ar { text-align: center; text-transform: none; }
            .eosp .ep-docfoot { display: flex; justify-content: space-between; margin-top: 5px; padding-top: 3px; border-top: 1px solid var(--ep-line); font-size: 8.5px; color: #9ca3af; letter-spacing: .3px; flex-shrink: 0; }
            @media print {
                .eosp.eosp-sheet { box-shadow: none; }
                /* No stamp placeholder on paper - signature boxes take the full width */
                .eosp .ep-stamp { display: none !important; }
                .eosp .ep-foot { grid-template-columns: 1fr 1fr; }
            }
        </style>
    </head>
    <body class="enlarged" data-keep-enlarged="true" onload="printDiv()">

        <!-- Begin page -->
        <div id="wrapper">

            <!-- ========== Left Sidebar Start ========== -->
            <div class="left side-menu">
                <div class="slimscroll-menu" id="remove-scroll">
                    <!-- LOGO -->
                    <div class="topbar-left">
                        <a href="dashboard.php" class="logo">
                            <span><img src="<?=get_setting($conDB, 'logo')?>" alt="" height="22"></span>
                            <i><img src="<?=get_setting($conDB, 'white_logo')?>" alt="" height="28"></i>
                        </a>
                    </div>
                    <!--- Sidemenu -->
                    <?php include("./includes/main_menu.php"); ?>
                    <!-- Sidebar -->
                    <div class="clearfix"></div>
                </div>
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
                            <div class="col-md-12">
                                <!-- Printable Area -->
                                <div class="eosp-preview" id="dvContents">
                                    <?php
                                        $h = function ($v) { return htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8'); };
                                        $money = function ($v) { return number_format((float)$v, 2); };
                                        $total_deductions = $gosi_deduction + $total_absence_deductions + $loan_deduction;
                                        $eos_ref = !empty($eosrow['id']) ? 'EOS-' . str_pad((string)$eosrow['id'], 5, '0', STR_PAD_LEFT) : '-';
                                        // Arabic values: department / nationality / company come from their *_ar columns.
                                        // The name has no Arabic column, so use the shared translator (cached in translation_cache).
                                        $name_ar = '';
                                        $translateFile = __DIR__ . '/includes/ajaxFile/translateText.php';
                                        if (!empty($emprow['name']) && file_exists($translateFile)) {
                                            require_once $translateFile;
                                            if (function_exists('auto_translate_text')) {
                                                $nameOk = false;
                                                $name_ar = auto_translate_text($emprow['name'], 'en', 'ar', $nameOk);
                                                if (!$nameOk || trim($name_ar) === trim($emprow['name'])) {
                                                    $name_ar = '';
                                                }
                                            }
                                        }
                                        // English value on the left, Arabic value on the right (Arabic hidden when missing)
                                        $dual = function ($en, $ar) use ($h) {
                                            $en = trim((string)($en ?? ''));
                                            $ar = trim((string)($ar ?? ''));
                                            return '<span class="en">' . ($en !== '' ? $h($en) : '-') . '</span>'
                                                . ($ar !== '' && $ar !== $en ? '<span class="ar">' . $h($ar) . '</span>' : '');
                                        };
                                        $lwd = $eosrow['end_date'] ?? ($emprow['ter_date'] ?? null);
                                        $vac_days = number_format((float)($eosrow['anul_vac_days'] ?? 0), 2);
                                        $cm_days = $eosrow['curt_month_days'] ?? '0';
                                    ?>
                                    <div class="eosp eosp-sheet">
                                        <div class="eosp-body">
                                            <!-- Header -->
                                            <header class="ep-head">
                                                <img src="<?=get_setting($conDB, 'logo')?>" alt="Company Logo" class="ep-logo">
                                                <div class="ep-title">
                                                    <h2>FINAL SETTLEMENT</h2>
                                                    <h3>مخالصة نهائية</h3>
                                                    <p>End of Service Clearance &middot; مخالصة نهاية الخدمة</p>
                                                </div>
                                                <div class="ep-ref">
                                                    <div><span>Reference No. <i>الرقم المرجعي</i></span><b><?= $h($eos_ref) ?></b></div>
                                                    <div><span>Issue Date <i>تاريخ الإصدار</i></span><b><?= date('d M, Y') ?></b></div>
                                                </div>
                                            </header>

                                            <!-- Employee Information -->
                                            <section class="ep-sec">
                                                <h4 class="ep-sec-title"><span>Employee Information</span><span class="ar">معلومات الموظف</span></h4>
                                                <div class="ep-fields">
                                                    <div class="ep-field span-2"><div class="ep-lbl"><span>Name of Employee</span><span class="ar">اسم الموظف</span></div><div class="ep-val is-strong is-dual"><?= $dual($emprow['name'], $name_ar) ?></div></div>
                                                    <div class="ep-field"><div class="ep-lbl"><span>Employee ID</span><span class="ar">الرقم الوظيفي</span></div><div class="ep-val is-mono"><?= $h($emprow['empid']) ?></div></div>
                                                    <div class="ep-field"><div class="ep-lbl"><span>Iqama / ID</span><span class="ar">رقم الإقامة</span></div><div class="ep-val is-mono"><?= $h($emprow['iqama']) ?></div></div>
                                                    <div class="ep-field"><div class="ep-lbl"><span>Passport No.</span><span class="ar">رقم الجواز</span></div><div class="ep-val is-mono"><?= $h($emprow['passport_number'] ?: '-') ?></div></div>
                                                    <div class="ep-field"><div class="ep-lbl"><span>Nationality</span><span class="ar">الجنسية</span></div><div class="ep-val is-dual"><?= $dual($emprow['country_name'], $emprow['country_name_ar'] ?? '') ?></div></div>
                                                    <div class="ep-field"><div class="ep-lbl"><span>Date of Birth</span><span class="ar">تاريخ الميلاد</span></div><div class="ep-val"><?= !empty($emprow['dob']) ? format_safe_date($emprow['dob'], 'd M, Y') : '-' ?><?= $years !== '' ? ' <small>(' . $h($years) . ')</small>' : '' ?></div></div>
                                                    <div class="ep-field"><div class="ep-lbl"><span>Department</span><span class="ar">القسم</span></div><div class="ep-val is-dual"><?= $dual($emprow['deptnme'], $emprow['deptnme_ar'] ?? '') ?></div></div>
                                                    <div class="ep-field"><div class="ep-lbl"><span>Company</span><span class="ar">الشركة</span></div><div class="ep-val is-dual"><?= $dual($emprow['compnme'], $emprow['compnme_ar'] ?? '') ?></div></div>
                                                    <div class="ep-field"><div class="ep-lbl"><span>Date Hired</span><span class="ar">تاريخ التعيين</span></div><div class="ep-val"><?= format_safe_date($emprow['joining_date'] ?? null, 'd M, Y') ?></div></div>
                                                    <div class="ep-field"><div class="ep-lbl"><span>Last Working Day</span><span class="ar">آخر يوم عمل</span></div><div class="ep-val"><?= $lwd ? format_safe_date($lwd, 'd M, Y') : '-' ?></div></div>
                                                </div>
                                            </section>

                                            <!-- Service Period -->
                                            <section class="ep-sec">
                                                <h4 class="ep-sec-title"><span>Service Period Summary</span><span class="ar">ملخص فترة الخدمة</span></h4>
                                                <div class="ep-service">
                                                    <div class="ep-stat"><b><?= $h($eosrow['t_years'] ?? '0') ?></b><span>Years <i>سنوات</i></span></div>
                                                    <div class="ep-stat"><b><?= $h($eosrow['t_months'] ?? '0') ?></b><span>Months <i>أشهر</i></span></div>
                                                    <div class="ep-stat"><b><?= $h($eosrow['t_days'] ?? '0') ?></b><span>Days <i>أيام</i></span></div>
                                                    <div class="ep-reason">
                                                        <div class="ep-lbl"><span>End of Service Reason</span><span class="ar">سبب نهاية الخدمة</span></div>
                                                        <div class="ep-reason-txt">
                                                            <span><?= $h($eosrow['leaving_reason'] ?? 'N/A') ?></span>
                                                            <span class="ar"><?= $h($eosrow['leaving_reason_ar'] ?? '') ?></span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </section>

                                            <?php if (!empty($assigned_assets)): ?>
                                            <!-- Assets for Clearance -->
                                            <section class="ep-sec">
                                                <h4 class="ep-sec-title"><span>Assets for Clearance</span><span class="ar">الأصول للتسليم</span></h4>
                                                <table class="ep-table">
                                                    <thead>
                                                        <tr>
                                                            <th>Asset Type <i>نوع الأصل</i></th>
                                                            <th>Serial No. <i>الرقم التسلسلي</i></th>
                                                            <th>Assigned Date <i>تاريخ التعيين</i></th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <?php foreach ($assigned_assets as $asset): ?>
                                                        <tr>
                                                            <td><?= $h($asset['asset_name']) ?></td>
                                                            <td class="is-mono"><?= $h($asset['serial_number']) ?></td>
                                                            <td><?= $h($asset['assigned_date']) ?></td>
                                                        </tr>
                                                        <?php endforeach; ?>
                                                    </tbody>
                                                </table>
                                            </section>
                                            <?php endif; ?>

                                            <?php if (!empty($outstanding_loans)): ?>
                                            <!-- Outstanding Loans -->
                                            <section class="ep-sec">
                                                <h4 class="ep-sec-title"><span>Outstanding Loans</span><span class="ar">السلف المستحقة</span></h4>
                                                <table class="ep-table">
                                                    <thead>
                                                        <tr>
                                                            <th>Loan Type <i>نوع السلفة</i></th>
                                                            <th class="num">Total Amount <i>المبلغ الإجمالي</i></th>
                                                            <th class="num">Remaining <i>المتبقي</i></th>
                                                            <th>Status <i>الحالة</i></th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <?php foreach ($outstanding_loans as $loan): ?>
                                                        <tr>
                                                            <td><?= $h(ucfirst($loan['loan_type'])) ?></td>
                                                            <td class="num"><?= $money($loan['total_payable']) ?></td>
                                                            <td class="num"><?= $money($loan['remaining_balance']) ?></td>
                                                            <td><?= $h(ucwords(str_replace('_', ' ', $loan['status']))) ?></td>
                                                        </tr>
                                                        <?php endforeach; ?>
                                                    </tbody>
                                                </table>
                                            </section>
                                            <?php endif; ?>

                                            <!-- Financial Settlement -->
                                            <section class="ep-sec">
                                                <h4 class="ep-sec-title"><span>Financial Settlement</span><span class="ar">التسوية المالية</span></h4>
                                                <table class="ep-fin">
                                                    <thead>
                                                        <tr><th>Description</th><th class="ar">البيان</th><th class="num">Amount (SAR)</th></tr>
                                                    </thead>
                                                    <tbody>
                                                        <tr class="grp is-earn"><td>Earnings</td><td class="ar">المستحقات</td><td></td></tr>
                                                        <tr><td>End of Service Award</td><td class="ar">مكافأة نهاية الخدمة</td><td class="num"><?= $money($eosrow['eos_amount'] ?? 0) ?></td></tr>
                                                        <tr><td>Vacation Balance <small>(<?= $vac_days ?> days)</small></td><td class="ar">رصيد الإجازات <small>(<?= $vac_days ?> يوم)</small></td><td class="num"><?= $money($eosrow['anul_vac_salry'] ?? 0) ?></td></tr>
                                                        <tr><td>Salary for Last Month <small>(<?= $h($cm_days) ?> days)</small></td><td class="ar">راتب الشهر الأخير <small>(<?= $h($cm_days) ?> يوم)</small></td><td class="num"><?= $money($last_month_salary_paid) ?></td></tr>
                                                        <?php if ($overtime_hours > 0): ?>
                                                        <tr><td>Overtime <small>(<?= $money($overtime_hours) ?> hrs)</small></td><td class="ar">العمل الإضافي <small>(<?= $money($overtime_hours) ?> ساعة)</small></td><td class="num"><?= $money($overtime_hourly_rate * $overtime_hours) ?></td></tr>
                                                        <?php endif; ?>
                                                        <?php if ($overtime_days > 0): ?>
                                                        <tr><td>Overtime <small>(<?= $money($overtime_days) ?> days)</small></td><td class="ar">العمل الإضافي <small>(<?= $money($overtime_days) ?> يوم)</small></td><td class="num"><?= $money($overtime_hourly_rate * 8 * $overtime_days) ?></td></tr>
                                                        <?php endif; ?>
                                                        <?php if ($other_earnings > 0): ?>
                                                        <tr><td>Other Earnings</td><td class="ar">مستحقات أخرى</td><td class="num"><?= $money($other_earnings) ?></td></tr>
                                                        <?php endif; ?>
                                                        <tr class="sub is-earn"><td>Total Earnings</td><td class="ar">إجمالي المستحقات</td><td class="num"><?= $money($total_earnings) ?></td></tr>

                                                        <tr class="grp is-deduct"><td>Deductions</td><td class="ar">الخصومات</td><td></td></tr>
                                                        <?php if ($gosi_deduction > 0.01): ?>
                                                        <tr><td>GOSI Deduction</td><td class="ar">خصم التأمينات الاجتماعية</td><td class="num neg">(<?= $money($gosi_deduction) ?>)</td></tr>
                                                        <?php endif; ?>
                                                        <?php if ($absent_days > 0): ?>
                                                        <tr><td>Absent Days <small>(<?= (int)$absent_days ?> days)</small></td><td class="ar">أيام الغياب <small>(<?= (int)$absent_days ?> يوم)</small></td><td class="num neg">(<?= $money($absent_deduction_amount) ?>)</td></tr>
                                                        <?php endif; ?>
                                                        <?php if ($deduction_hours > 0): ?>
                                                        <tr><td>Deduction Hours <small>(<?= $money($deduction_hours) ?> hrs)</small></td><td class="ar">خصم ساعات <small>(<?= $money($deduction_hours) ?> ساعة)</small></td><td class="num neg">(<?= $money($hourly_deduction_amount) ?>)</td></tr>
                                                        <?php endif; ?>
                                                        <?php if ($loan_deduction > 0.01): ?>
                                                        <tr><td>Loan / Other Deductions</td><td class="ar">خصم السلف / أخرى</td><td class="num neg">(<?= $money($loan_deduction) ?>)</td></tr>
                                                        <?php endif; ?>
                                                        <?php if ($total_deductions < 0.01): ?>
                                                        <tr class="is-none"><td>No deductions</td><td class="ar">لا توجد خصومات</td><td class="num">0.00</td></tr>
                                                        <?php endif; ?>
                                                        <tr class="sub is-deduct"><td>Total Deductions</td><td class="ar">إجمالي الخصومات</td><td class="num">(<?= $money($total_deductions) ?>)</td></tr>
                                                    </tbody>
                                                </table>
                                                <div class="ep-net">
                                                    <div class="ep-net-lbl"><span>NET PAYMENT DUE</span><span class="ar">صافي المبلغ المستحق</span></div>
                                                    <div class="ep-net-amt"><small>SAR</small> <?= $money($net_payment) ?></div>
                                                </div>
                                            </section>

                                            <!-- Acknowledgment -->
                                            <section class="ep-ack">
                                                <p>I acknowledge and undertake, the employee / <b><?= $h($emprow['name']) ?></b>, ID No. <b><?= $h($emprow['iqama']) ?></b>, that I have received all of my statutory dues from overtime and wages according to the Labor Law and the Workers from Al-Mutlak Trading from the beginning of my work until the date of leaving work.</p>
                                                <p class="ar">أقر وأتعهد أنا الموظف / <b><?= $h($name_ar !== '' ? $name_ar : $emprow['name']) ?></b> برقم هوية / إقامة <b><?= $h($emprow['iqama']) ?></b> بأنني استلمت كافة مستحقاتي القانونية من أجور العمل الإضافي والأجور حسب نظام العمل والعمال في شركة المطلق للتجارة منذ بداية عملي حتى تاريخ ترك العمل.</p>
                                            </section>
                                        </div>

                                        <!-- Signatures -->
                                        <footer class="ep-foot">
                                            <div class="ep-sign">
                                                <div class="ep-sign-head"><span>Employee</span><span class="ar">الموظف</span></div>
                                                <div class="ep-sign-row"><span>Name <i>الاسم</i></span><b><?= $h($emprow['name']) ?></b></div>
                                                <div class="ep-sign-row"><span>Signature <i>التوقيع</i></span><b></b></div>
                                                <div class="ep-sign-row"><span>Date <i>التاريخ</i></span><b></b></div>
                                            </div>
                                            <div class="ep-sign">
                                                <div class="ep-sign-head"><span>Company Representative</span><span class="ar">ممثل الشركة</span></div>
                                                <div class="ep-sign-row"><span>Name <i>الاسم</i></span><b></b></div>
                                                <div class="ep-sign-row"><span>Signature <i>التوقيع</i></span><b></b></div>
                                                <div class="ep-sign-row"><span>Date <i>التاريخ</i></span><b></b></div>
                                            </div>
                                            <div class="ep-stamp"><span>Company Stamp</span><span class="ar">ختم الشركة</span></div>
                                        </footer>
                                        <div class="ep-docfoot"><span><?= $h($emprow['compnme'] ?? '') ?></span><span>Final Settlement &middot; <?= $h($eos_ref) ?> &middot; Page 1 of 1</span></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- end row -->
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

        <!-- App js -->
        <script src="assets/js/jquery.core.js"></script>
        <script src="assets/js/jquery.app.js?t=<?= time() ?>"></script>

		<script type="text/javascript">
// Print only the A4 sheet in a hidden iframe. Same stylesheet as the screen preview; if the content is
// still taller than one A4 page it is scaled down so the settlement never spills onto a 2nd page.
function printDiv() {
    var divToPrint = document.getElementById('dvContents').innerHTML;
    var css = document.getElementById('eospStyles').innerHTML;
    var printFrame = document.createElement('iframe');
    printFrame.style.position = 'fixed';
    printFrame.style.width = '0';
    printFrame.style.height = '0';
    printFrame.style.border = '0';
    document.body.appendChild(printFrame);

    var printDocument = printFrame.contentWindow.document;
    printDocument.open();
    printDocument.write(
        '<!doctype html><html><head><meta charset="utf-8"><title>Final Settlement</title>' +
        '<style>' + css +
        '@page { size: A4 portrait; margin: 0; }' +
        'html, body { margin: 0; padding: 0; background: #fff; height: 296mm; overflow: hidden; }' +
        '.eosp-sheet { margin: 0 !important; box-shadow: none !important; }' +
        '</style></head><body>' + divToPrint + '</body></html>'
    );
    printDocument.close();

    var doPrint = function () {
        eospFit(printDocument);
        printFrame.contentWindow.focus();
        printFrame.contentWindow.print();
    };
    if (printDocument.readyState === 'complete') {
        setTimeout(doPrint, 300);
    } else {
        printFrame.onload = function () { setTimeout(doPrint, 300); };
    }
}

// Shrink .eosp-body until the sheet fits its fixed A4 height (no-op when it already fits).
function eospFit(doc) {
    var sheet = doc.querySelector('.eosp-sheet');
    var body = doc.querySelector('.eosp-body');
    if (!sheet || !body) return;
    body.style.zoom = 1;
    for (var i = 0; i < 12 && sheet.scrollHeight > sheet.clientHeight + 1; i++) {
        var ratio = sheet.clientHeight / sheet.scrollHeight;
        body.style.zoom = Math.max(0.6, (parseFloat(body.style.zoom) || 1) * ratio * 0.99);
    }
}

document.addEventListener('DOMContentLoaded', function () { eospFit(document); });
</script>
    </body>
</html>
<?php } ?>

