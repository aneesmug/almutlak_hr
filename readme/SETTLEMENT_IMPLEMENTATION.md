# Settlement System Implementation Guide

## Overview
The Settlement System handles payment processing for completed Annual Vacation and Loan requests after their approval chains are finalized. It provides multi-level approval workflow for payment verification before funds are disbursed.

## Components

### 1. Database Schema
**Tables Created:**
- `settlement_records` - Main settlement payment records
- `settlement_chain` - Settlement approval chain configuration
- `settlement_approvals` - Approval workflow tracking
- Columns added to `emp_vacation` and `emp_loan` tables

**File:** `sql/settlement_implementation.sql`

### 2. Backend Implementation
**File:** `includes/SettlementManager.php`

**Class:** `SettlementManager`
```php
// Initialize
$settlementManager = new SettlementManager($pdo, $conDB);

// Create settlement
$result = $settlementManager->createSettlement(
    'VAC-2026-0001',        // Request invoice number
    'annual_vacation',       // Request type
    '5160',                  // Employee ID
    5000.00,                 // Settlement amount
    '5430'                   // Created by (user ID)
);

// Approve settlement
$result = $settlementManager->approveSettlement(
    $settlementId,           // Settlement ID
    '5430',                  // Current approver ID
    'Approved for payment'   // Notes
);

// Process payment
$result = $settlementManager->processPayment(
    $settlementId,
    'bank_transfer',         // Payment method
    'TRF-REF-123456',       // Payment reference
    '5430'                   // Processed by
);
```

### 3. API Endpoint
**File:** `includes/api/settlement_handler.php`

**Actions:**
- `create_settlement` - Create new settlement record
- `get_settlement_chain` - Get approval chain for request type
- `approve_settlement` - Approve at current level
- `reject_settlement` - Reject settlement
- `process_payment` - Mark as paid
- `get_settlement_details` - Get full settlement info with approvals
- `get_employee_settlements` - Get settlements for employee

**Example:**
```javascript
// Create settlement via API
fetch('./includes/api/settlement_handler.php', {
    method: 'POST',
    body: new URLSearchParams({
        action: 'create_settlement',
        request_inv_no: 'VAC-2026-0001',
        request_type: 'annual_vacation',
        emp_id: '5160',
        settlement_amount: 5000.00
    })
}).then(r => r.json()).then(result => {
    console.log(result);
});
```

### 4. Frontend Implementation
**File:** `assets/js/settlement-manager.js`

**Class:** `SettlementManager`
```javascript
// Global instance available as settlementManager

// Create and show settlement
settlementManager.createSettlement(
    'VAC-2026-0001',
    'annual_vacation',
    '5160',
    5000.00
).then(result => {
    if (result.success) {
        settlementManager.showSettlementModal(result.settlement_id);
    }
});

// Show settlement details modal
settlementManager.showSettlementModal(settlementId);

// Show approval modal
settlementManager.showApproveModal(settlementId);

// Get employee settlements
settlementManager.getEmployeeSettlements(empId, 'pending');
```

## Settlement Workflow

### Step 1: Request Approval Completion
When a vacation or loan request completes its approval chain:
```php
// After final approval in your approval handler
$settlementManager->createSettlement(
    $requestInvNo,
    'annual_vacation',      // or 'loan_request'
    $empId,
    $settlementAmount,
    $currentUserId
);
```

### Step 2: Settlement Approval Chain
Settlement goes through its own approval chain (configurable in app_settings):
- Level 1: Department Manager
- Level 2: Finance Officer
- Level 3: HR Payroll (optional)

Each approver can:
- ✓ Approve → Forwards to next level
- ✗ Reject → Settlement rejected, employee notified
- Add notes/comments

### Step 3: Payment Processing
Once all approvals complete, Finance processes payment:
- Select payment method (Bank Transfer, Cash, Check)
- Enter payment reference
- System marks as settled and updates original request

### Step 4: Record Completion
Settlement marked as "Processed" with payment date and reference.

## Configuration

### Enable Settlement for Request Types
**In app_settings table:**
```
settlement_enable_vacation = 1
settlement_enable_loan = 1
```

### Configure Settlement Approval Chain
**In app_settings table:**
```json
settlement_chain_annual_vacation = [
    {"level": 1, "approver_role": "dept_manager", "approver_id": null},
    {"level": 2, "approver_role": "finance_officer", "approver_id": null},
    {"level": 3, "approver_role": "hr_payroll", "approver_id": null}
]
```

Or via UI in App Settings → Settlement Configuration section.

## Database Queries

### Get pending settlements for approver:
```sql
SELECT sr.*, sa.approval_level 
FROM settlement_records sr
JOIN settlement_approvals sa ON sr.id = sa.settlement_id
WHERE sa.approver_id = '5430' 
  AND sa.approval_status = 'pending'
ORDER BY sr.created_at ASC;
```

