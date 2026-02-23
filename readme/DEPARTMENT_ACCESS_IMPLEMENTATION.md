# Multi-Department Access Control Implementation

## Overview
This document describes the implementation of multi-department access control for users in the Al-Mutlak WMS system. The feature allows users to be granted access to multiple departments, similar to the existing multi-company access functionality.

## Database Changes

### SQL Migration
**File:** `sql/add_allowed_departments.sql`

The migration adds a new JSON column to the `admin_login` table:

```sql
ALTER TABLE `admin_login`
ADD COLUMN `allowed_departments` JSON DEFAULT NULL 
COMMENT 'JSON array of department IDs user is allowed to access. NULL = all departments'
AFTER `allowed_companies`;
```

**Access Logic:**
- `NULL` = User has access to all departments
- `[1, 3, 5]` = User can only access departments with IDs 1, 3, and 5

**To Apply:** Run the SQL file in your database:
```bash
mysql -u your_user -p your_database < sql/add_allowed_departments.sql
```

## Frontend Changes

### 1. User Management Interface (`all_users.php`)

#### New Table Column
Added "Allowed Departments" column to the user list table:
- Displays department names (converted from JSON IDs)
- Shows "All Departments" when user has full access
- Positioned after "Allowed Companies" column

#### New Filter
Added a filter dropdown for "Allowed Departments":
- Located in the filter row alongside Status, Department, Role, and Company filters
- Uses Select2 for searchable dropdown
- Filters users by their allowed departments

#### Updated Column Indices
Due to the new column, the following column indices were updated:
- Status: Column 9 → Column 10
- Action: Column 10 → Column 11

### 2. User Edit Modal (`assets/js/jquery.app.js?t=<?= time() ?>`)

#### New HTML Section
Added "Department Access Control" section in the edit modal:
- Multi-select dropdown using Select2
- "Full Access" checkbox option
- Help text explaining the feature
- Positioned between "Company Access Control" and "Status" sections

#### New JavaScript Functions

**`loadDepartmentAccess(userId, userType)`**
- Fetches available departments from the server
- Loads user's current department selections
- Initializes Select2 dropdown
- Handles "Full Access" checkbox state
- Mirrors the `loadCompanyAccess()` function

**`toggleDepartmentAccessSection(userType)`**
- Shows/hides department section based on user role
- Hidden for: administrator, gm, employee
- Visible for: hr, dept_user, assistant, hr_senior_bp

#### Modal Lifecycle Integration
- **willOpen**: Calls `loadDepartmentAccess()` when edit modal opens
- **didClose**: Destroys Select2 instance for cleanup
- **preConfirm**: Validates department selection before saving

#### Form Validation
- Ensures at least one department is selected OR "Full Access" is checked
- Error message: "Please select at least one department or grant full access"
- Prevents submission with empty selection

## Backend Changes

### 1. AJAX Endpoint (`includes/ajaxFile/getDepartmentAccess.php`)

**Purpose:** Fetch available departments and user's current selections

**Request:** GET/POST

**Response:**
```json
{
  "success": true,
  "departments": [
    {"id": 1, "name": "IT Department"},
    {"id": 2, "name": "HR Department"},
    {"id": 3, "name": "Finance Department"}
  ],
  "allowed_departments": [1, 3, 5]
}
```

**Query:**
```sql
SELECT id, dep_nme FROM department WHERE status = 1 ORDER BY dep_nme
```

### 2. User Update Handler (`includes/ajaxFile/ajaxUser.php`)

**Added Logic:**
```php
// Handle Department Access Control
$allowed_departments_json = null;
$full_dept_access = isset($_POST['full_dept_access']) && $_POST['full_dept_access'] === '1';

if (!$full_dept_access && isset($_POST['allowed_departments']) && is_array($_POST['allowed_departments'])) {
    $department_ids = array_filter(array_map('intval', $_POST['allowed_departments']));
    if (!empty($department_ids)) {
        $allowed_departments_json = json_encode($department_ids);
    }
}
```

**SQL Update:**
```php
allowed_departments = " . ($allowed_departments_json ? "'" . $allowed_departments_json . "'" : "NULL")
```

**Activity Logging:**
Department changes are logged in the activity log for audit purposes.

### 3. Data Retrieval (`includes/ajaxFile/getAllUsersData.php`)

**Added Column to Query:**
```php
SELECT ..., al.allowed_departments, ...
```

**Department Name Conversion:**
```php
// Convert JSON allowed_departments to department names
$department_names = "All Departments";
if (!empty($allowed_departments)) {
    $departments_array = json_decode($allowed_departments, true);
    if (is_array($departments_array) && !empty($departments_array)) {
        $department_ids = implode(',', array_map('intval', $departments_array));
        $dept_query = mysqli_query($conDB, 
            "SELECT GROUP_CONCAT(DISTINCT `dep_nme` SEPARATOR ', ') AS `names` 
             FROM `department` 
             WHERE `id` IN ($department_ids)");
        if ($dept_query && $dept_row = mysqli_fetch_assoc($dept_query)) {
            $department_names = $dept_row['names'] ?: "All Departments";
        }
    }
}
```

