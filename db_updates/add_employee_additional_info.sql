-- Feature: "Additional Information" tab on view_employee.php
-- Adds a new employee_additional_info table (Salary Grade, No. of Dependants, Ticket,
-- GOSI %, Medical Insurance, Labour Office Expenses, Iqama Renewal Fee, Monthly Leave
-- Accrual, Medical Class, Citizen (Local) relation, Saudi Engineering Council fee/expiry)
-- plus the EN/AR translation strings the new tab and its SweetAlert2 edit modal use.
-- Safe to run on both local and live - CREATE TABLE IF NOT EXISTS + ON DUPLICATE KEY UPDATE.

CREATE TABLE IF NOT EXISTS `employee_additional_info` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `emp_id` VARCHAR(255) NOT NULL,
  `salary_grade` VARCHAR(10) DEFAULT NULL,
  `dependants_count` INT(11) DEFAULT NULL,
  `ticket_fare` DECIMAL(10,2) DEFAULT NULL,
  `gosi_percentage` DECIMAL(5,2) DEFAULT NULL,
  `med_insurance` DECIMAL(10,2) DEFAULT NULL,
  `labour_office_expense` DECIMAL(10,2) DEFAULT NULL,
  `iqama_renewal_fee` DECIMAL(10,2) DEFAULT NULL,
  `monthly_leave_accrual` DECIMAL(5,2) DEFAULT NULL,
  `medical_class` ENUM('CLT','C','B','A','A+') DEFAULT NULL,
  `citizen_local_relation` ENUM('Son','Daughter','Wife','Husband') DEFAULT NULL,
  `eng_council_fee` DECIMAL(10,2) DEFAULT NULL,
  `eng_council_expiry` DATE DEFAULT NULL,
  `updated_by` VARCHAR(255) DEFAULT NULL,
  `updated_at` TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `emp_id` (`emp_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `translations` (`lang_key`, `lang_code`, `translation`) VALUES
('additional_information', 'en', 'Additional Information'),
('additional_information', 'ar', 'معلومات إضافية'),

('edit_additional_information', 'en', 'Edit Additional Information'),
('edit_additional_information', 'ar', 'تعديل المعلومات الإضافية'),

('salary_grade', 'en', 'Salary Grade'),
('salary_grade', 'ar', 'درجة الراتب'),

('grade', 'en', 'Grade'),
('grade', 'ar', 'درجة'),

('dependants_count', 'en', 'No. of Dependants'),
('dependants_count', 'ar', 'عدد المعالين'),

('ticket_fare', 'en', 'Ticket'),
('ticket_fare', 'ar', 'تذكرة السفر'),

('gosi_percentage', 'en', 'GOSI %'),
('gosi_percentage', 'ar', 'نسبة التأمينات الاجتماعية'),

('med_insurance', 'en', 'Medical Insurance'),
('med_insurance', 'ar', 'التأمين الطبي'),

('labour_office_expense', 'en', 'Labour Office Expenses'),
('labour_office_expense', 'ar', 'مصاريف مكتب العمل'),

('iqama_renewal_fee', 'en', 'Iqama Renewal Fee'),
('iqama_renewal_fee', 'ar', 'رسوم تجديد الإقامة'),

('monthly_leave_accrual', 'en', 'Monthly Leave Accrual'),
('monthly_leave_accrual', 'ar', 'استحقاق الإجازة الشهري'),

('medical_class', 'en', 'Medical Class'),
('medical_class', 'ar', 'الفئة الطبية'),

('citizen_local_relation', 'en', 'Citizen (Local)'),
('citizen_local_relation', 'ar', 'مواطن (محلي)'),

('eng_council_fee', 'en', 'Saudi Engineering Council Fee'),
('eng_council_fee', 'ar', 'رسوم الهيئة السعودية للمهندسين'),

('eng_council_expiry', 'en', 'Saudi Engineering Council Expiry'),
('eng_council_expiry', 'ar', 'تاريخ انتهاء الهيئة السعودية للمهندسين'),

('relation_son', 'en', 'Son'),
('relation_son', 'ar', 'ابن'),

('relation_daughter', 'en', 'Daughter'),
('relation_daughter', 'ar', 'ابنة'),

('relation_wife', 'en', 'Wife'),
('relation_wife', 'ar', 'زوجة'),

('relation_husband', 'en', 'Husband'),
('relation_husband', 'ar', 'زوج'),

('update_successful', 'en', 'Updated successfully'),
('update_successful', 'ar', 'تم التحديث بنجاح')

ON DUPLICATE KEY UPDATE `translation` = VALUES(`translation`);