### Get settlement history for employee:
```sql
SELECT * FROM settlement_records 
WHERE emp_id = '5160' 
ORDER BY created_at DESC;
```

### Get approval chain for settlement:
```sql
SELECT * FROM settlement_approvals 
WHERE settlement_id = 1 
ORDER BY approval_level ASC;
```

## Integration with Existing Systems

### In Vacation Approval Handler
```php
// When vacation reaches final approval
if ($vacationApproved) {
    require_once 'includes/SettlementManager.php';
    $settlementMgr = new SettlementManager($pdo, $conDB);
    
    // Calculate settlement amount (can be vacation salary or other)
    $settlementAmount = $vacationRecord['vacdays'] * $dailyRate;
    
    // Create settlement
    $settlementMgr->createSettlement(
        $vacationRecord['inv_no'],
        'annual_vacation',
        $vacationRecord['emp_id'],
        $settlementAmount,
        $currentUserId
    );
}
```

### In Loan Approval Handler
```php
// When loan reaches final approval
if ($loanApproved) {
    require_once 'includes/SettlementManager.php';
    $settlementMgr = new SettlementManager($pdo, $conDB);
    
    // Settlement amount is the loan disbursement
    $settlementMgr->createSettlement(
        $loanRecord['inv_no'],
        'loan_request',
        $loanRecord['emp_id'],
        $loanRecord['loan_amount'],
        $currentUserId
    );
}
```

### Display Settlement Status in Request Details
```php
// In vacation/loan detail page
$settlementStatus = getSettlementStatus($requestInvNo);
echo "Settlement Status: " . ucfirst($settlementStatus);

// CSS for status badges
if ($settlementStatus === 'pending') echo '<span class="badge badge-warning">Pending Settlement</span>';
if ($settlementStatus === 'approved') echo '<span class="badge badge-info">Settlement Approved</span>';
if ($settlementStatus === 'settled') echo '<span class="badge badge-success">Settled</span>';
if ($settlementStatus === 'rejected') echo '<span class="badge badge-danger">Settlement Rejected</span>';
```

## Settlement Statuses

| Status | Meaning | Actions Available |
|--------|---------|------------------|
| `pending` | Awaiting first approver | Approve/Reject |
| `approved` | All approvers approved | Process Payment |
| `processed` | Payment completed | View only |
| `rejected` | Rejected by approver | Delete/Retry |
| `cancelled` | Manually cancelled | Archive |

## Error Handling

### Common Errors and Solutions
```php
// Settlement already exists
if (!$result['success'] && strpos($result['message'], 'already exists')) {
    // Show existing settlement instead
}

// User not authorized
if (!$result['success'] && strpos($result['message'], 'not authorized')) {
    // Show error: Only assigned approvers can act
}

// Settlement not approved
if (!$result['success'] && strpos($result['message'], 'not approved')) {
    // Show error: Settlement still in approval workflow
}
```

## Notifications

### Automatic Notifications Sent
- Settlement created → Notify first approver
- Settlement approved (level complete) → Notify next approver
- Settlement rejected → Notify employee and original requestor
- Payment processed → Notify employee

**Example:**
```php
// Send notification to next approver
require_once 'includes/helper_functions.php';
create_and_show_notification(
    $nextApproverId,
    'Settlement Awaiting Approval',
    'Settlement for ' . $empName . ' awaiting your approval: ' . $settlementAmount . ' SAR',
    'settlement_approvals.php?id=' . $settlementId
);
```

## Testing

### Manual Testing Steps
1. Generate completed vacation request
2. After approval, run: `$settlementManager->createSettlement(...)`
3. Verify `settlement_records` table has entry
4. Verify `settlement_approvals` created with proper chain
5. Test approve action as first approver
6. Test forward to next level
7. Test payment processing
8. Verify `emp_vacation.settlement_status` updated

### Sample Test Data
```sql
-- Create test settlement
INSERT INTO settlement_records (request_inv_no, request_type, emp_id, settlement_amount, settlement_status, created_by)
VALUES ('VAC-TEST-001', 'annual_vacation', '5160', 5000.00, 'pending', '5430');

-- Create test approvals
INSERT INTO settlement_approvals (settlement_id, approval_level, approver_id, approval_status)
VALUES 
  (1, 1, '5430', 'pending'),
  (1, 2, '4120', 'pending');
```

## Performance Considerations

- Settlement records indexed by emp_id, created_at, status
- Approval chain cached in app_settings (JSON)
- Large settlements reports use pagination
- Archive old settled records after 1 year

## Future Enhancements

- Email notifications with approval links
- Bulk settlement processing
- Settlement templates for different request types
- Payment schedule for phased disbursements
- Settlement report generation
- Integration with bank APIs for transfer confirmations

## Support

For issues or questions:
1. Check error logs: `logs/php_error.log`
2. Verify app_settings configuration
3. Check settlement_records table status
4. Review settlement_approvals workflow
5. Contact development team
