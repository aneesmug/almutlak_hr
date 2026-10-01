<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../session_check.php';
require_once __DIR__ . '/../special_access_helper.php';
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

    if (attendance_upsert_day($conDB, $empId, $dateNormalized, $timeIn, $timeOut, $state, $note, 'manual')) {
        echo json_encode(['status' => 'success', 'message' => 'Attendance record saved.']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to save attendance record.']);
    }
}
