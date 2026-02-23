# Employee Note Enhancement - Attachment & Type Support

## Overview
Enhanced the employee note feature to support optional file attachments and categorization by note type.

## Changes Made

### 1. Database Changes
**File**: `add_note_attachment_and_type.sql`
- Added `note_type` column (VARCHAR 100) to `emp_notice` table
- Added `attachment` column (VARCHAR 255) to `emp_notice` table
- Added index on `note_type` for better query performance

**To apply migration**:
```sql
source add_note_attachment_and_type.sql;
-- OR
mysql -u root -p almutlak_db < add_note_attachment_and_type.sql
```

### 2. Frontend Changes
**File**: `assets/js/jquery.app.js?t=<?= time() ?>`

#### Updated `add_note_HTML()` function (lines ~6683-6729)
- Changed from single text input to comprehensive form
- Added note type dropdown with 14 common categories:
  - Warning
  - Sick Leave
  - Appreciation
  - Violation
  - Absence
  - Late Arrival
  - Performance Review
  - Training
  - Promotion
  - Salary Adjustment
  - Disciplinary Action
  - Medical Report
  - General
  - Other
- Changed note field from input to textarea for better content entry
- Added optional file upload field
- Added file format guidance (PDF, DOC, DOCX, JPG, PNG)

#### Updated `add_noties()` function (lines ~5258-5339)
- Replaced simple AJAX POST with FormData for file upload support
- Added validation for:
  - Required note type selection
  - Required note text
  - File size limit (5MB max)
  - File type validation (PDF, DOC, DOCX, JPG, PNG only)
- Added `processData: false` and `contentType: false` for file upload
- Improved error messages

### 3. Backend Changes
**File**: `includes/ajaxFile/ajaxEmployee.php`

#### Updated `add_note` handler (lines ~1169-1242)
- Added file upload processing
- Created upload directory: `assets/emp_notes/`
- Implemented file validation:
  - Type checking (PDF, DOC, DOCX, JPG, PNG)
  - Size limit (5MB)
- Generated unique filenames: `note_{empid}_{timestamp}_{uniqid}.{ext}`
- Added error handling with try-catch
- Updated database INSERT to include `note_type` and `attachment` columns
- Enhanced success/error messages

### 4. File Storage
**Directory**: `assets/emp_notes/`
- Auto-created with permissions 0755
- Stores all employee note attachments
- Filename format: `note_{emp_id}_{timestamp}_{uniqueid}.{extension}`

## Features

### Note Types
The system supports categorizing notes into the following types:
1. **Warning** - Employee warnings and cautions
2. **Sick Leave** - Medical leave documentation
3. **Appreciation** - Recognition and commendations
4. **Violation** - Policy or rule violations
5. **Absence** - Absence records
6. **Late Arrival** - Tardiness documentation
7. **Performance Review** - Performance evaluations
8. **Training** - Training completion certificates
9. **Promotion** - Promotion announcements
10. **Salary Adjustment** - Salary change notifications
11. **Disciplinary Action** - Formal disciplinary measures
12. **Medical Report** - Medical reports and certificates
13. **General** - General notes
14. **Other** - Miscellaneous notes

### File Upload Specifications
- **Supported Formats**: PDF, DOC, DOCX, JPG, JPEG, PNG
- **Maximum Size**: 5MB
- **Upload Status**: Optional (not required)
- **Storage Location**: `assets/emp_notes/`
- **Filename Pattern**: `note_{empid}_{timestamp}_{uniqid}.{extension}`

### Validation Rules
1. Note type is **required**
2. Note text is **required**
3. Attachment is **optional**
4. If attachment provided:
   - Must be valid file type
   - Must be under 5MB
   - Must pass server-side validation

## Usage

### Adding a Note with Attachment
1. Click "Add Note" button on employee profile
2. Select note type from dropdown (required)
3. Enter note details in textarea (required)
4. Optionally upload a file (PDF, DOC, DOCX, JPG, PNG)
5. Click "Register" to submit

### Backend Processing
```php
// Note type stored in database
$noteType = $_POST['note_type']; // e.g., 'warning', 'sick_leave'

// File uploaded and stored
$attachmentPath = 'assets/emp_notes/note_5456_1731398472_abc123.pdf';

// Database record
INSERT INTO emp_notice (emp_id, note, note_type, attachment, created_at)
VALUES (5456, 'Employee warned for...', 'warning', 'assets/emp_notes/...', NOW());
```

## Testing Checklist

- [ ] Run database migration
- [ ] Test adding note with all note types
- [ ] Test adding note without attachment (optional field)
- [ ] Test adding note with PDF attachment
- [ ] Test adding note with DOC/DOCX attachment
- [ ] Test adding note with JPG/PNG attachment
- [ ] Test file size validation (>5MB should fail)
- [ ] Test invalid file type (e.g., .exe should fail)
- [ ] Test empty note type validation
- [ ] Test empty note text validation
- [ ] Verify files stored in `assets/emp_notes/` directory
- [ ] Verify database records include note_type and attachment path
- [ ] Test view notes functionality still works

## Database Schema

### Before
```sql
CREATE TABLE `emp_notice` (
  `id` int(11) NOT NULL,
  `emp_id` int(11) NOT NULL,
  `note` varchar(255) NOT NULL,
  `status` int(11) NOT NULL DEFAULT 1,
  `is_deleted` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
);
```

### After
```sql
CREATE TABLE `emp_notice` (
  `id` int(11) NOT NULL,
  `emp_id` int(11) NOT NULL,
  `note` varchar(255) NOT NULL,
  `note_type` varchar(100) DEFAULT NULL COMMENT 'Type: warning, sick_leave, etc.',
  `attachment` varchar(255) DEFAULT NULL COMMENT 'File path for attached document',
  `status` int(11) NOT NULL DEFAULT 1,
  `is_deleted` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  KEY `idx_note_type` (`note_type`)
);
```

## Security Considerations

1. **File Type Validation**: Both client-side and server-side checks
2. **File Size Limit**: 5MB maximum to prevent abuse
3. **Unique Filenames**: Prevents file overwrites and conflicts
4. **Directory Permissions**: 0755 for proper security
5. **Error Handling**: Try-catch blocks prevent information leakage
6. **SQL Injection**: PDO prepared statements used throughout

## Future Enhancements

Potential improvements for future versions:
- [ ] Add ability to view/download attachments in note list
- [ ] Add ability to delete/update notes with attachments
- [ ] Add thumbnail preview for image attachments
- [ ] Add note type filtering in view notes
- [ ] Add bulk note operations
- [ ] Add note export functionality
- [ ] Add email notifications for certain note types
- [ ] Add note approval workflow for sensitive types
- [ ] Add audit trail for note modifications

## Troubleshooting

### File Upload Fails
- Check `assets/emp_notes/` directory exists and is writable (chmod 755)
- Verify PHP upload settings: `upload_max_filesize` and `post_max_size` in php.ini
- Check Apache/PHP error logs

### Database Error
- Ensure migration script has been run
- Verify `note_type` and `attachment` columns exist in `emp_notice` table
- Check database user permissions

### Validation Errors
- Ensure file is under 5MB
- Ensure file type is PDF, DOC, DOCX, JPG, or PNG
- Ensure note type is selected
- Ensure note text is not empty

## Support
For issues or questions, contact the development team or check the error logs:
- Apache: `logs/error.log`
- PHP: Check `error_log` settings in php.ini
