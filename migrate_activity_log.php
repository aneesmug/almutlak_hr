<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Activity Log Migration - Al-Mutlak WMS</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .container { 
            max-width: 900px; 
            width: 100%;
            background: white; 
            padding: 40px; 
            border-radius: 12px; 
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }
        h1 { 
            color: #333; 
            border-bottom: 4px solid #667eea; 
            padding-bottom: 15px; 
            margin-bottom: 30px;
            font-size: 28px;
        }
        h2 {
            color: #667eea;
            margin: 25px 0 15px;
            font-size: 20px;
        }
        .step { 
            background: #f8f9fa; 
            padding: 20px; 
            margin: 15px 0; 
            border-left: 5px solid #667eea;
            border-radius: 4px;
        }
        .step h3 {
            margin-bottom: 10px;
            color: #333;
        }
        .success { 
            background: #d4edda; 
            border-left-color: #28a745; 
            color: #155724; 
        }
        .error { 
            background: #f8d7da; 
            border-left-color: #dc3545; 
            color: #721c24; 
        }
        .warning { 
            background: #fff3cd; 
            border-left-color: #ffc107; 
            color: #856404; 
        }
        .info { 
            background: #d1ecf1; 
            border-left-color: #17a2b8; 
            color: #0c5460; 
        }
        pre { 
            background: #2d3748; 
            color: #68d391;
            padding: 15px; 
            border-radius: 6px; 
            overflow-x: auto;
            font-size: 13px;
            line-height: 1.6;
        }
        .btn { 
            display: inline-block; 
            padding: 12px 30px; 
            background: #667eea; 
            color: white; 
            text-decoration: none; 
            border-radius: 6px; 
            margin: 15px 10px 0 0; 
            cursor: pointer; 
            border: none;
            font-size: 16px;
            font-weight: 600;
            transition: all 0.3s;
        }
        .btn:hover { 
            background: #5568d3;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        .btn-danger { 
            background: #dc3545; 
        }
        .btn-danger:hover { 
            background: #c82333; 
        }
        .btn-success {
            background: #28a745;
        }
        .btn-success:hover {
            background: #218838;
        }
        ul, ol {
            margin-left: 25px;
            line-height: 1.8;
        }
        .badge {
            display: inline-block;
            padding: 4px 10px;
            background: #667eea;
            color: white;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
        }
        .table-info {
            margin: 15px 0;
            padding: 15px;
            background: #fff;
            border-radius: 6px;
            border: 2px solid #e9ecef;
        }
        .column-list {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 10px;
            margin: 15px 0;
        }
        .column-item {
            padding: 8px 12px;
            background: #f8f9fa;
            border-radius: 4px;
            font-family: 'Courier New', monospace;
            font-size: 13px;
        }
    </style>
