-- SQL Migration: Create approval_request_types table
-- Handles foreign key constraint issues by disabling checks temporarily

-- Disable foreign key constraints to avoid conflicts
SET FOREIGN_KEY_CHECKS=0;

-- Drop table if exists
DROP TABLE IF EXISTS `approval_request_types`;

-- Wait a moment for the drop to complete
-- (Sometimes helps with lock issues)

-- Create table with simple structure
CREATE TABLE `approval_request_types` (
  `id` varchar(64) NOT NULL,
  `type_name` varchar(255) NOT NULL,
  `description` longtext,
  `is_default` tinyint(1) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

-- Re-enable foreign key constraints
SET FOREIGN_KEY_CHECKS=1;

-- Add indexes for better performance
ALTER TABLE `approval_request_types` 
ADD INDEX `idx_is_default` (`is_default`),
ADD INDEX `idx_is_active` (`is_active`),
ADD INDEX `idx_type_name` (`type_name`);

-- Insert default request types
INSERT IGNORE INTO `approval_request_types` (`id`, `type_name`, `description`, `is_default`, `is_active`) VALUES
('vacation_request', 'Vacation Request', 'Annual vacation and fly vacation approval chain', 1, 1),
('excuse_leave', 'Excuse Leave', 'Sick leave, exam leave, hajj, maternity, marriage, death, business trip', 1, 1),
('loan_request', 'Loan Request', 'Employee loan application approval chain (regular, emergency, end of service, housing, advance salary)', 1, 1),
('resignation_request', 'Resignation Request', 'Employee resignation approval chain with asset clearance', 1, 1),
('rejoin_request', 'Rejoin Request', 'Employee rejoin after resignation approval chain', 1, 1);

-- Verify inserts
SELECT COUNT(*) as total_types FROM `approval_request_types`;

-- Display inserted data
SELECT id, type_name, is_default, is_active FROM `approval_request_types` ORDER BY id;

COMMIT;
