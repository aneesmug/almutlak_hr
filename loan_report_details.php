<?php

/****************************************************************
 * MODIFICATION SUMMARY:
 * 1. NEW FILE: This file was created to display a detailed, printable report for a single loan request.
 * 2. DATA FETCHING: It securely fetches all relevant data for the loan, the employee, and the loan's approval and payment history.
 * 3. DYNAMIC LAYOUT: The report displays employee information, loan details, a full payment history with payment methods, and a step-by-step approval timeline.
 * 4. PRINT STYLES: Includes CSS to ensure the report is formatted correctly for printing.
 ****************************************************************/
// require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/session_check.php';
$query = mysqli_query($conDB, "SELECT * FROM `admin_login` WHERE `id_iqama`='" . $username . "'");
if (mysqli_num_rows($query) == 1) {
    include("./includes/avatar_select.php");

    $loan_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    $emp_id = isset($_GET['emp_id']) ? $_GET['emp_id'] : '';

    if ($loan_id === 0 || empty($emp_id)) {
        die(__('invalid_request_parameters'));
    }

    // Fetch main loan and employee data
    $sql = "SELECT 
                l.*, 
                e.name as employee_name,
                e.avatar,
                e.joining_date,
                d.dep_nme AS `deptname`,
                s.section_name,
                c.name AS `country_name`
            FROM emp_loan l
            JOIN employees e ON l.emp_id = e.emp_id
            LEFT JOIN department d ON e.dept = d.id
            LEFT JOIN section s ON e.sectin_nme = s.id
            LEFT JOIN countries c ON e.country = c.id
            WHERE l.id = ? AND l.emp_id = ?";

    $stmt = $conDB->prepare($sql);
    $stmt->bind_param("is", $loan_id, $emp_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $loan_details = $result->fetch_assoc();
    $stmt->close();

    if (!$loan_details) {
        die(__('loan_request_not_found'));
    }

    // Fetch Payment History
    $payment_history = [];
    $stmt_payments = $conDB->prepare("SELECT * FROM `emp_loan_payments` WHERE `loan_id` = ? ORDER BY `payment_date` ASC");
    $stmt_payments->bind_param("i", $loan_id);
    $stmt_payments->execute();
    $payments_result = $stmt_payments->get_result();
    while ($row = $payments_result->fetch_assoc()) {
        $payment_history[] = $row;
    }
    $stmt_payments->close();

    // Fetch Approval History
    $approval_history = [];
    $stmt_approvals = $conDB->prepare("SELECT * FROM `emp_loan_approvals` WHERE `loan_id` = ? ORDER BY `created_at` ASC");
    $stmt_approvals->bind_param("i", $loan_id);
    $stmt_approvals->execute();
    $approvals_result = $stmt_approvals->get_result();
    while ($row = $approvals_result->fetch_assoc()) {
        $approval_history[] = $row;
    }
    $stmt_approvals->close();

    $all_loan_statuses = [
        'dept_manager_pending' => __('pending_department_manager'),
        'hr_manager_pending' => __('pending_hr_manager'),
        'finance_manager_pending' => __('pending_finance_manager'),
        'gm_pending' => __('pending_gm'),
        'finance_assistant_pending' => __('pending_final_processing'),
        'approved' => __('approved_and_processed'),
        'paid' => __('paid_and_closed'),
        'rejected' => __('rejected')
    ];
    $status_text = $all_loan_statuses[$loan_details['status']] ?? __('unknown_status');

?>
    <!doctype html>
    <html lang="<?= $current_lang ?? 'en' ?>" <?= ($is_rtl ?? false) ? 'dir="rtl"' : '' ?>>

    <head>
        <meta charset="utf-8" />
        <title><?= $site_title ?> - <?= __('loan_report_title') ?></title>
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
                box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, .05);
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
                box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, .075);
            }

            .section-title {
                font-weight: 600;
                color: #4a90e2;
                margin-bottom: 1rem;
                font-size: 1.1rem;
            }

            .detail-list li {
                display: flex;
                justify-content: space-between;
                padding: .6rem 0;
                border-bottom: 1px solid #f1f1f1;
            }

            .detail-list .label {
                font-weight: 600;
                color: #6c757d;
            }

            .timeline {
                list-style: none;
                padding: 0;
                position: relative;
            }

            .timeline:before {
                content: '';
                position: absolute;
                top: 0;
                bottom: 0;
                width: 4px;
                background: #e9ecef;
                left: 30px;
                margin-left: -2px;
            }

            .timeline-item {
                margin-bottom: 20px;
                position: relative;
            }

            .timeline-icon {
                position: absolute;
                left: 30px;
                top: 0;
                width: 40px;
                height: 40px;
                margin-left: -20px;
                background-color: #fff;
                border: 4px solid #e9ecef;
                border-radius: 50%;
                text-align: center;
                line-height: 32px;
                font-size: 1.2rem;
            }

            .timeline-body {
                margin-left: 70px;
                background: #f8f9fa;
                padding: 15px;
                border-radius: 6px;
            }

            @media print {

                body,
                html {
                    background: #fff !important;
                }

                .no-print,
                .left.side-menu,
                .footer,
                .topbar {
                    display: none !important;
                }

                #wrapper,
                .content-page,
                .content,
                .container-fluid,
                .card-box {
                    padding: 0 !important;
                    margin: 0 !important;
                    box-shadow: none !important;
                    border: none !important;
                }

                .report-main-card {
                    box-shadow: none !important;
                    border: 1px solid #dee2e6 !important;
                    page-break-inside: avoid;
                }
            }
        </style>
        <?php if ($is_rtl): ?>
            <link href="assets/css/style_rtl.css" rel="stylesheet" type="text/css" />
        <?php endif; ?>
        <script>
            window.lang = <?= json_encode($GLOBALS['translations'] ?? []) ?>;
        </script>
    </head>

    <body class="enlarged" data-keep-enlarged="true">
        <!-- The "no-print" class was removed from the div below to fix the printing issue -->
        <div id="wrapper">
            <div class="left side-menu">
                <div class="slimscroll-menu" id="remove-scroll">
                    <div class="topbar-left"><a href="dashboard.php" class="logo"><span><img src="assets/images/logo.png" alt="" height="22"></span><i><img src="assets/images/logo_sm.png" alt="" height="28"></i></a></div><?php include("./includes/main_menu.php"); ?><div class="clearfix"></div>
                </div>
            </div>
            <div class="content-page">
                <?php include("./includes/topbar.php"); ?>
                <div class="content">
                    <div class="container-fluid">
                        <div class="row">
                            <div class="col-xl-12">
                                <div class="card-box">
                                    <div class="text-right no-print mb-3">
                                        <a href="javascript:void(0);" onclick="window.print()" class="btn btn-primary waves-effect waves-light"><i class="fa fa-print mr-1"></i> <?= __('print_report_button') ?></a>
                                    </div>
                                    <div class="report-container" id="report-content">
                                        <div class="report-header">
                                            <img src="assets/images/logo.png" alt="Company Logo">
                                            <h2 class="report-title"><?= __('loan_request_report_header') ?></h2>
                                        </div>
                                        <div class="report-main-card">
                                            <div class="report-card-header">
                                                <img src="<?= htmlspecialchars($loan_details['avatar'] ?? 'assets/images/users/avatar-1.jpg'); ?>" alt="Employee Avatar" class="avatar">
                                                <div>
                                                    <h4><?= htmlspecialchars($loan_details['employee_name']); ?></h4>
                                                    <p class="mb-0"><?= __('employee_id_label') ?>: <?= htmlspecialchars($loan_details['emp_id']); ?> | <?= __('loan_id_label') ?>: #<?= htmlspecialchars($loan_details['id']); ?></p>
                                                </div>
                                            </div>
                                            <div class="card-body">
                                                <div class="row">
                                                    <div class="col-md-6 mb-4">
                                                        <h5 class="section-title"><?= __('loan_details_header') ?></h5>
                                                        <ul class="list-unstyled detail-list">
                                                            <li><span class="label"><?= __('loan_amount_label') ?></span> <span class="value"><?= number_format($loan_details['loan_amount'], 2); ?> <?= __('sar_currency') ?></span></li>
                                                            <li><span class="label"><?= __('total_payable_label') ?></span> <span class="value"><?= number_format($loan_details['total_payable'], 2); ?> <?= __('sar_currency') ?></span></li>
                                                            <li><span class="label"><?= __('monthly_deduction_label') ?></span> <span class="value"><?= number_format($loan_details['monthly_deduction'], 2); ?> <?= __('sar_currency') ?></span></li>
                                                            <li><span class="label"><?= __('start_date_label') ?></span> <span class="value"><?= date('d M Y', strtotime($loan_details['start_date'])); ?></span></li>
                                                            <li><span class="label"><?= __('end_date_label') ?></span> <span class="value"><?= date('d M Y', strtotime($loan_details['end_date'])); ?></span></li>
                                                            <li><span class="label"><?= __('current_status_label') ?></span><span class="value font-weight-bold text-primary"><?= $status_text ?></span></li>
                                                        </ul>
                                                    </div>
                                                    <div class="col-md-6 mb-4">
                                                        <h5 class="section-title"><?= __('payment_history_header') ?></h5><?php if (empty($payment_history)): ?><div class="alert alert-info"><?= __('no_payments_recorded_yet') ?></div><?php else: ?><div class="table-responsive">
                                                                <table class="table table-sm table-striped">
                                                                    <thead>
                                                                        <tr>
                                                                            <th><?= __('date_header') ?></th>
                                                                            <th><?= __('method_header') ?></th>
                                                                            <th class="text-right"><?= __('amount_header') ?></th>
                                                                        </tr>
                                                                    </thead>
                                                                    <tbody><?php foreach ($payment_history as $payment): ?><tr>
                                                                                <td><?= date('d M Y', strtotime($payment['payment_date'])); ?></td>
                                                                                <td><?= __($payment['payment_method']) ?></td>
                                                                                <td class="text-right"><?= number_format($payment['amount'], 2); ?> <?= __('sar_currency') ?></td>
                                                                            </tr><?php endforeach; ?></tbody>
                                                                </table>
                                                            </div><?php endif; ?>
                                                    </div>
                                                </div>
                                                <hr>
                                                <div class="row">
                                                    <div class="col-12">
                                                        <h5 class="section-title"><?= __('approval_history_header') ?></h5><?php if (empty($approval_history)): ?><div class="alert alert-info"><?= __('no_approval_history_found') ?></div><?php else: ?><ul class="timeline"><?php foreach ($approval_history as $approval): ?><li class="timeline-item">
                                                                        <div class="timeline-icon text-<?= $approval['status'] == 'approved' ? 'success' : 'danger' ?>"><i class="fa fa-<?= $approval['status'] == 'approved' ? 'check' : 'times' ?>"></i></div>
                                                                        <div class="timeline-body"><strong><?= __($approval['approver_role']) ?></strong> <?= __($approval['status'])." ".__('on') ?> <?= date('d M Y, h:i A', strtotime($approval['created_at'])) ?><?php if (!empty($approval['notes'])): ?><p class="mb-0 mt-2 text-muted"><em><?= __('note_label') ?>: <?= htmlspecialchars($approval['notes']) ?></em></p><?php endif; ?></div>
                                                                    </li><?php endforeach; ?></ul><?php endif; ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
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
