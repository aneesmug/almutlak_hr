# Three-Column Balance Synchronization Fix

## Problem Statement

After vacation approval, the three critical balance columns were NOT being updated with the deducted vacation days:
- `total_days` - Original contract days (30 for annual)
- `remaining_balance` - Days left after deduction
- `available_balance` - Available balance (should equal remaining)

**Result:** No days were deducted from the balance, and employee could apply unlimited vacations.

---

## Root Cause

The old code had a guard that was returning early WITHOUT executing the UPDATE statement:

```php
// OLD BUGGY CODE
if ($row_check_vac) {
    // Check if vacation has been deducted
    if ($row_current) {
        // ❌ RETURNS HERE WITHOUT UPDATING!
        return true;
    }
    
    // ❌ This code never executes because of early return above
    // UPDATE statement is never executed
}
```

---

## The Fix

### Part 1: Remove the Early Return Guard

**OLD CODE:**
```php
if ($row_check_vac) {
    // Check if vacation deducted
    if ($row_current) {
        error_log("Skipping duplicate deduction");
        return true;  // ❌ Returns without updating!
    }
    
    // Never reaches here
}
```

**NEW CODE:**
```php
if ($row_check_vac) {
    // Always update! No early return!
    // ✅ Synchronize the three columns:
    // - total_days (constant)
    // - used_days (cumulative)
    // - remaining_balance (total_days - used_days)
    // - available_balance (same as remaining)
    
    UPDATE `emp_vacation_balance` SET 
        `total_days` = ?,
        `used_days` = ?,
        `remaining_balance` = ?,
        `available_balance` = ?,
        ... other columns ...
    WHERE `vac_id` = ?
    
    return true; // After successful update
}
```

### Part 2: Correct Balance Calculation

**The logic for calculating the three columns:**

```php
// Step 1: Get old used_days (cumulative from previous vacations)
$old_used_days = (from previous vacation record)

// Step 2: Calculate new cumulative used_days
$new_used_days = $old_used_days + $days_to_deduct
// Example: 1 + 10 = 11 ✅

// Step 3: Calculate remaining balance
$new_remaining_balance = $total_contract_days - $new_used_days
// Example: 30 - 11 = 19 ✅

// Step 4: Set available balance to same as remaining
$new_available_balance = $new_remaining_balance
// Example: 19 ✅

// Step 5: Update all three columns
UPDATE SET:
  total_days = 30 (never changes)
  used_days = 11 (cumulative)
  remaining_balance = 19 (30 - 11)
  available_balance = 19 (same as remaining)
```

---

## How It Works Now

### Example: Employee with 30-day annual contract

**Initial State (Before any vacation):**
```
total_days = 30
used_days = 0
remaining_balance = 30
available_balance = 30
```

**Employee applies for 10-day vacation:**
```
Calculation:
  new_used_days = 0 + 10 = 10
  new_remaining_balance = 30 - 10 = 20
  new_available_balance = 20

After approval:
  total_days = 30 (unchanged)
  used_days = 10 (deducted)
  remaining_balance = 20 ✅
  available_balance = 20 ✅
```

**Employee applies for another 5-day vacation:**
```
Calculation:
  old_used_days = 10 (from previous vacation)
  new_used_days = 10 + 5 = 15
  new_remaining_balance = 30 - 15 = 15
  new_available_balance = 15

After approval:
  total_days = 30 (unchanged)
  used_days = 15 (cumulative)
  remaining_balance = 15 ✅
  available_balance = 15 ✅
```

---

## Column Synchronization Rules

| Column | Purpose | Calculation | Updates |
|--------|---------|-------------|---------|
| `total_days` | Contract annual days | Fixed from employees.vac_period | Never changes after insert |
| `used_days` | Cumulative days used | old_used_days + new_vacation_days | Increases on each vacation |
| `remaining_balance` | Days left available | total_days - used_days | Decreases on each vacation |
| `available_balance` | Currently available | Same as remaining_balance | Decreases on each vacation |

**Rule:** `remaining_balance` MUST EQUAL `available_balance` at all times.

