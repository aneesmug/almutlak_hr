# Pending Loan Request Validation - Testing Guide

## Feature Overview
This feature prevents employees from submitting new loan requests while they already have pending or awaiting loan requests. When attempted, they see a user-friendly modal showing the current approval status.

## Implementation Details

### Files Modified
1. **includes/ajaxFile/ajaxLoan.php** - `apply_for_loan()` function (lines 1092-1203)
   - Added pending/awaiting check at start of function
   - Queries emp_loan table for existing pending requests
   - Fetches approval chain from request_approvers table
   - Returns JSON with pending request details

2. **assets/js/loanHandling.js** - Response handler (lines 390-446)
   - Detects 'pending_request' response type
   - Builds SweetAlert2 modal with approval chain
   - Displays employee-friendly information about why they can't apply

### Database Queries Used

**Check for Pending Loans:**
```sql
SELECT id, inv_no, loan_type, loan_amount, status, created_at 
FROM emp_loan 
WHERE emp_id = ? AND status IN ('pending', 'awaiting')
```

**Get Approval Chain:**
```sql
SELECT ra.approval_level, ra.status, 
       COALESCE(e.name, al.fullname, al.username) as approver_name,
       ra.action_date
FROM request_approvers ra
LEFT JOIN employees e ON ra.approver_id = e.emp_id
LEFT JOIN admin_login al ON ra.approver_id = al.id_iqama
WHERE ra.request_inv_no = ? AND ra.request_type_id = 2
ORDER BY ra.approval_level ASC
```

**Get Current Approver Name:**
```sql
SELECT COALESCE(e.name, al.fullname, al.username) as approver_name
FROM request_approvers ra
LEFT JOIN employees e ON ra.approver_id = e.emp_id
LEFT JOIN admin_login al ON ra.approver_id = al.id_iqama
WHERE ra.request_inv_no = ? AND ra.request_type_id = 2 AND ra.approval_level = ?
```

## Testing Scenarios

### Scenario 1: Employee with NO Pending Loans
**Expected Result:** Normal loan application proceeds
1. Employee applies for loan
2. Passes pending check
3. Shows normal loan form and confirmation
4. Loan is submitted successfully

### Scenario 2: Employee with PENDING Loan
**Expected Result:** Shows "Cannot apply now" modal
1. Employee with pending loan tries to apply
2. Backend detects pending status
3. Fetches approval chain details
4. Returns `type: 'pending_request'` response
5. Frontend shows modal with:
   - "Cannot apply now" title with info icon
   - Current loan details (Invoice, Amount, Status, Days pending)
   - "Pending with: [Approver Name]"
   - Full approval chain with badges:
     * ✓ Green badge = Approved
     * ● Yellow badge = Pending
     * ✗ Red badge = Rejected

### Scenario 3: Employee with AWAITING Loan
**Expected Result:** Same as Scenario 2
- "Awaiting" status is also blocked from new submissions
- Shows same modal with approval chain information

## Modal Display Example

```
╔════════════════════════════════════════════╗
║ ⓘ Cannot apply now                         ║
├────────────────────────────────────────────┤
│ You already have a HOUSING loan request    │
│ pending approval.                          │
│                                            │
│ ┌──────────────────────────────────────┐  │
│ │ Invoice: LN-20260107-7052-mkqm       │  │
│ │ Amount: SAR 50,000.00                 │  │
│ │ Status: PENDING                       │  │
│ │ Submitted: 2 days ago                 │  │
│ └──────────────────────────────────────┘  │
│                                            │
│ ⏳ Pending with: ANEES AFZAL               │
│ ┌──────────────────────────────────────┐  │
│ │ ✓ Level 1: ANEES AFZAL — Approved    │  │
│ │ ✓ Level 2: ABDULRAHMAN ALSALHI       │  │
│ │ ● Level 3: SHARIFAH ALSALHI — Pending│  │
│ │ ◌ Level 4: [waiting...]              │  │
│ └──────────────────────────────────────┘  │
│                                            │
│ Please wait for current approval to       │
│ complete before submitting another loan.  │
│                                    [Got it]│
╚════════════════════════════════════════════╝
```

