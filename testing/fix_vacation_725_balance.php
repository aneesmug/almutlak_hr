<?php
/**
 * CORRECTION SCRIPT: Fix Balance for Vacation ID 725
 * 
 * This script corrects the balance record for vacation ID 725 which was
 * created before the fix was applied. The balance should have been deducted
 * but wasn't due to the bug we just fixed.
 */

require_once __DIR__ . '/../includes/db.php';

echo "=================================================================\n";
echo "CORRECTING BALANCE FOR VACATION ID 725\n";
echo "=================================================================\n\n";

$vacation_id = 725;

// Get vacation details
$sql_vac = "SELECT v.*, e.name as employee_name 
            FROM emp_vacation v 
            LEFT JOIN employees e ON v.emp_id = e.emp_id 
            WHERE v.id = ?";
$stmt_vac = mysqli_prepare($conDB, $sql_vac);
mysqli_stmt_bind_param($stmt_vac, "i", $vacation_id);
mysqli_stmt_execute($stmt_vac);
$result_vac = mysqli_stmt_get_result($stmt_vac);
$vacation = mysqli_fetch_assoc($result_vac);
mysqli_stmt_close($stmt_vac);

if (!$vacation) {
    die("ERROR: Vacation ID $vacation_id not found!\n");
}

echo "Vacation Details:\n";
echo "  Invoice: {$vacation['request_inv_no']}\n";
echo "  Employee: {$vacation['employee_name']} ({$vacation['emp_id']})\n";
echo "  Type: {$vacation['vac_type']} | {$vacation['fly_type']}\n";
echo "  Days: {$vacation['vacdays']}\n";
echo "  Status: {$vacation['current_status']}\n\n";

// Get current balance record
$sql_bal = "SELECT * FROM emp_vacation_balance WHERE vac_id = ?";
$stmt_bal = mysqli_prepare($conDB, $sql_bal);
mysqli_stmt_bind_param($stmt_bal, "i", $vacation_id);
mysqli_stmt_execute($stmt_bal);
$result_bal = mysqli_stmt_get_result($stmt_bal);
$balance = mysqli_fetch_assoc($result_bal);
mysqli_stmt_close($stmt_bal);

if (!$balance) {
    die("ERROR: Balance record not found for vacation ID $vacation_id!\n");
}

echo "CURRENT Balance Values:\n";
echo "  Record ID: {$balance['id']}\n";
echo "  Total Days: {$balance['total_days']}\n";
echo "  Used Days: {$balance['used_days']}\n";
echo "  Remaining: {$balance['remaining_balance']}\n";
echo "  Available: {$balance['available_balance']}\n\n";

// Calculate correct values
// The employee had 46.83 days available
// Used 46 days for this vacation
// Should have 0.83 days remaining

$original_available = 46.83; // This was the opening balance
$days_used = (float)$vacation['vacdays']; // 46 days
$correct_remaining = $original_available - $days_used; // 0.83 days
$correct_total_days = $correct_remaining; // total_days represents opening balance after deduction

echo "CORRECTED Balance Values:\n";
echo "  Total Days: $correct_total_days (opening balance after deduction)\n";
echo "  Used Days: $days_used (unchanged)\n";
echo "  Remaining: $correct_remaining\n";
echo "  Available: $correct_remaining\n\n";

// Ask for confirmation
echo "Do you want to apply this correction? (yes/no): ";
$handle = fopen("php://stdin", "r");
$line = trim(fgets($handle));
fclose($handle);

if (strtolower($line) !== 'yes' && strtolower($line) !== 'y') {
    echo "\nCorrection CANCELLED.\n";
    exit(0);
}

// Apply the correction
$sql_update = "UPDATE emp_vacation_balance 
               SET total_days = ?, 
                   remaining_balance = ?, 
                   available_balance = ?,
                   last_updated = NOW()
               WHERE vac_id = ?";

$stmt_update = mysqli_prepare($conDB, $sql_update);
if (!$stmt_update) {
    die("ERROR: Failed to prepare update statement: " . mysqli_error($conDB) . "\n");
}

mysqli_stmt_bind_param($stmt_update, "dddi", $correct_total_days, $correct_remaining, $correct_remaining, $vacation_id);

if (mysqli_stmt_execute($stmt_update)) {
    $affected = mysqli_stmt_affected_rows($stmt_update);
    echo "\n✓ SUCCESS! Balance record updated. Rows affected: $affected\n\n";
    
    // Verify the update
    $stmt_verify = mysqli_prepare($conDB, $sql_bal);
    mysqli_stmt_bind_param($stmt_verify, "i", $vacation_id);
    mysqli_stmt_execute($stmt_verify);
    $result_verify = mysqli_stmt_get_result($stmt_verify);
    $updated_balance = mysqli_fetch_assoc($result_verify);
    mysqli_stmt_close($stmt_verify);
    
    echo "VERIFIED Updated Balance:\n";
    echo "  Total Days: {$updated_balance['total_days']}\n";
    echo "  Used Days: {$updated_balance['used_days']}\n";
    echo "  Remaining: {$updated_balance['remaining_balance']}\n";
    echo "  Available: {$updated_balance['available_balance']}\n";
    echo "  Last Updated: {$updated_balance['last_updated']}\n";
} else {
    echo "\n✗ ERROR: Failed to update balance: " . mysqli_stmt_error($stmt_update) . "\n";
}

mysqli_stmt_close($stmt_update);
mysqli_close($conDB);

echo "\n=================================================================\n";
?>
