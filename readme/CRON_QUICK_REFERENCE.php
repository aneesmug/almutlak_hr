<?php
/**
 * QUICK REFERENCE: Vacation Balance Cron File
 * 
 * File: cron_update_vacation_balances.php
 * 
 * WHAT IT DOES:
 * - Updates employee vacation balances every day
 * - Saves old and new values to view later
 * - Shows beautiful HTML report in browser
 * 
 * HOW TO USE:
 * 
 * 1. VIEW REPORT IN BROWSER:
 *    http://localhost/almutlak/system/cron_update_vacation_balances.php
 *    (Shows last run results with old → new balance values)
 * 
 * 2. RUN MANUALLY FROM COMMAND LINE:
 *    D:\xampp\php\php.exe cron_update_vacation_balances.php
 *    (Shows text list + generates HTML report)
 * 
 * 3. CHECK FOR PROBLEMS:
 *    D:\xampp\php\php.exe test_cron.php
 *    (Tests all connections and dependencies)
 * 
 * WHERE IS DATA SAVED:
 * - cron_logs/vacation_balance_update_YYYY-MM-DD.log (daily log)
 * - cron_logs/last_vacation_update_report.json (latest results)
 * 
 * WHAT YOU SEE IN REPORT:
 * 
 * Summary Cards:
 *   - Total Employees: How many employees were checked
 *   - Records Updated: How many were changed
 *   - Balances Changed: Actual balance changes
 *   - Errors: Any problems that occurred
 * 
 * Update Details Table:
 *   - Employee ID & Name
 *   - Old Balance (before) → New Balance (after)
 *   - Status (CHANGED or REFRESHED)
 *   - Exact timestamp
 * 
 * SCHEDULE IT (OPTIONAL):
 * 
 * Windows Task Scheduler:
 *   - Program: D:\xampp\php\php.exe
 *   - Arguments: D:\xampp\htdocs\almutlak\system\cron_update_vacation_balances.php
 *   - Schedule: Daily at 1:00 AM
 * 
 * Linux/Unix Crontab:
 *   0 1 * * * /usr/bin/php /path/to/almutlak/system/cron_update_vacation_balances.php
 */
?>
