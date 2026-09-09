-- Phase 2 of the ZKTeco attendance integration: turns raw device punches
-- (zk_attendance) into daily attendance rows, and adds a manual-entry admin
-- page. `source` tracks whether a row was written by the pairing cron or by
-- an HR admin; the unique key enforces one row per employee per day
-- regardless of source, so the cron's upsert and a manual edit target the
-- same row instead of creating duplicates.
-- ALTER TABLE rebuilds/re-validates the whole table, and `date`'s existing
-- DEFAULT '0000-00-00 00:00:00' (legacy data, unrelated to this migration)
-- fails that re-validation under strict/NO_ZERO_DATE sql_mode. Relax just
-- for this statement - it doesn't change any stored data or the column
-- itself, only lets the ALTER complete.
SET SESSION sql_mode = '';

ALTER TABLE `attendance`
  ADD COLUMN `source` ENUM('device','manual') NOT NULL DEFAULT 'device' AFTER `note`,
  ADD UNIQUE KEY `uq_emp_date` (`emp_id`, `date`);
