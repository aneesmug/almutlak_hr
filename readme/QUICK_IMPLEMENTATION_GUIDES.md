# Quick Implementation Guides for Company Access Control

**System:** Al-Mutlak WMS  
**Date:** December 30, 2025

---

## 📝 General Pattern

All implementations follow this simple pattern:

```php
// Include session_check.php (if not already included)
require_once __DIR__ . '/includes/session_check.php';

// Get company filter
$company_filter = getCompanyFilterSQL('table_alias.comp_no', true);

// Use in all queries
$sql = "SELECT * FROM employees WHERE status=1 ".$company_filter;
```

---

## 1️⃣ Reports Module

**Files:** `includes/ajaxFile/ajaxReports.php`, `reports.php`

### Implementation

```php
// In ajaxReports.php, for each report generation function:

// Add at function start:
$company_filter = getCompanyFilterSQL('e.comp_no', true);

// Example: Vacation Report Query (line ~463)
BEFORE:
    INNER JOIN employees e ON v.emp_id = e.emp_id

AFTER:
    INNER JOIN employees e ON v.emp_id = e.emp_id
    WHERE " . $company_filter . "

// For COUNT queries:
$sql = "SELECT COUNT(*) FROM employees 
        WHERE status=1 " . $company_filter;
```

### Key Queries to Update
- Line 355: `FROM employees e`
- Line 463: Vacation report employee join
- Line 548: Loan report employee join
- Line 616: Salary report employee join
- Line 665: Total employees count
- Line 1221: Employee data query

---

## 2️⃣ Vacation Management

**Files:** 
- `includes/ajaxFile/ajaxVacation.php`
- `vacation_report.php`
- `all_applied_vac.php`
- `view_vacation_requests.php`

### Implementation

```php
// In ajaxVacation.php:

// For vacation queries:
$company_filter = getCompanyFilterSQL('e.comp_no', true);

// Vacation table joins with employees:
$sql = "SELECT v.*, e.name FROM emp_vacation v
        INNER JOIN employees e ON v.emp_id = e.emp_id
        WHERE v.current_status='approved' " . $company_filter;

// For vacation list display:
$sql = "SELECT v.* FROM emp_vacation v
        INNER JOIN employees e ON v.emp_id = e.emp_id
        WHERE e.status=1 " . $company_filter;
```

### Files to Update
- `ajaxVacation.php` (lines 392, 480, 997, 1103, 1232, 1526)
- `vacation_report.php` (lines 67, 77, 84)
- `view_vacation_requests.php` (line 77)
- `all_applied_vac.php` (table query)

---

## 3️⃣ Loan Management

**Files:**
- `includes/ajaxFile/ajaxLoan.php`
- `all_applied_loan.php`
- Loan detail pages

### Implementation

```php
// In ajaxLoan.php:

$company_filter = getCompanyFilterSQL('e.comp_no', true);

// Get loans with employee info:
$sql = "SELECT l.*, e.name, e.emp_id FROM loans l
        INNER JOIN employees e ON l.emp_id = e.emp_id
        WHERE e.status=1 " . $company_filter;

// Get loan details for user:
$sql = "SELECT * FROM loans l
        INNER JOIN employees e ON l.emp_id = e.emp_id
        WHERE l.id = ? " . $company_filter;
```

### Files to Update
- `ajaxLoan.php` (lines 585, 1124, 1172, 2333, 2352, 2461, 2479)
- `all_applied_loan.php` (main query)

---

## 4️⃣ Resignation Management

**Files:**
- `includes/ajaxFile/ajaxResignation.php`
- `all_resignations.php`
- Resignation detail pages

### Implementation

```php
// In ajaxResignation.php:

$company_filter = getCompanyFilterSQL('e.comp_no', true);

// Get resignations:
$sql = "SELECT r.*, e.name FROM resignations r
        INNER JOIN employees e ON r.emp_id = e.emp_id
        WHERE r.status='pending' " . $company_filter;
```

### Files to Update
- `ajaxResignation.php` (line 438 and related queries)
- `all_resignations.php` (main query)

---

## 5️⃣ General Requests

