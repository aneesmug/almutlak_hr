<?php
/**
 * AJAX Handler for User Activity Log
 * Handles data retrieval for activity tracking
 */

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../session_check.php';
require_once __DIR__ . '/../session_validator.php';

header('Content-Type: application/json');

// --- Validate Session Status on Every AJAX Request ---
// Ensures that if user was force-logged-out, they cannot make any AJAX calls
validateAndTerminateInvalidSession($conDB, $_SESSION, true);

$ajaxType = $_POST['ajaxType'] ?? $_GET['ajaxType'] ?? '';

// Auto-timeout stale active sessions (older than 24 hours)
autoTimeoutStaleSessions($conDB);

switch ($ajaxType) {
    case 'get_activity_log':
        getActivityLog($conDB);
        break;
    
    case 'get_statistics':
        getStatistics($conDB);
        break;
    
    case 'get_activity_details':
        getActivityDetails($conDB);
        break;
    
    case 'update_screen_resolution':
        updateScreenResolution($conDB);
        break;

    case 'get_location_markers':
        getLocationMarkers($conDB);
        break;
    
    case 'force_signout_user':
        forceSignoutUser($conDB);
        break;
    
    default:
        echo json_encode(['status' => 400, 'message' => 'Invalid request type']);
        break;
}

/**
 * Get activity log data for DataTables (server-side processing)
 */
function getActivityLog($conDB) {
    // DataTables parameters
    $draw = intval($_POST['draw'] ?? 1);
    $start = intval($_POST['start'] ?? 0);
    $length = intval($_POST['length'] ?? 25);
    $searchValue = $_POST['search']['value'] ?? '';
    $orderColumnIndex = $_POST['order'][0]['column'] ?? 0;
    $orderDir = $_POST['order'][0]['dir'] ?? 'DESC';
    
    // Column mapping
    $columns = ['id', 'username', 'login_time', 'logout_time', 'duration', 'ip_address', 'location', 'device_type', 'browser', 'os', 'screen', 'status'];
    $orderColumn = $columns[$orderColumnIndex] ?? 'id';
    
    // Base query
    $baseQuery = "FROM `user_activity_log` ual
                  LEFT JOIN `employees` e ON ual.emp_id = e.emp_id
                  WHERE 1=1";
    
    // Global search filter
    $searchQuery = "";
    if (!empty($searchValue)) {
        $searchValue = mysqli_real_escape_string($conDB, $searchValue);
        $searchQuery = " AND (
            ual.username LIKE '%{$searchValue}%' OR
            e.name LIKE '%{$searchValue}%' OR
            ual.ip_address LIKE '%{$searchValue}%' OR
            ual.country LIKE '%{$searchValue}%' OR
            ual.city LIKE '%{$searchValue}%' OR
            ual.browser LIKE '%{$searchValue}%' OR
            ual.os LIKE '%{$searchValue}%'
        )";
    }
    
    // Column-specific search filters
    $columnSearchQuery = "";
    if (isset($_POST['columns']) && is_array($_POST['columns'])) {
        foreach ($_POST['columns'] as $index => $column) {
            if (!empty($column['search']['value'])) {
                $colSearchValue = mysqli_real_escape_string($conDB, $column['search']['value']);
                
                switch ($index) {
                    case 1: // Username/Employee name
                        $columnSearchQuery .= " AND (ual.username LIKE '%{$colSearchValue}%' OR e.name LIKE '%{$colSearchValue}%')";
                        break;
                    case 6: // Location - search in the formatted location string
                        // Since location is formatted as "City, Region, Country", we need to match any part
                        $columnSearchQuery .= " AND CONCAT_WS(', ', 
                            NULLIF(ual.city, ''), 
                            NULLIF(ual.region, ''), 
                            NULLIF(ual.country, '')
                        ) LIKE '%{$colSearchValue}%'";
                        break;
                    case 7: // Device type
                        $columnSearchQuery .= " AND ual.device_type LIKE '%{$colSearchValue}%'";
                        break;
                    case 11: // Status
                        $columnSearchQuery .= " AND ual.status = '{$colSearchValue}'";
                        break;
                }
            }
        }
    }
    
    // Total records
    $totalQuery = "SELECT COUNT(*) as total " . $baseQuery;
    $totalResult = mysqli_query($conDB, $totalQuery);
    $totalRecords = mysqli_fetch_assoc($totalResult)['total'];
    
    // Filtered records (apply both global and column searches)
    $filteredQuery = "SELECT COUNT(*) as total " . $baseQuery . $searchQuery . $columnSearchQuery;
    $filteredResult = mysqli_query($conDB, $filteredQuery);
    $filteredRecords = mysqli_fetch_assoc($filteredResult)['total'];
    
    // Get data (apply both global and column searches)
    $dataQuery = "SELECT 
                    ual.id,
                    ual.username,
                    e.name as emp_name,
                    ual.login_time,
                    ual.logout_time,
                    ual.ip_address,
                    ual.country,
                    ual.city,
                    ual.region,
                    ual.device_type,
                    ual.browser,
                    ual.browser_version,
                    ual.os,
                    ual.os_version,
                    ual.screen_width,
                    ual.screen_height,
                    ual.status
                  " . $baseQuery . $searchQuery . $columnSearchQuery . "
                  ORDER BY {$orderColumn} {$orderDir}
                  LIMIT {$start}, {$length}";
    
    $dataResult = mysqli_query($conDB, $dataQuery);
    
    $data = [];
    while ($row = mysqli_fetch_assoc($dataResult)) {
        // Calculate duration
        $duration = '';
        if ($row['logout_time']) {
            $login = strtotime($row['login_time']);
            $logout = strtotime($row['logout_time']);
            $diff = $logout - $login;
            $hours = floor($diff / 3600);
            $minutes = floor(($diff % 3600) / 60);
            $duration = sprintf("%02d:%02d hrs", $hours, $minutes);
        }
        
        // Format location
        $location = '';
        if ($row['city']) $location .= $row['city'];
        if ($row['region']) $location .= ($location ? ', ' : '') . $row['region'];
        if ($row['country']) $location .= ($location ? ', ' : '') . $row['country'];
        if (empty($location)) $location = 'Unknown';
        
        // Format device
        $device = $row['device_type'] ?? 'Unknown';
        
        // Format browser
        $browser = ($row['browser'] ?? 'Unknown') . ' ' . ($row['browser_version'] ?? '');
        
        // Format OS
        $os = ($row['os'] ?? 'Unknown') . ' ' . ($row['os_version'] ?? '');
        
        // Format screen
        $screen = '';
        if ($row['screen_width'] && $row['screen_height']) {
            $screen = $row['screen_width'] . 'x' . $row['screen_height'];
        } else {
            $screen = 'N/A';
        }
        
        $data[] = [
            $row['id'],
            $row['emp_name'] ?? $row['username'],
            date('d M Y, h:i A', strtotime($row['login_time'])),
            $row['logout_time'] ? date('d M Y, h:i A', strtotime($row['logout_time'])) : null,
            $duration,
            $row['ip_address'],
            $location,
            $device,
            $browser,
            $os,
            $screen,
            $row['status']
        ];
    }
    
    echo json_encode([
        'draw' => $draw,
        'recordsTotal' => $totalRecords,
        'recordsFiltered' => $filteredRecords,
        'data' => $data
    ]);
}