---

## Code Changes

### Location 1: Update Block (When balance record exists)
**File:** [includes/helper_functions.php](includes/helper_functions.php#L3383-L3430)

```php
if ($row_check_vac) {
    // Always update with synchronized values
    UPDATE `emp_vacation_balance` SET 
        `total_days` = ?,          // stays constant
        `used_days` = ?,           // new cumulative value
        `remaining_balance` = ?,   // calculated value
        `available_balance` = ?,   // must equal remaining
        ...
    WHERE `vac_id` = ?;
}
```

### Location 2: Insert Block (When creating new balance record)
**File:** [includes/helper_functions.php](includes/helper_functions.php#L3432-L3475)

```php
else {
    // Create new record with synchronized values
    INSERT INTO `emp_vacation_balance` 
    SET:
        `total_days` = ?,          // original allocation
        `used_days` = ?,           // cumulative
        `remaining_balance` = ?,   // calculated
        `available_balance` = ?    // must equal remaining
    ...
}
```

---

## Verification Query

Run this query to verify the synchronization:

```sql
SELECT 
  e.emp_id,
  e.name,
  evb.vac_id,
  evb.total_days,
  evb.used_days,
  evb.remaining_balance,
  evb.available_balance,
  (evb.total_days - evb.used_days) as calculated_remaining,
  CASE 
    WHEN evb.remaining_balance = evb.available_balance THEN '✅ SYNCED'
    ELSE '❌ OUT OF SYNC'
  END as sync_status,
  evb.last_updated
FROM emp_vacation_balance evb
JOIN employees e ON e.emp_id = evb.emp_id
ORDER BY evb.emp_id, evb.last_updated DESC;
```

**Expected Results:**
- All rows should show: `remaining_balance = available_balance`
- `remaining_balance = total_days - used_days`
- `sync_status = '✅ SYNCED'`

---

## Error Log Monitoring

The function now logs all operations:

```bash
# Check what's being updated
grep "SUCCESS: Updated balance record" error.log
# Example: SUCCESS: Updated balance record for vacation ID 100 - emp_id=5, total_days=30, used_days=11, remaining_balance=19, available_balance=19

grep "SUCCESS: Inserted balance record" error.log
# Example: SUCCESS: Inserted balance record for vacation ID 100 - emp_id=5, total_days=30, used_days=10, remaining_balance=20, available_balance=20

# Check for errors
grep "ERROR:" error.log
```

---

## Testing Checklist

- [ ] Approve first vacation for an employee
  - Verify: `used_days` deducted correctly
  - Verify: `remaining_balance = total_days - used_days`
  - Verify: `available_balance = remaining_balance`

- [ ] Approve second vacation for same employee
  - Verify: `used_days` is cumulative (old + new)
  - Verify: `remaining_balance` recalculated correctly
  - Verify: Three columns synchronized

- [ ] Check multiple employees
  - Verify: Each has independent balance records
  - Verify: No data crossing between employees

- [ ] Check error logs
  - Verify: SUCCESS messages show correct calculations
  - Verify: No duplicate update attempts

---

## Impact Summary

✅ **Fixes:**
- Days are NOW deducted from balance on approval
- Three columns are synchronized automatically
- Cumulative calculation prevents double-counting

✅ **Preserves:**
- All existing data structures
- Backward compatibility
- Holiday deduction logic
- Encashment handling

✅ **Safety:**
- Comprehensive logging of all operations
- Validates calculations before update
- Prevents negative balances (caps at 0)

---

## Files Modified

- [includes/helper_functions.php](includes/helper_functions.php)
  - Function: `update_vacation_balance_on_approval()`
  - Section 1: Lines 3383-3430 (UPDATE logic)
  - Section 2: Lines 3432-3475 (INSERT logic)

---

## Timeline

- **Issue:** No vacation days deducted from balance after approval
- **Root Cause:** Early return guard skipped the UPDATE statement
- **Fix Applied:** Removed early return, ensured UPDATE always executes
- **Status:** Ready for testing

