# Used Days Double Deduction Fix - CRITICAL LOGIC ERROR

## Problem Statement

When an employee applies for vacation and receives HR_Payroll approval, the `used_days` column in `emp_vacation_balance` table was being incorrectly calculated, causing previously used days to be deducted AGAIN.

### Example of the Bug:

```
Initial state:
- total_days = 17.83 (annual allocation)
- used_days = 1 (already used before)
- available_balance = 16.83 (17.83 - 1)

Employee applies for 10 days vacation:
- Vacation approved and deducted

❌ WRONG RESULT (Before fix):
- used_days = 11 (which is 1 + 10)
- available_balance = 6.83 (17.83 - 11)
- But the calculation was adding old_used_days AGAIN!

✅ CORRECT RESULT (After fix):
- used_days = 11 (1 previous + 10 new)
- available_balance = 6.83 (17.83 - 11)
- Only the NEW vacation days (10) are deducted from available_balance
- Previous used_days (1) is already accounted for in available_balance
```

---

## Root Cause Analysis

The bug was in the `update_vacation_balance_on_approval()` function in [helper_functions.php](includes/helper_functions.php#L3260-L3380).

### How the Bug Occurred:

**INCORRECT LOGIC (Before Fix):**
```php
// Get the LATEST balance row
$latest_balance = ...query latest balance row...;

// If latest balance exists
if ($latest_balance) {
    $old_used_days = (float)$latest_balance['used_days'];  // ❌ Could be THIS vacation's record!
}

// Later: Calculate new used_days
$new_used_days = $old_used_days + $days_to_deduct;  // ❌ Double-adding!
```

**The Problem:**
- When a vacation is first deducted, a new balance record is created with `vac_id = X`
- If the same vacation is processed again (duplicate approval call), the query gets the LATEST balance row
- That latest row IS for the current vacation
- We get the already-deducted `used_days`, then add the vacation days AGAIN!
- Result: Double deduction

**Example Scenario:**
1. Vacation ID 100 for Employee 5, 10 days
2. First approval calls `update_vacation_balance_on_approval(5, 100)`
   - Gets: old_used_days = 0, creates balance record with used_days = 10, vac_id = 100
3. Duplicate approval calls `update_vacation_balance_on_approval(5, 100)` again
   - ❌ Gets latest balance: used_days = 10, vac_id = 100 (for THIS vacation)
   - ❌ Calculates: new_used_days = 10 + 10 = 20
   - ❌ Updates: used_days = 20 (DOUBLE DEDUCTED!)

---

## The Fix

Two critical changes were implemented in [helper_functions.php](includes/helper_functions.php#L3260-L3380):

### Fix #1: Distinguish Between Current Vacation and Previous Vacations

**Location:** Lines 3260-3340

```php
if ($latest_balance) {
    // Get the latest balance row's vac_id
    $latest_vac_id = (int)($latest_balance['vac_id'] ?? 0);
    
    // ✅ NEW: Check if this balance record IS for the current vacation
    if ($latest_vac_id === $vac_id_safe && $latest_vac_id > 0) {
        // This balance record is for the CURRENT vacation being approved!
        // Query for the balance record BEFORE this one
        $sql_prev_balance = "SELECT `used_days`, `carryover_days`, `total_days`, `period_start`, `period_end` 
                             FROM `emp_vacation_balance` 
                             WHERE `emp_id` = ? AND `vac_id` != ? 
                             ORDER BY `id` DESC LIMIT 1";
        
        // Get the PREVIOUS balance from a DIFFERENT vacation
        // This gives us the correct old_used_days (before current vacation)
        if ($prev_balance) {
            $old_used_days = (float)$prev_balance['used_days'];  // ✅ From OTHER vacation
        }
    } else {
        // Latest balance is from a DIFFERENT vacation, use it as-is
        $old_used_days = (float)$latest_balance['used_days'];  // ✅ Correct
    }
}
```

**Why This Works:**
- If latest balance is for the current vacation → Get the one BEFORE it
- If latest balance is for a different vacation → Use it directly
- This ensures `old_used_days` only includes OTHER vacations, not the current one

### Fix #2: Early Detection of Duplicate Deduction

**Location:** Lines 3380-3410

```php
if ($row_check_vac) {
    // A balance record exists for this vac_id
    // This means this vacation has ALREADY been deducted!
    
    $sql_get_current = "SELECT `used_days`, `vac_id`, `id` FROM `emp_vacation_balance` WHERE `vac_id` = ? LIMIT 1";
    
    if ($row_current = ...execute...) {
        $existing_record_id = (int)$row_current['id'];
        // ✅ A record exists for THIS vacation = Already deducted
        error_log("INFO: Vacation ID {$vac_id_safe} already has a balance record. Skipping duplicate deduction.");
        return true; // Already processed, no error
    }
}
```

**Why This Works:**
- The `vac_id` column in `emp_vacation_balance` ties each balance record to a specific vacation
- If a record exists with this `vac_id`, the vacation has been deducted
- Subsequent calls simply return true (already handled)

---

## How the Fixed Logic Works

```
SCENARIO: Employee 5 applies for 10-day vacation (ID 100)

FIRST APPROVAL (HR_Payroll approves):
1. Call: update_vacation_balance_on_approval(5, 100)
2. Get vacation: emp_id=5, vacdays=10
3. Query latest balance: Could find previous vacation record or nothing
4. If previous vacation exists: old_used_days = 1 (from that vacation)
5. Calculate: new_used_days = 1 + 10 = 11 ✅
6. Check if record for vac_id=100 exists: NO
7. INSERT new balance record:
   - emp_id = 5
   - vac_id = 100 (marks THIS vacation)
   - used_days = 11
   - available_balance = 6.83

SECOND APPROVAL (Duplicate call - prevents double deduction):
1. Call: update_vacation_balance_on_approval(5, 100)
2. Get vacation: emp_id=5, vacdays=10
3. Query latest balance: finds record with vac_id=100
4. Check: latest_vac_id === vac_id_safe? YES
5. Query PREVIOUS balance: find record with different vac_id
6. Get: old_used_days = 1 (from PREVIOUS vacation)
7. Calculate: new_used_days = 1 + 10 = 11 ✅
8. Check if record for vac_id=100 exists: YES
9. GUARD TRIGGERS: "Vacation already has balance record. Skipping."
10. Return true (already handled) ✅

RESULT: used_days = 11 (correct) - NO DOUBLE DEDUCTION
```

---

## Key Differences

| Aspect | Before Fix | After Fix |
|--------|-----------|----------|
| Latest Balance Query | Gets ANY latest balance | Distinguishes current vs other |
| Current Vacation Detection | Not checked | Checks `vac_id` and queries previous |
| Duplicate Calls | Would double-deduct | Returns early with guard |
| Used Days Calculation | Could include current twice | Only adds new vacation days once |
| Error Log | Silent failure | Logs when guard triggers |

---

## Testing the Fix

### Test Case 1: Single Vacation Approval

```
Employee: ID 5, Contract Period 30 days
Initial: used_days = 0, available = 30

Step 1: Apply for 10 days vacation (ID 100)
Step 2: HR_Payroll approves
Expected Result:
  - used_days = 10 ✅
  - available_balance = 20 ✅
  - vac_id in balance record = 100
```

### Test Case 2: Multiple Vacations

```
Employee: ID 5, Contract Period 30 days

Step 1: Vacation 1 (10 days) approved
  - used_days = 10, available = 20
  
Step 2: Vacation 2 (5 days) approved
  - Query latest balance: finds vacation 1 record (vac_id = 100)
  - latest_vac_id (100) !== vac_id_safe (200)
  - Use latest balance directly: old_used_days = 10
  - new_used_days = 10 + 5 = 15 ✅
  - available_balance = 15 ✅

Result: ✅ Correct cumulative deduction
```

### Test Case 3: Duplicate Approval (Double-Deduction Prevention)

```
Employee: ID 5

Step 1: Vacation ID 100 approved
  - Creates balance record: vac_id = 100, used_days = 10

Step 2: Same vacation ID 100 approved AGAIN (duplicate)
  - Query latest balance: finds vacation 100 record
  - latest_vac_id (100) === vac_id_safe (100)
  - Query previous: finds different vacation record
  - old_used_days = X (from previous, not current)
  - Check if record for vac_id=100 exists: YES
  - GUARD TRIGGERS ✅
  - Return true (already handled)

Result: ✅ No double deduction, used_days stays at 10
```

---

## Monitoring & Debugging

### Error Log Entries

```bash
# Guard triggered (duplicate deduction prevented)
grep "already has a balance record" error.log

# Vacation deduction applied
grep "Inserted or updated balance record for vacation ID" error.log

# Holiday deduction info
grep "has.*holiday days" error.log
```

### Debug Checks

**Query to verify used_days correctness:**
```sql
SELECT 
  e.emp_id, 
  e.name,
  evb.vac_id,
  evb.used_days,
  evb.total_days,
  evb.available_balance,
  evb.remaining_balance,
  evb.last_updated
FROM emp_vacation_balance evb
JOIN employees e ON e.emp_id = evb.emp_id
WHERE e.emp_id = 5
ORDER BY evb.id DESC;
```

**Expected:** Each row represents one vacation, and `used_days` is cumulative from all vacations.

### Common Issues After Fix

1. **used_days seems lower than expected**
   - Could be holiday days were subtracted
   - Check: `SELECT * FROM holidays WHERE active = 1`

2. **used_days jumped unexpectedly**
   - Verify vacation applications aren't overlapping
   - Check for overlapping vacation rejection/reapplication

3. **Balance record for vac_id appears twice**
   - Indicates duplicate records - should not happen with fix in place
   - Check logs for which vac_id has duplicates

---

## Files Modified

- [includes/helper_functions.php](includes/helper_functions.php#L3260-L3380)
  - Function: `update_vacation_balance_on_approval()`
  - Changes: Fixed old_used_days calculation and added vacation ID detection

---

## Impact Summary

✅ **Fixes:**
- No more double deduction of used_days
- Correct cumulative used_days tracking
- Proper handling of multiple vacation approvals

✅ **Preserves:**
- All existing balance record structures
- Holiday deduction logic
- Encashment handling
- Three-column synchronization

✅ **Safety:**
- Backward compatible (no schema changes)
- Guard prevents duplicate processing
- Comprehensive error logging
- Early returns on guard triggers

---

## Timeline

- **Issue Identified:** Double deduction of used_days on HR_Payroll approval
- **Root Cause:** Not distinguishing between current vacation and previous vacation balance records
- **Fix Applied:** Added vac_id-based distinction and early duplicate detection
- **Test Status:** Ready for production validation

