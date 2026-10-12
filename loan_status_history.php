<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/session_check.php';
require_once __DIR__ . '/includes/sr_history_helpers.php';

// Input
$inv_no = isset($_GET['inv_no']) ? trim($_GET['inv_no']) : '';
if ($inv_no === '') {
    srh_die('Loan invoice number not provided.');
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
    srh_die('Loan request not found.');
}
// Enforce department scoping: Only HR and System Admin can view other departments,
// otherwise allow if the user is part of the approval chain for this loan
$canSeeAll = ($is_system_admin ?? false) || ($isHR ?? false);
if (!$canSeeAll) {
    $userDeptId = $user_dept ?? null;
    $sameDept = ($userDeptId !== null) ? ((int)$loan['dept'] === (int)$userDeptId) : false;
    $inChain = false;
    if (!$sameDept) {
        if ($chk = $conDB->prepare("SELECT 1 FROM request_approvers ra JOIN approval_request_types t ON t.id = ra.request_type_id AND t.type_name = 'loan_request' WHERE ra.request_inv_no = ? AND ra.approver_id IN (?, {$approver_alt_id}) LIMIT 1")) {
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
        srh_die('Access denied: You are not authorized to view this request.');
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

/// Use getAvatarImagePath function for proper gender-based default avatars
$avatar_path = getAvatarImagePath($loan['avatar'] ?? '', $loan['sex'] ?? 1);
$dept_label = ($is_rtl ?? false) ? ($loan['department_name_ar'] ?? '') : ($loan['department_name'] ?? '');
$is_rejected = ($loan['status'] ?? '') === 'rejected';
$show_progress = in_array($loan['status'], ['approved', 'paid'], true);

// Rejection details: approver note first, then the loan's own rejection fields.
$rejection_rows = [];
if ($is_rejected) {
    if (!empty($loan['rejection_notes'])) {
        $rejection_rows = [
            [__('rejection_reason'), nl2br(sr_h(getDisplayName($loan['rejection_notes']))), 'mdi-comment-text-outline'],
            [__('rejected_by'), !empty($loan['rejection_approver_name']) ? sr_h(getDisplayName($loan['rejection_approver_name'])) : null, 'mdi-account'],
            [__('rejection_date'), !empty($loan['rejection_timestamp']) ? sr_h(format_safe_date($loan['rejection_timestamp'], 'd M Y, H:i:s')) : null, 'mdi-calendar'],
        ];
    } elseif (!empty($loan['rejection_reason'])) {
        $rejection_rows = [
            [__('rejection_reason'), nl2br(sr_h(getDisplayName($loan['rejection_reason']))), 'mdi-comment-text-outline'],
            [__('rejection_date'), !empty($loan['rejection_date']) ? sr_h(format_safe_date($loan['rejection_date'], 'd M Y, H:i:s')) : null, 'mdi-calendar'],
            [__('rejected_by'), !empty($loan['rejected_by_name']) ? sr_h(getDisplayName($loan['rejected_by_name'])) : null, 'mdi-account'],
        ];
    }
}

// Payments table
$payments_html = sr_empty_state(__('no_payments', 'No payments yet'));
if (!empty($payments)) {
    $payments_html = '<div class="sr-table-wrap"><table class="sr-table" style="margin: 0;"><thead><tr>'
        . '<th>' . sr_h(__('date', 'Date')) . '</th><th>' . sr_h(__('payment_method', 'Method')) . '</th><th>' . sr_h(__('receipt', 'Receipt')) . '</th><th class="text-right">' . sr_h(__('amount', 'Amount')) . '</th>'
        . '</tr></thead><tbody>';
    foreach ($payments as $p) {
        $payments_html .= '<tr><td>' . sr_h(format_safe_date($p['payment_date'] ?? null, 'd M Y')) . '</td>'
            . '<td>' . sr_pill(($p['payment_method'] === 'manual') ? 'sky' : 'indigo', ucfirst((string)$p['payment_method']), true) . '</td>'
            . '<td>' . (!empty($p['attachment']) ? '<a class="srh-file" target="_blank" rel="noopener" href="' . sr_h($p['attachment']) . '"><i class="mdi mdi-paperclip"></i> ' . sr_h(__('view_receipt')) . '</a>' : sr_h($p['receipt_id'] ?: '-')) . '</td>'
            . '<td class="text-right">' . srh_money($p['amount']) . '</td></tr>';
    }
    $payments_html .= '</tbody></table></div>';
}

$chain_rows = [];
foreach ($chain as $link) {
    // If request is rejected, skip pending/awaiting approvers
    if ($is_rejected && in_array($link['status'], ['pending', 'awaiting'], true)) continue;
    $chain_rows[] = ['level' => $link['approval_level'], 'name' => getDisplayName(parseName($link['approver_name']) ?? 'N/A'), 'role' => $link['user_type'] ?? '', 'status' => $link['status'], 'date' => $link['action_date'], 'note' => !empty($link['note']) ? getDisplayName($link['note']) : ''];
}
$history_rows = array_map(function ($e) {
    return ['status' => $e['status'] ?? '', 'by' => getDisplayName($e['emp_name'] ?? ''), 'date' => $e['created_at'] ?? null, 'note' => getDisplayName($e['note'] ?? '')];
}, $history);

$report_btn = '<a href="loan_report_details.php?id=' . (int)$loan['id'] . '&emp_id=' . urlencode($loan['emp_id']) . '" target="_blank" class="sr-btn"><i class="mdi mdi-file-pdf"></i> ' . sr_h(__('view_report')) . '</a>';

srh_page_open(__('loan_approval_history'));
echo srh_header(__('loan_approval_history'), __('complete_approval_timeline'), 'all_applied_loan.php', $report_btn);

echo srh_hero(getDisplayName($loan['employee_name']), $avatar_path, [
    [__('emp_id'), $loan['emp_id'], 'mdi-account-card-details'],
    [__('department'), $dept_label, 'mdi-domain'],
    [__('invoice_no'), $loan['inv_no'], 'mdi-barcode'],
], srh_status_pill($loan['status']));

echo srh_tiles([
    [__('loan_amount'), srh_money($loan['loan_amount']), 'indigo', sr_h(getDisplayName(ucwords(str_replace('_', ' ', $loan['loan_type']))))],
    [__('monthly_deduction'), srh_money($loan['monthly_deduction']), '', (int)$loan['installments'] . ' ' . sr_h(__('months'))],
    [__('total_paid'), srh_money($total_paid), 'green', number_format($payment_percentage, 1) . '%'],
    [__('remaining'), srh_money($remaining_amount), $remaining_amount > 0 ? 'amber' : 'green', ''],
]);
?>
<div class="srh-grid">
    <div>
        <?= srh_card('mdi-file-document', __('loan_details'), srh_kv([
            [__('invoice_no'), '<span class="sr-mono">' . sr_h($loan['inv_no']) . '</span>', 'mdi-barcode'],
            [__('loan_type'), sr_h(getDisplayName(ucwords(str_replace('_', ' ', $loan['loan_type'])))), 'mdi-tag'],
            [__('loan_amount'), srh_money($loan['loan_amount']), 'mdi-cash'],
            [__('installments'), '<strong>' . (int)$loan['installments'] . '</strong> ' . sr_h(__('months')), 'mdi-calendar'],
            [__('monthly_deduction'), srh_money($loan['monthly_deduction']), 'mdi-cash-multiple'],
            [__('interest_rate'), !empty($loan['interest_rate']) ? number_format($loan['interest_rate'], 2) . '%' : null, 'mdi-percent'],
            [__('total_payable'), '<strong style="color: var(--tone-red-fg);">' . srh_money($loan['total_payable']) . '</strong>', 'mdi-calculator'],
            [__('status'), srh_status_pill($loan['status']), 'mdi-flag'],
            [__('payment_proof'), !empty($loan['payment_proof_file']) ? '<a class="srh-file" href="' . sr_h($loan['payment_proof_file']) . '" target="_blank" rel="noopener"><i class="mdi mdi-download"></i> ' . sr_h(__('view_document')) . '</a>' : null, 'mdi-file-pdf'],
        ])) ?>
        <?php if ($is_rejected): ?>
            <?= srh_card('mdi-cancel', __('rejection_details'), $rejection_rows
                ? '<div class="sr-notice tone-red" style="margin-bottom: 0;"><i class="mdi mdi-close-circle"></i><div style="flex: 1;">' . srh_kv($rejection_rows) . '</div></div>'
                : '<div class="sr-notice tone-red" style="margin-bottom: 0;"><i class="mdi mdi-information-outline"></i><div>' . sr_h(__('no_rejection_reason_provided', 'No rejection reason provided')) . '</div></div>') ?>
        <?php endif; ?>
    </div>
    <div>
        <?php if ($show_progress): ?>
            <?= srh_card('mdi-chart-line', __('payment_progress'),
                '<div class="srh-progress"><span style="width: ' . number_format(min(100, $payment_percentage), 1) . '%;"></span></div>'
                . '<div class="srh-progress-meta"><span>' . sr_h(__('total_paid')) . ': <b>' . srh_money($total_paid) . '</b></span><span>' . sr_h(__('remaining')) . ': <b>' . srh_money($remaining_amount) . '</b></span></div>'
                . '<div style="margin-top: 14px;">' . $payments_html . '</div>',
                count($payments) . ' ' . sr_h(__('payments', 'payments'))) ?>
        <?php endif; ?>
        <?= srh_card('mdi-sitemap', __('approval_chain'), srh_chain($chain_rows), count($chain_rows) . ' ' . sr_h(__('levels', 'levels'))) ?>
    </div>
</div>
<?= srh_card('mdi-history', __('status_history_timeline'), srh_timeline($history_rows)) ?>
<?php
srh_page_close();
$conDB->close();
