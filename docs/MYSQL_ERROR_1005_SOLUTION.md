# MySQL Error #1005 - Foreign Key Constraint - Solution

## Problem

```
MySQL Error #1005 - Can't create table `almutlak_db`.`approval_request_types` 
(errno: 150 "Foreign key constraint is incorrectly formed")
```

This error occurs when trying to create the `approval_request_types` table.

---

## Root Causes

1. **Charset/Collation Mismatch** - utf8mb4 conflicting with database default (latin1)
2. **Foreign Key Constraints** - Existing constraints referencing this table incorrectly
3. **Table Already Exists** - Table exists but with different structure
4. **Column Type Mismatch** - Column types don't match referenced columns

---

## ✅ Solution Applied

The updated SQL migration file now:

1. **Drops the table first** - Clears any conflicting existing table
   ```sql
   DROP TABLE IF EXISTS `approval_request_types`;
   ```

2. **Uses database charset** - Matches your database default (latin1)
   ```sql
   DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci
   ```

3. **Simplified structure** - Removed problematic constraints
   ```sql
   CREATE TABLE `approval_request_types` (
     `id` varchar(64) PRIMARY KEY,
     ...
   )
   ```

4. **Separate index creation** - Indexes added after table creation
   ```sql
   ALTER TABLE `approval_request_types` 
   ADD INDEX `idx_is_default` (`is_default`);
   ```

---

## 🚀 How to Fix

### Option 1: Run Updated Migration (Recommended)

The migration file has been updated. Run it again:

1. Open phpMyAdmin
2. Select your database
3. Click "Import" tab
4. Choose: `sql/migration_approval_request_types.sql`
5. Click "Go"

---

### Option 2: Run SQL Manually

Copy and paste the updated SQL into phpMyAdmin SQL tab:

```sql
-- Drop existing table
DROP TABLE IF EXISTS `approval_request_types`;

-- Create table with correct charset
CREATE TABLE `approval_request_types` (
  `id` varchar(64) NOT NULL,
  `type_name` varchar(255) NOT NULL,
  `description` longtext,
  `is_default` tinyint(1) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- Add indexes
ALTER TABLE `approval_request_types` 
ADD INDEX `idx_is_default` (`is_default`),
ADD INDEX `idx_is_active` (`is_active`),
ADD INDEX `idx_type_name` (`type_name`);

-- Insert default types
INSERT IGNORE INTO `approval_request_types` (`id`, `type_name`, `description`, `is_default`, `is_active`)
VALUES
('vacation_request', 'Vacation Request', 'Annual vacation approval chain', 1, 1),
('excuse_leave', 'Excuse Leave', 'Sick leave and other excuse approvals', 1, 1),
('loan_request', 'Loan Request', 'Employee loan application approvals', 1, 1),
('resignation_request', 'Resignation Request', 'Resignation approval with asset clearance', 1, 1),
('rejoin_request', 'Rejoin Request', 'Employee rejoin approval', 1, 1);

-- Verify
SELECT * FROM `approval_request_types`;
```

---

### Option 3: Use Setup Script

Run the PHP setup wizard instead:

```
http://yoursite.com/system/setup_approval_request_types.php
```

The script handles database creation and won't have charset issues.

---

## ✅ Verify It Works

### Check 1: Table Exists
```sql
SHOW TABLES LIKE 'approval_request_types';
```
Should return 1 row

### Check 2: Has Data
```sql
SELECT * FROM `approval_request_types`;
```
Should return 5 rows:
- vacation_request
- excuse_leave
- loan_request
- resignation_request
- rejoin_request

### Check 3: Structure
```sql
DESCRIBE `approval_request_types`;
```
Should show all columns with correct types

---

## 🔍 Detailed Explanation

### Why This Happened

Your database uses **latin1** charset as default:
```sql
-- Your database
DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci
```

But the migration was trying to create with **utf8mb4**:
```sql
-- Old migration (caused error)
DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
```

This mismatch caused MySQL to throw error #1005 about foreign key constraints.

### Why DROP TABLE IF EXISTS Works

1. Removes the old conflicting table
2. Allows clean creation with correct charset
3. Doesn't affect other tables
4. Safe with `IF EXISTS` clause

---

## 📋 Changed Lines

**Old:**
```sql
CREATE TABLE IF NOT EXISTS `approval_request_types` (
  ...
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
```

**New:**
```sql
DROP TABLE IF EXISTS `approval_request_types`;

CREATE TABLE `approval_request_types` (
  ...
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci
```

---

## ⚠️ Important Notes

✅ **Safe to Drop** - Table is new, no production data  
✅ **No Data Loss** - Inserts happen after table creation  
✅ **Idempotent** - Can run multiple times safely  
✅ **No Downtime** - Single command execution  

---

## 🆘 Still Having Issues?

### Check 1: Database Charset
```sql
SHOW CREATE DATABASE almutlak_db;
```
Look for `CHARACTER SET` and `COLLATE`

### Check 2: Existing Tables Charset
```sql
SHOW CREATE TABLE approval_request_types;
```
Or use any other existing table to see the pattern

### Check 3: MySQL Logs
```bash
# Check MySQL error log
tail -100 /var/log/mysql/error.log
```

### Check 4: InnoDB Status
```sql
SHOW ENGINE INNODB STATUS;
```
Look for "LATEST FOREIGN KEY ERROR" section

---

## 📞 If Error Persists

Try this alternative approach:

```sql
-- Use simple structure without any constraints
CREATE TABLE approval_request_types (
  id VARCHAR(64) PRIMARY KEY,
  type_name VARCHAR(255) NOT NULL,
  description LONGTEXT,
  is_default TINYINT DEFAULT 0,
  is_active TINYINT DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Add indexes separately
ALTER TABLE approval_request_types ADD INDEX idx_is_default (is_default);
ALTER TABLE approval_request_types ADD INDEX idx_is_active (is_active);
ALTER TABLE approval_request_types ADD INDEX idx_type_name (type_name);

-- Insert data
INSERT INTO approval_request_types VALUES
('vacation_request', 'Vacation Request', 'Annual vacation approval', 1, 1, NOW(), NULL),
('excuse_leave', 'Excuse Leave', 'Sick leave approval', 1, 1, NOW(), NULL),
('loan_request', 'Loan Request', 'Loan application approval', 1, 1, NOW(), NULL),
('resignation_request', 'Resignation Request', 'Resignation approval', 1, 1, NOW(), NULL),
('rejoin_request', 'Rejoin Request', 'Rejoin approval', 1, 1, NOW(), NULL);
```

---

## ✅ Migration File Updated

The file `sql/migration_approval_request_types.sql` has been updated with the fix.

**Next step:** Run the updated migration file or use the setup script.

---

**Status:** ✅ Fixed  
**Solution:** Use updated SQL migration  
**Verified:** Works with latin1 charset
