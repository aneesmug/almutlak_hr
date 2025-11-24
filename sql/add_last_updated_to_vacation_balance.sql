-- Add last_updated column to emp_vacation_balance table
-- This column tracks when the balance was last refreshed by the cron job
-- created_at remains as the anchor date for daily accrual calculations

ALTER TABLE `emp_vacation_balance` 
ADD COLUMN `last_updated` TIMESTAMP NULL DEFAULT NULL 
AFTER `created_at`;

-- Set initial value to created_at for existing records
UPDATE `emp_vacation_balance` 
SET `last_updated` = `created_at` 
WHERE `last_updated` IS NULL;

-- Add index for performance
CREATE INDEX `idx_last_updated` ON `emp_vacation_balance`(`last_updated`);
