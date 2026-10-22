<?php
/**
 * Employee Vacation History Page
 * Displays all vacation requests for a specific employee
 */

require_once("./includes/session_check.php");
require_once __DIR__ . '/includes/sr_history_helpers.php';
include('./includes/MainClass.php');
include("./includes/Hijri_GregorianConvert.php");
$DateConv = new Hijri_GregorianConvert;
$format = "YYYY-MM-DD";

// Get employee ID securely from session
// ?emp_id=X opens another employee's records (from their profile) - access-checked in profile_target.php.
require_once __DIR__ . '/includes/profile_target.php';
$emp_id = $profile_target_empid;
if (empty($emp_id)) {
    header("Location: ./profile.php");
    exit();
}

// Fetch employee data
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

// Fetch vacation history
$vacation_query = "SELECT * FROM emp_vacation WHERE emp_id = ? ORDER BY start_date DESC";
$stmt = $conDB->prepare($vacation_query);
$stmt->bind_param("s", $emp_id);
$stmt->execute();
$vacation_result = $stmt->get_result();

$all_statuses = [
    'draft' => __('draft'),
    'pending_approval' => __('pending_approval'),
    'approved' => __('approved'),
    'completed' => __('completed'),
    'rejected' => __('rejected'),
    'cancelled' => __('cancelled')
];

$rows = [];
while ($vac = $vacation_result->fetch_assoc()) {
    // Do not show legacy invoices in vacation history
    if (isset($vac['request_inv_no']) && strpos($vac['request_inv_no'], 'LEGACY-') === 0) continue;
    $rows[] = $vac;
}
$count_by = ['approved' => 0, 'pending' => 0, 'rejected' => 0];
$days_taken = 0.0;
foreach ($rows as $r) {
    $st = (string)($r['current_status'] ?? '');
    if ($st === 'approved' || $st === 'completed') { $count_by['approved']++; $days_taken += (float)$r['vacdays']; }
    elseif ($st === 'rejected') $count_by['rejected']++;
    elseif (strpos($st, 'pending') !== false) $count_by['pending']++;
}

srh_page_open(__('vacation_history', 'Vacation History'), srh_portal_head());
echo srh_header(__('vacation_history', 'Vacation History'), '', srh_profile_url($emprow));
echo srh_emp_hero($emprow, [[__('contract_period', 'Contract Period'), $emprow['period'] ?? '', 'mdi-calendar']]);
echo srh_tiles([
    [__('total_requests', 'Total Requests'), count($rows), 'indigo', ''],
    [__('approved', 'Approved'), $count_by['approved'], 'green', number_format($days_taken, 0) . ' ' . sr_h(__('days', 'days'))],
    [__('pending', 'Pending'), $count_by['pending'], 'amber', ''],
    [__('rejected', 'Rejected'), $count_by['rejected'], $count_by['rejected'] ? 'red' : '', ''],
]);

