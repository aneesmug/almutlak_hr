# Loan Approval with Payment Proof and Automated Payroll Integration

## Overview
This document describes the enhanced loan approval system with payment proof requirement for the final payer and automated payroll integration based on loan type.

## Features Implemented

### 1. Final Payer Approval (Level 7 - Finance Officer)
When the Finance Officer (final payer) approves a loan, they must provide:
- **Payment Proof File**: Upload document (PDF, JPG, PNG, DOC, DOCX)
- **Final Approved Amount**: The actual amount being paid (defaults to requested amount)

### 2. Automated Payroll Integration
After final approval with payment proof, the system automatically adds loan deductions to payroll based on loan type:

#### A. EOS Loan (`eos_loan`)
- **Deduction Type**: Monthly installment
- **Location**: `payroll_deductions` table
- **Deduction Name**: "EOS Loan Installment - [INV_NO]"
- **Amount**: `monthly_deduction` from loan record
- **Duration**: Spread across number of installments
- **Example**: 
  - Loan Amount: 10,000 SAR
  - Installments: 7 months
  - Monthly Deduction: 1,428.57 SAR added to payroll for 7 consecutive months

#### B. Housing Loan (`housing_loan`)
- **Deduction Type**: Monthly installment
- **Location**: `payroll_deductions` table
- **Deduction Name**: "Housing Loan Deduction - [INV_NO]"
- **Amount**: `monthly_deduction` from loan record
- **Duration**: Spread across number of installments
- **Note**: Employee must have housing allowance > 0
- **Example**:
  - Loan Amount: 15,000 SAR
  - Installments: 6 months
  - Monthly Deduction: 2,500 SAR added to payroll for 6 consecutive months

#### C. Advance Salary (`advance_salary`)
- **Deduction Type**: One-time deduction
- **Location**: `payroll_deductions` table
- **Deduction Name**: "Advance Salary Deduction - [INV_NO]"
- **Amount**: `final_approved_amount` (full amount)
- **Duration**: Current month only (one-time)
- **Example**:
  - Loan Amount: 5,000 SAR
  - Deduction: 5,000 SAR added to current month's payroll as one-time deduction

## Database Changes

### emp_loan Table
Two new columns added:
```sql
ALTER TABLE `emp_loan` 
ADD COLUMN `payment_proof_file` VARCHAR(255) NULL COMMENT 'Payment proof uploaded by finance officer',
ADD COLUMN `final_approved_amount` DECIMAL(10,2) NULL COMMENT 'Final amount approved and paid by finance officer';
```

### File Storage
Payment proof files stored in: `assets/loan_payment_proofs/`
- Naming format: `payment_proof_[INV_NO]_[TIMESTAMP].[EXT]`
- Example: `payment_proof_LN-20251112-1234-abcd_1699876543.pdf`

## Code Changes

### Backend (PHP)

#### 1. `includes/ajaxFile/ajaxLoan.php`
- **Modified `approve_loan()` function**:
  - Detects if approver is Level 7 Finance Officer
  - Validates payment proof file upload
  - Validates final approved amount
  - Saves payment proof file
  - Updates `emp_loan` with payment proof and final amount
  - Triggers `integrate_loan_to_payroll()` after final approval

- **New function `integrate_loan_to_payroll()`**:
  - Gets loan details and type
  - Routes to appropriate payroll integration based on loan type
  - Returns success/failure status

- **New function `add_monthly_installment_deduction()`**:
  - Adds monthly deductions for EOS and Housing loans
  - Prevents duplicate entries
  - Spreads deductions across installment periods

### Frontend (JavaScript)

#### 1. `assets/js/loan_approval.js`
- **Modified `approveLoanRequest()` function**:
  - Now accepts 5 parameters: `loanId`, `role`, `requestedAmount`, `userType`, `approvalLevel`
  - Detects if current approver is Finance Officer (Level 7)
  - Shows special modal with payment proof upload fields for final payer
  - Shows normal approval modal for other levels
  - Uses FormData to handle file upload

