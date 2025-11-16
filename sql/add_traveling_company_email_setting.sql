-- Add traveling company email setting to app_settings table
-- This email will be used to send employee travel information when annual fly vacations are approved

INSERT INTO `app_settings` (`setting_name`, `setting_value`, `description`) 
VALUES ('traveling_company_email', 'travel@example.com', 'Email address of the traveling company to receive employee travel notifications')
ON DUPLICATE KEY UPDATE 
    `description` = 'Email address of the traveling company to receive employee travel notifications';

-- Add GR Officer email setting for CC
INSERT INTO `app_settings` (`setting_name`, `setting_value`, `description`) 
VALUES ('gr_officer_email', 'gr@example.com', 'Email address of the GR Officer to receive CC of travel notifications')
ON DUPLICATE KEY UPDATE 
    `description` = 'Email address of the GR Officer to receive CC of travel notifications';

-- Instructions:
-- 1. Run this SQL script in your database
-- 2. Update the 'traveling_company_email' value in the app_settings table with the actual email address
-- 3. Update the 'gr_officer_email' value in the app_settings table with the actual GR Officer email
-- 
-- Example:
-- UPDATE `app_settings` SET `setting_value` = 'actual-email@travelcompany.com' WHERE `setting_name` = 'traveling_company_email';
-- UPDATE `app_settings` SET `setting_value` = 'gr.officer@yourcompany.com' WHERE `setting_name` = 'gr_officer_email';
