-- =====================================================================
-- Database Migration: Add Other Earnings and Calculation Fields
-- Table: emp_vacation
-- Date: 2025-12-23
-- =====================================================================
-- Run these SQL queries to add the new columns to emp_vacation table

-- 1. Add other_earnings column (if not exists)
ALTER TABLE `emp_vacation` 
ADD COLUMN `other_earnings` DECIMAL(10, 2) DEFAULT 0.00 
AFTER `deduction_days`;

-- 2. Add overtime_amount column (calculated field)
ALTER TABLE `emp_vacation` 
ADD COLUMN `overtime_amount` DECIMAL(10, 2) DEFAULT 0.00 
AFTER `deduction_days`;

-- 3. Add deduction_amount column (calculated field)
ALTER TABLE `emp_vacation` 
ADD COLUMN `deduction_amount` DECIMAL(10, 2) DEFAULT 0.00 
AFTER `overtime_amount`;

-- Verify the new columns exist
-- SELECT COLUMN_NAME, COLUMN_TYPE FROM INFORMATION_SCHEMA.COLUMNS 
-- WHERE TABLE_NAME = 'emp_vacation' AND COLUMN_NAME IN ('other_earnings', 'overtime_amount', 'deduction_amount');

-- =====================================================================
-- Column Descriptions:
-- =====================================================================
-- other_earnings: Additional earnings to add to payroll (bonus, incentives, etc.)
--                 Values: DECIMAL(10,2), Default: 0.00
--
-- overtime_amount: Calculated overtime payment amount
--                  Formula: (overtime_hours * hourly_rate)
--                  Values: DECIMAL(10,2), Default: 0.00
--
-- deduction_amount: Calculated deduction payment amount
--                   Formula: (deduction_hours * hourly_rate) + (deduction_days * daily_rate)
--                   Values: DECIMAL(10,2), Default: 0.00
--
-- =====================================================================
