-- Employee header (includes/emp_top_info.php): "Vacation Due" tile = balance days x (monthly salary / 30).
INSERT INTO `translations` (`lang_key`, `lang_code`, `translation`) VALUES
('vacation_due', 'en', 'Vacation Due'),
('vacation_due', 'ar', 'مستحقات الإجازة'),
('vacation_due_hint', 'en', '%1$s days x %2$s daily salary (monthly salary / 30)'),
('vacation_due_hint', 'ar', '%1$s يوم × %2$s أجر يومي (الراتب الشهري ÷ 30)')
ON DUPLICATE KEY UPDATE `translation` = VALUES(`translation`);

-- Keys from the earlier date-based version, no longer used
DELETE FROM `translations` WHERE `lang_key` IN ('due_now', 'vacation_due_now_hint');
