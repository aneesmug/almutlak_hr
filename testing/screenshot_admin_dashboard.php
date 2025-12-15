<?php
/**
 * Screenshot System - Admin Dashboard
 * Main entry point for managing system guide screenshots
 */
require_once(__DIR__ . "/includes/init.php");
require_once(__DIR__ . "/includes/session_check.php");

// Check admin access
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] != 'administrator') {
    header("Location: profile.php");
    exit;
}

// Get upload statistics
$stats = [
    'total_screenshots' => 0,
    'by_section' => []
];

try {
    $stmt = $pdo->query("SELECT section, COUNT(*) as count FROM guide_screenshots WHERE is_active = 1 GROUP BY section");
    $sections = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($sections as $sec) {
        $stats['by_section'][$sec['section']] = $sec['count'];
        $stats['total_screenshots'] += $sec['count'];
    }
} catch (PDOException $e) {
    // Silent fail
}

$required = [
    'vacations' => 21,
    'loans' => 12,
    'excuse' => 4,
    'resignation' => 3,
    'rejoin' => 3
];

$total_required = array_sum($required);
?>
<!DOCTYPE html>
<html lang="<?= $current_lang ?? 'en' ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Screenshot System Admin Dashboard</title>
    <link href="assets/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        :root {
            --primary: #007bff;
            --success: #28a745;
            --danger: #dc3545;
            --warning: #ffc107;
            --info: #17a2b8;
        }
        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            padding: 20px 0;
        }
        .container {
            max-width: 1400px;
        }
        .header {
            background: linear-gradient(135deg, var(--primary) 0%, var(--info) 100%);
            color: white;
            padding: 40px 20px;
            border-radius: 12px;
            margin-bottom: 30px;
            box-shadow: 0 4px 16px rgba(0,0,0,0.2);
            text-align: center;
        }
        .header h1 {
            font-size: 32px;
            font-weight: 700;
            margin-bottom: 10px;
        }
        .header p {
            font-size: 16px;
            opacity: 0.9;
            margin: 0;
        }
        .card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            margin-bottom: 20px;
            border: none;
            transition: all 0.3s ease;
        }
        .card:hover {
            box-shadow: 0 4px 16px rgba(0,0,0,0.15);
            transform: translateY(-2px);
        }
        .card-header {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border-bottom: 2px solid #dee2e6;
            padding: 20px;
        }
        .card-header h5 {
            margin: 0;
            color: var(--primary);
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .card-body {
            padding: 20px;
        }
        .stat-box {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            border-left: 4px solid var(--primary);
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 15px;
        }
        .stat-number {
            font-size: 28px;
            font-weight: 700;
            color: var(--primary);
        }
        .stat-label {
            font-size: 13px;
            color: #6c757d;
            margin-top: 5px;
        }
        .progress {
            height: 25px;
            margin-bottom: 10px;
        }
        .btn-action {
            display: inline-block;
            padding: 12px 24px;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            margin: 5px;
            text-align: center;
            border: none;
            cursor: pointer;
        }
        .btn-primary-action {
            background: var(--primary);
            color: white;
        }
        .btn-primary-action:hover {
            background: #0056b3;
            color: white;
            transform: translateY(-2px);
        }
        .btn-secondary-action {
            background: var(--info);
            color: white;
        }
        .btn-secondary-action:hover {
            background: #138496;
            color: white;
            transform: translateY(-2px);
        }
        .btn-success-action {
            background: var(--success);
            color: white;
        }
        .btn-success-action:hover {
            background: #218838;
            color: white;
            transform: translateY(-2px);
        }
        .section-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin: 15px 0;
        }
        .section-item {
            background: #f8f9fa;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            padding: 15px;
            text-align: center;
            transition: all 0.3s ease;
        }
        .section-item:hover {
            border-color: var(--primary);
            background: #f0f7ff;
        }
        .section-name {
            font-weight: 600;
            color: #333;
            margin-bottom: 10px;
        }
        .section-count {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 5px;
        }
        .section-count-num {
            font-size: 20px;
            font-weight: 700;
            color: var(--primary);
        }
        .section-count-total {
            font-size: 13px;
            color: #6c757d;
        }
        .quick-links {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 15px;
            margin: 20px 0;
        }
        .quick-link {
            background: white;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            padding: 20px;
            text-align: center;
            transition: all 0.3s ease;
        }
        .quick-link:hover {
            border-color: var(--primary);
            box-shadow: 0 4px 12px rgba(0,123,255,0.2);
        }
        .quick-link-icon {
            font-size: 32px;
            color: var(--primary);
            margin-bottom: 10px;
        }
        .quick-link-title {
            font-weight: 600;
            color: #333;
            margin-bottom: 8px;
        }
        .quick-link-desc {
            font-size: 13px;
            color: #6c757d;
            margin-bottom: 12px;
        }
        .quick-link a {
            display: inline-block;
            padding: 8px 16px;
            background: var(--primary);
            color: white;
            border-radius: 4px;
            text-decoration: none;
            font-size: 13px;
            font-weight: 600;
        }
        .quick-link a:hover {
            background: #0056b3;
            color: white;
        }
        .warning-box {
            background: #fff3cd;
            border-left: 4px solid var(--warning);
            padding: 15px;
            border-radius: 6px;
            margin: 15px 0;
            color: #856404;
        }
        .info-box {
            background: #d1ecf1;
            border-left: 4px solid var(--info);
            padding: 15px;
            border-radius: 6px;
            margin: 15px 0;
            color: #0c5460;
        }
        .steps-list {
            list-style: none;
            padding: 0;
            margin: 15px 0;
        }
        .steps-list li {
            padding: 10px;
            margin: 8px 0;
            background: #f8f9fa;
            border-left: 4px solid var(--primary);
            border-radius: 4px;
        }
        .steps-list strong {
            color: var(--primary);
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <h1><i class="fa fa-images"></i> Screenshot System Admin Dashboard</h1>
            <p>Manage and upload screenshots for the system guide</p>
        </div>

        <!-- Overall Statistics -->
        <div class="row">
            <div class="col-md-6 col-lg-3">
                <div class="stat-box">
                    <div class="stat-number"><?= $stats['total_screenshots'] ?></div>
                    <div class="stat-label">Screenshots Uploaded</div>
                </div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="stat-box">
                    <div class="stat-number"><?= $total_required ?></div>
                    <div class="stat-label">Screenshots Required</div>
                </div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="stat-box">
                    <div class="stat-number"><?= $total_required - $stats['total_screenshots'] ?></div>
                    <div class="stat-label">Still Needed</div>
                </div>
            </div>
            <div class="col-md-6 col-lg-3">
                <div class="stat-box">
                    <div class="stat-number"><?= round(($stats['total_screenshots'] / $total_required) * 100) ?>%</div>
                    <div class="stat-label">Complete</div>
                </div>
            </div>
        </div>

        <!-- Overall Progress -->
        <div class="card" style="margin-bottom: 30px;">
            <div class="card-body">
                <h5 style="margin-bottom: 15px;">Overall Progress</h5>
                <div class="progress">
                    <div class="progress-bar bg-success" role="progressbar" style="width: <?= round(($stats['total_screenshots'] / $total_required) * 100) ?>%" aria-valuenow="<?= round(($stats['total_screenshots'] / $total_required) * 100) ?>" aria-valuemin="0" aria-valuemax="100"></div>
                </div>
                <small class="text-muted"><?= $stats['total_screenshots'] ?> of <?= $total_required ?> screenshots uploaded</small>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="card">
            <div class="card-header">
                <h5><i class="fa fa-lightning"></i> Quick Actions</h5>
            </div>
            <div class="card-body">
                <div class="quick-links">
                    <div class="quick-link">
                        <div class="quick-link-icon"><i class="fa fa-upload"></i></div>
                        <div class="quick-link-title">Upload Screenshots</div>
                        <div class="quick-link-desc">Add new screenshots to the guide</div>
                        <a href="manage_guide_screenshots.php"><i class="fa fa-arrow-right"></i> Go</a>
                    </div>
                    <div class="quick-link">
                        <div class="quick-link-icon"><i class="fa fa-list-check"></i></div>
                        <div class="quick-link-title">Upload Checklist</div>
                        <div class="quick-link-desc">Track your upload progress</div>
                        <a href="screenshot_checklist.html"><i class="fa fa-arrow-right"></i> Go</a>
                    </div>
                    <div class="quick-link">
                        <div class="quick-link-icon"><i class="fa fa-tasks"></i></div>
                        <div class="quick-link-title">View Requirements</div>
                        <div class="quick-link-desc">See what screenshots you need</div>
                        <a href="screenshot_requirements.php"><i class="fa fa-arrow-right"></i> Go</a>
                    </div>
                    <div class="quick-link">
                        <div class="quick-link-icon"><i class="fa fa-book"></i></div>
                        <div class="quick-link-title">View System Guide</div>
                        <div class="quick-link-desc">See how guide looks to users</div>
                        <a href="system_guide.php"><i class="fa fa-arrow-right"></i> Go</a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Section Status -->
        <div class="card" style="margin-top: 30px;">
            <div class="card-header">
                <h5><i class="fa fa-chart-bar"></i> Screenshots by Section</h5>
            </div>
            <div class="card-body">
                <div class="section-grid">
                    <?php
                    $sections = ['vacations' => 'Vacations & Leaves', 'loans' => 'Loans', 'excuse' => 'Excuse Leave', 'resignation' => 'Resignation', 'rejoin' => 'Rejoin'];
                    foreach ($sections as $key => $label):
                        $uploaded = $stats['by_section'][$key] ?? 0;
                        $needed = $required[$key];
                        $percentage = round(($uploaded / $needed) * 100);
                    ?>
                    <div class="section-item">
                        <div class="section-name"><?= $label ?></div>
                        <div class="progress" style="margin: 10px 0;">
                            <div class="progress-bar" style="width: <?= $percentage ?>%" role="progressbar" aria-valuenow="<?= $percentage ?>" aria-valuemin="0" aria-valuemax="100"></div>
                        </div>
                        <div class="section-count">
                            <span class="section-count-num"><?= $uploaded ?></span>
                            <span class="section-count-total">/ <?= $needed ?></span>
                        </div>
                        <small class="text-muted" style="display: block; margin-top: 8px;"><?= $percentage ?>% complete</small>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Getting Started -->
        <div class="card" style="margin-top: 30px;">
            <div class="card-header">
                <h5><i class="fa fa-play-circle"></i> Getting Started</h5>
            </div>
            <div class="card-body">
                <div class="info-box">
                    <strong>Total Estimated Time:</strong> 2-3 hours to upload all screenshots
                </div>

                <h6 style="margin-top: 20px; color: var(--primary); font-weight: 700;">Follow these 5 steps:</h6>
                <ol class="steps-list">
                    <li>
                        <strong>Step 1: Prepare Your Screenshots</strong>
                        <ul style="margin: 8px 0 0 0; padding-left: 20px;">
                            <li>Use consistent browser and screen resolution (1280x720 or 1366x768)</li>
                            <li>Hide sensitive information (names, IDs, passwords)</li>
                            <li>Take screenshots for each step in the process</li>
                            <li>Keep file sizes under 2MB (compress if needed)</li>
                        </ul>
                    </li>
                    <li>
                        <strong>Step 2: Review Requirements</strong>
                        <ul style="margin: 8px 0 0 0; padding-left: 20px;">
                            <li>Go to <a href="screenshot_requirements.php">screenshot_requirements.php</a></li>
                            <li>See exactly what screenshots are needed for each section</li>
                            <li>Start with Vacations & Leaves (21 screenshots)</li>
                        </ul>
                    </li>
                    <li>
                        <strong>Step 3: Upload Screenshots</strong>
                        <ul style="margin: 8px 0 0 0; padding-left: 20px;">
                            <li>Go to <a href="manage_guide_screenshots.php">manage_guide_screenshots.php</a></li>
                            <li>Select Section → Step Number → Add Title → Upload Image</li>
                            <li>One upload at a time (allows for organizing in order)</li>
                        </ul>
                    </li>
                    <li>
                        <strong>Step 4: Track Progress</strong>
                        <ul style="margin: 8px 0 0 0; padding-left: 20px;">
                            <li>Use <a href="screenshot_checklist.html">screenshot_checklist.html</a></li>
                            <li>Check off each step as you upload images</li>
                            <li>Automatically saves your progress</li>
                        </ul>
                    </li>
                    <li>
                        <strong>Step 5: Verify in System Guide</strong>
                        <ul style="margin: 8px 0 0 0; padding-left: 20px;">
                            <li>Go to <a href="system_guide.php">system_guide.php</a></li>
                            <li>View how screenshots appear to employees</li>
                            <li>Ensure all images display correctly</li>
                        </ul>
                    </li>
                </ol>
            </div>
        </div>

        <!-- Requirements Breakdown -->
        <div class="card" style="margin-top: 30px;">
            <div class="card-header">
                <h5><i class="fa fa-clipboard-list"></i> Screenshot Requirements</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <h6 style="color: var(--primary); font-weight: 700; margin-bottom: 15px;">VACATIONS & LEAVES (21 screenshots)</h6>
                        <ul class="steps-list">
                            <li><strong>Annual Leave:</strong> 7 steps<br><small class="text-muted">Section: "vacations", Step: 1</small></li>
                            <li><strong>Emergency Leave:</strong> 6-7 steps<br><small class="text-muted">Section: "vacations", Step: 2</small></li>
                            <li><strong>Encashment:</strong> 6-7 steps<br><small class="text-muted">Section: "vacations", Step: 3</small></li>
                        </ul>
                    </div>
                    <div class="col-md-6">
                        <h6 style="color: var(--primary); font-weight: 700; margin-bottom: 15px;">LOANS & OTHER (14 screenshots)</h6>
                        <ul class="steps-list">
                            <li><strong>EOS Loan:</strong> 4 steps<br><small class="text-muted">Section: "loans", Step: 1</small></li>
                            <li><strong>House Loan:</strong> 4 steps<br><small class="text-muted">Section: "loans", Step: 2</small></li>
                            <li><strong>Advance Salary:</strong> 4 steps<br><small class="text-muted">Section: "loans", Step: 3</small></li>
                            <li><strong>Excuse Leave:</strong> 4 steps<br><small class="text-muted">Section: "excuse", Step: 3</small></li>
                            <li><strong>Resignation:</strong> 3 steps<br><small class="text-muted">Section: "resignation", Step: 1</small></li>
                            <li><strong>Rejoin:</strong> 3 steps<br><small class="text-muted">Section: "rejoin", Step: 3</small></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <!-- File Requirements -->
        <div class="card" style="margin-top: 30px; margin-bottom: 40px;">
            <div class="card-header">
                <h5><i class="fa fa-file-image"></i> File Requirements</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4">
                        <h6 style="margin-bottom: 10px;"><strong>Supported Formats</strong></h6>
                        <ul style="padding-left: 20px;">
                            <li>JPG / JPEG</li>
                            <li>PNG</li>
                            <li>GIF</li>
                            <li>WebP</li>
                        </ul>
                    </div>
                    <div class="col-md-4">
                        <h6 style="margin-bottom: 10px;"><strong>Recommended Size</strong></h6>
                        <ul style="padding-left: 20px;">
                            <li>Width: 1280px</li>
                            <li>Height: 720px</li>
                            <li>File: &lt; 2MB</li>
                            <li>Max: 5MB</li>
                        </ul>
                    </div>
                    <div class="col-md-4">
                        <h6 style="margin-bottom: 10px;"><strong>Best Practices</strong></h6>
                        <ul style="padding-left: 20px;">
                            <li>Clear and readable</li>
                            <li>Hide sensitive data</li>
                            <li>Consistent resolution</li>
                            <li>Professional quality</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="assets/js/jquery.min.js"></script>
    <script src="assets/js/bootstrap.bundle.min.js"></script>
</body>
</html>
