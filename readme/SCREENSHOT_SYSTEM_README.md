# 📸 System Guide Screenshot Upload System

## Overview

A complete screenshot upload and management system for the Al-Mutlak WMS System Guide. Admins can upload real screenshots which automatically display in place of icon placeholders in the guide.

## Features

✅ **Easy Upload Interface** - Simple admin panel for uploading screenshots  
✅ **Automatic Organization** - Screenshots organized by section and step  
✅ **Gallery Display** - Beautiful responsive image gallery in system guide  
✅ **Fallback Icons** - Icon placeholders appear if no screenshots uploaded  
✅ **Access Control** - Upload panel restricted to admins only  
✅ **Image Preview** - Preview images before uploading  
✅ **Multiple Formats** - Support for PNG, JPEG, GIF, WebP  
✅ **Mobile Friendly** - Responsive design works on all devices  

## Files Created

| File | Purpose |
|------|---------|
| `create_screenshots_table.php` | Initialize database table (run ONCE) |
| `manage_guide_screenshots.php` | Admin upload/management panel |
| `screenshot_setup_info.php` | Setup information page |
| `SCREENSHOT_SETUP_GUIDE.md` | Detailed setup instructions |
| `includes/screenshot_helper.php` | Helper functions |
| `assets/screenshots/` | Folder for storing images |

## Installation

### Step 1: Create Database Table
```bash
# In your browser, navigate to:
http://localhost/almutlak/system/create_screenshots_table.php
```

You should see: ✅ "Screenshots table created successfully!"

### Step 2: Upload Screenshots
```bash
# Login as ADMIN and go to:
http://localhost/almutlak/system/manage_guide_screenshots.php
```

### Step 3: View in Guide
```bash
# Login as any employee and view:
http://localhost/almutlak/system/system_guide.php
```

Screenshots automatically appear in the guide!

## Database Schema

```sql
CREATE TABLE `guide_screenshots` (
  `id` int(11) AUTO_INCREMENT PRIMARY KEY,
  `section` varchar(50) NOT NULL,        -- vacations, loans, excuse, resignation, rejoin
  `step_number` int(11) NOT NULL,        -- 1, 2, 3, etc.
  `title` varchar(100) NOT NULL,         -- Display title
  `filename` varchar(255) NOT NULL,      -- Original filename
  `file_path` varchar(255) NOT NULL,     -- Relative path to image
  `display_order` int(11) DEFAULT 1,     -- Order in gallery
  `is_active` tinyint(1) DEFAULT 1,      -- Show/hide
  `uploaded_by` int(11),                 -- Admin user ID
  `created_at` timestamp DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY `idx_section` (`section`),
  KEY `idx_step` (`step_number`),
  KEY `idx_active` (`is_active`)
);
```

## Section & Step Reference

### Vacations & Leaves
- **Step 1:** Annual Leave
- **Step 2:** Emergency Leave
- **Step 3:** Encashment

### Loans
- **Step 1:** End of Service (EOS) Loan
- **Step 2:** House Loan
- **Step 3:** Advance Salary Loan

### Excuse Leave
- **Step 1:** What is Excuse Leave
- **Step 2:** How to Apply
- **Step 3:** Approval Process

### Resignation
- **Step 1:** Initiate Resignation
- **Step 2:** Exit Interview
- **Step 3:** Post-Resignation Process

### Rejoin
- **Step 1:** What is Rejoin Request
- **Step 2:** How to Submit
- **Step 3:** After Rejoin

## Upload Requirements

| Requirement | Details |
|------------|---------|
| **Format** | PNG, JPEG, GIF, WebP |
| **Max Size** | 5MB |
| **Recommended Size** | 1024x768px or larger |
| **Access** | Admin users only |
| **Organization** | By section and step number |

## How It Works

### For Admins:
1. Login as admin
2. Go to `manage_guide_screenshots.php`
3. Select section, step number, add title
4. Upload image
5. Screenshots appear in guide automatically!

