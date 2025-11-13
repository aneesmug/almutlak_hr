-- Insert loan-related translations for English (en) and Arabic (ar)
-- Generated on 2025-11-11

-- Loan Type Labels
INSERT INTO `translations` (`lang_key`, `lang_code`, `translation`) VALUES
('loan_type_label', 'en', 'Loan Type'),
('loan_type_label', 'ar', 'نوع القرض'),
('loan_type_end_of_service', 'en', 'End of Service'),
('loan_type_end_of_service', 'ar', 'قرض نهاية الخدمة'),
('loan_type_housing', 'en', 'Housing Loan'),
('loan_type_housing', 'ar', 'قرض السكن'),
('loan_type_advance_salary', 'en', 'Advance Salary'),
('loan_type_advance_salary', 'ar', 'سلفة راتب'),

-- Loan Type Descriptions
('loan_desc_end_of_service', 'en', 'Based on your end of service amount'),
('loan_desc_end_of_service', 'ar', 'بناءً على مبلغ نهاية الخدمة الخاص بك'),
('loan_desc_housing', 'en', 'Based on your housing allowance'),
('loan_desc_housing', 'ar', 'بناءً على بدل السكن الخاص بك'),
('loan_desc_advance_salary', 'en', 'Up to 50% of your monthly salary'),
('loan_desc_advance_salary', 'ar', 'حتى 50% من راتبك الشهري'),

-- Form Labels
('loan_amount_label', 'en', 'Loan Amount (SAR)'),
('loan_amount_label', 'ar', 'مبلغ القرض (ريال)'),
('loan_installments_label', 'en', 'Number of Installments'),
('loan_installments_label', 'ar', 'عدد الأقساط'),
('loan_monthly_deduction_label', 'en', 'Monthly Deduction'),
('loan_monthly_deduction_label', 'ar', 'الخصم الشهري'),
('loan_reason_label', 'en', 'Reason for Loan'),
('loan_reason_label', 'ar', 'سبب القرض'),

-- Installment Options
('loan_installment_months', 'en', 'months'),
('loan_installment_months', 'ar', 'شهر'),
('loan_installment_month', 'en', 'month'),
('loan_installment_month', 'ar', 'شهر واحد'),

-- Button Labels
('btn_submit_loan', 'en', 'Submit Loan Application'),
('btn_submit_loan', 'ar', 'تقديم طلب القرض'),
('btn_cancel', 'en', 'Cancel'),
('btn_cancel', 'ar', 'إلغاء'),

-- Eligibility Messages
('loan_eligible_message', 'en', 'You are eligible for this loan type'),
('loan_eligible_message', 'ar', 'أنت مؤهل للحصول على هذا النوع من القرض'),
('loan_max_amount_info', 'en', 'Maximum amount'),
('loan_max_amount_info', 'ar', 'الحد الأقصى للمبلغ'),
('loan_min_amount_info', 'en', 'Minimum amount'),
('loan_min_amount_info', 'ar', 'الحد الأدنى للمبلغ'),
('loan_installments_range', 'en', 'Installments'),
('loan_installments_range', 'ar', 'الأقساط'),

-- Validation Messages
('loan_error_select_type', 'en', 'Please select a loan type'),
('loan_error_select_type', 'ar', 'يرجى اختيار نوع القرض'),
('loan_error_amount_required', 'en', 'Please enter the loan amount'),
('loan_error_amount_required', 'ar', 'يرجى إدخال مبلغ القرض'),
('loan_error_amount_min', 'en', 'Loan amount must be at least'),
('loan_error_amount_min', 'ar', 'يجب أن يكون مبلغ القرض على الأقل'),
('loan_error_amount_max', 'en', 'Loan amount cannot exceed'),
('loan_error_amount_max', 'ar', 'لا يمكن أن يتجاوز مبلغ القرض'),
('loan_error_installments_required', 'en', 'Please select number of installments'),
('loan_error_installments_required', 'ar', 'يرجى تحديد عدد الأقساط'),
('loan_error_reason_required', 'en', 'Please provide a reason for the loan'),
('loan_error_reason_required', 'ar', 'يرجى تقديم سبب للقرض'),

