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

// Shared <head> + brand bar for the standalone report (new GUI: assets/css/smart_request.css)
function lrr_page_open($title) {
    global $conDB;
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?php echo htmlspecialchars($title); ?></title>
        <link rel="shortcut icon" href="<?php echo get_setting($conDB, 'favicon'); ?>">
        <link href="assets/css/bootstrap.min.css" rel="stylesheet" type="text/css" />
        <link href="assets/css/icons.css" rel="stylesheet" type="text/css" />
        <link href="assets/css/smart_request.css?v=<?php echo @filemtime(__DIR__ . '/assets/css/smart_request.css'); ?>" rel="stylesheet" type="text/css" />
        <style>
            .lrr-stats { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 12px; margin-bottom: 20px; }
            @media (max-width: 767px) { .lrr-stats { grid-template-columns: 1fr; } }
            .lrr-stats .sr-stat { background: var(--sr-surface); }
            .lrr-stats .sr-stat-value { font-size: 24px; }
            .lrr-reason { max-width: 260px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; display: block; color: var(--sr-muted); font-size: 12px; }
            .lrr-foot { padding: 12px 18px; border-top: 1px solid var(--sr-border); font-size: 12px; color: var(--sr-muted); display: flex; flex-wrap: wrap; gap: 6px 18px; }
        </style>
    </head>
    <body class="sr-standalone">
        <div class="sr-page sr-standalone-wrap">
            <div class="sr-standalone-brand">
                <a href="dashboard.php"><img src="<?php echo get_setting($conDB, 'logo'); ?>" alt=""></a>
            </div>
    <?php
}

function show_no_report_available() {
    lrr_page_open('Loan Rejection Report - No Report');
    ?>
            <div class="sr-head">
                <div>
                    <h1>Loan Rejection Report</h1>
                    <p>Auto-rejected loan requests</p>
                </div>
            </div>
            <div class="sr-card">
                <div class="sr-empty">
                    <i class="mdi mdi-inbox"></i>
                    <strong>No Report Available</strong><br>
                    The loan rejection job has not been run yet, or no saved report exists.<br>
                    Please wait for the scheduled job to run, or run it manually from the command line.
                </div>
                <div class="lrr-foot">Report Time: <?php echo date('Y-m-d H:i:s'); ?></div>
            </div>
        </div>
    </body>
    </html>
    <?php
}

