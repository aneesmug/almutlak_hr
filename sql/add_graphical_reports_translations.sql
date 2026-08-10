-- Translations for the new Graphical Reports page (graphical_reports.php), which
-- lets users pick any existing report type and render it as a Pie/Donut/Bar chart
-- grouped by Status or Department, reusing the same includes/ajaxFile/ajaxReports.php
-- data endpoint the tabular Reports page already uses.

INSERT INTO translations (lang_key, lang_code, translation) VALUES
    ('graphical_reports', 'en', 'Graphical Reports'),
    ('graphical_reports', 'ar', 'التقارير البيانية'),
    ('group_by', 'en', 'Group By'),
    ('group_by', 'ar', 'تجميع حسب'),
    ('chart_type', 'en', 'Chart Type'),
    ('chart_type', 'ar', 'نوع الرسم البياني'),
    ('pie_chart', 'en', 'Pie Chart'),
    ('pie_chart', 'ar', 'رسم دائري'),
    ('donut_chart', 'en', 'Donut Chart'),
    ('donut_chart', 'ar', 'رسم حلقي'),
    ('bar_chart', 'en', 'Bar Chart'),
    ('bar_chart', 'ar', 'رسم شريطي'),
    ('all_departments', 'en', 'All Departments'),
    ('all_departments', 'ar', 'جميع الأقسام'),
    ('select_report_type_and_generate', 'en', 'Select a report type and group-by, then click Generate'),
    ('select_report_type_and_generate', 'ar', 'اختر نوع التقرير والتجميع ثم اضغط توليد'),
    ('unspecified', 'en', 'Unspecified'),
    ('unspecified', 'ar', 'غير محدد'),
    ('categories', 'en', 'Categories'),
    ('categories', 'ar', 'الفئات'),
    ('count', 'en', 'Count'),
    ('count', 'ar', 'العدد'),
    ('rating', 'en', 'Rating'),
    ('rating', 'ar', 'التقييم')
ON DUPLICATE KEY UPDATE translation = VALUES(translation);
