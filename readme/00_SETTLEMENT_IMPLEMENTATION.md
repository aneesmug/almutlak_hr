# Settlement System - Complete Implementation Guide

## 📌 Overview

The Settlement System is a **post-approval payment workflow** for vacation and loan requests. After a request is **fully approved**, a Settlement is created that goes through its own **approval chain** before payment is processed.

### Key Points:
- ✅ Uses **existing tables**: `request_approvers`, `smt_request_status`, `employees`
- ✅ Integrated with **app_settings.php** admin panel
- ✅ Configured via **approval** tab with **json** input type
- ✅ Multi-level approval workflow (Department Manager → Finance Officer → HR Payroll)
- ✅ Complete tracking of approval history

---

## 🗂️ System Architecture

### How It Works:

```
REQUEST FINAL APPROVAL
        ↓
   Show "Create Settlement" Button
        ↓
   User Creates Settlement
        ↓
   SETTLEMENT-{INV_NO} Created
        ↓
   Entry in settlement_records
   Entries in request_approvers (1 per level)
   Entry in smt_request_status
        ↓
   Approval Chain Begins
        ↓
   Level 1: Department Manager
   Level 2: Finance Officer
   Level 3: HR Payroll
        ↓
   All Approved
        ↓
   Finance Processes Payment
```

---

## 📊 Database Schema

### Tables Used:

#### 1. **app_settings** (Configuration)
```sql
setting_name:     'approval_chain_settlement'
setting_group:    'approval'              ← Required
input_type:       'json'                  ← Required
setting_value:    '[{...chain...}]'
description:      'Settlement/Payment approval chain...'
```

**JSON Structure:**
```json
[
  {
    "level": 1,
    "user_type": "dept_manager",
    "role_label": "Department Manager"
  },
  {
    "level": 2,
    "user_type": "finance_officer",
    "role_label": "Finance Officer"
  },
  {
    "level": 3,
    "user_type": "hr_payroll",
    "role_label": "HR Payroll"
  }
]
```

#### 2. **request_approvers** (Approval Tracking - Existing Table)
```sql
request_inv_no:    'SETTLEMENT-VAC-20261216-5165-b6f1'
request_type_id:   {settlement type id}
approver_id:       {employee id}
approval_level:    1, 2, or 3
status:            'awaiting', 'approved', 'rejected', 'pending'
note:              Approval comments
action_date:       When approved/rejected
```

#### 3. **smt_request_status** (Status History - Existing Table)
```sql
inv_no:      'SETTLEMENT-VAC-20261216-5165-b6f1'
emp_id:      {approver emp_id}
emp_name:    'System'
note:        'Approved by Finance Officer'
status:      'approved_level_1', 'approved_level_2', 'approved_level_3', 'approved', 'rejected'
created_at:  timestamp
```

#### 4. **settlement_records** (Optional - Links Request to Settlement)
```sql
request_inv_no:  'VAC-20261216-5165-b6f1'    ← Original request
request_type:    'annual_vacation'            ← Or 'loan_request'
emp_id:          '5165'
amount:          5000.00
status:          'pending', 'approved', 'processed'
```

---

## 🎛️ Admin Configuration

### Access Settlement Chain Configuration:

1. **Login as Administrator**
2. Go to **app_settings.php**
3. Click **"approval"** tab
4. Find **"Settlement/Payment"** section
5. Modify approval levels:
   - **Add Approver**: Click "Add Approver" button
   - **Select Role**: Department Manager, Finance Officer, HR Payroll, etc.
   - **Save**: Click "Save Changes"

### Current Configuration:
| Level | Role | Description |
|-------|------|-------------|
| 1 | dept_manager | Department Manager |
| 2 | finance_officer | Finance Officer |
| 3 | hr_payroll | HR Payroll |

---

## 💻 PHP Implementation

### 1. Settlement Manager Class

**File:** `includes/SettlementManager_Corrected.php`

#### Create Settlement:
```php
require 'includes/SettlementManager_Corrected.php';

$manager = new SettlementManager($conDB);

$result = $manager->createSettlement(
    'VAC-20261216-5165-b6f1',  // Original request inv_no
    'annual_vacation',          // Request type
    '5165',                     // Employee ID
    5000.00,                    // Amount
    '5430'                      // Current user ID
);

// Result:
// {
//   "success": true,
//   "settlement_inv_no": "SETTLEMENT-VAC-20261216-5165-b6f1",
//   "message": "Settlement created successfully..."
// }
```

#### Get Approval Chain:
```php
$chain = $manager->getSettlementApprovalChain();
// Returns: Array of chain levels with roles
```

