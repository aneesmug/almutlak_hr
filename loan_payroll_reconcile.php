<?php
/**
 * Loan Payroll Reconcile (standalone admin tool, opens without the sidebar).
 *
 * HR keeps an Excel sheet of the loan amounts deducted in payroll each month
 * (EMP NO. column + one column per month). Upload it, pick the month, and this
 * page compares it with that month's payroll "Loan" deductions (including rows
 * saved with an empty deduction name). Selected rows are added to the loan:
 * the payroll rows become the "Loan Deduction - <inv_no>" type (as the payroll
 * modal saves them) and one 'payroll' emp_loan_payments row is recorded for the month.
 */
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/session_check.php';
require_once __DIR__ . '/includes/helper_functions.php';
require_once __DIR__ . '/includes/payroll_loan_sync.php';

$is_ajax = $_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add';
if (empty($is_system_admin)) {
    if ($is_ajax) {
        http_response_code(403);
        die(json_encode(['ok' => false, 'message' => 'Access denied.']));
    }
    header('Location: ./dashboard.php');
    exit();
}

if (empty($_SESSION['lpr_token'])) {
    $_SESSION['lpr_token'] = bin2hex(random_bytes(16));
}
$pdo = getDbConnection();

/* ---------- helpers ---------- */

/** Excel cell value -> emp_id string ('1916', not '1916.0'). */
function lpr_emp_id($v) {
    if (is_float($v) && floor($v) == $v) {
        return (string) (int) $v;
    }
    return trim(str_replace("\xC2\xA0", '', (string) $v));
}

/** Excel cell value -> amount, null when empty / not a number. */
function lpr_amount($v) {
    if ($v === null || $v === '') {
        return null;
    }
    $v = str_replace([',', ' ', "\xC2\xA0"], '', (string) $v);
    return is_numeric($v) ? round((float) $v, 2) : null;
}

/** Header cell -> 'Y-m' when it is a month (Excel date or text like "Jul-26"), else null. */
function lpr_header_month($v) {
    if (is_numeric($v) && $v > 20000 && $v < 80000) {
        return \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject((float) $v)->format('Y-m');
    }
    $s = trim((string) $v);
    if ($s === '' || !preg_match('/[a-z]{3}/i', $s)) {
        return null;
    }
    foreach (['M-y', 'M-Y', 'M y', 'M Y', 'F-y', 'F-Y', 'F y', 'F Y', 'd-M-y', 'd-M-Y'] as $fmt) {
        $d = DateTime::createFromFormat('!' . $fmt, $s);
        if ($d) {
            return $d->format('Y-m');
        }
    }
    $t = strtotime($s);
    return $t ? date('Y-m', $t) : null;
}

/** Sheet cells as [row => [col => value]]; formula cells use their saved result (links to other files are not recalculated). */
function lpr_sheet_rows($sheet) {
    $rows = [];
    foreach ($sheet->getCellCollection()->getCoordinates() as $coord) {
        $cell = $sheet->getCell($coord);
        $v = $cell->getValue();
        if ($cell->isFormula()) {
            $v = $cell->getOldCalculatedValue();
            if ($v === null) {
                try {
                    $v = $cell->getCalculatedValue();
                } catch (Throwable $e) {
                    $v = null;
                }
            }
        }
        if (is_object($v)) {
            $v = (string) $v;
        }
        [$col, $row] = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::coordinateFromString($coord);
        $rows[(int) $row][$col] = $v;
    }
    ksort($rows);
    return $rows;
}

/**
 * Reads the HR sheet: finds the "EMP" header row and the column for $month
 * (or the forced column letter). Returns [hrMap emp_id => amount, info] or throws.
 */
function lpr_read_excel($path, $month, $forceCol) {
    require_once __DIR__ . '/vendor/autoload.php';
    $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReaderForFile($path);
    $reader->setReadDataOnly(true);
    $book = $reader->load($path);
    $tried = [];
    foreach ($book->getWorksheetIterator() as $sheet) {
        $rows = lpr_sheet_rows($sheet);
        $headerRow = null;
        $empCol = null;
        foreach (array_slice($rows, 0, 15, true) as $r => $cells) {
            foreach ($cells as $col => $v) {
                if (preg_match('/\bemp/i', (string) $v)) {
                    $headerRow = $r;
                    $empCol = $col;
                    break 2;
                }
            }
        }
        if ($headerRow === null) {
            $tried[] = $sheet->getTitle() . ': no "EMP" header';
            continue;
        }
        $amountCol = null;
        if ($forceCol !== '') {
            $amountCol = $forceCol;
        } else {
            // The month header may sit on the EMP row or the row above it (merged titles).
            foreach ([$headerRow, $headerRow - 1] as $hr) {
                foreach ($rows[$hr] ?? [] as $col => $v) {
                    if ($col !== $empCol && lpr_header_month($v) === $month) {
                        $amountCol = $col;
                        break 2;
                    }
                }
            }
        }
        if ($amountCol === null) {
            // Simple template: emp_id + paid_loan (any "paid" / "amount" / "loan" header).
            foreach ($rows[$headerRow] ?? [] as $col => $v) {
                if ($col !== $empCol && preg_match('/paid|amount|loan|deduct/i', (string) $v)) {
                    $amountCol = $col;
                    break;
                }
            }
        }
        if ($amountCol === null) {
            $tried[] = $sheet->getTitle() . ': no paid_loan column (or column for ' . date('M-Y', strtotime($month . '-01')) . ')';
            continue;
        }
        $hr = [];
        $dupes = [];
        foreach ($rows as $r => $cells) {
            if ($r <= $headerRow) {
                continue;
            }
            $emp = lpr_emp_id($cells[$empCol] ?? '');
            $amt = lpr_amount($cells[$amountCol] ?? null);
            if ($emp === '' || !preg_match('/^\d+$/', $emp) || $amt === null || $amt <= 0) {
                continue;
            }
            if (isset($hr[$emp])) {
                $dupes[] = $emp;
                $hr[$emp] += $amt;
            } else {
                $hr[$emp] = $amt;
            }
        }
        return [$hr, [
            'sheet' => $sheet->getTitle(), 'emp_col' => $empCol, 'amount_col' => $amountCol,
            'header_row' => $headerRow, 'dupes' => array_values(array_unique($dupes)),
        ]];
    }
    throw new RuntimeException('Could not find the emp_id and paid_loan columns. ' . implode(' | ', $tried)
        . '. Type the amount column letter (e.g. J) and upload again.');
}

