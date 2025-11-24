<?php
/**
 * Setup Script - Creates user_activity_log table
 * Run this file once to set up the user activity tracking system
 */

require_once __DIR__ . '/includes/db.php';

echo "<!DOCTYPE html>
<html>
<head>
    <title>User Activity Setup</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 50px auto; padding: 20px; }
        .success { color: green; background: #d4edda; padding: 15px; border-radius: 5px; margin: 10px 0; }
        .error { color: red; background: #f8d7da; padding: 15px; border-radius: 5px; margin: 10px 0; }
        .info { color: blue; background: #d1ecf1; padding: 15px; border-radius: 5px; margin: 10px 0; }
        h1 { color: #333; }
        code { background: #f4f4f4; padding: 2px 5px; border-radius: 3px; }
    </style>
</head>
<body>
    <h1>User Activity Tracking System Setup</h1>";

// SQL to create the table
$sql = "CREATE TABLE IF NOT EXISTS `user_activity_log` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `user_id` INT(11) NOT NULL,
  `emp_id` VARCHAR(50) DEFAULT NULL,
  `username` VARCHAR(255) DEFAULT NULL,
  `login_time` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `logout_time` DATETIME DEFAULT NULL,
  `ip_address` VARCHAR(45) DEFAULT NULL,
  `country` VARCHAR(100) DEFAULT NULL,
  `country_code` VARCHAR(10) DEFAULT NULL,
  `region` VARCHAR(100) DEFAULT NULL,
  `city` VARCHAR(100) DEFAULT NULL,
  `latitude` DECIMAL(10, 8) DEFAULT NULL,
  `longitude` DECIMAL(11, 8) DEFAULT NULL,
  `timezone` VARCHAR(50) DEFAULT NULL,
  `isp` VARCHAR(255) DEFAULT NULL,
  `browser` VARCHAR(100) DEFAULT NULL,
  `browser_version` VARCHAR(50) DEFAULT NULL,
  `os` VARCHAR(100) DEFAULT NULL,
  `os_version` VARCHAR(50) DEFAULT NULL,
  `device_type` VARCHAR(50) DEFAULT NULL COMMENT 'Desktop, Mobile, Tablet',
  `screen_width` INT(11) DEFAULT NULL,
  `screen_height` INT(11) DEFAULT NULL,
  `user_agent` TEXT DEFAULT NULL,
  `session_id` VARCHAR(255) DEFAULT NULL,
  `status` ENUM('active', 'logged_out', 'timeout') DEFAULT 'active',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_emp_id` (`emp_id`),
  KEY `idx_login_time` (`login_time`),
  KEY `idx_ip_address` (`ip_address`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

try {
    // Check if table already exists
    $check = mysqli_query($conDB, "SHOW TABLES LIKE 'user_activity_log'");
    
    if (mysqli_num_rows($check) > 0) {
        echo "<div class='info'>✓ Table <code>user_activity_log</code> already exists. No action needed.</div>";
    } else {
        // Create the table
        if (mysqli_query($conDB, $sql)) {
            echo "<div class='success'>✓ Successfully created table <code>user_activity_log</code></div>";
        } else {
            throw new Exception(mysqli_error($conDB));
        }
    }
    
    // Verify table structure
    $columns = mysqli_query($conDB, "SHOW COLUMNS FROM user_activity_log");
    $columnCount = mysqli_num_rows($columns);
    
    echo "<div class='success'>✓ Table verified with {$columnCount} columns</div>";
    
    // Check for indexes
    $indexes = mysqli_query($conDB, "SHOW INDEXES FROM user_activity_log");
    $indexCount = mysqli_num_rows($indexes);
    
    echo "<div class='success'>✓ Table has {$indexCount} indexes for optimal performance</div>";
    
    echo "<div class='success'>
        <h3>Setup Complete! ✓</h3>
        <p>The user activity tracking system is ready to use.</p>
        <ul>
            <li>Activity logging will start automatically on next user login</li>
            <li>Access the dashboard at: <a href='user_activity.php'>user_activity.php</a></li>
            <li>Screen resolution tracking requires JavaScript (automatically handled)</li>
        </ul>
        <p><strong>Security Note:</strong> Delete or restrict access to this setup file after installation.</p>
    </div>";
    
} catch (Exception $e) {
    echo "<div class='error'>
        <h3>Error During Setup</h3>
        <p><strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "</p>
        <p>Please check your database connection and permissions.</p>
    </div>";
}

mysqli_close($conDB);

echo "</body></html>";
?>
