-- Feature: "Save as Draft" in the Add New Employee modal (assets/js/newEmployeeModal.js).
-- Employees whose information isn't complete yet are kept here (shared by all HR users) until
-- registered; registering a draft deletes its row (includes/ajaxFile/ajaxEmployeeCreateModal.php).
-- Each draft reserves its emp_id (UNIQUE) so new employees get the following number.
-- The endpoint also creates this table on first use; running this file is optional.
-- Safe to run on both local and live - CREATE TABLE IF NOT EXISTS + ON DUPLICATE KEY UPDATE.
--
-- IMPORTANT when importing: use a UTF-8/utf8mb4 connection, e.g.
--   mysql --default-character-set=utf8mb4 -u USER -p DBNAME < add_employee_drafts_table.sql

CREATE TABLE IF NOT EXISTS `employee_drafts` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `emp_id` VARCHAR(50) NOT NULL,
  `emp_type` ENUM('company','man_power') NOT NULL DEFAULT 'company',
  `name` VARCHAR(255) DEFAULT NULL,
  `form_data` LONGTEXT NOT NULL,
  `is_saved` TINYINT(1) NOT NULL DEFAULT 0,
  `created_by` VARCHAR(255) DEFAULT NULL,
  `created_by_name` VARCHAR(255) DEFAULT NULL,
  `updated_by` VARCHAR(255) DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_emp_id` (`emp_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO `translations` (`lang_key`, `lang_code`, `translation`) VALUES
('created_by', 'en', 'Created By'),
('created_by', 'ar', 'أنشئ بواسطة'),
('draft_not_found', 'en', 'This draft no longer exists.'),
('draft_not_found', 'ar', 'هذه المسودة لم تعد موجودة.')
ON DUPLICATE KEY UPDATE `translation` = VALUES(`translation`);

INSERT INTO `translations` (`lang_key`, `lang_code`, `translation`) VALUES
('draft_deleted', 'en', 'Draft deleted'),
('draft_deleted', 'ar', 'تم حذف المسودة')
ON DUPLICATE KEY UPDATE `translation` = VALUES(`translation`);
