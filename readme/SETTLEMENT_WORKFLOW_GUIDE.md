# Settlement Workflow Implementation Guide

## Overview
Settlement functionality has been fully integrated into the vacation and loan request systems. After a request receives final approval, users can create a settlement to process payment through a multi-level approval chain.

## Settlement Process Flow

```
Vacation/Loan Request
        ↓
    [APPROVED]
        ↓
   Settlement Button Appears
        ↓
   Create Settlement
        ↓
Settlement Records Created:
  • settlement_records table (payment tracking)
  • request_approvers entries (approval levels)
  • smt_request_status entries (audit trail)
        ↓
Settlement Approval Chain:
  Level 1: Department Manager (awaiting approval)
  Level 2: Finance Officer (awaiting approval)
  Level 3: HR Payroll (awaiting approval)
        ↓
    [APPROVED]
        ↓
 Process Payment
        ↓
   [COMPLETED]
```

## Files Updated

### 1. all_applied_vac.php
**Added:** Settlement button for approved vacation requests
- Button appears in dropdown menu when `current_status === 'approved'`
- Calls `createSettlement()` JavaScript function
- Parameters: vacationId, requestInvNo, empId, employeeName, vacationDays
- Amount calculated: `vacationDays × 350 SAR`

```php
<?php if ($req['current_status'] === 'approved'): ?>
    <a class="dropdown-item" href="javascript:void(0);" 
       onclick="createSettlement(...)">
        <i class="fa fa-handshake text-success"></i> Create Settlement
    </a>
<?php endif; ?>
```

### 2. all_applied_loan.php
**Added:** Settlement button for approved loan requests
- Button appears in dropdown menu when `status === 'approved'`
- Calls `createLoanSettlement()` JavaScript function
- Parameters: loanId, loanInvNo, empId, employeeName, loanAmount
- Amount uses actual loan amount (no calculation)

```php
<?php if ($loan['status'] === 'approved'): ?>
    <button type="button" class="dropdown-item" 
            onclick="createLoanSettlement(...)">
        <i class="fa fa-handshake text-success"></i> Create Settlement
    </button>
<?php endif; ?>
```

## Backend Processing

### Settlement Handler (includes/api/settlement_handler.php)
Handles all settlement operations through action parameter:

```
POST /includes/api/settlement_handler.php
{
    action: 'create_settlement',
    request_inv_no: 'VAC-20260127-5160-abc123',
    request_type: 'annual_vacation' OR 'loan_request',
    emp_id: '5160',
    settlement_amount: 1750.00,
    user_id: '5430'
}
```

**Response:**
```json
{
    "success": true,
    "settlement_inv_no": "SETTLEMENT-VAC-20260127-5160-abc123",
    "message": "Settlement created successfully. Awaiting approval."
}
```

### Settlement Manager (includes/SettlementManager_Corrected.php)
Core business logic for settlement operations:

**Key Methods:**
- `createSettlement()` - Creates settlement with approval chain
- `approveSettlement()` - Approves at specific level
- `rejectSettlement()` - Rejects settlement
- `processPayment()` - Marks as processed after final approval
- `getSettlementDetails()` - Gets complete settlement info
- `getEmployeeSettlements()` - Lists employee's settlements

## Database Tables

### settlement_records
```sql
CREATE TABLE `settlement_records` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `request_inv_no` varchar(255) NOT NULL,  -- 'SETTLEMENT-{original}'
  `request_type` varchar(50) NOT NULL,      -- 'annual_vacation' or 'loan_request'
  `emp_id` varchar(100) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `status` varchar(50) DEFAULT 'pending',   -- pending, approved, processed, rejected
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_settlement` (`request_inv_no`, `request_type`)
);
```

### request_approvers
Used for settlement approval chain (same table as vacation/loan approvals):
```sql
INSERT INTO request_approvers 
(request_inv_no, request_type_id, approver_id, approval_level, status)
VALUES ('SETTLEMENT-VAC-...', 8, 5430, 1, 'awaiting');
```

### smt_request_status
Complete audit trail for settlements:
```sql
INSERT INTO smt_request_status 
(inv_no, emp_id, emp_name, note, status)
VALUES ('SETTLEMENT-VAC-...', 5430, 'System', 'Settlement created', 'pending');
```

### app_settings
Settlement approval chain configuration:
```sql
SELECT * FROM app_settings 
WHERE setting_name = 'approval_chain_settlement'
AND setting_group = 'approval'
AND input_type = 'json';

