<?php
/**
 * SESSION VALIDATOR
 * Provides centralized session validation functions for all AJAX endpoints and pages
 * Ensures forced logouts are immediately enforced across all endpoints
 */

/**
 * Validate that user's session is still active in the database
 * Returns true if session is valid, false if session has been terminated
 * Includes grace period for new logins to allow activity to be recorded
 * 
 * @param mysqli $conDB Database connection
 * @param string $userId User ID from session
 * @param string $sessionId Current session ID
 * @param array $session $_SESSION array for grace period check
 * @return array ['valid' => bool, 'status' => string, 'message' => string]
 */
function validateSessionStatus($conDB, $userId, $sessionId, $session = null) {
    if (!$userId || !$sessionId) {
        return [
            'valid' => false,
            'status' => 'error',
            'message' => 'Missing user ID or session ID'
        ];
    }
    
    // Check if in grace period (30 seconds after login) - skip validation during this time
    // This allows time for the activity log to be inserted in session_check.php
    if ($session && isset($session['session_login_time'])) {
        $secondsSinceLogin = time() - $session['session_login_time'];
        if ($secondsSinceLogin < 30) {
            // Still in grace period, BUT we must verify the record exists first
            // If record exists and is logged_out, honor that regardless of grace period
            $quickCheckQuery = "SELECT `status` FROM `user_activity_log` 
                               WHERE `user_id` = ? AND `session_id` = ? 
                               LIMIT 1";
            
            $quickStmt = mysqli_prepare($conDB, $quickCheckQuery);
            if ($quickStmt) {
                mysqli_stmt_bind_param($quickStmt, "ss", $userId, $sessionId);
                mysqli_stmt_execute($quickStmt);
                $quickResult = mysqli_stmt_get_result($quickStmt);
                $quickRecord = mysqli_fetch_assoc($quickResult);
                mysqli_stmt_close($quickStmt);
                
                // If record exists but is logged_out, force logout immediately
                if ($quickRecord && $quickRecord['status'] !== 'active') {
                    return [
                        'valid' => false,
                        'status' => 'force_logout',
                        'message' => 'Your session has been terminated by an administrator'
                    ];
                }
            }
            
            // Grace period applies: either no record yet (being created) or record is active
            return [
                'valid' => true,
                'status' => 'active',
                'message' => 'Session is valid (grace period)'
            ];
        }
    }
    
    $query = "SELECT `id`, `status` FROM `user_activity_log` 
              WHERE `user_id` = ? AND `session_id` = ? 
              LIMIT 1";
    
    $stmt = mysqli_prepare($conDB, $query);
    if (!$stmt) {
        return [
            'valid' => false,
            'status' => 'error',
            'message' => 'Database error: ' . mysqli_error($conDB)
        ];
    }
    
    mysqli_stmt_bind_param($stmt, "ss", $userId, $sessionId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $sessionRecord = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
    
    // Session record doesn't exist - user was deleted or session cleared
    if (!$sessionRecord) {
        return [
            'valid' => false,
            'status' => 'terminated',
            'message' => 'Session record not found'
        ];
    }
    
    // Session status is not 'active' - user has been force-logged-out
    if ($sessionRecord['status'] !== 'active') {
        return [
            'valid' => false,
            'status' => 'force_logout',
            'message' => 'Your session has been terminated by an administrator'
        ];
    }
    
    // Session is valid
    return [
        'valid' => true,
        'status' => 'active',
        'message' => 'Session is valid'
    ];
}

/**
 * Check session validity and terminate if invalid
 * This should be called at the beginning of AJAX handlers
 * 
 * @param mysqli $conDB Database connection
 * @param array $session $_SESSION array
 * @param bool $returnJson Whether to return JSON or redirect (default: true for AJAX)
 * @return bool True if session is valid, exits if invalid
 */
function validateAndTerminateInvalidSession($conDB, $session, $returnJson = true) {
    // Use numeric ID for activity log lookup (stored in DB as integer)
    $userId = $session['auth_user']['numeric_id'] ?? null;
    $sessionId = session_id();
    
    // Pass $session to validateSessionStatus so grace period check works
    $validation = validateSessionStatus($conDB, $userId, $sessionId, $session);
    
    if (!$validation['valid']) {
        if ($returnJson) {
            // For AJAX requests, return JSON
            header('Content-Type: application/json');
            echo json_encode([
                'status' => 403,
                'message' => $validation['message'],
                'redirect' => './index.php'
            ]);
        } else {
            // For regular requests, redirect
            header("Location: ./index.php");
        }
        exit();
    }
    
    return true;
}

?>
