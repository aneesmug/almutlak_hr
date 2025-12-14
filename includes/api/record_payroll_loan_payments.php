<?php
require_once __DIR__ . '/../db.php';
header('Content-Type: application/json');

$input = [];
if ($_SERVER['CONTENT_TYPE'] === 'application/json') {
    $body = file_get_contents('php://input');
    $input = json_decode($body, true) ?: [];
} else {
    $input = $_POST;
}

$month = $input['month'] ?? date('Y-m');
if (!preg_match('/^\d{4}-\d{2}$/', $month)) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid month format. Use YYYY-MM.']);
    exit;
}

try {
    $likeLoan = '%LN-%';
    $likeAdv  = 'Advance Salary Deduction - %';

    $stmt = $conDB->prepare(
        "SELECT id, emp_id, deduction, note FROM payroll_deductions 
         WHERE month = ? AND status = 1 AND (deduction LIKE ? OR deduction LIKE ?)"
    );
    $stmt->bind_param('sss', $month, $likeLoan, $likeAdv);
    $stmt->execute();
    $res = $stmt->get_result();

    $count = 0;
    $payment_date = $month . '-01';

    while ($row = $res->fetch_assoc()) {
        $emp_id = $row['emp_id'];
        $deduction = $row['deduction'];
        $amount_str = $row['note'];
        $amount = floatval($amount_str);

        // Parse inv_no
        $inv_no = null;
        if (preg_match('/\b(LN-[A-Za-z0-9\-]+)/', $deduction, $m)) {
            $inv_no = $m[1];
        } else {
            $parts = explode(' - ', $deduction);
            $candidate = trim(end($parts));
            if (strpos($candidate, 'LN-') === 0) {
                $inv_no = $candidate;
            }
        }
        if (!$inv_no) { continue; }

        // Find loan by inv_no and emp_id, fallback to any
        $stmtLoan = $conDB->prepare('SELECT id FROM emp_loan WHERE inv_no = ? AND emp_id = ? LIMIT 1');
        $stmtLoan->bind_param('ss', $inv_no, $emp_id);
        $stmtLoan->execute();
        $loanRow = $stmtLoan->get_result()->fetch_assoc();
        $stmtLoan->close();

        if (!$loanRow) {
            $stmtLoan2 = $conDB->prepare('SELECT id, emp_id FROM emp_loan WHERE inv_no = ? LIMIT 1');
            $stmtLoan2->bind_param('s', $inv_no);
            $stmtLoan2->execute();
            $loanRow = $stmtLoan2->get_result()->fetch_assoc();
            $stmtLoan2->close();
        }
        if (!$loanRow) { continue; }
        $loan_id = intval($loanRow['id']);

        // Avoid duplicates for same month
        $stmtExists = $conDB->prepare("SELECT id FROM emp_loan_payments WHERE loan_id = ? AND payment_method = 'payroll' AND DATE_FORMAT(payment_date, '%Y-%m') = ? LIMIT 1");
        $stmtExists->bind_param('is', $loan_id, $month);
        $stmtExists->execute();
        $exists = $stmtExists->get_result()->fetch_assoc();
        $stmtExists->close();
        if ($exists) { continue; }

        // Insert payment
        $stmtPay = $conDB->prepare("INSERT INTO emp_loan_payments (loan_id, payment_date, amount, receipt_id, attachment, payment_method) VALUES (?, ?, ?, NULL, NULL, 'payroll')");
        $stmtPay->bind_param('isd', $loan_id, $payment_date, $amount);
        if ($stmtPay->execute()) { $count++; }
        $stmtPay->close();
    }
    $stmt->close();

    echo json_encode(['status' => 'success', 'message' => "Recorded {$count} payroll loan payments for {$month}", 'count' => $count]);
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
