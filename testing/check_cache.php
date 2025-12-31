<?php
require_once 'includes/db.php';

global $conDB;

// Check current cached translations
$result = mysqli_query($conDB, 'SELECT COUNT(*) as cnt FROM translation_cache');
$row = mysqli_fetch_assoc($result);
echo "Total cached translations in database: " . $row['cnt'] . "\n";

// Show recent translations
$recent = mysqli_query($conDB, 'SELECT source_text, target_lang, created_at FROM translation_cache ORDER BY created_at DESC LIMIT 5');
echo "\nRecent translations:\n";
while ($rec = mysqli_fetch_assoc($recent)) {
    echo "- Source: " . substr($rec['source_text'], 0, 50) . " | Target: " . $rec['target_lang'] . " | Created: " . $rec['created_at'] . "\n";
}
?>
