# Resignation Approval System - Complete Guide

## Overview
Complete employee resignation management system with multi-step approval workflow, exit interviews, and email notifications.

## System Components

### 1. Database Tables (5 tables)
Located in: `migration_resignation_tables.sql`

- **emp_resignations** - Main resignation records
- **emp_exit_interviews** - 9-question exit interview responses
- **emp_resignation_attachments** - File attachments
- **emp_resignation_history** - Audit trail for all actions
- **emp_resignation_clearance** - Employee clearance checklist

### 2. Database Schema Update
**File**: `update_resignation_columns.sql`

Run this SQL to add approval workflow columns:
- `approved_by`, `approved_at`
- `rejected_by`, `rejected_at`, `rejection_reason`
- `needs_replacement` (boolean)
- `replacement_data` (JSON)

### 3. Frontend Files

#### Employee Resignation Form
**File**: `apply_resignation.php` (assumed to exist)
**JavaScript**: `assets/js/resignationWizard.js`

Features:
- Two-step wizard (Personal Info → Exit Interview)
- 9 exit interview questions
- Last working day selection
- Real-time validation

#### All Resignations Listing
**File**: `all_resignations.php`
**JavaScript**: `assets/js/resignationApproval.js`

Features:
- Permission-based access control
- Stats dashboard (total, pending, approved, rejected)
- Search and filter functionality
- Action buttons: View, Approve, Reject

### 4. Backend Handler
**File**: `includes/ajaxFile/ajaxResignation.php`

Endpoints:
1. `apply_resignation` - Submit new resignation
2. `get_resignation_status` - Check employee's active resignation
3. `cancel_resignation` - Cancel pending resignation
4. `approve_resignation` - Approve with multi-level forwarding
5. `reject_resignation` - Reject with reason

### 5. Email System
**Template File**: `includes/PHPMailerMaster/resignation_request_email_template.html`
**Helper Function**: Modified in `includes/helper_functions.php`

Email sent to:
- Direct manager (on submission)
- HR Operations (after manager approval)
- HR Payroll (after HR Ops approval)
- Employee (on final approval/rejection)

## Approval Workflow

### Step 1: Employee Submission
1. Employee fills resignation form (2-step wizard)
2. System validates and saves to database
3. Email sent to **direct manager**
4. Browser notification created

### Step 2: Manager Approval
**SweetAlert2 Multi-Step Wizard:**

**Modal 1 - Employee & Resignation Info:**
- Employee ID, Name, Department, Designation
- Last Working Day
- Buttons: NEXT | REJECT

**Modal 2 - Replacement Question:**
- "Do you need a replacement employee?"
- Options: YES | NO
- Buttons: BACK | NEXT

**Modal 3 - Replacement Details (if YES):**
- Job Title
- Job Description
- Experience Required
- Certificate Required
- Academic Achievement
- Date of Joining
- Buttons: BACK | APPROVE

**Modal 4 - Final Confirmation (if NO):**
- Confirm approval without replacement
- Buttons: BACK | APPROVE

**On Approval:**
- Email sent to **HR Operations**
- Browser notification created

### Step 3: HR Operations Approval
- Same wizard flow as manager
- Can add/modify replacement info
- On approval: Email sent to **HR Payroll**

### Step 4: HR Payroll Final Approval
- Same wizard flow
- Final approval sets status to "approved"
- Employee receives notification

### Rejection at Any Level
- Requires rejection reason (textarea)
- Employee receives notification
- Process ends

## Permission Levels

### Admin / HR / GM
- View all resignations
- Approve/reject any resignation

### Department Manager
- View resignations from their department
- Approve/reject dept resignations

### Regular Employee
- View only their own resignations
- Cancel pending resignations

## Email Template Placeholders

```
{{LOGO_URL}}
{{APPROVER_NAME}}
{{EMP_ID}}
{{EMP_NAME}}
{{DEPARTMENT}}
{{DESIGNATION}}
{{RESIGNATION_ID}}
{{LAST_WORKING_DAY}}
{{SUBMISSION_DATE}}
{{REQUEST_URL}}
```

## JavaScript Functions Reference

### resignationWizard.js (Employee Form)
- `initResignationWizard()` - Initialize multi-step form
- `validateStep1()` - Validate personal information
- `validateStep2()` - Validate exit interview
- `submitResignation()` - AJAX submission

