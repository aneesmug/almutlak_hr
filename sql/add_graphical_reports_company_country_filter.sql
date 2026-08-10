-- Translation for the new "All Countries" placeholder used by the Country filter
-- added to Graphical Reports (graphical_reports.php) for every employee-related
-- report type, alongside the existing Company/Department filters.

INSERT INTO translations (lang_key, lang_code, translation) VALUES
    ('all_countries', 'en', 'All Countries'),
    ('all_countries', 'ar', 'جميع الدول')
ON DUPLICATE KEY UPDATE translation = VALUES(translation);
