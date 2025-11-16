# Travel Company Email Notification Feature

## Overview
This feature automatically sends employee travel information to a designated traveling company email address when an annual fly vacation is approved.

## Components Created/Modified

### 1. Database Changes
**File:** `add_traveling_company_email_setting.sql`
- Adds a new setting to the `app_settings` table
- Setting name: `traveling_company_email`
- Purpose: Stores the email address of the traveling company

**Installation:**
```sql
-- Run this SQL in your database
mysql -u root -p almutlak_db < add_traveling_company_email_setting.sql

-- Then update the email address
UPDATE `app_settings` 
SET `setting_value` = 'travel@yourcompany.com' 
WHERE `setting_name` = 'traveling_company_email';
```

### 2. New Functions

#### `send_travel_company_email()` 
**Location:** `includes/helper_functions.php` (line ~2923)

**Purpose:** Sends formatted email with employee travel details to the traveling company

**Parameters:**
- `$conDB` - Database connection object
- `$employee_name` - Employee's full name
- `$passport_no` - Employee's passport number
- `$passport_expiry` - Passport expiration date
- `$country_name` - Destination country name
- `$departure_date` - Flight departure date
- `$arrival_date` - Flight return/arrival date
- `$request_inv_no` - Vacation request invoice number (for reference)

**Returns:** `bool` - True if email sent successfully, false otherwise

**Example Usage:**
```php
$success = send_travel_company_email(
    $conDB,
    'John Doe',
    'AB1234567',
    '2026-12-31',
    'Philippines',
    '2025-12-01',
    '2025-12-15',
    'VAC-2025-001'
);
```

### 3. Modified Files

#### `vacation_report_details.php`
- **Added:** `departure_date` and `arrival_date` fields to the SELECT query
- **Added:** `passport_number` and `passport_exp` fields to the SELECT query
- **Display:** Flight dates are shown only for Annual Fly vacations
- **Location:** Lines 26-37 (SQL query), Lines 355-362 (display)

#### `vacation_status_history.php`
- **Added:** `passport_number` and `passport_exp` to the SELECT query
- **Display:** Flight dates shown in vacation details section
- **Location:** Lines 15-16 (SQL), Lines 169-174 (display)

#### `all_applied_vac.php`
- **Display:** Flight dates shown in vacation request cards
- **Condition:** Only displayed for Annual Fly vacations
- **Location:** Lines 465-470

#### `includes/helper_functions.php`
- **Added:** `send_travel_company_email()` function (lines 2923-3107)
- **Modified:** `handle_approval_action()` function to trigger travel email on final approval
- **Location:** Lines 1816-1856 (email trigger logic)

### 4. Standalone Email Sender Script

**File:** `send_travel_email.php`

**Purpose:** Can be used to manually send travel emails or called via AJAX

**Usage:**
```php
// POST or GET request
POST /send_travel_email.php
{
    "vacation_id": 123
}
```

**Response:**
```json
{
    "success": true,
    "message": "Travel information email sent successfully to traveling company"
}
```

## Email Template

The email sent to the traveling company includes:

1. **Header:** Professional gradient header with title
2. **Employee Information Table:**
   - Traveler Name
   - Passport No
   - Passport Expiry
   - Departure To (Country)
   - Departure Date
   - Arrival Date
3. **Reference Number:** Vacation request invoice number
4. **Footer:** Automated email notice

**Email Subject Format:**
```
Employee Travel Information - [Employee Name] - Ref: [Request Invoice No]
```

## Workflow Integration

### Automatic Trigger
The email is automatically sent when:
1. A vacation request is **fully approved** (all approvers have approved)
2. The vacation type is **"Fly"**
3. The fly type is **"annual"** (not emergency)
4. Both **departure_date** and **arrival_date** are provided

### Trigger Location
- **File:** `includes/helper_functions.php`
- **Function:** `handle_approval_action()`
- **Lines:** 1816-1856
- **Condition:** After final approval, before creator notification

### Code Flow
```
Final Approval Complete
    ↓
Check if request_type === 'vacation_request'
    ↓
Fetch vacation details (vac_type, fly_type, dates, passport info)
    ↓
Validate: Fly + Annual + Has flight dates
    ↓
Call send_travel_company_email()
    ↓
Log success/failure
    ↓
Continue with creator notification
```

## Configuration Requirements

### 1. SMTP Settings (must be configured in `app_settings` table)
- `smtp_host` - SMTP server hostname
- `smtp_port` - SMTP port (usually 587 for TLS, 465 for SSL)
- `smtp_user` - SMTP username
- `smtp_pass` - SMTP password
- `from_email` - Sender email address
- `from_name` - Sender name (default: "Al Mutlak HR System")
- `smtp_encryption` - Encryption type ('tls', 'ssl', or empty)

### 2. Traveling Company Email
- `traveling_company_email` - Email address of the travel company

**Check Configuration:**
```sql
SELECT * FROM app_settings 
WHERE setting_name IN (
    'smtp_host', 'smtp_port', 'smtp_user', 'from_email', 
    'traveling_company_email'
);
```

## Display Features

### Vacation Report Details Page
- Shows departure and arrival dates below Start Date and Return Date
- Only visible for Annual Fly vacations
- Formatted as: `dd MMM YYYY` (e.g., "15 Dec 2025")

