# Dynamic Role Management System - Quick Guide

**Last Updated:** November 5, 2025  
**Version:** 3.0 - Database-Driven Role System

---

## 🎯 Overview

The Al-Mutlak HR system now uses a **completely database-driven role system**. Roles are determined by the `user_type` field in the `admin_login` table. No hardcoded employee IDs - all role assignments are managed through the database!

---

## 📊 How It Works

### 1. User Login
```
User logs in → System reads admin_login.user_type → Assigns role → Grants permissions
```

### 2. Role Assignment Flow
```php
// In role_check.php
user_type from database → role_mapping array → $user_role variable

// Examples:
'administrator' → 'Administrator'
'hr_senior_bp' → 'HR_Senior_BP'
'finance_officer' → 'Finance_Officer'
'dept_user' → 'DPT_Manager'
```

---

## 🔑 Available User Types

### To assign a role, set `admin_login.user_type` to one of these values:

| user_type | Assigned Role | Description |
|-----------|---------------|-------------|
| `administrator` | Administrator | Full system access |
| `gm` | GM | General Manager |
| `hr_senior_bp` | HR_Senior_BP | HR Senior Business Partner |
| `hr_operations` | HR_Operations | HR Operations Manager |
| `hr_supervisor` | HR_Supervisor | HR Supervisor |
| `hr_recruitment` | HR_Recruitment | HR Recruitment Specialist |
| `hr_payroll` | HR_Payroll | HR Payroll Manager |
| `finance_officer` | Finance_Officer | Finance Officer |
| `auditor` | Auditor | Internal Auditor |
| `gr_officer` | GR_Officer | General Relations Officer |
| `hr` | HR_Manager | HR Manager (legacy) |
| `dept_user` | DPT_Manager | Department Manager |
| `assistant` | (varies) | Depends on dept field |
| `employee` | Employee | Regular Employee |

---

## 🔧 How to Assign Roles

### Method 1: Direct SQL Update

```sql
-- Assign Administrator role
UPDATE admin_login SET user_type = 'administrator' WHERE emp_id = '5430';

-- Assign HR Senior BP role
UPDATE admin_login SET user_type = 'hr_senior_bp' WHERE emp_id = '5455';

-- Assign Finance Officer role
UPDATE admin_login SET user_type = 'finance_officer' WHERE emp_id = '3061';

-- Assign Department Manager role
UPDATE admin_login SET user_type = 'dept_user' WHERE emp_id = '4120';
```

### Method 2: Batch Update by Employee Type

```sql
-- Make all Managers into Department Managers
UPDATE admin_login al
JOIN employees e ON al.emp_id = e.emp_id
SET al.user_type = 'dept_user'
WHERE e.emp_type = 'Manager';

-- Make all Supporters into Employees
UPDATE admin_login al
JOIN employees e ON al.emp_id = e.emp_id
SET al.user_type = 'employee'
WHERE e.emp_type = 'Supporter';
```

### Method 3: Through Application UI

1. Go to "Users Management" page
2. Edit user
3. Select appropriate "Type of Permission" from dropdown
4. Save changes

---

## 💡 Special Cases

### Assistant Role (Department-Based)

The `assistant` user_type has special behavior based on the `dept` field:

```php
user_type = 'assistant' AND dept = 5  → HR_Assistant
user_type = 'assistant' AND dept = 2  → Finance_Assistant
user_type = 'assistant' AND dept = 6  → IT_Assistant
user_type = 'assistant' AND dept = ?  → Assistant
```

**SQL Example:**
```sql
-- Make someone an HR Assistant
UPDATE admin_login 
SET user_type = 'assistant', dept = 5 
WHERE emp_id = '5115';

-- Make someone a Finance Assistant
UPDATE admin_login 
SET user_type = 'assistant', dept = 2 
WHERE emp_id = '3015';
```

---

## 📝 Usage Examples

### Check User Role in PHP

```php
// Method 1: Use the $user_role variable (from role_check.php)
if ($user_role == 'Administrator') {
    // Administrator-specific code
}

if ($user_role == 'HR_Senior_BP') {
    // HR Senior BP-specific code
}

// Method 2: Use the boolean variables (from session_check.php)
if ($is_system_admin) {
    // Administrator functions
}

if ($isHR_Senior_BP) {
    // HR Senior BP functions
}

if ($isHR) {
    // Any HR role (combined check)
}

// Method 3: Check user_type directly
if ($user_type == 'hr_payroll') {
    // HR Payroll-specific code
}

// Method 4: Check multiple roles
if (in_array($user_role, ['Administrator', 'HR_Senior_BP', 'HR_Supervisor'])) {
    // Code for these specific roles
}
```