/**
 * Get statistics for dashboard cards
 */
function getStatistics($conDB) {
    // Active sessions
    $activeQuery = "SELECT COUNT(*) as count FROM `user_activity_log` WHERE `status` = 'active'";
    $activeResult = mysqli_query($conDB, $activeQuery);
    $activeSessions = mysqli_fetch_assoc($activeResult)['count'];
    
    // Today's logins
    $todayQuery = "SELECT COUNT(*) as count FROM `user_activity_log` 
                   WHERE DATE(login_time) = CURDATE()";
    $todayResult = mysqli_query($conDB, $todayQuery);
    $todayLogins = mysqli_fetch_assoc($todayResult)['count'];
    
    // Unique locations
    $locationQuery = "SELECT COUNT(DISTINCT CONCAT(country, city)) as count 
                      FROM `user_activity_log` 
                      WHERE country IS NOT NULL AND country != ''";
    $locationResult = mysqli_query($conDB, $locationQuery);
    $uniqueLocations = mysqli_fetch_assoc($locationResult)['count'];
    
    // Device types
    $deviceQuery = "SELECT COUNT(DISTINCT device_type) as count FROM `user_activity_log`
                    WHERE device_type IS NOT NULL AND device_type != ''";
    $deviceResult = mysqli_query($conDB, $deviceQuery);
    $deviceTypes = mysqli_fetch_assoc($deviceResult)['count'];
    
    echo json_encode([
        'status' => 200,
        'data' => [
            'active_sessions' => $activeSessions,
            'today_logins' => $todayLogins,
            'unique_locations' => $uniqueLocations,
            'device_types' => $deviceTypes
        ]
    ]);
}

/**
 * Get detailed information for a specific activity
 */