-- Success/Error Messages
('loan_submit_success', 'en', 'Loan application submitted successfully'),
('loan_submit_success', 'ar', 'تم تقديم طلب القرض بنجاح'),
('loan_submit_error', 'en', 'Failed to submit loan application'),
('loan_submit_error', 'ar', 'فشل في تقديم طلب القرض'),
('loan_eligibility_error', 'en', 'Unable to check loan eligibility'),
('loan_eligibility_error', 'ar', 'تعذر التحقق من أهلية القرض'),
('loan_not_eligible', 'en', 'You are not eligible for this loan type'),
('loan_not_eligible', 'ar', 'أنت غير مؤهل للحصول على هذا النوع من القرض'),

-- Special Messages
('loan_housing_allowance_deduction', 'en', 'Your housing allowance will be deducted'),
('loan_housing_allowance_deduction', 'ar', 'سيتم خصم بدل السكن الخاص بك'),
('loan_advance_full_deduction', 'en', 'Full amount will be deducted from next salary'),
('loan_advance_full_deduction', 'ar', 'سيتم خصم المبلغ الكامل من الراتب التالي'),

-- Placeholder Text
('loan_amount_placeholder', 'en', 'Enter amount in SAR'),
('loan_amount_placeholder', 'ar', 'أدخل المبلغ بالريال السعودي'),
('loan_reason_placeholder', 'en', 'Enter reason for loan request'),
('loan_reason_placeholder', 'ar', 'أدخل سبب طلب القرض'),

-- Additional Info Labels
('loan_sar_currency', 'en', 'SAR'),
('loan_sar_currency', 'ar', 'ريال'),
('loan_per_month', 'en', 'per month'),
('loan_per_month', 'ar', 'شهرياً'),

-- Loading/Processing Messages
('loan_loading', 'en', 'Loading...'),
('loan_loading', 'ar', 'جاري التحميل...'),
('loan_processing', 'en', 'Processing your request...'),
('loan_processing', 'ar', 'جاري معالجة طلبك...'),
('loan_checking_eligibility', 'en', 'Checking eligibility...'),
('loan_checking_eligibility', 'ar', 'جاري التحقق من الأهلية...'),

-- Backend Message Keys (for AJAX responses)
('loan_eos_eligible_message', 'en', 'You can apply for End of Service loan from SAR 1,000 to SAR 20,000'),
('loan_eos_eligible_message', 'ar', 'يمكنك التقديم على قرض نهاية الخدمة من 1,000 ريال إلى 20,000 ريال'),
('loan_housing_no_allowance', 'en', 'You do not have housing allowance. Housing loan is not available for you.'),
('loan_housing_no_allowance', 'ar', 'ليس لديك بدل سكن. قرض السكن غير متاح لك.'),
('loan_housing_exists', 'en', 'You have a housing loan from {date} that must be fully paid and 1 year must pass before applying again.'),
('loan_housing_exists', 'ar', 'لديك قرض سكن من تاريخ {date} يجب سداده بالكامل ومرور سنة واحدة قبل التقديم مرة أخرى.'),
('loan_housing_eligible_message', 'en', 'You can apply for Housing loan up to SAR {max} (6 months advance)'),
('loan_housing_eligible_message', 'ar', 'يمكنك التقديم على قرض السكن حتى {max} ريال (سلفة 6 أشهر)'),
('loan_advance_eligible_message', 'en', 'You can apply for Advance Salary up to SAR {max} (50% of monthly salary). Full amount will be deducted in next payroll.'),
('loan_advance_eligible_message', 'ar', 'يمكنك التقديم على سلفة راتب حتى {max} ريال (50% من الراتب الشهري). سيتم خصم المبلغ الكامل في كشف الرواتب التالي.'),
('loan_invalid_type', 'en', 'Invalid loan type.'),
('loan_invalid_type', 'ar', 'نوع القرض غير صالح.');
