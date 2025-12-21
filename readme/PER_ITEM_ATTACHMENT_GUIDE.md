# Per-Item Attachment System for Delivery Modal

## Overview

The delivery attachment system has been updated to support **per-item attachments** instead of a single request-wide attachment. Each item in a delivery can now have its own individual attachment file.

---

## What Changed

### 1. Database Structure

**Table**: `general_request_items`

**New Column**:
```sql
ALTER TABLE `general_request_items` 
ADD COLUMN `attachment_filename` VARCHAR(255) NULL 
AFTER `delivery_status`;
```

**Example Row**:
```
id  | item_name      | quantity | delivery_status | attachment_filename
----|----------------|----------|-----------------|------------------------------------------
15  | Laptop         | 2        | delivered       | SR-2025-001_item15_20250117143022_5dfb4c.pdf
16  | Monitor        | 2        | delivered       | SR-2025-001_item16_20250117143022_5dfb4d.jpg
17  | Keyboard       | 2        | pending         | NULL
```

---

## 2. User Interface (Modal)

### Before
- Single file upload zone for entire request
- One attachment for all items

### After
- File upload zone **inside each item row**
- Each item can have its own optional attachment
- Remove button per item

**Modal Layout**:
```
┌─────────────────────────────────────────────────┐
│ 👤 Received By (Employee)                       │
│ [Select employee dropdown]                      │
└─────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────┐
│ 📦 Items to Deliver                             │
├─────────────────────────────────────────────────┤
│                                                 │
│ Item 1: Laptop (Qty: 2) [ID: 15]               │
│ ○ Delivered ○ Pending ○ Canceled               │
│                                                 │
│ 📎 Attachment for this item:                    │
│ [File Input] [Display with Remove]              │
│                                                 │
├─────────────────────────────────────────────────┤
│                                                 │
│ Item 2: Monitor (Qty: 2) [ID: 16]              │
│ ○ Delivered ○ Pending ○ Canceled               │
│                                                 │
│ 📎 Attachment for this item:                    │
│ [File Input] [Display with Remove]              │
│                                                 │
├─────────────────────────────────────────────────┤
│                                                 │
│ Item 3: Keyboard (Qty: 2) [ID: 17]             │
│ ○ Delivered ○ Pending ○ Canceled               │
│                                                 │
│ 📎 Attachment for this item:                    │
│ [File Input] [Display with Remove]              │
│                                                 │
└─────────────────────────────────────────────────┘
```

---

## 3. File Naming Convention

**Format**:
```
{invoice_no}_item{item_id}_{timestamp}_{unique_id}.{extension}
```

**Example**:
```
SR-2025-001_item15_20250117143022_5dfb4c7ef8901.pdf
SR-2025-001_item16_20250117143022_5dfb4d9a2c3f1.jpg
SR-2025-001_item17_20250117143022_5dfb4e1b4a6c2.png
```

**Benefits**:
- Unique per item (no conflicts)
- Timestamp prevents overwrites
- Item ID makes it traceable

---

## 4. Storage Location

**File System**:
```
/assets/delivery_attachments/SR-2025-001_item15_20250117143022_5dfb4c7ef8901.pdf
/assets/delivery_attachments/SR-2025-001_item16_20250117143022_5dfb4d9a2c3f1.jpg
/assets/delivery_attachments/SR-2025-001_item17_20250117143022_5dfb4e1b4a6c2.png
```

**Database**:
```sql
SELECT gri.id, gri.item_name, gri.delivery_status, gri.attachment_filename
FROM general_request_items gri
WHERE gri.request_inv_no = 'SR-2025-001';
```

---

## 5. Frontend JavaScript Changes

### Modal File Input
```javascript
// Each item has its own file input
<input type="file" id="modal_item_file_{item_id}" class="modal_item_file" data-item-id="{item_id}">
```

### File Validation (Per Item)
```javascript
function displayItemFileName(fileInput, itemId) {
    const file = fileInput.files[0];
    // Validates: size (5MB), type (PDF, JPG, PNG, DOC, DOCX, XLSX, ZIP)
    // Shows filename with remove button
}

function removeItemAttachment(itemId) {
    // Clears file for specific item
    document.getElementById('modal_item_file_' + itemId).value = '';
}
```

### Form Submission
```javascript
// Collect per-item attachments
for (const [itemId, status] of Object.entries(items)) {
    formData.append('items[' + itemId + ']', status);
    
    // Add file for this item if exists
    const fileInput = document.getElementById('modal_item_file_' + itemId);
    if (fileInput && fileInput.files.length > 0) {
        formData.append('attachments[' + itemId + ']', fileInput.files[0]);
    }
}
```

---

## 6. Backend PHP Changes

### Processing Per-Item Attachments

**File**: `ajaxGeneralRequest.php` (mark_delivery action)

```php
// Process per-item attachments
foreach ($attachments['name'] as $item_id => $filename) {
    if (!empty($filename) && !empty($attachments['tmp_name'][$item_id])) {
        // Validate file
        // Generate unique filename with item ID
        // Store attachment_filename in general_request_items for this item
    }
}

// Update each item with its attachment
UPDATE general_request_items 
SET delivery_status = 'delivered', 
    attachment_filename = '{filename}' 
WHERE id = {item_id}
```

