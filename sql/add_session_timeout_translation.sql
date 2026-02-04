-- Add translation for Set Session Timeout setting
-- This includes the HTML br tag and parenthetical text normalized

INSERT INTO `translations` (`lang_key`, `lang_code`, `translation`) 
VALUES 
('set_session_timeout_time_must_be_in_second', 'en', 'Set Session Timeout <br /> <small>(time must be in second)</small>'),
('set_session_timeout_time_must_be_in_second', 'ar', 'تعيين انتهاء صلاحية الجلسة <br /> <small>(يجب أن يكون الوقت بالثواني)</small>');

-- Verify the translation was added
SELECT * FROM `translations` WHERE `lang_key` = 'set_session_timeout_time_must_be_in_second';
