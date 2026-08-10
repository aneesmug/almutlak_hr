-- Translation keys for the Salary Increment Request feature
-- (all_applied_salary_increment.php, salary_increment_status_history.php,
--  assets/js/salaryIncrement.js, includes/ajaxFile/ajaxSalaryIncrement.php,
--  includes/emp_top_info.php, includes/main_menu.php)
-- Usage: Import this file into your database to add translation support

INSERT INTO `translations` (`lang_key`, `lang_code`, `translation`) VALUES
('apply_salary_increment', 'en', 'Apply Salary Increment'),
('apply_salary_increment', 'ar', 'تقديم طلب زيادة راتب'),

('salary_increment', 'en', 'Salary Increment'),
('salary_increment', 'ar', 'زيادة الراتب'),

('salary_increment_requests', 'en', 'Salary Increment Requests'),
('salary_increment_requests', 'ar', 'طلبات زيادة الراتب'),

('salary_increment_approval_center', 'en', 'Salary Increment Approval Center'),
('salary_increment_approval_center', 'ar', 'مركز اعتماد زيادة الراتب'),

('salary_increment_approval_history', 'en', 'Salary Increment Approval History'),
('salary_increment_approval_history', 'ar', 'سجل اعتماد زيادة الراتب'),

('no_salary_increment_requests_found', 'en', 'No salary increment requests found'),
('no_salary_increment_requests_found', 'ar', 'لم يتم العثور على طلبات زيادة راتب'),

('submit_salary_increment_request', 'en', 'Submit'),
('submit_salary_increment_request', 'ar', 'إرسال'),

('salary_increment_submitted', 'en', 'Salary increment request submitted successfully.'),
('salary_increment_submitted', 'ar', 'تم تقديم طلب زيادة الراتب بنجاح.'),

('you_already_have_active_salary_increment', 'en', 'You already have an active salary increment request for this employee. A new request is not allowed until the current one is completed.'),
('you_already_have_active_salary_increment', 'ar', 'لديك بالفعل طلب زيادة راتب نشط لهذا الموظف. لا يُسمح بتقديم طلب جديد حتى يتم إكمال الطلب الحالي.'),

('you_can_only_cancel_your_own_requests', 'en', 'You can only cancel your own requests'),
('you_can_only_cancel_your_own_requests', 'ar', 'يمكنك فقط إلغاء طلباتك الخاصة'),

('cancel_salary_increment_request', 'en', 'Cancel Salary Increment Request'),
('cancel_salary_increment_request', 'ar', 'إلغاء طلب زيادة الراتب'),

('confirm_cancel_salary_increment_for', 'en', 'Are you sure you want to cancel the salary increment request for'),
('confirm_cancel_salary_increment_for', 'ar', 'هل أنت متأكد من رغبتك في إلغاء طلب زيادة الراتب الخاص بـ'),

('cancellation_reason', 'en', 'Cancellation Reason'),
('cancellation_reason', 'ar', 'سبب الإلغاء'),

('cancellation_reason_required_validation', 'en', 'Cancellation reason is required'),
('cancellation_reason_required_validation', 'ar', 'سبب الإلغاء مطلوب'),

('enter_cancellation_reason_placeholder', 'en', 'Enter reason for cancelling this request'),
('enter_cancellation_reason_placeholder', 'ar', 'أدخل سبب إلغاء هذا الطلب'),

('yes_cancel', 'en', 'Yes, Cancel'),
('yes_cancel', 'ar', 'نعم، إلغاء'),

('access_denied', 'en', 'Access denied'),
('access_denied', 'ar', 'تم رفض الوصول'),

('increment_amount', 'en', 'Increment Amount'),
('increment_amount', 'ar', 'مبلغ الزيادة'),

('increment_details', 'en', 'Increment Details'),
('increment_details', 'ar', 'تفاصيل الزيادة'),

('max_2000', 'en', 'Max 2000'),
('max_2000', 'ar', 'الحد الأقصى 2000'),

('submitted_by', 'en', 'Submitted By'),
('submitted_by', 'ar', 'مُقدَّم من'),

('submitted_by_me', 'en', 'Submitted By Me'),
('submitted_by_me', 'ar', 'المقدَّمة مني'),

('years_of_service', 'en', 'Years of Service'),
('years_of_service', 'ar', 'سنوات الخدمة'),

('current_year_evaluation', 'en', 'Current Year Evaluation'),
('current_year_evaluation', 'ar', 'تقييم السنة الحالية'),

('evaluation_score', 'en', 'Evaluation Score'),
('evaluation_score', 'ar', 'درجة التقييم'),

('evaluated_score', 'en', 'Evaluated'),
('evaluated_score', 'ar', 'تم التقييم'),

('evaluation_required_hint', 'en', 'This employee must have a current-year evaluation from you before you can submit this request.'),
('evaluation_required_hint', 'ar', 'يجب أن يكون لدى هذا الموظف تقييم للسنة الحالية منك قبل أن تتمكن من تقديم هذا الطلب.'),

('no_current_year_evaluation_hint', 'en', 'No current-year evaluation found for this employee. Please evaluate them first.'),
('no_current_year_evaluation_hint', 'ar', 'لم يتم العثور على تقييم للسنة الحالية لهذا الموظف. يرجى تقييمه أولاً.'),

('go_to_evaluation_page', 'en', 'Go to Evaluation Page'),
('go_to_evaluation_page', 'ar', 'الذهاب إلى صفحة التقييم'),

('recheck', 'en', 'Recheck'),
('recheck', 'ar', 'إعادة التحقق'),

('checking', 'en', 'Checking...'),
('checking', 'ar', 'جارٍ التحقق...'),

('last_increment', 'en', 'Last Increment'),
('last_increment', 'ar', 'آخر زيادة'),

('last_increment_on', 'en', 'Last increment'),
('last_increment_on', 'ar', 'آخر زيادة'),

('no_previous_increment', 'en', 'No previous increment on record.'),
('no_previous_increment', 'ar', 'لا توجد زيادة سابقة مسجلة.'),

('last_increment_too_recent', 'en', 'This employee already received an increment of'),
('last_increment_too_recent', 'ar', 'حصل هذا الموظف بالفعل على زيادة بمقدار'),

('last_increment_too_recent_short', 'en', 'This employee received an increment less than a year ago. Not eligible yet.'),
('last_increment_too_recent_short', 'ar', 'حصل هذا الموظف على زيادة قبل أقل من سنة. غير مؤهل بعد.'),

('next_increment_eligible_in', 'en', 'Another increment is not allowed for about'),
('next_increment_eligible_in', 'ar', 'لا يُسمح بزيادة أخرى لمدة تقارب'),

('more_months', 'en', 'more month(s).'),
('more_months', 'ar', 'شهر/أشهر إضافية.'),

('no_approvers', 'en', 'No approvers found'),
('no_approvers', 'ar', 'لا يوجد معتمدون'),

('no_data_found', 'en', 'No data found'),
('no_data_found', 'ar', 'لا توجد بيانات')
ON DUPLICATE KEY UPDATE `translation` = VALUES(`translation`);
