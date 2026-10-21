<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../session_check.php';
require_once __DIR__ . '/../special_access_helper.php';
// Attendance switched off in App Settings > Integrations
if (!attendance_enabled($conDB)) {
    http_response_code(403);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['status' => 'error', 'message' => 'Attendance is turned off']);
    exit;
}
require_once __DIR__ . '/../attendance_helpers.php';

$canManage = !empty($is_system_admin) || user_has_special_access($conDB, $empid ?? '', 'manage_attendance', $user_role ?? '', $user_type ?? '', $is_system_admin ?? false);
if (!$canManage) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Access denied.']);
    exit;
}

$action = $_POST['action'] ?? '';

switch ($action) {
    case 'list_attendance':
        list_attendance($conDB);
        break;
    case 'add_edit_attendance':
        add_edit_attendance($conDB);
        break;
    case 'timetable_for':
        timetable_for($conDB);
        break;
    case 'list_transactions':
        list_transactions($conDB);
        break;
    default:
        echo json_encode(['status' => 'error', 'message' => 'Invalid action.']);
}

function list_attendance($conDB) {
    $draw = (int) ($_POST['draw'] ?? 1);
    $start = (int) ($_POST['start'] ?? 0);
    $length = (int) ($_POST['length'] ?? 10);
    $searchValue = trim((string) ($_POST['search']['value'] ?? ''));
    $empFilter = trim((string) ($_POST['emp_id'] ?? ''));
    $fromDate = trim((string) ($_POST['from_date'] ?? ''));
    $toDate = trim((string) ($_POST['to_date'] ?? ''));

    $where = ['1=1'];
    if ($empFilter !== '') {
        $where[] = "a.emp_id = " . (int) $empFilter;
    }
    $isDate = function ($v) { $d = DateTime::createFromFormat('Y-m-d', $v); return $d && $d->format('Y-m-d') === $v; };
    if ($fromDate !== '' && $isDate($fromDate)) {
        $where[] = "a.date >= '{$fromDate}'";
    }
    if ($toDate !== '' && $isDate($toDate)) {
        $where[] = "a.date <= '{$toDate} 23:59:59'";
    }
    if ($searchValue !== '') {
        $searchEsc = mysqli_real_escape_string($conDB, $searchValue);
        $where[] = "(e.name LIKE '%{$searchEsc}%' OR a.emp_id LIKE '%{$searchEsc}%' OR a.note LIKE '%{$searchEsc}%')";
    }
    // Status tiles: counts use the employee/date filter only, the list also applies the chosen tile + search.
    $stateGroups = [
        'present'    => "a.state = 'Present'",
        'late'       => "a.state LIKE 'Late%'",
        'early'      => "a.state LIKE '%Early Leave%'",
        'incomplete' => "a.state LIKE '%Incomplete%'",
        'dayoff'     => "a.state IN ('Day Off', 'Leave', 'Absent')",
    ];
    $countWhere = $where;
    foreach ($countWhere as $i => $cond) {
        if (strpos($cond, 'e.name LIKE') !== false) unset($countWhere[$i]);
    }
    $countSql = "COUNT(*) AS `all`";
    foreach ($stateGroups as $key => $cond) {
        $countSql .= ", SUM({$cond}) AS `{$key}`";
    }
    $countRes = mysqli_query($conDB, "SELECT {$countSql} FROM attendance a WHERE " . implode(' AND ', $countWhere));
    $counts = array_map('intval', $countRes ? (mysqli_fetch_assoc($countRes) ?: []) : []);

    $stateFilter = (string) ($_POST['state_group'] ?? '');
    if (isset($stateGroups[$stateFilter])) {
        $where[] = $stateGroups[$stateFilter];
    }
    $whereSql = implode(' AND ', $where);

    // attendance.emp_id is INT, employees.emp_id is a latin1 VARCHAR - the joins cast so the employees index is used.
    $totalResult = mysqli_query($conDB, "SELECT COUNT(*) AS cnt FROM attendance a");
    $totalRecords = (int) (mysqli_fetch_assoc($totalResult)['cnt'] ?? 0);

    $filteredResult = mysqli_query($conDB, "SELECT COUNT(*) AS cnt FROM attendance a LEFT JOIN employees e ON e.emp_id = CAST(a.emp_id AS CHAR CHARACTER SET latin1) WHERE {$whereSql}");
    $filteredRecords = (int) (mysqli_fetch_assoc($filteredResult)['cnt'] ?? 0);

    $rows = mysqli_query(
        $conDB,
        "SELECT a.id, a.emp_id, e.name, a.date, a.time_in, a.time_out, a.state, a.source, a.note
         FROM attendance a
         LEFT JOIN employees e ON e.emp_id = CAST(a.emp_id AS CHAR CHARACTER SET latin1)
         WHERE {$whereSql}
         ORDER BY a.date DESC, a.time_in DESC
         LIMIT {$start}, {$length}"
    );

    // "Total hours" tile (only when one employee is selected): worked time of every row matching the
    // current filters (dates + tile + search), not just this page. Same rule as the Employee Profile tab:
    // a day counts only when it has both punches and check-out is after check-in.
    $totalHours = null;
    if ($empFilter !== '') {
        $worked = "TIME_TO_SEC(a.time_out) - TIME_TO_SEC(a.time_in)";
        $hasBoth = "a.time_in <> '' AND a.time_out <> '' AND TIME_TO_SEC(a.time_out) > TIME_TO_SEC(a.time_in)";
        $th = mysqli_query($conDB, "SELECT COALESCE(SUM(IF({$hasBoth}, {$worked}, 0)), 0) AS secs, COALESCE(SUM({$hasBoth}), 0) AS days
             FROM attendance a LEFT JOIN employees e ON e.emp_id = CAST(a.emp_id AS CHAR CHARACTER SET latin1) WHERE {$whereSql}");
        $thRow = $th ? mysqli_fetch_assoc($th) : null;
        $totalHours = ['minutes' => (int) floor(((int) ($thRow['secs'] ?? 0)) / 60), 'days' => (int) ($thRow['days'] ?? 0)];
    }

    $data = [];
    while ($row = mysqli_fetch_assoc($rows)) {
        // Some rows written before the pairing fix (or a face-scan device
        // re-punching at the exact same second) have time_out == time_in -
        // that's never a real checkout, so treat it as missing here too.
        if (!empty($row['time_out']) && $row['time_out'] === $row['time_in']) {
            $row['time_out'] = '';
        }

        $empLabel = htmlspecialchars($row['emp_id'] . ' - ' . ($row['name'] ?? ''), ENT_QUOTES);
        $dateOnly = date('Y-m-d', strtotime($row['date']));
        $noteEsc = htmlspecialchars($row['note'] ?? '', ENT_QUOTES);

        $action = "
            <div class='btn-group dropdown'>
                <a href='javascript: void(0);' class='table-action-btn dropdown-toggle arrow-none btn btn-light btn-sm' data-toggle='dropdown' aria-expanded='false'><i class='mdi mdi-dots-horizontal'></i></a>
                <div class='dropdown-menu dropdown-menu-right'>
                    <a class='dropdown-item text-dark btn-edit-attendance' href='javascript:void(0);'
                        data-emp-id=\"{$row['emp_id']}\"
                        data-emp-label=\"{$empLabel}\"
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

        $data[] = [
            'emp_id' => $row['emp_id'],
            'name' => $row['name'] ?? '',
            'date' => $dateOnly,
            'time_in' => $row['time_in'],
            'time_out' => $row['time_out'],
            'state' => $row['state'],
            'source' => $row['source'],
            'note' => $row['note'],
            'action' => $action,
        ];
    }

    echo json_encode([
        'draw' => $draw,
        'recordsTotal' => $totalRecords,
        'recordsFiltered' => $filteredRecords,
        'data' => $data,
        'counts' => $counts,
        'total_hours' => $totalHours,
    ]);
}

// "Transactions" tab (attendance.php): every raw device punch from zk_attendance, newest first, with its
// position in that employee's day (1 of 3, ...) and the device it came from. Filters: employee, dates, search.
function list_transactions($conDB) {
    $draw = (int) ($_POST['draw'] ?? 1);
    $start = max(0, (int) ($_POST['start'] ?? 0));
    $length = (int) ($_POST['length'] ?? 25);
    if ($length < 1 || $length > 500) $length = 25;
    $searchValue = trim((string) ($_POST['search']['value'] ?? ''));
    $empFilter = trim((string) ($_POST['emp_id'] ?? ''));
    $fromDate = trim((string) ($_POST['from_date'] ?? ''));
    $toDate = trim((string) ($_POST['to_date'] ?? ''));
    $isDate = function ($v) { $d = DateTime::createFromFormat('Y-m-d', $v); return $d && $d->format('Y-m-d') === $v; };

    // punch_datetime ranges (not punch_date) so the idx_punch_datetime index serves both the filter and the order
    $where = ['1=1'];
    if ($empFilter !== '') {
        $where[] = "z.emp_id = '" . mysqli_real_escape_string($conDB, $empFilter) . "'";
    }
    if ($fromDate !== '' && $isDate($fromDate)) {
        $where[] = "z.punch_datetime >= '{$fromDate} 00:00:00'";
    }
    if ($toDate !== '' && $isDate($toDate)) {
        $where[] = "z.punch_datetime <= '{$toDate} 23:59:59'";
    }
    if ($searchValue !== '') {
        $s = mysqli_real_escape_string($conDB, $searchValue);
        $where[] = "(z.emp_name LIKE '%{$s}%' OR z.emp_id LIKE '%{$s}%' OR d.device_name LIKE '%{$s}%' OR d.area LIKE '%{$s}%')";
    }
    $whereSql = implode(' AND ', $where);
    $from = "FROM zk_attendance z LEFT JOIN zk_devices d ON d.serial_number = z.serial_number";

    $res = mysqli_query($conDB, "SELECT COUNT(*) AS cnt, COUNT(DISTINCT z.emp_id) AS emps, COUNT(DISTINCT z.emp_id, z.punch_date) AS days {$from} WHERE {$whereSql}");
    $sum = $res ? (mysqli_fetch_assoc($res) ?: []) : [];
    $filtered = (int) ($sum['cnt'] ?? 0);

    $rows = mysqli_query($conDB, "SELECT z.emp_id, z.emp_name, z.punch_date, z.punch_time, z.punch_state, z.serial_number, d.device_name, d.area
        {$from} WHERE {$whereSql} ORDER BY z.punch_datetime DESC, z.id DESC LIMIT {$start}, {$length}");

    $data = [];
    $days = []; // "emp|date" => true, for the per-day position below
    while ($rows && ($r = mysqli_fetch_assoc($rows))) {
        $data[] = $r;
        $days[$r['emp_id'] . '|' . $r['punch_date']] = [$r['emp_id'], $r['punch_date']];
    }

    // Position of each punch inside its employee's day - one indexed (emp_id, punch_date) lookup per day on this page
    $order = [];
    if ($days) {
        $stmt = mysqli_prepare($conDB, "SELECT punch_time FROM zk_attendance WHERE emp_id = ? AND punch_date = ? ORDER BY punch_time");
        foreach ($days as $key => $pair) {
            mysqli_stmt_bind_param($stmt, 'ss', $pair[0], $pair[1]);
            mysqli_stmt_execute($stmt);
            $rs = mysqli_stmt_get_result($stmt);
            $order[$key] = [];
            while ($p = mysqli_fetch_row($rs)) $order[$key][] = $p[0];
        }
        mysqli_stmt_close($stmt);
    }

    $out = [];
    foreach ($data as $r) {
        $times = $order[$r['emp_id'] . '|' . $r['punch_date']] ?? [];
        $pos = array_search($r['punch_time'], $times, true);
        $out[] = [
            'emp_id' => $r['emp_id'],
            'name' => $r['emp_name'],
            'date' => $r['punch_date'],
            'time' => $r['punch_time'],
            'state' => (string) $r['punch_state'],
            'device' => $r['device_name'] ?: $r['serial_number'],
            'area' => ($r['area'] && $r['area'] !== $r['device_name']) ? $r['area'] : '',
            'seq' => $pos === false ? null : $pos + 1,
            'of' => count($times),
        ];
    }

    echo json_encode([
        'draw' => $draw,
        'recordsTotal' => $filtered,
        'recordsFiltered' => $filtered,
        'data' => $out,
        'summary' => ['punches' => $filtered, 'employees' => (int) ($sum['emps'] ?? 0), 'days' => (int) ($sum['days'] ?? 0)],
    ]);
}

// Scheduled check-in / check-out for one employee on one day (resolved timetable) - used as the
// starting time of the Add/Edit record clock pickers (attendance.php, view_employee.php).
function timetable_for($conDB) {
    $empId = (int) ($_POST['emp_id'] ?? 0);
    $date = trim((string) ($_POST['date'] ?? ''));
    $d = DateTime::createFromFormat('Y-m-d', $date);
    if ($empId <= 0 || !$d || $d->format('Y-m-d') !== $date) {
        echo json_encode(['status' => 'error', 'message' => 'Employee and date are required.']);
        return;
    }
    $stmt = mysqli_prepare($conDB, "SELECT comp_no FROM employees WHERE emp_id = ? LIMIT 1");
    $empIdStr = (string) $empId;
    mysqli_stmt_bind_param($stmt, 's', $empIdStr);
    mysqli_stmt_execute($stmt);
    $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);
    $tt = attendance_resolve_timetable($conDB, $empId, $row['comp_no'] ?? '', $date);
    echo json_encode([
        'status' => 'success',
        'check_in' => substr((string) ($tt['check_in'] ?? '08:00'), 0, 5),
        'check_out' => substr((string) ($tt['check_out'] ?? '17:00'), 0, 5),
        'is_off' => !empty($tt['is_off']) ? 1 : 0,
    ]);
}

function add_edit_attendance($conDB) {
    $empId = (int) ($_POST['emp_id'] ?? 0);
    $date = trim((string) ($_POST['date'] ?? ''));
    $timeIn = trim((string) ($_POST['time_in'] ?? ''));
    $timeOut = trim((string) ($_POST['time_out'] ?? ''));
    $state = trim((string) ($_POST['state'] ?? 'Present'));
    $note = trim((string) ($_POST['note'] ?? ''));

    if ($empId <= 0 || $date === '' || ($timeIn === '' && $timeOut === '') || strtotime($date) === false) {
        echo json_encode(['status' => 'error', 'message' => 'Employee, date and at least one punch time are required.']);
        return;
    }

    $dateNormalized = date('Y-m-d', strtotime($date));

    // No row yet for this employee/day = a new manual record, which needs the 'add_manual_attendance' key.
    // Editing an existing row only needs 'manage_attendance' (already checked above).
    global $empid, $user_role, $user_type, $is_system_admin;
    $stmt = mysqli_prepare($conDB, "SELECT 1 FROM attendance WHERE emp_id = ? AND DATE(date) = ? LIMIT 1");
    mysqli_stmt_bind_param($stmt, 'is', $empId, $dateNormalized);
    mysqli_stmt_execute($stmt);
    $exists = (bool) mysqli_fetch_row(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);
    if (!$exists && !user_has_special_access($conDB, $empid ?? '', 'add_manual_attendance', $user_role ?? '', $user_type ?? '', !empty($is_system_admin))) {
        http_response_code(403);
        echo json_encode(['status' => 'error', 'message' => 'You do not have permission to add attendance records.']);
        return;
    }

    if (attendance_upsert_day($conDB, $empId, $dateNormalized, $timeIn, $timeOut, $state, $note, 'manual')) {
        echo json_encode(['status' => 'success', 'message' => 'Attendance record saved.']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to save attendance record.']);
    }
}
