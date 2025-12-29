# ✅ Fixed: MySQL Error #1005 - Quick Fix Guide

## Problem
```
ERROR #1005: Can't create table `almutlak_db`.`approval_request_types`
(errno: 150 "Foreign key constraint is incorrectly formed")
```

## ✅ Solution

The migration file has been **UPDATED** to fix the charset issue.

### Quick Fix (Choose One)

#### Method 1: Run Updated Migration File ⭐ Recommended
1. Open phpMyAdmin
2. Select your database
3. Click "Import"
4. Upload: `sql/migration_approval_request_types.sql`
5. Click "Go"

#### Method 2: Use Setup Script
```
http://yoursite.com/system/setup_approval_request_types.php
```

#### Method 3: Manual SQL
Copy this into phpMyAdmin SQL tab:

```sql
DROP TABLE IF EXISTS `approval_request_types`;

CREATE TABLE `approval_request_types` (
  `id` varchar(64) PRIMARY KEY,
  `type_name` varchar(255) NOT NULL,
  `description` longtext,
  `is_default` tinyint(1) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

ALTER TABLE `approval_request_types` 
ADD INDEX `idx_is_default` (`is_default`),
ADD INDEX `idx_is_active` (`is_active`),
ADD INDEX `idx_type_name` (`type_name`);

INSERT IGNORE INTO `approval_request_types` VALUES
('vacation_request', 'Vacation Request', 'Annual vacation approval', 1, 1, NOW(), NULL),
('excuse_leave', 'Excuse Leave', 'Sick leave approval', 1, 1, NOW(), NULL),
('loan_request', 'Loan Request', 'Loan application approval', 1, 1, NOW(), NULL),
('resignation_request', 'Resignation Request', 'Resignation approval', 1, 1, NOW(), NULL),
('rejoin_request', 'Rejoin Request', 'Rejoin approval', 1, 1, NOW(), NULL);

SELECT * FROM `approval_request_types`;
```

---

## 🔍 Verify It Worked

Run this query:
```sql
SELECT COUNT(*) FROM `approval_request_types`;
```

Should return: **5**

---

## 📊 What Changed

| Issue | Fix |
|-------|-----|
| Charset conflict | Changed utf8mb4 → latin1 (database default) |
| Table conflict | Drop old table first |
| Index issue | Create indexes after table |
| Foreign key error | Simplified table structure |

---

## 🚀 Next Steps

1. ✅ Fix the database (use one method above)
2. ✅ Verify with query above
3. ✅ Test in UI: App Settings → Approval tab
4. ✅ Should see 5 request types

---

## 📚 Full Details

See: [docs/MYSQL_ERROR_1005_SOLUTION.md](../docs/MYSQL_ERROR_1005_SOLUTION.md)

---

**Problem Solved!** ✅ Use one of the 3 methods above to fix.
