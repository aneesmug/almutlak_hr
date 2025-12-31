# Role Creation Guide - Al-Mutlak WMS

## Overview
Roles in the system are defined in **two main files** and are stored in the **database**:

1. **Database**: Actual user role data stored in `admin_login` table
2. **Code Files**: Role mapping and permission definitions

---

## 1. DATABASE LOCATION
The actual user roles are stored in: **`admin_login` table**

**Key columns:**
- `user_type`: The role identifier (e.g., 'hr_senior_bp', 'administrator', 'finance_officer')
- `dept`: The department ID (determines team role)
- `emp_type`: Employee type (Manager, Supporter, etc.)

**Example values in `user_type` column:**
```
- 'administrator'
- 'gm'
- 'hr_senior_bp'
- 'hr_operations'
- 'hr_supervisor'
- 'hr_recruitment'
- 'hr_payroll'
- 'finance_officer'
- 'auditor'
- 'gr_officer'
- 'dept_user'
- 'employee'
```

---

## 2. CODE FILES THAT DEFINE ROLES

### File 1: `includes/role_check.php` 
**Location:** [d:\xampp\htdocs\almutlak\system\includes\role_check.php](../includes/role_check.php)

**Purpose:** Maps database user_type values to standardized role names

**Key Section:** The `$role_mapping` array (around line 37)

```php
$role_mapping = [
    'administrator' => 'Administrator',
    'gm' => 'GM',
    'hr_senior_bp' => 'HR_Senior_BP',
    'hr_operations' => 'HR_Operations',
    'hr_supervisor' => 'HR_Supervisor',
    'hr_recruitment' => 'HR_Recruitment',
    'hr_payroll' => 'HR_Payroll',
    'gr_officer' => 'GR_Officer',
    'finance_officer' => 'Finance_Officer',
    'auditor' => 'Auditor',
    'hr' => 'HR_Manager',
    'it' => 'IT_Team_Manager',
    'finance' => 'Finance_Manager',
    'dept_user' => 'DPT_Manager',
    'employee' => 'Employee',
];
```

---

### File 2: `includes/main_menu.php`
**Location:** [d:\xampp\htdocs\almutlak\system\includes\main_menu.php](../includes/main_menu.php)

**Purpose:** Defines page access control and menu visibility per role

**Key Sections:**

1. **Page Access Control** (around line 24)
```php
$page_roles = [
    'dashboard.php' => ['Administrator', 'GM', 'HR_Senior_BP', ...],
    'reports.php' => ['Administrator', 'HR_Senior_BP', ...],
    // ... more pages
];
```

2. **Menu Visibility Arrays** (from line 59 onwards)
```php
$can_see_employees_group_main = [
    'Administrator', 'HR_Senior_BP', 'HR_Operations', ...
];

$can_see_reports_page = [
    'Administrator', 'HR_Senior_BP', 'HR_Operations', ...
];
// ... more arrays
```

---

### File 3: `includes/session_check.php`
**Location:** [d:\xampp\htdocs\almutlak\system\includes\session_check.php](../includes/session_check.php)

**Purpose:** Defines permission variables for each role

**Key Section:** Permission variables (around line 137)
```php
$is_system_admin = ($user_type === 'administrator');
$isGM = ($user_type === 'gm');
$isHR_Manager = ($user_type === 'hr');
$isHR_Senior_BP = ($user_type === 'hr_senior_bp');
$isFinance_Officer = ($user_type === 'finance_officer');
// ... more permission variables
```

---

## STEPS TO CREATE A NEW ROLE

### Step 1: Add user_type to Database
When creating a user in the system, assign one of the values from the `user_type` column in `admin_login` table.

Or to create a completely new role type:

1. Go to **Database Management** (PhpMyAdmin)
2. Open table: `admin_login`
3. New users with this role should have their `user_type` set to your new value
   Example: `'my_custom_role'`

---

### Step 2: Add Role Mapping in `role_check.php`

**File:** `includes/role_check.php` (around line 37)

Add your role to the `$role_mapping` array:

