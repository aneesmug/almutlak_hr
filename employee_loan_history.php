<?php
/**
 * Employee Loan History Page
 * Displays all loan records for a specific employee
 */

require_once("./includes/session_check.php");
require_once __DIR__ . '/includes/sr_history_helpers.php';
include('./includes/MainClass.php');
include("./includes/Hijri_GregorianConvert.php");
$DateConv = new Hijri_GregorianConvert;
$format = "YYYY-MM-DD";

// Get employee ID from the page link first, then fall back to the session employee.
// ?emp_id=X opens another employee's records (from their profile) - access-checked in profile_target.php.
require_once __DIR__ . '/includes/profile_target.php';
$emp_id = $profile_target_empid;
if (empty($emp_id)) {
    header("Location: ./profile.php");
    exit();
}

// Fetch employee data
$emp_query = "SELECT * FROM employees WHERE emp_id = ?";
$stmt = $conDB->prepare($emp_query);
$stmt->bind_param("s", $emp_id);
$stmt->execute();
$emprow = $stmt->get_result()->fetch_assoc();

if (!$emprow) {
    die("Employee not found.");
}

// Fetch loan history with repayment totals so remaining balance can be shown.
$loan_query = "SELECT l.*, 
                      COALESCE(p.total_paid, 0) AS total_paid,
                      GREATEST(COALESCE(l.total_payable, l.loan_amount, 0) - COALESCE(p.total_paid, 0), 0) AS remaining_balance
               FROM emp_loan l
               LEFT JOIN (
                   SELECT loan_id, SUM(amount) AS total_paid
                   FROM emp_loan_payments
                   GROUP BY loan_id
               ) p ON p.loan_id = l.id
               WHERE l.emp_id = ?
               ORDER BY COALESCE(l.created_at, l.start_date) DESC, l.id DESC";
$stmt = $conDB->prepare($loan_query);
$stmt->bind_param("s", $emp_id);
$stmt->execute();
$loan_result = $stmt->get_result();

$loan_history = [];
while ($loan = $loan_result->fetch_assoc()) {
    $loan_history[] = $loan;
}

$loan_payments = [];
if (!empty($loan_history)) {
    $loan_ids = array_map(static function ($loan) {
        return (int)$loan['id'];
    }, $loan_history);
    $placeholders = implode(',', array_fill(0, count($loan_ids), '?'));
    $payment_query = "SELECT loan_id, payment_date, amount, payment_method, receipt_id, attachment
                      FROM emp_loan_payments
                      WHERE loan_id IN ($placeholders)
                      ORDER BY payment_date DESC, id DESC";
    $payment_stmt = $conDB->prepare($payment_query);
    $payment_types = str_repeat('i', count($loan_ids));
    $payment_stmt->bind_param($payment_types, ...$loan_ids);
    $payment_stmt->execute();
    $payment_result = $payment_stmt->get_result();

    while ($payment = $payment_result->fetch_assoc()) {
        $loan_id = (int)$payment['loan_id'];
        if (!isset($loan_payments[$loan_id])) {
            $loan_payments[$loan_id] = [];
        }
        $loan_payments[$loan_id][] = $payment;
    }
}

// Employee can self-cancel any time before the loan reaches a final state
// (rejected/paid/partial/processed/cancelled) - matches vacation's cancel rule.
$loan_cancellable_statuses = [
    'pending', 'approved',
    'dept_manager_pending', 'hr_assistant_pending', 'hr_manager_pending',
    'finance_manager_pending', 'finance_assistant_pending', 'gm_pending'
];

function format_loan_status_label($status) {
    if (!$status) {
        return 'Pending';
    }

    if (strpos($status, 'pending') !== false) {
        return 'Pending';
    }

    return ucwords(str_replace('_', ' ', $status));
}

$sum_amount = 0.0; $sum_paid = 0.0; $sum_remaining = 0.0; $active = 0;
foreach ($loan_history as $l) {
    $st = (string)($l['status'] ?? '');
    if (in_array($st, ['rejected', 'cancelled'], true)) continue;
    $sum_amount += (float)($l['loan_amount'] ?? $l['total_payable'] ?? 0);
    $sum_paid += (float)($l['total_paid'] ?? 0);
    $sum_remaining += (float)($l['remaining_balance'] ?? 0);
    if ((float)($l['remaining_balance'] ?? 0) > 0) $active++;
}

