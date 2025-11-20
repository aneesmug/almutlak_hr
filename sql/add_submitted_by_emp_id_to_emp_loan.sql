-- Adds a column to track who submitted (applied) the loan request
-- This uses INT to align with employees.emp_id numeric usage in many places

ALTER TABLE `emp_loan`
ADD COLUMN `submitted_by_emp_id` INT NULL DEFAULT NULL AFTER `emp_id`;
