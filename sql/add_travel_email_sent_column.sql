-- Add column to track if travel company email has been sent
-- This prevents duplicate emails

ALTER TABLE `emp_vacation` 
ADD COLUMN `travel_email_sent` TINYINT(1) DEFAULT 0 COMMENT 'Flag to track if travel company email has been sent (0=not sent, 1=sent)' 
AFTER `arrival_date`;

-- Add index for better query performance
ALTER TABLE `emp_vacation` 
ADD INDEX `idx_travel_email_sent` (`travel_email_sent`);

-- Instructions:
-- Run this SQL to add the tracking column for travel company emails
-- This will prevent sending duplicate emails to the traveling company