/** Payroll "loan" rows for the month, grouped per employee. */
function lpr_payroll_rows(PDO $pdo, $month) {
    $stmt = $pdo->prepare("SELECT id, emp_id, deduction, calculation_type, CAST(note AS DECIMAL(12,2)) AS amount
        FROM payroll_deductions
        WHERE month = ? AND (deduction LIKE '%loan%' OR TRIM(COALESCE(deduction, '')) = '')
        ORDER BY id");
    $stmt->execute([$month]);
    $out = [];
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
        $name = trim((string) $r['deduction']);
        if (strpos($name, PAYROLL_LOAN_DEDUCTION_PREFIX) === 0) {
            $kind = 'linked';      // already the new Loan type (payment synced)
        } elseif ($name === '' || strtolower(trim(str_replace('"', '', $name))) === 'loan') {
            $kind = (string) ($r['calculation_type'] ?: 'fixed') === 'fixed' ? 'loan' : 'other';
        } else {
            $kind = 'other';       // e.g. scheduled "Manual Loan - ..." row, not counted in totals
        }
        $e = (string) $r['emp_id'];
        $out[$e][$kind]['ids'][] = (int) $r['id'];
        $out[$e][$kind]['names'][] = $name === '' ? '(no name)' : $name;
        $out[$e][$kind]['amount'] = round(($out[$e][$kind]['amount'] ?? 0) + (float) $r['amount'], 2);
    }
    return $out;
}

/** Payroll-method payment already recorded this month, per loan id. */
function lpr_month_payment(PDO $pdo, $loanId, $month) {
    $stmt = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) FROM emp_loan_payments
        WHERE loan_id = ? AND payment_method = 'payroll' AND DATE_FORMAT(payment_date, '%Y-%m') = ?");
    $stmt->execute([$loanId, $month]);
    return round((float) $stmt->fetchColumn(), 2);
}

