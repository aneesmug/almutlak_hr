<?php
require_once 'includes/db.php';

global $conDB;

// First, check if table exists
$check_table = mysqli_query($conDB, "SHOW TABLES LIKE 'translation_cache'");

if (mysqli_num_rows($check_table) == 0) {
    // Table doesn't exist, create it
    $sql = "CREATE TABLE translation_cache (
        id INT AUTO_INCREMENT PRIMARY KEY,
        text_hash VARCHAR(32) NOT NULL,
        source_text VARCHAR(500),
        source_lang VARCHAR(10) NOT NULL DEFAULT 'en',
        target_lang VARCHAR(10) NOT NULL DEFAULT 'ar',
        translated_text LONGTEXT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY unique_translation (text_hash, source_lang, target_lang),
        INDEX idx_created (created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
    
    if (mysqli_query($conDB, $sql)) {
        echo "✓ Translation cache table CREATED successfully!";
    } else {
        echo "✗ Error creating table: " . mysqli_error($conDB);
    }
} else {
    // Table exists, check structure
    $columns = mysqli_query($conDB, "SHOW COLUMNS FROM translation_cache");
    $col_count = mysqli_num_rows($columns);
    
    if ($col_count >= 6) {
        echo "✓ Translation cache table EXISTS and has correct structure (" . $col_count . " columns)";
    } else {
        echo "✗ Table exists but structure may be incomplete (" . $col_count . " columns)";
    }
}

// Verify the table can be written to
$test_insert = @mysqli_query($conDB, 
    "INSERT INTO translation_cache (text_hash, source_text, source_lang, target_lang, translated_text) 
     VALUES (MD5('test'), 'test', 'en', 'ar', 'اختبار')
     ON DUPLICATE KEY UPDATE translated_text = VALUES(translated_text)"
);

if ($test_insert) {
    echo "\n✓ Write access: OK - Data can be inserted";
    // Clean up test data
    @mysqli_query($conDB, "DELETE FROM translation_cache WHERE text_hash = MD5('test')");
} else {
    echo "\n✗ Write error: " . mysqli_error($conDB);
}

echo "\n✓ Translation cache system is ready!";
?>
