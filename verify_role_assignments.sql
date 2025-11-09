-- =====================================================
-- Al-Mutlak HR System - Role Assignment Verification
-- Created: November 5, 2025
-- =====================================================

-- This script helps verify that employee records match the
-- new role assignment system

-- =====================================================
-- 1. Check Specific Role Assignments
-- =====================================================

SELECT 
    e.emp_id,
    e.name,
    al.user_type,
    al.emp_type,
    al.dept,
    d.name_en as department_name,
    e.emp_type as employee_type,
    CASE 
        WHEN e.emp_id = '5430' THEN 'Administrator'
        WHEN e.emp_id = '3928' THEN 'GM'
        WHEN e.emp_id = '5455' THEN 'HR_Senior_BP'
        WHEN e.emp_id = '5423' THEN 'HR_Operations'
        WHEN e.emp_id = '5408' THEN 'HR_Supervisor'
        WHEN e.emp_id = '5115' THEN 'HR_Recruitment'
        WHEN e.emp_id = '3431' THEN 'HR_Payroll'
        WHEN e.emp_id = '5021' THEN 'GR_Officer'
        WHEN e.emp_id = '3061' THEN 'Finance_Officer'
        WHEN e.emp_id = '3332' THEN 'Auditor'
        WHEN e.emp_type = 'Manager' THEN 'DPT_Manager'
        WHEN e.emp_type = 'Supporter' THEN 'Employee'
        ELSE 'Not Assigned'
    END as assigned_role
FROM employees e
LEFT JOIN admin_login al ON e.emp_id = al.emp_id
LEFT JOIN department d ON e.dept = d.id
WHERE e.emp_id IN ('5430', '3928', '5455', '5423', '5408', '5115', '3431', '5021', '3061', '3332')
ORDER BY e.emp_id;

-- =====================================================
-- 2. Check All Department Managers
-- =====================================================

SELECT 
    e.emp_id,
    e.name,
    e.emp_type,
    e.dept,
    d.name_en as department_name,
    al.user_type,
    'DPT_Manager' as assigned_role
FROM employees e
LEFT JOIN admin_login al ON e.emp_id = al.emp_id
LEFT JOIN department d ON e.dept = d.id
WHERE e.emp_type = 'Manager'
  AND e.emp_id NOT IN ('5430', '3928', '5455', '5423', '5408', '5115', '3431', '5021', '3061', '3332')
ORDER BY e.dept, e.name;

-- =====================================================
-- 3. Check All Regular Employees (Supporters)
-- =====================================================

SELECT 
    e.emp_id,
    e.name,
    e.emp_type,
    e.dept,
    d.name_en as department_name,
    al.user_type,
    'Employee' as assigned_role
FROM employees e
LEFT JOIN admin_login al ON e.emp_id = al.emp_id
LEFT JOIN department d ON e.dept = d.id
WHERE e.emp_type = 'Supporter'
ORDER BY e.dept, e.name
LIMIT 20;

-- =====================================================
-- 4. Verify HR Department Structure
-- =====================================================

SELECT 
    e.emp_id,
    e.name,
    al.user_type,
    al.emp_type,
    CASE 
        WHEN e.emp_id = '5455' THEN 'HR_Senior_BP'
        WHEN e.emp_id = '5423' THEN 'HR_Operations'
        WHEN e.emp_id = '5408' THEN 'HR_Supervisor'
        WHEN e.emp_id = '5115' THEN 'HR_Recruitment'
        WHEN e.emp_id = '3431' THEN 'HR_Payroll'
        WHEN al.user_type = 'assistant' AND e.dept = 5 THEN 'HR_Assistant (Legacy)'
        ELSE 'Other HR'
    END as hr_role
FROM employees e
JOIN admin_login al ON e.emp_id = al.emp_id
WHERE e.dept = 5
ORDER BY 
    CASE e.emp_id
        WHEN '5455' THEN 1
        WHEN '5408' THEN 2
        WHEN '5423' THEN 3
        WHEN '5115' THEN 4
        WHEN '3431' THEN 5
        ELSE 6
    END;

-- =====================================================
-- 5. Verify Finance Department Structure
-- =====================================================

