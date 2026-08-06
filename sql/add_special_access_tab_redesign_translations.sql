-- Translation keys for the redesigned Special Access tab (App Settings) - modal-based
-- grant/edit flow with Assigned/Unassigned sections.

INSERT INTO `translations` (`lang_key`, `lang_code`, `translation`) VALUES
('grant_access_to_a_user', 'en', 'Grant Access to a User'),
('grant_access_to_a_user', 'ar', 'منح صلاحية لمستخدم'),
('manage_access', 'en', 'Manage Access'),
('manage_access', 'ar', 'إدارة الصلاحيات'),
('manage_special_access', 'en', 'Manage Special Access'),
('manage_special_access', 'ar', 'إدارة الصلاحيات الخاصة'),
('unassigned', 'en', 'Unassigned'),
('unassigned', 'ar', 'غير ممنوحة'),
('no_access_assigned_yet', 'en', 'Nothing assigned yet.'),
('no_access_assigned_yet', 'ar', 'لم يتم منح أي صلاحية بعد.'),
('everything_is_assigned', 'en', 'Everything is assigned.'),
('everything_is_assigned', 'ar', 'تم منح جميع الصلاحيات.')
ON DUPLICATE KEY UPDATE `translation` = VALUES(`translation`);
