# Used Days Double Deduction - Critical Bug Fix Summary

## The Problem You Reported

```
Scenario:
- total_days = 17.83
- used_days = 1 (already used before)
- Employee applies for 10 days vacation
- After approval: available_balance = 6.83
- ❌ WRONG: used_days updated to 11

Issue: "Why used_days will be deducted again if already used before?"
```

## Root Cause

The bug was in `update_vacation_balance_on_approval()` function:

When calculating new used_days, the function got the **latest** balance record. But if that record WAS for the current vacation being approved, it would:
1. Get: `old_used_days = 10` (from the current vacation's balance record)
2. Add: `days_to_deduct = 10` (the new vacation days)
3. Result: `new_used_days = 20` ❌ (DOUBLE DEDUCTED!)

## The Fix - TWO PARTS

### Part 1: Distinguish Current Vacation from Previous Vacations

**Before Fix:**
```php
if ($latest_balance) {
    $old_used_days = (float)$latest_balance['used_days'];  // ❌ Could be THIS vacation!
}
```

**After Fix:**
```php
if ($latest_balance) {
    $latest_vac_id = (int)($latest_balance['vac_id'] ?? 0);
    
    if ($latest_vac_id === $vac_id_safe) {
        // ✅ Latest balance IS for THIS vacation
        // Query for the PREVIOUS balance record (from a different vacation)
        $sql_prev_balance = "SELECT `used_days` FROM `emp_vacation_balance` 
                             WHERE `emp_id` = ? AND `vac_id` != ? ORDER BY `id` DESC LIMIT 1";
        // Use previous vacation's used_days, not current one!
        $old_used_days = (float)$prev_balance['used_days'];
    } else {
        // ✅ Latest balance is from a different vacation, use it directly
        $old_used_days = (float)$latest_balance['used_days'];
    }
}
```

**Impact:** Now we only get `used_days` from OTHER vacations, not the current one.

### Part 2: Detect Duplicate Deduction Early

**Before Fix:**
```php
if ($row_check_vac) {
    // Balance record exists for this vacation
    // Check if used_days matches the vacation days... confusing logic
}
```

**After Fix:**
```php
if ($row_check_vac) {
    // Balance record exists for this vac_id = Already deducted!
    // Simply return true (no error, already handled)
    error_log("Vacation ID {$vac_id_safe} already has a balance record. Skipping duplicate deduction.");
    return true;
}
```

**Impact:** If we ever try to deduct the same vacation twice, the guard catches it immediately.

---

## How It Works Now

### Correct Calculation:

```
Employee: 5 days already used, wants to take 10 more days

Step 1: Get old_used_days
  - Query: "Get balance from OTHER vacations only"
  - Result: old_used_days = 5 ✅ (not including current vacation)

Step 2: Calculate new_used_days
  - Formula: new_used_days = old_used_days + days_to_deduct
  - Calculation: 5 + 10 = 15 ✅
  - Result: correct! (5 already used + 10 new = 15 total)

Step 3: If same vacation is processed again (duplicate)
  - Guard detects: "Vacation already in balance table"
  - Action: Return early (skip processing)
  - Result: used_days stays at 15 ✅ (no double deduction)
```

---

## Files Updated

✅ [includes/helper_functions.php](includes/helper_functions.php)
- Function: `update_vacation_balance_on_approval()`
- Lines: 3260-3410

---

## Testing

### Test 1: First Vacation (No Previous Used Days)
```
Employee: 30 total days, used_days = 0
Apply: 10 days vacation
Expected: used_days = 10 ✅
```

### Test 2: Second Vacation (Previous Used Days Exist)
```
Employee: 30 total days, used_days = 10
Apply: 5 days vacation
Expected: used_days = 15 ✅ (10 + 5, not 20)
```

### Test 3: Duplicate Approval Call
```
Employee applies and HR_Payroll approves vacation ID 100
Same vacation ID 100 is approved again (duplicate)
Expected: No change, used_days stays correct ✅
Log: "Vacation already has balance record. Skipping duplicate deduction."
```

---

## Key Changes Summary

| Item | Before | After |
|------|--------|-------|
| used_days calculation | Could double-add | Only adds new days once |
| Duplicate detection | Not checked | Detected via vac_id |
| Error handling | Silent | Logs guard triggers |
| Safety | Vulnerable | Protected |

---

## When This Matters

This fix prevents the bug when:
1. ✅ Employee already has used_days from previous vacation
2. ✅ New vacation is applied and approved
3. ✅ HR_Payroll approval is processed

**Before Fix:** used_days would be calculated wrongly, showing double deduction
**After Fix:** used_days is calculated correctly, only adding the new vacation days

