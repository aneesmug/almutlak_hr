-- Add departure_date and arrival_date columns to emp_vacation table
-- These fields are only required for Fly + Annual vacation requests
-- Run this migration to update the database schema

ALTER TABLE `emp_vacation` 
ADD COLUMN `departure_date` DATE DEFAULT NULL COMMENT 'Flight departure date (for Fly + Annual vacations only)' AFTER `return_date`,
ADD COLUMN `arrival_date` DATE DEFAULT NULL COMMENT 'Flight arrival date (for Fly + Annual vacations only)' AFTER `departure_date`;

-- Update existing records (optional - set to NULL by default)
-- No data migration needed as these are new fields for future vacation requests
