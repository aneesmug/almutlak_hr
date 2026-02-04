<?php
/**
 * Direct Qiwa API Test
 * Used to diagnose API parameters and responses
 */

// First, fetch available reasons from Qiwa API
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

// Fetch available reasons
$reasons_result = makeCurlRequest("https://knowledge-center-be.qiwa.sa/api/v1/end-of-service", 'POST', []);
$available_reasons = [];
if (!$reasons_result['error'] && $reasons_result['http_code'] === 200 && !empty($reasons_result['data'])) {
    $api_reasons_data = $reasons_result['data']['EndOfServiceRewardLookUpRs']['Body']['EndOfServiceRewardLookUp']['ContractEndReason'] ?? [];
    foreach ($api_reasons_data as $reason) {
        if (isset($reason['ContractTypeCode']) && $reason['ContractTypeCode'] == '1') {
            $available_reasons[] = $reason;
        }
    }
    usort($available_reasons, function($a, $b) {
        return intval($a['ContractEndReasonCode'] ?? 0) - intval($b['ContractEndReasonCode'] ?? 0);
    });
}

// Test parameters for EOS calculation
$test_cases = [
    [
        'name' => 'Test Case 1: Reason Code 1, Salary 1983.33',
        'params' => [
            'StartDate' => '2020-02-17',
            'EndDate' => '2026-02-10',
            'Salary' => 1983.33,
            'ContractTypeCode' => '1',
            'ContractEndReasonCode' => '1'
        ]
    ]
];

// Add test cases for each available reason code
foreach ($available_reasons as $reason) {
    $code = $reason['ContractEndReasonCode'] ?? '';
    $desc = $reason['EnDescription'] ?? 'Unknown';
    if ($code !== '1') { // Skip if already tested
        $test_cases[] = [
            'name' => "Reason Code $code: $desc, Salary 1983.33",
            'params' => [
                'StartDate' => '2020-02-17',
                'EndDate' => '2026-02-10',
                'Salary' => 1983.33,
                'ContractTypeCode' => '1',
                'ContractEndReasonCode' => $code
            ]
        ];
    }
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Qiwa API Direct Test - Find Correct Reason Code</title>
    <style>
        body { font-family: Arial; margin: 20px; }
        .test-case { border: 1px solid #ccc; padding: 15px; margin: 10px 0; }
        .params { background: #f5f5f5; padding: 10px; margin: 10px 0; font-family: monospace; font-size: 12px; }
        .response { background: #e8f5e9; padding: 10px; margin: 10px 0; font-family: monospace; font-size: 12px; }
        .error { background: #ffebee; padding: 10px; margin: 10px 0; color: red; }
        h3 { color: #333; }
        .high-reward { background: #fff9c4; border: 2px solid #ff9800; }
        .target-reward { background: #c8e6c9; border: 3px solid #4caf50; font-weight: bold; }
    </style>
</head>
<body>
    <h1>Qiwa API Reason Code Finder</h1>
    <p>Testing all available reason codes for Contract Type 1 (Limited Period) to find which returns ~4608.64 SAR</p>
    
    <?php if (!empty($available_reasons)): ?>
        <h2>Available Reason Codes for Limited Period (Type 1):</h2>
        <ul>
            <?php foreach ($available_reasons as $reason): ?>
                <li><strong><?= htmlspecialchars($reason['ContractEndReasonCode']); ?></strong>: 
                    <?= htmlspecialchars($reason['EnDescription'] ?? 'N/A'); ?> 
                    (AR: <?= htmlspecialchars($reason['ArDescription'] ?? 'N/A'); ?>)
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
    
    <h2>Test Results:</h2>
    
    <?php foreach ($test_cases as $test): ?>
        <div class="test-case <?= (isset($test['params']['ContractEndReasonCode']) && $test['params']['ContractEndReasonCode'] === '4') ? 'target-reward' : '' ?>">
            <h3><?php echo htmlspecialchars($test['name']); ?></h3>
            
            <h4>Parameters Sent:</h4>
            <div class="params"><?php echo json_encode($test['params'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES); ?></div>
            
            <?php
                $url = "https://knowledge-center-be.qiwa.sa/api/v1/end-of-service-lookup?" . http_build_query($test['params']);
                $result = makeCurlRequest($url, 'POST', []);
                
                if ($result['error']) {
                    echo '<div class="error">cURL Error: ' . htmlspecialchars($result['error']) . '</div>';
                } else {
                    echo '<h4>API Response (HTTP ' . $result['http_code'] . '):</h4>';
                    if ($result['data']) {
                        $reward = $result['data']['RewardAmount'] ?? 'N/A';
                        $class = (floatval($reward) > 4600 && floatval($reward) < 4610) ? 'high-reward' : '';
                        echo '<div class="response ' . $class . '">' . json_encode($result['data'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . '</div>';
                        if (isset($result['data']['RewardAmount'])) {
                            $reward_value = floatval($reward);
                            $color = ($reward_value > 4600 && $reward_value < 4610) ? 'green' : 'inherit';
                            echo '<h4 style="color: ' . $color . ';">RewardAmount: <strong>' . htmlspecialchars($reward) . ' SAR</strong></h4>';
                            if ($reward_value > 4600 && $reward_value < 4610) {
                                echo '<p style="color: green; font-size: 18px;">✓✓✓ THIS IS THE CORRECT REASON CODE! ✓✓✓</p>';
                            }
                        }
                    } else {
                        echo '<div class="error">Failed to decode JSON response</div>';
                        echo '<pre>' . htmlspecialchars($result['raw_response']) . '</pre>';
                    }
                }
            ?>
        </div>
    <?php endforeach; ?>
</body>
</html>
