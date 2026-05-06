<?php
/**
 * Employee Vacation History Page
 * Displays all vacation requests for a specific employee
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

// Fetch vacation history
$vacation_query = "SELECT * FROM emp_vacation WHERE emp_id = ? ORDER BY start_date DESC";
$stmt = $conDB->prepare($vacation_query);
$stmt->bind_param("s", $emp_id);
$stmt->execute();
$vacation_result = $stmt->get_result();

$all_statuses = [
    'draft' => __('draft'),
    'pending_approval' => __('pending_approval'),
    'approved' => __('approved'),
    'completed' => __('completed'),
    'rejected' => __('rejected'),
    'cancelled' => __('cancelled')
];

$status_badges = [
    'draft' => 'badge-secondary',
    'pending_approval' => 'badge-warning',
    'approved' => 'badge-success',
    'completed' => 'badge-info',
    'rejected' => 'badge-danger',
    'cancelled' => 'badge-dark'
];
?>

<!doctype html>
<html lang="<?= $current_lang ?? 'en' ?>" <?= ($is_rtl ?? false) ? 'dir="rtl"' : '' ?>>
<head>
    <meta charset="utf-8" />
    <title><?= $site_title ?> - Vacation History</title>
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <link rel="shortcut icon" href="<?=get_setting($conDB, 'favicon')?>">
    <link href="./plugins/datatables/dataTables.bootstrap4.min.css" rel="stylesheet" type="text/css" />
    <link href="./plugins/datatables/buttons.bootstrap4.min.css" rel="stylesheet" type="text/css" />
    <!-- <link href="./plugins/sweet-alert/sweetalert2.min.css" rel="stylesheet" type="text/css" /> -->
    <link href="./assets/css/bootstrap.min.css" rel="stylesheet" type="text/css" />
    <link href="./assets/css/metisMenu.min.css" rel="stylesheet" type="text/css" />
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
                                            <th><?= __('from_date', 'From Date') ?></th>
                                            <th><?= __('to_date', 'To Date') ?></th>
                                            <th><?= __('days', 'Days') ?></th>
                                            <th><?= __('type', 'Type') ?></th>
                                            <th><?= __('fly_type', 'Fly Type') ?></th>
                                            <th><?= __('status', 'Status') ?></th>
                                            <th><?= __('applied_date', 'Applied Date') ?></th>
                                            <th><?= __('action', 'Action') ?></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php 
                                            while ($vac = $vacation_result->fetch_assoc()): 
                                                // Do not show legacy invoices in loan history
                                                if (isset($vac['request_inv_no']) && strpos($vac['request_inv_no'], 'LEGACY-') === 0) {
                                                    continue;
                                                }
                                        ?>
                                            <tr>
                                                <td>
                                                    <?= format_safe_date($vac['start_date'] ?? null, 'M d, Y') ?>
                                                    <br><small class="text-muted"><?= $DateConv->GregorianToHijri($vac['start_date'], $format) ?></small>
                                                </td>
                                                <td>
                                                    <?= format_safe_date($vac['return_date'] ?? null, 'M d, Y') ?>
                                                    <br><small class="text-muted"><?= $DateConv->GregorianToHijri($vac['return_date'], $format) ?></small>
                                                </td>
                                                <td><strong><?= number_format($vac['vacdays'], 2) ?></strong> days</td>
                                                <td><?= htmlspecialchars($vac['vac_type']) ?></td>
                                                <td>
                                                    <?php if (!empty($vac['fly_type'])): ?>
                                                        <span class="badge badge-info"><?= htmlspecialchars($vac['fly_type']) ?></span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <span class="badge <?= $status_badges[$vac['current_status']] ?? 'badge-secondary' ?>">
                                                        <?= isset($all_statuses[$vac['current_status']]) ? (is_callable($all_statuses[$vac['current_status']]) ? $all_statuses[$vac['current_status']]() : $all_statuses[$vac['current_status']]) : ucfirst(str_replace('_', ' ', $vac['current_status'])) ?>
                                                    </span>
                                                </td>
                                                <td><?= format_safe_date($vac['created_at'] ?? null, 'M d, Y') ?></td>
                                                <td>
                                                    <div class="btn-group btn-group-sm" role="group">
                                                        <a href="vacation_report_details.php?id=<?= $vac['id'] ?>&emp_id=<?= $vac['emp_id'] ?>" class="btn btn-info" title="View Details">
                                                            <i class="fa fa-eye"></i>
                                                        </a>
                                                        <?php if ($vac['current_status'] !== 'completed' && $vac['current_status'] !== 'cancelled' && $vac['current_status'] !== 'rejected'): ?>
                                                            <button type="button" class="btn btn-danger" onclick="cancelVacationRequest(<?= $vac['id'] ?>, '<?= htmlspecialchars(addslashes($vac['vac_type']), ENT_QUOTES) ?>', '<?= htmlspecialchars(addslashes(format_safe_date($vac['start_date'] ?? null, 'M d, Y')), ENT_QUOTES) ?>'" title="Cancel Request">
                                                                <i class="fa fa-times"></i>
                                                            </button>
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>

                            <?php if ($vacation_result->num_rows == 0): ?>
                                <div class="alert alert-info">
                                    <i class="fa fa-info-circle"></i> <?= __('no_vacation_history_found', 'No vacation history found for this employee.') ?>
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
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="assets/js/employee_profile.js"></script>
    <script>
        // Cancel vacation request function
        function cancelVacationRequest(vacationId, vacType, startDate) {
            // Show SweetAlert2 confirmation dialog
            Swal.fire({
                title: __('cancel_vacation_request'),
                // html: `<p>Are you sure you want to cancel this <strong>${vacType}</strong> vacation request starting on <strong>${startDate}</strong>?</p><p style="color: #dc3545; font-size: 12px; margin-top: 10px;">This action cannot be undone.</p>`,
                html: `<p>${__('are_you_sure_cancel_vacation', { vacType: vacType, startDate: startDate })}</p><p style="color: #dc3545; font-size: 12px; margin-top: 10px;">${__('this_action_cannot_be_undone')}</p>`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                cancelButtonColor: '#6c757d',
                confirmButtonText: __('yes_cancel_request'),
                cancelButtonText: __('keep_request'),
                allowOutsideClick: false,
                allowEscapeKey: false
            }).then((result) => {
                if (result.isConfirmed) {
                    // Show loading state
                    Swal.fire({
                        title: __('cancelling_request'),
                        html: __('please_wait_while_cancelling'),
                        icon: 'info',
                        allowOutsideClick: false,
                        allowEscapeKey: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });

                    // Send AJAX request to cancel the vacation
                    $.ajax({
                        url: './includes/ajaxFile/leaveHandler.php',
                        type: 'POST',
                        dataType: 'JSON',
                        data: {
                            ajaxType: 'cancelVacationRequest',
                            vacation_id: vacationId
                        },
                        success: function(response) {
                            Swal.fire({
                                title: response.title || __('success'),
                                text: response.message || __('your_vacation_request_has_been_cancelled_successfully'),
                                icon: response.type || 'success',
                                confirmButtonText: __('ok'),
                                allowOutsideClick: false
                            }).then(() => {
                                location.reload();
                            });
                        },
                        error: function(xhr) {
                            const response = xhr.responseJSON || {};
                            Swal.fire({
                                title: response.title || __('error'),
                                text: response.message || __('failed_to_cancel_vacation_request'),
                                icon: 'error',
                                confirmButtonText: __('ok')
                            });
                        }
                    });
                }
            });
        }

        $(document).ready(function() {
            $('.datatable').DataTable({
                order: [[6, 'desc']],
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
