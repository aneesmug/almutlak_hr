# Approval Chain Configuration Feature

## Overview
This feature allows administrators to configure custom approval workflows for different request types through the Application Settings interface.

## Installation

### Step 1: Run SQL Script
Execute the SQL script to add approval chain settings to your database:

```bash
# Navigate to the SQL directory
cd d:\xampp\htdocs\almutlak\system\sql

# Run the SQL script using phpMyAdmin or MySQL command line
mysql -u your_username -p your_database_name < add_approval_chain_settings.sql
```

Or import via phpMyAdmin:
1. Open phpMyAdmin
2. Select your database (almutlak_db)
3. Click "Import" tab
4. Choose file: `sql/add_approval_chain_settings.sql`
5. Click "Go"

### Step 2: Verify Files
Ensure these files exist:
- `app_seetings.php` (updated with approval chain UI)
- `includes/approval_chain_handler.php` (backend handler)
- `sql/add_approval_chain_settings.sql` (database schema)

## Usage

### Accessing Approval Chain Settings

1. Log in as **Administrator**
2. Navigate to **Application Settings** (app_seetings.php)
3. Click on the **"Approval"** tab in the left sidebar
4. You'll see 5 request types:
   - Vacation Request
   - Excuse Leave
   - Loan Request
   - Resignation Request
   - Rejoin Request

### Configuring an Approval Chain

1. **Add an Approver:**
   - Click the "Add Approver" button under the desired request type
   - Select a role from the dropdown (e.g., HR Senior BP, HR Payroll, etc.)
   - Click "Add"
   - The approver will be added to the end of the chain

2. **Remove an Approver:**
   - Click the delete icon (🗑️) next to the approval step
   - Confirm the removal
   - Levels will be automatically renumbered

3. **View Current Chain:**
   - Each approval step shows:
     - Level number (badge)
     - Role name
     - Remove button

### Available Approver Roles

**From Database (admin_login.user_type):**
- **Administrator** - System administrator
- **General Manager (GM)** - General Manager
- **HR Senior BP** - HR Senior Business Partner
- **HR Operations** - HR Operations Team
- **HR Supervisor** - HR Supervisor
- **HR Recruitment** - Recruitment Team
- **HR Payroll** - Payroll Processing Team
- **HR** - General HR Department
- **Finance Officer** - Finance Officer
- **Finance** - Finance Department
- **Auditor** - Internal Auditor
- **GR Officer** - Government Relations Officer
- **IT** - IT Department
- **Department User** - Department User
- **Assistant** - Assistant

**Additional Custom Roles:**
- **Direct Supervisor** - Employee's direct supervisor
- **Department Manager** - Department Head
- **IT Manager** - IT Department Manager
- **Admin Manager** - Administration Manager
- **Transportation Manager** - Transportation Department Manager

## Request Types

### 1. Vacation Request
- Annual vacation applications
- Fly vacation (annual & emergency)
- Applies to: `emp_vacation` table with `vac_type = 'Fly'` or `'Local Vacation'`

### 2. Excuse Leave
- Sick leave
- Exam leave
- Hajj leave
- Maternity leave
- Marriage leave
- Death/Bereavement leave
- Business trips
- Other excuse types
- Applies to: `emp_vacation` table with various `vac_type` values

### 3. Loan Request
- Regular loans
- Emergency loans
- End of service loans
- Housing loans
- Advance salary
- Applies to: `emp_loan` table

### 4. Resignation Request
- Employee resignation applications
- Applies to: `emp_resignation` table

### 5. Rejoin Request
- Re-joining after resignation
- Applies to: `emp_rejoin` table

## Excluded Request Types

The following request types are **NOT** included in approval chain configuration:
- **Smart Request** - Uses different approval logic
- **General Request** - Uses different approval logic

These use custom approval workflows defined elsewhere in the system.

## Database Structure

### app_settings Table
Each approval chain is stored as a JSON array:

```json
[
  {
    "level": 1,
    "user_type": "direct_supervisor",
    "role_label": "Direct Supervisor"
  },
  {
    "level": 2,
    "user_type": "hr_payroll",
    "role_label": "HR Payroll"
  }
]
```

### Setting Names
- `approval_chain_vacation_request`
- `approval_chain_excuse_leave`
- `approval_chain_loan_request`
- `approval_chain_resignation_request`
- `approval_chain_rejoin_request`

## API Endpoints

### Get Approval Chain
```javascript
POST: includes/approval_chain_handler.php
{
  action: 'get_approval_chain',
  request_type: 'vacation_request'
}
```

### Add Approval Step
```javascript
POST: includes/approval_chain_handler.php
{
  action: 'add_approval_step',
  request_type: 'vacation_request',
  user_type: 'hr_payroll'
}
```

### Remove Approval Step
```javascript
POST: includes/approval_chain_handler.php
{
  action: 'remove_approval_step',
  request_type: 'vacation_request',
  level: 2
}
```

## Integration with Existing Code

### ⚠️ IMPORTANT: Asset Clearance Logic is NOT Affected

**The new approval chain configuration manages STATIC approvers only.**

**Asset clearance (IT, Admin, Transportation) remains DYNAMIC and automatic:**
- ✅ Employee has laptop → IT Manager automatically added
- ✅ Employee has mobile/SIM → Admin Manager automatically added  
- ✅ Employee has vehicle → Transportation Manager automatically added

