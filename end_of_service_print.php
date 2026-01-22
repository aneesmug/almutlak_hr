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
    
    $total_earnings = (float)($eosrow['eos_amount'] ?? 0) + (float)($eosrow['anul_vac_salry'] ?? 0) + (float)($eosrow['curt_month_salry'] ?? 0) + $overtime_earnings;
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
                                <div class="card-box" id="dvContents">
                                    <div class="print-container">
                                        <div class="main-content">
                                            <!-- Header -->
                                            <div class="header-section">
                                                <img src="<?=get_setting($conDB, 'logo')?>" alt="Company Logo" class="logo">
                                                <div class="header-titles">
                                                    <h2>FINAL SETTLEMENT</h2>
                                                    <h3 class="arabic-title">مخالصة نهائية</h3>
                                                </div>
                                            </div>

                                            <!-- Employee Details Section -->
                                            <div class="section">
                                                <h4 class="section-title"><span>Employee Information</span><span class="arabic-label">معلومات الموظف</span></h4>
                                                <div class="details-grid">
                                                    <div class="grid-item">
                                                        <p class="detail-line"><span><strong>Name of Employee:</strong> <?=$emprow['name']; ?></span><span class="arabic-label"><strong>اسم الموظف</strong></span></p>
                                                        <p class="detail-line"><span><strong>Iqama / ID:</strong> <?=$emprow['iqama']; ?></span><span class="arabic-label"><strong>رقم الإقامة</strong></span></p>
                                                        <p class="detail-line"><span><strong>Passport No:</strong> <?=$emprow['passport_number']; ?></span><span class="arabic-label"><strong>رقم الجواز</strong></span></p>
                                                        <p class="detail-line"><span><strong>Date of Birth:</strong> <?=(!empty($emprow['dob'])) ? date('M d, Y', strtotime(str_replace('/', '-', $emprow['dob']))) : "";?> (Age: <?=$years; ?>)</span><span class="arabic-label"><strong>تاريخ الميلاد</strong></span></p>
                                                        <p class="detail-line"><span><strong>Nationality:</strong> <?=$emprow['country_name']; ?></span><span class="arabic-label"><strong>الجنسية</strong></span></p>
                                                    </div>
                                                    <div class="grid-item">
                                                        <p class="detail-line"><span><strong>Employee ID:</strong> <?=$emprow['empid']; ?></span><span class="arabic-label"><strong>الرقم الوظيفي</strong></span></p>
                                                        <p class="detail-line"><span><strong>Department:</strong> <?=$emprow['deptnme']; ?></span><span class="arabic-label"><strong>القسم</strong></span></p>
                                                        <p class="detail-line"><span><strong>Section / Area:</strong> <?=$emprow['sectin_nme']; ?></span><span class="arabic-label"><strong>الشعبة</strong></span></p>
                                                        <p class="detail-line"><span><strong>Date Hired:</strong> <?=date('M d, Y', strtotime(str_replace('/', '-', $emprow['joining_date']))); ?></span><span class="arabic-label"><strong>تاريخ التعيين</strong></span></p>
                                                        <?php if($emprow['status'] == 0): ?>
                                                        <p class="detail-line"><span><strong>Termination Date:</strong> <?=date('M d, Y', strtotime(str_replace('/', '-', $emprow['ter_date'])));?></span><span class="arabic-label"><strong>تاريخ الإنهاء</strong></span></p>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Service Period Section -->
                                            <div class="section">
                                                <h4 class="section-title"><span>Service Period Summary</span><span class="arabic-label">ملخص فترة الخدمة</span></h4>
                                                <div class="details-grid-3">
                                                    <p class="detail-line"><span><strong>Years:</strong> <?=isset($eosrow['t_years']) ? $eosrow['t_years'] : 'N/A'?></span><span class="arabic-label"><strong>سنوات</strong></span></p>
                                                    <p class="detail-line"><span><strong>Months:</strong> <?=isset($eosrow['t_months']) ? $eosrow['t_months'] : 'N/A'?></span><span class="arabic-label"><strong>أشهر</strong></span></p>
                                                    <p class="detail-line"><span><strong>Days:</strong> <?=isset($eosrow['t_days']) ? $eosrow['t_days'] : 'N/A'?></span><span class="arabic-label"><strong>أيام</strong></span></p>
                                                </div>
                                                <p class="service-reason detail-line"><span><strong>End of Service Reason:</strong> <?=isset($eosrow['leaving_reason']) ? $eosrow['leaving_reason'] : 'N/A'?></span><span class="arabic-label"> <?=isset($eosrow['leaving_reason_ar']) ? $eosrow['leaving_reason_ar'] : 'N/A'?><strong>سبب نهاية الخدمة</strong></span></p>
                                            </div>

                                            <?php /* ?>
                                            <!-- Salary & Benefits Section -->
                                            <div class="section">
                                                <h4 class="section-title"><span>Salary & Benefits</span><span class="arabic-label">الراتب والمستحقات</span></h4>
                                                <div>
                                                    <?php
                                                        $benefits_map = [
                                                            'basic'     => ['en' => 'Basic Salary', 'ar' => 'الراتب الأساسي'],
                                                            'housing'   => ['en' => 'Housing Allowance', 'ar' => 'بدل سكن'],
                                                            'transport' => ['en' => 'Transportation', 'ar' => 'بدل مواصلات'],
                                                            'food'      => ['en' => 'Food Allowance', 'ar' => 'بدل طعام'],
                                                            'misc'      => ['en' => 'Misc Allowance', 'ar' => 'بدل متنوع'],
                                                            'cashier'   => ['en' => 'Cashier Allowance', 'ar' => 'بدل صراف'],
                                                            'fuel'      => ['en' => 'Fuel Allowance', 'ar' => 'بدل وقود'],
                                                            'tel'       => ['en' => 'Telephone Allowance', 'ar' => 'بدل هاتف'],
                                                            'guard'     => ['en' => 'Guard Allowance', 'ar' => 'بدل حراسة'],
                                                            'other'     => ['en' => 'Other Allowances', 'ar' => 'بدلات أخرى'],
                                                        ];

                                                        $active_benefits_en = [];
                                                        $active_benefits_ar = [];

                                                        if (!empty($salaryrow)) {
                                                            foreach ($benefits_map as $key => $labels) {
                                                                $value = (float)($salaryrow[$key] ?? 0);
                                                                if ($value > 0) {
                                                                    $active_benefits_en[] = '<div class="benefit-item"><div class="benefit-label">' . $labels['en'] . '</div><div class="benefit-value">' . number_format($value, 2) . '</div></div>';
                                                                    $active_benefits_ar[] = '<div class="benefit-item"><div class="benefit-label">' . $labels['ar'] . '</div><div class="benefit-value">' . number_format($value, 2) . '</div></div>';
                                                                }
                                                            }
                                                        }
                                                    ?>
                                                    <div class="detail-line">
                                                        <div class="benefits-row"><?= implode('', $active_benefits_en) ?></div>
                                                        <div class="benefits-row arabic-label" style="justify-content: flex-end;"><?= implode('', array_reverse($active_benefits_ar)) ?></div>
                                                    </div>
                                                    <p class="label-pair"><span><strong>TOTAL SALARY:</strong> <?=number_format($total_salary, 2)?></span><span class="arabic-label"><strong>إجمالي الراتب</strong></span></p>
                                                </div>
                                            </div>
                                            <?php */ ?>


                                            <!-- Assets for Clearance Section -->
                                            <?php if (!empty($assigned_assets)): ?>
                                            <div class="section">
                                                <h4 class="section-title"><span>Assets for Clearance</span><span class="arabic-label">الأصول للتسليم</span></h4>
                                                <table class="clearance-table">
                                                    <thead>
                                                        <tr>
                                                            <th><span class="label-pair"><span>Asset Type</span><span class="arabic-label">نوع الأصل</span></span></th>
                                                            <th><span class="label-pair"><span>Serial No.</span><span class="arabic-label">الرقم التسلسلي</span></span></th>
                                                            <th><span class="label-pair"><span>Assigned Date</span><span class="arabic-label">تاريخ التعيين</span></span></th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <?php foreach ($assigned_assets as $asset): ?>
                                                        <tr>
                                                            <td><?= htmlspecialchars($asset['asset_name']); ?></td>
                                                            <td><?= htmlspecialchars($asset['serial_number']); ?></td>
                                                            <td><?= htmlspecialchars($asset['assigned_date']); ?></td>
                                                        </tr>
                                                        <?php endforeach; ?>
                                                    </tbody>
                                                </table>
                                            </div>
                                            <?php endif; ?>

                                            <!-- Outstanding Loans Section -->
                                            <?php if (!empty($outstanding_loans)): ?>
                                            <div class="section">
                                                <h4 class="section-title"><span>Outstanding Loans</span><span class="arabic-label">السلف المستحقة</span></h4>
                                                <table class="clearance-table">
                                                    <thead>
                                                        <tr>
                                                            <th><span class="label-pair"><span>Loan Type</span><span class="arabic-label">نوع السلفة</span></span></th>
                                                            <th><span class="label-pair"><span>Total Amount</span><span class="arabic-label">المبلغ الإجمالي</span></span></th>
                                                            <th><span class="label-pair"><span>Remaining</span><span class="arabic-label">المتبقي</span></span></th>
                                                            <th><span class="label-pair"><span>Status</span><span class="arabic-label">الحالة</span></span></th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <?php foreach ($outstanding_loans as $loan): ?>
                                                        <tr>
                                                            <td><?= htmlspecialchars(ucfirst($loan['loan_type'])); ?></td>
                                                            <td class="text-right"><?= number_format($loan['total_payable'], 2); ?></td>
                                                            <td class="text-right"><?= number_format($loan['remaining_balance'], 2); ?></td>
                                                            <td><?= htmlspecialchars($loan['status']); ?></td>
                                                        </tr>
                                                        <?php endforeach; ?>
                                                    </tbody>
                                                </table>
                                            </div>
                                            <?php endif; ?>


                                            <!-- Financial Settlement Section -->
                                            <div class="section">
                                                <h4 class="section-title"><span>Financial Settlement</span><span class="arabic-label">التسوية المالية</span></h4>
                                                <table class="financial-table">
                                                    <tbody>
                                                        <tr>
                                                            <td><span class="label-pair"><span>End of Service Amount (EOS)</span><span class="arabic-label">مبلغ نهاية الخدمة</span></span></td>
                                                            <td class="text-right"><?=number_format((float)($eosrow['eos_amount'] ?? 0), 2)?></td>
                                                        </tr>
                                                        <tr>
                                                            <td><span class="label-pair"><span>Vacation Balance (<?= number_format((float)($eosrow['anul_vac_days'] ?? 0), 2)?> days)</span><span class="arabic-label">(أيام <?= number_format((float)($eosrow['anul_vac_days'] ?? 0), 2)?>) رصيد الإجازات</span></span></td>
                                                            <td class="text-right"><?=number_format((float)($eosrow['anul_vac_salry'] ?? 0), 2)?></td>
                                                        </tr>
                                                        <tr>
                                                            <td><span class="label-pair"><span>Salary for Last Month (<?=($eosrow['curt_month_days'] ?? 'N/A')?> days)</span><span class="arabic-label">(أيام <?=($eosrow['curt_month_days'] ?? 'N/A')?>) راتب الشهر الأخير</span></span></td>
                                                            <td class="text-right"><?=number_format($last_month_salary_paid, 2)?></td>
                                                        </tr>
                                                        
                                                            <?php if ($overtime_hours > 0): ?>
                                                            <tr>
                                                                <td><span class="label-pair"><span>Overtime (Hours) - <?=number_format($overtime_hours, 2)?> hrs</span><span class="arabic-label">ساعات <?=number_format($overtime_hours, 2)?> - العمل الإضافي (ساعات)</span></span></td>
                                                                <td class="text-right text-success">+<?=number_format($overtime_hourly_rate * $overtime_hours, 2)?></td>
                                                            </tr>
                                                            <?php endif; ?>
                                                            
                                                            <?php if ($overtime_days > 0): ?>
                                                            <tr>
                                                                <td><span class="label-pair"><span>Overtime (Days) - <?=number_format($overtime_days, 2)?> days</span><span class="arabic-label">أيام <?=number_format($overtime_days, 2)?> - العمل الإضافي (أيام)</span></span></td>
                                                                <td class="text-right text-success">+<?=number_format($overtime_hourly_rate * 8 * $overtime_days, 2)?></td>
                                                            </tr>
                                                            <?php endif; ?>
                                                        
                                                        <tr style="background-color: #f8f9fa !important;">
                                                            <td colspan="2"><span class="label-pair"><span><strong>Deductions</strong></span><span class="arabic-label"><strong>الخصومات</strong></span></span></td>
                                                        </tr>

                                                        <?php if ($gosi_deduction > 0.01): ?>
                                                        <tr>
                                                            <td><span class="label-pair" style="padding-left: 15px;"><span>GOSI Deduction</span><span class="arabic-label">خصم التأمينات</span></span></td>
                                                            <td class="text-right text-danger">-<?=number_format($gosi_deduction, 2)?></td>
                                                        </tr>
                                                        <?php endif; ?>

                                                        <?php if ($absent_days > 0): ?>
                                                        <tr>
                                                            <td><span class="label-pair" style="padding-left: 15px;"><span>Absent Days - <?=$absent_days?> days</span><span class="arabic-label">أيام <?=$absent_days?> - أيام الغياب</span></span></td>
                                                            <td class="text-right text-danger">-<?=number_format($absent_deduction_amount, 2)?></td>
                                                        </tr>
                                                        <?php endif; ?>
                                                        
                                                        <?php if ($deduction_hours > 0): ?>
                                                        <tr>
                                                            <td><span class="label-pair" style="padding-left: 15px;"><span>Deduction (Hours) - <?=number_format($deduction_hours, 2)?> hrs</span><span class="arabic-label">ساعات <?=number_format($deduction_hours, 2)?> - خصم (ساعات)</span></span></td>
                                                            <td class="text-right text-danger">-<?=number_format($hourly_deduction_amount, 2)?></td>
                                                        </tr>
                                                        <?php endif; ?>

                                                        <?php if ($loan_deduction > 0.01): ?>
                                                        <tr>
                                                            <td><span class="label-pair" style="padding-left: 15px;"><span>Loan / Other Deductions</span><span class="arabic-label">خصم السلف / أخرى</span></span></td>
                                                            <td class="text-right text-danger">-<?=number_format($loan_deduction, 2)?></td>
                                                        </tr>
                                                        <?php endif; ?>
                                                        
                                                        <?php if ($gosi_deduction < 0.01 && $total_absence_deductions < 0.01 && $loan_deduction < 0.01) : ?>
                                                        <tr>
                                                            <td><span class="label-pair" style="padding-left: 15px;"><span>Total Deductions</span><span class="arabic-label">إجمالي الخصومات</span></span></td>
                                                            <td class="text-right text-danger">-0.00</td>
                                                        </tr>
                                                        <?php endif; ?>

                                                        <tr class="net-payment-row">
                                                            <td><span class="label-pair"><strong>NET PAYMENT DUE</strong><strong class="arabic-label">صافي المبلغ المستحق</strong></span></td>
                                                            <td class="text-right"><strong><?=number_format($net_payment, 2)?> SAR</strong></td>
                                                        </tr>
                                                    </tbody>
                                                </table>
                                            </div>
                                            
                                            <!-- Acknowledgment Section -->
                                            <div class="section acknowledgment-section">
                                                <p>I acknowledge and undertake, the employee / <span class="text-danger"><?=$emprow['name']?></span> .ID No <span class="text-danger"><?=$emprow['iqama']?></span>. I have received all of my statutory dues from overtime and wages according to the Labor Law and the Workers from Al-Mutlak Trading from the beginning of their work until the date of leaving work.</p>
                                                <p class="arabic-text">أقر وأتعهد أنا الموظف / <span class="text-danger"><?=$emprow['name']?></span>  برقم هوية اقامة / <span class="text-danger"><?=$emprow['iqama']?></span> لقد استلمت كافة مستحقاتي القانونية من أجور العمل الإضافي والأجور حسب قانون العمل والعمال في شركة المطلق للتجارة منذ بداية عملهم حتى تاريخ ترك العمل. </p>
                                            </div>
                                        </div>
                                        <!-- Footer Section -->
                                        <div class="footer-section">
                                            <div class="signature-box">
                                                <p class="signature-line">_________________________</p>
                                                <p class="detail-line"><span><strong>Employee Signature</strong></span><span class="arabic-label"><strong>توقيع الموظف</strong></span></p>
                                                <p class="detail-line"><span><strong>Date</strong></span><span class="arabic-label"><strong>التاريخ</strong></span>:</p>
                                            </div>
                                            <div class="signature-box">
                                                <p class="signature-line">_________________________</p>
                                                <p class="detail-line"><span><strong>Company Representative</strong></span><span class="arabic-label"><strong>ممثل الشركة</strong></span></p>
                                                <p class="detail-line"><span><strong>Date</strong></span><span class="arabic-label"><strong>التاريخ</strong></span>:</p>
                                            </div>
                                        </div>
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
        <script src="assets/js/jquery.app.js"></script>

		<script type="text/javascript">
