# WPS File Upload Implementation for Settlements - Complete Guide

## Overview
When HR Payroll moves into the settlement approval process, they now see a modal to upload WPS (Payroll System) files. This document outlines the complete implementation.

## Database Changes

### Table: `settlement_records`
Added 5 new columns to track WPS file uploads:

```sql
ALTER TABLE settlement_records
ADD COLUMN wps_file_path VARCHAR(255) NULL,
ADD COLUMN wps_file_name VARCHAR(100) NULL,
ADD COLUMN wps_uploaded_by VARCHAR(20) NULL,
ADD COLUMN wps_uploaded_at TIMESTAMP NULL,
ADD COLUMN wps_upload_status ENUM('required', 'pending', 'completed') DEFAULT 'pending';
```

**Column Details:**
- `wps_file_path`: Relative path to uploaded WPS file (e.g., `/uploads/wps_files/2024/12/settlement_123_20241215_141530.xlsx`)
- `wps_file_name`: Original filename with timestamp (e.g., `settlement_123_20241215_141530.xlsx`)
- `wps_uploaded_by`: Employee ID of the person who uploaded the file
- `wps_uploaded_at`: Timestamp  of upload
- `wps_upload_status`: Status of WPS upload ('required', 'pending', 'completed')

## Files Modified/Created

### 1. **includes/ajaxFile/settlement_handler.php**
- Added `uploadWPSFile()` function to handle file uploads
- Added `'upload_wps_file'` case in the action switch statement
- Features:
  - File type validation (only .xlsx, .xls, .csv, .txt)
  - File size validation (max 10MB)
  - Organized uploads by year/month structure
  - Unique filename generation
  - Database updates
  - Audit logging in `smt_request_status` table

### 2. **assets/js/settlement-wps-helper.js** (NEW)
Created helper JavaScript file with two main functions:

**`showWPSUploadModal(settlementId, requestInvNo, employeeId, employeeName, settlementAmount)`**
- Displays SweetAlert2 modal for WPS file upload
- Shows employee details, settlement ID, and amount
- Allows file selection and optional notes
- Validates file before upload
- Can be skipped by user

**`uploadWPSFile(settlementId, requestInvNo, employeeId, file, notes)`**
- Sends file to backend via AJAX
- Shows loading indicator during upload
- Displays success/error messages
- Reloads page on successful upload

### 3. **all_applied_vac.php**
- Added script reference to `settlement-wps-helper.js`
- Added `handleSettlementCreated()` function to check if current user is HR Payroll
- Modified settlement creation success handler to call `handleSettlementCreated()`
- If user is HR Payroll, shows WPS upload modal; otherwise, reloads page

## Workflow

### Step 1: Settlement Creation
```
User creates settlement → Settlement enters approval chain
```

### Step 2: Settlement Moves to HR Payroll
```
Settlement reaches HR Payroll in approval chain
```

### Step 3: HR Payroll Approves (with WPS Upload)
```
1. Settlement creation modal appears
2. User clicks "Create Settlement"
3. Settlement is created successfully
4. If user is HR Payroll:
   a. WPS upload modal appears
   b. User selects WPS file (optional)
   c. User can add notes (optional)
   d. File is uploaded and validated
   e. Page reloads with updated settlement
5. If user is NOT HR Payroll:
   a. Page reloads immediately
```

## File Upload Process

### Frontend Validation
- File type check (only .xlsx, .xls, .csv, .txt allowed)
- File size check (max 10MB)
- Visual feedback during upload

### Backend Processing
1. Validate file parameters
2. Check file size and type
3. Create upload directory with date structure: `/uploads/wps_files/YYYY/MM/`
4. Generate unique filename with timestamp
5. Move file to upload directory
6. Update `settlement_records` table
7. Create audit log in `smt_request_status`

### Upload Directory Structure
```
/uploads/wps_files/
├── 2024/
│   ├── 12/
│   │   ├── settlement_123_20241215_141530.xlsx
│   │   └── settlement_124_20241215_142015.csv
│   └── 11/
│       └── settlement_115_20241110_093045.xlsx
```

## Database Updates After Upload

When a WPS file is successfully uploaded, the `settlement_records` table is updated:

```sql
UPDATE settlement_records SET
    wps_file_path = '/uploads/wps_files/2024/12/settlement_123_20241215_141530.xlsx',
    wps_file_name = 'settlement_123_20241215_141530.xlsx',
    wps_uploaded_by = 456,  -- Current user's emp_id
    wps_uploaded_at = CURRENT_TIMESTAMP,
    wps_upload_status = 'completed'
WHERE id = 123;
```

## Audit Trail

Upload events are logged in `smt_request_status` table with:
- `inv_no`: Settlement reference number
- `status`: 'wps_file_uploaded'
- `remarks`: File name and upload notes
- `action_by`: The employee who uploaded
- `action_date`: Timestamp of action