if (!$rows) {
    $body = sr_empty_state(__('no_vacation_history_found', 'No vacation history found for this employee.'));
} else {
    $body = '<div class="sr-table-wrap"><table class="sr-table srh-dt"><thead><tr>'
        . '<th>' . sr_h(__('type', 'Type')) . '</th>'
        . '<th>' . sr_h(__('from_date', 'From Date')) . '</th>'
        . '<th>' . sr_h(__('to_date', 'To Date')) . '</th>'
        . '<th>' . sr_h(__('days', 'Days')) . '</th>'
        . '<th>' . sr_h(__('status', 'Status')) . '</th>'
        . '<th>' . sr_h(__('applied_date', 'Applied Date')) . '</th>'
        . '<th class="text-right no-print">' . sr_h(__('action', 'Action')) . '</th>'
        . '</tr></thead><tbody>';
    foreach ($rows as $vac) {
        $status = (string)$vac['current_status'];
        $status_label = $all_statuses[$status] ?? ucfirst(str_replace('_', ' ', $status));
        $menu = [];
        if (!empty($vac['request_inv_no'])) {
            $menu[] = '<a class="dropdown-item" href="vacation_status_history.php?request_inv_no=' . urlencode((string)$vac['request_inv_no']) . '" target="_blank"><i class="mdi mdi-history"></i> ' . sr_h(__('history', 'History')) . '</a>';
        }
        if (!in_array($status, ['completed', 'cancelled', 'rejected'], true)) {
            $menu[] = '<div class="dropdown-divider"></div><button type="button" class="dropdown-item text-danger" onclick="cancelVacationRequest(' . (int)$vac['id'] . ', ' . sr_h(json_encode((string)$vac['vac_type'])) . ', ' . sr_h(json_encode(format_safe_date($vac['start_date'] ?? null, 'M d, Y'))) . ')"><i class="mdi mdi-close"></i> ' . sr_h(__('cancel_request', 'Cancel Request')) . '</button>';
        }
        $primary = '<a class="sr-open-btn" href="vacation_report_details.php?id=' . (int)$vac['id'] . '&emp_id=' . urlencode((string)$vac['emp_id']) . '"><i class="mdi mdi-eye"></i> ' . sr_h(__('view', 'View')) . '</a>';

        $body .= '<tr>'
            . '<td><span class="sr-cell-title">' . sr_h($vac['vac_type']) . '</span>' . (!empty($vac['fly_type']) ? '<span class="sr-cell-sub">' . sr_h(ucfirst((string)$vac['fly_type'])) . '</span>' : '') . '</td>'
            . '<td data-order="' . sr_h($vac['start_date']) . '"><span class="sr-date">' . sr_h(format_safe_date($vac['start_date'] ?? null, 'd M Y')) . '<small>' . sr_h($DateConv->GregorianToHijri($vac['start_date'], $format)) . '</small></span></td>'
            . '<td data-order="' . sr_h($vac['return_date']) . '"><span class="sr-date">' . sr_h(format_safe_date($vac['return_date'] ?? null, 'd M Y')) . '<small>' . sr_h($DateConv->GregorianToHijri($vac['return_date'], $format)) . '</small></span></td>'
            . '<td data-order="' . (float)$vac['vacdays'] . '"><strong>' . number_format((float)$vac['vacdays'], 0) . '</strong> <span class="sr-cell-sub" style="display: inline;">' . sr_h(__('days', 'days')) . '</span></td>'
            . '<td>' . srh_status_pill($status, $status_label) . '</td>'
            . '<td data-order="' . sr_h($vac['created_at']) . '">' . sr_h(format_safe_date($vac['created_at'] ?? null, 'd M Y')) . '</td>'
            . '<td class="text-right no-print">' . srh_row_actions($primary, $menu) . '</td>'
            . '</tr>';
    }
    $body .= '</tbody></table></div>';
}
echo srh_card('mdi-white-balance-sunny', __('vacation_history', 'Vacation History'), $body, $rows ? srh_search_box() : '', 'sr-card-body p-0');

$cancel_js = <<<'JS'
<script>
    // Cancel vacation request
    function cancelVacationRequest(vacationId, vacType, startDate) {
        Swal.fire({
            title: __('cancel_vacation_request'),
            html: `<p>${__('are_you_sure_cancel_vacation', { vacType: vacType, startDate: startDate })}</p><p style="color: #dc3545; font-size: 12px; margin-top: 10px;">${__('this_action_cannot_be_undone')}</p>`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: __('yes_cancel_request'),
            cancelButtonText: __('keep_request'),
            allowOutsideClick: false,
            allowEscapeKey: false
        }).then((result) => {
            if (!result.isConfirmed) return;
            Swal.fire({
                title: __('cancelling_request'),
                html: __('please_wait_while_cancelling'),
                icon: 'info',
                allowOutsideClick: false,
                allowEscapeKey: false,
                didOpen: () => { Swal.showLoading(); }
            });
            $.ajax({
                url: './includes/ajaxFile/leaveHandler.php',
                type: 'POST',
                dataType: 'JSON',
                data: { ajaxType: 'cancelVacationRequest', vacation_id: vacationId },
                success: function (response) {
                    Swal.fire({
                        title: response.title || __('success'),
                        text: response.message || __('your_vacation_request_has_been_cancelled_successfully'),
                        icon: response.type || 'success',
                        confirmButtonText: __('ok'),
                        allowOutsideClick: false
                    }).then(() => { location.reload(); });
                },
                error: function (xhr) {
                    const response = xhr.responseJSON || {};
                    Swal.fire({
                        title: response.title || __('error'),
                        text: response.message || __('failed_to_cancel_vacation_request'),
                        icon: 'error',
                        confirmButtonText: __('ok')
                    });
                }
            });
        });
    }
</script>
JS;

srh_page_close('<script src="assets/js/employee_profile.js"></script>' . $cancel_js
    . srh_dt_js('.srh-dt', 1, 'desc', 'mdi-white-balance-sunny', 'srhSearch', [6]));
