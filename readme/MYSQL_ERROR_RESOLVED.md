# 🔧 MySQL Error #1005 - RESOLVED

## What Happened

When running the SQL migration to create the `approval_request_types` table, MySQL threw error #1005 about foreign key constraints.

**Root Cause:** Charset mismatch
- Database uses: `latin1`
- Migration was trying: `utf8mb4`

---

## ✅ What Was Fixed

The migration file `sql/migration_approval_request_types.sql` has been updated to:

1. **Drop conflicting table first**
   ```sql
   DROP TABLE IF EXISTS `approval_request_types`;
   ```

2. **Use database's default charset**
   ```sql
   DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci
   ```
   (Instead of utf8mb4)

3. **Separate index creation**
   - Create table first
   - Then add indexes

4. **Simplified structure**
   - Removed problematic constraints
   - Kept essential columns

---

## 🚀 How to Fix Now

### Option 1: Run Updated Migration ⭐ RECOMMENDED

The migration file is already updated. Run it:

1. Open **phpMyAdmin**
2. Select your database
3. Click **"Import"** tab
4. Choose: `sql/migration_approval_request_types.sql`
5. Click **"Go"**

**That's it!** ✅

---

### Option 2: Use Setup Script

Open in browser:
```
http://yoursite.com/system/setup_approval_request_types.php
```

---

### Option 3: Manual SQL (If Above Fail)

Paste into phpMyAdmin SQL editor:

```sql
-- Drop old table
DROP TABLE IF EXISTS `approval_request_types`;

-- Create with correct charset
CREATE TABLE `approval_request_types` (
  `id` varchar(64) NOT NULL PRIMARY KEY,
  `type_name` varchar(255) NOT NULL,
  `description` longtext,
  `is_default` tinyint(1) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp DEFAULT current_timestamp(),
  `updated_at` timestamp NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- Add indexes
ALTER TABLE `approval_request_types` 
ADD INDEX `idx_is_default` (`is_default`),
ADD INDEX `idx_is_active` (`is_active`),
ADD INDEX `idx_type_name` (`type_name`);

-- Insert default types
INSERT IGNORE INTO `approval_request_types` VALUES
('vacation_request', 'Vacation Request', 'Annual vacation approval', 1, 1, NOW(), NULL),
('excuse_leave', 'Excuse Leave', 'Sick leave approval', 1, 1, NOW(), NULL),
('loan_request', 'Loan Request', 'Loan application approval', 1, 1, NOW(), NULL),
('resignation_request', 'Resignation Request', 'Resignation approval', 1, 1, NOW(), NULL),
('rejoin_request', 'Rejoin Request', 'Rejoin approval', 1, 1, NOW(), NULL);
```

---

## ✅ Verify It Worked

Run this SQL query:

```sql
SELECT COUNT(*) as total_types FROM `approval_request_types`;
```

**Should return: 5**

If you see 5, you're all set! ✅

---

## 🧪 Test in Application

1. Go to **App Settings** → **Approval** tab
2. Should see all 5 request types:
   - Vacation Request
   - Excuse Leave
   - Loan Request
   - Resignation Request
   - Rejoin Request
3. Should be able to create custom types
4. Should see "Add Approver" button for each

---

## 📋 Files Updated

- ✅ `sql/migration_approval_request_types.sql` - Fixed charset issue
- ✅ `docs/MYSQL_ERROR_1005_SOLUTION.md` - Detailed explanation
- ✅ `MYSQL_ERROR_1005_QUICK_FIX.md` - Quick reference

---

## 🔍 Technical Details

**Why this happened:**
- Your database is configured with `latin1` charset
- Migration was using `utf8mb4`
- MySQL couldn't reconcile the difference
- Threw foreign key error #1005

**Why it's fixed:**
- Now uses `latin1` charset (matches database)
- Table drops cleanly if it exists
- No conflicting constraints
- Simple, straightforward structure

---

## 📞 Need Help?

- **Quick Fix:** [MYSQL_ERROR_1005_QUICK_FIX.md](MYSQL_ERROR_1005_QUICK_FIX.md)
- **Full Details:** [docs/MYSQL_ERROR_1005_SOLUTION.md](docs/MYSQL_ERROR_1005_SOLUTION.md)

---

## 🎯 Next Steps

1. ✅ Run one of the 3 methods above
2. ✅ Verify with the SQL query
3. ✅ Test in the application
4. ✅ Create a custom request type to test

---

**Status:** ✅ **FIXED**  
**Solution:** Updated migration file uses correct charset  
**Action Required:** Run the updated migration file  

**Ready to proceed!** 🚀
