# Translation Keys Required for Employee Profile Report

This document lists all translation keys (`__('key')`) used in the enhanced `employee_profile.php` file. These keys should be added to your language translation files.

## Translation Keys by Category

### Header & Status
```php
'personal_employment_details_header' => 'Personal & Employment Details',
'status_label' => 'Status',
'active_status' => 'Active',
'terminated_status' => 'Terminated',
'date_label' => 'Date',
```

### Personal Information
```php
'personal_information_header' => 'Personal Information',
'employee_id_label' => 'Employee ID',
'iqama_id_label' => 'IQAMA ID',
'iqama_exp_label' => 'IQAMA Expiry',
'expires_label' => 'Expires',
'passport_label' => 'Passport Number',
'passport_exp_label' => 'Passport Expiry',
'dob_label' => 'Date of Birth',
'nationality_label' => 'Nationality',
'age_label' => 'Age',
'years_text' => 'years',
```

### Employment Information
```php
'employment_information_header' => 'Employment Information',
'department_label' => 'Department',
'section_label' => 'Section',
'job_position_label' => 'Job Position',
'date_hired_label' => 'Date Hired',
'joining_date_label' => 'Joining Date',
'working_period' => 'Working Period',
'contract_period_label' => 'Contract Period',
'contact_label' => 'Contact',
'email_label' => 'Email',
```

### Financial Information
```php
'financial_details_header' => 'Financial Details',
'salary_breakdown_header' => 'Salary Breakdown',
'bank_insurance_header' => 'Bank & Insurance Information',
'basic_label' => 'Basic Salary',
'housing_label' => 'Housing',
'transport_label' => 'Transport',
'food_label' => 'Food',
'misc_label' => 'Miscellaneous',
'cashier_label' => 'Cashier',
'fuel_label' => 'Fuel',
'tel_label' => 'Telephone',
'other_label' => 'Other',
'guard_label' => 'Guard',
'total_salary_label' => 'Total Salary',
'sar_currency' => 'SAR',
'bank_name_label' => 'Bank Name',
'iban_label' => 'IBAN',
'gosi_no_label' => 'GOSI Number',
'gosi_payment_label' => 'GOSI Payment',
'insurance_no_label' => 'Insurance Number',
'insurance_class_label' => 'Insurance Class',
'insurance_exp_label' => 'Insurance Expiry',
```

### Assets
```php
'assigned_assets_header' => 'Assigned Assets',
'assigned_car_header' => 'Assigned Car',
'maker_model_label' => 'Maker/Model',
'plate_no_label' => 'Plate Number',
'receive_date_label' => 'Receive Date',
'other_assets_header' => 'Other Assets',
'serial_number' => 'Serial Number',
```

### Loans
```php
'loan_history_header' => 'Loan History',
'amount_header' => 'Amount',
'deduction_header' => 'Monthly Deduction',
'balance_header' => 'Balance',
'start_header' => 'Start Date',
'end_header' => 'End Date',
'type_header' => 'Type',
'status_header' => 'Status',
'emergency' => 'Emergency',
'approved' => 'Approved',
'paid' => 'Paid',
'rejected' => 'Rejected',
'pending' => 'Pending',
```

### Vacation
```php
'vacation_history_header' => 'Vacation History',
'vacation_balance_header' => 'Vacation Balance',
'start_date_header' => 'Start Date',
'return_date_header' => 'Return Date',
'days_header' => 'Days',
'permit_no_header' => 'Permit No',
'arrived_header' => 'Arrived',
'not_yet_text' => 'Not Yet',
'allocated_label' => 'Allocated',
'used_label' => 'Used',
'carried_over_label' => 'Carried Over',
'balance_label' => 'Balance',
'days_text' => 'Days',
```

### Professional Profiles
```php
'professional_profiles_header' => 'Professional Profiles',
'social_media_header' => 'Social Media',
'portfolio_header' => 'Portfolio',
'skills_label' => 'Skills',
'certifications_label' => 'Certifications',
'experience_label' => 'Experience',
'awards_label' => 'Awards',
```

### End of Service
```php
'end_of_service_header' => 'End of Service',
'resignation_date_label' => 'Resignation Date',
'last_working_day_label' => 'Last Working Day',
'reason_label' => 'Reason',
'eos_amount_label' => 'EOS Amount',
'final_settlement_label' => 'Final Settlement',
'settled' => 'Settled',
```

