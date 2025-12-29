# Two-Step HR Payroll Workflow: Payment + Approval
**Implementation Date**: January 6, 2025

---

## Overview
The HR Payroll approval process has been refactored to separate **Payment Processing** from **Vacation Approval** into two distinct steps. This prevents HR Payroll from being stuck approving twice and provides a cleaner workflow.

### Previous Problem
- HR Payroll appeared twice in the approval chain
- Users had to click "approve" twice for the same request (confusing)
- Payment details were mixed with approval logic

### New Solution
- **Step 1**: HR Payroll **Processes Payment** (marks as "paid")
- **Step 2**: HR Payroll **Approves Vacation** (marks as "approved")
- Clear separation of concerns with distinct buttons in the UI

---

## Database Changes

### New Fields in `emp_vacation` Table
```sql
ALTER TABLE emp_vacation ADD COLUMN payment_status ENUM('pending_payment', 'paid', 'needs_modification') DEFAULT 'pending_payment';
ALTER TABLE emp_vacation ADD COLUMN payment_date DATETIME NULL;
ALTER TABLE emp_vacation ADD COLUMN payment_modified_date DATETIME NULL;
ALTER TABLE emp_vacation ADD COLUMN payment_modified_by VARCHAR(50) NULL;
ALTER TABLE emp_vacation ADD COLUMN is_payment_completed TINYINT(1) DEFAULT 0;
```

**Field Descriptions**:
- `payment_status`: Tracks the payment state of the request
  - `pending_payment`: Initial state, awaiting payment processing
  - `paid`: Payment has been processed
  - `needs_modification`: Payment flagged for modification by HR
- `payment_date`: Timestamp when payment was marked as "paid"
- `payment_modified_date`: Timestamp of last payment modification
- `payment_modified_by`: Employee ID of the user who modified payment
- `is_payment_completed`: Flag (0/1) for quick status check

---

## API Endpoints Added to `ajaxVacation.php`

### 1. `processPayment` Endpoint
**Purpose**: HR Payroll marks vacation payment as "paid"

**Request**:
```javascript
{
    ajaxType: 'processPayment',
    vacation_id: <int>,
    request_inv_no: '<string>'
}
```

**Response**:
```json
{
    "status": "success",
    "title": "Payment Processed!",
    "message": "Payment has been recorded. You can now approve the vacation request.",
    "type": "success"
}
```

**Authorization**: Only `user_type = 'hr_payroll'` can call this endpoint

**Actions**:
1. Validates current user is HR Payroll
2. Verifies vacation status is "approved" or "pending_approval"
3. Sets `payment_status = 'paid'` and `is_payment_completed = 1`
4. Records `payment_date = NOW()`
5. Logs action in ActivityLogger

**Error Cases**:
- "Only HR Payroll can process payments"
- "Vacation must be approved or pending approval before payment"
- Database update failures

---

### 2. `modifyPayment` Endpoint
**Purpose**: HR Payroll marks payment as "needs_modification" if something is wrong

**Request**:
```javascript
{
    ajaxType: 'modifyPayment',
    vacation_id: <int>,
    payment_note: '<string>'
}
```

**Response**:
```json
{
    "status": "success",
    "title": "Payment Modification Recorded!",
    "message": "Payment has been marked for modification. Please review and update as needed.",
    "type": "success"
}
```

**Authorization**: Only `user_type = 'hr_payroll'` can call this endpoint

**Actions**:
1. Validates current user is HR Payroll
2. Sets `payment_status = 'needs_modification'`
3. Records `payment_modified_date = NOW()` and `payment_modified_by = <empid>`
4. Appends note to `payroll_note` field with "[PAYMENT MODIFICATION]" header
5. Logs action in ActivityLogger

**Use Case**: When HR Payroll needs employee to fix payment details (e.g., wrong bank account, fee amount incorrect)

---

## Modified Endpoints

### `approveVacation` Endpoint (Enhanced)
**New Validation** (Line 1140-1153 in ajaxVacation.php):
```php
// If HR Payroll is approving, verify payment has been completed
if ($user_role === 'hr_payroll' && $is_payment_completed !== 1) {
    throw new Exception("Payment must be processed before approval. Please process the payment first.");
}
```

**Effect**: Prevents HR Payroll from approving vacation if payment hasn't been marked as "paid"

---

## Frontend Changes

### `all_applied_vac.php` - New UI Buttons

