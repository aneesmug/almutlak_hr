# New Employee Registration - Quick Reference Guide

## What Happens When You Create a New Employee?

When you fill out the new employee form in **new_comp_employee.php** and click **"Yes, Register"**, the system automatically:

### 1. ✅ Saves Employee to Database
- All employee information stored in `employees` table
- Activity log created for audit trail
- Unique employee ID assigned

### 2. 📧 Sends Emails
- Email sent to ALL active HR staff (user_type = 'hr')
- Email sent to ALL active GR staff (user_type = 'gm')
- Contains complete employee details with formatted salary
- Professional HTML email with company logo and branding

### 3. 🔔 Browser Notifications
- Dashboard notification created for GR staff (user_type = 'gm')
- Employees see notification in their portal
- Direct link to view new employee profile

### 4. 📝 Audit Logging
- Activity log records:
  - New employee ID and name
  - Each recipient who was notified
  - Recipient type (HR or GR)
  - Exact timestamp of notification

---

## Who Gets Notified?

| User Type | Email | Browser Notification | Notes |
|-----------|-------|---------------------|-------|
| HR Staff (hr) | ✅ Yes | ❌ No | HR Department |
| GR Staff (gm) | ✅ Yes | ✅ Yes | General Relations |
| Others | ❌ No | ❌ No | Not notified |

**Requirements:**
- User must have status = 1 (active)
- User must have valid email address
- Email must be in proper format (user@domain.com)

---

## Email Content

The notification email includes:

**Header:**
- Company logo
- "New Employee Registration" title
- Brief description

**Employee Details Section:**
- Employee Name
- Employee ID
- Iqama/ID Number
- Email Address
- Mobile Number
- Department
- Position/Job Title
- Joining Date
- Salary (formatted with commas)
- Company Name

**Action Buttons:**
- "View Employee Profile" - Opens employee details page
- "View All Employees" - Opens employee list

**Footer:**
- Automated notification disclaimer
- Support contact information

---

## Browser Notification Details

For GR Staff (gm):
- **Title:** "New Employee Registered"
- **Message:** "Employee: [Name] (ID: [ID]) has been registered in the system"
- **Action:** Click to view employee profile

Located in: Employee's dashboard notifications panel

---

## Database Tables Involved

### admin_login
- Source of HR and GR staff recipients
- Query: Finds all active users with email addresses

### user_notifications
- Stores browser notifications for GR staff
- Automatically created by system
- Employees see in portal

### activity_log
- Audit trail of all notifications
- Records who was notified and when
- Useful for compliance and troubleshooting

### lookup tables (queries during notification)
- **department** → Department name
- **ac_jobs** → Job title
- **companies** → Company name

---

## Troubleshooting

### ❌ Emails not received?

**Check 1: SMTP Configuration**
```
Settings → Email Configuration
Verify:
- SMTP Host
- SMTP Port
- Username & Password
- From Email Address
```

**Check 2: HR/GR Staff**
```
Admin → Users
Verify:
- HR/GR staff exist in system
- Their status is ACTIVE (1)
- They have valid email addresses
```

**Check 3: Email Format**
```
Email must be: user@domain.com
NOT: user@domain
NOT: @domain.com
NOT: empty field
```

### ❌ Employee created but no notification?

1. Open employee record
2. Check employee ID in URL
3. Open **activity_log** table
4. Search for employee ID
5. Look for 'new_employee_notification_sent' action
6. Check details column for error messages

### ❌ Browser notification not showing?

1. Verify user_type = 'gm' in admin_login
2. Check user_notifications table for records
3. Refresh dashboard page
4. Check browser notifications panel

---

## Data Sanitization

All employee information is automatically cleaned before sending in emails:

✅ **HTML Special Characters Escaped**
- < becomes &lt;
- > becomes &gt;
- " becomes &quot;

✅ **Phone Numbers Validated**
- Only digits extracted
- Proper format enforced

✅ **Email Addresses Validated**
- Format checked before use
- Invalid addresses rejected

✅ **SQL Injection Prevention**
- All database queries use prepared statements
- Parameter binding used everywhere

---

## Activity Log Example

```json
{
  "emp_id": 5678,
  "action": "new_employee_notification_sent",
  "description": "Notification sent to HR/GR staff for new employee",
  "details": {
    "new_emp_id": "5678",
    "new_emp_name": "Ahmed Mohamed",
    "recipient_id": "1234",
    "recipient_name": "Fatima Al-Mutlak",
    "recipient_type": "hr",
    "sent_at": "2025-01-20 14:30:45"
  },
  "created_by": "2539",
  "created_at": "2025-01-20 14:30:45"
}
```

---

## If Something Goes Wrong

**Employee creation still succeeds!**

The notification system is designed to NOT block employee creation. Even if:
- Email fails to send
- Database error occurs
- SMTP server unreachable
- Browser notification fails

**The employee WILL STILL BE CREATED.**

Only the notifications might fail, and this is logged for later investigation.

---

## For System Administrators

### Adding New HR/GR Staff

When adding new users to the system:

1. Create user in admin_login table
2. Set **user_type** to:
   - 'hr' for HR Department
   - 'gm' for General Relations
3. Set **status** = 1 (active)
4. Ensure valid **email** is entered
5. User will automatically receive notifications for next new employee

### Testing Notification System

```sql
-- Test: How many HR/GR staff will be notified?
SELECT COUNT(*) as notification_count,
       GROUP_CONCAT(fullname) as recipients
FROM admin_login
WHERE status = 1
AND (user_type = 'hr' OR user_type = 'gm')
AND email IS NOT NULL
AND email != ''
AND email REGEXP '^[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Z|a-z]{2,}$';

-- Check recent notifications:
SELECT * FROM activity_log
WHERE action = 'new_employee_notification_sent'
ORDER BY created_at DESC
LIMIT 10;

-- Check browser notifications:
SELECT * FROM user_notifications
WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
ORDER BY created_at DESC;
```

---

## Performance Impact

- **Fast:** Notification process takes < 2 seconds
- **Concurrent:** Multiple staff can create employees simultaneously
- **Scalable:** Works with 1 or 1000 HR/GR staff
- **Non-blocking:** Failures don't affect user experience

---

## Multi-Language Support

Email template currently supports:
- English (header and content)
- Arabic placeholders (department and job fields use Arabic names if available)

Future: Full multi-language template variants

---

## Email Subject Lines

Format: `"New Employee Registered: [Employee Name]"`

Example: `"New Employee Registered: Ahmed Mohamed Al-Mutlak"`

---

## Compliance & Audit

✅ All notifications logged
✅ Timestamp recorded
✅ User tracking (who created employee)
✅ Recipient tracking
✅ Email content preserved
✅ Activity searchable by employee ID

---

**Last Updated:** January 2025
**Version:** 1.0

