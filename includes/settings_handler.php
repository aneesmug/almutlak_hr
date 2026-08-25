<?php
header('Content-Type: application/json');

// --- Database Connection ---
// Ensure you have a db.php file or similar connection logic.
require_once(__DIR__ . "/db.php");
require_once(__DIR__ . "/session_check.php");
require_once(__DIR__ . "/helper_functions.php");
require_once(__DIR__ . "/special_access_helper.php");
require_once(__DIR__ . "/page_access_helper.php");
require_once(__DIR__ . "/../vendor/autoload.php");

if ($conDB->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed: ' . $conDB->connect_error]);
    exit();
}

// This endpoint reads/writes every row in app_settings - including who has Special
// Access to what (privilege escalation risk) and page role access - so it must never
// be reachable without a valid session. It previously had NO auth check at all (unlike
// the sibling includes/payroll_settings_handler.php, which is gated per-group), so
// any unauthenticated request could read or overwrite every app setting. Mirror
// app_settings.php's own tab-visibility rule: system admin, or anyone granted at
// least one of the three delegable settings-tab special access keys.
$settingsHandlerCanManage = (
    ($is_system_admin ?? false)
    || user_has_special_access($conDB, $empid ?? '', 'manage_department_settings', $user_role ?? '', $user_type ?? '', $is_system_admin ?? false)
    || user_has_special_access($conDB, $empid ?? '', 'manage_job_title_settings', $user_role ?? '', $user_type ?? '', $is_system_admin ?? false)
    || user_has_special_access($conDB, $empid ?? '', 'manage_global_request_blocks', $user_role ?? '', $user_type ?? '', $is_system_admin ?? false)
);
if (!$settingsHandlerCanManage) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Access denied.']);
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
        case 'get_report_permission_users':
            get_report_permission_users($conDB);
            break;
        case 'get_special_access_users':
            // Assigning Special Access grants is a system-admin-only capability - there
            // is deliberately no delegable key for it (delegating it would let a
            // delegate grant themselves anything). The coarser $settingsHandlerCanManage
            // check above is not enough here.
            if (!($is_system_admin ?? false)) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Access denied.']);
                break;
            }
            get_special_access_users($conDB);
            break;
        case 'update_page_role_access':
            update_page_role_access($conDB);
            break;
        case 'test_email_settings':
            test_email_settings($conDB);
            break;
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action specified.']);
            break;
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
}
// NOTE: don't close $conDB here - includes/db.php already registers a shutdown
// function that closes it. Closing twice throws "mysqli object is already closed"
// on PHP 8.1+ (mysqli exception mode), and that fatal error text lands after the
// JSON we already echoed above, breaking JSON.parse on the client.


// --- Function Definitions ---

/**
 * Fetches all settings from the database.
 */
