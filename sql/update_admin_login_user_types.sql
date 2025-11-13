-- ========================================================================
-- UPDATE admin_login.user_type FOR DEPARTMENT-BASED ROLE SYSTEM
-- ========================================================================
-- This script updates user_type values in admin_login table to work with
-- the new department-based role system (role_check.php v015)
-- 
-- BEFORE RUNNING: Backup your database!
-- mysqldump -u root almutlak_db > backup_before_role_update.sql
-- ========================================================================

-- Step 1: View current user_type distribution
-- Run this first to see what needs updating
SELECT 
    user_type,
    COUNT(*) as count,
    GROUP_CONCAT(emp_id ORDER BY emp_id SEPARATOR ', ') as employee_ids
FROM admin_login 
GROUP BY user_type 
ORDER BY count DESC;

-- ========================================================================
-- ADMINISTRATOR ROLE
-- ========================================================================
-- Set administrator for system admins (usually IT/System administrators)
-- UPDATE admin_login 
-- SET user_type = 'administrator' 
-- WHERE emp_id IN ('YOUR_ADMIN_EMP_IDS');

-- Example:
-- UPDATE admin_login SET user_type = 'administrator' WHERE emp_id = '5430';

-- ========================================================================
-- GENERAL MANAGER
-- ========================================================================
-- Set GM for General Manager
-- UPDATE admin_login 
-- SET user_type = 'gm' 
-- WHERE emp_id IN ('YOUR_GM_EMP_IDS');

-- Example:
-- UPDATE admin_login SET user_type = 'gm' WHERE emp_id = '3928';

-- ========================================================================
-- HR DEPARTMENT ROLES (dept = 5)
-- ========================================================================

-- HR Senior Business Partner
-- UPDATE admin_login 
-- SET user_type = 'hr_senior_bp', dept = 5 
-- WHERE emp_id IN ('YOUR_HR_SENIOR_BP_EMP_IDS');

-- Example:
-- UPDATE admin_login SET user_type = 'hr_senior_bp', dept = 5 WHERE emp_id = '5455';

-- HR Operations
-- UPDATE admin_login 
-- SET user_type = 'hr_operations', dept = 5 
-- WHERE emp_id IN ('YOUR_HR_OPERATIONS_EMP_IDS');

-- HR Supervisor
-- UPDATE admin_login 
-- SET user_type = 'hr_supervisor', dept = 5 
-- WHERE emp_id IN ('YOUR_HR_SUPERVISOR_EMP_IDS');

-- HR Recruitment
-- UPDATE admin_login 
-- SET user_type = 'hr_recruitment', dept = 5 
-- WHERE emp_id IN ('YOUR_HR_RECRUITMENT_EMP_IDS');

-- HR Payroll
-- UPDATE admin_login 
-- SET user_type = 'hr_payroll', dept = 5 
-- WHERE emp_id IN ('YOUR_HR_PAYROLL_EMP_IDS');

-- HR Team Members (no specific role, just department assignment)
-- These will get 'HR_Team' role automatically
-- UPDATE admin_login 
-- SET user_type = 'dept_user', dept = 5, emp_type = 'Supporter' 
-- WHERE emp_id IN ('YOUR_HR_TEAM_MEMBER_IDS');

-- HR Team Manager (department-based manager)
-- UPDATE admin_login 
-- SET user_type = 'dept_user', dept = 5, emp_type = 'Manager' 
-- WHERE emp_id IN ('YOUR_HR_MANAGER_IDS');

-- ========================================================================
-- FINANCE DEPARTMENT ROLES (dept = 2)
-- ========================================================================

-- Finance Officer
-- UPDATE admin_login 
-- SET user_type = 'finance_officer', dept = 2 
-- WHERE emp_id IN ('YOUR_FINANCE_OFFICER_EMP_IDS');

-- Auditor
-- UPDATE admin_login 
-- SET user_type = 'auditor', dept = 2 
-- WHERE emp_id IN ('YOUR_AUDITOR_EMP_IDS');

-- Finance Team Members
-- UPDATE admin_login 
-- SET user_type = 'dept_user', dept = 2, emp_type = 'Supporter' 
-- WHERE emp_id IN ('YOUR_FINANCE_TEAM_MEMBER_IDS');

-- Finance Team Manager
-- UPDATE admin_login 
-- SET user_type = 'dept_user', dept = 2, emp_type = 'Manager' 
-- WHERE emp_id IN ('YOUR_FINANCE_MANAGER_IDS');

