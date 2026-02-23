# Company-Level Access Control Implementation Guide

**Date:** December 30, 2025  
**Status:** ✅ IMPLEMENTATION IN PROGRESS  
**System:** Al-Mutlak WMS

---

## 📋 Overview

This document outlines the comprehensive company-level access control system implemented across the Al-Mutlak project. The system ensures that users can only access employees from companies they have been granted access to.

---

## 🎯 Key Principle

**No One Can See Other Companies' Employees Unless Explicitly Granted Full Access**

- Users with full company access (system admins): Can see all employees from all companies
- Users with restricted access: Can see only employees from their allowed companies
- Users with no access: Cannot see any employees (locked down)

---

## 📁 Files Modified

### 1. **Database Setup** ✅
- **File:** `admin_login` table
- **Change:** Added `allowed_companies` JSON column
- **Purpose:** Stores array of company IDs user can access (e.g., `[1, 2, 5]`)
- **NULL = Full Access:** If NULL, user has full access to all companies

### 2. **Session & Helper Functions** ✅
- **File:** `includes/session_check.php`
- **Functions Added:**
  - `canAccessCompany($company_id)` - Check if user can access specific company
  - `getCompanyFilterSQL($column_name)` - Get WHERE clause for filtering queries
  - `getAccessibleCompanies()` - Get array of accessible company IDs
- **Variables Initialized:**
  - `$allowed_companies_array` - User's accessible companies
  - `$has_company_restrictions` - Boolean flag
  - `$_SESSION['allowed_companies_array']` - Session storage

### 3. **User Management** ✅
- **File:** `all_users.php`
- **Changes:**
  - Added Select2 library CSS & JS
  - Enhanced edit modal with company selection dropdown
  - Display current allowed companies in `data-allowed_companies` attribute

### 4. **User Edit Form** ✅
- **File:** `assets/js/jquery.app.js?t=<?= time() ?>`
- **Changes:**
  - Updated `edit_user_HTML()` function with company access section
  - Updated `loadCompanyAccess()` to fetch and display companies
  - Integrated Select2 for searchable multi-select
  - Added proper Select2 initialization and cleanup

### 5. **User Update Handler** ✅
- **File:** `includes/ajaxFile/ajaxUser.php`
- **Changes:**
  - Process company access data from modal
  - Store as JSON in `allowed_companies` column
  - Handle full access checkbox logic

### 6. **Company Access Endpoint** ✅
- **File:** `includes/ajaxFile/getCompanyAccess.php` (NEW)
- **Purpose:** Return list of available companies and user's current companies
- **Security:** Only accessible to system admins

### 7. **Dashboard** ✅
- **File:** `dashboard.php`
- **Changes:**
  - All employee count queries now include company filter
  - Updated lines 25-80 to add `$company_filter` to all SQL queries
  - Counts now reflect only accessible companies' employees

### 8. **Employee List** ✅
- **File:** `all_employee_list.php`
- **Changes:**
  - Added company filter to employee query
  - Only shows employees from accessible companies

### 9. **AJAX Employee Handlers** ✅
- **File:** `includes/ajaxFile/ajaxEmployee.php`
- **Changes:**
  - Updated `emp_search` - Add company filter
  - Updated `emp_data` - Add company filter
  - Updated `emp_department` - Add company filter
  - Updated `get_hr_senior_bp` - Add company filter
  - Updated `get_hr_team_members` - Add company filter

### 10. **Employee Select** ✅
- **File:** `includes/ajaxFile/ajaxEmployeeSelect.php`
- **Changes:**
  - Added session_check.php include
  - Added company filter to SELECT query

### 11. **CSS Styling** ✅
- **File:** `assets/css/style.css`
- **Changes:**
  - Added Select2 modal-specific styling
  - Proper z-index and focus states
  - Bootstrap integration

---

## 🔧 How to Implement Company Access Control

### For Administrators:

1. **Go to All Users page** → `all_users.php`
2. **Click Edit on any user**
3. **In the modal:**
   - Check "Full Access to All Companies" OR
   - Leave unchecked and select specific companies from dropdown
4. **Click "Yes, Update!"**

### For System Admins:

Full access is automatic for users with `user_type = 'administrator'`

---

## 🚀 Implementation Checklist

### ✅ Completed

- [x] Database schema (`allowed_companies` JSON column)
- [x] Session initialization (`allowed_companies_array`)
- [x] Helper functions (`getCompanyFilterSQL()`, `canAccessCompany()`)
- [x] User edit modal with Select2 dropdown
- [x] Dashboard employee counts with company filtering
- [x] Employee list with company filtering
- [x] AJAX endpoints with company filtering
- [x] CSS styling for Select2 in modals
- [x] Backend processing and JSON storage

### ⏳ In Progress / Not Yet Applied To:

The following areas still need company filtering applied:

#### **Reports Module**
- [ ] `includes/ajaxFile/ajaxReports.php` - Add company filter to all report queries
- [ ] Status: Many employee count queries need filtering

#### **Vacation Management**
- [ ] `includes/ajaxFile/ajaxVacation.php` - Filter vacation queries by company
- [ ] `vacation_report.php` - Filter vacation report by company
- [ ] `all_applied_vac.php` - Show only accessible companies' vacation requests

