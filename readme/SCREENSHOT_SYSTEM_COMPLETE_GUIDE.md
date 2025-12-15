# Screenshot System - Complete Guide

## Overview

Your system is now fully set up to display multiple screenshots for each step in the system guide. Here's everything you need to know to use it.

---

## What You Have

### 1. **Admin Upload Panel** 
📁 File: `manage_guide_screenshots.php`
- Simple form to upload screenshots one at a time
- Select Section → Step Number → Add Title → Upload Image
- Automatically organizes files in `assets/screenshots/` folder

### 2. **System Guide Page**
📁 File: `system_guide.php`
- Shows all uploaded screenshots per section/step
- Displays screenshots in a responsive grid
- Falls back to icon placeholders if no images uploaded yet

### 3. **Helper Documents**

#### Quick Reference Guide
📁 File: `screenshot_requirements.php`
- Visual guide showing what screenshots you need for each section
- Step-by-step breakdown for every process
- Direct links to upload panel

#### Upload Checklist
📁 File: `screenshot_checklist.html`
- Track your progress uploading screenshots
- Shows which steps still need images
- Auto-saves progress to browser

#### Detailed Instructions
📁 File: `SCREENSHOT_UPLOAD_INSTRUCTIONS.md`
- Comprehensive written guide
- Best practices for taking screenshots
- File size and format requirements

---

## How to Upload Screenshots

### Quick Steps:

1. **Go to Admin Panel**
   - Navigate to: `manage_guide_screenshots.php`
   - Only administrators can access

2. **Fill Out the Form**
   - **Section**: Choose from list (vacations, loans, excuse, resignation, rejoin)
   - **Step Number**: 1-7 depending on section
   - **Title**: Description of what the screenshot shows
   - **File**: Select the image from your computer

3. **Click Upload**
   - File is saved automatically
   - Screenshot immediately appears in system guide

4. **Repeat** for all 30-35 screenshots

---

## What Screenshots You Need

### VACATIONS & LEAVES (21 screenshots)

**Annual Leave - 7 screenshots (Section: "vacations", Step: 1)**
1. Go to Profile Page - Show profile menu location
2. Click More Button - Show where More button is
3. Select Annual Vacation - Show dropdown with option
4. Vacation Details Form - Show complete form
5. Choose Dates - Show date picker/calendar
6. Select Type - Show vacation type dropdown (Annual/Emergency/Fly)
7. Submit Button - Show submit button clearly

**Emergency Leave - 6-7 screenshots (Section: "vacations", Step: 2)**
- Follow same steps but for Emergency Leave option

**Encashment - 6-7 screenshots (Section: "vacations", Step: 3)**
- Follow same steps but for Encashment (vacation to cash) option

---

### LOANS (12 screenshots)

**EOS Loan - 4 screenshots (Section: "loans", Step: 1)**
1. Select EOS Loan Type
2. Enter Loan Amount
3. Select Monthly Installment
4. Review & Submit

**House Loan - 4 screenshots (Section: "loans", Step: 2)**
1. Select House Loan Type
2. Enter Property Details
3. Upload Real Estate Contract
4. Loan Amount & Tenure

**Advance Salary - 4 screenshots (Section: "loans", Step: 3)**
1. Select Advance Salary
2. Enter Advance Amount
3. Select Repayment Period
4. Submit & Confirm

---

### OTHER SECTIONS (4-9 screenshots)

**Excuse Leave - 4 screenshots (Section: "excuse", Step: 3)**
1. Select Excuse Leave Option
2. Choose Absence Date
3. Provide Reason/Details
4. Submit Request

**Resignation - 3 screenshots (Section: "resignation", Step: 1)**
1. Select Apply Resignation
2. Fill Resignation Form
3. Confirm & Submit

**Rejoin Request - 3 screenshots (Section: "rejoin", Step: 3)**
1. Select Rejoin Request
2. Confirm Return Date
3. Submit Rejoin

---

## File Requirements

