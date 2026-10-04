<?php
/**
 * DIAGNOSTIC SCRIPT: Find and Analyze Double Deduction Records
 * 
 * This script identifies vacations that have been deducted multiple times
 * and provides detailed analysis
 */

// Add HTML header for browser display
header('Content-Type: text/html; charset=UTF-8');
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Double Deduction Diagnostic Report</title>
    <link href="assets/css/bootstrap.min.css" rel="stylesheet" type="text/css" />
    <link href="assets/css/smart_request.css?v=<?= @filemtime(__DIR__ . '/assets/css/smart_request.css') ?>" rel="stylesheet" type="text/css" />
    <style>
        /* Report blocks echoed below, styled with the new GUI tokens */
        .ddd-body { padding: 18px; font-size: 13px; line-height: 1.6; }
        .ddd-body h2 { margin: 0 0 4px; font-size: 22px; font-weight: 700; color: var(--sr-text); }
        .ddd-body h3 { margin: 0 0 10px; font-size: 16px; font-weight: 700; color: var(--sr-text); }
        .separator { border-top: 1px solid var(--sr-border); margin: 16px 0; }
        .record { margin: 12px 0; padding: 14px 16px; border-radius: 12px; border: 1px solid var(--sr-border); border-inline-start: 4px solid var(--tone-green-bd); background: var(--sr-surface-2); }
        .record.warning { border-color: var(--tone-amber-bd); background: var(--tone-amber-bg); }
        .record.critical { border-color: var(--tone-red-bd); background: var(--tone-red-bg); }
        .found { color: var(--sr-accent-strong); font-weight: 700; margin-top: 10px; }
        .bullet { margin-inline-start: 16px; color: var(--sr-text-2); }
        .success { color: var(--tone-green-fg); font-weight: 700; }
        .warning-text { color: var(--tone-amber-fg); font-weight: 700; }
        .error { color: var(--tone-red-fg); font-weight: 700; }
        .summary { margin-top: 16px; padding: 16px 18px; border-radius: 12px; border: 1px solid var(--tone-indigo-bd); background: var(--tone-indigo-bg); }
        .ddd-body table { width: 100%; margin-top: 10px; border-collapse: separate; border-spacing: 0; border: 1px solid var(--sr-border); border-radius: 10px; overflow: hidden; background: var(--sr-surface); }
        .ddd-body th { padding: 10px 12px; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .4px; color: var(--sr-muted); background: var(--sr-surface-2); border-bottom: 1px solid var(--sr-border); text-align: start; }
        .ddd-body td { padding: 10px 12px; border-bottom: 1px solid var(--sr-border); font-variant-numeric: tabular-nums; }
        .ddd-body tr:last-child td { border-bottom: 0; }
    </style>
</head>
<body class="sr-standalone">
<div class="sr-page sr-standalone-wrap">
<div class="sr-card">
<div class="ddd-body">
<?php

require_once __DIR__ . '/./includes/db.php';

echo "<h2>📋 DOUBLE DEDUCTION DIAGNOSTIC REPORT</h2>";
echo "<div class='separator'></div>";

echo "<p><strong>ℹ️ DIAGNOSTIC RULE:</strong> <span class='warning-text'>emp_vacation_balance.used_days is cumulative across the employee balance history.</span></p>";
echo "<p>This report checks each vacation deduction against the <strong>delta from the previous balance row</strong>, not against the raw cumulative used_days value.</p>";
echo "<div class='separator'></div>";

$sql_vacations = "SELECT 
    v.id AS vac_id,
    v.request_inv_no,
    v.emp_id,
    e.name AS emp_name,
    v.vacdays AS requested_days,
    v.current_status,
    v.created_at AS vacation_created,
    b.id AS balance_id,
    b.used_days,
    b.total_days,
    b.remaining_balance,
    b.available_balance,
    b.created_at AS balance_created,
    b.last_updated,
    b.period_start,
    b.period_end,
    b.contract_id
