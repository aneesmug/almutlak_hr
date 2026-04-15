<?php
/**
 * Token Storage - Manages dynamic token generation and verification
 * 
 * Tokens are stored with expiration times
 * Default expiration: 30 minutes after generation
 */

class TokenManager {
    private $tokenFile;
    private $tokenExpiration = 1800; // 30 minutes in seconds
    
    public function __construct() {
        $this->tokenFile = __DIR__ . '/tokens.json';
    }
    
    /**
     * Generate a new random token
     */
    public function generateToken() {
        // Generate 32-character random token
        $token = bin2hex(random_bytes(16));
        
        // Store token with expiration
        $tokens = $this->getTokens();
        $tokens[$token] = [
            'created' => time(),
            'expires' => time() + $this->tokenExpiration,
            'used' => false
        ];
        
        $this->saveTokens($tokens);
        return $token;
    }
    
    /**
     * Verify if token is valid and not expired
     */
    public function verifyToken($token) {
        if (empty($token)) {
            return false;
        }
        
        $tokens = $this->getTokens();
        
        if (!isset($tokens[$token])) {
            return false;
        }
        
        $tokenData = $tokens[$token];
        
        // Check if token has expired
        if (time() > $tokenData['expires']) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Mark token as used
     */
    public function markTokenAsUsed($token) {
        $tokens = $this->getTokens();
        if (isset($tokens[$token])) {
            $tokens[$token]['used'] = true;
            $this->saveTokens($tokens);
        }
    }
    
    /**
     * Get all tokens
     */
    private function getTokens() {
        if (!file_exists($this->tokenFile)) {
            return [];
        }
        
        $content = file_get_contents($this->tokenFile);
        $tokens = json_decode($content, true);
        
        return is_array($tokens) ? $tokens : [];
    }
    
    /**
     * Save tokens to file
     */
    private function saveTokens($tokens) {
        // Clean up expired tokens
        $tokens = $this->cleanupExpiredTokens($tokens);
        
        // Create directory if it doesn't exist
        if (!is_dir(dirname($this->tokenFile))) {
            mkdir(dirname($this->tokenFile), 0755, true);
        }
        
        file_put_contents(
            $this->tokenFile,
            json_encode($tokens, JSON_PRETTY_PRINT),
            LOCK_EX
        );
    }
    
    /**
     * Remove expired tokens
     */
    private function cleanupExpiredTokens($tokens) {
        $now = time();
        return array_filter($tokens, function($data) use ($now) {
            return $data['expires'] > $now;
        });
    }
    
    /**
     * Get token info (for debugging)
     */
    public function getTokenInfo($token) {
        $tokens = $this->getTokens();
        return $tokens[$token] ?? null;
    }
    
    /**
     * Get time remaining for token (in seconds)
     */
    public function getTimeRemaining($token) {
        $tokenData = $this->getTokenInfo($token);
        if (!$tokenData) {
            return 0;
        }
        
        $remaining = $tokenData['expires'] - time();
        return max(0, $remaining);
    }
}

/**
 * Email Sender - Sends token via email
 */
class EmailSender {
    private $recipientEmail;
    private $senderEmail;
    
    public function __construct($recipientEmail, $senderEmail = 'noreply@almutlaksystem.com') {
        $this->recipientEmail = $recipientEmail;
        $this->senderEmail = $senderEmail;
    }
    
