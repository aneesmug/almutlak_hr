-- =====================================================
-- Al-Mutlak HR System - Update User Types
-- Created: November 5, 2025
-- =====================================================

-- This script updates admin_login.user_type to assign specific roles
-- to employees based on your organizational structure.

-- =====================================================
-- STEP 1: Review Current User Types
-- =====================================================

SELECT 
    emp_id,
    fullname,
    user_type,
    dept,
    email
FROM admin_login
ORDER BY 
    CASE user_type
        WHEN 'administrator' THEN 1
        WHEN 'gm' THEN 2
        WHEN 'hr' THEN 3
        WHEN 'assistant' THEN 4
        WHEN 'dept_user' THEN 5
        WHEN 'employee' THEN 6
        ELSE 7
    END,
    fullname;

-- =====================================================
-- STEP 2: Available User Type Values
-- =====================================================

/*
Available user_type values (use these in admin_login table):

ADMINISTRATIVE:
  - 'administrator'      : Full system administrator
  - 'gm'                 : General Manager

HR DEPARTMENT:
  - 'hr_senior_bp'       : HR Senior Business Partner
  - 'hr_operations'      : HR Operations Manager
  - 'hr_supervisor'      : HR Supervisor
  - 'hr_recruitment'     : HR Recruitment Specialist
  - 'hr_payroll'         : HR Payroll Manager
  - 'hr'                 : HR Manager (legacy)
  
FINANCE & AUDIT:
  - 'finance_officer'    : Finance Officer
  - 'auditor'            : Internal Auditor
  - 'gr_officer'         : General Relations Officer

GENERAL:
  - 'dept_user'          : Department Manager
  - 'assistant'          : Assistant (role varies by department)
  - 'employee'           : Regular Employee
*/

-- =====================================================
-- STEP 3: Example Updates (Customize for Your Org)
-- =====================================================

-- UPDATE SPECIFIC USERS TO SPECIFIC ROLES
-- Uncomment and modify as needed:

/*
-- Set Administrator
UPDATE admin_login SET user_type = 'administrator' WHERE emp_id = '5430';

-- Set General Manager
UPDATE admin_login SET user_type = 'gm' WHERE emp_id = '3928';

-- Set HR Roles
UPDATE admin_login SET user_type = 'hr_senior_bp' WHERE emp_id = '5455';
UPDATE admin_login SET user_type = 'hr_operations' WHERE emp_id = '5423';
UPDATE admin_login SET user_type = 'hr_supervisor' WHERE emp_id = '5408';
UPDATE admin_login SET user_type = 'hr_recruitment' WHERE emp_id = '5115';
UPDATE admin_login SET user_type = 'hr_payroll' WHERE emp_id = '3431';

-- Set Finance & Audit Roles
UPDATE admin_login SET user_type = 'finance_officer' WHERE emp_id = '3061';
UPDATE admin_login SET user_type = 'auditor' WHERE emp_id = '3332';
UPDATE admin_login SET user_type = 'gr_officer' WHERE emp_id = '5021';

-- Set Department Managers (update dept_user for all managers)
UPDATE admin_login al
JOIN employees e ON al.emp_id = e.emp_id
SET al.user_type = 'dept_user'
WHERE e.emp_type = 'Manager'
  AND al.emp_id NOT IN ('5430', '3928', '5455', '5423', '5408', '5115', '3431', '3061', '3332', '5021');

-- Set Regular Employees
UPDATE admin_login al
JOIN employees e ON al.emp_id = e.emp_id
SET al.user_type = 'employee'
WHERE e.emp_type = 'Supporter';
*/

-- =====================================================
-- STEP 4: Verify Updates
-- =====================================================

SELECT 
    user_type,
    COUNT(*) as count,
    GROUP_CONCAT(fullname ORDER BY fullname SEPARATOR ', ') as users
