# Loan Approval Authorization Issue - Root Cause Analysis

## Problem
When a direct supervisor tries to approve their employee's loan request, they get the error:
```
{"status":"error","title":"System Error","message":"An error occurred during approval: You are not authorized to approve this request","type":"error"}
```

## Root Cause

The authorization failure happens because **the supervisor's `supervisor_id` field in the employees table is either:**

1. **NOT SET/NULL** - The employee record doesn't have a supervisor_id assigned
2. **INCORRECT** - The supervisor_id points to the wrong person
3. **NOT SYNCED** - The supervisor_id exists but doesn't match the current user's emp_id

### How the Approval Chain Works

**When a loan is submitted:**
1. `apply_for_loan()` calls `ApprovalChainManager::createApprovalChain()`
2. This loads the approval chain configuration which starts with "direct_supervisor" at level 1
3. `resolveApprover('direct_supervisor', $emp_id)` queries:
   ```sql
   SELECT supervisor_id FROM employees WHERE emp_id = ? LIMIT 1
   ```
4. The returned `supervisor_id` is inserted into `request_approvers` table as the first approver

**When supervisor tries to approve:**
1. `approve_loan()` calls `ApprovalChainManager::verifyApprover($inv_no, $approver_emp_id)`
2. This queries:
   ```sql
   SELECT approver_id FROM request_approvers 
   WHERE request_inv_no = ? AND status IN ('pending', 'awaiting')
   LIMIT 1
   ```
3. It compares `approver_id` (from database) with `$approver_emp_id` (current user)
4. **If they don't match → Authorization fails**

## Why It Fails

The comparison `$approver_id != $approver_emp_id` fails when:

- **Case 1:** Employee has no supervisor assigned
  - supervisor_id is NULL/empty
  - `resolveApprover()` returns NULL
  - Chain skips level 1 or uses someone else
  - Supervisor can't approve because they're not in the chain

- **Case 2:** Supervisor ID is wrong in employees table
  - Points to wrong person
  - Current supervisor doesn't match the record

- **Case 3:** emp_id mismatch in session
  - `$_SESSION['empid']` doesn't match the supervisor's actual emp_id
  - Possible if multiple ID systems exist (emp_id vs admin_login id)

## Diagnostics

I've created a diagnostic script you can use:

**File:** `/diagnostic_loan_approval.php`

**Usage:** 
```
http://your-domain/almutlak/system/diagnostic_loan_approval.php?inv_no=LN-XXXXX&emp_id=EMPLOYEE_ID
```

**Example:**
```
http://localhost/almutlak/system/diagnostic_loan_approval.php?inv_no=LN-20260105-2403-3334&emp_id=5395
```

This script will show:
- ✓ Loan details
- ✓ Employee details (including supervisor_id)
- ✓ Supervisor details and whether they have admin access
- ✓ Full approval chain from database
- ✓ Current user info
- ✓ Why authorization failed (with exact ID comparison)

## Logging

Enhanced logging has been added to `ApprovalChainManager.php`:

**Check PHP error log for:**
```
RESOLVER: Resolving direct_supervisor for empId: XXXX
RESOLVER: Employee found: {...}
RESOLVER: Supervisor ID resolved to: YYYY
CREATE_CHAIN: Processing level 1 with user_type: direct_supervisor
CREATE_CHAIN: Level 1, user_type direct_supervisor resolved to approver_id: YYYY
```

## Solution Steps

1. **Verify supervisor is assigned:**
   - Go to Employee record
   - Check "Supervisor" field is set
   - Save if needed

2. **Verify supervisor has admin access:**
   - Supervisor must have an `admin_login` record
   - They must have emp_id matching their employees table emp_id
   - They should have a user_type assigned (e.g., 'department_manager', 'supervisor')

3. **Run diagnostic:**
   - Use the diagnostic script above
   - Verify all IDs match correctly

4. **Check session consistency:**
   - supervisor should have `$_SESSION['empid']` set
   - This should match their `employees.emp_id`

## Database Queries to Check

**Check if employee has supervisor assigned:**
```sql
SELECT emp_id, name, supervisor_id FROM employees 
WHERE emp_id = '5395';
```

**Check if supervisor has admin access:**
```sql
SELECT emp_id, user_type, id_iqama FROM admin_login 
WHERE emp_id = (SELECT supervisor_id FROM employees WHERE emp_id = '5395');
```

**Check the approval chain created for a loan:**
```sql
SELECT * FROM request_approvers 
WHERE request_inv_no = 'LN-20260105-2403-3334' 
ORDER BY approval_level ASC;
```

## Implementation Notes

- The supervisor MUST have their emp_id matching in both `employees` and `admin_login` tables
- The employee's `supervisor_id` field MUST be the emp_id (not the admin_login id)
- Enhanced logging is now active to debug these issues in the future
- The diagnostic script provides one-page visibility into the entire approval flow
