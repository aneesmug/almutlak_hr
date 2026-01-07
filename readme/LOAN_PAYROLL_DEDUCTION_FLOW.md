# Loan Automatic Payroll Deduction Implementation Guide

## Overview
This document describes the complete flow for automatic loan deduction when a GM approves a loan and how monthly payroll processing automatically deducts approved loans.

## Complete Flow

### Phase 1: Loan Approval (When GM Approves Final Level)
**File:** `includes/ajaxFile/ajaxLoan.php` - `approve_loan()` function

1. **GM Approves Loan** (Last approval level)
   - Status: `status = 'approved'`
   - Location: Lines 775-862

2. **Automatic Payroll Integration Triggered**
   - Function: `integrate_loan_to_payroll($loan_id, $conDB)`
   - Location: Lines 841-851
   - Called AFTER loan status is set to 'approved'

3. **Deduction Entries Created**
   - Function: `add_monthly_installment_deduction()`
   - Location: Lines 2950-3000
   - Inserts into `payroll_deductions` table for each month
   - Deduction format: `"{LOAN_TYPE} Loan - {INV_NO}"`
   - Amount stored in `note` field as formatted decimal string

### Phase 2: Payroll Generation (Monthly)
**File:** `includes/api/process_payroll.php`

1. **Process Payroll for Month**
   - Fetch all employees for the month
   - Check for payroll_deductions entries
   - Location: Lines 283-294

2. **Calculate Total Deductions**
   - Query: Reads all `payroll_deductions` with `status = 1`
   - Excludes loans set to `deduction_mode = 'manual'`
   - Location: Lines 283-294
   - Filter: `emp_id`, `month`, and `status`

3. **Calculate Net Salary**
   - Formula: `NetSalary = GrossSalary + Benefits - Deductions`
   - Creates/Updates `payrolls` record
   - Location: Lines 299-351

4. **Record Loan Payments (Optional)**
   - Function: `record_monthly_loan_payments($conDB, $month)`
   - Records payment in `emp_loan_payments` for tracking
   - Location: Lines 3007-3100

## Database Tables Used

### 1. `emp_loan`
**Key Fields:**
- `id` (int) - Primary key
- `inv_no` (varchar) - Invoice number
- `emp_id` (varchar) - Employee ID
- `loan_type` (enum) - Type of loan
- `loan_amount` (decimal) - Requested amount
- `monthly_deduction` (decimal) - Monthly payment amount
- `installments` (int) - Number of months
- `start_date` (date) - Deduction start date
- `deduction_mode` (enum) - 'automatic' or 'manual'
- `status` (varchar) - 'approved' when loan is fully approved

### 2. `payroll_deductions`
**Key Fields:**
- `id` (int) - Primary key
- `emp_id` (varchar) - Employee ID
- `deduction` (varchar) - Deduction name/type
- `note` (varchar) - Amount (stored as formatted decimal string)
- `month` (varchar) - Month in 'Y-m' format
- `status` (int) - 1 = active, 0 = inactive

**Sample Entry:**
```
emp_id: '1574'
deduction: 'End of Service Loan - LN-2025-00001'
note: '850.00'
month: '2025-01'
status: 1
```

### 3. `emp_loan_payments`
**Key Fields:**
- `loan_id` (int) - Loan ID
- `payment_date` (date) - Payment date
- `amount` (decimal) - Amount paid
- `payment_method` (varchar) - 'payroll' for auto-deductions
- `receipt_id` (varchar) - Optional receipt number

## Implementation Details

### Deduction Creation Process

```php
// When GM approves (final level):
integrate_loan_to_payroll($loan_id, $conDB);

// Which calls:
add_monthly_installment_deduction(
    $conDB,
    $emp_id,           // Employee ID
    $inv_no,           // Loan invoice number (e.g., 'LN-2025-00001')
    $monthly_amount,   // Monthly deduction (e.g., 850.00)
    $installments,     // Number of months (e.g., 12)
    $start_date,       // Start date as DateTime
    $deduction_label   // 'End of Service Loan', 'Housing Loan', etc.
);
```

### Deduction Format Examples

**End of Service Loan:**
- Deduction Name: `End of Service Loan - LN-2025-00001`
- Monthly Amount: `850.00` (stored in `note` field)
- Duration: 12 months
- Created for months: Jan 2025 → Dec 2025

**Housing Loan:**
- Deduction Name: `Housing Loan - LN-2025-00002`
- Monthly Amount: `500.00`
- Duration: 24 months

**Advance Salary:**
- Deduction Name: `Advance Salary Deduction - LN-2025-00003`
- Amount: Full amount in single month
- Duration: 1 month

