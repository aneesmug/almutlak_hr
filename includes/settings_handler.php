<?php
// Set the content type to JSON for all responses
header('Content-Type: application/json');

// --- Database Connection ---
require_once(__DIR__ . "/db.php");
// --- Main Logic ---
// Check if the request method is POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    switch ($action) {
        case 'get_settings':
            get_all_settings($conDB);
            break;
        case 'update_settings':
            update_all_settings($conDB);
            break;
        default:
            // Handle invalid action
            echo json_encode(['success' => false, 'message' => 'Invalid action specified.']);
            break;
    }
} else {
    // Handle non-POST requests
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
}

// Close the database connection
$conDB->close();


// --- Function Definitions ---

/**
 * Fetches all settings from the database and echoes them as a JSON object.
 * Returns a full profile for each setting to dynamically build the form.
 *
 * @param mysqli $conDB The database connection object.
 */
function get_all_settings($conDB) {
    $settings = [];
    // Fetch all necessary columns to build the form dynamically on the frontend
    $sql = "SELECT setting_name, setting_value, description, input_type, options, setting_group FROM app_settings ORDER BY setting_group, id";
    $result = $conDB->query($sql);

    if ($result) {
        while ($row = $result->fetch_assoc()) {
            // Append each setting as an object to the array
            $settings[] = $row;
        }
        echo json_encode(['success' => true, 'settings' => $settings]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error fetching settings: ' . $conDB->error]);
    }
}


/**
 * Updates settings in the database based on POST data.
 *
 * @param mysqli $conDB The database connection object.
 */
function update_all_settings($conDB) {
    $newSettingsJson = $_POST['settings'] ?? '{}';
    $newSettings = json_decode($newSettingsJson, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        echo json_encode(['success' => false, 'message' => 'Invalid JSON data received.']);
        return;
    }

    $conDB->begin_transaction();
    $all_successful = true;

    // Prepare a single statement to be used repeatedly for efficiency
    $sql = "UPDATE app_settings SET setting_value = ? WHERE setting_name = ?";
    $stmt = $conDB->prepare($sql);

    if (!$stmt) {
         echo json_encode(['success' => false, 'message' => 'Failed to prepare statement: ' . $conDB->error]);
         return;
    }

    foreach ($newSettings as $name => $value) {
        $stmt->bind_param("ss", $value, $name);
        if (!$stmt->execute()) {
            $all_successful = false;
        }
    }

    $stmt->close();

    if ($all_successful) {
        $conDB->commit();
        echo json_encode(['success' => true, 'message' => 'Settings updated successfully.']);
    } else {
        $conDB->rollback();
        echo json_encode(['success' => false, 'message' => 'One or more settings could not be updated.']);
    }
}
?>
