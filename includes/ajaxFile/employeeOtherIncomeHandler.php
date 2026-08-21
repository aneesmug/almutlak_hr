<?php
/**
 * Scheduled "Other Income" handler - lets HR schedule a recurring extra-income line
 * (e.g. a 3-month Bonus) for an employee across a specific month range. Unlike
 * employee_additional_info, this is a history table: every schedule adds a new row.
 * Payroll generation (includes/api/process_payroll.php -> addOrUpdateScheduledOtherIncome)
 * auto-inserts a matching `payroll_benefits` row for each month the schedule covers, and
 * flips the schedule to status = 0 once its end_month has been processed.
 * Powers the "Other Income" section on the Additional Information tab (view_employee.php).
 */
header('Content-Type: application/json');
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/session_check.php';
require_once __DIR__ . '/../../includes/helper_functions.php';
require_once __DIR__ . '/../special_access_helper.php';

$ajaxType = $_POST['ajaxType'] ?? '';

// Other Income has its own dedicated special access key ('view_employee_other_income') so it
// can be granted independently of the rest of Additional Information/salary - matches the
// $canViewOtherIncome gate on view_employee.php.
function otherIncomeCanView($conDB, $empid, $user_role, $user_type, $is_system_admin, $isHR, $isDeptHr)
{
    return ($is_system_admin || $isHR || $isDeptHr
        || user_has_special_access($conDB, $empid ?? '', 'view_employee_other_income', $user_role ?? '', $user_type ?? '', $is_system_admin ?? false));
}

function isValidPayrollMonth($value)
{
    return is_string($value) && preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $value);
}

if ($ajaxType === 'get_employee_other_income') {
    $emp_id = trim((string)($_POST['emp_id'] ?? ''));
    if ($emp_id === '') {
        echo json_encode(['status' => 400, 'message' => 'Missing employee ID']);
        exit;
    }
    if (!otherIncomeCanView($conDB, $empid, $user_role, $user_type, $is_system_admin, $isHR, $isDeptHr)) {
        echo json_encode(['status' => 403, 'message' => 'Access denied']);
        exit;
    }

    $stmt = mysqli_prepare($conDB, "SELECT id, title, amount, start_month, end_month, status, created_at FROM `employee_other_income` WHERE `emp_id` = ? ORDER BY `created_at` DESC, `id` DESC");
    mysqli_stmt_bind_param($stmt, "s", $emp_id);
    mysqli_stmt_execute($stmt);
    $rows = mysqli_fetch_all(mysqli_stmt_get_result($stmt), MYSQLI_ASSOC);
    mysqli_stmt_close($stmt);

    echo json_encode(['status' => 200, 'data' => $rows]);
    exit;
}