#### **Loan Management**
- [ ] `includes/ajaxFile/ajaxLoan.php` - Filter loan queries by company
- [ ] `all_applied_loan.php` - Show only accessible companies' loans

#### **Resignation Management**
- [ ] `includes/ajaxFile/ajaxResignation.php` - Filter resignations by company
- [ ] `all_resignations.php` - Show only accessible companies' resignations

#### **General Requests**
- [ ] `includes/ajaxFile/smartRequest*.php` - Filter requests by company
- [ ] `all_requests.php` - Show only accessible companies' requests
- [ ] `all_general_requests.php` - Filter by company

#### **Machine/Asset Management**
- [ ] `add_machine.php`, `all_machines.php` - Filter by company
- [ ] `view_machine.php` - Filter by company

#### **Car Management**
- [ ] `add_car.php`, `all_cars.php` - Filter by company
- [ ] `view_car.php` - Filter by company

#### **Location Management**
- [ ] `add_location.php`, `all_locations.php` - Filter by company
- [ ] `view_location.php` - Filter by company

#### **Customer Management**
- [ ] `add_customer.php`, `all_customers.php` - Filter by company
- [ ] `view_customer.php` - Filter by company (if applicable)

#### **Other Pages**
- [ ] `view_employee.php` - Verify user can access that employee
- [ ] `edit_employee.php` - Verify user can access that employee
- [ ] `profile.php` - Verify employee is from accessible company

---

## 📝 Implementation Template

For each file that needs company filtering, follow this pattern:

```php
// At the beginning of the PHP logic (after session_check.php is included):
$company_filter = getCompanyFilterSQL('employees.comp_no', true);

// In all SELECT queries that access employees or related data:
$sql = "SELECT * FROM employees WHERE status=1 ".$company_filter;

// For queries that use different table aliases:
$company_filter = getCompanyFilterSQL('e.comp_no', true); // Using 'e' alias
$sql = "SELECT * FROM employees e WHERE status=1 ".$company_filter;
```

---

## 🔐 Security Notes

1. **Function Checks Session Variables:** `getCompanyFilterSQL()` automatically checks:
   - `$_SESSION['allowed_companies_array']`
   - User's company restrictions
   - System admin status

2. **Null = Full Access:** If `allowed_companies` is NULL in database:
   - User has access to ALL companies
   - No WHERE clause is added to filter

3. **Implicit Denial:** Users with empty `allowed_companies` array:
   - Cannot see any employees (unless admin)
   - Functions return empty results

---

## 🧪 Testing Checklist

### User A (Full Access - System Admin)
- [ ] Can see all employees in dashboard counts
- [ ] Can see all employees in all_employee_list.php
- [ ] Company filter shows all companies available

### User B (Company 1, 2 Only)
- [ ] Dashboard counts show only Company 1 & 2 employees
- [ ] Employee list shows only Company 1 & 2 employees
- [ ] Cannot access employees from Company 3 or other companies
- [ ] Vacation/Loan/Request reports filtered correctly

### User C (Single Company)
- [ ] Can only see that specific company's data
- [ ] All dropdowns show only employees from their company

---

## 📊 Query Examples

### Example 1: Employee Search with Company Filter
```php
$company_filter = getCompanyFilterSQL('e.comp_no', true);
$sql = "SELECT e.* FROM employees e 
        WHERE e.status=1 ".$company_filter." 
        ORDER BY e.name ASC";
```

### Example 2: Dashboard Employee Count
```php
$company_filter = getCompanyFilterSQL('comp_no', true);
$sql = "SELECT COUNT(*) as total FROM employees 
        WHERE status=1 AND fly=0 ".$company_filter;
```

### Example 3: Multiple Joins with Company Filter
```php
$company_filter = getCompanyFilterSQL('e.comp_no', true);
$sql = "SELECT v.*, e.name, d.dep_nme 
        FROM emp_vacation v
        INNER JOIN employees e ON v.emp_id = e.emp_id
        LEFT JOIN department d ON e.dept = d.id
        WHERE v.current_status='approved' ".$company_filter;
```

---

## 🐛 Troubleshooting

### Issue: Users can see all employees despite company restrictions

**Solution:** Check if `getCompanyFilterSQL()` is being called in that file.

```php
// Add this line at the top
$company_filter = getCompanyFilterSQL('e.comp_no', true);

// Add this to queries
$sql = "SELECT * FROM employees e WHERE status=1 ".$company_filter;
```

### Issue: Dashboard counts are inaccurate

**Solution:** Verify all COUNT(*) queries in dashboard.php have `.$company_filter` appended.

### Issue: Select2 dropdown not showing properly

**Solution:** 
1. Verify Select2 CSS is loaded: `plugins/select2/css/select2.min.css`
2. Verify Select2 JS is loaded: `plugins/select2/js/select2.min.js`
3. Check browser console for errors

---

## 📞 Support

For questions about company-level access control implementation, refer to:
- `includes/session_check.php` - Helper function documentation
- `all_users.php` - User management modal example
- This document

---

## Version History

| Date | Version | Changes |
|------|---------|---------|
| 2025-12-30 | 1.0 | Initial implementation with employee access control |

---

**Last Updated:** December 30, 2025  
**Next Review:** Upon completion of all module integrations
