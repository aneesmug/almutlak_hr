-- Add translation strings for vacation salary type feature
-- These translations support both English and Arabic
-- Table: translations (lang_key, lang_code, translation)

-- Insert English translations
INSERT INTO `translations` (`lang_key`, `lang_code`, `translation`) VALUES
('vacation_salary_payment', 'en', 'Vacation Salary Payment'),
('with_payroll', 'en', 'With Payroll'),
('with_end_of_service', 'en', 'With End of Service'),
('vacation_salary_type_help', 'en', 'Choose when you want to receive your vacation salary: now with payroll or later at end of service.');

-- Insert Arabic translations
INSERT INTO `translations` (`lang_key`, `lang_code`, `translation`) VALUES
('vacation_salary_payment', 'ar', 'دفع راتب الإجازة'),
('with_payroll', 'ar', 'مع الراتب الشهري'),
('with_end_of_service', 'ar', 'مع نهاية الخدمة'),
('vacation_salary_type_help', 'ar', 'اختر متى تريد استلام راتب إجازتك: الآن مع الراتب الشهري أو لاحقًا عند نهاية الخدمة.');

-- Note: If these keys already exist, you may need to update them instead:
-- UPDATE `translations` SET `translation` = 'Vacation Salary Payment' WHERE `lang_key` = 'vacation_salary_payment' AND `lang_code` = 'en';
