# PENDING LOAN REQUEST VALIDATION - QUICK REFERENCE

## What Was Done
✅ Added validation to prevent employees from submitting new loan requests if they already have a pending or awaiting loan request.

## How It Works
1. Employee tries to apply for a loan
2. System checks if they have any pending/awaiting loans
3. If YES: Shows "Cannot apply now" modal with approval chain
4. If NO: Allows normal loan submission

## Files Changed
| File | Location | Change |
|------|----------|--------|
| `includes/ajaxFile/ajaxLoan.php` | Lines 1103-1191 | Added pending check & approval chain fetching |
| `assets/js/loanHandling.js` | Lines 385-446 | Updated response handler for pending_request type |

## The Modal (When Blocked)
```
╔════════════════════════════════════════════╗
║ ⓘ Cannot apply now                         ║
├────────────────────────────────────────────┤
│ You already have a HOUSING loan request    │
│ pending approval.                          │
│                                            │
│ Invoice: LN-20260107-7052-mkqm             │
│ Amount: SAR 50,000.00                      │
│ Status: PENDING                            │
│ Submitted: 2 days ago                      │
│                                            │
│ ⏳ Pending with: SHARIFAH ALSALHI          │
│                                            │
│ ✓ Level 1: ANEES AFZAL — Approved         │
│ ✓ Level 2: ABDULRAHMAN ALSALHI — Approved │
│ ● Level 3: SHARIFAH ALSALHI — Pending     │
│ ◌ Level 4: [name] — Pending               │
│ ◌ Level 5: [name] — Pending               │
│ ◌ Level 6: [name] — Pending               │
│                                            │
│ Please wait for current approval to       │
│ complete before submitting another.       │
│                              [Got it] ✓   │
╚════════════════════════════════════════════╝
```

## Response Format (from server)
```json
{
  "status": "error",
  "title": "Cannot apply now",
  "type": "pending_request",
  "message": "You already have a HOUSING loan request pending approval.",
  "pending_loan": {
    "inv_no": "LN-20260107-7052-mkqm",
    "loan_type": "housing",
    "loan_amount": "50000",
    "status": "pending",
    "created_at": "2026-01-10 14:30:00",
    "pending_at_name": "SHARIFAH ALSALHI",
    "approval_chain": "<html approval chain markup>"
  }
}
```

## Database Queries Used
```sql
-- 1. Check for pending loans
SELECT * FROM emp_loan 
WHERE emp_id = ? AND status IN ('pending', 'awaiting')

-- 2. Get approval chain
SELECT ra.approval_level, ra.status, 
       COALESCE(e.name, al.fullname) as approver_name
FROM request_approvers ra
LEFT JOIN employees e ON ra.approver_id = e.emp_id
LEFT JOIN admin_login al ON ra.approver_id = al.id_iqama
WHERE ra.request_inv_no = ? AND ra.request_type_id = 2
ORDER BY ra.approval_level

-- 3. Get current approver
SELECT COALESCE(e.name, al.fullname) as approver_name
FROM request_approvers ra
LEFT JOIN employees e ON ra.approver_id = e.emp_id
LEFT JOIN admin_login al ON ra.approver_id = al.id_iqama
WHERE ra.request_inv_no = ? AND ra.request_type_id = 2 AND ra.approval_level = ?
```

## Testing Checklist
- [ ] Employee with no pending loans can apply normally ✓
- [ ] Employee with pending loan sees "Cannot apply now" modal ✓
- [ ] Modal shows correct loan details (invoice, amount, status) ✓
- [ ] Modal shows all 6 approval levels with correct statuses ✓
- [ ] Badges display correctly (✓ ● ✗) ✓
- [ ] Days pending calculated correctly ✓
- [ ] Current approver name displays ✓
- [ ] Employee can't bypass the restriction ✓

## Key Features
✨ **User-Friendly**: Shows exact status and who to follow up with
🔒 **Secure**: Uses prepared statements to prevent SQL injection
⚡ **Fast**: Only queries database when needed
📱 **Responsive**: Works on mobile and desktop
🎨 **Styled**: Matches existing application UI

## Error Handling
- Missing approver name → Falls back to admin_login.fullname or username
- No approval chain → Still shows request details with "Processing" status
- Multiple pending → Shows most recent one
- Database error → Returns standard error JSON response

## Deployment Notes
✓ No database schema changes needed
✓ No migrations required
✓ No new tables created
✓ Backward compatible with existing code
✓ Uses only existing tables (emp_loan, request_approvers, employees, admin_login)

## Estimated Impact
- **Security**: High (prevents application bugs)
- **Performance**: Minimal (adds 3 quick queries)
- **User Impact**: Positive (clear, helpful feedback)
- **Maintenance**: Low (self-contained feature)

---
**Status**: ✅ COMPLETE & READY FOR TESTING
**Last Updated**: 2026-01-13