FROM emp_vacation v
JOIN emp_vacation_balance b ON v.id = b.vac_id
LEFT JOIN employees e ON v.emp_id = e.emp_id
WHERE v.vacdays > 0
    AND v.review = 'A'
ORDER BY v.emp_id ASC, b.id ASC";

$result_vacations = mysqli_query($conDB, $sql_vacations);
if (!$result_vacations) {
    echo "<p class='error'>❌ Database error: " . mysqli_error($conDB) . "</p>";
    exit(1);
}

$prev_balance_sql = "SELECT id, vac_id, used_days, available_balance, total_days, remaining_balance, last_updated
                     FROM emp_vacation_balance
                     WHERE emp_id = ? AND id < ?
                     ORDER BY id DESC
                     LIMIT 1";
$prev_balance_stmt = mysqli_prepare($conDB, $prev_balance_sql);

if (!$prev_balance_stmt) {
    echo "<p class='error'>❌ Failed to prepare previous balance query: " . mysqli_error($conDB) . "</p>";
    exit(1);
}

$double_count = 0;
$affected_employees = [];
$suspicious_records = [];

echo "<p><strong>🔍 FOUND VACATIONS WITH DOUBLE DEDUCTIONS:</strong></p>";

while ($row = mysqli_fetch_assoc($result_vacations)) {
    $requested_days = (float)($row['requested_days'] ?? 0);
    if ($requested_days <= 0) {
        continue;
    }

    $emp_id = (int)($row['emp_id'] ?? 0);
    $balance_id = (int)($row['balance_id'] ?? 0);
    $current_used = (float)($row['used_days'] ?? 0);
    $current_available = (float)($row['available_balance'] ?? 0);

    $prev_balance = null;
    mysqli_stmt_bind_param($prev_balance_stmt, 'ii', $emp_id, $balance_id);
    if (mysqli_stmt_execute($prev_balance_stmt)) {
        $prev_result = mysqli_stmt_get_result($prev_balance_stmt);
        $prev_balance = $prev_result ? mysqli_fetch_assoc($prev_result) : null;
        if ($prev_result) {
            mysqli_free_result($prev_result);
        }
    }

    $prev_used = (float)($prev_balance['used_days'] ?? 0);
    $prev_available = isset($prev_balance['available_balance']) ? (float)$prev_balance['available_balance'] : null;

    $actual_used_delta = $current_used - $prev_used;
    $actual_available_delta = ($prev_available !== null) ? ($prev_available - $current_available) : null;

    if ($actual_used_delta < 0) {
        $actual_used_delta = 0.0;
    }
    if ($actual_available_delta !== null && $actual_available_delta < 0) {
        $actual_available_delta = 0.0;
    }

    $used_ratio = $requested_days > 0 ? ($actual_used_delta / $requested_days) : 0;
    $available_ratio = ($actual_available_delta !== null && $requested_days > 0)
        ? ($actual_available_delta / $requested_days)
        : null;

    $is_over_by_used = ($actual_used_delta - $requested_days) > 0.01;
    $is_over_by_available = ($actual_available_delta !== null) && (($actual_available_delta - $requested_days) > 0.01);

    if (!$is_over_by_used && !$is_over_by_available) {
        continue;
    }

    $double_count++;
    $record_class = 'record warning';
    if ($used_ratio >= 1.9 || ($available_ratio !== null && $available_ratio >= 1.9)) {
        $record_class = 'record critical';
    }

    $suspicious_records[] = [
        'vac_id' => $row['vac_id'],
        'request_inv_no' => $row['request_inv_no'],
        'emp_id' => $row['emp_id'],
        'emp_name' => $row['emp_name'] ?? 'N/A',
        'current_status' => $row['current_status'],
        'requested_days' => $requested_days,
        'balance_id' => $balance_id,
        'current_used' => $current_used,
        'prev_used' => $prev_used,
        'actual_used_delta' => $actual_used_delta,
        'current_available' => $current_available,
        'prev_available' => $prev_available,
        'actual_available_delta' => $actual_available_delta,
        'used_ratio' => $used_ratio,
        'available_ratio' => $available_ratio,
        'balance_created' => $row['balance_created'],
        'vacation_created' => $row['vacation_created'],
        'prev_balance_id' => $prev_balance['id'] ?? null,
    ];

    echo "<div class='" . $record_class . "'>";
    echo "<strong style='font-size:1.1em;'>[$double_count] Vacation ID: " . (int)$row['vac_id'] . "</strong><br>";
    echo "<strong style='color:#666;'>Request:</strong> " . htmlspecialchars((string)$row['request_inv_no']) . "<br>";
    echo "<strong style='color:#666;'>Employee:</strong> " . htmlspecialchars((string)$row['emp_id']) . " (" . htmlspecialchars((string)($row['emp_name'] ?? 'N/A')) . ")<br>";
    echo "<strong style='color:#666;'>Status:</strong> " . htmlspecialchars((string)$row['current_status']) . "<br>";
    echo "<div class='found'>📊 BALANCE DETAILS:</div>";
    echo "<div class='bullet'>";
    echo "• <strong>Requested Days (vacdays):</strong> " . number_format($requested_days, 2) . "<br>";
    echo "• <strong>Previous Balance ID:</strong> " . ($prev_balance['id'] ?? 'None') . "<br>";
    echo "• <strong>Previous Used Days (cumulative):</strong> " . number_format($prev_used, 2) . "<br>";
    echo "• <strong>Current Used Days (cumulative):</strong> " . number_format($current_used, 2) . "<br>";
    echo "• <strong>Actual Deducted Days (used_days delta):</strong> " . number_format($actual_used_delta, 2) . "<br>";
    if ($prev_available !== null) {
        echo "• <strong>Previous Available Balance:</strong> " . number_format($prev_available, 2) . "<br>";
        echo "• <strong>Current Available Balance:</strong> " . number_format($current_available, 2) . "<br>";
        echo "• <strong>Actual Balance Drop:</strong> " . number_format((float)$actual_available_delta, 2) . "<br>";
    } else {
        echo "• <strong>Current Available Balance:</strong> " . number_format($current_available, 2) . "<br>";
    }
    echo "• <strong>Balance Created:</strong> " . htmlspecialchars((string)$row['balance_created']) . "<br>";
    echo "</div>";
    echo "<div class='found'>⚠️ DEDUCTION ANALYSIS:</div>";
    echo "<div class='bullet'>";
    if ($used_ratio >= 1.9 || ($available_ratio !== null && $available_ratio >= 1.9)) {
        echo "• <strong class='error'>USED DELTA RATIO: " . round($used_ratio, 2) . "x (CRITICAL: DOUBLE DEDUCTION DETECTED)</strong><br>";
    } else {
        echo "• <strong class='warning-text'>USED DELTA RATIO: " . round($used_ratio, 2) . "x (WARNING: Over-deduction detected)</strong><br>";
    }
    if ($available_ratio !== null) {
        echo "• <strong>AVAILABLE BALANCE DROP RATIO:</strong> " . round($available_ratio, 2) . "x<br>";
    }
    echo "</div>";
    echo "</div>";

    if (!in_array($row['emp_id'], $affected_employees, true)) {
        $affected_employees[] = $row['emp_id'];
    }
}

