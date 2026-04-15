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
require_once("./../../includes/session_check.php");
require_once("./../../includes/ApprovalChainManager.php");
require_once("./../../includes/payroll_approval_helpers.php");

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

/**
 * Helper function to format a payroll month for user-facing messages.
 *
 * @param string $monthYear The month in 'Y-m' format.
 * @return string The formatted month label.
 */
function formatPayrollMonthLabel($monthYear) {
    $date = DateTime::createFromFormat('Y-m-d', $monthYear . '-01');
    return $date ? $date->format('F Y') : $monthYear;
}

/**
 * Normalizes common date formats to payroll month format ('Y-m').
 *
 * @param string|null $dateValue
 * @return string|null
 */
function normalizeDateToPayrollMonth($dateValue) {
    if (empty($dateValue)) {
        return null;
    }

    $dateValue = trim((string)$dateValue);
    if ($dateValue === '' || $dateValue === '0000-00-00') {
        return null;
    }

    $formats = ['Y-m-d', 'd/m/Y', 'm/d/Y', 'd-m-Y', 'Y/m/d'];
    foreach ($formats as $format) {
        $date = DateTime::createFromFormat($format, $dateValue);
        if ($date instanceof DateTime) {
            return $date->format('Y-m');
        }
    }

    $timestamp = strtotime($dateValue);
    if ($timestamp === false) {
        return null;
    }

    return date('Y-m', $timestamp);
}

// Decode the incoming JSON payload from the request body
$input = json_decode(file_get_contents('php://input'), true);

// Extract data from the input, providing default empty values if not set
$employeeIds = $input['employee_ids'] ?? [];
$monthYear = $input['month'] ?? '';
$isGenerate = !empty($input['is_generate']);
$isRegenerate = !empty($input['is_regenerate']);
$skipApprovalChecks = $isGenerate || $isRegenerate;

// Validate that the employee IDs and month/year are provided
if (empty($employeeIds) || empty($monthYear)) {
    echo json_encode(['status' => 'error', 'message' => 'Missing employee IDs or month.']);
    exit();
}

if (!preg_match('/^\d{4}-\d{2}$/', $monthYear)) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid month format. Expected YYYY-MM.']);
    exit();
}

// Get the database connection object
$pdo = getDbConnection();

