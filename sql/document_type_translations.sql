-- Translations for the Employee Documents "Type of Document" dropdown
-- (docu_type table values, rendered untranslated because that table has no
-- Arabic column - see docTypeToTranslationKey() in assets/js/jquery.app.js).
-- Safe to run multiple times.

INSERT IGNORE INTO translations (lang_key, lang_code, translation) VALUES
('doc_type_iqama', 'en', 'Iqama'),
('doc_type_iqama', 'ar', 'الإقامة'),
('doc_type_passport', 'en', 'Passport'),
('doc_type_passport', 'ar', 'جواز السفر'),
('doc_type_passport_front', 'en', 'Passport Front'),
('doc_type_passport_front', 'ar', 'جواز السفر (الوجه الأمامي)'),
('doc_type_passport_back', 'en', 'Passport Back'),
('doc_type_passport_back', 'ar', 'جواز السفر (الوجه الخلفي)'),
('doc_type_company_contract', 'en', 'Company Contract'),
('doc_type_company_contract', 'ar', 'عقد الشركة'),
('doc_type_baldia_card', 'en', 'Baldia Card'),
('doc_type_baldia_card', 'ar', 'بطاقة البلدية'),
('doc_type_baldia_certificate', 'en', 'Baldia Certificate'),
('doc_type_baldia_certificate', 'ar', 'شهادة البلدية'),
('doc_type_id_card', 'en', 'ID Card'),
('doc_type_id_card', 'ar', 'بطاقة الهوية'),
('doc_type_employee_file', 'en', 'Employee file'),
('doc_type_employee_file', 'ar', 'ملف الموظف');
