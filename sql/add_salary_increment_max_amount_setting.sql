-- Adds a configurable "Maximum salary increment amount" setting so the 2000 cap
-- used by ajaxSalaryIncrement.php (submit + GM approve) and the frontend forms
-- can be changed from App Settings > Payroll & Compensation Settings > Salary
-- Increment Settings, instead of being hardcoded.

INSERT INTO app_settings (setting_name, setting_value, setting_group, description, input_type, options)
SELECT 'salary_increment_max_amount', '2000', 'salary_increment_settings', 'Maximum salary increment amount allowed per request', 'text', NULL
WHERE NOT EXISTS (
    SELECT 1 FROM app_settings WHERE setting_name = 'salary_increment_max_amount'
);

INSERT INTO translations (lang_key, lang_code, translation) VALUES
    ('payroll_settings', 'en', 'Payroll & Compensation Settings'),
    ('payroll_settings', 'ar', 'إعدادات الرواتب والتعويضات'),
    ('salary_increment_settings', 'en', 'Salary Increment Settings'),
    ('salary_increment_settings', 'ar', 'إعدادات علاوة الراتب')
ON DUPLICATE KEY UPDATE translation = VALUES(translation);
