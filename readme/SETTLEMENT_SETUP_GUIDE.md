# Settlement System - Quick Setup Guide

## Step 1: Execute Database Schema
Run the SQL file to create settlement tables:

```bash
# Using MySQL CLI
mysql -u root -p almutlak < sql/settlement_implementation.sql

# Or import via phpMyAdmin
# 1. Open phpMyAdmin
# 2. Select database 'almutlak'
# 3. Tools → Import
# 4. Select sql/settlement_implementation.sql
# 5. Click Import
```

**What it does:**
- Creates `settlement_records` table (main settlement payments)
- Creates `settlement_chain` table (approval chain config)
- Creates `settlement_approvals` table (approval workflow tracking)
- Adds columns to `emp_vacation` and `emp_loan` tables
- Configures default settlement chains for vacation and loan

---

## Step 2: Verify Installation

Run these queries in phpMyAdmin to verify:

```sql
-- Check settlement tables exist
SHOW TABLES LIKE 'settlement%';

-- Check emp_vacation has settlement columns
DESCRIBE emp_vacation;

-- Check emp_loan has settlement columns
DESCRIBE emp_loan;

-- Check settlement_chain configured
SELECT * FROM settlement_chain;
```

---

## Step 3: Configure Settlement Approvers

### Method A: Via Database
```sql
-- Set specific approvers for annual vacation settlement
UPDATE settlement_chain 
SET approver_id = '5430'  -- Finance Officer
WHERE request_type = 'annual_vacation' AND approval_level = 2;

-- OR keep role-based (approver_id = NULL) and configure in system
```

### Method B: Via App Settings
The system will resolve roles to employee IDs based on:
- `dept_manager` → Employee's department manager
- `finance_officer` → User with Finance role in employees table
- `hr_payroll` → User with HR role in employees table

---

## Step 4: Include Settlement Files

### Add to your vacation/loan approval handlers:

**In all_applied_vac.php or wherever vacation is approved:**
```php
<?php
// After final approval is granted
if ($approvalGranted) {
    require_once 'includes/SettlementManager.php';
    $settlementMgr = new SettlementManager($pdo, $conDB);
    
    $settlementMgr->createSettlement(
        $vacationRecord['inv_no'],           // Invoice number
        'annual_vacation',                   // Type
        $vacationRecord['emp_id'],           // Employee ID
        $vacationRecord['vacdays'] * 350,    // Amount (days × daily rate)
        $currentUserId                       // Created by
    );
}
?>
```

### Add to your loan approval handlers:

**In all_applied_loan.php or wherever loan is approved:**
```php
<?php
// After final approval is granted
if ($approvalGranted) {
    require_once 'includes/SettlementManager.php';
    $settlementMgr = new SettlementManager($pdo, $conDB);
    
    $settlementMgr->createSettlement(
        $loanRecord['inv_no'],              // Invoice number
        'loan_request',                     // Type
        $loanRecord['emp_id'],              // Employee ID
        $loanRecord['loan_amount'],         // Loan amount
        $currentUserId                      // Created by
    );
}
?>
```

---

## Step 5: Create Settlement Management Pages

### Create `settlement_approvals.php` (for approvers):
```php
<?php
session_start();
require_once 'includes/header.php';
require_once 'includes/SettlementManager.php';

$settlementMgr = new SettlementManager($pdo, $conDB);

// Get pending settlements for current user
$pendingSettlements = $settlementMgr->getEmployeeSettlements($_SESSION['emp_id'], 'pending');

?>
<div class="container mt-4">
    <h2>Settlement Approvals</h2>
    <table class="table table-bordered">
        <thead>
            <tr>
                <th>Request</th>
                <th>Employee</th>
                <th>Amount</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($pendingSettlements as $settlement): ?>
            <tr>
                <td><?= htmlspecialchars($settlement['request_inv_no']) ?></td>
                <td><?= htmlspecialchars($settlement['emp_id']) ?></td>
                <td><?= number_format($settlement['settlement_amount'], 2) ?> SAR</td>
                <td><span class="badge badge-warning">Pending</span></td>
                <td>
                    <button class="btn btn-sm btn-success" 
                            onclick="approveSettlement(<?= $settlement['id'] ?>)">
                        Approve
                    </button>
                    <button class="btn btn-sm btn-danger" 
                            onclick="rejectSettlement(<?= $settlement['id'] ?>)">
                        Reject
                    </button>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php require_once 'includes/footer.php'; ?>
```

