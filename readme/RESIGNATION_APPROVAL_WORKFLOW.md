# Employee Resignation Approval Workflow

## Overview
The resignation approval workflow implements a 3-level approval chain for employee resignations:
1. **Level 1: Direct Supervisor (Manager)** - Employee's direct manager from the `employees.supervisor_id` field
2. **Level 2: HR Operations** - User with `user_type = 'hr_operations'` in `admin_login` table
3. **Level 3: HR Payroll (Final)** - User with `user_type = 'hr_payroll'` in `admin_login` table

## Workflow Flow

### Step 1: Employee Submits Resignation (apply_resignation)
When an employee submits a resignation, the system:

1. **Creates Resignation Record** in `emp_resignations` table:
   - `emp_id` - Employee ID
   - `request_inv_no` - Unique request invoice number (format: RES-YYYYMMDD-XXXXXX)
   - `last_working_day` - Employee's intended last working day
   - `submission_date` - Current timestamp
   - `status` - Set to 'pending'
   - `created_at`, `updated_at` - Timestamps

2. **Creates Exit Interview** in `emp_exit_interviews` table:
   - Stores 9 interview questions from the resignation wizard
   - Links to resignation via `resignation_id`

3. **Creates Approval Chain** in `request_approvers` table:
   - **Level 1:** Direct Supervisor (awaiting approval)
   - **Level 2:** HR Operations (awaiting approval)
   - **Level 3:** HR Payroll (awaiting approval)
   
   Each record contains:
   - `request_inv_no` - Links all approvers to this resignation
   - `request_type_id` - 4 (from `approval_request_types` for 'resignation_request')
   - `approver_id` - Employee ID of the approver
   - `approval_level` - 1, 2, or 3
   - `status` - 'awaiting' for all initially

4. **Sends Email Notification** to Level 1 Approver (Manager):
   - Uses `load_email_template()` with template type 'resignation_request'
   - Template data includes:
     - Employee name, ID, department, designation
     - Last working day
     - Submission date
     - Approval level information
   - **All emails use the load_email_template function as required**

5. **Creates Browser Notification** for Level 1 Approver:
   - Title: "Resignation Request Requires Your Approval"
   - Message: Employee name and action required
   - Link to `all_resignations.php?inv=REQUEST_INV_NO`

6. **Logs History** in `emp_resignation_history` table:
   - Action: 'submitted'
   - New status: 'pending'
   - Submitter: Employee ID

---

### Step 2: Approvers Review & Approve (approve_resignation)

#### Approval Authorization
The system verifies the user is an authorized approver by checking `request_approvers` table:
- User's ID matches `approver_id`
- Status is 'awaiting'
- User hasn't already approved

#### Approval Logic
When an approver approves:

1. **Update request_approvers** record:
   - Set `status = 'approved'`
   - Set `action_date = NOW()`
   - Set `note` with approver name

2. **Check if Final Approval**:
   - Query for next level in chain: `approval_level > current_level AND status = 'awaiting'`
   - If no next approver exists → This is the final approval

3. **Update emp_resignations** (based on approval level):
   
   **For Intermediate Approval (Level 1 or 2):**
   - Update `needs_replacement` if provided
   - Update `replacement_data` as JSON if provided
   - Keep `status = 'pending'`
   - Keep `updated_at`
   
   **For Final Approval (Level 3):**
   - Set `status = 'approved'`
   - Set `approved_by = approver_id`
   - Set `approval_date = NOW()`
   - Update `needs_replacement` if provided
   - Update `replacement_data` if provided
   - Update `updated_at`

4. **Send Email to Next Approver** (if not final):
   - Uses `load_email_template()` with 'resignation_request' type
   - Template data includes approval level information
   - Subject includes level number: "Level 2: HR Operations"

5. **Send Notification to Next Approver**:
   - Title: "Resignation Requires Your Approval"
   - Link to `all_resignations.php?inv=REQUEST_INV_NO`

6. **If Final Approval - Notify Employee**:
   - Browser notification: "Resignation Approved"
   - Message: All approvers have approved; exit process will begin

7. **Log History**:
   - Action: 'intermediate_approved' or 'final_approved'
   - New status: 'pending' (for intermediate) or 'approved' (for final)
   - Action by: Approver ID
   - Notes: Approver name and level, replacement note if applicable

---

### Step 3: Reject Resignation (reject_resignation)

If any approver rejects at any level:

1. **Verify Authorization**:
   - Check `request_approvers` table for user as approver
   - Status must be 'awaiting'

2. **Update Resignation**:
   - Set `status = 'rejected'`
   - Set `rejected_by = rejector_id`
   - Set `approval_date = NOW()`
   - Set `rejection_reason` text
   - Update `updated_at`

3. **Update Approval Records**:
   - Current approver's record: `status = 'rejected'`
   - All remaining pending records: `status = 'rejected'` (reject entire chain)

4. **Notify Employee**:
   - Browser notification with rejection level and reason
   - Email notification via `load_email_template()`

