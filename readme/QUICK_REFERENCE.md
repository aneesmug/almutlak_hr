# Quick Reference: Loan Deduction Mode System

## 🎯 What Problem Does This Solve?

**Before:** When loans were modified or regenerated, the system would sometimes create duplicate payroll deductions, causing incorrect salary calculations.

**After:** Users can choose "Automatic" or "Manual" deduction mode. When regenerating, the system safely deletes old entries before creating new ones—no duplicates.

---

## 📋 Three Main Features

### 1. Deduction Mode Selection
```
Location: Employee Loan Tab → Loan Details
UI: Dropdown selector
Options:
  • Automatic Monthly (system creates payroll entries)
  • Manual Addition (user adds each month manually)
```

### 2. Safe Regeneration
```
When user modifies loan + clicks regenerate:
  Step 1: Delete OLD payroll entries for this loan
  Step 2: Create NEW payroll entries based on updated loan terms
  Result: No duplicates, clean calculation
```

### 3. Mode-Based Payroll Integration
```
Automatic Mode: System auto-creates monthly deductions
Manual Mode: System skips payroll creation (user adds manually)
```

---

## 🚀 Quick Start (For Users)

### Change Deduction Mode
1. Open employee profile → Loan tab
2. Find "Deduction Mode" dropdown
3. Select "Automatic Monthly" or "Manual Addition"
4. Confirm in dialog
5. If switching to automatic, click "Regenerate" (optional)

### Regenerate After Loan Modification
1. Edit loan (installments, amount, etc.)
2. Save changes
3. Scroll to "Edit Installments" button area
4. Click "Regenerate Deductions"
5. Confirm in dialog
6. Wait for success message

---

## 💾 Database Change

**One new column added to `emp_loan` table:**

```
deduction_mode ENUM('automatic', 'manual') DEFAULT 'automatic'
```

**What it stores:**
- `automatic` = System creates/updates payroll entries automatically
- `manual` = User manually adds payroll entries each month

**Execution:**
Run the migration file: `add_deduction_mode_to_loan.sql`

---

## 📡 API Endpoints (for developers)

### Update Deduction Mode
```
POST includes/ajaxFile/ajaxLoan.php
Data:
  - ajaxType: 'updateDeductionMode'
  - loan_id: (integer)
  - deduction_mode: 'automatic' or 'manual'

Response:
  {
    "status": 200,
    "message": "Deduction mode updated to automatic",
    "deduction_mode": "automatic"
  }
```

### Purge and Regenerate Deductions
```
POST includes/ajaxFile/ajaxLoan.php
Data:
  - ajaxType: 'purgeAndRegenerateLoanDeductions'
  - loan_id: (integer)

Response:
  {
    "status": 200,
    "message": "Deductions purged and regenerated"
  }
```

---

## 🔧 Files Modified

1. **add_deduction_mode_to_loan.sql** (NEW)
   - Database migration to add deduction_mode column

2. **includes/ajaxFile/ajaxLoan.php** (MODIFIED)
   - Added updateDeductionMode() function
   - Added purgeAndRegenerateLoanDeductions() function
   - Updated integrate_loan_to_payroll() to check mode
   - Added switch cases for new AJAX handlers

3. **view_employee.php** (MODIFIED)
   - Added deduction mode dropdown UI
   - Added JavaScript event handler for mode changes
   - Added regenerateLoanDeductions() function

---

## ✅ Testing Checklist (Quick Version)

- [ ] Run migration SQL
- [ ] Create new loan → verify automatic mode, payroll entries created
- [ ] Open loan → verify dropdown visible
- [ ] Change mode → verify confirmation dialog
- [ ] Confirm change → verify database updated
- [ ] Try regenerate → verify old entries deleted, new ones created
- [ ] Check no duplicates in payroll_deductions table

---

## ⚠️ Important Notes

### Before Deployment
- Backup database
- Test in staging first
- All existing loans will be set to 'automatic' (backward compatible)

### User Communication
- New dropdown is optional (defaults work as before)
- "Automatic" = existing system behavior (safe default)
- "Manual" = for special cases where user wants full control

### Error Recovery
- If regeneration fails: database rolls back (no partial updates)
- If connection lost: transaction rolls back (no orphaned entries)
- Errors shown to user with descriptive messages

---

## 🔍 Troubleshooting

| Problem | Check |
|---------|-------|
| Dropdown not visible | Loan exists in database? deduction_mode column created? |
| Error on mode change | Database connection working? Loan ID valid? |
| Regenerate doesn't work | Check payroll_deductions table for matching inv_no |
| Duplicates still appearing | Check if regenerate actually deleted old entries |
| Database error on regenerate | Check MySQL error log, verify tables have required columns |

---

## 📞 Contact Support

If issues occur, check:
1. VERIFICATION_CHECKLIST.md (detailed testing steps)
2. IMPLEMENTATION_SUMMARY.md (overview and deployment guide)
3. LOAN_DEDUCTION_MODE_IMPLEMENTATION.md (technical details)

---

## 📊 System Behavior Summary

```
┌─────────────────────────────────────────────────────────┐
│                  NEW LOAN CREATED                        │
│              (Default: AUTOMATIC mode)                   │
└────────────────────┬────────────────────────────────────┘
                     │
       ┌─────────────┴─────────────┐
       │                           │
   ┌───▼───┐                   ┌───▼────┐
   │PAYROLL│                   │ USER   │
   │ENTRIES│                   │ADDS    │
   │CREATE │                   │MANUALLY│
   │AUTO   │                   │        │
   │       │                   │        │
   │MONTHLY│                   │ PER    │
   │       │                   │ MONTH  │
   └───────┘                   └────────┘
   (AUTOMATIC)                 (MANUAL)
      MODE                       MODE

LOAN MODIFIED?
     │
     ▼
REGENERATE BUTTON CLICKED
     │
     ├─ Step 1: DELETE old payroll entries
     ├─ Step 2: CREATE new payroll entries
     └─ Result: No duplicates ✓
```

---

**Last Updated:** 2025-11-25  
**Version:** 1.0  
**Status:** Production Ready
