-- Complete emp_loan table structure for the new loan system
-- This is the final structure after applying all updates
-- Generated on 2025-11-11

DROP TABLE IF EXISTS `emp_loan`;

CREATE TABLE `emp_loan` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `emp_id` varchar(20) NOT NULL COMMENT 'Employee ID from employees table',
  `loan_type` enum('regular','emergency','end_of_service','housing','advance_salary') NOT NULL DEFAULT 'end_of_service' COMMENT 'Type of loan',
  `loan_amount` decimal(10,2) NOT NULL COMMENT 'Principal loan amount requested',
  `installments` int(11) NOT NULL DEFAULT 1 COMMENT 'Number of monthly installments',
  `reason` text DEFAULT NULL COMMENT 'Reason for loan application',
  `interest_rate` decimal(5,2) NOT NULL DEFAULT 0.00 COMMENT 'Interest rate (0 for new loans)',
  `total_payable` decimal(10,2) NOT NULL COMMENT 'Total amount to be repaid (same as loan_amount for new loans)',
  `monthly_deduction` decimal(10,2) NOT NULL COMMENT 'Amount deducted per month',
  `start_date` date NOT NULL COMMENT 'Date when deductions start (first day of next month)',
  `end_date` date DEFAULT NULL COMMENT 'Expected completion date (calculated)',
  `status` varchar(50) NOT NULL DEFAULT 'pending' COMMENT 'Current status: pending, approved, rejected, paid, processing',
  `current_approval_level` int(11) DEFAULT 1 COMMENT 'Current approval level in chain',
  `approved_by_user_ids` text DEFAULT NULL COMMENT 'JSON array of user IDs who approved',
  `rejected_by` varchar(50) DEFAULT NULL COMMENT 'User ID who rejected',
  `rejection_reason` text DEFAULT NULL COMMENT 'Reason for rejection',
  `rejection_date` datetime DEFAULT NULL COMMENT 'Date of rejection',
  `modified_by` varchar(50) DEFAULT NULL COMMENT 'User ID who modified loan details',
  `modification_note` text DEFAULT NULL COMMENT 'Note about modification',
  `original_amount` decimal(10,2) DEFAULT NULL COMMENT 'Original requested amount before modification',
  `original_installments` int(11) DEFAULT NULL COMMENT 'Original installments before modification',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp() COMMENT 'When loan was applied',
  
  -- Legacy approval columns (keep for backward compatibility or remove if migrating fully)
  `dept_manager_status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `hr_manager_status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `hr_assistant_status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `finance_manager_status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `gm_status` enum('pending','approved','rejected') NOT NULL DEFAULT 'pending',
  `finance_assistant_status` enum('pending','processed') NOT NULL DEFAULT 'pending',
  
  -- Finance disbursement tracking
  `disbursement_receipt_id` varchar(255) DEFAULT NULL COMMENT 'Receipt ID for disbursement to employee',
  `disbursement_attachment` varchar(255) DEFAULT NULL COMMENT 'Proof of payment file path',
  
  PRIMARY KEY (`id`),
  KEY `idx_emp_id` (`emp_id`),
  KEY `idx_loan_type` (`loan_type`),
  KEY `idx_emp_status` (`emp_id`, `status`),
  KEY `idx_current_approval` (`current_approval_level`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci COMMENT='Employee loan applications and tracking';

-- Sample data for the new loan types
INSERT INTO `emp_loan` (`emp_id`, `loan_type`, `loan_amount`, `installments`, `reason`, `interest_rate`, `total_payable`, `monthly_deduction`, `start_date`, `end_date`, `status`) VALUES
('5127', 'end_of_service', 5000.00, 6, 'Personal emergency', 0.00, 5000.00, 833.33, '2025-12-01', '2026-05-01', 'pending'),
('5127', 'housing', 6000.00, 6, 'Housing advance', 0.00, 6000.00, 1000.00, '2025-12-01', '2026-05-01', 'pending'),
('5127', 'advance_salary', 2500.00, 1, 'Salary advance', 0.00, 2500.00, 2500.00, '2025-12-01', '2025-12-01', 'pending');