function get_all_settings($conDB) {
    ensure_report_visibility_setting($conDB);
    ensure_special_access_setting($conDB);
    ensure_page_role_access_setting($conDB);
    ensure_announcement_smtp_settings($conDB);

    $settings = [];
    // db_export_secret_key is auto-generated/rotated from db_export.php's own
    // "Regenerate Key" button - it doesn't belong in the general settings UI
    // as a plain editable text field (and typing over it would just orphan
    // the real key the export pages check against). db_backup_secret_key is
    // different: it's meant to be a stable value for cron, and editing it here
    // is a legitimate way to rotate it (just also update the cron URL), so it
    // stays visible.
    $sql = "SELECT setting_name, setting_value, description, input_type, options, setting_group FROM app_settings WHERE setting_name != 'db_export_secret_key' ORDER BY setting_group, id";
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
    ensure_report_visibility_setting($conDB);
    ensure_special_access_setting($conDB);
    ensure_page_role_access_setting($conDB);
    ensure_announcement_smtp_settings($conDB);

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
 * Ensure report visibility map setting exists in app_settings.
 */
function ensure_report_visibility_setting($conDB) {
    $settingName = 'report_visibility_by_user';
    $defaultValue = '{}';

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

    $insertSql = "INSERT INTO app_settings (setting_name, setting_value, setting_group, description, input_type, options) VALUES (?, ?, 'report_permissions', 'report_visibility_by_user_json_map_emp_id_to_report_type_array', 'text', NULL)";
    $insertStmt = $conDB->prepare($insertSql);
    if (!$insertStmt) {
        return;
    }

    $insertStmt->bind_param("ss", $settingName, $defaultValue);
    $insertStmt->execute();
    $insertStmt->close();
}

/**
 * Ensure special access map setting exists in app_settings.
 */
function ensure_special_access_setting($conDB) {
    $settingName = 'special_access_by_user';
    $defaultValue = '{}';

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

    $insertSql = "INSERT INTO app_settings (setting_name, setting_value, setting_group, description, input_type, options) VALUES (?, ?, 'special_access', 'special_access_by_user_json_map_emp_id_to_access_key_array', 'text', NULL)";
    $insertStmt = $conDB->prepare($insertSql);
    if (!$insertStmt) {
        return;
    }

    $insertStmt->bind_param("ss", $settingName, $defaultValue);
    $insertStmt->execute();
    $insertStmt->close();
}

/**
 * Ensure per-page role-access map setting exists in app_settings, seeded with
 * the roles that used to be hardcoded in includes/main_menu.php so behavior
 * is unchanged until an admin edits it from App Settings > Page Access.
 */
function ensure_page_role_access_setting($conDB) {
    $settingName = 'page_role_access';
    $defaultValue = json_encode(get_default_page_access_roles());

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

    $insertSql = "INSERT INTO app_settings (setting_name, setting_value, setting_group, description, input_type, options) VALUES (?, ?, 'page_role_access', 'page_role_access_json_map_page_file_to_allowed_role_array', 'text', NULL)";
    $insertStmt = $conDB->prepare($insertSql);
    if (!$insertStmt) {
        return;
    }

    $insertStmt->bind_param("ss", $settingName, $defaultValue);
    $insertStmt->execute();
    $insertStmt->close();
}

/**
 * Sends a test email using the SMTP values currently typed into the Email tab's
 * form (not necessarily saved yet), to the Default From Email Address entered
 * there, so an admin can verify credentials before committing to Save Changes.
 */
function test_email_settings($conDB) {
    if (!class_exists('PHPMailer\\PHPMailer\\PHPMailer')) {
        echo json_encode(['success' => false, 'message' => 'PHPMailer library not found.']);
        return;
    }

    $smtp_host = trim((string)($_POST['smtp_host'] ?? ''));
    $smtp_port = trim((string)($_POST['smtp_port'] ?? ''));
    $smtp_user = trim((string)($_POST['smtp_user'] ?? ''));
    $smtp_pass = (string)($_POST['smtp_pass'] ?? '');
    $smtp_encryption = strtolower(trim((string)($_POST['smtp_encryption'] ?? '')));
    $from_email = trim((string)($_POST['from_email'] ?? ''));
    $from_name = trim((string)($_POST['from_name'] ?? '')) ?: 'App Settings Test';
    $cc_email = trim((string)($_POST['admin_email'] ?? ''));

    if ($smtp_host === '' || $smtp_port === '' || $smtp_user === '' || $smtp_pass === '' || $from_email === '') {
        echo json_encode(['success' => false, 'message' => 'Please fill Host, Port, Username, Password and Default From Email Address before testing.']);
        return;
    }

    if (!filter_var($from_email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => 'Default From Email Address is not a valid email address.']);
        return;
    }

    if ($cc_email !== '' && !filter_var($cc_email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => 'Administrator Email for Notifications is not a valid email address.']);
        return;
    }

    $mail = new PHPMailer\PHPMailer\PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = $smtp_host;
        $mail->SMTPAuth = true;
        $mail->Username = $smtp_user;
        $mail->Password = $smtp_pass;

        switch ($smtp_encryption) {
            case 'tls':
                $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
                break;
            case 'ssl':
                $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
                break;
            default:
                $mail->SMTPSecure = false;
                break;
        }

        $mail->Port = (int)$smtp_port;
        $mail->CharSet = 'UTF-8';
        $mail->Timeout = 15;

        $mail->setFrom($from_email, $from_name);
        $mail->addAddress($from_email, $from_name);
        if ($cc_email !== '') {
            $mail->addCC($cc_email);
        }
        $mail->isHTML(true);
        $mail->Subject = 'Test Email - App Settings SMTP Configuration';
        $mail->Body = 'This is a test email confirming your SMTP configuration entered in App Settings is working correctly.<br><br>Sent at: ' . date('Y-m-d H:i:s');
        $mail->AltBody = 'This is a test email confirming your SMTP configuration entered in App Settings is working correctly. Sent at: ' . date('Y-m-d H:i:s');

        $mail->send();
        $recipientNote = $from_email . ($cc_email !== '' ? ' (CC: ' . $cc_email . ')' : '');
        echo json_encode(['success' => true, 'message' => 'Test email sent successfully to ' . $recipientNote . '. Please check the inbox.']);
    } catch (Throwable $e) {
        echo json_encode(['success' => false, 'message' => 'Failed to send test email: ' . $mail->ErrorInfo]);
    }
}

/**
 * Ensure the Announcement Config SMTP settings exist in app_settings. These are
 * kept separate from the general 'email' group's smtp_* rows because
 * send_announcement.php sends from a dedicated mailbox, not the system's
 * general notification sender. Seeded with empty defaults only - never with a
 * real credential - so no secret is ever committed to this file; the actual
 * values are set once directly in the database.
 */
