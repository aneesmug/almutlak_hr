# Auto-Reject Stale Loan Requests - Setup Guide

## Overview
This system automatically rejects loan requests that haven't been approved by the direct supervisor within 3 days.

## Files Created
1. `cron_auto_reject_stale_loans.php` - Main cron job script
2. `test_auto_reject_loans.php` - Manual testing page (admin only)

## How It Works
1. **Daily Check**: Script runs once per day (recommended: 2:00 AM)
2. **Detection**: Finds loan requests pending level 1 approval for more than 3 days
3. **Auto-Rejection**: 
   - Updates loan status to 'rejected'
   - Sets rejection reason explaining the timeout
   - Updates approval chain to 'auto_rejected'
   - Logs comment in system
   - Notifies employee via browser notification
4. **Logging**: All executions are logged to `cron_logs/auto_reject_loans_YYYY-MM.log`

## Setup Instructions

### Option 1: Windows Task Scheduler (XAMPP on Windows)

1. **Open Task Scheduler**
   - Press `Win + R`, type `taskschd.msc`, press Enter

2. **Create New Task**
   - Click "Create Task" (not Basic Task)
   - **General Tab:**
     - Name: `Al-Mutlak Auto-Reject Stale Loans`
     - Description: `Automatically rejects loan requests pending supervisor approval for more than 3 days`
     - Select: "Run whether user is logged on or not"
     - Select: "Run with highest privileges"

3. **Triggers Tab:**
   - Click "New"
   - Begin the task: "On a schedule"
   - Settings: "Daily"
   - Start time: `02:00:00` (2:00 AM)
   - Recur every: `1` days
   - Click OK

4. **Actions Tab:**
   - Click "New"
   - Action: "Start a program"
   - Program/script: `D:\xampp\php\php.exe`
   - Add arguments: `"D:\xampp\htdocs\almutlak\system\cron_auto_reject_stale_loans.php"`
   - Click OK

5. **Conditions Tab:**
   - Uncheck "Start the task only if the computer is on AC power"

6. **Settings Tab:**
   - Check "Allow task to be run on demand"
   - Check "If the task fails, restart every: 1 minute, 3 times"

7. **Save the Task**
   - Enter your Windows password when prompted

### Option 2: Linux/Ubuntu Cron Job

1. **Edit Crontab**
   ```bash
   crontab -e
   ```

2. **Add Cron Entry**
   ```bash
   # Auto-reject stale loan requests daily at 2:00 AM
   0 2 * * * /usr/bin/php /var/www/html/almutlak/system/cron_auto_reject_stale_loans.php >> /var/www/html/almutlak/system/logs/cron/auto_reject.log 2>&1
   ```

3. **Save and Exit**
   - Press `Ctrl + X`, then `Y`, then `Enter`

### Option 3: Manual Execution via Web Browser (Testing Only)

**URL:** `http://localhost/almutlak/system/cron_auto_reject_stale_loans.php?cron_key=auto_reject_loans_2026`

**Note:** This should only be used for testing. For production, use Task Scheduler or cron.

## Testing

### Test Page (Admin Only)
A dedicated test page is available at:
```
http://localhost/almutlak/system/test_auto_reject_loans.php
```

Features:
- View current configuration (3-day threshold)
- See stale loan requests that would be auto-rejected
- Manual trigger button to execute rejection immediately
- View execution logs

### Manual Testing Steps

1. **Create a Test Loan Request**
   - Have an employee submit a loan request
   - Note the request ID and invoice number

2. **Manually Set Old Date** (for testing only)
   ```sql
   UPDATE emp_loan 
   SET created_at = DATE_SUB(NOW(), INTERVAL 4 DAY)
   WHERE id = YOUR_TEST_LOAN_ID;
   ```

3. **Run the Script**
   - Via test page: Click "Run Auto-Reject Now"
   - Via CLI: `php cron_auto_reject_stale_loans.php`
   - Via browser: Visit the cron URL with the correct key

4. **Verify Results**
   - Check loan status is 'rejected'
   - Check rejection_reason field
   - Check approval chain status is 'auto_rejected'
   - Check employee received notification
   - Check log file: `logs/cron/auto_reject_loans_YYYY-MM.log`

## Configuration

### Change Rejection Threshold

Edit `cron_auto_reject_stale_loans.php`:
```php
$DAYS_THRESHOLD = 3; // Change this number (e.g., 5 for 5 days)
```

### Change Execution Time

**Windows Task Scheduler:** Edit the trigger start time
**Linux Cron:** Edit the crontab time (first two numbers: `0 2` = 2:00 AM)

## Monitoring

### Check Logs
```bash
# View latest log
cat cron_logs/auto_reject_loans_2026-01.log

# View last 50 lines
tail -n 50 cron_logs/auto_reject_loans_2026-01.log

# Monitor in real-time
tail -f cron_logs/auto_reject_loans_2026-01.log
```

### Verify Task is Running (Windows)
1. Open Task Scheduler
2. Find "Al-Mutlak Auto-Reject Stale Loans"
3. Check "Last Run Time" and "Last Run Result"

### Verify Cron is Running (Linux)
```bash
# View cron logs
grep CRON /var/log/syslog | tail -20

# Check if cron is running
systemctl status cron
```

## Troubleshooting

### Issue: Script not executing
**Windows:**
- Check Task Scheduler history (enable if disabled)
- Verify PHP path is correct
- Run manually from command line to check for errors

**Linux:**
- Check cron logs: `grep CRON /var/log/syslog`
- Verify PHP path: `which php`
- Check file permissions: `chmod +x cron_auto_reject_stale_loans.php`

### Issue: Database errors
- Check database connection in `includes/db.php`
- Verify `approval_request_types` table has 'loan_request' entry
- Check MySQL is running

### Issue: No rejections happening
- Verify there are actually loans pending > 3 days
- Check cutoff date calculation in logs
- Verify loan status is 'pending' (not 'pending_level_1')
- Check approval chain has pending level 1 approvers

## Security Notes

1. **Cron Key:** The URL cron key (`auto_reject_loans_2026`) should be changed in production
2. **File Permissions:** Ensure cron script is not publicly writable
3. **Log Access:** Restrict access to cron_logs directory
4. **Test Page:** Remove or restrict `test_auto_reject_loans.php` in production

## Support

For issues or questions:
1. Check execution logs first
2. Run test page to see what would be rejected
3. Manually execute script and check output
4. Review database queries for data issues

## Maintenance

### Monthly Tasks
- Review log files for errors
- Verify rejection counts are reasonable
- Archive old logs if needed

### Quarterly Tasks
- Review rejection threshold (currently 3 days)
- Update cron key for security
- Test execution manually
