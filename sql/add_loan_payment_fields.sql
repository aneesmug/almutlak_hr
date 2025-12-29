-- Add payment processing fields to emp_loan table
-- Run this SQL to add the required columns for Finance Manager payment processing

-- Check if columns already exist before adding
SET @dbname = DATABASE();
SET @tablename = 'emp_loan';

-- Add payer_emp_id column (who will process the payment)
SET @payer_col = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = 'payer_emp_id');

SET @sql = IF(@payer_col = 0,
    'ALTER TABLE `emp_loan` ADD COLUMN `payer_emp_id` INT(11) NULL DEFAULT NULL COMMENT ''Employee ID of the person who will process the payment'' AFTER `final_approved_amount`',
    'SELECT ''Column payer_emp_id already exists'' AS result');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add payment_date column (when payment was processed)
SET @payment_date_col = (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tablename AND COLUMN_NAME = 'payment_date');

SET @sql = IF(@payment_date_col = 0,
    'ALTER TABLE `emp_loan` ADD COLUMN `payment_date` DATETIME NULL DEFAULT NULL COMMENT ''Date when payment was processed by Finance Manager'' AFTER `payer_emp_id`',
    'SELECT ''Column payment_date already exists'' AS result');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Verify columns were added
DESCRIBE `emp_loan`;
