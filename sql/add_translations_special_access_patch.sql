-- Migration: Add missing translations for the Special Access / Request Blocking patch
-- Date: 2026-07-14
-- Purpose: Populate `translations` (en + ar) for new lang_keys introduced by the
--          Special Access tab, employee request-blocking UI, vacation report balance
--          display, and Vacation Approval Center filters/cancel action.
-- Safe to re-run: INSERT IGNORE against the existing unique (lang_key, lang_code) index.

INSERT IGNORE INTO `translations` (`lang_key`, `lang_code`, `translation`) VALUES
('special_access', 'en', 'Special Access'),
('special_access', 'ar', 'صلاحيات خاصة'),

('special_access_by_user', 'en', 'Special Access By User'),
('special_access_by_user', 'ar', 'الصلاحيات الخاصة حسب المستخدم'),

('select_a_user_and_grant_them_specific_admin_hr_abilities', 'en', 'Select a user and grant them specific admin/HR abilities'),
('select_a_user_and_grant_them_specific_admin_hr_abilities', 'ar', 'اختر مستخدمًا وامنحه صلاحيات إدارية/موارد بشرية محددة'),

('select_user_to_configure_special_access', 'en', 'Select a user to configure their special access'),
('select_user_to_configure_special_access', 'ar', 'اختر مستخدمًا لتهيئة صلاحياته الخاصة'),

('check_the_special_abilities_this_user_should_have', 'en', 'Check the special abilities this user should have'),
('check_the_special_abilities_this_user_should_have', 'ar', 'حدد الصلاحيات الخاصة التي يجب أن يمتلكها هذا المستخدم'),

('this_will_remove_special_access_for_this_user', 'en', 'This will remove all special access for this user'),
('this_will_remove_special_access_for_this_user', 'ar', 'سيؤدي هذا إلى إزالة جميع الصلاحيات الخاصة لهذا المستخدم'),

('request_restrictions', 'en', 'Request Restrictions'),
('request_restrictions', 'ar', 'قيود الطلبات'),

('block_from_all_requests', 'en', 'Block From All Requests'),
('block_from_all_requests', 'ar', 'حظر من جميع الطلبات'),

('block_specific_request_types', 'en', 'Block Specific Request Types'),
('block_specific_request_types', 'ar', 'حظر أنواع طلبات محددة'),

('blocked_from_all_requests', 'en', 'Blocked From All Requests'),
('blocked_from_all_requests', 'ar', 'محظور من جميع الطلبات'),

('blocked_request_types', 'en', 'Blocked Request Types'),
('blocked_request_types', 'ar', 'أنواع الطلبات المحظورة'),

('remaining_vacation_balance', 'en', 'Remaining Vacation Balance'),
('remaining_vacation_balance', 'ar', 'رصيد الإجازة المتبقي'),

('min_days', 'en', 'Min Days'),
('min_days', 'ar', 'أقل عدد أيام'),

('max_days', 'en', 'Max Days'),
('max_days', 'ar', 'أكثر عدد أيام'),

('cancel_request', 'en', 'Cancel Request'),
('cancel_request', 'ar', 'إلغاء الطلب'),

('confirm_cancellation', 'en', 'Confirm Cancellation'),
('confirm_cancellation', 'ar', 'تأكيد الإلغاء'),

('are_you_sure_cancel_request', 'en', 'Are you sure you want to cancel this request?'),
('are_you_sure_cancel_request', 'ar', 'هل أنت متأكد أنك تريد إلغاء هذا الطلب؟'),

('yes_cancel_it', 'en', 'Yes, Cancel It'),
('yes_cancel_it', 'ar', 'نعم، قم بإلغائه');
