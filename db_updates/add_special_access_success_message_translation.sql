-- Success confirmation shown after saving a user's access in the Special Access edit modal
-- (App Settings -> Special Access). Wording clarifies the change is staged and still needs
-- the page-level "Save Changes" button, since the modal only updates local state / hidden
-- inputs - it does not write to the DB itself. Idempotent: safe to re-run.

INSERT INTO `translations` (`lang_key`, `lang_code`, `translation`) VALUES
('access_updated_click_save_changes_to_apply', 'en', 'Access updated. Click Save Changes at the bottom to apply.'),
('access_updated_click_save_changes_to_apply', 'ar', 'تم تحديث الصلاحيات. اضغط على حفظ التغييرات أسفل الصفحة لتطبيقها.'),
('updated', 'en', 'Updated'),
('updated', 'ar', 'تم التحديث')
ON DUPLICATE KEY UPDATE translation = VALUES(translation);
