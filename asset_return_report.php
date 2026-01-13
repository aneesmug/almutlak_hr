<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/session_check.php';

if (!isset($_GET['asset_id']) || !is_numeric($_GET['asset_id'])) {
    die("Invalid Asset ID.");
}
$asset_record_id = (int)$_GET['asset_id'];

$query = mysqli_query($conDB, "
    SELECT 
        ea.*,
        a.name AS asset_name,
        e.name AS employee_name,
        e.iqama AS employee_iqama,
        d.dep_nme AS department_name
    FROM `employee_assets` ea
    JOIN `assets` a ON ea.asset_id = a.id
    JOIN `employees` e ON ea.emp_id = e.emp_id
    LEFT JOIN `department` d ON e.dept = d.id
    WHERE ea.id = {$asset_record_id}
");

if (mysqli_num_rows($query) == 0) {
    die("Asset record not found.");
}
$asset = mysqli_fetch_assoc($query);

$print_return_date = null;
if (isset($_GET['print_return_date']) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['print_return_date'])) {
    $print_return_date = $_GET['print_return_date'];
}

?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <title><?= $site_title ?> - Asset Return Report</title>
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <link href="assets/css/bootstrap.min.css" rel="stylesheet" type="text/css" />
    <style>
        body { background-color: #fff; }
        .report-container { max-width: 800px; margin: 20px auto; padding: 20px; border: 1px solid #dee2e6; border-radius: 5px; }
        .report-header { text-align: center; margin-bottom: 40px; }
        .report-header img { max-height: 80px; }
        .signature-section { margin-top: 100px; }
        .signature-box { display: inline-block; width: 45%; margin: 10px; text-align: center; }
        .signature-line { border-top: 1px solid #000; margin-top: 40px; }
        .btn-container { text-align: center; padding: 20px 0; }
        @media print {
            body, .report-container { margin: 0; border: none; }
            .no-print { display: none; }
        }
    </style>
</head>
<body onload="window.print()">
    <div class="report-container">
        <div class="report-header">
            <img src="<?=get_setting($conDB, 'logo')?>" alt="Company Logo">
            <h3 class="mt-3">Asset Return Acknowledgment</h3>
            <p>إقرار استلام عهدة</p>
        </div>

        <h4>Asset Details</h4>
        <table class="table table-bordered">
            <tbody>
                <tr>
                    <th style="width: 25%;">Asset Type</th>
                    <td><?= htmlspecialchars($asset['asset_name']) ?></td>
                </tr>
                <tr>
                    <th>Serial Number / ID</th>
                    <td><?= htmlspecialchars($asset['serial_number']) ?></td>
                </tr>
                <tr>
                    <th>Description</th>
                    <td><?= htmlspecialchars($asset['description']) ?></td>
                </tr>
                 <tr>
                    <th>Assigned Date</th>
                    <td><?= date('d M, Y', strtotime($asset['assigned_date'])) ?></td>
                </tr>
                <tr>
                    <th>Return Date</th>
                    <td><?= $print_return_date ? date('d M, Y', strtotime($print_return_date)) : ($asset['return_date'] ? date('d M, Y', strtotime($asset['return_date'])) : 'N/A'); ?></td>
                </tr>
                <tr>
                    <th>Asset Condition</th>
                    <td><strong><?= htmlspecialchars($asset['asset_condition'] ?? 'Not Specified') ?></strong></td>
                </tr>
                 <tr>
                    <th>Return Status</th>
                    <td><strong><?= htmlspecialchars($asset['status']) ?></strong></td>
                </tr>
            </tbody>
        </table>

        <h4 class="mt-4">Employee Information</h4>
        <table class="table table-bordered">
            <tbody>
                <tr>
                    <th style="width: 25%;">Employee Name</th>
                    <td><?= htmlspecialchars($asset['employee_name']) ?></td>
                </tr>
                <tr>
                    <th>Employee ID / Iqama</th>
                    <td><?= htmlspecialchars($asset['employee_iqama']) ?></td>
                </tr>
                <tr>
                    <th>Department</th>
                    <td><?= htmlspecialchars($asset['department_name']) ?></td>
                </tr>
            </tbody>
        </table>

        <div class="signature-section">
            <div class="signature-box float-left">
                <p><strong>Received By (المستلم)</strong></p>
                <?php if (!empty($asset['signature_file']) && file_exists(__DIR__ . '/' . $asset['signature_file'])): ?>
                    <div style="margin: 20px 0; min-height: 60px;">
                        <img src="<?= htmlspecialchars($asset['signature_file']) ?>" alt="Signature" style="max-height: 80px; max-width: 100%; border-bottom: 1px solid #000; padding-bottom: 5px;">
                    </div>
                <?php else: ?>
                    <p class="signature-line"></p>
                <?php endif; ?>
                <p>Name & Signature</p>
            </div>
            <div class="signature-box float-right">
                <p><strong>Returned By (المُسلّم)</strong></p>
                <p class="signature-line"></p>
                <p>Name & Signature</p>
            </div>
        </div>
    </div>
    <div class="btn-container no-print">
        <button onclick="window.print()" class="btn btn-primary btn-lg">
            <i class="fa fa-print"></i> Print Report
        </button>
        <button onclick="window.close()" class="btn btn-secondary btn-lg ms-2">
            <i class="fa fa-times"></i> Close
        </button>
    </div>
</body>
</html>
