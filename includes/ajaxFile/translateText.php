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
 * Helper function: Auto-translate text with session and database caching
 * Saves translations to translation_cache table for persistent reuse
 *
 * @param string $text Text to translate
 * @param string $source Source language code
 * @param string $target Target language code
 * @return string Translated text or original if fails
 */
function auto_translate_text(string $text, string $source = 'en', string $target = 'ar'): string {
    if (empty($text)) {
        return $text;
    }
    
    global $conDB;
    static $request_translation_cache = [];
    $cache_key = md5($text . '_' . $source . '_' . $target);
    
    // 1. Check request cache first (static variable - fastest, lasts for one page load)
    if (isset($request_translation_cache[$cache_key])) {
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
            return $translated;
        }
    }
    
    // 3. API call only as last resort
    try {
        $url = "https://translate.googleapis.com/translate_a/single?client=gtx&sl=" 
               . urlencode($source) 
               . "&tl=" . urlencode($target) 
               . "&dt=t&q=" . urlencode($text);
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode !== 200 || !$response) {
            return $text;
        }
        
        // Parse the response
        $result = json_decode($response, true);
        
        if (!$result || !isset($result[0])) {
            return $text;
        }
        
        // Extract translated text from response
        $translatedText = '';
        foreach ($result[0] as $segment) {
            if (isset($segment[0])) {
                $translatedText .= $segment[0];
            }
        }
        
        if (empty($translatedText)) {
            return $text;
        }
        
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
    $translatedText = auto_translate_text($text, $source, $target);
    
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

