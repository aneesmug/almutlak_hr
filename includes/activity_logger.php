<?php
/**
 * Activity Logger Helper Class
 * 
 * Provides comprehensive activity logging functionality for tracking
 * all user actions across the entire Al-Mutlak WMS system.
 * 
 * Database Table: activity_log
 * Columns: id, user_editor, action_type, page, pg_id, old_value, new_value, 
 *          description, ip_address, user_agent, created_at
 * 
 * USAGE EXAMPLES:
 * 
 * // CREATE - New record
 * ActivityLogger::logCreate('add_customer.php', $customer_id, "Created new customer: ABC Company");
 * 
 * // UPDATE - Modified record
 * ActivityLogger::logUpdate('edit_employee.php', $emp_id, "Updated salary", "5000 SAR", "6000 SAR");
 * 
 * // DELETE - Removed record
 * ActivityLogger::logDelete('manage_guide_screenshots.php', $screenshot_id, "Deleted screenshot: Login Step");
 * 
 * // LOGIN - User authentication
 * ActivityLogger::logLogin('5127', "Login successful from dashboard");
 * 
 * // APPROVE/REJECT - Approval workflows
 * ActivityLogger::logApprove('vacation_approval.php', $vacation_id, "Approved 5 days vacation for Ahmed");
 * ActivityLogger::logReject('loan_approval.php', $loan_id, "Rejected loan: Invalid documentation");
 * 
 * // EXPORT/IMPORT
 * ActivityLogger::logExport('payroll_report.php', "Exported payroll for January 2025");
 * ActivityLogger::logImport('upload_attendance.php', "Imported 150 attendance records");
 */

