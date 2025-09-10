<?php
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

    // 2. MODIFIED: Fetch all data with a single query, now including contract vacation days and attachment path
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

    // 4. MODIFIED: Calculate Vacation Salary & Fees with new logic
    $vacation_salary = 0;
    $gosi_deduction = 0;
    $ticket_fee = 0;
    $permit_fee = 0;
    
    // Define leave types for which salary should not be calculated
    $non_payable_leave_types = ['Sick Leave', 'Casual Leave', 'Maternity Leave', 'Compassionate Leave', 'Business Trip', 'Compensatory Leave'];
    $is_payable_leave = !in_array($request['vac_type'], $non_payable_leave_types);


    if ($is_payable_leave && $request['fly_type'] !== 'emergency') {
        if ($salary) {
            // Sum all salary components for a full monthly salary
            $total_monthly_salary = 
                ($salary['basic'] ?? 0) + 
                ($salary['housing'] ?? 0) + 
                ($salary['transport'] ?? 0) + 
                ($salary['food'] ?? 0) +
                ($salary['misc'] ?? 0) +
                ($salary['cashier'] ?? 0) +
                ($salary['fuel'] ?? 0) +
                ($salary['tel'] ?? 0) +
                ($salary['other'] ?? 0) +
                ($salary['guard'] ?? 0);
                
            $applied_days = (float)$request['vacdays'];
            $contract_days = isset($request['contract_vacation_days']) ? (float)$request['contract_vacation_days'] : 0;

            // If applied days match the total contract days, pay full salary
            if ($contract_days > 0 && $applied_days == $contract_days) {
                $vacation_salary = $total_monthly_salary;
            } else {
                // Otherwise, calculate salary per day based on a 30-day month
                $vacation_salary = ($total_monthly_salary / 30) * $applied_days;
            }

            // Apply GOSI deduction only for Saudi employees (country_id = 191)
            if (isset($request['country_id']) && $request['country_id'] == 191 && isset($request['gosi']) && is_numeric($request['gosi'])) {
                $gosi_percentage = (float)$request['gosi'];
                $gosi_deduction = ($vacation_salary * $gosi_percentage) / 100;
            }
        }
        
        // Check for ticket and permit fees for non-saudi employees on annual or local vacation
        if (($request['vac_type'] === 'Fly' || $request['vac_type'] === 'Local Vacation') && $request['country_id'] != 191) {
            $ticket_fee = $request['ticket_pay'] ?? 0;
            $permit_fee = $request['permit_fee'] ?? 0;
        }
    }
    
    // Calculate final total payable
    $total_payable = ($vacation_salary) + $ticket_fee + $permit_fee - $gosi_deduction;

    $all_statuses = [
        'pending' => ['text' => 'Pending', 'badge' => 'warning'],
        'hr_assistant_approved' => ['text' => 'HR Assistant Approved', 'badge' => 'primary'],
        'hr_manager_approved' => ['text' => 'HR Manager Approved', 'badge' => 'info'],
        'gm_approved' => ['text' => 'GM Approved', 'badge' => 'success'],
        'rejected' => ['text' => 'Rejected', 'badge' => 'danger']
    ];
    
    $status_info = $all_statuses[$request['approval_status']] ?? ['text' => 'Unknown', 'badge' => 'secondary'];

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
        <link href="assets/css/bootstrap.min.css" rel="stylesheet" type="text/css" />
        <link href="assets/css/icons.css" rel="stylesheet" type="text/css" />
        <link href="assets/css/metismenu.min.css" rel="stylesheet" type="text/css" />
        <link href="assets/css/style.css" rel="stylesheet" type="text/css" />
        <link href="assets/css/style_dark.css" rel="stylesheet" type="text/css" />
        <script src="assets/js/modernizr.min.js"></script>
        <style>
            .report-container {
                font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            }
            .report-header {
                text-align: center;
                margin-bottom: 2rem;
                padding-bottom: 1rem;
                border-bottom: 1px solid #dee2e6;
            }
            .report-header img {
                max-height: 70px;
                margin-bottom: 1rem;
            }
            .report-title {
                font-weight: 600;
                font-size: 1.5rem;
                text-transform: uppercase;
                letter-spacing: 1px;
                color: #343a40;
            }
            .report-main-card {
                background-color: #fff;
                border-radius: .75rem;
                border: 1px solid #e9ecef;
                box-shadow: 0 0.5rem 1rem rgba(0,0,0,.05);
            }
            .report-card-header {
                background-color: #f8f9fa;
                padding: 1rem 1.5rem;
                border-bottom: 1px solid #e9ecef;
                display: flex;
                align-items: center;
            }
            .report-card-header .avatar {
                width: 60px;
                height: 60px;
                border-radius: 50%;
                margin-right: 1rem;
                border: 3px solid #fff;
                box-shadow: 0 0.125rem 0.25rem rgba(0,0,0,.075);
            }
            .report-card-header h4 {
                font-weight: 600;
                margin-bottom: .25rem;
            }
            .report-card-header p {
                color: #6c757d;
                margin-bottom: 0;
            }
            .report-card-body {
                padding: 1.5rem;
            }
            .section-title {
                font-weight: 600;
                color: #4a90e2;
                margin-bottom: 1rem;
                font-size: 1.1rem;
            }
            .detail-list {
                list-style: none;
                padding-left: 0;
            }
            .detail-list li {
                display: flex;
                justify-content: space-between;
                padding: .6rem 0;
                border-bottom: 1px solid #f1f1f1;
            }
            .detail-list li:last-child {
                border-bottom: none;
            }
            .detail-list .label {
                font-weight: 600;
                color: #6c757d;
            }
            .detail-list .value {
                font-weight: 500;
                color: #343a40;
            }
            .notes-section {
                background-color: #f8f9fa;
                border-radius: .5rem;
                padding: 1rem;
            }
            
            /* --- PRINT STYLES --- */
            @media print {
                @page {
                    size: A4;
                    margin: 1cm;
                }

                body {
                    background-color: #fff !important;
                }
                
                /* Hide everything that is not the report */
                .no-print,
                .left.side-menu,
                .footer,
                .topbar,
                #wrapper > .content-page > .content > .container-fluid > .row > .col-xl-12 > .card-box > .text-right {
                    display: none !important;
                }
                
                /* Ensure the report container and its parents are visible and take up full space */
                body, #wrapper, .content-page, .content, .container-fluid, .row, .col-xl-12, .card-box {
                    padding: 0 !important;
                    margin: 0 !important;
                    box-shadow: none !important;
                    border: none !important;
                    background: transparent !important;
                }

                .report-main-card {
                    box-shadow: none !important;
                    border: 1px solid #dee2e6 !important;
                    page-break-inside: avoid;
                }

                .report-container {
                    display: block !important;
                }
            }
        </style>
    </head>

    <body class="enlarged" data-keep-enlarged="true">
        <!-- Begin page -->
        <div id="wrapper">
            <!-- ========== Left Sidebar Start ========== -->
            <div class="left side-menu no-print">
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
                            <div class="col-xl-12">
                                <div class="card-box">
                                    <div class="text-right no-print mb-3">
                                        <a href="javascript:void(0);" onclick="window.print()" class="btn btn-primary waves-effect waves-light"><i class="fa fa-print mr-1"></i> Print Report</a>
                                    </div>

                                    <!-- THIS IS THE PRINTABLE CONTENT -->
                                    <div class="report-container" id="report-content">
                                        <div class="report-header">
                                            <img src="assets/images/logo.png" alt="Company Logo">
                                            <h2 class="report-title">Vacation Request Report</h2>
                                        </div>

                                        <div class="report-main-card">
                                            <div class="report-card-header">
                                                <img src="<?=htmlspecialchars($request['avatar'] ?? 'assets/images/users/avatar-1.jpg'); ?>" alt="Employee Avatar" class="avatar">
                                                <div>
                                                    <h4><?=htmlspecialchars($request['employee_name']); ?></h4>
                                                    <p>Employee ID: <?=htmlspecialchars($request['emp_id']); ?> | Request ID: #<?=htmlspecialchars($request['id']); ?></p>
                                                </div>
                                            </div>
                                            <div class="report-card-body">
                                                <div class="row">
                                                    <div class="col-md-6 mb-4">
                                                        <h5 class="section-title">Employee Information</h5>
                                                        <ul class="detail-list">
                                                            <li><span class="label">Department</span> <span class="value"><?=htmlspecialchars($request['deptname']); ?></span></li>
                                                            <li><span class="label">Section</span> <span class="value"><?=htmlspecialchars($request['section_name'] ?? 'N/A'); ?></span></li>
                                                            <li><span class="label">Nationality</span> <span class="value"><?=htmlspecialchars($request['country_name']); ?></span></li>
                                                            <li><span class="label">Joining Date</span> <span class="value"><?=htmlspecialchars(date('d M Y', strtotime($request['joining_date']))); ?></span></li>
                                                        </ul>
                                                    </div>
                                                    <div class="col-md-6 mb-4">
                                                        <h5 class="section-title">Request Information</h5>
                                                        <ul class="detail-list">
                                                            <li><span class="label">Request Date</span> <span class="value"><?=htmlspecialchars(date('d M Y, h:i A', strtotime($request['created_at']))); ?></span></li>
                                                            <li><span class="label">Status</span> <span class="value"><span class="badge badge-<?=$status_info['badge']; ?> p-1"><?=$status_info['text']; ?></span></span></li>
                                                            <li><span class="label">Replacement</span> <span class="value"><?=htmlspecialchars($request['replacement_person_name'] ?? 'N/A'); ?></span></li>
                                                        </ul>
                                                    </div>
                                                </div>
                                                <hr>
                                                <div class="row">
                                                    <div class="col-md-6 mb-4">
                                                        <h5 class="section-title">Vacation Details</h5>
                                                         <ul class="detail-list">
                                                            <li><span class="label">Vacation Type</span> <span class="value"><?=htmlspecialchars($request['vac_type']); ?><?= !empty($request['fly_type']) ? ' | ' . htmlspecialchars($request['fly_type']) : '' ?></span></li>
                                                            <li><span class="label">Start Date</span> <span class="value"><?=htmlspecialchars(date('d M Y', strtotime($request['start_date']))); ?></span></li>
                                                            <li><span class="label">Return Date</span> <span class="value"><?=htmlspecialchars(date('d M Y', strtotime($request['return_date']))); ?></span></li>
                                                            <li><span class="label">Total Days</span> <span class="value font-weight-bold"><?=htmlspecialchars($request['vacdays']); ?> Days</span></li>
                                                            <!-- NEW: Attachment Link Display -->
                                                            <?php if (!empty($request['attachment_path'])): ?>
                                                                <li>
                                                                    <span class="label">Attachment</span> 
                                                                    <span class="value">
                                                                        <a href="<?=htmlspecialchars($request['attachment_path']); ?>" target="_blank" class="btn btn-sm btn-info no-print">
                                                                            <i class="fa fa-paperclip mr-1"></i> View Document
                                                                        </a>
                                                                    </span>
                                                                </li>
                                                            <?php endif; ?>
                                                        </ul>
                                                    </div>
                                                     <div class="col-md-6 mb-4">
                                                        <h5 class="section-title">Payment Details</h5>
                                                        <!-- NEW: Logic to hide payment for non-payable leave types -->
                                                        <?php if (!$is_payable_leave): ?>
                                                            <div class="alert alert-info">
                                                                Salary and benefits are not applicable for this type of leave (<?=htmlspecialchars($request['vac_type']); ?>).
                                                            </div>
                                                        <?php else: ?>
                                                            <ul class="detail-list">
                                                                <li><span class="label">Vacation Salary</span> <span class="value"><?=number_format($vacation_salary, 2); ?> SAR</span></li>
                                                                <?php if ($ticket_fee > 0): ?>
                                                                    <li><span class="label">Ticket Payment</span> <span class="value"><?=number_format($ticket_fee, 2); ?> SAR</span></li>
                                                                <?php endif; ?>
                                                                <?php if ($permit_fee > 0): ?>
                                                                    <li><span class="label">Permit Fee</span> <span class="value"><?=number_format($permit_fee, 2); ?> SAR</span></li>
                                                                <?php endif; ?>
                                                                <?php if ($gosi_deduction > 0): ?>
                                                                    <li><span class="label text-danger">GOSI Deduction</span> <span class="value text-danger">-<?=number_format($gosi_deduction, 2); ?> SAR</span></li>
                                                                <?php endif; ?>
                                                                <li class="bg-light p-2 rounded"><span class="label">Total Payable</span> <span class="value font-weight-bold text-success h5 mb-0"><?=number_format($total_payable, 2); ?> SAR</span></li>
                                                            </ul>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>

                                                <?php if(!empty($request['remarks'])): ?>
                                                <hr>
                                                <div class="row">
                                                    <div class="col-12">
                                                        <?php if($request['approval_status'] == 'rejected'): ?>
                                                            <h5 class="section-title text-danger"><i class="fas fa-comment-slash mr-2"></i>Rejection Note</h5>
                                                            <div class="alert alert-danger mb-0">
                                                                <?=nl2br(htmlspecialchars($request['note'])); ?>
                                                            </div>
                                                        <?php else: ?>
                                                            <h5 class="section-title"><i class="fas fa-comments mr-2"></i>Notes & Remarks</h5>
                                                            <div class="notes-section">
                                                                <p class="mb-0"><?=nl2br(htmlspecialchars($request['remarks'])); ?></p>
                                                            </div>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                    <!-- END PRINTABLE CONTENT -->

                                </div>
                            </div>
                        </div>
                    </div> <!-- container -->
                </div> <!-- content -->
                <footer class="footer no-print">
                    <?= $site_footer ?>
                </footer>
            </div>
            <!-- ============================================================== -->
            <!-- End Right content here -->
            <!-- ============================================================== -->
        </div>
        <!-- END wrapper -->

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
