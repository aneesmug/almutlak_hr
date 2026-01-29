<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/session_check.php';

echo "=== ENDPOINT PATH TEST ===\n";
echo "Current file: " . __FILE__ . "\n";
echo "__DIR__: " . __DIR__ . "\n";

$paths = [
    'db.php via /../../db.php' => __DIR__ . '/includes/api/../../db.php',
    'Real file' => __DIR__ . '/db.php',
];

foreach ($paths as $name => $path) {
    echo "\n$name:\n";
    echo "  Path: $path\n";
    echo "  Real path: " . realpath($path) . "\n";
    echo "  Exists: " . (file_exists($path) ? 'YES' : 'NO') . "\n";
}

// Test if the settlement handler can be included
echo "\n=== SETTLEMENT HANDLER TEST ===\n";
if (file_exists(__DIR__ . '/includes/api/settlement_handler.php')) {
    echo "settlement_handler.php exists\n";
    // Try to include it (it will output JSON on error)
    ob_start();
    // Don't actually include to avoid output issues
    echo "File will be included on AJAX requests\n";
} else {
    echo "settlement_handler.php NOT FOUND\n";
}
?>
