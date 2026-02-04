<?php
/**
 * Reverse Engineering: Find What Salary Produces 4,608.64 SAR
 */

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
        'data' => json_decode($response, true)
    ];
}

// Test different salary values with reason code 1 to find what produces 4608.64
$target_reward = 4608.64;
$test_salaries = [1700, 1800, 1900, 1983, 2000, 2100, 2200, 2300, 2400, 2500];

?>
<!DOCTYPE html>
<html>
<head>
    <title>Find Correct Salary for 4,608.64 SAR Reward</title>
    <style>
        body { font-family: Arial; margin: 20px; background: #f5f5f5; }
        table { width: 100%; border-collapse: collapse; background: white; }
        th, td { border: 1px solid #ddd; padding: 10px; text-align: right; }
        th { background: #2196F3; color: white; }
        tr:hover { background: #f5f5f5; }
        .match { background: #c8e6c9; font-weight: bold; }
        .salary { text-align: left; }
    </style>
</head>
<body>
    <h1>🔍 Reverse Engineering Salary Amount</h1>
    <p>Testing different salaries to find which one produces <strong style="color: #2196F3;">4,608.64 SAR</strong> reward</p>
    <p><strong>Fixed Parameters:</strong> Dates 2020-02-17 to 2026-02-10, Contract Type 1, Reason Code 1</p>
    
    <table>
        <thead>
            <tr>
                <th class="salary">Salary Tested</th>
                <th>Reward Amount</th>
                <th>Difference from Target</th>
                <th>Match?</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($test_salaries as $salary): ?>
                <?php
                    $params = [
                        'StartDate' => '2020-02-17',
                        'EndDate' => '2026-02-10',
                        'Salary' => $salary,
                        'ContractTypeCode' => '1',
                        'ContractEndReasonCode' => '1'
                    ];
                    
                    $url = "https://knowledge-center-be.qiwa.sa/api/v1/end-of-service-lookup?" . http_build_query($params);
                    $result = makeCurlRequest($url, 'POST', []);
                    
                    $reward = isset($result['data']['RewardAmount']) ? floatval($result['data']['RewardAmount']) : 0;
                    $diff = abs($reward - $target_reward);
                    $is_match = ($diff < 1);
                    $class = $is_match ? 'match' : '';
                ?>
                <tr class="<?= $class ?>">
                    <td class="salary"><?= number_format($salary, 2) ?> SAR</td>
                    <td><?= number_format($reward, 2) ?> SAR</td>
                    <td><?= number_format($diff, 2) ?> SAR</td>
                    <td><?= $is_match ? '✓✓✓ YES!' : '' ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    
    <h2>Analysis</h2>
    <p>If no salary produces 4,608.64 SAR with reason code 1, then:</p>
    <ul>
        <li>The reason code might be different</li>
        <li>The dates might be different</li>
        <li>The contract type might be different</li>
        <li>The Qiwa website might use different calculation logic</li>
    </ul>
</body>
</html>
