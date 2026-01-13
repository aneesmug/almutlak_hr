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
    <style>
        body { font-family: monospace; background: #f5f5f5; padding: 20px; }
        .container { background: white; padding: 20px; border-radius: 5px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        .separator { border-top: 2px solid #333; margin: 15px 0; }
        .record { margin: 20px 0; padding: 15px; background: #fff9e6; border-left: 4px solid #ff9800; }
        .record.critical { background: #ffebee; border-left-color: #d32f2f; }
        .record.warning { background: #fff3e0; border-left-color: #ff9800; }
        .found { color: #1976d2; font-weight: bold; margin-top: 10px; }
        .bullet { margin-left: 20px; }
        .success { color: #4CAF50; font-weight: bold; }
        .warning-text { color: #ff9800; font-weight: bold; }
        .error { color: #d32f2f; font-weight: bold; }
        .summary { background: #f3e5f5; padding: 15px; border-radius: 5px; margin-top: 20px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { padding: 10px; text-align: left; border: 1px solid #ddd; }
        th { background: #f5f5f5; font-weight: bold; border-bottom: 2px solid #333; }
    </style>
</head>
<body>
<div class="container">
<?php

require_once __DIR__ . '/./includes/db.php';

echo "<h2>📋 DOUBLE DEDUCTION DIAGNOSTIC REPORT</h2>";
echo "<div class='separator'></div>";

// Query 1: Find all vacations with possible double deductions
$sql_double = "SELECT 
    v.id as vac_id,
    v.request_inv_no,
    v.emp_id,
    e.name as emp_name,
    v.vacdays as requested_days,
    b.id as balance_id,
    b.used_days,
    b.total_days,
    b.available_balance,
    (b.used_days / v.vacdays) as deduction_ratio,
    b.created_at as balance_created,
    v.current_status
FROM emp_vacation v
JOIN emp_vacation_balance b ON v.id = b.vac_id
LEFT JOIN employees e ON v.emp_id = e.emp_id
WHERE (b.used_days / v.vacdays) > 1.5
ORDER BY deduction_ratio DESC, v.created_at DESC";

$result_double = mysqli_query($conDB, $sql_double);

if (!$result_double) {
    echo "<p class='error'>❌ Database error: " . mysqli_error($conDB) . "</p>";
    exit(1);
}

$double_count = 0;
$affected_employees = [];

echo "<p><strong>🔍 FOUND VACATIONS WITH DOUBLE DEDUCTIONS:</strong></p>";

while ($row = mysqli_fetch_assoc($result_double)) {
    $double_count++;
    $record_class = "record";
    $icon = "⚠️";
    
    if ($row['deduction_ratio'] > 1.9) {
        $record_class = "record critical";
        $icon = "❌";
    } else if ($row['deduction_ratio'] > 1.1) {
        $record_class = "record warning";
        $icon = "⚠️";
    }
    
    echo "<div class='$record_class'>";
    echo "<strong style='font-size:1.1em;'>[$double_count] Vacation ID: " . $row['vac_id'] . "</strong><br>";
    echo "<strong style='color:#666;'>Request:</strong> " . $row['request_inv_no'] . "<br>";
    echo "<strong style='color:#666;'>Employee:</strong> " . $row['emp_id'] . " (" . ($row['emp_name'] ?? 'N/A') . ")<br>";
    echo "<strong style='color:#666;'>Status:</strong> " . $row['current_status'] . "<br>";
    echo "<div class='found'>📊 BALANCE DETAILS:</div>";
    echo "<div class='bullet'>";
    echo "• <strong>Requested Days:</strong> " . $row['requested_days'] . "<br>";
    echo "• <strong>Used Days (in balance):</strong> " . $row['used_days'] . "<br>";
    echo "• <strong>Total Days:</strong> " . $row['total_days'] . "<br>";
    echo "• <strong>Available Balance:</strong> " . $row['available_balance'] . "<br>";
    echo "• <strong>Balance Created:</strong> " . $row['balance_created'] . "<br>";
    echo "</div>";
    echo "<div class='found'>⚠️ DEDUCTION ANALYSIS:</div>";
    echo "<div class='bullet'>";
    
    if ($row['deduction_ratio'] > 1.9) {
        echo "• <strong class='error'>DEDUCTION RATIO: " . round($row['deduction_ratio'], 2) . "x (CRITICAL: DOUBLE DEDUCTION DETECTED!)</strong><br>";
    } else if ($row['deduction_ratio'] > 1.1) {
        echo "• <strong class='warning-text'>DEDUCTION RATIO: " . round($row['deduction_ratio'], 2) . "x (WARNING: Over-deduction detected)</strong><br>";
    }
    echo "</div>";
    echo "</div>";
    
    // Store for summary
    if (!in_array($row['emp_id'], $affected_employees)) {
        $affected_employees[] = $row['emp_id'];
    }
}

echo "<div class='separator'></div>";

// Query 2: Multiple balance records for same vacation
echo "<p><strong>🔗 CHECKING FOR MULTIPLE BALANCE RECORDS PER VACATION:</strong></p>";

$sql_multi = "SELECT 
    v.id as vac_id,
    v.request_inv_no,
    v.emp_id,
    COUNT(b.id) as balance_count,
    GROUP_CONCAT(b.id) as balance_ids,
    SUM(b.used_days) as total_used
FROM emp_vacation v
LEFT JOIN emp_vacation_balance b ON v.id = b.vac_id
GROUP BY v.id
HAVING balance_count > 1
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
    echo "• <strong>Total Used Days (sum):</strong> " . $row['total_used'] . "<br>";
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
    
    // Re-query for specific cases
    $sql_fix = "SELECT 
        v.id as vac_id,
        v.request_inv_no,
        v.emp_id,
        v.vacdays as requested_days,
        b.id as balance_id,
        b.used_days,
        CEIL(b.used_days / 2) as corrected_used_days
    FROM emp_vacation v
    JOIN emp_vacation_balance b ON v.id = b.vac_id
    WHERE (b.used_days / v.vacdays) > 1.5";
    
    $result_fix = mysqli_query($conDB, $sql_fix);
    
    echo "<p><strong>📋 RECORDS REQUIRING FIX:</strong></p>";
    echo "<table>";
    echo "<tr><th>Request</th><th>Employee</th><th>Requested Days</th><th>Current Used</th><th>Should Be</th><th>Balance ID</th></tr>";
    
    while ($row = mysqli_fetch_assoc($result_fix)) {
        echo "<tr>";
        echo "<td>" . $row['request_inv_no'] . "</td>";
        echo "<td>" . $row['emp_id'] . "</td>";
        echo "<td>" . $row['requested_days'] . "</td>";
        echo "<td><strong class='error'>" . $row['used_days'] . "</strong></td>";
        echo "<td><strong class='success'>" . $row['requested_days'] . "</strong></td>";
        echo "<td>" . $row['balance_id'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<br><p class='success'><strong>✅ NO DOUBLE DEDUCTIONS FOUND</strong></p>";
    echo "<p>The vacation balance system appears to be working correctly.</p>";
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
</body>
</html>
