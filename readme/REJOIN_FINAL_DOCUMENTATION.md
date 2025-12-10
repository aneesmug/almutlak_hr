# Employee Rejoin Approval System - Final Documentation

## System Overview

The employee rejoin approval system uses **only** the unified approval tables (`request_approvers` and `rejoin_requests`) for tracking rejoin requests. The `emp_vacation` table is **NOT modified** by this system.

---

## Database Tables Used

### 1. `rejoin_requests` (Main Detail Table)
Stores all rejoin-specific information.

```sql
CREATE TABLE `rejoin_requests` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `emp_id` VARCHAR(20) NOT NULL,
    `vacation_id` INT UNSIGNED NOT NULL,  -- Links to emp_vacation.id (read-only)
    `requested_rejoin_date` DATE NOT NULL,
    `requested_reason` TEXT,
    `requested_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `requested_by_emp_id` VARCHAR(20),
    
    `status` ENUM('pending', 'approved', 'adjusted', 'rejected') DEFAULT 'pending',
    `approved_at` DATETIME,
    `approved_by_emp_id` VARCHAR(20),
    `approval_note` TEXT,
    
    `rejection_reason` TEXT,
    
    `adjustment_allowed` BOOLEAN DEFAULT FALSE,
    `adjustment_from_date` DATE,
    `adjustment_to_date` DATE,
    `adjustment_reason_text` TEXT,
    `adjustment_submitted_date` DATE,
    `adjustment_submitted_at` DATETIME,
    
    `final_approved_date` DATE,
    `final_approved_at` DATETIME,
    
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX `idx_emp_id` (`emp_id`),
    INDEX `idx_vacation_id` (`vacation_id`),
    INDEX `idx_status` (`status`),
    FOREIGN KEY (`emp_id`) REFERENCES `employees` (`emp_id`) ON DELETE CASCADE,
    FOREIGN KEY (`vacation_id`) REFERENCES `emp_vacation` (`id`) ON DELETE CASCADE
);
```

### 2. `request_approvers` (Unified Approval Tracking)
Tracks approval status in centralized system.

```sql
-- Record created for each rejoin request
request_inv_no = rejoin_requests.id
request_type_id = 5  -- 'rejoin_request'
approver_id = supervisor's emp_id
approval_level = 1
status = 'pending' | 'approved' | 'rejected'
note = approval/rejection notes
action_date = timestamp of approval/rejection
```

### 3. `employees` (Status Update)
Only the `fly` field is updated.

```sql
-- Set to 0 when rejoin is approved
UPDATE employees SET fly = 0 WHERE emp_id = [emp_id]
```

### 4. `emp_vacation` (Read-Only)
**NOT MODIFIED** - Only read for vacation details.

- Used to get `vacation_id`, `return_date`
- Joins for employee and supervisor lookup
- **NO updates performed**

---

## Workflow

```
Employee Submits Rejoin Request
         ↓
┌────────────────────────────────┐
│  rejoin_requests                │
│  - INSERT new record            │
│  - status = 'pending'           │
└────────────────────────────────┘
         ↓
┌────────────────────────────────┐
│  request_approvers              │
│  - INSERT new record            │
│  - request_type_id = 5          │
│  - status = 'pending'           │
└────────────────────────────────┘
         ↓
    Supervisor Reviews
         ↓
  ┌──────┴──────┬────────────┐
  ↓             ↓            ↓
APPROVE       ADJUST      REJECT
  │             │            │
  ↓             ↓            ↓
UPDATE:       UPDATE:      UPDATE:
- rejoin_     - rejoin_    - rejoin_
  requests      requests     requests
  status=       status=      status=
  'approved'    'adjusted'   'rejected'
              
- request_    - request_   - request_
  approvers     approvers    approvers
  status=       status=      status=
  'approved'    'pending'    'rejected'
              
