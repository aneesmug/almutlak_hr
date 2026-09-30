<?php
/**
 * Google Translate API Handler
 * Translates text from one language to another using Google Translate
 * 
 * This file can be used in two ways:
 * 1. Direct AJAX endpoint - POST request with 'text' parameter
 * 2. Include as library - Call auto_translate_text() function directly
 */

// Start session for caching if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Helper function: GET a URL with cURL.
 *
 * @param string $url
 * @param int|null $httpCode Set to the HTTP status code of the response
 * @return string|null Response body, or null when the request failed
 */
function translate_http_get(string $url, ?int &$httpCode = null): ?string {
    $httpCode = 0;

    if (!function_exists('curl_init')) {
        error_log('Translation failed: cURL extension is not available');
        return null;
    }

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');

    $response = curl_exec($ch);
    $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($httpCode !== 200 || !$response) {
        error_log('Translation request failed (' . parse_url($url, PHP_URL_HOST) . '): HTTP ' . $httpCode . ' ' . $curlError);
        return null;
    }

    return $response;
}

/**
 * Helper function: Check (or set) the MyMemory "quota exhausted" flag.
 * The flag is kept in a temp file so it is shared by all requests, and expires after one hour.
 *
 * @param bool $block Pass true to raise the flag
 * @return bool True while MyMemory should be skipped
 */
function mymemory_quota_blocked(bool $block = false): bool {
    static $blocked = null;
    $flagFile = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'mymemory_quota_' . md5(__DIR__) . '.flag';

    if ($block) {
        $blocked = true;
        @touch($flagFile);
        return true;
    }

    if ($blocked === null) {
        $flagTime = @filemtime($flagFile);
        $blocked = $flagTime !== false && (time() - $flagTime) < 3600;
    }

    return $blocked;
}

/**
 * Helper function: Translate with MyMemory.
 *
 * @return string|null Translated text, or null when no translation was obtained
 */
function translate_via_mymemory(string $text, string $source, string $target): ?string {
    // Quota used up recently: skip the call instead of waiting for another HTTP 429
    if (mymemory_quota_blocked()) {
        return null;
    }

    $httpCode = 0;
    $response = translate_http_get(
        "https://api.mymemory.translated.net/get?q="
        . urlencode($text)
        . "&langpair=" . urlencode($source) . "|" . urlencode($target),
        $httpCode
    );

    if ($response === null) {
        if ($httpCode === 429) {
            mymemory_quota_blocked(true);
        }
        return null;
    }

    $result = json_decode($response, true);

    if (!$result || empty($result['responseData']['translatedText'])) {
        return null;
    }

    $translatedText = $result['responseData']['translatedText'];

    // Quota/limit errors come back as HTTP 200 with a warning in place of the translation
    if ((int)($result['responseStatus'] ?? 200) !== 200 || stripos($translatedText, 'MYMEMORY WARNING') !== false) {
        error_log('Translation request failed (MyMemory): ' . $translatedText);
        mymemory_quota_blocked(true);
        return null;
    }

    return $translatedText;
}

/**
 * Helper function: Translate with Google Translate (fallback).
 *
 * @return string|null Translated text, or null when no translation was obtained
 */
function translate_via_google(string $text, string $source, string $target): ?string {
    $response = translate_http_get(
        "https://clients5.google.com/translate_a/t?client=dict-chrome-ex"
        . "&sl=" . urlencode($source)
        . "&tl=" . urlencode($target)
        . "&q=" . urlencode($text)
    );

    if ($response === null) {
        return null;
    }

    $result = json_decode($response, true);

    if (!is_array($result) || empty($result[0])) {
        return null;
    }

    // Response is ["translated"] (or [["translated", "detected_lang"]] when source is auto)
    $translatedText = is_array($result[0]) ? ($result[0][0] ?? '') : $result[0];

    return is_string($translatedText) && $translatedText !== '' ? $translatedText : null;
}

/**
 * Helper function: Auto-translate text with session and database caching
 * Saves translations to translation_cache table for persistent reuse
 *
 * @param string $text Text to translate
 * @param string $source Source language code
 * @param string $target Target language code
 * @param bool|null $translated_ok Set to true only when a real translation was obtained
 * @return string Translated text or original if fails
 */
