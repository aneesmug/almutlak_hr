<?php
/**
 * Locations Handler
 * Manages the `locations` lookup table (each location belongs to one Saudi city).
 */

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/session_check.php';
require_once __DIR__ . '/special_access_helper.php';

header('Content-Type: application/json');

$canManageLocations = (isset($is_system_admin) && $is_system_admin)
    || user_has_special_access($conDB, $empid ?? '', 'manage_location_settings', $user_role ?? '', $user_type ?? '', $is_system_admin ?? false);

if (!$canManageLocations) {
    http_response_code(403);
    die(json_encode(['success' => false, 'message' => 'Access denied. Admin privileges required.']));
}

$action = $_POST['action'] ?? '';

switch ($action) {
    case 'get_cities':
        getCities();
        break;
    case 'get_locations':
        getLocations();
        break;
    case 'get_location':
        getLocation();
        break;
    case 'add_location':
        addLocation();
        break;
    case 'update_location':
        updateLocation();
        break;
    case 'delete_location':
        deleteLocation();
        break;
    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
}

function getCities() {
    global $conDB;
    try {
        $result = mysqli_query($conDB, "SELECT id, name_en, name_ar FROM saudi_cities ORDER BY name_en ASC");
        if (!$result) throw new Exception(mysqli_error($conDB));
        $cities = [];
        while ($row = mysqli_fetch_assoc($result)) $cities[] = $row;
        echo json_encode(['success' => true, 'cities' => $cities]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

function getLocations() {
    global $conDB;
    try {
        $sql = "SELECT l.id, l.city_id, l.name_en, l.name_ar, c.name_en AS city_name_en, c.name_ar AS city_name_ar
                FROM locations l
                LEFT JOIN saudi_cities c ON c.id = l.city_id
                ORDER BY c.name_en ASC, l.name_en ASC";
        $result = mysqli_query($conDB, $sql);
        if (!$result) throw new Exception(mysqli_error($conDB));
        $locations = [];
        while ($row = mysqli_fetch_assoc($result)) $locations[] = $row;
        echo json_encode(['success' => true, 'locations' => $locations]);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

function getLocation() {
    global $conDB;
    $locationId = intval($_POST['location_id'] ?? 0);

    if (!$locationId) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Location ID is required']);
        return;
    }

    try {
        $stmt = $conDB->prepare("SELECT id, city_id, name_en, name_ar FROM locations WHERE id = ?");
        if (!$stmt) throw new Exception($conDB->error);
        $stmt->bind_param('i', $locationId);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            echo json_encode(['success' => false, 'message' => 'Location not found']);
            return;
        }

        echo json_encode(['success' => true, 'location' => $result->fetch_assoc()]);
        $stmt->close();
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

function addLocation() {
    global $conDB;
    $cityId = intval($_POST['city_id'] ?? 0);
    $nameEn = trim($_POST['name_en'] ?? '');
    $nameAr = trim($_POST['name_ar'] ?? '');

    if (!$cityId || !$nameEn || !$nameAr) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'City, English name and Arabic name are required']);
        return;
    }

    try {
        $stmt = $conDB->prepare("INSERT INTO locations (city_id, name_en, name_ar) VALUES (?, ?, ?)");
        if (!$stmt) throw new Exception($conDB->error);
        $stmt->bind_param('iss', $cityId, $nameEn, $nameAr);
        if (!$stmt->execute()) throw new Exception($stmt->error);

        echo json_encode(['success' => true, 'message' => 'Location added successfully', 'id' => $stmt->insert_id]);
        $stmt->close();
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

function updateLocation() {
    global $conDB;
    $locationId = intval($_POST['location_id'] ?? 0);
    $cityId = intval($_POST['city_id'] ?? 0);
    $nameEn = trim($_POST['name_en'] ?? '');
    $nameAr = trim($_POST['name_ar'] ?? '');

    if (!$locationId || !$cityId || !$nameEn || !$nameAr) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Location ID, city, and both names are required']);
        return;
    }

    try {
        $stmt = $conDB->prepare("UPDATE locations SET city_id = ?, name_en = ?, name_ar = ? WHERE id = ?");
        if (!$stmt) throw new Exception($conDB->error);
        $stmt->bind_param('issi', $cityId, $nameEn, $nameAr, $locationId);
        if (!$stmt->execute()) throw new Exception($stmt->error);

        if ($stmt->affected_rows === 0) {
            echo json_encode(['success' => false, 'message' => 'Location not found']);
            return;
        }

        echo json_encode(['success' => true, 'message' => 'Location updated successfully']);
        $stmt->close();
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

function deleteLocation() {
    global $conDB;
    $locationId = intval($_POST['location_id'] ?? 0);

    if (!$locationId) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Location ID is required']);
        return;
    }

    try {
        $stmt = $conDB->prepare("DELETE FROM locations WHERE id = ?");
        if (!$stmt) throw new Exception($conDB->error);
        $stmt->bind_param('i', $locationId);
        if (!$stmt->execute()) throw new Exception($stmt->error);

        if ($stmt->affected_rows === 0) {
            echo json_encode(['success' => false, 'message' => 'Location not found']);
            return;
        }

        echo json_encode(['success' => true, 'message' => 'Location deleted successfully']);
        $stmt->close();
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}