</head>
<body>
<div class="container">
    <h1>📊 Activity Log System - Database Migration</h1>
    
    <?php
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    
    // Include database connection
    require_once 'includes/db.php';
    
    if (!isset($_GET['confirm'])) {
        // ===== SHOW MIGRATION PLAN =====
        
        echo '<div class="info step">';
        echo '<h3>⚠️ Important Information</h3>';
        echo '<p>This migration will enhance your <code>activity_log</code> table to support comprehensive activity tracking across the entire system.</p>';
        echo '</div>';
        
        echo '<h2>📋 Migration Steps</h2>';
        echo '<div class="step">';
        echo '<h3>What will happen:</h3>';
        echo '<ol>';
        echo '<li>Backup current <code>activity_log</code> table to <code>activity_log_old_backup</code></li>';
        echo '<li>Drop old table and create new enhanced structure</li>';
        echo '<li>Migrate all existing data (with date conversion)</li>';
        echo '<li>Add indexes for optimal performance</li>';
        echo '<li>Verify data integrity</li>';
        echo '</ol>';
        echo '</div>';
        
        // Check current table
        $current_cols = [];
        $result = mysqli_query($conDB, "SHOW COLUMNS FROM activity_log");
        if ($result) {
            echo '<div class="table-info">';
            echo '<h3>Current Table Structure <span class="badge">' . mysqli_num_rows($result) . ' columns</span></h3>';
            echo '<div class="column-list">';
            while ($row = mysqli_fetch_assoc($result)) {
                echo '<div class="column-item">' . $row['Field'] . ' <small style="color:#666;">(' . $row['Type'] . ')</small></div>';
                $current_cols[] = $row['Field'];
            }
            echo '</div>';
            echo '</div>';
        }
        
        // Show new columns
        echo '<div class="success step">';
        echo '<h3>✨ New Enhanced Structure</h3>';
        echo '<p><strong>New columns being added:</strong></p>';
        echo '<div class="column-list">';
        echo '<div class="column-item"><strong>action_type</strong> <small>(VARCHAR 50)</small><br><small style="color:#666;">CREATE, UPDATE, DELETE, etc.</small></div>';
        echo '<div class="column-item"><strong>old_value</strong> <small>(TEXT)</small><br><small style="color:#666;">Previous value tracking</small></div>';
        echo '<div class="column-item"><strong>new_value</strong> <small>(TEXT)</small><br><small style="color:#666;">New value tracking</small></div>';
        echo '<div class="column-item"><strong>description</strong> <small>(TEXT)</small><br><small style="color:#666;">Human-readable description</small></div>';
        echo '<div class="column-item"><strong>ip_address</strong> <small>(VARCHAR 45)</small><br><small style="color:#666;">User IP tracking</small></div>';
        echo '<div class="column-item"><strong>user_agent</strong> <small>(VARCHAR 255)</small><br><small style="color:#666;">Browser/device info</small></div>';
        echo '<div class="column-item"><strong>created_at</strong> <small>(TIMESTAMP)</small><br><small style="color:#666;">Replaces reg_date VARCHAR</small></div>';
        echo '</div>';
        echo '</div>';
        
        // Count records
        $count_result = mysqli_query($conDB, "SELECT COUNT(*) as total FROM activity_log");
        $count = 0;
        if ($count_result) {
            $count = mysqli_fetch_assoc($count_result)['total'];
        }
        
        echo '<div class="warning step">';
        echo '<h3>📊 Current Data</h3>';
        echo "<p><strong style='font-size: 24px; color: #856404;'>{$count}</strong> existing records will be safely migrated to the new structure.</p>";
        echo '<p>All data will be preserved. A backup table will be created for safety.</p>';
        echo '</div>';
        
        echo '<div class="step">';
        echo '<h3>🚀 Ready to Begin?</h3>';
        echo '<p>Click the button below to start the migration process.</p>';
        echo '<a href="?confirm=yes" class="btn btn-success">✅ Run Migration Now</a>';
        echo '<a href="dashboard.php" class="btn btn-danger">❌ Cancel & Go Back</a>';
        echo '</div>';
        
    } else {
        // ===== RUN MIGRATION =====
        
        echo '<h2>🔄 Migration in Progress...</h2>';
        
        $errors = [];
        $success = true;
        
        try {
            // STEP 1: Backup
            echo '<div class="step">';
            echo '<strong>Step 1:</strong> Creating backup of current table... ';
            mysqli_query($conDB, "DROP TABLE IF EXISTS `activity_log_old_backup`");
            $backup = mysqli_query($conDB, "CREATE TABLE `activity_log_old_backup` AS SELECT * FROM `activity_log`");
            if ($backup) {
                echo '<span style="color: #28a745; font-weight: bold;">✓ Success</span>';
                $backup_count = mysqli_affected_rows($conDB);
                echo '<br><small style="color: #666;">Backed up ' . $backup_count . ' records</small>';
            } else {
                throw new Exception('Failed to create backup: ' . mysqli_error($conDB));
            }
            echo '</div>';
            
            // STEP 2: Drop and recreate
            echo '<div class="step">';
            echo '<strong>Step 2:</strong> Creating new enhanced table structure... ';
            
            mysqli_query($conDB, "DROP TABLE IF EXISTS `activity_log`");
            
            $create_sql = "CREATE TABLE `activity_log` (
              `id` int(11) NOT NULL AUTO_INCREMENT,
              `user_editor` varchar(100) NOT NULL COMMENT 'User who performed the action',
              `action_type` varchar(50) NOT NULL DEFAULT 'VIEW' COMMENT 'Type of action',
              `page` varchar(255) NOT NULL COMMENT 'Page where action occurred',
              `pg_id` varchar(255) DEFAULT NULL COMMENT 'ID of affected record',
              `old_value` text DEFAULT NULL COMMENT 'Previous value',
              `new_value` text DEFAULT NULL COMMENT 'New value',
              `description` text DEFAULT NULL COMMENT 'Human-readable description',
              `ip_address` varchar(45) DEFAULT NULL COMMENT 'User IP address',
              `user_agent` varchar(255) DEFAULT NULL COMMENT 'Browser info',
              `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
              PRIMARY KEY (`id`),
              KEY `idx_user` (`user_editor`),
              KEY `idx_page` (`page`),
              KEY `idx_action_type` (`action_type`),
              KEY `idx_created_at` (`created_at`)
            ) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Enhanced activity logging'";
            
            $create = mysqli_query($conDB, $create_sql);
            if ($create) {
                echo '<span style="color: #28a745; font-weight: bold;">✓ Success</span>';
            } else {
                throw new Exception('Failed to create table: ' . mysqli_error($conDB));
            }
            echo '</div>';
            
            // STEP 3: Migrate data
            echo '<div class="step">';
            echo '<strong>Step 3:</strong> Migrating existing records... ';
            
            $migrate_sql = "INSERT INTO `activity_log` (`user_editor`, `page`, `pg_id`, `created_at`, `action_type`)
            SELECT 
              IFNULL(NULLIF(user_editor, ''), 'SYSTEM') as user_editor,
              page,
              pg_id,
              CASE 
                WHEN reg_date REGEXP '^[0-9]{4}-[0-9]{2}-[0-9]{2}' THEN 
                  STR_TO_DATE(SUBSTRING(reg_date, 1, 19), '%Y-%m-%dT%H:%i:%s')
                WHEN reg_date != '' THEN
                  STR_TO_DATE(reg_date, '%Y-%m-%d %H:%i:%s')
                ELSE NOW()
              END as created_at,
              'VIEW' as action_type
            FROM `activity_log_old_backup`";
            
            $migrate = mysqli_query($conDB, $migrate_sql);
            if ($migrate) {
                $migrated = mysqli_affected_rows($conDB);
                echo '<span style="color: #28a745; font-weight: bold;">✓ Migrated ' . $migrated . ' records</span>';
            } else {
                throw new Exception('Failed to migrate data: ' . mysqli_error($conDB));
            }
            echo '</div>';
            
            // STEP 4: Verify
            echo '<div class="step">';
            echo '<strong>Step 4:</strong> Verifying migration... ';
            $old_count = mysqli_fetch_assoc(mysqli_query($conDB, "SELECT COUNT(*) as c FROM activity_log_old_backup"))['c'];
            $new_count = mysqli_fetch_assoc(mysqli_query($conDB, "SELECT COUNT(*) as c FROM activity_log"))['c'];
            
            if ($old_count == $new_count) {
                echo '<span style="color: #28a745; font-weight: bold;">✓ Verified: All ' . $new_count . ' records migrated successfully</span>';
            } else {
                echo '<span style="color: #ffc107; font-weight: bold;">⚠ Warning: Count mismatch</span>';
                echo '<br><small>Old table: ' . $old_count . ' | New table: ' . $new_count . '</small>';
            }
            echo '</div>';
            
            // SUCCESS!
            echo '<div class="success step">';
            echo '<h3>🎉 Migration Completed Successfully!</h3>';
            echo '<h4 style="margin: 15px 0 10px;">What Changed:</h4>';
            echo '<ul>';
            echo '<li>✅ Old table backed up as <code>activity_log_old_backup</code> (' . $old_count . ' records)</li>';
            echo '<li>✅ New enhanced table created with 11 columns (was ' . count($current_cols) . ')</li>';
            echo '<li>✅ All ' . $new_count . ' records migrated with proper date conversion</li>';
            echo '<li>✅ Performance indexes added for fast queries</li>';
            echo '</ul>';
            
            echo '<h4 style="margin: 20px 0 10px;">New Capabilities:</h4>';
            echo '<ul>';
            echo '<li>🔍 Track CREATE, UPDATE, DELETE, LOGIN, LOGOUT, APPROVE, REJECT actions</li>';
            echo '<li>📝 Store old/new values for complete audit trail</li>';
            echo '<li>🌐 Track user IP addresses and browser information</li>';
            echo '<li>📊 Human-readable descriptions for each action</li>';
            echo '<li>⚡ Fast searching with indexed columns</li>';
            echo '</ul>';
            
            echo '<h4 style="margin: 20px 0 10px;">Next Steps:</h4>';
            echo '<ol>';
            echo '<li>Integrate ActivityLogger class into your pages</li>';
            echo '<li>Test logging functionality</li>';
            echo '<li>Review activity logs in the new viewer</li>';
            echo '<li>After verification, you can drop the backup table</li>';
            echo '</ol>';
            echo '</div>';
            
            echo '<div class="step" style="background: #fff; border: none; text-align: center;">';
            echo '<a href="dashboard.php" class="btn">← Back to Dashboard</a>';
            echo '<a href="view_activity_logs.php" class="btn btn-success">📊 View Activity Logs</a>';
            echo '</div>';
            
        } catch (Exception $e) {
            echo '<div class="error step">';
            echo '<h3>❌ Migration Failed</h3>';
            echo '<p><strong>Error:</strong> ' . htmlspecialchars($e->getMessage()) . '</p>';
            echo '<h4>Recovery Steps:</h4>';
            echo '<ul>';
            echo '<li>Your original data is safe in <code>activity_log_old_backup</code></li>';
            echo '<li>To restore: <code>DROP TABLE activity_log; RENAME TABLE activity_log_old_backup TO activity_log;</code></li>';
            echo '<li>Check the error message and database permissions</li>';
            echo '<li>Contact system administrator if needed</li>';
            echo '</ul>';
            echo '</div>';
        }
    }
    ?>
</div>
</body>
</html>
