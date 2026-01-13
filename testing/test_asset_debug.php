<?php
// This test mimics what the browser would do
session_start();

// Simulate being logged in (for testing only)
if ($_GET['fake_login'] === '1') {
    $_SESSION['auth_user'] = [
        'user_id' => '1',
        'fullname' => 'Test User'
    ];
    header('Location: ?');
    exit;
}

if (!isset($_SESSION['auth_user'])) {
    echo "<h2>Not logged in. Testing requires authentication.</h2>";
    echo "<p><a href='?fake_login=1'>Click here to fake-login for testing</a></p>";
    exit;
}

// Now test the API
require_once __DIR__ . '/includes/session_check.php';
require_once __DIR__ . '/includes/init.php';
require_once __DIR__ . '/includes/helper_functions.php';

echo "<h1>Testing Asset Inventory API</h1>";
echo "<p>Logged in as: " . $_SESSION['auth_user']['user_id'] . "</p>";

// Check if PDO is available
echo "<p>PDO Status: " . (isset($pdo) ? "YES" : "NO") . "</p>";

if (isset($pdo)) {
    try {
        // Test the list_items query directly
        $stmt = $pdo->query("SELECT ai.id, ai.asset_id, ai.serial_number, ai.status, ai.assigned_emp_id, ai.assigned_date, ai.return_date, ai.description,
                                        a.name AS asset_name, e.name AS employee_name
                                 FROM asset_items ai
                                 LEFT JOIN assets a ON ai.asset_id = a.id
                                 LEFT JOIN employees e ON ai.assigned_emp_id = e.id
                                 ORDER BY ai.created_at DESC");
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<h2>Query Results</h2>";
        echo "<p>Number of asset items found: " . count($rows) . "</p>";
        
        if (count($rows) > 0) {
            echo "<table border='1' cellpadding='10'>";
            echo "<tr><th>ID</th><th>Asset Name</th><th>Serial</th><th>Status</th><th>Employee</th><th>Assigned Date</th></tr>";
            foreach ($rows as $row) {
                echo "<tr>";
                echo "<td>" . htmlspecialchars($row['id'] ?? '') . "</td>";
                echo "<td>" . htmlspecialchars($row['asset_name'] ?? '') . "</td>";
                echo "<td>" . htmlspecialchars($row['serial_number'] ?? '') . "</td>";
                echo "<td>" . htmlspecialchars($row['status'] ?? '') . "</td>";
                echo "<td>" . htmlspecialchars($row['employee_name'] ?? '') . "</td>";
                echo "<td>" . htmlspecialchars($row['assigned_date'] ?? '') . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "<p>No asset items found. This is normal if none have been created yet.</p>";
        }
    } catch (Exception $e) {
        echo "<h2>Error</h2>";
        echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
    }
}

// Also test the get_assets query
try {
    echo "<h2>Assets List</h2>";
    $stmt = $pdo->query("SELECT id, name FROM assets ORDER BY name ASC");
    $assets = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<p>Number of assets: " . count($assets) . "</p>";
    if (count($assets) > 0) {
        echo "<ul>";
        foreach ($assets as $asset) {
            echo "<li>" . htmlspecialchars($asset['name']) . " (ID: " . $asset['id'] . ")</li>";
        }
        echo "</ul>";
    }
} catch (Exception $e) {
    echo "<p>Error loading assets: " . htmlspecialchars($e->getMessage()) . "</p>";
}

// Test table existence
echo "<h2>Database Tables Check</h2>";
$tables = ['assets', 'asset_items', 'employee_assets'];
foreach ($tables as $table) {
    try {
        $stmt = $pdo->query("SELECT COUNT(*) FROM `" . $table . "`");
        $count = $stmt->fetchColumn();
        echo "<p>✓ Table '$table' exists with $count rows</p>";
    } catch (Exception $e) {
        echo "<p>✗ Table '$table' issue: " . htmlspecialchars($e->getMessage()) . "</p>";
    }
}

?>
