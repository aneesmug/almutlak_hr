-- ============================================================
-- SETTLEMENT ATTACHMENTS SYSTEM - OLD WPS IMPLEMENTATION REMOVAL
-- ============================================================
-- Completed: 2026-02-09
-- Purpose: Remove legacy WPS file columns from settlement_records table
--          All file storage now handled via settlement_attachments table
-- ============================================================

## CHANGES SUMMARY

### 1. DATABASE MIGRATIONS CREATED

#### remove_wps_columns_from_settlement_records.sql
- Removes WPS-related columns from settlement_records table:
  * wps_file_name (VARCHAR)
  * wps_file_path (VARCHAR)
  * wps_uploaded_by (INT)
  * wps_uploaded_at (TIMESTAMP)
  * wps_upload_status (VARCHAR/ENUM)
  
- NOTE: Execute this migration after verifying all WPS data has been migrated
  Command: mysql -u root -p almutlak_db < migrations/remove_wps_columns_from_settlement_records.sql

### 2. PHP CODE CHANGES

#### includes/ajaxFile/settlement_handler.php
- Removed case 'approve_settlement_with_wps': (and corresponding handler call)
- Removed case 'upload_wps_file': (and corresponding handler call)
- Removed function approveSettlementWithWPS() (~290 lines)
  * This function handled both approval workflow AND WPS file uploads
  * Functionality replaced by approveSettlementWithAttachments()
  
- Removed function uploadWPSFile() (~150 lines)
  * Standalone WPS file upload endpoint
  * Functionality replaced by settlement_attachments table with audit trail

#### all_settlements.php
- No changes needed - already uses approveSettlement() function
- Modal implementation already uses approveSettlementWithAttachments endpoint

### 3. NEW SYSTEM ARCHITECTURE

#### settlement_attachments Table Structure:
- id (Primary Key)
- settlement_id (FK → settlement_records.id) 
- file_name (VARCHAR) - Original filename
- file_path (VARCHAR) - Server path (/uploads/settlement_attachments/YYYY/MM/)
- file_type (VARCHAR) - File extension
- file_size (BIGINT) - File size in bytes
- attachment_category (ENUM) - wps_file, payment_proof, supporting_document, other
- uploaded_by (INT FK → employees.emp_id)
- uploaded_at (TIMESTAMP)
- created_at (TIMESTAMP)
- updated_at (TIMESTAMP)
- is_deleted (TINYINT) - Soft delete flag

#### settlement_attachments_audit Table:
- Tracks all operations: uploaded, deleted, replaced, downloaded
- Records IP address, user, timestamp, and action details
- Retention: 90 days (configurable)

### 4. FILE STORAGE LOCATIONS

#### Old WPS Location (Deprecated):
- /uploads/wps_files/YYYY/MM/settlement_*.ext

#### New Attachment Location (Active):
- /uploads/settlement_attachments/YYYY/MM/settlement_*.ext
- Support for all previous file types plus more
- Maintained directory structure for organization

### 5. BENEFITS OF NEW SYSTEM

✓ Multiple file support per settlement (up to 10 files)
✓ File categorization (WPS files, payment proofs, supporting documents)
✓ Complete audit trail of all operations
✓ Access control and permissions (owner/approver/admin only)
✓ Secure download handler with proper HTTP headers
✓ MIME type validation for security
✓ Soft delete capability with recovery option
✓ Better data integrity with PDO prepared statements

### 6. MIGRATION CHECKLIST

Before executing remove_wps_columns_from_settlement_records.sql:

☐ Backup settlement_records table
☐ Verify all WPS files have been migrated to settlement_attachments (if needed)
☐ Export WPS data for historical records (if required)
☐ Confirm all tests pass with new attachment system
☐ Brief users on new settlement approval workflow with attachments
☐ Execute migration during maintenance window
☐ Verify no foreign key constraint errors
☐ Check disk space in /uploads/settlement_attachments/

### 7. CODE REFERENCES

#### Removed Functions:
- approveSettlementWithWPS() - Handled approval + single WPS file upload
- uploadWPSFile() - Standalone WPS upload endpoint

#### Active Functions:
- approveSettlementWithAttachments() - NEW: Handles approval + multiple files + audit
- getSettlementAttachments() - Helper: Retrieve all attachments
- getSettlementAttachmentsByCategory() - Helper: Filter by type
- renderSettlementAttachments() - Helper: Display with icons
- deleteSettlementAttachment() - Helper: Secure removal
- download_settlement_attachment.php - Secure download with access control

#### JavaScript Functions:
- approveSettlement() - Modal trigger
- initializeSettlementDropzone() - File upload UI
- Frontend validation and error handling via ajaxErrorHandling.js

### 8. ROLLBACK PROCEDURE (if needed)

If issues arise after migration:
1. Restore settlement_records from backup
2. Restore old WPS columns from migration file (reverse ALTER statements)
3. Revert settlement_handler.php code from git history
4. Revert all_settlements.php to use approveSettlementWithWPS
5. Test thoroughly before re-deploying new system

### 9. NOTES FOR FUTURE MAINTENANCE

- The settlement_attachments table is extensible
- Can add more attachment_category values without code changes
- Audit table allows for compliance reporting and forensics
- File storage can be moved to cloud storage (S3, Azure, etc.) with path updates
- Consider archiving old WPS files to cold storage after 1 year

### 10. EXECUTION STEPS

1. ✅ Create remove_wps_columns_from_settlement_records.sql migration
2. ✅ Remove old WPS functions from settlement_handler.php
3. ⏳ Monitor production usage for 1 week
4. ⏳ Execute database migration (after approval)
5. ⏳ Verify no errors from missing columns
6. ⏳ Archive old WPS files (optional, after 30 days)

====================================================
End of WPS Implementation Removal Documentation
====================================================
