-- Add payroll adjustment fields to emp_vacation table
-- Date: 2025-11-17
-- Purpose: Allow HR Payroll to record overtime and deductions during vacation approval

ALTER TABLE `emp_vacation` 
ADD COLUMN `overtime_hours` DECIMAL(10,2) NULL DEFAULT NULL COMMENT 'Overtime hours during vacation period' AFTER `created_at`,
ADD COLUMN `deduction_hours` DECIMAL(10,2) NULL DEFAULT NULL COMMENT 'Deduction hours during vacation period' AFTER `overtime_hours`,
ADD COLUMN `deduction_days` DECIMAL(10,2) NULL DEFAULT NULL COMMENT 'Deduction days during vacation period' AFTER `deduction_hours`,
ADD COLUMN `payroll_note` TEXT NULL DEFAULT NULL COMMENT 'Payroll notes and remarks' AFTER `deduction_days`;
