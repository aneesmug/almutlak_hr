<?php
/**
 * Salary Increment Request AJAX Handler
 * Handles submission, approval, and rejection of salary increment requests.
 *
 * A Direct Supervisor submits this request for one of their own assigned
 * employees (employees.supervisor_id = current user). The last approver in
 * the chain picks the salary component the approved amount goes into; their
 * approval writes a new active emp_salary row and updates employees.salary.
 *
 * Features:
 * - Multi-level approval workflow based on app_settings configuration
 * - Direct supervisor authorization + minimum 1 year tenure gate
 * - Email notifications to approvers
 * - Browser notifications
 */

header('Content-Type: application/json');
require_once __DIR__ . '/../../includes/session_check.php';
include("./../../includes/validate_supervisor.php");

if (session_status() == PHP_SESSION_NONE) session_start();
$current_user_id = $_SESSION['empid'] ?? 0;
$userwel = $_SESSION['userwel'] ?? 'System';

function getSalaryIncrementRequestTypeId($conDB)
{
    $type_stmt = mysqli_prepare($conDB, "SELECT id FROM approval_request_types WHERE type_name = 'salary_increment' LIMIT 1");
    if ($type_stmt) {
        mysqli_stmt_execute($type_stmt);
        $type_res = mysqli_stmt_get_result($type_stmt);
        if ($type_res && ($type_row = mysqli_fetch_assoc($type_res))) {
            $resolvedId = (int)($type_row['id'] ?? 0);
            mysqli_free_result($type_res);
            mysqli_stmt_close($type_stmt);
            return ($resolvedId > 0) ? $resolvedId : 0;
        }
        if ($type_res) mysqli_free_result($type_res);
        mysqli_stmt_close($type_stmt);
    }
    return 0;
}

/**
 * Generate unique Salary Increment Request ID
 * Format: SI-YYYYMMDDHHMMSS-EMPID-RND
 */
function generateSalaryIncrementRequestID($conDB, $emp_id)
{
    $safeEmpId = preg_replace('/[^A-Za-z0-9]/', '', (string)$emp_id);
    $safeEmpId = ($safeEmpId !== '') ? $safeEmpId : 'EMP';

    $max_attempts = 5;
    $attempt = 0;

    while ($attempt < $max_attempts) {
        $request_inv_no_candidate = sprintf(
            'SI-%s-%s-%s',
            date('YmdHis'),
            $safeEmpId,
            substr(bin2hex(random_bytes(4)), 0, 4)
        );

        $exists_stmt = mysqli_prepare($conDB, "SELECT id FROM emp_salary_increment WHERE request_inv_no = ? LIMIT 1");
        if ($exists_stmt) {
            mysqli_stmt_bind_param($exists_stmt, "s", $request_inv_no_candidate);
            mysqli_stmt_execute($exists_stmt);
            $exists_res = mysqli_stmt_get_result($exists_stmt);
            $exists = ($exists_res && mysqli_num_rows($exists_res) > 0);
            if ($exists_res) {
                mysqli_free_result($exists_res);
            }
            mysqli_stmt_close($exists_stmt);

            if (!$exists) {
                return $request_inv_no_candidate;
            }
        } else {
            return $request_inv_no_candidate;
        }

        $attempt++;
    }

    throw new Exception('Unable to generate unique salary increment request ID. Please try again.');
}

/**
 * Parse an employee joining_date value (multiple stored formats) into a DateTime,
 * mirrors the passport_exp parsing pattern used in ajaxBusinessTrip.php.
 */
function parseSalaryIncrementDate($rawValue)
{
    $rawValue = trim((string)$rawValue);
    if ($rawValue === '') {
        return null;
    }

    $formats = ['Y-m-d', 'd-m-Y', 'd/m/Y', 'm/d/Y', 'Y/m/d'];
    foreach ($formats as $format) {
        $dt = DateTime::createFromFormat($format, $rawValue);
        if ($dt instanceof DateTime) {
            return $dt;
        }
    }

    $ts = strtotime($rawValue);
    if ($ts !== false) {
        return new DateTime(date('Y-m-d', $ts));
    }

    return null;
}

// emp_salary component columns (same whitelist as hrHandler.php 'update_salary').
const SALARY_INCREMENT_COMPONENTS = ['basic', 'housing', 'transport', 'food', 'misc', 'cashier', 'fuel', 'tel', 'other', 'guard'];

/**
 * True when the pending row for one of $approver_ids is the last level of the chain,
 * i.e. approving it finalizes the request.
 */
