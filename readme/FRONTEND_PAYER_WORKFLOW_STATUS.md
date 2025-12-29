# Frontend JavaScript Files - Finance Payer Workflow Status

## Summary
The frontend JavaScript files have **PARTIAL** updates for the finance payer workflow. The **Loan Approval** flow is fully updated, but the **Rejoin/Vacation Approval** flow needs to be updated with Finance Manager payer selection UI.

---

## ✅ COMPLETE - Loan Request Approval (loan_approval.js)

### File: [assets/js/loan_approval.js](assets/js/loan_approval.js)

**Status**: ✅ UPDATED & COMPLETE

**Key Function**: `approveLoanRequest(loanId, role, requestedAmount, userType, approvalLevel, payerEmpId, currentUserId)`

#### Finance Manager Payer Selection (Lines 113-200)
```javascript
} else if (isFinanceManager) {
    // Show payer selection modal for Finance Manager
    $.ajax({
        url: './includes/ajaxFile/ajaxLoan.php',
        type: 'POST',
        data: { ajaxType: 'get_finance_staff' },
        dataType: 'JSON',
    }).done(function(staffResponse) {
        // Modal with payer selection dropdown
        // Includes:
        // - Payer employee selection
        // - Requested amount display
        // - Optional approval comment
        // - Character counter for comment
        
        // AJAX submit to: ajaxType: 'approve_loan'
        // Data includes: payer_emp_id, approval_comment
    });
}
```

#### Payer Payment Recording (Lines 17-112)
```javascript
if (isPayer) {
    // Show payment proof upload modal for assigned payer
    Swal.fire({
        // Modal includes:
        // - Final approved amount input
        // - Payment proof file upload (PDF, JPG, PNG, DOC, DOCX)
        // - Payment notes textarea
        // - Character counter
        
        // AJAX submit to: ajaxType: 'approve_loan'
        // Data includes: final_approved_amount, payment_proof file
    });
}
```

**Features Implemented**:
- ✅ Detects Finance Manager role (userType === 'finance')
- ✅ Fetches available finance staff via AJAX
- ✅ Shows payer selection dropdown
- ✅ Validates payer selection required
- ✅ Sends payer_emp_id to backend
- ✅ Payer payment proof upload
- ✅ File validation (only PDF, JPG, PNG, DOC, DOCX)
- ✅ Amount validation
- ✅ Character counter for notes
- ✅ Error handling and messages

**Integration**: ✅ Fully integrated with `approveWithPayerSelection()` and `recordPayerPayment()`

---

## ⚠️ NEEDS UPDATE - Rejoin Request Approval

### File: [rejoin_approvals.php](rejoin_approvals.php)

**Status**: ⚠️ PARTIAL - Missing Finance Manager payer selection

**Current Function**: `viewAndApproveRequest(rejoinRequestId, empId, rejoinDate, empName, vacationType)`

#### Current Implementation (Lines 455-620)
```javascript
function viewAndApproveRequest(rejoinRequestId, empId, rejoinDate, empName, vacationType) {
    Swal.fire({
        // Current modal shows:
        // - Employee info
        // - Vacation type and date
        // - Action selection (Approve/Adjust/Reject)
        // - Conditional fields based on action
        
        // MISSING: Finance Manager payer selection UI
    });
}
```

**Missing Features**:
- ❌ No Finance Manager role detection
- ❌ No payer selection dropdown
- ❌ No payer assignment workflow
- ❌ No payer email parameter sent

**Required Changes**:
1. Detect if approver is Finance Manager
2. Fetch list of finance staff
3. Add payer selection dropdown when Finance Manager approves
4. Send `payer_emp_id` parameter to `processRejoinApproval`

---

## ⚠️ NEEDS UPDATE - Vacation Approval

### File: [includes/ajaxFile/ajaxVacation.php - approveVacation function]

**Status**: ⚠️ PARTIAL - Uses generic `handle_approval_action()`

**Current Flow**: Uses existing `handle_approval_action()` function which doesn't have Finance Manager payer support yet.

**Location**: Lines 1093-1400 in ajaxVacation.php

**Current Issues**:
- ❌ No Finance Manager detection in vacation approvals
- ❌ No payer selection workflow
- ❌ Generic approval flow doesn't support payer assignment

---

## Implementation Roadmap

### IMMEDIATE - Update Rejoin Approvals (rejoin_approvals.php)

Add Finance Manager payer selection UI to `viewAndApproveRequest()`:

```javascript
function viewAndApproveRequest(rejoinRequestId, empId, rejoinDate, empName, vacationType) {
    // 1. Get current user's user_type from global or session
    // 2. Check if Finance Manager (user_type === 'finance')
    // 3. If Finance Manager, add payer selection UI:
    //    - Fetch finance staff list
    //    - Add payer dropdown to modal
    //    - Add payer selection to form data
    
    // 4. Send payer_emp_id to processRejoinApproval if selected
}
```

### PRIORITY 1 - Update Rejoin Payment Recording

Add UI for finance payer to record payment for rejoin requests in rejoin_approvals.php:

