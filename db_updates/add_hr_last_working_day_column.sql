-- =====================================================================
-- Add HR Last Working Day Column to emp_resignations Table
-- Purpose: Store the HR-selected last working day for resignations
-- Date: 2025-11-26
-- =====================================================================

-- Add column to store HR's selected last working day
ALTER TABLE `emp_resignations` 
ADD COLUMN `hr_last_working_day` DATE NULL DEFAULT NULL AFTER `last_working_day`;

-- Add index for the new column
ALTER TABLE `emp_resignations` 
ADD INDEX `idx_hr_last_working_day` (`hr_last_working_day`);

-- Log the migration
INSERT INTO `system_logs` (`description`, `log_date`) 
VALUES ('Added hr_last_working_day column to emp_resignations table', NOW());
