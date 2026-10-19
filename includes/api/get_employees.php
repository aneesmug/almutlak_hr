<?php
/****************************************************************
 * MODIFICATION SUMMARY:
 * 1. TABLE NAME CHANGE: The SQL query has been updated to LEFT JOIN with the `payrolls` table instead of `generated_payrolls` to align with the new database schema.
 ****************************************************************/
// api/get_employees.php
header('Content-Type: application/json');
require_once("./../../includes/db.php"); // Adjust this path as needed
require_once("./../../includes/session_check.php"); // Include session for user permissions
// Release the session file lock immediately - this endpoint never writes to $_SESSION
// again below. Without this, the payroll page's several concurrent AJAX calls (this,
// get_payroll_approval_status.php, get_available_months.php, etc.) all serialize behind
// PHP's per-session file lock, so this query's own runtime gets added to every other
// request's wait time too, which is what was pushing them all past max_execution_time.
if (session_status() === PHP_SESSION_ACTIVE) {
    session_write_close();
}
require_once("./../../includes/payroll_approval_helpers.php");

// This query joins over vacation/settlement history for every active employee, which
// gets noticeably heavier for months with a full generated/paid roster. The app-wide
// 25s cap (.htaccess) is too tight for that case; raise it just for this endpoint.
set_time_limit(90);

$pdo = getDbConnection();
ensurePayrollChecklistFeedbackTable($pdo);

$monthYear = $_GET['month'] ?? null; // Get the month from the GET request

// DEPARTMENT-BASED ACCESS CONTROL FOR EMPLOYEE LIST
// Determine if user can see all employees or only their department
$can_see_all_employees = (
    function_exists('canSeeAllEmployeesByRole')
        ? canSeeAllEmployeesByRole(true)
        : (
            $is_system_admin ||
            $user_type == 'administrator' ||
            $user_dept == 5 ||
            $isHR ||
            $isDeptHr
        )
);

// Build department filter condition
$dept_filter = "";
$monthStart = null;
$monthEnd = null;
if (!empty($monthYear) && preg_match('/^\d{4}-\d{2}$/', $monthYear)) {
    $monthStart = $monthYear . '-01';
    $monthEnd = date('Y-m-t', strtotime($monthStart));
}

$params = [
    ':month_year_param' => $monthYear,
    ':month_start_param' => $monthStart,
    ':month_end_param' => $monthEnd,
    ':feedback_month_param' => $monthYear
];

// Must mirror the VACATION PAYROLL DROPOUT CHECK in process_payroll.php so the
// listing page doesn't show employees that generation will skip anyway.
// Each skip rule is kept as a bare EXISTS condition + its own params so the same rule can be
// negated for the normal list and matched for the "On Vacation (skipped)" list below.
// Vacation dropout rule lives in includes/payroll_vacation_rules.php.
$vacationDropoutDays = getPayrollVacationDropoutDays($conDB);
$dropoutFilter = "";
$dropoutParams = [];
$dropoutCond = (!empty($monthStart) && !empty($monthEnd))
    ? payrollVacationDropoutCondition($conDB, 'e.emp_id', ':dropout_month_start', ':dropout_month_end')
    : '';
if ($dropoutCond !== '') {
    $dropoutParams = [
        ':dropout_month_end' => $monthEnd,
        ':dropout_month_start' => $monthStart,
    ];
    $dropoutFilter = " AND NOT " . $dropoutCond;
    $params += $dropoutParams;
}

// Double-check: a PAID vacation settlement (special-case early payout) overlapping this
// month means the employee already got paid for that period, regardless of day count.
// LEFT JOIN, not INNER: some settlement_records rows imported from the live server have
// no matching emp_vacation row anymore. For those orphaned settlements, fall back to the
// settlement's own payment_date falling inside the payroll month.
$settlementFilter = "";
$settlementCond = "";
$settlementParams = [];
if (!empty($monthStart) && !empty($monthEnd)) {
    // Paid vacation settlement rule lives in includes/payroll_vacation_rules.php.
    [$settlementCond, $settlementParams] = payrollPaidSettlementCondition('e.emp_id', $monthStart, $monthEnd);
    $settlementFilter = " AND NOT " . $settlementCond;
    $params += $settlementParams;
}

// Native prepares (ATTR_EMULATE_PREPARES = false) can't reuse a named placeholder, so a
// condition used twice in one statement needs its own copy of the placeholders.
function suffixSqlParams($sql, array $params, $suffix) {
    $renamed = [];
    foreach ($params as $key => $value) {
        $newKey = $key . '_' . $suffix;
        $sql = preg_replace('/' . preg_quote($key, '/') . '\b/', $newKey, $sql);
        $renamed[$newKey] = $value;
    }
    return [$sql, $renamed];
}

