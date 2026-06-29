-- Migration: Add overtime eligibility flag to employees table
-- Date: 2026-05-12
-- Purpose: Controls whether employee is eligible for overtime

SET @db_name = DATABASE();
SET @column_exists = (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = @db_name
      AND TABLE_NAME = 'employees'
      AND COLUMN_NAME = 'is_overtime_eligible'
);

SET @ddl = IF(
    @column_exists = 0,
    'ALTER TABLE `employees` ADD COLUMN `is_overtime_eligible` TINYINT(1) NOT NULL DEFAULT 0 AFTER `payment_type`',
    'SELECT ''Column is_overtime_eligible already exists in employees'' AS message'
);

PREPARE stmt FROM @ddl;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