### resignationApproval.js (Admin/Manager)
- `showStep1EmployeeInfo(data)` - Display employee info
- `showStep2ReplacementInfo(data)` - Ask replacement question
- `showStep3ReplacementDetails(data)` - Collect 6 replacement fields
- `showFinalApprovalConfirmation(data)` - Confirm no replacement
- `submitApproval(resignationId, replacementData)` - AJAX approval
- `submitRejection(resignationId, reason)` - AJAX rejection
- `generateResignationDetailsHTML(data)` - View modal

## Installation Steps

### 1. Database Setup
```sql
-- Run these SQL files in order:
source migration_resignation_tables.sql
source update_resignation_columns.sql
```

### 2. File Upload
Upload all files maintaining directory structure:
```
system/
├── apply_resignation.php
├── all_resignations.php
├── update_resignation_columns.sql
├── migration_resignation_tables.sql
├── assets/
│   └── js/
│       ├── resignationWizard.js
│       └── resignationApproval.js
└── includes/
    ├── helper_functions.php (modified)
    ├── ajaxFile/
    │   └── ajaxResignation.php
    └── PHPMailerMaster/
        └── resignation_request_email_template.html
```

### 3. Configuration
No configuration needed - uses existing:
- Database connection (`$conDB`)
- Session management
- Email system (PHPMailer)
- Helper functions

### 4. Permissions Setup
Ensure these user types exist in `admin_login`:
- `hr_operations`
- `hr_payroll`
- `admin`

## Usage Examples

### Employee Submits Resignation
```javascript
// Frontend automatically handles:
// 1. Form validation
// 2. AJAX submission
// 3. Success/error messages
```

### Manager Approves
```javascript
// Click "Approve" button
// → Step 1: Review info → Click NEXT
// → Step 2: Select "YES" for replacement
// → Step 3: Fill 6 fields → Click APPROVE
// → System forwards to HR Operations
```

### HR Rejects
```javascript
// Click "Reject" button
// → Enter rejection reason
// → Click "Submit Rejection"
// → Employee receives notification
```

## Replacement Data JSON Structure
```json
{
  "job_title": "Senior Developer",
  "job_description": "Full stack development with 5+ years experience",
  "experience": "5-7 years",
  "certificate": "PMP, AWS Certified Solutions Architect",
  "academic_achievement": "Bachelor's degree in Computer Science or related field",
  "date_of_joining": "2025-02-01"
}
```

## Error Handling

### Common Issues

1. **Email not sending**
   - Check PHPMailer configuration
   - Verify `load_email_template()` function exists
   - Check email template file path

2. **Approval not forwarding**
   - Verify `hr_operations` and `hr_payroll` users exist
   - Check `user_type` in `admin_login` table
   - Ensure users have valid email addresses

3. **Permission errors**
   - Check session variables: `$is_system_admin`, `$isDeptManager`, `$isHR`, `$isGM`
   - Verify user has proper `user_type` in database

## Browser Notifications

All notifications created with:
```php
create_browser_notification(
    $conDB,
    $recipient_emp_id,
    'Title',
    'Message',
    'link_url.php?id=123'
);
```

Notifications created for:
- Manager when employee submits
- Next approver when current approves
- Employee when finally approved/rejected

## Audit Trail

All actions logged in `emp_resignation_history`:
- submitted
- intermediate_approved
- final_approved
- rejected
- cancelled

Each record includes:
- Action type
- Previous/new status
- Action by (emp_id)
- Action date
- Notes

## Translation Support

Uses `__()` function for all text:
- `__('resignation_details')`
- `__('employee_information')`
- `__('approve')`, `__('reject')`, etc.

Add translations in your language files.

## Security Features

1. **SQL Injection Prevention**
   - All inputs escaped with `mysqli_real_escape_string()`
   - Prepared statements for complex queries

2. **Session Validation**
   - Check user authentication before any action
   - Verify permissions for approve/reject

3. **Status Validation**
   - Only pending resignations can be approved/rejected
   - Employee can only cancel own pending resignation

4. **Audit Trail**
   - All actions logged with timestamp and user
   - Full history maintained for compliance

## Future Enhancements

Consider adding:
1. Document upload during resignation
2. Clearance checklist integration
3. Final settlement calculation
4. Export to PDF functionality
5. Analytics dashboard for resignation trends
6. Integration with HR management system

## Support

For issues or questions:
1. Check error logs in PHP error log
2. Check browser console for JavaScript errors
3. Verify database permissions
4. Test email system separately

---

**Version**: 1.0  
**Last Updated**: 2025-11-25  
**Maintained By**: Al-Mutlak WMS Development Team
