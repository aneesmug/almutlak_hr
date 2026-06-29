<?php
/**
 * Employee Loan History Page
 * Displays all loan records for a specific employee
 */

require_once("./includes/session_check.php");
include('./includes/MainClass.php');
include("./includes/Hijri_GregorianConvert.php");
$DateConv = new Hijri_GregorianConvert;
$format = "YYYY-MM-DD";

// Get employee ID from the page link first, then fall back to the session employee.
$emp_id = $_GET['emp_id'] ?? ($_SESSION['empid'] ?? ($empid ?? null));
$emp_id = is_string($emp_id) ? trim($emp_id) : $emp_id;
if (empty($emp_id)) {
    header("Location: ./profile.php");
    exit();
}

// Fetch employee data
$emp_query = "SELECT * FROM employees WHERE emp_id = ?";
$stmt = $conDB->prepare($emp_query);
$stmt->bind_param("s", $emp_id);
$stmt->execute();
$emprow = $stmt->get_result()->fetch_assoc();

if (!$emprow) {
    die("Employee not found.");
}

// Fetch loan history with repayment totals so remaining balance can be shown.
$loan_query = "SELECT l.*, 
                      COALESCE(p.total_paid, 0) AS total_paid,
                      GREATEST(COALESCE(l.total_payable, l.loan_amount, 0) - COALESCE(p.total_paid, 0), 0) AS remaining_balance
               FROM emp_loan l
               LEFT JOIN (
                   SELECT loan_id, SUM(amount) AS total_paid
                   FROM emp_loan_payments
                   GROUP BY loan_id
               ) p ON p.loan_id = l.id
               WHERE l.emp_id = ?
               ORDER BY COALESCE(l.created_at, l.start_date) DESC, l.id DESC";
$stmt = $conDB->prepare($loan_query);
$stmt->bind_param("s", $emp_id);
$stmt->execute();
$loan_result = $stmt->get_result();

$loan_history = [];
while ($loan = $loan_result->fetch_assoc()) {
    $loan_history[] = $loan;
}

$loan_payments = [];
if (!empty($loan_history)) {
    $loan_ids = array_map(static function ($loan) {
        return (int)$loan['id'];
    }, $loan_history);
    $placeholders = implode(',', array_fill(0, count($loan_ids), '?'));
    $payment_query = "SELECT loan_id, payment_date, amount, payment_method, receipt_id, attachment
                      FROM emp_loan_payments
                      WHERE loan_id IN ($placeholders)
                      ORDER BY payment_date DESC, id DESC";
    $payment_stmt = $conDB->prepare($payment_query);
    $payment_types = str_repeat('i', count($loan_ids));
    $payment_stmt->bind_param($payment_types, ...$loan_ids);
    $payment_stmt->execute();
    $payment_result = $payment_stmt->get_result();

    while ($payment = $payment_result->fetch_assoc()) {
        $loan_id = (int)$payment['loan_id'];
        if (!isset($loan_payments[$loan_id])) {
            $loan_payments[$loan_id] = [];
        }
        $loan_payments[$loan_id][] = $payment;
    }
}

$status_badges = [
    'approved' => 'badge-success',
    'pending' => 'badge-warning',
    'dept_manager_pending' => 'badge-warning',
    'hr_assistant_pending' => 'badge-warning',
    'hr_manager_pending' => 'badge-warning',
    'finance_manager_pending' => 'badge-warning',
    'finance_assistant_pending' => 'badge-warning',
    'gm_pending' => 'badge-warning',
    'rejected' => 'badge-danger',
    'paid' => 'badge-info',
    'partial' => 'badge-secondary',
    'processed' => 'badge-primary'
];

function format_loan_status_label($status) {
    if (!$status) {
        return 'Pending';
    }

    if (strpos($status, 'pending') !== false) {
        return 'Pending';
    }

    return ucwords(str_replace('_', ' ', $status));
}
?>

