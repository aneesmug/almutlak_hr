-- Add attachment and note_type columns to emp_notice table
-- Migration for Employee Note Enhancement
-- Created: 2025-11-12

ALTER TABLE `emp_notice` 
ADD COLUMN `note_type` VARCHAR(100) DEFAULT NULL COMMENT 'Type of note: warning, sick_leave, appreciation, etc.' AFTER `note`,
ADD COLUMN `attachment` VARCHAR(255) DEFAULT NULL COMMENT 'File path for attached document' AFTER `note_type`;

-- Optional: Add index for better query performance
ALTER TABLE `emp_notice` ADD INDEX `idx_note_type` (`note_type`);
