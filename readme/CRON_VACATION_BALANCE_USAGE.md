# Vacation Balance Cron Update - Usage Guide

## Overview
The `cron_update_vacation_balances.php` file automatically updates employee vacation balances daily. It's designed to be run as a cron job on a schedule, and you can view the results in a web browser.

## How It Works

### 1. **Automatic Daily Updates (Cron Job)**
The file should be scheduled to run once per day:
```bash
# Linux/Unix Crontab Example
0 1 * * * /usr/bin/php /path/to/almutlak/system/cron_update_vacation_balances.php >> /var/log/almutlak_cron.log 2>&1

# Windows Task Scheduler
# Action: D:\xampp\php\php.exe D:\xampp\htdocs\almutlak\system\cron_update_vacation_balances.php
```

### 2. **View Results in Browser**
After the cron job runs, open this URL to see the report:
```
http://localhost/almutlak/system/cron_update_vacation_balances.php
```

The report shows:
- **Total Employees**: How many employees were processed
- **Records Updated**: How many vacation balance records were updated
- **Balances Changed**: How many had actual balance changes
- **Errors**: Any processing errors

### 3. **Detailed Update List**
The HTML report displays a table with:
- Employee ID
- Employee Name
- **Old Balance** (before update)
- **New Balance** (after update)
- Status (CHANGED or REFRESHED)
- Timestamp of the update

## Files Generated

### Log Files
```
cron_logs/vacation_balance_update_YYYY-MM-DD.log
```
- Daily log file with detailed processing information
- Created automatically

### Report File
```
cron_logs/last_vacation_update_report.json
```
- Saved after each cron run
- Contains all update data in JSON format
- Used to display results in the browser

## Manual Execution

### From Command Line
```bash
D:\xampp\php\php.exe cron_update_vacation_balances.php
```

Output in terminal:
```
========== VACATION BALANCE UPDATE RESULTS ==========
Total Employees: 445
Records Updated: 1
Balances Changed: 1
Errors: 0

--- UPDATE LIST ---
[2025-12-25 11:23:32] 5430 (ANEES AFZAL MUHAMMAD AFZAL) - Old: 17.89 → New: 17.97 (CHANGED)
======================================================
```

### From Browser
Simply open the file URL - it will display the last saved report in an HTML format.

## Troubleshooting

### No Report File Found
- The report file is created after the first cron run
- Make sure the `cron_logs/` directory is writable

### Records Not Updating
Run the diagnostic test:
```bash
D:\xampp\php\php.exe test_cron.php
```

This will check:
- Required PHP files exist
- Database connection works
- Employee records exist
- Vacation balance table is accessible
- Helper functions are available

## What Gets Updated

The script updates the `emp_vacation_balance` table for:
- All active employees (status = 1)
- Only those with existing vacation balance records
- Both `available_balance` and `total_days` fields

The calculation is based on:
- Employment start date
- Vacation entitlements
- Previously taken vacations
- Current date

## Key Features

✓ Persistent report storage (saved to JSON file)
✓ Beautiful HTML GUI report display
✓ Text console output for cron monitoring
✓ Detailed change tracking (old → new values)
✓ Error handling and logging
✓ Daily log files for audit trail
✓ Employee names in reports (not just IDs)
✓ Timestamp on every update
✓ Shows if balance was changed or just refreshed

