<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/session_check.php';

// Input
$inv_no = isset($_GET['inv_no']) ? trim($_GET['inv_no']) : '';
if ($inv_no === '') {
    die('<div style="padding:16px;margin:16px;border:1px solid #f5c2c7;background:#f8d7da;color:#842029;border-radius:8px;">ERROR: Loan invoice number not provided.</div>');
}

// Loan details
$loan = null;
$sql = "SELECT l.id, l.inv_no, l.emp_id, l.loan_type, l.loan_amount, l.installments, l.interest_rate, l.total_payable, l.monthly_deduction, l.status,
               l.payment_proof_file, l.disbursement_receipt_id, l.disbursement_attachment,
               l.rejection_reason, l.rejection_date, l.rejected_by,
               e.name AS employee_name, 
               e.avatar, e.dept, 
               d.dep_nme AS department_name,
               d.dep_nme_ar AS department_name_ar,
               rejected_by_emp.name as rejected_by_name,
               ra.note as rejection_notes,
               ra.action_date as rejection_timestamp,
               COALESCE(rejected_emp.name, al.fullname, al.username) as rejection_approver_name
        FROM emp_loan l
        JOIN employees e ON l.emp_id = e.emp_id
        LEFT JOIN department d ON e.dept = d.id
        LEFT JOIN employees rejected_by_emp ON l.rejected_by = rejected_by_emp.emp_id
        LEFT JOIN request_approvers ra ON ra.request_inv_no = l.inv_no AND ra.request_type_id = 2 AND ra.status = 'rejected'
        LEFT JOIN employees rejected_emp ON ra.approver_id = rejected_emp.emp_id
        LEFT JOIN admin_login al ON ra.approver_id = al.id_iqama
        WHERE l.inv_no = ?
        LIMIT 1";
$stmt = $conDB->prepare($sql);
$stmt->bind_param('s', $inv_no);
$stmt->execute();
$res = $stmt->get_result();
if ($res && $res->num_rows === 1) {
    $loan = $res->fetch_assoc();
}
$stmt->close();
if (!$loan) {
    die('<div style="padding:16px;margin:16px;border:1px solid #f5c2c7;background:#f8d7da;color:#842029;border-radius:8px;">ERROR: Loan request not found.</div>');
}
// Enforce department scoping: Only HR and System Admin can view other departments,
// otherwise allow if the user is part of the approval chain for this loan
$canSeeAll = ($is_system_admin ?? false) || ($isHR ?? false);
if (!$canSeeAll) {
    $userDeptId = $user_dept ?? null;
    $sameDept = ($userDeptId !== null) ? ((int)$loan['dept'] === (int)$userDeptId) : false;
    $inChain = false;
    if (!$sameDept) {
        if ($chk = $conDB->prepare("SELECT 1 FROM request_approvers ra JOIN approval_request_types t ON t.id = ra.request_type_id AND t.type_name = 'loan_request' WHERE ra.request_inv_no = ? AND ra.approver_id = ? LIMIT 1")) {
            $chk->bind_param('si', $inv_no, $empid);
            if ($chk->execute()) {
                $resChk = $chk->get_result();
                $inChain = ($resChk && $resChk->num_rows > 0);
            }
            $chk->close();
        }
    }
    if (!$sameDept && !$inChain) {
        http_response_code(403);
        die('<div style="padding:16px;margin:16px;border:1px solid #f5c2c7;background:#f8d7da;color:#842029;border-radius:8px;">Access denied: You are not authorized to view this request.</div>');
    }
}
// Payments
$payments = [];
$total_paid = 0.0;
$stmt = $conDB->prepare("SELECT payment_date, amount, payment_method, receipt_id, attachment FROM emp_loan_payments WHERE loan_id = ? ORDER BY payment_date DESC");
$stmt->bind_param('i', $loan['id']);
$stmt->execute();
$pr = $stmt->get_result();
while ($row = $pr->fetch_assoc()) {
    $payments[] = $row;
    $total_paid += (float)$row['amount'];
}
$stmt->close();
$remaining_amount = max(0, (float)$loan['total_payable'] - $total_paid);
$payment_percentage = ($loan['total_payable'] > 0) ? ($total_paid / (float)$loan['total_payable']) * 100 : 0;

// Status history
$history = [];
$stmt = $conDB->prepare("SELECT status, note, emp_name, created_at FROM smt_request_status WHERE inv_no = ? ORDER BY created_at ASC");
$stmt->bind_param('s', $inv_no);
$stmt->execute();
$hr = $stmt->get_result();
while ($row = $hr->fetch_assoc()) {
    $history[] = $row;
}
$stmt->close();