---

## 7. Complete Data Flow

### Step 1: Load Modal
```
view_general_request.php
  → Fetch items from general_request_items
  → Display in modal with file upload per item
```

### Step 2: Select Status & Upload File
```
User selects:
  • Item 1: Delivered + uploads receipt.pdf
  • Item 2: Pending + uploads delivery_note.jpg
  • Item 3: Canceled + no attachment
```

### Step 3: Submit
```
submitDelivery()
  → Collects: items[15]=delivered, items[16]=pending, items[17]=canceled
  → Collects: attachments[15]=receipt.pdf, attachments[16]=delivery_note.jpg
  → Sends FormData to backend
```

### Step 4: Backend Processing
```
mark_delivery action:
  → Validate all files
  → Generate unique filenames per item
  → Move files to /assets/delivery_attachments/
  → Update general_request_items table:
     • item 15: attachment_filename = 'SR-2025-001_item15_..._xxx.pdf'
     • item 16: attachment_filename = 'SR-2025-001_item16_..._xxx.jpg'
     • item 17: attachment_filename = NULL
  → Update delivery statuses
  → Mark request completed if all items delivered
```

### Step 5: Database State
```sql
SELECT * FROM general_request_items WHERE request_inv_no = 'SR-2025-001':

id  | item_name  | delivery_status | attachment_filename
----|------------|-----------------|------------------------------------------
15  | Laptop     | delivered       | SR-2025-001_item15_20250117143022_5dfb4c.pdf
16  | Monitor    | pending         | SR-2025-001_item16_20250117143022_5dfb4d.jpg
17  | Keyboard   | canceled        | NULL
```

---

## 8. Validation Rules

| Parameter | Value |
|-----------|-------|
| Max Size per File | 5 MB |
| Allowed Types | PDF, JPG, JPEG, PNG, DOC, DOCX, XLSX, ZIP |
| Attachment Required | No (optional per item) |
| Per Item | ✓ Yes |

---

## 9. File System Structure

```
/xampp/htdocs/almutlak/system/
├── assets/
│   └── delivery_attachments/
│       ├── SR-2025-001_item15_20250117143022_5dfb4c7ef8901.pdf
│       ├── SR-2025-001_item16_20250117143022_5dfb4d9a2c3f1.jpg
│       ├── SR-2025-001_item17_20250117143022_5dfb4e1b4a6c2.png
│       └── [more files...]
└── view_general_request.php
```

---

## 10. Setup Steps

### Step 1: Database Migration
```bash
# Run the migration script
mysql -u root almutlak < database_migrations/add_attachment_to_items.sql
```

### Step 2: Create Directory
```bash
# Create storage directory with proper permissions
mkdir -p /xampp/htdocs/almutlak/system/assets/delivery_attachments
chmod 755 /xampp/htdocs/almutlak/system/assets/delivery_attachments
```

### Step 3: Deploy Files
```bash
# Update these files:
# - view_general_request.php (modal UI & JavaScript)
# - includes/ajaxFile/ajaxGeneralRequest.php (backend handler)
```

### Step 4: Verify
```bash
# Check database column
mysql -u root almutlak -e "DESCRIBE general_request_items;" | grep attachment

# Check directory permissions
ls -la /xampp/htdocs/almutlak/system/assets/delivery_attachments/
```

---

## 11. Query Examples

### Get All Items with Attachments
```sql
SELECT id, item_name, delivery_status, attachment_filename 
FROM general_request_items 
WHERE request_inv_no = 'SR-2025-001' 
AND attachment_filename IS NOT NULL;
```

### Get Delivery Summary
```sql
SELECT 
    gri.id,
    gri.item_name,
    gri.quantity,
    gri.delivery_status,
    gri.attachment_filename,
    grd.received_by,
    grd.delivery_date
FROM general_request_items gri
LEFT JOIN general_request_deliveries grd ON gri.delivery_id = grd.id
WHERE gri.request_inv_no = 'SR-2025-001';
```

---

## 12. Troubleshooting

| Issue | Solution |
|-------|----------|
| "Attachment not saving" | Check directory exists & has 755 permissions |
| "File not uploading" | Check max file size (5MB), allowed types |
| "NULL in database" | Attachment is optional - that's normal |
| "Cannot find file" | Check filename format in `/assets/delivery_attachments/` |
| "Permission denied" | Run: `chmod 755 /xampp/htdocs/almutlak/system/assets/delivery_attachments/` |

---

## 13. Summary

✅ **Per-Item Attachments**: Each item can have its own file
✅ **Optional**: Attachments are optional per item
✅ **Database**: Stored in `general_request_items.attachment_filename`
✅ **File System**: Stored in `/assets/delivery_attachments/`
✅ **Unique Names**: Format prevents conflicts
✅ **Transaction Safe**: Rollback deletes files on error
✅ **Validation**: Size & type validation per item
✅ **Remove Button**: ✓ Added for each item

---

**Status**: ✅ Ready to Deploy
