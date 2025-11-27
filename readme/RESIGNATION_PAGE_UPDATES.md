# Resignation Page Updates - Status Display & Action Button Authorization

## Changes Made to `all_resignations.php`

### 1. **Status Query Update (Line 165)**
Changed the JOIN condition to find "awaiting" approvals instead of "pending":
```php
-- FROM:
LEFT JOIN request_approvers ra_pending ON ra_pending.request_inv_no = r.request_inv_no 
     AND ra_pending.request_type_id = ? AND ra_pending.status = 'pending'

-- TO:
LEFT JOIN request_approvers ra_pending ON ra_pending.request_inv_no = r.request_inv_no 
     AND ra_pending.request_type_id = ? AND ra_pending.status = 'awaiting'
```

### 2. **Approval Chain Fetching (Lines 360-396)**
Added logic to fetch complete approval chain for each resignation:

```php
// Get approval chain details
$approval_chain = [];
$approver_stmt = $conDB->prepare("
    SELECT ra.approval_level, ra.status, ra.approver_id, e.name as approver_name
    FROM request_approvers ra
    LEFT JOIN employees e ON ra.approver_id = e.emp_id
    WHERE ra.request_inv_no = ? AND ra.request_type_id = ?
    ORDER BY ra.approval_level ASC
");
```

This fetches:
- All 3 approval levels (Manager, HR Operations, HR Payroll)
- Current status of each (awaiting, approved, rejected)
- Approver names

### 3. **User Action Authorization (Lines 378-384)**
Changed authorization logic to only show action buttons to users with pending approvals:

```php
// Determine if current user has pending approval
$user_has_pending_approval = false;
foreach ($approval_chain as $approval) {
    if ($approval['status'] === 'awaiting') {
        $awaiting_approver_id = $approval['approver_id'] ?? null;
        if ($awaiting_approver_id == $empid) {
            $user_has_pending_approval = true;
            break;
        }
    }
}

// Only show action buttons if current user has pending approval
$can_take_action = $user_has_pending_approval;
```

**Result:** Action buttons now only visible to the current approver with pending responsibility.

### 4. **Enhanced Status Display (Lines 410-460)**
Shows all approval levels with their status:

**For Pending Resignations:**
Each approval level displays as a colored badge below the main status:
- **Blue badge** `Manager: [Name]` - Awaiting approval
- **Green badge** `HR Operations: ✓` - Already approved  
- **Red badge** `HR Payroll: ✗` - Rejected

Example output:
```
Status: ⏳ Pending
Manager: John Smith
HR Operations: ✓
HR Payroll: (Not yet reached)
```

**Status Logic:**
```php
// For 'pending' status, shows all approval levels:
switch ($approval['status']) {
    case 'awaiting':
        $approval_badge = "<span class='badge badge-info'>{$level_name}: {$approval_name}</span>";
    case 'approved':
        $approval_badge = "<span class='badge badge-success'>{$level_name}: ✓</span>";
    case 'rejected':
        $approval_badge = "<span class='badge badge-danger'>{$level_name}: ✗</span>";
}
```

---

## Behavior Changes

### Before
- Status showed: "Pending with [Current Approver Name]"
- Action buttons visible to: System Admin, HR, or current approver
- Approvers could see others' resignation requests but couldn't take action

### After
- Status shows: "Pending" with all 3 approval levels listed below
  - Each level shows approver name and status (awaiting/approved/rejected)
- Action buttons visible to: **ONLY the current approver with pending responsibility**
- Non-approvers see full approval chain but cannot take action
- Previous approvers see their approval checkmark (✓)
- Rejected levels show red ✗ mark

---

## SQL Query Behavior

### Main Query Change
The query now uses `status = 'awaiting'` in the LEFT JOIN to identify the current pending approver:
- This matches the actual status values in `request_approvers` table
- The workflow uses "awaiting" for pending approvals

### Approval Chain Fetch
Runs for each resignation to fetch all 3 approval levels:
```sql
SELECT ra.approval_level, ra.status, ra.approver_id, e.name as approver_name
FROM request_approvers ra
LEFT JOIN employees e ON ra.approver_id = e.emp_id
WHERE ra.request_inv_no = ? AND ra.request_type_id = ?
ORDER BY ra.approval_level ASC
```

---

## Visual Example

### Card for Resignation in Progress

```
┌─────────────────────────────────┐
│ John Doe              ID: EMP123 │
├─────────────────────────────────┤
│ Submitted: 25 Nov 2025          │
│ Department: IT                  │
│ Designation: Senior Developer   │
│ Last Working Day: 15 Dec 2025   │
│                                 │
│ Status: ⏳ Pending              │
│ Manager: Ahmed Khan             │
│ HR Operations: ✓                │
│ HR Payroll: (awaiting)          │
├─────────────────────────────────┤
│ [View Button]                   │
│ (Action buttons hidden - user   │
│  is not the current approver)   │
└─────────────────────────────────┘
```

### Card for User Who Has Pending Approval

```
┌─────────────────────────────────┐
│ Sarah Miller          ID: EMP456 │
├─────────────────────────────────┤
│ Submitted: 20 Nov 2025          │
│ Department: Finance             │
│ Designation: Accountant         │
│ Last Working Day: 10 Dec 2025   │
│                                 │
│ Status: ⏳ Pending              │
│ Manager: ✓                      │
│ HR Operations: Your Turn        │
│ HR Payroll: (pending)           │
├─────────────────────────────────┤
│ [View Button]  [Actions ▼]      │
│                 - Approve       │
│                 - Reject        │
│ (Action buttons visible - user  │
│  is HR Operations approver)     │
└─────────────────────────────────┘
```

---

## Testing Checklist

- [ ] View resignation - All 3 approval levels visible in status
- [ ] Manager can see "Manager: [Name]" in blue
- [ ] HR Operations can see "HR Operations: [Name]" in blue when it's their turn
- [ ] Only current approver sees action buttons (Approve/Reject)
- [ ] After manager approves, status shows "Manager: ✓" and badge changes
- [ ] When rejected, show red ✗ badge
- [ ] Completed approvals show green ✓ badge
- [ ] Non-approvers cannot see action buttons
- [ ] Search and filter still work correctly
- [ ] Pagination displays correctly with updated status logic
