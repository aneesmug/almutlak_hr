<?php
header('Content-Type: application/json');

// --- Database Connection ---
// Ensure you have a db.php file or similar connection logic.
require_once(__DIR__ . "/db.php"); 
require_once(__DIR__ . "/helper_functions.php");

if ($conDB->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed: ' . $conDB->connect_error]);
    exit();
}


// --- Main Logic ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    switch ($action) {
        case 'get_settings':
            get_all_settings($conDB);
            break;
        case 'update_settings':
            update_all_settings($conDB);
            break;
        case 'get_full_access_candidates':
            get_full_access_candidates($conDB);
            break;
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action specified.']);
            break;
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
}

$conDB->close();


// --- Function Definitions ---

/**
 * Fetches all settings from the database.
 */
function get_all_settings($conDB) {
    ensure_full_access_override_setting($conDB);

    $settings = [];
    $sql = "SELECT setting_name, setting_value, description, input_type, options, setting_group FROM app_settings ORDER BY setting_group, id";
    $result = $conDB->query($sql);

    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $settings[] = $row;
        }
        echo json_encode(['success' => true, 'settings' => $settings]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Error fetching settings: ' . $conDB->error]);
    }
}

/**
 * Updates settings, handling both file uploads and text inputs.
 */
function update_all_settings($conDB) {
    ensure_full_access_override_setting($conDB);

    // IMPORTANT: Make sure this path is correct and writable by your web server.
    $upload_dir = __DIR__ . '/../assets/logo/'; // Assumes 'assets/logo/' is one level up from this script's directory.
    $relative_path = 'assets/logo/'; // The path that will be stored in the database.

    // Create directory if it doesn't exist
    if (!is_dir($upload_dir) && !mkdir($upload_dir, 0755, true)) {
        echo json_encode(['success' => false, 'message' => 'Failed to create upload directory.']);
        return;
    }

    $conDB->begin_transaction();

    try {
        $sql = "UPDATE app_settings SET setting_value = ? WHERE setting_name = ?";
        $stmt = $conDB->prepare($sql);
        if (!$stmt) {
            throw new Exception('Failed to prepare statement: ' . $conDB->error);
        }

        // --- Process File Uploads ---
        foreach ($_FILES as $setting_name => $file) {
            if ($file['error'] === UPLOAD_ERR_OK) {
                // Sanitize filename to prevent directory traversal attacks
                $file_name = basename($file['name']);
                $destination = $upload_dir . $file_name;
                $db_path = $relative_path . $file_name;

                if (move_uploaded_file($file['tmp_name'], $destination)) {
                    $stmt->bind_param("ss", $db_path, $setting_name);
                    if (!$stmt->execute()) {
                        throw new Exception('DB update failed for file: ' . $setting_name);
                    }
                } else {
                    throw new Exception('Failed to move uploaded file: ' . $setting_name);
                }
            } elseif ($file['error'] !== UPLOAD_ERR_NO_FILE) {
                throw new Exception('File upload error code ' . $file['error'] . ' for ' . $setting_name);
            }
        }

        // --- Process Text/Select Inputs ---
        $text_inputs = array_diff_key($_POST, ['action' => '']);
        foreach ($text_inputs as $setting_name => $value) {
            $stmt->bind_param("ss", $value, $setting_name);
            if (!$stmt->execute()) {
                throw new Exception('DB update failed for setting: ' . $setting_name);
            }
        }

        $stmt->close();
        $conDB->commit();
        echo json_encode(['success' => true, 'message' => 'Settings updated successfully.']);

    } catch (Exception $e) {
        $conDB->rollback();
        echo json_encode(['success' => false, 'message' => 'An error occurred: ' . $e->getMessage()]);
    }
}

/**
 * Ensure full access override setting exists in app_settings for frontend control.
 */
function ensure_full_access_override_setting($conDB) {
    $settingName = 'full_access_emp_ids';
    $checkSql = "SELECT id FROM app_settings WHERE setting_name = ? LIMIT 1";
    $checkStmt = $conDB->prepare($checkSql);
    if (!$checkStmt) {
        return;
    }

    $checkStmt->bind_param("s", $settingName);
    if (!$checkStmt->execute()) {
        $checkStmt->close();
        return;
    }

    $result = $checkStmt->get_result();
    $exists = ($result && $result->num_rows > 0);
    if ($result) {
        $result->free();
    }
    $checkStmt->close();

    if ($exists) {
        return;
    }

    $insertSql = "INSERT INTO app_settings (setting_name, setting_value, setting_group, description, input_type, options) VALUES (?, '', 'security', 'full_access_employee_ids_comma_separated_or_json_emp_id_values', 'text', NULL)";
    $insertStmt = $conDB->prepare($insertSql);
    if (!$insertStmt) {
        return;
    }

    $insertStmt->bind_param("s", $settingName);
    $insertStmt->execute();
    $insertStmt->close();
}

/**
 * Return employees list for full-access multi-selection UI.
 */
function get_full_access_candidates($conDB) {
    $employees = [];

    $sql = "SELECT emp_id, name, status FROM employees WHERE emp_id IS NOT NULL AND emp_id <> '' ORDER BY name ASC";
    $result = $conDB->query($sql);

    if (!$result) {
        echo json_encode(['success' => false, 'message' => 'Error fetching employees: ' . $conDB->error]);
        return;
    }

    while ($row = $result->fetch_assoc()) {
        $parsedName = parseName((string)($row['name'] ?? ''));
        $employees[] = [
            'emp_id' => (string)($row['emp_id'] ?? ''),
            'name' => $parsedName,
            'status' => (int)($row['status'] ?? 0),
        ];
    }

    echo json_encode(['success' => true, 'employees' => $employees]);
}
?>