<?php
/****************************************************************
 * MODIFICATION SUMMARY:
 * 1. ADDED LOAN PAYMENT SYNC: The script now checks if the deduction being deleted is named "Loan Installment".
 * 2. SYNCHRONIZED DELETION: If it is a loan installment, the script will also delete the corresponding payment record from the `emp_loan_payments` table for the same employee and month.
 * 3. TRANSACTIONAL SAFETY: The entire operation is wrapped in a database transaction to ensure that both the deduction and the payment are deleted successfully, or neither is.
 ****************************************************************/
header('Content-Type: application/json');
require_once("./../../includes/db.php");

$input = json_decode(file_get_contents('php://input'), true);
$deductionId = $input['deduction_id'] ?? '';
$empId = $input['emp_id'] ?? '';
$month = $input['month'] ?? '';

if (empty($deductionId) || empty($empId) || empty($month)) {
    echo json_encode(['status' => 'error', 'message' => 'Missing required parameters.']);
    exit();
}

$pdo = getDbConnection();

try {
    $pdo->beginTransaction();

    // First, get the details of the deduction to check if it's a loan payment
    $stmtFetch = $pdo->prepare("SELECT deduction FROM payroll_deductions WHERE id = :id");
    $stmtFetch->execute([':id' => $deductionId]);
    $deduction = $stmtFetch->fetch(PDO::FETCH_ASSOC);

    // Now, delete the deduction from the payroll
    $stmtDeleteDeduction = $pdo->prepare("DELETE FROM payroll_deductions WHERE id = :id AND emp_id = :emp_id AND month = :month");
    $stmtDeleteDeduction->execute([
        ':id' => $deductionId,
        ':emp_id' => $empId,
        ':month' => $month
    ]);

    // If the deduction was a "Loan Installment", also delete the corresponding payment
    if ($deduction && $deduction['deduction'] === 'Loan Installment') {
        $paymentDate = date('Y-m-t', strtotime($month . '-01')); // Get the last day of the month

        $stmtDeletePayment = $pdo->prepare("
            DELETE p FROM emp_loan_payments p
            JOIN emp_loan l ON p.loan_id = l.id
            WHERE l.emp_id = :emp_id 
            AND p.payment_date = :payment_date
        ");
        $stmtDeletePayment->execute([
            ':emp_id' => $empId,
            ':payment_date' => $paymentDate
        ]);
        
        // Also, ensure the loan status is reverted if it was marked as 'paid' this month
        $stmtUpdateLoan = $pdo->prepare("
            UPDATE emp_loan SET status = 'approved' 
            WHERE emp_id = :emp_id AND status = 'paid' 
            AND end_date >= :payment_date
        ");
        $stmtUpdateLoan->execute([
            ':emp_id' => $empId,
            ':payment_date' => $paymentDate
        ]);
    }

    $pdo->commit();
    
    echo json_encode(['status' => 'success', 'message' => 'Deduction deleted successfully.']);

} catch (PDOException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('Error deleting deduction: ' . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => 'Failed to delete deduction.']);
}
?>
