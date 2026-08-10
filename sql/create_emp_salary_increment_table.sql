-- Salary Increment Request Table Migration
-- Direct Supervisor submits a salary increment request for one of their assigned employees.
-- This is a workflow/approval record only - it does NOT modify any salary/payroll table.

CREATE TABLE IF NOT EXISTS `emp_salary_increment` (
    `id` INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `request_inv_no` VARCHAR(100) NOT NULL UNIQUE COMMENT 'Salary Increment Request Unique ID (SI-YYYYMMDDHHMMSS-EMPID-RND)',
    `emp_id` VARCHAR(255) NOT NULL COMMENT 'Employee receiving the increment',
    `submitted_by` VARCHAR(255) NOT NULL COMMENT 'Direct supervisor who submitted the request',

    `increment_amount` DECIMAL(10,2) NOT NULL COMMENT 'Requested increment amount (max 2000)',
    `reason` TEXT NOT NULL COMMENT 'Reason for the increment request',

    `has_evaluation` ENUM('yes','no') NOT NULL DEFAULT 'no' COMMENT 'Whether the employee has been evaluated',
    `evaluation_score` DECIMAL(5,2) DEFAULT NULL COMMENT 'Evaluation score entered by the supervisor',

    -- Approval Workflow
    `current_status` ENUM('pending_approval', 'approved', 'rejected', 'cancelled') DEFAULT 'pending_approval' COMMENT 'Current workflow status',
    `current_approval_level` INT(11) DEFAULT 1 COMMENT 'Current approval stage (1, 2, 3, etc.)',

    -- Tracking
    `created_by` INT(11) COMMENT 'User who created the request',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'Request submission timestamp',
    `modified_by` INT(11) COMMENT 'User who last modified the request',
    `last_modified` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT 'Last modification timestamp',

    -- Indexes for performance
    KEY `idx_emp_id` (`emp_id`),
    KEY `idx_submitted_by` (`submitted_by`),
    KEY `idx_request_inv_no` (`request_inv_no`),
    KEY `idx_status` (`current_status`),
    KEY `idx_created_at` (`created_at`),

    -- Foreign Keys
    CONSTRAINT `fk_emp_salary_increment_emp` FOREIGN KEY (`emp_id`) REFERENCES `employees` (`emp_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci COMMENT='Salary Increment Requests with Multi-Level Approval';
