<?php
/**
 * Employee Attendance History Page
 * Read-only daily check-in / check-out of the logged-in employee, one month at a time.
 * Opened from profile.php > Employee records.
 */

require_once("./includes/session_check.php");
require_once __DIR__ . '/includes/sr_history_helpers.php';
require_once __DIR__ . '/includes/attendance_helpers.php';

if (!attendance_enabled($conDB)) {
    header("Location: ./profile.php");
    exit();
}

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

// ?month=YYYY-MM, defaults to the current month; never past the current month.
$thisMonth = date('Y-m');
$month = (string)($_GET['month'] ?? $thisMonth);
if (!preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $month) || $month > $thisMonth) {
    $month = $thisMonth;
}
$from = $month . '-01';
$to = date('Y-m-t', strtotime($from));
$prevMonth = date('Y-m', strtotime($from . ' -1 month'));
$nextMonth = date('Y-m', strtotime($from . ' +1 month'));

$empIdInt = (int)$emp_id;
$stmt = $conDB->prepare("SELECT date, time_in, time_out, state, note FROM attendance
                         WHERE emp_id = ? AND date >= ? AND date <= ?
                         ORDER BY date DESC");
$toEnd = $to . ' 23:59:59';
$stmt->bind_param("iss", $empIdInt, $from, $toEnd);
$stmt->execute();
$att_result = $stmt->get_result();

$rows = [];
$count_by = ['present' => 0, 'late_early' => 0, 'incomplete' => 0];
while ($r = $att_result->fetch_assoc()) {
    $dateOnly = date('Y-m-d', strtotime($r['date']));
    // time_out == time_in is never a real checkout (device re-punch) - treat as missing
    if (!empty($r['time_out']) && $r['time_out'] === $r['time_in']) {
        $r['time_out'] = '';
    }

    $hours = '';
    if (!empty($r['time_in']) && !empty($r['time_out'])) {
        $in = strtotime($r['time_in']);
        $out = strtotime($r['time_out']);
        if ($in !== false && $out !== false && $out >= $in) {
            $hours = gmdate('H:i', $out - $in);
        }
    }

    // Late / early minutes against the timetable grace boundaries (same as the Employee Profile attendance tab)
    $late = '';
    $early = '';
    $timetable = attendance_resolve_timetable($conDB, $empIdInt, $emprow['comp_no'] ?? '', $dateOnly);
    if (empty($timetable['is_off'])) {
        if (!empty($r['time_in']) && $r['time_in'] > $timetable['check_in_end']) {
            $late = (int)round((strtotime($r['time_in']) - strtotime($timetable['check_in_end'])) / 60);
        }
        if (!empty($r['time_out']) && $r['time_out'] < $timetable['check_out_start']) {
            $early = (int)round((strtotime($timetable['check_out_start']) - strtotime($r['time_out'])) / 60);
        }
    }

    $state = (string)$r['state'];
    if ($state === 'Present') $count_by['present']++;
    if (strpos($state, 'Incomplete') !== false || $state === 'Absent') $count_by['incomplete']++;
    elseif (strpos($state, 'Late') === 0 || strpos($state, 'Early Leave') !== false) $count_by['late_early']++;

    $rows[] = ['date' => $dateOnly, 'time_in' => $r['time_in'], 'time_out' => $r['time_out'], 'hours' => $hours,
               'late' => $late, 'early' => $early, 'state' => $state, 'note' => (string)($r['note'] ?? '')];
}

function att_state_tone($s)
{
    if ($s === 'Present') return 'green';
    if (strpos($s, 'Incomplete') !== false || $s === 'Absent') return 'red';
    if (strpos($s, 'Late') === 0) return 'amber';
    if (strpos($s, 'Early Leave') !== false || $s === 'Half Day') return 'indigo';
    return 'slate';
}

$monthLabel = date('F Y', strtotime($from));
$navBtn = function ($m, $icon, $label, $disabled = false) {
    return $disabled
        ? '<span class="sr-btn sr-btn-ghost" style="opacity:.45; pointer-events:none;"><i class="mdi ' . $icon . '"></i></span>'
        : '<a class="sr-btn sr-btn-ghost" href="?month=' . sr_h($m) . '" title="' . sr_h($label) . '"><i class="mdi ' . $icon . '"></i></a>';
};
$monthNav = '<div style="display:inline-flex; align-items:center; gap:8px;">'
    . $navBtn($prevMonth, 'mdi-chevron-left', __('previous', 'Previous'))
    . '<b style="min-width:120px; text-align:center;">' . sr_h($monthLabel) . '</b>'
    . $navBtn($nextMonth, 'mdi-chevron-right', __('next', 'Next'), $month >= $thisMonth)
    . '</div>';

srh_page_open(__('attendance_history', 'Attendance History'), srh_dt_head());
echo srh_header(__('attendance_history', 'Attendance History'), '', srh_profile_url($emprow), $monthNav);
echo srh_emp_hero($emprow);
echo srh_tiles([
    [__('days_recorded', 'Days Recorded'), count($rows), 'indigo', sr_h($monthLabel)],
    [__('present', 'Present'), $count_by['present'], 'green', ''],
    [__('late_early_leave', 'Late / Early Leave'), $count_by['late_early'], $count_by['late_early'] ? 'amber' : '', ''],
    [__('incomplete_absent', 'Incomplete / Absent'), $count_by['incomplete'], $count_by['incomplete'] ? 'red' : '', ''],
]);

if (!$rows) {
    $body = sr_empty_state(__('no_attendance_records_found', 'No attendance records for this month'));
} else {
    $body = '<div class="sr-table-wrap"><table class="sr-table srh-dt"><thead><tr>'
        . '<th>' . sr_h(__('date', 'Date')) . '</th>'
        . '<th>' . sr_h(__('check_in', 'Check In')) . '</th>'
        . '<th>' . sr_h(__('check_out', 'Check Out')) . '</th>'
        . '<th>' . sr_h(__('hours', 'Hours')) . '</th>'
        . '<th>' . sr_h(__('late_minutes', 'Late (min)')) . '</th>'
        . '<th>' . sr_h(__('early_minutes', 'Early (min)')) . '</th>'
        . '<th>' . sr_h(__('state', 'State')) . '</th>'
        . '<th>' . sr_h(__('note', 'Note')) . '</th>'
        . '</tr></thead><tbody>';
    $dash = '<span class="text-muted">&ndash;</span>';
    foreach ($rows as $r) {
        $stateKey = strtolower(str_replace([' & ', ' '], ['_and_', '_'], $r['state']));
        $body .= '<tr>'
            . '<td data-order="' . sr_h($r['date']) . '"><span class="sr-date">' . sr_h(date('d M Y', strtotime($r['date'])))
                . '<small>' . sr_h(date('l', strtotime($r['date']))) . '</small></span></td>'
            . '<td>' . ($r['time_in'] ? '<span class="sr-mono">' . sr_h($r['time_in']) . '</span>' : $dash) . '</td>'
            . '<td>' . ($r['time_out'] ? '<span class="sr-mono">' . sr_h($r['time_out']) . '</span>' : $dash) . '</td>'
            . '<td>' . ($r['hours'] !== '' ? '<span class="sr-mono">' . sr_h($r['hours']) . '</span>' : $dash) . '</td>'
            . '<td>' . ($r['late'] !== '' ? '<span style="color: var(--tone-amber-fg); font-weight: 600;">' . (int)$r['late'] . '</span>' : $dash) . '</td>'
            . '<td>' . ($r['early'] !== '' ? '<span style="color: var(--tone-indigo-fg); font-weight: 600;">' . (int)$r['early'] . '</span>' : $dash) . '</td>'
            . '<td>' . sr_pill(att_state_tone($r['state']), __($stateKey, $r['state']), true) . '</td>'
            . '<td>' . ($r['note'] !== '' ? sr_h($r['note']) : $dash) . '</td>'
            . '</tr>';
    }
    $body .= '</tbody></table></div>';
}
echo srh_card('mdi-fingerprint', __('attendance_history', 'Attendance History'), $body, $rows ? srh_search_box() : '', 'sr-card-body p-0');

srh_page_close(srh_dt_js('.srh-dt', 0, 'desc', 'mdi-fingerprint', 'srhSearch', [1, 2, 3, 6, 7]));
