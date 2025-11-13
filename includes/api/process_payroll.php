<?php
/****************************************************************
 * MODIFICATION SUMMARY:
 * 1. ADDED LOAN DEDUCTION FUNCTION: A new function, `addOrUpdateLoanDeduction`, has been created.
 * 2. SKIPPABLE DEDUCTIONS: This function now first checks if a "Loan Installment" has already been manually added.
 * 3. BALANCE CALCULATION & DEDUCTION: It calculates the remaining balance and automatically inserts a 'Loan Installment' record.
 * 4. PAYMENT TRACKING: After creating the deduction, it also inserts a record into `emp_loan_payments`.
 * 5. LOAN COMPLETION: If the payment clears the balance, the loan's status is updated to 'paid'.
 * 6. ADDED VACATION SALARY BENEFIT: A new function, `addVacationWorkingDaysSalary`, has been added to automatically calculate and add the salary for days worked in the month a vacation starts.
 * 7. PRORATED SALARY FOR RETURNING EMPLOYEES: The main payroll generation logic now checks if an employee returned from vacation mid-month and prorates their salary accordingly.
 * 8. CORRECTED COLUMN NAMES: The script now uses the correct salary component column names (e.g., `basic_salary`).
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
        $stmtEmployeeData = $pdo->prepare("SELECT es.basic as basic_salary, es.housing as housing_allowance, es.transport as transport_allowance, es.food as food_allowance, es.misc as miscellaneous_allowance, es.cashier as cashier_allowance, es.fuel as fuel_allowance, es.tel as telephone_allowance, es.other as other_allowance, es.guard as guard_allowance, e.country, e.gosi
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
            'basic_salary' => $employeeData['basic_salary'],
            'housing_allowance' => $employeeData['housing_allowance'],
            'transport_allowance' => $employeeData['transport_allowance'],
            'food_allowance' => $employeeData['food_allowance'],
            'miscellaneous_allowance' => $employeeData['miscellaneous_allowance'],
            'cashier_allowance' => $employeeData['cashier_allowance'],
            'fuel_allowance' => $employeeData['fuel_allowance'],
            'telephone_allowance' => $employeeData['telephone_allowance'],
            'other_allowance' => $employeeData['other_allowance'],
            'guard_allowance' => $employeeData['guard_allowance']
        ];
        
        // --- PRORATED SALARY LOGIC FOR RETURNING EMPLOYEES ---
        $stmtVacationReturn = $pdo->prepare("SELECT return_date FROM emp_vacation WHERE emp_id = :emp_id AND DATE_FORMAT(return_date, '%Y-%m') = :month_year AND current_status = 'approved' AND is_deductible = 1");
        $stmtVacationReturn->execute([':emp_id' => $empId, ':month_year' => $monthYear]);
        $vacationReturn = $stmtVacationReturn->fetch(PDO::FETCH_ASSOC);

        $daysInMonth = date('t', strtotime($monthYear . '-01'));
        $prorationFactor = 1.0;

        if ($vacationReturn) {
            $returnDate = new DateTime($vacationReturn['return_date']);
            $daysWorked = $daysInMonth - ($returnDate->format('d') - 1);
            if ($daysWorked > 0) {
                $prorationFactor = $daysWorked / $daysInMonth;
            } else {
                continue; // Skip payroll if they returned at the end or after the month
            }
        }
        
        foreach ($salaryComponents as $key => $value) {
            $salaryComponents[$key] = $value * $prorationFactor;
        }
        
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
            $basicPlusHousing = floatval($salaryComponents['basic_salary']) + floatval($salaryComponents['housing_allowance']);
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
                $basicSalary = floatval($salaryComponents['basic_salary']);
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
                :emp_id, :month_year, :basic_salary, :housing_allowance, :transport_allowance,
                :food_allowance, :miscellaneous_allowance, :cashier_allowance, :fuel_allowance,
                :telephone_allowance, :other_allowance, :guard_allowance, :total_gross_salary,
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
            ':basic_salary' => number_format($salaryComponents['basic_salary'], 2, '.', ''),
            ':housing_allowance' => number_format($salaryComponents['housing_allowance'], 2, '.', ''),
            ':transport_allowance' => number_format($salaryComponents['transport_allowance'], 2, '.', ''),
            ':food_allowance' => number_format($salaryComponents['food_allowance'], 2, '.', ''),
            ':miscellaneous_allowance' => number_format($salaryComponents['miscellaneous_allowance'], 2, '.', ''),
            ':cashier_allowance' => number_format($salaryComponents['cashier_allowance'], 2, '.', ''),
            ':fuel_allowance' => number_format($salaryComponents['fuel_allowance'], 2, '.', ''),
            ':telephone_allowance' => number_format($salaryComponents['telephone_allowance'], 2, '.', ''),
            ':other_allowance' => number_format($salaryComponents['other_allowance'], 2, '.', ''),
            ':guard_allowance' => number_format($salaryComponents['guard_allowance'], 2, '.', ''),
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
    // Remove any previous absence deduction for this employee/month
    $stmtDelete = $pdo->prepare("DELETE FROM payroll_deductions WHERE emp_id = :emp_id AND month = :month_year AND deduction LIKE 'Absence Deduction%'");
    $stmtDelete->execute([':emp_id' => $empId, ':month_year' => $monthYear]);

    // NEW LOGIC: Do NOT deduct for any approved leave days (any type)
    // Only deduct for true absences (not covered by any approved leave)
    // If you have a table of absences, loop through each absent day and check if it is covered by any approved leave
    // If not covered, then deduct. Otherwise, skip deduction.
    // If you do not track absences, then do not insert any absence deduction here at all.
    // (If you want to keep absence deduction for true absences, implement that logic here)
    // For now, this function will NOT insert any deduction for approved leave days.
}

/**
 * Checks for active loans and inserts a deduction for the monthly installment,
 * but only if a loan deduction doesn't already exist (checks for any loan-related deduction).
 * MODIFIED: Now checks for ANY loan deduction (not just "Loan Installment") to work with 
 * automated loan approval system that creates specific loan deductions.
 */
