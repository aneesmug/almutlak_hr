<?php
/**
 * Deep analysis of EOS calculation patterns
 * Testing multiple test cases to find the underlying formula
 */

// Multiple test cases to identify the pattern
$test_cases = [
    [
        'salary' => 1983.33,
        'joining' => '2020-02-17',
        'end' => '2026-02-10',
        'jisr_expected' => 4604.97,
        'qiwa_expected' => 4608.64,
        'description' => 'Original test case'
    ],
    [
        'salary' => 2000,
        'joining' => '2020-01-01',
        'end' => '2026-01-01',
        'jisr_expected' => null, // Will calculate
        'qiwa_expected' => null,
        'description' => 'Round numbers: 6 years, 2000 SAR'
    ],
    [
        'salary' => 5000,
        'joining' => '2021-01-01',
        'end' => '2026-01-01',
        'jisr_expected' => null,
        'qiwa_expected' => null,
        'description' => 'Higher salary: 5 years, 5000 SAR'
    ],
];

echo "=== DEEP EOS FORMULA ANALYSIS ===\n\n";

foreach ($test_cases as $case) {
    echo "TEST CASE: " . $case['description'] . "\n";
    echo "Salary: " . $case['salary'] . " SAR\n";
    echo "Period: " . $case['joining'] . " to " . $case['end'] . "\n";
    
    $start = new DateTime($case['joining']);
    $end = new DateTime($case['end']);
    $diff = $start->diff($end);
    
    $total_years = $diff->y + ($diff->m / 12) + ($diff->d / 365);
    
    echo "Service Years: " . number_format($total_years, 4) . "\n";
    
    // Calculate expected divisor from JISR/Qiwa value
    if ($case['jisr_expected'] !== null) {
        $divisor_jisr = ($case['salary'] * $total_years) / $case['jisr_expected'];
        echo "Divisor (JISR): " . number_format($divisor_jisr, 4) . "\n";
    }
    
    if ($case['qiwa_expected'] !== null) {
        $divisor_qiwa = ($case['salary'] * $total_years) / $case['qiwa_expected'];
        echo "Divisor (Qiwa): " . number_format($divisor_qiwa, 4) . "\n";
    }
    
    // Analysis: Maybe the formula is based on monthly salary calculation differently
    // Let's test: EOS = (Salary × Service Years) / Constant
    
    // Theory 1: It's a half-month calculation
    $half_month_rate = (30 / 2) / 30; // Half of monthly days
    $eos_half = $case['salary'] * $half_month_rate * $total_years;
    echo "Theory 1 - Half Month (Salary × 0.5 × Years): " . number_format($eos_half, 2) . "\n";
    
    // Theory 2: It's using a specific percentage
    $eos_percentage = ($case['salary'] * $total_years) * (1 / 2.576);
    echo "Theory 2 - Fixed divisor 2.576: " . number_format($eos_percentage, 2) . "\n";
    
    // Theory 3: Checking if it's monthly salary calculation
    // Maybe: (Monthly Salary / 30) × Days, but Days is calculated differently
    // If JISR/Qiwa return ~4600 for ~6 years of service
    // Then days per year average = (4600 / salary) / years * 30
    if ($case['jisr_expected'] !== null) {
        $avg_days_jisr = ($case['jisr_expected'] / $case['salary']) / $total_years * 30;
        echo "Theory 3 - Average days per year (JISR): " . number_format($avg_days_jisr, 2) . " days/year\n";
    }
    
    echo "\n";
}

echo "=== ANALYSIS ===\n";
echo "From the test case, we can see:\n";
echo "- JISR divisor: 2.5766\n";
echo "- Qiwa divisor: 2.5745\n";
echo "- These are nearly identical (0.08% difference)\n";
echo "- The divisor ~2.57 suggests a formula like: (Salary × Years) / 2.57\n";
echo "\nThis is equivalent to: Salary × Years × (1/2.57) = Salary × Years × 0.389\n";
echo "\nWhich means EOS = ~38.9% of (Salary × Years)\n";
echo "\nOr: EOS = Salary × Years × (1/2.57)\n";
echo "Or: EOS = Salary × (Years / 2.57)\n";
echo "Or: EOS = (Salary / 2.57) × Years\n";

// More analysis
echo "\n=== HYPOTHESIS ===\n";
echo "The formula might be related to a statutory requirement or international standard\n";
echo "not the Saudi Labor Law standard which gives 8,890.12\n";
echo "\nPossible explanations:\n";
echo "1. The Qiwa API uses a different calculation method than their website\n";
echo "2. JISR and Qiwa websites follow a specific international standard\n";
echo "3. There's a middle ground calculation for grievance/dispute cases\n";
echo "4. The 2.57 divisor is a standardized factor in HR calculations\n";
?>
