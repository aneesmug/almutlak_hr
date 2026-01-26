# Quick Reference - Vacation Balance Update Rules

## The Rule

✅ **`available_balance` - Updated by multiple endpoints based on business logic**
🔒 **`opening_balance` - ONLY updated by cron job (never by any user endpoint)**

---

## Where Can Each Column Be Updated?

### available_balance ✅ (Can be updated by)
```
1. cron_update_vacation_balances.php (Daily sync)
   - Set to: live_balance (calculated from employee contract)
   
2. helper_functions.php (Vacation deduction)
   - Set to: remaining_balance after deduction
   
3. ajaxVacation.php (Admin manual override)
   - Set to: admin-specified amount
   
4. ajaxVacation.php (INSERT new records)
   - Set to: initial calculated balance
```

### opening_balance 🔒 (ONLY updated by)
```
CRON JOB ONLY:
- cron_update_vacation_balances.php
  * Daily UPDATE (line 274)
  * Daily INSERT for new employees (line 167)

NEVER by:
- ❌ Any user-facing endpoint
- ❌ Vacation deduction logic
- ❌ Manual balance adjustments
- ❌ Admin overrides
```

### remaining_balance (Can be updated by)
```
1. helper_functions.php (Vacation deduction)
   - Decreases when days are deducted
   
2. ajaxVacation.php (Extra days deduction)
   - Adjusts for additional days taken
   
3. ajaxVacation.php (Manual adjustments)
   - Admin corrections
```

### total_days (Can be updated by)
```
1. helper_functions.php (Vacation deduction)
   - Decreases when days are deducted
   
2. ajaxVacation.php (Manual balance save)
   - Admin overrides
```

---

## Updated Endpoints Summary

| File | Line | Operation | Columns Updated | Safe? |
|------|------|-----------|-----------------|-------|
| cron_update_vacation_balances.php | 274 | UPDATE daily | available_balance, opening_balance | ✅ **FIXED** |
| cron_update_vacation_balances.php | 167 | INSERT new emp | available_balance, opening_balance | ✅ Safe |
| ajaxVacation.php | 4412 | Manual override | available_balance | ✅ Safe |
| ajaxVacation.php | 2650 | Deduct extra | remaining_balance | ✅ Safe |
| ajaxVacation.php | 5760 | Manager deduct | remaining_balance | ✅ Safe |
| ajaxVacation.php | 6030 | Employee adjust | remaining_balance | ✅ Safe |
| helper_functions.php | 3434 | Vacation deduct | available_balance, remaining_balance, total_days, used_days | ✅ Safe |

---

## What Changed (Jan 22, 2026)

### BEFORE ❌
Cron was updating 4 columns:
```php
UPDATE emp_vacation_balance SET
  available_balance = ?,
  opening_balance = ?,
  remaining_balance = ?,  // ← WRONG: Conflicts with deduction logic
  last_updated = NOW()
```

### AFTER ✅
Cron now updates ONLY 2 columns:
```php
UPDATE emp_vacation_balance SET
  available_balance = ?,
  opening_balance = ?,
  last_updated = NOW()
```

---

## Key Principles

1. **Separation of Concerns**
   - Cron = Daily balance sync (opening_balance always set)
   - Deduction = Day-to-day changes (remaining_balance only)

2. **opening_balance is Sacred 🔒**
   - Cron sets it once per day
   - It represents the starting balance for the day
   - Never modified by any other process

3. **available_balance is Dynamic**
   - Starts each day = opening_balance
   - Updated during day by deductions/adjustments
   - Updated next day by cron

4. **Data Integrity**
   - All updates follow documented business rules
   - No rogue endpoints modifying core fields
   - Audit trail in emp_vacation_balance_history

---

## Verification Query

Check that opening_balance equals available_balance after cron runs:

```sql
-- Should return 0 rows (all equal after cron)
SELECT id, emp_id, available_balance, opening_balance
FROM emp_vacation_balance
WHERE available_balance != opening_balance
LIMIT 10;
```

---

## Contact / Issues

If `opening_balance` is modified by any endpoint other than cron:
1. Check [VACATION_BALANCE_UPDATE_AUDIT.md](VACATION_BALANCE_UPDATE_AUDIT.md)
2. Review [AVAILABLE_BALANCE_UPDATE_SUMMARY.md](AVAILABLE_BALANCE_UPDATE_SUMMARY.md)
3. Verify cron_update_vacation_balances.php is running daily

---

**Last Updated:** January 22, 2026
**Status:** ✅ Production Ready
