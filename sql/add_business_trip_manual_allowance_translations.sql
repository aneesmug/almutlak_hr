-- Translation keys for the "Add Manual Allowance" business trip modal
-- (openBusinessTripOtherAllowanceModal in assets/js/businessTrip.js)
-- Usage: Import this file into your database to add translation support

INSERT INTO `translations` (`lang_key`, `lang_code`, `translation`) VALUES
('add_manual_allowance', 'en', 'Add Manual Allowance'),
('add_manual_allowance', 'ar', 'إضافة بدل يدوي'),
('comment', 'en', 'Comment'),
('comment', 'ar', 'تعليق'),
('comment_placeholder', 'en', 'e.g. Taxi, Parking'),
('comment_placeholder', 'ar', 'مثال: تاكسي، وقوف السيارات'),
('comment_required', 'en', 'Please add a comment for every allowance entry.'),
('comment_required', 'ar', 'يرجى إضافة تعليق لكل بند من بنود البدل.'),
('at_least_one_allowance_entry', 'en', 'Add at least one allowance entry.'),
('at_least_one_allowance_entry', 'ar', 'أضف بندًا واحدًا على الأقل من بنود البدل.')
ON DUPLICATE KEY UPDATE `translation` = VALUES(`translation`);