/** Payroll-method payments recorded this month on any loan of the employee. */
function lpr_emp_month_payment(PDO $pdo, $empId, $month) {
    $stmt = $pdo->prepare("SELECT COALESCE(SUM(p.amount), 0) FROM emp_loan_payments p JOIN emp_loan l ON l.id = p.loan_id
        WHERE l.emp_id = ? AND p.payment_method = 'payroll' AND DATE_FORMAT(p.payment_date, '%Y-%m') = ?");
    $stmt->execute([$empId, $month]);
    return round((float) $stmt->fetchColumn(), 2);
}

/**
 * Records one payroll payment for the loan and month and flips the loan to 'paid' /
 * 'approved'. Never replaces: throws when the employee already has a payroll payment
 * on any loan for that month (the employee's loan rows are locked first, so two
 * clicks / two tabs cannot both add). payroll_loan_sync() only counts unpaid-month
 * rows (status = 1), so paid months are recorded here. Caller owns the transaction.
 */
function lpr_record_payment(PDO $pdo, $empId, $loanId, $month, $amount) {
    $pdo->prepare("SELECT id FROM emp_loan WHERE emp_id = ? FOR UPDATE")->execute([$empId]);
    $dup = $pdo->prepare("SELECT COUNT(*) FROM emp_loan_payments p JOIN emp_loan l ON l.id = p.loan_id
        WHERE l.emp_id = ? AND p.payment_method = 'payroll' AND DATE_FORMAT(p.payment_date, '%Y-%m') = ?");
    $dup->execute([$empId, $month]);
    if ((int) $dup->fetchColumn() > 0) {
        throw new DomainException('already has a loan payment for ' . $month);
    }
    $pdo->prepare("INSERT INTO emp_loan_payments (loan_id, payment_date, amount, payment_method, notes) VALUES (?, ?, ?, 'payroll', ?)")
        ->execute([$loanId, $month . '-01', number_format($amount, 2, '.', ''), PAYROLL_LOAN_PAYMENT_NOTE . ' ' . $month]);
    $stmt = $pdo->prepare("SELECT l.status, COALESCE(NULLIF(l.total_payable, 0), l.final_approved_amount, l.approved_amount, l.loan_amount, 0) AS total_payable,
            (SELECT COALESCE(SUM(amount), 0) FROM emp_loan_payments WHERE loan_id = l.id) AS paid
        FROM emp_loan l WHERE l.id = ?");
    $stmt->execute([$loanId]);
    $loan = $stmt->fetch(PDO::FETCH_ASSOC);
    $newStatus = (float) $loan['paid'] + 0.009 >= (float) $loan['total_payable'] ? 'paid' : 'approved';
    if ($newStatus !== $loan['status']) {
        $pdo->prepare("UPDATE emp_loan SET status = ? WHERE id = ?")->execute([$newStatus, $loanId]);
    }
}

/** Builds the comparison rows for the stored HR sheet. */
function lpr_compare(PDO $pdo, $month, array $hr) {
    $pay = lpr_payroll_rows($pdo, $month);
    $empIds = array_values(array_unique(array_merge(array_map('strval', array_keys($hr)), array_map('strval', array_keys($pay)))));
    sort($empIds, SORT_NUMERIC);
    if (!$empIds) {
        return [];
    }
    $in = implode(',', array_fill(0, count($empIds), '?'));
    $names = [];
    $stmt = $pdo->prepare("SELECT emp_id, name FROM employees WHERE emp_id IN ($in)");
    $stmt->execute($empIds);
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
        $names[(string) $r['emp_id']] = $r['name'];
    }
    $payroll = [];
    $stmt = $pdo->prepare("SELECT emp_id, MAX(status) AS status FROM payrolls WHERE month_year = ? AND emp_id IN ($in) GROUP BY emp_id");
    $stmt->execute(array_merge([$month], $empIds));
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
        $payroll[(string) $r['emp_id']] = (string) $r['status'];
    }
    $others = [];
    $stmt = $pdo->prepare("SELECT emp_id, deduction, CAST(note AS DECIMAL(12,2)) AS amount FROM payroll_deductions
        WHERE month = ? AND emp_id IN ($in) AND deduction NOT LIKE '%loan%' AND TRIM(COALESCE(deduction, '')) <> ''");
    $stmt->execute(array_merge([$month], $empIds));
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
        $others[(string) $r['emp_id']][] = $r['deduction'] . ' ' . number_format((float) $r['amount'], 2);
    }

    $rows = [];
    foreach ($empIds as $emp) {
        $hrAmt = $hr[$emp] ?? null;
        $p = $pay[$emp] ?? [];
        $loanRow = $p['loan'] ?? null;
        $linked = $p['linked'] ?? null;
        $other = $p['other'] ?? null;
        $payAmt = $loanRow['amount'] ?? null;
        $loans = payroll_loan_options($pdo, $emp, $month);
        foreach ($loans as &$l) {
            $l['month_paid'] = lpr_month_payment($pdo, $l['id'], $month);
        }
        unset($l);

        $row = [
            'emp_id' => $emp, 'name' => getDisplayName($names[$emp] ?? '', 'en') ?: ('#' . $emp),
            'hr' => $hrAmt, 'payroll' => $payAmt, 'ded_ids' => $loanRow['ids'] ?? [],
            'ded_names' => array_values(array_unique($loanRow['names'] ?? [])),
            'linked' => $linked['amount'] ?? null, 'other' => $other ? implode(', ', array_unique($other['names'])) . ' (' . number_format($other['amount'], 2) . ')' : '',
            'payroll_status' => $payroll[$emp] ?? '', 'loans' => $loans, 'loan_id' => null,
            'other_deductions' => implode(', ', $others[$emp] ?? []),
            'state' => '', 'reason' => '', 'can_add' => false, 'checked' => false,
        ];

        // Default loan: the one whose balance fits the amount, oldest first.
        $amt = $payAmt ?? $hrAmt ?? 0;
        $fit = array_values(array_filter($loans, fn($l) => $l['month_paid'] <= 0 && $l['available'] + 0.009 >= $amt));
        usort($fit, fn($a, $b) => $a['id'] <=> $b['id']);
        $row['loan_id'] = $fit[0]['id'] ?? ($loans[0]['id'] ?? null);
        $monthPaid = lpr_emp_month_payment($pdo, $emp, $month);

        if ($linked !== null && $payAmt === null) {
            $same = $hrAmt !== null && abs($hrAmt - $linked['amount']) < 0.01;
            $row['state'] = $same ? 'done' : 'diff';
            $row['reason'] = $same ? 'Already added (linked Loan deduction)' : 'Already linked in payroll as ' . number_format($linked['amount'], 2) . ' - HR ' . ($hrAmt === null ? 'has no amount' : 'says ' . number_format($hrAmt, 2));
        } elseif ($payAmt === null) {
            $row['state'] = $hrAmt !== null ? 'missing' : 'info';
            $row['reason'] = $hrAmt !== null ? 'HR says deducted, but payroll has no Loan deduction' : 'Only a scheduled loan row - not deducted from salary';
        } elseif ($row['payroll_status'] === '') {
            $row['state'] = 'blocked';
            $row['reason'] = 'No payroll for this month';
        } elseif ($monthPaid > 0) {
            $same = abs($monthPaid - $payAmt) < 0.01;
            $row['state'] = $same ? 'done' : 'diff';
            $row['reason'] = 'Loan already has a payroll payment of ' . number_format($monthPaid, 2) . ' this month' . ($same ? '' : ' - payroll deduction is ' . number_format($payAmt, 2));
        } elseif (!$loans) {
            $row['state'] = 'blocked';
            $row['reason'] = 'Employee has no active loan';
        } elseif (!$fit) {
            $row['state'] = 'blocked';
            $row['reason'] = 'Amount is more than the loan remaining balance';
        } elseif ($hrAmt === null) {
            $row['state'] = 'not_hr';
            $row['reason'] = 'Deducted in payroll, not in HR file';
            $row['can_add'] = true;
        } elseif (abs($hrAmt - $payAmt) >= 0.01) {
            $row['state'] = 'diff';
            $row['reason'] = 'HR amount and payroll amount are different';
        } else {
            $row['state'] = 'ready';
            $row['reason'] = 'Matches HR - ready to add';
            $row['can_add'] = true;
            $row['checked'] = true;
        }
        if ($row['state'] === 'ready' && $row['payroll_status'] !== 'paid') {
            $row['reason'] .= ' (payroll not paid yet)';
        }
        $rows[] = $row;
    }
    return $rows;
}

/* ---------- template download ---------- */

if (isset($_GET['template'])) {
    require_once __DIR__ . '/vendor/autoload.php';
    $tplMonth = preg_match('/^\d{4}-\d{2}$/', (string) ($_GET['month'] ?? '')) ? $_GET['month'] : date('Y-m', strtotime('first day of last month'));

    // Two columns only: emp_id + loan amount paid (deducted) in the month chosen on the page.
    $book = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
    $sheet = $book->getActiveSheet();
    $sheet->setTitle('Loan ' . $tplMonth);
    $sheet->fromArray(['emp_id', 'paid_loan'], null, 'A1');
    $head = $sheet->getStyle('A1:B1');
    $head->getFont()->setBold(true)->getColor()->setRGB('FFFFFF');
    $head->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('25256E');
    $sheet->getColumnDimension('A')->setWidth(14);
    $sheet->getColumnDimension('B')->setWidth(16);
    $sheet->freezePane('A2');
    $book->setActiveSheetIndex(0);

    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="loan_deductions_' . $tplMonth . '.xlsx"');
    header('Cache-Control: max-age=0');
    (new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($book))->save('php://output');
    exit();
}

/* ---------- add selected (AJAX) ---------- */

if ($is_ajax) {
    header('Content-Type: application/json; charset=UTF-8');
    $reply = function ($ok, $message, $extra = []) {
        die(json_encode(['ok' => $ok, 'message' => $message] + $extra));
    };
    if (!hash_equals($_SESSION['lpr_token'], (string) ($_POST['token'] ?? ''))) {
        $reply(false, 'Session expired. Reload the page.');
    }
    $state = $_SESSION['lpr'] ?? null;
    $month = (string) ($_POST['month'] ?? '');
    if (!$state || $state['month'] !== $month) {
        $reply(false, 'Upload the HR file again.');
    }
    $items = json_decode((string) ($_POST['items'] ?? '[]'), true) ?: [];
    if (!$items) {
        $reply(false, 'Select at least one row.');
    }
    $current = [];
    foreach (lpr_compare($pdo, $month, $state['hr']) as $r) {
        $current[$r['emp_id']] = $r;
    }
    $added = 0;
    $total = 0;
    $errors = [];
    $seen = [];
    foreach ($items as $it) {
        $emp = (string) ($it['emp_id'] ?? '');
        if (isset($seen[$emp])) {
            continue; // same employee twice in one request: add once
        }
        $seen[$emp] = true;
        $loanId = (int) ($it['loan_id'] ?? 0);
        $r = $current[$emp] ?? null;
        if (!$r || !$r['can_add']) {
            $errors[] = $emp . ': ' . ($r['reason'] ?? 'not found');
            continue;
        }
        $loan = null;
        foreach ($r['loans'] as $l) {
            if ($l['id'] === $loanId) {
                $loan = $l;
            }
        }
        if (!$loan || $loan['month_paid'] > 0 || $r['payroll'] > $loan['available'] + 0.009) {
            $errors[] = $emp . ': selected loan cannot take ' . number_format($r['payroll'], 2);
            continue;
        }
        try {
            $pdo->beginTransaction();
            lpr_record_payment($pdo, $emp, $loan['id'], $month, $r['payroll']);
            $in = implode(',', array_fill(0, count($r['ded_ids']), '?'));
            $pdo->prepare("UPDATE payroll_deductions SET deduction = ?, calculation_type = 'loan' WHERE emp_id = ? AND month = ? AND id IN ($in)")
                ->execute(array_merge([payroll_loan_deduction_name($loan['inv_no']), $emp, $month], $r['ded_ids']));
            $pdo->commit();
            $added++;
            $total += $r['payroll'];
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            if ($e instanceof DomainException) {
                $errors[] = $emp . ': ' . $e->getMessage() . ' - not added again';
            } else {
                error_log('loan_payroll_reconcile: ' . $e->getMessage());
                $errors[] = $emp . ': database error';
            }
        }
    }
    $reply($added > 0, $added . ' payment(s) added to loans, total ' . number_format($total, 2) . '.', ['errors' => $errors]);
}

/* ---------- upload ---------- */

$error = '';
$month = $_SESSION['lpr']['month'] ?? date('Y-m', strtotime('first day of last month'));
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload'])) {
    $month = preg_match('/^\d{4}-\d{2}$/', (string) ($_POST['month'] ?? '')) ? $_POST['month'] : '';
    $forceCol = strtoupper(preg_replace('/[^a-z]/i', '', (string) ($_POST['column'] ?? '')));
    $file = $_FILES['hr_file'] ?? null;
    if (!hash_equals($_SESSION['lpr_token'], (string) ($_POST['token'] ?? ''))) {
        $error = 'Session expired. Reload the page.';
    } elseif ($month === '') {
        $error = 'Select the payroll month.';
    } elseif (!$file || $file['error'] !== UPLOAD_ERR_OK) {
        $error = 'Select the HR Excel file.';
    } elseif (!in_array(strtolower(pathinfo($file['name'], PATHINFO_EXTENSION)), ['xlsx', 'xls', 'csv'], true)) {
        $error = 'Upload an .xlsx, .xls or .csv file.';
    } else {
        // Read straight from the temp upload; nothing is stored on the server.
        try {
            [$hr, $info] = lpr_read_excel($file['tmp_name'], $month, $forceCol);
            if (!$hr) {
                throw new RuntimeException('No amounts found in column ' . $info['amount_col'] . ' of sheet "' . $info['sheet'] . '".');
            }
            $_SESSION['lpr'] = ['month' => $month, 'hr' => $hr, 'info' => $info + ['file' => $file['name']]];
            header('Location: ./loan_payroll_reconcile.php');
            exit();
        } catch (Throwable $e) {
            $error = $e->getMessage();
        }
    }
}
if (isset($_GET['clear'])) {
    unset($_SESSION['lpr']);
    header('Location: ./loan_payroll_reconcile.php');
    exit();
}

