<?php
require_once 'includes/db.php';

global $conDB;

$result = mysqli_query($conDB, 'SELECT COUNT(*) as cnt, MAX(created_at) as latest FROM translation_cache');
$row = mysqli_fetch_assoc($result);

echo "Total translations cached: " . $row['cnt'] . "\n";
echo "Latest entry: " . $row['latest'] . "\n";

if ($row['cnt'] > 0) {
    $data = mysqli_query($conDB, 'SELECT source_text, translated_text, created_at FROM translation_cache ORDER BY created_at DESC LIMIT 5');
    echo "\nLast 5 entries:\n";
    while ($r = mysqli_fetch_assoc($data)) {
        echo "- {$r['source_text']} => {$r['translated_text']} ({$r['created_at']})\n";
    }
}
?>