mysqli_stmt_close($prev_balance_stmt);

if ($double_count === 0) {
    echo "<div class='record'>";
    echo "<strong class='success'>✅ No double deductions found using cumulative-balance delta analysis</strong>";
    echo "</div>";
}

echo "<div class='separator'></div>";

// Query 2: Multiple balance records for same vacation
echo "<p><strong>🔗 CHECKING FOR MULTIPLE BALANCE RECORDS PER VACATION:</strong></p>";

$sql_multi = "SELECT 
    v.id AS vac_id,
    v.request_inv_no,
    v.emp_id,
    COUNT(b.id) AS balance_count,
    GROUP_CONCAT(b.id ORDER BY b.id ASC) AS balance_ids,
    MIN(b.created_at) AS first_balance_created,
    MAX(b.created_at) AS last_balance_created
FROM emp_vacation_balance b
JOIN emp_vacation v ON v.id = b.vac_id
WHERE b.vac_id > 0
    AND v.review = 'A'
GROUP BY v.id, v.request_inv_no, v.emp_id
HAVING COUNT(b.id) > 1
ORDER BY v.created_at DESC";

$result_multi = mysqli_query($conDB, $sql_multi);
$multi_count = 0;

while ($row = mysqli_fetch_assoc($result_multi)) {
    $multi_count++;
    echo "<div class='record critical'>";
    echo "<strong>[$multi_count] Vacation: " . $row['request_inv_no'] . " (ID: " . $row['vac_id'] . ")</strong><br>";
    echo "<strong class='error'>❌ ERROR: Multiple balance records detected!</strong><br>";
    echo "<div class='bullet'>";
    echo "• <strong>Balance Record IDs:</strong> " . $row['balance_ids'] . "<br>";
    echo "• <strong>Balance Count:</strong> " . $row['balance_count'] . "<br>";
    echo "• <strong>First Balance Created:</strong> " . $row['first_balance_created'] . "<br>";
    echo "• <strong>Last Balance Created:</strong> " . $row['last_balance_created'] . "<br>";
    echo "</div>";
    echo "</div>";
}

