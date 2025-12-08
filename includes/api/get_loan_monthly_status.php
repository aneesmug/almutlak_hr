<?php
/**
 * Get Loan Monthly Status API
 * 
 * Retrieves the monthly skip/active status for a specific loan
 * Returns all months with their status and skip reasons
 */

header('Content-Type: application/json');
require_once("./../../includes/db.php");

$loanId = $_GET['loan_id'] ?? '';

if (empty($loanId)) {
    echo json_encode(['status' => 'error', 'message' => 'Missing loan ID.']);
    exit();
}

$pdo = getDbConnection();

try {
    // Get loan details
    $stmtLoan = $pdo->prepare("
        SELECT l.id, l.emp_id, l.inv_no, l.loan_amount, l.monthly_deduction, 
               l.start_date, l.end_date, l.deduction_mode, l.status,
               e.name AS employee_name
        FROM emp_loan l
        JOIN employees e ON l.emp_id = e.emp_id
        WHERE l.id = :loan_id
    ");
    $stmtLoan->execute([':loan_id' => $loanId]);
    $loan = $stmtLoan->fetch(PDO::FETCH_ASSOC);
    
    if (!$loan) {
        echo json_encode(['status' => 'error', 'message' => 'Loan not found.']);
        exit();
    }
    
    // Get all monthly status records
    $stmtStatus = $pdo->prepare("
        SELECT month_year, status, skip_reason, created_at, updated_at
        FROM emp_loan_monthly_status
        WHERE loan_id = :loan_id
        ORDER BY month_year ASC
    ");
    $stmtStatus->execute([':loan_id' => $loanId]);
    $monthlyStatuses = $stmtStatus->fetchAll(PDO::FETCH_ASSOC);
    
    // Get payment history
    $stmtPayments = $pdo->prepare("
        SELECT payment_date, amount, payment_method, notes
        FROM emp_loan_payments
        WHERE loan_id = :loan_id
        ORDER BY payment_date ASC
    ");
    $stmtPayments->execute([':loan_id' => $loanId]);
    $payments = $stmtPayments->fetchAll(PDO::FETCH_ASSOC);
    
    // Calculate total paid and remaining
    $totalPaid = array_sum(array_column($payments, 'amount'));
    $remainingBalance = $loan['loan_amount'] - $totalPaid;
    
    // Generate month list from start to end date
    $months = [];
    $startDate = new DateTime($loan['start_date']);
    $endDate = new DateTime($loan['end_date']);
    $currentMonth = clone $startDate;
    
    while ($currentMonth <= $endDate) {
        $monthYear = $currentMonth->format('Y-m');
        
        // Find status for this month
        $monthStatus = array_filter($monthlyStatuses, function($s) use ($monthYear) {
            return $s['month_year'] === $monthYear;
        });
        $monthStatus = reset($monthStatus); // Get first match or false
        
        // Find payment for this month
        $monthPayment = array_filter($payments, function($p) use ($monthYear) {
            return date('Y-m', strtotime($p['payment_date'])) === $monthYear;
        });
        $monthPayment = reset($monthPayment);
        
        $months[] = [
            'month_year' => $monthYear,
            'month_label' => $currentMonth->format('F Y'),
            'status' => $monthStatus ? (int)$monthStatus['status'] : 1, // Default to active
            'skip_reason' => $monthStatus ? $monthStatus['skip_reason'] : null,
            'payment_amount' => $monthPayment ? (float)$monthPayment['amount'] : null,
            'payment_notes' => $monthPayment ? $monthPayment['notes'] : null,
            'is_paid' => (bool)$monthPayment
        ];
        
        $currentMonth->modify('+1 month');
    }
    
    echo json_encode([
        'status' => 'success',
        'loan' => $loan,
        'months' => $months,
        'total_paid' => $totalPaid,
        'remaining_balance' => $remainingBalance
    ]);
    
} catch (Exception $e) {
    // error_log('Get loan monthly status error: ' . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => 'Failed to retrieve data: ' . $e->getMessage()]);
}
?>
