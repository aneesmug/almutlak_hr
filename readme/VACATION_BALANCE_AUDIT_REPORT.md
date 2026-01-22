# Vacation Balance Deduction Audit Report
**Date**: January 20, 2026  
**Scope**: Entire project - All vacation balance modification endpoints  

## Critical Fix Applied

### Issue Identified
The `rejectVacation` endpoint was **incorrectly refunding/restoring vacation days** when requests were rejected.

### Root Cause
Since the new logic dictates that days are **only deducted at FINAL APPROVAL** (not at submission), rejection at any approval stage should **NOT modify the balance** because no deduction has occurred yet.

### Fix Applied
**File**: `d:\xampp\htdocs\almutlak\system\includes\ajaxFile\ajaxVacation.php`  
**Lines**: 2466-2470

**Before** (Lines 2466-2533):
- Complex refund logic that restored `used_days`, `remaining_balance`, and `available_balance`
- Calculated new values after refund
- Updated `emp_vacation_balance` table

**After** (Lines 2466-2470):
```php
// NOTE: DO NOT REFUND/RESTORE VACATION BALANCE ON REJECTION
// Days are only deducted at FINAL APPROVAL, not at submission or rejection
// Therefore, rejection should not update the balance
```

---

## Complete Audit Results

### ✅ CORRECT: Endpoints That Properly Deduct Days

1. **`update_vacation_balance_on_approval()`** - `helper_functions.php` (Line 3201)
   - Deducts days ONLY on final approval
   - Checks `is_deductible` flag
   - Prevents duplicate deductions
   - Adjusts for holidays
   - **STATUS**: ✅ CORRECT

2. **`returnVacation`** - `ajaxVacation.php` (Line 2585)
   - Deducts **extra days** when employee returns late
   - Only updates balance when `actual_return_date > planned_return_date`
   - **STATUS**: ✅ CORRECT (legitimate extra days deduction)

3. **`processRejoinApproval`** - `ajaxVacation.php` (Line 5741)
   - Deducts **extra days** when supervisor approves delayed rejoin
   - Only for non-emergency vacations
   - **STATUS**: ✅ CORRECT (legitimate vacation extension)

4. **`submitAdjustedRejoinDate`** - `ajaxVacation.php` (Line 5964)
   - Deducts **extra days** when employee submits adjusted rejoin date
   - Only for non-emergency vacations
   - **STATUS**: ✅ CORRECT (legitimate vacation extension)

---

### ✅ CORRECT: Endpoints That Do NOT Modify Balance

5. **`rejectVacation`** - `ajaxVacation.php` (Line 2413) - **FIXED**
   - Now correctly skips balance restoration
   - **STATUS**: ✅ FIXED

6. **`applyVacation`** - `ajaxVacation.php` (Line 384)
   - Does NOT deduct balance on submission
   - Only validates balance is sufficient
   - **STATUS**: ✅ CORRECT

7. **`delete_vac.php`** - Legacy deletion script
   - Simple DELETE query, no balance restoration
   - **NOTE**: This file has SQL injection vulnerabilities (uses `$_GET['id']` directly)
   - **STATUS**: ⚠️ SECURITY ISSUE (separate from balance logic)

---

## Vacation Balance Flow (Correct Logic)

```
┌─────────────────────────────────────────────────────────────┐
│ 1. SUBMISSION (applyVacation)                               │
│    - Validates balance is sufficient                        │
│    - Creates vacation request                               │
│    - Balance: NO CHANGE                                     │
└─────────────────────────────────────────────────────────────┘
                            ↓
┌─────────────────────────────────────────────────────────────┐
│ 2. APPROVAL CHAIN (approveVacation)                         │
│    - Each approver reviews                                  │
│    - Balance: NO CHANGE (until final approval)              │
└─────────────────────────────────────────────────────────────┘
                            ↓
            ┌───────────────┴────────────────┐
            ↓                                ↓
┌─────────────────────────┐    ┌────────────────────────────┐
│ 3a. REJECTION           │    │ 3b. FINAL APPROVAL         │
│     - Updates status    │    │     - Calls update_        │
│     - Sends rejection   │    │       vacation_balance_    │
│       notification      │    │       on_approval()        │
│     - Balance: NO       │    │     - Deducts days from    │
│       CHANGE ✅         │    │       available_balance    │
└─────────────────────────┘    │     - Increases used_days  │
                               │     - Decreases remaining_ │
                               │       balance              │
                               └────────────────────────────┘
                                            ↓
                               ┌────────────────────────────┐
                               │ 4. EMPLOYEE GOES ON        │
                               │    VACATION                │
                               │    - Status: active        │
                               │    - fly flag = 1          │
                               └────────────────────────────┘
                                            ↓
                               ┌────────────────────────────┐
                               │ 5. RETURN (returnVacation) │
                               │    - If ON TIME: No change │
                               │    - If LATE: Deduct extra │
                               │      days ✅               │
                               └────────────────────────────┘
```

---

## Key Rules Enforced

### Balance Deduction Rules:
1. ✅ **Deduct ONLY at final approval** - Not at submission or intermediate approvals
2. ✅ **Deduct extra days for late returns** - When employee returns after planned date
3. ✅ **Deduct extra days for rejoin extensions** - When employee delays rejoin date
4. ✅ **Do NOT deduct for emergency vacations** - These are unpaid leave
5. ✅ **Do NOT refund on rejection** - Days were never deducted in the first place

### Files Modified:
- `includes/ajaxFile/ajaxVacation.php` (Line 2466-2470)

### Files Verified (No Issues):
- `includes/helper_functions.php` (update_vacation_balance_on_approval)
- `includes/vacation_calculator.php`
- `includes/delete_vac.php` (legacy, no balance impact)

---

## Recommendations

### Security Issues Found:
1. **`includes/delete_vac.php`** - SQL injection vulnerability
   - Uses `$_GET['id']` directly in query
   - Should use prepared statements
   - **Priority**: HIGH

### Testing Checklist:
- [ ] Test rejection at different approval levels
- [ ] Test final approval balance deduction
- [ ] Test late return extra days deduction
- [ ] Test rejoin extension extra days deduction
- [ ] Test emergency vacation (should NOT deduct balance)
- [ ] Test annual vacation (should deduct balance)

---

## Conclusion

✅ **All vacation balance endpoints have been audited**  
✅ **Critical refund bug in rejection handler has been FIXED**  
✅ **No other balance restoration/refund operations found**  
⚠️ **Security vulnerability in delete_vac.php noted (separate issue)**

The vacation balance deduction logic now correctly follows the rule:
**"Deduct ONLY at final approval, never on rejection or cancellation"**
