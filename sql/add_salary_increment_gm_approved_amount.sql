-- GM approval step: editable final approved amount + required increment effective date.
ALTER TABLE `emp_salary_increment`
    ADD COLUMN `approved_amount` DECIMAL(10,2) NULL DEFAULT NULL AFTER `increment_amount`;

INSERT INTO `translations` (`lang_key`, `lang_code`, `translation`) VALUES
('increment_effective_date', 'en', 'Increment Effective Date'),
('increment_effective_date', 'ar', 'تاريخ سريان الزيادة')
ON DUPLICATE KEY UPDATE `translation` = VALUES(`translation`);
