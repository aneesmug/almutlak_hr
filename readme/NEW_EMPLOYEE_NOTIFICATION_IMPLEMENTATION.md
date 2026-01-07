# New Employee Registration - Email & Notification System Implementation

## Overview

A comprehensive notification system has been successfully implemented that automatically sends emails and browser notifications to HR and GR staff when a new employee is registered in the Al-Mutlak WMS system.

---

## Implementation Details

### 1. Email Template
**File:** [includes/PHPMailerMaster/new_employee_email_template.html](includes/PHPMailerMaster/new_employee_email_template.html)

- Professional HTML email template with company branding
- Displays complete employee information
- Includes action buttons to view employee profile and all employees
- Responsive design for mobile and desktop clients
- Contains 10 employee detail fields:
  - Employee Name & ID
  - Iqama/ID Number
  - Email & Mobile
  - Department & Position
  - Joining Date & Salary
  - Company

### 2. Notification Function
**File:** [includes/helper_functions.php](includes/helper_functions.php)

**Function Name:** `notify_hr_gr_new_employee()`

**Functionality:**
- Queries `admin_login` table for HR (`user_type = 'hr'`) and GR (`user_type = 'gm'`) staff
- Sends formatted email to each recipient using PHPMailer
- Creates browser notifications in `user_notifications` table for GR (GM) staff
- Logs all notifications in activity_log for audit trail
- Validates all input data before processing
- Handles errors gracefully without blocking employee creation

**Parameters:**
```php
$employee_data = [
    'emp_id' => employee ID,
    'name' => employee name,
    'email' => employee email,
    'mobile' => employee mobile,
    'iqama' => iqama number,
    'department_name' => department name,
    'job_title' => job title,
    'joining_date' => joining date,
    'salary' => salary,
    'comp_name' => company name,
]
```

**Return Value:**
```php
[
    'success' => bool,
    'email_count' => int (number of emails sent),
    'message' => string (result description)
]
```

### 3. Integration with New Employee Form
**File:** [new_comp_employee.php](new_comp_employee.php)

**Changes Made:**
- After successful employee creation (line ~115)
- Gathers all employee data including department, job, and company names
- Queries lookup tables to get display names
- Calls `notify_hr_gr_new_employee()` function
- Logs notification result (success/failure) to error log
- Notification system does NOT block employee creation if it fails

**Processing Flow:**
```
1. Employee form submitted
2. Data validated and cleaned
3. INSERT into employees table
4. Activity log created
5. Query department, job, company lookup tables
6. Call notify_hr_gr_new_employee()
7. Return success message with redirect to view_employee.php
```

### 4. Email Template Registration
**File:** [includes/helper_functions.php](includes/helper_functions.php)

**Updated:** `load_email_template()` function

Added support for 'new_employee' request type:
```php
'new_employee' => 'new_employee_email_template.html'
```

---

## Database Tables Used

### 1. admin_login
```sql
SELECT id, fullname, email, user_type, emp_id
FROM admin_login
WHERE status = 1 
AND (user_type = 'hr' OR user_type = 'gm')
AND email IS NOT NULL
AND email REGEXP '^[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Z|a-z]{2,}$'
```

**Criteria for Notification:**
- Active users only (status = 1)
- HR users (user_type = 'hr') - receive email only
- GR/GM users (user_type = 'gm') - receive email + browser notification
- Must have valid, non-empty email address
- Email format validated with regex

### 2. user_notifications (Browser Notifications)
```sql
INSERT INTO `user_notifications` 
(`emp_id`, `title`, `message`, `url`, `created_at`)
VALUES (?, ?, ?, ?, NOW())
```

**When Used:**
- Created for GR/GM staff (user_type = 'gm')
- Allows in-portal notifications
- Contains URL to employee profile

### 3. activity_log (Audit Trail)
```sql
INSERT INTO activity_log 
(emp_id, action, description, details, created_by, created_at)
VALUES (?, ?, ?, ?, ?, NOW())
```

**Logged Information:**
- New employee ID and name
- Recipient ID, name, and user type
- Timestamp of notification
- User who created the employee

### 4. Lookup Tables
- `department` - Department names (dep_nme_ar field)
- `ac_jobs` - Job titles (job_ar field)
- `companies` - Company names (comp_name field)

---

## Email Configuration

The system uses existing SMTP configuration from `app_settings` table:

```php
$smtp_host = get_setting($conDB, 'smtp_host');
$smtp_port = (int)get_setting($conDB, 'smtp_port');
$smtp_user = get_setting($conDB, 'smtp_user');
$smtp_pass = get_setting($conDB, 'smtp_pass');
$smtp_from_email = get_setting($conDB, 'from_email');
$smtp_from_name = get_setting($conDB, 'from_name', 'Al Mutlak HR System');
$smtp_secure = get_setting($conDB, 'smtp_encryption');
```

**Supported Encryption:**
- TLS (STARTTLS)
- SSL (SMTPS)
- None (plain connection)

---

## Recipient Categories

### HR Staff (user_type = 'hr')
- **Notifications Received:** Email only
- **Email Subject:** "New Employee Registered: [Employee Name]"
- **Use Case:** HR department notification of new hire

