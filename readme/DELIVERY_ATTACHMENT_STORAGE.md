# Delivery Attachment Storage - Complete Guide

## 📁 Storage Location

### Directory Path
```
/assets/delivery_attachments/
```

### Full Path (Server)
```
d:\xampp\htdocs\almutlak\system\assets\delivery_attachments\
```

### Access URL (Web Browser)
```
http://yourserver/almutlak/system/assets/delivery_attachments/{filename}
```

---

## 💾 How Files Are Stored

### Filename Format
```
{inv_no}_{YmdHis}_{uniqid}.{ext}
```

### Example Filenames
```
SR-2025-001_20250131120530_5dfb4c7ef8901.pdf
GR-2025-045_20250131150215_5dfb4d9a2c3f1.jpg
REQ-2025-012_20250131180745_5dfb4f1b7e4a2.docx
```

### Breakdown
- **SR-2025-001** = Request ID (inv_no)
- **20250131120530** = Timestamp (YYYYMMDDHHMMSS)
- **5dfb4c7ef8901** = Unique ID (uniqid)
- **pdf** = File extension

---

## 🗄️ Database Storage

### Table: general_request_deliveries

#### Column Added
```sql
ALTER TABLE `general_request_deliveries` 
ADD COLUMN `attachment_filename` VARCHAR(255) NULL AFTER `delivery_date`;
```

#### Table Structure
```sql
CREATE TABLE `general_request_deliveries` (
    `id` INT PRIMARY KEY AUTO_INCREMENT,
    `request_inv_no` VARCHAR(50),
    `received_by` VARCHAR(50),
    `delivery_date` DATETIME,
    `attachment_filename` VARCHAR(255) NULL,  -- NEW COLUMN
    FOREIGN KEY (`request_inv_no`) REFERENCES `general_requests`(`inv_no`)
);
```

#### Example Record
```sql
INSERT INTO general_request_deliveries VALUES (
    1,
    'SR-2025-001',
    'E001',
    '2025-01-31 12:05:30',
    'SR-2025-001_20250131120530_5dfb4c7ef8901.pdf'
);
```

---

## 📤 Upload Process Flow

### 1. User Selects File
```javascript
User clicks file input or drags file
    ↓
displayFileName() validates file
    ↓
Shows filename with green checkmark
```

### 2. Form Submission
```javascript
User clicks "Submit Delivery"
    ↓
submitDelivery() collects all data
    ↓
Builds FormData with:
   - Employee ID
   - Item statuses
   - File object (if selected)
    ↓
Sends POST to ajaxGeneralRequest.php
```

### 3. Server-Side Processing
```php
// In ajaxGeneralRequest.php - mark_delivery action

1. Validate file size (5MB max)
2. Validate file type (PDF, JPG, PNG, DOC, DOCX, XLSX, ZIP)
3. Create /assets/delivery_attachments/ directory if missing
4. Generate unique filename with timestamp + uniqid
5. Move file from temp location to delivery_attachments folder
6. Save filename in database
7. On error: Delete uploaded file and rollback transaction
```

---

## 🔒 File Validation

### File Size
```
Maximum: 5 MB (5,242,880 bytes)
Validation: Client-side + Server-side
```

### Allowed File Types
```
✓ PDF (.pdf)
✓ Images (.jpg, .jpeg, .png)
✓ Documents (.doc, .docx)
✓ Spreadsheets (.xlsx)
✓ Archives (.zip)
```

### Rejected File Types
```
✗ .exe, .bat, .com, .pif, .scr
✗ .html, .htm, .js
✗ .php, .asp, .jsp
✗ .app, .msi, .dll
✗ Any other executables or scripts
```

---

## 💻 Code Implementation

### Frontend (view_general_request.php)

