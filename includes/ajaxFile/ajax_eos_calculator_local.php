<?php
/****************************************************************
 * EOS CALCULATOR - LOCAL IMPLEMENTATION (100% QIWA-COMPATIBLE)
 * Based on Saudi Arabia Labor Law (Articles 80-85 of Labor Code)
 * 
 * SERVICE CALCULATION (360-day year basis):
 * Days = (Days in joining month, inclusive) 
 *      + (Full complete months × 30)
 *      + (Days in end month)
 * Service Years = Days / 360
 *
 * BASE REWARD (Saudi Labor Law - Article 84):
 * - First 5 years: half-month salary per year
 * - Beyond 5 years: one-month salary per year
 *
 * Then apply reason-specific entitlement rules.
 * 
 * ENTITLEMENT RULES:
 * - Resignation (code 1):
 *   <2 years = 0
 *   >=2 and <5 years = 1/3 of reward
 *   >=5 and <10 years = 2/3 of reward
 *   >=10 years = full reward
 * - Article 80 / probation termination codes = 0
 * - Other termination reasons = full reward
 * 
 * VERIFIED AGAINST QIWA OFFICIAL CALCULATOR:
 * ✓ 2023-10-25 to 2026-06-11, Resignation, 2,487.50 SAR = 1,091.74 SAR
 * ✓ 2025-05-18 to 2026-06-11, End of Contract, 9,000 SAR = 4,812.50 SAR
 * ✓ 2009-05-29 to 2026-06-15, contract expiration, 6,500 SAR = 94,575.00 SAR
 * 
 * Replaces external Qiwa API (WAF-blocked) with local Saudi Arabia labor law calculation
 ****************************************************************/

header('Content-Type: application/json');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

// Parse all input sources (GET, POST, REQUEST)
$requestData = array_merge($_GET, $_POST, $_REQUEST);

if (empty($requestData)) {
    $rawInput = file_get_contents('php://input');
    if (!empty($rawInput)) {
        $jsonData = json_decode($rawInput, true);
        if (is_array($jsonData)) {
            $requestData = $jsonData;
        } else {
            parse_str($rawInput, $requestData);
        }
    }
}

$response = [
    'success' => false,
    'eos_amount' => '0.00',
    'vacation_salary' => '0.00',
    'net_payment' => '0.00',
    'message' => 'Invalid input.'
];

if (empty($requestData)) {
    echo json_encode($response);
    exit;
}

// Validate and sanitize inputs using same method as ajax_eos_calculator.php
$contractType = trim((string)($requestData['contract_type'] ?? ''));
$selectedReasonCode = trim((string)($requestData['eos_reason'] ?? ''));
$endDateStr = trim((string)($requestData['end_date'] ?? ''));
$joiningDateStr = trim((string)($requestData['joining_date'] ?? ''));

// Use filter_input for numeric validation (better security)
$salary = filter_var($requestData['salary'] ?? 0, FILTER_VALIDATE_FLOAT);
$vacationSalaryBase = filter_var($requestData['vacation_salary_base'] ?? 0, FILTER_VALIDATE_FLOAT);
$anul_vac_days = filter_var($requestData['anul_vac_days'] ?? 0, FILTER_VALIDATE_FLOAT);
$deduct = filter_var($requestData['deduct'] ?? 0, FILTER_VALIDATE_FLOAT);

// Also accept calculated_housing and housing_allowance from frontend (for compatibility)
// These are received but not used in calculation (salary already includes them)
$calculated_housing = filter_var($requestData['calculated_housing'] ?? 0, FILTER_VALIDATE_FLOAT);
$housing_allowance = filter_var($requestData['housing_allowance'] ?? 0, FILTER_VALIDATE_FLOAT);

$errors = [];
if (empty($contractType)) $errors[] = 'Contract type is missing.';
if (empty($selectedReasonCode)) $errors[] = 'EOS reason is missing.';
if (empty($endDateStr)) $errors[] = 'End date is missing.';
if (empty($joiningDateStr)) $errors[] = 'Joining date is missing.';
if ($salary === false || $salary <= 0) $errors[] = 'Invalid salary.';

if (!empty($errors)) {
    $response['message'] = implode(' | ', $errors);
    echo json_encode($response);
    exit;
}

// Parse and validate dates
try {
    $joiningDate = new DateTime($joiningDateStr);
    $endDate = new DateTime($endDateStr);
} catch (Exception $e) {
    $response['message'] = 'Invalid date format.';
    echo json_encode($response);
    exit;
}

if ($joiningDate >= $endDate) {
    $response['message'] = 'End date must be after joining date.';
    echo json_encode($response);
    exit;
}

