-- Translations for the Apply Vacation "Fly locked by Iqama / Passport expiry" card
-- (applyFlyDocBlock in assets/js/jquery.app.js + assets/js/employee_profile.js) and its
-- "Notify responsible person" button. Keys that already exist (expired, required, sending,
-- employee, an_error_occurred) are not touched.
-- {days} in fly_vacation_documents_rule is replaced in JS with the minimum days (90).
-- Idempotent: safe to re-run.

INSERT INTO `translations` (`lang_key`, `lang_code`, `translation`) VALUES
('documents_update_required', 'en', 'Update Documents Required'),
('documents_update_required', 'ar', 'مطلوب تحديث المستندات'),
('fly_vacation_locked', 'en', 'Fly vacation is locked'),
('fly_vacation_locked', 'ar', 'إجازة السفر مقفلة'),
('fly_vacation_documents_rule', 'en', 'Fly vacation needs Iqama and Passport valid for more than {days} days.'),
('fly_vacation_documents_rule', 'ar', 'تتطلب إجازة السفر أن تكون الإقامة وجواز السفر صالحة لأكثر من {days} يوماً.'),
('not_updated', 'en', 'Not updated'),
('not_updated', 'ar', 'غير محدث'),
('expiry_not_updated_in_system', 'en', 'Expiry date is not updated in the system'),
('expiry_not_updated_in_system', 'ar', 'تاريخ الانتهاء غير محدث في النظام'),
('expired_on', 'en', 'Expired on'),
('expired_on', 'ar', 'انتهت في'),
('expires_on', 'en', 'Expires on'),
('expires_on', 'ar', 'تنتهي في'),
('days_left', 'en', 'day(s) left'),
('days_left', 'ar', 'يوم متبقي'),
('update_documents_before_applying', 'en', 'Please update the document(s) above with HR before applying.'),
('update_documents_before_applying', 'ar', 'يرجى تحديث المستندات أعلاه لدى الموارد البشرية قبل التقديم.'),
('notify_document_responsible', 'en', 'Notify responsible person'),
('notify_document_responsible', 'ar', 'إشعار الشخص المسؤول'),
('notification_sent', 'en', 'Notification sent'),
('notification_sent', 'ar', 'تم إرسال الإشعار'),
('cc_sent_to', 'en', 'CC'),
('cc_sent_to', 'ar', 'نسخة إلى'),
('email_failed_for', 'en', 'Failed for'),
('email_failed_for', 'ar', 'فشل الإرسال إلى')
ON DUPLICATE KEY UPDATE translation = VALUES(translation);
