-- Fixes Arabic translations for the Salary Increment feature.
--
-- Root cause: the Arabic text in the original migration files
-- (sql/add_salary_increment_translations.sql, add_salary_increment_additional_translations.sql,
-- etc.) was correct UTF-8, but got corrupted on import into this database - either
-- mangled into mojibake, or lost entirely into literal "?" characters. A few other keys
-- (apply_salary_increment, submit_salary_increment_request, salary_increment_settings,
-- salary_increment_report) were readable but genuinely mistranslated / inconsistent
-- wording ("تطبيق" instead of "تقديم طلب", a conjugated verb instead of a button label,
-- "علاوة" instead of "زيادة" used everywhere else in this feature).
--
-- IMPORTANT when importing: use a UTF-8/utf8mb4 connection, e.g.
--   mysql --default-character-set=utf8mb4 -u USER -p DBNAME < fix_salary_increment_translations.sql
-- or phpMyAdmin -> Import with charset set to utf8mb4, otherwise this same corruption
-- will happen again on the way in.

INSERT INTO `translations` (`lang_key`, `lang_code`, `translation`) VALUES
('apply_salary_increment', 'ar', 'تقديم طلب زيادة راتب'),
('submit_salary_increment_request', 'ar', 'إرسال'),
('salary_increment_settings', 'ar', 'إعدادات زيادة الراتب'),
('salary_increment_report', 'ar', 'تقرير زيادة الراتب'),

('increment_amount', 'ar', 'مبلغ الزيادة'),
('increment_details', 'ar', 'تفاصيل الزيادة'),
('increment_effective_date', 'ar', 'تاريخ سريان الزيادة'),
('increment_effective_date_must_be_future', 'ar', 'تاريخ سريان الزيادة لا يمكن أن يكون تاريخًا سابقًا.'),
('increment_effective_date_required', 'ar', 'تاريخ سريان الزيادة مطلوب.'),
('last_increment', 'ar', 'آخر زيادة'),
('last_increment_date', 'ar', 'تاريخ آخر زيادة (اختياري)'),
('last_increment_on', 'ar', 'آخر زيادة'),
('last_increment_too_recent', 'ar', 'حصل هذا الموظف بالفعل على زيادة بمقدار'),
('last_increment_too_recent_short', 'ar', 'حصل هذا الموظف على زيادة قبل أقل من سنة. غير مؤهل بعد.'),
('next_increment_eligible_in', 'ar', 'لا يُسمح بزيادة أخرى لمدة تقارب'),
('not_eligible_for_increment', 'ar', 'غير مؤهل بعد'),
('no_previous_increment', 'ar', 'لا توجد زيادة سابقة مسجلة.'),
('next_eligible_date', 'ar', 'تاريخ الأهلية القادم'),
('time_remaining', 'ar', 'الوقت المتبقي'),
('salary_information', 'ar', 'معلومات الراتب'),

('cancellation_reason', 'ar', 'سبب الإلغاء'),
('cancellation_reason_required_validation', 'ar', 'سبب الإلغاء مطلوب'),
('enter_cancellation_reason_placeholder', 'ar', 'أدخل سبب إلغاء هذا الطلب'),
('yes_cancel', 'ar', 'نعم، إلغاء'),
('access_denied', 'ar', 'تم رفض الوصول'),
('max_2000', 'ar', 'الحد الأقصى 2000'),
('submitted_by', 'ar', 'مُقدَّم من'),
('submitted_by_me', 'ar', 'المقدَّمة مني'),
('years_of_service', 'ar', 'سنوات الخدمة'),
('current_year_evaluation', 'ar', 'تقييم السنة الحالية'),
('evaluation_score', 'ar', 'درجة التقييم'),
('evaluated_score', 'ar', 'تم التقييم'),
('evaluation_required_hint', 'ar', 'يجب أن يكون لدى هذا الموظف تقييم للسنة الحالية منك قبل أن تتمكن من تقديم هذا الطلب.'),
('no_current_year_evaluation_hint', 'ar', 'لم يتم العثور على تقييم للسنة الحالية لهذا الموظف. يرجى تقييمه أولاً.'),
('go_to_evaluation_page', 'ar', 'الذهاب إلى صفحة التقييم'),
('recheck', 'ar', 'إعادة التحقق'),
('checking', 'ar', 'جارٍ التحقق...'),
('more_months', 'ar', 'شهر/أشهر إضافية.'),
('no_approvers', 'ar', 'لا يوجد معتمدون'),
('no_data_found', 'ar', 'لا توجد بيانات'),
('you_can_only_cancel_your_own_requests', 'ar', 'يمكنك فقط إلغاء طلباتك الخاصة')
ON DUPLICATE KEY UPDATE `translation` = VALUES(`translation`);
