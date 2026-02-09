# Settlement Email Troubleshooting Guide

## Problem
When creating a settlement via EOS submission, the SweetAlert2 shows correctly, but emails are NOT being sent to the first approver.

## Quick Diagnosis

### Step 1: Check SMTP Configuration
Navigate to **App Settings** (usually `app_settings.php`) and verify these settings are configured:
- `smtp_host` - SMTP server address (e.g., smtp.gmail.com, mail.domain.com)
- `smtp_port` - SMTP port (typically 587 or 465)
- `smtp_user` - SMTP username
- `smtp_pass` - SMTP password
- `smtp_encryption` - Encryption type (TLS or SSL)
- `from_email` - Email address that sends notifications
- `from_name` - Display name for email sender

**Fix:** If any are missing, add them to the `app_settings` table:
```sql
INSERT INTO app_settings (setting_key, setting_value) VALUES 
('smtp_host', 'your-smtp-server.com'),
('smtp_port', '587'),
('smtp_user', 'your-email@domain.com'),
('smtp_pass', 'your-password'),
('smtp_encryption', 'TLS'),
('from_email', 'noreply@almutlak.com'),
('from_name', 'Al Mutlak HR System');
```

### Step 2: Check Approver Email
The first approver in the approval chain must have an email address in the `admin_login` table.

**To verify/fix:**
```sql
SELECT al.id_iqama, al.emp_id, al.email 
FROM admin_login al 
WHERE al.emp_id = 'APPROVER_EMP_ID';

-- If email is missing, update it:
UPDATE admin_login SET email = 'approver@domain.com' 
WHERE emp_id = 'APPROVER_EMP_ID';
```

### Step 3: Check Approval Chain Configuration
The system needs to know who should approve settlements. This is configured in `app_settings` table as `approval_chain_settlement`.

**To verify/fix:**
```sql
SELECT setting_value FROM app_settings WHERE setting_key = 'approval_chain_settlement';
```

**Expected format** (example):
```json
{
  "chain": [
    {
      "position": 1,
      "approver_type": "dept_manager",
      "description": "Department Manager"
    },
    {
      "position": 2,
      "approver_type": "finance_manager",
      "description": "Finance Manager"
    }
  ]
}
```

**If missing**, insert it:
```sql
INSERT INTO app_settings (setting_key, setting_value) VALUES 
('approval_chain_settlement', '{
  "chain": [
    {
      "position": 1,
      "approver_type": "dept_manager",
      "description": "Department Manager"
    },
    {
      "position": 2,
      "approver_type": "finance_manager",
      "description": "Finance Manager"
    }
  ]
}');
```

### Step 4: Check Settlement Request Type
The system needs a "settlement" request type registered.

**To verify/fix:**
```sql
SELECT * FROM approval_request_types WHERE type_name = 'settlement';

-- If missing, insert it:
INSERT INTO approval_request_types (type_name, description) 
VALUES ('settlement', 'Settlement Records');
```

### Step 5: Use Debugging Tool
After creating an EOS, use the debugging tool to diagnose email issues:

**URL:** `debug_settlement_email.php?settlement_inv_no=SETL-YOUR-REQUEST-NUMBER`

**Example:** `debug_settlement_email.php?settlement_inv_no=SETL-RES-2024-001`

This tool will show:
- ✓ Settlement record exists
- ✓ Approval chain created  
- ✓ First approver details
- ✓ Approver email address
- ✓ SMTP configuration status
- ✓ Approval chain configuration
- ✓ Error log entries

## Common Issues & Solutions

### Issue 1: "No approvers found in chain"
**Cause:** Approval chain creation failed
**Solution:** 
1. Check `approval_chain_settlement` is configured in app_settings
2. Check ApprovalChainManager has proper permission to create entries
3. Verify `request_approvers` table structure exists

### Issue 2: "Approver has no email"
**Cause:** First approver doesn't have email in admin_login table
**Solution:**
```sql
UPDATE admin_login SET email = 'approver.email@domain.com' 
WHERE emp_id = 'APPROVER_ID';
```

### Issue 3: "Settlement record not found"
**Cause:** Settlement creation failed
**Solution:**
1. Check server error log for PHP errors
2. Verify `settlement_records` table exists
3. Check `emp_end_of_service.php` logs show settlement was attempted
4. Review SettlementManager_Corrected.php for SQL errors

### Issue 4: "SMTP settings missing"
**Cause:** Email configuration incomplete
**Solution:**
1. Login as administrator
2. Go to Settings/Configuration
3. Fill in all SMTP fields
4. Test SMTP connection if available

### Issue 5: "Settlement type not registered"
**Cause:** `approval_request_types` table missing settlement entry
**Solution:**
```sql
INSERT INTO approval_request_types (type_name, description) 
VALUES ('settlement', 'Settlement Records from EOS/Vacation/Loans');
```

## Testing Email Delivery

After fixing configuration issues:

1. **Create a test EOS:**
   - Submit EOS for a test employee
   - Check that SweetAlert2 shows
   - Check that settlement_records table has new entry

2. **Check Server Logs:**
   - Look for lines with "Settlement" or "SEND_EMAIL"
   - Check for "NOTIFICATION_DEBUG" entries
   - Look for SMTP connection errors

3. **Verify Settlement Created:**
   - Navigate to All Settlements page
   - Filter by "My Pending Approvals" 
   - New settlement should appear

4. **Check Email Was Sent:**
   - Check approver's email inbox
   - Check spam/junk folder
   - Review email headers for SMTP errors

## Manual Email Test

To manually test email sending, use this code in a PHP file:

```php
<?php
require_once 'includes/init.php';
require_once 'includes/helper_functions.php';

$conDB = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Get SMTP settings
$smtp_host = get_setting($conDB, 'smtp_host');
$smtp_port = get_setting($conDB, 'smtp_port');
$smtp_user = get_setting($conDB, 'smtp_user');
$smtp_pass = get_setting($conDB, 'smtp_pass');
$from_email = get_setting($conDB, 'from_email');
$from_name = get_setting($conDB, 'from_name', 'Al Mutlak HR');

echo "SMTP Configuration:<br>";
echo "Host: $smtp_host<br>";
echo "Port: $smtp_port<br>";
echo "User: $smtp_user<br>";
echo "From: $from_email ($from_name)<br><br>";

// Test email to a specific address
$test_email = 'approver@domain.com';
$result = send_approval_email($conDB, $test_email, 'Test Approver', 
    'Test Settlement Email', 
    'settlement_approval', 
    [
        'APPROVER_NAME' => 'Test Approver',
        'REQUEST_ID' => 'TEST-001',
        'EMPLOYEE_NAME' => 'Test Employee',
        'SETTLEMENT_AMOUNT' => '5000.00'
    ]
);

echo "Email send result: " . ($result ? 'SUCCESS' : 'FAILED');
?>
```

## Server Error Log Location

Check these locations for error messages:
- Windows XAMPP: `C:\xampp\apache\logs\error.log`
- Linux: `/var/log/apache2/error.log`
- Check PHP error log: `php.ini` `error_log` setting

## Support Information

If problems persist, provide to support:
1. Output of `debug_settlement_email.php?settlement_inv_no=SETL-YOUR-NUMBER`
2. Last 50 lines of server error log (filtered for "Settlement" or "SEND_EMAIL")
3. SMTP settings (host, port, encryption - without password)
4. List of approvers and their email addresses

---

**Last Updated:** February 2025
**System:** Al-Mutlak WMS v1.0