```javascript
// File upload in modal
<input type="file" id="modal_attachment_file" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx,.xlsx,.zip">

// File validation
function displayFileName(fileInput) {
    const file = fileInput.files[0];
    const maxSize = 5 * 1024 * 1024; // 5MB
    
    if (file.size > maxSize) {
        // Show error and clear
    }
    // Generate FormData with file
}

// Submission
function submitDelivery(inv_no) {
    const formData = new FormData();
    
    if (fileInput.files.length > 0) {
        formData.append('attachment', fileInput.files[0]);
    }
    
    fetch('./includes/ajaxFile/ajaxGeneralRequest.php', {
        method: 'POST',
        body: formData
    });
}
```

### Backend (ajaxGeneralRequest.php)

```php
// mark_delivery action
elseif ($_POST['action'] === 'mark_delivery') {
    
    // Validate file
    if (!empty($_FILES['attachment'])) {
        $file = $_FILES['attachment'];
        $upload_dir = __DIR__ . '/../../assets/delivery_attachments/';
        
        // Check size: 5MB max
        if ($file['size'] > 5 * 1024 * 1024) {
            // Reject
        }
        
        // Check type: allowed extensions only
        $allowed_types = ['pdf', 'jpg', 'jpeg', 'png', 'doc', 'docx', 'xlsx', 'zip'];
        $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($file_ext, $allowed_types)) {
            // Reject
        }
        
        // Generate unique filename
        $timestamp = date('YmdHis');
        $random_id = uniqid();
        $attachment_filename = $inv_no . '_' . $timestamp . '_' . $random_id . '.' . $file_ext;
        
        // Move file to storage directory
        $upload_path = $upload_dir . $attachment_filename;
        move_uploaded_file($file['tmp_name'], $upload_path);
    }
    
    // Save filename to database
    $insert_delivery = "INSERT INTO general_request_deliveries 
                        (request_inv_no, received_by, delivery_date, attachment_filename)
                        VALUES ('$inv_no', '$received_by', NOW(), '$attachment_filename')";
}
```

---

## 📊 Storage Statistics

### Directory Size Estimation

| Scenario | Avg File Size | Files/Month | Monthly Size | Yearly Size |
|----------|---------------|------------|--------------|------------|
| Light Usage | 500 KB | 50 | 25 MB | 300 MB |
| Medium Usage | 1 MB | 200 | 200 MB | 2.4 GB |
| Heavy Usage | 2 MB | 500 | 1 GB | 12 GB |

### Server Recommendations
- **Minimum Disk Space**: 50 GB
- **Backup Strategy**: Weekly backups of /assets/delivery_attachments/
- **Cleanup Policy**: Archive files older than 2 years (optional)

---

## 🔍 Viewing Stored Attachments

### Method 1: Direct Download Link
```html
<a href="assets/delivery_attachments/SR-2025-001_20250131120530_5dfb4c7ef8901.pdf" 
   download>Download Attachment</a>
```

### Method 2: Display in Modal
```javascript
// Show file link in delivery details modal
const attachment = delivery_info['attachment_filename'];
if (attachment) {
    html += `<a href="assets/delivery_attachments/${attachment}" 
                target="_blank">📎 View Attachment</a>`;
}
```

### Method 3: Server-Side Access
```php
// Check if file exists
$filepath = __DIR__ . '/../../assets/delivery_attachments/' . $filename;
if (file_exists($filepath)) {
    // Process file
    // Serve for download, etc.
}
```

---

## 🛡️ Security Measures

### File Upload Security
```php
1. ✓ Validate file extension (whitelist only)
2. ✓ Validate file size (5MB max)
3. ✓ Generate random filename (prevent overwrites)
4. ✓ Check file MIME type (optional enhancement)
5. ✓ Store outside webroot (optional enhancement)
6. ✓ Use unique directory per request (optional)
```

### Access Control
```php
1. ✓ Check user authentication
2. ✓ Verify request ownership (delivery belongs to user)
3. ✓ Check delivery status (only approved/completed)
4. ✓ Log file downloads (optional)
```

### Error Handling
```php
if (upload fails) {
    1. Delete uploaded file
    2. Rollback database transaction
    3. Return error message to user
    4. Log error details
}
```

---