function ensure_announcement_smtp_settings($conDB) {
    $encryptionOptions = json_encode(['none' => 'None', 'tls' => 'TLS', 'ssl' => 'SSL']);

    // Mirrors the general 'email' group's field set (host/port/user/pass/encryption/
    // from email/from name), kept as its own group because send_announcement.php
    // sends from a dedicated mailbox, not the system's general notification sender.
    $defaults = [
        'announcement_smtp_host' => ['', 'text', 'Announcement SMTP Host', null],
        'announcement_smtp_port' => ['', 'text', 'Announcement SMTP Port', null],
        'announcement_smtp_user' => ['', 'text', 'Announcement SMTP Username', null],
        'announcement_smtp_pass' => ['', 'text', 'Announcement SMTP Password', null],
        'announcement_smtp_encryption' => ['tls', 'select', 'Announcement SMTP Encryption', $encryptionOptions],
        'announcement_smtp_from_email' => ['', 'text', 'Announcement From Email', null],
        'announcement_smtp_from_name' => ['', 'text', 'Announcement From Name', null],
    ];

    $checkStmt = $conDB->prepare("SELECT id FROM app_settings WHERE setting_name = ? LIMIT 1");
    $insertStmt = $conDB->prepare("INSERT INTO app_settings (setting_name, setting_value, setting_group, description, input_type, options) VALUES (?, ?, 'announcement_config', ?, ?, ?)");
    if (!$checkStmt || !$insertStmt) {
        return;
    }

    foreach ($defaults as $settingName => $meta) {
        [$defaultValue, $inputType, $description, $options] = $meta;

        $checkStmt->bind_param('s', $settingName);
        if (!$checkStmt->execute()) {
            continue;
        }
        $result = $checkStmt->get_result();
        $exists = ($result && $result->num_rows > 0);
        if ($result) {
            $result->free();
        }
        if ($exists) {
            continue;
        }

        $insertStmt->bind_param('sssss', $settingName, $defaultValue, $description, $inputType, $options);
        $insertStmt->execute();
    }

    $checkStmt->close();
    $insertStmt->close();
}

/**
 * Dedicated save for the Page Access tab, so it persists immediately instead
 * of relying on the far-away global "Save Changes" button at the bottom of
 * the Settings page (that button still also submits this field as a backup).
 */
function update_page_role_access($conDB) {
    ensure_page_role_access_setting($conDB);

    $raw = $_POST['page_role_access'] ?? '{}';
    $decoded = decode_page_access_map($raw);

    // Preserve any page not present in what the client sent (e.g. a page added
    // after this browser tab loaded) instead of silently dropping it.
    $current = get_page_access_map($conDB);
    $merged = array_merge($current, $decoded);

    $value = json_encode($merged);
    $stmt = $conDB->prepare("UPDATE app_settings SET setting_value = ? WHERE setting_name = 'page_role_access'");
    if (!$stmt) {
        echo json_encode(['success' => false, 'message' => 'Failed to prepare statement: ' . $conDB->error]);
        return;
    }
    $stmt->bind_param('s', $value);
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'page_role_access' => $merged]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to save page access: ' . $conDB->error]);
    }
    $stmt->close();
}

/**
 * Return active admin users for report-permission assignment.
 */
function get_report_permission_users($conDB) {
    $users = [];

    $sql = "SELECT al.emp_id, al.id_iqama, al.user_type, al.status, e.name
            FROM admin_login al
            LEFT JOIN employees e ON e.emp_id = al.emp_id
                        WHERE al.emp_id IS NOT NULL
                            AND al.emp_id <> ''
                            AND LOWER(TRIM(COALESCE(al.user_type, ''))) <> 'employee'
            ORDER BY e.name ASC, al.emp_id ASC";

    $result = $conDB->query($sql);
    if (!$result) {
        echo json_encode(['success' => false, 'message' => 'Error fetching users: ' . $conDB->error]);
        return;
    }

    while ($row = $result->fetch_assoc()) {
        $rawName = trim((string)($row['name'] ?? ''));
        $name = $rawName !== '' ? parseName($rawName) : (string)($row['id_iqama'] ?? '');

        $users[] = [
            'emp_id' => (string)($row['emp_id'] ?? ''),
            'name' => $name,
            'user_type' => (string)($row['user_type'] ?? ''),
            'status' => (string)($row['status'] ?? ''),
        ];
    }

    echo json_encode(['success' => true, 'users' => $users]);
}

/**
 * Same shape as get_report_permission_users(), but WITHOUT excluding user_type='employee' -
 * Special Access grants (e.g. access_all_applied_vac) are specifically meant to unlock a
 * normally-blocked page for one plain employee, so they must be selectable here.
 */
function get_special_access_users($conDB) {
    $users = [];

    $sql = "SELECT al.emp_id, al.id_iqama, al.user_type, al.status, e.name
            FROM admin_login al
            LEFT JOIN employees e ON e.emp_id = al.emp_id
                        WHERE al.emp_id IS NOT NULL
                            AND al.emp_id <> ''
            ORDER BY e.name ASC, al.emp_id ASC";

    $result = $conDB->query($sql);
    if (!$result) {
        echo json_encode(['success' => false, 'message' => 'Error fetching users: ' . $conDB->error]);
        return;
    }

    while ($row = $result->fetch_assoc()) {
        $rawName = trim((string)($row['name'] ?? ''));
        $name = $rawName !== '' ? parseName($rawName) : (string)($row['id_iqama'] ?? '');

        $users[] = [
            'emp_id' => (string)($row['emp_id'] ?? ''),
            'name' => $name,
            'user_type' => (string)($row['user_type'] ?? ''),
            'status' => (string)($row['status'] ?? ''),
        ];
    }

    echo json_encode(['success' => true, 'users' => $users]);
}
?>