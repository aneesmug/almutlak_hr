<?php
/**
 * Employee Assigned Assets Page
 * Displays all assets assigned to a specific employee
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
$emp_query = "SELECT * FROM employees WHERE emp_id = ?";
$stmt = $conDB->prepare($emp_query);
$stmt->bind_param("s", $emp_id);
$stmt->execute();
$emprow = $stmt->get_result()->fetch_assoc();

if (!$emprow) {
    die("Employee not found.");
}

// Fetch assigned assets from employee_assets table (laptops, mobiles, SIMs, etc.)
$employee_assets_query = "SELECT 
                            ea.id,
                            ea.asset_id,
                            ea.serial_number,
                            ea.description,
                            ea.assigned_date,
                            ea.return_date,
                            ea.status,
                            a.name as asset_name,
                            a.asset_type
                          FROM employee_assets ea
                          LEFT JOIN assets a ON ea.asset_id = a.id
                          WHERE ea.emp_id = ?
                          ORDER BY ea.assigned_date DESC";

$stmt = $conDB->prepare($employee_assets_query);
$stmt->bind_param("s", $emp_id);
$stmt->execute();
$employee_assets_result = $stmt->get_result();

// Fetch cars assigned through cars_drv table
$cars_drv_query = "SELECT 
                     cd.id,
                     cd.car_id,
                     cd.rcv_date as assigned_date,
                     cd.rtn_date as return_date,
                     cd.status,
                     cd.created_at,
                     c.maker_name as maker_id,
                     c.model as model_id,
                     c.made_year,
                     c.plate_no,
                     c.type,
                     cm.maker as maker_name,
                     cmd.model as model_name
                   FROM cars_drv cd
                   JOIN cars c ON cd.car_id = c.id
                   LEFT JOIN car_maker cm ON c.maker_name = cm.id
                   LEFT JOIN car_model cmd ON c.model = cmd.id
                   WHERE cd.car_user = ?
                   ORDER BY cd.rcv_date DESC";

$stmt = $conDB->prepare($cars_drv_query);
$stmt->bind_param("s", $emp_id);
$stmt->execute();
$cars_drv_result = $stmt->get_result();
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

        .btn-light:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.2) !important;
        }

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
                        <div class="card-body p-4">
                            <div class="table-responsive">
                                <table class="table table-hover mb-0 datatable">
                                    <thead>
                                        <tr>
                                            <th>#</th>
                                            <th><?= __('asset_type') ?></th>
                                            <th><?= __('name_model') ?></th>
                                            <th><?= __('details') ?></th>
                                            <th><?= __('serial_number') ?></th>
                                            <th><?= __('assigned_date') ?></th>
                                            <th><?= __('return_date') ?></th>
                                            <th><?= __('status') ?></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php 
                                        $count = 0;
                                        
                                        // Display employee assets (laptops, mobiles, SIMs, etc.)
                                        while ($asset = $employee_assets_result->fetch_assoc()): 
                                            $count++;
                                        ?>
                                            <tr>
                                                <td><?= $count ?></td>
                                                <td>
                                                    <span class="badge badge-primary">
                                                        <?= htmlspecialchars($asset['asset_type'] ?? $asset['asset_name'] ?? 'Asset') ?>
                                                    </span>
                                                </td>
                                                <td><?= htmlspecialchars($asset['asset_name'] ?? 'N/A') ?></td>
                                                <td><?= htmlspecialchars($asset['description'] ?? 'N/A') ?></td>
                                                <td><?= htmlspecialchars($asset['serial_number'] ?? 'N/A') ?></td>
                                                <td>
                                                    <?php if (isset($asset['assigned_date']) && $asset['assigned_date']): ?>
                                                        <?= format_safe_date($asset['assigned_date'], 'M d, Y') ?>
                                                        <br><small class="text-muted"><?= $DateConv->GregorianToHijri($asset['assigned_date'], $format) ?></small>
                                                    <?php else: echo 'N/A'; endif; ?>
                                                </td>
                                                <td>
                                                    <?php if (isset($asset['return_date']) && $asset['return_date']): ?>
                                                        <?= format_safe_date($asset['return_date'], 'M d, Y') ?>
                                                        <br><small class="text-muted"><?= $DateConv->GregorianToHijri($asset['return_date'], $format) ?></small>
                                                    <?php else: echo '-'; endif; ?>
                                                </td>
                                                <td>
                                                    <?php
                                                    $status_class = 'success';
                                                    if ($asset['status'] == 'Returned') $status_class = 'secondary';
                                                    elseif ($asset['status'] == 'Lost') $status_class = 'danger';
                                                    elseif ($asset['status'] == 'Damaged') $status_class = 'warning';
                                                    ?>
                                                    <span class="badge badge-<?= $status_class ?>"><?= htmlspecialchars($asset['status']) ?></span>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                        
                                        <?php 
                                        // Display car assignments from cars_drv table
                                        while ($car_drv = $cars_drv_result->fetch_assoc()): 
                                            $count++;
                                            // Determine if car is still assigned or returned
                                            $is_active = ($car_drv['status'] == 1);
                                            $has_return_date = !empty($car_drv['return_date']);
                                        ?>
                                            <tr>
                                                <td><?= $count ?></td>
                                                <td>
                                                    <span class="badge badge-info">
                                                        Car
                                                    </span>
                                                </td>
                                                <td><?= htmlspecialchars($car_drv['maker_name'] ?? 'N/A') ?> <?= htmlspecialchars($car_drv['model_name'] ?? '') ?></td>
                                                <td>
                                                    Type: <?= htmlspecialchars($car_drv['type'] ?? 'N/A') ?><br>
                                                    Year: <?= htmlspecialchars($car_drv['made_year'] ?? 'N/A') ?>
                                                </td>
                                                <td><?= htmlspecialchars($car_drv['plate_no'] ?? 'N/A') ?></td>
                                                <td>
                                                    <?php if (isset($car_drv['assigned_date']) && $car_drv['assigned_date']): ?>
                                                        <?= format_safe_date($car_drv['assigned_date'], 'M d, Y') ?>
                                                        <br><small class="text-muted"><?= $DateConv->GregorianToHijri($car_drv['assigned_date'], $format) ?></small>
                                                    <?php else: echo 'N/A'; endif; ?>
                                                </td>
                                                <td>
                                                    <?php if ($has_return_date): ?>
                                                        <?= format_safe_date($car_drv['return_date'], 'M d, Y') ?>
                                                        <br><small class="text-muted"><?= $DateConv->GregorianToHijri($car_drv['return_date'], $format) ?></small>
                                                    <?php else: echo '-'; endif; ?>
                                                </td>
                                                <td>
                                                    <?php if ($is_active && !$has_return_date): ?>
                                                        <span class="badge badge-success">Active</span>
                                                    <?php else: ?>
                                                        <span class="badge badge-secondary">Returned</span>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>

                            <?php if ($count == 0): ?>
                                <div class="alert alert-info mt-3">
                                    <i class="fa fa-info-circle"></i> No assets assigned to this employee.
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
                order: [[5, 'desc']], // Sort by Assigned Date column
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
