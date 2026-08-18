-- Translations for the "Assign Direct Supervisor (Payroll)" feature in generate_payroll.php.
--
-- IMPORTANT when importing: use a UTF-8/utf8mb4 connection, e.g.
--   mysql --default-character-set=utf8mb4 -u USER -p DBNAME < add_payroll_supervisor_assignment_translations.sql
-- or phpMyAdmin -> Import with charset set to utf8mb4.

INSERT INTO `translations` (`lang_key`, `lang_code`, `translation`) VALUES
('assign_payroll_supervisor_button', 'en', 'Assign Direct Supervisor (Payroll)'),
('select_payroll_supervisor_modal_title', 'en', 'Select Direct Supervisor'),
('payroll_supervisor_assigned_successfully', 'en', 'Payroll supervisor assigned successfully.'),
('please_select_one_employee_for_supervisor_assignment', 'en', 'Please select at least one employee.'),
('no_payroll_supervisor_candidates_found', 'en', 'No supervisors available to assign.'),
('select_employees_to_assign', 'en', 'Select Employees'),
('already_assigned_to', 'en', 'Already assigned to'),
('already_assigned_employees_locked_hint', 'en', 'Employees already assigned to a payroll supervisor are locked and cannot be re-selected here.'),
('download_payroll_supervisor_assignments_button', 'en', 'Download Supervisor Assignments'),
('supervisor_emp_id', 'en', 'Supervisor Emp ID')
ON DUPLICATE KEY UPDATE `translation` = VALUES(`translation`);

INSERT INTO `translations` (`lang_key`, `lang_code`, `translation`) VALUES
('assign_payroll_supervisor_button', 'ar', 'تعيين المشرف المباشر (الرواتب)'),
('select_payroll_supervisor_modal_title', 'ar', 'اختر المشرف المباشر'),
('payroll_supervisor_assigned_successfully', 'ar', 'تم تعيين المشرف بنجاح'),
('please_select_one_employee_for_supervisor_assignment', 'ar', 'يرجى اختيار موظف واحد على الأقل'),
('no_payroll_supervisor_candidates_found', 'ar', 'لا يوجد مشرفون متاحون للتعيين'),
('select_employees_to_assign', 'ar', 'اختر الموظفين'),
('already_assigned_to', 'ar', 'معيّن بالفعل إلى'),
('already_assigned_employees_locked_hint', 'ar', 'الموظفون المعيّنون بالفعل لمشرف رواتب مقفلون ولا يمكن اختيارهم هنا مرة أخرى.'),
('download_payroll_supervisor_assignments_button', 'ar', 'تنزيل تعيينات المشرفين'),
('supervisor_emp_id', 'ar', 'رقم المشرف الوظيفي')
ON DUPLICATE KEY UPDATE `translation` = VALUES(`translation`);
