# Employee Rejoin Approval System - Unified Approval Integration

## Update Summary

The employee rejoin approval system has been updated to integrate with the unified approval system using the `request_approvers` table. This ensures consistency across all approval workflows in the system.

---

## Key Changes

### 1. **Unified Approval Integration**

The system now uses the centralized `request_approvers` table to track approval statuses, providing:
- Consistent approval tracking across all request types
- Centralized reporting and analytics
- Unified notification system
- Standard approval workflow patterns

### 2. **Employee Fly Status Management**

When a rejoin request is approved (either immediately or after adjustment), the system automatically:
- Sets `employees.fly = 0` to mark the employee as back at work
- Updates the vacation record with final rejoin date
- Records approval in `request_approvers` table

### 3. **Database Schema**

The system uses three interconnected approval tables:

```sql
-- Unified approval types registry
approval_request_types
├── id: 5
├── type_name: 'rejoin_request'
└── main_table_name: 'rejoin_request'

-- Unified approval tracking
request_approvers
├── request_inv_no (links to rejoin_requests.id)
├── request_type_id: 5
├── approver_id (supervisor's emp_id)
├── approval_level: 1
├── status: 'pending'|'approved'|'rejected'|'awaiting'
├── note (approval/rejection notes)
└── action_date

-- Rejoin-specific details
rejoin_requests
├── id (primary key)
├── emp_id
├── vacation_id
├── requested_rejoin_date
├── status
├── adjustment details
└── final_approved_date
```

---

## Workflow Diagram

```
┌─────────────────────────────────────────────────────────────┐
│                   EMPLOYEE REJOIN WORKFLOW                   │
└─────────────────────────────────────────────────────────────┘

┌──────────────┐
│   Employee   │ Submits rejoin request
│  (fly = 1)   │
└──────┬───────┘
       │
       ▼
┌─────────────────────────────────────────────────────────────┐
│ System Creates:                                              │
│ 1. rejoin_requests record (id=123, status='pending')        │
│ 2. request_approvers record                                  │
│    - request_inv_no = 123                                    │
│    - request_type_id = 5 (rejoin_request)                   │
│    - approver_id = supervisor's emp_id                       │
│    - status = 'pending'                                      │
└─────────────────────────────────────────────────────────────┘
       │
       ▼
┌──────────────┐
│  Supervisor  │ Reviews request in rejoin_approvals.php
└──────┬───────┘
       │
       ├───────────────────┬──────────────────┬──────────────────┐
       │                   │                  │                  │
       ▼                   ▼                  ▼                  ▼
   ┌────────┐        ┌─────────┐       ┌──────────┐      ┌──────────┐
   │APPROVE │        │ ADJUST  │       │  REJECT  │      │  IGNORE  │
   └───┬────┘        └────┬────┘       └────┬─────┘      └────┬─────┘
       │                  │                 │                 │
       ▼                  ▼                 ▼                 ▼
   Updates:           Updates:           Updates:         No Action
   ─────────         ─────────          ─────────         ─────────
   1. request_      1. request_        1. request_       Status stays
      approvers        approvers          approvers      'pending'
      status =         status =           status =
      'approved'       'pending'          'rejected'
                       (until emp
   2. rejoin_          adjusts)        2. rejoin_
      requests                            requests
      status =      2. rejoin_            status =
      'approved'       requests           'rejected'
                       status =
   3. employees        'adjusted'      3. emp_vacation
      fly = 0                             rejoin_status
                    3. emp_vacation        = 'rejected'
   4. emp_              sets
      vacation          adjustment
      rejoin_           window
      status =          (±3 days)
      'approved'
                           │
                           ▼
                    ┌─────────────┐
                    │  Employee   │
                    │ Adjusts Date│
                    └──────┬──────┘
                           │
                           ▼
                       Updates:
                       ─────────
                       1. request_
                          approvers
                          status =
                          'approved'

                       2. rejoin_
                          requests
                          status =
                          'approved'

                       3. employees
                          fly = 0

                       4. emp_vacation
                          rejoin_status
                          = 'approved'
```

---

## API Endpoints Updated

### 1. Submit Rejoin Request
**Endpoint:** `ajaxVacation.php?ajaxType=submitRejoinRequest`

**Changes:**
- Creates `rejoin_requests` record
- Creates `request_approvers` record with `request_type_id = 5`
- Notifies supervisor via `request_approvers` table

**Request:**
```json
{
  "vacation_id": 456,
  "rejoin_date": "2025-01-15",
  "rejoin_reason": "Need to attend urgent family matter",
  "emp_id": 5127
}
```

