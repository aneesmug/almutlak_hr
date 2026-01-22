-- ========================================================================
-- ALTER TABLE: Add hours, days, and calculation_type columns
-- Purpose: Support deduction/benefit tracking by hours and days
-- Date: January 21, 2026
-- ========================================================================

-- Add missing columns to payroll_deductions table
ALTER TABLE `payroll_deductions` 
  ADD COLUMN `hours` INT(11) DEFAULT NULL AFTER `note`,
  ADD COLUMN `days` INT(11) DEFAULT NULL AFTER `hours`,
  ADD COLUMN `calculation_type` VARCHAR(50) DEFAULT 'fixed' AFTER `days`;

-- Add missing columns to payroll_benefits table (for consistency)
ALTER TABLE `payroll_benefits` 
  ADD COLUMN `days` INT(11) DEFAULT NULL AFTER `hours`,
  ADD COLUMN `calculation_type` VARCHAR(50) DEFAULT 'fixed' AFTER `days`;

-- ========================================================================
-- NEW COLUMNS EXPLANATION:
-- 
-- `hours` (INT): Number of hours for hourly deductions/benefits
-- `days` (INT): Number of days for daily deductions/benefits
-- `calculation_type` (VARCHAR): Type of calculation
--   - 'fixed': Fixed amount (original behavior)
--   - 'hourly_deduction': Deduction calculated by hours
--   - 'daily_deduction': Deduction calculated by days
--   - 'overtime_basic': Overtime based on basic salary
--   - 'overtime_total': Overtime based on total salary
--
-- IMPACT ON EXCEL EXPORT:
-- When exporting to Excel, deductions/benefits will now display:
--   - If hourly: "Deduction 5 hrs"
--   - If daily: "Deduction 2 days"
--   - If fixed: "Deduction: 500.00"
--
-- EACH ITEM ON SEPARATE LINE (due to \n line breaks in cell values)
-- ========================================================================
