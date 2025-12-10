# Employee Rejoin Approval System
## Complete Implementation Guide

### Overview
This system enables employees to submit rejoin requests when returning from vacation, which require supervisor approval. It includes a 3-day adjustment window to handle cases where employees select incorrect dates.

---

## System Flow

### 1. **Employee Submits Rejoin Request**
- Employee clicks "Rejoin" button in the vacation section
- Opens modal to select actual rejoin date
- Can optionally provide reason for date change
- Request is submitted to supervisor for approval

**Function:** `submitRejoinRequest()` in `view_employee.php`

### 2. **Supervisor Reviews Request**
- Supervisor sees pending rejoin requests in dedicated dashboard
- Can take one of 3 actions:
  - **Approve**: Accept the requested date immediately
  - **Adjust**: Allow employee to change date within ±3 days
  - **Reject**: Reject with explanation (requires HR review)

**Function:** `approveRejoinRequest()` in `view_employee.php`

### 3. **Employee Adjusts Date (if allowed)**
- If supervisor selects "Adjust", employee can change the date
- Date must be within ±3 days from originally requested date
- Final adjusted date is automatically approved

**Function:** `submitAdjustedRejoinDate()` in AJAX handler

---

## Database Schema

### Tables Added

#### `rejoin_requests` (Main tracking table)
```
id - Unique request ID
emp_id - Employee ID
vacation_id - Reference to emp_vacation
requested_rejoin_date - Date employee requested to rejoin
requested_reason - Optional reason for change
requested_at - When request was submitted
requested_by_emp_id - Who submitted (usually employee themselves)

status - 'pending' | 'approved' | 'adjusted' | 'rejected'
approved_at - When supervisor reviewed
approved_by_emp_id - Supervisor ID
approval_note - Optional note from supervisor

rejection_reason - If rejected, reason why
adjustment_allowed - Boolean if employee can adjust
adjustment_from_date - Earliest allowed adjustment date (requested - 3 days)
adjustment_to_date - Latest allowed adjustment date (requested + 3 days)
adjustment_reason_text - Supervisor's reason for allowing adjustment
adjustment_submitted_date - Final date after adjustment
adjustment_submitted_at - When employee submitted adjusted date

final_approved_date - Final approved rejoin date
final_approved_at - When finalized
```

#### `emp_vacation` (Columns Added)
```
rejoin_request_status - Status of rejoin process
rejoin_requested_date - Employee's requested rejoin date
rejoin_requested_at - When submitted
rejoin_approved_date - Approved date
rejoin_approved_by - Supervisor who approved
rejoin_approved_at - Approval timestamp
rejoin_adjustment_allowed - Can employee adjust?
rejoin_adjustment_from_date - Start of adjustment window
rejoin_adjustment_to_date - End of adjustment window
rejoin_adjustment_reason - Why adjustment allowed
rejoin_final_date - Final confirmed date
rejoin_final_confirmed_at - When finalized
```

#### `rejoin_notifications` (Notification tracking)
```
id - Notification ID
rejoin_request_id - Reference to rejoin_requests
emp_id - Employee requesting rejoin
supervisor_emp_id - Supervisor to notify
notification_type - 'new_request' | 'adjustment_needed' | 'reminder'
is_read - Read status
read_at - When read
created_at - When created
```

---

## API Endpoints

### 1. **Submit Rejoin Request**
**URL:** `includes/ajaxFile/ajaxVacation.php`
**Method:** POST
**Type:** `submitRejoinRequest`

**Parameters:**
```javascript
{
    ajaxType: 'submitRejoinRequest',
    vacation_id: int,
    rejoin_date: 'YYYY-MM-DD',
    rejoin_reason: string (optional),
    emp_id: int
}
```

**Response:**
```json
{
    "status": "success",
    "title": "Success",
    "message": "Your rejoin request has been submitted..."
}
```

### 2. **Process Rejoin Approval**
**URL:** `includes/ajaxFile/ajaxVacation.php`
**Method:** POST
**Type:** `processRejoinApproval`

**Parameters:**
```javascript
{
    ajaxType: 'processRejoinApproval',
    rejoin_request_id: int,
    action: 'approve' | 'adjust' | 'reject',
    approval_note: string,
    rejection_reason: string (required if action='reject')
}
```

**Response:**
```json
{
    "status": "success",
    "message": "Request processed successfully..."
}
```

### 3. **Submit Adjusted Date**
**URL:** `includes/ajaxFile/ajaxVacation.php`
**Method:** POST
**Type:** `submitAdjustedRejoinDate`

**Parameters:**
```javascript
{
    ajaxType: 'submitAdjustedRejoinDate',
    rejoin_request_id: int,
    adjusted_date: 'YYYY-MM-DD'
}
```

### 4. **Get Supervisor Rejoin Requests**
**URL:** `includes/api/get_rejoin_requests.php`
**Method:** GET

**Response:**
```json
{
    "status": "success",
    "data": {
        "pending": [...],
        "approved": [...],
        "rejected": [...]
    }
}
```

---

## Frontend Functions

### In `view_employee.php`