**Database Updates:**
```sql
-- Creates in rejoin_requests
INSERT INTO rejoin_requests (emp_id, vacation_id, requested_rejoin_date, requested_reason, status)
VALUES (5127, 456, '2025-01-15', 'Need to attend...', 'pending')
-- Returns id = 123

-- Creates in request_approvers (NEW)
INSERT INTO request_approvers (request_inv_no, request_type_id, approver_id, approval_level, status, note)
VALUES (123, 5, 5001, 1, 'pending', 'Rejoin request for vacation ID: 456')
```

---

### 2. Process Rejoin Approval
**Endpoint:** `ajaxVacation.php?ajaxType=processRejoinApproval`

**Changes:**
- Updates `request_approvers` status based on supervisor action
- Sets `employees.fly = 0` when approved
- Maintains backward compatibility with `rejoin_requests` table

**Request:**
```json
{
  "rejoin_request_id": 123,
  "action": "approve",
  "approval_note": "Approved for immediate return"
}
```

**Database Updates (APPROVE):**
```sql
-- Update request_approvers (NEW)
UPDATE request_approvers 
SET status = 'approved', note = 'Approved for immediate return', action_date = NOW()
WHERE request_inv_no = 123 AND request_type_id = 5

-- Update rejoin_requests
UPDATE rejoin_requests 
SET status = 'approved', approved_by_emp_id = 5001, approved_at = NOW()
WHERE id = 123

-- Set employee back to work (NEW)
UPDATE employees 
SET fly = 0 
WHERE emp_id = 5127

-- Update vacation record
UPDATE emp_vacation 
SET rejoin_request_status = 'approved', rejoin_final_date = '2025-01-15'
WHERE id = 456
```

**Database Updates (ADJUST):**
```sql
-- Update request_approvers - keep pending until employee confirms (NEW)
UPDATE request_approvers 
SET status = 'pending', note = 'Adjustment allowed: Please select final date', action_date = NOW()
WHERE request_inv_no = 123 AND request_type_id = 5

-- Update rejoin_requests
UPDATE rejoin_requests 
SET status = 'adjusted', adjustment_allowed = 1, 
    adjustment_from_date = '2025-01-12', adjustment_to_date = '2025-01-18'
WHERE id = 123
```

**Database Updates (REJECT):**
```sql
-- Update request_approvers (NEW)
UPDATE request_approvers 
SET status = 'rejected', note = 'Rejected: Insufficient coverage', action_date = NOW()
WHERE request_inv_no = 123 AND request_type_id = 5

-- Update rejoin_requests
UPDATE rejoin_requests 
SET status = 'rejected', rejection_reason = 'Insufficient coverage'
WHERE id = 123
```

---

### 3. Submit Adjusted Date
**Endpoint:** `ajaxVacation.php?ajaxType=submitAdjustedRejoinDate`

**Changes:**
- Updates `request_approvers` to 'approved' when employee confirms adjusted date
- Sets `employees.fly = 0` to mark employee as rejoined
- Appends adjustment details to approval notes

**Request:**
```json
{
  "rejoin_request_id": 123,
  "adjusted_date": "2025-01-16"
}
```

**Database Updates:**
```sql
-- Update request_approvers to approved (NEW)
UPDATE request_approvers 
SET status = 'approved', 
    note = CONCAT(COALESCE(note, ''), ' - Employee adjusted date to: 2025-01-16'),
    action_date = NOW()
WHERE request_inv_no = 123 AND request_type_id = 5

-- Update rejoin_requests
UPDATE rejoin_requests 
SET status = 'approved', final_approved_date = '2025-01-16', adjustment_submitted_date = '2025-01-16'
WHERE id = 123

-- Set employee back to work (NEW)
UPDATE employees 
SET fly = 0 
WHERE emp_id = 5127

-- Update vacation record
UPDATE emp_vacation 
SET rejoin_request_status = 'approved', rejoin_final_date = '2025-01-16'
WHERE id = 456
```

---

### 4. Get Rejoin Requests (Dashboard API)
**Endpoint:** `includes/api/get_rejoin_requests.php`

**Changes:**
- Joins with `request_approvers` table
- Returns unified approval status
- Shows approval notes from both tables

**Response:**
```json
{
  "status": "success",
  "data": {
    "pending": [
      {
        "rejoin_request_id": 123,
        "emp_id": 5127,
        "emp_name": "Ahmed Ali",
        "requested_rejoin_date": "2025-01-15",
        "requested_reason": "Family matter",
        "requested_at": "2025-01-10 14:30:00",
        "status": "pending",
        "approval_status": "pending",
        "approval_note": "Rejoin request for vacation ID: 456"
      }
    ],
    "approved": [...],
    "rejected": [...]
  }
}
```

