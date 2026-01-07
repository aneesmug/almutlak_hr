<?php
/**
 * AS400 Variance Test
 * Tests why daily accrual varies between 0.08 and 0.09
 * Employee 5430: Joined 2025-05-18, 30 days annual
 */

date_default_timezone_set('Asia/Riyadh');

$emp_join = new DateTime('2025-05-18');
$emp_join->setTime(0, 0, 0);
$annual_days = 30;

echo "=== AS400 Daily Accrual Variance Analysis ===\n\n";
echo "Employee Joining Date: " . $emp_join->format('Y-m-d') . "\n";
echo "Annual Vacation Days: $annual_days\n";
echo "Expected Daily Rate (30/360): " . number_format($annual_days / 360, 6) . "\n\n";

function calc_30_360($start, $end) {
    $y1 = (int)$start->format('Y');
    $m1 = (int)$start->format('m');
    $d1 = (int)$start->format('d');
    
    $y2 = (int)$end->format('Y');
    $m2 = (int)$end->format('m');
    $d2 = (int)$end->format('d');
    
    // Standard 30/360 without adjustment
    return (($y2 - $y1) * 360) + (($m2 - $m1) * 30) + ($d2 - $d1);
}

function calc_30_360_adjusted($start, $end) {
    $y1 = (int)$start->format('Y');
    $m1 = (int)$start->format('m');
    $d1 = (int)$start->format('d');
    
    $y2 = (int)$end->format('Y');
    $m2 = (int)$end->format('m');
    $d2 = (int)$end->format('d');
    
    // Adjust day 31 to 30
    if ($d1 == 31) $d1 = 30;
    if ($d2 == 31) $d2 = 30;
    
    return (($y2 - $y1) * 360) + (($m2 - $m1) * 30) + ($d2 - $d1);
}

echo "Day-by-Day from Jan 1 to Jan 10, 2026:\n";
echo str_repeat('-', 120) . "\n";
printf("%-12s | %-10s | %-12s | %-10s | %-12s | %-10s | %-10s\n", 
    "Date", "Days(360)", "Earned(360)", "Daily Δ", "Days(360-Adj)", "Earned(Adj)", "Daily Δ");
echo str_repeat('-', 120) . "\n";

$start_date = new DateTime('2026-01-01');
$start_date->setTime(0, 0, 0);
$end_date = new DateTime('2026-01-10');

$current = clone $start_date;
$prev_earned = null;
$prev_earned_adj = null;

while ($current <= $end_date) {
    $days_360 = calc_30_360($emp_join, $current);
    $earned_360 = $days_360 * ($annual_days / 360);
    $delta = $prev_earned !== null ? ($earned_360 - $prev_earned) : 0;
    
    $days_360_adj = calc_30_360_adjusted($emp_join, $current);
    $earned_adj = $days_360_adj * ($annual_days / 360);
    $delta_adj = $prev_earned_adj !== null ? ($earned_adj - $prev_earned_adj) : 0;
    
    printf("%-12s | %10d | %12.4f | %10.4f | %12d | %12.4f | %10.4f\n",
        $current->format('Y-m-d'),
        $days_360,
        $earned_360,
        $delta,
        $days_360_adj,
        $earned_adj,
        $delta_adj
    );
    
    $prev_earned = $earned_360;
    $prev_earned_adj = $earned_adj;
    $current->add(new DateInterval('P1D'));
}

echo "\n\n=== Month-End Effects ===\n";
echo "Testing days around month boundaries:\n\n";

$test_dates = [
    ['2025-05-30', '2025-05-31', 'May 30-31'],
    ['2025-05-31', '2025-06-01', 'May 31 to Jun 1'],
    ['2025-06-29', '2025-06-30', 'Jun 29-30'],
    ['2025-06-30', '2025-07-01', 'Jun 30 to Jul 1'],
    ['2025-12-30', '2025-12-31', 'Dec 30-31'],
    ['2025-12-31', '2026-01-01', 'Dec 31 to Jan 1'],
];

printf("%-20s | %-10s | %-12s | %-10s\n", "Period", "Days(360)", "Days(360-Adj)", "Difference");
echo str_repeat('-', 60) . "\n";

foreach ($test_dates as $test) {
    $d1 = new DateTime($test[0]);
    $d2 = new DateTime($test[1]);
    $days_360 = calc_30_360($d1, $d2);
    $days_adj = calc_30_360_adjusted($d1, $d2);
    $diff = $days_adj - $days_360;
    
    printf("%-20s | %10d | %12d | %10d\n", $test[2], $days_360, $days_adj, $diff);
}

echo "\n\n=== AS400 Probable Method ===\n";
echo "If AS400 varies between 0.08 and 0.09, it likely uses one of these methods:\n\n";
echo "1. Monthly Accrual (2.5 days/month, calculated daily within month)\n";
echo "2. Actual/360 (actual calendar days / 360)\n";
echo "3. Modified 30/360 with special rules for month-end\n";
echo "4. Bi-weekly or payroll-period based accrual\n\n";

echo "Recommendation:\n";
echo "- Check actual AS400 report for employee 5430\n";
echo "- Compare earned days on specific dates with our calculation\n";
echo "- Identify the exact accrual rule AS400 uses\n";
echo "- Update VacationCalculator to match AS400 exactly\n";
?>
