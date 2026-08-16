-- Translations for the new "Payroll Details" tab (view_employee.php) and the
-- existing "Direct Reports" tab, which was missing DB translation rows.
-- Table: translations (lang_key, lang_code, translation) — unique key (lang_key, lang_code)

INSERT INTO `translations` (`lang_key`, `lang_code`, `translation`) VALUES
('payroll_details', 'en', 'Payroll Details'),
('payroll_details', 'ar', 'تفاصيل الراتب'),

('payroll_history', 'en', 'Payroll History'),
('payroll_history', 'ar', 'سجل الرواتب'),

('download_payslip', 'en', 'Download Payslip'),
('download_payslip', 'ar', 'تحميل قسيمة الراتب'),

('direct_reports', 'en', 'Direct Reports'),
('direct_reports', 'ar', 'المرؤوسون المباشرون')

ON DUPLICATE KEY UPDATE `translation` = VALUES(`translation`);