5. **Log History**:
   - Action: 'rejected'
   - Previous status: 'pending'
   - New status: 'rejected'
   - Action by: Rejector ID
   - Notes: Reason and approval level

---

## Database Tables Updated

### emp_resignations
New/Modified Columns:
- `needs_replacement` (TINYINT) - Whether position needs replacement
- `replacement_data` (JSON) - Replacement details as JSON
- `rejected_by` (VARCHAR) - Employee ID who rejected

### request_approvers
Links resignation to approval chain:
- Stores Level 1, 2, 3 approvers
- Tracks status: 'awaiting', 'approved', 'rejected'
- Stores timestamps and notes for each approval

### emp_resignation_history
Audit trail of all actions with:
- `action` - submitted, intermediate_approved, final_approved, rejected, etc.
- `previous_status` and `new_status`
- `action_by` - Employee ID
- `action_date` - Timestamp
- `notes` - Descriptive text

---

## Email Template Integration

All approval communications use `load_email_template()` function with:

```php
send_approval_email(
    $conDB,
    $email,
    $name,
    $subject,
    'resignation_request',  // Template type
    [
        'emp_name' => $employee_name,
        'emp_id' => $employee_id,
        'department' => $department,
        'designation' => $designation,
        'request_id' => $request_inv_no,
        'last_working_day' => $date,
        'submission_date' => $date,
        'approver_name' => $approver_name,
        'approval_level' => 1|2|3,
        'approval_level_name' => 'Manager (Direct Supervisor)|HR Operations|HR Payroll (Final)',
        'rejection_reason' => $reason  // if rejection
    ]
);
```

Template file: `/includes/PHPMailerMaster/resignation_request_email_template.html`

---

## API Endpoints (AJAX)

### apply_resignation
**POST** to `includes/ajaxFile/ajaxResignation.php`

Parameters:
- `ajaxType = 'apply_resignation'`
- `emp_id` - Employee ID
- `last_working_day` - Date (YYYY-MM-DD)
- `exit_interview` - JSON with 9 interview answers

Response:
- Success: Resignation submitted with approval chain created
- All 3 approvers notified at initial state

### approve_resignation
**POST** to `includes/ajaxFile/ajaxResignation.php`

Parameters:
- `ajaxType = 'approve_resignation'`
- `resignation_id` OR `inv_no` - Resign ID or invoice number
- `needs_replacement` - 0|1
- `replacement_data` - JSON with replacement info (optional)

Response:
- If not final: "Forwarded to next approver (Level X)"
- If final: "Approved by all required approvers"

### reject_resignation
**POST** to `includes/ajaxFile/ajaxResignation.php`

Parameters:
- `ajaxType = 'reject_resignation'`
- `resignation_id` OR `inv_no`
- `rejection_reason` - Text reason

Response:
- Resignation rejected
- All remaining approvals cancelled
- Employee notified

---

## Status Values

### emp_resignations.status
- `pending` - Waiting for approvals
- `approved` - All approvers approved
- `rejected` - Any approver rejected
- `cancelled` - Employee cancelled
- `withdrawn` - Employee withdrawn

### request_approvers.status
- `awaiting` - Waiting for this approver's decision
- `approved` - This approver approved
- `rejected` - This approver rejected
- `cancelled` - Resignation was rejected, this approval cancelled

---

## Key Features

✅ **3-Level Approval Chain** - Manager → HR Operations → HR Payroll  
✅ **Email Notifications** - Using `load_email_template()` for all communications  
✅ **Browser Notifications** - Real-time in-system alerts  
✅ **Replacement Tracking** - Track if position needs replacement with JSON data  
✅ **Full Audit Trail** - Complete history of all actions and approvals  
✅ **Authorization Checks** - Verify user is authorized approver before allowing action  
✅ **Atomic Updates** - Transaction-like approach to maintain data consistency  
✅ **Exit Interview** - Capture employee feedback on 9 questions during submission  

---

## Files Modified

1. **`includes/ajaxFile/ajaxResignation.php`**
   - Added approval chain creation in `apply_resignation`
   - Updated `approve_resignation` to use `request_approvers` table
   - Updated `reject_resignation` to cancel remaining approvals
   - All email sending uses `load_email_template()`

2. **Database Changes**:
   - Added columns to `emp_resignations`: `needs_replacement`, `replacement_data`, `rejected_by`
   - Existing tables used: `request_approvers`, `emp_resignation_history`

---

## Testing Checklist

- [ ] Employee can submit resignation with exit interview
- [ ] Level 1 (Manager) receives email with approval link
- [ ] Manager can approve with or without replacement info
- [ ] Level 2 (HR Operations) receives forwarded request
- [ ] HR Operations can approve/reject
- [ ] Level 3 (HR Payroll) receives request if not rejected
- [ ] HR Payroll final approval sets resignation to 'approved'
- [ ] Rejection at any level cancels remaining approvals
- [ ] Employee receives rejection notification
- [ ] All emails use template system correctly
- [ ] History records all actions accurately
- [ ] Replacement data stores as JSON correctly
