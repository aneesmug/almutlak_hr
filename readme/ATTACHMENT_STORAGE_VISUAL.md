# 📎 Attachment Storage Visual Guide

## 🔄 Complete Flow Diagram

```
┌─────────────────────────────────────────────────────────────────────────┐
│                    DELIVERY ATTACHMENT SYSTEM FLOW                       │
└─────────────────────────────────────────────────────────────────────────┘

1. USER SELECTS FILE
   ┌──────────────────────────────────────┐
   │  [📁 Drag files or click to browse]  │
   └──────────────────────────────────────┘
              ↓
        File selected
              ↓

2. CLIENT-SIDE VALIDATION (Frontend - view_general_request.php)
   ┌──────────────────────────────────┐
   │  Check file size (5MB max)       │
   │  Check file type (PDF, JPG, etc) │
   └──────────────────────────────────┘
              ↓
        ✓ Valid           ✗ Invalid
           ↓                  ↓
        Show filename   Error message
        Green check     Red alert
        + X button      File cleared
              ↓
        File ready to submit
              ↓

3. USER SUBMITS DELIVERY
   ┌────────────────────────────────────┐
   │  Select Employee                  │
   │  Choose Item Status               │
   │  📎 Attachment: filename.pdf      │
   │     [✓] [X]                       │
   │  [✓ Submit Delivery]              │
   └────────────────────────────────────┘
              ↓
        FormData sent
              ↓

4. BACKEND PROCESSING (ajaxGeneralRequest.php)
   ┌─────────────────────────────────────────────┐
   │  Mark: mark_delivery action                │
   │                                             │
   │  ✓ Validate file                           │
   │  ✓ Generate unique name                    │
   │  ✓ Move to /assets/delivery_attachments/  │
   │  ✓ Save filename to database               │
   │  ✓ Update item statuses                    │
   │  ✓ Auto-complete if all delivered         │
   │                                             │
   │  On Error:                                 │
   │  ✗ Delete uploaded file                    │
   │  ✗ Rollback database                       │
   │  ✗ Return error message                    │
   └─────────────────────────────────────────────┘
              ↓
        Success/Error response
              ↓

5. STORAGE LOCATIONS
   ┌──────────────────────────────────────────────────┐
   │                                                  │
   │  FILE SYSTEM                                    │
   │  /assets/delivery_attachments/                 │
   │  ├── SR-2025-001_20250131120530_5dfb4c7ef8901 │
   │  ├── SR-2025-002_20250131150215_5dfb4d9a2c3f │
   │  └── SR-2025-003_20250131180745_5dfb4f1b7e4a │
   │                                                  │
   │  DATABASE                                       │
   │  general_request_deliveries.attachment_filename │
   │  ├── SR-2025-001_20250131120530_5dfb4c7ef8901 │
   │  ├── SR-2025-002_20250131150215_5dfb4d9a2c3f │
   │  └── NULL (no file)                             │
   │                                                  │
   └──────────────────────────────────────────────────┘
              ↓
        Page reloads
        Success message shown
```

---

## 📊 File Storage Structure

```
SERVER DIRECTORY TREE
─────────────────────

/xampp/htdocs/almutlak/system/
│
├── assets/
│   ├── cars_documents/
│   ├── emp_documents/
│   ├── general_request_attachments/
│   │
│   └── delivery_attachments/        ← NEW DIRECTORY
│       │
│       ├── SR-2025-001_20250131120530_5dfb4c7ef8901.pdf
│       │   ├─ Size: 1.2 MB
│       │   ├─ Type: PDF Document
│       │   └─ Date: 2025-01-31 12:05:30
│       │
│       ├── SR-2025-002_20250131150215_5dfb4d9a2c3f1.jpg
│       │   ├─ Size: 500 KB
│       │   ├─ Type: JPEG Image
│       │   └─ Date: 2025-01-31 15:02:15
│       │
│       ├── SR-2025-003_20250131180745_5dfb4f1b7e4a2.docx
│       │   ├─ Size: 250 KB
│       │   ├─ Type: Word Document
│       │   └─ Date: 2025-01-31 18:07:45
│       │
│       └── ... (more files)
│
└── includes/
    └── ajaxFile/
        └── ajaxGeneralRequest.php    ← Updated
```

