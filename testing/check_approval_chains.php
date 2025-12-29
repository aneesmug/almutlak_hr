<?php
require_once __DIR__ . '/includes/db.php';

echo "<h2>Checking Approval Chain Configuration</h2>";

// Check app_settings table
echo "<h3>1. Current Approval Chains in app_settings:</h3>";
$query = mysqli_query($conDB, "SELECT setting_name, setting_value FROM app_settings WHERE setting_name LIKE '%approval_chain%' ORDER BY setting_name");

if ($query && mysqli_num_rows($query) > 0) {
    echo "<table border='1' cellpadding='10'>";
    echo "<tr><th>Setting Name</th><th>Configuration</th></tr>";
    while ($row = mysqli_fetch_assoc($query)) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['setting_name']) . "</td>";
        echo "<td><pre>" . htmlspecialchars($row['setting_value']) . "</pre></td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p style='color: red;'><strong>No approval chains configured!</strong></p>";
}
if ($query) mysqli_free_result($query);

// Check if resignation_request exists in approval_request_types
echo "<h3>2. Checking approval_request_types table:</h3>";
$query = mysqli_query($conDB, "SELECT id, type_name, description FROM approval_request_types WHERE type_name = 'resignation_request'");

if ($query && mysqli_num_rows($query) > 0) {
    $row = mysqli_fetch_assoc($query);
    echo "<p><strong>Found:</strong> ID=" . $row['id'] . ", Type=" . $row['type_name'] . ", Description=" . $row['description'] . "</p>";
} else {
    echo "<p style='color: red;'><strong>resignation_request NOT found in approval_request_types table!</strong></p>";
    
    // Show what's available
    echo "<p><strong>Available request types:</strong></p>";
    $allTypes = mysqli_query($conDB, "SELECT id, type_name FROM approval_request_types");
    if ($allTypes) {
        while ($type = mysqli_fetch_assoc($allTypes)) {
            echo "- " . $type['type_name'] . "<br>";
        }
        mysqli_free_result($allTypes);
    }
}
if ($query) mysqli_free_result($query);

// Check app_settings for resignation_request chain specifically
echo "<h3>3. Checking for resignation_request chain specifically:</h3>";
$query = mysqli_query($conDB, "SELECT setting_value FROM app_settings WHERE setting_name = 'approval_chain_resignation_request'");

if ($query && mysqli_num_rows($query) > 0) {
    $row = mysqli_fetch_assoc($query);
    $chain = json_decode($row['setting_value'], true);
    echo "<p><strong>Resignation Request Approval Chain:</strong></p>";
    if (empty($chain)) {
        echo "<p style='color: orange;'><strong>Chain is empty!</strong> Go to App Settings > Approval Chain to configure it.</p>";
    } else {
        echo "<pre>" . json_encode($chain, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "</pre>";
    }
} else {
    echo "<p style='color: red;'><strong>NO setting found for 'approval_chain_resignation_request'</strong></p>";
    echo "<p>You need to add this setting through the App Settings > Approval Chain configuration page.</p>";
}
if ($query) mysqli_free_result($query);

echo "<hr>";
echo "<p><a href='app_seetings.php' target='_blank'><strong>Go to App Settings to configure approval chains</strong></a></p>";
?>
