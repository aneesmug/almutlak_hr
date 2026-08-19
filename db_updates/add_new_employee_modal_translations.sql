-- Translations for the "Add New Employee" SweetAlert2 modal (assets/js/newEmployeeModal.js).
-- Everything else the modal displays reuses existing keys already used by
-- new_comp_employee.php / new_mnpow_employee.php - these 7 are the only genuinely new ones.
--
-- IMPORTANT when importing: use a UTF-8/utf8mb4 connection, e.g.
--   mysql --default-character-set=utf8mb4 -u USER -p DBNAME < add_new_employee_modal_translations.sql

INSERT INTO `translations` (`lang_key`, `lang_code`, `translation`) VALUES
('add_new_employee_modal_title', 'en', 'Add New Employee'),
('basic_information', 'en', 'Basic Information'),
('employment_information', 'en', 'Employment Information'),
('other_information', 'en', 'Other Information'),
('is_required', 'en', 'is required'),
('register', 'en', 'Register'),
('add_employee_picture', 'en', 'Add Employee Picture')
ON DUPLICATE KEY UPDATE `translation` = VALUES(`translation`);

INSERT INTO `translations` (`lang_key`, `lang_code`, `translation`) VALUES
('add_new_employee_modal_title', 'ar', 'إضافة موظف جديد'),
('basic_information', 'ar', 'المعلومات الأساسية'),
('employment_information', 'ar', 'معلومات التوظيف'),
('other_information', 'ar', 'معلومات أخرى'),
('is_required', 'ar', 'مطلوب'),
('register', 'ar', 'تسجيل'),
('add_employee_picture', 'ar', 'إضافة صورة الموظف')
ON DUPLICATE KEY UPDATE `translation` = VALUES(`translation`);
