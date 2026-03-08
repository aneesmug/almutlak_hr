-- Add travel_email_sent column to emp_business_trip table
-- This column tracks whether the travel/ticket purchase email has been sent to the travel company

ALTER TABLE `emp_business_trip` ADD COLUMN IF NOT EXISTS `travel_email_sent` TINYINT(1) DEFAULT 0;

-- Add index for better query performance
ALTER TABLE `emp_business_trip` ADD INDEX IF NOT EXISTS `idx_travel_email_sent` (`travel_email_sent`);