if ($multi_count == 0) {
    echo "<div class='record'>";
    echo "<strong class='success'>✅ No multiple balance records found</strong>";
    echo "</div>";
}

echo "<div class='separator'></div>";

// Summary Report
echo "<div class='summary'>";
echo "<h3>📊 SUMMARY REPORT</h3>";
echo "<strong>Total Vacations with Double Deductions Found: <span class='error'>$double_count</span></strong><br>";
echo "<strong>Total Vacations with Multiple Balances: <span class='error'>$multi_count</span></strong><br>";

if ($double_count > 0) {
    echo "<br><p class='error'><strong>❌ ACTION REQUIRED:</strong></p>";
    echo "<p>The following vacations have been over-deducted and need to be fixed:</p>";

    echo "<p><strong>📋 RECORDS REQUIRING FIX:</strong></p>";
    echo "<table>";
    echo "<tr><th>Request</th><th>Employee</th><th>Requested Days</th><th>Actual Deducted</th><th>Should Be</th><th>Balance ID</th></tr>";

    foreach ($suspicious_records as $row) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars((string)$row['request_inv_no']) . "</td>";
        echo "<td>" . htmlspecialchars((string)$row['emp_id']) . "</td>";
        echo "<td>" . number_format((float)$row['requested_days'], 2) . "</td>";
        echo "<td><strong class='error'>" . number_format((float)$row['actual_used_delta'], 2) . "</strong></td>";
        echo "<td><strong class='success'>" . number_format((float)$row['requested_days'], 2) . "</strong></td>";
        echo "<td>" . $row['balance_id'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<br><p class='success'><strong>✅ NO DOUBLE DEDUCTIONS FOUND</strong></p>";
    echo "<p>The vacation balance system appears to be working correctly when measured by per-vacation balance deltas.</p>";
}

echo "<br><strong>Affected Employees: " . count($affected_employees) . "</strong>";
if (count($affected_employees) > 0) {
    echo "<br><strong>Employee IDs:</strong> " . implode(", ", $affected_employees);
}

echo "</div>";

echo "<div class='separator'></div>";
echo "<h3>✅ Diagnostic Complete</h3>";

mysqli_close($conDB);
?>
</div>
</div>
</div>
</body>
</html>
