# Settlement System - CORRECTED Implementation Guide

## 🎯 Overview

Settlement is a **payment/clearing workflow** that happens after a vacation or loan request is **fully approved**. It uses your **existing tables** (`request_approvers`, `smt_request_status`) and **doesn't create new settlement-specific tables**.

---

## 📊 Table Structure

### Tables Used:

| Table | Purpose |
|-------|---------|
| `app_settings` | Stores `approval_chain_settlement` configuration |
| `request_approvers` | Tracks settlement approvals (one entry per level) |
| `smt_request_status` | Logs all settlement status changes |
| `settlement_records` | *Optional* - Links original request to settlement |
| `approval_request_types` | Should have 'settlement' type |

### Key Fields:

**app_settings:**
```sql
setting_name = 'approval_chain_settlement'
setting_group = 'approval'           ← IMPORTANT: Must be 'approval'
input_type = 'json'                  ← IMPORTANT: Must be 'json'
setting_value = '[{...chain...}]'
```

**request_approvers:**
- `request_inv_no`: Settlement reference (e.g., `SETTLEMENT-VAC-202612...`)
- `request_type_id`: ID of 'settlement' type
- `approval_level`: 1, 2, 3, etc.
- `status`: 'pending', 'approved', 'rejected', 'awaiting'

**smt_request_status:**
- `inv_no`: Settlement reference
- `status`: 'approved_level_1', 'approved_level_2', 'approved_level_3', 'approved', 'rejected'

---

## 🔄 Settlement Workflow

```
Vacation/Loan Final Approved
          ↓
    [Show "Create Settlement" Button]
          ↓
    User clicks "Create Settlement"
          ↓
    1. Create entry in settlement_records (optional)
    2. Create approver entries in request_approvers (one per level)
    3. Add initial status to smt_request_status
          ↓
    Settlement enters approval workflow
          ↓
    Level 1 Approver: Dept Manager
       ↓ (approved)
    Level 2 Approver: Finance Officer
       ↓ (approved)
    Level 3 Approver: HR Payroll
       ↓ (approved)
    Mark Settlement as "processed"
    Process Payment
```

---

## ✅ Setup Steps

### Step 1: Execute SQL

Run `settlement_corrected.sql`:
```sql
-- Fixes app_settings entry
-- Adds settlement request type
-- Creates settlement_records table (optional)
```

### Step 2: Verify app_settings

```sql
SELECT * FROM app_settings WHERE setting_name = 'approval_chain_settlement';
```

Should return:
- `setting_group` = 'approval'
- `input_type` = 'json'
- `setting_value` = JSON array with 3 levels

### Step 3: Check Admin Panel

Visit: **app_settings.php → "approval" tab**

You should see:
- "Settlement" listed as a request type
- Ability to add/remove/modify approval levels
- All changes reflected in app_settings immediately

---

## 🔧 PHP Integration

### Use in Vacation Approval (all_applied_vac.php):

```php
// After vacation reaches final approval
if ($status === 'approved' && $final_approval) {
    // Show "Create Settlement" button
    echo '<button onclick="createSettlement(\'{$inv_no}\', \'annual_vacation\', {$amount})">Create Settlement</button>';
}
```

### Create Settlement via AJAX:

```php
// settlement_handler.php

require 'includes/SettlementManager_Corrected.php';

if ($_POST['action'] === 'create_settlement') {
    $settlementMgr = new SettlementManager($conDB);
    
    $result = $settlementMgr->createSettlement(
        $_POST['request_inv_no'],      // VAC-20261... or LOAN-...
        $_POST['request_type'],         // 'annual_vacation' or 'loan_request'
        $_POST['emp_id'],               // Employee ID
        $_POST['amount'],               // Payment amount
        $_SESSION['user_id']            // Current user
    );
    
    echo json_encode($result);
}
```

### Get Settlement Approvers:

```php
$settlementMgr = new SettlementManager($conDB);
$approvers = $settlementMgr->getSettlementApprovers('SETTLEMENT-VAC-...');

foreach ($approvers as $approver) {
    echo "Level " . $approver['approval_level'] . ": ";
    echo $approver['approver_name'] . " - ";
    echo $approver['status'];
}
```

### Approve Settlement:

```php
$result = $settlementMgr->approveSettlement(
    'SETTLEMENT-VAC-...',      // Settlement inv_no
    5430,                      // Approver ID
    1,                         // Level
    'Approved for payment'     // Notes
);
```

---

## 📋 Settlement Invoice Number Format

**Format:** `SETTLEMENT-{ORIGINAL_INV_NO}`

**Examples:**
- Original: `VAC-20261216-5165-b6f1`
- Settlement: `SETTLEMENT-VAC-20261216-5165-b6f1`

- Original: `LOAN-20261216-5123-a1b2`
- Settlement: `SETTLEMENT-LOAN-20261216-5123-a1b2`

---

## 👥 Approval Chain Roles

The settlement approval chain should include roles that exist in your `employees` table:

| Role | Description |
|------|-------------|
| `dept_manager` | Department Manager |
| `finance_officer` | Finance/Accounts Officer |
| `hr_payroll` | HR Payroll Specialist |
| `hr` | HR Manager |
| `gm` | General Manager |
| `finance` | Finance Manager |

---

## 📝 Settlement Status Tracking

### In `smt_request_status`:

```
Status Values:
- pending                    → Settlement just created
- approved_level_1          → Level 1 approved
- approved_level_2          → Level 2 approved
- approved_level_3          → Level 3 approved
- approved                  → All levels approved
- rejected                  → Settlement rejected
```

### Example Query:

```sql
SELECT * FROM smt_request_status 
WHERE inv_no LIKE 'SETTLEMENT-%'
ORDER BY created_at DESC;
```

---

## 🎛️ Managing Settlement Chain (Admin Panel)

### Via app_settings.php UI:

1. Login as Administrator
2. Go to **app_settings.php**
3. Click **"approval"** tab
4. Find **"Settlement"** section
5. Click **"Add Approver"** to add approval levels
6. Select role (Department Manager, Finance Officer, HR Payroll, etc.)
7. Click **"Save Changes"**

### Changes are immediate and stored in `app_settings` as JSON

---

## 🔗 Integration Checklist

- [ ] Run `settlement_corrected.sql`
- [ ] Verify `app_settings` has correct entry (group='approval', type='json')
- [ ] Check Admin Panel shows Settlement in approval tab
- [ ] Add "Create Settlement" button to vacation/loan approval pages
- [ ] Create settlement_handler.php for AJAX calls
- [ ] Create settlement_approvals.php for viewing pending settlements
- [ ] Create settlement_payment.php for processing payments
- [ ] Add Settlement links to main navigation menu
- [ ] Test complete workflow end-to-end

---

## 📞 Troubleshooting

### Settlement not showing in admin panel?
- Check `app_settings` has correct `setting_group = 'approval'`
- Verify `input_type = 'json'`
- Refresh browser cache

### Approval chain not working?
- Verify employee roles exist in `employees` table
- Check `approval_request_types` has 'settlement' type
- Review error logs for SQL errors

### Settlements not created?
- Check `settlement_records` table exists
- Verify `request_approvers` entries created
- Look for error_log entries

---

## 📚 Files Included

1. **settlement_corrected.sql** - Corrected database setup
2. **SettlementManager_Corrected.php** - Corrected settlement manager class
3. This guide (SETTLEMENT_CORRECTED.md)

---

## 💡 Key Differences from Previous Approach

| Aspect | Previous | Corrected |
|--------|----------|-----------|
| **Approval Storage** | settlement_approvals table | request_approvers table |
| **Status Tracking** | settlement_chain table | smt_request_status table |
| **app_settings Group** | 'general' | 'approval' ✓ |
| **app_settings Type** | 'text' | 'json' ✓ |
| **Request Type** | Not configured | Added to approval_request_types |
| **Uses Existing Tables** | New tables created | request_approvers, smt_request_status |