**These two systems work TOGETHER, not as replacements.**

### How They Combine

**Example Vacation Request Flow:**

```
Level 1: Direct Supervisor        ← Static (from approval chain config)
Level 2: HR Senior BP              ← Static (from approval chain config)
Level 3: IT Manager                ← Dynamic (if laptop assigned)
Level 4: Admin Manager             ← Dynamic (if mobile assigned)
Level 5: Transportation Manager    ← Dynamic (if vehicle assigned)
Level 6: HR Payroll                ← Static (from approval chain config)
```

### Integration Code Pattern

To use the configured approval chains in your request handlers:

```php
// Step 1: Get static approvers from configuration
function getStaticApproversFromConfig($conDB, $requestType) {
    $settingName = "approval_chain_{$requestType}";
    $query = mysqli_query($conDB, "SELECT setting_value FROM app_settings WHERE setting_name = '$settingName' LIMIT 1");
    
    if ($query && mysqli_num_rows($query) > 0) {
        $row = mysqli_fetch_assoc($query);
        $chain = json_decode($row['setting_value'], true);
        mysqli_free_result($query);
        return $chain ?? [];
    }
    
    return [];
}

// Step 2: In your approval chain building (e.g., ajaxVacation.php)
$approvers = [];

// Add static approvers from configuration
$staticChain = getStaticApproversFromConfig($conDB, 'vacation_request');
foreach ($staticChain as $step) {
    if ($step['user_type'] === 'direct_supervisor') {
        // Add employee's supervisor
        $approvers[] = $employee_supervisor_id;
    } elseif ($step['user_type'] === 'dept_manager') {
        // Add department manager
        $approvers[] = $dept_manager_id;
    } else {
        // Add by user_type (hr_payroll, hr_senior_bp, etc.)
        $res = mysqli_query($conDB, "SELECT e.emp_id FROM employees e 
                                      JOIN admin_login al ON e.emp_id = al.emp_id 
                                      WHERE al.user_type = '{$step['user_type']}' 
                                      AND e.status = 1 LIMIT 1");
        if ($res && ($row = mysqli_fetch_assoc($res))) {
            $approvers[] = $row['emp_id'];
        }
        if ($res) mysqli_free_result($res);
    }
}

// Step 3: Add dynamic asset clearance approvers (KEEP THIS EXISTING LOGIC)
// Check assigned assets and add IT/Admin/Transport managers automatically
$asset_query = mysqli_query($conDB, "SELECT a.name AS asset_name 
                                      FROM employee_assets ea 
                                      JOIN assets a ON ea.asset_id = a.id 
                                      WHERE ea.emp_id = '$emp_id' AND ea.status = 'Assigned'");

$needs_it = false;
$needs_admin = false;
$needs_transport = false;

while ($asset_query && ($asset = mysqli_fetch_assoc($asset_query))) {
    $asset_name = strtolower($asset['asset_name']);
    if (strpos($asset_name, 'laptop') !== false) $needs_it = true;
    if (strpos($asset_name, 'mobile') !== false) $needs_admin = true;
    if (strpos($asset_name, 'car') !== false) $needs_transport = true;
}

if ($needs_it) {
    // Add IT Manager
    $it_mgr = getDeptManager($conDB, 'IT');
    if ($it_mgr) $approvers[] = $it_mgr['emp_id'];
}
if ($needs_admin) {
    // Add Admin Manager  
    $admin_mgr = getDeptManager($conDB, 'Administration');
    if ($admin_mgr) $approvers[] = $admin_mgr['emp_id'];
}
if ($needs_transport) {
    // Add Transportation Manager
    $transport_mgr = getDeptManager($conDB, 'Transportation');
    if ($transport_mgr) $approvers[] = $transport_mgr['emp_id'];
}

// Step 4: Save combined approval chain
save_approval_chain($conDB, $request_inv_no, 'vacation_request', $approvers);
```

### Key Points

1. **Static Approvers** (from config):
   - Configured in Application Settings → Approval tab
   - Same for all employees
   - Examples: HR Senior BP, HR Payroll, Finance Officer

2. **Dynamic Approvers** (automatic):
   - Based on employee's assigned assets
   - Different for each employee
   - Examples: IT Manager, Admin Manager, Transportation Manager

3. **Both Work Together**:
   - Config provides base approval structure
   - Asset clearance adds required department approvers
   - Final chain = Static + Dynamic approvers

## Troubleshooting

### Approval tab not showing
- Verify SQL script was executed successfully
- Check if 'approval' group exists in app_settings
- Ensure you're logged in as administrator

### Cannot add/remove approvers
- Check browser console for JavaScript errors
- Verify `includes/approval_chain_handler.php` exists and is readable
- Check database permissions

### Approval chain not saving
- Check MySQL error logs
- Verify app_settings table has write permissions
- Ensure JSON encoding is working (PHP json_encode)

## Security

- Only users with `user_type = 'administrator'` can configure approval chains
- All inputs are sanitized using `mysqli_real_escape_string()`
- Session validation required for all operations

## Future Enhancements

- Drag-and-drop reordering of approval levels
- Conditional approval paths (e.g., amount-based routing)
- Email notifications when chain is modified
- Approval chain versioning/history
- Clone approval chain from one type to another

## Support

For issues or questions, contact the development team or refer to:
- Main documentation: `/system/README.md`
- Copilot instructions: `/system/.github/copilot-instructions.md`
