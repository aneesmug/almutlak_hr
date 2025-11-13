-- Quick fix to update loan status from empty string to proper generic status
-- This fixes the "unknown" status issue shown in the screenshot
-- Run this after the main migration

-- Update loans with empty loan_type to 'end_of_service'
UPDATE `emp_loan` 
SET `loan_type` = 'end_of_service' 
WHERE `loan_type` = '' OR `loan_type` IS NULL;

-- Update loans with 'pending' status to 'pending_level_1'
UPDATE `emp_loan` 
SET `status` = 'pending_level_1' 
WHERE `status` = 'pending' OR `status` = '';

-- Update current_approval_level for pending loans
UPDATE `emp_loan` 
SET `current_approval_level` = 1 
WHERE `status` LIKE 'pending%' AND `current_approval_level` IS NULL;

-- Generate inv_no for loans that don't have one yet
UPDATE `emp_loan` 
SET `inv_no` = CONCAT(
    'LOAN-',
    DATE_FORMAT(`created_at`, '%Y%m%d'),
    '-',
    `emp_id`,
    '-',
    SUBSTRING(MD5(CONCAT(`id`, `emp_id`, `created_at`)), 1, 4)
)
WHERE `inv_no` IS NULL OR `inv_no` = '';

-- Verify the updates
SELECT id, emp_id, loan_type, loan_amount, status, inv_no 
FROM emp_loan 
WHERE id = 4;
