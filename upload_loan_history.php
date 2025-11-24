<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/session_check.php';

// Restrict access
$allowed = ($is_system_admin ?? false) || ($isHR ?? false) || ($isFinance ?? false);
if (!$allowed) {
    http_response_code(403);
    die('Access denied');
}

$autoloadPath = __DIR__ . '/vendor/autoload.php';
if (!file_exists($autoloadPath)) {
    die('Vendor autoload not found. Run composer install.');
}
require $autoloadPath;

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

function safe_str($v) { return trim((string)$v); }
function safe_float($v) { return (float)$v; }

// Parse CSV file
function parse_csv_file($filepath) {
    $rows = [];
    if (($handle = fopen($filepath, 'r')) !== false) {
        while (($data = fgetcsv($handle)) !== false) {
            $rows[] = $data;
        }
        fclose($handle);
    }
    return $rows;
}

$messages = [];
$summary = ['loans_inserted'=>0,'payments_inserted'=>0,'errors'=>0];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['history_file'])) {
    $file = $_FILES['history_file'];
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $messages[] = ['type'=>'danger','text'=>'Upload error code: '.$file['error']];
    } else {
        $tmpPath = $file['tmp_name'];
        $fileName = strtolower($file['name']);
        $isCSV = (substr($fileName, -4) === '.csv');
        $isExcel = (substr($fileName, -5) === '.xlsx' || substr($fileName, -4) === '.xls');
        
        if (!$isCSV && !$isExcel) {
            $messages[] = ['type'=>'danger','text'=>'File must be CSV or Excel (.csv, .xlsx, .xls)'];
        } else {
            try {
                if ($isCSV) {
                    // Parse CSV
                    $csvRows = parse_csv_file($tmpPath);
                    if (count($csvRows) < 2) {
                        throw new Exception('CSV file must have header row and at least one data row');
                    }
                    $dataRows = array_slice($csvRows, 1); // Skip header
                } else {
                    // Parse Excel
                    $spreadsheet = IOFactory::load($tmpPath);
                    $sheet = $spreadsheet->getSheet(0);
                    $highestRow = $sheet->getHighestDataRow();
                    $dataRows = [];
                    for ($row = 2; $row <= $highestRow; $row++) {
                        $dataRows[] = [
                            $sheet->getCell("A$row")->getValue(),
                            $sheet->getCell("B$row")->getCalculatedValue(),
                            $sheet->getCell("C$row")->getCalculatedValue(),
                            $sheet->getCell("D$row")->getCalculatedValue(),
                            $sheet->getCell("E$row")->getValue()
                        ];
                    }
                }
                
                $conDB->begin_transaction();
                $loanIdMap = []; // emp_id => loan_id for payment tracking
                
                // --- Import Loans ---
                foreach ($dataRows as $rowIndex => $rowData) {
                    $emp_id = safe_str($rowData[0] ?? '');
                    if ($emp_id === '') continue; // skip empty rows
                    
                    $total_loan = safe_float($rowData[1] ?? 0);
                    $paid_loan = safe_float($rowData[2] ?? 0);
                    $remaining_loan = safe_float($rowData[3] ?? 0);
                    $loan_type = safe_str($rowData[4] ?? '');
                    
                    $rowNum = $rowIndex + 2; // +2 because 0-indexed and skip header
                    
                    // Validation
                    if ($total_loan <= 0) {
                        $summary['errors']++;
                        $messages[] = ['type'=>'warning','text'=>"Row $rowNum: total_loan must be > 0"];
                        continue;
                    }
                    if (!in_array($loan_type, ['regular','emergency','end_of_service','housing','advance_salary'])) {
                        $summary['errors']++;
                        $messages[] = ['type'=>'warning','text'=>"Row $rowNum: invalid loan_type '$loan_type'"];
                        continue;
                    }
                    
                    // Generate unique inv_no
                    $inv_no = 'LOAN-' . date('Ymd') . '-' . $emp_id . '-' . uniqid();
                    
                    // Set defaults for missing fields
                    $installments = 12;
                    $interest_rate = 0.00;
                    $total_payable = $total_loan;
                    $monthly_deduction = $remaining_loan > 0 ? round($remaining_loan / 12, 2) : 0;
                    $start_date = date('Y-m-d');
                    $end_date = null;
                    $status = 'approved';
                    $current_level = 6;
                    $final_approved_amount = $total_loan;
                    $reason = null;
                    $created_at = date('Y-m-d H:i:s');
                    
                    $stmt = $conDB->prepare("INSERT INTO emp_loan (inv_no, emp_id, loan_type, loan_amount, installments, interest_rate, total_payable, monthly_deduction, start_date, end_date, status, current_approval_level, final_approved_amount, reason, created_at) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
                    $stmt->bind_param('sssidiiddssisss', $inv_no, $emp_id, $loan_type, $total_loan, $installments, $interest_rate, $total_payable, $monthly_deduction, $start_date, $end_date, $status, $current_level, $final_approved_amount, $reason, $created_at);
                    
                    if ($stmt->execute()) {
                        $loan_id = $stmt->insert_id;
                        $loanIdMap[$emp_id] = $loan_id;
                        $summary['loans_inserted']++;
                        
                        // --- Create Payment Record if paid_loan > 0 ---
                        if ($paid_loan > 0) {
                            $payment_date = date('Y-m-d');
                            $payment_method = 'payroll';
                            $receipt_id = null;
                            $attachment = null;
                            $payment_created_at = date('Y-m-d H:i:s');
                            
                            $stmtPayment = $conDB->prepare("INSERT INTO emp_loan_payments (loan_id, payment_date, amount, payment_method, receipt_id, attachment, created_at) VALUES (?,?,?,?,?,?,?)");
                            $stmtPayment->bind_param('isdsiss', $loan_id, $payment_date, $paid_loan, $payment_method, $receipt_id, $attachment, $payment_created_at);
                            
                            if ($stmtPayment->execute()) {
                                $summary['payments_inserted']++;
                            } else {
                                $messages[] = ['type'=>'warning','text'=>"Row $rowNum: Payment record failed: {$stmtPayment->error}"];
                            }
                            $stmtPayment->close();
                        }
                    } else {
                        $summary['errors']++;
                        $messages[] = ['type'=>'warning','text'=>"Row $rowNum: {$stmt->error}"];
                    }
                    $stmt->close();
                }
                
                $conDB->commit();
                $messages[] = ['type'=>'success','text'=>'Import completed successfully.'];
            } catch (Exception $e) {
                $conDB->rollback();
                $messages[] = ['type'=>'danger','text'=>'Import failed: '.$e->getMessage()];
            }
        }
    }
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <title>Upload Loan History</title>
    <link href="assets/css/bootstrap.min.css" rel="stylesheet" />
    <style>
        body { padding:20px; }
        .summary-box { background:#f8f9fa; padding:12px; border-radius:6px; }
        .summary-box span { display:inline-block; margin-right:15px; }
    </style>
</head>
<body>
    <h3>Upload Loan History</h3>
    <p>Download the template, fill with employee loan data (employee_id | total_loan | paid_loan | remaining_loan | loan_type), then upload here (CSV or Excel).</p>
    <p><a class="btn btn-sm btn-secondary" href="download_loan_history_template.php"><i class="fa fa-download"></i> Download Template</a></p>

    <?php foreach ($messages as $m): ?>
        <div class="alert alert-<?= htmlspecialchars($m['type']) ?>"><?= htmlspecialchars($m['text']) ?></div>
    <?php endforeach; ?>

    <form method="post" enctype="multipart/form-data" class="mb-4">
        <div class="form-group">
            <label for="history_file">File (CSV or Excel)</label>
            <input type="file" name="history_file" id="history_file" class="form-control" accept=".csv,.xlsx,.xls" required>
            <small class="form-text text-muted">Supported formats: .csv, .xlsx, .xls</small>
        </div>
        <button class="btn btn-primary">Import Loans</button>
    </form>

    <div class="summary-box">
        <strong>Summary:</strong>
        <span>Loans Imported: <?= $summary['loans_inserted'] ?></span>
        <span>Payments Created: <?= $summary['payments_inserted'] ?></span>
        <span>Errors: <?= $summary['errors'] ?></span>
    </div>
    <hr>
    <h5>Column Reference</h5>
    <table class="table table-sm table-bordered">
        <thead>
            <tr>
                <th>Column</th>
                <th>Example</th>
                <th>Notes</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td><code>employee_id</code></td>
                <td>E12345</td>
                <td>Employee ID (required)</td>
            </tr>
            <tr>
                <td><code>total_loan</code></td>
                <td>5000.00</td>
                <td>Principal loan amount (required, must be > 0)</td>
            </tr>
            <tr>
                <td><code>paid_loan</code></td>
                <td>2000.00</td>
                <td>Amount already paid</td>
            </tr>
            <tr>
                <td><code>remaining_loan</code></td>
                <td>3000.00</td>
                <td>Amount still owed (calculated as monthly deduction base)</td>
            </tr>
            <tr>
                <td><code>loan_type</code></td>
                <td>regular</td>
                <td>One of: regular, emergency, end_of_service, housing, advance_salary</td>
            </tr>
        </tbody>
    </table>
    <hr>
    <h5>Import Notes</h5>
    <ul>
        <li>Supports both CSV (.csv) and Excel (.xlsx, .xls) file formats.</li>
        <li>All loans imported with status <strong>approved</strong> and approval level <strong>6</strong>.</li>
        <li>Start date set to today; end date left empty.</li>
        <li>Monthly deduction calculated as remaining_loan ÷ 12.</li>
        <li><strong>Payment records created:</strong> If paid_loan > 0, a payment record is automatically created in emp_loan_payments with today's date.</li>
        <li>If a row has errors, that row will be skipped but import continues.</li>
    </ul>
</body>
</html>
