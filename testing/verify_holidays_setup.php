<?php
/**
 * HOLIDAYS SYSTEM - SETUP VERIFICATION SCRIPT
 * 
 * Run this script to verify all holiday system components are properly installed
 */

header('Content-Type: text/html; charset=utf-8');

?>
<!DOCTYPE html>
<html>
<head>
    <title>Holidays System - Setup Verification</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f5f5f5;
            padding: 20px;
        }
        .container {
            max-width: 900px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        h1 {
            color: #333;
            border-bottom: 3px solid #007bff;
            padding-bottom: 10px;
        }
        .check-item {
            padding: 15px;
            margin: 10px 0;
            border-left: 4px solid #ddd;
            background: #f9f9f9;
        }
        .check-item.pass {
            border-left-color: #28a745;
            background: #f1f9f1;
        }
        .check-item.fail {
            border-left-color: #dc3545;
            background: #fdf1f1;
        }
        .check-item.warn {
            border-left-color: #ffc107;
            background: #fffaf0;
        }
        .status-icon {
            font-weight: bold;
            margin-right: 10px;
        }
        .pass .status-icon {
            color: #28a745;
        }
        .fail .status-icon {
            color: #dc3545;
        }
        .warn .status-icon {
            color: #ffc107;
        }
        .next-steps {
            background: #e7f3ff;
            border: 1px solid #b3d9ff;
            padding: 20px;
            border-radius: 4px;
            margin-top: 20px;
        }
        .next-steps h2 {
            color: #004085;
            margin-top: 0;
        }
        .next-steps ol {
            color: #004085;
        }
        .next-steps code {
            background: #fff;
            padding: 2px 6px;
            border: 1px solid #b3d9ff;
            border-radius: 3px;
            font-family: 'Courier New', monospace;
        }
        .summary {
            margin-top: 30px;
            padding: 15px;
            background: #f0f0f0;
            border-radius: 4px;
            font-weight: bold;
        }
        .summary.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .summary.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 Holidays System - Setup Verification</h1>
        
        <p>This script verifies that all holiday system components are properly installed and configured.</p>

        <?php
        $base_path = __DIR__;
        $checks = [];
        $all_pass = true;

        // Check 1: SQL Migration File
        $file = 'sql/holiday_system_migration.sql';
        $exists = file_exists($base_path . '/' . $file);
        $checks[] = [
            'name' => 'SQL Migration File',
            'status' => $exists ? 'pass' : 'fail',
            'path' => $file,
            'message' => $exists ? 'Found' : 'NOT found'
        ];
        if (!$exists) $all_pass = false;

        // Check 2: Holiday Management Page
        $file = 'manage_holidays.php';
        $exists = file_exists($base_path . '/' . $file);
        $checks[] = [
            'name' => 'Holiday Management Page',
            'status' => $exists ? 'pass' : 'fail',
            'path' => $file,
            'message' => $exists ? 'Found' : 'NOT found'
        ];
        if (!$exists) $all_pass = false;

        // Check 3: Helper Functions - get_active_holidays_in_range
        $helper_file = $base_path . '/includes/helper_functions.php';
        $helper_content = file_get_contents($helper_file);
        $has_func1 = strpos($helper_content, 'function get_active_holidays_in_range') !== false;
        $checks[] = [
            'name' => 'Helper Function: get_active_holidays_in_range',
            'status' => $has_func1 ? 'pass' : 'fail',
            'path' => 'includes/helper_functions.php',
            'message' => $has_func1 ? 'Implemented' : 'NOT found'
        ];
        if (!$has_func1) $all_pass = false;

        // Check 4: Helper Function - calculate_holiday_days_in_vacation
        $has_func2 = strpos($helper_content, 'function calculate_holiday_days_in_vacation') !== false;
        $checks[] = [
            'name' => 'Helper Function: calculate_holiday_days_in_vacation',
            'status' => $has_func2 ? 'pass' : 'fail',
            'path' => 'includes/helper_functions.php',
            'message' => $has_func2 ? 'Implemented' : 'NOT found'
        ];
        if (!$has_func2) $all_pass = false;

        // Check 5: Helper Function - calculate_working_vacation_days
        $has_func3 = strpos($helper_content, 'function calculate_working_vacation_days') !== false;
        $checks[] = [
            'name' => 'Helper Function: calculate_working_vacation_days',
            'status' => $has_func3 ? 'pass' : 'fail',
            'path' => 'includes/helper_functions.php',
            'message' => $has_func3 ? 'Implemented' : 'NOT found'
        ];
        if (!$has_func3) $all_pass = false;

        // Check 6: Holiday logic in update_vacation_balance_on_approval
        $has_holiday_logic = strpos($helper_content, 'HOLIDAY CALCULATION') !== false;
        $checks[] = [
            'name' => 'Holiday Integration in Vacation Balance',
            'status' => $has_holiday_logic ? 'pass' : 'fail',
            'path' => 'includes/helper_functions.php',
            'message' => $has_holiday_logic ? 'Integrated' : 'NOT integrated'
        ];
        if (!$has_holiday_logic) $all_pass = false;

        // Check 7: Documentation Files
        $file = 'HOLIDAYS_FEATURE_GUIDE.md';
        $exists_guide = file_exists($base_path . '/' . $file);
        $checks[] = [
            'name' => 'Feature Guide Documentation',
            'status' => $exists_guide ? 'pass' : 'warn',
            'path' => $file,
            'message' => $exists_guide ? 'Found' : 'Optional file not found'
        ];

        $file = 'HOLIDAYS_IMPLEMENTATION.php';
        $exists_impl = file_exists($base_path . '/' . $file);
        $checks[] = [
            'name' => 'Implementation Guide',
            'status' => $exists_impl ? 'pass' : 'warn',
            'path' => $file,
            'message' => $exists_impl ? 'Found' : 'Optional file not found'
        ];

        // Display checks
        foreach ($checks as $check) {
            $class = $check['status'] === 'pass' ? 'pass' : ($check['status'] === 'fail' ? 'fail' : 'warn');
            $icon = $check['status'] === 'pass' ? '✅' : ($check['status'] === 'fail' ? '❌' : '⚠️');
            ?>
            <div class="check-item <?php echo $class; ?>">
                <span class="status-icon"><?php echo $icon; ?></span>
                <strong><?php echo htmlspecialchars($check['name']); ?></strong>
                <br>
                <small style="color: #666;">
                    <?php echo htmlspecialchars($check['path']); ?> - 
                    <?php echo htmlspecialchars($check['message']); ?>
                </small>
            </div>
            <?php
        }

        // Summary
        if ($all_pass) {
            ?>
            <div class="summary success">
                ✅ All required components are installed!
            </div>
            <?php
        } else {
            ?>
            <div class="summary error">
                ❌ Some required components are missing. Please check above and ensure all files are present.
            </div>
            <?php
        }

        // Database check
        ?>
        <h2 style="margin-top: 40px; color: #333;">Database Check</h2>
        <?php

        try {
            require_once __DIR__ . '/includes/db.php';

            // Check if emp_holidays table exists
            $result = $pdo->query("SHOW TABLES LIKE 'emp_holidays'");
            $table_exists = $result && $result->rowCount() > 0;

            if ($table_exists) {
                // Count records
                $count_stmt = $pdo->query("SELECT COUNT(*) as count FROM emp_holidays WHERE is_active = 1");
                $count = $count_stmt->fetch()['count'];

                ?>
                <div class="check-item pass">
                    <span class="status-icon">✅</span>
                    <strong>emp_holidays Table</strong>
                    <br>
                    <small style="color: #666;">
                        Table exists and contains <strong><?php echo $count; ?> active holiday(ies)</strong>
                    </small>
                </div>
                <?php

                if ($count > 0) {
                    echo '<div class="check-item pass"><span class="status-icon">✅</span><strong>Sample Holidays</strong><br>';
                    $stmt = $pdo->prepare("
                        SELECT holiday_name, start_date, end_date, total_days 
                        FROM emp_holidays 
                        WHERE is_active = 1 
                        ORDER BY start_date DESC 
                        LIMIT 3
                    ");
                    $stmt->execute();
                    echo '<small style="color: #666;"><ul style="margin: 10px 0;">';
                    while ($row = $stmt->fetch()) {
                        $days = date('M d', strtotime($row['start_date'])) . ' - ' . date('M d, Y', strtotime($row['end_date']));
                        echo '<li><strong>' . htmlspecialchars($row['holiday_name']) . '</strong>: ' . $days . ' (' . $row['total_days'] . ' days)</li>';
                    }
                    echo '</ul></small></div>';
                } else {
                    echo '<div class="check-item warn"><span class="status-icon">⚠️</span><strong>No Holidays Found</strong><br><small style="color: #666;">Table exists but has no active holidays. Add some via manage_holidays.php</small></div>';
                }
            } else {
                ?>
                <div class="check-item fail">
                    <span class="status-icon">❌</span>
                    <strong>emp_holidays Table</strong>
                    <br>
                    <small style="color: #666;">
                        Table NOT found. Run the SQL migration file to create it.
                    </small>
                </div>
                <?php
            }
        } catch (Exception $e) {
            ?>
            <div class="check-item fail">
                <span class="status-icon">❌</span>
                <strong>Database Connection</strong>
                <br>
                <small style="color: #666;">
                    Error: <?php echo htmlspecialchars($e->getMessage()); ?>
                </small>
            </div>
            <?php
        }
        ?>

        <!-- Next Steps -->
        <div class="next-steps">
            <h2>📋 Next Steps</h2>
            <ol>
                <li>
                    <strong>Create the emp_holidays table</strong>
                    <br>
                    Run the SQL migration file:
                    <br>
                    <code>sql/holiday_system_migration.sql</code>
                </li>
                <li>
                    <strong>Add your first holiday</strong>
                    <br>
                    Navigate to: <a href="manage_holidays.php" target="_blank">manage_holidays.php</a>
                    <br>
                    Click "Add Holiday" and fill in the details
                </li>
                <li>
                    <strong>Test the feature</strong>
                    <br>
                    Create a vacation that overlaps with a holiday
                    <br>
                    Verify the deduction is calculated correctly
                </li>
                <li>
                    <strong>Check the debug logs</strong>
                    <br>
                    Look for messages like: "DEBUG: Vacation ID X has Y holiday days"
                    <br>
                    In your PHP error log file
                </li>
            </ol>
        </div>

        <!-- Feature Summary -->
        <h2 style="margin-top: 40px; color: #333;">Feature Summary</h2>
        <div style="background: #f9f9f9; padding: 15px; border-radius: 4px;">
            <p><strong>What this feature does:</strong></p>
            <ul>
                <li>Allows HR to define company holidays</li>
                <li>Automatically excludes holidays from vacation day deductions</li>
                <li>Example: 15-day vacation with 4 holidays = 11 days deducted</li>
            </ul>

            <p><strong>Holiday Types:</strong></p>
            <ul>
                <li>Religious (e.g., Eid al-Fitr)</li>
                <li>National (e.g., Saudi National Day)</li>
                <li>Other (Company-specific)</li>
            </ul>

            <p><strong>How to use:</strong></p>
            <ol>
                <li>Add holidays via manage_holidays.php</li>
                <li>Employees apply for vacation normally</li>
                <li>System automatically subtracts holidays from deduction</li>
            </ol>
        </div>

        <p style="margin-top: 40px; text-align: center; color: #666; font-size: 12px;">
            Holidays System Implementation - Generated on <?php echo date('Y-m-d H:i:s'); ?>
        </p>
    </div>
</body>
</html>
