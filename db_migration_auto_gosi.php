<?php
// Quick database migration for auto_gosi_deduction feature
$conn = new mysqli('localhost', 'root', 'admin123', 'almutlak_db');
if ($conn->connect_error) {
    die('Connection error: ' . $conn->connect_error);
}

echo "=== Vacation Adjustment Database Migration ===\n\n";

// Check if other_deductions column exists
$result = $conn->query("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME='emp_vacation' AND COLUMN_NAME='other_deductions'");
if ($result && $result->num_rows == 0) {
    echo "Adding other_deductions column...";
    if ($conn->query("ALTER TABLE emp_vacation ADD COLUMN other_deductions decimal(10,2) DEFAULT 0.00 COMMENT 'Other deductions (manual entry)' AFTER other_earnings")) {
        echo " ✓ Success\n";
    } else {
        echo " ✗ Error: " . $conn->error . "\n";
    }
} else {
    echo "other_deductions column already exists\n";
}

// Check if auto_gosi_deduction column exists
$result = $conn->query("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME='emp_vacation' AND COLUMN_NAME='auto_gosi_deduction'");
if ($result && $result->num_rows == 0) {
    echo "Adding auto_gosi_deduction column...";
    if ($conn->query("ALTER TABLE emp_vacation ADD COLUMN auto_gosi_deduction tinyint(1) DEFAULT 1 COMMENT 'Auto GOSI deduction flag: 1=auto deduct GOSI, 0=manual/no deduction' AFTER other_deductions")) {
        echo " ✓ Success\n";
    } else {
        echo " ✗ Error: " . $conn->error . "\n";
    }
} else {
    echo "auto_gosi_deduction column already exists\n";
}

echo "\n=== Migration Complete ===\n";
$conn->close();
?>
