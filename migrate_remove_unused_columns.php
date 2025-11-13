<?php
require_once __DIR__ . '/includes/db.php';

echo "=== Removing Unused Columns from emp_loan Table ===\n\n";

// First, create a backup
echo "Step 1: Creating backup table...\n";
$backup_sql = "CREATE TABLE emp_loan_backup_20251112 AS SELECT * FROM emp_loan";
if (mysqli_query($conDB, $backup_sql)) {
    $count = mysqli_query($conDB, "SELECT COUNT(*) as cnt FROM emp_loan_backup_20251112");
    $row = mysqli_fetch_assoc($count);
    echo "✅ Backup created successfully ({$row['cnt']} records)\n\n";
} else {
    die("❌ Failed to create backup: " . mysqli_error($conDB) . "\n");
}

// Show current structure
echo "Step 2: Current table structure:\n";
$result = mysqli_query($conDB, "DESCRIBE emp_loan");
$columns_before = [];
while ($row = mysqli_fetch_assoc($result)) {
    $columns_before[] = $row['Field'];
    echo "  - {$row['Field']} ({$row['Type']})\n";
}
echo "\nTotal columns before: " . count($columns_before) . "\n\n";

// Remove unused columns
echo "Step 3: Removing unused columns...\n";
$columns_to_remove = [
    'approved_by_user_ids',
    'modified_by',
    'modification_note',
    'original_amount',
    'original_installments'
];

$alter_parts = [];
foreach ($columns_to_remove as $col) {
    $alter_parts[] = "DROP COLUMN `{$col}`";
}

$alter_sql = "ALTER TABLE `emp_loan` " . implode(", ", $alter_parts);

if (mysqli_query($conDB, $alter_sql)) {
    echo "✅ Columns removed successfully\n\n";
} else {
    die("❌ Failed to remove columns: " . mysqli_error($conDB) . "\n");
}

// Show new structure
echo "Step 4: New table structure:\n";
$result = mysqli_query($conDB, "DESCRIBE emp_loan");
$columns_after = [];
while ($row = mysqli_fetch_assoc($result)) {
    $columns_after[] = $row['Field'];
    echo "  - {$row['Field']} ({$row['Type']})\n";
}
echo "\nTotal columns after: " . count($columns_after) . "\n\n";

// Summary
echo "=== Summary ===\n";
echo "Columns before: " . count($columns_before) . "\n";
echo "Columns after: " . count($columns_after) . "\n";
echo "Columns removed: " . (count($columns_before) - count($columns_after)) . "\n\n";

echo "Removed columns:\n";
foreach ($columns_to_remove as $col) {
    echo "  - {$col}\n";
}

echo "\n✅ Migration completed successfully!\n";
echo "Backup table: emp_loan_backup_20251112\n";

$conDB->close();
?>
