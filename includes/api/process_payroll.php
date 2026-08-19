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

/**
 * Returns true when GOSI is already deducted via vacation payout in the payroll month.
 */
function hasVacationGosiDeductedForMonth(PDO $pdo, $empId, $monthYear) {
    global $conDB;
    $monthStart = $monthYear . '-01';
    $monthEnd = date('Y-m-t', strtotime($monthStart));
    $localVacationMinDays = (int) get_setting_num($conDB, 'vacation_gosi_local_min_days', 20);

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
                    AND COALESCE(v.vacdays, 0) >= :local_min_days
                    AND LOWER(COALESCE(v.vacation_salary_type, '')) = 'payroll'
                )
          )
        LIMIT 1");
    $stmt->execute([
        ':emp_id' => $empId,
        ':month_start' => $monthStart,
        ':month_end' => $monthEnd,
        ':local_min_days' => $localVacationMinDays
    ]);

    return (bool)$stmt->fetch(PDO::FETCH_ASSOC);
}

/**
 * Double-check against settlement_records: if the employee already has a PAID
 * settlement (annual_vacation settlement, completed/processed + payment_date set)
 * tied to a vacation overlapping this payroll month, he must be dropped from
 * payroll entirely - even if the vacation's day count is below the normal
 * dropout/GOSI thresholds (special-case early settlement).
 *
 * Uses LEFT JOIN, not INNER JOIN: some settlement_records rows imported from the
 * live server have no matching emp_vacation row anymore (the vacation record didn't
 * survive the import). For those orphaned settlements there's no date range to
 * compare against, so fall back to the settlement's own payment_date falling
 * inside the payroll month.
 */
