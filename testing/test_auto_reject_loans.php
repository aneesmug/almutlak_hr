<?php
/**
 * Test Page: Auto-Reject Stale Loan Requests
 * 
 * This page allows administrators to:
 * 1. View current configuration
 * 2. See which loans would be auto-rejected
 * 3. Manually trigger the auto-rejection process
 * 4. View execution logs
 * 
 * SECURITY: Restricted to system administrators only
 */

require_once __DIR__ . '/includes/session_check.php';
require_once __DIR__ . '/includes/db.php';

// Restrict to system admin only
if (!isset($is_system_admin) || !$is_system_admin) {
    header("Location: ./dashboard.php");
    exit();
}

$DAYS_THRESHOLD = 3;
$page_title = "Auto-Reject Stale Loans - Test & Monitor";

// Handle manual trigger
$execution_output = '';
$manual_trigger = false;

if (isset($_POST['trigger_auto_reject']) && $_POST['trigger_auto_reject'] === 'yes') {
    $manual_trigger = true;
    ob_start();
    include __DIR__ . '/cron_auto_reject_stale_loans.php';
    $execution_output = ob_get_clean();
}

// Get request type ID
$type_query = mysqli_query($conDB, "SELECT `id` FROM `approval_request_types` WHERE `type_name` = 'loan_request' LIMIT 1");
$request_type_id = 0;
if ($type_query && mysqli_num_rows($type_query) > 0) {
    $request_type_id = (int)mysqli_fetch_assoc($type_query)['id'];
}

// Find stale requests (would be auto-rejected)
$cutoff_date = date('Y-m-d H:i:s', strtotime("-{$DAYS_THRESHOLD} days"));
$stale_requests = [];

if ($request_type_id > 0) {
    $sql = "SELECT 
                l.id,
                l.inv_no,
                l.emp_id,
                l.loan_amount,
                l.loan_type,
                l.created_at,
                e.name as employee_name,
                e.dept,
                d.name as dept_name,
                ra.approver_id,
                ra.approval_level,
                approver.name as supervisor_name,
                TIMESTAMPDIFF(HOUR, l.created_at, NOW()) as hours_pending,
                TIMESTAMPDIFF(DAY, l.created_at, NOW()) as days_pending
            FROM emp_loan l
            JOIN employees e ON l.emp_id = e.emp_id
            LEFT JOIN department d ON e.dept = d.dept_id
            JOIN request_approvers ra ON ra.request_inv_no = l.inv_no 
                AND ra.request_type_id = ?
                AND ra.approval_level = 1
                AND ra.status = 'pending'
            LEFT JOIN employees approver ON ra.approver_id = approver.emp_id
            WHERE l.status = 'pending'
                AND l.created_at <= ?
                AND l.inv_no NOT LIKE 'LEGACY-%'
            ORDER BY l.created_at ASC";
    
    $stmt = $conDB->prepare($sql);
    if ($stmt) {
        $stmt->bind_param("is", $request_type_id, $cutoff_date);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $stale_requests[] = $row;
        }
        $stmt->close();
    }
}

// Get recent log entries
$log_file = __DIR__ . '/cron_logs/auto_reject_loans_' . date('Y-m') . '.log';
$recent_logs = '';
if (file_exists($log_file)) {
    $log_content = file_get_contents($log_file);
    $log_lines = explode("\n", $log_content);
    $recent_logs = implode("\n", array_slice($log_lines, -100)); // Last 100 lines
}

