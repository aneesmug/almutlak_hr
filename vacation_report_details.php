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

require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/session_check.php';
$query = mysqli_query($conDB, "SELECT * FROM `admin_login` WHERE `id_iqama`='" . $username . "'");
if (mysqli_num_rows($query) == 1) {
    include("./includes/avatar_select.php");

    // 1. Get and validate the IDs from the URL
    $vacation_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    $emp_id = isset($_GET['emp_id']) ? $_GET['emp_id'] : '';

    if ($vacation_id === 0 || empty($emp_id)) {
        die("Invalid request parameters.");
    }

    // 2. MODIFIED: Fetch all data with a single query (added vacation_salary_type)
    $sql = "SELECT 
                v.*, 
                v.fly_type as raw_fly_type,
                v.attachment_path,
                v.vacation_salary_type,
                e.name as employee_name,
                e.avatar,
                e.joining_date,
                e.gosi,
                e.country as country_id,
                d.dep_nme AS `deptname`,
                s.section_name,
                c.name AS `country_name`,
                re.name AS `replacement_person_name`,
                cp.vac_period AS contract_vacation_days,
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
    $applied_days = (float)($request['vacdays'] ?? 0);
    
    $non_payable_leave_types = ['Sick Leave', 'Casual Leave', 'Maternity Leave', 'Compassionate Leave', 'Business Trip', 'Compensatory Leave'];
    $is_payable_leave = !in_array($request['vac_type'], $non_payable_leave_types);

    // If fly_type is emergency, it is not payable.
    if (isset($request['raw_fly_type']) && $request['raw_fly_type'] === 'emergency') {
        $is_payable_leave = false;
    }    // NEW: Check vacation_salary_type - if 'end_of_service', don't calculate vacation salary in payroll
    $vacation_salary_type = $request['vacation_salary_type'] ?? 'payroll';
    $show_vacation_salary = ($vacation_salary_type === 'payroll');

    if ($is_payable_leave && $show_vacation_salary) {
        if ($salary) {
            $total_monthly_salary = ($salary['basic'] ?? 0) + ($salary['housing'] ?? 0) + ($salary['transport'] ?? 0) + ($salary['food'] ?? 0) + ($salary['misc'] ?? 0) + ($salary['cashier'] ?? 0) + ($salary['fuel'] ?? 0) + ($salary['tel'] ?? 0) + ($salary['other'] ?? 0) + ($salary['guard'] ?? 0);
            $daily_rate = $total_monthly_salary / 30;

            // Calculate vacation days salary
            $contract_days = isset($request['contract_vacation_days']) ? (float)$request['contract_vacation_days'] : 0;
            if ($contract_days > 0 && $applied_days == $contract_days) {
                $vacation_salary = $total_monthly_salary;
            } else {
                $vacation_salary = $daily_rate * $applied_days;
            }
            
            // Calculate working days salary (from 1st day of month until last day)
            $start_date_obj = new DateTime($request['start_date']);
            $working_days = (int)$start_date_obj->format('d');
            $working_days_salary = $daily_rate * $working_days;

            if (isset($request['country_id']) && $request['country_id'] == 191 && isset($request['gosi']) && is_numeric($request['gosi'])) {
                $gosi_percentage = (float)$request['gosi'];
                $gosi_deduction = (($vacation_salary + $working_days_salary) * $gosi_percentage) / 100;
            }
        }
        if (($request['vac_type'] === 'Fly' || $request['vac_type'] === 'Local Vacation') && $request['country_id'] != 191) {
            $ticket_fee = $request['ticket_pay'] ?? 0;
            $permit_fee = $request['permit_fee'] ?? 0;
        }
    } elseif ($is_payable_leave && !$show_vacation_salary) {
        // NEW: If salary type is 'end_of_service', only calculate working days salary (1st to last day of month)
        if ($salary) {
            $total_monthly_salary = ($salary['basic'] ?? 0) + ($salary['housing'] ?? 0) + ($salary['transport'] ?? 0) + ($salary['food'] ?? 0) + ($salary['misc'] ?? 0) + ($salary['cashier'] ?? 0) + ($salary['fuel'] ?? 0) + ($salary['tel'] ?? 0) + ($salary['other'] ?? 0) + ($salary['guard'] ?? 0);
            $daily_rate = $total_monthly_salary / 30;

            // Calculate working days salary (from 1st day of month until last day before vacation)
            $start_date_obj = new DateTime($request['start_date']);
            $working_days = (int)$start_date_obj->format('d') - 1; // Days before vacation starts
            $working_days_salary = $daily_rate * $working_days;

            if (isset($request['country_id']) && $request['country_id'] == 191 && isset($request['gosi']) && is_numeric($request['gosi'])) {
                $gosi_percentage = (float)$request['gosi'];
                $gosi_deduction = ($working_days_salary * $gosi_percentage) / 100;
            }
        }
        // Still show ticket and permit fees
        if (($request['vac_type'] === 'Fly' || $request['vac_type'] === 'Local Vacation') && $request['country_id'] != 191) {
            $ticket_fee = $request['ticket_pay'] ?? 0;
            $permit_fee = $request['permit_fee'] ?? 0;
        }
    }


    if ($request['vac_type'] !== 'Encashed'){
        $total_payable = ($vacation_salary + $working_days_salary) + $ticket_fee + $permit_fee - $gosi_deduction;
    } else {
        $total_payable = $vacation_salary + $ticket_fee + $permit_fee - $gosi_deduction;
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
    <html lang="en">

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
            
            .report-body { padding: 1.5rem; } /* Reduced padding */
            
            .employee-banner { display: flex; align-items: center; background-color: var(--background-light); padding: 1rem; border-radius: 6px; margin-bottom: 1.5rem; border: 1px solid var(--border-color); }
            .employee-banner .avatar { width: 60px; height: 60px; border-radius: 50%; margin-right: 1rem; }
            .employee-banner .info h4 { font-weight: 600; margin: 0 0 0.2rem 0; font-size: 1.1rem; }
            .employee-banner .info p { color: var(--muted-color); margin: 0; font-size: 0.85rem; }

            .report-section { margin-bottom: 1.5rem; } /* Reduced margin */
            .section-title { font-weight: 600; color: var(--primary-color); margin-bottom: 1rem; font-size: 1rem; border-bottom: 1px solid var(--border-color); padding-bottom: 0.5rem; }
            .section-title i { margin-right: 0.5rem; }

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
            
            .notes-section { background-color: #fff9e6; border-left: 4px solid var(--warning-color); padding: 1rem; border-radius: 4px; font-size: 0.85rem; }
            
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
                            <a href="javascript:void(0);" onclick="window.print()" class="btn btn-primary waves-effect waves-light"><i class="fa fa-print mr-1"></i> Print Report</a>
                        </div>
                        
                        <div class="report-wrapper">
                            <div class="report-header">
                                <div class="logo-container"><img src="<?=get_setting($conDB, 'logo')?>" alt="Company Logo"></div>
                                <div class="report-meta">
                                    <h2 class="report-title">Vacation Request Report</h2>
                                    <p class="report-subtitle">Request ID: #<?=htmlspecialchars($request['id']); ?></p>
                                </div>
                            </div>

                            <div class="report-body">
                                <div class="employee-banner">
                                    <img src="<?=htmlspecialchars($request['avatar'] ?? 'assets/images/users/avatar-1.jpg'); ?>" alt="Employee Avatar" class="avatar">
                                    <div class="info">
                                        <h4><?=htmlspecialchars($request['employee_name']); ?></h4>
                                        <p>Employee ID: <?=htmlspecialchars($request['emp_id']); ?> | <?=htmlspecialchars($request['deptname']); ?><?= !empty($request['section_name']) ? ' / ' . htmlspecialchars($request['section_name']) : '' ?></p>
                                    </div>
                                </div>

                                <div class="report-section">
                                    <h5 class="section-title"><i class="fa fa-calendar-alt"></i>Vacation Details</h5>
                                    <div class="grid-details">
                                        <div class="detail-item"><span class="label">Vacation Type</span> <span class="value"><small><?=htmlspecialchars($request['vac_type']); ?><?= !empty($request['fly_type']) ? ' | ' . htmlspecialchars($request['fly_type']) : '' ?></small></span></div>
                                        <div class="detail-item"><span class="label">Start Date</span> <span class="value"><small><?=htmlspecialchars(date('d M Y', strtotime($request['start_date']))); ?></small></span></div>
                                        <div class="detail-item"><span class="label">Return Date</span> <span class="value"><small><?=htmlspecialchars(date('d M Y', strtotime($request['return_date']))); ?></small></span></div>
                                        <div class="detail-item"><span class="label">Total Days</span> <span class="value highlight"><small><?=htmlspecialchars($request['vacdays']); ?> Days</small></span></div>
                                        <div class="detail-item"><span class="label">Replacement</span> <span class="value"><small><?=parseName($request['replacement_person_name'] ?? 'N/A'); ?></small></span></div>
                                        <div class="detail-item"><span class="label">Requested On</span> <span class="value"><small><?=htmlspecialchars(date('d M Y, h:i A', strtotime($request['created_at']))); ?></small></span></div>
                                         <?php if (!empty($request['attachment_path'])): ?>
                                            <div class="detail-item"><span class="label">Attachment</span> <span class="value"><small><a href="<?=htmlspecialchars($request['attachment_path']); ?>" target="_blank">View Document</a></small></span></div>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <?php 
                                // Hide payment details if:
                                // 1. Emergency vacation, OR
                                    // 2. Vacation salary type is 'end_of_service', OR
                                    // 3. Encashment request (will show separate section)
                                    $is_encashment_request = (trim(strtolower($request['remarks'] ?? '')) === 'encashment');
                                    $hide_payment_details = ($request['raw_fly_type'] === 'emergency') || ($vacation_salary_type === 'end_of_service') || $is_encashment_request;
                                ?>
                                
                                    <?php if ($is_encashment_request): ?>
                                    <div class="report-section">
                                        <h5 class="section-title"><i class="fa fa-coins"></i>Encashment Payment Details</h5>
                                        <div class="alert alert-success mb-3">
                                            <i class="fa fa-info-circle"></i> <strong>Vacation Balance Encashment</strong>
                                            <p class="mb-0 mt-2">The employee has opted to encash their remaining vacation balance instead of taking time off.</p>
                                        </div>
                                        <div class="payment-summary">
                                            <ul>
                                                <li>
                                                    <div>
                                                        <span class="label">Encashed Vacation Days</span>
                                                        <small class="text-muted d-block">Based on available balance</small>
                                                    </div>
                                                    <span class="value"><?= htmlspecialchars($effective_remaining ?? $request['vacdays'] ?? 0); ?> day(s)</span>
                                                </li>
                                                <li>
                                                    <div>
                                                        <span class="label">Daily Salary Rate</span>
                                                        <small class="text-muted d-block">Monthly salary ÷ 30</small>
                                                    </div>
                                                    <span class="value">
                                                        <?php 
                                                        $encashment_amount = $request['encashment_amount'] ?? 0;
                                                        $days_encashed = $effective_remaining ?? $request['vacdays'] ?? 1;
                                                        $daily_rate_display = ($days_encashed > 0) ? ($encashment_amount / $days_encashed) : 0;
                                                        echo number_format($daily_rate_display, 2); 
                                                        ?> SAR
                                                    </span>
                                                </li>
                                                <?php if ($gosi_deduction > 0): ?>
                                                <li>
                                                    <span class="label text-danger">GOSI Deduction</span>
                                                    <span class="value text-danger">-<?= number_format($gosi_deduction, 2); ?> SAR</span>
                                                </li>
                                                <?php endif; ?>
                                                <li class="total-payable">
                                                    <span class="label">Total Encashment Payment</span>
                                                    <span class="value"><?= number_format($encashment_amount - $gosi_deduction, 2); ?> SAR</span>
                                                </li>
                                            </ul>
                                        </div>
                                        <div class="alert alert-warning mt-3 mb-0">
                                            <i class="fa fa-exclamation-triangle"></i> <strong>Note:</strong> After this encashment, your vacation balance will be set to <strong>0 days</strong>.
                                        </div>
                                    </div>
                                    <?php endif; ?>
                                
                                <?php if ($vacation_salary_type === 'end_of_service'): ?>
                                <div class="report-section">
                                    <h5 class="section-title"><i class="fa fa-piggy-bank"></i>Salary Payment Information</h5>
                                    <div class="alert alert-info mb-0">
                                        <i class="fa fa-info-circle"></i> <strong>Vacation Salary Deferred to End of Service</strong>
                                        <p class="mb-2 mt-2">The employee has chosen to receive their vacation salary (<?= htmlspecialchars($applied_days); ?> days) at the time of End of Service settlement.</p>
                                        <ul class="mb-0 pl-4">
                                            <li>Vacation Days: <strong><?= htmlspecialchars($applied_days); ?> day(s)</strong></li>
                                            <li>Payment: <strong>End of Service Settlement</strong></li>
                                            <li>This amount will be calculated and added to the final settlement upon termination of employment.</li>
                                        </ul>
                                    </div>
                                </div>
                                <?php endif; ?>
                                
                                <?php if (!$hide_payment_details): ?>
                                <div class="report-section">
                                    <h5 class="section-title"><i class="fa fa-money-check-alt"></i>Payment Details</h5>
                                    <?php if (!$is_payable_leave): ?>
                                        <div class="alert alert-info">Salary and benefits are not applicable for this type of leave.</div>
                                    <?php else: ?>
                                        <div class="payment-summary">
                                            <ul>
                                                <?php if($request['vac_type'] !== 'Encashed'): ?>
                                                <li>
                                                    <div>
                                                        <span class="label">Working Days Salary</span>
                                                        <small class="text-muted d-block">Calculated for <?= htmlspecialchars($working_days); ?> day(s)</small>
                                                    </div>
                                                    <span class="value"><?=number_format($working_days_salary, 2); ?> SAR</span>
                                                </li>
                                                <?php endif; ?>
                                                <li>
                                                    <div>
                                                        <span class="label">Vacation Salary</span>
                                                        <small class="text-muted d-block">Calculated for <?= htmlspecialchars($applied_days); ?> day(s)</small>
                                                    </div>
                                                    <span class="value"><?=number_format($vacation_salary, 2); ?> SAR</span>
                                                </li>
                                                <?php if ($ticket_fee > 0): ?><li><span class="label">Ticket Payment</span> <span class="value"><?=number_format($ticket_fee, 2); ?> SAR</span></li><?php endif; ?>
                                                <?php if ($permit_fee > 0): ?><li><span class="label">Permit Fee</span> <span class="value"><?=number_format($permit_fee, 2); ?> SAR</span></li><?php endif; ?>
                                                <?php if ($gosi_deduction > 0): ?><li><span class="label text-danger">GOSI Deduction</span> <span class="value text-danger">-<?=number_format($gosi_deduction, 2); ?> SAR</span></li><?php endif; ?>
                                                <li class="total-payable"><span class="label">Total Payable</span> <span class="value"><?=number_format($total_payable, 2); ?> SAR</span></li>
                                            </ul>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <?php endif; ?>

                                <div class="row">
                                    <div class="col-md-7">
                                        <div class="report-section">
                                            <h5 class="section-title"><i class="fa fa-tasks"></i>Approval Status</h5>
                                            <div class="approval-timeline">
                                                <?php if ($current_status == 'rejected'): ?>
                                                    <div class="timeline-item rejected">
                                                        <div class="icon"><i class="fa fa-times-circle"></i></div>
                                                        <span class="status ml-3"><strong>Request Rejected</strong></span>
                                                    </div>
                                                <?php elseif ($current_status == 'approved'): ?>
                                                    <div class="timeline-item approved">
                                                        <div class="icon"><i class="fa fa-check-circle"></i></div>
                                                        <span class="status ml-3"><strong>Request Approved</strong></span>
                                                    </div>
                                                    <?php if (!empty($approval_chain)): ?>
                                                        <div class="mt-3">
                                                            <small class="text-muted"><i class="fa fa-info-circle"></i> Approved by:</small>
                                                            <?php foreach ($approval_chain as $approver): ?>
                                                                <?php if ($approver['status'] == 'approved'): ?>
                                                                    <div class="ml-3 mt-1">
                                                                        <small><i class="fa fa-user-check text-success"></i> <?= htmlspecialchars($approver['approver_name']) ?> (<?= __($approver['approver_role']) ?>)</small>
                                                                    </div>
                                                                <?php endif; ?>
                                                            <?php endforeach; ?>
                                                        </div>
                                                    <?php endif; ?>
                                                <?php elseif (!empty($approval_chain)): ?>
                                                    <?php // NEW SYSTEM: Show approval chain ?>
                                                    <?php foreach ($approval_chain as $index => $approver): 
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
                                                                    <?= htmlspecialchars($approver['approver_name']) ?> 
                                                                    <small class="text-muted">(Level <?= $approver['approval_level'] ?>: <?= htmlspecialchars(!empty($approver['approver_dept_name']) ? $approver['approver_dept_name'] : ucfirst($approver['approver_role'])) ?>)</small>
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
                                                                echo "Status: " . htmlspecialchars(ucfirst(str_replace('_', ' ', $request['approval_status'])));
                                                            } else {
                                                                echo "Pending Approval";
                                                            }
                                                            ?>
                                                        </span>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-5">
                                        <?php if(!empty($request['remarks']) || !empty($request['note'])): ?>
                                        <div class="report-section">
                                             <h5 class="section-title"><i class="fa fa-comments"></i>Remarks</h5>
                                             <?php if($current_status == 'rejected'): ?>
                                                <div class="alert alert-danger mb-0"><?=nl2br(htmlspecialchars($request['note'])); ?></div>
                                             <?php elseif(!empty($request['remarks'])): ?>
                                                <div class="notes-section"><p class="mb-0"><?=nl2br(htmlspecialchars($request['remarks'])); ?></p></div>
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
                                                stripos($dept_lower, 'information technology') !== false ||
                                                stripos($dept_lower, 'technology') !== false ||
            	                                stripos($dept_lower, 'technical support') !== false
                                            );
                                            $dept_is_admin = (
                                                $dept_lower === 'admin' ||
                                                stripos($dept_lower, 'administration') !== false ||
                                                stripos($dept_lower, 'general admin') !== false ||
                                                stripos($dept_lower, 'general administration') !== false
                                            );
                                            $dept_is_transport = (
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
                                    <h5 class="section-title"><i class="fa fa-laptop"></i>Asset Clearance Details</h5>
                                    
                                    <?php if (!empty($asset_clearance_approvers)): ?>
                                    <div class="mb-3">
                                        <small class="text-muted"><i class="fa fa-info-circle"></i> Cleared by:</small>
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
                                                    <th>Asset Name</th>
                                                    <th>Serial Number</th>
                                                    <th>Asset Type</th>
                                                    <th>Clearance Status</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($assigned_assets as $asset): 
                                                    // Determine asset type for better categorization
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
                                                    
                                                    // Check if this asset type's department has cleared
                                                    $cleared_by_dept = false;
                                                    foreach ($asset_clearance_approvers as $approver) {
                                                        $role_lower = strtolower($approver['approver_role'] ?? '');
                                                        $dept_lower = strtolower($approver['approver_dept_name'] ?? '');
                                                        $dept_is_it = (
                                                            $dept_lower === 'it' ||
                                                            stripos($dept_lower, 'information technology') !== false ||
                                                            stripos($dept_lower, 'technology') !== false ||
                                                            stripos($dept_lower, 'technical support') !== false
                                                        );
                                                        $dept_is_admin = (
                                                            $dept_lower === 'admin' ||
                                                            stripos($dept_lower, 'administration') !== false ||
                                                            stripos($dept_lower, 'general admin') !== false ||
                                                            stripos($dept_lower, 'general administration') !== false
                                                        );
                                                        $dept_is_transport = (
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
                                                    
                                                    $status = $cleared_by_dept ? 'Cleared' : 'Pending';
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
                                    <h5 class="section-title"><i class="fa fa-laptop"></i>Assigned Assets</h5>
                                    <div class="alert alert-info mb-3">
                                        <i class="fa fa-info-circle"></i> <strong>Asset Clearance Required</strong>
                                        <p class="mb-0 mt-2">The following assets are assigned to this employee and must be cleared before final approval:</p>
                                    </div>
                                    <div class="table-responsive">
                                        <table class="table table-bordered table-sm">
                                            <thead>
                                                <tr>
                                                    <th>Asset Name</th>
                                                    <th>Serial Number</th>
                                                    <th>Asset Type</th>
                                                    <th>Clearance Department</th>
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
                                    <div class="signature-box"><p>Employee Signature</p></div>
                                    <div class="signature-box"><p>HR Manager Signature</p></div>
                                    <div class="signature-box"><p>General Manager Signature</p></div>
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

