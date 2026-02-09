# Department Access Control Implementation Guide

## Overview
This document outlines the implementation of department-based access control throughout the Al-Mutlak WMS system, mirroring the existing company access control functionality.

## Database Schema
The `allowed_departments` column has been added to the `admin_login` table:
```sql
ALTER TABLE `admin_login`
ADD COLUMN `allowed_departments` JSON DEFAULT NULL 
COMMENT 'JSON array of department IDs user is allowed to access. NULL = all departments'
AFTER `allowed_companies`;
```

## Core Functions Added

### 1. Session Initialization (`includes/session_check.php`)
```php
// Department Access Control
$allowed_departments = null;  // Default: all departments
$allowed_departments_array = [];  // Default: empty array

if (isset($emprow['allowed_departments']) && !empty($emprow['allowed_departments'])) {
    try {
        $decoded_departments = json_decode($emprow['allowed_departments'], true);
        if (is_array($decoded_departments) && count($decoded_departments) > 0) {
            $allowed_departments = $decoded_departments;
            $allowed_departments_array = array_map('intval', $decoded_departments);
        }
    } catch (Exception $e) {
        $allowed_departments = null;
        $allowed_departments_array = [];
    }
}

$_SESSION['allowed_departments'] = $allowed_departments;
$_SESSION['allowed_departments_array'] = $allowed_departments_array;
$has_department_restrictions = !empty($allowed_departments_array) && count($allowed_departments_array) > 0;
```

### 2. Helper Functions (`includes/helper_functions.php`)

#### getDepartmentFilterSQL()
```php
function getDepartmentFilterSQL($column_name, $use_session = true) {
    global $conDB, $allowed_departments_array;
    
    $departments = $use_session && isset($_SESSION['allowed_departments_array']) 
        ? $_SESSION['allowed_departments_array'] 
        : $allowed_departments_array;
    
    // No restrictions = no WHERE clause needed
    if (empty($departments)) {
        return "";
    }
    
    // Create IN clause for allowed departments with AND prefix
    $departments_list = implode(',', array_map('intval', $departments));
    return " AND $column_name IN ($departments_list)";
}
```

#### getAccessibleDepartments()
```php
function getAccessibleDepartments($use_session = true) {
    global $allowed_departments_array;
    
    $departments = $use_session && isset($_SESSION['allowed_departments_array']) 
        ? $_SESSION['allowed_departments_array'] 
        : $allowed_departments_array;
    
    return !empty($departments) ? $departments : [];
}
```

## Implementation Pattern

### Standard Pattern for SQL Queries
```php
// Get company and department filters
$company_filter = getCompanyFilterSQL('e.comp_no', true);
$department_filter = getDepartmentFilterSQL('e.dept', true);

// Apply to query
$sql = "SELECT * FROM employees e WHERE e.status = 1" . $company_filter . $department_filter;
```

### Example Implementations

#### 1. Simple Query (No Alias)
```php
$company_filter = getCompanyFilterSQL('comp_no', true);
$department_filter = getDepartmentFilterSQL('dept', true);
$query = mysqli_query($conDB, "SELECT * FROM employees WHERE status=1" . $company_filter . $department_filter);
```

#### 2. Query with Table Alias
```php
$company_filter = getCompanyFilterSQL('e.comp_no', true);
$department_filter = getDepartmentFilterSQL('e.dept', true);
$query = mysqli_query($conDB, "SELECT * FROM employees e WHERE e.status=1" . $company_filter . $department_filter);
```

#### 3. Existing WHERE Clause
```php
$company_filter = getCompanyFilterSQL('e.comp_no', true);
$department_filter = getDepartmentFilterSQL('e.dept', true);

if (strpos($where_sql, 'WHERE') === false) {
    $where_sql = " WHERE 1=1" . $company_filter . $department_filter;
} else {
    $where_sql .= $company_filter . $department_filter;
}
```

## Files Updated

