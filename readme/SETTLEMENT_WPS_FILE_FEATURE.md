# Settlement WPS File Upload Feature - Implementation Guide

## Overview
This feature adds WPS (Payroll System) file upload capability to the settlement approval workflow. When HR Payroll approves a settlement, they can upload the corresponding WPS file that contains payroll information.

## Updated Components

### 1. Database Changes

#### Table: `settlement_records`
Added 5 new columns to store WPS file information:

```sql
-- Run this migration to add columns to your settlement_records table:

ALTER TABLE `settlement_records` ADD COLUMN `wps_file_path` VARCHAR(255) NULL DEFAULT NULL AFTER `settlement_amount`;
ALTER TABLE `settlement_records` ADD COLUMN `wps_file_name` VARCHAR(255) NULL DEFAULT NULL AFTER `wps_file_path`;
ALTER TABLE `settlement_records` ADD COLUMN `wps_uploaded_by` INT NULL DEFAULT NULL AFTER `wps_file_name`;
ALTER TABLE `settlement_records` ADD COLUMN `wps_uploaded_at` TIMESTAMP NULL DEFAULT NULL AFTER `wps_uploaded_by`;
ALTER TABLE `settlement_records` ADD COLUMN `wps_upload_status` ENUM('pending', 'uploaded', 'approved') DEFAULT 'pending' AFTER `wps_uploaded_at`;

-- Add index for faster lookups
ALTER TABLE `settlement_records` ADD INDEX `idx_wps_upload_status` (`wps_upload_status`);
```

**Column Descriptions:**
- `wps_file_path` (VARCHAR 255): Relative path to the uploaded WPS file
- `wps_file_name` (VARCHAR 255): Original filename of the uploaded WPS file
- `wps_uploaded_by` (INT): Employee ID of the HR Payroll user who uploaded the file
- `wps_uploaded_at` (TIMESTAMP): Timestamp when the file was uploaded
- `wps_upload_status` (ENUM): Status of the WPS upload: 'pending', 'uploaded', 'approved'

#### Running the Migration

**Option 1: CLI Command**
```bash
php migrations/add_wps_file_to_settlements.php
```

**Option 2: Direct SQL**
Copy and paste the SQL queries above in your MySQL client interface.

**Option 3: PHPMyAdmin**
1. Go to your database in PHPMyAdmin
2. Select the `settlement_records` table
3. Click "SQL" tab
4. Paste the migration SQL and execute

---

### 2. Frontend Changes

#### File: `all_applied_vac.php`

**New Functions Added:**

##### `showSettlementApprovalForHRPayroll()`
Shows the settlement approval modal with WPS file upload option when HR Payroll views a settlement.

**Parameters:**
- `settlementId` (int): Settlement record ID
- `requestInvNo` (string): Settlement invoice number (e.g., "SETL-VAC-2024-001")
- `employeeId` (int): Employee ID
- `employeeName` (string): Employee full name
- `totalPayable` (float): Total payable amount
- `approvalChainText` (string): Approval chain description

**Usage:**
```javascript
showSettlementApprovalForHRPayroll(
    settlementId,
    'SETL-VAC-2024-001',
    12345,
    'Ahmed Muhammad',
    5000.00,
    'Department Manager → HR → Finance'
);
```

##### `uploadWPSFileToSettlement()`
Handles the WPS file upload process. Validates file type and size before upload.

**Features:**
- File type validation (Excel, CSV, Text only)
- File size limit: 10MB
- Progress indicator during upload
- Error handling and user feedback

**WPS File Upload Modal:**
```
Title: "HR Payroll Settlement Processing"

Fields:
├── Employee: [Employee Name]
├── Settlement ID: [Request Invoice Number]
├── Total Payable: [Amount in SAR]
├── WPS File Input: [File selection with format restrictions]
└── Upload Notes: [Optional textarea for notes]

Accepted File Formats:
├── Excel (.xlsx, .xls)
├── CSV (.csv)
└── Text (.txt)

File Size Limit: 10MB
```

---

### 3. Backend Changes

#### File: `includes/ajaxFile/settlement_handler.php`

**New Function Added:**

##### `uploadWPSFile($currentUserId)`
Processes WPS file uploads from HR Payroll.

**Flow:**
1. Validates input parameters (settlementId, requestInvNo, empId)
2. Checks settlement exists in database
3. Validates uploaded file (type, size)
4. Creates upload directory with date structure
5. Moves file to secure location
6. Updates `settlement_records` with file information
7. Records status in `smt_request_status` table
8. Returns success/error response

