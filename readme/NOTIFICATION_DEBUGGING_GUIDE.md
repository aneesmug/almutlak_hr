# Notification & Email Debugging Guide

## What Was Done

I've added comprehensive debugging and logging to the notification system for:
- ✅ Annual Vacation Requests (applyVacation)
- ✅ Leave Requests (applyLeave)  
- ✅ Loan Requests (apply_for_loan)

## How to Debug

### Step 1: Run the Test Page

Open in your browser:
```
http://localhost/almutlak/system/test_notifications.php
```

This will show you:
1. ✓ If all required functions exist
2. ✓ If the database tables are correct
3. ✓ Which employees have email addresses
4. ✓ Your SMTP configuration
5. ✓ A test notification creation
6. ✓ Where to find PHP error logs

### Step 2: Submit a Test Request

1. **Submit a vacation, leave, or loan request** through the normal UI
2. **Check the PHP error log** (location shown in test page)

You should see detailed logs like:
```
applyVacation: Attempting to send notification to first_approver_id: 1234
applyVacation: Browser notification result: SUCCESS
applyVacation: Attempting to send email to: manager@example.com
applyVacation: Email result: SUCCESS
```

### Step 3: Common Issues and Solutions

#### Issue 1: "function NOT FOUND"
**Log shows:** `create_browser_notification function NOT FOUND`

**Solution:**
- Make sure `includes/helper_functions.php` is included
- File path: `d:\xampp\htdocs\almutlak\system\includes\helper_functions.php`

#### Issue 2: "NO EMAIL in database"
**Log shows:** `First approver has NO EMAIL in database`

**Solution:**
- Add email addresses to employee records in the database
- Run this SQL to check:
```sql
SELECT emp_id, name, email FROM employees WHERE emp_id = [APPROVER_ID];
```
- Update with:
```sql
UPDATE employees SET email = 'approver@company.com' WHERE emp_id = [APPROVER_ID];
```

#### Issue 3: "Notification creation FAILED"
**Log shows:** `Browser notification result: FAILED`

**Check:**
1. Does `user_notifications` table exist?
```sql
SHOW TABLES LIKE 'user_notifications';
```

2. If missing, create it:
```sql
CREATE TABLE `user_notifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `emp_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `url` varchar(255) DEFAULT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `emp_id` (`emp_id`),
  KEY `is_read` (`is_read`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

#### Issue 4: "Email result: FAILED"
**Log shows:** `Email result: FAILED`

**Check SMTP Settings:**
1. Verify SMTP configuration in `app_settings` table:
```sql
SELECT * FROM app_settings 
WHERE setting_name IN ('smtp_host', 'smtp_port', 'smtp_username', 'smtp_password', 'smtp_secure', 'smtp_from_email');
```

2. Common SMTP settings:
- `smtp_host`: smtp.gmail.com (or your mail server)
- `smtp_port`: 587 (TLS) or 465 (SSL)
- `smtp_username`: your-email@gmail.com
- `smtp_password`: your-app-password
- `smtp_secure`: tls or ssl
- `smtp_from_email`: noreply@yourcompany.com
- `smtp_from_name`: Al-Mutlak HR System

3. For Gmail, you need to:
   - Enable 2-factor authentication
   - Create an App Password
   - Use the App Password in `smtp_password`

#### Issue 5: "first_approver_id is EMPTY"
**Log shows:** `first_approver_id is EMPTY`

**Solution:**
- The employee doesn't have a supervisor assigned
- Assign a supervisor:
```sql
UPDATE employees SET supervisor_id = [MANAGER_EMP_ID] WHERE emp_id = [EMPLOYEE_ID];
```

## Where to Find Logs

### Windows (XAMPP):
1. **Apache Error Log:** `d:\xampp\apache\logs\error.log`
2. **PHP Error Log:** `d:\xampp\php\logs\php_error_log.log`
3. **Custom Log (if configured):** Check `test_notifications.php` for location

### Linux:
1. **/var/log/apache2/error.log**
2. **/var/log/php_errors.log**

## Verify Notifications in Database

After submitting a request, check if notifications were created:

```sql
-- Check latest notifications
SELECT * FROM user_notifications ORDER BY id DESC LIMIT 10;

-- Check notifications for specific employee
SELECT * FROM user_notifications WHERE emp_id = [APPROVER_ID] ORDER BY id DESC;

-- Count unread notifications
SELECT emp_id, COUNT(*) as unread_count 
FROM user_notifications 
WHERE is_read = 0 
GROUP BY emp_id;
```

## Test Email Sending

You can test if email sending works independently:

```php
<?php
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/helper_functions.php';

$result = send_approval_email(
    $conDB,
    'test@example.com',  // Change to your email
    'Test User',
    'Test Email Subject',
    'This is a <strong>test email</strong> from the system.'
);

echo $result ? 'Email sent successfully!' : 'Email sending failed!';
?>
```

## Expected Behavior

When everything is working correctly:

1. **Employee submits request** → Request created in database
2. **System identifies first approver** → Gets approver details from database
3. **Browser notification created** → Entry in `user_notifications` table
4. **Email sent** → SMTP sends email to approver's email address
5. **Employee sees success** → "Request submitted successfully" message
6. **Approver sees notification** → Bell icon shows new notification
7. **Approver receives email** → Email arrives in inbox

## Quick Checklist

- [ ] All functions exist (check test page)
- [ ] `user_notifications` table exists
- [ ] Approvers have email addresses in database
- [ ] SMTP settings are configured correctly
- [ ] Employees have supervisors assigned
- [ ] PHP error logging is enabled
- [ ] Test notification creation works
- [ ] Check error logs after submitting request

## Contact Support

If you're still having issues after checking all of the above:

1. Run `test_notifications.php` and take a screenshot
2. Submit a test request and copy the error logs
3. Run the SQL queries above and share the results
4. Check if Smart Requests are working (for comparison)

The detailed error logs will tell us exactly what's failing!
