# Employee Table Query Audit Report
**Generated:** December 30, 2025

---

## Summary
This report details all PHP files querying the `employees` table, their current session_check.php inclusion status, SELECT queries found, and company filtering status.

---

## File Analysis

### 1. **search.php**
- **File Path:** [search.php](search.php)
- **session_check.php Status:** ✅ **INCLUDED** (Line 2)
- **SELECT Queries from employees table:**
  1. Line 88: `SELECT COUNT(*) as totalCount FROM 'employees' WHERE` (with dynamic construct)
  2. Line 104: `SELECT * FROM 'employees' WHERE` (with dynamic construct)
  3. Line 132: `SELECT COUNT(*) as total FROM employees` (unfiltered total count)
- **Company Filtering:** ⚠️ **PARTIAL** - Has department-based access control but no explicit company filtering applied
- **Query Type:** mysqli with prepared statements (mixed - some constructed queries)
- **Notes:** Uses prepared statements for the main queries but still has an unfiltered count query

---

### 2. **vacation_details_sch.php**
- **File Path:** [vacation_details_sch.php](vacation_details_sch.php)
- **session_check.php Status:** ❌ **NOT INCLUDED**
- **SELECT Queries from employees table:**
  1. Line 4: `SELECT * FROM 'employees' WHERE 'emp_id'='...$_GET['emp_id']...'` (vulnerable to SQL injection)
  2. Line 207: `SELECT * FROM 'employees' WHERE 'emp_id'='...$_GET['emp_id']...'` (duplicate query, vulnerable)
  3. Line 308: `SELECT * FROM 'employees' WHERE 'emp_sup_type'='mocha' AND 'note'!='terminat' AND 'dept'<>'' ORDER BY 'dept'` (no filtering)
- **Company Filtering:** ❌ **NOT APPLIED**
- **Query Type:** mysqli (procedural, string concatenation - HIGH RISK)
- **Notes:** **CRITICAL** - No session check, SQL injection vulnerabilities, no company filtering

---

### 3. **find_birthday.php**
- **File Path:** [find_birthday.php](find_birthday.php)
- **session_check.php Status:** ✅ **INCLUDED** (Line 3)
- **SELECT Queries from employees table:**
  1. Line 137: `SELECT * FROM 'employees' WHERE MONTH('dob') = MONTH('...$dobget...') AND 'status' = 1`
- **Company Filtering:** ❌ **NOT APPLIED**
- **Query Type:** mysqli (string concatenation - vulnerable but simpler)
- **Notes:** Has session check but no company filtering applied to birthday search

---

### 4. **employee_salary_report.php**
- **File Path:** [employee_salary_report.php](employee_salary_report.php)
- **session_check.php Status:** ✅ **INCLUDED** (Line 2)
- **SELECT Queries from employees table:**
  1. Line 112-127: Complex SELECT with JOINs:
     ```sql
     SELECT e.emp_id, e.name, s.sponsor, es.basic, es.housing... 
     FROM employees AS e
     LEFT JOIN emp_salary AS es ON e.emp_id = es.emp_id AND es.status = 1
     LEFT JOIN sponsorship AS s ON e.emp_sup_type = s.id
     LEFT JOIN bank_list AS bl ON e.bank_name = bl.bnk_id
     LEFT JOIN department AS d ON e.dept = d.id
     LEFT JOIN companies AS c ON e.comp_no = c.comp_id
     WHERE e.status = 1
     ORDER BY e.name ASC
     ```
- **Company Filtering:** ✅ **PARTIALLY APPLIED** - Query joins `companies` table but does NOT filter by company_id
- **Query Type:** mysqli (direct query)
- **Notes:** Already has companies table joined but needs WHERE clause to filter by user's company

---

### 5. **add_new_employee.php**
- **File Path:** [add_new_employee.php](add_new_employee.php)
- **session_check.php Status:** ✅ **INCLUDED** (Line 3)
- **SELECT Queries from employees table:**
  1. Line 9: `SELECT COUNT(*) FROM 'employees' WHERE 'status'=1 AND 'fly'='no' AND 'dept'='{$user_dept}'` (dept filtered)
  2. Line 12: `SELECT COUNT(*) FROM 'employees' WHERE 'status'='no' AND 'dept'='{$user_dept}'` (dept filtered)
  3. Line 15: `SELECT COUNT(*) FROM 'employees' WHERE 'fly'='yes' AND 'dept'='{$user_dept}'` (dept filtered)
  4. Line 18: `SELECT COUNT(*) FROM 'employees' WHERE 'dept'='{$user_dept}'` (dept filtered)
  5. Line 21: `SELECT COUNT(*) FROM 'employees' WHERE 'emp_sup_type'='man_power'` (NO filtering)
  6. Line 24: `SELECT COUNT(*) FROM 'employees' WHERE 'status'=1 AND 'fly'='no'` (NO filtering)
  7. Line 27: `SELECT COUNT(*) FROM 'employees' WHERE 'status'='no'` (NO filtering)
  8. Line 30: `SELECT COUNT(*) FROM 'employees' WHERE 'fly'='yes'` (NO filtering)
  9. Line 33: `SELECT COUNT(*) FROM 'employees'` (unfiltered)
