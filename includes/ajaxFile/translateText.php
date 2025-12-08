<?php
/**
 * Google Translate API Handler
 * Translates text from one language to another using Google Translate
 */

// Start session for caching if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Only set JSON header if this is an AJAX request
if (!function_exists('auto_translate_text')) {
    header('Content-Type: application/json');
}

// Get input parameters (from POST or function call)
$text = $_POST['text'] ?? '';
$source = $_POST['source'] ?? 'en';
$target = $_POST['target'] ?? 'ar';

// Only exit if this is an AJAX request (POST data present) and text is empty
if (!empty($_POST) && empty($text)) {
    echo json_encode([
        'success' => false,
        'error' => 'No text provided for translation'
    ]);
    exit;
}

// Check session cache first for better performance
$cache_key = 'translation_' . md5($text . '_' . $source . '_' . $target);
if (isset($_SESSION[$cache_key])) {
    if (!empty($_POST)) {
        echo json_encode([
            'success' => true,
            'translation' => $_SESSION[$cache_key],
            'source' => $source,
            'target' => $target,
            'cached' => true
        ]);
        exit;
    }
}

try {
    // Use Google Translate's free API endpoint
    // Note: This uses the unofficial free API. For production, consider using the official Google Cloud Translation API
    $url = "https://translate.googleapis.com/translate_a/single?client=gtx&sl=" 
           . urlencode($source) 
           . "&tl=" . urlencode($target) 
           . "&dt=t&q=" . urlencode($text);
    
    // Initialize cURL
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    
    // Execute request
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode !== 200 || !$response) {
        throw new Exception('Translation service request failed');
    }
    
    // Parse the response
    $result = json_decode($response, true);
    
    if (!$result || !isset($result[0])) {
        throw new Exception('Invalid response from translation service');
    }
    
    // Extract translated text from response
    $translatedText = '';
    foreach ($result[0] as $segment) {
        if (isset($segment[0])) {
            $translatedText .= $segment[0];
        }
    }
    
    if (empty($translatedText)) {
        throw new Exception('No translation returned');
    }
    
    // Cache the translation in session
    $_SESSION[$cache_key] = $translatedText;
    
    // Return success response (only for AJAX requests)
    if (!empty($_POST)) {
        echo json_encode([
            'success' => true,
            'translation' => $translatedText,
            'source' => $source,
            'target' => $target,
            'cached' => false
        ]);
    }
    
} catch (Exception $e) {
    if (!empty($_POST)) {
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage()
        ]);
    }
}

/**
 * Helper function: Auto-translate text with session caching
 * This function can be called directly from PHP files
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
    
    // Check session cache
    $cache_key = 'translation_' . md5($text . '_' . $source . '_' . $target);
    if (isset($_SESSION[$cache_key])) {
        return $_SESSION[$cache_key];
    }
    
    // Call Google Translate
    $url = "https://translate.googleapis.com/translate_a/single?client=gtx&sl=" 
           . urlencode($source) 
           . "&tl=" . urlencode($target) 
           . "&dt=t&q=" . urlencode($text);
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');
    
    $response = curl_exec($ch);
    curl_close($ch);
    
    if ($response) {
        $result = json_decode($response, true);
        if (isset($result[0][0][0])) {
            $translated = $result[0][0][0];
            $_SESSION[$cache_key] = $translated;
            return $translated;
        }
    }
    
    return $text;
}