```php
$role_mapping = [
    'administrator' => 'Administrator',
    'gm' => 'GM',
    // ... existing roles ...
    'my_custom_role' => 'My_Custom_Role',  // ← ADD THIS LINE
];
```

**Naming Convention:** Use underscores for database (my_custom_role) and PascalCase for display (My_Custom_Role)

---

### Step 3: Define Permissions in `session_check.php`

**File:** `includes/session_check.php` (around line 137)

Add a permission variable for your role:

```php
$isMyCustomRole = ($user_type === 'my_custom_role');
```

---

### Step 4: Add to Page Access Control in `main_menu.php`

**File:** `includes/main_menu.php` (around line 24)

Add your role to the `$page_roles` array for pages it should access:

```php
$page_roles = [
    'dashboard.php' => ['Administrator', 'GM', 'My_Custom_Role', ...],
    'reports.php' => ['Administrator', 'My_Custom_Role', ...],
    'all_users.php' => ['Administrator'],  // New role doesn't need access
];
```

---

### Step 5: Add to Menu Visibility Arrays in `main_menu.php`

**File:** `includes/main_menu.php` (from line 59 onwards)

Add your role to relevant menu visibility arrays:

```php
$can_see_employees_group_main = [
    'Administrator', 'HR_Senior_BP', 'My_Custom_Role', ...
];

$can_see_reports_page = [
    'Administrator', 'HR_Senior_BP', 'My_Custom_Role', ...
];
```

---

## Example: Creating "HR_Team_Lead" Role

### Step 1: Database
Assign users with `user_type = 'hr_team_lead'` in `admin_login` table

### Step 2: Update `role_check.php`
```php
$role_mapping = [
    'administrator' => 'Administrator',
    'gm' => 'GM',
    'hr_senior_bp' => 'HR_Senior_BP',
    'hr_team_lead' => 'HR_Team_Lead',  // ← NEW
    // ... rest of roles
];
```

### Step 3: Update `session_check.php`
```php
$isHR_Team_Lead = ($user_type === 'hr_team_lead');  // ← NEW
```

### Step 4: Update `main_menu.php` - Page Access
```php
$page_roles = [
    'dashboard.php' => ['Administrator', 'GM', 'HR_Senior_BP', 'HR_Team_Lead', ...],
    'reports.php' => ['Administrator', 'HR_Senior_BP', 'HR_Team_Lead', ...],
    'all_applied_vac.php' => ['Administrator', 'HR_Senior_BP', 'HR_Team_Lead', ...],
];
```

### Step 5: Update `main_menu.php` - Menu Visibility
```php
$can_see_employees_group_main = [
    'Administrator', 'HR_Senior_BP', 'HR_Team_Lead', ...
];

$can_see_applied_vac_page = [
    'Administrator', 'HR_Senior_BP', 'HR_Team_Lead', ...
];
```

---

## USER MANAGEMENT PAGE

To manage users and assign roles, go to:
- **Page:** [all_users.php](../all_users.php)
- **Menu:** Settings → Users (admin only)

Here you can:
1. Create new users
2. Assign/change user_type (role)
3. Assign department
4. Set employee type

---

## SUMMARY

| File | Purpose | Change Required |
|------|---------|-----------------|
| Database `admin_login` | Store user role | Set `user_type` column |
| `includes/role_check.php` | Map DB role to display name | Add to `$role_mapping` array |
| `includes/session_check.php` | Define permission variable | Add `$isMyRole = ...;` |
| `includes/main_menu.php` | Control access & menu visibility | Add to `$page_roles` and menu arrays |

---

## QUICK REFERENCE: ALL CURRENT ROLES

**Database Values (user_type):**
- administrator
- gm  
- hr_senior_bp
- hr_operations
- hr_supervisor
- hr_recruitment
- hr_payroll
- finance_officer
- auditor
- gr_officer
- dept_user
- employee
- hr (legacy - maps to HR_Manager)
- it (legacy - maps to IT_Team_Manager)
- finance (legacy - maps to Finance_Manager)

**Department-Based Roles:**
- HR_Team (dept 5)
- Finance_Team (dept 2)
- IT_Team (dept 6)
- Executive_Team (dept 10)
- Team_Manager variants (when emp_type = 'Manager')
