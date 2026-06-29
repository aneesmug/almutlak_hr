<?php
/****************************************************************
 * MODIFICATION SUMMARY:
 * 1. TABLE NAME CHANGE: The SQL query has been updated to LEFT JOIN with the `payrolls` table instead of `generated_payrolls` to align with the new database schema.
 ****************************************************************/
// api/get_employees.php
header('Content-Type: application/json');
require_once("./../../includes/db.php"); // Adjust this path as needed
require_once("./../../includes/session_check.php"); // Include session for user permissions
require_once("./../../includes/payroll_approval_helpers.php");

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

if (!$can_see_all_employees && isset($user_dept)) {
    $dept_filter = " AND e.dept = :user_dept";
    $params[':user_dept'] = $user_dept;
}

try {
    // Include only non-Fly active vacations overlapping the payroll month.
    // This keeps Local Annual / Emergency requests visible in payroll until rejoining closes them,
    // while excluding employees on Fly annual vacation from payroll generation.
    $sql = "SELECT
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
        )" . $dept_filter . "
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

    echo json_encode(['status' => 'success', 'employees' => $employees]);

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
