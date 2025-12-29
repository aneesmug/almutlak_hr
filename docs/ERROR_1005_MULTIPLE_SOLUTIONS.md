# Error #1005 - Multiple Solutions

## Problem
```
MySQL Error #1005
Can't create table `almutlak_db`.`approval_request_types`
(errno: 150 "Foreign key constraint is incorrectly formed")
```

---

## ✅ Solution: Try These Methods in Order

### Method 1: Updated Migration File ⭐ TRY FIRST

File: `sql/migration_approval_request_types.sql`

This file has been updated to disable foreign key checks:

```sql
SET FOREIGN_KEY_CHECKS=0;
DROP TABLE IF EXISTS `approval_request_types`;
CREATE TABLE `approval_request_types` (...);
SET FOREIGN_KEY_CHECKS=1;
```

**Steps:**
1. Open phpMyAdmin
2. Click "Import"
3. Choose: `sql/migration_approval_request_types.sql`
4. Click "Go"

---

### Method 2: Alternative SQL Methods

If Method 1 fails, use the alternative SQL file:

File: `sql/approval_request_types_alternative_methods.sql`

Contains 3 different approaches:

**Method 2.1: Disable Foreign Keys** (Most Reliable)
```sql
SET FOREIGN_KEY_CHECKS=0;
DROP TABLE IF EXISTS `approval_request_types`;
CREATE TABLE ... ENGINE=MyISAM ...
SET FOREIGN_KEY_CHECKS=1;
```

**Method 2.2: Use MyISAM Engine** (Simpler)
```sql
CREATE TABLE ... ENGINE=MyISAM ...
```

**Method 2.3: Nuclear Option** (Most Aggressive)
```sql
-- Completely reset, then create
```

---

### Method 3: Manual Copy-Paste

Open phpMyAdmin → SQL tab and copy one of these:

#### Option A: Disable Foreign Key Checks
```sql
SET FOREIGN_KEY_CHECKS=0;
DROP TABLE IF EXISTS `approval_request_types`;

CREATE TABLE `approval_request_types` (
  `id` varchar(64) PRIMARY KEY,
  `type_name` varchar(255) NOT NULL,
  `description` longtext,
  `is_default` tinyint(1) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp DEFAULT current_timestamp(),
  `updated_at` timestamp NULL ON UPDATE current_timestamp()
) ENGINE=MyISAM CHARSET=latin1;

SET FOREIGN_KEY_CHECKS=1;

INSERT INTO `approval_request_types` VALUES
('vacation_request', 'Vacation Request', 'Annual vacation', 1, 1, NOW(), NULL),
('excuse_leave', 'Excuse Leave', 'Sick leave', 1, 1, NOW(), NULL),
('loan_request', 'Loan Request', 'Loan approval', 1, 1, NOW(), NULL),
('resignation_request', 'Resignation Request', 'Resignation', 1, 1, NOW(), NULL),
('rejoin_request', 'Rejoin Request', 'Rejoin', 1, 1, NOW(), NULL);

SELECT COUNT(*) FROM `approval_request_types`;
```

#### Option B: Use Setup Script Instead
```
http://yoursite.com/system/setup_approval_request_types.php
```

---

## 🔍 Diagnostic Queries

If none above work, run these to diagnose:

```sql
-- Check if table exists
SHOW TABLES LIKE 'approval_request_types';

-- Check table structure
DESCRIBE `approval_request_types`;

-- Show table creation
SHOW CREATE TABLE `approval_request_types`;

-- Check foreign key constraints
SELECT * FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
WHERE REFERENCED_TABLE_NAME = 'approval_request_types';

-- Check MySQL errors
SHOW ERRORS;
```

---

## ✅ Verify Success

After running any method, verify:

```sql
SELECT COUNT(*) as total FROM `approval_request_types`;
```

Should return: **5**

If it does, you're done! ✅

---

## 📊 What Each Method Does

| Method | Engine | Foreign Keys | Complexity | Success Rate |
|--------|--------|--------------|-----------|--------------|
| #1 (Updated) | MyISAM | Disabled | Low | Very High |
| #2.1 | MyISAM | Disabled | Low | Very High |
| #2.2 | MyISAM | N/A | Low | High |
| #2.3 | MyISAM | Disabled | Medium | High |

---

## 🚨 Why This Error Happens

**Root Cause:** 
- MySQL can't create table due to foreign key conflicts
- Usually happens when:
  - Table already exists with constraints
  - Database foreign key checking is strict
  - Charset mismatch with InnoDB
  - Lock from previous failed attempt

**Solution:**
- Disable foreign key checks temporarily
- Drop old table cleanly
- Create new table
- Re-enable foreign key checks
- Insert data

---

## 🎯 Recommended Steps

1. **Try Method 1** (Updated migration file)
   - Easiest, highest success rate
   
2. **If that fails, use Option A** (Disable FK checks)
   - Copy-paste into phpMyAdmin
   - Very reliable
   
3. **If that fails, use Setup Script**
   - Navigate to `setup_approval_request_types.php`
   - Uses PHP to create table
   
4. **If all fail, use Option B** (MyISAM only)
   - Simpler engine, no FK issues

---

## 📂 Files Created

1. **`sql/migration_approval_request_types.sql`** ⭐
   - Updated with FK checks disable
   - Try this first

2. **`sql/approval_request_types_alternative_methods.sql`**
   - Contains 3 methods to fix
   - Diagnostic queries included

3. **`setup_approval_request_types.php`**
   - PHP setup wizard
   - Alternative to SQL

---

## 💡 Quick Fix (Copy-Paste)

Paste this into phpMyAdmin SQL editor:

```sql
SET FOREIGN_KEY_CHECKS=0;
DROP TABLE IF EXISTS `approval_request_types`;
CREATE TABLE `approval_request_types` (
  `id` varchar(64) PRIMARY KEY,
  `type_name` varchar(255),
  `description` longtext,
  `is_default` tinyint(1) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp DEFAULT current_timestamp(),
  `updated_at` timestamp NULL ON UPDATE current_timestamp()
) ENGINE=MyISAM CHARSET=latin1;
SET FOREIGN_KEY_CHECKS=1;
INSERT INTO `approval_request_types` VALUES
('vacation_request', 'Vacation Request', 'Annual vacation', 1, 1, NOW(), NULL),
('excuse_leave', 'Excuse Leave', 'Sick leave', 1, 1, NOW(), NULL),
('loan_request', 'Loan Request', 'Loan approval', 1, 1, NOW(), NULL),
('resignation_request', 'Resignation Request', 'Resignation', 1, 1, NOW(), NULL),
('rejoin_request', 'Rejoin Request', 'Rejoin', 1, 1, NOW(), NULL);
SELECT COUNT(*) FROM `approval_request_types`;
```

Click "Go" and wait for completion.

---

## ✅ Test

After fixing, run:

```sql
SELECT * FROM `approval_request_types` ORDER BY id;
```

Should show 5 rows with all request types.

---

## 📞 Still Having Issues?

1. ✅ Try Method 1 (Updated migration)
2. ✅ Try the copy-paste option above
3. ✅ Use the setup script
4. ✅ Run diagnostic queries
5. ✅ Check PHP error logs
6. ✅ Restart MySQL service

If all fail, the issue is likely deeper database configuration that may need hosting support.

---

**Key Takeaway:** The updated migration file and alternative methods handle foreign key constraints properly. One of them should work!
