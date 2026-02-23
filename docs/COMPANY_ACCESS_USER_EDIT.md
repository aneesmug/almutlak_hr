# Company Access Control - User Edit Implementation

## Overview
Added company-level access control to the user editing interface in `all_users.php`. System admins can now restrict which companies each user can access.

## Files Modified

### 1. [all_users.php](all_users.php) - Line 193
**Change:** Added `data-allowed_companies` attribute to the edit user button
```php
data-allowed_companies="<?= htmlspecialchars($rec['allowed_companies'] ?? '') ?>"
```

### 2. [assets/js/jquery.app.js?t=<?= time() ?>](assets/js/jquery.app.js?t=<?= time() ?>)

#### A. Edit User HTML Form (Line ~6966)
**Added:** Company access control section to the edit user modal
- Full access checkbox (for admins/full access)
- Multi-select dropdown for specific companies
- Helper text and notes

#### B. Load Company Access Function (New)
**Added:** `loadCompanyAccess(userId, userType)` function that:
- Fetches all companies from database via AJAX
- Loads current user's allowed companies
- Sets up checkbox/select interaction
- Shows/hides based on user type

#### C. Toggle Company Access Section (New)
**Added:** `toggleCompanyAccessSection(userType)` function that:
- Hides company access for admins and employees
- Shows for managers, HR, Finance, etc.

#### D. Update User Handler (Line ~2538)
**Modified:** `.updateUserAjax` click handler to call `loadCompanyAccess()`

### 3. [includes/ajaxFile/edit_user.php](includes/ajaxFile/edit_user.php)
**Updated:** Added company access handling:
- Process `full_access` checkbox
- Process `allowed_companies` array
- Convert to JSON format
- Update `allowed_companies` column in database

### 4. [includes/ajaxFile/getCompanyAccess.php](includes/ajaxFile/getCompanyAccess.php) - NEW FILE
**Created:** AJAX endpoint that returns:
- List of all companies in system
- Current user's allowed companies
- Only accessible to system admins

## How It Works

### User Edit Flow:
1. Admin clicks "Edit" on user row
2. Modal opens with edit form
3. `loadCompanyAccess()` is called with user ID
4. AJAX fetches:
   - All companies from database
   - User's current allowed_companies (JSON)
5. Form is populated:
   - If allowed_companies is NULL → "Full Access" checked
   - If allowed_companies has values → specific companies selected
6. Admin can:
   - Check "Full Access" to allow all companies
   - Uncheck and select specific companies
   - Select multiple companies (Ctrl+Click)
7. On save, the selected companies are converted to JSON and saved

### Company Access Rules:
- **System Admins** (administrator, gm): Can access all companies (no restriction)
- **Other Users**: Can see company access section
- **Employees**: Company access section hidden
- **NULL value**: User has full access to all companies
- **JSON Array**: User restricted to specified companies

## UI Features

### Company Access Section:
```
☑ Full Access to All Companies
   (When checked, multi-select is disabled)

[ ] Company 1  [selected]
[ ] Company 2  [selected]
[ ] Company 3
...
```

### Conditional Display:
- Shows for: HR, Finance, Department managers, etc.
- Hidden for: System admins, employees
- Auto-toggles when user type changes

## Security

✅ Only system admins can modify company access
✅ Company IDs are validated (integers)
✅ JSON is properly encoded/decoded
✅ User input sanitized via mysqli_real_escape_string

## Testing Checklist

- [ ] Edit user as system admin
- [ ] Verify company dropdown loads all companies
- [ ] Check "Full Access" and save
- [ ] Uncheck and select specific companies
- [ ] Re-edit user and verify selections saved
- [ ] Verify JSON is stored correctly in database
- [ ] Test with different user types
- [ ] Verify section hides for employees
- [ ] Verify user type changes toggle company section

## Example Database Values

### Full Access (NULL)
```sql
SELECT id, user_type, allowed_companies FROM admin_login 
WHERE allowed_companies IS NULL;
```

### Restricted Access
```sql
SELECT id, user_type, allowed_companies FROM admin_login 
WHERE allowed_companies IS NOT NULL;
-- Result: [1, 2, 5]
```

## Related Files

- [docs/COMPANY_ACCESS_CONTROL.md](docs/COMPANY_ACCESS_CONTROL.md) - Implementation guide
- [sql/add_company_access_control.sql](sql/add_company_access_control.sql) - Database migration
- [includes/session_check.php](includes/session_check.php) - Helper functions
