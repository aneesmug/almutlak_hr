<?php
// Test the Asset Inventory API
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/includes/session_check.php';
require_once __DIR__ . '/includes/init.php';
require_once __DIR__ . '/includes/helper_functions.php';

// Check if pdo exists
echo "PDO exists: " . (isset($pdo) ? "YES" : "NO") . "<br>";

// Try to query the database
try {
    $stmt = $pdo->query("SELECT COUNT(*) AS cnt FROM employees");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "Employee count: " . $result['cnt'] . "<br>";
} catch (Exception $e) {
    echo "Error querying employees: " . $e->getMessage() . "<br>";
}

// Test listing assets
echo "<h2>Testing Asset Inventory API</h2>";

// Simulate the AJAX request
$_POST['action'] = 'list_items';

try {
    $stmt = $pdo->query("SELECT ai.id, ai.asset_id, ai.serial_number, ai.status, ai.assigned_emp_id, ai.assigned_date, ai.return_date, ai.description,
                                    a.name AS asset_name, e.name AS employee_name
                             FROM asset_items ai
                             LEFT JOIN assets a ON ai.asset_id = a.id
                             LEFT JOIN employees e ON ai.assigned_emp_id = e.id
                             ORDER BY ai.created_at DESC");
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<pre>";
    print_r($rows);
    echo "</pre>";
    echo "Total asset items: " . count($rows) . "<br>";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "<br>";
}

// Check if tables exist
$tables = ['assets', 'asset_items', 'employee_assets'];
foreach ($tables as $table) {
    try {
        $stmt = $pdo->query("SELECT COUNT(*) FROM `" . $table . "`");
        $count = $stmt->fetchColumn();
        echo "Table '$table' exists with $count rows<br>";
    } catch (Exception $e) {
        echo "Table '$table' does not exist or error: " . $e->getMessage() . "<br>";
    }
}
?>
