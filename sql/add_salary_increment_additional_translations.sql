-- Additional translation keys for Salary Increment (GM approval fields, not-eligible modal,
-- salary information panel) added after add_salary_increment_translations.sql and
-- add_salary_increment_last_increment_date.sql / add_salary_increment_gm_approved_amount.sql.

INSERT INTO `translations` (`lang_key`, `lang_code`, `translation`) VALUES
('increment_effective_date_required', 'en', 'Increment effective date is required.'),
('increment_effective_date_required', 'ar', 'تاريخ سريان الزيادة مطلوب.'),

('increment_effective_date_must_be_future', 'en', 'Increment effective date cannot be a past date.'),
('increment_effective_date_must_be_future', 'ar', 'تاريخ سريان الزيادة لا يمكن أن يكون تاريخًا سابقًا.'),

('not_eligible_for_increment', 'en', 'Not Eligible Yet'),
('not_eligible_for_increment', 'ar', 'غير مؤهل بعد'),

('next_eligible_date', 'en', 'Next Eligible Date'),
('next_eligible_date', 'ar', 'تاريخ الأهلية القادم'),

('time_remaining', 'en', 'Time Remaining'),
('time_remaining', 'ar', 'الوقت المتبقي'),

('last_increment_too_recent_notice', 'en', 'This employee is not yet eligible for a new salary increment. Only one increment is allowed per year.'),
('last_increment_too_recent_notice', 'ar', 'هذا الموظف غير مؤهل بعد لزيادة راتب جديدة. يُسمح بزيادة واحدة فقط كل سنة.'),

('salary_information', 'en', 'Salary Information'),
('salary_information', 'ar', 'معلومات الراتب')
ON DUPLICATE KEY UPDATE `translation` = VALUES(`translation`);
