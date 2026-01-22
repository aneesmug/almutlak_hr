<?php
/****************************************************************
 * MODIFICATION SUMMARY:
 * 1. REVISED APPROVAL TIMELINE: Overhauled the approval timeline logic to accurately reflect the new multi-step workflow (DPT_Manager -> HR_Assistant -> HR_Manager -> IT -> GM).
 * 2. DYNAMIC WORKFLOW PATH: The timeline now dynamically adjusts its path, correctly skipping steps for HR employees and omitting the IT step if the employee has no assigned assets.
 * 3. IMPROVED STATUS VISUALS: Corrected the timeline rendering to properly show completed steps as 'approved', the current step as 'pending', and future steps with a neutral style.
 * 4. ENHANCED LABELS & ICONS: Updated the map of approval steps with clearer labels and more distinct icons for each stage of the process.
 * 5. EMERGENCY LEAVE: Financial details are not calculated and the "Payment Details" section is hidden if the fly_type is 'emergency'.
 * 6. END OF SERVICE SALARY: Payment Details section is hidden when vacation_salary_type is 'end_of_service', showing info message instead.
 ****************************************************************/

require_once __DIR__ . '/includes/session_check.php';

// Restrict access: Employees cannot view this detailed report page
// if (isset($isEmployee) && $isEmployee === true) {
//     header("Location: ./profile.php");
//     exit();
// }