$state = $_SESSION['lpr'] ?? null;
$rows = $state ? lpr_compare($pdo, $state['month'], $state['hr']) : [];
$states = [
    'ready' => ['Ready to add', 'green', 'mdi-check-circle-outline'],
    'not_hr' => ['Not in HR file', 'sky', 'mdi-file-question-outline'],
    'diff' => ['Amount different', 'amber', 'mdi-scale-unbalanced'],
    'missing' => ['Not deducted in payroll', 'red', 'mdi-alert-circle-outline'],
    'blocked' => ['Cannot add', 'red', 'mdi-cancel'],
    'done' => ['Already added', 'indigo', 'mdi-check-all'],
    'info' => ['Scheduled row only', 'slate', 'mdi-information-outline'],
];
$counts = array_count_values(array_column($rows, 'state'));
$h = fn($v) => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
$money = fn($v) => $v === null ? '<span class="sr-num is-muted">-</span>' : number_format((float) $v, 2);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Loan Payroll Reconcile</title>
    <link rel="shortcut icon" href="<?= $h(get_setting($conDB, 'favicon')) ?>">
    <link href="assets/css/bootstrap.min.css" rel="stylesheet" type="text/css" />
    <link href="assets/css/icons.css" rel="stylesheet" type="text/css" />
    <link href="assets/css/smart_request.css?v=<?= @filemtime(__DIR__ . '/assets/css/smart_request.css') ?>" rel="stylesheet" type="text/css" />
    <style>
        .lpr-upload { display: grid; grid-template-columns: 1fr 180px 140px auto; gap: 12px; align-items: end; padding: 16px 18px; }
        @media (max-width: 991px) { .lpr-upload { grid-template-columns: 1fr; } }
        .lpr-upload label { display: block; margin: 0 0 4px; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .4px; color: var(--sr-muted); }
        .lpr-upload .form-control { height: 38px; border-radius: 9px; font-size: 13px; }
        .lpr-tiles { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 10px; margin-bottom: 16px; }
        .lpr-tile { padding: 12px 14px; border-radius: 12px; border: 1px solid var(--sr-border); background: var(--sr-surface); cursor: pointer; text-align: start; }
        .lpr-tile span { display: block; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .4px; color: var(--sr-muted); }
        .lpr-tile b { font-size: 22px; color: var(--sr-text); }
        .lpr-tile.active { border-color: var(--sr-accent); box-shadow: 0 0 0 3px rgba(99, 102, 241, .16); }
        .lpr-sub { display: block; font-size: 11px; color: var(--sr-muted); }
        .lpr-loan select { height: 32px; font-size: 12px; border-radius: 8px; max-width: 230px; }
        .lpr-bar { position: sticky; bottom: 0; display: flex; flex-wrap: wrap; gap: 10px; align-items: center; justify-content: flex-end; padding: 12px 18px; border-top: 1px solid var(--sr-border); background: var(--sr-surface); }
        .lpr-bar .lpr-sum { margin-inline-end: auto; font-size: 13px; color: var(--sr-text-2); }
        .sr-table tr.is-hidden { display: none; }
    </style>
