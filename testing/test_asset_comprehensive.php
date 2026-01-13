<?php
/**
 * Comprehensive test of Asset Inventory AJAX API
 * This test mimics a browser request to debug data loading issues
 */

session_start();

// Simulate a logged-in user with proper session data
if (!isset($_SESSION['auth_user'])) {
    $_SESSION['auth_user'] = [
        'user_id' => 'test_user'
    ];
}

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Asset Inventory API Test</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .test-section { margin: 20px 0; padding: 15px; border: 1px solid #ddd; border-radius: 5px; }
        .success { color: green; }
        .error { color: red; }
        .warning { color: orange; }
        pre { background: #f5f5f5; padding: 10px; overflow-x: auto; }
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background: #f0f0f0; }
    </style>
</head>
<body>
    <h1>Asset Inventory AJAX API Test</h1>
    <p>Session User: <?php echo $_SESSION['auth_user']['user_id']; ?></p>

    <?php
    // Test 1: Check if PDO is available
    echo '<div class="test-section">';
    echo '<h2>Test 1: Database Connection</h2>';
    try {
        require_once __DIR__ . '/includes/session_check.php';
        require_once __DIR__ . '/includes/init.php';
        require_once __DIR__ . '/includes/db.php';
        
        if (isset($pdo)) {
            echo '<p class="success">✓ PDO connection established</p>';
        } else {
            echo '<p class="error">✗ PDO not available</p>';
        }
    } catch (Exception $e) {
        echo '<p class="error">✗ Error: ' . htmlspecialchars($e->getMessage()) . '</p>';
    }
    echo '</div>';

    // Test 2: Check if tables exist
    echo '<div class="test-section">';
    echo '<h2>Test 2: Database Tables</h2>';
    $tables = ['assets', 'asset_items', 'employee_assets', 'employees'];
    foreach ($tables as $table) {
        try {
            $stmt = $pdo->query("SELECT COUNT(*) AS cnt FROM `" . $table . "`");
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $count = $result['cnt'] ?? 0;
            echo "<p class='success'>✓ Table '$table' exists with $count rows</p>";
        } catch (Exception $e) {
            echo "<p class='error'>✗ Table '$table' error: " . htmlspecialchars($e->getMessage()) . "</p>";
        }
    }
    echo '</div>';

    // Test 3: Direct query test for asset_items
    echo '<div class="test-section">';
    echo '<h2>Test 3: Asset Items Query</h2>';
    try {
        $stmt = $pdo->query("SELECT ai.id, ai.asset_id, ai.serial_number, ai.status, ai.assigned_emp_id, ai.assigned_date,
                                    a.name AS asset_name, e.name AS employee_name
                             FROM asset_items ai
                             LEFT JOIN assets a ON ai.asset_id = a.id
                             LEFT JOIN employees e ON ai.assigned_emp_id = e.id
                             ORDER BY ai.created_at DESC");
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "<p class='success'>✓ Query executed successfully</p>";
        echo "<p>Total rows: " . count($rows) . "</p>";
        
        if (count($rows) > 0) {
            echo '<table>';
            echo '<tr><th>ID</th><th>Asset</th><th>Serial</th><th>Status</th><th>Employee</th><th>Assigned Date</th></tr>';
            foreach ($rows as $row) {
                echo '<tr>';
                echo '<td>' . htmlspecialchars($row['id']) . '</td>';
                echo '<td>' . htmlspecialchars($row['asset_name'] ?? '') . '</td>';
                echo '<td>' . htmlspecialchars($row['serial_number']) . '</td>';
                echo '<td>' . htmlspecialchars($row['status']) . '</td>';
                echo '<td>' . htmlspecialchars($row['employee_name'] ?? '') . '</td>';
                echo '<td>' . htmlspecialchars($row['assigned_date'] ?? '') . '</td>';
                echo '</tr>';
            }
            echo '</table>';
        } else {
            echo '<p class="warning">No asset items found (this is OK if none have been created)</p>';
        }
    } catch (Exception $e) {
        echo '<p class="error">✗ Query error: ' . htmlspecialchars($e->getMessage()) . '</p>';
    }
    echo '</div>';

    // Test 4: Test the AJAX endpoint directly
    echo '<div class="test-section">';
    echo '<h2>Test 4: AJAX Endpoint Simulation</h2>';
    
    $_POST['action'] = 'list_items';
    
    ob_start();
    try {
        // Include the AJAX handler
        include __DIR__ . '/includes/ajaxFile/ajaxAssetInventory.php';
    } catch (Exception $e) {
        echo '<p class="error">✗ Exception: ' . htmlspecialchars($e->getMessage()) . '</p>';
    }
    $ajax_output = ob_get_clean();
    
    if (!empty($ajax_output)) {
        echo '<p>Raw AJAX Response:</p>';
        echo '<pre>' . htmlspecialchars($ajax_output) . '</pre>';
        
        $json_data = json_decode($ajax_output, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            echo '<p class="success">✓ Valid JSON response</p>';
            if ($json_data['success'] ?? false) {
                echo '<p class="success">✓ success flag is true</p>';
                $items_count = count($json_data['data']['items'] ?? []);
                echo '<p>Items returned: ' . $items_count . '</p>';
            } else {
                echo '<p class="error">✗ success flag is false: ' . htmlspecialchars($json_data['message'] ?? 'Unknown error') . '</p>';
            }
        } else {
            echo '<p class="error">✗ Invalid JSON: ' . json_last_error_msg() . '</p>';
        }
    } else {
        echo '<p class="error">✗ No output from AJAX endpoint</p>';
    }
    echo '</div>';

    // Test 5: Assets list
    echo '<div class="test-section">';
    echo '<h2>Test 5: Assets List</h2>';
    try {
        $stmt = $pdo->query("SELECT id, name FROM assets ORDER BY name ASC");
        $assets = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "<p>Total assets: " . count($assets) . "</p>";
        if (count($assets) > 0) {
            echo '<ul>';
            foreach ($assets as $asset) {
                echo '<li>' . htmlspecialchars($asset['name']) . ' (ID: ' . $asset['id'] . ')</li>';
            }
            echo '</ul>';
        } else {
            echo '<p class="warning">No assets defined</p>';
        }
    } catch (Exception $e) {
        echo '<p class="error">✗ Error: ' . htmlspecialchars($e->getMessage()) . '</p>';
    }
    echo '</div>';

    ?>

</body>
</html>
