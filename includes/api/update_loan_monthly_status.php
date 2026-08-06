<?php
/**
 * Update Loan Monthly Status API
 * 
 * Manages the monthly skip/active status for automatic loan deductions
 * Allows marking specific months to skip deduction (status = 0) or activate (status = 1)
 */

header('Content-Type: application/json');
require_once("./../../includes/db.php");
require_once("./../../includes/session_check.php");

// This endpoint skips/activates monthly loan deductions and previously had no auth
// check at all. Restrict to system admin / HR Payroll.
if (!(($is_system_admin ?? false) || ($isHR_Payroll ?? false))) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Access denied.']);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);

$loanId = $input['loan_id'] ?? '';
$monthYear = $input['month_year'] ?? '';
$status = isset($input['status']) ? (int)$input['status'] : 1;
$skipReason = $input['skip_reason'] ?? '';

// Validation
if (empty($loanId) || empty($monthYear)) {
    echo json_encode(['status' => 'error', 'message' => 'Missing loan ID or month.']);
    exit();
}

// Validate month format (YYYY-MM)
if (!preg_match('/^\d{4}-\d{2}$/', $monthYear)) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid month format. Use YYYY-MM.']);
    exit();
}

// Validate status (0 or 1)
if ($status !== 0 && $status !== 1) {
    echo json_encode(['status' => 'error', 'message' => 'Status must be 0 (skip) or 1 (active).']);
    exit();
}

$pdo = getDbConnection();

try {
    $pdo->beginTransaction();
    
    // Verify loan exists and is automatic
    $stmtLoan = $pdo->prepare("SELECT id, emp_id, inv_no, deduction_mode FROM emp_loan WHERE id = :loan_id");
    $stmtLoan->execute([':loan_id' => $loanId]);
    $loan = $stmtLoan->fetch(PDO::FETCH_ASSOC);
    
    if (!$loan) {
        throw new Exception('Loan not found.');
    }
    
    if ($loan['deduction_mode'] !== 'automatic') {
        throw new Exception('This feature only works for automatic deduction mode loans.');
    }
    
    // Check if status already exists for this month
    $stmtCheck = $pdo->prepare("SELECT id FROM emp_loan_monthly_status WHERE loan_id = :loan_id AND month_year = :month_year");
    $stmtCheck->execute([':loan_id' => $loanId, ':month_year' => $monthYear]);
    $existing = $stmtCheck->fetch(PDO::FETCH_ASSOC);
    
    if ($existing) {
        // Update existing record
        $stmtUpdate = $pdo->prepare("UPDATE emp_loan_monthly_status SET status = :status, skip_reason = :skip_reason, updated_at = NOW() WHERE id = :id");
        $stmtUpdate->execute([
            ':status' => $status,
            ':skip_reason' => $status == 0 ? $skipReason : null,
            ':id' => $existing['id']
        ]);
        $message = 'Monthly status updated successfully.';
    } else {
        // Insert new record
        $stmtInsert = $pdo->prepare("INSERT INTO emp_loan_monthly_status (loan_id, month_year, status, skip_reason) VALUES (:loan_id, :month_year, :status, :skip_reason)");
        $stmtInsert->execute([
            ':loan_id' => $loanId,
            ':month_year' => $monthYear,
            ':status' => $status,
            ':skip_reason' => $status == 0 ? $skipReason : null
        ]);
        $message = 'Monthly status created successfully.';
    }
    
    // If setting to skip (status = 0), check if payroll deduction already exists and warn
    if ($status == 0) {
        $stmtCheckDeduction = $pdo->prepare("SELECT id FROM payroll_deductions WHERE emp_id = :emp_id AND month = :month_year AND (deduction = 'Loan Installment' OR deduction LIKE '%Loan%')");
        $stmtCheckDeduction->execute([':emp_id' => $loan['emp_id'], ':month_year' => $monthYear]);
        $existingDeduction = $stmtCheckDeduction->fetch(PDO::FETCH_ASSOC);
        
        if ($existingDeduction) {
            $message .= ' Note: A loan deduction already exists for this month. You may need to delete it manually from the payroll.';
        }
    }
    
    $pdo->commit();
    
    echo json_encode([
        'status' => 'success',
        'message' => $message,
        'loan_id' => $loanId,
        'month_year' => $monthYear,
        'monthly_status' => $status
    ]);
    
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    // error_log('Loan monthly status update error: ' . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => 'Update failed: ' . $e->getMessage()]);
}
?>
