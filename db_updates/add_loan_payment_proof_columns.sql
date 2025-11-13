-- Add payment proof and final approved amount columns to emp_loan table
-- This allows the final payer (Finance Officer - Level 7) to upload payment proof
-- and specify the actual amount paid before final approval

ALTER TABLE `emp_loan` 
ADD COLUMN `payment_proof_file` VARCHAR(255) NULL COMMENT 'Payment proof uploaded by finance officer' AFTER `installments`,
ADD COLUMN `final_approved_amount` DECIMAL(10,2) NULL COMMENT 'Final amount approved and paid by finance officer' AFTER `payment_proof_file`;

-- Add index for better query performance
ALTER TABLE `emp_loan` ADD INDEX `idx_payment_proof` (`payment_proof_file`);

-- Display changes
SELECT 'Columns added successfully to emp_loan table' AS Status;
