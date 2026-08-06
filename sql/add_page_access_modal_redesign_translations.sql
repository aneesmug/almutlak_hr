-- Translations for the Page Access tab redesign (SweetAlert2 modal, same UX as Special Access).

INSERT INTO `translations` (`lang_key`, `lang_code`, `translation`) VALUES
('grant_access_to_a_page', 'en', 'Grant Access to a Page'),
('grant_access_to_a_page', 'ar', 'منح صلاحية الوصول لصفحة'),
('remove_page_access_config', 'en', 'Remove page access config'),
('remove_page_access_config', 'ar', 'إزالة إعداد صلاحية الصفحة'),
('this_will_remove_configured_roles_for_this_page', 'en', 'This will remove all configured roles for this page.'),
('this_will_remove_configured_roles_for_this_page', 'ar', 'سيؤدي هذا إلى إزالة جميع الأدوار المعينة لهذه الصفحة.')
ON DUPLICATE KEY UPDATE `translation` = VALUES(`translation`);
