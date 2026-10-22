<?php
/**
 * Employee Business Trip History Page
 * Displays all business trip requests for the logged-in employee
 */

require_once("./includes/session_check.php");
require_once __DIR__ . '/includes/sr_history_helpers.php';
include('./includes/MainClass.php');
include("./includes/Hijri_GregorianConvert.php");
$DateConv = new Hijri_GregorianConvert;
$format = "YYYY-MM-DD";

// ?emp_id=X opens another employee's records (from their profile) - access-checked in profile_target.php.
require_once __DIR__ . '/includes/profile_target.php';
$emp_id = $profile_target_empid;
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

$trip_query = "SELECT bt.id, bt.request_inv_no, bt.trip_type, bt.transportation_type, bt.destination_country, bt.travel_email_sent,
                      bt.trip_start_date, bt.trip_end_date, bt.current_status, bt.created_at, bt.emp_id,
                      fc.name_en AS from_city_name_en, fc.name_ar AS from_city_name_ar,
                      tc.name_en AS to_city_name_en, tc.name_ar AS to_city_name_ar,
                      (CASE WHEN ba.id IS NOT NULL THEN 1 ELSE 0 END) as has_allowance_record
               FROM emp_business_trip bt
               LEFT JOIN saudi_cities fc ON bt.from_city_id = fc.id
               LEFT JOIN saudi_cities tc ON bt.to_city_id = tc.id
               LEFT JOIN emp_business_trip_allowances ba ON ba.trip_id = bt.id
               WHERE bt.emp_id = ?
               ORDER BY bt.created_at DESC";
$stmt = $conDB->prepare($trip_query);
$stmt->bind_param("s", $emp_id);
$stmt->execute();
$trip_result = $stmt->get_result();

$rows = [];
while ($trip = $trip_result->fetch_assoc()) $rows[] = $trip;
$count_by = ['approved' => 0, 'pending' => 0, 'rejected' => 0];
$total_days = 0;
foreach ($rows as $r) {
    $st = (string)($r['current_status'] ?? '');
    if ($st === 'approved' || $st === 'completed') {
        $count_by['approved']++;
        $d1 = date_create((string)$r['trip_start_date']);
        $d2 = date_create((string)$r['trip_end_date']);
        if ($d1 && $d2) $total_days += $d1->diff($d2)->days + 1;
    } elseif ($st === 'rejected') $count_by['rejected']++;
    elseif (strpos($st, 'pending') !== false) $count_by['pending']++;
}

srh_page_open(__('business_trip_history', 'Business Trip History'), srh_portal_head());
echo srh_header(__('business_trip_history', 'Business Trip History'), '', srh_profile_url($emprow));
echo srh_emp_hero($emprow);
echo srh_tiles([
    [__('total_requests', 'Total Requests'), count($rows), 'indigo', ''],
    [__('approved', 'Approved'), $count_by['approved'], 'green', $total_days . ' ' . sr_h(__('days', 'days'))],
    [__('pending', 'Pending'), $count_by['pending'], 'amber', ''],
    [__('rejected', 'Rejected'), $count_by['rejected'], $count_by['rejected'] ? 'red' : '', ''],
]);

