-- Translation for the salary-hidden placeholder shown on view_employee.php when the
-- viewer lacks system_admin/HR/'view_employee_salary_value' special access.

INSERT INTO `translations` (`lang_key`, `lang_code`, `translation`) VALUES
('salary_hidden_no_access', 'en', 'Salary is hidden - you do not have permission to view it.'),
('salary_hidden_no_access', 'ar', 'الراتب مخفي - ليس لديك صلاحية لعرضه.')
ON DUPLICATE KEY UPDATE `translation` = VALUES(`translation`);
