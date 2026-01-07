# GM Loan Approval with Modification Implementation

## Overview
This implementation enables the General Manager (GM) to modify loan amounts and installments when approving loan requests. When a GM approves a loan, they are presented with a modal showing:
- Employee's End of Service benefit (calculated)
- Maximum loan amount (40% of EOS)
- Current loan amount and installments
- Option to modify both amount and installments
- Real-time calculation of monthly deduction

## Changes Made

### 1. Frontend Changes - `assets/js/loan_approval.js`

**Modified Function:** `approveLoanRequest()`

Added GM detection at the beginning of the function:
```javascript
// Check if this is GM (user_type = 'gm')
const isGM = (userType === 'gm');
```

When GM approves a loan, the system now:
1. Fetches loan details including employee ID via AJAX
2. Calls `modifyAndApproveLoan()` function which shows the modification modal
3. GM can adjust loan amount within limits and change installments (1-12 months)
4. Real-time calculation shows monthly deduction
5. Submit button is disabled if amount exceeds max limit

**Flow:**
```
User clicks Approve → approveLoanRequest() called
↓
Check if userType === 'gm'
↓
YES: Fetch loan details → Call modifyAndApproveLoan()
↓
NO: Continue with normal approval process
```

### 2. Backend Changes - `includes/ajaxFile/ajaxLoan.php`

**Added New AJAX Case:** `get_loan_details_for_modification`

```php
case 'get_loan_details_for_modification':
    get_loan_details_for_modification();
    break;
```

**New Function:** `get_loan_details_for_modification()`

This function:
- Accepts `loan_id` parameter
- Returns employee ID and current loan details
- Enables the frontend to fetch EOS and salary details

Response format:
```json
{
    "status": "success",
    "emp_id": "1234",
    "loan_amount": 50000,
    "installments": 12,
    "loan_type": "end_of_service"
}
```

### 3. Existing Function Used - `modifyAndApproveLoan()`

The frontend already has a comprehensive `modifyAndApproveLoan()` function that:
- Fetches EOS benefit and max loan amount (40% of EOS)
- Shows modification modal with:
  - Current amount and installments pre-filled
  - Max loan amount displayed
  - Real-time monthly deduction calculation
  - Validation (amount cannot exceed max)
- Calls `modify_and_approve_loan` AJAX action on submit
- Handles success/error responses

## Approval Flow with GM Modification

```
Supervisor Approves
↓
HR Assistant Approves (if configured)
↓
HR Manager Approves (if configured)
↓
Finance Manager Approves / Assigns Payer
↓
GM APPROVES (NEW: Can Modify Amount & Installments)
↓
Finance Assistant Finalizes
```

## User Experience

### Before (GM):
- Simple approve/reject option
- No ability to adjust terms

### After (GM):
1. Clicks "Approve" on loan card
2. Modal appears showing:
   - ✓ Employee's Total EOS Benefit (calculated)
   - ✓ Maximum Loan Amount (40% of EOS)
   - ✓ Current Loan Amount (with text input)
   - ✓ Current Installments (with dropdown 1-12)
   - ✓ Monthly Deduction (calculated in real-time)
3. GM can modify loan amount (capped at max) and installments
4. Monthly deduction updates automatically
5. Submit button disabled if amount exceeds max
6. On submit: Modified loan goes to next approver (or finalizes if GM is last approver)

## Validation Rules

- **Maximum Amount:** 40% of End of Service Benefit
- **Minimum Amount:** 0.01 SAR
- **Installments:** 1-12 months
- **Monthly Deduction:** Automatically calculated as `loan_amount / installments`
- **Error Handling:** If amount exceeds max, submit button is disabled with error message

## Technical Details

### Function Call Chain:
1. `approveLoanRequest()` - Detects GM user type
2. AJAX call to `get_loan_details_for_modification()` - Fetches employee ID
3. `modifyAndApproveLoan()` - Shows modification modal
4. AJAX call to `modify_and_approve_loan` - Processes approval with modified terms
5. Page reloads with updated loan status

### Database Updates:
When GM modifies and approves:
- `emp_loan.loan_amount` - Updated to new amount
- `emp_loan.installments` - Updated to new installment count
- `emp_loan.monthly_deduction` - Recalculated
- `emp_loan.end_date` - Recalculated based on new installments
- `request_approvers` - Status updated to 'approved' for current level
- Next approver's status changed to 'pending'
- Notifications sent to next approver

## Backward Compatibility

- Non-GM users continue to use the simple approve modal
- Finance Manager and Payer workflows unchanged
- HR Assistant has their own modification function (`modifyAndApproveLoanHRAssistant`)
- All existing approval chains work as before

## Testing Checklist

- [ ] GM sees approve button on pending loans
- [ ] Click approve → modification modal appears
- [ ] EOS and max amount displayed correctly
- [ ] Loan amount field is editable with current amount pre-filled
- [ ] Installments dropdown works (1-12)
- [ ] Monthly deduction calculates correctly on amount/installment change
- [ ] Submit disabled if amount > max amount
- [ ] Error message shows when amount exceeds max
- [ ] Submit succeeds with valid modifications
- [ ] Loan status updates in database
- [ ] Next approver receives notification
- [ ] Modified loan appears in next approver's queue

## Files Modified

1. `assets/js/loan_approval.js` - Added GM detection and fetch logic
2. `includes/ajaxFile/ajaxLoan.php` - Added `get_loan_details_for_modification()` case and function

## Files Not Modified (But Used)

- `assets/js/loan_approval.js` - `modifyAndApproveLoan()` function (already existed)
- `includes/ajaxFile/ajaxLoan.php` - `modify_and_approve_loan()` function (already existed)