SELECT 
    e.emp_id,
    e.name,
    al.user_type,
    al.emp_type,
    CASE 
        WHEN e.emp_id = '3061' THEN 'Finance_Officer'
        WHEN e.emp_id = '3332' THEN 'Auditor'
        WHEN e.emp_type = 'Manager' AND e.dept = 2 THEN 'Finance_Manager (Legacy)'
        WHEN al.user_type = 'assistant' AND e.dept = 2 THEN 'Finance_Assistant (Legacy)'
        ELSE 'Other Finance'
    END as finance_role
FROM employees e
JOIN admin_login al ON e.emp_id = al.emp_id
WHERE e.dept = 2
ORDER BY e.emp_id;

-- =====================================================
-- 6. Check IT Department
-- =====================================================

SELECT 
    e.emp_id,
    e.name,
    al.user_type,
    al.emp_type,
    CASE 
        WHEN e.emp_id = '5127' THEN 'IT_Assistant'
        ELSE 'Other IT'
    END as it_role
FROM employees e
JOIN admin_login al ON e.emp_id = al.emp_id
WHERE e.dept = 6
ORDER BY e.emp_id;

-- =====================================================
-- 7. Summary Count by Role
-- =====================================================

SELECT 
    CASE 
        WHEN e.emp_id = '5430' THEN 'Administrator'
        WHEN e.emp_id = '3928' THEN 'GM'
        WHEN e.emp_id = '5455' THEN 'HR_Senior_BP'
        WHEN e.emp_id = '5423' THEN 'HR_Operations'
        WHEN e.emp_id = '5408' THEN 'HR_Supervisor'
        WHEN e.emp_id = '5115' THEN 'HR_Recruitment'
        WHEN e.emp_id = '3431' THEN 'HR_Payroll'
        WHEN e.emp_id = '5021' THEN 'GR_Officer'
        WHEN e.emp_id = '3061' THEN 'Finance_Officer'
        WHEN e.emp_id = '3332' THEN 'Auditor'
        WHEN e.emp_type = 'Manager' THEN 'DPT_Manager'
        WHEN e.emp_type = 'Supporter' THEN 'Employee'
        ELSE 'Not Assigned'
    END as role_name,
    COUNT(*) as count
FROM employees e
GROUP BY role_name
ORDER BY 
    CASE role_name
        WHEN 'Administrator' THEN 1
        WHEN 'GM' THEN 2
        WHEN 'HR_Senior_BP' THEN 3
        WHEN 'HR_Supervisor' THEN 4
        WHEN 'HR_Operations' THEN 5
        WHEN 'HR_Recruitment' THEN 6
        WHEN 'HR_Payroll' THEN 7
        WHEN 'Finance_Officer' THEN 8
        WHEN 'Auditor' THEN 9
        WHEN 'GR_Officer' THEN 10
        WHEN 'DPT_Manager' THEN 11
        WHEN 'Employee' THEN 12
        ELSE 13
    END;

-- =====================================================
-- 8. Find Users Without admin_login Records
-- =====================================================

SELECT 
    e.emp_id,
    e.name,
    e.emp_type,
    e.dept,
    d.name_en as department_name
FROM employees e
LEFT JOIN admin_login al ON e.emp_id = al.emp_id
LEFT JOIN department d ON e.dept = d.id
WHERE al.emp_id IS NULL
ORDER BY e.emp_type DESC, e.dept, e.name
LIMIT 20;

-- =====================================================
-- 9. Update Recommendations (Run if needed)
-- =====================================================

-- These are example queries to update employee records if needed
-- DO NOT RUN without verifying the data first!

/*
-- Example: Update employee type for specific users
UPDATE employees SET emp_type = 'Manager' WHERE emp_id IN ('5455', '5408', '5423', '5115', '3431', '3061', '3332', '5021');

-- Example: Update employee type for supporters
UPDATE employees SET emp_type = 'Supporter' WHERE emp_type = 'Employee' OR emp_type IS NULL;

-- Example: Verify department assignments
UPDATE employees SET dept = 5 WHERE emp_id IN ('5455', '5408', '5423', '5115', '3431');
UPDATE employees SET dept = 2 WHERE emp_id IN ('3061', '3332');
*/

-- =====================================================
-- End of Verification Script
-- =====================================================