</head>
<body class="sr-standalone">
<div class="sr-page sr-standalone-wrap">
    <div class="sr-standalone-brand">
        <a href="dashboard.php"><img src="<?= $h(get_setting($conDB, 'logo')) ?>" alt=""></a>
    </div>
    <div class="sr-head">
        <div>
            <h1>Loan Payroll Reconcile</h1>
            <p>Upload the HR loan deduction sheet, compare it with the payroll of the month, and add the matching amounts to the employees' loans.</p>
        </div>
        <?php if ($state): ?>
        <div class="sr-head-actions">
            <a class="sr-btn sr-btn-ghost" href="?clear=1"><i class="mdi mdi-refresh"></i> New upload</a>
        </div>
        <?php endif; ?>
    </div>

    <?php if ($error): ?>
    <div class="sr-notice tone-red"><i class="mdi mdi-alert-circle-outline"></i><div><?= $h($error) ?></div></div>
    <?php endif; ?>

    <div class="sr-card" style="margin-bottom:16px">
        <form method="post" enctype="multipart/form-data" class="lpr-upload">
            <input type="hidden" name="token" value="<?= $h($_SESSION['lpr_token']) ?>">
            <div>
                <label>HR Excel file</label>
                <input type="file" name="hr_file" class="form-control" accept=".xlsx,.xls,.csv" required>
            </div>
            <div>
                <label>Payroll month</label>
                <input type="month" name="month" class="form-control" value="<?= $h($month) ?>" required
                       oninput="document.getElementById('lprTemplate').href = '?template=1&month=' + encodeURIComponent(this.value)">
            </div>
            <div>
                <label>Amount column (optional)</label>
                <input type="text" name="column" class="form-control" maxlength="3" placeholder="Auto, e.g. J">
            </div>
            <div style="display:flex;gap:8px;flex-wrap:wrap">
                <a href="?template=1&amp;month=<?= $h($month) ?>" id="lprTemplate" class="sr-btn sr-btn-ghost"><i class="mdi mdi-file-download-outline"></i> Download template</a>
                <button type="submit" name="upload" value="1" class="sr-btn sr-btn-primary"><i class="mdi mdi-upload"></i> Upload &amp; Compare</button>
            </div>
        </form>
        <div style="padding:0 18px 14px;font-size:12px;color:var(--sr-muted)">
            Excel needs 2 columns: <b>emp_id</b> and <b>paid_loan</b> (loan amount deducted in the selected month) - use <b>Download template</b>. HR sheets with an <b>EMP NO.</b> column and month columns (e.g. <b>Jul-26</b>) also work. Nothing is changed until you press <b>Add selected to loans</b>.
        </div>
    </div>

    <?php if ($state): $info = $state['info']; ?>
    <div class="sr-notice tone-sky">
        <i class="mdi mdi-file-excel-outline"></i>
        <div>
            <b><?= $h($info['file']) ?></b> - sheet "<?= $h($info['sheet']) ?>", employee column <?= $h($info['emp_col']) ?>, amount column <?= $h($info['amount_col']) ?>
            - <?= count($state['hr']) ?> employee(s), total <?= number_format(array_sum($state['hr']), 2) ?>
            - compared with payroll <b><?= $h(date('F Y', strtotime($state['month'] . '-01'))) ?></b>.
            <?php if (!empty($info['dupes'])): ?><br><b>Listed twice in the HR file (amounts added together):</b> <?= $h(implode(', ', $info['dupes'])) ?><?php endif; ?>
        </div>
    </div>

    <div class="lpr-tiles">
        <button type="button" class="lpr-tile active" data-filter="all"><span>All</span><b><?= count($rows) ?></b></button>
        <?php foreach ($states as $key => [$label, $tone]): if (empty($counts[$key])) continue; ?>
        <button type="button" class="lpr-tile" data-filter="<?= $key ?>"><span><?= $h($label) ?></span><b><?= (int) $counts[$key] ?></b></button>
        <?php endforeach; ?>
    </div>

    <div class="sr-card">
        <div class="sr-toolbar">
            <h2 class="sr-card-title" style="margin-inline-end:auto"><i class="mdi mdi-table"></i> Comparison <span class="sr-count"><?= count($rows) ?></span></h2>
            <div class="sr-search">
                <i class="mdi mdi-magnify"></i>
                <input id="lprSearch" type="search" placeholder="Emp ID / Name" autocomplete="off">
            </div>
        </div>
        <div class="sr-table-wrap sr-table-scroll">
            <table class="sr-table" id="lprTable">
                <thead>
                    <tr>
                        <th style="width:36px"><input type="checkbox" id="lprAll" class="sr-check" title="Select all ready rows"></th>
                        <th>Employee</th>
                        <th class="sr-num">HR amount</th>
                        <th class="sr-num">Payroll amount</th>
                        <th class="sr-num">Difference</th>
                        <th>Loan</th>
                        <th>Payroll</th>
                        <th>Result</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($rows as $r):
                    [$label, $tone, $icon] = $states[$r['state']];
                    $diff = ($r['hr'] !== null && $r['payroll'] !== null) ? round($r['payroll'] - $r['hr'], 2) : null; ?>
                    <tr data-state="<?= $r['state'] ?>" data-search="<?= $h(strtolower($r['emp_id'] . ' ' . $r['name'])) ?>" data-emp="<?= $h($r['emp_id']) ?>" data-name="<?= $h($r['name']) ?>" data-hr="<?= $r['hr'] === null ? '' : (float) $r['hr'] ?>" data-amount="<?= (float) $r['payroll'] ?>">
                        <td><?php if ($r['can_add']): ?><input type="checkbox" class="lpr-pick sr-check" <?= $r['checked'] ? 'checked' : '' ?>><?php endif; ?></td>
                        <td>
                            <span class="sr-cell-title"><?= $h($r['name']) ?></span>
                            <span class="lpr-sub">#<?= $h($r['emp_id']) ?><?= $r['ded_ids'] ? ' - deduction id ' . $h(implode(', ', $r['ded_ids'])) . ' (' . $h(implode(', ', $r['ded_names'])) . ')' : '' ?></span>
                        </td>
                        <td class="sr-num"><?= $money($r['hr']) ?></td>
                        <td class="sr-num">
                            <?= $money($r['payroll'] ?? $r['linked']) ?>
                            <?php if ($r['other']): ?><span class="lpr-sub"><?= $h($r['other']) ?></span><?php endif; ?>
                        </td>
                        <td class="sr-num <?= $diff ? 'is-bad' : 'is-muted' ?>"><?= $diff === null ? '-' : number_format($diff, 2) ?></td>
                        <td class="lpr-loan">
                            <?php if (!$r['loans']): ?>
                                <span class="lpr-sub">No active loan</span>
                            <?php elseif ($r['can_add'] && count($r['loans']) > 1): ?>
                                <select class="form-control lpr-loan-id">
                                    <?php foreach ($r['loans'] as $l): ?>
                                    <option value="<?= $l['id'] ?>" data-inv="<?= $h($l['inv_no']) ?>" data-available="<?= (float) $l['available'] ?>" <?= $l['id'] === $r['loan_id'] ? 'selected' : '' ?>><?= $h($l['inv_no']) ?> - left <?= number_format($l['available'], 2) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            <?php else: foreach ($r['loans'] as $l): ?>
                                <span class="sr-mono"><?= $h($l['inv_no']) ?></span>
                                <span class="lpr-sub">Remaining <?= number_format($l['available'], 2) ?> of <?= number_format($l['total_payable'], 2) ?><?= $l['month_paid'] > 0 ? ' - this month ' . number_format($l['month_paid'], 2) : '' ?></span>
                                <?php if ($r['can_add']): ?><input type="hidden" class="lpr-loan-id" value="<?= $l['id'] ?>" data-inv="<?= $h($l['inv_no']) ?>" data-available="<?= (float) $l['available'] ?>"><?php endif; ?>
                            <?php endforeach; endif; ?>
                        </td>
                        <td>
                            <?php if ($r['payroll_status'] === ''): ?>
                                <span class="lpr-sub">No payroll</span>
                            <?php else: ?>
                                <span class="sr-pill tone-<?= $r['payroll_status'] === 'paid' ? 'green' : 'amber' ?>"><span class="sr-dot"></span><?= $h(ucfirst($r['payroll_status'])) ?></span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="sr-pill tone-<?= $tone ?>"><i class="mdi <?= $icon ?>"></i> <?= $h($label) ?></span>
                            <span class="lpr-sub"><?= $h($r['reason']) ?></span>
                            <?php if ($r['state'] === 'missing' && $r['other_deductions']): ?><span class="lpr-sub">Other deductions: <?= $h($r['other_deductions']) ?></span><?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <?php if (!$rows): ?><div class="sr-empty"><i class="mdi mdi-inbox"></i>No HR amounts and no payroll loan deductions for this month.</div><?php endif; ?>
        </div>
        <div class="lpr-bar">
            <span class="lpr-sum" id="lprSum">0 selected</span>
            <button type="button" class="sr-btn sr-btn-success" id="lprAdd"><i class="mdi mdi-plus-circle-outline"></i> Add selected to loans</button>
        </div>
    </div>
    <?php endif; ?>
