-- ================================================================
-- Employee Performance Evaluation System
-- Created: November 9, 2025
-- Description: This table stores employee performance evaluations
--              conducted by department managers
-- ================================================================

CREATE TABLE IF NOT EXISTS `emp_evaluations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `manager_emp_id` varchar(255) NOT NULL COMMENT 'Employee ID of the manager who conducted the evaluation',
  `employee_emp_id` varchar(255) NOT NULL COMMENT 'Employee ID of the person being evaluated',
  `dept_id` int(11) NOT NULL COMMENT 'Department ID at time of evaluation',
  `dept_name` varchar(255) DEFAULT NULL COMMENT 'Department name snapshot',
  `employee_name` varchar(255) DEFAULT NULL COMMENT 'Employee name snapshot',
  `employee_position` varchar(255) DEFAULT NULL COMMENT 'Job position snapshot from ac_jobs',
  
  -- Evaluation Criteria (1-10 scale, default 10)
  `punctuality` tinyint(2) UNSIGNED NOT NULL DEFAULT 10 COMMENT 'الإنتظام وعدم التأخير - Punctuality Attendance',
  `achieving_time` tinyint(2) UNSIGNED NOT NULL DEFAULT 10 COMMENT 'التحقيق في الوقت المحدد - Achieving at the specified time',
  `job_knowledge` tinyint(2) UNSIGNED NOT NULL DEFAULT 10 COMMENT 'معرفة الوظيفة - Knowledge of job',
  `problem_solving` tinyint(2) UNSIGNED NOT NULL DEFAULT 10 COMMENT 'القدرة على حل المشاكل - The Ability to solve problems',
  `feedback_receptiveness` tinyint(2) UNSIGNED NOT NULL DEFAULT 10 COMMENT 'تقبل التوجيهات والتعليمات - Receptiveness to Feedback and Instructions',
  `self_development` tinyint(2) UNSIGNED NOT NULL DEFAULT 10 COMMENT 'السعي لتطوير المهارات والمعرفة وتحسين الأداء بإستمرار - Self & Professional Development',
  `work_under_pressure` tinyint(2) UNSIGNED NOT NULL DEFAULT 10 COMMENT 'العمل تحت الضغط - Work under pressure',
  `communication_teamwork` tinyint(2) UNSIGNED NOT NULL DEFAULT 10 COMMENT 'مهارات التواصل والعمل الجماعي - Communication skills and Teamwork',
  `creativity_response` tinyint(2) UNSIGNED NOT NULL DEFAULT 10 COMMENT 'الإبداع وسرعة الإستجابة - Creativity and speed of response',
  `initiative_cooperation` tinyint(2) UNSIGNED NOT NULL DEFAULT 10 COMMENT 'المبادرة والتعاون - Initiative and cooperation',
  
  -- Additional Fields
  `observation` text DEFAULT NULL COMMENT 'Remarks or observations from the manager',
  `total_score` smallint(3) UNSIGNED NOT NULL DEFAULT 100 COMMENT 'Total evaluation score (sum of all criteria)',
  
  -- Metadata
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  
  PRIMARY KEY (`id`),
  KEY `idx_employee` (`employee_emp_id`),
  KEY `idx_manager` (`manager_emp_id`),
  KEY `idx_dept` (`dept_id`),
  KEY `idx_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Employee performance evaluations by department managers';

-- Add constraints for score validation (1-10 range)
-- Note: MySQL TINYINT UNSIGNED allows 0-255, application layer will enforce 1-10
ALTER TABLE `emp_evaluations`
  ADD CONSTRAINT `chk_punctuality` CHECK (`punctuality` BETWEEN 1 AND 10),
  ADD CONSTRAINT `chk_achieving_time` CHECK (`achieving_time` BETWEEN 1 AND 10),
  ADD CONSTRAINT `chk_job_knowledge` CHECK (`job_knowledge` BETWEEN 1 AND 10),
  ADD CONSTRAINT `chk_problem_solving` CHECK (`problem_solving` BETWEEN 1 AND 10),
  ADD CONSTRAINT `chk_feedback_receptiveness` CHECK (`feedback_receptiveness` BETWEEN 1 AND 10),
  ADD CONSTRAINT `chk_self_development` CHECK (`self_development` BETWEEN 1 AND 10),
  ADD CONSTRAINT `chk_work_under_pressure` CHECK (`work_under_pressure` BETWEEN 1 AND 10),
  ADD CONSTRAINT `chk_communication_teamwork` CHECK (`communication_teamwork` BETWEEN 1 AND 10),
  ADD CONSTRAINT `chk_creativity_response` CHECK (`creativity_response` BETWEEN 1 AND 10),
  ADD CONSTRAINT `chk_initiative_cooperation` CHECK (`initiative_cooperation` BETWEEN 1 AND 10),
  ADD CONSTRAINT `chk_total_score` CHECK (`total_score` BETWEEN 10 AND 100);

-- ================================================================
-- USAGE INSTRUCTIONS:
-- 1. Run this SQL file in your MySQL database
-- 2. Access the evaluation page at: employee_evaluation.php
-- 3. Only department managers can evaluate their employees
-- 4. All evaluation criteria use a 1-10 scale (10 being excellent)
-- 5. Total score is automatically calculated from all criteria
-- ================================================================
