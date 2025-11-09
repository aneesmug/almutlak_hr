-- Add from_name setting to app_settings table
-- Execute this SQL in phpMyAdmin or MySQL command line

USE almutlak;

INSERT INTO `app_settings` (`setting_name`, `setting_value`, `setting_group`, `description`, `input_type`, `options`) 
VALUES ('from_name', 'Al Mutlak HR System', 'email', 'Default From Name (Sender Display Name)', 'text', NULL);

-- Verify the setting was added
SELECT * FROM `app_settings` WHERE `setting_name` = 'from_name';