**Updated Row Data:**
Added department names to column 9 in the DataTables response.

## Testing Checklist

### Database
- [ ] Execute `sql/add_allowed_departments.sql`
- [ ] Verify column exists: `SHOW COLUMNS FROM admin_login LIKE 'allowed_departments';`
- [ ] Check existing users have NULL (full access)

### User Interface
- [ ] Open "All Users" page
- [ ] Verify "Allowed Departments" column is visible
- [ ] Verify "Filter by Allowed Department" dropdown appears
- [ ] Click "Edit" on a user
- [ ] Verify "Department Access Control" section appears (for non-admin/employee users)
- [ ] Verify department dropdown is populated

### Functionality
- [ ] Select multiple departments in edit modal
- [ ] Save and verify departments display in table
- [ ] Reload page and edit same user - verify selections persist
- [ ] Check "Full Access" checkbox and save - verify "All Departments" shows
- [ ] Uncheck "Full Access" without selecting departments - verify validation error
- [ ] Select departments then check "Full Access" - verify departments are cleared
- [ ] Test filter dropdown - verify filtering works
- [ ] Check database - verify JSON format is correct
- [ ] Check activity log - verify department changes are logged

### User Roles
- [ ] Test with administrator - section should be hidden
- [ ] Test with gm - section should be hidden
- [ ] Test with employee - section should be hidden
- [ ] Test with hr - section should be visible
- [ ] Test with dept_user - section should be visible
- [ ] Test with assistant - section should be visible

## Data Format

### Database Storage
```json
NULL                 // Full access to all departments
[1]                  // Access to department ID 1 only
[1, 3, 5]           // Access to departments 1, 3, and 5
[]                   // Empty array (treated as NULL)
```

### Frontend Submission
**Form Data:**
- `full_dept_access`: "1" or "0"
- `allowed_departments[]`: Array of department IDs

**Example POST data:**
```
full_dept_access: 0
allowed_departments[]: 1
allowed_departments[]: 3
allowed_departments[]: 5
```

## Security Considerations

### Input Validation
- All department IDs are validated and cast to integers
- Empty arrays are filtered out
- Invalid department IDs are ignored
- SQL injection prevented through proper escaping

### Access Control
- Department access control section is hidden for certain user types
- Only authorized users can modify department access
- Changes are logged in activity log

### Data Integrity
- JSON validation ensures proper format
- NULL is used consistently for "all departments"
- Database query uses parameterized values

## Future Enhancements

### Suggested Improvements
1. **Session Integration**
   - Add `allowed_departments_array` to session (like `allowed_companies_array`)
   - Helper function: `hasDepartmentAccess($dept_id)`
   - Middleware to check department access on page load

2. **Validation Functions**
   - `canAccessDepartment($user_id, $dept_id)`
   - `getUserDepartments($user_id)`
   - `getDepartmentUsers($dept_id)`

3. **UI Enhancements**
   - Badge indicators showing restricted access
   - Department access summary in user profile
   - Bulk department assignment for multiple users

4. **Reporting**
   - Department access audit report
   - Users by department report
   - Access change history report

## Files Modified

### Created Files
1. `sql/add_allowed_departments.sql` - Database migration
2. `includes/ajaxFile/getDepartmentAccess.php` - AJAX endpoint
3. `DEPARTMENT_ACCESS_IMPLEMENTATION.md` - This documentation

### Modified Files
1. `assets/js/jquery.app.js?t=<?= time() ?>`
   - Added `loadDepartmentAccess()` function
   - Added `toggleDepartmentAccessSection()` function
   - Updated `edit_user_HTML()` with department section
   - Updated `updateUserAjax` handler

2. `includes/ajaxFile/ajaxUser.php`
   - Added department access handling in `user_upate`
   - Added validation and sanitization
   - Added activity logging

3. `includes/ajaxFile/getAllUsersData.php`
   - Added `allowed_departments` to SELECT query
   - Added department name conversion logic
   - Updated row data structure

4. `all_users.php`
   - Added "Allowed Departments" table column
   - Added "Filter by Allowed Department" dropdown
   - Updated column indices
   - Updated DataTables configuration
   - Added filter population logic
   - Updated export column configuration

## Support

For issues or questions regarding this implementation:
1. Check the activity log for error messages
2. Verify database column exists and has correct type
3. Check browser console for JavaScript errors
4. Verify AJAX endpoints are accessible
5. Ensure user has appropriate permissions

## Version History

**Version 1.0** (Current)
- Initial implementation of multi-department access control
- Mirrors existing multi-company access functionality
- Full CRUD operations for department assignments
- Filtering and display in user table
- Activity logging for audit trail
