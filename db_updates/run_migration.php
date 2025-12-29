<?php
/**
 * Database Migration: Add Payment Workflow Fields
 * This script adds the payment tracking columns to emp_vacation table
 * Run this once to initialize the two-step HR Payroll workflow
 */

// Direct DB connection without session checks (for CLI execution)
$mysqli = new mysqli('localhost', 'root', 'admin123', 'almutlak_db');

if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}

echo "Starting migration: Add Payment Workflow Fields to emp_vacation\n";
echo "=" . str_repeat("=", 75) . "\n";

$queries = [
    "ALTER TABLE `emp_vacation` 
     ADD COLUMN `payment_status` ENUM('pending_payment', 'paid', 'needs_modification') 
     DEFAULT 'pending_payment' 
     COMMENT 'Payment status for final HR Payroll approval step' 
     AFTER `payroll_note`" 
    => "Add payment_status column",
    
    "ALTER TABLE `emp_vacation` 
     ADD COLUMN `payment_date` DATETIME NULL 
     DEFAULT NULL 
     COMMENT 'Timestamp when payment was processed'
     AFTER `payment_status`"
    => "Add payment_date column",
    
    "ALTER TABLE `emp_vacation` 
     ADD COLUMN `payment_modified_date` DATETIME NULL 
     DEFAULT NULL 
     COMMENT 'Timestamp when payment was last modified'
     AFTER `payment_date`"
    => "Add payment_modified_date column",
    
    "ALTER TABLE `emp_vacation` 
     ADD COLUMN `payment_modified_by` VARCHAR(50) NULL 
     DEFAULT NULL 
     COMMENT 'Employee ID of user who modified payment'
     AFTER `payment_modified_date`"
    => "Add payment_modified_by column",
    
    "ALTER TABLE `emp_vacation` 
     ADD COLUMN `is_payment_completed` TINYINT(1) DEFAULT 0 
     COMMENT 'Flag: 0=payment pending, 1=payment processing complete'
     AFTER `payment_modified_by`"
    => "Add is_payment_completed flag",
    
    "ALTER TABLE `emp_vacation` 
     ADD INDEX `idx_payment_status` (`payment_status`)"
    => "Create index on payment_status"
];

$success_count = 0;
$skip_count = 0;
$error_count = 0;

foreach ($queries as $query => $description) {
    echo "\n→ $description ... ";
    
    try {
        if ($mysqli->query($query)) {
            echo "✓ OK\n";
            $success_count++;
        }
    } catch (mysqli_sql_exception $e) {
        if (strpos($e->getMessage(), 'Duplicate column') !== false || 
            strpos($e->getMessage(), 'Duplicate key') !== false) {
            echo "⊝ SKIPPED (already exists)\n";
            $skip_count++;
        } else {
            echo "✗ ERROR: " . $e->getMessage() . "\n";
            $error_count++;
        }
    }
}

echo "\n" . "=" . str_repeat("=", 75) . "\n";
echo "Migration Summary:\n";
echo "  ✓ Successful: $success_count\n";
echo "  ⊝ Skipped: $skip_count\n";
echo "  ✗ Errors: $error_count\n";

// Verify the columns exist
echo "\n" . "=" . str_repeat("=", 75) . "\n";
echo "Verifying new columns:\n";

$verify_result = $mysqli->query('SELECT COLUMN_NAME, COLUMN_TYPE FROM INFORMATION_SCHEMA.COLUMNS 
                                 WHERE TABLE_SCHEMA="almutlak_db" AND TABLE_NAME="emp_vacation" 
                                 AND COLUMN_NAME IN ("payment_status", "payment_date", "payment_modified_date", "payment_modified_by", "is_payment_completed")
                                 ORDER BY ORDINAL_POSITION');

$found_count = 0;
while ($row = $verify_result->fetch_assoc()) {
    echo "  ✓ " . $row['COLUMN_NAME'] . " (" . $row['COLUMN_TYPE'] . ")\n";
    $found_count++;
}

echo "\n";
if ($found_count === 5) {
    echo "✓ SUCCESS! All 5 payment workflow fields are now available.\n";
    echo "   You can now implement the two-step HR Payroll approval workflow.\n";
} else {
    echo "⚠ WARNING: Only $found_count of 5 expected columns were found.\n";
    echo "   Check the migration output above for details.\n";
}

$mysqli->close();
?>