<!doctype html>
<html lang="<?= $current_lang ?? 'en' ?>" <?= ($is_rtl ?? false) ? 'dir="rtl"' : '' ?>>
<head>
    <meta charset="utf-8" />
    <title><?= $site_title ?> - Loan History</title>
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <link rel="shortcut icon" href="<?=get_setting($conDB, 'favicon')?>">
    <link href="./plugins/datatables/dataTables.bootstrap4.min.css" rel="stylesheet" type="text/css" />
    <link href="./plugins/datatables/buttons.bootstrap4.min.css" rel="stylesheet" type="text/css" />
    <link href="./assets/css/bootstrap.min.css" rel="stylesheet" type="text/css" />
    <link href="./assets/css/metisMenu.min.css" rel="stylesheet" type="text/css" />
    <link href="./assets/css/icons.min.css" rel="stylesheet" type="text/css" />
    <link href="./assets/css/style.css" rel="stylesheet" type="text/css" />
    <style>
        :root {
            --primary: #007bff;
            --secondary: #6c757d;
            --success: #28a745;
            --danger: #dc3545;
            --warning: #ffc107;
            --info: #17a2b8;
            --light: #f8f9fa;
            --dark: #343a40;
            --white: #ffffff;
            --shadow-sm: 0 1px 3px rgba(0, 0, 0, 0.12);
            --shadow-md: 0 2px 8px rgba(0, 0, 0, 0.15);
            --shadow-lg: 0 4px 16px rgba(0, 0, 0, 0.2);
        }

        body.authentication-bg-pattern {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
        }

        .profile-container { max-width: 1400px; margin: 0 auto; padding: 20px; }

        .profile-header {
            background: linear-gradient(135deg, var(--primary) 0%, var(--info) 100%);
            border-radius: 16px;
            padding: 28px;
            color: white;
            margin: 20px auto 24px;
            box-shadow: var(--shadow-lg);
            position: relative;
            overflow: hidden;
        }

        .profile-header::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -10%;
            width: 420px;
            height: 420px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
        }

        .profile-header .container-custom {
            display: grid;
            grid-template-columns: auto 1fr auto;
            gap: 24px;
            align-items: center;
            position: relative;
            z-index: 1;
        }

        .profile-avatar {
            width: 90px;
            height: 90px;
            border-radius: 50%;
            border: 4px solid rgba(255, 255, 255, 0.3);
            object-fit: cover;
            background: rgba(255, 255, 255, 0.1);
        }

        .profile-header-info h1 { font-size: 24px; font-weight: 700; margin-bottom: 4px; }
        .profile-header-info p { font-size: 13px; opacity: 0.95; margin: 2px 0; }

        .qr-code { width: 100px; height: 100px; }

        .card { border: none; border-radius: 12px; box-shadow: var(--shadow-md); }
        .card .card-body { padding: 24px; }
        .repayment-list { min-width: 260px; max-height: 180px; overflow-y: auto; }
        .repayment-item {
            background: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 8px;
            padding: 10px 12px;
            margin-bottom: 8px;
        }
        .repayment-item:last-child { margin-bottom: 0; }
        .repayment-item .amount { font-weight: 700; color: var(--success); }
        .repayment-item .meta { font-size: 12px; color: var(--secondary); }

        @media (max-width: 768px) {
            .profile-header { padding: 20px; }
            .profile-header .container-custom { grid-template-columns: auto 1fr; gap: 16px; }
            .qr-code { width: 80px; height: 80px; }
            .profile-avatar { width: 70px; height: 70px; }
        }
        @media (max-width: 480px) {
            .profile-header .container-custom { grid-template-columns: 1fr; text-align: center; }
            .qr-code { justify-self: center; }
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
    <?php include('./includes/profile_employee_header.php'); ?>
    <div class="account-pages" style="max-width: 1400px; margin: 20px auto; padding: 0 20px;">
        <div class="container-fluid" style="max-width: none;">
            <div class="row">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover mb-0 datatable">
                                    <thead>
                                        <tr>
                                            <th><?= __('loan_type', 'Loan Type') ?></th>
                                            <th><?= __('amount', 'Amount') ?></th>
                                            <th><?= __('monthly_deduction', 'Monthly Deduction') ?></th>
                                            <th><?= __('total_paid', 'Total Paid') ?></th>
                                            <th><?= __('remaining_balance', 'Remaining Balance') ?></th>
                                            <th><?= __('start_date', 'Start Date') ?></th>
                                            <th><?= __('status', 'Status') ?></th>
                                            <th><?= __('repayment_details', 'Repayment Details') ?></th>
                                            <th><?= __('action', 'Action') ?></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($loan_history as $loan): ?>
                                            <?php $payments = $loan_payments[(int)$loan['id']] ?? []; ?>
                                            <tr>
                                                <td>
                                                    <span class="badge badge-<?= (($loan['loan_type'] ?? 'regular') === 'emergency') ? 'warning' : 'info' ?>">
                                                        <?= htmlspecialchars(ucfirst($loan['loan_type'] ?? 'regular')) ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <strong><?= number_format((float)($loan['loan_amount'] ?? $loan['total_payable'] ?? 0), 2) ?></strong>
                                                    <i class="icon-saudi_riyal"></i>
                                                </td>
                                                <td>
                                                    <strong><?= number_format((float)($loan['monthly_deduction'] ?? 0), 2) ?></strong>
                                                    <i class="icon-saudi_riyal"></i>
                                                </td>
                                                <td>
                                                    <strong class="text-success"><?= number_format((float)($loan['total_paid'] ?? 0), 2) ?></strong>
                                                    <i class="icon-saudi_riyal"></i>
                                                </td>
                                                <td>
                                                    <strong class="<?= ((float)($loan['remaining_balance'] ?? 0) > 0) ? 'text-danger' : 'text-success' ?>">
                                                        <?= number_format((float)($loan['remaining_balance'] ?? 0), 2) ?>
                                                    </strong>
                                                    <i class="icon-saudi_riyal"></i>
                                                </td>
                                                <td>
                                                    <?php $loan_date = $loan['start_date'] ?? $loan['created_at'] ?? null; ?>
                                                    <?php if (!empty($loan_date)): ?>
                                                        <span class="date-batch-g"><?= format_safe_date($loan_date, 'M d, Y') ?></span>
                                                        <br><small class="text-muted"><?= $DateConv->GregorianToHijri(substr($loan_date, 0, 10), $format) ?></small>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <span class="badge <?= $status_badges[$loan['status']] ?? 'badge-secondary' ?>">
                                                        <?= htmlspecialchars(format_loan_status_label($loan['status'] ?? 'pending')) ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <?php if (!empty($payments)): ?>
                                                        <div class="repayment-list">
                                                            <?php foreach ($payments as $payment): ?>
                                                                <div class="repayment-item">
                                                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                                                        <span><?= format_safe_date($payment['payment_date'] ?? null, 'M d, Y') ?></span>
                                                                        <span class="amount"><?= number_format((float)$payment['amount'], 2) ?> <i class="icon-saudi_riyal"></i></span>
                                                                    </div>
                                                                    <div class="meta">
                                                                        <?= htmlspecialchars(ucfirst($payment['payment_method'] ?? 'manual')) ?>
                                                                        <?php if (!empty($payment['receipt_id'])): ?>
                                                                            | <?= __('receipt_id', 'Receipt ID') ?>: <?= htmlspecialchars($payment['receipt_id']) ?>
                                                                        <?php endif; ?>
                                                                    </div>
                                                                    <?php if (!empty($payment['attachment'])): ?>
                                                                        <div class="meta mt-1">
                                                                            <a href="<?= htmlspecialchars($payment['attachment']) ?>" target="_blank"><?= __('view_receipt', 'View Receipt') ?></a>
                                                                        </div>
                                                                    <?php endif; ?>
                                                                </div>
                                                            <?php endforeach; ?>
                                                        </div>
                                                    <?php else: ?>
                                                        <span class="text-muted small"><?= __('no_repayments_yet', 'No repayments yet') ?></span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php if (!empty($loan['inv_no'])): ?>
                                                        <a href="loan_status_history.php?inv_no=<?= urlencode($loan['inv_no']) ?>" class="btn btn-sm btn-info" title="View Details" target="_blank">
                                                            <i class="fa fa-eye"></i>
                                                        </a>
                                                    <?php else: ?>
                                                        <span class="text-muted">N/A</span>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>

                            <?php if (empty($loan_history)): ?>
                                <div class="alert alert-info">
                                    <i class="fa fa-info-circle"></i> <?= __('no_loan_records_found', 'No loan records found for this employee.') ?>
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
    <script src="./plugins/datatables/jquery.dataTables.min.js"></script>
    <script src="./plugins/datatables/dataTables.bootstrap4.min.js"></script>
    <script src="assets/js/employee_profile.js"></script>
    <script>
        $(document).ready(function() {
            $('.datatable').DataTable({
                order: [[5, 'desc']],
                pageLength: 8,
                language: {
                    search: `<span>${__('search')}:</span> _INPUT_`,
                    searchPlaceholder: `${__('search')}...`,
                    lengthMenu: `${__('show')} _MENU_ ${__('entries')}`,
                    info: `${__('showing')} _START_ ${__('to')} _END_ ${__('of')} _TOTAL_ ${__('entries')}`,
                    infoEmpty: `${__('showing')} 0 ${__('to')} 0 ${__('of')} 0 ${__('entries')}`,
                    infoFiltered: `(${__('filtered_from')} _MAX_ ${__('total_entries')})`,
                    paginate: {
                        first: __('first'),
                        last: __('last'),
                        next: __('next'),
                        previous: __('previous')
                    },
                    emptyTable: __('no_data_available_in_table'),
                    zeroRecords: __('no_matching_records_found'),
                    processing: `<div class="spinner-border text-primary" role="status"><span class="visually-hidden">${__('loading')}...</span></div>`
                }
            });
        });
    </script>
</body>
</html>
