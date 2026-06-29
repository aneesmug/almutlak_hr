<?php
/****************************************************************
 * EOS CALCULATOR v3 - FINAL WORKING VERSION
 * Timestamp: 2026-06-11
 * This version avoids all caching issues
 ****************************************************************/

require_once __DIR__ . '/../../includes/db.php';

header('Content-Type: application/json');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');

// Parse all input sources
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

// Validate inputs
$contractType = trim((string)($requestData['contract_type'] ?? ''));
$selectedReasonCode = trim((string)($requestData['eos_reason'] ?? ''));
$endDateStr = trim((string)($requestData['end_date'] ?? ''));
$joiningDateStr = trim((string)($requestData['joining_date'] ?? ''));
$salary = isset($requestData['salary']) ? (float)$requestData['salary'] : false;
$anul_vac_days = isset($requestData['anul_vac_days']) ? (float)$requestData['anul_vac_days'] : 0;
$deduct = isset($requestData['deduct']) ? (float)$requestData['deduct'] : 0;

$errors = [];
if (empty($contractType)) $errors[] = 'Contract type missing';
if (empty($selectedReasonCode)) $errors[] = 'EOS reason missing';
if (empty($endDateStr)) $errors[] = 'End date missing';
if (empty($joiningDateStr)) $errors[] = 'Joining date missing';
if ($salary === false || $salary <= 0) $errors[] = 'Invalid salary';

if (!empty($errors)) {
    $response['message'] = implode(' | ', $errors);
    echo json_encode($response);
    exit;
}

// Call Qiwa API with automatic retry
$apiUrl = "https://knowledge-center-be.qiwa.sa/api/v1/end-of-service-lookup?" . http_build_query([
    'StartDate' => $joiningDateStr,
    'EndDate' => $endDateStr,
    'Salary' => $salary,
    'ContractTypeCode' => $contractType,
    'ContractEndReasonCode' => $selectedReasonCode
]);

$apiResponse = '';
$httpCode = null;
$curlError = '';
$maxRetries = 2;
$retryCount = 0;
$validJsonReceived = false;

while ($retryCount < $maxRetries && !$validJsonReceived) {
    $retryCount++;
    error_log("API Call Attempt #$retryCount for URL: " . substr($apiUrl, 0, 100) . "...");
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $apiUrl);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, '');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 20);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, false);
    curl_setopt($ch, CURLOPT_ENCODING, '');
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Accept: application/json',
        'Accept-Encoding: gzip, deflate',
        'Accept-Language: en-US,en;q=0.9',
        'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36 Edg/120.0.0.0',
        'Connection: keep-alive',
        'Cache-Control: no-cache',
        'Pragma: no-cache',
        'Sec-Fetch-Dest: empty',
        'Sec-Fetch-Mode: cors',
        'Sec-Fetch-Site: cross-site',
        'Origin: https://hr.almutlaksystem.com',
        'Referer: https://hr.almutlaksystem.com/'
    ]);

    $apiResponse = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    // Check if response is HTML (might be WAF block or redirect)
    if (!empty($apiResponse) && (strpos($apiResponse, '<!DOCTYPE') !== false || strpos($apiResponse, '<html') !== false)) {
        error_log("WARNING: Got HTML response on attempt #$retryCount. Retrying in 500ms...");
        if ($retryCount < $maxRetries) {
            usleep(500000); // Wait 500ms before retry
        }
        continue;
    }

    // If we got JSON-like response, mark as valid and exit loop
    if (!empty($apiResponse) && strpos(trim($apiResponse), '{') === 0) {
        $validJsonReceived = true;
        error_log("Got valid JSON response on attempt #$retryCount");
        break;
    }
}

// After retry loop - check what we got
if (!$validJsonReceived) {
    // Check if all retries got HTML (WAF block)
    if (!empty($apiResponse) && (strpos($apiResponse, '<!DOCTYPE') !== false || strpos($apiResponse, '<html') !== false)) {
        error_log("ERROR: API returned HTML on all $retryCount attempts. WAF is blocking requests.");
        error_log("Response sample: " . substr($apiResponse, 0, 300));
        $response['message'] = 'API access blocked by security filter. Contact Qiwa support for whitelist approval.';
        $response['debug_retries'] = $retryCount;
        $response['debug_issue'] = 'WAF_BLOCKED';
        echo json_encode($response);
        exit;
    }
    
    // Check for curl errors
    if (!empty($curlError)) {
        error_log("ERROR: cURL Error after $retryCount attempts: " . $curlError);
        $response['message'] = 'API connection error: ' . $curlError;
        echo json_encode($response);
        exit;
    }
    
    // Empty response
    if (empty($apiResponse)) {
        error_log("ERROR: Empty API response after $retryCount attempts!");
        $response['message'] = 'Empty API response - service unavailable';
        $response['debug_retries'] = $retryCount;
        echo json_encode($response);
        exit;
    }
}

// Remove any BOM or whitespace
$apiResponse = trim($apiResponse);
if (empty($apiResponse)) {
    error_log("ERROR: Empty API response after trim!");
    $response['message'] = 'Empty API response';
    echo json_encode($response);
    exit;
}

$apiResponse = preg_replace('/^\xEF\xBB\xBF/', '', $apiResponse);

// Log for debugging
error_log("=== QIWA API CALL DEBUG (Attempt #$retryCount/$maxRetries) ===");
error_log("API URL: " . $apiUrl);
error_log("HTTP Code: " . $httpCode);
error_log("Response Type: " . (strpos($apiResponse, '{') === 0 ? "JSON" : "NOT JSON"));
error_log("Response Encoding: " . mb_detect_encoding($apiResponse, 'UTF-8, ISO-8859-1', true));
error_log("Raw Response Length: " . strlen($apiResponse));
error_log("Raw Response (first 150 chars): " . substr($apiResponse, 0, 150));

// Try to decode JSON with detailed error info
$apiData = json_decode($apiResponse, true, 512, JSON_BIGINT_AS_STRING);
$jsonError = json_last_error();
$jsonErrorMsg = json_last_error_msg();

error_log("JSON Decode - Error Code: $jsonError, Message: $jsonErrorMsg");
error_log("JSON Decode Result: " . ($apiData ? "SUCCESS - has " . count($apiData) . " keys" : "FAILED"));

if (!$apiData || $jsonError !== JSON_ERROR_NONE) {
    error_log("ERROR: Invalid JSON. Response: " . substr($apiResponse, 0, 300));
    $response['message'] = 'JSON decode error: ' . $jsonErrorMsg;
    echo json_encode($response);
    exit;
}

$rewardAmount = $apiData['RewardAmount'] ?? null;
if ($rewardAmount === null) {
    $response['message'] = 'RewardAmount not found in API response';
    echo json_encode($response);
    exit;
}

try {
    $eosAmount = (float)$rewardAmount;
    $vacationSalary = ($salary / 30) * $anul_vac_days;
    $netPayment = ($eosAmount + $vacationSalary) - $deduct;
    
    $response = [
        'success' => true,
        'eos_amount' => number_format($eosAmount, 2, '.', ''),
        'vacation_salary' => number_format($vacationSalary, 2, '.', ''),
        'net_payment' => number_format($netPayment, 2, '.', ''),
        'message' => 'Calculation successful.',
        'debug_timestamp' => date('Y-m-d H:i:s'),
        'debug_api_status' => 'OK',
        'debug_response_type' => 'JSON'
    ];
} catch (Exception $e) {
    $response['message'] = 'Calculation error: ' . $e->getMessage();
}

echo json_encode($response);
?>
