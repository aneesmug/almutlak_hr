-- ================================================================================
-- Add Multiple Departments Support to User Accounts
-- ================================================================================
-- This script adds the ability for users to access multiple departments,
-- similar to the existing allowed_companies feature.
-- ================================================================================

-- Add allowed_departments JSON column to admin_login table
ALTER TABLE `admin_login`
ADD COLUMN `allowed_departments` JSON DEFAULT NULL 
COMMENT 'JSON array of department IDs user is allowed to access. NULL = all departments (full access). Example: [1,2,5]'
AFTER `allowed_companies`;

-- ================================================================================
-- USAGE EXAMPLES
-- ================================================================================

-- Example 1: Grant full access to all departments (NULL = full access)
-- UPDATE `admin_login` SET `allowed_departments` = NULL WHERE `user_type` IN ('administrator', 'gm');

-- Example 2: Grant access to specific departments (e.g., departments 1, 2, 5)
-- UPDATE `admin_login` SET `allowed_departments` = JSON_ARRAY(1,2,5) WHERE `id` = 123;

-- Example 3: Grant access to single department (e.g., department 3)
-- UPDATE `admin_login` SET `allowed_departments` = JSON_ARRAY(3) WHERE `user_type` = 'dept_user';

-- Example 4: Check if user has access to department 5
-- SELECT id_iqama, user_type, allowed_departments
-- FROM admin_login
-- WHERE allowed_departments IS NULL 
--    OR JSON_CONTAINS(allowed_departments, '5', '$');

-- ================================================================================
-- VERIFICATION QUERIES
-- ================================================================================

-- Check if column was added successfully:
-- SHOW COLUMNS FROM admin_login LIKE 'allowed_departments';

-- View users with department restrictions:
-- SELECT id_iqama, user_type, dept, allowed_departments 
-- FROM admin_login 
-- WHERE allowed_departments IS NOT NULL;

-- View department access summary:
-- SELECT 
--     id_iqama,
--     user_type,
--     CASE 
--         WHEN allowed_departments IS NULL THEN 'Full Access'
--         ELSE CONCAT('Limited Access (', JSON_LENGTH(allowed_departments), ' departments)')
--     END as access_level,
--     allowed_departments
-- FROM admin_login
-- ORDER BY user_type, id_iqama;
