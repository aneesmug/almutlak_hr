<?php
// api/get_payroll_report.php
header('Content-Type: application/json');

// Adjust this path based on where db.php is relative to this file
require_once("./../../../includes/db.php"); // Example path

$pdo = getDbConnection();

$monthYear = $_GET['month'] ?? null; // Get the month from the GET request

try {
    // Corrected SQL query to include WHERE clause for month filtering
    $sql = "SELECT
                gp.emp_id,
                e.iqama,
                e.name AS employee_name,
                e.iban,
                d.dep_nme AS department_name,
                bl.bank_name_s,
                s.sponsor,
                gp.month_year,
                gp.basic_salary,
                gp.housing_allowance,
                gp.transport_allowance,
                gp.food_allowance,
                gp.miscellaneous_allowance,
                gp.cashier_allowance,
                gp.fuel_allowance,
                gp.telephone_allowance,
                gp.other_allowance,
                gp.guard_allowance,
                gp.total_gross_salary,
                gp.total_benefits,
                gp.total_deductions,
                gp.net_salary,
                gp.status
            FROM generated_payrolls gp
            JOIN employees e ON gp.emp_id = e.emp_id
            LEFT JOIN department d ON e.dept = d.id
            LEFT JOIN bank_list bl ON bl.bnk_id = e.bank_name
            LEFT JOIN sponsorship s ON e.emp_sup_type = s.id";
    
    // Add WHERE clause only if a month is provided
    if ($monthYear) {
        $sql .= " WHERE gp.month_year = :month_year_param";
    }

    $sql .= " ORDER BY gp.month_year DESC, d.dep_nme ASC, e.name ASC";

    $stmt = $pdo->prepare($sql);
    
    // Bind parameter only if it exists
    if ($monthYear) {
        $stmt->bindParam(':month_year_param', $monthYear, PDO::PARAM_STR);
    }
    
    $stmt->execute();
    $reportData = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['status' => 'success', 'report' => $reportData]);

} catch (PDOException $e) {
    error_log('Error fetching payroll report: ' . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => 'Database error: Could not retrieve payroll report data.']);
} catch (Exception $e) {
    error_log('General error in get_payroll_report.php: ' . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => 'An unexpected server error occurred.']);
}
?>
