/**
 * Translation Utility Functions for JavaScript
 * Provides functions to translate text dynamically using Google Translate API
 * 
 * Usage:
 * - Single translation: translateName("Ahmed", "en", "ar")
 * - With language auto-detect: translateName("Ahmed", getCurrentLanguage())
 * - With callback: translateName("Ahmed", getCurrentLanguage(), function(translated) { console.log(translated); })
 */

/**
 * Cache object for storing translations in the browser session
 * This reduces API calls significantly
 */
const translationCache = {};

/**
 * Get current language from HTML or document
 * @returns {string} Language code ('en' or 'ar')
 */
function getCurrentLanguage() {
    // Check HTML lang attribute
    const htmlLang = document.documentElement.getAttribute('lang');
    if (htmlLang) return htmlLang;
    
    // Check if Arabic class exists on body
    if (document.body.classList.contains('arabic')) return 'ar';
    
    // Check if RTL is set
    if (document.documentElement.getAttribute('dir') === 'rtl') return 'ar';
    
    // Default to English
    return 'en';
}

/**
 * Generate cache key from text and language combination
 * @param {string} text - Text to translate
 * @param {string} source - Source language code
 * @param {string} target - Target language code
 * @returns {string} MD5-like cache key
 */
function getCacheKey(text, source, target) {
    return `translation_${text}_${source}_${target}`;
}

/**
 * Main translation function - Translates text using Google Translate API
 * Can be used synchronously or asynchronously
 * 
 * @param {string} text - Text to translate
 * @param {string} sourceOrCurrentLang - Source language or current language code
 * @param {string|function} targetOrCallback - Target language or callback function
 * @param {function} callback - Optional callback function for async operation
 * @returns {string|Promise} Translated text (sync) or Promise (async with callback)
 * 
 * Examples:
 * // Sync (when result is cached)
 * const translated = translateName("Ahmed", "en", "ar");
 * 
 * // Async with callback
 * translateName("Ahmed", "en", "ar", function(translated) {
 *     console.log(translated); // "أحمد"
 * });
 * 
 * // Auto language detection
 * const currentLang = getCurrentLanguage();
 * translateName("Ahmed", currentLang, function(translated) {
 *     if (currentLang === 'ar') {
 *         console.log("Arabic:", translated);
 *     } else {
 *         console.log("English:", "Ahmed");
 *     }
 * });
 */
function translateName(text, sourceOrCurrentLang, targetOrCallback, callback) {
    // Validate input
    if (!text || typeof text !== 'string') {
        return text;
    }
    
    // Handle parameter overloading
    let source = 'en';
    let target = 'ar';
    let callbackFn = null;
    
    // Parse parameters based on their types
    if (typeof sourceOrCurrentLang === 'string') {
        source = sourceOrCurrentLang;
    }
    
    if (typeof targetOrCallback === 'string') {
        target = targetOrCallback;
    } else if (typeof targetOrCallback === 'function') {
        callbackFn = targetOrCallback;
    }
    
    if (typeof callback === 'function') {
        callbackFn = callback;
    }
    
    // If target language is not Arabic, return original text
    if (target !== 'ar') {
        if (callbackFn) {
            callbackFn(text);
        }
        return text;
    }

    // Check cache first
    const cacheKey = getCacheKey(text, source, target);
    if (translationCache[cacheKey]) {
        const cached = translationCache[cacheKey];
        if (callbackFn) {
            callbackFn(cached);
        }
        return cached;
    }

    // If callback is provided, make async request
    if (callbackFn) {
        makeTranslationRequest(text, source, target, callbackFn);
        return;
    }

    // If not in cache and no callback, return original text (sync mode)
    return text;
}

/**
 * Make AJAX request to translateText.php endpoint
 * @private
 * @param {string} text - Text to translate
 * @param {string} source - Source language code
 * @param {string} target - Target language code
 * @param {function} callback - Callback function with translated text
 */
function makeTranslationRequest(text, source, target, callback) {
    // Use jQuery if available, otherwise use Fetch API
    if (typeof jQuery !== 'undefined' && typeof jQuery.ajax === 'function') {
        makeTranslationRequestJQuery(text, source, target, callback);
    } else {
        makeTranslationRequestFetch(text, source, target, callback);
    }
}

