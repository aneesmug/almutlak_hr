<?php
/****************************************************************
 * MODIFICATION SUMMARY:
 * 1. TABLE NAME CHANGE: The SQL query for fetching payroll details has been updated to select from the `payrolls` table instead of `generated_payrolls` to align with the new database schema.
 ****************************************************************/
// get_payroll_details.php
header('Content-Type: application/json');
require_once("./../../includes/db.php"); // Include your database connection file
require_once("./../../includes/session_check.php");
require_once("./../../includes/payroll_approval_helpers.php");

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

$empId = $_GET['emp_id'] ?? '';
$monthYear = $_GET['month'] ?? ''; // Expected format: YYYY-MM
$requestInvNo = trim((string)($_GET['request_inv_no'] ?? $_GET['inv_no'] ?? ''));
$assignedScopeOnly = ((string)($_GET['assigned_scope'] ?? '0') === '1');

if (empty($empId) || empty($monthYear)) {
    echo json_encode(['status' => 'error', 'message' => 'Missing employee ID or month for details.']);
    exit();
}

$pdo = getDbConnection();

try {
    ensurePayrollChecklistFeedbackTable($pdo);

    $currentApproverId = trim((string)($empid ?? $_SESSION['empid'] ?? ''));
    $currentUserType = strtolower(trim((string)($user_type ?? '')));
    $currentEmpType = trim((string)($emp_type ?? ''));
    $isFinanceOfficerChecklistUser = ($currentUserType === 'finance_officer')
        || ($currentUserType === 'finance' && strcasecmp($currentEmpType, 'Manager') !== 0 && (int)($user_dept ?? 0) === 2);
    $isFinanceManagerChecklistUser = ($currentUserType === 'finance'
        && strcasecmp($currentEmpType, 'Manager') === 0
        && (int)($user_dept ?? 0) === 2);

    // Finance manager can use assigned_scope toggle if assigned to companies
    // For now, assume valid until scope check fails
    $isMainFinanceManager = $isFinanceManagerChecklistUser;
    
    if (!$isMainFinanceManager && $assignedScopeOnly) {
        $assignedScopeOnly = false;
    }

    if (($isFinanceOfficerChecklistUser || $isFinanceManagerChecklistUser) && $requestInvNo === '' && $monthYear !== '') {
        $latestRequestStmt = $pdo->prepare("SELECT request_inv_no
            FROM payroll_approval_requests
            WHERE payroll_month = :month_year
            ORDER BY id DESC
            LIMIT 1");
        $latestRequestStmt->execute([':month_year' => $monthYear]);
        $requestInvNo = trim((string)($latestRequestStmt->fetchColumn() ?: ''));
    }

    // 1. Fetch employee details
    $stmtEmployee = $pdo->prepare("SELECT id, name, emp_id, salary, dept, country, gosi, payment_type, comp_no, updated_at
        FROM employees
        WHERE emp_id = :emp_id
    ");
    $stmtEmployee->execute([':emp_id' => $empId]);
    $employee = $stmtEmployee->fetch(PDO::FETCH_ASSOC);

    if (!$employee) {
        echo json_encode(['status' => 'error', 'message' => 'Employee not found.']);
        exit();
    }

    $skipAutoGosiDueToVacation = hasVacationGosiDeductedForMonth($pdo, $empId, $monthYear);

    if (($isFinanceOfficerChecklistUser || $isFinanceManagerChecklistUser) && $currentApproverId !== '' && $requestInvNo !== '') {
        $assignedFinanceCompanyIds = [];
        $assignedScopeRaw = null;

        if ($isFinanceManagerChecklistUser && $assignedScopeOnly) {
            $managerOwnScopeStmt = $pdo->prepare("SELECT selected_company_ids
                FROM payroll_finance_officer_company_defaults
                WHERE finance_manager_emp_id = :manager_id
                  AND finance_officer_emp_id = :officer_id
                ORDER BY id DESC
                LIMIT 1");
            $managerOwnScopeStmt->execute([
                ':manager_id' => $currentApproverId,
                ':officer_id' => $currentApproverId
            ]);
            $assignedScopeRaw = $managerOwnScopeStmt->fetchColumn();

            if ($assignedScopeRaw === false || $assignedScopeRaw === null || trim((string)$assignedScopeRaw) === '') {
                $managerOwnHistoryStmt = $pdo->prepare("SELECT selected_company_ids
                    FROM payroll_finance_verification_history
                    WHERE request_inv_no = :inv_no
                      AND payroll_month = :month_year
                      AND finance_manager_emp_id = :manager_id
                      AND finance_officer_emp_id = :officer_id
                      AND action_type IN ('setup_assigned', 'setup_updated')
                    ORDER BY id DESC
                    LIMIT 1");
                $managerOwnHistoryStmt->execute([
                    ':inv_no' => $requestInvNo,
                    ':month_year' => $monthYear,
                    ':manager_id' => $currentApproverId,
                    ':officer_id' => $currentApproverId
                ]);
                $assignedScopeRaw = $managerOwnHistoryStmt->fetchColumn();
            }
        }

        if ($assignedScopeRaw === false || $assignedScopeRaw === null || trim((string)$assignedScopeRaw) === '') {
            $assignedScopeManagerStmt = $pdo->prepare("SELECT selected_company_ids
                FROM payroll_finance_verification
                WHERE request_inv_no = :inv_no
                  AND payroll_month = :month_year
                  AND finance_manager_emp_id = :finance_user
                  AND is_confirmed = 1
                ORDER BY id DESC
                LIMIT 1");
            $assignedScopeManagerStmt->execute([
                ':inv_no' => $requestInvNo,
                ':month_year' => $monthYear,
                ':finance_user' => $currentApproverId
            ]);
            $assignedScopeRaw = $assignedScopeManagerStmt->fetchColumn();
        }

        if ($assignedScopeRaw === false || $assignedScopeRaw === null || trim((string)$assignedScopeRaw) === '') {
            $assignedScopeManagerUnconfirmedStmt = $pdo->prepare("SELECT selected_company_ids
                FROM payroll_finance_verification
                WHERE request_inv_no = :inv_no
                  AND payroll_month = :month_year
                  AND finance_manager_emp_id = :finance_user
                ORDER BY id DESC
                LIMIT 1");
            $assignedScopeManagerUnconfirmedStmt->execute([
                ':inv_no' => $requestInvNo,
                ':month_year' => $monthYear,
                ':finance_user' => $currentApproverId
            ]);
            $unconfirmedManager = $assignedScopeManagerUnconfirmedStmt->fetchColumn();
            if ($unconfirmedManager !== false && $unconfirmedManager !== null && trim((string)$unconfirmedManager) !== '') {
                $assignedScopeRaw = $unconfirmedManager;
            }
        }

        if ($assignedScopeRaw === false || $assignedScopeRaw === null || trim((string)$assignedScopeRaw) === '') {
            $assignedScopeOfficerStmt = $pdo->prepare("SELECT selected_company_ids
                FROM payroll_finance_verification
                WHERE request_inv_no = :inv_no
                  AND payroll_month = :month_year
                  AND finance_officer_emp_id = :finance_user
                  AND is_confirmed = 1
                ORDER BY id DESC
                LIMIT 1");
            $assignedScopeOfficerStmt->execute([
                ':inv_no' => $requestInvNo,
                ':month_year' => $monthYear,
                ':finance_user' => $currentApproverId
            ]);
            $assignedScopeRaw = $assignedScopeOfficerStmt->fetchColumn();
        }

        if ($assignedScopeRaw === false || $assignedScopeRaw === null || trim((string)$assignedScopeRaw) === '') {
            $assignedScopeOfficerUnconfirmedStmt = $pdo->prepare("SELECT selected_company_ids
                FROM payroll_finance_verification
                WHERE request_inv_no = :inv_no
                  AND payroll_month = :month_year
                  AND finance_officer_emp_id = :finance_user
                ORDER BY id DESC
                LIMIT 1");
            $assignedScopeOfficerUnconfirmedStmt->execute([
                ':inv_no' => $requestInvNo,
                ':month_year' => $monthYear,
                ':finance_user' => $currentApproverId
            ]);
            $unconfirmedOfficer = $assignedScopeOfficerUnconfirmedStmt->fetchColumn();
            if ($unconfirmedOfficer !== false && $unconfirmedOfficer !== null && trim((string)$unconfirmedOfficer) !== '') {
                $assignedScopeRaw = $unconfirmedOfficer;
            }
        }

        $assignedScopeDecoded = json_decode((string)($assignedScopeRaw ?: '[]'), true);
        if (is_array($assignedScopeDecoded)) {
            $assignedFinanceCompanyIds = array_values(array_unique(array_filter(array_map(static function ($value) {
                return trim((string)$value);
            }, $assignedScopeDecoded), static function ($value) {
                return $value !== '';
            })));
        }

        if (empty($assignedFinanceCompanyIds)) {
            echo json_encode(['status' => 'error', 'message' => 'You are not assigned to verify this payroll checklist request.']);
            exit();
        }

        $employeeCompanyNo = trim((string)($employee['comp_no'] ?? ''));
        if ($employeeCompanyNo === '' || !in_array($employeeCompanyNo, $assignedFinanceCompanyIds, true)) {
            echo json_encode(['status' => 'error', 'message' => 'Access denied for this employee scope.']);
            exit();
        }
    }

    // 2. Fetch generated payroll details
    $stmtPayroll = $pdo->prepare("SELECT
            basic_salary, housing_allowance, transport_allowance, food_allowance,
            miscellaneous_allowance, cashier_allowance, fuel_allowance, telephone_allowance,
            other_allowance, guard_allowance, total_gross_salary, total_benefits,
            total_deductions, net_salary, status, generated_at
        FROM payrolls
        WHERE emp_id = :emp_id AND month_year = :month_year
    ");
    $stmtPayroll->execute([':emp_id' => $empId, ':month_year' => $monthYear]);
    $payroll = $stmtPayroll->fetch(PDO::FETCH_ASSOC);

    // Detect whether the employee master (main profile or active salary record) was
    // edited after this payroll snapshot was generated, so the UI can prompt the admin
    // to save/regenerate instead of silently showing stale amounts.
    $masterDataChangedSinceGeneration = false;
    if ($payroll && !empty($payroll['generated_at'])) {
        $generatedAtTs = strtotime($payroll['generated_at']);
        $employeeUpdatedAtTs = !empty($employee['updated_at']) ? strtotime($employee['updated_at']) : false;

        $stmtActiveSalary = $pdo->prepare("SELECT created_at FROM emp_salary
            WHERE emp_id = :emp_id AND status = 1
            ORDER BY id DESC LIMIT 1");
        $stmtActiveSalary->execute([':emp_id' => $empId]);
        $activeSalaryUpdatedAt = $stmtActiveSalary->fetchColumn();
        $activeSalaryUpdatedAtTs = $activeSalaryUpdatedAt ? strtotime($activeSalaryUpdatedAt) : false;

        if ($generatedAtTs !== false) {
            if ($employeeUpdatedAtTs !== false && $employeeUpdatedAtTs > $generatedAtTs) {
                $masterDataChangedSinceGeneration = true;
            }
            if ($activeSalaryUpdatedAtTs !== false && $activeSalaryUpdatedAtTs > $generatedAtTs) {
                $masterDataChangedSinceGeneration = true;
            }
        }
    }

    $joiningDeductionPreviewAmount = 0.0;
    $joiningDeductionPreviewDays = 0;

    if (!$payroll) {
        // If no generated payroll, fetch basic salary components
        $stmtEmpSalary = $pdo->prepare("SELECT basic, housing, transport, food, misc, cashier, fuel, tel, other, guard
            FROM emp_salary WHERE emp_id = :emp_id AND status = 1
        ");
        $stmtEmpSalary->execute([':emp_id' => $empId]);
        $empSalaryData = $stmtEmpSalary->fetch(PDO::FETCH_ASSOC);
        if ($empSalaryData) {
            $monthStartDate = new DateTime($monthYear . '-01');
            $monthStartDate->setTime(0, 0, 0);
            $monthEndDate = new DateTime($monthYear . '-01');
            $monthEndDate->modify('last day of this month');
            $monthEndDate->setTime(0, 0, 0);
            $daysInMonth = 30;
            $prorationFactor = 1.0;

            $joiningDateRaw = trim((string)($employee['joining_date'] ?? ''));
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
                    $joiningDeductionPreviewDays = max(0, ((int)$joiningDate->format('d')) - 1);
                }
            }

            foreach ($empSalaryData as $componentKey => $componentValue) {
                $empSalaryData[$componentKey] = (float)$componentValue * $prorationFactor;
            }

            $joiningDeductionPreviewAmount = round((
                (float)$empSalaryData['basic']
                + (float)$empSalaryData['housing']
                + (float)$empSalaryData['transport']
                + (float)$empSalaryData['food']
                + (float)$empSalaryData['misc']
                + (float)$empSalaryData['cashier']
                + (float)$empSalaryData['fuel']
                + (float)$empSalaryData['tel']
                + (float)$empSalaryData['other']
                + (float)$empSalaryData['guard']
            ) / 30 * $joiningDeductionPreviewDays, 2);

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
                'total_deductions' => $joiningDeductionPreviewAmount,
                'net_salary' => ((float)$empSalaryData['basic'] + (float)$empSalaryData['housing'] + (float)$empSalaryData['transport'] + (float)$empSalaryData['food'] + (float)$empSalaryData['misc'] + (float)$empSalaryData['cashier'] + (float)$empSalaryData['fuel'] + (float)$empSalaryData['tel'] + (float)$empSalaryData['other'] + (float)$empSalaryData['guard']) - $joiningDeductionPreviewAmount,
                'status' => 'not_generated'
            ];
        } else {
             // Handle case where employee has no salary data either
            echo json_encode(['status' => 'error', 'message' => 'No salary data found for this employee.']);
            exit();
        }
    }

    // 3. Fetch specific benefits for the month
    $stmtBenefits = $pdo->prepare("SELECT id, benefit, note, hours, minutes, type_id FROM payroll_benefits
        WHERE emp_id = :emp_id AND month = :month_year
    ");
    $stmtBenefits->execute([':emp_id' => $empId, ':month_year' => $monthYear]);
    $benefits = $stmtBenefits->fetchAll(PDO::FETCH_ASSOC);

    // 4. Fetch specific deductions for the month
    $stmtDeductions = $pdo->prepare("SELECT id, deduction, note, calculation_type, hours, minutes, days FROM payroll_deductions
        WHERE emp_id = :emp_id AND month = :month_year
    ");

    $stmtDeductions->execute([':emp_id' => $empId, ':month_year' => $monthYear]);
    $deductions = $stmtDeductions->fetchAll(PDO::FETCH_ASSOC);

    if (!$payroll || ($payroll['status'] ?? '') === 'not_generated') {
        if ($joiningDeductionPreviewAmount > 0 && $joiningDeductionPreviewDays > 0) {
            $deductions[] = [
                'id' => null,
                'deduction' => 'Joining Date Deduction (' . $joiningDeductionPreviewDays . ' days)',
                'note' => number_format($joiningDeductionPreviewAmount, 2, '.', ''),
                'calculation_type' => 'daily_deduction',
                'hours' => null,
                'minutes' => null,
                'days' => $joiningDeductionPreviewDays
            ];
        }
    }

    // 5. Fetch benefit types (Corrected from before)
    $stmtBenefitTypes = $pdo->prepare("SELECT id, name, calculation_type FROM benefit_types WHERE status = 1");
    $stmtBenefitTypes->execute();
    $benefitTypes = $stmtBenefitTypes->fetchAll(PDO::FETCH_ASSOC);

    $stmtFeedback = $pdo->prepare("SELECT
            f.id,
            f.payroll_month,
            f.feedback_note,
            f.created_at,
            f.status,
            f.resolved_at,
            COALESCE(e.name, al.fullname, al.username, f.approver_id) AS approver_name,
            COALESCE(re.name, ral.fullname, ral.username, f.resolved_by) AS resolved_by_name
        FROM payroll_checklist_feedback f
        LEFT JOIN employees e ON e.emp_id = f.approver_id
        LEFT JOIN admin_login al ON al.emp_id = f.approver_id
        LEFT JOIN employees re ON re.emp_id = f.resolved_by
        LEFT JOIN admin_login ral ON ral.emp_id = f.resolved_by
        WHERE f.emp_id = :emp_id
          AND (f.payroll_month = :month_year OR f.status = 'open')
        ORDER BY f.created_at DESC");
    $stmtFeedback->execute([':emp_id' => $empId, ':month_year' => $monthYear]);
    $feedbacks = $stmtFeedback->fetchAll(PDO::FETCH_ASSOC);
    

    echo json_encode([
        'status' => 'success',
        'employee' => $employee,
        'payroll' => $payroll,
        'benefits' => $benefits,
        'deductions' => $deductions,
        'benefit_types' => $benefitTypes,
        'feedbacks' => $feedbacks,
        'skip_auto_gosi_due_to_vacation' => $skipAutoGosiDueToVacation,
        'master_data_changed_since_generation' => $masterDataChangedSinceGeneration,
        'payroll_generated_at' => $payroll['generated_at'] ?? null,
    ]);

} catch (PDOException $e) {
    error_log('Error fetching payroll details: ' . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => 'Failed to load payroll details.']);
}
?>