function isSalaryIncrementFinalStep($conDB, $request_inv_no, $request_type_id, $approver_id, $delegated_from_id)
{
    $stmt = mysqli_prepare($conDB, "SELECT ra.approval_level,
            (SELECT MAX(r2.approval_level) FROM request_approvers r2 WHERE r2.request_inv_no = ra.request_inv_no AND r2.request_type_id = ra.request_type_id) AS max_level
        FROM request_approvers ra
        WHERE ra.request_inv_no = ? AND ra.request_type_id = ? AND ra.approver_id IN (?, ?) AND ra.status = 'pending'
        LIMIT 1");
    if (!$stmt) return false;
    mysqli_stmt_bind_param($stmt, 'siii', $request_inv_no, $request_type_id, $approver_id, $delegated_from_id);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $row = $res ? mysqli_fetch_assoc($res) : null;
    if ($res) mysqli_free_result($res);
    mysqli_stmt_close($stmt);
    return $row && (int)$row['approval_level'] === (int)$row['max_level'];
}

/** Active (status = 1) emp_salary row for the employee, or null. */
function getActiveSalaryRow($conDB, $emp_id)
{
    $stmt = mysqli_prepare($conDB, "SELECT * FROM emp_salary WHERE emp_id = ? AND status = 1 ORDER BY id DESC LIMIT 1");
    if (!$stmt) return null;
    mysqli_stmt_bind_param($stmt, 's', $emp_id);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $row = $res ? mysqli_fetch_assoc($res) : null;
    if ($res) mysqli_free_result($res);
    mysqli_stmt_close($stmt);
    return $row ?: null;
}

/**
 * Final approval: add the approved amount to one salary component. Same versioning as
 * hrHandler.php 'update_salary' (old emp_salary row -> status 0, new row -> status 1),
 * then employees.salary = new component total and the "Update Salary" flag is cleared.
 * Returns the old/new breakdown, or null when the request is not approved or a write failed.
 */
function applySalaryIncrementToSalary($conDB, $request_inv_no, $si_full, $salary_row, $component, $approved_amount_override)
{
    $st_stmt = mysqli_prepare($conDB, "SELECT current_status, approved_amount, increment_amount FROM emp_salary_increment WHERE request_inv_no = ? LIMIT 1");
    mysqli_stmt_bind_param($st_stmt, 's', $request_inv_no);
    mysqli_stmt_execute($st_stmt);
    $st_res = mysqli_stmt_get_result($st_stmt);
    $st = $st_res ? mysqli_fetch_assoc($st_res) : null;
    if ($st_res) mysqli_free_result($st_res);
    mysqli_stmt_close($st_stmt);
    if (!$st || $st['current_status'] !== 'approved') {
        return null;
    }

    $amount = $approved_amount_override ?? ($st['approved_amount'] !== null ? (float)$st['approved_amount'] : (float)$st['increment_amount']);
    $increment = (int)round($amount); // emp_salary columns are INT
    $emp_id = (string)$si_full['emp_id'];

    $old = [];
    foreach (SALARY_INCREMENT_COMPONENTS as $col) {
        $old[$col] = (int)$salary_row[$col];
    }
    $new = $old;
    $new[$component] += $increment;
    $old_total = array_sum($old);
    $new_total = array_sum($new);

    $cols = implode(', ', SALARY_INCREMENT_COMPONENTS);
    $ins_stmt = mysqli_prepare($conDB, "INSERT INTO emp_salary (emp_id, {$cols}, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1)");
    if (!$ins_stmt) {
        error_log('Salary increment apply failed (prepare insert): ' . mysqli_error($conDB));
        return null;
    }
    $vals = array_values($new);
    mysqli_stmt_bind_param($ins_stmt, 'siiiiiiiiii', $emp_id, ...$vals);
    if (!mysqli_stmt_execute($ins_stmt)) {
        error_log('Salary increment apply failed (insert): ' . mysqli_stmt_error($ins_stmt));
        mysqli_stmt_close($ins_stmt);
        return null;
    }
    $new_salary_id = mysqli_insert_id($conDB);
    mysqli_stmt_close($ins_stmt);

    $old_id = (int)$salary_row['id'];
    $off_stmt = mysqli_prepare($conDB, "UPDATE emp_salary SET status = 0 WHERE id = ?");
    mysqli_stmt_bind_param($off_stmt, 'i', $old_id);
    mysqli_stmt_execute($off_stmt);
    mysqli_stmt_close($off_stmt);

    $new_total_str = (string)$new_total;
    $emp_stmt = mysqli_prepare($conDB, "UPDATE employees SET salary = ?, salary_update_pending = 0 WHERE emp_id = ?");
    mysqli_stmt_bind_param($emp_stmt, 'ss', $new_total_str, $emp_id);
    mysqli_stmt_execute($emp_stmt);
    mysqli_stmt_close($emp_stmt);

    // Requests with no GM amount step keep the requested amount as the approved one.
    $amt_stmt = mysqli_prepare($conDB, "UPDATE emp_salary_increment SET approved_amount = ? WHERE request_inv_no = ? AND approved_amount IS NULL");
    $amount_f = (float)$increment;
    mysqli_stmt_bind_param($amt_stmt, 'ds', $amount_f, $request_inv_no);
    mysqli_stmt_execute($amt_stmt);
    mysqli_stmt_close($amt_stmt);

    if (class_exists('ActivityLogger')) {
        ActivityLogger::logUpdate('Employee Salary', 'ajaxSalaryIncrement.php', $new_salary_id, $old, $new,
            "Salary increment {$request_inv_no}: +{$increment} to {$component} for employee ID: {$emp_id}, Total: {$old_total} -> {$new_total}", 'emp_salary');
    }

    return [
        'component' => $component,
        'amount' => $increment,
        'old' => $old,
        'new' => $new,
        'old_total' => $old_total,
        'new_total' => $new_total,
    ];
}

/**
 * Final notice to the submitting supervisor, with the employees the last approver picked in CC.
 */
function sendSalaryIncrementFinalEmail($conDB, $request_inv_no, $si_full, $applied, $cc_emp_ids, $approver_name)
{
    if (!function_exists('send_approval_email')) return;

    $lookup = function ($ids) use ($conDB) {
        $out = [];
        if (empty($ids)) return $out;
        $ph = implode(',', array_fill(0, count($ids), '?'));
        $stmt = mysqli_prepare($conDB, "SELECT e.emp_id, e.name, MIN(al.email) AS email FROM admin_login al
            JOIN employees e ON e.emp_id = al.emp_id
            WHERE al.emp_id IN ({$ph}) AND al.email IS NOT NULL AND al.email <> ''
            GROUP BY e.emp_id, e.name");
        if (!$stmt) return $out;
        mysqli_stmt_bind_param($stmt, str_repeat('s', count($ids)), ...$ids);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        while ($res && ($r = mysqli_fetch_assoc($res))) {
            $out[(string)$r['emp_id']] = $r;
        }
        if ($res) mysqli_free_result($res);
        mysqli_stmt_close($stmt);
        return $out;
    };

    $submitter = $lookup([(string)$si_full['submitted_by']]);
    $submitter = reset($submitter) ?: null;
    $cc_rows = $lookup($cc_emp_ids);

    $to = $submitter;
    if (!$to) {
        $to = array_shift($cc_rows);
    }
    if (!$to || !filter_var($to['email'], FILTER_VALIDATE_EMAIL)) return;

    $cc = [];
    foreach ($cc_rows as $r) {
        if (strcasecmp($r['email'], $to['email']) !== 0) {
            $cc[$r['email']] = $r['name'];
        }
    }

    $h = function ($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); };
    $labels = ['basic' => 'Basic', 'housing' => 'Housing', 'transport' => 'Transport', 'food' => 'Food', 'misc' => 'Misc', 'cashier' => 'Cashier', 'fuel' => 'Fuel', 'tel' => 'Telephone', 'other' => 'Other', 'guard' => 'Guard'];
    $cell = 'padding: 8px 10px; border-bottom: 1px solid #404040; font-size: 13px;';
    $rows = '';
    foreach (SALARY_INCREMENT_COMPONENTS as $col) {
        if ($applied['old'][$col] == 0 && $applied['new'][$col] == 0) continue;
        $hit = $col === $applied['component'];
        $rows .= '<tr' . ($hit ? ' style="background-color:#24352a;"' : '') . '>'
            . '<td style="' . $cell . ' color:#e0e0e0; text-align:left;">' . $h($labels[$col]) . ($hit ? ' &#9650;' : '') . '</td>'
            . '<td style="' . $cell . ' color:#a0a0a0; text-align:right;">' . number_format($applied['old'][$col], 2) . '</td>'
            . '<td style="' . $cell . ' color:' . ($hit ? '#4CAF50' : '#ffffff') . '; text-align:right; font-weight:600;">' . number_format($applied['new'][$col], 2) . '</td>'
            . '</tr>';
    }
    $th = 'padding: 8px 10px; font-size: 12px; color: #a0a0a0; text-transform: uppercase; border-bottom: 1px solid #404040;';
    $table = '<table width="100%" border="0" cellspacing="0" cellpadding="0" style="background-color:#2a2a2a; border:1px solid #404040; border-radius:8px;">'
        . '<tr><th style="' . $th . ' text-align:left;">Component</th><th style="' . $th . ' text-align:right;">Current</th><th style="' . $th . ' text-align:right;">New</th></tr>'
        . $rows
        . '<tr><td style="padding:10px; color:#ffffff; font-weight:700; text-align:left;">Total Salary</td>'
        . '<td style="padding:10px; color:#a0a0a0; text-align:right;">' . number_format($applied['old_total'], 2) . '</td>'
        . '<td style="padding:10px; color:#4CAF50; font-weight:700; text-align:right;">' . number_format($applied['new_total'], 2) . ' SAR</td></tr>'
        . '</table>';

    $template_data = [
        'APPROVER_NAME' => $to['name'],
        'REQUEST_ID' => $request_inv_no,
        'EMPLOYEE_NAME' => $si_full['employee_name'],
        'EMPLOYEE_ID' => $si_full['emp_id'],
        'DEPARTMENT' => $si_full['department_name'] ?: 'N/A',
        'INCREMENT_AMOUNT' => number_format($applied['amount'], 2),
        'START_DATE' => $si_full['last_increment_date'] ?: date('Y-m-d'),
        'SUBMITTED_BY' => $approver_name,
        'EMAIL_MESSAGE' => 'The salary increment for ' . $si_full['employee_name'] . ' has been fully approved and the salary has been updated (+' . number_format($applied['amount'], 2) . ' SAR to ' . $labels[$applied['component']] . ').',
        'EMAIL_MESSAGE_HTML' => $table,
        'REQUEST_URL' => get_base_url() . '/salary_increment_status_history.php?request_inv_no=' . urlencode($request_inv_no),
    ];

    send_approval_email($conDB, $to['email'], $to['name'], 'Salary Increment Approved - ' . $si_full['employee_name'], 'salary_increment_approved', $template_data, $cc);
}

/**
 * Data for the last approver's popup: current salary breakdown, approved amount and
 * the people who can be CC'd on the final notice.
 */
if (isset($_POST['ajaxType']) && $_POST['ajaxType'] === 'getSalaryIncrementFinalContext') {
    try {
        $request_type_id = getSalaryIncrementRequestTypeId($conDB);
        $request_inv_no = trim((string)($_POST['request_inv_no'] ?? ''));
        $delegated_from_id = (int)(getDelegatedFromEmpId($conDB, $current_user_id) ?? $current_user_id);

        if ($request_inv_no === '' || !isSalaryIncrementFinalStep($conDB, $request_inv_no, $request_type_id, (int)$current_user_id, $delegated_from_id)) {
            echo json_encode(['status' => 'success', 'is_final' => false]);
            exit;
        }

        $si_stmt = mysqli_prepare($conDB, "SELECT emp_id, increment_amount, approved_amount, last_increment_date FROM emp_salary_increment WHERE request_inv_no = ? LIMIT 1");
        mysqli_stmt_bind_param($si_stmt, 's', $request_inv_no);
        mysqli_stmt_execute($si_stmt);
        $si_res = mysqli_stmt_get_result($si_stmt);
        $si = $si_res ? mysqli_fetch_assoc($si_res) : null;
        if ($si_res) mysqli_free_result($si_res);
        mysqli_stmt_close($si_stmt);
        if (!$si) {
            throw new Exception('Salary increment request not found.');
        }

        $salary_row = getActiveSalaryRow($conDB, $si['emp_id']);
        $components = [];
        foreach (SALARY_INCREMENT_COMPONENTS as $col) {
            $components[$col] = $salary_row ? (float)$salary_row[$col] : 0;
        }

        $master_salary = null;
        $ms_stmt = mysqli_prepare($conDB, "SELECT salary FROM employees WHERE emp_id = ? LIMIT 1");
        mysqli_stmt_bind_param($ms_stmt, 's', $si['emp_id']);
        mysqli_stmt_execute($ms_stmt);
        $ms_res = mysqli_stmt_get_result($ms_stmt);
        if ($ms_res && ($ms_row = mysqli_fetch_assoc($ms_res))) {
            $master_salary = (float)$ms_row['salary'];
        }
        if ($ms_res) mysqli_free_result($ms_res);
        mysqli_stmt_close($ms_stmt);

        // Active employees with a login email - HR, finance or anyone else to keep informed.
        $cc_people = [];
        $cc_res = mysqli_query($conDB, "SELECT e.emp_id, e.name, MIN(al.email) AS email, MIN(d.dep_nme) AS dep_nme
            FROM admin_login al
            JOIN employees e ON e.emp_id = al.emp_id AND e.status = 1
            LEFT JOIN department d ON d.id = e.dept
            WHERE al.email IS NOT NULL AND al.email <> ''
            GROUP BY e.emp_id, e.name
            ORDER BY e.name ASC");
        if ($cc_res) {
            while ($r = mysqli_fetch_assoc($cc_res)) {
                $cc_people[] = ['id' => (string)$r['emp_id'], 'name' => (string)$r['name'], 'dept' => (string)($r['dep_nme'] ?? '')];
            }
            mysqli_free_result($cc_res);
        }

        echo json_encode([
            'status' => 'success',
            'is_final' => true,
            'has_salary_row' => $salary_row !== null,
            'components' => $components,
            'master_salary' => $master_salary,
            'amount' => (float)($si['approved_amount'] ?? $si['increment_amount']),
            'effective_date' => (string)($si['last_increment_date'] ?? ''),
            'cc_people' => $cc_people,
        ], JSON_UNESCAPED_UNICODE);
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => 'Failed to load salary details: ' . $e->getMessage()]);
    }
    exit;
}

