<?php
/****************************************************************
 * MODIFICATION SUMMARY:
 * 1. TABLE NAME CHANGE: The SQL query for fetching payroll details has been updated to select from the `payrolls` table instead of `generated_payrolls` to align with the new database schema.
 ****************************************************************/
// get_payroll_details.php
header('Content-Type: application/json');
require_once("./../../includes/db.php"); // Include your database connection file

$empId = $_GET['emp_id'] ?? '';
$monthYear = $_GET['month'] ?? ''; // Expected format: YYYY-MM

if (empty($empId) || empty($monthYear)) {
    echo json_encode(['status' => 'error', 'message' => 'Missing employee ID or month for details.']);
    exit();
}

$pdo = getDbConnection();

try {
    // 1. Fetch employee details
    $stmtEmployee = $pdo->prepare("SELECT id, name, emp_id, salary, dept, country, gosi
        FROM employees
        WHERE emp_id = :emp_id
    ");
    $stmtEmployee->execute([':emp_id' => $empId]);
    $employee = $stmtEmployee->fetch(PDO::FETCH_ASSOC);

    if (!$employee) {
        echo json_encode(['status' => 'error', 'message' => 'Employee not found.']);
        exit();
    }

    // 2. Fetch generated payroll details
    $stmtPayroll = $pdo->prepare("SELECT
            basic_salary, housing_allowance, transport_allowance, food_allowance,
            miscellaneous_allowance, cashier_allowance, fuel_allowance, telephone_allowance,
            other_allowance, guard_allowance, total_gross_salary, total_benefits,
            total_deductions, net_salary, status
        FROM payrolls
        WHERE emp_id = :emp_id AND month_year = :month_year
    ");
    $stmtPayroll->execute([':emp_id' => $empId, ':month_year' => $monthYear]);
    $payroll = $stmtPayroll->fetch(PDO::FETCH_ASSOC);

    if (!$payroll) {
        // If no generated payroll, fetch basic salary components
        $stmtEmpSalary = $pdo->prepare("SELECT basic, housing, transport, food, misc, cashier, fuel, tel, other, guard
            FROM emp_salary WHERE emp_id = :emp_id AND status = 1
        ");
        $stmtEmpSalary->execute([':emp_id' => $empId]);
        $empSalaryData = $stmtEmpSalary->fetch(PDO::FETCH_ASSOC);
        if ($empSalaryData) {
            // Populate payroll with basic components if no generated payroll exists yet
            $payroll = [
                'basic_salary' => (float)$empSalaryData['basic'],
                'housing_allowance' => (float)$empSalaryData['housing'],
                'transport_allowance' => (float)$empSalaryData['transport'],
                'food_allowance' => (float)$empSalaryData['food'],
                'miscellaneous_allowance' => (float)$empSalaryData['misc'],
                'cashier_allowance' => (float)$empSalaryData['cashier'],
                'fuel_allowance' => (float)$empSalaryData['fuel'],
                'telephone_allowance' => (float)$empSalaryData['tel'],
                'other_allowance' => (float)$empSalaryData['other'],
                'guard_allowance' => (float)$empSalaryData['guard'],
                'total_gross_salary' => (float)$empSalaryData['basic'] + (float)$empSalaryData['housing'] + (float)$empSalaryData['transport'] + (float)$empSalaryData['food'] + (float)$empSalaryData['misc'] + (float)$empSalaryData['cashier'] + (float)$empSalaryData['fuel'] + (float)$empSalaryData['tel'] + (float)$empSalaryData['other'] + (float)$empSalaryData['guard'],
                'total_benefits' => 0.00,
                'total_deductions' => 0.00,
                'net_salary' => (float)$employee['salary'], // Default to basic salary or calculate from components
                'status' => 'not_generated'
            ];
        } else {
             // Handle case where employee has no salary data either
            echo json_encode(['status' => 'error', 'message' => 'No salary data found for this employee.']);
            exit();
        }
    }

    // 3. Fetch specific benefits for the month
    $stmtBenefits = $pdo->prepare("SELECT id, benefit, note FROM payroll_benefits
        WHERE emp_id = :emp_id AND month = :month_year
    ");
    $stmtBenefits->execute([':emp_id' => $empId, ':month_year' => $monthYear]);
    $benefits = $stmtBenefits->fetchAll(PDO::FETCH_ASSOC);

    // 4. Fetch specific deductions for the month
    $stmtDeductions = $pdo->prepare("SELECT id, deduction, note FROM payroll_deductions
        WHERE emp_id = :emp_id AND month = :month_year
    ");

    $stmtDeductions->execute([':emp_id' => $empId, ':month_year' => $monthYear]);
    $deductions = $stmtDeductions->fetchAll(PDO::FETCH_ASSOC);

    // 5. Fetch benefit types (Corrected from before)
    $stmtBenefitTypes = $pdo->prepare("SELECT id, name, calculation_type FROM benefit_types WHERE status = 1");
    $stmtBenefitTypes->execute();
    $benefitTypes = $stmtBenefitTypes->fetchAll(PDO::FETCH_ASSOC);
    

    echo json_encode([
        'status' => 'success',
        'employee' => $employee,
        'payroll' => $payroll,
        'benefits' => $benefits,
        'deductions' => $deductions,
        'benefit_types' => $benefitTypes,
    ]);

} catch (PDOException $e) {
    error_log('Error fetching payroll details: ' . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => 'Failed to load payroll details.']);
}
?>