// Guard against double declaration - ActivityLogger is now defined in init.php
if (!class_exists('ActivityLogger')) {

class ActivityLogger {
    
    /**
     * Main logging function
     * 
     * @param string|null $user_editor User ID (null = auto-detect from session)
     * @param string $action_type CREATE, UPDATE, DELETE, LOGIN, LOGOUT, VIEW, EXPORT, IMPORT, APPROVE, REJECT
     * @param string $page Page/file name where action occurred
     * @param string|null $pg_id ID of the affected record
     * @param string|null $description Human-readable description
     * @param string|null $old_value Previous value (for updates/deletes)
     * @param string|null $new_value New value (for creates/updates)
     * @param string|null $ip_address IP address (null = auto-detect)
     * @param string|null $user_agent User agent (null = auto-detect)
     * @return bool Success status
     */
    public static function log(
        $user_editor = null,
        $action_type = 'VIEW',
        $page = '',
        $pg_id = null,
        $description = null,
        $old_value = null,
        $new_value = null,
        $ip_address = null,
        $user_agent = null
    ) {
        global $conDB;
        
        // Don't log if database connection not available
        if (!isset($conDB) || !$conDB) {
            return false;
        }
        
        // Auto-detect user from session if not provided
        if ($user_editor === null) {
            if (isset($_SESSION['user_id'])) {
                $user_editor = $_SESSION['user_id'];
            } elseif (isset($_SESSION['empid'])) {
                $user_editor = $_SESSION['empid'];
            } elseif (isset($_SESSION['auth_user']['user_id'])) {
                $user_editor = $_SESSION['auth_user']['user_id'];
            } else {
                $user_editor = 'SYSTEM';
            }
        }
        
        // Auto-detect page if not provided
        if (empty($page)) {
            $page = basename($_SERVER['PHP_SELF'] ?? 'unknown.php');
        }
        
        // Auto-detect IP address
        if ($ip_address === null) {
            $ip_address = self::getIpAddress();
        }
        
        // Auto-detect user agent
        if ($user_agent === null) {
            $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
        }
        
        // Prepare SQL using mysqli
        $sql = "INSERT INTO activity_log (
                    user_editor, action_type, page, pg_id, 
                    old_value, new_value, description, 
                    ip_address, user_agent, created_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
        
        if ($stmt = mysqli_prepare($conDB, $sql)) {
            mysqli_stmt_bind_param($stmt, 'sssssssss',
                $user_editor, $action_type, $page, $pg_id,
                $old_value, $new_value, $description,
                $ip_address, $user_agent
            );
            
            $result = mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
            
            return $result;
        }
        
        return false;
    }
    
    /**
     * Log CREATE action - new record added
     * 
     * @param string $page Page name (e.g., 'add_customer.php')
     * @param string $pg_id ID of the new record
     * @param string $description Description of what was created
     * @param string|null $new_value New value/data (optional)
     * @param string|null $user_editor User ID (null = auto-detect)
     * @return bool
     */
    public static function logCreate($page, $pg_id, $description, $new_value = null, $user_editor = null) {
        return self::log($user_editor, 'CREATE', $page, $pg_id, $description, null, $new_value);
    }
    
    /**
     * Log UPDATE action - record modified
     * 
     * @param string $page Page name (e.g., 'edit_employee.php')
     * @param string $pg_id ID of the updated record
     * @param string $description Description of what was updated
     * @param string|null $old_value Previous value
     * @param string|null $new_value New value
     * @param string|null $user_editor User ID (null = auto-detect)
     * @return bool
     */
    public static function logUpdate($page, $pg_id, $description, $old_value = null, $new_value = null, $user_editor = null) {
        return self::log($user_editor, 'UPDATE', $page, $pg_id, $description, $old_value, $new_value);
    }
    
    /**
     * Log DELETE action - record removed
     * 
     * @param string $page Page name (e.g., 'manage_screenshots.php')
     * @param string $pg_id ID of the deleted record
     * @param string $description Description of what was deleted
     * @param string|null $old_value Value that was deleted (optional)
     * @param string|null $user_editor User ID (null = auto-detect)
     * @return bool
     */
    public static function logDelete($page, $pg_id, $description, $old_value = null, $user_editor = null) {
        return self::log($user_editor, 'DELETE', $page, $pg_id, $description, $old_value, null);
    }
    
    /**
     * Log LOGIN action - user authentication
     * 
     * @param string $user_editor User ID who logged in
     * @param string|null $description Custom description (optional)
     * @return bool
     */
    public static function logLogin($user_editor, $description = null) {
        $desc = $description ?? "User logged in successfully";
        return self::log($user_editor, 'LOGIN', 'login.php', null, $desc);
    }
    
    /**
     * Log LOGOUT action - user logged out
     * 
     * @param string|null $user_editor User ID (null = auto-detect from session)
     * @param string|null $description Custom description (optional)
     * @return bool
     */
    public static function logLogout($user_editor = null, $description = null) {
        $desc = $description ?? "User logged out";
        return self::log($user_editor, 'LOGOUT', 'logout.php', null, $desc);
    }
    
    /**
     * Log APPROVE action - approval workflow
     * 
     * @param string $page Page name
     * @param string $pg_id ID of approved record
     * @param string $description Description
     * @param string|null $user_editor User ID (null = auto-detect)
     * @return bool
     */
    public static function logApprove($page, $pg_id, $description, $user_editor = null) {
        return self::log($user_editor, 'APPROVE', $page, $pg_id, $description, 'Pending', 'Approved');
    }
    
    /**
     * Log REJECT action - rejection workflow
     * 
     * @param string $page Page name
     * @param string $pg_id ID of rejected record
     * @param string $description Description
     * @param string|null $user_editor User ID (null = auto-detect)
     * @return bool
     */
    public static function logReject($page, $pg_id, $description, $user_editor = null) {
        return self::log($user_editor, 'REJECT', $page, $pg_id, $description, 'Pending', 'Rejected');
    }
    
    /**
     * Log VIEW action - record accessed
     * 
     * @param string $page Page name
     * @param string $pg_id ID of viewed record
     * @param string $description Description
     * @param string|null $user_editor User ID (null = auto-detect)
     * @return bool
     */
    public static function logView($page, $pg_id, $description, $user_editor = null) {
        return self::log($user_editor, 'VIEW', $page, $pg_id, $description);
    }
    
    /**
     * Log EXPORT action - data exported
     * 
     * @param string $page Page name
     * @param string $description Description of export
     * @param string|null $user_editor User ID (null = auto-detect)
     * @return bool
     */
    public static function logExport($page, $description, $user_editor = null) {
        return self::log($user_editor, 'EXPORT', $page, null, $description);
    }
    
    /**
     * Log IMPORT action - data imported
     * 
     * @param string $page Page name
     * @param string $description Description of import
     * @param string|null $user_editor User ID (null = auto-detect)
     * @return bool
     */
    public static function logImport($page, $description, $user_editor = null) {
        return self::log($user_editor, 'IMPORT', $page, null, $description);
    }
    
    /**
     * Get recent activity logs
     * 
     * @param int $limit Number of records to retrieve
     * @param string|null $page Filter by page
     * @param string|null $user_editor Filter by user
     * @param string|null $action_type Filter by action type
     * @return array Array of activity log records
     */
    public static function getRecentActivity($limit = 50, $page = null, $user_editor = null, $action_type = null) {
        global $conDB;
        
        if (!isset($conDB) || !$conDB) {
            return [];
        }
        
        $sql = "SELECT * FROM activity_log WHERE 1=1";
        $params = [];
        $types = '';
        
        if ($page !== null) {
            $sql .= " AND page = ?";
            $params[] = $page;
            $types .= 's';
        }
        
        if ($user_editor !== null) {
            $sql .= " AND user_editor = ?";
            $params[] = $user_editor;
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
     * 
     * @param string $user_editor User ID
     * @param int $limit Number of records
     * @return array
     */
    public static function getUserActivity($user_editor, $limit = 50) {
        return self::getRecentActivity($limit, null, $user_editor);
    }
    
    /**
     * Get activity for a specific page
     * 
     * @param string $page Page name
     * @param int $limit Number of records
     * @return array
     */
    public static function getPageActivity($page, $limit = 50) {
        return self::getRecentActivity($limit, $page);
    }
    
    /**
     * Clean old activity logs
     * 
     * @param int $days Delete logs older than this many days
     * @return bool
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
     * Get user's IP address (handles proxies)
     * 
     * @return string
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
