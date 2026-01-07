<?php
/**
 * CORRECTION SCRIPT: Fix Existing Double Deduction Records
 * 
 * This script corrects vacations that have been over-deducted
 * IMPORTANT: Review the corrections before executing
 */

// Add HTML header for browser display
header('Content-Type: text/html; charset=UTF-8');
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Double Deduction Fix Report</title>
    <style>
        body { font-family: monospace; background: #f5f5f5; padding: 20px; }
        .container { background: white; padding: 20px; border-radius: 5px; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        .separator { border-top: 2px solid #333; margin: 10px 0; }
        .record { margin: 20px 0; padding: 15px; background: #f9f9f9; border-left: 4px solid #4CAF50; }
        .found { color: #1976d2; font-weight: bold; margin-top: 10px; }
        .corrections { color: #d32f2f; font-weight: bold; margin-top: 10px; }
        .bullet { margin-left: 20px; }
        .success { color: #4CAF50; font-weight: bold; }
        .error { color: #d32f2f; font-weight: bold; }
        .summary { background: #e8f5e9; padding: 15px; border-radius: 5px; margin-top: 20px; }
        pre { background: #f5f5f5; padding: 10px; overflow-x: auto; }
    </style>
</head>
<body>
<div class="container">
<?php

require_once __DIR__ . '/../includes/db.php';

echo "<h2>🔧 FIXING DOUBLE DEDUCTION RECORDS</h2>";
echo "<div class='separator'></div>";

// DYNAMICALLY FETCH CORRECTIONS FROM DATABASE
echo "<p><strong>📊 SCANNING FOR DOUBLE DEDUCTIONS...</strong></p>";
echo "<div class='separator'></div>";

$sql_scan = "SELECT 
    b.id as balance_id,
    v.id as vac_id,
    v.request_inv_no,
    v.emp_id,
    v.vacdays as requested_days,
    b.used_days,
    (b.used_days / v.vacdays) as deduction_ratio
FROM emp_vacation v
JOIN emp_vacation_balance b ON v.id = b.vac_id
WHERE (b.used_days / v.vacdays) > 1.1
ORDER BY deduction_ratio DESC, v.created_at DESC";

$scan_result = mysqli_query($conDB, $sql_scan);

if (!$scan_result) {
    echo "❌ Database error: " . mysqli_error($conDB) . "\n";
    exit(1);
}

// Build corrections array from query results
$corrections = [];
$scan_count = 0;

echo "<p><strong>📋 DOUBLE DEDUCTIONS FOUND:</strong></p>";

while ($row = mysqli_fetch_assoc($scan_result)) {
    $balance_id = (int)$row['balance_id'];
    $correct_used_days = (int)$row['requested_days'];
    $current_used_days = (int)$row['used_days'];
    $ratio = (float)$row['deduction_ratio'];
    
    $scan_count++;
    echo "<div class='record'>";
    echo "<strong>[$scan_count] Balance ID: $balance_id</strong><br>";
    echo "<strong style='color:#666;'>Request:</strong> " . $row['request_inv_no'] . "<br>";
    echo "<strong style='color:#666;'>Employee:</strong> " . $row['emp_id'] . "<br>";
    echo "<strong style='color:#666;'>Requested Days:</strong> " . $row['requested_days'] . "<br>";
    echo "<strong style='color:#666;'>Current Used Days:</strong> $current_used_days<br>";
    echo "<strong style='color:#666;'>Deduction Ratio:</strong> " . round($ratio, 2) . "x<br>";
    echo "<strong style='color:#d32f2f;'>⚠️ Correction:</strong> $current_used_days → $correct_used_days<br>";
    echo "</div>";
    
    $corrections[$balance_id] = $correct_used_days;
}

echo "<div class='separator'></div>";

if (count($corrections) == 0) {
    echo "<p class='success'>✅ NO DOUBLE DEDUCTIONS FOUND</p>";
    echo "<p>The vacation balance system appears to be working correctly.</p>";
    mysqli_close($conDB);
    echo "</div></body></html>";
    exit(0);
}

echo "<p><strong>Total Double Deductions to Fix: " . count($corrections) . "</strong></p>";
echo "<div class='separator'></div>";

$fixed_count = 0;
$failed_count = 0;
$scan_details = []; // Store scan details for later use

echo "<p><strong>🔧 STARTING CORRECTIONS...</strong></p>";

// Re-scan to get all details for each balance record being fixed
$details_sql = "SELECT 
    b.id as balance_id,
    v.request_inv_no,
    v.emp_id,
    v.vacdays as requested_days,
    b.used_days,
    b.available_balance,
    b.total_days,
    (b.used_days / v.vacdays) as deduction_ratio
FROM emp_vacation v
JOIN emp_vacation_balance b ON v.id = b.vac_id
WHERE b.id IN (" . implode(",", array_keys($corrections)) . ")
ORDER BY deduction_ratio DESC";

$details_result = mysqli_query($conDB, $details_sql);
while ($row = mysqli_fetch_assoc($details_result)) {
    $scan_details[$row['balance_id']] = $row;
}

foreach ($corrections as $balance_id => $correct_used_days) {
    // Get stored scan details
    $detail = $scan_details[$balance_id] ?? null;
    
    if (!$detail) {
        echo "<div class='record error'>❌ FAILED: Balance record $balance_id details not found</div>";
        $failed_count++;
        continue;
    }
    
    $old_used = (float)$detail['used_days'];
    $old_available = (float)$detail['available_balance'];
    $total_days = (float)$detail['total_days'];
    
    // Calculate new available balance
    $new_available = $total_days - $correct_used_days;
    
    echo "<div class='record'>";
    echo "<strong style='font-size:1.1em;'>🔧 [Fixing] Balance Record ID: $balance_id</strong><br>";
    echo "<strong style='color:#666;'>Request:</strong> " . $detail['request_inv_no'] . "<br>";
    echo "<strong style='color:#666;'>Employee:</strong> " . $detail['emp_id'] . "<br>";
    echo "<div class='found'>📦 FOUND VALUES FROM DATABASE:</div>";
    echo "<div class='bullet'>";
    echo "• <strong>Requested Days:</strong> " . $detail['requested_days'] . "<br>";
    echo "• <strong>Used Days (Current):</strong> " . $old_used . "<br>";
    echo "• <strong>Available Balance (Current):</strong> " . $old_available . "<br>";
    echo "• <strong>Total Days:</strong> " . $total_days . "<br>";
    echo "• <strong>Deduction Ratio:</strong> " . round($detail['deduction_ratio'], 2) . "x (Problem: > 1.0x)<br>";
    echo "</div>";
    echo "<div class='corrections'>✏️ CORRECTIONS TO APPLY:</div>";
    echo "<div class='bullet'>";
    echo "• <strong>Used Days:</strong> " . $old_used . " → " . $correct_used_days . "<br>";
    echo "• <strong>Available Balance:</strong> " . $old_available . " → " . $new_available . "<br>";
    echo "</div>";
    echo "<br>";

    // Update the balance record
    $update_sql = "UPDATE emp_vacation_balance 
                   SET used_days = " . (float)$correct_used_days . ",
                       available_balance = " . (float)$new_available . ",
                       last_updated = NOW(),
                       updated_at = NOW()
                   WHERE id = $balance_id";
    
    if (mysqli_query($conDB, $update_sql)) {
        echo "<strong class='success'>✅ FIXED</strong><br>";
        $fixed_count++;
    } else {
        echo "<strong class='error'>❌ FAILED: " . mysqli_error($conDB) . "</strong><br>";
        $failed_count++;
    }
    echo "</div>";
}

echo "<div class='separator'></div>";
echo "<div class='summary'>";
echo "<h3>📊 CORRECTION SUMMARY</h3>";
echo "<strong class='success'>✅ Fixed: $fixed_count records</strong><br>";
echo "<strong class='error'>❌ Failed: $failed_count records</strong>";
echo "</div>";

echo "<div class='separator'></div>";
echo "<p><strong>✔️ VERIFYING CORRECTIONS...</strong></p>";

$verify_sql = "SELECT 
    v.request_inv_no,
    v.vacdays as requested,
    b.used_days,
    b.available_balance,
    (b.used_days / v.vacdays) as ratio
FROM emp_vacation v
JOIN emp_vacation_balance b ON v.id = b.vac_id
WHERE b.id IN (" . implode(",", array_keys($corrections)) . ")
ORDER BY v.request_inv_no";

$verify_result = mysqli_query($conDB, $verify_sql);

echo "<table style='width:100%; border-collapse: collapse; margin-top: 10px;'>";
echo "<tr style='background: #f5f5f5; border-bottom: 2px solid #333;'>";
echo "<th style='padding: 10px; text-align: left; border: 1px solid #ddd;'>Status</th>";
echo "<th style='padding: 10px; text-align: left; border: 1px solid #ddd;'>Request Invoice</th>";
echo "<th style='padding: 10px; text-align: center; border: 1px solid #ddd;'>Requested</th>";
echo "<th style='padding: 10px; text-align: center; border: 1px solid #ddd;'>Used</th>";
echo "<th style='padding: 10px; text-align: center; border: 1px solid #ddd;'>Ratio</th>";
echo "</tr>";

while ($row = mysqli_fetch_assoc($verify_result)) {
    $ratio = (float)$row['ratio'];
    $status = abs($ratio - 1.0) < 0.01 ? "✅ OK" : "⚠️ Issue";
    $status_color = abs($ratio - 1.0) < 0.01 ? "#4CAF50" : "#ff9800";
    echo "<tr style='border-bottom: 1px solid #ddd;'>";
    echo "<td style='padding: 10px; border: 1px solid #ddd; color: $status_color; font-weight: bold;'>$status</td>";
    echo "<td style='padding: 10px; border: 1px solid #ddd;'>" . $row['request_inv_no'] . "</td>";
    echo "<td style='padding: 10px; border: 1px solid #ddd; text-align: center;'>" . $row['requested'] . "</td>";
    echo "<td style='padding: 10px; border: 1px solid #ddd; text-align: center;'>" . $row['used_days'] . "</td>";
    echo "<td style='padding: 10px; border: 1px solid #ddd; text-align: center;'>" . round($ratio, 2) . "x</td>";
    echo "</tr>";
}

echo "</table>";

echo "<div class='separator'></div>";
echo "<h3>✅ Correction Complete</h3>";
echo "<p><strong>NOTE:</strong> Future approvals will use the new prevention logic and won't have double deductions.</p>";

mysqli_close($conDB);
?>
</div>
</body>
</html>