function getActivityDetails($conDB) {
    $activityId = intval($_POST['activity_id'] ?? 0);
    
    if ($activityId <= 0) {
        echo json_encode(['status' => 400, 'message' => 'Invalid activity ID']);
        return;
    }
    
    $query = "SELECT 
                ual.*,
                e.name as emp_name
              FROM `user_activity_log` ual
              LEFT JOIN `employees` e ON ual.emp_id = e.emp_id
              WHERE ual.id = ?";
    
    $stmt = mysqli_prepare($conDB, $query);
    mysqli_stmt_bind_param($stmt, "i", $activityId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);
    
    if (!$row) {
        echo json_encode(['status' => 404, 'message' => 'Activity not found']);
        return;
    }
    
    // Calculate duration
    $duration = 'Still Active';
    if ($row['logout_time']) {
        $login = strtotime($row['login_time']);
        $logout = strtotime($row['logout_time']);
        $diff = $logout - $login;
        $hours = floor($diff / 3600);
        $minutes = floor(($diff % 3600) / 60);
        $duration = sprintf("%d hours, %d minutes", $hours, $minutes);
    }
    
    echo json_encode([
        'status' => 200,
        'data' => [
            'username' => $row['username'],
            'emp_name' => $row['emp_name'],
            'login_time' => date('d M Y, h:i:s A', strtotime($row['login_time'])),
            'logout_time' => $row['logout_time'] ? date('d M Y, h:i:s A', strtotime($row['logout_time'])) : null,
            'duration' => $duration,
            'ip_address' => $row['ip_address'],
            'country' => $row['country'] ?: 'Unknown',
            'region' => $row['region'] ?: 'Unknown',
            'city' => $row['city'] ?: 'Unknown',
            'isp' => $row['isp'] ?: 'Unknown',
            'browser' => $row['browser'] ?: 'Unknown',
            'browser_version' => $row['browser_version'] ?: '',
            'os' => $row['os'] ?: 'Unknown',
            'os_version' => $row['os_version'] ?: '',
            'device_type' => $row['device_type'] ?: 'Unknown',
            'screen_width' => $row['screen_width'] ?: 'N/A',
            'screen_height' => $row['screen_height'] ?: 'N/A',
            'user_agent' => $row['user_agent'] ?: 'Unknown'
        ]
    ]);
}

/**
 * Get location markers for map
 */
function getLocationMarkers($conDB) {
    // First check if we have any records at all
    $countQuery = "SELECT COUNT(*) as total FROM `user_activity_log`";
    $countResult = mysqli_query($conDB, $countQuery);
    if (!$countResult) {
        echo json_encode([
            'status' => 500,
            'message' => 'Failed to count activity records',
            'error' => mysqli_error($conDB)
        ]);
        return;
    }
    $totalRecords = mysqli_fetch_assoc($countResult)['total'];
    
    // Check how many have coordinates
    $coordQuery = "SELECT COUNT(*) as total FROM `user_activity_log` 
                   WHERE latitude IS NOT NULL AND longitude IS NOT NULL 
                   AND latitude != 0 AND longitude != 0";
    $coordResult = mysqli_query($conDB, $coordQuery);
    if (!$coordResult) {
        echo json_encode([
            'status' => 500,
            'message' => 'Failed to count coordinate records',
            'error' => mysqli_error($conDB)
        ]);
        return;
    }
    $coordRecords = mysqli_fetch_assoc($coordResult)['total'];
    
    $query = "SELECT 
                ual.id,
                ual.username,
                ual.emp_id,
                e.name AS emp_name,
                ual.latitude,
                ual.longitude,
                ual.login_time,
                ual.city,
                ual.country,
                ual.status
              FROM `user_activity_log` ual
              LEFT JOIN `employees` e ON ual.emp_id = e.emp_id
              WHERE ual.latitude IS NOT NULL AND ual.longitude IS NOT NULL
              AND ual.latitude != 0 AND ual.longitude != 0
              AND ual.status = 'active'
              ORDER BY ual.login_time DESC
              LIMIT 500";

    $result = mysqli_query($conDB, $query);
    if (!$result) {
        echo json_encode([
            'status' => 500,
            'message' => 'Failed to load map markers',
            'error' => mysqli_error($conDB)
        ]);
        return;
    }

    $markers = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $markers[] = [
            'id' => (int)$row['id'],
            'username' => $row['emp_name'] ?: $row['username'],
            'lat' => (float)$row['latitude'],
            'lng' => (float)$row['longitude'],
            'login_time' => $row['login_time'],
            'city' => $row['city'] ?: 'Unknown',
            'country' => $row['country'] ?: 'Unknown',
            'status' => $row['status']
        ];
    }

    echo json_encode([
        'status' => 200,
        'data' => $markers,
        'debug' => [
            'total_records' => $totalRecords,
            'records_with_coords' => $coordRecords
        ]
    ]);
}

