-- Add missing columns to payroll_deductions table for calculation types and period tracking
-- These columns are needed to support hourly and daily deduction calculations

ALTER TABLE `payroll_deductions` ADD COLUMN `calculation_type` VARCHAR(20) DEFAULT 'fixed' AFTER `note`;
ALTER TABLE `payroll_deductions` ADD COLUMN `hours` DECIMAL(5,2) DEFAULT NULL AFTER `calculation_type`;
ALTER TABLE `payroll_deductions` ADD COLUMN `days` DECIMAL(5,2) DEFAULT NULL AFTER `hours`;
