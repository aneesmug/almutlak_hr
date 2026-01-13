# Vacation Balance History - Quick Reference

## 🚀 Quick Start

### Access the History Viewer
```
http://your-domain/almutlak/system/vacation_balance_history.php
```

### Create the Table (One-time Setup)
```bash
mysql -u root -p almutlak_db < sql/add_vacation_balance_history_table.sql
```

---

## 📊 Key Tables

### emp_vacation_balance_history
**What it stores:** Daily snapshot of every employee's vacation balance update

**Key Columns:**
| Column | Type | Description |
|--------|------|-------------|
| `id` | INT | Primary key |
| `emp_id` | VARCHAR(50) | Employee ID |
| `update_date` | DATE | Date of update (YYYY-MM-DD) |
| `old_available_balance` | DECIMAL(10,2) | Balance before update |
| `new_available_balance` | DECIMAL(10,2) | Balance after update |
| `change_amount` | DECIMAL(10,2) | Difference (new - old) |
| `balance_changed` | BOOLEAN | True if balance changed |
| `earned_days` | DECIMAL(10,2) | Days earned (calculated) |
| `used_days` | DECIMAL(10,2) | Days used/deducted |
| `carryover_days` | DECIMAL(10,2) | Days from previous period |
| `total_days` | DECIMAL(10,2) | Period allocation |
| `calculation_status` | ENUM | success/warning/error/manual |
| `run_timestamp` | DATETIME | When update ran |

---

## 🔍 Common Queries

### Find Negative Balances (CRITICAL)
```sql
SELECT * FROM emp_vacation_balance_history 
WHERE new_available_balance < 0
ORDER BY update_date DESC;
```
**Alert:** Any negative balance indicates a calculation error!

### View One Employee's Full History
```sql
SELECT * FROM emp_vacation_balance_history 
WHERE emp_id = '1061'
ORDER BY update_date DESC;
```

### Compare Two Dates
```sql
SELECT 
  update_date,
  old_available_balance,
  new_available_balance,
  change_amount
FROM emp_vacation_balance_history 
WHERE emp_id = '1061' 
  AND update_date BETWEEN '2026-01-01' AND '2026-01-15'
ORDER BY update_date;
```

### Daily Summary
```sql
SELECT 
  update_date,
  COUNT(*) AS total_updates,
  SUM(CASE WHEN balance_changed THEN 1 ELSE 0 END) AS balances_changed,
  SUM(CASE WHEN new_available_balance < 0 THEN 1 ELSE 0 END) AS negative_errors,
  AVG(new_available_balance) AS avg_balance
FROM emp_vacation_balance_history 
WHERE update_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
GROUP BY update_date
ORDER BY update_date DESC;
```

### Detect Pattern Issues
```sql
-- Employees whose balance changes every single day
SELECT 
  emp_id,
  COUNT(*) AS days_changed,
  AVG(change_amount) AS avg_daily_change
FROM emp_vacation_balance_history 
WHERE balance_changed = TRUE
GROUP BY emp_id
HAVING COUNT(*) >= 20
ORDER BY COUNT(*) DESC;
```

### Check Emergency Vacations Not in Balance
```sql
-- Verify emergency vacations are NOT reducing balance
SELECT 
  h.emp_id,
  h.update_date,
  h.used_days,
  COUNT(*) AS emergency_vac_count
FROM emp_vacation_balance_history h
JOIN emp_vacation v ON h.emp_id = v.emp_id 
  AND v.fly_type = 'emergency' 
  AND v.current_status IN ('approved', 'gm_approved')
WHERE h.update_date = CURDATE()
GROUP BY h.emp_id, h.update_date;
```

---

## 🐛 Debugging Tips

### Issue: Employee Balance Goes Negative
**Check This:**
1. Is total_days constant? (Should stay the same)
2. Are emergency vacations in used_days? (Should be 0)
3. Did rejoin happen? (Check emp_vacation for rejoin_requests)

```sql
SELECT 
  update_date,
  total_days,
  used_days,
  earned_days,
  new_available_balance
FROM emp_vacation_balance_history 
WHERE emp_id = '1061' 
  AND update_date BETWEEN '2026-01-06' AND '2026-01-08'
ORDER BY update_date;
```

### Issue: Balance Changes Every Day (Normal)
**Expected:** Balance increases by ~0.08 per day (for 30-day annual allocation)