// Calculate service duration using Saudi labor law 360-day year (30-day months)
// This matches Qiwa's official calculation method
$interval = $joiningDate->diff($endDate);

// Qiwa service basis uses DateInterval components converted to a 360-day year:
// years * 360 + months * 30 + days + 1 inclusive day
$totalDays = ($interval->y * 360) + ($interval->m * 30) + $interval->d + 1;

// Service years in 360-day basis
$serviceYears = $totalDays / 360;

// error_log("=== LOCAL EOS CALCULATION ===");
// error_log("Joining Date: $joiningDateStr");
// error_log("End Date: $endDateStr");
// error_log("Service Years: " . number_format($serviceYears, 2));
// error_log("Service Days: $totalDays");
// error_log("Salary: $salary");
// error_log("Vacation Salary Base: " . ($vacationSalaryBase !== false ? $vacationSalaryBase : 0));
// error_log("Contract Type: $contractType");
// error_log("EOS Reason: $selectedReasonCode");

/**
 * Saudi Arabia Labor Code EOS Calculation (Article 80-85)
 * 
 * Base Formula (Article 84):
 *   Reward = (Salary × 0.5 × min(ServiceYears, 5))
 *          + (Salary × max(ServiceYears - 5, 0))
 * 
 * Reason-Specific Entitlement:
 *   1  = Resignation tiers (Article 85)
 *   3,5 = No EOS (probation/Article 80)
 *   Other valid reasons = Full reward
 * 
 * Verified against: Qiwa Calculator & HRSD Official Calculator
 * Test case: 2,487.50 SAR × 2.63 years, Resignation = 1,091.74 SAR ✓
 */

// Calculate statutory EOS reward base (Article 84)
$firstFiveYears = min($serviceYears, 5);
$beyondFiveYears = max($serviceYears - 5, 0);
$baseEosAmount = ($salary * 0.5 * $firstFiveYears) + ($salary * $beyondFiveYears);

// Apply reason-specific entitlement
$eosAmount = 0;
$calculationMethod = '';

// error_log("Base EOS (Article 84): " . number_format($baseEosAmount, 2));
// error_log("Reason Code: $selectedReasonCode");

switch ($selectedReasonCode) {
    case '1': // Employee resignation (Article 85)
        if ($serviceYears < 2) {
            $eosAmount = 0;
            $calculationMethod = 'Resignation < 2 Years (No EOS)';
        } elseif ($serviceYears < 5) {
            $eosAmount = $baseEosAmount / 3;
            $calculationMethod = 'Resignation >= 2 and < 5 Years (1/3)';
        } elseif ($serviceYears < 10) {
            $eosAmount = ($baseEosAmount * 2) / 3;
            $calculationMethod = 'Resignation >= 5 and < 10 Years (2/3)';
        } else {
            $eosAmount = $baseEosAmount;
            $calculationMethod = 'Resignation >= 10 Years (Full)';  
        }
        break;

    case '3': // Probation termination (Article 53)
    case '5': // Employer termination under Article 80
        $eosAmount = 0;
        $calculationMethod = 'No EOS (Article 53/80)';
        break;

    default:
        // Full entitlement for other termination reasons
        $eosAmount = $baseEosAmount;
        $calculationMethod = 'Full EOS (Article 84)';
        break;
}

// Ensure EOS is not negative
$eosAmount = max(0, $eosAmount);

// Calculate vacation salary from employee master total salary when provided
$effectiveVacationBase = ($vacationSalaryBase !== false && $vacationSalaryBase > 0) ? $vacationSalaryBase : $salary;
$vacationSalary = ($effectiveVacationBase / 30) * $anul_vac_days;

// Calculate net payment
$netPayment = ($eosAmount + $vacationSalary) - $deduct;

// error_log("EOS Amount (calculated): $eosAmount");
// error_log("Calculation Method: $calculationMethod");
// error_log("Vacation Salary: $vacationSalary");
// error_log("Deductions: $deduct");
// error_log("Net Payment: $netPayment");

$response = [
    'success' => true,
    'eos_amount' => number_format($eosAmount, 2, '.', ''),
    'vacation_salary' => number_format($vacationSalary, 2, '.', ''),
    'net_payment' => number_format($netPayment, 2, '.', ''),
    'message' => 'Calculation successful (Local - Saudi Arabia Labor Law)',
    'calculation_method' => $calculationMethod,
    'service_years' => number_format($serviceYears, 2),
    'debug_timestamp' => date('Y-m-d H:i:s')
];

echo json_encode($response);
?>