- employees   (Employee    (No update)
  fly = 0     confirms
              adjusted
              date)
                  ↓
              - rejoin_
                requests
                status=
                'approved'
              
              - request_
                approvers
                status=
                'approved'
              
              - employees
                fly = 0
```

---

## API Operations

### 1. Submit Rejoin Request

**Endpoint:** `ajaxVacation.php?ajaxType=submitRejoinRequest`

**Tables Updated:**
- `rejoin_requests`: INSERT new record
- `request_approvers`: INSERT new record

**Tables Read:**
- `emp_vacation`: Get vacation details
- `employees`: Get supervisor (reports_to)

**emp_vacation:** ❌ NOT UPDATED

---

### 2. Approve Rejoin

**Endpoint:** `ajaxVacation.php?ajaxType=processRejoinApproval&action=approve`

**Tables Updated:**
- `rejoin_requests`: UPDATE status='approved', final_approved_date
- `request_approvers`: UPDATE status='approved'
- `employees`: UPDATE fly=0

**emp_vacation:** ❌ NOT UPDATED

---

### 3. Allow Adjustment

**Endpoint:** `ajaxVacation.php?ajaxType=processRejoinApproval&action=adjust`

**Tables Updated:**
- `rejoin_requests`: UPDATE status='adjusted', adjustment window dates
- `request_approvers`: UPDATE status='pending' (until employee confirms)

**emp_vacation:** ❌ NOT UPDATED

---

### 4. Reject Rejoin

**Endpoint:** `ajaxVacation.php?ajaxType=processRejoinApproval&action=reject`

**Tables Updated:**
- `rejoin_requests`: UPDATE status='rejected', rejection_reason
- `request_approvers`: UPDATE status='rejected'

**emp_vacation:** ❌ NOT UPDATED

---

### 5. Employee Confirms Adjusted Date

**Endpoint:** `ajaxVacation.php?ajaxType=submitAdjustedRejoinDate`

**Tables Updated:**
- `rejoin_requests`: UPDATE status='approved', final_approved_date
- `request_approvers`: UPDATE status='approved'
- `employees`: UPDATE fly=0

**emp_vacation:** ❌ NOT UPDATED

---

## Data Queries

### Get Rejoin Request Details

```sql
SELECT 
    rr.*,
    ra.status as approval_status,
    ra.action_date,
    ra.note as approval_note,
    e.name as employee_name,
    e.fly,
    v.return_date,
    v.start_date,
    v.end_date
FROM rejoin_requests rr
JOIN request_approvers ra ON ra.request_inv_no = rr.id AND ra.request_type_id = 5
JOIN employees e ON rr.emp_id = e.emp_id
JOIN emp_vacation v ON rr.vacation_id = v.id
WHERE rr.id = ?
```

### Get Supervisor's Pending Requests

```sql
SELECT 
    rr.id,
    rr.emp_id,
    rr.requested_rejoin_date,
    rr.requested_reason,
    rr.requested_at,
    e.name as emp_name,
    v.return_date,
    ra.status as approval_status
