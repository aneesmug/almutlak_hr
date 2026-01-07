<?php
// Test script - trace the approval flow
require_once(__DIR__ . '/includes/db.php');

$emp_id = 5430;
$vac_id = 706;

echo "=== TRACING UPDATE_VACATION_BALANCE_ON_APPROVAL ===\n\n";

// Check vacation details
$query = "SELECT id, emp_id, vacdays, start_date, return_date, current_status FROM emp_vacation WHERE emp_id = ? AND id = ?";
$stmt = $conDB->prepare($query);
$stmt->bind_param("ii", $emp_id, $vac_id);
$stmt->execute();
$result = $stmt->get_result();
$vacation = $result->fetch_assoc();

echo "Step 1: Vacation Details\n";
echo "  Vacation ID: " . $vacation['id'] . "\n";
echo "  Days Requested: " . $vacation['vacdays'] . "\n";
echo "  Start: " . $vacation['start_date'] . "\n";
echo "  End: " . $vacation['return_date'] . "\n";
echo "  Status: " . $vacation['current_status'] . "\n\n";

// Get holidays
$query = "SELECT id, holiday_name, total_days FROM emp_holidays WHERE is_active = 1 AND start_date <= ? AND end_date >= ?";
$stmt = $conDB->prepare($query);
$stmt->bind_param("ss", $vacation['return_date'], $vacation['start_date']);
$stmt->execute();
$result = $stmt->get_result();

echo "Step 2: Holidays in Period\n";
$total_holiday_days = 0;
while ($holiday = $result->fetch_assoc()) {
    echo "  " . $holiday['holiday_name'] . ": " . $holiday['total_days'] . " days\n";
    $total_holiday_days += (float)$holiday['total_days'];
}
echo "  Total Holiday Days: " . $total_holiday_days . "\n\n";

// Calculate days to deduct (same as the function does)
$days_to_deduct = max(0, (float)$vacation['vacdays'] - $total_holiday_days);
echo "Step 3: Deduction Calculation\n";
echo "  Vacation Days: " . $vacation['vacdays'] . "\n";
echo "  Holiday Days: " . $total_holiday_days . "\n";
echo "  Days to Deduct: " . $days_to_deduct . "\n\n";

// Get the current balance record
$query = "SELECT * FROM emp_vacation_balance WHERE emp_id = ? ORDER BY id DESC LIMIT 1";
$stmt = $conDB->prepare($query);
$stmt->bind_param("i", $emp_id);
$stmt->execute();
$result = $stmt->get_result();
$current_balance = $result->fetch_assoc();

echo "Step 4: Current Balance Record\n";
if ($current_balance) {
    echo "  Used Days (before): " . $current_balance['used_days'] . "\n";
    echo "  Remaining (before): " . $current_balance['remaining_balance'] . "\n";
    echo "  Total Days: " . $current_balance['total_days'] . "\n";
    echo "  Carryover: " . $current_balance['carryover_days'] . "\n\n";
    
    // Simulate the update
    $old_used_days = (float)$current_balance['used_days'];
    $carryover_days = (float)$current_balance['carryover_days'];
    $total_contract_days = (float)$current_balance['total_days'];
    
    $new_used_days = $old_used_days + $days_to_deduct;
    $max_allowable = $total_contract_days + $carryover_days;
    
    if ($new_used_days > $max_allowable) {
        $new_used_days = $max_allowable;
    }
    
    $new_remaining_balance = $max_allowable - $new_used_days;
    
    echo "Step 5: NEW Values (After Update)\n";
    echo "  Old Used Days: " . $old_used_days . "\n";
    echo "  Days to Add: " . $days_to_deduct . "\n";
    echo "  New Used Days: " . $new_used_days . "\n";
    echo "  New Remaining: " . $new_remaining_balance . "\n";
    echo "  Max Allowable (total + carryover): " . $max_allowable . "\n";
} else {
    echo "  No balance record found!\n";
}

$conDB->close();
?>
