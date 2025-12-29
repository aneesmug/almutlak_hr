-- Migration: Add payment tracking columns to request_approvers table
-- Purpose: Store payment amount and proof for payer-level approvers in vacation/leave requests
-- Date: December 2025

ALTER TABLE `request_approvers` ADD COLUMN `payment_amount` DECIMAL(10, 2) DEFAULT NULL COMMENT 'Amount paid by the payer (for payer role)' AFTER `note`;
ALTER TABLE `request_approvers` ADD COLUMN `payment_proof_path` VARCHAR(500) DEFAULT NULL COMMENT 'Path to payment proof document' AFTER `payment_amount`;

-- Create index for faster lookups
ALTER TABLE `request_approvers` ADD INDEX `idx_payment_status` (`status`, `payment_amount`);
