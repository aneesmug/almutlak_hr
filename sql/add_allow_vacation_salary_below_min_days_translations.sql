-- Translations for the "Allow Vacation Salary Payout Below Minimum Days" employee master flag
-- Table: translations (lang_key, lang_code, translation)

INSERT INTO `translations` (`lang_key`, `lang_code`, `translation`) VALUES
('allow_vacation_salary_below_min_days', 'en', 'Allow Vacation Salary Payout Below Minimum Days'),
('allow_vacation_salary_below_min_days_hint', 'en', 'When Yes, this employee is still paid the vacation salary (and removed from active payroll) for a Local Annual (Saudi) vacation even when approved days are below {days}.'),
('vacation_salary_below_min_days_override_note', 'en', 'This employee is individually authorized (Employee Master) to receive the vacation salary payout even though the approved days are below the normal minimum.')
ON DUPLICATE KEY UPDATE `translation` = VALUES(`translation`);

INSERT INTO `translations` (`lang_key`, `lang_code`, `translation`) VALUES
('allow_vacation_salary_below_min_days', 'ar', 'السماح بصرف راتب الإجازة رغم قلة الأيام عن الحد الأدنى'),
('allow_vacation_salary_below_min_days_hint', 'ar', 'عند اختيار نعم، يُصرف لهذا الموظف راتب الإجازة (ويُستبعد من الرواتب النشطة) لإجازة سنوية محلية (سعودية) حتى لو كانت الأيام المعتمدة أقل من {days}.'),
('vacation_salary_below_min_days_override_note', 'ar', 'تم تفويض هذا الموظف بشكل فردي (من ملف الموظف) لصرف راتب الإجازة رغم أن الأيام المعتمدة أقل من الحد الأدنى المعتاد.')
ON DUPLICATE KEY UPDATE `translation` = VALUES(`translation`);
