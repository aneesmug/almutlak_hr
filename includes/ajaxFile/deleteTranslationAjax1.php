<?php
// File: admin/includes/ajaxFile/deleteTranslationAjax.php
// Handles deleting all translations (en, ar, etc.) for a given key.

require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/translation_functions.php';

header('Content-Type: application/json');

// Now expects a 'key' instead of a numeric 'id'
if (isset($_POST['key'])) {
    $key = $_POST['key'];
    $stmt = mysqli_prepare($conDB, "DELETE FROM `translations` WHERE `lang_key` = ?");
    // Bind as a string 's'
    mysqli_stmt_bind_param($stmt, "s", $key);
    
    if (mysqli_stmt_execute($stmt)) {
        // Regenerate files after deletion
        regenerate_translation_files($conDB);
        echo json_encode(['title' => 'Deleted!', 'message' => 'All translations for this key have been deleted.', 'type' => 'success']);
    } else {
        echo json_encode(['title' => 'Error!', 'message' => 'Could not delete the translations.', 'type' => 'error']);
    }
    mysqli_stmt_close($stmt);
} else {
    echo json_encode(['title' => 'Error!', 'message' => 'Invalid request. No key provided.', 'type' => 'error']);
}
?>