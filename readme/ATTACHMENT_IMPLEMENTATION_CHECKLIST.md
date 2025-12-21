# Delivery Attachment Implementation Checklist

## 📋 Implementation Steps

### Step 1: Database Setup
- [ ] Run migration to add `attachment_filename` column
  ```sql
  ALTER TABLE `general_request_deliveries` 
  ADD COLUMN `attachment_filename` VARCHAR(255) NULL AFTER `delivery_date`;
  ```
- [ ] Verify column was added:
  ```sql
  DESCRIBE general_request_deliveries;
  ```

### Step 2: Create Storage Directory
- [ ] Create directory: `/assets/delivery_attachments/`
  ```bash
  mkdir -p /xampp/htdocs/almutlak/system/assets/delivery_attachments
  ```
- [ ] Set permissions:
  ```bash
  chmod 755 /xampp/htdocs/almutlak/system/assets/delivery_attachments
  ```

### Step 3: Deploy Code
- [ ] Update `includes/ajaxFile/ajaxGeneralRequest.php` (DONE ✓)
- [ ] Update `view_general_request.php` (DONE ✓)
  - Modal form with file upload
  - Remove button for attachment
  - File validation

### Step 4: Test Upload
- [ ] Create a general request
- [ ] Approve it
- [ ] Click "Deliver Items"
- [ ] Test file upload with valid file
- [ ] Test file removal
- [ ] Submit delivery
- [ ] Check database for filename
- [ ] Verify file exists in `/assets/delivery_attachments/`

### Step 5: Verify Everything Works
- [ ] File uploaded successfully
- [ ] Database saved filename
- [ ] No errors in browser console
- [ ] No PHP warnings in error log

---

## 🔧 Database Migration Command

```sql
-- Run this in MySQL to add attachment support
ALTER TABLE `general_request_deliveries` 
ADD COLUMN `attachment_filename` VARCHAR(255) NULL AFTER `delivery_date`;
```

## 📂 Directory Structure Required

```
/assets/
  └── delivery_attachments/    ← Create this directory
      ├── (empty initially)
      ├── SR-2025-001_20250131120530_5dfb4c7ef8901.pdf
      ├── SR-2025-002_20250131150215_5dfb4d9a2c3f1.jpg
      └── ... (files will be stored here)
```

## 📊 File Storage Info

| Item | Value |
|------|-------|
| **Storage Location** | `/assets/delivery_attachments/` |
| **Filename Format** | `{request_id}_{timestamp}_{uniqueid}.{extension}` |
| **Max File Size** | 5 MB |
| **Allowed Types** | PDF, JPG, PNG, DOC, DOCX, XLSX, ZIP |
| **Database Field** | `general_request_deliveries.attachment_filename` |

## ✅ Verification

### Test Case 1: File Upload Success
```
1. Go to approved request
2. Click "Deliver Items"
3. Select employee
4. Choose item status
5. Select file (PDF, JPG, etc.)
6. Click "Submit Delivery"

Expected: 
- ✓ File uploaded
- ✓ Filename saved in database
- ✓ File exists in /assets/delivery_attachments/
- ✓ Page reloads successfully
- ✓ No errors in console
```

### Test Case 2: File Removal
```
1. Select file
2. Click red X button

Expected:
- ✓ File cleared
- ✓ Display area empty
- ✓ Can select another file
```

### Test Case 3: File Size Validation
```
1. Try to upload file > 5MB

Expected:
- ✓ Error message: "File too large (Max: 5MB)"
- ✓ File not uploaded
- ✓ Input cleared
```

### Test Case 4: File Type Validation
```
1. Try to upload .exe file

Expected:
- ✓ Error message: "Invalid file type"
- ✓ File not uploaded
- ✓ Input cleared
```

### Test Case 5: Optional Attachment
```
1. Create delivery WITHOUT selecting file

Expected:
- ✓ Delivery submitted successfully
- ✓ Database field is NULL
- ✓ No file in /assets/delivery_attachments/
```

## 🐛 Troubleshooting

### Issue: Upload fails
**Check:**
- [ ] Directory exists: `/assets/delivery_attachments/`
- [ ] Directory is writable: `chmod 755`
- [ ] Database column exists
- [ ] File size < 5MB
- [ ] File type is allowed
- [ ] PHP error logs

**Fix:**
```bash
# Create directory
mkdir -p /xampp/htdocs/almutlak/system/assets/delivery_attachments

# Set permissions
chmod 755 /xampp/htdocs/almutlak/system/assets/delivery_attachments

# Verify
ls -la /xampp/htdocs/almutlak/system/assets/
```

### Issue: Database field empty
**Check:**
- [ ] Migration was run
- [ ] Column name is correct: `attachment_filename`
- [ ] Column is in right table: `general_request_deliveries`

**Verify:**
```sql
DESCRIBE general_request_deliveries;
-- Look for: attachment_filename VARCHAR(255)
```

### Issue: File not found after upload
**Check:**
- [ ] File in correct directory
- [ ] Filename matches database entry
- [ ] File permissions (644)

**Verify:**
```bash
# List files
ls -la /xampp/htdocs/almutlak/system/assets/delivery_attachments/

# Check permissions
file /xampp/htdocs/almutlak/system/assets/delivery_attachments/SR-2025-001*

# Fix permissions if needed
chmod 644 /xampp/htdocs/almutlak/system/assets/delivery_attachments/*
```

## 📚 Related Documentation

- **Main Guide**: DELIVERY_ATTACHMENT_STORAGE.md
- **Implementation**: DELIVERY_MODAL_GUIDE.md
- **Frontend Code**: view_general_request.php (lines 1660-1695)
- **Backend Code**: ajaxGeneralRequest.php (lines 322-457)

## 🚀 Deployment Commands

### One-Time Setup
```bash
# Create storage directory
mkdir -p /xampp/htdocs/almutlak/system/assets/delivery_attachments
chmod 755 /xampp/htdocs/almutlak/system/assets/delivery_attachments

# Run database migration
mysql -u root almutlak << 'EOF'
ALTER TABLE general_request_deliveries 
ADD COLUMN attachment_filename VARCHAR(255) NULL AFTER delivery_date;
EOF

# Verify
mysql -u root almutlak -e "DESCRIBE general_request_deliveries;" | grep attachment
```

### Code Deployment
1. Deploy `view_general_request.php`
2. Deploy `includes/ajaxFile/ajaxGeneralRequest.php`
3. Test on staging first
4. Deploy to production

## ✨ Features Implemented

- [x] File upload with drag & drop
- [x] File size validation (5MB max)
- [x] File type validation (PDF, JPG, PNG, DOC, DOCX, XLSX, ZIP)
- [x] Remove button to clear selection
- [x] Database storage of filename
- [x] Server-side file storage in `/assets/delivery_attachments/`
- [x] Error handling with transaction rollback
- [x] Unique filename generation (prevents overwrites)
- [x] File cleanup on failed transactions

## 📝 Notes

- Attachments are optional (can submit delivery without file)
- Only one file per delivery (can be enhanced to multiple)
- Files stored with unique name format to prevent conflicts
- On submission failure, uploaded file is automatically deleted
- Database transaction ensures consistency

---

**Status**: ✅ Ready to Deploy
**Tested**: ✅ Backend code verified for syntax
**Documentation**: ✅ Complete
