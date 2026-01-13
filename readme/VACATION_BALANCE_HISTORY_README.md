# Vacation Balance History Implementation

## Overview

The **Vacation Balance History** feature tracks all daily vacation balance updates, allowing you to:
- ✅ Audit every balance change
- ✅ Detect calculation errors (negative balances, mismatches)
- ✅ Identify calculation issues before they become problems
- ✅ Review historical trends
- ✅ Compare day-to-day changes

## Setup Instructions

### 1. Create the History Table

Run the SQL script to create the `emp_vacation_balance_history` table:

```bash
mysql -u root -p almutlak_db < /xampp/htdocs/almutlak/system/sql/add_vacation_balance_history_table.sql
```

Or execute directly in phpMyAdmin using this file:
- Location: `/system/sql/add_vacation_balance_history_table.sql`

### 2. Files Modified/Created

| File | Purpose |
|------|---------|
| `sql/add_vacation_balance_history_table.sql` | Table definition |
| `sql/vacation_balance_history_guide.sql` | Useful queries and documentation |
| `cron_update_vacation_balances.php` | Modified to insert history records |
| `vacation_balance_history.php` | New viewer UI |

### 3. How It Works

Every time the cron job runs (`cron_update_vacation_balances.php`):

1. **Calculates** the live vacation balance using `VacationCalculator`
2. **Compares** with the old balance stored in `emp_vacation_balance`
3. **Inserts** a history record into `emp_vacation_balance_history` with:
   - Old balance (before update)
   - New balance (calculated)
   - Change amount and status
   - All calculation details (earned, used, carryover, period info)
   - Timestamp of when update occurred

## Accessing the History

### GUI Viewer

Visit this page in your browser:
```
http://your-domain/almutlak/system/vacation_balance_history.php
```

**Features:**
- Filter by Employee ID, Date Range
- Show only changed balances
- Show only error records
- View all calculation details
- Statistics dashboard

### Database Queries

Use the provided SQL queries in `sql/vacation_balance_history_guide.sql`:

#### Find Negative Balances (Critical)
```sql
SELECT * FROM emp_vacation_balance_history 
WHERE new_available_balance < 0
ORDER BY update_date DESC;
```

#### View Specific Employee History
```sql
SELECT * FROM emp_vacation_balance_history 
WHERE emp_id = '1061'
ORDER BY update_date DESC;
```

#### Daily Summary
```sql
SELECT 
  update_date,
  COUNT(*) AS total_updates,
  SUM(CASE WHEN balance_changed THEN 1 ELSE 0 END) AS changed,
  SUM(CASE WHEN new_available_balance < 0 THEN 1 ELSE 0 END) AS negative
FROM emp_vacation_balance_history 
GROUP BY update_date
ORDER BY update_date DESC;
```

## Understanding the Data

### Table Columns

**Key Fields:**
- `emp_id` - Employee ID
- `update_date` - Date the update ran (YYYY-MM-DD)
- `old_available_balance` - Balance before update
- `new_available_balance` - Balance after update
- `change_amount` - Difference (new - old)
- `balance_changed` - Boolean flag if value changed

**Calculation Fields:**
- `earned_days` - Days earned in the current period
- `used_days` - Days deducted from vacation
- `carryover_days` - Days brought forward from previous period
- `total_days` - Period allocation (should stay constant)

**Period Fields:**
- `period_start` - Start of vacation period
- `period_end` - End of vacation period

**Status Fields:**
- `calculation_status` - success/warning/error/manual
- `notes` - Any calculation warnings

## Troubleshooting Examples

### Problem: Employee 1061 Balance Goes to -13 Then Resets to 0

**Investigation:**
```sql
SELECT * FROM emp_vacation_balance_history 
WHERE emp_id = '1061' 
  AND update_date BETWEEN '2026-01-06' AND '2026-01-07'
ORDER BY update_date, created_at;
```

**What to Look For:**
- `total_days` should be constant (not changing daily)
- `used_days` should only increase (not decrease)
- `carryover_days` should stay the same within a period
- Emergency vacations should NOT be in `used_days`

### Problem: Negative Balance Detected

**Check:**
```sql
SELECT 
  emp_id,
  update_date,
  old_available_balance,
  new_available_balance,
  earned_days,
  used_days,
  carryover_days
FROM emp_vacation_balance_history 
WHERE new_available_balance < 0
ORDER BY update_date DESC
LIMIT 20;
```

**Common Causes:**
1. ❌ Emergency vacations being counted in `used_days`
2. ❌ `total_days` being overwritten with balance value
3. ❌ Holiday calculations incorrect
4. ❌ Rejoin dates causing balance recalculation

## Fixes Applied (Jan 11, 2026)

### Fix #1: Emergency Vacations Excluded from Balance
- **File:** `vacation_calculator.php` line 273
- **Change:** Removed 'emergency' from used_days query
- **Effect:** Emergency vacations no longer deduct balance

### Fix #2: total_days Stabilized
- **File:** `vacation_calculator.php` line 243
- **Change:** Return `$total_vac_days` instead of `$final_available`
- **Effect:** Period allocation stays constant

### Fix #3: Cron Only Updates available_balance
- **File:** `cron_update_vacation_balances.php` line 245
- **Change:** Don't update `total_days` on cron runs
- **Effect:** Period allocation never overwritten

## Verification Process

### Daily Checklist

1. **Check for Negative Balances:**
   ```sql
   SELECT COUNT(*) FROM emp_vacation_balance_history 
   WHERE update_date = CURDATE() AND new_available_balance < 0;
   ```

2. **Verify Employee 1061:**
   ```sql
   SELECT 
     update_date,
     old_available_balance,
     new_available_balance,
     change_amount
   FROM emp_vacation_balance_history 
   WHERE emp_id = '1061' 
     AND update_date = CURDATE();
   ```

3. **Check Daily Summary:**
   ```sql
   SELECT 
     COUNT(*) AS updates,
     SUM(CASE WHEN balance_changed THEN 1 ELSE 0 END) AS changed,
     SUM(CASE WHEN new_available_balance < 0 THEN 1 ELSE 0 END) AS errors
   FROM emp_vacation_balance_history 
   WHERE update_date = CURDATE();
   ```

## Maintenance

### Backup History Data
```bash
# Weekly backup
mysqldump -u root -p almutlak_db emp_vacation_balance_history > /backup/history_$(date +%Y%m%d).sql
```

### Archive Old Records (Optional)
```sql
-- Keep last 90 days, delete older
DELETE FROM emp_vacation_balance_history 
WHERE update_date < DATE_SUB(CURDATE(), INTERVAL 90 DAY);
```

### Monitor Table Size
```sql
SELECT 
  ROUND(((data_length + index_length) / 1024 / 1024), 2) AS size_mb
FROM information_schema.TABLES 
WHERE table_schema = 'almutlak_db' 
  AND table_name = 'emp_vacation_balance_history';
```

## Support

If you encounter issues:

1. Check the history for the specific employee
2. Compare old vs new balances
3. Review calculation fields (earned, used, carryover)
4. Look for negative balance records
5. Check the cron logs: `/cron_logs/vacation_balance_update_*.log`

---

**Last Updated:** January 11, 2026  
**Version:** 1.0  
**Status:** Production Ready
