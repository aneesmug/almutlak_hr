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
    <link rel="shortcut icon" href="<?= get_setting($conDB, 'favicon') ?>">
    <link href="assets/css/bootstrap.min.css" rel="stylesheet" type="text/css" />
    <link href="assets/css/icons.css" rel="stylesheet" type="text/css" />
    <link href="assets/css/smart_request.css?v=<?= @filemtime(__DIR__ . '/assets/css/smart_request.css') ?>" rel="stylesheet" type="text/css" />
    <style>
        .vbh-stats { display: grid; grid-template-columns: repeat(6, minmax(0, 1fr)); gap: 12px; margin-bottom: 20px; }
        @media (max-width: 1199px) { .vbh-stats { grid-template-columns: repeat(3, minmax(0, 1fr)); } }
        @media (max-width: 575px) { .vbh-stats { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
        .vbh-stats .sr-stat { background: var(--sr-surface); }
        .vbh-stats .sr-stat-value { font-size: 22px; }
        @media print { .sr-filter-grid, .sr-standalone-brand { display: none; } }
    </style>
</head>
<body class="sr-standalone">
    <div class="sr-page sr-standalone-wrap">
        <div class="sr-standalone-brand">
            <a href="dashboard.php"><img src="<?= get_setting($conDB, 'logo') ?>" alt=""></a>
        </div>

        <div class="sr-head">
            <div>
                <h1>Vacation Balance History</h1>
                <p>Track and audit daily vacation balance changes</p>
            </div>
            <div class="sr-head-actions">
                <button type="button" class="sr-btn" onclick="window.print()"><i class="mdi mdi-printer"></i> Print</button>
            </div>
        </div>

        <!-- Statistics -->
        <div class="vbh-stats">
            <div class="sr-stat is-sky">
                <div class="sr-stat-label">Total Records <i class="mdi mdi-database"></i></div>
                <div class="sr-stat-value"><?php echo (int)($stats['total_records'] ?? 0); ?></div>
            </div>
            <div class="sr-stat is-sky">
                <div class="sr-stat-label">Unique Employees <i class="mdi mdi-account-multiple"></i></div>
                <div class="sr-stat-value"><?php echo (int)($stats['unique_employees'] ?? 0); ?></div>
            </div>
            <div class="sr-stat is-sky">
                <div class="sr-stat-label">Days Tracked <i class="mdi mdi-calendar"></i></div>
                <div class="sr-stat-value"><?php echo (int)($stats['days_tracked'] ?? 0); ?></div>
            </div>
            <div class="sr-stat is-green">
                <div class="sr-stat-label">Balance Changes <i class="mdi mdi-swap-vertical"></i></div>
                <div class="sr-stat-value"><?php echo (int)($stats['records_changed'] ?? 0); ?></div>
            </div>
            <div class="sr-stat is-red">
                <div class="sr-stat-label">Negative Balances <i class="mdi mdi-minus-circle-outline"></i></div>
                <div class="sr-stat-value"><?php echo (int)($stats['negative_balances'] ?? 0); ?></div>
            </div>
            <div class="sr-stat is-red">
                <div class="sr-stat-label">Errors <i class="mdi mdi-alert-circle-outline"></i></div>
                <div class="sr-stat-value"><?php echo (int)($stats['error_count'] ?? 0); ?></div>
            </div>
        </div>

        <div class="sr-card">
            <!-- Filters -->
            <form method="GET" class="sr-filter-grid" style="border-bottom: 1px solid var(--sr-border);">
                <div>
                    <label>Employee ID</label>
                    <input type="text" name="emp_id" class="form-control" placeholder="e.g., 1061" value="<?php echo htmlspecialchars($filter_emp_id); ?>">
                </div>
                <div>
                    <label>From Date</label>
                    <input type="date" name="date_from" class="form-control" value="<?php echo htmlspecialchars($filter_date_from); ?>">
                </div>
                <div>
                    <label>To Date</label>
                    <input type="date" name="date_to" class="form-control" value="<?php echo htmlspecialchars($filter_date_to); ?>">
                </div>
                <div>
                    <label>Balance Status</label>
                    <select name="balance_changed" class="form-control">
                        <option value="-1">All</option>
                        <option value="1" <?php echo $filter_balance_changed === 1 ? 'selected' : ''; ?>>Changed</option>
                        <option value="0" <?php echo $filter_balance_changed === 0 ? 'selected' : ''; ?>>Unchanged</option>
                    </select>
                </div>
                <div class="sr-filter-actions">
                    <button type="submit" class="sr-btn sr-btn-primary"><i class="mdi mdi-magnify"></i> Filter</button>
                    <a href="vacation_balance_history.php" class="sr-btn"><i class="mdi mdi-refresh"></i> Reset</a>
                    <label class="sr-check">
                        <input type="checkbox" name="show_errors" value="1" <?php echo $show_errors ? 'checked' : ''; ?>> Errors only
                    </label>
                </div>
            </form>

            <!-- History Table -->
            <div class="sr-card-head">
                <h2 class="sr-card-title"><i class="mdi mdi-history"></i> Balance History Records</h2>
                <span class="sr-card-sub">Latest <?php echo count($history_records); ?></span>
            </div>

            <?php if (count($history_records) > 0): ?>
                <div class="sr-table-wrap sr-table-scroll">
                    <table class="sr-table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Employee</th>
                                <th class="text-right">Old Balance</th>
                                <th class="text-right">New Balance</th>
                                <th class="text-right">Change</th>
                                <th>Status</th>
                                <th class="text-right">Earned</th>
                                <th class="text-right">Used</th>
                                <th class="text-right">Carryover</th>
                                <th>Period</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($history_records as $record):
                                $newBal = (float)$record['new_available_balance'];
                                $chg = (float)$record['change_amount'];
                                if ($newBal < 0) {
                                    $status_tone = 'tone-red';
                                    $status_text = 'Negative';
                                } elseif (($record['calculation_status'] ?? '') === 'error') {
                                    $status_tone = 'tone-red';
                                    $status_text = 'Error';
                                } else {
                                    $status_tone = $record['balance_changed'] ? 'tone-amber' : 'tone-green';
                                    $status_text = $record['balance_changed'] ? 'Changed' : 'Refreshed';
                                }
                                $empName = (string)($record['emp_name'] ?? 'N/A');
                                $initials = strtoupper(implode('', array_map(function ($w) { return mb_substr($w, 0, 1); }, array_slice(preg_split('/\s+/', trim($empName)), 0, 2))));
                            ?>
                                <tr>
                                    <td class="sr-date"><strong><?php echo htmlspecialchars($record['snapshot_date']); ?></strong></td>
                                    <td>
                                        <div class="sr-person">
                                            <span class="sr-avatar sr-avatar-sm"><?php echo htmlspecialchars($initials); ?></span>
                                            <div>
                                                <span class="sr-person-name"><?php echo htmlspecialchars($empName); ?></span>
                                                <span class="sr-cell-sub"><?php echo htmlspecialchars($record['emp_id']); ?></span>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="sr-num"><?php echo number_format((float)$record['old_available_balance'], 2); ?></td>
                                    <td class="sr-num <?php echo $newBal < 0 ? 'is-bad' : 'is-up'; ?>"><?php echo number_format($newBal, 2); ?></td>
                                    <td class="sr-num <?php echo $chg > 0 ? 'is-up' : ($chg < 0 ? 'is-down' : 'is-muted'); ?>">
                                        <?php echo $record['balance_changed'] ? ($chg > 0 ? '+' : '') . number_format($chg, 2) : '—'; ?>
                                    </td>
                                    <td><span class="sr-pill sr-pill-xs <?php echo $status_tone; ?>"><span class="sr-dot"></span><?php echo htmlspecialchars($status_text); ?></span></td>
                                    <td class="sr-num"><?php echo $record['new_used_days'] ? number_format((float)$record['new_used_days'], 2) : '—'; ?></td>
                                    <td class="sr-num"><?php echo $record['new_remaining_balance'] ? number_format((float)$record['new_remaining_balance'], 2) : '—'; ?></td>
                                    <td class="sr-num"><?php echo $record['carryover_days'] ? number_format((float)$record['carryover_days'], 2) : '—'; ?></td>
                                    <td class="sr-date">
                                        <?php echo $record['period_start'] ? htmlspecialchars($record['period_start']) : '—'; ?>
                                        <small><?php echo $record['period_end'] ? '→ ' . htmlspecialchars($record['period_end']) : ''; ?></small>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="sr-empty"><i class="mdi mdi-inbox"></i>No history records found for the selected filters.</div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