| Requirement | Specification |
|-------------|---|
| **Formats** | JPG, PNG, GIF, WebP |
| **Max Size** | 5 MB per image |
| **Recommended Resolution** | 1280x720 or 1366x768 |
| **Recommended File Size** | 500KB - 2MB |
| **Text Readability** | Must be clear and readable |

---

## How It Works Behind the Scenes

### Database Storage
```
guide_screenshots table
├─ section (vacations, loans, excuse, resignation, rejoin)
├─ step_number (1-7)
├─ title (descriptive name)
├─ file_path (location of image)
├─ display_order (order within step)
├─ is_active (show/hide)
└─ uploaded_by (admin who uploaded)
```

### File Organization
```
assets/screenshots/
├─ vacations/
│  ├─ vacations_1_[timestamp].jpg
│  ├─ vacations_2_[timestamp].jpg
│  └─ vacations_3_[timestamp].jpg
├─ loans/
│  ├─ loans_1_[timestamp].jpg
│  ├─ loans_2_[timestamp].jpg
│  └─ loans_3_[timestamp].jpg
├─ excuse/
├─ resignation/
└─ rejoin/
```

### Display Logic
The system guide page (`system_guide.php`) automatically:
1. Queries database for screenshots by section and step
2. Shows ALL uploaded images for that section/step in a grid
3. Falls back to icon placeholder if no images exist
4. Displays image title below each screenshot
5. Handles broken image links gracefully

---

## Best Practices

### ✅ DO:
- Use consistent browser and screen resolution
- Highlight important buttons/fields with arrows if needed
- Hide sensitive information (passwords, email, phone)
- Use clear, professional language in titles
- Test images display correctly before uploading
- Keep screenshots in logical order (upload Step 1 images first)
- Compress large images before uploading

### ❌ DON'T:
- Use blurry or low-quality images
- Include personal/sensitive data
- Mix different screen resolutions
- Use overly large file sizes
- Forget to add clear titles
- Upload same screenshot multiple times
- Include desktop clutter (taskbar, notifications)

---

## Quick Tools

### 1. View Requirements
👉 `screenshot_requirements.php`
- Visual guide with step-by-step breakdowns
- Shows what you need for each section
- Quick links to upload panel

### 2. Track Progress
👉 `screenshot_checklist.html`
- Checkoff list for all screenshots needed
- Shows overall progress percentage
- Auto-saves your progress locally

### 3. Upload Screenshots
👉 `manage_guide_screenshots.php`
- Upload panel for admin users only
- One-click upload process
- Automatic file organization

### 4. View System Guide
👉 `system_guide.php`
- Live preview of all screenshots
- Shows how guide appears to employees
- Accessible from main site menu

---

## Troubleshooting

### Image Not Appearing?
1. Check file format (JPG, PNG, GIF, WebP only)
2. Verify file size is under 5MB
3. Ensure image file is not corrupted
4. Try re-uploading with a different format

### Wrong Section/Step?
1. Go back to upload panel
2. Check the database records
3. Edit database if needed to move images

### File Too Large?
1. Use image compression tool (TinyPNG, ImageOptim)
2. Reduce resolution to 1280x720
3. Try JPEG format instead of PNG
4. Remove excess whitespace

### Database Issues?
- All database operations use PDO prepared statements (secure)
- Automatic error handling and rollback
- Check file system permissions on `assets/screenshots/` folder

---

## Support

For help with:
- **Uploading screenshots**: Use `screenshot_requirements.php`
- **Tracking progress**: Use `screenshot_checklist.html`
- **Detailed instructions**: Read `SCREENSHOT_UPLOAD_INSTRUCTIONS.md`
- **System guide display**: Edit `system_guide.php`

---

## What's Next?

1. **Review** `screenshot_requirements.php` to understand what's needed
2. **Use** `screenshot_checklist.html` to track progress
3. **Start uploading** via `manage_guide_screenshots.php`
4. **View** results in `system_guide.php`

---

**Last Updated**: December 14, 2025
**Total Screenshots Needed**: ~30-35 images
**Estimated Time to Complete**: 2-3 hours

