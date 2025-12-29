<?php
/**
 * Vacation Balance Update Report Viewer
 *
 * Reads the last saved JSON report from cron_update_vacation_balances.php
 * and renders a GUI page for viewing.
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

date_default_timezone_set('Asia/Riyadh');

$report_file = __DIR__ . '/cron_logs/last_vacation_update_report.json';

function show_no_report_available() {
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Vacation Balance Update - No Report</title>
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
            <p>The vacation balance update cron job has not been run yet, or no saved report exists.</p>
            <p>Please wait for the scheduled cron job to run, or run it manually from the command line.</p>
            <p style="font-size: 12px; color: #999; margin-top: 30px;">Report Time: <?php echo date('Y-m-d H:i:s'); ?></p>
        </div>
    </body>
    </html>
    <?php
}

function display_gui_report($updated_count, $changed_count, $error_count, $total_employees, $updates_log, $report_timestamp = null) {
    if ($report_timestamp === null) {
        $report_timestamp = date('Y-m-d H:i:s');
    }
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Vacation Balance Update - Cron Report</title>
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
        <style>
            * { margin: 0; padding: 0; box-sizing: border-box; }
            body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; padding: 20px; }
            .container { max-width: 1200px; margin: 0 auto; }
            .header { background: white; padding: 30px; border-radius: 10px 10px 0 0; box-shadow: 0 2px 10px rgba(0,0,0,0.1); display: flex; align-items: center; gap: 20px; }
            .header h1 { color: #333; font-size: 28px; }
            .header .icon { font-size: 40px; color: #667eea; }
            .summary { background: white; padding: 30px; display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
            .summary-card { padding: 20px; border-radius: 8px; text-align: center; display: flex; flex-direction: column; align-items: center; gap: 10px; }
            .summary-card.total { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; }
            .summary-card.updated { background: linear-gradient(135deg, #84fab0 0%, #8fd3f4 100%); color: white; }
            .summary-card.changed { background: linear-gradient(135deg, #fa709a 0%, #fee140 100%); color: white; }
            .summary-card.errors { background: linear-gradient(135deg, #ff6b6b 0%, #ff8787 100%); color: white; }
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
            .status-badge.success { background: #d4edda; color: #155724; }
            .status-badge.changed { background: #fff3cd; color: #856404; }
            .emp-id { font-weight: 600; color: #667eea; }
            .emp-name { font-weight: 500; color: #333; display: block; max-width: 200px; word-break: break-word; }
            .value-container { display: flex; align-items: center; gap: 10px; justify-content: center; }
            .old-value { padding: 6px 12px; background: #f0f0f0; border-radius: 4px; font-family: 'Courier New', monospace; }
            .arrow { color: #999; font-size: 16px; }
            .new-value { padding: 6px 12px; background: #e8f5e9; border-radius: 4px; font-family: 'Courier New', monospace; font-weight: 600; color: #2e7d32; }
            .timestamp { color: #999; font-size: 12px; }
            .footer { background: white; padding: 20px 30px; border-radius: 0 0 10px 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); text-align: center; color: #666; font-size: 12px; }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="header">
                <div class="icon"><i class="fas fa-calendar-check"></i></div>
                <div>
                    <h1>Vacation Balance Update Report</h1>
                    <p style="color: #999; margin-top: 5px;">Saved Cron Report</p>
                </div>
            </div>

            <div class="summary">
                <div class="summary-card total">
                    <div class="icon"><i class="fas fa-users"></i></div>
                    <div class="number"><?php echo (int)$total_employees; ?></div>
                    <div class="label">Total Employees</div>
                </div>
                <div class="summary-card updated">
                    <div class="icon"><i class="fas fa-check-circle"></i></div>
                    <div class="number"><?php echo (int)$updated_count; ?></div>
                    <div class="label">Records Updated</div>
                </div>
                <div class="summary-card changed">
                    <div class="icon"><i class="fas fa-exchange-alt"></i></div>
                    <div class="number"><?php echo (int)$changed_count; ?></div>
                    <div class="label">Balances Changed</div>
                </div>
                <div class="summary-card errors">
                    <div class="icon"><i class="fas fa-exclamation-circle"></i></div>
                    <div class="number"><?php echo (int)$error_count; ?></div>
                    <div class="label">Errors</div>
                </div>
            </div>

            <div class="details-section">
                <h2 style="display:flex; align-items:center; justify-content:space-between; gap:12px; flex-wrap:wrap;">
                    <span style="display:flex; align-items:center; gap:10px;"><i class="fas fa-table"></i> Update Details (<?php echo count($updates_log); ?> records)</span>
                    <span style="display:flex; align-items:center; gap:8px;">
                        <label for="search-id" style="font-weight:600; color:#333;">Search ID:</label>
                        <input id="search-id" type="text" placeholder="Enter Employee ID" style="padding:8px 10px; border:1px solid #ccc; border-radius:6px; min-width:180px;" />
                    </span>
                </h2>
                <div class="table-wrapper">
                    <?php if (count($updates_log) > 0): ?>
                        <table>
                            <thead>
                                <tr>
                                    <th style="width: 12%;">Employee ID</th>
                                    <th style="width: 20%;">Employee Name</th>
                                    <th style="width: 18%;">Old Balance</th>
                                    <th style="width: 18%;">New Balance</th>
                                    <th style="width: 16%;">Status</th>
                                    <th style="width: 16%;">Timestamp</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($updates_log as $log): ?>
                                    <?php $is_changed = abs($log['old_value'] - $log['new_value']) > 0.001; ?>
                                    <tr>
                                        <td><span class="emp-id"><?php echo htmlspecialchars($log['emp_id']); ?></span></td>
                                        <td><span class="emp-name"><?php echo htmlspecialchars($log['emp_name']); ?></span></td>
                                        <td><div class="value-container"><span class="old-value"><?php echo number_format((float)$log['old_value'], 2); ?></span></div></td>
                                        <td><div class="value-container"><span class="arrow"><i class="fas fa-arrow-right"></i></span><span class="new-value"><?php echo number_format((float)$log['new_value'], 2); ?></span></div></td>
                                        <td>
                                            <span class="status-badge <?php echo $is_changed ? 'changed' : 'success'; ?>">
                                                <i class="<?php echo $is_changed ? 'fas fa-exchange-alt' : 'fas fa-sync-alt'; ?>"></i>
                                                <?php echo $is_changed ? 'Changed' : 'Refreshed'; ?>
                                            </span>
                                        </td>
                                        <td><span class="timestamp"><?php echo htmlspecialchars($log['timestamp']); ?></span></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <div style="text-align:center; color:#999; padding:40px;">
                            <i class="fas fa-inbox" style="font-size:48px; opacity:0.5;"></i>
                            <p>No updates to display</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="footer">
                <p>Report Generated: <?php echo htmlspecialchars($report_timestamp); ?></p>
            </div>
        </div>
        <script>
            (function() {
                const input = document.getElementById('search-id');
                const rows = Array.from(document.querySelectorAll('tbody tr'));

                if (!input || rows.length === 0) return;

                const filter = () => {
                    const term = input.value.trim().toLowerCase();
                    rows.forEach(row => {
                        const idCell = row.querySelector('.emp-id');
                        if (!idCell) return;
                        const text = idCell.textContent.trim().toLowerCase();
                        row.style.display = term === '' || text.includes(term) ? '' : 'none';
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
            (int)($saved_report['updated_count'] ?? 0),
            (int)($saved_report['changed_count'] ?? 0),
            (int)($saved_report['error_count'] ?? 0),
            (int)($saved_report['total_employees'] ?? 0),
            (array)($saved_report['updates_log'] ?? []),
            $saved_report['timestamp'] ?? null
        );
        exit;
    }
}

show_no_report_available();
exit;