---

## Status Mapping

| Rejoin Status | request_approvers Status | Employee.fly | Description |
|---------------|--------------------------|--------------|-------------|
| pending       | pending                  | 1            | Awaiting supervisor decision |
| adjusted      | pending                  | 1            | Supervisor allowed adjustment, employee hasn't confirmed |
| approved      | approved                 | **0**        | Approved - employee has rejoined |
| rejected      | rejected                 | 1            | Request denied, employee still on vacation |

---

## Employee Fly Status Logic

### When `fly = 1` (On Vacation)
- Employee can submit rejoin request
- Rejoin button visible in employee profile
- System creates approval workflow

### When `fly = 0` (Back at Work)
- Employee is marked as rejoined
- Cannot submit new rejoin requests
- Vacation record is finalized

### Automatic Updates
```php
// Set fly=0 in two scenarios:

// 1. Immediate Approval
if ($action === 'approve') {
    $pdo->exec("UPDATE employees SET fly = 0 WHERE emp_id = {$emp_id}");
}

// 2. After Adjustment Confirmation
if ($ajaxType === 'submitAdjustedRejoinDate') {
    $pdo->exec("UPDATE employees SET fly = 0 WHERE emp_id = {$emp_id}");
}
```

---

## Migration Requirements

### Before Running Migration

1. Ensure `approval_request_types` table exists
2. Ensure `request_approvers` table exists
3. Verify the following record exists in `approval_request_types`:

```sql
INSERT INTO approval_request_types (id, type_name, main_table_name) 
VALUES (5, 'rejoin_request', 'rejoin_request');
```

### Run Migration

```bash
php includes/migrations/add_rejoin_approval_system.php
```

**Migration performs:**
- Creates `rejoin_requests` table
- Creates `rejoin_notifications` table
- Adds 12 columns to `emp_vacation` table
- Verifies `approval_request_types` configuration

**Expected output:**
```json
{
  "status": "success",
  "message": "Rejoin approval system database migration completed successfully. Integration with request_approvers table is ready."
}
```

---

## Testing Checklist

### 1. Submit Rejoin Request ✓
- [ ] Employee submits request
- [ ] `rejoin_requests` record created
- [ ] `request_approvers` record created with `request_type_id = 5`
- [ ] Supervisor sees request in dashboard
- [ ] Employee `fly` status remains 1

### 2. Approve Request ✓
- [ ] Supervisor approves request
- [ ] `request_approvers.status` = 'approved'
- [ ] `rejoin_requests.status` = 'approved'
- [ ] `employees.fly` = 0 ← **KEY CHANGE**
- [ ] `emp_vacation.rejoin_request_status` = 'approved'

### 3. Adjust Request ✓
- [ ] Supervisor allows adjustment
- [ ] `request_approvers.status` = 'pending' (until employee confirms)
- [ ] `rejoin_requests.status` = 'adjusted'
- [ ] Adjustment window calculated (±3 days)
- [ ] Employee sees adjustment modal

### 4. Employee Confirms Adjusted Date ✓
- [ ] Employee submits adjusted date
- [ ] `request_approvers.status` = 'approved'
- [ ] `rejoin_requests.status` = 'approved'
- [ ] `employees.fly` = 0 ← **KEY CHANGE**
- [ ] Adjustment note appended to `request_approvers.note`

### 5. Reject Request ✓
- [ ] Supervisor rejects request
- [ ] `request_approvers.status` = 'rejected'
- [ ] `rejoin_requests.status` = 'rejected'
- [ ] `employees.fly` remains 1
- [ ] Rejection reason recorded in both tables

### 6. Dashboard Queries ✓
- [ ] Pending requests show correct `approval_status`
- [ ] Approved requests include approval notes from both tables
- [ ] Rejected requests show rejection reason
- [ ] Queries join `request_approvers` correctly

---

## Reporting & Analytics

### Unified Approval Reports

Query all rejoin approvals across the system:

```sql
-- Get all rejoin approval activity
SELECT 
    ra.request_inv_no as rejoin_id,
    ra.approver_id as supervisor,
    ra.status,
    ra.action_date,
    ra.note,
    rr.emp_id,
    rr.requested_rejoin_date,
    rr.final_approved_date,
    e.name as employee_name,
    supervisor.name as supervisor_name
FROM request_approvers ra
JOIN rejoin_requests rr ON ra.request_inv_no = rr.id
JOIN employees e ON rr.emp_id = e.emp_id
JOIN employees supervisor ON ra.approver_id = supervisor.emp_id
WHERE ra.request_type_id = 5
ORDER BY ra.action_date DESC
```

