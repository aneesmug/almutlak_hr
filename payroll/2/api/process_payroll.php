<?php
header('Content-Type: application/json');
require_once("./../../../includes/db.php"); // Adjust path as necessary

$input = json_decode(file_get_contents('php://input'), true);

$employeeIds = $input['employee_ids'] ?? [];
$monthYear = $input['month'] ?? '';

if (empty($employeeIds) || empty($monthYear)) {
    error_log('Error: Missing employee IDs or month for payroll processing. Input: ' . json_encode($input));
    echo json_encode(['status' => 'error', 'message' => 'Missing employee IDs or month.']);
    exit();
}

$pdo = getDbConnection();

try {
    $pdo->beginTransaction();
    $processedCount = 0;

    foreach ($employeeIds as $empId) {
        // 1. Get salary components and employee country
        $stmtEmployeeData = $pdo->prepare("
            SELECT es.basic, es.housing, es.transport, es.food, es.misc, es.cashier, es.fuel, es.tel, es.other, es.guard, e.country
            FROM emp_salary es
            JOIN employees e ON es.emp_id = e.emp_id
            WHERE es.emp_id = :emp_id
        ");
        $stmtEmployeeData->execute([':emp_id' => $empId]);
        $employeeData = $stmtEmployeeData->fetch(PDO::FETCH_ASSOC);

        if (!$employeeData) {
            error_log("No salary components or employee data found for employee ID: $empId. Skipping.");
            continue; // Skip this employee if data is missing
        }

        $salaryComponents = [
            'basic' => $employeeData['basic'],
            'housing' => $employeeData['housing'],
            'transport' => $employeeData['transport'],
            'food' => $employeeData['food'],
            'misc' => $employeeData['misc'],
            'cashier' => $employeeData['cashier'],
            'fuel' => $employeeData['fuel'],
            'tel' => $employeeData['tel'],
            'other' => $employeeData['other'],
            'guard' => $employeeData['guard']
        ];
        $employeeCountry = $employeeData['country'];

        // Calculate gross salary by summing up all numeric components
        $totalGrossSalary = 0;
        foreach ($salaryComponents as $component) {
            $totalGrossSalary += floatval($component);
        }

        // --- GOSI Automatic Deduction Logic (New Addition) ---
        if ($employeeCountry === '191') {
            $basicPlusHousing = floatval($salaryComponents['basic']) + floatval($salaryComponents['housing']);
            $gosiAmount = round($basicPlusHousing * 0.0975, 2); // Calculate 9.75% of basic + housing, round to 2 decimal places

            // Check if GOSI deduction already exists for this employee and month
            $stmtCheckGOSI = $pdo->prepare("
                SELECT id FROM payroll_deductions
                WHERE emp_id = :emp_id AND deduction = 'GOSI' AND month = :month_year
            ");
            $stmtCheckGOSI->execute([
                ':emp_id' => $empId,
                ':month_year' => $monthYear
            ]);
            $existingGosiId = $stmtCheckGOSI->fetchColumn();

            if ($existingGosiId) {
                // Update existing GOSI deduction
                $stmtUpdateGOSI = $pdo->prepare("
                    UPDATE payroll_deductions
                    SET note = :gosi_amount, status = 1 -- Ensure status is active
                    WHERE id = :id
                ");
                $stmtUpdateGOSI->execute([
                    ':gosi_amount' => number_format($gosiAmount, 2, '.', ''),
                    ':id' => $existingGosiId
                ]);
                error_log("Updated GOSI deduction for emp_id=$empId, month=$monthYear, amount=$gosiAmount");
            } else {
                // Insert new GOSI deduction
                $stmtInsertGOSI = $pdo->prepare("
                    INSERT INTO payroll_deductions (emp_id, deduction, note, month, status)
                    VALUES (:emp_id, 'GOSI', :gosi_amount, :month_year, 1)
                ");
                $stmtInsertGOSI->execute([
                    ':emp_id' => $empId,
                    ':gosi_amount' => number_format($gosiAmount, 2, '.', ''),
                    ':month_year' => $monthYear
                ]);
                error_log("Inserted new GOSI deduction for emp_id=$empId, month=$monthYear, amount=$gosiAmount");
            }
        }
        // --- End GOSI Automatic Deduction Logic ---

        // 2. Get benefits total for the specific month (after GOSI might have been handled)
        $stmtBenefitsSum = $pdo->prepare("
            SELECT COALESCE(SUM(CAST(note AS DECIMAL(10,2))), 0) as total_benefits
            FROM payroll_benefits
            WHERE emp_id = :emp_id AND month = :month_year AND status = 1
        ");
        $stmtBenefitsSum->execute([':emp_id' => $empId, ':month_year' => $monthYear]);
        $totalBenefits = (float)$stmtBenefitsSum->fetchColumn();

        // 3. Get deductions total for the specific month (after GOSI might have been handled)
        $stmtDeductionsSum = $pdo->prepare("
            SELECT COALESCE(SUM(CAST(note AS DECIMAL(10,2))), 0) as total_deductions
            FROM payroll_deductions
            WHERE emp_id = :emp_id AND month = :month_year AND status = 1
        ");
        $stmtDeductionsSum->execute([':emp_id' => $empId, ':month_year' => $monthYear]);
        $totalDeductions = (float)$stmtDeductionsSum->fetchColumn();

        // Calculate net salary
        $netSalary = $totalGrossSalary + $totalBenefits - $totalDeductions;

        // 4. Insert/update payroll record in generated_payrolls table
        $stmt = $pdo->prepare("INSERT INTO generated_payrolls (
                emp_id, month_year, basic_salary, housing_allowance, transport_allowance,
                food_allowance, miscellaneous_allowance, cashier_allowance, fuel_allowance,
                telephone_allowance, other_allowance, guard_allowance, total_gross_salary,
                total_benefits, total_deductions, net_salary, status
            ) VALUES (
                :emp_id, :month_year, :basic, :housing, :transport,
                :food, :misc, :cashier, :fuel,
                :tel, :other, :guard, :total_gross_salary,
                :total_benefits, :total_deductions, :net_salary, 'generated'
            ) ON DUPLICATE KEY UPDATE
                basic_salary = VALUES(basic_salary),
                housing_allowance = VALUES(housing_allowance),
                transport_allowance = VALUES(transport_allowance),
                food_allowance = VALUES(food_allowance),
                miscellaneous_allowance = VALUES(miscellaneous_allowance),
                cashier_allowance = VALUES(cashier_allowance),
                fuel_allowance = VALUES(fuel_allowance),
                telephone_allowance = VALUES(telephone_allowance),
                other_allowance = VALUES(other_allowance),
                guard_allowance = VALUES(guard_allowance),
                total_gross_salary = VALUES(total_gross_salary),
                total_benefits = VALUES(total_benefits),
                total_deductions = VALUES(total_deductions),
                net_salary = VALUES(net_salary),
                status = VALUES(status)
        ");

        $stmt->execute([
            ':emp_id' => $empId,
            ':month_year' => $monthYear,
            ':basic' => number_format($salaryComponents['basic'], 2, '.', ''),
            ':housing' => number_format($salaryComponents['housing'], 2, '.', ''),
            ':transport' => number_format($salaryComponents['transport'], 2, '.', ''),
            ':food' => number_format($salaryComponents['food'], 2, '.', ''),
            ':misc' => number_format($salaryComponents['misc'], 2, '.', ''),
            ':cashier' => number_format($salaryComponents['cashier'], 2, '.', ''),
            ':fuel' => number_format($salaryComponents['fuel'], 2, '.', ''),
            ':tel' => number_format($salaryComponents['tel'], 2, '.', ''),
            ':other' => number_format($salaryComponents['other'], 2, '.', ''),
            ':guard' => number_format($salaryComponents['guard'], 2, '.', ''),
            ':total_gross_salary' => number_format($totalGrossSalary, 2, '.', ''),
            ':total_benefits' => number_format($totalBenefits, 2, '.', ''),
            ':total_deductions' => number_format($totalDeductions, 2, '.', ''),
            ':net_salary' => number_format($netSalary, 2, '.', '')
        ]);

        $processedCount++; 
    }

    $pdo->commit();
    echo json_encode([
        'status' => 'success', 
        'message' => "Payroll processed for $processedCount employees, including automatic GOSI deductions where applicable.",
        'processed_count' => $processedCount
    ]);

} catch (Exception $e) {
    $pdo->rollBack();
    error_log('Payroll processing error in process_payroll.php: ' . $e->getMessage() . ' - File: ' . $e->getFile() . ' - Line: ' . $e->getLine());
    echo json_encode(['status' => 'error', 'message' => 'Processing failed: ' . $e->getMessage()]);
}
?>