function addOrUpdateLoanDeduction($pdo, $empId, $monthYear) {
    // Check if ANY loan-related deduction already exists for this month
    $stmtCheck = $pdo->prepare("SELECT id FROM payroll_deductions WHERE emp_id = :emp_id AND month = :month_year AND (deduction = 'Loan Installment' OR deduction LIKE '%Loan%')");
    $stmtCheck->execute([':emp_id' => $empId, ':month_year' => $monthYear]);
    if ($stmtCheck->fetch()) {
        return; // Skip if any loan deduction already exists
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
 * ENHANCED FUNCTION
 * Calculates and inserts benefits for employees on vacation:
 * 1. Working days salary before vacation starts (prorated monthly salary)
 * 2. Vacation salary benefit (if vacation_salary_type = 'payroll')
 */
function addVacationWorkingDaysSalary($pdo, $empId, $monthYear, $totalGrossSalary) {
    // Find approved vacation starting this month
    $stmtVacation = $pdo->prepare("
        SELECT id, start_date, return_date, vac_type, fly_type, vacation_salary_type, vacdays
        FROM emp_vacation
        WHERE emp_id = :emp_id
        AND current_status = 'approved'
        AND DATE_FORMAT(start_date, '%Y-%m') = :month_year
        ORDER BY start_date ASC
        LIMIT 1
    ");
    $stmtVacation->execute([':emp_id' => $empId, ':month_year' => $monthYear]);
    $vacation = $stmtVacation->fetch(PDO::FETCH_ASSOC);
    
    // If no vacation found, delete any existing vacation benefits and return
    if (!$vacation) {
        $stmtDelete = $pdo->prepare("DELETE FROM payroll_benefits 
            WHERE emp_id = :emp_id 
            AND month = :month_year 
            AND (benefit LIKE 'Working Days Salary for Vacation%' OR benefit LIKE 'Vacation Salary Benefit%')");
        $stmtDelete->execute([':emp_id' => $empId, ':month_year' => $monthYear]);
        return;
    }
    
    // Check if vacation benefits already exist for this specific vacation ID
    $stmtCheckExisting = $pdo->prepare("
        SELECT COUNT(*) as count 
        FROM payroll_benefits 
        WHERE emp_id = :emp_id 
        AND month = :month_year 
        AND (benefit = :working_days_name OR benefit = :vacation_salary_name)
    ");
    $stmtCheckExisting->execute([
        ':emp_id' => $empId, 
        ':month_year' => $monthYear,
        ':working_days_name' => "Working Days Salary for Vacation (ID: {$vacation['id']})",
        ':vacation_salary_name' => "Vacation Salary Benefit (ID: {$vacation['id']})"
    ]);
    $existingCount = $stmtCheckExisting->fetch(PDO::FETCH_ASSOC)['count'];
    
    // If benefits already exist for this vacation, skip adding duplicates
    if ($existingCount > 0) {
        return;
    }
    
    // Remove any old vacation-related benefits (from different vacation IDs or orphaned entries)
    $stmtDelete = $pdo->prepare("DELETE FROM payroll_benefits 
        WHERE emp_id = :emp_id 
        AND month = :month_year 
        AND (benefit LIKE 'Working Days Salary for Vacation%' OR benefit LIKE 'Vacation Salary Benefit%')");
    $stmtDelete->execute([':emp_id' => $empId, ':month_year' => $monthYear]);

    if ($vacation) {
        // Define non-payable leave types
        $non_payable_leave_types = ['Sick Leave', 'Casual Leave', 'Maternity Leave', 'Compassionate Leave', 'Business Trip', 'Compensatory Leave'];
        
        // Check if it's a payable leave (not in the non-payable list and not an emergency)
        $is_payable_leave = !in_array($vacation['vac_type'], $non_payable_leave_types) && $vacation['fly_type'] !== 'emergency';
        $vacation_salary_type = $vacation['vacation_salary_type'] ?? 'end_of_service';

        if ($is_payable_leave) {
            // Calculate working days before vacation starts
            $startDate = new DateTime($vacation['start_date']);
            $workingDays = (int)$startDate->format('d') - 1;

            $dailyRate = $totalGrossSalary / 30;

            // 1. ADD WORKING DAYS SALARY (salary for days worked before vacation)
            if ($workingDays > 0) {
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

            // 2. ADD VACATION SALARY BENEFIT (if vacation_salary_type = 'payroll')
            if ($vacation_salary_type === 'payroll') {
                // Calculate vacation salary based on vacation days
                $vacationDays = (int)$vacation['vacdays'];
                
                if ($vacationDays > 0) {
                    $vacationSalary = $dailyRate * $vacationDays;
                    
                    if ($vacationSalary > 0) {
                        $benefitName = "Vacation Salary Benefit (ID: {$vacation['id']})";
                        
                        $stmtInsertVacSalary = $pdo->prepare("
                            INSERT INTO payroll_benefits (emp_id, benefit, note, month, status)
                            VALUES (:emp_id, :benefit_name, :amount, :month_year, 1)
                        ");
                        $stmtInsertVacSalary->execute([
                            ':emp_id' => $empId,
                            ':benefit_name' => $benefitName,
                            ':amount' => number_format($vacationSalary, 2, '.', ''),
                            ':month_year' => $monthYear
                        ]);
                    }
                }
            }
            // If vacation_salary_type = 'end_of_service', vacation salary is NOT added to payroll
            // It will be calculated and paid during end of service settlement
        }
    }
}
?>