**Files:**
- `includes/ajaxFile/smartRequest*.php` (multiple AJAX files)
- `includes/ajaxFile/smartRequestAjaxTbl.php`
- `all_requests.php`
- `all_general_requests.php`

### Implementation

```php
// In smartRequest files:

$company_filter = getCompanyFilterSQL('e.comp_no', true);

// Get requests with employees:
$sql = "SELECT gr.*, e.name FROM general_requests gr
        INNER JOIN employees e ON gr.initiated_by = e.emp_id
        WHERE gr.status='active' " . $company_filter;

// For DataTables AJAX (smartRequestAjaxTbl.php):
$where_clause = " WHERE gr.status='active' " . $company_filter;
```

### Files to Update
- Multiple `smartRequest*.php` files in ajaxFile directory
- `smartRequestAjaxTbl.php` (DataTables server-side filtering)
- `all_requests.php` (main query)
- `all_general_requests.php` (view_general_request.php, line 20)

---

## 6️⃣ Machine Management

**Files:**
- `add_machine.php`
- `all_machines.php`
- `view_machine.php`
- `edit_machine.php`

### Implementation

```php
// Simple implementation - machines are linked to companies

$company_filter = getCompanyFilterSQL('m.comp_no', true);

// Get machines:
$sql = "SELECT m.* FROM machines m
        WHERE m.status=1 " . $company_filter;

// With employee driver info:
$sql = "SELECT m.*, e.name as driver_name FROM machines m
        LEFT JOIN employees e ON m.assigned_to = e.emp_id
        WHERE m.status=1 " . $company_filter;
```

### Files to Update
- `all_machines.php` (main query)
- `view_machine.php` (detail view)

---

## 7️⃣ Car Management

**Files:**
- `add_car.php`
- `all_cars.php`
- `view_car.php`
- `edit_car.php`

### Implementation

```php
// Cars are linked to companies and driven by employees

$company_filter = getCompanyFilterSQL('c.comp_no', true);

// Get cars:
$sql = "SELECT c.* FROM cars c
        WHERE c.status=1 " . $company_filter;

// With driver info:
$sql = "SELECT c.*, e.name as driver_name FROM cars c
        LEFT JOIN employees e ON c.user_id = e.emp_id
        WHERE c.status=1 " . $company_filter;

// In view_car.php (line 77):
$sql = "SELECT cd.*, e.name FROM cars_drv cd
        LEFT JOIN employees e ON cd.car_user = e.emp_id
        WHERE cd.car_id = ? " . $company_filter;
```

### Files to Update
- `all_cars.php` (main query)
- `view_car.php` (lines 77, 446, 518)

---

## 8️⃣ Location Management

**Files:**
- `add_location.php`
- `all_locations.php`
- `view_location.php`
- `edit_location.php`

### Implementation

```php
// Locations are linked to companies

$company_filter = getCompanyFilterSQL('l.comp_no', true);

// Get locations:
$sql = "SELECT l.* FROM locations l
        WHERE l.status=1 " . $company_filter;

// With contract info:
$sql = "SELECT l.*, lc.contract_value FROM locations l
        LEFT JOIN location_contracts lc ON l.id = lc.location_id
        WHERE l.status=1 " . $company_filter;
```

### Files to Update
- `all_locations.php` (main query)
- `view_location.php` (detail view)

---

## 9️⃣ Customer Management

**Files:**
- `add_customer.php`
- `all_customers.php`
- `view_customer.php`
- `edit_customer.php`

### Implementation

```php
// If customers are linked to companies:

$company_filter = getCompanyFilterSQL('cust.comp_no', true);

// Get customers:
$sql = "SELECT cust.* FROM customers cust
        WHERE cust.status=1 " . $company_filter;

// With contact person (if linked to employee):
$sql = "SELECT cust.*, e.name as contact_person FROM customers cust
        LEFT JOIN employees e ON cust.contact_emp_id = e.emp_id
        WHERE cust.status=1 " . $company_filter;
```

### Files to Update
- `all_customers.php` (main query)
- `all_contact.php` (if contacts are company-related)

---

## 🔟 Employee Profile Pages

**Files:**
- `view_employee.php`
- `edit_employee.php`
- `profile.php`

### Implementation

