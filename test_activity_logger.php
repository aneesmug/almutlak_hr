<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Activity Logger - Test Page</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 1200px;
            margin: 50px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        h1 {
            color: #333;
            border-bottom: 3px solid #667eea;
            padding-bottom: 10px;
        }
        .test-section {
            background: #f8f9fa;
            padding: 20px;
            margin: 20px 0;
            border-left: 4px solid #667eea;
            border-radius: 4px;
        }
        .success {
            background: #d4edda;
            border-left-color: #28a745;
            color: #155724;
            padding: 15px;
            margin: 10px 0;
            border-radius: 4px;
        }
        .error {
            background: #f8d7da;
            border-left-color: #dc3545;
            color: #721c24;
            padding: 15px;
            margin: 10px 0;
            border-radius: 4px;
        }
        .btn {
            display: inline-block;
            padding: 12px 24px;
            background: #667eea;
            color: white;
            text-decoration: none;
            border-radius: 6px;
            margin: 5px;
            border: none;
            cursor: pointer;
            font-size: 14px;
        }
        .btn:hover {
            background: #5568d3;
        }
        .btn-success {
            background: #28a745;
        }
        .btn-danger {
            background: #dc3545;
        }
        pre {
            background: #2d3748;
            color: #68d391;
            padding: 15px;
            border-radius: 6px;
            overflow-x: auto;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 20px 0;
        }
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background: #667eea;
            color: white;
        }
        tr:hover {
            background: #f8f9fa;
        }
    </style>
</head>
<body>

<?php
require_once(__DIR__ . "/includes/init.php");
require_once(__DIR__ . "/includes/session_check.php");

$message = '';
$message_type = '';

// Test CREATE
if (isset($_POST['test_create'])) {
    $result = ActivityLogger::logCreate(
        'Test Module',
        'test_activity_logger.php',
        '12345',
        ['name' => 'Test Record', 'status' => 'active'],
        'Test CREATE action - Created test record #12345',
        'test_table'
    );
    
    $message = $result ? 'CREATE action logged successfully!' : 'Failed to log CREATE action';
    $message_type = $result ? 'success' : 'error';
}

// Test UPDATE
if (isset($_POST['test_update'])) {
    $result = ActivityLogger::logUpdate(
        'Test Module',
        'test_activity_logger.php',
        '12345',
        ['status' => 'pending', 'amount' => 1000],
        ['status' => 'approved', 'amount' => 1500],
        'Test UPDATE action - Updated record #12345',
        'test_table'
    );
    
    $message = $result ? 'UPDATE action logged successfully!' : 'Failed to log UPDATE action';
    $message_type = $result ? 'success' : 'error';
}

// Test DELETE
if (isset($_POST['test_delete'])) {
    $result = ActivityLogger::logDelete(
        'Test Module',
        'test_activity_logger.php',
        '12345',
        ['name' => 'Test Record', 'status' => 'active'],
        'Test DELETE action - Deleted record #12345',
        'test_table'
    );
    
    $message = $result ? 'DELETE action logged successfully!' : 'Failed to log DELETE action';
    $message_type = $result ? 'success' : 'error';
}

// Test LOGIN
if (isset($_POST['test_login'])) {
    $result = ActivityLogger::logLogin(
        $_SESSION['user_id'] ?? 'TEST_USER',
        $_SESSION['fname'] ?? 'Test User'
    );
    
    $message = $result ? 'LOGIN action logged successfully!' : 'Failed to log LOGIN action';
    $message_type = $result ? 'success' : 'error';
}

// Test APPROVE
if (isset($_POST['test_approve'])) {
    $result = ActivityLogger::logApproval(
        'Test Module',
        'test_activity_logger.php',
        '12345',
        'approved',
        'Test approval with comments',
        'test_table'
    );
    
    $message = $result ? 'APPROVE action logged successfully!' : 'Failed to log APPROVE action';
    $message_type = $result ? 'success' : 'error';
}

// Test EXPORT
if (isset($_POST['test_export'])) {
    $result = ActivityLogger::logExport(
        'Test Module',
        'test_activity_logger.php',
        'Exported test data',
        150
    );
    
    $message = $result ? 'EXPORT action logged successfully!' : 'Failed to log EXPORT action';
    $message_type = $result ? 'success' : 'error';
}

// Get recent test logs
$test_logs = ActivityLogger::getRecentActivity(10, 'Test Module');
?>