**File Storage Structure:**
```
/system/uploads/wps_files/
├── 2024/
│   ├── 01/
│   │   └── WPS_SETL-VAC-2024-001_1704067200_abc123.xlsx
│   ├── 02/
│   │   └── WPS_SETL-VAC-2024-002_1706831600_def456.csv
│   └── ...
└── ...
```

**Request Parameters:**
```php
[
    'action' => 'upload_wps_file',
    'settlement_id' => 1,
    'request_inv_no' => 'SETL-VAC-2024-001',
    'emp_id' => 123,
    'wps_file' => [Binary file data],
    'wps_note' => 'Optional notes about this upload',
    'user_id' => 456  // Current user ID
]
```

**Response Format:**
```json
{
    "success": true,
    "message": "WPS file uploaded successfully",
    "file_name": "payroll_2024_01.xlsx",
    "settlement_inv_no": "SETL-VAC-2024-001",
    "upload_time": "2024-01-02 10:30:45"
}
```

#### File: `includes/SettlementManager_Corrected.php`

**New Methods Added:**

##### `checkWPSFileRequirement($settlementInvNo)`
Checks if a settlement needs WPS file upload and returns upload status.

**Return:**
```php
[
    'needs_wps' => bool,  // True if HR Payroll is approver and file not uploaded
    'is_hr_payroll_approver' => bool,  // True if HR Payroll is in approval chain
    'file_uploaded' => bool  // True if file already uploaded
]
```

**Usage:**
```php
$wpsInfo = $settlementManager->checkWPSFileRequirement('SETL-VAC-2024-001');

if ($wpsInfo['needs_wps']) {
    // Show WPS file upload button/modal
}
```

##### `getWPSFileInfo($settlementInvNo)`
Retrieves WPS file information for a settlement.

**Return:**
```php
[
    'wps_file_path' => 'uploads/wps_files/2024/01/WPS_...xlsx',
    'wps_file_name' => 'original_filename.xlsx',
    'wps_uploaded_by' => 123,
    'wps_uploaded_at' => '2024-01-02 10:30:45',
    'wps_upload_status' => 'uploaded',
    'uploaded_by_name' => 'Ahmed Muhammad'
]
```

---

## Workflow / User Experience

### Settlement Approval Flow with WPS File

```
1. HR/Admin creates settlement
   └─> Settlement enters approval chain (pending_approval status)

2. First approver (e.g., Dept Manager) approves/rejects
   └─> Settlement moves to next level or stays pending

3. HR Payroll receives settlement for final approval
   └─> showSettlementApprovalForHRPayroll() is triggered
   └─> Modal displays with WPS file upload option

4. HR Payroll:
   a) Selects WPS file from computer
   b) (Optional) Adds notes about the file
   c) Clicks "Upload WPS File"
   
5. uploadWPSFileToSettlement() processes:
   a) Validates file (type, size)
   b) Uploads to /uploads/wps_files/YYYY/MM/
   c) Saves file info to settlement_records
   d) Creates status record in smt_request_status
   
6. Settlement marked as:
   - wps_upload_status = 'uploaded'
   - Can now be approved/processed by Finance
   
7. Finance can then:
   - Download/view the uploaded WPS file
   - Process settlement payment
```

---

## Integration with Existing Settlement Workflow

### When to Show WPS Upload

The WPS file upload modal should be shown when:
1. Current user is HR Payroll (`user_type = 'hr_payroll'`)
2. Settlement status is pending at HR Payroll approval level
3. WPS file has not yet been uploaded (`wps_upload_status = 'pending'`)

### Implementation in Frontend

In the settlement approval action buttons in `all_settlements.php` or settlement detail pages, add:

```javascript
// Check if current user is HR Payroll and settlement needs WPS
const currentUserType = document.body.getAttribute('data-user-type');
if (currentUserType === 'hr_payroll') {
    // Check WPS requirement via AJAX
    $.ajax({
        url: './includes/ajaxFile/settlement_handler.php',
        type: 'POST',
        data: {
            action: 'check_wps_requirement',
            settlement_inv_no: requestInvNo
        },
        success: function(response) {
            if (response.needs_wps) {
                // Show WPS upload modal instead of regular approval
                showSettlementApprovalForHRPayroll(
                    settlementId,
                    requestInvNo,
                    empId,
                    employeeName,
                    totalPayable,
                    chainText
                );
            } else {
                // Show regular approval modal
                approveSettlement(settlementId, ...);
            }
        }
    });
}
```

---

## File Upload Security

### Implemented Security Measures:

1. **File Type Validation**
   - Whitelist: .xlsx, .xls, .csv, .txt
   - Checked on both frontend and backend

2. **File Size Limit**
   - Maximum 10MB per file
   - Enforced on both frontend and backend

