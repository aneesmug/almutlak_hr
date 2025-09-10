<?php
/****************************************************************
 * MODIFICATION SUMMARY:
 * 1. ADDED LOAN DEDUCTION FUNCTION: A new function, `addOrUpdateLoanDeduction`, has been created at the end of the file.
 * 2. SKIPPABLE DEDUCTIONS: This function now first checks if a "Loan Installment" has already been manually added to the `payroll_deductions` table for the month. If it exists (even with a value of 0), the automatic deduction is skipped.
 * 3. BALANCE CALCULATION & DEDUCTION: It calculates the remaining balance and automatically inserts a 'Loan Installment' record if one doesn't already exist.
 * 4. PAYMENT TRACKING: After creating the deduction, it also inserts a record into `emp_loan_payments` to track the repayment.
 * 5. LOAN COMPLETION: If the payment clears the balance, the loan's status is updated to 'paid'.
 * 6. ADDED VACATION SALARY BENEFIT: A new function, `addVacationWorkingDaysSalary`, has been added to automatically calculate and add the salary for days worked in the month a vacation starts. This is added as a benefit in the payroll.
 ****************************************************************/
// Set the content type of the response to JSON
header('Content-Type: application/json');
// Include the database connection file
require_once("./../../includes/db.php");

/**
 * Helper function to get the previous month in 'Y-m' format.
 *
 * @param string $monthYear The month in 'Y-m' format (e.g., "2024-07").
 * @return string The previous month in 'Y-m' format.
 */
function getPreviousMonth($monthYear) {
    $date = new DateTime($monthYear . '-01');
    $date->modify('first day of last month');
    return $date->format('Y-m');
}

// Decode the incoming JSON payload from the request body
$input = json_decode(file_get_contents('php://input'), true);

// Extract data from the input, providing default empty values if not set
$employeeIds = $input['employee_ids'] ?? [];
$monthYear = $input['month'] ?? '';

// Validate that the employee IDs and month/year are provided
if (empty($employeeIds) || empty($monthYear)) {
    echo json_encode(['status' => 'error', 'message' => 'Missing employee IDs or month.']);
    exit();
}

// Get the database connection object
$pdo = getDbConnection();

