# Approval Request Types - Database Implementation

## Overview

All approval request types (default and custom) are now fetched from the database instead of being hardcoded. This provides:

✅ **Dynamic Management** - Add/manage request types without code changes
✅ **Persistence** - All types stored in database
✅ **Scalability** - Unlimited custom request types
✅ **Flexibility** - Can activate/deactivate types
✅ **Audit Trail** - Track when types were created/modified

---

## Database Table: `approval_request_types`

### Structure

```sql
CREATE TABLE `approval_request_types` (
  `id` varchar(64) PRIMARY KEY,           -- Unique request type ID (e.g., 'vacation_request')
  `type_name` varchar(255) NOT NULL,      -- Display name (e.g., 'Vacation Request')
  `description` text,                     -- Description of the request type
  `is_default` tinyint(1) DEFAULT 0,      -- 1 = default (cannot delete), 0 = custom
  `is_active` tinyint(1) DEFAULT 1,       -- 1 = active, 0 = inactive
  `created_at` timestamp,                 -- When type was created
  `updated_at` timestamp                  -- When type was last updated
)
```

### Indexes

- `PRIMARY KEY (id)`
- `KEY (is_default)` - For filtering default types
- `KEY (is_active)` - For filtering active types
- `KEY (type_name)` - For faster lookups

---

## Setup Instructions

### Option 1: Run Setup Script (Recommended)

1. Open your browser and navigate to:
   ```
   http://yoursite.com/system/setup_approval_request_types.php
   ```

2. The script will:
   - Create the `approval_request_types` table if it doesn't exist
   - Insert default request types
   - Show a summary of what was done
   - Display all request types in database

3. After completion, **delete** the setup script file for security

### Option 2: Run SQL Migration

1. Open phpMyAdmin or your MySQL client
2. Select your database
3. Import the SQL file:
   ```
   sql/migration_approval_request_types.sql
   ```

### Option 3: Manual Database Execution

