-- Add translation keys for GR Officer auto-addition message

INSERT INTO translations (`key`, lang, value) VALUES
('gr_officer_auto_added', 'en', 'GR Officer will be automatically added for exit & re-entry visa processing.'),
('gr_officer_auto_added', 'ar', 'سيتم إضافة موظف العلاقات الحكومية تلقائيا لمعالجة تأشيرة الخروج والعودة.')
ON DUPLICATE KEY UPDATE value=VALUES(value);
