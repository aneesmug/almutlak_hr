# GR Officer Approval Button Troubleshooting Guide

##  Issue: GR Officer Can't See Approval Button

### Root Cause Analysis

The approval button appears when **ALL** of these conditions are met:

1. ✅ User is logged in as GR Officer (`user_type = 'gr_officer'`)
2. ✅ GR Officer is added to the approval chain (auto-added for Fly | Annual vacations)
3. ✅ Previous approver has completed their approval
4. ✅ GR Officer's status in `request_approvers` is `'pending'` (not `'awaiting'`)
5. ✅ Request `current_status = 'pending_approval'`
6. ✅ GR Officer is viewing the request on `all_applied_vac.php`

### Step-by-Step Diagnosis

#### Step 1: Verify GR Officer User Account

Run this SQL query:

```sql
SELECT 
    e.emp_id,
    e.name,
    al.username,
    al.user_type,
    al.emp_type,
    e.status
FROM employees e
JOIN admin_login al ON e.emp_id = al.emp_id
WHERE al.user_type = 'gr_officer';
```

**Expected Result:**
- At least one row with `user_type = 'gr_officer'`
- `status = 1` (active)

**If No Results:**
```sql
-- Create or update GR Officer user
UPDATE admin_login 
SET user_type = 'gr_officer'
WHERE emp_id = XXX;  -- Replace with actual emp_id
```

#### Step 2: Check if GR Officer is in Approval Chain

For a specific vacation request, run:

```sql
SELECT 
    ra.approval_level,
    e.name as approver_name,
    al.user_type,
    ra.status,
    ra.action_date
FROM request_approvers ra
JOIN employees e ON ra.approver_id = e.emp_id
JOIN admin_login al ON e.emp_id = al.emp_id
WHERE ra.request_inv_no = 'VAC-2026-XXXX'  -- Replace with actual request number
ORDER BY ra.approval_level ASC;
```

**Expected Result:**
- GR Officer appears in the list with highest `approval_level`
- Status is either `'awaiting'` or `'pending'`

**If GR Officer is Missing:**
- This means the vacation is NOT "Fly | Annual" type
- Check: `SELECT vac_type, fly_type FROM emp_vacation WHERE request_inv_no = 'VAC-2026-XXXX'`
- Should be: `vac_type = 'Fly'` AND `fly_type = 'annual'`

#### Step 3: Check GR Officer's Approval Status

```sql
SELECT 
    v.request_inv_no,
    v.current_status,
    v.current_approval_level,
    ra.approval_level as gr_level,
    ra.status as gr_status,
    ra.approver_id as gr_emp_id
FROM emp_vacation v
JOIN request_approvers ra ON v.request_inv_no = ra.request_inv_no
JOIN admin_login al ON ra.approver_id = al.emp_id
WHERE v.request_inv_no = 'VAC-2026-XXXX'  -- Replace
  AND al.user_type = 'gr_officer';
```

