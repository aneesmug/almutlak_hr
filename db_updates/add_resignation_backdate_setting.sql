-- Resignation back-date window: App Settings > Payroll & Compensation Settings > Resignation Settings.
-- The app_settings row itself (resignation_backdate_days, default 0 = future dates only) is
-- auto-created by includes/payroll_settings_handler.php::ensurePayrollParamSettings(); this
-- insert only makes it exist before the tab is opened for the first time.
--
-- IMPORTANT when importing: use a UTF-8/utf8mb4 connection, e.g.
--   mysql --default-character-set=utf8mb4 -u USER -p DBNAME < add_resignation_backdate_setting.sql

INSERT INTO `app_settings` (`setting_name`, `setting_value`, `setting_group`, `description`, `input_type`, `options`)
SELECT 'resignation_backdate_days', '0', 'resignation_settings', 'Days back allowed for Last Working Day when applying resignation', 'text', NULL
FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM `app_settings` WHERE `setting_name` = 'resignation_backdate_days');

INSERT INTO `translations` (`lang_key`, `lang_code`, `translation`) VALUES
('resignation_settings', 'en', 'Resignation Settings'),
('resignation_settings', 'ar', 'إعدادات الاستقالة'),
('days_back_allowed_for_last_working_day_when_applying_resignation', 'en', 'Days back allowed for Last Working Day when applying resignation (0 = future dates only)'),
('days_back_allowed_for_last_working_day_when_applying_resignation', 'ar', 'عدد الأيام السابقة المسموح بها لآخر يوم عمل عند تقديم الاستقالة (0 = تواريخ مستقبلية فقط)'),
('last_working_day_backdate_limit', 'en', 'Last working day cannot be earlier than'),
('last_working_day_backdate_limit', 'ar', 'لا يمكن أن يكون آخر يوم عمل قبل')
ON DUPLICATE KEY UPDATE `translation` = VALUES(`translation`);
