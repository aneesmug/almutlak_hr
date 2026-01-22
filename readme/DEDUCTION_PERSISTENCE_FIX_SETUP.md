# Deduction Persistence Fix - Complete Setup Guide

## Issue Summary
Deductions added to payroll were not persisting when the payroll details window was closed and reopened. The deductions would disappear from the display even though they were partially saved to the database.

## Root Causes

### 1. Missing Database Columns
The `payroll_deductions` table was missing critical columns needed to store deduction calculation metadata:
- `calculation_type`: Stores whether deduction is 'fixed', 'hourly_deduction', or 'daily_deduction'
- `hours`: Stores number of hours for hourly deductions
- `days`: Stores number of days for daily deductions

### 2. Incomplete Data Retrieval
The API endpoint `get_payroll_details.php` was not fetching the calculation metadata columns, so deductions appeared empty on reload.

### 3. Incomplete Data Persistence
The INSERT/UPDATE statements were not storing the calculation metadata, causing data loss.

### 4. Broken Deletion Logic
The deduction comparison logic would delete existing deductions when saving new ones (as per previous fix).

## Required Changes

### Step 1: Add Database Columns
Run this SQL migration to add the missing columns to `payroll_deductions`:

```sql
ALTER TABLE `payroll_deductions` ADD COLUMN `calculation_type` VARCHAR(20) DEFAULT 'fixed' AFTER `note`;
ALTER TABLE `payroll_deductions` ADD COLUMN `hours` DECIMAL(5,2) DEFAULT NULL AFTER `calculation_type`;
ALTER TABLE `payroll_deductions` ADD COLUMN `days` DECIMAL(5,2) DEFAULT NULL AFTER `hours`;
```

**File:** `sql/add_deduction_calculation_columns.sql` (already created)

### Step 2: Update Retrieval API
The `get_payroll_details.php` now fetches all required columns:

```php
SELECT id, deduction, note, calculation_type, hours, days FROM payroll_deductions
```

**File:** `includes/api/get_payroll_details.php` (already updated)

### Step 3: Update Persistence Logic
The `update_payroll.php` now stores all metadata:

```php
INSERT INTO payroll_deductions (emp_id, deduction, note, month, status, calculation_type, hours, days) 
VALUES (:emp_id, :deduction_name, :deduction_amount, :month_year, 1, :calc_type, :hours, :days)
```

**File:** `includes/api/update_payroll.php` (already updated)

## Implementation Steps

### 1. Backup Database
```bash
# Using MySQL/MariaDB command line
mysqldump -u root -p almutlak > backup_almutlak_$(date +%Y%m%d_%H%M%S).sql
```

### 2. Apply Migration
Execute the SQL file in your database:

```bash
# Option A: Command line
mysql -u root -p almutlak < sql/add_deduction_calculation_columns.sql

# Option B: Through PHPMyAdmin
# - Go to SQL tab
# - Copy contents of add_deduction_calculation_columns.sql
# - Execute
```

### 3. Verify Changes
Test that the columns were added:

```sql
DESCRIBE payroll_deductions;
```

Should show:
```
+-------------------+---------------------+------+-----+---------+----------------+
| Field             | Type                | Null | Key | Default | Extra          |
+-------------------+---------------------+------+-----+---------+----------------+
| id                | int(11)             | NO   | PRI | NULL    | auto_increment |
| emp_id            | varchar(15)         | NO   | MUL | NULL    |                |
| deduction         | varchar(100)        | NO   |     | NULL    |                |
| note              | varchar(255)        | NO   |     | NULL    |                |
| calculation_type  | varchar(20)         | YES  |     | fixed   |                |
| hours             | decimal(5,2)        | YES  |     | NULL    |                |
| days              | decimal(5,2)        | YES  |     | NULL    |                |
| month             | varchar(50)         | NO   | MUL | NULL    |                |
| status            | int(11)             | NO   |     | 1       |                |
| created_at        | timestamp           | NO   |     | CURRENT |                |
+-------------------+---------------------+------+-----+---------+----------------+
```

## Testing Procedure

1. **Add Multiple Deductions:**
   - Open payroll details for an employee in January 2026
   - Click "Add Deduction" button
   - Add a "Fixed Amount" deduction (e.g., "loan" with 200 SAR)
   - Add a "Deduction by Hour" (e.g., 3 hours)
   - Add a "Deduction by Day" (e.g., 7 days)

2. **Save Changes:**
   - Click "Save Changes" button
   - Verify success message appears

3. **Close and Reopen:**
   - Click "Close" button to close the modal
   - Click on the same employee again to open payroll details
   - **Expected Result:** All three deductions should reappear with their values intact

4. **Verify Calculations:**
   - Check that hourly and daily deductions calculate correctly
   - Check that total deductions reflect all items
   - Check that net salary is correctly calculated

5. **Database Verification:**
   - Query the database to verify data is stored:
   ```sql
   SELECT id, emp_id, deduction, note, calculation_type, hours, days, month 
   FROM payroll_deductions 
   WHERE emp_id = '5073' AND month = '2026-01';
   ```

## Files Modified

1. **`includes/api/update_payroll.php`**
   - Added proper handling of `calculation_type`, `hours`, `days` in INSERT statements
   - Added proper handling of `calculation_type`, `hours`, `days` in UPDATE statements
   - Fixed deletion logic to not delete new deductions (null IDs)

2. **`includes/api/get_payroll_details.php`**
   - Updated SELECT query to fetch `calculation_type`, `hours`, `days` columns

3. **`sql/add_deduction_calculation_columns.sql`** (NEW)
   - Database migration to add missing columns

## Troubleshooting

### Issue: "Unknown column 'calculation_type'" Error
**Solution:** Run the SQL migration file to add the missing columns to the database.

### Issue: Deductions Still Not Showing
**Solution:** 
1. Clear browser cache (Ctrl+F5 or Cmd+Shift+R)
2. Verify database columns were added using `DESCRIBE payroll_deductions;`
3. Check PHP error logs for SQL errors

### Issue: Calculations Are Incorrect
**Solution:**
1. Verify the correct calculation_type is being stored (fixed, hourly_deduction, daily_deduction)
2. Check that hours/days values are being stored correctly
3. Verify the hourly/daily rate calculations in the frontend

## Additional Notes

- All changes are backward compatible - existing GOSI deductions will continue to work
- The system gracefully defaults to 'fixed' type for deductions that don't specify a calculation type
- All INSERT/UPDATE operations are wrapped in transactions for data integrity