### For Employees:
1. Visit `system_guide.php`
2. Browse different sections
3. See real screenshots (or icon placeholders)
4. Follow step-by-step instructions with visual reference

### Technical Flow:
```
1. Admin uploads image
   ↓
2. File saved to assets/screenshots/[section]/
   ↓
3. Database record created
   ↓
4. system_guide.php queries database
   ↓
5. Gallery displays real images (or fallback icons)
```

## Code Examples

### Query Screenshots
```php
// In system_guide.php
$stmt = $pdo->query("
    SELECT section, step_number, title, file_path 
    FROM guide_screenshots 
    WHERE is_active = 1 
    ORDER BY section, step_number, display_order
");
```

### Display Images
```php
<?php foreach ($screenshots as $shot): ?>
    <img src="<?= htmlspecialchars($shot['file_path']) ?>" 
         alt="<?= htmlspecialchars($shot['title']) ?>">
<?php endforeach; ?>
```

### Fallback to Icons
```php
<?php if (!empty($screenshots)): ?>
    <!-- Show uploaded images -->
<?php else: ?>
    <!-- Show icon placeholders -->
<?php endif; ?>
```

## Troubleshooting

### 🔴 Table Creation Failed
- Check database permissions
- Verify `config.ini` settings
- Try running script again

### 🔴 Can't Access Upload Manager
- Verify you're logged in as ADMIN
- Check user type in database
- Try logging out and back in

### 🔴 Upload Failed
- File size > 5MB? Compress the image
- Wrong format? Use PNG or JPEG
- All fields filled? Check all required fields

### 🔴 Images Not Showing
- Clear browser cache (Ctrl+Shift+Delete)
- Check file permissions on `assets/screenshots/`
- Verify relative paths in database

### 🔴 Folder Permissions Error
```bash
# Make directory writable:
chmod 755 assets/screenshots/
chmod 755 assets/screenshots/*
```

## File Structure

```
almutlak/system/
├── create_screenshots_table.php
├── manage_guide_screenshots.php
├── screenshot_setup_info.php
├── system_guide.php (modified to fetch screenshots)
├── includes/
│   └── screenshot_helper.php
├── assets/
│   └── screenshots/
│       ├── vacations/
│       ├── loans/
│       ├── excuse/
│       ├── resignation/
│       └── rejoin/
└── SCREENSHOT_SETUP_GUIDE.md
```

## Admin Features

✅ Upload screenshots  
✅ Preview before upload  
✅ Organize by section/step  
✅ View all uploaded images  
✅ Delete screenshots  
✅ Add descriptive titles  
✅ Set display order  
✅ Activate/deactivate screenshots  

## Security Features

🔒 **Admin-Only Access** - Upload panel restricted to admin users  
🔒 **File Type Validation** - Only images allowed  
🔒 **File Size Limit** - Max 5MB to prevent abuse  
🔒 **Relative Paths** - Images stored outside web root when possible  
🔒 **SQL Injection Prevention** - PDO prepared statements  
🔒 **XSS Protection** - htmlspecialchars() on all output  

## Performance

- 📊 Lazy loading of images
- 🖼️ Fallback placeholders until loaded
- 🗂️ Indexed database queries
- 📱 Responsive grid layout

## Browser Support

✅ Chrome/Edge (v88+)  
✅ Firefox (v85+)  
✅ Safari (v14+)  
✅ Mobile browsers  

## Support

For issues or questions:
1. Check `SCREENSHOT_SETUP_GUIDE.md` for detailed instructions
2. Review troubleshooting section above
3. Check browser console for errors (F12)
4. Contact system administrator

## Future Enhancements

- 🎬 Video tutorials option
- 🏷️ Auto-tagging by AI
- 📊 Analytics on screenshot views
- 🔍 Advanced search/filter
- 👥 User-submitted screenshots
- ⭐ Screenshot ratings/feedback

---

**Version:** 1.0  
**Last Updated:** December 2025  
**Status:** Production Ready ✅
