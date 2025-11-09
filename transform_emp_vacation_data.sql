-- ========================================
-- Transform emp_vacation data from old structure to new structure
-- Created: November 5, 2025
-- ========================================

-- This script transforms vacation data from the old approval system
-- to the new request_approvers workflow system

-- Transformation Logic:
-- old approval_status -> new current_status mapping:
--   'apply' -> 'draft'
--   'dept_manager_pending' -> 'pending_approval'
--   'pending' -> 'pending_approval'
--   'hr_assistant_approved' -> 'pending_approval'
--   'it_pending' -> 'pending_approval'
--   'hr_manager_approved' -> 'pending_approval'
--   'gm_approved' -> 'approved'
--   'rejected' -> 'rejected'

-- Step 1: Insert transformed data
INSERT INTO `emp_vacation` (
    `id`,
    `request_inv_no`,
    `current_status`,
    `current_approval_level`,
    `emp_id`,
    `start_date`,
    `user_update`,
    `return_date`,
    `vacdays`,
    `vac_type`,
    `fly_type`,
    `arrived_date`,
    `permit_no`,
    `remarks`,
    `vacation_salary_type`,
    `attachment_path`,
    `is_deductible`,
    `review`,
    `note`,
    `replacement_person`,
    `last_vac_date`,
    `next_vac_date`,
    `ticket_pay`,
    `permit_fee`,
    `encashment_amount`,
    `created_at`
)
SELECT 
    `id`,
    CONCAT('VAC-', LPAD(`id`, 6, '0')) as request_inv_no, -- Generate unique request number
    CASE 
        WHEN `approval_status` = 'apply' THEN 'draft'
        WHEN `approval_status` IN ('dept_manager_pending', 'pending', 'hr_assistant_approved', 'it_pending', 'hr_manager_approved') THEN 'pending_approval'
        WHEN `approval_status` = 'gm_approved' THEN 'approved'
        WHEN `approval_status` = 'rejected' THEN 'rejected'
        ELSE 'draft'
    END as current_status,
    CASE 
        WHEN `approval_status` = 'apply' THEN NULL
        WHEN `approval_status` = 'dept_manager_pending' THEN 1
        WHEN `approval_status` IN ('pending', 'hr_assistant_approved') THEN 2
        WHEN `approval_status` = 'it_pending' THEN 3
        WHEN `approval_status` = 'hr_manager_approved' THEN 4
        WHEN `approval_status` = 'gm_approved' THEN NULL -- Approved, no pending level
        WHEN `approval_status` = 'rejected' THEN NULL
        ELSE NULL
    END as current_approval_level,
    `emp_id`,
    `start_date`,
    `user_update`,
    `return_date`,
    `vacdays`,
    `vac_type`,
    `fly_type`,
    `arrived_date`,
    `permit_no`,
    `remarks`,
    'payroll' as vacation_salary_type, -- Default to payroll
    `attachment_path`,
    `is_deductible`,
    `review`,
    `note`,
    `replacement_person`,
    `last_vac_date`,
    `next_vac_date`,
    `ticket_pay`,
    `permit_fee`,
    NULL as encashment_amount, -- Set to NULL, can be updated later
    `created_at`
FROM `emp_vacation_old_backup`
WHERE `id` NOT IN (SELECT `id` FROM `emp_vacation`); -- Avoid duplicates

-- Note: Before running this script:
-- 1. Backup your current emp_vacation table
-- 2. Rename old table: RENAME TABLE emp_vacation TO emp_vacation_old_backup;
-- 3. Create new table structure (from Untitled-1)
-- 4. Run this script to populate new table with transformed data
