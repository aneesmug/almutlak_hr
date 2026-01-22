<?php
/**
 * One-time script to add coordinates to existing login records that don't have them
 * This is useful for testing the map or for records created before geolocation was added
 */

require_once __DIR__ . '/includes/db.php';

// Check if running from command line or has proper authentication
session_start();
if (php_sapi_name() !== 'cli') {
    require_once __DIR__ . '/includes/session_check.php';
    if (!$is_system_admin) {
        die('Access denied. Only system administrators can run this script.');
    }
}

// Default coordinates for Saudi Arabia (Jeddah) for local/unknown IPs
$defaultLat = 21.5433;
$defaultLng = 39.1728;
$defaultCountry = 'Saudi Arabia';
$defaultCity = 'Jeddah';

// Get all records without coordinates
$query = "SELECT id, ip_address FROM `user_activity_log` 
          WHERE (latitude IS NULL OR longitude IS NULL OR latitude = 0 OR longitude = 0)
          LIMIT 1000";

$result = mysqli_query($conDB, $query);
$updated = 0;
$failed = 0;

echo "Starting coordinate update...\n";
echo "Found " . mysqli_num_rows($result) . " records without coordinates.\n\n";

while ($row = mysqli_fetch_assoc($result)) {
    $id = $row['id'];
    $ip = $row['ip_address'];
    
    $lat = null;
    $lng = null;
    $country = null;
    $city = null;
    
    // Try to get real geolocation if it's a public IP
    if (!empty($ip) && $ip !== 'UNKNOWN' && $ip !== '127.0.0.1' && $ip !== '::1') {
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false) {
            // It's a public IP, try to geolocate
            $geoData = getLocationFromIP($ip);
            if (!empty($geoData['latitude']) && !empty($geoData['longitude'])) {
                $lat = $geoData['latitude'];
                $lng = $geoData['longitude'];
                $country = $geoData['country'];
                $city = $geoData['city'];
            }
        }
    }
    
    // If geolocation failed or it's a local IP, use default coordinates
    if ($lat === null || $lng === null) {
        $lat = $defaultLat;
        $lng = $defaultLng;
        $country = $defaultCountry;
        $city = $defaultCity;
    }
    
    // Update the record
    $updateQuery = "UPDATE `user_activity_log` 
                    SET latitude = ?, longitude = ?, country = COALESCE(NULLIF(country, ''), ?), city = COALESCE(NULLIF(city, ''), ?)
                    WHERE id = ?";
    
    $stmt = mysqli_prepare($conDB, $updateQuery);
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "ddssi", $lat, $lng, $country, $city, $id);
        if (mysqli_stmt_execute($stmt)) {
            $updated++;
            echo "Updated record ID {$id}: {$city}, {$country} ({$lat}, {$lng})\n";
        } else {
            $failed++;
            echo "Failed to update record ID {$id}\n";
        }
        mysqli_stmt_close($stmt);
    }
    
    // Small delay to avoid overwhelming the API
    if ($updated % 50 === 0) {
        sleep(1);
    }
}

echo "\n=== Update Complete ===\n";
echo "Successfully updated: {$updated} records\n";
echo "Failed: {$failed} records\n";

/**
 * Get location from IP using ip-api.com
 */
function getLocationFromIP($ip) {
    try {
        $url = "http://ip-api.com/json/{$ip}?fields=status,country,countryCode,region,city,lat,lon,timezone,isp";
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);
        $response = curl_exec($ch);
        curl_close($ch);
        
        if ($response) {
            $data = json_decode($response, true);
            if ($data && isset($data['status']) && $data['status'] === 'success') {
                return [
                    'country' => $data['country'] ?? '',
                    'city' => $data['city'] ?? '',
                    'latitude' => $data['lat'] ?? null,
                    'longitude' => $data['lon'] ?? null,
                ];
            }
        }
    } catch (Exception $e) {
        // Silent fail
    }
    
    return [
        'country' => null,
        'city' => null,
        'latitude' => null,
        'longitude' => null,
    ];
}