try {
    // Begin a database transaction for bulk processing
    $pdo->beginTransaction();
    $processedCount = 0;
    $skippedEmployees = []; // Array to hold skipped employee IDs
    $previousMonthYear = getPreviousMonth($monthYear);

    foreach ($employeeIds as $empId) {
        // --- FIX STARTS: Check previous month's payroll status ---
        $stmtCheckPrevious = $pdo->prepare(
            "SELECT status FROM payrolls WHERE emp_id = :emp_id AND month_year = :previous_month_year"
        );
        $stmtCheckPrevious->execute([':emp_id' => $empId, ':previous_month_year' => $previousMonthYear]);
        $previousPayroll = $stmtCheckPrevious->fetch(PDO::FETCH_ASSOC);

        if ($previousPayroll && $previousPayroll['status'] === 'generated') {
            $skippedEmployees[] = $empId;
            continue; // Skip to the next employee
        }
        // --- FIX ENDS ---

        // Get employee's salary components and country for GOSI calculation
        $stmtEmployeeData = $pdo->prepare("SELECT es.basic, es.housing, es.transport, es.food, es.misc, es.cashier, es.fuel, es.tel, es.other, es.guard, e.country, e.gosi
            FROM emp_salary es
            JOIN employees e ON es.emp_id = e.emp_id
            WHERE es.emp_id = :emp_id AND e.status = 1 AND es.status = 1
        ");
        $stmtEmployeeData->execute([':emp_id' => $empId]);
        $employeeData = $stmtEmployeeData->fetch(PDO::FETCH_ASSOC);

        // Skip to the next employee if no data is found
        if (!$employeeData) continue;

        // Create an array of salary components for easier summation
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
        // Calculate the total gross salary
        $totalGrossSalary = array_sum(array_map('floatval', $salaryComponents));
        
        // --- LEAVE DEDUCTION LOGIC ---
        addOrUpdateLeaveDeduction($pdo, $empId, $monthYear, $totalGrossSalary);

        // --- (NEW) VACATION WORKING DAYS SALARY ---
        addVacationWorkingDaysSalary($pdo, $empId, $monthYear, $totalGrossSalary);

        // --- (NEW) LOAN DEDUCTION LOGIC ---
        addOrUpdateLoanDeduction($pdo, $empId, $monthYear);


        // --- GOSI Deduction Logic ---
        if ($employeeData['country'] === '191') {
            $basicPlusHousing = floatval($salaryComponents['basic']) + floatval($salaryComponents['housing']);
            $gosiAmount = round($basicPlusHousing * ($employeeData['gosi'] / 100) , 2); // 0.0975
            $stmtCheckGosi = $pdo->prepare("SELECT id, note FROM payroll_deductions
                WHERE emp_id = :emp_id AND deduction = 'GOSI' AND month = :month_year LIMIT 1
            ");
            $stmtCheckGosi->execute([':emp_id' => $empId, ':month_year' => $monthYear]);
            $existingGosi = $stmtCheckGosi->fetch(PDO::FETCH_ASSOC);

            if ($existingGosi) {
                $storedAmount = floatval($existingGosi['note']);
                if (abs($storedAmount - $gosiAmount) > 0.01) { // Use a tolerance for float comparison
                    $stmtUpdateGosi = $pdo->prepare("UPDATE payroll_deductions
                        SET note = :gosi_amount
                        WHERE id = :id
                    ");
                    $stmtUpdateGosi->execute([
                        ':gosi_amount' => number_format($gosiAmount, 2, '.', ''),
                        ':id' => $existingGosi['id']
                    ]);
                }
            } else {
                $stmtGosi = $pdo->prepare("INSERT INTO payroll_deductions (emp_id, deduction, note, month, status)
                    VALUES (:emp_id, 'GOSI', :gosi_amount, :month_year, 1)
                ");
                $stmtGosi->execute([
                    ':emp_id' => $empId,
                    ':gosi_amount' => number_format($gosiAmount, 2, '.', ''),
                    ':month_year' => $monthYear
                ]);
            }
        }
        // --- Calculate total benefits (including auto-calculated ones) ---
        $stmtBenefits = $pdo->prepare("SELECT pb.*, bt.calculation_type
            FROM payroll_benefits pb
            LEFT JOIN benefit_types bt ON pb.type_id = bt.id
            WHERE pb.emp_id = :emp_id AND pb.month = :month_year AND pb.status = 1
        ");
        $stmtBenefits->execute([':emp_id' => $empId, ':month_year' => $monthYear]);
        $benefits = $stmtBenefits->fetchAll(PDO::FETCH_ASSOC);
        $totalBenefits = 0;
        foreach ($benefits as $benefit) {
            $amount = 0;
            if ($benefit['calculation_type'] === 'overtime_basic') {
                $hours = floatval($benefit['hours'] ?? 0);
                $basicSalary = floatval($salaryComponents['basic']);
                $hourlyRate = ($basicSalary / 240 / 2) + ($totalGrossSalary / 240);
                $amount = $hourlyRate * $hours;
            } elseif ($benefit['calculation_type'] === 'overtime_total') {
                $hours = floatval($benefit['hours'] ?? 0);
                $amount = ($totalGrossSalary / 240) * $hours;
            } else {
                $amount = floatval($benefit['note']);
            }
            $totalBenefits += $amount;
        }
        
        // --- Calculate total deductions ---
        $stmtDeductionsSum = $pdo->prepare("SELECT COALESCE(SUM(CAST(note AS DECIMAL(10,2))), 0)
            FROM payroll_deductions
            WHERE emp_id = :emp_id AND month = :month_year AND status = 1
        ");
        $stmtDeductionsSum->execute([':emp_id' => $empId, ':month_year' => $monthYear]);
        $totalDeductions = (float)$stmtDeductionsSum->fetchColumn();
        // Calculate net salary
        $netSalary = $totalGrossSalary + $totalBenefits - $totalDeductions;
        // --- Insert or update the final payroll record ---
        $stmt = $pdo->prepare("INSERT INTO payrolls (
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
    // Commit the transaction to save all changes
    $pdo->commit();
    $message = "Payroll processed for $processedCount employees.";
    if (!empty($skippedEmployees)) {
        $message .= " Skipped " . count($skippedEmployees) . " employees because their previous month's payroll is not paid: " . implode(', ', $skippedEmployees);
    }
    echo json_encode([
        'status' => 'success',
        'message' => $message,
        'processed_count' => $processedCount,
        'skipped_count' => count($skippedEmployees),
        'skipped_employees' => $skippedEmployees
    ]);
} catch (Exception $e) {
    // If any error occurs, roll back the transaction
    if($pdo->inTransaction()){
        $pdo->rollBack();
    }
    error_log('Payroll processing error: ' . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => 'Processing failed: ' . $e->getMessage()]);
}

/**
 * Calculates and inserts/updates a deduction for unpaid leave based on approved, deductible vacations.
 */
function addOrUpdateLeaveDeduction($pdo, $empId, $monthYear, $totalGrossSalary) {
    $stmtDelete = $pdo->prepare("DELETE FROM payroll_deductions WHERE emp_id = :emp_id AND month = :month_year AND deduction LIKE 'Absence Deduction%'");
    $stmtDelete->execute([':emp_id' => $empId, ':month_year' => $monthYear]);

    $stmtLeaves = $pdo->prepare("
        SELECT SUM(vacdays) as total_deductible_days
        FROM emp_vacation
        WHERE emp_id = :emp_id
        AND approval_status = 'gm_approved'
        AND is_deductible = 1
        AND DATE_FORMAT(start_date, '%Y-%m') = :month_year
    ");
    $stmtLeaves->execute([':emp_id' => $empId, ':month_year' => $monthYear]);
    $deductibleDays = $stmtLeaves->fetchColumn();

    if ($deductibleDays > 0) {
        $dailyRate = $totalGrossSalary / 30;
        $deductionAmount = $dailyRate * $deductibleDays;

        if ($deductionAmount > 0) {
            $deductionName = "Absence (" . $deductibleDays . ($deductibleDays > 1 ? " Days" : " Day") . ")";
            $stmtInsertDeduction = $pdo->prepare("
                INSERT INTO payroll_deductions (emp_id, deduction, note, month, status)
                VALUES (:emp_id, :deduction_name, :amount, :month_year, 1)
            ");
            $stmtInsertDeduction->execute([
                ':emp_id' => $empId,
                ':deduction_name' => $deductionName,
                ':amount' => number_format($deductionAmount, 2, '.', ''),
                ':month_year' => $monthYear
            ]);
        }
    }
}

/**
 * Checks for active loans and inserts a deduction for the monthly installment,
 * but only if a manual "Loan Installment" deduction doesn't already exist.
 */
function addOrUpdateLoanDeduction($pdo, $empId, $monthYear) {
    $stmtCheck = $pdo->prepare("SELECT id FROM payroll_deductions WHERE emp_id = :emp_id AND month = :month_year AND deduction = 'Loan Installment'");
    $stmtCheck->execute([':emp_id' => $empId, ':month_year' => $monthYear]);
    if ($stmtCheck->fetch()) {
        return;
    }
    $payrollMonthEnd = date('Y-m-t', strtotime($monthYear . '-01'));
    $stmtLoan = $pdo->prepare("SELECT id, total_payable, monthly_deduction FROM emp_loan WHERE emp_id = :emp_id AND status = 'approved' AND start_date <= :payroll_month_end LIMIT 1");
    $stmtLoan->execute([':emp_id' => $empId, ':payroll_month_end' => $payrollMonthEnd]);
    $loan = $stmtLoan->fetch(PDO::FETCH_ASSOC);
    if ($loan) {
        $loanId = $loan['id'];
        $stmtPaid = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) FROM emp_loan_payments WHERE loan_id = :loan_id");
        $stmtPaid->execute([':loan_id' => $loanId]);
        $totalPaid = $stmtPaid->fetchColumn();
        $remainingBalance = $loan['total_payable'] - $totalPaid;
        if ($remainingBalance > 0) {
            $deductionAmount = min($loan['monthly_deduction'], $remainingBalance);
            $stmtInsertDeduction = $pdo->prepare("INSERT INTO payroll_deductions (emp_id, deduction, note, month, status) VALUES (:emp_id, 'Loan Installment', :amount, :month_year, 1)");
            $stmtInsertDeduction->execute([':emp_id' => $empId, ':amount' => number_format($deductionAmount, 2, '.', ''), ':month_year' => $monthYear]);
            
            // MODIFIED: Added payment_method
            $stmtInsertPayment = $pdo->prepare("INSERT INTO emp_loan_payments (loan_id, payment_date, amount, payment_method) VALUES (:loan_id, :payment_date, :amount, 'payroll')");
            $stmtInsertPayment->execute([':loan_id' => $loanId, ':payment_date' => $payrollMonthEnd, ':amount' => number_format($deductionAmount, 2, '.', '')]);

            if (($totalPaid + $deductionAmount) >= $loan['total_payable']) {
                $stmtUpdateLoan = $pdo->prepare("UPDATE emp_loan SET status = 'paid' WHERE id = :loan_id");
                $stmtUpdateLoan->execute([':loan_id' => $loanId]);
            }
        }
    }
}


/**
 * NEW FUNCTION
 * Calculates and inserts a benefit for salary of days worked in the month a vacation starts.
 */
function addVacationWorkingDaysSalary($pdo, $empId, $monthYear, $totalGrossSalary) {
    // First, remove any previous vacation working days salary benefit to avoid duplication
    $stmtDelete = $pdo->prepare("DELETE FROM payroll_benefits WHERE emp_id = :emp_id AND month = :month_year AND benefit LIKE 'Working Days Salary for Vacation%'");
    $stmtDelete->execute([':emp_id' => $empId, ':month_year' => $monthYear]);

    // Find approved, payable vacations starting this month
    $stmtVacation = $pdo->prepare("
        SELECT id, start_date, vac_type, fly_type
        FROM emp_vacation
        WHERE emp_id = :emp_id
        AND approval_status = 'gm_approved'
        AND DATE_FORMAT(start_date, '%Y-%m') = :month_year
    ");
    $stmtVacation->execute([':emp_id' => $empId, ':month_year' => $monthYear]);
    $vacation = $stmtVacation->fetch(PDO::FETCH_ASSOC);

    if ($vacation) {
        // Define non-payable leave types
        $non_payable_leave_types = ['Sick Leave', 'Casual Leave', 'Maternity Leave', 'Compassionate Leave', 'Business Trip', 'Compensatory Leave'];
        
        // Check if it's a payable leave (not in the non-payable list and not an emergency)
        $is_payable_leave = !in_array($vacation['vac_type'], $non_payable_leave_types) && $vacation['fly_type'] !== 'emergency';

        if ($is_payable_leave) {
            // Calculate working days before vacation starts
            $startDate = new DateTime($vacation['start_date']);
            $workingDays = (int)$startDate->format('d') - 1;

            if ($workingDays > 0) {
                $dailyRate = $totalGrossSalary / 30;
                $workingDaysSalary = $dailyRate * $workingDays;

                if ($workingDaysSalary > 0) {
                    $benefitName = "Working Days Salary for Vacation (ID: {$vacation['id']})";
                    $stmtInsertBenefit = $pdo->prepare("
                        INSERT INTO payroll_benefits (emp_id, benefit, note, month, status)
                        VALUES (:emp_id, :benefit_name, :amount, :month_year, 1)
                    ");
                    $stmtInsertBenefit->execute([
                        ':emp_id' => $empId,
                        ':benefit_name' => $benefitName,
                        ':amount' => number_format($workingDaysSalary, 2, '.', ''),
                        ':month_year' => $monthYear
                    ]);
                }
            }
        }
    }
}
?>
