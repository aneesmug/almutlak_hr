-- =====================================================
-- Employee Resignation Tables Schema
-- Created: 2025-11-25
-- =====================================================

-- Main resignation records table
CREATE TABLE IF NOT EXISTS `emp_resignations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `emp_id` int(11) NOT NULL,
  `last_working_day` date NOT NULL,
  `submission_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `status` enum('pending','approved','rejected','cancelled') NOT NULL DEFAULT 'pending',
  `approved_by` int(11) DEFAULT NULL,
  `approval_date` datetime DEFAULT NULL,
  `rejection_reason` text DEFAULT NULL,
  `hr_notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_emp_id` (`emp_id`),
  KEY `idx_status` (`status`),
  KEY `idx_last_working_day` (`last_working_day`),
  KEY `idx_approved_by` (`approved_by`),
  CONSTRAINT `fk_resignation_emp` FOREIGN KEY (`emp_id`) REFERENCES `employee_basic_info` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_resignation_approver` FOREIGN KEY (`approved_by`) REFERENCES `employee_basic_info` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Exit interview responses table
CREATE TABLE IF NOT EXISTS `emp_exit_interviews` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `resignation_id` int(11) NOT NULL,
  `emp_id` int(11) NOT NULL,
  `q1_reasons` text NOT NULL COMMENT 'Primary reasons for leaving',
  `q2_support` text NOT NULL COMMENT 'Team and management support',
  `q3_resources` text NOT NULL COMMENT 'Resources and tools availability',
  `q4_manager` text NOT NULL COMMENT 'Relationship with direct manager',
  `q5_growth` text NOT NULL COMMENT 'Professional growth opportunities',
  `q6_compensation` text NOT NULL COMMENT 'Satisfaction with compensation',
  `q7_different` text NOT NULL COMMENT 'What could be done differently',
  `q8_recommend` text NOT NULL COMMENT 'Would recommend company',
  `q9_additional` text DEFAULT NULL COMMENT 'Additional comments',
  `submitted_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_resignation_id` (`resignation_id`),
  KEY `idx_emp_id` (`emp_id`),
  CONSTRAINT `fk_exit_interview_resignation` FOREIGN KEY (`resignation_id`) REFERENCES `emp_resignations` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_exit_interview_emp` FOREIGN KEY (`emp_id`) REFERENCES `employee_basic_info` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Resignation attachments table (for any supporting documents)
CREATE TABLE IF NOT EXISTS `emp_resignation_attachments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `resignation_id` int(11) NOT NULL,
  `file_name` varchar(255) NOT NULL,
  `file_path` varchar(500) NOT NULL,
  `file_type` varchar(100) DEFAULT NULL,
  `file_size` int(11) DEFAULT NULL,
  `uploaded_by` int(11) NOT NULL,
  `uploaded_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_resignation_id` (`resignation_id`),
  CONSTRAINT `fk_attachment_resignation` FOREIGN KEY (`resignation_id`) REFERENCES `emp_resignations` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Resignation workflow history (track status changes)
CREATE TABLE IF NOT EXISTS `emp_resignation_history` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `resignation_id` int(11) NOT NULL,
  `action` enum('submitted','approved','rejected','cancelled','modified') NOT NULL,
  `previous_status` enum('pending','approved','rejected','cancelled') DEFAULT NULL,
  `new_status` enum('pending','approved','rejected','cancelled') NOT NULL,
  `action_by` int(11) NOT NULL,
  `action_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `notes` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_resignation_id` (`resignation_id`),
  KEY `idx_action_by` (`action_by`),
  CONSTRAINT `fk_history_resignation` FOREIGN KEY (`resignation_id`) REFERENCES `emp_resignations` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add indexes for better query performance
CREATE INDEX idx_submission_date ON emp_resignations(submission_date);
CREATE INDEX idx_status_emp ON emp_resignations(status, emp_id);
CREATE INDEX idx_submitted_at ON emp_exit_interviews(submitted_at);
