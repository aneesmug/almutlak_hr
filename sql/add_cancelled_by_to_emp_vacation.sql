-- Migration: Track who actually cancelled a vacation/leave request
-- Purpose: emp_vacation only stored current_status = 'cancelled' with no record of
--          who performed the cancellation, so every cancelled request displayed the
--          same generic "Cancelled by Employee" label even when HR/admin cancelled
--          it on the employee's behalf. Add columns to record the actual canceller.

SET @db_name = DATABASE();

SET @column_exists = (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = @db_name
      AND TABLE_NAME = 'emp_vacation'
      AND COLUMN_NAME = 'cancelled_by'
);

SET @ddl = IF(
    @column_exists = 0,
    'ALTER TABLE `emp_vacation` ADD COLUMN `cancelled_by` VARCHAR(50) NULL DEFAULT NULL AFTER `current_status`',
    'SELECT ''Column cancelled_by already exists in emp_vacation'' AS message'
);

PREPARE stmt FROM @ddl;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @column_exists = (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = @db_name
      AND TABLE_NAME = 'emp_vacation'
      AND COLUMN_NAME = 'cancelled_at'
);

SET @ddl = IF(
    @column_exists = 0,
    'ALTER TABLE `emp_vacation` ADD COLUMN `cancelled_at` DATETIME NULL DEFAULT NULL AFTER `cancelled_by`',
    'SELECT ''Column cancelled_at already exists in emp_vacation'' AS message'
);

PREPARE stmt FROM @ddl;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