3. **Directory Structure**
   - Files stored outside web root when possible
   - Organized by date (YYYY/MM) for easy management
   - Unique filename generation: `WPS_{RequestNo}_{Timestamp}_{Random}.ext`

4. **Access Control**
   - Only authenticated users can upload
   - Only HR Payroll role can upload WPS files
   - File path stored in database (not exposed directly)

5. **Database Tracking**
   - Employee ID recorded (`wps_uploaded_by`)
   - Timestamp recorded (`wps_uploaded_at`)
   - Status tracked (`wps_upload_status`)
   - Logged in `smt_request_status` table

6. **Error Handling**
   - File upload errors caught and reported
   - Database failures handled gracefully
   - Uploaded files cleaned up on error

---

## Troubleshooting

### Common Issues

#### Issue: "File upload error"
**Solution:**
- Check that `/uploads/wps_files/` directory exists
- Verify directory permissions (755 or writable)
- Check PHP `upload_max_filesize` setting in php.ini

#### Issue: "Invalid file type"
**Solution:**
- Ensure file has correct extension (.xlsx, .xls, .csv, or .txt)
- Check that MIME type matches the extension

#### Issue: "File size exceeds limit"
**Solution:**
- Compress the Excel file before uploading
- Split into multiple files if needed
- Contact admin if you need a larger file size limit

#### Issue: Settlement records table doesn't have new columns
**Solution:**
- Run the migration: `php migrations/add_wps_file_to_settlements.php`
- Or manually execute the SQL queries provided above

---

## Database Queries for Management

### View WPS Uploads
```sql
SELECT 
    sr.request_inv_no,
    sr.settlement_amount,
    sr.wps_file_name,
    sr.wps_uploaded_at,
    e.name as uploaded_by_name,
    sr.wps_upload_status
FROM settlement_records sr
LEFT JOIN employees e ON e.emp_id = sr.wps_uploaded_by
WHERE sr.wps_file_path IS NOT NULL
ORDER BY sr.wps_uploaded_at DESC;
```

### Find Settlements Pending WPS Upload
```sql
SELECT 
    sr.id,
    sr.request_inv_no,
    sr.settlement_amount,
    e.name as employee_name
FROM settlement_records sr
JOIN employees e ON e.emp_id = sr.emp_id
WHERE sr.wps_upload_status = 'pending'
  AND EXISTS (
      SELECT 1 FROM request_approvers ra
      JOIN admin_login al ON ra.approver_id = al.emp_id
      WHERE ra.request_inv_no = sr.request_inv_no
        AND al.user_type = 'hr_payroll'
        AND ra.status = 'pending'
  )
ORDER BY sr.created_at DESC;
```

### Download WPS File Count by Month
```sql
SELECT 
    DATE_FORMAT(sr.wps_uploaded_at, '%Y-%m') as month,
    COUNT(*) as total_uploads,
    COUNT(DISTINCT sr.emp_id) as unique_employees
FROM settlement_records sr
WHERE sr.wps_file_path IS NOT NULL
GROUP BY DATE_FORMAT(sr.wps_uploaded_at, '%Y-%m')
ORDER BY month DESC;
```

---

## API Endpoints

### Upload WPS File
**Endpoint:** `includes/ajaxFile/settlement_handler.php`

**Method:** POST (Multipart Form Data)

**Parameters:**
```
action: 'upload_wps_file'
settlement_id: [int]
request_inv_no: [string]
emp_id: [int]
wps_file: [File]
wps_note: [string, optional]
user_id: [int]
```

**Success Response:**
```json
{
    "success": true,
    "message": "WPS file uploaded successfully",
    "file_name": "payroll_2024.xlsx",
    "settlement_inv_no": "SETL-VAC-2024-001",
    "upload_time": "2024-01-02 10:30:45"
}
```

**Error Response:**
```json
{
    "success": false,
    "message": "Error description"
}
```

---

## Configuration Reference

### `approval_chain_settlement` in app_settings
The WPS file upload feature works with the configured approval chain. Ensure HR Payroll is included as an approver in your settlement approval chain configuration:

```json
[
    {"level": 1, "user_type": "dept_manager"},
    {"level": 2, "user_type": "hr_senior_bp"},
    {"level": 3, "user_type": "hr_payroll"},
    {"level": 4, "user_type": "finance"}
]
```

---

## Version Information

- **Feature:** Settlement WPS File Upload
- **Added:** February 2026
- **Database Version:** Compatible with existing settlement_records structure
- **Dependencies:** 
  - ApprovalChainManager
  - SettlementManager_Corrected
  - jQuery for AJAX
  - SweetAlert2 for UI modals

## Support

For issues or questions about this feature, contact your system administrator or development team.