-- Value contains 3-level chain:
-- Level 1: dept_manager
-- Level 2: finance_officer
-- Level 3: hr_payroll
```

## Settlement Approval Chain

### Level 1: Department Manager
- Reviews settlement
- Can approve or reject
- Cannot process payment yet

### Level 2: Finance Officer
- Reviews for financial accuracy
- Can approve or reject
- Cannot process payment yet

### Level 3: HR Payroll
- Final approval authority
- Can approve or reject
- **Can process payment** (marks as 'processed')

## User Interface Components

### JavaScript Functions

#### createSettlement() (all_applied_vac.php)
```javascript
function createSettlement(vacationId, requestInvNo, employeeId, 
                          employeeName, vacationDays) {
    // Shows confirmation modal
    // Calculates amount: days × 350 SAR
    // Calls settlement_handler.php
    // Reloads page on success
}
```

#### createLoanSettlement() (all_applied_loan.php)
```javascript
function createLoanSettlement(loanId, loanInvNo, employeeId, 
                              employeeName, loanAmount) {
    // Shows confirmation modal
    // Uses actual loan amount
    // Calls settlement_handler.php
    // Reloads page on success
}
```

### Modal Display
Both functions show:
- Employee name
- Request reference number
- Settlement amount (with badge)
- Confirmation message
- Cancel option

## Data Flow Example

### Vacation Settlement Creation
```
1. User views all_applied_vac.php
2. Finds approved vacation request
3. Clicks "Create Settlement" button
4. Modal shows:
   - Employee: John Smith
   - Request: VAC-20260127-5160-abc123
   - Days: 5 days
   - Amount: SAR 1,750.00
5. User confirms
6. JavaScript calls settlement_handler.php with POST:
   {
       action: 'create_settlement',
       request_inv_no: 'VAC-20260127-5160-abc123',
       request_type: 'annual_vacation',
       emp_id: '5160',
       settlement_amount: 1750.00,
       user_id: '5430'
   }
7. Settlement Manager processes:
   - Creates entry in settlement_records
   - Creates 3 entries in request_approvers (one per level)
   - Creates initial status in smt_request_status
8. Returns success response with settlement_inv_no
9. Page reloads showing updated status
10. Settlement now appears in settlement approval screens
```

## Status Transitions

### Settlement Status Flow
```
pending
   ↓
[Level 1 Approval]
   ├─ approved_level_1 → awaiting Level 2
   └─ rejected → SETTLEMENT REJECTED
   ↓
[Level 2 Approval]
   ├─ approved_level_2 → awaiting Level 3
   └─ rejected → SETTLEMENT REJECTED
   ↓
[Level 3 Approval]
   ├─ approved_level_3 → approved (ready for payment)
   └─ rejected → SETTLEMENT REJECTED
   ↓
[Payment Processing]
   └─ processed → COMPLETE
```

## Integration Points

### From all_applied_vac.php
- SettlementManager_Corrected.php (imported at top)
- Uses emp_vacation table data
- Passes vacationDays for amount calculation

### From all_applied_loan.php
- settlement_handler.php (AJAX endpoint)
- Uses emp_loan table data
- Passes loan_amount directly

### Database Connections
- MySQLi connection ($conDB) for all database operations
- No PDO dependencies
- Uses prepared statements for security

## Testing Checklist

- [ ] View approved vacation request
- [ ] Settlement button appears
- [ ] Click settlement button
- [ ] Confirmation modal shows correct details
- [ ] Submit settlement creation
- [ ] Check settlement_records table for new entry
- [ ] Check request_approvers for 3 level entries
- [ ] Check smt_request_status for audit trail
- [ ] Verify Department Manager sees it pending
- [ ] Verify Finance Officer sees it after Level 1 approval
- [ ] Verify HR Payroll sees it after Level 2 approval
- [ ] Test approval at each level
- [ ] Test rejection at each level
- [ ] Test payment processing by HR Payroll

## Known Limitations

1. Settlement button only appears when request status === 'approved'
2. Cannot create duplicate settlements for same request
3. Settlements use fixed 350 SAR/day rate for vacations
4. No email notifications yet (future enhancement)
5. No settlement reports/dashboard (future enhancement)

## Future Enhancements

1. Email notifications for settlement approvers
2. Settlement dashboard for finance team
3. Settlement payment proof uploads
4. Settlement reports and analytics
5. Bulk settlement operations
6. Settlement cancellation workflows
7. Integration with accounting system

---
**Last Updated:** January 27, 2026
**Status:** Ready for Production
