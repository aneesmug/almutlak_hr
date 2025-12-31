<?php
require_once 'includes/db.php';
require_once 'includes/session_check.php';
require_once 'includes/ajaxFile/translateText.php';

global $conDB;

echo "Testing Translation Cache System\n";
echo "================================\n\n";

// Test 1: Manual translation
echo "Test 1: Translating sample text...\n";
$test_texts = [
    "Ahmed",
    "Muhammad",
    "Saudi Arabia",
    "Human Resources"
];

foreach ($test_texts as $text) {
    echo "Translating: '$text' ... ";
    $translated = auto_translate_text($text, 'en', 'ar');
    echo "Result: '$translated'\n";
    sleep(1); // Delay between requests to avoid API rate limiting
}

echo "\n\nTest 2: Checking database cache...\n";
$result = mysqli_query($conDB, 'SELECT COUNT(*) as cnt FROM translation_cache');
$row = mysqli_fetch_assoc($result);
echo "Total cached translations: " . $row['cnt'] . "\n";

if ($row['cnt'] > 0) {
    echo "\nCached translations:\n";
    $recent = mysqli_query($conDB, 'SELECT source_text, translated_text, created_at FROM translation_cache ORDER BY created_at DESC LIMIT 10');
    while ($rec = mysqli_fetch_assoc($recent)) {
        echo "- Source: '{$rec['source_text']}' => '{$rec['translated_text']}' (Created: {$rec['created_at']})\n";
    }
} else {
    echo "⚠ No translations in database yet\n";
}

echo "\n✓ Test complete!\n";
?>
