<?php
require_once 'includes/db.php';

global $conDB;

$sql = "CREATE TABLE IF NOT EXISTS translation_cache (
    id INT AUTO_INCREMENT PRIMARY KEY,
    text_hash VARCHAR(32) NOT NULL,
    source_text VARCHAR(500),
    source_lang VARCHAR(10) NOT NULL DEFAULT 'en',
    target_lang VARCHAR(10) NOT NULL DEFAULT 'ar',
    translated_text TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_translation (text_hash, source_lang, target_lang),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

if (mysqli_query($conDB, $sql)) {
    echo "✓ Translation cache table created successfully!";
} else {
    echo "Error: " . mysqli_error($conDB);
}
?>
