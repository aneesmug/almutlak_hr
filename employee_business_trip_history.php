<?php
/**
 * Employee Business Trip History Page
 * Displays all business trip requests for the logged-in employee
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

$trip_query = "SELECT bt.id, bt.request_inv_no, bt.trip_type, bt.transportation_type, bt.destination_country, bt.travel_email_sent,
                      bt.trip_start_date, bt.trip_end_date, bt.current_status, bt.created_at, bt.emp_id,
                      fc.name_en AS from_city_name_en, fc.name_ar AS from_city_name_ar,
                      tc.name_en AS to_city_name_en, tc.name_ar AS to_city_name_ar,
                      (CASE WHEN ba.id IS NOT NULL THEN 1 ELSE 0 END) as has_allowance_record
               FROM emp_business_trip bt
               LEFT JOIN saudi_cities fc ON bt.from_city_id = fc.id
               LEFT JOIN saudi_cities tc ON bt.to_city_id = tc.id
               LEFT JOIN emp_business_trip_allowances ba ON ba.trip_id = bt.id
               WHERE bt.emp_id = ?
               ORDER BY bt.created_at DESC";
$stmt = $conDB->prepare($trip_query);
$stmt->bind_param("s", $emp_id);
$stmt->execute();
$trip_result = $stmt->get_result();

$status_badges = [
    'pending_approval' => 'badge-warning',
    'approved' => 'badge-success',
    'rejected' => 'badge-danger',
    'completed' => 'badge-primary'
];
?>

<!doctype html>
<html lang="<?= $current_lang ?? 'en' ?>" <?= ($is_rtl ?? false) ? 'dir="rtl"' : '' ?>>
<head>
    <meta charset="utf-8" />
    <title><?= $site_title ?> - <?= __('business_trip_history', 'Business Trip History') ?></title>
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
                                            <th><?= __('trip_type', 'Trip Type') ?></th>
                                            <th><?= __('transportation_type', 'Transportation') ?></th>
                                            <th><?= __('destination', 'Destination') ?></th>
                                            <th><?= __('trip_dates', 'Trip Dates') ?></th>
                                            <th><?= __('status', 'Status') ?></th>
                                            <th><?= __('applied_date', 'Applied Date') ?></th>
                                            <th><?= __('action', 'Action') ?></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while ($trip = $trip_result->fetch_assoc()): ?>
                                            <?php
                                            if (($trip['trip_type'] ?? '') === 'international') {
                                                $destination_text = $trip['destination_country'] ?? '-';
                                            } else {
                                                $from_city = ($is_rtl ?? false) ? ($trip['from_city_name_ar'] ?? '') : ($trip['from_city_name_en'] ?? '');
                                                $to_city = ($is_rtl ?? false) ? ($trip['to_city_name_ar'] ?? '') : ($trip['to_city_name_en'] ?? '');
                                                $destination_text = trim((string)$from_city) . ' -> ' . trim((string)$to_city);
                                            }

                                            $status = (string)($trip['current_status'] ?? 'pending_approval');
                                            $badge_class = $status_badges[$status] ?? 'badge-secondary';
                                            ?>
                                            <tr>
                                                <td><strong><?= htmlspecialchars((string)$trip['request_inv_no']) ?></strong></td>
                                                <td><?= htmlspecialchars((string)__($trip['trip_type'] ?? 'domestic')) ?></td>
                                                <td><?= htmlspecialchars((string)__($trip['transportation_type'] ?? '-')) ?></td>
                                                <td><?= htmlspecialchars((string)$destination_text) ?></td>
                                                <td>
                                                    <?= date('M d, Y', strtotime((string)$trip['trip_start_date'])) ?>
                                                    <?= __('to', 'to') ?>
                                                    <?= date('M d, Y', strtotime((string)$trip['trip_end_date'])) ?>
                                                </td>
                                                <td>
                                                    <span class="badge <?= $badge_class ?>">
                                                        <?= htmlspecialchars((string)__($status)) ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <?= date('M d, Y', strtotime((string)$trip['created_at'])) ?>
                                                    <br><small class="text-muted"><?= $DateConv->GregorianToHijri($trip['created_at'], $format) ?></small>
                                                </td>
                                                <td>
                                                    <div class="btn-group" style="position: relative; z-index: 1000;">
                                                        <button type="button" class="btn btn-sm btn-secondary dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                                            <?= __('actions', 'Actions') ?> <span class="caret"></span>
                                                        </button>
                                                        <div class="dropdown-menu dropdown-menu-right" style="z-index: 1050; position: absolute;">
                                                            <a class="dropdown-item" href="business_trip_report.php?id=<?= (int)$trip['id'] ?>&emp_id=<?= urlencode((string)($trip['emp_id'] ?? $emp_id)) ?>" target="_blank">
                                                                <i class="fa fa-eye"></i> <?= __('view_report', 'View Report') ?>
                                                            </a>
                                                            <a class="dropdown-item" href="business_trip_status_history.php?request_inv_no=<?= urlencode((string)$trip['request_inv_no']) ?>" target="_blank">
                                                                <i class="fa fa-history"></i> <?= __('history', 'History') ?>
                                                            </a>
                                                            <?php if (($trip['current_status'] ?? '') === 'approved' && ($trip['transportation_type'] ?? '') === 'by_air' && !empty($trip['travel_email_sent']) && empty($trip['has_allowance_record'])): ?>
                                                                <div class="dropdown-divider"></div>
                                                                <button type="button" class="dropdown-item" style="cursor: pointer; background: none; border: none; width: 100%; text-align: left;" onclick="openBusinessTripAllowanceModal(<?= (int)$trip['id'] ?>, '<?= htmlspecialchars((string)($emprow['name'] ?? ''), ENT_QUOTES) ?>')">
                                                                    <i class="fa fa-money-bill-wave text-success"></i> <?= __('add_ticket_amount', 'Add Ticket Amount') ?>
                                                                </button>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>

                            <?php if ($trip_result->num_rows == 0): ?>
                                <div class="alert alert-info">
                                    <i class="fa fa-info-circle"></i> <?= __('no_business_trip_requests_found', 'No business trip history found for this employee.') ?>
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
    <script src="assets/js/businessTrip.js?t=<?= time() ?>"></script>
    <script>
        $(document).ready(function() {
            $('.datatable').DataTable({
                order: [[6, 'desc']],
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
