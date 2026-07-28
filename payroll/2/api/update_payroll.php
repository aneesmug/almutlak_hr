<?php
header('Content-Type: application/json');
require_once("./../../../includes/db.php");

function hasVacationGosiDeductedForMonth(PDO $pdo, $empId, $monthYear) {
    $monthStart = $monthYear . '-01';
    $monthEnd = date('Y-m-t', strtotime($monthStart));

    $stmt = $pdo->prepare("SELECT v.id
        FROM emp_vacation v
        INNER JOIN employees e ON e.emp_id = v.emp_id
        WHERE v.emp_id = :emp_id
          AND e.country = 191
          AND COALESCE(v.auto_gosi_deduction, 1) = 1
          AND v.review = 'A'
          AND v.current_status IN ('approved', 'completed')
          AND v.start_date <= :month_end
          AND COALESCE(v.return_date, v.start_date) >= :month_start
          AND (
                (
                    LOWER(COALESCE(v.vac_type, '')) = 'fly'
                    AND LOWER(COALESCE(v.fly_type, '')) = 'annual'
                    AND LOWER(COALESCE(v.vacation_salary_type, '')) = 'payroll'
                )
                OR
                (
                    LOWER(COALESCE(v.vac_type, '')) = 'local vacation'
                    AND LOWER(COALESCE(v.fly_type, '')) = 'annual'
                    AND COALESCE(v.vacdays, 0) >= 20
                    AND LOWER(COALESCE(v.vacation_salary_type, '')) = 'payroll'
                )
          )
        LIMIT 1");
    $stmt->execute([
        ':emp_id' => $empId,
        ':month_start' => $monthStart,
        ':month_end' => $monthEnd
    ]);

    return (bool)$stmt->fetch(PDO::FETCH_ASSOC);
}

$input = json_decode(file_get_contents('php://input'), true);

$empId = $input['emp_id'] ?? '';
$monthYear = $input['month'] ?? '';
$updatedBenefits = $input['benefits'] ?? [];
$updatedDeductions = $input['deductions'] ?? [];

if (empty($empId) || empty($monthYear)) {
    echo json_encode(['status' => 'error', 'message' => 'Missing employee ID or month for update.']);
    exit();
}

$pdo = getDbConnection();

try {
    $pdo->beginTransaction();

    $skipAutoGosiDueToVacation = hasVacationGosiDeductedForMonth($pdo, $empId, $monthYear);
    if ($skipAutoGosiDueToVacation) {
        $stmtDeleteAutoGosi = $pdo->prepare("DELETE FROM payroll_deductions
            WHERE emp_id = :emp_id AND month = :month_year AND deduction = 'GOSI'");
        $stmtDeleteAutoGosi->execute([':emp_id' => $empId, ':month_year' => $monthYear]);
    }

    // --- Update Benefits ---
    foreach ($updatedBenefits as $benefit) {
        $benefitName = trim($benefit['name'] ?? '');
        $benefitAmount = (float)($benefit['amount'] ?? 0);
        $benefitId = $benefit['id'] ?? null;

        if ($benefitAmount <= 0 && empty($benefitName)) continue;

        if ($benefitId) {
            $stmt = $pdo->prepare("
                UPDATE payroll_benefits
                SET benefit = :benefit_name, note = :benefit_amount
                WHERE id = :id AND emp_id = :emp_id AND month = :month_year
            ");
            $stmt->execute([
                ':benefit_name' => $benefitName,
                ':benefit_amount' => number_format($benefitAmount, 2, '.', ''),
                ':id' => $benefitId,
                ':emp_id' => $empId,
                ':month_year' => $monthYear
            ]);
        } else {
            $stmt = $pdo->prepare("
                INSERT INTO payroll_benefits (emp_id, benefit, note, month, status)
                VALUES (:emp_id, :benefit_name, :benefit_amount, :month_year, 1)
            ");
            $stmt->execute([
                ':emp_id' => $empId,
                ':benefit_name' => $benefitName,
                ':benefit_amount' => number_format($benefitAmount, 2, '.', ''),
                ':month_year' => $monthYear
            ]);
        }
    }

    // --- Update Deductions ---
    foreach ($updatedDeductions as $deduction) {
        $deductionName = trim($deduction['name'] ?? '');
        $deductionAmount = (float)($deduction['amount'] ?? 0);
        $deductionId = $deduction['id'] ?? null;

        if ($deductionAmount <= 0 && empty($deductionName)) continue;

        if ($skipAutoGosiDueToVacation && strtoupper($deductionName) === 'GOSI') {
            continue;
        }

        if ($deductionId) {
            $stmt = $pdo->prepare("
                UPDATE payroll_deductions
                SET deduction = :deduction_name, note = :deduction_amount
                WHERE id = :id AND emp_id = :emp_id AND month = :month_year
            ");
            $stmt->execute([
                ':deduction_name' => $deductionName,
                ':deduction_amount' => number_format($deductionAmount, 2, '.', ''),
                ':id' => $deductionId,
                ':emp_id' => $empId,
                ':month_year' => $monthYear
            ]);
        } else {
            $stmt = $pdo->prepare("
                INSERT INTO payroll_deductions (emp_id, deduction, note, month, status)
                VALUES (:emp_id, :deduction_name, :deduction_amount, :month_year, 1)
            ");
            $stmt->execute([
                ':emp_id' => $empId,
                ':deduction_name' => $deductionName,
                ':deduction_amount' => number_format($deductionAmount, 2, '.', ''),
                ':month_year' => $monthYear
            ]);
        }
    }

    // --- Recalculate Totals ---
    // 1. Get salary components (ensure they exist)
    $stmtSalary = $pdo->prepare("SELECT basic, housing, transport, food, misc, cashier, fuel, tel, other, guard
        FROM emp_salary
        WHERE emp_id = :emp_id
    ");
    $stmtSalary->execute([':emp_id' => $empId]);
    $salaryComponents = $stmtSalary->fetch(PDO::FETCH_ASSOC);

    if (!$salaryComponents) {
        throw new Exception("No salary components found for employee ID: $empId");
    }

    // Calculate gross salary from components
    $totalGrossSalary = array_sum(array_map('floatval', $salaryComponents));

    // 2. Calculate total benefits
    $stmtBenefitsSum = $pdo->prepare("SELECT COALESCE(SUM(CAST(note AS DECIMAL(10,2))), 0) as total_benefits_sum
        FROM payroll_benefits
        WHERE emp_id = :emp_id AND month = :month_year
    ");
    $stmtBenefitsSum->execute([':emp_id' => $empId, ':month_year' => $monthYear]);
    $totalBenefits = (float)$stmtBenefitsSum->fetchColumn();

    // 3. Calculate total deductions
    $stmtDeductionsSum = $pdo->prepare("SELECT COALESCE(SUM(CAST(note AS DECIMAL(10,2))), 0) as total_deductions_sum
        FROM payroll_deductions
        WHERE emp_id = :emp_id AND month = :month_year
    ");
    $stmtDeductionsSum->execute([':emp_id' => $empId, ':month_year' => $monthYear]);
    $totalDeductions = (float)$stmtDeductionsSum->fetchColumn();

    // Calculate net salary
    $netSalary = $totalGrossSalary + $totalBenefits - $totalDeductions;

    // Update generated_payrolls
    $stmtUpdateGenerated = $pdo->prepare("INSERT INTO generated_payrolls (
            emp_id, month_year, basic_salary, housing_allowance, transport_allowance,
            food_allowance, miscellaneous_allowance, cashier_allowance, fuel_allowance,
            telephone_allowance, other_allowance, guard_allowance, total_gross_salary,
            total_benefits, total_deductions, net_salary, status
        ) VALUES (
            :emp_id, :month_year, :basic, :housing, :transport,
            :food, :misc, :cashier, :fuel,
            :tel, :other, :guard, :total_gross_salary,
            :total_benefits, :total_deductions, :net_salary, 'updated'
        ) ON DUPLICATE KEY UPDATE
            total_benefits = VALUES(total_benefits),
            total_deductions = VALUES(total_deductions),
            net_salary = VALUES(net_salary),
            status = VALUES(status)
    ");
    
    $stmtUpdateGenerated->execute([
        ':emp_id' => $empId,
        ':month_year' => $monthYear,
        ':basic' => $salaryComponents['basic'],
        ':housing' => $salaryComponents['housing'],
        ':transport' => $salaryComponents['transport'],
        ':food' => $salaryComponents['food'],
        ':misc' => $salaryComponents['misc'],
        ':cashier' => $salaryComponents['cashier'],
        ':fuel' => $salaryComponents['fuel'],
        ':tel' => $salaryComponents['tel'],
        ':other' => $salaryComponents['other'],
        ':guard' => $salaryComponents['guard'],
        ':total_gross_salary' => $totalGrossSalary,
        ':total_benefits' => $totalBenefits,
        ':total_deductions' => $totalDeductions,
        ':net_salary' => $netSalary
    ]);

    $pdo->commit();
    echo json_encode(['status' => 'success', 'message' => 'Payroll details updated successfully.']);

} catch (Exception $e) {
    $pdo->rollBack();
    error_log('Error updating payroll: ' . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => 'Error: ' . $e->getMessage()]);
}
?>