```sql
SELECT 
  update_date,
  old_available_balance,
  new_available_balance,
  (new_available_balance - old_available_balance) AS daily_accrual
FROM emp_vacation_balance_history 
WHERE emp_id = '1061' 
  AND balance_changed = TRUE
ORDER BY update_date DESC
LIMIT 30;
```

---

## 📈 Analysis Examples

### Track One Employee's Balance Trend
```sql
SELECT 
  update_date,
  new_available_balance,
  earned_days,
  used_days,
  carryover_days
FROM emp_vacation_balance_history 
WHERE emp_id = '1061'
  AND update_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
ORDER BY update_date DESC;
```

### Find All Calculation Errors
```sql
SELECT 
  id,
  emp_id,
  update_date,
  old_available_balance,
  new_available_balance,
  calculation_status,
  notes
FROM emp_vacation_balance_history 
WHERE calculation_status IN ('error', 'warning')
  OR new_available_balance < 0
ORDER BY update_date DESC;
```

### Monthly Balance Summary
```sql
SELECT 
  emp_id,
  YEAR(update_date) AS year,
  MONTH(update_date) AS month,
  MIN(update_date) AS first_day,
  MAX(update_date) AS last_day,
  (SELECT new_available_balance FROM emp_vacation_balance_history h2 
   WHERE h2.emp_id = emp_vacation_balance_history.emp_id 
   AND YEAR(h2.update_date) = YEAR(emp_vacation_balance_history.update_date)
   AND MONTH(h2.update_date) = MONTH(emp_vacation_balance_history.update_date)
   ORDER BY h2.update_date ASC LIMIT 1) AS start_balance,
  (SELECT new_available_balance FROM emp_vacation_balance_history h3 
   WHERE h3.emp_id = emp_vacation_balance_history.emp_id 
   AND YEAR(h3.update_date) = YEAR(emp_vacation_balance_history.update_date)
   AND MONTH(h3.update_date) = MONTH(emp_vacation_balance_history.update_date)
   ORDER BY h3.update_date DESC LIMIT 1) AS end_balance
FROM emp_vacation_balance_history
WHERE emp_id = '1061'
GROUP BY emp_id, YEAR(update_date), MONTH(update_date)
ORDER BY year DESC, month DESC;
```

---

## ✅ Verification Checklist

### Daily Verification
- [ ] No negative balances: `SELECT COUNT(*) FROM emp_vacation_balance_history WHERE new_available_balance < 0 AND update_date = CURDATE();`
- [ ] Employee 1061 normal: `SELECT * FROM emp_vacation_balance_history WHERE emp_id = '1061' AND update_date = CURDATE();`
- [ ] All updates successful: `SELECT COUNT(*) FROM emp_vacation_balance_history WHERE update_date = CURDATE() AND calculation_status = 'success';`

### Weekly Review
- [ ] Check for patterns: `SELECT emp_id, COUNT(*) as days_with_changes FROM emp_vacation_balance_history WHERE update_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) AND balance_changed = TRUE GROUP BY emp_id ORDER BY COUNT(*) DESC;`
- [ ] Review emergency vacations: Verify they're not in used_days

### Monthly Review
- [ ] Generate summary report of all balance changes
- [ ] Archive old records if needed: `DELETE FROM emp_vacation_balance_history WHERE update_date < DATE_SUB(CURDATE(), INTERVAL 90 DAY);`

---

## 🔧 Maintenance

### Table Size Check
```sql
SELECT 
  ROUND(((data_length + index_length) / 1024 / 1024), 2) AS size_mb,
  TABLE_ROWS
FROM information_schema.TABLES 
WHERE table_schema = 'almutlak_db' 
  AND table_name = 'emp_vacation_balance_history';
```

### Backup Before Cleanup
```bash
mysqldump -u root -p almutlak_db emp_vacation_balance_history > /backup/history_$(date +%Y%m%d).sql
```

### Keep Only Last 90 Days
```sql
DELETE FROM emp_vacation_balance_history 
WHERE update_date < DATE_SUB(CURDATE(), INTERVAL 90 DAY);
```

---

## 📚 Full Documentation
See: `VACATION_BALANCE_HISTORY_README.md`

## 💾 SQL Files
- Table definition: `sql/add_vacation_balance_history_table.sql`
- Query examples: `sql/vacation_balance_history_guide.sql`

---

**Last Updated:** January 11, 2026
