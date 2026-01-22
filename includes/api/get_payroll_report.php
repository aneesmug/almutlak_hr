<?php
// api/get_payroll_report.php
header('Content-Type: application/json');

/****************************************************************
 * MODIFICATION SUMMARY:
 * 1. TABLE NAME CHANGE: The main SQL query has been updated to select from the `payrolls` table (aliased as `gp`) instead of `generated_payrolls` to align with the new database schema.
 * 2. Fetches Detailed Benefits: After getting the main payroll list, this script now loops through each employee.
 * 3. Fetches Detailed Deductions: For each employee, it runs additional queries to get all their individual benefits and deductions for the specified month.
 * 4. Nests Data: The fetched benefits and deductions (with notes) are added as new nested arrays ('benefits_list' and 'deductions_list') to each employee's data object.
 * 5. This provides the frontend with all the necessary data to build the detailed checklist view without needing extra API calls.
 * 6. (NEW) Enhanced Benefit Fetching: The query for benefits now joins with the `benefit_types` table. This ensures that the correct name for predefined benefits is fetched, falling back to the text in the `benefit` column for manually added entries.
 ****************************************************************/

require_once("./../../includes/db.php"); 
require_once("./../../includes/session_check.php"); // Include session to get user permissions

$pdo = getDbConnection();

$monthYear = $_GET['month'] ?? null;
$companyFilter = $_GET['company'] ?? null; // Get optional company filter

if (!$monthYear) {
    echo json_encode(['status' => 'error', 'message' => 'Month parameter is required.']);
    exit;
}

// DEPARTMENT-BASED ACCESS CONTROL FOR PAYROLL REPORT
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
$company_filter = "";
$params = [':month_year_param' => $monthYear];

if (!$can_see_all_employees && isset($user_dept)) {
    $dept_filter = " AND e.dept = :user_dept";
    $params[':user_dept'] = $user_dept;
}

// Add company filter if specified
if (!empty($companyFilter)) {
    $company_filter = " AND c.comp_name = :company_name";
    $params[':company_name'] = $companyFilter;
}

try {
    // Main query to get the generated payroll summary for all employees for the month
    $sql = "SELECT
                gp.id AS payroll_id,
                gp.emp_id,
                e.iqama,
                e.name AS employee_name,
                e.iban,
                e.payment_type,
                e.dept,
                d.dep_nme AS department_name,
                c.comp_name,
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
            FROM payrolls gp
            JOIN employees e ON gp.emp_id = e.emp_id
            LEFT JOIN department d ON e.dept = d.id
            LEFT JOIN companies c ON e.comp_no = c.comp_id
            LEFT JOIN bank_list bl ON bl.bnk_id = e.bank_name
            LEFT JOIN sponsorship s ON e.emp_sup_type = s.id
            WHERE gp.month_year = :month_year_param" . $dept_filter . $company_filter . "
            ORDER BY c.comp_name ASC, d.dep_nme ASC, e.name ASC";

    $stmt = $pdo->prepare($sql);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->execute();
    $reportData = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Prepare statements for fetching details, to be reused inside the loop for efficiency
    // MODIFIED: This query now joins with benefit_types to get the accurate benefit name.
    $stmtBenefits = $pdo->prepare("
        SELECT 
            CASE 
                WHEN pb.type_id IS NOT NULL AND bt.name IS NOT NULL THEN bt.name
                ELSE pb.benefit 
            END AS benefit, 
            pb.note,
            pb.hours,
            pb.days,
            pb.calculation_type
        FROM payroll_benefits pb
        LEFT JOIN benefit_types bt ON pb.type_id = bt.id
        WHERE pb.emp_id = :emp_id AND pb.month = :month_year AND pb.status = 1
    ");
    // Query now includes calculation_type, hours, and days from payroll_deductions
    $stmtDeductions = $pdo->prepare("
        SELECT deduction, note, hours, days, calculation_type 
        FROM payroll_deductions 
        WHERE emp_id = :emp_id AND month = :month_year AND status = 1
    ");

    // Loop through each main payroll record to fetch and attach the detailed checklist items
    foreach ($reportData as $key => $row) {
        $empId = $row['emp_id'];

        // Fetch all benefits for this employee and month
        $stmtBenefits->execute([':emp_id' => $empId, ':month_year' => $monthYear]);
        // Add the results as a nested array to the main report data
        $reportData[$key]['benefits_list'] = $stmtBenefits->fetchAll(PDO::FETCH_ASSOC);

        // Fetch all deductions for this employee and month
        $stmtDeductions->execute([':emp_id' => $empId, ':month_year' => $monthYear]);
        // Add the results as a nested array to the main report data
        $reportData[$key]['deductions_list'] = $stmtDeductions->fetchAll(PDO::FETCH_ASSOC);
    }

    echo json_encode(['status' => 'success', 'report' => $reportData]);

} catch (PDOException $e) {
    // error_log('Error fetching payroll report: ' . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => 'Database error: Could not retrieve payroll report data.']);
} catch (Exception $e) {
    // error_log('General error in get_payroll_report.php: ' . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => 'An unexpected server error occurred.']);
}
?>