?>
<!doctype html>
<html lang="<?= $current_lang ?? 'en' ?>">
<head>
    <meta charset="utf-8" />
    <title><?= $site_title ?> - <?= $page_title ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <link rel="shortcut icon" href="<?= get_setting($conDB, 'favicon') ?>">
    <link href="assets/css/bootstrap.min.css" rel="stylesheet" type="text/css" />
    <link href="assets/css/style.css" rel="stylesheet" type="text/css" />
    <style>
        .info-card { border-left: 4px solid #17a2b8; }
        .warning-card { border-left: 4px solid #ffc107; }
        .danger-card { border-left: 4px solid #dc3545; }
        .log-output { background: #1e1e1e; color: #d4d4d4; padding: 15px; border-radius: 5px; font-family: 'Courier New', monospace; font-size: 12px; max-height: 500px; overflow-y: auto; }
        .stale-request-card { border-left: 3px solid #dc3545; }
    </style>
</head>
<body class="enlarged">
    <div id="wrapper">
        <?php include 'includes/header.php'; ?>
        
        <div class="content-page">
            <div class="content">
                <div class="container-fluid">
                    <div class="row">
                        <div class="col-12">
                            <div class="page-title-box">
                                <h4 class="page-title"><?= $page_title ?></h4>
                            </div>
                        </div>
                    </div>

                    <?php if ($manual_trigger && $execution_output): ?>
                    <div class="row">
                        <div class="col-12">
                            <div class="alert alert-success alert-dismissible fade show">
                                <button type="button" class="close" data-dismiss="alert">&times;</button>
                                <strong>Execution Completed!</strong> The auto-rejection process has been triggered manually.
                            </div>
                            <div class="card">
                                <div class="card-header bg-dark text-white">
                                    <h5 class="mb-0">Execution Output</h5>
                                </div>
                                <div class="card-body">
                                    <pre class="log-output"><?= htmlspecialchars($execution_output) ?></pre>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Configuration Info -->
                    <div class="row">
                        <div class="col-md-4">
                            <div class="card info-card">
                                <div class="card-body">
                                    <h5 class="card-title"><i class="fa fa-cog"></i> Configuration</h5>
                                    <p class="mb-1"><strong>Rejection Threshold:</strong> <?= $DAYS_THRESHOLD ?> days</p>
                                    <p class="mb-1"><strong>Cutoff Date:</strong> <?= date('Y-m-d H:i', strtotime($cutoff_date)) ?></p>
                                    <p class="mb-0"><strong>Current Time:</strong> <?= date('Y-m-d H:i:s') ?></p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card warning-card">
                                <div class="card-body">
                                    <h5 class="card-title"><i class="fa fa-clock-o"></i> Stale Requests</h5>
                                    <h2 class="mb-0"><?= count($stale_requests) ?></h2>
                                    <p class="mb-0">Pending > <?= $DAYS_THRESHOLD ?> days</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card danger-card">
                                <div class="card-body">
                                    <h5 class="card-title"><i class="fa fa-bolt"></i> Manual Trigger</h5>
                                    <form method="POST" onsubmit="return confirm('Are you sure you want to manually trigger auto-rejection for all stale loans?');">
                                        <input type="hidden" name="trigger_auto_reject" value="yes">
                                        <button type="submit" class="btn btn-danger btn-block">
                                            <i class="fa fa-play"></i> Run Auto-Reject Now
                                        </button>
                                    </form>
                                    <small class="text-muted">This will reject <?= count($stale_requests) ?> request(s)</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Stale Requests List -->
                    <?php if (count($stale_requests) > 0): ?>
                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header bg-warning">
                                    <h5 class="mb-0"><i class="fa fa-exclamation-triangle"></i> Loans Pending Auto-Rejection</h5>
                                </div>
                                <div class="card-body">
                                    <?php foreach ($stale_requests as $req): ?>
                                    <div class="card stale-request-card mb-3">
                                        <div class="card-body">
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <p class="mb-1"><strong>Invoice:</strong> <?= htmlspecialchars($req['inv_no']) ?></p>
                                                    <p class="mb-1"><strong>Employee:</strong> <?= htmlspecialchars($req['employee_name']) ?> (<?= $req['emp_id'] ?>)</p>
                                                    <p class="mb-1"><strong>Department:</strong> <?= htmlspecialchars($req['dept_name'] ?? 'N/A') ?></p>
                                                    <p class="mb-0"><strong>Loan Type:</strong> <?= htmlspecialchars($req['loan_type']) ?></p>
                                                </div>
                                                <div class="col-md-3">
                                                    <p class="mb-1"><strong>Amount:</strong> <?= number_format($req['loan_amount'], 2) ?> SAR</p>
                                                    <p class="mb-1"><strong>Created:</strong> <?= date('Y-m-d H:i', strtotime($req['created_at'])) ?></p>
                                                    <p class="mb-0"><strong>Days Pending:</strong> <span class="badge badge-danger"><?= $req['days_pending'] ?> days</span></p>
                                                </div>
                                                <div class="col-md-3">
                                                    <p class="mb-1"><strong>Pending With:</strong></p>
                                                    <p class="mb-0"><?= htmlspecialchars($req['supervisor_name'] ?? 'Unknown') ?></p>
                                                    <small class="text-muted">Level 1 Approval</small>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php else: ?>
                    <div class="row">
                        <div class="col-12">
                            <div class="alert alert-success">
                                <i class="fa fa-check-circle"></i> <strong>All Clear!</strong> No stale loan requests found. All requests are within the <?= $DAYS_THRESHOLD ?>-day approval timeframe.
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Recent Logs -->
                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header bg-dark text-white">
                                    <h5 class="mb-0"><i class="fa fa-file-text-o"></i> Recent Execution Logs</h5>
                                </div>
                                <div class="card-body">
                                    <?php if (!empty($recent_logs)): ?>
                                        <pre class="log-output"><?= htmlspecialchars($recent_logs) ?></pre>
                                    <?php else: ?>
                                        <p class="text-muted">No execution logs found for this month.</p>
                                        <p><small>Log file: <?= $log_file ?></small></p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
            <?php include 'includes/footer.php'; ?>
        </div>
    </div>

    <script src="assets/js/jquery.min.js"></script>
    <script src="assets/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/waves.js"></script>
    <script src="assets/js/jquery.core.js"></script>
    <script src="assets/js/jquery.app.js"></script>
</body>
</html>
<?php
if (isset($conDB)) {
    mysqli_close($conDB);
}
?>
