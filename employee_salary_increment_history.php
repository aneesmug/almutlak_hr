<?php
/**
 * Employee Salary Increment History Page
 * Displays all salary increment requests submitted about the logged-in employee
 */

require_once("./includes/session_check.php");
include('./includes/MainClass.php');
include("./includes/Hijri_GregorianConvert.php");
$DateConv = new Hijri_GregorianConvert;
$format = "YYYY-MM-DD";

$emp_id = $_SESSION['empid'] ?? ($empid ?? null);
if (empty($emp_id)) {
    header("Location: ./profile.php");
    exit();
}

$emp_query = "SELECT e.*, cp.period FROM employees e
              LEFT JOIN contract_period cp ON e.vac_period = cp.id
              WHERE e.emp_id = ?";
$stmt = $conDB->prepare($emp_query);
$stmt->bind_param("s", $emp_id);
$stmt->execute();
$emprow = $stmt->get_result()->fetch_assoc();

if (!$emprow) {
    die("Employee not found.");
}

$si_query = "SELECT si.id, si.request_inv_no, si.increment_amount, si.approved_amount, si.evaluation_score,
                    si.current_status, si.created_at, si.emp_id
             FROM emp_salary_increment si
             WHERE si.emp_id = ?
             ORDER BY si.created_at DESC";
$stmt = $conDB->prepare($si_query);
$stmt->bind_param("s", $emp_id);
$stmt->execute();
$si_result = $stmt->get_result();

$status_badges = [
    'pending_approval' => 'badge-warning',
    'approved' => 'badge-success',
    'rejected' => 'badge-danger',
    'cancelled' => 'badge-secondary'
];
?>

<!doctype html>
<html lang="<?= $current_lang ?? 'en' ?>" <?= ($is_rtl ?? false) ? 'dir="rtl"' : '' ?>>
<head>
    <meta charset="utf-8" />
    <title><?= $site_title ?> - <?= __('salary_increment_history', 'Salary Increment History') ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <link rel="shortcut icon" href="<?= get_setting($conDB, 'favicon') ?>">
    <link href="./plugins/datatables/dataTables.bootstrap4.min.css" rel="stylesheet" type="text/css" />
    <link href="./plugins/datatables/buttons.bootstrap4.min.css" rel="stylesheet" type="text/css" />
    <link href="./assets/css/bootstrap.min.css" rel="stylesheet" type="text/css" />
    <link href="./plugins/bootstrap-datepicker/css/bootstrap-datepicker.min.css" rel="stylesheet" type="text/css" />
    <link href="./assets/css/metisMenu.min.css" rel="stylesheet" type="text/css" />
    <link href="./assets/css/icons.min.css" rel="stylesheet" type="text/css" />
    <link href="./assets/css/style.css" rel="stylesheet" type="text/css" />
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
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

        .section-title { font-size: 20px; font-weight: 700; color: var(--dark); margin-bottom: 16px; display: flex; align-items: center; gap: 10px; }
        .section-title i { font-size: 22px; color: var(--primary); }

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
                                            <th><?= __('request_id', 'Request ID') ?></th>
                                            <th><?= __('increment_amount', 'Increment Amount') ?></th>
                                            <th><?= __('approved_amount', 'Approved Amount') ?></th>
                                            <th><?= __('evaluation_score', 'Evaluation Score') ?></th>
                                            <th><?= __('status', 'Status') ?></th>
                                            <th><?= __('applied_date', 'Applied Date') ?></th>
                                            <th><?= __('action', 'Action') ?></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while ($si = $si_result->fetch_assoc()): ?>
                                            <?php
                                            $status = (string)($si['current_status'] ?? 'pending_approval');
                                            $badge_class = $status_badges[$status] ?? 'badge-secondary';
                                            ?>
                                            <tr>
                                                <td><strong><?= htmlspecialchars((string)$si['request_inv_no']) ?></strong></td>
                                                <td><?= number_format((float)$si['increment_amount'], 2) ?></td>
                                                <td><?= $si['approved_amount'] !== null ? number_format((float)$si['approved_amount'], 2) : '-' ?></td>
                                                <td><?= $si['evaluation_score'] !== null ? number_format((float)$si['evaluation_score'], 2) : '-' ?></td>
                                                <td>
                                                    <span class="badge <?= $badge_class ?>">
                                                        <?= htmlspecialchars((string)__($status)) ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <?= format_safe_date((string)$si['created_at'], 'M d, Y') ?>
                                                    <br><small class="text-muted"><?= $DateConv->GregorianToHijri($si['created_at'], $format) ?></small>
                                                </td>
                                                <td>
                                                    <a class="btn btn-sm btn-info" href="salary_increment_status_history.php?request_inv_no=<?= urlencode((string)$si['request_inv_no']) ?>" target="_blank">
                                                        <i class="fa fa-eye"></i> <?= __('view_report', 'View Report') ?>
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>

                            <?php if ($si_result->num_rows == 0): ?>
                                <div class="alert alert-info">
                                    <i class="fa fa-info-circle"></i> <?= __('no_salary_increment_requests_found', 'No salary increment requests found') ?>
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
    <script src="./plugins/bootstrap-datepicker/js/bootstrap-datepicker.min.js"></script>
    <script src="./plugins/datatables/jquery.dataTables.min.js"></script>
    <script src="./plugins/datatables/dataTables.bootstrap4.min.js"></script>
    <script src="assets/js/employee_profile.js"></script>
    <script>
        $(document).ready(function() {
            $('.datatable').DataTable({
                order: [[5, 'desc']],
                pageLength: 10,
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
                    zeroRecords: __('no_matching_records_found')
                }
            });
        });
    </script>
</body>
</html>
