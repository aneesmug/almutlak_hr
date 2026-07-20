<?php
/**
 * Vacation Balance History Viewer
 * 
 * Allows viewing and auditing of vacation balance changes over time
 * Helps identify calculation issues and mismatches
 */
require_once __DIR__ . '/includes/session_check.php';
require_once __DIR__ . '/includes/special_access_helper.php';

// Allow: System admin, administrator, HR, or anyone explicitly granted this special access
$can_view_history = (
    $is_system_admin
    || $user_type == 'administrator'
    || $user_type == 'hr'
    || user_has_special_access($conDB, $empid ?? '', 'view_vacation_balance_history', $user_role ?? '', $user_type ?? '', $is_system_admin ?? false)
);

if (!$can_view_history) {
    http_response_code(403);
    die("Access Denied: Only administrators can view balance history");
}

error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
date_default_timezone_set('Asia/Riyadh');

// Get filter parameters
// NOTE: date range defaults to empty (no restriction) rather than "today" -- the balance
// snapshot cron does not necessarily run every single day, so defaulting to today's date
// could show zero rows even though history exists. The query below already limits to the
// latest 500 rows ordered by snapshot_date DESC, so an empty default still shows the most
// recent activity instead of an empty table.
$filter_emp_id = isset($_GET['emp_id']) ? trim($_GET['emp_id']) : '';
$filter_date_from = isset($_GET['date_from']) ? trim($_GET['date_from']) : '';
$filter_date_to = isset($_GET['date_to']) ? trim($_GET['date_to']) : '';
$filter_balance_changed = isset($_GET['balance_changed']) ? (int)$_GET['balance_changed'] : 1; // Default: 1 = changed balances only
$show_errors = isset($_GET['show_errors']) ? (int)$_GET['show_errors'] : 0;

// Build query
$where_clauses = [];
$params = [];
$types = '';

if (!empty($filter_emp_id)) {
    $where_clauses[] = "evbh.emp_id = ?";
    $params[] = $filter_emp_id;
    $types .= 's';
}

if (!empty($filter_date_from)) {
    $where_clauses[] = "evbh.snapshot_date >= ?";
    $params[] = $filter_date_from;
    $types .= 's';
}

if (!empty($filter_date_to)) {
    $where_clauses[] = "evbh.snapshot_date <= ?";
    $params[] = $filter_date_to;
    $types .= 's';
}

if ($filter_balance_changed >= 0) {
    $where_clauses[] = "evbh.balance_changed = ?";
    $params[] = $filter_balance_changed;
    $types .= 'i';
}

if ($show_errors) {
    $where_clauses[] = "(evbh.new_available_balance < 0 OR evbh.calculation_status = 'error')";
}

$where_clause = count($where_clauses) > 0 ? 'WHERE ' . implode(' AND ', $where_clauses) : '';

$query = "SELECT 
            evbh.id,
            evbh.emp_id,
            e.name AS emp_name,
            evbh.snapshot_date,
            evbh.old_available_balance,
            evbh.new_available_balance,
            evbh.new_used_days,
            evbh.new_remaining_balance,
            evbh.carryover_days,
            evbh.total_days,
            evbh.period_start,
            evbh.period_end,
            evbh.balance_changed,
            evbh.change_amount,
            evbh.calculation_status,
            evbh.notes,
            evbh.snapshot_time
          FROM emp_vacation_balance_history evbh
          LEFT JOIN employees e ON evbh.emp_id = e.emp_id
          $where_clause
          ORDER BY evbh.snapshot_date DESC, evbh.emp_id
          LIMIT 500";

$stmt = mysqli_prepare($conDB, $query);
if ($types && count($params) > 0) {
    mysqli_stmt_bind_param($stmt, $types, ...$params);
}
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$history_records = [];
while ($row = mysqli_fetch_assoc($result)) {
    $history_records[] = $row;
}
mysqli_stmt_close($stmt);

// Statistics
$stats_query = "SELECT 
    COUNT(*) AS total_records,
    COUNT(DISTINCT emp_id) AS unique_employees,
    COUNT(DISTINCT snapshot_date) AS days_tracked,
    SUM(CASE WHEN balance_changed THEN 1 ELSE 0 END) AS records_changed,
    SUM(CASE WHEN new_available_balance < 0 THEN 1 ELSE 0 END) AS negative_balances,
    SUM(CASE WHEN calculation_status = 'error' THEN 1 ELSE 0 END) AS error_count
  FROM emp_vacation_balance_history";

