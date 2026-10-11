<?php
/**
 * Manual "Loan" deductions added from the payroll modal (generate_payroll.php).
 *
 * A loan deduction row lives in payroll_deductions with calculation_type = 'loan' and
 * the name "Loan Deduction - <inv_no>". The amount is mirrored into emp_loan_payments as
 * one 'payroll' payment per loan per month, and emp_loan.status flips between
 * 'approved' and 'paid' as the balance clears / reopens.
 */

const PAYROLL_LOAN_DEDUCTION_PREFIX = 'Loan Deduction - ';
const PAYROLL_LOAN_PAYMENT_NOTE = 'Payroll loan deduction';

function payroll_loan_deduction_name(string $invNo): string
{
    return PAYROLL_LOAN_DEDUCTION_PREFIX . $invNo;
}

function payroll_loan_inv_from_name(string $name): ?string
{
    return strpos($name, PAYROLL_LOAN_DEDUCTION_PREFIX) === 0
        ? trim(substr($name, strlen(PAYROLL_LOAN_DEDUCTION_PREFIX)))
        : null;
}

/**
 * Loans of this employee that can take a deduction this month: approved with a
 * remaining balance, plus any loan already deducted this month (it may be 'paid'
 * only because of this month's deduction). 'available' = balance before this
 * month's payroll payment, i.e. the max the month's deduction may be.
 */
