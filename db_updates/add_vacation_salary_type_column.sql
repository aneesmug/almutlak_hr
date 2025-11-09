-- Add vacation_salary_type column to emp_vacation table
-- This allows employees to choose whether they want vacation salary with payroll or end of service

ALTER TABLE `emp_vacation` 
ADD COLUMN `vacation_salary_type` ENUM('payroll', 'end_of_service') NOT NULL DEFAULT 'payroll' 
COMMENT 'Determines when vacation salary is paid: with payroll or at end of service'
AFTER `remarks`;

-- Add index for better query performance
ALTER TABLE `emp_vacation` 
ADD INDEX `idx_vacation_salary_type` (`vacation_salary_type`);
