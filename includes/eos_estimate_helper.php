<?php

if (defined('EOS_ESTIMATE_HELPER_INCLUDED')) {
    return;
}
define('EOS_ESTIMATE_HELPER_INCLUDED', true);

if (!function_exists('calculate_current_eos_estimate')) {
    /**
     * Estimates the End of Service (EOS) benefit accrued up to today, using the
     * same Saudi Labor Law base formula (Article 84) as
     * includes/ajaxFile/ajax_eos_calculator_local.php. Since the employee is
     * still active, no termination reason exists yet, so this returns the full
     * statutory base reward (before any resignation-tier reduction under
     * Article 85) as an indicative "if terminated today" figure.
     */
    function calculate_current_eos_estimate($conDB, $empId, $joiningDateStr) {
        $result = [
            'success' => false,
            'eos_amount' => 0.0,
            'service_years' => 0.0,
            'salary_base' => 0.0,
            'message' => '',
        ];

        $empId = trim((string)$empId);
        $joiningDateStr = trim((string)$joiningDateStr);

        if ($empId === '' || $joiningDateStr === '') {
            $result['message'] = 'Missing employee ID or joining date.';
            return $result;
        }

        try {
            $joiningDate = new DateTime($joiningDateStr);
            $endDate = new DateTime('today');
        } catch (Exception $e) {
            $result['message'] = 'Invalid joining date.';
            return $result;
        }

        if ($joiningDate >= $endDate) {
            $result['message'] = 'Joining date is not in the past.';
            return $result;
        }

        // Active salary components (status = 1), same logic as emp_end_of_service.php
        $empIdEsc = mysqli_real_escape_string($conDB, $empId);
        $salaryRes = mysqli_query($conDB, "SELECT * FROM `emp_salary` WHERE `emp_id`='{$empIdEsc}' AND `status` = 1 ORDER BY `id` DESC LIMIT 1");
        $salaryRow = $salaryRes ? (mysqli_fetch_assoc($salaryRes) ?: []) : [];

        if (empty($salaryRow)) {
            $result['message'] = 'No active salary record found.';
            return $result;
        }

        $basicSalary = (float)($salaryRow['basic'] ?? 0);
        $housingBenefit = (float)($salaryRow['housing'] ?? 0);

        // If housing benefit is not provided, calculate it as (basic/12)*2, matching EOS page logic
        $housingForBase = ($housingBenefit == 0 && $basicSalary > 0) ? (($basicSalary / 12) * 2) : $housingBenefit;

        $salaryBase = $basicSalary
            + $housingForBase
            + (float)($salaryRow['transport'] ?? 0)
            + (float)($salaryRow['food'] ?? 0)
            + (float)($salaryRow['misc'] ?? 0)
            + (float)($salaryRow['cashier'] ?? 0)
            + (float)($salaryRow['fuel'] ?? 0)
            + (float)($salaryRow['tel'] ?? 0)
            + (float)($salaryRow['guard'] ?? 0)
            + (float)($salaryRow['other'] ?? 0);

        if ($salaryBase <= 0) {
            $result['message'] = 'Invalid salary base.';
            return $result;
        }

        // Service duration on a 360-day year (30-day months), matching Qiwa's method
        $interval = $joiningDate->diff($endDate);
        $totalDays = ($interval->y * 360) + ($interval->m * 30) + $interval->d + 1;
        $serviceYears = $totalDays / 360;

        // Article 84 base reward: half-month per year for first 5 years, full month beyond
        $firstFiveYears = min($serviceYears, 5);
        $beyondFiveYears = max($serviceYears - 5, 0);
        $eosAmount = max(0, ($salaryBase * 0.5 * $firstFiveYears) + ($salaryBase * $beyondFiveYears));

        $result['success'] = true;
        $result['eos_amount'] = $eosAmount;
        $result['service_years'] = $serviceYears;
        $result['salary_base'] = $salaryBase;
        return $result;
    }
}
