<?php
/**
 * Database Health Check - Token Generation & Email Configuration
 * 
 * This file configures dynamic token generation and email delivery
 */

// EMAIL CONFIGURATION
define('ADMIN_EMAIL', 'aneesmug2007@yahoo.com');
define('SENDER_EMAIL', 'noreply@almutlaksystem.com');
define('SENDER_NAME', 'Al-Mutlak WMS');

// TOKEN SETTINGS
define('TOKEN_EXPIRATION_MINUTES', 30);  // Tokens valid for 30 minutes
define('TOKEN_LENGTH', 32);               // 32 character tokens

// MAIL SETTINGS - Using PHPMailer with SMTP
define('MAIL_METHOD', 'phpmailer');  // Using PHPMailer library

// SMTP Settings for PHPMailer
define('SMTP_HOST', 'smtp.office365.com');
define('SMTP_PORT', 587);
define('SMTP_SECURE', 'tls');           // 'tls' or 'ssl'
define('SMTP_USER', 'noreply@almutlak.com');
define('SMTP_PASS', '@DiN512756539306#');

// Feature flags
define('ENABLE_TOKEN_LOGGING', true);
define('TOKEN_LOG_FILE', __DIR__ . '/token_requests.log');

/**
 * Log token requests for audit trail
 */
function logTokenRequest($action, $token = '', $email = '', $status = 'INFO') {
    if (!ENABLE_TOKEN_LOGGING) {
        return;
    }
    
    $logEntry = date('Y-m-d H:i:s') . ' | ' 
                . $status . ' | '
                . $action . ' | '
                . 'Email: ' . $email . ' | '
                . 'Token: ' . substr($token, 0, 10) . '*** | '
                . 'IP: ' . ($_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN') . "\n";
    
    @error_log($logEntry, 3, TOKEN_LOG_FILE);
}
?>