### Create `settlement_payment.php` (for finance):
```php
<?php
session_start();
require_once 'includes/header.php';
require_once 'includes/SettlementManager.php';

$settlementMgr = new SettlementManager($pdo, $conDB);

// Get approved settlements ready for payment
$approvedSettlements = $settlementMgr->getEmployeeSettlements($_SESSION['emp_id'], 'approved');

?>
<div class="container mt-4">
    <h2>Process Settlement Payments</h2>
    <table class="table table-bordered">
        <thead>
            <tr>
                <th>Request</th>
                <th>Employee</th>
                <th>Amount</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($approvedSettlements as $settlement): ?>
            <tr>
                <td><?= htmlspecialchars($settlement['request_inv_no']) ?></td>
                <td><?= htmlspecialchars($settlement['emp_id']) ?></td>
                <td><?= number_format($settlement['settlement_amount'], 2) ?> SAR</td>
                <td><span class="badge badge-info">Approved</span></td>
                <td>
                    <button class="btn btn-sm btn-primary" 
                            onclick="showPaymentForm(<?= $settlement['id'] ?>)">
                        Process Payment
                    </button>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php require_once 'includes/footer.php'; ?>
```

---

## Step 6: Add JavaScript Integration

Add to your pages that handle settlement approvals:

```html
<!-- Include Settlement Manager JS -->
<script src="assets/js/settlement-manager.js"></script>

<script>
// Approve settlement
function approveSettlement(settlementId) {
    settlementManager.showApproveModal(settlementId);
}

// Reject settlement
function rejectSettlement(settlementId) {
    settlementManager.showRejectModal(settlementId);
}

// Process payment
function showPaymentForm(settlementId) {
    settlementManager.showPaymentModal(settlementId);
}

// Get settlement details
function viewSettlement(settlementId) {
    settlementManager.showSettlementModal(settlementId);
}
</script>
```

---

## Step 7: Test the Workflow

### Test Script:
```php
<?php
// test_settlement.php
require_once 'includes/conn.php';
require_once 'includes/SettlementManager.php';

$settlementMgr = new SettlementManager($pdo, $conDB);

// 1. Create settlement
echo "1. Creating settlement...<br>";
$result = $settlementMgr->createSettlement(
    'VAC-TEST-001',
    'annual_vacation',
    '5160',
    5000.00,
    '5430'
);
var_dump($result);

// 2. Get details
if ($result['success']) {
    echo "2. Getting settlement details...<br>";
    $details = $settlementMgr->getSettlementDetails($result['settlement_id']);
    var_dump($details);
    
    // 3. Approve
    echo "3. Approving settlement...<br>";
    $approve = $settlementMgr->approveSettlement(
        $result['settlement_id'],
        '5430',
        'Approved'
    );
    var_dump($approve);
}
?>
```

---

## Database Columns Added

### emp_vacation table:
- `settlement_status` - Status of settlement for this vacation
- `settlement_amount` - Amount being settled
- `settlement_date` - Date settlement was processed

### emp_loan table:
- `settlement_status` - Status of settlement for this loan
- `settlement_amount` - Amount being settled
- `settlement_date` - Date settlement was processed

---

## Settlement API Endpoints

All endpoints POST to `includes/api/settlement_handler.php`:

```javascript
// Create settlement
POST with: action=create_settlement, request_inv_no, request_type, emp_id, settlement_amount

// Approve settlement
POST with: action=approve_settlement, settlement_id, approver_id, notes

// Reject settlement
POST with: action=reject_settlement, settlement_id, approver_id, reason

// Process payment
POST with: action=process_payment, settlement_id, payment_method, payment_reference

// Get details
POST with: action=get_settlement_details, settlement_id

// Get employee settlements
POST with: action=get_employee_settlements, emp_id, status
```

---

## Troubleshooting

### Settlement not creating
1. Check `settlement_chain` table has configuration for your request type
2. Verify employee exists in database
3. Check error logs: `tail logs/php_error.log`

### Approvers not resolving
1. Verify employees have correct roles assigned
2. Check settlement_chain approver_role matches employee roles
3. Test with specific approver_id instead of role-based

### Settlement not showing in list
1. Check settlement_status in settlement_records
2. Verify approver_id matches current user
3. Query: `SELECT * FROM settlement_records WHERE settlement_status = 'pending'`

### Payment not processing
1. Check all approval levels have status='approved'
2. Verify payment_method is valid (bank_transfer, cash, check)
3. Check payment_reference is provided

---

## Next Steps

1. ✅ Run settlement_implementation.sql
2. ✅ Configure settlement approvers in settlement_chain
3. ✅ Create settlement_approvals.php page
4. ✅ Create settlement_payment.php page
5. ✅ Integrate settlement creation in vacation/loan approval handlers
6. ✅ Test complete workflow
7. ✅ Add settlement notifications to system
8. ✅ Create settlement reports/dashboard
