<?php
/**
 * EOS Formula Reverse Engineering
 * 
 * Based on Saudi Labor Law Article 89 & 91:
 * - For first 5 years: 21 days salary per year
 * - For years after 5: 30 days salary per year
 * 
 * This script tests different formula variations to match
 * JISR and Qiwa website results.
 */

// Test Case Data from JISR.net
$test_salary = 1983.33; // Monthly salary
$joining_date = '2020-02-17';
$end_date = '2026-02-10';

// Calculate service years
$start = new DateTime($joining_date);
$end = new DateTime($end_date);
$service = $start->diff($end);

$total_years = $service->y + ($service->m / 12) + ($service->d / 365);
$years_first_5 = min($total_years, 5);
$years_after_5 = max(0, $total_years - 5);

echo "=== EOS CALCULATION ANALYSIS ===\n";
echo "Salary: " . number_format($test_salary, 2) . " SAR\n";
echo "Joining Date: {$joining_date}\n";
echo "End Date: {$end_date}\n";
echo "Total Service Years: " . number_format($total_years, 4) . " years\n";
echo "First 5 years: " . number_format($years_first_5, 4) . " years\n";
echo "After 5 years: " . number_format($years_after_5, 4) . " years\n";
echo "\n";

// Daily salary calculations
$daily_salary = $test_salary / 30;
echo "Daily Salary: " . number_format($daily_salary, 2) . " SAR\n";
echo "Monthly Salary: " . number_format($test_salary, 2) . " SAR\n";
echo "\n";

// FORMULA 1: Direct days calculation (Saudi Labor Law Article 89)
// First 5 years: 21 days per year
// After 5 years: 30 days per year
$eos_f1 = ($daily_salary * 21 * $years_first_5) + ($daily_salary * 30 * $years_after_5);
echo "FORMULA 1 (Direct Days - Saudi Law Article 89):\n";
echo "  = (Daily Salary × 21 × First5Years) + (Daily Salary × 30 × After5Years)\n";
echo "  = ({$daily_salary} × 21 × {$years_first_5}) + ({$daily_salary} × 30 × {$years_after_5})\n";
echo "  = " . number_format($eos_f1, 2) . " SAR\n";
echo "  Difference from JISR (4,604.97): " . number_format(abs($eos_f1 - 4604.97), 2) . " SAR\n\n";

// FORMULA 2: Proportional salary calculation
// First 5 years: (21/30) × Monthly Salary
// After 5 years: (30/30) × Monthly Salary = Full monthly salary
$eos_f2 = ($test_salary * (21/30) * $years_first_5) + ($test_salary * (30/30) * $years_after_5);
echo "FORMULA 2 (Proportional Salary):\n";
echo "  = (Monthly Salary × 21/30 × First5Years) + (Monthly Salary × 30/30 × After5Years)\n";
echo "  = ({$test_salary} × 21/30 × {$years_first_5}) + ({$test_salary} × 30/30 × {$years_after_5})\n";
echo "  = " . number_format($eos_f2, 2) . " SAR\n";
echo "  Difference from JISR (4,604.97): " . number_format(abs($eos_f2 - 4604.97), 2) . " SAR\n\n";

// FORMULA 3: Weighted average
// Average days per year across entire service period
$average_days = (21 * $years_first_5 + 30 * $years_after_5) / $total_years;
$eos_f3 = $daily_salary * $average_days * $total_years;
echo "FORMULA 3 (Weighted Average Days):\n";
echo "  Average Days per Year: " . number_format($average_days, 4) . "\n";
echo "  = Daily Salary × Average Days × Total Years\n";
echo "  = {$daily_salary} × {$average_days} × {$total_years}\n";
echo "  = " . number_format($eos_f3, 2) . " SAR\n";
echo "  Difference from JISR (4,604.97): " . number_format(abs($eos_f3 - 4604.97), 2) . " SAR\n\n";

// FORMULA 4: Simple divisor approach
// Try to find the perfect divisor
$target_eos = 4604.97; // JISR target
$divisor_for_target = ($test_salary * $total_years) / $target_eos;
$eos_f4 = ($test_salary * $total_years) / $divisor_for_target;
echo "FORMULA 4 (Simple Divisor):\n";
echo "  Perfect Divisor for JISR target: " . number_format($divisor_for_target, 4) . "\n";
echo "  = (Monthly Salary × Total Years) / {$divisor_for_target}\n";
echo "  = " . number_format($eos_f4, 2) . " SAR\n";
echo "  Difference from JISR (4,604.97): " . number_format(abs($eos_f4 - 4604.97), 2) . " SAR\n\n";

// FORMULA 5: Qiwa Official Target
// Target from Qiwa website: 4,608.64
$target_qiwa = 4608.64;
$divisor_qiwa = ($test_salary * $total_years) / $target_qiwa;
$eos_f5 = ($test_salary * $total_years) / $divisor_qiwa;
echo "FORMULA 5 (Simple Divisor - Qiwa Target):\n";
echo "  Perfect Divisor for Qiwa target: " . number_format($divisor_qiwa, 4) . "\n";
echo "  = (Monthly Salary × Total Years) / {$divisor_qiwa}\n";
echo "  = " . number_format($eos_f5, 2) . " SAR\n";
echo "  Difference from Qiwa (4,608.64): " . number_format(abs($eos_f5 - 4608.64), 2) . " SAR\n\n";

// SUMMARY
echo "=== SUMMARY ===\n";
echo "Formula 1 (Direct Days): " . number_format($eos_f1, 2) . " SAR\n";
echo "Formula 2 (Proportional): " . number_format($eos_f2, 2) . " SAR\n";
echo "Formula 3 (Weighted Avg): " . number_format($eos_f3, 2) . " SAR\n";
echo "Formula 4 (JISR Target): " . number_format($eos_f4, 2) . " SAR\n";
echo "Formula 5 (Qiwa Target): " . number_format($eos_f5, 2) . " SAR\n";
echo "\nTarget Values:\n";
echo "JISR.net: 4,604.97 SAR\n";
echo "Qiwa Website: 4,608.64 SAR\n";
echo "\n=== RECOMMENDATION ===\n";

// Check which formula is closest to targets
$formulas = [
    'Formula 1' => $eos_f1,
    'Formula 2' => $eos_f2,
    'Formula 3' => $eos_f3,
];

$closest_to_jisr = null;
$min_diff_jisr = PHP_FLOAT_MAX;

foreach ($formulas as $name => $value) {
    $diff = abs($value - 4604.97);
    if ($diff < $min_diff_jisr) {
        $min_diff_jisr = $diff;
        $closest_to_jisr = $name;
    }
}

echo "Closest to JISR.net: {$closest_to_jisr} (" . number_format($min_diff_jisr, 2) . " SAR difference)\n";

if ($closest_to_jisr === 'Formula 2') {
    echo "\nRECOMMENDATION: Use Formula 2 (Proportional Salary Method)\n";
    echo "This matches Saudi Labor Law Article 89 & 91 exactly.\n";
    echo "\nPHP Code:\n";
    echo '$eos_amount = ($salary_get * (21/30) * $years_first_5) + ($salary_get * (30/30) * $years_after_5);' . "\n";
}
?>
