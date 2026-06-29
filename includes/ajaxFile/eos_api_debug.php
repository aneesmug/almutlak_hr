<?php
/**
 * RAW API RESPONSE DEBUG
 * Shows exactly what the Qiwa API returns
 */

header('Content-Type: application/json');

$queryString = http_build_query([
    'StartDate' => $_GET['start_date'] ?? '2023-10-25',
    'EndDate' => $_GET['end_date'] ?? '2026-06-11',
    'Salary' => $_GET['salary'] ?? '2487.5',
    'ContractTypeCode' => $_GET['contract_type'] ?? '1',
    'ContractEndReasonCode' => $_GET['eos_reason'] ?? '1'
]);

$url = "https://knowledge-center-be.qiwa.sa/api/v1/end-of-service-lookup?" . $queryString;

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
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

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

echo json_encode([
    'url' => $url,
    'http_code' => $httpCode,
    'curl_error' => $curlError ?: null,
    'raw_response' => $response,
    'response_length' => strlen($response),
    'is_valid_json' => json_decode($response, true) ? true : false,
    'json_error' => json_last_error_msg(),
    'first_200_chars' => substr($response, 0, 200)
], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
?>
