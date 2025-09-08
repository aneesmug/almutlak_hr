<?php
// File: includes/ajaxFile/update_translation_ajax.php (Updated)
// Handles updating existing translations for a given language key.

/**************************************************************************************************
 * MODIFICATION SUMMARY
 *
 * 1.  **Modernized Logic**: Completely rewrote the script to work with the `translations` table,
 * removing the old logic that targeted the obsolete `language` table.
 * 2.  **Automated Key Regeneration**: When you update the English translation, a new `lang_key` is
 * automatically generated from it (lowercase, with spaces replaced by underscores).
 * 3.  **Transactional Integrity**: Uses a MySQL transaction to ensure that updates to both the
 * English and Arabic records succeed or fail together, preventing data inconsistencies.
 * 4.  **Key Conflict Check**: Before updating, the script checks if the newly generated key
 * already exists in the database to prevent accidental duplicates.
 * 5.  **Removed JSON Regeneration**: The code no longer needs to regenerate JSON files, as the
 * system now fetches translations directly from the database.
 * 6.  **IMPORTANT**: Be aware that changing the English text will change the `lang_key`. You will
 * need to update this key in your code (e.g., change `__('old_key')` to `__('new_key')`).
 *
 **************************************************************************************************/

require_once __DIR__ . '/../../includes/db.php';

header('Content-Type: application/json');

// --- Input Validation and Sanitization ---

if (empty($_POST['original_lang_key']) || !isset($_POST['en_translation']) || !isset($_POST['ar_translation'])) {
    echo json_encode(['type' => 'error', 'title' => 'Error', 'message' => 'Missing required fields.']);
    exit();
}

$original_lang_key = mysqli_real_escape_string($conDB, trim($_POST['original_lang_key']));
$en_translation_raw = trim($_POST['en_translation']);
$ar_translation = mysqli_real_escape_string($conDB, trim($_POST['ar_translation']));
$en_translation = mysqli_real_escape_string($conDB, $en_translation_raw);

// --- Automated Key Regeneration ---

// Generate the new language key from the new English text.
$new_lang_key = strtolower(str_replace(' ', '_', $en_translation_raw));
$new_lang_key = mysqli_real_escape_string($conDB, $new_lang_key);

// --- Database Update Logic ---

// Check if the new key already exists and is different from the original key.
if ($original_lang_key !== $new_lang_key) {
    $check_query = "SELECT translation_id FROM translations WHERE lang_key = '{$new_lang_key}'";
    $check_result = mysqli_query($conDB, $check_query);
    if (mysqli_num_rows($check_result) > 0) {
        echo json_encode(['type' => 'error', 'title' => 'Duplicate Key', 'message' => 'A language key based on this English translation already exists.']);
        exit();
    }
}

// Use a transaction to ensure both updates succeed or fail together.
mysqli_begin_transaction($conDB);

try {
    // 1. Update the English record with the new key and text.
    $sql_en = "UPDATE translations SET lang_key = '{$new_lang_key}', translation = '{$en_translation}' WHERE lang_key = '{$original_lang_key}' AND lang_code = 'en'";
    if (!mysqli_query($conDB, $sql_en)) {
        throw new Exception("Error updating English translation.");
    }

    // 2. Update the Arabic record with the new key and text.
    $sql_ar = "UPDATE translations SET lang_key = '{$new_lang_key}', translation = '{$ar_translation}' WHERE lang_key = '{$original_lang_key}' AND lang_code = 'ar'";
    if (!mysqli_query($conDB, $sql_ar)) {
        // If the Arabic record doesn't exist for some reason, create it.
        if (mysqli_affected_rows($conDB) == 0) {
            $sql_ar_insert = "INSERT INTO translations (lang_key, lang_code, translation) VALUES ('{$new_lang_key}', 'ar', '{$ar_translation}')";
            if(!mysqli_query($conDB, $sql_ar_insert)) {
                 throw new Exception("Error creating Arabic translation.");
            }
        } else {
            throw new Exception("Error updating Arabic translation.");
        }
    }

    // If both queries are successful, commit the transaction.
    mysqli_commit($conDB);
    echo json_encode(['type' => 'success', 'title' => 'Updated!', 'message' => 'Translation has been updated successfully.']);

} catch (Exception $e) {
    // If any query fails, roll back the transaction.
    mysqli_rollback($conDB);
    echo json_encode(['type' => 'error', 'title' => 'Database Error', 'message' => 'Could not update the translation.']);
}

mysqli_close($conDB);
?>
