<?php
/**
 * Loan Rejection Report Viewer
 *
 * Reads the last saved JSON report from cron_auto_reject_stale_loans.php
 * and renders a GUI page for viewing all auto-rejected loan requests.
 */
require_once __DIR__ . '/includes/session_check.php';

// Allow: System admin, administrator
$can_view_report = ( $is_system_admin || $user_type == 'administrator' );

if (!$can_view_report) {
    header("Location: ./dashboard.php");
    exit();
}

error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

date_default_timezone_set('Asia/Riyadh');

$report_file = __DIR__ . '/cron_logs/last_loan_rejection_report.json';

// Handle clear history action
if (isset($_GET['action']) && $_GET['action'] === 'clear_history' && isset($_GET['confirm']) && $_GET['confirm'] === 'yes') {
    if ($can_view_report) {
        // Clear the JSON file
        $empty_report = [
            'timestamp' => date('Y-m-d H:i:s'),
            'total_stale' => 0,
            'rejected_count' => 0,
            'failed_count' => 0,
            'days_threshold' => 3,
            'rejections_log' => []
        ];
        file_put_contents($report_file, json_encode($empty_report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        header('Location: ./loan_rejection_report.php?cleared=1');
        exit;
    }
}

$cleared = isset($_GET['cleared']) && $_GET['cleared'] === '1';

function show_no_report_available() {
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Loan Rejection Report - No Report</title>
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
        <style>
            body {
                font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                min-height: 100vh;
                display: flex;
                align-items: center;
                justify-content: center;
                padding: 20px;
            }
            .container {
                background: white;
                border-radius: 10px;
                box-shadow: 0 10px 40px rgba(0,0,0,0.2);
                padding: 60px 40px;
                text-align: center;
                max-width: 600px;
            }
            .icon {
                font-size: 64px;
                color: #ffc107;
                margin-bottom: 20px;
            }
            h1 {
                color: #333;
                margin-bottom: 10px;
                font-size: 24px;
            }
            p {
                color: #666;
                line-height: 1.6;
                margin-bottom: 20px;
            }
            .button {
                display: inline-block;
                background: #667eea;
                color: white;
                padding: 12px 30px;
                border-radius: 5px;
                text-decoration: none;
                margin-top: 20px;
            }
            .button:hover {
                background: #764ba2;
            }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="icon">
                <i class="fas fa-inbox"></i>
            </div>
            <h1>No Report Available</h1>
            <p>The loan rejection cron job has not been run yet, or no saved report exists.</p>
            <p>Please wait for the scheduled cron job to run, or run it manually from the command line.</p>
            <p style="font-size: 12px; color: #999; margin-top: 30px;">Report Time: <?php echo date('Y-m-d H:i:s'); ?></p>
        </div>
    </body>
    </html>
    <?php
}

function display_gui_report($rejected_count, $failed_count, $total_stale, $rejections_log, $report_timestamp = null, $days_threshold = 3) {
    if ($report_timestamp === null) {
        $report_timestamp = date('Y-m-d H:i:s');
    }
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Loan Rejection Report - Auto-Rejected Requests</title>
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
        <style>
            * { margin: 0; padding: 0; box-sizing: border-box; }
            body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; padding: 20px; }
            .container { max-width: 1400px; margin: 0 auto; }
            .header { background: white; padding: 30px; border-radius: 10px 10px 0 0; box-shadow: 0 2px 10px rgba(0,0,0,0.1); display: flex; align-items: center; gap: 20px; }
            .header h1 { color: #333; font-size: 28px; }
            .header .icon { font-size: 40px; color: #dc3545; }
            .summary { background: white; padding: 30px; display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
            .summary-card { padding: 20px; border-radius: 8px; text-align: center; display: flex; flex-direction: column; align-items: center; gap: 10px; }
            .summary-card.total { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; }
            .summary-card.rejected { background: linear-gradient(135deg, #ff6b6b 0%, #ff8787 100%); color: white; }
            .summary-card.failed { background: linear-gradient(135deg, #ffa502 0%, #ffb300 100%); color: white; }
            .summary-card .icon { font-size: 32px; }
            .summary-card .number { font-size: 32px; font-weight: bold; }
            .summary-card .label { font-size: 14px; opacity: 0.9; }
            .details-section { background: white; padding: 30px; margin-top: 20px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
            .details-section h2 { color: #333; margin-bottom: 20px; font-size: 20px; display: flex; align-items: center; gap: 10px; }
            .table-wrapper { overflow-x: auto; }
            table { width: 100%; border-collapse: collapse; }
            table thead { background: #f5f5f5; }
            table th { padding: 12px; text-align: left; font-weight: 600; color: #333; border-bottom: 2px solid #ddd; }
            table td { padding: 12px; border-bottom: 1px solid #eee; }
            table tbody tr:hover { background: #f9f9f9; }
            .status-badge { display: inline-flex; align-items: center; gap: 6px; padding: 6px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; }
            .status-badge.rejected { background: #f8d7da; color: #721c24; }
            .status-badge.failed { background: #fff3cd; color: #856404; }
            .loan-id { font-weight: 600; color: #667eea; }
            .emp-name { font-weight: 500; color: #333; display: block; max-width: 200px; word-break: break-word; }
            .amount { font-weight: 600; color: #28a745; text-align: right; }
            .timestamp { color: #999; font-size: 12px; }
            .footer { background: white; padding: 20px 30px; border-radius: 0 0 10px 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); text-align: center; color: #666; font-size: 12px; }
            .reason-tooltip { cursor: help; color: #667eea; text-decoration: underline; }
            .modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5); }
            .modal.active { display: flex; align-items: center; justify-content: center; }
            .modal-content { background: white; padding: 30px; border-radius: 10px; max-width: 600px; max-height: 80vh; overflow-y: auto; }
            .modal-close { float: right; font-size: 28px; font-weight: bold; cursor: pointer; color: #999; }
            .modal-close:hover { color: #333; }
            .search-box { display: flex; gap: 10px; margin-bottom: 20px; }
            .search-box input { flex: 1; padding: 10px; border: 1px solid #ddd; border-radius: 6px; }
            .search-box button { padding: 10px 20px; background: #667eea; color: white; border: none; border-radius: 6px; cursor: pointer; }
            .search-box button:hover { background: #764ba2; }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="header" style="justify-content: space-between;">
                <div style="display: flex; align-items: center; gap: 20px;">
                    <div class="icon"><i class="fas fa-ban"></i></div>
                    <div>
                        <h1>Loan Rejection Report</h1>
                        <p style="color: #999; margin-top: 5px;">Auto-Rejected Loan Requests (<?php echo $days_threshold; ?> days threshold)</p>
                    </div>
                </div>
                <div style="text-align: right;">
                    <button onclick="confirmClearHistory()" style="background: #dc3545; color: white; border: none; padding: 10px 20px; border-radius: 6px; cursor: pointer; font-weight: 600;">
                        <i class="fas fa-trash"></i> Clear History
                    </button>
                </div>
            </div>
            <?php if (isset($cleared) && $cleared): ?>
            <div style="background: #d4edda; border: 1px solid #c3e6cb; color: #155724; padding: 15px; margin: 20px auto; border-radius: 6px; max-width: 1400px; text-align: center;">
                <i class="fas fa-check-circle"></i> History cleared successfully! All previous rejection records have been deleted.
            </div>
            <?php endif; ?>

            <div class="summary">
                <div class="summary-card total">
                    <div class="icon"><i class="fas fa-list"></i></div>
                    <div class="number"><?php echo (int)$total_stale; ?></div>
                    <div class="label">Total Stale Requests</div>
                </div>
                <div class="summary-card rejected">
                    <div class="icon"><i class="fas fa-check-circle"></i></div>
                    <div class="number"><?php echo (int)$rejected_count; ?></div>
                    <div class="label">Successfully Rejected</div>
                </div>
                <div class="summary-card failed">
                    <div class="icon"><i class="fas fa-exclamation-circle"></i></div>
                    <div class="number"><?php echo (int)$failed_count; ?></div>
                    <div class="label">Failed Rejections</div>
                </div>
            </div>

            <div class="details-section">
                <h2 style="display:flex; align-items:center; justify-content:space-between; gap:12px; flex-wrap:wrap;">
                    <span style="display:flex; align-items:center; gap:10px;"><i class="fas fa-table"></i> Rejection Details (<?php echo count($rejections_log); ?> records)</span>
                    <span style="display:flex; align-items:center; gap:8px;">
                        <label for="search-field" style="font-weight:600; color:#333;">Search:</label>
                        <input id="search-field" type="text" placeholder="INV / Emp ID / Name / Supervisor" style="padding:8px 10px; border:1px solid #ccc; border-radius:6px; min-width:220px;" />
                    </span>
                </h2>
                <div class="table-wrapper">
                    <?php if (count($rejections_log) > 0): ?>
                        <table>
                            <thead>
                                <tr>
                                    <th style="width: 8%;">Loan ID</th>
                                    <th style="width: 11%;">Invoice No.</th>
                                    <th style="width: 8%;">Emp ID</th>
                                    <th style="width: 14%;">Employee Name</th>
                                    <th style="width: 9%;">Loan Type</th>
                                    <th style="width: 9%;">Amount (SAR)</th>
                                    <th style="width: 14%;">Supervisor Name</th>
                                    <th style="width: 8%;">Days Pending</th>
                                    <th style="width: 22%;">Rejection Reason</th>
                                    <th style="width: 10%;">Created At</th>
                                    <th style="width: 12%;">Rejected At</th>
                                    <th style="width: 7%;">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($rejections_log as $log): ?>
                                    <tr>
                                        <td><span class="loan-id"><?php echo htmlspecialchars($log['loan_id']); ?></span></td>
                                        <td><span class="loan-id"><?php echo htmlspecialchars($log['inv_no']); ?></span></td>
                                        <td><?php echo htmlspecialchars($log['emp_id']); ?></td>
                                        <td><span class="emp-name"><?php echo htmlspecialchars($log['emp_name']); ?></span></td>
                                        <td><?php echo htmlspecialchars(ucwords(str_replace('_', ' ', $log['loan_type']))); ?></td>
                                        <td><span class="amount"><?php echo number_format($log['loan_amount'], 2); ?></span></td>
                                        <td><?php echo htmlspecialchars($log['supervisor_name'] ?? 'N/A'); ?></td>
                                        <td style="text-align:center;"><strong><?php echo $log['days_pending']; ?></strong></td>
                                        <td>
                                            <small style="color:#666; max-width:200px; display:block; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;" title="<?php echo htmlspecialchars($log['rejection_reason']); ?>">
                                                <?php echo htmlspecialchars($log['rejection_reason']); ?>
                                            </small>
                                        </td>
                                        <td><span class="timestamp"><?php echo date('d M Y H:i', strtotime($log['created_at'])); ?></span></td>
                                        <td><span class="timestamp"><?php echo date('d M Y H:i', strtotime($log['rejected_at'])); ?></span></td>
                                        <td>
                                            <span class="status-badge <?php echo ($log['status'] === 'successfully_rejected') ? 'rejected' : 'failed'; ?>">
                                                <i class="<?php echo ($log['status'] === 'successfully_rejected') ? 'fas fa-ban' : 'fas fa-exclamation'; ?>"></i>
                                                <?php echo ($log['status'] === 'successfully_rejected') ? 'OK' : 'Err'; ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <div style="text-align:center; color:#999; padding:40px;">
                            <i class="fas fa-inbox" style="font-size:48px; opacity:0.5;"></i>
                            <p>No rejections to display</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="footer">
                <p>Report Generated: <?php echo htmlspecialchars($report_timestamp); ?></p>
                <p style="margin-top: 10px; font-size: 11px;">Auto-rejection threshold: <?php echo $days_threshold; ?> days</p>
            </div>
        </div>

        <script>
            function confirmClearHistory() {
                if (confirm('⚠️ WARNING: This will permanently delete ALL rejection history records. This action cannot be undone.\n\nAre you sure you want to clear the history?')) {
                    if (confirm('Please confirm again by clicking OK to delete all records.')) {
                        window.location.href = './loan_rejection_report.php?action=clear_history&confirm=yes';
                    }
                }
            }

            (function() {
                const input = document.getElementById('search-field');
                const rows = Array.from(document.querySelectorAll('tbody tr'));

                if (!input || rows.length === 0) return;

                const filter = () => {
                    const term = input.value.trim().toLowerCase();
                    rows.forEach(row => {
                        const cells = row.querySelectorAll('td');
                        const searchText = [
                            cells[1]?.textContent || '',
                            cells[2]?.textContent || '',
                            cells[3]?.textContent || '',
                            cells[6]?.textContent || ''
                        ].join(' ').toLowerCase();
                        row.style.display = term === '' || searchText.includes(term) ? '' : 'none';
                    });
                };

                input.addEventListener('input', filter);
            })();
        </script>
    </body>
    </html>
    <?php
}

// Controller: load JSON and render
if (file_exists($report_file)) {
    $saved_report = json_decode(file_get_contents($report_file), true);
    if ($saved_report && is_array($saved_report)) {
        display_gui_report(
            (int)($saved_report['rejected_count'] ?? 0),
            (int)($saved_report['failed_count'] ?? 0),
            (int)($saved_report['total_stale'] ?? 0),
            (array)($saved_report['rejections_log'] ?? []),
            $saved_report['timestamp'] ?? null,
            (int)($saved_report['days_threshold'] ?? 3)
        );
        exit;
    }
}

show_no_report_available();
exit;
