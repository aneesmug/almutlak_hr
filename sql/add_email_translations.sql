-- Add email-related translation keys
-- Execute this SQL in phpMyAdmin or MySQL command line

USE almutlak;

-- Insert English and Arabic translations for email settings
INSERT INTO `translations` (`lang_key`, `lang_code`, `translation`) 
VALUES 
('default_from_name_sender_display_name', 'en', 'Default From Name (Sender Display Name)'),
('default_from_name_sender_display_name', 'ar', 'اسم المرسل الافتراضي (اسم عرض المرسل)'),
('from_email_setting', 'en', 'From Email Address'),
('from_email_setting', 'ar', 'عنوان البريد الإلكتروني للمرسل'),
('from_name_setting', 'en', 'From Name'),
('from_name_setting', 'ar', 'اسم المرسل');

-- Verify the translations were added
SELECT * FROM `translations` WHERE `lang_key` LIKE 'default_from_name%' OR `lang_key` LIKE 'from_%';