srh_page_open(__('loan_history', 'Loan History'), srh_portal_head());
echo srh_header(__('loan_history', 'Loan History'), '', srh_profile_url($emprow));
echo srh_emp_hero($emprow);
echo srh_tiles([
    [__('total_loans', 'Total Loans'), count($loan_history), 'indigo', $active . ' ' . sr_h(__('active', 'active'))],
    [__('amount', 'Amount'), srh_money($sum_amount), '', ''],
    [__('total_paid', 'Total Paid'), srh_money($sum_paid), 'green', ''],
    [__('remaining_balance', 'Remaining Balance'), srh_money($sum_remaining), $sum_remaining > 0 ? 'amber' : 'green', ''],
]);

if (!$loan_history) {
    $body = sr_empty_state(__('no_loan_records_found', 'No loan records found for this employee.'));
} else {
    $body = '<div class="sr-table-wrap"><table class="sr-table srh-dt"><thead><tr>'
        . '<th>' . sr_h(__('loan_type', 'Loan Type')) . '</th>'
        . '<th class="text-right">' . sr_h(__('amount', 'Amount')) . '</th>'
        . '<th class="text-right">' . sr_h(__('total_paid', 'Total Paid')) . '</th>'
        . '<th class="text-right">' . sr_h(__('remaining_balance', 'Remaining Balance')) . '</th>'
        . '<th>' . sr_h(__('start_date', 'Start Date')) . '</th>'
        . '<th>' . sr_h(__('status', 'Status')) . '</th>'
        . '<th>' . sr_h(__('repayment_details', 'Repayment Details')) . '</th>'
        . '<th class="text-right no-print">' . sr_h(__('action', 'Action')) . '</th>'
        . '</tr></thead><tbody>';
    foreach ($loan_history as $loan) {
        $payments = $loan_payments[(int)$loan['id']] ?? [];
        $amount = (float)($loan['loan_amount'] ?? $loan['total_payable'] ?? 0);
        $paid = (float)($loan['total_paid'] ?? 0);
        $remaining = (float)($loan['remaining_balance'] ?? 0);
        $pct = $amount > 0 ? min(100, $paid / max($amount, (float)($loan['total_payable'] ?? 0), 0.01) * 100) : 0;
        $loan_date = $loan['start_date'] ?? $loan['created_at'] ?? null;
        $is_emergency = ($loan['loan_type'] ?? 'regular') === 'emergency';

        $repay = '<span class="sr-cell-sub">' . sr_h(__('no_repayments_yet', 'No repayments yet')) . '</span>';
        if ($payments) {
            $repay = '<details class="srh-repay"><summary>' . count($payments) . ' ' . sr_h(__('payments', 'payments')) . '</summary><ul>';
            foreach ($payments as $p) {
                $repay .= '<li><span>' . sr_h(format_safe_date($p['payment_date'] ?? null, 'd M Y')) . ' · ' . sr_h(ucfirst((string)($p['payment_method'] ?? 'manual')))
                    . (!empty($p['receipt_id']) ? ' · ' . sr_h($p['receipt_id']) : '') . '</span><b>' . srh_money($p['amount']) . '</b>'
                    . (!empty($p['attachment']) ? ' <a href="' . sr_h($p['attachment']) . '" target="_blank" rel="noopener" title="' . sr_h(__('view_receipt', 'View Receipt')) . '"><i class="mdi mdi-paperclip"></i></a>' : '')
                    . '</li>';
            }
            $repay .= '</ul></details>';
        }

        $primary = !empty($loan['inv_no'])
            ? '<a class="sr-open-btn" href="loan_status_history.php?inv_no=' . urlencode($loan['inv_no']) . '" target="_blank"><i class="mdi mdi-eye"></i> ' . sr_h(__('view', 'View')) . '</a>'
            : '<span class="text-muted">N/A</span>';
        $menu = [];
        if (in_array($loan['status'] ?? '', $loan_cancellable_statuses, true)) {
            $menu[] = '<button type="button" class="dropdown-item text-danger" onclick="cancelLoanRequest(' . (int)$loan['id'] . ', ' . sr_h(json_encode((string)($loan['inv_no'] ?? ''))) . ')"><i class="mdi mdi-close"></i> ' . sr_h(__('cancel_request', 'Cancel Request')) . '</button>';
        }

        $body .= '<tr>'
            . '<td>' . sr_pill($is_emergency ? 'amber' : 'sky', ucfirst((string)($loan['loan_type'] ?? 'regular')), true) . '<span class="sr-cell-sub sr-mono">' . sr_h($loan['inv_no'] ?? '') . '</span></td>'
            . '<td class="text-right" data-order="' . $amount . '">' . srh_money($amount) . '<span class="sr-cell-sub">' . srh_money($loan['monthly_deduction'] ?? 0) . ' / ' . sr_h(__('month', 'month')) . '</span></td>'
            . '<td class="text-right" data-order="' . $paid . '"><span style="color: var(--tone-green-fg); font-weight: 600;">' . srh_money($paid) . '</span>'
                . '<div class="srh-progress srh-progress-sm"><span style="width: ' . number_format($pct, 1) . '%;"></span></div></td>'
            . '<td class="text-right" data-order="' . $remaining . '"><span style="color: ' . ($remaining > 0 ? 'var(--tone-red-fg)' : 'var(--tone-green-fg)') . '; font-weight: 600;">' . srh_money($remaining) . '</span></td>'
            . '<td data-order="' . sr_h($loan_date) . '">' . (!empty($loan_date) ? '<span class="sr-date">' . sr_h(format_safe_date($loan_date, 'd M Y')) . '<small>' . sr_h($DateConv->GregorianToHijri(substr($loan_date, 0, 10), $format)) . '</small></span>' : '-') . '</td>'
            . '<td>' . srh_status_pill($loan['status'] ?? 'pending', format_loan_status_label($loan['status'] ?? 'pending')) . '</td>'
            . '<td>' . $repay . '</td>'
            . '<td class="text-right no-print">' . srh_row_actions($primary, $menu) . '</td>'
            . '</tr>';
    }
    $body .= '</tbody></table></div>';
}
echo srh_card('mdi-cash-multiple', __('loan_history', 'Loan History'), $body, $loan_history ? srh_search_box() : '', 'sr-card-body p-0');

