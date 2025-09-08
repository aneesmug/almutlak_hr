<?php
// File: includes/ajaxFile/delete_translation_ajax.php (Updated)
// Handles deleting all translations associated with a specific key.

/**************************************************************************************************
 * MODIFICATION SUMMARY
 *
 * 1.  **Removed JSON Regeneration**: The call to `regenerate_translation_files()` has been removed.
 * The system now reads directly from the database, so file generation is obsolete.
 *
 **************************************************************************************************/

require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/translation_functions.php';

header('Content-Type: application/json');

if (empty($_POST['lang_key'])) {
    echo json_encode(['status' => 'error', 'title_key' => 'error_modal_title', 'message_key' => 'error_invalid_key']);
    exit();
}

$lang_key = mysqli_real_escape_string($conDB, $_POST['lang_key']);

$sql = "DELETE FROM translations WHERE lang_key = '{$lang_key}'";

if (mysqli_query($conDB, $sql)) {
    // Check if any rows were actually deleted
    if (mysqli_affected_rows($conDB) > 0) {
        // The call to regenerate_translation_files() is no longer needed.
        echo json_encode(['status' => 'success', 'title_key' => 'deleted_modal_title', 'message_key' => 'success_translation_deleted']);
    } else {
        echo json_encode(['status' => 'error', 'title_key' => 'error_modal_title', 'message_key' => 'error_key_not_found']);
    }
} else {
    // You might want to log the actual error: error_log(mysqli_error($conDB));
    echo json_encode(['status' => 'error', 'title_key' => 'error_modal_title', 'message_key' => 'error_database_error']);
}

mysqli_close($conDB);
?>
