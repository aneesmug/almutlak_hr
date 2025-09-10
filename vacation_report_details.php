<?php
/****************************************************************
 * MODIFICATION SUMMARY:
 * 1. REVISED APPROVAL TIMELINE: Overhauled the approval timeline logic to accurately reflect the new multi-step workflow (DPT_Manager -> HR_Assistant -> HR_Manager -> IT -> GM).
 * 2. DYNAMIC WORKFLOW PATH: The timeline now dynamically adjusts its path, correctly skipping steps for HR employees and omitting the IT step if the employee has no assigned assets.
 * 3. IMPROVED STATUS VISUALS: Corrected the timeline rendering to properly show completed steps as 'approved', the current step as 'pending', and future steps with a neutral style.
 * 4. ENHANCED LABELS & ICONS: Updated the map of approval steps with clearer labels and more distinct icons for each stage of the process.
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

    // 2. MODIFIED: Fetch all data with a single query
    $sql = "SELECT 
                v.*, 
                v.attachment_path,
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

    if ($is_payable_leave) {
        if ($salary) {
            $total_monthly_salary = ($salary['basic'] ?? 0) + ($salary['housing'] ?? 0) + ($salary['transport'] ?? 0) + ($salary['food'] ?? 0) + ($salary['misc'] ?? 0) + ($salary['cashier'] ?? 0) + ($salary['fuel'] ?? 0) + ($salary['tel'] ?? 0) + ($salary['other'] ?? 0) + ($salary['guard'] ?? 0);
            $daily_rate = $total_monthly_salary / 30;

            // Calculate vacation days salary
            if ($request['fly_type'] !== 'emergency') {
                $contract_days = isset($request['contract_vacation_days']) ? (float)$request['contract_vacation_days'] : 0;
                if ($contract_days > 0 && $applied_days == $contract_days) {
                    $vacation_salary = $total_monthly_salary;
                } else {
                    $vacation_salary = $daily_rate * $applied_days;
                }
            }
            
            // Calculate working days salary
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
    }
    $total_payable = ($vacation_salary + $working_days_salary) + $ticket_fee + $permit_fee - $gosi_deduction;

    // Approval Timeline Logic
    $approval_steps_map = [
        'apply'                 => ['label' => 'Dept. Manager Approval', 'icon' => 'fa-user-tie'],
        'pending'               => ['label' => 'HR Assistant Approval', 'icon' => 'fa-user-cog'],
        'hr_assistant_approved' => ['label' => 'HR Manager Approval', 'icon' => 'fa-user-shield'],
        'it_pending'            => ['label' => 'IT Clearance', 'icon' => 'fa-laptop'],
        'hr_manager_approved'   => ['label' => 'General Manager Approval', 'icon' => 'fa-crown'],
        'gm_approved'           => ['label' => 'Approved', 'icon' => 'fa-check-circle'],
        'rejected'              => ['label' => 'Request Rejected', 'icon' => 'fa-times'],
    ];
    
    // Determine if the employee is HR
    $employee_dept_sql = "SELECT dept FROM employees WHERE emp_id = ?";
    $stmt_dept = $conDB->prepare($employee_dept_sql);
    $stmt_dept->bind_param("s", $emp_id);
    $stmt_dept->execute();
    $employee_dept_result = $stmt_dept->get_result()->fetch_assoc();
    $stmt_dept->close();
    $is_hr_employee = ($employee_dept_result && $employee_dept_result['dept'] == 5);
    
    // Customize the flow based on employee type and assets
    if ($is_hr_employee) {
        // HR employees skip Dept Manager and HR Assistant
        $approval_flow = ['hr_assistant_approved', 'it_pending', 'hr_manager_approved', 'gm_approved'];
    } else {
        // Standard flow
        $approval_flow = ['apply', 'pending', 'hr_assistant_approved', 'it_pending', 'hr_manager_approved', 'gm_approved'];
    }
    
    // If employee has no assets, skip IT step
    if (empty($assigned_assets)) {
        $approval_flow = array_filter($approval_flow, function($step) {
            return $step !== 'it_pending';
        });
    }
    
    $current_status_index = array_search($request['approval_status'], $approval_flow);
    // If status not in flow (e.g., rejected), it won't be found
    if ($current_status_index === false) {
        $current_status_index = -1; 
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
        <link rel="shortcut icon" href="assets/images/favicon.ico">
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
                    <div class="topbar-left"><a href="dashboard.php" class="logo"><span><img src="assets/images/logo.png" alt="" height="22"></span><i><img src="assets/images/logo_sm.png" alt="" height="28"></i></a></div>
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
                                <div class="logo-container"><img src="assets/images/logo.png" alt="Company Logo"></div>
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
                                        <div class="detail-item"><span class="label">Vacation Type</span> <span class="value"><?=htmlspecialchars($request['vac_type']); ?><?= !empty($request['fly_type']) ? ' | ' . htmlspecialchars($request['fly_type']) : '' ?></span></div>
                                        <div class="detail-item"><span class="label">Start Date</span> <span class="value"><?=htmlspecialchars(date('d M Y', strtotime($request['start_date']))); ?></span></div>
                                        <div class="detail-item"><span class="label">Return Date</span> <span class="value"><?=htmlspecialchars(date('d M Y', strtotime($request['return_date']))); ?></span></div>
                                        <div class="detail-item"><span class="label">Total Days</span> <span class="value highlight"><?=htmlspecialchars($request['vacdays']); ?> Days</span></div>
                                        <div class="detail-item"><span class="label">Replacement</span> <span class="value"><?=parseName($request['replacement_person_name'] ?? 'N/A'); ?></span></div>
                                        <div class="detail-item"><span class="label">Requested On</span> <span class="value"><small><?=htmlspecialchars(date('d M Y, h:i A', strtotime($request['created_at']))); ?></small></span></div>
                                         <?php if (!empty($request['attachment_path'])): ?>
                                            <div class="detail-item"><span class="label">Attachment</span> <span class="value"><a href="<?=htmlspecialchars($request['attachment_path']); ?>" target="_blank">View Document</a></span></div>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <div class="report-section">
                                    <h5 class="section-title"><i class="fa fa-money-check-alt"></i>Payment Details</h5>
                                    <?php if (!$is_payable_leave): ?>
                                        <div class="alert alert-info">Salary and benefits are not applicable for this type of leave (<?=htmlspecialchars($request['vac_type']); ?>).</div>
                                    <?php else: ?>
                                        <div class="payment-summary">
                                            <ul>
                                                <li>
                                                    <div>
                                                        <span class="label">Working Days Salary</span>
                                                        <small class="text-muted d-block">Calculated for <?= htmlspecialchars($working_days); ?> day(s)</small>
                                                    </div>
                                                    <span class="value"><?=number_format($working_days_salary, 2); ?> SAR</span>
                                                </li>
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

                                <div class="row">
                                    <div class="col-md-7">
                                        <div class="report-section">
                                            <h5 class="section-title"><i class="fa fa-tasks"></i>Approval Status</h5>
                                            <div class="approval-timeline">
                                                <?php if ($request['approval_status'] == 'rejected'): ?>
                                                    <div class="timeline-item rejected">
                                                        <div class="icon"><i class="fa <?= $approval_steps_map['rejected']['icon'] ?>"></i></div>
                                                        <span class="status ml-3"><?= $approval_steps_map['rejected']['label'] ?></span>
                                                    </div>
                                                <?php else: ?>
                                                    <?php foreach ($approval_flow as $index => $status_key): 
                                                        $item_class = ''; // Default for future steps
                                                        if ($request['approval_status'] == 'gm_approved') {
                                                            $item_class = 'approved';
                                                        } else {
                                                            if ($current_status_index > $index) {
                                                                $item_class = 'approved';
                                                            } elseif ($current_status_index == $index) {
                                                                $item_class = 'pending';
                                                            }
                                                        }
                                                    ?>
                                                        <div class="timeline-item <?= $item_class ?>">
                                                            <div class="icon"><i class="fa <?= $approval_steps_map[$status_key]['icon'] ?>"></i></div>
                                                            <span class="status ml-3"><?= $approval_steps_map[$status_key]['label'] ?></span>
                                                        </div>
                                                    <?php endforeach; ?>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-5">
                                        <?php if(!empty($request['remarks']) || !empty($request['note'])): ?>
                                        <div class="report-section">
                                             <h5 class="section-title"><i class="fa fa-comments"></i>Remarks</h5>
                                             <?php if($request['approval_status'] == 'rejected'): ?>
                                                <div class="alert alert-danger mb-0"><?=nl2br(htmlspecialchars($request['note'])); ?></div>
                                             <?php elseif(!empty($request['remarks'])): ?>
                                                <div class="notes-section"><p class="mb-0"><?=nl2br(htmlspecialchars($request['remarks'])); ?></p></div>
                                             <?php endif; ?>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <?php // NEW IT CLEARANCE SECTION
                                if ($request['it_approval_status'] === 'cleared' && !empty($assigned_assets)): ?>
                                <div class="report-section">
                                    <h5 class="section-title"><i class="fa fa-laptop"></i>IT Clearance Details</h5>
                                    <div class="table-responsive">
                                        <table class="table table-bordered table-sm">
                                            <thead>
                                                <tr>
                                                    <th>Asset Name</th>
                                                    <th>Serial Number</th>
                                                    <th>Status</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($assigned_assets as $asset): 
                                                    $it_notes_lower = strtolower($request['it_notes'] ?? '');
                                                    $status = __('received');
                                                    $badge = 'success';
                                                    
                                                    // Check for keywords indicating the asset was not returned
                                                    if (
                                                        strpos($it_notes_lower, 'keep') !== false ||
                                                        strpos($it_notes_lower, 'kept') !== false ||
                                                        strpos($it_notes_lower, 'not received') !== false
                                                    ) {
                                                        $status = __('not_received');
                                                        $badge = 'warning';
                                                    }
                                                ?>
                                                <tr>
                                                    <td><?= htmlspecialchars($asset['asset_name']); ?></td>
                                                    <td><?= htmlspecialchars($asset['serial_number']); ?></td>
                                                    <td><span class="badge badge-<?= $badge ?>"><?= $status ?></span></td>
                                                </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                    <?php if (!empty($request['it_notes'])): ?>
                                    <div class="notes-section mt-2">
                                        <strong>IT Notes:</strong>
                                        <p class="mb-0"><?=nl2br(htmlspecialchars($request['it_notes'])); ?></p>
                                    </div>
                                    <?php endif; ?>
                                </div>
                                <?php endif; ?>
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