if ($ajaxType === 'add_employee_other_income') {
    $emp_id = trim((string)($_POST['emp_id'] ?? ''));
    if ($emp_id === '') {
        echo json_encode(['status' => 400, 'message' => 'Missing employee ID']);
        exit;
    }
    if (!($is_system_admin || $isHR || $isDeptHr)) {
        echo json_encode(['status' => 403, 'message' => 'Access denied']);
        exit;
    }

    $title = trim((string)($_POST['title'] ?? ''));
    $amount = ($_POST['amount'] ?? '') === '' ? null : (float)$_POST['amount'];
    $start_month = trim((string)($_POST['start_month'] ?? ''));
    $end_month = trim((string)($_POST['end_month'] ?? ''));

    if ($title === '' || $amount === null || $amount <= 0) {
        echo json_encode(['status' => 400, 'message' => 'Title and a positive amount are required']);
        exit;
    }
    if (!isValidPayrollMonth($start_month) || !isValidPayrollMonth($end_month)) {
        echo json_encode(['status' => 400, 'message' => 'Start and end month must be valid (YYYY-MM)']);
        exit;
    }
    if ($end_month < $start_month) {
        echo json_encode(['status' => 400, 'message' => 'End month cannot be before start month']);
        exit;
    }

    $created_by = $empid ?? '';
    $stmt = mysqli_prepare($conDB, "INSERT INTO `employee_other_income`
            (`emp_id`, `title`, `amount`, `start_month`, `end_month`, `status`, `created_by`)
        VALUES (?, ?, ?, ?, ?, 1, ?)");
    mysqli_stmt_bind_param($stmt, "ssdsss", $emp_id, $title, $amount, $start_month, $end_month, $created_by);

    if (mysqli_stmt_execute($stmt)) {
        $newId = mysqli_insert_id($conDB);
        mysqli_stmt_close($stmt);
        if (class_exists('ActivityLogger')) {
            ActivityLogger::logUpdate(
                'Employee',
                'employeeOtherIncomeHandler.php',
                $emp_id,
                [],
                ['title' => $title, 'amount' => $amount, 'start_month' => $start_month, 'end_month' => $end_month],
                "Scheduled other income '{$title}' for employee: {$emp_id} ({$start_month} to {$end_month})",
                'employee_other_income'
            );
        }
        echo json_encode(['status' => 200, 'message' => __('update_successful') ?: 'Updated successfully', 'id' => $newId]);
    } else {
        $error = mysqli_stmt_error($stmt);
        mysqli_stmt_close($stmt);
        echo json_encode(['status' => 500, 'message' => 'Database error: ' . $error]);
    }
    exit;
}

if ($ajaxType === 'deactivate_employee_other_income') {
    $id = (int)($_POST['id'] ?? 0);
    if ($id <= 0) {
        echo json_encode(['status' => 400, 'message' => 'Missing record ID']);
        exit;
    }
    if (!($is_system_admin || $isHR || $isDeptHr)) {
        echo json_encode(['status' => 403, 'message' => 'Access denied']);
        exit;
    }

    $stmt = mysqli_prepare($conDB, "UPDATE `employee_other_income` SET `status` = 0 WHERE `id` = ? AND `status` = 1");
    mysqli_stmt_bind_param($stmt, "i", $id);

    if (mysqli_stmt_execute($stmt)) {
        mysqli_stmt_close($stmt);

        // Also strip this schedule's benefit from any month whose payroll isn't finalized
        // yet (still 'generated'/'updated') - regeneration alone never removes it, since it
        // only skips *adding* new rows for inactive schedules, it doesn't delete existing
        // ones. Already-'paid' payroll is left untouched.
        $removedCount = 0;
        $cleanupStmt = mysqli_prepare($conDB, "
            DELETE pb FROM `payroll_benefits` pb
            INNER JOIN `payrolls` p ON p.`emp_id` = pb.`emp_id` AND p.`month_year` = pb.`month`
            WHERE pb.`source_other_income_id` = ? AND p.`status` != 'paid'");
        if ($cleanupStmt) {
            mysqli_stmt_bind_param($cleanupStmt, "i", $id);
            mysqli_stmt_execute($cleanupStmt);
            $removedCount = mysqli_stmt_affected_rows($cleanupStmt);
            mysqli_stmt_close($cleanupStmt);
        }

        if (class_exists('ActivityLogger')) {
            ActivityLogger::logUpdate(
                'Employee',
                'employeeOtherIncomeHandler.php',
                (string)$id,
                [],
                ['status' => 0, 'removed_pending_benefits' => $removedCount],
                "Deactivated scheduled other income record #{$id}" . ($removedCount > 0 ? " and removed it from {$removedCount} pending payroll month(s)" : ''),
                'employee_other_income'
            );
        }
        echo json_encode(['status' => 200, 'message' => __('update_successful') ?: 'Updated successfully']);
    } else {
        $error = mysqli_stmt_error($stmt);
        mysqli_stmt_close($stmt);
        echo json_encode(['status' => 500, 'message' => 'Database error: ' . $error]);
    }
    exit;
}

echo json_encode(['status' => 400, 'message' => 'Invalid AJAX type specified.']);
