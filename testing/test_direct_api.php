<?php
// Direct test of the AJAX endpoints
session_start();

// Simulate a logged-in user
if (!isset($_SESSION['auth_user'])) {
    $_SESSION['auth_user'] = [
        'user_id' => '1',
        'id' => '1'
    ];
}

echo "<h1>Direct AJAX Endpoint Test</h1>";

// Set up the POST action
$_POST['action'] = 'list_items';

// Capture output
ob_start();
try {
    include(__DIR__ . '/includes/ajaxFile/ajaxAssetInventory.php');
} catch (Throwable $e) {
    echo "Exception: " . $e->getMessage();
}
$output = ob_get_clean();

echo "<h2>API Response</h2>";
echo "<pre>";
echo htmlspecialchars($output);
echo "</pre>";

// Try to parse as JSON
echo "<h2>Parsed JSON</h2>";
$data = json_decode($output, true);
if (json_last_error() === JSON_ERROR_NONE) {
    echo "<pre>";
    print_r($data);
    echo "</pre>";
} else {
    echo "<p style='color: red;'>Failed to parse JSON: " . json_last_error_msg() . "</p>";
}

?>
