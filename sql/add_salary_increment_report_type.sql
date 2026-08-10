-- Registers 'salary_increment' as a selectable report type in Reports and in
-- App Settings > Special Access > Report Access, mirroring the existing 'loan'
-- report type. The actual query logic lives in generateSalaryIncrementReport()
-- (includes/ajaxFile/ajaxReports.php) and does not need a DB migration; this
-- file only adds the translation used for its label.

INSERT INTO translations (lang_key, lang_code, translation) VALUES
    ('salary_increment_report', 'en', 'Salary Increment Report'),
    ('salary_increment_report', 'ar', 'تقرير علاوة الراتب')
ON DUPLICATE KEY UPDATE translation = VALUES(translation);
