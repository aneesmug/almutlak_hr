<?php
/**
 * User Activity Logger
 * Logs user login activity with location, device, and browser information
 */

/**
 * Get client IP address (handles proxy and forwarding)
 */
function getUserIP() {
    $ipaddress = '';
    if (isset($_SERVER['HTTP_CLIENT_IP']))
        $ipaddress = $_SERVER['HTTP_CLIENT_IP'];
    else if(isset($_SERVER['HTTP_X_FORWARDED_FOR']))
        $ipaddress = $_SERVER['HTTP_X_FORWARDED_FOR'];
    else if(isset($_SERVER['HTTP_X_FORWARDED']))
        $ipaddress = $_SERVER['HTTP_X_FORWARDED'];
    else if(isset($_SERVER['HTTP_FORWARDED_FOR']))
        $ipaddress = $_SERVER['HTTP_FORWARDED_FOR'];
    else if(isset($_SERVER['HTTP_FORWARDED']))
        $ipaddress = $_SERVER['HTTP_FORWARDED'];
    else if(isset($_SERVER['REMOTE_ADDR']))
        $ipaddress = $_SERVER['REMOTE_ADDR'];
    else
        $ipaddress = 'UNKNOWN';
    
    // Handle multiple IPs (take the first one)
    if (strpos($ipaddress, ',') !== false) {
        $ipaddress = explode(',', $ipaddress)[0];
    }
    
    return trim($ipaddress);
}

/**
 * Get location information from IP address using ip-api.com (free, no key required)
 */
function getLocationFromIP($ip) {
    // For local/private IPs, use default Saudi Arabia coordinates (Jeddah)
    if ($ip === 'UNKNOWN' || $ip === '127.0.0.1' || $ip === '::1' || 
        filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) {
        return [
            'country' => 'Saudi Arabia',
            'country_code' => 'SA',
            'region' => 'Makkah',
            'city' => 'Jeddah',
            'latitude' => 21.5433,
            'longitude' => 39.1728,
            'timezone' => 'Asia/Riyadh',
            'isp' => 'Local Network'
        ];
    }
    
    try {
        $url = "http://ip-api.com/json/{$ip}?fields=status,country,countryCode,region,city,lat,lon,timezone,isp";
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 3);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 2);
        $response = curl_exec($ch);
        curl_close($ch);
        
        if ($response) {
            $data = json_decode($response, true);
            if ($data && isset($data['status']) && $data['status'] === 'success') {
                return [
                    'country' => $data['country'] ?? '',
                    'country_code' => $data['countryCode'] ?? '',
                    'region' => $data['region'] ?? '',
                    'city' => $data['city'] ?? '',
                    'latitude' => $data['lat'] ?? null,
                    'longitude' => $data['lon'] ?? null,
                    'timezone' => $data['timezone'] ?? '',
                    'isp' => $data['isp'] ?? ''
                ];
            }
        }
    } catch (Exception $e) {
        // Silent fail - don't interrupt login process
    }
    
    return [
        'country' => '',
        'country_code' => '',
        'region' => '',
        'city' => '',
        'latitude' => null,
        'longitude' => null,
        'timezone' => '',
        'isp' => ''
    ];
}

/**
 * Parse user agent to extract browser and OS information
 */