### Permission Checking Examples

```php
// Allow only HR team members
if ($isHR) {
    // Show HR dashboard
}

// Allow HR and Finance
if ($isHR || $isFinance_Officer || $isAuditor) {
    // Show employee salary data
}

// Allow specific roles
if ($is_system_admin || $isGM || $isHR_Senior_BP) {
    // Show executive reports
}

// Department-specific check
if ($isDeptManager && $user_dept == 10) {
    // Show GM department reports
}
```

---

## 🚀 Adding a New Employee to a Role

### Step-by-Step Process:

1. **Ensure employee has admin_login record:**
   ```sql
   SELECT * FROM admin_login WHERE emp_id = '1234';
   ```

2. **Assign the appropriate user_type:**
   ```sql
   UPDATE admin_login 
   SET user_type = 'hr_operations' 
   WHERE emp_id = '1234';
   ```

3. **Verify the assignment:**
   ```sql
   SELECT emp_id, fullname, user_type, dept 
   FROM admin_login 
   WHERE emp_id = '1234';
   ```

4. **Test the login:**
   - Login with the employee's credentials
   - Check if correct menu items appear
   - Verify page access permissions

---

## 🔄 Creating a New Role Type

### To add a completely new role:

1. **Add to role_mapping in `role_check.php`:**
   ```php
   $role_mapping = [
       // ... existing mappings ...
       'custom_role_name' => 'Custom_Role_Name',
   ];
   ```

2. **Add variables in `session_check.php`:**
   ```php
   $isCustomRole = ($user_type === 'custom_role_name');
   ```

3. **Update permission arrays in `main_menu.php`:**
   ```php
   $can_see_employees_group_main = [
       'Administrator', 'Custom_Role_Name', // ... others
   ];
   ```

4. **Assign to users in database:**
   ```sql
   UPDATE admin_login 
   SET user_type = 'custom_role_name' 
   WHERE emp_id = 'XXXX';
   ```

---

## ✅ Verification Checklist

After assigning roles, verify:

- [ ] User can login successfully
- [ ] Correct menu items are visible
- [ ] Correct pages are accessible
- [ ] Approval queues show correct requests
- [ ] Badge counts are accurate
- [ ] No permission errors in logs

---

## ⚠️ Important Notes

1. **user_type is case-sensitive:** Use lowercase with underscores (e.g., `hr_senior_bp`, not `HR_Senior_BP`)

2. **Always backup before mass updates:**
   ```sql
   CREATE TABLE admin_login_backup AS SELECT * FROM admin_login;
   ```

3. **Clear sessions after role changes:**
   - Users must logout and login again to see role changes
   - Or clear server session files

4. **Department field matters:** For `assistant` role, the `dept` field determines the specific assistant type

5. **No code deployment needed:** Changing user_type in database is enough - no PHP file changes required!

---

## 📊 Quick Reference Table

| Task | SQL Command |
|------|-------------|
| Make Admin | `UPDATE admin_login SET user_type = 'administrator' WHERE emp_id = 'XXX';` |
| Make HR Senior BP | `UPDATE admin_login SET user_type = 'hr_senior_bp' WHERE emp_id = 'XXX';` |
| Make Finance Officer | `UPDATE admin_login SET user_type = 'finance_officer' WHERE emp_id = 'XXX';` |
| Make Dept Manager | `UPDATE admin_login SET user_type = 'dept_user' WHERE emp_id = 'XXX';` |
| Make Employee | `UPDATE admin_login SET user_type = 'employee' WHERE emp_id = 'XXX';` |
| Make HR Assistant | `UPDATE admin_login SET user_type = 'assistant', dept = 5 WHERE emp_id = 'XXX';` |
| Check Current Role | `SELECT emp_id, fullname, user_type, dept FROM admin_login WHERE emp_id = 'XXX';` |

---

## 🎯 Benefits

✅ **No Hardcoding:** No employee IDs in code  
✅ **Easy Management:** Update database, not code  
✅ **Flexible:** Add new roles anytime  
✅ **Scalable:** Works for any organization size  
✅ **Auditable:** All changes in database logs  
✅ **Fast:** No code deployment for role changes  

---

**Need Help?** Check `update_user_types.sql` for detailed SQL examples and verification queries.
