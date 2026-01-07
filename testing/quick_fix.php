<?php
require_once __DIR__ . '/includes/db.php';

// Fix the double-deducted record
$sql = 'UPDATE emp_vacation_balance SET used_days = 12, available_balance = 35.95 WHERE id = 86';
if (mysqli_query($conDB, $sql)) {
    echo "✅ Fixed double deduction record (ID 86)\n";
    echo "Used days corrected: 24 → 12\n";
    echo "Available balance restored: 23.95 → 35.95\n";
} else {
    echo "❌ Error: " . mysqli_error($conDB) . "\n";
}

mysqli_close($conDB);
?>
