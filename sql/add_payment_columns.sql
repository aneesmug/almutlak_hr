ALTER TABLE `request_approvers` ADD COLUMN `payment_amount` DECIMAL(10, 2) DEFAULT NULL AFTER `note`;
ALTER TABLE `request_approvers` ADD COLUMN `payment_proof_path` VARCHAR(500) DEFAULT NULL AFTER `payment_amount`;
ALTER TABLE `request_approvers` ADD INDEX `idx_payment_status` (`status`, `payment_amount`);