function auto_translate_text(string $text, string $source = 'en', string $target = 'ar', ?bool &$translated_ok = null): string {
    $translated_ok = false;

    if (empty($text)) {
        return $text;
    }

    global $conDB;
    static $request_translation_cache = [];
    $cache_key = md5($text . '_' . $source . '_' . $target);
    
    // 1. Check request cache first (static variable - fastest, lasts for one page load)
    if (isset($request_translation_cache[$cache_key])) {
        $translated_ok = true;
        return $request_translation_cache[$cache_key];
    }
    
    // 2. Check database cache (persistent across sessions)
    if ($conDB) {
        $text_hash = md5($text);
        $db_check = mysqli_query($conDB, 
            "SELECT translated_text FROM translation_cache 
             WHERE text_hash = '" . mysqli_real_escape_string($conDB, $text_hash) . "' 
             AND source_lang = '" . mysqli_real_escape_string($conDB, $source) . "'
             AND target_lang = '" . mysqli_real_escape_string($conDB, $target) . "' 
             LIMIT 1"
        );
        
        if ($db_check && $db_row = mysqli_fetch_assoc($db_check)) {
            $translated = $db_row['translated_text'];
            // Store in request cache
            $request_translation_cache[$cache_key] = $translated;
            $translated_ok = true;
            return $translated;
        }
    }
    
    // 3. API call only as last resort (MyMemory first, Google as fallback - both free, no key required)
    try {
        $translatedText = translate_via_mymemory($text, $source, $target);

        if ($translatedText === null) {
            $translatedText = translate_via_google($text, $source, $target);
        }

        if ($translatedText === null) {
            return $text;
        }

        $translated_ok = true;

        // Cache in request cache immediately
        $request_translation_cache[$cache_key] = $translatedText;
        
        // Save to database (persistent cache for all users)
        if ($conDB) {
            $text_hash = md5($text);
            $source_safe = mysqli_real_escape_string($conDB, $source);
            $target_safe = mysqli_real_escape_string($conDB, $target);
            $text_truncated = substr($text, 0, 500);
            $text_truncated_safe = mysqli_real_escape_string($conDB, $text_truncated);
            $translated_safe = mysqli_real_escape_string($conDB, $translatedText);
            
            // Use INSERT ... ON DUPLICATE KEY UPDATE for upsert
            $insert_sql = "INSERT INTO translation_cache 
                          (text_hash, source_text, source_lang, target_lang, translated_text, created_at) 
                          VALUES (
                             '" . $text_hash . "',
                             '" . $text_truncated_safe . "',
                             '" . $source_safe . "',
                             '" . $target_safe . "',
                             '" . $translated_safe . "',
                             NOW()
                          )
                          ON DUPLICATE KEY UPDATE 
                             translated_text = VALUES(translated_text), 
                             updated_at = NOW()";
            
            // Execute without suppressing errors - log them instead
            if (!mysqli_query($conDB, $insert_sql)) {
                error_log("Translation cache insert failed: " . mysqli_error($conDB));
            }
        }
        
        return $translatedText;
        
    } catch (Exception $e) {
        return $text;
    }
}

// ========================================
// AJAX ENDPOINT - Only handle direct POST requests
// ========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['text'])) {
    header('Content-Type: application/json');
    
    $text = $_POST['text'] ?? '';
    $source = $_POST['source'] ?? 'en';
    $target = $_POST['target'] ?? 'ar';
    
    if (empty($text)) {
        echo json_encode([
            'success' => false,
            'error' => 'No text provided for translation'
        ]);
        exit;
    }
    
    // Use the auto_translate_text function
    $translated_ok = false;
    $translatedText = auto_translate_text($text, $source, $target, $translated_ok);

    // Translation service unreachable / over quota: report failure instead of echoing the source text
    if (!$translated_ok) {
        echo json_encode([
            'success' => false,
            'error' => 'Translation service unavailable'
        ]);
        exit;
    }

    echo json_encode([
        'success' => true,
        'translation' => $translatedText,
        'source' => $source,
        'target' => $target,
        'cached' => false
    ]);
    exit;
}

// Check session cache first for better performance - handled in function above

