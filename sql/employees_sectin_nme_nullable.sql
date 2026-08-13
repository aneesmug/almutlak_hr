-- Section is being replaced by Company throughout the employee master UI/reports.
-- Relax the NOT NULL constraint on employees.sectin_nme so new/edited employees
-- no longer require picking a Section. Existing data/column is left intact
-- (still readable for legacy records / anything not yet migrated).
-- Safe to run multiple times.
ALTER TABLE `employees` MODIFY COLUMN `sectin_nme` VARCHAR(50) NULL DEFAULT NULL;
