<?php
/****************************************************************
 * EOS CALCULATOR v3 - ROBUST WITH FULL LOGGING
 * 
 * This version:
 * 1. Accepts POST, GET, and raw JSON/form data
 * 2. Logs ALL attempts to files for debugging
 * 3. Tries multiple parsing methods
 * 4. Returns detailed error info if it fails
 ****************************************************************/

require_once __DIR__ . '/../../includes/db.php';

$debug_log = __DIR__ . '/eos_calc_debug.log';

function log_debug($msg) {
    global $debug_log;
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($debug_log, "[$timestamp] $msg\n", FILE_APPEND);
}

log_debug("========== NEW REQUEST ==========");
log_debug("Request Method: " . $_SERVER['REQUEST_METHOD']);
log_debug("Request URI: " . $_SERVER['REQUEST_URI']);
log_debug("Content-Type: " . ($_SERVER['CONTENT_TYPE'] ?? 'none'));

// ========== PARSE REQUEST DATA FROM MULTIPLE SOURCES ==========
$requestData = [];

// Source 1: Query string ($_GET)
if (!empty($_GET)) {
    log_debug("Found GET params: " . json_encode($_GET));
    $requestData = array_merge($requestData, $_GET);
}

// Source 2: POST data ($_POST)
if (!empty($_POST)) {
    log_debug("Found POST params: " . json_encode($_POST));
    $requestData = array_merge($requestData, $_POST);
}

// Source 3: Raw PHP input (JSON or form-encoded)
$rawInput = file_get_contents('php://input');
if (!empty($rawInput)) {
    log_debug("Raw input length: " . strlen($rawInput));
    log_debug("Raw input preview: " . substr($rawInput, 0, 200));
    
    // Try JSON decode
    $jsonData = json_decode($rawInput, true);
    if (is_array($jsonData)) {
        log_debug("Successfully parsed as JSON: " . json_encode($jsonData));
        $requestData = array_merge($requestData, $jsonData);
    } else {
        // Try form-encoded
        parse_str($rawInput, $formData);
        if (!empty($formData)) {
            log_debug("Successfully parsed as form-encoded: " . json_encode($formData));
            $requestData = array_merge($requestData, $formData);
        }
    }
}

log_debug("Final merged request data: " . json_encode($requestData));

header('Content-Type: application/json');

$response = [
    'success' => false,
    'eos_amount' => '0.00',
    'vacation_salary' => '0.00',
    'net_payment' => '0.00',
    'message' => 'Invalid input.'
];

if (empty($requestData)) {
    log_debug("ERROR: No request data found!");
    echo json_encode($response);
    exit;
}

// ========== VALIDATE INPUTS ==========
$contractType = trim((string)($requestData['contract_type'] ?? ''));
$selectedReasonCode = trim((string)($requestData['eos_reason'] ?? ''));
$endDateStr = trim((string)($requestData['end_date'] ?? ''));
$joiningDateStr = trim((string)($requestData['joining_date'] ?? ''));
$salary = isset($requestData['salary']) ? (float)$requestData['salary'] : false;
$anul_vac_days = isset($requestData['anul_vac_days']) ? (float)$requestData['anul_vac_days'] : 0;
$deduct = isset($requestData['deduct']) ? (float)$requestData['deduct'] : 0;

log_debug("Parsed inputs: contractType=$contractType, reason=$selectedReasonCode, endDate=$endDateStr, joiningDate=$joiningDateStr, salary=$salary, vacDays=$anul_vac_days");

$errors = [];
if (empty($contractType)) $errors[] = 'Contract type missing';
if (empty($selectedReasonCode)) $errors[] = 'EOS reason missing';
if (empty($endDateStr)) $errors[] = 'End date missing';
if (empty($joiningDateStr)) $errors[] = 'Joining date missing';
if ($salary === false || $salary <= 0) $errors[] = 'Invalid salary: ' . ($salary ?? 'null');

if (!empty($errors)) {
    log_debug("Validation errors: " . implode(', ', $errors));
    $response['message'] = implode(' | ', $errors);
    echo json_encode($response);
    exit;
}

// ========== CALL QIWA API ==========
$apiUrl = "https://knowledge-center-be.qiwa.sa/api/v1/end-of-service-lookup?" . http_build_query([
    'StartDate' => $joiningDateStr,
    'EndDate' => $endDateStr,
    'Salary' => $salary,
    'ContractTypeCode' => $contractType,
    'ContractEndReasonCode' => $selectedReasonCode
]);

log_debug("Calling Qiwa API: $apiUrl");

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $apiUrl);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, '');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 15);
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Accept: application/json',
    'User-Agent: Mozilla/5.0'
]);

$apiResponse = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

log_debug("Qiwa API response code: $httpCode, error: " . ($curlError ?: 'none'));
log_debug("Qiwa API response preview: " . substr($apiResponse, 0, 300));

if ($curlError) {
    log_debug("ERROR: cURL error - $curlError");
    $response['message'] = "API call failed: $curlError";
    echo json_encode($response);
    exit;
}

if ($httpCode !== 200) {
    log_debug("ERROR: HTTP $httpCode - " . substr($apiResponse, 0, 500));
    $response['message'] = "API returned HTTP $httpCode";
    echo json_encode($response);
    exit;
}

$apiData = json_decode($apiResponse, true);
if (!$apiData) {
    log_debug("ERROR: Could not parse API response as JSON");
    $response['message'] = 'Invalid API response format';
    echo json_encode($response);
    exit;
}

log_debug("API data decoded: " . json_encode($apiData));

// ========== EXTRACT REWARD AMOUNT ==========
$rewardAmount = $apiData['RewardAmount'] 
    ?? $apiData['data']['RewardAmount']
    ?? $apiData['Body']['RewardAmount']
    ?? $apiData['EndOfServiceReward']['RewardAmount']
    ?? null;

log_debug("Extracted RewardAmount: " . ($rewardAmount ?? 'NOT FOUND'));

if ($rewardAmount === null) {
    log_debug("ERROR: RewardAmount not found in response. Full response: " . json_encode($apiData));
    $response['message'] = 'Could not extract RewardAmount from API response';
    echo json_encode($response);
    exit;
}

// ========== CALCULATE FINAL AMOUNTS ==========
try {
    $startDate = new DateTime($joiningDateStr);
    $endDate = new DateTime($endDateStr);
    
    $eosAmount = (float)$rewardAmount;
    $vacationSalary = ($salary / 30) * $anul_vac_days;
    $netPayment = ($eosAmount + $vacationSalary) - $deduct;
    
    log_debug("Success: EOS=$eosAmount, Vacation=$vacationSalary, Net=$netPayment");
    
    $response = [
        'success' => true,
        'eos_amount' => number_format($eosAmount, 2, '.', ''),
        'vacation_salary' => number_format($vacationSalary, 2, '.', ''),
        'net_payment' => number_format($netPayment, 2, '.', ''),
        'message' => 'Calculation successful.'
    ];
} catch (Exception $e) {
    log_debug("ERROR: Exception - " . $e->getMessage());
    $response['message'] = 'Calculation error: ' . $e->getMessage();
}

echo json_encode($response);
log_debug("Response sent: " . json_encode($response));
log_debug("========== END REQUEST ==========\n");
?>
