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
    'apply' => 'Applied',
    'pending' => 'Assistant Pending',
    'hr_assistant_approved' => 'HR Assistant Approved',
    'hr_manager_approved' => 'HR Manager Approved',
    'gm_approved' => 'GM Approved',
    'rejected' => 'Rejected'
];

$status_badges = [
    'apply' => 'badge-secondary',
    'pending' => 'badge-warning',
    'hr_assistant_approved' => 'badge-info',
    'hr_manager_approved' => 'badge-primary',
    'gm_approved' => 'badge-success',
    'rejected' => 'badge-danger'
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
</head>
<body class="authentication-bg-pattern">
    <div class="profile-container">
        <div class="profile-header">
            <div class="container-custom">
                <img src="<?= htmlspecialchars($emprow['avatar'] ?? './assets/images/users/avatar-1.jpg') ?>" alt="<?= htmlspecialchars($emprow['name']) ?>" class="profile-avatar">
                <div class="profile-header-info">
                    <h1><?= htmlspecialchars($emprow['name']) ?></h1>
                    <p><strong><?= __('employee_id') ?>:</strong> <?= htmlspecialchars($emprow['emp_id']) ?></p>
                </div>
                <?php
                    $qrPath = "./assets/qrcodes/" . (($emprow['eid'] ?? '') . $emprow['emp_id']) . ".png";
                    if (!empty($emprow['emp_id']) && file_exists($qrPath)):
                ?>
                    <img src="<?= $qrPath ?>" alt="QR Code" class="qr-code">
                <?php endif; ?>
            </div>
        </div>
    </div>
    <div class="account-pages my-5 pt-5">
        <div class="container">
            <div class="row">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-body p-4">
                            <div class="row mb-4">
                                <div class="col-sm-8">
                                    <h4><?= htmlspecialchars($emprow['name']) ?> - Vacation History</h4>
                                    <p class="text-muted">Employee ID: <?= htmlspecialchars($emprow['emp_id']) ?></p>
                                </div>
                                <div class="col-sm-4 text-right">
                                    <a href="profile.php" class="btn btn-sm btn-secondary">
                                        <i class="fa fa-arrow-left"></i> Back to Profile
                                    </a>
                                </div>
                            </div>

                            <div class="table-responsive">
                                <table class="table table-hover mb-0 datatable">
                                    <thead>
                                        <tr>
                                            <th>From Date</th>
                                            <th>To Date</th>
                                            <th>Days</th>
                                            <th>Type</th>
                                            <th>Fly Type</th>
                                            <th>Status</th>
                                            <th>Applied Date</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while ($vac = $vacation_result->fetch_assoc()): ?>
                                            <tr>
                                                <td>
                                                    <?= date('M d, Y', strtotime($vac['start_date'])) ?>
                                                    <br><small class="text-muted"><?= $DateConv->GregorianToHijri($vac['start_date'], $format) ?></small>
                                                </td>
                                                <td>
                                                    <?= date('M d, Y', strtotime($vac['return_date'])) ?>
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
                                                        <?= $all_statuses[$vac['current_status']] ?? ucfirst(str_replace('_', ' ', $vac['current_status'])) ?>
                                                    </span>
                                                </td>
                                                <td><?= date('M d, Y', strtotime($vac['created_at'])) ?></td>
                                                <td>
                                                    <a href="vacation_report_details.php?id=<?= $vac['id'] ?>&emp_id=<?= $vac['emp_id'] ?>" class="btn btn-sm btn-info" title="View Details">
                                                        <i class="fa fa-eye"></i>
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>

                            <?php if ($vacation_result->num_rows == 0): ?>
                                <div class="alert alert-info">
                                    <i class="fa fa-info-circle"></i> No vacation history found for this employee.
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
    <script>
        $(document).ready(function() {
            $('.datatable').DataTable({
                order: [[6, 'desc']],
                pageLength: 25
            });
        });
    </script>
</body>
</html>
