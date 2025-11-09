-- ========================================================================
-- ALTER admin_login.user_type ENUM FOR DEPARTMENT-BASED ROLE SYSTEM
-- ========================================================================
-- This script updates the user_type column to include all required values
-- for the new department-based role system (role_check.php v015)
-- 
-- BEFORE RUNNING: Backup your database!
-- mysqldump -u root almutlak_db > backup_before_enum_update.sql
-- ========================================================================

-- Step 1: Check current ENUM definition
SHOW COLUMNS FROM `admin_login` LIKE 'user_type';

-- Step 2: Alter the user_type column to include all new role types
ALTER TABLE `admin_login` 
MODIFY COLUMN `user_type` ENUM(
    'administrator',     -- System Administrator (full access)
    'gm',               -- General Manager
    'hr_senior_bp',     -- HR Senior Business Partner
    'hr_operations',    -- HR Operations
    'hr_supervisor',    -- HR Supervisor
    'hr_recruitment',   -- HR Recruitment
    'hr_payroll',       -- HR Payroll
    'finance_officer',  -- Finance Officer
    'auditor',          -- Auditor
    'gr_officer',       -- Government Relations Officer
    'dept_user',        -- Department User (role assigned by department)
    'employee',         -- Regular Employee (default)
    'hr',               -- Legacy HR Manager (keep for backward compatibility)
    'it',               -- Legacy IT role (keep for backward compatibility)
    'finance',          -- Legacy Finance role (keep for backward compatibility)
    'assistant'         -- Legacy Assistant role (deprecated, keep for migration)
) 
DEFAULT 'employee' 
COMMENT 'User role type - determines access permissions based on department and role';

-- Step 3: Verify the change
SHOW COLUMNS FROM `admin_login` LIKE 'user_type';

-- Step 4: Check for any invalid user_type values before the change
-- Run this BEFORE Step 2 to identify users that need manual updating
SELECT 
    emp_id, 
    id_iqama, 
    user_type,
    'Will be set to DEFAULT (employee)' as action_needed
FROM `admin_login` 
WHERE user_type NOT IN (
    'administrator', 'gm', 'hr_senior_bp', 'hr_operations', 'hr_supervisor',
    'hr_recruitment', 'hr_payroll', 'finance_officer', 'auditor', 'gr_officer',
    'dept_user', 'employee', 'hr', 'it', 'finance', 'assistant'
)
OR user_type IS NULL;

-- ========================================================================
-- RECOMMENDED: MIGRATE LEGACY VALUES TO NEW SYSTEM
-- ========================================================================

-- Migrate legacy 'hr' to 'hr_senior_bp' or 'dept_user'
-- UPDATE admin_login 
-- SET user_type = 'hr_senior_bp', dept = 5 
-- WHERE user_type = 'hr';

-- Migrate legacy 'it' to 'administrator' or 'dept_user'
-- UPDATE admin_login 
-- SET user_type = 'administrator', dept = 6 
-- WHERE user_type = 'it';

-- Migrate legacy 'finance' to 'finance_officer'
-- UPDATE admin_login 
-- SET user_type = 'finance_officer', dept = 2 
-- WHERE user_type = 'finance';

-- Migrate legacy 'assistant' to department-based 'dept_user'
-- UPDATE admin_login 
-- SET user_type = 'dept_user', emp_type = 'Supporter' 
-- WHERE user_type = 'assistant';

-- ========================================================================
-- OPTIONAL: REMOVE LEGACY VALUES AFTER MIGRATION
-- ========================================================================
-- After migrating all users from legacy values, you can remove them:
/*
ALTER TABLE `admin_login` 
MODIFY COLUMN `user_type` ENUM(
    'administrator',
    'gm',
    'hr_senior_bp',
    'hr_operations',
    'hr_supervisor',
    'hr_recruitment',
    'hr_payroll',
    'finance_officer',
    'auditor',
    'gr_officer',
    'dept_user',
    'employee'
) 
DEFAULT 'employee';
*/

-- ========================================================================
-- VERIFICATION QUERIES
-- ========================================================================

-- Count users by user_type
SELECT 
    user_type,
    COUNT(*) as count,
    GROUP_CONCAT(emp_id ORDER BY emp_id SEPARATOR ', ') as employee_ids
FROM admin_login 
GROUP BY user_type 
ORDER BY 
    FIELD(user_type, 'administrator', 'gm', 'hr_senior_bp', 'hr_operations', 
          'hr_supervisor', 'hr_recruitment', 'hr_payroll', 'finance_officer', 
          'auditor', 'gr_officer', 'dept_user', 'employee', 'hr', 'it', 'finance', 'assistant');

-- View all users with their complete role information
SELECT 
    al.emp_id,
    al.id_iqama,
    al.user_type,
    al.dept,
    d.dep_nme,
    al.emp_type,
    e.emptype as employee_table_emp_type,
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
        WHEN al.user_type = 'hr' THEN 'HR_Manager (LEGACY)'
        WHEN al.user_type = 'it' THEN 'IT (LEGACY)'
        WHEN al.user_type = 'finance' THEN 'Finance (LEGACY)'
        WHEN al.user_type = 'assistant' THEN 'Assistant (DEPRECATED)'
        ELSE 'Employee'
    END as assigned_role
FROM admin_login al
LEFT JOIN department d ON al.dept = d.id
LEFT JOIN employees e ON al.emp_id = e.emp_id
ORDER BY 
    FIELD(al.user_type, 'administrator', 'gm', 'hr_senior_bp', 'hr_operations', 
          'hr_supervisor', 'hr_recruitment', 'hr_payroll', 'finance_officer', 
          'auditor', 'gr_officer', 'dept_user', 'employee', 'hr', 'it', 'finance', 'assistant'),
    al.dept,
    al.emp_type DESC;

-- ========================================================================
-- ENUM VALUES REFERENCE
-- ========================================================================
/*
PRIMARY VALUES (New System):
- administrator     → Administrator role (full system access)
- gm                → General Manager
- hr_senior_bp      → HR Senior Business Partner
- hr_operations     → HR Operations
- hr_supervisor     → HR Supervisor
- hr_recruitment    → HR Recruitment
- hr_payroll        → HR Payroll
- finance_officer   → Finance Officer
- auditor           → Auditor
- gr_officer        → Government Relations Officer
- dept_user         → Department-based role (requires dept + emp_type)
- employee          → Regular Employee (default)

LEGACY VALUES (For Backward Compatibility):
- hr                → Legacy HR Manager (migrate to hr_senior_bp or dept_user)
- it                → Legacy IT role (migrate to administrator or dept_user)
- finance           → Legacy Finance (migrate to finance_officer)
- assistant         → Deprecated (migrate to dept_user with appropriate dept)

DEPARTMENT MAPPING (when user_type = 'dept_user'):
- dept = 5  → HR_Team / HR_Team_Manager
- dept = 2  → Finance_Team / Finance_Team_Manager
- dept = 6  → IT_Team / IT_Team_Manager
- dept = 10 → Executive_Team / Executive_Team_Manager
- Other     → DPT_Manager (if Manager) or Employee

emp_type VALUES (for dept_user):
- Manager   → Adds '_Manager' suffix to department role
- Supporter → Regular team member role
*/
