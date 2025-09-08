<?php
/****************************************************************
 * MODIFICATION SUMMARY:
 * 1. ADDED LOAN PAYMENT SYNC ON UPDATE: The script now checks if a deduction being updated is named "Loan Installment".
 * 2. SYNCHRONIZED UPDATE: If it is a loan installment, the script will find the corresponding payment record in the `emp_loan_payments` table for the same employee and month and update its amount to match the new amount from the payroll. This handles cases where a payment is skipped by setting the amount to 0.
 * 3. TRANSACTIONAL SAFETY: The entire operation remains wrapped in a database transaction.
 ****************************************************************/
// Set the content type of the response to JSON
header('Content-Type: application/json');
// Include the database connection file
require_once("./../../includes/db.php");

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

    // --- LOGIC TO HANDLE DELETED BENEFITS ---
    $stmtExistingIds = $pdo->prepare("SELECT id FROM payroll_benefits WHERE emp_id = :emp_id AND month = :month_year");
    $stmtExistingIds->execute([':emp_id' => $empId, ':month_year' => $monthYear]);
    $dbBenefitIds = $stmtExistingIds->fetchAll(PDO::FETCH_COLUMN);
    $submittedBenefitIds = array_filter(array_column($updatedBenefits, 'id'));
    $idsToDelete = array_diff($dbBenefitIds, $submittedBenefitIds);

    if (!empty($idsToDelete)) {
        $placeholders = implode(',', array_fill(0, count($idsToDelete), '?'));
        $stmtDelete = $pdo->prepare("DELETE FROM payroll_benefits WHERE id IN ($placeholders)");
        $stmtDelete->execute(array_values($idsToDelete));
    }

    // --- LOGIC TO HANDLE DELETED DEDUCTIONS ---
    $stmtExistingDeductionIds = $pdo->prepare("SELECT id FROM payroll_deductions 
        WHERE emp_id = :emp_id AND month = :month_year 
        AND (deduction != 'GOSI' OR deduction IS NULL)
    ");
    $stmtExistingDeductionIds->execute([':emp_id' => $empId, ':month_year' => $monthYear]);
    $dbDeductionIds = $stmtExistingDeductionIds->fetchAll(PDO::FETCH_COLUMN);
    $submittedDeductionIds = array_filter(array_column($updatedDeductions, 'id'));
    $deductionIdsToDelete = array_diff($dbDeductionIds, $submittedDeductionIds);

    if (!empty($deductionIdsToDelete)) {
        $placeholders = implode(',', array_fill(0, count($deductionIdsToDelete), '?'));
        $stmtDeleteDeductions = $pdo->prepare("DELETE FROM payroll_deductions WHERE id IN ($placeholders)");
        $stmtDeleteDeductions->execute(array_values($deductionIdsToDelete));
    }


    // --- PROCESS SUBMITTED DATA (UPDATES & INSERTS) ---
    $stmtSalary = $pdo->prepare("SELECT basic, housing, transport, food, misc, cashier, fuel, tel, other, guard FROM emp_salary WHERE emp_id = :emp_id AND status = 1");
    $stmtSalary->execute([':emp_id' => $empId]);
    $salaryComponents = $stmtSalary->fetch(PDO::FETCH_ASSOC);

    if (!$salaryComponents) {
        throw new Exception("No salary components found for employee ID: $empId");
    }
    $totalGrossSalary = array_sum(array_map('floatval', $salaryComponents));

    // --- Process Benefits (Update or Insert) ---
    foreach ($updatedBenefits as $benefit) {
        $benefitName = trim($benefit['benefit'] ?? '');
        $benefitAmount = (float)($benefit['amount'] ?? 0);
        $benefitId = $benefit['id'] ?? null;
        $benefitTypeId = $benefit['type_id'] ?? null;
        $benefitHours = isset($benefit['hours']) ? (float)$benefit['hours'] : null;

        if (empty($benefitName) && $benefitAmount <= 0) continue;

        $calculatedAmount = $benefitAmount;
        if ($benefitTypeId) {
            $stmtBenefitType = $pdo->prepare("SELECT calculation_type FROM benefit_types WHERE id = :id");
            $stmtBenefitType->execute([':id' => $benefitTypeId]);
            $calculationType = $stmtBenefitType->fetchColumn();

            if ($calculationType === 'overtime_basic' && $benefitHours !== null) {
                $basicSalary = (float)$salaryComponents['basic'];
                $hourlyRate = ($basicSalary / 240 / 2) + ($totalGrossSalary / 240);
                $calculatedAmount = $hourlyRate * $benefitHours;
            } elseif ($calculationType === 'overtime_total' && $benefitHours !== null) {
                $calculatedAmount = ($totalGrossSalary / 240) * $benefitHours;
            }
        }

        if ($benefitId) {
            $stmt = $pdo->prepare("UPDATE payroll_benefits SET benefit = :benefit_name, note = :benefit_amount, hours = :hours, type_id = :type_id WHERE id = :id");
            $stmt->execute([':benefit_name' => $benefitName, ':benefit_amount' => number_format($calculatedAmount, 2, '.', ''), ':hours' => $benefitHours, ':type_id' => $benefitTypeId, ':id' => $benefitId]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO payroll_benefits (emp_id, benefit, note, hours, month, status, type_id) VALUES (:emp_id, :benefit_name, :benefit_amount, :hours, :month_year, 1, :type_id)");
            $stmt->execute([':emp_id' => $empId, ':benefit_name' => $benefitName, ':benefit_amount' => number_format($calculatedAmount, 2, '.', ''), ':hours' => $benefitHours, ':month_year' => $monthYear, ':type_id' => $benefitTypeId]);
        }
    }
    
    // --- PROCESS DEDUCTIONS (UPDATE & INSERT) ---
    foreach ($updatedDeductions as $deduction) {
        $deductionName = trim($deduction['name'] ?? $deduction['deduction'] ?? '');
        $deductionAmount = (float)($deduction['amount'] ?? $deduction['note'] ?? 0);
        $deductionId = $deduction['id'] ?? null;

        if ((empty($deductionName) && $deductionAmount <= 0) && strtoupper($deductionName) !== 'LOAN INSTALLMENT') {
            continue;
        }

        if (strtoupper($deductionName) === 'GOSI') {
            if ($deductionId) {
                $stmt = $pdo->prepare("UPDATE payroll_deductions SET note = :deduction_amount WHERE id = :id");
                $stmt->execute([':deduction_amount' => number_format($deductionAmount, 2, '.', ''), ':id' => $deductionId]);
            }
            continue; 
        }

        if ($deductionId) {
            $stmt = $pdo->prepare("UPDATE payroll_deductions SET deduction = :deduction_name, note = :deduction_amount WHERE id = :id");
            $stmt->execute([':deduction_name' => $deductionName, ':deduction_amount' => number_format($deductionAmount, 2, '.', ''), ':id' => $deductionId]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO payroll_deductions (emp_id, deduction, note, month, status) VALUES (:emp_id, :deduction_name, :deduction_amount, :month_year, 1)");
            $stmt->execute([':emp_id' => $empId, ':deduction_name' => $deductionName, ':deduction_amount' => number_format($deductionAmount, 2, '.', ''), ':month_year' => $monthYear]);
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


    // --- RE-CALCULATE TOTALS AND UPDATE MASTER RECORD ---
    $stmtBenefitsSum = $pdo->prepare("SELECT COALESCE(SUM(CAST(note AS DECIMAL(10,2))), 0) FROM payroll_benefits WHERE emp_id = :emp_id AND month = :month_year AND status = 1");
    $stmtBenefitsSum->execute([':emp_id' => $empId, ':month_year' => $monthYear]);
    $totalBenefits = (float)$stmtBenefitsSum->fetchColumn();

    $stmtDeductionsSum = $pdo->prepare("SELECT COALESCE(SUM(CAST(note AS DECIMAL(10,2))), 0) FROM payroll_deductions WHERE emp_id = :emp_id AND month = :month_year AND status = 1");
    $stmtDeductionsSum->execute([':emp_id' => $empId, ':month_year' => $monthYear]);
    $totalDeductions = (float)$stmtDeductionsSum->fetchColumn();

    $netSalary = $totalGrossSalary + $totalBenefits - $totalDeductions;
    
    $stmtUpdateGenerated = $pdo->prepare("UPDATE payrolls SET total_benefits = :total_benefits, total_deductions = :total_deductions, net_salary = :net_salary, status = 'updated' WHERE emp_id = :emp_id AND month_year = :month_year");
    $stmtUpdateGenerated->execute([':total_benefits' => number_format($totalBenefits, 2, '.', ''), ':total_deductions' => number_format($totalDeductions, 2, '.', ''), ':net_salary' => number_format($netSalary, 2, '.', ''), ':emp_id' => $empId, ':month_year' => $monthYear]);

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
        'message' => 'Payroll details updated successfully.',
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
