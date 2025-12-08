-- Add "Pending Payment" filter translation
-- This filter shows approved annual vacation (fly) requests that don't have payment details yet

INSERT INTO `translations` (`translation_id`, `lang_key`, `lang_code`, `translation`) VALUES
(NULL, 'pending_payment', 'en', 'Pending Payment'),
(NULL, 'pending_payment', 'ar', 'في انتظار الدفع')
ON DUPLICATE KEY UPDATE `translation` = VALUES(`translation`);