#### PHP Logic (Lines 629-656)
```php
<?php 
    // Check if current user is HR Payroll and payment not completed
    $is_hr_payroll = ($user_type === 'hr_payroll');
    $show_process_payment_button = false;
    $show_approve_payment_button = false;
    
    if ($req['current_status'] == 'approved' && $req['vac_type'] == 'Fly' && $req['fly_type'] == 'annual') {
        if ($is_hr_payroll) {
            $payment_status = $req['payment_status'] ?? 'pending_payment';
            if ($payment_status === 'pending_payment') {
                $show_process_payment_button = true;
            } elseif ($payment_status === 'paid') {
                $show_approve_payment_button = true;
            }
        }
    }
?>
```

**Buttons Displayed**:

1. **For HR Payroll with `payment_status = 'pending_payment'`**:
   ```html
   <a class="dropdown-item" onclick="processPayment(...)">
       <i class="fa fa-money-bill text-warning"></i> <strong>Process Payment</strong>
   </a>
   ```
   - Only appears when payment hasn't been processed yet
   - Single click to mark payment as "paid"

2. **For HR Payroll with `payment_status = 'paid'`**:
   ```html
   <a class="dropdown-item" onclick="approveVacationPayment(...)">
       <i class="fa fa-thumbs-up text-success"></i> <strong>Approve After Payment</strong>
   </a>
   ```
   - Only appears AFTER payment has been processed
   - Opens full approval modal for vacation

3. **For non-HR Payroll Users**:
   - Neither button appears
   - Only HR Payroll sees payment/approval buttons

---

## JavaScript Functions Added

### `processPayment(vacationId, requestInvNo, employeeName)`
**Location**: `all_applied_vac.php`, Lines 1861-1918

**Flow**:
1. Shows confirmation dialog with:
   - Request reference number
   - Employee name
   - Note about next steps
2. On confirmation:
   - Shows loading modal
   - Calls `processPayment` AJAX endpoint
   - Reloads page on success to update buttons
3. On error:
   - Displays error message from server

**UI Features**:
- Yellow warning icon for payment processing
- Info box explaining the process
- Smooth loading state

### `approveVacationPayment(vacationId, requestInvNo, employeeName)`
**Location**: `all_applied_vac.php`, Lines 1920-1946

**Flow**:
1. Fetches vacation details via `getVacationDetails` AJAX
2. Calls `approveRequest()` function with:
   - Role set to `'hr_payroll'`
   - Current approval level set to final level (3)
3. Opens the standard approval modal where HR Payroll can:
   - Enter overtime/deduction adjustments
   - Add approval comment
   - View payroll calculations
   - Finally approve the vacation

**Connection**: Reuses existing approval modal infrastructure

---

## Workflow Diagram

```
┌─────────────────────────────────────────────────────────────────┐
│ ANNUAL VACATION APPROVAL CHAIN (with new payment step)          │
└─────────────────────────────────────────────────────────────────┘

Department Manager
       ↓
    [Approves]
       ↓
HR Senior BP + Asset Clearance Teams
       ↓
    [Approves]
       ↓
       HR PAYROLL (NEW TWO-STEP PROCESS)
       ├─ Step 1: Process Payment
       │  ├─ Button: "Process Payment"
       │  ├─ Action: Marks as payment_status='paid'
       │  └─ Result: Button changes to "Approve After Payment"
       │
       └─ Step 2: Approve Vacation
          ├─ Button: "Approve After Payment"
          ├─ Check: Verify payment_completed=1 (enforced in backend)
          ├─ Action: Standard approval with payroll adjustments
          └─ Result: Status → "approved" → Email sent → Status → "complete"

```

---

## Status Flow for Annual Vacations

```
Draft
  ↓
Pending Approval (through manager chain)
  ↓
Approved (by HR Senior BP)
  ↓
[HR PAYROLL TAKES OVER]
  │
  ├─ Payment Status: pending_payment
  │  └─ Button: Process Payment [Click to mark as paid]
  │
  └─ Payment Status: paid
     └─ Button: Approve After Payment [Click to open approval modal]
       └─ After Approval Modal:
          └─ Status → Approved → Complete (with email)
```

---

## Test Scenarios

### Scenario 1: Happy Path (Normal Approval)
1. ✓ Employee submits annual vacation → Status: `pending_approval`
2. ✓ Department Manager approves → Status: Still `pending_approval`
3. ✓ HR Senior BP approves → Status: `approved`
4. ✓ HR Payroll processes payment → Status: `approved`, `payment_status = paid`
5. ✓ HR Payroll approves → Status: `completed`, Email sent

