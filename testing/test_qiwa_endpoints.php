<?php
/**
 * Test Different Qiwa API Endpoints
 * Finding the correct endpoint that returns 4,608.64 SAR
 */

function makeCurlRequest($url, $method = 'POST', $payload = [], $headers = []) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    }
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    $default_headers = ['Content-Type: application/json'];
    $all_headers = array_merge($default_headers, $headers);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $all_headers);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    curl_close($ch);

    return [
        'error' => $curl_error,
        'http_code' => $http_code,
        'data' => json_decode($response, true),
        'raw_response' => $response
    ];
}

// Test parameters matching the Qiwa website
$params = [
    'StartDate' => '2020-02-17',
    'EndDate' => '2026-02-10',
    'Salary' => 1983.00,
    'ContractTypeCode' => '1',
    'ContractEndReasonCode' => '1'
];

// Different API endpoints to test
$endpoints = [
    [
        'name' => 'Current Endpoint - POST with Query Parameters (CORRECT FORMAT)',
        'url' => 'https://knowledge-center-be.qiwa.sa/api/v1/end-of-service-lookup?' . http_build_query($params),
        'method' => 'POST',
        'body' => []  // Empty body, params in URL
    ],
    [
        'name' => 'Current Endpoint - POST with JSON Body (Original)',
        'url' => 'https://knowledge-center-be.qiwa.sa/api/v1/end-of-service-lookup',
        'method' => 'POST',
        'body' => $params
    ]
];

?>
<!DOCTYPE html>
<html>
<head>
    <title>Qiwa API Endpoint Finder</title>
    <style>
        body { font-family: Arial; margin: 20px; background: #f5f5f5; }
        .endpoint { border: 1px solid #ccc; padding: 15px; margin: 10px 0; background: white; }
        .endpoint.success { border-color: #4caf50; background: #c8e6c9; }
        .endpoint.error { border-color: #f44336; background: #ffcdd2; }
        .url { background: #e3f2fd; padding: 10px; margin: 10px 0; font-family: monospace; word-break: break-all; }
        .response { background: #f5f5f5; padding: 10px; margin: 10px 0; font-family: monospace; font-size: 12px; max-height: 300px; overflow-y: auto; }
        h3 { color: #333; }
        .reward { font-size: 24px; font-weight: bold; color: #d32f2f; }
        .target { color: #4caf50; }
    </style>
</head>
<body>
    <h1>🔍 Qiwa API Endpoint Finder</h1>
    <p>Testing different endpoints to find which one returns <strong>4,608.64 SAR</strong></p>
    <p><strong>Test Parameters:</strong> Salary 1983.00, Dates 2020-02-17 to 2026-02-10, Contract Type 1, Reason 1</p>
    
    <?php foreach ($endpoints as $endpoint): ?>
        <div class="endpoint">
            <h3><?= htmlspecialchars($endpoint['name']); ?></h3>
            <div class="url"><strong>URL:</strong> <?= htmlspecialchars($endpoint['url']); ?></div>
            
            <?php
                $result = ($endpoint['method'] === 'GET') 
                    ? makeCurlRequest($endpoint['url'], 'GET')
                    : makeCurlRequest($endpoint['url'], 'POST', $endpoint['body'] ?? $params);
                
                if ($result['error']) {
                    echo '<div style="background: #ffebee; padding: 10px; color: red;"><strong>cURL Error:</strong> ' . htmlspecialchars($result['error']) . '</div>';
                } else {
                    echo '<p><strong>HTTP Status:</strong> ' . $result['http_code'] . '</p>';
                    
                    if ($result['data']) {
                        echo '<div class="response">';
                        echo '<strong>Response:</strong><br>';
                        echo htmlspecialchars(json_encode($result['data'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
                        echo '</div>';
                        
                        // Check for RewardAmount in different possible fields
                        $reward = null;
                        if (isset($result['data']['RewardAmount'])) {
                            $reward = $result['data']['RewardAmount'];
                        } elseif (isset($result['data']['eos_amount'])) {
                            $reward = $result['data']['eos_amount'];
                        } elseif (isset($result['data']['reward'])) {
                            $reward = $result['data']['reward'];
                        }
                        
                        if ($reward !== null) {
                            $reward_value = floatval($reward);
                            $is_target = ($reward_value > 4608 && $reward_value < 4609);
                            echo '<p class="reward' . ($is_target ? ' target' : '') . '">';
                            echo $is_target ? '✓ ' : '';
                            echo 'Reward Amount: ' . htmlspecialchars($reward) . ' SAR';
                            echo $is_target ? ' ✓✓✓ CORRECT ENDPOINT! ✓✓✓' : '';
                            echo '</p>';
                        } else {
                            echo '<p style="color: orange;">⚠ Response received but no RewardAmount found</p>';
                        }
                    } else {
                        echo '<p style="color: red;">❌ No valid JSON response</p>';
                        echo '<pre>' . htmlspecialchars(mb_substr($result['raw_response'], 0, 500)) . '</pre>';
                    }
                }
            ?>
        </div>
    <?php endforeach; ?>
    
    <h2>Findings</h2>
    <p>✓ If an endpoint returns <strong style="color: #4caf50;">4,608.64 SAR</strong>, that's the correct one to use.</p>
    <p>✗ The current endpoint returns 1,978.74 SAR, which appears to be incorrect.</p>
</body>
</html>
