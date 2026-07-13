/**
 * Migration: Create Settlement Attachments Table
 * Purpose: Store multiple attachments for settlement records
 * Date: 2026-02-09
 */

-- Create settlement_attachments table
CREATE TABLE IF NOT EXISTS `settlement_attachments` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY COMMENT 'Unique attachment ID',
    `settlement_id` INT NOT NULL COMMENT 'Foreign key to settlement_records.id',
    `request_inv_no` VARCHAR(50) NOT NULL COMMENT 'Settlement reference number',
    `emp_id` INT NOT NULL COMMENT 'Employee ID for reference',
    `file_name` VARCHAR(255) NOT NULL COMMENT 'Original uploaded file name',
    `file_path` VARCHAR(500) NOT NULL COMMENT 'Path to stored file',
    `file_type` VARCHAR(100) COMMENT 'MIME type of file',
    `file_size` BIGINT COMMENT 'File size in bytes',
    `attachment_category` ENUM('wps_file', 'payment_proof', 'supporting_document', 'other') DEFAULT 'supporting_document' COMMENT 'Type of attachment',
    `uploaded_by` INT COMMENT 'Employee ID of who uploaded',
    `uploaded_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'Upload timestamp',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    CONSTRAINT `fk_settlement_attachments_settlement` 
        FOREIGN KEY (`settlement_id`) 
        REFERENCES `settlement_records`(`id`) 
        ON DELETE CASCADE 
        ON UPDATE CASCADE,
    
    KEY `idx_settlement_id` (`settlement_id`),
    KEY `idx_request_inv_no` (`request_inv_no`),
    KEY `idx_emp_id` (`emp_id`),
    KEY `idx_attachment_category` (`attachment_category`),
    KEY `idx_uploaded_at` (`uploaded_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Store attachments for settlement records';

-- Create settlement_attachments_audit table (optional, for audit trail)
CREATE TABLE IF NOT EXISTS `settlement_attachments_audit` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `attachment_id` INT UNSIGNED,
    `settlement_id` INT,
    `emp_id` INT,
    `action` ENUM('uploaded', 'deleted', 'replaced', 'downloaded') DEFAULT 'uploaded',
    `file_name` VARCHAR(255),
    `uploaded_by` INT,
    `ip_address` VARCHAR(45),
    `user_agent` TEXT,
    `action_timestamp` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    KEY `idx_settlement_id` (`settlement_id`),
    KEY `idx_attachment_id` (`attachment_id`),
    KEY `idx_action_timestamp` (`action_timestamp`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Audit trail for settlement attachment operations';
