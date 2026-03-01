/**
 * HOLIDAYS SYSTEM - COMPANY ASSIGNMENT MIGRATION
 * 
 * This migration adds company-wise holiday assignments
 * Allows each holiday to be assigned to specific companies
 */

-- Create junction table to link holidays to companies
CREATE TABLE IF NOT EXISTS `holiday_companies` (
  `id` int(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `holiday_id` int(11) NOT NULL COMMENT 'Reference to emp_holidays',
  `company_id` int(11) NOT NULL COMMENT 'Reference to companies table (id field)',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'When this assignment was created',
  UNIQUE KEY `unique_holiday_company` (`holiday_id`, `company_id`),
  CONSTRAINT `fk_holiday_companies_holiday` FOREIGN KEY (`holiday_id`) REFERENCES `emp_holidays` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_holiday_companies_company` FOREIGN KEY (`company_id`) REFERENCES `companies` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci COMMENT='Junction table linking holidays to specific companies';

-- Create index for efficient lookups
CREATE INDEX IF NOT EXISTS `idx_holiday_company_lookup` ON `holiday_companies` (`holiday_id`, `company_id`);
CREATE INDEX IF NOT EXISTS `idx_company_holiday_lookup` ON `holiday_companies` (`company_id`, `holiday_id`);

-- Optional: If you want to backfill existing holidays to all companies (for backward compatibility)
-- Uncomment this section after verifying it won't cause issues
/*
INSERT INTO `holiday_companies` (`holiday_id`, `company_id`) 
SELECT h.`id`, c.`id` 
FROM `emp_holidays` h 
CROSS JOIN `companies` c 
WHERE NOT EXISTS (
  SELECT 1 FROM `holiday_companies` hc 
  WHERE hc.`holiday_id` = h.`id`
);
*/