### Documents & Notes
```php
'notes_notices_header' => 'Notes & Notices',
'employee_documents_header' => 'Employee Documents',
'date_header' => 'Date',
'note_header' => 'Note',
```

## Sample Language File Entry (English)

```php
<?php

return [
    // Personal Information
    'personal_information_header' => 'Personal Information',
    'employee_id_label' => 'Employee ID',
    'iqama_id_label' => 'IQAMA ID',
    'iqama_exp_label' => 'IQAMA Expiry',
    'expires_label' => 'Expires',
    'passport_label' => 'Passport Number',
    'passport_exp_label' => 'Passport Expiry',
    'dob_label' => 'Date of Birth',
    'nationality_label' => 'Nationality',
    'age_label' => 'Age',
    'years_text' => 'years',
    
    // Employment Information
    'employment_information_header' => 'Employment Information',
    'department_label' => 'Department',
    'section_label' => 'Section',
    'job_position_label' => 'Job Position',
    'date_hired_label' => 'Date Hired',
    'joining_date_label' => 'Joining Date',
    'working_period' => 'Working Period',
    'contract_period_label' => 'Contract Period',
    'contact_label' => 'Contact',
    'email_label' => 'Email',
    
    // Financial Information
    'financial_details_header' => 'Financial Details',
    'salary_breakdown_header' => 'Salary Breakdown',
    'bank_insurance_header' => 'Bank & Insurance Information',
    'basic_label' => 'Basic Salary',
    'housing_label' => 'Housing',
    'transport_label' => 'Transport',
    'food_label' => 'Food',
    'misc_label' => 'Miscellaneous',
    'cashier_label' => 'Cashier',
    'fuel_label' => 'Fuel',
    'tel_label' => 'Telephone',
    'other_label' => 'Other',
    'guard_label' => 'Guard',
    'total_salary_label' => 'Total Salary',
    'sar_currency' => 'SAR',
    'bank_name_label' => 'Bank Name',
    'iban_label' => 'IBAN',
    'gosi_no_label' => 'GOSI Number',
    'gosi_payment_label' => 'GOSI Payment',
    'insurance_no_label' => 'Insurance Number',
    'insurance_class_label' => 'Insurance Class',
    'insurance_exp_label' => 'Insurance Expiry',
    
    // Assets
    'assigned_assets_header' => 'Assigned Assets',
    'assigned_car_header' => 'Assigned Car',
    'maker_model_label' => 'Maker/Model',
    'plate_no_label' => 'Plate Number',
    'receive_date_label' => 'Receive Date',
    'other_assets_header' => 'Other Assets',
    'serial_number' => 'Serial Number',
    
    // Loans
    'loan_history_header' => 'Loan History',
    'amount_header' => 'Amount',
    'deduction_header' => 'Monthly Deduction',
    'balance_header' => 'Balance',
    'start_header' => 'Start Date',
    'end_header' => 'End Date',
    'type_header' => 'Type',
    'status_header' => 'Status',
    'emergency' => 'Emergency',
    'approved' => 'Approved',
    'paid' => 'Paid',
    'rejected' => 'Rejected',
    'pending' => 'Pending',
    
    // Vacation
    'vacation_history_header' => 'Vacation History',
    'vacation_balance_header' => 'Vacation Balance',
    'start_date_header' => 'Start Date',
    'return_date_header' => 'Return Date',
    'days_header' => 'Days',
    'permit_no_header' => 'Permit No',
    'arrived_header' => 'Arrived',
    'not_yet_text' => 'Not Yet',
    'allocated_label' => 'Allocated',
    'used_label' => 'Used',
    'carried_over_label' => 'Carried Over',
    'balance_label' => 'Balance',
    'days_text' => 'Days',
    
    // Professional Profiles
    'professional_profiles_header' => 'Professional Profiles',
    'social_media_header' => 'Social Media',
    'portfolio_header' => 'Portfolio',
    'skills_label' => 'Skills',
    'certifications_label' => 'Certifications',
    'experience_label' => 'Experience',
    'awards_label' => 'Awards',
    
    // End of Service
    'end_of_service_header' => 'End of Service',
    'resignation_date_label' => 'Resignation Date',
    'last_working_day_label' => 'Last Working Day',
    'reason_label' => 'Reason',
    'eos_amount_label' => 'EOS Amount',
    'final_settlement_label' => 'Final Settlement',
    'settled' => 'Settled',
    
    // Documents & Notes
    'notes_notices_header' => 'Notes & Notices',
    'employee_documents_header' => 'Employee Documents',
    'date_header' => 'Date',
    'note_header' => 'Note',
    'personal_employment_details_header' => 'Personal & Employment Details',
    'status_label' => 'Status',
    'active_status' => 'Active',
    'terminated_status' => 'Terminated',
    'date_label' => 'Date',
];
```