### Vacation Status History Page
- Displays flight dates in the vacation details section
- Icons: ✈️ (plane-departure) and 🛬 (plane-arrival)
- Conditional display based on vacation type

### All Applied Vacations Page
- Flight dates shown in vacation cards
- Appears between Return Date and Total Days
- Uses Font Awesome duotone icons

## Testing

### Manual Test
1. Create a test vacation request:
   - Type: Fly
   - Fly Type: Annual
   - Fill in Departure Date and Arrival Date
2. Complete the full approval chain
3. Check if email was sent (check logs)

### Test Email Function Directly
```php
// In a test file
require_once 'includes/db.php';
require_once 'includes/helper_functions.php';

$result = send_travel_company_email(
    $conDB,
    'Test Employee',
    'TEST123456',
    '2026-12-31',
    'Saudi Arabia',
    '2025-12-01',
    '2025-12-15',
    'TEST-001'
);

echo $result ? 'Success' : 'Failed';
```

### Check Logs
```bash
# PHP error log
tail -f D:\xampp\php\logs\php_error_log

# Apache error log
tail -f D:\xampp\apache\logs\error.log
```

**Expected Log Messages:**
- Success: `"Travel company email sent successfully for vacation request: VAC-XXX-XXX"`
- Failure: `"Failed to send travel company email for vacation request: VAC-XXX-XXX"`

## Troubleshooting

### Email Not Sending

**1. Check traveling_company_email setting:**
```sql
SELECT setting_value FROM app_settings 
WHERE setting_name = 'traveling_company_email';
```
- Should be a valid email address
- Should not be empty

**2. Check SMTP settings:**
```sql
SELECT setting_name, setting_value FROM app_settings 
WHERE setting_name LIKE 'smtp_%' OR setting_name LIKE 'from_%';
```

**3. Check error logs:**
```bash
tail -f D:\xampp\apache\logs\error.log | grep "send_travel_company_email"
```

**4. Test PHPMailer directly:**
```php
// Test if PHPMailer is loaded
var_dump(class_exists('PHPMailer\\PHPMailer\\PHPMailer'));
```

### Flight Dates Not Showing

**1. Check if data exists in database:**
```sql
SELECT id, emp_id, vac_type, fly_type, departure_date, arrival_date 
FROM emp_vacation 
WHERE departure_date IS NOT NULL;
```

**2. Verify vacation type:**
- Must be `vac_type = 'Fly'`
- Must be `fly_type = 'annual'`

**3. Check page conditions:**
- Review lines 355-362 in `vacation_report_details.php`
- Conditions: `!empty($request['departure_date'])` AND `$request['vac_type'] === 'Fly'` AND `$request['raw_fly_type'] === 'annual'`

### Email Sent But Not Received

**1. Check spam folder**

**2. Verify email address:**
```sql
SELECT setting_value FROM app_settings 
WHERE setting_name = 'traveling_company_email';
```

**3. Check SMTP logs:**
- Enable SMTP debugging in PHPMailer (for development only)
- Check mail server logs

## Security Considerations

1. **Email Address Validation:**
   - Function validates email format using `FILTER_VALIDATE_EMAIL`
   - Empty emails are rejected

2. **HTML Escaping:**
   - All user data is escaped with `htmlspecialchars()`
   - Prevents XSS attacks in email content

3. **SQL Injection Prevention:**
   - Uses prepared statements where applicable
   - Escapes data with `mysqli_real_escape_string()`

4. **Error Logging:**
   - Errors logged to PHP error log
   - Sensitive data (passwords) not logged

## Maintenance

### Update Email Template
- Edit the `$body_html` variable in `send_travel_company_email()` function
- Location: `includes/helper_functions.php` lines 2975-3050

### Change Email Trigger Conditions
- Edit the conditions in `handle_approval_action()` function
- Location: `includes/helper_functions.php` lines 1818-1854

### Add Email Logging (Optional)
Create an email_logs table:
```sql
CREATE TABLE IF NOT EXISTS `email_logs` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `request_inv_no` VARCHAR(100),
    `email_type` VARCHAR(50),
    `sent_to` VARCHAR(255),
    `sent_at` DATETIME,
    `status` ENUM('sent', 'failed') DEFAULT 'sent',
    `error_message` TEXT NULL,
    INDEX idx_request_inv_no (request_inv_no),
    INDEX idx_sent_at (sent_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

## Summary

### Files Created
1. `add_traveling_company_email_setting.sql` - Database migration
2. `send_travel_email.php` - Standalone email sender script
3. `TRAVEL_EMAIL_DOCUMENTATION.md` - This documentation

### Files Modified
1. `vacation_report_details.php` - Display flight dates
2. `vacation_status_history.php` - Display flight dates
3. `all_applied_vac.php` - Display flight dates in listing
4. `includes/helper_functions.php` - Added email function and trigger

### Database Changes
1. New setting: `traveling_company_email` in `app_settings` table

### Key Features
- ✅ Automatic email on final approval
- ✅ Professional HTML email template
- ✅ Flight dates displayed in 3 pages
- ✅ Passport information in emails
- ✅ Comprehensive error logging
- ✅ Manual email sending option
- ✅ Security: Email validation, HTML escaping, SQL injection prevention

### Next Steps
1. Run the SQL migration: `add_traveling_company_email_setting.sql`
2. Update the traveling company email in app_settings
3. Test with a sample vacation approval
4. Monitor logs for any errors
5. (Optional) Create email_logs table for tracking
