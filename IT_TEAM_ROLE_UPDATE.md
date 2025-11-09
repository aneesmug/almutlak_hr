# IT Team Role System Update

## Summary
Updated the role system to support IT team members with department-based roles instead of the old `IT_Assistant` role.

## Changes Made

### 1. **role_check.php (v016)**
- Added legacy mapping: `'it' => 'IT_Team_Manager'`
- Users with `user_type = 'it'` will be assigned `IT_Team_Manager` role
- Users with `user_type = 'dept_user'` and `dept = 6` (IT Department) will get:
  - `IT_Team_Manager` (if `emp_type = 'Manager'`)
  - `IT_Team` (if `emp_type = 'Supporter'` or not Manager)

### 2. **main_menu.php (v026)**
- Replaced all `IT_Assistant` references with `IT_Team` and `IT_Team_Manager`
- Updated page access permissions:
  - `dashboard.php`: Added IT_Team, IT_Team_Manager
  - `reg_employee.php`: Added IT_Team, IT_Team_Manager
  - `all_applied_vac.php`: Added IT_Team, IT_Team_Manager
  - `all_requests.php`: Added IT_Team, IT_Team_Manager
  - `manual_vacation.php`: Added IT_Team, IT_Team_Manager
- Updated menu visibility arrays to include IT_Team roles

### 3. **dashboard.php**
- Fixed redirect loop issue for IT_Team users
- Updated employee redirect to check `$user_role == 'Employee'` instead of `$_SESSION['user_type']`
- Updated dashboard permission checks to include IT team members

### 4. **login.php (v016)**
- Updated to use simple check: only `user_type = 'employee'` requires password
- All other user types (including 'it') get OTP authentication

## Database Setup

### For Existing IT Users:
```sql
-- Option 1: Use legacy 'it' user_type (will be mapped to IT_Team_Manager)
UPDATE admin_login 
SET user_type = 'it', dept = 6 
WHERE emp_id = 'YOUR_IT_USER_EMP_ID';

-- Option 2: Use new department-based system
-- For IT Team Manager:
UPDATE admin_login 
SET user_type = 'dept_user', dept = 6, emp_type = 'Manager' 
WHERE emp_id = 'YOUR_IT_MANAGER_EMP_ID';

-- For IT Team Member:
UPDATE admin_login 
SET user_type = 'dept_user', dept = 6, emp_type = 'Supporter' 
WHERE emp_id = 'YOUR_IT_TEAM_MEMBER_EMP_ID';
```

## Role Assignment Logic

```
Priority Order:
1. user_type = 'it' → IT_Team_Manager (legacy mapping)
2. user_type = 'dept_user' + dept = 6 + emp_type = 'Manager' → IT_Team_Manager
3. user_type = 'dept_user' + dept = 6 → IT_Team
4. Default fallback → Employee
```

## Access Permissions

### IT_Team_Manager has access to:
- Dashboard
- All Employees List
- Applied Vacations
- Smart Requests
- Manual Vacation Import

### IT_Team has access to:
- Dashboard
- All Employees List
- Applied Vacations
- Smart Requests
- Manual Vacation Import

## Testing Checklist

- [ ] IT user can login with OTP (not password)
- [ ] IT user can access dashboard without redirect loop
- [ ] IT user can see "All Employees" page
- [ ] IT user can see "Applied Vacations" page
- [ ] IT user can see "Smart Requests" page
- [ ] IT user does NOT see HR-specific pages (Add New Employee, Payroll, etc.)
- [ ] IT user does NOT see Finance-specific pages (Vouchers, etc.)

## Notes

- Department 6 = IT Department
- The `user_type = 'it'` is legacy and maintained for backward compatibility
- Recommended: Migrate to `dept_user` with appropriate department for future users
- All IT team members should have `dept = 6` in their `admin_login` record

## Files Modified

1. `includes/role_check.php` - Added 'it' mapping and department logic
2. `includes/main_menu.php` - Updated all permission arrays
3. `dashboard.php` - Fixed redirect and permission checks
4. `login.php` - Updated authentication logic
5. `alter_admin_login_user_type_enum.sql` - ENUM already includes 'it'
