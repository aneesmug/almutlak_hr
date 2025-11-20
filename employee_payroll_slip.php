<?php
/**
 * Employee Payroll Slip Page
 * Displays detailed payroll slips with overtime, deductions, and download option
 */

require_once("./includes/session_check.php");
include('./includes/MainClass.php');
include("./includes/Hijri_GregorianConvert.php");
$DateConv = new Hijri_GregorianConvert;
$format = "YYYY-MM-DD";

// Get employee ID from URL parameter
$emp_id = isset($_GET['emp_id']) ? $_GET['emp_id'] : $user_data['empid'];

// Fetch employee data
$emp_query = "SELECT * FROM employees WHERE empid = ?";
$stmt = $conDB->prepare($emp_query);
$stmt->bind_param("s", $emp_id);
$stmt->execute();
$emprow = $stmt->get_result()->fetch_assoc();

if (!$emprow) {
    die("Employee not found.");
}

// Fetch payroll records - adjust table name based on your actual database schema
$month = isset($_GET['month']) ? $_GET['month'] : date('m');
$year = isset($_GET['year']) ? $_GET['year'] : date('Y');

// Query for payroll slip details (adjust based on your actual table structure)
$payroll_query = "SELECT * FROM payroll_records 
                  WHERE emp_id = ? AND MONTH(salary_month) = ? AND YEAR(salary_month) = ?
                  ORDER BY salary_month DESC";
$stmt = $conDB->prepare($payroll_query);
$stmt->bind_param("sii", $emp_id, $month, $year);
$stmt->execute();
$payroll_result = $stmt->get_result();
$payroll_record = $payroll_result->fetch_assoc();

// Fetch all payroll records for month/year filter
$all_payrolls_query = "SELECT DISTINCT MONTH(salary_month) as month, YEAR(salary_month) as year 
                       FROM payroll_records WHERE emp_id = ? 
                       ORDER BY salary_month DESC LIMIT 12";
$stmt = $conDB->prepare($all_payrolls_query);
$stmt->bind_param("s", $emp_id);
$stmt->execute();
$all_payrolls = $stmt->get_result();
?>