$stats_stmt = mysqli_prepare($conDB, $stats_query);
mysqli_stmt_execute($stats_stmt);
$stats = mysqli_stmt_get_result($stats_stmt)->fetch_assoc();
mysqli_stmt_close($stats_stmt);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vacation Balance History</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); 
            min-height: 100vh; 
            padding: 20px; 
        }
        .container { max-width: 1400px; margin: 0 auto; }
        .header { background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); margin-bottom: 20px; }
        .header h1 { color: #333; font-size: 28px; display: flex; align-items: center; gap: 15px; }
        .header .icon { color: #667eea; font-size: 32px; }
        .filters { background: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); margin-bottom: 20px; }
        .filter-row { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-bottom: 15px; }
        .filter-group { display: flex; flex-direction: column; gap: 5px; }
        .filter-group label { font-weight: 600; color: #333; font-size: 12px; }
        .filter-group input, .filter-group select { padding: 10px; border: 1px solid #ddd; border-radius: 6px; font-size: 13px; }
        .filter-buttons { display: flex; gap: 10px; }
        .btn { padding: 10px 20px; border: none; border-radius: 6px; cursor: pointer; font-weight: 600; transition: all 0.3s; }
        .btn-primary { background: #667eea; color: white; }
        .btn-primary:hover { background: #764ba2; }
        .btn-secondary { background: #f0f0f0; color: #333; }
        .btn-secondary:hover { background: #e0e0e0; }
        .stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 15px; margin-bottom: 20px; }
        .stat-card { background: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); text-align: center; }
        .stat-card .number { font-size: 28px; font-weight: bold; color: #667eea; }
        .stat-card .label { font-size: 12px; color: #999; margin-top: 5px; }
        .table-wrapper { background: white; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); overflow: hidden; }
        .table-header { padding: 20px; border-bottom: 2px solid #eee; background: #f9f9f9; display: flex; justify-content: space-between; align-items: center; }
        .table-header h2 { color: #333; font-size: 18px; }
        table { width: 100%; border-collapse: collapse; }
        table thead { background: #f5f5f5; }
        table th { padding: 12px 15px; text-align: left; font-weight: 600; color: #333; border-bottom: 2px solid #ddd; font-size: 12px; }
        table td { padding: 12px 15px; border-bottom: 1px solid #eee; font-size: 12px; }
        table tbody tr:hover { background: #f9f9f9; }
        .emp-id { color: #667eea; font-weight: 600; }
        .emp-name { color: #333; }
        .status-badge { display: inline-block; padding: 4px 10px; border-radius: 20px; font-size: 10px; font-weight: 600; }
        .status-success { background: #d4edda; color: #155724; }
        .status-changed { background: #fff3cd; color: #856404; }
        .status-error { background: #f8d7da; color: #721c24; }
        .balance-negative { color: #d32f2f; font-weight: 600; }
        .balance-positive { color: #388e3c; }
        .balance-unchanged { color: #999; }
        .value-cell { font-family: 'Courier New', monospace; text-align: right; }
        .change-up { color: #388e3c; }
        .change-down { color: #d32f2f; }
        .pagination { display: flex; gap: 10px; justify-content: center; margin-top: 20px; padding: 20px; }
        .no-data { text-align: center; padding: 40px; color: #999; }
        @media print {
            body { background: white; }
            .filters, .header { box-shadow: none; }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>
                <i class="fas fa-history icon"></i>
                Vacation Balance History
            </h1>
            <p style="color: #999; margin-top: 5px;">Track and audit daily vacation balance changes</p>
        </div>

        <!-- Statistics -->
        <div class="stats">
            <div class="stat-card">
                <div class="number"><?php echo (int)($stats['total_records'] ?? 0); ?></div>
                <div class="label">Total Records</div>
            </div>
            <div class="stat-card">
                <div class="number"><?php echo (int)($stats['unique_employees'] ?? 0); ?></div>
                <div class="label">Unique Employees</div>
            </div>
            <div class="stat-card">
                <div class="number"><?php echo (int)($stats['days_tracked'] ?? 0); ?></div>
                <div class="label">Days Tracked</div>
            </div>
            <div class="stat-card">
                <div class="number"><?php echo (int)($stats['records_changed'] ?? 0); ?></div>
                <div class="label">Balance Changes</div>
            </div>
            <div class="stat-card">
                <div class="number" style="color: #d32f2f;"><?php echo (int)($stats['negative_balances'] ?? 0); ?></div>
                <div class="label">Negative Balances</div>
            </div>
            <div class="stat-card">
                <div class="number" style="color: #d32f2f;"><?php echo (int)($stats['error_count'] ?? 0); ?></div>
                <div class="label">Errors</div>
            </div>
        </div>

        <!-- Filters -->
        <div class="filters">
            <form method="GET" style="display: contents;">
                <div class="filter-row">
                    <div class="filter-group">
                        <label>Employee ID</label>
                        <input type="text" name="emp_id" placeholder="e.g., 1061" value="<?php echo htmlspecialchars($filter_emp_id); ?>">
                    </div>
                    <div class="filter-group">
                        <label>From Date</label>
                        <input type="date" name="date_from" value="<?php echo htmlspecialchars($filter_date_from); ?>">
                    </div>
                    <div class="filter-group">
                        <label>To Date</label>
                        <input type="date" name="date_to" value="<?php echo htmlspecialchars($filter_date_to); ?>">
                    </div>
                    <div class="filter-group">
                        <label>Balance Status</label>
                        <select name="balance_changed">
                            <option value="-1">All</option>
                            <option value="1" <?php echo $filter_balance_changed === 1 ? 'selected' : ''; ?>>Changed</option>
                            <option value="0" <?php echo $filter_balance_changed === 0 ? 'selected' : ''; ?>>Unchanged</option>
                        </select>
                    </div>
                </div>
                <div class="filter-buttons">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search"></i> Filter
                    </button>
                    <a href="vacation_balance_history.php" class="btn btn-secondary">
                        <i class="fas fa-redo"></i> Reset
                    </a>
                    <label style="display: flex; align-items: center; gap: 8px; margin-left: auto; padding: 10px 0;">
                        <input type="checkbox" name="show_errors" value="1" <?php echo $show_errors ? 'checked' : ''; ?>> 
                        <span style="font-size: 13px;">Show Errors Only</span>
                    </label>
                </div>
            </form>
        </div>

        <!-- History Table -->
        <div class="table-wrapper">
            <div class="table-header">
                <h2>Balance History Records (Latest <?php echo count($history_records); ?>)</h2>
            </div>
            
            <?php if (count($history_records) > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Employee ID</th>
                            <th>Employee Name</th>
                            <th>Old Balance</th>
                            <th>New Balance</th>
                            <th>Change</th>
                            <th>Status</th>
                            <th>Earned</th>
                            <th>Used</th>
                            <th>Carryover</th>
                            <th>Period Start</th>
                            <th>Period End</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($history_records as $record): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($record['snapshot_date']); ?></strong></td>
                                <td><span class="emp-id"><?php echo htmlspecialchars($record['emp_id']); ?></span></td>
                                <td><span class="emp-name"><?php echo htmlspecialchars($record['emp_name'] ?? 'N/A'); ?></span></td>
                                <td class="value-cell"><?php echo number_format((float)$record['old_available_balance'], 2); ?></td>
                                <td class="value-cell <?php echo ((float)$record['new_available_balance'] < 0) ? 'balance-negative' : 'balance-positive'; ?>">
                                    <?php echo number_format((float)$record['new_available_balance'], 2); ?>
                                </td>
                                <td class="value-cell <?php echo ((float)$record['change_amount'] > 0) ? 'change-up' : (((float)$record['change_amount'] < 0) ? 'change-down' : 'balance-unchanged'); ?>">
                                    <?php echo $record['balance_changed'] ? number_format((float)$record['change_amount'], 2) : '—'; ?>
                                </td>
                                <td>
                                    <?php 
                                    $status_class = 'status-' . ($record['calculation_status'] ?? 'success');
                                    if ((float)$record['new_available_balance'] < 0) {
                                        $status_class = 'status-error';
                                        $status_text = '⚠ Negative';
                                    } else {
                                        $status_text = $record['balance_changed'] ? 'Changed' : 'Refreshed';
                                    }
                                    ?>
                                    <span class="status-badge <?php echo $status_class; ?>">
                                        <?php echo htmlspecialchars($status_text); ?>
                                    </span>
                                </td>
                                <td class="value-cell"><?php echo $record['new_used_days'] ? number_format((float)$record['new_used_days'], 2) : '—'; ?></td>
                                <td class="value-cell"><?php echo $record['new_remaining_balance'] ? number_format((float)$record['new_remaining_balance'], 2) : '—'; ?></td>
                                <td class="value-cell"><?php echo $record['carryover_days'] ? number_format((float)$record['carryover_days'], 2) : '—'; ?></td>
                                <td><?php echo $record['period_start'] ? htmlspecialchars($record['period_start']) : '—'; ?></td>
                                <td><?php echo $record['period_end'] ? htmlspecialchars($record['period_end']) : '—'; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="no-data">
                    <i class="fas fa-inbox" style="font-size: 48px; opacity: 0.3;"></i>
                    <p style="margin-top: 15px;">No history records found for the selected filters.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