#### `submitRejoinRequest(vacationId, returndate, empId, empName)`
Opens modal for employee to submit rejoin request.
- Shows planned return date
- Date picker starts from planned date (can't select before)
- Optional reason field
- Maximum 3 days after planned date validation

#### `approveRejoinRequest(rejoinRequestId, empId, rejoinDate, showAdjustmentOption)`
Opens supervisor approval modal with options:
- Approve: Accepts the date immediately
- Adjust: Allows employee 3-day window (±3 days)
- Reject: Requires rejection reason

#### `processRejoinApproval(rejoinRequestId, action, approvalNote, rejectReason)`
Submits supervisor's decision to backend.

---

## Pages

### Employee Interface
**Location:** `view_employee.php`
- Rejoin button in vacation section
- Calls `returnVacationRequest()` with parameters
- Modal for date selection and submission

### Supervisor Dashboard
**Location:** `rejoin_approvals.php`
- Shows pending, approved, rejected requests
- Real-time update (refreshes every 30 seconds)
- Review button to open approval modal
- Displays employee info, planned return, requested date, reason

---

## Key Features

### 1. **Date Validation**
- Employee can't select date more than 3 days after planned return
- Supervisor can extend this window through adjustment
- System validates all dates on submission

### 2. **Supervisor Flexibility**
- Can approve immediately
- Can allow adjustment within ±3 days
- Can reject with detailed reason
- Can add approval notes

### 3. **Audit Trail**
- Complete history of all requests and decisions
- Timestamps for all actions
- User IDs for accountability
- Rejection reasons tracked

### 4. **Notifications**
- Supervisor is notified when request submitted
- Employee is notified of approval/rejection
- Adjustment notifications if allowed

### 5. **Business Rules**
- Only employee's direct supervisor can approve
- HR/Admin can override if needed
- Cannot approve own requests
- 3-day adjustment window prevents excessive date changes

---

## Implementation Steps

### 1. Run Database Migration
```bash
php includes/migrations/add_rejoin_approval_system.php
```

### 2. Update emp_top_info.php
Already done - rejoin button now passes emp_id and emp_name

### 3. Add Rejoin Approval Functions to view_employee.php
Already done - all functions added

### 4. Add AJAX Handlers to ajaxVacation.php
Already done - three new AJAX types added

### 5. Create Supervisor Dashboard
Already done - `rejoin_approvals.php` created

### 6. Add Translations (if needed)
Add these keys to your translation file:
```
rejoin_request_title
rejoin_request_subtitle
rejoin_date_label
planned_return_text
rejoin_reason_label
submit_request_button
rejoin_date_required_validation
rejoin_date_range_validation
rejection_reason_required
rejoin_request_submitted_text
rejoin_request_approved_text
rejoin_adjustment_allowed_text
rejoin_request_rejected_text
```

---

## Usage Scenarios

### Scenario 1: Normal Approval
1. Employee submits rejoin request for 2024-12-10
2. Supervisor reviews and approves
3. Rejoin date is locked to 2024-12-10

### Scenario 2: Employee Selected Wrong Date
1. Employee submits rejoin request for 2024-12-12 (but should be 2024-12-15)
2. Supervisor selects "Adjust" option
3. Employee gets notification they can adjust
4. Employee changes date to 2024-12-15 (within 3-day window)
5. Date is finalized

### Scenario 3: Rejection
1. Employee submits rejoin request
2. Supervisor sees issue (e.g., payroll issues, other conflicts)
3. Supervisor rejects with explanation
4. HR gets involved to resolve
5. New request submitted after issue resolved

---

## Security Considerations

1. **Permission Checks**
   - Only supervisor of employee can approve
   - Employees can't approve own requests
   - HR can override all decisions

2. **Data Validation**
   - Date ranges validated on both client and server
   - Employee ID verified in session
   - All inputs sanitized

3. **Audit Trail**
   - All actions logged in database
   - Who approved, when, and why
   - Full history preserved

4. **Business Rules**
   - Can't bypass 3-day adjustment window
   - Can't approve without valid supervisor
   - Rejection requires explanation

---

## Troubleshooting

### Issue: No rejoin button appears
- Check if `fly = 1` in employees table
- Verify user is HR/Admin/DeptHr
- Check vacation is active and approved

### Issue: Supervisor can't see requests
- Verify `reports_to` field is set in employees table
- Check supervisor emp_id matches in database
- Ensure supervisor is logged in

### Issue: Date validation fails
- Client-side validation checks date is within 3 days
- Server validates again before saving
- Check server timezone matches client

### Issue: Adjustment window not working
- Verify supervisor selected "Adjust" action
- Employee must submit within adjustment window
- Window is ±3 days from requested date

---

## Future Enhancements

1. **Email Notifications**
   - Notify supervisor of new requests
   - Notify employee of approval/rejection
   - Reminder emails for pending requests

2. **Mobile App Support**
   - Mobile-friendly approval interface
   - Push notifications for supervisors

3. **Bulk Operations**
   - Approve multiple requests
   - Export approval history

4. **Advanced Reporting**
   - Rejoin statistics
   - Average approval time
   - Frequent date changes tracking

---

## Support

For issues or questions about this system, contact the HR department or system administrator.

**Last Updated:** December 2025
**Version:** 1.0
**Status:** Production Ready