    /**
     * Send token email
     */
    public function sendTokenEmail($token, $accessUrl = '') {
        $fullUrl = $accessUrl ?: $this->buildAccessUrl($token);
        
        $subject = "Database Health Check - Your Access Token";
        
        $message = "
<!DOCTYPE html>
<html>
<head>
    <meta charset='UTF-8'>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background: #007bff; color: white; padding: 20px; border-radius: 5px 5px 0 0; text-align: center; }
        .content { background: #f9f9f9; padding: 20px; border: 1px solid #ddd; }
        .token-box { background: #fff3cd; border: 1px solid #ffc107; padding: 15px; border-radius: 5px; margin: 20px 0; text-align: center; }
        .token-box .label { color: #856404; font-size: 12px; text-transform: uppercase; margin-bottom: 5px; }
        .token-box .token { font-size: 18px; font-weight: bold; font-family: monospace; color: #333; word-break: break-all; }
        .url-box { background: #e7f3ff; border: 1px solid #b3d9ff; padding: 15px; border-radius: 5px; margin: 20px 0; }
        .url-box .label { color: #004085; font-size: 12px; text-transform: uppercase; margin-bottom: 5px; }
        .url-box .url { font-size: 12px; font-family: monospace; color: #004085; word-break: break-all; }
        .link-button { display: inline-block; background: #007bff; color: white; padding: 12px 30px; text-decoration: none; border-radius: 5px; margin-top: 10px; }
        .footer { background: #f0f0f0; padding: 15px; text-align: center; font-size: 12px; color: #666; border-radius: 0 0 5px 5px; }
        .warning { color: #dc3545; font-size: 12px; margin-top: 10px; }
    </style>
</head>
<body>
    <div class='container'>
        <div class='header'>
            <h2>Database Health Check Access</h2>
        </div>
        
        <div class='content'>
            <p>Hello,</p>
            
            <p>A new access token has been generated for the Database Health Check dashboard.</p>
            
            <p><strong>Your Access Token:</strong></p>
            <div class='token-box'>
                <div class='label'>Token (Valid for 30 minutes)</div>
                <div class='token'>{$token}</div>
            </div>
            
            <p><strong>Method 1 - Direct Link:</strong></p>
            <div class='url-box'>
                <div class='label'>Click the link below or copy it to your browser:</div>
                <a href='{$fullUrl}' class='link-button'>Access Health Check</a>
                <div class='url'>{$fullUrl}</div>
            </div>
            
            <p><strong>Method 2 - Use Token Parameter:</strong></p>
            <ol>
                <li>Go to: <code>/db_check_admin/</code></li>
                <li>When prompted, paste the token above</li>
                <li>Click 'Verify Token'</li>
            </ol>
            
            <div class='warning'>
                ⚠️ <strong>Security Notice:</strong>
                <ul>
                    <li>This token expires in 30 minutes</li>
                    <li>Do not share this token with anyone</li>
                    <li>Do not reply to this email</li>
                    <li>If you did not request this, ignore this email</li>
                </ul>
            </div>
        </div>
        
        <div class='footer'>
            <p>Al-Mutlak Warehouse Management System</p>
            <p>Generated: " . date('Y-m-d H:i:s') . "</p>
        </div>
    </div>
</body>
</html>
        ";
        
        return $this->sendEmail($subject, $message);
    }
    
    /**
     * Send email using PHP mail() function (PHPMailer fallback if available)
     */
    private function sendEmail($subject, $message) {
        try {
            // Set timeout for this operation
            set_time_limit(15);
            
            $result = false;
            
            // PRIMARY METHOD: Try PHPMailer first (better reliability with Office365)
            if (defined('ENABLE_TOKEN_LOGGING') && ENABLE_TOKEN_LOGGING) {
                @error_log('Attempting email send via PHPMailer SMTP...' . "\n", 3, defined('TOKEN_LOG_FILE') ? TOKEN_LOG_FILE : '');
            }
            
            // CORRECT PATH: From db_check_admin go up 2 levels to system, then into includes
            $phpmailerPath = __DIR__ . '/../../includes/PHPMailerMaster/PHPMailerAutoload.php';
            
            if (file_exists($phpmailerPath)) {
                try {
                    require_once $phpmailerPath;
                    
                    // Create PHPMailer instance
                    $mail = new PHPMailer();
                    
                    // Set timeout and debugging
                    $mail->Timeout = 10;
                    $mail->SMTPDebug = 0;
                    
                    // Set SMTP configuration
                    if (defined('SMTP_HOST') && SMTP_HOST) {
                        $mail->isSMTP();
                        $mail->Host = SMTP_HOST;
                        $mail->Port = defined('SMTP_PORT') ? SMTP_PORT : 587;
                        $mail->SMTPAuth = true;
                        $mail->SMTPSecure = defined('SMTP_SECURE') ? SMTP_SECURE : 'tls';
                        $mail->Username = defined('SMTP_USER') ? SMTP_USER : '';
                        $mail->Password = defined('SMTP_PASS') ? SMTP_PASS : '';
                        
                        // Add SSL options for Office365
                        $mail->SMTPOptions = array(
                            'ssl' => array(
                                'verify_peer' => false,
                                'verify_peer_name' => false,
                                'allow_self_signed' => true
                            )
                        );
                    } else {
                        $mail->isMail();
                    }
                    
                    // Set email details
                    $mail->setFrom($this->senderEmail, 'Al-Mutlak WMS');
                    $mail->addAddress($this->recipientEmail);
                    $mail->addReplyTo($this->senderEmail, 'Al-Mutlak WMS');
                    $mail->isHTML(true);
                    $mail->Subject = $subject;
                    $mail->Body = $message;
                    $mail->AltBody = strip_tags(str_replace('<br>', "\n", $message));
                    
                    // Send via PHPMailer
                    if ($mail->send()) {
                        if (defined('ENABLE_TOKEN_LOGGING') && ENABLE_TOKEN_LOGGING) {
                            @error_log('Email sent successfully via PHPMailer SMTP' . "\n", 3, defined('TOKEN_LOG_FILE') ? TOKEN_LOG_FILE : '');
                        }
                        return true;
                    } else {
                        if (defined('ENABLE_TOKEN_LOGGING') && ENABLE_TOKEN_LOGGING) {
                            @error_log('PHPMailer send failed: ' . $mail->ErrorInfo . "\n", 3, defined('TOKEN_LOG_FILE') ? TOKEN_LOG_FILE : '');
                        }
                    }
                } catch (Exception $e) {
                    if (defined('ENABLE_TOKEN_LOGGING') && ENABLE_TOKEN_LOGGING) {
                        @error_log('PHPMailer exception: ' . $e->getMessage() . "\n", 3, defined('TOKEN_LOG_FILE') ? TOKEN_LOG_FILE : '');
                    }
                }
            } else {
                if (defined('ENABLE_TOKEN_LOGGING') && ENABLE_TOKEN_LOGGING) {
                    @error_log('PHPMailer NOT found at expected path: ' . $phpmailerPath . "\n", 3, defined('TOKEN_LOG_FILE') ? TOKEN_LOG_FILE : '');
                }
            }
            
            // FALLBACK METHOD: If PHPMailer fails, try PHP mail() function
            if (!$result) {
                if (defined('ENABLE_TOKEN_LOGGING') && ENABLE_TOKEN_LOGGING) {
                    @error_log('Attempting fallback to PHP mail() function...' . "\n", 3, defined('TOKEN_LOG_FILE') ? TOKEN_LOG_FILE : '');
                }
                
                $headers = "From: " . $this->senderEmail . "\r\n";
                $headers .= "Reply-To: " . $this->senderEmail . "\r\n";
                $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
                $headers .= "X-Mailer: Al-Mutlak WMS Token System\r\n";
                
                $result = @mail($this->recipientEmail, $subject, $message, $headers);
                
                if ($result) {
                    if (defined('ENABLE_TOKEN_LOGGING') && ENABLE_TOKEN_LOGGING) {
                        @error_log('Email sent successfully via PHP mail() fallback' . "\n", 3, defined('TOKEN_LOG_FILE') ? TOKEN_LOG_FILE : '');
                    }
                    return true;
                }
            }
            
            // If we get here, log final failure
            if (defined('ENABLE_TOKEN_LOGGING') && ENABLE_TOKEN_LOGGING) {
                @error_log('Email delivery failed via all methods' . "\n", 3, defined('TOKEN_LOG_FILE') ? TOKEN_LOG_FILE : '');
            }
            
            return false;
        } catch (Exception $e) {
            // Log any uncaught exceptions
            if (defined('ENABLE_TOKEN_LOGGING') && ENABLE_TOKEN_LOGGING) {
                @error_log('Email Send Exception: ' . $e->getMessage() . "\n", 3, defined('TOKEN_LOG_FILE') ? TOKEN_LOG_FILE : '');
            }
            return false;
        }
    }
    
    /**
     * Build full access URL with token
     */
    private function buildAccessUrl($token) {
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'];
        $path = '/almutlak/system/db_check_admin/?checkpoint=' . $token;
        
        return $protocol . '://' . $host . $path;
    }
}

?>
