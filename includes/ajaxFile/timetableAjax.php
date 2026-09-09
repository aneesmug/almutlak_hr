<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../session_check.php';
require_once __DIR__ . '/../special_access_helper.php';
require_once __DIR__ . '/../attendance_helpers.php';

$canManage = !empty($is_system_admin) || user_has_special_access($conDB, $empid ?? '', 'manage_attendance_config', $user_role ?? '', $user_type ?? '', $is_system_admin ?? false);
if (!$canManage) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Access denied.']);
    exit;
}

const TIMETABLE_TIME_FIELDS = ['check_in', 'check_in_start', 'check_in_end', 'check_out', 'check_out_start', 'check_out_end'];
const TIMETABLE_WEEKDAYS = [1, 2, 3, 4, 5, 6, 7]; // ISO: 1=Mon..7=Sun

$action = $_POST['action'] ?? '';

switch ($action) {
    case 'list_timetables':
        list_timetables($conDB);
        break;
    case 'add_edit_timetable':
        add_edit_timetable($conDB);
        break;
    case 'delete_timetable':
        delete_timetable($conDB);
        break;
    case 'toggle_timetable_active':
        toggle_timetable_active($conDB);
        break;
    case 'list_companies':
        list_companies($conDB);
        break;
    case 'search_employees':
        search_employees($conDB);
        break;
    default:
        echo json_encode(['status' => 'error', 'message' => 'Invalid action.']);
}

function list_timetables($conDB) {
    $result = mysqli_query(
        $conDB,
        "SELECT t.*, GROUP_CONCAT(DISTINCT c.comp_name ORDER BY c.comp_name SEPARATOR ', ') AS companies
         FROM timetables t
         LEFT JOIN companies c ON c.timetable_id = t.id
         GROUP BY t.id
         ORDER BY (t.id = 1) DESC, t.is_temporary ASC, t.name ASC"
    );
    $timetables = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $row['employees'] = [];
        $row['days'] = [];
        $dayStmt = mysqli_prepare($conDB, "SELECT * FROM timetable_days WHERE timetable_id = ? ORDER BY weekday ASC");
        $timetableIdForDays = (int) $row['id'];
        mysqli_stmt_bind_param($dayStmt, 'i', $timetableIdForDays);
        mysqli_stmt_execute($dayStmt);
        $dayResult = mysqli_stmt_get_result($dayStmt);
        while ($dayRow = mysqli_fetch_assoc($dayResult)) {
            $row['days'][(int) $dayRow['weekday']] = $dayRow;
        }
        mysqli_stmt_close($dayStmt);

        // Structured company list (in addition to the flattened 'companies'
        // string above) so the UI can render each name as its own chip
        // instead of one run-on comma-separated line - company names can
        // themselves contain slashes/commas, making the flattened string
        // unreliable to split back apart on the client.
        $row['company_list'] = [];
        $compStmt = mysqli_prepare($conDB, "SELECT comp_id, comp_name FROM companies WHERE timetable_id = ? ORDER BY comp_name ASC");
        $timetableIdForComps = (int) $row['id'];
        mysqli_stmt_bind_param($compStmt, 'i', $timetableIdForComps);
        mysqli_stmt_execute($compStmt);
        $compResult = mysqli_stmt_get_result($compStmt);
        while ($compRow = mysqli_fetch_assoc($compResult)) {
            $row['company_list'][] = $compRow;
        }
        mysqli_stmt_close($compStmt);

        if ((int) $row['is_temporary'] === 1) {
            $empStmt = mysqli_prepare(
                $conDB,
                "SELECT e.emp_id, e.name FROM timetable_employees te
                 JOIN employees e ON e.emp_id = te.emp_id
                 WHERE te.timetable_id = ? ORDER BY e.name ASC"
            );
            $timetableId = (int) $row['id'];
            mysqli_stmt_bind_param($empStmt, 'i', $timetableId);
            mysqli_stmt_execute($empStmt);
            $empResult = mysqli_stmt_get_result($empStmt);
            while ($empRow = mysqli_fetch_assoc($empResult)) {
                $empRow['name'] = parseName($empRow['name']);
                $row['employees'][] = $empRow;
            }
            mysqli_stmt_close($empStmt);
        }
        $timetables[] = $row;
    }
    echo json_encode(['status' => 'success', 'timetables' => $timetables]);
}