/**
 * Get the current-year evaluation score - submitted by the employee's actual assigned
 * direct supervisor (employees.supervisor_id), regardless of who is currently viewing
 * the apply form (e.g. a system admin submitting on the supervisor's behalf).
 */
if (isset($_POST['ajaxType']) && $_POST['ajaxType'] === 'getEmployeeEvaluationLatest') {
    try {
        $target_emp_id = trim((string)($_POST['emp_id'] ?? ''));
        if ($target_emp_id === '') {
            echo json_encode(['status' => 'error', 'message' => 'Invalid employee id.']);
            exit;
        }

        // Any current-year evaluation counts, whoever submitted it - employee_evaluation.php
        // already limits evaluators to the direct supervisor or a system admin.
        $score = null;
        $stmt = mysqli_prepare($conDB, "SELECT total_score FROM emp_evaluations WHERE employee_emp_id = ? AND YEAR(created_at) = YEAR(CURDATE()) ORDER BY id DESC LIMIT 1");
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "s", $target_emp_id);
            mysqli_stmt_execute($stmt);
            $res = mysqli_stmt_get_result($stmt);
            if ($res && ($row = mysqli_fetch_assoc($res))) {
                $score = (float)$row['total_score'];
            }
            if ($res) mysqli_free_result($res);
            mysqli_stmt_close($stmt);
        }

        echo json_encode(['status' => 'success', 'has_score' => ($score !== null), 'evaluation_score' => $score]);
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => 'Failed to load evaluation score: ' . $e->getMessage()]);
    }
    exit;
}

/**
 * Get the employee's last APPROVED salary increment (if any) and whether at least
 * 1 year has passed since it was approved - an employee can only get one increment per year.
 */
if (isset($_POST['ajaxType']) && $_POST['ajaxType'] === 'getLastIncrementInfo') {
    try {
        $target_emp_id = trim((string)($_POST['emp_id'] ?? ''));
        if ($target_emp_id === '') {
            echo json_encode(['status' => 'error', 'message' => 'Invalid employee id.']);
            exit;
        }

        $last_increment = null;
        $stmt = mysqli_prepare($conDB, "SELECT increment_amount, approved_amount, last_modified, last_increment_date FROM emp_salary_increment WHERE emp_id = ? AND current_status = 'approved' ORDER BY COALESCE(last_increment_date, DATE(last_modified)) DESC LIMIT 1");
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "s", $target_emp_id);
            mysqli_stmt_execute($stmt);
            $res = mysqli_stmt_get_result($stmt);
            if ($res && ($row = mysqli_fetch_assoc($res))) {
                $last_increment = $row;
            }
            if ($res) mysqli_free_result($res);
            mysqli_stmt_close($stmt);
        }

        $eligible = true;
        $months_remaining = 0;
        if ($last_increment) {
            $effectiveDateStr = !empty($last_increment['last_increment_date']) ? $last_increment['last_increment_date'] : $last_increment['last_modified'];
            $lastDate = new DateTime($effectiveDateStr);
            $oneYearAfter = (clone $lastDate)->modify('+1 year');
            $now = new DateTime();
            if ($oneYearAfter > $now) {
                $eligible = false;
                $months_remaining = (int)ceil(($oneYearAfter->getTimestamp() - $now->getTimestamp()) / (30 * 86400));
            }
        }

        echo json_encode([
            'status' => 'success',
            'has_last_increment' => ($last_increment !== null),
            'eligible' => $eligible,
            'months_remaining' => $months_remaining,
            'last_increment' => $last_increment ? [
                'amount' => (float)(!empty($last_increment['approved_amount']) ? $last_increment['approved_amount'] : $last_increment['increment_amount']),
                'date' => (!empty($last_increment['last_increment_date']) ? $last_increment['last_increment_date'] : $last_increment['last_modified'])
            ] : null
        ]);
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => 'Failed to load last increment info: ' . $e->getMessage()]);
    }
    exit;
}

/**
 * Get full report details (any status) + approval chain for a single request,
 * used by the "View Report" SweetAlert2 modal on all_applied_salary_increment.php.
 */
if (isset($_POST['ajaxType']) && $_POST['ajaxType'] === 'getSalaryIncrementReport') {
    try {
        $request_inv_no = trim((string)($_POST['request_inv_no'] ?? ''));
        if ($request_inv_no === '') {
            echo json_encode(['status' => 'error', 'message' => 'Invalid request id.']);
            exit;
        }

        $request_row = null;
        $stmt = mysqli_prepare($conDB, "SELECT si.*, e.name AS employee_name, e.dept AS employee_dept, d.dep_nme AS department_name, sup.name AS submitted_by_name,
                e.iqama, e.iqama_exp_g, e.mobile, e.c_email, e.joining_date, e.emptype, e.salary AS master_salary, e.cost_center,
                j.job AS job_title, co.name AS nationality, cp.comp_name AS company_name, l.name_en AS location_name,
                dsup.name AS direct_supervisor_name
            FROM emp_salary_increment si
            JOIN employees e ON si.emp_id = e.emp_id
            LEFT JOIN department d ON e.dept = d.id
            LEFT JOIN employees sup ON si.submitted_by = sup.emp_id
            LEFT JOIN employees dsup ON e.supervisor_id = dsup.emp_id
            LEFT JOIN ac_jobs j ON j.id = e.actual_job
            LEFT JOIN countries co ON co.id = e.country
            LEFT JOIN companies cp ON cp.comp_id = e.comp_no
            LEFT JOIN locations l ON l.id = e.location_id
            WHERE si.request_inv_no = ? LIMIT 1");
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "s", $request_inv_no);
            mysqli_stmt_execute($stmt);
            $res = mysqli_stmt_get_result($stmt);
            if ($res && ($row = mysqli_fetch_assoc($res))) {
                $request_row = $row;
            }
            if ($res) mysqli_free_result($res);
            mysqli_stmt_close($stmt);
        }

        if (!$request_row) {
            echo json_encode(['status' => 'error', 'message' => 'Salary increment request not found.']);
            exit;
        }

        // Same visibility as all_applied_salary_increment.php: admin/HR/finance see all;
        // others only their department, their own submissions or requests they approve.
        $report_type_id = getSalaryIncrementRequestTypeId($conDB);
        $can_view = !empty($is_system_admin) || !empty($isHR) || (isset($user_type) && stripos((string)$user_type, 'finance') !== false)
            || (string)$request_row['submitted_by'] === (string)$current_user_id
            || (isset($user_dept) && (string)$request_row['employee_dept'] === (string)$user_dept);
        if (!$can_view) {
            $delegated_from_id = (int)(getDelegatedFromEmpId($conDB, $current_user_id) ?? $current_user_id);
            $acc_stmt = mysqli_prepare($conDB, "SELECT 1 FROM request_approvers WHERE request_inv_no = ? AND request_type_id = ? AND approver_id IN (?, ?) LIMIT 1");
            mysqli_stmt_bind_param($acc_stmt, 'siii', $request_inv_no, $report_type_id, $current_user_id, $delegated_from_id);
            mysqli_stmt_execute($acc_stmt);
            $acc_res = mysqli_stmt_get_result($acc_stmt);
            $can_view = ($acc_res && mysqli_num_rows($acc_res) > 0);
            if ($acc_res) mysqli_free_result($acc_res);
            mysqli_stmt_close($acc_stmt);
        }
        if (!$can_view) {
            echo json_encode(['status' => 'error', 'message' => 'You do not have access to this request.']);
            exit;
        }

        // Years of service for the report header / print.
        $join_dt = parseSalaryIncrementDate($request_row['joining_date'] ?? '');
        $request_row['service_years'] = $join_dt ? $join_dt->diff(new DateTime('today'))->y : null;
        $request_row['service_months'] = $join_dt ? $join_dt->diff(new DateTime('today'))->m : null;

        $salary_info = null;
        $salary_stmt = mysqli_prepare($conDB, "SELECT basic, housing, transport, food, misc, cashier, fuel, tel, other, guard,
                (basic + housing + transport + food + misc + cashier + fuel + tel + other + guard) AS total_salary
            FROM emp_salary WHERE emp_id = ? AND status = 1 ORDER BY id DESC LIMIT 1");
        if ($salary_stmt) {
            mysqli_stmt_bind_param($salary_stmt, "s", $request_row['emp_id']);
            mysqli_stmt_execute($salary_stmt);
            $salary_res = mysqli_stmt_get_result($salary_stmt);
            if ($salary_res && ($salary_row = mysqli_fetch_assoc($salary_res))) {
                $salary_info = $salary_row;
            }
            if ($salary_res) mysqli_free_result($salary_res);
            mysqli_stmt_close($salary_stmt);
        }

        $approval_chain = [];
        $chain_stmt = mysqli_prepare($conDB, "SELECT ra.approval_level, ra.status, ra.approver_id, ra.note, ra.action_date, e.name AS approver_name,
                j.job AS approver_job,
                (SELECT al.user_type FROM admin_login al WHERE al.emp_id = ra.approver_id LIMIT 1) AS approver_role
            FROM request_approvers ra
            LEFT JOIN employees e ON e.emp_id = ra.approver_id
            LEFT JOIN ac_jobs j ON j.id = e.actual_job
            WHERE ra.request_inv_no = ? AND ra.request_type_id = ?
            ORDER BY ra.approval_level ASC, ra.id ASC");
        if ($chain_stmt) {
            mysqli_stmt_bind_param($chain_stmt, "si", $request_inv_no, $report_type_id);
            mysqli_stmt_execute($chain_stmt);
            $chain_res = mysqli_stmt_get_result($chain_stmt);
            if ($chain_res) {
                while ($chain_row = mysqli_fetch_assoc($chain_res)) {
                    $approval_chain[] = [
                        'level' => (int)($chain_row['approval_level'] ?? 0),
                        'approver_id' => (int)($chain_row['approver_id'] ?? 0),
                        'approver_name' => (string)($chain_row['approver_name'] ?? ''),
                        'approver_job' => (string)($chain_row['approver_job'] ?? ''),
                        'approver_role' => (string)($chain_row['approver_role'] ?? ''),
                        'status' => (string)($chain_row['status'] ?? ''),
                        'note' => (string)($chain_row['note'] ?? ''),
                        'action_date' => $chain_row['action_date']
                    ];
                }
                mysqli_free_result($chain_res);
            }
            mysqli_stmt_close($chain_stmt);
        }

        echo json_encode([
            'status' => 'success',
            'request' => $request_row,
            'salary_info' => $salary_info,
            'approval_chain' => $approval_chain
        ]);
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => 'Failed to load report: ' . $e->getMessage()]);
    }
    exit;
}

