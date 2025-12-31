-- ================================================================
-- COMPANY ACCESS CONTROL - DATABASE REFERENCE
-- Al-Mutlak WMS
-- Date: December 30, 2025
-- ================================================================

-- ================================================================
-- 1. VERIFY DATABASE SCHEMA
-- ================================================================

-- Check if allowed_companies column exists:
DESC admin_login;

-- Expected output should show:
-- | allowed_companies | json | YES | | NULL |

-- ================================================================
-- 2. COMPANY STRUCTURE
-- ================================================================

-- View all companies in the system:
SELECT comp_id, comp_name FROM companies ORDER BY comp_id;

-- ================================================================
-- 3. USER COMPANY ACCESS EXAMPLES
-- ================================================================

-- Example 1: System Admin (Full Access) - allowed_companies is NULL
SELECT 
    al.id, 
    al.id_iqama, 
    e.name,
    al.user_type,
    al.allowed_companies,
    CASE WHEN al.allowed_companies IS NULL THEN 'Full Access'
         ELSE JSON_LENGTH(al.allowed_companies) END as company_count
FROM admin_login al
LEFT JOIN employees e ON al.emp_id = e.emp_id
WHERE al.user_type = 'administrator';

-- Example 2: User with Company Restrictions
SELECT 
    al.id,
    al.id_iqama,
    e.name,
    al.user_type,
    al.allowed_companies,
    JSON_LENGTH(al.allowed_companies) as accessible_companies
FROM admin_login al
LEFT JOIN employees e ON al.emp_id = e.emp_id
WHERE al.allowed_companies IS NOT NULL;

-- ================================================================
-- 4. SETTING COMPANY ACCESS FOR USERS
-- ================================================================

-- Grant full access to user (set allowed_companies to NULL):
UPDATE admin_login 
SET allowed_companies = NULL 
WHERE id = 5;

-- Grant access to specific companies (e.g., companies 1, 2, 5):
UPDATE admin_login 
SET allowed_companies = JSON_ARRAY(1, 2, 5) 
WHERE id = 15;

-- Grant access to single company:
UPDATE admin_login 
SET allowed_companies = JSON_ARRAY(2) 
WHERE id = 20;

-- Remove all company access (empty array - no access):
UPDATE admin_login 
SET allowed_companies = JSON_ARRAY() 
WHERE id = 25;

-- ================================================================
-- 5. EMPLOYEE COMPANY ASSIGNMENTS
-- ================================================================

-- View employees by company:
SELECT 
    e.emp_id,
    e.name,
    e.dept,
    c.comp_name,
    e.status
FROM employees e
LEFT JOIN companies c ON e.comp_no = c.comp_id
ORDER BY c.comp_name, e.name;

-- Count employees per company:
SELECT 
    COALESCE(c.comp_name, 'Unassigned') as company,
    COUNT(*) as employee_count,
    SUM(CASE WHEN e.status = 1 THEN 1 ELSE 0 END) as active_employees
FROM employees e
LEFT JOIN companies c ON e.comp_no = c.comp_id
GROUP BY e.comp_no
ORDER BY company;

-- ================================================================
-- 6. USER ACCESS VERIFICATION QUERIES
-- ================================================================

-- Check specific user's access level:
DELIMITER //
CREATE TEMPORARY PROCEDURE check_user_access(IN user_id INT)
BEGIN
    SELECT 
        al.id as user_id,
        al.id_iqama,
        e.name as user_name,
        al.user_type,
        CASE 
            WHEN al.allowed_companies IS NULL THEN 'FULL_ACCESS'
            WHEN JSON_LENGTH(al.allowed_companies) = 0 THEN 'NO_ACCESS'
            ELSE CONCAT('LIMITED_ACCESS (', JSON_LENGTH(al.allowed_companies), ' companies)')
        END as access_level,
        al.allowed_companies as accessible_companies_json,
        (SELECT GROUP_CONCAT(DISTINCT comp_name) 
         FROM companies 
         WHERE JSON_CONTAINS(al.allowed_companies, JSON_QUOTE(comp_id)) 
            OR al.allowed_companies IS NULL) as company_names
    FROM admin_login al
    LEFT JOIN employees e ON al.emp_id = e.emp_id
    WHERE al.id = user_id;
END //
DELIMITER ;

-- ================================================================
-- 7. AUDIT TRAIL - Track Company Access Changes
-- ================================================================

-- View admin_login audit log (if audit table exists):
SELECT * FROM admin_login_audit 
WHERE column_name = 'allowed_companies'
ORDER BY changed_at DESC
LIMIT 20;

-- ================================================================
-- 8. COMMON SCENARIOS
-- ================================================================

-- Scenario 1: Get all users who can access Company 1
SELECT 
    al.id,
    al.id_iqama,
    e.name,
    al.user_type
FROM admin_login al
LEFT JOIN employees e ON al.emp_id = e.emp_id
WHERE al.allowed_companies IS NULL  -- System admins
   OR JSON_CONTAINS(al.allowed_companies, JSON_QUOTE(1));

-- Scenario 2: Get all users with company restrictions (not full access)
SELECT 
    al.id,
    al.id_iqama,
    e.name,
    al.user_type,
    al.allowed_companies
FROM admin_login al
LEFT JOIN employees e ON al.emp_id = e.emp_id
WHERE al.allowed_companies IS NOT NULL;

-- Scenario 3: Get all system admins (full access)
SELECT 
    al.id,
    al.id_iqama,
    e.name,
    al.user_type
FROM admin_login al
LEFT JOIN employees e ON al.emp_id = e.emp_id
WHERE al.user_type = 'administrator'
   AND al.allowed_companies IS NULL;

-- Scenario 4: Find users who can only access one specific company
SELECT 
    al.id,
    al.id_iqama,
    e.name,
    al.user_type,
    JSON_UNQUOTE(JSON_EXTRACT(al.allowed_companies, '$[0]')) as allowed_company_id,
    c.comp_name
FROM admin_login al
LEFT JOIN employees e ON al.emp_id = e.emp_id
LEFT JOIN companies c ON JSON_UNQUOTE(JSON_EXTRACT(al.allowed_companies, '$[0]')) = c.comp_id
WHERE JSON_LENGTH(al.allowed_companies) = 1;

-- ================================================================
-- 9. MIGRATION & DATA FIXES
-- ================================================================

-- Initialize all admin users with full access:
UPDATE admin_login 
SET allowed_companies = NULL 
WHERE user_type IN ('administrator', 'gm');

-- Initialize department managers with their company only:
UPDATE admin_login al
INNER JOIN employees e ON al.emp_id = e.emp_id
SET al.allowed_companies = JSON_ARRAY(e.comp_no)
WHERE al.user_type IN ('dept_user', 'assistant')
  AND e.comp_no IS NOT NULL;

-- ================================================================
-- 10. PERFORMANCE OPTIMIZATION
-- ================================================================

-- Check for index on admin_login for JSON queries:
SHOW INDEX FROM admin_login;

-- If needed, add index (MySQL 5.7.8+):
-- ALTER TABLE admin_login ADD INDEX idx_allowed_companies 
--   ((CAST(allowed_companies AS UNSIGNED ARRAY)));

-- Or for better performance with LIKE queries:
-- ALTER TABLE admin_login ADD FULLTEXT INDEX ft_allowed_companies 
--   (allowed_companies);

-- ================================================================
-- END OF COMPANY ACCESS CONTROL DATABASE REFERENCE
-- ================================================================