if (!$rows) {
    $body = sr_empty_state(__('no_business_trip_requests_found', 'No business trip history found for this employee.'));
} else {
    $body = '<div class="sr-table-wrap"><table class="sr-table srh-dt"><thead><tr>'
        . '<th>' . sr_h(__('request_id', 'Request ID')) . '</th>'
        . '<th>' . sr_h(__('destination', 'Destination')) . '</th>'
        . '<th>' . sr_h(__('transportation_type', 'Transportation')) . '</th>'
        . '<th>' . sr_h(__('trip_dates', 'Trip Dates')) . '</th>'
        . '<th>' . sr_h(__('status', 'Status')) . '</th>'
        . '<th>' . sr_h(__('applied_date', 'Applied Date')) . '</th>'
        . '<th class="text-right no-print">' . sr_h(__('action', 'Action')) . '</th>'
        . '</tr></thead><tbody>';
    foreach ($rows as $trip) {
        if (($trip['trip_type'] ?? '') === 'international') {
            $destination_text = $trip['destination_country'] ?? '-';
        } else {
            $from_city = ($is_rtl ?? false) ? ($trip['from_city_name_ar'] ?? '') : ($trip['from_city_name_en'] ?? '');
            $to_city = ($is_rtl ?? false) ? ($trip['to_city_name_ar'] ?? '') : ($trip['to_city_name_en'] ?? '');
            $destination_text = trim((string)$from_city) . ' → ' . trim((string)$to_city);
        }
        $status = (string)($trip['current_status'] ?? 'pending_approval');

        $menu = ['<a class="dropdown-item" href="business_trip_status_history.php?request_inv_no=' . urlencode((string)$trip['request_inv_no']) . '" target="_blank"><i class="mdi mdi-history"></i> ' . sr_h(__('history', 'History')) . '</a>'];
        if ($status === 'approved' && ($trip['transportation_type'] ?? '') === 'by_air' && !empty($trip['travel_email_sent']) && empty($trip['has_allowance_record'])) {
            $menu[] = '<div class="dropdown-divider"></div><button type="button" class="dropdown-item" onclick="openBusinessTripAllowanceModal(' . (int)$trip['id'] . ', ' . sr_h(json_encode((string)($emprow['name'] ?? ''))) . ')"><i class="mdi mdi-cash" style="color: var(--tone-green-fg);"></i> ' . sr_h(__('add_ticket_amount', 'Add Ticket Amount')) . '</button>';
        }
        if (!in_array($status, ['rejected', 'completed', 'cancelled'], true)) {
            $menu[] = '<div class="dropdown-divider"></div><button type="button" class="dropdown-item text-danger" onclick="cancelBusinessTripRequest(' . sr_h(json_encode((string)$trip['request_inv_no'])) . ')"><i class="mdi mdi-close"></i> ' . sr_h(__('cancel_request', 'Cancel Request')) . '</button>';
        }
        $primary = '<a class="sr-open-btn" href="business_trip_report_details.php?id=' . (int)$trip['id'] . '&emp_id=' . urlencode((string)($trip['emp_id'] ?? $emp_id)) . '" target="_blank"><i class="mdi mdi-eye"></i> ' . sr_h(__('view_report', 'View Report')) . '</a>';

        $body .= '<tr>'
            . '<td><span class="sr-mono">' . sr_h($trip['request_inv_no']) . '</span><span class="sr-cell-sub">' . sr_h(__($trip['trip_type'] ?? 'domestic')) . '</span></td>'
            . '<td><span class="sr-cell-title">' . sr_h($destination_text) . '</span></td>'
            . '<td>' . sr_h(__($trip['transportation_type'] ?? '-')) . '</td>'
            . '<td data-order="' . sr_h($trip['trip_start_date']) . '"><span class="sr-date">' . sr_h(format_safe_date((string)$trip['trip_start_date'], 'd M Y'))
                . '<small>' . sr_h(__('to', 'to')) . ' ' . sr_h(format_safe_date((string)$trip['trip_end_date'], 'd M Y')) . '</small></span></td>'
            . '<td>' . srh_status_pill($status, __($status)) . '</td>'
            . '<td data-order="' . sr_h($trip['created_at']) . '"><span class="sr-date">' . sr_h(format_safe_date((string)$trip['created_at'], 'd M Y'))
                . '<small>' . sr_h($DateConv->GregorianToHijri($trip['created_at'], $format)) . '</small></span></td>'
            . '<td class="text-right no-print">' . srh_row_actions($primary, $menu) . '</td>'
            . '</tr>';
    }
    $body .= '</tbody></table></div>';
}
echo srh_card('mdi-airplane', __('business_trip_history', 'Business Trip History'), $body, $rows ? srh_search_box() : '', 'sr-card-body p-0');

srh_page_close('<script src="assets/js/employee_profile.js"></script><script src="assets/js/businessTrip.js?t=' . time() . '"></script>'
    . srh_dt_js('.srh-dt', 5, 'desc', 'mdi-airplane', 'srhSearch', [6]));
