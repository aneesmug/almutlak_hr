-- ========================================================================
-- MIGRATION: Add Company-Level Access Control
-- Date: 2025-12-30
-- Description: Adds JSON column to restrict which companies each user can access
-- ========================================================================

-- Add allowed_companies JSON column to admin_login table
ALTER TABLE `admin_login` 
ADD COLUMN `allowed_companies` JSON DEFAULT NULL COMMENT 'JSON array of company IDs user is allowed to access. NULL = all companies (full access). Example: [1,2,5]';

-- Create index for better query performance
ALTER TABLE `admin_login` ADD INDEX `idx_user_type` (`user_type`);

-- ========================================================================
-- MIGRATION DATA (Optional - Set default access for existing users)
-- ========================================================================

-- For regular 'employee' type users, restrict to their company only
-- UPDATE `admin_login` 
-- SET `allowed_companies` = JSON_ARRAY((SELECT IFNULL(comp_no, 1) FROM employees WHERE emp_id = admin_login.emp_id))
-- WHERE `user_type` = 'employee';

-- For managers/supervisors, allow multiple companies (commented - set manually per user)
-- Example: UPDATE `admin_login` SET `allowed_companies` = JSON_ARRAY(1,2,3) WHERE `user_type` = 'dept_user';

-- For administrative roles, leave NULL (full access to all companies)
-- UPDATE `admin_login` SET `allowed_companies` = NULL WHERE `user_type` IN ('administrator', 'gm');

-- ========================================================================
-- VERIFICATION QUERIES
-- ========================================================================

-- Check the new column
-- SELECT id_iqama, user_type, emp_id, allowed_companies FROM admin_login LIMIT 10;

-- Check which users have company restrictions
-- SELECT id_iqama, user_type, allowed_companies FROM admin_login WHERE allowed_companies IS NOT NULL;

-- Check JSON array values
-- SELECT id_iqama, user_type, JSON_EXTRACT(allowed_companies, '$[*]') as company_ids FROM admin_login WHERE allowed_companies IS NOT NULL;
