-- Replaces the single global "Office Hours Settings" (app_settings rows
-- office_hours_*) with named Timetables assignable per company - different
-- branches/factories have different weekly days off. See attendance_config
-- tab in app_settings.php and includes/attendance_helpers.php.

CREATE TABLE IF NOT EXISTS `timetables` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `check_in` VARCHAR(5) NOT NULL DEFAULT '08:00',
  `check_in_start` VARCHAR(5) NOT NULL DEFAULT '07:45',
  `check_in_end` VARCHAR(5) NOT NULL DEFAULT '08:15',
  `check_out` VARCHAR(5) NOT NULL DEFAULT '17:00',
  `check_out_start` VARCHAR(5) NOT NULL DEFAULT '16:45',
  `check_out_end` VARCHAR(5) NOT NULL DEFAULT '17:15',
  `standard_hours` DECIMAL(4,1) NOT NULL DEFAULT 8,
  `days_off` VARCHAR(50) NOT NULL DEFAULT '5,6',  -- comma list, ISO weekday (1=Mon..7=Sun); default Fri+Sat
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

ALTER TABLE `companies` ADD COLUMN IF NOT EXISTS `timetable_id` INT NULL DEFAULT NULL AFTER `comp_name_ar`;

-- Seed "Default", carrying forward any office_hours_* values already saved
-- via the now-removed Payroll Settings > Office Hours Settings tab, else the
-- same defaults that tab used.
INSERT INTO timetables (id, name, check_in, check_in_start, check_in_end, check_out, check_out_start, check_out_end, standard_hours, days_off)
SELECT 1, 'Default',
  COALESCE((SELECT setting_value FROM app_settings WHERE setting_name='office_hours_check_in'), '08:00'),
  COALESCE((SELECT setting_value FROM app_settings WHERE setting_name='office_hours_check_in_start'), '07:45'),
  COALESCE((SELECT setting_value FROM app_settings WHERE setting_name='office_hours_check_in_end'), '08:15'),
  COALESCE((SELECT setting_value FROM app_settings WHERE setting_name='office_hours_check_out'), '17:00'),
  COALESCE((SELECT setting_value FROM app_settings WHERE setting_name='office_hours_check_out_start'), '16:45'),
  COALESCE((SELECT setting_value FROM app_settings WHERE setting_name='office_hours_check_out_end'), '17:15'),
  COALESCE((SELECT setting_value FROM app_settings WHERE setting_name='office_hours_standard_hours'), '8'),
  '5,6'
WHERE NOT EXISTS (SELECT 1 FROM timetables WHERE id = 1);

UPDATE companies SET timetable_id = 1 WHERE timetable_id IS NULL;

DELETE FROM app_settings WHERE setting_name LIKE 'office_hours_%';
