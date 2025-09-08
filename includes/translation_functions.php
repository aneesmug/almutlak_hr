<?php
// File: includes/translation_functions.php (Updated)

/**************************************************************************************************
 * MODIFICATION SUMMARY
 *
 * 1.  **Switched to Database-Driven Loading**: Replaced the previous JSON file loading system with the
 * functions from your `language_helper.php`. Translations are now fetched directly from the database.
 * 2.  **Adopted `load_language()`**: The new `load_language()` function is now the primary loader.
 * - It uses a static variable (`$is_loaded`) to ensure the database is queried only ONCE per request,
 * which is very efficient.
 * - It has been adapted to use your project's existing global database connection variable, `$conDB`.
 * 3.  **Adopted `__()`**: The `__()` function has been updated to the new version which supports an
 * optional default value.
 * 4.  **Removed Obsolete Function**: The `regenerate_translation_files()` function has been completely
 * removed as it is no longer needed.
 *
 **************************************************************************************************/

// Global variable to hold the translations for the current language
$GLOBALS['translations'] = [];

/**
 * Loads all translation strings for a given language code from the database.
 * This function is idempotent and will only run the database query once per request.
 *
 * @param string $lang_code The language code (e.g., 'en', 'ar').
 */
function load_language(string $lang_code = 'en') {
    global $conDB; // Use the existing global connection from your project
    static $is_loaded = false;

    // Only load from the database once per page request.
    if ($is_loaded) {
        return;
    }

    // Check if the database connection exists.
    if (!$conDB) {
        error_log("Translation Functions: Database connection variable \$conDB is not available.");
        $GLOBALS['translations'] = []; // Reset to empty on failure
        $is_loaded = true; // Mark as "attempted" to prevent retries
        return;
    }
    
    // CRITICAL: Ensure UTF-8 communication with the database.
    mysqli_set_charset($conDB, "utf8mb4");

    $GLOBALS['translations'] = []; // Start with a clean slate for the new load.

    try {
        $escaped_lang_code = mysqli_real_escape_string($conDB, $lang_code);
        $query = "SELECT lang_key, translation FROM translations WHERE lang_code = '{$escaped_lang_code}'";
        $result = mysqli_query($conDB, $query);

        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                $GLOBALS['translations'][$row['lang_key']] = $row['translation'];
            }
            mysqli_free_result($result);
        }
        
    } catch (Exception $e) {
        // Log error if something goes wrong, but don't crash the application.
        error_log("Could not load language '{$lang_code}': " . $e->getMessage());
        $GLOBALS['translations'] = []; // Ensure it's empty on error
    }
    
    $is_loaded = true; // Mark as loaded for this request.
}

/**
 * Translates a given key into the currently loaded language.
 *
 * @param string $key The language key to translate (e.g., 'user_management').
 * @param string $default An optional default value to return if the key is not found.
 * @return string The translated string, or the key/default value if not found.
 */
function __(string $key, string $default = ''): string {
    if (isset($GLOBALS['translations'][$key]) && !empty($GLOBALS['translations'][$key])) {
        return $GLOBALS['translations'][$key];
    }
    
    // If a default is provided, use it. Otherwise, return the key itself.
    return $default !== '' ? $default : $key;
}