function parseUserAgent($userAgent) {
    $browser = 'Unknown';
    $browser_version = '';
    $os = 'Unknown';
    $os_version = '';
    $device_type = 'Desktop';
    
    // Detect Browser
    if (preg_match('/MSIE ([0-9]+\.[0-9]+)/', $userAgent, $matches)) {
        $browser = 'Internet Explorer';
        $browser_version = $matches[1];
    } elseif (preg_match('/Edge\/([0-9]+\.[0-9]+)/', $userAgent, $matches)) {
        $browser = 'Microsoft Edge';
        $browser_version = $matches[1];
    } elseif (preg_match('/Edg\/([0-9]+\.[0-9]+)/', $userAgent, $matches)) {
        $browser = 'Microsoft Edge (Chromium)';
        $browser_version = $matches[1];
    } elseif (preg_match('/Firefox\/([0-9]+\.[0-9]+)/', $userAgent, $matches)) {
        $browser = 'Mozilla Firefox';
        $browser_version = $matches[1];
    } elseif (preg_match('/Chrome\/([0-9]+\.[0-9]+)/', $userAgent, $matches)) {
        $browser = 'Google Chrome';
        $browser_version = $matches[1];
    } elseif (preg_match('/Safari\/([0-9]+\.[0-9]+)/', $userAgent, $matches)) {
        $browser = 'Safari';
        $browser_version = $matches[1];
    } elseif (preg_match('/Opera\/([0-9]+\.[0-9]+)/', $userAgent, $matches)) {
        $browser = 'Opera';
        $browser_version = $matches[1];
    }
    
    // Detect Operating System
    if (preg_match('/Windows NT ([0-9\.]+)/', $userAgent, $matches)) {
        $os = 'Windows';
        $version_map = [
            '10.0' => '10/11',
            '6.3' => '8.1',
            '6.2' => '8',
            '6.1' => '7',
            '6.0' => 'Vista',
            '5.2' => 'XP',
            '5.1' => 'XP',
        ];
        $os_version = $version_map[$matches[1]] ?? $matches[1];
    } elseif (preg_match('/Mac OS X ([0-9_]+)/', $userAgent, $matches)) {
        $os = 'macOS';
        $os_version = str_replace('_', '.', $matches[1]);
    } elseif (preg_match('/Android ([0-9\.]+)/', $userAgent, $matches)) {
        $os = 'Android';
        $os_version = $matches[1];
        $device_type = 'Mobile';
    } elseif (preg_match('/iPhone OS ([0-9_]+)/', $userAgent, $matches)) {
        $os = 'iOS';
        $os_version = str_replace('_', '.', $matches[1]);
        $device_type = 'Mobile';
    } elseif (preg_match('/iPad/', $userAgent)) {
        $os = 'iOS';
        $device_type = 'Tablet';
    } elseif (preg_match('/Linux/', $userAgent)) {
        $os = 'Linux';
    }
    
    // Detect tablet
    if (preg_match('/tablet|ipad/i', $userAgent)) {
        $device_type = 'Tablet';
    }
    
    return [
        'browser' => $browser,
        'browser_version' => $browser_version,
        'os' => $os,
        'os_version' => $os_version,
        'device_type' => $device_type
    ];
}

/**
 * Log user login activity
 * Note: Initial latitude/longitude are from IP geolocation (city-level accuracy).
 * Browser-based GPS coordinates are captured client-side and updated via ajaxPreciseLocation.php
 * providing precise location (5-30 meter accuracy) once user grants permission.
 * If user already has an active session, update it instead of creating duplicate.
 */
