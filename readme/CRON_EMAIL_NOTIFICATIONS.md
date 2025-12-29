# CRON Job Email Notifications - Complete Guide

## Overview

The vacation balance cron job now sends email notifications after each run. This guide explains how to set up and use this feature.

## What Gets Emailed

After the cron job runs, an email is automatically sent to the admin with:

### Email Content:
1. **Summary Statistics**
   - Total Employees Processed
   - Records Updated
   - Balances Changed
   - Errors Encountered

2. **Detailed Update Table** showing:
   - Employee ID
   - Employee Name
   - Old Balance (before)
   - New Balance (after)
   - Status (CHANGED or REFRESHED)

3. **Action Button** - "View Full Report" link to the online report

4. **Timestamp** - Exact date and time of the cron run

## Setup Instructions

### Step 1: Enable Email Notifications

Go to your Al-Mutlak HR System Admin Panel → Settings → Email Configuration

### Step 2: Configure Required Settings

You must configure these in **app_settings** table:

| Setting Name | Example Value | Description |
|---|---|---|
| `admin_email` | `admin@almutlak.com` | Where to send reports |
| `from_email` | `noreply@almutlak.com` | Sender email address |
| `from_name` | `Al-Mutlak HR System` | Sender display name |
| `cron_email_notify_enabled` | `1` | Enable (1) or disable (0) |

### Step 3 (Optional): Configure SMTP

For reliable email delivery, configure SMTP settings:

| Setting Name | Example Value | Description |
|---|---|---|
| `smtp_host` | `smtp.gmail.com` | SMTP server |
| `smtp_port` | `587` | Port (587 for TLS, 465 for SSL) |
| `smtp_user` | `your-email@gmail.com` | SMTP username |
| `smtp_pass` | `your-app-password` | SMTP password |
| `smtp_encryption` | `tls` | Encryption type (tls or ssl) |

## SQL Setup Script

Run this SQL to configure email settings:

```sql
-- Enable cron email notifications
INSERT INTO app_settings (setting_name, setting_value, created_at)
VALUES ('cron_email_notify_enabled', '1', NOW())
ON DUPLICATE KEY UPDATE setting_value = '1';

-- Set admin email
INSERT INTO app_settings (setting_name, setting_value, created_at)
VALUES ('admin_email', 'admin@almutlak.com', NOW())
ON DUPLICATE KEY UPDATE setting_value = 'admin@almutlak.com';

-- Set sender email
INSERT INTO app_settings (setting_name, setting_value, created_at)
VALUES ('from_email', 'noreply@almutlak.com', NOW())
ON DUPLICATE KEY UPDATE setting_value = 'noreply@almutlak.com';

-- Set sender name
INSERT INTO app_settings (setting_name, setting_value, created_at)
VALUES ('from_name', 'Al-Mutlak HR System', NOW())
ON DUPLICATE KEY UPDATE setting_value = 'Al-Mutlak HR System';
```

## Testing Email Functionality

### Test 1: Check Email Settings

```bash
D:\xampp\php\php.exe test_cron_email.php
```

This will show:
- ✓ or ❌ for each email setting
- Which email method will be used (PHPMailer or PHP mail)
- Last cron report details

### Test 2: Send Test Email

```bash
D:\xampp\php\php.exe send_cron_test_email.php
```

This sends a test email with sample data to verify delivery.

## Email Sending Methods

The system automatically uses the best available method:

### 1. PHPMailer + SMTP (Recommended for Production)
- **When used:** If SMTP settings are configured
- **Pros:** Most reliable, supports authentication, better error handling
- **Setup:** Configure SMTP settings in admin panel

### 2. PHP mail() Function (Default)
- **When used:** If SMTP settings are not configured
- **Pros:** Works on most servers, no extra setup
- **Uses:** Server's local mail configuration

## Troubleshooting

### Problem: Email Not Received

**Check these settings first:**

1. ✓ Verify `admin_email` is set to your email address
2. ✓ Verify `from_email` is configured
3. ✓ Verify `cron_email_notify_enabled` is set to `1`

**If using SMTP:**
4. ✓ Verify SMTP host is correct (e.g., smtp.gmail.com)
5. ✓ Verify SMTP port is correct (usually 587 or 465)
6. ✓ Verify username and password are correct
7. ✓ For Gmail, use App Password (not regular password)

**Check for errors:**
8. ✓ Run test file: `D:\xampp\php\php.exe test_cron_email.php`
9. ✓ Check PHP error logs for "CRON EMAIL" messages
10. ✓ Check email spam/junk folder

### Problem: "Admin email not configured"

- Go to Settings → Email Configuration
- Set `admin_email` to your email address
- Save

### Problem: SMTP Connection Failed

For Gmail:
1. Enable 2-Factor Authentication in your Google account
2. Create an App Password: https://myaccount.google.com/apppasswords
3. Use that App Password in `smtp_pass` (not your regular password)

## Disable Email Notifications (If Needed)

To temporarily disable emails without removing settings:

```sql
UPDATE app_settings SET setting_value = '0' WHERE setting_name = 'cron_email_notify_enabled';
```

To re-enable:

```sql
UPDATE app_settings SET setting_value = '1' WHERE setting_name = 'cron_email_notify_enabled';
```

## Email Format Examples

### Email Summary Section:
```
Total Employees: 445
Records Updated: 25
Balances Changed: 10
Errors: 0
```

### Sample Email Table Row:
```
Employee ID: 1061
Employee Name: ABU AL FOTOOH A MAJD A FATTAH
Old Balance: 32.64 days
New Balance: 32.85 days
Status: CHANGED
```

## How the System Works

1. **Cron Job Runs** → `cron_update_vacation_balances.php` executes
2. **Updates Processed** → Employee vacation balances are calculated and updated
3. **Report Saved** → Results saved to `cron_logs/last_vacation_update_report.json`
4. **Email Triggered** → Sends email with update details to admin
5. **HTML Report** → Still available at `/cron_update_vacation_balances.php`

## Files Created

- `cron_update_vacation_balances.php` - Main cron file (updated with email)
- `test_cron_email.php` - Email settings test tool
- `send_cron_test_email.php` - Send test email
- `CRON_EMAIL_SETTINGS.php` - Email settings reference
- `CRON_VACATION_BALANCE_USAGE.md` - Usage guide
- `CRON_QUICK_REFERENCE.php` - Quick reference

## Support

For issues or questions:
1. Run test files to diagnose the problem
2. Check the error logs
3. Review the troubleshooting section above
4. Ensure all required settings are configured

---

**Last Updated:** December 25, 2025