## User Flow Example

### For HR Payroll User
1. Open Settlements page (`all_applied_vac.php`)
2. Find pending settlements
3. Click "Create Settlement" button
4. Confirm settlement details
5. **New**: WPS Upload Modal appears
6. Select WPS file (optional)
7. Add notes if needed
8. Click "Upload WPS File"
9. File is validated and uploaded
10. Success message shown
11. Page reloads

### For Other Users (e.g., Finance)
1. Open Settlements page
2. Find pending settlements
3. Click "Create Settlement" button
4. Confirm settlement details
5. Success message shown
6. Page reloads (no WPS modal)

## Configuration

### User Type Detection
```php
$currentUserType = $_SESSION['user_type'] ?? "";
$isHRPayroll = ($currentUserType === 'hr_payroll');
```

### Accepted File Formats
- Excel: `.xlsx`, `.xls`
- CSV: `.csv`
- Text: `.txt`

### File Size Limit
- Maximum: **10 MB**

### Upload Directory Permissions
```
/uploads/wps_files/ - 755 (rwxr-xr-x)
```

## API Endpoints

### Upload WPS File
**POST** `/includes/ajaxFile/settlement_handler.php`

**Parameters:**
```
action: 'upload_wps_file'
settlement_id: integer (required)
request_inv_no: string (required)
emp_id: integer (required)
wps_file: File (required)
wps_note: string (optional - notes about upload)
```

**Response:**
```json
{
  "success": true,
  "message": "WPS file uploaded successfully",
  "file_name": "settlement_123_20241215_141530.xlsx",
  "file_path": "/uploads/wps_files/2024/12/settlement_123_20241215_141530.xlsx",
  "settlement_id": 123,
  "request_inv_no": "SETL-VAC-2024-001"
}
```

## Error Handling

### Frontend Errors
- Missing file
- Invalid file type
- File size exceeds limit
- Network/AJAX errors

### Backend Errors
- Missing required parameters
- Invalid file upload
- Database update failure
- Directory creation failure

All errors display user-friendly messages via SweetAlert2.

## Security Features

1. **File Type Whitelist**: Only specific extensions allowed
2. **File Size Limit**: 10MB maximum to prevent abuse
3. **Unique Naming**: Timestamp-based unique filenames prevent overwrite
4. **User Authentication**: Requires valid session
5. **Audit Logging**: All uploads logged for tracking
6. **Directory Structure**: Organized by date to prevent directory traversal
7. **Permission Checks**: Only HR Payroll receives the modal (checked server-side in future iterations)

## Testing Checklist

- [ ] Create settlement as non-HR user → No WPS modal
- [ ] Create settlement as HR Payroll user → WPS modal appears
- [ ] Upload valid Excel file → Success
- [ ] Upload valid CSV file → Success
- [ ] Upload valid text file → Success
- [ ] Try to upload invalid file type → Error message
- [ ] Try to  upload file > 10MB → Error message
- [ ] Upload with notes → Notes saved in audit log
- [ ] Skip upload (cancel) → Returns to page
- [ ] Verify database records updated
- [ ] Verify audit trail created
- [ ] Verify file stored in correct directory

## Future Enhancements

1. **Server-side HR Payroll validation** in settlement_handler.php
2. **File preview** before upload
3. **Batch file uploads** for multiple settlements
4. **Permission levels** for accessing uploaded files
5. **Encryption** of sensitive WPS files
6. **Automatic processing** of WPS files
7. **File download** from settlement details page
8. **Integration** with payroll system API

## Files Involved

- `/includes/ajaxFile/settlement_handler.php` - Backend handler
- `/assets/js/settlement-wps-helper.js` - Frontend functions
- `/all_applied_vac.php` - Main settlement page
- `/uploads/wps_files/` - Upload directory
- `settlement_records` table - Database storage

## Troubleshooting

### WPS modal not appearing
- Check if user type is 'hr_payroll'
- Verify `settlement-wps-helper.js` is loaded
- Check browser console for errors

### Upload fails
- Verify file is correct format (xlsx, xls, csv, txt)
- Check file size < 10MB
- Ensure `/uploads/wps_files/` directory is writable (755 permissions)

### Database not updating
- Check `settlement_records` table exists and has WPS columns
- Verify PDO connection is working
- Check database user has UPDATE permissions

### Files not saving
- Verify `/uploads/` directory exists and is writable
- Check PHP `php.ini` file upload limits
- Review PHP error logs

## Support & Documentation

For questions about implementation or integration:
1. Check this document
2. Review code comments in settlement_handler.php and settlement-wps-helper.js
3. Check database schema with `DESCRIBE settlement_records`
4. Review smt_request_status table for audit trail
