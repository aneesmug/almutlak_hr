<?php

/****************************************************************
 * MODIFICATION SUMMARY:
 * 1. NEW FILE: This file was created to display a detailed, printable report for a single loan request.
 * 2. DATA FETCHING: It securely fetches all relevant data for the loan, the employee, and the loan's approval and payment history.
 * 3. DYNAMIC LAYOUT: The report displays employee information, loan details, a full payment history with payment methods, and a step-by-step approval timeline.
 * 4. PRINT STYLES: Includes CSS to ensure the report is formatted correctly for printing.
 ****************************************************************/

require_once __DIR__ . '/includes/session_check.php';
require_once __DIR__ . '/includes/special_access_helper.php';

// Ensure UTF-8 encoding for database connection
mysqli_set_charset($conDB, "utf8mb4");

// Restrict access: Employees cannot view this detailed report page,
// unless explicitly granted via app_settings -> Special Access.
if (
    isset($isEmployee) && $isEmployee === true
    && !user_has_special_access($conDB, $empid ?? '', 'access_loan_report_details', $user_role ?? '', $user_type ?? '', $is_system_admin ?? false)
) {
    header("Location: ./profile.php");
    exit();
}

$query = mysqli_query($conDB, "SELECT * FROM `admin_login` WHERE `id_iqama`='" . $username . "'");
if (mysqli_num_rows($query) == 1) {
    include("./includes/avatar_select.php");
    // Fetch request identifiers
    $loan_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    $emp_id  = isset($_GET['emp_id']) ? $_GET['emp_id'] : '';

    if ($loan_id === 0 || empty($emp_id)) {
        die(__('invalid_request_parameters'));
    }

    // Fetch main loan and employee data
    $sql = "SELECT 
                l.*, 
                e.name as employee_name,
                e.avatar,
                e.joining_date,
                e.dept AS dept_id,
                e.bank_name,
                e.iban,
                bl.name as bank_name_en,
                bl.bank_name_ar,
                d.dep_nme AS `deptname`,
                d.dep_nme_ar AS `deptname_ar`,
                s.section_name,
                c.name AS `country_name`,
                rejected_by_emp.name as rejected_by_name,
                ela.notes as rejection_notes,
                ela.status as rejection_action,
                ela.created_at as rejection_timestamp,
                approver_emp.name as rejection_approver_name
            FROM emp_loan l
            JOIN employees e ON l.emp_id = e.emp_id
            LEFT JOIN bank_list bl ON e.bank_name = bl.bnk_id
            LEFT JOIN department d ON e.dept = d.id
            LEFT JOIN section s ON e.sectin_nme = s.id
            LEFT JOIN countries c ON e.country = c.id
            LEFT JOIN employees rejected_by_emp ON l.rejected_by = rejected_by_emp.emp_id
            LEFT JOIN emp_loan_approvals ela ON ela.loan_id = l.id AND ela.status = 'rejected'
            LEFT JOIN employees approver_emp ON ela.approver_id = approver_emp.emp_id
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

    // Department access scoping: allow HR/System Admin or approvers
    $canSeeAll = ($is_system_admin ?? false) || ($isHR ?? false);
    if (!$canSeeAll) {
        $userDeptId = $user_dept ?? null;
        $sameDept = ($userDeptId !== null && isset($loan_details['dept_id'])) ? ((int)$loan_details['dept_id'] === (int)$userDeptId) : false;
        $inChain = false;
        if (!$sameDept && !empty($loan_details['inv_no'])) {
            if ($chk = $conDB->prepare("SELECT 1 FROM request_approvers ra JOIN approval_request_types t ON t.id = ra.request_type_id AND t.type_name = 'loan_request' WHERE ra.request_inv_no = ? AND ra.approver_id = ? LIMIT 1")) {
                $inv = $loan_details['inv_no'];
                $chk->bind_param('si', $inv, $empid);
                if ($chk->execute()) {
                    $resChk = $chk->get_result();
                    $inChain = ($resChk && $resChk->num_rows > 0);
                }
                $chk->close();
            }
        }
        if (!$sameDept && !$inChain) {
            http_response_code(403);
            die('<div style="padding:16px;margin:16px;border:1px solid #f5c2c7;background:#f8d7da;color:#842029;border-radius:8px;">Access denied: You are not authorized to view this loan report.</div>');
        }
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

    // Fetch Approval Chain from request_approvers table and merge with emp_loan_approvals notes
    $approval_chain = [];
    if (!empty($loan_details['inv_no'])) {
        $chain_sql = "SELECT ra.*,
                      COALESCE(e.name, al.fullname, al.username) as approver_name,
                      al.user_type as approver_role,
                      d2.dep_nme AS approver_dept_name
                      FROM request_approvers ra
                      LEFT JOIN employees e ON ra.approver_id = e.emp_id
                      LEFT JOIN admin_login al ON ra.approver_id = al.id_iqama
                      LEFT JOIN department d2 ON e.dept = d2.id
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

    // Status text mapping (display-friendly)
    $all_loan_statuses = [
        'pending'                => __('pending'),
        'dept_manager_pending'   => __('pending_department_manager'),
        'hr_manager_pending'     => __('pending_hr_manager'),
        'finance_manager_pending'=> __('pending_finance_manager'),
        'gm_pending'             => __('pending_gm'),
        'finance_assistant_pending' => __('pending_final_processing'),
        'approved'               => __('approved_and_processed'),
        'paid'                   => __('paid_and_closed'),
        'rejected'               => __('rejected')
    ];
    $raw_status = strtolower((string)($loan_details['status'] ?? ''));
    if ($raw_status === '') {
        $status_text = __('unknown_status');
    } else {
        $status_text = $all_loan_statuses[$raw_status] ?? ucwords(str_replace('_', ' ', $raw_status));
    }

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
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
        <link href="assets/css/bootstrap.min.css" rel="stylesheet" type="text/css" />
        <link href="assets/css/icons.css" rel="stylesheet" type="text/css" />
        <link href="assets/css/metismenu.min.css" rel="stylesheet" type="text/css" />
        <link href="assets/css/style.css" rel="stylesheet" type="text/css" />
        <link href="assets/css/style_dark.css" rel="stylesheet" type="text/css" />
        <?php if ($is_rtl): ?>
            <link href="assets/css/style_rtl.css" rel="stylesheet" type="text/css" />
        <?php endif; ?>
        <script src="assets/js/modernizr.min.js"></script>
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
            .detail-item .value.font-monospace { font-family: 'Courier New', monospace; letter-spacing: 0.5px; font-size: 0.85rem; }

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

            .approval-flat-list { padding: 0; }
            .approval-flat-item { display: flex; flex-wrap: wrap; align-items: center; gap: 8px; padding: 8px 0; }
            .approval-flat-item i { width: 16px; text-align: center; font-size: 1rem; }
            .approval-flat-item strong { font-size: 0.95rem; }
            .approval-flat-note { flex-basis: 100%; margin-left: 24px; font-style: italic; color: var(--danger-color); font-size: 0.85rem; }
            [dir="rtl"] .approval-flat-note { margin-left: 0; margin-right: 24px; }
            
            .notes-section { background-color: #fff9e6; border-left: 4px solid var(--warning-color); padding: 1rem; border-radius: 4px; font-size: 0.85rem; }
            
            [dir="rtl"] .notes-section { border-left: none; border-right: 4px solid var(--warning-color); }
            
            .toggle-approval-btn { margin: 2rem 0 1rem 0; }
            #approvalStatusContainer { display: flex; transition: all 0.3s ease-in-out; }
            
            .report-footer { padding: 1rem 1.5rem; border-top: 1px solid var(--border-color); margin-top: 1.5rem; }
            .signature-area { display: grid; grid-template-columns: repeat(3, 1fr); gap: 1.5rem; text-align: center; margin-top: 2.5rem; }
            .signature-box { border-top: 1px solid var(--muted-color); padding-top: 0.5rem; }
            .signature-box p { margin: 0; color: var(--muted-color); font-size: 0.8rem; }

            .report-footer { padding: 1rem 1.5rem; border-top: 1px solid var(--border-color); margin-top: 1.5rem; text-align: center; color: var(--muted-color); font-size: 0.8rem; }

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
                            <a href="javascript:void(0);" onclick="window.print()" class="btn btn-primary waves-effect waves-light"><i class="fa fa-print mr-1"></i> <?= __('print_report_button') ?></a>
                        </div>
                        <div class="report-wrapper">
                            <div class="report-header">
                                <div class="logo-container"><img src="<?=get_setting($conDB, 'logo')?>" alt="Company Logo"></div>
                                <div class="report-meta">
                                    <h2 class="report-title"><?= __('loan_request_report_header') ?></h2>
                                    <p class="report-subtitle"><?= __('loan_id_label') ?>: #<?=htmlspecialchars($loan_details['id']); ?></p>
                                </div>
                            </div>
                            <div class="report-body">
                                <div class="employee-banner">
                                    <img src="<?= getAvatarImagePath($loan_details['avatar'] ?? '', $loan_details['sex'] ?? 1); ?>" alt="Employee Avatar" class="avatar">
                                    <div class="info">
                                        <h4><?=getDisplayName($loan_details['employee_name']); ?></h4>
                                        <p><?= __('employee_id_label') ?>: <?=htmlspecialchars($loan_details['emp_id']); ?> | <?=($is_rtl ?? false ? $loan_details['deptname_ar'] : $loan_details['deptname']) ; ?><?= getDisplayName(!empty($loan_details['section_name']) ? ' / ' . htmlspecialchars($loan_details['section_name']) : '') ?></p>
                                    </div>
                                </div>
                                <div class="report-section">
                                    <h5 class="section-title"><i class="fa fa-money-check-alt"></i><?= __('loan_details_header') ?></h5>
                                    <div class="grid-details">
                                        <div class="detail-item"><span class="label"><?= __('invoice_no') ?></span> <span class="value"><code><?= htmlspecialchars($loan_details['inv_no'] ?? 'N/A'); ?></code></span></div>
                                        <div class="detail-item"><span class="label"><?= __('loan_type_label') ?></span> <span class="value"><?= getDisplayName(ucwords(str_replace('_', ' ', $loan_details['loan_type'] ?? 'N/A'))); ?></span></div>
                                        <div class="detail-item"><span class="label"><?= __('applied_loan_amount') ?></span> <span class="value"><?= number_format($loan_details['loan_amount'], 2); ?> <?= __('sar_currency') ?></span></div>
                                        <?php if (!empty($loan_details['approved_amount']) && $loan_details['approved_amount'] != $loan_details['loan_amount']): ?>
                                        <div class="detail-item"><span class="label"><?= __('approved_loan_amount') ?></span> <span class="value highlight" style="color: #28a745;"><?= number_format($loan_details['approved_amount'], 2); ?> <?= __('sar_currency') ?> <small class="text-muted">(<?= __('modified_by') ?>: <?= htmlspecialchars($loan_details['approved_by_emp_id'] ?? 'N/A') ?>)</small></span></div>
                                        <?php endif; ?>
                                        <div class="detail-item"><span class="label"><?= __('loan_installments_label') ?></span> <span class="value"><?= htmlspecialchars($loan_details['installments'] ?? '1'); ?> <?= __('months') ?></span></div>
                                        <div class="detail-item"><span class="label"><?= __('total_payable_label') ?></span> <span class="value highlight"><?= number_format($loan_details['total_payable'], 2); ?> <?= __('sar_currency') ?></span></div>
                                        <div class="detail-item"><span class="label"><?= __('monthly_deduction_label') ?></span> <span class="value"><?= number_format($loan_details['monthly_deduction'], 2); ?> <?= __('sar_currency') ?></span></div>
                                        <div class="detail-item"><span class="label"><?= __('start_date_label') ?></span> <span class="value"><?php $loanStartDate = $loan_details['start_date'] ?? ''; echo (!empty($loanStartDate) && $loanStartDate !== '0000-00-00') ? format_safe_date($loanStartDate, 'd M Y') : '-'; ?></span></div>
                                        <div class="detail-item"><span class="label"><?= __('end_date_label') ?></span> <span class="value"><?php $loanEndDate = $loan_details['end_date'] ?? ''; echo (!empty($loanEndDate) && $loanEndDate !== '0000-00-00') ? format_safe_date($loanEndDate, 'd M Y') : '-'; ?></span></div>
                                        <div class="detail-item"><span class="label"><?= __('current_status_label') ?></span> <span class="value highlight text-primary"><?= $status_text ?></span></div>
                                    </div>
                                </div>
                                            <div class="report-section">
                                                <h5 class="section-title"><i class="fa fa-history"></i> <?= __('payment_history_header') ?></h5>
                                                <?php if (empty($payment_history)): ?>
                                                    <div class="alert alert-info">
                                                        <i class="fa fa-info-circle"></i> <?= __('no_payments_recorded_yet') ?>
                                                    </div>
                                                <?php else: 
                                                    $total_paid = 0;
                                                    foreach ($payment_history as $payment) {
                                                        $total_paid += $payment['amount'];
                                                    }
                                                    $remaining_balance = $loan_details['total_payable'] - $total_paid;
                                                ?>
                                                    <div class="payment-summary">
                                                        <ul>
                                                            <li>
                                                                <span class="label"><?= __('total_paid') ?></span>
                                                                <span class="value text-success">+<?= number_format($total_paid, 2); ?> SAR</span>
                                                            </li>
                                                            <li>
                                                                <span class="label"><?= __('remaining_balance') ?></span>
                                                                <span class="value text-<?= $remaining_balance > 0 ? 'danger' : 'success' ?>"><?= number_format($remaining_balance, 2); ?> SAR</span>
                                                            </li>
                                                            <li class="total-payable">
                                                                <span class="label"><?= __('total_payable_label') ?></span>
                                                                <span class="value"><?= number_format($loan_details['total_payable'], 2); ?> SAR</span>
                                                            </li>
                                                        </ul>
                                                    </div>
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
                                                                    <td><?= format_safe_date($payment['payment_date'] ?? null, 'd M Y'); ?></td>
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
                                                                            <i class="fa fa-comment"></i> <strong><?= __('note_label') ?>:</strong> <?= htmlspecialchars($payment['note'], ENT_QUOTES, 'UTF-8'); ?>
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
                                            
                                            <?php 
                                                // Show bank payment information only if:
                                                // 1. Loan is approved AND
                                                // 2. Current user is finance officer OR system admin
                                                $show_bank_info = false;
                                                if ($loan_details['status'] === 'approved' || $loan_details['status'] === 'paid') {
                                                    $show_bank_info = ($isFinance_Officer ?? false) || ($is_system_admin ?? false) || ($isHR_Payroll ?? false);
                                                }
                                            ?>

                                            <?php if ($show_bank_info): ?>
                                            <div class="report-section">
                                                <h5 class="section-title"><i class="fa fa-university"></i><?= __('bank_payment_information') ?? 'Bank Payment Information' ?></h5>
                                                <div class="grid-details">
                                                    <div class="detail-item">
                                                        <span class="label"><?= __('bank_name') ?></span>
                                                        <span class="value">
                                                            <?php 
                                                                $bank_display = '';
                                                                if (!empty($loan_details['bank_name_en']) || !empty($loan_details['bank_name_ar'])) {
                                                                    // Prefer bank_list data
                                                                    $bank_display = ($is_rtl ?? false) ? $loan_details['bank_name_ar'] : $loan_details['bank_name_en'];
                                                                } elseif (!empty($loan_details['bank_name'])) {
                                                                    // Fallback to employee bank_name field
                                                                    $bank_display = $loan_details['bank_name'];
                                                                }
                                                                echo !empty($bank_display) ? htmlspecialchars($bank_display) : '<em class="text-muted">' . __('not_provided') . '</em>';
                                                            ?>
                                                        </span>
                                                    </div>
                                                    <div class="detail-item">
                                                        <span class="label"><?= __('iban') ?? 'IBAN' ?></span>
                                                        <span class="value font-monospace">
                                                            <?= !empty($loan_details['iban']) ? htmlspecialchars($loan_details['iban']) : '<em class="text-muted">' . __('not_provided') . '</em>' ?>
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>
                                            <?php endif; ?>
                                            
                                            <div class="text-center mb-3 no-print" style="margin-top: 2rem;">
                                                <button type="button" class="btn btn-outline-primary waves-effect waves-light" id="toggleApprovalBtn" onclick="toggleApprovalStatus()">
                                                    <i class="fa fa-eye mr-2"></i><?= __('view_approval_status') ?? 'View Approval Status' ?>
                                                </button>
                                            </div>

                                            <div class="row" id="approvalStatusContainer" style="display: none;">
                                                <div class="col-md-12">
                                                    <div class="report-section">
                                                        <h5 class="section-title"><i class="fa fa-tasks"></i> <?= __('approval_status') ?></h5>
                                                        <div class="approval-flat-list">
                                                    <?php if (empty($approval_chain)): ?>
                                                        <div class="alert alert-info"><?= __('add') ?></div>
                                                    <?php else:
                                                        // Check if request was rejected
                                                        $is_loan_rejected = isset($loan_details['status']) && $loan_details['status'] === 'rejected';
                                                    ?>
                                                        <?php foreach ($approval_chain as $level):
                                                            // If request is rejected, skip pending/awaiting approvers
                                                            if ($is_loan_rejected && in_array($level['status'], ['pending', 'awaiting'])) {
                                                                continue;
                                                            }

                                                            $level_status = $level['status'] ?? 'pending';
                                                            $status_color = 'text-muted';
                                                            if ($level_status === 'approved') $status_color = 'text-success';
                                                            elseif ($level_status === 'rejected') $status_color = 'text-danger';
                                                            elseif ($level_status === 'pending') $status_color = 'text-warning';

                                                            $role_icon = 'fa-user';
                                                            if (!empty($level['approver_role']) && stripos($level['approver_role'], 'hr') !== false) {
                                                                $role_icon = 'fa-user-shield';
                                                            } elseif (!empty($level['approver_role']) && (stripos($level['approver_role'], 'manager') !== false || stripos($level['approver_role'], 'administrator') !== false || stripos($level['approver_role'], 'gm') !== false)) {
                                                                $role_icon = 'fa-user-tie';
                                                            }

                                                            $level_scope = !empty($level['approver_dept_name']) ? getDisplayName($level['approver_dept_name']) : ucwords(str_replace('_', ' ', (string)($level['approver_role'] ?? '')));
                                                        ?>
                                                        <div class="approval-flat-item">
                                                            <i class="fa <?= $role_icon ?> <?= $status_color ?>"></i>
                                                            <strong><?= getDisplayName($level['approver_name'] ?? 'Pending Assignment'); ?></strong>
                                                            <small class="text-muted">(<?= __('level') ?> <?= (int)$level['approval_level'] ?><?= $level_scope !== '' ? ': ' . htmlspecialchars((string)$level_scope) : '' ?>)</small>
                                                            <?php if ($level_status === 'rejected' && !empty($level['note'])): ?>
                                                                <div class="approval-flat-note"><?= getDisplayName($level['note']); ?></div>
                                                            <?php endif; ?>
                                                        </div>
                                                        <?php endforeach; ?>
                                                    <?php endif; ?>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <script>
                                            function toggleApprovalStatus() {
                                                const container = document.getElementById('approvalStatusContainer');
                                                const btn = document.getElementById('toggleApprovalBtn');
                                                
                                                if (container.style.display === 'none' || container.style.display === '') {
                                                    container.style.display = 'flex';
                                                    btn.innerHTML = '<i class="fa fa-eye-slash mr-2"></i><?= __('hide_approval_status') ?? 'Hide Approval Status' ?>';
                                                } else {
                                                    container.style.display = 'none';
                                                    btn.innerHTML = '<i class="fa fa-eye mr-2"></i><?= __('view_approval_status') ?? 'View Approval Status' ?>';
                                                }
                                            }
                                            </script>

                                            <?php if (!empty($loan_details['payment_proof_file']) || !empty($loan_details['final_approved_amount'])): ?>
                                            <div class="report-section">
                                                <h5 class="section-title"><i class="fa fa-file-invoice-dollar"></i> <?= __('Payment Proof') ?></h5>
                                                <div class="grid-details">
                                                    <?php if (!empty($loan_details['final_approved_amount'])): ?>
                                                    <div class="detail-item">
                                                        <span class="label text-primary"><?= __('Final Approved Amount') ?>:</span>
                                                        <span class="value highlight text-success"><?= number_format($loan_details['final_approved_amount'], 2); ?> SAR</span>
                                                    </div>
                                                    <?php endif; ?>
                                                    <?php if (!empty($loan_details['payment_proof_file'])): ?>
                                                    <div class="detail-item">
                                                        <span class="label text-primary"><?= __('Payment Proof Attachment') ?>:</span>
                                                        <span class="value"><a href="./assets/loan_payment_proofs/<?= htmlspecialchars($loan_details['payment_proof_file']); ?>" target="_blank" class="btn btn-info btn-sm mt-2"><i class="fa fa-download"></i> <?= __('View/Download Payment Proof') ?></a></span>
                                                    </div>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                            <?php endif; ?>
                                            <div class="report-section">
                                                <h5 class="section-title"><i class="fa fa-pencil"></i> <?= __('employee_signatures_section') ?></h5>
                                                <div class="signature-area">
                                                    <div class="signature-box"><p><?= __('employee_signature') ?></p></div>
                                                    <div class="signature-box"><p><?= __('hr_manager_signature') ?></p></div>
                                                    <div class="signature-box"><p><?= __('general_manager_signature') ?></p></div>
                                                </div>
                                            </div>
                                        </div>
                                        <!-- Report Footer -->
                                        <div class="report-footer">
                                            <p>Automatically generated on <?= date('Y-m-d H:i:s') ?> by <?= $site_title ?> - <?= __('loan_report_title') ?></p>
                                        </div>
                                </div>
                            </div> <!-- /.report-wrapper -->
                        </div> <!-- /.container-fluid -->
                    </div> <!-- /.content -->
                    <footer class="footer no-print"><?= $site_footer ?></footer>
                </div> <!-- /.content-page -->
            </div> <!-- /#wrapper -->
        <script src="assets/js/jquery.min.js"></script>
        <script src="assets/js/bootstrap.bundle.min.js"></script>
        <script src="assets/js/metisMenu.min.js"></script>
        <script src="assets/js/waves.js"></script>
        <script src="assets/js/jquery.slimscroll.js"></script>
        <script src="assets/js/jquery.core.js"></script>
        <script src="assets/js/jquery.app.js?t=<?= time() ?>"></script>
    </body>

    </html>
<?php
    $conDB->close();
}
?>