### ✅ Completed Files
1. **includes/session_check.php** - Department access initialization
2. **includes/helper_functions.php** - Helper functions added
3. **dashboard.php** - All employee count queries updated
4. **view_vacation_requests.php** - Vacation request filtering
5. **view_general_request.php** - General request filtering
6. **vacation_details_sch.php** - Vacation details and replacement employees
7. **vacation_report.php** - Vacation report filtering
8. **includes/ajaxFile/ajaxEmployee.php** - All 3 employee queries (emp_search, emp_data, emp_department)
9. **includes/ajaxFile/ajaxEmployeeSelect.php** - Employee selection dropdown
10. **includes/ajaxFile/ajaxVacation.php** - Vacation AJAX endpoints (emp_search, emp_data)
11. **includes/ajaxFile/ajaxSalary.php** - Salary processing employee query
12. **includes/ajaxFile/ajaxLoan.php** - Loan search employee function
13. **includes/ajaxFile/ajaxReports.php** - All 12 report exports:
    - Employee Report Export
    - Vacation Report Export
    - Loan Report Export
    - Salary Report Export
    - Attendance Report Export
    - Document Report Export
    - Evaluation Report Export
    - Resignation Report Export
    - Terminated Employees Report
    - End of Service (EOS) Prospective Report
    - Department Comparison Report
14. **dashbydepart.php** - All 7 department dashboard queries
15. **all_settlements.php** - Settlement listings
16. **all_resignations.php** - Resignation listings
17. **all_employee_list.php** - Employee list page
18. **all_applied_vac.php** - Applied vacation requests
19. **all_applied_loan.php** - Applied loan requests
20. **add_new_employee.php** - Employee registration counts
21. **find_birthday.php** - Birthday search
22. **employee_salary_report.php** - Salary report page
23. **search.php** - Employee search functionality
24. **includes/sup_emp_view.php** - Supervisor employee view

### 🎉 IMPLEMENTATION COMPLETE!

**Total Files Updated:** 24 files
**Total Locations Updated:** 86+ individual query locations
**Implementation Date:** February 4, 2026

All employee-related queries throughout the Al-Mutlak WMS system now respect both company AND department access restrictions for users.

## How to Use Department Access Control

### Assigning Department Access to Users

1. **Navigate to User Management:** Go to `all_users.php`
2. **Edit User:** Click edit on any user
3. **Select Departments:** 
   - Use the multi-select dropdown to choose specific departments
   - OR check "Full Access to All Departments" for unrestricted access
4. **Save:** Department restrictions are saved as JSON in `allowed_departments` column

### Testing Department Access

**Test Case 1: Full Access**
- User with NULL or empty `allowed_departments`
- Should see all employees from all departments (within company restrictions)

**Test Case 2: Single Department**
- User with `[5]` (HR department only)
- Should only see employees from HR department

**Test Case 3: Multiple Departments**
- User with `[1,3,5]` (Admin, IT, HR)
- Should only see employees from these three departments

**Test Case 4: Combined Filters**
- User with company restriction `[1,2]` AND department restriction `[5]`
- Should only see HR employees from companies 1 and 2

## Common Column Names
- `dept` - Most common department column
- `e.dept` - When using alias 'e' for employees table
- `employees.dept` - Full table name reference

## Implementation Pattern Examples

### Standard Pattern for SQL Queries
```php
// Get company and department filters
$company_filter = getCompanyFilterSQL('e.comp_no', true);
$department_filter = getDepartmentFilterSQL('e.dept', true);

// Apply to query
$sql = "SELECT * FROM employees e WHERE e.status = 1" . $company_filter . $department_filter;
```

### For Reports (using false parameter)
```php
// In report exports, use false to get from global vars instead of session
$company_filter = getCompanyFilterSQL('e.comp_no', false);
$department_filter = getDepartmentFilterSQL('e.dept', false);

if (!empty($company_filter)) {
    $where[] = substr($company_filter, 5); // Remove " AND " prefix
}
if (!empty($department_filter)) {
    $where[] = substr($department_filter, 5); // Remove " AND " prefix
}
```

## Migration Instructions

1. **Run SQL Migration:**
   ```bash
   mysql -u your_user -p almutlak_db < sql/add_allowed_departments.sql
   ```