## Code Flow

### Backend (PHP)
```
apply_for_loan()
├─ Check required fields
├─ Sanitize inputs
├─ [NEW] Check for pending/awaiting loans
│  ├─ Query emp_loan for pending status
│  ├─ If found:
│  │  ├─ Fetch approval chain from request_approvers
│  │  ├─ Build HTML with approval badges
│  │  ├─ Get current pending approver name
│  │  └─ Return JSON with type='pending_request'
│  └─ Else: Continue to next checks
├─ Validate supervisor assignment
├─ Process loan application
└─ Return success response
```

### Frontend (JavaScript)
```
submit loan form
├─ Collect form data (emp_id, loan_amount, loan_type, installments)
├─ Send AJAX to ajaxLoan.php with ajaxType='apply_loan'
└─ Handle response:
   ├─ If type === 'pending_request':
   │  ├─ Extract pending loan details
   │  ├─ Calculate days pending from created_at
   │  ├─ Build SweetAlert2 HTML with approval chain
   │  ├─ Display modal with "Cannot apply now"
   │  └─ Show "Got it" button to close
   └─ Else:
      └─ Show regular success/error message
```

## Response JSON Structure

```json
{
  "status": "error",
  "title": "Cannot apply now",
  "message": "You already have a HOUSING loan request pending approval.",
  "type": "pending_request",
  "pending_loan": {
    "inv_no": "LN-20260107-7052-mkqm",
    "loan_type": "housing",
    "loan_amount": "50000",
    "status": "pending",
    "created_at": "2026-01-10 14:30:00",
    "pending_at_name": "SHARIFAH ALSALHI",
    "approval_chain": "<div style=\"...\">...</div>"
  }
}
```

## Approval Chain HTML Structure

```html
<div style="display:flex; align-items:center; padding:8px 0; border-bottom:1px solid #eee;">
  <span class="badge badge-success" style="min-width:30px; margin-right:10px;">✓</span>
  <span style="flex:1;">Level 1: APPROVER_NAME — Approved</span>
</div>
<div style="display:flex; align-items:center; padding:8px 0; border-bottom:1px solid #eee;">
  <span class="badge badge-warning" style="min-width:30px; margin-right:10px;">●</span>
  <span style="flex:1;">Level 2: APPROVER_NAME — Pending</span>
</div>
<div style="display:flex; align-items:center; padding:8px 0; border-bottom:1px solid #eee;">
  <span class="badge badge-danger" style="min-width:30px; margin-right:10px;">✗</span>
  <span style="flex:1;">Level 3: APPROVER_NAME — Rejected</span>
</div>
```

## Error Handling

1. **Missing Approver:** Falls back to admin_login.fullname or username
2. **No Approval Chain:** Still shows request but with "Processing" as pending approver
3. **Multiple Pending Requests:** Shows the most recent one (LIMIT 1)
4. **Database Errors:** Propagates JSON error response

## Browser Compatibility

- Uses SweetAlert2 (already included in project)
- ES6 async/await for date calculations
- Flexbox for approval chain display (modern browsers)
- CSS badges (Bootstrap classes)

## Performance Considerations

- Three database queries per submission attempt (necessary for security)
- Uses prepared statements to prevent SQL injection
- Queries are indexed on (emp_id, status) and (request_inv_no, request_type_id, approval_level)
- Modal display is client-side only (no additional requests)

## Future Enhancements

1. Add option to view full details of existing pending loan
2. Show estimated completion date based on current approver
3. Add notification system to approvers about pending requests
4. Allow cancellation of old pending requests (with confirmation)
5. Track rejection reasons in approval chain display
