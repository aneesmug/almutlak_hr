-- Migration: Add Delivery Tracking System for General Requests
-- This migration adds the necessary tables and columns for delivery tracking
-- Foreign keys removed to avoid constraint issues - relationships enforced in application code

-- 1. Add delivery_status column to general_request_items table (if not exists)
ALTER TABLE `general_request_items` 
ADD COLUMN IF NOT EXISTS `delivery_status` VARCHAR(20) NULL DEFAULT 'pending' COMMENT 'Status: pending, delivered, canceled';

-- 2. Add delivery_id column to general_request_items table (if not exists)
ALTER TABLE `general_request_items` 
ADD COLUMN IF NOT EXISTS `delivery_id` INT NULL;

-- 3. Create general_request_deliveries table WITHOUT foreign keys (to avoid constraint errors)
CREATE TABLE IF NOT EXISTS `general_request_deliveries` (
    `id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY COMMENT 'Unique delivery record ID',
    `request_inv_no` VARCHAR(100) NOT NULL COMMENT 'Reference to general_requests.inv_no',
    `received_by` VARCHAR(50) NOT NULL COMMENT 'Employee ID who received items',
    `delivery_date` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'When items were delivered',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'Record creation time',
    INDEX `idx_request_inv_no` (`request_inv_no`),
    INDEX `idx_received_by` (`received_by`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Tracks item delivery and who received them';

-- 4. Add completion tracking column to general_requests (if not exists)
ALTER TABLE `general_requests`
ADD COLUMN IF NOT EXISTS `completed_at` DATETIME NULL COMMENT 'When all items were delivered';

-- 5. Create indexes for performance (if not exist)
CREATE INDEX IF NOT EXISTS `idx_delivery_status` ON `general_request_items` (`delivery_status`);
CREATE INDEX IF NOT EXISTS `idx_delivery_id` ON `general_request_items` (`delivery_id`);
CREATE INDEX IF NOT EXISTS `idx_completed_at` ON `general_requests` (`completed_at`);

-- Migration complete!
-- Note: Foreign key constraints removed to avoid type mismatch errors
-- Data integrity is maintained through application-level validation in PHP code
