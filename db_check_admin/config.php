<?php
/**
 * Database Health Check - Access Control Configuration
 * 
 * This file stores the secure checkpoint token needed to access
 * the database health check page. This prevents unauthorized
 * users from viewing sensitive database information.
 * 
 * IMPORTANT: Change the token value to something only you know!
 * The token should be added to the URL as: ?checkpoint=YOUR_TOKEN
 * 
 * Example URL: http://localhost/almutlak/system/db_check_admin/index.php?checkpoint=your_secret_token_here
 */

// CHECKPOINT CONFIG - Change this token to a secure value
// Use a mix of letters, numbers, and special characters
// Example: AbC123!@#xYz456$%^ABC789
define('DB_HEALTH_CHECK_TOKEN', 'admin_health_check_2026_secured');

// Enable checkpoint logging (logs access attempts)
define('ENABLE_CHECKPOINT_LOG', true);
define('CHECKPOINT_LOG_FILE', __DIR__ . '/access_log.txt');

// Optional: Maintenance mode bypass
// If true, only works during maintenance mode for extra safety
define('REQUIRE_MAINTENANCE_MODE', false);

// NOTE: Session requirement is now DISABLED
// Only checkpoint token is required for access (no login needed)
// This allows health check to work during maintenance when login may be unavailable

/**
 * Helper function to verify checkpoint
 */
function verifCheckpoint() {
    $token = $_GET['checkpoint'] ?? '';
    $isValid = ($token === DB_HEALTH_CHECK_TOKEN);
    
    // Log access attempt
    if (ENABLE_CHECKPOINT_LOG) {
        $logEntry = date('Y-m-d H:i:s') . ' | ' 
                    . ($_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN_IP') . ' | '
                    . ($isValid ? 'SUCCESS' : 'FAILED') . ' | '
                    . 'Token: ' . substr($token, 0, 10) . '***' . "\n";
        
        @error_log($logEntry, 3, CHECKPOINT_LOG_FILE);
    }
    
    return $isValid;
}

/**
 * Helper function to get correct access URL
 */
function getAccessUrl() {
    $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    $path = '/almutlak/system/db_check_admin/index.php';
    return $protocol . '://' . $host . $path . '?checkpoint=' . DB_HEALTH_CHECK_TOKEN;
}
?>
