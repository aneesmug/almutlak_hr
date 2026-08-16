-- Small hint text shown under the "Select user" dropdown in App Settings -> Special Access,
-- now that picking a user always opens the SweetAlert2 editor modal (inline editing removed).
-- Idempotent: safe to re-run.

INSERT INTO `translations` (`lang_key`, `lang_code`, `translation`) VALUES
('picking_a_user_opens_the_access_editor', 'en', 'Picking a user opens the access editor.'),
('picking_a_user_opens_the_access_editor', 'ar', 'اختيار المستخدم يفتح محرر الصلاحيات.')
ON DUPLICATE KEY UPDATE translation = VALUES(translation);
