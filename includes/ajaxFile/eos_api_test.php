<?php
/**
 * EOS API DIAGNOSTIC ENDPOINT
 * 
 * This file tests direct Qiwa API connectivity and returns comprehensive diagnostic info.
 * Use this to identify if the issue is:
 * 1. Network/firewall blocking the Qiwa API
 * 2. Wrong API endpoint
 * 3. Wrong API parameters
 * 4. Response parsing issue
 */

header('Content-Type: application/json');

// Test parameters (sample data)
$testParams = [
    'StartDate' => '2023-10-25',
    'EndDate' => '2026-06-11',
    'Salary' => 2487.5,
    'ContractTypeCode' => '2',
    'ContractEndReasonCode' => '1'
];

$result = [
    'timestamp' => date('Y-m-d H:i:s'),
    'server' => $_SERVER['HTTP_HOST'] ?? 'unknown',
    'tests' => []
];

// TEST 1: Try cURL POST (query params in URL)
$url = "https://knowledge-center-be.qiwa.sa/api/v1/end-of-service-lookup?" . http_build_query($testParams);

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, '');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Accept: application/json',
    'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64)'
]);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curl_error = curl_error($ch);
curl_close($ch);

$result['tests'][] = [
    'name' => 'cURL POST with query params',
    'url' => $url,
    'method' => 'POST',
    'http_code' => $http_code,
    'curl_error' => $curl_error ?: null,
    'response_preview' => substr($response, 0, 500),
    'response_length' => strlen($response),
    'success' => ($http_code === 200 && !$curl_error)
];

// TEST 2: Try GET instead
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

$response2 = curl_exec($ch);
$http_code2 = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curl_error2 = curl_error($ch);
curl_close($ch);

$result['tests'][] = [
    'name' => 'cURL GET with query params',
    'url' => $url,
    'method' => 'GET',
    'http_code' => $http_code2,
    'curl_error' => $curl_error2 ?: null,
    'response_preview' => substr($response2, 0, 500),
    'response_length' => strlen($response2),
    'success' => ($http_code2 === 200 && !$curl_error2)
];

// TEST 3: Try stream_get_contents
$context = stream_context_create([
    'http' => [
        'method' => 'POST',
        'header' => "Content-Type: application/json\r\n" .
                    "Accept: application/json\r\n",
        'timeout' => 10
    ],
    'ssl' => [
        'verify_peer' => false,
        'verify_peer_name' => false
    ]
]);

$response3 = @file_get_contents($url, false, $context);
$http_code3 = 0;
$stream_error = $response3 === false ? 'Failed to get contents' : null;

if (!empty($http_response_header)) {
    if (preg_match('/\s(\d{3})\s/', $http_response_header[0], $m)) {
        $http_code3 = intval($m[1]);
    }
}

$result['tests'][] = [
    'name' => 'stream_get_contents POST',
    'url' => $url,
    'method' => 'POST',
    'http_code' => $http_code3,
    'stream_error' => $stream_error ?: null,
    'response_preview' => $response3 ? substr($response3, 0, 500) : null,
    'response_length' => $response3 ? strlen($response3) : 0,
    'success' => (!$stream_error && $http_code3 === 200)
];

// TEST 4: Try alternative Qiwa endpoint (reason codes list)
$reasonUrl = "https://knowledge-center-be.qiwa.sa/api/v1/end-of-service";
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $reasonUrl);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, '');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

$response4 = curl_exec($ch);
$http_code4 = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curl_error4 = curl_error($ch);
curl_close($ch);

$result['tests'][] = [
    'name' => 'cURL POST - Reason codes endpoint',
    'url' => $reasonUrl,
    'method' => 'POST',
    'http_code' => $http_code4,
    'curl_error' => $curl_error4 ?: null,
    'response_preview' => substr($response4, 0, 500),
    'response_length' => strlen($response4),
    'success' => ($http_code4 === 200 && !$curl_error4)
];

// Summary
$successful_tests = array_filter($result['tests'], fn($t) => $t['success']);
$result['summary'] = [
    'total_tests' => count($result['tests']),
    'successful' => count($successful_tests),
    'any_api_reachable' => count($successful_tests) > 0
];

echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
?>
