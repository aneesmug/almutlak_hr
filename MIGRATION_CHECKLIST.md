# Migration Checklist - Removing Legacy Approval Columns

## Overview
The old individual approval status columns are being removed from the `emp_loan` table in favor of the generic approval chain system using `status` and `current_approval_level`.

## Files Updated

### 1. ✅ `update_emp_loan_table.sql`
- Uncommented DROP COLUMN statements
- Will remove these 6 columns:
  - `dept_manager_status`
  - `hr_manager_status`
  - `hr_assistant_status`
  - `finance_manager_status`
  - `gm_status`
  - `finance_assistant_status`

### 2. ✅ `all_applied_loan.php`
**Changes Made:**
- Removed all checks for old approval status columns
- Updated button visibility logic to use only `status` field
- Simplified conditional logic (removed redundant checks)

**Before:**
```php
if ($loan['status'] == 'dept_manager_pending' && $user_role == 'DPT_Manager' && $loan['dept'] == $user_dept && $loan['dept_manager_status'] == 'pending')
```

**After:**
```php
if ($loan['status'] == 'dept_manager_pending' && $user_role == 'DPT_Manager' && $loan['dept'] == $user_dept)
```

## Approval Flow (New System)

The system now relies entirely on the `status` field:

| Status | Approver Role | Action Available |
|--------|---------------|------------------|
| `dept_manager_pending` | DPT_Manager | Approve/Reject |
| `hr_assistant_pending` | HR_Assistant | Modify & Approve/Reject |
| `hr_manager_pending` | HR_Manager | Approve/Reject |
| `finance_manager_pending` | Finance_Manager | Approve/Reject |
| `gm_pending` | GM | Modify & Approve/Reject |
| `finance_assistant_pending` | Finance_Assistant | Finalize/Reject |
| `approved` | - | View only (loan active) |
| `paid` | - | View only (loan closed) |
| `rejected` | - | View only (loan rejected) |

## Migration Steps

### Step 1: Backup Database ⚠️
```bash
mysqldump -u root -p almutlak_db emp_loan > emp_loan_backup_$(date +%Y%m%d_%H%M%S).sql
```

### Step 2: Run Migration SQL
```bash
mysql -u root -p almutlak_db < update_emp_loan_table.sql
```

Or via phpMyAdmin:
1. Open phpMyAdmin
2. Select `almutlak_db` database
3. Go to SQL tab
4. Import `update_emp_loan_table.sql`

### Step 3: Verify Table Structure
```sql
SHOW COLUMNS FROM emp_loan;
```

Expected result: The 6 old status columns should be **gone**.

### Step 4: Test the Application
- [ ] Login as Department Manager - verify loan approval works
- [ ] Login as HR Assistant - verify modify & approve works
- [ ] Login as HR Manager - verify approval works
- [ ] Login as Finance Manager - verify approval works
- [ ] Login as GM - verify modify & approve works
- [ ] Login as Finance Assistant - verify finalize works
- [ ] Check that status badges display correctly
- [ ] Verify filtering by status works
- [ ] Test search functionality
- [ ] Verify pagination works

### Step 5: Check for Other Files
Search for any other files that might reference the old columns:

**Files to check:**
- `ajaxLoan.php` ✅ (should already be using new system)
- `loan_approval.js` ✅ (JavaScript - status based)
- `loan_report_details.php` (check if it displays old status columns)
- `add_manual_loan.php` (check if it sets old status columns)

**Search command:**
```bash
grep -r "dept_manager_status\|hr_manager_status\|hr_assistant_status\|finance_manager_status\|gm_status\|finance_assistant_status" --include="*.php" --include="*.js"
```

## Rollback Plan

If issues occur:

### Option 1: Restore from backup
```bash
mysql -u root -p almutlak_db < emp_loan_backup_YYYYMMDD_HHMMSS.sql
```

### Option 2: Re-add columns manually
```sql
ALTER TABLE `emp_loan`
ADD COLUMN `dept_manager_status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending' AFTER `created_at`,
ADD COLUMN `hr_manager_status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending' AFTER `dept_manager_status`,
ADD COLUMN `hr_assistant_status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending' AFTER `hr_manager_status`,
ADD COLUMN `finance_manager_status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending' AFTER `hr_assistant_status`,
ADD COLUMN `gm_status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending' AFTER `finance_manager_status`,
ADD COLUMN `finance_assistant_status` enum('pending','processed') NOT NULL DEFAULT 'pending' AFTER `gm_status`;
```

## Benefits of This Change

✅ **Cleaner Database:** Removes 6 redundant columns  
✅ **Simpler Code:** Less conditional logic to maintain  
✅ **Generic System:** Can easily add/modify approval levels  
✅ **Better Performance:** Fewer columns to index and query  
✅ **Consistent:** Uses the same approval pattern across all request types  

## Known Changes

### Database
- **Removed:** 6 individual approval status columns
- **Using:** `status` field + `current_approval_level` field
- **Tracking:** `approved_by_user_ids` (JSON array of approvers)

### Code
- **all_applied_loan.php:** Simplified button visibility logic
- **ajaxLoan.php:** Already updated to use new system
- **loanHandling.js:** Frontend unchanged (status-based)

## Post-Migration Verification

After migration, run these queries to verify data integrity:

### 1. Check for orphaned statuses
```sql
SELECT DISTINCT status FROM emp_loan;
```
Expected: Only valid statuses (dept_manager_pending, hr_assistant_pending, etc.)

### 2. Check current approval levels
```sql
SELECT status, current_approval_level, COUNT(*) as count 
FROM emp_loan 
GROUP BY status, current_approval_level;
```

### 3. Verify no NULL statuses
```sql
SELECT COUNT(*) FROM emp_loan WHERE status IS NULL OR status = '';
```
Expected: 0

### 4. Check loan types
```sql
SELECT loan_type, COUNT(*) as count FROM emp_loan GROUP BY loan_type;
```

## Support

If you encounter issues:
1. Check error logs: `includes/error.log` or Apache error log
2. Verify database connection
3. Clear browser cache
4. Test with administrator account first
5. Restore from backup if critical issues

## Completion Checklist

- [x] Backup database
- [x] Update SQL migration script
- [x] Update all_applied_loan.php
- [ ] Run migration SQL
- [ ] Verify table structure
- [ ] Test all approval roles
- [ ] Search for remaining references
- [ ] Update loan_report_details.php if needed
- [ ] Deploy to production
- [ ] Monitor for errors