<!doctype html>
<html lang="<?= $current_lang ?? 'en' ?>" <?= ($is_rtl ?? false) ? 'dir="rtl"' : '' ?>>
<head>
    <meta charset="utf-8" />
    <title><?= $site_title ?> - Payroll Slip</title>
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <link rel="shortcut icon" href="<?=get_setting($conDB, 'favicon')?>">
    <link href="./assets/css/bootstrap.min.css" rel="stylesheet" type="text/css" />
    <link href="./assets/css/icons.min.css" rel="stylesheet" type="text/css" />
    <link href="./assets/css/style.min.css" rel="stylesheet" type="text/css" />
    <style>
        .payroll-container {
            background: white;
            padding: 30px;
            border: 1px solid #ddd;
            margin-bottom: 20px;
        }
        .payroll-header {
            border-bottom: 2px solid #333;
            margin-bottom: 20px;
            padding-bottom: 15px;
        }
        .payroll-section {
            margin-bottom: 20px;
        }
        .payroll-section-title {
            background: #f8f9fa;
            padding: 8px 12px;
            font-weight: bold;
            border-left: 3px solid #007bff;
        }
        .payroll-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px dotted #ddd;
        }
        .payroll-total-row {
            display: flex;
            justify-content: space-between;
            padding: 12px 0;
            font-weight: bold;
            background: #f8f9fa;
            margin-top: 10px;
        }
        .text-right {
            text-align: right;
        }
        .net-salary {
            font-size: 18px;
            font-weight: bold;
            color: #28a745;
        }
        @media print {
            body { margin: 0; padding: 0; }
            .btn { display: none; }
            .payroll-container { border: 1px solid #000; }
        }
    </style>
</head>
<body class="authentication-bg-pattern">
    <div class="account-pages my-5 pt-5">
        <div class="container">
            <div class="row">
                <div class="col-lg-10 mx-auto">
                    <div class="card">
                        <div class="card-body p-4">
                            <div class="row mb-4">
                                <div class="col-sm-8">
                                    <h4><?= htmlspecialchars($emprow['name']) ?> - Payroll Slip</h4>
                                    <p class="text-muted">Employee ID: <?= htmlspecialchars($emprow['empid']) ?></p>
                                </div>
                                <div class="col-sm-4 text-right">
                                    <a href="profile.php?hashcode=<?= $emprow['empid'] ?>&verification=<?= $emprow['eid'] ?>" class="btn btn-sm btn-secondary">
                                        <i class="fa fa-arrow-left"></i> Back
                                    </a>
                                    <button onclick="window.print()" class="btn btn-sm btn-info">
                                        <i class="fa fa-print"></i> Print
                                    </button>
                                </div>
                            </div>

                            <!-- Month/Year Filter -->
                            <div class="row mb-4">
                                <div class="col-md-6">
                                    <form method="GET" class="form-inline">
                                        <input type="hidden" name="emp_id" value="<?= $emp_id ?>">
                                        <label class="mr-2">Select Month:</label>
                                        <select name="month" class="form-control form-control-sm mr-2">
                                            <?php for ($m = 1; $m <= 12; $m++): ?>
                                                <option value="<?= str_pad($m, 2, '0', STR_PAD_LEFT) ?>" <?= ($m == $month) ? 'selected' : '' ?>>
                                                    <?= date('F', mktime(0, 0, 0, $m, 1)) ?>
                                                </option>
                                            <?php endfor; ?>
                                        </select>
                                        <select name="year" class="form-control form-control-sm mr-2">
                                            <?php for ($y = date('Y'); $y >= date('Y') - 3; $y--): ?>
                                                <option value="<?= $y ?>" <?= ($y == $year) ? 'selected' : '' ?>>
                                                    <?= $y ?>
                                                </option>
                                            <?php endfor; ?>
                                        </select>
                                        <button type="submit" class="btn btn-sm btn-primary">View</button>
                                    </form>
                                </div>
                            </div>

                            <?php if ($payroll_record): ?>
                                <div class="payroll-container">
                                    <div class="payroll-header">
                                        <h5>Payroll Slip for <?= date('F Y', strtotime($payroll_record['salary_month'])) ?></h5>
                                        <p class="mb-0 text-muted">
                                            Generated: <?= date('M d, Y') ?> 
                                            | Period: <?= date('d M', strtotime($payroll_record['salary_month'])) ?> - <?= date('d M Y', strtotime('+1 month', strtotime($payroll_record['salary_month']))) ?>
                                        </p>
                                    </div>

                                    <div class="row mb-4">
                                        <div class="col-md-6">
                                            <strong>Employee Details:</strong>
                                            <p class="mb-1">Name: <?= htmlspecialchars($emprow['name']) ?></p>
                                            <p class="mb-1">Employee ID: <?= htmlspecialchars($emprow['empid']) ?></p>
                                            <p class="mb-1">Position: <?= htmlspecialchars($emprow['jobname'] ?? 'N/A') ?></p>
                                        </div>
                                        <div class="col-md-6 text-right">
                                            <strong>Salary Details:</strong>
                                            <p class="mb-1">Basic Salary: <?= number_format($emprow['basic'] ?? 0, 2) ?> <i class="icon-saudi_riyal"></i></p>
                                            <p class="mb-1">Salary Type: <?= ($emprow['payment_type'] == 1 ? 'Bank Transfer' : 'Cash') ?></p>
                                        </div>
                                    </div>

                                    <!-- EARNINGS SECTION -->
                                    <div class="payroll-section">
                                        <div class="payroll-section-title">EARNINGS</div>
                                        <div class="payroll-row">
                                            <span>Basic Salary</span>
                                            <strong><?= number_format($payroll_record['basic_salary'] ?? 0, 2) ?></strong>
                                        </div>
                                        <?php if (!empty($payroll_record['housing'])): ?>
                                            <div class="payroll-row">
                                                <span>Housing Allowance</span>
                                                <span><?= number_format($payroll_record['housing'], 2) ?></span>
                                            </div>
                                        <?php endif; ?>
                                        <?php if (!empty($payroll_record['transport'])): ?>
                                            <div class="payroll-row">
                                                <span>Transport Allowance</span>
                                                <span><?= number_format($payroll_record['transport'], 2) ?></span>
                                            </div>
                                        <?php endif; ?>
                                        <?php if (!empty($payroll_record['overtime'])): ?>
                                            <div class="payroll-row">
                                                <span>Overtime (<span id="ot_hours"><?= $payroll_record['overtime_hours'] ?? 0 ?></span> hrs)</span>
                                                <span><?= number_format($payroll_record['overtime'], 2) ?></span>
                                            </div>
                                        <?php endif; ?>
                                        <?php if (!empty($payroll_record['bonus'])): ?>
                                            <div class="payroll-row">
                                                <span>Bonus/Incentive</span>
                                                <span><?= number_format($payroll_record['bonus'], 2) ?></span>
                                            </div>
                                        <?php endif; ?>
                                        <div class="payroll-total-row">
                                            <span>Total Earnings</span>
                                            <span><?= number_format(($payroll_record['basic_salary'] ?? 0) + ($payroll_record['housing'] ?? 0) + ($payroll_record['transport'] ?? 0) + ($payroll_record['overtime'] ?? 0) + ($payroll_record['bonus'] ?? 0), 2) ?></span>
                                        </div>
                                    </div>

                                    <!-- DEDUCTIONS SECTION -->
                                    <div class="payroll-section">
                                        <div class="payroll-section-title">DEDUCTIONS</div>
                                        <?php if (!empty($payroll_record['gosi'])): ?>
                                            <div class="payroll-row">
                                                <span>GOSI/Social Insurance</span>
                                                <span><?= number_format($payroll_record['gosi'], 2) ?></span>
                                            </div>
                                        <?php endif; ?>
                                        <?php if (!empty($payroll_record['income_tax'])): ?>
                                            <div class="payroll-row">
                                                <span>Income Tax</span>
                                                <span><?= number_format($payroll_record['income_tax'], 2) ?></span>
                                            </div>
                                        <?php endif; ?>
                                        <?php if (!empty($payroll_record['loan_deduction'])): ?>
                                            <div class="payroll-row">
                                                <span>Loan Deduction</span>
                                                <span><?= number_format($payroll_record['loan_deduction'], 2) ?></span>
                                            </div>
                                        <?php endif; ?>
                                        <?php if (!empty($payroll_record['absences_fine'])): ?>
                                            <div class="payroll-row">
                                                <span>Absence Fine</span>
                                                <span><?= number_format($payroll_record['absences_fine'], 2) ?></span>
                                            </div>
                                        <?php endif; ?>
                                        <?php if (!empty($payroll_record['other_deductions'])): ?>
                                            <div class="payroll-row">
                                                <span>Other Deductions</span>
                                                <span><?= number_format($payroll_record['other_deductions'], 2) ?></span>
                                            </div>
                                        <?php endif; ?>
                                        <div class="payroll-total-row">
                                            <span>Total Deductions</span>
                                            <span><?= number_format(($payroll_record['gosi'] ?? 0) + ($payroll_record['income_tax'] ?? 0) + ($payroll_record['loan_deduction'] ?? 0) + ($payroll_record['absences_fine'] ?? 0) + ($payroll_record['other_deductions'] ?? 0), 2) ?></span>
                                        </div>
                                    </div>

                                    <!-- NET SALARY -->
                                    <div class="payroll-section">
                                        <div class="payroll-total-row net-salary">
                                            <span>NET SALARY (Take Home)</span>
                                            <span><?= number_format($payroll_record['net_salary'] ?? 0, 2) ?> <i class="icon-saudi_riyal"></i></span>
                                        </div>
                                    </div>

                                    <div class="text-muted small mt-4">
                                        <p>This is a system-generated payroll slip. For official records, please contact HR.</p>
                                    </div>
                                </div>
                            <?php else: ?>
                                <div class="alert alert-warning">
                                    <i class="fa fa-exclamation-triangle"></i> No payroll record found for <?= date('F Y', strtotime($year . '-' . $month . '-01')) ?>.
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="./assets/js/jquery.min.js"></script>
    <script src="./assets/js/bootstrap.bundle.min.js"></script>
</body>
</html>