/**
 * Check active salary increment request before opening apply form
 */
if (isset($_POST['ajaxType']) && $_POST['ajaxType'] === 'checkActiveSalaryIncrement') {
    try {
        $target_emp_id = trim((string)($_POST['emp_id'] ?? ''));
        if ($target_emp_id === '') {
            echo json_encode(['status' => 'error', 'message' => 'Invalid employee id.']);
            exit;
        }

        $existing_request = null;
        $active_stmt = mysqli_prepare($conDB, "SELECT id, request_inv_no, increment_amount, reason, current_status, created_at
            FROM emp_salary_increment
            WHERE emp_id = ? AND current_status NOT IN ('approved','rejected','cancelled')
            ORDER BY id DESC LIMIT 1");
        if ($active_stmt) {
            mysqli_stmt_bind_param($active_stmt, "s", $target_emp_id);
            mysqli_stmt_execute($active_stmt);
            $active_res = mysqli_stmt_get_result($active_stmt);
            if ($active_res && ($active_row = mysqli_fetch_assoc($active_res))) {
                $existing_request = $active_row;
            }
            if ($active_res) mysqli_free_result($active_res);
            mysqli_stmt_close($active_stmt);
        }

        if (!$existing_request) {
            echo json_encode(['status' => 'success', 'has_active_request' => false]);
            exit;
        }

        $approval_chain = [];
        $chain_stmt = mysqli_prepare($conDB, "SELECT ra.approval_level, ra.status, ra.approver_id, e.name AS approver_name
            FROM request_approvers ra
            LEFT JOIN employees e ON e.emp_id = ra.approver_id
            WHERE ra.request_inv_no = ?
            ORDER BY ra.approval_level ASC, ra.id ASC");
        if ($chain_stmt) {
            $existing_inv = (string)$existing_request['request_inv_no'];
            mysqli_stmt_bind_param($chain_stmt, "s", $existing_inv);
            mysqli_stmt_execute($chain_stmt);
            $chain_res = mysqli_stmt_get_result($chain_stmt);
            if ($chain_res) {
                while ($chain_row = mysqli_fetch_assoc($chain_res)) {
                    $approval_chain[] = [
                        'level' => (int)($chain_row['approval_level'] ?? 0),
                        'approver_id' => (int)($chain_row['approver_id'] ?? 0),
                        'approver_name' => (string)($chain_row['approver_name'] ?? ''),
                        'status' => (string)($chain_row['status'] ?? '')
                    ];
                }
                mysqli_free_result($chain_res);
            }
            mysqli_stmt_close($chain_stmt);
        }

        $summary_html =
            '<div style="text-align:left;">'
            . '<p style="margin:0 0 8px 0;">' . __('you_already_have_active_salary_increment', 'You already have an active salary increment request for this employee. A new request is not allowed until the current one is completed.') . '</p>'
            . '<p style="margin:0 0 6px 0;"><strong>' . __('applied_information', 'Applied Information') . '</strong></p>'
            . '<ul style="margin:0 0 8px 18px;">'
            . '<li>Request ID: ' . htmlspecialchars((string)$existing_request['request_inv_no'], ENT_QUOTES, 'UTF-8') . '</li>'
            . '<li>Status: ' . htmlspecialchars((string)$existing_request['current_status'], ENT_QUOTES, 'UTF-8') . '</li>'
            . '<li>Increment Amount: ' . htmlspecialchars(number_format((float)$existing_request['increment_amount'], 2), ENT_QUOTES, 'UTF-8') . '</li>'
            . '</ul>'
            . '</div>';

        echo json_encode([
            'status' => 'success',
            'has_active_request' => true,
            'title' => __('active_request_exists', 'Active Request Exists'),
            'html' => $summary_html,
            'type' => 'warning',
            'existing_request' => $existing_request,
            'approval_chain' => $approval_chain
        ]);
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => 'Failed to check active salary increment request: ' . $e->getMessage()]);
    }
    exit;
}

/**
 * Submit new salary increment request
 */