```javascript
// Add new function for payer payment recording
function recordRejoinPayment(rejoinRequestId, payerId, requestAmount) {
    // Similar to loan payment recording:
    // - Final amount input
    // - Payment proof upload
    // - Payment notes
    // - Submit to AJAX handler
}
```

### PRIORITY 2 - Update Vacation Approvals (if needed)

Extend vacation approval modal to support Finance Manager payer selection if vacation requests require payment processing.

---

## Testing Checklist - Frontend

### Loan Approval ✅
- [x] Finance Manager sees payer selection dropdown
- [x] Non-Finance roles don't see payer UI
- [x] Payer selection is required
- [x] Finance Manager can submit with payer selected
- [x] Assigned payer sees payment upload form
- [x] Payer can upload proof and amount
- [x] Form validation works (file, amount)

### Rejoin Approval ❌ 
- [ ] Finance Manager sees payer selection dropdown
- [ ] Payer dropdown populated with finance staff
- [ ] Finance Manager can submit with payer selected
- [ ] Backend receives payer_emp_id
- [ ] Payer receives notification
- [ ] Payer can record payment (UI needs to be added)

### Vacation Approval ❌
- [ ] Finance Manager can select payer (if needed)
- [ ] Payer assignment works
- [ ] Integration with new methods works

---

## Backend Methods - Frontend Integration Status

| Method | File | Frontend JS | Status | Notes |
|--------|------|-------------|--------|-------|
| `approveWithPayerSelection()` | ApprovalChainManager.php | loan_approval.js | ✅ Complete | Loan FM payer selection integrated |
| | | rejoin_approvals.php | ❌ Missing | Needs UI implementation |
| | | vacation approvals | ❌ Missing | Not implemented |
| `recordPayerPayment()` | ApprovalChainManager.php | loan_approval.js | ✅ Complete | Loan payer payment integrated |
| | | rejoin_approvals.php | ❌ Missing | Needs UI implementation |
| | | vacation approvals | ❌ Missing | Not implemented |
| `getFinancePayer()` | ApprovalChainManager.php | - | - | Backend-only method |

---

## Files That Need Updates

### High Priority
1. **rejoin_approvals.php** (Lines 455-620)
   - Add Finance Manager detection
   - Add payer selection dropdown
   - Add payer payment recording UI
   - Pass payer_emp_id to backend

### Medium Priority  
2. **assets/js/loan_approval.js** (Optional enhancement)
   - Add success message customization based on payer selection
   - Add payer payment status display

3. **includes/ajaxFile/ajaxVacation.php** (approveVacation handler)
   - Add Finance Manager payer selection support if needed
   - Update frontend UI accordingly

---

## Code Examples for Updates

### Example 1: Add Finance Manager Detection in Rejoin Approvals

```javascript
function viewAndApproveRequest(rejoinRequestId, empId, rejoinDate, empName, vacationType) {
    // Get current user's type from global variable or data attribute
    const currentUserType = document.body.dataset.userType || '<?php echo $_SESSION['user_type'] ?? ""; ?>';
    const isFinanceManager = (currentUserType === 'finance');
    
    // ... existing code ...
    
    if (isFinanceManager && action === 'approve') {
        // Fetch finance staff and show payer selection
        fetchFinanceStaff();
    }
}
```

### Example 2: Fetch Finance Staff for Rejoin

```javascript
function fetchFinanceStaff() {
    $.ajax({
        url: './includes/ajaxFile/ajaxVacation.php',
        type: 'POST',
        data: { ajaxType: 'get_finance_staff' },
        dataType: 'JSON',
        success: function(response) {
            if (response.status === 'success') {
                // Populate payer dropdown
                const options = response.staff.map(s => 
                    `<option value="${s.emp_id}">${s.name} (${s.emp_id})</option>`
                ).join('');
                $('#payerSelect').html(options);
            }
        }
    });
}
```

### Example 3: Send Payer to Backend

```javascript
const formData = new FormData();
formData.append('ajaxType', 'processRejoinApproval');
formData.append('rejoin_request_id', rejoinRequestId);
formData.append('action', action);
formData.append('payer_emp_id', isFinanceManager ? selectedPayerId : '');
formData.append('approval_note', approvalNote);

// Send to backend
```

---

## Summary

**Current Status**:
- ✅ **Loan Approvals**: Fully updated with Finance Manager payer selection and payer payment recording
- ⚠️ **Rejoin Approvals**: Backend ready, frontend needs Finance Manager payer selection UI
- ⚠️ **Vacation Approvals**: Generic handler, needs Finance Manager payer support if required

**Action Required**:
1. Update `rejoin_approvals.php` to add Finance Manager payer selection UI
2. Test rejoin approval workflow end-to-end
3. Optionally update vacation approval if Finance Manager payer selection is needed

**Estimated Effort**:
- Add Rejoin payer selection UI: 2-3 hours
- Add Rejoin payer payment UI: 1-2 hours
- Testing: 1-2 hours
- **Total: 4-7 hours**

---

**Last Updated**: December 24, 2025
**Status**: Partial Implementation - Loan Complete, Rejoin/Vacation Pending