### GR/GM Staff (user_type = 'gm')
- **Notifications Received:** Email + Browser notification
- **Email Subject:** "New Employee Registered: [Employee Name]"
- **Browser Title:** "New Employee Registered"
- **Use Case:** General Relations department instant notification

---

## Data Flow Diagram

```
new_comp_employee.php
    ↓
Form Submission → Validation
    ↓
INSERT employees table
    ↓
ActivityLogger::logCreate()
    ↓
notify_hr_gr_new_employee() 
    ├─→ Query admin_login (HR + GR staff)
    ├─→ For each recipient:
    │   ├─→ send_approval_email() 
    │   │   └─→ PHPMailer sends email
    │   ├─→ If GR staff: create_browser_notification()
    │   │   └─→ INSERT into user_notifications
    │   └─→ activity_log INSERT (audit trail)
    └─→ Return result array
    ↓
view_employee.php?emp_id=X (redirect)
```

---

## Error Handling

### Email Sending Failures
- Individual email failures do NOT stop the process
- System continues to notify other recipients
- Only successful emails counted in result
- All errors logged to error_log

### Database Failures
- Notification system does NOT block employee creation
- Captured exception returns error message
- Execution continues, employee is still created
- Error logged for debugging

### Validation Failures
- Missing required fields: "Missing required field: [field_name]"
- Invalid database connection: "Database connection error"
- No recipients found: "No HR/GR staff found to notify"
- Query failed: "Database query failed: [error message]"

---

## Audit Trail

Every notification attempt is logged in `activity_log`:

```json
{
    "new_emp_id": "5678",
    "new_emp_name": "John Doe",
    "recipient_id": "1234",
    "recipient_name": "HR Manager",
    "recipient_type": "hr",
    "sent_at": "2025-01-20 14:30:45"
}
```

---

## Testing Checklist

- [ ] Create new employee with all required fields
- [ ] Verify email received by HR staff
- [ ] Verify email received by GR staff
- [ ] Verify browser notification created in user_notifications table
- [ ] Check activity_log contains notification records
- [ ] Test with invalid SMTP configuration (should not block employee creation)
- [ ] Test with no HR/GR staff in system
- [ ] Verify employee details in email match submitted form
- [ ] Test email links to view employee profile
- [ ] Test with multiple new employees created in sequence

---

## Files Modified

1. **[includes/PHPMailerMaster/new_employee_email_template.html](includes/PHPMailerMaster/new_employee_email_template.html)**
   - NEW: Email template for new employee notifications

2. **[includes/helper_functions.php](includes/helper_functions.php)**
   - UPDATED: Added 'new_employee' to template_map in load_email_template()
   - ADDED: notify_hr_gr_new_employee() function (180+ lines)
   - ADDED: Browser notification creation for GR staff

3. **[new_comp_employee.php](new_comp_employee.php)**
   - UPDATED: Added notification logic after employee creation (lines 115-168)
   - UPDATED: Queries lookup tables for department, job, company names
   - UPDATED: Calls notify_hr_gr_new_employee() function

---

## Future Enhancements

1. **Customizable Notification Templates**
   - Allow HR to customize email subject and content
   - Add company-specific branding

2. **Recipient Selection**
   - Allow specifying custom recipients
   - Department-specific notifications
   - Company-specific notifications

3. **Batch Notifications**
   - Notify when multiple employees created
   - Summary email with all new hires

4. **Notification Preferences**
   - Staff can opt-in/out of notifications
   - Choose notification methods (email only/both/browser only)
   - Set quiet hours

5. **Email Templates**
   - Separate templates for different notification types
   - Multi-language support
   - Rich formatting with employee photo

6. **Retry Logic**
   - Automatic retry of failed email deliveries
   - Queue system for pending notifications

---

## Security Considerations

✅ **Input Validation**
- All email addresses validated with regex
- Employee data sanitized before template insertion
- HTML special characters escaped in templates

✅ **SQL Injection Prevention**
- Prepared statements used for database queries
- PDO for new employee insertion
- mysqli_prepare() for lookup queries

✅ **Email Security**
- SMTP credentials used from secure settings
- TLS/SSL encryption support
- Email validation before sending

✅ **Audit Trail**
- All notifications logged with timestamps
- User tracking (who created employee)
- Recipient tracking for compliance

---

## Support & Maintenance

### For IT Support:
- Check error_log for email sending failures
- Verify SMTP settings in app_settings table
- Confirm admin_login table has active HR/GR staff
- Test email delivery with test employee creation

### Common Issues:

**No emails sent:**
- Verify SMTP credentials in app_settings
- Check email regex validation (user@domain.com format)
- Confirm HR/GR staff have status = 1 in admin_login

**Employees created but no notifications:**
- Check activity_log for error messages
- Verify user_notifications table exists
- Check error_log for PHP exceptions

**Email content incorrect:**
- Verify form data is properly submitted
- Check new_employee_email_template.html for template tags
- Ensure lookup tables have data (departments, jobs, companies)

---

## System Requirements

- PHP 8.x with PDO and MySQLi extensions
- MySQL 5.7+ with user_notifications table
- PHPMailer library in includes/vendor/
- SMTP server configured in app_settings
- Active HR and/or GR staff in admin_login table

---

**Implementation Date:** January 2025
**Version:** 1.0
**Status:** Production Ready

