<?php
/**
 * Resignation AJAX Error Diagnostics
 * 
 * This script helps identify why resignation submission is failing.
 * Access it directly in browser to run diagnostics.
 */

echo "<h2>Resignation Module Diagnostics</h2>";
echo "<hr>";

// 1. Check database connection
echo "<h3>1. Database Connection</h3>";
try {
    require_once __DIR__ . '/includes/db.php';
    if ($conDB) {
        echo "<p style='color: green;'>✓ Database connection successful</p>";
        
        // Check MySQL version
        $result = mysqli_query($conDB, "SELECT VERSION()");
        $row = mysqli_fetch_row($result);
        echo "<p>MySQL Version: " . $row[0] . "</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Database connection failed: " . $e->getMessage() . "</p>";
}

// 2. Check required tables
echo "<h3>2. Required Tables</h3>";
$tables = [
    'emp_resignations' => ['id', 'emp_id', 'request_inv_no', 'last_working_day', 'submission_date', 'status'],
    'emp_exit_interviews' => ['id', 'resignation_id', 'emp_id', 'q1_reasons', 'q2_support', 'q3_resources'],
    'employees' => ['emp_id', 'name', 'status'],
    'request_approvers' => ['id', 'request_inv_no', 'approver_id', 'approval_level', 'status'],
    'admin_login' => ['emp_id', 'user_type', 'status']
];

foreach ($tables as $tableName => $expectedCols) {
    $result = mysqli_query($conDB, "SHOW TABLES LIKE '$tableName'");
    if (mysqli_num_rows($result) > 0) {
        echo "<p style='color: green;'>✓ Table `$tableName` exists</p>";
        
        // Check columns
        $colResult = mysqli_query($conDB, "DESCRIBE `$tableName`");
        $foundCols = [];
        while ($row = mysqli_fetch_assoc($colResult)) {
            $foundCols[] = $row['Field'];
        }
        
        $missing = array_diff($expectedCols, $foundCols);
        if (empty($missing)) {
            echo "  - All required columns present</p>";
        } else {
            echo "  - <span style='color: red;'>Missing columns: " . implode(', ', $missing) . "</span></p>";
        }
    } else {
        echo "<p style='color: red;'>✗ Table `$tableName` NOT FOUND</p>";
    }
}

// 3. Check employee data
echo "<h3>3. Sample Employee Data</h3>";
$empResult = mysqli_query($conDB, "SELECT emp_id, name, status, supervisor_id FROM employees WHERE status = 1 AND emp_id = 5127");
if ($empRow = mysqli_fetch_assoc($empResult)) {
    echo "<p>Sample active employee:</p>";
    echo "<ul>";
    echo "<li>emp_id: " . $empRow['emp_id'] . "</li>";
    echo "<li>name: " . $empRow['name'] . "</li>";
    echo "<li>status: " . $empRow['status'] . " (1=active)</li>";
    echo "<li>supervisor_id: " . ($empRow['supervisor_id'] ?? 'NOT SET') . "</li>";
    echo "</ul>";
    
    if (empty($empRow['supervisor_id'])) {
        echo "<p style='color: orange;'>⚠ Employee has NO SUPERVISOR - Resignation will fail!</p>";
    }
} else {
    echo "<p style='color: red;'>✗ No active employees found in database</p>";
}

// 4. Check helper functions
echo "<h3>4. Helper Functions</h3>";
$helperFile = __DIR__ . '/includes/helper_functions.php';
if (file_exists($helperFile)) {
    echo "<p style='color: green;'>✓ helper_functions.php exists</p>";
    
    // Check for key functions
    require_once $helperFile;
    $functions = ['save_approval_comment_db', 'validate_employee_supervisor', 'handle_approval_action'];
    foreach ($functions as $func) {
        if (function_exists($func)) {
            echo "<p style='color: green;'>✓ Function `$func()` exists</p>";
        } else {
            echo "<p style='color: red;'>✗ Function `$func()` NOT FOUND</p>";
        }
    }
} else {
    echo "<p style='color: red;'>✗ helper_functions.php NOT FOUND</p>";
}

// 5. Check ActivityLogger
echo "<h3>5. ActivityLogger Class</h3>";
$loggerFile = __DIR__ . '/includes/activity_logger.php';
if (file_exists($loggerFile)) {
    echo "<p style='color: green;'>✓ activity_logger.php exists</p>";
    require_once $loggerFile;
    if (class_exists('ActivityLogger')) {
        echo "<p style='color: green;'>✓ ActivityLogger class exists</p>";
        
        // Check methods
        $methods = ['logSubmit', 'logApproval', 'logCreate'];
        foreach ($methods as $method) {
            if (method_exists('ActivityLogger', $method)) {
                echo "<p style='color: green;'>✓ ActivityLogger::$method() exists</p>";
            } else {
                echo "<p style='color: red;'>✗ ActivityLogger::$method() NOT FOUND</p>";
            }
        }
    } else {
        echo "<p style='color: red;'>✗ ActivityLogger class NOT FOUND</p>";
    }
} else {
    echo "<p style='color: red;'>✗ activity_logger.php NOT FOUND</p>";
}

// 6. Check PHP Error Log
echo "<h3>6. Recent PHP Errors</h3>";
$logFile = ini_get('error_log');
if ($logFile && file_exists($logFile)) {
    echo "<p>Error log location: <code>$logFile</code></p>";
    echo "<p>Last 20 lines of error log:</p>";
    echo "<pre style='background: #f0f0f0; padding: 10px; overflow-x: auto; max-height: 400px;'>";
    $lines = array_slice(file($logFile), -20);
    foreach ($lines as $line) {
        echo htmlspecialchars($line);
    }
    echo "</pre>";
} else {
    echo "<p style='color: orange;'>⚠ PHP error log not found or not configured</p>";
}

// 7. Recommendations
echo "<h3>7. Troubleshooting Steps</h3>";
echo "<ol>";
echo "<li>If database connection failed: Check <code>/includes/db.php</code> connection settings</li>";
echo "<li>If tables are missing: Run database migration scripts from <code>/sql/</code> directory</li>";
echo "<li>If employees have no supervisor: Add supervisor_id to employee records</li>";
echo "<li>If functions are missing: Check <code>/includes/</code> directory for complete files</li>";
echo "<li>Check PHP error log (shown above) for specific error messages</li>";
echo "<li>Enable DEBUG_MODE in config to see detailed error messages</li>";
echo "</ol>";

echo "<hr>";
echo "<p><strong>To view detailed AJAX errors:</strong></p>";
echo "<ol>";
echo "<li>Open browser Developer Tools (F12)</li>";
echo "<li>Go to Network tab</li>";
echo "<li>Perform the failing action</li>";
echo "<li>Find the failed AJAX request</li>";
echo "<li>Click it and view Response tab for error details</li>";
echo "<li>Also check PHP error log shown above</li>";
echo "</ol>";

?>
