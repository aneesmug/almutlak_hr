# Approval Email Notification Fix

## Problem Description

Approval emails were not being sent to the next approver in the loan approval chain when:
1. GM approved a loan with modifications (`modify_and_approve_loan()`)
2. HR Assistant approved a loan with modifications (`modify_and_approve_loan_hr_assistant()`)

## Root Cause

Both functions were calling `$chainManager->processApproval()` which correctly:
- Updated the approval status in the database
- Identified the next approver
- Returned next approver details in the response

**However**, they were **NOT sending the email notification** to the next approver. The `processApproval()` method in `ApprovalChainManager` only processes the approval status update, it does NOT send emails.

Comparison with working code:
- ✅ `approve_loan()` function: Calls `processApproval()` AND THEN sends email to next approver
- ❌ `modify_and_approve_loan()` function: Called `processApproval()` but did NOT send email
- ❌ `modify_and_approve_loan_hr_assistant()` function: Called `processApproval()` but did NOT send email

## Solution Implemented

Added email notification code to both functions immediately after `processApproval()` call:

### 1. `modify_and_approve_loan()` - Lines 1988-2045
- Added `$isFinalApproval` and `$nextApprover` extraction from `processApproval()` result
- Added activity logging for the modification
- Added status history entry to `smt_request_status` table
- **NEW:** Added email notification to next approver using `send_approval_email()`
- **NEW:** Added browser notification using `create_browser_notification()`

### 2. `modify_and_approve_loan_hr_assistant()` - Complete rewrite (Lines 2065-2195)
- Changed to use `ApprovalChainManager` (was using old manual approval method)
- Now properly extracts `approver_emp_id` from session
- Now properly uses `processApproval()` through the approval chain
- **NEW:** Added activity logging
- **NEW:** Added status history
- **NEW:** Added email notification to next approver
- **NEW:** Added browser notification

## Email Flow

### Before (Broken):
```
GM clicks "Modify & Approve" 
  → Modal sends AJAX request
    → Backend updates loan amount/installments
    → Backend calls processApproval() → updates database status
    → Backend returns success
    → User sees "Success!" message
    ❌ Next approver gets NO EMAIL notification
```

### After (Fixed):
```
GM clicks "Modify & Approve" 
  → Modal sends AJAX request
    → Backend updates loan amount/installments
    → Backend calls processApproval() → updates database status & returns next approver details
    → Backend fetches loan details from database
    → Backend calls send_approval_email() to next approver's email
    → Backend calls create_browser_notification() for next approver
    → Backend returns success
    ✅ Next approver gets EMAIL + Browser notification
```

## Files Modified

1. **[includes/ajaxFile/ajaxLoan.php](includes/ajaxFile/ajaxLoan.php)**
   - `modify_and_approve_loan()` function (Lines 1903-2060)
   - `modify_and_approve_loan_hr_assistant()` function (Lines 2065-2195)

## Testing Checklist

- [ ] GM modifies and approves a loan
- [ ] Check: Next approver receives email notification
- [ ] Check: Next approver receives browser notification
- [ ] Check: Email contains loan details (amount, installments, employee name)
- [ ] Check: Email subject indicates modification (e.g., "Modified by GM")
- [ ] Check: Next approver status in database is 'pending'
- [ ] HR Assistant modifies and approves a loan
- [ ] Check: Next approver receives email notification
- [ ] Check: Status history is recorded for the modification
- [ ] Check: Final approval doesn't send email (only creates browser notification for loan creator)

## Email Template Variables

The following variables are passed to the email template:

```php
$emailData = [
    'APPROVER_NAME' => $nextApproverName,           // Recipient's name
    'REQUEST_ID' => $inv_no,                        // Loan invoice number
    'EMPLOYEE_NAME' => $loan_details['employee_name'],  // Loan applicant name
    'LOAN_TYPE' => str_replace('_', ' ', $loan_details['loan_type']),  // Loan type
    'LOAN_AMOUNT' => number_format($loan_details['loan_amount'], 2),    // Modified amount
    'INSTALLMENTS' => $loan_details['installments'],     // Modified installments
    'REQUEST_URL' => 'https://hr.almutlaksystem.com/all_applied_loan.php?status=my_pending',
    'EMAIL_MESSAGE' => 'A loan request has been modified by the GM and now requires your approval.'
];
```

## Email Subject Lines

- **GM Modification:** `"Loan Request Pending Your Approval - Modified by GM (INV123...)"`
- **HR Assistant Modification:** `"Loan Request Pending Your Approval - Modified by HR Assistant (INV123...)"`
- **Regular Approval:** `"Loan Request Pending Your Approval - Loan Type (INV123...)"`

## Notification Messages

### Email Message
- **GM:** "A loan request has been modified by the GM and now requires your approval."
- **HR Assistant:** "A loan request has been modified by the HR Assistant and now requires your approval."

### Browser Notification
- **GM:** "Loan request {INV_NO} (modified by GM) is now pending your approval."
- **HR Assistant:** "Loan request {INV_NO} (modified by HR Assistant) is now pending your approval."

## Dependencies

- `send_approval_email()` - Function from helper_functions.php (called with: conDB, email, name, subject, request_type, template_data)
- `create_browser_notification()` - Function from helper_functions.php (called with: conDB, approver_id, title, message, url)
- `ActivityLogger::logApproval()` - Static method to log approvals for audit trail
- `ApprovalChainManager::processApproval()` - Core method that processes approval status updates

## Error Handling

Both functions include try-catch blocks that:
1. Catch any exceptions during the approval process
2. Rollback the database transaction if error occurs
3. Return JSON error response to frontend
4. Log detailed error message for debugging

## Backward Compatibility

- No changes to existing API calls
- Response format remains unchanged
- Database schema unchanged
- No breaking changes to other functions
