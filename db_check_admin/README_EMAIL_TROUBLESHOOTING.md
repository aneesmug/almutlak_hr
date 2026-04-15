# Database Health Check - Email Troubleshooting Guide

## Recent Enhancements

This document describes the new email troubleshooting and timeout prevention features added to the Database Health Check system.

### 1. **Enhanced Timeout Protection** ⏱️

**What Changed:**
- Increased `set_time_limit()` to 30 seconds specifically for email generation operations
- Added PHPMailer `Timeout = 10` seconds to prevent hanging on SMTP connections
- Added output buffering to capture any errors during email send

**Files Modified:**
- `db_check_admin/index.php` - Lines 30-34 and 39-41
- `db_check_admin/TokenManager.php` - EmailSender::sendEmail() method

### 2. **SMTP Connection Test Tool** 🔧

**New File Added:**
- `db_check_admin/test_smtp.php`

**What It Tests:**
1. ✓ PHPMailer library availability
2. ✓ SMTP configuration completeness
3. ✓ DNS hostname resolution
4. ✓ Raw socket connection to SMTP server
5. ✓ PHPMailer SMTP connection attempt

**How to Use:**
```
Visit: /almutlak/system/db_check_admin/test_smtp.php
```

**What to Look For:**
- All tests should show ✓ (green check mark)
- If any test fails, shows the specific error message
- Provides troubleshooting tips at the bottom

### 3. **Automatic Email Fallback** 📧

**What Changed:**
- If SMTP connection fails, system automatically tries PHP's `mail()` function
- Keeps trying until one method succeeds
- Logs all attempts in `token_requests.log`

**Priority:**
1. Try SMTP (PHPMailer with Office365)
2. If fails → Try PHP mail() function
3. Both failures → Return error message

**Benefits:**
- More reliable email delivery
- Doesn't completely fail if Office365 is unavailable
- Can use alternative mail service if configured

### 4. **Better Error Messages** 💬

**What Changed:**
- Users now see clearer error messages when email fails
- Error messages indicate whether to check mail configuration
- Added "Troubleshooting" info box with link to SMTP test

**New Messages:**
```
"Token was generated but email delivery failed. 
Please try again or contact system administrator."
```

Plus link to: `SMTP Connection Status` test page

### 5. **Improved Logging** 📝

**What's Logged:**
- Token generation success/failure
- Email send attempts
- SMTP connection errors
- Fallback method attempts
- Exception details

**Log File:**
- `db_check_admin/token_requests.log`
- Automatically rotating (expired tokens cleaned)

---

## Troubleshooting Steps

### Step 1: Check SMTP Connectivity

Visit the SMTP test page:
```
http://your-site.com/almutlak/system/db_check_admin/test_smtp.php
```

**Expected Results:**
```
✓ PHPMailer Library - Found
✓ SMTP Configuration - Configured
✓ DNS Resolution - Resolved to [IP]
✓ Socket Connection - Connected
✓ PHPMailer SMTP Connect - Connected
```

### Step 2: Check Common Issues

**Issue: DNS Resolution Failed**
- Domain `smtp.office365.com` not reachable
- Solution: Check internet connectivity, DNS settings

**Issue: Socket Connection Failed**  
- Port 587 blocked by firewall
- Solution: Contact server admin to unblock outbound port 587

**Issue: PHPMailer SMTP Connect Failed**
- SMTP credentials incorrect
- Account not allowed (disabled/locked)
- Office365 OAuth2 required instead of password
- Solution: Verify email/password in `token_config.php`

**Issue: Email received but took long time**
- Normal for Office365 (1-5 minutes sometimes)
- Check spam/junk folder
- Wait and try again

### Step 3: Update SMTP Configuration

File: `db_check_admin/token_config.php`

```php
define('SMTP_HOST', 'smtp.office365.com');      // Office365 SMTP server
define('SMTP_PORT', 587);                       // TLS port
define('SMTP_SECURE', 'tls');                   // TLS encryption
define('SMTP_USER', 'noreply@almutlak.com');    // Office365 email
define('SMTP_PASS', 'YourPassword123#');        // Office365 password
```

**Important Notes:**
- Special characters in password must be typed exactly  
- Example: `@DiN512756539306#` should have `@`, `#` preserved
- If password fails, check Office365 account status
- May need app-specific password instead of account password

### Step 4: Test Token Generation

Go to: `http://your-site.com/almutlak/system/db_check_admin/`

1. Click "📧 Generate & Send Token"
2. Wait 10-30 seconds
3. Check for:
   - ✓ Success message: "token has been sent"
   - ⚠ Error message: Review troubleshooting above
   - 🔧 Click "SMTP Connection Status" link to test

