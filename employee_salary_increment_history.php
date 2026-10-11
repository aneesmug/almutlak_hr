<?php
/**
 * Employee Salary Increment History Page
 * Displays all salary increment requests submitted about the logged-in employee
 */

require_once("./includes/session_check.php");
require_once __DIR__ . '/includes/sr_history_helpers.php';
include('./includes/MainClass.php');
include("./includes/Hijri_GregorianConvert.php");
$DateConv = new Hijri_GregorianConvert;
$format = "YYYY-MM-DD";

$emp_id = $_SESSION['empid'] ?? ($empid ?? null);
if (empty($emp_id)) {
    header("Location: ./profile.php");
    exit();
}

$emp_query = "SELECT e.*, cp.period FROM employees e
              LEFT JOIN contract_period cp ON e.vac_period = cp.id
              WHERE e.emp_id = ?";
$stmt = $conDB->prepare($emp_query);
$stmt->bind_param("s", $emp_id);
$stmt->execute();
$emprow = $stmt->get_result()->fetch_assoc();

if (!$emprow) {
    die("Employee not found.");
}

$si_query = "SELECT si.id, si.request_inv_no, si.increment_amount, si.approved_amount, si.evaluation_score,
                    si.current_status, si.created_at, si.emp_id
             FROM emp_salary_increment si
             WHERE si.emp_id = ?
             ORDER BY si.created_at DESC";
$stmt = $conDB->prepare($si_query);
$stmt->bind_param("s", $emp_id);
$stmt->execute();
$si_result = $stmt->get_result();

$rows = [];
while ($si = $si_result->fetch_assoc()) $rows[] = $si;
$count_by = ['approved' => 0, 'pending' => 0, 'rejected' => 0];
$total_approved = 0.0;
foreach ($rows as $r) {
    $st = (string)($r['current_status'] ?? '');
    if ($st === 'approved') { $count_by['approved']++; $total_approved += (float)($r['approved_amount'] ?? $r['increment_amount']); }
    elseif ($st === 'rejected') $count_by['rejected']++;
    elseif (strpos($st, 'pending') !== false) $count_by['pending']++;
}

srh_page_open(__('salary_increment_history', 'Salary Increment History'), srh_dt_head());
echo srh_header(__('salary_increment_history', 'Salary Increment History'), '', srh_profile_url($emprow));
echo srh_emp_hero($emprow);
echo srh_tiles([
    [__('total_requests', 'Total Requests'), count($rows), 'indigo', ''],
    [__('approved', 'Approved'), $count_by['approved'], 'green', srh_money($total_approved)],
    [__('pending', 'Pending'), $count_by['pending'], 'amber', ''],
    [__('rejected', 'Rejected'), $count_by['rejected'], $count_by['rejected'] ? 'red' : '', ''],
]);

if (!$rows) {
    $body = sr_empty_state(__('no_salary_increment_requests_found', 'No salary increment requests found'));
} else {
    $body = '<div class="sr-table-wrap"><table class="sr-table srh-dt"><thead><tr>'
        . '<th>' . sr_h(__('request_id', 'Request ID')) . '</th>'
        . '<th class="text-right">' . sr_h(__('increment_amount', 'Increment Amount')) . '</th>'
        . '<th class="text-right">' . sr_h(__('approved_amount', 'Approved Amount')) . '</th>'
        . '<th>' . sr_h(__('evaluation_score', 'Evaluation Score')) . '</th>'
        . '<th>' . sr_h(__('status', 'Status')) . '</th>'
        . '<th>' . sr_h(__('applied_date', 'Applied Date')) . '</th>'
        . '<th class="text-right no-print">' . sr_h(__('action', 'Action')) . '</th>'
        . '</tr></thead><tbody>';
    foreach ($rows as $si) {
        $status = (string)($si['current_status'] ?? 'pending_approval');
        $body .= '<tr>'
            . '<td><span class="sr-mono">' . sr_h($si['request_inv_no']) . '</span></td>'
            . '<td class="text-right">' . srh_money($si['increment_amount']) . '</td>'
            . '<td class="text-right">' . ($si['approved_amount'] !== null ? '<span style="color: var(--tone-green-fg); font-weight: 600;">' . srh_money($si['approved_amount']) . '</span>' : '-') . '</td>'
            . '<td><span class="sr-chip">' . ($si['evaluation_score'] !== null ? number_format((float)$si['evaluation_score'], 2) : '-') . '</span></td>'
            . '<td>' . srh_status_pill($status, __($status)) . '</td>'
            . '<td data-order="' . sr_h($si['created_at']) . '"><span class="sr-date">' . sr_h(format_safe_date((string)$si['created_at'], 'd M Y'))
                . '<small>' . sr_h($DateConv->GregorianToHijri($si['created_at'], $format)) . '</small></span></td>'
            . '<td class="text-right no-print"><a class="sr-open-btn" href="salary_increment_status_history.php?request_inv_no=' . urlencode((string)$si['request_inv_no']) . '" target="_blank"><i class="mdi mdi-eye"></i> ' . sr_h(__('view_report', 'View Report')) . '</a></td>'
            . '</tr>';
    }
    $body .= '</tbody></table></div>';
}
echo srh_card('mdi-trending-up', __('salary_increment_history', 'Salary Increment History'), $body, $rows ? srh_search_box() : '', 'sr-card-body p-0');

srh_page_close(srh_dt_js('.srh-dt', 5, 'desc', 'mdi-trending-up', 'srhSearch', [6]));