#### Get Settlement Approvers:
```php
$approvers = $manager->getSettlementApprovers('SETTLEMENT-VAC-20261216-5165-b6f1');
// Returns: Array of approver details with status
```

#### Approve Settlement:
```php
$result = $manager->approveSettlement(
    'SETTLEMENT-VAC-20261216-5165-b6f1',  // Settlement inv_no
    5430,                                  // Approver ID
    1,                                     // Level
    'Approved for payment'                 // Notes
);
```

### 2. API Handler

**File:** `includes/api/settlement_handler.php`

```php
<?php
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../SettlementManager_Corrected.php';

header('Content-Type: application/json');

$action = $_POST['action'] ?? '';

switch ($action) {
    case 'create_settlement':
        $manager = new SettlementManager($conDB);
        $result = $manager->createSettlement(
            $_POST['request_inv_no'],
            $_POST['request_type'],
            $_POST['emp_id'],
            $_POST['amount'],
            $_SESSION['user_id']
        );
        echo json_encode($result);
        break;
        
    case 'get_approvers':
        $manager = new SettlementManager($conDB);
        $approvers = $manager->getSettlementApprovers($_POST['settlement_inv_no']);
        echo json_encode(['success' => true, 'approvers' => $approvers]);
        break;
        
    case 'approve':
        $manager = new SettlementManager($conDB);
        $result = $manager->approveSettlement(
            $_POST['settlement_inv_no'],
            $_SESSION['user_id'],
            $_POST['level'],
            $_POST['notes']
        );
        echo json_encode($result);
        break;
}
?>
```

### 3. Frontend JavaScript

```javascript
// Create Settlement
async function createSettlement(requestInvNo, requestType, amount) {
    const response = await fetch('./includes/api/settlement_handler.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({
            action: 'create_settlement',
            request_inv_no: requestInvNo,
            request_type: requestType,
            emp_id: document.getElementById('emp_id').value,
            amount: amount
        })
    });
    
    const data = await response.json();
    if (data.success) {
        alert('Settlement created: ' + data.settlement_inv_no);
        // Redirect to settlement approvals page
        window.location.href = 'settlement_approvals.php';
    } else {
        alert('Error: ' + data.message);
    }
}

// Get Approvers
async function getSettlementApprovers(settlementInvNo) {
    const response = await fetch('./includes/api/settlement_handler.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({
            action: 'get_approvers',
            settlement_inv_no: settlementInvNo
        })
    });
    
    return await response.json();
}

// Approve Settlement
async function approveSettlement(settlementInvNo, level, notes) {
    const response = await fetch('./includes/api/settlement_handler.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({
            action: 'approve',
            settlement_inv_no: settlementInvNo,
            level: level,
            notes: notes
        })
    });
    
    return await response.json();
}
```

---

## 📄 Implementation Files

### Included Files:

1. **sql/settlement_corrected.sql**
   - Fixes app_settings entry
   - Adds settlement request type
   - Creates settlement_records table

2. **includes/SettlementManager_Corrected.php**
   - Main class for settlement operations
   - Uses request_approvers and smt_request_status
   - Methods: createSettlement, approveSettlement, getApprovers

3. **SETTLEMENT_CORRECTED.md**
   - Detailed technical documentation
   - Database schema
   - Setup instructions

4. **This file (SETTLEMENT_FINAL_SUMMARY.md)**
   - Complete implementation guide
   - All necessary code samples
   - Step-by-step instructions

---

## 🚀 Step-by-Step Implementation

### Step 1: Database Setup ✅ DONE
- Executed `settlement_corrected.sql`
- Created correct app_settings entry
- Added settlement request type
- Created settlement_records table

### Step 2: Verify Admin Panel ✅ DONE
- Settlement appears in app_settings.php → approval tab
- Can modify approval chain
- Changes saved to app_settings

### Step 3: Add Settlement Button to Vacation Page
**File:** `all_applied_vac.php`

```php
// After vacation is final approved
if ($final_status === 'approved' && $approval_complete) {
    ?>
    <button class="btn btn-success" onclick="createSettlement(
        '<?php echo $inv_no; ?>', 
        'annual_vacation', 
        <?php echo $vacation_days * 350; ?>
    )">
        Create Settlement
    </button>
    <?php
}
?>
```

### Step 4: Create Settlement API Handler
**File:** `includes/api/settlement_handler.php`

Create with the code from PHP Implementation section above

### Step 5: Create Settlement Approvals Page
**File:** `settlement_approvals.php`

