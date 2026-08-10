-- Adds an optional "date of last increment" column, settable by HR Payroll at approval time.
ALTER TABLE `emp_salary_increment`
    ADD COLUMN `last_increment_date` DATE NULL DEFAULT NULL AFTER `evaluation_score`;

INSERT INTO `translations` (`lang_key`, `lang_code`, `translation`) VALUES
('last_increment_date', 'en', 'Date of Last Increment (Optional)'),
('last_increment_date', 'ar', 'تاريخ آخر زيادة (اختياري)')
ON DUPLICATE KEY UPDATE `translation` = VALUES(`translation`);