```php
// Security check - verify user can access this employee

// At the start of view_employee.php:
$emp_id = isset($_GET['emp_id']) ? (int)$_GET['emp_id'] : 0;

// Get employee with company info:
$sql = "SELECT e.*, c.comp_name FROM employees e
        LEFT JOIN companies c ON e.comp_no = c.comp_id
        WHERE e.emp_id = ? LIMIT 1";

// Then verify access:
if (!canAccessCompany($emp_row['comp_no'])) {
    header("Location: ./dashboard.php");
    exit("Unauthorized access");
}

// For all employee-related queries in view_employee.php:
$company_filter = getCompanyFilterSQL('e.comp_no', true);

// Line 1099 needs filtering when pulling supervisors/referrers
$sql = "SELECT * FROM employees e 
        WHERE e.status=1 " . $company_filter;
```

### Files to Update
- `view_employee.php` (add security check + line 1099)
- `edit_employee.php` (add security check)
- `profile.php` (current user's profile - already secure)

---

## 1️⃣1️⃣ Payroll & Salary

**Files:**
- `add_emp_slry.php`
- `all_employee_salary_list.php`
- Payroll generation files

### Implementation

```php
// Salary records are linked to employees, so filter by employee company

$company_filter = getCompanyFilterSQL('e.comp_no', true);

// Get salary records:
$sql = "SELECT s.*, e.name FROM emp_salary s
        INNER JOIN employees e ON s.emp_id = e.emp_id
        WHERE s.status=1 " . $company_filter;
```

### Files to Update
- `all_employee_salary_list.php` (main query)
- Payroll-related AJAX files

---

## Priority Order for Implementation

### 🔴 **CRITICAL** (Most Important - User-Facing)
1. **Employee Profile Pages** (`view_employee.php`, `edit_employee.php`)
   - Security risk if not implemented
   
2. **Dashboard Components**
   - Already done ✅

3. **Employee List**
   - Already done ✅

### 🟠 **HIGH PRIORITY** (Commonly Used)
4. **Vacation Management** (`all_applied_vac.php`, vacation reports)
5. **Employee Requests** (`all_requests.php`)
6. **Loan Management** (`all_applied_loan.php`)

### 🟡 **MEDIUM PRIORITY** (Regular Use)
7. **Car Management** (`all_cars.php`)
8. **Machine Management** (`all_machines.php`)
9. **Salary/Payroll** (`all_employee_salary_list.php`)

### 🟢 **LOW PRIORITY** (Less Frequent)
10. **Reports Module** (more complex)
11. **Customer Management** (if applicable)
12. **Location Management** (if applicable)

---

## Testing Template

For each file implemented, test:

```php
// User A: System Admin
- Can see all employees
- Dashboard shows full counts
- All dropdowns work

// User B: Company 1 & 2 only
- Can see only Company 1 & 2 employees
- Dashboard counts accurate for those companies
- Dropdowns show only those companies' employees
- Cannot access Company 3 employees (security test)

// User C: Single Company
- Can see only their company
- All filters work correctly
```

---

## Helper Functions Reference

All these functions are in `includes/session_check.php`:

```php
// Check if user can access a specific company
$can_access = canAccessCompany($company_id);

// Get SQL WHERE clause for filtering
$filter = getCompanyFilterSQL('table.company_column', true);

// Get array of accessible companies
$companies = getAccessibleCompanies();

// Check if user has company restrictions
$has_restrictions = !empty($_SESSION['allowed_companies_array']);
```

---

## Common Issues & Solutions

| Issue | Solution |
|-------|----------|
| Users see all employees | Add `$company_filter` to all queries |
| Dashboard counts wrong | Add company filter to COUNT queries |
| Dropdowns show all companies | Add filter to SELECT options queries |
| Employee access not restricted | Add `canAccessCompany()` check in detail pages |
| AJAX endpoints broken | Ensure `session_check.php` is included |

---

## Support & Questions

1. Reference the **Implementation Guide** (`COMPANY_ACCESS_CONTROL_IMPLEMENTATION.md`)
2. Check **Database Reference** (`sql/COMPANY_ACCESS_CONTROL_DB_REFERENCE.sql`)
3. Review **Implemented Examples** in `dashboard.php` and `all_users.php`

---

**Last Updated:** December 30, 2025