function logUserActivity($conDB, $user_id, $emp_id, $username) {
    $ip = getUserIP();
    $location = getLocationFromIP($ip);
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    $deviceInfo = parseUserAgent($userAgent);
    $sessionId = session_id();
    
    // Check if user already has an active session
    $checkStmt = $conDB->prepare("SELECT `id` FROM `user_activity_log` WHERE `user_id` = ? AND `status` = 'active' LIMIT 1");
    if ($checkStmt) {
        $checkStmt->bind_param("i", $user_id);
        $checkStmt->execute();
        $result = $checkStmt->get_result();
        $existingSession = $result->fetch_assoc();
        $checkStmt->close();
        
        if ($existingSession) {
            // Update existing active session with new login information
            $updateStmt = $conDB->prepare("UPDATE `user_activity_log` SET
                `login_time` = NOW(),
                `last_activity` = NOW(),
                `logout_time` = NULL,
                `ip_address` = ?, 
                `country` = ?, 
                `country_code` = ?, 
                `region` = ?, 
                `city` = ?, 
                `latitude` = ?, 
                `longitude` = ?, 
                `timezone` = ?, 
                `isp` = ?,
                `browser` = ?, 
                `browser_version` = ?, 
                `os` = ?, 
                `os_version` = ?, 
                `device_type` = ?,
                `user_agent` = ?, 
                `session_id` = ?,
                `status` = 'active'
                WHERE `id` = ?");
            
            if ($updateStmt) {
                $updateStmt->bind_param(
                    "sssssddsssssssssi",
                    $ip,
                    $location['country'],
                    $location['country_code'],
                    $location['region'],
                    $location['city'],
                    $location['latitude'],
                    $location['longitude'],
                    $location['timezone'],
                    $location['isp'],
                    $deviceInfo['browser'],
                    $deviceInfo['browser_version'],
                    $deviceInfo['os'],
                    $deviceInfo['os_version'],
                    $deviceInfo['device_type'],
                    $userAgent,
                    $sessionId,
                    $existingSession['id']
                );
                
                $updateStmt->execute();
                $updateStmt->close();
                
                // Store activity ID in session for logout tracking
                $_SESSION['activity_log_id'] = $existingSession['id'];
                
                return $existingSession['id'];
            }
        }
    }
    
    // No active session found, create new record
    $stmt = $conDB->prepare("INSERT INTO `user_activity_log` (
        `user_id`, `emp_id`, `username`, `login_time`, `last_activity`, `ip_address`,
        `country`, `country_code`, `region`, `city`,
        `latitude`, `longitude`, `timezone`, `isp`,
        `browser`, `browser_version`, `os`, `os_version`, `device_type`,
        `user_agent`, `session_id`, `status`
    ) VALUES (?, ?, ?, NOW(), NOW(), ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'active')");
    
    if ($stmt) {
        $stmt->bind_param(
            "issssssssddssssssss",
            $user_id,
            $emp_id,
            $username,
            $ip,
            $location['country'],
            $location['country_code'],
            $location['region'],
            $location['city'],
            $location['latitude'],
            $location['longitude'],
            $location['timezone'],
            $location['isp'],
            $deviceInfo['browser'],
            $deviceInfo['browser_version'],
            $deviceInfo['os'],
            $deviceInfo['os_version'],
            $deviceInfo['device_type'],
            $userAgent,
            $sessionId
        );
        
        $stmt->execute();
        $activity_id = $stmt->insert_id;
        $stmt->close();
        
        // Store activity ID in session for logout tracking
        $_SESSION['activity_log_id'] = $activity_id;
        
        return $activity_id;
    }
    
    return false;
}

/**
 * Bump the last-activity timestamp for the current session's activity row.
 * Called on every authenticated request so a real per-user "last seen" exists
 * in the DB, not just in that user's own PHP session (which nobody else can
 * read) - needed for sweepStaleUserActivity() to reliably time out sessions
 * whose browser was simply closed and never sent another request.
 */
function touchUserActivity($conDB, $activity_id) {
    if (!$activity_id) {
        return;
    }
    $page = $_SERVER['REQUEST_URI'] ?? '';
    $stmt = $conDB->prepare("UPDATE `user_activity_log` SET `last_activity` = NOW(), `current_page` = ? WHERE `id` = ? AND `status` = 'active'");
    if ($stmt) {
        $stmt->bind_param("si", $page, $activity_id);
        $stmt->execute();
        $stmt->close();
    }
}

/**
 * Current status of one activity row - used to force-kill a session on its
 * NEXT request after an admin signs the user out from Connection Monitor.
 * A live PHP process can't be killed remotely; this is how every "admin force
 * logout" works in practice - the target session notices on its next request.
 */
function getUserActivityStatus($conDB, $activity_id) {
    if (!$activity_id) {
        return null;
    }
    $stmt = $conDB->prepare("SELECT `status` FROM `user_activity_log` WHERE `id` = ? LIMIT 1");
    if (!$stmt) {
        return null;
    }
    $stmt->bind_param("i", $activity_id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $row['status'] ?? null;
}

/**
 * Admin-initiated forced sign-out (Connection Monitor "Sign out" button).
 * Only flips the DB row - the target session dies on its own next request
 * via the getUserActivityStatus() check in session_check.php.
 */
function forceSignOutActivity($conDB, $activity_id) {
    $stmt = $conDB->prepare("UPDATE `user_activity_log` SET `status` = 'logged_out', `logout_time` = NOW() WHERE `id` = ? AND `status` = 'active'");
    if (!$stmt) {
        return false;
    }
    $stmt->bind_param("i", $activity_id);
    $ok = $stmt->execute();
    $affected = $stmt->affected_rows;
    $stmt->close();
    return $ok && $affected > 0;
}

/**
 * Auto sign-out sweep: mark any 'active' row idle longer than the configured
 * session timeout as timed out. Session-based logout is lazy (only fires when
 * THAT user's own browser sends another request), so a closed tab/phone
 * lock/lost connection left rows stuck "active" indefinitely. This runs the
 * same check server-side across ALL users, driven by the existing
 * app_settings "Set Session Timeout" value (in seconds).
 */
function sweepStaleUserActivity($conDB, $timeoutSeconds) {
    $timeoutSeconds = (int) $timeoutSeconds;
    if ($timeoutSeconds < 60) {
        $timeoutSeconds = 60; // safety floor - never sweep on a near-zero/invalid setting
    }
    mysqli_query($conDB, "UPDATE `user_activity_log`
        SET `status` = 'timeout', `logout_time` = NOW()
        WHERE `status` = 'active'
          AND COALESCE(`last_activity`, `login_time`) < (NOW() - INTERVAL $timeoutSeconds SECOND)");
}

/**
 * Update user logout time
 */
function logUserLogout($conDB, $status = 'logged_out') {
    if (isset($_SESSION['activity_log_id'])) {
        $activity_id = $_SESSION['activity_log_id'];
        $stmt = $conDB->prepare("UPDATE `user_activity_log` SET `logout_time` = NOW(), `status` = ? WHERE `id` = ?");
        if ($stmt) {
            $stmt->bind_param("si", $status, $activity_id);
            $stmt->execute();
            $stmt->close();
        }
    }
}

/**
 * Update screen resolution (called via AJAX from client-side)
 */
function updateScreenResolution($conDB, $activity_id, $width, $height) {
    $stmt = $conDB->prepare("UPDATE `user_activity_log` SET `screen_width` = ?, `screen_height` = ? WHERE `id` = ?");
    if ($stmt) {
        $stmt->bind_param("iii", $width, $height, $activity_id);
        $stmt->execute();
        $stmt->close();
        return true;
    }
    return false;
}
