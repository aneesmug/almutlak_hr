<?php
require_once './includes/db.php';
require_once './includes/vacation_calculator.php';

$emp_id = '5456';
$calc = new VacationCalculator($conDB);

// Get the balance
$balance = $calc->getCalculatedBalance($emp_id);

// Manual calculation for verification
$joining = new DateTime('2025-10-15');
$today = new DateTime('2025-11-17');
$days_incl = $joining->diff($today)->days + 1;
$accrual_365 = $days_incl * (30 / 365.25);
$accrual_round = round($accrual_365, 2);

echo "Manual Calculation:\n";
echo "Days inclusive: $days_incl\n";
echo "Accrual (30/365.25): $accrual_365\n";
echo "Accrual rounded: $accrual_round\n\n";

echo "System Result:\n";
echo json_encode($balance, JSON_PRETTY_PRINT) . "\n";
?>
