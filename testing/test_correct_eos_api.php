<?php
/**
 * Testing JISR.net API endpoints
 * JISR shows 4,604.97 SAR - we need to find their API
 */

function testAPI($url, $method = 'POST', $params = []) {
    $ch = curl_init();
    
    if ($method === 'POST') {
        $url_with_params = $url . '?' . http_build_query($params);
        curl_setopt($ch, CURLOPT_URL, $url_with_params);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, '');
    } else {
        curl_setopt($ch, CURLOPT_URL, $url . '?' . http_build_query($params));
    }
    
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    
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
    'startDate' => '2020-02-17',
    'endDate' => '2026-02-10',
    'salary' => '1983',
    'contractTypeCode' => '1',
    'contractEndReasonCode' => '1'
];

// Different API endpoints to try
$endpoints = [
    [
        'name' => 'JISR API v1',
        'url' => 'https://api.jisr.net/api/v1/end-of-service-reward'
    ],
    [
        'name' => 'JISR API (direct)',
        'url' => 'https://api.jisr.net/end-of-service-reward'
    ],
    [
        'name' => 'JISR Common endpoint',
        'url' => 'https://www.jisr.net/api/end-of-service-calculator'
    ],
    [
        'name' => 'Qiwa i.qiwa.sa v1 API',
        'url' => 'https://i.qiwa.sa/api/v1/end-of-service-reward'
    ],
    [
        'name' => 'Qiwa API (alternative)',
        'url' => 'https://api.qiwa.sa/end-of-service-reward'
    ]
];

?>
<!DOCTYPE html>
<html>
<head>
    <title>Finding the Correct EOS API (4,604.97 SAR)</title>
    <style>
        body { font-family: Arial; margin: 20px; background: #f5f5f5; }
        .test { border: 2px solid #ccc; padding: 15px; margin: 10px 0; background: white; }
        .success { border-color: #4caf50; background: #c8e6c9; }
        .url { background: #e3f2fd; padding: 10px; margin: 10px 0; font-family: monospace; font-size: 12px; word-break: break-all; }
        .response { background: #f5f5f5; padding: 10px; margin: 10px 0; font-family: monospace; font-size: 11px; max-height: 300px; overflow-y: auto; border: 1px solid #ddd; }
        .reward { font-size: 18px; font-weight: bold; }
        .target { color: #4caf50; background: #c8e6c9; padding: 5px; border-radius: 3px; }
        h3 { color: #333; margin-top: 0; }
        .status { margin: 10px 0; }
    </style>
</head>
<body>
    <h1>🔍 Finding the Correct API Endpoint</h1>
    <p>Looking for endpoint that returns <strong style="color: #4caf50;">~4,604-4,609 SAR</strong> (JISR/Qiwa website value)</p>
    <p><strong>Test Parameters:</strong> Salary 1983, Dates 2020-02-17 to 2026-02-10, Type 1, Reason 1</p>
    
    <?php foreach ($endpoints as $endpoint): ?>
        <div class="test">
            <h3><?= htmlspecialchars($endpoint['name']); ?></h3>
            <div class="url"><strong>URL:</strong> <?= htmlspecialchars($endpoint['url']); ?></div>
            
            <?php
                $result = testAPI($endpoint['url'], 'POST', $params);
                
                echo '<div class="status">';
                echo '<p><strong>HTTP Status:</strong> ' . $result['http_code'] . '</p>';
                
                if ($result['error']) {
                    echo '<p style="color: red;"><strong>Error:</strong> ' . htmlspecialchars($result['error']) . '</p>';
                }
                
                if ($result['data']) {
                    echo '<div class="response">' . htmlspecialchars(json_encode($result['data'], JSON_PRETTY_PRINT)) . '</div>';
                    
                    // Check various possible field names
                    $reward = null;
                    if (isset($result['data']['RewardAmount'])) $reward = $result['data']['RewardAmount'];
                    elseif (isset($result['data']['rewardAmount'])) $reward = $result['data']['rewardAmount'];
                    elseif (isset($result['data']['reward'])) $reward = $result['data']['reward'];
                    elseif (isset($result['data']['eosAmount'])) $reward = $result['data']['eosAmount'];
                    elseif (isset($result['data']['eos_amount'])) $reward = $result['data']['eos_amount'];
                    
                    if ($reward !== null) {
                        $reward_val = floatval($reward);
                        $is_match = ($reward_val > 4600 && $reward_val < 4610);
                        echo '<p class="reward ' . ($is_match ? 'target' : '') . '">';
                        if ($is_match) echo '✓✓✓ ';
                        echo 'Reward: ' . htmlspecialchars($reward) . ' SAR';
                        if ($is_match) echo ' ✓✓✓ CORRECT ENDPOINT!';
                        echo '</p>';
                    } else {
                        echo '<p style="color: orange;">⚠ Response received but no reward field found</p>';
                    }
                } else {
                    echo '<p style="color: orange;"><strong>Response (first 200 chars):</strong></p>';
                    echo '<pre style="background: #fff3cd; padding: 10px;">' . htmlspecialchars(mb_substr($result['response'], 0, 200)) . '...</pre>';
                }
                
                echo '</div>';
            ?>
        </div>
    <?php endforeach; ?>
    
    <h2>Expected Result</h2>
    <p>One of these endpoints should return a reward amount in the range of <strong style="background: #c8e6c9; padding: 5px;">4,604.97 - 4,608.64 SAR</strong></p>
</body>
</html>
