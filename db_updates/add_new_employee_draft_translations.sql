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

-- "Save as Draft" mode + Draft Employees list in the type picker.
INSERT INTO `translations` (`lang_key`, `lang_code`, `translation`) VALUES
('save_as_draft', 'en', 'Save as Draft'),
('saved_as_draft', 'en', 'Saved as draft'),
('draft_employees', 'en', 'Draft Employees'),
('draft_emp_id_taken', 'en', 'This ID is already registered - a new ID will be assigned'),
('taken', 'en', 'Taken'),
('continue_draft', 'en', 'Continue'),
('confirm_delete_draft', 'en', 'Delete this draft?'),
('save_as_draft', 'ar', 'حفظ كمسودة'),
('saved_as_draft', 'ar', 'تم الحفظ كمسودة'),
('draft_employees', 'ar', 'الموظفون المسودة'),
('draft_emp_id_taken', 'ar', 'هذا الرقم مسجل مسبقاً - سيتم تعيين رقم جديد'),
('taken', 'ar', 'مستخدم'),
('continue_draft', 'ar', 'متابعة'),
('confirm_delete_draft', 'ar', 'حذف هذه المسودة؟')
ON DUPLICATE KEY UPDATE `translation` = VALUES(`translation`);

-- Auto-saved (closed without "Save as Draft") marker in the Draft Employees list.
INSERT INTO `translations` (`lang_key`, `lang_code`, `translation`) VALUES
('auto_saved', 'en', 'Auto-saved'),
('draft_auto_saved_hint', 'en', 'Closed without saving - kept automatically'),
('auto_saved', 'ar', 'حفظ تلقائي'),
('draft_auto_saved_hint', 'ar', 'أُغلق دون حفظ - تم الاحتفاظ به تلقائياً')
ON DUPLICATE KEY UPDATE `translation` = VALUES(`translation`);
