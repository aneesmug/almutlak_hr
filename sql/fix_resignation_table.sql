-- Quick fix: Add ONLY the missing request_inv_no column
-- Other columns (approved_by, etc.) already exist
-- Run this via phpMyAdmin SQL tab:

-- Check if request_inv_no column exists first by running:
-- SHOW COLUMNS FROM emp_resignations LIKE 'request_inv_no';

-- If it doesn't exist, run this:
ALTER TABLE `emp_resignations` 
ADD COLUMN `request_inv_no` VARCHAR(50) NULL AFTER `id`;

-- Add unique index (run separately if column already exists without index)
-- ALTER TABLE `emp_resignations` ADD UNIQUE KEY `request_inv_no` (`request_inv_no`);

-- Generate request_inv_no for existing records
UPDATE `emp_resignations` 
SET `request_inv_no` = CONCAT('RES-', LPAD(`id`, 6, '0')) 
WHERE `request_inv_no` IS NULL OR `request_inv_no` = '';
