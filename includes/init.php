<?php
// File: includes/init.php (Final Updated)
// This file should be included at the very top of all your user-facing PHP pages.

/**************************************************************************************************
 * MODIFICATION SUMMARY
 *
 * 1.  Removed dependency on ?lang=xx in URL. Now language comes directly from user profile (DB).
 * 2.  Introduced ?change_lang=xx only for switching, then auto-redirects back with same query string
 *     (without leaving change_lang in the URL).
 * 3.  Language preference is saved in DB (for logged-in users) and synced in session.
 * 4.  All original layout/logic preserved.
 **************************************************************************************************/

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Include core files
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/translation_functions.php';

// --- User and Language Setup ---

$is_user_logged_in = isset($_SESSION['auth_user']['user_id']);
$username = $is_user_logged_in ? $_SESSION['auth_user']['user_id'] : null;

// ACTION: Handle explicit language change via ?change_lang=xx
if (isset($_GET['change_lang']) && in_array($_GET['change_lang'], ['en', 'ar'])) {
    $selected_lang = $_GET['change_lang'];
    $_SESSION['lang'] = $selected_lang;

    // If the user is logged in, update permanent preference in DB
    if ($is_user_logged_in && $username) {
        $stmt = mysqli_prepare($conDB, "UPDATE `admin_login` SET `preferred_language` = ? WHERE `id_iqama` = ?");
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "ss", $selected_lang, $username);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
        }
    }

    // Redirect back to same page without change_lang param, keeping other query params
    $parsedUrl = parse_url($_SERVER['REQUEST_URI']);
    $query = [];
    if (!empty($parsedUrl['query'])) {
        parse_str($parsedUrl['query'], $query);
        unset($query['change_lang']);
    }
    $redirectUrl = $parsedUrl['path'];
    if (!empty($query)) {
        $redirectUrl .= '?' . http_build_query($query);
    }
    header("Location: $redirectUrl");
    exit();
}

// DETERMINATION: Figure out which language to display
$current_lang = 'en'; // Default

if ($is_user_logged_in && $username) {
    // Fetch language from DB
    $stmt = mysqli_prepare($conDB, "SELECT `preferred_language` FROM `admin_login` WHERE `id_iqama` = ?");
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "s", $username);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        if ($lang_result = mysqli_fetch_assoc($result)) {
            if (!empty($lang_result['preferred_language']) && in_array($lang_result['preferred_language'], ['en', 'ar'])) {
                $current_lang = $lang_result['preferred_language'];
            }
        }
        mysqli_stmt_close($stmt);
    }
} elseif (isset($_SESSION['lang']) && in_array($_SESSION['lang'], ['en', 'ar'])) {
    $current_lang = $_SESSION['lang'];
}

// Sync session
$_SESSION['lang'] = $current_lang;

// RTL flag
$is_rtl = ($current_lang === 'ar');

// Load translations
load_language($current_lang);

// Global settings
$site_name   = get_setting($conDB, 'application_name');
$site_title  = get_setting($conDB, 'application_name')." | cPanel";
$site_footer = "2008 - " . date("Y") . " © SnapS Production House";

/**************************************************************************************************
 * ACTIVITY LOGGER CLASS
 * 
 * Comprehensive activity tracking system for all user actions
 * 
 * Usage Examples:
 * - ActivityLogger::log('CREATE', 'Employee', 'Created new employee', ['record_id' => '123']);
 * - ActivityLogger::logCreate('Employee', 'add_employee.php', '123', ['name' => 'Ahmed'], 'Created employee Ahmed');
 * - ActivityLogger::logUpdate('Vacation', 'edit_vacation.php', '456', $old, $new, 'Updated vacation dates');
 * - ActivityLogger::logDelete('Loan', 'delete_loan.php', '789', $oldData);
 * - ActivityLogger::logLogin($empid, $empname);
 * - ActivityLogger::logApproval('Vacation', 'approve_vacation.php', '456', 'approved', 'Approved 5 days vacation');
 **************************************************************************************************/

