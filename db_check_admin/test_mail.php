<?php
/**
 * Simple PHP mail() Test
 * Tests if basic PHP mail() function works
 */

$result = false;
$output = '';
$testEmail = 'aneesmug2007@yahoo.com';

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>PHP mail() Test</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { max-width: 600px; margin: 0 auto; background: white; padding: 20px; border-radius: 5px; }
        .success { background: #d4edda; border-left: 4px solid #28a745; padding: 15px; color: #155724; }
        .error { background: #f8d7da; border-left: 4px solid #dc3545; padding: 15px; color: #721c24; }
        .info { background: #d1ecf1; border-left: 4px solid #17a2b8; padding: 15px; color: #0c5460; }
        .code { background: #f5f5f5; border: 1px solid #ddd; padding: 10px; margin: 10px 0; font-family: monospace; }
        button { background: #007bff; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; }
        button:hover { background: #0056b3; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🧪 PHP mail() Function Test</h1>
        
        <div class="info">
            <strong>Test Purpose:</strong> Verify that PHP's built-in mail() function can send emails on this server.
        </div>
        
        <h2>Configuration</h2>
        <div class="code">
sendmail_path: <?php echo htmlspecialchars(ini_get('sendmail_path')); ?><br>
SMTP Host: <?php echo htmlspecialchars(ini_get('SMTP')); ?><br>
SMTP Port: <?php echo htmlspecialchars(ini_get('smtp_port')); ?><br>
mail.add_x_header: <?php echo htmlspecialchars(ini_get('mail.add_x_header')); ?><br>
        </div>
        
        <h2>Send Test Email</h2>
        <form method="POST">
            <label>Email Address:</label><br>
            <input type="email" name="email" value="<?php echo htmlspecialchars($testEmail); ?>" required style="width: 100%; padding: 8px; margin: 10px 0;">
            
            <button type="submit" name="send_test" value="1">📧 Send Test Email via mail()</button>
        </form>
        
        <?php
        // Handle form submission
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_test'])) {
            $toEmail = $_POST['email'] ?? $testEmail;
            $subject = 'PHP mail() Test - ' . date('Y-m-d H:i:s');
            $message = "
<!DOCTYPE html>
<html>
<head><meta charset='UTF-8'></head>
<body>
<h2>PHP mail() Function Test</h2>
<p>This is a test email sent using PHP's mail() function.</p>
<p><strong>Timestamp:</strong> " . date('Y-m-d H:i:s') . "</p>
<p><strong>Server:</strong> " . htmlspecialchars($_SERVER['HTTP_HOST'] ?? 'localhost') . "</p>
<p>If you received this email, PHP mail() is working correctly!</p>
</body>
</html>
            ";
            
            $headers = "From: noreply@almutlaksystem.com\r\n";
            $headers .= "Reply-To: noreply@almutlaksystem.com\r\n";
            $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
            $headers .= "X-Mailer: PHP mail() Test\r\n";
            
            // Attempt to send
            $result = @mail($toEmail, $subject, $message, $headers);
            
            echo '<h2>Test Result</h2>';
            if ($result) {
                echo '<div class="success">
                    <strong>✓ SUCCESS</strong><br>
                    Email submitted to system mail queue.<br>
                    <strong>To:</strong> ' . htmlspecialchars($toEmail) . '<br>
                    <strong>Subject:</strong> ' . htmlspecialchars($subject) . '<br>
                    <br>
                    Check your email in 1-5 minutes. If you don\'t receive it:<br>
                    1. Check spam/junk folder<br>
                    2. Contact server administrator
                </div>';
            } else {
                echo '<div class="error">
                    <strong>✗ FAILED</strong><br>
                    mail() function returned false. Possible causes:<br>
                    <ul>
                    <li>sendmail_path is not configured</li>
                    <li>No local mail server running</li>
                    <li>Mail server is not responding</li>
                    <li>To address is invalid</li>
                    </ul>
                </div>';
            }
        }
        ?>
        
        <h2>Troubleshooting</h2>
        <div class="info">
            <strong>If mail() returns false:</strong><br>
            1. Check if sendmail is installed: <code>which sendmail</code><br>
            2. Check sendmail_path in php.ini<br>
            3. Verify mail service is running: <code>service postfix status</code><br>
            4. Contact server administrator
        </div>
        
        <h2>Log Check</h2>
        <div class="code">
<?php
// Try to read mail log
$logPaths = [
    '/var/log/mail.log',
    '/var/log/maillog',
    '/var/log/postfix/main.log',
];

foreach ($logPaths as $logPath) {
    if (file_exists($logPath)) {
        echo '<strong>Mail log found at:</strong> ' . htmlspecialchars($logPath) . '<br>';
        echo 'Last 5 lines:<br>';
        $lines = array_slice(file($logPath), -5);
        foreach ($lines as $line) {
            echo htmlspecialchars(trim($line)) . '<br>';
        }
        break;
    }
}

if (empty($logPath)) {
    echo 'Mail log not found in standard locations.';
}
?>
        </div>
        
        <hr style="margin: 20px 0;">
        <p style="font-size: 12px; color: #666;">
            <a href="index.php">← Back to Health Check</a> | 
            <a href="test_smtp.php">SMTP Connection Test →</a>
        </p>
    </div>
</body>
</html>
