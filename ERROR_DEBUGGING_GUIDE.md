# Error Debugging Guide for Al-Mutlak WMS

## Overview
This guide helps IT support identify and resolve "System Error" messages in the Al-Mutlak WMS application.

---

## Generic System Error: "An unexpected error occurred. Please contact IT support."

### Location
This error occurs when exceptions are caught in try-catch blocks throughout the system. The actual error details are logged to the PHP error log.

### Where to Find Error Details

**Option 1: PHP Error Log**
```
Location: /var/log/apache2/error.log (Linux)
         C:\xampp\apache\logs\error.log (Windows XAMPP)
```

**Option 2: XAMPP Log**
```
Location: C:\xampp\apache\logs\error.log
Command:  tail -f C:\xampp\apache\logs\error.log
```

**Option 3: PHP-FPM Log (if used)**
```
Location: /var/log/php-fpm.log
```

### Error Log Format
All system errors are logged with the following format:
```
[YYYY-MM-DD HH:MM:SS] [Module] [Function]: Error message details
```

Example:
```
[2025-12-15 14:32:45] Resignation submission error: Error: Database connection failed | File: /path/to/ajaxResignation.php | Line: 320
```

---

## Common Error Patterns

### 1. Database Connection Errors

**Error Message in Log:**
```
Database connection failed
SQLSTATE or mysqli_error details
```

**Solution:**
- Check database credentials in `/includes/db.php`
- Verify MySQL server is running
- Check database user permissions
- Verify database exists

### 2. SQL Query Errors

**Error Message in Log:**
```
Resignation insert error: [SQL Error Details]
Exit interview insert error: [SQL Error Details]
```

**Common Causes:**
- Column name mismatch
- Data type mismatch
- Missing required fields
- Constraint violations
- Table doesn't exist

**To Debug:**
1. Enable DEBUG_MODE in configuration
2. Check the SQL error message in logs
3. Verify table structure with: `DESCRIBE table_name;`
4. Test query manually in MySQL

### 3. ActivityLogger Errors

**Error Message in Log:**
```
Array to string conversion in /includes/init.php on line 176
```

**Fixed In:** Version 2.0+
- All logSubmit() calls now pass strings only
- All logApproval() calls now pass strings only
- logCreate(), logUpdate(), logDelete() correctly accept arrays

### 4. Missing Required Fields

**Error Handling Location:** AJAX files in `/includes/ajaxFile/`

**Check:**
- All POST parameters are validated before use
- Required fields are checked with `isset()` and `!empty()`
- Type casting is correct (int, float, string)

### 5. Permission Errors

**Related Files:**
- `/includes/session_check.php` - Session validation
- `/includes/validate_supervisor.php` - Role validation

**Check:**
- User session is valid
- User has correct role/permission level
- Employee supervisor is assigned (for vacation approvals)

---

## Resignation Module Specific Errors

### Error: "Failed to save resignation"

**Logged As:** 
```
Resignation insert error: [Database error details]
```

**Check:**
1. `emp_resignations` table exists and has correct structure
2. All required columns: emp_id, request_inv_no, last_working_day, submission_date, status
3. Employee exists with status = 1
4. No duplicate pending resignations for employee
5. Last working day is in future

### Error: "Failed to save exit interview"

**Logged As:**
```
Exit interview insert error: [Database error details]
```

**Check:**
1. `emp_exit_interviews` table exists
2. Required columns match expected schema
3. resignation_id from INSERT can be retrieved with mysqli_insert_id()
4. All 9 exit interview questions (q1-q9) are provided

### Error: "An unexpected error occurred" During Approval

**Logged As:**
```
Resignation approval error: Error: [Message] | File: [path] | Line: [number]
```

**Common Causes:**
- request_approvers table missing approval record
- User is not authenticated ($empid not set)
- Invalid resignation ID
- Database transaction failure

---

## Enabling Debug Mode

To see detailed error messages in AJAX responses (for development only):

```php
// Add to includes/init.php or config file
define('DEBUG_MODE', true);
```

**Warning:** Never enable DEBUG_MODE on production as it exposes internal system details.

---

## Error Logging Improvements (Recent Updates)

### Enhanced Error Messages
All AJAX error responses now include:
- `type`: Error type (error, warning, success)
- `title`: Error title
- `message`: User-friendly message
- `debug_info`: Detailed error (only if DEBUG_MODE = true)

### Example Improved Error Response:
```json
{
  "type": "error",
  "title": "Database Error",
  "message": "Failed to save resignation. Please try again.",
  "debug_info": "SQLSTATE[HY000]: General error: 1030 Got error 28 from storage engine"
}
```

---

## Related Database Tables

### Resignation Module
- `emp_resignations` - Main resignation records
- `emp_exit_interviews` - Exit interview answers
- `request_approvers` - Approval chain
- `approval_comments` - Approval comments (if saved)
- `activity_log` - Activity audit trail

### Vacation Module
- `emp_vacation` - Vacation applications
- `emp_vacation_balance` - Vacation balance tracking
- `approval_comments` - Approval comments
- `activity_log` - Activity audit trail

---

## Quick Troubleshooting Checklist

- [ ] Check PHP error log for detailed error message
- [ ] Verify database connection is working
- [ ] Confirm all required tables exist
- [ ] Check employee exists with correct status
- [ ] Verify user has required permissions
- [ ] Check for missing required POST parameters
- [ ] Review recent schema changes
- [ ] Test manually in MySQL client
- [ ] Review recent code changes in AJAX files
- [ ] Check file permissions on log files

---

## Contact & Support

For unresolved errors:
1. Collect full error log output
2. Include HTTP request details (method, POST data)
3. Include browser console JavaScript errors
4. Include database structure (DESCRIBE table_name)
5. Include recent code changes

Email to: IT Support

---

## Version History

**v2.1 - 2025-12-15**
- Enhanced error logging with file/line information
- Added database error details to responses
- Improved ActivityLogger error detection

**v2.0 - 2025-12-15**
- Fixed ActivityLogger array-to-string conversion errors
- Added comprehensive error handling across AJAX files
- Implemented activity logging for all approvals

**v1.0 - Initial Release**
- Basic error handling
- Activity logging foundation
