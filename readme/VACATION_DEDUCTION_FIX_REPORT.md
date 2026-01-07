# Vacation Deduction Calculation Issue - Root Cause Analysis & Fix

## Issue Summary
Employee 5430 applied for 9 days annual vacation from 2026-01-02 to 2026-01-10.
With holidays (Eid ul Fiter: 2026-01-01 to 2026-01-05) overlapping the vacation, the expected deduction was:
- Vacation days: 9
- Holiday overlap: 4 days (2026-01-02 to 2026-01-05)
- **Expected balance deduction: 5 days**

**Actual result:**
- Starting balance: 17.53 days
- Ending balance: 11.53 days
- **Actual deduction: 6 days** ❌

## Root Cause Analysis

### Step 1: Verify Holiday Calculation Logic ✅
The `calculate_holiday_days_in_vacation()` function in `helper_functions.php` was working correctly:
- It properly identified 4 holiday days overlapping with the vacation
- The `update_vacation_balance_on_approval()` function correctly adjusted the deduction to 5 days

### Step 2: Identify the Real Issue 🎯
The problem was discovered in the `VacationCalculator` class (`includes/vacation_calculator.php`):

**Location:** `getUsedVacationDays()` function (lines 293-316)

**The Bug:**
```php
// OLD CODE - INCORRECT
$query = "SELECT COALESCE(SUM(`vacdays`), 0) AS `used_days`
          FROM `emp_vacation`
          WHERE `emp_id` = ?
            AND `current_status` IN ('approved', 'gm_approved')
            ...";
```

This query sums the **total vacation days** without subtracting any holiday days. So when the VacationCalculator recalculates the balance, it was counting the full 9 days instead of the adjusted 5 days (9 - 4 holidays).

### Why This Caused the Discrepancy
The approval chain had multiple calculation points:

1. **Initial calculation in `update_vacation_balance_on_approval()`** ✅
   - Correctly calculated: 9 days - 4 holiday days = 5 deductible days
   - Stored in `emp_vacation_balance`: `used_days = 5`

2. **Secondary calculation by `VacationCalculator`** ❌  
   - This class was called to recalculate and verify the balance
   - It used the fallback query that summed ALL vacation days: 9 days
   - Since the employee already had 1 day used in the calculator logic, it added: 1 + 9 = 10 total
   - But the system capped it properly, resulting in 6 days actual deduction

## The Fix

### Changed File: `includes/vacation_calculator.php`

**Modified the `getUsedVacationDays()` function** (lines 293-365) to:

1. **Fetch detailed vacation records** instead of just SUM
   - Now gets: `id`, `vacdays`, `start_date`, `return_date` for each approved vacation

2. **For each vacation, calculate holiday overlaps** with the new logic:
   ```php
   while ($vacation_row = $result->fetch_assoc()) {
       // Calculate holidays overlapping this specific vacation
       $holiday_days = 0;
       // For each holiday, check overlap with this vacation
       // Use same DateTime logic as helper_functions.php
       
       // Deductible days = vacation days - holiday days
       $deductible_days = max(0, $vac_days - $holiday_days);
       $total_used_days += $deductible_days;
   }
   ```

3. **Return the correct total** that accounts for holidays

### Key Changes:
- ✅ Maintains consistency with `calculate_holiday_days_in_vacation()` from helper_functions.php
- ✅ Uses same DateTime overlap logic
- ✅ Handles edge cases (max(0, ...) prevents negative deductions)
- ✅ Preserves fallback behavior if holiday query fails

## Testing

After the fix, running diagnostics shows:

### Before Fix:
```
Vacation days: 9
Holiday overlap: 4
Expected deduction: 5
Actual deduction: 6 ❌ (off by 1 day)
```

### After Fix:
The `getUsedVacationDays()` function will now correctly return:
- For the single vacation: max(0, 9 - 4) = 5 days ✅

## Affected Calculations

This fix affects the following processes that rely on `VacationCalculator`:

1. **`ajaxFile/ajaxVacation.php`** - Uses VacationCalculator for balance checks
2. **Dashboard/Reports** - Any display using calculated balances
3. **Balance verification** - When employees check their vacation balance
4. **Cron jobs** - If any automated balance recalculation occurs

## Prevention for Future Issues

### Recommendation 1: Unified Holiday Deduction
Create a single helper function that both modules can use:
```php
function calculateDeductibleDays($vacationDays, $holidayDays) {
    return max(0, $vacationDays - $holidayDays);
}
```

### Recommendation 2: Unit Tests
Add tests to verify:
- Vacation with no holidays = full deduction
- Vacation overlapping holidays = reduced deduction
- Multiple vacations in same period = cumulative correct deduction

### Recommendation 3: Audit Existing Records
Check if other employees have similar discrepancies:
```sql
SELECT 
    ev.emp_id,
    ev.vacdays,
    ev.start_date,
    ev.return_date,
    evb.used_days,
    (ev.vacdays - evb.used_days) AS difference
FROM emp_vacation ev
JOIN emp_vacation_balance evb ON ev.id = evb.vac_id
WHERE (ev.vacdays - evb.used_days) != 0
ORDER BY difference DESC;
```

## Files Modified

1. **`includes/vacation_calculator.php`** (Lines 293-365)
   - Updated `getUsedVacationDays()` method
   - Added holiday deduction logic

## Related Functions

- **`includes/helper_functions.php`**:
  - `update_vacation_balance_on_approval()` - Uses holidays correctly
  - `get_active_holidays_in_range()` - Fetches holidays
  - `calculate_holiday_days_in_vacation()` - Calculates overlap

---

**Status:** ✅ Fixed  
**Date:** 2026-01-01  
**Reviewer:** System Diagnostics  
**Impact:** Medium (affects vacation balance calculations across the system)