## Sample Language File Entry (Arabic)

```php
<?php

return [
    // Personal Information
    'personal_information_header' => 'معلومات شخصية',
    'employee_id_label' => 'رقم الموظف',
    'iqama_id_label' => 'رقم الإقامة',
    'iqama_exp_label' => 'انتهاء الإقامة',
    'expires_label' => 'ينتهي',
    'passport_label' => 'رقم جواز السفر',
    'passport_exp_label' => 'انتهاء جواز السفر',
    'dob_label' => 'تاريخ الميلاد',
    'nationality_label' => 'الجنسية',
    'age_label' => 'العمر',
    'years_text' => 'سنوات',
    
    // Employment Information
    'employment_information_header' => 'معلومات التوظيف',
    'department_label' => 'القسم',
    'section_label' => 'الفرع',
    'job_position_label' => 'الوظيفة',
    'date_hired_label' => 'تاريخ التوظيف',
    'joining_date_label' => 'تاريخ الالتحاق',
    'working_period' => 'فترة العمل',
    'contract_period_label' => 'مدة العقد',
    'contact_label' => 'اتصل',
    'email_label' => 'البريد الإلكتروني',
    
    // Financial Information
    'financial_details_header' => 'التفاصيل المالية',
    'salary_breakdown_header' => 'توزيع الراتب',
    'bank_insurance_header' => 'البنك ومعلومات التأمين',
    'basic_label' => 'الراتب الأساسي',
    'housing_label' => 'السكن',
    'transport_label' => 'المواصلات',
    'food_label' => 'الطعام',
    'misc_label' => 'متفرقات',
    'cashier_label' => 'الصندوق',
    'fuel_label' => 'الوقود',
    'tel_label' => 'الهاتف',
    'other_label' => 'أخرى',
    'guard_label' => 'الحارس',
    'total_salary_label' => 'إجمالي الراتب',
    'sar_currency' => 'ريال سعودي',
    'bank_name_label' => 'اسم البنك',
    'iban_label' => 'رقم حساب IBAN',
    'gosi_no_label' => 'رقم التأمينات',
    'gosi_payment_label' => 'دفعة التأمينات',
    'insurance_no_label' => 'رقم التأمين',
    'insurance_class_label' => 'فئة التأمين',
    'insurance_exp_label' => 'انتهاء التأمين',
    
    // ... Continue for other sections
];
```

## Integration Steps

1. **Locate your language files** (typically in `includes/lang/` or similar)
2. **Add the translation keys** to your language files (English, Arabic, etc.)
3. **Test the employee profile report** in different languages
4. **Verify all labels** display correctly
5. **Test RTL support** for Arabic translations

## Missing Translation Behavior

If a translation key is not found:
- The system will display the key itself (e.g., `personal_information_header`)
- Check language file exists and is properly configured
- Verify `__()` function is defined correctly
- Check for typos in the key names

## Adding to Existing Language Files

If you have existing language files, merge these keys with your current translations:

```php
// In your existing language file (e.g., en.php or ar.php)
$translations = array_merge($translations, [
    'personal_employment_details_header' => 'Personal & Employment Details',
    'employee_id_label' => 'Employee ID',
    // ... add all keys from above
]);
```

## Language Key Naming Convention

Keys follow the pattern:
- `[section]_header` - Section titles (e.g., `personal_information_header`)
- `[field]_label` - Field labels (e.g., `employee_id_label`)
- `[status]_text` or `[status]` - Status messages (e.g., `active_status`)
- `[term]_currency` - Currency codes (e.g., `sar_currency`)

This convention makes it easy to:
- Identify the purpose of each key
- Find related keys quickly
- Add new translations systematically

---

**Document Version**: 1.0
**Status**: Ready for Implementation
