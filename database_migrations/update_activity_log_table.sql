-- Enhanced Activity Log Table Schema
-- This migration improves the activity_log table for comprehensive tracking

-- Drop existing table if you want to start fresh (CAUTION: This will delete existing data)
-- DROP TABLE IF EXISTS `activity_log`;

-- Create enhanced activity_log table
CREATE TABLE IF NOT EXISTS `activity_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` varchar(100) NOT NULL COMMENT 'Employee ID of user who performed action',
  `user_name` varchar(255) DEFAULT NULL COMMENT 'Name of user for quick reference',
  `action_type` enum('CREATE','UPDATE','DELETE','LOGIN','LOGOUT','VIEW','DOWNLOAD','UPLOAD','APPROVE','REJECT','SUBMIT','EXPORT','IMPORT','OTHER') NOT NULL DEFAULT 'OTHER' COMMENT 'Type of action performed',
  `module` varchar(100) NOT NULL COMMENT 'Module/Section (e.g., Employee, Vacation, Loan, Payroll)',
  `page` varchar(255) NOT NULL COMMENT 'Page/file where action occurred',
  `record_id` varchar(255) DEFAULT NULL COMMENT 'ID of the affected record',
  `table_name` varchar(100) DEFAULT NULL COMMENT 'Database table affected',
  `description` text DEFAULT NULL COMMENT 'Human-readable description of action',
  `old_values` text DEFAULT NULL COMMENT 'JSON of old values (for UPDATE/DELETE)',
  `new_values` text DEFAULT NULL COMMENT 'JSON of new values (for CREATE/UPDATE)',
  `ip_address` varchar(45) DEFAULT NULL COMMENT 'IP address of user',
  `user_agent` text DEFAULT NULL COMMENT 'Browser/device information',
  `severity` enum('INFO','WARNING','CRITICAL','ERROR') DEFAULT 'INFO' COMMENT 'Severity level of action',
  `status` enum('SUCCESS','FAILED','PENDING') DEFAULT 'SUCCESS' COMMENT 'Status of action',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'When action occurred',
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_action_type` (`action_type`),
  KEY `idx_module` (`module`),
  KEY `idx_created_at` (`created_at`),
  KEY `idx_record_id` (`record_id`),
  KEY `idx_table_name` (`table_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Comprehensive activity logging for all system actions';

-- If you want to migrate existing data (if you have old activity_log table):
/*
INSERT INTO activity_log (user_id, page, record_id, created_at, action_type)
SELECT 
    user_editor as user_id,
    page,
    pg_id as record_id,
    STR_TO_DATE(reg_date, '%Y-%m-%d %H:%i:%s') as created_at,
    'OTHER' as action_type
FROM old_activity_log_backup
WHERE reg_date IS NOT NULL AND reg_date != '';
*/
