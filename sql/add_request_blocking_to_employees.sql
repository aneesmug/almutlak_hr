-- Migration: Add per-employee request blocking flags
-- Date: 2026-07-14
-- Purpose: Allow HR/permitted users to block an employee from submitting all
--          requests, or from submitting specific request types only.

SET @db_name = DATABASE();

SET @column_exists = (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = @db_name
      AND TABLE_NAME = 'employees'
      AND COLUMN_NAME = 'requests_blocked'
);

SET @ddl = IF(
    @column_exists = 0,
    'ALTER TABLE `employees` ADD COLUMN `requests_blocked` TINYINT(1) NOT NULL DEFAULT 0 AFTER `is_overtime_eligible`',
    'SELECT ''Column requests_blocked already exists in employees'' AS message'
);

PREPARE stmt FROM @ddl;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @column_exists = (
    SELECT COUNT(*)
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = @db_name
      AND TABLE_NAME = 'employees'
      AND COLUMN_NAME = 'blocked_request_types'
);

SET @ddl = IF(
    @column_exists = 0,
    'ALTER TABLE `employees` ADD COLUMN `blocked_request_types` TEXT NULL AFTER `requests_blocked`',
    'SELECT ''Column blocked_request_types already exists in employees'' AS message'
);

PREPARE stmt FROM @ddl;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
