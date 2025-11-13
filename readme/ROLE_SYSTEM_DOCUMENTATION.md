# Al-Mutlak HR System - New Role Management Documentation

**Last Updated:** November 5, 2025  
**Version:** 2.0 - Employee ID-Based Role System

---

## 📋 Overview

The Al-Mutlak HR system now uses a **comprehensive employee ID-based role assignment system** that provides precise control over user permissions and access rights. This replaces the previous generic department-based role system.

---

## 🎯 Specific Role Assignments

### Administrative Roles

| Employee ID | Name | Role | Description |
|-------------|------|------|-------------|
| **5430** | Anees Mughal | `Administrator` | Full system access, all permissions |
| **3928** | Maher Thabet Al Jabari | `GM` | General Manager, executive approvals |

### HR Department Roles

| Employee ID | Name | Role | Department | Description |
|-------------|------|------|------------|-------------|
| **5455** | Haifaa Saeed Almalki | `HR_Senior_BP` | 5 | HR Senior Business Partner |
| **5423** | Abrar Mohammed Alsahbi | `HR_Operations` | 5 | HR Operations Manager |
| **5408** | Sharifah Ahmed Alsalhi | `HR_Supervisor` | 5 | HR Supervisor |
| **5115** | Roua Ahmed Sendi | `HR_Recruitment` | 5 | HR Recruitment Specialist |
| **3431** | Leandro Bunag Santiago | `HR_Payroll` | 5 | HR Payroll Manager |

### Finance & Audit Roles

| Employee ID | Name | Role | Department | Description |
|-------------|------|------|------------|-------------|
| **3061** | Ahmed Abdelhay A Soliman | `Finance_Officer` | 2 | Finance Officer |
| **3332** | TBD | `Auditor` | TBD | Internal Auditor |
| **5021** | N/A | `GR_Officer` | TBD | GR (General Relations) Officer |

### IT Department Roles

| Employee ID | Role | Department | Description |
|-------------|------|------------|-------------|
| **5127** | `IT_Assistant` | 6 | IT Administrator |

---

## 🔄 Dynamic Role Assignment

### Department Managers
- **Rule:** Any employee with `emp_type = 'Manager'` in the `employees` table (not assigned to specific roles above)
- **Role Assigned:** `DPT_Manager`
- **Permissions:** Department-level approvals, employee management within their department

### Regular Employees
- **Rule:** Any employee with `emp_type = 'Supporter'` in the `employees` table
- **Role Assigned:** `Employee`
- **Permissions:** Basic access to own profile, vacation requests, loan applications

---

## 📊 Role Variables in Code

### Available in `session_check.php` and globally:

```php
// Primary Role Variables (Employee ID-based)
$is_system_admin      // true for emp_id: 5430
$isGM                 // true for emp_id: 3928

// HR Team
$isHR_Senior_BP       // true for emp_id: 5455
$isHR_Operations      // true for emp_id: 5423
$isHR_Supervisor      // true for emp_id: 5408
$isHR_Recruitment     // true for emp_id: 5115
$isHR_Payroll         // true for emp_id: 3431
$isHR                 // true for ANY HR role above

// Finance & Audit
$isFinance_Officer    // true for emp_id: 3061
$isAuditor            // true for emp_id: 3332
$isGR_Officer         // true for emp_id: 5021

// General Categories
$isDeptManager        // true if user_role = 'DPT_Manager'
$isEmployee           // true if user_type = 'employee'
$isAssistant          // true if user_type = 'assistant'
$isItAssistant        // true if assistant in dept 6

// Legacy (backward compatibility)
$isDeptHr             // true if assistant in dept 5
```

---

## 🔐 Permission Groups

### Page Access Control

#### Employees Group Management
- **Full Access:** Administrator, All HR roles, Finance_Officer, Auditor
- **View Only:** DPT_Manager, IT_Assistant

