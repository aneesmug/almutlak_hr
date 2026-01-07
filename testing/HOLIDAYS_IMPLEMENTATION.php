<?php
/**
 * HOLIDAYS SYSTEM - IMPLEMENTATION GUIDE & TEST SCRIPT
 * 
 * This document explains the new holidays feature for vacation deduction calculations
 */

echo "=== HOLIDAYS SYSTEM IMPLEMENTATION GUIDE ===\n\n";

echo "1. DATABASE CHANGES\n";
echo "   - New table: emp_holidays\n";
echo "   - Fields: id, holiday_name, start_date, end_date, total_days, holiday_type, is_active, remarks, created_by, created_at, updated_by, updated_at\n";
echo "   - Run SQL: sql/holiday_system_migration.sql\n\n";

echo "2. NEW FILES CREATED\n";
echo "   - manage_holidays.php: Holiday management interface for HR/Admin\n";
echo "   - includes/helper_functions.php: Added new helper functions\n\n";

echo "3. NEW HELPER FUNCTIONS IN helper_functions.php\n";
echo "   Functions added:\n";
echo "   - get_active_holidays_in_range(\$conDB, \$start_date, \$end_date)\n";
echo "   - calculate_holiday_days_in_vacation(\$holidays, \$vac_start, \$vac_end)\n";
echo "   - calculate_working_vacation_days(\$total_days, \$holiday_days)\n";
echo "   - format_holiday_details(\$holidays)\n\n";

echo "4. MODIFIED FUNCTIONS\n";
echo "   - update_vacation_balance_on_approval(): Now calculates holiday days and adjusts deduction\n\n";

echo "5. HOW IT WORKS\n";
echo "   Step 1: Employee applies for vacation (01-01-2026 to 01-15-2026 = 15 days)\n";
echo "   Step 2: Manager approves\n";
echo "   Step 3: When marking as approved, system checks for active holidays\n";
echo "   Step 4: Find holidays in range:\n";
echo "           - Eid al-Fitr: 01-05-2026 to 01-08-2026 = 4 days\n";
echo "   Step 5: Calculate working days: 15 - 4 = 11 days\n";
echo "   Step 6: Deduct 11 days from employee vacation balance (not 15)\n\n";

echo "6. USER INTERFACE\n";
echo "   - Access: manage_holidays.php (HR/Admin only)\n";
echo "   - Features:\n";
echo "     * Add new holidays\n";
echo "     * Edit existing holidays\n";
echo "     * Archive holidays (soft delete)\n";
echo "     * View all active holidays in a table\n\n";

echo "7. HOLIDAY TYPES\n";
echo "   - Religious: Islamic holidays, etc.\n";
echo "   - National: National celebration days\n";
echo "   - Other: Company-specific holidays\n\n";

echo "8. DATABASE MIGRATION COMMAND\n";
echo "   Execute the SQL file to create the emp_holidays table:\n";
echo "   mysql -u [user] -p [database] < sql/holiday_system_migration.sql\n\n";

// TEST SECTION
echo "=== TEST SCENARIO ===\n\n";

require_once __DIR__ . '/includes/db.php';

try {
    // Check if holidays table exists
    $result = $pdo->query("SHOW TABLES LIKE 'emp_holidays'");
    $table_exists = $result->rowCount() > 0;
    
    if ($table_exists) {
        echo "✅ emp_holidays table exists\n";
        
        // Count active holidays
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM emp_holidays WHERE is_active = 1");
        $count = $stmt->fetch()['count'];
        echo "✅ Active holidays: $count\n\n";
        
        // Show upcoming holidays
        $stmt = $pdo->prepare("
            SELECT holiday_name, start_date, end_date, total_days, holiday_type 
            FROM emp_holidays 
            WHERE is_active = 1 
            AND start_date >= CURDATE()
            ORDER BY start_date ASC
            LIMIT 5
        ");
        $stmt->execute();
        $holidays = $stmt->fetchAll();
        
        if (!empty($holidays)) {
            echo "Upcoming Holidays:\n";
            foreach ($holidays as $h) {
                echo "  - {$h['holiday_name']}: {$h['start_date']} to {$h['end_date']} ({$h['total_days']} days) [{$h['holiday_type']}]\n";
            }
        } else {
            echo "⚠ No upcoming holidays found. Add holidays via manage_holidays.php\n";
        }
        
    } else {
        echo "❌ emp_holidays table NOT found\n";
        echo "   Run: sql/holiday_system_migration.sql\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error checking table: " . $e->getMessage() . "\n";
}

echo "\n=== EXAMPLE IMPLEMENTATION ===\n\n";
echo <<<'EXAMPLE'
// Example: Adding a holiday programmatically
$pdo->prepare("
    INSERT INTO emp_holidays 
    (holiday_name, start_date, end_date, total_days, holiday_type, remarks, created_by)
    VALUES (?, ?, ?, ?, ?, ?, ?)
")->execute([
    'Eid al-Fitr 2026',
    '2026-04-09',
    '2026-04-13',
    5,
    'religious',
    'Islamic holiday',
    $current_user_id
]);

// Example: Getting holidays for a vacation period
$holidays = get_active_holidays_in_range($conDB, '2026-01-01', '2026-01-15');
$holiday_days = calculate_holiday_days_in_vacation($holidays, '2026-01-01', '2026-01-15');
$working_days = calculate_working_vacation_days(15, $holiday_days); // Returns: 15 - 4 = 11
EXAMPLE;

echo "\n=== CONFIGURATION IN APP_SETTINGS ===\n\n";
echo "Optional: You can manage holiday settings in app_settings.php:\n";
echo "  - Enable/disable holiday feature\n";
echo "  - Set default holiday type\n";
echo "  - Configure which vacation types exclude holidays\n\n";

echo "=== TESTING CHECKLIST ===\n";
echo "[ ] 1. Run SQL migration to create emp_holidays table\n";
echo "[ ] 2. Access manage_holidays.php and add test holidays\n";
echo "[ ] 3. Create a vacation that overlaps with a holiday\n";
echo "[ ] 4. Approve the vacation and verify correct deduction\n";
echo "[ ] 5. Check debug logs: error_log shows holiday calculations\n";
echo "[ ] 6. Verify emp_vacation_balance shows correct used_days\n\n";

echo "=== SUPPORT ===\n";
echo "For issues or questions:\n";
echo "1. Check php error logs for debug output\n";
echo "2. Verify emp_holidays table has active records\n";
echo "3. Ensure start_date and return_date are set in vacation record\n";
echo "4. Check holiday overlap calculation in helper functions\n\n";

?>