### Scenario 2: Payment Modification
1. ✓ Payment processed → `payment_status = paid`
2. ✓ HR Payroll notices issue (e.g., wrong fee)
3. ✓ Modifies payment → `payment_status = needs_modification`
4. ✓ System shows modification note in payroll_note field
5. ✓ HR Payroll corrects issue and re-processes

### Scenario 3: Payment Not Yet Processed
1. ✓ HR Payroll tries to approve without processing payment first
2. ✗ Backend rejects: "Payment must be processed before approval"
3. ✓ User must first click "Process Payment" button

### Scenario 4: Non-HR Payroll Users
1. ✓ Department Manager sees vacation in pending queue
2. ✓ Cannot see "Process Payment" or "Approve After Payment" buttons
3. ✓ Only HR Payroll role shows these payment-related buttons

---

## Configuration & Localization

### Required Language Keys (in translation file)
```php
__('process_payment')           => 'Process Payment'
__('approve_after_payment')     => 'Approve After Payment'
__('process_payment_confirm')   => 'Are you ready to process payment for'
__('payment_process_note')      => 'Processing payment will mark...'
__('payment_processing_error')  => 'Error processing payment'
__('pending_payment')           => 'Pending Payment'
```

If not defined, default English messages will display.

---

## Logging & Audit Trail

### ActivityLogger Entries
1. **Payment Processing**: 
   ```
   "Payment processed by HR Payroll - Request XXXXX"
   ```
2. **Payment Modification**:
   ```
   "Payment marked for modification by HR Payroll - Note: ..."
   ```
3. **Approval After Payment** (existing):
   ```
   "Approved vacation request: XXXXX"
   ```

### Timestamps Recorded
- `payment_date`: When payment marked as "paid"
- `payment_modified_date`: When payment marked for modification
- `created_at`: Original request submission (unchanged)
- `updated_at`: (If tracking in future) Last status change

---

## Database Migration

**File**: `d:/xampp/htdocs/almutlak/system/db_updates/20250106_add_payment_workflow_fields.sql`

**Migration Status**: ✓ COMPLETE (All columns already exist in database)

**Verification**:
```bash
DESCRIBE emp_vacation;
```

Look for columns:
- `payment_status`
- `payment_date`
- `payment_modified_date`
- `payment_modified_by`
- `is_payment_completed`

---

## Files Modified

1. **`includes/ajaxFile/ajaxVacation.php`**:
   - Added `processPayment` endpoint (Lines 1402-1460)
   - Added `modifyPayment` endpoint (Lines 1462-1519)
   - Enhanced `approveVacation` to check payment status (Lines 1140-1153)

2. **`all_applied_vac.php`**:
   - Added payment button logic (Lines 629-656)
   - Added conditional button rendering (Lines 657-670)
   - Added `processPayment()` JS function (Lines 1861-1918)
   - Added `approveVacationPayment()` JS function (Lines 1920-1946)

---

## Future Enhancements

1. **Payment History**: Create separate `vacation_payment_history` table to track all payment changes
2. **Payment Report**: Add report showing all payments processed by HR Payroll with dates
3. **Batch Payment**: Ability to process multiple vacation payments at once
4. **Payment Notifications**: Email traveler when payment is processed
5. **Expense Tracking**: Link payments to accounting/expense system

---

## Support & Troubleshooting

### Issue: "Payment must be processed before approval"
**Cause**: HR Payroll tried to approve vacation without processing payment first
**Solution**: Click "Process Payment" button first, then "Approve After Payment"

### Issue: Payment button not showing for HR Payroll
**Cause**: User may not have `user_type = 'hr_payroll'` in admin_login table
**Solution**: Check admin_login.user_type = 'hr_payroll' for the user

### Issue: Database columns don't exist
**Cause**: Migration not run or columns were dropped
**Solution**: Run migration script: `php db_updates/run_migration.php`

---

## Rollback Instructions (if needed)

To remove payment workflow and revert to old approval:
1. Delete payment-related columns from emp_vacation
2. Revert changes to ajaxVacation.php
3. Revert UI changes in all_applied_vac.php
4. Update approval chain to not include HR Payroll twice

**Note**: This is NOT recommended as the old workflow was causing the original issue.

---

**Implementation Complete**: ✓
**Ready for Testing**: ✓
**Documentation**: ✓
