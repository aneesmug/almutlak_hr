# System Guide Screenshots - Setup Instructions

## Quick Start Guide

### Step 1: Initialize the Database Table
1. Open your browser and go to: `http://localhost/almutlak/system/create_screenshots_table.php`
2. You should see a success message confirming the table was created
3. Click "Go to Screenshot Manager"

### Step 2: Access the Screenshot Manager
**URL:** `http://localhost/almutlak/system/manage_guide_screenshots.php`

**Requirements:**
- You must be logged in as an **ADMIN** user
- Only admins can upload and manage screenshots

### Step 3: Upload Screenshots
1. **Select Section:** Choose from:
   - Vacations & Leaves
   - Loans
   - Excuse Leave
   - Resignation
   - Rejoin Request

2. **Enter Step Number:** 
   - For "Annual Leave" (step 1 in vacations), enter: `1`
   - For "Emergency Leave" (step 2 in vacations), enter: `2`
   - For "Encashment" (step 3 in vacations), enter: `3`
   - And so on...

3. **Add Screenshot Title:**
   - Examples: "Profile Page", "Application Form", "Confirmation Screen"
   - This text appears below the image in the guide

4. **Select Image File:**
   - Click "Browse" and select an image from your computer
   - Accepted formats: JPEG, PNG, GIF, WebP
   - Maximum file size: 5MB
   - Recommended size: 1024x768px or larger

5. **Click Upload**

### Step 4: View Screenshots in Guide
1. Go to: `http://localhost/almutlak/system/system_guide.php`
2. Navigate to different sections (tabs)
3. Scroll down in each section to see the screenshot gallery
4. **If you uploaded screenshots:** They will appear as real images
5. **If you haven't uploaded:** Default icon placeholders will show

## Section & Step Reference

### Vacations & Leaves Section
- Step 1: Annual Leave
- Step 2: Emergency Leave
- Step 3: Encashment

### Loans Section
- Step 1: End of Service (EOS) Loan
- Step 2: House Loan
- Step 3: Advance Salary Loan

### Excuse Leave Section
- Step 1: What is Excuse Leave
- Step 2: How to Apply
- Step 3: Approval Process

### Resignation Section
- Step 1: Initiate Resignation
- Step 2: Exit Interview
- Step 3: Post-Resignation Process

### Rejoin Section
- Step 1: What is Rejoin Request
- Step 2: How to Submit
- Step 3: After Rejoin

## Taking Screenshots

### Recommended Process:
1. **Login to your system** as a regular employee
2. **Navigate to each step** you want to capture:
   - Go to Profile → More Menu → Apply Vacation/Loan/Excuse etc.
   - Fill in forms and dialogs that appear
3. **Capture with Screenshot Tool:**
   - **Windows:** Press `Print Screen` or use `Win + Shift + S`
   - **Mac:** Press `Cmd + Shift + 4`
   - **Linux:** Use your system's screenshot tool
4. **Edit if needed:**
   - Use Paint, Preview, or Photoshop to crop/annotate
   - Add arrows or highlights to important fields
5. **Save as:**
   - PNG format (best quality)
   - Resize to ~1024x768 if very large

### Example Screenshots:
- Profile page header with name and options
- "More" menu dropdown
- Vacation application form with fields
- Confirmation dialogs
- Success messages
- Approval status screens

## Troubleshooting

### Screenshots Not Showing
1. ✅ Verify table was created: Run `create_screenshots_table.php` again
2. ✅ Check you're logged in as ADMIN
3. ✅ Ensure images are in `assets/screenshots/[section]/`
4. ✅ Check browser console (F12) for errors

### Upload Failed
- ✅ File size > 5MB? Compress the image
- ✅ Wrong format? Use PNG or JPEG
- ✅ Missing fields? Fill in all required fields (Section, Step, Title)
- ✅ Permissions? Check `assets/screenshots/` folder permissions

### Can't Access Manager
- ✅ Are you logged in as ADMIN? Check user type
- ✅ Try logging out and back in
- ✅ Try a different browser

## Database Table Structure

```sql
CREATE TABLE `guide_screenshots` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `section` VARCHAR(50),           -- vacations, loans, excuse, resignation, rejoin
  `step_number` INT,               -- 1, 2, 3, etc.
  `title` VARCHAR(100),            -- Display title
  `filename` VARCHAR(255),         -- Original filename
  `file_path` VARCHAR(255),        -- Path to image
  `display_order` INT,             -- Order to display
  `is_active` TINYINT,            -- Show/hide screenshot
  `uploaded_by` INT,               -- Admin user ID
  `created_at` TIMESTAMP,
  `updated_at` TIMESTAMP
);
```

## File Structure

```
almutlak/system/
├── create_screenshots_table.php      ← Run once to create table
├── manage_guide_screenshots.php       ← Upload/manage screenshots (ADMIN ONLY)
├── system_guide.php                  ← View guide with screenshots
├── includes/
│   └── screenshot_helper.php         ← Helper functions
└── assets/
    └── screenshots/                  ← Uploaded images stored here
        ├── vacations/
        ├── loans/
        ├── excuse/
        ├── resignation/
        └── rejoin/
```

## Tips & Best Practices

✅ **Do:**
- Take clear, well-lit screenshots
- Highlight important fields with arrows
- Use consistent image dimensions
- Upload high-quality images (PNG recommended)
- Add descriptive titles
- Organize by section and step number

❌ **Don't:**
- Upload low-res/blurry images
- Mix different resolutions
- Forget to label screenshots
- Upload personal/sensitive data
- Upload very large files (>5MB)

## Support

If you encounter issues:
1. Check the browser console (F12) for JavaScript errors
2. Check the server logs in `error_log`
3. Verify database table exists: `SELECT * FROM guide_screenshots;`
4. Ensure upload directory is writable: `chmod 755 assets/screenshots/`

---

**Setup Complete!** 🎉 Your system guide is now ready to display real screenshots.
