-- Add attachment support to delivery system
-- Migration: Add attachment_filename column to general_request_deliveries table

ALTER TABLE `general_request_deliveries` 
ADD COLUMN `attachment_filename` VARCHAR(255) NULL AFTER `delivery_date`;

-- Note: This column will store the filename of the attachment
-- File location: /assets/delivery_attachments/{attachment_filename}
-- Format: {inv_no}_{YmdHis}_{uniqid}.{ext}
-- Example: SR-2025-001_20250131120530_5dfb4c7ef8901.pdf
