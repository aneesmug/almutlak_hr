/**
 * HOLIDAYS SYSTEM - DATABASE MIGRATION
 * 
 * This migration creates the emp_holidays table to manage company holidays
 * Holidays are used to exclude days from vacation deductions
 */

-- Create the emp_holidays table
CREATE TABLE IF NOT EXISTS `emp_holidays` (
  `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `holiday_name` varchar(255) NOT NULL COMMENT 'Name of the holiday (e.g., Eid al-Fitr, National Day)',
  `start_date` date NOT NULL COMMENT 'Start date of the holiday period',
  `end_date` date NOT NULL COMMENT 'End date of the holiday period',
  `total_days` int(11) NOT NULL COMMENT 'Total number of days in this holiday period',
  `holiday_type` enum('religious','national','other') DEFAULT 'other' COMMENT 'Type of holiday: religious, national, or other',
  `is_active` tinyint(1) NOT NULL DEFAULT 1 COMMENT '1 = Active (counts in deductions), 0 = Inactive (archived)',
  `remarks` text DEFAULT NULL COMMENT 'Additional remarks about the holiday',
  `created_by` varchar(255) DEFAULT NULL COMMENT 'User who created this holiday record',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Timestamp when record was created',
  `updated_by` varchar(255) DEFAULT NULL COMMENT 'User who last updated this record',
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Timestamp of last update'
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci COMMENT='Company holidays used to exclude days from vacation deductions';

-- Create index for efficient holiday lookups
CREATE INDEX IF NOT EXISTS `idx_holiday_dates` ON `emp_holidays` (`start_date`, `end_date`, `is_active`);
CREATE INDEX IF NOT EXISTS `idx_holiday_active` ON `emp_holidays` (`is_active`, `start_date`);

-- Sample holidays for testing (optional - comment out if not needed)
-- INSERT INTO `emp_holidays` (`holiday_name`, `start_date`, `end_date`, `total_days`, `holiday_type`, `is_active`, `remarks`, `created_by`)
-- VALUES 
-- ('Eid al-Fitr 2026', '2026-04-09', '2026-04-13', 5, 'religious', 1, 'Islamic holiday', 'System'),
-- ('Eid al-Adha 2026', '2026-06-15', '2026-06-19', 5, 'religious', 1, 'Islamic holiday', 'System'),
-- ('Saudi National Day', '2026-09-23', '2026-09-24', 2, 'national', 1, 'National holiday', 'System');
