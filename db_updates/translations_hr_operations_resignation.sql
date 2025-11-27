-- ===== Resignation HR Operations Approval Workflow - Translation Keys =====
-- Added: 2025-11-26
-- Purpose: Bilingual translations for HR Operations resignation approval wizard

INSERT INTO `translations` (`lang_key`, `lang_code`, `translation`) VALUES

-- Exit Interview Questions Display Labels
('exit_interview_questions', 'en', 'Exit Interview Questions'),
('exit_interview_questions', 'ar', 'أسئلة المقابلة الشاملة'),

-- Replacement Information Labels
('replacement_job_details', 'en', 'Replacement Job Details'),
('replacement_job_details', 'ar', 'تفاصيل وظيفة الموظف البديل'),

('replacement_info_from_manager', 'en', 'Information provided by Direct Manager'),
('replacement_info_from_manager', 'ar', 'المعلومات المقدمة من المدير المباشر'),

('no_replacement_needed', 'en', 'No replacement employee is needed for this position.'),
('no_replacement_needed', 'ar', 'لا توجد حاجة لموظف بديل لهذا المنصب.'),

('replacement_info_not_available', 'en', 'Replacement Information Not Available'),
('replacement_info_not_available', 'ar', 'معلومات الاستبدال غير متاحة'),

('no_replacement_info', 'en', 'Replacement information was not provided by the direct manager.'),
('no_replacement_info', 'ar', 'لم توفر معلومات الاستبدال من قبل المدير المباشر.'),

-- Last Working Day Labels
('last_working_day_employee', 'en', 'Last Workday by Employee'),
('last_working_day_employee', 'ar', 'آخر يوم عمل حسب الموظف'),
('last_working_day_hr', 'en', 'Last Workday by HR'),
('last_working_day_hr', 'ar', 'آخر يوم عمل حسب الموارد البشرية'),

-- Replacement Details Labels (with numbering for HR view)
('job_title', 'en', 'Job Title of the Replacement'),
('job_title', 'ar', 'مسمى الوظيفة للموظف البديل'),

('job_description', 'en', 'Job Description'),
('job_description', 'ar', 'وصف الوظيفة'),

('experience', 'en', 'Experience'),
('experience', 'ar', 'الخبرة'),

('certificate', 'en', 'Certificate'),
('certificate', 'ar', 'الشهادة'),

('academic_achievement', 'en', 'Academic Achievement'),
('academic_achievement', 'ar', 'الإنجازات الأكاديمية'),

('date_of_joining', 'en', 'Date of Joining'),
('date_of_joining', 'ar', 'تاريخ بدء العمل'),

-- Form Placeholders
('enter_job_title', 'en', 'Enter job title'),
('enter_job_title', 'ar', 'أدخل مسمى الوظيفة'),

('enter_job_description', 'en', 'Enter job description'),
('enter_job_description', 'ar', 'أدخل وصف الوظيفة'),

('enter_experience_required', 'en', 'e.g., 3-5 years'),
('enter_experience_required', 'ar', 'مثال: 3-5 سنوات'),

('enter_required_certificates', 'en', 'Enter required certificates'),
('enter_required_certificates', 'ar', 'أدخل الشهادات المطلوبة'),

('enter_academic_requirements', 'en', 'e.g., Bachelor degree in...'),
('enter_academic_requirements', 'ar', 'مثال: درجة البكالوريوس في...'),

('fill_replacement_fields', 'en', 'Please fill in the required fields below:'),
('fill_replacement_fields', 'ar', 'يرجى ملء الحقول المطلوبة أدناه:'),

('fill_all_required_fields', 'en', 'Please fill in all required fields'),
('fill_all_required_fields', 'ar', 'يرجى ملء جميع الحقول المطلوبة'),

-- Approval Actions
('approve', 'en', 'APPROVE'),
('approve', 'ar', 'موافقة'),

('reject', 'en', 'REJECT'),
('reject', 'ar', 'رفض'),

('back', 'en', 'BACK'),
('back', 'ar', 'رجوع'),

('next', 'en', 'NEXT'),
('next', 'ar', 'التالي'),

('cancel', 'en', 'Cancel'),
('cancel', 'ar', 'إلغاء'),

-- Validation Messages
('last_working_day_hr_required', 'en', 'Last Workday by HR is required'),
('last_working_day_hr_required', 'ar', 'آخر يوم عمل حسب الموارد البشرية مطلوب'),

-- Generic Labels
('no', 'en', 'NO'),
('no', 'ar', 'لا'),

('yes', 'en', 'YES'),
('yes', 'ar', 'نعم'),

('processing', 'en', 'Processing...'),
('processing', 'ar', 'جاري المعالجة...'),

('please_wait', 'en', 'Please wait while we process your approval'),
('please_wait', 'ar', 'يرجى الانتظار بينما نعالج موافقتك'),

('success', 'en', 'Success'),
('success', 'ar', 'نجح'),

('error', 'en', 'Error'),
('error', 'ar', 'خطأ'),

('ok', 'en', 'OK'),
('ok', 'ar', 'موافق'),

-- HR Payroll Final Approval Labels
('resignation_final_approval', 'en', 'Resignation - Final Approval'),
('resignation_final_approval', 'ar', 'الاستقالة - الموافقة النهائية'),

('all_approvals_completed', 'en', 'All Prior Approvals Completed'),
('all_approvals_completed', 'ar', 'تم إكمال جميع الموافقات السابقة'),

('hr_payroll_final_review', 'en', 'As HR Payroll, you are conducting the final review before creating the End of Service record.'),
('hr_payroll_final_review', 'ar', 'كموظف الرواتب في الموارد البشرية، أنت تقوم بالمراجعة النهائية قبل إنشاء سجل نهاية الخدمة.'),

('exit_interview_summary', 'en', 'Exit Interview Summary'),
('exit_interview_summary', 'ar', 'ملخص المقابلة الشاملة'),

('approve_create_eos', 'en', 'Approve & Create EOS'),
('approve_create_eos', 'ar', 'وافق وأنشئ نهاية الخدمة'),

('resignation_approved_eos_created', 'en', 'Resignation has been approved and End of Service record has been created'),
('resignation_approved_eos_created', 'ar', 'تمت الموافقة على الاستقالة وتم إنشاء سجل نهاية الخدمة');
