<?php
/****************************************************************
 * MODIFICATION SUMMARY:
 * 1. TABLE NAME CHANGE: The SQL query has been updated to LEFT JOIN with the `payrolls` table instead of `generated_payrolls` to align with the new database schema.
 ****************************************************************/
// api/get_employees.php
header('Content-Type: application/json');
require_once("./../../includes/db.php"); // Adjust this path as needed

$pdo = getDbConnection();

$monthYear = $_GET['month'] ?? null; // Get the month from the GET request

try {
    // Always include gp.status with a LEFT JOIN.
    // If no matching record in payrolls, gp.status will be NULL.
    $sql = "SELECT
            e.id, e.name, e.emp_id, CAST(e.salary AS DECIMAL(10,2)) as salary, e.dept,
            es.basic, es.housing, es.transport, es.food, es.misc, es.cashier, es.fuel, es.tel, es.other, es.guard,
            gp.status AS payroll_status, -- Always select payroll_status
            d.dep_nme as department_name, -- Include department name for filtering/display
            e.country, -- Include department name for filtering/display
            s.sponsor,
            c.comp_name
        FROM employees e
        LEFT JOIN emp_salary es ON e.emp_id = es.emp_id AND es.status = 1
        LEFT JOIN payrolls gp ON e.emp_id = gp.emp_id AND gp.month_year = :month_year_param
        LEFT JOIN department d ON e.dept = d.id -- Join for department name
        LEFT JOIN sponsorship s ON e.emp_sup_type = s.id
        LEFT JOIN companies c ON e.comp_no = c.comp_id
        WHERE e.status = 1 AND e.fly = 0
        ORDER BY e.dept, e.name
    ";

    $stmt = $pdo->prepare($sql);

    // Bind the month_year parameter. If null, PDO handles it by matching NULL or no specific row.
    // However, for explicit behavior with LEFT JOIN, it's better to bind even if it's NULL,
    // or provide a default value to the query. For this case, a bound NULL works.
    $stmt->bindParam(':month_year_param', $monthYear, PDO::PARAM_STR);

    $stmt->execute();
    $employees = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['status' => 'success', 'employees' => $employees]);

} catch (PDOException $e) {
    error_log('Error fetching employees: ' . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => 'Failed to load employee data. Database error.']);
} catch (Exception $e) {
    error_log('General error in get_employees.php: ' . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => 'An unexpected server error occurred.']);
}
?>
