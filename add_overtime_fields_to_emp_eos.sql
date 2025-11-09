-- Add overtime fields to emp_eos table
-- Run this SQL to add overtime_hours and overtime_days columns

ALTER TABLE `emp_eos` 
ADD COLUMN `overtime_hours` DECIMAL(10,2) DEFAULT 0.00 AFTER `anul_vac_salry`,
ADD COLUMN `overtime_days` DECIMAL(10,2) DEFAULT 0.00 AFTER `overtime_hours`;

-- Update existing records to have 0 for overtime
UPDATE `emp_eos` SET `overtime_hours` = 0.00, `overtime_days` = 0.00 WHERE `overtime_hours` IS NULL OR `overtime_days` IS NULL;
