<?php
// Test file to check ActivityLogger and database connectivity

require 'includes/db.php';

echo "=== Database Connection Test ===\n";
if ($conDB) {
    echo "✓ Database connected\n";
} else {
    echo "✗ Database connection failed\n";
    exit;
}

echo "\n=== Check activity_log Table ===\n";
$result = mysqli_query($conDB, "DESCRIBE activity_log");
if ($result) {
    $columns = mysqli_fetch_all($result, MYSQLI_ASSOC);
    echo "✓ activity_log table exists with " . count($columns) . " columns:\n";
    foreach ($columns as $col) {
        echo "  - " . $col['Field'] . " (" . $col['Type'] . ")\n";
    }
} else {
    echo "✗ Error: " . mysqli_error($conDB) . "\n";
}

echo "\n=== Check ActivityLogger Class ===\n";
require 'includes/init.php';

if (class_exists('ActivityLogger')) {
    echo "✓ ActivityLogger class loaded\n";
    
    if (method_exists('ActivityLogger', 'logDelete')) {
        echo "✓ logDelete method exists\n";
    } else {
        echo "✗ logDelete method not found\n";
    }
} else {
    echo "✗ ActivityLogger class not found\n";
}

echo "\n=== Test logDelete Call ===\n";
$test_data = [
    ['id' => 1, 'key' => 'test_key', 'lang' => 'en', 'value' => 'Test Value']
];

try {
    $result = ActivityLogger::logDelete('System', 'test_logging_error.php', 0, $test_data, 'Test delete', 'translations');
    if ($result) {
        echo "✓ logDelete executed successfully\n";
    } else {
        echo "✗ logDelete returned false\n";
    }
} catch (Exception $e) {
    echo "✗ Exception: " . $e->getMessage() . "\n";
}

echo "\n=== Test Database Insert for activity_log ===\n";
$test_insert = "INSERT INTO activity_log (user_id, user_name, module, page, action_type, description, record_id, table_name, created_at) 
    VALUES ('TEST', 'Test User', 'System', 'test.php', 'DELETE', 'Test deletion', '0', 'translations', NOW())";

if (mysqli_query($conDB, $test_insert)) {
    echo "✓ Direct INSERT into activity_log succeeded\n";
    $last_id = mysqli_insert_id($conDB);
    echo "  Last insert ID: " . $last_id . "\n";
} else {
    echo "✗ Direct INSERT failed: " . mysqli_error($conDB) . "\n";
}

mysqli_close($conDB);
?>