if (!class_exists('ActivityLogger')) {

class ActivityLogger {
    
/**
 * Main logging function
 * 
 * @param string $action_type CREATE|UPDATE|DELETE|LOGIN|LOGOUT|VIEW|DOWNLOAD|UPLOAD|APPROVE|REJECT|SUBMIT|EXPORT|IMPORT|OTHER
 * @param string $module Module name (e.g., Employee, Vacation, Loan, Payroll)
 * @param string $description Human-readable description
 * @param array $options Additional options:
 *   - user_id: Override user ID (auto-detected if not provided)
 *   - user_name: User name for quick reference
 *   - record_id: ID of affected record
 *   - table_name: Database table name
 *   - old_values: Array of old values (will be JSON encoded)
 *   - new_values: Array of new values (will be JSON encoded)
 *   - page: Override page name (auto-detected if not provided)
 *   - severity: INFO|WARNING|CRITICAL|ERROR (default: INFO)
 *   - status: SUCCESS|FAILED|PENDING (default: SUCCESS)
 *   - allow_duplicates_after_minutes: Minutes before allowing same action again (default: 5)
 * @return bool Success status
 */
public static function log($action_type, $module, $description, $options = []) {
    global $conDB, $empid, $fname;
    
    // Don't log if database connection not available
    if (!isset($conDB) || !$conDB) {
        return false;
    }
    
    // Get user information
    $user_id = $options['user_id'] ?? $empid ?? $_SESSION['user_id'] ?? $_SESSION['auth_user']['user_id'] ?? 'SYSTEM';
    $user_name = $options['user_name'] ?? $fname ?? $_SESSION['fname'] ?? '';
    
    // Get page information
    $page = $options['page'] ?? basename($_SERVER['PHP_SELF'] ?? 'unknown');
    
    // Get record information
    $record_id = $options['record_id'] ?? null;
    $table_name = $options['table_name'] ?? null;
    
    // Get deduplication time window (in minutes)
    $duplicate_check_minutes = $options['allow_duplicates_after_minutes'] ?? 5;
    
        // Get values (encode as JSON if arrays)
        $old_values = isset($options['old_values']) ? json_encode($options['old_values'], JSON_UNESCAPED_UNICODE) : null;
        $new_values = isset($options['new_values']) ? json_encode($options['new_values'], JSON_UNESCAPED_UNICODE) : null;
        
        // --- DEDUPLICATION CHECK ---
        // Check if this exact action was logged recently to prevent duplicate entries on page refresh
        // BUT: If data has changed (old_values != new_values), log immediately
        if (!self::shouldLogAction($action_type, $module, $record_id, $user_id, $old_values, $new_values, $duplicate_check_minutes)) {
            // Same action with same data logged too recently, skip
            return false;
        }
        // --- END DEDUPLICATION CHECK ---
        
        // Get request information
        $ip_address = self::getIpAddress();
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? null;
        
        // Get severity and status
        $severity = $options['severity'] ?? 'INFO';
        $status = $options['status'] ?? 'SUCCESS';
        
        // Prepare SQL
        $sql = "INSERT INTO activity_log (
                    user_id, user_name, action_type, module, page, 
                    record_id, table_name, description, old_values, new_values,
                    ip_address, user_agent, severity, status
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        // Use prepared statement
        if ($stmt = mysqli_prepare($conDB, $sql)) {
            mysqli_stmt_bind_param($stmt, 'ssssssssssssss',
                $user_id, $user_name, $action_type, $module, $page,
                $record_id, $table_name, $description, $old_values, $new_values,
                $ip_address, $user_agent, $severity, $status
            );
            
            $result = mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
            
            // Update session cache with this logged action
            if ($result) {
                self::updateLogCache($action_type, $module, $record_id, $user_id, $new_values);
            }
            
            return $result;
        }
        
        return false;
    }
    
    /**
     * Log CREATE action
     */
    public static function logCreate($module, $page, $record_id, $new_values = null, $description = null, $table_name = null) {
        $desc = $description ?? "Created new {$module}";
        return self::log('CREATE', $module, $desc, [
            'page' => $page,
            'record_id' => $record_id,
            'table_name' => $table_name,
            'new_values' => $new_values
        ]);
    }
    
    /**
     * Log UPDATE action
     */
    public static function logUpdate($module, $page, $record_id, $old_values = null, $new_values = null, $description = null, $table_name = null) {
        $desc = $description ?? "Updated {$module} record";
        return self::log('UPDATE', $module, $desc, [
            'page' => $page,
            'record_id' => $record_id,
            'table_name' => $table_name,
            'old_values' => $old_values,
            'new_values' => $new_values
        ]);
    }
    
    /**
     * Log DELETE action
     */
    public static function logDelete($module, $page, $record_id, $old_values = null, $description = null, $table_name = null) {
        $desc = $description ?? "Deleted {$module} record";
        return self::log('DELETE', $module, $desc, [
            'page' => $page,
            'record_id' => $record_id,
            'table_name' => $table_name,
            'old_values' => $old_values,
            'severity' => 'WARNING'
        ]);
    }
    
    /**
     * Log LOGIN action
     */
    public static function logLogin($user_id = null, $user_name = null) {
        global $empid, $fname;
        $uid = $user_id ?? $empid;
        $uname = $user_name ?? $fname;
        
        return self::log('LOGIN', 'Authentication', "User logged in", [
            'user_id' => $uid,
            'user_name' => $uname,
            'page' => 'login.php'
        ]);
    }
    
    /**
     * Log LOGOUT action
     */
    public static function logLogout() {
        return self::log('LOGOUT', 'Authentication', "User logged out", [
            'page' => 'logout.php'
        ]);
    }
    
    /**
     * Log APPROVAL action (APPROVE or REJECT)
     */
    public static function logApproval($module, $page, $record_id, $approval_status, $comments = null, $table_name = null) {
        $action = strtoupper($approval_status) === 'APPROVED' ? 'APPROVE' : 'REJECT';
        $desc = ucfirst(strtolower($approval_status)) . " {$module} request";
        if ($comments) {
            $desc .= ": {$comments}";
        }
        
        return self::log($action, $module, $desc, [
            'page' => $page,
            'record_id' => $record_id,
            'table_name' => $table_name,
            'new_values' => ['status' => $approval_status, 'comments' => $comments],
            'severity' => $action === 'REJECT' ? 'WARNING' : 'INFO'
        ]);
    }
    
    /**
     * Log file UPLOAD action
     */
    public static function logUpload($module, $page, $filename, $record_id = null, $table_name = null) {
        return self::log('UPLOAD', $module, "Uploaded file: {$filename}", [
            'page' => $page,
            'record_id' => $record_id,
            'table_name' => $table_name,
            'new_values' => ['filename' => $filename]
        ]);
    }
    
    /**
     * Log file DOWNLOAD action
     */
    public static function logDownload($module, $page, $filename, $record_id = null) {
        return self::log('DOWNLOAD', $module, "Downloaded file: {$filename}", [
            'page' => $page,
            'record_id' => $record_id,
            'new_values' => ['filename' => $filename]
        ]);
    }
    
    /**
     * Log EXPORT action
     */
    public static function logExport($module, $page, $description, $record_count = null) {
        $desc = $description;
        if ($record_count) {
            $desc .= " ({$record_count} records)";
        }
        return self::log('EXPORT', $module, $desc, [
            'page' => $page
        ]);
    }
    
    /**
     * Log IMPORT action
     */
    public static function logImport($module, $page, $description, $record_count = null, $status = 'SUCCESS') {
        $desc = $description;
        if ($record_count) {
            $desc .= " ({$record_count} records)";
        }
        return self::log('IMPORT', $module, $desc, [
            'page' => $page,
            'status' => $status
        ]);
    }
    
    /**
     * Log SUBMIT action
     */
    public static function logSubmit($module, $page, $record_id, $description, $table_name = null) {
        return self::log('SUBMIT', $module, $description, [
            'page' => $page,
            'record_id' => $record_id,
            'table_name' => $table_name
        ]);
    }
    
    /**
     * Log VIEW action
     */
    public static function logView($module, $page, $record_id, $description = null) {
        $desc = $description ?? "Viewed {$module} record";
        return self::log('VIEW', $module, $desc, [
            'page' => $page,
            'record_id' => $record_id
        ]);
    }
    
    /**
     * Get recent activity logs
     */
    public static function getRecentActivity($limit = 50, $module = null, $user_id = null, $action_type = null) {
        global $conDB;
        
        if (!isset($conDB) || !$conDB) {
            return [];
        }
        
        $sql = "SELECT * FROM activity_log WHERE 1=1";
        $params = [];
        $types = '';
        
        if ($module !== null) {
            $sql .= " AND module = ?";
            $params[] = $module;
            $types .= 's';
        }
        
        if ($user_id !== null) {
            $sql .= " AND user_id = ?";
            $params[] = $user_id;
            $types .= 's';
        }
        
        if ($action_type !== null) {
            $sql .= " AND action_type = ?";
            $params[] = $action_type;
            $types .= 's';
        }
        
        $sql .= " ORDER BY created_at DESC LIMIT ?";
        $params[] = $limit;
        $types .= 'i';
        
        $stmt = mysqli_prepare($conDB, $sql);
        if (!$stmt) {
            return [];
        }
        
        if (!empty($params)) {
            mysqli_stmt_bind_param($stmt, $types, ...$params);
        }
        
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $logs = mysqli_fetch_all($result, MYSQLI_ASSOC);
        mysqli_stmt_close($stmt);
        
        return $logs;
    }
    
    /**
     * Get activity for a specific user
     */
    public static function getUserActivity($user_id, $limit = 50) {
        return self::getRecentActivity($limit, null, $user_id);
    }
    
    /**
     * Get activity for a specific module
     */
    public static function getModuleActivity($module, $limit = 50) {
        return self::getRecentActivity($limit, $module);
    }
    
    /**
     * Clean old activity logs
     */
    public static function cleanOldLogs($days = 365) {
        global $conDB;
        
        if (!isset($conDB) || !$conDB) {
            return false;
        }
        
        $sql = "DELETE FROM activity_log WHERE created_at < DATE_SUB(NOW(), INTERVAL ? DAY)";
        $stmt = mysqli_prepare($conDB, $sql);
        
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, 'i', $days);
            $result = mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
            return $result;
        }
        
        return false;
    }
    
    /**
     * Get changed fields between two arrays
     */
    private static function getChangedFields($old, $new) {
        $changes = ['old' => [], 'new' => []];
        
        foreach ($new as $key => $value) {
            if (!isset($old[$key]) || $old[$key] != $value) {
                $changes['old'][$key] = $old[$key] ?? null;
                $changes['new'][$key] = $value;
            }
        }
        
        return $changes;
    }
    
    /**
     * Check if the same action was logged recently (deduplication)
     * Returns false if the exact same action with same data was logged within the time window
     * Returns true if data has changed (to allow immediate logging)
     */
    private static function shouldLogAction($action_type, $module, $record_id, $user_id, $old_values, $new_values, $minutes = 5) {
        // Initialize session cache for activity logger if not exists
        if (!isset($_SESSION['activity_log_cache'])) {
            $_SESSION['activity_log_cache'] = [];
        }
        
        // Create a unique cache key for this action
        $cache_key = self::getCacheKey($action_type, $module, $record_id, $user_id);
        
        // Check session cache first (fastest check)
        if (isset($_SESSION['activity_log_cache'][$cache_key])) {
            $cached_data = $_SESSION['activity_log_cache'][$cache_key];
            $last_logged_time = $cached_data['timestamp'];
            $last_logged_data = $cached_data['new_values'] ?? null;
            
            $time_diff = time() - $last_logged_time;
            
            // If data has changed, log immediately (regardless of time window)
            if ($new_values !== $last_logged_data) {
                return true;
            }
            
            // If less than the specified minutes have passed AND data is the same, skip logging
            if ($time_diff < ($minutes * 60)) {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Update session cache after logging an action
     */
    private static function updateLogCache($action_type, $module, $record_id, $user_id, $new_values = null) {
        // Initialize session cache for activity logger if not exists
        if (!isset($_SESSION['activity_log_cache'])) {
            $_SESSION['activity_log_cache'] = [];
        }
        
        // Create cache key and store current timestamp along with the data
        $cache_key = self::getCacheKey($action_type, $module, $record_id, $user_id);
        $_SESSION['activity_log_cache'][$cache_key] = [
            'timestamp' => time(),
            'new_values' => $new_values
        ];
        
        // Limit cache size to prevent memory issues (keep only last 100 entries)
        if (count($_SESSION['activity_log_cache']) > 100) {
            // Remove oldest entries by timestamp
            usort($_SESSION['activity_log_cache'], function($a, $b) {
                return $b['timestamp'] - $a['timestamp'];
            });
            $_SESSION['activity_log_cache'] = array_slice($_SESSION['activity_log_cache'], 0, 100, true);
        }
    }
    
    /**
     * Generate a cache key for deduplication
     */
    private static function getCacheKey($action_type, $module, $record_id, $user_id) {
        // Create a unique key based on the action details
        return md5($action_type . '|' . $module . '|' . ($record_id ?? '') . '|' . $user_id);
    }
    
    /**
     * Get user's IP address (handles proxies)
     */
    private static function getIpAddress() {
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            $ip = $_SERVER['REMOTE_ADDR'] ?? 'UNKNOWN';
        }
        
        // Return only the first IP if multiple are present (proxy chain)
        $ip_parts = explode(',', $ip);
        return trim($ip_parts[0]);
    }
}
} // End of if (!class_exists('ActivityLogger'))
?>
