-- Translations for the new banking/GOSI and documents visibility placeholders on
-- view_employee.php, gated by 'view_employee_banking_details' / 'view_employee_documents'.

INSERT INTO `translations` (`lang_key`, `lang_code`, `translation`) VALUES
('banking_hidden_no_access', 'en', 'Banking/IBAN/GOSI details are hidden - you do not have permission to view them.'),
('banking_hidden_no_access', 'ar', 'بيانات البنك/الآيبان/التأمينات مخفية - ليس لديك صلاحية لعرضها.'),
('documents_hidden_no_access', 'en', 'Documents are hidden'),
('documents_hidden_no_access', 'ar', 'المستندات مخفية'),
('documents_hidden_no_access_desc', 'en', 'You do not have permission to view this employee''s uploaded documents.'),
('documents_hidden_no_access_desc', 'ar', 'ليس لديك صلاحية لعرض المستندات المرفوعة لهذا الموظف.')
ON DUPLICATE KEY UPDATE `translation` = VALUES(`translation`);
