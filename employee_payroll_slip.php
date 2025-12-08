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

// Get employee ID securely from session
$emp_id = $_SESSION['empid'] ?? ($empid ?? null);
if (empty($emp_id)) {
    header("Location: ./profile.php");
    exit();
}

// Fetch employee data
$emp_query = "SELECT 
e.*,
`department`.`dep_nme`,
`department`.`dep_nme_ar`
FROM employees e
LEFT JOIN `department` ON `department`.`id` = `e`.`dept`
WHERE emp_id = ?";
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
$month_year = sprintf('%04d-%02d', $year, $month);
$payroll_query = "SELECT * FROM payrolls 
                  WHERE emp_id = ? AND month_year = ?
                  ORDER BY month_year DESC";
$stmt = $conDB->prepare($payroll_query);
$stmt->bind_param("ss", $emp_id, $month_year);
$stmt->execute();
$payroll_result = $stmt->get_result();
$payroll_record = $payroll_result->fetch_assoc();

// Fetch all payroll records for month/year filter
$all_payrolls_query = "SELECT DISTINCT MONTH(month_year) as month, YEAR(month_year) as year 
                       FROM payrolls WHERE emp_id = ? 
                       ORDER BY month_year DESC LIMIT 12";
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
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .payroll-container {
            background: white;
            padding: 40px;
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        .payroll-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 25px;
            border-radius: 10px;
            margin-bottom: 30px;
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        .payroll-header h5 {
            margin: 0 0 10px 0;
            font-size: 24px;
            font-weight: 600;
        }
        .payroll-header p {
            margin: 0;
            opacity: 0.9;
        }
        .employee-details-card {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            color: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 25px;
        }
        .employee-details-card h6 {
            font-weight: 600;
            margin-bottom: 15px;
            font-size: 16px;
            opacity: 0.9;
        }
        .employee-details-card p {
            margin-bottom: 8px;
            font-size: 14px;
        }
        .payroll-section {
            margin-bottom: 25px;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 3px 10px rgba(0,0,0,0.08);
        }
        .payroll-section-title {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            color: white;
            padding: 12px 20px;
            font-weight: 600;
            font-size: 16px;
            letter-spacing: 0.5px;
        }
        .payroll-section-title.deductions-title {
            background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
        }
        .payroll-section-body {
            background: white;
            padding: 15px 20px;
        }
        .payroll-row {
            display: flex;
            justify-content: space-between;
            padding: 12px 15px;
            border-bottom: 1px solid #f0f0f0;
            transition: background 0.3s ease;
        }
        .payroll-row:hover {
            background: #f8f9ff;
        }
        .payroll-row:last-child {
            border-bottom: none;
        }
        .payroll-row span:first-child {
            color: #555;
            font-weight: 500;
        }
        .payroll-row span:last-child {
            color: #333;
            font-weight: 600;
        }
        .payroll-total-row {
            display: flex;
            justify-content: space-between;
            padding: 15px 20px;
            font-weight: bold;
            background: linear-gradient(135deg, #e0f7fa 0%, #e1f5fe 100%);
            margin-top: 5px;
            border-radius: 8px;
            font-size: 16px;
        }
        .payroll-total-row span:last-child {
            color: #0288d1;
        }
        .net-salary-card {
            background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
            color: white;
            padding: 25px;
            border-radius: 12px;
            text-align: center;
            box-shadow: 0 8px 25px rgba(17, 153, 142, 0.3);
            margin-top: 30px;
        }
        .net-salary-card .label {
            font-size: 14px;
            opacity: 0.9;
            margin-bottom: 10px;
            letter-spacing: 1px;
            text-transform: uppercase;
        }
        .net-salary-card .amount {
            font-size: 36px;
            font-weight: 700;
            margin: 0;
        }
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 8px;
            padding: 10px 20px;
            font-weight: 500;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
        }
        .btn-secondary {
            background: linear-gradient(135deg, #868f96 0%, #596164 100%);
            border: none;
            border-radius: 8px;
            padding: 10px 20px;
            font-weight: 500;
        }
        .btn-info {
            background: linear-gradient(135deg, #00b4db 0%, #0083b0 100%);
            border: none;
            border-radius: 8px;
            padding: 10px 20px;
            font-weight: 500;
        }
        .card {
            border-radius: 15px;
            border: none;
            box-shadow: 0 5px 25px rgba(0,0,0,0.1);
        }
    </style>
    <?php if ($is_rtl): ?>
        <link href="assets/css/style_rtl.css" rel="stylesheet" type="text/css" />
    <?php endif; ?>
    <script>
        window.lang = <?= json_encode($GLOBALS['translations'] ?? []) ?>;
    </script>
    
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
                                    <h4><?= translate_name($emprow['name'], $current_lang ?? 'en') ?> - <?= __('payroll_slip', 'Payroll Slip'); ?></h4>
                                    <p class="text-muted"><?= __('employee_id', 'Employee ID') ?>: <?= htmlspecialchars($emprow['emp_id']) ?></p>
                                </div>
                                <div class="col-sm-4 text-<?= $is_rtl ? 'left' : 'right' ?>">
                                    <a href="profile.php" class="btn btn-sm btn-secondary">
                                        <i class="fa fa-arrow-<?= $is_rtl ? 'right' : 'left' ?>"></i> <?= __('back', 'Back') ?>
                                    </a>
                                    <a href="generate_payroll_pdf.php?emp_id=<?= $emp_id ?>&month=<?= $month ?>&year=<?= $year ?>" class="btn btn-sm btn-info">
                                        <i class="fa fa-download"></i> <?= __('download_pdf', 'Download PDF') ?>
                                    </a>
                                </div>
                            </div>

                            <!-- Month/Year Filter -->
                            <div class="row mb-4">
                                <div class="col-md-6">
                                    <form method="GET" class="form-inline">
                                        <input type="hidden" name="emp_id" value="<?= $emp_id ?>">
                                        <label class="<?= $is_rtl ? 'ml-2' : 'mr-2' ?>"><?= __('select_month', 'Select Month') ?>:</label>
                                        <select name="month" class="form-control form-control-sm mr-2">
                                            <?php for ($m = 1; $m <= 12; $m++): ?>
                                                <option value="<?= str_pad($m, 2, '0', STR_PAD_LEFT) ?>" <?= ($m == $month) ? 'selected' : '' ?>>
                                                    <?= __(strtolower(date('F', mktime(0, 0, 0, $m, 1)))) ?>
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
                                        <button type="submit" class="btn btn-sm btn-primary"><?= __('view', 'View') ?></button>
                                    </form>
                                </div>
                            </div>

                            <?php if ($payroll_record): ?>
                                <div class="payroll-container">
                                    <div class="payroll-header">
                                        <h5><i class="fa fa-file-text"></i> <?= __('payroll_slip_for', 'Payroll Slip for') ?> <?= date('F Y', strtotime($payroll_record['month_year'] . '-01')) ?></h5>
                                        <p class="mb-0">
                                            <i class="fa fa-calendar"></i> <?=__('generated') ?>: <?= date('M d, Y', strtotime($payroll_record['generated_at'])) ?> 
                                            | <i class="fa fa-clock"></i> <?= __('period', 'Period') ?>: <?= date('F Y', strtotime($payroll_record['month_year'] . '-01')) ?>
                                            | <i class="fa fa-check-circle"></i> <?= __('status', 'Status') ?>: <?= ucfirst($payroll_record['status']) ?>
                                        </p>
                                    </div>

                                    <div class="row mb-4">
                                        <div class="col-md-6">
                                            <div class="employee-details-card">
                                                <h6><i class="fa fa-user"></i> <?= __('employee_information', 'Employee Information') ?></h6>
                                                <p><i class="fa fa-id-card"></i> <strong><?= __('name', 'Name') ?>:</strong> <?= translate_name($emprow['name'], $current_lang ?? 'en') ?></p>
                                                <p><i class="fa fa-hashtag"></i> <strong><?= __('employee_id', 'Employee ID') ?>:</strong> <?= htmlspecialchars($emprow['emp_id']) ?></p>
                                                <p><i class="fa fa-building"></i> <strong><?= __('department', 'Department') ?>:</strong> <?= ($is_rtl ?? false ? $emprow['dep_nme_ar'] : $emprow['dep_nme']) ?></p>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="employee-details-card" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);">
                                                <h6><i class="fa fa-money"></i> <?= __('salary_summary', 'Salary Summary') ?></h6>
                                                <p><i class="fa fa-coins"></i> <strong><?= __('basic_salary', 'Basic Salary') ?>:</strong> <?= number_format($payroll_record['basic_salary'] ?? 0, 2) ?> SAR</p>
                                                <p><i class="fa fa-chart-line"></i> <strong><?= __('gross_salary', 'Gross Salary') ?>:</strong> <?= number_format($payroll_record['total_gross_salary'] ?? 0, 2) ?> SAR</p>
                                                <p><i class="fa fa-wallet"></i> <strong><?= __('net_salary', 'Net Salary') ?>:</strong> <?= number_format($payroll_record['net_salary'] ?? 0, 2) ?> SAR</p>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- EARNINGS SECTION -->
                                    <div class="payroll-section">
                                        <div class="payroll-section-title"><i class="fa fa-arrow-up"></i> <?= strtoupper(__('earnings', 'EARNINGS')) ?></div>
                                        <div class="payroll-section-body">
                                            <?php if (isset($payroll_record['basic_salary']) && $payroll_record['basic_salary'] > 0): ?>
                                                <div class="payroll-row">
                                                    <span><i class="fa fa-circle text-primary"></i> <?= __('basic_salary', 'Basic Salary') ?></span>
                                                    <span><?= number_format($payroll_record['basic_salary'], 2) ?> SAR</span>
                                                </div>
                                            <?php endif; ?>
                                            <?php if (isset($payroll_record['housing_allowance']) && $payroll_record['housing_allowance'] > 0): ?>
                                                <div class="payroll-row">
                                                    <span><i class="fa fa-circle text-info"></i> <?= __('housing_allowance', 'Housing Allowance') ?></span>
                                                    <span><?= number_format($payroll_record['housing_allowance'], 2) ?> SAR</span>
                                                </div>
                                            <?php endif; ?>
                                            <?php if (isset($payroll_record['transport_allowance']) && $payroll_record['transport_allowance'] > 0): ?>
                                                <div class="payroll-row">
                                                    <span><i class="fa fa-circle text-success"></i> <?= __('transport_allowance', 'Transport Allowance') ?></span>
                                                    <span><?= number_format($payroll_record['transport_allowance'], 2) ?> SAR</span>
                                                </div>
                                            <?php endif; ?>
                                            <?php if (isset($payroll_record['food_allowance']) && $payroll_record['food_allowance'] > 0): ?>
                                                <div class="payroll-row">
                                                    <span><i class="fa fa-circle text-warning"></i> <?= __('food_allowance', 'Food Allowance') ?></span>
                                                    <span><?= number_format($payroll_record['food_allowance'], 2) ?> SAR</span>
                                                </div>
                                            <?php endif; ?>
                                            <?php if (isset($payroll_record['miscellaneous_allowance']) && $payroll_record['miscellaneous_allowance'] > 0): ?>
                                                <div class="payroll-row">
                                                    <span><i class="fa fa-circle text-secondary"></i> <?= __('miscellaneous_allowance', 'Miscellaneous Allowance') ?></span>
                                                    <span><?= number_format($payroll_record['miscellaneous_allowance'], 2) ?> SAR</span>
                                                </div>
                                            <?php endif; ?>
                                            <?php if (isset($payroll_record['cashier_allowance']) && $payroll_record['cashier_allowance'] > 0): ?>
                                                <div class="payroll-row">
                                                    <span><i class="fa fa-circle text-primary"></i> <?= __('cashier_allowance', 'Cashier Allowance') ?></span>
                                                    <span><?= number_format($payroll_record['cashier_allowance'], 2) ?> SAR</span>
                                                </div>
                                            <?php endif; ?>
                                            <?php if (isset($payroll_record['fuel_allowance']) && $payroll_record['fuel_allowance'] > 0): ?>
                                                <div class="payroll-row">
                                                    <span><i class="fa fa-circle text-danger"></i> <?= __('fuel_allowance', 'Fuel Allowance') ?></span>
                                                    <span><?= number_format($payroll_record['fuel_allowance'], 2) ?> SAR</span>
                                                </div>
                                            <?php endif; ?>
                                            <?php if (isset($payroll_record['telephone_allowance']) && $payroll_record['telephone_allowance'] > 0): ?>
                                                <div class="payroll-row">
                                                    <span><i class="fa fa-circle text-info"></i> <?= __('telephone_allowance', 'Telephone Allowance') ?></span>
                                                    <span><?= number_format($payroll_record['telephone_allowance'], 2) ?> SAR</span>
                                                </div>
                                            <?php endif; ?>
                                            <?php if (isset($payroll_record['other_allowance']) && $payroll_record['other_allowance'] > 0): ?>
                                                <div class="payroll-row">
                                                    <span><i class="fa fa-circle text-success"></i> <?= __('other_allowance', 'Other Allowances') ?></span>
                                                    <span><?= number_format($payroll_record['other_allowance'], 2) ?> SAR</span>
                                                </div>
                                            <?php endif; ?>
                                            <?php if (isset($payroll_record['guard_allowance']) && $payroll_record['guard_allowance'] > 0): ?>
                                                <div class="payroll-row">
                                                    <span><i class="fa fa-circle text-warning"></i> <?= __('guard_allowance', 'Guard Allowance') ?></span>
                                                    <span><?= number_format($payroll_record['guard_allowance'], 2) ?> SAR</span>
                                                </div>
                                            <?php endif; ?>
                                            <?php if (isset($payroll_record['total_benefits']) && $payroll_record['total_benefits'] > 0): ?>
                                                <div class="payroll-row">
                                                    <span><i class="fa fa-circle text-primary"></i> <?= __('total_benefits', 'Additional Benefits') ?></span>
                                                    <span><?= number_format($payroll_record['total_benefits'], 2) ?> SAR</span>
                                                </div>
                                            <?php endif; ?>
                                            <div class="payroll-total-row">
                                                <span><i class="fa fa-calculator"></i> <?= __('total_gross_salary', 'Total Gross Salary') ?></span>
                                                <span><?= number_format($payroll_record['total_gross_salary'] ?? 0, 2) ?> SAR</span>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- DEDUCTIONS SECTION -->
                                    <?php if (!empty($payroll_record['total_deductions'])): ?>
                                        <div class="payroll-section">
                                            <div class="payroll-section-title deductions-title"><i class="fa fa-arrow-down"></i> <?= strtoupper(__('deductions', 'DEDUCTIONS')) ?></div>
                                            <div class="payroll-section-body">
                                                <?php
                                                // Fetch detailed deductions from payroll_deductions table
                                                $deductions_query = "SELECT deduction, note FROM payroll_deductions WHERE emp_id = ? AND month = ?";
                                                $stmt_ded = $conDB->prepare($deductions_query);
                                                $stmt_ded->bind_param("ss", $emp_id, $payroll_record['month_year']);
                                                $stmt_ded->execute();
                                                $deductions_result = $stmt_ded->get_result();
                                                while ($ded = $deductions_result->fetch_assoc()):
                                                    if (!empty($ded['deduction']) && $ded['deduction'] > 0):
                                                ?>
                                                    <div class="payroll-row">
                                                        <span><i class="fa fa-circle text-danger"></i> <?= htmlspecialchars($ded['note'] ?: 'Deduction') ?></span>
                                                        <span><?= number_format($ded['deduction'], 2) ?> SAR</span>
                                                    </div>
                                                <?php 
                                                    endif;
                                                endwhile; 
                                                ?>
                                                <div class="payroll-total-row" style="background: linear-gradient(135deg, #ffe5e5 0%, #fff0e5 100%);">
                                                    <span><i class="fa fa-minus-circle"></i> <?= __('total_deductions', 'Total Deductions') ?></span>
                                                    <span style="color: #d32f2f;"><?= number_format($payroll_record['total_deductions'] ?? 0, 2) ?> SAR</span>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endif; ?>

                                    <!-- NET SALARY -->
                                    <div class="net-salary-card">
                                        <div class="label"><i class="fa fa-hand-holding-usd"></i> <?= __('net_salary', 'NET SALARY (Take Home)') ?></div>
                                        <div class="amount"><?= number_format($payroll_record['net_salary'] ?? 0, 2) ?> SAR</div>
                                    </div>

                                    <div class="text-muted small mt-4">
                                        <p><?= __('payroll_slip_note', 'This is a system-generated payroll slip. For official records, please contact HR.') ?></p>
                                    </div>
                                </div>
                            <?php else: ?>
                                <div class="alert alert-warning">
                                    <i class="fa fa-exclamation-triangle"></i> <?= __('no_payroll_record_found', 'No payroll record found for') ?> <?= __(strtolower(date('F', strtotime($year . '-' . ($month) . '-01')))) . ' ' . date('Y', strtotime($year . '-' . ($month) . '-01')) ?>.
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
    <script src="assets/js/employee_profile.js"></script>
</body>
</html>
