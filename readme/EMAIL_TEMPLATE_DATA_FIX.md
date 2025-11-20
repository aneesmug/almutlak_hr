# Email Template Data Fix - Vacation Approval Notifications

## Problem
After manager approval of a vacation request, the approval email was being sent but only showing the email template without the vacation history/details (employee name, dates, duration, etc.). This happened because the code was passing incorrect database column names to the email template.

## Root Cause
The PHP code in `ajaxVacation.php` was trying to access database columns that don't exist:
- Using `$vac_data['end_date']` instead of `$vac_data['return_date']` (wrong column name)
- Using `$vac_data['total_days']` instead of `$vac_data['vacdays']` (wrong column name)

When these invalid keys were accessed, PHP returned `NULL` or empty values, which the email template couldn't display. The email template was showing but with blank vacation details.

## Database Column Names (Actual)
From `emp_vacation` table:
- `start_date` - vacation start date
- `return_date` - vacation end/return date (NOT `end_date`)
- `vacdays` - total vacation days (NOT `total_days`)

## Fixes Applied

### Fix 1: Next Approver Email Template (Line ~862)
**Location**: `includes/ajaxFile/ajaxVacation.php` - approveVacation block

**Before**:
```php
'END_DATE' => date('d M Y', strtotime($vac_data['end_date'])),
'DURATION' => $vac_data['total_days'],
```

**After**:
```php
'END_DATE' => date('d M Y', strtotime($vac_data['return_date'])),
'DURATION' => $vac_data['vacdays'],
```

### Fix 2: CC Recipient Email Template (Line ~974)
**Location**: `includes/ajaxFile/ajaxVacation.php` - HR Team CC notification

**Before**:
```php
'END_DATE' => date('d M Y', strtotime($vac_data['end_date'])),
'DURATION' => $vac_data['total_days'],
```

**After**:
```php
'END_DATE' => date('d M Y', strtotime($vac_data['return_date'])),
'DURATION' => $vac_data['vacdays'],
```

## Email Template Data Structure
The vacation email template expects these placeholders (all now correctly populated):
- `APPROVER_NAME` - Name of the person receiving the email
- `REQUEST_TYPE` - "Annual Vacation Request"
- `REQUEST_TYPE_LOWER` - "annual vacation request" (lowercase)
- `REQUEST_ID` - Vacation request reference number
- `EMPLOYEE_NAME` - Employee's name ✅ Now correctly fetched
- `START_DATE` - Vacation start date ✅ Now correctly formatted
- `END_DATE` - Vacation return date ✅ Now correctly pulled from `return_date` column
- `DURATION` - Number of days ✅ Now correctly pulled from `vacdays` column
- `REQUEST_URL` - Link to approve the request

## Result
Now when a manager approves a vacation:
1. ✅ Email is sent with proper vacation details
2. ✅ Employee name shows in email
3. ✅ Start date and end date are displayed
4. ✅ Duration in days is shown
5. ✅ All template placeholders are properly populated

## Files Changed
- `includes/ajaxFile/ajaxVacation.php` (2 sections - both approveVacation block)

## Testing
1. Manager approves a vacation request
2. Check the email sent to next approver
3. Email should now display:
   - Employee name
   - Vacation dates (start and end)
   - Number of days
   - Request ID
   - All other vacation details

Previously it would only show the email template without these details.
