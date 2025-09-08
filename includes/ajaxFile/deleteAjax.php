<?php
// It's good practice to set the content type to JSON at the beginning
// if the script's primary purpose is to return JSON.
header('Content-Type: application/json');
// --- Configuration ---
// 1. Database Connection:
// Include your database connection file.
// IMPORTANT: This file should establish a PDO connection, like:
// $pdo = new PDO("mysql:host=localhost;dbname=your_db", "username", "password");
// Using PDO is crucial for security.
require_once __DIR__ . '/../../includes/db.php'; // This should provide the $pdo object.
include("./../../includes/custom_functions.php"); // --- Helper Function ---
// 2. File Path Mapping:
// Maps a table name to its corresponding file directory for tables WITH attachments.
const PATH_MAP = [
    'menu_item'          => './../../QR_MENU/images/item_img/',
    'gallery'            => './../../assets/gallery/',
    'cars_docu'          => './../../assets/cars_documents/',
    'emp_docu'           => './../../assets/emp_documents/',
    'location_docu'      => './../../assets/location_content/',
    'vouchers'           => './../../assets/voucher_documents/',
    'emp_inv_attachment' => './../../assets/invo_attach_emp/',
    'smt_attachment'     => './../../assets/smt_attachment/',
];

// 3. Whitelists for Security:
// Define all possible attachment column names here. This is a security measure.
$allowed_columns = [
    'attachment', 'file', 'image', 'document', 'invoice_file', 'profile_pic','path'
];
// --- Input Validation ---
// Sanitize and validate all incoming POST data.
$table_name  = $_POST['tbl'] ?? null;
$record_id   = $_POST['id'] ?? null;
$column_name = $_POST['column'] ?? null;
$ajax_type   = $_POST['ajaxType'] ?? 'delete'; // Default to a hard delete if not specified.
// Check for required parameters.
if (!$table_name || !$record_id) {
    send_json_response("Bad Request", "Missing required parameters (table or id).", "error", 400);
}
// Security Check: If a column is specified, ensure it's in our whitelist.
if ($column_name && !in_array($column_name, $allowed_columns)) {
    send_json_response("Forbidden", "Invalid column specified for file deletion.", "error", 403);
}
// --- Main Logic ---
try {
    // A. Handle File Deletion (only if a column is provided AND the table has a configured path)
    $file_to_delete = null;
    if ($column_name && array_key_exists($table_name, PATH_MAP)) {
        $path = PATH_MAP[$table_name]; // We know the key exists.
        // Securely fetch the filename from the database BEFORE any deletion.
        $stmt = $pdo->prepare("SELECT `$column_name` FROM `$table_name` WHERE `id` = :id");
        $stmt->execute([':id' => $record_id]);
        $filename = $stmt->fetchColumn();

        if ($filename) {
            $file_to_delete = $path . $filename;
        }
    }
    // B. Handle Database Record Deletion/Update
    $sql = '';
    // Use a switch statement for cleaner logic.
    switch ($ajax_type) {
        case 'deleteInv':
            // Soft delete by setting the 'deleted' flag.
            $sql = "UPDATE `$table_name` SET `deleted` = 1 WHERE `id` = :id";
            break;
        case 'isDelete':
            // Soft delete by setting the 'is_deleted' flag.
            $sql = "UPDATE `$table_name` SET `is_deleted` = 1 WHERE `id` = :id";
            break;
        default:
            // Default action is a permanent (hard) delete.
            $sql = "DELETE FROM `$table_name` WHERE `id` = :id";
            break;
    }
    // Prepare and execute the database query.
    $stmt = $pdo->prepare($sql);
    $success = $stmt->execute([':id' => $record_id]);

    if ($success) {
        // C. Physically Delete the File (only after the database query succeeds)
        if ($file_to_delete && file_exists($file_to_delete)) {
            // Use @unlink to suppress warnings if the file is somehow already gone.
            @unlink($file_to_delete);
        }
        send_json_response("Success!", "Record processed successfully.", "success");
    } else {
        // This case is less likely with PDO but good to have.
        send_json_response("Error!", "Database operation failed.", "error", 500);
    }
} catch (PDOException $e) {
    // Catch any database-related errors.
    // For a production environment, you should log the error instead of displaying it.
    // error_log("Database Error: " . $e->getMessage());
    send_json_response("Server Error", "A database error occurred. Please contact support.", "error", 500);
} catch (Exception $e) {
    // Catch any other general errors.
    send_json_response("Server Error", "An unexpected error occurred.", "error", 500);
}
?>