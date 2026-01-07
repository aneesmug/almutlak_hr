<?php
// Check which completed vacations are missing deductions and why

require_once 'includes/db/conn.php';

$sql = "
    SELECT 
        v.id,
        v.request_inv_no,
        v.emp_id,
        e.emp_name,
        v.vacdays,
        v.current_status,
        v.vac_type,
        b.id as balance_id,
        b.used_days
    FROM emp_vacation v
    LEFT JOIN emp_vacation_balance b ON v.id = b.vac_id
    LEFT JOIN employee_details e ON v.emp_id = e.emp_id
    WHERE v.request_inv_no LIKE 'VAC-%'
    AND v.current_status = 'completed'
    AND b.id IS NULL
    AND v.created_at >= DATE_SUB(NOW(), INTERVAL 60 DAY)
    ORDER BY v.created_at DESC
    LIMIT 10
";

$result = mysqli_query($conDB, $sql);

echo "=== COMPLETED VACATIONS WITHOUT DEDUCTIONS ===\n";
echo "Checking why deductions were not applied...\n\n";

$count = 0;
while ($row = mysqli_fetch_assoc($result)) {
    $count++;
    echo "$count. Vacation ID: {$row['request_inv_no']}\n";
    echo "   Employee: {$row['emp_name']} (ID: {$row['emp_id']})\n";
    echo "   Vacation Type: {$row['vac_type']}\n";
    echo "   Days Requested: {$row['vacdays']}\n";
    echo "   Status: {$row['current_status']}\n";
    echo "   Balance Record: MISSING\n\n";
}

if ($count == 0) {
    echo "✅ No completed vacations missing deductions found!\n";
} else {
    echo "⚠️ Found $count completed vacations missing deductions.\n";
    echo "   These need manual investigation or reprocessing.\n";
}
?>