<div class="container">
    <h1>🧪 Activity Logger Test Page</h1>
    
    <?php if ($message): ?>
        <div class="<?= $message_type ?>">
            <strong><?= $message ?></strong>
        </div>
    <?php endif; ?>
    
    <div class="test-section">
        <h2>Test Actions</h2>
        <p>Click each button to test different logging actions. Check the logs below or in <a href="view_activity_logs.php">Activity Logs Admin</a>.</p>
        
        <form method="POST" style="margin: 20px 0;">
            <button type="submit" name="test_create" class="btn">🆕 Test CREATE</button>
            <button type="submit" name="test_update" class="btn">✏️ Test UPDATE</button>
            <button type="submit" name="test_delete" class="btn btn-danger">🗑️ Test DELETE</button>
            <button type="submit" name="test_login" class="btn">🔐 Test LOGIN</button>
            <button type="submit" name="test_approve" class="btn btn-success">✅ Test APPROVE</button>
            <button type="submit" name="test_export" class="btn">📥 Test EXPORT</button>
        </form>
    </div>
    
    <div class="test-section">
        <h2>Current Session Info</h2>
        <pre><?php 
            echo "User ID: " . ($_SESSION['user_id'] ?? $_SESSION['empid'] ?? 'Not set') . "\n";
            echo "User Name: " . ($_SESSION['fname'] ?? 'Not set') . "\n";
            echo "IP Address: " . ($_SERVER['REMOTE_ADDR'] ?? 'Unknown') . "\n";
            echo "User Agent: " . substr($_SERVER['HTTP_USER_AGENT'] ?? 'Unknown', 0, 80) . "...";
        ?></pre>
    </div>
    
    <div class="test-section">
        <h2>Recent Test Logs (Last 10)</h2>
        
        <?php if (empty($test_logs)): ?>
            <p>No test logs found. Click the buttons above to create test logs.</p>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Time</th>
                        <th>User</th>
                        <th>Action</th>
                        <th>Description</th>
                        <th>Record ID</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($test_logs as $log): ?>
                        <tr>
                            <td><?= $log['id'] ?></td>
                            <td><?= date('H:i:s', strtotime($log['created_at'])) ?></td>
                            <td>
                                <strong><?= htmlspecialchars($log['user_id']) ?></strong>
                                <?php if (!empty($log['user_name'])): ?>
                                    <br><small><?= htmlspecialchars($log['user_name']) ?></small>
                                <?php endif; ?>
                            </td>
                            <td><span style="background: #667eea; color: white; padding: 4px 8px; border-radius: 4px; font-size: 11px;"><?= $log['action_type'] ?></span></td>
                            <td><?= htmlspecialchars($log['description']) ?></td>
                            <td><?= htmlspecialchars($log['record_id'] ?? '-') ?></td>
                            <td><span style="background: #28a745; color: white; padding: 4px 8px; border-radius: 4px; font-size: 11px;"><?= $log['status'] ?></span></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
    
    <div class="test-section">
        <h2>Database Connection Test</h2>
        <pre><?php
            global $conDB;
            if (isset($conDB) && $conDB) {
                echo "✓ Database connection: OK\n";
                
                // Check if table exists
                $check = mysqli_query($conDB, "SHOW TABLES LIKE 'activity_log'");
                if (mysqli_num_rows($check) > 0) {
                    echo "✓ activity_log table: EXISTS\n";
                    
                    // Check table structure
                    $cols = mysqli_query($conDB, "SHOW COLUMNS FROM activity_log");
                    echo "\n📋 Table Columns:\n";
                    while ($col = mysqli_fetch_assoc($cols)) {
                        echo "   - " . $col['Field'] . " (" . $col['Type'] . ")\n";
                    }
                } else {
                    echo "✗ activity_log table: NOT FOUND\n";
                    echo "Please create the table using the schema provided.\n";
                }
            } else {
                echo "✗ Database connection: FAILED\n";
            }
        ?></pre>
    </div>
    
    <div style="text-align: center; margin-top: 30px;">
        <a href="view_activity_logs.php" class="btn btn-success">📊 View All Activity Logs</a>
        <a href="dashboard.php" class="btn">← Back to Dashboard</a>
    </div>
    
    <div style="margin-top: 30px; padding: 20px; background: #fff3cd; border-radius: 6px; border-left: 4px solid #ffc107;">
        <h3 style="color: #856404; margin-top: 0;">📚 Documentation</h3>
        <p>For complete documentation and integration examples, see <strong>ACTIVITY_LOGGING_GUIDE.md</strong></p>
        <p>Example integration can be found in <strong>manage_guide_screenshots.php</strong></p>
    </div>
</div>

</body>
</html>
