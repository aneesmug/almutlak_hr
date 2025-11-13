-- Update emp_loan table structure for new loan type system
-- Generated on 2025-11-11
-- This updates the loan_type enum and removes interest-based calculations

-- Step 1: Update the loan_type enum to include new loan types
ALTER TABLE `emp_loan` 
MODIFY COLUMN `loan_type` enum('regular','emergency','end_of_service','housing','advance_salary') NOT NULL DEFAULT 'end_of_service';

-- Step 2: Add installments column to track number of monthly installments
ALTER TABLE `emp_loan`
ADD COLUMN `installments` int(11) NOT NULL DEFAULT 1 COMMENT 'Number of monthly installments' AFTER `loan_amount`;

-- Step 3: Add reason column for loan application
ALTER TABLE `emp_loan`
ADD COLUMN `reason` text DEFAULT NULL COMMENT 'Reason for loan application' AFTER `installments`;

-- Step 4: Modify interest_rate to allow 0 (new loans don't use interest)
ALTER TABLE `emp_loan`
MODIFY COLUMN `interest_rate` decimal(5,2) NOT NULL DEFAULT 0.00;

-- Step 5: Make end_date nullable since advance_salary loans are 1 month only
ALTER TABLE `emp_loan`
MODIFY COLUMN `end_date` date DEFAULT NULL;

-- Step 6: Update status field to use generic approval chain
-- The status will be managed by the approval_request_types system
ALTER TABLE `emp_loan`
MODIFY COLUMN `status` varchar(50) NOT NULL DEFAULT 'pending';

-- Step 7: Add columns to track which approval level is current
ALTER TABLE `emp_loan`
ADD COLUMN `current_approval_level` int(11) DEFAULT 1 COMMENT 'Current approval level in chain' AFTER `status`;

ALTER TABLE `emp_loan`
ADD COLUMN `approved_by_user_ids` text DEFAULT NULL COMMENT 'JSON array of user IDs who approved' AFTER `current_approval_level`;

-- Step 8: Add rejection tracking
ALTER TABLE `emp_loan`
ADD COLUMN `rejected_by` varchar(50) DEFAULT NULL COMMENT 'User ID who rejected' AFTER `approved_by_user_ids`;

ALTER TABLE `emp_loan`
ADD COLUMN `rejection_reason` text DEFAULT NULL COMMENT 'Reason for rejection' AFTER `rejected_by`;

ALTER TABLE `emp_loan`
ADD COLUMN `rejection_date` datetime DEFAULT NULL COMMENT 'Date of rejection' AFTER `rejection_reason`;

-- Step 9: Add modified tracking for HR modifications
ALTER TABLE `emp_loan`
ADD COLUMN `modified_by` varchar(50) DEFAULT NULL COMMENT 'User ID who modified loan details' AFTER `rejection_date`;

ALTER TABLE `emp_loan`
ADD COLUMN `modification_note` text DEFAULT NULL COMMENT 'Note about modification' AFTER `modified_by`;

ALTER TABLE `emp_loan`
ADD COLUMN `original_amount` decimal(10,2) DEFAULT NULL COMMENT 'Original requested amount before modification' AFTER `modification_note`;

ALTER TABLE `emp_loan`
ADD COLUMN `original_installments` int(11) DEFAULT NULL COMMENT 'Original installments before modification' AFTER `original_amount`;

-- Step 10: Update existing 'regular' and 'emergency' loans to 'end_of_service' (if needed)
-- Comment this out if you want to keep old loan types as-is
-- UPDATE `emp_loan` SET `loan_type` = 'end_of_service' WHERE `loan_type` IN ('regular', 'emergency');

-- Step 11: Set default installments based on loan amount / monthly deduction for existing loans
UPDATE `emp_loan` 
SET `installments` = CEILING(`total_payable` / `monthly_deduction`) 
WHERE `installments` = 1 AND `monthly_deduction` > 0;

-- Step 12: Update interest_rate to 0 for new loan entries
UPDATE `emp_loan` 
SET `interest_rate` = 0.00 
WHERE `loan_type` IN ('end_of_service', 'housing', 'advance_salary');

-- Step 13: Create index for better performance on loan_type queries
CREATE INDEX `idx_loan_type` ON `emp_loan` (`loan_type`);
CREATE INDEX `idx_emp_status` ON `emp_loan` (`emp_id`, `status`);
CREATE INDEX `idx_current_approval` ON `emp_loan` (`current_approval_level`);

-- Step 14: Add comments to existing columns for clarity
ALTER TABLE `emp_loan` 
MODIFY COLUMN `loan_amount` decimal(10,2) NOT NULL COMMENT 'Principal loan amount requested',
MODIFY COLUMN `total_payable` decimal(10,2) NOT NULL COMMENT 'Total amount to be repaid (same as loan_amount for new loans)',
MODIFY COLUMN `monthly_deduction` decimal(10,2) NOT NULL COMMENT 'Amount deducted per month',
MODIFY COLUMN `start_date` date NOT NULL COMMENT 'Date when deductions start (first day of next month)',
MODIFY COLUMN `status` varchar(50) NOT NULL DEFAULT 'pending' COMMENT 'Current status: pending, approved, rejected, paid, processing';

-- Optional: Drop old approval status columns if you want to fully migrate to new system
-- Uncomment these if you're sure you want to remove the old approval columns
-- WARNING: This will delete the old approval tracking data!

ALTER TABLE `emp_loan` 
DROP COLUMN `dept_manager_status`,
DROP COLUMN `hr_manager_status`,
DROP COLUMN `hr_assistant_status`,
DROP COLUMN `finance_manager_status`,
DROP COLUMN `gm_status`,
DROP COLUMN `finance_assistant_status`;


-- Display updated structure
SHOW COLUMNS FROM `emp_loan`;
