-- Add additional columns to emp_resignations table for approval workflow and replacement data
-- Run this SQL to update the existing schema

ALTER TABLE `emp_resignations`
ADD COLUMN `request_inv_no` VARCHAR(50) NULL UNIQUE AFTER `id`,
ADD COLUMN `approved_by` VARCHAR(255) NULL AFTER `status`,
ADD COLUMN `approved_at` DATETIME NULL AFTER `approved_by`,
ADD COLUMN `rejected_by` VARCHAR(255) NULL AFTER `approved_at`,
ADD COLUMN `rejected_at` DATETIME NULL AFTER `rejected_by`,
ADD COLUMN `rejection_reason` TEXT NULL AFTER `rejected_at`,
ADD COLUMN `needs_replacement` TINYINT(1) DEFAULT 0 AFTER `rejection_reason`,
ADD COLUMN `replacement_data` TEXT NULL COMMENT 'JSON data for replacement job requirements' AFTER `needs_replacement`;

-- Generate request_inv_no for existing records
UPDATE `emp_resignations` 
SET `request_inv_no` = CONCAT('RES-', LPAD(`id`, 6, '0')) 
WHERE `request_inv_no` IS NULL;

-- Sample structure of replacement_data JSON:
-- {
--   "job_title": "Senior Developer",
--   "job_description": "Full stack development...",
--   "experience": "5-7 years",
--   "certificate": "PMP, AWS Certified",
--   "academic_achievement": "Bachelor in Computer Science",
--   "date_of_joining": "2025-01-15"
-- }