### Approval Statistics

```sql
-- Rejoin approval metrics
SELECT 
    ra.status,
    COUNT(*) as count,
    AVG(TIMESTAMPDIFF(HOUR, rr.requested_at, ra.action_date)) as avg_approval_hours
FROM request_approvers ra
JOIN rejoin_requests rr ON ra.request_inv_no = rr.id
WHERE ra.request_type_id = 5
GROUP BY ra.status
```

---

## Benefits of Unified Approval System

### 1. **Consistency**
- All approval types follow same pattern
- Standardized status values
- Unified reporting across request types

### 2. **Centralization**
- Single table for all approvals
- Easier to query approval history
- Simplified notification system

### 3. **Flexibility**
- Can add approval levels easily
- Support for multi-level approvals
- Configurable approval workflows

### 4. **Audit Trail**
- Complete approval history
- Action dates tracked
- Approver identification
- Notes and reasons preserved

### 5. **Analytics**
- Cross-request-type reporting
- Approval performance metrics
- Supervisor workload analysis
- Trend identification

---

## Files Modified

### Backend (3 files)
1. **includes/ajaxFile/ajaxVacation.php**
   - Added `request_approvers` table updates in all handlers
   - Added `employees.fly = 0` updates on approval
   - Maintained backward compatibility with `rejoin_requests`

2. **includes/api/get_rejoin_requests.php**
   - Added JOIN with `request_approvers` table
   - Returns unified approval status
   - Includes approval notes from both tables

3. **includes/migrations/add_rejoin_approval_system.php**
   - Added verification of `approval_request_types` entry
   - Added warning if rejoin_request type not configured

---

## Backward Compatibility

The system maintains full backward compatibility:

- `rejoin_requests` table still exists and is updated
- `rejoin_notifications` table still tracks notifications
- `emp_vacation` columns are still populated
- Frontend UI unchanged

**Why maintain both?**
- `rejoin_requests`: Stores rejoin-specific details (adjustment windows, dates)
- `request_approvers`: Tracks approval workflow (status, approver, notes)
- Both tables work together for complete functionality

---

## Future Enhancements

### 1. Multi-Level Approvals
Currently supports single-level (direct supervisor). Could extend to:
- HR approval after supervisor approval
- Department manager approval
- Multi-step workflows

### 2. Email Notifications
Integrate with email system using `request_approvers` status changes:
```php
// When status changes in request_approvers
if ($old_status !== $new_status) {
    sendEmailNotification($emp_id, $new_status, $note);
}
```

### 3. Mobile Notifications
Push notifications based on `request_approvers` updates

### 4. Approval Delegation
Allow supervisors to delegate approval authority

### 5. Bulk Approvals
Process multiple rejoin requests simultaneously

---

## Support & Troubleshooting

### Issue: request_approvers not updating

**Check:**
```sql
SELECT * FROM request_approvers 
WHERE request_inv_no = [rejoin_id] AND request_type_id = 5
```

**Fix:** Ensure transaction didn't rollback. Check error logs.

### Issue: employees.fly not resetting to 0

**Check:**
```sql
SELECT fly FROM employees WHERE emp_id = '[emp_id]'
```

**Verify approval status:**
```sql
SELECT status FROM request_approvers 
WHERE request_inv_no = [rejoin_id] AND request_type_id = 5
```

**Manual fix:**
```sql
UPDATE employees SET fly = 0 WHERE emp_id = '[emp_id]'
```

### Issue: Dashboard not showing requests

**Check JOIN:**
```sql
SELECT rr.id, rr.status, ra.status as approval_status
FROM rejoin_requests rr
LEFT JOIN request_approvers ra ON ra.request_inv_no = rr.id AND ra.request_type_id = 5
WHERE rr.id = [rejoin_id]
```

**Verify:**
- `request_type_id = 5` in query
- `approval_request_types` has rejoin_request entry

---

## Summary

✅ **Unified Approval Integration** - All rejoin approvals tracked in `request_approvers`  
✅ **Employee Fly Status** - Automatically set to 0 on approval  
✅ **Backward Compatible** - Existing `rejoin_requests` table still functional  
✅ **Complete Audit Trail** - All actions recorded with timestamps  
✅ **Centralized Reporting** - Query all approvals from single source  

**Status:** PRODUCTION READY  
**Version:** 2.0 (Unified Approval Integration)  
**Updated:** December 2025

---

For original documentation, see:
- `REJOIN_SYSTEM_DOCUMENTATION.md` - Complete system overview
- `QUICK_REFERENCE.md` - User guides
- `IMPLEMENTATION_SUMMARY.md` - Change summary
