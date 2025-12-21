# ⚡ Quick Answer: Where Will Delivery Attachments Be Stored?

## 📍 Storage Location

### File System (Server)
```
/xampp/htdocs/almutlak/system/assets/delivery_attachments/
```

### Directory to Create
```bash
mkdir /xampp/htdocs/almutlak/system/assets/delivery_attachments
chmod 755 /xampp/htdocs/almutlak/system/assets/delivery_attachments
```

### File Name Format
```
{request_id}_{date}_{unique_id}.{extension}

Example: SR-2025-001_20250131120530_5dfb4c7ef8901.pdf
```

---

## 🗄️ Database Storage

### Table
```
general_request_deliveries
```

### Column
```
attachment_filename (VARCHAR 255)
```

### Example Entry
```sql
SELECT * FROM general_request_deliveries 
WHERE request_inv_no = 'SR-2025-001';

┌────┬─────────────────┬─────────────┬─────────────────────┬──────────────────────────────────────┐
│ id │ request_inv_no  │ received_by │ delivery_date       │ attachment_filename                  │
├────┼─────────────────┼─────────────┼─────────────────────┼──────────────────────────────────────┤
│ 1  │ SR-2025-001     │ E001        │ 2025-01-31 12:05:30 │ SR-2025-001_20250131120530_5dfb4c... │
└────┴─────────────────┴─────────────┴─────────────────────┴──────────────────────────────────────┘
```

---

## 📊 Complete Path Examples

### Example 1: PDF Document
```
File System: /assets/delivery_attachments/SR-2025-001_20250131120530_5dfb4c7ef8901.pdf
Database:   SR-2025-001_20250131120530_5dfb4c7ef8901.pdf
Web URL:    http://yourserver/almutlak/system/assets/delivery_attachments/SR-2025-001_20250131120530_5dfb4c7ef8901.pdf
Size:       1.2 MB
Type:       PDF Document
```

### Example 2: JPEG Image
```
File System: /assets/delivery_attachments/SR-2025-002_20250131150215_5dfb4d9a2c3f1.jpg
Database:   SR-2025-002_20250131150215_5dfb4d9a2c3f1.jpg
Web URL:    http://yourserver/almutlak/system/assets/delivery_attachments/SR-2025-002_20250131150215_5dfb4d9a2c3f1.jpg
Size:       500 KB
Type:       JPEG Image
```

### Example 3: No Attachment
```
File System: (no file)
Database:   NULL
Web URL:    (not applicable)
Size:       -
Type:       -
```

---

## ✅ What Gets Saved Where

| Item | Saved In | Example |
|------|----------|---------|
| **File** | /assets/delivery_attachments/ | SR-2025-001_20250131120530_5dfb4c7ef8901.pdf |
| **Filename** | Database (attachment_filename) | SR-2025-001_20250131120530_5dfb4c7ef8901.pdf |
| **Request ID** | Database (request_inv_no) | SR-2025-001 |
| **Employee ID** | Database (received_by) | E001 |
| **Date/Time** | Database (delivery_date) | 2025-01-31 12:05:30 |

---

## 🔧 Implementation Checklist

- [ ] Create directory: `/assets/delivery_attachments/`
- [ ] Set permissions: `chmod 755`
- [ ] Run migration: Add `attachment_filename` column
- [ ] Deploy code files
- [ ] Test file upload
- [ ] Verify file in directory
- [ ] Check database entry

---

## 🎯 Key Points

✓ **Location**: `/assets/delivery_attachments/`
✓ **Format**: `{inv_no}_{timestamp}_{uniqueid}.{ext}`
✓ **Database**: `general_request_deliveries.attachment_filename`
✓ **Max Size**: 5 MB
✓ **Types**: PDF, JPG, PNG, DOC, DOCX, XLSX, ZIP
✓ **Optional**: Can submit without file
✓ **Remove Button**: ✓ Added (red X)

---

## 📚 Full Documentation

For more details, see:
- **DELIVERY_ATTACHMENT_STORAGE.md** - Complete guide
- **ATTACHMENT_STORAGE_VISUAL.md** - Visual diagrams
- **ATTACHMENT_IMPLEMENTATION_CHECKLIST.md** - Setup steps
- **ATTACHMENT_SUMMARY.md** - Overview

---

**Status**: ✅ Ready to Deploy
**Remove Button**: ✅ Implemented
**File Storage**: ✅ Fully Configured
