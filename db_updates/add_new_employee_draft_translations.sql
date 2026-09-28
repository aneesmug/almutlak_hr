-- Translations for the "Add New Employee" modal draft notice (assets/js/newEmployeeModal.js).
--
-- IMPORTANT when importing: use a UTF-8/utf8mb4 connection, e.g.
--   mysql --default-character-set=utf8mb4 -u USER -p DBNAME < add_new_employee_draft_translations.sql

INSERT INTO `translations` (`lang_key`, `lang_code`, `translation`) VALUES
('new_emp_draft_restored', 'en', 'Previously entered values will be restored.'),
('discard_draft', 'en', 'Discard and start fresh'),
('new_emp_draft_restored', 'ar', 'سيتم استعادة القيم التي تم إدخالها سابقاً.'),
('discard_draft', 'ar', 'تجاهل والبدء من جديد')
ON DUPLICATE KEY UPDATE `translation` = VALUES(`translation`);
