# General Request System - Quick Start Guide

## 🚀 5-Minute Setup

### Step 1: Run SQL Installation (2 minutes)
```bash
# Navigate to your MySQL client or phpMyAdmin
# Execute the installation script:
SOURCE d:/xampp/htdocs/almutlak/system/sql/install_general_requests.sql;

# OR using command line:
mysql -u root -p almutlak_db < sql/install_general_requests.sql
```

### Step 2: Verify Database (1 minute)
```sql
-- Check tables exist
SHOW TABLES LIKE 'general_request%';
-- Should show: general_requests, general_request_items, general_request_attachments

-- Check approval type
SELECT * FROM approval_request_types WHERE main_table_name = 'general_requests';
-- Should return 1 row
```

### Step 3: Verify Files (1 minute)
All files are already created! ✅
- ✅ Upload directory: `assets/general_request_attachments/` (already created)
- ✅ Frontend pages: 3 files in root directory
- ✅ AJAX handlers: 2 files in `includes/ajaxFile/`
- ✅ Documentation: 4 files

### Step 4: Access the System (1 minute)
1. Login as a **non-employee user** (Manager, Supporter, etc.)
2. Navigate to: `http://your-domain/almutlak/system/all_general_requests.php`
3. Click **"New Request"** button
4. Test creating a request!

---

## ✨ First Request Test

### Create Your First General Request:
1. **Click** "New Request" button in `all_general_requests.php`
2. **Fill** the form:
   - Title: "Test Laptop Request"
   - Department: "IT"
   - Priority: "Medium"
   - Category: "Equipment"
3. **Add** an item:
   - Item Name: "Dell Laptop"
   - Type: "Laptop"
   - Quantity: 1
   - Specs: "i7 processor, 16GB RAM"
4. **Submit** the request
5. **View** your request - it should be in "Draft" status
6. **Select approvers** (department managers)
7. **Submit for approval**

---

## 📋 What You Get

### For Users:
- Create requests for items/services from any department
- Track request status in real-time
- Upload supporting documents
- Receive notifications when approved/rejected

### For Managers:
- Review and approve team requests
- View all pending approvals in one place
- Add approval notes
- Multi-level approval workflow

### For Administrators:
- Full visibility across all departments
- Delete/manage any request
- Export data for reporting
- Monitor system usage

---

## 🎯 Quick Reference

### User Access Levels:
| User Type | Can Create? | Can Approve? | Can Delete? |
|-----------|-------------|--------------|-------------|
| Employee | ❌ No | ❌ No | ❌ No |
| Regular User | ✅ Yes | ❌ No | ✅ Own drafts only |
| Manager | ✅ Yes | ✅ Assigned requests | ✅ Own drafts only |
| Administrator | ✅ Yes | ✅ Any request | ✅ Any request |

### Request Statuses:
- **Draft** - Created but not submitted
- **Pending Approval** - Waiting for approver action
- **Approved** - All approvers approved
- **Rejected** - Rejected by an approver

### Priority Levels:
- **Low** - Can wait, not urgent
- **Medium** - Normal priority (default)
- **High** - Important, needed soon
- **Urgent** - Critical, immediate attention

---

## 🔧 Common Departments & Items

### IT Department
Request: Laptop, Desktop, Monitor, Printer, Software, Network Equipment

### Transportation
Request: Vehicle, Mobile Phone, SIM Card, Fuel Card, GPS

### HR
Request: Stationery, Office Supplies, Furniture, ID Card

### Finance
Request: Calculator, Receipt Book, Document Folder

### Maintenance
Request: Tools, Equipment, Safety Gear

---

## ❓ Quick Troubleshooting

### "Cannot access general requests"
- **Issue**: User type is "employee"
- **Fix**: System is for non-employee users only (managers, supporters, admins)

### "Request not found"
- **Issue**: Invalid request ID or deleted request
- **Fix**: Go back to list, click on a valid request

### "Cannot upload files"
- **Issue**: Directory permissions
- **Fix** (Windows): Ensure `assets/general_request_attachments/` exists
- **Fix** (Linux): Run `chmod 777 assets/general_request_attachments`

### "No approvers available"
- **Issue**: No managers in your department
- **Fix**: Contact administrator to assign managers

---

## 📞 Need Help?

1. **Read** the full documentation: `GENERAL_REQUESTS_README.md`
2. **Check** installation checklist: `INSTALLATION_CHECKLIST.md`
3. **Review** project summary: `PROJECT_SUMMARY.md`
4. **Contact** your system administrator

---

## 🎉 You're Ready!

The General Request System is now installed and ready to use. Start creating requests and streamline your department's operations!

### Next Steps:
1. ✅ Test creating a request
2. ✅ Test the approval workflow
3. ✅ Add menu entry (optional - see INSTALLATION_CHECKLIST.md)
4. ✅ Train your users
5. ✅ Enjoy the streamlined process!

---

**System Version**: 1.0.0  
**Last Updated**: December 16, 2025  
**Support**: See GENERAL_REQUESTS_README.md