Run this SQL directly:

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
  KEY `idx_is_active` (`is_active`),
  KEY `idx_type_name` (`type_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO `approval_request_types` VALUES
('vacation_request', 'Vacation Request', 'Annual vacation and fly vacation approval chain', 1, 1, NOW(), NULL),
('excuse_leave', 'Excuse Leave', 'Sick leave, exam leave, and other excuse types', 1, 1, NOW(), NULL),
('loan_request', 'Loan Request', 'Employee loan application approval chain', 1, 1, NOW(), NULL),
('resignation_request', 'Resignation Request', 'Employee resignation approval chain', 1, 1, NOW(), NULL),
('rejoin_request', 'Rejoin Request', 'Employee rejoin after resignation approval chain', 1, 1, NOW(), NULL);
```

---

## Default Request Types

| ID | Name | Description | Default | Active |
|----|----|-----------|---------|--------|
| `vacation_request` | Vacation Request | Annual and fly vacations | ✅ Yes | ✅ Yes |
| `excuse_leave` | Excuse Leave | Sick, exam, hajj, maternity, marriage, death, business trip | ✅ Yes | ✅ Yes |
| `loan_request` | Loan Request | Employee loans (regular, emergency, housing, advance) | ✅ Yes | ✅ Yes |
| `resignation_request` | Resignation Request | Employee resignation with asset clearance | ✅ Yes | ✅ Yes |
| `rejoin_request` | Rejoin Request | Re-joining after resignation | ✅ Yes | ✅ Yes |

---

## How It Works

### 1. Getting All Request Types

**Frontend** → Clicks "Add New Request Type" button  
**JavaScript** → Calls `getAllRequestTypes()` via AJAX  
**Backend** → Queries `approval_request_types` table  
**Response** → Returns all active types as JSON  
**UI** → Displays types as cards

```php
// In approval_chain_handler.php
function getAllRequestTypes($conDB) {
    // Ensure defaults exist
    ensureDefaultRequestTypes($conDB);
    
    // Fetch all from database
    $query = mysqli_query($conDB, 
        "SELECT id, type_name, description FROM approval_request_types ORDER BY id");
    
    // Return as JSON
}
```

### 2. Creating a New Request Type

**Frontend** → User fills form and clicks "Create"  
**Backend** → Validates input format  
**Database** → Inserts into `approval_request_types` table  
**Config** → Inserts empty approval chain into `app_settings` table  
**Response** → Success message  
**UI** → New type appears immediately

```php
// In approval_chain_handler.php
function createNewRequestType($conDB) {
    // 1. Validate ID format (lowercase, underscores)
    // 2. Check if ID already exists
    // 3. Insert into approval_request_types
    // 4. Create approval chain setting in app_settings
    // 5. Return success
}
```

### 3. Automatic Defaults

On first load, the system automatically:

```php
function ensureDefaultRequestTypes($conDB) {
    // Check each default type
    foreach ($defaultTypes as $type) {
        // If not in database, insert it
        if (!exists($type['id'])) {
            insert($type);
        }
    }
}
```

---

## Adding Custom Request Types

### Via Frontend (Easy)

1. Go to **App Settings** → **Approval** tab
2. Click **"Add New Request Type"** button
3. Fill form:
   - ID: `training_request`
   - Name: `Training Request`
   - Description: `Employee training program approvals`
4. Click **"Create"**
5. Done! Type appears with its own approval chain

### Via Database (SQL)

```sql
INSERT INTO approval_request_types 
  (id, type_name, description, is_default, is_active, created_at) 
VALUES 
  ('training_request', 'Training Request', 'Employee training approvals', 0, 1, NOW());
```

---

## Querying Request Types

### Get All Active Types

```sql
SELECT * FROM approval_request_types 
WHERE is_active = 1 
ORDER BY id;
```

### Get Only Default Types

```sql
SELECT * FROM approval_request_types 
WHERE is_default = 1 AND is_active = 1;
```

### Get Only Custom Types

```sql
SELECT * FROM approval_request_types 
WHERE is_default = 0 AND is_active = 1;
```

### Check if Type Exists

```sql
SELECT id FROM approval_request_types 
WHERE id = 'travel_request' AND is_active = 1;
```

---

## Integration with Application Code

### Get Request Type for Approval

```php
<?php
require_once 'includes/db.php';

// Get request type details
$typeId = 'loan_request';
$typeQuery = mysqli_query($conDB, 
    "SELECT id, type_name, description FROM approval_request_types 
     WHERE id = '{$typeId}' AND is_active = 1 LIMIT 1");

if ($typeQuery && mysqli_num_rows($typeQuery) > 0) {
    $requestType = mysqli_fetch_assoc($typeQuery);
    
    // Use for approval workflow
    echo "Processing: " . $requestType['type_name'];
}
?>
```

### Get Approval Chain for Type

```php
<?php
// Get approval chain config for this request type
$settingName = "approval_chain_{$typeId}";
$settingQuery = mysqli_query($conDB, 
    "SELECT setting_value FROM app_settings 
     WHERE setting_name = '{$settingName}' 
     AND setting_group = 'approval'");

if ($settingQuery && mysqli_num_rows($settingQuery) > 0) {
    $setting = mysqli_fetch_assoc($settingQuery);
    $approvers = json_decode($setting['setting_value'], true);
    
    // Use approvers in workflow
}
?>
```

---

## Files Modified/Created

### Modified Files

1. **[includes/approval_chain_handler.php](../includes/approval_chain_handler.php)**
   - `getAllRequestTypes()` - Now fetches from database
   - `ensureDefaultRequestTypes()` - NEW: Ensures defaults exist
   - `createNewRequestType()` - Now inserts into database table

### New Files

1. **[setup_approval_request_types.php](../setup_approval_request_types.php)**
   - Setup wizard to create table and seed defaults
   - User-friendly interface with progress summary
   - Security note: Delete after running

2. **[sql/migration_approval_request_types.sql](../sql/migration_approval_request_types.sql)**
   - SQL migration file for manual setup
   - Can be run via phpMyAdmin

3. **[docs/APPROVAL_REQUEST_TYPES_DATABASE.md](APPROVAL_REQUEST_TYPES_DATABASE.md)**
   - This documentation file

---

## Important Notes

### Default Types Cannot Be Deleted

The 5 default types (`vacation_request`, `excuse_leave`, `loan_request`, `resignation_request`, `rejoin_request`) are marked with `is_default = 1` and should NOT be deleted from the database.

If accidentally deleted, run the setup script again to restore them.

### Custom Types Can Be Managed

Custom types (created via the UI) have `is_default = 0` and can be:
- Modified (name, description)
- Deactivated (set `is_active = 0` to hide without deleting)
- Deleted (when no longer needed)

### Always Use `is_active` Flag

Instead of deleting request types, set `is_active = 0` to:
- Keep historical data intact
- Allow auditing/tracking
- Easily re-enable later

```sql
-- Deactivate a type instead of deleting
UPDATE approval_request_types 
SET is_active = 0 
WHERE id = 'old_request_type';
```

### Consistency

Always ensure:
- ✅ `approval_request_types` table has the type
- ✅ `app_settings` has the approval chain configuration
- ✅ Both use the same type ID

---

## Troubleshooting

### "Table doesn't exist" Error

**Solution:** Run the setup script:
```
http://yoursite.com/system/setup_approval_request_types.php
```

### Request Types Not Loading

**Check:**
1. Table `approval_request_types` exists
2. Table has data (run SELECT query)
3. Database connection is working
4. `is_active = 1` for the types

### New Type Not Appearing

**Check:**
1. Type was inserted into `approval_request_types`
2. No database errors in the response
3. Refresh the page
4. Check browser console for errors

### Custom Type Missing After Server Restart

**Likely Cause:** Not properly saved to database

**Solution:** 
1. Check if type exists in `approval_request_types` table
2. Create it again if missing
3. Verify both `approval_request_types` AND `app_settings` tables have entries

---

## Verification Query

Run this to verify everything is set up correctly:

```sql
-- Check table exists
SHOW TABLES LIKE 'approval_request_types';

-- Check default types
SELECT * FROM approval_request_types 
WHERE is_default = 1;

-- Check all active types
SELECT id, type_name, is_active FROM approval_request_types 
WHERE is_active = 1 
ORDER BY id;

-- Check approval chain settings exist
SELECT setting_name FROM app_settings 
WHERE setting_name LIKE 'approval_chain_%' 
AND setting_group = 'approval';
```

---

## Future Enhancements

Possible future improvements:
- [ ] Edit request type name/description via UI
- [ ] Deactivate (soft delete) types via UI
- [ ] View request type usage statistics
- [ ] Export/import request type configurations
- [ ] Categorize request types by department
- [ ] Set approval chain templates for each type

---

## Support

If you encounter issues:

1. Check the troubleshooting section above
2. Run the verification query
3. Check `approval_request_types` table structure
4. Review browser console for JavaScript errors
5. Check PHP error logs for backend issues

---

**Version:** 1.0  
**Last Updated:** December 22, 2025  
**Status:** Production Ready