function payroll_loan_options(PDO $pdo, string $empId, string $monthYear): array
{
    $stmt = $pdo->prepare("SELECT l.id, l.inv_no, l.loan_type, l.status, l.deduction_mode,
            l.monthly_deduction,
            COALESCE(NULLIF(l.total_payable, 0), l.final_approved_amount, l.approved_amount, l.loan_amount, 0) AS total_payable,
            COALESCE(SUM(CASE WHEN p.payment_method = 'payroll' AND DATE_FORMAT(p.payment_date, '%Y-%m') = :m1 THEN 0 ELSE p.amount END), 0) AS paid_other,
            COALESCE(SUM(CASE WHEN p.payment_method = 'payroll' AND DATE_FORMAT(p.payment_date, '%Y-%m') = :m2 THEN p.amount ELSE 0 END), 0) AS paid_this_month
        FROM emp_loan l
        LEFT JOIN emp_loan_payments p ON p.loan_id = l.id
        WHERE l.emp_id = :emp_id AND l.status IN ('approved', 'paid') AND COALESCE(l.inv_no, '') <> ''
        GROUP BY l.id
        ORDER BY l.id DESC");
    $stmt->execute([':m1' => $monthYear, ':m2' => $monthYear, ':emp_id' => $empId]);

    $stmtUsed = $pdo->prepare("SELECT deduction FROM payroll_deductions
        WHERE emp_id = :emp_id AND month = :month AND calculation_type = 'loan'");
    $stmtUsed->execute([':emp_id' => $empId, ':month' => $monthYear]);
    $usedInv = [];
    foreach ($stmtUsed->fetchAll(PDO::FETCH_COLUMN) as $name) {
        $inv = payroll_loan_inv_from_name((string)$name);
        if ($inv !== null) $usedInv[$inv] = true;
    }

    $out = [];
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $available = round((float)$row['total_payable'] - (float)$row['paid_other'], 2);
        $used = isset($usedInv[$row['inv_no']]);
        if (!$used && ($row['status'] !== 'approved' || $available - (float)$row['paid_this_month'] <= 0.009)) {
            continue;
        }
        $out[] = [
            'id' => (int)$row['id'],
            'inv_no' => $row['inv_no'],
            'loan_type' => $row['loan_type'],
            'deduction_mode' => $row['deduction_mode'],
            'total_payable' => round((float)$row['total_payable'], 2),
            'monthly_deduction' => round((float)$row['monthly_deduction'], 2),
            'available' => max(0, $available),
        ];
    }
    return $out;
}

/** Loan row of this employee by id (null when not found / not deductible). */
function payroll_loan_find(PDO $pdo, string $empId, int $loanId): ?array
{
    $stmt = $pdo->prepare("SELECT id, inv_no FROM emp_loan
        WHERE id = :id AND emp_id = :emp_id AND status IN ('approved', 'paid') AND COALESCE(inv_no, '') <> '' LIMIT 1");
    $stmt->execute([':id' => $loanId, ':emp_id' => $empId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    return $row ?: null;
}

/** Inv numbers of this month's loan deductions (used to resync after deletes). */
function payroll_loan_month_invs(PDO $pdo, string $empId, string $monthYear): array
{
    $stmt = $pdo->prepare("SELECT deduction FROM payroll_deductions
        WHERE emp_id = :emp_id AND month = :month AND calculation_type = 'loan'");
    $stmt->execute([':emp_id' => $empId, ':month' => $monthYear]);
    $invs = [];
    foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $name) {
        $inv = payroll_loan_inv_from_name((string)$name);
        if ($inv !== null) $invs[$inv] = true;
    }
    return array_keys($invs);
}

/**
 * Mirror this month's loan deductions of one loan into emp_loan_payments and
 * refresh the loan status. Caller owns the transaction.
 */
function payroll_loan_sync(PDO $pdo, string $empId, string $monthYear, string $invNo): void
{
    $stmtLoan = $pdo->prepare("SELECT id, status,
            COALESCE(NULLIF(total_payable, 0), final_approved_amount, approved_amount, loan_amount, 0) AS total_payable
        FROM emp_loan WHERE inv_no = :inv AND emp_id = :emp_id LIMIT 1");
    $stmtLoan->execute([':inv' => $invNo, ':emp_id' => $empId]);
    $loan = $stmtLoan->fetch(PDO::FETCH_ASSOC);
    if (!$loan) return;
    $loanId = (int)$loan['id'];

    $stmtSum = $pdo->prepare("SELECT COALESCE(SUM(CAST(note AS DECIMAL(10,2))), 0) FROM payroll_deductions
        WHERE emp_id = :emp_id AND month = :month AND calculation_type = 'loan' AND deduction = :name AND status = 1");
    $stmtSum->execute([':emp_id' => $empId, ':month' => $monthYear, ':name' => payroll_loan_deduction_name($invNo)]);
    $amount = round((float)$stmtSum->fetchColumn(), 2);

    // One payroll payment per loan per month: replace whatever this month already has
    // when a loan deduction exists, otherwise drop only the ones this feature created.
    if ($amount > 0) {
        $pdo->prepare("DELETE FROM emp_loan_payments WHERE loan_id = :loan AND payment_method = 'payroll'
                AND DATE_FORMAT(payment_date, '%Y-%m') = :month")
            ->execute([':loan' => $loanId, ':month' => $monthYear]);
        $pdo->prepare("INSERT INTO emp_loan_payments (loan_id, payment_date, amount, payment_method, notes)
                VALUES (:loan, :date, :amount, 'payroll', :notes)")
            ->execute([
                ':loan' => $loanId,
                ':date' => $monthYear . '-01',
                ':amount' => number_format($amount, 2, '.', ''),
                ':notes' => PAYROLL_LOAN_PAYMENT_NOTE . ' ' . $monthYear,
            ]);
    } else {
        $pdo->prepare("DELETE FROM emp_loan_payments WHERE loan_id = :loan AND payment_method = 'payroll'
                AND DATE_FORMAT(payment_date, '%Y-%m') = :month AND notes LIKE :note")
            ->execute([':loan' => $loanId, ':month' => $monthYear, ':note' => PAYROLL_LOAN_PAYMENT_NOTE . '%']);
    }

    $stmtPaid = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) FROM emp_loan_payments WHERE loan_id = :loan");
    $stmtPaid->execute([':loan' => $loanId]);
    $paid = (float)$stmtPaid->fetchColumn();
    $newStatus = $paid + 0.009 >= (float)$loan['total_payable'] ? 'paid' : 'approved';
    if ($newStatus !== $loan['status']) {
        $pdo->prepare("UPDATE emp_loan SET status = :status WHERE id = :id")
            ->execute([':status' => $newStatus, ':id' => $loanId]);
    }
}