```php
<?php
require_once 'includes/session_check.php';
require_once 'includes/SettlementManager_Corrected.php';

$manager = new SettlementManager($conDB);

// Get pending settlements for current user
$query = mysqli_query($conDB, "
    SELECT DISTINCT sr.*, ra.approval_level
    FROM settlement_records sr
    JOIN request_approvers ra ON ra.request_inv_no = CONCAT('SETTLEMENT-', sr.request_inv_no)
    WHERE ra.approver_id = " . $_SESSION['user_id'] . "
    AND ra.status = 'awaiting'
    ORDER BY sr.created_at DESC
");

include 'includes/header.php';
?>
<div class="container mt-4">
    <h3>Pending Settlements for Approval</h3>
    <table class="table table-striped">
        <thead>
            <tr>
                <th>Settlement ID</th>
                <th>Request Type</th>
                <th>Employee</th>
                <th>Amount</th>
                <th>Level</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = mysqli_fetch_assoc($query)) { ?>
                <tr>
                    <td><?php echo 'SETTLEMENT-' . $row['request_inv_no']; ?></td>
                    <td><?php echo $row['request_type']; ?></td>
                    <td><?php echo $row['emp_id']; ?></td>
                    <td><?php echo $row['amount']; ?> SAR</td>
                    <td>Level <?php echo $row['approval_level']; ?></td>
                    <td>
                        <button onclick="approveSettlement(...)">Approve</button>
                        <button onclick="rejectSettlement(...)">Reject</button>
                    </td>
                </tr>
            <?php } ?>
        </tbody>
    </table>
</div>
<?php include 'includes/footer.php'; ?>
```

### Step 6: Add Navigation Links
**File:** `includes/main_menu.php`

Add under HR or Finance menu section:
```html
<li class="has-treeview">
    <a href="#"><i class="fa fa-money"></i> <span>Settlement</span></a>
    <ul class="treeview-menu">
        <li><a href="settlement_approvals.php">Pending Approvals</a></li>
        <li><a href="settlement_payment.php">Process Payments</a></li>
    </ul>
</li>
```

### Step 7: Test Workflow

1. **Create Vacation**
   - Employee requests vacation
   - Gets all approvals

2. **Create Settlement**
   - Click "Create Settlement" button
   - Settlement created with pending approvals

3. **Approve Settlement**
   - Department Manager approves (Level 1)
   - Finance Officer approves (Level 2)
   - HR Payroll approves (Level 3)

4. **Process Payment**
   - Finance marks as paid
   - Update payment reference
   - Complete

---

## ✅ Verification Queries

### Check Settlement Configuration:
```sql
SELECT setting_name, setting_group, input_type, setting_value 
FROM app_settings 
WHERE setting_name = 'approval_chain_settlement';
```

Expected:
- setting_group = 'approval'
- input_type = 'json'

### View Pending Settlements:
```sql
SELECT sr.*, ra.approval_level, e.name
FROM settlement_records sr
JOIN request_approvers ra ON ra.request_inv_no = CONCAT('SETTLEMENT-', sr.request_inv_no)
JOIN employees e ON e.id = ra.approver_id
WHERE sr.status = 'pending'
ORDER BY sr.created_at DESC;
```

### View Settlement Approval History:
```sql
SELECT inv_no, emp_id, note, status, created_at
FROM smt_request_status
WHERE inv_no LIKE 'SETTLEMENT-%'
ORDER BY inv_no DESC, created_at DESC;
```

---

## 📞 Troubleshooting

### Settlement not showing in admin panel?
- Verify: `SELECT * FROM app_settings WHERE setting_name='approval_chain_settlement'`
- Check: `setting_group = 'approval'`
- Confirm: `input_type = 'json'`
- Refresh browser cache

### Can't create settlement?
- Check employee roles in `employees` table
- Verify `settlement_records` table exists
- Check error logs: `error_logs/`

### Settlement not approving?
- Verify current user is in request_approvers
- Check user has correct role in employees table
- Review `request_approvers` table entries

### Approvers not found?
- Check `employees` table for roles
- Verify `user_type` matches configuration
- Ensure employees have active status

---

## 📚 Additional Resources

- **SETTLEMENT_CORRECTED.md** - Detailed technical documentation
- **SettlementManager_Corrected.php** - Class reference
- **settlement_corrected.sql** - Database schema

---

## 🎉 Summary

You now have:
- ✅ Proper Settlement approval chain in app_settings
- ✅ Correct configuration (approval group, json type)
- ✅ Integration with existing request_approvers table
- ✅ Status tracking in smt_request_status
- ✅ SettlementManager class ready for use
- ✅ Complete PHP/JavaScript code samples
- ✅ Admin panel integration

Next: Implement the UI pages and test the complete workflow!

