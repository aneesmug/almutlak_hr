-- Manager Evaluation Acknowledgment/Objection Translations

INSERT INTO `translations` (`lang_key`, `lang_code`, `translation`) VALUES
-- Acknowledgment/Objection Status
('manager_acknowledgment', 'en', 'Acknowledged'),
('manager_acknowledgment', 'ar', 'تم الإقرار'),
('manager_objection', 'en', 'Objected'),
('manager_objection', 'ar', 'تم الاعتراض'),
('manager_acknowledgment_pending', 'en', 'Pending Manager Acknowledgment'),
('manager_acknowledgment_pending', 'ar', 'في انتظار إقرار المدير'),

-- Action Labels
('acknowledge_evaluation', 'en', 'Acknowledge Evaluation'),
('acknowledge_evaluation', 'ar', 'الإقرار بالتقييم'),
('object_to_evaluation', 'en', 'Object to Evaluation'),
('object_to_evaluation', 'ar', 'الاعتراض على التقييم'),

-- Modal Labels
('manager_acknowledgment_title', 'en', 'Manager Evaluation Acknowledgment'),
('manager_acknowledgment_title', 'ar', 'إقرار المدير بالتقييم'),
('manager_objection_title', 'en', 'Manager Evaluation Objection'),
('manager_objection_title', 'ar', 'اعتراض المدير على التقييم'),

-- Prompts
('acknowledge_prompt', 'en', 'You are about to acknowledge this evaluation. Do you want to proceed?'),
('acknowledge_prompt', 'ar', 'أنت على وشك الإقرار بهذا التقييم. هل تريد المتابعة؟'),
('objection_prompt', 'en', 'Please provide your objection note/reason:'),
('objection_prompt', 'ar', 'يرجى إدخال ملاحظة/سبب اعتراضك:'),
('objection_required', 'en', 'Objection note is required when objecting to an evaluation'),
('objection_required', 'ar', 'ملاحظة الاعتراض مطلوبة عند الاعتراض على التقييم'),

-- Report Labels
('manager_acknowledgment_report', 'en', 'Manager Acknowledgment Report'),
('manager_acknowledgment_report', 'ar', 'تقرير إقرار المدير'),
('acknowledged_by', 'en', 'Acknowledged by'),
('acknowledged_by', 'ar', 'تم الإقرار من قبل'),
('objected_by', 'en', 'Objected by'),
('objected_by', 'ar', 'تم الاعتراض من قبل'),
('acknowledgment_date', 'en', 'Acknowledgment Date'),
('acknowledgment_date', 'ar', 'تاريخ الإقرار'),
('objection_reason', 'en', 'Objection Reason'),
('objection_reason', 'ar', 'سبب الاعتراض'),

-- Management View
('view_acknowledgments', 'en', 'View Acknowledgments'),
('view_acknowledgments', 'ar', 'عرض الإقرارات'),
('pending_acknowledgments', 'en', 'Pending Acknowledgments'),
('pending_acknowledgments', 'ar', 'الإقرارات المعلقة'),
('acknowledged_evaluations', 'en', 'Acknowledged Evaluations'),
('acknowledged_evaluations', 'ar', 'التقييمات المقرة'),
('objected_evaluations', 'en', 'Objected Evaluations'),
('objected_evaluations', 'ar', 'التقييمات المعترض عليها')
ON DUPLICATE KEY UPDATE `translation` = VALUES(`translation`);