- **Company Filtering:** ❌ **NOT APPLIED** (uses department filtering but not company filtering)
- **Query Type:** mysqli (string concatenation)
- **Notes:** Has conditional logic - dept_user sees only their dept, but company filtering is completely missing

---

### 6. **view_general_request.php**
- **File Path:** [view_general_request.php](view_general_request.php)
- **session_check.php Status:** ✅ **INCLUDED** (Line 2)
- **SELECT Queries from employees table:**
  1. Line 18-23: `SELECT e.'emp_id', e.'name', al.'user_type', e.'dept' FROM 'employees' e JOIN 'admin_login' al ON e.'emp_id' = al.'emp_id' WHERE al.'user_type' != 'employee' AND e.'status' = 1 ORDER BY e.'name'`
  2. Line 128: `SELECT d.*, e.name as received_employee_name FROM general_request_deliveries d LEFT JOIN employees e ON e.emp_id = d.received_by WHERE d.request_inv_no = '$inv_no'` (vulnerable parameter)
- **Company Filtering:** ❌ **NOT APPLIED**
- **Query Type:** mysqli (mixed - some vulnerable to SQL injection)
- **Notes:** Fetches approvers across all companies, no company filtering on either query

---

### 7. **all_employee_salary_list.php**
- **File Path:** [all_employee_salary_list.php](all_employee_salary_list.php)
- **session_check.php Status:** ✅ **INCLUDED** (Line 3)
- **SELECT Queries from employees table:**
  1. Line 130: `SELECT * FROM 'employees' WHERE 'status'=1 AND 'emp_sup_type'='mocha'`
- **Company Filtering:** ❌ **NOT APPLIED**
- **Query Type:** mysqli (string concatenation)
- **Notes:** Simple query but no company filtering for 'mocha' employees

---

### 8. **includes/sup_emp_view.php**
- **File Path:** [includes/sup_emp_view.php](includes/sup_emp_view.php)
- **session_check.php Status:** ❌ **NOT INCLUDED** (This is an include file, should be called from another file)
- **SELECT Queries from employees table:**
  1. Line 3: `SELECT * FROM 'employees' WHERE 'dept' = '{$dept_get}' AND 'emp_id'<>'{$emp_id_get}' AND 'status'=1`
- **Company Filtering:** ⚠️ **PARTIAL** - Filters by department only
- **Query Type:** mysqli (string concatenation)
- **Notes:** Includes file showing supervisor's employees within same department; no company filtering

---

### 9. **includes/ajaxFile/ajaxSalary.php**
- **File Path:** [includes/ajaxFile/ajaxSalary.php](includes/ajaxFile/ajaxSalary.php)
- **session_check.php Status:** ❌ **NOT INCLUDED**
- **SELECT Queries from employees table:**
  1. Line 21: `SELECT * FROM 'employees' WHERE 'id'='$chk_id'` (vulnerable to SQL injection)
- **Company Filtering:** ❌ **NOT APPLIED**
- **Query Type:** mysqli (string concatenation with POST variable - CRITICAL VULNERABILITY)
- **Notes:** AJAX handler with no session check and SQL injection vulnerability; processes payroll

---

### 10. **includes/ajaxFile/OthersAjax/EmployeesAjax/ajaxEmployeeSelect.php**
- **File Path:** [includes/ajaxFile/OthersAjax/EmployeesAjax/ajaxEmployeeSelect.php](includes/ajaxFile/OthersAjax/EmployeesAjax/ajaxEmployeeSelect.php)
- **session_check.php Status:** ❌ **NOT INCLUDED**
- **SELECT Queries from employees table:**
  1. Line 4: `SELECT * FROM 'employees' WHERE 'status'=1 ORDER BY 'name' REGEXP '^[^A-Za-z]' ASC, 'name'`
- **Company Filtering:** ❌ **NOT APPLIED**
- **Query Type:** mysqli
- **Notes:** AJAX endpoint with no session check; returns all active employees globally

---

### 11. **includes/ajaxFile/ajaxEmployeeSelect.php** *(Note: Different from above)*
- **File Path:** [includes/ajaxFile/ajaxEmployeeSelect.php](includes/ajaxFile/ajaxEmployeeSelect.php)
- **session_check.php Status:** ❌ **NOT INCLUDED**
- **SELECT Queries from employees table:**
  1. Line 9: `SELECT * FROM 'employees' WHERE 'status'=1 {$company_filter} ORDER BY 'name' REGEXP '^[^A-Za-z]' ASC, 'name'`
- **Company Filtering:** ✅ **PARTIALLY APPLIED** - Variable `$company_filter` is used but origin needs verification
- **Query Type:** mysqli (uses variable filter)
- **Notes:** Has company_filter variable but implementation unclear; still lacks session check

---

