-- Migration: Add attachment_filename column to general_request_items table
-- Purpose: Store individual attachments per item during delivery
-- Date: 2025-12-17

ALTER TABLE `general_request_items` 
ADD COLUMN `attachment_filename` VARCHAR(255) NULL 
AFTER `delivery_status`;

-- Index for faster queries
CREATE INDEX idx_attachment_filename ON general_request_items(attachment_filename);

-- Example:
-- SELECT * FROM general_request_items WHERE attachment_filename IS NOT NULL;