</div>

<script src="./plugins/sweet-alert/v11/sweetalert2.all.min.js"></script>
<script>
(function () {
    var table = document.getElementById('lprTable');
    if (!table) return;
    var filter = 'all';
    var rows = Array.prototype.slice.call(table.querySelectorAll('tbody tr'));

    function applyFilter() {
        var q = (document.getElementById('lprSearch').value || '').trim().toLowerCase();
        rows.forEach(function (tr) {
            var show = (filter === 'all' || tr.dataset.state === filter) && (!q || tr.dataset.search.indexOf(q) !== -1);
            tr.classList.toggle('is-hidden', !show);
        });
    }
    function picked() {
        return rows.filter(function (tr) { var c = tr.querySelector('.lpr-pick'); return c && c.checked; });
    }
    function updateSum() {
        var sel = picked(), total = 0;
        sel.forEach(function (tr) { total += parseFloat(tr.dataset.amount) || 0; });
        document.getElementById('lprSum').textContent = sel.length + ' selected - total ' + total.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        document.getElementById('lprAdd').disabled = !sel.length;
    }

    document.querySelectorAll('.lpr-tile').forEach(function (btn) {
        btn.addEventListener('click', function () {
            document.querySelectorAll('.lpr-tile').forEach(function (b) { b.classList.remove('active'); });
            btn.classList.add('active');
            filter = btn.dataset.filter;
            applyFilter();
        });
    });
    document.getElementById('lprSearch').addEventListener('input', applyFilter);
    table.addEventListener('change', function (e) { if (e.target.classList.contains('lpr-pick')) updateSum(); });
    document.getElementById('lprAll').addEventListener('change', function () {
        var on = this.checked;
        rows.forEach(function (tr) {
            var c = tr.querySelector('.lpr-pick');
            if (c && !tr.classList.contains('is-hidden')) c.checked = on;
        });
        updateSum();
    });

    function esc(v) {
        return String(v == null ? '' : v).replace(/[&<>"']/g, function (c) { return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]; });
    }
    function fmt(n) {
        return (parseFloat(n) || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }
    var MONTH_LABEL = <?= json_encode(date('F Y', strtotime(($state['month'] ?? date('Y-m')) . '-01'))) ?>;

    // Step 1: review popup - every selected employee is listed unticked; only the ones
    // ticked here are sent. Step 2: the server adds them and refuses any month already added.
    document.getElementById('lprAdd').addEventListener('click', function () {
        var sel = picked();
        if (!sel.length) return;
        var list = sel.map(function (tr) {
            var l = tr.querySelector('.lpr-loan-id');
            var opt = l && l.tagName === 'SELECT' ? l.options[l.selectedIndex] : l;
            var amount = parseFloat(tr.dataset.amount) || 0;
            var avail = opt ? parseFloat(opt.dataset.available) || 0 : 0;
            return {
                emp_id: tr.dataset.emp, name: tr.dataset.name, hr: tr.dataset.hr, amount: amount,
                loan_id: l ? l.value : 0, inv: opt ? opt.dataset.inv : '', available: avail
            };
        });
        var body = list.map(function (it, i) {
            return '<tr>'
                + '<td><input type="checkbox" class="lpr-rv" data-i="' + i + '"></td>'
                + '<td><span class="sr-cell-title">' + esc(it.name) + '</span><span class="lpr-sub">#' + esc(it.emp_id) + '</span></td>'
                + '<td class="sr-num">' + (it.hr === '' ? '<span class="lpr-sub">not in HR</span>' : fmt(it.hr)) + '</td>'
                + '<td class="sr-num"><b>' + fmt(it.amount) + '</b></td>'
                + '<td><span class="sr-mono">' + esc(it.inv) + '</span><span class="lpr-sub">' + fmt(it.available) + ' &rarr; ' + fmt(it.available - it.amount) + '</span></td>'
                + '</tr>';
        }).join('');
        Swal.fire({
            title: 'Review before adding',
            width: 900,
            html: '<div class="sr-notice tone-amber" style="text-align:start;margin-bottom:10px"><i class="mdi mdi-alert-outline"></i><div>Tick each employee to add for <b>' + esc(MONTH_LABEL) + '</b>. Only ticked rows are added. An employee who already has a loan payment for this month is never added twice.</div></div>'
                + '<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:6px"><label class="sr-check"><input type="checkbox" id="lprRvAll"> Tick all</label><span class="lpr-sub" id="lprRvSum"></span></div>'
                + '<div class="sr-table-wrap" style="max-height:52vh;overflow:auto;text-align:start"><table class="sr-table"><thead><tr><th></th><th>Employee</th><th class="sr-num">HR amount</th><th class="sr-num">Add to loan</th><th>Loan (remaining &rarr; after)</th></tr></thead><tbody>' + body + '</tbody></table></div>',
            showCancelButton: true,
            confirmButtonText: 'Add ticked',
            allowOutsideClick: false,
            customClass: { popup: 'sr-addline-popup sr-page' },
            didOpen: function (pop) {
                var boxes = Array.prototype.slice.call(pop.querySelectorAll('.lpr-rv'));
                var refresh = function () {
                    var on = boxes.filter(function (b) { return b.checked; });
                    var tot = on.reduce(function (sum, b) { return sum + list[b.dataset.i].amount; }, 0);
                    pop.querySelector('#lprRvSum').textContent = on.length + ' of ' + list.length + ' ticked - total ' + fmt(tot);
                    pop.querySelector('#lprRvAll').checked = on.length === boxes.length;
                    Swal.getConfirmButton().disabled = !on.length;
                    Swal.getConfirmButton().textContent = on.length ? 'Add ' + on.length + ' ticked' : 'Add ticked';
                };
                boxes.forEach(function (b) { b.addEventListener('change', refresh); });
                pop.querySelector('#lprRvAll').addEventListener('change', function () {
                    var v = this.checked;
                    boxes.forEach(function (b) { b.checked = v; });
                    refresh();
                });
                refresh();
            },
            preConfirm: function () {
                var on = Array.prototype.slice.call(Swal.getPopup().querySelectorAll('.lpr-rv:checked'));
                if (!on.length) {
                    Swal.showValidationMessage('Tick at least one employee.');
                    return false;
                }
                return on.map(function (b) { var it = list[b.dataset.i]; return { emp_id: it.emp_id, loan_id: it.loan_id }; });
            }
        }).then(function (res) {
            if (!res.isConfirmed) return;
            var fd = new FormData();
            fd.append('action', 'add');
            fd.append('token', <?= json_encode($_SESSION['lpr_token']) ?>);
            fd.append('month', <?= json_encode($state['month'] ?? '') ?>);
            fd.append('items', JSON.stringify(res.value));
            Swal.fire({ title: 'Adding...', allowOutsideClick: false, didOpen: function () { Swal.showLoading(); } });
            fetch('loan_payroll_reconcile.php', { method: 'POST', body: fd, credentials: 'same-origin' })
                .then(function (r) { return r.json(); })
                .then(function (d) {
                    var errs = (d.errors || []).map(function (e) { return '<li>' + esc(e) + '</li>'; }).join('');
                    Swal.fire({
                        icon: d.ok ? (errs ? 'warning' : 'success') : 'error',
                        title: d.ok ? 'Done' : 'Not added',
                        html: esc(d.message || '') + (errs ? '<ul style="text-align:start;margin-top:10px">' + errs + '</ul>' : ''),
                        allowOutsideClick: false,
                        customClass: { popup: 'sr-addline-popup sr-page' }
                    }).then(function () { location.reload(); });
                })
                .catch(function () { Swal.fire({ icon: 'error', title: 'Request failed', allowOutsideClick: false }); });
        });
    });

    updateSum();
})();
</script>
</body>
</html>
