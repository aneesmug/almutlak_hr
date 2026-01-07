<?php
// Diagnostic script for employee 5430 vacation deduction issue

require_once 'includes/db.php';

echo "=== DIAGNOSTIC: Employee 5430 Vacation Deduction Issue ===\n\n";

$emp_id = 5430;

// 1. Find the vacation record
echo "1. Finding vacation record for employee $emp_id...\n";
$sql = "SELECT id, request_inv_no, emp_id, vacdays, start_date, return_date, current_status, vac_type, fly_type FROM emp_vacation WHERE emp_id = $emp_id ORDER BY id DESC LIMIT 1";
$result = mysqli_query($conDB, $sql);
if ($row = mysqli_fetch_assoc($result)) {
    echo "   Found: ID=" . $row['id'] . ", Inv#=" . $row['request_inv_no'] . "\n";
    echo "   Vacation days: " . $row['vacdays'] . "\n";
    echo "   Period: " . $row['start_date'] . " to " . $row['return_date'] . "\n";
    echo "   Status: " . $row['current_status'] . "\n";
    echo "   Type: " . $row['vac_type'] . " | Fly: " . $row['fly_type'] . "\n";
    $vac_id = $row['id'];
    $vac_start = $row['start_date'];
    $vac_end = $row['return_date'];
    $vac_days = $row['vacdays'];
} else {
    echo "   ERROR: No vacation record found!\n";
    exit;
}
mysqli_free_result($result);

// 2. Check emp_vacation_balance for this vacation
echo "\n2. Checking emp_vacation_balance...\n";
$sql = "SELECT * FROM emp_vacation_balance WHERE vac_id = $vac_id";
$result = mysqli_query($conDB, $sql);
if ($row = mysqli_fetch_assoc($result)) {
    echo "   Found balance record:\n";
    echo "   - Used days: " . $row['used_days'] . "\n";
    echo "   - Remaining: " . $row['remaining_balance'] . "\n";
    echo "   - Available: " . $row['available_balance'] . "\n";
} else {
    echo "   No balance record for this vacation ID.\n";
}
mysqli_free_result($result);

// 3. Check for holidays in the vacation period
echo "\n3. Checking emp_holidays for period " . $vac_start . " to " . $vac_end . "...\n";
$sql = "SELECT id, holiday_name, start_date, end_date, total_days, is_active FROM emp_holidays 
        WHERE is_active = 1 
        AND start_date <= '$vac_end' 
        AND end_date >= '$vac_start'
        ORDER BY start_date ASC";
$result = mysqli_query($conDB, $sql);
$holiday_count = mysqli_num_rows($result);
echo "   Found $holiday_count active holidays:\n";
$total_holiday_days = 0;
while ($row = mysqli_fetch_assoc($result)) {
    echo "   - " . $row['holiday_name'] . " (" . $row['start_date'] . " to " . $row['end_date'] . ") = " . $row['total_days'] . " days\n";
    
    // Check overlap manually
    $h_start = new DateTime($row['start_date']);
    $h_end = new DateTime($row['end_date']);
    $v_start = new DateTime($vac_start);
    $v_end = new DateTime($vac_end);
    
    $overlap_start = $v_start > $h_start ? $v_start : $h_start;
    $overlap_end = $v_end < $h_end ? $v_end : $h_end;
    
    if ($overlap_start <= $overlap_end) {
        $count = 0;
        $temp = clone $overlap_start;
        while ($temp <= $overlap_end) {
            $count++;
            $temp->modify('+1 day');
        }
        echo "     └─ Overlap with vacation: $count days\n";
        $total_holiday_days += $count;
    }
}
mysqli_free_result($result);
echo "   Total holiday days in vacation period: $total_holiday_days\n";

// 4. Calculate expected deduction
echo "\n4. Expected vs Actual Deduction:\n";
$expected_deduction = $vac_days - $total_holiday_days;
echo "   Vacation days: $vac_days\n";
echo "   Holiday days overlap: $total_holiday_days\n";
echo "   Expected deduction: $expected_deduction days\n";

// 5. Check if update_vacation_balance_on_approval was called
echo "\n5. Checking approval chain for this vacation...\n";
$sql = "SELECT * FROM request_approvers WHERE request_inv_no = (SELECT request_inv_no FROM emp_vacation WHERE id = $vac_id LIMIT 1) ORDER BY approval_level";
$result = mysqli_query($conDB, $sql);
$approvals = [];
while ($row = mysqli_fetch_assoc($result)) {
    $approvals[] = $row;
    echo "   - Level " . $row['approval_level'] . ": " . $row['approver_id'] . " [" . $row['status'] . "] - " . $row['action_date'] . "\n";
}
mysqli_free_result($result);

if (empty($approvals)) {
    echo "   No approval chain found!\n";
}

echo "\n=== END DIAGNOSTIC ===\n";