function display_gui_report($rejected_count, $failed_count, $total_stale, $rejections_log, $report_timestamp = null, $days_threshold = 3) {
    global $cleared;
    if ($report_timestamp === null) {
        $report_timestamp = date('Y-m-d H:i:s');
    }
    lrr_page_open('Loan Rejection Report - Auto-Rejected Requests');
    ?>
            <div class="sr-head">
                <div>
                    <h1>Loan Rejection Report</h1>
                    <p>Auto-Rejected Loan Requests (<?php echo (int)$days_threshold; ?> days threshold)</p>
                </div>
                <div class="sr-head-actions">
                    <button type="button" class="sr-btn sr-btn-danger" onclick="confirmClearHistory()"><i class="mdi mdi-delete"></i> Clear History</button>
                </div>
            </div>

            <?php if (!empty($cleared)): ?>
            <div class="sr-notice tone-green">
                <i class="mdi mdi-check-circle-outline"></i>
                <div>History cleared successfully! All previous rejection records have been deleted.</div>
            </div>
            <?php endif; ?>

            <div class="lrr-stats">
                <div class="sr-stat is-sky">
                    <div class="sr-stat-label">Total Stale Requests <i class="mdi mdi-format-list-bulleted"></i></div>
                    <div class="sr-stat-value"><?php echo (int)$total_stale; ?></div>
                </div>
                <div class="sr-stat is-green">
                    <div class="sr-stat-label">Successfully Rejected <i class="mdi mdi-check-circle-outline"></i></div>
                    <div class="sr-stat-value"><?php echo (int)$rejected_count; ?></div>
                </div>
                <div class="sr-stat is-amber">
                    <div class="sr-stat-label">Failed Rejections <i class="mdi mdi-alert-circle-outline"></i></div>
                    <div class="sr-stat-value"><?php echo (int)$failed_count; ?></div>
                </div>
            </div>

            <div class="sr-card">
                <div class="sr-toolbar">
                    <h2 class="sr-card-title" style="margin-inline-end: auto;"><i class="mdi mdi-table"></i> Rejection Details <span class="sr-count"><?php echo count($rejections_log); ?></span></h2>
                    <div class="sr-search">
                        <i class="mdi mdi-magnify"></i>
                        <input id="search-field" type="search" placeholder="INV / Emp ID / Name / Supervisor" autocomplete="off">
                    </div>
                </div>
                <?php if (count($rejections_log) > 0): ?>
                    <div class="sr-table-wrap sr-table-scroll">
                        <table class="sr-table">
                            <thead>
                                <tr>
                                    <th>Loan</th>
                                    <th>Employee</th>
                                    <th>Loan Type</th>
                                    <th class="text-right">Amount (SAR)</th>
                                    <th>Supervisor</th>
                                    <th class="text-center">Days Pending</th>
                                    <th>Rejection Reason</th>
                                    <th>Created / Rejected</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($rejections_log as $log):
                                    $ok = (($log['status'] ?? '') === 'successfully_rejected');
                                    $name = (string)($log['emp_name'] ?? '');
                                    $initials = strtoupper(implode('', array_map(function ($w) { return mb_substr($w, 0, 1); }, array_slice(preg_split('/\s+/', trim($name)), 0, 2))));
                                ?>
                                    <tr data-search="<?php echo htmlspecialchars(strtolower(($log['inv_no'] ?? '') . ' ' . ($log['emp_id'] ?? '') . ' ' . $name . ' ' . ($log['supervisor_name'] ?? ''))); ?>">
                                        <td>
                                            <span class="sr-chip sr-mono"><?php echo htmlspecialchars($log['inv_no'] ?? ''); ?></span>
                                            <span class="sr-cell-sub">#<?php echo htmlspecialchars($log['loan_id'] ?? ''); ?></span>
                                        </td>
                                        <td>
                                            <div class="sr-person">
                                                <span class="sr-avatar sr-avatar-sm"><?php echo htmlspecialchars($initials); ?></span>
                                                <div>
                                                    <span class="sr-person-name"><?php echo htmlspecialchars($name); ?></span>
                                                    <span class="sr-cell-sub"><?php echo htmlspecialchars($log['emp_id'] ?? ''); ?></span>
                                                </div>
                                            </div>
                                        </td>
                                        <td><?php echo htmlspecialchars(ucwords(str_replace('_', ' ', $log['loan_type'] ?? ''))); ?></td>
                                        <td class="text-right"><span class="sr-money"><?php echo number_format((float)($log['loan_amount'] ?? 0), 2); ?></span></td>
                                        <td><?php echo htmlspecialchars($log['supervisor_name'] ?? 'N/A'); ?></td>
                                        <td class="text-center"><span class="sr-pill sr-pill-xs tone-amber"><?php echo (int)($log['days_pending'] ?? 0); ?></span></td>
                                        <td><span class="lrr-reason" title="<?php echo htmlspecialchars($log['rejection_reason'] ?? ''); ?>"><?php echo htmlspecialchars($log['rejection_reason'] ?? ''); ?></span></td>
                                        <td class="sr-date">
                                            <?php echo format_safe_date($log['created_at'] ?? null, 'd M Y H:i'); ?>
                                            <small><?php echo format_safe_date($log['rejected_at'] ?? null, 'd M Y H:i'); ?></small>
                                        </td>
                                        <td>
                                            <span class="sr-pill sr-pill-xs <?php echo $ok ? 'tone-red' : 'tone-amber'; ?>">
                                                <i class="mdi <?php echo $ok ? 'mdi-cancel' : 'mdi-alert'; ?>"></i>
                                                <?php echo $ok ? 'Rejected' : 'Error'; ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        <div class="sr-empty" id="lrrNoMatch" style="display:none;"><i class="mdi mdi-magnify"></i>No matching records found</div>
                    </div>
                <?php else: ?>
                    <div class="sr-empty"><i class="mdi mdi-inbox"></i>No rejections to display</div>
                <?php endif; ?>
                <div class="lrr-foot">
                    <span>Report Generated: <?php echo htmlspecialchars($report_timestamp); ?></span>
                    <span>Auto-rejection threshold: <?php echo (int)$days_threshold; ?> days</span>
                </div>
            </div>
        </div>

        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
        <script>
            function confirmClearHistory() {
                Swal.fire({
                    icon: 'warning',
                    title: 'Clear history?',
                    text: 'This will permanently delete ALL rejection history records. This action cannot be undone.',
                    showCancelButton: true,
                    confirmButtonColor: '#dc2626',
                    confirmButtonText: 'Yes, clear it',
                    allowOutsideClick: false
                }).then(function (r) {
                    if (r.isConfirmed) {
                        window.location.href = './loan_rejection_report.php?action=clear_history&confirm=yes';
                    }
                });
            }

            (function() {
                const input = document.getElementById('search-field');
                const rows = Array.from(document.querySelectorAll('tbody tr[data-search]'));
                const none = document.getElementById('lrrNoMatch');

                if (!input || rows.length === 0) return;

                input.addEventListener('input', function () {
                    const term = input.value.trim().toLowerCase();
                    let shown = 0;
                    rows.forEach(row => {
                        const hit = term === '' || row.dataset.search.includes(term);
                        row.style.display = hit ? '' : 'none';
                        if (hit) shown++;
                    });
                    if (none) none.style.display = shown ? 'none' : '';
                });
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
