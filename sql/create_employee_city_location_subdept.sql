-- Employee Master: City / Location / Sub-department
-- City is sourced from the existing `saudi_cities` table.
-- Locations are admin-managed (app_settings.php) and each belongs to exactly one city.
-- Sub-departments are admin-managed (app_settings.php) and each belongs to exactly one department.
-- Safe to run multiple times (CREATE TABLE IF NOT EXISTS + guarded ALTERs).

CREATE TABLE IF NOT EXISTS `locations` (
  `id` INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `city_id` INT(11) NOT NULL,
  `name_en` VARCHAR(255) NOT NULL,
  `name_ar` VARCHAR(255) NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY `idx_locations_city_id` (`city_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `sub_departments` (
  `id` INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `department_id` INT(11) NOT NULL,
  `name_en` VARCHAR(255) NOT NULL,
  `name_ar` VARCHAR(255) NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY `idx_sub_departments_department_id` (`department_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Re-run-safe: if this ran previously under the old latin1 default, convert both
-- tables (and their name_en/name_ar columns) to utf8mb4 so Arabic text stores
-- correctly. CONVERT TO on an already-utf8mb4 table is a harmless no-op.
ALTER TABLE `locations` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `sub_departments` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE `locations` CHANGE `name_ar` `name_ar` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL;
ALTER TABLE `sub_departments` CHANGE `name_ar` `name_ar` VARCHAR(255) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL;

-- ALTER TABLE has no native IF NOT EXISTS for ADD COLUMN in this MySQL/MariaDB version,
-- so each column addition is guarded via information_schema to stay safe on re-run.
SET @col_exists = (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'employees' AND COLUMN_NAME = 'city_id'
);
SET @sql = IF(@col_exists = 0,
  'ALTER TABLE `employees` ADD COLUMN `city_id` INT(11) NULL DEFAULT NULL AFTER `sectin_nme`',
  'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @col_exists = (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'employees' AND COLUMN_NAME = 'location_id'
);
SET @sql = IF(@col_exists = 0,
  'ALTER TABLE `employees` ADD COLUMN `location_id` INT(11) NULL DEFAULT NULL AFTER `city_id`',
  'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @col_exists = (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'employees' AND COLUMN_NAME = 'sub_dept_id'
);
SET @sql = IF(@col_exists = 0,
  'ALTER TABLE `employees` ADD COLUMN `sub_dept_id` INT(11) NULL DEFAULT NULL AFTER `location_id`',
  'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

