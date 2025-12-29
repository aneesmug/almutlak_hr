# Approval Chain Configuration - Implementation Summary

## What Was Created

### 1. Frontend UI (app_seetings.php)
✅ Added new "Approval" tab in Application Settings
✅ Custom interface for managing approval chains by request type
✅ Support for 5 request types:
   - Vacation Request
   - Excuse Leave  
   - Loan Request
   - Resignation Request
   - Rejoin Request
✅ Excluded Smart Request and General Request (as requested)
✅ Add/Remove approver functionality
✅ Visual approval chain display with level badges

### 2. Backend Handler (includes/approval_chain_handler.php)
✅ API endpoints for CRUD operations:
   - `get_approval_chain` - Retrieve configured chain
   - `add_approval_step` - Add new approver to chain
   - `remove_approval_step` - Remove approver from chain
   - `update_approval_order` - Reorder approval steps (future use)
✅ Admin-only access control
✅ JSON-based storage in app_settings table
✅ Automatic level renumbering when steps are removed

### 3. Database Schema (sql/add_approval_chain_settings.sql)
✅ SQL script to create initial settings
✅ 5 approval chain settings (one per request type)
✅ Enable/disable toggle for feature
✅ Uses existing app_settings table structure

### 4. Documentation (docs/APPROVAL_CHAIN_CONFIGURATION.md)
✅ Complete installation guide
✅ Usage instructions
✅ API reference
✅ Integration examples
✅ Troubleshooting tips

## How to Use

### Step 1: Install
```bash
# Import SQL file via phpMyAdmin or command line
mysql -u username -p database_name < sql/add_approval_chain_settings.sql
```

### Step 2: Access Settings
1. Login as Administrator
2. Go to Application Settings (app_seetings.php)
3. Click "Approval" tab

### Step 3: Configure Chains
1. Select request type (e.g., Vacation Request)
2. Click "Add Approver" button
3. Choose role from dropdown
4. Click "Add"
5. Repeat for all approval levels
6. Remove unwanted steps with delete icon

## Available Approver Roles

**From Database (admin_login.user_type):**
- Administrator
- General Manager (GM)
- HR Senior BP
- HR Operations
- HR Supervisor
- HR Recruitment
- HR Payroll
- HR
- Finance Officer
- Finance
- Auditor
- GR Officer
- IT
- Department User
- Assistant

**Additional Custom Roles:**
- Direct Supervisor
- Department Manager
- IT Manager
- Admin Manager
- Transportation Manager

## Key Features

✅ **Visual Management** - See entire approval chain at a glance
✅ **Flexible Configuration** - Add/remove steps as needed
✅ **Auto-Leveling** - Levels automatically renumber
✅ **Per-Type Chains** - Different workflow for each request type
✅ **Admin-Only** - Secure configuration access
✅ **JSON Storage** - Efficient database structure
✅ **Ready to Integrate** - Easy to use in existing code

## ⚠️ IMPORTANT: Asset Clearance NOT Affected

**The new approval chain configuration is for STATIC approvers only.**

**Asset clearance logic remains DYNAMIC and unchanged:**
- ✅ IT Manager - auto-added if employee has laptop/computer
- ✅ Admin Manager - auto-added if employee has mobile/SIM
- ✅ Transportation Manager - auto-added if employee has vehicle

**Both systems work TOGETHER:**

```
Example Vacation Approval Flow:
├─ Level 1: Direct Supervisor (Static - from config)
├─ Level 2: HR Senior BP (Static - from config)
├─ Level 3: IT Manager (Dynamic - if laptop assigned)
├─ Level 4: Admin Manager (Dynamic - if mobile assigned)
├─ Level 5: Transportation Manager (Dynamic - if vehicle assigned)
└─ Level 6: HR Payroll (Static - from config)
```

**What this means:**
- Configure **static** approval steps in Application Settings
- **Keep** existing asset clearance code in ajaxVacation.php (lines 815-860)
- Final approval chain = Static approvers + Dynamic asset clearance

## Integration Example

```php
// In your request handler (e.g., ajaxVacation.php)
$settingName = "approval_chain_vacation_request";
$query = mysqli_query($conDB, "SELECT setting_value FROM app_settings WHERE setting_name = '$settingName'");

if ($query && mysqli_num_rows($query) > 0) {
    $row = mysqli_fetch_assoc($query);
    $approvalChain = json_decode($row['setting_value'], true);
    
    foreach ($approvalChain as $step) {
        $userType = $step['user_type'];
        $level = $step['level'];
        
        // Get employee with this role
        $res = mysqli_query($conDB, "SELECT e.emp_id FROM employees e 
                                      JOIN admin_login al ON e.emp_id = al.emp_id 
                                      WHERE al.user_type = '$userType' AND e.status = 1 
                                      LIMIT 1");
        
        if ($res && ($row = mysqli_fetch_assoc($res))) {
            // Add to approval chain
            $approvers[] = $row['emp_id'];
        }
    }
}
```

## Files Modified/Created

### Modified:
- `app_seetings.php` (added approval chain UI)

### Created:
- `includes/approval_chain_handler.php` (backend API)
- `sql/add_approval_chain_settings.sql` (database schema)
- `docs/APPROVAL_CHAIN_CONFIGURATION.md` (documentation)

## Next Steps

1. ✅ Run SQL script to create settings
2. ✅ Test UI in app_seetings.php
3. ✅ Configure approval chains for each request type
4. 🔄 Integrate with existing request handlers:
   - ajaxVacation.php (vacation & leave)
   - ajaxLoan.php (loans)
   - ajaxResignation.php (resignations)
   - ajaxRejoin.php (rejoin)

## Notes

- Smart Request and General Request are intentionally excluded
- Approval chains stored as JSON in app_settings table
- Each request type has independent configuration
- Admin-only access ensures security
- Chains can be modified at any time without code changes

---

**Created:** December 21, 2025
**Developer:** GitHub Copilot
**Status:** Ready for Testing
