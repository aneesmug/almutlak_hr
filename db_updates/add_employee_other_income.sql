-- Feature: "Other Income" (scheduled) section on the Additional Information tab of view_employee.php
-- Lets HR schedule a recurring extra-income line (e.g. a 3-month Bonus) for an employee across a
-- specific month range. During payroll generation (includes/api/process_payroll.php ->
-- addOrUpdateScheduledOtherIncome), any schedule covering the month being generated automatically
-- gets inserted into `payroll_benefits` as an "Other Income" benefit. Once the schedule's end_month
-- has been processed, the schedule is automatically flipped to status = 0 (inactive).
-- Safe to run on both local and live - CREATE TABLE IF NOT EXISTS / conditional ALTER.

CREATE TABLE IF NOT EXISTS `employee_other_income` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `emp_id` VARCHAR(255) NOT NULL,
  `title` VARCHAR(100) NOT NULL,
  `amount` DECIMAL(10,2) NOT NULL,
  `start_month` VARCHAR(7) NOT NULL COMMENT 'YYYY-MM, inclusive',
  `end_month` VARCHAR(7) NOT NULL COMMENT 'YYYY-MM, inclusive',
  `status` TINYINT(1) NOT NULL DEFAULT 1 COMMENT '1 = active/scheduled, 0 = inactive (manually stopped or period elapsed)',
  `created_by` VARCHAR(255) DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `emp_id` (`emp_id`),
  KEY `status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Links an auto-generated payroll_benefits row back to the schedule that created it, so
-- regeneration/cleanup can tell auto rows apart from manually-added "Other Income" entries.
SET @col_exists = (
  SELECT COUNT(*) FROM `information_schema`.`COLUMNS`
  WHERE `TABLE_SCHEMA` = DATABASE() AND `TABLE_NAME` = 'payroll_benefits' AND `COLUMN_NAME` = 'source_other_income_id'
);
SET @sql = IF(@col_exists = 0,
  'ALTER TABLE `payroll_benefits` ADD COLUMN `source_other_income_id` INT(11) DEFAULT NULL AFTER `type_id`, ADD KEY `source_other_income_id` (`source_other_income_id`)',
  'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Per-employee master switch (Edit Employee page) for whether the Other Income section shows
-- at all on view_employee.php for that employee - independent of the viewer's own
-- 'view_employee_other_income' special access. Defaults to enabled (1) so existing behavior
-- doesn't change for anyone until an admin explicitly turns it off for a given employee.
SET @col_exists = (
  SELECT COUNT(*) FROM `information_schema`.`COLUMNS`
  WHERE `TABLE_SCHEMA` = DATABASE() AND `TABLE_NAME` = 'employees' AND `COLUMN_NAME` = 'other_income_enabled'
);
SET @sql = IF(@col_exists = 0,
  'ALTER TABLE `employees` ADD COLUMN `other_income_enabled` TINYINT(1) NOT NULL DEFAULT 1 AFTER `allow_vacation_salary_below_min_days`',
  'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

INSERT INTO `translations` (`lang_key`, `lang_code`, `translation`) VALUES
('other_income', 'en', 'Other Income'),
('other_income', 'ar', 'دخل إضافي'),

('scheduled_months', 'en', 'scheduled months'),
('scheduled_months', 'ar', 'أشهر مجدولة'),

('add_other_income', 'en', 'Add Other Income'),
('add_other_income', 'ar', 'إضافة دخل إضافي'),

('other_income_title', 'en', 'Title'),
('other_income_title', 'ar', 'العنوان'),

('other_income_title_placeholder', 'en', 'e.g. Bonus'),
('other_income_title_placeholder', 'ar', 'مثال: مكافأة'),

('start_month', 'en', 'Start Month'),
('start_month', 'ar', 'شهر البداية'),

('end_month', 'en', 'End Month'),
('end_month', 'ar', 'شهر النهاية'),

('no_other_income_scheduled', 'en', 'No scheduled other income records.'),
('no_other_income_scheduled', 'ar', 'لا توجد سجلات دخل إضافي مجدولة.'),

('show_inactive', 'en', 'Show Inactive'),
('show_inactive', 'ar', 'إظهار غير النشط'),

('other_income_enabled', 'en', 'Show Other Income Block'),
('other_income_enabled', 'ar', 'إظهار قسم الدخل الإضافي'),

('other_income_enabled_hint', 'en', 'When Yes, the Other Income section is available on this employee''s profile. When No, it is hidden for everyone regardless of Special Access.'),
('other_income_enabled_hint', 'ar', 'عند التفعيل، يظهر قسم الدخل الإضافي في ملف هذا الموظف. عند الإيقاف، يتم إخفاؤه عن الجميع بغض النظر عن الصلاحيات الخاصة.'),

('deactivate', 'en', 'Deactivate'),
('deactivate', 'ar', 'إيقاف'),

('confirm_deactivate_other_income', 'en', 'Stop this scheduled income? Months not yet paid will no longer receive it.'),
('confirm_deactivate_other_income', 'ar', 'هل تريد إيقاف هذا الدخل المجدول؟ لن يتم إضافته للأشهر القادمة غير المدفوعة.'),

('amount', 'en', 'Amount'),
('amount', 'ar', 'المبلغ')

ON DUPLICATE KEY UPDATE `translation` = VALUES(`translation`);
