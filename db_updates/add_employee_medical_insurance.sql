-- Feature: "Medical Insurance" section on the Additional Information tab (view_employee.php).
-- Medical Insurance amount, Insurance No, and Medical Expiry are yearly-renewed, so they get
-- their own history table instead of living in employee_additional_info: every renewal adds
-- a new row, and the previous row automatically flips from status='active' to 'expired'
-- (see employeeMedicalInsuranceHandler.php). The row with status='active' is the current one.
-- Safe to run on both local and live - CREATE TABLE IF NOT EXISTS + ON DUPLICATE KEY UPDATE.

CREATE TABLE IF NOT EXISTS `employee_medical_insurance` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `emp_id` VARCHAR(255) NOT NULL,
  `insurance_no` VARCHAR(100) DEFAULT NULL,
  `med_insurance` DECIMAL(10,2) DEFAULT NULL,
  `medical_expiry` DATE DEFAULT NULL,
  `status` ENUM('active','expired') NOT NULL DEFAULT 'active',
  `created_by` VARCHAR(255) DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `emp_id` (`emp_id`),
  KEY `emp_status` (`emp_id`, `status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `translations` (`lang_key`, `lang_code`, `translation`) VALUES
('medical_insurance', 'en', 'Medical Insurance'),
('medical_insurance', 'ar', 'التأمين الطبي'),

('renews_yearly', 'en', 'renews yearly'),
('renews_yearly', 'ar', 'يتجدد سنويا'),

('add_new_year', 'en', 'Add New Year'),
('add_new_year', 'ar', 'إضافة سنة جديدة'),

('medical_expiry', 'en', 'Medical Expiry'),
('medical_expiry', 'ar', 'تاريخ انتهاء التأمين الطبي'),

('insurance_history', 'en', 'Insurance History'),
('insurance_history', 'ar', 'سجل التأمين'),

('added_on', 'en', 'Added On'),
('added_on', 'ar', 'تاريخ الإضافة'),

('enter_at_least_one_field', 'en', 'Enter at least one field'),
('enter_at_least_one_field', 'ar', 'أدخل حقلا واحدا على الأقل')

ON DUPLICATE KEY UPDATE `translation` = VALUES(`translation`);
