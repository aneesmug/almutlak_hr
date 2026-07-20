-- Migration: Add per-employee emergency-vacation override flag
-- Date: 2026-07-14
-- Purpose: Normally emergency vacation can only be applied when an employee's
--          available balance is below 1 day. This flag lets HR/admin explicitly
--          allow a specific employee to apply for emergency vacation even when
--          they still have a healthy balance (>1 day).

SET @db_name = DATABASE();

SET @column_exists = (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = @db_name
      AND TABLE_NAME = 'employees'
      AND COLUMN_NAME = 'allow_emergency_vacation'
);

SET @ddl = IF(
    @column_exists = 0,
    'ALTER TABLE `employees` ADD COLUMN `allow_emergency_vacation` TINYINT(1) NOT NULL DEFAULT 0 AFTER `blocked_request_types`',
    'SELECT ''Column allow_emergency_vacation already exists in employees'' AS message'
);

PREPARE stmt FROM @ddl;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