-- ========================================================================
-- GR (GOVERNMENT RELATIONS) ROLE
-- ========================================================================

-- GR Officer
-- UPDATE admin_login 
-- SET user_type = 'gr_officer' 
-- WHERE emp_id IN ('YOUR_GR_OFFICER_EMP_IDS');

-- ========================================================================
-- IT DEPARTMENT ROLES (dept = 6)
-- ========================================================================

-- IT Team Members
-- UPDATE admin_login 
-- SET user_type = 'dept_user', dept = 6, emp_type = 'Supporter' 
-- WHERE emp_id IN ('YOUR_IT_TEAM_MEMBER_IDS');

-- IT Team Manager
-- UPDATE admin_login 
-- SET user_type = 'dept_user', dept = 6, emp_type = 'Manager' 
-- WHERE emp_id IN ('YOUR_IT_MANAGER_IDS');

-- ========================================================================
-- EXECUTIVE DEPARTMENT ROLES (dept = 10)
-- ========================================================================

-- Executive Team Members
-- UPDATE admin_login 
-- SET user_type = 'dept_user', dept = 10, emp_type = 'Supporter' 
-- WHERE emp_id IN ('YOUR_EXECUTIVE_TEAM_MEMBER_IDS');

-- Executive Team Manager
-- UPDATE admin_login 
-- SET user_type = 'dept_user', dept = 10, emp_type = 'Manager' 
-- WHERE emp_id IN ('YOUR_EXECUTIVE_MANAGER_IDS');

-- ========================================================================
-- OTHER DEPARTMENT MANAGERS
-- ========================================================================

-- For managers in other departments (will get 'DPT_Manager' role)
-- UPDATE admin_login 
-- SET user_type = 'dept_user', emp_type = 'Manager' 
-- WHERE emp_id IN ('YOUR_OTHER_MANAGER_IDS');

-- ========================================================================
-- REGULAR EMPLOYEES
-- ========================================================================

-- Set regular employees (no special access)
-- UPDATE admin_login 
-- SET user_type = 'employee', emp_type = 'Supporter' 
-- WHERE emp_id IN ('YOUR_REGULAR_EMPLOYEE_IDS');

-- Or update all users without specific roles to 'employee'
-- UPDATE admin_login 
-- SET user_type = 'employee' 
-- WHERE user_type IS NULL 
--    OR user_type = '' 
--    OR user_type NOT IN ('administrator', 'gm', 'hr_senior_bp', 'hr_operations', 
--                         'hr_supervisor', 'hr_recruitment', 'hr_payroll', 
--                         'finance_officer', 'auditor', 'gr_officer', 'dept_user');

-- ========================================================================
-- VERIFICATION QUERIES
-- ========================================================================

-- Check all users and their assigned roles
SELECT 
    al.emp_id,
    al.id_iqama,
    al.user_type,
    al.dept,
    al.emp_type,
    d.department_name,
    CASE 
        WHEN al.user_type = 'administrator' THEN 'Administrator'
        WHEN al.user_type = 'gm' THEN 'GM'
        WHEN al.user_type = 'hr_senior_bp' THEN 'HR_Senior_BP'
        WHEN al.user_type = 'hr_operations' THEN 'HR_Operations'
        WHEN al.user_type = 'hr_supervisor' THEN 'HR_Supervisor'
        WHEN al.user_type = 'hr_recruitment' THEN 'HR_Recruitment'
        WHEN al.user_type = 'hr_payroll' THEN 'HR_Payroll'
        WHEN al.user_type = 'finance_officer' THEN 'Finance_Officer'
        WHEN al.user_type = 'auditor' THEN 'Auditor'
        WHEN al.user_type = 'gr_officer' THEN 'GR_Officer'
        WHEN al.user_type = 'dept_user' AND al.emp_type = 'Manager' AND al.dept = 5 THEN 'HR_Team_Manager'
        WHEN al.user_type = 'dept_user' AND al.emp_type = 'Manager' AND al.dept = 2 THEN 'Finance_Team_Manager'
        WHEN al.user_type = 'dept_user' AND al.emp_type = 'Manager' AND al.dept = 6 THEN 'IT_Team_Manager'
        WHEN al.user_type = 'dept_user' AND al.emp_type = 'Manager' AND al.dept = 10 THEN 'Executive_Team_Manager'
        WHEN al.user_type = 'dept_user' AND al.emp_type = 'Manager' THEN 'DPT_Manager'
        WHEN al.user_type = 'dept_user' AND al.dept = 5 THEN 'HR_Team'
        WHEN al.user_type = 'dept_user' AND al.dept = 2 THEN 'Finance_Team'
        WHEN al.user_type = 'dept_user' AND al.dept = 6 THEN 'IT_Team'
        WHEN al.user_type = 'dept_user' AND al.dept = 10 THEN 'Executive_Team'
        WHEN al.dept = 5 THEN 'HR_Team'
        WHEN al.dept = 2 THEN 'Finance_Team'
        WHEN al.dept = 6 THEN 'IT_Team'
        WHEN al.dept = 10 THEN 'Executive_Team'
        ELSE 'Employee'
    END as assigned_role