---

## 🗄️ Database Structure

```
TABLE: general_request_deliveries
──────────────────────────────────

PRIMARY KEY: id
│
├─ id (INT)
│  └─ Auto-increment ID
│
├─ request_inv_no (VARCHAR 50)
│  └─ Links to: general_requests.inv_no
│
├─ received_by (VARCHAR 50)
│  └─ Employee ID who received items
│
├─ delivery_date (DATETIME)
│  └─ When delivery was completed
│
└─ attachment_filename (VARCHAR 255) ← NEW COLUMN
   └─ Stores uploaded file name
      Example: SR-2025-001_20250131120530_5dfb4c7ef8901.pdf


EXAMPLE DATA
────────────

id | request_inv_no | received_by | delivery_date        | attachment_filename
─────────────────────────────────────────────────────────────────────────────
1  | SR-2025-001    | E001        | 2025-01-31 12:05:30 | SR-2025-001_20250131120530_5dfb4c7ef8901.pdf
2  | SR-2025-002    | E002        | 2025-01-31 15:02:15 | SR-2025-002_20250131150215_5dfb4d9a2c3f1.jpg
3  | SR-2025-003    | E003        | 2025-01-31 18:07:45 | NULL
4  | SR-2025-004    | E004        | 2025-01-31 20:30:10 | SR-2025-004_20250131203010_5dfb50c3f9e2d.zip
```

---

## 🎯 File Upload Modal Interface

```
┌───────────────────────────────────────────────────────────────┐
│  🚚 Delivery Details                                       X  │
├───────────────────────────────────────────────────────────────┤
│                                                               │
│  👤 Received By (Employee) *                                 │
│  ┌──────────────────────────────────────────────────────┐   │
│  │  [Ahmed Mohammed (E001) - HR        ▼]              │   │
│  └──────────────────────────────────────────────────────┘   │
│                                                               │
│  📦 Items to Deliver                                         │
│  ┌──────────────────────────────────────────────────────┐   │
│  │  VISA Testing (x1)                                  │   │
│  │  ⦿ ✓ Delivered  ○ ⏱ Pending  ○ ✕ Canceled          │   │
│  │                                                     │   │
│  │  Monitor (x2)                                       │   │
│  │  ⦿ ✓ Delivered  ○ ⏱ Pending  ○ ✕ Canceled          │   │
│  └──────────────────────────────────────────────────────┘   │
│                                                               │
│  📎 Attachment (Optional)                                    │
│  ┌──────────────────────────────────────────────────────┐   │
│  │  ☁️ Click to upload or drag and drop               │   │
│  │  PDF, Images, Documents (Max: 5MB)                 │   │
│  └──────────────────────────────────────────────────────┘   │
│                                                               │
│  FILE SELECTED                                               │
│  ┌──────────────────────────────────────────────────────┐   │
│  │  ✓ delivery_report.pdf       [X]                    │   │
│  └──────────────────────────────────────────────────────┘   │
│                                                               │
├───────────────────────────────────────────────────────────────┤
│  [✓ Submit Delivery]                  [Cancel]               │
└───────────────────────────────────────────────────────────────┘


FILE DISPLAY STATES
───────────────────

State 1: No File Selected
┌────────────────────────────────────────┐
│  (empty - ready for file selection)    │
└────────────────────────────────────────┘

State 2: File Selected
┌────────────────────────────────────────┐
│  ✓ document.pdf                  [X]  │
│  (Green box, checkmark, red button)    │
└────────────────────────────────────────┘

State 3: File Too Large (> 5MB)
┌────────────────────────────────────────┐
│  ⚠️ File too large (Max: 5MB)          │
│  (Red text, warning icon)              │
└────────────────────────────────────────┘

State 4: Invalid Type (.exe, etc)
┌────────────────────────────────────────┐
│  ⚠️ Invalid file type                  │
│  (Red text, warning icon)              │
└────────────────────────────────────────┘
```

---

## 🔍 Filename Generation Logic

