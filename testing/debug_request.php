<?php
// Disable session check for debugging
define('DEBUG_MODE', true);

require_once __DIR__ . '/includes/db.php';

// Try to get the database connection
if (!$conDB) {
    die("Database connection failed");
}

// Get database table info
echo "<h2>Database Tables</h2>";
$tables_result = mysqli_query($conDB, "SHOW TABLES LIKE '%general%'");
while ($table = mysqli_fetch_assoc($tables_result)) {
    $table_name = current($table);
    echo "<p><strong>$table_name</strong></p>";
    
    // Get columns
    $columns_result = mysqli_query($conDB, "DESCRIBE $table_name");
    echo "<ul>";
    while ($col = mysqli_fetch_assoc($columns_result)) {
        echo "<li>" . $col['Field'] . " (" . $col['Type'] . ")</li>";
    }
    echo "</ul>";
}

// Get a sample general request
echo "<h2>Sample Request Data</h2>";
$result = mysqli_query($conDB, "SELECT gr.* FROM general_requests gr LIMIT 1");

if ($result && mysqli_num_rows($result) > 0) {
    $request = mysqli_fetch_assoc($result);
    echo "<pre>";
    print_r($request);
    echo "</pre>";
} else {
    echo "No requests found in database";
}
?>

