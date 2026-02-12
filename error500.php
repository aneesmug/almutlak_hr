<?php
header("HTTP/1.1 500 Internal Server Error");
header("Content-Type: text/html; charset=UTF-8");

// Check if user is admin or in development mode
$is_admin = false;
$is_dev_mode = (php_uname('s') === 'Windows' || $_SERVER['HTTP_HOST'] === 'localhost' || $_SERVER['HTTP_HOST'] === '127.0.0.1');

// Try to include session to check if user is authenticated admin
try {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $is_admin = isset($_SESSION['user_type']) && ($_SESSION['user_type'] === 'admin' || $_SESSION['user_type'] === 'system_admin');
} catch (Exception $e) {
    // Session failed, continue without authentication check
}

// Get error details
$error_log_file = ini_get('error_log');
$error_details = [];
$show_details = ($is_admin || $is_dev_mode);

// Function to read last N lines from error log
function getLastErrorLogs($file, $lines = 10) {
    if (!file_exists($file) || !is_readable($file)) {
        return [];
    }
    
    $handle = fopen($file, 'r');
    if (!$handle) return [];
    
    $logs = [];
    $line_count = 0;
    $file_size = filesize($file);
    
    // Start from end of file
    fseek($handle, max(0, $file_size - 5000), SEEK_SET);
    
    while (!feof($handle)) {
        $line = fgets($handle);
        if ($line !== false) {
            $logs[] = trim($line);
        }
    }
    fclose($handle);
    
    return array_slice($logs, -$lines);
}

$recent_errors = getLastErrorLogs($error_log_file, 10);
?>
<!DOCTYPE html>
<html lang="<?= isset($current_lang) ? $current_lang : 'en' ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>500 - Server Error</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 20px;
        }
        
        .error-container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            padding: 50px 40px;
            max-width: 700px;
            width: 100%;
        }
        
        .error-header {
            text-align: center;
            margin-bottom: 30px;
        }
        
        .error-icon {
            font-size: 60px;
            margin-bottom: 20px;
        }
        
        .error-code {
            font-size: 100px;
            font-weight: bold;
            color: #dc3545;
            line-height: 1;
            margin-bottom: 20px;
        }
        
        .error-title {
            font-size: 28px;
            color: #333;
            margin-bottom: 15px;
            font-weight: 600;
        }
        
        .error-message {
            font-size: 16px;
            color: #666;
            line-height: 1.6;
            margin-bottom: 30px;
        }
        
        .btn-home {
            display: inline-block;
            padding: 12px 30px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            text-decoration: none;
            border-radius: 8px;
            transition: transform 0.3s, box-shadow 0.3s;
            font-weight: 600;
        }
        
        .btn-home:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.4);
        }
        
        .error-details {
            margin-top: 40px;
            padding-top: 30px;
            border-top: 2px solid #eee;
        }
        
        .details-title {
            font-size: 18px;
            font-weight: 600;
            color: #333;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .details-title i {
            font-size: 20px;
        }
        
        .error-log {
            background: #f5f5f5;
            border-left: 4px solid #dc3545;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            font-family: 'Courier New', monospace;
            font-size: 12px;
            max-height: 400px;
            overflow-y: auto;
            line-height: 1.6;
            color: #333;
        }
        
        .error-log-item {
            margin-bottom: 10px;
            padding-bottom: 10px;
            border-bottom: 1px solid #ddd;
        }
        
        .error-log-item:last-child {
            border-bottom: none;
            margin-bottom: 0;
            padding-bottom: 0;
        }
        
        .error-timestamp {
            color: #999;
            font-size: 11px;
        }
        
        .error-text {
            color: #dc3545;
            font-weight: 600;
            margin-top: 5px;
            word-break: break-word;
        }
        
        .info-box {
            background: #e7f3ff;
            border-left: 4px solid #2196F3;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            font-size: 13px;
            color: #0c5394;
        }
        
        .warning-box {
            background: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            font-size: 13px;
            color: #856404;
        }
        
        .footer-info {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #eee;
            font-size: 12px;
            color: #999;
            text-align: center;
        }
        
        .hidden-admin {
            display: none;
        }
        
        .show-details-btn {
            background: #17a2b8;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            margin-top: 15px;
            transition: background 0.3s;
        }
        
        .show-details-btn:hover {
            background: #138496;
        }
        
        @media (max-width: 600px) {
            .error-container {
                padding: 30px 20px;
            }
            
            .error-code {
                font-size: 70px;
            }
            
            .error-log {
                font-size: 11px;
                padding: 10px;
            }
        }
    </style>
</head>
<body>
    <div class="error-container">
        <div class="error-header">
            <div class="error-icon">⚠️</div>
            <div class="error-code">500</div>
            <div class="error-title">Internal Server Error</div>
            
            <div class="error-message">
                <p>Sorry, something went wrong on our server.</p>
                <p>Our team has been notified and we're working to fix it.</p>
            </div>
            
            <a href="/" class="btn-home">← Back to Home</a>
        </div>
        
        <?php if ($show_details): ?>
            <div class="error-details">
                <div class="details-title">
                    <span>🔧 Error Details (Admin View)</span>
                </div>
                
                <?php if ($is_dev_mode): ?>
                    <div class="warning-box">
                        <strong>Development Mode:</strong> You are viewing detailed error information because you're on a local/development server.
                    </div>
                <?php endif; ?>
                
                <?php if ($error_log_file && file_exists($error_log_file)): ?>
                    <div class="info-box">
                        <strong>Error Log Location:</strong> <code><?php echo htmlspecialchars($error_log_file); ?></code>
                    </div>
                    
                    <?php if (!empty($recent_errors)): ?>
                        <div class="details-title">Recent Error Logs:</div>
                        <div class="error-log">
                            <?php foreach ($recent_errors as $error): ?>
                                <?php if (!empty($error)): ?>
                                    <div class="error-log-item">
                                        <div class="error-text"><?php echo htmlspecialchars($error); ?></div>
                                    </div>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="info-box">
                            No error logs found yet. Check your error log file directly.
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="warning-box">
                        <strong>Error Log Not Configured:</strong> The PHP error log is not configured. Add this to your php.ini: <code>error_log = /path/to/error.log</code>
                    </div>
                <?php endif; ?>
                
                <div style="margin-top: 20px; font-size: 12px; color: #666;">
                    <strong>Server Information:</strong><br>
                    Server: <?php echo htmlspecialchars($_SERVER['SERVER_SOFTWARE'] ?? 'Unknown'); ?><br>
                    Requested: <?php echo htmlspecialchars($_SERVER['REQUEST_METHOD']); ?> <?php echo htmlspecialchars($_SERVER['REQUEST_URI']); ?><br>
                    PHP Version: <?php echo phpversion(); ?><br>
                    Time: <?php echo date('Y-m-d H:i:s'); ?>
                </div>
            </div>
        <?php else: ?>
            <div class="error-details">
                <div class="info-box">
                    <strong>Need Help?</strong> If this error persists, please contact the administrator with the timestamp above.
                </div>
                <div style="text-align: center; color: #999; font-size: 12px; margin-top: 20px;">
                    Error ID: <?php echo date('Ymdhis'); ?>
                </div>
            </div>
        <?php endif; ?>
        
        <div class="footer-info">
            <p>If you are the administrator and want to see detailed error information, log in first.</p>
            <p><?php echo date('Y-m-d H:i:s'); ?></p>
        </div>
    </div>
</body>
</html>
