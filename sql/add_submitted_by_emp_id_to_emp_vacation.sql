-- Adds a column to track who submitted (applied) the vacation request
-- Safe to run multiple times: check if column exists before altering

ALTER TABLE `emp_vacation`
ADD COLUMN `submitted_by_emp_id` INT NULL DEFAULT NULL AFTER `emp_id`;