$cancel_js = <<<'JS'
<script>
    function cancelLoanRequest(loanId, invNo) {
        Swal.fire({
            title: __('cancel_loan_request') || 'Cancel Loan Request',
            html: `<p>${__('are_you_sure_cancel_request') || 'Are you sure you want to cancel this request?'}</p><strong>${$('<div>').text(invNo).html()}</strong>`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: __('yes_cancel_request') || 'Yes, Cancel It',
            cancelButtonText: __('keep_request') || 'No',
            allowOutsideClick: false
        }).then((result) => {
            if (!result.isConfirmed) return;
            Swal.fire({ title: __('cancelling_request') || 'Cancelling...', allowOutsideClick: false, allowEscapeKey: false, didOpen: () => Swal.showLoading() });
            $.ajax({
                url: './includes/ajaxFile/ajaxLoan.php',
                type: 'POST',
                dataType: 'JSON',
                data: { ajaxType: 'cancel_loan_self', loan_id: loanId },
                success: function (response) {
                    Swal.fire({ title: response.title || __('success'), text: response.message, icon: response.type || 'success', confirmButtonText: __('ok') })
                        .then(() => location.reload());
                },
                error: function (xhr) {
                    const response = xhr.responseJSON || {};
                    Swal.fire({ title: response.title || __('error'), text: response.message || 'Failed to cancel loan request.', icon: 'error', confirmButtonText: __('ok') });
                }
            });
        });
    }
</script>
JS;

srh_page_close('<script src="assets/js/employee_profile.js"></script>' . $cancel_js
    . srh_dt_js('.srh-dt', 4, 'desc', 'mdi-cash-multiple', 'srhSearch', [6, 7]));
