<?php
// Final Verification: Check deduction status by approval status

require_once 'includes/db.php';

$sql = "
    SELECT 
        v.request_inv_no,
        v.emp_id,
        v.vacdays,
        v.current_status,
        COALESCE(b.used_days, 0) as deducted_days,
        CASE 
            WHEN v.current_status = 'completed' AND b.id IS NOT NULL THEN 'CORRECT'
            WHEN v.current_status = 'completed' AND b.id IS NULL THEN 'MISSING'
            WHEN v.current_status = 'rejected' AND b.id IS NULL THEN 'EXPECTED'
            WHEN v.current_status IN ('pending', 'hr_payroll', 'general_manager') THEN 'EXPECTED'
            ELSE 'UNKNOWN'
        END as deduction_status
    FROM emp_vacation v
    LEFT JOIN emp_vacation_balance b ON v.id = b.vac_id
    WHERE v.request_inv_no LIKE 'VAC-%'
    AND v.created_at >= DATE_SUB(NOW(), INTERVAL 60 DAY)
    ORDER BY v.current_status, v.id DESC
    LIMIT 50
";

$result = mysqli_query($conDB, $sql);

echo "=== VERIFICATION BY STATUS ===\n\n";

$status_groups = [];
while ($row = mysqli_fetch_assoc($result)) {
    $status = $row['current_status'];
    if (!isset($status_groups[$status])) {
        $status_groups[$status] = ['total' => 0, 'with_deduction' => 0, 'without_deduction' => 0];
    }
    
    $status_groups[$status]['total']++;
    if ($row['deducted_days'] > 0) {
        $status_groups[$status]['with_deduction']++;
    } else {
        $status_groups[$status]['without_deduction']++;
    }
}

foreach ($status_groups as $status => $counts) {
    echo "Status: $status\n";
    echo "  Total: {$counts['total']} | With Deduction: {$counts['with_deduction']} | Without: {$counts['without_deduction']}\n";
    echo "  Deduction Rate: " . ($counts['total'] > 0 ? round(($counts['with_deduction'] / $counts['total']) * 100) : 0) . "%\n\n";
}

// Now check for COMPLETED vacations specifically
echo "\n=== CRITICAL CHECK: COMPLETED VACATIONS ===\n";
$sql_completed = "
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN b.id IS NOT NULL THEN 1 ELSE 0 END) as with_deduction,
        SUM(CASE WHEN b.id IS NULL THEN 1 ELSE 0 END) as without_deduction,
        SUM(CASE WHEN b.used_days > v.vacdays * 1.5 THEN 1 ELSE 0 END) as with_double_deduction
    FROM emp_vacation v
    LEFT JOIN emp_vacation_balance b ON v.id = b.vac_id
    WHERE v.request_inv_no LIKE 'VAC-%'
    AND v.current_status = 'completed'
    AND v.created_at >= DATE_SUB(NOW(), INTERVAL 60 DAY)
";

$result_completed = mysqli_query($conDB, $sql_completed);
$row_completed = mysqli_fetch_assoc($result_completed);

echo "Total COMPLETED Vacations: {$row_completed['total']}\n";
echo "  With Correct Deduction: {$row_completed['with_deduction']}\n";
echo "  Missing Deduction: {$row_completed['without_deduction']}\n";
echo "  With Double Deduction: {$row_completed['with_double_deduction']}\n";

if ($row_completed['with_double_deduction'] == 0) {
    echo "\n✅ NO DOUBLE DEDUCTIONS FOUND IN COMPLETED VACATIONS\n";
} else {
    echo "\n⚠️ WARNING: Double deductions detected in completed vacations\n";
}

if ($row_completed['without_deduction'] == 0 && $row_completed['total'] > 0) {
    echo "✅ ALL COMPLETED VACATIONS HAVE DEDUCTIONS APPLIED\n";
} else if ($row_completed['without_deduction'] > 0) {
    echo "⚠️ Some completed vacations missing deductions - investigating...\n";
}
?>
