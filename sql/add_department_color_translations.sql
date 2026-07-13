-- Add translations for department color management labels

INSERT INTO translations (lang_key, lang_code, translation)
SELECT 'department_color', 'en', 'Department Color'
WHERE NOT EXISTS (
    SELECT 1 FROM translations WHERE lang_key = 'department_color' AND lang_code = 'en'
);

INSERT INTO translations (lang_key, lang_code, translation)
SELECT 'department_color', 'ar', 'لون القسم'
WHERE NOT EXISTS (
    SELECT 1 FROM translations WHERE lang_key = 'department_color' AND lang_code = 'ar'
);

INSERT INTO translations (lang_key, lang_code, translation)
SELECT 'invalid_department_color', 'en', 'Please select a valid department color'
WHERE NOT EXISTS (
    SELECT 1 FROM translations WHERE lang_key = 'invalid_department_color' AND lang_code = 'en'
);

INSERT INTO translations (lang_key, lang_code, translation)
SELECT 'invalid_department_color', 'ar', 'يرجى اختيار لون قسم صالح'
WHERE NOT EXISTS (
    SELECT 1 FROM translations WHERE lang_key = 'invalid_department_color' AND lang_code = 'ar'
);