if (isset($_POST['ajaxType']) && $_POST['ajaxType'] === 'submitSalaryIncrement') {
    try {
        $request_type_id = getSalaryIncrementRequestTypeId($conDB);
        if ($request_type_id <= 0) {
            throw new Exception('Salary increment request type is not configured in approval_request_types.');
        }

        $target_emp_id = trim((string)($_POST['emp_id'] ?? ''));
        if ($target_emp_id === '') {
            echo json_encode(['status' => 'error', 'title' => 'Invalid Request', 'message' => 'Invalid employee id.', 'type' => 'error']);
            exit;
        }

        // Block-check: employee may be restricted from having salary increment requests submitted
        require_once __DIR__ . '/../special_access_helper.php';
        $block_status = is_employee_request_blocked($conDB, $target_emp_id, 'salary_increment');
        if ($block_status['blocked']) {
            echo json_encode(['status' => 'error', 'title' => 'Request Blocked', 'message' => $block_status['reason'], 'type' => 'error']);
            exit;
        }

        // Load target employee record
        $emp_ctx = null;
        $emp_stmt = mysqli_prepare($conDB, "SELECT e.emp_id, e.name, e.supervisor_id, e.dept, e.joining_date, e.status, d.dep_nme AS dept_name
            FROM employees e
            LEFT JOIN department d ON d.id = e.dept
            WHERE e.emp_id = ? LIMIT 1");
        if ($emp_stmt) {
            mysqli_stmt_bind_param($emp_stmt, "s", $target_emp_id);
            mysqli_stmt_execute($emp_stmt);
            $emp_res = mysqli_stmt_get_result($emp_stmt);
            if ($emp_res && ($row = mysqli_fetch_assoc($emp_res))) {
                $emp_ctx = $row;
            }
            if ($emp_res) mysqli_free_result($emp_res);
            mysqli_stmt_close($emp_stmt);
        }

        if (!$emp_ctx) {
            echo json_encode(['status' => 'error', 'title' => 'Not Found', 'message' => 'Employee not found.', 'type' => 'error']);
            exit;
        }

        // Supervisor authorization: current user must be the direct supervisor of the target employee (system admin bypasses this)
        $is_system_admin = $is_system_admin ?? false;
        if (!$is_system_admin && (string)($emp_ctx['supervisor_id'] ?? '') !== (string)$current_user_id) {
            echo json_encode(['status' => 'error', 'title' => 'Access Denied', 'message' => 'You are not the direct supervisor of this employee.', 'type' => 'error']);
            exit;
        }

        // Tenure check: employee must have more than 1 year of service
        $joinDate = parseSalaryIncrementDate($emp_ctx['joining_date'] ?? '');
        $oneYearAgo = new DateTime('-1 year');
        if (!($joinDate instanceof DateTime) || $joinDate > $oneYearAgo) {
            echo json_encode(['status' => 'error', 'title' => 'Not Eligible', 'message' => 'Employee must have more than 1 year of service to be eligible for a salary increment request.', 'type' => 'warning']);
            exit;
        }

        // Sanitize inputs
        $increment_amount = (float)($_POST['increment_amount'] ?? 0);
        // Not escape_string() here - $reason is bound via a prepared statement placeholder
        // below, so pre-escaping would double-escape and store literal backslashes.
        $reason = trim((string)($_POST['reason'] ?? ''));

        $maxIncrementAmount = get_setting_num($conDB, 'salary_increment_max_amount', 2000);
        if ($increment_amount <= 0 || $increment_amount > $maxIncrementAmount) {
            echo json_encode(['status' => 'error', 'title' => 'Validation Error', 'message' => "Increment amount must be greater than 0 and not exceed {$maxIncrementAmount}.", 'type' => 'error']);
            exit;
        }

        if ($reason === '') {
            echo json_encode(['status' => 'error', 'title' => 'Validation Error', 'message' => 'Reason is required.', 'type' => 'error']);
            exit;
        }

        // Evaluation score is never taken from client input - pulled directly from the
        // employee's latest current-year evaluation (by the supervisor or an admin), so it
        // can't be typed/tampered.
        $has_evaluation = 'no';
        $evaluation_score = null;
        $eval_stmt = mysqli_prepare($conDB, "SELECT total_score FROM emp_evaluations WHERE employee_emp_id = ? AND YEAR(created_at) = YEAR(CURDATE()) ORDER BY id DESC LIMIT 1");
        if ($eval_stmt) {
            mysqli_stmt_bind_param($eval_stmt, "s", $target_emp_id);
            mysqli_stmt_execute($eval_stmt);
            $eval_res = mysqli_stmt_get_result($eval_stmt);
            if ($eval_res && ($eval_row = mysqli_fetch_assoc($eval_res))) {
                $evaluation_score = (float)$eval_row['total_score'];
                $has_evaluation = 'yes';
            }
            if ($eval_res) mysqli_free_result($eval_res);
            mysqli_stmt_close($eval_stmt);
        }

        if ($has_evaluation !== 'yes' || $evaluation_score === null) {
            echo json_encode(['status' => 'error', 'title' => 'Evaluation Required', 'message' => 'This employee does not have a current-year evaluation yet. Please evaluate them first.', 'type' => 'warning']);
            exit;
        }

        // An employee can only get one increment per year - block if the last approved
        // increment was less than 1 year ago. Mirrors getLastIncrementInfo's logic: the
        // GM-set effective date (last_increment_date) is authoritative when present,
        // since it can legitimately differ from the approval timestamp (last_modified).
        $last_incr_stmt = mysqli_prepare($conDB, "SELECT last_modified, last_increment_date FROM emp_salary_increment WHERE emp_id = ? AND current_status = 'approved' ORDER BY COALESCE(last_increment_date, DATE(last_modified)) DESC LIMIT 1");
        if ($last_incr_stmt) {
            mysqli_stmt_bind_param($last_incr_stmt, "s", $target_emp_id);
            mysqli_stmt_execute($last_incr_stmt);
            $last_incr_res = mysqli_stmt_get_result($last_incr_stmt);
            if ($last_incr_res && ($last_incr_row = mysqli_fetch_assoc($last_incr_res))) {
                $lastIncrementEffectiveStr = !empty($last_incr_row['last_increment_date']) ? $last_incr_row['last_increment_date'] : $last_incr_row['last_modified'];
                $lastIncrementDate = new DateTime($lastIncrementEffectiveStr);
                $oneYearAfterLastIncrement = (clone $lastIncrementDate)->modify('+1 year');
                if ($oneYearAfterLastIncrement > new DateTime()) {
                    if ($last_incr_res) mysqli_free_result($last_incr_res);
                    mysqli_stmt_close($last_incr_stmt);
                    echo json_encode(['status' => 'error', 'title' => 'Not Eligible', 'message' => 'This employee already received a salary increment on ' . $lastIncrementDate->format('d M Y') . '. Another increment is not allowed until 1 year has passed.', 'type' => 'warning']);
                    exit;
                }
            }
            if ($last_incr_res) mysqli_free_result($last_incr_res);
            mysqli_stmt_close($last_incr_stmt);
        }

        // Duplicate active request check
        $existing_request = null;
        $active_stmt = mysqli_prepare($conDB, "SELECT id, request_inv_no, current_status FROM emp_salary_increment WHERE emp_id = ? AND current_status NOT IN ('approved','rejected','cancelled') ORDER BY id DESC LIMIT 1");
        if ($active_stmt) {
            mysqli_stmt_bind_param($active_stmt, "s", $target_emp_id);
            mysqli_stmt_execute($active_stmt);
            $active_res = mysqli_stmt_get_result($active_stmt);
            if ($active_res && ($active_row = mysqli_fetch_assoc($active_res))) {
                $existing_request = $active_row;
            }
            if ($active_res) mysqli_free_result($active_res);
            mysqli_stmt_close($active_stmt);
        }

        if ($existing_request) {
            echo json_encode([
                'status' => 'error',
                'title' => 'Active Request Exists',
                'message' => 'This employee already has an active salary increment request (' . $existing_request['request_inv_no'] . '). A new request is not allowed until the current one is completed.',
                'type' => 'warning'
            ]);
            exit;
        }

        // Build approval chain from app_settings
        $approvalChainSetting = "approval_chain_salary_increment";
        $settingQuery = mysqli_query($conDB, "SELECT setting_value FROM app_settings WHERE setting_name = '" . escape_string($approvalChainSetting) . "' LIMIT 1");
        $configured_chain = [];

        if ($settingQuery && mysqli_num_rows($settingQuery) > 0) {
            $cfg_row = mysqli_fetch_assoc($settingQuery);
            $decoded = json_decode($cfg_row['setting_value'], true);
            if (is_array($decoded)) {
                $configured_chain = $decoded;
            }
        }
        if ($settingQuery) mysqli_free_result($settingQuery);

        if (empty($configured_chain)) {
            echo json_encode([
                'status' => 'error',
                'title' => 'Approval Chain Not Configured',
                'message' => 'Approval chain not configured for Salary Increment. Please configure it in App Settings > Approval Chain Configuration.',
                'type' => 'error'
            ]);
            exit;
        }

        // Helper: resolve configured role to approver emp_id
        $resolveApprover = function ($role, $emp_context) use ($conDB) {
            $role = trim((string)$role);
            if ($role === '') return null;

            if ($role === 'direct_supervisor') {
                return !empty($emp_context['supervisor_id']) ? (int)$emp_context['supervisor_id'] : null;
            }

            if ($role === 'dept_manager' && function_exists('getDeptManager')) {
                $dept_mgr = getDeptManager($conDB, $emp_context['dept']);
                return ($dept_mgr && !empty($dept_mgr['emp_id'])) ? (int)$dept_mgr['emp_id'] : null;
            }

            // Default: first active employee with matching user_type
            $stmt = mysqli_prepare($conDB, "SELECT e.emp_id FROM employees e
                                           JOIN admin_login al ON e.emp_id = al.emp_id
                                           WHERE al.user_type = ? AND e.status = 1
                                           ORDER BY e.emp_id ASC LIMIT 1");
            if ($stmt) {
                mysqli_stmt_bind_param($stmt, "s", $role);
                mysqli_stmt_execute($stmt);
                $res = mysqli_stmt_get_result($stmt);
                if ($res && ($row = mysqli_fetch_assoc($res))) {
                    $empId = (int)$row['emp_id'];
                    mysqli_free_result($res);
                    mysqli_stmt_close($stmt);
                    return $empId > 0 ? $empId : null;
                }
                if ($res) mysqli_free_result($res);
                mysqli_stmt_close($stmt);
            }
            return null;
        };

        $approver_chain = [];
        foreach ($configured_chain as $step) {
            $role = $step['user_type'] ?? '';
            $approverId = $resolveApprover($role, $emp_ctx);
            if ($approverId && !in_array($approverId, $approver_chain, true)) {
                $approver_chain[] = $approverId;
            }
        }

        if (empty($approver_chain)) {
            echo json_encode([
                'status' => 'error',
                'title' => 'Approval Chain Not Resolved',
                'message' => 'Could not resolve any approvers for the configured approval chain. Please check App Settings > Approval Chain Configuration.',
                'type' => 'error'
            ]);
            exit;
        }

        // Generate request ID
        $request_inv_no = generateSalaryIncrementRequestID($conDB, $target_emp_id);

        mysqli_begin_transaction($conDB);

        try {
            $insert_query = "INSERT INTO emp_salary_increment (
                request_inv_no, emp_id, submitted_by, increment_amount, reason,
                has_evaluation, evaluation_score, current_status, current_approval_level,
                created_by, created_at
            ) VALUES (
                ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW()
            )";

            $stmt = mysqli_prepare($conDB, $insert_query);
            if (!$stmt) {
                throw new Exception("Prepare failed: " . mysqli_error($conDB));
            }

            $current_status = 'pending_approval';
            $current_approval_level = 1;
            $submitted_by = (string)$current_user_id;
            mysqli_stmt_bind_param(
                $stmt,
                "sssdssdsii",
                $request_inv_no, $target_emp_id, $submitted_by, $increment_amount, $reason,
                $has_evaluation, $evaluation_score, $current_status, $current_approval_level,
                $current_user_id
            );

            if (!mysqli_stmt_execute($stmt)) {
                throw new Exception("Execute failed: " . mysqli_stmt_error($stmt));
            }

            $increment_id = mysqli_insert_id($conDB);
            mysqli_stmt_close($stmt);

            // Create request approvers entries
            $level = 1;
            foreach ($approver_chain as $approverId) {
                $status = ($level === 1) ? 'pending' : 'awaiting';
                $ra_stmt = mysqli_prepare($conDB, "INSERT INTO request_approvers (request_inv_no, request_type_id, approver_id, approval_level, status) VALUES (?, ?, ?, ?, ?)");
                if (!$ra_stmt) {
                    throw new Exception("Prepare failed (approvers): " . mysqli_error($conDB));
                }
                mysqli_stmt_bind_param($ra_stmt, "siiis", $request_inv_no, $request_type_id, $approverId, $level, $status);
                if (!mysqli_stmt_execute($ra_stmt)) {
                    throw new Exception("Execute failed (approvers): " . mysqli_stmt_error($ra_stmt));
                }
                mysqli_stmt_close($ra_stmt);
                $level++;
            }

            // Notify first approver
            $first_approver_id = (int)$approver_chain[0];
            $notif_title = 'New Salary Increment Request';
            $notif_message = 'A salary increment request for ' . ($emp_ctx['name'] ?? $target_emp_id) . ' is pending your approval.';
            $notif_url = 'all_applied_salary_increment.php?status=my_pending';

            if (function_exists('create_and_show_notification')) {
                create_and_show_notification($conDB, $first_approver_id, $notif_title, $notif_message, $notif_url, 'info');
            } elseif (function_exists('create_browser_notification')) {
                create_browser_notification($conDB, $first_approver_id, $notif_title, $notif_message, $notif_url);
            }

            if (function_exists('send_approval_email')) {
                $approver_stmt = mysqli_prepare($conDB, "SELECT al.email, e.name FROM admin_login al JOIN employees e ON al.emp_id = e.emp_id WHERE al.emp_id = ? LIMIT 1");
                if ($approver_stmt) {
                    mysqli_stmt_bind_param($approver_stmt, "i", $first_approver_id);
                    mysqli_stmt_execute($approver_stmt);
                    $approver_res = mysqli_stmt_get_result($approver_stmt);
                    if ($approver_res && ($approver_row = mysqli_fetch_assoc($approver_res))) {
                        if (!empty($approver_row['email'])) {
                            $template_data = function_exists('get_request_details_for_email')
                                ? get_request_details_for_email($conDB, $request_inv_no, 'salary_increment', $approver_row['name'])
                                : false;
                            if (!$template_data) {
                                $template_data = ['APPROVER_NAME' => $approver_row['name'], 'REQUEST_ID' => $request_inv_no];
                            }
                            send_approval_email($conDB, $approver_row['email'], $approver_row['name'], $notif_title, 'salary_increment', $template_data);
                        }
                    }
                    if ($approver_res) mysqli_free_result($approver_res);
                    mysqli_stmt_close($approver_stmt);
                }
            }

            if (class_exists('ActivityLogger')) {
                ActivityLogger::logSubmit(
                    'Salary Increment',
                    'ajaxSalaryIncrement.php',
                    $increment_id,
                    "Submitted salary increment request: {$request_inv_no}",
                    'emp_salary_increment'
                );
            }

            mysqli_commit($conDB);

            echo json_encode([
                'status' => 'success',
                'title' => 'Submitted',
                'message' => 'Salary increment request submitted successfully.',
                'type' => 'success',
                'request_inv_no' => $request_inv_no
            ]);
        } catch (Exception $e) {
            mysqli_rollback($conDB);
            throw $e;
        }
    } catch (Exception $e) {
        error_log('Salary Increment Submission Error: ' . $e->getMessage());
        echo json_encode(['status' => 'error', 'title' => 'Submission Failed', 'message' => 'An error occurred while submitting request: ' . $e->getMessage(), 'type' => 'error']);
    }
    exit;
}

/**
 * Approve salary increment request
 */
if (isset($_POST['ajaxType']) && $_POST['ajaxType'] === 'approveSalaryIncrement') {
    try {
        $request_type_id = getSalaryIncrementRequestTypeId($conDB);
        if ($request_type_id <= 0) {
            throw new Exception('Salary increment request type is not configured in approval_request_types.');
        }

        $request_inv_no = trim((string)($_POST['request_inv_no'] ?? ''));
        $approval_comment = trim((string)($_POST['approval_comment'] ?? 'Approved'));
        if ($request_inv_no === '') {
            echo json_encode(['status' => 'error', 'title' => 'Invalid Request', 'message' => 'Invalid salary increment request id.', 'type' => 'error']);
            exit;
        }

        // Optional - only sent by HR Payroll approvers from the approval modal's date picker.
        $last_increment_date = trim((string)($_POST['last_increment_date'] ?? ''));
        if ($last_increment_date !== '') {
            $li_date_obj = DateTime::createFromFormat('Y-m-d', $last_increment_date);
            if (!$li_date_obj || $li_date_obj->format('Y-m-d') !== $last_increment_date) {
                echo json_encode(['status' => 'error', 'title' => 'Validation Error', 'message' => 'Invalid date of last increment.', 'type' => 'error']);
                exit;
            }
        }

        // GM approval step - final approved amount (editable, defaults to requested amount) and
        // the increment effective date are both required.
        $approved_amount = null;
        if (!empty($isGM)) {
            $approved_amount_raw = trim((string)($_POST['approved_amount'] ?? ''));
            $gm_effective_date = trim((string)($_POST['increment_effective_date'] ?? ''));

            $maxIncrementAmount = get_setting_num($conDB, 'salary_increment_max_amount', 2000);
            if ($approved_amount_raw === '' || !is_numeric($approved_amount_raw) || (float)$approved_amount_raw <= 0 || (float)$approved_amount_raw > $maxIncrementAmount) {
                echo json_encode(['status' => 'error', 'title' => 'Validation Error', 'message' => "Approved amount is required and must be between 0 and {$maxIncrementAmount}.", 'type' => 'error']);
                exit;
            }
            $approved_amount = (float)$approved_amount_raw;

            if ($gm_effective_date === '') {
                echo json_encode(['status' => 'error', 'title' => 'Validation Error', 'message' => 'Increment effective date is required.', 'type' => 'error']);
                exit;
            }
            $gm_date_obj = DateTime::createFromFormat('Y-m-d', $gm_effective_date);
            if (!$gm_date_obj || $gm_date_obj->format('Y-m-d') !== $gm_effective_date) {
                echo json_encode(['status' => 'error', 'title' => 'Validation Error', 'message' => 'Invalid increment effective date.', 'type' => 'error']);
                exit;
            }
            $gm_today = new DateTime('today');
            if ($gm_date_obj < $gm_today) {
                echo json_encode(['status' => 'error', 'title' => 'Validation Error', 'message' => 'Increment effective date cannot be a past date.', 'type' => 'error']);
                exit;
            }
            // GM's effective date is authoritative for the "last increment" cooldown tracking.
            $last_increment_date = $gm_effective_date;
        }

        $si_status = '';
        $si_stmt = mysqli_prepare($conDB, "SELECT current_status FROM emp_salary_increment WHERE request_inv_no = ? LIMIT 1");
        if ($si_stmt) {
            mysqli_stmt_bind_param($si_stmt, 's', $request_inv_no);
            mysqli_stmt_execute($si_stmt);
            $si_res = mysqli_stmt_get_result($si_stmt);
            if ($si_res && ($si_row = mysqli_fetch_assoc($si_res))) {
                $si_status = (string)($si_row['current_status'] ?? '');
            }
            if ($si_res) mysqli_free_result($si_res);
            mysqli_stmt_close($si_stmt);
        }

        if ($si_status === '') {
            echo json_encode(['status' => 'error', 'title' => 'Not Found', 'message' => 'Salary increment request not found.', 'type' => 'error']);
            exit;
        }

        if (in_array($si_status, ['approved', 'rejected', 'cancelled'], true)) {
            echo json_encode(['status' => 'error', 'title' => 'Invalid Status', 'message' => 'This request is already finalized and cannot be approved again.', 'type' => 'warning']);
            exit;
        }

        // A temp-role replacement covers the original employee's pending approvals too -
        // request_approvers.approver_id still points at the original employee.
        $delegated_from_id = (int)(getDelegatedFromEmpId($conDB, $current_user_id) ?? $current_user_id);
        $pending_stmt = mysqli_prepare($conDB, "SELECT id FROM request_approvers WHERE request_inv_no = ? AND request_type_id = ? AND approver_id IN (?, ?) AND status = 'pending' LIMIT 1");
        $has_pending_access = false;
        if ($pending_stmt) {
            mysqli_stmt_bind_param($pending_stmt, 'siii', $request_inv_no, $request_type_id, $current_user_id, $delegated_from_id);
            mysqli_stmt_execute($pending_stmt);
            $pending_res = mysqli_stmt_get_result($pending_stmt);
            $has_pending_access = ($pending_res && mysqli_num_rows($pending_res) > 0);
            if ($pending_res) mysqli_free_result($pending_res);
            mysqli_stmt_close($pending_stmt);
        }

        if (!$has_pending_access) {
            echo json_encode(['status' => 'error', 'title' => 'Access Denied', 'message' => 'This request is not pending your approval.', 'type' => 'error']);
            exit;
        }

        // Last approver: must pick the salary component; the approval then writes the new salary.
        $is_final_step = isSalaryIncrementFinalStep($conDB, $request_inv_no, $request_type_id, (int)$current_user_id, $delegated_from_id);
        $salary_component = '';
        $cc_emp_ids = [];
        $si_full = null;
        $salary_row = null;
        if ($is_final_step) {
            $salary_component = trim((string)($_POST['salary_component'] ?? ''));
            if (!in_array($salary_component, SALARY_INCREMENT_COMPONENTS, true)) {
                echo json_encode(['status' => 'error', 'title' => 'Validation Error', 'message' => 'Please select the salary component for this increment.', 'type' => 'error']);
                exit;
            }
            $cc_raw = $_POST['cc_emp_ids'] ?? [];
            $cc_emp_ids = array_values(array_unique(array_filter(array_map(function ($v) {
                return preg_replace('/[^A-Za-z0-9]/', '', (string)$v);
            }, is_array($cc_raw) ? $cc_raw : []))));

            $sf_stmt = mysqli_prepare($conDB, "SELECT si.*, e.name AS employee_name, e.email AS employee_email, d.dep_nme AS department_name
                FROM emp_salary_increment si
                JOIN employees e ON e.emp_id = si.emp_id
                LEFT JOIN department d ON d.id = e.dept
                WHERE si.request_inv_no = ? LIMIT 1");
            mysqli_stmt_bind_param($sf_stmt, 's', $request_inv_no);
            mysqli_stmt_execute($sf_stmt);
            $sf_res = mysqli_stmt_get_result($sf_stmt);
            $si_full = $sf_res ? mysqli_fetch_assoc($sf_res) : null;
            if ($sf_res) mysqli_free_result($sf_res);
            mysqli_stmt_close($sf_stmt);

            $salary_row = $si_full ? getActiveSalaryRow($conDB, $si_full['emp_id']) : null;
            if (!$salary_row) {
                echo json_encode(['status' => 'error', 'title' => 'Salary Not Set', 'message' => 'This employee has no active salary breakdown. Please set it from the employee profile first.', 'type' => 'warning']);
                exit;
            }
        }

        $approval_result = handle_approval_action(
            $conDB,
            $request_inv_no,
            'salary_increment',
            (int)$current_user_id,
            'approve',
            ($approval_comment !== '' ? $approval_comment : 'Approved')
        );

        if (($approval_result['status'] ?? 'error') === 'error') {
            throw new Exception((string)($approval_result['message'] ?? 'Approval failed.'));
        }

        if ($approved_amount !== null) {
            $gm_update_stmt = mysqli_prepare($conDB, "UPDATE emp_salary_increment SET approved_amount = ?, last_increment_date = ? WHERE request_inv_no = ? LIMIT 1");
            if ($gm_update_stmt) {
                mysqli_stmt_bind_param($gm_update_stmt, 'dss', $approved_amount, $last_increment_date, $request_inv_no);
                mysqli_stmt_execute($gm_update_stmt);
                mysqli_stmt_close($gm_update_stmt);
            }
        } elseif ($last_increment_date !== '') {
            $li_update_stmt = mysqli_prepare($conDB, "UPDATE emp_salary_increment SET last_increment_date = ? WHERE request_inv_no = ? LIMIT 1");
            if ($li_update_stmt) {
                mysqli_stmt_bind_param($li_update_stmt, 'ss', $last_increment_date, $request_inv_no);
                mysqli_stmt_execute($li_update_stmt);
                mysqli_stmt_close($li_update_stmt);
            }
        }

        $salary_applied = null;
        if ($is_final_step) {
            $salary_applied = applySalaryIncrementToSalary($conDB, $request_inv_no, $si_full, $salary_row, $salary_component, $approved_amount);
            if ($salary_applied) {
                sendSalaryIncrementFinalEmail($conDB, $request_inv_no, $si_full, $salary_applied, $cc_emp_ids, $userwel);
            }
        }

        if (class_exists('ActivityLogger')) {
            ActivityLogger::logApproval(
                'Salary Increment',
                'ajaxSalaryIncrement.php',
                $request_inv_no,
                'approved',
                "Approved salary increment request: {$request_inv_no}",
                'emp_salary_increment'
            );
        }

        $message = 'Salary increment request approved successfully.';
        if ($salary_applied) {
            $message .= ' New total salary: ' . number_format($salary_applied['new_total'], 2) . ' SAR.';
        } elseif ($is_final_step) {
            $message .= ' The salary could not be updated automatically - please update it from the employee profile.';
        }

        echo json_encode([
            'status' => 'success',
            'title' => 'Approved',
            'message' => $message,
            'type' => 'success',
            'request_inv_no' => $request_inv_no
        ]);
    } catch (Exception $e) {
        error_log('Salary Increment Approval Error: ' . $e->getMessage());
        echo json_encode(['status' => 'error', 'title' => 'Approval Failed', 'message' => 'An error occurred while approving request: ' . $e->getMessage(), 'type' => 'error']);
    }
    exit;
}

/**
 * Reject salary increment request
 */
if (isset($_POST['ajaxType']) && $_POST['ajaxType'] === 'rejectSalaryIncrement') {
    try {
        $request_type_id = getSalaryIncrementRequestTypeId($conDB);
        if ($request_type_id <= 0) {
            throw new Exception('Salary increment request type is not configured in approval_request_types.');
        }

        $request_inv_no = trim((string)($_POST['request_inv_no'] ?? ''));
        $rejection_reason = trim((string)($_POST['rejection_reason'] ?? ''));
        if ($request_inv_no === '') {
            echo json_encode(['status' => 'error', 'title' => 'Invalid Request', 'message' => 'Invalid salary increment request id.', 'type' => 'error']);
            exit;
        }
        if ($rejection_reason === '') {
            echo json_encode(['status' => 'error', 'title' => 'Validation Error', 'message' => 'Rejection reason is required.', 'type' => 'error']);
            exit;
        }

        $si_status = '';
        $si_stmt = mysqli_prepare($conDB, "SELECT current_status FROM emp_salary_increment WHERE request_inv_no = ? LIMIT 1");
        if ($si_stmt) {
            mysqli_stmt_bind_param($si_stmt, 's', $request_inv_no);
            mysqli_stmt_execute($si_stmt);
            $si_res = mysqli_stmt_get_result($si_stmt);
            if ($si_res && ($si_row = mysqli_fetch_assoc($si_res))) {
                $si_status = (string)($si_row['current_status'] ?? '');
            }
            if ($si_res) mysqli_free_result($si_res);
            mysqli_stmt_close($si_stmt);
        }

        if ($si_status === '') {
            echo json_encode(['status' => 'error', 'title' => 'Not Found', 'message' => 'Salary increment request not found.', 'type' => 'error']);
            exit;
        }

        if (in_array($si_status, ['approved', 'rejected', 'cancelled'], true)) {
            echo json_encode(['status' => 'error', 'title' => 'Invalid Status', 'message' => 'This request is already finalized and cannot be rejected again.', 'type' => 'warning']);
            exit;
        }

        // A temp-role replacement covers the original employee's pending approvals too -
        // request_approvers.approver_id still points at the original employee.
        $delegated_from_id = (int)(getDelegatedFromEmpId($conDB, $current_user_id) ?? $current_user_id);
        $pending_stmt = mysqli_prepare($conDB, "SELECT id FROM request_approvers WHERE request_inv_no = ? AND request_type_id = ? AND approver_id IN (?, ?) AND status = 'pending' LIMIT 1");
        $has_pending_access = false;
        if ($pending_stmt) {
            mysqli_stmt_bind_param($pending_stmt, 'siii', $request_inv_no, $request_type_id, $current_user_id, $delegated_from_id);
            mysqli_stmt_execute($pending_stmt);
            $pending_res = mysqli_stmt_get_result($pending_stmt);
            $has_pending_access = ($pending_res && mysqli_num_rows($pending_res) > 0);
            if ($pending_res) mysqli_free_result($pending_res);
            mysqli_stmt_close($pending_stmt);
        }

        if (!$has_pending_access) {
            echo json_encode(['status' => 'error', 'title' => 'Access Denied', 'message' => 'This request is not pending your approval.', 'type' => 'error']);
            exit;
        }

        $approval_result = handle_approval_action(
            $conDB,
            $request_inv_no,
            'salary_increment',
            (int)$current_user_id,
            'reject',
            $rejection_reason
        );

        if (($approval_result['status'] ?? 'error') === 'error') {
            throw new Exception((string)($approval_result['message'] ?? 'Rejection failed.'));
        }

        if (class_exists('ActivityLogger')) {
            ActivityLogger::logApproval(
                'Salary Increment',
                'ajaxSalaryIncrement.php',
                $request_inv_no,
                'rejected',
                "Rejected salary increment request: {$request_inv_no} - {$rejection_reason}",
                'emp_salary_increment'
            );
        }

        // Notify the submitting supervisor
        $emp_row_stmt = mysqli_prepare($conDB, "SELECT submitted_by FROM emp_salary_increment WHERE request_inv_no = ? LIMIT 1");
        if ($emp_row_stmt) {
            mysqli_stmt_bind_param($emp_row_stmt, 's', $request_inv_no);
            mysqli_stmt_execute($emp_row_stmt);
            $emp_row_res = mysqli_stmt_get_result($emp_row_stmt);
            if ($emp_row_res && ($emp_row = mysqli_fetch_assoc($emp_row_res)) && function_exists('create_browser_notification')) {
                create_browser_notification(
                    $conDB,
                    $emp_row['submitted_by'],
                    'Salary Increment Request Rejected',
                    'Your salary increment request ' . htmlspecialchars($request_inv_no) . ' was rejected. Reason: ' . $rejection_reason,
                    'salary_increment_status_history.php?request_inv_no=' . urlencode($request_inv_no)
                );
            }
            if ($emp_row_res) mysqli_free_result($emp_row_res);
            mysqli_stmt_close($emp_row_stmt);
        }

        echo json_encode([
            'status' => 'success',
            'title' => 'Rejected',
            'message' => 'Salary increment request rejected.',
            'type' => 'success',
            'request_inv_no' => $request_inv_no
        ]);
    } catch (Exception $e) {
        error_log('Salary Increment Rejection Error: ' . $e->getMessage());
        echo json_encode(['status' => 'error', 'title' => 'Rejection Failed', 'message' => 'An error occurred while rejecting request: ' . $e->getMessage(), 'type' => 'error']);
    }
    exit;
}

/**
 * Cancel own salary increment request (submitting supervisor only)
 */
if (isset($_POST['ajaxType']) && $_POST['ajaxType'] === 'cancelSalaryIncrementSelf') {
    try {
        $request_inv_no = trim((string)($_POST['request_inv_no'] ?? ''));
        if ($request_inv_no === '') {
            echo json_encode(['status' => 'error', 'title' => 'Invalid Request', 'message' => 'Invalid salary increment request id.', 'type' => 'error']);
            exit;
        }

        $si_stmt = mysqli_prepare($conDB, "SELECT current_status, submitted_by FROM emp_salary_increment WHERE request_inv_no = ? LIMIT 1");
        mysqli_stmt_bind_param($si_stmt, 's', $request_inv_no);
        mysqli_stmt_execute($si_stmt);
        $si_res = mysqli_stmt_get_result($si_stmt);
        $si_row = $si_res ? mysqli_fetch_assoc($si_res) : null;
        mysqli_stmt_close($si_stmt);

        if (!$si_row) {
            echo json_encode(['status' => 'error', 'title' => 'Not Found', 'message' => 'Salary increment request not found.', 'type' => 'error']);
            exit;
        }

        if ((string)$si_row['submitted_by'] !== (string)$current_user_id) {
            echo json_encode(['status' => 'error', 'title' => 'Access Denied', 'message' => __('you_can_only_cancel_your_own_requests', 'You can only cancel your own requests'), 'type' => 'error']);
            exit;
        }

        if (in_array($si_row['current_status'], ['approved', 'rejected', 'cancelled'], true)) {
            echo json_encode(['status' => 'error', 'title' => 'Invalid Status', 'message' => 'This request in status "' . $si_row['current_status'] . '" cannot be cancelled.', 'type' => 'warning']);
            exit;
        }

        $update_stmt = mysqli_prepare($conDB, "UPDATE emp_salary_increment SET current_status = 'cancelled', modified_by = ?, last_modified = NOW() WHERE request_inv_no = ? AND current_status = ?");
        mysqli_stmt_bind_param($update_stmt, 'iss', $current_user_id, $request_inv_no, $si_row['current_status']);
        mysqli_stmt_execute($update_stmt);
        $affected = mysqli_stmt_affected_rows($update_stmt);
        mysqli_stmt_close($update_stmt);

        if ($affected <= 0) {
            echo json_encode(['status' => 'error', 'title' => 'Error', 'message' => 'Failed to cancel request - status may have changed.', 'type' => 'error']);
            exit;
        }

        $ra_stmt = mysqli_prepare($conDB, "UPDATE request_approvers ra JOIN approval_request_types art ON art.id = ra.request_type_id AND art.type_name = 'salary_increment' SET ra.status = 'cancelled' WHERE ra.request_inv_no = ? AND ra.status IN ('pending', 'awaiting')");
        mysqli_stmt_bind_param($ra_stmt, 's', $request_inv_no);
        mysqli_stmt_execute($ra_stmt);
        mysqli_stmt_close($ra_stmt);

        echo json_encode(['status' => 'success', 'title' => 'Cancelled', 'message' => 'Your salary increment request has been cancelled successfully.', 'type' => 'success']);
        exit;
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'title' => 'Error', 'message' => $e->getMessage(), 'type' => 'error']);
        exit;
    }
}

