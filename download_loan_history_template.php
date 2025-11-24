<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/session_check.php';

// Restrict to system admin / HR / finance roles
$allowed = ($is_system_admin ?? false) || ($isHR ?? false) || ($isFinance ?? false);
if (!$allowed) {
    http_response_code(403);
    die('Access denied');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <title><?= $site_title ?> - Loan History Template</title>
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <link rel="shortcut icon" href="<?= get_setting($conDB, 'favicon') ?>">
    <link href="assets/css/bootstrap.min.css" rel="stylesheet" />
    <link href="assets/css/style.css" rel="stylesheet" />
    <style>
        body { padding:20px; }
        .template-box { background:#f8f9fa; border:1px solid #dee2e6; border-radius:6px; padding:16px; }
        .download-links button { margin-right:10px; margin-bottom:10px; }
        code { font-size: 90%; }
        .info-box { background:#e7f3ff; border-left:4px solid #007bff; padding:12px; margin-bottom:16px; }
    </style>
</head>
<body>
    <h3>Loan History Import Template</h3>
    <div class="info-box">
        <strong>Simple Format:</strong> Download template with 5 columns (employee_id, total_loan, paid_loan, remaining_loan, loan_type), fill with employee loan data, and upload to import.
    </div>
    <div class="template-box">
        <h5>Download Template</h5>
        <p class="mb-3">Click below to download the loan import template (CSV format):</p>
        <button class="btn btn-primary" onclick="downloadTemplate()">
            <i class="fa fa-download"></i> Download Loan Template
        </button>
        <hr>
        <h6>Column Reference</h6>
        <table class="table table-sm table-bordered">
            <thead>
                <tr>
                    <th>Column</th>
                    <th>Type</th>
                    <th>Example</th>
                    <th>Notes</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><code>employee_id</code></td>
                    <td>varchar</td>
                    <td>E12345</td>
                    <td>Employee ID (required)</td>
                </tr>
                <tr>
                    <td><code>total_loan</code></td>
                    <td>decimal</td>
                    <td>15000.00</td>
                    <td>Principal loan amount (required, > 0)</td>
                </tr>
                <tr>
                    <td><code>paid_loan</code></td>
                    <td>decimal</td>
                    <td>3000.50</td>
                    <td>Amount already paid</td>
                </tr>
                <tr>
                    <td><code>remaining_loan</code></td>
                    <td>decimal</td>
                    <td>11999.50</td>
                    <td>Amount still owed (used to calculate monthly deduction)</td>
                </tr>
                <tr>
                    <td><code>loan_type</code></td>
                    <td>enum</td>
                    <td>regular</td>
                    <td>One of: regular, emergency, end_of_service, housing, advance_salary</td>
                </tr>
            </tbody>
        </table>
        <hr>
        <h6>Automatic Defaults</h6>
        <p>The following fields are automatically set during import:</p>
        <ul>
            <li><code>installments</code> = 12 (default 12 months)</li>
            <li><code>interest_rate</code> = 0.00</li>
            <li><code>total_payable</code> = total_loan</li>
            <li><code>monthly_deduction</code> = remaining_loan ÷ 12</li>
            <li><code>start_date</code> = today's date</li>
            <li><code>end_date</code> = empty (null)</li>
            <li><code>status</code> = approved</li>
            <li><code>current_approval_level</code> = 6 (all approvals passed)</li>
            <li><code>final_approved_amount</code> = total_loan</li>
            <li><code>reason</code> = empty (null)</li>
        </ul>
    </div>
    
    <script>
    function escapeCSV(val){
        if(val === null || val === undefined) return '';
        val = String(val).replace(/\r|\n/g,' ');
        return /[",\n]/.test(val) ? '"' + val.replace(/"/g,'""') + '"' : val;
    }
    function makeCSV(headers, rows){
        let csv = headers.join(',') + '\n';
        rows.forEach(r => { csv += r.map(v => escapeCSV(v)).join(',') + '\n'; });
        return csv;
    }
    function triggerDownload(filename, content){
        const blob = new Blob([content], {type:'text/csv;charset=utf-8;'});
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = filename;
        a.style.display = 'none';
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        URL.revokeObjectURL(url);
    }
    function downloadTemplate(){
        const headers = ['employee_id', 'total_loan', 'paid_loan', 'remaining_loan', 'loan_type'];
        const sampleRows = [
            ['E12345', '15000.00', '3000.00', '12000.00', 'regular'],
            ['E12346', '5000.00', '0.00', '5000.00', 'advance_salary'],
            ['E12347', '25000.00', '10000.00', '15000.00', 'housing']
        ];
        const csv = makeCSV(headers, sampleRows);
        triggerDownload('loan_import_template.csv', csv);
    }
    </script>
</body>
</html>