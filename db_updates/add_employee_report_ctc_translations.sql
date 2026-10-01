-- Translations for the extended Employee Report columns (location / CTC fields).
-- Most keys already exist from add_ctc_report_translations.sql; only new ones here.
-- Idempotent: safe to re-run.

INSERT INTO `translations` (`lang_key`, `lang_code`, `translation`) VALUES
('gosi_amount', 'en', 'GOSI Amount'),
('gosi_amount', 'ar', 'مبلغ التأمينات الاجتماعية')
ON DUPLICATE KEY UPDATE translation = VALUES(translation);
