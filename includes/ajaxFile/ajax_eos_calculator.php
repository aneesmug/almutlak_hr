<?php
/****************************************************************
 * AJAX EOS CALCULATOR (v2)
 *
 * This script handles the real-time calculation for the End of Service page.
 * It receives data via POST, calls the Qiwa API, calculates the final
 * settlement, and returns the results as a JSON object.
 *
 * MODIFICATION SUMMARY:
 * 1. UPDATED DB PATH: Changed the database include path to reflect the new file location.
 ****************************************************************/

// Updated database include path
require_once __DIR__ . '/../../includes/db.php';

// --- API Communication Function ---
function makeCurlRequest($url, $method = 'POST', $payload = []) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    }
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    curl_close($ch);

    if ($curl_error) {
        return ['error' => 'Curl error: ' . $curl_error, 'http_code' => 0, 'data' => null];
    }
    
    return ['error' => null, 'http_code' => $http_code, 'data' => json_decode($response, true)];
}

header('Content-Type: application/json');
$response = [
    'success' => false,
    'eos_amount' => 0,
    'vacation_salary' => 0,
    'net_payment' => 0,
    'message' => 'Invalid input.'
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // --- Sanitize and Validate inputs ---
    $contractType = trim($_POST['contract_type'] ?? '');
    $selectedReasonCode = trim($_POST['eos_reason'] ?? '');
    $endDateStr = trim($_POST['end_date'] ?? '');
    $joiningdateget = trim($_POST['joining_date'] ?? '');
    $salary_get = filter_input(INPUT_POST, 'salary', FILTER_VALIDATE_FLOAT);
    $anul_vac_days = filter_input(INPUT_POST, 'anul_vac_days', FILTER_VALIDATE_FLOAT, ['options' => ['default' => 0]]);
    $deduct = filter_input(INPUT_POST, 'deduct', FILTER_VALIDATE_FLOAT, ['options' => ['default' => 0]]);

    $errors = [];
    if (empty($contractType)) $errors[] = 'Contract type is missing.';
    if (empty($selectedReasonCode)) $errors[] = 'EOS reason is missing.';
    if (empty($endDateStr)) $errors[] = 'End date is missing.';
    if (empty($joiningdateget)) $errors[] = 'Joining date is missing.';
    if ($salary_get === false || $salary_get <= 0) $errors[] = 'Invalid salary.';

    if (!empty($joiningdateget) && !empty($endDateStr)) {
        try {
            $startDateTime = new DateTime($joiningdateget);
            $endDateTime = new DateTime($endDateStr);
            if ($startDateTime >= $endDateTime) {
                $errors[] = "End date must be after start date.";
            }
        } catch (Exception $e) {
            $errors[] = "Invalid date format.";
        }
    }

    if (empty($errors)) {
        $api_params = [
            'StartDate' => $joiningdateget,
            'EndDate' => $endDateStr,
            'Salary' => $salary_get,
            'ContractTypeCode' => $contractType,
            'ContractEndReasonCode' => $selectedReasonCode
        ];
        $calc_url = "https://knowledge-center-be.qiwa.sa/api/v1/end-of-service-lookup?" . http_build_query($api_params);
        $calcApiResult = makeCurlRequest($calc_url, 'POST', []);

        if ($calcApiResult['error'] || $calcApiResult['http_code'] !== 200 || !isset($calcApiResult['data']['RewardAmount'])) {
            $response['message'] = 'Could not calculate the reward via API. Please check the reason and dates.';
        } else {
            $eos_amount = $calcApiResult['data']['RewardAmount'] ?? 0;
            $vacation_salary = ($salary_get / 30) * $anul_vac_days;
            $net_payment = ($eos_amount + $vacation_salary) - $deduct;

            $response = [
                'success' => true,
                'eos_amount' => number_format($eos_amount, 2, '.', ''),
                'vacation_salary' => number_format($vacation_salary, 2, '.', ''),
                'net_payment' => number_format($net_payment, 2, '.', ''),
                'message' => 'Calculation successful.'
            ];
        }
    } else {
        $response['message'] = implode(' ', $errors);
    }
}

echo json_encode($response);
exit();
