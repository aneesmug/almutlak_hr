-- ===================================================================
-- MIGRATION: Add Payment Workflow Fields to emp_vacation Table
-- PURPOSE: Support two-step approval for HR Payroll (Payment + Approval)
-- CREATED: 2025-01-06
-- ===================================================================

-- Add payment status field
ALTER TABLE `emp_vacation` 
ADD COLUMN `payment_status` ENUM('pending_payment', 'paid', 'needs_modification') 
DEFAULT 'pending_payment' 
COMMENT 'Payment status for final HR Payroll approval step: pending_payment | paid | needs_modification'
AFTER `payroll_note`;

-- Add payment processing date
ALTER TABLE `emp_vacation` 
ADD COLUMN `payment_date` DATETIME NULL 
DEFAULT NULL 
COMMENT 'Timestamp when payment was processed'
AFTER `payment_status`;

-- Add payment modification tracking
ALTER TABLE `emp_vacation` 
ADD COLUMN `payment_modified_date` DATETIME NULL 
DEFAULT NULL 
COMMENT 'Timestamp when payment was last modified'
AFTER `payment_date`;

-- Add who modified the payment
ALTER TABLE `emp_vacation` 
ADD COLUMN `payment_modified_by` VARCHAR(50) NULL 
DEFAULT NULL 
COMMENT 'Employee ID of user who modified payment'
AFTER `payment_modified_date`;

-- Add flag to track if payment step completed
ALTER TABLE `emp_vacation` 
ADD COLUMN `is_payment_completed` TINYINT(1) DEFAULT 0 
COMMENT 'Flag: 0=payment pending, 1=payment processing complete (can now approve)'
AFTER `payment_modified_by`;

-- Create index on payment_status for queries
ALTER TABLE `emp_vacation` 
ADD INDEX `idx_payment_status` (`payment_status`);

-- ===================================================================
-- VERIFICATION QUERIES (run after migration)
-- ===================================================================
-- SELECT * FROM emp_vacation LIMIT 1 \G
-- DESCRIBE emp_vacation;