**Expected Result:**
- `gr_status = 'pending'` → GR Officer CAN approve
- `gr_status = 'awaiting'` → GR Officer CANNOT approve yet (previous approver hasn't approved)

**If Status is 'awaiting':**
- Check who the current approver is:
  ```sql
  SELECT e.name, ra.approval_level, ra.status
  FROM request_approvers ra
  JOIN employees e ON ra.approver_id = e.emp_id
  WHERE ra.request_inv_no = 'VAC-2026-XXXX'
    AND ra.status = 'pending';
  ```
- Wait for this approver to complete their approval

#### Step 4: Verify Current Approver Query

Check if the page query correctly identifies GR Officer as current approver:

```sql
SELECT 
    v.request_inv_no,
    v.current_status,
    ra_pending.approver_id as current_approver_id,
    approver_emp.name as current_approver_name
FROM emp_vacation v
LEFT JOIN request_approvers ra_pending ON ra_pending.request_inv_no = v.request_inv_no 
     AND ra_pending.request_type_id IN (3, 7)  -- vacation_request, excuse_leave
     AND ra_pending.status = 'pending'
LEFT JOIN employees approver_emp ON ra_pending.approver_id = approver_emp.emp_id
WHERE v.request_inv_no = 'VAC-2026-XXXX';  -- Replace
```

**Expected Result:**
- `current_approver_id` = GR Officer's `emp_id`
- `current_approver_name` = GR Officer's name

#### Step 5: Check Page Access

Ensure GR Officer can access `all_applied_vac.php`:

1. Login as GR Officer
2. Go to: `http://yourdomain.com/all_applied_vac.php?status=my_pending`
3. Should see vacation requests where GR Officer is pending approver

**If Redirected to profile.php:**
- Check session: GR Officer must NOT have `$isEmployee = true`
- GR Officers should be treated as approvers/administrators

### Common Issues & Solutions

#### Issue 1: GR Officer Status Stuck on 'awaiting'

**Cause:** Previous approver hasn't approved yet, or approval didn't trigger status update.

**Solution:**
Manually update status (temporary fix):
```sql
UPDATE request_approvers 
SET status = 'pending'
WHERE request_inv_no = 'VAC-2026-XXXX'
  AND approver_id = XXX;  -- GR Officer emp_id
```

**Permanent Fix:**
Ensure previous approver uses the approval system correctly (not manual SQL updates).

#### Issue 2: Multiple GR Officers in System

**Cause:** Query uses `LIMIT 1` to get first GR Officer.

**Check:**
```sql
SELECT COUNT(*) as gr_count
FROM admin_login
WHERE user_type = 'gr_officer' AND status = 1;
```

**If gr_count > 1:**
System will use the one with lowest `emp_id`. To specify which one:
```sql
-- Check which GR Officer is being used
SELECT e.emp_id, e.name 
FROM employees e
JOIN admin_login al ON e.emp_id = al.emp_id
WHERE al.user_type = 'gr_officer' AND e.status = 1
ORDER BY e.emp_id ASC 
LIMIT 1;
```

#### Issue 3: Button Shows But Form is Empty

**Cause:** JavaScript variables not set correctly.

**Check Browser Console:**
1. Right-click → Inspect → Console
2. Look for errors related to `isGR_Officer` or `permit_fee`
3. Should see: `isGR_Officer = true` when GR Officer opens approval modal

#### Issue 4: Approval Submitted But permit_fee Not Saved

**Cause:** Form field not being collected or sent to backend.

**Check:**
1. Open approval modal
2. Right-click → Inspect
3. Verify `<input id="swal_permit_fee">` exists in the DOM
4. Check Network tab when submitting approval
5. Verify `permit_fee` is in the POST data

### Quick Test Procedure

1. **Create Test Fly | Annual Vacation**
   - Employee: Any employee
   - Type: Fly → Annual
   - Dates: Valid future dates

2. **Check GR Officer Added to Chain**
   ```sql
   SELECT * FROM request_approvers 
   WHERE request_inv_no = 'VAC-2026-XXXX'  
   ORDER BY approval_level;
   ```
   - GR Officer should be at highest level with status='awaiting'

3. **Approve Through Chain**
   - Approve as Level 1 (Supervisor/Manager)
   - Approve as Level 2 (HR Senior BP)
   - Approve as Level 3+ (Asset Checkers if any)
   - Approve as HR Payroll (if applicable)

4. **Verify GR Officer Status Changed**
   ```sql
   SELECT status FROM request_approvers 
   WHERE request_inv_no = 'VAC-2026-XXXX'
     AND approver_id = (
       SELECT emp_id FROM admin_login 
       WHERE user_type = 'gr_officer' LIMIT 1
     );
   ```
   - Should now be: `status='pending'`

5. **Login as GR Officer**
   - Go to: `all_applied_vac.php?status=my_pending`
   - Click request card
   - Click Actions → Approve
   - **Should see:** "Approve" button in dropdown
   - **Should see:** Form with "Permit & Visa Fees" field

6. **Verify Approval Works**
   - Enter permit_fee amount (e.g., 500.00)
   - Add comment (optional)
   - Click "Yes, Approve It"
   - Check database:
     ```sql
     SELECT permit_fee FROM emp_vacation 
     WHERE request_inv_no = 'VAC-2026-XXXX';
     ```
   - Should show the entered amount

### Files to Check

If button still doesn't show, verify these files have the correct code:

1. **ajaxVacation.php** (Lines 1200-1261)
   - GR Officer auto-append logic

2. **all_applied_vac.php** (Line 788)
   - `$is_pending_with_me` condition

3. **all_applied_vac.php** (Line 883)
   - Approval button visibility check

4. **all_applied_vac.php** (Lines 1859-1876)
   - GR Officer permit_fee form field

5. **ApprovalChainManager.php** (Lines 525-533)
   - Status update from 'awaiting' to 'pending'

### Emergency Manual Fix

If GR Officer needs to approve IMMEDIATELY and button won't show:

```sql
-- 1. Update their status to pending
UPDATE request_approvers 
SET status = 'pending'
WHERE request_inv_no = 'VAC-2026-XXXX'
  AND approver_id = XXX;  -- GR Officer emp_id

-- 2. Update all other pending approvers to awaiting (if multiple)
UPDATE request_approvers 
SET status = 'awaiting'
WHERE request_inv_no = 'VAC-2026-XXXX'
  AND approver_id != XXX  -- NOT GR Officer
  AND status = 'pending';

-- 3. Refresh page - button should now appear
```

**After manual fix:** Investigate why automatic status update didn't work.

### Contact Support

If none of the above solves the issue, provide:
1. Screenshot of the page (no approve button)
2. GR Officer's emp_id and username
3. Request invoice number
4. Results of SQL queries in Step 2 and Step 3
5. Browser console errors (if any)

---

**Last Updated:** January 7, 2026
**Related Docs:** GR_OFFICER_AUTO_APPROVAL_CHAIN.md