/**
 * Cancel salary increment request (HR/admin, any status pre-final)
 */
if (isset($_POST['ajaxType']) && $_POST['ajaxType'] === 'cancelSalaryIncrementAdmin') {
    try {
        require_once __DIR__ . '/../special_access_helper.php';

        $can_cancel_any = (
            !empty($is_system_admin)
            || user_has_special_access($conDB, $current_user_id, 'cancel_salary_increment_requests', $user_role ?? '', $user_type ?? '', $is_system_admin ?? false)
        );
        if (!$can_cancel_any) {
            echo json_encode(['status' => 'error', 'title' => 'Access Denied', 'message' => __('access_denied', 'Access denied'), 'type' => 'error']);
            exit;
        }

        $request_inv_no = trim((string)($_POST['request_inv_no'] ?? ''));
        $cancellation_note = trim((string)($_POST['cancellation_note'] ?? ''));
        if ($request_inv_no === '') {
            echo json_encode(['status' => 'error', 'title' => 'Invalid Request', 'message' => 'Invalid salary increment request id.', 'type' => 'error']);
            exit;
        }

        $si_stmt = mysqli_prepare($conDB, "SELECT current_status, submitted_by FROM emp_salary_increment WHERE request_inv_no = ? LIMIT 1");
        mysqli_stmt_bind_param($si_stmt, 's', $request_inv_no);
        mysqli_stmt_execute($si_stmt);
        $si_res = mysqli_stmt_get_result($si_stmt);
        $si_row = $si_res ? mysqli_fetch_assoc($si_res) : null;
        mysqli_stmt_close($si_stmt);

        if (!$si_row) {
            echo json_encode(['status' => 'error', 'title' => 'Not Found', 'message' => 'Salary increment request not found.', 'type' => 'error']);
            exit;
        }

        if (in_array($si_row['current_status'], ['approved', 'rejected', 'cancelled'], true)) {
            echo json_encode(['status' => 'error', 'title' => 'Invalid Status', 'message' => 'This request in status "' . $si_row['current_status'] . '" cannot be cancelled.', 'type' => 'warning']);
            exit;
        }

        $update_stmt = mysqli_prepare($conDB, "UPDATE emp_salary_increment SET current_status = 'cancelled', modified_by = ?, last_modified = NOW() WHERE request_inv_no = ? AND current_status = ?");
        mysqli_stmt_bind_param($update_stmt, 'iss', $current_user_id, $request_inv_no, $si_row['current_status']);
        mysqli_stmt_execute($update_stmt);
        $affected = mysqli_stmt_affected_rows($update_stmt);
        mysqli_stmt_close($update_stmt);

        if ($affected <= 0) {
            echo json_encode(['status' => 'error', 'title' => 'Error', 'message' => 'Failed to cancel request - status may have changed.', 'type' => 'error']);
            exit;
        }

        $ra_stmt = mysqli_prepare($conDB, "UPDATE request_approvers ra JOIN approval_request_types art ON art.id = ra.request_type_id AND art.type_name = 'salary_increment' SET ra.status = 'cancelled' WHERE ra.request_inv_no = ? AND ra.status IN ('pending', 'awaiting')");
        mysqli_stmt_bind_param($ra_stmt, 's', $request_inv_no);
        mysqli_stmt_execute($ra_stmt);
        mysqli_stmt_close($ra_stmt);

        if (function_exists('create_browser_notification')) {
            create_browser_notification(
                $conDB,
                $si_row['submitted_by'],
                'Salary Increment Request Cancelled',
                'Salary increment request ' . htmlspecialchars($request_inv_no) . ' was cancelled by an administrator.' . ($cancellation_note !== '' ? ' Reason: ' . $cancellation_note : ''),
                'salary_increment_status_history.php?request_inv_no=' . urlencode($request_inv_no)
            );
        }

        echo json_encode(['status' => 'success', 'title' => 'Cancelled', 'message' => 'Salary increment request has been cancelled successfully.', 'type' => 'success']);
        exit;
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'title' => 'Error', 'message' => $e->getMessage(), 'type' => 'error']);
        exit;
    }
}

echo json_encode(['status' => 'error', 'message' => 'Invalid ajaxType.']);
exit;
