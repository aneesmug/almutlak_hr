# Delivery Modal - Visual Summary

## User Flow

### When Request is APPROVED ✓
```
┌─────────────────────────────────────┐
│  Ready for Delivery                 │
│  ───────────────────────────────────│
│  This request is approved.           │
│  Click below to mark items as       │
│  delivered.                          │
│                                      │
│  [🚚 Deliver Items]                 │
└─────────────────────────────────────┘
           ↓ (Click Button)
```

### SweetAlert2 Modal Opens
```
┌─────────────────────────────────────────────────────┐
│ 🚚 Delivery Details                          X      │
├─────────────────────────────────────────────────────┤
│                                                      │
│ 👤 Received By (Employee) *                        │
│ ┌──────────────────────────────────────────────┐   │
│ │ Search employee name, ID, or department...   │   │
│ └──────────────────────────────────────────────┘   │
│                                                      │
│ 📦 Items to Deliver                                │
│ ┌──────────────────────────────────────────────┐   │
│ │ VISA Testing (x1)                            │   │
│ │ ⦿ ✓ Delivered  ○ ⏱ Pending  ○ ✕ Canceled    │   │
│ │                                              │   │
│ │ Monitor (x2)                                 │   │
│ │ ⦿ ✓ Delivered  ○ ⏱ Pending  ○ ✕ Canceled    │   │
│ └──────────────────────────────────────────────┘   │
│                                                      │
│ 📎 Attachment (Optional)                           │
│ ┌──────────────────────────────────────────────┐   │
│ │  ☁️ Click to upload or drag and drop        │   │
│ │  PDF, Images, or Documents (Max: 5MB)       │   │
│ └──────────────────────────────────────────────┘   │
│                                                      │
├─────────────────────────────────────────────────────┤
│        [✓ Submit Delivery]    [Cancel]              │
└─────────────────────────────────────────────────────┘
```

### When Request is COMPLETED ✓
```
┌─────────────────────────────────────┐
│  ✓ Delivery Completed               │
│  ───────────────────────────────────│
│  This request has been completed.    │
│  Click below to view delivery        │
│  details.                            │
│                                      │
│  [👁️ View Delivery Details]         │
└─────────────────────────────────────┘
           ↓ (Click Button)
```

### Delivery Details Modal (Read-Only)
```
┌────────────────────────────────────────────┐
│ 📦 Delivery Details                   X    │
├────────────────────────────────────────────┤
│                                            │
│ ✓ Delivery Completed                      │
│ Received by: Ahmed Mohammed               │
│ Date: Jan 31, 2025 10:30 AM              │
│                                            │
│ Items Status:                              │
│ • VISA Testing (Qty: 1)   ✓ Delivered   │
│ • Monitor (Qty: 2)        ⏱ Pending     │
│ • Cable (Qty: 5)          ✓ Delivered   │
│                                            │
├────────────────────────────────────────────┤
│              [Close]                       │
└────────────────────────────────────────────┘
```

## Key Features

### Employee Selector
```
Select2 Dropdown
├─ Real-time AJAX search
├─ Search by: Name, ID, or Department
└─ Format: "Ahmed Mohammed (E001) - HR"
```

### Item Status Selection
```
For each item:
├─ ⦿ ✓ Delivered  (Green)  - Item received
├─ ○ ⏱ Pending    (Yellow) - Item not yet received
└─ ○ ✕ Canceled   (Red)    - Item cancelled
```

### File Upload
```
Drag & Drop Zone:
├─ Drag files directly
├─ Or click to browse
├─ Max size: 5MB
└─ Formats: PDF, JPG, PNG, DOC, DOCX, XLSX, ZIP
```

## Status Badge Colors in Items List

When delivery is completed, items show inline badges:

```
VISA Testing (x1) ✓ Delivered
Monitor (x2) ⏱ Pending
Cable (x5) ✕ Canceled
```

- 🟢 **Delivered** - Item successfully received
- 🟡 **Pending** - Item awaiting delivery
- 🔴 **Canceled** - Item cancelled

## Data Submitted

```json
{
  "action": "mark_delivery",
  "inv_no": "SR-2025-001",
  "received_by": "E001",
  "items[1]": "delivered",
  "items[2]": "pending",
  "items[3]": "delivered",
  "attachment": "<file_object>"
}
```

## Response Flow

```
Submit clicked
    ↓
Validate employee + items
    ↓
Show loading spinner
    ↓
Send to ajaxGeneralRequest.php
    ↓
Update database
    ↓
Check if all items delivered
    ↓
Auto-complete request if yes
    ↓
Return success
    ↓
Reload page
    ↓
Show "Delivery Completed" button
```

## Modal Properties

| Property | Value |
|----------|-------|
| Width | 700px |
| Max Height | ~90vh (scrollable) |
| Backdrop | Dark overlay |
| Animation | Smooth fade-in |
| Keyboard Support | ESC to close |
| Mobile Friendly | Yes (responsive) |

## Keyboard Shortcuts

- `Tab` - Navigate form fields
- `Space` - Select radio buttons
- `Enter` - Submit form
- `ESC` - Close modal
- `Ctrl+Enter` - Submit (custom)

---

**Modal Type**: SweetAlert2 v11
**Theme**: Modern, professional with Material Design
**Status**: Production Ready ✓
