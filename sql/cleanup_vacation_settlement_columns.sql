-- ============================================================
-- CLEANUP: Remove settlement columns from emp_vacation
-- Settlement data now stored in separate settlement_records table
-- ============================================================

ALTER TABLE `emp_vacation` DROP COLUMN IF EXISTS `settlement_status`;
ALTER TABLE `emp_vacation` DROP COLUMN IF EXISTS `settlement_amount`;
ALTER TABLE `emp_vacation` DROP COLUMN IF EXISTS `settlement_date`;

-- Verify columns removed
-- SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS 
-- WHERE TABLE_NAME='emp_vacation' AND COLUMN_NAME LIKE 'settlement%';
