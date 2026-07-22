<?php
/****************************************************************
 * MODIFICATION SUMMARY:
 * 1. ADDED LOAN PAYMENT SYNC ON UPDATE: The script now checks if a deduction being updated is named "Loan Installment".
 * 2. SYNCHRONIZED UPDATE: If it is a loan installment, the script will find the corresponding payment record in the `emp_loan_payments` table for the same employee and month and update its amount.
 * 3. TRANSACTIONAL SAFETY: The entire operation remains wrapped in a database transaction.
 * 4. PRORATED SALARY FOR RETURNING EMPLOYEES: Logic added to prorate salary components based on the number of days worked after returning from vacation.
 * 5. CORRECTED COLUMN NAMES: The script now uses the correct salary component column names (e.g., `basic_salary`).
 ****************************************************************/
// Set the content type of the response to JSON
header('Content-Type: application/json');
// Include the database connection file
require_once("./../../includes/db.php");

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
                    AND COALESCE(v.vacdays, 0) > 20
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

// Decode the incoming JSON payload from the request body
$input = json_decode(file_get_contents('php://input'), true);

// Extract data from the input, providing default empty values if not set
$empId = $input['emp_id'] ?? '';
$monthYear = $input['month'] ?? '';
$updatedBenefits = $input['benefits'] ?? [];
$updatedDeductions = $input['deductions'] ?? [];

// Validate that the employee ID and month/year are provided
if (empty($empId) || empty($monthYear)) {
    echo json_encode(['status' => 'error', 'message' => 'Missing employee ID or month for update.']);
    exit();
}

// Get the database connection object
$pdo = getDbConnection();