```
FILENAME STRUCTURE
──────────────────

Format: {inv_no}_{timestamp}_{unique_id}.{extension}
        └─────┘ └─────────┘ └────────┘  └───────┘
           │        │           │          │
           │        │           │          └─ File extension
           │        │           └─ Unique ID (PHP uniqid())
           │        └─ Date/Time (YmdHis format)
           └─ Request ID


EXAMPLE BREAKDOWN
─────────────────

Filename: SR-2025-001_20250131120530_5dfb4c7ef8901.pdf
           ├─────────┼────────────────┼───────────────┼──┤
           │         │                │               │  │
           │         │                │               │  └─ .pdf (extension)
           │         │                │               │
           │         │                └─ 5dfb4c7ef8901 (uniqid)
           │         │
           │         └─ 20250131120530 (date: Jan 31, 2025 12:05:30)
           │
           └─ SR-2025-001 (request ID)


PREVENTS CONFLICTS
──────────────────

Same request, multiple deliveries:
- SR-2025-001_20250131120530_5dfb4c7ef8901.pdf  (1st delivery)
- SR-2025-001_20250131150215_5dfb4d9a2c3f1.pdf  (2nd delivery - different time)

Same filename won't overwrite because:
✓ Different timestamp (150215 vs 120530)
✓ Different uniqid (5dfb4d9a2c3f1 vs 5dfb4c7ef8901)
```

---

## 📋 File Validation Rules

```
VALIDATION FLOW
───────────────

┌─── File Selected ─────────────────────────┐
│                                           │
│  Step 1: Check Size                       │
│  ├─ Current: file.size                   │
│  ├─ Limit: 5 * 1024 * 1024 (5MB)        │
│  └─ if (size > limit) → ERROR            │
│         ↓                                 │
│  Step 2: Check Type                      │
│  ├─ Extract extension                    │
│  ├─ Allowed: pdf, jpg, jpeg, png,       │
│  │           doc, docx, xlsx, zip        │
│  └─ if (!allowed) → ERROR                │
│         ↓                                 │
│  Step 3: Generate Unique Name            │
│  ├─ Combine: inv_no + timestamp + id    │
│  ├─ Add extension                        │
│  └─ Result: ready for storage            │
│         ↓                                 │
│  Step 4: Upload File                     │
│  ├─ move_uploaded_file() to directory   │
│  ├─ Check success                        │
│  └─ if (fails) → Cleanup & ERROR         │
│         ↓                                 │
│  Step 5: Save to Database                │
│  ├─ INSERT/UPDATE filename               │
│  ├─ If fails → Delete file & Rollback   │
│  └─ Success!                             │
│                                           │
└─────────────────────────────────────────┘


ACCEPTED TYPES
──────────────

✓ PDF
  .pdf
  
✓ IMAGES
  .jpg, .jpeg, .png
  
✓ DOCUMENTS  
  .doc, .docx (Word)
  .xlsx (Excel)
  
✓ ARCHIVES
  .zip


REJECTED TYPES
──────────────

✗ Executables: .exe, .bat, .com, .app, .msi
✗ Scripts: .php, .asp, .jsp, .js, .html
✗ System: .dll, .sys, .ini, .cfg
✗ Other: .rar, .7z (only .zip allowed)
```

---

## 🚀 Deployment Flow

```
┌─────────────────────────────────────┐
│  1. Run Database Migration          │
│  └─ Add attachment_filename column  │
└─────────────────────────────────────┘
            ↓
┌─────────────────────────────────────┐
│  2. Create Storage Directory        │
│  └─ /assets/delivery_attachments/   │
└─────────────────────────────────────┘
            ↓
┌─────────────────────────────────────┐
│  3. Deploy Code Files               │
│  ├─ view_general_request.php        │
│  └─ ajaxGeneralRequest.php          │
└─────────────────────────────────────┘
            ↓
┌─────────────────────────────────────┐
│  4. Test File Upload                │
│  ├─ Create request                  │
│  ├─ Approve it                      │
│  ├─ Click Deliver                   │
│  ├─ Upload file                     │
│  └─ Verify storage                  │
└─────────────────────────────────────┘
            ↓
┌─────────────────────────────────────┐
│  5. Go Live! ✓                      │
└─────────────────────────────────────┘
```

---

**Visual Guide Complete** ✓
*All storage locations and flows documented above*
