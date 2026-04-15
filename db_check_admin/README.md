# Database Health Check - Dynamic Token System

## Overview

This is a **secure, token-based database health check dashboard** located at `/db_check_admin/`. 

## How It Works

### The 3-Step Access Flow

**Step 1: Generate Token**
- Visit: `https://hr.almutlaksystem.com/db_check_admin/`
- Click "Generate & Send Token" button
- System generates a unique 32-character token

**Step 2: Receive Email**
- Check your email: **aneesmug2007@yahoo.com**
- Email contains the access token
- Token is valid for **30 minutes only**

**Step 3: Access Dashboard**
- Copy the token from email
- Paste into the prompt
- Click "Verify & Access Dashboard"
- View full database health information

## Quick Start

1. Go to: `https://hr.almutlaksystem.com/db_check_admin/`
2. Click **"Generate & Send Token"** 
3. Check email for token (aneesmug2007@yahoo.com)
4. Paste token and click **"Verify"**
5. Monitor database health!

## Security Features

✅ **Dynamic Token Generation** - Each session gets a new unique token
✅ **Email Verification** - Only admins with email access can get tokens
✅ **30-Minute Expiration** - Tokens automatically expire for security
✅ **No Session Required** - Works even when login system is down during maintenance
✅ **Access Logging** - All attempts recorded with IP and timestamp
✅ **No URL Exposure** - Token not permanently stored in bookmarkable URLs
✅ **.htaccess Override** - Bypasses maintenance mode restrictions
✅ **Auto Token Cleanup** - Expired tokens are automatically deleted

## File Structure

```
db_check_admin/
├── .htaccess              # Bypass main domain restrictions
├── .gitignore             # Protect logs and configs
├── config.php             # NOT USED (kept for compatibility)
├── index.php              # Main application with token flow ⭐
├── TokenManager.php       # Token generation & verification ⭐
├── token_config.php       # Email & token settings ⭐
├── tokens.json            # Auto-generated token storage
├── access_log.txt         # Auto-generated access log
├── token_requests.log     # Auto-generated token request log
└── README.md              # This file
```

**⭐ = New files for dynamic token system**

## Configuration

### Admin Email Address

Edit [token_config.php](token_config.php) to change the admin email:

```php
// Current setting
define('ADMIN_EMAIL', 'aneesmug2007@yahoo.com');

// Change to your email
define('ADMIN_EMAIL', 'your-email@example.com');
```

### Token Duration

To change how long tokens are valid (default 30 minutes):

```php
// In token_config.php
define('TOKEN_EXPIRATION_MINUTES', 30);

// Change to 60 minutes
define('TOKEN_EXPIRATION_MINUTES', 60);
```

### Sender Email Address

```php
define('SENDER_EMAIL', 'noreply@almutlaksystem.com');
define('SENDER_NAME', 'Al-Mutlak WMS');
```

## Using During Maintenance Mode

### Recommended Workflow

1. **Enable Maintenance Mode** - Edit `.htaccess`: Change `MAINTENANCE_MODE:OFF` to `MAINTENANCE_MODE:ON`
2. **Access Health Check** - Visit `https://hr.almutlaksystem.com/db_check_admin/`
3. **Generate Token** - Click button (token sent to your email)
4. **Enter Token** - Paste from email
5. **Monitor Database** - Watch for locks and query status
6. **Run Maintenance** - Create indexes, run checks, etc.
7. **Verify Completion** - Check SHOW FULL PROCESSLIST shows no locks
8. **Disable Maintenance** - Turn `MAINTENANCE_MODE:ON` back to `OFF`
9. **Test Login** - Confirm HR system works normally

## Dashboard Sections

### 1. Database Information
- Database name and MySQL version
- Connection status verification

### 2. Active Connections & Locks ⭐
- **SHOW FULL PROCESSLIST** results
- Identifies locked/waiting queries
- Critical for debugging slowdowns

### 3. All Tables Overview
- Row count per table
- Table size (data + index)
- Last update timestamps

### 4. Index Information
- All indexes across all tables
- Index column composition
- Verify new indexes created successfully

### 5. Storage Analysis
- Total database size
- Data vs. index storage
- Identify large tables

### 6. Key Performance Metrics
- Active threads
- Query statistics
- Lock information
- Connection usage

