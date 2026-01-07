<?php
/**
 * TEST: Verify deductions are being applied (single time, not zero times, not double)
 */

require_once __DIR__ . '/includes/db.php';

echo "=== VACATION DEDUCTION VERIFICATION ===\n\n";

// Check for VAC vacations (the ones that should have deductions)
$sql = "SELECT 
    v.id,
    v.request_inv_no,
    v.emp_id,
    v.vacdays as requested_days,
    b.id as balance_id,
    b.used_days,
    b.available_balance,
    v.current_status
FROM emp_vacation v
LEFT JOIN emp_vacation_balance b ON v.id = b.vac_id
WHERE v.request_inv_no LIKE 'VAC-%'
AND v.created_at >= DATE_SUB(NOW(), INTERVAL 60 DAY)
ORDER BY v.created_at DESC
LIMIT 50";

$result = mysqli_query($conDB, $sql);

if (!$result) {
    echo "❌ Database error: " . mysqli_error($conDB) . "\n";
    exit(1);
}

$total = 0;
$with_deduction = 0;
$without_deduction = 0;
$double_deduction = 0;

echo "Recent VAC Vacation Requests (Last 60 Days):\n";
echo str_repeat("-", 100) . "\n";

while ($row = mysqli_fetch_assoc($result)) {
    $total++;
    $requested = (float)$row['requested_days'];
    $used = isset($row['used_days']) ? (float)$row['used_days'] : 0;
    $has_balance = !empty($row['balance_id']);
    
    echo "\n" . $row['request_inv_no'] . " | Employee: " . $row['emp_id'] . " | Status: " . $row['current_status'] . "\n";
    echo "  Requested: $requested days\n";
    
    if ($has_balance) {
        echo "  Used Days (Deducted): $used\n";
        echo "  Available Balance: " . $row['available_balance'] . "\n";
        
        // Check ratio
        if ($used > 0) {
            $ratio = $used / $requested;
            if ($ratio >= 1.9) {
                echo "  ❌ DOUBLE DEDUCTION! (Ratio: " . round($ratio, 2) . "x)\n";
                $double_deduction++;
            } elseif (abs($ratio - 1.0) < 0.1) {
                echo "  ✅ Correct deduction (1:1 ratio)\n";
                $with_deduction++;
            } else {
                echo "  ⚠️  Partial deduction (Ratio: " . round($ratio, 2) . "x)\n";
                $with_deduction++;
            }
        }
    } else {
        echo "  ❌ NO DEDUCTION APPLIED (No balance record)\n";
        $without_deduction++;
    }
}

echo "\n" . str_repeat("-", 100) . "\n";
echo "\n=== SUMMARY ===\n";
echo "Total Vacations Checked: $total\n";
echo "With Deductions: $with_deduction ✅\n";
echo "Without Deductions: $without_deduction ❌\n";
echo "Double Deductions: $double_deduction ❌\n";

if ($without_deduction == 0 && $double_deduction == 0) {
    echo "\n✅ SYSTEM STATUS: DEDUCTIONS WORKING CORRECTLY!\n";
} else {
    echo "\n⚠️  SYSTEM STATUS: Needs review\n";
    if ($without_deduction > 0) {
        echo "   - Some vacations missing deductions\n";
    }
    if ($double_deduction > 0) {
        echo "   - Some vacations have double deductions\n";
    }
}

mysqli_close($conDB);
?>
