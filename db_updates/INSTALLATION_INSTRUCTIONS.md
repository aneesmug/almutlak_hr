# Vacation Salary Type Feature - Installation Instructions

## Quick Installation Guide

Follow these steps in order to install the vacation salary type feature:

### Step 1: Backup Your Database
```bash
# Create a backup of your database before making any changes
mysqldump -u your_username -p almutlak_db > backup_$(date +%Y%m%d).sql
```

### Step 2: Run Database Scripts

Execute the following SQL files in your MySQL database in this order:

#### 2.1 Add the vacation_salary_type column
```sql
-- File: db_updates/add_vacation_salary_type_column.sql
SOURCE /path/to/almutlak/system/db_updates/add_vacation_salary_type_column.sql;
```

Or execute directly:
```sql
ALTER TABLE `emp_vacation` 
ADD COLUMN `vacation_salary_type` ENUM('payroll', 'end_of_service') NOT NULL DEFAULT 'payroll' 
COMMENT 'Determines when vacation salary is paid: with payroll or at end of service'
AFTER `remarks`;

ALTER TABLE `emp_vacation` 
ADD INDEX `idx_vacation_salary_type` (`vacation_salary_type`);
```

#### 2.2 Add translation strings
```sql
-- File: db_updates/add_vacation_salary_type_translations.sql
SOURCE /path/to/almutlak/system/db_updates/add_vacation_salary_type_translations.sql;
```

Or execute directly:
```sql
-- English translations
INSERT INTO `translations` (`lang_key`, `lang_code`, `translation`) VALUES
('vacation_salary_payment', 'en', 'Vacation Salary Payment'),
('with_payroll', 'en', 'With Payroll'),
('with_end_of_service', 'en', 'With End of Service'),
('vacation_salary_type_help', 'en', 'Choose when you want to receive your vacation salary: now with payroll or later at end of service.');

-- Arabic translations
INSERT INTO `translations` (`lang_key`, `lang_code`, `translation`) VALUES
('vacation_salary_payment', 'ar', 'دفع راتب الإجازة'),
('with_payroll', 'ar', 'مع الراتب الشهري'),
('with_end_of_service', 'ar', 'مع نهاية الخدمة'),
('vacation_salary_type_help', 'ar', 'اختر متى تريد استلام راتب إجازتك: الآن مع الراتب الشهري أو لاحقًا عند نهاية الخدمة.');
```

### Step 3: Clear Browser Cache

After database updates, clear your browser cache:
- **Chrome/Edge**: Ctrl + Shift + Delete → Clear cached images and files
- **Firefox**: Ctrl + Shift + Delete → Cache
- Or use Incognito/Private mode for testing

### Step 4: Verify Installation

1. **Check Database Column**:
```sql
DESCRIBE emp_vacation;
-- You should see vacation_salary_type column
```

2. **Check Translations**:
```sql
SELECT * FROM translations WHERE lang_key LIKE '%vacation_salary%';
-- Should return 8 rows (4 English + 4 Arabic)
```

3. **Test the Feature**:
   - Navigate to an employee profile
   - Click "Apply Vacation"
   - You should see the new "Vacation Salary Payment" option with two radio buttons
   - Apply a vacation with "With Payroll" selected
   - Check the vacation report - salary should appear normally
   - Apply another vacation with "With End of Service" selected
   - Check the vacation report - salary should show as "Deferred"

### Step 5: Verification Checklist

- [ ] Database column `vacation_salary_type` exists in `emp_vacation` table
- [ ] Index `idx_vacation_salary_type` created successfully
- [ ] 8 translation records added to `translations` table
- [ ] Vacation application form shows new radio buttons
- [ ] "With Payroll" option works correctly (shows salary in report)
- [ ] "With End of Service" option works correctly (shows "Deferred" in report)
- [ ] Existing vacation requests still work (default to 'payroll')
- [ ] GOSI calculation is correct for both options
- [ ] Working days salary calculation is correct for both options

## Troubleshooting

### Issue: Translation keys not working (showing as __(key_name))

**Solution**:
1. Check if translations are loaded:
   - Open browser console (F12)
   - Type: `window.lang`
   - You should see an object with translation keys

2. If empty, check PHP:
```php
// In your page, add temporary debug:
echo '<pre>';
print_r($GLOBALS['translations']);
echo '</pre>';
```

3. Ensure `translation_functions.php` is loaded:
```php
// Should be in includes/init.php or session_check.php
require_once __DIR__ . '/translation_functions.php';
```

4. Reload translations:
```sql
-- Check current language setting
SELECT * FROM settings WHERE setting_name = 'default_language';
```

### Issue: Radio buttons not appearing

**Solution**:
1. Clear browser cache completely
2. Check browser console for JavaScript errors
3. Verify `empVacationHandle.js` is loaded:
   - Open Developer Tools → Network tab
   - Look for `empVacationHandle.js`
   - Status should be 200

### Issue: Database error when applying vacation

**Solution**:
1. Check error logs: `includes/ajaxFile/ajaxVacation.php`
2. Verify column exists:
```sql
SHOW COLUMNS FROM emp_vacation LIKE 'vacation_salary_type';
```

3. Check for typos in column name

### Issue: Vacation report not showing "Deferred" message

**Solution**:
1. Clear PHP opcache if enabled:
```php
opcache_reset();
```

2. Check vacation record in database:
```sql
SELECT vacation_salary_type FROM emp_vacation WHERE id = [vacation_id];
-- Should return 'end_of_service' for deferred vacations
```

## Rollback Instructions

If you need to remove this feature:

```sql
-- Remove the column
ALTER TABLE `emp_vacation` DROP COLUMN `vacation_salary_type`;

-- Remove translations
DELETE FROM `translations` WHERE `lang_key` IN (
    'vacation_salary_payment',
    'with_payroll', 
    'with_end_of_service',
    'vacation_salary_type_help'
);
```

Then restore the previous versions of these files from git:
- `assets/js/empVacationHandle.js`
- `includes/ajaxFile/ajaxVacation.php`
- `vacation_report_details.php`

## Support

For technical support:
- Check the detailed README: `VACATION_SALARY_TYPE_FEATURE_README.md`
- Review error logs in: `error_log` or server logs
- Contact development team

---
**Installation Date**: _____________
**Installed By**: _____________
**Database Backup Location**: _____________
