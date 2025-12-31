<!-- 
COMPANY-LEVEL ACCESS CONTROL IMPLEMENTATION
Generated: 2025-12-30
-->

# Company-Level Access Control System

## Overview
This implementation allows restricting which companies each user can access in the system. Users can be limited to one or multiple companies, or have full access to all companies.

## Database Changes

### New Column Added
```sql
ALTER TABLE `admin_login` 
ADD COLUMN `allowed_companies` JSON DEFAULT NULL;
```

**Column Details:**
- **Name:** `allowed_companies`
- **Type:** JSON
- **Default:** NULL (means full access to all companies)
- **Format:** JSON array of company IDs: `[1, 2, 5]`
- **Purpose:** Restrict which companies a user can view and access

## How It Works

### Access Logic

1. **NULL (No Restrictions)**
   - User has access to ALL companies
   - No filtering applied
   - Example: Administrators, GMs

2. **JSON Array (Restricted Access)**
   - User can only access companies in the array
   - Filtering applied automatically
   - Example: `[1, 2]` = user can only see companies 1 and 2

### User Types Affected

All users EXCEPT those with `user_type = 'employee'` can have company restrictions:
- Managers (`dept_user`)
- HR roles (`hr_senior_bp`, `hr_operations`, etc.)
- Finance Officer
- Auditor
- GR Officer
- IT Team members
- Department managers

Regular employees (`user_type = 'employee'`) can also be restricted.

## Usage Examples

### Setting Company Access via Database

```sql
-- Restrict user to companies 1 and 2
UPDATE `admin_login` 
SET `allowed_companies` = JSON_ARRAY(1, 2) 
WHERE `id_iqama` = '1234567890';

-- Grant user access to companies 1, 3, and 5
UPDATE `admin_login` 
SET `allowed_companies` = JSON_ARRAY(1, 3, 5) 
WHERE `user_type` = 'finance_officer' AND `id_iqama` = '9876543210';

-- Remove restrictions (full access)
UPDATE `admin_login` 
SET `allowed_companies` = NULL 
WHERE `id_iqama` = '1234567890';

-- Check which users have company restrictions
SELECT id_iqama, user_type, allowed_companies 
FROM `admin_login` 
WHERE allowed_companies IS NOT NULL;
```

### Using in PHP Code

#### 1. Check if User Can Access a Company
```php
<?php
// In any PHP file that includes session_check.php

// Check single company access
if (canAccessCompany(1)) {
    echo "User can access company 1";
} else {
    echo "User does NOT have access to company 1";
}

// Check before processing employee from company 2
$employee_company = 2;
if (!canAccessCompany($employee_company)) {
    die("You do not have permission to access this company");
}
?>
```

#### 2. Filter Database Queries
```php
<?php
// Get SQL WHERE clause for company filtering
$where = getCompanyFilterSQL('comp_no');

// Build query with company filter
$sql = "SELECT * FROM employees WHERE " . $where . " AND status = 1";
// If user has restrictions: SELECT * FROM employees WHERE comp_no IN (1,2) AND status = 1
// If no restrictions: SELECT * FROM employees WHERE  AND status = 1 (empty, so just check status)

// Better approach with conditional
$where = getCompanyFilterSQL('comp_no');
$sql = "SELECT * FROM employees WHERE status = 1";
if (!empty($where)) {
    $sql .= " AND " . $where;
}
$result = mysqli_query($conDB, $sql);
?>
```

#### 3. Get User's Accessible Companies
```php
<?php
// Get array of allowed company IDs
$accessible_companies = getAccessibleCompanies();

if (empty($accessible_companies)) {
    echo "User has full access to all companies";
} else {
    echo "User can access companies: " . implode(", ", $accessible_companies);
}

// Use in dropdown
foreach ($accessible_companies as $company_id) {
    echo "<option value='$company_id'>Company $company_id</option>";
}
?>
```

#### 4. Multiple Column Filtering
```php
<?php
// If you have multiple tables with company references
$filter1 = getCompanyFilterSQL('employees.comp_no');
$filter2 = getCompanyFilterSQL('employees.company_id');

$sql = "SELECT * FROM employees WHERE 1=1";
if (!empty($filter1)) {
    $sql .= " AND " . $filter1;
}
$result = mysqli_query($conDB, $sql);
?>
```

## Session Variables

After login, the following variables are automatically set:

```php
// Session variables set in session_check.php
$_SESSION['allowed_companies']       // JSON string from database
$_SESSION['allowed_companies_array'] // Array of company IDs
$GLOBALS['allowed_companies_array']  // Global array
$GLOBALS['has_company_restrictions'] // Boolean: true if restricted
```

## Implementation Checklist

### For Managers/Supervisors
- [ ] Set `allowed_companies` JSON to their relevant companies
- [ ] Update company filter WHERE clauses in their queries
- [ ] Test employee listings only show filtered companies

### For Department Heads
- [ ] Restrict to their own department's companies (if needed)
- [ ] Or grant full access with `allowed_companies = NULL`

### For Finance Officers
- [ ] Set to companies they manage financially
- [ ] Update financial reports to respect company filter

### For Reports/Dashboards
- [ ] Add company filter to report generators
- [ ] Filter department data by allowed companies
- [ ] Update export functions

## Testing

```sql
-- Test 1: Check user with restrictions
SELECT id_iqama, user_type, allowed_companies 
FROM `admin_login` 
WHERE id_iqama = '1234567890';
-- Should return: [..., '1234567890', 'finance_officer', '[1, 2]']

-- Test 2: Verify JSON is valid
SELECT JSON_VALID(allowed_companies) as is_valid
FROM `admin_login` 
WHERE allowed_companies IS NOT NULL;
-- Should return: 1 (true)

-- Test 3: Extract company IDs
SELECT id_iqama, JSON_EXTRACT(allowed_companies, '$[*]') as company_ids
FROM `admin_login` 
WHERE allowed_companies IS NOT NULL;
```

## Migration from Old System (if applicable)

If you had a previous company access system:

```sql
-- Example: Migrate from single company_id column
UPDATE `admin_login` 
SET `allowed_companies` = JSON_ARRAY(company_id) 
WHERE company_id IS NOT NULL;
```

## Security Considerations

1. **Always validate** user can access company BEFORE returning data
2. **Use functions** `canAccessCompany()` in critical operations
3. **Log access** when company restrictions are checked
4. **Combine with role checks** - Company restrictions are additional to role-based permissions
5. **Never trust user input** for company_id - always check against allowed_companies

## Future Enhancements

- Add UI for managing company access in all_users.php
- Create audit log for company access changes
- Add company access history/changelog
- Implement dynamic company group assignments
- Add department-based automatic company assignment

## Support Functions Reference

### `canAccessCompany($company_id, $use_session = true)`
Check if user can access a specific company

### `getCompanyFilterSQL($column_name, $use_session = true)`
Get SQL WHERE clause for company filtering

### `getAccessibleCompanies($use_session = true)`
Get array of accessible company IDs

## Files Modified

1. **Database:**
   - Migration: `/sql/add_company_access_control.sql`

2. **PHP Files:**
   - [includes/session_check.php](includes/session_check.php) - Added company access initialization and helper functions

3. **Optional - To Implement:**
   - [all_users.php](all_users.php) - Add UI for managing company access
   - [reports.php](reports.php) - Update to respect company restrictions
   - Any employee/customer listing pages
