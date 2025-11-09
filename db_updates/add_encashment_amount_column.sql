-- Add encashment_amount column to emp_vacation table
-- This will store the calculated encashment salary when remarks = 'Encashment'
-- Execute this SQL in phpMyAdmin or MySQL command line

USE almutlak;

ALTER TABLE `emp_vacation` 
ADD COLUMN `encashment_amount` DECIMAL(10,2) DEFAULT NULL COMMENT 'Encashed vacation days salary amount' AFTER `permit_fee`;

-- Verify the column was added
DESCRIBE `emp_vacation`;
