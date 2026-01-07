<?php
/**
 * Diagnostic Tool: Daily Accrual Analysis for Employee 5430
 * 
 * This script analyzes day-by-day vacation accrual to identify why
 * the daily rate varies (sometimes 0.08, sometimes 0.09) instead of
 * being constant at 0.0833 (30/360).
 * 
 * Employee 5430: Joined 18-05-2025
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);
date_default_timezone_set('Asia/Riyadh');

require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/vacation_calculator.php';

// Employee details
$emp_id = '5430';
$joining_date_str = '2025-05-18';
$annual_vac_days = 30; // 1-year contract = 30 days
$contract_years = 1;

echo "<h1>Daily Accrual Diagnostic for Employee $emp_id</h1>\n";
echo "<p><strong>Joining Date:</strong> $joining_date_str</p>\n";
echo "<p><strong>Annual Vacation Days:</strong> $annual_vac_days</p>\n";
echo "<p><strong>Contract Period:</strong> $contract_years year(s)</p>\n";
echo "<hr>\n";

// Calculate contract period
$joining = new DateTime($joining_date_str);
$joining->setTime(0, 0, 0);
$period_start = clone $joining;
$period_end = (clone $period_start)->add(new DateInterval("P{$contract_years}Y"));
$today = new DateTime();
$today->setTime(0, 0, 0);

echo "<h2>Contract Period</h2>\n";
echo "<p>Start: " . $period_start->format('Y-m-d') . "</p>\n";
echo "<p>End: " . $period_end->format('Y-m-d') . "</p>\n";
echo "<p>Today: " . $today->format('Y-m-d') . "</p>\n";
echo "<hr>\n";

// Function to calculate 30/360 day difference (same as in VacationCalculator)
function calculate_30_360($date_start, $date_end) {
    $y1 = (int)$date_start->format('Y');
    $m1 = (int)$date_start->format('m');
    $d1 = (int)$date_start->format('d');
    
    $y2 = (int)$date_end->format('Y');
    $m2 = (int)$date_end->format('m');
    $d2 = (int)$date_end->format('d');
    
    // Adjust day 31 to 30 (standard 30/360 rule)
    if ($d1 == 31) $d1 = 30;
    if ($d2 == 31) $d2 = 30;
    
    return (($y2 - $y1) * 360) + (($m2 - $m1) * 30) + ($d2 - $d1);
}

// Function to calculate actual calendar days
function calculate_actual_days($date_start, $date_end) {
    return $date_start->diff($date_end)->days;
}

echo "<h2>Method Comparison</h2>\n";
echo "<table border='1' cellpadding='5' cellspacing='0'>\n";
echo "<tr><th>Method</th><th>Days Elapsed</th><th>Daily Rate</th><th>Earned Days</th></tr>\n";

// Method 1: Pure 30/360
$days_360 = calculate_30_360($period_start, $today);
$total_period_360 = calculate_30_360($period_start, $period_end);
$daily_rate_360 = $annual_vac_days / 360.0;
$earned_360 = round($days_360 * $daily_rate_360, 2);
echo "<tr><td>30/360 (Fixed Rate)</td><td>$days_360</td><td>" . number_format($daily_rate_360, 6) . "</td><td>$earned_360</td></tr>\n";

// Method 2: Actual/360
$days_actual = calculate_actual_days($period_start, $today);
$daily_rate_actual_360 = $annual_vac_days / 360.0;
$earned_actual_360 = round($days_actual * $daily_rate_actual_360, 2);
echo "<tr><td>Actual/360</td><td>$days_actual</td><td>" . number_format($daily_rate_actual_360, 6) . "</td><td>$earned_actual_360</td></tr>\n";

// Method 3: Actual/Actual
$total_period_actual = calculate_actual_days($period_start, $period_end);
$daily_rate_actual_actual = $annual_vac_days / $total_period_actual;
$earned_actual_actual = round($days_actual * $daily_rate_actual_actual, 2);
echo "<tr><td>Actual/Actual</td><td>$days_actual</td><td>" . number_format($daily_rate_actual_actual, 6) . "</td><td>$earned_actual_actual</td></tr>\n";

// Method 4: 30/360 with period proportion
$proportion_360 = $days_360 / $total_period_360;
$earned_proportion = round($annual_vac_days * $proportion_360, 2);
echo "<tr><td>30/360 Proportion</td><td>$days_360 / $total_period_360</td><td>Prorated</td><td>$earned_proportion</td></tr>\n";

echo "</table>\n";
echo "<hr>\n";

// Day-by-day analysis (last 30 days to today)
echo "<h2>Day-by-Day Accrual Analysis (Last 30 Days)</h2>\n";
echo "<table border='1' cellpadding='5' cellspacing='0'>\n";
echo "<tr><th>Date</th><th>Days (30/360)</th><th>Earned (30/360)</th><th>Daily Δ</th><th>Days (Actual)</th><th>Earned (Actual/360)</th><th>Daily Δ</th></tr>\n";

$start_analysis = (clone $today)->sub(new DateInterval('P30D'));
$current = clone $start_analysis;
$prev_earned_360 = null;
$prev_earned_actual = null;

while ($current <= $today) {
    $days_360_curr = calculate_30_360($period_start, $current);
    $earned_360_curr = round($days_360_curr * $daily_rate_360, 2);
    $delta_360 = $prev_earned_360 !== null ? round($earned_360_curr - $prev_earned_360, 2) : 0;
    
    $days_actual_curr = calculate_actual_days($period_start, $current);
    $earned_actual_curr = round($days_actual_curr * $daily_rate_actual_360, 2);
    $delta_actual = $prev_earned_actual !== null ? round($earned_actual_curr - $prev_earned_actual, 2) : 0;
    
    $date_str = $current->format('Y-m-d');
    $delta_360_str = $delta_360 == 0 ? '-' : number_format($delta_360, 2);
    $delta_actual_str = $delta_actual == 0 ? '-' : number_format($delta_actual, 2);
    
    // Highlight varying daily rates
    $style_360 = '';
    $style_actual = '';
    if ($delta_360 > 0.08 && $delta_360 < 0.09) {
        $style_360 = ' style="background-color: yellow;"';
    } elseif ($delta_360 >= 0.09) {
        $style_360 = ' style="background-color: orange;"';
    }
    
    if ($delta_actual > 0.08 && $delta_actual < 0.09) {
        $style_actual = ' style="background-color: yellow;"';
    } elseif ($delta_actual >= 0.09) {
        $style_actual = ' style="background-color: orange;"';
    }
    
    echo "<tr>";
    echo "<td>$date_str</td>";
    echo "<td>$days_360_curr</td>";
    echo "<td>" . number_format($earned_360_curr, 2) . "</td>";
    echo "<td$style_360>$delta_360_str</td>";
    echo "<td>$days_actual_curr</td>";
    echo "<td>" . number_format($earned_actual_curr, 2) . "</td>";
    echo "<td$style_actual>$delta_actual_str</td>";
    echo "</tr>\n";
    
    $prev_earned_360 = $earned_360_curr;
    $prev_earned_actual = $earned_actual_curr;
    $current->add(new DateInterval('P1D'));
}

echo "</table>\n";
echo "<p><em>Yellow highlight: Daily accrual ~0.08-0.09 | Orange highlight: Daily accrual ≥ 0.09</em></p>\n";
echo "<hr>\n";

// AS400-style calculation with monthly boundaries
echo "<h2>AS400-Style Monthly Calculation</h2>\n";
echo "<p>AS400 may use monthly accrual with 30-day months:</p>\n";
echo "<table border='1' cellpadding='5' cellspacing='0'>\n";
echo "<tr><th>Month</th><th>Days in Month (30/360)</th><th>Earned This Month</th><th>Cumulative Earned</th></tr>\n";

$monthly_rate = $annual_vac_days / 12.0; // 30 days / 12 months = 2.5 days/month
$cumulative = 0;
$current_month = clone $period_start;
$month_count = 0;

while ($current_month < $today && $month_count < 24) {
    $month_start = clone $current_month;
    $month_end = (clone $month_start)->add(new DateInterval('P1M'));
    
    if ($month_end > $today) {
        $month_end = clone $today;
    }
    
    $days_in_month_360 = calculate_30_360($month_start, $month_end);
    $earned_this_month = round(($days_in_month_360 / 30.0) * $monthly_rate, 2);
    $cumulative += $earned_this_month;
    
    echo "<tr>";
    echo "<td>" . $month_start->format('Y-m') . "</td>";
    echo "<td>$days_in_month_360</td>";
    echo "<td>" . number_format($earned_this_month, 2) . "</td>";
    echo "<td>" . number_format($cumulative, 2) . "</td>";
    echo "</tr>\n";
    
    $current_month = $month_end;
    $month_count++;
    
    if ($month_end >= $today) break;
}

echo "</table>\n";
echo "<hr>\n";

// Database check
echo "<h2>Database Current Balance</h2>\n";
$query = "SELECT * FROM emp_vacation_balance WHERE emp_id = ? ORDER BY id DESC LIMIT 1";
$stmt = mysqli_prepare($conDB, $query);
mysqli_stmt_bind_param($stmt, 's', $emp_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$db_balance = mysqli_fetch_assoc($result);

if ($db_balance) {
    echo "<table border='1' cellpadding='5' cellspacing='0'>\n";
    echo "<tr><th>Field</th><th>Value</th></tr>\n";
    foreach ($db_balance as $key => $value) {
        echo "<tr><td>$key</td><td>$value</td></tr>\n";
    }
    echo "</table>\n";
} else {
    echo "<p>No balance record found in database for employee $emp_id</p>\n";
}
echo "<hr>\n";

// VacationCalculator check
echo "<h2>VacationCalculator Result</h2>\n";
try {
    $calc = new VacationCalculator($conDB);
    $calc_result = $calc->getCalculatedBalance($emp_id);
    
    if ($calc_result) {
        echo "<table border='1' cellpadding='5' cellspacing='0'>\n";
        echo "<tr><th>Field</th><th>Value</th></tr>\n";
        foreach ($calc_result as $key => $value) {
            if ($value instanceof DateTime) {
                $value = $value->format('Y-m-d');
            }
            echo "<tr><td>$key</td><td>$value</td></tr>\n";
        }
        echo "</table>\n";
    } else {
        echo "<p>VacationCalculator returned null</p>\n";
    }
} catch (Exception $e) {
    echo "<p style='color:red;'>VacationCalculator Error: " . htmlspecialchars($e->getMessage()) . "</p>\n";
}

echo "<hr>\n";
echo "<h2>Conclusion & Recommendations</h2>\n";
echo "<ul>\n";
echo "<li>If daily accrual should be constant (0.0833), use <strong>30/360 Fixed Rate</strong> or <strong>Actual/360</strong></li>\n";
echo "<li>If AS400 varies daily (0.08 to 0.09), it may be using <strong>monthly accrual</strong> with partial-month calculations</li>\n";
echo "<li>Check AS400 documentation or actual payroll reports to confirm the exact method</li>\n";
echo "<li>Current VacationCalculator uses 30/360 which should give constant daily rate</li>\n";
echo "</ul>\n";

mysqli_close($conDB);
?>
