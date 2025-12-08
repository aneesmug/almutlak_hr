-- Add emp_loan_monthly_status table for tracking monthly skip/active status
-- This allows skipping specific months for loan deductions and carrying forward amounts

CREATE TABLE IF NOT EXISTS `emp_loan_monthly_status` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `loan_id` int(11) NOT NULL,
  `month_year` varchar(7) NOT NULL COMMENT 'Format: YYYY-MM',
  `status` tinyint(1) NOT NULL DEFAULT 1 COMMENT '1 = Active (deduct), 0 = Skip (don''t deduct)',
  `skip_reason` varchar(255) DEFAULT NULL COMMENT 'Reason for skipping this month',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_loan_month` (`loan_id`, `month_year`),
  KEY `idx_loan_id` (`loan_id`),
  KEY `idx_month_year` (`month_year`),
  KEY `idx_status` (`status`),
  CONSTRAINT `fk_loan_monthly_status` FOREIGN KEY (`loan_id`) REFERENCES `emp_loan` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci COMMENT='Tracks monthly active/skip status for automatic loan deductions';

-- Add index for efficient queries
CREATE INDEX `idx_loan_month_status` ON `emp_loan_monthly_status`(`loan_id`, `month_year`, `status`);

-- Add notes column to emp_loan_payments for carry-forward tracking
ALTER TABLE `emp_loan_payments` 
ADD COLUMN `notes` TEXT NULL COMMENT 'Payment notes including carry-forward info' AFTER `attachment`;

