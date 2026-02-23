# Create User with Role and Supervisor Assignment - Update Documentation

## Overview
Enhanced the `createUserDeptAjax` function to allow selecting **user role/permission** (admin_login.user_type) and assigning a direct supervisor when creating a new user account.

## What Changed

### 1. Frontend (jquery.app.js?t=<?= time() ?>)

#### Updated `create_user_HTML()` function:
- Changed from employee type (Manager/Assistant) to **admin user roles**
- Added all available user roles:
  - Administrator
  - Department Manager (dept_user)
  - Assistant
  - Normal Employee
  - General Manager
  - HR
  - HR Senior BP
- Added supervisor selection dropdown with Select2 support
- Shows format: "Name (EmpID) - Type - Department"
- Email field is conditional (hidden for 'employee' user type)
- Help text explains the purpose

#### Updated `createUserDeptAjax` click handler:
- Added AJAX call in `didOpen()` to load available supervisors
- Loads all active Managers and Supervisors (excluding the current employee)
- Initializes Select2 for searchable dropdown
- Sends `user_type` (admin role) and `supervisor_id` in the form submission
- Email validation is conditional based on user_type
- Toggles email field visibility based on selected role

### 2. Backend (includes/ajaxFile/ajaxUser.php)

#### Updated `create_user` handler:
- Accepts `user_type` (admin role) parameter
- Accepts optional `supervisor_id` parameter
- Creates user account in `admin_login` table with selected role
- Updates `supervisor_id` in `employees` table if provided
- Success message indicates if supervisor was assigned

#### Added `load_supervisors` handler:
- Returns list of all potential supervisors
- Filters: status=1, emptype IN ('Manager','Supervisor'), not current employee
- Ordered by: Department → Manager first → Name
- Returns: emp_id, name, emptype, dept_name

## Usage

### For HR/Admin Creating New Users:
1. Navigate to employee list or profile
2. Click "Create User" button
3. Fill in fields:
   - **User Role / Permission** (required) - Select from:
     * Administrator - Full system access
     * Department Manager - Department level access
     * Assistant - Limited access
     * Normal Employee - Basic access (no email required)
     * General Manager - Executive access
     * HR - HR department access
     * HR Senior BP - HR senior business partner
   - **Email** (required for all except 'Normal Employee')
   - **Direct Supervisor** (optional)
4. Select supervisor from searchable dropdown
5. Click "Create User"

### User Role Selection:
- **Required field** - Must select a role
- Determines user permissions in admin_login table
- Different from employee type (Manager/Supervisor)
- Email field automatically hides for 'Normal Employee' role

### Supervisor Selection:
- Shows all active Managers and Supervisors
- Displays: Name (ID) - Type - Department
- Searchable dropdown (if Select2 loaded)
- Can be left empty (optional)

## Benefits

1. **Complete User Setup**: Role and supervisor in one step
2. **Proper Permissions**: Uses correct admin_login user_type values
3. **Conditional Validation**: Email only required for certain roles
4. **Immediate Assignment**: Supervisor assigned during user creation
5. **No Manual SQL**: No need to update database separately
6. **Consistent UX**: Same roles as edit user dialog
7. **Leave Approval Ready**: New user immediately routes to correct supervisor

## Technical Details

### Database Operations:
```sql
-- Insert into admin_login (with user role)
INSERT INTO admin_login (emp_id, id_iqama, fullname, user_type, dept, email, created_at)
VALUES (?, ?, ?, ?, ?, ?, ?)

-- Update employees table (supervisor assignment)
UPDATE employees SET supervisor_id = ? WHERE emp_id = ?

-- Load supervisors query
SELECT e.emp_id, e.name, e.emptype, d.name as dept_name 
FROM employees e 
LEFT JOIN department d ON e.dept = d.id 
WHERE e.status = 1 
AND e.emptype IN ('Manager', 'Supervisor') 
AND e.emp_id != ?
ORDER BY d.name ASC, e.emptype = 'Manager' DESC, e.name ASC
```

### Available User Roles (admin_login.user_type):
- `administrator` - System administrator
- `dept_user` - Department manager
- `assistant` - Assistant user
- `employee` - Normal employee (no email required)
- `general_manager` - General manager
- `hr` - HR user
- `hr_senior_bp` - HR Senior Business Partner

### JavaScript Features:
- Dynamic email field visibility based on role
- Conditional email validation
- Select2 integration for searchable dropdowns
- Error handling for AJAX failures
- Graceful degradation if Select2 not loaded
- Translation support with fallbacks

## Prerequisites

**CRITICAL**: The `supervisor_id` column must exist in the `employees` table.

Run this SQL if not already executed:
```sql
-- Add supervisor_id column
ALTER TABLE employees 
ADD COLUMN supervisor_id VARCHAR(255) NULL AFTER emptype;

-- Add index for performance
ALTER TABLE employees 
ADD INDEX idx_supervisor (supervisor_id);
```

## Testing Checklist

- [ ] Create user dialog opens successfully
- [ ] Supervisor dropdown loads with correct data
- [ ] Supervisor dropdown is searchable (if Select2 loaded)
- [ ] Can create user without selecting supervisor
- [ ] Can create user with supervisor selected
- [ ] Supervisor is saved to employees table
- [ ] Success message shows supervisor assignment status
- [ ] Leave application routes to assigned supervisor
- [ ] View employee page shows assigned supervisor

## Files Modified

1. `assets/js/jquery.app.js?t=<?= time() ?>`
   - Line ~5928: `create_user_HTML()` - Added supervisor dropdown
   - Line ~2683: `createUserDeptAjax` - Added supervisor loading and submission

2. `includes/ajaxFile/ajaxUser.php`
   - Line ~56: `create_user` - Added supervisor_id update
   - Line ~85: `load_supervisors` - New handler for supervisor list

## Integration with Leave System

When this employee applies for leave:
1. System checks `employees.supervisor_id`
2. If supervisor assigned → routes to supervisor first
3. If no supervisor → falls back to department manager
4. Second approval always goes to HR Senior BP

## Translation Keys Used

- `direct_supervisor` - Label for supervisor field
- `select_supervisor` - Dropdown placeholder
- `supervisor_help_text` - Field help text

All keys have English fallbacks using `??` operator.

---

**Created**: November 6, 2025
**System**: Al-Mutlak HR Management System
**Related**: SUPERVISOR_ASSIGNMENT_GUIDE.md, SUPERVISOR_UI_CHANGES.md
