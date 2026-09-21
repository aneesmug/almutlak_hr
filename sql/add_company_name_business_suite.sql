-- Company name shown in the header title ("{company_name} Business Suite").
INSERT INTO `app_settings` (`setting_name`, `setting_value`, `setting_group`, `description`, `input_type`, `options`)
SELECT 'company_name', 'Al-Mutlak', 'general', 'Company name (shown in the header title)', 'text', NULL
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `app_settings` WHERE `setting_name` = 'company_name');

INSERT INTO `translations` (`lang_key`, `lang_code`, `translation`)
SELECT 'business_suite', 'en', 'Business Suite' FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `translations` WHERE `lang_key` = 'business_suite' AND `lang_code` = 'en');
INSERT INTO `translations` (`lang_key`, `lang_code`, `translation`)
SELECT 'business_suite', 'ar', 'نظام إدارة الأعمال' FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `translations` WHERE `lang_key` = 'business_suite' AND `lang_code` = 'ar');