try {
    $requestedBy = $_SESSION['empid'] ?? ($empid ?? null);
    $requestInvNo = null;

    if (!$skipApprovalChecks) {
        ensurePayrollApprovalTable($pdo);
        ensurePayrollApprovalRequestType($pdo);

        if (empty($requestedBy)) {
            throw new Exception('Unauthorized payroll approval request.');
        }

        $requestStmt = $pdo->prepare("SELECT * FROM payroll_approval_requests WHERE payroll_month = :payroll_month LIMIT 1");
        $requestStmt->execute([':payroll_month' => $monthYear]);
        $payrollApprovalRequest = $requestStmt->fetch(PDO::FETCH_ASSOC);

        $chainManager = new ApprovalChainManager($conDB, $pdo);

        if (!$payrollApprovalRequest) {
            echo json_encode([
                'status' => 'approval_required',
                'message' => __('payroll_start_approval_first', 'Please start payroll approval first for this month.'),
                'payroll_month' => $monthYear,
                'approval_status' => 'not_started'
            ]);
            exit();
        }

        $requestInvNo = $payrollApprovalRequest['request_inv_no'];
        $approvalStatus = $chainManager->getApprovalStatus($requestInvNo);

        if ($approvalStatus['status'] === 'not_found') {
            echo json_encode([
                'status' => 'approval_required',
                'message' => __('payroll_start_approval_first', 'Please start payroll approval first for this month.'),
                'request_inv_no' => $requestInvNo,
                'payroll_month' => $monthYear,
                'approval_status' => 'not_started'
            ]);
            exit();
        }

        if ($approvalStatus['status'] !== 'approved') {
            $pendingApproverStmt = $pdo->prepare("SELECT ra.approver_id, ra.approval_level, e.name AS approver_name
                FROM request_approvers ra
                LEFT JOIN employees e ON e.emp_id = ra.approver_id
                WHERE ra.request_inv_no = :inv_no
                  AND ra.status = 'pending'
                ORDER BY ra.approval_level ASC
                LIMIT 1");
            $pendingApproverStmt->execute([':inv_no' => $requestInvNo]);
            $pendingApprover = $pendingApproverStmt->fetch(PDO::FETCH_ASSOC);

            $pendingWith = !empty($pendingApprover['approver_name'])
                ? $pendingApprover['approver_name']
                : (!empty($pendingApprover['approver_id']) ? $pendingApprover['approver_id'] : __('next_approver'));

            $blockedMessage = __('payroll_approval_required', 'Payroll generation requires chain approval first.');
            if ($approvalStatus['status'] === 'rejected') {
                $blockedMessage = __('payroll_approval_rejected', 'Payroll approval request is rejected. Please create/update approval and try again.');
            } else {
                $blockedMessage .= ' ' . __('pending_with') . ' ' . $pendingWith . '.';
            }

            echo json_encode([
                'status' => 'approval_required',
                'message' => $blockedMessage,
                'request_inv_no' => $requestInvNo,
                'payroll_month' => $monthYear,
                'approval_status' => $approvalStatus['status'],
                'pending_approver' => $pendingApprover
            ]);
            exit();
        }
    }

    // Begin a database transaction for bulk processing
    $pdo->beginTransaction();
    $processedCount = 0;
    $skippedEmployees = []; // Array to hold skipped employee IDs
    $previousMonthYear = getPreviousMonth($monthYear);

    foreach ($employeeIds as $empId) {
        // --- CHECK: Payroll on Hold (payment_type = 3) ---
        $stmtCheckHold = $pdo->prepare(
            "SELECT payment_type FROM employees WHERE emp_id = :emp_id"
        );
        $stmtCheckHold->execute([':emp_id' => $empId]);
        $employeePaymentType = $stmtCheckHold->fetch(PDO::FETCH_ASSOC);
        
        // Skip employee if payment_type is 3 (on hold)
        if ($employeePaymentType && $employeePaymentType['payment_type'] == 3) {
            $skippedEmployees[] = [
                'emp_id' => $empId,
                'reason' => __('payroll_on_hold')
            ];
            continue;
        }
        // --- CHECK END ---
        
        // --- FIX STARTS: Check previous month's payroll status ---
        $stmtCheckPrevious = $pdo->prepare(
            "SELECT status FROM payrolls WHERE emp_id = :emp_id AND month_year = :previous_month_year"
        );
        $stmtCheckPrevious->execute([':emp_id' => $empId, ':previous_month_year' => $previousMonthYear]);
        $previousPayroll = $stmtCheckPrevious->fetch(PDO::FETCH_ASSOC);

        if ($previousPayroll && $previousPayroll['status'] === 'generated') {
            $skippedEmployees[] = [
                'emp_id' => $empId,
                'reason' => __('previous_month_payroll_still_pending'),
                'blocked_month' => $previousMonthYear,
                'blocked_month_label' => formatPayrollMonthLabel($previousMonthYear)
            ];
            continue; // Skip to the next employee
        }
        // --- FIX ENDS ---
        
        // --- NEW CHECK: Employee status must be paid (not on hold) ---
        $stmtCheckStatus = $pdo->prepare(
            "SELECT status, joining_date FROM employees WHERE emp_id = :emp_id"
        );
        $stmtCheckStatus->execute([':emp_id' => $empId]);
        $employeeStatus = $stmtCheckStatus->fetch(PDO::FETCH_ASSOC);
        
        // Skip employee if status is not 1 (active/paid)
        if (!$employeeStatus || $employeeStatus['status'] != 1) {
            $skippedEmployees[] = [
                'emp_id' => $empId,
                'reason' => __('employee_status_is_not_active')
            ];
            continue;
        }

        $joiningMonthYear = normalizeDateToPayrollMonth($employeeStatus['joining_date'] ?? null);
        if ($joiningMonthYear !== null && $monthYear < $joiningMonthYear) {
            $skippedEmployees[] = [
                'emp_id' => $empId,
                'reason' => 'Payroll month is before employee joining month',
                'blocked_month' => $joiningMonthYear,
                'blocked_month_label' => formatPayrollMonthLabel($joiningMonthYear)
            ];
            continue;
        }

        // Skip employees on Fly annual vacation for the selected payroll month.
        // Local annual/emergency vacations remain eligible and stay visible in payroll.
        $payrollMonthStart = $monthYear . '-01';
        $payrollMonthEnd = date('Y-m-t', strtotime($payrollMonthStart));

        $stmtActiveFlyVacation = $pdo->prepare(
            "SELECT id
             FROM emp_vacation
             WHERE emp_id = :emp_id
               AND review = 'A'
               AND current_status IN ('approved', 'completed')
               AND start_date <= :month_end
               AND return_date >= :month_start
               AND (
                    LOWER(COALESCE(vac_type, '')) = 'fly'
                    OR LOWER(COALESCE(note, '')) = 'fly'
               )
             LIMIT 1"
        );
        $stmtActiveFlyVacation->execute([
            ':emp_id' => $empId,
            ':month_start' => $payrollMonthStart,
            ':month_end' => $payrollMonthEnd
        ]);
        $activeFlyVacation = $stmtActiveFlyVacation->fetch(PDO::FETCH_ASSOC);

        if ($activeFlyVacation) {
            $skippedEmployees[] = [
                'emp_id' => $empId,
                'reason' => 'Employee is on Fly annual vacation for this payroll month'
            ];
            continue;
        }
        // --- CHECK END ---

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
        
        // --- PRORATED SALARY LOGIC FOR MID-MONTH JOINERS / RETURNING EMPLOYEES ---
        $stmtVacationReturn = $pdo->prepare("SELECT return_date FROM emp_vacation WHERE emp_id = :emp_id AND DATE_FORMAT(return_date, '%Y-%m') = :month_year AND current_status = 'approved' AND is_deductible = 1");
        $stmtVacationReturn->execute([':emp_id' => $empId, ':month_year' => $monthYear]);
        $vacationReturn = $stmtVacationReturn->fetch(PDO::FETCH_ASSOC);

        $monthStartDate = new DateTime($monthYear . '-01');
        $monthEndDate = new DateTime($monthYear . '-01');
        $monthEndDate->modify('last day of this month');
        $daysInMonth = (int)$monthEndDate->format('d');
        $prorationFactor = 1.0;
        $payableStartDate = clone $monthStartDate;

        $joiningDateValue = $employeeStatus['joining_date'] ?? null;
        if (!empty($joiningDateValue) && $joiningMonthYear === $monthYear) {
            $joiningDate = DateTime::createFromFormat('Y-m-d', $joiningDateValue);
            if (!$joiningDate instanceof DateTime) {
                $joiningTimestamp = strtotime((string)$joiningDateValue);
                if ($joiningTimestamp !== false) {
                    $joiningDate = new DateTime(date('Y-m-d', $joiningTimestamp));
                }
            }

            if ($joiningDate instanceof DateTime && $joiningDate > $payableStartDate) {
                $payableStartDate = clone $joiningDate;
            }
        }

        if ($vacationReturn) {
            $returnDate = new DateTime($vacationReturn['return_date']);
            if ($returnDate > $payableStartDate) {
                $payableStartDate = clone $returnDate;
            }
        }

        if ($payableStartDate > $monthEndDate) {
            continue; // Nothing payable in this month
        }

        $daysWorked = ((int)$monthEndDate->diff($payableStartDate)->format('%a')) + 1;
        if ($daysWorked > 0 && $daysWorked < $daysInMonth) {
            $prorationFactor = $daysWorked / $daysInMonth;
        }
        
        foreach ($salaryComponents as $key => $value) {
            $salaryComponents[$key] = $value * $prorationFactor;
        }
        
        // Calculate the total gross salary
        $totalGrossSalary = array_sum(array_map('floatval', $salaryComponents));
        
        // --- CHECK: Skip automatic calculations if payroll already generated ---
        // If payroll exists and has manually added benefits/deductions, preserve them
        $stmtCheckPayroll = $pdo->prepare("SELECT id FROM payrolls WHERE emp_id = :emp_id AND month_year = :month_year");
        $stmtCheckPayroll->execute([':emp_id' => $empId, ':month_year' => $monthYear]);
        $existingPayroll = $stmtCheckPayroll->fetch(PDO::FETCH_ASSOC);
        
        $isRegeneration = !empty($existingPayroll);
        
        if (!$isRegeneration) {
            // Only add automatic benefits/deductions on initial payroll generation
            // --- LEAVE DEDUCTION LOGIC ---
            addOrUpdateLeaveDeduction($pdo, $empId, $monthYear, $totalGrossSalary);

            // --- (NEW) VACATION WORKING DAYS SALARY ---
            addVacationWorkingDaysSalary($pdo, $empId, $monthYear, $totalGrossSalary);

            // --- (NEW) LOAN DEDUCTION LOGIC ---
            addOrUpdateLoanDeduction($pdo, $empId, $monthYear);
        }

        // --- (NEW) RECORD LOAN PAYMENTS IN emp_loan_payments FOR THIS MONTH ---
        // Scan payroll_deductions for this employee/month and persist to loan payments
        try {
            $likeLoan = '%LN-%';
            $likeAdv  = 'Advance Salary Deduction - %';
            $stmtPd = $pdo->prepare("SELECT id, emp_id, deduction, note FROM payroll_deductions WHERE emp_id = :emp_id AND month = :month AND status = 1 AND (deduction LIKE :likeLoan OR deduction LIKE :likeAdv)");
            $stmtPd->execute([':emp_id' => $empId, ':month' => $monthYear, ':likeLoan' => $likeLoan, ':likeAdv' => $likeAdv]);
            $rows = $stmtPd->fetchAll(PDO::FETCH_ASSOC);
            foreach ($rows as $pd) {
                $deduction = $pd['deduction'];
                $amount = floatval($pd['note']);
                // Parse inv_no (prefer regex)
                $inv_no = null;
                if (preg_match('/\\b(LN-[A-Za-z0-9\\-]+)/', $deduction, $m)) {
                    $inv_no = $m[1];
                } else {
                    $parts = explode(' - ', $deduction);
                    $candidate = trim(end($parts));
                    if (strpos($candidate, 'LN-') === 0) {
                        $inv_no = $candidate;
                    }
                }
                if (!$inv_no) { continue; }
                // Resolve loan_id (prefer matching emp_id)
                $stmtLoan = $pdo->prepare('SELECT id FROM emp_loan WHERE inv_no = :inv AND emp_id = :emp LIMIT 1');
                $stmtLoan->execute([':inv' => $inv_no, ':emp' => $empId]);
                $loanRow = $stmtLoan->fetch(PDO::FETCH_ASSOC);
                if (!$loanRow) {
                    $stmtLoan2 = $pdo->prepare('SELECT id FROM emp_loan WHERE inv_no = :inv LIMIT 1');
                    $stmtLoan2->execute([':inv' => $inv_no]);
                    $loanRow = $stmtLoan2->fetch(PDO::FETCH_ASSOC);
                }
                if (!$loanRow) { continue; }
                $loan_id = intval($loanRow['id']);
                // Avoid duplicates for month
                $stmtExists = $pdo->prepare("SELECT id FROM emp_loan_payments WHERE loan_id = :loan AND payment_method = 'payroll' AND DATE_FORMAT(payment_date, '%Y-%m') = :month LIMIT 1");
                $stmtExists->execute([':loan' => $loan_id, ':month' => $monthYear]);
                $exists = $stmtExists->fetch(PDO::FETCH_ASSOC);
                if ($exists) { continue; }
                // Insert payment (use first day of month)
                $stmtPay = $pdo->prepare("INSERT INTO emp_loan_payments (loan_id, payment_date, amount, receipt_id, attachment, payment_method) VALUES (:loan, :date, :amount, NULL, NULL, 'payroll')");
                $stmtPay->execute([':loan' => $loan_id, ':date' => $monthYear . '-01', ':amount' => $amount]);
            }
        } catch (Exception $e) {
            // Continue processing even if loan payment recording fails
        }

        // Cleanup: ensure no loan deductions persist for loans set to manual mode for this employee/month
        // This handles cases where mode was switched after scheduling or legacy rows exist
        $stmtCleanup = $pdo->prepare("DELETE pd FROM payroll_deductions pd
            WHERE pd.emp_id = :emp_id AND pd.month = :month_year AND (
                EXISTS (
                    SELECT 1 FROM emp_loan el
                    WHERE el.emp_id = pd.emp_id
                      AND el.deduction_mode = 'manual'
                      AND pd.deduction LIKE CONCAT('%', el.inv_no, '%')
                )
            )");
        $stmtCleanup->execute([':emp_id' => $empId, ':month_year' => $monthYear]);


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
        // --- CRITICAL FIX: Only recalculate totals on initial generation, preserve on regeneration ---
        if (!$isRegeneration) {
            // First-time generation: Calculate totals from scratch
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
            
            // Calculate total deductions
            $stmtDeductionsSum = $pdo->prepare("SELECT COALESCE(SUM(CAST(pd.note AS DECIMAL(10,2))), 0)
                    FROM payroll_deductions pd
                    WHERE pd.emp_id = :emp_id
                        AND pd.month = :month_year
                        AND pd.status = 1
                        AND NOT EXISTS (
                                SELECT 1 FROM emp_loan el
                                WHERE el.emp_id = pd.emp_id
                                    AND el.deduction_mode = 'manual'
                                    AND pd.deduction LIKE CONCAT('%', el.inv_no, '%')
                        )");
            $stmtDeductionsSum->execute([':emp_id' => $empId, ':month_year' => $monthYear]);
            $totalDeductions = (float)$stmtDeductionsSum->fetchColumn();
        } else {
            // Regeneration: Get existing totals from payroll record to preserve manual changes
            $stmtExisting = $pdo->prepare("SELECT total_benefits, total_deductions FROM payrolls WHERE emp_id = :emp_id AND month_year = :month_year");
            $stmtExisting->execute([':emp_id' => $empId, ':month_year' => $monthYear]);
            $existingTotals = $stmtExisting->fetch(PDO::FETCH_ASSOC);
            $totalBenefits = floatval($existingTotals['total_benefits'] ?? 0);
            $totalDeductions = floatval($existingTotals['total_deductions'] ?? 0);
        }
        
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
    
    // Determine response status: 'warning' if employees skipped, 'success' if all processed
    $responseStatus = !empty($skippedEmployees) ? 'warning' : 'success';
    
    // $message = "Payroll processed for $processedCount employees.";
    $message = sprintf(__('payroll_processed_for_employees'), $processedCount);
    if (!empty($skippedEmployees)) {
        // Build a readable list of skipped employees with their reasons
        $skippedList = array_map(function($item) {
            $reason = $item['reason'];
            if (!empty($item['blocked_month_label'])) {
                $reason .= ' - ' . $item['blocked_month_label'];
            }
            return __('emp_id') . ": " . $item['emp_id'] . " (" . $reason . ")";
        }, $skippedEmployees);
        $message .= " \n\n".__('skipped') ." " . count($skippedEmployees) . " " . __('employees') . ":\n" . implode("\n", $skippedList);
    }
    if (!$skipApprovalChecks && !empty($requestInvNo)) {
        $completeStmt = $pdo->prepare("UPDATE payroll_approval_requests
            SET status = CASE WHEN :processed_count > 0 THEN 'completed' ELSE status END,
                processed_at = CASE WHEN :processed_count > 0 THEN NOW() ELSE processed_at END,
                processed_by = CASE WHEN :processed_count > 0 THEN :processed_by ELSE processed_by END
            WHERE request_inv_no = :request_inv_no");
        $completeStmt->execute([
            ':processed_count' => (int)$processedCount,
            ':processed_by' => (string)$requestedBy,
            ':request_inv_no' => $requestInvNo
        ]);
    }

    echo json_encode([
        'status' => $responseStatus,
        'message' => $message,
        'processed_count' => $processedCount,
        'skipped_count' => count($skippedEmployees),
        'skipped_employees' => $skippedEmployees,
        'request_inv_no' => $requestInvNo
    ]);
} catch (Exception $e) {
    // If any error occurs, roll back the transaction
    if($pdo->inTransaction()){
        $pdo->rollBack();
    }
    // error_log('Payroll processing error: ' . $e->getMessage());
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
 * 
 * ENHANCED WITH SKIP LOGIC:
 * - Checks emp_loan_monthly_status table for month-specific skip status (status = 0)
 * - If month is skipped, calculates carry-forward amount for next active months
 * - Distributes skipped amounts across remaining loan term
 * - Respects manual deduction mode
 */
function addOrUpdateLoanDeduction($pdo, $empId, $monthYear) {
    // If employee has any approved loans set to manual mode, do not auto-insert deductions
    $payrollMonthEnd = date('Y-m-t', strtotime($monthYear . '-01'));
    $stmtHasManual = $pdo->prepare("SELECT 1 FROM emp_loan WHERE emp_id = :emp_id AND status = 'approved' AND deduction_mode = 'manual' AND (start_date IS NULL OR start_date = '0000-00-00' OR start_date <= :month_end) LIMIT 1");
    $stmtHasManual->execute([':emp_id' => $empId, ':month_end' => $payrollMonthEnd]);
    if ($stmtHasManual->fetchColumn()) {
        return; // Respect manual mode: no automatic loan deduction creation
    }
    
    // Check if ANY loan-related deduction already exists for this month
    $stmtCheck = $pdo->prepare("SELECT id FROM payroll_deductions WHERE emp_id = :emp_id AND month = :month_year AND (deduction = 'Loan Installment' OR deduction LIKE '%Loan%')");
    $stmtCheck->execute([':emp_id' => $empId, ':month_year' => $monthYear]);
    if ($stmtCheck->fetch()) {
        return; // Skip if any loan deduction already exists
    }
    
    // Fetch latest approved automatic loan
    $stmtLoan = $pdo->prepare("SELECT id, inv_no, total_payable, monthly_deduction, deduction_mode, start_date, end_date, created_at FROM emp_loan WHERE emp_id = :emp_id AND status = 'approved' AND deduction_mode = 'automatic' ORDER BY id DESC LIMIT 1");
    $stmtLoan->execute([':emp_id' => $empId]);
    $loan = $stmtLoan->fetch(PDO::FETCH_ASSOC);
    
    if (!$loan) {
        return; // No automatic loan found
    }
    
    // Extra safety: skip if deduction_mode somehow not automatic
    if (isset($loan['deduction_mode']) && $loan['deduction_mode'] !== 'automatic') {
        return; // Manual mode: do not auto insert deduction
    }
    
    // Determine effective start date: prefer start_date, fallback to created_at
    $rawStart = $loan['start_date'];
    if (!$rawStart || $rawStart === '0000-00-00') {
        $rawStart = substr($loan['created_at'], 0, 10);
    }
    
    // If effective start date is after payroll month end, skip until that month
    if (strtotime($rawStart) > strtotime($payrollMonthEnd)) {
        return; // Not started yet in this payroll period
    }
    
    $loanId = $loan['id'];
    
    // === NEW: CHECK MONTHLY STATUS ===
    // Check if this month is marked as skip (status = 0) in emp_loan_monthly_status
    $stmtMonthStatus = $pdo->prepare("SELECT status, skip_reason FROM emp_loan_monthly_status WHERE loan_id = :loan_id AND month_year = :month_year");
    $stmtMonthStatus->execute([':loan_id' => $loanId, ':month_year' => $monthYear]);
    $monthStatus = $stmtMonthStatus->fetch(PDO::FETCH_ASSOC);
    
    // If explicitly skipped (status = 0), do not create deduction
    if ($monthStatus && $monthStatus['status'] == 0) {
        // Skip this month - the deduction will be carried forward automatically
        // when future months are processed
        return;
    }
    
    // Calculate remaining balance and skipped amounts
    $stmtPaid = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) FROM emp_loan_payments WHERE loan_id = :loan_id");
    $stmtPaid->execute([':loan_id' => $loanId]);
    $totalPaid = $stmtPaid->fetchColumn();
    $remainingBalance = $loan['total_payable'] - $totalPaid;
    
    if ($remainingBalance <= 0) {
        // Loan fully paid, mark as paid if not already
        $stmtUpdateLoan = $pdo->prepare("UPDATE emp_loan SET status = 'paid' WHERE id = :loan_id AND status != 'paid'");
        $stmtUpdateLoan->execute([':loan_id' => $loanId]);
        return;
    }
    
    // === CARRY-FORWARD LOGIC ===
    // Count how many months were skipped before this month
    $stmtSkippedCount = $pdo->prepare("
        SELECT COUNT(*) 
        FROM emp_loan_monthly_status 
        WHERE loan_id = :loan_id 
        AND status = 0 
        AND month_year < :month_year 
        AND month_year >= :start_month
    ");
    $startMonth = date('Y-m', strtotime($rawStart));
    $stmtSkippedCount->execute([
        ':loan_id' => $loanId, 
        ':month_year' => $monthYear,
        ':start_month' => $startMonth
    ]);
    $skippedMonthsCount = $stmtSkippedCount->fetchColumn();
    
    // Calculate how many months remain from current month to end date
    $currentMonthDate = new DateTime($monthYear . '-01');
    $endDate = new DateTime($loan['end_date']);
    $interval = $currentMonthDate->diff($endDate);
    $remainingMonths = ($interval->y * 12) + $interval->m + 1; // +1 to include current month
    
    // Calculate deduction amount with carry-forward
    $baseMonthlyDeduction = floatval($loan['monthly_deduction']);
    $skippedAmount = $skippedMonthsCount * $baseMonthlyDeduction;
    
    // Distribute skipped amount across remaining months
    $carryForwardPerMonth = $remainingMonths > 0 ? ($skippedAmount / $remainingMonths) : 0;
    $deductionAmount = $baseMonthlyDeduction + $carryForwardPerMonth;
    
    // Don't exceed remaining balance
    $deductionAmount = min($deductionAmount, $remainingBalance);
    
    // Insert deduction with carry-forward note if applicable
    $deductionNote = $skippedMonthsCount > 0 
        ? "Including " . number_format($carryForwardPerMonth, 2) . " SAR carry-forward from {$skippedMonthsCount} skipped month(s)"
        : "";
    
    $stmtInsertDeduction = $pdo->prepare("INSERT INTO payroll_deductions (emp_id, deduction, note, month, status) VALUES (:emp_id, 'Loan Installment', :amount, :month_year, 1)");
    $stmtInsertDeduction->execute([
        ':emp_id' => $empId, 
        ':amount' => number_format($deductionAmount, 2, '.', ''), 
        ':month_year' => $monthYear
    ]);
    
    // Insert payment record
    $stmtInsertPayment = $pdo->prepare("INSERT INTO emp_loan_payments (loan_id, payment_date, amount, payment_method, notes) VALUES (:loan_id, :payment_date, :amount, 'payroll', :notes)");
    $stmtInsertPayment->execute([
        ':loan_id' => $loanId, 
        ':payment_date' => $payrollMonthEnd, 
        ':amount' => number_format($deductionAmount, 2, '.', ''),
        ':notes' => $deductionNote
    ]);
    
    // Check if loan is now fully paid
    $newTotalPaid = $totalPaid + $deductionAmount;
    if ($newTotalPaid >= $loan['total_payable']) {
        $stmtUpdateLoan = $pdo->prepare("UPDATE emp_loan SET status = 'paid' WHERE id = :loan_id");
        $stmtUpdateLoan->execute([':loan_id' => $loanId]);
    }
}


/**
 * ENHANCED FUNCTION
 * Calculates and inserts benefits for employees on vacation:
 * 1. Working days salary before vacation starts (prorated monthly salary)
 * 2. Vacation salary benefit (if vacation_salary_type = 'payroll')
 * 
 * MODIFIED: Now checks is_deductible flag. If is_deductible = 0 (e.g., Fly + Annual),
 * the vacation does NOT add any payroll benefits. Employee receives their full regular salary.
 */
function addVacationWorkingDaysSalary($pdo, $empId, $monthYear, $totalGrossSalary) {
    // Find approved vacation starting this month
    $stmtVacation = $pdo->prepare("
        SELECT id, start_date, return_date, vac_type, fly_type, vacation_salary_type, vacdays, is_deductible
        FROM emp_vacation
        WHERE emp_id = :emp_id
        AND current_status = 'approved'
        AND DATE_FORMAT(start_date, '%Y-%m') = :month_year
        ORDER BY start_date ASC
        LIMIT 1
    ");
    $stmtVacation->execute([':emp_id' => $empId, ':month_year' => $monthYear]);
    $vacation = $stmtVacation->fetch(PDO::FETCH_ASSOC);
    
    // MODIFIED: If no vacation found, only delete auto-generated vacation benefits, preserve manually added ones
    // Check if there are any manually added benefits by looking for benefits that are NOT auto-generated
    if (!$vacation) {
        // Only remove orphaned auto-generated vacation benefits.
        // Use distinct placeholder names because this query references the same values in outer and inner scopes.
        $stmtDelete = $pdo->prepare("DELETE FROM payroll_benefits 
            WHERE emp_id = :outer_emp_id 
            AND month = :outer_month_year 
            AND (benefit LIKE 'Working Days Salary for Vacation%' OR benefit LIKE 'Vacation Salary Benefit%')
            AND id IN (
                SELECT id FROM (
                    SELECT pb.id FROM payroll_benefits pb
                    LEFT JOIN emp_vacation ev ON pb.benefit LIKE CONCAT('%', CONCAT('(ID: ', ev.id, ')'))
                    WHERE pb.emp_id = :inner_emp_id 
                    AND pb.month = :inner_month_year 
                    AND (pb.benefit LIKE 'Working Days Salary for Vacation%' OR pb.benefit LIKE 'Vacation Salary Benefit%')
                    AND ev.id IS NULL
                ) AS subquery
            )");
        $stmtDelete->execute([
            ':outer_emp_id' => $empId,
            ':outer_month_year' => $monthYear,
            ':inner_emp_id' => $empId,
            ':inner_month_year' => $monthYear
        ]);
        return;
    }
    
    // NEW: Check is_deductible flag
    // If is_deductible = 0 (e.g., Fly + Annual vacation), employee stays in full payroll
    // Do NOT add vacation benefits - they get their complete regular salary
    if (isset($vacation['is_deductible']) && $vacation['is_deductible'] == 0) {
        // Remove any existing vacation benefits (shouldn't exist, but clean up just in case)
        $stmtDelete = $pdo->prepare("DELETE FROM payroll_benefits 
            WHERE emp_id = :emp_id 
            AND month = :month_year 
            AND (benefit LIKE 'Working Days Salary for Vacation%' OR benefit LIKE 'Vacation Salary Benefit%')");
        $stmtDelete->execute([':emp_id' => $empId, ':month_year' => $monthYear]);
        return; // Exit - employee remains in normal payroll with full salary
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
    
    // MODIFIED: Only remove old auto-generated vacation benefits (not manually modified ones)
    // Keep benefits that were manually added/edited by admin
    $stmtDelete = $pdo->prepare("DELETE FROM payroll_benefits 
        WHERE emp_id = :emp_id 
        AND month = :month_year 
        AND (benefit LIKE 'Working Days Salary for Vacation%' OR benefit LIKE 'Vacation Salary Benefit%')
        AND id IN (
            SELECT id FROM (
                SELECT pb.id FROM payroll_benefits pb
                LEFT JOIN emp_vacation ev ON pb.benefit LIKE CONCAT('%', CONCAT('(ID: ', ev.id, ')'))
                WHERE pb.emp_id = :emp_id2
                AND pb.month = :month_year2
                AND (pb.benefit LIKE 'Working Days Salary for Vacation%' OR pb.benefit LIKE 'Vacation Salary Benefit%')
                AND ev.id IS NULL
            ) AS subquery
        )");
    $stmtDelete->execute([':emp_id' => $empId, ':month_year' => $monthYear, ':emp_id2' => $empId, ':month_year2' => $monthYear]);

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