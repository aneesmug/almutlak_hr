-- =====================================================
-- Employee Resignation System - Migration Script
-- Database: almutlak_hr_db
-- Created: 2025-11-25
-- Compatible with existing schema (MariaDB 10.11.15)
-- =====================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET time_zone = "+03:00";

-- =====================================================
-- TABLE 1: Main Resignation Records
-- =====================================================

CREATE TABLE IF NOT EXISTS `emp_resignations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `emp_id` varchar(255) NOT NULL COMMENT 'Employee ID from employees table',
  `last_working_day` date NOT NULL COMMENT 'Employee intended last working day',
  `submission_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'When resignation was submitted',
  `status` enum('pending','approved','rejected','cancelled','withdrawn') NOT NULL DEFAULT 'pending' COMMENT 'Current status of resignation',
  `approved_by` varchar(255) DEFAULT NULL COMMENT 'Employee ID who approved/rejected',
  `approval_date` datetime DEFAULT NULL COMMENT 'Date of approval/rejection',
  `rejection_reason` text DEFAULT NULL COMMENT 'Reason if rejected',
  `hr_notes` text DEFAULT NULL COMMENT 'Internal HR notes',
  `final_settlement_completed` tinyint(1) DEFAULT 0 COMMENT 'Final settlement status',
  `clearance_completed` tinyint(1) DEFAULT 0 COMMENT 'Exit clearance completed',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_emp_id` (`emp_id`),
  KEY `idx_status` (`status`),
  KEY `idx_last_working_day` (`last_working_day`),
  KEY `idx_submission_date` (`submission_date`),
  KEY `idx_approved_by` (`approved_by`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci 
COMMENT='Employee resignation records';

-- =====================================================
-- TABLE 2: Exit Interview Responses
-- =====================================================

CREATE TABLE IF NOT EXISTS `emp_exit_interviews` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `resignation_id` int(11) NOT NULL COMMENT 'Reference to emp_resignations.id',
  `emp_id` varchar(255) NOT NULL COMMENT 'Employee ID for quick reference',
  `q1_reasons` text NOT NULL COMMENT 'Q1: Primary reasons for leaving the company',
  `q2_support` text NOT NULL COMMENT 'Q2: Team and management support experience',
  `q3_resources` text NOT NULL COMMENT 'Q3: Resources and tools availability',
  `q4_manager` text NOT NULL COMMENT 'Q4: Relationship with direct manager',
  `q5_growth` text NOT NULL COMMENT 'Q5: Professional growth and development opportunities',
  `q6_compensation` text NOT NULL COMMENT 'Q6: Satisfaction with compensation and benefits',
  `q7_different` text NOT NULL COMMENT 'Q7: What could company have done differently',
  `q8_recommend` text NOT NULL COMMENT 'Q8: Would recommend company to others',
  `q9_additional` text DEFAULT NULL COMMENT 'Q9: Additional comments or feedback',
  `submitted_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Exit interview submission timestamp',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_resignation_id` (`resignation_id`),
  KEY `idx_emp_id` (`emp_id`),
  KEY `idx_submitted_at` (`submitted_at`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci 
COMMENT='Employee exit interview responses';

-- =====================================================
-- TABLE 3: Resignation Attachments (Optional Documents)
-- =====================================================

CREATE TABLE IF NOT EXISTS `emp_resignation_attachments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `resignation_id` int(11) NOT NULL COMMENT 'Reference to emp_resignations.id',
  `file_name` varchar(255) NOT NULL COMMENT 'Original filename',
  `file_path` varchar(500) NOT NULL COMMENT 'Server path to file',
  `file_type` varchar(100) DEFAULT NULL COMMENT 'MIME type',
  `file_size` int(11) DEFAULT NULL COMMENT 'File size in bytes',
  `uploaded_by` varchar(255) NOT NULL COMMENT 'Employee ID who uploaded',
  `document_type` enum('resignation_letter','clearance_form','other') DEFAULT 'other' COMMENT 'Document category',
  `uploaded_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_resignation_id` (`resignation_id`),
  KEY `idx_uploaded_by` (`uploaded_by`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci 
COMMENT='Supporting documents for resignations';

-- =====================================================
-- TABLE 4: Resignation Workflow History (Audit Trail)
-- =====================================================

CREATE TABLE IF NOT EXISTS `emp_resignation_history` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `resignation_id` int(11) NOT NULL COMMENT 'Reference to emp_resignations.id',
  `action` enum('submitted','approved','rejected','cancelled','withdrawn','modified','commented') NOT NULL COMMENT 'Action performed',
  `previous_status` enum('pending','approved','rejected','cancelled','withdrawn') DEFAULT NULL COMMENT 'Status before action',
  `new_status` enum('pending','approved','rejected','cancelled','withdrawn') NOT NULL COMMENT 'Status after action',
  `action_by` varchar(255) NOT NULL COMMENT 'Employee ID who performed action',
  `action_date` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'When action occurred',
  `notes` text DEFAULT NULL COMMENT 'Additional notes or comments',
  `ip_address` varchar(45) DEFAULT NULL COMMENT 'IP address of user',
  PRIMARY KEY (`id`),
  KEY `idx_resignation_id` (`resignation_id`),
  KEY `idx_action_by` (`action_by`),
  KEY `idx_action_date` (`action_date`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci 
COMMENT='Audit trail for resignation workflow';

-- =====================================================
-- TABLE 5: Resignation Exit Clearance Checklist
-- =====================================================

CREATE TABLE IF NOT EXISTS `emp_resignation_clearance` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `resignation_id` int(11) NOT NULL COMMENT 'Reference to emp_resignations.id',
  `dept_name` varchar(100) NOT NULL COMMENT 'Department name (IT, Finance, HR, etc)',
  `cleared_by` varchar(255) DEFAULT NULL COMMENT 'Employee ID who cleared',
  `clearance_status` enum('pending','cleared','issues') DEFAULT 'pending' COMMENT 'Clearance status',
  `clearance_date` datetime DEFAULT NULL COMMENT 'When cleared',
  `notes` text DEFAULT NULL COMMENT 'Clearance notes or issues',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_resignation_id` (`resignation_id`),
  KEY `idx_clearance_status` (`clearance_status`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci 
COMMENT='Exit clearance checklist by department';

-- =====================================================
-- Insert default clearance departments for each resignation
-- This will be handled by application logic
-- =====================================================

COMMIT;