FROM admin_login al
LEFT JOIN departments d ON al.dept = d.id
ORDER BY 
    FIELD(al.user_type, 'administrator', 'gm', 'hr_senior_bp', 'hr_operations', 
          'hr_supervisor', 'hr_recruitment', 'hr_payroll', 'finance_officer', 
          'auditor', 'gr_officer', 'dept_user', 'employee'),
    al.dept,
    al.emp_type DESC;

-- Count users by department and role
SELECT 
    d.department_name,
    al.user_type,
    al.emp_type,
    COUNT(*) as user_count
FROM admin_login al
LEFT JOIN departments d ON al.dept = d.id
GROUP BY d.department_name, al.user_type, al.emp_type
ORDER BY d.department_name, al.user_type;

-- Find users with missing or invalid data
SELECT 
    emp_id,
    id_iqama,
    user_type,
    dept,
    emp_type,
    CASE 
        WHEN user_type IS NULL THEN 'Missing user_type'
        WHEN user_type = '' THEN 'Empty user_type'
        WHEN dept IS NULL THEN 'Missing department'
        WHEN emp_type IS NULL THEN 'Missing emp_type'
        WHEN emp_type = '' THEN 'Empty emp_type'
        ELSE 'Other issue'
    END as issue
FROM admin_login
WHERE user_type IS NULL 
   OR user_type = '' 
   OR (user_type = 'dept_user' AND (dept IS NULL OR dept = ''))
   OR (user_type = 'dept_user' AND (emp_type IS NULL OR emp_type = ''));

-- ========================================================================
-- ROLE SYSTEM SUMMARY
-- ========================================================================
/*
AVAILABLE user_type VALUES:
- administrator    → Administrator (full system access)
- gm              → GM (General Manager)
- hr_senior_bp    → HR_Senior_BP (HR Senior Business Partner)
- hr_operations   → HR_Operations (HR Operations)
- hr_supervisor   → HR_Supervisor (HR Supervisor)
- hr_recruitment  → HR_Recruitment (HR Recruitment)
- hr_payroll      → HR_Payroll (HR Payroll)
- finance_officer → Finance_Officer (Finance Officer)
- auditor         → Auditor (Auditor)
- gr_officer      → GR_Officer (Government Relations Officer)
- dept_user       → Department-based role (requires dept and emp_type)
- employee        → Employee (default/basic access)

DEPARTMENT IDs:
- 5  → HR Department
- 2  → Finance Department
- 6  → IT Department
- 10 → Executive/GM Department

emp_type VALUES:
- Manager    → Gets '_Manager' suffix for department roles
- Supporter  → Regular team member

ROLE ASSIGNMENT LOGIC:
1. Specific user_type (administrator, gm, hr_senior_bp, etc.) → Direct role
2. dept_user + Manager + dept 5 → HR_Team_Manager
3. dept_user + Supporter + dept 5 → HR_Team
4. No specific role + dept 5 → HR_Team (fallback)
5. Default → Employee

PERMISSION VARIABLES IN session_check.php:
- $is_system_admin (administrator)
- $isGM (gm)
- $isHR_Senior_BP (hr_senior_bp)
- $isHR_Operations (hr_operations)
- $isHR_Supervisor (hr_supervisor)
- $isHR_Recruitment (hr_recruitment)
- $isHR_Payroll (hr_payroll)
- $isFinance_Officer (finance_officer)
- $isAuditor (auditor)
- $isGR_Officer (gr_officer)
- $isHR (combines all HR roles + dept 5)
- $isDeptHr (anyone in dept 5)
- $isFinance (Finance Officer + Auditor + dept 2)
- $isDeptFinance (anyone in dept 2)
- $isItTeam (anyone in dept 6)
*/
