-- Add no_modifications flag to emp_vacation table to track when "No modifications needed" is approved
-- This allows hiding the "Add Deduction/Overtime" button once it's been marked as reviewed

ALTER TABLE `emp_vacation`
ADD COLUMN IF NOT EXISTS `no_modifications` TINYINT(1) DEFAULT 0 COMMENT 'Flag set to 1 when "No modifications needed" is approved by payroll' AFTER `payroll_note`,
ADD INDEX `idx_no_modifications` (`no_modifications`);
