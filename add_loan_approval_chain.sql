-- Add new approval chain columns to emp_loan table
ALTER TABLE `emp_loan`
  ADD COLUMN `audit_status` VARCHAR(32) DEFAULT NULL AFTER `hr_manager_status`,
  ADD COLUMN `payer_status` VARCHAR(32) DEFAULT NULL AFTER `finance_manager_status`;

-- Optionally, add columns to store approver IDs and dates for audit and payer
ALTER TABLE `emp_loan`
  ADD COLUMN `audit_approver_id` VARCHAR(32) DEFAULT NULL AFTER `audit_status`,
  ADD COLUMN `audit_approved_at` DATETIME DEFAULT NULL AFTER `audit_approver_id`,
  ADD COLUMN `payer_approver_id` VARCHAR(32) DEFAULT NULL AFTER `payer_status`,
  ADD COLUMN `payer_approved_at` DATETIME DEFAULT NULL AFTER `payer_approver_id`;

-- Update status values for new stages
-- audit_pending, payer_pending, paid