## Common Tasks

### Check for Database Locks

```
1. Visit: https://hr.almutlaksystem.com/db_check_admin/
2. Generate & enter token  
3. Scroll to "Active Connections & Locks"
4. Look for "Locked" or "Waiting" status
5. Note the ID of locked query
6. Copy provided KILL command and execute in MySQL
```

### Verify Index Was Created

```
1. Click to "Index Information" section
2. Search for your table name
3. Confirm new index appears in list
4. Check it has correct column(s)
```

### Monitor Table Growth

```
1. Go to "All Tables Overview"
2. Check "Size_MB" column
3. If table exceeds 500MB, consider archiving
4. Watch SIZE_MB growth week-over-week
```

## Email Configuration

### Testing Email Delivery

If tokens aren't being sent:

1. Check server mail logs: `/var/log/mail.log`
2. Verify PHP mail() is enabled in php.ini
3. Test manually:
   ```bash
   echo "test" | mail -s "Test" aneesmug2007@yahoo.com
   ```
4. Check mail server status: `service postfix status`

### Email Format

The email contains:
- ✉️ Subject: "Database Health Check - Your Access Token"
- 📋 Token (32 characters)
- 🔗 Direct access link
- ⏱️ 30-minute expiration notice
- ⚠️ Security warnings

## Troubleshooting

### "Token was generated but email delivery failed"

**Cause:** Mail server not running or misconfigured

**Fixes:**
1. Check mail service: `sudo service postfix status`
2. Check mail logs: `tail -20 /var/log/mail.log`
3. Verify SMTP in php.ini
4. Contact server administrator

### "Invalid or expired token"

**Cause:** Token expired or typo in copy/paste

**Solution:**
1. Generate a new token (30-min expiration)
2. Check email for latest token
3. Paste carefully (no leading/trailing spaces)
4. If needed, wait before requesting second token

### Dashboard Shows "Access Denied" After Token

**Cause:** Database connection problem

**Solution:**
1. Check database is running: `mysql -u root -p`
2. Verify credentials in [../includes/db.php](../includes/db.php)
3. Check database user privileges: `SHOW GRANTS`
4. Review PHP error logs

### "No data displayed" / Empty tables

**Cause:** Database query failed or no permission

**Solution:**
1. Verify database user has SELECT privilege
2. Check error logs in browser console (F12)
3. Test database connection manually
4. Confirm database exists and has tables

### Tokens.json Permission Denied

**Cause:** Folder not writable

**Solution:**
```bash
chmod 755 /path/to/db_check_admin/
chmod 666 /path/to/db_check_admin/tokens.json
```

## Security Best Practices

