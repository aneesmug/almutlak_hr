<?php
/**
 * Database Migration: Add/Verify inv_no column in emp_loan table
 * Run this script once to ensure the inv_no column exists
 */

require_once __DIR__ . '/includes/db.php';

echo "=== emp_loan.inv_no Column Migration ===\n\n";

// Check if inv_no column exists
$check_col = mysqli_query($conDB, "SHOW COLUMNS FROM emp_loan LIKE 'inv_no'");
$column_exists = mysqli_num_rows($check_col) > 0;

if (!$column_exists) {
    echo "❌ Column 'inv_no' does NOT exist. Adding it now...\n";
    
    // Add the column
    $add_col_sql = "ALTER TABLE `emp_loan` 
                    ADD COLUMN `inv_no` VARCHAR(50) DEFAULT NULL AFTER `id`, 
                    ADD UNIQUE INDEX `idx_inv_no` (`inv_no`)";
    
    if (mysqli_query($conDB, $add_col_sql)) {
        echo "✅ Column 'inv_no' added successfully with unique index.\n";
    } else {
        die("❌ ERROR adding column: " . mysqli_error($conDB) . "\n");
    }
} else {
    echo "✅ Column 'inv_no' already exists.\n";
    
    // Check if it has unique index
    $check_index = mysqli_query($conDB, "SHOW INDEX FROM emp_loan WHERE Column_name = 'inv_no'");
    if (mysqli_num_rows($check_index) > 0) {
        echo "✅ Unique index on 'inv_no' exists.\n";
    } else {
        echo "⚠️  Adding unique index on 'inv_no'...\n";
        if (mysqli_query($conDB, "ALTER TABLE `emp_loan` ADD UNIQUE INDEX `idx_inv_no` (`inv_no`)")) {
            echo "✅ Unique index added.\n";
        } else {
            echo "❌ ERROR adding index: " . mysqli_error($conDB) . "\n";
        }
    }
}

echo "\n=== Migration Complete ===\n";
echo "The emp_loan table is now ready to use unique invoice numbers.\n";
echo "Format: LN-YYYYMMDD-####-XXXX (e.g., LN-20251111-5127-22fa)\n\n";
echo "Next: Create a loan to test the new invoice number generation.\n";
?>