try {
    // Begin a database transaction for atomic operations
    $pdo->beginTransaction();

    $skipAutoGosiDueToVacation = hasVacationGosiDeductedForMonth($pdo, $empId, $monthYear);

    if ($skipAutoGosiDueToVacation) {
        $stmtDeleteAutoGosi = $pdo->prepare("DELETE FROM payroll_deductions
            WHERE emp_id = :emp_id AND month = :month_year AND deduction = 'GOSI'");
        $stmtDeleteAutoGosi->execute([':emp_id' => $empId, ':month_year' => $monthYear]);
    }

    // --- LOGIC TO HANDLE DELETED BENEFITS ---
    $stmtExistingIds = $pdo->prepare("SELECT id FROM payroll_benefits WHERE emp_id = :emp_id AND month = :month_year");
    $stmtExistingIds->execute([':emp_id' => $empId, ':month_year' => $monthYear]);
    $dbBenefitIds = $stmtExistingIds->fetchAll(PDO::FETCH_COLUMN);
    
    // Only get IDs from submitted benefits (filter out null IDs for new benefits)
    $submittedBenefitIds = array_filter(array_column($updatedBenefits, 'id'), function($id) {
        return $id !== null && $id !== '';
    });
    
    $idsToDelete = array_diff($dbBenefitIds, $submittedBenefitIds);

    if (!empty($idsToDelete)) {
        $placeholders = implode(',', array_fill(0, count($idsToDelete), '?'));
        $stmtDelete = $pdo->prepare("DELETE FROM payroll_benefits WHERE id IN ($placeholders)");
        $stmtDelete->execute(array_values($idsToDelete));
    }

    // --- LOGIC TO HANDLE DELETED DEDUCTIONS ---
    // Only delete deductions that had existing IDs but are no longer in the submitted list
    $stmtExistingDeductionIds = $pdo->prepare("SELECT id FROM payroll_deductions 
        WHERE emp_id = :emp_id AND month = :month_year 
        AND status = 1
    ");
    $stmtExistingDeductionIds->execute([':emp_id' => $empId, ':month_year' => $monthYear]);
    $dbDeductionIds = $stmtExistingDeductionIds->fetchAll(PDO::FETCH_COLUMN);
    
    // Only get IDs from submitted deductions (filter out null IDs for new deductions)
    $submittedDeductionIds = array_filter(array_column($updatedDeductions, 'id'), function($id) {
        return $id !== null && $id !== '';
    });
    
    $deductionIdsToDelete = array_diff($dbDeductionIds, $submittedDeductionIds);

    if (!empty($deductionIdsToDelete)) {
        $placeholders = implode(',', array_fill(0, count($deductionIdsToDelete), '?'));
        $stmtDeleteDeductions = $pdo->prepare("DELETE FROM payroll_deductions WHERE id IN ($placeholders)");
        $stmtDeleteDeductions->execute(array_values($deductionIdsToDelete));
    }


    // --- PROCESS SUBMITTED DATA (UPDATES & INSERTS) ---
    $stmtSalary = $pdo->prepare("SELECT es.basic as basic_salary, es.housing as housing_allowance, es.transport as transport_allowance, es.food as food_allowance, es.misc as miscellaneous_allowance, es.cashier as cashier_allowance, es.fuel as fuel_allowance, es.tel as telephone_allowance, es.other as other_allowance, es.guard as guard_allowance, e.joining_date
        FROM emp_salary es
        JOIN employees e ON e.emp_id = es.emp_id
        WHERE es.emp_id = :emp_id AND es.status = 1
        LIMIT 1");
    $stmtSalary->execute([':emp_id' => $empId]);
    $salaryComponents = $stmtSalary->fetch(PDO::FETCH_ASSOC);

    if (!$salaryComponents) {
        throw new Exception("No salary components found for employee ID: $empId");
    }
    
    // --- PRORATED SALARY LOGIC ---
    $monthStartDate = new DateTime($monthYear . '-01');
    $monthStartDate->setTime(0, 0, 0);
    $monthEndDate = new DateTime($monthYear . '-01');
    $monthEndDate->modify('last day of this month');
    $monthEndDate->setTime(0, 0, 0);
    $daysInMonth = 30;
    $prorationFactor = 1.0;
    $payableStartDate = clone $monthStartDate;
    $joinedThisPayrollMonth = false;
    $joiningDeductionDays = 0;

    $joiningDateRaw = trim((string)($salaryComponents['joining_date'] ?? ''));
    unset($salaryComponents['joining_date']);

    if ($joiningDateRaw !== '' && $joiningDateRaw !== '0000-00-00') {
        $joiningDate = DateTime::createFromFormat('!Y-m-d', substr($joiningDateRaw, 0, 10));
        if (!$joiningDate instanceof DateTime) {
            $joiningTimestamp = strtotime($joiningDateRaw);
            if ($joiningTimestamp !== false) {
                $joiningDate = new DateTime(date('Y-m-d', $joiningTimestamp));
                $joiningDate->setTime(0, 0, 0);
            }
        }

        if ($joiningDate instanceof DateTime && $joiningDate >= $monthStartDate && $joiningDate <= $monthEndDate) {
            $joinedThisPayrollMonth = true;
            $joiningDeductionDays = max(0, ((int)$joiningDate->format('d')) - 1);
            if ($joiningDate > $payableStartDate) {
                $payableStartDate = clone $joiningDate;
            }
        }
    }

        if (!$joinedThisPayrollMonth) {
                                $stmtVacationReturn = $pdo->prepare("SELECT COALESCE(rr.final_approved_date, v.return_date) AS effective_return_date
                        FROM emp_vacation v
                        LEFT JOIN (
                                SELECT rr1.vacation_id, rr1.final_approved_date
                                FROM rejoin_requests rr1
                                INNER JOIN (
                                        SELECT vacation_id, MAX(id) AS latest_id
                                        FROM rejoin_requests
                                        WHERE status = 'approved'
                                            AND final_approved_date IS NOT NULL
                                        GROUP BY vacation_id
                                ) rr_latest ON rr_latest.latest_id = rr1.id
                        ) rr ON rr.vacation_id = v.id
                        WHERE v.emp_id = :emp_id
                            AND v.current_status = 'approved'
                            AND v.is_deductible = 1
                            AND v.start_date <= :month_start_upper
                            AND COALESCE(rr.final_approved_date, v.return_date) >= :month_start_lower
                            AND COALESCE(rr.final_approved_date, v.return_date) <= :month_end
                        ORDER BY COALESCE(rr.final_approved_date, v.return_date) DESC
                        LIMIT 1");
        $stmtVacationReturn->execute([
            ':emp_id' => $empId,
                        ':month_start_upper' => $monthYear . '-01',
                        ':month_start_lower' => $monthYear . '-01',
            ':month_end' => $monthEndDate->format('Y-m-d')
        ]);
        $vacationReturn = $stmtVacationReturn->fetch(PDO::FETCH_ASSOC);

        if ($vacationReturn && !empty($vacationReturn['effective_return_date'])) {
            $returnDate = new DateTime($vacationReturn['effective_return_date']);
            $returnDate->setTime(0, 0, 0);
            if ($returnDate > $payableStartDate) {
                $payableStartDate = clone $returnDate;
            }
        }
    }

    if ($payableStartDate > $monthEndDate) {
        $prorationFactor = 0.0;
    } else {
        $daysWorked = ((int)$monthEndDate->diff($payableStartDate)->format('%a')) + 1;
        if ($daysWorked > 0 && $daysWorked < $daysInMonth) {
            $prorationFactor = $daysWorked / $daysInMonth;
        }
    }

    if ($joinedThisPayrollMonth) {
        $prorationFactor = 1.0;
    }
    
    foreach ($salaryComponents as $key => $value) {
        $salaryComponents[$key] = $value * $prorationFactor;
    }

    $totalGrossSalary = array_sum(array_map('floatval', $salaryComponents));

    // --- Process Benefits (Update or Insert) ---
    foreach ($updatedBenefits as $benefit) {
        $benefitName = trim($benefit['benefit'] ?? '');
        $benefitAmount = (float)($benefit['amount'] ?? 0);
        $benefitId = $benefit['id'] ?? null;
        $benefitTypeId = $benefit['type_id'] ?? null;
        // hours keeps whatever decimal the user typed (e.g. 1.46); minutes is a separate
        // whole-number field on top of it - both stored in their own columns.
        $benefitHours = isset($benefit['hours']) && $benefit['hours'] !== '' ? (float)$benefit['hours'] : null;
        $benefitMinutes = isset($benefit['minutes']) && $benefit['minutes'] !== '' ? (int)$benefit['minutes'] : null;
        $benefitDurationHours = ($benefitHours ?? 0) + (($benefitMinutes ?? 0) / 60);

        if (empty($benefitName) && $benefitAmount <= 0) continue;

        $calculatedAmount = $benefitAmount;
        if ($benefitTypeId) {
            $stmtBenefitType = $pdo->prepare("SELECT calculation_type FROM benefit_types WHERE id = :id");
            $stmtBenefitType->execute([':id' => $benefitTypeId]);
            $calculationType = $stmtBenefitType->fetchColumn();

            if ($calculationType === 'overtime_basic' && $benefitDurationHours > 0) {
                $basicSalary = (float)$salaryComponents['basic_salary'];
                $hourlyRate = ($basicSalary / 240 / 2) + ($totalGrossSalary / 240);
                $calculatedAmount = $hourlyRate * $benefitDurationHours;
            } elseif ($calculationType === 'overtime_total' && $benefitDurationHours > 0) {
                $calculatedAmount = ($totalGrossSalary / 240) * $benefitDurationHours;
            }
        }

        if ($benefitId) {
            $stmt = $pdo->prepare("UPDATE payroll_benefits SET benefit = :benefit_name, note = :benefit_amount, hours = :hours, minutes = :minutes, type_id = :type_id WHERE id = :id");
            $stmt->execute([':benefit_name' => $benefitName, ':benefit_amount' => number_format($calculatedAmount, 2, '.', ''), ':hours' => $benefitHours, ':minutes' => $benefitMinutes, ':type_id' => $benefitTypeId, ':id' => $benefitId]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO payroll_benefits (emp_id, benefit, note, hours, minutes, month, status, type_id) VALUES (:emp_id, :benefit_name, :benefit_amount, :hours, :minutes, :month_year, 1, :type_id)");
            $stmt->execute([':emp_id' => $empId, ':benefit_name' => $benefitName, ':benefit_amount' => number_format($calculatedAmount, 2, '.', ''), ':hours' => $benefitHours, ':minutes' => $benefitMinutes, ':month_year' => $monthYear, ':type_id' => $benefitTypeId]);
        }
    }
    
    // --- PROCESS DEDUCTIONS (UPDATE & INSERT) ---
    foreach ($updatedDeductions as $deduction) {
        $deductionName = trim($deduction['name'] ?? $deduction['deduction'] ?? '');
        $deductionAmount = (float)($deduction['amount'] ?? $deduction['note'] ?? 0);
        $deductionId = $deduction['id'] ?? null;

        // Skip empty deductions (but allow LOAN INSTALLMENT even with 0 amount)
        if (empty($deductionName) && $deductionAmount <= 0 && strtoupper($deductionName) !== 'LOAN INSTALLMENT') {
            continue;
        }

        if (strtoupper($deductionName) === 'GOSI') {
            if ($skipAutoGosiDueToVacation) {
                continue;
            }
            if ($deductionId) {
                $stmt = $pdo->prepare("UPDATE payroll_deductions SET note = :deduction_amount WHERE id = :id");
                $stmt->execute([':deduction_amount' => number_format($deductionAmount, 2, '.', ''), ':id' => $deductionId]);
            } else {
                // Insert new GOSI deduction if it doesn't exist
                $stmt = $pdo->prepare("INSERT INTO payroll_deductions (emp_id, deduction, note, month, status, calculation_type, hours, days) VALUES (:emp_id, :deduction_name, :deduction_amount, :month_year, 1, 'fixed', NULL, NULL)");
                $stmt->execute([':emp_id' => $empId, ':deduction_name' => $deductionName, ':deduction_amount' => number_format($deductionAmount, 2, '.', ''), ':month_year' => $monthYear]);
            }
            continue; 
        }

        if ($deductionId) {
            $calcType = $deduction['calculation_type'] ?? 'fixed';
            // hours keeps whatever decimal the user typed (e.g. 1.46); minutes and days are
            // separate whole-number fields on top of it - both stored in their own columns.
            $hours = isset($deduction['hours']) && $deduction['hours'] !== '' ? (float)$deduction['hours'] : null;
            $minutes = isset($deduction['minutes']) && $deduction['minutes'] !== '' ? (int)$deduction['minutes'] : null;
            $days = isset($deduction['days']) && $deduction['days'] !== '' ? (int)$deduction['days'] : null;
            $stmt = $pdo->prepare("UPDATE payroll_deductions SET deduction = :deduction_name, note = :deduction_amount, calculation_type = :calc_type, hours = :hours, minutes = :minutes, days = :days WHERE id = :id");
            $stmt->execute([':deduction_name' => $deductionName, ':deduction_amount' => number_format($deductionAmount, 2, '.', ''), ':calc_type' => $calcType, ':hours' => $hours, ':minutes' => $minutes, ':days' => $days, ':id' => $deductionId]);
        } else {
            $calcType = $deduction['calculation_type'] ?? 'fixed';
            $hours = isset($deduction['hours']) && $deduction['hours'] !== '' ? (float)$deduction['hours'] : null;
            $minutes = isset($deduction['minutes']) && $deduction['minutes'] !== '' ? (int)$deduction['minutes'] : null;
            $days = isset($deduction['days']) && $deduction['days'] !== '' ? (int)$deduction['days'] : null;
            $stmt = $pdo->prepare("INSERT INTO payroll_deductions (emp_id, deduction, note, month, status, calculation_type, hours, minutes, days) VALUES (:emp_id, :deduction_name, :deduction_amount, :month_year, 1, :calc_type, :hours, :minutes, :days)");
            $stmt->execute([':emp_id' => $empId, ':deduction_name' => $deductionName, ':deduction_amount' => number_format($deductionAmount, 2, '.', ''), ':month_year' => $monthYear, ':calc_type' => $calcType, ':hours' => $hours, ':minutes' => $minutes, ':days' => $days]);
        }
        
        // --- NEW: SYNCHRONIZE LOAN PAYMENT UPDATE ---
        if (strtoupper($deductionName) === 'LOAN INSTALLMENT') {
            $paymentDate = date('Y-m-t', strtotime($monthYear . '-01'));
            $stmtUpdatePayment = $pdo->prepare("
                UPDATE emp_loan_payments p
                JOIN emp_loan l ON p.loan_id = l.id
                SET p.amount = :amount
                WHERE l.emp_id = :emp_id AND p.payment_date = :payment_date
            ");
            $stmtUpdatePayment->execute([
                ':amount' => number_format($deductionAmount, 2, '.', ''),
                ':emp_id' => $empId,
                ':payment_date' => $paymentDate
            ]);
        }
    }

    // Enforce joining-date deduction based on days before joining in this month.
    $stmtDeleteJoin = $pdo->prepare("DELETE FROM payroll_deductions
        WHERE emp_id = :emp_id AND month = :month_year AND deduction LIKE 'Joining Date Deduction%'");
    $stmtDeleteJoin->execute([':emp_id' => $empId, ':month_year' => $monthYear]);

    if ($joiningDeductionDays > 0) {
        $joiningDeductionAmount = round(($totalGrossSalary / 30) * $joiningDeductionDays, 2);
        $joiningLabel = 'Joining Date Deduction (' . $joiningDeductionDays . ' days)';

        $stmtInsertJoin = $pdo->prepare("INSERT INTO payroll_deductions
            (emp_id, deduction, note, month, status, calculation_type, hours, days)
            VALUES (:emp_id, :deduction, :amount, :month_year, 1, 'daily_deduction', NULL, :days)");
        $stmtInsertJoin->execute([
            ':emp_id' => $empId,
            ':deduction' => $joiningLabel,
            ':amount' => number_format($joiningDeductionAmount, 2, '.', ''),
            ':month_year' => $monthYear,
            ':days' => $joiningDeductionDays
        ]);
    }


    // --- RE-CALCULATE TOTALS AND UPDATE MASTER RECORD ---
    $stmtBenefitsSum = $pdo->prepare("SELECT COALESCE(SUM(CAST(note AS DECIMAL(10,2))), 0) FROM payroll_benefits WHERE emp_id = :emp_id AND month = :month_year AND status = 1");
    $stmtBenefitsSum->execute([':emp_id' => $empId, ':month_year' => $monthYear]);
    $totalBenefits = (float)$stmtBenefitsSum->fetchColumn();

    $stmtDeductionsSum = $pdo->prepare("SELECT COALESCE(SUM(CAST(note AS DECIMAL(10,2))), 0) FROM payroll_deductions WHERE emp_id = :emp_id AND month = :month_year AND status = 1");
    $stmtDeductionsSum->execute([':emp_id' => $empId, ':month_year' => $monthYear]);
    $totalDeductions = round((float)$stmtDeductionsSum->fetchColumn(), 0);

    $netSalary = round($totalGrossSalary + $totalBenefits - $totalDeductions, 0);
    
    $stmtUpdateGenerated = $pdo->prepare("UPDATE payrolls SET
        basic_salary = :basic_salary,
        housing_allowance = :housing_allowance,
        transport_allowance = :transport_allowance,
        food_allowance = :food_allowance,
        miscellaneous_allowance = :miscellaneous_allowance,
        cashier_allowance = :cashier_allowance,
        fuel_allowance = :fuel_allowance,
        telephone_allowance = :telephone_allowance,
        other_allowance = :other_allowance,
        guard_allowance = :guard_allowance,
        total_gross_salary = :total_gross_salary,
        total_benefits = :total_benefits,
        total_deductions = :total_deductions,
        net_salary = :net_salary,
        status = 'updated'
        WHERE emp_id = :emp_id AND month_year = :month_year");
    $stmtUpdateGenerated->execute([
        ':basic_salary' => number_format((float)$salaryComponents['basic_salary'], 2, '.', ''),
        ':housing_allowance' => number_format((float)$salaryComponents['housing_allowance'], 2, '.', ''),
        ':transport_allowance' => number_format((float)$salaryComponents['transport_allowance'], 2, '.', ''),
        ':food_allowance' => number_format((float)$salaryComponents['food_allowance'], 2, '.', ''),
        ':miscellaneous_allowance' => number_format((float)$salaryComponents['miscellaneous_allowance'], 2, '.', ''),
        ':cashier_allowance' => number_format((float)$salaryComponents['cashier_allowance'], 2, '.', ''),
        ':fuel_allowance' => number_format((float)$salaryComponents['fuel_allowance'], 2, '.', ''),
        ':telephone_allowance' => number_format((float)$salaryComponents['telephone_allowance'], 2, '.', ''),
        ':other_allowance' => number_format((float)$salaryComponents['other_allowance'], 2, '.', ''),
        ':guard_allowance' => number_format((float)$salaryComponents['guard_allowance'], 2, '.', ''),
        ':total_gross_salary' => number_format($totalGrossSalary, 2, '.', ''),
        ':total_benefits' => number_format($totalBenefits, 2, '.', ''),
        ':total_deductions' => number_format($totalDeductions, 2, '.', ''),
        ':net_salary' => number_format($netSalary, 2, '.', ''),
        ':emp_id' => $empId,
        ':month_year' => $monthYear
    ]);

    // Commit the transaction to save all changes.
    $pdo->commit();

    // --- CRITICAL FIX: FETCH AND RETURN THE UPDATED DATA ---
    $stmtFreshBenefits = $pdo->prepare("SELECT * FROM payroll_benefits WHERE emp_id = :emp_id AND month = :month_year ORDER BY id");
    $stmtFreshBenefits->execute([':emp_id' => $empId, ':month_year' => $monthYear]);
    $freshBenefitsData = $stmtFreshBenefits->fetchAll(PDO::FETCH_ASSOC);

    $stmtFreshDeductions = $pdo->prepare("SELECT * FROM payroll_deductions WHERE emp_id = :emp_id AND month = :month_year ORDER BY id");
    $stmtFreshDeductions->execute([':emp_id' => $empId, ':month_year' => $monthYear]);
    $freshDeductionsData = $stmtFreshDeductions->fetchAll(PDO::FETCH_ASSOC);

    // Send a success response including the fresh data.
    echo json_encode([
        'status' => 'success', 
        'message' => __('payroll_details_updated_successfully'),
        'benefits' => $freshBenefitsData,
        'deductions' => $freshDeductionsData
    ]);

} catch (Exception $e) {
    // If any error occurs, roll back the entire transaction.
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('Error updating payroll: ' . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => 'Error: ' . $e->getMessage()]);
}
?>