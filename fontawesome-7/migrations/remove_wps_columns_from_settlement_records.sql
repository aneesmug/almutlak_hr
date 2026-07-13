-- ============================================================
-- MIGRATION: Remove old WPS implementation from settlement_records
-- DESCRIPTION: Remove WPS-related columns from settlement_records table
--   since we now use the settlement_attachments table for file handling
-- DATE: 2026-02-09
-- ============================================================

-- Ensure the table exists before attempting to drop columns
-- Drop columns that stored WPS file information
ALTER TABLE `settlement_records` DROP COLUMN IF EXISTS `wps_file_name`;
ALTER TABLE `settlement_records` DROP COLUMN IF EXISTS `wps_file_path`;
ALTER TABLE `settlement_records` DROP COLUMN IF EXISTS `wps_uploaded_by`;
ALTER TABLE `settlement_records` DROP COLUMN IF EXISTS `wps_uploaded_at`;
ALTER TABLE `settlement_records` DROP COLUMN IF EXISTS `wps_upload_status`;

-- Verify columns have been removed
-- SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS 
-- WHERE TABLE_NAME='settlement_records' AND COLUMN_NAME LIKE 'wps_%';

-- Note: All attachment data should now be stored in settlement_attachments table
-- with categorization by attachment_category (wps_file, payment_proof, supporting_document, other)
