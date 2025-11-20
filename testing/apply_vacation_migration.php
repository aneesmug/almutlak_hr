<?php
/**
 * Apply database migration for vacation salary type and fix invalid dates
 */

require_once 'includes/db.php';

echo "=== Applying Vacation Database Migration ===\n\n";

// Step 1: Modify vacation_salary_type enum to be nullable
echo "Step 1: Making vacation_salary_type nullable...\n";
$sql1 = "ALTER TABLE `emp_vacation` 
         MODIFY COLUMN `vacation_salary_type` ENUM('payroll', 'end_of_service') NULL DEFAULT NULL";
         
if (mysqli_query($conDB, $sql1)) {
    echo "✅ Successfully made vacation_salary_type nullable\n";
} else {
    echo "❌ Error modifying enum: " . mysqli_error($conDB) . "\n";
}

// Step 2: Fix '0000-00-00' departure dates
echo "\nStep 2: Fixing invalid departure dates...\n";
$sql2 = "UPDATE `emp_vacation` 
         SET `departure_date` = NULL 
         WHERE `departure_date` = '0000-00-00'";
         
if (mysqli_query($conDB, $sql2)) {
    $affected = mysqli_affected_rows($conDB);
    echo "✅ Fixed $affected departure_date records\n";
} else {
    echo "❌ Error fixing departure dates: " . mysqli_error($conDB) . "\n";
}

// Step 3: Fix '0000-00-00' arrival dates
echo "\nStep 3: Fixing invalid arrival dates...\n";
$sql3 = "UPDATE `emp_vacation` 
         SET `arrival_date` = NULL 
         WHERE `arrival_date` = '0000-00-00'";
         
if (mysqli_query($conDB, $sql3)) {
    $affected = mysqli_affected_rows($conDB);
    echo "✅ Fixed $affected arrival_date records\n";
} else {
    echo "❌ Error fixing arrival dates: " . mysqli_error($conDB) . "\n";
}

// Verification
echo "\n=== Verification ===\n";

// Check for remaining '0000-00-00' dates
$check1 = mysqli_query($conDB, "SELECT COUNT(*) as count FROM emp_vacation WHERE departure_date = '0000-00-00'");
$result1 = mysqli_fetch_assoc($check1);
echo "Departure dates with '0000-00-00': {$result1['count']}\n";

$check2 = mysqli_query($conDB, "SELECT COUNT(*) as count FROM emp_vacation WHERE arrival_date = '0000-00-00'");
$result2 = mysqli_fetch_assoc($check2);
echo "Arrival dates with '0000-00-00': {$result2['count']}\n";

// Check enum
$check3 = mysqli_query($conDB, "SHOW COLUMNS FROM emp_vacation LIKE 'vacation_salary_type'");
$result3 = mysqli_fetch_assoc($check3);
echo "\nVacation salary type enum: {$result3['Type']}\n";
echo "Nullable: {$result3['Null']}\n";
echo "Default: " . ($result3['Default'] ?? 'NULL') . "\n";

if ($result3['Null'] === 'YES') {
    echo "\n✅ Migration completed successfully!\n";
} else {
    echo "\n❌ Migration incomplete - column is not nullable\n";
}

echo "\n=== Migration Complete ===\n";
?>
