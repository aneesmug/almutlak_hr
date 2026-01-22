<?php
/**
 * AJAX Handler for updating user activity with precise geolocation
 */

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../session_check.php';

header('Content-Type: application/json');

$ajaxType = $_POST['ajaxType'] ?? $_GET['ajaxType'] ?? '';

switch ($ajaxType) {
    case 'update_precise_location':
        updatePreciseLocation($conDB);
        break;
    
    default:
        echo json_encode(['status' => 400, 'message' => 'Invalid request type']);
        break;
}

/**
 * Update activity log with precise GPS coordinates from browser
 */
function updatePreciseLocation($conDB) {
    $latitude = isset($_POST['latitude']) ? floatval($_POST['latitude']) : null;
    $longitude = isset($_POST['longitude']) ? floatval($_POST['longitude']) : null;
    $accuracy = isset($_POST['accuracy']) ? floatval($_POST['accuracy']) : null;
    
    if (!isset($_SESSION['activity_log_id'])) {
        echo json_encode(['status' => 400, 'message' => 'No active session']);
        return;
    }
    
    if ($latitude === null || $longitude === null) {
        echo json_encode(['status' => 400, 'message' => 'Invalid coordinates']);
        return;
    }
    
    $activity_id = $_SESSION['activity_log_id'];
    
    // Update with precise GPS coordinates
    $query = "UPDATE `user_activity_log` 
              SET `latitude` = ?, `longitude` = ?, `location_accuracy` = ?
              WHERE `id` = ?";
    
    $stmt = mysqli_prepare($conDB, $query);
    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "dddi", $latitude, $longitude, $accuracy, $activity_id);
        if (mysqli_stmt_execute($stmt)) {
            echo json_encode([
                'status' => 200, 
                'message' => 'Location updated successfully',
                'data' => [
                    'latitude' => $latitude,
                    'longitude' => $longitude,
                    'accuracy' => $accuracy
                ]
            ]);
        } else {
            echo json_encode(['status' => 500, 'message' => 'Failed to update location']);
        }
        mysqli_stmt_close($stmt);
    } else {
        echo json_encode(['status' => 500, 'message' => 'Database error']);
    }
}
