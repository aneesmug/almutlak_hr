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

// Fetch assigned assets (cars, machines, laptops, etc.)
$assets_query = "SELECT 'car' as asset_type, c.car_id as id, c.maker_name, c.model, c.made_year, c.plate_number, e.assign_date 
                 FROM all_cars c 
                 JOIN employees e ON c.car_id = e.car_id 
                 WHERE e.empid = ? AND c.status = 1
                 
                 UNION ALL
                 
                 SELECT 'machine' as asset_type, m.mac_id as id, m.mac_name as maker_name, m.mac_code as model, m.purchase_date as made_year, m.mac_notes as plate_number, ma.assign_date 
                 FROM all_machines m 
                 JOIN mac_assign ma ON m.mac_id = ma.mac_id 
                 WHERE ma.emp_id = ? AND ma.status = 1
                 
                 ORDER BY assign_date DESC";
                 
$stmt = $conDB->prepare($assets_query);
$stmt->bind_param("ss", $emp_id, $emp_id);
$stmt->execute();
$assets_result = $stmt->get_result();
?>

<!doctype html>
<html lang="<?= $current_lang ?? 'en' ?>" <?= ($is_rtl ?? false) ? 'dir="rtl"' : '' ?>>
<head>
    <meta charset="utf-8" />
    <title><?= $site_title ?> - Assigned Assets</title>
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <link rel="shortcut icon" href="<?=get_setting($conDB, 'favicon')?>">
    <link href="./plugins/datatables/dataTables.bootstrap4.min.css" rel="stylesheet" type="text/css" />
    <link href="./assets/css/bootstrap.min.css" rel="stylesheet" type="text/css" />
    <link href="./assets/css/icons.min.css" rel="stylesheet" type="text/css" />
    <link href="./assets/css/style.min.css" rel="stylesheet" type="text/css" />
</head>
<body class="authentication-bg-pattern">
    <div class="account-pages my-5 pt-5">
        <div class="container">
            <div class="row">
                <div class="col-lg-12">
                    <div class="card">
                        <div class="card-body p-4">
                            <div class="row mb-4">
                                <div class="col-sm-8">
                                    <h4><?= htmlspecialchars($emprow['name']) ?> - Assigned Assets</h4>
                                    <p class="text-muted">Employee ID: <?= htmlspecialchars($emprow['empid']) ?></p>
                                </div>
                                <div class="col-sm-4 text-right">
                                    <a href="profile.php?hashcode=<?= $emprow['empid'] ?>&verification=<?= $emprow['eid'] ?>" class="btn btn-sm btn-secondary">
                                        <i class="fa fa-arrow-left"></i> Back to Profile
                                    </a>
                                </div>
                            </div>

                            <div class="table-responsive">
                                <table class="table table-hover mb-0 datatable">
                                    <thead>
                                        <tr>
                                            <th>Asset Type</th>
                                            <th>Maker/Name</th>
                                            <th>Model/Code</th>
                                            <th>Year/Reference</th>
                                            <th>Details</th>
                                            <th>Assigned Date</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while ($asset = $assets_result->fetch_assoc()): ?>
                                            <tr>
                                                <td>
                                                    <span class="badge badge-info">
                                                        <?= ucfirst($asset['asset_type']) ?>
                                                    </span>
                                                </td>
                                                <td><?= htmlspecialchars($asset['maker_name'] ?? 'N/A') ?></td>
                                                <td><?= htmlspecialchars($asset['model'] ?? 'N/A') ?></td>
                                                <td><?= htmlspecialchars($asset['made_year'] ?? 'N/A') ?></td>
                                                <td><?= htmlspecialchars($asset['plate_number'] ?? 'N/A') ?></td>
                                                <td>
                                                    <?php if (isset($asset['assign_date']) && $asset['assign_date']): ?>
                                                        <span class="date-batch-g"><?= date('M d, Y', strtotime($asset['assign_date'])) ?></span>
                                                        <br><small class="text-muted"><?= $DateConv->GregorianToHijri($asset['assign_date'], $format) ?></small>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <span class="badge badge-success">Active</span>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>

                            <?php if ($assets_result->num_rows == 0): ?>
                                <div class="alert alert-info">
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
    <script>
        $(document).ready(function() {
            $('.datatable').DataTable({
                order: [[5, 'desc']],
                pageLength: 25
            });
        });
    </script>
</body>
</html>
