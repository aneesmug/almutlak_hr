-- Add columns for other_deductions and auto_gosi_deduction to emp_vacation table
-- This migration adds support for manual deduction entry and GOSI auto-deduction toggle

ALTER TABLE `emp_vacation`
ADD COLUMN `other_deductions` decimal(10,2) DEFAULT 0.00 COMMENT 'Other deductions (manual entry)' AFTER `other_earnings`,
ADD COLUMN `auto_gosi_deduction` tinyint(1) DEFAULT 1 COMMENT 'Auto GOSI deduction flag: 1=auto deduct GOSI, 0=manual/no deduction' AFTER `other_deductions`;

-- Verify the columns were added
-- SELECT COLUMN_NAME, DATA_TYPE, COLUMN_DEFAULT, COLUMN_COMMENT
-- FROM INFORMATION_SCHEMA.COLUMNS
-- WHERE TABLE_NAME = 'emp_vacation' AND COLUMN_NAME IN ('other_deductions', 'auto_gosi_deduction');
