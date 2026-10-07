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
if (!function_exists('load_language')) {
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

        if ($lang_code !== 'en') {
            repair_translation_placeholders($conDB);
        }

    } catch (Exception $e) {
        // Log error if something goes wrong, but don't crash the application.
        error_log("Could not load language '{$lang_code}': " . $e->getMessage());
        $GLOBALS['translations'] = []; // Ensure it's empty on error
    }
    
    $is_loaded = true; // Mark as loaded for this request.
}
}

/**
 * sprintf() placeholder check for a translation string:
 *  'count' - real placeholders (%s, %d, %u, %f, %.2f, %1$s ...)
 *  'pure'  - every '%' is such a placeholder (or '%%'), i.e. the text is a sprintf format
 *  'safe'  - PHP 8's sprintf() would not throw on it (no '%' outside a valid conversion)
 */
if (!function_exists('translation_placeholder_info')) {
function translation_placeholder_info(string $text): array {
    $text = str_replace('%%', '', $text);
    $real = '/%(?:\d+\$)?(?:\.\d+)?[sdfu]/';
    $anyPhp = '/%(?:\d+\$)?[-+ 0\'#]*\d*(?:\.\d+)?[bcdeEfFgGosuxX]/';
    return [
        'count' => (int) preg_match_all($real, $text),
        'pure'  => strpos(preg_replace($real, '', $text), '%') === false,
        'safe'  => strpos(preg_replace($anyPhp, '', $text), '%') === false,
    ];
}
}

/**
 * Non-English translations are typed in an RTL editor, which easily turns "%s" into "s%"
 * (or leaves a stray "%"). The code passes these strings to sprintf(), so on PHP 8 one bad
 * translation makes the whole request fail - but only for users of that language (e.g. a
 * vacation request that submits fine in English errors out in Arabic).
 * For every loaded translation whose English source has placeholders: flipped placeholders
 * are repaired; if it is still unusable (stray '%', or more placeholders than English) the
 * English text is used instead. Each bad key is logged so it can be fixed in language.php.
 */
if (!function_exists('repair_translation_placeholders')) {
function repair_translation_placeholders($conDB): void {
    $suspect = [];
    foreach ($GLOBALS['translations'] as $key => $text) {
        if (is_string($text) && strpos($text, '%') !== false) {
            $suspect[$key] = $text;
        }
    }
    if (!$suspect) {
        return;
    }

    $keys = implode(',', array_map(function ($k) use ($conDB) {
        return "'" . mysqli_real_escape_string($conDB, $k) . "'";
    }, array_keys($suspect)));
    $english = [];
    $res = mysqli_query($conDB, "SELECT lang_key, translation FROM translations WHERE lang_code = 'en' AND lang_key IN ($keys)");
    if ($res) {
        while ($row = mysqli_fetch_assoc($res)) {
            $english[$row['lang_key']] = (string) $row['translation'];
        }
        mysqli_free_result($res);
    }

    foreach ($suspect as $key => $text) {
        $en = $english[$key] ?? null;
        $enInfo = $en !== null ? translation_placeholder_info($en) : null;
        // Only strings whose English version is a real sprintf format matter here
        // (plain texts like "Up to 50% of salary" are never passed to sprintf).
        if (!$enInfo || !$enInfo['pure'] || $enInfo['count'] === 0) {
            continue;
        }
        $info = translation_placeholder_info($text);
        if ($info['pure'] && $info['count'] <= $enInfo['count']) {
            continue;
        }

        // "s%" / "d%" written right-to-left -> "%s" / "%d" (only where no valid placeholder follows)
        $repaired = preg_replace('/(?<![%\w])([sdfu])%(?![-+ 0\'#]*\d*(?:\.\d+)?[bcdeEfFgGosuxX])/u', '%$1', $text);
        $repairedInfo = translation_placeholder_info($repaired);
        if ($repairedInfo['pure'] && $repairedInfo['count'] === $enInfo['count']) {
            $GLOBALS['translations'][$key] = $repaired;
        } else {
            $GLOBALS['translations'][$key] = $en;
        }
        // Logged at most once an hour per key (this runs on every request)
        $marker = sys_get_temp_dir() . '/almutlak_bad_translation_' . md5($key);
        if (!is_file($marker) || filemtime($marker) < time() - 3600) {
            @touch($marker);
            error_log("Translation '{$key}' has broken sprintf placeholders - fix it in language.php. Using "
                . ($GLOBALS['translations'][$key] === $en ? 'English' : 'auto-repaired') . ' text for now.');
        }
    }
}
}

/**
 * Translates a given key into the currently loaded language.
 *
 * @param string $key The language key to translate (e.g., 'user_management').
 * @param string $default An optional default value to return if the key is not found.
 * @return string The translated string, or the key/default value if not found.
 */
if (!function_exists('__')) {
function __(string $key, string $default = ''): string {
    if (isset($GLOBALS['translations'][$key]) && !empty($GLOBALS['translations'][$key])) {
        return $GLOBALS['translations'][$key];
    }
    // If a default is provided, use it. Otherwise, return the key itself.
    return $default !== '' ? $default : $key;
}
}

/**
 * Translate employee name to Arabic if current language is Arabic
 * Uses the translateText.php endpoint for actual translation
 * Returns Arabic translation if language is Arabic, otherwise returns original name
 *
 * @param string|null $name Employee name in English (can be null)
 * @param string $current_lang Current language code
 * @return string Translated or original name (returns empty string if null)
 */
if (!function_exists('translate_name')) {
function translate_name(?string $name, string $current_lang = 'en'): string {
    global $is_rtl;
    // Handle NULL or empty names
    if ($name === null || trim($name) === '') {
        return '';
    }
    // Only translate if Arabic language is selected
    if ($current_lang === 'ar' || $is_rtl === true) {
        // Ensure session is started for caching
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        // Include the translateText.php file to use its auto_translate_text function
        $translateFile = __DIR__ . '/ajaxFile/translateText.php';
        if (file_exists($translateFile)) {
            require_once $translateFile;
            if (function_exists('auto_translate_text')) {
                return auto_translate_text($name, 'en', 'ar');
            }
        }
    }
    return $name;
}
}