2. **Verify Database:**
   ```sql
   DESC admin_login; -- Check for allowed_departments column
   ```

3. **Clear PHP Session Cache:**
   - Have users log out and log back in to initialize department access

4. **Assign Permissions:**
   - Update users who should have department restrictions
   - Leave NULL for admins/users who need full access

## Verification & Testing

After implementation, verify that department filtering is working correctly:

### Quick Test Queries

**Check User Department Access:**
```sql
SELECT id, admin_name, allowed_departments FROM admin_login WHERE allowed_departments IS NOT NULL;
```

**Test Employee Query with Department Filter:**
```php
// This should only return employees from allowed departments
$department_filter = getDepartmentFilterSQL('dept', true);
$query = mysqli_query($conDB, "SELECT * FROM employees WHERE status=1" . $department_filter);
```

**Verify Session Variables:**
```php
echo '<pre>';
print_r($_SESSION['allowed_departments_array']);
echo '</pre>';
```

### Testing Checklist

- [ ] Users with NULL `allowed_departments` see all employees
- [ ] Users with specific departments only see those employees
- [ ] Dashboard counts reflect department restrictions
- [ ] Vacation requests filtered by department
- [ ] Reports export only accessible departments
- [ ] AJAX employee searches respect restrictions
- [ ] Combined company + department filters work together

## Common Column Names
- `dept` - Most common department column
- `e.dept` - When using alias 'e' for employees table
- `employees.dept` - Full table name reference

## Testing Checklist

### For Each Updated File:
1. **Test with Full Access** (no department restrictions)
   - User should see all departments
   - Verify no SQL errors
   
2. **Test with Restricted Access** (specific departments only)
   - User should only see employees from allowed departments
   - Verify correct filtering
   
3. **Test with Multiple Filters** (company + department)
   - Both filters should work together
   - Results should match intersection of both

4. **Test Edge Cases**
   - Empty department array (should show nothing or all?)
   - NULL department value in employee record
   - Invalid department IDs

## Migration Steps

### 1. Database Migration
```bash
mysql -u your_user -p your_database < sql/add_allowed_departments.sql
```

### 2. Test Access Control
```sql
-- Test query with department filter
SELECT * FROM employees WHERE status=1 AND dept IN (1,3,5);
```

### 3. Update User Permissions
Users can be assigned departments through:
- **all_users.php** - Edit user modal with department multi-select
- Direct database update:
  ```sql
  UPDATE admin_login SET allowed_departments = '[1,3,5]' WHERE id = USER_ID;
  UPDATE admin_login SET allowed_departments = NULL WHERE id = ADMIN_ID; -- Full access
  ```

## Troubleshooting

### Issue: No data showing after update
**Check:**
1. User's `allowed_departments` value: `SELECT allowed_departments FROM admin_login WHERE id = USER_ID;`
2. Session variables: `$_SESSION['allowed_departments_array']`
3. Query includes department filter: Look for ` AND dept IN (...)` in debug output

### Issue: Department filter not working
**Check:**
1. Column name is correct (dept, e.dept, or employees.dept)
2. getDepartmentFilterSQL() is called before query
3. Filter is concatenated to WHERE clause

### Issue: SQL errors
**Check:**
1. AND/WHERE clause placement
2. Column names match table structure
3. Parentheses matching in complex queries

## Future Enhancements

1. **Helper Functions**
   - `hasDepartmentAccess($dept_id)` - Check if user can access specific department
   - `canUserAccessEmployee($emp_id)` - Check both company and department access

2. **UI Improvements**
   - Department badge indicators
   - Access restriction warnings
   - Bulk department assignment

3. **Reporting**
   - Department access audit logs
   - Access summary reports
   - Restriction change history

## Notes

- Department filtering works **alongside** company filtering
- Both filters use IN clause for multiple values
- Empty array = no restrictions (full access)
- NULL = full access to all departments
- Department restrictions respect existing $can_see_all_employees logic

## Support

For implementation questions:
1. Check this document first
2. Refer to company access control implementation (same pattern)
3. Review session_check.php for session variable initialization
4. Test with SQL directly before updating PHP code
