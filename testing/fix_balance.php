<?php
// Fix the balance record for vacation 706
require_once(__DIR__ . '/includes/db.php');

$emp_id = 5430;
$vac_id = 706;

echo "=== FIXING BALANCE RECORD FOR VACATION 706 ===\n\n";

// Get the vacation details
$query = "SELECT vacdays, start_date, return_date FROM emp_vacation WHERE id = ? AND emp_id = ?";
$stmt = $conDB->prepare($query);
$stmt->bind_param("ii", $vac_id, $emp_id);
$stmt->execute();
$result = $stmt->get_result();
$vacation = $result->fetch_assoc();

echo "Vacation: " . $vacation['vacdays'] . " days\n";
echo "Period: " . $vacation['start_date'] . " to " . $vacation['return_date'] . "\n\n";

// Get holidays
$query = "SELECT total_days FROM emp_holidays WHERE is_active = 1 AND start_date <= ? AND end_date >= ?";
$stmt = $conDB->prepare($query);
$stmt->bind_param("ss", $vacation['return_date'], $vacation['start_date']);
$stmt->execute();
$result = $stmt->get_result();

$total_holiday_days = 0;
while ($row = $result->fetch_assoc()) {
    $total_holiday_days += (float)$row['total_days'];
}

$days_to_deduct = max(0, (float)$vacation['vacdays'] - $total_holiday_days);

echo "Holiday days: " . $total_holiday_days . "\n";
echo "Days to deduct: " . $days_to_deduct . "\n\n";

// Get current balance
$query = "SELECT * FROM emp_vacation_balance WHERE vac_id = ?";
$stmt = $conDB->prepare($query);
$stmt->bind_param("i", $vac_id);
$stmt->execute();
$result = $stmt->get_result();
$balance = $result->fetch_assoc();

echo "Current balance record:\n";
echo "  used_days: " . $balance['used_days'] . "\n";
echo "  remaining_balance: " . $balance['remaining_balance'] . "\n";
echo "  total_days: " . $balance['total_days'] . "\n";
echo "  carryover_days: " . $balance['carryover_days'] . "\n\n";

// Calculate correct values
$total_contract_days = (float)$balance['total_days'];
$carryover = (float)$balance['carryover_days'];
$max_allowable = $total_contract_days + $carryover;

// NEW calculation: correct used_days should be 4, not 5
// But we need to account for OTHER vacations. Let's check:
$query = "SELECT SUM(vacdays) as total_other_vacations FROM emp_vacation 
          WHERE emp_id = ? AND id != ? AND current_status IN ('approved', 'gm_approved')
          AND (vac_type = 'Fly' OR vac_type = 'Local Vacation')";
$stmt = $conDB->prepare($query);
$stmt->bind_param("ii", $emp_id, $vac_id);
$stmt->execute();
$result = $stmt->get_result();
$other = $result->fetch_assoc();
$other_vacations = (float)($other['total_other_vacations'] ?? 0);

echo "Other approved vacations (total days): " . $other_vacations . "\n";
echo "This vacation (corrected deduction): " . $days_to_deduct . "\n";

$new_used_days = $other_vacations + $days_to_deduct;
$new_remaining = $max_allowable - $new_used_days;

echo "\nCorrected values:\n";
echo "  new_used_days: " . $new_used_days . "\n";
echo "  new_remaining_balance: " . $new_remaining . "\n\n";

// UPDATE the balance record
$query = "UPDATE emp_vacation_balance SET used_days = ?, remaining_balance = ?, available_balance = ?, last_updated = NOW() WHERE vac_id = ?";
$stmt = $conDB->prepare($query);
$stmt->bind_param("dddi", $new_used_days, $new_remaining, $new_remaining, $vac_id);

if ($stmt->execute()) {
    echo "✓ Balance record updated successfully\n";
    
    // Verify
    $query = "SELECT used_days, remaining_balance FROM emp_vacation_balance WHERE vac_id = ?";
    $stmt = $conDB->prepare($query);
    $stmt->bind_param("i", $vac_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $verify = $result->fetch_assoc();
    
    echo "\nVerification:\n";
    echo "  used_days: " . $verify['used_days'] . " (should be 4.00)\n";
    echo "  remaining_balance: " . $verify['remaining_balance'] . " (should be 13.53)\n";
} else {
    echo "✗ Error updating balance record\n";
}

$conDB->close();
?>