function list_companies($conDB) {
    $result = mysqli_query($conDB, "SELECT comp_id, comp_name, timetable_id FROM companies ORDER BY comp_name ASC");
    $companies = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $companies[] = $row;
    }
    echo json_encode(['status' => 'success', 'companies' => $companies]);
}

function search_employees($conDB) {
    $search = trim((string) ($_POST['search'] ?? ''));
    $like = "%{$search}%";
    $stmt = mysqli_prepare(
        $conDB,
        "SELECT emp_id AS id, name FROM employees
         WHERE status = 1 AND (name LIKE ? OR emp_id LIKE ?) ORDER BY name ASC LIMIT 30"
    );
    mysqli_stmt_bind_param($stmt, 'ss', $like, $like);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $employees = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $employees[] = ['id' => $row['id'], 'text' => $row['id'] . ' - ' . parseName($row['name'])];
    }
    mysqli_stmt_close($stmt);
    echo json_encode(['status' => 'success', 'results' => $employees]);
}

function add_edit_timetable($conDB) {
    $id = (int) ($_POST['id'] ?? 0);
    $name = trim((string) ($_POST['name'] ?? ''));
    $isTemporary = !empty($_POST['is_temporary']) && $id !== 1; // Default can never be temporary
    $companyIdsRaw = $isTemporary ? [] : ($_POST['company_ids'] ?? []);
    $employeeIdsRaw = $isTemporary ? ($_POST['employee_ids'] ?? []) : [];
    $startDate = trim((string) ($_POST['start_date'] ?? ''));
    $endDate = trim((string) ($_POST['end_date'] ?? ''));

    if ($name === '') {
        echo json_encode(['status' => 'error', 'message' => 'Name is required.']);
        return;
    }

    // One schedule row per ISO weekday (1=Mon..7=Sun), sent as a JSON object
    // keyed by weekday - lets a single timetable run different hours (or be
    // off) on different days, e.g. a short Thursday + Friday off + normal
    // Sat-Wed, instead of one fixed schedule for every working day.
    $daysRaw = json_decode((string) ($_POST['days'] ?? ''), true);
    if (!is_array($daysRaw)) {
        echo json_encode(['status' => 'error', 'message' => 'Missing weekly schedule.']);
        return;
    }
    $days = [];
    foreach (TIMETABLE_WEEKDAYS as $weekday) {
        $d = $daysRaw[$weekday] ?? $daysRaw[(string) $weekday] ?? null;
        if (!is_array($d)) {
            echo json_encode(['status' => 'error', 'message' => "Missing schedule for weekday {$weekday}."]);
            return;
        }
        $isOff = !empty($d['is_off']);
        $dayTimes = [];
        if (!$isOff) {
            foreach (TIMETABLE_TIME_FIELDS as $field) {
                $value = trim((string) ($d[$field] ?? ''));
                if (!preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $value)) {
                    echo json_encode(['status' => 'error', 'message' => "Invalid time for weekday {$weekday} {$field}: must be HH:MM (24-hour)."]);
                    return;
                }
                $dayTimes[$field] = $value;
            }
            $standardHours = trim((string) ($d['standard_hours'] ?? ''));
            if (!is_numeric($standardHours) || (float) $standardHours <= 0) {
                echo json_encode(['status' => 'error', 'message' => "Standard hours for weekday {$weekday} must be a positive number."]);
                return;
            }
        } else {
            // Off day - times/hours are irrelevant, keep the schema's defaults.
            foreach (TIMETABLE_TIME_FIELDS as $field) {
                $dayTimes[$field] = '00:00';
            }
            $standardHours = '0';
        }
        $days[$weekday] = ['is_off' => $isOff ? 1 : 0, 'standard_hours' => $standardHours] + $dayTimes;
    }

    $companyIds = array_values(array_unique(array_filter(array_map('intval', (array) $companyIdsRaw))));
    $employeeIds = array_values(array_unique(array_filter(array_map('intval', (array) $employeeIdsRaw))));

    if ($isTemporary) {
        // Employee-wise assignment: the date range is optional - both blank
        // means the override is always active (permanent), matching the
        // employee out of their company's schedule for good. Set both to
        // limit it to a window instead.
        if ($startDate !== '' || $endDate !== '') {
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $startDate) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $endDate)) {
                echo json_encode(['status' => 'error', 'message' => 'Provide both a start and end date, or leave both blank for a permanent assignment.']);
                return;
            }
            if ($startDate > $endDate) {
                echo json_encode(['status' => 'error', 'message' => 'Start date must be before or equal to the end date.']);
                return;
            }
        } else {
            $startDate = '';
            $endDate = '';
        }
        if (empty($employeeIds)) {
            echo json_encode(['status' => 'error', 'message' => 'Select at least one employee for an employee-wise timetable.']);
            return;
        }
    } else {
        // A company already locked to a different custom (non-Default) *active*
        // timetable can't be silently stolen from this screen - it has to be
        // unassigned there first. Guards the case where the UI's disabled
        // checkboxes were bypassed or the assignment changed elsewhere since
        // the page loaded. An inactive/draft timetable doesn't govern that
        // company's attendance right now, so it isn't locked - claiming it
        // here just leaves the other (inactive) timetable's own company list
        // stale until someone edits it, which is harmless since it's not live.
        if (!empty($companyIds)) {
            $placeholders = implode(',', array_fill(0, count($companyIds), '?'));
            $types = str_repeat('i', count($companyIds));
            $lockStmt = mysqli_prepare(
                $conDB,
                "SELECT c.comp_name, t.name AS timetable_name FROM companies c
                 JOIN timetables t ON t.id = c.timetable_id
                 WHERE c.comp_id IN ({$placeholders}) AND c.timetable_id IS NOT NULL
                   AND c.timetable_id != 1 AND c.timetable_id != ? AND t.is_active = 1"
            );
            mysqli_stmt_bind_param($lockStmt, $types . 'i', ...array_merge($companyIds, [$id]));
            mysqli_stmt_execute($lockStmt);
            $lockedRow = mysqli_stmt_get_result($lockStmt)->fetch_assoc();
            mysqli_stmt_close($lockStmt);
            if ($lockedRow) {
                echo json_encode(['status' => 'error', 'message' => "{$lockedRow['comp_name']} is already assigned to \"{$lockedRow['timetable_name']}\" - remove it there first."]);
                return;
            }
        }
    }

    if ($id === 1 && $name !== 'Default') {
        // Default's name is permanent (fallback shown throughout the UI as "Default") - its
        // hours/days-off/company list are still fully editable.
        $name = 'Default';
    }

    // Employees affected by this save (before the change) get their stored
    // attendance.state recalculated afterward - see attendance_recalculate_states().
    $affectedEmpIds = [];
    if ($id > 0) {
        $priorEmpStmt = mysqli_prepare($conDB, "SELECT emp_id FROM timetable_employees WHERE timetable_id = ?");
        mysqli_stmt_bind_param($priorEmpStmt, 'i', $id);
        mysqli_stmt_execute($priorEmpStmt);
        $priorEmpResult = mysqli_stmt_get_result($priorEmpStmt);
        while ($row = mysqli_fetch_assoc($priorEmpResult)) {
            $affectedEmpIds[] = (int) $row['emp_id'];
        }
        mysqli_stmt_close($priorEmpStmt);

        $priorCompStmt = mysqli_prepare($conDB, "SELECT comp_id FROM companies WHERE timetable_id = ?");
        mysqli_stmt_bind_param($priorCompStmt, 'i', $id);
        mysqli_stmt_execute($priorCompStmt);
        $priorCompResult = mysqli_stmt_get_result($priorCompStmt);
        $priorCompIds = [];
        while ($row = mysqli_fetch_assoc($priorCompResult)) {
            $priorCompIds[] = (int) $row['comp_id'];
        }
        mysqli_stmt_close($priorCompStmt);
        if (!empty($priorCompIds)) {
            $affectedEmpIds = array_merge($affectedEmpIds, employees_of_companies($conDB, $priorCompIds));
        }
    }

    if ($id > 0) {
        $stmt = mysqli_prepare(
            $conDB,
            "UPDATE timetables SET name=?, is_temporary=?, start_date=?, end_date=? WHERE id=?"
        );
        $isTemporaryInt = $isTemporary ? 1 : 0;
        $startDateParam = ($isTemporary && $startDate !== '') ? $startDate : null;
        $endDateParam = ($isTemporary && $endDate !== '') ? $endDate : null;
        mysqli_stmt_bind_param($stmt, 'sissi', $name, $isTemporaryInt, $startDateParam, $endDateParam, $id);
        if (!mysqli_stmt_execute($stmt)) {
            mysqli_stmt_close($stmt);
            echo json_encode(['status' => 'error', 'message' => 'Failed to update timetable.']);
            return;
        }
        mysqli_stmt_close($stmt);
    } else {
        // New timetables start inactive (draft) - has to be explicitly
        // switched on via 'toggle_timetable_active' before it affects any
        // company's/employee's attendance calculations. Keeps a
        // half-configured schedule from going live the moment it's saved.
        $stmt = mysqli_prepare(
            $conDB,
            "INSERT INTO timetables (name, is_temporary, start_date, end_date, is_active) VALUES (?, ?, ?, ?, 0)"
        );
        $isTemporaryInt = $isTemporary ? 1 : 0;
        $startDateParam = ($isTemporary && $startDate !== '') ? $startDate : null;
        $endDateParam = ($isTemporary && $endDate !== '') ? $endDate : null;
        mysqli_stmt_bind_param($stmt, 'siss', $name, $isTemporaryInt, $startDateParam, $endDateParam);
        if (!mysqli_stmt_execute($stmt)) {
            mysqli_stmt_close($stmt);
            echo json_encode(['status' => 'error', 'message' => 'Failed to create timetable.']);
            return;
        }
        $id = mysqli_insert_id($conDB);
        mysqli_stmt_close($stmt);
    }

    $dayStmt = mysqli_prepare(
        $conDB,
        "INSERT INTO timetable_days (timetable_id, weekday, is_off, check_in, check_in_start, check_in_end, check_out, check_out_start, check_out_end, standard_hours)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
         ON DUPLICATE KEY UPDATE is_off=VALUES(is_off), check_in=VALUES(check_in), check_in_start=VALUES(check_in_start),
            check_in_end=VALUES(check_in_end), check_out=VALUES(check_out), check_out_start=VALUES(check_out_start),
            check_out_end=VALUES(check_out_end), standard_hours=VALUES(standard_hours)"
    );
    foreach ($days as $weekday => $d) {
        mysqli_stmt_bind_param(
            $dayStmt, 'iiissssssd',
            $id, $weekday, $d['is_off'], $d['check_in'], $d['check_in_start'], $d['check_in_end'],
            $d['check_out'], $d['check_out_start'], $d['check_out_end'], $d['standard_hours']
        );
        mysqli_stmt_execute($dayStmt);
    }
    mysqli_stmt_close($dayStmt);

    if ($isTemporary) {
        mysqli_query($conDB, "DELETE FROM timetable_employees WHERE timetable_id = " . (int) $id);
        foreach ($employeeIds as $empId) {
            $insStmt = mysqli_prepare($conDB, "INSERT IGNORE INTO timetable_employees (timetable_id, emp_id) VALUES (?, ?)");
            mysqli_stmt_bind_param($insStmt, 'ii', $id, $empId);
            mysqli_stmt_execute($insStmt);
            mysqli_stmt_close($insStmt);
        }
        $affectedEmpIds = array_merge($affectedEmpIds, $employeeIds);
    } else {
        // Non-temporary save: this id no longer owns any employee overrides.
        mysqli_query($conDB, "DELETE FROM timetable_employees WHERE timetable_id = " . (int) $id);

        // Release companies that were on this timetable but got unchecked - back to Default.
        if ($id !== 1) {
            if (empty($companyIds)) {
                $releaseStmt = mysqli_prepare($conDB, "UPDATE companies SET timetable_id = 1 WHERE timetable_id = ?");
                mysqli_stmt_bind_param($releaseStmt, 'i', $id);
            } else {
                $placeholders = implode(',', array_fill(0, count($companyIds), '?'));
                $types = 'i' . str_repeat('i', count($companyIds));
                $releaseStmt = mysqli_prepare($conDB, "UPDATE companies SET timetable_id = 1 WHERE timetable_id = ? AND comp_id NOT IN ({$placeholders})");
                mysqli_stmt_bind_param($releaseStmt, $types, $id, ...$companyIds);
            }
            mysqli_stmt_execute($releaseStmt);
            mysqli_stmt_close($releaseStmt);
        }

        // Claim the checked companies - already guarded above against stealing
        // from another custom timetable, so this only ever claims companies
        // that were on Default or already on this same timetable.
        if (!empty($companyIds)) {
            $placeholders = implode(',', array_fill(0, count($companyIds), '?'));
            $types = 'i' . str_repeat('i', count($companyIds));
            $claimStmt = mysqli_prepare($conDB, "UPDATE companies SET timetable_id = ? WHERE comp_id IN ({$placeholders})");
            mysqli_stmt_bind_param($claimStmt, $types, $id, ...$companyIds);
            mysqli_stmt_execute($claimStmt);
            mysqli_stmt_close($claimStmt);
        }

        $affectedEmpIds = array_merge($affectedEmpIds, employees_of_companies($conDB, $companyIds));
    }

    $recalculated = attendance_recalculate_states($conDB, $affectedEmpIds);

    echo json_encode(['status' => 'success', 'message' => 'Timetable saved.', 'id' => $id, 'recalculated' => $recalculated]);
}