FROM admin_login
GROUP BY user_type
ORDER BY 
    CASE user_type
        WHEN 'administrator' THEN 1
        WHEN 'gm' THEN 2
        WHEN 'hr_senior_bp' THEN 3
        WHEN 'hr_operations' THEN 4
        WHEN 'hr_supervisor' THEN 5
        WHEN 'hr_recruitment' THEN 6
        WHEN 'hr_payroll' THEN 7
        WHEN 'hr' THEN 8
        WHEN 'finance_officer' THEN 9
        WHEN 'auditor' THEN 10
        WHEN 'gr_officer' THEN 11
        WHEN 'dept_user' THEN 12
        WHEN 'assistant' THEN 13
        WHEN 'employee' THEN 14
        ELSE 15
    END;

-- =====================================================
-- STEP 5: Check Role Assignments
-- =====================================================

SELECT 
    al.emp_id,
    al.fullname,
    al.user_type,
    al.dept,
    d.name_en as department,
    CASE 
        WHEN al.user_type = 'administrator' THEN 'Administrator'
        WHEN al.user_type = 'gm' THEN 'GM'
        WHEN al.user_type = 'hr_senior_bp' THEN 'HR_Senior_BP'
        WHEN al.user_type = 'hr_operations' THEN 'HR_Operations'
        WHEN al.user_type = 'hr_supervisor' THEN 'HR_Supervisor'
        WHEN al.user_type = 'hr_recruitment' THEN 'HR_Recruitment'
        WHEN al.user_type = 'hr_payroll' THEN 'HR_Payroll'
        WHEN al.user_type = 'gr_officer' THEN 'GR_Officer'
        WHEN al.user_type = 'finance_officer' THEN 'Finance_Officer'
        WHEN al.user_type = 'auditor' THEN 'Auditor'
        WHEN al.user_type = 'hr' THEN 'HR_Manager'
        WHEN al.user_type = 'assistant' AND al.dept = 5 THEN 'HR_Assistant'
        WHEN al.user_type = 'assistant' AND al.dept = 2 THEN 'Finance_Assistant'
        WHEN al.user_type = 'assistant' AND al.dept = 6 THEN 'IT_Assistant'
        WHEN al.user_type = 'assistant' THEN 'Assistant'
        WHEN al.user_type = 'dept_user' THEN 'DPT_Manager'
        WHEN al.user_type = 'employee' THEN 'Employee'
        ELSE 'Unknown'
    END as assigned_role
FROM admin_login al
LEFT JOIN department d ON al.dept = d.id
ORDER BY assigned_role, al.fullname;

-- =====================================================
-- STEP 6: Add New User Type (Example)
-- =====================================================

/*
To add a new user to a specific role:

-- Example 1: Add new HR Operations user
UPDATE admin_login 
SET user_type = 'hr_operations' 
WHERE emp_id = 'YOUR_EMP_ID';

-- Example 2: Add new Finance Officer
UPDATE admin_login 
SET user_type = 'finance_officer' 
WHERE emp_id = 'YOUR_EMP_ID';

-- Example 3: Promote employee to Department Manager
UPDATE admin_login 
SET user_type = 'dept_user' 
WHERE emp_id = 'YOUR_EMP_ID';
*/

-- =====================================================
-- IMPORTANT NOTES:
-- =====================================================

/*
1. BACKUP FIRST: Always backup your admin_login table before making updates
   
   CREATE TABLE admin_login_backup_20251105 AS SELECT * FROM admin_login;

2. TEST CHANGES: After updating, login with each role to verify permissions

3. DEPARTMENT-BASED ROLES: 
   - 'assistant' role behavior changes based on dept field:
     * dept = 5 → HR_Assistant
     * dept = 2 → Finance_Assistant
     * dept = 6 → IT_Assistant

4. NO CODE CHANGES NEEDED: Once user_type is updated in database,
   the role system automatically applies the correct permissions

5. ROLE HIERARCHY: The system respects this priority:
   - user_type mapping (highest)
   - dept_user or emp_type = 'Manager' → DPT_Manager
   - employees.emp_type → Manager/Supporter → DPT_Manager/Employee
*/

-- =====================================================
-- End of Update Script
-- =====================================================
