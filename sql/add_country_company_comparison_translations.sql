-- Translation keys for combined Country & Company Comparison Report
-- Usage: Import this file into your database to add translation support

INSERT INTO `translations` (`lang_key`, `lang_code`, `translation`) VALUES
('country_company_comparison', 'en', 'Country & Company Comparison'),
('country_company_comparison', 'ar', 'مقارنة الدول والشركات'),
('country_company_comparison_report', 'en', 'Country & Company Comparison Report'),
('country_company_comparison_report', 'ar', 'تقرير مقارنة الدول والشركات'),
('company', 'en', 'Company'),
('company', 'ar', 'الشركة'),
('select_companies', 'en', 'Select companies'),
('select_companies', 'ar', 'اختر الشركات'),
('all_companies', 'en', 'All Companies'),
('all_companies', 'ar', 'كل الشركات'),
('select_all_or_specific_companies', 'en', 'Select all or specific companies'),
('select_all_or_specific_companies', 'ar', 'اختر الكل أو شركات محددة')
ON DUPLICATE KEY UPDATE `translation` = VALUES(`translation`);
