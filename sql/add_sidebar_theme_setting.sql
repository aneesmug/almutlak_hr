-- Adds "Main Menu Theme" (light / dark) to App Settings > General.
INSERT INTO `app_settings` (`setting_name`, `setting_value`, `setting_group`, `description`, `input_type`, `options`)
SELECT 'sidebar_theme', 'light', 'general', 'Main menu theme', 'select', '{"light":"Light","dark":"Dark"}'
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `app_settings` WHERE `setting_name` = 'sidebar_theme');