function employees_of_companies($conDB, array $compIds) {
    $compIds = array_values(array_unique(array_filter(array_map('intval', $compIds))));
    if (empty($compIds)) {
        return [];
    }
    $placeholders = implode(',', array_fill(0, count($compIds), '?'));
    $types = str_repeat('i', count($compIds));
    $stmt = mysqli_prepare($conDB, "SELECT emp_id FROM employees WHERE comp_no IN ({$placeholders})");
    mysqli_stmt_bind_param($stmt, $types, ...$compIds);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $empIds = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $empIds[] = (int) $row['emp_id'];
    }
    mysqli_stmt_close($stmt);
    return $empIds;
}

function delete_timetable($conDB) {
    $id = (int) ($_POST['id'] ?? 0);
    if ($id <= 0) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid id.']);
        return;
    }
    if ($id === 1) {
        echo json_encode(['status' => 'error', 'message' => 'The Default timetable cannot be deleted.']);
        return;
    }

    $affectedEmpIds = [];
    $empStmt = mysqli_prepare($conDB, "SELECT emp_id FROM timetable_employees WHERE timetable_id = ?");
    mysqli_stmt_bind_param($empStmt, 'i', $id);
    mysqli_stmt_execute($empStmt);
    $empResult = mysqli_stmt_get_result($empStmt);
    while ($row = mysqli_fetch_assoc($empResult)) {
        $affectedEmpIds[] = (int) $row['emp_id'];
    }
    mysqli_stmt_close($empStmt);

    $compStmt = mysqli_prepare($conDB, "SELECT comp_id FROM companies WHERE timetable_id = ?");
    mysqli_stmt_bind_param($compStmt, 'i', $id);
    mysqli_stmt_execute($compStmt);
    $compResult = mysqli_stmt_get_result($compStmt);
    $compIds = [];
    while ($row = mysqli_fetch_assoc($compResult)) {
        $compIds[] = (int) $row['comp_id'];
    }
    mysqli_stmt_close($compStmt);
    $affectedEmpIds = array_merge($affectedEmpIds, employees_of_companies($conDB, $compIds));

    mysqli_query($conDB, "DELETE FROM timetable_employees WHERE timetable_id = " . (int) $id);

    $releaseStmt = mysqli_prepare($conDB, "UPDATE companies SET timetable_id = 1 WHERE timetable_id = ?");
    mysqli_stmt_bind_param($releaseStmt, 'i', $id);
    mysqli_stmt_execute($releaseStmt);
    mysqli_stmt_close($releaseStmt);

    $deleteStmt = mysqli_prepare($conDB, "DELETE FROM timetables WHERE id = ?");
    mysqli_stmt_bind_param($deleteStmt, 'i', $id);
    if (mysqli_stmt_execute($deleteStmt)) {
        mysqli_stmt_close($deleteStmt);
        attendance_recalculate_states($conDB, $affectedEmpIds);
        echo json_encode(['status' => 'success', 'message' => 'Timetable deleted, its companies moved to Default.']);
    } else {
        mysqli_stmt_close($deleteStmt);
        echo json_encode(['status' => 'error', 'message' => 'Failed to delete timetable.']);
    }
}

