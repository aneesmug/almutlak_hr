-- Translation keys for Dashboard demographic pie charts (Country/Gender/Company/Saudi)
-- Usage: Import this file into your database to add translation support

INSERT INTO `translations` (`lang_key`, `lang_code`, `translation`) VALUES
('employee_demographics', 'en', 'Employee Demographics'),
('employee_demographics', 'ar', 'التركيبة السكانية للموظفين'),
('employees_by_country', 'en', 'Employees by Country'),
('employees_by_country', 'ar', 'الموظفون حسب الدولة'),
('employees_by_gender', 'en', 'Employees by Gender'),
('employees_by_gender', 'ar', 'الموظفون حسب الجنس'),
('employees_by_company', 'en', 'Employees by Company'),
('employees_by_company', 'ar', 'الموظفون حسب الشركة'),
('saudi_vs_non_saudi', 'en', 'Saudi vs Non-Saudi'),
('saudi_vs_non_saudi', 'ar', 'سعودي مقابل غير سعودي'),
('saudi', 'en', 'Saudi'),
('saudi', 'ar', 'سعودي'),
('non_saudi', 'en', 'Non-Saudi'),
('non_saudi', 'ar', 'غير سعودي'),
('other', 'en', 'Other'),
('other', 'ar', 'أخرى'),
('unspecified', 'en', 'Unspecified'),
('unspecified', 'ar', 'غير محدد'),
('apply', 'en', 'Apply'),
('apply', 'ar', 'تطبيق'),
('filtered_by', 'en', 'Filtered by'),
('filtered_by', 'ar', 'مفلتر حسب'),
('click_a_chart_slice_to_filter', 'en', 'Tip: click a slice on the Country, Gender, or Saudi/Non-Saudi chart to filter all charts by it.'),
('click_a_chart_slice_to_filter', 'ar', 'ملاحظة: انقر على أي جزء من مخطط الدولة أو الجنس أو سعودي/غير سعودي لتصفية جميع المخططات حسبه.'),
('click_to_clear', 'en', 'Click to clear'),
('click_to_clear', 'ar', 'انقر للمسح'),
('hold_ctrl_to_select_multiple', 'en', 'Hold Ctrl (Cmd) to select multiple'),
('hold_ctrl_to_select_multiple', 'ar', 'اضغط مع الاستمرار على Ctrl (Cmd) لتحديد أكثر من عنصر')
ON DUPLICATE KEY UPDATE `translation` = VALUES(`translation`);