#### 2. `all_applied_loan.php`
- **Updated SQL query**:
  - Added `current_approver_user_type` to SELECT
- **Updated approve button**:
  - Passes `total_payable`, `current_approver_user_type`, `current_approval_level` to JS function

## Workflow

### Normal Approval Flow (Levels 1-6)
1. Approver clicks "Approve" button
2. Simple confirmation modal appears
3. Approval recorded in `request_approvers` table
4. Next level set to pending
5. Status history updated

### Final Payer Approval (Level 7)
1. Finance Officer clicks "Approve" button
2. **Special modal appears** with:
   - Final Approved Amount field (pre-filled with requested amount)
   - Payment Proof file upload
3. Finance Officer enters actual paid amount and uploads proof
4. System validates inputs:
   - Amount must be > 0
   - File must be provided
   - File format must be PDF, JPG, PNG, DOC, or DOCX
5. File uploaded to `assets/loan_payment_proofs/`
6. `emp_loan` table updated with:
   - `payment_proof_file`
   - `final_approved_amount`
7. Approval recorded in `request_approvers` table
8. Loan status set to 'approved'
9. **Automated Payroll Integration triggered**:
   - System reads loan type
   - Creates appropriate deduction entries in `payroll_deductions` table
   - For EOS/Housing: Creates monthly entries for each installment
   - For Advance Salary: Creates single entry for current month
10. Status history updated with "fully_approved"
11. Success message shown

## Payroll Deductions Table Structure

The system uses the `payroll_deductions` table:
```sql
CREATE TABLE `payroll_deductions` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `emp_id` VARCHAR(50) NOT NULL,
  `deduction` VARCHAR(255) NOT NULL,  -- Deduction name/label
  `note` VARCHAR(255) NOT NULL,        -- Deduction amount (stored as string)
  `month` VARCHAR(7) NOT NULL,         -- Format: YYYY-MM
  `status` TINYINT DEFAULT 1,          -- 1 = active
  FOREIGN KEY (`emp_id`) REFERENCES `employees`(`emp_id`)
);
```

### Example Entries

#### EOS Loan (7 installments, 1,428.57 SAR/month)
```
| emp_id | deduction                            | note    | month   | status |
|--------|--------------------------------------|---------|---------|--------|
| 1234   | EOS Loan Installment - LN-...-abcd   | 1428.57 | 2025-11 | 1      |
| 1234   | EOS Loan Installment - LN-...-abcd   | 1428.57 | 2025-12 | 1      |
| 1234   | EOS Loan Installment - LN-...-abcd   | 1428.57 | 2026-01 | 1      |
| ...    | ...                                  | ...     | ...     | ...    |
```

#### Advance Salary (one-time, 5,000 SAR)
```
| emp_id | deduction                            | note    | month   | status |
|--------|--------------------------------------|---------|---------|--------|
| 1234   | Advance Salary Deduction - LN-...-xyz| 5000.00 | 2025-11 | 1      |
```

## Testing Checklist

### Prerequisites
1. Database columns added (`payment_proof_file`, `final_approved_amount`)
2. Directory created (`assets/loan_payment_proofs/` with write permissions)
3. Finance Officer user exists with `user_type='finance_officer'`

### Test Case 1: EOS Loan with Monthly Installments
1. Employee creates EOS loan request for 10,000 SAR, 7 installments
2. Approve through Levels 1-6 (Supervisor → HR Payroll → HR Supervisor → Auditor → GM → Finance Manager)
3. Finance Officer (Level 7) approves:
   - Upload payment proof PDF
   - Enter final amount: 10,000 SAR
   - Click "Approve & Submit"
4. **Verify**:
   - Loan status = 'approved'
   - `payment_proof_file` stored in database and file exists
   - `final_approved_amount` = 10,000.00
   - 7 entries in `payroll_deductions` table for months 2025-11 through 2026-05
   - Each deduction = 1,428.57 SAR
   - Deduction name = "EOS Loan Installment - LN-20251112-xxxx-yyyy"