function hasPaidVacationSettlementForMonth(PDO $pdo, $empId, $monthYear) {
    $monthStart = $monthYear . '-01';
    $monthEnd = date('Y-m-t', strtotime($monthStart));

    // Uses the employee's ACTUAL return date (an approved rejoin request's
    // final_approved_date, when one exists - same COALESCE precedence as the
    // vacation-return proration query below) rather than the vacation's originally
    // requested return_date. Otherwise an employee who returned early from a
    // settled vacation (rejoin recorded, but emp_vacation.return_date left as the
    // original longer request) gets dropped from payroll for the whole month even
    // though they actually worked the days after their real return.
    $stmt = $pdo->prepare("SELECT sr.id
        FROM settlement_records sr
        LEFT JOIN emp_vacation v ON v.request_inv_no = SUBSTRING(sr.request_inv_no, 6)
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
        WHERE sr.emp_id = :emp_id
          AND sr.request_type = 'annual_vacation'
          AND sr.settlement_status IN ('completed', 'processed')
          AND sr.payment_date IS NOT NULL
          AND (
                (
                    v.id IS NOT NULL
                    AND v.current_status IN ('approved', 'completed')
                    AND v.start_date <= :month_end
                    AND COALESCE(rr.final_approved_date, v.return_date, v.start_date) >= :month_start
                    AND (
                        COALESCE(rr.final_approved_date, v.return_date) IS NULL
                        OR COALESCE(rr.final_approved_date, v.return_date) > :month_end3
                    )
                )
                OR
                (
                    v.id IS NULL
                    AND sr.payment_date BETWEEN :month_start2 AND :month_end2
                )
          )
        LIMIT 1");
    $stmt->execute([
        ':emp_id' => $empId,
        ':month_start' => $monthStart,
        ':month_end' => $monthEnd,
        ':month_start2' => $monthStart,
        ':month_end2' => $monthEnd,
        ':month_end3' => $monthEnd
    ]);

    return (bool)$stmt->fetch(PDO::FETCH_ASSOC);
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

// Self-heal the deduction_types lookup table used below to decide whether a given
// deduction name (GOSI, Loan Installment, etc.) counts toward net-pay deductions.
// Safe no-op once sql/add_payroll_params_settings.sql has been run.
$pdo->exec("CREATE TABLE IF NOT EXISTS `deduction_types` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `name` varchar(100) NOT NULL,
    `counts_in_net` tinyint(1) NOT NULL DEFAULT 1,
    `status` tinyint(4) NOT NULL DEFAULT 1,
    PRIMARY KEY (`id`),
    UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");

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

        // Skip employees only when they are already on Fly annual vacation
        // at the START of the selected payroll month.
        // If vacation starts later in the month, payroll should still be generated.
        // Local annual/emergency vacations remain eligible and stay visible in payroll.
        $payrollMonthStart = $monthYear . '-01';
        $payrollMonthEnd = date('Y-m-t', strtotime($payrollMonthStart));

                $stmtActiveFlyVacation = $pdo->prepare(
                        "SELECT v.id
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
                             AND v.review = 'A'
                             AND v.current_status IN ('approved', 'completed')
                             AND v.start_date <= :month_start_upper
                             AND COALESCE(rr.final_approved_date, v.return_date) >= :month_start_lower
                             AND (
                                    COALESCE(rr.final_approved_date, v.return_date) IS NULL
                                    OR COALESCE(rr.final_approved_date, v.return_date) > :month_end
                             )
                             AND (
                                        LOWER(COALESCE(v.vac_type, '')) = 'fly'
                                        OR LOWER(COALESCE(v.note, '')) = 'fly'
                             )
                         LIMIT 1"
                );
        $stmtActiveFlyVacation->execute([
            ':emp_id' => $empId,
            ':month_start_upper' => $payrollMonthStart,
            ':month_start_lower' => $payrollMonthStart,
            ':month_end' => $payrollMonthEnd
        ]);
        $activeFlyVacation = $stmtActiveFlyVacation->fetch(PDO::FETCH_ASSOC);

        if ($activeFlyVacation) {
            $skippedEmployees[] = [
                'emp_id' => $empId,
                'reason' => 'Employee is on Fly annual vacation at the start of this payroll month'
            ];
            continue;
        }
        // --- CHECK END ---
        // NOTE: an employee who already returned mid-month (rejoin/return date falls
        // between month start and month end) intentionally does NOT hit the skip above -
        // they fall through to the proration block further down, which prorates their
        // salary starting from their actual return date instead of excluding them
        // from payroll entirely.

        // --- VACATION PAYROLL DROPOUT CHECK ---
        // Any active/approved vacation (regardless of type) whose day count exceeds the
        // configured threshold drops the employee out of this month's payroll entirely.
        // Scoped to VAC-* (real vacations) only - LV-* leave/excuse requests (sick,
        // marriage, hajj, maternity, etc.) must never drop an employee out of payroll,
        // no matter how many days they cover.
        // Uses full-month overlap (start_date <= month_end AND return_date >= month_start),
        // not just "active on day 1" - a qualifying vacation starting mid-month must still
        // drop the employee from that month's payroll.
        $vacationDropoutDays = (int) get_setting_num($conDB, 'vacation_payroll_dropout_days', 30);
        if ($vacationDropoutDays > 0) {
            $stmtVacationDropout = $pdo->prepare(
                "SELECT v.id
                 FROM emp_vacation v
                 WHERE v.emp_id = :emp_id
                     AND v.review = 'A'
                     AND v.current_status IN ('approved', 'completed')
                     AND v.start_date <= :month_end
                     AND COALESCE(v.return_date, v.start_date) >= :month_start
                     AND COALESCE(v.vacdays, 0) >= :dropout_days
                     AND v.request_inv_no LIKE 'VAC-%'
                 LIMIT 1"
            );
            $stmtVacationDropout->execute([
                ':emp_id' => $empId,
                ':month_end' => $payrollMonthEnd,
                ':month_start' => $payrollMonthStart,
                ':dropout_days' => $vacationDropoutDays
            ]);
            $vacationDropout = $stmtVacationDropout->fetch(PDO::FETCH_ASSOC);

            if ($vacationDropout) {
                $skippedEmployees[] = [
                    'emp_id' => $empId,
                    'reason' => "Employee's vacation is {$vacationDropoutDays}+ days at the start of this payroll month"
                ];
                continue;
            }
        }

        // Double-check: even if the vacation's day count is below the dropout threshold,
        // a PAID settlement (special-case early payout) tied to a vacation overlapping this
        // month means the employee already got paid for that period - drop from payroll.
        if (hasPaidVacationSettlementForMonth($pdo, $empId, $monthYear)) {
            $skippedEmployees[] = [
                'emp_id' => $empId,
                'reason' => 'Employee already has a paid vacation settlement for this payroll month'
            ];
            continue;
        }
        // --- VACATION PAYROLL DROPOUT CHECK END ---

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
        $monthStartDate = new DateTime($monthYear . '-01');
        $monthStartDate->setTime(0, 0, 0);
        $monthEndDate = new DateTime($monthYear . '-01');
        $monthEndDate->modify('last day of this month');
        $monthEndDate->setTime(0, 0, 0);
        $daysInMonth = 30;
        $prorationFactor = 1.0;
        $payableStartDate = clone $monthStartDate;
        $joinedThisPayrollMonth = false;
        $joiningDate = null;
        $joiningDeductionDays = 0;
        $vacationReturnDate = null;
        $vacationReturnDeductionDays = 0;

        $joiningDateValue = $employeeStatus['joining_date'] ?? null;
        if (!empty($joiningDateValue)) {
            $joiningDate = DateTime::createFromFormat('!Y-m-d', substr((string)$joiningDateValue, 0, 10));
            if (!$joiningDate instanceof DateTime) {
                $joiningTimestamp = strtotime((string)$joiningDateValue);
                if ($joiningTimestamp !== false) {
                    $joiningDate = new DateTime(date('Y-m-d', $joiningTimestamp));
                    $joiningDate->setTime(0, 0, 0);
                }
            }

            // Apply proration when joining happens within the selected payroll month.
            if (
                $joiningDate instanceof DateTime
                && $joiningDate >= $monthStartDate
                && $joiningDate <= $monthEndDate
            ) {
                $joinedThisPayrollMonth = true;
                if ($joiningDate > $payableStartDate) {
                    $payableStartDate = clone $joiningDate;
                }
            }
        }

        if (!$joinedThisPayrollMonth) {
                        // NOTE: intentionally no is_deductible filter here - that flag tracks
                        // whether a vacation deducts from the employee's leave BALANCE
                        // (see leaveHandler.php ~line 1304), not payroll eligibility. Annual
                        // Fly vacations are is_deductible=0 by design, so requiring =1 here
                        // used to silently skip proration for exactly this case, falling back
                        // to a full month's pay instead of prorating from the return date.
                        //
                        // Only prorate/deduct for a vacation that would actually have dropped
                        // this employee out of payroll had it still been ongoing - i.e. its day
                        // count meets the configured dropout threshold - OR one that already has
                        // a paid settlement (already compensated for those days, so must still be
                        // deducted regardless of length). A short vacation under the threshold
                        // with no settlement must not touch payroll at all: full month's pay.
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
                                    AND v.current_status IN ('approved', 'completed')
                                    AND v.request_inv_no LIKE 'VAC-%'
                                    AND v.start_date <= :month_start_upper
                                    AND COALESCE(rr.final_approved_date, v.return_date) >= :month_start_lower
                                    AND COALESCE(rr.final_approved_date, v.return_date) <= :month_end
                                    AND (
                                        COALESCE(v.vacdays, 0) >= :dropout_days_threshold
                                        OR EXISTS (
                                            SELECT 1 FROM settlement_records sr
                                            WHERE sr.request_inv_no = CONCAT('SETL-', v.request_inv_no)
                                                AND sr.request_type = 'annual_vacation'
                                                AND sr.settlement_status IN ('completed', 'processed')
                                                AND sr.payment_date IS NOT NULL
                                        )
                                    )
                                ORDER BY COALESCE(rr.final_approved_date, v.return_date) DESC
                                LIMIT 1");
            $stmtVacationReturn->execute([
                ':emp_id' => $empId,
                                ':month_start_upper' => $monthYear . '-01',
                                ':month_start_lower' => $monthYear . '-01',
                ':month_end' => $monthEndDate->format('Y-m-d'),
                ':dropout_days_threshold' => $vacationDropoutDays
            ]);
            $vacationReturn = $stmtVacationReturn->fetch(PDO::FETCH_ASSOC);

            if ($vacationReturn && !empty($vacationReturn['effective_return_date'])) {
                $effectiveReturnDate = new DateTime($vacationReturn['effective_return_date']);
                $effectiveReturnDate->setTime(0, 0, 0);

                // Chain forward through back-to-back vacation records (e.g. an Emergency
                // vacation starting the day after an Annual vacation's return_date) - a
                // single-row LIMIT 1 lookup only sees the first vacation touching this
                // month and silently drops any immediately-following one, undercounting
                // the unpaid span. Keep extending the effective return date as long as
                // the next vacation starts on/right after the previous one's own return.
                $stmtVacationChainNext = $pdo->prepare("SELECT COALESCE(rr.final_approved_date, v.return_date) AS effective_return_date
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
                            AND v.current_status IN ('approved', 'completed')
                            AND v.request_inv_no LIKE 'VAC-%'
                            AND v.start_date >= :prev_return_date
                            AND v.start_date <= :prev_return_date_plus1
                        ORDER BY v.start_date ASC
                        LIMIT 1");

                $chainGuard = 0;
                while ($chainGuard < 12) {
                    $prevReturnDate = clone $effectiveReturnDate;
                    $prevReturnDatePlus1 = (clone $prevReturnDate)->modify('+1 day');
                    $stmtVacationChainNext->execute([
                        ':emp_id' => $empId,
                        ':prev_return_date' => $prevReturnDate->format('Y-m-d'),
                        ':prev_return_date_plus1' => $prevReturnDatePlus1->format('Y-m-d')
                    ]);
                    $chainedVacation = $stmtVacationChainNext->fetch(PDO::FETCH_ASSOC);
                    if (!$chainedVacation || empty($chainedVacation['effective_return_date'])) {
                        break;
                    }
                    $chainedReturnDate = new DateTime($chainedVacation['effective_return_date']);
                    $chainedReturnDate->setTime(0, 0, 0);
                    if ($chainedReturnDate <= $effectiveReturnDate) {
                        break; // guard against a non-advancing chain
                    }
                    $effectiveReturnDate = $chainedReturnDate;
                    $chainGuard++;
                }

                if ($effectiveReturnDate > $payableStartDate) {
                    $payableStartDate = clone $effectiveReturnDate;
                    $vacationReturnDate = clone $effectiveReturnDate;
                }
            }
        }

        if ($payableStartDate > $monthEndDate) {
            continue; // Nothing payable in this month
        }

        $daysWorked = ((int)$monthEndDate->diff($payableStartDate)->format('%a')) + 1;
        if ($daysWorked > 0 && $daysWorked < $daysInMonth) {
            $prorationFactor = $daysWorked / $daysInMonth;
        }

        // For employees joining during this payroll month, keep full gross salary
        // and apply the pre-joining period as a separate deduction entry.
        if ($joinedThisPayrollMonth && $joiningDate instanceof DateTime) {
            $joiningDeductionDays = max(0, ((int)$joiningDate->format('d')) - 1);
            $prorationFactor = 1.0;
        }

        // Same treatment for employees returning from vacation mid-month: keep full
        // gross salary and show the missed days as an explicit deduction line
        // instead of silently shrinking every salary component, so HR can see
        // exactly how many days are being deducted.
        if ($vacationReturnDate instanceof DateTime) {
            $vacationReturnDeductionDays = max(0, ((int)$vacationReturnDate->format('d')) - 1);
            $prorationFactor = 1.0;
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

        addOrUpdateJoiningDateDeduction($pdo, $empId, $monthYear, $totalGrossSalary, $joiningDeductionDays);
        addOrUpdateVacationReturnDeduction($pdo, $empId, $monthYear, $totalGrossSalary, $vacationReturnDeductionDays);

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
            $vacationHasGosi = hasVacationGosiDeductedForMonth($pdo, $empId, $monthYear);

            if ($vacationHasGosi) {
                // Vacation already deducted GOSI for this month; payroll must not add it again.
                $stmtDeleteGosi = $pdo->prepare("DELETE FROM payroll_deductions
                    WHERE emp_id = :emp_id AND deduction = 'GOSI' AND month = :month_year");
                $stmtDeleteGosi->execute([':emp_id' => $empId, ':month_year' => $monthYear]);
            } else {
                $deductionBaseComponents = json_decode((string) get_setting($conDB, 'deduction_base_components'), true);
                if (!is_array($deductionBaseComponents) || empty($deductionBaseComponents)) {
                    $deductionBaseComponents = ['basic_salary', 'housing_allowance'];
                }
                $deductionBase = 0.0;
                foreach ($deductionBaseComponents as $componentKey) {
                    if (array_key_exists($componentKey, $salaryComponents)) {
                        $deductionBase += floatval($salaryComponents[$componentKey]);
                    }
                }
                $gosiAmount = round($deductionBase * ($employeeData['gosi'] / 100) , 2); // 0.0975
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
                    $overtimeMonthlyHours = get_setting_num($conDB, 'overtime_monthly_hours', 240) ?: 240;
                    $overtimeExtraMultiplier = get_setting_num($conDB, 'overtime_extra_multiplier', 0.5);
                    $hourlyRate = ($basicSalary / $overtimeMonthlyHours * $overtimeExtraMultiplier) + ($totalGrossSalary / $overtimeMonthlyHours);
                    $amount = $hourlyRate * $hours;
                } elseif ($benefit['calculation_type'] === 'overtime_total') {
                    $hours = floatval($benefit['hours'] ?? 0);
                    $overtimeMonthlyHours = get_setting_num($conDB, 'overtime_monthly_hours', 240) ?: 240;
                    $amount = ($totalGrossSalary / $overtimeMonthlyHours) * $hours;
                } else {
                    $amount = floatval($benefit['note']);
                }
                $totalBenefits += $amount;
            }
            
            // Calculate total deductions
            $stmtDeductionsSum = $pdo->prepare("SELECT COALESCE(SUM(CAST(pd.note AS DECIMAL(10,2))), 0)
                    FROM payroll_deductions pd
                    LEFT JOIN deduction_types dt ON LOWER(TRIM(dt.name)) = LOWER(TRIM(pd.deduction))
                    WHERE pd.emp_id = :emp_id
                        AND pd.month = :month_year
                        AND pd.status = 1
                        AND COALESCE(dt.counts_in_net, 1) = 1
                        AND NOT EXISTS (
                                SELECT 1 FROM emp_loan el
                                WHERE el.emp_id = pd.emp_id
                                    AND el.deduction_mode = 'manual'
                                    AND pd.deduction LIKE CONCAT('%', el.inv_no, '%')
                        )");
            $stmtDeductionsSum->execute([':emp_id' => $empId, ':month_year' => $monthYear]);
            $totalDeductions = round((float)$stmtDeductionsSum->fetchColumn(), 0);
        } else {
            // Regeneration: Recalculate totals from current rows (including joining-date deduction)
            $stmtBenefitsSum = $pdo->prepare("SELECT COALESCE(SUM(CAST(note AS DECIMAL(10,2))), 0)
                FROM payroll_benefits
                WHERE emp_id = :emp_id AND month = :month_year AND status = 1
            ");
            $stmtBenefitsSum->execute([':emp_id' => $empId, ':month_year' => $monthYear]);
            $totalBenefits = (float)$stmtBenefitsSum->fetchColumn();

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
                        )
            ");
            $stmtDeductionsSum->execute([':emp_id' => $empId, ':month_year' => $monthYear]);
            $totalDeductions = round((float)$stmtDeductionsSum->fetchColumn(), 0);
        }
        
        // Calculate net salary
        $netSalary = round($totalGrossSalary + $totalBenefits - $totalDeductions, 0);
        
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
                status = VALUES(status),
                generated_at = CURRENT_TIMESTAMP
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
            SET status = CASE WHEN :processed_count_status > 0 THEN 'completed' ELSE status END,
                processed_at = CASE WHEN :processed_count_time > 0 THEN NOW() ELSE processed_at END,
                processed_by = CASE WHEN :processed_count_user > 0 THEN :processed_by ELSE processed_by END
            WHERE request_inv_no = :request_inv_no");
        $completeStmt->execute([
            ':processed_count_status' => (int)$processedCount,
            ':processed_count_time' => (int)$processedCount,
            ':processed_count_user' => (int)$processedCount,
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
 * Maintains a fixed joining-date deduction row for employees who join mid-month.
 * Deduction is calculated on a 30-day divisor and represents days before joining.
 */
function addOrUpdateJoiningDateDeduction($pdo, $empId, $monthYear, $totalGrossSalary, $joiningDeductionDays) {
    $stmtDelete = $pdo->prepare("DELETE FROM payroll_deductions
        WHERE emp_id = :emp_id AND month = :month_year AND deduction LIKE 'Joining Date Deduction%'");
    $stmtDelete->execute([':emp_id' => $empId, ':month_year' => $monthYear]);

    $days = (int)$joiningDeductionDays;
    if ($days <= 0) {
        return;
    }

    $amount = round((floatval($totalGrossSalary) / 30) * $days, 2);
    if ($amount <= 0) {
        return;
    }

    $label = 'Joining Date Deduction (' . $days . ' days)';
    $stmtInsert = $pdo->prepare("INSERT INTO payroll_deductions
        (emp_id, deduction, note, month, status, calculation_type, hours, days)
        VALUES (:emp_id, :deduction, :amount, :month_year, 1, 'daily_deduction', NULL, :days)");
    $stmtInsert->execute([
        ':emp_id' => $empId,
        ':deduction' => $label,
        ':amount' => number_format($amount, 2, '.', ''),
        ':month_year' => $monthYear,
        ':days' => $days
    ]);
}

/**
 * Maintains a fixed vacation-return deduction row for employees who return from
 * vacation mid-month. Deduction is calculated on a 30-day divisor and represents
 * the days before the rejoin date, so HR can see exactly how many days were
 * deducted rather than a silently shrunk gross salary.
 */
function addOrUpdateVacationReturnDeduction($pdo, $empId, $monthYear, $totalGrossSalary, $vacationReturnDeductionDays) {
    $stmtDelete = $pdo->prepare("DELETE FROM payroll_deductions
        WHERE emp_id = :emp_id AND month = :month_year AND deduction LIKE 'Vacation Return Deduction%'");
    $stmtDelete->execute([':emp_id' => $empId, ':month_year' => $monthYear]);

    $days = (int)$vacationReturnDeductionDays;
    if ($days <= 0) {
        return;
    }

    $amount = round((floatval($totalGrossSalary) / 30) * $days, 2);
    if ($amount <= 0) {
        return;
    }

    // Fixed amount, not the "daily_deduction" calc type - the day-based type follows the
    // gross-minus-food rule the admin configured for manual day deductions, which doesn't
    // apply here. Day count is recorded in the label instead so HR can see it.
    $label = 'Vacation Return Deduction (' . $days . ' days)';
    $stmtInsert = $pdo->prepare("INSERT INTO payroll_deductions
        (emp_id, deduction, note, month, status, calculation_type, hours, days)
        VALUES (:emp_id, :deduction, :amount, :month_year, 1, 'fixed', NULL, NULL)");
    $stmtInsert->execute([
        ':emp_id' => $empId,
        ':deduction' => $label,
        ':amount' => number_format($amount, 2, '.', ''),
        ':month_year' => $monthYear
    ]);
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