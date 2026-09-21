-- Adds "ticket officer" email (CC on travel-company emails) to App Settings > General.
-- Value: one email, or several separated by comma.
INSERT INTO `app_settings` (`setting_name`, `setting_value`, `setting_group`, `description`, `input_type`, `options`)
SELECT 'gr_officer_email', '', 'general', 'Email address of the employee who handles tickets (CC on travel company emails)', 'text', NULL
FROM DUAL
WHERE NOT EXISTS (SELECT 1 FROM `app_settings` WHERE `setting_name` = 'gr_officer_email');

-- If an old row exists in another group, move it to General.
UPDATE `app_settings`
SET `setting_group` = 'general',
    `description` = 'Email address of the employee who handles tickets (CC on travel company emails)',
    `input_type` = 'text'
WHERE `setting_name` = 'gr_officer_email';