## Automatic Features

### 1. Duplicate Prevention
- Before creating deduction, checks if already exists
- Uses: `emp_id`, `month`, `deduction_name`
- Skips if entry exists

### 2. Manual Deduction Exclusion
- If loan has `deduction_mode = 'manual'`, no auto-deductions created
- Payroll processes skips manual-mode loans when calculating deductions

### 3. Status History Tracking
- Inserts into `smt_request_status` table on approval
- Records: `'fully_approved'` status
- Useful for audit trail

### 4. Notification System
- Browser notification sent to loan creator
- Email sent to loan creator with loan details

## Potential Enhancements

### 1. Add Loan ID to Payroll Deductions
Consider adding a `loan_id` column to `payroll_deductions` for direct linking:
```sql
ALTER TABLE payroll_deductions ADD COLUMN loan_id INT NULL;
ALTER TABLE payroll_deductions ADD FOREIGN KEY (loan_id) REFERENCES emp_loan(id);
```

### 2. Deduction Status Tracking
Add a `deduction_id_list` column to `emp_loan` to track payroll_deductions IDs:
```sql
ALTER TABLE emp_loan ADD COLUMN payroll_deduction_ids JSON NULL COMMENT 'Array of payroll_deductions IDs';
```

### 3. Enhanced Logging
Create a `loan_payroll_deduction_log` table for audit trail:
```sql
CREATE TABLE loan_payroll_deduction_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    loan_id INT NOT NULL,
    inv_no VARCHAR(255),
    emp_id VARCHAR(20),
    deduction_month VARCHAR(7),
    deduction_amount DECIMAL(10,2),
    status ENUM('created', 'applied', 'paid', 'cancelled'),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    applied_at TIMESTAMP NULL,
    FOREIGN KEY (loan_id) REFERENCES emp_loan(id)
);
```

## Testing Checklist

### Test Case 1: Create and Approve Loan
1. Create a loan application (emp_loan record)
2. Approvers approve loan through chain
3. GM gives final approval
4. **Expected:** Status changes to 'approved'
5. **Expected:** Payroll deductions created for all months

### Test Case 2: Payroll Generation
1. Generate payroll for a month with active loan
2. **Expected:** Deductions included in payroll
3. **Expected:** Net salary = Gross - Deduction

### Test Case 3: Manual Deduction Mode
1. Create loan with `deduction_mode = 'manual'`
2. Approve loan
3. Generate payroll
4. **Expected:** No automatic deductions
5. **Expected:** Manual deductions can be added separately

### Test Case 4: Multiple Loans
1. Employee has multiple active loans
2. Generate payroll
3. **Expected:** All loan deductions applied
4. **Expected:** Total deductions sum of all loans

## Troubleshooting

### Issue: Deductions not appearing in payroll
1. Check `emp_loan` status is 'approved' (not 'pending')
2. Check `payroll_deductions` entries exist for the month
3. Check `deduction_mode` is 'automatic' (not 'manual')
4. Check `payroll_deductions.status = 1` (not 0)
5. Verify month format is 'Y-m' (e.g., '2025-01')

### Issue: Wrong deduction amount
1. Verify `monthly_deduction` in emp_loan
2. Check `payroll_deductions.note` field (should be formatted decimal)
3. Verify calculation: `monthly_deduction = total_payable / installments`

### Issue: Deductions for wrong months
1. Verify `start_date` in emp_loan
2. Check that loop correctly iterates: `for ($i = 0; $i < $installments; $i++)`
3. Ensure date arithmetic adds months correctly

## Related Files

- **Approval Logic:** `includes/ajaxFile/ajaxLoan.php` (lines 251-862)
- **Deduction Creation:** `includes/ajaxFile/ajaxLoan.php` (lines 2838-3000)
- **Payroll Processing:** `includes/api/process_payroll.php` (lines 1-679)
- **Payroll Updates:** `includes/api/update_payroll.php` (lines 1-210)
- **Frontend:** `assets/js/loan_approval.js` (approval modal handling)
- **Frontend:** `assets/js/loanHandling.js` (application modal handling)

## Summary

The automatic loan deduction system is fully implemented:

✅ **Phase 1 - Approval:** When GM approves, `integrate_loan_to_payroll()` creates monthly deduction entries
✅ **Phase 2 - Payroll:** `process_payroll.php` automatically includes deductions when calculating salaries
✅ **Flexibility:** Manual deduction mode allows exceptions when needed
✅ **Tracking:** Payments can be recorded in `emp_loan_payments` table

The system flows: **Approval → Deduction Creation → Payroll Generation → Auto-Deduction**
