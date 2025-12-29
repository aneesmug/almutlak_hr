# Finance Payer Workflow Integration - Complete

## Overview
The new Finance Manager → Finance Payer workflow has been successfully integrated into the Al-Mutlak WMS system. This enables Finance Managers to select finance employees as payers during approval, with those payers then recording payment amounts and proof.

## Changes Made

### 1. ApprovalChainManager.php (New Methods Added)

Three new methods have been added to the `ApprovalChainManager` class:

#### `approveWithPayerSelection()`
- **Purpose**: Handles Finance Manager approval with payer selection
- **Parameters**:
  - `$requestInvNo`: Request invoice number
  - `$financeManagerId`: Finance Manager's employee ID
  - `$selectedPayerId`: Selected finance employee (payer) ID
  - `$note`: Optional approval note
- **Returns**: Array with status and payer details
- **Key Features**:
  - Marks Finance Manager approval as complete
  - Creates new payer entry in request_approvers with `approval_level >= 100`
  - Sets payer status to 'awaiting' for payment recording
  - Returns payer details for notification

#### `recordPayerPayment()`
- **Purpose**: Records payment amount and proof by finance payer
- **Parameters**:
  - `$requestInvNo`: Request invoice number
  - `$payerId`: Finance payer's employee ID
  - `$approvedAmount`: Payment amount
  - `$paymentProof`: Payment proof/receipt reference
  - `$paymentMethod`: Payment method (e.g., 'bank_transfer', 'file_upload')
- **Returns**: Array with payment status and next approver details
- **Key Features**:
  - Verifies payer authorization using approval_level >= 100
  - Stores payment details in request_approvers.note field
  - Updates payer status to 'approved'
  - Checks for additional approval levels and returns next approver
  - Marks as final if no more levels exist

#### `getFinancePayer()`
- **Purpose**: Retrieves currently assigned finance payer for a request
- **Parameters**:
  - `$requestInvNo`: Request invoice number
- **Returns**: Payer details array or null if not assigned
- **Key Features**:
  - Fetches payer from request_approvers table
  - Includes payer status and payment details from note field
  - Joins with admin_login and employees tables for contact info

---

### 2. ajaxLoan.php (Finance Manager Payer Selection)

#### Changes to Finance Manager Approval Flow (Lines ~395-455)

**Before**: Manually added payer to request_approvers with level+1

**After**: Uses new `approveWithPayerSelection()` method
```php
// Finance Manager payer selection
if ($is_finance_manager) {
    // Validate payer selection
    $payer_emp_id = (int)$_POST['payer_emp_id'];
    
    // Use ApprovalChainManager to handle payer selection
    $payerSelectionResult = $chainManager->approveWithPayerSelection(
        $inv_no,
        $approver_emp_id,
        $payer_emp_id,
        $approval_comment
    );
    // Fallback to manual addition if chain manager fails
}
```

**Benefits**:
- Centralized payer management
- Consistent approval chain tracking
- Automatic payer notification

#### Changes to Payer Payment Recording (Lines ~375-410)

**Before**: Only updated emp_loan table with payment proof

**After**: Uses new `recordPayerPayment()` method
```php
// If payer is approving, record payment via chain manager
if ($is_payer) {
    $paymentResult = $chainManager->recordPayerPayment(
        $inv_no,
        $approver_emp_id,
        $final_approved_amount,
        $payment_proof_filename,
        'file_upload'
    );
    
    // If payment is final, update main emp_loan status to 'paid'
    if ($paymentResult['is_final']) {
        // Update status
    }
}
```

**Benefits**:
- Payment details stored in centralized request_approvers table
- Automatic forwarding to next approvers if needed
- Consistent payment tracking across all request types

---

### 3. ajaxVacation.php (Rejoin Request Finance Manager Support)

#### Changes to Rejoin Approval Handler (Lines ~4165-4250)

**New Feature**: Finance Manager can now select a payer for rejoin requests

```php
// Check if current user is Finance Manager
$is_finance_manager = ($user_type_row && $user_type_row['user_type'] == 'finance');

if ($action === 'approve') {
    // Check if Finance Manager is selecting a payer
    if ($is_finance_manager && isset($_POST['payer_emp_id'])) {
        // Use approveWithPayerSelection() method
        $payerSelectionResult = $chainManager->approveWithPayerSelection(
            $request['request_inv_no'],
            $current_user_id,
            $payer_emp_id,
            $approval_note
        );
        
        // Update rejoin_requests with payer assignment
        // Notify payer of assignment
    } else {
        // Normal approval flow (unchanged)
    }
}
```