#### Approvals (Vacation/Loan/Content)
- **Vacation:** Administrator, GM, All HR roles, DPT_Manager, IT_Assistant
- **Loans:** Administrator, GM, All HR roles, Finance_Officer, Auditor, DPT_Manager
- **Content:** Administrator, All HR roles

#### Smart Requests
- **Access:** Administrator, GM, All HR roles, Finance_Officer, Auditor, GR_Officer, DPT_Manager, IT_Assistant

#### Vouchers & Payroll
- **Access:** Administrator, HR_Senior_BP, HR_Payroll, Finance_Officer, Auditor

#### Assets (Cars/Locations/Machines)
- **Access:** Administrator, GR_Officer

---

## 🛠️ Implementation Details

### Files Modified

1. **`includes/role_check.php`**
   - Added employee ID-based role mapping array
   - Priority: Specific ID mapping → Employee type check → Legacy fallback

2. **`includes/session_check.php`**
   - Added all new role boolean variables
   - Combined HR roles into `$isHR` variable
   - Updated modification summary

3. **`includes/main_menu.php`**
   - Updated all `page_roles` arrays with new roles
   - Updated menu visibility permission arrays
   - Updated loan and smart request count queries
   - Modified `$is_admin` and `$is_gm` checks

---

## 📝 Usage Examples

### Checking Permissions in PHP

```php
// Check if user is any HR role
if ($isHR) {
    // Show HR-specific features
}

// Check specific HR role
if ($isHR_Payroll) {
    // Show payroll features
}

// Check if user can see employee list
if ($is_system_admin || $isHR || $isFinance_Officer || $isDeptManager) {
    // Display employee list
}

// Check from role_check.php variable
if (in_array($user_role, ['Administrator', 'HR_Senior_BP', 'HR_Operations'])) {
    // Grant access
}
```

### Checking Permissions in JavaScript

```javascript
// Available in window.user_role from session
if (window.user_role === 'HR_Payroll') {
    // Show payroll-specific UI
}

// Multiple role check
const hrRoles = ['HR_Senior_BP', 'HR_Operations', 'HR_Supervisor', 'HR_Recruitment', 'HR_Payroll'];
if (hrRoles.includes(window.user_role)) {
    // HR-specific functionality
}
```

---

## 🔄 Migration from Old System

### Old → New Mapping

| Old System | New System |
|------------|------------|
| `user_type = 'administrator'` | `Administrator` role (emp_id: 5430) |
| `user_type = 'hr'` | Specific HR roles based on employee ID |
| `user_type = 'assistant' AND dept = 5` | HR-specific roles or legacy `$isDeptHr` |
| `user_type = 'gm'` | `GM` role (emp_id: 3928) |
| `emp_type = 'Manager'` | `DPT_Manager` (if not in specific mapping) |
| `emp_type = 'Supporter'` | `Employee` |

---

## ⚠️ Important Notes

1. **Priority Order:** Employee ID mapping takes precedence over emp_type checks
2. **Backward Compatibility:** Legacy role variables still available for old code
3. **Case Sensitivity:** Role names are case-sensitive in arrays
4. **Database Updates:** When adding new specific roles, update `$specific_role_mapping` array in `role_check.php`
5. **Testing:** Always test permission changes with actual user accounts

---

## 🚀 Adding New Roles

To add a new specific role:

1. **Update `role_check.php`:**
   ```php
   $specific_role_mapping = [
       // ... existing mappings ...
       '9999' => 'New_Role_Name',
   ];
   ```

2. **Update `session_check.php`:**
   ```php
   $isNewRole = ($empid == '9999');
   ```

3. **Update `main_menu.php`:**
   - Add to relevant `$can_see_*` arrays
   - Add to `$page_roles` for specific pages

4. **Test thoroughly** with the specific employee account

---

## 📞 Support

For questions about role assignments or permission issues, contact:
- **System Administrator:** Anees Mughal (emp_id: 5430)
- **HR Senior BP:** Haifaa Saeed Almalki (emp_id: 5455)

---

**End of Documentation**