### Step 5: Check Email Inbox

After token generation:
1. **Check Inbox** - Look for email from `noreply@almutlaksystem.com`
2. **Check Spam/Junk** - May be filtered as spam
3. **Token Content** - Email contains 32-character token
4. **Token Validity** - Valid for 30 minutes from generation time
5. **Copy Token** - Copy entire token (no spaces)

### Step 6: Enter Token in Dashboard

1. Go to: `http://your-site.com/almutlak/system/db_check_admin/`
2. Under "Step 2" - Paste token in text box
3. Click "✓ Verify & Access Dashboard"
4. If valid → Full database dashboard loads
5. If expired → Generate new token (only 30-min validity)

---

## Technical Details

### Token System Flow

```
User clicks "Generate Token"
        ↓
PHP generates 32-char random token
        ↓
Token saved to: db_check_admin/tokens.json
        ↓
Try to send via SMTP (PHPMailer)
        ├─ SUCCESS → Email delivered
        └─ FAIL → Try PHP mail() function
                 └─ SUCCESS → Email delivered
                 └─ FAIL → Error message returned
        ↓
Token stored with expiration time (30 min)
        ↓
User receives email with token
        ↓
User pastes token on page
        ↓
Token verified (not expired, not used)
        ↓
Dashboard loads with database health info
```

### Security Features

1. **Random Tokens** - 32-char cryptographically secure random bytes
2. **One-Time Use** - Token marked used after validation
3. **Time-Limited** - Tokens auto-expire after 30 minutes
4. **No Session** - Works without requiring user login
5. **Audit Trail** - All attempts logged with IP, timestamp
6. **Minimal Exposure** - Token sent via email only

### Files Overview

```
db_check_admin/
├── index.php                    # Main dashboard interface
├── TokenManager.php             # Token generation & email sending
├── token_config.php             # SMTP & user configuration
├── test_smtp.php               # SMTP connection testing tool
├── .htaccess                   # Bypass maintenance mode
├── tokens.json                 # Active tokens (auto-generated)
├── token_requests.log          # Audit trail (auto-generated)
└── ../PHPMailerMaster/         # Email library (existing)
```

---

## Email Debug Log

Check this file for detailed email operation logs:
```
db_check_admin/token_requests.log
```

Example log entries:
```
2026-04-08 14:30:45 | SUCCESS | Token Generated | Email: aneesmug2007@yahoo.com | Token: admin_he*** | IP: 192.168.1.100
2026-04-08 14:31:02 | SUCCESS | Token Verified | Email: aneesmug2007@yahoo.com | Token: admin_he*** | IP: 192.168.1.100
2026-04-08 14:35:10 | ERROR | Token Email Failed | Email: aneesmug2007@yahoo.com | IP: 192.168.1.100
```

---

## FAQ

**Q: How long does email take to arrive?**
A: Usually 1-5 seconds for Office365, occasionally up to 1-2 minutes

**Q: What if I don't receive the email?**
A: Check spam/junk folder, then run SMTP test to diagnose

**Q: Can I use a different email service?**
A: Yes, update SMTP settings in `token_config.php` with your provider's details

**Q: What if the token expires before I use it?**
A: Generate a new token (10 second process)

**Q: Is this system secure?**
A: Yes - random tokens, one-time use, time-limited, logged

**Q: Can multiple people access the dashboard?**
A: Yes, each gets their own token, but all go to same email address

---

## Quick Reference

| Component | Status | File |
|-----------|--------|------|
| Token Generation | ✓ Working | `TokenManager.php` |
| SMTP Connection | ⏳ To Test | `test_smtp.php` |
| Email Fallback | ✓ Active | `TokenManager.php` |
| Token Validation | ✓ Working | `TokenManager.php` |
| Dashboard Access | ✓ Working | `index.php` |
| Maintenance Mode | ✓ Available | `.htaccess` |

---

## Next Steps

1. **Test SMTP**: Visit `/db_check_admin/test_smtp.php`
2. **Generate Token**: Click button on main page
3. **Check Email**: Look in inbox for token
4. **Access Dashboard**: Paste token and verify
5. **Monitor**: Check `token_requests.log` for verification

---

## Support

If issues persist:

1. Check server error log: `error_log`
2. Check PHP error log: `php_errors.log`
3. Run SMTP connection test
4. Verify SMTP credentials
5. Contact server administrator for firewall/network access

---

**Last Updated:** 2026-04-08  
**System Version:** 1.0 with Email Fallback
