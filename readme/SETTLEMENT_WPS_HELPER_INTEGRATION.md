# Settlement WPS Helper Functions - Integration Guide

This guide explains how to integrate the `settlement-wps-helper.js` functions into other pages that need WPS file upload functionality for settlements.

## Overview

The settlement WPS helper functions have been moved from inline JavaScript in `all_applied_vac.php` to a reusable external file at:
```
assets/js/settlement-wps-helper.js
```

## Available Functions

### 1. `showSettlementApprovalForHRPayroll()`
Shows a SweetAlert modal for HR Payroll users to upload WPS files during settlement approval.

**Parameters:**
```javascript
showSettlementApprovalForHRPayroll(
    settlementId,      // (int) Settlement record ID
    requestInvNo,      // (string) Settlement invoice number (e.g., "SETL-VAC-2024-001")
    employeeId,        // (int) Employee ID
    employeeName,      // (string) Employee full name
    totalPayable,      // (float) Total payable amount
    approvalChainText  // (string) Approval chain description
);
```

**Example Usage:**
```javascript
showSettlementApprovalForHRPayroll(
    123,
    'SETL-VAC-2024-001',
    456,
    'Ahmed Mohammed',
    5000.50,
    'Manager -> Finance -> HR Payroll'
);
```

### 2. `uploadWPSFileToSettlement()`
Handles the AJAX upload of WPS files to the settlement_handler.php backend.

**Parameters:**
```javascript
uploadWPSFileToSettlement(
    settlementId,      // (int) Settlement record ID
    requestInvNo,      // (string) Settlement invoice number
    employeeId,        // (int) Employee ID
    file,              // (File) File object from input
    note               // (string) Optional upload notes
);
```

## Integration Steps

### Step 1: Include Required Scripts

Add the helper script to your page's `</footer>` or bottom script section:

```html
<!-- Settlement WPS Helper Functions -->
<script src="assets/js/settlement-wps-helper.js"></script>

<!-- Settlement WPS Configuration -->
<script>
    window.settlementWPSConfig = {
        empId: <?= (int)$empid; ?>,
        userType: '<?php echo $_SESSION['user_type'] ?? ""; ?>'
    };
</script>
```

**Important:** 
- Include jQuery and SweetAlert2 before this script (via footer.php)
- The configuration must be set AFTER loading the helper script
- Ensure `$empid` and `$_SESSION['user_type']` are available from `session_check.php`

### Step 2: Call Function from Button or Link

Add a button/link that triggers the modal:

```html
<button class="btn btn-sm btn-primary" 
        onclick="showSettlementApprovalForHRPayroll(
            <?= $settlement['id'] ?>,
            '<?= $settlement['request_inv_no'] ?>',
            <?= $settlement['emp_id'] ?>,
            '<?= $settlement['employee_name'] ?>',
            <?= $settlement['total_payable'] ?>,
            'Manager -> Finance -> HR Payroll'
        )">
    <i class="fa fa-upload"></i> Upload WPS File
</button>
```

### Step 3: Backend Processing

The functions send data to `./includes/ajaxFile/settlement_handler.php` with action `upload_wps_file`.

**Backend handles:**
- File validation (type, size)
- Directory creation with date structure: `/uploads/wps_files/YYYY/MM/`
- Unique filename generation
- Database updates to settlement_records table
- Audit logging in smt_request_status table

## Configuration Object

The `window.settlementWPSConfig` object is required and contains:

```javascript
{
    empId: number,         // Current user's employee ID
    userType: string      // Current user's type (e.g., 'hr_payroll', 'manager')
}
```

**Validation:**
- Only users with `userType === 'hr_payroll'` can access the modal
- If not HR Payroll, a SweetAlert error message is shown

## File Requirements

**Accepted File Formats:**
- Excel: `.xlsx`, `.xls`
- CSV: `.csv`
- Text: `.txt`

**Constraints:**
- Maximum file size: **10 MB**
- File type validation on both frontend and backend

## Database Updates

When a WPS file is successfully uploaded, the following fields in `settlement_records` are updated:

```sql
UPDATE settlement_records SET
    wps_file_path = '/uploads/wps_files/2024/12/settlement_123_20241215_141530.xlsx',
    wps_file_name = 'settlement_123_20241215_141530.xlsx',
    wps_uploaded_by = 456,  -- User ID
    wps_uploaded_at = NOW(),
    wps_upload_status = 'completed'
WHERE id = 123;
```

## Error Handling

The functions include error handling for:
- Missing file selection
- Invalid file types
- File size over 10MB
- AJAX request failures
- Backend processing errors

All errors are displayed using SweetAlert2 with user-friendly messages.

## Example: Integration in all_settlements.php

```php
<?php
// ... Page setup code ...
require_once __DIR__ . '/includes/session_check.php';
require_once __DIR__ . '/includes/footer.php';
?>

<!-- In your settlement card or table -->
<button type="button" 
        class="btn btn-sm btn-info"
        onclick="showSettlementApprovalForHRPayroll(
            <?= $settlement['id'] ?>,
            '<?= htmlspecialchars($settlement['request_inv_no']) ?>',
            <?= (int)$settlement['emp_id'] ?>,
            '<?= htmlspecialchars($settlement['employee_name']) ?>',
            <?= (float)$settlement['total_payable'] ?>,
            'Approval Chain'
        )">
    <i class="fa fa-file-upload"></i> WPS Upload
</button>

<!-- Before closing body tag, ensure scripts are included: -->
<!-- Settlement WPS Helper Functions -->
<script src="assets/js/settlement-wps-helper.js"></script>

<!-- Settlement WPS Configuration -->
<script>
    window.settlementWPSConfig = {
        empId: <?= (int)$empid; ?>,
        userType: '<?php echo $_SESSION['user_type'] ?? ""; ?>'
    };
</script>
```

## Best Practices

1. **Authorization Check**: Always verify `user_type === 'hr_payroll'` on the backend (settlement_handler.php does this)
2. **File Validation**: The modal validates file type and size before upload
3. **User Feedback**: SweetAlert provides clear feedback on success/failure
4. **Auto-Reload**: Page reloads after successful upload to reflect changes
5. **Error Messages**: All errors are user-friendly and descriptive

## Troubleshooting

### Issue: Link/button not working
- Ensure jQuery and SweetAlert2 are loaded
- Verify `window.settlementWPSConfig` is set
- Check browser console for JavaScript errors

### Issue: File upload fails with "Invalid file format"
- Verify file extension is one of: xlsx, xls, csv, txt (case-insensitive)
- Check file size is under 10MB

### Issue: AJAX returns 401/403 error
- Ensure user is logged in and has valid session
- Verify user_type is 'hr_payroll'

## File Locations

- **Helper Script**: `assets/js/settlement-wps-helper.js`
- **Backend Handler**: `includes/ajaxFile/settlement_handler.php`
- **Upload Directory**: `uploads/wps_files/YYYY/MM/`
- **Database**: `settlement_records` table (columns: wps_file_path, wps_file_name, wps_uploaded_by, wps_uploaded_at, wps_upload_status)

## Support

For issues or questions about WPS file upload integration:
1. Check the console for JavaScript errors
2. Review backend logs in settlement_handler.php
3. Verify database schema has the required WPS columns
4. Ensure user has proper permissions (hr_payroll user type)
