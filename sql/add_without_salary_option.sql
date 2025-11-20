-- Migration: Make vacation_salary_type nullable and fix invalid dates
-- Date: 2025-11-18
-- Purpose: Allow employees to apply for vacation without pre-selecting salary payment option

-- Step 1: Make vacation_salary_type nullable (no default value to force selection)
ALTER TABLE `emp_vacation` 
MODIFY COLUMN `vacation_salary_type` ENUM('payroll', 'end_of_service') NULL DEFAULT NULL;

-- Step 2: Fix existing '0000-00-00' dates to NULL
UPDATE `emp_vacation` 
SET `departure_date` = NULL 
WHERE `departure_date` = '0000-00-00';

UPDATE `emp_vacation` 
SET `arrival_date` = NULL 
WHERE `arrival_date` = '0000-00-00';

-- Verification queries:
-- SELECT COUNT(*) FROM emp_vacation WHERE arrival_date = '0000-00-00'; -- Should return 0
-- SELECT COUNT(*) FROM emp_vacation WHERE departure_date = '0000-00-00'; -- Should return 0
-- SHOW COLUMNS FROM emp_vacation LIKE 'vacation_salary_type'; -- Should show NULL allowed
