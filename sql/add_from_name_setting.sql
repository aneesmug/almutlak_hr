-- Add from_name setting to app_settings table
-- Execute this SQL in phpMyAdmin or MySQL command line

USE almutlak;

INSERT INTO `app_settings` (`setting_name`, `setting_value`, `setting_group`, `description`, `input_type`, `options`) 
VALUES ('from_name', 'Al Mutlak HR System', 'email', 'default_from_name_sender_display_name', 'text', NULL);

-- Verify the setting was added
SELECT * FROM `app_settings` WHERE `setting_name` = 'from_name';
