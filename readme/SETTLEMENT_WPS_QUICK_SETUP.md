# Settlement WPS File Upload - Quick Setup Checklist

## ✅ Implementation Status

### Database Updates
- [x] Migration script created: `migrations/add_wps_file_to_settlements.php`
- [ ] Run migration to add WPS columns to `settlement_records` table

### Frontend Updates
- [x] **File:** `all_applied_vac.php`
  - [x] Added `showSettlementApprovalForHRPayroll()` function
  - [x] Added `uploadWPSFileToSettlement()` function
  - [x] Added WPS file upload modal with validation

### Backend Updates
- [x] **File:** `includes/ajaxFile/settlement_handler.php`
  - [x] Added `uploadWPSFile()` function
  - [x] Added case for 'upload_wps_file' action
  - [x] Added file validation and storage logic

- [x] **File:** `includes/SettlementManager_Corrected.php`
  - [x] Added `checkWPSFileRequirement()` method
  - [x] Added `getWPSFileInfo()` method

### Documentation
- [x] Complete implementation guide created
- [x] API documentation added
- [x] Troubleshooting guide included
- [x] Database schema documented

---

## 🚀 Step-by-Step Setup

### Step 1: Run Database Migration
```bash
# Option A: CLI
cd /d D:\xampp\htdocs\almutlak\system
php migrations/add_wps_file_to_settlements.php

# Option B: MySQL CLI
mysql -u username -p database < migrations/add_wps_file_to_settlements.php

# Option C: PHPMyAdmin
1. Open PHPMyAdmin
2. Select your database
3. Copy SQL from Step 4 in this file
4. Click SQL tab and paste/execute
```

### Step 2: Create Upload Directory
```bash
# Create the uploads directory
mkdir -p system/uploads/wps_files

# Set proper permissions
chmod 755 system/uploads/wps_files
```

### Step 3: Verify File Changes
```bash
# Check that all files are updated:
ls -la includes/ajaxFile/settlement_handler.php
ls -la includes/SettlementManager_Corrected.php
ls -la all_applied_vac.php
```

### Step 4: Test the Feature
1. Log in as HR Payroll user
2. Navigate to a settlement in pending approval status
3. Click settlement approval action
4. Verify WPS file upload modal appears
5. Try uploading a test file (XLSX, CSV, or TXT)

### Step 5: Verify Database
```sql
-- Check that new columns exist
DESCRIBE settlement_records;

-- Should see these new columns:
-- - wps_file_path
-- - wps_file_name
-- - wps_uploaded_by
-- - wps_uploaded_at
-- - wps_upload_status
```

---

## 📋 Database Migration SQL

Run this if you prefer manual migration:

```sql
-- Add WPS file columns to settlement_records
ALTER TABLE `settlement_records` 
ADD COLUMN `wps_file_path` VARCHAR(255) NULL DEFAULT NULL AFTER `settlement_amount`,
ADD COLUMN `wps_file_name` VARCHAR(255) NULL DEFAULT NULL,
ADD COLUMN `wps_uploaded_by` INT NULL DEFAULT NULL,
ADD COLUMN `wps_uploaded_at` TIMESTAMP NULL DEFAULT NULL,
ADD COLUMN `wps_upload_status` ENUM('pending', 'uploaded', 'approved') DEFAULT 'pending',
ADD INDEX `idx_wps_upload_status` (`wps_upload_status`);

-- Verify migration
DESCRIBE settlement_records;
```

---

## 🔍 Verification Checklist

After setup, verify:

- [ ] New columns added to `settlement_records` table
- [ ] `/uploads/wps_files/` directory exists and is writable
- [ ] HR Payroll user can access settlement approval page
- [ ] WPS file upload modal appears when HR Payroll approves settlement
- [ ] File upload works (test with Excel file)
- [ ] File is stored in `/uploads/wps_files/YYYY/MM/` directory
- [ ] Settlement record updated with file information
- [ ] Status record added to `smt_request_status` table
- [ ] File path saved in database

