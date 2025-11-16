# Email Notification Templates - Implementation Guide

## Overview
This document explains the new email notification system using professional dark-themed HTML templates for all request types in the Al-Mutlak HR System.

## Template Files Created

All templates are located in `includes/PHPMailerMaster/` directory:

1. **smart_request_email_template.html** - For Smart Requests
2. **vacation_request_email_template.html** - For Annual Vacation & Leave Requests  
3. **loan_request_email_template.html** - For Loan Requests

## Design Features

- **Dark Theme**: Professional dark background (#121212, #1e1e1e, #2a2a2a)
- **Responsive**: Mobile-friendly design
- **Consistent Branding**: Company logo, color scheme matching OTP email template
- **Professional Typography**: Clear hierarchy with proper font sizes and weights
- **Action Button**: Green "View Request" button for quick access
- **Information Box**: Structured display of request details

## Template Placeholders

### Common Placeholders (All Templates)
```
{{LOGO_URL}}          - Company logo URL
{{APPROVER_NAME}}     - Name of the approver receiving the email
{{REQUEST_ID}}        - Unique request identifier (INV number)
{{REQUEST_URL}}       - Direct link to view the request
```

### Smart Request Specific
```
{{REQUEST_TITLE}}     - Title/subject of the smart request
{{SUBMITTED_BY}}      - Name of person who submitted request
{{DEPARTMENT}}        - Department name
```

### Vacation/Leave Request Specific
```
{{REQUEST_TYPE}}      - "Annual Vacation Request" or "Sick Leave Request" etc.
{{REQUEST_TYPE_LOWER}}- lowercase version for sentence usage
{{EMPLOYEE_NAME}}     - Name of employee requesting vacation/leave
{{START_DATE}}        - Vacation/leave start date (formatted)
{{END_DATE}}          - Vacation/leave end date (formatted)
{{DURATION}}          - Number of days
```

### Loan Request Specific
```
{{EMPLOYEE_NAME}}     - Name of employee requesting loan
{{LOAN_TYPE}}         - Type of loan (housing, end_of_service, etc.)
{{LOAN_AMOUNT}}       - Amount in SAR (formatted with commas)
{{INSTALLMENTS}}      - Number of monthly installments
```

## Helper Functions

### 1. send_approval_email()
**Updated signature:**
```php
send_approval_email($conDB, $to_email, $to_name, $subject, $request_type = 'smart_request', $template_data = [])
```

**Parameters:**
- `$conDB` - Database connection
- `$to_email` - Recipient email address
- `$to_name` - Recipient name
- `$subject` - Email subject line
- `$request_type` - One of: 'smart_request', 'vacation_request', 'leave_request', 'loan_request'
- `$template_data` - Associative array of placeholder values

**Example Usage:**
```php
$template_data = [
    'APPROVER_NAME' => 'John Doe',
    'REQUEST_ID' => 'VAC-20251113-123',
    'EMPLOYEE_NAME' => 'Jane Smith',
    'START_DATE' => '15 Nov 2025',
    'END_DATE' => '20 Nov 2025',
    'DURATION' => 6,
    'REQUEST_URL' => 'https://example.com/all_applied_vac.php?status=my_pending'
];

send_approval_email($conDB, 'approver@example.com', 'John Doe', 
    'New Vacation Request Pending Approval', 
    'vacation_request', 
    $template_data);
```

### 2. load_email_template()
Loads HTML template and replaces placeholders.

**Signature:**
```php
load_email_template($request_type, $data = [])
```

**Returns:** HTML string or `false` on failure

### 3. get_base_url()
Gets the application base URL for constructing links.

**Returns:** String like `http://localhost/almutlak/system`

## Implementation Examples

### Vacation Request (ajaxVacation.php)
```php
// Get employee name
$employee_name = 'Employee';
$emp_result = mysqli_query($conDB, "SELECT emp_name FROM employees WHERE emp_id = '$emp_id' LIMIT 1");
if ($emp_result && $emp_row = mysqli_fetch_assoc($emp_result)) {
    $employee_name = $emp_row['emp_name'];
}
if ($emp_result) mysqli_free_result($emp_result);

// Prepare template data
$base_url = ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http') . 
            '://' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['SCRIPT_NAME'], 3);

$template_data = [
    'APPROVER_NAME' => $first_details['name'],
    'REQUEST_TYPE' => 'Annual Vacation Request',
    'REQUEST_TYPE_LOWER' => 'annual vacation request',
    'REQUEST_ID' => $request_inv_no,
    'EMPLOYEE_NAME' => $employee_name,
    'START_DATE' => date('d M Y', strtotime($start_date)),
    'END_DATE' => date('d M Y', strtotime($end_date)),
    'DURATION' => $vacdays,
    'REQUEST_URL' => $base_url . '/all_applied_vac.php?status=my_pending'
];

$email_subject = "New Annual Vacation Request Pending Approval";
$email_result = send_approval_email($conDB, $first_details['email'], $first_details['name'], 
    $email_subject, 'vacation_request', $template_data);
```

### Leave Request (ajaxVacation.php - applyLeave)
```php
$template_data = [
    'APPROVER_NAME' => $approver_details['name'],
    'REQUEST_TYPE' => ucfirst($leave_type) . ' Leave Request',
    'REQUEST_TYPE_LOWER' => strtolower($leave_type) . ' leave request',
    'REQUEST_ID' => $request_inv_no,
    'EMPLOYEE_NAME' => $employee_name,
    'START_DATE' => date('d M Y', strtotime($start_date)),
    'END_DATE' => date('d M Y', strtotime($end_date)),
    'DURATION' => $vacdays,
    'REQUEST_URL' => $base_url . '/all_applied_vac.php?status=my_pending'
];

$email_subject = "New " . ucfirst($leave_type) . " Leave Request Pending Approval";
$email_result = send_approval_email($conDB, $approver_details['email'], $approver_details['name'], 
    $email_subject, 'leave_request', $template_data);
```

### Loan Request (ajaxLoan.php)
```php
$template_data = [
    'APPROVER_NAME' => $first_approver_details['name'],
    'REQUEST_ID' => $inv_no,
    'EMPLOYEE_NAME' => $employee_name,
    'LOAN_TYPE' => str_replace('_', ' ', $loan_type),
    'LOAN_AMOUNT' => number_format($loan_amount, 2),
    'INSTALLMENTS' => $installments,
    'REQUEST_URL' => $base_url . '/all_applied_loan.php?status=my_pending'
];

$email_subject = "New Loan Request Pending Approval - " . ucfirst(str_replace('_', ' ', $loan_type));
$email_result = send_approval_email($conDB, $first_approver_details['email'], $first_approver_details['name'], 
    $email_subject, 'loan_request', $template_data);
```

## Configuration Requirements

### 1. Logo File
Ensure company logo exists at: `assets/images/logo.png`

If logo is in a different location, update the default in `load_email_template()`:
```php
'LOGO_URL' => $base_url . '/path/to/your/logo.png',
```

### 2. SMTP Settings
All settings are stored in `app_settings` table:
- `smtp_host` - SMTP server (e.g., smtp.office365.com)
- `smtp_port` - Port number (587 for TLS, 465 for SSL)
- `smtp_user` - SMTP username/email
- `smtp_pass` - SMTP password
- `smtp_encryption` - 'tls' or 'ssl'
- `from_email` - From email address
- `from_name` - From name (e.g., "Al Mutlak HR System")

## Testing

### Test Checklist
- [ ] Vacation request sends email with correct template
- [ ] Leave request sends email with correct template  
- [ ] Loan request sends email with correct template
- [ ] Smart request sends email with correct template (when implemented)
- [ ] All placeholders populated correctly
- [ ] Logo displays in email
- [ ] "View Request" button links work
- [ ] Email displays correctly in:
  - [ ] Outlook
  - [ ] Gmail
  - [ ] Mobile email clients
- [ ] UTF-8 characters display correctly (Arabic text if applicable)

### Debug Email Sending
Check PHP error logs for messages like:
```
send_approval_email: Email sent successfully to user@example.com
```

Or errors:
```
send_approval_email: Message could not be sent. Mailer Error: [details]
load_email_template: Template file not found: [path]
```

## Troubleshooting

### Email not sending
1. Check SMTP settings in database
2. Verify `from_email` and `from_name` are set
3. Check error logs: `C:\xampp\apache\logs\error.log`
4. Verify recipient has valid email in `admin_login` table

### Template not loading
1. Verify template file exists in `includes/PHPMailerMaster/`
2. Check file permissions
3. Look for error log: `load_email_template: Template file not found`

### Placeholders not replaced
1. Verify placeholder names match exactly (case-sensitive)
2. Check template_data array has all required keys
3. Ensure values are not empty

### Logo not displaying
1. Check logo file exists at specified path
2. Verify `LOGO_URL` includes full URL with protocol (http/https)
3. Test logo URL directly in browser

## Future Enhancements

### Smart Request Template Implementation
TODO: Update `open_request.php` to use template system instead of direct PHPMailer.

**Current:** Direct PHPMailer with old template
**Target:** Use `send_approval_email()` with 'smart_request' type

### Additional Request Types
To add new request types:
1. Create HTML template in `includes/PHPMailerMaster/`
2. Add mapping in `load_email_template()`:
   ```php
   $template_map = [
       'smart_request' => 'smart_request_email_template.html',
       'vacation_request' => 'vacation_request_email_template.html',
       'leave_request' => 'vacation_request_email_template.html',
       'loan_request' => 'loan_request_email_template.html',
       'your_new_type' => 'your_new_template.html'  // Add here
   ];
   ```
3. Update calling code to use new request type

## File Locations Summary

```
system/
├── includes/
│   ├── PHPMailerMaster/
│   │   ├── smart_request_email_template.html      (NEW)
│   │   ├── vacation_request_email_template.html   (NEW)
│   │   ├── loan_request_email_template.html       (NEW)
│   │   └── otp_email_template_dark.html          (Existing - reference)
│   ├── helper_functions.php                       (UPDATED)
│   └── ajaxFile/
│       ├── ajaxVacation.php                       (UPDATED)
│       └── ajaxLoan.php                          (UPDATED)
└── open_request.php                               (TODO)
```

## Maintenance Notes

### Updating Templates
To modify email design:
1. Edit HTML file in `includes/PHPMailerMaster/`
2. Keep placeholder syntax: `{{PLACEHOLDER_NAME}}`
3. Test in multiple email clients
4. Maintain dark theme colors for consistency

### Adding New Placeholders
1. Add to template HTML: `{{NEW_PLACEHOLDER}}`
2. Update this documentation
3. Pass value in `$template_data` array

---
**Last Updated:** November 13, 2025  
**Version:** 1.0