## 🗂️ Directory Structure

### Current
```
/assets/
    ├── cars_documents/
    ├── emp_documents/
    ├── general_request_attachments/
    ├── delivery_attachments/        ← NEW
    ├── smt_attachment/
    └── ...
```

### With Subdirectories (Future Enhancement)
```
/assets/delivery_attachments/
    ├── 2025-01/
    │   ├── SR-2025-001_*.pdf
    │   └── SR-2025-002_*.jpg
    ├── 2025-02/
    │   └── ...
    └── 2025-03/
        └── ...
```

---

## 📋 Setup Instructions

### Step 1: Create Directory
```bash
mkdir -p /xampp/htdocs/almutlak/system/assets/delivery_attachments
chmod 755 /xampp/htdocs/almutlak/system/assets/delivery_attachments
```

### Step 2: Add Database Column
Execute migration:
```sql
ALTER TABLE `general_request_deliveries` 
ADD COLUMN `attachment_filename` VARCHAR(255) NULL AFTER `delivery_date`;
```

### Step 3: Update Code
Deploy updated `ajaxGeneralRequest.php` with file handling

### Step 4: Test Upload
1. Create approved request
2. Click "Deliver Items"
3. Upload test file
4. Verify file appears in /assets/delivery_attachments/
5. Check database for filename

---

## 🐛 Troubleshooting

### Issue: Upload fails with "Failed to upload file"

**Causes:**
1. Directory doesn't exist → Create it manually
2. Directory not writable → Set chmod 755
3. File size too large → Check 5MB limit
4. Wrong file type → Check allowed extensions

**Solution:**
```bash
# Check directory exists and permissions
ls -la /xampp/htdocs/almutlak/system/assets/delivery_attachments/

# Fix permissions if needed
chmod 755 /xampp/htdocs/almutlak/system/assets/delivery_attachments/
```

### Issue: File uploaded but not saved in database

**Causes:**
1. Database column doesn't exist → Run migration
2. Wrong column name in SQL → Check spelling
3. Transaction rolled back → Check logs

**Solution:**
```bash
# Run migration
mysql -u root almutlak < migration_add_delivery_attachment.sql

# Verify column exists
DESCRIBE general_request_deliveries;
```

### Issue: File appears but can't download

**Causes:**
1. Wrong file path in link → Check filename
2. File deleted from server → Check server
3. Permission issue → Check file permissions

**Solution:**
```bash
# Check file exists and readable
ls -la /xampp/htdocs/almutlak/system/assets/delivery_attachments/SR-2025-001*

# Fix permissions if needed
chmod 644 /xampp/htdocs/almutlak/system/assets/delivery_attachments/*
```

---

## 🔮 Future Enhancements

### Phase 1: Current
- ✓ Single file upload per delivery
- ✓ File validation (size, type)
- ✓ Database storage

### Phase 2: Multiple Files
- [ ] Allow multiple attachments per delivery
- [ ] Create attachment_files table
- [ ] Show file gallery

### Phase 3: Virus Scanning
- [ ] ClamAV integration
- [ ] File scanning on upload
- [ ] Quarantine suspicious files

### Phase 4: Advanced Features
- [ ] File versioning
- [ ] Automatic archival after 2 years
- [ ] Cloud storage integration (AWS S3, Azure)
- [ ] File encryption

---

## 📝 Summary

| Aspect | Details |
|--------|---------|
| **Storage Location** | /assets/delivery_attachments/ |
| **Filename Format** | {inv_no}_{timestamp}_{uniqueid}.{ext} |
| **Max File Size** | 5 MB |
| **Allowed Types** | PDF, JPG, PNG, DOC, DOCX, XLSX, ZIP |
| **Database Column** | attachment_filename (VARCHAR 255) |
| **Table** | general_request_deliveries |
| **Access Control** | User authentication + request ownership |
| **Error Handling** | Rollback + file cleanup on failure |

---

**Version**: 1.0
**Status**: Ready for Implementation
**Last Updated**: January 31, 2025
