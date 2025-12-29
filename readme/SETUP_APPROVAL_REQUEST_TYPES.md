# Quick Setup Guide - Approval Request Types from Database

## 🚀 One-Click Setup

### Run Setup Script

1. Open your browser and go to:
   ```
   http://yoursite.com/system/setup_approval_request_types.php
   ```

2. The script will:
   - ✅ Create `approval_request_types` table
   - ✅ Insert 5 default request types
   - ✅ Show a summary
   - ✅ Display verification results

3. **After completion, delete the setup file** from your server:
   ```
   Delete: system/setup_approval_request_types.php
   ```

That's it! 🎉

---

## ✅ Verify It Works

### Check Table in Database

```sql
SELECT * FROM approval_request_types;
```

Should show 5 rows:
- vacation_request
- excuse_leave
- loan_request
- resignation_request
- rejoin_request

### Test in UI

1. Go to **App Settings** → **Approval** tab
2. Should see all 5 default request types
3. Click **"Add New Request Type"**
4. Create a test type (e.g., `test_travel`)
5. Should immediately appear in the list

---

## 📊 What Changed

| Before | After |
|--------|-------|
| Hardcoded types in PHP | All types in database |
| Only 5 types possible | Unlimited custom types |
| Requires code change to add type | Add types via UI |
| No persistence tracking | Created/updated timestamps |

---

## 🔧 Database Table Structure

```
approval_request_types
├── id (varchar 64) - Primary Key
├── type_name (varchar 255) - Display name
├── description (text) - What it's for
├── is_default (tinyint) - 1=default, 0=custom
├── is_active (tinyint) - 1=active, 0=inactive
├── created_at (timestamp) - When created
└── updated_at (timestamp) - Last updated
```

---

## 💡 Key Features

✅ **Dynamic** - Add/manage types without code  
✅ **Persistent** - Stored in database  
✅ **Secure** - Setup script deletes after use  
✅ **Scalable** - Unlimited custom types  
✅ **Trackable** - Creation/modification timestamps  
✅ **Flexible** - Can activate/deactivate types  

---

## 📝 Files Created

1. **setup_approval_request_types.php** - Interactive setup wizard
2. **sql/migration_approval_request_types.sql** - SQL migration file
3. **docs/APPROVAL_REQUEST_TYPES_DATABASE.md** - Full documentation

---

## 🛠️ Alternative Setup Methods

### Method 2: Run SQL Migration File

1. Open phpMyAdmin
2. Select your database
3. Click "Import" tab
4. Upload: `sql/migration_approval_request_types.sql`
5. Click "Go"

### Method 3: Manual SQL

Copy and paste into phpMyAdmin SQL tab:

```sql
CREATE TABLE IF NOT EXISTS `approval_request_types` (
  `id` varchar(64) NOT NULL,
  `type_name` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `is_default` tinyint(1) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_is_default` (`is_default`),
  KEY `idx_is_active` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO `approval_request_types` VALUES
('vacation_request', 'Vacation Request', 'Annual vacation approval', 1, 1, NOW(), NULL),
('excuse_leave', 'Excuse Leave', 'Sick/exam leave approvals', 1, 1, NOW(), NULL),
('loan_request', 'Loan Request', 'Employee loan approvals', 1, 1, NOW(), NULL),
('resignation_request', 'Resignation Request', 'Resignation approvals', 1, 1, NOW(), NULL),
('rejoin_request', 'Rejoin Request', 'Rejoin after resignation', 1, 1, NOW(), NULL);
```

---

## ⚠️ Important Notes

- ✅ **Default types** (is_default=1) should NOT be deleted
- ✅ **Custom types** can be deleted or deactivated
- ✅ **Deactivate instead of delete** to keep audit trail
- ✅ **Delete setup script** after running for security

---

## 🆘 Troubleshooting

| Problem | Solution |
|---------|----------|
| Table doesn't exist | Run setup script |
| Types not loading | Check table exists: `SELECT * FROM approval_request_types;` |
| Setup script error | Try Method 2 or 3 above |
| Can't add custom types | Verify database permissions |

---

## 📚 Full Documentation

See [docs/APPROVAL_REQUEST_TYPES_DATABASE.md](docs/APPROVAL_REQUEST_TYPES_DATABASE.md) for:
- Detailed setup instructions
- Database schema details
- Integration examples
- SQL queries
- Troubleshooting guide

---

**Status:** ✅ Ready to Deploy  
**Setup Time:** < 1 minute  
**Difficulty:** ⭐ Very Easy