/**
 * Update screen resolution (called from client-side JavaScript)
 */
function updateScreenResolution($conDB) {
    require_once __DIR__ . '/../user_activity_logger.php';
    
    $activityId = intval($_POST['activity_id'] ?? 0);
    $width = intval($_POST['width'] ?? 0);
    $height = intval($_POST['height'] ?? 0);
    
    if ($activityId <= 0 || $width <= 0 || $height <= 0) {
        echo json_encode(['status' => 400, 'message' => 'Invalid parameters']);
        return;
    }
    
    $result = updateScreenResolution($conDB, $activityId, $width, $height);
    
    echo json_encode([
        'status' => $result ? 200 : 500,
        'message' => $result ? 'Screen resolution updated' : 'Failed to update'
    ]);
}

/**
 * Auto-timeout stale sessions that are still marked as active
 * Sessions older than 10 hours (7am to 6pm) without logout are marked as timeout
 */
function autoTimeoutStaleSessions($conDB) {
    $query = "UPDATE `user_activity_log` 
              SET `status` = 'timeout', `logout_time` = DATE_ADD(`login_time`, INTERVAL 10 HOUR)
              WHERE `status` = 'active' 
              AND `login_time` < DATE_SUB(NOW(), INTERVAL 10 HOUR)
              AND `logout_time` IS NULL";
    mysqli_query($conDB, $query);
}

/**
 * Force sign out a specific user by activity ID
 * This will invalidate their session and remember tokens so they cannot use the system
 */
function forceSignoutUser($conDB) {
    $activity_id = intval($_POST['activity_id'] ?? 0);
    
    if (!$activity_id) {
        echo json_encode(['status' => 400, 'message' => 'Invalid activity ID']);
        return;
    }
    
    // Get activity details including user_id
    $checkQuery = "SELECT `id`, `user_id`, `username` FROM `user_activity_log` WHERE `id` = ?";
    $stmt = mysqli_prepare($conDB, $checkQuery);
    mysqli_stmt_bind_param($stmt, "i", $activity_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $activity = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
    
    if (!$activity) {
        echo json_encode(['status' => 404, 'message' => 'Activity record not found']);
        return;
    }
    
    $user_id = $activity['user_id'];
    $username = $activity['username'];
    
    // Begin transaction to ensure all updates succeed
    mysqli_begin_transaction($conDB);
    
    try {
        // 1. Update the activity log with logout time and status
        $updateActivityQuery = "UPDATE `user_activity_log` 
                        SET `status` = 'logged_out', `logout_time` = NOW()
                        WHERE `id` = ?";
        $stmt1 = mysqli_prepare($conDB, $updateActivityQuery);
        mysqli_stmt_bind_param($stmt1, "i", $activity_id);
        if (!mysqli_stmt_execute($stmt1)) {
            throw new Exception('Failed to update activity log');
        }
        mysqli_stmt_close($stmt1);
        
        // 2. Clear all active sessions for this user
        $clearSessionQuery = "UPDATE `user_activity_log` 
                              SET `status` = 'logged_out', `logout_time` = NOW()
                              WHERE `user_id` = ? AND `status` = 'active'";
        $stmt2 = mysqli_prepare($conDB, $clearSessionQuery);
        mysqli_stmt_bind_param($stmt2, "i", $user_id);
        if (!mysqli_stmt_execute($stmt2)) {
            throw new Exception('Failed to clear user sessions');
        }
        mysqli_stmt_close($stmt2);
        
        // 3. Clear remember tokens to prevent auto-login
        $clearTokenQuery = "UPDATE `admin_login` 
                           SET `remember_token` = NULL, `remember_token_expiry` = NULL
                           WHERE `id_iqama` = ?";
        $stmt3 = mysqli_prepare($conDB, $clearTokenQuery);
        mysqli_stmt_bind_param($stmt3, "s", $username);
        if (!mysqli_stmt_execute($stmt3)) {
            throw new Exception('Failed to clear remember tokens');
        }
        mysqli_stmt_close($stmt3);
        
        mysqli_commit($conDB);
        
        echo json_encode([
            'status' => 200, 
            'message' => 'User ' . htmlspecialchars($username) . ' has been signed out successfully. All active sessions have been invalidated.'
        ]);
    } catch (Exception $e) {
        mysqli_rollback($conDB);
        echo json_encode([
            'status' => 500, 
            'message' => 'Failed to sign out user: ' . $e->getMessage()
        ]);
    }
}
