<?php
/**
 * SMTP Connection Test - Used to diagnose SMTP connectivity issues
 */

// Load configuration
require_once 'token_config.php';

$testResults = array();
$smtpConnected = false;
$smtpError = '';

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>SMTP Connection Test</title>
    <style>
        body { 
            font-family: Arial, sans-serif; 
            margin: 20px; 
            background: #f5f5f5;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            background: white;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        h1 { color: #333; border-bottom: 2px solid #007bff; padding-bottom: 10px; }
        .test-item {
            margin: 15px 0;
            padding: 10px;
            border-left: 4px solid #ddd;
            background: #f9f9f9;
        }
        .test-item.success {
            border-left-color: #28a745;
            background: #d4edda;
        }
        .test-item.error {
            border-left-color: #dc3545;
            background: #f8d7da;
        }
        .test-item.info {
            border-left-color: #17a2b8;
            background: #d1ecf1;
        }
        .label {
            font-weight: bold;
            color: #333;
        }
        .value {
            font-family: monospace;
            color: #666;
            margin-top: 5px;
        }
        .footer {
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
            font-size: 12px;
            color: #666;
        }
        button {
            background: #007bff;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            margin-top: 10px;
        }
        button:hover {
            background: #0056b3;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 SMTP Connection Test</h1>
        
        <h2>Configuration Status</h2>
        
        <div class="test-item info">
            <div class="label">SMTP Host:</div>
            <div class="value"><?= htmlspecialchars(defined('SMTP_HOST') ? SMTP_HOST : 'NOT DEFINED') ?></div>
        </div>
        
        <div class="test-item info">
            <div class="label">SMTP Port:</div>
            <div class="value"><?= htmlspecialchars(defined('SMTP_PORT') ? SMTP_PORT : 'NOT DEFINED') ?></div>
        </div>
        
        <div class="test-item info">
            <div class="label">SMTP Secure:</div>
            <div class="value"><?= htmlspecialchars(defined('SMTP_SECURE') ? SMTP_SECURE : 'NOT DEFINED') ?></div>
        </div>
        
        <div class="test-item info">
            <div class="label">SMTP User:</div>
            <div class="value"><?= htmlspecialchars(defined('SMTP_USER') ? SMTP_USER : 'NOT DEFINED') ?></div>
        </div>
        
        <h2>Connection Tests</h2>
        
        <?php
        // Test 1: Check if PHPMailer is available
        $pharMailerExists = file_exists(__DIR__ . '/../../includes/PHPMailerMaster/PHPMailerAutoload.php');
        $testResults[] = array(
            'name' => 'PHPMailer Library',
            'status' => $pharMailerExists ? 'success' : 'error',
            'message' => $pharMailerExists ? 'PHPMailer library found' : 'PHPMailer library NOT found at: /includes/PHPMailerMaster/'
        );
        
        // Test 2: Check SMTP configuration
        $smtpConfigured = defined('SMTP_HOST') && SMTP_HOST && defined('SMTP_USER') && SMTP_USER;
        $testResults[] = array(
            'name' => 'SMTP Configuration',
            'status' => $smtpConfigured ? 'success' : 'error',
            'message' => $smtpConfigured ? 'SMTP settings configured' : 'SMTP settings incomplete'
        );
        
        // Test 3: DNS resolution
        if (defined('SMTP_HOST') && SMTP_HOST) {
            $dnsCheck = @gethostbyname(SMTP_HOST);
            $dnsSuccess = $dnsCheck !== SMTP_HOST;
            $testResults[] = array(
                'name' => 'DNS Resolution',
                'status' => $dnsSuccess ? 'success' : 'error',
                'message' => $dnsSuccess ? 'Host resolves to: ' . $dnsCheck : 'Cannot resolve hostname'
            );
        }
        
        // Test 4: fsockopen connection test
        if (defined('SMTP_HOST') && defined('SMTP_PORT')) {
            $socket = @fsockopen(SMTP_HOST, SMTP_PORT, $errno, $errstr, 5);
            if ($socket) {
                fclose($socket);
                $testResults[] = array(
                    'name' => 'Socket Connection',
                    'status' => 'success',
                    'message' => 'Successfully connected to SMTP server'
                );
                $smtpConnected = true;
            } else {
                $testResults[] = array(
                    'name' => 'Socket Connection',
                    'status' => 'error',
                    'message' => 'Connection failed: ' . htmlspecialchars($errstr) . ' (Error code: ' . $errno . ')'
                );
            }
        }
        
        // Test 5: Try using PHPMailer
        if ($pharMailerExists && $smtpConfigured) {
            try {
                require_once __DIR__ . '/../../includes/PHPMailerMaster/PHPMailerAutoload.php';
                
                $mail = new PHPMailer();
                $mail->isSMTP();
                $mail->Host = SMTP_HOST;
                $mail->Port = SMTP_PORT;
                $mail->SMTPSecure = SMTP_SECURE;
                $mail->SMTPAuth = true;
                $mail->Username = SMTP_USER;
                $mail->Password = SMTP_PASS;
                $mail->Timeout = 10;
                $mail->SMTPDebug = 0;
                
                // Add SSL options for Office365
                $mail->SMTPOptions = array(
                    'ssl' => array(
                        'verify_peer' => false,
                        'verify_peer_name' => false,
                        'allow_self_signed' => true
                    )
                );
                
                // Try to connect
                if (@$mail->smtpConnect()) {
                    $testResults[] = array(
                        'name' => 'PHPMailer SMTP Connect',
                        'status' => 'success',
                        'message' => 'PHPMailer successfully connected to SMTP server'
                    );
                    @$mail->smtpClose();
                } else {
                    $testResults[] = array(
                        'name' => 'PHPMailer SMTP Connect',
                        'status' => 'error',
                        'message' => 'PHPMailer connection failed: ' . htmlspecialchars($mail->ErrorInfo)
                    );
                }
            } catch (Exception $e) {
                $testResults[] = array(
                    'name' => 'PHPMailer SMTP Connect',
                    'status' => 'error',
                    'message' => 'Exception: ' . htmlspecialchars($e->getMessage())
                );
            }
        }
        
        // Display test results
        foreach ($testResults as $result):
            $statusClass = $result['status'] === 'success' ? 'success' : ($result['status'] === 'error' ? 'error' : 'info');
        ?>
            <div class="test-item <?= $statusClass ?>">
                <div class="label">
                    <?= $result['status'] === 'success' ? '✓' : ($result['status'] === 'error' ? '✗' : 'ℹ') ?>
                    <?= htmlspecialchars($result['name']) ?>
                </div>
                <div class="value"><?= htmlspecialchars($result['message']) ?></div>
            </div>
        <?php endforeach; ?>
        
        <h2>Summary</h2>
        <?php
        $successCount = count(array_filter($testResults, function($r) { return $r['status'] === 'success'; }));
        $errorCount = count(array_filter($testResults, function($r) { return $r['status'] === 'error'; }));
        $totalCount = count($testResults);
        ?>
        <div class="test-item info">
            <div class="label">Passed:</div>
            <div class="value"><?= $successCount ?> / <?= $totalCount ?></div>
        </div>
        
        <?php if ($errorCount > 0): ?>
            <div class="test-item error">
                <div class="label">Failed:</div>
                <div class="value"><?= $errorCount ?> / <?= $totalCount ?></div>
            </div>
            
            <h3>Troubleshooting Tips</h3>
            <ul>
                <li><strong>DNS Resolution Failed:</strong> Check if <?= htmlspecialchars(SMTP_HOST) ?> is reachable from this server</li>
                <li><strong>Socket Connection Failed:</strong> Firewall may be blocking outbound connections to port <?= htmlspecialchars(SMTP_PORT) ?></li>
                <li><strong>PHPMailer Connection Failed:</strong> Check SMTP credentials are correct</li>
                <li><strong>Office365 Specific:</strong> May require app-specific password or OAuth2</li>
            </ul>
        <?php endif; ?>
        
        <div class="footer">
            <p>This test utility checks your SMTP configuration and connectivity.</p>
            <p><a href="index.php">← Back to Health Check</a></p>
        </div>
    </div>
</body>
</html>