/**
 * Make translation request using jQuery AJAX
 * @private
 */
function makeTranslationRequestJQuery(text, source, target, callback) {
    jQuery.ajax({
        url: './includes/ajaxFile/translateText.php',
        type: 'POST',
        dataType: 'json',
        data: {
            text: text,
            source: source,
            target: target
        },
        timeout: 5000,
        success: function(response) {
            if (response.success && response.translation) {
                const cacheKey = getCacheKey(text, source, target);
                translationCache[cacheKey] = response.translation;
                callback(response.translation);
            } else {
                console.warn('Translation failed:', response.error || 'Unknown error');
                callback(text); // Return original text on failure
            }
        },
        error: function(xhr, status, error) {
            console.error('Translation request error:', error);
            callback(text); // Return original text on error
        }
    });
}

/**
 * Make translation request using Fetch API
 * @private
 */
function makeTranslationRequestFetch(text, source, target, callback) {
    const formData = new FormData();
    formData.append('text', text);
    formData.append('source', source);
    formData.append('target', target);
    
    fetch('./includes/ajaxFile/translateText.php', {
        method: 'POST',
        body: formData,
        timeout: 5000
    })
    .then(response => response.json())
    .then(data => {
        if (data.success && data.translation) {
            const cacheKey = getCacheKey(text, source, target);
            translationCache[cacheKey] = data.translation;
            callback(data.translation);
        } else {
            console.warn('Translation failed:', data.error || 'Unknown error');
            callback(text); // Return original text on failure
        }
    })
    .catch(error => {
        console.error('Translation request error:', error);
        callback(text); // Return original text on error
    });
}

/**
 * Translate multiple elements on the page
 * Finds all elements with data-translate attribute and translates them
 * 
 * Usage in HTML:
 * <span data-translate="ar">Ahmed</span>
 * 
 * Then call:
 * translatePageElements();
 */
function translatePageElements() {
    const currentLang = getCurrentLanguage();
    
    if (currentLang !== 'ar') {
        return; // Only translate to Arabic
    }
    
    // Find all elements with data-translate attribute
    const elements = document.querySelectorAll('[data-translate]');
    
    elements.forEach(element => {
        const originalText = element.getAttribute('data-original') || element.textContent;
        const targetLang = element.getAttribute('data-translate');
        
        // Store original text for reference
        element.setAttribute('data-original', originalText);
        
        // Translate the text
        translateName(originalText, 'en', targetLang, function(translated) {
            element.textContent = translated;
        });
    });
}

/**
 * Clear translation cache
 * Useful when user changes language or for debugging
 */
function clearTranslationCache() {
    for (const key in translationCache) {
        delete translationCache[key];
    }
    console.log('Translation cache cleared');
}

/**
 * Get cache statistics for debugging
 * @returns {object} Cache statistics
 */
function getTranslationCacheStats() {
    return {
        itemsCount: Object.keys(translationCache).length,
        items: translationCache,
        totalSize: JSON.stringify(translationCache).length
    };
}

/**
 * Language change handler
 * Call this when user changes the application language
 * 
 * Usage:
 * onLanguageChange('ar');
 */
function onLanguageChange(newLanguage) {
    // Update HTML lang attribute
    document.documentElement.setAttribute('lang', newLanguage);
    
    // Update RTL if needed
    if (newLanguage === 'ar') {
        document.documentElement.setAttribute('dir', 'rtl');
        document.body.classList.add('rtl');
        document.body.classList.remove('ltr');
    } else {
        document.documentElement.setAttribute('dir', 'ltr');
        document.body.classList.add('ltr');
        document.body.classList.remove('rtl');
    }
    
    // Clear cache for new language
    clearTranslationCache();
    
    // Re-translate page elements if they exist
    translatePageElements();
}

// Auto-initialize on document ready if jQuery is available
if (typeof jQuery !== 'undefined') {
    jQuery(document).ready(function() {
        // Optionally auto-translate page elements on load
        // Uncomment if you want automatic translation of elements with data-translate attribute
        // translatePageElements();
    });
} else if (document.readyState === 'loading') {
    // For vanilla JS, wait for DOM to load
    document.addEventListener('DOMContentLoaded', function() {
        // Uncomment if you want automatic translation of elements with data-translate attribute
        // translatePageElements();
    });
}
