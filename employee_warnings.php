<?php
/**
 * Employee Warnings Display Page
 * Shows all warnings/disciplinary records for a specific employee
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

// Fetch warnings/disciplinary records - adjust table name based on your schema
// Common table names might be: employee_warnings, disciplinary_actions, employee_discipline
$warnings_query = "SELECT * FROM employee_warnings 
                   WHERE emp_id = ? 
                   ORDER BY warning_date DESC";
                   
$stmt = $conDB->prepare($warnings_query);
$stmt->bind_param("s", $emp_id);
$stmt->execute();
$warnings_result = $stmt->get_result();

$severity_badges = [
    'verbal' => 'badge-info',
    'written' => 'badge-warning',
    'suspension' => 'badge-danger',
    'final' => 'badge-dark',
    'warning' => 'badge-warning',
    'critical' => 'badge-danger'
];
?>

<!doctype html>
<html lang="<?= $current_lang ?? 'en' ?>" <?= ($is_rtl ?? false) ? 'dir="rtl"' : '' ?>>
<head>
    <meta charset="utf-8" />
    <title><?= $site_title ?> - Employee Warnings</title>
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <link rel="shortcut icon" href="<?=get_setting($conDB, 'favicon')?>">
    <link href="./plugins/datatables/dataTables.bootstrap4.min.css" rel="stylesheet" type="text/css" />
    <link href="./assets/css/bootstrap.min.css" rel="stylesheet" type="text/css" />
    <link href="./assets/css/icons.min.css" rel="stylesheet" type="text/css" />
    <link href="./assets/css/style.min.css" rel="stylesheet" type="text/css" />
    <style>
        .warning-card {
            border-left: 4px solid #ffc107;
            margin-bottom: 15px;
            transition: transform 0.2s;
        }
        .warning-card:hover {
            transform: translateX(5px);
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .warning-card.critical {
            border-left-color: #dc3545;
        }
        .warning-card.suspension {
            border-left-color: #dc3545;
        }
    </style>
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
                                    <h4><?= htmlspecialchars($emprow['name']) ?> - Employee Warnings & Disciplinary Records</h4>
                                    <p class="text-muted">Employee ID: <?= htmlspecialchars($emprow['empid']) ?></p>
                                </div>
                                <div class="col-sm-4 text-right">
                                    <a href="profile.php?hashcode=<?= $emprow['empid'] ?>&verification=<?= $emprow['eid'] ?>" class="btn btn-sm btn-secondary">
                                        <i class="fa fa-arrow-left"></i> Back to Profile
                                    </a>
                                </div>
                            </div>

                            <?php if ($warnings_result->num_rows > 0): ?>
                                <div class="row">
                                    <div class="col-lg-12">
                                        <?php while ($warning = $warnings_result->fetch_assoc()): ?>
                                            <div class="card warning-card <?= strtolower($warning['severity'] ?? 'warning') ?>">
                                                <div class="card-body">
                                                    <div class="row">
                                                        <div class="col-sm-8">
                                                            <h6 class="card-title mb-2">
                                                                <span class="badge <?= $severity_badges[strtolower($warning['severity'] ?? 'warning')] ?? 'badge-secondary' ?>">
                                                                    <?= ucfirst($warning['severity'] ?? 'Warning') ?>
                                                                </span>
                                                                <?= htmlspecialchars($warning['title'] ?? $warning['reason'] ?? 'Disciplinary Action') ?>
                                                            </h6>
                                                            <p class="card-text text-muted small mb-2">
                                                                <strong>Reason:</strong> <?= htmlspecialchars($warning['reason'] ?? 'N/A') ?>
                                                            </p>
                                                            <p class="card-text mb-2">
                                                                <?= htmlspecialchars($warning['description'] ?? $warning['remarks'] ?? 'No additional details provided.') ?>
                                                            </p>
                                                        </div>
                                                        <div class="col-sm-4 text-right">
                                                            <div class="text-muted small">
                                                                <p class="mb-1">
                                                                    <strong>Date:</strong><br>
                                                                    <span class="date-batch-g"><?= date('M d, Y', strtotime($warning['warning_date'] ?? $warning['date'])) ?></span><br>
                                                                    <span class="text-muted"><?= $DateConv->GregorianToHijri($warning['warning_date'] ?? $warning['date'], $format) ?></span>
                                                                </p>
                                                                <?php if (!empty($warning['issued_by'])): ?>
                                                                    <p class="mb-1">
                                                                        <strong>Issued By:</strong><br>
                                                                        <?= htmlspecialchars($warning['issued_by']) ?>
                                                                    </p>
                                                                <?php endif; ?>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    
                                                    <?php if (!empty($warning['status']) && $warning['status'] !== 'active'): ?>
                                                        <div class="row mt-3">
                                                            <div class="col-12">
                                                                <small class="text-info">
                                                                    <strong>Status:</strong> 
                                                                    <span class="badge badge-pill badge-info">
                                                                        <?= ucfirst(str_replace('_', ' ', $warning['status'])) ?>
                                                                    </span>
                                                                </small>
                                                            </div>
                                                        </div>
                                                    <?php endif; ?>

                                                    <?php if (!empty($warning['action_required'])): ?>
                                                        <div class="row mt-2">
                                                            <div class="col-12">
                                                                <small class="text-warning">
                                                                    <strong><i class="fa fa-exclamation-circle"></i> Action Required:</strong> 
                                                                    <?= htmlspecialchars($warning['action_required']) ?>
                                                                </small>
                                                            </div>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        <?php endwhile; ?>
                                    </div>
                                </div>
                            <?php else: ?>
                                <div class="alert alert-success">
                                    <i class="fa fa-check-circle"></i> No warnings or disciplinary records found. Employee has a clean record.
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