### 12. **includes/MainClass.php**
- **File Path:** [includes/MainClass.php](includes/MainClass.php)
- **session_check.php Status:** ❌ **NOT INCLUDED** (Class file, may not need it)
- **SELECT Queries from employees table:**
  1. Line 218: `SELECT * FROM 'employees' WHERE 'emp_id' = ? AND 'status' = 1` (prepared statement)
- **Company Filtering:** ❌ **NOT APPLIED**
- **Query Type:** mysqli (prepared statement - GOOD)
- **Notes:** Uses prepared statement but no company filtering; likely called from other files

---

### 13. **payroll/2/api/get_employees.php**
- **File Path:** [payroll/2/api/get_employees.php](payroll/2/api/get_employees.php)
- **session_check.php Status:** ❌ **NOT INCLUDED**
- **SELECT Queries from employees table:**
  1. Lines 13-20: Complex PDO query with JOINs:
     ```sql
     SELECT e.id, e.name, e.emp_id, e.salary, e.dept, es.basic, es.housing..., 
            gp.status AS payroll_status, d.dep_nme, e.country, s.sponsor
     FROM employees e
     LEFT JOIN emp_salary es ON e.emp_id = es.emp_id
     LEFT JOIN generated_payrolls gp ON e.emp_id = gp.emp_id AND gp.month_year = :month_year_param
     LEFT JOIN department d ON e.dept = d.id
     LEFT JOIN sponsorship s ON e.emp_sup_type = s.id
     ORDER BY e.dept, e.name
     ```
- **Company Filtering:** ❌ **NOT APPLIED**
- **Query Type:** PDO (prepared statement with parameters - GOOD security pattern)
- **Notes:** Modern PDO implementation but no company filtering for multi-company setup

---

### 14. **payroll/1/payroll.php**
- **File Path:** [payroll/1/payroll.php](payroll/1/payroll.php)
- **session_check.php Status:** ❌ **NOT INCLUDED**
- **SELECT Queries from employees table:**
  - Not directly querying employees table in visible lines (relies on functions)
  - Related: Queries `emp_salary`, `payroll_benefits`, `payroll_deductions` tables
- **Company Filtering:** ❌ **NOT APPLIED**
- **Query Type:** mysqli (prepared statements for salary queries)
- **Notes:** Payroll system - processes employee salary/deductions without company context

---

## Summary Table

| # | File | session_check | SELECT Queries | Company Filter | Risk Level |
|---|------|:---:|:---:|:---:|:---:|
| 1 | search.php | ✅ | 3 | ⚠️ Partial | 🟡 Medium |
| 2 | vacation_details_sch.php | ❌ | 3 | ❌ | 🔴 Critical |
| 3 | find_birthday.php | ✅ | 1 | ❌ | 🟡 Medium |
| 4 | employee_salary_report.php | ✅ | 1 (complex) | ⚠️ Partial | 🟡 Medium |
| 5 | add_new_employee.php | ✅ | 9 | ❌ | 🟡 Medium |
| 6 | view_general_request.php | ✅ | 2 | ❌ | 🟡 Medium |
| 7 | all_employee_salary_list.php | ✅ | 1 | ❌ | 🟡 Medium |
| 8 | includes/sup_emp_view.php | ❌ | 1 | ⚠️ Partial | 🟡 Medium |
| 9 | includes/ajaxFile/ajaxSalary.php | ❌ | 1 | ❌ | 🔴 Critical |
| 10 | includes/ajaxFile/OthersAjax/EmployeesAjax/ajaxEmployeeSelect.php | ❌ | 1 | ❌ | 🟡 Medium |
| 11 | includes/ajaxFile/ajaxEmployeeSelect.php | ❌ | 1 | ⚠️ Partial | 🟡 Medium |
| 12 | includes/MainClass.php | ❌ | 1 | ❌ | 🟡 Medium |
| 13 | payroll/2/api/get_employees.php | ❌ | 1 (complex) | ❌ | 🟡 Medium |
| 14 | payroll/1/payroll.php | ❌ | 0 (indirect) | ❌ | 🟡 Medium |

---

## Key Findings

### 🔴 CRITICAL Issues
1. **vacation_details_sch.php** - No session check + SQL injection vulnerabilities
2. **includes/ajaxFile/ajaxSalary.php** - No session check + SQL injection in POST parameter

### 🟡 HIGH Priority Issues
1. **No company filtering in any file** - All 14 files lack multi-company data isolation
2. **Inconsistent session_check inclusion** - AJAX files particularly vulnerable
3. **Mix of security patterns** - Some use prepared statements, others use string concatenation

### ⚠️ MEDIUM Issues
1. Department-based filtering exists but not company-based
2. Some queries have company joins but no WHERE clause filtering
3. AJAX endpoints lack proper access control

---

## Recommended Actions (Priority Order)

1. **Immediate:** Add session_check to all AJAX files and vacation_details_sch.php
2. **High:** Add `WHERE e.comp_no = {$user_company}` to all SELECT queries
3. **High:** Convert all string-concatenated queries to prepared statements
4. **Medium:** Add access control checks to AJAX handlers
5. **Medium:** Implement company parameter validation from session data

