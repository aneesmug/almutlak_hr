<?php
require_once 'includes/db.php';

global $conDB;

echo "Direct Database Test\n";
echo "====================\n\n";

// Test 1: Check table structure
echo "1. Checking table structure...\n";
$columns = mysqli_query($conDB, "SHOW COLUMNS FROM translation_cache");
$col_count = 0;
while ($col = mysqli_fetch_assoc($columns)) {
    echo "   - {$col['Field']} ({$col['Type']})\n";
    $col_count++;
}
echo "Total columns: $col_count\n\n";

// Test 2: Direct INSERT test
echo "2. Testing direct INSERT...\n";
$test_hash = md5("test_ahmed");
$insert_query = "INSERT INTO translation_cache 
                (text_hash, source_text, source_lang, target_lang, translated_text, created_at)
                VALUES (
                  '$test_hash',
                  'Ahmed',
                  'en',
                  'ar',
                  'أحمد',
                  NOW()
                )
                ON DUPLICATE KEY UPDATE translated_text = VALUES(translated_text)";

echo "Query: " . substr($insert_query, 0, 100) . "...\n";
$result = mysqli_query($conDB, $insert_query);

if ($result) {
    echo "✓ INSERT successful!\n";
} else {
    echo "✗ INSERT failed: " . mysqli_error($conDB) . "\n";
}

// Test 3: Verify data was inserted
echo "\n3. Verifying data...\n";
$check = mysqli_query($conDB, "SELECT * FROM translation_cache WHERE text_hash = '$test_hash'");
if ($check && mysqli_num_rows($check) > 0) {
    $row = mysqli_fetch_assoc($check);
    echo "✓ Data found:\n";
    echo "  - Source: {$row['source_text']}\n";
    echo "  - Translated: {$row['translated_text']}\n";
    echo "  - Created: {$row['created_at']}\n";
} else {
    echo "✗ Data NOT found in database\n";
}

// Test 4: Count all records
echo "\n4. Total records in cache:\n";
$count_result = mysqli_query($conDB, "SELECT COUNT(*) as cnt FROM translation_cache");
$count_row = mysqli_fetch_assoc($count_result);
echo "Total: " . $count_row['cnt'] . " records\n";
?>