🔒 **DO:**
- ✅ Verify token received in email before using
- ✅ Use token immediately (don't wait until near expiry)
- ✅ Generate fresh token for each maintenance session  
- ✅ Check access logs weekly for suspicious IP addresses
- ✅ Use HTTPS on production (always https:// not http://)
- ✅ Keep email account secure (2FA recommended)

🚫 **DON'T:**
- ❌ Share tokens with colleagues
- ❌ Store tokens in documents/emails
- ❌ Bookmark the dashboard URL
- ❌ Use token URLs in scripts
- ❌ Leave dashboard open unattended
- ❌ Modify token generation code
- ❌ Disable email requirement

## Access Logs

### Token Request Log

Every token request is logged to `token_requests.log`:

```
2026-04-08 14:30:45 | SUCCESS | Token Generated | Email: aneesmug2007@yahoo.com | Token: admin_he*** | IP: 192.168.1.100
2026-04-08 14:31:12 | SUCCESS | Token Verified | Email: aneesmug2007@yahoo.com | IP: 192.168.1.100
2026-04-08 14:32:30 | WARNING | Invalid Token Attempt | Token: wrong_to*** | IP: 192.168.1.100
2026-04-08 15:05:00 | ERROR | Token Email Failed | Email: aneesmug2007@yahoo.com | IP: 192.168.1.100
```

### Monitoring Access

```bash
# View recent token requests
tail -50 token_requests.log

# Find failed attempts
grep "ERROR\|FAILED" token_requests.log

# Check requests from specific IP
grep "192.168.1" token_requests.log

# Count requests by status
grep "SUCCESS" token_requests.log | wc -l
```

## FAQ

**Q: Why email verification?**
A: Most secure method. Prevents brute-force attacks while allowing offline access.

**Q: Can I bookmark the dashboard?**
A: No. Tokens expire in 30 minutes. Generate fresh token each session.

**Q: What if email arrives late?**
A: Token remains valid for full 30 minutes from generation.

**Q: Can multiple people access at once?**
A: Yes. Each person generates their own unique token independently.

**Q: Does dashboard show password data?**
A: No. Only database structure info, table sizes, and monitoring metrics.

**Q: Which database does it connect to?**
A: The one in [../includes/db.php](../includes/db.php) - currently almutlak_hr_db.

**Q: Is the connection encrypted?**
A: Use HTTPS in URL (https:// not http://). Add SSL certificate to server.

**Q: Can I run malicious queries?**
A: No. Connection uses read-only database user with SELECT permission only.

## Technical Details

### Token Generation

- **Type:** Random/Secure
- **Length:** 32 characters (16 random bytes → hex)
- **Algorithm:** PHP `random_bytes()` + `bin2hex()`
- **Storage:** JSON file with expiration metadata
- **Cleanup:** Auto-removes expired tokens on each request

### Email Delivery

- **Method:** PHP mail() function
- **Format:** HTML with inline styling
- **Subject:** "Database Health Check - Your Access Token"
- **From:** noreply@almutlaksystem.com
- **To:** aneesmug2007@yahoo.com

### Data Stored

```
tokens.json:
{
  "abc123def456...": {
    "created": 1712596245,
    "expires": 1712597845,
    "used": false
  }
}

token_requests.log:
[timestamp] | [status] | [action] | [email] | [token preview] | [IP]

access_log.txt:
[timestamp] | [IP] | [page accessed]
```

## Maintenance Schedule

- **Daily:** Brief check of current operations
- **Weekly:** Review access logs for patterns
- **Monthly:** Check token generation statistics
- **Quarterly:** Test email system

## Support

For issues:
1. Check `db_check_admin/token_requests.log` for error messages
2. Review `../includes/db.php` for database configuration
3. Test mail server: `echo "test" | mail -s "test" aneesmug2007@yahoo.com`
4. Review PHP error logs: `/var/log/php-errors.log`
5. Check browser console (F12) for client-side errors
6. Contact server administrator if mail service is down

---

**Last Updated:** April 8, 2026
**Version:** 2.0 - Dynamic Token System  
**Maintenance Mode Compatible:** ✅ Yes
**Production Ready:** ✅ Yes
**Email Required:** ✅ Yes (aneesmug2007@yahoo.com)


## Security Features

✅ **Checkpoint Token Protection** - URL parameter verification prevents unauthorized access
✅ **Access Logging** - All access attempts (successful/failed) are logged
✅ **.htaccess Override** - Bypasses maintenance mode redirect for this folder
✅ **IP Logging** - Remote IP address is recorded for all attempts
✅ **Fail-Safe Access Denial** - Invalid tokens get HTTP 403 Forbidden response
✅ **No Session Required** - Works even when login system is down during maintenance

## How to Access

### Step 1: Enable Maintenance Mode (Recommended)
```
Edit .htaccess in root directory:
Change: RewriteRule .* - [E=MAINTENANCE_MODE:OFF]
To:     RewriteRule .* - [E=MAINTENANCE_MODE:ON]
```

### Step 2: Access the Health Check
Navigate to your browser with the checkpoint token:

```
http://localhost/almutlak/system/db_check_admin/index.php?checkpoint=admin_health_check_2026_secured
```

Or use HTTPS on production:
```
https://yourdomain.com/almutlak/system/db_check_admin/index.php?checkpoint=admin_health_check_2026_secured
```

## Customizing the Security Token

**⚠️ IMPORTANT:** Change the default token immediately for production use!

1. Open `db_check_admin/config.php`
2. Find this line:
   ```php
   define('DB_HEALTH_CHECK_TOKEN', 'admin_health_check_2026_secured');
   ```

3. Replace with your own secure token (suggest 20+ characters):
   ```php
   define('DB_HEALTH_CHECK_TOKEN', 'YourSecure#Token@2026!WithSpecialChars');
   ```

4. Save the file
5. Use the new token in your URL:
   ```
   http://localhost/almutlak/system/db_check_admin/index.php?checkpoint=YourSecure#Token@2026!WithSpecialChars
   ```

## File Structure

```
db_check_admin/
├── .htaccess          # Bypass main domain .htaccess, allows direct access
├── config.php         # Checkpoint token & security configuration
├── index.php          # Main health check dashboard (THIS PAGE)
├── access_log.txt     # Access attempt log (auto-generated)
└── README.md          # This file
```

## Access Log

All access attempts are logged to `db_check_admin/access_log.txt`:

```
2026-04-08 14:30:45 | 127.0.0.1 | SUCCESS | Token: admin_he***
2026-04-08 14:32:12 | 127.0.0.1 | FAILED | Token: wrong_to***
2026-04-08 14:35:01 | 192.168.1.100 | SUCCESS | Successfully accessed health check
```

Check this file periodically for unauthorized access attempts.

## Dashboard Sections

The health check dashboard includes:

1. **Database Information** - Database name and MySQL version
2. **Active Connections & Locks** - Current database activity and locks
3. **All Tables Overview** - Table statistics, sizes, row counts
4. **Index Information** - All indexes across all tables
5. **Storage Analysis** - Database size breakdown
6. **Key Performance Metrics** - System performance indicators
7. **Table Integrity Check** - CHECK, ANALYZE, OPTIMIZE commands
8. **Slow Query Log Status** - Slow query configuration
9. **Connection Limits** - Resource configuration
10. **Maintenance Recommendations** - What actions to take
11. **Reference Queries** - Helpful SQL snippets

## Usage During Maintenance

During maintenance mode, use this health check to:

1. **Monitor Active Queries**
   - Check `SHOW FULL PROCESSLIST` section for locks
   - Identify which queries are blocked

2. **Verify Index Creation**
   - Look for your new index in "Index Information" section
   - Confirm it appears after running `CREATE INDEX`

3. **Check Table Status**
   - Monitor table row counts and sizes
   - Verify data integrity

4. **Track Performance**
   - Watch for active connection counts
   - Monitor slow queries

## Security Best Practices

🔒 **DO:**
- ✅ Change the default token to something secure
- ✅ Store the token in a secure location (password manager)
- ✅ Enable maintenance mode while accessing this page in production
- ✅ Check access logs weekly for suspicious activity
- ✅ Use HTTPS on production servers
- ✅ Limit access to trusted IPs (see config.php commented sections)

🚫 **DON'T:**
- ❌ Share the checkpoint token via email or chat
- ❌ Use the same token across multiple servers
- ❌ Leave maintenance mode enabled longer than necessary
- ❌ Share the URL in unencrypted channels
- ❌ Commit the real token to version control

## Disabling After Use

### Step 1: Turn Off Maintenance Mode
Edit `.htaccess` in root directory:
```
Change: RewriteRule .* - [E=MAINTENANCE_MODE:ON]
To:     RewriteRule .* - [E=MAINTENANCE_MODE:OFF]
```

### Step 2: Verify System Status
- Confirm HR login works normally
- Check that queries are responsive
- Verify no table locks in health check dashboard

## Troubleshooting

### "Access Denied" Error
- Verify checkpoint token in URL exactly matches `config.php`
- Ensure you're using the complete token (case-sensitive)
- Check browser console for JavaScript errors

### Tokens Match But Still Denied
- Check database connection in `includes/db.php`
- Verify user has database SELECT privileges
- Check browser console for SQL errors

### Can't Find Access Log
- Log file is created automatically on first access
- Location: `db_check_admin/access_log.txt`
- Check folder permissions if not created

## Performance Notes

- Dashboard auto-refreshes every 5 minutes
- All queries are read-only (no modifications)
- Safe to run while system is live (if needed)
- SHOW FULL PROCESSLIST may slow down on many connections
- Disable auto-refresh for manual monitoring: Edit `index.php` line `setTimeout(...)`

## Support

If you encounter issues:
1. Check `db_check_admin/access_log.txt` for access attempts
2. Review `includes/db.php` for database connection errors
3. Verify checkpoint token in URL matches config.php exactly (case-sensitive)
4. Ensure the token parameter is properly URL-encoded if it contains special characters

---

**Last Updated:** April 8, 2026
**Version:** 1.0 - Secure Health Check
