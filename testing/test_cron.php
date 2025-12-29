<?php
/**
 * TEST FILE: Debug Cron Vacation Balance Update
 * This file helps test if the cron job is working correctly
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

date_default_timezone_set('Asia/Riyadh');

echo "<pre>";
echo "=== CRON VACATION BALANCE UPDATE - TEST ===\n\n";

// Step 1: Check if includes exist
echo "STEP 1: Checking required files...\n";
$required_files = [
    'includes/db.php',
    'includes/helper_functions.php',
    'includes/vacation_calculator.php'
];

foreach ($required_files as $file) {
    $path = __DIR__ . '/' . $file;
    if (file_exists($path)) {
        echo "  ✓ $file exists\n";
    } else {
        echo "  ✗ $file NOT FOUND\n";
    }
}

echo "\nSTEP 2: Testing database connection...\n";
try {
    require_once __DIR__ . '/includes/db.php';
    
    if ($conDB) {
        echo "  ✓ Database connection established\n";
        
        // Test query
        $test_query = "SELECT COUNT(*) as cnt FROM employees WHERE status = 1";
        $result = mysqli_query($conDB, $test_query);
        if ($result) {
            $row = mysqli_fetch_assoc($result);
            echo "  ✓ Active employees count: " . $row['cnt'] . "\n";
        } else {
            echo "  ✗ Query failed: " . mysqli_error($conDB) . "\n";
        }
    } else {
        echo "  ✗ Database connection failed\n";
    }
} catch (Exception $e) {
    echo "  ✗ Error: " . $e->getMessage() . "\n";
}

echo "\nSTEP 3: Checking vacation balance table...\n";
try {
    require_once __DIR__ . '/includes/db.php';
    
    $query = "SELECT COUNT(*) as cnt FROM emp_vacation_balance";
    $result = mysqli_query($conDB, $query);
    if ($result) {
        $row = mysqli_fetch_assoc($result);
        echo "  ✓ Total vacation balance records: " . $row['cnt'] . "\n";
        
        // Show sample
        $sample_query = "SELECT evb.emp_id, evb.available_balance, e.name 
                        FROM emp_vacation_balance evb
                        JOIN employees e ON evb.emp_id = e.emp_id
                        WHERE e.status = 1 LIMIT 5";
        $sample_result = mysqli_query($conDB, $sample_query);
        echo "  Sample records:\n";
        while ($row = mysqli_fetch_assoc($sample_result)) {
            echo "    - " . $row['emp_id'] . " (" . $row['name'] . "): " . $row['available_balance'] . " days\n";
        }
    } else {
        echo "  ✗ Query failed: " . mysqli_error($conDB) . "\n";
    }
} catch (Exception $e) {
    echo "  ✗ Error: " . $e->getMessage() . "\n";
}

echo "\nSTEP 4: Testing helper functions...\n";
try {
    require_once __DIR__ . '/includes/db.php';
    require_once __DIR__ . '/includes/helper_functions.php';
    
    if (function_exists('get_live_vacation_balance')) {
        echo "  ✓ get_live_vacation_balance function exists\n";
    } else {
        echo "  ✗ get_live_vacation_balance function NOT FOUND\n";
    }
} catch (Exception $e) {
    echo "  ✗ Error: " . $e->getMessage() . "\n";
}

echo "\nSTEP 5: Creating log directory...\n";
$log_dir = __DIR__ . '/cron_logs';
if (!is_dir($log_dir)) {
    mkdir($log_dir, 0755, true);
    echo "  ✓ Created cron_logs directory\n";
} else {
    echo "  ✓ cron_logs directory exists\n";
}

echo "\nSTEP 6: Checking log files...\n";
$log_file = $log_dir . '/vacation_balance_update_' . date('Y-m-d') . '.log';
if (file_exists($log_file)) {
    $size = filesize($log_file);
    $mtime = filemtime($log_file);
    echo "  ✓ Log file exists: " . date('Y-m-d H:i:s', $mtime) . " (Size: $size bytes)\n";
} else {
    echo "  ✗ Log file not found (will be created on first run)\n";
}

$report_file = $log_dir . '/last_vacation_update_report.json';
if (file_exists($report_file)) {
    $mtime = filemtime($report_file);
    echo "  ✓ Report file exists: " . date('Y-m-d H:i:s', $mtime) . "\n";
    $report = json_decode(file_get_contents($report_file), true);
    if ($report) {
        echo "    - Total employees: " . $report['total_employees'] . "\n";
        echo "    - Updated: " . $report['updated_count'] . "\n";
        echo "    - Changed: " . $report['changed_count'] . "\n";
        echo "    - Errors: " . $report['error_count'] . "\n";
        echo "    - Report timestamp: " . $report['timestamp'] . "\n";
    }
} else {
    echo "  ✗ Report file not found (will be created on first run)\n";
}

echo "\n=== TEST COMPLETE ===\n";
echo "</pre>";
?>
