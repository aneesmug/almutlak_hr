<?php
/****************************************************************
 * MODIFICATION SUMMARY:
 * 1. TABLE NAME CHANGE: The SQL query has been updated to LEFT JOIN with the `payrolls` table instead of `generated_payrolls` to align with the new database schema.
 ****************************************************************/
// api/get_employees.php
header('Content-Type: application/json');
require_once("./../../includes/db.php"); // Adjust this path as needed
require_once("./../../includes/session_check.php"); // Include session for user permissions

$pdo = getDbConnection();

$monthYear = $_GET['month'] ?? null; // Get the month from the GET request

// DEPARTMENT-BASED ACCESS CONTROL FOR EMPLOYEE LIST
// Determine if user can see all employees or only their department
$can_see_all_employees = (
    $is_system_admin || 
    $user_type == 'administrator' ||
    $user_dept == 5 || // HR Department
    $isHR || 
    $isDeptHr
);

// Build department filter condition
$dept_filter = "";
$params = [
    ':month_year_param' => $monthYear,
    ':month_year_param2' => $monthYear
];

if (!$can_see_all_employees && isset($user_dept)) {
    $dept_filter = " AND e.dept = :user_dept";
    $params[':user_dept'] = $user_dept;
}

try {
    // Modified to include employees on vacation (fly=1) if they have a vacation starting this month
    // This allows us to generate payroll for their working days before vacation
    // ALSO include employees with is_deductible=0 (Fly+Annual) who remain in full payroll
    $sql = "SELECT DISTINCT
            e.id, e.name, e.emp_id, CAST(e.salary AS DECIMAL(10,2)) as salary, e.dept, 
            es.basic, es.housing, es.transport, es.food, es.misc, es.cashier, es.fuel, es.tel, es.other, es.guard,
            gp.basic_salary, gp.housing_allowance, gp.transport_allowance, gp.food_allowance, gp.miscellaneous_allowance, gp.cashier_allowance, gp.fuel_allowance, gp.telephone_allowance, gp.other_allowance, gp.guard_allowance,
            gp.status AS payroll_status,
            d.dep_nme as department_name,
            e.country,
            s.sponsor,
            c.comp_name,
            e.fly,
            v.is_deductible as vacation_is_deductible
        FROM employees e
        LEFT JOIN emp_salary es ON e.emp_id = es.emp_id AND es.status = 1
        LEFT JOIN payrolls gp ON e.emp_id = gp.emp_id AND gp.month_year = :month_year_param
        LEFT JOIN department d ON e.dept = d.id
        LEFT JOIN sponsorship s ON e.emp_sup_type = s.id
        LEFT JOIN companies c ON e.comp_no = c.comp_id
        LEFT JOIN emp_vacation v ON e.emp_id = v.emp_id 
            AND v.current_status = 'approved'
            AND DATE_FORMAT(v.start_date, '%Y-%m') = :month_year_param2
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