**Benefits**:
- Rejoin requests can now require payment processing
- Finance Managers can assign payers directly in approval flow
- Payers notified automatically of assignment
- Maintains normal approval chain for non-Finance-Manager approvers

---

## How It Works

### Loan Request Finance Payer Workflow

1. **Loan Submission**: Employee submits loan request → Approval chain created
2. **Initial Approvals**: Department Manager/HR approves → Chain advances
3. **Finance Manager Approval**:
   - Finance Manager receives request
   - Reviews and selects finance employee as payer
   - Calls `approveWithPayerSelection()`:
     - Marks FM approval as complete
     - Creates payer entry with level >= 100
     - Sends notification to payer
4. **Payer Payment Recording**:
   - Finance payer receives notification
   - Uploads payment proof and confirms amount
   - Calls `recordPayerPayment()`:
     - Verifies payer authorization
     - Records amount + proof in request_approvers.note
     - Marks as complete
     - Forwards to next approvers if needed
5. **Final Completion**: All approvals done → Loan status → 'paid'

### Data Storage

**request_approvers table**:
- `approver_id`: Finance payer's employee ID
- `approval_level`: >= 100 (distinguishes payers from regular approvers)
- `status`: 'awaiting' → 'approved'
- `note`: Stores "Payment Amount: X.XX | Proof: filename | Method: method"

**emp_loan table**:
- `payer_emp_id`: Assigned payer's employee ID
- `final_approved_amount`: Confirmed payment amount
- `payment_proof_file`: Uploaded proof filename
- `status`: 'pending' → 'paid'

---

## Testing Checklist

- [ ] Finance Manager can approve loan request and select payer
- [ ] Payer receives notification of assignment
- [ ] Payer can upload payment proof and amount
- [ ] Payment details stored in request_approvers.note
- [ ] Loan status updated to 'paid' when payer completes payment
- [ ] Finance Manager can assign payer for rejoin requests
- [ ] Payer can record payment for rejoin requests
- [ ] Normal approval chain still works for non-Finance roles
- [ ] Error handling works (invalid payer, missing proof, etc.)
- [ ] Activity logging captures payer selection and payment recording
- [ ] Notifications sent to all relevant parties

---

## Database Considerations

### Existing Tables (No Changes Needed)
- `request_approvers`: Uses existing structure, distinguishes payers via approval_level >= 100
- `emp_loan`: Uses existing payer_emp_id, final_approved_amount columns
- `rejoin_requests`: New optional payer_emp_id column may be added for reference

### New/Modified Columns (Optional Enhancement)
For future enhancement, consider adding to rejoin_requests:
```sql
ALTER TABLE rejoin_requests ADD COLUMN payer_emp_id INT DEFAULT NULL;
ALTER TABLE rejoin_requests ADD COLUMN payer_assignment_date DATETIME;
```

---

## Fallback Mechanism

Both loan and vacation handlers include try-catch blocks with fallback logic:

1. **Primary**: Use ApprovalChainManager methods
2. **Fallback**: Manual database insertion if chain manager fails
3. **Logging**: All attempts logged for debugging

This ensures system continues working even if new methods have issues.

---

## Integration Points

### Files Modified
1. `includes/ApprovalChainManager.php` - Added 3 new methods
2. `includes/ajaxFile/ajaxLoan.php` - Finance Manager payer selection + payer payment
3. `includes/ajaxFile/ajaxVacation.php` - Rejoin request Finance Manager support

### Backward Compatibility
- All changes are additive
- Existing approval flows unchanged
- Normal approvers (non-Finance) not affected
- Finance Manager payer selection optional per request type

---

## Future Enhancements

1. **Frontend UI**: Modal for Finance Manager to select payer with search/filter
2. **Payment Dashboard**: Track all payments pending by payer
3. **Batch Payer Assignment**: Allow Finance Manager to assign same payer to multiple requests
4. **Payment Reconciliation**: Link payment proofs to bank statements
5. **Multi-Payer Support**: Allow request to be split between multiple payers
6. **Payer Performance Metrics**: Track payer approval times and quality

---

## Support & Maintenance

For questions or issues with the Finance Payer workflow:
1. Check error logs for specific failures
2. Verify request_approvers table for payer entries (approval_level >= 100)
3. Confirm payer user_type is set correctly in admin_login table
4. Test with sample loan request through complete flow

---

**Status**: ✅ Integration Complete
**Date**: December 24, 2025
**Version**: 1.0
