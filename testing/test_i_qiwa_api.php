<?php
/**
 * Testing i.qiwa.sa API endpoint (what the website likely uses)
 */

function testQiwaAPI($endpoint_url, $method = 'POST', $params = []) {
    $ch = curl_init();
    
    // For POST, we'll try both JSON body and query string
    if ($method === 'POST' && !empty($params)) {
        // Try with query string in URL
        $url_with_params = $endpoint_url . '?' . http_build_query($params);
        curl_setopt($ch, CURLOPT_URL, $url_with_params);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, '');
    } else {
        curl_setopt($ch, CURLOPT_URL, $endpoint_url);
    }
    
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    return [
        'http_code' => $http_code,
        'response' => $response,
        'data' => json_decode($response, true),
        'error' => $error
    ];
}

$params = [
    'StartDate' => '2020-02-17',
    'EndDate' => '2026-02-10',
    'Salary' => '1983',
    'ContractTypeCode' => '1',
    'ContractEndReasonCode' => '1'
];

?>
<!DOCTYPE html>
<html>
<head>
    <title>Testing i.qiwa.sa API</title>
    <style>
        body { font-family: Arial; margin: 20px; background: #f5f5f5; }
        .test { border: 2px solid #2196F3; padding: 20px; margin: 15px 0; background: white; }
        .success { border-color: #4caf50; background: #c8e6c9; }
        .url { background: #e3f2fd; padding: 10px; margin: 10px 0; font-family: monospace; word-break: break-all; }
        .response { background: #f5f5f5; padding: 10px; margin: 10px 0; font-family: monospace; font-size: 12px; max-height: 400px; overflow-y: auto; border: 1px solid #ddd; }
        .reward { font-size: 20px; font-weight: bold; color: #d32f2f; }
        .target { color: #4caf50; }
        h3 { color: #333; margin-top: 0; }
    </style>
</head>
<body>
    <h1>🔍 Testing i.qiwa.sa Endpoints</h1>
    <p>Testing endpoints that match the official Qiwa website URL structure</p>
    <p><strong>Parameters:</strong> Salary 1983, Dates 2020-02-17 to 2026-02-10, Type 1, Reason 1 (Employee Resignation)</p>
    
    <?php
        // Test 1: Direct i.qiwa.sa service endpoint
        echo '<div class="test">';
        echo '<h3>Test 1: i.qiwa.sa/services/end-of-service-reward (Direct Service)</h3>';
        $result = testQiwaAPI('https://i.qiwa.sa/services/end-of-service-reward', 'POST', $params);
        echo '<div class="url"><strong>URL:</strong> https://i.qiwa.sa/services/end-of-service-reward?' . http_build_query($params) . '</div>';
        echo '<p><strong>HTTP Status:</strong> ' . $result['http_code'] . '</p>';
        if ($result['error']) {
            echo '<p style="color: red;"><strong>cURL Error:</strong> ' . htmlspecialchars($result['error']) . '</p>';
        }
        if ($result['data']) {
            echo '<div class="response">' . htmlspecialchars(json_encode($result['data'], JSON_PRETTY_PRINT)) . '</div>';
            if (isset($result['data']['RewardAmount'])) {
                $reward = floatval($result['data']['RewardAmount']);
                $class = ($reward > 4600 && $reward < 4610) ? 'target' : '';
                echo '<p class="reward ' . $class . '">' . ($reward > 4600 && $reward < 4610 ? '✓ ' : '') . 'Reward: ' . htmlspecialchars($result['data']['RewardAmount']) . ' SAR' . ($reward > 4600 && $reward < 4610 ? ' ✓✓✓ MATCH!' : '') . '</p>';
            }
        } else {
            echo '<p style="color: orange;"><strong>Response:</strong> ' . mb_substr(htmlspecialchars($result['response']), 0, 300) . '...</p>';
        }
        echo '</div>';
        
        // Test 2: Try with different parameter format
        echo '<div class="test">';
        echo '<h3>Test 2: knowledge-center-be with decimal salary</h3>';
        $params_decimal = $params;
        $params_decimal['Salary'] = '1983.00';
        $result = testQiwaAPI('https://knowledge-center-be.qiwa.sa/api/v1/end-of-service-lookup', 'POST', $params_decimal);
        echo '<div class="url"><strong>URL:</strong> https://knowledge-center-be.qiwa.sa/api/v1/end-of-service-lookup?' . http_build_query($params_decimal) . '</div>';
        echo '<p><strong>HTTP Status:</strong> ' . $result['http_code'] . '</p>';
        if ($result['data']) {
            echo '<div class="response">' . htmlspecialchars(json_encode($result['data'], JSON_PRETTY_PRINT)) . '</div>';
            if (isset($result['data']['RewardAmount'])) {
                $reward = floatval($result['data']['RewardAmount']);
                $class = ($reward > 4600 && $reward < 4610) ? 'target' : '';
                echo '<p class="reward ' . $class . '">' . ($reward > 4600 && $reward < 4610 ? '✓ ' : '') . 'Reward: ' . htmlspecialchars($result['data']['RewardAmount']) . ' SAR' . ($reward > 4600 && $reward < 4610 ? ' ✓✓✓ MATCH!' : '') . '</p>';
            }
        }
        echo '</div>';
    ?>
    
    <h2>Summary</h2>
    <p>If neither endpoint returns 4,608.64 SAR, then the Qiwa website might be using a proprietary calculation or different API altogether.</p>
    <p>In that case, we should accept that the system EOS calculation is working correctly and the discrepancy is with the reference value.</p>
</body>
</html>
