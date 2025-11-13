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
               e.name AS employee_name, e.avatar, e.dept, d.dep_nme AS department_name
        FROM emp_loan l
        JOIN employees e ON l.emp_id = e.emp_id
        LEFT JOIN department d ON e.dept = d.id
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
$stmt = $conDB->prepare("SELECT ra.approval_level, ra.status, ra.action_date,
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

$avatar_path = 'assets/images/users/avatar-1.jpg';
if (!empty($loan['avatar'])) {
    $avatar_candidate = $loan['avatar'];
    // If stored path is relative to project root
    if (is_file(__DIR__ . '/' . $avatar_candidate)) {
        $avatar_path = $avatar_candidate;
    }
}
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
        .page-header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: #fff; padding: 30px 0; margin-bottom: 30px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        .page-header h3 { margin: 0; font-weight: 600; }
        .info-card { background: #fff; border-radius: 10px; padding: 25px; margin-bottom: 20px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        .info-card h5 { color: #667eea; margin-bottom: 20px; font-weight: 600; border-bottom: 2px solid #f0f0f0; padding-bottom: 10px; }
        .employee-header { display: flex; align-items: center; margin-bottom: 20px; }
        .employee-avatar { width: 80px; height: 80px; border-radius: 50%; object-fit: cover; margin-right: 20px; border: 3px solid #667eea; }
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
        .timeline { position: relative; padding: 30px 0; margin-top: 20px; }
        .timeline:before { content: ''; position: absolute; left: 40px; top: 0; bottom: 0; width: 3px; background: linear-gradient(to bottom, #667eea, #e0e0e0); }
        .timeline-item { position: relative; padding-left: 90px; padding-bottom: 30px; }
        .timeline-marker { position: absolute; left: 25px; width: 30px; height: 30px; border-radius: 50%; background: #fff; border: 4px solid #28a745; z-index: 1; display:flex;align-items:center;justify-content:center; box-shadow: 0 2px 8px rgba(0,0,0,0.15); }
        .timeline-marker.pending { border-color: #ffc107; }
        .timeline-marker.rejected { border-color: #dc3545; }
        .timeline-content { background: #fff; padding: 20px; border-radius: 10px; box-shadow: 0 3px 10px rgba(0,0,0,0.08); border-left: 4px solid transparent; }
        .timeline-content.approved { border-left-color: #28a745; }
        .timeline-content.pending { border-left-color: #ffc107; }
        .timeline-content.rejected { border-left-color: #dc3545; }
        .status-badge { padding: 6px 14px; border-radius: 20px; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; }
        .btn-back { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border: none; color: #fff; padding: 12px 30px; border-radius: 25px; font-weight: 600; }
    </style>
</head>
<body class="enlarged">
    <div class="page-header">
        <div class="container">
            <h3><i class="fas fa-history"></i> <?= function_exists('__') ? __('loan_approval_history') : 'Loan Approval History' ?></h3>
            <p class="mb-0"><?= function_exists('__') ? __('complete_approval_timeline') : 'Complete approval timeline and status history' ?></p>
        </div>
    </div>

    <div class="container" style="margin-bottom:30px;">
        <!-- Employee Info -->
        <div class="info-card">
            <div class="employee-header">
                <img src="<?= htmlspecialchars($avatar_path) ?>" class="employee-avatar" alt="avatar" />
                <div>
                    <h4 style="margin:0;"><?= htmlspecialchars(function_exists('parseName') ? parseName($loan['employee_name']) : $loan['employee_name']) ?></h4>
                    <div class="text-muted" style="margin-top:6px;">
                        <strong><?= function_exists('__') ? __('emp_id') : 'Employee ID' ?>:</strong> <?= htmlspecialchars($loan['emp_id']) ?>
                        &nbsp; | &nbsp;
                        <strong><?= function_exists('__') ? __('department') : 'Department' ?>:</strong> <?= htmlspecialchars($loan['department_name'] ?? 'N/A') ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-6">
                <div class="info-card">
                    <h5><i class="fas fa-file-invoice-dollar"></i> <?= function_exists('__') ? __('loan_details') : 'Loan Details' ?></h5>
                    <div class="info-row"><div class="info-label"><i class="fas fa-barcode"></i> <?= function_exists('__') ? __('invoice_no') : 'Invoice No' ?>:</div><div class="info-value"><code><?= htmlspecialchars($loan['inv_no']) ?></code></div></div>
                    <div class="info-row"><div class="info-label"><i class="fas fa-tag"></i> <?= function_exists('__') ? __('loan_type') : 'Loan Type' ?>:</div><div class="info-value"><strong><?= htmlspecialchars(ucwords(str_replace('_', ' ', $loan['loan_type']))) ?></strong></div></div>
                    <div class="info-row"><div class="info-label"><i class="fas fa-money-bill-wave"></i> <?= function_exists('__') ? __('loan_amount') : 'Loan Amount' ?>:</div><div class="info-value"><span class="amount-display">SAR <?= number_format($loan['loan_amount'], 2) ?></span></div></div>
                    <div class="info-row"><div class="info-label"><i class="fas fa-calendar-alt"></i> <?= function_exists('__') ? __('installments') : 'Installments' ?>:</div><div class="info-value"><strong><?= (int)$loan['installments'] ?></strong> <?= function_exists('__') ? __('months') : 'months' ?></div></div>
                    <div class="info-row"><div class="info-label"><i class="fas fa-hand-holding-usd"></i> <?= function_exists('__') ? __('monthly_deduction') : 'Monthly Deduction' ?>:</div><div class="info-value"><strong>SAR <?= number_format($loan['monthly_deduction'], 2) ?></strong></div></div>
                    <?php if (!empty($loan['interest_rate'])): ?>
                    <div class="info-row"><div class="info-label"><i class="fas fa-percent"></i> <?= function_exists('__') ? __('interest_rate') : 'Interest Rate' ?>:</div><div class="info-value"><strong><?= number_format($loan['interest_rate'], 2) ?>%</strong></div></div>
                    <?php endif; ?>
                    <div class="info-row"><div class="info-label"><i class="fas fa-calculator"></i> <?= function_exists('__') ? __('total_payable') : 'Total Payable' ?>:</div><div class="info-value"><strong style="color:#dc3545;">SAR <?= number_format($loan['total_payable'], 2) ?></strong></div></div>
                    <div class="info-row"><div class="info-label"><i class="fas fa-info-circle"></i> <?= function_exists('__') ? __('status') : 'Status' ?>:</div><div class="info-value"><span class="badge badge-<?= $status_class ?>" style="font-size:14px;padding:8px 16px;"><i class="fas <?= $status_icon ?>"></i> <?= htmlspecialchars(ucwords(str_replace('_', ' ', $loan['status']))) ?></span></div></div>
                    <?php if (!empty($loan['payment_proof_file'])): ?>
                    <div class="info-row"><div class="info-label"><i class="fas fa-file-pdf"></i> <?= function_exists('__') ? __('payment_proof') : 'Payment Proof' ?>:</div><div class="info-value"><a href="<?= htmlspecialchars($loan['payment_proof_file']) ?>" target="_blank" class="attachment-link"><i class="fas fa-download"></i> <?= function_exists('__') ? __('view_document') : 'View Document' ?></a></div></div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="col-md-6">
                <?php if (in_array($loan['status'], ['approved','paid'])): ?>
                <div class="info-card">
                    <h5><i class="fas fa-chart-line"></i> <?= function_exists('__') ? __('payment_progress') : 'Payment Progress' ?></h5>
                    <div class="info-row"><div class="info-label"><i class="fas fa-check-double"></i> <?= function_exists('__') ? __('total_paid') : 'Total Paid' ?>:</div><div class="info-value"><strong style="color:#28a745;">SAR <?= number_format($total_paid, 2) ?></strong></div></div>
                    <div class="info-row"><div class="info-label"><i class="fas fa-hourglass-half"></i> <?= function_exists('__') ? __('remaining') : 'Remaining' ?>:</div><div class="info-value"><strong style="color:#dc3545;">SAR <?= number_format($remaining_amount, 2) ?></strong></div></div>
                    <div class="progress-bar-custom"><div class="progress-fill" style="width: <?= number_format($payment_percentage, 1) ?>%"></div></div>
                    <?php if (!empty($payments)): ?>
                    <div class="mt-3">
                        <strong><i class="fas fa-list"></i> <?= function_exists('__') ? __('recent_payments') : 'Recent Payments' ?>:</strong>
                        <div class="mt-2">
                            <?php foreach (array_slice($payments, 0, 3) as $payment): ?>
                            <div class="payment-item">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <i class="fas fa-calendar"></i> <?= date('d M Y', strtotime($payment['payment_date'])) ?>
                                        <span class="badge badge-<?= ($payment['payment_method']==='manual') ? 'info' : 'primary' ?> ml-2"><?= ucfirst($payment['payment_method']) ?></span>
                                    </div>
                                    <strong>SAR <?= number_format($payment['amount'], 2) ?></strong>
                                </div>
                                <?php if (!empty($payment['attachment'])): ?>
                                <a class="text-muted small" target="_blank" href="<?= htmlspecialchars($payment['attachment']) ?>"><i class="fas fa-paperclip"></i> <?= function_exists('__') ? __('view_receipt') : 'View Receipt' ?></a>
                                <?php endif; ?>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>

                <div class="info-card">
                    <h5><i class="fas fa-users-cog"></i> <?= function_exists('__') ? __('approval_chain') : 'Approval Chain' ?></h5>
                    <table class="table table-sm chain-table table-hover">
                        <thead><tr><th width="70" class="text-center"><?= function_exists('__') ? __('level') : 'Level' ?></th><th><?= function_exists('__') ? __('approver') : 'Approver' ?></th><th><?= function_exists('__') ? __('status') : 'Status' ?></th></tr></thead>
                        <tbody>
                        <?php if (empty($chain)): ?>
                            <tr><td colspan="3" class="text-center text-muted"><i class="fas fa-info-circle"></i> <?= function_exists('__') ? __('no_approval_chain') : 'No approval chain configured' ?></td></tr>
                        <?php else: foreach ($chain as $link): 
                            $cClass = 'secondary'; $cIcon = 'fa-circle';
                            if ($link['status']==='approved') { $cClass='success'; $cIcon='fa-check-circle'; }
                            elseif ($link['status']==='rejected') { $cClass='danger'; $cIcon='fa-times-circle'; }
                            elseif ($link['status']==='pending') { $cClass='warning'; $cIcon='fa-clock'; }
                        ?>
                            <tr>
                                <td class="text-center"><span class="level-badge"><?= (int)$link['approval_level'] ?></span></td>
                                <td><strong><?= htmlspecialchars($link['approver_name'] ?? 'N/A') ?></strong><br><small class="text-muted"><i class="fas fa-user-tag"></i> <?= htmlspecialchars($link['user_type'] ?? '') ?></small></td>
                                <td>
                                    <span class="badge badge-<?= $cClass ?>"><i class="fas <?= $cIcon ?>"></i> <?= htmlspecialchars(ucfirst($link['status'])) ?></span>
                                    <?php if (!empty($link['action_date'])): ?><br><small class="text-muted"><i class="far fa-calendar-alt"></i> <?= date('d M Y, H:i', strtotime($link['action_date'])) ?></small><?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="info-card">
            <h5><i class="fas fa-history"></i> <?= function_exists('__') ? __('status_history_timeline') : 'Status History Timeline' ?></h5>
            <?php if (empty($history)): ?>
                <div class="alert alert-info"><i class="fas fa-info-circle"></i> <?= function_exists('__') ? __('no_history') : 'No status history available for this loan request.' ?></div>
            <?php else: ?>
                <div class="timeline">
                    <?php foreach ($history as $item): 
                        $marker_class = 'draft';
                        if (strpos($item['status'], 'approved') !== false) $marker_class = 'approved';
                        elseif (strpos($item['status'], 'rejected') !== false) $marker_class = 'rejected';
                        elseif (strpos($item['status'], 'pending') !== false) $marker_class = 'pending';
                        $badge_class = 'secondary';
                        if (strpos($item['status'], 'approved') !== false) $badge_class = 'success';
                        elseif (strpos($item['status'], 'rejected') !== false) $badge_class = 'danger';
                        elseif (strpos($item['status'], 'pending') !== false) $badge_class = 'warning';
                    ?>
                    <div class="timeline-item">
                        <div class="timeline-marker <?= $marker_class ?>"></div>
                        <div class="timeline-content <?= $marker_class ?>">
                            <span class="status-badge bg-<?= $badge_class ?> text-white"><?= htmlspecialchars(ucwords(str_replace('_', ' ', $item['status']))) ?></span>
                            <p class="mb-2"><strong><?= nl2br(htmlspecialchars($item['note'])) ?></strong></p>
                            <small class="text-muted"><i class="far fa-clock"></i> <?= date('d M Y, H:i:s', strtotime($item['created_at'])) ?> | <i class="far fa-user"></i> <?= htmlspecialchars($item['emp_name']) ?></small>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            <div class="text-center mt-4">
                <a href="all_applied_loan.php" class="btn btn-back"><i class="fas fa-arrow-left"></i> <?= function_exists('__') ? __('back_to_loans') : 'Back to Loan Requests' ?></a>
                <a href="loan_report_details.php?id=<?=$loan['id']; ?>&emp_id=<?=$loan['emp_id']; ?>" target="_blank" class="btn btn-back ml-2"><i class="fas fa-file-pdf"></i> <?= function_exists('__') ? __('view_report') : 'View Report' ?></a>
            </div>
        </div>
    </div>

    <script src="assets/js/jquery.min.js"></script>
    <script src="assets/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php $conDB->close(); ?>