function toggle_timetable_active($conDB) {
    $id = (int) ($_POST['id'] ?? 0);
    $active = !empty($_POST['active']) ? 1 : 0;

    if ($id <= 0) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid id.']);
        return;
    }
    if ($id === 1 && $active === 0) {
        echo json_encode(['status' => 'error', 'message' => 'The Default timetable is always active and cannot be deactivated.']);
        return;
    }

    $ttStmt = mysqli_prepare($conDB, "SELECT id, name, is_temporary, start_date, end_date FROM timetables WHERE id = ? LIMIT 1");
    mysqli_stmt_bind_param($ttStmt, 'i', $id);
    mysqli_stmt_execute($ttStmt);
    $timetable = mysqli_stmt_get_result($ttStmt)->fetch_assoc();
    mysqli_stmt_close($ttStmt);
    if (!$timetable) {
        echo json_encode(['status' => 'error', 'message' => 'Timetable not found.']);
        return;
    }
    $isTemporary = (int) $timetable['is_temporary'] === 1;

    if ($active === 1) {
        if ($isTemporary) {
            // Employee-wise timetables aren't mutually exclusive at the DB
            // level like company timetables are (an employee could in theory
            // be added to more than one), so this is the one case a real
            // conflict can occur: the same employee ending up covered by two
            // *active* overrides with overlapping windows, leaving it
            // ambiguous which one governs their attendance.
            $myStart = $timetable['start_date']; // null = permanent
            $myEnd = $timetable['end_date'];

            $candidateStmt = mysqli_prepare(
                $conDB,
                "SELECT DISTINCT e.name AS emp_name, t2.name AS other_name, t2.start_date, t2.end_date
                 FROM timetable_employees te
                 JOIN timetable_employees te2 ON te2.emp_id = te.emp_id AND te2.timetable_id != te.timetable_id
                 JOIN timetables t2 ON t2.id = te2.timetable_id
                 JOIN employees e ON e.emp_id = te.emp_id
                 WHERE te.timetable_id = ? AND t2.is_temporary = 1 AND t2.is_active = 1"
            );
            mysqli_stmt_bind_param($candidateStmt, 'i', $id);
            mysqli_stmt_execute($candidateStmt);
            $candidates = mysqli_stmt_get_result($candidateStmt);
            $conflict = null;
            while ($cand = mysqli_fetch_assoc($candidates)) {
                // A NULL/NULL window is permanent - always overlaps. Two
                // dated windows overlap only if they actually intersect.
                $overlaps = ($myStart === null || $cand['start_date'] === null)
                    || ($myStart <= $cand['end_date'] && $cand['start_date'] <= $myEnd);
                if ($overlaps) {
                    $conflict = $cand;
                    break;
                }
            }
            mysqli_stmt_close($candidateStmt);
            if ($conflict) {
                echo json_encode(['status' => 'error', 'message' => "{$conflict['emp_name']} is already covered by the active timetable \"{$conflict['other_name']}\" for an overlapping period - remove them there first."]);
                return;
            }
        }
        // Company timetables have nothing to check here: companies.timetable_id
        // points to exactly one timetable at a time (see the "assigned
        // elsewhere" lock in add_edit_timetable()), so a company can never
        // simultaneously belong to two timetables for this to conflict with -
        // whichever timetable currently owns it is the only one that can be
        // activated/deactivated for it.
    }

    $updStmt = mysqli_prepare($conDB, "UPDATE timetables SET is_active = ? WHERE id = ?");
    mysqli_stmt_bind_param($updStmt, 'ii', $active, $id);
    if (!mysqli_stmt_execute($updStmt)) {
        mysqli_stmt_close($updStmt);
        echo json_encode(['status' => 'error', 'message' => 'Failed to update status.']);
        return;
    }
    mysqli_stmt_close($updStmt);

    $affectedEmpIds = [];
    $empStmt = mysqli_prepare($conDB, "SELECT emp_id FROM timetable_employees WHERE timetable_id = ?");
    mysqli_stmt_bind_param($empStmt, 'i', $id);
    mysqli_stmt_execute($empStmt);
    $empResult = mysqli_stmt_get_result($empStmt);
    while ($row = mysqli_fetch_assoc($empResult)) {
        $affectedEmpIds[] = (int) $row['emp_id'];
    }
    mysqli_stmt_close($empStmt);

    $compStmt = mysqli_prepare($conDB, "SELECT comp_id FROM companies WHERE timetable_id = ?");
    mysqli_stmt_bind_param($compStmt, 'i', $id);
    mysqli_stmt_execute($compStmt);
    $compResult = mysqli_stmt_get_result($compStmt);
    $compIds = [];
    while ($row = mysqli_fetch_assoc($compResult)) {
        $compIds[] = (int) $row['comp_id'];
    }
    mysqli_stmt_close($compStmt);
    $affectedEmpIds = array_merge($affectedEmpIds, employees_of_companies($conDB, $compIds));

    attendance_recalculate_states($conDB, $affectedEmpIds);

    echo json_encode([
        'status' => 'success',
        'message' => $active ? 'Timetable activated.' : 'Timetable deactivated.',
        'is_active' => $active,
    ]);
}
