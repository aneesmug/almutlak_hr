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
require_once("./../../includes/attendance_helpers.php");

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

    // Rule lives in includes/payroll_vacation_rules.php (actual return = approved rejoin
    // date, a vacation ending on month end still covers the month).
    [$cond, $params] = payrollPaidSettlementCondition(':emp_id', $monthStart, $monthEnd);
    $stmt = $pdo->prepare("SELECT {$cond} AS paid");
    $stmt->execute([':emp_id' => $empId] + $params);

    return (int)$stmt->fetchColumn() === 1;
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

// payrolls.paid_date (set by "Mark as Paid") - read by addRejoinArrearsAndVacationHolidaySalary().
ensurePayrollPaidDateColumn($pdo);

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
        // Rule lives in includes/payroll_vacation_rules.php.
        $vacationDropoutDays = getPayrollVacationDropoutDays($conDB);
        $dropoutCond = payrollVacationDropoutCondition($conDB, ':emp_id', ':month_start', ':month_end', 'v');
        if ($dropoutCond !== '') {
            $stmtVacationDropout = $pdo->prepare("SELECT {$dropoutCond} AS dropped");
            $stmtVacationDropout->execute([
                ':emp_id' => $empId,
                ':month_end' => $payrollMonthEnd,
                ':month_start' => $payrollMonthStart,
            ]);

            if ((int)$stmtVacationDropout->fetchColumn() === 1) {
                $skippedEmployees[] = [
                    'emp_id' => $empId,
                    'reason' => 'Employee is ' . lcfirst(payrollVacationDropoutReason($conDB)),
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
        $vacationReturnExcusedDays = 0;   // approved leave days inside a late-return gap
        $vacationReturnLateFrom = null;   // planned return date, when attendance showed a later one
        $returnVacations = [];            // vacation row(s) ending this month (incl. back-to-back chain)
        $vacationReturnSettledThrough = null; // settlement paid-through date, when it extends the deduction

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
                        $vacationReturnQualifierSql = payrollVacationDropoutQualifierSql($conDB, 'v');
                        $stmtVacationReturn = $pdo->prepare("SELECT COALESCE(rr.final_approved_date, v.return_date) AS effective_return_date,
                                    v.id, v.request_inv_no, v.vac_type, v.fly_type, v.vacation_salary_type, v.vacdays, v.is_deductible,
                                    v.start_date, v.return_date, v.overtime_amount, v.other_earnings, v.deduction_amount, v.auto_gosi_deduction,
                                    rr.final_approved_date
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
                                        -- Same vacations that drop an employee from payroll
                                        -- (includes/payroll_vacation_rules.php).
                                        {$vacationReturnQualifierSql}
                                        -- Emergency Fly vacation: abroad and unpaid (no vacation
                                        -- salary in payroll or settlement), so the days before the
                                        -- return are deducted whatever its length.
                                        OR (LOWER(COALESCE(v.vac_type, '')) = 'fly' AND LOWER(COALESCE(v.fly_type, '')) = 'emergency')
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
            ]);
            $vacationReturn = $stmtVacationReturn->fetch(PDO::FETCH_ASSOC);

            if ($vacationReturn && !empty($vacationReturn['effective_return_date'])) {
                $effectiveReturnDate = new DateTime($vacationReturn['effective_return_date']);
                $effectiveReturnDate->setTime(0, 0, 0);
                $returnHasRejoin = !empty($vacationReturn['final_approved_date']);
                $returnVacations[] = $vacationReturn;

                // Chain forward through back-to-back vacation records (e.g. an Emergency
                // vacation starting the day after an Annual vacation's return_date) - a
                // single-row LIMIT 1 lookup only sees the first vacation touching this
                // month and silently drops any immediately-following one, undercounting
                // the unpaid span. Keep extending the effective return date as long as
                // the next vacation starts on/right after the previous one's own return.
                $stmtVacationChainNext = $pdo->prepare("SELECT COALESCE(rr.final_approved_date, v.return_date) AS effective_return_date,
                            v.id, v.request_inv_no, v.vac_type, v.fly_type, v.vacation_salary_type, v.vacdays, v.is_deductible,
                                    v.start_date, v.return_date, v.overtime_amount, v.other_earnings, v.deduction_amount, v.auto_gosi_deduction,
                                    rr.final_approved_date
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
                    $returnHasRejoin = !empty($chainedVacation['final_approved_date']);
                    $returnVacations[] = $chainedVacation;
                    $chainGuard++;
                }

                // No approved rejoin -> return_date is only the PLANNED return. When the
                // employee punches on the device, the real return is the first check-in on/after
                // it (4933: return_date Oct 5, first punch Oct 12 -> Oct 1-11 deducted). Only
                // trusted when the company's device synced during the gap; approved LV-* leave
                // in the gap (e.g. sick leave) is not deducted. No check-in yet = keep planned.
                if (!$returnHasRejoin && $effectiveReturnDate <= $monthEndDate) {
                    $plannedReturn = $effectiveReturnDate->format('Y-m-d');
                    $stmtFirstPunch = $pdo->prepare("SELECT MIN(DATE(date)) FROM attendance
                        WHERE emp_id = :emp_id AND time_in IS NOT NULL AND time_in <> ''
                            AND date >= :from_date AND date < DATE_ADD(:to_date, INTERVAL 1 DAY)");
                    $stmtFirstPunch->execute([':emp_id' => $empId, ':from_date' => $plannedReturn, ':to_date' => $monthEndDate->format('Y-m-d')]);
                    $firstPunch = $stmtFirstPunch->fetchColumn() ?: null;
                    if ($firstPunch !== null && $firstPunch > $plannedReturn) {
                        $stmtReturnComp = $pdo->prepare("SELECT comp_no FROM employees WHERE emp_id = :emp_id LIMIT 1");
                        $stmtReturnComp->execute([':emp_id' => $empId]);
                        $gapEnd = date('Y-m-d', strtotime($firstPunch . ' -1 day'));
                        $deviceWorkedInGap = false;
                        foreach (array_keys(getDeviceSyncedDatesForMonth($pdo, $stmtReturnComp->fetchColumn(), $monthYear)) as $syncedDay) {
                            if ($syncedDay >= $plannedReturn && $syncedDay <= $gapEnd) {
                                $deviceWorkedInGap = true;
                                break;
                            }
                        }
                        if ($deviceWorkedInGap) {
                            $stmtGapLeave = $pdo->prepare("SELECT start_date, COALESCE(return_date, start_date) AS end_date
                                FROM emp_vacation
                                WHERE emp_id = :emp_id AND request_inv_no LIKE 'LV-%'
                                    AND current_status IN ('approved', 'completed')
                                    AND start_date <= :gap_end AND COALESCE(return_date, start_date) >= :gap_start");
                            $stmtGapLeave->execute([':emp_id' => $empId, ':gap_end' => $gapEnd, ':gap_start' => $plannedReturn]);
                            $excusedDates = [];
                            foreach ($stmtGapLeave->fetchAll(PDO::FETCH_ASSOC) as $leave) {
                                $to = min(substr((string) $leave['end_date'], 0, 10), $gapEnd);
                                for ($d = max(substr((string) $leave['start_date'], 0, 10), $plannedReturn); $d <= $to; $d = date('Y-m-d', strtotime($d . ' +1 day'))) {
                                    if ((int) date('j', strtotime($d)) <= 30) {
                                        $excusedDates[$d] = true;
                                    }
                                }
                            }
                            $vacationReturnExcusedDays = count($excusedDates);
                            $vacationReturnLateFrom = $plannedReturn;
                            $effectiveReturnDate = new DateTime($firstPunch);
                            $effectiveReturnDate->setTime(0, 0, 0);
                        }
                    }
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
            $vacationReturnDeductionDays = max(0, min(30, (int)$vacationReturnDate->format('d') - 1) - $vacationReturnExcusedDays);
            $prorationFactor = 1.0;
        }

        // Days of this month already paid by a vacation settlement (vacation salary) must be
        // deducted too, otherwise they are paid twice - by the settlement and by this payroll.
        // Covers: no rejoin, where the "back" date used above is return_date, the settlement's
        // last PAID vacation day (vacdays counts start_date .. return_date); a rejoin on or
        // before return_date (early return / rejoin = return_date); and a settlement paying past
        // month end. Never lowers the deduction - a late return stays deducted to the rejoin day.
        foreach ($returnVacations as $returnVacation) {
            $settledThrough = getVacationSalaryPaidThrough($pdo, $empId, $returnVacation);
            if ($settledThrough === null || $settledThrough < $monthStartDate->format('Y-m-d')) {
                continue;
            }
            $settledDays = $settledThrough > $monthEndDate->format('Y-m-d') ? 30 : min(30, (int) date('j', strtotime($settledThrough)));
            if ($settledDays > $vacationReturnDeductionDays) {
                $vacationReturnDeductionDays = $settledDays;
                $vacationReturnSettledThrough = $settledThrough;
                $prorationFactor = 1.0;
            }
        }

        foreach ($salaryComponents as $key => $value) {
            $salaryComponents[$key] = $value * $prorationFactor;
        }
        
        // Calculate the total gross salary
        $totalGrossSalary = array_sum(array_map('floatval', $salaryComponents));
        
        // --- CHECK: Skip automatic calculations if payroll already generated ---
        // If payroll exists and has manually added benefits/deductions, preserve them
        $stmtCheckPayroll = $pdo->prepare("SELECT id, auto_deduction_enabled, auto_benefit_enabled FROM payrolls WHERE emp_id = :emp_id AND month_year = :month_year");
        $stmtCheckPayroll->execute([':emp_id' => $empId, ':month_year' => $monthYear]);
        $existingPayroll = $stmtCheckPayroll->fetch(PDO::FETCH_ASSOC);

        $isRegeneration = !empty($existingPayroll);

        // Per-employee/month opt-out (set from the payroll details modal, default checked).
        // No existing row yet means this is the very first generation for this month, before
        // the admin has ever had a chance to uncheck anything - always run auto items then.
        $autoDeductionEnabled = $existingPayroll ? ((int)($existingPayroll['auto_deduction_enabled'] ?? 1) !== 0) : true;
        $autoBenefitEnabled = $existingPayroll ? ((int)($existingPayroll['auto_benefit_enabled'] ?? 1) !== 0) : true;

        if ($autoDeductionEnabled) {
            addOrUpdateJoiningDateDeduction($pdo, $empId, $monthYear, $totalGrossSalary, $joiningDeductionDays);
            addOrUpdateVacationReturnDeduction($pdo, $empId, $monthYear, $totalGrossSalary, $vacationReturnDeductionDays,
                $vacationReturnDate, $vacationReturnLateFrom, $vacationReturnSettledThrough);
        }

        if ($autoBenefitEnabled) {
            // Runs on every generation, including regeneration - unlike the benefits/deductions
            // below, it's keyed off source_other_income_id so it never duplicates or touches
            // manually-added benefits, so it's safe to re-check every time (e.g. a schedule added
            // *after* the month was first generated still gets picked up on the next regenerate).
            addOrUpdateScheduledOtherIncome($pdo, $empId, $monthYear);

            // Same every-generation rule: pays last month's post-rejoin days and vacation
            // public holidays the employee missed by being out of last month's payroll.
            addRejoinArrearsAndVacationHolidaySalary($pdo, $empId, $monthYear, $totalGrossSalary);

            // Public holidays among THIS month's deducted vacation days (Vacation Return Deduction).
            if ($autoDeductionEnabled && $vacationReturnDeductionDays > 0) {
                addReturnMonthVacationHolidaySalary($pdo, $empId, $monthYear, $totalGrossSalary, $returnVacations, $vacationReturnDeductionDays);
            }

            // Joined last month but missed last month's paid payroll (e.g. registered after HR
            // marked it paid) - pays those worked days here.
            addNewJoinerArrears($pdo, $empId, $monthYear, $totalGrossSalary, $joiningDate, $autoDeductionEnabled);
        }

        if (!$isRegeneration) {
            // Only add automatic benefits/deductions on initial payroll generation
            if ($autoDeductionEnabled) {
                // --- LEAVE DEDUCTION LOGIC ---
                addOrUpdateLeaveDeduction($pdo, $empId, $monthYear, $totalGrossSalary, $payableStartDate, floatval($salaryComponents['food_allowance']));

                // --- (NEW) LOAN DEDUCTION LOGIC ---
                addOrUpdateLoanDeduction($pdo, $empId, $monthYear);

                // --- (NEW) AUTOMATIC LATE / EARLY-LEAVE DEDUCTION FROM ATTENDANCE ---
                addOrUpdateAttendanceDeduction($pdo, $empId, $monthYear, $totalGrossSalary);
            }

            if ($autoBenefitEnabled) {
                // --- (NEW) VACATION WORKING DAYS SALARY ---
                // Vacation salary uses the components chosen in App Settings > Vacation Payroll
                addVacationWorkingDaysSalary($pdo, $empId, $monthYear, $totalGrossSalary, vacation_salary_monthly_base($salaryComponents, $totalGrossSalary));

                // --- (NEW) AUTOMATIC OVERTIME BENEFIT FROM ATTENDANCE ---
                addOrUpdateAttendanceOvertime($pdo, $empId, $monthYear, $totalGrossSalary, floatval($salaryComponents['basic_salary']));
            }
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
            WHERE pd.emp_id = :emp_id AND pd.month = :month_year AND COALESCE(pd.calculation_type, 'fixed') <> 'loan' AND (
                EXISTS (
                    SELECT 1 FROM emp_loan el
                    WHERE el.emp_id = pd.emp_id
                      AND el.deduction_mode = 'manual'
                      AND pd.deduction LIKE CONCAT('%', el.inv_no, '%')
                )
            )");
        $stmtCleanup->execute([':emp_id' => $empId, ':month_year' => $monthYear]);


        // --- GOSI Deduction Logic ---
        if ($autoDeductionEnabled && $employeeData['country'] === '191') {
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
                        AND (COALESCE(pd.calculation_type, 'fixed') = 'loan' OR NOT EXISTS (
                                SELECT 1 FROM emp_loan el
                                WHERE el.emp_id = pd.emp_id
                                    AND el.deduction_mode = 'manual'
                                    AND pd.deduction LIKE CONCAT('%', el.inv_no, '%')
                        ))");
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
                        AND (COALESCE(pd.calculation_type, 'fixed') = 'loan' OR NOT EXISTS (
                                SELECT 1 FROM emp_loan el
                                WHERE el.emp_id = pd.emp_id
                                    AND el.deduction_mode = 'manual'
                                    AND pd.deduction LIKE CONCAT('%', el.inv_no, '%')
                        ))
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
 * Returns a set (['Y-m-d' => true]) of dates in $monthYear covered by any APPROVED
 * leave of this employee - LV-* excuse leaves and VAC-* vacations alike. Pending,
 * rejected or cancelled requests never excuse a day. return_date is treated as
 * inclusive (LV-* stores the last leave day there; for VAC-* it's lenient by one day).
 */
function getApprovedLeaveDatesForMonth($pdo, $empId, $monthYear) {
    $monthStart = $monthYear . '-01';
    $monthEnd = date('Y-m-t', strtotime($monthStart));

    $stmt = $pdo->prepare("SELECT start_date, COALESCE(return_date, start_date) AS end_date
        FROM emp_vacation
        WHERE emp_id = :emp_id
          AND current_status IN ('approved', 'completed')
          AND start_date <= :month_end
          AND COALESCE(return_date, start_date) >= :month_start");
    $stmt->execute([':emp_id' => $empId, ':month_end' => $monthEnd, ':month_start' => $monthStart]);

    $dates = [];
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $leave) {
        $from = max(substr((string)$leave['start_date'], 0, 10), $monthStart);
        $to = min(substr((string)$leave['end_date'], 0, 10), $monthEnd);
        for ($d = $from; $d <= $to; $d = date('Y-m-d', strtotime($d . ' +1 day'))) {
            $dates[$d] = true;
        }
    }
    return $dates;
}

/**
 * Returns a set (['Y-m-d' => true]) of dates in $monthYear on which the attendance
 * device actually synced for this company - at least 20% of the company's busiest
 * day's headcount punched in. Guards absence detection against sync gaps (device
 * offline, import not run yet), where every employee would otherwise look absent.
 * Cached per company/month since it's called once per employee.
 */
function getDeviceSyncedDatesForMonth($pdo, $compNo, $monthYear) {
    static $cache = [];
    $key = $compNo . '|' . $monthYear;
    if (isset($cache[$key])) {
        return $cache[$key];
    }

    $stmt = $pdo->prepare("SELECT DATE(a.date) AS d, COUNT(DISTINCT a.emp_id) AS n
        FROM attendance a
        JOIN employees e ON e.emp_id = a.emp_id
        WHERE a.source = 'device' AND DATE_FORMAT(a.date, '%Y-%m') = :month_year AND e.comp_no = :comp_no
        GROUP BY DATE(a.date)");
    $stmt->execute([':month_year' => $monthYear, ':comp_no' => $compNo]);
    $counts = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

    $dates = [];
    if (!empty($counts)) {
        $threshold = max(1, (int) ceil(max($counts) * 0.2));
        foreach ($counts as $d => $n) {
            if ((int) $n >= $threshold) {
                $dates[$d] = true;
            }
        }
    }
    return $cache[$key] = $dates;
}

/**
 * Auto-generates an Absence Deduction ((gross - food)/30 per day) for working days with NO
 * check-in, gated by the same "auto attendance deduction" setting as Late/Early.
 * A day is skipped (not deducted) when it is: before $payableStartDate (joining /
 * vacation return - already deducted separately), today or later, a timetable Day
 * Off, an active public holiday for the employee's company, has any attendance row
 * with a check-in (incl. 'Incomplete' = no checkout), or is covered by an APPROVED
 * excuse leave / vacation (getApprovedLeaveDatesForMonth). Employees with no device
 * punches at all this month aren't tracked by attendance and get no deduction.
 */
function addOrUpdateLeaveDeduction($pdo, $empId, $monthYear, $totalGrossSalary, $payableStartDate = null, $foodAllowance = 0) {
    global $conDB;

    // Remove any previous absence deduction for this employee/month
    $stmtDelete = $pdo->prepare("DELETE FROM payroll_deductions WHERE emp_id = :emp_id AND month = :month_year AND deduction LIKE 'Absence Deduction%'");
    $stmtDelete->execute([':emp_id' => $empId, ':month_year' => $monthYear]);

    if ((string) get_setting($conDB, 'deduction_auto_attendance_enabled') !== '1' || !attendance_enabled($pdo)) {
        return;
    }

    $stmtTracked = $pdo->prepare("SELECT COUNT(*) FROM attendance WHERE emp_id = :emp_id AND DATE_FORMAT(date, '%Y-%m') = :month_year AND source = 'device'");
    $stmtTracked->execute([':emp_id' => $empId, ':month_year' => $monthYear]);
    if ((int) $stmtTracked->fetchColumn() === 0) {
        return;
    }

    $stmtEmp = $pdo->prepare("SELECT comp_no FROM employees WHERE emp_id = :emp_id LIMIT 1");
    $stmtEmp->execute([':emp_id' => $empId]);
    $compNo = $stmtEmp->fetchColumn();

    $monthStart = $monthYear . '-01';
    $monthEnd = date('Y-m-t', strtotime($monthStart));
    $from = $monthStart;
    if ($payableStartDate instanceof DateTime && $payableStartDate->format('Y-m-d') > $from) {
        $from = $payableStartDate->format('Y-m-d');
    }
    $to = min($monthEnd, date('Y-m-d', strtotime('-1 day')));
    if ($from > $to) {
        return;
    }

    $stmtPresent = $pdo->prepare("SELECT DISTINCT DATE(date) AS d FROM attendance
        WHERE emp_id = :emp_id AND DATE_FORMAT(date, '%Y-%m') = :month_year
          AND time_in IS NOT NULL AND time_in <> ''");
    $stmtPresent->execute([':emp_id' => $empId, ':month_year' => $monthYear]);
    $presentDates = array_fill_keys($stmtPresent->fetchAll(PDO::FETCH_COLUMN), true);

    $stmtHolidays = $pdo->prepare("SELECT DISTINCT h.start_date, h.end_date FROM emp_holidays h
        LEFT JOIN holiday_companies hc ON h.id = hc.holiday_id
        LEFT JOIN companies hcc ON hcc.id = hc.company_id
        WHERE h.is_active = 1 AND h.start_date <= :month_end AND h.end_date >= :month_start
          AND (hcc.comp_id = :comp_no OR hc.holiday_id IS NULL)");
    $stmtHolidays->execute([':month_end' => $monthEnd, ':month_start' => $monthStart, ':comp_no' => (int) $compNo]);
    $holidayDates = [];
    foreach ($stmtHolidays->fetchAll(PDO::FETCH_ASSOC) as $h) {
        for ($d = max($h['start_date'], $monthStart); $d <= min($h['end_date'], $monthEnd); $d = date('Y-m-d', strtotime($d . ' +1 day'))) {
            $holidayDates[$d] = true;
        }
    }

    $excusedDates = getApprovedLeaveDatesForMonth($pdo, $empId, $monthYear);
    $syncedDates = getDeviceSyncedDatesForMonth($pdo, $compNo, $monthYear);

    $absentDays = 0;
    for ($d = $from; $d <= $to; $d = date('Y-m-d', strtotime($d . ' +1 day'))) {
        if (!isset($syncedDates[$d])) {
            continue; // device didn't sync that day - can't tell absence from missing data
        }
        if (isset($presentDates[$d]) || isset($excusedDates[$d]) || isset($holidayDates[$d])) {
            continue;
        }
        $timetable = attendance_resolve_timetable($conDB, (int) $empId, $compNo, $d);
        if (!empty($timetable['is_off'])) {
            continue;
        }
        $absentDays++;
    }

    if ($absentDays <= 0) {
        return;
    }

    // Same formula as a manual "By Day" deduction in generate_payroll.php:
    // (gross - food) / 240 hourly rate x (days x 8 hours).
    $deductibleSalary = max(floatval($totalGrossSalary) - floatval($foodAllowance), 0);
    $amount = round(($deductibleSalary / 240) * ($absentDays * 8), 2);
    if ($amount <= 0) {
        return;
    }

    $stmtInsert = $pdo->prepare("INSERT INTO payroll_deductions
        (emp_id, deduction, note, month, status, calculation_type, hours, days)
        VALUES (:emp_id, :deduction, :amount, :month_year, 1, 'daily_deduction', NULL, :days)");
    $stmtInsert->execute([
        ':emp_id' => $empId,
        ':deduction' => 'Absence Deduction (' . $absentDays . ' days)',
        ':amount' => number_format($amount, 2, '.', ''),
        ':month_year' => $monthYear,
        ':days' => $absentDays
    ]);
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
function addOrUpdateVacationReturnDeduction($pdo, $empId, $monthYear, $totalGrossSalary, $vacationReturnDeductionDays,
        $returnDate = null, $lateFrom = null, $settledThrough = null) {
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
    // e.g. "Vacation Return Deduction (10 days) back 12 Oct by attendance, planned 5 Oct"
    //      "Vacation Return Deduction (14 days) back 14 Oct, settlement paid to 14 Oct"
    $label = 'Vacation Return Deduction (' . $days . ' days)';
    if ($returnDate instanceof DateTime) {
        $label .= ' back ' . $returnDate->format('j M')
            . ($lateFrom ? ' by attendance, planned ' . date('j M', strtotime($lateFrom)) : '');
    }
    if ($settledThrough) {
        $label .= ($returnDate instanceof DateTime ? ',' : '') . ' settlement paid to ' . date('j M', strtotime($settledThrough));
    }
    $label = mb_substr($label, 0, 100);
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
 * Auto-generates Late/Early-Leave deductions from that month's attendance, gated by
 * Payroll Settings -> Deduction Settings -> "Automatically add Late/Early-Leave
 * deductions..." (deduction_auto_attendance_enabled). Reuses the exact hourly-rate
 * divisor the Overtime formula already uses (overtime_monthly_hours) rather than
 * introducing a separate rate setting - straight-time only, no premium multiplier
 * (that's an overtime concept, not a penalty one). Late hours are measured from the
 * employee's resolved timetable's scheduled check_in (not check_in_end - the grace
 * window only decides whether the day gets flagged Late at all, once flagged the
 * deduction covers the actual time missed from the real shift start). Mirrors the
 * delete-then-insert pattern of the other addOrUpdate*Deduction functions above so
 * regenerating with the setting turned off cleanly removes any prior auto rows.
 */
function addOrUpdateAttendanceDeduction($pdo, $empId, $monthYear, $totalGrossSalary) {
    global $conDB;

    $stmtDelete = $pdo->prepare("DELETE FROM payroll_deductions WHERE emp_id = :emp_id AND month = :month_year AND deduction IN ('Late Deduction', 'Early Leave Deduction')");
    $stmtDelete->execute([':emp_id' => $empId, ':month_year' => $monthYear]);

    if ((string) get_setting($conDB, 'deduction_auto_attendance_enabled') !== '1' || !attendance_enabled($pdo)) {
        return;
    }

    $stmtEmp = $pdo->prepare("SELECT comp_no FROM employees WHERE emp_id = :emp_id LIMIT 1");
    $stmtEmp->execute([':emp_id' => $empId]);
    $compNo = $stmtEmp->fetchColumn();

    $stmtAttendance = $pdo->prepare("SELECT DATE(date) AS d, state, time_in, time_out FROM attendance
        WHERE emp_id = :emp_id AND DATE_FORMAT(date, '%Y-%m') = :month_year AND source = 'device'
          AND (state LIKE '%Late%' OR state LIKE '%Early Leave%')");
    $stmtAttendance->execute([':emp_id' => $empId, ':month_year' => $monthYear]);
    $days = $stmtAttendance->fetchAll(PDO::FETCH_ASSOC);
    if (empty($days)) {
        return;
    }

    $overtimeMonthlyHours = get_setting_num($conDB, 'overtime_monthly_hours', 240) ?: 240;
    $hourlyRate = floatval($totalGrossSalary) / $overtimeMonthlyHours;

    $excusedDates = getApprovedLeaveDatesForMonth($pdo, $empId, $monthYear);

    $lateHours = 0.0;
    $earlyHours = 0.0;
    foreach ($days as $day) {
        if (isset($excusedDates[$day['d']])) {
            continue; // covered by an approved excuse leave / vacation
        }
        $state = (string) $day['state'];
        $timetable = attendance_resolve_timetable($conDB, (int) $empId, $compNo, $day['d']);

        if (strpos($state, 'Late') !== false && $day['time_in'] !== '' && $day['time_in'] > $timetable['check_in']) {
            $lateHours += (strtotime($day['time_in']) - strtotime($timetable['check_in'])) / 3600;
        }
        if (strpos($state, 'Early Leave') !== false && $day['time_out'] !== '' && $day['time_out'] < $timetable['check_out']) {
            $earlyHours += (strtotime($timetable['check_out']) - strtotime($day['time_out'])) / 3600;
        }
    }

    if ($lateHours > 0) {
        $amount = round($hourlyRate * $lateHours, 2);
        if ($amount > 0) {
            $stmtInsert = $pdo->prepare("INSERT INTO payroll_deductions (emp_id, deduction, note, hours, calculation_type, month, status) VALUES (:emp_id, 'Late Deduction', :amount, :hours, 'attendance_auto', :month_year, 1)");
            $stmtInsert->execute([':emp_id' => $empId, ':amount' => number_format($amount, 2, '.', ''), ':hours' => round($lateHours, 2), ':month_year' => $monthYear]);
        }
    }
    if ($earlyHours > 0) {
        $amount = round($hourlyRate * $earlyHours, 2);
        if ($amount > 0) {
            $stmtInsert = $pdo->prepare("INSERT INTO payroll_deductions (emp_id, deduction, note, hours, calculation_type, month, status) VALUES (:emp_id, 'Early Leave Deduction', :amount, :hours, 'attendance_auto', :month_year, 1)");
            $stmtInsert->execute([':emp_id' => $empId, ':amount' => number_format($amount, 2, '.', ''), ':hours' => round($earlyHours, 2), ':month_year' => $monthYear]);
        }
    }
}

/**
 * Auto-generates an Overtime benefit from that month's attendance, gated by Payroll
 * Settings -> Overtime Settings -> "Automatically add an Overtime benefit..."
 * (overtime_auto_attendance_enabled). Only counts days flagged plain 'Present' (no
 * Late/Early-Leave/Incomplete) - staying late doesn't earn overtime on a day already
 * penalized for lateness. Hours are checkout time past the employee's resolved
 * timetable's scheduled check_out. Inserted against benefit_types' existing
 * calculation_type='overtime_basic' row so the amount uses the exact same formula as
 * a manually-added Overtime benefit (see the totals loop above, lines ~848-854) -
 * looked up by calculation_type rather than a hardcoded id in case that row ever
 * changes. Delete-then-insert, same pattern as addOrUpdateAttendanceDeduction().
 */
function addOrUpdateAttendanceOvertime($pdo, $empId, $monthYear, $totalGrossSalary, $basicSalary) {
    global $conDB;

    $stmtDelete = $pdo->prepare("DELETE FROM payroll_benefits WHERE emp_id = :emp_id AND month = :month_year AND benefit = 'Overtime (Auto)'");
    $stmtDelete->execute([':emp_id' => $empId, ':month_year' => $monthYear]);

    if ((string) get_setting($conDB, 'overtime_auto_attendance_enabled') !== '1' || !attendance_enabled($pdo)) {
        return;
    }

    $stmtEmp = $pdo->prepare("SELECT comp_no FROM employees WHERE emp_id = :emp_id LIMIT 1");
    $stmtEmp->execute([':emp_id' => $empId]);
    $compNo = $stmtEmp->fetchColumn();

    $stmtAttendance = $pdo->prepare("SELECT DATE(date) AS d, time_out FROM attendance
        WHERE emp_id = :emp_id AND DATE_FORMAT(date, '%Y-%m') = :month_year AND source = 'device' AND state = 'Present'");
    $stmtAttendance->execute([':emp_id' => $empId, ':month_year' => $monthYear]);
    $days = $stmtAttendance->fetchAll(PDO::FETCH_ASSOC);
    if (empty($days)) {
        return;
    }

    $overtimeHours = 0.0;
    foreach ($days as $day) {
        if ($day['time_out'] === '') {
            continue;
        }
        $timetable = attendance_resolve_timetable($conDB, (int) $empId, $compNo, $day['d']);
        if ($day['time_out'] > $timetable['check_out']) {
            $overtimeHours += (strtotime($day['time_out']) - strtotime($timetable['check_out'])) / 3600;
        }
    }
    $overtimeHours = round($overtimeHours, 2);
    if ($overtimeHours <= 0) {
        return;
    }

    $stmtBenefitType = $pdo->prepare("SELECT id FROM benefit_types WHERE calculation_type = 'overtime_basic' AND status = 1 ORDER BY id ASC LIMIT 1");
    $stmtBenefitType->execute();
    $benefitTypeId = $stmtBenefitType->fetchColumn();
    if (!$benefitTypeId) {
        return; // No Overtime benefit type configured - nothing to attach this to.
    }

    $overtimeMonthlyHours = get_setting_num($conDB, 'overtime_monthly_hours', 240) ?: 240;
    $overtimeExtraMultiplier = get_setting_num($conDB, 'overtime_extra_multiplier', 0.5);
    $hourlyRate = ($basicSalary / $overtimeMonthlyHours * $overtimeExtraMultiplier) + (floatval($totalGrossSalary) / $overtimeMonthlyHours);
    $amount = round($hourlyRate * $overtimeHours, 2);
    if ($amount <= 0) {
        return;
    }

    $stmtInsert = $pdo->prepare("INSERT INTO payroll_benefits (emp_id, benefit, note, hours, calculation_type, month, status, type_id) VALUES (:emp_id, 'Overtime (Auto)', :amount, :hours, 'overtime_basic', :month_year, 1, :type_id)");
    $stmtInsert->execute([
        ':emp_id' => $empId, ':amount' => number_format($amount, 2, '.', ''),
        ':hours' => $overtimeHours, ':month_year' => $monthYear, ':type_id' => $benefitTypeId,
    ]);
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
function addVacationWorkingDaysSalary($pdo, $empId, $monthYear, $totalGrossSalary, $vacationGross = null) {
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
                    $vacationSalary = (($vacationGross ?? $totalGrossSalary) / 30) * $vacationDays;
                    
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

/**
 * Auto-adds "Other Income" payroll_benefits rows for any employee_other_income schedule
 * (e.g. a 3-month Bonus set up on the Additional Information tab) that covers $monthYear.
 * Each schedule is only applied once per month (tracked via source_other_income_id, so
 * re-running this for the same month never duplicates the benefit). Once a schedule's
 * end_month has been processed, the schedule is flipped to status = 0 (inactive) so it
 * stops being picked up by future payroll runs.
 */
function addOrUpdateScheduledOtherIncome($pdo, $empId, $monthYear) {
    $stmtSchedules = $pdo->prepare("
        SELECT id, amount, end_month
        FROM employee_other_income
        WHERE emp_id = :emp_id
        AND status = 1
        AND start_month <= :month_year_start
        AND end_month >= :month_year_end
    ");
    $stmtSchedules->execute([':emp_id' => $empId, ':month_year_start' => $monthYear, ':month_year_end' => $monthYear]);
    $schedules = $stmtSchedules->fetchAll(PDO::FETCH_ASSOC);

    if (!$schedules) {
        return;
    }

    $typeStmt = $pdo->query("SELECT id FROM benefit_types WHERE name = 'Other Income' LIMIT 1");
    $otherIncomeTypeId = $typeStmt ? $typeStmt->fetchColumn() : false;
    $otherIncomeTypeId = ($otherIncomeTypeId !== false) ? (int)$otherIncomeTypeId : null;

    foreach ($schedules as $schedule) {
        $stmtCheck = $pdo->prepare("SELECT id FROM payroll_benefits WHERE source_other_income_id = :schedule_id AND month = :month_year LIMIT 1");
        $stmtCheck->execute([':schedule_id' => $schedule['id'], ':month_year' => $monthYear]);

        if (!$stmtCheck->fetch(PDO::FETCH_ASSOC)) {
            $stmtInsert = $pdo->prepare("
                INSERT INTO payroll_benefits (emp_id, benefit, note, month, status, type_id, calculation_type, source_other_income_id)
                VALUES (:emp_id, 'Other Income', :amount, :month_year, 1, :type_id, 'fixed', :schedule_id)
            ");
            $stmtInsert->execute([
                ':emp_id' => $empId,
                ':amount' => number_format((float)$schedule['amount'], 2, '.', ''),
                ':month_year' => $monthYear,
                ':type_id' => $otherIncomeTypeId,
                ':schedule_id' => $schedule['id'],
            ]);
        }

        // The schedule's last covered month has now been generated - deactivate it so it's
        // no longer picked up (matches "inactive after the time period" behavior).
        if ($monthYear >= $schedule['end_month']) {
            $stmtDeactivate = $pdo->prepare("UPDATE employee_other_income SET status = 0 WHERE id = :id");
            $stmtDeactivate->execute([':id' => $schedule['id']]);
        }
    }
}

/**
 * Pays, in this month's payroll, what an employee coming back from a VAC-* vacation
 * missed because they were out of LAST month's payroll (the vacation dropout / Fly skip /
 * settlement skip drops them for the whole month, so payroll on the 24th never covers
 * days worked after a return on the 25th):
 *
 * Return date = the APPROVED rejoin date (rejoin_requests.final_approved_date); when the
 * vacation has no approved rejoin, falls back to return_date (the planned day back at work;
 * the vacation ends the day before), moved to the first attendance check-in when the
 * employee came back late.
 * Only vacations whose return date falls in last month are handled.
 *
 * 1. "Rejoin Salary" - ONE row per employee per month: gross / 30 per day from the earliest
 *    return date to the end of last month, same 30-day basis as the Vacation Return Deduction
 *    (30 - (day - 1) days), minus days still on another vacation (back-to-back vacations).
 *    Only when the employee has no payroll row for last month (a row = already paid).
 * 2. "Vacation Holiday Salary" - gross / 30 per day for each active public holiday
 *    (emp_holidays, employee's company) inside any vacation touching last month
 *    (start_date .. return_date, up to last month's end - finished or still running), only on
 *    dates in months with no payroll row; paid once per vacation + holiday across all months.
 *
 * Settlement check: when a paid settlement (completed/processed, payment_date set) covered
 * this vacation's VACATION SALARY (same isSettlementPayableVacation + 'payroll' rule as
 * settlement_handler.php), the days it paid are worked back from its total (the vacation may
 * have been extended after the settlement): holidays up to that paid-through date are
 * skipped, later ones paid; an early return only pays after the paid-through date.
 * Emergency / non-payroll vacations get no vacation salary, so all their holidays and
 * early-return days are paid here.
 *
 * Runs on every generation; rows are keyed by month (RJ) or vacation + holiday id, so re-running
 * never duplicates and HR edits to an existing row are kept.
 */
function addRejoinArrearsAndVacationHolidaySalary($pdo, $empId, $monthYear, $totalGrossSalary) {
    global $conDB;

    $prevMonth = date('Y-m', strtotime($monthYear . '-01 -1 month'));
    $prevStart = $prevMonth . '-01';
    $prevEnd = date('Y-m-t', strtotime($prevStart));
    $dailyRate = floatval($totalGrossSalary) / 30;
    if ($dailyRate <= 0) {
        return;
    }

    $stmtVacations = $pdo->prepare("SELECT v.id, v.request_inv_no, v.vac_type, v.fly_type, v.vacation_salary_type,
            v.vacdays, v.is_deductible, v.start_date, v.return_date, rr.final_approved_date,
            v.overtime_amount, v.other_earnings, v.deduction_amount, v.auto_gosi_deduction,
            COALESCE(rr.final_approved_date, v.return_date) AS back_date
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
            AND v.return_date IS NOT NULL
            AND v.start_date <= :prev_end
            AND COALESCE(rr.final_approved_date, v.return_date) >= :prev_start");
    $stmtVacations->execute([':emp_id' => $empId, ':prev_start' => $prevStart, ':prev_end' => $prevEnd]);
    // Every vacation touching last month (needed to know which days were still on vacation);
    // only those whose return date falls inside last month trigger anything.
    $vacations = $stmtVacations->fetchAll(PDO::FETCH_ASSOC);

    // No approved rejoin -> return_date is only the PLANNED return. When the employee is
    // on the attendance device, the real return is his first check-in from that day on (a
    // late return must not be paid). Only trusted when the device was syncing for his company
    // during the gap (other employees punched) - during a sync gap the planned date is kept.
    // No check-in at all until month end = not back last month (picked up next month).
    $stmtTracked = $pdo->prepare("SELECT COUNT(*) FROM attendance WHERE emp_id = :emp_id AND source = 'device' AND DATE_FORMAT(date, '%Y-%m') = :month_year");
    $stmtTracked->execute([':emp_id' => $empId, ':month_year' => $prevMonth]);
    if ((int) $stmtTracked->fetchColumn() > 0) {
        $stmtCompNo = $pdo->prepare("SELECT comp_no FROM employees WHERE emp_id = :emp_id LIMIT 1");
        $stmtCompNo->execute([':emp_id' => $empId]);
        $syncedDates = getDeviceSyncedDatesForMonth($pdo, $stmtCompNo->fetchColumn(), $prevMonth);
        $stmtFirstPunch = $pdo->prepare("SELECT MIN(DATE(date)) FROM attendance
            WHERE emp_id = :emp_id AND time_in IS NOT NULL AND time_in <> ''
                AND date >= :from_date AND date < DATE_ADD(:to_date, INTERVAL 1 DAY)");
        foreach ($vacations as &$vac) {
            $planned = substr((string) $vac['back_date'], 0, 10);
            if ($vac['final_approved_date'] || $planned < $prevStart || $planned > $prevEnd) {
                continue;
            }
            $stmtFirstPunch->execute([':emp_id' => $empId, ':from_date' => $planned, ':to_date' => $prevEnd]);
            $firstPunch = $stmtFirstPunch->fetchColumn() ?: null;
            if ($firstPunch === $planned) {
                $vac['back_source'] = 'attendance';
                continue;
            }
            $gapEnd = $firstPunch ? date('Y-m-d', strtotime($firstPunch . ' -1 day')) : $prevEnd;
            $deviceWorkedInGap = false;
            foreach (array_keys($syncedDates) as $d) {
                if ($d >= $planned && $d <= $gapEnd) {
                    $deviceWorkedInGap = true;
                    break;
                }
            }
            if (!$deviceWorkedInGap) {
                continue; // sync gap - can't prove the late return, keep the planned date
            }
            $vac['back_date'] = $firstPunch ?: date('Y-m-d', strtotime($prevEnd . ' +1 day'));
            $vac['back_source'] = 'attendance';
        }
        unset($vac);
    }
    $returnedInMonth = function ($v) use ($prevStart, $prevEnd) {
        $back = substr((string) $v['back_date'], 0, 10);
        return $back >= $prevStart && $back <= $prevEnd;
    };
    if (!$vacations) {
        return;
    }

    // Back pay only for a month the employee was truly left out of: his company's payroll
    // for that month is PAID (status 'paid'), and he has no row in it. Treated as "covered"
    // (nothing added) when he has a row there in any status (paid, or still to be paid in
    // that month), or when the company's payroll for that month isn't paid yet / was never
    // generated - he could still be added there. Since this runs on every generation, a
    // regenerate after that month is marked paid picks the back pay up.
    $stmtPaidMonth = $pdo->prepare("SELECT
            SUM(p.emp_id = :emp_id) AS own_rows,
            SUM(p.status = 'paid') AS company_paid_rows
        FROM payrolls p
        JOIN employees e ON e.emp_id = p.emp_id
        WHERE p.month_year = :month_year
            AND e.comp_no = (SELECT comp_no FROM employees WHERE emp_id = :emp_id2 LIMIT 1)");
    $paidMonths = [];
    $wasPaid = function ($month) use ($stmtPaidMonth, $empId, &$paidMonths) {
        if (!isset($paidMonths[$month])) {
            $stmtPaidMonth->execute([':emp_id' => $empId, ':emp_id2' => $empId, ':month_year' => $month]);
            $row = $stmtPaidMonth->fetch(PDO::FETCH_ASSOC);
            $paidMonths[$month] = (int) $row['own_rows'] > 0 || (int) $row['company_paid_rows'] === 0;
        }
        return $paidMonths[$month];
    };

    // Keyed by "<prefix>...<key>" so a re-run with a different day count never adds a 2nd row.
    $stmtExists = $pdo->prepare("SELECT COUNT(*) FROM payroll_benefits WHERE emp_id = :emp_id AND month = :month_year AND benefit LIKE :pattern");
    $stmtInsert = $pdo->prepare("INSERT INTO payroll_benefits (emp_id, benefit, note, days, calculation_type, month, status)
        VALUES (:emp_id, :benefit, :amount, :days, 'fixed', :month_year, 1)");
    $insertOnce = function ($prefix, $label, $key, $days) use ($stmtExists, $stmtInsert, $empId, $monthYear, $dailyRate) {
        $stmtExists->execute([':emp_id' => $empId, ':month_year' => $monthYear, ':pattern' => $prefix . '%' . $key]);
        if ((int) $stmtExists->fetchColumn() > 0) {
            return;
        }
        $amount = round($dailyRate * $days, 2);
        if ($amount <= 0) {
            return;
        }
        $stmtInsert->execute([
            ':emp_id' => $empId,
            ':benefit' => mb_substr($prefix . $label, 0, 100 - mb_strlen($key)) . $key,
            ':amount' => number_format($amount, 2, '.', ''),
            ':days' => $days,
            ':month_year' => $monthYear,
        ]);
    };

    $stmtEmp = $pdo->prepare("SELECT comp_no FROM employees WHERE emp_id = :emp_id LIMIT 1");
    $stmtEmp->execute([':emp_id' => $empId]);
    $compNo = (int) $stmtEmp->fetchColumn();

    $stmtHolidays = $pdo->prepare("SELECT DISTINCT h.id, h.holiday_name, h.start_date, h.end_date
        FROM emp_holidays h
        LEFT JOIN holiday_companies hc ON h.id = hc.holiday_id
        LEFT JOIN companies hcc ON hcc.id = hc.company_id
        WHERE h.is_active = 1 AND h.start_date <= :vac_last_day AND h.end_date >= :vac_start
            AND (hcc.comp_id = :comp_no OR hc.holiday_id IS NULL)
        ORDER BY h.start_date");

    // Days of last month NOT payable as Rejoin Salary: still on vacation (start .. back - 1,
    // incl. a late-return gap), or already paid by a settlement's vacation salary (an early
    // return before return_date - the settlement paid through its paid-through date).
    $covered = [];
    foreach ($vacations as &$vac) {
        $vac['salary_paid_through'] = getVacationSalaryPaidThrough($pdo, $empId, $vac);
        $coverEnd = date('Y-m-d', strtotime(substr((string) $vac['back_date'], 0, 10) . ' -1 day'));
        if ($vac['salary_paid_through'] !== null) {
            $coverEnd = max($coverEnd, $vac['salary_paid_through']);
        }
        $to = min($coverEnd, $prevEnd);
        for ($d = max(substr((string) $vac['start_date'], 0, 10), $prevStart); $d <= $to; $d = date('Y-m-d', strtotime($d . ' +1 day'))) {
            $covered[$d] = true;
        }
    }
    unset($vac);
    $returned = array_filter($vacations, $returnedInMonth);

    // Date HR transferred last month's salaries for this company (Mark as Paid popup).
    // Employees back BEFORE it could have been included in that payroll, so only returns
    // on/after it are paid here. Months marked paid before paid_date existed have no date
    // -> no cutoff.
    $stmtPaidDate = $pdo->prepare("SELECT MIN(p.paid_date) FROM payrolls p
        JOIN employees e ON e.emp_id = p.emp_id
        WHERE p.month_year = :month_year AND p.status = 'paid' AND p.paid_date IS NOT NULL
            AND e.comp_no = :comp_no");
    $stmtPaidDate->execute([':month_year' => $prevMonth, ':comp_no' => $compNo]);
    $salaryPaidDate = $stmtPaidDate->fetchColumn() ?: null;

    // 1. Rejoin Salary - once per employee per month, even for back-to-back vacations.
    //    30-day basis like the Vacation Return Deduction: 30 - (first back day - 1), minus
    //    covered days (day 31 never counts).
    $first = null;
    foreach ($wasPaid($prevMonth) ? [] : $returned as $vac) {
        if ($salaryPaidDate !== null && substr((string) $vac['back_date'], 0, 10) < $salaryPaidDate) {
            continue; // back before salaries were paid - should have been in that payroll
        }
        if ($first === null || $vac['back_date'] < $first['back_date']) {
            $first = $vac;
        }
    }
    if ($first !== null) {
        $firstBack = substr((string) $first['back_date'], 0, 10);
        $days = max(0, 30 - ((int) date('j', strtotime($firstBack)) - 1));
        $workedFrom = null;
        $workedTo = null;
        for ($d = $firstBack; $d <= $prevEnd; $d = date('Y-m-d', strtotime($d . ' +1 day'))) {
            if (isset($covered[$d])) {
                if ((int) date('j', strtotime($d)) <= 30) {
                    $days--;
                }
                continue;
            }
            $workedFrom = $workedFrom ?? $d;
            $workedTo = $d;
        }
        if ($days > 0 && $workedFrom !== null) {
            // e.g. "Rejoin Salary Sep 2026: 3 days (28-30 Sep) by attendance (RJ: 2026-09)"
            $range = $workedFrom === $workedTo
                ? date('j M', strtotime($workedFrom))
                : date('j', strtotime($workedFrom)) . '-' . date('j M', strtotime($workedTo));
            $insertOnce('Rejoin Salary ', date('M Y', strtotime($prevStart)) . ': ' . $days . ' ' . ($days === 1 ? 'day' : 'days')
                . ' (' . $range . ') by ' . ($first['final_approved_date'] ? 'rejoin' : ($first['back_source'] ?? 'return date')),
                ' (RJ: ' . $prevMonth . ')', $days);
        }
    }

    // 2. Salary for public holidays inside a vacation - every vacation touching last month,
    //    not only finished ones, so a holiday is paid in the first payroll after its month
    //    (e.g. holiday Sep 24 inside a vacation ending Oct 1 -> October payroll). Holidays up
    //    to the end of last month only; later ones come in a later payroll. Skipped when the
    //    settlement's vacation salary already paid every calendar day of the vacation.
    //    One payment per vacation + holiday across ALL months (the same vacation is seen again
    //    next month while it still overlaps).
    $stmtHolidayPaidBefore = $pdo->prepare("SELECT COUNT(*) FROM payroll_benefits
        WHERE emp_id = :emp_id AND benefit LIKE :pattern");
    foreach ($vacations as $vac) {
        $vacId = (int) $vac['id'];
        $vacStart = substr((string) $vac['start_date'], 0, 10);
        // Last day ON vacation = the day before the planned/approved return (approved rejoin,
        // else return_date). Not the attendance date - a late-return gap isn't vacation.
        $plannedBack = substr((string) ($vac['final_approved_date'] ?: $vac['return_date']), 0, 10);
        $vacLastDay = min(date('Y-m-d', strtotime($plannedBack . ' -1 day')), $prevEnd);

        // Days up to the settlement's paid-through date were paid as vacation salary already.
        $paidThrough = $vac['salary_paid_through'];
        if ($paidThrough !== null && $paidThrough >= $vacLastDay) {
            continue;
        }
        if ($vacLastDay < $vacStart) {
            continue;
        }
        $stmtHolidays->execute([':vac_last_day' => $vacLastDay, ':vac_start' => $vacStart, ':comp_no' => $compNo]);
        foreach ($stmtHolidays->fetchAll(PDO::FETCH_ASSOC) as $h) {
            $holidayDates = [];
            $to = min($h['end_date'], $vacLastDay);
            for ($d = max($h['start_date'], $vacStart); $d <= $to; $d = date('Y-m-d', strtotime($d . ' +1 day'))) {
                if (($paidThrough === null || $d > $paidThrough) && !$wasPaid(substr($d, 0, 7))) {
                    $holidayDates[] = $d;
                }
            }
            $holidayDays = count($holidayDates);
            $holidayKey = ' (VID: ' . $vacId . ', HID: ' . (int) $h['id'] . ')';
            $stmtHolidayPaidBefore->execute([':emp_id' => $empId, ':pattern' => 'Vacation Holiday Salary: %' . $holidayKey]);
            if ($holidayDays > 0 && (int) $stmtHolidayPaidBefore->fetchColumn() === 0) {
                // e.g. "Vacation Holiday Salary: National Day 24 Sep 2026, 1 day (VID: 1823, HID: 12)"
                $firstH = reset($holidayDates);
                $lastH = end($holidayDates);
                $when = $firstH === $lastH
                    ? date('j M Y', strtotime($firstH))
                    : (substr($firstH, 0, 7) === substr($lastH, 0, 7)
                        ? date('j', strtotime($firstH)) . '-' . date('j M Y', strtotime($lastH))
                        : date('j M', strtotime($firstH)) . ' - ' . date('j M Y', strtotime($lastH)));
                $insertOnce('Vacation Holiday Salary: ', mb_substr($h['holiday_name'], 0, 30) . ' ' . $when . ', '
                    . $holidayDays . ' ' . ($holidayDays === 1 ? 'day' : 'days'), $holidayKey, $holidayDays);
            }
        }
    }
}

/**
 * Public holidays inside the vacation days deducted THIS month by the Vacation Return
 * Deduction (days 1 .. N of the return month). The deduction takes those days away, so a
 * holiday among them is paid back as "Vacation Holiday Salary" - unless a paid settlement
 * already paid that day as vacation salary. addRejoinArrearsAndVacationHolidaySalary() only
 * pays holidays of months the employee was left out of, so the return month is handled here.
 * Same "Vacation Holiday Salary: ... (VID: x, HID: y)" key as there - one payment per
 * vacation + holiday across all months.
 *   e.g. "Vacation Holiday Salary: Eid al-Fitr 1-3 Apr 2027, 3 days (VID: 2400, HID: 40)"
 */
function addReturnMonthVacationHolidaySalary($pdo, $empId, $monthYear, $totalGrossSalary, array $returnVacations, $deductionDays) {
    $dailyRate = floatval($totalGrossSalary) / 30;
    $deductionDays = min(30, (int) $deductionDays);
    if ($dailyRate <= 0 || $deductionDays <= 0 || !$returnVacations) {
        return;
    }
    $monthStart = $monthYear . '-01';
    $deductedTo = date('Y-m-d', strtotime($monthStart . ' +' . ($deductionDays - 1) . ' days'));

    $stmtComp = $pdo->prepare("SELECT comp_no FROM employees WHERE emp_id = :emp_id LIMIT 1");
    $stmtComp->execute([':emp_id' => $empId]);
    $compNo = (int) $stmtComp->fetchColumn();
    $stmtHolidays = $pdo->prepare("SELECT DISTINCT h.id, h.holiday_name, h.start_date, h.end_date
        FROM emp_holidays h
        LEFT JOIN holiday_companies hc ON h.id = hc.holiday_id
        LEFT JOIN companies hcc ON hcc.id = hc.company_id
        WHERE h.is_active = 1 AND h.start_date <= :last_day AND h.end_date >= :first_day
            AND (hcc.comp_id = :comp_no OR hc.holiday_id IS NULL)
        ORDER BY h.start_date");
    $stmtPaidBefore = $pdo->prepare("SELECT COUNT(*) FROM payroll_benefits WHERE emp_id = :emp_id AND benefit LIKE :pattern");
    $stmtInsert = $pdo->prepare("INSERT INTO payroll_benefits (emp_id, benefit, note, days, calculation_type, month, status)
        VALUES (:emp_id, :benefit, :amount, :days, 'fixed', :month_year, 1)");

    foreach ($returnVacations as $vac) {
        // Last day ON vacation = day before the approved rejoin / planned return (same rule as
        // addRejoinArrearsAndVacationHolidaySalary) - a late-return gap isn't vacation.
        $plannedBack = substr((string) ($vac['final_approved_date'] ?: $vac['return_date']), 0, 10);
        $firstDay = max(substr((string) $vac['start_date'], 0, 10), $monthStart);
        $lastDay = min(date('Y-m-d', strtotime($plannedBack . ' -1 day')), $deductedTo);
        if ($lastDay < $firstDay) {
            continue;
        }
        $paidThrough = getVacationSalaryPaidThrough($pdo, $empId, $vac);
        if ($paidThrough !== null && $paidThrough >= $lastDay) {
            continue; // every deducted vacation day was paid by the settlement, holidays included
        }

        $stmtHolidays->execute([':last_day' => $lastDay, ':first_day' => $firstDay, ':comp_no' => $compNo]);
        foreach ($stmtHolidays->fetchAll(PDO::FETCH_ASSOC) as $h) {
            $dates = [];
            $to = min($h['end_date'], $lastDay);
            for ($d = max($h['start_date'], $firstDay); $d <= $to; $d = date('Y-m-d', strtotime($d . ' +1 day'))) {
                if (($paidThrough === null || $d > $paidThrough) && (int) date('j', strtotime($d)) <= 30) {
                    $dates[] = $d;
                }
            }
            $days = count($dates);
            $key = ' (VID: ' . (int) $vac['id'] . ', HID: ' . (int) $h['id'] . ')';
            $stmtPaidBefore->execute([':emp_id' => $empId, ':pattern' => 'Vacation Holiday Salary: %' . $key]);
            if ($days <= 0 || (int) $stmtPaidBefore->fetchColumn() > 0) {
                continue;
            }
            $amount = round($dailyRate * $days, 2);
            $firstH = reset($dates);
            $lastH = end($dates);
            $when = $firstH === $lastH ? date('j M Y', strtotime($firstH)) : date('j', strtotime($firstH)) . '-' . date('j M Y', strtotime($lastH));
            $label = 'Vacation Holiday Salary: ' . mb_substr($h['holiday_name'], 0, 30) . ' ' . $when . ', ' . $days . ' ' . ($days === 1 ? 'day' : 'days');
            $stmtInsert->execute([
                ':emp_id' => $empId,
                ':benefit' => mb_substr($label, 0, 100 - mb_strlen($key)) . $key,
                ':amount' => number_format($amount, 2, '.', ''),
                ':days' => $days,
                ':month_year' => $monthYear,
            ]);
        }
    }
}

/**
 * New joiner left out of last month's payroll: joined last month, the company's payroll for
 * that month is already PAID, and the employee has no row in it (typically registered after HR marked
 * it paid). Pays joining day .. month end (30-day basis, day 31 never counts, same as the
 * Joining Date Deduction) as one benefit row in this payroll, e.g.
 *   "New Joiner Salary Sep 2026: 5 days (26-30 Sep), joined after Sep payroll paid 24 Sep (NJ: 2026-09)"
 * Keyed by "(NJ: YYYY-MM)" across all months, so it is paid once and HR edits are kept.
 *
 * Saudi employees (country 191) also get that month's GOSI share for the same days as a
 * separate deduction: GOSI base (deduction_base_components, default basic + housing) x gosi%
 * / 30 x days, e.g. "GOSI New Joiner Sep 2026: 18 days (NJ: 2026-09)". Named apart from the
 * month's own "GOSI" row so the regular GOSI logic never merges or overwrites it.
 */
function addNewJoinerArrears($pdo, $empId, $monthYear, $totalGrossSalary, $joiningDate, $addGosi = true) {
    if (!$joiningDate instanceof DateTime) {
        return;
    }
    $prevMonth = date('Y-m', strtotime($monthYear . '-01 -1 month'));
    $joined = $joiningDate->format('Y-m-d');
    if (substr($joined, 0, 7) !== $prevMonth) {
        return;
    }
    $dailyRate = floatval($totalGrossSalary) / 30;
    $joinDay = (int) $joiningDate->format('j');
    $days = max(0, 30 - ($joinDay - 1));
    if ($dailyRate <= 0 || $days <= 0) {
        return;
    }

    $stmtMonth = $pdo->prepare("SELECT
            SUM(p.emp_id = :emp_id) AS own_rows,
            SUM(p.status = 'paid') AS company_paid_rows,
            MIN(CASE WHEN p.status = 'paid' THEN p.paid_date END) AS paid_date
        FROM payrolls p
        JOIN employees e ON e.emp_id = p.emp_id
        WHERE p.month_year = :month_year
            AND e.comp_no = (SELECT comp_no FROM employees WHERE emp_id = :emp_id2 LIMIT 1)");
    $stmtMonth->execute([':emp_id' => $empId, ':emp_id2' => $empId, ':month_year' => $prevMonth]);
    $row = $stmtMonth->fetch(PDO::FETCH_ASSOC);
    // In last month's payroll already, or that payroll isn't paid yet (he can still be added there).
    if ((int) $row['own_rows'] > 0 || (int) $row['company_paid_rows'] === 0) {
        return;
    }

    $key = ' (NJ: ' . $prevMonth . ')';
    if ($addGosi) {
        addNewJoinerGosiDeduction($pdo, $empId, $monthYear, $prevMonth, $days, $key);
    }
    $stmtExists = $pdo->prepare("SELECT COUNT(*) FROM payroll_benefits WHERE emp_id = :emp_id AND benefit LIKE :pattern");
    $stmtExists->execute([':emp_id' => $empId, ':pattern' => 'New Joiner Salary %' . $key]);
    if ((int) $stmtExists->fetchColumn() > 0) {
        return;
    }

    $prevEnd = date('Y-m-t', strtotime($prevMonth . '-01'));
    $lastDay = min($prevEnd, $prevMonth . '-30');
    $range = $joined === $lastDay ? date('j M', strtotime($joined)) : $joinDay . '-' . date('j M', strtotime($lastDay));
    $why = $row['paid_date']
        ? 'joined after ' . date('M', strtotime($joined)) . ' payroll paid ' . date('j M', strtotime($row['paid_date']))
        : 'not in ' . date('M', strtotime($joined)) . ' paid payroll';
    $label = 'New Joiner Salary ' . date('M Y', strtotime($joined)) . ': ' . $days . ' ' . ($days === 1 ? 'day' : 'days')
        . ' (' . $range . '), ' . $why;
    $stmtInsert = $pdo->prepare("INSERT INTO payroll_benefits (emp_id, benefit, note, days, calculation_type, month, status)
        VALUES (:emp_id, :benefit, :amount, :days, 'fixed', :month_year, 1)");
    $stmtInsert->execute([
        ':emp_id' => $empId,
        ':benefit' => mb_substr($label, 0, 100 - mb_strlen($key)) . $key,
        ':amount' => number_format(round($dailyRate * $days, 2), 2, '.', ''),
        ':days' => $days,
        ':month_year' => $monthYear,
    ]);
}

/**
 * GOSI for a new joiner's back-paid days (see addNewJoinerArrears) - Saudi employees only,
 * once per joining month across all months.
 */
function addNewJoinerGosiDeduction($pdo, $empId, $monthYear, $prevMonth, $days, $key) {
    global $conDB;

    $stmtEmp = $pdo->prepare("SELECT e.country, e.gosi, s.basic, s.housing, s.transport, s.food, s.misc,
            s.cashier, s.fuel, s.tel, s.other, s.guard
        FROM employees e JOIN emp_salary s ON s.emp_id = e.emp_id AND s.status = 1
        WHERE e.emp_id = :emp_id ORDER BY s.id DESC LIMIT 1");
    $stmtEmp->execute([':emp_id' => $empId]);
    $emp = $stmtEmp->fetch(PDO::FETCH_ASSOC);
    if (!$emp || (string) $emp['country'] !== '191' || !is_numeric($emp['gosi']) || (float) $emp['gosi'] <= 0) {
        return;
    }

    $stmtExists = $pdo->prepare("SELECT COUNT(*) FROM payroll_deductions WHERE emp_id = :emp_id AND deduction LIKE :pattern");
    $stmtExists->execute([':emp_id' => $empId, ':pattern' => 'GOSI New Joiner %' . $key]);
    if ((int) $stmtExists->fetchColumn() > 0) {
        return;
    }

    // Same GOSI base as the monthly GOSI row (payroll settings), default basic + housing.
    $componentMap = [
        'basic_salary' => 'basic', 'housing_allowance' => 'housing', 'transport_allowance' => 'transport',
        'food_allowance' => 'food', 'miscellaneous_allowance' => 'misc', 'cashier_allowance' => 'cashier',
        'fuel_allowance' => 'fuel', 'telephone_allowance' => 'tel', 'other_allowance' => 'other',
        'guard_allowance' => 'guard',
    ];
    $baseComponents = json_decode((string) get_setting($conDB, 'deduction_base_components'), true);
    if (!is_array($baseComponents) || empty($baseComponents)) {
        $baseComponents = ['basic_salary', 'housing_allowance'];
    }
    $base = 0.0;
    foreach ($baseComponents as $component) {
        if (isset($componentMap[$component])) {
            $base += (float) $emp[$componentMap[$component]];
        }
    }
    $amount = round($base * ((float) $emp['gosi'] / 100) / 30 * $days, 2);
    if ($amount <= 0) {
        return;
    }

    $label = 'GOSI New Joiner ' . date('M Y', strtotime($prevMonth . '-01')) . ': ' . $days . ' ' . ($days === 1 ? 'day' : 'days');
    $stmtInsert = $pdo->prepare("INSERT INTO payroll_deductions (emp_id, deduction, note, days, calculation_type, month, status)
        VALUES (:emp_id, :deduction, :amount, :days, 'fixed', :month_year, 1)");
    $stmtInsert->execute([
        ':emp_id' => $empId,
        ':deduction' => $label . $key,
        ':amount' => number_format($amount, 2, '.', ''),
        ':days' => $days,
        ':month_year' => $monthYear,
    ]);
}
?>