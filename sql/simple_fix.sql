-- Simple Fix for Error #1005
-- This is the most straightforward approach - just copy and paste into phpMyAdmin SQL editor

SET FOREIGN_KEY_CHECKS=0;

DROP TABLE IF EXISTS `approval_request_types`;

CREATE TABLE `approval_request_types` (
  `id` varchar(64) NOT NULL PRIMARY KEY,
  `type_name` varchar(255) NOT NULL,
  `description` longtext NULL,
  `is_default` tinyint(1) DEFAULT 0,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

SET FOREIGN_KEY_CHECKS=1;

-- Insert default request types
INSERT INTO `approval_request_types` 
(`id`, `type_name`, `description`, `is_default`, `is_active`, `created_at`, `updated_at`) 
VALUES
('vacation_request', 'Vacation Request', 'Employee vacation/leave request', 1, 1, NOW(), NULL),
('excuse_leave', 'Excuse Leave', 'Excuse leave request (sick leave, emergency, etc)', 1, 1, NOW(), NULL),
('loan_request', 'Loan Request', 'Employee loan request', 1, 1, NOW(), NULL),
('resignation_request', 'Resignation Request', 'Employee resignation request', 1, 1, NOW(), NULL),
('rejoin_request', 'Rejoin Request', 'Employee rejoin request after resignation', 1, 1, NOW(), NULL);

-- Verify the insert
SELECT COUNT(*) as 'Total Request Types Created' FROM `approval_request_types`;

-- Show all inserted types
SELECT `id`, `type_name`, `is_default` FROM `approval_request_types` ORDER BY `id`;