$query = mysqli_query($conDB, "SELECT * FROM `admin_login` WHERE `id_iqama`='" . $username . "'");
if (mysqli_num_rows($query) == 1) {
    include("./includes/avatar_select.php");

    // 1. Get and validate the IDs from the URL
    $vacation_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    $emp_id = isset($_GET['emp_id']) ? $_GET['emp_id'] : '';

    if ($vacation_id === 0 || empty($emp_id)) {
        die("Invalid request parameters.");
    }

    // 2. MODIFIED: Fetch all data with a single query (added vacation_salary_type, overtime_hours, deduction_hours, deduction_days, payroll_note, accommodation_provided, transportation_provided, contract_period)
    $sql = "SELECT 
                v.*, 
                v.fly_type as raw_fly_type,
                v.attachment_path,
                v.vacation_salary_type,
                v.departure_date,
                v.arrival_date,
                v.overtime_hours,
                v.deduction_hours,
                v.deduction_days,
                v.payroll_note,
                v.accommodation_provided,
                v.transportation_provided,
                e.name as employee_name,
                e.avatar,
                e.joining_date,
                e.gosi,
                e.country as country_id,
                e.passport_number,
                e.passport_exp,
                d.dep_nme AS `deptname`,
                d.dep_nme_ar AS `deptname_ar`,
                s.section_name,
                c.name AS `country_name`,
                re.name AS `replacement_person_name`,
                cp.vac_period AS contract_vacation_days,
                cp.period AS contract_period,
                CASE 
                    WHEN `v`.`fly_type` = 'annual' THEN 'Annual Vacation' 
                    WHEN `v`.`fly_type` = 'emergency' THEN 'Emergency Vacation'
                    ELSE ''
                END AS `fly_type`
            FROM emp_vacation v
            JOIN employees e ON v.emp_id = e.emp_id
            LEFT JOIN employees re ON v.replacement_person = re.emp_id
            LEFT JOIN department d ON e.dept = d.id
            LEFT JOIN section s ON e.sectin_nme = s.id
            LEFT JOIN countries c ON e.country = c.id
            LEFT JOIN contract_period cp ON e.vac_period = cp.id
            WHERE v.id = ? AND v.emp_id = ?";

    $stmt = $conDB->prepare($sql);
    $stmt->bind_param("is", $vacation_id, $emp_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $request = $result->fetch_assoc();
    $stmt->close();

    if (!$request) {
        die("Vacation request not found.");
    }
    
    // 3. Fetch Salary Details
    $salary_sql = "SELECT * FROM `emp_salary` WHERE `emp_id`= ? ORDER BY id DESC LIMIT 1";
    $stmt_salary = $conDB->prepare($salary_sql);
    $stmt_salary->bind_param("s", $emp_id);
    $stmt_salary->execute();
    $salary_result = $stmt_salary->get_result();
    $salary = $salary_result->fetch_assoc();
    $stmt_salary->close();

    // Fetch employee's assigned assets
    $assets_sql = "SELECT a.name as asset_name, ea.serial_number 
                   FROM employee_assets ea 
                   JOIN assets a ON ea.asset_id = a.id 
                   WHERE ea.emp_id = ? AND ea.status = 'Assigned'";
    $stmt_assets = $conDB->prepare($assets_sql);
    $stmt_assets->bind_param("s", $emp_id);
    $stmt_assets->execute();
    $assets_result = $stmt_assets->get_result();
    $assigned_assets = [];
    while ($row = $assets_result->fetch_assoc()) {
        $assigned_assets[] = $row;
    }
    $stmt_assets->close();

    // 4. Calculate Vacation Salary & Fees
    $vacation_salary = 0;
    $working_days_salary = 0;
    $gosi_deduction = 0;
    $ticket_fee = 0;
    $permit_fee = 0;
    $overtime_amount = 0;
    $deduction_amount = 0;
    
    // Get approved vacation days from the request (vacdays = approved days)
    $approved_days = (float)($request['vacdays'] ?? 0);
    $applied_days = $approved_days; // Use approved days for calculations
    
    // Get payroll overtime/deduction values
    $overtime_hours = (float)($request['overtime_hours'] ?? 0);
    $deduction_hours = (float)($request['deduction_hours'] ?? 0);
    $deduction_days = (float)($request['deduction_days'] ?? 0);
    $payroll_note = $request['payroll_note'] ?? '';
    
    // Determine vacation salary type
    $vacation_salary_type = $request['vacation_salary_type'] ?? 'payroll';
    $vac_type = $request['vac_type'] ?? '';
    $fly_type = $request['raw_fly_type'] ?? '';
    
    // === PAYROLL LOGIC RULES ===
    // 1. Fly + Annual: Employee EXCLUDED from payroll → Show vacation salary (if vacation_salary_type = payroll)
    // 2. Local Vacation + Annual: Employee ACTIVE in payroll → NO vacation salary, only deduct days
    // 3. Encashment: Show ONLY encashment amount (no working days salary)
    // 4. Emergency: Not payable
    // 5. Other leave types: Not payable
    
    $is_fly_annual = ($vac_type === 'Fly' && $fly_type === 'annual');
    $is_local_annual = ($vac_type === 'Local Vacation' && $fly_type === 'annual');
    $is_encashment = ($vac_type === 'Encashed');
    $is_emergency = ($fly_type === 'emergency');
    
    // Non-payable leave types
    $non_payable_leave_types = ['Sick Leave', 'Casual Leave', 'Maternity Leave', 'Compassionate Leave', 'Business Trip', 'Compensatory Leave'];
    $is_non_payable_leave = in_array($vac_type, $non_payable_leave_types);
    
    // Determine if this vacation gets any payment calculation
    $calculate_payments = !$is_non_payable_leave && !$is_emergency && !$is_local_annual;
    
    // Calculate actual days in the vacation month
    $days_in_month = 30; // Fixed 30 days for all calculations
    
    if ($calculate_payments && $salary) {
        $basic_salary = (float)($salary['basic'] ?? 0);
        $total_monthly_salary = $basic_salary + ($salary['housing'] ?? 0) + ($salary['transport'] ?? 0) + ($salary['food'] ?? 0) + ($salary['misc'] ?? 0) + ($salary['cashier'] ?? 0) + ($salary['fuel'] ?? 0) + ($salary['tel'] ?? 0) + ($salary['other'] ?? 0) + ($salary['guard'] ?? 0);
        $daily_rate = $total_monthly_salary / $days_in_month;
        
        // --- CALCULATE OVERTIME AND DEDUCTIONS (EOS Logic) ---
        $DEDUCTION_BASE = $total_monthly_salary;
        $dailyRateDeduction = $DEDUCTION_BASE / $days_in_month;
        $hourlyRateDeduction = $dailyRateDeduction / 8;
        
        // OVERTIME CALCULATION (per EOS file):
        // per-hour overtime rate = (basic/240)/2 + (full/240)
        $overtimeHourlyRate = (($basic_salary / 240) / 2) + ($total_monthly_salary / 240);
        
        // Calculate amounts
        if ($overtime_hours > 0) {
            $overtime_amount = $overtimeHourlyRate * $overtime_hours;
        }
        
        if ($deduction_hours > 0 || $deduction_days > 0) {
            $deduction_hours_amount = $hourlyRateDeduction * $deduction_hours;
            $deduction_days_amount = $dailyRateDeduction * $deduction_days;
            $deduction_amount = $deduction_hours_amount + $deduction_days_amount;
        }

        // === WORKING DAYS SALARY ===
        // Only for Fly + Annual (employee excluded from payroll)
        // NOT for Encashment or Local Vacation + Annual
        if ($is_fly_annual && !empty($request['start_date'])) {
            $start_date_obj = new DateTime($request['start_date']);
            $working_days = (int)$start_date_obj->format('d') - 1; // Days before vacation starts
            $working_days_salary = $daily_rate * $working_days;
        }

        // === VACATION SALARY ===
        // Only for Fly + Annual AND vacation_salary_type = 'payroll'
        // NOT for Local Vacation + Annual (stays in payroll)
        // NOT for Encashment (handled separately)
        // IMPORTANT: Calculation is based on APPROVED DAYS (vacdays field)
        // Formula: (daily rate × vacation days) = (total_monthly_salary / 30) × approved_days
        if ($is_fly_annual && $vacation_salary_type === 'payroll') {
            // Calculate vacation salary as daily rate × vacation days
            $vacation_salary = $daily_rate * $approved_days;
            
            // Track salaries_earned for display purposes (how many full contract periods)
            $contract_days = isset($request['contract_vacation_days']) ? (float)$request['contract_vacation_days'] : 0;
            $salaries_earned = 1; // Default to 1 for display
            
            if ($contract_days > 0) {
                // Extract period years from contract_period string (e.g., "2 Years - 60" → 2)
                $contract_period_str = $request['contract_period'] ?? '';
                $period_years = 1; // default to 1 year
                
                if (!empty($contract_period_str) && preg_match('/(\d+)\s+Years?/', $contract_period_str, $matches)) {
                    $period_years = (int)$matches[1];
                }
                
                // Calculate how many full salaries represented (for display only)
                // Formula: (approved_days / contract_days) * period_years
                $salaries_earned = floor($approved_days / $contract_days) * $period_years;
                if ($salaries_earned < 1) $salaries_earned = 1; // Always show at least 1
            }
        }

        // === GOSI DEDUCTION ===
        if (isset($request['country_id']) && $request['country_id'] == 191 && isset($request['gosi']) && is_numeric($request['gosi'])) {
            $gosi_percentage = (float)$request['gosi'];
            if ($is_fly_annual) {
                // For Fly + Annual: Apply GOSI on working days + vacation salary
                $gosi_base = $working_days_salary + $vacation_salary;
                $gosi_deduction = ($gosi_base * $gosi_percentage) / 100;
            } elseif ($is_encashment) {
                // For Encashment: GOSI calculated separately in encashment section
                $gosi_deduction = 0;
            }
        }
        
        // === TICKET AND PERMIT FEES ===
        // Only for Fly + Annual (non-Saudi employees)
        if ($is_fly_annual && $request['country_id'] != 191) {
            $ticket_fee = $request['ticket_pay'] ?? 0;
            $permit_fee = $request['permit_fee'] ?? 0;
        }
    }

    // === TOTAL PAYABLE CALCULATION ===
    if ($is_encashment) {
        // Encashment: Total is handled in encashment section
        $total_payable = 0;
    } elseif ($is_fly_annual) {
        // Fly + Annual: Working days + vacation salary + ticket + permit + overtime - deductions - GOSI
        // $total_payable = ($working_days_salary + $vacation_salary) + $ticket_fee + $permit_fee + $overtime_amount - $deduction_amount - $gosi_deduction;
        $total_payable = ($working_days_salary + $vacation_salary)  + $overtime_amount - $deduction_amount - $gosi_deduction;
    } else {
        // Local Vacation + Annual or other: No payment (stays in payroll)
        $total_payable = 0;
    }

    // Approval Timeline Logic - NEW CHAIN APPROVAL SYSTEM
    // Fetch approval chain for this request
    $request_inv_no = $request['request_inv_no'] ?? '';
    $current_status = $request['current_status'] ?? 'pending_approval';
    
    // Initialize approval chain array
    $approval_chain = [];
    
    // Only fetch chain if we have a request_inv_no (new system)
    if (!empty($request_inv_no)) {
        // Get the request type ID for vacation_request
        $type_query = mysqli_query($conDB, "SELECT `id` FROM `approval_request_types` WHERE `type_name` = 'vacation_request' LIMIT 1");
        if ($type_query && mysqli_num_rows($type_query) > 0) {
            $request_type_row = mysqli_fetch_assoc($type_query);
            $request_type_id = (int)$request_type_row['id'];
            mysqli_free_result($type_query);
            
            // Fetch the approval chain
            if ($request_type_id > 0) {
          // Include approver's department to classify clearance by department (more reliable than user_type)
          $chain_sql = "SELECT ra.*, 
                       e.name AS approver_name, 
                       e.emp_id AS approver_emp_id, 
                       al.user_type AS approver_role,
                       d2.dep_nme AS approver_dept_name
                   FROM request_approvers ra
                   LEFT JOIN employees e ON ra.approver_id = e.emp_id
                   LEFT JOIN admin_login al ON e.emp_id = al.emp_id
                   LEFT JOIN department d2 ON e.dept = d2.id
                   WHERE ra.request_inv_no = ? AND ra.request_type_id = ?
                   ORDER BY ra.approval_level ASC";
                $stmt_chain = $conDB->prepare($chain_sql);
                if ($stmt_chain) {
                    $stmt_chain->bind_param("si", $request_inv_no, $request_type_id);
                    $stmt_chain->execute();
                    $chain_result = $stmt_chain->get_result();
                    while ($chain_row = $chain_result->fetch_assoc()) {
                        $approval_chain[] = $chain_row;
                    }
                    $stmt_chain->close();
                }
            }
        }
    }

?>

    <!doctype html>
    <html lang="<?= $current_lang ?? 'en' ?>" <?= ($is_rtl ?? false) ? 'dir="rtl"' : '' ?>>

    <head>
        <meta charset="utf-8" />
        <title><?= $site_title ?> - Vacation Report</title>
        <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
        <meta content="Anees Afzal" name="author" />
        <meta http-equiv="X-UA-Compatible" content="IE=edge" />
        <link rel="shortcut icon" href="<?=get_setting($conDB, 'favicon')?>">
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
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
        <style>
            :root {
                --primary-color: #4a90e2;
                --text-color: #333;
                --muted-color: #6c757d;
                --border-color: #e9ecef;
                --background-light: #f8f9fa;
                --success-color: #28a745;
                --danger-color: #dc3545;
                --warning-color: #ffc107;
            }
            body.enlarged {
                 background-color: #f4f7f6;
            }
            .report-wrapper {
                font-family: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
                max-width: 800px; /* Reduced width */
                margin: 1rem auto; /* Reduced margin */
                background-color: #fff;
                border-radius: 8px;
                box-shadow: 0 4px 12px rgba(0,0,0,.08);
                color: var(--text-color);
                font-size: 14px; /* Base font size */
            }
            .report-header {
                display: flex;
                justify-content: space-between;
                align-items: center;
                padding: 1rem 1.5rem; /* Reduced padding */
                border-bottom: 1px solid var(--border-color);
            }
            .report-header .logo-container img { max-height: 40px; } /* Reduced logo size */
            .report-header .report-meta { text-align: right; }
            .report-header .report-title { font-size: 1.1rem; font-weight: 600; margin: 0; }
            .report-header .report-subtitle { font-size: 0.8rem; color: var(--muted-color); margin: 0; }
            
            /* RTL Overrides */
            [dir="rtl"] .report-header { flex-direction: row-reverse; }
            [dir="rtl"] .report-header .report-meta { text-align: left; }
            
            .report-body { padding: 1.5rem; } /* Reduced padding */
            
            .employee-banner { display: flex; align-items: center; background-color: var(--background-light); padding: 1rem; border-radius: 6px; margin-bottom: 1.5rem; border: 1px solid var(--border-color); }
            .employee-banner .avatar { width: 60px; height: 60px; border-radius: 50%; margin-right: 1rem; }
            .employee-banner .info { flex: 1; }
            .employee-banner .info h4 { font-weight: 600; margin: 0 0 0.2rem 0; font-size: 1.1rem; }
            .employee-banner .info p { color: var(--muted-color); margin: 0; font-size: 0.85rem; }
            
            /* RTL Overrides */
            [dir="rtl"] .employee-banner { flex-direction: row-reverse; }
            [dir="rtl"] .employee-banner .avatar { margin-right: 0; margin-left: 1rem; order: 2; }
            [dir="rtl"] .employee-banner .info { order: 1; text-align: right; }

            .report-section { margin-bottom: 1.5rem; } /* Reduced margin */
            .section-title { font-weight: 600; color: var(--primary-color); margin-bottom: 1rem; font-size: 1rem; border-bottom: 1px solid var(--border-color); padding-bottom: 0.5rem; }
            .section-title i { margin-right: 0.5rem; }
            [dir="rtl"] .section-title i { margin-right: 0; margin-left: 0.5rem; }

            .grid-details { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; }
            .detail-item .label { font-size: 0.8rem; color: var(--muted-color); margin-bottom: 0.1rem; }
            .detail-item .value { font-weight: 500; font-size: 0.9rem; }
            .detail-item .value.highlight { font-weight: 700; color: var(--success-color); }

            .payment-summary { background-color: var(--background-light); border-radius: 6px; padding: 1rem; border: 1px solid var(--border-color); }
            .payment-summary ul { list-style: none; padding-left: 0; margin-bottom: 0;}
            .payment-summary li { display: flex; justify-content: space-between; align-items: center; padding: .5rem 0; font-size: 0.9rem; border-bottom: 1px solid #e9ecef; }
            .payment-summary li:last-child { border-bottom: none; }
            .payment-summary .total-payable { background-color: #e9f5ec; margin: 1rem -1rem -1rem; padding: 1rem 1rem; border-top: 1px solid #c3e6cb; }
            .payment-summary .total-payable .label { font-weight: 700; color: #155724; }
            .payment-summary .total-payable .value { font-size: 1.1rem; font-weight: 700; color: #155724; }
            
            [dir="rtl"] .payment-summary li { flex-direction: row-reverse; }
            
            .approval-timeline { position: relative; padding-left: 5px; }
            .timeline-item { position: relative; padding-bottom: 1rem; padding-left: 30px; min-height: 20px; }
            .timeline-item:last-child { padding-bottom: 0; }
            .timeline-item::before { content: ''; position: absolute; left: 0; top: 10px; bottom: 0; width: 2px; background: var(--border-color); }
            .timeline-item:last-child::before { display: none; }
            .timeline-item .icon { position: absolute; left: -9px; top: 0; width: 20px; height: 20px; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: #fff; font-size: 10px; border: 2px solid #fff; }
            .timeline-item.approved .icon { background-color: var(--success-color); }
            .timeline-item.pending .icon { background-color: var(--warning-color); }
            .timeline-item.rejected .icon { background-color: var(--danger-color); }
            .timeline-item.future .icon, .timeline-item .icon { background-color: #ced4da; }
            .timeline-item .status { font-weight: 600; line-height: 20px; font-size: 0.9rem; }
            
            [dir="rtl"] .approval-timeline { padding-left: 0; padding-right: 5px; }
            [dir="rtl"] .timeline-item { padding-left: 0; padding-right: 30px; }
            [dir="rtl"] .timeline-item::before { left: auto; right: 0; }
            [dir="rtl"] .timeline-item .icon { left: auto; right: -9px; }
            
            .notes-section { background-color: #fff9e6; border-left: 4px solid var(--warning-color); padding: 1rem; border-radius: 4px; font-size: 0.85rem; }
            
            [dir="rtl"] .notes-section { border-left: none; border-right: 4px solid var(--warning-color); }
            
            .report-footer { padding: 1rem 1.5rem; border-top: 1px solid var(--border-color); margin-top: 1.5rem; }
            .signature-area { display: grid; grid-template-columns: repeat(3, 1fr); gap: 1.5rem; text-align: center; margin-top: 2.5rem; }
            .signature-box { border-top: 1px solid var(--muted-color); padding-top: 0.5rem; }
            .signature-box p { margin: 0; color: var(--muted-color); font-size: 0.8rem; }

            @media print {
                @page { size: A4; margin: 0.5cm; }
                body { background-color: #fff !important; font-size: 12px; }
                .no-print, .left.side-menu, .footer, .topbar { display: none !important; }
                #wrapper, .content-page, .content, .container-fluid { padding: 0 !important; margin: 0 !important; }
                .report-wrapper { max-width: 100%; margin: 0; box-shadow: none; border: none; border-radius: 0; }
                .report-body { padding: 1cm 0.5cm; }
                .employee-banner, .payment-summary, .notes-section { background-color: #f8f9fa !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
                .signature-area { margin-top: 3rem; }
                .report-section { margin-bottom: 1rem; }
            }
        </style>
    </head>

    <body class="enlarged" data-keep-enlarged="true">
        <div id="wrapper">
            <div class="left side-menu no-print">
                <div class="slimscroll-menu" id="remove-scroll">
                    <div class="topbar-left"><a href="dashboard.php" class="logo"><span><img src="<?=get_setting($conDB, 'logo')?>" alt="" height="22"></span><i><img src="<?=get_setting($conDB, 'white_logo')?>" alt="" height="28"></i></a></div>
                    <?php include("./includes/main_menu.php"); ?>
                    <div class="clearfix"></div>
                </div>
            </div>

            <div class="content-page">
                <?php include("./includes/topbar.php"); ?>
                <div class="content">
                    <div class="container-fluid">
                        <div class="text-right no-print mb-3">
                            <a href="javascript:void(0);" onclick="window.print()" class="btn btn-primary waves-effect waves-light"><i class="fa fa-print mr-1"></i> <?= __('print_report') ?></a>
                        </div>
                        
                        <div class="report-wrapper">
                            <div class="report-header">
                                <div class="logo-container"><img src="<?=get_setting($conDB, 'logo')?>" alt="Company Logo"></div>
                                <div class="report-meta">
                                    <h2 class="report-title"><?= __('vacation_request_report') ?></h2>
                                    <p class="report-subtitle"><?= __('request_id') ?>: #<?= display_or_na($request['id'] ?? null); ?></p>
                                </div>
                            </div>

                            <div class="report-body">
                                <div class="employee-banner">
                                    <img src="<?= display_or_na($request['avatar'] ?? 'assets/images/users/avatar-1.jpg'); ?>" alt="Employee Avatar" class="avatar">
                                    <div class="info">
                                        <h4><?= getDisplayName($request['employee_name'] ?? null); ?></h4>
                                        <p><?= __('employee_id') ?>: <?= display_or_na($request['emp_id'] ?? null); ?> | <?= ($is_rtl ?? false ? $request['deptname_ar'] : $request['deptname']); ?><?= getDisplayName($request['section_name']) ? ' / ' . getDisplayName($request['section_name']) : '' ?></p>
                                    </div>
                                </div>

                                <div class="report-section">
                                    <h5 class="section-title"><i class="fa fa-calendar-alt"></i><?= __('vacation_details') ?></h5>
                                    <div class="grid-details">
                                        <div class="detail-item"><span class="label"><?= __('vacation_type') ?></span> <span class="value"><small><?= getDisplayName($request['vac_type'] ?? null); ?><?= getDisplayName($request['fly_type']) ? ' | ' . getDisplayName($request['fly_type']) : '' ?></small></span></div>
                                        <?php if ($request['vac_type'] !== 'Encashed'): ?>
                                        <div class="detail-item"><span class="label"><?= __('start_date') ?></span> <span class="value"><small><?= display_or_na(!empty($request['start_date']) ? date('d M Y', strtotime($request['start_date'])) : null); ?></small></span></div>
                                        <div class="detail-item"><span class="label"><?= __('return_date') ?></span> <span class="value"><small><?= display_or_na(!empty($request['return_date']) ? date('d M Y', strtotime($request['return_date'])) : null); ?></small></span></div>
                                        <?php endif; ?>
                                        <div class="detail-item"><span class="label"><?= __('vacation_days') ?></span> <span class="value highlight"><small><?= display_or_na($request['vacdays'] ?? null); ?> <?= __('days') ?></small></span></div>
                                        <?php 
                                        // Calculate flight days if both departure and arrival dates exist
                                        $flight_days = 0;
                                        if (!empty($request['departure_date']) && !empty($request['arrival_date'])) {
                                            $departure_date_obj = new DateTime($request['departure_date']);
                                            $arrival_date_obj = new DateTime($request['arrival_date']);
                                            $flight_interval = $departure_date_obj->diff($arrival_date_obj);
                                            $flight_days = $flight_interval->days + 1; // Include both departure and arrival days
                                        }
                                        ?>
                                        <?php if (!empty($request['departure_date']) && $request['vac_type'] === 'Fly' && $request['raw_fly_type'] === 'annual'): ?>
                                            <div class="detail-item"><span class="label"><?= __('departure_date') ?></span> <span class="value"><small><?= display_or_na(!empty($request['departure_date']) ? date('d M Y', strtotime($request['departure_date'])) : null); ?></small></span></div>
                                        <?php endif; ?>
                                        <?php if (!empty($request['arrival_date']) && $request['vac_type'] === 'Fly' && $request['raw_fly_type'] === 'annual'): ?>
                                            <div class="detail-item"><span class="label"><?= __('arrival_date') ?></span> <span class="value"><small><?= display_or_na(!empty($request['arrival_date']) ? date('d M Y', strtotime($request['arrival_date'])) : null); ?></small></span></div>
                                        <?php endif; ?>
                                        <?php if ($flight_days > 0 && $ticket_fee > 0): ?>
                                            <div class="detail-item"><span class="label"><?= __('flight_days') ?? 'Flight Days' ?></span> <span class="value highlight"><small><?= display_or_na($flight_days); ?> <?= __('days') ?></small></span></div>
                                        <?php endif; ?>
                                        <div class="detail-item"><span class="label"><?= __('replacement') ?></span> <span class="value"><small><?= getDisplayName(($request['replacement_person_name'] ?? '') !== '' ? $request['replacement_person_name'] : __('not_available')); ?></small></span></div>
                                        
                                        <?php if ($request['vac_type'] === 'Business Trip'): ?>
                                            <div class="detail-item"><span class="label"><?= __('accommodation_provided') ?></span> <span class="value"><small><?= ucfirst($request['accommodation_provided'] ?? 'N/A'); ?></small></span></div>
                                            <div class="detail-item"><span class="label"><?= __('transportation_provided') ?></span> <span class="value"><small><?= ucfirst($request['transportation_provided'] ?? 'N/A'); ?></small></span></div>
                                        <?php endif; ?>
                                        
                                        <div class="detail-item"><span class="label"><?= __('requested_on') ?></span> <span class="value"><small><?= display_or_na(!empty($request['created_at']) ? date('d M Y, h:i A', strtotime($request['created_at'])) : null); ?></small></span></div>
                                         <?php if (!empty($request['attachment_path'])): ?>
                                            <div class="detail-item"><span class="label"><?= __('attachment') ?></span> <span class="value"><small><a href="<?= display_or_na($request['attachment_path']); ?>" target="_blank"><?= __('view_document') ?></a></small></span></div>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <?php 
                                // Hide payment details if:
                                // 1. Emergency vacation, OR
                                // 2. Encashment request (but show custom encashment section), OR
                                // 3. Fly + Annual OR Local Vacation + Annual (is_deductible = 0, employee stays in full payroll)
                                // Note: end_of_service type will show payment details but only working days salary
                                $is_encashment_request = (trim(strtolower($request['vac_type'] ?? '')) === 'encashed');
                                $is_annual_non_deductible = (
                                    ($request['vac_type'] === 'Fly' || $request['vac_type'] === 'Local Vacation') && 
                                    $request['raw_fly_type'] === 'annual' && 
                                    isset($request['is_deductible']) && 
                                    $request['is_deductible'] == 0
                                );
                                $hide_payment_details = ($request['raw_fly_type'] === 'emergency') || $is_encashment_request || $is_annual_non_deductible;
                                ?>
                                
                                    <?php if ($is_encashment_request): 
                                        // Get encashment details from database
                                        $encashment_amount = (float)($request['encashment_amount'] ?? 0);
                                        $days_encashed = (float)($request['vacdays'] ?? 0);
                                        $daily_rate_display = ($days_encashed > 0) ? ($encashment_amount / $days_encashed) : 0;
                                        
                                        // Calculate GOSI deduction for encashment if applicable
                                        $encash_gosi = 0;
                                        if (isset($request['country_id']) && $request['country_id'] == 191 && isset($request['gosi']) && is_numeric($request['gosi'])) {
                                            $gosi_percentage = (float)$request['gosi'];
                                            $encash_gosi = ($encashment_amount * $gosi_percentage) / 100;
                                        }
                                        $net_encashment = $encashment_amount - $encash_gosi;
                                    ?>
                                    <div class="report-section">
                                        <h5 class="section-title"><i class="fa fa-coins"></i><?= __('encashment_payment_details') ?? 'Encashment Payment Details' ?></h5>
                                        <div class="alert alert-success mb-3">
                                            <i class="fa fa-info-circle"></i> <strong><?= __('vacation_balance_encashment') ?? 'Vacation Balance Encashment' ?></strong>
                                            <p class="mb-0 mt-2"><?= __('employee_opted_encash_message') ?? 'The employee has chosen to encash vacation days instead of taking time off.' ?></p>
                                        </div>
                                        <div class="payment-summary">
                                            <ul>
                                                <li>
                                                    <div>
                                                        <span class="label"><?= __('encashed_vacation_days') ?? 'Encashed Vacation Days' ?></span>
                                                        <small class="text-muted d-block"><?= __('days_converted_to_cash') ?? 'Days converted to cash payment' ?></small>
                                                    </div>
                                                    <span class="value"><?= number_format($days_encashed, 2); ?> <?= __('day_s') ?? 'Days' ?></span>
                                                </li>
                                                <li>
                                                    <div>
                                                        <span class="label"><?= __('daily_salary_rate') ?? 'Daily Salary Rate' ?></span>
                                                        <small class="text-muted d-block"><?= __('monthly_salary_divided_30') ?? 'Monthly salary ÷ 30 days' ?></small>
                                                    </div>
                                                    <span class="value"><?= number_format($daily_rate_display, 2); ?> SAR</span>
                                                </li>
                                                <li>
                                                    <div>
                                                        <span class="label"><?= __('gross_encashment_amount') ?? 'Gross Encashment Amount' ?></span>
                                                        <small class="text-muted d-block"><?= __('days_x_daily_rate') ?? 'Days × Daily Rate' ?></small>
                                                    </div>
                                                    <span class="value"><?= number_format($encashment_amount, 2); ?> SAR</span>
                                                </li>
                                                <?php if ($encash_gosi > 0): ?>
                                                <li>
                                                    <span class="label text-danger"><?= __('gosi_deduction') ?? 'GOSI Deduction' ?> (<?= number_format($request['gosi'], 1); ?>%)</span>
                                                    <span class="value text-danger">-<?= number_format($encash_gosi, 2); ?> SAR</span>
                                                </li>
                                                <?php endif; ?>
                                                <li class="total-payable">
                                                    <span class="label"><?= __('total_encashment_payment') ?? 'Total Encashment Payment' ?></span>
                                                    <span class="value"><?= number_format($net_encashment, 2); ?> SAR</span>
                                                </li>
                                            </ul>
                                        </div>
                                        <div class="alert alert-info mt-3 mb-0">
                                            <i class="fa fa-info-circle"></i> <strong><?= __('note') ?? 'Note' ?>:</strong> <?= __('encashment_deducts_balance') ?? 'This encashment will deduct the specified days from the employee vacation balance.' ?>
                                        </div>
                                    </div>
                                    <?php endif; ?>
                                
                                <?php if ($vacation_salary_type === 'end_of_service'): ?>
                                <div class="report-section">
                                    <h5 class="section-title"><i class="fa fa-info-circle"></i><?= __('vacation_salary_information') ?></h5>
                                    <div class="alert alert-info mb-0">
                                        <i class="fa fa-piggy-bank"></i> <strong><?= __('vacation_salary_deferred_eos') ?></strong>
                                        <p class="mb-2 mt-2"><?= str_replace('{days}', htmlspecialchars($applied_days), __('employee_chosen_receive_vacation_salary_eos')) ?></p>
                                        <ul class="mb-0 pl-4">
                                            <li><?= __('vacation_days') ?>: <strong><?= htmlspecialchars($applied_days); ?> <?= __('day_s') ?></strong></li>
                                            <li><?= __('payment') ?>: <strong><?= __('end_of_service_settlement') ?></strong></li>
                                            <li><?= __('amount_calculated_added_final_settlement') ?></li>
                                        </ul>
                                    </div>
                                </div>
                                <?php endif; ?>
                                
                                <?php // Hide payroll info for employees
                                    if ($is_local_annual && (empty($isEmployee) || $isEmployee === false)): ?>
                                <div class="report-section">
                                    <h5 class="section-title"><i class="fa fa-briefcase"></i><?= __('payroll_information') ?? 'Payroll Information' ?></h5>
                                    <div class="alert alert-success mb-0">
                                        <i class="fa fa-check-circle"></i> <strong><?= __('employee_active_in_payroll') ?? 'Employee Active in Payroll' ?></strong>
                                        <p class="mb-2 mt-2"><?= __('local_annual_vacation_payroll_message') ?? 'This is a Local Annual vacation. The employee will remain active in the payroll system and receive their complete monthly salary as per normal.' ?></p>
                                        <ul class="mb-0 pl-4">
                                            <li><?= __('vacation_type') ?>: <strong><?= getDisplayName($request['vac_type'] . ' - ' . $request['fly_type']); ?></strong></li>
                                            <li><?= __('payroll_status') ?>: <strong><?= __('active_full_salary') ?? 'Active - Full Salary' ?></strong></li>
                                            <li><?= __('vacation_days_deducted') ?? 'Vacation Days Deducted' ?>: <strong><?= htmlspecialchars($applied_days); ?> <?= __('day_s') ?? 'Days' ?></strong></li>
                                            <li><?= __('salary_deduction') ?? 'Salary Deduction' ?>: <strong><?= __('none') ?? 'None' ?></strong></li>
                                        </ul>
                                    </div>
                                </div>
                                <?php endif; ?>
                                
                                <?php if ($is_fly_annual && $vacation_salary_type === 'end_of_service'): ?>
                                <div class="report-section">
                                    <h5 class="section-title"><i class="fa fa-info-circle"></i><?= __('vacation_salary_information') ?? 'Vacation Salary Information' ?></h5>
                                    <div class="alert alert-info mb-0">
                                        <i class="fa fa-piggy-bank"></i> <strong><?= __('vacation_salary_deferred_eos') ?? 'Vacation Salary Deferred to End of Service' ?></strong>
                                        <p class="mb-2 mt-2"><?= __('employee_chosen_receive_vacation_salary_eos') ?? 'The employee has chosen to receive their vacation salary as part of their end of service settlement instead of with payroll.' ?></p>
                                        <ul class="mb-0 pl-4">
                                            <li><?= __('vacation_days') ?>: <strong><?= htmlspecialchars($applied_days); ?> <?= __('day_s') ?? 'Days' ?></strong></li>
                                            <li><?= __('payment') ?>: <strong><?= __('end_of_service_settlement') ?? 'End of Service Settlement' ?></strong></li>
                                            <li><?= __('amount_calculated_added_final_settlement') ?? 'The vacation salary amount will be calculated and added to the final settlement.' ?></li>
                                        </ul>
                                    </div>
                                </div>
                                <?php endif; ?>
                                
                                <?php if ($is_fly_annual && $vacation_salary_type === 'payroll'): ?>
                                <div class="report-section">
                                    <h5 class="section-title"><i class="fa fa-money-check-alt"></i><?= __('payment_details') ?? 'Payment Details' ?></h5>
                                    <div class="payment-summary">
                                        <ul>
                                            <li>
                                                <div>
                                                    <span class="label"><?= __('working_days_salary') ?? 'Working Days Salary' ?></span>
                                                    <small class="text-muted d-block"><?= htmlspecialchars($working_days ?? 0); ?> <?= __('days') ?? 'days' ?> × <?= number_format($daily_rate, 2); ?> SAR/day (<?= number_format($total_monthly_salary, 2); ?> SAR ÷ <?= $days_in_month; ?> days)</small>
                                                </div>
                                                <span class="value"><?=number_format($working_days_salary, 2); ?> SAR</span>
                                            </li>
                                            <li>
                                                <div>
                                                    <span class="label"><?= __('vacation_salary') ?? 'Vacation Salary' ?><?php if ($salaries_earned > 1): ?> <span style="color: #28a745; font-weight: 700;">x<?= (int)$salaries_earned ?></span><?php endif; ?></span>
                                                    <small class="text-muted d-block"><?= htmlspecialchars($applied_days); ?> <?= __('days') ?? 'days' ?> × <?= number_format($daily_rate, 2); ?> SAR/day (<?= number_format($total_monthly_salary, 2); ?> SAR ÷ <?= $days_in_month; ?> days)</small>
                                                </div>
                                                <span class="value"><?=number_format($vacation_salary, 2); ?> SAR</span>
                                            </li>
                                            <?php if ($overtime_amount > 0): ?>
                                            <li>
                                                <div>
                                                    <span class="label text-success"><?= __('overtime_payment') ?? 'Overtime Payment' ?></span>
                                                    <small class="text-muted d-block"><?= htmlspecialchars($overtime_hours) ?> <?= __('hours') ?? 'hours' ?> @ <?= number_format($overtimeHourlyRate ?? 0, 2) ?> SAR/hr</small>
                                                </div>
                                                <span class="value text-success">+<?=number_format($overtime_amount, 2); ?> SAR</span>
                                            </li>
                                            <?php endif; ?>
                                            <?php if ($ticket_fee > 0): ?>
                                            <li class="text-warning">
                                                <div>
                                                    <span class="label"><?= __('ticket_payment') ?? 'Ticket Payment' ?></span>
                                                    <small class="text-muted d-block">Ticket fares will not be added in total payable amount.</small>
                                                </div>
                                                <span class="value"><?=number_format($ticket_fee, 2); ?> SAR</span>
                                            </li>
                                            <?php endif; ?>
                                            <?php if ($permit_fee > 0): ?>
                                            <li class="text-warning">
                                                <div>
                                                    <span class="label"><?= __('permit_fee') ?? 'Permit Fee' ?></span>
                                                    <small class="text-muted d-block">Permit fees will not be added in total payable amount.</small>
                                                </div>
                                                <span class="value"><?=number_format($permit_fee, 2); ?> SAR</span>
                                            </li>
                                            <?php endif; ?>
                                            <?php if ($deduction_amount > 0): ?>
                                            <li>
                                                <div>
                                                    <span class="label text-danger"><?= __('deductions') ?? 'Deductions' ?></span>
                                                    <small class="text-muted d-block">
                                                        <?php if ($deduction_hours > 0): ?>
                                                            <?= htmlspecialchars($deduction_hours) ?> <?= __('hours') ?? 'hours' ?> @ <?= number_format($hourlyRateDeduction ?? 0, 2) ?> SAR/hr
                                                        <?php endif; ?>
                                                        <?php if ($deduction_days > 0): ?>
                                                            <?php if ($deduction_hours > 0) echo ' + '; ?>
                                                            <?= htmlspecialchars($deduction_days) ?> <?= __('days') ?? 'days' ?> @ <?= number_format($dailyRateDeduction ?? 0, 2) ?> SAR/day
                                                        <?php endif; ?>
                                                    </small>
                                                </div>
                                                <span class="value text-danger">-<?=number_format($deduction_amount, 2); ?> SAR</span>
                                            </li>
                                            <?php endif; ?>
                                            <?php if ($gosi_deduction > 0): ?>
                                            <li>
                                                <span class="label text-danger"><?= __('gosi_deduction') ?? 'GOSI Deduction' ?> (<?= number_format($request['gosi'] ?? 0, 1); ?>%)</span>
                                                <span class="value text-danger">-<?=number_format($gosi_deduction, 2); ?> SAR</span>
                                            </li>
                                            <?php endif; ?>
                                            <li class="total-payable">
                                                <span class="label"><?= __('total_payable') ?? 'Total Payable' ?></span>
                                                <span class="value"><?=number_format($total_payable, 2); ?> SAR</span>
                                            </li>
                                        </ul>
                                    </div>
                                    <?php if (!empty($payroll_note)): ?>
                                    <div class="alert alert-warning mt-3 mb-0">
                                        <i class="fa fa-sticky-note"></i> <strong><?= __('payroll_note') ?? 'Payroll Note' ?>:</strong> <?= htmlspecialchars($payroll_note); ?>
                                    </div>
                                    <?php endif; ?>
                                </div>
                                <?php endif; ?>
                                <div class="row">
                                    <div class="col-md-8">
                                        <div class="report-section">
                                            <h5 class="section-title"><i class="fa fa-tasks"></i><?= __('approval_status') ?></h5>
                                            <div class="approval-timeline">
                                                <?php if ($current_status == 'rejected'): ?>
                                                    <div class="timeline-item rejected">
                                                        <div class="icon"><i class="fa fa-times-circle"></i></div>
                                                        <span class="status ml-3"><strong><?= __('request_rejected') ?></strong></span>
                                                    </div>
                                                <?php elseif ($current_status == 'approved'): ?>
                                                    <div class="timeline-item approved">
                                                        <div class="icon"><i class="fa fa-check-circle"></i></div>
                                                        <span class="status ml-3"><strong><?= __('request_approved') ?></strong></span>
                                                    </div>
                                                    <?php if (!empty($approval_chain)): ?>
                                                        <div class="mt-3">
                                                            <small class="text-muted"><i class="fa fa-info-circle"></i> <?= __('approved_by') ?></small>
                                                            <?php foreach ($approval_chain as $approver): ?>
                                                                <?php if ($approver['status'] == 'approved'): ?>
                                                                    <div class="ml-3 mt-1">
                                                                        <small><i class="fa fa-user-check text-success"></i> <?=getDisplayName($approver['approver_name']) ?> (<?= getRoleLabel($approver['approver_role']) ?>)</small>
                                                                    </div>
                                                                <?php endif; ?>
                                                            <?php endforeach; ?>
                                                        </div>
                                                    <?php endif; ?>
                                                <?php elseif (!empty($approval_chain)): ?>
                                                    <?php // NEW SYSTEM: Show approval chain ?>
                                                    <?php 
                                                        // Check if request was rejected
                                                        $is_vacation_rejected = isset($request['status']) && $request['status'] === 'rejected';
                                                    ?>
                                                    <?php foreach ($approval_chain as $index => $approver): 
                                                        // If request is rejected, skip pending/awaiting approvers
                                                        if ($is_vacation_rejected && in_array($approver['status'], ['pending', 'awaiting'])) {
                                                            continue;
                                                        }
                                                        
                                                        $item_class = '';
                                                        if ($approver['status'] == 'approved') {
                                                            $item_class = 'approved';
                                                            $icon = 'fa-check-circle';
                                                        } elseif ($approver['status'] == 'pending') {
                                                            $item_class = 'pending';
                                                            $icon = 'fa-clock';
                                                        } elseif ($approver['status'] == 'rejected') {
                                                            $item_class = 'rejected';
                                                            $icon = 'fa-times-circle';
                                                        } else {
                                                            $item_class = 'future';
                                                            $icon = 'fa-circle';
                                                        }
                                                        $role_icon = 'fa-user';
                                                                    if (!empty($approver['approver_role']) && stripos($approver['approver_role'], 'hr') !== false) {
                                                            $role_icon = 'fa-user-shield';
                                                                    } elseif (!empty($approver['approver_role']) && (stripos($approver['approver_role'], 'manager') !== false || stripos($approver['approver_role'], 'administrator') !== false)) {
                                                            $role_icon = 'fa-user-tie';
                                                        }
                                                    ?>
                                                            <div class="timeline-item <?= $item_class ?>">
                                                                <div class="icon"><i class="fa <?= $icon ?>"></i></div>
                                                                <span class="status ml-3">
                                                                    <i class="fa <?= $role_icon ?>"></i>
                                                                    <?= getDisplayName($approver['approver_name']) ?> 
                                                                    <small class="text-muted">(<?= __('level') ?> <?= $approver['approval_level'] ?>: <?= getDisplayName(!empty($approver['approver_dept_name']) ? $approver['approver_dept_name'] : getRoleLabel($approver['approver_role'])) ?>)</small>
                                                                </span>
                                                            </div>
                                                    <?php endforeach; ?>
                                                <?php else: ?>
                                                    <?php // OLD SYSTEM or PENDING: Show simple status ?>
                                                    <div class="timeline-item pending">
                                                        <div class="icon"><i class="fa fa-clock"></i></div>
                                                        <span class="status ml-3">
                                                            <?php 
                                                            // Check for old approval_status field
                                                            if (isset($request['approval_status'])) {
                                                                echo __('status') . ": " . htmlspecialchars(ucfirst(str_replace('_', ' ', $request['approval_status'])));
                                                            } else {
                                                                echo __('pending_approval');
                                                            }
                                                            ?>
                                                        </span>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <?php if(!empty($request['remarks']) || !empty($request['note'])): ?>
                                        <div class="report-section">
                                             <h5 class="section-title"><i class="fa fa-comments"></i><?= __('remarks') ?></h5>
                                             <?php if($current_status == 'rejected'): ?>
                                                <div class="alert alert-danger mb-0"><?=getDisplayName(nl2br(htmlspecialchars($request['note']))); ?></div>
                                             <?php elseif(!empty($request['remarks'])): ?>
                                                <div class="notes-section"><p class="mb-0"><?=getDisplayName(nl2br(htmlspecialchars($request['remarks']))); ?></p></div>
                                             <?php endif; ?>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <?php // ASSET CLEARANCE SECTION - Only show if employee has assets
                                if (!empty($assigned_assets)): 
                                    // Check if any asset clearance approver has acted on this request
                                    $asset_clearance_happened = false;
                                    $asset_clearance_approvers = [];
                                    
                                    if (!empty($approval_chain)) {
                                        foreach ($approval_chain as $approver) {
                                            $role_lower = strtolower($approver['approver_role'] ?? '');
                                            $dept_lower = strtolower($approver['approver_dept_name'] ?? '');
                                            
                                            // Normalize dept names into categories (handles synonyms/abbreviations)
                                            $dept_is_it = (
                                                $dept_lower === 'it' ||
                                                $dept_lower === 'information technology' ||
                                                stripos($dept_lower, 'information technology') !== false ||
                                                stripos($dept_lower, 'technology') !== false ||
            	                                stripos($dept_lower, 'technical support') !== false
                                            );
                                            $dept_is_admin = (
                                                $dept_lower === 'admin' ||
                                                $dept_lower === 'administration' ||
                                                stripos($dept_lower, 'administration') !== false ||
                                                stripos($dept_lower, 'general admin') !== false ||
                                                stripos($dept_lower, 'general administration') !== false
                                            );
                                            $dept_is_transport = (
                                                $dept_lower === 'transportation' ||
                                                $dept_lower === 'transport' ||
                                                stripos($dept_lower, 'transportation') !== false ||
                                                stripos($dept_lower, 'transport') !== false ||
                                                stripos($dept_lower, 'fleet') !== false ||
                                                stripos($dept_lower, 'garage') !== false
                                            );
                                            
                                            // Consider either role keywords OR department categories
                                            $is_asset_clearance_role = (
                                                stripos($role_lower, 'it') !== false || $dept_is_it ||
                                                stripos($role_lower, 'admin') !== false || $dept_is_admin ||
                                                stripos($role_lower, 'transport') !== false || $dept_is_transport
                                            );
                                            if ($is_asset_clearance_role) {
                                                $asset_clearance_approvers[] = $approver;
                                                if ($approver['status'] == 'approved') {
                                                    $asset_clearance_happened = true;
                                                }
                                            }
                                        }
                                    }
                                    
                                    if ($asset_clearance_happened):
                                ?>
                                <div class="report-section">
                                    <h5 class="section-title"><i class="fa fa-laptop"></i><?= __('asset_clearance_details') ?></h5>
                                    
                                    <?php if (!empty($asset_clearance_approvers)): ?>
                                    <div class="mb-3">
                                        <small class="text-muted"><i class="fa fa-info-circle"></i> <?= __('cleared_by') ?></small>
                                        <?php foreach ($asset_clearance_approvers as $approver): ?>
                                            <?php if ($approver['status'] == 'approved'): ?>
                                                <div class="ml-3 mt-1">
                                                    <small>
                                                        <i class="fa fa-user-check text-success"></i> 
                                                        <?= htmlspecialchars($approver['approver_name']) ?> 
                                                        (<?= htmlspecialchars(!empty($approver['approver_dept_name']) ? $approver['approver_dept_name'] : ucfirst($approver['approver_role'])) ?>)
                                                        <?php if (!empty($approver['action_date'])): ?>
                                                            - <?= date('d M Y, h:i A', strtotime($approver['action_date'])) ?>
                                                        <?php endif; ?>
                                                    </small>
                                                </div>
                                            <?php endif; ?>
                                        <?php endforeach; ?>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <div class="table-responsive">
                                        <table class="table table-bordered table-sm">
                                            <thead>
                                                <tr>
                                                    <th><?= __('asset_name') ?></th>
                                                    <th><?= __('serial_number') ?></th>
                                                    <th><?= __('asset_type') ?></th>
                                                    <th><?= __('clearance_status') ?></th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($assigned_assets as $asset): 
                                                    // Determine asset type for better categorization
                                                    $asset_name_lower = strtolower($asset['asset_name']);
                                                    $asset_type = __('other');
                                                    $clearance_dept = 'General';
                                                    
                                                    if (stripos($asset_name_lower, 'laptop') !== false || 
                                                        stripos($asset_name_lower, 'computer') !== false || 
                                                        stripos($asset_name_lower, 'pc') !== false) {
                                                        $asset_type = __('it_equipment');
                                                        $clearance_dept = __('it');
                                                    } elseif (stripos($asset_name_lower, 'mobile') !== false || 
                                                               stripos($asset_name_lower, 'phone') !== false || 
                                                               stripos($asset_name_lower, 'sim') !== false) {
                                                        $asset_type = __('communication');
                                                        $clearance_dept = __('administration');
                                                    } elseif (stripos($asset_name_lower, 'car') !== false || 
                                                               stripos($asset_name_lower, 'vehicle') !== false) {
                                                        $asset_type = __('vehicle');
                                                        $clearance_dept = __('transportation');
                                                    }
                                                    
                                                    // Check if this asset type's department has cleared
                                                    $cleared_by_dept = false;
                                                    foreach ($asset_clearance_approvers as $approver) {
                                                        $role_lower = strtolower($approver['approver_role'] ?? '');
                                                        $dept_lower = strtolower($approver['approver_dept_name'] ?? '');
                                                        
                                                        $dept_is_it = (
                                                            $dept_lower === 'it' ||
                                                            $dept_lower === 'information technology' ||
                                                            stripos($dept_lower, 'information technology') !== false ||
                                                            stripos($dept_lower, 'technology') !== false ||
                                                            stripos($dept_lower, 'technical support') !== false
                                                        );
                                                        $dept_is_admin = (
                                                            $dept_lower === 'admin' ||
                                                            $dept_lower === 'administration' ||
                                                            stripos($dept_lower, 'administration') !== false ||
                                                            stripos($dept_lower, 'general admin') !== false ||
                                                            stripos($dept_lower, 'general administration') !== false
                                                        );
                                                        $dept_is_transport = (
                                                            $dept_lower === 'transportation' ||
                                                            $dept_lower === 'transport' ||
                                                            stripos($dept_lower, 'transportation') !== false ||
                                                            stripos($dept_lower, 'transport') !== false ||
                                                            stripos($dept_lower, 'fleet') !== false ||
                                                            stripos($dept_lower, 'garage') !== false
                                                        );
                                                        
                                                        if ($approver['status'] == 'approved') {
                                                            $match_it = ($clearance_dept == 'IT' && (stripos($role_lower, 'it') !== false || $dept_is_it));
                                                            $match_admin = ($clearance_dept == 'Administration' && (stripos($role_lower, 'admin') !== false || $dept_is_admin));
                                                            $match_transport = ($clearance_dept == 'Transportation' && (stripos($role_lower, 'transport') !== false || $dept_is_transport));
                                                            if ($match_it || $match_admin || $match_transport) {
                                                                $cleared_by_dept = true;
                                                                break;
                                                            }
                                                        }
                                                    }
                                                    
                                                    $status = $cleared_by_dept ? __('cleared') : __('pending');
                                                    $badge = $cleared_by_dept ? 'success' : 'warning';
                                                ?>
                                                <tr>
                                                    <td><?= htmlspecialchars($asset['asset_name']); ?></td>
                                                    <td><?= htmlspecialchars($asset['serial_number']); ?></td>
                                                    <td><span class="badge badge-secondary"><?= $asset_type ?></span></td>
                                                    <td>
                                                        <span class="badge badge-<?= $badge ?>"><?= $status ?></span>
                                                        <small class="text-muted d-block"><?= $clearance_dept ?></small>
                                                    </td>
                                                </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                                <?php elseif ($current_status == 'pending_approval' || $current_status == 'approved'): ?>
                                <div class="report-section">
                                    <h5 class="section-title"><i class="fa fa-laptop"></i><?= __('asset_clearance_details') ?></h5>
                                    <div class="alert alert-info mb-3">
                                        <i class="fa fa-info-circle"></i> <strong><?= __('asset_clearance_required') ?></strong>
                                        <p class="mb-0 mt-2"><?= __('assigned_assets_message') ?></p>
                                    </div>
                                    <div class="table-responsive">
                                        <table class="table table-bordered table-sm">
                                            <thead>
                                                <tr>
                                                    <th><?= __('asset_name') ?></th>
                                                    <th><?= __('serial_number') ?></th>
                                                    <th><?= __('asset_type') ?></th>
                                                    <th><?= __('clearance_department') ?></th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($assigned_assets as $asset): 
                                                    $asset_name_lower = strtolower($asset['asset_name']);
                                                    $asset_type = 'Other';
                                                    $clearance_dept = 'General';
                                                    
                                                    if (stripos($asset_name_lower, 'laptop') !== false || 
                                                        stripos($asset_name_lower, 'computer') !== false || 
                                                        stripos($asset_name_lower, 'pc') !== false) {
                                                        $asset_type = 'IT Equipment';
                                                        $clearance_dept = 'IT';
                                                    } elseif (stripos($asset_name_lower, 'mobile') !== false || 
                                                               stripos($asset_name_lower, 'phone') !== false || 
                                                               stripos($asset_name_lower, 'sim') !== false) {
                                                        $asset_type = 'Communication';
                                                        $clearance_dept = 'Administration';
                                                    } elseif (stripos($asset_name_lower, 'car') !== false || 
                                                               stripos($asset_name_lower, 'vehicle') !== false) {
                                                        $asset_type = 'Vehicle';
                                                        $clearance_dept = 'Transportation';
                                                    }
                                                ?>
                                                <tr>
                                                    <td><?= htmlspecialchars($asset['asset_name']); ?></td>
                                                    <td><?= htmlspecialchars($asset['serial_number']); ?></td>
                                                    <td><span class="badge badge-secondary"><?= $asset_type ?></span></td>
                                                    <td><span class="badge badge-primary"><?= $clearance_dept ?></span></td>
                                                </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                                <?php endif; // End if ($asset_clearance_happened) ?>
                                <?php endif; // End if (!empty($assigned_assets)) ?>
                            </div>
                            
                            <div class="report-footer">
                                <div class="signature-area">
                                    <div class="signature-box"><p><?= __('compensation_signature') ?></p></div>
                                    <div class="signature-box"><p><?= __('hr_manager_signature') ?></p></div>
                                    <div class="signature-box"><p><?= __('general_manager_signature') ?></p></div>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
                <footer class="footer no-print"><?= $site_footer ?></footer>
            </div>
        </div>

        <script src="assets/js/jquery.min.js"></script>
        <script src="assets/js/bootstrap.bundle.min.js"></script>
        <script src="assets/js/metisMenu.min.js"></script>
        <script src="assets/js/waves.js"></script>
        <script src="assets/js/jquery.slimscroll.js"></script>
        <script src="assets/js/jquery.core.js"></script>
        <script src="assets/js/jquery.app.js"></script>

    </body>
    </html>
<?php
    $conDB->close();
}
?>

