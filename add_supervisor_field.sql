-- Add supervisor_id field to employees table
-- This allows assigning a direct supervisor/manager to each employee

ALTER TABLE `employees` 
ADD COLUMN `supervisor_id` VARCHAR(255) NULL DEFAULT NULL COMMENT 'Employee ID of direct supervisor/manager' AFTER `emptype`;

-- Add index for better performance
ALTER TABLE `employees` 
ADD INDEX `idx_supervisor` (`supervisor_id`);

-- Optional: Add foreign key constraint (uncomment if you want strict referential integrity)
-- ALTER TABLE `employees` 
-- ADD CONSTRAINT `fk_supervisor` 
-- FOREIGN KEY (`supervisor_id`) REFERENCES `employees`(`emp_id`) 
-- ON DELETE SET NULL ON UPDATE CASCADE;

-- Verify the change
DESCRIBE `employees`;