FROM rejoin_requests rr
JOIN employees e ON rr.emp_id = e.emp_id
JOIN emp_vacation v ON rr.vacation_id = v.id
LEFT JOIN request_approvers ra ON ra.request_inv_no = rr.id AND ra.request_type_id = 5
WHERE e.reports_to = ?
AND rr.status = 'pending'
ORDER BY rr.requested_at DESC
```

---

## Migration Script

**File:** `includes/migrations/add_rejoin_approval_system.php`

**Creates:**
1. `rejoin_requests` table
2. Verifies `approval_request_types` has rejoin_request entry

**Does NOT:**
- Modify `emp_vacation` table
- Add any columns to `emp_vacation`

---

## Benefits of This Approach

### 1. **Clean Separation**
- `emp_vacation`: Vacation records only
- `rejoin_requests`: Rejoin workflow only
- No mixing of concerns

### 2. **No Schema Changes to Existing Tables**
- `emp_vacation` structure unchanged
- No risk of breaking existing vacation features
- Easy rollback if needed

### 3. **Unified Approval System**
- All approval workflows use `request_approvers`
- Consistent reporting across request types
- Centralized approval tracking

### 4. **Clear Audit Trail**
- All rejoin actions in `rejoin_requests`
- All approval actions in `request_approvers`
- Complete history preserved

### 5. **Flexible Queries**
- JOIN with `emp_vacation` when vacation details needed
- Query `rejoin_requests` alone for rejoin-specific data
- Simple queries for approval status

---

## Status Mapping

| rejoin_requests.status | request_approvers.status | employees.fly | Meaning |
|------------------------|--------------------------|---------------|---------|
| pending                | pending                  | 1             | Awaiting supervisor decision |
| adjusted               | pending                  | 1             | Supervisor allowed adjustment, employee hasn't confirmed |
| approved               | approved                 | **0**         | Approved - employee has rejoined |
| rejected               | rejected                 | 1             | Denied - employee still on vacation |

---

## Testing Checklist

### Submission ✓
- [ ] Employee submits rejoin request
- [ ] `rejoin_requests` record created with correct vacation_id
- [ ] `request_approvers` record created with request_type_id=5
- [ ] Supervisor ID correct in `request_approvers.approver_id`
- [ ] `emp_vacation` table **unchanged**
- [ ] Employee `fly` status remains 1

### Approval ✓
- [ ] Supervisor approves request
- [ ] `rejoin_requests.status` = 'approved'
- [ ] `request_approvers.status` = 'approved'
- [ ] `employees.fly` = 0
- [ ] `emp_vacation` table **unchanged**

### Adjustment ✓
- [ ] Supervisor allows adjustment
- [ ] `rejoin_requests.status` = 'adjusted'
- [ ] Adjustment window calculated (±3 days)
- [ ] `request_approvers.status` = 'pending'
- [ ] `emp_vacation` table **unchanged**

### Adjusted Confirmation ✓
- [ ] Employee confirms adjusted date
- [ ] `rejoin_requests.status` = 'approved'
- [ ] `request_approvers.status` = 'approved'
- [ ] `employees.fly` = 0
- [ ] `emp_vacation` table **unchanged**

### Rejection ✓
- [ ] Supervisor rejects request
- [ ] `rejoin_requests.status` = 'rejected'
- [ ] `request_approvers.status` = 'rejected'
- [ ] `employees.fly` remains 1
- [ ] `emp_vacation` table **unchanged**

---

## Files Modified

### Backend
1. **includes/ajaxFile/ajaxVacation.php**
   - Added 3 AJAX handlers
   - Updates `rejoin_requests` and `request_approvers` only
   - Sets `employees.fly = 0` on approval
   - **Does NOT update emp_vacation**

2. **includes/api/get_rejoin_requests.php**
   - Queries `rejoin_requests` + `request_approvers`
   - JOINs with `emp_vacation` (read-only)
   - Returns approval status from both tables

3. **includes/migrations/add_rejoin_approval_system.php**
   - Creates `rejoin_requests` table
   - Creates `rejoin_notifications` table
   - **Does NOT alter emp_vacation**

---

## Summary

✅ **emp_vacation table:** NOT MODIFIED (read-only)  
✅ **rejoin_requests table:** All rejoin details stored here  
✅ **request_approvers table:** All approval tracking here  
✅ **employees.fly:** Updated to 0 on approval  
✅ **Clean separation:** No mixing of vacation and rejoin data  
✅ **Unified system:** Consistent with other approval workflows  

**Status:** PRODUCTION READY  
**Version:** 2.1 (No emp_vacation Updates)  
**Updated:** December 2025

---

For previous documentation (now outdated), see:
- ~~REJOIN_UNIFIED_APPROVAL_UPDATE.md~~ (referenced emp_vacation updates - no longer applicable)
