<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../session_check.php';
require_once __DIR__ . '/../special_access_helper.php';
require_once __DIR__ . '/../attendance_helpers.php';

$canViewAttendanceTab = ($is_system_admin ?? false)
    || user_has_special_access($conDB, $empid ?? '', 'view_employee_attendance_tab', $user_role ?? '', $user_type ?? '', $is_system_admin ?? false);
if (!$canViewAttendanceTab) {
    http_response_code(403);
    echo json_encode(['draw' => 1, 'recordsTotal' => 0, 'recordsFiltered' => 0, 'data' => []]);
    exit;
}

$canManageAttendanceRecord = ($is_system_admin ?? false)
    || user_has_special_access($conDB, $empid ?? '', 'manage_attendance', $user_role ?? '', $user_type ?? '', $is_system_admin ?? false);

$empId = (int) ($_POST['emp_id'] ?? 0);
$draw = (int) ($_POST['draw'] ?? 1);
$fromDate = trim((string) ($_POST['fromdate'] ?? ''));
$toDate = trim((string) ($_POST['todate'] ?? ''));

if ($empId <= 0) {
    echo json_encode(['draw' => $draw, 'recordsTotal' => 0, 'recordsFiltered' => 0, 'data' => []]);
    exit;
}

$dateFilter = '';
if ($fromDate !== '' && $toDate !== '') {
    $fromEsc = mysqli_real_escape_string($conDB, $fromDate);
    $toEsc = mysqli_real_escape_string($conDB, $toDate);
    $dateFilter = " AND (date BETWEEN '{$fromEsc}' AND '{$toEsc} 23:59:59')";
}

$totalResult = mysqli_query($conDB, "SELECT COUNT(*) AS cnt FROM attendance WHERE emp_id = {$empId}");
$totalRecords = (int) (mysqli_fetch_assoc($totalResult)['cnt'] ?? 0);

$filteredResult = mysqli_query($conDB, "SELECT COUNT(*) AS cnt FROM attendance WHERE emp_id = {$empId}{$dateFilter}");
$filteredRecords = (int) (mysqli_fetch_assoc($filteredResult)['cnt'] ?? 0);

$compNoResult = mysqli_query($conDB, "SELECT comp_no FROM employees WHERE emp_id = {$empId} LIMIT 1");
$compNo = $compNoResult ? (mysqli_fetch_assoc($compNoResult)['comp_no'] ?? '') : '';

// Returns every matching row (no LIMIT) - this table runs client-side (serverSide:
// false) so its Buttons export can include the full filtered history, not just
// whatever page happens to be on screen (DataTables' server-side mode can't export
// beyond the current page). Per-employee history is small enough this is cheap.
$rows = mysqli_query(
    $conDB,
    "SELECT id, date, time_in, time_out, state, note
     FROM attendance
     WHERE emp_id = {$empId}{$dateFilter}
     ORDER BY date DESC"
);

$data = [];
while ($row = mysqli_fetch_assoc($rows)) {
    $dateOnly = date('Y-m-d', strtotime($row['date']));

    // Some rows written before the pairing fix (or a face-scan device
    // re-punching at the exact same second) have time_out == time_in - that's
    // never a real checkout, so treat it as missing here too rather than
    // showing a fake 0-hour day.
    if (!empty($row['time_out']) && $row['time_out'] === $row['time_in']) {
        $row['time_out'] = '';
    }

    $hours = '';
    if (!empty($row['time_in']) && !empty($row['time_out'])) {
        $in = strtotime($row['time_in']);
        $out = strtotime($row['time_out']);
        if ($in !== false && $out !== false && $out >= $in) {
            $hours = gmdate('H:i', $out - $in);
        }
    }

    // Late/Early-Leave minutes measured against the resolved timetable's grace
    // boundaries (check_in_end / check_out_start) - the same cutoffs
    // attendance_derive_state() uses to decide Late/Early Leave in the first
    // place, so the minutes shown here match what actually triggered the flag
    // (e.g. check-in allowed until 8:15 - a 8:20 check-in is "5 min late", not
    // measured from the 8:00 scheduled start). None shown on a Day Off - the
    // employee isn't expected in, so there's nothing to be late/early against.
    $lateMinutes = '-';
    $earlyMinutes = '-';
    $timetable = attendance_resolve_timetable($conDB, $empId, $compNo, $dateOnly);
    if (empty($timetable['is_off'])) {
        if (!empty($row['time_in']) && $row['time_in'] > $timetable['check_in_end']) {
            $lateMinutes = (int) round((strtotime($row['time_in']) - strtotime($timetable['check_in_end'])) / 60);
        }
        if (!empty($row['time_out']) && $row['time_out'] < $timetable['check_out_start']) {
            $earlyMinutes = (int) round((strtotime($timetable['check_out_start']) - strtotime($row['time_out'])) / 60);
        }
    }

    $entry = [
        'date' => $dateOnly,
        'check_in' => $row['time_in'] ?: '-',
        'check_out' => $row['time_out'] ?: '-',
        'hours' => $hours ?: '-',
        'state' => $row['state'],
        'late_minutes' => $lateMinutes,
        'early_minutes' => $earlyMinutes,
        'note' => $row['note'] ?: '',
    ];

    if ($canManageAttendanceRecord) {
        $noteEsc = htmlspecialchars($row['note'] ?? '', ENT_QUOTES);
        $entry['action'] = "
            <div class='btn-group dropdown'>
                <a href='javascript: void(0);' class='table-action-btn dropdown-toggle arrow-none btn btn-light btn-sm' data-toggle='dropdown' aria-expanded='false'><i class='mdi mdi-dots-horizontal'></i></a>
                <div class='dropdown-menu dropdown-menu-right'>
                    <a class='dropdown-item text-dark btn-edit-attendance-record' href='javascript:void(0);'
                        data-date=\"{$dateOnly}\"
                        data-time-in=\"{$row['time_in']}\"
                        data-time-out=\"{$row['time_out']}\"
                        data-state=\"" . htmlspecialchars($row['state'], ENT_QUOTES) . "\"
                        data-note=\"{$noteEsc}\">
                        <i class='mdi mdi-pencil mr-2'></i>Edit
                    </a>
                    <a class='dropdown-item text-danger deleteAjax' href='javascript:void(0);' data-id='{$row['id']}' data-tbl='attendance' data-file='0'>
                        <i class='fa fa-trash mr-2'></i>Delete
                    </a>
                </div>
            </div>
        ";
    }

    $data[] = $entry;
}

echo json_encode([
    'draw' => $draw,
    'recordsTotal' => $totalRecords,
    'recordsFiltered' => $filteredRecords,
    'data' => $data,
]);
