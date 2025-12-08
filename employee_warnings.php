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

// Fetch warnings/disciplinary records from emp_notice table
$warnings_result = null;

try {
    $warnings_query = "SELECT * FROM emp_notice 
                      WHERE emp_id = ? AND is_deleted = 0
                      ORDER BY created_at DESC";
    $stmt = $conDB->prepare($warnings_query);
    $stmt->bind_param("i", $emp_id);
    $stmt->execute();
    $warnings_result = $stmt->get_result();
} catch (Exception $e) {
    // Table doesn't exist or query failed, create empty result
    $warnings_result = null;
}

// Create empty result if query failed
if (!$warnings_result) {
    $warnings_result = new stdClass();
    $warnings_result->num_rows = 0;
}
?>

<!doctype html>
<html lang="<?= $current_lang ?? 'en' ?>" <?= ($is_rtl ?? false) ? 'dir="rtl"' : '' ?>>
<head>
    <meta charset="utf-8" />
    <title><?= $site_title ?> - Employee Warnings</title>
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
        .warning-card {
            border-left: 4px solid #ffc107;
            margin-bottom: 15px;
            transition: transform 0.2s;
        }
        .warning-card:hover {
            transform: translateX(5px);
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .warning-card.warning {
            border-left-color: #ffd32a;
        }
        .warning-card.disciplinary_action {
            border-left-color: #ff6b6b;
        }
        .warning-card.violation,
        .warning-card.absence {
            border-left-color: #fc4a1a;
        }
        .warning-card.late_arrival {
            border-left-color: #f093fb;
        }
        .warning-card.appreciation,
        .warning-card.promotion {
            border-left-color: #11998e;
        }
        .warning-card.sick_leave,
        .warning-card.medical_report {
            border-left-color: #4facfe;
        }
        .warning-card.performance_review {
            border-left-color: #a8edea;
        }
        .warning-card.training {
            border-left-color: #667eea;
        }
        .warning-card.salary_adjustment {
            border-left-color: #ffecd2;
        }
        .warning-card.general,
        .warning-card.other {
            border-left-color: #89f7fe;
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
                            <?php if ($warnings_result && (is_object($warnings_result) && !($warnings_result instanceof stdClass)) && $warnings_result->num_rows > 0): ?>
                                <div class="row">
                                    <div class="col-lg-12">
                                        <?php while ($warning = $warnings_result->fetch_assoc()): 
                                            $note_type = $warning['note_type'] ?? 'general';
                                            
                                            // Set colors, badge class, and text color based on note type
                                            switch($note_type) {
                                                case 'warning':
                                                    $gradient_color = 'linear-gradient(135deg, #ffd32a 0%, #ff6b08 100%)'; // Yellow to Orange
                                                    $badge_class = 'badge-warning';
                                                    $text_color = '#333'; // Dark text for light background
                                                    break;
                                                case 'disciplinary_action':
                                                    $gradient_color = 'linear-gradient(135deg, #ff6b6b 0%, #ee5a6f 100%)'; // Red
                                                    $badge_class = 'badge-danger';
                                                    $text_color = '#fff'; // White text
                                                    break;
                                                case 'violation':
                                                case 'absence':
                                                    $gradient_color = 'linear-gradient(135deg, #fc4a1a 0%, #f7b733 100%)'; // Orange-Red
                                                    $badge_class = 'badge-danger';
                                                    $text_color = '#fff'; // White text
                                                    break;
                                                case 'late_arrival':
                                                    $gradient_color = 'linear-gradient(135deg, #f093fb 0%, #f5576c 100%)'; // Pink
                                                    $badge_class = 'badge-warning';
                                                    $text_color = '#fff'; // White text
                                                    break;
                                                case 'appreciation':
                                                case 'promotion':
                                                    $gradient_color = 'linear-gradient(135deg, #11998e 0%, #38ef7d 100%)'; // Green
                                                    $badge_class = 'badge-success';
                                                    $text_color = '#fff'; // White text
                                                    break;
                                                case 'sick_leave':
                                                case 'medical_report':
                                                    $gradient_color = 'linear-gradient(135deg, #4facfe 0%, #00f2fe 100%)'; // Blue
                                                    $badge_class = 'badge-info';
                                                    $text_color = '#fff'; // White text
                                                    break;
                                                case 'performance_review':
                                                    $gradient_color = 'linear-gradient(135deg, #a8edea 0%, #fed6e3 100%)'; // Light Blue-Pink
                                                    $badge_class = 'badge-info';
                                                    $text_color = '#333'; // Dark text for light background
                                                    break;
                                                case 'training':
                                                    $gradient_color = 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)'; // Purple
                                                    $badge_class = 'badge-primary';
                                                    $text_color = '#fff'; // White text
                                                    break;
                                                case 'salary_adjustment':
                                                    $gradient_color = 'linear-gradient(135deg, #ffecd2 0%, #fcb69f 100%)'; // Peach
                                                    $badge_class = 'badge-info';
                                                    $text_color = '#333'; // Dark text for light background
                                                    break;
                                                case 'general':
                                                case 'other':
                                                default:
                                                    $gradient_color = 'linear-gradient(135deg, #89f7fe 0%, #66a6ff 100%)'; // Light Blue
                                                    $badge_class = 'badge-secondary';
                                                    $text_color = '#fff'; // White text
                                                    break;
                                            }
                                        ?>
                                            <div class="card warning-card <?= strtolower($note_type) ?>">
                                                <div class="card-body">
                                                    <div class="row">
                                                        <div class="col-sm-9">
                                                            <h6 class="card-title mb-2">
                                                                <span class="badge <?= $badge_class ?>">
                                                                    <?= __($note_type, str_replace('_', ' ', $note_type)) ?>
                                                                </span>
                                                                <?= htmlspecialchars($warning['note'] ?? 'Notice') ?>
                                                            </h6>
                                                            <?php if (!empty($warning['attachment'])): ?>
                                                                <p class="card-text text-muted small mb-2">
                                                                    <strong><?= __('attachment', 'Attachment') ?>:</strong> 
                                                                    <a href="<?= htmlspecialchars($warning['attachment']) ?>" target="_blank" class="text-primary">
                                                                        <i class="fa fa-file"></i> <?= basename($warning['attachment']) ?>
                                                                    </a>
                                                                </p>
                                                            <?php endif; ?>
                                                        </div>
                                                        <div class="col-sm-3">
                                                            <div class="warning-info-box" style="background: <?= $gradient_color ?>; border-radius: 8px; padding: 20px; color: <?= $text_color ?>;">
                                                                <div class="text-center">
                                                                    <div style="font-size: 11px; opacity: 0.9; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 8px;">
                                                                        <i class="fa fa-calendar-alt" style="margin-<?= $is_rtl ? 'left' : 'right' ?>: 6px;"></i> <?= __('date', 'Date') ?>
                                                                    </div>
                                                                    <div style="font-weight: 700; font-size: 15px; margin-bottom: 4px;">
                                                                        <?= date('M d, Y', strtotime($warning['created_at'])) ?>
                                                                    </div>
                                                                    <div style="font-size: 12px; opacity: 0.85;">
                                                                        <?= $DateConv->GregorianToHijri($warning['created_at'], $format) ?>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endwhile; ?>
                                    </div>
                                </div>
                            <?php else: ?>
                                <div class="alert alert-success">
                                    <i class="fa fa-check-circle"></i> <?= __('no_warnings_found', 'No warnings or disciplinary records found. Employee has a clean record.') ?>
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
