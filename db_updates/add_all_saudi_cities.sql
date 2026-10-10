-- Complete list of Saudi Arabia cities / governorates for `saudi_cities`.
-- Existing ids are kept (business trips + employees reference them by id);
-- only missing cities are inserted. Safe to run more than once.
-- Backup taken before first run: saudi_cities_backup_20261008

SET NAMES utf8mb4;

-- Fix wrong data in existing rows
UPDATE saudi_cities SET name_ar = 'الأحساء' WHERE id = 5 AND name_en = 'Al-Ahsa';
UPDATE saudi_cities SET name_ar = 'ينبع' WHERE id = 19 AND name_en = 'Yanbu';
UPDATE saudi_cities SET name_en = 'Jazan' WHERE id = 22 AND name_en = 'Jiza';

DROP TEMPORARY TABLE IF EXISTS tmp_saudi_cities;
CREATE TEMPORARY TABLE tmp_saudi_cities (
  name_en VARCHAR(255) NOT NULL,
  name_ar VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL
) DEFAULT CHARSET = utf8mb4;

INSERT INTO tmp_saudi_cities (name_en, name_ar) VALUES
-- Riyadh Region
('Riyadh', 'الرياض'), ('Al-Kharj', 'الخرج'), ('Diriyah', 'الدرعية'), ('Al Majmaah', 'المجمعة'),
('Az Zulfi', 'الزلفي'), ('Shaqra', 'شقراء'), ('Ad Dawadimi', 'الدوادمي'), ('Afif', 'عفيف'),
('Al Quwayiyah', 'القويعية'), ('Wadi Ad-Dawasir', 'وادي الدواسر'), ('As Sulayyil', 'السليل'),
('Al Aflaj', 'الأفلاج'), ('Hotat Bani Tamim', 'حوطة بني تميم'), ('Al Hariq', 'الحريق'),
('Al Ghat', 'الغاط'), ('Rumah', 'رماح'), ('Thadiq', 'ثادق'), ('Huraymila', 'حريملاء'),
('Al Muzahimiyah', 'المزاحمية'), ('Dhurma', 'ضرما'), ('Marat', 'مرات'), ('Ad Dilam', 'الدلم'),
('Al Uyaynah', 'العيينة'), ('Al Hawtah', 'الحوطة'),
-- Makkah Region
('Makkah', 'مكة المكرمة'), ('Jeddah', 'جدة'), ('Taif', 'الطائف'), ('Rabigh', 'رابغ'),
('Al Qunfudhah', 'القنفذة'), ('Al Lith', 'الليث'), ('Al Jumum', 'الجموم'), ('Khulais', 'خليص'),
('Al Kamil', 'الكامل'), ('Turabah', 'تربة'), ('Ranyah', 'رنية'), ('Al Khurmah', 'الخرمة'),
('Al Muwayh', 'المويه'), ('Adham', 'أضم'), ('Al Ardiyat', 'العرضيات'), ('Maysan', 'ميسان'),
('Bahrah', 'بحرة'), ('Thuwal', 'ثول'), ('King Abdullah Economic City', 'مدينة الملك عبدالله الاقتصادية'),
-- Madinah Region
('Medina', 'المدينة المنورة'), ('Yanbu', 'ينبع'), ('Al Ula', 'العلا'), ('Badr', 'بدر'),
('Khaybar', 'خيبر'), ('Al Mahd', 'المهد'), ('Al Hanakiyah', 'الحناكية'), ('Wadi Al Fara', 'وادي الفرع'),
('Al Ais', 'العيص'),
-- Qassim Region
('Buraidah', 'بريدة'), ('Unaizah', 'عنيزة'), ('Ar Rass', 'الرس'), ('Al Bukayriyah', 'البكيرية'),
('Al Badai', 'البدائع'), ('Al Mithnab', 'المذنب'), ('Riyadh Al Khabra', 'رياض الخبراء'),
('Uyun Al Jiwa', 'عيون الجواء'), ('Al Asyah', 'الأسياح'), ('An Nabhaniyah', 'النبهانية'),
('Ash Shimasiyah', 'الشماسية'), ('Uqlat As Suqur', 'عقلة الصقور'), ('Dariyah', 'ضرية'),
-- Eastern Province
('Dammam', 'الدمام'), ('Khobar', 'الخبر'), ('Dhahran', 'الظهران'), ('Al-Ahsa', 'الأحساء'),
('Hofuf', 'الهفوف'), ('Al Mubarraz', 'المبرز'), ('Jubail', 'الجبيل'), ('Qatif', 'القطيف'),
('Hafar Al-Batin', 'حفر الباطن'), ('Khafji', 'الخفجي'), ('Ras Tanura', 'رأس تنورة'),
('Abqaiq', 'بقيق'), ('An Nuayriyah', 'النعيرية'), ('Qaryat Al Ulya', 'قرية العليا'),
('Saihat', 'سيهات'), ('Safwa', 'صفوى'), ('Tarout', 'تاروت'), ('Anak', 'عنك'),
('Al Uqair', 'العقير'), ('Salwa', 'سلوى'), ('Al Qaisumah', 'القيصومة'),
-- Asir Region
('Abha', 'أبها'), ('Khamis Mushait', 'خميس مشيط'), ('Bisha', 'بيشة'), ('An Namas', 'النماص'),
('Muhayil Asir', 'محايل عسير'), ('Sarat Abidah', 'سراة عبيدة'), ('Tathlith', 'تثليث'),
('Rijal Almaa', 'رجال ألمع'), ('Ahad Rafidah', 'أحد رفيدة'), ('Dhahran Al Janub', 'ظهران الجنوب'),
('Balqarn', 'بلقرن'), ('Al Majardah', 'المجاردة'), ('Tanomah', 'تنومة'), ('Bariq', 'بارق'),
('Al Harajah', 'الحرجة'), ('Tabalah', 'تبالة'),
-- Tabuk Region
('Tabuk', 'تبوك'), ('Al Wajh', 'الوجه'), ('Duba', 'ضباء'), ('Tayma', 'تيماء'), ('Umluj', 'أملج'),
('Haql', 'حقل'), ('Al Bad', 'البدع'), ('NEOM', 'نيوم'),
-- Hail Region
('Hail', 'حائل'), ('Baqaa', 'بقعاء'), ('Al Ghazalah', 'الغزالة'), ('Ash Shinan', 'الشنان'),
('Al Hait', 'الحائط'), ('As Sulaimi', 'السليمي'), ('Mawqaq', 'موقق'), ('Samira', 'سميراء'),
-- Northern Borders Region
('Arar', 'عرعر'), ('Rafha', 'رفحاء'), ('Turaif', 'طريف'), ('Al Uwayqilah', 'العويقيلة'),
-- Jazan Region
('Jazan', 'جازان'), ('Sabya', 'صبيا'), ('Abu Arish', 'أبو عريش'), ('Samtah', 'صامطة'),
('Ad Darb', 'الدرب'), ('Baish', 'بيش'), ('Ahad Al Masarihah', 'أحد المسارحة'), ('Farasan', 'فرسان'),
('Al Aridhah', 'العارضة'), ('Ad Dayer', 'الداير'), ('Al Harth', 'الحرث'), ('Damad', 'ضمد'),
('At Tuwal', 'الطوال'), ('Al Idabi', 'العيدابي'), ('Fifa', 'فيفاء'), ('Ar Rayth', 'الريث'),
('Harub', 'هروب'),
-- Najran Region
('Najran', 'نجران'), ('Sharurah', 'شرورة'), ('Hubuna', 'حبونا'), ('Badr Al Janub', 'بدر الجنوب'),
('Yadamah', 'يدمة'), ('Thar', 'ثار'), ('Khubash', 'خباش'),
-- Al Bahah Region
('Al Bahah', 'الباحة'), ('Baljurashi', 'بلجرشي'), ('Al Mandaq', 'المندق'), ('Al Mikhwah', 'المخواة'),
('Al Aqiq', 'العقيق'), ('Qilwah', 'قلوة'), ('Al Qura', 'القرى'),
-- Al Jouf Region
('Sakaka', 'سكاكا'), ('Dumat Al Jandal', 'دومة الجندل'), ('Al Qurayyat', 'القريات'), ('Tabarjal', 'طبرجل');

-- Insert only cities that are not already in the table (matched by English name)
INSERT INTO saudi_cities (name_en, name_ar)
SELECT t.name_en, t.name_ar
FROM tmp_saudi_cities t
WHERE NOT EXISTS (
    SELECT 1 FROM saudi_cities c WHERE CONVERT(LOWER(c.name_en) USING utf8mb4) COLLATE utf8mb4_general_ci = LOWER(t.name_en) COLLATE utf8mb4_general_ci
);

DROP TEMPORARY TABLE IF EXISTS tmp_saudi_cities;