```bash
# Quick verification commands:
# Check directory exists and is writable
ls -la system/uploads/wps_files/

# Check database columns
mysql -u root -p almutlak_hr_db -e "DESCRIBE settlement_records;"

# Check sample data
mysql -u root -p almutlak_hr_db -e "SELECT wps_file_name, wps_upload_status FROM settlement_records LIMIT 5;"
```

---

## 📞 Support Files

### Documentation
- **Full Guide:** `readme/SETTLEMENT_WPS_FILE_FEATURE.md`
- **This File:** `readme/SETTLEMENT_WPS_QUICK_SETUP.md`

### Functions Reference

**Frontend Functions:**
- `showSettlementApprovalForHRPayroll()` - Show WPS upload modal
- `uploadWPSFileToSettlement()` - Handle file upload

**Backend Functions:**
- `uploadWPSFile()` in `settlement_handler.php`
- `checkWPSFileRequirement()` in `SettlementManager_Corrected.php`
- `getWPSFileInfo()` in `SettlementManager_Corrected.php`

---

## 🛠️ Troubleshooting

### Issue: Migration fails
**Check:**
- [ ] Database user has ALTER/CREATE permissions
- [ ] No duplicate column error (columns might already exist)
- [ ] Correct database name in connection

### Issue: File upload fails
**Check:**
- [ ] `/uploads/wps_files/` directory writable
- [ ] PHP `upload_max_filesize` >= 10MB
- [ ] File format is allowed (.xlsx, .xls, .csv, .txt)
- [ ] File size < 10MB

### Issue: Modal doesn't appear
**Check:**
- [ ] User type is 'hr_payroll'
- [ ] Settlement status is pending approval
- [ ] JavaScript console for errors (F12 Dev Tools)

### Issue: Files not saving to database
**Check:**
- [ ] Database columns exist (run DESCRIBE query)
- [ ] settlement_records table has write permissions
- [ ] Check PHP error logs

---

## 📊 Usage Statistics Queries

```sql
-- Total WPS files uploaded
SELECT COUNT(*) as total_uploads FROM settlement_records WHERE wps_file_path IS NOT NULL;

-- Uploads by status
SELECT wps_upload_status, COUNT(*) as count FROM settlement_records GROUP BY wps_upload_status;

-- Uploads by user
SELECT 
    e.name,
    COUNT(*) as upload_count,
    MAX(sr.wps_uploaded_at) as last_upload
FROM settlement_records sr
JOIN employees e ON e.emp_id = sr.wps_uploaded_by
GROUP BY sr.wps_uploaded_by
ORDER BY upload_count DESC;

-- Settlements awaiting WPS upload
SELECT 
    sr.request_inv_no,
    sr.settlement_amount,
    e.name as employee_name
FROM settlement_records sr
JOIN employees e ON e.emp_id = sr.emp_id
WHERE sr.wps_upload_status = 'pending'
ORDER BY sr.created_at DESC;
```

---

## ⚙️ Configuration

### PHP Settings (php.ini)
Ensure these are set for large file uploads:
```ini
upload_max_filesize = 20M
post_max_size = 25M
max_execution_time = 300
```

### Approval Chain Configuration
Add HR Payroll to your settlement approval chain in `app_settings`:
```json
{
    "setting_name": "approval_chain_settlement",
    "setting_value": "[{\"level\":1,\"user_type\":\"dept_manager\"},{\"level\":2,\"user_type\":\"hr_senior_bp\"},{\"level\":3,\"user_type\":\"hr_payroll\"},{\"level\":4,\"user_type\":\"finance\"}]"
}
```

---

## 📝 Implementation Notes

- WPS file upload is optional in the approval flow
- Files are stored with unique names: `WPS_{RequestNo}_{Timestamp}_{Random}.ext`
- File paths are relative to system root for portability
- Status tracking in `smt_request_status` for audit trail
- Can add more file types by updating allowedExtensions array
- Can increase file size limit (currently 10MB)

---

**All changes are ready to deploy!**

For detailed information, see: `readme/SETTLEMENT_WPS_FILE_FEATURE.md`
