# Smart Request Email & Notification Update

## Summary
Updated `open_request.php` to use the centralized email/notification system that matches the pattern used in vacation and loan requests.

## Key Changes

### 1. Email Source - Admin Login Table ✅
**Your system already fetches emails from the `admin_login` table correctly!**

The helper functions use this query pattern:
```sql
SELECT e.name, al.email
FROM `employees` e
LEFT JOIN `admin_login` al ON e.emp_id = al.emp_id
WHERE e.`emp_id` = ? AND e.`status` = 1
```

Functions that fetch emails:
- `getEmployeeDetailsForApproval()` - Used for approvers (lines 1824-1860 in helper_functions.php)
- `getEmployeeDetails()` - Used for other employees (lines 2004-2040 in helper_functions.php)

### 2. Centralized Email Function
All emails now use `send_approval_email()` from `helper_functions.php` which:
- Fetches SMTP settings from `app_settings` table
- Uses PHPMailer with proper error handling
- Logs success/failure to error logs

**SMTP Settings Required in `app_settings` table:**
- `smtp_host` (e.g., smtp.office365.com)
- `smtp_port` (e.g., 587)
- `smtp_user` (e.g., noreply@almutlak.com)
- `smtp_pass` (your SMTP password)
- `smtp_encryption` (e.g., tls)
- `from_email` (e.g., noreply@almutlak.com)
- `from_name` (e.g., Al Mutlak HR System)

### 3. Updated Sections in `open_request.php`

#### A. Draft Submission (Lines ~220-285)
**OLD:** Used `getEmployeeDetails()` and only sent browser notification
**NEW:** 
- Uses `getEmployeeDetailsForApproval()` to fetch email from admin_login table
- Sends browser notification via `create_browser_notification()`
- Sends email via `send_approval_email()`
- Comprehensive error logging for debugging

**Email Content:**
- Subject: "Smart Request Requires Your Approval - [Request ID]"
- Body: HTML email with request details and direct link

#### B. Payer Assignment (Lines ~365-397)
**OLD:** Created email variables but didn't actually send email
**NEW:**
- Uses `getEmployeeDetailsForApproval()` to fetch email from admin_login table
- Sends browser notification to assigned payer
- Sends email notification to assigned payer
- Error logging for debugging

**Email Content:**
- Subject: "Smart Request - Payment Processing Required - [Request ID]"
- Body: HTML email notifying of payment assignment

#### C. Approval Actions
**NOTE:** The `handle_approval_action()` function in helper_functions.php already handles:
- Sending notifications to next approvers
- Sending rejection notifications to creator
- Sending approval notifications

This function is called at line ~297 and already uses the centralized notification system.

### 4. Legacy Code Preserved
The old PHPMailer block (lines ~417-520) is KEPT because it handles:
- CC functionality for HR employees
- Custom email templates from `./includes/PHPMailerMaster/email_contant_body_redesigned.php`

This is specific to Smart Requests and not part of the standard approval flow.

## Error Logging Added

All notification/email operations now log:
1. **Function availability check:**
   ```
   "open_request: getEmployeeDetailsForApproval function NOT FOUND"
   "open_request: create_browser_notification function NOT FOUND"
   "open_request: send_approval_email function NOT FOUND"
   ```

2. **Email availability check:**
   ```
   "open_request: First approver has NO EMAIL in database (emp_id: X)"
   "open_request (assign payer): Assignee has NO EMAIL in database (emp_id: X)"
   ```

3. **Operation results:**
   ```
   "open_request: Browser notification result: SUCCESS/FAILED"
   "open_request: Email result: SUCCESS/FAILED"
   ```

## Testing Checklist

### 1. Verify Email Addresses in Database
```sql
-- Check if employees have emails in admin_login table
SELECT e.emp_id, e.name, al.email
FROM employees e
LEFT JOIN admin_login al ON e.emp_id = al.emp_id
WHERE e.status = 1
ORDER BY e.emp_id;
```

