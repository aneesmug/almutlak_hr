<?php
// File: includes/ajaxFile/add_translation_ajax.php (Updated)
// Handles adding new translations for both English and Arabic.

/**************************************************************************************************
 * MODIFICATION SUMMARY
 *
 * 1.  **Reverted English Generation**: The script no longer automatically generates the English
 * translation. It now expects and uses the `en_translation` value submitted from the form,
 * which is auto-populated and editable on the front-end.
 * 2.  **Retained Key Formatting**: The `lang_key` is still processed on the backend to ensure it is
 * lowercase and uses underscores, providing consistent and reliable key formatting.
 *
 **************************************************************************************************/

require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/translation_functions.php';

header('Content-Type: application/json');

// Validation now includes en_translation from the form.
if (empty($_POST['lang_key']) || !isset($_POST['en_translation']) || !isset($_POST['ar_translation'])) {
    echo json_encode(['status' => 'error', 'message_key' => 'error_all_fields_required']);
    exit();
}

// --- Input Processing ---

// 1. Process the language key: convert to lowercase and replace spaces with underscores.
$raw_key = trim($_POST['lang_key']);
$lang_key = strtolower(str_replace(' ', '_', $raw_key));
$lang_key = mysqli_real_escape_string($conDB, $lang_key);

// 2. Get the user-provided English translation from the form.
$en_translation = mysqli_real_escape_string($conDB, trim($_POST['en_translation']));

// 3. Get the Arabic translation from the form input.
$ar_translation = mysqli_real_escape_string($conDB, trim($_POST['ar_translation']));


// --- Database Insertion ---

// Check if the processed key already exists
$check_query = "SELECT translation_id FROM translations WHERE lang_key = '{$lang_key}'";
$check_result = mysqli_query($conDB, $check_query);
if (mysqli_num_rows($check_result) > 0) {
    echo json_encode(['title'   => "Error!",'message' => "This record key already exists.",'type' => 'error']);
    // echo json_encode(['status' => 'error', 'message_key' => '']);
    exit();
}

// Use a transaction to ensure both inserts succeed or fail together
mysqli_begin_transaction($conDB);

try {
    // Insert English translation
    $sql_en = "INSERT INTO translations (lang_key, lang_code, translation) VALUES ('{$lang_key}', 'en', '{$en_translation}')";
    if (!mysqli_query($conDB, $sql_en)) {
        throw new Exception(mysqli_error($conDB));
    }

    // Insert Arabic translation
    $sql_ar = "INSERT INTO translations (lang_key, lang_code, translation) VALUES ('{$lang_key}', 'ar', '{$ar_translation}')";
    if (!mysqli_query($conDB, $sql_ar)) {
        throw new Exception(mysqli_error($conDB));
    }

    // If both inserts are successful, commit the transaction
    mysqli_commit($conDB);

    // echo json_encode(['status' => 'success', 'message_key' => 'success_translation_added']);
    echo json_encode(['title'   => "Added!",'message' => "This record has been added successfully.",'type'    => 'success', 'lang_key'=> $lang_key ]);

} catch (Exception $e) {
    // If any query fails, roll back the transaction
    mysqli_rollback($conDB);
    echo json_encode(['title'   => "Error!",'message' => "Record not added because there are some error.",'type' 	  => 'error']);
}

mysqli_close($conDB);
?>