### Test Case 2: Housing Loan
1. Employee creates housing loan request for 12,000 SAR, 6 installments
2. Approve through all levels to Finance Officer
3. Finance Officer approves with payment proof and amount
4. **Verify**:
   - 6 monthly deduction entries created
   - Each deduction = 2,000 SAR
   - Deduction name = "Housing Loan Deduction - LN-..."

### Test Case 3: Advance Salary (One-Time Deduction)
1. Employee creates advance salary request for 3,000 SAR
2. Approve through all levels to Finance Officer
3. Finance Officer approves with payment proof
4. **Verify**:
   - Only 1 deduction entry for current month
   - Deduction amount = 3,000 SAR (full amount)
   - Deduction name = "Advance Salary Deduction - LN-..."

### Test Case 4: Validation - Missing Payment Proof
1. Create any loan type
2. Approve to Level 7
3. Finance Officer clicks approve but doesn't upload file
4. **Verify**: Error message "Payment proof document is required"

### Test Case 5: Validation - Missing Amount
1. Create any loan type
2. Approve to Level 7
3. Finance Officer uploads file but clears amount field
4. **Verify**: Error message "Approved amount is required..."

### Test Case 6: Validation - Invalid File Format
1. Create any loan type
2. Approve to Level 7
3. Finance Officer uploads .exe or .zip file
4. **Verify**: Error message "Payment proof must be PDF, JPG, PNG, or DOC file"

## File Locations

### Modified Files
1. `/includes/ajaxFile/ajaxLoan.php` - Backend approval logic and payroll integration
2. `/assets/js/loan_approval.js` - Frontend approval modal with file upload
3. `/all_applied_loan.php` - Updated button with new parameters

### New Files
1. `/db_updates/add_loan_payment_proof_columns.sql` - Database schema update
2. `/assets/loan_payment_proofs/` - Storage directory for payment proof files
3. `/LOAN_PAYMENT_PROOF_GUIDE.md` - This documentation file

## Security Considerations

1. **File Upload Security**:
   - Only specific file types allowed (PDF, JPG, PNG, DOC, DOCX)
   - Files renamed with timestamp to prevent conflicts
   - Files stored outside webroot or with proper .htaccess protection

2. **Amount Validation**:
   - Server-side validation ensures amount > 0
   - Amount type-cast to float to prevent injection

3. **Authorization**:
   - Only Level 7 approvers with `user_type='finance_officer'` can access payment proof modal
   - Standard approval chain validation applies

## Troubleshooting

### Issue: Payroll deductions not created
**Check**:
1. `emp_loan.status` = 'approved'
2. `emp_loan.payment_proof_file` is not NULL
3. PHP error logs for `integrate_loan_to_payroll()` errors
4. Employee has `emp_salary` record with `status=1`

### Issue: File upload fails
**Check**:
1. Directory `/assets/loan_payment_proofs/` exists
2. Directory has write permissions (777 or appropriate)
3. PHP `upload_max_filesize` and `post_max_size` settings
4. File size within limits

### Issue: Housing loan fails with "no housing allowance"
**Check**:
1. Employee's `emp_salary.housing` value > 0
2. `emp_salary.status` = 1 (active record)

## Future Enhancements

1. **Email Notifications**: Notify employee when loan is fully approved and payroll entries created
2. **Payment Proof Viewer**: Add link to view/download payment proof in loan details
3. **Payroll Preview**: Show preview of payroll entries before final approval
4. **Deduction Adjustment**: Allow HR to modify deduction amounts after approval (with audit trail)
5. **Loan Balance Tracking**: Display remaining balance and upcoming deductions in employee view

## Support

For questions or issues, contact IT Team or check:
- Main loan approval documentation
- Payroll deductions guide
- Request approvers system documentation