function printDiv() {
    var divToPrint = document.getElementById('dvContents').innerHTML;
    var printFrame = document.createElement('iframe');

    printFrame.style.display = 'none';
    document.body.appendChild(printFrame);

    var printDocument = printFrame.contentWindow.document;
    printDocument.open();
    printDocument.write(`
        <html>
            <head>
                <title>Print</title>
                <style>
                    body {
                        -webkit-print-color-adjust: exact !important;
                        color-adjust: exact !important;
                        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
                        color: #333;
                        line-height: 1.5;
                    }
                    .print-container {
                        display: flex;
                        flex-direction: column;
                        justify-content: space-between;
                        min-height: 267mm; /* A4 height (297mm) minus margins */
                    }
                    .main-content {
                        flex-grow: 1;
                    }
                    .header-section {
                        display: flex;
                        justify-content: space-between;
                        align-items: center;
                        border-bottom: 3px solid #4a4a4a;
                        padding-bottom: 5px;
                    }
                    .logo {
                        height: 60px;
                    }
                    .header-titles h2 {
                        margin: 0;
                        font-weight: bold;
                        font-size: 22px;
                        color: #000;
                        text-align: left;
                    }
                    .header-titles .arabic-title {
                        margin: 0;
                        direction: rtl;
                        font-size: 20px;
                        text-align: right;
                    }
                    .section {
                        margin-top: 5px;
                    }
                    .section-title {
                        display: flex;
                        justify-content: space-between;
                        align-items: baseline;
                        background-color: #343a40 !important;
                        color: #fff !important;
                        padding: 6px 6px;
                        border-radius: 5px;
                        font-weight: bold;
                        font-size: 13px;
                    }
                    .arabic-label {
                        direction: rtl;
                        text-align: right;
                    }
                    .details-grid, .details-grid-3 {
                        display: grid;
                        gap: 5px 20px;
                        margin-top: 5px;
                        font-size: 13px;
                    }
                    .details-grid {
                        grid-template-columns: 1fr 1fr;
                    }
                    .details-grid-3 {
                        grid-template-columns: repeat(3, 1fr);
                    }
                    .details-grid p, .details-grid-3 p {
                        margin-bottom: 3px;
                    }
                    .detail-line {
                        display: flex;
                        justify-content: space-between;
                        align-items: baseline;
                        border-bottom: 1px dotted #ccc;
                        padding-bottom: 1px;
                    }
                    .service-reason {
                        margin-top: 5px;
                        font-size: 13px;
                    }
                    .financial-table, .clearance-table {
                        width: 100%;
                        margin-top: 5px;
                        border-collapse: collapse;
                        font-size: 13px;
                    }
                    .financial-table td, .clearance-table td, .clearance-table th {
                        padding: 8px;
                        border: 1px solid #dee2e6 !important;
                    }
                    .clearance-table th {
                        background-color: #f8f9fa !important;
                        text-align: left;
                    }
                    .label-pair {
                        display: flex;
                        justify-content: space-between;
                        align-items: baseline;
                        width: 100%;
                    }
                    .financial-table tbody tr:nth-child(odd) {
                        background-color: #f8f9fa !important;
                    }
                    .net-payment-row {
                        font-weight: bold;
                        font-size: 15px;
                        background-color: #e9ecef !important;
                    }
                    .text-right { text-align: right; }
                    .text-danger { color: #dc3545 !important; }
                    .acknowledgment-section {
                        margin-top: 10px;
                        border-top: 1px solid #dee2e6;
                        padding-top: 10px;
                        font-size: 12px;
                        line-height: 1.4;
                    }
                    .arabic-text {
                        direction: rtl;
                        text-align: right;
                        margin-top: 10px;
                    }
                    .footer-section {
                        display: flex;
                        justify-content: space-between;
                        padding-top: 15px;
                        page-break-inside: avoid;
                        flex-shrink: 0;
                    }
                    .signature-box {
                        text-align: center;
                        width: 45%;
                        font-size: 13px;
                    }
                    .signature-line {
                        margin-bottom: 5px;
                    }
                    .benefits-row {
                        display: flex;
                        flex-wrap: nowrap;
                        gap: 10px;
                        align-items: stretch;
                        width: 100%;
                    }
                    .benefit-item {
                        text-align: center;
                        padding: 2px 5px;
                        font-size: 12px;
                        line-height: 1.2;
                        border: 1px solid #e9ecef !important;
                        border-radius: 4px;
                    }
                    .benefit-label {
                        font-weight: bold;
                        font-size: 9px;
                        color: #495057;
                    }
                    .benefit-value {
                        font-weight: normal;
                        border-top: 1px solid #e9ecef !important;
                        margin-top: 2px;
                        padding-top: 2px;
                    }
                    @page {
                        size: A4;
                        margin: 10mm;
                    }
                    @media print {
                        html, body {
                            width: 210mm;
                            height: 297mm;
                            margin: 0;
                            padding: 0;
                        }
                    }
                </style>
            </head>
            <body onload="window.focus(); window.print(); window.close();">
                ${divToPrint}
            </body>
        </html>
    `);
    printDocument.close();
}
</script>
    </body>
</html>
<?php } ?>