**Fix missing emails:**
```sql
UPDATE admin_login 
SET email = 'employee@almutlak.com' 
WHERE emp_id = [EMPLOYEE_ID];
```

### 2. Verify SMTP Settings
```sql
-- Check SMTP configuration
SELECT setting_name, setting_value 
FROM app_settings 
WHERE setting_group = 'email';
```

**Expected output:**
| setting_name | setting_value |
|--------------|---------------|
| smtp_host | smtp.office365.com |
| smtp_port | 587 |
| smtp_user | noreply@almutlak.com |
| smtp_pass | HO@66887 |
| smtp_encryption | tls |
| from_email | noreply@almutlak.com |
| from_name | Al Mutlak HR System |

### 3. Test Smart Request Flow

**A. Submit New Request:**
1. Create a new smart request as draft
2. Select approvers and submit
3. Check PHP error log for:
   - "open_request: Attempting to send notification to first_approver_id: X"
   - "open_request: Browser notification result: SUCCESS"
   - "open_request: Email result: SUCCESS"
4. First approver should receive:
   - Browser notification in the system
   - Email to their address from admin_login table

**B. Approve Request:**
1. Log in as first approver
2. Approve the request
3. Next approver should receive notification/email (handled by `handle_approval_action()`)

**C. Assign Payer (Finance Manager):**
1. Log in as Finance Manager
2. Open approved request
3. Assign a payer from dropdown
4. Check PHP error log for:
   - "open_request (assign payer): Browser notification result: SUCCESS"
   - "open_request (assign payer): Email result: SUCCESS"
5. Assigned employee should receive notification/email

### 4. Check PHP Error Logs

**Windows (XAMPP):**
```
C:\xampp\php\logs\php_error_log
C:\xampp\apache\logs\error.log
```

**Look for:**
- Lines starting with "open_request:"
- Any "FAILED" results
- Any "NOT FOUND" messages
- SMTP connection errors

## Comparison with Other Request Types

All three request types now use the SAME pattern:

| Feature | Smart Request | Vacation Request | Loan Request |
|---------|--------------|------------------|--------------|
| Email Source | admin_login table ✅ | admin_login table ✅ | admin_login table ✅ |
| Function Used | getEmployeeDetailsForApproval() | getEmployeeDetailsForApproval() | getEmployeeDetailsForApproval() |
| Email Function | send_approval_email() | send_approval_email() | send_approval_email() |
| Notification Function | create_browser_notification() | create_browser_notification() | create_browser_notification() |
| Error Logging | Comprehensive ✅ | Comprehensive ✅ | Comprehensive ✅ |

## Troubleshooting

### Issue: No emails received
**Check:**
1. Employee has email in admin_login table
2. SMTP settings in app_settings table are correct
3. PHP error log shows "Email result: SUCCESS"
4. Email not in spam folder
5. SMTP server is allowing connections (some block port 587)

### Issue: "send_approval_email function NOT FOUND"
**Solution:** Verify `includes/helper_functions.php` is properly included
- Line 2 in open_request.php includes session_check.php
- session_check.php should include helper_functions.php

### Issue: "Employee has NO EMAIL in database"
**Solution:** Add email address to admin_login table:
```sql
UPDATE admin_login SET email = 'employee@company.com' WHERE emp_id = X;
```

### Issue: SMTP connection error
**Check:**
1. smtp_host is correct (smtp.office365.com for Office 365)
2. smtp_port is correct (587 for TLS, 465 for SSL)
3. smtp_encryption matches port (tls for 587, ssl for 465)
4. Username and password are correct
5. Firewall allows outgoing SMTP connections

## Files Modified
- `open_request.php` - Updated draft submission and payer assignment sections

## Files Referenced (No Changes)
- `includes/helper_functions.php` - Contains email/notification functions
- `sql/app_settings.sql` - Contains SMTP configuration

## Next Steps
1. Submit a test Smart Request
2. Check PHP error logs
3. Verify notifications appear in browser
4. Verify emails are received
5. If issues occur, share the error log output for diagnosis
