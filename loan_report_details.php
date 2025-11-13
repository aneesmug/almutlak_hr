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

    // Fetch Approval Chain from request_approvers table
    $approval_chain = [];
    if (!empty($loan_details['inv_no'])) {
        $chain_sql = "SELECT ra.*, 
                      COALESCE(e.name, al.fullname, al.username) as approver_name,
                      al.user_type
                      FROM request_approvers ra
                      LEFT JOIN employees e ON ra.approver_id = e.emp_id
                      LEFT JOIN admin_login al ON ra.approver_id = al.id_iqama
                      WHERE ra.request_inv_no = ? AND ra.request_type_id = 2
                      ORDER BY ra.approval_level";
        $stmt_chain = $conDB->prepare($chain_sql);
        $stmt_chain->bind_param("s", $loan_details['inv_no']);
        $stmt_chain->execute();
        $chain_result = $stmt_chain->get_result();
        while ($row = $chain_result->fetch_assoc()) {
            $approval_chain[] = $row;
        }
        $stmt_chain->close();
    }

    // Fetch Status History from smt_request_status table
    $status_history = [];
    if (!empty($loan_details['inv_no'])) {
        $history_sql = "SELECT * FROM smt_request_status 
                        WHERE inv_no = ? 
                        ORDER BY created_at ASC";
        $stmt_history = $conDB->prepare($history_sql);
        $stmt_history->bind_param("s", $loan_details['inv_no']);
        $stmt_history->execute();
        $history_result = $stmt_history->get_result();
        while ($row = $history_result->fetch_assoc()) {
            $status_history[] = $row;
        }
        $stmt_history->close();
    }

    // Fetch Legacy Approval History (for backward compatibility)
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
        <link rel="shortcut icon" href="<?=get_setting($conDB, 'favicon')?>">
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
            
            /* Payment History Styles */
            .payment-summary-card {
                border-left: 3px solid;
                transition: all 0.3s ease;
            }
            
            .payment-summary-card:hover {
                box-shadow: 0 0.25rem 0.5rem rgba(0, 0, 0, .1);
            }
            
            .table thead th {
                font-weight: 600;
                font-size: 0.85rem;
                text-transform: uppercase;
                letter-spacing: 0.5px;
            }
            
            .table tbody tr:hover {
                background-color: #f8f9fa;
            }
            
            .btn-xs {
                padding: 0.15rem 0.4rem;
                font-size: 0.75rem;
                line-height: 1.2;
                border-radius: 0.2rem;
            }
            
            .badge {
                font-weight: 500;
                padding: 0.35em 0.6em;
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

                * {
                    -webkit-print-color-adjust: exact !important;
                    print-color-adjust: exact !important;
                }

                @page {
                    margin: 0.5cm;
                    size: A4;
                }

                body,
                html {
                    background: #fff !important;
                    margin: 0 !important;
                    padding: 0 !important;
                    width: 100% !important;
                    font-size: 11px !important;
                    line-height: 1.3 !important;
                }

                body.enlarged {
                    padding-left: 0 !important;
                }

                .no-print,
                .left.side-menu,
                .footer,
                .topbar,
                .navbar,
                .breadcrumb,
                .slimscroll-menu {
                    display: none !important;
                    height: 0 !important;
                    margin: 0 !important;
                    padding: 0 !important;
                }

                #wrapper {
                    margin: 0 !important;
                    padding: 0 !important;
                    width: 100% !important;
                }

                .content-page {
                    margin: 0 !important;
                    margin-left: 0 !important;
                    padding: 0 !important;
                    min-height: auto !important;
                    width: 100% !important;
                }

                .content {
                    margin: 0 !important;
                    margin-top: 0 !important;
                    padding: 0 !important;
                    width: 100% !important;
                }

                .container-fluid {
                    padding: 0 !important;
                    margin: 0 !important;
                    width: 100% !important;
                }

                .row {
                    margin-left: 0 !important;
                    margin-right: 0 !important;
                }

                .col-md-6, .col-xl-12 {
                    padding-left: 5px !important;
                    padding-right: 5px !important;
                }

                .card-box {
                    padding: 0 !important;
                    margin: 0 !important;
                    box-shadow: none !important;
                    border: none !important;
                }

                .report-container {
                    margin: 0 !important;
                    padding: 5px !important;
                }

                .report-header {
                    margin-top: 0 !important;
                    margin-bottom: 0.5rem !important;
                    padding-top: 0 !important;
                    padding-bottom: 0.3rem !important;
                    border-bottom: 1px solid #dee2e6;
                    text-align: center !important;
                }

                .report-header img {
                    max-height: 40px !important;
                    margin-bottom: 0.3rem !important;
                    display: block !important;
                    margin-left: auto !important;
                    margin-right: auto !important;
                }

                .report-title {
                    font-size: 1rem !important;
                    margin: 0 !important;
                    font-weight: 600;
                }

                .report-main-card {
                    box-shadow: none !important;
                    border: 1px solid #dee2e6 !important;
                    page-break-inside: avoid;
                    margin: 0 !important;
                }

                .report-card-header {
                    padding: 0.5rem !important;
                    background-color: #f8f9fa !important;
                }

                .report-card-header h4 {
                    font-size: 0.95rem !important;
                    margin: 0 !important;
                }

                .report-card-header p {
                    font-size: 0.8rem !important;
                    margin: 0 !important;
                }

                .report-card-header .avatar {
                    width: 40px !important;
                    height: 40px !important;
                    margin-right: 0.5rem !important;
                }

                .card-body {
                    padding: 0.5rem !important;
                }

                .section-title {
                    font-size: 0.9rem !important;
                    margin-bottom: 0.4rem !important;
                    font-weight: 600;
                }

                .detail-list li {
                    padding: 0.25rem 0 !important;
                    font-size: 0.85rem !important;
                }

                .mb-4 {
                    margin-bottom: 0.5rem !important;
                }

                .payment-summary-card {
                    padding: 0.4rem !important;
                    margin-bottom: 0.4rem !important;
                }

                .payment-summary-card h6 {
                    font-size: 0.8rem !important;
                    margin-bottom: 0.2rem !important;
                }

                .payment-summary-card h3 {
                    font-size: 1rem !important;
                    margin: 0 !important;
                }

                table {
                    font-size: 0.8rem !important;
                    margin-bottom: 0.5rem !important;
                }

                table thead th {
                    padding: 0.3rem !important;
                    font-size: 0.75rem !important;
                }

                table tbody td {
                    padding: 0.3rem !important;
                }

                .badge {
                    font-size: 0.7rem !important;
                    padding: 0.2em 0.4em !important;
                }

                .btn-xs {
                    font-size: 0.7rem !important;
                    padding: 0.1rem 0.3rem !important;
                }

                h5 {
                    font-size: 0.9rem !important;
                    margin-bottom: 0.4rem !important;
                }

                .row {
                    page-break-inside: avoid;
                }

                table {
                    page-break-inside: auto;
                }

                tr {
                    page-break-inside: avoid;
                    page-break-after: auto;
                }

                thead {
                    display: table-header-group;
                }

                tfoot {
                    display: table-footer-group;
                }

                .alert {
                    padding: 0.4rem !important;
                    font-size: 0.8rem !important;
                    margin-bottom: 0.5rem !important;
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
                    <div class="topbar-left"><a href="dashboard.php" class="logo"><span><img src="<?=get_setting($conDB, 'logo')?>" alt="" height="22"></span><i><img src="<?=get_setting($conDB, 'white_logo')?>" alt="" height="28"></i></a></div><?php include("./includes/main_menu.php"); ?><div class="clearfix"></div>
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
                                            <img src="<?=get_setting($conDB, 'logo')?>" alt="Company Logo">
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
                                                            <li><span class="label"><?= __('invoice_no_label') ?></span> <span class="value"><code><?= htmlspecialchars($loan_details['inv_no'] ?? 'N/A'); ?></code></span></li>
                                                            <li><span class="label"><?= __('loan_type_label') ?></span> <span class="value"><?= htmlspecialchars(ucwords(str_replace('_', ' ', $loan_details['loan_type'] ?? 'N/A'))); ?></span></li>
                                                            <li><span class="label"><?= __('loan_amount_label') ?></span> <span class="value"><?= number_format($loan_details['loan_amount'], 2); ?> <?= __('sar_currency') ?></span></li>
                                                            <li><span class="label"><?= __('installments_label') ?></span> <span class="value"><?= htmlspecialchars($loan_details['installments'] ?? '1'); ?> <?= __('months') ?></span></li>
                                                            <li><span class="label"><?= __('total_payable_label') ?></span> <span class="value"><?= number_format($loan_details['total_payable'], 2); ?> <?= __('sar_currency') ?></span></li>
                                                            <li><span class="label"><?= __('monthly_deduction_label') ?></span> <span class="value"><?= number_format($loan_details['monthly_deduction'], 2); ?> <?= __('sar_currency') ?></span></li>
                                                            <li><span class="label"><?= __('start_date_label') ?></span> <span class="value"><?= date('d M Y', strtotime($loan_details['start_date'])); ?></span></li>
                                                            <li><span class="label"><?= __('end_date_label') ?></span> <span class="value"><?= date('d M Y', strtotime($loan_details['end_date'])); ?></span></li>
                                                            <li><span class="label"><?= __('current_status_label') ?></span><span class="value font-weight-bold text-primary"><?= $status_text ?></span></li>
                                                        </ul>
                                                    </div>
                                                    <div class="col-md-6 mb-4">
                                                        <h5 class="section-title"><i class="fa fa-history"></i> <?= __('payment_history_header') ?></h5>
                                                        <?php if (empty($payment_history)): ?>
                                                            <div class="alert alert-info">
                                                                <i class="fa fa-info-circle"></i> <?= __('no_payments_recorded_yet') ?>
                                                            </div>
                                                        <?php else: 
                                                            // Calculate totals
                                                            $total_paid = 0;
                                                            foreach ($payment_history as $payment) {
                                                                $total_paid += $payment['amount'];
                                                            }
                                                            $remaining_balance = $loan_details['total_payable'] - $total_paid;
                                                        ?>
                                                            <!-- Payment Summary Cards -->
                                                            <div class="row mb-3">
                                                                <div class="col-6">
                                                                    <div class="card border-success mb-2">
                                                                        <div class="card-body p-2 text-center">
                                                                            <small class="text-muted d-block"><?= __('total_paid') ?></small>
                                                                            <h5 class="mb-0 text-success font-weight-bold"><?= number_format($total_paid, 2); ?> SAR</h5>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                                <div class="col-6">
                                                                    <div class="card border-<?= $remaining_balance > 0 ? 'danger' : 'success' ?> mb-2">
                                                                        <div class="card-body p-2 text-center">
                                                                            <small class="text-muted d-block"><?= __('remaining_balance') ?></small>
                                                                            <h5 class="mb-0 text-<?= $remaining_balance > 0 ? 'danger' : 'success' ?> font-weight-bold"><?= number_format($remaining_balance, 2); ?> SAR</h5>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                            
                                                            <!-- Payment Details Table -->
                                                            <div class="table-responsive">
                                                                <table class="table table-sm table-bordered">
                                                                    <thead class="thead-light">
                                                                        <tr>
                                                                            <th><?= __('date_header') ?></th>
                                                                            <th><?= __('method_header') ?></th>
                                                                            <th><?= __('receipt_id') ?></th>
                                                                            <th class="text-right"><?= __('amount_header') ?></th>
                                                                            <th class="text-center no-print"><?= __('attachment') ?></th>
                                                                        </tr>
                                                                    </thead>
                                                                    <tbody>
                                                                        <?php foreach ($payment_history as $payment): 
                                                                            $payment_method = $payment['payment_method'] ?? 'auto';
                                                                            $payment_method_badge = '';
                                                                            $badge_icon = '';
                                                                            switch($payment_method) {
                                                                                case 'manual':
                                                                                    $payment_method_badge = 'badge-success';
                                                                                    $badge_icon = 'fa-hand-paper-o';
                                                                                    $method_text = __('manual');
                                                                                    break;
                                                                                case 'payroll':
                                                                                    $payment_method_badge = 'badge-primary';
                                                                                    $badge_icon = 'fa-calendar';
                                                                                    $method_text = __('payroll');
                                                                                    break;
                                                                                default:
                                                                                    $payment_method_badge = 'badge-info';
                                                                                    $badge_icon = 'fa-cog';
                                                                                    $method_text = __('auto');
                                                                            }
                                                                        ?>
                                                                        <tr>
                                                                            <td><?= date('d M Y', strtotime($payment['payment_date'])); ?></td>
                                                                            <td>
                                                                                <span class="badge <?= $payment_method_badge ?>">
                                                                                    <i class="fa <?= $badge_icon ?>"></i> <?= $method_text ?>
                                                                                </span>
                                                                            </td>
                                                                            <td>
                                                                                <?php if (!empty($payment['receipt_id'])): ?>
                                                                                    <small><?= htmlspecialchars($payment['receipt_id']); ?></small>
                                                                                <?php else: ?>
                                                                                    <span class="text-muted">-</span>
                                                                                <?php endif; ?>
                                                                            </td>
                                                                            <td class="text-right font-weight-bold"><?= number_format($payment['amount'], 2); ?> SAR</td>
                                                                            <td class="text-center no-print">
                                                                                <?php if (!empty($payment['attachment'])): 
                                                                                    // Determine file path based on payment method
                                                                                    if ($payment_method === 'manual') {
                                                                                        $file_path = './assets/loan_manual_payments/' . htmlspecialchars($payment['attachment']);
                                                                                    } else {
                                                                                        $file_path = './assets/loan_receipts/' . htmlspecialchars($payment['attachment']);
                                                                                    }
                                                                                ?>
                                                                                    <a href="<?= $file_path; ?>" target="_blank" class="btn btn-xs btn-info">
                                                                                        <i class="fa fa-eye"></i>
                                                                                    </a>
                                                                                <?php else: ?>
                                                                                    <span class="text-muted">-</span>
                                                                                <?php endif; ?>
                                                                            </td>
                                                                        </tr>
                                                                        <?php if (!empty($payment['note'])): ?>
                                                                        <tr>
                                                                            <td colspan="5" class="bg-light">
                                                                                <small class="text-muted">
                                                                                    <i class="fa fa-comment"></i> <strong><?= __('note_label') ?>:</strong> <?= htmlspecialchars($payment['note']); ?>
                                                                                </small>
                                                                            </td>
                                                                        </tr>
                                                                        <?php endif; ?>
                                                                        <?php endforeach; ?>
                                                                    </tbody>
                                                                    <tfoot class="font-weight-bold bg-light">
                                                                        <tr>
                                                                            <td colspan="3" class="text-right"><?= __('total_paid') ?>:</td>
                                                                            <td class="text-right text-success"><?= number_format($total_paid, 2); ?> SAR</td>
                                                                            <td class="no-print"></td>
                                                                        </tr>
                                                                    </tfoot>
                                                                </table>
                                                            </div>
                                                            
                                                            <?php if ($remaining_balance <= 0): ?>
                                                            <div class="alert alert-success mt-2 mb-0">
                                                                <i class="fa fa-check-circle"></i> <strong><?= __('loan_fully_paid') ?></strong>
                                                            </div>
                                                            <?php endif; ?>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                                <hr>
                                                
                                                <!-- Approval Chain Section -->
                                                <div class="row mb-4">
                                                    <div class="col-12">
                                                        <h5 class="section-title"><i class="fa fa-check-circle"></i> <?= __('Approval Chain') ?></h5>
                                                        <?php if (empty($approval_chain)): ?>
                                                            <div class="alert alert-info"><?= __('No approval chain found') ?></div>
                                                        <?php else: ?>
                                                            <div class="table-responsive">
                                                                <table class="table table-sm table-bordered">
                                                                    <thead class="thead-light">
                                                                        <tr>
                                                                            <th><?= __('Level') ?></th>
                                                                            <th><?= __('Approver') ?></th>
                                                                            <th><?= __('Status') ?></th>
                                                                            <th><?= __('Date & Time') ?></th>
                                                                        </tr>
                                                                    </thead>
                                                                    <tbody>
                                                                        <?php foreach ($approval_chain as $level): ?>
                                                                            <tr>
                                                                                <td><strong>Level <?= $level['approval_level'] ?></strong></td>
                                                                                <td><?= htmlspecialchars($level['approver_name'] ?? 'Pending Assignment'); ?></td>
                                                                                <td>
                                                                                    <?php 
                                                                                    $status_badge = '';
                                                                                    switch($level['status']) {
                                                                                        case 'approved':
                                                                                            $status_badge = '<span class="badge badge-success">Approved</span>';
                                                                                            break;
                                                                                        case 'rejected':
                                                                                            $status_badge = '<span class="badge badge-danger">Rejected</span>';
                                                                                            break;
                                                                                        case 'pending':
                                                                                            $status_badge = '<span class="badge badge-warning">Pending</span>';
                                                                                            break;
                                                                                        case 'awaiting':
                                                                                            $status_badge = '<span class="badge badge-secondary">Awaiting</span>';
                                                                                            break;
                                                                                        default:
                                                                                            $status_badge = '<span class="badge badge-light">' . htmlspecialchars($level['status']) . '</span>';
                                                                                    }
                                                                                    echo $status_badge;
                                                                                    ?>
                                                                                </td>
                                                                                <td>
                                                                                    <?php if (!empty($level['action_date'])): ?>
                                                                                        <small><?= date('d M Y, h:i A', strtotime($level['action_date'])); ?></small>
                                                                                    <?php else: ?>
                                                                                        <span class="text-muted">-</span>
                                                                                    <?php endif; ?>
                                                                                </td>
                                                                            </tr>
                                                                        <?php endforeach; ?>
                                                                    </tbody>
                                                                </table>
                                                            </div>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                                
                                                <!-- Payment Proof Section -->
                                                <?php if (!empty($loan_details['payment_proof_file']) || !empty($loan_details['final_approved_amount'])): ?>
                                                <div class="row mb-4">
                                                    <div class="col-12">
                                                        <h5 class="section-title"><i class="fa fa-file-invoice-dollar"></i> <?= __('Payment Proof') ?></h5>
                                                        <div class="card border-primary">
                                                            <div class="card-body">
                                                                <div class="row">
                                                                    <?php if (!empty($loan_details['final_approved_amount'])): ?>
                                                                    <div class="col-md-6">
                                                                        <div class="mb-2">
                                                                            <strong class="text-primary"><?= __('Final Approved Amount') ?>:</strong>
                                                                            <h4 class="mb-0 text-success"><?= number_format($loan_details['final_approved_amount'], 2); ?> SAR</h4>
                                                                        </div>
                                                                    </div>
                                                                    <?php endif; ?>
                                                                    
                                                                    <?php if (!empty($loan_details['payment_proof_file'])): ?>
                                                                    <div class="col-md-6">
                                                                        <div class="mb-2">
                                                                            <strong class="text-primary"><?= __('Payment Proof Attachment') ?>:</strong><br>
                                                                            <a href="./assets/loan_payment_proofs/<?= htmlspecialchars($loan_details['payment_proof_file']); ?>" 
                                                                               target="_blank" 
                                                                               class="btn btn-info btn-sm mt-2">
                                                                                <i class="fa fa-download"></i> <?= __('View/Download Payment Proof') ?>
                                                                            </a>
                                                                        </div>
                                                                    </div>
                                                                    <?php endif; ?>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <?php endif; ?>
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
