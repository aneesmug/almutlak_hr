<?php
/**
 * Migration Script: Add Language Support to Screenshots
 * This script adds a 'language' column to the guide_screenshots table
 */

require_once(__DIR__ . "/includes/init.php");

try {
    // Check if column already exists
    $check = $pdo->query("SHOW COLUMNS FROM guide_screenshots LIKE 'language'");
    
    if ($check->rowCount() == 0) {
        // Add language column
        $pdo->exec("
            ALTER TABLE guide_screenshots 
            ADD COLUMN language VARCHAR(5) DEFAULT 'en' AFTER title,
            ADD INDEX idx_language (language),
            ADD INDEX idx_section_step_lang (section, step_number, language)
        ");
        
        echo "✅ SUCCESS: Language column added to guide_screenshots table\n";
        echo "📋 Indexes created for better performance\n";
        echo "🌐 Default language set to 'en' (English)\n";
        echo "\n";
        echo "Next steps:\n";
        echo "1. Upload Arabic screenshots via manage_guide_screenshots.php\n";
        echo "2. System will automatically show correct language based on user's language setting\n";
    } else {
        echo "ℹ️ INFO: Language column already exists\n";
    }
    
} catch (PDOException $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    exit(1);
}