if (!$can_see_all_employees && isset($user_dept)) {
    $dept_filter = " AND e.dept = :user_dept";
    $params[':user_dept'] = $user_dept;
}

// Once a month has been marked "Paid", its employee list must stay frozen to whoever
// was actually part of that paid run. Without this, an employee added afterwards
// (no payrolls row for this month) would show up as "not generated" inside an already
// closed month - they belong in the next payroll instead.
$newHireFilter = "";
if (!empty($monthYear)) {
    $paidCheckStmt = $pdo->prepare("SELECT 1 FROM payrolls WHERE month_year = :month_year AND status = 'paid' LIMIT 1");
    $paidCheckStmt->execute([':month_year' => $monthYear]);
    if ($paidCheckStmt->fetchColumn()) {
        $newHireFilter = " AND gp.id IS NOT NULL";
    }
}

try {
    // Include only non-Fly active vacations overlapping the payroll month.
    // This keeps Local Annual / Emergency requests visible in payroll until rejoining closes them,
    // while excluding employees on Fly annual vacation from payroll generation.
    $sql = "SELECT /*EXTRA_COLS*/
            e.id, e.name, e.emp_id, CAST(e.salary AS DECIMAL(10,2)) as salary, e.dept, e.payment_type,
            e.joining_date,
            es.basic, es.housing, es.transport, es.food, es.misc, es.cashier, es.fuel, es.tel, es.other, es.guard,
            gp.basic_salary, gp.housing_allowance, gp.transport_allowance, gp.food_allowance, gp.miscellaneous_allowance, gp.cashier_allowance, gp.fuel_allowance, gp.telephone_allowance, gp.other_allowance, gp.guard_allowance,
            gp.status AS payroll_status,
            COALESCE(pcf.open_feedback_count, 0) AS open_feedback_count,
            CASE WHEN COALESCE(pcf.open_feedback_count, 0) > 0 THEN 1 ELSE 0 END AS has_open_feedback,
            d.dep_nme as department_name,
            e.country,
            s.sponsor,
            c.comp_name,
            e.fly,
            v.start_date AS vacation_start_date,
            v.return_date AS vacation_return_date,
            v.vac_type AS vacation_type,
            v.current_status AS vacation_status,
            v.is_deductible as vacation_is_deductible
        FROM employees e
        LEFT JOIN (
            SELECT es1.*
            FROM emp_salary es1
            INNER JOIN (
                SELECT emp_id, MAX(id) AS latest_id
                FROM emp_salary
                WHERE status = 1
                GROUP BY emp_id
            ) es_latest ON es1.id = es_latest.latest_id
        ) es ON e.emp_id = es.emp_id
        LEFT JOIN (
            SELECT gp1.*
            FROM payrolls gp1
            INNER JOIN (
                SELECT emp_id, MAX(id) AS latest_id
                FROM payrolls
                WHERE month_year = :month_year_param
                GROUP BY emp_id
            ) gp_latest ON gp1.id = gp_latest.latest_id
        ) gp ON e.emp_id = gp.emp_id
        LEFT JOIN (
            SELECT emp_id, COUNT(*) AS open_feedback_count
            FROM payroll_checklist_feedback
            WHERE payroll_month = :feedback_month_param
              AND status = 'open'
            GROUP BY emp_id
        ) pcf ON e.emp_id = pcf.emp_id
        LEFT JOIN department d ON e.dept = d.id
        LEFT JOIN sponsorship s ON e.emp_sup_type = s.id
        LEFT JOIN companies c ON e.comp_no = c.comp_id
        LEFT JOIN (
            SELECT v1.*
            FROM emp_vacation v1
            INNER JOIN (
                SELECT emp_id, MAX(id) AS latest_id
                FROM emp_vacation
                WHERE review = 'A'
                  AND current_status IN ('approved', 'completed')
                  AND start_date <= :month_end_param
                  AND return_date >= :month_start_param
                  AND LOWER(COALESCE(vac_type, '')) <> 'fly'
                  AND LOWER(COALESCE(note, '')) <> 'fly'
                GROUP BY emp_id
            ) v_latest ON v1.id = v_latest.latest_id
        ) v ON e.emp_id = v.emp_id
        WHERE e.status = 1 
        AND (
            e.fly = 0 
            OR (e.fly = 1 AND v.id IS NOT NULL)
            OR (v.is_deductible = 0)
        )" . $dept_filter . $newHireFilter;
    $baseSql = $sql;
    $baseParams = array_diff_key($params, $dropoutParams, $settlementParams);
    $sql = str_replace('/*EXTRA_COLS*/', '', $baseSql) . $dropoutFilter . $settlementFilter . "
        ORDER BY e.dept, e.name
    ";

    $stmt = $pdo->prepare($sql);

    // Bind all parameters
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }

    $stmt->execute();
    $employees = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Apply display helpers used by payroll screens.
    foreach ($employees as &$employee) {
        if (isset($employee['name'])) {
            $employee['parsed_name'] = getDisplayName(parseName($employee['name']));
            $employee['name'] = getDisplayName($employee['name']);
        }
    }
    unset($employee); // Break the reference

    $response = ['status' => 'success', 'employees' => $employees];

    // Opt-in only (payroll table's "On Vacation" tile): employees the skip rules above
    // removed from this month's payroll. Returned as a SEPARATE list so every other caller
    // (exports, printing, reports) keeps getting just the normal payroll roster.
    if (!empty($_GET['include_vacation_skipped']) && ($dropoutCond !== '' || $settlementCond !== '')) {
        $skipConds = [];
        $skipCols = [];
        $skipParams = $baseParams;
        foreach (['dropout' => [$dropoutCond, $dropoutParams], 'settlement' => [$settlementCond, $settlementParams]] as $name => [$cond, $condParams]) {
            if ($cond === '') {
                $skipCols[] = "0 AS skip_by_{$name},";
                continue;
            }
            [$colSql, $colParams] = suffixSqlParams($cond, $condParams, 'col');
            [$whereSql, $whereParams] = suffixSqlParams($cond, $condParams, 'whr');
            $skipCols[] = "{$colSql} AS skip_by_{$name},";
            $skipConds[] = $whereSql;
            $skipParams += $colParams + $whereParams;
        }
        // The vacation that caused the skip (longest approved VAC-* overlapping the month).
        $skipCols[] = "(SELECT CONCAT_WS('|', x.request_inv_no, COALESCE(x.vac_type, ''), x.start_date, COALESCE(x.return_date, ''), COALESCE(x.vacdays, 0))
                FROM emp_vacation x
                WHERE x.emp_id = e.emp_id
                    AND x.review = 'A'
                    AND x.current_status IN ('approved', 'completed')
                    AND x.request_inv_no LIKE 'VAC-%'
                    AND x.start_date <= :skip_info_month_end
                    AND COALESCE(x.return_date, x.start_date) >= :skip_info_month_start
                ORDER BY COALESCE(x.vacdays, 0) DESC, x.id DESC
                LIMIT 1) AS skip_vacation_info,";
        $skipParams[':skip_info_month_end'] = $monthEnd;
        $skipParams[':skip_info_month_start'] = $monthStart;

        $skipSql = str_replace('/*EXTRA_COLS*/', implode("\n", $skipCols), $baseSql)
            . " AND (" . implode(' OR ', $skipConds) . ")
            ORDER BY e.dept, e.name";
        $skipStmt = $pdo->prepare($skipSql);
        foreach ($skipParams as $key => $value) {
            $skipStmt->bindValue($key, $value);
        }
        $skipStmt->execute();
        $skipped = $skipStmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($skipped as &$employee) {
            if (isset($employee['name'])) {
                $employee['parsed_name'] = getDisplayName(parseName($employee['name']));
                $employee['name'] = getDisplayName($employee['name']);
            }
            $info = !empty($employee['skip_vacation_info']) ? explode('|', $employee['skip_vacation_info']) : [];
            $employee['skip_vacation_inv_no'] = $info[0] ?? null;
            $employee['skip_vacation_type'] = $info[1] ?? null;
            $employee['skip_vacation_start'] = $info[2] ?? null;
            $employee['skip_vacation_return'] = $info[3] ?? null;
            $employee['skip_vacation_days'] = isset($info[4]) ? (int) $info[4] : null;
            unset($employee['skip_vacation_info']);
            $employee['skip_by_dropout'] = (int) $employee['skip_by_dropout'];
            $employee['skip_by_settlement'] = (int) $employee['skip_by_settlement'];
            $employee['skip_reason'] = $employee['skip_by_settlement'] ? 'settlement_paid' : 'vacation_dropout';
            $employee['vacation_skipped'] = 1;
        }
        unset($employee);

        $response['vacation_skipped_employees'] = $skipped;
        $response['vacation_dropout_days'] = $vacationDropoutDays;
    }

    echo json_encode($response);

} catch (PDOException $e) {
    error_log('Error fetching employees: ' . $e->getMessage());
    error_log('SQL Query: ' . ($sql ?? 'N/A'));
    error_log('Parameters: ' . print_r($params ?? [], true));
    
    // In development mode, show detailed error
    if (get_setting($conDB, 'developer_mode') == '1') {
        echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage(), 'sql' => $sql ?? 'N/A']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to load employee data. Database error.']);
    }
} catch (Exception $e) {
    error_log('General error in get_employees.php: ' . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => 'An unexpected server error occurred.']);
}
?>