// Approval chain (request_type_id 2 assumed for loans)
$chain = [];
$stmt = $conDB->prepare("SELECT ra.approval_level, ra.status, ra.action_date, ra.note,
                                COALESCE(e.name, al.fullname, al.username) AS approver_name,
                                al.user_type
                         FROM request_approvers ra
                         LEFT JOIN employees e ON ra.approver_id = e.emp_id
                         LEFT JOIN admin_login al ON ra.approver_id = al.id_iqama
                         WHERE ra.request_inv_no = ? AND ra.request_type_id = 2
                         ORDER BY ra.approval_level ASC");
$stmt->bind_param('s', $inv_no);
$stmt->execute();
$cr = $stmt->get_result();
while ($row = $cr->fetch_assoc()) {
    $chain[] = $row;
}
$stmt->close();

// Helpers
$status_class = 'secondary';
$status_icon = 'fa-circle';
if ($loan['status'] === 'approved') { $status_class = 'success'; $status_icon = 'fa-check-circle'; }
elseif ($loan['status'] === 'rejected') { $status_class = 'danger'; $status_icon = 'fa-times-circle'; }
elseif (strpos($loan['status'], 'pending') !== false) { $status_class = 'warning'; $status_icon = 'fa-clock'; }
elseif ($loan['status'] === 'paid') { $status_class = 'info'; $status_icon = 'fa-check-double'; }

// Use getAvatarImagePath function for proper gender-based default avatars
$avatar_path = getAvatarImagePath($loan['avatar'] ?? '', $loan['sex'] ?? 1);

?>
<!doctype html>
<html lang="<?= isset($current_lang) ? $current_lang : 'en' ?>" <?= (isset($is_rtl) && $is_rtl) ? 'dir="rtl"' : '' ?>>
<head>
    <meta charset="utf-8" />
    <title><?= isset($site_title) ? $site_title : 'Al-Mutlak' ?> - Loan Approval History</title>
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <link rel="shortcut icon" href="<?= function_exists('get_setting') ? (get_setting($conDB, 'favicon') ?? 'assets/images/favicon.ico') : 'assets/images/favicon.ico' ?>">
    <link href="assets/css/bootstrap.min.css" rel="stylesheet" type="text/css" />
    <link href="assets/css/metismenu.min.css" rel="stylesheet" type="text/css" />
    <link href="assets/css/style.css" rel="stylesheet" type="text/css" />
    <link href="assets/css/icons.css" rel="stylesheet" type="text/css" />
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body { background-color: #f8f9fa; }
        .page-header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: #fff; padding: 30px 0; margin-bottom: 30px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); text-align: <?= $is_rtl ?? false ? 'right' : 'left' ?>; }
        .page-header h3 { margin: 0; font-weight: 600; }
        .info-card { background: #fff; border-radius: 10px; padding: 25px; margin-bottom: 20px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); text-align: <?= $is_rtl ?? false ? 'right' : 'left' ?>; }
        .info-card h5 { color: #667eea; margin-bottom: 20px; font-weight: 600; border-bottom: 2px solid #f0f0f0; padding-bottom: 10px; }
        .employee-header { display: flex; align-items: center; margin-bottom: 20px; text-align: <?= $is_rtl ?? false ? 'right' : 'left' ?>; }
        .employee-avatar { width: 80px; height: 80px; border-radius: 50%; object-fit: cover; <?= $is_rtl ?? false ? 'margin-left' : 'margin-right' ?>: 20px; border: 3px solid #667eea; }
        .info-row { display: flex; padding: 12px 0; border-bottom: 1px solid #f0f0f0; }
        .info-row:last-child { border-bottom: none; }
        .info-label { font-weight: 600; color: #666; width: 40%; display: flex; align-items: center; }
        .info-label i { margin-right: 8px; color: #667eea; width: 20px; }
        .info-value { color: #333; width: 60%; }
        .amount-display { font-size: 24px; font-weight: 700; color: #667eea; }
        .progress-bar-custom { height: 8px; border-radius: 10px; background: #e9ecef; overflow: hidden; margin-top: 10px; }
        .progress-bar-custom .progress-fill { height: 100%; background: linear-gradient(90deg, #28a745, #20c997); border-radius: 10px; transition: width 1s ease; }
        .payment-item { background: #f8f9fa; padding: 15px; border-radius: 8px; margin-bottom: 10px; border-left: 3px solid #28a745; }
        .chain-table thead { background: #f8f9fa; }
        .chain-table td, .chain-table th { padding: 15px; vertical-align: middle; }
        .level-badge { display: inline-block; width: 40px; height: 40px; line-height: 40px; text-align: center; border-radius: 50%; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: #fff; font-weight: 700; }
        .attachment-link { display: inline-flex; align-items: center; gap: 8px; padding: 8px 16px; background: #f0f4ff; border-radius: 6px; color: #667eea; text-decoration: none; font-weight: 500; }
        .attachment-link:hover { background: #667eea; color: #fff; text-decoration: none; }
        
        /* Modern Timeline Design */
        .timeline { position: relative; padding: 10px 0; }
        .timeline:before { content: ''; position: absolute; left: 19px; top: 0; bottom: 0; width: 2px; background: #eaedf1; }
        .timeline-item { position: relative; padding-left: 50px; margin-bottom: 20px; }
        .timeline-item:last-child { margin-bottom: 0; }
        .timeline-marker { 
            position: absolute; left: 8px; top: 4px; width: 24px; height: 24px; 
            border-radius: 50%; background: #fff; border: 2px solid #adb5bd; 
            z-index: 1; display:flex; align-items:center; justify-content:center; 
            font-size: 10px; color: #adb5bd; transition: all 0.3s ease;
        }
        .timeline-item.approved .timeline-marker, .timeline-item.completed .timeline-marker { border-color: #28a745; color: #28a745; background-color: #f6ffed; }
        .timeline-item.rejected .timeline-marker { border-color: #dc3545; color: #dc3545; background-color: #fff1f0; }
        .timeline-item.pending .timeline-marker { border-color: #ffc107; color: #ffc107; background-color: #fffbe6; }
        
        .timeline-content { 
            background: #fff; padding: 15px; border-radius: 8px; 
            border: 1px solid #e3e8ee; box-shadow: 0 1px 3px rgba(0,0,0,0.05); 
            position: relative; transition: all 0.2s ease-in-out;
            text-align: <?= $is_rtl ?? false ? 'right' : 'left' ?>;
        }
        .timeline-content:hover { border-color: #667eea; box-shadow: 0 4px 12px rgba(102, 126, 234, 0.1); }
        .status-badge { padding: 4px 10px; border-radius: 4px; font-size: 10px; font-weight: 700; text-transform: uppercase; }

        /* RTL Adjustments */
        [dir="rtl"] .timeline:before { left: auto; right: 19px; }
        [dir="rtl"] .timeline-item { padding-left: 0; padding-right: 50px; }
        [dir="rtl"] .timeline-marker { left: auto; right: 8px; }
        .btn-back { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border: none; color: #fff; padding: 12px 30px; border-radius: 25px; font-weight: 600; }
    </style>
</head>
<body class="enlarged">
    <div class="page-header">
        <div class="container">
            <h3><i class="fas fa-history"></i> <?= __('loan_approval_history')?></h3>
            <p class="mb-0"><?= __('complete_approval_timeline') ?></p>
        </div>
    </div>

    <div class="container" style="margin-bottom:30px;">
        <!-- Employee Info -->
        <div class="info-card">
            <div class="employee-header">
                <img src="<?= htmlspecialchars($avatar_path) ?>" class="employee-avatar" alt="avatar" />
                <div>
                    <h4 style="margin:0;"><?= getDisplayName($loan['employee_name']) ?></h4>
                    <div class="text-muted" style="margin-top:6px;">
                        <strong><?= __('emp_id')?>:</strong> <?= htmlspecialchars($loan['emp_id']) ?>
                        &nbsp; | &nbsp;
                        <strong><?= __('department') ?>:</strong> <?= ($is_rtl ?? false) ? htmlspecialchars($loan['department_name_ar'] ?? 'N/A') : htmlspecialchars($loan['department_name'] ?? 'N/A') ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-6">
                <div class="info-card">
                    <h5><i class="fas fa-file-invoice-dollar"></i> <?= __('loan_details') ?></h5>
                    <div class="info-row"><div class="info-label"><i class="fas fa-barcode"></i> <?= __('invoice_no') ?>:</div><div class="info-value"><code><?= htmlspecialchars($loan['inv_no']) ?></code></div></div>
                    <div class="info-row"><div class="info-label"><i class="fas fa-tag"></i> <?= __('loan_type') ?>:</div><div class="info-value"><strong><?= getDisplayName(ucwords(str_replace('_', ' ', $loan['loan_type']))) ?></strong></div></div>
                    <div class="info-row"><div class="info-label"><i class="fas fa-money-bill-wave"></i> <?= __('loan_amount') ?>:</div><div class="info-value"><span class="amount-display">SAR <?= number_format($loan['loan_amount'], 2) ?></span></div></div>
                    <div class="info-row"><div class="info-label"><i class="fas fa-calendar-alt"></i> <?= __('installments') ?>:</div><div class="info-value"><strong><?= (int)$loan['installments'] ?></strong> <?= __('months') ?></div></div>
                    <div class="info-row"><div class="info-label"><i class="fas fa-hand-holding-usd"></i> <?= __('monthly_deduction') ?>:</div><div class="info-value"><strong>SAR <?= number_format($loan['monthly_deduction'], 2) ?></strong></div></div>
                    <?php if (!empty($loan['interest_rate'])): ?>
                    <div class="info-row"><div class="info-label"><i class="fas fa-percent"></i> <?= __('interest_rate') ?>:</div><div class="info-value"><strong><?= number_format($loan['interest_rate'], 2) ?>%</strong></div></div>
                    <?php endif; ?>
                    <div class="info-row"><div class="info-label"><i class="fas fa-calculator"></i> <?= __('total_payable') ?>:</div><div class="info-value"><strong style="color:#dc3545;">SAR <?= number_format($loan['total_payable'], 2) ?></strong></div></div>
                    <div class="info-row"><div class="info-label"><i class="fas fa-info-circle"></i> <?= __('status') ?>:</div><div class="info-value"><span class="badge badge-<?= $status_class ?>" style="font-size:14px;padding:8px 16px;"><i class="fas <?= $status_icon ?>"></i> <?= getDisplayName(ucwords(str_replace('_', ' ', $loan['status']))) ?></span></div></div>
                    <?php if (!empty($loan['payment_proof_file'])): ?>
                    <div class="info-row"><div class="info-label"><i class="fas fa-file-pdf"></i> <?= __('payment_proof') ?>:</div><div class="info-value"><a href="<?= htmlspecialchars($loan['payment_proof_file']) ?>" target="_blank" class="attachment-link"><i class="fas fa-download"></i> <?= __('view_document') ?></a></div></div>
                    <?php endif; ?>
                </div>

                <?php if ($loan['status'] === 'rejected'): ?>
                <div class="info-card">
                    <h5 style="color:#dc3545;"><i class="fas fa-ban"></i> <?= __('rejection_details') ?></h5>
                    <div style="background-color: #f8d7da; border-left: 4px solid #dc3545; padding: 15px; border-radius: 6px;">
                        <?php 
                        // Check for rejection note from request_approvers table
                        if (!empty($loan['rejection_notes'])):
                        ?>
                        <div class="info-row" style="background:transparent;border:none;padding:0 0 12px 0;">
                            <div class="info-label" style="width:auto;"><i class="fas fa-comment-slash"></i> <?= __('rejection_reason') ?>:</div>
                            <div class="info-value" style="width:auto;color:#721c24;font-weight:500;"><?= nl2br(htmlspecialchars(getDisplayName($loan['rejection_notes']))); ?></div>
                        </div>
                        <?php if (!empty($loan['rejection_approver_name'])): ?>
                        <div class="info-row" style="background:transparent;border:none;padding:0 0 12px 0;">
                            <div class="info-label" style="width:auto;"><i class="fas fa-user-slash"></i> <?= __('rejected_by') ?>:</div>
                            <div class="info-value" style="width:auto;color:#721c24;font-weight:500;"><?= htmlspecialchars(getDisplayName($loan['rejection_approver_name'])); ?></div>
                        </div>
                        <?php endif; ?>
                        <?php if (!empty($loan['rejection_timestamp'])): ?>
                        <div class="info-row" style="background:transparent;border:none;padding:0;">
                            <div class="info-label" style="width:auto;"><i class="fas fa-calendar-times"></i> <?= __('rejection_date') ?>:</div>
                            <div class="info-value" style="width:auto;color:#721c24;"><?= format_safe_date($loan['rejection_timestamp'] ?? null, 'd M Y, H:i:s'); ?></div>
                        </div>
                        <?php endif; ?>
                        <?php elseif (!empty($loan['rejection_reason'])): ?>
                        <div class="info-row" style="background:transparent;border:none;padding:0 0 12px 0;">
                            <div class="info-label" style="width:auto;"><i class="fas fa-comment-slash"></i> <?= __('rejection_reason') ?>:</div>
                            <div class="info-value" style="width:auto;color:#721c24;font-weight:500;"><?= nl2br(htmlspecialchars(getDisplayName($loan['rejection_reason']))); ?></div>
                        </div>
                        <?php if (!empty($loan['rejection_date'])): ?>
                        <div class="info-row" style="background:transparent;border:none;padding:0 0 12px 0;">
                            <div class="info-label" style="width:auto;"><i class="fas fa-calendar-times"></i> <?= __('rejection_date') ?>:</div>
                            <div class="info-value" style="width:auto;color:#721c24;"><?= format_safe_date($loan['rejection_date'] ?? null, 'd M Y, H:i:s'); ?></div>
                        </div>
                        <?php endif; ?>
                        <?php if (!empty($loan['rejected_by_name'])): ?>
                        <div class="info-row" style="background:transparent;border:none;padding:0;">
                            <div class="info-label" style="width:auto;"><i class="fas fa-user-slash"></i> <?= __('rejected_by') ?>:</div>
                            <div class="info-value" style="width:auto;color:#721c24;font-weight:500;"><?= htmlspecialchars(getDisplayName($loan['rejected_by_name'])); ?></div>
                        </div>
                        <?php endif; ?>
                        <?php else: ?>
                        <div class="info-row" style="background:transparent;border:none;padding:0;">
                            <div class="info-value" style="width:100%;color:#721c24;font-style:italic;"><i class="fas fa-info-circle"></i> <?= __('no_rejection_reason_provided', 'No rejection reason provided') ?></div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <div class="col-md-6">
                <?php if (in_array($loan['status'], ['approved','paid'])): ?>
                <div class="info-card">
                    <h5><i class="fas fa-chart-line"></i> <?= __('payment_progress') ?></h5>
                    <div class="info-row"><div class="info-label"><i class="fas fa-check-double"></i> <?= __('total_paid') ?>:</div><div class="info-value"><strong style="color:#28a745;">SAR <?= number_format($total_paid, 2) ?></strong></div></div>
                    <div class="info-row"><div class="info-label"><i class="fas fa-hourglass-half"></i> <?= __('remaining') ?>:</div><div class="info-value"><strong style="color:#dc3545;">SAR <?= number_format($remaining_amount, 2) ?></strong></div></div>
                    <div class="progress-bar-custom"><div class="progress-fill" style="width: <?= number_format($payment_percentage, 1) ?>%"></div></div>
                    <?php if (!empty($payments)): ?>
                    <div class="mt-3">
                        <strong><i class="fas fa-list"></i> <?= __('recent_payments') ?>:</strong>
                        <div class="mt-2">
                            <?php foreach (array_slice($payments, 0, 3) as $payment): ?>
                            <div class="payment-item">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <i class="fas fa-calendar"></i> <?= format_safe_date($payment['payment_date'] ?? null, 'd M Y') ?>
                                        <span class="badge badge-<?= ($payment['payment_method']==='manual') ? 'info' : 'primary' ?> ml-2"><?= ucfirst($payment['payment_method']) ?></span>
                                    </div>
                                    <strong>SAR <?= number_format($payment['amount'], 2) ?></strong>
                                </div>
                                <?php if (!empty($payment['attachment'])): ?>
                                <a class="text-muted small" target="_blank" href="<?= htmlspecialchars($payment['attachment']) ?>"><i class="fas fa-paperclip"></i> <?= __('view_receipt') ?></a>
                                <?php endif; ?>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>

                <div class="info-card">
                    <h5><i class="fas fa-users-cog"></i> <?= __('approval_chain') ?></h5>
                    <table class="table table-sm chain-table table-hover">
                        <thead><tr><th width="70" class="text-center"><?= __('level') ?></th><th><?= __('approver') ?></th><th><?= __('status') ?></th></tr></thead>
                        <tbody>
                        <?php if (empty($chain)): ?>
                            <tr><td colspan="3" class="text-center text-muted"><i class="fas fa-info-circle"></i> <?= __('no_approval_chain') ?></td></tr>
                        <?php else: 
                            // Check if request was rejected
                            $is_rejected = isset($loan['status']) && $loan['status'] === 'rejected';
                            
                            foreach ($chain as $link): 
                                // If request is rejected, skip pending/awaiting approvers
                                if ($is_rejected && in_array($link['status'], ['pending', 'awaiting'])) {
                                    continue;
                                }
                                
                                $cClass = 'secondary'; $cIcon = 'fa-circle';
                                if ($link['status']==='approved') { $cClass='success'; $cIcon='fa-check-circle'; }
                                elseif ($link['status']==='rejected') { $cClass='danger'; $cIcon='fa-times-circle'; }
                                elseif ($link['status']==='pending') { $cClass='warning'; $cIcon='fa-clock'; }
                        ?>
                            <tr>
                                <td class="text-center"><span class="level-badge"><?= (int)$link['approval_level'] ?></span></td>
                                <td><strong><?= getDisplayName(parseName($link['approver_name']) ?? 'N/A') ?></strong><br><small class="text-muted"><i class="fas fa-user-tag"></i> <?= getDisplayName($link['user_type'] ?? '') ?></small></td>
                                <td>
                                    <span class="badge badge-<?= $cClass ?>"><i class="fas <?= $cIcon ?>"></i> <?= getDisplayName(ucfirst($link['status'])) ?></span>
                                    <?php if (!empty($link['action_date'])): ?><br><small class="text-muted"><i class="far fa-calendar-alt"></i> <?= format_safe_date($link['action_date'], 'd M Y, H:i') ?></small><?php endif; ?>
                                    <?php if (!empty($link['note'])):
                                        $note_border_color = ($link['status'] === 'rejected') ? '#dc3545' : '#667eea';
                                        $note_label = ($link['status'] === 'rejected') ? __('Rejection Reason') : __('note');
                                        $note_label_style = ($link['status'] === 'rejected') ? 'color:#dc3545;' : '';
                                    ?>
                                    <br><small style="background:#f0f0f0;padding:6px 10px;border-radius:4px;display:block;margin-top:8px;border-left:3px solid <?= $note_border_color ?>;"><strong style="<?= $note_label_style ?>"><?= $note_label ?>:</strong> <?= nl2br(htmlspecialchars(getDisplayName($link['note']))); ?></small>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="info-card">
            <h5><i class="fas fa-history"></i> <?= __('status_history_timeline') ?></h5>
            <?php if (empty($history)): ?>
                <div class="alert alert-info"><i class="fas fa-info-circle"></i> <?= __('no_history') ?></div>
            <?php else: ?>
                <div class="timeline">
                    <?php foreach ($history as $item): 
                        $status_clean = strtolower($item['status']);
                        $item_class = 'pending'; $icon = 'fa-clock'; $badge = 'warning';
                        if (strpos($status_clean, 'approved') !== false || strpos($status_clean, 'completed') !== false) {
                            $item_class = 'approved'; $icon = 'fa-check'; $badge = 'success';
                        } elseif (strpos($status_clean, 'rejected') !== false) {
                            $item_class = 'rejected'; $icon = 'fa-times'; $badge = 'danger';
                        }
                    ?>
                    <div class="timeline-item <?= $item_class ?>">
                        <div class="timeline-marker" style="<?= ($is_rtl ?? false) ? 'right' : 'left' ?> : -11px !important;"><i class="fas <?= $icon ?>"></i></div>
                        <div class="timeline-content">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span class="status-badge bg-<?= $badge ?> text-white"><?= getDisplayName(ucwords(str_replace('_', ' ', $item['status']))) ?></span>
                                <small class="text-muted"><i class="far fa-clock"></i> <?= format_safe_date($item['created_at'] ?? null, 'd M Y, H:i') ?></small>
                            </div>
                            <p class="mb-1"><strong><?= nl2br(htmlspecialchars(getDisplayName(($item['note']) ?? 'No notes'))) ?></strong></p>
                            <small class="text-muted"><i class="far fa-user"></i> <?= getDisplayName($item['emp_name']) ?></small>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            <div class="text-center mt-4">
                <a href="all_applied_loan.php" class="btn btn-back"><i class="fas fa-arrow-left"></i> <?= __('back_to_loans') ?></a>
                <a href="loan_report_details.php?id=<?=$loan['id']; ?>&emp_id=<?=$loan['emp_id']; ?>" target="_blank" class="btn btn-back ml-2"><i class="fas fa-file-pdf"></i> <?= __('view_report') ?></a>
            </div>
        </div>
    </div>

    <script src="assets/js/jquery.min.js"></script>
    <script src="assets/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php $conDB->close(); ?>
